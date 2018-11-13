<?php

namespace Drupal\media_folder_browser\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class RefreshMFBCommand.
 */
class RefreshMFBCommand implements CommandInterface {

  /**
   * A settings array to be passed to any attached JavaScript behavior.
   *
   * @var int
   */
  protected $folderId;

  /**
   * Constructs a RefreshMFBCommand object.
   *
   * @param int|null $folderId
   *   The ID of the folder to refresh.
   */
  public function __construct($folderId) {
    $this->folderId = $folderId;
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
    ];
  }

}
