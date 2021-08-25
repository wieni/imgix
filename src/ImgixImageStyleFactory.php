<?php

namespace Drupal\imgix;

use Drupal\image\Entity\ImageStyle;
use Drupal\image\ImageStyleInterface;
use Drupal\imgix\Plugin\ImageToolkit\Operation\Convert;
use Drupal\imgix\Plugin\ImageToolkit\Operation\Crop;
use Drupal\imgix\Plugin\ImageToolkit\Operation\Desaturate;
use Drupal\imgix\Plugin\ImageToolkit\Operation\Quality;
use Drupal\imgix\Plugin\ImageToolkit\Operation\Resize;
use Drupal\imgix\Plugin\ImageToolkit\Operation\Rotate;
use Drupal\imgix\Plugin\ImageToolkit\Operation\Scale;
use Drupal\imgix\Plugin\ImageToolkit\Operation\ScaleAndCrop;

class ImgixImageStyleFactory implements ImgixImageStyleFactoryInterface
{
    public function getImageStyleByParameters(array $parameters): ImageStyleInterface
    {
        /** @var ImageStyle $imageStyle */
        $imageStyle = ImageStyle::create();

        /** @see Convert */
        if (isset($parameters['fm'])) {
            $imageStyle->addImageEffect([
                'id' => 'image_convert',
                'data' => [
                    'extension' => $parameters['fm'],
                ],
            ]);

            unset($parameters['fm']);
        }

        /** @see Crop */
        if (isset($parameters['rect'])) {
            [$x, $y, $width, $height] = explode(',', $parameters['rect']);

            $imageStyle->addImageEffect([
                'id' => 'image_crop',
                'data' => [
                    'x' => (int) $x,
                    'y' => (int) $y,
                    'width' => (int) $width,
                    'height' => (int) $height,
                ],
            ]);

            unset($parameters['rect']);
        }

        /** @see Desaturate */
        if (isset($parameters['sat']) && $parameters['sat'] === -100) {
            $imageStyle->addImageEffect([
                'id' => 'image_desaturate',
            ]);

            unset($parameters['sat']);
        }

        /** @see Quality */
        if (isset($parameters['q'])) {
            $imageStyle->addImageEffect([
                'id' => 'imgix_quality',
                'data' => [
                    'quality' => (int) $parameters['q'],
                ],
            ]);

            unset($parameters['q']);
        }

        /** @see Resize */
        if (isset($parameters['fit']) && $parameters['fit'] === 'scale') {
            $imageStyle->addImageEffect([
                'id' => 'image_resize',
                'data' => [
                    'width' => (int) $parameters['w'],
                    'height' => (int) $parameters['h'],
                ],
            ]);

            unset($parameters['fit'], $parameters['w'], $parameters['h']);
        }

        /** @see Rotate */
        if (isset($parameters['rot'])) {
            $imageStyle->addImageEffect([
                'id' => 'image_rotate',
                'data' => [
                    'degrees' => (int) $parameters['rot'],
                    'background' => $parameters['bg'] ?? null,
                ],
            ]);

            unset($parameters['rot'], $parameters['bg']);
        }

        /** @see Scale */
        if (isset($parameters['fit']) && in_array($parameters['fit'], ['clip', 'max'])) {
            $imageStyle->addImageEffect([
                'id' => 'image_scale',
                'data' => [
                    'width' => $parameters['w'] ?? null,
                    'height' => $parameters['h'] ?? null,
                    'upscale' => $parameters['fit'] === 'clip',
                ],
            ]);

            unset($parameters['fit'], $parameters['w'], $parameters['h']);
        }

        /** @see ScaleAndCrop */
        if (isset($parameters['fit']) && in_array($parameters['fit'], ['crop', 'min'])) {
            $imageStyle->addImageEffect([
                'id' => 'image_scale_and_crop',
                'data' => [
                    'width' => $parameters['w'] ?? null,
                    'height' => $parameters['h'] ?? null,
                    'anchor' => isset($parameters['crop'])
                        ? str_replace(',', '-', $parameters['crop'])
                        : null,
                    'upscale' => $parameters['fit'] === 'crop',
                ],
            ]);

            unset($parameters['fit'], $parameters['w'], $parameters['h'], $parameters['crop']);
        }

        foreach ($parameters as $key => $value) {
            $imageStyle->addImageEffect([
                'id' => 'imgix_param',
                'data' => [
                    'key' => $key,
                    'value' => $value,
                ],
            ]);
        }

        // Set weights
        $effects = $imageStyle->getEffects();
        $weight = -10;

        foreach ($effects as $effect) {
            $effect->setWeight($weight);
            $weight++;
        }

        return $imageStyle;
    }
}
