<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationPrice extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'location_prices';

    protected $fillable = [
        'product_id',
        'location_id',
        'price',
        'quantity',
        'deleted',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
