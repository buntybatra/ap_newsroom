<?php

namespace Drupal\ap_newsroom_clone\Controller;

use Drupal\ap_newsroom_clone\ApDashboardService;
use Drupal\ap_newsroom_clone\Entity\FeedCloneEntityFormBuilder;
use Drupal\node\Controller\NodeController;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Quick Node Clone Node routes.
 */
class ApNewsroomCloneController extends NodeController {

  /**
   * The entity form builder.
   *
   * @var FeedCloneEntityFormBuilder
   */
  protected $feedCloneEntityFormBuilder;

  /**
   * XML feed content.
   *
   * @var ApDashboardService
   */
  protected $apNewsroomService;

  /**
   * Logger Factory.
   *
   * @var LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * The Messenger service.
   *
   * @var MessengerInterface
   */
  protected $messenger;
  /**
   * Exception message.
   *
   * @var string
   */
  protected $exceptionMessage = 'Something went wrong please content site admin for more details.';

  /**
   * Constructs a NodeController object.
   *
   * @param FeedCloneEntityFormBuilder $entity_form_builder
   *   The entity form builder.
   * @param ApDashboardService $apNewsroomService
   *   Xml feed data.
   * @param LoggerChannelFactory $logger_factory
   *   Passing an Instance of logger factory.
   * @param MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(FeedCloneEntityFormBuilder $entity_form_builder, ApDashboardService $apNewsroomService, LoggerChannelFactory $logger_factory, MessengerInterface $messenger) {
    $this->feedCloneEntityFormBuilder = $entity_form_builder;
    $this->apNewsroomService = $apNewsroomService;
    $this->loggerFactory = $logger_factory;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ap_newsroom_clone.entity.form_builder'),
      $container->get('ap_newsroom_clone.ap_newsroom_service'),
      $container->get('logger.factory'),
      $container->get('messenger')
    );
  }

  /**
   * Retrieves the entity form builder.
   *
   * @return FeedCloneEntityFormBuilder
   *   The entity form builder.
   */
  protected function entityFormBuilder() {
    return $this->feedCloneEntityFormBuilder;
  }

  /**
   * Retrieves the ApNewsroomService.
   *
   * @return ApDashboardService
   */
  protected function getApNewsroomService() {
    return $this->apNewsroomService;
  }

  /**
   * Return form to clone feed into News.
   * @param $entity_type
   * @return array
   */
  public function cloneContent($entity_type) {

    $node = $this->apNewsroomService->prepareNodeForClone($entity_type);
    if (!empty($node)) {
      $form = $this->entityFormBuilder()->getForm($node);
      return $form;
    }
    else {
      throw new NotFoundHttpException();
    }
  }
}
