/**
 * @file
 * Media folder browser functions that can be reused.
 */

(($, Drupal) => {
  Drupal.mfbCommon = {
    /**
     * Reloads the contents of the browser overview.
     *
     * @param {number|null} id
     *   ID of the folder for which the contents should be displayed in the
     *   overview.
     * @param {boolean} sidebar
     *   Wether or not the sidebar should be refreshed.
     */
    reload: (id, sidebar = false) => {
      // If the id is not defined, replace it with an  empty string.
      if (typeof id === 'undefined' || id === null) {
        id = '';
      }

      // Refresh overview
      let endpoint = Drupal.url(`media-folder-browser/overview/refresh/${id}`);
      Drupal.ajax({ url: endpoint }).execute();

      if (sidebar) {
        // Refresh sidebar
        endpoint = Drupal.url('media-folder-browser/sidebar/refresh');
        Drupal.ajax({ url: endpoint }).execute();
      }
    },
    addFolder: () => {
      $('.js-loader').removeClass('hidden');
      let id = $('.js-current-folder').attr('data-folder-id');
      // If the id is not defined, replace it with an  empty string.
      if (typeof id === 'undefined' || id === null || id === 'root') {
        id = '';
      }
      const endpoint = Drupal.url(`media-folder-browser/folder/add/${id}`);
      Drupal.ajax({ url: endpoint }).execute();
    },
    addMedia: () => {
      $('.js-loader').removeClass('hidden');
      let id = $('.js-current-folder').attr('data-folder-id');
      // If the id is not defined, replace it with an  empty string.
      if (typeof id === 'undefined' || id === null || id === 'root') {
        id = '';
      }
      const endpoint = Drupal.url(`media-folder-browser/media/add/${id}`);
      Drupal.ajax({ url: endpoint }).execute();
    },
  };
})(jQuery, Drupal);
