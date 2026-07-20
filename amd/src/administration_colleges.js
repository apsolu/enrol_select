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
 * Module javascript pour gérer la page d'administration des populations..
 *
 * @todo       Description à compléter.
 *
 * @module     enrol_select/administration_colleges
 * @copyright  2026 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core_form/modalform', 'core/str', 'core/modal_events', 'core/notification'],
    function($, ModalForm, Str, ModalEvents, Notification) {

    /**
     * Affiche la modal du formulaire pour les règles programmées de gestion des voeux.
     * @param {Object} modalForm la modal (formulaire dynamique).
     * @param {boolean} addDeleteBtn témoin pour inclure ou non un bouton "Supprimer" dans le footer.
     */
    function showModalForm(modalForm, addDeleteBtn) {
        modalForm.show();

        // Après le chargement.
        $(document).one(ModalEvents.bodyRendered, (e) => {
            const modalElement = e.target;
            modalElement.id = 'modal-college-programmation';

            // Ajout de l'icône calendrier devant le titre de la modal.
            const title = $(modalElement).find('div.modal-header h5');
            $(title).prepend('<i class="icon fa-regular fa-calendar fa-fw" me-3 />');

            // Le widget du calendrier (date picker) doit être replacé au premier plan.
            const modalZ = $(modalElement).css('z-index');
            const calendar = $(modalElement).closest('body').find('#dateselector-calendar-panel');
            $(calendar).css('z-index', Number(modalZ) + 10);

            const population = $(modalElement).find('div.modal-body h5.info strong').first().text(); // Nom de la population.

            // Ajout d'un bouton "Supprimer" dans le footer (mode édition uniquement).
            if(addDeleteBtn == true) {
                const footer = $(modalElement).find('div.modal-footer');
                Str.get_strings([
                    {key: 'delete_rule', component: 'enrol_select'},
                    {key: 'delete', component: 'core'}
                ]).then(([question, deleteLabel]) => {

                    const deletebtn = $('<button type="button" class="btn btn-danger" data-action="delete">').text(deleteLabel);
                    // Au clic sur le bouton : message de confirmation.
                    $(deletebtn).on("click", async (e) => {
                        const form = $(e.currentTarget).closest('.modal-content').find('form');

                        try {
                            await Notification.deleteCancelPromise(population, question, deleteLabel, {
                                triggerElement: e.target,
                            });

                            // Suppression confirmée.
                            // Modifie la valeur du champ caché faisant office de témoin pour activer la suppression.
                            form.find('[name="deletesubmit"]').val(1);
                            form.get(0).requestSubmit(); // Soumet le formulaire avec cette valeur.
                        } catch (error) {
                            // Suppression annulée.
                        }
                    });
                    $(footer).prepend(deletebtn);
                });
            }

        });

        //Après une soumission réussie.
        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, () => {
            window.location.reload();
        });
    }


    return {
        initialise : function(options) {

            const form = "enrol_select\\form\\manage_college_form";

            // Bouton 'Ajouter une règle' : déclenche l'apparition du formulaire pour créer une nouvelle règle plannifiée.
            $('#add_rule').click(function(event) {
                event.preventDefault();

                const modalForm = new ModalForm({
                    formClass: form,
                    args: {contextid:options.contextid},
                    modalConfig: {
                        title: Str.get_string('add_rule', 'enrol_select'),
                        large: true,
                        removeOnClose: true,
                        buttons: {save: Str.get_string('add')},
                    },
                });

                showModalForm(modalForm, false);

            });

            // Bouton 'Modifier la règle' : déclenche l'apparition du formulaire pour modifier ou supprimer une règle existante.
            $('.edit_rule').click(function(event) {
                event.preventDefault();
                var taskid = $(this).data('taskid');

                const modalForm = new ModalForm({
                    formClass: form,
                    args: {taskid: taskid, contextid:options.contextid},
                    modalConfig: {
                        title: Str.get_string('edit_rule', 'enrol_select'),
                        large: true,
                        removeOnClose: true,
                        buttons: {save: Str.get_string('save')},
                    },
                });

                showModalForm(modalForm, true);

            });

            // Après soumission d'un formulaire (et rechargement de la page) :
            // Focus sur la notification success ou warning pour qu'elle soit toujours visible.
            const notification = $('#user-notifications .alert').first();

            if ($(notification).length) {
                // On veut que la notification soit positionnée à environ 1/3 de la hauteur de la fenêtre.
                const targetPosition = $(notification).offset().top - ($(window).height() / 3);

                $('html, body').animate({
                    scrollTop: targetPosition
                }, 200);
            }
        }
    };
});