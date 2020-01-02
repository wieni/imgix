<?php

namespace Drupal\imgix\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\imgix\ImgixManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ImgixSettings extends ConfigFormBase
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;

    public static function create(ContainerInterface $container)
    {
        $instance = parent::create($container);
        $instance->entityTypeManager = $container->get('entity_type.manager');

        return $instance;
    }

    public function getFormId()
    {
        return 'imgix_settings_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('imgix.settings');

        $form['source'] = [
            '#type' => 'details',
            '#title' => $this->t('Source'),
            '#collapsible' => false,
            '#open' => true,
        ];

        $form['source']['source_domain'] = [
            '#type' => 'textfield',
            '#required' => true,
            '#title' => $this->t('Subdomain'),
            '#description' => $this->t('Your imgix subdomain: *.imgix.net. Without protocol (http/https) please.'),
            '#default_value' => $config->get('source_domain'),
        ];

        $form['mapping'] = [
            '#type' => 'details',
            '#title' => $this->t('Mapping'),
            '#collapsible' => false,
            '#open' => true,
        ];

        // TODO: replace by getMappingTypes() from Manager.
        $form['mapping']['mapping_type'] = [
            '#type' => 'radios',
            '#required' => true,
            '#title' => $this->t('Source Type'),
            '#options' => [
                'webfolder' => 'Web Folder',
                'webproxy' => 'Web Proxy',
                's3' => 'Amazon S3',
            ],
            '#default_value' => $config->get('mapping_type'),
        ];

        $form['mapping']['s3_has_prefix'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('S3 bucket has prefix'),
            '#default_value' => $config->get('s3_has_prefix'),
        ];

        $form['mapping']['s3_has_prefix']['#states'] = [
            'visible' => [
                'input[name="mapping_type"]' => ['value' => ImgixManager::SOURCE_S3],
            ],
        ];

        // TODO: not supporting mapping urls for now.
        //$form['mapping']['mapping_url'] = array(
        //    '#type' => 'textfield',
        //    '#description' => $this->t('Leave blank to get the current base URL.'),
        //    '#title' => $this->t('Base URL'),
        //    '#default_value' => $config->get('mapping_url'),
        //);

        $form['security'] = [
            '#type' => 'details',
            '#title' => $this->t('Security'),
            '#collapsible' => false,
            '#open' => true,
        ];

        $form['security']['secure_url_token'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Secure URL Token'),
            '#default_value' => $config->get('secure_url_token'),
        ];

        $form['security']['https'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('HTTPS support'),
            '#default_value' => $config->get('https'),
        ];

        $form['options'] = [
            '#type' => 'details',
            '#title' => $this->t('Extra options'),
            '#collapsible' => false,
            '#open' => true,
        ];

        $form['options']['external_cdn'] = [
            '#type' => 'textfield',
            '#title' => $this->t('External CDN'),
            '#description' => $this->t('Enter the base url of your external cdn to use instead of Imgix own cdn. Do not add a protocol (http/https) nor leading/trailing slashes'),
            '#default_value' => $config->get('external_cdn'),
        ];

        return parent::buildForm($form, $form_state);
    }

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

    protected function getEditableConfigNames()
    {
        return [
            'imgix.settings',
        ];
    }
}
