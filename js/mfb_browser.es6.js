/**
 * @file
 * Media folder browser related JS code.
 */

(($, Drupal) => {
  /**
   * Handles the selection of media entities.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for selecting media tiles by clicking them.
   */
  Drupal.behaviors.selectMedia = {
    attach(context) {
      $('.js-media-item', context).click((e) => {
        e.preventDefault();
        const clickedElement = $(e.currentTarget);

        if (clickedElement.hasClass('selected')) {
          clickedElement.removeClass('selected');
        }
        else {
          clickedElement.addClass('selected');
        }

        // Update selected count.
        const selectedCount = clickedElement.parent().children('.selected').length;
        if (selectedCount > 0) {
          $('.js-select-actions').removeClass('hidden-scale-y');
          $('.js-standard-actions').addClass('hidden-scale-y');
        }
        else {
          $('.js-select-actions').addClass('hidden-scale-y');
          $('.js-standard-actions').removeClass('hidden-scale-y');
        }
        $('.js-selected-count').html(selectedCount);
      });
    },
  };

  /**
   * Handles the submission of selected media entities.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for submitting media.
   */
  Drupal.behaviors.submitSelection = {
    attach(context) {
      $('.js-submit-selected', context).click((e) => {
        e.preventDefault();
        const clickedElement = $(e.currentTarget);
        const widgetId = clickedElement.closest('.folder-browser-widget').attr('data-widget-id');

        $('.js-loader').removeClass('hidden');

        let selected = '';

        $('.js-overview').children('.selected').each((index, elem) => {
          if (selected !== '') selected += ',';
          selected += $(elem).attr('data-id');
        });

        $(`[data-folder-browser-widget-value=${widgetId}]`).val(selected);
        $(`[data-folder-browser-widget-update=${widgetId}]`).trigger('mousedown');

        // Close dialog.
        const $dialog = $('#drupal-modal');
        if ($dialog.length) {
          Drupal.dialog($dialog.get(0)).close();
          $dialog.remove();
        }
      });
    },
  };

  /**
   * Handles the opening of the upload form when 'add media' is clicked.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for loading the upload form.
   */
  Drupal.behaviors.addMedia = {
    attach(context) {
      $('.js-submit-add-media', context).click((e) => {
        e.preventDefault();
        Drupal.mfbCommon.addMedia();
      });
    },
  };

  /**
   * Handles the addition of a new folder entity after 'add folder' is clicked.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for adding new folders.
   */
  Drupal.behaviors.addFolder = {
    attach(context) {
      $('.js-submit-add-folder', context).click((e) => {
        e.preventDefault();
        Drupal.mfbCommon.addFolder();
      });
    },
  };

  /**
   * Handles searching.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for adding new folders.
   */
  Drupal.behaviors.searchSubmit = {
    attach(context) {
      $('.js-search-button', context).click((e) => {
        e.preventDefault();
        const searchText = $('.js-search-text').val();
        const endpoint = Drupal.url(`media-folder-browser/search/${searchText}`);
        Drupal.ajax({ url: endpoint }).execute();
      });
    },
  };

  /**
   * Handles pagination.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for adding new folders.
   */
  Drupal.behaviors.pagination = {
    attach(context) {
      $('.js-mfb-pager-item', context).click((e) => {
        e.preventDefault();
        let id = $('.js-current-folder').attr('data-folder-id'); // If the id is not defined, replace it with an  empty string.
        if (typeof id === 'undefined' || id === null || id === 'root') {
          id = '';
        }
        let page = $(e.currentTarget).attr('data-page');
        if (typeof page === 'undefined') {
          page = null;
        }
        Drupal.mfbCommon.reload(id, false, page);
      });
    },
  };
})(jQuery, Drupal);
