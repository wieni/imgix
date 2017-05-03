## Imgix

Upload files and render them through imgix

### Installation

* Enable the module
* Config the module on /admin/config/services/imgix

### Usage

* When creating fields, use the Field type 'Imgix' (category "References")
* A form widget and a formatter are provided

### Twig function

There are three twig functions you can call:

```imgix(image, preset)```

Renders the image (imgx field type) in the given preset.

```imgix_width(image, preset)```

Returns the rendered width of the give image (field type) in the given preset.

```imgix_height(image, preset)```

Returns the rendered height of the give image (field type) in the given preset.

### TODO

* Provide a formatter
