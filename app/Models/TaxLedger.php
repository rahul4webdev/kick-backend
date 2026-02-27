<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxLedger extends Model
{
    protected $table = 'tbl_tax_ledger';

    public $timestamps = false;

    protected $fillable = [
        'seller_id', 'order_id', 'tax_type', 'amount_paise',
        'financial_year', 'month', 'description',
        'status', 'deposited_at',
    ];

    protected $casts = [
        'amount_paise' => 'integer',
        'created_at' => 'datetime',
        'deposited_at' => 'datetime',
    ];

    const TYPE_TCS = 'tcs';
    const TYPE_TDS = 'tds';

    const STATUS_ACCRUED = 0;
    const STATUS_DEPOSITED = 1;

    public function seller()
    {
        return $this->belongsTo(Users::class, 'seller_id');
    }

    /**
     * Get current Indian financial year (e.g., "2026-27").
     */
    public static function currentFY(): string
    {
        $now = now();
        $year = $now->year;
        $month = $now->month;

        if ($month < 4) {
            return ($year - 1) . '-' . substr($year, 2);
        }
        return $year . '-' . substr($year + 1, 2);
    }

    /**
     * Record a tax entry.
     */
    public static function record(int $sellerId, ?int $orderId, string $taxType, int $amountPaise, ?string $description = null): self
    {
        return self::create([
            'seller_id' => $sellerId,
            'order_id' => $orderId,
            'tax_type' => $taxType,
            'amount_paise' => $amountPaise,
            'financial_year' => self::currentFY(),
            'month' => now()->format('Y-m'),
            'description' => $description,
        ]);
    }
}
