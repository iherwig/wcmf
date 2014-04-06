define([
    "dojo/date/locale"
],
function(
    locale
) {
    return function(value, attribute, synch) {
        if (value) {
            var parseDateFormat = {
                selector: 'date',
                datePattern: 'yyyy-MM-dd',
                locale: appConfig.uiLanguage
            };
            var formatDateFormat = {
                selector: 'date',
                formatLength: 'short',
                fullYear: true,
                locale: appConfig.uiLanguage
            };
            if (typeof value === "string") {
                value = locale.parse(value.substring(1, 10), parseDateFormat);
            }
            value = locale.format(value, formatDateFormat);
        }
        return value;
    };
});