<?php

namespace Drupal\imgix\TwigExtension;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\file\FileInterface;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\imgix\ImgixManagerInterface;
use Drupal\media\MediaInterface;

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
        if ($file instanceof EntityReferenceItem) {
            $file = $file->get('entity')->getValue();
        }

        if ($file instanceof MediaInterface) {
            $source = $file->getSource();
            $field = $source->getConfiguration()['source_field'] ?? null;

            if ($file->get($field)->entity) {
                $file = $file->get($field)->entity;
            }
        }

        if ($file instanceof ImageItem) {
            $file = $file->get('entity')->getValue();
        }

        if (!$file instanceof FileInterface) {
            return '';
        }

        return $this->imgixManager->getImgixUrlByPreset($file, $preset);
    }
}
