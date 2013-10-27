define([
    "dojo/_base/declare",
    "dojox/layout/TableContainer"
], function (
    declare,
    TableContainer
) {
    return declare([TableContainer], {

        cols: 2,
        customClass: 'entity-form'

    });
});