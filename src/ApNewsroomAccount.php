<?php

namespace Drupal\ap_newsroom;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ApNewsroomAccount {

  /**
   * The Config Factory.
   *
   * @var ConfigFactoryInterface
   */
  protected $config_factory;


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
   * Api type
   */
  const API_TYPE = "account";

  /**
   * @var ApNewsroomApi
   */
  protected $apNewsroomApi;

  /**
   * @inheritDoc
   * @param ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('messenger'),
      $container->get('ap_newsroom.ap_newsroom_api_service')
    );
  }

  /**
   * SearchAndFeedHandler constructor.
   * @param ConfigFactoryInterface $configFactory
   * @param LoggerChannelFactory $logger_factory
   * @param MessengerInterface $messenger
   * @param ApNewsroomApi $apNewsroomApi
   */
  public function __construct(ConfigFactoryInterface $configFactory, LoggerChannelFactory $logger_factory, MessengerInterface $messenger, ApNewsroomApi $apNewsroomApi) {
    $this->config_factory = $configFactory;
    $this->loggerFactory = $logger_factory;
    $this->messenger = $messenger;
    $this->apNewsroomApi = $apNewsroomApi;
  }

  /**
   * Get ApNewsroomApi
   * @return ApNewsroomApi
   */
  public function getApNewsroomApi() {
    return $this->apNewsroomApi;
  }

  /**
   * Retrieve your available account endpoints
   * @return bool|string
   */
  public function getAccountEndpoints() {
    return $this->getAccountDetails('', []);
  }

  /**
   * Retrieve your Followed Topics
   * @param array $param
   * @return bool|string
   */
  public function getFollowedTopics($param = []) {
    $options = [
      'query' => $param
    ];
    $api_endpoint = 'followedtopics';
    return $this->getAccountDetails($api_endpoint, $options);
  }

  /**
   * Retrieve your entitlements and associated meter information
   * @param array $param
   * @return bool|string
   */
  public function getAccountPlan($param = []) {
    $options = [
      'query' => $param
    ];
    $api_endpoint = 'plans';
    return $this->getAccountDetails($api_endpoint, $options);
  }

  /**
   * A list of content items downloaded by you or your salesperson.
   * Important:
      - The requested date range may not exceed 60 days.
      - The download information is available for the last 365 days only.
   * @param array $param
   * @return bool|string
   */
  public function getDownloadHistory($param = []) {
    $options = [
      'query' => $param
    ];
    $api_endpoint = 'downloads';
    return $this->getAccountDetails($api_endpoint, $options);
  }

  /**
   * Returns the your account’s request limits for the various endpoints
   * @return bool|string
   */
  public function getAccountQuotas() {
    $api_endpoint = 'quotas';
    return $this->getAccountDetails($api_endpoint, []);
  }

  /**
   * Get account details from Ap newsroom
   * @param $api_endpoint
   * @param $options
   * @return bool|string
   */
  public function getAccountDetails($api_endpoint, $options) {
    $url = $this->getApNewsroomApi()->generateApiUrl(self::API_TYPE, $api_endpoint, $options);
    return Json::decode($this->getApNewsroomApi()->sendRequest($url));
  }
}
