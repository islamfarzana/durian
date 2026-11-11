<?php

namespace App\Models;

use App\Services\Themes\ThemeCatalog;
use App\Services\Themes\ThemeCssSanitizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreTheme extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'niche',
        'description',
        'source',
        'version',
        'author',
        'settings',
        'custom_css',
        'preview_image',
        'is_active',
        'is_locked',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
            'is_locked' => 'boolean',
        ];
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class, 'theme_id');
    }

    public function setting(string $key, string $default = ''): string
    {
        $settings = is_array($this->settings) ? $this->settings : [];
        $preset = ThemeCatalog::preset($this->niche ?: 'classic');
        $value = $settings[$key] ?? $preset[$key] ?? $default;

        return (string) $value;
    }

    public function announcement(): string
    {
        return $this->setting('announcement');
    }

    public function cssVariables(): string
    {
        $heading = $this->cssFontFamily($this->setting('font_heading'), 'serif');
        $body = $this->cssFontFamily($this->setting('font_body'), 'sans-serif');

        return '--store-primary:'.$this->setting('primary').';'
            .'--store-secondary:'.$this->setting('secondary').';'
            .'--store-accent:'.$this->setting('accent').';'
            .'--store-text:'.$this->setting('text').';'
            .'--store-surface:'.$this->setting('surface').';'
            .'--store-font-heading:'.$heading.';'
            .'--store-font-body:'.$body.';'
            .'--store-radius:'.($this->niche === 'fashion' ? '0px' : '8px').';';
    }

    public function googleFontHref(): string
    {
        $fonts = array_values(array_unique(array_filter([
            $this->setting('font_heading'),
            $this->setting('font_body'),
        ])));

        $query = collect($fonts)
            ->map(fn (string $font): string => 'family='.str_replace('%20', '+', rawurlencode($font)).':wght@400;600;700')
            ->implode('&');

        return 'https://fonts.googleapis.com/css2?'.$query.'&display=swap';
    }

    public function stylesheetUrl(): ?string
    {
        if ($this->slug === null || ! is_file(public_path('themes/'.$this->slug.'/theme.css'))) {
            return null;
        }

        return asset('themes/'.$this->slug.'/theme.css');
    }

    public function previewUrl(): ?string
    {
        if ($this->preview_image === null || ! is_file(public_path($this->preview_image))) {
            return null;
        }

        return asset($this->preview_image);
    }

    public function safeCustomCss(): string
    {
        return app(ThemeCssSanitizer::class)->sanitize($this->custom_css);
    }

    public static function fallback(): self
    {
        $theme = new self;
        $theme->name = 'Porto Classic';
        $theme->slug = 'porto-classic';
        $theme->niche = 'classic';
        $theme->settings = ThemeCatalog::preset('classic');
        $theme->is_active = true;
        $theme->status = 1;

        return $theme;
    }

    private function cssFontFamily(string $font, string $generic): string
    {
        $serif = ['Fraunces', 'Libre Baskerville'];
        $generic = in_array($font, $serif, true) ? 'serif' : $generic;
        $safe = str_replace(['"', '\\', ';', '<', '>'], '', $font);

        return '"'.$safe.'", '.$generic;
    }
}
