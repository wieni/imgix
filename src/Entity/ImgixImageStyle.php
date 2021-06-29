<?php

namespace Drupal\imgix\Entity;

use Drupal\Core\Image\ImageInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\imgix\Plugin\ImageToolkit\ImgixToolkit;
use Drupal\imgix\Plugin\ImageToolkit\ImgixToolkitInterface;
use Imgix\UrlBuilder;

class ImgixImageStyle extends ImageStyle
{
    public function buildUri($uri)
    {
        // We don't need to generate derivatives using this image toolkit,
        // so return the original url

        return $uri;
    }

    public function buildUrl($uri, $clean_urls = NULL)
    {
        /** @var ImageInterface $image */
        $image = \Drupal::service('image.factory')->get($uri);
        $toolkit = $image->getToolkit();

        if (!$toolkit instanceof ImgixToolkit || $toolkit->useFallbackToolkit($uri)) {
            return parent::buildUrl($uri, $clean_urls);
        }

        if (!$sourceDomain = $toolkit->getSourceDomain()) {
            return null;
        }

        $parts = parse_url($uri);
        $path = sprintf('/%s%s', $parts['host'], $parts['path']);
        $buildUrl = null;

        switch ($toolkit->getMappingType()) {
            case ImgixToolkitInterface::SOURCE_FOLDER:
                // We need the full path after the domain.
                $buildUrl = $parts['path'];
                break;
            case ImgixToolkitInterface::SOURCE_PROXY:
                // We just need the full path.
                $buildUrl = $path;
                break;
            case ImgixToolkitInterface::SOURCE_S3:
                $buildUrl = explode('/', $path);
                array_shift($buildUrl); // The "/"

                if (!$toolkit->hasS3Prefix()) {
                    array_shift($buildUrl); // The bucket.
                }

                $buildUrl = implode('/', $buildUrl);
                break;
        }

        if (!$buildUrl) {
            return null;
        }

        $builder = new UrlBuilder($sourceDomain);

        if ($toolkit->usesHttps()) {
            $builder->setUseHttps(true);
        }

        if ($token = $toolkit->getSecureUrlToken()) {
            $builder->setSignKey($token);
        }

        foreach ($this->getEffects() as $effect) {
            $effect->applyEffect($image);
        }

        $url = $builder->createURL(
            $buildUrl,
            $toolkit->getParameters()
        );

        if ($cdnDomain = $toolkit->getExternalCdnDomain()) {
            $url = str_replace($sourceDomain, $cdnDomain, $url);
        }

        return $url;
    }
}
