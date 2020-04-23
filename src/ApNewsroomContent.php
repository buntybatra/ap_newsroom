<?php

namespace Drupal\ap_newsroom;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\MessengerInterface;
use exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ApNewsroomContent {

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
  const API_TYPE = "content";

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
   * Search content with search API.
   * @param array $param Check Ap newsroom developer portal for parameters list.
   * @return bool|string
   */
  public function search($param = []) {
    $options = [
      'query' => $param
    ];
    $api_endpoint = 'search';
    return $this->getContent($api_endpoint, $options);
  }

  /**
   * Get feed from ap newsroom
   * @param array $param Check Ap newsroom developer portal for parameters list.
   * @return bool|string
   */
  public function feed($param = []) {
    $options = [
      'query' => $param
    ];
    $api_endpoint = 'feed';
    return $this->getContent($api_endpoint, $options);
  }

  /**
   * Retrieve a list of available RSS XML feeds entitled to your plan.
   * @return bool|string
   */
  public function rss() {
    $api_endpoint = 'rss';
    return $this->getContent($api_endpoint, []);
  }

  /**
   * Receive a RSS feed of latest AP content
   * Receive a RSS feed of latest AP Content for a Product (RSS) Id.
   * @param $rss_id
   * @param array $param Check Ap newsroom developer portal for parameters list.
   * @return bool|string
   * @throws exception
   */
  public function rssByRssId($rss_id, $param = []) {
    if(!$rss_id) {
      throw new exception('Rss id cannot be null.');
    }
    $options = [
      'query' => $param
    ];
    $api_endpoint = 'rss/' . $rss_id;
    return $this->getContent($api_endpoint, $options);
  }

  /**
   * Receive a feed of ‘contentitem’ objects
   * which have been sent to your organization’s OnDemand queue.
   * @param array $param Check Ap newsroom developer portal for parameters list.
   * @return bool|string
   */
  public function getFeedOnDemand($param = []) {
    $options = [
      'query' => $param
    ];
    $api_endpoint = 'ondemand';
    return $this->getContent($api_endpoint, $options);
  }

  /**
   * Fetch the ‘contentitem’ object for a single piece of content by its Item ID.
   * @param $item_id
   * @param array $param Check Ap newsroom developer portal for parameters list.
   * @return bool|string
   * @throws exception
   */
  public function getContentById($item_id, $param = []) {
    if(!$item_id) {
      throw new exception('Content id cannot be null.');
    }
    $options = [
      'query' => $param
    ];
    $api_endpoint = $item_id;
    return $this->getContent($api_endpoint, $options);
  }

  /**
   * Get next page content while search and feed.
   * @param $next_page_url
   * @return bool|string
   * @throws exception
   */
  public function nextPage($next_page_url) {
    if(!$next_page_url) {
      throw new exception('Next page url cannot be null.');
    }
    $next_page_url = $next_page_url . '&apikey=' . $this->getApNewsroomApi()->getApiKey();
    return $this->getApNewsroomApi()->sendRequest($next_page_url);
  }

  /**
   * Get previous page content while search and feed.
   * @param $previous_page_url
   * @return bool|string
   * @throws exception
   */
  public function previousPage($previous_page_url) {
    if(!$previous_page_url) {
      throw new exception('Previous page url cannot be null.');
    }
    $previous_page_url = $previous_page_url . '&apikey=' . $this->getApNewsroomApi()->getApiKey();
    return $this->getApNewsroomApi()->sendRequest($previous_page_url);
  }

  /**
   * Get Nitf data in xml string by Nitf URL.
   * @param $nitf_url
   * @return bool|string
   * @throws exception
   */
  public function getNitfByUrl($nitf_url) {
    if(!$nitf_url) {
      throw new exception('Nitf url cannot be null.');
    }
    $nitf_url = $nitf_url . '&apikey=' . $this->getApNewsroomApi()->getApiKey();
    return $this->getApNewsroomApi()->sendRequest($nitf_url);
  }

  /**
   * Get Nitf data in xml string by content id.
   * @param $item_id
   * @return bool|string|null
   * @throws exception
   */
  public function getNitfByItemId($item_id) {
    if(!$item_id) {
      throw new exception('Item Id cannot be null.');
    }
    $content_item = $this->getContentById($item_id);
    if(isset($content_item['data']['item']['renditions']['nitf'])) {
      $nitf_url = $content_item['data']['item']['renditions']['nitf']['href'];
      return $this->getNitfByUrl($nitf_url);
    }
    return NULL;
  }

  /**
   * Get content from Ap newsroom
   * @param $api_endpoint
   * @param $options
   * @return bool|stringgetFeedOnDemand
   * @throws exception
   */
  public function getContent($api_endpoint, $options) {
    $url = $this->getApNewsroomApi()->generateApiUrl(self::API_TYPE, $api_endpoint, $options);
    return Json::decode($this->getApNewsroomApi()->sendRequest($url));
  }
}
