<?php

namespace Drupal\imgix\TwigExtension;

use Drupal\imgix\ImgixManagerInterface;
use Drupal\imgix\Plugin\Field\FieldType\ImgixFieldType;

class ImgixExtension extends \Twig_Extension
{
    protected $imgixManager;
    
    public function __construct(ImgixManagerInterface $imgixManager)
    {
        $this->imgixManager = $imgixManager;
    }
    
    public function getFilters()
    {
        return [
            'imgix' => new \Twig_SimpleFilter(
                'imgix',
                [$this, 'imgix']
            ),
        ];
    }
    
    public function getFunctions()
    {
        return [
            'imgix' => new \Twig_SimpleFunction(
                'imgix',
                [$this, 'imgix']
            ),
            'imgix_width' => new \Twig_SimpleFunction(
                'imgix_width',
                [$this, 'imgixWidth']
            ),
            'imgix_height' => new \Twig_SimpleFunction(
                'imgix_height',
                [$this, 'imgixHeight']
            ),
        ];
    }
    
    /**
     * This is the same name we used on the services.yml file
     *
     * @return string
     */
    public function getName()
    {
        return "imgix.twig_extension";
    }
    
    public function imgix($file, $presetSetting)
    {
        if (!$file) {
            return "https://placeholdit.imgix.net/~text?txtsize=33&txt=no_image&w=200&h=200";
        }
        
        if ($file instanceof ImgixFieldType) {
            $file = $file->getFile();
        }
        
        if ($url = $this->imgixManager->getImgixUrl($file, $this->imgixManager->getParamsFromPreset($presetSetting))) {
            return $url;
        }
        
        return 'No valid preset found';
    }
    
    public function imgixWidth(ImgixFieldType $file, $preset)
    {
        $json = $this->imgixManager->getJson($file->getFile(), $this->imgixManager->getParamsFromPreset($preset));
        if ($json && $json->PixelWidth) {
            return $json->PixelWidth;
        }
    }
    
    public function imgixHeight(ImgixFieldType $file, $preset)
    {
        $json = $this->imgixManager->getJson($file->getFile(), $this->imgixManager->getParamsFromPreset($preset));
        if ($json && $json->PixelHeight) {
            return $json->PixelHeight;
        }
    }
    
}
