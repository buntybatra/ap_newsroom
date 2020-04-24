<?php


namespace Drupal\ap_newsroom_clone;

use Drupal\ap_newsroom\ApNewsroomContent;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use http\Exception\InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ApDashboardService {

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
   * @var "ap_newsroom.base_config" object
   */
  protected $config;

  /**
   * Request stack service object.
   * @var object
   */
  protected $request_stack;

  /**
   * The Entity Type Manager.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var ApNewsroomContent
   */
  protected $apNewsroomContent;

  /**
   * http_client_factory
   */
  protected $http_client_factory;

  /**
   * @var DateFormatter
   */
  protected $dateFormatter;
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
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('ap_newsroom.ap_newsroom_content_service'),
      $container->get('http_client_factory'),
      $container->get('date.formatter')
    );
  }

  /**
   * SearchAndFeedHandler constructor.
   * @param ConfigFactoryInterface $configFactory
   * @param LoggerChannelFactory $logger_factory
   * @param MessengerInterface $messenger
   * @param RequestStack $request_stack
   * @param EntityTypeManagerInterface $entityTypeManager
   * @param ApNewsroomContent $apNewsroomContent
   * @param ClientFactory $http_client_factory
   * @param DateFormatter $dateFormatter
   */
  public function __construct(ConfigFactoryInterface $configFactory,
                              LoggerChannelFactory $logger_factory,
                              MessengerInterface $messenger,
                              RequestStack $request_stack,
                              EntityTypeManagerInterface $entityTypeManager,
                              ApNewsroomContent $apNewsroomContent,
                              ClientFactory $http_client_factory,
                              DateFormatter $dateFormatter) {
    $this->config_factory = $configFactory;
    $this->loggerFactory = $logger_factory;
    $this->messenger = $messenger;
    $this->request_stack = $request_stack;
    $this->entityTypeManager= $entityTypeManager;
    $this->apNewsroomContent = $apNewsroomContent;
    $this->http_client_factory = $http_client_factory;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * Page size.
   * @return string
   */
  public function getPageSize() {
    $config = $this->config_factory->getEditable('ap_newsroom.base_config');
    return $config->get('ap_newsroom_page_size');
  }

  /**
   * Check if feed api in use.
   * @return bool
   */
  public function isFeedChecked() {
    $base_config = $this->config_factory->getEditable('ap_newsroom.base_config');
    if($base_config->get('use_feed')) {
      return TRUE;
    } else {
      return FALSE;
    }
  }

  /**
   * Prepare node entity for cloning
   * @param $entity_type
   * @return \Drupal\Core\Entity\EntityBase|\Drupal\Core\Entity\EntityInterface
   */
  public function prepareNodeForClone($entity_type) {

    $item_id = $this->getItemId();
    if($item_id) {
      $singleContentResponse = $this->apNewsroomContent->getContentById($item_id);
      return $this->mapAPDataToNode($entity_type,$singleContentResponse);
    }
  }

  /**
   * Get itemID from url.
   * @return mixed
   */
  public function getItemId() {
    $item_id = $this->request_stack->getCurrentRequest()->query->get('item_id');
    if(!$item_id) {
      throw new InvalidArgumentException('Item id is not valid.');
    }
    return $this->request_stack->getCurrentRequest()->query->get('item_id');
  }

  /**
   * Map node field.
   * @param $entity_type
   * @param $singleContentResponse
   * @return \Drupal\Core\Entity\EntityBase|\Drupal\Core\Entity\EntityInterface
   */
  public function mapAPDataToNode($entity_type, $singleContentResponse) {
    // Prepare Node.
    $node_data = [
      'type' => $entity_type
    ];
    try {
      $node = Node::create($node_data);
    } catch (\Exception $exception) {
      watchdog_exception('ap_newsroom_clone' , $exception);
    }

    $config = $this->config_factory->getEditable('ap_newsroom_clone.field_mapping');

    // Get all the configured entity.
    $node_list = $config->get('node');
    foreach($node_list[$entity_type]['fields'] as $key => $field) {
      $this->resolveFieldTypeMapping($node, $field, $singleContentResponse);
    }
    return $node;
  }

  /**
   * Field type resolver
   * @param $entity Entity type
   * @param $field field array
   * @param $singleContentResponse
   */
  public function resolveFieldTypeMapping(&$entity, $field, $singleContentResponse) {
    switch ($field['type']) {
      case 'text' :
        $this->mapTextTypeField($entity, $field, $singleContentResponse);
        break;
      case 'image' :
        $this->mapImageField($entity, $field, $singleContentResponse);
        break;
      case 'nitf' :
        $this->mapNitfField($entity, $field, $singleContentResponse);
        break;
      case 'paragraphs' :
        $this->mapParagraphsType($entity, $field, $singleContentResponse);
        break;
    }
  }

  /**
   * Map text type field.
   * @param $entity
   * @param $field
   * @param $item_data
   */
  public function mapTextTypeField(&$entity, $field, $item_data) {
    $field_name = $field['id'];
    $mapping_index = explode('.',$field['path']);
    // Loop for value.

    $field_value = $item_data['data'];
    for ($i = 0; $i < count($mapping_index); $i++) {
      if(isset($field_value[$mapping_index[$i]])) {
        $field_value = $field_value[$mapping_index[$i]];
      }
    }
    if(is_string($field_value) && $entity->hasField($field_name)) {
      $entity->$field_name->appendItem($field_value);
    }
  }

  /**
   * Map image type field
   * @param $entity
   * @param $field
   * @param $item_data
   */
  public function mapImageField(&$entity, $field, $item_data) {
    $field_name = $field['id'];
    $is_multiple = $field['multiple'];
    if(isset($item_data['data']['item']['associations'])) {
      $media_index = $item_data['data']['item']['associations'];
      foreach ($media_index as $index => $media) {
        if($media['type'] == 'picture') {
          $media_id = $media['altids']['itemid'];
          $media_data = $this->apNewsroomContent->getContentById($media_id);
          $file_data = $this->saveFile($media_data, $this->apNewsroomContent->getApNewsroomApi()->getApiKey());
          if(!empty($file_data) && $entity->hasField($field_name)) {
            $entity->$field_name->appendItem($file_data);
            if(!$is_multiple) {
              break;
            }
          }
        }
      }
    }
  }

  /**
   * Save file for image type.
   * @param $media_data
   * @param $api_key
   * @return array
   */
  public function saveFile($media_data, $api_key) {
    if(isset($media_data['data']['item']['renditions']['main'])) {
      $title = $media_data['data']['item']['title'];
      $alt_text = $media_data['data']['item']['headline'];
      if(isset($media_data['data']['item']['altids']['itemid'])) {
        $item_id = $media_data['data']['item']['altids']['itemid'];
        $uri = $media_data['data']['item']['renditions']['main']['href'];
        $image_url = $uri . '&apikey=' . $api_key;
        $actual_uri = NULL;

        // Get redirected url.
        $response = $this->http_client_factory->fromOptions([
          'allow_redirects' => [
            'on_redirect' => function (RequestInterface $request, ResponseInterface $response, \Psr\Http\Message\UriInterface $uri) use (&$actual_uri) {
              $actual_uri = (string) $uri;
            }
          ],
        ])->get($image_url);
        $redirected_url = $actual_uri . '&apikey=' . $api_key;
        $data = file_get_contents($redirected_url);
        $file = file_save_data($data, "public://$item_id.jpeg", FileSystemInterface::EXISTS_REPLACE);
        return [
          'target_id' => $file->id(),
          'alt'       => $alt_text,
          'title'     => $title,
        ];
      }
    }
    return [];
  }

  /**
   * Map Body from XML ap newsroom
   * @param $entity
   * @param $field
   * @param $item_data
   */
  public function mapNitfField(&$entity, $field, $item_data) {
    $field_name = $field['id'];
    if(isset($item_data['data']['item']['renditions']['nitf'])) {
      $href = $item_data['data']['item']['renditions']['nitf']['href'];
      $xml_data = $this->apNewsroomContent->getNitfByUrl($href);
      // parse xml string
      $xml_data = simplexml_load_string($xml_data);
      $body = $xml_data->body->{'body.content'}->asXML();
      if($body && $entity->hasField($field_name)) {
        $entity->$field_name->appendItem($body);
      }
    }
  }

  /**
   * Map paragraphs type field.
   * @param $entity
   * @param $field
   * @param $item_data
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function mapParagraphsType(&$entity, $field, $item_data) {
    $field_name = $field['id'];
    foreach ($field['paragraph_list'] as $para_type => $para_fields) {
      $paragraph = $this->entityTypeManager
        ->getStorage('paragraph')
        ->create(['type' => $para_type]);
      foreach ($para_fields['fields'] as $key => $para_field) {
        $this->resolveFieldTypeMapping($paragraph, $para_field, $item_data);
      }
      if(!empty($paragraph) && $entity->hasField($field_name)) {
        $entity->$field_name->appendItem($paragraph);
      }
    }
  }
  /**
   * Generate table structure for listing.
   * @param $decodedJsonFeedData
   * @return array
   */
  public function getTable($decodedJsonFeedData) {
    $header = [
      'headline' => t('Headline'),
      'item_id' => t('Item Id'),
      'version_created' => t('Version Created'),
      'clone' => t('Clone'),
    ];

    $rows = [];
    if(isset($decodedJsonFeedData['data']['items'])) {
      $items = $decodedJsonFeedData['data']['items'];
      foreach ($items as $key => $item) {
        $item = $item['item'];
        $clone_buttons = $this->getCloneButton($item['altids']['itemid']);
        $rows[$key] = [
          'headline' => [
            'data' => isset($item['headline']) ? $item['headline'] : ''
          ],
          'item_id' => [
            'data' => isset($item['altids']['itemid']) ? $item['headline'] : ''
          ],
          'version_created' => [
            'data' => isset($item['versioncreated']) ?
              $this->dateFormatter->format(strtotime($item['versioncreated']), 'custom', 'm/d/Y h:ia') : ''
          ],
          'clone' => [
            'data' => !empty($clone_buttons) ? $clone_buttons : ''
          ],
        ];
      }
    }

    return [
      '#type' => 'table',
      '#prefix' => '<div id="ap-news-table-list" class="table-feed-list">',
      '#suffix' => '</div>',
      '#header' => $header,
      '#rows' => $rows,
      '#footer' =>$this->getFooter($decodedJsonFeedData),
      '#empty' => t('No result found.'),
    ];
  }

  /**
   * Get clone dropbutton
   * @param $item_id
   * @return string[]|null
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function getCloneButton($item_id) {
    $mapping_config = $this->config_factory->getEditable('ap_newsroom_clone.field_mapping');
    $node_mapping = $mapping_config->get('node');
    $clone_button = ['#type' => 'dropbutton'];
    if(!empty($node_mapping)) {
      foreach ($node_mapping as $type => $node) {
        $clone_title = $this->entityTypeManager
          ->getStorage('node_type')
          ->load($type)
          ->label();
        $clone_button['#links'][$type] = [
          'title' => $clone_title,
          'url' =>  Url::fromRoute('ap_newsroom_clone.clone_content', [
            'entity_type' => $type
          ],[
            'query' => ['item_id' => $item_id],
            'attributes' => ['target' => '_blank']
          ]),
        ];
      }
      return $clone_button;
    }
    return NUll;
  }

  /**
   * Get footer for table.
   * @param $decodedJsonFeedData
   * @return array
   */
  public function getFooter($decodedJsonFeedData) {
    $footer = [];
    if(isset($decodedJsonFeedData['data']['previous_page'])) {
      $parsed_url_previous_page = UrlHelper::parse($decodedJsonFeedData['data']['previous_page']);
      $footer['data'][] = $this->getPagerLinks($parsed_url_previous_page, '‹ Previous');
    }

    if(!$this->isFeedChecked()) {
      $footer['data'][] = t('Page: ') . $decodedJsonFeedData['data']['current_page'];
    }

    if(isset($decodedJsonFeedData['data']['next_page'])) {
      if($this->isFeedChecked()) {
        $parsed_url_next_page = UrlHelper::parse($decodedJsonFeedData['data']['next_page']);
        $footer['data'][] = $this->getPagerLinks($parsed_url_next_page, 'More ›');
        return $footer;
      }
      $parsed_url_next_page = UrlHelper::parse($decodedJsonFeedData['data']['next_page']);
      $footer['data'][] = $this->getPagerLinks($parsed_url_next_page, 'Next ›');
    }
    return $footer;
  }

  /**
   * Generate pager for listing.
   * @param $param
   * @param $label
   * @return Link
   */
  public function getPagerLinks($param, $label) {
    $qt = $param['query']['qt'];
    if($this->isFeedChecked()) {
      $seq = $param['query']['seq'];
      $pager_link = Link::createFromRoute($label,'ap_newsroom_clone.pager',[
        'qt' => $qt,
        'page_num' => $seq
      ],[
        'attributes' => ['class' => 'use-ajax']
      ]);
    } else {
      $page = $param['query']['page'];
      $pager_link = Link::createFromRoute($label,'ap_newsroom_clone.pager',[
        'qt' => $qt,
        'page_num' => $page
      ],[
        'attributes' => ['class' => 'use-ajax']
      ]);
    }
    return $pager_link;
  }
}
