<?php

namespace App\Services\Themes;

class ThemeCatalog
{
    /**
     * Storefront niches the admin can assign to a theme.
     *
     * @return array<string, string>
     */
    public static function niches(): array
    {
        return [
            'classic' => 'Classic marketplace',
            'fashion' => 'Fashion and apparel',
            'grocery' => 'Grocery and fresh food',
            'electronics' => 'Electronics and gadgets',
            'marketplace' => 'Multi-vendor bazaar',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function fonts(): array
    {
        return [
            'Poppins',
            'Open Sans',
            'Fraunces',
            'Source Sans 3',
            'Libre Baskerville',
            'Nunito',
            'DM Sans',
            'Space Grotesk',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function headerStyles(): array
    {
        return [
            'classic' => 'Classic bar',
            'centered' => 'Centered logo',
            'compact' => 'Compact',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function productCards(): array
    {
        return [
            'portrait' => 'Portrait cards',
            'compact' => 'Compact cards',
            'spec' => 'Specification cards',
        ];
    }

    /**
     * Default settings for a niche, used by the admin form and built-in themes.
     *
     * @return array<string, string>
     */
    public static function preset(string $niche): array
    {
        $presets = [
            'classic' => [
                'primary' => '#0088cc',
                'secondary' => '#f4f4f4',
                'accent' => '#ff6600',
                'text' => '#222529',
                'surface' => '#ffffff',
                'font_heading' => 'Poppins',
                'font_body' => 'Open Sans',
                'header_style' => 'classic',
                'product_card' => 'portrait',
                'announcement' => 'Welcome to Durian — multi-vendor shopping',
            ],
            'fashion' => [
                'primary' => '#1c1410',
                'secondary' => '#f6f1ea',
                'accent' => '#9a6b4f',
                'text' => '#1c1410',
                'surface' => '#fffdf9',
                'font_heading' => 'Fraunces',
                'font_body' => 'Nunito',
                'header_style' => 'centered',
                'product_card' => 'portrait',
                'announcement' => 'New season edit — free shipping over $75',
            ],
            'grocery' => [
                'primary' => '#1f7a3a',
                'secondary' => '#f3faf4',
                'accent' => '#f4b000',
                'text' => '#16301d',
                'surface' => '#ffffff',
                'font_heading' => 'Nunito',
                'font_body' => 'Source Sans 3',
                'header_style' => 'compact',
                'product_card' => 'compact',
                'announcement' => 'Fresh today — same-day grocery from local vendors',
            ],
            'electronics' => [
                'primary' => '#0e1420',
                'secondary' => '#e8eefc',
                'accent' => '#3d8bfd',
                'text' => '#0e1420',
                'surface' => '#f7f9fc',
                'font_heading' => 'Space Grotesk',
                'font_body' => 'DM Sans',
                'header_style' => 'compact',
                'product_card' => 'spec',
                'announcement' => 'Compare gadgets from verified sellers',
            ],
            'marketplace' => [
                'primary' => '#9a3412',
                'secondary' => '#fff8f1',
                'accent' => '#ea580c',
                'text' => '#431407',
                'surface' => '#ffffff',
                'font_heading' => 'Libre Baskerville',
                'font_body' => 'Source Sans 3',
                'header_style' => 'classic',
                'product_card' => 'compact',
                'announcement' => 'Shop independent vendors in one checkout',
            ],
        ];

        return $presets[$niche] ?? $presets['classic'];
    }

    /**
     * Keep only known theme settings and fall back to the niche preset.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    public static function normalize(array $input, string $niche): array
    {
        $defaults = self::preset($niche);
        $settings = [];

        foreach (['primary', 'secondary', 'accent', 'text', 'surface'] as $color) {
            $value = (string) ($input[$color] ?? '');
            $settings[$color] = preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1 ? $value : $defaults[$color];
        }

        $settings['font_heading'] = in_array($input['font_heading'] ?? '', self::fonts(), true)
            ? (string) $input['font_heading']
            : $defaults['font_heading'];
        $settings['font_body'] = in_array($input['font_body'] ?? '', self::fonts(), true)
            ? (string) $input['font_body']
            : $defaults['font_body'];
        $settings['header_style'] = array_key_exists((string) ($input['header_style'] ?? ''), self::headerStyles())
            ? (string) $input['header_style']
            : $defaults['header_style'];
        $settings['product_card'] = array_key_exists((string) ($input['product_card'] ?? ''), self::productCards())
            ? (string) $input['product_card']
            : $defaults['product_card'];
        $settings['announcement'] = mb_substr(trim(strip_tags((string) ($input['announcement'] ?? $defaults['announcement']))), 0, 160);

        return $settings;
    }

    /**
     * Built-in themes installed with the migration.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function builtins(): array
    {
        $themes = [
            ['name' => 'Porto Classic', 'slug' => 'porto-classic', 'niche' => 'classic', 'description' => 'The original storefront for a general marketplace.', 'is_active' => true],
            ['name' => 'Fashion Atelier', 'slug' => 'fashion-atelier', 'niche' => 'fashion', 'description' => 'Editorial layout for clothing, beauty, and lifestyle shops.', 'is_active' => false],
            ['name' => 'Fresh Grocery', 'slug' => 'fresh-grocery', 'niche' => 'grocery', 'description' => 'Compact, category-first layout for food and daily essentials.', 'is_active' => false],
            ['name' => 'Circuit Electronics', 'slug' => 'circuit-electronics', 'niche' => 'electronics', 'description' => 'Dark specification-led layout for gadgets and appliances.', 'is_active' => false],
            ['name' => 'Bazaar Marketplace', 'slug' => 'bazaar-marketplace', 'niche' => 'marketplace', 'description' => 'Vendor-first bazaar for many sellers on one site.', 'is_active' => false],
        ];

        return array_map(function (array $theme): array {
            $theme['settings'] = self::preset($theme['niche']);
            $theme['source'] = 'builtin';
            $theme['version'] = '1.0.0';
            $theme['author'] = 'Durian';
            $theme['is_locked'] = true;
            $theme['status'] = 1;

            return $theme;
        }, $themes);
    }
}
