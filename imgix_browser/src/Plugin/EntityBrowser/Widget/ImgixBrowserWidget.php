<?php

namespace Drupal\imgix_browser\Plugin\EntityBrowser\Widget;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\WidgetBase;
use Drupal\entity_browser\WidgetValidationManager;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\imgix\ImgixManagerInterface;

/**
 * Uses a view to provide entity listing in a browser's widget.
 *
 * @EntityBrowserWidget(
 *   id = "imgix",
 *   label = @Translation("Imgix"),
 *   provider = "imgix_browser",
 *   description = @Translation("Image listings for imgix fields"),
 *   auto_select = TRUE
 * )
 */
class ImgixBrowserWidget extends WidgetBase implements ContainerFactoryPluginInterface
{
    /** @var  ImgixManagerInterface */
    protected $imgixManager;

    /**
     * {@inheritdoc}
     */
    public function defaultConfiguration() {
        return array(
                'preset' => NULL,
            ) + parent::defaultConfiguration();
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('event_dispatcher'),
            $container->get('entity_type.manager'),
            $container->get('plugin.manager.entity_browser.widget_validation'),
            $container->get('imgix.manager')
        );
    }

    /**
     * Constructs a new View object.
     *
     * @param array $configuration
     *   A configuration array containing information about the plugin instance.
     * @param string $plugin_id
     *   The plugin_id for the plugin instance.
     * @param mixed $plugin_definition
     *   The plugin implementation definition.
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
     *   Event dispatcher service.
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     *   The entity type manager.
     * @param \Drupal\entity_browser\WidgetValidationManager $validation_manager
     *   The Widget Validation Manager service.
     * @param ImgixManagerInterface $imgixManager
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        EventDispatcherInterface $event_dispatcher,
        EntityTypeManagerInterface $entity_type_manager,
        WidgetValidationManager $validation_manager,
        ImgixManagerInterface $imgixManager
    ) {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager);
        $this->imgixManager = $imgixManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters)
    {
        $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);

        $pagerKey = 666;

        $query = $this->entityTypeManager->getStorage('file')->getQuery('AND');
        $ids = $query
            ->pager(10, $pagerKey)
            ->sort('fid', 'DESC')
            ->execute();

        $files = $this->entityTypeManager->getStorage('file')->loadMultiple($ids);

        // Get the selected preset.
        $presets = $this->imgixManager->getPresets();
        $params = [];
        if (!empty($presets[$this->configuration['preset']])) {
            foreach (explode('&', $presets[$this->configuration['preset']]['query']) as $item1) {
                $item2 = explode('=', $item1);
                $params[$item2[0]] = $item2[1];
            }
        }

        $form['view']['view'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => 'imgix-browser-container',
            ],
            '#tree' => true,
        ];

        /** @var File $file */
        foreach ($files as $file) {
            $entityBrowserKey = 'file:' . $file->id();

            $form['view']['view'][$entityBrowserKey] = [
                '#type' => 'container',
                '#attributes' => [
                    'class' => 'imgix-browser-item',
                ],
            ];

            $form['view']['view'][$entityBrowserKey]['checkbox'] = [
                '#type' => 'checkbox',
                '#title' => $this->t('Select this item'),
                '#title_display' => 'invisible',
                '#return_value' => $entityBrowserKey,
                '#attributes' => ['name' => "entity_browser_select[$entityBrowserKey]"],
                '#default_value' => NULL,
            ];
            $form['view']['view'][$entityBrowserKey]['file'] = [
                '#markup' => $file->id(),
            ];
            $form['view']['view'][$entityBrowserKey]['preview'] = [
                '#weight' => -10,
                '#theme' => 'imgix_image',
                '#url' => $this->imgixManager->getImgixUrl($file, $params),
                '#title' => '',
                '#caption' => '',
            ];
        }

        $form['view']['pager_pager'] = [
            '#type' => 'pager',
            '#element' => $pagerKey
        ];

        return $form;
    }

    /**
     * Sets the #checked property when rebuilding form.
     *
     * Every time when we rebuild we want all checkboxes to be unchecked.
     *
     * @see \Drupal\Core\Render\Element\Checkbox::processCheckbox()
     */
    public static function processCheckbox(&$element, FormStateInterface $form_state, &$complete_form) {
        $element['#checked'] = FALSE;
        return $element;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(array &$form, FormStateInterface $form_state) {
        $user_input = $form_state->getUserInput();

        if (isset($user_input['entity_browser_select'])) {
            $selected_rows = array_values(array_filter($user_input['entity_browser_select']));
            foreach ($selected_rows as $row) {
                if (is_string($row) && $parts = explode(':', $row, 2)) {
                    // Make sure we have a type and id present.
                    if (count($parts) == 2) {
                        try {
                            $storage = $this->entityTypeManager->getStorage($parts[0]);
                            if (!$storage->load($parts[1])) {
                                $message = $this->t('The @type Entity @id does not exist.', [
                                    '@type' => $parts[0],
                                    '@id' => $parts[1],
                                ]);
                                $form_state->setError($form['widget']['view']['entity_browser_select'], $message);
                            }
                        }
                        catch (PluginNotFoundException $e) {
                            $message = $this->t('The Entity Type @type does not exist.', [
                                '@type' => $parts[0],
                            ]);
                            $form_state->setError($form['widget']['view']['entity_browser_select'], $message);
                        }
                    }
                }
            }

            // If there weren't any errors set, run the normal validators.
            if (empty($form_state->getErrors())) {
                parent::validate($form, $form_state);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareEntities(array $form, FormStateInterface $form_state) {
        $selected_rows = array_values(array_filter($form_state->getUserInput()['entity_browser_select']));
        $entities = [];
        foreach ($selected_rows as $row) {
            list($type, $id) = explode(':', $row);
            $storage = $this->entityTypeManager->getStorage($type);
            if ($entity = $storage->load($id)) {
                $entities[] = $entity;
            }
        }
        return $entities;
    }

    /**
     * {@inheritdoc}
     */
    public function submit(array &$element, array &$form, FormStateInterface $form_state) {
        $entities = $this->prepareEntities($form, $form_state);
        $this->selectEntities($entities, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
        $form = parent::buildConfigurationForm($form, $form_state);

        $options = [];
        // Get the list of available presets.
        foreach ($this->imgixManager->getPresets() as $preset) {
            $options[$preset['key']] = $preset['key'];
        }

        $form['preset'] = [
            '#type' => 'select',
            '#title' => $this->t('Imgix preset'),
            '#default_value' => $this->configuration['preset'],
            '#options' => $options,
            '#empty_option' => $this->t('- Select a preset -'),
            '#required' => TRUE,
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
        $values = $form_state->getValues()['table'][$this->uuid()]['form'];
        $this->configuration['submit_text'] = $values['submit_text'];
        $this->configuration['auto_select'] = $values['auto_select'];
        $this->configuration['preset'] = $values['preset'];
    }
}
