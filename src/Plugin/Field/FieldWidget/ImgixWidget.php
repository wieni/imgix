<?php

namespace Drupal\imgix\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Drupal\Core\Render\Element;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\imgix\ImgixManagerInterface;

/**
 * Plugin implementation of the 'imgix' widget.
 *
 * @FieldWidget(
 *   id = "imgix",
 *   label = @Translation("Imgix image"),
 *   field_types = {
 *     "imgix"
 *   }
 * )
 */
class ImgixWidget extends FileWidget implements ContainerFactoryPluginInterface
{
    /** @var  ImgixManagerInterface */
    protected $imgixManager;
    /** @var  RendererInterface */
    protected $renderer;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        $plugin_id,
        $plugin_definition,
        FieldDefinitionInterface $field_definition,
        array $settings,
        array $third_party_settings,
        ElementInfoManagerInterface $element_info,
        ImgixManagerInterface $imgixManager,
        RendererInterface $renderer
    ) {
        parent::__construct(
            $plugin_id,
            $plugin_definition,
            $field_definition,
            $settings,
            $third_party_settings,
            $element_info
        );

        $this->imgixManager = $imgixManager;
        $this->renderer = $renderer;
    }

    /**
     * @param ContainerInterface $container
     * @param array $configuration
     * @param string $plugin_id
     * @param mixed $plugin_definition
     * @return static
     */
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
            $configuration['third_party_settings'],
            $container->get('element_info'),
            $container->get('imgix.manager'),
            $container->get('renderer')
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function defaultSettings()
    {
        return [
                'preview_preset' => 'thumb',
            ] + parent::defaultSettings();
    }

    /**
     * {@inheritdoc}
     */
    public function settingsForm(array $form, FormStateInterface $form_state)
    {
        $options = [];
        foreach ($this->imgixManager->getPresets() as $preset) {
            $options[$preset['key']] = $preset['key'];
        }

        $element['preview_preset'] = [
            '#type' => 'select',
            '#title' => t('Preview format'),
            '#options' => $options,
            '#default_value' => $this->getSetting('preview_preset'),
            '#weight' => 16,
        ];

        return $element;
    }

    /**
     * {@inheritdoc}
     */
    public function settingsSummary()
    {
        $summary = [];
        $summary[] = t('Preset: @preset', ['@preset' => $this->getSetting('preview_preset')]);
        return $summary;
    }

    /**
     * Overrides \Drupal\file\Plugin\Field\FieldWidget\FileWidget::formMultipleElements().
     *
     * Special handling for draggable multiple widgets and 'add more' button.
     * @param FieldItemListInterface $items
     * @param array $form
     * @param FormStateInterface $form_state
     * @return array
     */
    protected function formMultipleElements(
        FieldItemListInterface $items,
        array &$form,
        FormStateInterface $form_state
    ) {
        $elements = parent::formMultipleElements($items, $form, $form_state);

        $cardinality = $this->fieldDefinition->getFieldStorageDefinition()
            ->getCardinality();
        $file_upload_help = [
            '#theme' => 'file_upload_help',
            '#description' => '',
            '#upload_validators' => $elements[0]['#upload_validators'],
            '#cardinality' => $cardinality,
        ];
        if ($cardinality == 1) {
            // If there's only one field, return it as delta 0.
            if (empty($elements[0]['#default_value']['fids'])) {
                $file_upload_help['#description'] = $this->getFilteredDescription();
                $elements[0]['#description'] = $this->renderer->renderPlain($file_upload_help);
            }

            return $elements;
        }

        $elements['#file_upload_description'] = $file_upload_help;

        return $elements;
    }

    /**
     * {@inheritdoc}
     */
    public function formElement(
        FieldItemListInterface $items,
        $delta,
        array $element,
        array &$form,
        FormStateInterface $form_state
    ) {
        $element = parent::formElement($items, $delta, $element, $form, $form_state);

        $field_settings = $this->getFieldSettings();

        $presets = $this->imgixManager->getPresets();
        $element['#imgix_preset'] = 'thumb';
        if (!empty($presets[$this->getSetting('preview_preset')]['key'])) {
            $element['#imgix_preset'] = $presets[$this->getSetting('preview_preset')]['key'];
        }

        // If not using custom extension validation, ensure this is an image.
        $supported_extensions = ['png', 'gif', 'jpg', 'jpeg', 'svg'];
        if (isset($element['#upload_validators']['file_validate_extensions'][0])) {
            $extensions = $element['#upload_validators']['file_validate_extensions'][0];
        } else {
            $extensions = implode(' ', $supported_extensions);
        }
        $extensions = array_intersect(
            explode(' ', $extensions),
            $supported_extensions
        );
        $element['#upload_validators']['file_validate_extensions'][0] = implode(
            ' ',
            $extensions
        );

        // Add properties needed by process() method.
        $element['#title_field'] = $field_settings['title_field'];
        $element['#title_field_required'] = $field_settings['title_field_required'];

        return $element;
    }

    /**
     * Form API callback: Processes a image_image field element.
     *
     * Expands the image_image type to include the alt and title fields.
     *
     * This method is assigned as a #process callback in formElement() method.
     * @param $element
     * @param FormStateInterface $form_state
     * @param $form
     * @return
     */
    public static function process(
        $element,
        FormStateInterface $form_state,
        $form
    ) {
        $item = $element['#value'];
        $item['fids'] = $element['fids']['#value'];

        foreach (Element::children($element) as $child) {
            if (isset($element[$child]['filename']['#file']) && $element[$child]['filename']['#theme'] == 'file_link') {
                unset($element[$child]['filename']['#theme']);

                // Yes I know no injection here. We can't access $this it in static context.
                $url = \Drupal::service('imgix.manager')->getImgixUrlByPreset(
                    $element[$child]['filename']['#file'],
                    !empty($element['#imgix_preset']) ? $element['#imgix_preset']: 'thumb'
                );

                $element[$child]['preview'] = [
                    '#weight' => -10,
                    '#theme' => 'imgix_image',
                    '#url' => $url,
                    '#title' => isset($item['title']) ? $item['title'] : '',
                    '#caption' => '',
                ];
            }
        }

        // Add the image title.
        $element['title'] = [
            '#type' => 'textfield',
            '#title' => t('Title'),
            '#default_value' => isset($item['title']) ? $item['title'] : '',
            '#description' => t('The title is used as a tool tip when the user hovers the mouse over the image.'),
            '#maxlength' => 1024,
            '#weight' => -10,
            '#access' => (bool) $item['fids'] && $element['#title_field'],
            '#required' => $element['#title_field_required'],
            '#element_validate' => $element['#title_field_required'] == 1 ?
            [
                [
                    get_called_class(),
                    'validateRequiredFields',
                ],
            ] :
            [],
        ];

        $element = parent::process($element, $form_state, $form);

        // Attach image preview library
        $element['#attached']['library'][] = 'imgix/image-preview';

        // Change the image description / caption
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
     * @param $element
     * @param FormStateInterface $form_state
     */
    public static function validateRequiredFields(
        $element,
        FormStateInterface $form_state
    ) {
        // Only do validation if the function is triggered from other places than
        // the image process form.
        if (!in_array(
            'file_managed_file_submit',
            $form_state->getTriggeringElement()['#submit']
        )
        ) {
            // If the image is not there, we do not check for empty values.
            $parents = $element['#parents'];
            $field = array_pop($parents);
            $image_field = NestedArray::getValue(
                $form_state->getUserInput(),
                $parents
            );
            // We check for the array key, so that it can be NULL (like if the user
            // submits the form without using the "upload" button).
            if (!array_key_exists($field, $image_field)) {
                return;
            }
        } else {
            $form_state->setLimitValidationErrors([]);
        }
    }
}
