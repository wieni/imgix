<?php

namespace Drupal\imgix;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\file\FileInterface;
use Imgix\UrlBuilder;

class ImgixManager implements ImgixManagerInterface
{
    public const SOURCE_S3 = 's3';
    public const SOURCE_FOLDER = 'webfolder';
    public const SOURCE_PROXY = 'webproxy';

    /** @var LoggerChannelInterface */
    protected $logger;
    /** @var ConfigFactoryInterface */
    protected $config;
    /** @var FileSystemInterface */
    protected $fileSystem;

    public function __construct(
        LoggerChannelInterface $logger,
        ConfigFactoryInterface $config,
        FileSystemInterface $fileSystem
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->fileSystem = $fileSystem;
    }

    public function getPresets(): array
    {
        return $this->config
            ->get('imgix.presets')
            ->get('presets');
    }

    public function getImgixUrlByPreset(FileInterface $file, string $preset): ?string
    {
        $presets = $this->getPresets();

        $params = [];
        if (!isset($presets[$preset])) {
            throw new \InvalidArgumentException(
                sprintf('No such preset: \'%s\'.', $preset)
            );
        }

        $queryParams = explode('&', $presets[$preset]['query']);
        foreach ($queryParams as $value) {
            $keyValue = explode('=', $value);
            $params[$keyValue[0]] = $keyValue[1];
        }

        return $this->getImgixUrl($file, $params);
    }

    public function getImgixUrl(FileInterface $file, array $parameters): ?string
    {
        $settings = $this->getSettings();

        if (empty($settings['source_domain'])) {
            return null;
        }

        // Get the public path of the file.
        $path = $file->createFileUrl();
        $pathInfo = parse_url($path);

        $buildUrl = false;

        switch ($settings['mapping_type']) {
            case self::SOURCE_FOLDER:
                // We need the full path after the domain.
                $buildUrl = $pathInfo['path'];
                break;
            case self::SOURCE_PROXY:
                // We just need the full path.
                $buildUrl = $path;
                break;
            case self::SOURCE_S3:
                $hasPrefix = $settings['s3_has_prefix'] ?? false;

                $buildUrl = explode('/', $pathInfo['path']);
                array_shift($buildUrl); // The "/"

                if (!$hasPrefix) {
                    array_shift($buildUrl); // The bucket.
                }

                $buildUrl = implode('/', $buildUrl);
                break;
        }

        if (!$buildUrl) {
            return null;
        }

        $builder = new UrlBuilder($settings['source_domain']);

        if ($settings['https']) {
            $builder->setUseHttps(true);
        }
        if ($settings['secure_url_token']) {
            $builder->setSignKey($settings['secure_url_token']);
        }

        $url = $builder->createURL(
            $buildUrl,
            $parameters
        );

        if ($settings['external_cdn'] !== '') {
            $url = str_replace($settings['source_domain'], $settings['external_cdn'], $url);
        }

        return $url;
    }

    public function getMappingTypes(): array
    {
        return [
            self::SOURCE_FOLDER => 'Web Folder',
            self::SOURCE_PROXY => 'Web Proxy',
            self::SOURCE_S3 => 'Amazon S3',
        ];
    }

    protected function getSettings(): array
    {
        return $this->config->get('imgix.settings')->get() ?? [];
    }
}
