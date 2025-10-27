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
 * @module     enrol_select/select_manage_user_selection
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'local_apsolu/table-mask'],
    function($) {
        return {
            initialise: function(activeEnrolId) {
                // Active les premiers sous-onglets (liste des acceptés, liste principale, liste complémentaire, etc).
                $('.apsolu-manage-users-tab-ul > li:first-child > a').each(function() {
                    $(this).addClass('active show');
                    $(this).attr('aria-selected', 'true');
                    $('#' + $(this).attr('aria-controls')).addClass('active show');
                });

                // Active un onglet (semestre 1, semestre 2, etc).
                var $link = $('#apsolu-manage-methods-title-tab-ul > li a[aria-controls="enrol-' + activeEnrolId + '"]');
                $link.addClass('active show');
                $link.attr('aria-selected', 'true');
                $('#' + $link.attr('aria-controls')).addClass('active show');

                // Gère les checkboxes permettant de faire des actions sur les utilisateurs sélectionnés.
                $('.select_options').change(function() {
                    let $checkboxes = $(this).parents(':eq(1)').find("input[type='checkbox']:checked");
                    if ($checkboxes.length > 0 && $(this).val() !== '') {
                        $(this).parents(':eq(1)').submit();
                    }
                });

                // Active ou désactive le menu déroulant permettant de faire des actions sur les utilisateurs sélectionnés.
                $('.apsolu-select-manage-users-input-checkbox').change(function() {
                    let $form = $(this).closest('.participants-form');
                    if ($form.find(".apsolu-select-manage-users-input-checkbox:checked").length == 0) {
                        $form.find('select[name="actions"],input[name="send_message"]').prop('disabled', true);
                    } else {
                        $form.find('select[name="actions"],input[name="send_message"]').prop('disabled', false);
                    }

                    let check = $(this).prop("checked");
                    if (!check) {
                        $form.find('.change-all').prop("checked", false);
                    } else if ($form.find('.apsolu-select-manage-users-input-checkbox:not(:checked)').length == 0) {
                        $form.find('.change-all').prop("checked", true);
                    }
                });

                // Désactive par défaut les menus déroulants permettant de faire des actions sur les utilisateurs sélectionnés.
                $('select[name="actions"]').prop('disabled', true);
            }
        };
    }
);
