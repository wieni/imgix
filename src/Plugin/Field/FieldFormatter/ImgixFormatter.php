<?php

namespace Drupal\imgix\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\file\Plugin\Field\FieldFormatter\GenericFileFormatter;
use Drupal\image\Entity\ImageStyle;
use Drupal\imgix\ImgixManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'imgix' formatter.
 *
 * @FieldFormatter(
 *     id = "imgix_formatter",
 *     label = @Translation("Imgix"),
 *     field_types = {
 *         "imgix"
 *     }
 * )
 */
class ImgixFormatter extends GenericFileFormatter implements ContainerFactoryPluginInterface
{
    /** @var ImgixManagerInterface */
    protected $imgixManager;

    public function __construct(
        $plugin_id,
        $plugin_definition,
        FieldDefinitionInterface $field_definition,
        array $settings,
        $label,
        $view_mode,
        array $third_party_settings,
        ImgixManagerInterface $imgixManager
    ) {
        parent::__construct(
            $plugin_id,
            $plugin_definition,
            $field_definition,
            $settings,
            $label,
            $view_mode,
            $third_party_settings
        );

        $this->imgixManager = $imgixManager;
    }

    public static function create(
        ContainerInterface $container,
        array $configuration,
        $plugin_id,
        $plugin_definition
    ) {
        return new static(
            $plugin_id,
            $plugin_definition,
            $configuration['field_definition'],
            $configuration['settings'],
            $configuration['label'],
            $configuration['view_mode'],
            $configuration['third_party_settings'],
            $container->get('imgix.manager')
        );
    }

    public static function defaultSettings()
    {
        return [
            'image_preset' => '',
            'image_link' => '',
        ] + parent::defaultSettings();
    }

    public function settingsForm(array $form, FormStateInterface $form_state)
    {
        $presets = $this->imgixManager->getPresets();
        $options = [];

        foreach ($presets as $key => $preset) {
            $options[$key] = ucfirst($preset['key']) . ' (' . $preset['query'] . ')';
        }

        $element['image_preset'] = [
            '#title' => t('Imgix preset'),
            '#type' => 'select',
            '#default_value' => $this->getSetting('image_preset'),
            '#empty_option' => t('None (original image)'),
            '#options' => $options,
        ];

        $linkTypes = [
            'content' => t('Content'),
            'file' => t('File'),
        ];

        $element['image_link'] = [
            '#title' => t('Link image to'),
            '#type' => 'select',
            '#default_value' => $this->getSetting('image_link'),
            '#empty_option' => t('Nothing'),
            '#options' => $linkTypes,
        ];

        return $element;
    }

    public function settingsSummary()
    {
        $summary = [];
        $presets = $this->imgixManager->getPresets();

        // Styles could be lost because of enabled/disabled modules that defines
        // their styles in code.
        $presetSetting = $this->getSetting('image_preset');
        if (isset($presets[$presetSetting])) {
            $summary[] = $this->t(
                'Imgix preset: @preset',
                ['@preset' => ucfirst($presets[$presetSetting]['key'])]
            );
        } else {
            $summary[] = t('Original image');
        }

        $linkTypes = [
            'content' => t('Linked to content'),
            'file' => t('Linked to file'),
        ];

        // Display this setting only if image is linked.
        $imageLinkSetting = $this->getSetting('image_link');
        if (isset($linkTypes[$imageLinkSetting])) {
            $summary[] = $linkTypes[$imageLinkSetting];
        }

        return $summary;
    }

    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        $elements = [];
        $files = $this->getEntitiesToView($items, $langcode);

        if (empty($files)) {
            return $elements;
        }

        // Check if the formatter involves a link.
        $linkUrl = null;
        $imageLinkSetting = $this->getSetting('image_link');
        if ($imageLinkSetting == 'content') {
            $entity = $items->getEntity();
            if (!$entity->isNew()) {
                $linkUrl = $entity->toUrl();
            }
        } elseif ($imageLinkSetting == 'file') {
            $linkFile = true;
        }

        // Get the params for the given preset.
        $presetSetting = $this->getSetting('image_preset');
        $presets = $this->imgixManager->getPresets();
        $params = [];
        if (isset($presets[$presetSetting])) {
            $tmp = explode('&', $presets[$presetSetting]['query']);
            foreach ($tmp as $value) {
                $tmp2 = explode('=', $value);
                $params[$tmp2[0]] = $tmp2[1];
            }
        }

        // Collect cache tags to be added for each item in the field.
        $baseCacheTags = [];
        $meta = [];

        foreach ($items as $item) {
            $meta[$item->target_id] = [
                'title' => $item->title,
                'caption' => $item->description,
            ];
        }

        foreach ($files as $delta => $file) {
            $cacheContexts = [];

            if (isset($linkFile)) {
                $imageUri = $file->getFileUri();
                // @todo Wrap in file_url_transform_relative(). This is currently
                // impossible. As a work-around, we currently add the 'url.site' cache
                // context to ensure different file URLs are generated for different
                // sites in a multisite setup, including HTTP and HTTPS versions of the
                // same site. Fix in https://www.drupal.org/node/2646744.
                $linkUrl = Url::fromUri(file_create_url($imageUri));
                $cacheContexts[] = 'url.site';
            }

            $cacheTags = Cache::mergeTags(
                $baseCacheTags,
                $file->getCacheTags()
            );

            $url = $this->imgixManager->getImgixUrl($file, $params);

            $elements[$delta] = [
                '#theme' => 'imgix_image',
                '#preset' => $presetSetting,
                '#url' => $url,
                '#linkUrl' => $linkUrl,
                '#title' => !empty($meta[$file->id()]) ? $meta[$file->id()]['title'] : '',
                '#caption' => !empty($meta[$file->id()]) ? $meta[$file->id()]['caption'] : '',
                '#cache' => [
                    'tags' => $cacheTags,
                    'contexts' => $cacheContexts,
                ],
            ];
        }

        return $elements;
    }

    public function calculateDependencies()
    {
        $dependencies = parent::calculateDependencies();
        $styleId = $this->getSetting('image_style');

        if ($styleId && $style = ImageStyle::load($styleId)) {
            // If this formatter uses a valid image style to display the image, add
            // the image style configuration entity as dependency of this formatter.
            $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
        }

        return $dependencies;
    }

    public function onDependencyRemoval(array $dependencies)
    {
        $changed = parent::onDependencyRemoval($dependencies);
        $styleId = $this->getSetting('image_style');

        if ($styleId && $style = ImageStyle::load($styleId)) {
            if (!empty($dependencies[$style->getConfigDependencyKey()][$style->getConfigDependencyName()])) {
                $replacement_id = $this->imageStyleStorage->getReplacementId($styleId);
                // If a valid replacement has been provided in the storage, replace the
                // image style with the replacement and signal that the formatter plugin
                // settings were updated.
                if ($replacement_id && ImageStyle::load($replacement_id)) {
                    $this->setSetting('image_style', $replacement_id);
                    $changed = true;
                }
            }
        }

        return $changed;
    }
}
