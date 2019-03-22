<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'is_directory', 'level', 'path'];

    protected $casts = [
        'is_directory' => 'boolean',
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function (Category $category) {
            // parent_id 为null 则是创建一个根类目
            if (is_null($category->parent_id)) {
                $category->level = 0;
                $category->path = '-';
            } else {
                // 将层级设为父类目的层级 +1
                $category->level = $category->parent->level + 1;
                $category->path = $category->parent->path . $category->parent_id . '-';
            }
        });
    }

    public function parent()
    {
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

    // 获取所有祖先类目 ID 值
    public function getPathIdsAttribute()
    {
        return array_filter(explode('-', trim($this->path, '-')));
    }

    // 获取所有祖先类目并按层级排序
    public function getAncestorsAttribute()
    {
        return $this::query()
            ->whereIn('id', $this->path_ids)
            ->orderBy('level')
            ->get();
    }

    // 获取以 - 分隔的所有祖先类目名称以及当前类目的名称
    public function getFullNameAttribute()
    {
        return $this->ancestors
            ->pluck('name')
            ->push($this->name)
            ->implode('-');
    }
}
