<?php

namespace Drupal\media_folder_browser\Controller;

use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Renderer;
use Drupal\image\Entity\ImageStyle;
use Drupal\media_folder_browser\Ajax\RefreshMFBCommand;
use Drupal\media_folder_browser\Entity\FolderEntity;
use Drupal\media_folder_browser\FolderStructureService;
use Drupal\media_folder_browser\MediaHelperService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\media_folder_browser\Form\MediaFolderUploadForm;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Form builder interface.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Folder entity storage interface.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $folderStorage;

  /**
   * Media entity storage interface.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mediaStorage;

  /**
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

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
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The renderer.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FileSystem $file_system, FolderStructureService $folder_structure_service, MediaHelperService $media_helper, Renderer $renderer, FormBuilderInterface $formBuilder, RequestStack $request) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->folderStructure = $folder_structure_service;
    $this->mediaHelper = $media_helper;
    $this->renderer = $renderer;
    $this->formBuilder = $formBuilder;
    $this->folderStorage = $this->entityTypeManager->getStorage('folder_entity');
    $this->mediaStorage = $this->entityTypeManager->getStorage('media');
    $this->requestStack = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('media_folder_browser.folder_structure'),
      $container->get('media_folder_browser.media_helper'),
      $container->get('renderer'),
      $container->get('form_builder'),
      $container->get('request_stack')
    );
  }

  /**
   * Renders the browser.
   *
   * @return array
   *   A render array.
   */
  public function renderBrowser() {
    $children = $this->getFolderContents();
    $results = $this->renderOverview($children['folders'], $children['media']);

    $element = [
      '#theme' => 'folder_browser_overview',
      '#sidebar_folders' => $this->getFolderTree(),
      '#results' => $results,
      '#attached' => ['library' => ['media_folder_browser/browser']],
    ];
    return $element;
  }

  /**
   * Callback to refresh the overview results.
   *
   * @param int|null $folder_id
   *   ID of the folder or null for root.
   * @param int $page
   *   Curent page of results.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function refreshResults($folder_id = NULL) {
    $response = new AjaxResponse();

    $children = $this->getFolderContents($folder_id);

    $page = $this->requestStack->getCurrentRequest()->query->get('page');

    $results = $this->renderOverview($children['folders'], $children['media'], $page);
    $results = $this->renderer->render($results);

    $response->addCommand(new ReplaceCommand('.js-results-wrapper', $results));
    $response->addCommand(new InvokeCommand('.loader-container', 'addClass', ['hidden']));
    $response->addCommand(new InvokeCommand('.js-select-actions', 'addClass', ['hidden-scale-y']));
    $response->addCommand(new InvokeCommand('.js-standard-actions', 'removeClass', ['hidden-scale-y']));

    return $response;
  }

  /**
   * Callback to replace overview results with search results.
   *
   * @param string|null $search_text
   *   Text to match.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function getSearchResults($search_text) {
    $response = new AjaxResponse();

    $media = $this->mediaHelper->getSearchMedia($search_text);
    $results = $this->renderOverview([], $media);

    $results = $this->renderer->render($results);

    $response->addCommand(new ReplaceCommand('.js-results-wrapper', $results));
    $response->addCommand(new InvokeCommand('.loader-container', 'addClass', ['hidden']));
    $response->addCommand(new InvokeCommand('.js-select-actions', 'addClass', ['hidden-scale-y']));
    $response->addCommand(new InvokeCommand('.js-standard-actions', 'removeClass', ['hidden-scale-y']));
    $response->addCommand(new HtmlCommand('.js-current-folder', t('Search results for "%search"', ['%search' => $search_text])));
    $response->addCommand(new InvokeCommand('.selected', 'removeClass', ['selected']));

    return $response;
  }

  /**
   * Callback to refresh the sidebar folder tree.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function refreshSidebar() {
    $response = new AjaxResponse();

    $results = $this->getFolderTree();
    $results = $this->renderer->render($results);

    $response->addCommand(new ReplaceCommand('.js-sidebar', $results));

    return $response;
  }

  /**
   * Gets child folders and media for a specific folder as a renderable array.
   *
   * @param int|null $folder_id
   *   ID of the folder or null for root.
   *
   * @return array
   *   Array containing child folders and media.
   */
  public function getFolderContents(int $folder_id = NULL) {
    $folder_entity = NULL;

    /** @var \Drupal\media_folder_browser\Entity\FolderEntity $folder_entity */
    if ($folder_id) {
      $folder_entity = $this->folderStorage->load($folder_id);
    }
    if ($folder_entity) {
      $folders = $this->folderStructure->getFolderChildren($folder_entity);
      $media = $this->mediaHelper->getFolderMediaChildren($folder_entity);
    }
    else {
      $folders = $this->folderStructure->getRootFolders();
      $media = $this->mediaHelper->getRootMedia();
    }

    return [
      'folders' => $folders,
      'media' => $media,
    ];
  }

  /**
   * Turns folders and media entities into a render array for the overview.
   *
   * @param array $folders
   *   Array of folder entities.
   * @param array $media
   *   Array of media entities.
   * @param int $page
   *   The current page.
   *
   * @return array
   *   A render array.
   */
  public function renderOverview(array $folders = [], array $media = [], $page = 1) {
    $results = [];

    if (!$page) {
      $page = 1;
    }

    // Add child folders to results.
    /** @var \Drupal\media_folder_browser\Entity\FolderEntity $folder */
    foreach ($folders as $folder) {
      $results[] = [
        '#theme' => 'folder_browser_folder_item',
        '#id' => $folder->id(),
        '#name' => $folder->getName(),
      ];
    }

    // Add child media entities to results.
    /** @var \Drupal\media\Entity\Media $media_item */
    foreach ($media as $media_item) {
      $thumbnail = ImageStyle::load('mfb_thumbnail')->buildUrl($media_item->get('thumbnail')[0]->entity->uri->value);

      // ToDo: Add support for custom media types.
      // Set icon based on media type.
      switch ($media_item->bundle()) {
        case 'image':
          $icon = '/assets/photo-camera.svg';
          break;

        case 'video':
        case 'remote_video':
          $icon = '/assets/play-button-on-film-strip.svg';
          break;

        case 'audio':
          $icon = '/assets/radio-microphone.svg';
          break;

        default:
          $icon = '/assets/text-and-image-document.svg';
          break;
      }
      $icon = base_path() . drupal_get_path('module', 'media_folder_browser') . $icon;
      $file = $this->mediaHelper->getMediaFile($media_item);

      $size = $file ? $file->getSize() : '0';
      $type = $file ? $file->getMimeType() : 'none';

      $results[] = [
        '#theme' => 'folder_browser_media_item',
        '#id' => $media_item->id(),
        '#icon' => $icon,
        '#name' => $media_item->getName(),
        '#size' => format_size($size),
        '#type' => $type,
        '#thumbnail' => $thumbnail,
      ];
    }

    // Add pagination.
    $per_page = 10;

    pager_default_initialize(count($results), $per_page);

    $chunks = array_chunk($results, $per_page, TRUE);
    $render = [
      '#theme' => 'folder_browser_folder_results',
      '#results' => $chunks[$page - 1],
      '#pager' => [
        '#theme' => 'folder_browser_pager',
        '#pages' => count($chunks),
        '#current_page' => $page,
      ],
    ];

    return $render;
  }

  /**
   * Gets the folder tree as a renderable array.
   *
   * @return array
   *   A render array.
   */
  public function getFolderTree() {
    // Load first level folders.
    $root_folders = $this->folderStructure->getRootFolders();

    $children = [];
    foreach ($root_folders as $folder) {
      $children[] = $this->getFolderTreeItem($folder);
    }

    return [
      '#theme' => 'folder_browser_folder_tree',
      '#children' => $children,
    ];
  }

  /**
   * Recursive function to prepare a folder tree render array.
   *
   * @param \Drupal\media_folder_browser\Entity\FolderEntity $folder
   *   Folder entity.
   *
   * @return array
   *   A render array.
   */
  public function getFolderTreeItem(FolderEntity $folder) {
    $child_folders = $this->folderStructure->getFolderChildren($folder);

    $children = [];
    foreach ($child_folders as $child_folder) {
      $children[] = $this->getFolderTreeItem($child_folder);
    }

    return [
      '#theme' => 'folder_browser_folder_tree_item',
      '#name' => $folder->getName(),
      '#id' => $folder->id(),
      '#children' => $children,
    ];
  }

  /**
   * Creates a new folder entity.
   *
   * @param int|null $parent_id
   *   ID of the parent folder.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An Ajax response.
   */
  public function addFolder(int $parent_id = NULL) {
    /** @var \Drupal\media_folder_browser\Entity\FolderEntity $parent_entity */
    $parent_entity = $this->folderStorage->load($parent_id);

    if ($parent_entity) {
      $folders = $this->folderStructure->getFolderChildren($parent_entity);
    }
    else {
      $folders = $this->folderStructure->getRootFolders();
    }

    // Get all sibling folder names as an array.
    $folder_names = array_map(function ($folder) {
      /** @var \Drupal\media_folder_browser\Entity\FolderEntity $folder */
      return $folder->get('name')->value;
    }, $folders);

    // Create a unique folder name.
    $name = 'New folder';
    $i = 1;
    while (in_array($name, $folder_names)) {
      $name = 'New folder ' . $i;
      $i++;
    }

    /** @var \Drupal\media_folder_browser\Entity\FolderEntity $folder_entity */
    $folder_entity = $this->folderStorage->create([
      'name' => $name,
      'parent' => $parent_id,
    ]);
    $this->folderStorage->save($folder_entity);

    $uri = $this->folderStructure->buildUri($folder_entity);
    $this->fileSystem->mkdir($uri, NULL, TRUE);

    $response = new AjaxResponse();
    return $response->addCommand(new RefreshMFBCommand($parent_id, TRUE));
  }

  /**
   * Callback to move a media entity to a different folder.
   *
   * @param int $media_id
   *   ID of the media entity.
   * @param int|null $folder_id
   *   ID of the folder or null for root.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   A redirect response.
   */
  public function moveMedia(int $media_id, $folder_id) {
    $response = new AjaxResponse();

    /** @var \Drupal\media\Entity\Media $media */
    if ($media = $this->mediaStorage->load($media_id)) {
      // Store current folder so it can be used in the refresh command.
      $current_folder = $media->get('field_parent_folder')->referencedEntities();
      if (empty($current_folder)) {
        $current_folder_id = NULL;
      }
      else {
        $current_folder_id = $current_folder[0]->id();
      }

      if ($this->moveMediaEntity($media_id, $folder_id)) {
        return $response->addCommand(new RefreshMFBCommand($current_folder_id));
      }
    }
    // ToDo: error responses and watchdog warnings.
    return $response
      ->addCommand(new InvokeCommand('.loader-container', 'addClass', ['hidden']));
  }

  /**
   * Moves a media entity to a different folder.
   *
   * @param int $media_id
   *   ID of the media entity.
   * @param int|null $folder_id
   *   ID of the folder or null for root.
   *
   * @return bool
   *   Wether or not the operation was successful.
   */
  private function moveMediaEntity(int $media_id, $folder_id) {
    /** @var \Drupal\media\Entity\Media $media */
    if ($media = $this->mediaStorage->load($media_id)) {
      $file = $this->mediaHelper->getMediaFile($media);
      $file_success = TRUE;

      if ($file) {
        // Set destination to root if the folder ID is NULL.
        $dest = 'public://';
        if ($folder_id !== NULL) {
          /** @var \Drupal\media_folder_browser\Entity\FolderEntity $folder */
          if ($folder = $this->folderStorage->load($folder_id)) {
            $dest = $this->folderStructure->buildUri($folder);
            if (!is_dir($dest)) {
              $this->fileSystem->mkdir($dest, NULL, TRUE);
            }
          }
        }

        // Move the file entity to the new folder.
        $file_success = file_move($file, $dest);
      }

      if ($file_success) {
        if ($folder_id !== NULL) {
          $media->set('field_parent_folder', $folder_id);
        }
        else {
          $media->set('field_parent_folder', NULL);
        }
        $media->save();
        return TRUE;
      }
    }
    // ToDo: error responses and watchdog warnings.
    return FALSE;
  }

  /**
   * Callback to move a media entity to the parenting folder.
   *
   * @param int $media_id
   *   ID of the media entity.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An Ajax response.
   */
  public function moveMediaParent(int $media_id) {
    // Load media entity.
    /** @var \Drupal\media\Entity\Media $media */
    if ($media = $this->mediaStorage->load($media_id)) {
      // Get parent folder entity.
      /** @var \Drupal\media_folder_browser\Entity\FolderEntity $folder */
      $folder_ref = $media->get('field_parent_folder')->referencedEntities();
      if (!empty($folder_ref)) {
        $folder = $folder_ref[0];
        // Get folder entity parent ID.
        $parent_folder_ref = $folder->get('parent')->referencedEntities();
        $parent_folder_id = NULL;
        if (!empty($parent_folder_ref)) {
          $parent_folder_id = $parent_folder_ref[0]->id();
        }
        return $this->moveMedia($media_id, $parent_folder_id);
      }
    }
    // ToDo: error responses and watchdog warnings.
    return (new AjaxResponse())
      ->addCommand(new InvokeCommand('.loader-container', 'addClass', ['hidden']));
  }

  /**
   * Callback to move a folder entity to a different folder.
   *
   * @param int $folder_id
   *   ID of the folder entity.
   * @param int|null $dest_folder_id
   *   ID of the folder or null for root.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   A redirect response.
   */
  public function moveFolder(int $folder_id, $dest_folder_id) {
    $response = new AjaxResponse();

    /** @var \Drupal\media_folder_browser\Entity\FolderEntity $folder */
    if ($folder = $this->folderStorage->load($folder_id)) {
      // Store current folder so it can be used in the refresh command.
      $current_folder = $folder->get('parent')->referencedEntities();
      if (empty($current_folder)) {
        $current_folder_id = NULL;
      }
      else {
        $current_folder_id = $current_folder[0]->id();
      }

      // Save the old URI so that the empty folder can be removed afterwards.
      $oldUri = $this->folderStructure->buildUri($folder);

      // Change the parent of the folder.
      $folder->set('parent', $dest_folder_id);
      $folder->save();

      // Update all children recursively.
      if ($this->updateFolderChildren($folder_id)) {
        // Remove the empty dir when all children have been moved.
        $this->folderStructure->delTree($oldUri);
      }

      return $response->addCommand(new RefreshMFBCommand($current_folder_id, TRUE));
    }
    // ToDo: error responses and watchdog warnings.
    return $response
      ->addCommand(new InvokeCommand('.loader-container', 'addClass', ['hidden']));
  }

  /**
   * Callback to move a folder entity to the parenting folder.
   *
   * @param int $folder_id
   *   ID of the folder entity.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An Ajax response.
   */
  public function moveFolderParent(int $folder_id) {
    // Load folder entity.
    /** @var \Drupal\media_folder_browser\Entity\FolderEntity $folder */
    if ($folder = $this->folderStorage->load($folder_id)) {
      // Get parent folder entity.
      /** @var \Drupal\media_folder_browser\Entity\FolderEntity $folder */
      $parent_folder_ref_1 = $folder->get('parent')->referencedEntities();
      if (!empty($parent_folder_ref_1)) {
        $parent_folder_1 = $parent_folder_ref_1[0];
        // Get folder entity parent ID.
        $parent_folder_ref = $parent_folder_1->get('parent')->referencedEntities();
        $parent_folder_id = NULL;
        if (!empty($parent_folder_ref)) {
          $parent_folder_id = $parent_folder_ref[0]->id();
        }
        return $this->moveFolder($folder_id, $parent_folder_id);
      }
    }
    // ToDo: error responses and watchdog warnings.
    return (new AjaxResponse())
      ->addCommand(new InvokeCommand('.loader-container', 'addClass', ['hidden']));
  }

  /**
   * Recursively update all media entities under a folder entity.
   *
   * @param int $folder_id
   *   ID of the folder entity.
   *
   * @return bool
   *   Wether or not the operation was successful.
   */
  private function updateFolderChildren(int $folder_id) {
    /** @var \Drupal\media_folder_browser\Entity\FolderEntity $folder */
    if ($folder = $this->folderStorage->load($folder_id)) {
      $children = $this->folderStructure->getFolderChildren($folder);

      // Recurse.
      /** @var \Drupal\media_folder_browser\Entity\FolderEntity $childFolder */
      foreach ($children as $childFolder) {
        $this->updateFolderChildren($childFolder->id());
      }

      // Moving the child media entities to the same folder updates the
      // file system.
      $media_entities = $this->mediaHelper->getFolderMediaChildren($folder);
      /** @var \Drupal\media\Entity\Media $media */
      foreach ($media_entities as $media) {
        $this->moveMediaEntity($media->id(), $folder_id);
      }
    }
    return TRUE;
  }

  /**
   * Callback to delete a folder entity.
   *
   * @param int $folder_id
   *   ID of the folder.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An Ajax response.
   */
  public function removeFolder(int $folder_id = NULL) {
    $entity = $this->folderStorage->load($folder_id);
    $response = new AjaxResponse();

    if ($entity) {
      $parent_folder_id = $entity->get('parent')->target_id;
      $oldUri = $this->folderStructure->buildUri($entity);
      $this->recursiveDelete($folder_id);
      $this->folderStructure->delTree($oldUri);
      return $response
        ->addCommand(new RefreshMFBCommand($parent_folder_id, TRUE));
    }

    return $response
      ->addCommand(new InvokeCommand('.loader-container', 'addClass', ['hidden']));
  }

  /**
   * Callback to rename a folder entity.
   *
   * @param int $folder_id
   *   ID of the folder.
   * @param string $input
   *   ID of the folder.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An Ajax response.
   */
  public function renameFolder(int $folder_id, string $input) {
    /** @var \Drupal\media_folder_browser\Entity\FolderEntity $folder */
    $folder = $this->folderStorage->load($folder_id);
    $response = new AjaxResponse();

    $input = trim($input);

    if ($folder) {
      // Make sure the input differs from the old name before proceeding.
      if (trim($folder->getName()) !== $input) {
        $oldUri = $this->folderStructure->buildUri($folder);

        $folder->setName($input);
        $folder->save();

        // Update all children recursively.
        if ($this->updateFolderChildren($folder_id)) {
          // Remove the old dir when all children have been moved.
          $this->folderStructure->delTree($oldUri);
        }

        $parent_folder_id = $folder->get('parent')->target_id;
        return $response
          ->addCommand(new RefreshMFBCommand($parent_folder_id, TRUE));
      }
    }

    return $response
      ->addCommand(new InvokeCommand('.loader-container', 'addClass', ['hidden']));
  }

  /**
   * Remove a folder entity and its children recursively.
   *
   * @param int $folder_id
   *   ID of the folder.
   */
  private function recursiveDelete(int $folder_id = NULL) {
    /** @var \Drupal\media_folder_browser\Entity\FolderEntity $folder_entity */
    $folder_entity = $this->folderStorage->load($folder_id);

    $children = $this->folderStructure->getFolderChildren($folder_entity);

    // Remove child folder entities.
    /** @var \Drupal\media_folder_browser\Entity\FolderEntity $childFolder */
    foreach ($children as $childFolder) {
      $this->recursiveDelete($childFolder->id());
    }

    // Remove child media entities.
    $media_entities = $this->mediaHelper->getFolderMediaChildren($folder_entity);
    /** @var \Drupal\media\Entity\Media $media */
    foreach ($media_entities as $media) {
      $media->delete();
    }

    // Remove directory from file system.
    $this->folderStorage->delete([$folder_entity]);
    $uri = $this->folderStructure->buildUri($folder_entity);
    $this->fileSystem->rmdir($uri);
  }

  /**
   * Callback to delete a media entity.
   *
   * @param int $media_id
   *   ID of the media entity.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response.
   */
  public function removeMedia(int $media_id) {
    $entity = $this->mediaStorage->load($media_id);
    $response = new AjaxResponse();

    if ($entity) {
      $folder_id = $entity->get('field_parent_folder')->target_id;
      $entity->delete();
      return $response
        ->addCommand(new RefreshMFBCommand($folder_id));
    }

    return $response
      ->addCommand(new InvokeCommand('.loader-container', 'addClass', ['hidden']));
  }

  /**
   * Callback to open the add media form.
   *
   * @param int|null $folder_id
   *   ID of the folder or null for root.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function openForm(int $folder_id = NULL) {
    $response = new AjaxResponse();

    $form = $this->formBuilder->getForm(MediaFolderUploadForm::class, $folder_id);

    $replace = new HtmlCommand('.js-upload-wrapper', $form);
    $show_upload = new InvokeCommand('.js-upload-wrapper', 'removeClass', ['hidden']);

    $response->addCommand($replace);
    $response->addCommand($show_upload);

    return $response;
  }

}
