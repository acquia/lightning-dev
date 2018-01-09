<?php

use Drupal\DrupalExtension\Context\DrupalSubContextBase;

/**
 * Contains miscellaneous step definitions for working with Drupal entities.
 */
class EntityContext extends DrupalSubContextBase {

  /**
   * Visits a randomly chosen entity of a specific type and (optional) bundle.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $bundle
   *   (optional) The bundle.
   *
   * @throws \Exception if there are no entities to visit.
   *
   * @When I visit a(n) :entity_type
   * @When I visit a(n) :bundle :entity_type
   */
  public function visitEntity($entity_type, $bundle = NULL) {
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type);

    $query = $storage->getQuery();

    if ($bundle) {
      $key = $storage->getEntityType()->getKey('bundle');
      if ($key) {
        $query->condition($key, $bundle);
      }
    }

    $items = $query->execute();
    if ($items) {
      $id = reset($items);
      $url = $storage->load($id)->toUrl()->getInternalPath();
      $this->visitPath($url);
    }
    else {
      $label = $storage->getEntityType()->getPluralLabel();
      throw new \Exception("There are no $bundle $label available.");
    }
  }

}
