/**
 * @file
 * Media folder browser functions that can be reused.
 */

(($, Drupal) => {
  Drupal.mfbCommon = {
    /**
     * Reloads the contents of the browser overview.
     *
     * @param {number} id
     *   ID of the folder for which the contents should be displayed in the
     *   overview.
     */
    reload: (id) => {
      $.get('/media-folder-browser/overview/refresh', { id }, (data) => {
        // Mock a drupal.ajax response to correctly parse data.
        const ajaxObject = Drupal.ajax({
          url: '',
          base: false,
          element: false,
          progress: false,
        });

        ajaxObject.success(data, 'success');
      });
    },
  };
})(jQuery, Drupal);
