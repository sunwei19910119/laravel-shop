<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\SearchBuilders\ProductSearchBuilder;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductsController extends Controller
{
    public function index(Request $request, CategoryService $categoryService){
        $page    = $request->input('page', 1);
        $perPage = 16;
        // 新建查询构造器对象，设置只搜索上架商品，设置分页
        $builder = (new ProductSearchBuilder())->onSale()->paginate($perPage, $page);

        if ($request->input('category_id') && $category = Category::find($request->input('category_id'))) {
            // 调用查询构造器的类目筛选
            $builder->category($category);
        }

        if ($search = $request->input('search', '')) {
            $keywords = array_filter(explode(' ', $search));
            // 调用查询构造器的关键词筛选
            $builder->keywords($keywords);
        }

        if ($search || isset($category)) {
            // 调用查询构造器的分面搜索
            $builder->aggregateProperties();
        }

        $propertyFilters = [];
        if ($filterString = $request->input('filters')) {
            $filterArray = explode('|', $filterString);
            foreach ($filterArray as $filter) {
                list($name, $value) = explode(':', $filter);
                $propertyFilters[$name] = $value;
                // 调用查询构造器的属性筛选
                $builder->propertyFilter($name, $value);
            }
        }

        if ($order = $request->input('order', '')) {
            if (preg_match('/^(.+)_(asc|desc)$/', $order, $m)) {
                if (in_array($m[1], ['price', 'sold_count', 'rating'])) {
                    // 调用查询构造器的排序
                    $builder->orderBy($m[1], $m[2]);
                }
            }
        }

        // 最后通过 getParams() 方法取回构造好的查询参数
        $result = app('es')->search($builder->getParams());

        $properties = [];
        // 如果返回结果里有 aggregations 字段，说明做了分面搜索
        if (isset($result['aggregations'])) {
            // 使用 collect 函数将返回值转为集合
            $properties = collect($result['aggregations']['properties']['properties']['buckets'])
                ->map(function ($bucket) {
                    // 通过 map 方法取出我们需要的字段
                    return [
                        'key'    => $bucket['key'],
                        'values' => collect($bucket['value']['buckets'])->pluck('key')->all(),
                    ];
                })->filter(function ($property) use ($propertyFilters) {
                    // 过滤掉只剩下一个值 或者 已经在筛选条件里的属性
                    return count($property['values']) > 1 && !isset($propertyFilters[$property['key']]) ;
                });;
        }

        // 通过 collect 函数将返回结果转为集合，并通过集合的 pluck 方法取到返回的商品 ID 数组
        $productIds = collect($result['hits']['hits'])->pluck('_id')->all();

        // 通过 whereIn 方法从数据库中读取商品数据
        $products = Product::query()
            ->whereIn('id', $productIds)
            // orderByRaw 可以让我们用原生的 SQL 来给查询结果排序
            ->orderByRaw(sprintf("FIND_IN_SET(id, '%s')", join(',', $productIds)))
            ->get();
        // 返回一个 LengthAwarePaginator 对象
        $pager = new LengthAwarePaginator($products, $result['hits']['total'], $perPage, $page, [
            'path' => route('products.index', false), // 手动构建分页的 url
        ]);
        return view('products.index', [
            'products' => $pager,
            'filters'  => [
                'search' => $search,
                'order'  => $order,
            ],
            'category' => $category ?? null,
            'properties' => $properties,
            'propertyFilters' => $propertyFilters,
        ]);

//        //创建查询构造器
//        $builder = Product::query()->where('on_sale',true);
//        //判断是否有提交search参数，如果有就赋值给$search变量
//        if($search = $request->input('search','')){
//            $like = '%'.$search."%";
//            //模糊搜索商品标题、商品详情、SKU标题、SKU描述
//            $builder->where(function($query) use ($like){
//               $query->where('title','like',$like)
//                     ->orWhere('description','like',$like)
//                     ->orWhereHas('skus',function($query) use ($like){
//                        $query->where('title','like',$like)
//                            ->orWhere('description','like',$like);
//                     });
//            });
//        }
//
//        //是否有提交order参数,如果有就赋值给$order变量
//        //order参数用来控制商品的排序规则
//        if($order = $request->input('order','')){
//            //是否是以 _asc 或 _desc结尾
//            if(preg_match('/^(.+)_(asc|desc)$/',$order,$m)){
//                //如果字符串的开头是这个三个字符串之一，说明是合法的排序值
//                if(in_array($m[1],['price','sold_count','rating'])){
//                    //根据传入的排序值来构造排序参数,
//                    $builder->orderBy($m[1],$m[2]);
//                }
//            }
//        }
//
//        //如果有传入 category_id 字段，并且载数据库中有对应的类目
//        if($request->input('category_id') && $category = Category::find($request->input('category_id'))){
//            if($category->is_directory){
//                $builder->whereHas('category',function($query) use ($category){
//                    $query->where('path','like',$category->path.$category->id.'-%');
//                });
//            }else{
//                $builder->where('category_id',$category->id);
//            }
//        }
//
//        $products = $builder->paginate(16);

//        return view('products.index', [
//            'products' => $products,
//            'filters'  => [
//                'search' => $search,
//                'order'  => $order,
//            ],
//            'category' => $category ?? null,
//            'categoryTree' => $categoryService->getCategoryTree(),
//        ]);
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

        $reviews = OrderItem::query()
            ->with('order.user','productSku')
            ->where('product_id',$product->id)
            ->whereNotNull('reviewed_at')
            ->orderBy('reviewed_at','desc')
            ->limit(10)
            ->get();

        return view('products.show',['product'=>$product,'favored'=>$favored,'reviews'=>$reviews]);
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
