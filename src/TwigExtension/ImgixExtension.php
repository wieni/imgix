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

    public function imgix($file, $preset)
    {
        if (!$file) {
            return "https://placeholdit.imgix.net/~text?txtsize=33&txt=no_image&w=200&h=200";
        }

        if ($file instanceof ImgixFieldType) {
            $file = $file->getFile();
        }

        return $this->imgixManager->getImgixUrlByPreset($file, $preset);
    }
}
