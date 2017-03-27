/**
 * The modform class has all the JavaScript specific to mod/quiz/edit.php.
 *
 * @module moodle-mod_branchedquiz-edit
 */

var EDIT = function() {
    EDIT.superclass.constructor.apply(this, arguments);
};

/**
 * The coursebase class to provide shared functionality to Modules within
 * Moodle.
 *
 * @class M.course.coursebase
 * @constructor
 */
Y.extend(EDIT, Y.Base, {
    initializer: function () {

    }
});

// Ensure that M.mod_branchedquiz exists and that coursebase is initialised correctly
M.mod_branchedquiz = M.mod_branchedquiz || {};
M.mod_branchedquiz.edit = M.mod_branchedquiz.edit || new EDIT();
M.mod_branchedquiz.edit.init = function() {
    return new EDIT();
};
