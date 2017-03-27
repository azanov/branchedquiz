/**
 * @package    mod_branchedquiz
 * @copyright  2017 onwards Dominik Wittenberg, Paul Youssef, Pavel Azanov, Allessandro Oxymora, Robin Voigt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

var MODFORM = function() {
    MODFORM.superclass.constructor.apply(this, arguments);
};

Y.extend(MODFORM, Y.Base, {
    qppSelect: null,
    navSelect: null,
    pbhSelect: null,

    initializer: function () {
        this.qppSelect = Y.one('#id_questionsperpage');
        this.navSelect = Y.one('#id_navmethod');
        this.pbhSelect = Y.one('#id_preferredbehaviour');

        this.qppSelect.set('value', 1);
        this.qppSelect.set('disabled', 1);

        this.navSelect.set('value', 'sequential');
        this.navSelect.set('disabled', 1);

        this.navSelect.set('value', 'sequential');
        this.navSelect.set('disabled', 1);

        this.pbhSelect.set('value', 'immediatefeedback');
        this.pbhSelect.set('disabled', 1);
    }

});

// Ensure that M.mod_branchedquiz exists and that coursebase is initialised correctly
M.mod_branchedquiz = M.mod_branchedquiz || {};
M.mod_branchedquiz.modform = M.mod_branchedquiz.modform || new MODFORM();
M.mod_branchedquiz.modform.init = function() {
    return new MODFORM();
};
