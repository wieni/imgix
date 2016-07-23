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

  $form['imgix_settings']['imgix_domain'] = array(
    '#type' => 'textfield',
    '#title' => t('Domain'),
    '#default_value' => variable_get('imgix_domain'),
  );

  $form['imgix_settings']['imgix_mapping'] = array(
    '#type' => 'radios',
    '#title' => t('Mapping'),
    '#options' => array(
      'webfolder' => 'Web Folder',
      'webproxy' => 'Web Proxy'
    ),
    '#default_value' => variable_get('imgix_mapping', 'webfolder'),
  );

  $form['imgix_settings']['imgix_https'] = array(
    '#type' => 'checkbox',
    '#title' => t('HTTPS support'),
    '#default_value' => variable_get('imgix_https', FALSE),
  );

  $form['imgix_settings']['imgix_secure_url'] = array(
    '#type' => 'checkbox',
    '#title' => t('Secure URL'),
    '#default_value' => variable_get('imgix_secure_url', FALSE),
    '#states' => array(
      'checked' => array(
        ':input[name="imgix_mapping"]' => array('value' => 'webproxy'),
      ),
    ),
  );

  $form['imgix_settings']['imgix_secure_url_token'] = array(
    '#type' => 'textfield',
    '#title' => t('Secure URL Token'),
    '#default_value' => variable_get('imgix_secure_url_token'),
    '#states' => array(
      'visible' => array(
        ':input[name="imgix_secure_url"]' => array('checked' => TRUE),
      ),
    ),
  );

  return system_settings_form($form);
}
