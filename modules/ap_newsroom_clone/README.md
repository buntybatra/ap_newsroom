# AP Newsroom clone

## Background
Ap Newsroom Clone module provide dashboard and clone functionality for AP Newsroom content.

## How to use it.

1) Install module as usual.
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules

2) Configure module in /admin/config/services/ap_newsroom.
Provide API key and API version to be used left empty(recommended) to use latest version.
Provide Page size

## How to map AP newsroom data to Drupal content type.
See example_config/ap_newsroom_clone.field_mapping.yml

Add your content mapping accordingly and import this configuration.

Currently this module only support following field type :-
1) Text
2) Image
3) Paragraphs
