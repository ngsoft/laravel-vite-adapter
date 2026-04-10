<?php

declare(strict_types=1);

namespace NGSOFT\Vite\Adapter;

class ViteEntrypoint
{
    private string $file   = '';
    private string $name   = '';
    private string $src    = '';
    private bool $isEntry  = false;

    /** @var string[] */
    private array $css     = [];

    /** @var string[] */
    private array $imports = [];

    public static function make(array $data, ?self $instance = null): static
    {
        $instance ??= new static();

        foreach ($data as $property => $value)
        {
            if ( ! isset($value))
            {
                continue;
            }

            if (property_exists($instance, $property))
            {
                $instance->{$property} = $value;
            }
        }

        return $instance;
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getSrc(): ?string
    {
        return $this->src;
    }

    public function getIsEntry(): ?bool
    {
        return $this->isEntry;
    }

    /**
     * @return string[]
     */
    public function getCss(): array
    {
        return $this->css;
    }

    /**
     * @return string[]
     */
    public function getImports(): array
    {
        return $this->imports;
    }
}
