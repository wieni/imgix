<?php

namespace Drupal\imgix\Plugin\ImageEffect;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageInterface;
use Drupal\image\ConfigurableImageEffectBase;

/**
 * @ImageEffect(
 *   id = "imgix_param",
 *   label = @Translation("Apply Imgix parameter"),
 *   description = @Translation("Applies a certain Imgix parameter."),
 * )
 */
class ImgixParamImageEffect extends ConfigurableImageEffectBase
{
    public function applyEffect(ImageInterface $image)
    {
        return $image->apply('imgix_param', $this->configuration);
    }

    public function buildConfigurationForm(array $form, FormStateInterface $form_state): array
    {
        $form['key'] = [
            '#type' => 'textfield',
            '#title' => 'Parameter',
            '#default_value' => $this->configuration['key'] ?? null,
            '#required' => true,
        ];

        $form['value'] = [
            '#type' => 'textfield',
            '#title' => 'Value',
            '#default_value' => $this->configuration['value'] ?? null,
            '#required' => true,
        ];

        return $form;
    }

    public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void
    {
        parent::submitConfigurationForm($form, $form_state);

        $this->configuration['key'] = $form_state->getValue('key');
        $this->configuration['value'] = $form_state->getValue('value');
    }

    public function getSummary(): array
    {
        $summary = parent::getSummary();
        $summary['#markup'] = implode(': ', [$this->configuration['key'], $this->configuration['value']]);

        return $summary;
    }
}
