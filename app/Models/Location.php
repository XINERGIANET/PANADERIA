<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'locations';

    protected $fillable = [
        'name',
        'deleted',
    ];

    public function location_prices()
    {
        return $this->hasMany(LocationPrice::class);
    }
}
