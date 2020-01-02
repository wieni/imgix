<?php

namespace Drupal\imgix;

use Drupal\file\FileInterface;

interface ImgixManagerInterface
{
    /**
     * Get all Imgix presets.
     *
     * @return array
     */
    public function getPresets();

    /**
     * @param FileInterface $file
     *   File.
     * @param string $preset
     *   The preset to render the image in
     *
     * @return string
     */
    public function getImgixUrlByPreset(FileInterface $file, $preset);

    /**
     * @param \Drupal\file\FileInterface $file
     *   File.
     * @param array $parameters
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
