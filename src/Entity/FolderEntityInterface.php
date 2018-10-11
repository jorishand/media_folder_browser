<?php

namespace Drupal\media_folder_browser\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Folder entity entities.
 *
 * @ingroup media_folder_browser
 */
interface FolderEntityInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Folder entity name.
   *
   * @return string
   *   Name of the Folder entity.
   */
  public function getName();

  /**
   * Sets the Folder entity name.
   *
   * @param string $name
   *   The Folder entity name.
   *
   * @return \Drupal\media_folder_browser\Entity\FolderEntityInterface
   *   The called Folder entity entity.
   */
  public function setName($name);

}
