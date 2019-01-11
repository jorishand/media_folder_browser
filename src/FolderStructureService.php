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
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getRootFolders() {
    $storage = $this->entityTypeManager->getStorage('folder_entity');

    $nids = \Drupal::entityQuery('folder_entity')
      ->notExists('parent')
      ->execute();

    $entities = $storage->loadMultiple($nids);
    uasort($entities, [$this, 'sortByName']);
    return $entities;
  }

  /**
   * Gets child folder entities for a given folder.
   *
   * @param \Drupal\media_folder_browser\Entity\FolderEntity $folder
   *   The folder entity.
   *
   * @return array
   *   The children.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getFolderChildren(FolderEntity $folder) {
    $storage = $this->entityTypeManager->getStorage('folder_entity');
    $entities = $storage->loadByProperties(['parent' => $folder->id()]);
    uasort($entities, [$this, 'sortByName']);
    return $entities;
  }

  /**
   * Delete a folder tree from the file system.
   *
   * @param string $dir
   *   Uri of the directory.
   *
   * @return array
   *   The children.
   */
  public function delTree($dir) {
    $children = array_diff(scandir($dir), ['.', '..']);
    foreach ($children as $child) {
      (is_dir("$dir/$child")) ? $this->delTree("$dir/$child") : unlink("$dir/$child");
    }
    return rmdir($dir);
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
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function buildUri(FolderEntity $folderEntity, string $uri = '') {
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

  /**
   * Compare function to sort folder entities by name in a natural order.
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
    return strnatcmp($a->get('name')->value, $b->get('name')->value);
  }

}
