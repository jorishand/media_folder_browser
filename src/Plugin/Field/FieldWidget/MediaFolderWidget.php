<?php

namespace Drupal\media_folder_browser\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;

/**
 * Folder browser widget.
 *
 * @FieldWidget(
 *   id = "folder_browser_widget",
 *   label = @Translation("Media folder browser"),
 *   description = @Translation("A folder based media browser."),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE,
 * )
 */
class MediaFolderWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $settings = $this->getFieldSetting('handler_settings');
    $parents = $form['#parents'];
    $id_suffix = '-' . implode('-', $parents);
    $wrapper_id = $field_name . '-folder-browser-wrapper' . $id_suffix;
    $element += [
      '#type' => 'fieldset',
      '#cardinality' => $this->fieldDefinition->getFieldStorageDefinition()->getCardinality(),
      '#target_bundles' => isset($settings['target_bundles']) ? $settings['target_bundles'] : FALSE,
      '#attributes' => [
        'id' => $wrapper_id,
        'class' => ['folder-browser-widget'],
      ],
    ];
    $query = [
      'folder-browser_allowed_types' => $element['#target_bundles'],
    ];
    $dialog_options = Json::encode([
      'dialogClass' => 'folder-browser-widget-modal',
      'height' => '75%',
      'width' => '75%',
      'title' => $this->t('Media folder browser'),
    ]);
    $element['media_browser_add_button'] = [
      '#type' => 'link',
      '#title' => $this->t('Add media'),
      '#name' => 'folder-browser-add-button',
      '#url' => Url::fromRoute('folder_browser.view', [], [
        'query' => $query,
      ]),
      '#attributes' => [
        'class' => ['button', 'use-ajax', 'folder-browser-add-button'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => $dialog_options,
      ],
    ];
    return ['value' => $element];
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getSetting('target_type') === 'media';
  }

}
