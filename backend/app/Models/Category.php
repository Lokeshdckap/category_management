<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'parent_id',
        'name',
        'slug',
        'slug_url',
        'slug_status',
        'description',
        'featured_image',
        'featured_image_meta',
        'banner_image',
        'banner_image_meta',
        'featured',
        'status',
        'sort_order',
        'featured_order',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'featured_image_meta' => 'array',
        'banner_image_meta' => 'array',
        'featured' => 'boolean',
        'status' => 'boolean',
        'slug_status' => 'boolean',
        'sort_order' => 'integer',
        'featured_order' => 'integer',
    ];

    public function getRouteKeyName()
    {
        return 'uuid';
    }

      public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'category_product');
    }

    public function getFeaturedImageUrlAttribute()
    {
        return $this->featured_image 
            ? asset('storage/' . $this->featured_image) 
            : null;
    }

    public function getBannerImageUrlAttribute()
    {
        return $this->banner_image 
            ? asset('storage/' . $this->banner_image) 
            : null;
    }

    // Get full hierarchical slug
    public function getFullSlugAttribute()
    {
        $slugs = [$this->slug];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($slugs, $parent->slug);
            $parent = $parent->parent;
        }

        return implode('/', $slugs);
    }

    // Get hierarchical slug path for URL generation
    public static function getHierarchicalSlug($slug, $parentId = null)
    {
        $slugParts = [$slug];
        
        if ($parentId) {
            $parent = self::find($parentId);
            while ($parent) {
                array_unshift($slugParts, $parent->slug);
                $parent = $parent->parent;
            }
        }
        
        return implode('/', $slugParts);
    }

    public function getFullSlugUrlAttribute()
    {
        return $this->slug_url;
    }
}