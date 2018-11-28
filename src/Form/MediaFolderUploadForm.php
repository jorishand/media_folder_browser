<?php

namespace Drupal\media_folder_browser\Form;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\TypedData\FieldItemDataDefinition;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\file\FileInterface;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\media\MediaTypeInterface;
use Drupal\media_folder_browser\Ajax\RefreshMFBCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Drupal\Core\File\FileSystem;
use Drupal\media_folder_browser\FolderStructureService;

/**
 * Creates a form to create media entities from uploaded files.
 *
 * @Todo: Extend when Media Library gets stable.
 * This file is a slightly altered version of the media library upload form.
 * This form should be extended on the one media library provides once it gets
 * stable.
 *
 * @see \Drupal\media_library\Form\MediaLibraryUploadForm
 */
class MediaFolderUploadForm extends FormBase {

  /**
   * The element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Media types the current user has access to.
   *
   * @var \Drupal\media\MediaTypeInterface[]
   */
  protected $types;

  /**
   * The media being processed.
   *
   * @var \Drupal\media\MediaInterface[]
   */
  protected $media = [];

  /**
   * The files waiting for type selection.
   *
   * @var \Drupal\file\FileInterface[]
   */
  protected $files = [];

  /**
   * Indicates whether the 'medium' image style exists.
   *
   * @var bool
   */
  protected $mediumStyleExists = FALSE;

  /**
   * The id of the active folder.
   *
   * @var int
   */
  protected $folderId = NULL;

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
   * Constructs a new MediaLibraryUploadForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info
   *   The element info manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ElementInfoManagerInterface $element_info, FileSystem $file_system, FolderStructureService $folder_structure_service) {
    $this->entityTypeManager = $entity_type_manager;
    $this->elementInfo = $element_info;
    $this->mediumStyleExists = !empty($entity_type_manager->getStorage('image_style')->load('medium'));
    $this->fileSystem = $file_system;
    $this->folderStructure = $folder_structure_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('element_info'),
      $container->get('file_system'),
      $container->get('media_folder_browser.folder_structure')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mfb_upload_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $folder_id = NULL) {
    $this->folderId = $folder_id;

    $form['#prefix'] = '<div id="media-library-upload-wrapper">';
    $form['#suffix'] = '</div>';

    $form['#attached']['library'][] = 'media_library/style';
    $form['#attributes']['class'][] = 'media-library-upload';

    if (empty($this->media) && empty($this->files)) {
      $process = (array) $this->elementInfo->getInfoProperty('managed_file', '#process', []);
      $upload_validators = $this->mergeUploadValidators($this->getTypes());
      $form['upload_container'] = [
        '#type' => 'fieldset',
      ];
      $form['upload_container']['upload'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Upload'),
        '#process' => array_merge(['::validateUploadElement'], $process, ['::processUploadElement']),
        '#upload_validators' => $upload_validators,
      ];
      $form['upload_container']['upload_help'] = [
        '#theme' => 'file_upload_help',
        '#description' => $this->t('Upload files here to add new media.'),
        '#upload_validators' => $upload_validators,
      ];
      // Todo: when https://www.drupal.org/project/drupal/issues/2793343 gets
      // resolved, put these in an actions element.
      $form['cancel'] = [
        '#type' => 'submit',
        '#value' => $this->t('Cancel'),
        '#name' => 'cancel',
        '#ajax' => [
          'callback' => '::closeForm',
          'wrapper' => 'media-library-upload-wrapper',
        ],
      ];

    }
    else {
      $form['media'] = [
        '#type' => 'container',
      ];
      foreach ($this->media as $i => $media) {
        $source_field = $media->getSource()
          ->getSourceFieldDefinition($media->bundle->entity)
          ->getName();

        $element = [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'media-library-upload__media',
            ],
          ],
          'preview' => [
            '#type' => 'container',
            '#attributes' => [
              'class' => [
                'media-library-upload__media-preview',
              ],
            ],
          ],
          'fields' => [
            '#type' => 'container',
            '#attributes' => [
              'class' => [
                'media-library-upload__media-fields',
              ],
            ],
            // Parents is set here as it is used in the form display.
            '#parents' => ['media', $i, 'fields'],
          ],
        ];
        if ($this->mediumStyleExists && $thumbnail_uri = $media->getSource()->getMetadata($media, 'thumbnail_uri')) {
          $element['preview']['thumbnail'] = [
            '#theme' => 'image_style',
            '#style_name' => 'medium',
            '#uri' => $thumbnail_uri,
          ];
        }
        EntityFormDisplay::collectRenderDisplay($media, 'media_library')
          ->buildForm($media, $element['fields'], $form_state);
        // We hide certain elements in the image widget with CSS.
        if (isset($element['fields'][$source_field])) {
          $element['fields'][$source_field]['#attributes']['class'][] = 'media-library-upload__source-field';
        }
        if (isset($element['fields']['revision_log_message'])) {
          $element['fields']['revision_log_message']['#access'] = FALSE;
        }
        $form['media'][$i] = $element;
      }

      $form['files'] = [
        '#type' => 'container',
      ];
      foreach ($this->files as $i => $file) {
        $types = $this->filterTypesThatAcceptFile($file, $this->getTypes());
        $form['files'][$i] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'media-library-upload__file',
            ],
          ],
          'help' => [
            '#markup' => '<strong class="media-library-upload__file-label">' . $this->t('Select a media type for %filename:', [
              '%filename' => $file->getFilename(),
            ]) . '</strong>',
          ],
        ];
        foreach ($types as $type) {
          $form['files'][$i][$type->id()] = [
            '#type' => 'submit',
            '#media_library_index' => $i,
            '#media_library_type' => $type->id(),
            '#value' => $type->label(),
            '#submit' => ['::selectType'],
            '#ajax' => [
              'callback' => '::updateFormCallback',
              'wrapper' => 'media-library-upload-wrapper',
            ],
            '#limit_validation_errors' => [['files', $i, $type->id()]],
          ];
        }
      }
      // Todo: when https://www.drupal.org/project/drupal/issues/2793343 gets
      // resolved, put these in an actions element.
      $form['cancel'] = [
        '#type' => 'submit',
        '#value' => $this->t('Cancel'),
        '#name' => 'cancel',
        '#ajax' => [
          'callback' => '::closeForm',
          'wrapper' => 'media-library-upload-wrapper',
        ],
      ];

      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
        '#ajax' => [
          'callback' => '::closeForm',
          'wrapper' => 'media-library-upload-wrapper',
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (count($this->files)) {
      $form_state->setError($form['files'], $this->t('Please select a media type for all files.'));
    }
    foreach ($this->media as $i => $media) {
      $form_display = EntityFormDisplay::collectRenderDisplay($media, 'media_library');
      $form_display->extractFormValues($media, $form['media'][$i]['fields'], $form_state);
      $form_display->validateFormValues($media, $form['media'][$i]['fields'], $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->media as $i => $media) {
      EntityFormDisplay::collectRenderDisplay($media, 'media_library')
        ->extractFormValues($media, $form['media'][$i]['fields'], $form_state);
      $source_field = $media->getSource()->getSourceFieldDefinition($media->bundle->entity)->getName();
      /** @var \Drupal\file\FileInterface $file */
      $file = $media->get($source_field)->entity;

      // Move the new file to the correct folder.
      $folder = $media->get('field_parent_folder')->referencedEntities()[0];
      $dest = 'public://';
      if (!empty($folder)) {
        $dest = $this->folderStructure->buildUri($folder);
        if (!is_dir($dest)) {
          $this->fileSystem->mkdir($dest, NULL, TRUE);
        }
      }
      $file->setPermanent();
      $file->save();

      $media->save();
      file_move($file, $dest);
    }
  }

  /**
   * AJAX callback to select a media type for a file.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   If the triggering element is missing required properties.
   */
  public function selectType(array &$form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    if (!isset($element['#media_library_index']) || !isset($element['#media_library_type'])) {
      throw new BadRequestHttpException('The "#media_library_index" and "#media_library_type" properties on the triggering element are required for type selection.');
    }
    $i = $element['#media_library_index'];
    $type = $element['#media_library_type'];
    $this->media[] = $this->createMediaEntity($this->files[$i], $this->getTypes()[$type], $this->folderId);
    unset($this->files[$i]);
    $form_state->setRebuild();
  }

  /**
   * AJAX callback to close the form and refreshing the overview after doign so.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Response containing the commands needed to close the form and refresh the
   *   overview.
   */
  public function closeForm(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $response = new AjaxResponse();
    if ($trigger['#name'] !== 'cancel') {
      $response->addCommand(new RefreshMFBCommand($this->folderId));
    }
    $response->addCommand(new RemoveCommand('#media-library-upload-wrapper'));
    $response->addCommand(new InvokeCommand('.js-upload-wrapper, .js-loader', "addClass", ['hidden']));

    return ($response);
  }

  /**
   * Processes an upload (managed_file) element.
   *
   * @param array $element
   *   The upload element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The processed upload element.
   */
  public function processUploadElement(array $element, FormStateInterface $form_state) {
    $element['upload_button']['#submit'] = ['::uploadButtonSubmit'];
    $element['upload_button']['#ajax'] = [
      'callback' => '::updateFormCallback',
      'wrapper' => 'media-library-upload-wrapper',
    ];
    return $element;
  }

  /**
   * Validates the upload element.
   *
   * @param array $element
   *   The upload element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The processed upload element.
   */
  public function validateUploadElement(array $element, FormStateInterface $form_state) {
    if ($form_state->getErrors()) {
      $element['#value'] = [];
    }
    return $element;
  }

  /**
   * Submit handler for the upload button, inside the managed_file element.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function uploadButtonSubmit(array $form, FormStateInterface $form_state) {
    $fids = $form_state->getValue('upload', []);
    $files = $this->entityTypeManager->getStorage('file')->loadMultiple($fids);
    /** @var \Drupal\file\FileInterface $file */
    foreach ($files as $file) {
      $types = $this->filterTypesThatAcceptFile($file, $this->getTypes());
      if (!empty($types)) {
        if (count($types) === 1) {
          $this->media[] = $this->createMediaEntity($file, reset($types), $this->folderId);
        }
        else {
          $this->files[] = $file;
        }
      }
    }
    $form_state->setRebuild();
  }

  /**
   * Creates a new, unsaved media entity.
   *
   * @param \Drupal\file\FileInterface $file
   *   A file for the media source field.
   * @param \Drupal\media\MediaTypeInterface $type
   *   A media type.
   * @param int|null $folderId
   *   ID of the parent folder.
   *
   * @return \Drupal\media\MediaInterface
   *   An unsaved media entity.
   *
   * @throws \Exception
   *   If a file operation failed when moving the upload.
   */
  protected function createMediaEntity(FileInterface $file, MediaTypeInterface $type, $folderId) {
    /** @var \Drupal\media\MediaInterface $media */
    $media = $this->entityTypeManager->getStorage('media')->create([
      'bundle' => $type->id(),
      'name' => $file->getFilename(),
    ]);
    $source_field = $type->getSource()->getSourceFieldDefinition($type)->getName();
    $location = $this->getUploadLocationForType($media->bundle->entity);
    if (!file_prepare_directory($location, FILE_CREATE_DIRECTORY)) {
      throw new \Exception("The destination directory '$location' is not writable");
    }
    $file = file_move($file, $location);
    if (!$file) {
      throw new \Exception("Unable to move file to '$location'");
    }
    $media->set($source_field, $file->id());
    if ($folderId) {
      $media->set('field_parent_folder', $folderId);
    }
    else {
      $media->set('field_parent_folder', NULL);
    }

    return $media;
  }

  /**
   * AJAX callback for refreshing the entire form.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form render array.
   */
  public function updateFormCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Access callback to check that the user can create file based media.
   *
   * @param array $allowed_types
   *   (optional) The contextually allowed types.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(array $allowed_types = NULL) {
    return AccessResultAllowed::allowedIf(count($this->getTypes($allowed_types)))->mergeCacheMaxAge(0);
  }

  /**
   * Returns media types which use files that the current user can create.
   *
   * @param array $allowed_types
   *   (optional) The contextually allowed types.
   *
   * @return \Drupal\media\MediaTypeInterface[]
   *   A list of media types that are valid for this form.
   */
  protected function getTypes(array $allowed_types = NULL) {
    // Cache results if possible.
    if (!isset($this->types)) {
      $media_type_storage = $this->entityTypeManager->getStorage('media_type');
      if (!$allowed_types) {
        $allowed_types = _media_library_get_allowed_types() ?: NULL;
      }
      $types = $media_type_storage->loadMultiple($allowed_types);
      $types = $this->filterTypesWithFileSource($types);
      $types = $this->filterTypesWithCreateAccess($types);
      $this->types = $types;
    }
    return $this->types;
  }

  /**
   * Filters media types that accept a given file.
   *
   * @param \Drupal\file\FileInterface $file
   *   A file entity.
   * @param \Drupal\media\MediaTypeInterface[] $types
   *   An array of available media types.
   *
   * @return \Drupal\media\MediaTypeInterface[]
   *   An array of media types that accept the file.
   */
  protected function filterTypesThatAcceptFile(FileInterface $file, array $types) {
    $types = $this->filterTypesWithFileSource($types);
    return array_filter($types, function (MediaTypeInterface $type) use ($file) {
      $validators = $this->getUploadValidatorsForType($type);
      $errors = file_validate($file, $validators);
      return empty($errors);
    });
  }

  /**
   * Filters an array of media types that accept file sources.
   *
   * @param \Drupal\media\MediaTypeInterface[] $types
   *   An array of media types.
   *
   * @return \Drupal\media\MediaTypeInterface[]
   *   An array of media types that accept file sources.
   */
  protected function filterTypesWithFileSource(array $types) {
    return array_filter($types, function (MediaTypeInterface $type) {
      return is_a($type->getSource()->getSourceFieldDefinition($type)->getClass(), FileFieldItemList::class, TRUE);
    });
  }

  /**
   * Merges file upload validators for an array of media types.
   *
   * @param \Drupal\media\MediaTypeInterface[] $types
   *   An array of media types.
   *
   * @return array
   *   An array suitable for passing to file_save_upload() or the file field
   *   element's '#upload_validators' property.
   */
  protected function mergeUploadValidators(array $types) {
    $max_size = 0;
    $extensions = [];
    $types = $this->filterTypesWithFileSource($types);
    foreach ($types as $type) {
      $validators = $this->getUploadValidatorsForType($type);
      if (isset($validators['file_validate_size'])) {
        $max_size = max($max_size, $validators['file_validate_size'][0]);
      }
      if (isset($validators['file_validate_extensions'])) {
        $extensions = array_unique(array_merge($extensions, explode(' ', $validators['file_validate_extensions'][0])));
      }
    }
    // If no field defines a max size, default to the system wide setting.
    if ($max_size === 0) {
      $max_size = file_upload_max_size();
    }
    return [
      'file_validate_extensions' => [implode(' ', $extensions)],
      'file_validate_size' => [$max_size],
    ];
  }

  /**
   * Gets upload validators for a given media type.
   *
   * @param \Drupal\media\MediaTypeInterface $type
   *   A media type.
   *
   * @return array
   *   An array suitable for passing to file_save_upload() or the file field
   *   element's '#upload_validators' property.
   */
  protected function getUploadValidatorsForType(MediaTypeInterface $type) {
    return $this->getFileItemForType($type)->getUploadValidators();
  }

  /**
   * Gets upload destination for a given media type.
   *
   * @param \Drupal\media\MediaTypeInterface $type
   *   A media type.
   *
   * @return string
   *   An unsanitized file directory URI with tokens replaced.
   */
  protected function getUploadLocationForType(MediaTypeInterface $type) {
    return $this->getFileItemForType($type)->getUploadLocation();
  }

  /**
   * Creates a file item for a given media type.
   *
   * @param \Drupal\media\MediaTypeInterface $type
   *   A media type.
   *
   * @return \Drupal\file\Plugin\Field\FieldType\FileItem
   *   The file item.
   */
  protected function getFileItemForType(MediaTypeInterface $type) {
    $source = $type->getSource();
    $source_data_definition = FieldItemDataDefinition::create($source->getSourceFieldDefinition($type));
    return new FileItem($source_data_definition);
  }

  /**
   * Filters an array of media types that can be created by the current user.
   *
   * @param \Drupal\media\MediaTypeInterface[] $types
   *   An array of media types.
   *
   * @return \Drupal\media\MediaTypeInterface[]
   *   An array of media types that accept file sources.
   */
  protected function filterTypesWithCreateAccess(array $types) {
    $access_handler = $this->entityTypeManager->getAccessControlHandler('media');
    return array_filter($types, function (MediaTypeInterface $type) use ($access_handler) {
      return $access_handler->createAccess($type->id());
    });
  }

}
