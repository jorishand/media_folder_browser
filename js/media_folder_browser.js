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
        $('.loader-container').removeClass('hidden');
        var selected = '';
        $('.overview-container').children('.selected').each(function (index, elem) {
          if (selected !== '') selected += ',';
          selected += $(elem).attr('data-id');
        });
        $("[data-folder-browser-widget-value=" + widgetId + "]").val(selected);
        $("[data-folder-browser-widget-update=" + widgetId + "]").trigger('mousedown');
        $('#drupal-modal').dialog('close');
      });
    }
  };
})(jQuery, Drupal);