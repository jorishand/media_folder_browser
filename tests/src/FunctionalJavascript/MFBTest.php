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
    $this->getSession()->maximizeWindow();

    // Visit a node create page.
    $this->drupalGet('node/add/test_page');

    // Verify that both media widget instances are present.
    $assert_session->pageTextContains('Media unlimited');

    // Click the add media button.
    $add_button = $assert_session->elementExists('css', '.folder-browser-add-button');
    $add_button->click();

    // Check if the browser dialog is opened.
    $assert_session->waitForElementVisible('css', '.ui-dialog');
    $assert_session->pageTextContains('Media folder browser');

    // Check folder addition functionality.
    $folder_button = $assert_session->elementExists('css', '.js-submit-add-folder');
    $folder_button->click();

    $folder_item = $assert_session->waitForElementVisible('css', '.js-folder-item[data-id="1"]');
    $this->assertEquals('New folder', $folder_item->getText());

    // Add a folder using the context menu.
    $this->getSession()->getPage()->find('css', '.js-results-wrapper')->rightClick();

    $context_folder_button = $assert_session->elementExists('css', '.option[data-action="add-folder"]');
    $context_folder_button->click();

    $folder_item_2 = $assert_session->waitForElementVisible('css', '.js-folder-item[data-id="2"]');
    $this->assertEquals('New folder 1', $folder_item_2->getText());

    // Test renaming functionality.
    $folder_item->rightClick();
    $context_rename = $assert_session->elementExists('css', '.option[data-action="rename"]');
    $context_rename->click();

    $folder_label = $folder_item->find('css', '.js-item-label');
    $this->assertTrue($folder_label->getAttribute('contenteditable'));

    $folder_label->setValue('test');
    $folder_label->blur();

    $this->assertEquals('testNew folder', $folder_label->getText());
  }

}
