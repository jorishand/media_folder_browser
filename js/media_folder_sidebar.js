"use strict";

/**
 * @file
 * Media folder browser sidebar related JS code.
 */
(function ($, Drupal) {
  /**
   * Reloads the items in the overview when a folder is selected.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for submitting media.
   */
  Drupal.behaviors.treeFolderReload = {
    attach: function attach(context) {
      function refreshResults(id) {
        $.get('/media-folder-browser/overview/refresh', {
          id: id
        }, function (data) {
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

      $('.js-tree-item', context).click(function (e) {
        e.preventDefault();
        var clickedElement = $(e.currentTarget);
        $('.selected').removeClass('selected');
        clickedElement.addClass('selected');
        $('.js-current-folder').html(clickedElement.children('span').html());
        $('.loader-container').removeClass('hidden');
        refreshResults(clickedElement.attr('data-id'));
      });
    }
  };
  /**
   * Set initial height of all collapsible folders after the first uncollapse.
   */

  function setInitialHeights() {
    $('.sub-dir').once('initial-heights').each(function (index, elem) {
      var $childList = $(elem).children('ul'); // Alter style in a way that a height can be determined.

      $(elem).removeClass('collapsed');
      $childList.css('position', 'absolute');
      $childList.css('max-height', '');
      $childList.attr('data-height', $childList.prop('offsetHeight')); // Reset style.

      $childList.css('position', '');
      $(elem).addClass('collapsed');
    });
  }
  /**
   * Update all ul heights upstream.
   *
   * @param {jQuery} $element
   *   The element to start from.
   * @param {number} offset
   *   The difference in height.
   */


  function alterParentHeight($element, offset) {
    var parentList = $element.parent('ul');
    var dataHeight = parseInt(parentList.attr('data-height'), 10) + parseInt(offset, 10);
    parentList.css('max-height', "".concat(dataHeight, "px"));
    parentList.attr('data-height', dataHeight);
    var parentListParent = parentList.parent('.sub-dir');

    if (parentListParent.length) {
      alterParentHeight(parentListParent, offset);
    }
  }
  /**
   * Handles the collapsing of submenus in the folder tree structure.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for collapsing submenus.
   */


  Drupal.behaviors.sidebarCollapse = {
    attach: function attach(context) {
      $('.js-dropdown', context).click(function (e) {
        e.preventDefault();
        setInitialHeights();
        var clickedElement = $(e.currentTarget);
        var $parent = clickedElement.parent().parent('.sub-dir');
        var heightOffset = 0;

        if ($parent.hasClass('collapsed')) {
          $parent.removeClass('collapsed');
          $parent.children('ul').each(function (index, elem) {
            var dataHeight = $(elem).attr('data-height');
            $(elem).css('max-height', "".concat(dataHeight, "px"));
            heightOffset = dataHeight;
          });
        } else {
          $parent.addClass('collapsed');
          $parent.children('ul').each(function (index, elem) {
            $(elem).css('max-height', 0);
            heightOffset = -$(elem).attr('data-height');
          });
        }

        alterParentHeight($parent, heightOffset);
      });
    }
  };
})(jQuery, Drupal);