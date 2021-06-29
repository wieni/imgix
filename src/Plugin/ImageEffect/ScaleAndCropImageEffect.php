<?php

namespace Drupal\imgix\Plugin\ImageEffect;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageInterface;
use Drupal\image\Plugin\ImageEffect\ScaleAndCropImageEffect as ScaleAndCropImageEffectBase;
use Drupal\imgix\Plugin\ImageToolkit\ImgixToolkit;

class ScaleAndCropImageEffect extends ScaleAndCropImageEffectBase
{
    public function applyEffect(ImageInterface $image): bool
    {
        if (!$image->getToolkit() instanceof ImgixToolkit) {
            return parent::applyEffect($image);
        }

        $result = $image->apply('scale_and_crop', [
            'width' => $this->configuration['width'],
            'height' => $this->configuration['height'],
            'anchor' => $this->configuration['anchor'],
            'upscale' => $this->configuration['upscale'],
        ]);

        if (!$result) {
            $this->logger->error('Image scale and crop failed using the %toolkit toolkit on %path (%mimetype, %dimensions)', ['%toolkit' => $image->getToolkitId(), '%path' => $image->getSource(), '%mimetype' => $image->getMimeType(), '%dimensions' => $image->getWidth() . 'x' . $image->getHeight()]);
            return false;
        }

        return true;
    }

    public function defaultConfiguration(): array
    {
        $configuration = parent::defaultConfiguration();
        $configuration['upscale'] = false;

        return $configuration;
    }

    public function buildConfigurationForm(array $form, FormStateInterface $form_state): array
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        /** @see https://www.drupal.org/project/drupal/issues/872206 */
        $form['upscale'] = [
            '#type' => 'checkbox',
            '#default_value' => $this->configuration['upscale'],
            '#title' => $this->t('Allow Upscaling'),
            '#description' => $this->t('Let scale make images larger than their original size.'),
        ];

        return $form;
    }

    public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void
    {
        parent::submitConfigurationForm($form, $form_state);

        $this->configuration['upscale'] = $form_state->getValue('upscale');
    }
}
