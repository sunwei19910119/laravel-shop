<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    public function index(Request $request){
        //创建查询构造器
        $builder = Product::query()->where('on_sale',true);
        //判断是否有提交search参数，如果有就赋值给$search变量
        if($search = $request->input('search','')){
            $like = '%'.$search."%";
            //模糊搜索商品标题、商品详情、SKU标题、SKU描述
            $builder->where(function($query) use ($like){
               $query->where('title','like',$like)
                     ->orWhere('description','like',$like)
                     ->orWhereHas('skus',function($query) use ($like){
                        $query->where('title','like',$like)
                            ->orWhere('description','like',$like);
                     });
            });
        }

        //是否有提交order参数,如果有就赋值给$order变量
        //order参数用来控制商品的排序规则
        if($order = $request->input('order','')){
            //是否是以 _asc 或 _desc结尾
            if(preg_match('/^(.+)_(asc|desc)$/',$order,$m)){
                //如果字符串的开头是这个三个字符串之一，说明是合法的排序值
                if(in_array($m[1],['price','sold_count','rating'])){
                    //根据传入的排序值来构造排序参数,
                    $builder->orderBy($m[1],$m[2]);
                }
            }
        }

        $products = $builder->paginate(16);

        return view('products.index', [
            'products' => $products,
            'filters'  => [
                'search' => $search,
                'order'  => $order,
            ],
        ]);
    }

    public function show(Product $product,Request $request){
        //判断商品是否已经上架，如果没有上架则抛出异常
        if(!$product->on_sale){
            throw new InvalidRequestException('商品未上架');
        }

        $favored = false;
        //用户未登录时返回时NULL，已登录时返回的时对应的用户对象
        if($user = $request->user()){
            //从当前用户已收藏的商品中搜索ID为当前商品的记录
            //boolval()把值转换成布尔值
            $favored = boolval($user->favoriteProducts()->find($product->id));
        }
        return view('products.show',['product'=>$product,'favored'=>$favored]);
    }

    public function favor(Product $product,Request $request){
        $user = $request->user();
        if($user->favoriteProducts()->find($product->id)){
            return [];
        }
        $user->favoriteProducts()->attach($product);

        return [];
    }

    public function disfavor(Product $product,Request $request){
        $user = $request->user();
        $user->favoriteProducts()->detach($product);
        return [];
    }

    public function favorites(Request $request){
        $products = $request->user()->favoriteProducts()->paginate(16);
        return view('products.favorites',['products' => $products]);
    }

}
