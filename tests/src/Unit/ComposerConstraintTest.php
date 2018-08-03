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
   * @dataProvider rangeToDevProvider
   */
  public function testRangeToDev($range, $expected) {
    $actual = ComposerConstraint::rangeToDev($range);
    $this->assertEquals($expected, $actual);
  }

  public function rangeToDevProvider() {
    return [
      [
        'range' => '2.1',
        'expected' => '2.x-dev',
      ],
      [
        'range' => '^2.8',
        'expected' => '2.x-dev',
      ],
      [
        'range' => '8.5.3',
        'expected' => '8.5.x-dev',
      ],
    ];
  }

  /**
   * @dataProvider getRangesProvider
   */
  public function testGetRanges($constraint, $expected) {
    $actual = (new ComposerConstraint($constraint))->getRanges();
    $this->assertArrayEquals($expected, $actual);
  }

  public function getRangesProvider() {
    return [
      [
        'constraint' => '^2.8 || ^3.0',
        'expected' => [
          '^2.8',
          '^3.0',
        ],
      ],
      [
        'constraint' => '>=1.0 <1.1 || >=1.2',
        'expected' => [
          '>=1.0',
          '<1.1',
          '>=1.2',
        ],
      ],
      [
        'constraint' => '>=1.0.0.0-dev <3.0.0.0-dev',
        'expected' => [
          '>=1.0.0.0-dev',
          '<3.0.0.0-dev',
        ],
      ],
    ];
  }

  /**
   * @dataProvider getDevProvider
   */
  public function testGetDev($constraint, $expected) {
    $actual = (new ComposerConstraint($constraint))->getDev();
    $this->assertEquals($expected, $actual);
  }

  public function getDevProvider() {
    return [
      [
        'contraint' => '2.8',
        'expected' => '2.x-dev',
      ],
      [
        'contraint' => '^2.8',
        'expected' => '2.x-dev',
      ],
      [
        'contraint' => '^2.8 || ^3.0',
        'expected' => '2.x-dev || 3.x-dev',
      ],
    ];
  }

}
