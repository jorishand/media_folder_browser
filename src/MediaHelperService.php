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
   * The media storage instance.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mediaStorage;

  /**
   * MediaHelperService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->mediaStorage = $this->entityTypeManager->getStorage('media');
  }

  /**
   * Gets referenced file from a media entity.
   *
   * @param \Drupal\media\Entity\Media $media
   *   The media entity.
   *
   * @return \Drupal\file\Entity\File|bool
   *   Returns the referenced file, if no file was found, return FALSE.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
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
          return $file_ref->getValue();
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
    $entities = $this->mediaStorage->loadByProperties(['field_parent_folder' => $folder->id()]);
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
    $nids = \Drupal::entityQuery('media')
      ->notExists('field_parent_folder')
      ->execute();

    $entities = $this->mediaStorage->loadMultiple($nids);
    uasort($entities, [$this, 'sortByName']);
    return $entities;
  }

  /**
   * Gets media entities whose names contain a given string.
   *
   * @param string $search_text
   *   The  text to search by.
   *
   * @return array
   *   The resulting media entities.
   */
  public function getSearchMedia(string $search_text) {
    $nids = \Drupal::entityQuery('media')
      ->condition('name', '%' . $search_text . '%', 'like')
      ->execute();
    $entities = $this->mediaStorage->loadMultiple($nids);
    uasort($entities, [$this, 'sortByName']);
    return $entities;
  }

  /**
   * Compare function to sort media entities by name in a natural order.
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
    return strnatcmp($a->get('name')->value, $b->get('name')->value);
  }

}
