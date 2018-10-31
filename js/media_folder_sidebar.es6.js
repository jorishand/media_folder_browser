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
      function refreshResults(id) {
        $.get('/media-folder-browser/overview/refresh', { id }, (data) => {
          // Simulate a drupal.ajax response to correctly parse data.
          const ajaxObject = Drupal.ajax({
            url: '',
            base: false,
            element: false,
            progress: false,
          });

          ajaxObject.success(data, 'success');
        });
      }

      $('.js-tree-item', context).click((e) => {
        e.preventDefault();
        const clickedElement = $(e.currentTarget);

        $('.selected').removeClass('selected');
        clickedElement.addClass('selected');
        $('.js-current-folder').html(clickedElement.children('span').html());
        $('.loader-container').removeClass('hidden');
        refreshResults(clickedElement.attr('data-id'));
      });
    },
  };

  /**
   * Set initial height of all collapsible folders after the first uncollapse.
   */
  function setInitialHeights() {
    $('.sub-dir').once('initial-heights').each((index, elem) => {
      const $childList = $(elem).children('ul');

      // Alter style in a way that a height can be determined.
      $(elem).removeClass('collapsed');
      $childList.css('position', 'absolute');
      $childList.css('max-height', '');

      $childList.attr('data-height', $childList.prop('offsetHeight'));

      // Reset style.
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

        if ($parent.hasClass('collapsed')) {
          $parent.removeClass('collapsed');
          $parent.children('ul').each((index, elem) => {
            const dataHeight = $(elem).attr('data-height');
            $(elem).css('max-height', `${dataHeight}px`);
            heightOffset = dataHeight;
          });
        }
        else {
          $parent.addClass('collapsed');
          $parent.children('ul').each((index, elem) => {
            $(elem).css('max-height', 0);
            heightOffset = -($(elem).attr('data-height'));
          });
        }
        alterParentHeight($parent, heightOffset);
      });
    },
  };
})(jQuery, Drupal);
