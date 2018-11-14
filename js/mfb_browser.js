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
      $('.js-media-item', context).click(function (e) {
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
      $('.js-submit-selected', context).click(function (e) {
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
      $('.js-submit-add-media', context).click(function (e) {
        e.preventDefault();
        var id = $('.js-current-folder').attr('data-folder-id'); // If the id is not defined, replace it with an  empty string.

        if (typeof id === 'undefined' || id === null || id === 'root') {
          id = '';
        }

        var endpoint = Drupal.url("media-folder-browser/media/add/".concat(id));
        Drupal.ajax({
          url: endpoint
        }).execute();
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
      $('.js-submit-add-folder', context).click(function (e) {
        e.preventDefault();
        $('.js-loader').removeClass('hidden');
        var id = $('.js-current-folder').attr('data-folder-id'); // If the id is not defined, replace it with an  empty string.

        if (typeof id === 'undefined' || id === null || id === 'root') {
          id = '';
        }

        var endpoint = Drupal.url("media-folder-browser/folder/add/".concat(id));
        Drupal.ajax({
          url: endpoint
        }).execute();
      });
    }
  };
})(jQuery, Drupal);