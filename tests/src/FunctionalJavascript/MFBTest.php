<?php

namespace Drupal\Tests\media_library\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Contains Media folder browser integration tests.
 *
 * @group media_library
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

    // Create some example folders.
    $folders = [
      'rootfolder 1' => [
        'subfolder 1',
        'subfolder 2',
      ],
      'rootfolder 2' => [],
    ];

    $time = time();

    $folderStorage = $this->container->get('entity_type.manager')->getStorage('folder_entity');
    $fileSystem = $this->container->get('file_system');
    $folderStructure = $this->container->get('media_folder_browser.folder_structure');

    foreach ($folders as $key => $children) {
      $folder_entity = $folderStorage->create([
        'name' => $key,
        'parent' => NULL,
      ]);
      $folderStorage->save($folder_entity);
      $uri = $folderStructure->buildUri($folder_entity);
      $fileSystem->mkdir($uri, NULL, TRUE);

      foreach ($children as $child) {
        $folder_entity = $folderStorage->create([
          'name' => $child,
          'parent' => $key,
        ]);
        $folderStorage->save($folder_entity);
        $uri = $folderStructure->buildUri($folder_entity);
        $fileSystem->mkdir($uri, NULL, TRUE);

        // Put a media item in one of the folders.
        if ($child === 'subfolder 2') {
          $entity = Media::create(['name' => 'Media test', 'bundle' => 'test_image']);
          $entity->setCreatedTime(++$time);
          $entity->save();
        }
      }
    }

    // Create a user who can use the browser.
    $user = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'access media overview',
      'edit own basic_page content',
      'create basic_page content',
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
    $this->createScreenshot(\Drupal::root() . '/sites/default/files/simpletest/browserclosed.png');

    // Open the browser.
    $add_button = $assert_session->elementExists('css',
      '.folder-browser-add-button');
    $add_button->click();
    $assert_session->assertWaitOnAjaxRequest();

    $this->createScreenshot(\Drupal::root() . '/sites/default/files/simpletest/browseropen.png');
  }

}
