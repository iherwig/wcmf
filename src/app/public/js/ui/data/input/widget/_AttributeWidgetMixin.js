define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/on"
], function (
    declare,
    lang,
    on
) {
    /**
     * Attribute widget mixin. Manages the dirty flag.
     */
    return declare([], {
        _isDirty: false,

        postCreate: function() {
            this.inherited(arguments);

            this.own(
                on(this, "change", lang.hitch(this, function() {
                    this.setDirty(true);
                }))
            );
        },

        setDirty: function (isDirty) {
            this._isDirty = isDirty;
        },

        isDirty: function () {
            return this._isDirty;
        }
    });
});