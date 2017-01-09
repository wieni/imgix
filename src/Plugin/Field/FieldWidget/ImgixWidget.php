<?php

namespace Drupal\imgix\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;

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
class ImgixWidget extends FileWidget
{
    /**
     * {@inheritdoc}
     */
    public function settingsForm(array $form, FormStateInterface $form_state)
    {
        return [];
    }
    
    /**
     * {@inheritdoc}
     */
    public function settingsSummary()
    {
        return [];
    }
    
    /**
     * Overrides \Drupal\file\Plugin\Field\FieldWidget\FileWidget::formMultipleElements().
     *
     * Special handling for draggable multiple widgets and 'add more' button.
     */
    protected function formMultipleElements(
        FieldItemListInterface $items,
        array &$form,
        FormStateInterface $form_state
    ) {
        $elements = parent::formMultipleElements($items, $form, $form_state);
        
        $cardinality = $this->fieldDefinition->getFieldStorageDefinition()
            ->getCardinality();
        $file_upload_help = array(
            '#theme' => 'file_upload_help',
            '#description' => '',
            '#upload_validators' => $elements[0]['#upload_validators'],
            '#cardinality' => $cardinality,
        );
        if ($cardinality == 1) {
            // If there's only one field, return it as delta 0.
            if (empty($elements[0]['#default_value']['fids'])) {
                $file_upload_help['#description'] = $this->getFilteredDescription();
                $elements[0]['#description'] = \Drupal::service('renderer')
                    ->renderPlain($file_upload_help);
            }
        }
        else {
            $elements['#file_upload_description'] = $file_upload_help;
        }
        
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
        $element = parent::formElement(
            $items, $delta, $element, $form,
            $form_state
        );
        
        $field_settings = $this->getFieldSettings();
        
        // If not using custom extension validation, ensure this is an image.
        $supported_extensions = array('png', 'gif', 'jpg', 'jpeg');
        $extensions = isset($element['#upload_validators']['file_validate_extensions'][0]) ? $element['#upload_validators']['file_validate_extensions'][0] : implode(
            ' ',
            $supported_extensions
        );
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
     */
    public static function process(
        $element,
        FormStateInterface $form_state,
        $form
    ) {
        $item = $element['#value'];
        $item['fids'] = $element['fids']['#value'];
        
        $element['#theme'] = 'imgix_widget';
        
        // Add the image preview.
        if (!empty($element['#files'])) {
            $file = reset($element['#files']);

            if (!empty($element['#value']['target_id'])) {
                $url = \Drupal::service('imgix.manager')
                    ->getImgixUrl(
                        $file, [
                        'auto' => 'format',
                        'fit' => 'max',
                        'h' => 150,
                        'q' => 75,
                        'w' => 150,
                        ]
                    );
                
                $element['preview'] = array(
                    '#weight' => -10,
                    '#theme' => 'imgix_image',
                    '#url' => $url,
                    '#title' => isset($item['title']) ? $item['title'] : '',
                    '#caption' => '',
                );
            }
        }
        
        $element['title'] = array(
            '#type' => 'textfield',
            '#title' => t('Title'),
            '#default_value' => isset($item['title']) ? $item['title'] : '',
            '#description' => t('The title is used as a tool tip when the user hovers the mouse over the image.'),
            '#maxlength' => 1024,
            '#weight' => -10,
            '#access' => (bool) $item['fids'] && $element['#title_field'],
            '#required' => $element['#title_field_required'],
            '#element_validate' => $element['#title_field_required'] == 1 ? array(
                array(
                    get_called_class(),
                    'validateRequiredFields',
                ),
            ) : array(),
        );
        
        $element['#description_display'] = 'before';
        
        return parent::process($element, $form_state, $form);
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
        }
        else {
            $form_state->setLimitValidationErrors([]);
        }
    }
    
}
