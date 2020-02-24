<?php

namespace Drupal\imgix_browser\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\Plugin\Field\FieldWidget\EntityReferenceBrowserWidget;
use Drupal\imgix\ImgixManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Imgix Entity browser file widget.
 *
 * @FieldWidget(
 *     id = "entity_browser_imgix",
 *     label = @Translation("Imgix browser"),
 *     provider = "imgix_browser",
 *     multiple_values = TRUE,
 *     field_types = {
 *         "imgix",
 *     }
 * )
 */
class ImgixFileBrowserWidget extends EntityReferenceBrowserWidget
{
    /**
     * Due to the table structure, this widget has a different depth.
     *
     * @var int
     */
    protected static $deleteDepth = 3;

    /**
     * A list of currently edited items. Used to determine alt/title values.
     *
     * @var \Drupal\Core\Field\FieldItemListInterface
     */
    protected $items;

    /** @var ConfigFactoryInterface */
    protected $configFactory;
    /** @var EntityDisplayRepositoryInterface */
    protected $displayRepository;
    /** @var ImgixManagerInterface */
    protected $imgixManager;

    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
        $instance->entityTypeManager = $container->get('entity_type.manager');
        $instance->fieldDisplayManager = $container->get('plugin.manager.entity_browser.field_widget_display');
        $instance->configFactory = $container->get('config.factory');
        $instance->displayRepository = $container->get('entity_display.repository');
        $instance->imgixManager = $container->get('imgix.manager');

        return $instance;
    }

    public static function defaultSettings()
    {
        $settings = parent::defaultSettings();

        unset($settings['field_widget_display']);
        unset($settings['field_widget_display_settings']);

        return $settings;
    }

    public function settingsForm(array $form, FormStateInterface $form_state)
    {
        $element = parent::settingsForm($form, $form_state);

        $element['field_widget_display']['#access'] = false;
        $element['field_widget_display_settings']['#access'] = false;

        return $element;
    }

    public function settingsSummary()
    {
        return $this->summaryBase();
    }

    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)
    {
        $this->items = $items;
        $return = parent::formElement($items, $delta, $element, $form, $form_state);

        if (!empty($return['current'])) {
            $return['current']['#attached']['library'][] = 'imgix_browser/imgix.widget';
        }

        return $return;
    }

    public function massageFormValues(array $values, array $form, FormStateInterface $form_state)
    {
        $ids = empty($values['target_id']) ? [] : explode(' ', trim($values['target_id']));
        $return = [];

        foreach ($ids as $id) {
            $id = explode(':', $id)[1];
            if (is_array($values['current']) && isset($values['current'][$id])) {
                $item_values = [
                    'target_id' => $id,
                    '_weight' => $values['current'][$id]['_weight'],
                ];

                if (isset($values['current'][$id]['meta']['description'])) {
                    $item_values['description'] = $values['current'][$id]['meta']['description'];
                }
                if (isset($values['current'][$id]['meta']['title'])) {
                    $item_values['title'] = $values['current'][$id]['meta']['title'];
                }
                $return[] = $item_values;
            }
        }

        // Return ourself as the structure doesn't match the default.
        usort($return, function ($a, $b) {
            return SortArray::sortByKeyInt($a, $b, '_weight');
        });

        return array_values($return);
    }

    /**
     * Retrieves the upload validators for a file field.
     *
     * This is a combination of logic shared between the File and Image widgets.
     *
     * @return array
     *   An array suitable for passing to file_save_upload() or the file field
     *   element's '#upload_validators' property
     */
    public function getFileValidators()
    {
        $validators = [];
        $settings = $this->fieldDefinition->getSettings();

        // Cap the upload size according to the PHP limit.
        $maxFilesize = Bytes::toInt(Environment::getUploadMaxSize());
        if (!empty($settings['max_filesize'])) {
            $maxFilesize = min($maxFilesize, Bytes::toInt($settings['max_filesize']));
        }

        // There is always a file size limit due to the PHP server limit.
        $validators['file_validate_size'] = [$maxFilesize];

        // Images have expected defaults for file extensions.
        // See \Drupal\image\Plugin\Field\FieldWidget::formElement() for details.
        if ($this->fieldDefinition->getType() == 'image') {
            // If not using custom extension validation, ensure this is an image.
            $supportedExtensions = ImgixManagerInterface::SUPPORTED_EXTENSIONS;
            $extensions = $settings['file_extensions'] ?? implode(' ', $supportedExtensions);
            $extensions = array_intersect(explode(' ', $extensions), $supportedExtensions);
            $validators['file_validate_extensions'] = [implode(' ', $extensions)];
        } elseif (!empty($settings['file_extensions'])) {
            $validators['file_validate_extensions'] = [$settings['file_extensions']];
        }

        // Add upload resolution validation.
        if (!empty($settings['max_resolution']) || !empty($settings['min_resolution'])) {
            $validators['entity_browser_file_validate_image_resolution'] = [$settings['max_resolution'], $settings['min_resolution']];
        }

        return $validators;
    }

    /**
     * Validate callback for alt and title field, if the user wants them required.
     *
     * This is separated in a validate function instead of a #required flag to
     * avoid being validated on the process callback.
     */
    public static function validateRequiredFields($element, FormStateInterface $form_state)
    {
        $trigger = $form_state->getTriggeringElement();

        // Only do validation if the function is triggered from other places than
        // the image process form.
        if (isset($trigger['#submit']) && !in_array('file_managed_file_submit', $trigger['#submit'])) {
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

    protected function displayCurrentSelection($details_id, $field_parents, $entities)
    {
        $fieldSettings = $this->fieldDefinition->getSettings();
        $fieldMachineName = $this->fieldDefinition->getName();
        $fileSettings = $this->configFactory->get('file.settings');
        $widgetSettings = $this->getSettings();

        $canEdit = (bool) $widgetSettings['field_widget_edit'];

        $delta = 0;

        $orderClass = $fieldMachineName . '-delta-order';

        $current = [
            '#type' => 'table',
            '#empty' => $this->t('No files yet'),
            '#attributes' => ['class' => ['entities-list']],
            '#tabledrag' => [
                [
                    'action' => 'order',
                    'relationship' => 'sibling',
                    'group' => $orderClass,
                ],
            ],
        ];

        $current['#header'][] = $this->t('Preview');

        // Add the remaining columns.
        $current['#header'][] = $this->t('Metadata');
        $current['#header'][] = $this->t('Delete');
        $current['#header'][] = $this->t('Order', [], ['context' => 'Sort order']);

        /** @var \Drupal\file\FileInterface[] $entities */
        foreach ($entities as $entity) {
            // Check to see if this entity has an edit form. If not, the edit button
            // will only throw an exception.
            if (!$entity->getEntityType()->getFormClass('edit')) {
                $canEdit = false;
            }

            $entityId = $entity->id();

            // Find the default description.
            $title = '';
            $description = '';
            $weight = $delta;
            foreach ($this->items as $item) {
                if ($item->target_id == $entityId && get_class($item) == 'Drupal\imgix\Plugin\Field\FieldType\ImgixFieldType') {
                    $title = $item->getTitle();
                    $description = $item->getCaption();
                    $weight = $item->_weight ?: $delta;
                }
            }

            $current[$entityId] = [
                '#attributes' => [
                    'class' => ['draggable'],
                    'data-entity-id' => $entity->getEntityTypeId() . ':' . $entityId,
                    'data-row-id' => $delta,
                ],
            ];

            $current[$entityId]['display'] = [
                '#weight' => -10,
                '#theme' => 'imgix_image',
                '#url' => $this->imgixManager->getImgixUrl(
                        $entity, [
                            'auto' => 'format',
                            'fit' => 'max',
                            'h' => 150,
                            'q' => 75,
                            'w' => 150,
                        ]
                    ),
                '#title' => $title,
                '#caption' => '',
            ];

            $current[$entityId] += [
                'meta' => [
                    'display_field' => [
                        '#type' => 'checkbox',
                        '#title' => $this->t('Include file in display'),
                        '#default_value' => (bool) $fieldSettings['display_default'],
                        '#access' => $this->fieldDefinition->getType() == 'file' && $fieldSettings['display_field'],
                    ],
                    'title' => [
                        '#type' => 'textfield',
                        '#title' => $this->t('Title'),
                        '#default_value' => $title,
                        '#size' => 45,
                        '#maxlength' => 1024,
                        '#access' => $fieldSettings['title_field'],
                        '#required' => $fieldSettings['title_field_required'],
                        '#element_validate' => $fieldSettings['title_field_required'] == 1 ? [
                            [
                                get_called_class(),
                                'validateRequiredFields',
                            ],
                        ] : [],
                    ],
                    'description' => [
                        '#type' => $fileSettings->get('description.type'),
                        '#title' => $this->t('Description'),
                        '#default_value' => $description,
                        '#size' => 45,
                        '#maxlength' => $fileSettings->get('description.length'),
                        '#access' => (bool) $fieldSettings['description_field'],
                        '#required' => (bool) $fieldSettings['description_field_required'],
                        '#element_validate' => $fieldSettings['description_field_required'] == 1 ? [
                            [
                                get_called_class(),
                                'validateRequiredFields',
                            ],
                        ] : [],
                    ],
                ],
                'remove_button' => [
                    '#type' => 'submit',
                    '#value' => $this->t('Remove'),
                    '#ajax' => [
                        'callback' => [get_class($this), 'updateWidgetCallback'],
                        'wrapper' => $details_id,
                    ],
                    '#submit' => [[get_class($this), 'removeItemSubmit']],
                    '#name' => $fieldMachineName . '_remove_' . $entityId . '_' . md5(json_encode($field_parents)),
                    '#limit_validation_errors' => [array_merge($field_parents, [$fieldMachineName, 'target_id'])],
                    '#attributes' => [
                        'data-entity-id' => $entity->getEntityTypeId() . ':' . $entity->id(),
                        'data-row-id' => $delta,
                    ],
                    '#access' => (bool) $widgetSettings['field_widget_remove'],
                ],
                '_weight' => [
                    '#type' => 'weight',
                    '#title' => $this->t('Weight for row @number', ['@number' => $delta + 1]),
                    '#title_display' => 'invisible',
                    // Note: this 'delta' is the FAPI #type 'weight' element's property.
                    '#delta' => count($entities),
                    '#default_value' => $weight,
                    '#attributes' => ['class' => [$orderClass]],
                ],
            ];

            $current['#attached']['library'][] = 'entity_browser/file_browser';

            $delta++;
        }

        return $current;
    }

    protected function getPersistentData()
    {
        $data = parent::getPersistentData();
        $settings = $this->fieldDefinition->getSettings();
        // Add validators based on our current settings.
        $data['validators']['file'] = ['validators' => $this->getFileValidators()];
        // Provide context for widgets to enhance their configuration. Currently
        // we only know that "upload_location" is used.
        $data['widget_context']['upload_location'] = $settings['uri_scheme'] . '://' . $settings['file_directory'];

        return $data;
    }
}
