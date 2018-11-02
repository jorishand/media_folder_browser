"use strict";

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

/**
 * @file
 * Media folder browser JS code for the context menu.
 */
(function ($, Drupal) {
  /**
   * Context menu object.
   *
   * @constructor
   *
   * @param {jQuery} target
   *   The target element.
   * @param {string} type
   *   The type of context menu, this will determine which options will be shown.
   * @param {number} x
   *   The X position for the context menu.
   * @param {number} y
   *   The Y position for the context menu.
   */
  var ContextMenu = function ContextMenu(target, type, x, y) {
    _classCallCheck(this, ContextMenu);

    this.target = target; // Remove any old context menus from the DOM.

    $('.js-context-menu').remove(); // Build the context menu.;

    var $menuWrapper = $("<div class=\"folder-context-menu js-context-menu\" style=\"left:".concat(x, "px;top:").concat(y, "px\">"));
    var $menu = $('<ul class="context-options">');

    if (type === 'overview') {
      $menu.append($("<li class=\"option\" data-action=\"add-folder\">".concat(Drupal.t('Add folder'), "</li>")));
      $menu.append($("<li class=\"option\" data-action=\"add-media\">".concat(Drupal.t('Add media'), "</li>")));
    } else {
      // If folders are present, add move option and build the folder list.
      var $folders = $('.overview-item__folder');

      if ($folders[0]) {
        var $moveAction = $("<li class=\"option\">".concat(Drupal.t('Move to'), "</li>"));
        var $folderList = $('<ul class="context-options sub-options js-context-move-list"></ul>');
        $folders.each(function (index, elem) {
          var dataId = $(elem).attr('data-id');
          var folderName = $(elem).find('.overview-item__folder__label').html();
          $folderList.append("<li class=\"option\" data-action=\"move\" data-id=\"".concat(dataId, "\">").concat(folderName, "</li>"));
        });
        $menu.append($moveAction.append($folderList));
      }

      if (type === 'media') {
        $menu.append($("<li class=\"option\" data-action=\"edit\">".concat(Drupal.t('Edit'), "</li>")));
      }

      $menu.append($("<li class=\"option\" data-action=\"rename\">".concat(Drupal.t('Rename'), "</li>")));
      $menu.append($("<li class=\"option\" data-action=\"delete\">".concat(Drupal.t('Delete'), "</li>")));
    }

    $('.js-media-folder-browser').append($menuWrapper.append($menu));
    this.domElement = $menu; // todo: Add event listeners
    //$menu.find('[data-action]').each((index, elem) => {
    //  elem.addEventListener('click', this.actionHandler(elem).bind(this));
    //});
  };
  /**
   * Add handlers to build the context menu.
   */


  Drupal.behaviors.contextMenu = {
    attach: function attach() {
      $('.js-overview').bind('contextmenu', function (e) {
        e.preventDefault(); // Checks if a child was clicked.

        if (e.target !== e.currentTarget) {
          return;
        }

        new ContextMenu($(e.trigger), 'overview', e.clientX, e.clientY);
      });
      $('.js-tree-item, .js-folder-item').bind('contextmenu', function (e) {
        e.preventDefault();
        new ContextMenu($(e.currentTarget), 'folder', e.clientX, e.clientY);
      });
      $('.js-media-item').bind('contextmenu', function (e) {
        e.preventDefault();
        new ContextMenu($(e.currentTarget), 'media', e.clientX, e.clientY);
      });
    }
  };
  /**
   * Hides the context menu.
   */

  Drupal.behaviors.hideContextMenu = {
    attach: function attach() {
      $('.media-folder-browser').click(function (e) {
        e.preventDefault();
        $('.js-context-menu').remove();
      });
    }
  };
})(jQuery, Drupal);