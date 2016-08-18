<?php

use Imgix\UrlBuilder;

/**
 * Builds URL given parameters and loaded variables.
 */
function imgix_build_url($path, $params = array()) {
  // Load settings.
  $source_domain = variable_get('imgix_source_domain');
  $mapping_type = variable_get('imgix_mapping_type');
  $mapping_url = variable_get('imgix_mapping_url');
  $https = variable_get('imgix_https');
  $secure_url_token = variable_get('imgix_secure_url_token');

  // Parse URL.
  $parse = parse_url($path);

  // Initialize object with given source domain.
  $builder = new UrlBuilder($source_domain);
  
  // Enable HTTPS support
  $builder->setUseHttps($https);
  
  // If secure URL is enabled, then set the signkey.
  if ($secure_url_token) {
    $builder->setSignKey($secure_url_token);
  }

  // Replace current domain with given Mapping URL.
  if ($mapping_url) {
    $path = str_replace($parse['scheme'] . '://' . $parse['host'], $mapping_url, $path);
  }

  // If it's a webfolder mapping, then path must be the relative path.
  if ($mapping_type == 'webfolder') {
    $path = $parse['path'];
  }

  return $builder->createURL($path, $params);
}
