/**
 * @file
 * Media folder browser functions for handling drag and drop functionality.
 */

(($, Drupal) => {
  Drupal.mfbDragDrop = {
    /**
     * Handles drag start.
     *
     * @param {object} event
     *   Event on onDragOver trigger.
     * @param {string} type
     *   The type of item.
     */
    onDragStartHandler: (event, type) => {
      event.dataTransfer.setData('id', event.target.getAttribute('data-id'));
      event.dataTransfer.setData('type', type);
    },
    /**
     * Handles dragging an item over a folder.
     *
     * @param {object} event
     *   Event on onDragOver trigger.
     */
    onDragOverHandler: (event) => {
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
    onDropHandler: (event, el) => {
      event.preventDefault();
      const type = event.dataTransfer.getData('type');
      const sourceId = event.dataTransfer.getData('id');
      const destId = el.getAttribute('data-id');

      Drupal.mfbCommon.move(sourceId, destId, type);
    },
  };
})(jQuery, Drupal);
