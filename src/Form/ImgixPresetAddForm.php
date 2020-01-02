<?php

namespace Drupal\imgix\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ImgixPresetAddForm extends ConfigFormBase
{
    public function getFormId()
    {
        return 'imgix_presets_add';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $key = null)
    {
        $presets = $this->config('imgix.presets')->get('presets');
        $preset = false;
        if (!empty($presets[$key])) {
            $preset = $presets[$key];
        }

        $form['key'] = [
            '#title' => $this->t('Key'),
            '#type' => 'machine_name',
            '#default_value' => $preset ? $preset['key'] : '',
            '#maxlength' => 64,
            '#description' => $this->t('A unique name for this preset. It must only contain lowercase letters, numbers, and underscores.'),
            '#machine_name' => [
                'exists' => [$this, 'exists'],
            ],
            '#disabled' => isset($preset['key']),
        ];

        $form['query'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Parameter string'),
            '#default_value' => $preset ? $preset['query'] : '',
            '#size' => 128,
            '#required' => true,
            '#maxlength' => 255,
            '#description' => $this->t('The parameter string to pass to imgix eg "w=900&h=300&fit=crop&crop=entropy"'),
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * Checks for an existing ECK entity type.
     * TODO: Fix this
     *
     * @param string             $key
     *   The settings ID.
     * @param array              $element
     *   The form element.
     * @param FormStateInterface $form_state
     *   The form state.
     *
     * @return bool
     *   TRUE if this format already exists, FALSE otherwise.
     */
    public function exists($key, array $element, FormStateInterface $form_state)
    {
        // $setting = $this->wmSettings->readKey($key);
        $setting = [];

        if (!empty($setting['key'])) {
            return true;
        }

        return false;
    }

    public function submitForm(array &$form, FormStateInterface $formState)
    {
        $config = $this->config('imgix.presets');
        $presets = $config->get('presets');

        $presets[$formState->getValue('key')] = [
            'key' => $formState->getValue('key'),
            'query' => $formState->getValue('query'),
        ];

        $config->set('presets', $presets)->save();

        parent::submitForm($form, $formState);
    }

    protected function getEditableConfigNames()
    {
        return [
            'imgix.presets',
        ];
    }
}
