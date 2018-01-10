<?php

use Behat\Mink\Element\ElementInterface;
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

  /**
   * Clears a field.
   *
   * @param string $field
   *   The field to clear.
   * @param ElementInterface $container
   *   (optional) The element that contains the field.
   *
   * @When I clear :field
   */
  public function clearField($field, ElementInterface $container = NULL) {
    $this->assertSession()->fieldExists($field, $container)->setValue(FALSE);
  }

  /**
   * Asserts that a form field is not present.
   *
   * Oddly, MinkContext does not appear to provide a step definition for this.
   *
   * @param string $field
   *   The field locator.
   *
   * @Then I should not see a(n) :field field
   */
  public function assertFieldNotExists($field) {
    $this->assertSession()->fieldNotExists($field);
  }

}
