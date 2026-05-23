<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sales';

    protected $fillable = [
        'user_id',
        'location_id',
        'shift',
        'type_sale',
        'voucher_type',
        'voucher_id',
        'voucher_file',
        'number',
        'table_id',
        'client_id',
        'client_name',
        'phone',
        'delivery_date',
        'delivery_hour',
        'address',
        'reference',
        'observation',
        'total',
        'date',
        'type_status',
        'status',
        'deleted',
        'foto',
    ];

    protected $dates = [
        'date',
        'delivery_date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function sale_details()
    {
        return $this->hasMany(SaleDetail::class);
    }

    public function details()
    {
        return $this->hasMany(SaleDetail::class);
    }
    

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function saldo()//saldo a pagar de una venta
    {
        $totalPagos = $this->payments()->sum('subtotal');
        $saldo = $this->total - $totalPagos;
        return number_format($saldo, 2, '.', '');
    }
}
