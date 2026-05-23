<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'products';

    protected $fillable = [
        'name',
        'category_id',
        'deleted',
    ];

    public function sale_details()
    {
        return $this->hasMany(SaleDetail::class);
    }

    public function order_details()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function location_prices()
    {
        return $this->hasMany(LocationPrice::class)->where('deleted', 0);
    }
}
