<?php

namespace Drupal\imgix\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\file\FileInterface;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\imgix\ImgixManagerInterface;
use Drupal\imgix\Plugin\ImageToolkit\ImgixToolkit;

/**
 * @deprecated Use the `image` field type from the `image` core module instead.
 *
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
class DoNotUseImgixFieldType extends FileItem
{
    public static function defaultFieldSettings()
    {
        return [
            'file_extensions' => ImgixToolkit::getSupportedExtensions(),
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
        $element = [];

        $element['do_not_use'] = [
            '#markup' => sprintf('<p>%s</p>', $this->t('This field type should not be used anymore. 
                Please migrate this field to an Image field, see UPGRADING.md in the module folder for instructions.'))
        ];

        return $element;
    }

    public function fieldSettingsForm(array $form, FormStateInterface $form_state)
    {
        $element = [];

        $element['do_not_use'] = [
            '#markup' => sprintf('<p>%s</p>', $this->t('This field type should not be used anymore. 
                Please migrate this field to an Image field, see UPGRADING.md in the module folder for instructions.'))
        ];

        return $element;
    }
}
