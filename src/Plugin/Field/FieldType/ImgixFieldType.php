<?php

namespace Drupal\imgix\Plugin\Field\FieldType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;

/**
* @FieldType(
*   id = "imgix",
*   label = @Translation("Imgix image"),
*   description = @Translation("This field stores the ID of a file as an string value."),
*   category = @Translation("Reference"),
*   default_widget = "imgix",
*   default_formatter = "imgix_formatter",
*   column_groups = {
*     "file" = {
*       "label" = @Translation("File"),
*       "columns" = {
*         "target_id", "width", "height"
*       },
*       "require_all_groups_for_translation" = TRUE
*     },
*     "title" = {
*       "label" = @Translation("Title"),
*       "translatable" = TRUE
*     },
*   },
*   list_class = "\Drupal\file\Plugin\Field\FieldType\FileFieldItemList",
*   constraints = {"ReferenceAccess" = {}, "FileValidation" = {}}
* )
*/

class ImgixFieldType extends FileItem
{
    /**
     * {@inheritdoc}
     */
    public static function defaultFieldSettings() 
    {
        $settings = array(
                'file_extensions' => 'png gif jpg jpeg',
                'description_field_required' => 0,
                'title_field' => 0,
                'title_field_required' => 0,
            ) + parent::defaultFieldSettings();
        
        return $settings;
    }
    
    /**
     * {@inheritdoc}
     */
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
    
    /**
     * {@inheritdoc}
     */
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
    
    /**
     * {@inheritdoc}
     */
    public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data)
    {
        $element = parent::storageSettingsForm($form, $form_state, $has_data);
        
        unset($element['display_field']);
        unset($element['display_default']);
        
        return $element;
    }
    
    
    /**
     * {@inheritdoc}
     */
    public function fieldSettingsForm(array $form, FormStateInterface $form_state) 
    {
        // Get base form from FileItem.
        $element = parent::fieldSettingsForm($form, $form_state);
    
        $settings = $this->getSettings();

        // That's right no shame.
        if (isset($element['description_field'])) {
            $element['description_field']['#title'] = new TranslatableMarkup('Enable <em>Caption</em> field');
            $element['description_field']['#description'] = new TranslatableMarkup('The caption field allows users to enter a caption for the image.');
            
            $element['description_field_required'] = array(
                '#type' => 'checkbox',
                '#title' => t('<em>Caption</em> field required'),
                '#default_value' => $settings['description_field_required'],
                '#weight' => $element['description_field']['#weight'],
                '#states' => array(
                    'visible' => array(
                        ':input[name="settings[description_field]"]' => array('checked' => true),
                    ),
                ),
            );
        }
        
        // Add title configuration options.
        $element['title_field'] = array(
            '#type' => 'checkbox',
            '#title' => t('Enable <em>Title</em> field'),
            '#default_value' => $settings['title_field'],
            '#description' => t('The title attribute is used as a tooltip when the mouse hovers over the image. Enabling this field is not recommended as it can cause problems with screen readers.'),
            '#weight' => 11,
        );
        $element['title_field_required'] = array(
            '#type' => 'checkbox',
            '#title' => t('<em>Title</em> field required'),
            '#default_value' => $settings['title_field_required'],
            '#weight' => 12,
            '#states' => array(
                'visible' => array(
                    ':input[name="settings[title_field]"]' => array('checked' => true),
                ),
            ),
        );

        return $element;
    }
    
    /**
     * This is separate because it should be dep injection but fieldType cannot
     * cope with with that yet
     */
    protected function getImgixService()
    {
        return \Drupal::service('vrt_imagestore.manager');
    }
}
