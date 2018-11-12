<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name','is_directory','level','path'];
    protected $casts = [
        'is_directory' => 'boolean',
    ];

    protected static function boot(){
        parent::boot();
        //监听 Category 的创建事件，用于初始化 path 和 level 的值
        static::creating(function(Category $category){
            //如果创建的类是一个根类目
            if(is_null($category->parent_id)){
                //将层级设为 0
                $category->level = 0;
                $category->path = '-';
            }else{
                //层级为父类 +1
                $category->level = $category->parent->level +1;
                $category->path = $category->parent->path.$category->parent_id.'-';
            }
        });
    }

    public function parent(){
        return $this->belongsTo(Category::class);
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    //定义一个访问器，获取所有祖先类目的 ID 值
    public function getPathIdsAttribute(){
        return array_filter(explode('-',trim($this->path,'-')));
    }

    //定义一个访问器，获取所有祖先类目并按层级排序
    public function getAncestorsAttribute(){
        return Category::query()
            ->whereIn('id',$this->path_ids)
            ->orderBy('level')
            ->get();
    }

    //定义一个访问器，获取以'-' 为分隔符的所有祖先类目名称以及当前类目的名称
    public function getFullNameAttribute(){
        return $this->ancestors    //获取所有祖先类目
            ->pluck('name')         //取出所有祖先类目的 name 字段作为一个数组
            ->push($this->name)     //将当前类目 name 放入数组尾部
            ->implode(' - ');
    }
}