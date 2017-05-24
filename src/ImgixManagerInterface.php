<?php

namespace Drupal\imgix;

use Drupal\file\FileInterface;

/**
 * Class ImgixManagerInterface.
 *
 * @package Drupal\imgix
 */
interface ImgixManagerInterface
{
    /**
     * Get all Imgix presets.
     *
     * @return array
     */
    public function getPresets();

    /**
     * @param \Drupal\file\FileInterface $file
     *   File.
     * @param $parameters
     *   The parameters to pass on to imgix.
     *
     * @return string
     */
    public function getImgixUrl(FileInterface $file, $parameters);

    /**
     * Default mapping types in Imgix.
     *
     * @return array
     */
    public function getMappingTypes();
}
