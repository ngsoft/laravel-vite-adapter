<?php

namespace NGSOFT\Vite\Adapter;

use League\MimeTypeDetection\FinfoMimeTypeDetector;

class MimeDetector
{
    private FinfoMimeTypeDetector $mime;

    private string $defaultValue = 'application/octet-stream';

    public function __construct()
    {
        $this->mime = new FinfoMimeTypeDetector();
    }

    public function fromFileName(string $file): string
    {
        return $this->mime->detectMimeTypeFromPath($file) ?? $this->defaultValue;
    }

    public function fromContent(string $content): string
    {
        return $this->mime->detectMimeTypeFromBuffer($content) ?? $this->defaultValue;
    }
}
