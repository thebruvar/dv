<?php

namespace Drupal\dmt_domain_config;

use Drupal\views_ui\ViewListBuilder as CoreViewListBuilder;
use Drupal\domain\DomainNegotiatorInterface;

/**
 * Defines a class to build a listing of view entities.
 *
 * @see \Drupal\views\Entity\View
 */
class ViewListBuilder extends CoreViewListBuilder {

  /** @var  DomainNegotiatorInterface */
  protected $domainNegotiator;

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = array(
      'enabled' => array(),
      'disabled' => array(),
    );
    foreach ($this->loadConfigEntity() as $entity) {
      if ($entity->status()) {
        $entities['enabled'][] = $entity;
      }
      else {
        $entities['disabled'][] = $entity;
      }
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function loadConfigEntity() {
    $entity_ids = $this->getEntityIds();

    /** @var \Drupal\domain\Entity\Domain $active */
    $active = \Drupal::service('domain.negotiator')->getActiveDomain();
    if (!$active->isDefault()) {
      $entities = $this->storage->loadMultiple($entity_ids);
    } else {
      $entities = $this->storage->loadMultipleOverrideFree($entity_ids);
    }

    // Sort the entities using the entity class's sort() method.
    // See \Drupal\Core\Config\Entity\ConfigEntityBase::sort().
    uasort($entities, array($this->entityType->getClass(), 'sort'));
    return $entities;
  }

  /**
   * Set the Domain negotiator.
   * @param DomainNegotiatorInterface $domain_negotiator
   */
  public function setDomainNegotiator(DomainNegotiatorInterface $domain_negotiator) {
    /// @todo this is not used find a way to set a call in dmt_domain_config_entity_type_alter
    $this->domainNegotiator = $domain_negotiator;
  }
}
