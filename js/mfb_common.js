"use strict";

/**
 * @file
 * Media folder browser functions that can be reused.
 */
(function ($, Drupal) {
  Drupal.mfbCommon = {
    /**
     * Reloads the contents of the browser overview.
     *
     * @param {number|null} id
     *   ID of the folder for which the contents should be displayed in the
     *   overview.
     */
    reload: function reload(id) {
      // If the id is not defined, replace it with an  empty string.
      if (typeof id === 'undefined' || id === null) {
        id = '';
      }

      var endpoint = Drupal.url("media-folder-browser/overview/refresh/".concat(id));
      Drupal.ajax({
        url: endpoint
      }).execute();
    }
  };
})(jQuery, Drupal);