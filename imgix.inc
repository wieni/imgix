<?php

use Imgix\UrlBuilder;

/**
 * Builds URL given parameters and loaded variables.
 */
function imgix_build_url($path, $params = array()) {
  // Load settings.
  $domain = variable_get('imgix_domain');
  $mapping = variable_get('imgix_mapping');
  $https = variable_get('imgix_https');
  $secure_url = variable_get('imgix_secure_url');
  $secure_url_token = variable_get('imgix_secure_url_token');

  // Initialize object with given domain.
  $builder = new UrlBuilder($domain);
  
  // Enable HTTPS support
  $builder->setUseHttps($https);
  
  // If secure URL is enabled, then set the signkey.
  if ($secure_url) {
    $builder->setSignKey($secure_url_token);
  }

  // If it's a webfolder mapping, then path must be the relative path.
  if ($mapping == 'webfolder') {
    $parse = parse_url($path);
    $path = $parse['path'];
  }

  return $builder->createURL($path, $params);
}
