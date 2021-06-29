<?php

namespace Drupal\imgix\Plugin\ImageToolkit\Operation;

/**
 * @see https://docs.imgix.com/apis/rendering/size/crop
 *
 * @ImageToolkitOperation(
 *   id = "imgix_crop",
 *   toolkit = "imgix",
 *   operation = "crop",
 *   label = @Translation("Crop"),
 *   description = @Translation("Crops an image to a rectangle specified by the given dimensions.")
 * )
 */
class Crop extends ImgixImageToolkitOperationBase
{
    protected function arguments(): array
    {
        return [
            'x' => [
                'description' => 'The starting x offset at which to start the crop, in pixels',
            ],
            'y' => [
                'description' => 'The starting y offset at which to start the crop, in pixels',
            ],
            'width' => [
                'description' => 'The width of the cropped area, in pixels',
                'required' => false,
                'default' => null,
            ],
            'height' => [
                'description' => 'The height of the cropped area, in pixels',
                'required' => false,
                'default' => null,
            ],
        ];
    }

    protected function validateArguments(array $arguments): array
    {
        // Assure at least one dimension.
        if (empty($arguments['width']) && empty($arguments['height'])) {
            throw new \InvalidArgumentException("At least one dimension ('width' or 'height') must be provided to the image 'crop' operation");
        }

        // Assure integers for all arguments.
        foreach (['x', 'y', 'width', 'height'] as $key) {
            $arguments[$key] = (int) round($arguments[$key]);
        }

        // Fail when width or height are 0 or negative.
        if ($arguments['width'] <= 0) {
            throw new \InvalidArgumentException("Invalid width ('{$arguments['width']}') specified for the image 'crop' operation");
        }
        if ($arguments['height'] <= 0) {
            throw new \InvalidArgumentException("Invalid height ('{$arguments['height']}') specified for the image 'crop' operation");
        }

        return $arguments;
    }

    protected function execute(array $arguments): bool
    {
        $this->getToolkit()->setParameter('rect', implode(',', [
            $arguments['x'],
            $arguments['y'],
            $arguments['width'],
            $arguments['height'],
        ]));

        return true;
    }
}
