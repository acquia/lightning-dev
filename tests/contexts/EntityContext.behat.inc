<?php

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;

/**
 * Contains miscellaneous step definitions for working with Drupal entities.
 */
class EntityContext extends DrupalSubContextBase {

  /**
   * IDs of entities created during the scenario, divided into sets keyed by
   * entity type ID.
   *
   * @var array
   */
  protected $entities = [];

  /**
   * Creates a set of entities from tabular data.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param \Behat\Gherkin\Node\TableNode $table
   *   The table of entity data.
   *
   * @Given :entity_type entities:
   */
  public function createMultiple($entity_type, TableNode $table) {
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type);

    foreach ($table as $values) {
      $entity = $storage->create($values);
      $entity->save();
      $this->entities[$entity_type][] = $entity->id();
    }
  }

  /**
   * Deletes entities created during the scenario.
   *
   * @afterScenario
   */
  public function tearDown() {
    foreach ($this->entities as $entity_type => $entities) {
      /** @var \Drupal\Core\Entity\EntityInterface[] $entities */
      $entities = \Drupal::entityTypeManager()
        ->getStorage($entity_type)
        ->loadMultiple($entities);

      foreach ($entities as $entity) {
        $entity->delete();
      }
    }
  }

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
