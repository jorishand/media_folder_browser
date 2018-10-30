/**
 * @file
 * Media folder browser related JS code.
 */

(function ($, Drupal) {
  /**
   * Handles the selection of media items.
   */
  Drupal.behaviors.selectMedia = {
    attach(context) {
      $('.js-media-item', context).click((e) => {
        e.preventDefault();

        if ($(this).hasClass('selected')) {
          $(this).removeClass('selected');
        }
        else {
          $(this).addClass('selected');
        }

        // Update selected count.
        const selectedCount = $(this).parent().children('.selected').length;
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
   * Sends selected media to a hidden field in the widget.
   */
  Drupal.behaviors.submitSelection = {
    attach(context) {
      $('.js-submit-selected', context).click((e) => {
        e.preventDefault();

        $('.loader-container').removeClass('hidden');

        const widgetId = $(this).closest('.folder-browser-widget').attr('data-widget-id');
        let selected = '';

        $('.overview-container').children('.selected').each(() => {
          if (selected !== '') selected += ',';
          selected += $(this).attr('data-id');
        });

        $(`[data-folder-browser-widget-value=${widgetId}]`).val(selected);
        $(`[data-folder-browser-widget-update=${widgetId}]`).trigger('mousedown');
        $('#drupal-modal').dialog('close');
      });
    },
  };
}(jQuery, Drupal));
