<?php

use Drupal\DrupalExtension\Context\DrupalSubContextBase;

/**
 * Contains step definitions to assist with debugging tests.
 */
class DebugContext extends DrupalSubContextBase {

  /**
   * Waits a while, for debugging.
   *
   * @param int $seconds
   *   How long to wait.
   *
   * @When I wait :seconds second(s)
   */
  public function wait($seconds) {
    sleep($seconds);
  }

  /**
   * Takes a screenshot for debugging purposes.
   *
   * @param string $filename
   *   (optional) The name of the screenshot file.
   *
   * @When I take a screenshot
   * @When I take a screenshot named :filename
   */
  public function capture($filename = NULL) {
    $filename = $filename ?: uniqid('screenshot_');
  
    $screenshot = $this->getSession()->getDriver()->getScreenshot();
    // If this file is in tests/features/bootstrap, the screenshot be in tests.
    file_put_contents(__DIR__ . '../../' . $filename . '.png', $screenshot);
  }
  
  /**
   *  Dumps the HTML directly to stdout.
   *
   * @When I dump the HTML
   */
  public function dump() {
    echo $this->getSession()->getDriver()->getContent();
  }
  
  /**
   *  Dumps the HTML directly to stdout.
   *
   * @When I dump the HTML to a file
   * @When I dump the HTML to :file
   */
  public function dumpToFile($filename = NULL) {
    $filename = $filename ?: uniqid('dump_');
    file_put_contents("$filename.html", $this->getSession()->getDriver()->getContent());
  }

}
