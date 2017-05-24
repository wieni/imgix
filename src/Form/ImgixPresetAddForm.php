<?php

namespace Drupal\imgix\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure imgix presets for this site.
 */
class ImgixPresetAddForm extends ConfigFormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'imgix_presets_add';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return [
            'imgix.presets.add',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $key = null)
    {
        $presets = \Drupal::config('imgix.presets')->get('presets');
        $preset = false;
        if (!empty($presets[$key])) {
            $preset = $presets[$key];
        }

        $form['key'] = array(
            '#title' => $this->t('Key'),
            '#type' => 'machine_name',
            '#default_value' => $preset ? $preset['key'] : '',
            '#maxlength' => 64,
            '#description' => $this->t('A unique name for this preset. It must only contain lowercase letters, numbers, and underscores.'),
            '#machine_name' => [
                'exists' => [$this, 'exists'],
            ],
            '#disabled' => isset($preset['key']),
        );

        $form['query'] = array(
            '#type' => 'textarea',
            '#title' => $this->t('Parameter string'),
            '#default_value' => $preset ? $preset['query'] : '',
            '#size' => 128,
            '#required' => true,
            '#maxlength' => 255,
            '#description' => $this->t('The parameter string to pass to imgix eg "w=900&h=300&fit=crop&crop=entropy"'),
        );

        return parent::buildForm($form, $form_state);
    }

    /**
     * Checks for an existing ECK entity type.
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

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $presets = \Drupal::config('imgix.presets')->get('presets');

        $presets[$form_state->getValue('key')] = [
            'key' => $form_state->getValue('key'),
            'query' => $form_state->getValue('query'),
        ];

        $config = \Drupal::service('config.factory')->getEditable('imgix.presets');
        $config->set('presets', $presets)->save();

        parent::submitForm($form, $form_state);
    }
}
