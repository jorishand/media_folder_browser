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
    $page = $this->getSession()->getPage();

    // Visit a node create page.
    $this->drupalGet('node/add/test_page');

    // Verify that both media widget instances are present.
    $assert_session->pageTextContains('Media unlimited');

    // Click the add media button.
    $open_browser_button = $assert_session->elementExists('css', '.folder-browser-add-button');
    $open_browser_button->click();

    // Check if the browser dialog is opened.
    $assert_session->waitForElementVisible('css', '.ui-dialog');
    $assert_session->pageTextContains('Media folder browser');

    // Check folder addition functionality.
    $folder_button = $assert_session->elementExists('css', '.js-submit-add-folder');
    $folder_button->click();

    $folder_item = $assert_session->waitForElementVisible('css', '.js-folder-item[data-id="1"]');
    $this->assertEquals('New folder', $folder_item->getText());

    // Add a folder using the context menu.
    $results_wrapper = $page->find('css', '.js-results-wrapper');
    $results_wrapper->rightClick();

    $context_folder_button = $assert_session->elementExists('css', '.option[data-action="add-folder"]');
    $context_folder_button->click();

    $folder_item_2 = $assert_session->waitForElementVisible('css', '.js-folder-item[data-id="2"]');
    $this->assertEquals('New folder 1', $folder_item_2->getText());

    // Test folder renaming functionality.
    $folder_item->rightClick();
    $context_rename = $assert_session->elementExists('css', '.option[data-action="rename"]');
    $context_rename->click();

    $folder_label = $folder_item->find('css', '.js-item-label');
    $this->assertTrue($folder_label->getAttribute('contenteditable'));

    $folder_label->setValue('test');
    $folder_label->blur();

    $this->assertEquals('testNew folder', $folder_label->getText());

    // Check if the folder name has been reloaded correctly in the sidebar.
    $assert_session->assertWaitOnAjaxRequest();

    $sidebar_folder = $assert_session->elementExists('css', '.js-tree-item[data-id="1"]');
    $this->assertEquals('testNew folder', $sidebar_folder->find('css', 'span')->getText());

    // Open add media form.
    $media_button = $assert_session->elementExists('css', '.js-submit-add-media');
    $media_button->click();

    $assert_session->waitForElementVisible('css', '.mfb-upload-form');
    $upload_wrapper = $page->find('css', '.js-upload-wrapper');
    $this->assertFalse($upload_wrapper->hasClass('hidden'));

    // Click cancel button.
    $this->submitForm([], 'Cancel');
    $assert_session->assertWaitOnAjaxRequest();

    $this->assertTrue($upload_wrapper->hasClass('hidden'));

    // Open add media form using the context menu.
    $results_wrapper->rightClick();

    $context_media_button = $assert_session->elementExists('css', '.option[data-action="add-media"]');
    $context_media_button->click();

    $assert_session->waitForElementVisible('css', '.mfb-upload-form');
    $this->assertFalse($upload_wrapper->hasClass('hidden'));
    // Todo: test upload form.
    // Click cancel button.
    $this->submitForm([], 'Cancel');
    $assert_session->assertWaitOnAjaxRequest();

    $this->assertTrue($upload_wrapper->hasClass('hidden'));

    // Move media test.
    $media_item = $assert_session->elementExists('css', '.js-media-item[data-id="1"]');
    $media_item->rightClick();

    $move_to_button = $assert_session->elementExists('css', '.option:not([data-action])');
    $move_to_button->mouseOver();
    $context_move_option = $assert_session->elementExists('css', '.option[data-action="move"][data-id="1"]');
    $context_move_option->click();
    $assert_session->assertWaitOnAjaxRequest();

    $assert_session->pageTextNotContains('test_item');

  }

}
