<?php

namespace Drupal\imgix\Plugin\ImageToolkit;

use Drupal\Core\ImageToolkit\ImageToolkitInterface;

interface ImgixToolkitInterface extends ImageToolkitInterface
{
    public const SOURCE_S3 = 's3';
    public const SOURCE_GCS = 'gcs';
    public const SOURCE_FOLDER = 'webfolder';
    public const SOURCE_PROXY = 'webproxy';

    public function getParameter(string $key);

    public function setParameter(string $key, string $value);

    public function unsetParameter(string $key);

    public function mergeParameters(array $params);

    public function getParameters(): array;

    public function getSourceDomain(): ?string;

    public function getExternalCdnDomain(): ?string;

    public function getMappingType(): ?string;

    public function getMappingTypes(): array;

    /** @deprecated use getPathPrefix instead. */
    public function hasS3Prefix(): bool;

    public function getPathPrefix(): ?string;

    public function usesHttps(): bool;

    public function getSecureUrlToken(): ?string;

    /** @param string|null $destination */
    public function useFallbackToolkit($destination = null): bool;
}
