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
          if (this.type === 'media') {
            // If sibling folders are present or we are not in the root directory,
            // add move option and build the folder list.
            var $folders = $('.overview-item__folder');
            var currentFolder = $('.js-current-folder').attr('data-folder-id');

            if ($folders[0] || currentFolder) {
              var $moveAction = $("<li class=\"option\">".concat(Drupal.t('Move to'), "</li>"));
              var $folderList = $('<ul class="context-options sub-options js-context-move-list"></ul>'); // Add 'move to parent' option.

              if (currentFolder && currentFolder !== 'root') {
                $folderList.append("<li class=\"option\" data-action=\"move-parent\">".concat(Drupal.t('Parent folder'), "</li>"));
              } // Add move options for sibling folders.


              $folders.each(function (index, elem) {
                var dataId = $(elem).attr('data-id');
                var folderName = $(elem).find('.overview-item__folder__label').html();
                $folderList.append("<li class=\"option\" data-action=\"move\" data-id=\"".concat(dataId, "\">").concat(folderName, "</li>"));
              });
              $menu.append($moveAction.append($folderList));
            }

            $menu.append($("<li class=\"option\" data-action=\"edit\">".concat(Drupal.t('Edit'), "</li>")));
          }

          $menu.append($("<li class=\"option\" data-action=\"rename\">".concat(Drupal.t('Rename'), "</li>")));
          $menu.append($("<li class=\"option\" data-action=\"delete\">".concat(Drupal.t('Delete'), "</li>")));
        }

        $('.js-media-folder-browser').append($menuWrapper.append($menu));
        $menu.find('[data-action]').each(function (index, elem) {
          elem.addEventListener('click', function () {
            return _this.actionHandler($(elem));
          });
        });
      }
    }, {
      key: "actionHandler",
      value: function actionHandler(elem) {
        switch (elem.attr('data-action')) {
          case 'add-folder':
            break;

          case 'add-media':
            break;

          case 'move':
            this.moveAction(elem.attr('data-id'));
            break;

          case 'move-parent':
            this.moveParentAction();
            break;

          case 'edit':
            break;

          case 'rename':
            break;

          case 'delete':
            break;

          default:
            console.log('action not recognised');
        }
      }
    }, {
      key: "moveAction",
      value: function moveAction(folderId) {
        if (this.type === 'media') {
          var mediaId = this.target.attr('data-id');
          var endpoint = Drupal.url("media-folder/move-media/".concat(mediaId, "/").concat(folderId));
          var status = Drupal.ajax({
            url: endpoint
          }).execute();

          if (status) {
            // Display the loader.
            $('.loader-container').removeClass('hidden'); // Refresh the overview.

            Drupal.mfbCommon.reload($('.js-current-folder').attr('data-folder-id'));
          }
        } else {
          console.log('execute move folder action');
        }
      }
    }, {
      key: "moveParentAction",
      value: function moveParentAction() {
        if (this.type === 'media') {
          var mediaId = this.target.attr('data-id');
          var endpoint = Drupal.url("media-folder/move-media-parent/".concat(mediaId));
          var status = Drupal.ajax({
            url: endpoint
          }).execute();

          if (status) {
            // Display the loader.
            $('.loader-container').removeClass('hidden'); // Refresh the overview.

            Drupal.mfbCommon.reload($('.js-current-folder').attr('data-folder-id'));
          }
        } else {
          console.log('execute move folder to parent action');
        }
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