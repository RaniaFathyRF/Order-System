<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'stock',
        'price'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Determines if the stock level is low.
     *
     * @return bool True if the stock level is less than or equal to 5, otherwise false.
     */
    public function hasLowStock(): bool
    {
        return $this->stock <= 5;
    }
}
