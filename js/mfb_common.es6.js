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
     * @param {number|null} page
     *   The current page.
     */
    reload: (id, sidebar = false, page) => {
      $('.js-loader').removeClass('hidden');
      // If the id is not defined, replace it with an  empty string.
      if (typeof id === 'undefined' || id === null) {
        id = '';
      }
      if (typeof page === 'undefined' || page === null) {
        page = '';
      }

      // Refresh overview
      let endpoint = Drupal.url(`media-folder-browser/overview/refresh${id ? '/' : ''}${id}${page ? '?page=' : ''}${page}`);
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
