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
            )
        ];
    }
    
    public function getFunctions()
    {
        return [
            'imgix' => new \Twig_SimpleFunction(
                'imgix',
                [$this, 'imgix']
            )
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
        if ($file instanceof ImgixFieldType) {
            $file = $file->getFile();
        }
        
        $presets = $this->imgixManager->getPresets();
    
        $params = [];
        if (isset($presets[$presetSetting])) {
            $tmp = explode('&', $presets[$presetSetting]['query']);
            foreach ($tmp as $value) {
                $tmp2 = explode('=', $value);
                $params[$tmp2[0]] = $tmp2[1];
            }
    
            return $this->imgixManager->getImgixUrl($file, $params);
        }
        
        return 'No valid preset found';
    }
    
}
