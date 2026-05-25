<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function categories()
    {
        return $this->hasMany(Category::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function category()
    {
        return $this->hasOne(Category::class, 'id', 'parent_id');
    }

    public function subcategories()
    {
        return $this->hasMany(Subcategory::class, 'category_id')
            ->where('status', 1)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function menusubcategories()
    {
        return $this->hasMany(Subcategory::class, 'category_id')
            ->select('id', 'slug', 'subcategoryName', 'category_id')
            ->where('status', 1)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function childrenCategories()
    {
        return $this->hasMany(Category::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->with('categories');
    }

    public function menuchildcategories()
    {
        return $this->hasMany(Childcategory::class, 'subcategory_id')->select('id', 'slug', 'subcategory_id', 'childcategoryName')->where('status', 1);
    }

    public function homeproducts()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function menuproducts()
    {
        return $this->hasMany(Product::class, 'category_id')->limit(8);
    }

    public function products()
    {
        // NOTE: products.stock column was dropped in 2026_01_05_000004_drop_stock_column_from_products_table.php
        // Stock is now derived from warehouse stock, so do not select a non-existent column.
        return $this->hasMany(Product::class, 'category_id')
            ->select('id', 'name', 'slug', 'category_id', 'new_price', 'old_price', 'sold', 'has_variant')
            ->orderBy('id', 'DESC');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
