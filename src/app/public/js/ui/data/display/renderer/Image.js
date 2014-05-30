define([
],
function(
) {
    return function(value, attribute, synch) {
        if (value && value.match(/\.gif$|\.jpg$|\.png$/)) {
            return '<a href="'+value+'" target="_blank"><img src="'+value+'" class="thumb"></a>';
        }
        return value;
    };
});