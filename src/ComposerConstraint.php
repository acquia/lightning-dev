<?php

namespace Drupal\lightning_dev;

/**
 * Class to perform operations on a composer constraint.
 */
final class ComposerConstraint {

  /**
   * Raw constraint.
   *
   * E.g. '^2.8 || ^3.0'.
   *
   * @var string
   */
  private $constraint = '';

  /**
   * ComposerConstraint constructor.
   *
   * @param string $constraint
   *   Raw constraint.
   */
  public function __construct($constraint) {
    $this->constraint = $constraint;
  }

  /**
   * Returns the constraint's ranges as an array.
   *
   * @see https://getcomposer.org/doc/articles/versions.md#version-range
   *
   * @return string[]
   *   Constraint's ranges. For example, if the constraint is '^2.8 || ^3.0',
   *   it will return ['^2.8', '^3.0'].
   */
  private function getRanges() {
    preg_match_all('/[0-9a-zA-Z\~\>\=\-\<\.\^\*]+/', $this->constraint, $matches);

    return $matches[0];
  }

  /**
   * Returns the core dev version of the constraint.
   *
   * In the core dev version the last digits of the constraint's ranges are
   * replaced by the string 'x-dev', and their operators are removed.
   *
   * @return string
   *   Core dev version. For example, if the constraint is '8.4.3 || ^8.5.3',
   *   it will return '8.4.x-dev || 8.5.x-dev'.
   */
  public function getCoreDev() {
    return $this->getDev([static::class, 'coreRangeToDev']);
  }

  /**
   * Returns the lightning dev version of the constraint.
   *
   * In the lightning dev version the first digits of the constraint's ranges are
   * concatenated with the string 'x-dev', and their operators are removed.
   *
   * @return string
   *   Lightning dev version. For example, if the constraint is '^1.3.0 || ^2.3.0',
   *   it will return '1.x-dev || 2.x-dev'.
   */
  public function getLightningDev() {
    return $this->getDev([static::class, 'lightningRangeToDev']);
  }

  /**
   * @param callable $callback
   *
   * @return string
   */
  private function getDev($callback) {
    $dev = $this->constraint;
    $ranges = $this->getRanges();

    foreach ($ranges as $oldRange) {
      $newRange = $callback($oldRange);
      $dev = str_replace($oldRange, $newRange, $dev);
    }

    return $dev;
  }

  /**
   * @param string $range
   *   E.g. '8.5.3'.
   * @return string
   *   E.g. '8.5.x-dev'.
   */
  private static function coreRangeToDev($range) {
    $numeric = preg_replace('/[^0-9\.]+/', NULL, $range);
    $dev = preg_replace('/\.[0-9]+$/', '.x-dev', $numeric);

    return $dev;
  }

  /**
   * @param string $range
   *   E.g. '1.3.0'.
   * @return string
   *   E.g. '1.x-dev'.
   */
  private static function lightningRangeToDev($range) {
    $numeric = preg_replace('/[^0-9\.]+/', NULL, $range);
    $dev = preg_replace('/^([0-9])+\..*/', '$1.x-dev', $numeric);

    return $dev;
  }

}
