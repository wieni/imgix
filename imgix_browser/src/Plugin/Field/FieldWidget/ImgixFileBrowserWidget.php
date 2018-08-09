<?php

namespace Drupal\imgix_browser\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\FieldWidgetDisplayManager;
use Drupal\entity_browser\Plugin\Field\FieldWidget\EntityReferenceBrowserWidget;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Imgix Entity browser file widget.
 *
 * @FieldWidget(
 *   id = "entity_browser_imgix",
 *   label = @Translation("Imgix browser"),
 *   provider = "imgix_browser",
 *   multiple_values = TRUE,
 *   field_types = {
 *     "imgix",
 *   }
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

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The display repository service.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $displayRepository;

  /**
   * Constructs widget plugin.
   *
   * @param string $plugin_id
   *   The plugin_id for the plugin instance
   * @param mixed $plugin_definition
   *   The plugin implementation definition
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated
   * @param array $settings
   *   The widget settings
   * @param array $third_party_settings
   *   Any third party settings
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher
   * @param \Drupal\entity_browser\FieldWidgetDisplayManager $field_display_manager
   *   Field widget display plugin manager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository
   *   The entity display repository service
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, FieldWidgetDisplayManager $field_display_manager, ConfigFactoryInterface $config_factory, EntityDisplayRepositoryInterface $display_repository, ModuleHandlerInterface $module_handler)
  {
      parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $entity_type_manager, $event_dispatcher, $field_display_manager, $module_handler);
      $this->entityTypeManager = $entity_type_manager;
      $this->fieldDisplayManager = $field_display_manager;
      $this->configFactory = $config_factory;
      $this->displayRepository = $display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
      return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('plugin.manager.entity_browser.field_widget_display'),
      $container->get('config.factory'),
      $container->get('entity_display.repository'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings()
  {
      $settings = parent::defaultSettings();

      // These settings are hidden.
      unset($settings['field_widget_display']);
      unset($settings['field_widget_display_settings']);

      return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state)
  {
      $element = parent::settingsForm($form, $form_state);

      $element['field_widget_display']['#access'] = false;
      $element['field_widget_display_settings']['#access'] = false;

      return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary()
  {
      $summary = $this->summaryBase();

      return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)
  {
      $this->items = $items;
      $return = parent::formElement($items, $delta, $element, $form, $form_state);

      if (!empty($return['current'])) {
          $return['current']['#attached']['library'][] = 'imgix_browser/imgix.widget';
      }

      // This is so good but breaks entity_browser :(
      // if (!empty($return['entity_browser'])) {
      //    // Put the button to add more on the footer.
      //    $return['entity_browser']["#weight"] = 101;
      // }

      return $return;
  }

  /**
   * {@inheritdoc}
   */
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
    $max_filesize = Bytes::toInt(file_upload_max_size());
      if (!empty($settings['max_filesize'])) {
          $max_filesize = min($max_filesize, Bytes::toInt($settings['max_filesize']));
      }

    // There is always a file size limit due to the PHP server limit.
    $validators['file_validate_size'] = [$max_filesize];

    // Images have expected defaults for file extensions.
    // See \Drupal\image\Plugin\Field\FieldWidget::formElement() for details.
    if ($this->fieldDefinition->getType() == 'image') {
        // If not using custom extension validation, ensure this is an image.
      $supported_extensions = ['png', 'gif', 'jpg', 'jpeg'];
        $extensions = isset($settings['file_extensions']) ? $settings['file_extensions'] : implode(' ', $supported_extensions);
        $extensions = array_intersect(explode(' ', $extensions), $supported_extensions);
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
    public static function validateRequiredFields(
        $element,
        FormStateInterface $form_state
    ) {
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

    /**
     * {@inheritdoc}
     */
    protected function displayCurrentSelection($details_id, $field_parents, $entities)
    {
        $field_type = $this->fieldDefinition->getType();
        $field_settings = $this->fieldDefinition->getSettings();
        $field_machine_name = $this->fieldDefinition->getName();
        $file_settings = $this->configFactory->get('file.settings');
        $widget_settings = $this->getSettings();

        $can_edit = (bool) $widget_settings['field_widget_edit'];

        $delta = 0;

        $order_class = $field_machine_name . '-delta-order';

        $current = [
            '#type' => 'table',
            '#empty' => $this->t('No files yet'),
            '#attributes' => ['class' => ['entities-list']],
            '#tabledrag' => [
                [
                    'action' => 'order',
                    'relationship' => 'sibling',
                    'group' => $order_class,
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
                $can_edit = false;
            }

            $entity_id = $entity->id();

            $display_field = $field_settings['display_default'];

            // Find the default description.
            $title = '';
            $description = '';
            $weight = $delta;
            foreach ($this->items as $item) {
                if ($item->target_id == $entity_id && get_class($item) == 'Drupal\imgix\Plugin\Field\FieldType\ImgixFieldType') {
                    $title = $item->getTitle();
                    $description = $item->getCaption();
                    $weight = $item->_weight ?: $delta;
                }
            }

            $current[$entity_id] = [
                '#attributes' => [
                    'class' => ['draggable'],
                    'data-entity-id' => $entity->getEntityTypeId() . ':' . $entity_id,
                    'data-row-id' => $delta,
                ],
            ];

            $current[$entity_id]['display'] = [
                '#weight' => -10,
                '#theme' => 'imgix_image',
                '#url' => \Drupal::service('imgix.manager')
                    ->getImgixUrl(
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

            $current[$entity_id] += [
                'meta' => [
                    'display_field' => [
                        '#type' => 'checkbox',
                        '#title' => $this->t('Include file in display'),
                        '#default_value' => (bool) $display_field,
                        '#access' => $field_type == 'file' && $field_settings['display_field'],
                    ],
                    'title' => [
                        '#type' => 'textfield',
                        '#title' => $this->t('Title'),
                        '#default_value' => $title,
                        '#size' => 45,
                        '#maxlength' => 1024,
                        '#access' => $field_settings['title_field'],
                        '#required' => $field_settings['title_field_required'],
                        '#element_validate' => $field_settings['title_field_required'] == 1 ? array(
                            array(
                                get_called_class(),
                                'validateRequiredFields',
                            ),
                        ) : array(),
                    ],
                    'description' => [
                        '#type' => $file_settings->get('description.type'),
                        '#title' => $this->t('Description'),
                        '#default_value' => $description,
                        '#size' => 45,
                        '#maxlength' => $file_settings->get('description.length'),
                        '#access' => (bool) $field_settings['description_field'],
                        '#required' => (bool) $field_settings['description_field_required'],
                        '#element_validate' => $field_settings['description_field_required'] == 1 ? array(
                            array(
                                get_called_class(),
                                'validateRequiredFields',
                            ),
                        ) : array(),
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
                    '#name' => $field_machine_name . '_remove_' . $entity_id . '_' . md5(json_encode($field_parents)),
                    '#limit_validation_errors' => [array_merge($field_parents, [$field_machine_name, 'target_id'])],
                    '#attributes' => [
                        'data-entity-id' => $entity->getEntityTypeId() . ':' . $entity->id(),
                        'data-row-id' => $delta,
                    ],
                    '#access' => (bool) $widget_settings['field_widget_remove'],
                ],
                '_weight' => [
                    '#type' => 'weight',
                    '#title' => $this->t('Weight for row @number', ['@number' => $delta + 1]),
                    '#title_display' => 'invisible',
                    // Note: this 'delta' is the FAPI #type 'weight' element's property.
                    '#delta' => count($entities),
                    '#default_value' => $weight,
                    '#attributes' => ['class' => [$order_class]],
                ],
            ];

            $current['#attached']['library'][] = 'entity_browser/file_browser';

            $delta++;
        }

        return $current;
    }

  /**
   * {@inheritdoc}
   */
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
