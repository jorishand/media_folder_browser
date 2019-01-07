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
     * @param {boolean} sidebar
     *   Wether or not the sidebar should be refreshed.
     * @param {number|null} page
     *   The current page.
     */
    reload: function reload(id) {
      var sidebar = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
      var page = arguments.length > 2 ? arguments[2] : undefined;
      $('.js-loader').removeClass('hidden'); // If the id is not defined, replace it with an  empty string.

      if (typeof id === 'undefined' || id === null) {
        id = '';
      }

      if (typeof page === 'undefined' || page === null) {
        page = '';
      } // Refresh overview


      var endpoint = Drupal.url("media-folder-browser/overview/refresh".concat(id ? '/' : '').concat(id).concat(page ? '?page=' : '').concat(page));
      Drupal.ajax({
        url: endpoint
      }).execute();

      if (sidebar) {
        // Refresh sidebar
        endpoint = Drupal.url('media-folder-browser/sidebar/refresh');
        Drupal.ajax({
          url: endpoint
        }).execute();
      }
    },

    /**
     * Adds a new folder to the active directory.
     */
    addFolder: function addFolder() {
      $('.js-loader').removeClass('hidden');
      var id = $('.js-current-folder').attr('data-folder-id'); // If the id is not defined, replace it with an  empty string.

      if (typeof id === 'undefined' || id === null || id === 'root') {
        id = '';
      }

      var endpoint = Drupal.url("media-folder-browser/folder/add/".concat(id));
      Drupal.ajax({
        url: endpoint
      }).execute();
    },

    /**
     * Opens the media upload form for the current directory.
     */
    addMedia: function addMedia() {
      $('.js-loader').removeClass('hidden');
      var id = $('.js-current-folder').attr('data-folder-id'); // If the id is not defined, replace it with an  empty string.

      if (typeof id === 'undefined' || id === null || id === 'root') {
        id = '';
      }

      var endpoint = Drupal.url("media-folder-browser/media/add/".concat(id));
      Drupal.ajax({
        url: endpoint
      }).execute();
    },

    /**
     * Moves a folder or media entity to a different folder.
     *
     * @param {number} sourceId
     *   ID of the entity that should be moved.
     * @param {number} destId
     *   ID of the destination folder entity.
     * @param {string} type
     *   Wether the source entity is a "media" or "folder" entity.
     */
    move: function move(sourceId, destId, type) {
      $('.js-loader').removeClass('hidden');
      var endpoint = '';

      if (type === 'media') {
        endpoint = Drupal.url("media-folder-browser/media/move/".concat(sourceId, "/").concat(destId));
      } else {
        endpoint = Drupal.url("media-folder-browser/folder/move/".concat(sourceId, "/").concat(destId));
      }

      Drupal.ajax({
        url: endpoint
      }).execute();
    }
  };
})(jQuery, Drupal);