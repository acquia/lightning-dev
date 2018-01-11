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

  /**
   * Switches to a frame.
   *
   * @param string $name
   *   The frame name.
   *
   * @When I switch to the :name frame
   * @When I enter the :name frame
   */
  public function enterFrame($name) {
    $this->getSession()->switchToIFrame($name);
  }

  /**
   * Switches out of an frame, into the main window.
   *
   * @When I (switch|return) to the window
   * @When I (exit|leave) the frame
   */
  public function exitFrame() {
    $this->getSession()->switchToWindow();
  }

  /**
   * Asserts that vertical tab set exists.
   *
   * @param ElementInterface $container
   *   (optional) The element containing the vertical tab set.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The vertical tab set.
   *
   * @Then I should see a set of vertical tabs
   */
  public function assertVerticalTabs(ElementInterface $container = NULL) {
    return $this
      ->assertSession()
      ->elementExists('css', 'ul.vertical-tabs__menu', $container);
  }

  /**
   * Asserts that a specific vertical tab exists.
   *
   * @param string $tab
   *   The text of the tab.
   * @param ElementInterface $container
   *   (optional) The element containing the vertical tab set.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The vertical tab.
   *
   * @Then I should see a :tab vertical tab
   * @Then I should see the :tab vertical tab
   */
  public function assertVerticalTab($tab, ElementInterface $container = NULL) {
    return $this
      ->assertSession()
      ->elementExists(
        'named',
        ['link', $tab],
        $this->assertVerticalTabs($container)
      );
  }

  /**
   * Asserts that a specific vertical tab does not exist.
   *
   * @param string $tab
   *   The text of the tab.
   * @param ElementInterface $container
   *   (optional) The element containing the vertical tab set.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The vertical tab.
   *
   * @Then I should not see a :tab vertical tab
   * @Then I should not see the :tab vertical tab
   */
  public function assertNoVerticalTab($tab, ElementInterface $container = NULL) {
    $this
      ->assertSession()
      ->elementNotExists(
        'named',
        ['link', $tab],
        $this->assertVerticalTabs($container)
      );
  }

  /**
   * Asserts that a specific set of vertical tabs exists.
   *
   * @param \Behat\Gherkin\Node\PyStringNode $tabs
   *   The text of the tabs, one per line.
   * @param ElementInterface $container
   *   (optional) The element containing the vertical tab set.
   *
   * @Then I should see (the )vertical tabs:
   */
  public function assertVerticalTabMultiple(PyStringNode $tabs, ElementInterface $container = NULL) {
    foreach ($tabs->getStrings() as $tab) {
      $this->assertVerticalTab($tab, $container);
    }
  }

  /**
   * Asserts that a set of specific vertical tabs exists.
   *
   * @param \Behat\Gherkin\Node\PyStringNode $tabs
   *   The text of the tabs, one per line.
   * @param ElementInterface $container
   *   (optional) The element containing the vertical tab set.
   *
   * @Then I should not see (the )vertical tabs:
   */
  public function assertNotVerticalTabMultiple(PyStringNode $tabs, ElementInterface $container = NULL) {
    foreach ($tabs->getStrings() as $tab) {
      $this->assertNoVerticalTab($tab, $container);
    }
  }

}
