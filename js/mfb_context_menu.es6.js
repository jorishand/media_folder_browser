/**
 * @file
 * Media folder browser JS code for the context menu.
 */

(($, Drupal) => {
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
  class ContextMenu {
    constructor(target, type, x, y) {
      this.target = target;
      this.type = type;
      this.x = x;
      this.y = y;
    }

    render() {
      // Remove any old context menus from the DOM.
      $('.js-context-menu').remove();

      // Build the context menu.;
      const $menuWrapper = $(`<div class="folder-context-menu js-context-menu" style="left:${this.x}px;top:${this.y}px">`);
      const $menu = $('<ul class="context-options">');

      if (this.type === 'overview') {
        $menu.append($(`<li class="option" data-action="add-folder">${Drupal.t('Add folder')}</li>`));
        $menu.append($(`<li class="option" data-action="add-media">${Drupal.t('Add media')}</li>`));
      }
      else {
        if (this.type === 'media') {
          // The edit option is unique to media.
          $menu.append($(`<li class="option" data-action="edit">${Drupal.t('Edit')}</li>`));
        }

        // If sibling folders are present or we are not in the root directory,
        // add move option and build the folder list.
        const $folders = $('.js-folder-item');
        const currentFolder = $('.js-current-folder').attr('data-folder-id');

        if ($folders[0] || currentFolder) {
          const $moveAction = $(`<li class="option">${Drupal.t('Move to')}</li>`);
          const $folderList = $('<ul class="context-options sub-options js-context-move-list"></ul>');

          // Add 'move to parent' option.
          if (currentFolder && currentFolder !== 'root') {
            $folderList.append(`<li class="option" data-action="move-parent">${Drupal.t('Parent folder')}</li>`);
          }

          // Add move options for sibling folders.
          $folders.each((index, elem) => {
            const dataId = $(elem).attr('data-id');
            const folderName = $(elem).find('.overview-item__folder__label').html();
            $folderList.append(`<li class="option" data-action="move" data-id="${dataId}">${folderName}</li>`);
          });

          $menu.append($moveAction.append($folderList));
        }
        $menu.append($(`<li class="option" data-action="rename">${Drupal.t('Rename')}</li>`));
        $menu.append($(`<li class="option" data-action="delete">${Drupal.t('Delete')}</li>`));
      }

      $('.js-media-folder-browser').append($menuWrapper.append($menu));

      $menu.find('[data-action]').each((index, elem) => {
        elem.addEventListener('click', () => this.actionHandler($(elem)));
      });
    }

    actionHandler(elem) {
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

    moveAction(folderId) {
      Drupal.mfbCommon.move(this.target.attr('data-id'), folderId, this.type);
    }

    moveParentAction() {
      if (this.type === 'media') {
        $('.js-loader').removeClass('hidden');
        const mediaId = this.target.attr('data-id');
        const endpoint = Drupal.url(`media-folder-browser/media/move-parent/${mediaId}`);
        Drupal.ajax({ url: endpoint }).execute();
      }
      else {
        $('.js-loader').removeClass('hidden');
        const selectedFolderId = this.target.attr('data-id');
        const endpoint = Drupal.url(`media-folder-browser/folder/move-parent/${selectedFolderId}`);
        Drupal.ajax({ url: endpoint }).execute();
      }
    }

    renameAction() {
      const $label = this.target.find('.js-item-label');
      $label.attr('contenteditable', 'true');
      this.target.addClass('editable');
      $label.focus();
    }

    deleteAction() {
      $('.js-loader').removeClass('hidden');
      const id = this.target.attr('data-id');
      let endpoint = '';
      if (this.type === 'media') {
        endpoint = Drupal.url(`media-folder-browser/media/remove/${id}`);
      }
      else {
        endpoint = Drupal.url(`media-folder-browser/folder/remove/${id}`);
      }
      Drupal.ajax({ url: endpoint }).execute();
    }
  }

  /**
   * Add handlers to build the context menu.
   */
  Drupal.behaviors.contextMenu = {
    attach() {
      $('.js-results-wrapper, .js-overview').bind('contextmenu', (e) => {
        e.preventDefault();
        // Checks if a child was clicked.
        if (e.target !== e.currentTarget) {
          return;
        }
        const context = new ContextMenu($(e.trigger), 'overview', e.clientX, e.clientY);
        context.render();
      });

      $('.js-tree-item, .js-folder-item').bind('contextmenu', (e) => {
        e.preventDefault();
        const context = new ContextMenu($(e.currentTarget), 'folder', e.clientX, e.clientY);
        context.render();
      });

      $('.js-media-item').bind('contextmenu', (e) => {
        e.preventDefault();
        const context = new ContextMenu($(e.currentTarget), 'media', e.clientX, e.clientY);
        $(e.currentTarget).addClass('focused');
        context.render();
      });
    },
  };

  /**
   * Hides the context menu.
   */
  Drupal.behaviors.hideContextMenu = {
    attach() {
      $('.js-media-folder-browser').on('click', () => {
        $('.js-context-menu').remove();
        $('.focused').removeClass('focused');
      });
    },
  };

  /**
   * Handles renaming.
   */
  Drupal.behaviors.renameItem = {
    attach() {
      const $label = $('.js-item-label');

      // Prevent the parent click event (select) from being triggered.
      $label.on('click', (e) => {
        e.stopPropagation();
      });

      // Trigger focusout when pressing enter.
      $label.on('keydown', (e) => {
        if (e.which === 13) {
          e.preventDefault();
          $(e.target).blur();
        }
      });

      $label.on('focusout', (e) => {
        const $activeSpan = $(e.target);
        const input = $activeSpan.html();
        const id = $activeSpan.attr('data-id');
        let endpoint = '';

        if ($activeSpan.parent().hasClass('js-folder-item')) {
          endpoint = Drupal.url(`media-folder-browser/folder/rename/${id}/${input}`);
          $activeSpan.parent().removeClass('editable');
        }
        else {
          $activeSpan.closest('.js-media-item').removeClass('editable');
          // Todo: create endpoint
          // endpoint = Drupal.url(`media-folder-browser/media/rename/${id}/${input}`);
        }

        Drupal.ajax({ url: endpoint }).execute();
      });
    },
  };
})(jQuery, Drupal);
