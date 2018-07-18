define(['jquery', 'local_apsolu/jquery.tablesorter'], function($) {
    return {
        initialise : function(semester2){
            // Gère les onglets des méthodes d'inscription.

            // Créer une barre de navigation en haut du conteneur.
            $('#apsolu-manage-users').prepend('<ul id="apsolu-manage-methods-title-tab-ul" class="nav nav-tabs"></ul>');

            // Ajoute les titres de chaque méthode dans la barre de navigation.
            $('.apsolu-manage-users-h3').each(function(){
                $('#apsolu-manage-methods-title-tab-ul').append('<li class="apsolu-manage-users-tab-li"><a>'+$(this).text()+'</a></li>');
                $(this).remove();
            });

            // Détermine quel onglet doit être actif au chargement de la page (S1 ou S2).
            if (semester2 == true) {
                index = Math.floor($('#apsolu-manage-methods-title-tab-ul > li').length/2);
            } else {
                index = 0;
            }

            // Active le premier onglet de méthodes.
            $('#apsolu-manage-methods-title-tab-ul > li').eq(index).addClass('active');
            // Affiche les conteneurs (sauf le premier).
            $('#apsolu-manage-methods-lists-tab-ul > li:not(:eq('+index+'))').css('display', 'none');

            // Gère la navigation par onglet.
            $('#apsolu-manage-methods-title-tab-ul > li').click(function(){
                var current_tab = $('#apsolu-manage-methods-title-tab-ul li.active');
                var index = $('#apsolu-manage-methods-title-tab-ul li').index(current_tab);

                current_tab.removeClass('active');
                $('#apsolu-manage-methods-lists-tab-ul > li:eq('+index+')').css('display', 'none');

                $(this).addClass('active');
                index = $('#apsolu-manage-methods-title-tab-ul > li').index($(this));
                $('#apsolu-manage-methods-lists-tab-ul > li').eq(index).css('display', 'block');
            });

            // Gère les onglets des listes d'inscription (dans les méthodes d'inscription).
            $('.apsolu-manage-users').each(function(){
                // Ajoute le menu contenant les différentes listes d'inscription.
                $(this).parent().prepend('<ul class="apsolu-manage-users-tab-ul nav nav-tabs"></ul>');
                $(this).children('.apsolu-manage-users-h4').each(function(){
                    $(this).parent().prev().prev().append('<li class="apsolu-manage-users-tab-li"><a>'+$(this).text()+'</a></li>');
                    $(this).remove();
                });

                // Ajoute "active" sur le premier onglet.
                $(this).parent().children('.apsolu-manage-users-tab-ul').children().eq(0).addClass('active');
                // Masque toutes les listes (sauf la première).
                $(this).children('.apsolu-manage-users-content-div:gt(0)').css('display', 'none');

                // Gère la navigation par onglet.
                $('.apsolu-manage-users-tab-ul > li').click(function(){
                    var current_tab = $(this).parent().children('li.active');
                    var index = $(this).parent().children().index(current_tab);

                    current_tab.removeClass('active');
                    $(this).parent().parent().children('.apsolu-manage-users').children('.apsolu-manage-users-content-div').eq(index).css('display', 'none');

                    $(this).addClass('active');
                    index = $(this).parent().children().index($(this));

                    $(this).parent().parent().children('.apsolu-manage-users').children('.apsolu-manage-users-content-div').eq(index).css('display', 'block');
                });
            });

            // Gère les checkboxes... blabla !
            $('.select_options').change(function(){
                var form = $(this).parents(':eq(5)');

                var checkboxes = $(this).parents(':eq(1)').find("input[type='checkbox']:checked");
                if (checkboxes.length > 0 && $(this).val() !== '') {
                    $(this).parents(':eq(1)').submit();
                }
            });

            $('.checkall').click(function(){
                var form = $(this).parents(':eq(5)');
                form.find("input[type='checkbox']").prop('checked', true);
                form.find('select[name="actions"]').prop('disabled', false);
            });

            $('.uncheckall').click(function(){
                var form = $(this).parents(':eq(5)');
                form.find("input[type='checkbox']").prop('checked', false);
                form.find('select[name="actions"]').prop('disabled', true);
            });

            $('.apsolu-select-manage-users-input-checkbox').change(function(){
                var form = $(this).parents(':eq(5)');
                if (form.find(".apsolu-select-manage-users-input-checkbox:checked").length == 0) {
                    form.find('select[name="actions"],input[name="send_message"]').prop('disabled', true);
                } else {
                    form.find('select[name="actions"],input[name="send_message"]').prop('disabled', false);
                }
            });

            $('select[name="actions"]').prop('disabled', true);

            // ajoute la possiblité de trier les tableaux
            $(".table-sortable").tablesorter({
                headers: {
                    0: {sorter: false},
                    1: {sorter: false}
                    // 6: {sorter: false}
                }
            });
        }
    };
});
