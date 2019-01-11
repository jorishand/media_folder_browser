"use strict";

(function ($, Drupal) {
  Drupal.AjaxCommands.prototype.mfbRefresh = function (ajax, response) {
    Drupal.mfbCommon.reload(response.folderId, response.reloadSidebar, response.focusItem);
  };
})(jQuery, Drupal);