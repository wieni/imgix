<?php

namespace Drupal\imgix\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\file\Plugin\Field\FieldFormatter\GenericFileFormatter;
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

    public static function create(
        ContainerInterface $container,
        array $configuration,
        $pluginId,
        $pluginDefinition
    ) {
        $instance = parent::create(
            $container,
            $configuration,
            $pluginId,
            $pluginDefinition
        );
        $instance->imgixManager = $container->get('imgix.manager');

        return $instance;
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
        $element['image_preset'] = [
            '#title' => $this->t('Imgix preset'),
            '#type' => 'select',
            '#default_value' => $this->getSetting('image_preset'),
            '#empty_option' => $this->t('None (original image)'),
            '#options' => array_map(
                static function (array $preset) {
                    return ucfirst($preset['key']) . ' (' . $preset['query'] . ')';
                },
                $this->imgixManager->getPresets()
            ),
        ];

        $element['image_link'] = [
            '#title' => $this->t('Link image to'),
            '#type' => 'select',
            '#default_value' => $this->getSetting('image_link'),
            '#empty_option' => $this->t('Nothing'),
            '#options' => [
                'content' => $this->t('Content'),
                'file' => $this->t('File'),
            ],
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
            $summary[] = $this->t('Original image');
        }

        $linkTypes = [
            'content' => $this->t('Linked to content'),
            'file' => $this->t('Linked to file'),
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

            $elements[$delta] = [
                '#theme' => 'imgix_image',
                '#linkUrl' => $linkUrl,
                '#title' => !empty($meta[$file->id()]) ? $meta[$file->id()]['title'] : '',
                '#caption' => !empty($meta[$file->id()]) ? $meta[$file->id()]['caption'] : '',
                '#cache' => [
                    'tags' => $cacheTags,
                    'contexts' => $cacheContexts,
                ],
            ];

            if ($preset = $this->getSetting('image_preset')) {
                $elements[$delta]['#preset'] = $preset;
                $elements[$delta]['#url'] = $this->imgixManager->getImgixUrlByPreset($file, $preset);
            }
        }

        return $elements;
    }
}
