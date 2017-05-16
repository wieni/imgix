<?php

namespace Drupal\imgix\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure file system settings for this site.
 */
class ImgixSettings extends ConfigFormBase
{
    
    /**
     * EntitTypeManager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;
    
    /**
     * Construct.
     *
     * {@inheritDoc}
     */
    public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) 
    {
        parent::__construct($config_factory);
        $this->entityTypeManager = $entity_type_manager;
    }
    
    /**
     * Create.
     *
     * {@inheritDoc}
     */
    public static function create(ContainerInterface $container) 
    {
        return new static(
            $container->get('config.factory'),
            $container->get('entity_type.manager')
        );
    }
    
    /**
     * Formid.
     *
     * {@inheritdoc}
     */
    public function getFormId() 
    {
        return 'imgix_settings_form';
    }
    
    /**
     * Config names.
     *
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() 
    {
        return ['imgix.settings'];
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) 
    {
        $config = $this->config('imgix.settings');
        
        $form['source'] = array(
            '#type' => 'details',
            '#title' => $this->t('Source'),
            '#collapsible' => false,
            '#open' => true,
        );
        
        $form['source']['source_domain'] = array(
            '#type' => 'textfield',
            '#required' => true,
            '#title' => $this->t('Subdomain'),
            '#description' => $this->t('Your imgix subdomain: *.imgix.net. Without protocol (http/https) please.'),
            '#default_value' => $config->get('source_domain'),
        );
        
        $form['mapping'] = array(
            '#type' => 'details',
            '#title' => $this->t('Mapping'),
            '#collapsible' => false,
            '#open' => true,
        );
        
        // TOdo replace by getMappingTypes() from Manager.
        $form['mapping']['mapping_type'] = array(
            '#type' => 'radios',
            '#required' => true,
            '#title' => $this->t('Source Type'),
            '#options' => array(
                'webfolder' => 'Web Folder',
                'webproxy' => 'Web Proxy',
                's3' => 'Amazon S3',
            ),
            '#default_value' => $config->get('mapping_type'),
            '#description' => $this->t('Do not use a S3 Bucket Prefix when using S3 as a Source Type'),
        );

        // Todo not supporting mapping urls for now.
        //$form['mapping']['mapping_url'] = array(
        //    '#type' => 'textfield',
        //    '#description' => $this->t('Leave blank to get the current base URL.'),
        //    '#title' => $this->t('Base URL'),
        //    '#default_value' => $config->get('mapping_url'),
        //);
        
        $form['security'] = array(
            '#type' => 'details',
            '#title' => $this->t('Security'),
            '#collapsible' => false,
            '#open' => true,
        );
        
        $form['security']['secure_url_token'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Secure URL Token'),
            '#default_value' => $config->get('secure_url_token'),
        );
        
        $form['security']['https'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('HTTPS support'),
            '#default_value' => $config->get('https'),
        );

        $form['options'] = array(
            '#type' => 'details',
            '#title' => $this->t('Extra options'),
            '#collapsible' => false,
            '#open' => true,
        );
        $form['options']['external_cdn'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('External CDN'),
            '#description' => $this->t('Enter the base url of your external cdn to use instead of Imgix own cdn. Do not add a protocol (http/https) nor leading/trailing slashes'),
            '#default_value' => $config->get('external_cdn'),
        );
        
        return parent::buildForm($form, $form_state);
    }
    
    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) 
    {
        
        $config = $this->config('imgix.settings');
        
        $form_state->cleanValues();
        
        foreach ($form_state->getValues() as $key => $value) {
            $config->set($key, $value);
        }
        $config->save();
        
        parent::submitForm($form, $form_state);
    }
    
}
