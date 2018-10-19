/**
 * @file
 * Media folder browser sidenbar related JS code.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Reload the items in the tile view when a folder is selected.
   */
  Drupal.behaviors.treeFolderReload = {
    attach: function (context) {
      $('.js-tree-item', context).click(function (e) {
        e.preventDefault();
        $('.selected').removeClass('selected');
        $(this).addClass('selected');
        refreshResults($(this).attr("data-id"));
      });

      function refreshResults(id) {
        $.get('/media-folder-browser/overview/refresh', {id:id}, function (data) {
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

    }
  };

  /**
   * Set initial heights of the child lists by subtracting their total height
   * with the height of their children.
   */
  Drupal.behaviors.initialHeights = {
    attach: function(context) {
      $('.sub-dir', context).each(function() {
        $(this).find('ul').each(function() {
          var child_heights = 0;
          $(this).find('> li > ul').each(function() {
            child_heights += parseInt($(this).prop('scrollHeight'));
          });
          var collapsed_height = parseInt($(this).prop('scrollHeight')) - child_heights;
          $(this).attr('data-height', collapsed_height);
          $(this).css('max-height', 0);
        });
      });
    }
  };

  /**
   * Handles collapsing of submenus in the folder tree structure.
   */
  Drupal.behaviors.sidebarCollapse = {
    attach: function (context) {
      $('.sub-dir > a', context).click(function() {
        var $parent = $(this).parent('.sub-dir');
        var heightOffset = 0;

        if ($parent.hasClass('collapsed')) {
          $parent.removeClass('collapsed');
          $parent.children('ul').each(function() {
            var data_height = $(this).attr('data-height');
            $(this).css('max-height', data_height + 'px');
            heightOffset = data_height;
          });
        } else {
          $parent.addClass('collapsed');
          $parent.children('ul').each(function() {
            $(this).css('max-height', 0);
            heightOffset = -($(this).attr('data-height'));
          });
        }
        alterParentHeight($parent, heightOffset);
      });
    }
  };

  /**
   * Update all ul heights upstream.
   *
   * @param element
   *   The element to start from.
   * @param offset
   *   The element to start from.
   */
  function alterParentHeight(element, offset) {
    var parent_list = element.parent('ul');
    var data_height = parseInt(parent_list.attr('data-height')) + parseInt(offset);

    parent_list.css('max-height', data_height + 'px');
    parent_list.attr('data-height', data_height);

    var parent_list_parent = parent_list.parent('.sub-dir');
    if (parent_list_parent.length) {
      alterParentHeight(parent_list_parent, offset);
    }
  }

})(jQuery, Drupal);
