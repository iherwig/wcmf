define([
    "dojo/_base/declare",
    "bootstrap/Tooltip",
    "dojo/query",
    "dojo/dom-construct",
    "../../locale/Dictionary"
], function (
    declare,
    Tooltip,
    query,
    domConstruct,
    Dict
) {
    return declare([], {

        postCreate: function() {
            this.inherited(arguments);

            var text = this.helpText;
            if (text && text.length > 0) {
                if (this.focusNode && !this.isInlineEditor) {
                    var questionSign = domConstruct.toDom(' <a href="#"><i class="icon-question-sign"></i></a>');
                    domConstruct.place(questionSign, this.focusNode, "after");
                    new Tooltip({
                        "placement": "top",
                        title: Dict.translate(text)
                    },
                    query("a", this.focusNode.parentNode)[0]);
                }
            }
        }
    });
});