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
 * @module     enrol_select/select_filter
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'enrol_select/select2'], function($) {
    return {
        initialise: function() {

            /**
             * Fonction appelée pour filtrer le tableau en fonction des filtres utilisés.
             */
            function apsolu_filter() {
                var rows = $("#apsolu-activities-table tbody tr");

                rows.css("display", "table-row");

                var selections = $("#apsolu-wishes-filters .select2-selection__clear");

                if (selections.length != 0) {
                    rows.each(function() {
                        var row = $(this);
                        var match = true;

                        // Pour chaque filtre.
                        selections.each(function() {
                            if (match == false) {
                                return;
                            }

                            var found = false;
                            var choices = $(this).parent().find(".select2-selection__choice");

                            // Pour chaque valeur de filtres.
                            choices.each(function() {
                                if (found == false) {
                                    var text = $(this).attr("title");
                                    var colname = $(this).parents(":eq(3)").prev().attr("data-column-name");
                                    var th = $('#apsolu-activities-table th[data-column=' + colname + ']');
                                    var colnum = $("#apsolu-activities-table tr th").index(th);

                                    if (row.children().length >= colnum) {
                                        if (colname == 'role') {
                                            found = row.children().eq(colnum).html().indexOf(text) > -1;
                                        } else if (colname == 'starttime' || colname == 'endtime') {
                                            found = row.children().eq(colnum).text().substr(0, 3) == text.substr(0, 2) + ':';
                                        } else if (colname == 'sport' || colname == 'location') {
                                            found = row.children().eq(colnum).children().eq(0).text() == text;
                                        } else {
                                            found = row.children().eq(colnum).text() == text;
                                        }
                                    }
                                }
                            });

                            if (found == false) {
                                match = false;
                            }
                        });

                        if (match == false) {
                            $(this).css("display", "none");
                        }
                    });
                } else {
                    $("#apsolu-activities-table tbody tr th").each(function() {
                        $(this).css("display", "table-cell");

                        if ($(this).hasClass("apsolu-expandable")) {
                            $(this).parent().nextUntil(".apsolu-sports-tr").css("display", "none");
                        } else {
                            $(this).parent().nextUntil(".apsolu-sports-tr").css("display", "table-row");
                        }
                    });
                }
            }

            /**
             * Masque toutes les lignes du tableau contenant un créneau, pour ne garder que le sport.
             */
            function apsolu_collapse_rows() {
                $('.apsolu-sports-th').children('span').attr("class", "apsolu-expandable");
                $('.apsolu-sports-th').parent().nextUntil(".apsolu-sports-tr").css("display", "none");
            }

            $('.filters').each(function() {
                $(this).select2({width: '100%'});

                $(this).on('change.select2', function() {
                    apsolu_filter();
                });
            });

            $('#filters-submit, .apsolu-categories-col').css('display', 'none');
            apsolu_filter();

            $('#filters-reset').click(function() {
                $('.filters').select2('val', '');
                apsolu_filter();

                apsolu_collapse_rows();
            });
        }
    };
});
