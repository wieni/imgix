<?php

namespace Drupal\imgix\Plugin\ImageToolkit\Operation;

/**
 * @see https://docs.imgix.com/apis/rendering/size/fit
 *
 * @ImageToolkitOperation(
 *     id = "imgix_resize",
 *     toolkit = "imgix",
 *     operation = "resize",
 *     label = @Translation("Resize"),
 *     description = @Translation("Resizes an image to the given dimensions (ignoring aspect ratio).")
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
            throw new \InvalidArgumentException(sprintf('Invalid width (\'%s\') specified for the image \'resize\' operation', $arguments['width']));
        }
        if ($arguments['height'] <= 0) {
            throw new \InvalidArgumentException(sprintf('Invalid height (\'%d\') specified for the image \'resize\' operation', $arguments['height']));
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
