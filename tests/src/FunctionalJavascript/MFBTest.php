<?php

namespace Drupal\Tests\media_folder_browser\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Contains Media folder browser integration tests.
 *
 * @group media_folder_browser
 */
class MFBTest extends WebDriverTestBase {

  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['mfb_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a media item to manipulate in the test.
    $time = time();
    $entity = Media::create(['name' => 'test_item', 'bundle' => 'test_image']);
    $entity->setCreatedTime(++$time);
    $entity->save();

    // Create a user who can use the browser.
    $user = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'access media overview',
      'edit own test_page content',
      'create test_page content',
      'create media',
      'delete any media',
      'view media',
      'administer media folder browser',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests that the Media library's widget works as expected.
   */
  public function testWidget() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Visit a node create page.
    $this->drupalGet('node/add/test_page');

    // Verify that both media widget instances are present.
    $assert_session->pageTextContains('Media unlimited');

    // Click the add media button.
    $add_button = $page->findLink('Add media');
    $this->assertTrue($add_button->isVisible(), 'Add media button exists.');
    $add_button->click();

    // Check if the browser dialog is opened.
    $assert_session->waitForElementVisible('css', '.ui-dialog');
    $assert_session->pageTextContains('Media folder browser');
  }

}
