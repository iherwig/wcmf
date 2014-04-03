define([
],
function(
) {
    return function(value, attribute) {
        return '<a href="'+value+'" target="_blank"><img src="'+value+'" class="thumb"></a>';
    };
});