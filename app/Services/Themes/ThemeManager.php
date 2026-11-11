<?php

namespace App\Services\Themes;

use App\Models\StoreTheme;
use App\Models\Vendor;
use Illuminate\Database\QueryException;

class ThemeManager
{
    private ?StoreTheme $resolved = null;

    public function resolveForRequest(): StoreTheme
    {
        if ($this->resolved instanceof StoreTheme) {
            return $this->resolved;
        }

        try {
            $routeVendor = request()->route('vendor');
            $vendorId = $routeVendor instanceof Vendor ? $routeVendor->id : request()->route('vendorid');

            if (is_numeric($vendorId)) {
                $themeId = Vendor::query()->whereKey((int) $vendorId)->value('theme_id');

                if ($themeId) {
                    $vendorTheme = StoreTheme::query()->whereKey($themeId)->where('status', 1)->first();

                    if ($vendorTheme instanceof StoreTheme) {
                        return $this->resolved = $vendorTheme;
                    }
                }
            }

            $active = StoreTheme::query()->where('is_active', true)->where('status', 1)->first();

            if ($active instanceof StoreTheme) {
                return $this->resolved = $active;
            }
        } catch (QueryException) {
            return $this->resolved = StoreTheme::fallback();
        }

        return $this->resolved = StoreTheme::fallback();
    }

    public function activate(StoreTheme $theme): void
    {
        StoreTheme::query()->where('is_active', true)->update(['is_active' => false]);
        $theme->is_active = true;
        $theme->status = 1;
        $theme->save();
        $this->resolved = null;
    }
}
