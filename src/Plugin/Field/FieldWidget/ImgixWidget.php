<?php

namespace Drupal\imgix\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Drupal\imgix\ImgixManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @FieldWidget(
 *     id = "imgix",
 *     label = @Translation("Imgix image"),
 *     field_types = {
 *         "imgix"
 *     }
 * )
 */
class ImgixWidget extends FileWidget
{
    /** @var ImgixManagerInterface */
    protected $imgixManager;
    /** @var RendererInterface */
    protected $renderer;

    public static function create(
        ContainerInterface $container,
        array $configuration,
        $plugin_id,
        $plugin_definition
    ) {
        $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
        $instance->imgixManager = $container->get('imgix.manager');
        $instance->renderer = $container->get('renderer');

        return $instance;
    }

    public static function defaultSettings()
    {
        return [
            'preview_preset' => 'thumb',
        ] + parent::defaultSettings();
    }

    public function settingsForm(array $form, FormStateInterface $form_state)
    {
        $options = [];
        foreach ($this->imgixManager->getPresets() as $preset) {
            $options[$preset['key']] = $preset['key'];
        }

        $element['preview_preset'] = [
            '#type' => 'select',
            '#title' => $this->t('Preview format'),
            '#options' => $options,
            '#default_value' => $this->getSetting('preview_preset'),
            '#weight' => 16,
        ];

        return $element;
    }

    public function settingsSummary()
    {
        return [
            $this->t('Preset: @preset', ['@preset' => $this->getSetting('preview_preset')]),
        ];
    }

    public function formElement(
        FieldItemListInterface $items,
        $delta,
        array $element,
        array &$form,
        FormStateInterface $form_state
    ) {
        $element = parent::formElement($items, $delta, $element, $form, $form_state);
        $fieldSettings = $this->getFieldSettings();
        $presets = $this->imgixManager->getPresets();

        $element['#imgix_preset'] = 'thumb';
        if (!empty($presets[$this->getSetting('preview_preset')]['key'])) {
            $element['#imgix_preset'] = $presets[$this->getSetting('preview_preset')]['key'];
        }

        // If not using custom extension validation, ensure this is an image.
        $supportedExtensions = ImgixManagerInterface::SUPPORTED_EXTENSIONS;
        $extensions = $element['#upload_validators']['file_validate_extensions'][0] ?? implode(' ', $supportedExtensions);

        $extensions = array_intersect(
            explode(' ', $extensions),
            $supportedExtensions
        );

        $element['#upload_validators']['file_validate_extensions'][0] = implode(' ', $extensions);

        // Add properties needed by process() method.
        $element['#title_field'] = $fieldSettings['title_field'];
        $element['#title_field_required'] = $fieldSettings['title_field_required'];

        return $element;
    }

    public static function process($element, FormStateInterface $form_state, $form)
    {
        $item = $element['#value'];
        $item['fids'] = $element['fids']['#value'];

        foreach (Element::children($element) as $child) {
            if (
                !isset($element[$child]['filename']['#file'])
                || $element[$child]['filename']['#theme'] !== 'file_link'
            ) {
                continue;
            }

            unset($element[$child]['filename']['#theme']);

            $url = \Drupal::service('imgix.manager')->getImgixUrlByPreset(
                $element[$child]['filename']['#file'],
                !empty($element['#imgix_preset']) ? $element['#imgix_preset'] : 'thumb'
            );

            $element[$child]['preview'] = [
                '#weight' => -10,
                '#theme' => 'imgix_image',
                '#url' => $url,
                '#title' => $item['title'] ?? '',
                '#caption' => '',
            ];
        }

        $element['title'] = [
            '#type' => 'textfield',
            '#title' => t('Title'),
            '#default_value' => $item['title'] ?? '',
            '#description' => t('The title is used as a tool tip when the user hovers the mouse over the image.'),
            '#maxlength' => 1024,
            '#weight' => -10,
            '#access' => (bool) $item['fids'] && $element['#title_field'],
            '#required' => $element['#title_field_required'],
            '#element_validate' => $element['#title_field_required'] == 1
                ? [
                    [static::class, 'validateRequiredFields'],
                ]
                : [],
        ];

        $element = parent::process($element, $form_state, $form);

        $element['#attached']['library'][] = 'file/drupal.file';
        $element['#attached']['library'][] = 'imgix/image-preview';

        $element['#description_display'] = 'before';
        if (isset($element['description'])) {
            $element['description']['#title'] = t('Caption');
            $element['description']['#description'] = t('The caption will be shown under the image.');
        }

        return $element;
    }

    /**
     * Validate callback for alt and title field, if the user wants them required.
     *
     * This is separated in a validate function instead of a #required flag to
     * avoid being validated on the process callback.
     */
    public static function validateRequiredFields(
        $element,
        FormStateInterface $form_state
    ) {
        // Only do validation if the function is triggered from other places than
        // the image process form.
        if (in_array('file_managed_file_submit', $form_state->getTriggeringElement()['#submit'])) {
            $form_state->setLimitValidationErrors([]);
            return;
        }
    }

    /** Special handling for draggable multiple widgets and 'add more' button. */
    protected function formMultipleElements(
        FieldItemListInterface $items,
        array &$form,
        FormStateInterface $form_state
    ) {
        $elements = parent::formMultipleElements($items, $form, $form_state);
        $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
        $fileUploadHelp = [
            '#theme' => 'file_upload_help',
            '#description' => '',
            '#upload_validators' => $elements[0]['#upload_validators'],
            '#cardinality' => $cardinality,
        ];

        if ($cardinality == 1) {
            // If there's only one field, return it as delta 0.
            if (empty($elements[0]['#default_value']['fids'])) {
                $fileUploadHelp['#description'] = $this->getFilteredDescription();
                $elements[0]['#description'] = $this->renderer->renderPlain($fileUploadHelp);
            }

            return $elements;
        }

        $elements['#file_upload_description'] = $fileUploadHelp;

        return $elements;
    }
}
