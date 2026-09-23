<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'delivery_method',
        'payment_method',
        'subtotal',
        'delivery_cost',
        'discount',
        'total',
        'status',
        'notes',
        'order_date'
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'delivery_cost' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_items')
                    ->withPivot(['quantity', 'price', 'subtotal'])
                    ->withTimestamps();
    }

    public function getStatusTextAttribute()
    {
        $statuses = [
            'pending' => 'Ожидает подтверждения',
            'confirmed' => 'Подтвержден',
            'processing' => 'В обработке',
            'shipped' => 'Отправлен',
            'delivered' => 'Доставлен',
            'cancelled' => 'Отменен'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getDeliveryMethodTextAttribute()
    {
        $methods = [
            'nova_poshta' => 'Новая Почта',
            'ukrposhta' => 'Укрпошта',
            'meest' => 'Meest Express',
            'pickup' => 'Самовывоз'
        ];

        return $methods[$this->delivery_method] ?? $this->delivery_method;
    }

    public function getPaymentMethodTextAttribute()
    {
        $methods = [
            'card' => 'Банковская карта',
            'cash' => 'Наличные',
            'bank_transfer' => 'Банковский перевод'
        ];

        return $methods[$this->payment_method] ?? $this->payment_method;
    }

    public function getFormattedTotalAttribute()
    {
        return number_format($this->total, 2, ',', ' ') . ' ₴';
    }

    public function getFormattedOrderDateAttribute()
    {
        return $this->order_date->format('d.m.Y H:i');
    }
}
