<?php

namespace Drupal\media_folder_browser;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\media_folder_browser\Entity\FolderEntity;

/**
 * Class FolderStructureService.
 */
class FolderStructureService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * FolderStructureService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Gets child folder entities for a given folder.
   *
   * @param \Drupal\media_folder_browser\Entity\FolderEntity $folder
   *   The folder entity.
   *
   * @return array
   *   The children.
   */
  public function getFolderChildren(FolderEntity $folder) {
    $storage = $this->entityTypeManager->getStorage('folder_entity');
    return $storage->loadByProperties(['parent' => $folder->id()]);
  }

  /**
   * Gets child media entities for a given folder.
   *
   * @param \Drupal\media_folder_browser\Entity\FolderEntity $folder
   *   The folder entity.
   *
   * @return array
   *   The children.
   */
  public function getFolderMediaChildren(FolderEntity $folder) {
    $storage = $this->entityTypeManager->getStorage('media');
    return $storage->loadByProperties(['field_parent_folder' => $folder->id()]);
  }

}
