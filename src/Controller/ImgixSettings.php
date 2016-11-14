<?php

namespace Drupal\imgix\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Provides a list of wmsettings settings.
 */
class ImgixSettings extends ControllerBase {

  /**
   * Returns the admin screen with all presets.
   */
  public function overview() {
    $rows = [];

    $presets = \Drupal::config('imgix.presets')->get('presets');

    foreach ((array) $presets as $key => $value) {
      $operations = [
        'data' => [
          '#type' => 'operations',
          '#links' => [
            'edit' => [
              'url' => Url::fromRoute(
                "imgix.presets.add",
                [
                  'key' => $key,
                ],
                [
                  'query' => [
                    'destination' => Url::fromRoute(
                      "imgix.presets"
                    )->toString(),
                  ],
                ]
              ),
              'title' => $this->t('Edit'),
            ],
            'delete' => [
              'url' => Url::fromRoute(
                "imgix.presets.delete",
                [
                  'key' => $key,
                ],
                [
                  'query' => [
                    'destination' => Url::fromRoute(
                      "imgix.presets"
                    )->toString(),
                  ],
                ]
              ),
              'title' => $this->t('Delete'),
            ],
          ],
        ],
      ];

      $rows[] = [
        $key,
        $value['query'],
        $operations,
      ];
    }

    $build = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => [
        $this->t('Key'),
        $this->t('Query'),
        $this->t('Operations'),
      ],
    ];
    return $build;
  }

}
