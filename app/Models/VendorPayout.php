<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPayout extends Model
{
    use HasFactory;

    public const REQUESTED = 'requested';

    public const PAID = 'paid';

    public const REJECTED = 'rejected';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['vendor_id', 'amount', 'status', 'account_details', 'reference', 'note', 'processed_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'processed_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
