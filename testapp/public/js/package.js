/**
 * This file is referenced by the `dojoBuild` key in `package.json` and provides extra hinting specific to the Dojo
 * build system about how certain files in the package need to be handled at build time. Build profiles for the
 * application itself are stored in the `profiles` directory.
 *
 * See: http://dojotoolkit.org/reference-guide/1.9/build/buildSystem.html#resource-tags
 */

var profile = (function(){
    var testResourceRe = /^app\/tests\//,
        // checks if mid is in app/tests directory

        copyOnly = function(filename, mid){
            var list = {
                "app/app.profile": true,
                // we shouldn't touch our profile
                "app/package.json": true
                // we shouldn't touch our package.json
            };
            return (mid in list) ||
                (/^app\/resources\//.test(mid)
                    && !/\.css$/.test(filename)) ||
                /(png|jpg|jpeg|gif|tiff)$/.test(filename);
            // Check if it is one of the special files, if it is in
            // app/resource (but not CSS) or is an image
        };

    return {
        resourceTags: {
            test: function(filename, mid){
                return testResourceRe.test(mid) || mid=="app/tests";
                // Tag our test files
            },

            copyOnly: function(filename, mid){
                return copyOnly(filename, mid);
                // Tag our copy only files
            },

            amd: function(filename, mid){
                return !testResourceRe.test(mid)
                    && !copyOnly(filename, mid)
                    && /\.js$/.test(filename);
                // If it isn't a test resource, copy only,
                // but is a .js file, tag it as AMD
            }
        }
    };
})();