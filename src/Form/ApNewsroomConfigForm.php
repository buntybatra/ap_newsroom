<?php

namespace Drupal\ap_newsroom\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ApNewsroomConfigForm.
 */
class ApNewsroomConfigForm extends ConfigFormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'ap_newsroom_base_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->config('ap_newsroom.base_config');

    $form['ap_news_config'] = array(
      '#type' => 'fieldset',
      '#title' => t('AP newsroom API configurations')
    );

    $form['ap_news_config']['ap_newsroom_key'] = array(
      '#type' => 'textfield',
      '#title' => t('API key'),
      '#default_value' => $config->get('ap_newsroom_key'),
      '#required' => TRUE,
      '#description' => t('AP newsroom API key'),
    );
    $form['ap_news_config']['ap_newsroom_api_ver'] = array(
      '#type' => 'textfield',
      '#title' => t('API version'),
      '#default_value' => $config->get('ap_newsroom_api_ver'),
      '#description' => t('Please enter API version to be used or left empty(recommended) to use latest version.'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {

    $this->config('ap_newsroom.base_config')
      ->set('ap_newsroom_key', $form_state->getValue('ap_newsroom_key'))
      ->set('ap_newsroom_api_ver', $form_state->getValue('ap_newsroom_api_ver'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames()
  {
    return [
      'ap_newsroom.base_config',
    ];
  }

}
