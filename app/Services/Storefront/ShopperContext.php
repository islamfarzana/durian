<?php

namespace App\Services\Storefront;

use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Cart\CartSummary;
use Illuminate\Support\Facades\Auth;

/**
 * Per-request facts about the current shopper that many partials render:
 * the bag, the wishlist, and followed stores. Each is loaded once.
 */
class ShopperContext
{
    /**
     * @var array<int, int>|null
     */
    private ?array $wishlistIds = null;

    /**
     * @var array<int, int>|null
     */
    private ?array $followedStoreIds = null;

    public function __construct(private CartService $cart) {}

    public function cart(): CartSummary
    {
        return $this->cart->summary();
    }

    /**
     * The signed-in customer. Admin and vendor logins use their own guard and are not customers.
     */
    public function customer(): ?User
    {
        $user = Auth::guard('web')->user();

        return $user instanceof User ? $user : null;
    }

    /**
     * @return array<int, int>
     */
    public function wishlistIds(): array
    {
        if ($this->wishlistIds === null) {
            $this->wishlistIds = $this->customer()
                ? $this->customer()->wishlist()->pluck('products.id')->map(fn ($id): int => (int) $id)->all()
                : [];
        }

        return $this->wishlistIds;
    }

    /**
     * @return array<int, int>
     */
    public function followedStoreIds(): array
    {
        if ($this->followedStoreIds === null) {
            $this->followedStoreIds = $this->customer()
                ? $this->customer()->followedStores()->pluck('vendors.id')->map(fn ($id): int => (int) $id)->all()
                : [];
        }

        return $this->followedStoreIds;
    }
}
