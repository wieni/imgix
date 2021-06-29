<?php

namespace Drupal\imgix\Plugin\ImageToolkit\Operation;

use Drupal\Core\ImageToolkit\ImageToolkitOperationBase;
use Drupal\imgix\Plugin\ImageToolkit\ImgixToolkit;

abstract class ImgixImageToolkitOperationBase extends ImageToolkitOperationBase
{
    /**
     * The correctly typed image toolkit for GD operations.
     */
    protected function getToolkit(): ImgixToolkit
    {
        return parent::getToolkit();
    }
}
