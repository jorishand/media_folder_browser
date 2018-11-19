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
   *   The type of context menu, this will determine which options will be
   *     shown.
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
            // The edit option is unique to media.
            $menu.append($("<li class=\"option\" data-action=\"edit\">".concat(Drupal.t('Edit'), "</li>")));
          } // If sibling folders are present or we are not in the root directory,
          // add move option and build the folder list.


          var $folders = $('.js-folder-item');
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
          case 'move':
            this.moveAction(elem.attr('data-id'));
            break;

          case 'move-parent':
            this.moveParentAction();
            break;

          case 'edit':
            break;

          case 'rename':
            this.renameAction();
            break;

          case 'delete':
            this.deleteAction();
            break;

          case 'add-folder':
            Drupal.mfbCommon.addFolder();
            break;

          case 'add-media':
            Drupal.mfbCommon.addMedia();
            break;

          default:
        }
      }
    }, {
      key: "moveAction",
      value: function moveAction(folderId) {
        if (this.type === 'media') {
          $('.js-loader').removeClass('hidden');
          var mediaId = this.target.attr('data-id');
          var endpoint = Drupal.url("media-folder-browser/media/move/".concat(mediaId, "/").concat(folderId));
          Drupal.ajax({
            url: endpoint
          }).execute();
        } else {
          $('.js-loader').removeClass('hidden');
          var selectedFolderId = this.target.attr('data-id');

          var _endpoint = Drupal.url("media-folder-browser/folder/move/".concat(selectedFolderId, "/").concat(folderId));

          Drupal.ajax({
            url: _endpoint
          }).execute();
        }
      }
    }, {
      key: "moveParentAction",
      value: function moveParentAction() {
        if (this.type === 'media') {
          $('.js-loader').removeClass('hidden');
          var mediaId = this.target.attr('data-id');
          var endpoint = Drupal.url("media-folder-browser/media/move-parent/".concat(mediaId));
          Drupal.ajax({
            url: endpoint
          }).execute();
        } else {
          $('.js-loader').removeClass('hidden');
          var selectedFolderId = this.target.attr('data-id');

          var _endpoint2 = Drupal.url("media-folder-browser/folder/move-parent/".concat(selectedFolderId));

          Drupal.ajax({
            url: _endpoint2
          }).execute();
        }
      }
    }, {
      key: "renameAction",
      value: function renameAction() {
        var $label = this.target.find('.js-item-label');
        $label.attr('contenteditable', 'true');
        this.target.addClass('editable');
        $label.focus();
      }
    }, {
      key: "deleteAction",
      value: function deleteAction() {
        $('.js-loader').removeClass('hidden');
        var id = this.target.attr('data-id');
        var endpoint = '';

        if (this.type === 'media') {
          endpoint = Drupal.url("media-folder-browser/media/remove/".concat(id));
        } else {
          endpoint = Drupal.url("media-folder-browser/folder/remove/".concat(id));
        }

        Drupal.ajax({
          url: endpoint
        }).execute();
      }
    }]);

    return ContextMenu;
  }();
  /**
   * Add handlers to build the context menu.
   */


  Drupal.behaviors.contextMenu = {
    attach: function attach() {
      $('.js-results-wrapper, .js-overview').bind('contextmenu', function (e) {
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
        $(e.currentTarget).addClass('focused');
        context.render();
      });
    }
  };
  /**
   * Hides the context menu.
   */

  Drupal.behaviors.hideContextMenu = {
    attach: function attach() {
      $('.js-media-folder-browser').click(function () {
        $('.js-context-menu').remove();
        $('.focused').removeClass('focused');
      });
    }
  };
  /**
   * Handles renaming.
   */

  Drupal.behaviors.renameItem = {
    attach: function attach() {
      var $label = $('.js-item-label'); // Prevent the parent click event (select) from being triggered.

      $label.click(function (e) {
        e.stopPropagation();
      }); // Trigger focusout when pressing enter.

      $label.keydown(function (e) {
        if (e.which === 13) {
          e.preventDefault();
          $(e.target).blur();
        }
      });
      $label.focusout(function (e) {
        var $activeSpan = $(e.target);
        var input = $activeSpan.html();
        var id = $activeSpan.attr('data-id');
        var endpoint = '';

        if ($activeSpan.parent().hasClass('js-folder-item')) {
          endpoint = Drupal.url("media-folder-browser/folder/rename/".concat(id, "/").concat(input));
          $activeSpan.parent().removeClass('editable');
        } else {
          $activeSpan.closest('.js-media-item').removeClass('editable'); // Todo: create endpoint
          // endpoint = Drupal.url(`media-folder-browser/media/rename/${id}/${input}`);
        }

        Drupal.ajax({
          url: endpoint
        }).execute();
      });
    }
  };
})(jQuery, Drupal);