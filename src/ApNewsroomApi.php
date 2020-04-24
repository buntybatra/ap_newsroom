<?php

namespace Drupal\ap_newsroom;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Error;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ApNewsroomApi {

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
   * Base URL for AP newsroom
   */
  const AP_BASE_URL = "https://api.ap.org/media/";

  /**
   * API url not correct.
   * @var string
   */
  protected $url_not_correct_error = "URL you trying is not correct. Please verify URL or contact site admin for more details.";

  /**
   * Exception message for editor.
   * @var string
   */
  protected $exception_msg = "Something has been wrong with AP News. Please contact Site Admin. AP News responded with status code :- @code";

  /**
   * @inheritDoc
   * @param ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('messenger')
    );
  }

  /**
   * SearchAndFeedHandler constructor.
   * @param ConfigFactoryInterface $configFactory
   * @param LoggerChannelFactory $logger_factory
   * @param MessengerInterface $messenger
   */
  public function __construct(ConfigFactoryInterface $configFactory, LoggerChannelFactory $logger_factory, MessengerInterface $messenger) {
    $this->config_factory = $configFactory;
    $this->loggerFactory = $logger_factory;
    $this->messenger = $messenger;
  }

  /**
   * Send request to URL.
   * @param $url
   * @return bool|string
   */
  public function sendRequest($url) {
    if(empty($url)) {
      $this->messenger->addError(t($this->url_not_correct_error));
      return FALSE;
    }

    try {
      $response = \Drupal::httpClient()->get($url);
      $data = $response->getBody();
      if (empty($data)) {
        return FALSE;
      }
      return $data->getContents();
    }
    catch (RequestException $e) {
      $this->messenger->addError(t($this->exception_msg, ["@code" =>$e->getCode()]));
      $variables = Error::decodeException($e);
      $this->loggerFactory->get('ap_newsroom_clone')->error('%type: @message in %function (line %line of %file).', $variables);
      return FALSE;
    }
  }

  /**
   * Get API key for ap newsroom.
   * @return array|mixed
   * @throws \exception
   */
  public function getApiKey() {
    $config = $this->config_factory->getEditable('ap_newsroom.base_config');
    $api_key = $config->get('ap_newsroom_key');
    if(!$api_key) {
      throw new \exception("API key not found.");
    }
    return $api_key;
  }

  /**
   * Get version to be used for API.
   * @return array|mixed|string
   */
  public function getApiVersion() {
    $config = $this->config_factory->getEditable('ap_newsroom.base_config');
    $ver = $config->get('ap_newsroom_api_ver');
    if($ver) {
      return $ver;
    } else {
      return 'v';
    }
  }

  /**
   * Get base URL with version and api type
   * @param $api_type
   *  - content
   *  - account
   * @return string
   */
  public function getBaseUrl($api_type) {
    $base_url = self::AP_BASE_URL;
    $api_ver = $this->getApiVersion();
    if($api_ver) {
      $base_url .= $api_ver . "/$api_type/";
    } else {
      $base_url .= "$api_type/";
    }
    return $base_url;
  }

  /**
   * Generate API url
   * @param $api_type
   *  - content
   *  - account
   * @param $api_endpoint
   *  - Visit ap newsroom developer portal for endpoints https://api.ap.org/media/v/swagger/#/
   * @param $options
   * @return string
   * @throws \exception
   */
  public function generateApiUrl($api_type, $api_endpoint, $options) {
    // Add API key and item fragment.
    $options['query']['apikey'] = $this->getApiKey();
    $uri = $this->getBaseUrl($api_type) . $api_endpoint;
    $url = Url::fromUri($uri, $options);
    return $url->toUriString();
  }

}
