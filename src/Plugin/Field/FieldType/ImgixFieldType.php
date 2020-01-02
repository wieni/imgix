<?php

namespace Drupal\imgix\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\file\FileInterface;
use Drupal\file\Plugin\Field\FieldType\FileItem;

/**
 * @FieldType(
 *     id = "imgix",
 *     label = @Translation("Imgix image"),
 *     description = @Translation("This field stores the ID of a file as an string value."),
 *     category = @Translation("Reference"),
 *     default_widget = "imgix",
 *     default_formatter = "imgix_formatter",
 *     column_groups = {
 *         "file" : {
 *             "label" : @Translation("File"),
 *             "columns" : {
 *                 "target_id", "width", "height"
 *             },
 *             "require_all_groups_for_translation" : TRUE
 *         },
 *         "title" : {
 *             "label" : @Translation("Title"),
 *             "translatable" : TRUE
 *         },
 *     },
 *     list_class = "\Drupal\file\Plugin\Field\FieldType\FileFieldItemList",
 *     constraints = {"ReferenceAccess" : {}, "FileValidation" : {}}
 * )
 */
class ImgixFieldType extends FileItem
{
    public static function defaultFieldSettings()
    {
        return [
            'file_extensions' => 'png gif jpg jpeg svg jfif',
            'description_field_required' => 0,
            'title_field' => 0,
            'title_field_required' => 0,
        ] + parent::defaultFieldSettings();
    }

    public function getFile(): ?FileInterface
    {
        return $this->entity;
    }

    public function getCaption(): ?string
    {
        return $this->get('description')->getValue();
    }

    public function getTitle(): ?string
    {
        return $this->get('title')->getValue();
    }

    public static function schema(FieldStorageDefinitionInterface $field_definition)
    {
        $schema = parent::schema($field_definition);

        unset($schema['columns']['display']);

        $schema['columns']['title'] = [
            'description' => "Image title text, for the image's 'title' attribute.",
            'type' => 'varchar',
            'length' => 1024,
        ];
        $schema['columns']['width'] = [
            'description' => 'The width of the image in pixels.',
            'type' => 'int',
            'unsigned' => true,
        ];
        $schema['columns']['height'] = [
            'description' => 'The height of the image in pixels.',
            'type' => 'int',
            'unsigned' => true,
        ];

        return $schema;
    }

    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition)
    {
        $properties = parent::propertyDefinitions($field_definition);

        unset($properties['display']);

        $properties['title'] = DataDefinition::create('string')
            ->setLabel(t('Title'));
        $properties['width'] = DataDefinition::create('integer')
            ->setLabel(t('Width'));
        $properties['height'] = DataDefinition::create('integer')
            ->setLabel(t('Height'));

        return $properties;
    }

    public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data)
    {
        $element = parent::storageSettingsForm($form, $form_state, $has_data);

        unset($element['display_field']);
        unset($element['display_default']);

        return $element;
    }

    public function fieldSettingsForm(array $form, FormStateInterface $form_state)
    {
        $element = parent::fieldSettingsForm($form, $form_state);
        $settings = $this->getSettings();

        if (isset($element['description_field'])) {
            $element['description_field']['#title'] = $this->t('Enable <em>Caption</em> field');
            $element['description_field']['#description'] = $this->t('The caption field allows users to enter a caption for the image.');

            $element['description_field_required'] = [
                '#type' => 'checkbox',
                '#title' => $this->t('<em>Caption</em> field required'),
                '#default_value' => $settings['description_field_required'],
                '#weight' => $element['description_field']['#weight'],
                '#states' => [
                    'visible' => [
                        ':input[name="settings[description_field]"]' => ['checked' => true],
                    ],
                ],
            ];
        }

        $element['title_field'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Enable <em>Title</em> field'),
            '#default_value' => $settings['title_field'],
            '#description' => $this->t('The title attribute is used as a tooltip when the mouse hovers over the image. Enabling this field is not recommended as it can cause problems with screen readers.'),
            '#weight' => 11,
        ];

        $element['title_field_required'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('<em>Title</em> field required'),
            '#default_value' => $settings['title_field_required'],
            '#weight' => 12,
            '#states' => [
                'visible' => [
                    ':input[name="settings[title_field]"]' => ['checked' => true],
                ],
            ],
        ];

        return $element;
    }
}
