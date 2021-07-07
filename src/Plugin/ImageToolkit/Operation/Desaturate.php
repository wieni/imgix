<?php

namespace Drupal\imgix\Plugin\ImageToolkit\Operation;

/**
 * @see https://docs.imgix.com/apis/rendering/adjustment/sat
 *
 * @ImageToolkitOperation(
 *   id = "imgix_desaturate",
 *   toolkit = "imgix",
 *   operation = "desaturate",
 *   label = @Translation("Desaturate"),
 *   description = @Translation("Converts an image to grayscale.")
 * )
 */
class Desaturate extends ImgixImageToolkitOperationBase
{
    protected function arguments(): array
    {
        // This operation does not use any parameters.
        return [];
    }

    protected function execute(array $arguments)
    {
        $this->getToolkit()->setParameter('sat', -100);
    }
}
