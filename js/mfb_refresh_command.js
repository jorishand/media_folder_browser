"use strict";

(function ($, Drupal) {
  Drupal.AjaxCommands.prototype.mfbRefresh = function (ajax, response) {
    Drupal.mfbCommon.reload(response.folderId);
  };
})(jQuery, Drupal);