<?php

namespace Drupal\imgix\Plugin\ImageToolkit\Operation;

use Drupal\Component\Utility\Color;

/**
 * @see https://docs.imgix.com/apis/rendering/fill/bg
 * @see https://docs.imgix.com/apis/rendering/rotation/rot
 *
 * @ImageToolkitOperation(
 *   id = "imgix_rotate",
 *   toolkit = "imgix",
 *   operation = "rotate",
 *   label = @Translation("Rotate"),
 *   description = @Translation("Rotates an image by the given number of degrees.")
 * )
 */
class Rotate extends ImgixImageToolkitOperationBase
{
    protected function arguments(): array
    {
        return [
            'degrees' => [
                'description' => 'The number of (clockwise) degrees to rotate the image',
            ],
            'background' => [
                'description' => "A string specifying the hexadecimal color code to use as background for the uncovered area of the image after the rotation. E.g. '#000000' for black, '#ff00ff' for magenta, and '#ffffff' for white. For images that support transparency, this will default to transparent white",
                'required' => FALSE,
                'default' => NULL,
            ],
        ];
    }

    protected function validateArguments(array $arguments): array
    {
        // Validate or set background color argument.
        if (!empty($arguments['background'])) {
            // Validate the background color: Color::hexToRgb does so for us.
            Color::hexToRgb($arguments['background']);
        } else {
            // Background color is not specified: use transparent white as background.
            $arguments['background'] = ['red' => 255, 'green' => 255, 'blue' => 255, 'alpha' => 127];
        }

        return $arguments;
    }

    protected function execute(array $arguments): bool
    {
        $this->getToolkit()
            ->setParameter('rot', $arguments['degrees'])
            ->setParameter('bg', $arguments['background']);

        return true;
    }
}
