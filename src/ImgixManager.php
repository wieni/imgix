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
    protected $http;
    
    protected $auth;
    protected $baseUri;
    
    /**
     * Constructor for ImgixManager.
     *
     * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $channelFactory
     * @param \Drupal\Core\Config\ConfigFactoryInterface $config
     * @param \Drupal\Core\File\FileSystemInterface $fileSystem
     * @param \GuzzleHttp\Client $http
     */
    public function __construct(
        LoggerChannelFactoryInterface $channelFactory,
        ConfigFactoryInterface $config,
        FileSystemInterface $fileSystem,
        Client $http
    ) {
        $this->logger = $channelFactory->get('vrt_imagestore');
        $this->config = $config;
        $this->fileSystem = $fileSystem;
        $this->http = $http;
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
     *
     * TODO: Do some logging.
     */
    public function getImgixUrl(FileInterface $file, $parameters, $json = false)
    {
        $settings = $this->getSettings();
        
        if (empty($settings['source_domain'])) {
            return '';
        }
        
        // Get the public path of the file.
        $path = file_create_url($file->getFileUri());
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
                // We liberally decide that you did NOT enter a S3 Bucket Prefix.
                // Now it's basically also the path.
                $buildUrl = explode("/", $pathInfo['path']);
                array_shift($buildUrl); // The "/".
                array_shift($buildUrl); // The bucket.
                
                $buildUrl = implode("/", $buildUrl);
                break;
        }
        
        if (!$buildUrl) {
            return '';
        }
        
        if ($json) {
            $parameters['fm'] = 'json';
        }
        
        $builder = new UrlBuilder($settings['source_domain']);
        
        if ($settings['https']) {
            $builder->setUseHttps(true);
        }
        if ($settings['secure_url_token']) {
            $builder->setSignKey($settings['secure_url_token']);
        }
        
        return $builder->createURL(
            $buildUrl,
            $parameters
        );
    }
    
    /**
     * @inheritdoc
     */
    public function getJson(FileInterface $file, $parameters)
    {
        $url = $this->getImgixUrl($file, $parameters, true);
        
        try {
            $response = $this
                ->http
                ->get($url,
                    array('headers' => array('Accept' => 'text/plain')));
            $data = (string)$response->getBody();
            if (empty($data)) {
                return false;
            }
        } catch (RequestException $e) {
            return false;
        }
        
        return json_decode($data);
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
    
    /**
     * @inheritdoc
     */
    public function getParamsFromPreset(string $preset)
    {
        $presets = $this->getPresets();
        
        if (isset($presets[$preset])) {
            return $this->explodePreset($presets[$preset]['query']);
        }
        return false;
    }
    
    private function getSettings()
    {
        return $this
            ->config
            ->get('imgix.settings')->getRawData();
    }
    
    private function explodePreset(string $preset)
    {
        $params = [];
        
        $exploded = explode('&', $preset);
        foreach ($exploded as $value) {
            $split = explode('=', $value);
            $params[$split[0]] = $split[1];
        }
        
        return $params;
    }
}
