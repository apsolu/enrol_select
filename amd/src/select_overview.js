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
 * @module     enrol_select/select_overview
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(["jquery", "enrol_select/jquery.popupoverlay"], function($) {
    return {
        initialise: function() {
            // Action exécutée à chaque clic sur les flèches à gauche du nom de l'activité.
            $(".apsolu-sports-th-span").click(function() {
                $(this).parent().parent().nextUntil(".apsolu-sports-tr-activity").toggle("slow", "swing");
                if ($(this).hasClass("apsolu-expandable")) {
                    $(this).attr("class", "apsolu-sports-th-span apsolu-collapsible");
                } else if ($(this).hasClass("apsolu-collapsible")) {
                    $(this).attr("class", "apsolu-sports-th-span apsolu-expandable");
                }
            });

            // Overlay : http://dev.vast.com/jquery-popup-overlay/.
            // Affiche la description d'un sport.
            $('.apsolu-sports-description-info-img').click(function() {
                var id = $(this).data('popup');
                var description = $('#' + id);

                description.css({
                    backgroundColor: '#EEEEEE',
                    padding: '.5em',
                    cursor: 'default',
                    maxWidth: '50%',
                    textAlign: 'justify'
                });
                description.popup('show');
            });

            // Masque le tableau récapitulatif des voeux.
            $('#apsolu-rules-summary').css('display', 'none');

            $('#apsolu-rules-summary-a').click(function(evt) {
                evt.preventDefault();

                $('#apsolu-rules-summary').css({backgroundColor: '#EEEEEE', padding: '.5em', cursor: 'default', maxWidth: '50%'});
                $('#apsolu-rules-summary').popup('show');
            });
        }
    };
});
