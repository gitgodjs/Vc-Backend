<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MercadoPagoComprobante extends Model
{
    use HasFactory;

    protected $table = 'mercadopago_comprobantes';

    public $timestamps = false; 

    protected $fillable = [
        'user_id',
        'plan_id',
        'collection_id',
        'collection_status',
        'payment_id',
        'status',
        'external_reference',
        'payment_type',
        'merchant_order_id',
        'preference_id',
        'site_id',
        'processing_mode',
        'merchant_account_id',
        'created_at',
    ];
}
