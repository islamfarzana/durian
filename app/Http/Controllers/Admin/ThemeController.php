<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveStoreThemeRequest;
use App\Models\StoreTheme;
use App\Services\Themes\ThemeCatalog;
use App\Services\Themes\ThemeCssSanitizer;
use App\Services\Themes\ThemeManager;
use App\Services\Themes\ThemeZipInstaller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ThemeController extends Controller
{
    public function __construct(
        private ThemeManager $themes,
        private ThemeCssSanitizer $cssSanitizer,
        private ThemeZipInstaller $zipInstaller,
    ) {}

    public function index(): View
    {
        $this->ensureSuperadmin();
        Session::put('page', 'themes');

        $themes = StoreTheme::query()->orderByDesc('is_active')->orderBy('name')->get();

        return view('admin.themes.index', [
            'themes' => $themes,
        ]);
    }

    public function create(): View
    {
        $this->ensureSuperadmin();
        Session::put('page', 'themes');

        return view('admin.themes.form', [
            'title' => 'Create Theme',
            'theme' => new StoreTheme([
                'niche' => 'classic',
                'settings' => ThemeCatalog::preset('classic'),
                'status' => 1,
            ]),
        ]);
    }

    public function store(SaveStoreThemeRequest $request): RedirectResponse
    {
        $theme = new StoreTheme;
        $this->fillTheme($theme, $request);
        $theme->source = 'admin';
        $theme->version = '1.0.0';
        $theme->author = Auth::guard('admin')->user()->name;
        $theme->is_locked = false;
        $theme->is_active = false;
        $theme->status = 1;
        $theme->save();
        $this->storePreview($theme, $request);

        if ($request->boolean('activate')) {
            $this->themes->activate($theme);
        }

        return redirect('admin/themes')->with('success_message', 'Theme created successfully!');
    }

    public function edit(int $id): View
    {
        $this->ensureSuperadmin();
        Session::put('page', 'themes');
        $theme = StoreTheme::query()->findOrFail($id);

        return view('admin.themes.form', [
            'title' => 'Edit Theme',
            'theme' => $theme,
        ]);
    }

    public function update(SaveStoreThemeRequest $request, int $id): RedirectResponse
    {
        $theme = StoreTheme::query()->findOrFail($id);
        $this->fillTheme($theme, $request);
        $theme->save();
        $this->storePreview($theme, $request);

        if ($request->boolean('activate')) {
            $this->themes->activate($theme);
        }

        return redirect('admin/themes')->with('success_message', 'Theme updated successfully!');
    }

    public function activate(int $id): RedirectResponse
    {
        $this->ensureSuperadmin();
        $theme = StoreTheme::query()->findOrFail($id);

        if ((int) $theme->status !== 1) {
            return redirect()->back()->with('error_message', 'Activate a published theme before making it the storefront.');
        }

        $this->themes->activate($theme);

        return redirect()->back()->with('success_message', $theme->name.' is now the storefront theme.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->ensureSuperadmin();
        $theme = StoreTheme::query()->findOrFail($id);

        if ($theme->is_locked) {
            return redirect()->back()->with('error_message', 'Built-in themes cannot be deleted.');
        }

        if ($theme->is_active) {
            return redirect()->back()->with('error_message', 'Activate another theme before deleting this one.');
        }

        $this->deleteAssets($theme);
        $theme->delete();

        return redirect()->back()->with('success_message', 'Theme deleted successfully!');
    }

    public function upload(Request $request): RedirectResponse
    {
        $this->ensureSuperadmin();
        $request->validate([
            'theme_zip' => ['required', 'file', 'mimes:zip', 'max:5120'],
        ], [
            'theme_zip.required' => 'Choose a theme zip to upload.',
            'theme_zip.mimes' => 'The theme package must be a zip file.',
            'theme_zip.max' => 'The theme zip must be 5 MB or smaller.',
        ]);

        try {
            $theme = $this->zipInstaller->install($request->file('theme_zip'));
        } catch (RuntimeException $exception) {
            return redirect()->back()->with('error_message', $exception->getMessage());
        }

        return redirect('admin/themes')->with('success_message', $theme->name.' was installed from the zip package.');
    }

    public function example(): BinaryFileResponse
    {
        $this->ensureSuperadmin();

        return $this->zipInstaller->exampleDownload();
    }

    private function fillTheme(StoreTheme $theme, SaveStoreThemeRequest $request): void
    {
        $data = $request->validated();

        if ($theme->is_locked) {
            $data['slug'] = $theme->slug;
        }

        $theme->fill([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'niche' => $data['niche'],
            'description' => $data['description'] ?? null,
            'settings' => ThemeCatalog::normalize($data, $data['niche']),
            'custom_css' => $this->cssSanitizer->sanitize($data['custom_css'] ?? ''),
        ]);
    }

    private function storePreview(StoreTheme $theme, SaveStoreThemeRequest $request): void
    {
        if (! $request->hasFile('preview_image')) {
            return;
        }

        $extension = $request->file('preview_image')->guessExtension() ?: 'jpg';
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (! in_array($extension, $allowed, true)) {
            $extension = 'jpg';
        }

        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        $directory = public_path('themes/'.$theme->slug);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $request->file('preview_image')->move($directory, 'preview.'.$extension);
        $theme->preview_image = 'themes/'.$theme->slug.'/preview.'.$extension;
        $theme->save();
    }

    private function deleteAssets(StoreTheme $theme): void
    {
        $directory = public_path('themes/'.$theme->slug);
        $root = realpath(public_path('themes'));
        $target = realpath($directory);

        if ($root === false || $target === false) {
            return;
        }

        if (! str_starts_with(strtolower($target), strtolower($root))) {
            return;
        }

        File::deleteDirectory($target);
    }

    private function ensureSuperadmin(): void
    {
        abort_unless(Auth::guard('admin')->user()?->type === 'superadmin', 403);
    }
}
