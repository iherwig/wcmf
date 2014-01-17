define([
    "dojo/_base/declare",
    "dojo/_base/array",
    "./Cookie"
], function (
    declare,
    array,
    Cookie
) {
    var User = declare(null, {
    });

    /**
     * Create the user instance
     * @param login The login name
     * @param roles Array of role names
     */
    User.create = function(login, roles) {
        Cookie.set("user", {
            login: login,
            roles: roles
        });
    };

    /**
     * Get the user's login
     * @return String
     */
    User.getLogin = function() {
        var user = Cookie.get("user");
        return user ? user.login : "";
    };

    /**
     * Check if the user has the given role
     * @return Boolean
     */
    User.hasRole = function(name) {
        var user = Cookie.get("user");
        if (user && user.roles) {
            return array.indexOf(user.roles, name) !== -1;
        }
        return false;
    };

    return User;
});