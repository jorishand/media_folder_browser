"use strict";

/**
 * @file
 * Media folder browser functions for handling drag and drop functionality.
 */
(function ($, Drupal) {
  Drupal.mfbDragDrop = {
    /**
     * Handles drag start.
     *
     * @param {object} event
     *   Event on onDragOver trigger.
     * @param {string} type
     *   The type of item.
     */
    onDragStartHandler: function onDragStartHandler(event, type) {
      event.dataTransfer.setData('id', event.target.getAttribute('data-id'));
      event.dataTransfer.setData('type', type);
    },

    /**
     * Handles dragging an item over a folder.
     *
     * @param {object} event
     *   Event on onDragOver trigger.
     */
    onDragOverHandler: function onDragOverHandler(event) {
      event.preventDefault();
    },

    /**
     * Handles dropping an item in a folder.
     *
     * @param {object} event
     *   Event on onDragOver trigger.
     * @param {object} el
     *   Element to drop into.
     */
    onDropHandler: function onDropHandler(event, el) {
      event.preventDefault();
      var type = event.dataTransfer.getData('type');
      var sourceId = event.dataTransfer.getData('id');
      var destId = el.getAttribute('data-id');

      if (sourceId !== destId && sourceId !== 'root' && sourceId) {
        if (destId === 'root') {
          destId = null;
        }

        Drupal.mfbCommon.move(sourceId, destId, type);
      }
    }
  };
})(jQuery, Drupal);