"use strict";

/**
 * @file
 * Media folder browser related JS code.
 */
(function ($, Drupal) {
  /**
   * Handles the selection of media entities.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for selecting media tiles by clicking them.
   */
  Drupal.behaviors.selectMedia = {
    attach: function attach(context) {
      $(context).find('.js-media-item').on('click', function (e) {
        e.preventDefault();
        var clickedElement = $(e.currentTarget);

        if (clickedElement.hasClass('selected')) {
          clickedElement.removeClass('selected');
        } else {
          clickedElement.addClass('selected');
        } // Update selected count.


        var selectedCount = clickedElement.parent().children('.selected').length;

        if (selectedCount > 0) {
          $('.js-select-actions').removeClass('hidden-scale-y');
          $('.js-standard-actions').addClass('hidden-scale-y');
        } else {
          $('.js-select-actions').addClass('hidden-scale-y');
          $('.js-standard-actions').removeClass('hidden-scale-y');
        }

        $('.js-selected-count').html(selectedCount);
      });
    }
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
    attach: function attach(context) {
      $(context).find('.js-submit-selected').on('click', function (e) {
        e.preventDefault();
        var clickedElement = $(e.currentTarget);
        var widgetId = clickedElement.closest('.folder-browser-widget').attr('data-widget-id');
        $('.js-loader').removeClass('hidden');
        var selected = '';
        $('.js-overview').children('.selected').each(function (index, elem) {
          if (selected !== '') selected += ',';
          selected += $(elem).attr('data-id');
        });
        $("[data-folder-browser-widget-value=".concat(widgetId, "]")).val(selected);
        $("[data-folder-browser-widget-update=".concat(widgetId, "]")).trigger('mousedown'); // Close dialog.

        var $dialog = $('#drupal-modal');

        if ($dialog.length) {
          Drupal.dialog($dialog.get(0)).close();
          $dialog.remove();
        }
      });
    }
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
    attach: function attach(context) {
      $(context).find('.js-submit-add-media').on('click', function (e) {
        e.preventDefault();
        Drupal.mfbCommon.addMedia();
      });
    }
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
    attach: function attach(context) {
      $(context).find('.js-submit-add-folder').on('click', function (e) {
        e.preventDefault();
        Drupal.mfbCommon.addFolder();
      });
    }
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
    attach: function attach(context) {
      var searchMedia = function searchMedia() {
        var $input = $('.js-search-text');
        var query = $input.val();
        $('.js-current-folder').html(Drupal.t('Search results for "%search"', {
          '%search': query
        }));
        $input.blur();
        $('.js-loader').removeClass('hidden');
        var endpoint = Drupal.url("media-folder-browser/search/".concat(query));
        Drupal.ajax({
          url: endpoint
        }).execute();
      }; // Trigger search when pressing enter.


      $(context).find('input.js-search-text').on('keypress', function (e) {
        var enterCode = e.charCode || e.keyCode;

        if (enterCode === 13) {
          searchMedia();
        }
      }); // Trigger search when clicking the button.

      $(context).find('div.js-search-button').on('click', function (e) {
        e.preventDefault();
        searchMedia();
      });
    }
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
    attach: function attach(context) {
      $(context).find('.js-mfb-pager-item').on('click', function (e) {
        e.preventDefault();
        var id = $('.js-current-folder').attr('data-folder-id'); // If the id is not defined, replace it with an  empty string.

        if (typeof id === 'undefined' || id === null || id === 'root') {
          id = '';
        }

        var page = $(e.currentTarget).attr('data-page');

        if (typeof page === 'undefined') {
          page = null;
        }

        Drupal.mfbCommon.reload(id, false, page);
      });
    }
  };
})(jQuery, Drupal);