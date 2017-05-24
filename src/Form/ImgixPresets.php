<?php

/**
 * @file
 * Contains \Drupal\imgix\Form\ImgixPresets
 */
namespace Drupal\imgix\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;

use Drupal\imgix\ImgixManagerInterface;

class ImgixPresets extends FormBase
{

    protected $entityTypeManager;
    protected $imgixManager;

    /**
     * Constructs a \Drupal\system\ConfigFormBase object.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     * @param \Drupal\imgix\ImgixManagerInterface            $imgixManager
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        ImgixManagerInterface $imgixManager
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->imgixManager = $imgixManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('entity_type.manager'),
            $container->get('imgix.manager')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'imgix_profiles';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['presets'] = [
            '#type' => 'table',
            '#header' => [
                $this->t('Key'),
                $this->t('Parameter string'),
                $this->t('Operations'),
            ],
        ];

        foreach ($this->imgixManager->getPresets() as $key => $preset) {
            $form['presets'][$key]['key'] = array(
                '#markup' => $preset['key'],
                '#title' => $this->t('Key'),
                '#title_display' => 'invisible',
            );
            $form['presets'][$key]['query'] = array(
                '#markup' => $preset['query'],
                '#title' => $this->t('Parameter string'),
                '#title_display' => 'invisible',
            );
            $form['presets'][$key]['operations'] = [
                '#type' => 'dropbutton',
                '#links' => [
                    'edit' => [
                        'url' => Url::fromRoute(
                            "imgix.presets.add",
                            [
                                'key' => $key,
                            ],
                            [
                                'query' => [
                                    'destination' => Url::fromRoute(
                                        "imgix.presets"
                                    )->toString(),
                                ],
                            ]
                        ),
                        'title' => $this->t('Edit'),
                    ],
                    'delete' => [
                        'url' => Url::fromRoute(
                            "imgix.presets.delete",
                            [
                                'key' => $key,
                            ],
                            [
                                'query' => [
                                    'destination' => Url::fromRoute(
                                        "imgix.presets"
                                    )->toString(),
                                ],
                            ]
                        ),
                        'title' => $this->t('Delete'),
                    ],
                ],
            ];
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        parent::submitForm($form, $form_state);
    }
}
