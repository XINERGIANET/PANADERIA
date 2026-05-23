<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashClose extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'amount',
        'shift',
        'user_id',
        'location_id',
        'deleted'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

}
