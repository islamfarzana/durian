<?php

namespace App\Services\Delivery;

use App\Models\City;
use App\Models\DeliverySlot;
use App\Models\Setting;
use App\Models\ShippingCharge;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class DeliveryOptions
{
    public const SESSION_CITY = 'delivery_city';

    /**
     * Delivery days from today, each with its slots. A slot today is only
     * offered when it starts after the configured lead time.
     *
     * @return array<int, array{date: string, label: string, slots: array<int, array{id: int, label: string, available: bool}>}>
     */
    public function days(): array
    {
        $now = now(config('marketplace.timezone'));
        $earliestStart = $now->copy()->addMinutes((int) Setting::number('slot_lead_minutes'));
        $slots = DeliverySlot::query()->active()->get();
        $days = [];

        for ($offset = 0; $offset < max(1, (int) Setting::number('delivery_days_ahead')); $offset++) {
            $day = $now->copy()->startOfDay()->addDays($offset);

            $days[] = [
                'date' => $day->toDateString(),
                'label' => match ($offset) {
                    0 => 'Today',
                    1 => 'Tomorrow',
                    default => $day->format('D, j M'),
                },
                'slots' => $slots->map(fn (DeliverySlot $slot): array => [
                    'id' => $slot->id,
                    'label' => $slot->label,
                    'available' => $this->startsAt($day, $slot)->gte($earliestStart),
                ])->all(),
            ];
        }

        return $days;
    }

    public function isAvailable(string $date, int $slotId): bool
    {
        foreach ($this->days() as $day) {
            if ($day['date'] !== $date) {
                continue;
            }

            foreach ($day['slots'] as $slot) {
                if ($slot['id'] === $slotId) {
                    return $slot['available'];
                }
            }
        }

        return false;
    }

    /**
     * @return array{date: string, slot: DeliverySlot}|null
     */
    public function firstAvailable(): ?array
    {
        foreach ($this->days() as $day) {
            foreach ($day['slots'] as $slot) {
                if ($slot['available']) {
                    return ['date' => $day['date'], 'slot' => DeliverySlot::query()->findOrFail($slot['id'])];
                }
            }
        }

        return null;
    }

    /**
     * Fee for one order. Free once the bag reaches the threshold; otherwise the
     * weight bracket for the city, then the country, then the default fee.
     */
    public function fee(float $subtotal, float $weightInGrams, ?string $city, ?string $country): float
    {
        if ($subtotal <= 0 || $this->freeDeliveryRemaining($subtotal) === 0.0) {
            return 0;
        }

        $rates = ShippingCharge::query()
            ->where('status', 1)
            ->when($country, fn ($query) => $query->where('country', $country))
            ->get();
        $rate = $rates->first(fn (ShippingCharge $charge): bool => $city !== null && strcasecmp((string) $charge->city, $city) === 0)
            ?? $rates->first();

        if ($rate === null) {
            return Setting::number('default_delivery_fee');
        }

        return (float) match (true) {
            $weightInGrams <= 500 => $rate->between_0_500g,
            $weightInGrams <= 1000 => $rate->between_501g_1000g,
            $weightInGrams <= 2000 => $rate->between_1001_2000g,
            $weightInGrams <= 5000 => $rate->between_2001g_5000g,
            default => $rate->above_5000g,
        };
    }

    /**
     * How much more the shopper must add for free delivery: zero once it is
     * free, null when the marketplace has no free-delivery threshold.
     */
    public function freeDeliveryRemaining(float $subtotal): ?float
    {
        $threshold = Setting::number('free_delivery_threshold');

        if ($threshold <= 0) {
            return null;
        }

        return round(max(0, $threshold - $subtotal), 2);
    }

    public function selectedCity(): string
    {
        $chosen = Session::get(self::SESSION_CITY);

        if (is_string($chosen) && $chosen !== '') {
            return $chosen;
        }

        return (string) (City::query()->where('status', 1)->orderBy('id')->value('city_name') ?? 'Dhaka');
    }

    /**
     * @return Collection<int, string>
     */
    public function cities(): Collection
    {
        return City::query()->where('status', 1)->orderBy('city_name')->pluck('city_name');
    }

    private function startsAt(Carbon $day, DeliverySlot $slot): Carbon
    {
        [$hour, $minute] = array_map('intval', explode(':', (string) $slot->starts_at));

        return $day->copy()->setTime($hour, $minute);
    }
}
