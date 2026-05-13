<?php

declare(strict_types=1);

namespace NGSOFT\Vite\Adapter;

readonly class ViteAdapterOptions
{
    public function __construct(
        public string $buildDirectory = 'build',
        public string $basePath = '',
        public bool $fixScriptsImports = false,
        public bool $fixStylesImports = false,
        public ?string $nonce = null,
        public ?string $hotFile = null,
        public string $manifestFilename = 'manifest.json',
        public bool $preload = true
    ) {}
}
