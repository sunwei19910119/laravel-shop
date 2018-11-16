<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    const TYPE_NORMAL = 'normal';
    const TYPE_CROWDFUNDING = 'crowdfunding';
    public static $typeMap = [
        self::TYPE_NORMAL  => '普通商品',
        self::TYPE_CROWDFUNDING => '众筹商品',
    ];

    protected $fillable = [
        'title', 'description', 'image', 'on_sale','long_title',
        'rating', 'sold_count', 'review_count', 'price','type',
    ];

    protected $casts = [
        'on_sale' => 'boolean',
    ];

    //关联商品库存
    public function skus(){
        return $this->hasMany(ProductSku::class);
    }

    public function crowdfunding()
    {
        return $this->hasOne(CrowdfundingProduct::class);
    }

    public function properties()
    {
        return $this->hasMany(ProductProperty::class);
    }

    public function getImageUrlAttribute(){
        //如果image字段本身已经是完整的url就直接返回
        if(Str::startsWith($this->attributes['image'],['http://','https://'])){
            return $this->attributes['image'];
        }
        return \Storage::disk('admin')->url($this->attributes['image']);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function getGroupedPropertiesAttribute(){
        return $this->properties
            ->groupBy('name')
            ->map(function($properties){
                // 使用 map 方法将属性集合变为属性值集合
                return $properties->pluck('value')->all();
            });
    }
}
