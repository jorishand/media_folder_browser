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
      $('.js-tree-item, .js-folder-item', context).click(function (e) {
        e.preventDefault();
        var clickedElement = $(e.currentTarget);
        var dataId = clickedElement.attr('data-id'); // Set 'selected' class on the clicked folder.

        $('.selected').removeClass('selected');
        $("[data-id=".concat(dataId, "]")).addClass('selected'); // Set 'current folder' label and ID.

        var $currentFolder = $('.js-current-folder');
        $currentFolder.html(clickedElement.children('span').html());
        $currentFolder.attr('data-folder-id', dataId); // Display the loader.

        $('.loader-container').removeClass('hidden'); // Refresh the overview.

        Drupal.mfbCommon.reload(dataId);
      });
    }
  };
  /**
   * Set initial height of all collapsible folders after the first uncollapse.
   *
   * We set a data attribute for each collapsible list with its offset height.
   * This way we can dynamically handle the collapse animations, which require
   * an accurate height to look smooth.
   */

  function setInitialHeights() {
    $('.sub-dir').once('initial-heights').each(function (index, elem) {
      var $childList = $(elem).children('ul'); // Temporarily alter the element so that a height can be determined.

      $(elem).removeClass('collapsed');
      $childList.css('position', 'absolute');
      $childList.css('max-height', ''); // Get the offsetHeight and set it to a data attribute for later use.

      $childList.attr('data-height', $childList.prop('offsetHeight')); // Reset the element's style to its normal state.

      $childList.css('position', '');
      $(elem).addClass('collapsed');
    });
  }
  /**
   * Update all ul heights upstream.
   *
   * We do this because a collapsed child list affects the actual height of all
   * its parents.
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
        var $elem = $parent.children('ul').first();
        var dataHeight = $elem.attr('data-height');

        if ($parent.hasClass('collapsed')) {
          $parent.removeClass('collapsed');
          $elem.css('max-height', "".concat(dataHeight, "px"));
          heightOffset = dataHeight;
        } else {
          $parent.addClass('collapsed');
          $elem.css('max-height', 0);
          heightOffset = -dataHeight;
        }

        alterParentHeight($parent, heightOffset);
      });
    }
  };
})(jQuery, Drupal);