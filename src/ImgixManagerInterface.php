<?php

namespace Drupal\imgix;

use Drupal\file\FileInterface;

interface ImgixManagerInterface
{
    public function getPresets(): array;

    public function getImgixUrlByPreset(FileInterface $file, string $preset): ?string;

    public function getImgixUrl(FileInterface $file, array $parameters): ?string;

    public function getMappingTypes(): array;
}
