<?php

namespace Drupal\imgix\TwigExtension;

use Drupal\imgix\ImgixManagerInterface;

class ImgixExtension extends \Twig_Extension
{
    protected $imgixManager;
    
    public function __construct(ImgixManagerInterface $imgixManager)
    {
        $this->imgixManager = $imgixManager;
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
    
    public function imgix($url, $profile)
    {
        return $this->imgixManager->renderProfile($url, $profile);
    }
    
}
