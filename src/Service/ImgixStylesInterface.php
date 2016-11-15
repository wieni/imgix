<?php

namespace Drupal\imgix\Service;


/**
 * Provides an interface for creating Imgix Styles.
 */
interface ImgixStylesInterface {

  /**
   * Get all imgix presets.
   *
   * @return array
   *   List of all available presets.
   */
  public function getPresets();

  /**
   * Loads an Image modules image style.
   *
   * @param string $id
   *   Machine name of the image style to load.
   */
  public function loadStyle($id);

  /**
   * Loads an Image modules image.
   *
   * @param string $uri
   *   The image uri to load.
   */
  public function loadImage($uri);

  /**
   * Returns the URL of this image derivative for an original image path or URI.
   *
   * @return string
   *   The absolute URL where a style image can be downloaded.
   *
   * @see \Drupal\image\Controller\ImageStyleDownloadController::deliver()
   * @see file_url_transform_relative()
   */
  public function buildUrl();

  /**
   * Returns the URL of this image derivativr from the original path.
   *
   * @param string $path
   *   The path of the image.
   * @param string $query
   *   The parameters to pass to imgix (w,h,fit,...).
   *
   * @return string
   *   The absolute imgix url.
   */
  public function buildRawUrl($path, $query);

  /**
   * Determines the dimensions of this image style.
   *
   * Stores the dimensions of this image style into $dimensions associative
   * array. Implementations have to provide at least values to next keys:
   * - width: Integer with the derivative image width.
   * - height: Integer with the derivative image height.
   *
   * @param array $dimensions
   *   Associative array passed by reference. Implementations have to store the
   *   resulting width and height, in pixels.
   *
   * @see ImageEffectInterface::transformDimensions
   */
  public function transformDimensions(array &$dimensions);

}
