<?php

namespace Drupal\imgix\TwigExtension;

use Drupal\file\FileInterface;
use Drupal\imgix\ImgixManagerInterface;
use Drupal\imgix\Plugin\Field\FieldType\DoNotUseImgixFieldType;

class ImgixExtension extends \Twig_Extension
{
    /** @var ImgixManagerInterface */
    protected $imgixManager;

    public function __construct(ImgixManagerInterface $imgixManager)
    {
        $this->imgixManager = $imgixManager;
    }

    public function getFilters()
    {
        return [
            'imgix' => new \Twig_SimpleFilter('imgix', [$this, 'imgix']),
        ];
    }

    public function getFunctions()
    {
        return [
            'imgix' => new \Twig_SimpleFunction('imgix', [$this, 'imgix']),
        ];
    }

    public function getName()
    {
        return 'imgix.twig_extension';
    }

    public function imgix($file, $preset)
    {
        if ($file instanceof DoNotUseImgixFieldType) {
            $file = $file->getFile();
        }

        if (!$file instanceof FileInterface) {
            return '';
        }

        return $this->imgixManager->getImgixUrlByPreset($file, $preset);
    }
}
