<?php

/**
 * Settings page callback.
 */
function imgix_admin() {
  $form = array();

  $form['imgix_enable'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable Imgix Integration'),
    '#description' => t('Enabling this option make it the default image processing and CDN.'),
    '#default_value' => variable_get('imgix_enable', FALSE),
  );

  $form['imgix_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Settings'),
    '#collapsible' => FALSE,
    '#states' => array(
      'visible' => array(
        ':input[name="imgix_enable"]' => array('checked' => TRUE),
      ),
    ),
  );

  $form['imgix_settings']['source'] = array(
    '#type' => 'fieldset',
    '#title' => t('Source'),
    '#collapsible' => FALSE,
  );

  $form['imgix_settings']['source']['imgix_source_domain'] = array(
    '#type' => 'textfield',
    '#required' => TRUE,
    '#title' => t('Domain'),
    '#default_value' => variable_get('imgix_source_domain'),
  );

  $form['imgix_settings']['mapping'] = array(
    '#type' => 'fieldset',
    '#title' => t('Mapping'),
    '#collapsible' => FALSE,
  );

  $form['imgix_settings']['mapping']['imgix_mapping_type'] = array(
    '#type' => 'radios',
    '#required' => TRUE,
    '#title' => t('Type'),
    '#options' => array(
      'webfolder' => 'Web Folder',
      'webproxy' => 'Web Proxy'
    ),
    '#default_value' => variable_get('imgix_mapping_type', 'webfolder'),
  );

  $form['imgix_settings']['mapping']['imgix_mapping_url'] = array(
    '#type' => 'textfield',
    '#description' => t('Leave blank to get the current base URL.'),
    '#title' => t('Base URL'),
    '#default_value' => variable_get('imgix_mapping_url'),
  );

  $form['imgix_settings']['security'] = array(
    '#type' => 'fieldset',
    '#title' => t('Security'),
    '#collapsible' => FALSE,
  );

  $form['imgix_settings']['security']['imgix_secure_url_token'] = array(
    '#type' => 'textfield',
    '#title' => t('Secure URL Token'),
    '#default_value' => variable_get('imgix_secure_url_token'),
  );

  $form['imgix_settings']['security']['imgix_https'] = array(
    '#type' => 'checkbox',
    '#title' => t('HTTPS support'),
    '#default_value' => variable_get('imgix_https', FALSE),
  );

  return system_settings_form($form);
}
