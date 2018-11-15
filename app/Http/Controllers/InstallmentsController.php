<?php

namespace App\Http\Controllers;

use App\Models\Installment;
use Illuminate\Http\Request;

class InstallmentsController extends Controller
{
    public function index(Request $request){
        $installments = Installment::query()
            ->where('user_id',$request->user()->id)
            ->paginate(10);
        return view('installments.index',['installments'=>$installments]);
    }

    public function show(Installment $installment){
        $this->authorize('own', $installment);

        //取出当前分期的所有项目，按还款顺序排序
        $items = $installment->items()->orderBy('sequence')->get();
        return view('installments.show',[
            'installment' => $installment,
            'items' => $items,
            //下一个待支付的还款
            'nextItem' => $items->where('paid_at',null)->first(),
        ]);
    }
}