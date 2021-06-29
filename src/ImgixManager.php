<?php

namespace Drupal\imgix;

use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\ImageStyleInterface;

/** @deprecated This service is now obsolete. */
class ImgixManager implements ImgixManagerInterface
{
    /** @var ImgixImageStyleFactoryInterface */
    protected $imageStyleFactory;

    public function __construct(
        ImgixImageStyleFactoryInterface $imageStyleFactory
    ) {
        $this->imageStyleFactory = $imageStyleFactory;
    }

    public function getImgixUrlByPreset(FileInterface $file, string $preset): ?string
    {
        if (!$imageStyle = ImageStyle::load($preset)) {
            return null;
        }

        return $this->getUrlByImageStyle($file, $imageStyle);
    }

    public function getImgixUrl(FileInterface $file, array $parameters): ?string
    {
        $imageStyle = $this->imageStyleFactory->getImageStyleByParameters($parameters);

        return $this->getUrlByImageStyle($file, $imageStyle);
    }

    protected function getUrlByImageStyle(FileInterface $file, ImageStyleInterface $imageStyle): ?string
    {
        $path = $file->getFileUri();

        if (!$imageStyle->supportsUri($path)) {
            return null;
        }

        return $imageStyle->buildUrl($path);
    }
}
