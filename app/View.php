<?php

namespace App;

class View
{
    private string $templatePath;
    private array $properties;

    public function __construct(string $templatePath, array $properties = [])
    {
        $this->templatePath = $templatePath;
        $this->properties = $properties;
    }

    public function getTemplatePath(): string
    {
        return $this->templatePath;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }
}