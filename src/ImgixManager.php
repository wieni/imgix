<?php

namespace Drupal\imgix;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\file\FileInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ClientException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;

use Imgix\UrlBuilder;

/**
 * Class ImgixManager.
 *
 * @package Drupal\imgix
 */
class ImgixManager implements ImgixManagerInterface
{
    const SOURCE_S3 = 's3';
    const SOURCE_FOLDER = 'webfolder';
    const SOURCE_PROXY = 'webproxy';

    protected $logger;
    protected $config;
    protected $fileSystem;

    protected $auth;
    protected $baseUri;

    /**
     * Constructor for ImgixManager.
     *
     * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $channelFactory
     * @param \Drupal\Core\Config\ConfigFactoryInterface        $config
     * @param \Drupal\Core\File\FileSystemInterface             $fileSystem
     */
    public function __construct(
        LoggerChannelFactoryInterface $channelFactory,
        ConfigFactoryInterface $config,
        FileSystemInterface $fileSystem
    ) {
        $this->logger = $channelFactory->get('imgix');
        $this->config = $config;
        $this->fileSystem = $fileSystem;
    }

    /**
     * @inheritdoc
     */
    public function getPresets()
    {
        return $this
            ->config
            ->get('imgix.presets')
            ->get('presets');
    }

    /**
     * @inheritdoc
     */
    public function getImgixUrlByPreset(FileInterface $file, $preset)
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

    /**
     * @inheritdoc
     *
     * TODO: Do some logging.
     */
    public function getImgixUrl(FileInterface $file, $parameters)
    {
        $settings = $this->getSettings();

        if (empty($settings['source_domain'])) {
            return '';
        }

        // Get the public path of the file.
        $path = $file->url();
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
                $hasPrefix = isset($settings['s3_has_prefix']) ? $settings['s3_has_prefix'] : false;

                $buildUrl = explode("/", $pathInfo['path']);
                array_shift($buildUrl); // The "/"

                if (!$hasPrefix) {
                    array_shift($buildUrl); // The bucket.
                }

                $buildUrl = implode("/", $buildUrl);
                break;
        }

        if (!$buildUrl) {
            return '';
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

    /**
     * @inheritdoc
     */
    public function getMappingTypes()
    {
        return [
            self::SOURCE_FOLDER => 'Web Folder',
            self::SOURCE_PROXY => 'Web Proxy',
            self::SOURCE_S3 => 'Amazon S3',
        ];
    }

    private function getSettings()
    {
        return $this
            ->config
            ->get('imgix.settings')->get();
    }
}
