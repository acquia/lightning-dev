<?php

use Behat\Gherkin\Node\PyStringNode;
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
   * Asserts that a form field is present.
   *
   * Oddly, MinkContext does not appear to provide a step definition for this.
   *
   * @param string $field
   *   The field locator.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The form field element.
   *
   * @Then I should see a(n) :field field
   */
  public function assertField($field) {
    return $this->assertSession()->fieldExists($field);
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
  public function assertNoField($field) {
    $this->assertSession()->fieldNotExists($field);
  }

  /**
   * Asserts that a select list has a particular option.
   *
   * @param string $select
   *   The select list to check.
   * @param string $option
   *   The option to look for.
   *
   * @Then the :select field should have a(n) :option option
   */
  public function assertOption($select, $option) {
    $this->assertSession()
      ->elementExists('named', ['option', $option], $this->assertField($select));
  }

  /**
   * Asserts that a select list has a set of options.
   *
   * @param string $select
   *   The select list to check.
   * @param \Behat\Gherkin\Node\PyStringNode $options
   *   The options to look for.
   *
   * @Then the :select field should have options:
   */
  public function assertOptions($select, PyStringNode $options) {
    foreach ($options->getStrings() as $option) {
      $this->assertOption($select, $option);
    }
  }

}
