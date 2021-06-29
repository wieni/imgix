<?php

namespace Drupal\imgix\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;

/**
 * @deprecated Use the `image` field widget from the `image` core module instead.
 *
 * @FieldWidget(
 *     id = "imgix",
 *     label = @Translation("Imgix image"),
 *     field_types = {
 *         "imgix"
 *     }
 * )
 */
class DoNotUseImgixWidget extends FileWidget
{
    public static function defaultSettings()
    {
        return [
            'preview_preset' => 'thumb',
        ] + parent::defaultSettings();
    }

    public function settingsForm(array $form, FormStateInterface $form_state)
    {
        $element = [];

        $element['do_not_use'] = [
            '#markup' => sprintf('<p>%s</p>', $this->t('This field type should not be used anymore. 
                Please migrate this field to an Image field, see UPGRADING.md in the module folder for instructions.'))
        ];

        return $element;
    }

    public function settingsSummary()
    {
        return [
            $this->t('This field type should not be used anymore.'),
        ];
    }

    public function formElement(
        FieldItemListInterface $items,
        $delta,
        array $element,
        array &$form,
        FormStateInterface $form_state
    ) {
        $element['do_not_use'] = [
            '#markup' => sprintf('<p>%s</p>', $this->t('This field type should not be used anymore. 
                Please migrate this field to an Image field, see UPGRADING.md in the module folder for instructions.'))
        ];

        return $element;
    }
}
