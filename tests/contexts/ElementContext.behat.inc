<?php

use Behat\Mink\Exception\ExpectationException;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;

/**
 * Contains miscellaneous step definitions for working with HTML elements.
 */
class ElementContext extends DrupalSubContextBase {

  /**
   * Asserts that an element is empty.
   *
   * @param string $selector
   *   The element's CSS selector.
   *
   * @throws ExpectationException
   *   If the element contains any HTML (leading and trailing white space is
   *   trimmed out).
   *
   * @Then the :selector element(s) should be empty
   */
  public function assertEmptyElement($selector) {
    $content = $this->assertSession()
      ->elementExists('css', $selector)
      ->getHtml();

    if (trim($content)) {
      throw new ExpectationException("Expected element '$selector' to be empty, but it is not.", $this->getSession()->getDriver());
    }
  }

}
