<?php

namespace Drupal\imgix\Plugin\ImageToolkit\Operation;

/**
 * @see https://docs.imgix.com/apis/rendering/size/fit
 *
 * @ImageToolkitOperation(
 *   id = "imgix_resize",
 *   toolkit = "imgix",
 *   operation = "resize",
 *   label = @Translation("Resize"),
 *   description = @Translation("Resizes an image to the given dimensions (ignoring aspect ratio).")
 * )
 */
class Resize extends ImgixImageToolkitOperationBase
{
    protected function arguments(): array
    {
        return [
            'width' => [
                'description' => 'The new width of the resized image, in pixels',
            ],
            'height' => [
                'description' => 'The new height of the resized image, in pixels',
            ],
        ];
    }

    protected function validateArguments(array $arguments): array
    {
        // Assure integers for all arguments.
        $arguments['width'] = (int) round($arguments['width']);
        $arguments['height'] = (int) round($arguments['height']);

        // Fail when width or height are 0 or negative.
        if ($arguments['width'] <= 0) {
            throw new \InvalidArgumentException("Invalid width ('{$arguments['width']}') specified for the image 'resize' operation");
        }
        if ($arguments['height'] <= 0) {
            throw new \InvalidArgumentException("Invalid height ('{$arguments['height']}') specified for the image 'resize' operation");
        }

        return $arguments;
    }

    protected function execute(array $arguments = []): bool
    {
        $this->getToolkit()
            ->setParameter('fit', 'scale')
            ->setParameter('w', $arguments['width'])
            ->setParameter('h', $arguments['height']);

        return true;
    }
}
