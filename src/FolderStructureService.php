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
   * Gets folder entities that have no parent.
   *
   * @return array
   *   The folders.
   */
  public function getRootFolders() {
    $storage = $this->entityTypeManager->getStorage('folder_entity');

    $nids = \Drupal::entityQuery('folder_entity')
      ->notExists('parent')
      ->sort('name', 'ASC')
      ->execute();

    return $storage->loadMultiple($nids);
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
    $entities = $storage->loadByProperties(['parent' => $folder->id()]);
    uasort($entities, [$this, 'sortByName']);
    return $entities;
  }

  /**
   * Compare function to sort folder entities by name.
   *
   * @param \Drupal\media_folder_browser\Entity\FolderEntity $a
   *   Folder entity a.
   * @param \Drupal\media_folder_browser\Entity\FolderEntity $b
   *   Folder entity b.
   *
   * @return array
   *   The children.
   */
  private function sortByName(FolderEntity $a, FolderEntity $b) {
    return strcmp($a->get('name')->value, $b->get('name')->value);
  }

}
