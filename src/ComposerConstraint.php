<?php

namespace Drupal\lightning_dev;

class ComposerConstraint {

  /**
   * E.g. '^2.8 || ^3.0'.
   *
   * @var string
   */
  private $constraint = '';

  /**
   * @param string $constraint
   */
  public function __construct($constraint) {
    $this->constraint = $constraint;
  }

  /**
   * @return string[]
   *   E.g. ['^2.8', '^3.0'].
   */
  public function getRanges() {
    preg_match_all('/[0-9a-zA-Z\~\>\=\-\<\.\^\*]+/', $this->constraint, $matches);

    return $matches[0];
  }

  /**
   * @return string
   *   E.g. '2.x-dev || 3.x-dev'.
   */
  public function getDev() {
    $dev = $this->constraint;
    $ranges = $this->getRanges();

    foreach ($ranges as $oldRange) {
      $newRange = static::rangeToDev($oldRange);
      $dev = str_replace($oldRange, $newRange, $dev);
    }

    return $dev;
  }

  /**
   * @param string $range
   *   E.g. '^2.8'.
   * @return string
   *   E.g. '2.x-dev'.
   */
  public static function rangeToDev($range) {
    $numeric = preg_replace('/[^0-9\.]+/', NULL, $range);
    $dev = preg_replace('/\.[0-9]+$/', '.x-dev', $numeric);

    return $dev;
  }

}
