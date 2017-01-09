<?php

namespace Drupal\imgix\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

class ImgixPresetDeleteForm extends ConfirmFormBase
{
    
    /**
     * {@inheritdoc}
     */
    public function getFormId() 
    {
        return 'imgix_presets_delete';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getQuestion() 
    {
        return t('Do you want to delete preset %id?', array('%id' => $this->id));
    }
    
    /**
     * {@inheritdoc}
     */
    public function getCancelUrl() 
    {
        return new Url('imgix.presets');
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDescription() 
    {
        return t('Only do this if you are sure!');
    }
    
    /**
     * {@inheritdoc}
     */
    public function getConfirmText() 
    {
        return t('Delete it!');
    }
    
    /**
     * {@inheritdoc}
     */
    public function getCancelText() 
    {
        return t('Nevermind');
    }
    
    /**
     * {@inheritdoc}
     *
     * @param int $key
     *   (optional) The ID of the item to be deleted.
     */
    public function buildForm(array $form, FormStateInterface $form_state, $key = null) 
    {
        $this->id = $key;
        return parent::buildForm($form, $form_state);
    }
    
    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) 
    {
        $presets = \Drupal::config('imgix.presets')->get('presets');
        
        unset($presets[$this->id]);
        
        $config = \Drupal::service('config.factory')->getEditable('imgix.presets');
        $config->set('presets', $presets)->save();
    }
    
}
