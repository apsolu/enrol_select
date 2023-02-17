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
 * @module     enrol_select/select_subscribe
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'enrol_select/notify'], function($) {
    return {
        initialise: function(wwwroot) {
            $("input[name='enrolin']").css("display", "none");

            $("select[name=roleid]").change(function() {
                // Get form values.
                var courseid = $(this).attr('id').substring(9);
                var roleid = $(this).children('option:selected')[0].value;
                var enrolid = $(this).parent().find("input[name='enrolid']")[0].value;

                // Set 'apsolu-loading' css class to information cell.
                var information = $(this).parent().parent().next(".apsolu-activities-information-td");
                information.addClass('apsolu-loading');

                var main_list = $(this).parent().parent().next().next(".apsolu-activities-main-list-td");

                var wait_list = $(this).parent().parent().next().next().next(".apsolu-activities-wait-list-td");

                $.ajax(
                    {
                        url: wwwroot + "/enrol/select/ajax/subscribe.php",
                        type: 'POST',
                        data: {courseid: courseid, roleid: roleid, enrolid: enrolid},
                        dataType: 'json'
                    })
                    .done(function(result) {
                        switch (result.status_code) {
                            case "0":
                                $.notify(result.message, "success");
                                break;
                            case "1":
                                $.notify(result.message, "warn");
                                break;
                            case "2":
                                $.notify(result.message, "error");
                                break;
                        }

                        $('.notifyjs-corner').css('top', '50px');

                        main_list.text(result.main_list);
                        wait_list.text(result.wait_list);

                        $("#apsolu-wishes-div").remove();
                        $("#apsolu-filters-div").prepend(result.wishes_tpl);
                    })
                    .fail(function() {
                        $.notify("Une erreur est survenue.", "error");
                        $('.notifyjs-corner').css('top', '50px');
                    })
                    .always(function() {
                        information.removeClass('apsolu-loading');
                    });
            });
        }
    };
});
