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
     * @param $json
     *  TRUE if you want to construct a json url
     *
     * @return string
     */
    public function getImgixUrl(FileInterface $file, $parameters, $json);
    
    /**
     * Default mapping types in Imgix.
     *
     * @return array
     */
    public function getMappingTypes();
    
    /**
     * Get params from a preset.
     *
     * @param string $preset
     */
    public function getParamsFromPreset(string $preset);
    
    /**
     * @param \Drupal\file\FileInterface $file
     *   File.
     * @param $parameters
     *   The parameters to pass on to imgix.
     *
     * @return string
     */
    public function getJson(FileInterface $file, $parameters);
}
