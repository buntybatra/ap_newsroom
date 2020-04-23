<?php

namespace Drupal\ap_newsroom_clone\Form;

use Drupal\ap_newsroom\ApNewsroomContent;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ap_newsroom_clone\ApDashboardService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\UrlHelper;

/**
 * Provide a search form for search AP Feed.
 */
class ApFeedsSearchForm extends FormBase {

  /**
   * @var ApDashboardService $apDashboardService
   */
  protected $apDashboardService;

  /**
   * @var ApNewsroomContent
   */
  protected $apNewsroomContent;

  /**
   * Class constructor.
   * @param ApDashboardService $apDashboardService
   */
  public function __construct(ApDashboardService $apDashboardService, ApNewsroomContent $apNewsroomContent) {
    $this->apDashboardService = $apDashboardService;
    $this->apNewsroomContent = $apNewsroomContent;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ap_newsroom_clone.ap_newsroom_service'),
      $container->get('ap_newsroom.ap_newsroom_content_service')
    );
  }

  /**
   * {{ @inheritDoc }}
   */
  public function getFormId() {
    return 'ap_newsroom_search_form';
  }

  /**
   * {{ @inheritDoc }}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $decodedJsonFeedData = []) {

    $form['feedSearch'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('AP Feeds Search Form'),
      '#open' => TRUE,
    ];

    $form['feedSearch']['Search'] = [
      '#title' => 'Search',
      '#type' => 'search'

    ];
    $form['feedSearch']['sort'] = [
      '#type' => 'select',
      '#title' => $this->t('Sort'),
      '#options' => [
        'relevance' => $this->t('Relevance'),
        'versioncreated:desc' => $this->t('Version created desc'),
        'versioncreated:asc' => $this->t('Version created asc')
      ],
    ];
    $form['feedSearch']['actions']['submit'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Search'),
      '#prefix' =>'<div class="form-item-submit form-item">',
      '#suffix' => '</div>',
      '#ajax' => [
        'callback' => '::getApNewsSearchResult',
        'disable-refocus' => FALSE,
        'event' => 'click',
        'wrapper' => 'ap-news-table-list',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Getting news for you...'),
        ],
      ]
    ];

    $form['table'] = $this->apDashboardService->getTable($decodedJsonFeedData);
    $form['#ap_newsroom_response'] = $decodedJsonFeedData;

    return $form;
  }

  /**
   * {{ @inheritDoc }}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo Needs to decide what is to be done upon form submission.
  }

  /**
   * Get Search result from Ap News.
   * @param array $form
   * @param FormStateInterface $form_state
   * @return mixed
   */
  public function getApNewsSearchResult(array &$form, FormStateInterface $form_state) {

    // get field value from form_state;
    $search_keyword = $form_state->getValue('Search');
    $sort = $form_state->getValue('sort');
    $param = [];
    if($sort != 'relevance') {
      $param['sort'] = $sort;
    }
    if($search_keyword) {
      $param['q'] = $search_keyword;
    }
    $decodedJsonFeedData = $this->apNewsroomContent->search($param);
    $total_record = $decodedJsonFeedData['data']['total_items'];
    $this->messenger()->addStatus("$total_record records found.");
    $table = $this->apDashboardService->getTable($decodedJsonFeedData);
    return $table;
  }
}
