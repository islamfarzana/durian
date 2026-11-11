<?php

namespace App\Services\Themes;

use App\Models\StoreTheme;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class ThemeZipInstaller
{
    public function __construct(private ThemeCssSanitizer $cssSanitizer) {}

    public function install(UploadedFile $file): StoreTheme
    {
        if (strtolower($file->getClientOriginalExtension()) !== 'zip') {
            throw new RuntimeException('Upload a .zip theme package.');
        }

        $zip = new ZipArchive;
        $opened = $zip->open($file->getRealPath());

        if ($opened !== true) {
            throw new RuntimeException('The zip file could not be opened.');
        }

        try {
            $this->assertSafeEntries($zip);
            $manifest = $this->manifest($zip);
            $theme = $this->persist($manifest);
            $this->writeAssets($zip, $theme);

            return $theme;
        } finally {
            $zip->close();
        }
    }

    public function exampleDownload(): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'theme-zip-');

        if ($path === false) {
            throw new RuntimeException('An example theme zip could not be created.');
        }

        $zipPath = $path.'.zip';
        rename($path, $zipPath);

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('theme.json', json_encode([
            'name' => 'Harbor Market',
            'slug' => 'harbor-market',
            'niche' => 'marketplace',
            'version' => '1.0.0',
            'author' => 'Durian',
            'description' => 'A warm marketplace theme packaged as a zip.',
            'settings' => ThemeCatalog::preset('marketplace'),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $zip->addFromString('assets/theme.css', "/* Harbor Market */\n.store-theme .home-slide img{filter:saturate(1.05);}\n");
        $zip->close();

        return response()->download($zipPath, 'harbor-market-theme.zip')->deleteFileAfterSend(true);
    }

    private function assertSafeEntries(ZipArchive $zip): void
    {
        if ($zip->numFiles > 8) {
            throw new RuntimeException('A theme zip can contain only theme.json, a stylesheet, and a preview image.');
        }

        $allowed = [
            'theme.json',
            'assets/theme.css',
            'assets/preview.jpg',
            'assets/preview.jpeg',
            'assets/preview.png',
            'assets/preview.webp',
        ];
        $sawManifest = false;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);

            if ($stat === false) {
                throw new RuntimeException('The zip contains an unreadable entry.');
            }

            $original = (string) $stat['name'];
            $name = str_replace('\\', '/', $original);

            if (str_contains($original, '..') || str_starts_with($name, '/') || str_contains($name, ':')) {
                throw new RuntimeException('The zip contains an unsafe path.');
            }

            if (str_ends_with($name, '/')) {
                continue;
            }

            if (! in_array($name, $allowed, true)) {
                throw new RuntimeException('Themes can only include theme.json, assets/theme.css, and a preview image.');
            }

            if (($stat['size'] ?? 0) > 2_000_000) {
                throw new RuntimeException('A file inside the zip is too large.');
            }

            if ($name === 'theme.json') {
                $sawManifest = true;
            }
        }

        if (! $sawManifest) {
            throw new RuntimeException('The zip must include theme.json at the top level.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function manifest(ZipArchive $zip): array
    {
        $raw = $zip->getFromName('theme.json');

        if (! is_string($raw) || strlen($raw) > 65536) {
            throw new RuntimeException('theme.json is missing or too large.');
        }

        $manifest = json_decode($raw, true);

        if (! is_array($manifest)) {
            throw new RuntimeException('theme.json is not valid JSON.');
        }

        $niche = (string) ($manifest['niche'] ?? '');
        $slug = Str::slug((string) ($manifest['slug'] ?? ''));
        $name = trim(strip_tags((string) ($manifest['name'] ?? '')));

        if ($name === '' || $slug === '' || ! array_key_exists($niche, ThemeCatalog::niches())) {
            throw new RuntimeException('theme.json must include a name, slug, and a supported niche.');
        }

        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1) {
            throw new RuntimeException('The theme slug may only use lowercase letters, numbers, and hyphens.');
        }

        $existing = StoreTheme::query()->where('slug', $slug)->first();

        if ($existing?->is_locked) {
            throw new RuntimeException('Built-in themes cannot be replaced by a zip upload.');
        }

        $settingsInput = is_array($manifest['settings'] ?? null) ? $manifest['settings'] : [];

        return [
            'name' => mb_substr($name, 0, 255),
            'slug' => $slug,
            'niche' => $niche,
            'description' => mb_substr(trim(strip_tags((string) ($manifest['description'] ?? ''))), 0, 2000),
            'version' => mb_substr(trim(strip_tags((string) ($manifest['version'] ?? '1.0.0'))), 0, 32),
            'author' => mb_substr(trim(strip_tags((string) ($manifest['author'] ?? ''))), 0, 255),
            'settings' => ThemeCatalog::normalize($settingsInput, $niche),
            'existing' => $existing,
        ];
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function persist(array $manifest): StoreTheme
    {
        $theme = $manifest['existing'] instanceof StoreTheme ? $manifest['existing'] : new StoreTheme;
        $theme->fill([
            'name' => $manifest['name'],
            'slug' => $manifest['slug'],
            'niche' => $manifest['niche'],
            'description' => $manifest['description'] !== '' ? $manifest['description'] : null,
            'source' => 'zip',
            'version' => $manifest['version'] !== '' ? $manifest['version'] : '1.0.0',
            'author' => $manifest['author'] !== '' ? $manifest['author'] : null,
            'settings' => $manifest['settings'],
            'status' => 1,
            'is_locked' => false,
        ]);

        if (! $theme->exists) {
            $theme->is_active = false;
        }

        $theme->save();

        return $theme;
    }

    private function writeAssets(ZipArchive $zip, StoreTheme $theme): void
    {
        $directory = public_path('themes/'.$theme->slug);
        $themesRoot = public_path('themes');

        if (! is_dir($themesRoot) && ! mkdir($themesRoot, 0755, true) && ! is_dir($themesRoot)) {
            throw new RuntimeException('The themes directory could not be created.');
        }

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('The theme directory could not be created.');
        }

        $root = realpath($themesRoot);
        $target = realpath($directory);

        if ($root === false || $target === false || ! str_starts_with(strtolower($target), strtolower($root))) {
            throw new RuntimeException('The theme could not be stored in a safe directory.');
        }

        $css = $this->entry($zip, 'assets/theme.css');

        if ($css !== null) {
            file_put_contents($target.DIRECTORY_SEPARATOR.'theme.css', $this->cssSanitizer->sanitize($css));
        }

        $preview = $this->previewBytes($zip);

        if ($preview === null) {
            return;
        }

        $relative = 'themes/'.$theme->slug.'/preview.'.$preview['extension'];
        file_put_contents(public_path($relative), $preview['bytes']);
        $theme->preview_image = $relative;
        $theme->save();
    }

    private function entry(ZipArchive $zip, string $name): ?string
    {
        $contents = $zip->getFromName($name);

        if (! is_string($contents)) {
            $contents = $zip->getFromName(str_replace('/', '\\', $name));
        }

        return is_string($contents) ? $contents : null;
    }

    /**
     * @return array{bytes: string, extension: string}|null
     */
    private function previewBytes(ZipArchive $zip): ?array
    {
        foreach (['assets/preview.jpg', 'assets/preview.jpeg', 'assets/preview.png', 'assets/preview.webp'] as $name) {
            $bytes = $this->entry($zip, $name);

            if ($bytes === null || $bytes === '') {
                continue;
            }

            $info = @getimagesizefromstring($bytes);
            $mime = is_array($info) ? ($info['mime'] ?? '') : '';
            $extensions = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ];

            if (! isset($extensions[$mime])) {
                throw new RuntimeException('The preview must be a JPEG, PNG, or WebP image.');
            }

            return [
                'bytes' => $bytes,
                'extension' => $extensions[$mime],
            ];
        }

        return null;
    }
}
