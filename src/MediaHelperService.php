<?php

namespace Drupal\media_folder_browser;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\media\Entity\Media;
use Drupal\media_folder_browser\Entity\FolderEntity;

/**
 * Class MediaHelperService.
 */
class MediaHelperService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * MediaHelperService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Gets referenced file from a media entity.
   *
   * @param \Drupal\media\Entity\Media $media
   *   The media entity.
   *
   * @return \Drupal\file\Entity\File|bool
   *   Returns the referenced file, if no file was found, return FALSE.
   */
  public function getMediaFile(Media $media) {
    // ToDo: Maybe replace with config form? Check what the best solution is.
    $file_fields = [
      'image' => 'image',
      'file' => 'file',
      'video' => 'video_file',
      'audio' => 'audio_file',
    ];
    if (array_key_exists($media->bundle(), $file_fields)) {
      if ($media_ref = $media->get('field_media_' . $file_fields[$media->bundle()])->first()) {
        if ($file_ref = $media_ref->get('entity')->getTarget()) {
          return $file_ref->getEntity();
        }
      }
    }
    return FALSE;
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
    $entities = $storage->loadByProperties(['field_parent_folder' => $folder->id()]);
    uasort($entities, [$this, 'sortByName']);
    return $entities;
  }

  /**
   * Gets media entities that have no parent.
   *
   * @return array
   *   The media entities.
   */
  public function getRootMedia() {
    $storage = $this->entityTypeManager->getStorage('media');

    $nids = \Drupal::entityQuery('media')
      ->notExists('field_parent_folder')
      ->sort('name', 'ASC')
      ->execute();

    return $storage->loadMultiple($nids);
  }

  /**
   * Compare function to sort media entities by name.
   *
   * @param \Drupal\media\Entity\Media $a
   *   Folder entity a.
   * @param \Drupal\media\Entity\Media $b
   *   Folder entity b.
   *
   * @return array
   *   The children.
   */
  private function sortByName(Media $a, Media $b) {
    return strcmp($a->get('name')->value, $b->get('name')->value);
  }

}
