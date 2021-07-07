<?php

namespace Drupal\imgix\Plugin\ImageToolkit\Operation;

/**
 * @ImageToolkitOperation(
 *   id = "imgix_param",
 *   toolkit = "imgix",
 *   operation = "imgix_param",
 *   label = @Translation("Apply Imgix parameter"),
 *   description = @Translation("Applies a certain Imgix parameter."),
 * )
 */
class ImgixParam extends ImgixImageToolkitOperationBase
{
    protected function arguments(): array
    {
        return [
            'key' => [
                'description' => 'Parameter',
            ],
            'value' => [
                'description' => 'Value',
            ],
        ];
    }

    protected function execute(array $arguments): bool
    {
        $this->getToolkit()->setParameter($arguments['key'], $arguments['value']);

        return true;
    }
}
