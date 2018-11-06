<?php

namespace Drupal\media_folder_browser\Controller;

use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Renderer;
use Drupal\image\Entity\ImageStyle;
use Drupal\media_folder_browser\Ajax\RefreshMFBCommand;
use Drupal\media_folder_browser\Entity\FolderEntity;
use Drupal\media_folder_browser\FolderStructureService;
use Drupal\media_folder_browser\MediaHelperService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Ajax\AjaxResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route responses for media folders.
 */
class MediaFolderController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * File system.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * Folder structure service.
   *
   * @var \Drupal\media_folder_browser\FolderStructureService
   */
  protected $folderStructure;

  /**
   * Media helper service.
   *
   * @var \Drupal\media_folder_browser\MediaHelperService
   */
  protected $mediaHelper;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Constructs a new MediaFolderController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileSystem $file_system
   *   The file system.
   * @param \Drupal\media_folder_browser\FolderStructureService $folder_structure_service
   *   The folder structure service.
   * @param \Drupal\media_folder_browser\MediaHelperService $media_helper
   *   The media helper service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FileSystem $file_system, FolderStructureService $folder_structure_service, MediaHelperService $media_helper, Renderer $renderer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->folderStructure = $folder_structure_service;
    $this->mediaHelper = $media_helper;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('media_folder_browser.folder_structure'),
      $container->get('media_folder_browser.media_helper'),
      $container->get('renderer')
    );
  }

  /**
   * Renders the browser.
   *
   * @return array
   *   A render array.
   */
  public function renderBrowser() {
    $element = [
      '#theme' => 'folder_browser_overview',
      '#sidebar_folders' => $this->getFolderTree(),
      '#results' => $this->getFolderContents(),
      '#attached' => ['library' => ['media_folder_browser/browser']],
    ];
    return $element;
  }

  /**
   * Callback to refresh the overview results.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function refreshResults(Request $request) {
    $response = new AjaxResponse();
    $folder_id = $request->query->get('id');

    if (!$folder_id) {
      // $folder_id should be NULL instead of an empty string.
      $folder_id = NULL;
    }

    $results = $this->getFolderContents($folder_id);
    $results = $this->renderer->render($results);

    $response->addCommand(new ReplaceCommand('.js-results-wrapper', $results));
    $response->addCommand(new InvokeCommand('.loader-container', 'addClass', ['hidden']));

    return $response;
  }

  /**
   * Gets child folders and media for a specific folder as a renderable array.
   *
   * @param int|null $folder_id
   *   ID of the folder.
   *
   * @return array
   *   A render array.
   */
  public function getFolderContents(int $folder_id = NULL) {
    $results = [];

    /** @var \Drupal\media_folder_browser\Entity\FolderEntity $folder_entity */
    $folder_entity = $this->entityTypeManager->getStorage('folder_entity')->load($folder_id);
    if ($folder_entity) {
      $folders = $this->folderStructure->getFolderChildren($folder_entity);
      $media = $this->folderStructure->getFolderMediaChildren($folder_entity);
    }
    else {
      $folders = $this->folderStructure->getRootFolders();
      $media = $this->mediaHelper->getRootMedia();
    }

    // Add child folders to results.
    /** @var \Drupal\media_folder_browser\Entity\FolderEntity $folder */
    foreach ($folders as $folder) {
      $results[] = [
        '#theme' => 'folder_browser_folder_item',
        '#id' => $folder->id(),
        '#name' => $folder->getName(),
      ];
    }

    // Add child media entities to results.
    /** @var \Drupal\media\Entity\Media $media_item */
    foreach ($media as $media_item) {
      $thumbnail = ImageStyle::load('mfb_thumbnail')->buildUrl($media_item->get('thumbnail')[0]->entity->uri->value);

      // ToDo: make this configurable in a config form.
      // Set icon based on media type.
      switch ($media_item->bundle()) {
        case 'image':
          $icon = '/assets/photo-camera.svg';
          break;

        case 'video':
        case 'remote_video':
          $icon = '/assets/play-button-on-film-strip.svg';
          break;

        case 'audio':
          $icon = '/assets/radio-microphone.svg';
          break;

        default:
          $icon = '/assets/text-and-image-document.svg';
          break;
      }
      $icon = base_path() . drupal_get_path('module', 'media_folder_browser') . $icon;
      $file = $this->mediaHelper->getMediaFile($media_item);
      $size = $file->getSize();
      $type = $file->getMimeType();
      $results[] = [
        '#theme' => 'folder_browser_media_item',
        '#id' => $media_item->id(),
        '#icon' => $icon,
        '#name' => $media_item->getName(),
        '#size' => format_size($size),
        '#type' => $type,
        '#thumbnail' => $thumbnail,
      ];
    }

    return [
      '#theme' => 'folder_browser_folder_results',
      '#results' => $results,
    ];
  }

  /**
   * Gets the folder tree as a renderable array.
   *
   * @return array
   *   A render array.
   */
  public function getFolderTree() {
    // Load first level folders.
    $root_folders = $this->folderStructure->getRootFolders();

    $children = [];
    foreach ($root_folders as $folder) {
      $children[] = $this->getFolderTreeItem($folder);
    }

    return [
      '#theme' => 'folder_browser_folder_tree',
      '#children' => $children,
    ];
  }

  /**
   * Recursive function to prepare a folder tree render array.
   *
   * @param \Drupal\media_folder_browser\Entity\FolderEntity $folder
   *   Folder entity.
   *
   * @return array
   *   A render array.
   */
  public function getFolderTreeItem(FolderEntity $folder) {
    $child_folders = $this->folderStructure->getFolderChildren($folder);

    $children = [];
    foreach ($child_folders as $child_folder) {
      $children[] = $this->getFolderTreeItem($child_folder);
    }

    return [
      '#theme' => 'folder_browser_folder_tree_item',
      '#name' => $folder->getName(),
      '#id' => $folder->id(),
      '#children' => $children,
    ];
  }

  /**
   * Creates a new folder entity.
   *
   * @param string $name
   *   Name of the folder.
   * @param int|null $parent_id
   *   ID of the parent folder.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response.
   */
  public function addFolder(string $name, int $parent_id = NULL) {
    // Add folder entity to storage.
    $storage = $this->entityTypeManager->getStorage('folder_entity');
    /** @var \Drupal\media_folder_browser\Entity\FolderEntity $folder_entity */
    $folder_entity = $storage->create([
      'name' => $name,
      'parent' => $parent_id,
    ]);
    $storage->save($folder_entity);

    $uri = $this->buildUri($folder_entity);
    $this->fileSystem->mkdir($uri, NULL, TRUE);

    return $this->redirect('<front>');
  }

  /**
   * Callback to delete a folder entity.
   *
   * @param int $folder_id
   *   ID of the folder.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response.
   */
  public function removeFolder(int $folder_id = NULL) {
    $this->recursiveDelete($folder_id);
    return $this->redirect('<front>');
  }

  /**
   * Callback to move a media entity to a different folder.
   *
   * @param int $media_id
   *   ID of the media entity.
   * @param int|null $folder_id
   *   ID of the folder or null for root.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   A redirect response.
   */
  public function moveMedia(int $media_id, $folder_id) {
    $response = new AjaxResponse();

    /** @var \Drupal\media\Entity\Media $media */
    if ($media = $this->entityTypeManager->getStorage('media')->load($media_id)) {
      // Save current folder for the refresh command.
      $current_folder = $media->get('field_parent_folder')->referencedEntities();
      if (empty($current_folder)) {
        $current_folder_id = NULL;
      }
      else {
        $current_folder_id = $current_folder[0]->id();
      }
      // Move the file entity to the new folder.
      $file = $this->mediaHelper->getMediaFile($media);
      if ($folder_id !== NULL) {
        /** @var \Drupal\media_folder_browser\Entity\FolderEntity $folder */
        if ($folder = $this->entityTypeManager->getStorage('folder_entity')->load($folder_id)) {
          $dest = $this->buildUri($folder) . '/' . $file->getFilename();
        }
      }
      else {
        // Move to root if the folder ID is NULL.
        $dest = 'public://';
      }
      $moved_file = file_move($file, $dest);
      if ($moved_file) {
        if ($folder_id !== NULL) {
          $media->set('field_parent_folder', $folder_id);
        }
        else {
          $media->set('field_parent_folder', NULL);
        }
        $media->save();
        $response->addCommand(new RefreshMFBCommand($current_folder_id));
      }
    }
    // ToDo: error responses and watchdog warnings.
    return $response;
  }

  /**
   * Callback to move a media entity to the parenting folder.
   *
   * @param int $media_id
   *   ID of the media entity.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   A redirect response.
   */
  public function moveMediaParent(int $media_id) {
    // Load media entity.
    /** @var \Drupal\media\Entity\Media $media */
    if ($media = $this->entityTypeManager->getStorage('media')->load($media_id)) {
      // Get parent folder entity.
      /** @var \Drupal\media_folder_browser\Entity\FolderEntity $folder */
      $folder = $media->get('field_parent_folder')->referencedEntities()[0];
      if ($folder) {
        // Get folder entity parent ID.
        /** @var \Drupal\media_folder_browser\Entity\FolderEntity $parent_folder */
        $parent_folder = $folder->get('parent')->referencedEntities()[0];
        $parent_folder_id = NULL;
        if ($parent_folder) {
          $parent_folder_id = $parent_folder->id();
        }
        return $this->moveMedia($media_id, $parent_folder_id);
      }
    }
    // ToDo: error responses and watchdog warnings.
    return new AjaxResponse();
  }

  /**
   * Remove a folder entity and its children recursively.
   *
   * @param int $folder_id
   *   ID of the folder.
   */
  private function recursiveDelete(int $folder_id = NULL) {
    $storage = $this->entityTypeManager->getStorage('folder_entity');
    /** @var \Drupal\media_folder_browser\Entity\FolderEntity $folder_entity */
    $folder_entity = $storage->load($folder_id);

    $children = $this->folderStructure->getFolderChildren($folder_entity);

    // Remove child folder entities.
    /** @var \Drupal\media_folder_browser\Entity\FolderEntity $childFolder */
    foreach ($children as $childFolder) {
      $this->recursiveDelete($childFolder->id());
    }

    // Remove child media entities.
    $media_entities = $this->folderStructure->getFolderMediaChildren($folder_entity);
    /** @var \Drupal\media\Entity\Media $media */
    foreach ($media_entities as $media) {
      $media->delete();
    }

    // Remove directory from file system.
    $storage->delete([$folder_entity]);
    $uri = $this->buildUri($folder_entity);
    $this->fileSystem->rmdir($uri);
  }

  /**
   * Recursively creates an URI based on the parent folder's name.
   *
   * @param \Drupal\media_folder_browser\Entity\FolderEntity $folderEntity
   *   Folder entity.
   * @param string $uri
   *   URI to start from (used for recursion).
   *
   * @return string
   *   The URI.
   */
  private function buildUri(FolderEntity $folderEntity, string $uri = '') {
    $uri = empty($uri) ? '' : '/' . $uri;
    $uri = $folderEntity->getName() . $uri;

    if ($folderEntity->hasParent()) {
      /** @var \Drupal\media_folder_browser\Entity\FolderEntity $parent */
      $parent = $this->entityTypeManager
        ->getStorage('folder_entity')
        ->load($folderEntity->get('parent')->target_id);
      return $this->buildUri($parent, $uri);
    }

    return 'public://' . $uri;
  }

}
