/*jshint strict:false */

/**
 * @class GridAction
 *
 * GridAction defines the structure of objects used in grid instances to
 * perform actions on a row item. Each action renders as an image
 * and executes the given function on the item represented by the row.
 */
define([
    "dojo/_base/declare"
], function (
    declare
) {
    return declare(null, {
      /**
       * The localized name
       */
      name: "",

      /**
       * The icon class
       */
      iconClass: "",

      /**
       * The action function
       */
      execute: function () {}
    });
});
