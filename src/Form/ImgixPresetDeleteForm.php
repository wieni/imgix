<?php

namespace Drupal\imgix\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

class ImgixPresetDeleteForm extends ConfirmFormBase
{
    public function getFormId()
    {
        return 'imgix_presets_delete';
    }

    public function getQuestion()
    {
        return $this->t('Do you want to delete preset %id?', ['%id' => $this->id]);
    }

    public function getCancelUrl()
    {
        return Url::fromRoute('imgix.presets');
    }

    public function getDescription()
    {
        return $this->t('Only do this if you are sure!');
    }

    public function getConfirmText()
    {
        return $this->t('Delete it!');
    }

    public function getCancelText()
    {
        return $this->t('Nevermind');
    }

    public function buildForm(array $form, FormStateInterface $form_state, $key = null)
    {
        $this->id = $key;
        return parent::buildForm($form, $form_state);
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $config = $this->configFactory()->getEditable('imgix.presets');
        $presets = $config->get('presets');

        unset($presets[$this->id]);

        $config->set('presets', $presets)->save();
    }
}
