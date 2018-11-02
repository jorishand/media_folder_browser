"use strict";

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

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
  var ContextMenu =
  /*#__PURE__*/
  function () {
    function ContextMenu(target, type, x, y) {
      _classCallCheck(this, ContextMenu);

      this.target = target;
      this.type = type;
      this.x = x;
      this.y = y;
    }

    _createClass(ContextMenu, [{
      key: "render",
      value: function render() {
        var _this = this;

        // Remove any old context menus from the DOM.
        $('.js-context-menu').remove(); // Build the context menu.;

        var $menuWrapper = $("<div class=\"folder-context-menu js-context-menu\" style=\"left:".concat(this.x, "px;top:").concat(this.y, "px\">"));
        var $menu = $('<ul class="context-options">');

        if (this.type === 'overview') {
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

          if (this.type === 'media') {
            $menu.append($("<li class=\"option\" data-action=\"edit\">".concat(Drupal.t('Edit'), "</li>")));
          }

          $menu.append($("<li class=\"option\" data-action=\"rename\">".concat(Drupal.t('Rename'), "</li>")));
          $menu.append($("<li class=\"option\" data-action=\"delete\">".concat(Drupal.t('Delete'), "</li>")));
        }

        $('.js-media-folder-browser').append($menuWrapper.append($menu));
        this.domElement = $menu; // todo: Add event listeners

        $menu.find('[data-action]').each(function (index, elem) {
          elem.addEventListener('click', function () {
            return _this.actionHandler(elem);
          });
        });
      }
    }, {
      key: "actionHandler",
      value: function actionHandler(elem) {
        console.log(elem);
        console.log(this.target);
        console.log(this.type);
        console.log(this.x);
        console.log(this.y);
      }
    }]);

    return ContextMenu;
  }();
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

        var context = new ContextMenu($(e.trigger), 'overview', e.clientX, e.clientY);
        context.render();
      });
      $('.js-tree-item, .js-folder-item').bind('contextmenu', function (e) {
        e.preventDefault();
        var context = new ContextMenu($(e.currentTarget), 'folder', e.clientX, e.clientY);
        context.render();
      });
      $('.js-media-item').bind('contextmenu', function (e) {
        e.preventDefault();
        var context = new ContextMenu($(e.currentTarget), 'media', e.clientX, e.clientY);
        context.render();
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