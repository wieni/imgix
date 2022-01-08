<?php

namespace Drupal\imgix\Plugin\ImageToolkit\Operation;

/**
 * @see https://docs.imgix.com/apis/rendering/format/fm
 *
 * @ImageToolkitOperation(
 *     id = "imgix_convert",
 *     toolkit = "imgix",
 *     operation = "convert",
 *     label = @Translation("Convert"),
 *     description = @Translation("Instructs the toolkit to save the image with a specified extension.")
 * )
 */
class Convert extends ImgixImageToolkitOperationBase
{
    protected function arguments(): array
    {
        return [
            'extension' => [
                'description' => 'The new extension of the converted image',
            ],
        ];
    }

    protected function validateArguments(array $arguments): array
    {
        $supportedExtensions = $this->getToolkit()->getSupportedExtensions();

        if (!in_array($arguments['extension'], $supportedExtensions, true)) {
            throw new \InvalidArgumentException(sprintf('Invalid extension (%s) specified for the image \'convert\' operation', $arguments['extension']));
        }

        return $arguments;
    }

    protected function execute(array $arguments): bool
    {
        $this->getToolkit()->setParameter('fm', $arguments['extension']);

        return true;
    }
}
