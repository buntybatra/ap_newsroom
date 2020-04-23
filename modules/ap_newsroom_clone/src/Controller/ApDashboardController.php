<?php

/**
 * @file
 * Contains \Drupal\ap_newsroom_clone\Controller\ApDashboardController.
 */

namespace Drupal\ap_newsroom_clone\Controller;

use Drupal\ap_newsroom\ApNewsroomContent;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\ap_newsroom_clone\ApDashboardService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Returns responses for Quick Node Clone Node routes.
 */
class ApDashboardController extends ControllerBase {

  /**
   * @var ApDashboardService
   *   Variable used for loading Ap newsroom service.
   */
  protected $apDashboardService;

  /**
   * @var ApNewsroomContent
   */
  protected $apNewsroomContent;

  /**
   * Constructs a NodeController object.
   *
   * @param LoggerChannelFactory $logger_factory
   *   Passing an Instance of logger factory.
   * @param MessengerInterface $messenger
   *   The messenger service.
   * @param ApDashboardService $apDashboardService
   * @param ApNewsroomContent $apNewsroomContent
   */
  public function __construct(LoggerChannelFactory $logger_factory, MessengerInterface $messenger, ApDashboardService $apDashboardService, ApNewsroomContent $apNewsroomContent)
  {
    $this->loggerFactory = $logger_factory;
    $this->messenger = $messenger;
    $this->apDashboardService = $apDashboardService;
    $this->apNewsroomContent = $apNewsroomContent;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
      return new static(
      $container->get('logger.factory'),
      $container->get('messenger'),
      $container->get('ap_newsroom_clone.ap_newsroom_service'),
      $container->get('ap_newsroom.ap_newsroom_content_service')
    );
  }
  /**
   * Lists Feeds.
   * @return array
   */

  public function listFeeds() {

    if($this->apDashboardService->isFeedChecked()) {
      $decodedJsonFeedData = $this->apNewsroomContent->feed();
    } else {
      $param = [];
      $page_size = $this->apDashboardService->getPageSize();
      if($page_size) {
        $param['page_size'] = $page_size;
      }
      $decodedJsonFeedData = $this->apNewsroomContent->search($param);
    }
    $form = $this->formBuilder()
      ->getForm('Drupal\ap_newsroom_clone\Form\ApFeedsSearchForm', $decodedJsonFeedData);
    return $form;
  }

/**
 * Method for updating data upon pagination.
 *
 *  @param string
 *  @param string
 *
 *  @return array
 */
  public function updateData($qt, $page_num) {
    if($this->apDashboardService->isFeedChecked()) {
      $param = [
        'qt' => $qt,
        'seq' => $page_num
      ];
    } else {
      $param = [
        'qt' => $qt,
        'page' => $page_num
      ];
    }
    $decodedJsonFeedData = $this->apNewsroomContent->search($param);
    $table = $this->apDashboardService->getTable($decodedJsonFeedData);
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#ap-news-table-list', $table));
    return $response;
  }
}
