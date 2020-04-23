<?php

namespace Drupal\ap_newsroom_clone\Entity;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFormBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\EnforcedResponseException;
use Drupal\Core\Form\FormAjaxException;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FeedCloneEntityFormBuilder extends EntityFormBuilder {

  protected $formBuilder;

  /**
   * The Config Factory.
   *
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Entity Type Manager.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * FeedCloneEntityFormBuilder constructor.
   *
   * @param FormBuilderInterface $formBuilder
   * @param ConfigFactoryInterface $configFactory
   * @param EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(FormBuilderInterface $formBuilder, ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager) {
    $this->formBuilder = $formBuilder;
    $this->configFactory = $configFactory;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(EntityInterface $original_entity, $operation = 'default', array $form_state_additions = []) {

    // Get the form object for the entity defined in entity definition
    $form_object = $this->entityTypeManager->getFormObject($original_entity->getEntityTypeId(), $operation);

    // Assign the form's entity to our duplicate!
    $form_object->setEntity($original_entity);

    $form_state = (new FormState())->setFormState($form_state_additions);
    $new_form = $this->formBuilder->buildForm($form_object, $form_state);

    return $new_form;
  }
}
