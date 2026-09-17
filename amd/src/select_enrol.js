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
 * @copyright  2016 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/notification', 'core/url', 'core/str', 'local_apsolu/sort', 'enrol_select/jquery.popupoverlay'],
    function($, Notification, Url, Str, SortTable) {
    "use strict";

    /**
     * Affiche les catégories d'activités si au moins un de ses cours est visible (non filtré).
     *
     * @param {Object} table
     * @return {void}
     */
    var toggleFilteredActivities = function(table) {
        // Pour chaque activité (= catégorie de créneaux).
        $(table).find('.apsolu-sports-tr-activity').each(function() {
            // Chercher si au moins une ligne de données associée N'A PAS la classe .filtered
            let hasVisibleCourses = $(this)
                .nextUntil('.apsolu-sports-tr-activity', '.apsolu-sports-tr-course')
                .not('.filtered').length > 0;

            // On enlève ou on laisse la classe "filtered".
            $(this).toggleClass('filtered', !hasVisibleCourses);
        });
    };

     /**
     * Déplie ou replie toutes les activités.
     *
     * @param {Event} event event.data.display : true pour déplier les créneaux, false pour les replier.
     * @return {void}
     */
    var toggleAllActivities = function(event) {

        // Paramètre display = true => déplier.
        let display = event.data.display;

        // Pour chaque activité.
        $(this).closest('.apsolu-activities-table').find('.apsolu-sports-tr-activity').each(function() {
            $(this).nextUntil(".apsolu-sports-tr-activity", '.apsolu-sports-tr-course:not(.filtered)').toggle(display);

            // On affiche l'icône "Replier" pour l'activité si on vient de tout déplier, et inversement.
            $(this).find('.apsolu-sports-th-span')
                .toggleClass('apsolu-collapsible', display)
                .toggleClass('apsolu-expandable', !display);
        });

        // On masque l'icône actuelle et on affiche l'icône de l'action inverse.
        $(this).parent().find('.apsolu-expand-all').toggleClass('apsolu-toggle-all-hidden', display);
        $(this).parent().find('.apsolu-collapse-all').toggleClass('apsolu-toggle-all-hidden', !display);

    };

    /**
     * Affiche ou masque le contenu d'une activité au clic sur l'icône plier/déplier de l'activité.
     *
     * @return {void}
     */
    var toggleActivityCourses = function() {
        // On change le symbole (déplier / replier).
        $(this).toggleClass('apsolu-expandable');
        $(this).toggleClass('apsolu-collapsible');

        // Liste des créneaux qui sont situés sous le bandeau de l'activité.
        $(this).closest('tr.apsolu-sports-tr-activity')
            .nextUntil(".apsolu-sports-tr-activity", '.apsolu-sports-tr:not(".filtered")')
            .toggle("slow", "swing").promise().done(() => { // Attendre la fin des animations.
                // Est-ce que l'élément qui a été replié/déplié était le dernier élément repliable/dépliable visible ?
                // Si oui, on déclenche l'action du bouton 'tout déplier / tout replier' de sorte qu'il s'accorde à l'état
                // actuel des activités visibles, et que les autres activités non visibles soient également dans le même état.

                // Si l'élément est replié, on cherche les éléments avec la classe inverse.
                let opposite = $(this).hasClass('apsolu-collapsible') ? '.apsolu-expandable' : '.apsolu-collapsible';
                let toggleAll = $(this).hasClass('apsolu-collapsible') ? '.apsolu-expand-all' : '.apsolu-collapse-all';

                let hasVisibleOpposite = $(this).closest('.apsolu-activities-table')
                    .find('.apsolu-sports-tr-activity:not(".filtered")').find(opposite).length > 0;
                // Toutes les activités visibles sont dans le même état (déplié / replié).
                if(!hasVisibleOpposite) {
                    // On inverse le bouton tout déplier / tout replier, et on déclenche son action pour s'assurer que
                    // toutes les activités sont dans le même état.
                    $(this).closest('.apsolu-activities-table').find(toggleAll).trigger('click');
                }

            });

    };

    /**
     * Ajoute un message d'erreur dans un bandeau et positionne le scroll sur la notification.
     *
     * @param {string} message
     * @return {void}
     */
    var addErrorNotification = function(message) {
        Notification.addNotification({
            message: message,
            type: 'error'
        });
        var $notifRegion = $('#user-notifications').first();
        if ($notifRegion.length) {
            $('html, body').animate({
                scrollTop: 200 // Scroll en haut de la page pour afficher les notifications.
            }, 300);
        }
    };

    return {
        initialise: function(wwwroot, options = {}) {
            // Ajoute une div pour accueil les différents formulaires en overlay...
            $('body').append('<div id="apsolu-enrol-form"></div>');

            // Action exécutée à chaque clic sur les flèches à gauche du nom de l'activité.
            $(".apsolu-sports-th-span").on('click', toggleActivityCourses);

            // Après l'initialisation de la table avec tablesorter.
            $(".apsolu-activities-table").one('tablesorter-initialized', function() {

                // On enlève le filtre dans la première cellule de l'en-tête (Actions).
                $(this).find('.tablesorter-filter-row td[data-column="0"] input').remove();

                // Ajouter un bouton Plier / Déplier dans la première cellule de la ligne de filtre (Actions).
                let collapseAll = $('<button class="apsolu-collapse-all apsolu-toggle-all" title="Tout replier" '
                    + 'data-bs-toggle="tooltip" data-bs-placement="right" data-bs-custom-class="overview-tooltip">')
                    .on('click', {display: false}, toggleAllActivities);
                let expandAll = $('<button class="apsolu-expand-all apsolu-toggle-all apsolu-toggle-all-hidden" '
                    + 'title="Tout déplier" data-bs-toggle="tooltip" data-bs-placement="right" '
                    +'data-bs-custom-class="overview-tooltip">').on('click', {display: true}, toggleAllActivities);
                $(this).find('.tablesorter-filter-row td[data-column="0"]').append(collapseAll).append(expandAll);

                // Certains filtres peuvent être préremplis par la librairie (via les cookies).
                let filters = $(this).find('.tablesorter-filter').filter(function() {
                    return $(this).val() != "";
                });

                // S'il n'y a aucun filtre déjà présent, on replie toutes les activités.
                if(filters.length == 0) {
                    $(collapseAll).trigger('click');
                }

                // Quand tout est fait seulement, on affiche le tableau.
                $(this).removeClass('apsolu-table-loading').css({
                    'visibility': 'visible',
                    'opacity': 1
                });
            });

            // Après chaque filtre, on rend visible les activités qui ont au moins 1 créneau non filtré.
            $(".apsolu-activities-table").on('filterEnd', function() {
                // Note : un premier filtre est effectué au chargement si des valeurs sont présentes dans les cookies.

                toggleFilteredActivities(this);

                // On déplie toutes les activités (même si aucune n'est visible en raison du filtre appliqué).
                $(this).find('.apsolu-expand-all').trigger('click');

                // On met à jour la section "Réinitialiser les filtres", avec la liste des filtres actifs.
                let noActiveFilter = true;
                var filterResults = $(this).closest('.apsolu-format-activities').find('.filter-results-filters');

                $(this).find('.tablesorter-filter').each(function() {
                    let filterid = $(this).parent().data('column');
                    let filterActive = $(filterResults).find(".filter-active[data-filterid='" + filterid + "']");
                    if($(this).val() != "") {
                        noActiveFilter = false;
                        let filtername = $(this).closest('thead').find('.tablesorter-headerRow .tablesorter-header[data-column="'
                            + filterid + '"] .tablesorter-header-inner').html();
                        let filtervalue = '« ' + $(this).val() + ' »';
                        // Si le filtre n'existe pas encore on l'ajoute.
                        if(filterActive.length == 0) {
                            filterActive = $('<p class="filter-active" data-filterid="' + filterid + '">');
                            $(filterActive).append('<span class="filter-active-name">' + filtername + '</span>')
                                .append('<span class="filter-active-value">');
                            $(filterResults).append(filterActive);
                        }
                        // La valeur du filtre a changé ?
                        if($(filterActive).find('.filter-active-value').html() != filtervalue) {
                            $(filterActive).find('.filter-active-value').text('« ' + $(this).val() + ' »');
                        }
                    } else if(filterActive.length != 0) {
                        $(filterActive).remove();
                    }
                });

                // On désactive le bouton Réinitialiser les filtres si aucun filtre actif,
                $(this).closest('.apsolu-format-activities').find('.apsolu-reset-table-filters').prop('disabled', noActiveFilter);
                // On affiche le texte correspondant au résultat du filtre.
                $(this).closest('.apsolu-format-activities').find('.filter-results-active').toggleClass('d-none', noActiveFilter);
                $(this).closest('.apsolu-format-activities').find('.filter-results-inactive')
                    .toggleClass('d-none', !noActiveFilter);

                // On met à jour le nombre de résultats affichés / résultats disponibles.
                if(!noActiveFilter) {
                    let countResults = $(this).find('.apsolu-sports-tr-course:not(".filtered")').length;
                    Str.get_string('n_results','enrol_select', countResults).then(message => {
                        $(this).closest('.apsolu-format-activities').find('.filter-count-results').text(message);
                    }).fail(Notification.exception);

                    // Animations pour mettre en évidence la prise en compte du filtre.
                    let $a = $(this).closest('.apsolu-format-activities').find('.filter-results-active');
                    $a.addClass('highlight'); // Le nombre de résultat filtrés est brièvement animé.
                    void $a[0].offsetWidth; // force reflow pour que le retrait déclenche bien la transition.
                    $a.removeClass('highlight');
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

            // Lorsqu'on clique sur le lien "s'inscrire/modifier"...
            $('.apsolu-enrol-a').click(function(event) {
                event.preventDefault();

                // Affiche le formulaire.
                $.ajax({
                    url: wwwroot + '/enrol/select/ajax/enrol.php',
                    data: { enrolid: $(this).data('enrolid'), ajax: 1},
                    type: 'GET',
                    dataType: 'html'
                })
                .done(function(result) {
                    var data = JSON.parse(result);
                    if(data.success) {
                        $('#apsolu-enrol-form').html(data.html);
                        set_edit_actions();
                        set_cancel_actions();
                        set_policy_actions();

                        $('#apsolu-enrol-form').popup('show');
                    } else {
                        addErrorNotification(data.error);
                    }
                })
                .fail(function() {
                    Notification.exception(new Error('Echec de l’appel à la ressource serveur.'));
                });
                // .always(function() {
                // });
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
                        actions = {_qf__enrol_select_form: 1, sesskey: sesskey, enrolid: enrolid, unenrolbutton: 1, ajax: 1};
                    } else if ($(this).attr('id') == 'id_editenrol') {
                        actions = {_qf__enrol_select_form: 1, sesskey: sesskey, enrolid: enrolid, editenrol: 1, policy: 0, ajax: 1};
                    } else {
                        var fullname = $('#apsolu-enrol-form form input[name=fullname]').val();
                        actions = {
                                fullname: fullname,
                                enrolid: enrolid,
                                role: role,
                                enrolbutton: 1,
                                _qf__enrol_select_form: 1,
                                sesskey: sesskey,
                                policy: 0,
                                ajax: 1
                            };

                        var federation = $('#apsolu-enrol-form form select[name=federation] option:selected').val();
                        if (federation) {
                           actions.federation = federation;
                        }
                    }

                    $.ajax({
                        url: wwwroot + "/enrol/select/ajax/enrol.php",
                        type: 'POST',
                        data: actions,
                        dataType: 'html'
                    })
                    .done(function(result) {
                        var data = JSON.parse(result);
                        if(data.success) {
                            $('#apsolu-enrol-form').html(data.html);
                            set_edit_actions();
                            set_cancel_actions();
                            set_policy_actions();
                            // Ne pas charger les modifications de l'ui lors du clic sur 'modifier son inscription'.
                            if(!actions.editenrol) {
                                reload_ui(enrolid, data.unenrol);
                            }
                        } else {
                            addErrorNotification(data.error);
                            $('#apsolu-enrol-form').popup('hide');
                        }
                    })
                    .fail(function() {
                        Notification.exception(new Error('Echec de l’appel à la ressource serveur.'));
                    });
                    // .always(function() {

                    // });

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
             * @param {bool} unenrol si l'utilisateur doit être désinscrit ou inscrit.
             */
            function reload_ui(enrolid, unenrol) {
                // TODO: modifier l'icone edit/add

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
                let icon = $('.apsolu-enrol-a[data-enrolid=' + enrolid + ']');
                let img = unenrol ? 'completion-manual-n' : 'completion-manual-y';
                $(icon).toggleClass('apsolu-enroled-a', !unenrol)
                    .toggleClass('apsolu-not-enroled-a', unenrol)
                    .find('img').attr('src', Url.imageUrl('i/' + img, 'core'))
                    .closest('.apsolu-sports-tr-course').toggleClass('info', !unenrol);
            }

            SortTable.initialise(options);
        }
    };
});
