<?php

namespace Drupal\media_folder_browser;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Folder entity entity.
 *
 * @see \Drupal\media_folder_browser\Entity\FolderEntity.
 */
class FolderEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // ToDo: split up permissions.
    /** @var \Drupal\media_folder_browser\Entity\FolderEntityInterface $entity */
    switch ($operation) {
      case 'view':
      case 'update':
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer media folder browser');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'administer media folder browser');
  }

}
