<?php

namespace Drupal\imgix\Plugin\ImageToolkit;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\ImageToolkit\ImageToolkitBase;
use Drupal\Core\ImageToolkit\ImageToolkitInterface;
use Drupal\Core\ImageToolkit\ImageToolkitManager;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ImageToolkit(
 *     id = "imgix",
 *     title = @Translation("Imgix toolkit")
 * )
 */
class ImgixToolkit extends ImageToolkitBase implements ImgixToolkitInterface
{
    /** @var int */
    protected $width;
    /** @var int */
    protected $height;

    /** @var ImageToolkitManager */
    protected $imageToolkitManager;
    /** @var array */
    protected $params = [];
    /** @var ImageToolkitInterface */
    protected $fallbackToolkit;

    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        $instance = new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('image.toolkit.operation.manager'),
            $container->get('logger.channel.image'),
            $container->get('config.factory')
        );
        $instance->imageToolkitManager = $container->get('image.toolkit.manager');

        return $instance;
    }

    public function isValid(): bool
    {
        if ($this->useFallbackToolkit()) {
            return $this->getFallbackToolkit()->isValid();
        }

        // Let's assume the image is valid, this toolkit doesn't care
        return true;
    }

    public function save($destination)
    {
        if ($this->useFallbackToolkit($destination)) {
            return $this->getFallbackToolkit()->save($destination);
        }

        // No changes to the actual files are necessary to use this toolkit
        return true;
    }

    public function parseFile(): bool
    {
        if ($this->useFallbackToolkit()) {
            return $this->getFallbackToolkit()->parseFile();
        }

        // Let's assume the image is valid, this toolkit doesn't care
        return false;
    }

    public function getWidth(): ?int
    {
        if ($this->useFallbackToolkit()) {
            return $this->getFallbackToolkit()->getWidth();
        }

        if (isset($this->width)) {
            return $this->width;
        }

        $this->loadInfo();

        return $this->width;
    }

    public function getHeight(): ?int
    {
        if ($this->useFallbackToolkit()) {
            return $this->getFallbackToolkit()->getHeight();
        }

        if (isset($this->height)) {
            return $this->height;
        }

        $this->loadInfo();

        return $this->height;
    }

    public function getMimeType(): ?string
    {
        if ($this->useFallbackToolkit()) {
            return $this->getFallbackToolkit()->getMimeType();
        }

        return null;
    }

    public function getParameter(string $key)
    {
        return $this->params[$key] ?? null;
    }

    public function setParameter(string $key, string $value)
    {
        $this->params[$key] = $value;

        return $this;
    }

    public function unsetParameter(string $key)
    {
        if (isset($this->params[$key])) {
            unset($this->params[$key]);
        }

        return $this;
    }

    public function mergeParameters(array $params)
    {
        $this->params = array_merge($this->params, $params);

        return $this;
    }

    public function getParameters(): array
    {
        return $this->params;
    }

    public function getSourceDomain(): ?string
    {
        return $this->configFactory
            ->get('imgix.settings')
            ->get('source_domain');
    }

    public function getExternalCdnDomain(): ?string
    {
        return $this->configFactory
            ->get('imgix.settings')
            ->get('external_cdn');
    }

    public function getMappingType(): ?string
    {
        return $this->configFactory
            ->get('imgix.settings')
            ->get('mapping_type');
    }

    public function getMappingTypes(): array
    {
        return [
            static::SOURCE_FOLDER => 'Web Folder',
            static::SOURCE_PROXY => 'Web Proxy',
            static::SOURCE_S3 => 'Amazon S3',
            static::SOURCE_GCS => 'Google Cloud Storage',
        ];
    }

    public function getPathPrefix(): ?string
    {
        return $this->configFactory
            ->get('imgix.settings')
            ->get('path_prefix');
    }

    public function usesHttps(): bool
    {
        return (bool) $this->configFactory
            ->get('imgix.settings')
            ->get('https');
    }

    public function getSecureUrlToken(): ?string
    {
        return $this->configFactory
            ->get('imgix.settings')
            ->get('secure_url_token');
    }

    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->configFactory->get('imgix.settings');

        $form['source_domain'] = [
            '#type' => 'textfield',
            '#required' => true,
            '#title' => $this->t('Source domain'),
            '#description' => $this->t('The Imgix domain from which your images are served. Usually, this is a subdomain of imgix.net.'),
            '#default_value' => $config->get('source_domain'),
        ];

        $form['external_cdn'] = [
            '#type' => 'textfield',
            '#title' => $this->t('External CDN'),
            '#description' => $this->t('The domain of an external CDN through which the images should be served, instead of the source domain.'),
            '#default_value' => $config->get('source_domain'),
        ];

        $form['secure_url_token'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Secure URL Token'),
            '#description' => $this->t('Signing URLs using a token prevents unauthorized parties from changing the parameters.'),
            '#default_value' => $config->get('secure_url_token'),
        ];

        $form['mapping_type'] = [
            '#type' => 'radios',
            '#required' => true,
            '#title' => $this->t('Mapping type'),
            '#description' => $this->t('The way Imgix connects to your image storage.'),
            '#options' => $this->getMappingTypes(),
            '#default_value' => $config->get('mapping_type'),
        ];

        $form['mapping_type'][static::SOURCE_S3]['#description'] = $this->t("An Amazon S3 Source connects to an existing Amazon S3 bucket. imgix connects using the credentials you supply, so images don't have to be public.");
        $form['mapping_type'][static::SOURCE_GCS]['#description'] = $this->t("A Google Cloud Storage Source connects to an existing Google Cloud Storage bucket. imgix connects using the credentials you supply, so images don't have to be public.");
        $form['mapping_type'][static::SOURCE_FOLDER]['#description'] = $this->t('A Web Folder Source connects to your existing folder of images that are on a publicly addressable website, usually your website’s existing image folder.');
        $form['mapping_type'][static::SOURCE_PROXY]['#description'] = $this->t('A Web Proxy Source allows you to connect to any image that is addressable through a publicly-available URL. You provide the entire image URL of the master image in the path of the imgix request.');

        $form['path_prefix'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Path prefix'),
            '#description' => $this->t('A path prefix that should be removed from the image path. When using a GCS or S3 source, this should be the bucket name. This can also be useful in case your images are stored in a subfolder.'),
            '#default_value' => $config->get('path_prefix'),
        ];

        $form['https'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('HTTPS support'),
            '#default_value' => $config->get('https'),
        ];

        foreach (Element::children($form) as $child) {
            if ($config->get($child) !== $config->getOriginal($child, false)) {
                $form[$child]['#disabled'] = true;
                $form[$child]['#description'] = $this->t('This config cannot be changed because it is overridden.');
            }
        }

        return $form;
    }

    public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void
    {
        $config = $this->configFactory->getEditable('imgix.settings');
        $values = $form_state->getValue('imgix');

        foreach (Element::children($form) as $key) {
            if (!array_key_exists($key, $values)) {
                continue;
            }

            if ($config->get($key) !== $config->getOriginal($key, false)) {
                // Value has override
                continue;
            }

            $config->set($key, $values[$key]);
        }

        $config->save();
    }

    public function useFallbackToolkit($destination = null): bool
    {
        $mappingType = $this->getMappingType();
        $urlScheme = parse_url($destination ?: $this->getSource(), PHP_URL_SCHEME);

        if ($urlScheme === null) {
            return true;
        }

        if ($mappingType === static::SOURCE_PROXY && $urlScheme === 'private') {
            return true;
        }

        if ($mappingType === static::SOURCE_S3 && $urlScheme !== 's3') {
            return true;
        }

        if ($mappingType === static::SOURCE_GCS && $urlScheme !== 'gcs') {
            return true;
        }

        return false;
    }

    public static function isAvailable(): bool
    {
        return true;
    }

    /**
     * @see https://docs.imgix.com/apis/rendering/format/fm
     */
    public static function getSupportedExtensions(): array
    {
        return [
            'gif', 'jp2', 'jpg', 'jpeg', 'json', 'jxr', 'pjpg', 'mp4',
            'png', 'png8', 'png32', 'webm', 'webp', 'blurhash',
        ];
    }

    protected function getFallbackToolkit(): ImageToolkitInterface
    {
        if (isset($this->fallbackToolkit)) {
            return $this->fallbackToolkit;
        }

        return $this->fallbackToolkit = $this->imageToolkitManager
            ->createInstance('gd')
            ->setSource($this->getSource());
    }

    protected function loadInfo(): void
    {
        [$this->width, $this->height] = $this->getImageDimensions($this->getSource());
    }

    protected function getImageDimensions(string $source): ?array
    {
        $urlParts = parse_url($source);
        $pathParts = pathinfo($urlParts['path'] ?? '');

        if (isset($pathParts['extension']) && $pathParts['extension'] === 'svg') {
            return $this->getSvgImageDimensions($source);
        }

        $size = @getimagesize($this->getSource());
        if (!isset($size[0], $size[1])) {
            return null;
        }

        return [$size[0], $size[1]];
    }

    protected function getSvgImageDimensions(string $source): ?array
    {
        if (!$fileContents = file_get_contents($source)) {
            return null;
        }

        $svg = simplexml_load_string($fileContents);
        $attributes = iterator_to_array($svg->attributes());

        if (isset($attributes['width'], $attributes['height'])) {
            return [(int) $attributes['width'], (int) $attributes['height']];
        }

        if (isset($attributes['viewBox'])) {
            $viewBox = $attributes['viewBox'];
        } elseif (isset($attributes['viewbox'])) {
            $viewBox = $attributes['viewbox'];
        }

        if (isset($viewBox)) {
            $viewBoxParts = explode(' ', $viewBox);

            if (isset($viewBoxParts[2], $viewBoxParts[3])) {
                return [(int) $viewBoxParts[2], (int) $viewBoxParts[3]];
            }
        }

        return [64, 64];
    }
}
