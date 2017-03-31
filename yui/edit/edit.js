/**
 * @package    mod_branchedquiz
 * @copyright  2017 onwards Dominik Wittenberg, Paul Youssef, Pavel Azanov, Allessandro Noli, Robin Voigt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

var EDIT = function() {
    EDIT.superclass.constructor.apply(this, arguments);
};

Y.extend(EDIT, Y.Base, {
    initializer: function () {
        console.log(arguments)
    }
});

// Ensure that M.mod_branchedquiz exists and that coursebase is initialised correctly
M.mod_branchedquiz = M.mod_branchedquiz || {};
M.mod_branchedquiz.edit = M.mod_branchedquiz.edit || new EDIT();
M.mod_branchedquiz.edit.init = function() {
    return new EDIT();
};
