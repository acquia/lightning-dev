<?php

namespace Drupal\Tests\lightning_dev\Unit;

use Acquia\Lightning\Commands\ReleaseHistoryClient;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

/**
 * Tests the ReleaseHistoryClient service.
 *
 * @group lightning_dev
 */
class ReleaseHistoryClientTest extends UnitTestCase {

  /**
   * Tests fetching the latest stable release.
   *
   * @param string $name
   *   The name of the Drupal project.
   * @param string $range
   *   The constraint range.
   * @param string $release_history
   *   The mock response body.
   * @param string $expected
   *   The expected result.
   *
   * @dataProvider getLatestStableReleaseProvider
   */
  public function testGetLatestStableRelease($name, $range, $release_history, $expected) {
    $client = $this->prophesize(Client::class);
    $client->get("https://updates.drupal.org/release-history/$name/8.x")
      ->shouldBeCalledOnce()
      ->willReturn(new Response(200, [], $release_history));
    $result = (new ReleaseHistoryClient($client->reveal()))->getLatestStableRelease($name, $range);
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for ::testGetLatestStableRelease().
   *
   * @return array
   *   The test data.
   */
  public function getLatestStableReleaseProvider() {
    $drupal_release_history = '
      <project>
        <releases>
          <release><version>8.6.2</version></release>
          <release><version>8.6.1</version></release>
          <release><version>8.5.8</version></release>
          <release><version>8.5.7</version></release>
        </releases>
      </project>
    ';
    $lightning_core_release_history = '
      <project>
        <releases>
          <release><version>8.x-3.2</version></release>
          <release><version>8.x-3.1</version></release>
          <release><version>8.x-2.11</version></release>
          <release><version>8.x-2.10</version></release>
        </releases>
      </project>
    ';

    return [
      'drupal latest' => [
        'name' => 'drupal',
        'range' => '8.6.x-dev',
        'release_history' => $drupal_release_history,
        'expected' => '8.6.2',
      ],
      'drupal 8.5.x-dev' => [
        'name' => 'drupal',
        'range' => '8.5.x-dev',
        'release_history' => $drupal_release_history,
        'expected' => '8.5.8',
      ],
      'lightning_core latest' => [
        'name' => 'lightning_core',
        'range' => '3.x-dev',
        'release_history' => $lightning_core_release_history,
        'expected' => '3.2',
      ],
      'lightning_core 2.x-dev' => [
        'name' => 'lightning_core',
        'range' => '2.x-dev',
        'release_history' => $lightning_core_release_history,
        'expected' => '2.11',
      ],
    ];
  }

}
