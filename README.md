# Ap Newsroom

## Background
Ap Newsroom module provide developer service to easily integrate Ap newsroom APIs.

For more details visit :-
    https://developer.ap.org/

## How to use it.

1) Install module as usual.
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules

2) Configure module in /admin/config/services/ap_newsroom.
Provide API key and API version to be used left empty(recommended) to use latest version.

## How it works

Ap newsroom module provide developer service for different endpoints of AP newsroom.

Example of account Service
    Drupal::service("ap_newsroom.ap_newsroom_account_service")->getApNewsroomApi();

For different endpoints details visit :- https://api.ap.org/media/v/swagger/#/
1) ApNewsroomAccount provide functions to get your account details.
2) ApNewsroomContent provide functions to get content from AP newsroom.

## How to clone content in drupal

Use sub-module Ap newsroom clone.
