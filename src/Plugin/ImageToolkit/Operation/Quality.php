<?php

namespace Drupal\imgix\Plugin\ImageToolkit\Operation;

/**
 * @ImageToolkitOperation(
 *     id = "imgix_quality",
 *     toolkit = "imgix",
 *     operation = "quality",
 *     label = @Translation("Change the quality"),
 * )
 */
class Quality extends ImgixImageToolkitOperationBase
{
    protected function arguments(): array
    {
        return [
            'quality' => [
                'description' => 'The quality, a number between 1 and 100.',
            ],
        ];
    }

    protected function execute(array $arguments): bool
    {
        $this->getToolkit()->setParameter('q', $arguments['quality']);

        return true;
    }
}
