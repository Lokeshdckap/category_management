<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CustomerGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customerGroup) {
            if (empty($customerGroup->uuid)) {
                $customerGroup->uuid = (string) Str::uuid();
            }
        });
    }

    public function customers()
    {
        return $this->hasMany(User::class, 'customer_group_id');
    }
}
