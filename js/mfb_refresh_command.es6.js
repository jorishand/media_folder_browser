(($, Drupal) => {
  Drupal.AjaxCommands.prototype.mfbRefresh = (ajax, response) => {
    Drupal.mfbCommon.reload(response.folderId);
  };
})(jQuery, Drupal);
