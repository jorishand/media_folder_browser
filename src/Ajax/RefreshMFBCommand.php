<?php

namespace Drupal\media_folder_browser\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class RefreshMFBCommand.
 */
class RefreshMFBCommand implements CommandInterface {

  /**
   * The ID of the folder to refresh.
   *
   * @var int
   */
  protected $folderId;

  /**
   * Boolean stating whether or not to refresh the sidebar folder structure.
   *
   * @var bool
   */
  protected $reloadSidebar;

  /**
   * Item to focus after refreshing.
   *
   * @var array
   */
  protected $focusItem;

  /**
   * Constructs a RefreshMFBCommand object.
   *
   * @param int|null $folderId
   *   The ID of the folder to refresh.
   * @param bool $reloadSidebar
   *   Wether or not to refresh the sidebar.
   * @param array $focusItem
   *   Associative array containing:
   *   - type: type of item to focus ('media', 'folder' or 'page')
   *   - id: id of the item, or page number.
   */
  public function __construct($folderId = NULL, bool $reloadSidebar = FALSE, array $focusItem = []) {
    $this->folderId = $folderId;
    $this->reloadSidebar = $reloadSidebar;
    $this->focusItem = $focusItem;
  }

  /**
   * Custom ajax command for refreshing the media folder browser.
   *
   * @return array
   *   Command function.
   */
  public function render() {
    return [
      'command' => 'mfbRefresh',
      'folderId' => $this->folderId,
      'reloadSidebar' => $this->reloadSidebar,
      'focusItem' => $this->focusItem,
    ];
  }

}
