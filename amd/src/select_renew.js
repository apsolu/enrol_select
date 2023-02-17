// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Module javascript.
 *
 * @todo       Description à compléter.
 *
 * @module     enrol_select/select_renew
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(["jquery"], function($) {
    return {
        initialise: function() {
            /**
             * Fonction appelée pour modifier les boutons radio.
             *
             * @param {HTMLElement} input Bouton radio à éditer.
             */
            function apsolu_renew_set_radio_pairing(input) {
                var name = input.attr("name");
                var index = name.substr(5);

                if (input.attr("value") === "1") {
                    input.parent().addClass("success");
                    input.parent().removeClass("danger");

                    $("#apsolu-enrol-select-renew-table input[name='role" + index + "']").prop("disabled", false);
                    input.parent().next().removeClass("apsolu-inactive-td");
                } else if (input.attr("value") === "0") {
                    input.parent().removeClass("success");
                    input.parent().addClass("danger");

                    $("#apsolu-enrol-select-renew-table input[name='role" + index + "']").prop("disabled", true);
                    input.parent().next().addClass("apsolu-inactive-td");
                }
            }

            $("#apsolu-enrol-select-renew-table input[name^='renew']").each(function() {
                if ($(this).prop("checked")) {
                    apsolu_renew_set_radio_pairing($(this));
                }

                $(this).change(function() {
                    apsolu_renew_set_radio_pairing($(this));
                });
            });
        }
    };
});
