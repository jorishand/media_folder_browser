<?php

namespace Drupal\media_folder_browser\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\media_folder_browser\Entity\FolderEntity;
use Drupal\media_folder_browser\FolderStructureService;
use Drupal\media_folder_browser\MediaHelperService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystem;

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
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FileSystem $file_system, FolderStructureService $folder_structure_service, MediaHelperService $media_helper) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->folderStructure = $folder_structure_service;
    $this->mediaHelper = $media_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('media_folder_browser.folder_structure'),
      $container->get('media_folder_browser.media_helper')
    );
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
   * @param int $folder_id
   *   ID of the folder.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response.
   */
  public function moveMedia(int $media_id, int $folder_id) {
    /** @var \Drupal\media_folder_browser\Entity\FolderEntity $folder */
    if ($folder = $this->entityTypeManager->getStorage('folder_entity')->load($folder_id)) {
      /** @var \Drupal\media\Entity\Media $media */
      if ($media = $this->entityTypeManager->getStorage('media')->load($media_id)) {
        $file = $this->mediaHelper->getMediaFile($media);
        $dest = $this->buildUri($folder) . '/' . $file->getFilename();
        $moved_file = file_move($file, $dest);
        if ($moved_file) {
          $media->set('field_parent_folder', $folder_id);
          $media->save();
        }
      }
    }
    // ToDo: error responses and watchdog warnings.
    return $this->redirect('<front>');
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
