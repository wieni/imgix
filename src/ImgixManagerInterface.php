<?php

namespace Drupal\imgix;

use Drupal\file\FileInterface;

/** @deprecated This interface is now obsolete. */
interface ImgixManagerInterface
{
    /** @deprecated Use ImageStyleInterface::buildUrl instead */
    public function getImgixUrlByPreset(FileInterface $file, string $preset): ?string;

    /** @deprecated without replacement */
    public function getImgixUrl(FileInterface $file, array $parameters): ?string;
}
