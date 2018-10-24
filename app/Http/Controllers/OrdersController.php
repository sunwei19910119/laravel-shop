<?php

namespace App\Http\Controllers;

use App\Events\OrderReviewed;
use App\Exceptions\InternalException;
use App\Exceptions\InvalidRequestException;
use App\Http\Requests\Admin\HandleRefundRequest;
use App\Http\Requests\ApplyRefundRequest;
use App\Http\Requests\OrderRequest;
use App\Http\Requests\SendReviewRequest;
use App\Jobs\CloseOrder;
use App\Models\Order;
use App\Models\ProductSku;
use App\Models\UserAddress;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    public function store(OrderRequest $request, OrderService $orderService)
    {
        $user    = $request->user();
        $address = UserAddress::find($request->input('address_id'));

        return $orderService->store($user, $address, $request->input('remark'), $request->input('items'));
    }

    public function index(Request $request){
        //使用with预加载，避免 N+1 问题
        $orders = Order::query()
            ->with(['items.product','items.productSku'])
            ->where('user_id',$request->user()->id)
            ->orderBy('created_at','desc')
            ->paginate();
        return view('orders.index',['orders'=>$orders]);
    }

    public function show(Order $order,Request $request){
        $this->authorize('own', $order);

        return view('orders.show',['order' => $order->load(['items.productSku','items.product'])]);
    }


    public function received(Order $order,Request $request){
        //校验权限
        $this->authorize('own',$order);

        //判断订单的发货状态是否已发货
        if($order->ship_status !== Order::SHIP_STATUS_DELIVERED){
            throw new InvalidRequestException('发货状态不争取');
        }

        $order->update(['ship_status' => Order::SHIP_STATUS_RECEIVED]);

        return $order;
    }

    public function review(Order $order){
        //校验权限
        $this->authorize('own',$order);
        //判断是否已经支付
        if(!$order->paid_at){
            throw new InvalidRequestException('该订单未支付，不可评价');
        }
        //使用 load 预加载
        return view('orders.review',['order'=>$order->load(['items.productSku','items.product'])]);
    }

    public function sendReview(Order $order,SendReviewRequest $request){
        $this->authorize('own',$order);
        if (!$order->paid_at) {
            throw new InvalidRequestException('该订单未支付，不可评价');
        }
        //判断是否已评价
        if($order->reviewed){
            throw new InvalidRequestException('该订单已评价，不可重复提交');
        }
        $reviews = $request->input('reviews');
        \DB::transaction(function () use ($reviews,$order){
            foreach($reviews as $review){
                $orderItem = $order->items()->find($review['id']);
                $orderItem->update([
                    'rating' => $review['rating'],
                    'review' => $review['review'],
                    'reviewed_at' => Carbon::now()
                ]);
            }
            $order->update(['reviewed' => true]);
            event(new OrderReviewed($order));
        });

        return redirect()->back();
    }

    public function applyRefund(Order $order,ApplyRefundRequest $request){
        $this->authorize('own',$order);
        if (!$order->paid_at) {
            throw new InvalidRequestException('该订单未支付，不可评价');
        }
        //判断订单退款状态
        if($order->refund_status !== Order::REFUND_STATUS_PENDING){
            throw new InvalidRequestException('该订单已经申请过退款，请勿重复申请');
        }
        //将用户输入的退款理由放到 extra 字段中
        $extra = $order->extra ?: [];
        $extra['refund_reason'] = $request->input('reason');
        //将订单退款状态改为已申请退款
        $order->update([
            'refund_status' => Order::REFUND_STATUS_APPLIED,
            'extra' => $extra
        ]);
        return $order;
    }

    public function handleRefund(Order $order,HandleRefundRequest $request){
        if($order->refund_status !== Order::REFUND_STATUS_APPLIED){
            throw new InvalidRequestException('订单状态不正确');
        }
        if($request->input('agree')){
            $this->_refundOrder($order);
        }else{
            $extra = $order->extra ?: [];
            $extra['refund_disagree_reason'] = $request->input('reason');
            $order->update([
                'refund_status' => Order::REFUND_STATUS_PENDING,
                'extra' => $extra
            ]);
        }
        return $order;
    }

    protected function _refundOrder(Order $order){
        //判断订单的支付方式
        switch ($order->payment_method){
            case 'wechat':
                break;
            case 'alipay':
                //生成退款订单号
                $refundNo = Order::getAvailableRefundBo();
                //调用支付宝支付实例的 refund 方法
                $ret = app('alipay')->refund([
                    'out_trade_no' => $order->no, //订单流水号
                    'refund_amount' => $order->total_amount, //退款金额，单位元
                    'out_request_no' => $refundNo, //退款订单号
                ]);
                //根据支付宝文档，如果返回值里有 sub_code 字段说明退款失败
                if($ret->sub_code){
                    //将退款失败存入 extra 字段
                    $extra = $order->extra;
                    $extra['refund_failed_code'] = $ret->sub_code;
                    //将订单的退款状态标记为退款失败
                    $order->update([
                        'refund_no' => $refundNo,
                        'refund_status' => Order::REFUND_STATUS_FAILED,
                        'extra' => $extra,
                    ]);
                }else {
                    // 将订单的退款状态标记为退款成功并保存退款订单号
                    $order->update([
                        'refund_no' => $refundNo,
                        'refund_status' => Order::REFUND_STATUS_SUCCESS,
                    ]);
                }
                break;
            default: throw new InternalException('未知订单支付方式:'.$order->payment_method);break;
        }
    }
}
