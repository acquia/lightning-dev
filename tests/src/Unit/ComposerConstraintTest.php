<?php

namespace Drupal\Tests\lightning_dev\Unit;

use Drupal\lightning_dev\ComposerConstraint;
use Drupal\Tests\UnitTestCase;

/**
 * @group lightning
 * @group lightning_dev
 */
class ComposerConstraintTest extends UnitTestCase {

  /**
   * @dataProvider getLightningDevProvider
   */
  public function testGetLightningDev($constraint, $expected) {
    $actual = (new ComposerConstraint($constraint))->getLightningDev();
    $this->assertEquals($expected, $actual);
  }

  public function getLightningDevProvider() {
    return [
      [
        'constraint' => '1.3.0',
        'expected' => '1.x-dev',
      ],
      [
        'constraint' => '^1.3.0',
        'expected' => '1.x-dev',
      ],
      [
        'constraint' => '^1.3.0 || ^2.3.0',
        'expected' => '1.x-dev || 2.x-dev',
      ],
    ];
  }

  /**
   * @dataProvider getCoreDevProvider
   */
  public function testGetCoreDev($constraint, $expected) {
    $actual = (new ComposerConstraint($constraint))->getCoreDev();
    $this->assertEquals($expected, $actual);
  }

  public function getCoreDevProvider() {
    return [
      [
        'constraint' => '8.5.3',
        'expected' => '8.5.x-dev',
      ],
      [
        'constraint' => '^8.5.3',
        'expected' => '8.5.x-dev',
      ],
      [
        'constraint' => '8.5.3 || 8.6.3',
        'expected' => '8.5.x-dev || 8.6.x-dev',
      ],
    ];
  }

}
