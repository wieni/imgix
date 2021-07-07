<?php

namespace Drupal\imgix\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldFormatter\GenericFileFormatter;

/**
 * Plugin implementation of the 'imgix' formatter.
 * @deprecated Use the `image` field formatter from the `image` core module instead.
 *
 * @FieldFormatter(
 *     id = "imgix_formatter",
 *     label = @Translation("Imgix"),
 *     field_types = {
 *         "imgix"
 *     }
 * )
 */
class DoNotUseImgixFormatter extends GenericFileFormatter
{
    public static function defaultSettings()
    {
        return [
            'image_preset' => '',
            'image_link' => '',
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

    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        $element = [];

        $element['do_not_use'] = [
            '#markup' => sprintf('<p>%s</p>', $this->t('This field type should not be used anymore. 
                Please migrate this field to an Image field, see UPGRADING.md in the module folder for instructions.'))
        ];

        return $element;
    }
}
