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
