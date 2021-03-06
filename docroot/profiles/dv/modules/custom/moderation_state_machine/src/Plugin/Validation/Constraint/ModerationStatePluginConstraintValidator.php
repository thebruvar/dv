<?php

namespace Drupal\moderation_state_machine\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\moderation_state_machine\Plugin\Type\ModerationStateMachineManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\content_moderation\StateTransitionValidation;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Validates the Entity Moderation constraint.
 */
class ModerationStatePluginConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The state transition validation.
   *
   * @var \Drupal\content_moderation\StateTransitionValidation
   */
  protected $validation;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private $entityTypeManager;

  /**
   * The moderation info.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * @var \Drupal\moderation_state_machine\Plugin\Type\ModerationStateMachineManager
   */
  protected $moderationStateMachineManager;

  /**
   * Creates a new ModerationStateConstraintValidator instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\content_moderation\StateTransitionValidation $validation
   *   The state transition validation.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   The moderation information.
   * @param \Drupal\moderation_state_machine\Plugin\Type\ModerationStateMachineManager $moderationStateMachineManager
   */
  public function __construct(EntityTypeManager $entity_type_manager, StateTransitionValidation $validation, ModerationInformationInterface $moderation_information, ModerationStateMachineManager $moderationStateMachineManager) {
    $this->validation = $validation;
    $this->entityTypeManager = $entity_type_manager;
    $this->moderationInformation = $moderation_information;
    $this->moderationStateMachineManager = $moderationStateMachineManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('content_moderation.state_transition_validation'),
      $container->get('content_moderation.moderation_information'),
      $container->get('plugin.manager.switch_moderation_state_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $value->getEntity();

    // Ignore entities that are not subject to moderation anyway.
    if (!$this->moderationInformation->isModeratedEntity($entity)) {
      return;
    }

    // Ignore entities that are being created for the first time.
    if ($entity->isNew()) {
      return;
    }

    // Ignore entities that are being moderated for the first time, such as
    // when they existed before moderation was enabled for this entity type.
    if ($this->isFirstTimeModeration($entity)) {
      return;
    }

    $plugin_ids = $this->moderationStateMachineManager->getPluginId($entity);

    foreach ($plugin_ids as $plugin_id) {
      /** @var \Drupal\moderation_state_machine\ModerationStateMachineBase $sms */
      $sms = $this->moderationStateMachineManager->createInstance($plugin_id);
      $violations = $sms->validate($entity);

      if (!empty($violations)) {
        foreach ($violations as $violation) {
          $this->context->buildViolation($violation['message'])
            ->setCause(isset($violation['cause']) ? $violation['cause'] : NULL)
            ->addViolation();
        }
      }
    }
  }

  /**
   * Determines if this entity is being moderated for the first time.
   *
   * If the previous version of the entity has no moderation state, we assume
   * that means it predates the presence of moderation states.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being moderated.
   *
   * @return bool
   *   TRUE if this is the entity's first time being moderated, FALSE otherwise.
   */
  protected function isFirstTimeModeration(EntityInterface $entity) {
    $original_entity = $this->moderationInformation->getLatestRevision($entity->getEntityTypeId(), $entity->id());

    $original_id = $original_entity->moderation_state;

    return !($entity->moderation_state && $original_entity && $original_id);
  }
}
