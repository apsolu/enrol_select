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
 * @module     enrol_select/select_enrol
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {
    return {
        initialise: function(wwwroot) {
            // Ajoute une div pour accueil les différents formulaires en overlay...
            $('body').append('<div id="apsolu-enrol-form"></div>');

            // Permet de déplier/replier la liste des activités.
            var tooglebutton = document.getElementById('apsolu-toggle-activities');
            if (tooglebutton) {
                tooglebutton.addEventListener('click', function(evt) {
                    var i = 0;
                    var display = '';
                    var newclassname = '';
                    var action = evt.currentTarget.getAttribute('data-action');
                    switch (action) {
                        case 'show':
                            display = 'table-row';
                            action = 'hide';
                            newclassname = 'apsolu-collapsible';
                            break;
                        case 'hide':
                            display = 'none';
                            action = 'show';
                            newclassname = 'apsolu-expandable';
                            break;
                    }

                    // Affiche ou masque toutes les lignes du tableau des activités.
                    var rows = document.querySelectorAll('#apsolu-activities-table .apsolu-sports-tr-course');
                    for (i = 0; i < rows.length; i++) {
                        rows[i].style.display = display;
                    }

                    // Change le pictogramme représentant une flèche vers le bas ou vers la droite.
                    rows = document.getElementsByClassName('apsolu-sports-th-span');
                    for (i = 0; i < rows.length; i++) {
                        rows[i].className = "apsolu-sports-th-span " + newclassname;
                    }

                    // Renseigne l'action à réaliser lors du prochain appel.
                    evt.currentTarget.setAttribute('data-action', action);
                });
            }

            // Lorsqu'on clique sur le lien "s'inscrire/modifier"...
            $('.apsolu-enrol-a').click(function(event) {
                event.preventDefault();

                var requesturl = $(this).attr('href');
                requesturl = requesturl.replace('/enrol/select/overview/enrol.php', '/enrol/select/ajax/enrol.php');

                // Affiche le formulaire.
                $.ajax({
                    url: requesturl,
                    type: 'GET',
                    dataType: 'html'
                })
                .done(function(result) {
                    try {
                        var error = $.parseJSON(result);
                        $('#apsolu-enrol-form').html('<div class="alert alert-danger"><p>' + error.error + '</p></div>');
                    } catch (e) {
                        $('#apsolu-enrol-form').html(result);
                    }
                })/*
                .fail(function(result) {
                    console.log('FAIL 1');
                    var error = $.parseJSON(result);
                    $('#apsolu-enrol-form').html('<div class="alert alert-danger"><p>'+error.error+'</p></div>');
                })*/
                .always(function() {
                    set_edit_actions();
                    set_cancel_actions();
                    set_policy_actions();

                    $('#apsolu-enrol-form').popup('show');
                });
                return false;
            });

            /**
             * Fonction appelée lorsqu'on clique sur le bouton "s'inscrire/se désinscrire"...
             */
            function set_edit_actions() {
                $('#apsolu-enrol-form #id_enrolbutton, #apsolu-enrol-form #id_unenrolbutton, #apsolu-enrol-form #id_editenrol').
                    click(function(event) {
                    event.preventDefault();

                    var role = $('#apsolu-enrol-form form select[name=role] option:selected').val();
                    if (role == undefined) {
                        role = $('#apsolu-enrol-form form input[name=role]').val();
                    }
                    var enrolid = $('#apsolu-enrol-form form input[name=enrolid]').val();
                    var sesskey = $('#apsolu-enrol-form form input[name=sesskey]').val();

                    var actions;
                    if ($(this).attr('id') == 'id_unenrolbutton') {
                        actions = {_qf__enrol_select_form: 1, sesskey: sesskey, enrolid: enrolid, unenrolbutton: 1};
                    } else if ($(this).attr('id') == 'id_editenrol') {
                        actions = {_qf__enrol_select_form: 1, sesskey: sesskey, enrolid: enrolid, editenrol: 1, policy: 1};
                    } else {
                        var fullname = $('#apsolu-enrol-form form input[name=fullname]').val();
                        var federation = $('#apsolu-enrol-form form select[name=federation] option:selected').val();
                        if (federation) {
                            actions = {
                                fullname: fullname,
                                enrolid: enrolid,
                                role: role,
                                federation: federation,
                                enrolbutton: 1,
                                _qf__enrol_select_form: 1,
                                sesskey: sesskey,
                                policy: 1
                            };
                        } else {
                            actions = {
                                fullname: fullname,
                                enrolid: enrolid,
                                role: role,
                                enrolbutton: 1,
                                _qf__enrol_select_form: 1,
                                sesskey: sesskey,
                                policy: 1
                            };
                        }
                    }

                    $.ajax({
                            url: wwwroot + "/enrol/select/ajax/enrol.php",
                            type: 'POST',
                            data: actions,
                            dataType: 'html'
                        })
                        .done(function(result) {
                            try {
                                var error = $.parseJSON(result);
                                $('#apsolu-enrol-form').html('<div class="alert alert-danger"><p>' + error.error + '</p></div>');
                            } catch (e) {
                                $('#apsolu-enrol-form').html(result);
                            }
                        })/*
                        .fail(function(result) {
                            console.log('FAIL');
                            var error = $.parseJSON(result);
                            $('#apsolu-enrol-form').html('<div class="alert alert-danger"><p>'+error.error+'</p></div>');
                        })*/
                        .always(function() {
                            set_edit_actions();
                            set_cancel_actions();
                            set_policy_actions();
                            reload_ui(enrolid);
                        });

                    return false;
                });
            }

            /**
             * Fonction appelée pour définir les actions sur le bouton d'annulation.
             */
            function set_cancel_actions() {
                // Lorsqu'on clique sur le bouton "annuler"...
                $('.apsolu-cancel-a').click(function(event) {
                    event.preventDefault();

                    $('#apsolu-enrol-form').popup('hide');
                });
            }

            /**
             * Fonction appelée pour désactiver le bouton d'inscription si la case des recommandations médicales n'est pas cochée.
             */
            function set_policy_actions() {
                var policy = $('#apsolu-enrol-form form input[name=policy]');

                // Si la validation des recommandations médicales sont activées.
                if (policy.length) {
                    // Désactive le bouton d'inscription.
                    $('#apsolu-enrol-form form input[name=enrolbutton]').prop('disabled', true);

                    // Active/désactive le bouton d'inscription lorsqu'on coche/décoche la case des recommandations médicales.
                    policy.change(function() {
                        if ($(this).is(':checked')) {
                            $('#apsolu-enrol-form form input[name=enrolbutton]').prop('disabled', false);
                        } else {
                            $('#apsolu-enrol-form form input[name=enrolbutton]').prop('disabled', true);
                        }
                    });
                }
            }

            /**
             * Fonction appelée pour recharger l'interace graphique.
             *
             * @param {string} enrolid Identifiant numérique de la méthode d'inscription.
             */
            function reload_ui(enrolid) {
                // TODO: modifier l'icone edit/add

                // On rafraichit le bloc "Choix restants".
                $.ajax({
                    url: wwwroot + "/enrol/select/ajax/reload_block_remaining.php",
                    dataType: 'html'
                })
                .done(function(result) {
                    $('#apsolu-select-remaining-ajax').html(result);

                    $('#apsolu-rules-summary').css('display', 'none');

                    $('#apsolu-rules-summary_background, #apsolu-rules-summary_wrapper').remove();

                    $('#apsolu-rules-summary-a').click(function(evt) {
                        evt.preventDefault();

                        $('#apsolu-rules-summary').css({
                            backgroundColor: '#EEEEEE',
                            padding: '.5em',
                            cursor: 'default',
                            maxWidth: '50%'
                        });
                        $('#apsolu-rules-summary').popup('show');
                    });
                })
                .fail(function() {
                    // TODO.
                });

                // On rafraichit le bloc "Je souhaite m'inscrire à...".
                $.ajax({
                    url: wwwroot + "/enrol/select/ajax/reload_block_enrolments.php",
                    dataType: 'html'
                })
                .done(function(result) {
                    $('#apsolu-select-enrolments-ajax').html(result);
                })
                .fail(function() {
                    // TODO.
                });

                // On rafraichit la ligne "Places disponibles".
                $.ajax({
                    url: wwwroot + "/enrol/select/ajax/reload_column_left_places.php",
                    type: 'POST',
                    data: {enrolid: enrolid},
                    dataType: 'html'
                })
                .done(function(result) {
                    $('#apsolu-select-left-places-' + enrolid + '-ajax').replaceWith(result);
                })
                .fail(function() {
                    // TODO.
                });

                // On rafraichit l'icône "actions".
                var img_src = $('.apsolu-enrol-a[data-enrolid=' + enrolid + '] img').attr('src');
                if ($('.apsolu-enrol-a[data-enrolid=' + enrolid + ']').hasClass('apsolu-enroled-a')) {
                    $('.apsolu-enrol-a[data-enrolid=' + enrolid + ']').removeClass('apsolu-enroled-a');
                    $('.apsolu-enrol-a[data-enrolid=' + enrolid + ']').addClass('apsolu-not-enroled-a');
                    $('.apsolu-enrol-a[data-enrolid=' + enrolid + ']').parent().parent().removeClass('info');
                    $('.apsolu-enrol-a[data-enrolid=' + enrolid + '] img')
                        .attr('src', img_src.replace('i/completion-manual-y', 'i/completion-manual-n'));
                } else {
                    $('.apsolu-enrol-a[data-enrolid=' + enrolid + ']').removeClass('apsolu-not-enroled-a');
                    $('.apsolu-enrol-a[data-enrolid=' + enrolid + ']').addClass('apsolu-enroled-a');
                    $('.apsolu-enrol-a[data-enrolid=' + enrolid + ']').parent().parent().addClass('info');
                    $('.apsolu-enrol-a[data-enrolid=' + enrolid + '] img')
                        .attr('src', img_src.replace('i/completion-manual-n', 'i/completion-manual-y'));
                }
            }
        }
    };
});
