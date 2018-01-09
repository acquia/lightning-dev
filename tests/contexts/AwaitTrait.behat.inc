<?php

use Behat\Mink\Exception\UnsupportedDriverActionException;
use Drupal\DrupalExtension\Context\MinkContext;

trait AwaitTrait {

  /**
   * Waits for an element to exist.
   *
   * @param string $selector
   *   The element's CSS selector.
   * @param int $timeout
   *   (optional) How long, in seconds, to wait before timing out.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The awaited element.
   */
  protected function awaitElement($selector, $timeout = 10) {
    $js = 'document.querySelector("' . addslashes($selector) . '")';

    try {
      $this->getSession()->wait($timeout * 1000, $js);
    }
    catch (UnsupportedDriverActionException $e) {
      sleep($timeout);
    }

    return $this->assertSession()->elementExists('css', $selector);
  }

  /**
   * Waits for AJAX to finish.
   *
   * If the Mink context is unavailable, or the current driver does not support
   * waiting for a JavaScript condition, waits $timeout seconds and returns.
   *
   * @param int $timeout
   *   (optional) How many seconds to wait.
   */
  protected function awaitAjax($timeout = 10) {
    /** @var MinkContext $context */
    $context = $this->getContext(MinkContext::class);

    if ($context) {
      try {
        return $context->iWaitForAjaxToFinish();
      }
      catch (UnsupportedDriverActionException $e) {
        // Fall through to sleep().
      }
    }
    sleep($timeout);
  }

}