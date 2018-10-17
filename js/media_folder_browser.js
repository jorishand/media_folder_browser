(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.folderBrowser = {
    attach: function (context) {
      $('.js-tree-item', context).click(function (e) {
        e.preventDefault();
        $(this).addClass('selected');
        refreshResults($(this).attr("data-id"));
      });

      function refreshResults(id) {
        $.get('/media-folder-browser/overview/refresh', {id:id}, function (data) {
          // Simulate a drupal.ajax response to correctly parse data.
          var ajaxObject = Drupal.ajax({
            url: '',
            base: false,
            element: false,
            progress: false
          });

          ajaxObject.success(data, 'success');
        });
      }

    }
  };

})(jQuery, Drupal);
