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
