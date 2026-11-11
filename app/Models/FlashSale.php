<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlashSale extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['title', 'starts_at', 'ends_at', 'status'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => 'integer',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(FlashSaleProduct::class);
    }

    public function approvedItems(): HasMany
    {
        return $this->items()->where('status', FlashSaleProduct::APPROVED);
    }

    #[Scope]
    protected function running(Builder $query): void
    {
        $query->where('status', 1)->where('starts_at', '<=', now())->where('ends_at', '>', now());
    }

    #[Scope]
    protected function upcoming(Builder $query): void
    {
        $query->where('status', 1)->where('starts_at', '>', now());
    }

    public function isRunning(): bool
    {
        return $this->status === 1 && $this->starts_at->isPast() && $this->ends_at->isFuture();
    }
}
