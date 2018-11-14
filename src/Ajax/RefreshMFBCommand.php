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
   * Boolean stating wether or not to refresh the sidebar folder structure.
   *
   * @var bool
   */
  protected $reloadSidebar;

  /**
   * Constructs a RefreshMFBCommand object.
   *
   * @param int|null $folderId
   *   The ID of the folder to refresh.
   * @param bool $reloadSidebar
   *   Wether or not to refresh the sidebar.
   */
  public function __construct($folderId, $reloadSidebar = FALSE) {
    $this->folderId = $folderId;
    $this->reloadSidebar = $reloadSidebar;
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
    ];
  }

}
