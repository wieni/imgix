<?php

namespace Drupal\imgix\Plugin\ImageToolkit\Operation;

/**
 * https://docs.imgix.com/apis/rendering/size/fit#scale
 *
 * @ImageToolkitOperation(
 *   id = "imgix_scale",
 *   toolkit = "imgix",
 *   operation = "scale",
 *   label = @Translation("Scale"),
 *   description = @Translation("Scales an image while maintaining aspect ratio. The resulting image can be smaller for one or both target dimensions.")
 * )
 */
class Scale extends Resize
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
            throw new \InvalidArgumentException("At least one dimension ('width' or 'height') must be provided to the image 'scale' operation");
        }

        // Assure integers for all arguments.
        $arguments['width'] = (int) round($arguments['width']);
        $arguments['height'] = (int) round($arguments['height']);

        // Fail when width or height are 0 or negative.
        if ($arguments['width'] <= 0) {
            throw new \InvalidArgumentException("Invalid width ('{$arguments['width']}') specified for the image 'scale' operation");
        }
        if ($arguments['height'] <= 0) {
            throw new \InvalidArgumentException("Invalid height ('{$arguments['height']}') specified for the image 'scale' operation");
        }

        return $arguments;
    }

    protected function execute(array $arguments = []): bool
    {
        $this->getToolkit()
            ->setParameter('fit', $arguments['upscale'] ? 'clip' : 'max')
            ->setParameter('w', $arguments['width'])
            ->setParameter('h', $arguments['height']);

        return true;
    }
}
