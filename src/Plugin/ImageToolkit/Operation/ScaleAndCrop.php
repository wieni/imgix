<?php

namespace Drupal\imgix\Plugin\ImageToolkit\Operation;

/**
 * @ImageToolkitOperation(
 *     id = "imgix_scale_and_crop",
 *     toolkit = "imgix",
 *     operation = "scale_and_crop",
 *     label = @Translation("Scale and crop"),
 *     description = @Translation("Scales an image to the exact width and height given. This plugin achieves the target aspect ratio by cropping the original image equally on both sides, or equally on the top and bottom. This function is useful to create uniform sized avatars from larger images.")
 * )
 */
class ScaleAndCrop extends Resize
{
    protected function arguments(): array
    {
        return [
            'width' => [
                'description' => 'The target width, in pixels. This value is omitted then the scaling will based only on the height value',
                'required' => false,
                'default' => null,
            ],
            'height' => [
                'description' => 'The target height, in pixels. This value is omitted then the scaling will based only on the width value',
                'required' => false,
                'default' => null,
            ],
            'anchor' => [
                'description' => 'The part of the image that will be retained during the crop',
            ],
            'upscale' => [
                'description' => 'Boolean indicating that files smaller than the dimensions will be scaled up. This generally results in a low quality image',
                'required' => false,
                'default' => false,
            ],
        ];
    }

    protected function validateArguments(array $arguments): array
    {
        // Assure at least one dimension.
        if (empty($arguments['width']) && empty($arguments['height'])) {
            throw new \InvalidArgumentException("At least one dimension ('width' or 'height') must be provided to the image 'scale_and_crop' operation");
        }

        if (!empty($arguments['width'])) {
            $arguments['width'] = (int) round($arguments['width']);

            if ($arguments['width'] <= 0) {
                throw new \InvalidArgumentException(sprintf("Invalid width ('%s') specified for the image 'scale_and_crop' operation", $arguments['width']));
            }
        }

        if (!empty($arguments['height'])) {
            $arguments['height'] = (int) round($arguments['height']);

            if ($arguments['height'] <= 0) {
                throw new \InvalidArgumentException(sprintf("Invalid height ('%s') specified for the image 'scale_and_crop' operation", $arguments['height']));
            }
        }

        return $arguments;
    }

    protected function execute(array $arguments = []): bool
    {
        $this->getToolkit()
            ->setParameter('fit', $arguments['upscale'] ? 'crop' : 'min')
            ->setParameter('w', $arguments['width'])
            ->setParameter('h', $arguments['height'])
            ->setParameter('crop', str_replace('-', ',', $arguments['anchor']));

        return true;
    }
}
