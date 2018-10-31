/**
 * @file
 * Media folder browser JS code for the context menu.
 */

(($, Drupal) => {
  /**
   * Populate folders in the "move" sub-menu.
   */
  Drupal.behaviors.setMoveFolders = {
    attach(context) {
      $('.overview-item__folder', context).each((index, elem) => {
        const dataId = $(elem).attr('data-id');
        const folderName = $(elem).find('.overview-item__folder__label').html();
        const folderItem = `<li class="option js-context-action" data-action="move" data-id="${dataId}">${folderName}</li>`;
        $('.js-context-move-list').append(folderItem);
      });
    },
  };

  /**
   * Function to toggle the context menu display.
   *
   * @param {string} command
   *   Wether to show or hide the menu, use "show" to make the menu visible.
   */
  function toggleMenu(command) {
    const $context = $('.js-context-menu');
    if (command === 'show') {
      $context.css('display', 'block');
    }
    else {
      $context.css('display', 'none');
    }
  }

  /**
   * Shows the context menu.
   */
  Drupal.behaviors.contextMenu = {
    attach() {
      const $context = $('.js-context-menu');
      const $overviewContext = $('.js-overview-context');
      const $entityContext = $('.js-entity-context');

      function openContext(event) {
        $context.css('left', `${event.clientX}px`);
        $context.css('top', `${event.clientY}px`);
        $context.attr('data-active-element', event.trigger);
        toggleMenu('show');
      }

      $('.js-overview').bind('contextmenu', (e) => {
        e.preventDefault();

        // Checks if a child was clicked.
        if (e.target !== e.currentTarget) {
          return;
        }

        $entityContext.css('display', 'none');
        $overviewContext.css('display', 'block');
        openContext(e);
      });

      $('.js-tree-item, .js-folder-item').bind('contextmenu', (e) => {
        e.preventDefault();
        $entityContext.css('display', 'block');
        $overviewContext.css('display', 'none');
        $(".js-context-action[data-action='edit']").css('display', 'none');
        openContext(e);
      });

      $('.js-media-item').bind('contextmenu', (e) => {
        e.preventDefault();
        $entityContext.css('display', 'block');
        $overviewContext.css('display', 'none');
        $(".js-context-action[data-action='edit']").css('display', 'block');
        openContext(e);
      });
    },
  };

  /**
   * Handles context menu actions.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches click events to the different options in the context menu.
   */
  Drupal.behaviors.contextAction = {
    attach() {
      $('.js-context-action').click((e) => {
        const clickedElement = $(e.currentTarget);
        switch (clickedElement.attr('data-action')) {
          case 'move':
            console.log('move to', $(this).attr('data-id'));
            break;

          default:
            console.log('action clicked');
        }
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
        if ($('.js-context-menu').css('display') === 'block') {
          toggleMenu('hide');
        }
      });
    },
  };
})(jQuery, Drupal);
