<?php

namespace Drupal\imgix;

use Drupal\image\ImageStyleInterface;

interface ImgixImageStyleFactoryInterface
{
    public function getImageStyleByParameters(array $parameters): ImageStyleInterface;
}
