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
 * @module     enrol_select/edit_calendar
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {
    return {
        initialise : function(warning){
            // Récupère le menu déroulant permettant de choisir le calendrier.
            let calendarselect = document.getElementById('id_customchar1');

            if (!calendarselect) {
                // Il devrait toujours avoir un menu déroulant pour choisir le calendrier...
                return;
            }

            // Récupère la valeur courante du calendrier.
            const originalvalue = calendarselect.value;

            if (originalvalue == '0') {
                // Le calendrier n'est pas utilisé. Il ne peut pas avoir un risque de suppression de notes.
                return;
            }

            // Place un évènement pour détecter les changements de la valeur du calendrier.
            calendarselect.addEventListener('change', function(evt) {
                // Récupère la div Moodle permettant d'afficher les erreurs.
                let errordiv = document.getElementById('id_error_customchar1');

                if (!errordiv) {
                    // Il devrait toujours avoir une div Moodle permettant d'afficher les erreurs...
                    return;
                }

                if (evt.target.value == originalvalue) {
                    // La valeur actuelle est identique à la valeur d'origine. On supprime le message d'erreur.
                    errordiv.textContent = '';
                    errordiv.style.display = 'None';
                } else {
                    // On affiche le message d'erreur pour indiquer le risque de perte de données.
                    errordiv.textContent = warning;
                    errordiv.style.display = 'block';
                }
            });
        }
    };
});
