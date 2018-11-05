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
   *   The type of context menu, this will determine which options will be shown.
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
        // If folders are present, add move option and build the folder list.
        const $folders = $('.overview-item__folder');
        if ($folders[0]) {
          const $moveAction = $(`<li class="option">${Drupal.t('Move to')}</li>`);
          const $folderList = $('<ul class="context-options sub-options js-context-move-list"></ul>');

          $folders.each((index, elem) => {
            const dataId = $(elem).attr('data-id');
            const folderName = $(elem).find('.overview-item__folder__label').html();
            $folderList.append(`<li class="option" data-action="move" data-id="${dataId}">${folderName}</li>`);
          });

          $menu.append($moveAction.append($folderList));
        }

        if (this.type === 'media') {
          $menu.append($(`<li class="option" data-action="edit">${Drupal.t('Edit')}</li>`));
        }

        $menu.append($(`<li class="option" data-action="rename">${Drupal.t('Rename')}</li>`));
        $menu.append($(`<li class="option" data-action="delete">${Drupal.t('Delete')}</li>`));
      }

      $('.js-media-folder-browser').append($menuWrapper.append($menu));

      this.domElement = $menu;

      // todo: Add event listeners
      $menu.find('[data-action]').each((index, elem) => {
        elem.addEventListener('click', () => this.actionHandler(elem));
      });
    }

    actionHandler(elem) {
      console.log(elem);
      console.log(this.target);
      console.log(this.type);
      console.log(this.x);
      console.log(this.y);
    }
  }

  /**
   * Add handlers to build the context menu.
   */
  Drupal.behaviors.contextMenu = {
    attach() {
      $('.js-overview').bind('contextmenu', (e) => {
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
        context.render();
      });
    },
  };

  /**
   * Hides the context menu.
   */
  Drupal.behaviors.hideContextMenu = {
    attach() {
      $('.media-folder-browser').click((e) => {
        e.preventDefault();
        $('.js-context-menu').remove();
      });
    },
  };
})(jQuery, Drupal);
