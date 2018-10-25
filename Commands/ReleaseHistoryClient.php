<?php

namespace Acquia\Lightning\Commands;

use GuzzleHttp\Client;

/**
 * Class to fetch release history information about Drupal projects.
 */
final class ReleaseHistoryClient {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  private $httpClient;

  /**
   * ReleaseHistoryClient constructor.
   *
   * @param \GuzzleHttp\Client $http_client
   *   The HTTP client.
   */
  public function __construct(Client $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * Fetches the latest stable release of a project from Drupal.org.
   *
   * @param string $name
   *   The name of the Drupal project (e.g. 'lightning_core').
   * @param string $range
   *   The constraint range (e.g. '3.x-dev').
   *
   * @return string
   *   The latest stable release (e.g. '3.2'), or empty string if not found.
   */
  public function getLatestStableRelease($name, $range) {
    $release_history = $this->getReleaseHistory($name);

    // Remove the '.x-dev' suffix from range if present.
    $range = preg_replace('#\.x-dev$#', '', $range);
    $range_length = strlen($range);

    // Releases are ordered from newest to oldest.
    foreach ($release_history->releases->release as $release) {
      $release_version = (string) $release->version;
      // Remove the '8.x-' prefix from release version if present.
      $release_version = preg_replace('#^8\.x-#', '', $release_version);

      if (strncmp($release_version, $range, $range_length) === 0) {
        return $release_version;
      }
    }

    return '';
  }

  /**
   * Fetches a project's release history from Drupal.org.
   *
   * @param string $name
   *   The name of the Drupal project (e.g. 'lightning_core').
   *
   * @return \SimpleXMLElement
   *   The parsed XML response.
   */
  private function getReleaseHistory($name) {
    $uri = "https://updates.drupal.org/release-history/$name/8.x";
    $data = $this->httpClient->get($uri)->getBody()->getContents();

    return simplexml_load_string($data);
  }

}
