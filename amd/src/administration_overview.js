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
 * @module     enrol_select/administration_overview
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {
    return {
        initialise : function() {
            var table = document.getElementById('table-enrolments-overview');

            if (table === false) {
                return;
            }

            // Insère une checkbox au dessus de la liste des méthodes d'inscription.
            var div = document.createElement('div');
            div.innerHTML = '<p class="alert alert-danger">'+
                '<label>'+
                '<input id="show-only-errors" type="checkbox" /><span class="px-2">Afficher uniquement les erreurs</span>'+
                '</label>'+
                '</p>';
            table.before(div);

            // Place un évènement pour afficher uniquement les lignes en erreur.
            var input = document.getElementById('show-only-errors');
            input.addEventListener('change', function() {
                var style = 'table-row';
                var rows = document.querySelectorAll("#table-enrolments-overview tr[data-anomalies='0']");
                if (input.checked) {
                    style = 'none';
                }

                for (var i = 0; i < rows.length; i++) {
                    rows[i].style.display = style;
                }
            });
        }
    };
});
