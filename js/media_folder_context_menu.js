/**
 * @file
 * Media folder browser related JS code.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Populate folders in the "move" sub-menu.
   */
  Drupal.behaviors.setMoveFolders = {
    attach: function (context) {
      $('.overview-item__folder', context).each(function() {
        var folder_item = '<li class="option js-context-action" data-action="move" data-id="'
            + $(this).attr('data-id') + '">'
            + $(this).find('.overview-item__folder__label').html()
            + '</li>';
        $('.js-context-move-list').append(folder_item);
      });
    }
  };

  /**
   * Shows the context menu.
   */
  Drupal.behaviors.contextMenu = {
    attach: function (context) {
      var $context = $('.js-context-menu');
      var $overview_context = $('.js-overview-context');
      var $entity_context = $('.js-entity-context');

      function openContext(event) {
        $context.css('left', event.clientX + 'px');
        $context.css('top', event.clientY + 'px');
        $context.attr('data-active-element', event.trigger);
        toggleMenu('show');
      }

      $('.js-overview').bind('contextmenu',function(e){
        e.preventDefault();

        // Checks if a child was clicked.
        if (e.target !== this) {
          return;
        }

        $entity_context.css('display', 'none');
        $overview_context.css('display', 'block');
        openContext(e);
      });

      $('.js-tree-item, .js-folder-item').bind('contextmenu',function(e){
        e.preventDefault();
        $entity_context.css('display', 'block');
        $overview_context.css('display', 'none');
        $(".js-context-action[data-action='edit']").css('display', 'none');
        openContext(e);
      });

      $('.js-media-item').bind('contextmenu',function(e){
        e.preventDefault();
        $entity_context.css('display', 'block');
        $overview_context.css('display', 'none');
        $(".js-context-action[data-action='edit']").css('display', 'block');
        openContext(e);
      });
    }
  };

  Drupal.behaviors.contextAction = {
    attach: function (context) {
      $('.js-context-action').click(function(e) {
        switch ($(this).attr('data-action')) {
          case "move":
            console.log('move to', $(this).attr('data-id'));
            break;

          default:
            console.log('action clicked');
        }
      });
    }
  };

  /**
   * Hides the context menu.
   */
  Drupal.behaviors.hideContextMenu = {
    attach: function (context) {
      $('.media-folder-browser').click(function(e) {
        e.preventDefault();
        if ($('.js-context-menu').css('display') === 'block') {
          toggleMenu('hide');
        }
      });
    }
  };

  /**
   * Function to toggle the context menu display.
   */
  function toggleMenu(command) {
    var $context = $('.js-context-menu');
    if (command === 'show') {
      $context.css('display', 'block');
    }else {
      $context.css('display', 'none');
    }
  }
})(jQuery, Drupal);