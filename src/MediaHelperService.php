<?php

namespace Drupal\media_folder_browser;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\media\Entity\Media;

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
   * Gets media entities that have no parent.
   *
   * @return array
   *   The media entities.
   */
  public function getRootMedia() {
    $storage = $this->entityTypeManager->getStorage('media');

    $nids = \Drupal::entityQuery('media')
      ->notExists('field_parent_folder')
      ->execute();

    return $storage->loadMultiple($nids);
  }

}
