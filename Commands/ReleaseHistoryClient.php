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

    $prefix = "8.x-";
    $prefix_length = strlen($prefix);

    // Remove the '.x-dev' suffix from range if present.
    $suffix = '.x-dev';
    $suffix_position = -strlen($suffix);

    if (substr($range, $suffix_position) === $suffix) {
      $range = substr($range, 0, $suffix_position);
    }

    // Releases are ordered from newest to oldest.
    foreach ($release_history->releases->release as $release) {
      $release_version = (string) $release->version;

      // Remove the API version prefix from the release version if exists.
      // For example, if the release version is '8.x-3.2', it will be '3.2'.
      if (strncmp($release_version, $prefix, $prefix_length) === 0) {
        $release_version = substr($release_version, $prefix_length);
      }

      if (strncmp($release_version, $range, strlen($range)) === 0) {
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
