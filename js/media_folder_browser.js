"use strict";

/**
 * @file
 * Media folder browser related JS code.
 */
(function ($, Drupal) {
  'use strict';
  /**
   * Handles the selection of media items.
   */

  Drupal.behaviors.selectMedia = {
    attach: function attach(context) {
      var _this = this;

      $('.js-media-item', context).click(function (e) {
        e.preventDefault();

        if ($(_this).hasClass('selected')) {
          $(_this).removeClass('selected');
        } else {
          $(_this).addClass('selected');
        } // Update selected count.


        var selectedCount = $(_this).parent().children('.selected').length;

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
   * Sends selected media to a hidden field in the widget.
   */

  Drupal.behaviors.submitSelection = {
    attach: function attach(context) {
      var _this2 = this;

      $('.js-submit-selected', context).click(function (e) {
        e.preventDefault();
        $('.loader-container').removeClass('hidden');
        var widget_id = $(_this2).closest('.folder-browser-widget').attr('data-widget-id');
        var selected = '';
        $('.overview-container').children('.selected').each(function () {
          if (selected !== '') selected += ',';
          selected += $(_this2).attr('data-id');
        });
        $('[data-folder-browser-widget-value=' + widget_id + ']').val(selected);
        $('[data-folder-browser-widget-update=' + widget_id + ']').trigger('mousedown');
        $('#drupal-modal').dialog('close');
      });
    }
  };
})(jQuery, Drupal);