<?php

namespace Drupal\imgix\Plugin\ImageEffect;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageInterface;
use Drupal\image\ConfigurableImageEffectBase;

/**
 * @ImageEffect(
 *     id = "imgix_quality",
 *     label = @Translation("Change the quality"),
 *     description = @Translation("Controls the output quality of lossy file formats"),
 * )
 */
class QualityImageEffect extends ConfigurableImageEffectBase
{
    public function applyEffect(ImageInterface $image): bool
    {
        return $image->apply('quality', $this->configuration);
    }

    public function buildConfigurationForm(array $form, FormStateInterface $form_state): array
    {
        $form['quality'] = [
            '#type' => 'number',
            '#title' => 'Quality',
            '#description' => 'A number between 1 and 100.',
            '#default_value' => $this->configuration['quality'] ?? 80,
            '#required' => true,
            '#min' => 1,
            '#max' => 100,
        ];

        return $form;
    }

    public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void
    {
        parent::submitConfigurationForm($form, $form_state);

        $this->configuration['quality'] = (int) $form_state->getValue('quality');
    }

    public function getSummary(): array
    {
        $summary = parent::getSummary();
        $summary['#markup'] = $this->configuration['quality'];

        return $summary;
    }
}
