<?php

namespace Drupal\imgix\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\imgix\ImgixManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ImgixPresets extends FormBase
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var ImgixManagerInterface */
    protected $imgixManager;

    public static function create(ContainerInterface $container)
    {
        $instance = parent::create($container);
        $instance->entityTypeManager = $container->get('entity_type.manager');
        $instance->imgixManager = $container->get('imgix.manager');

        return $instance;
    }

    public function getFormId()
    {
        return 'imgix_profiles';
    }

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
            $form['presets'][$key]['key'] = [
                '#markup' => $preset['key'],
                '#title' => $this->t('Key'),
                '#title_display' => 'invisible',
            ];

            $form['presets'][$key]['query'] = [
                '#markup' => $preset['query'],
                '#title' => $this->t('Parameter string'),
                '#title_display' => 'invisible',
            ];

            $form['presets'][$key]['operations'] = [
                '#type' => 'dropbutton',
                '#links' => [
                    'edit' => [
                        'url' => Url::fromRoute(
                            'imgix.presets.add',
                            [
                                'key' => $key,
                            ],
                            [
                                'query' => [
                                    'destination' => Url::fromRoute(
                                        'imgix.presets'
                                    )->toString(),
                                ],
                            ]
                        ),
                        'title' => $this->t('Edit'),
                    ],
                    'delete' => [
                        'url' => Url::fromRoute(
                            'imgix.presets.delete',
                            [
                                'key' => $key,
                            ],
                            [
                                'query' => [
                                    'destination' => Url::fromRoute(
                                        'imgix.presets'
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

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
    }
}
