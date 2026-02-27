<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOrder extends Model
{
    use HasFactory;

    protected $table = 'tbl_product_orders';

    protected $fillable = [
        'product_id',
        'buyer_id',
        'seller_id',
        'quantity',
        'total_coins',
        'transaction_id',
        'status',
        'shipping_address',
        'tracking_number',
        'buyer_note',
        'seller_note',
        // Real money fields
        'payment_transaction_id',
        'total_amount_paise',
        'shipping_charge_paise',
        'gst_amount_paise',
        'platform_commission_rate',
        'platform_commission_paise',
        'tcs_amount_paise',
        'tds_amount_paise',
        'seller_net_amount_paise',
        'payment_method',
        'shipping_method',
        'shiprocket_order_id',
        'shiprocket_shipment_id',
        'awb_code',
        'courier_name',
        'shipping_label_url',
        'estimated_delivery_date',
        'delivered_at',
        'return_window_expires_at',
        'shipping_address_id',
        'invoice_number',
        'invoice_url',
        'payout_id',
        'payout_eligible',
    ];

    protected $casts = [
        'total_amount_paise' => 'integer',
        'shipping_charge_paise' => 'integer',
        'gst_amount_paise' => 'integer',
        'platform_commission_paise' => 'integer',
        'tcs_amount_paise' => 'integer',
        'tds_amount_paise' => 'integer',
        'seller_net_amount_paise' => 'integer',
        'platform_commission_rate' => 'float',
        'payout_eligible' => 'boolean',
        'estimated_delivery_date' => 'date',
        'delivered_at' => 'datetime',
        'return_window_expires_at' => 'datetime',
    ];

    const STATUS_PENDING = 0;
    const STATUS_CONFIRMED = 1;
    const STATUS_SHIPPED = 2;
    const STATUS_DELIVERED = 3;
    const STATUS_CANCELLED = 4;
    const STATUS_REFUNDED = 5;
    const STATUS_RETURN_REQUESTED = 6;
    const STATUS_RETURN_IN_PROGRESS = 7;
    const STATUS_RETURN_COMPLETED = 8;

    const PAYMENT_PREPAID = 'prepaid';
    const PAYMENT_COD = 'cod';

    const SHIPPING_SELF = 'self';
    const SHIPPING_SHIPROCKET = 'shiprocket';
    const SHIPPING_DELHIVERY = 'delhivery';

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function buyer()
    {
        return $this->belongsTo(Users::class, 'buyer_id');
    }

    public function seller()
    {
        return $this->belongsTo(Users::class, 'seller_id');
    }

    public function transaction()
    {
        return $this->belongsTo(CoinTransaction::class, 'transaction_id');
    }

    public function paymentTransaction()
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id');
    }

    public function returns()
    {
        return $this->hasMany(ProductReturn::class, 'order_id');
    }

    public function shippingAddressRecord()
    {
        return $this->belongsTo(ShippingAddress::class, 'shipping_address_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_SHIPPED => 'Shipped',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_REFUNDED => 'Refunded',
            self::STATUS_RETURN_REQUESTED => 'Return Requested',
            self::STATUS_RETURN_IN_PROGRESS => 'Return In Progress',
            self::STATUS_RETURN_COMPLETED => 'Return Completed',
            default => 'Unknown',
        };
    }

    /**
     * Generate a unique invoice number.
     */
    public static function generateInvoiceNumber(): string
    {
        $prefix = 'KICK';
        $year = now()->format('y');
        $month = now()->format('m');
        $lastOrder = self::whereNotNull('invoice_number')
            ->orderByDesc('id')
            ->first();

        $sequence = 1;
        if ($lastOrder && $lastOrder->invoice_number) {
            $parts = explode('-', $lastOrder->invoice_number);
            $sequence = (int) end($parts) + 1;
        }

        return "{$prefix}-{$year}{$month}-" . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Check if return window is still open.
     */
    public function isReturnWindowOpen(): bool
    {
        if (!$this->return_window_expires_at) return false;
        return now()->lt($this->return_window_expires_at);
    }

    /**
     * Check if order is eligible for payout.
     */
    public function isPayoutEligible(): bool
    {
        if ($this->payout_eligible) return true;
        if ($this->status !== self::STATUS_DELIVERED) return false;
        if ($this->return_window_expires_at && now()->lt($this->return_window_expires_at)) return false;
        return true;
    }
}
