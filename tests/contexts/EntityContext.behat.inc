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
  protected $trash = [];

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
      $this->trash[$entity_type][] = $entity->id();
    }
  }

  /**
   * Deletes entities created during the scenario.
   *
   * @afterScenario
   */
  public function tearDown() {
    foreach ($this->trash as $entity_type => $IDs) {
      /** @var \Drupal\Core\Entity\EntityInterface[] $entities */
      $entities = \Drupal::entityTypeManager()
        ->getStorage($entity_type)
        ->loadMultiple($IDs);

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

  /**
   * Marks recently created entities to be deleted after the test scenario.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param int $n
   *   (optional) How many entities to delete.
   *
   * @When I delete the latest :entity_type
   * @When I delete the latest :entity_type entity
   * @When I delete the latest :n :entity_type entities
   */
  public function trashNewest($entity_type, $n = 1) {
    $items = \Drupal::entityTypeManager()
      ->getStorage($entity_type)
      ->getQuery()
      ->sort('created', 'DESC')
      ->range(0, $n)
      ->execute();

    foreach ($items as $id) {
      $this->trash[$entity_type][] = $id;
    }
  }

  /**
   * Visits the edit form for an entity.
   *
   * @see lightning_dev_local_tasks_alter()
   *
   * @When I visit the edit form
   */
  public function edit() {
    $this
      ->assertSession()
      ->elementExists('named', ['link', 'edit-form'])
      ->click();
  }

}
