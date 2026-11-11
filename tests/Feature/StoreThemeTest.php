<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\StoreTheme;
use App\Services\Themes\ThemeManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

class StoreThemeTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        File::deleteDirectory(public_path('themes/harbor-market'));
        File::deleteDirectory(public_path('themes/night-market'));

        parent::tearDown();
    }

    public function test_storefront_uses_the_active_theme(): void
    {
        app(ThemeManager::class)->activate(StoreTheme::query()->where('slug', 'porto-classic')->firstOrFail());

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Porto Classic', false);
        preg_match('/<body class="([^"]*)"/', $response->getContent(), $bodyClass);
        $bodyClasses = preg_split('/\s+/', $bodyClass[1] ?? '');
        $this->assertContains('store-theme', $bodyClasses);
        $this->assertContains('store-theme-classic', $bodyClasses);
        $response->assertSee('--store-primary:#0088cc', false);
    }

    public function test_superadmin_can_create_and_activate_a_theme(): void
    {
        $admin = Admin::query()->where('email', 'superadmin@gmail.com')->firstOrFail();

        $response = $this->actingAs($admin, 'admin')->post('/admin/themes', $this->themePayload([
            'name' => 'Night Market',
            'slug' => 'night-market',
            'announcement' => 'Night market is open',
            'activate' => '1',
        ]));

        $response->assertRedirect('/admin/themes');
        $this->assertDatabaseHas('store_themes', [
            'slug' => 'night-market',
            'is_active' => 1,
            'niche' => 'fashion',
        ]);
        $this->assertDatabaseHas('store_themes', [
            'slug' => 'porto-classic',
            'is_active' => 0,
        ]);

        $home = $this->get('/');
        $home->assertOk();
        $home->assertSee('Night market is open', false);
        $home->assertSee('store-theme-fashion', false);
    }

    public function test_guest_checkout_redirects_to_the_login_page(): void
    {
        $this->get('/checkout')->assertRedirect(route('login'));
    }

    public function test_vendor_cannot_open_theme_admin(): void
    {
        $vendor = Admin::query()->where('type', 'vendor')->firstOrFail();

        $this->actingAs($vendor, 'admin')
            ->get('/admin/themes')
            ->assertForbidden();
    }

    public function test_guest_cannot_download_another_customers_invoice(): void
    {
        $orderId = DB::table('orders')->insertGetId([
            'user_id' => 1,
            'name' => 'Bella',
            'address' => '12 Market Road',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'country' => 'Bangladesh',
            'pincode' => '1205',
            'mobile' => '01855854625',
            'email' => 'bella@mailinator.com',
            'shipping_charges' => 0,
            'order_status' => 'New',
            'payment_method' => 'COD',
            'payment_gateway' => 'COD',
            'grand_total' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/orders/invoice/download/'.$orderId)->assertForbidden();
    }

    public function test_zip_upload_installs_a_theme_and_rejects_unsafe_paths(): void
    {
        $admin = Admin::query()->where('email', 'superadmin@gmail.com')->firstOrFail();
        $path = $this->zipWith([
            'theme.json' => json_encode([
                'name' => 'Harbor Market',
                'slug' => 'harbor-market',
                'niche' => 'marketplace',
                'description' => 'Uploaded zip theme',
                'settings' => [
                    'primary' => '#9a3412',
                    'announcement' => 'Harbor is open',
                ],
            ]),
            'assets/theme.css' => '.store-theme{outline:none;}',
        ]);

        $response = $this->actingAs($admin, 'admin')->post('/admin/themes/upload', [
            'theme_zip' => new UploadedFile($path, 'harbor-market-theme.zip', 'application/zip', null, true),
        ]);

        $response->assertRedirect('/admin/themes');
        $this->assertDatabaseHas('store_themes', [
            'slug' => 'harbor-market',
            'source' => 'zip',
        ]);
        $this->assertFileExists(public_path('themes/harbor-market/theme.css'));

        $bad = $this->zipWith([
            'theme.json' => '{"name":"Bad","slug":"bad-theme","niche":"classic"}',
            '../evil.php' => '<?php echo 1;',
        ]);

        $rejected = $this->actingAs($admin, 'admin')->from('/admin/themes')->post('/admin/themes/upload', [
            'theme_zip' => new UploadedFile($bad, 'bad.zip', 'application/zip', null, true),
        ]);

        $rejected->assertRedirect('/admin/themes');
        $rejected->assertSessionHas('error_message');
        $this->assertDatabaseMissing('store_themes', ['slug' => 'bad-theme']);
        $this->assertFileDoesNotExist(public_path('evil.php'));
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function themePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Night Market',
            'slug' => 'night-market',
            'niche' => 'fashion',
            'description' => 'A fashion theme created in the admin panel.',
            'primary' => '#111111',
            'secondary' => '#f6f1ea',
            'accent' => '#9a6b4f',
            'text' => '#111111',
            'surface' => '#ffffff',
            'font_heading' => 'Fraunces',
            'font_body' => 'Nunito',
            'header_style' => 'centered',
            'product_card' => 'portrait',
            'announcement' => 'Night market is open',
            'custom_css' => '.product-title{letter-spacing:0.08em;}',
        ], $overrides);
    }

    /**
     * @param  array<string, string>  $files
     */
    private function zipWith(array $files): string
    {
        $path = tempnam(sys_get_temp_dir(), 'theme');
        $zipPath = $path.'.zip';
        rename($path, $zipPath);

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $name => $contents) {
            $zip->addFromString($name, $contents);
        }

        $zip->close();

        return $zipPath;
    }
}
