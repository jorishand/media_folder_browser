/**
 * @file
 * Media folder browser sidebar related JS code.
 */

(($, Drupal) => {
  /**
   * Reloads the items in the overview when a folder is selected.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for submitting media.
   */
  Drupal.behaviors.treeFolderReload = {
    attach(context) {
      $('.js-tree-item', context).click((e) => {
        e.preventDefault();
        const clickedElement = $(e.currentTarget);

        // Set 'selected' class on the clicked folder.
        $('.selected').removeClass('selected');
        clickedElement.addClass('selected');
        // Set 'current folder' label and ID.
        const $currentFolder = $('.js-current-folder');
        $currentFolder.html(clickedElement.children('span').html());
        $currentFolder.attr('data-folder-id', clickedElement.attr('data-id'));
        // Display the loader.
        $('.loader-container').removeClass('hidden');
        // Refresh the overview.
        Drupal.mfbCommon.reload(clickedElement.attr('data-id'));
      });
    },
  };

  /**
   * Set initial height of all collapsible folders after the first uncollapse.
   *
   * We set a data attribute for each collapsible list with its offset height.
   * This way we can dynamically handle the collapse animations, which require
   * an accurate height to look smooth.
   */
  function setInitialHeights() {
    $('.sub-dir').once('initial-heights').each((index, elem) => {
      const $childList = $(elem).children('ul');

      // Temporarily alter the element so that a height can be determined.
      $(elem).removeClass('collapsed');
      $childList.css('position', 'absolute');
      $childList.css('max-height', '');

      // Get the offsetHeight and set it to a data attribute for later use.
      $childList.attr('data-height', $childList.prop('offsetHeight'));

      // Reset the element's style to its normal state.
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
    const parentList = $element.parent('ul');
    const dataHeight = parseInt(parentList.attr('data-height'), 10) + parseInt(offset, 10);

    parentList.css('max-height', `${dataHeight}px`);
    parentList.attr('data-height', dataHeight);

    const parentListParent = parentList.parent('.sub-dir');
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
    attach(context) {
      $('.js-dropdown', context).click((e) => {
        e.preventDefault();
        setInitialHeights();

        const clickedElement = $(e.currentTarget);
        const $parent = clickedElement.parent().parent('.sub-dir');
        let heightOffset = 0;

        const $elem = $parent.children('ul').first();
        const dataHeight = $elem.attr('data-height');

        if ($parent.hasClass('collapsed')) {
          $parent.removeClass('collapsed');
          $elem.css('max-height', `${dataHeight}px`);
          heightOffset = dataHeight;
        }
        else {
          $parent.addClass('collapsed');
          $elem.css('max-height', 0);
          heightOffset = -dataHeight;
        }

        alterParentHeight($parent, heightOffset);
      });
    },
  };
})(jQuery, Drupal);
