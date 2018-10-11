<?php

namespace Drupal\media_folder_browser\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

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
   * Constructs a new MediaFolderController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Creates a new folder entity.
   *
   * @param string $name
   *   Name of the folder.
   * @param int $parent_id
   *   ID of the parent folder.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response.
   */
  public function addFolder(string $name, int $parent_id = NULL) {
    // Add folder entity to storage.
    $storage = $this->entityTypeManager->getStorage('folder_entity');
    $folder_entity = $storage->create([
      'name' => $name,
      'parent' => $parent_id,
    ]);
    $storage->save($folder_entity);

    // ToDo: Create folder in file system.
    return $this->redirect('<front>');
  }

}
