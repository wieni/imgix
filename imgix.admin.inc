<?php

/**
 * Settings page callback.
 */
function imgix_admin() {
  $form = array();

  $form['imgix_domain'] = array(
    '#type' => 'textfield',
    '#title' => t('Domain'),
    '#required' => TRUE,
    '#default_value' => variable_get('imgix_domain'),
  );

  $form['imgix_mapping'] = array(
    '#type' => 'radios',
    '#title' => t('Mapping'),
    '#options' => array(
      'webfolder' => 'Web Folder',
      'webproxy' => 'Web Proxy'
    ),
    '#required' => TRUE,
    '#default_value' => variable_get('imgix_mapping', 'webfolder'),
  );

  $form['imgix_https'] = array(
    '#type' => 'checkbox',
    '#title' => t('HTTPS support'),
    '#default_value' => variable_get('imgix_https', FALSE),
  );

  $form['imgix_secure_url'] = array(
    '#type' => 'checkbox',
    '#title' => t('Secure URL'),
    '#default_value' => variable_get('imgix_secure_url', FALSE),
    '#states' => array(
      'checked' => array(
        ':input[name="imgix_mapping"]' => array('value' => 'webproxy'),
      ),
      'required' => array(
        ':input[name="imgix_mapping"]' => array('value' => 'webproxy'),
      ),
    ),
  );

  $form['imgix_secure_url_token'] = array(
    '#type' => 'textfield',
    '#title' => t('Secure URL Token'),
    '#default_value' => variable_get('imgix_secure_url_token'),
    '#required' => TRUE,
    '#states' => array(
      'visible' => array(
        ':input[name="imgix_secure_url"]' => array('checked' => TRUE),
        ':input[name="imgix_mapping"]' => array('value' => 'webproxy'),
      ),
    ),
  );

  return system_settings_form($form);
}