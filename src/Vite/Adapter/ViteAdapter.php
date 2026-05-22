<?php

declare(strict_types=1);

namespace NGSOFT\Vite\Adapter;

class ViteAdapter implements Version
{
    /** @var array<string, ?array> */
    private static array $manifests  = [];
    private ?string $hotFile         = null;
    private string $manifestFilename = 'manifest.json';
    private string $buildDirectory   = 'build';
    private string $basePath         = '';
    private ?string $nonce           = null;
    private bool $fixStylesImports   = false;
    private bool $fixScriptsImports  = false;
    private readonly MimeDetector $mimeDetector;
    private bool $usePreload         = true;
    private array $loaded            = [];

    public function __construct(
        private readonly string $projectRoot,
        private readonly string $publicDir,
        ?ViteAdapterOptions $options = null
    ) {
        $this->assertProjectRootValid($this->projectRoot);
        $this->assertPublicDirValid($this->publicDir);
        $this->mimeDetector = new MimeDetector();
        $options && $this->resolveOptions($options);
    }

    public function __invoke($entrypoints, ?string $buildDirectory = null, bool $loadClient = true): string
    {
        return $this->loadEntryPoints($entrypoints, $buildDirectory, $loadClient);
    }

    /**
     * Returns HTML code to load vite entry points.
     *
     * @param string|string[] $entrypoints
     * @param ?string         $buildDirectory
     * @param bool            $loadClient
     *
     * @return string
     *
     * @noinspection HtmlUnknownTarget
     */
    public function loadEntryPoints($entrypoints, ?string $buildDirectory = null, bool $loadClient = true): string
    {
        if ( ! is_array($entrypoints))
        {
            $entrypoints = [$entrypoints];
        }

        $buildDirectory ??= $this->buildDirectory;

        if ($hot = $this->getHotFile())
        {
            $server = trim(file_get_contents($hot));
            $nonce  = $this->nonce ? sprintf(' nonce="%s"', $this->nonce) : '';
            $html   = $loadClient ? sprintf(
                '<script%s type="module" src="%s"></script>',
                $nonce,
                $this->resolvePath($server, '@vite/client')
            ) : '';

            foreach ($entrypoints as $entrypoint)
            {
                if ($this->isCss($entrypoint))
                {
                    $html .= sprintf(
                        "\n" . '<link%s rel="stylesheet" href="%s" crossorigin>',
                        $nonce,
                        $this->resolvePath($server, $entrypoint)
                    );
                    continue;
                }
                $html .= sprintf(
                    "\n" . '<script%s type="module" src="%s" crossorigin></script>',
                    $nonce,
                    $this->resolvePath($server, $entrypoint)
                );
            }
            return "{$html}\n";
        }

        $manifest = $this->manifest($buildDirectory);

        $prefetch = [];
        $styles   = [];
        $scripts  = [];

        foreach ($entrypoints as $entrypoint)
        {
            $chunks   = $this->getChunk($manifest, $entrypoint);
            $scripts  = [...$scripts, ...$chunks[0]];
            $styles   = [...$styles, ...$chunks[1]];
            $prefetch = [...$prefetch, ...$chunks[2]];
        }
        $scripts  = array_values(array_unique($scripts));
        $styles   = array_values(array_unique($styles));
        $prefetch = array_values(array_unique($prefetch));

        $html     = [];
        $preload  = [];

        foreach ($styles as $file)
        {
            if ( ! in_array($file, $this->loaded))
            {
                $this->loaded[] = $file;
                $html[]         = $this->makeTag($file, $buildDirectory);
                $preload[]      = $this->renderPreloadTag($file, $buildDirectory);

                if ($this->fixStylesImports)
                {
                    $this->handleRepairStylesImports($file, $buildDirectory);
                }
            }
        }

        foreach ($scripts as $file)
        {
            if ( ! in_array($file, $this->loaded))
            {
                $this->loaded[] = $file;
                $html[]         = $this->makeTag($file, $buildDirectory);
                $preload[]      = $this->renderPreloadTag($file, $buildDirectory);

                if ($this->fixScriptsImports)
                {
                    $this->handleRepairScriptsImports($file, $buildDirectory);
                }
            }
        }

        foreach ($prefetch as $file)
        {
            if ( ! in_array($file, $this->loaded))
            {
                $this->loaded[] = $file;
                $preload[]      = $this->renderPrefetchTag($file, $buildDirectory);
            }
        }

        // code
        $code     = '';

        if ($this->usePreload)
        {
            $code = implode("\n", $preload) . "\n";
        }
        $code .= implode("\n", $html);
        return ltrim("{$code}\n");
    }

    public function clear(): static
    {
        $this->loaded = [];
        return $this;
    }

    public function getNonce(): ?string
    {
        return $this->nonce;
    }

    public function setNonce(string $nonce): static
    {
        $this->nonce = $nonce;
        return $this;
    }

    public function canFixStylesImports(): bool
    {
        return $this->fixStylesImports;
    }

    public function setFixStylesImports(bool $fixStylesImports): static
    {
        $this->fixStylesImports = $fixStylesImports;
        return $this;
    }

    public function canFixScriptsImports(): bool
    {
        return $this->fixScriptsImports;
    }

    public function setFixScriptsImports(bool $fixScriptsImports): static
    {
        $this->fixScriptsImports = $fixScriptsImports;
        return $this;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function setBasePath(string $basePath): static
    {
        $this->basePath = $this->normalizePath($basePath);
        return $this;
    }

    public function canPreload(): bool
    {
        return $this->usePreload;
    }

    public function setPreload(bool $usePreload): static
    {
        $this->usePreload = $usePreload;
        return $this;
    }

    public function setHotFile(?string $hotFile): static
    {
        $this->hotFile = $hotFile;
        return $this;
    }

    public function getHotFile(): ?string
    {
        $file = $this->hotFile ?? $this->resolvePath($this->publicDir, 'hot');
        return is_file($file) ? $file : null;
    }

    public function getPublicDir(): string
    {
        return $this->publicDir;
    }

    public function getProjectRoot(): string
    {
        return $this->projectRoot;
    }

    public function getBuildDirectory(): string
    {
        return $this->buildDirectory;
    }

    public function setBuildDirectory(string $buildDirectory): static
    {
        $this->buildDirectory = $buildDirectory;
        return $this;
    }

    public function getManifestFilename(): string
    {
        return $this->manifestFilename;
    }

    public function setManifestFilename(string $manifestFilename): static
    {
        $this->manifestFilename = $manifestFilename;
        return $this;
    }

    /**
     * @param array<string,ViteEntrypoint> $manifest
     * @param string                       $entrypoint
     * @param string[]                     $loaded
     *
     * @return array{string[],string[]}
     */
    private function getChunk(array $manifest, string $entrypoint, array &$loaded = [])
    {
        if (empty($manifest[$entrypoint]))
        {
            throw new ViteException("Manifest file does not contains an entry for \"{$entrypoint}\".");
        }

        $loaded[] = $entrypoint;
        $styles   = [];
        $scripts  = [];
        $prefetch = [];
        $entry    = $manifest[$entrypoint];

        foreach ($entry->getDynamicImports() as $import)
        {
            if ( ! in_array($import, $loaded))
            {
                $chunks   = $this->getChunk($manifest, $import, $loaded);
                $prefetch = [...$prefetch, ...$chunks[2], ...$chunks[0]];
                $styles   = [...$styles, ...$chunks[1]];
            }
        }

        foreach ($entry->getImports() as $import)
        {
            if ( ! in_array($import, $loaded))
            {
                $chunks   = $this->getChunk($manifest, $import, $loaded);
                $prefetch = [...$prefetch, ...$chunks[2], ...$chunks[0]];
                $styles   = [...$styles, ...$chunks[1]];
            }
        }

        foreach ($entry->getCss() as $css)
        {
            $styles = [$css, ...$styles];
        }

        if ($this->isCss($entrypoint))
        {
            $styles[] = $entry->getFile();
        } else
        {
            $scripts[] = $entry->getFile();
        }

        $scripts  = array_values(array_unique($scripts));
        $styles   = array_values(array_unique($styles));
        $prefetch = array_values(array_unique($prefetch));
        return [$scripts, $styles, $prefetch];
    }

    private function handleRepairScriptsImports(string $file, string $buildDirectory): void
    {
        $real     = $this->resolvePath($this->publicDir, $buildDirectory, $file);
        $repaired = "{$real}.repaired";

        if (is_file($repaired))
        {
            return;
        }
        @copy($real, $repaired);

        if ($content = @file_get_contents($real))
        {
            // remove the asset absolute path (if basepath changed)
            $content = preg_replace('#(=["`]modulepreload["`][^"`]+[`"])/#', '$1', $content);
            @file_put_contents($real, $content);
        }
    }

    private function handleRepairStylesImports(string $file, string $buildDirectory): void
    {
        $real     = $this->resolvePath($this->publicDir, $buildDirectory, $file);
        $repaired = "{$real}.repaired";

        if (is_file($repaired))
        {
            return;
        }

        @copy($real, $repaired);

        if ($content = @file_get_contents($real))
        {
            // remove the asset absolute path (if basepath changed)
            $content = str_replace(
                'url(/',
                'url(',
                $content,
            );

            @file_put_contents($real, $content);
        }
    }

    private function resolveOptions(ViteAdapterOptions $options): void
    {
        $this->setBasePath($options->basePath);
        $this->buildDirectory    = $options->buildDirectory;
        $this->nonce             = $options->nonce;
        $this->hotFile           = $options->hotFile;
        $this->manifestFilename  = $options->manifestFilename;
        $this->fixStylesImports  = $options->fixStylesImports;
        $this->fixScriptsImports = $options->fixScriptsImports;
        $this->usePreload        = $options->preload;
    }

    /** @noinspection HtmlUnknownTarget
     * @noinspection HtmlUnknownAttribute
     */
    private function makeTag(string $file, string $buildDirectory): string
    {
        $nonce = $this->nonce
            ? sprintf(' nonce="%s"', $this->nonce)
            : '';

        if ($this->isCss($file))
        {
            return sprintf('<link%s rel="stylesheet" href="%s" crossorigin>', $nonce, $this->resolvePath(
                $this->basePath,
                $buildDirectory,
                $file
            ));
        }

        return sprintf('<script%s type="module" src="%s" crossorigin></script>', $nonce, $this->resolvePath(
            $this->basePath,
            $buildDirectory,
            $file
        ));
    }

    /**
     * @noinspection HtmlUnknownTarget
     * @noinspection HtmlWrongAttributeValue
     * @noinspection HtmlUnknownAttribute
     */
    private function renderPreloadTag(string $file, string $buildDirectory): string
    {
        $clean = preg_split('/[?#]+/', $file)[0];
        $as    = match (explode('/', $this->mimeDetector->fromFileName($clean))[0])
        {
            'font'  => 'font',
            'image' => 'image',
            default => $this->isCss($file) ? 'style' : 'script',
        };

        return sprintf(
            '<link%s rel="%s" as="%s" href="%s" crossorigin>',
            $this->nonce ? sprintf(' nonce="%s"', $this->nonce) : '',
            'script' === $as ? 'modulepreload' : 'preload',
            $as,
            $this->resolvePath($this->basePath, $buildDirectory, $file)
        );
    }

    private function renderPrefetchTag(string $file, string $buildDirectory): string
    {
        return sprintf(
            '<link%s rel="prefetch" fetchpriority="low" as="script" href="%s" crossorigin>',
            $this->nonce ? sprintf(' nonce="%s"', $this->nonce) : '',
            $this->resolvePath($this->basePath, $buildDirectory, $file)
        );
    }

    private function isCss(string $subject): bool
    {
        $subject = preg_split('/[?#]+/', $subject)[0];
        return (bool) preg_match('#\.(css|less|sass|scss|styl|stylus|pcss|postcss)$#', $subject);
    }

    /**
     * @param string $buildDirectory
     *
     * @return array<string, ViteEntrypoint>
     */
    private function manifest(string $buildDirectory): array
    {
        $path                          = $this->resolvePath(
            $this->publicDir,
            $buildDirectory,
            $this->manifestFilename
        );

        if (array_key_exists($path, self::$manifests))
        {
            return self::$manifests[$path];
        }

        if ( ! is_file($path))
        {
            throw new ViteException("Manifest file \"{$path}\" does not exist.");
        }

        /** @var array<string, array> $array */
        $array                         = json_decode(file_get_contents($path), true);

        if ( ! is_array($array))
        {
            throw new ViteException("Manifest file \"{$path}\" does not contain valid JSON.");
        }

        return self::$manifests[$path] = array_map(function ($entry)
        {
            return ViteEntrypoint::make($entry);
        }, $array);
    }

    private function normalizePath(string $path): string
    {
        return rtrim(str_replace(DIRECTORY_SEPARATOR, '/', $path), '/');
    }

    private function resolvePath(string $base, string ...$segments): string
    {
        $full = $this->normalizePath($base);

        foreach ($segments as $segment)
        {
            $full .= '/' . ltrim($this->normalizePath($segment), '/');
        }
        return $full;
    }

    private function assertProjectRootValid(string $projectRoot)
    {
        $projectRoot = $this->normalizePath($projectRoot);

        if ( ! is_dir($projectRoot))
        {
            throw new ViteException('Project root "' . $projectRoot . '" does not exist.');
        }

        if ( ! is_file($this->resolvePath($projectRoot, 'composer.json')))
        {
            throw new ViteException('Project root "' . $projectRoot . '/composer.json" does not exist.');
        }

        if ( ! is_file($this->resolvePath($projectRoot, 'vite.config.ts'))
            && ! is_file($this->resolvePath($projectRoot, 'vite.config.js')))
        {
            throw new ViteException('Project root "' . $projectRoot . '/vite.config.ts" does not exist.');
        }
    }

    private function assertPublicDirValid(string $publicDir)
    {
        $publicDir = $this->normalizePath($publicDir);

        if ( ! is_dir($publicDir))
        {
            throw new ViteException('Public directory "' . $publicDir . '" does not exist.');
        }

        if ( ! is_file($this->resolvePath($publicDir, 'index.php')))
        {
            throw new ViteException('Public file "' . $publicDir . '/index.php" does not exist.');
        }
    }
}
