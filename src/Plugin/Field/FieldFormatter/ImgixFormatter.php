<?php

namespace Drupal\imgix\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;
use Drupal\imgix\Service\ImgixStylesInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Plugin implementation of the 'imgix' formatter.
 *
 * @FieldFormatter(
 *   id = "imgix",
 *   label = @Translation("Imgix"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ImgixFormatter extends ImageFormatter {

  /**
   * The ImgixStyles Service.
   *
   * @var ImgixStylesInterface $imgixStyles
   */
  protected $imgixStyles;

  /**
   * Constructs an ImgixFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityStorageInterface $image_style_storage, ImgixStylesInterface $imgixStyles) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $image_style_storage);
    $this->imgixStyles = $imgixStyles;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
          $plugin_id,
          $plugin_definition,
          $configuration['field_definition'],
          $configuration['settings'],
          $configuration['label'],
          $configuration['view_mode'],
          $configuration['third_party_settings'],
          $container->get('current_user'),
          $container->get('entity.manager')->getStorage('image_style'),
          $container->get('imgix.styles')
      );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'image_preset' => '',
      'image_link' => '',
      'image_caption' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $presets = $this->imgixStyles->getPresets()->get('presets');

    $options = [];
    foreach ($presets as $preset) {
      $options[$preset['key']] = $preset['key'] . ' (' . $preset['query'] . ')';
    }

    // $image_styles = image_style_options(FALSE);
    $description_link = Link::fromTextAndUrl(
          $this->t('Configure Imgix presets'),
          Url::fromRoute('imgix.presets')
      );
    $element['image_preset'] = [
      '#title' => t('Imgix preset'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_preset'),
      '#empty_option' => t('None (original image)'),
      '#options' => $options,
      '#description' => $description_link->toRenderable() + [
        '#access' => $this->currentUser->hasPermission('administer imgix'),
      ],
    ];
    $link_types = array(
      'content' => t('Content'),
      'file' => t('File'),
    );
    $element['image_link'] = array(
      '#title' => t('Link image to'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_link'),
      '#empty_option' => t('Nothing'),
      '#options' => $link_types,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $presets = $this->imgixStyles->getPresets()->get('presets');
    $image_preset_setting = $this->getSetting('image_preset');

    $presetFound = FALSE;
    if (isset($presets[$image_preset_setting])) {
      $presetFound = TRUE;
    }

    // Styles could be lost.
    if ($presetFound) {
      $summary[] = t('Imgix preset: @style', array('@style' => $image_preset_setting));
    }
    else {
      $summary[] = t('Original image');
    }

    $link_types = array(
      'content' => t('Linked to content'),
      'file' => t('Linked to file'),
    );
    // Display this setting only if image is linked.
    $image_link_setting = $this->getSetting('image_link');
    if (isset($link_types[$image_link_setting])) {
      $summary[] = $link_types[$image_link_setting];
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $files = $this->getEntitiesToView($items, $langcode);

    $presets = $this->imgixStyles->getPresets()->get('presets');

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    $url = NULL;
    $image_link_setting = $this->getSetting('image_link');
    // Check if the formatter involves a link.
    if ($image_link_setting == 'content') {
      $entity = $items->getEntity();
      if (!$entity->isNew()) {
        $url = $entity->urlInfo();
      }
    }
    elseif ($image_link_setting == 'file') {
      $link_file = TRUE;
    }

    // Get the correct imgix query string;.
    $image_style_setting = $this->getSetting('image_preset');
    if (isset($presets[$image_style_setting])) {
      $query = $presets[$image_style_setting]['query'];
    }

    // Collect cache tags to be added for each item in the field.
    $base_cache_tags = [];

    foreach ($files as $delta => $file) {
      $cache_contexts = [];
      if (isset($link_file)) {
        $image_uri = $file->getFileUri();
        // @todo Wrap in file_url_transform_relative(). This is currently
        // impossible. As a work-around, we currently add the 'url.site' cache
        // context to ensure different file URLs are generated for different
        // sites in a multisite setup, including HTTP and HTTPS versions of the
        // same site. Fix in https://www.drupal.org/node/2646744.
        $url = Url::fromUri(file_create_url($image_uri));
        $cache_contexts[] = 'url.site';
      }
      $cache_tags = Cache::mergeTags($base_cache_tags, $file->getCacheTags());

      // Get the real url of the file.
      $image_uri = $file->getFileUri();

      $imgixUrl = $this->imgixStyles->buildRawUrl(
            Url::fromUri(file_create_url($image_uri))->toString(),
            $query
        );

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;
      $item_attributes = $item->_attributes;
      unset($item->_attributes);

      $elements[$delta] = array(
        '#theme' => 'imgix_formatter',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#image_preset' => $image_style_setting,
        '#url' => $url,
        '#imgix_url' => $imgixUrl,
        '#cache' => array(
          'tags' => $cache_tags,
          'contexts' => $cache_contexts,
        ),
      );
    }

    return $elements;
  }

}
