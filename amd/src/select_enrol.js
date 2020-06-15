define(['jquery'], function($) {
    return {
        initialise : function(wwwroot){
            // Ajoute une div pour accueil les différents formulaires en overlay...
            $('body').append('<div id="apsolu-enrol-form"></div>');

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

                    $('#apsolu-enrol-form').popup('show');
                });
                return false;
            });

            // Lorsqu'on clique sur le bouton "s'inscrire/se désinscrire"...
            function set_edit_actions() {
                $('#apsolu-enrol-form #id_enrolbutton, #apsolu-enrol-form #id_unenrolbutton, #apsolu-enrol-form #id_editenrol').click(function(event) {
                    event.preventDefault();

                    // console.log('click sur un bouton');

                    var role = $('#apsolu-enrol-form form select[name=role] option:selected').val();
                    if (role == undefined) {
                       var role = $('#apsolu-enrol-form form input[name=role]').val();
                    }
                    var enrolid = $('#apsolu-enrol-form form input[name=enrolid]').val();
                    var sesskey = $('#apsolu-enrol-form form input[name=sesskey]').val();

                    if ($(this).attr('id') == 'id_unenrolbutton') {
                        var actions = {_qf__enrol_select_form: 1, sesskey: sesskey, enrolid: enrolid, unenrolbutton: 1};
                    } else if ($(this).attr('id') == 'id_editenrol') {
                        var actions = {_qf__enrol_select_form: 1, sesskey: sesskey, enrolid: enrolid, editenrol: 1};
                    } else {
                        var federation = $('#apsolu-enrol-form form select[name=federation] option:selected').val();
                        if (federation) {
                            var actions = {fullname: '1', enrolid: enrolid, role: role, federation: federation, enrolbutton: 1, _qf__enrol_select_form: 1, sesskey: sesskey};
                        } else {
                            var actions = {fullname: '1', enrolid: enrolid, role: role, enrolbutton: 1, _qf__enrol_select_form: 1, sesskey: sesskey};
                        }
                    }

                    // console.log(actions);
                    $.ajax({
                            url: wwwroot+"/enrol/select/ajax/enrol.php",
                            type: 'POST',
                            data: actions,
                            dataType: 'html'
                        })
                        .done(function(result) {
                            /*
                            console.log('form 2');
                            console.log(result);
                            $('#apsolu-enrol-form').html(result);
                            */
                            try {
                                var error = $.parseJSON(result);
                                $('#apsolu-enrol-form').html('<div class="alert alert-danger"><p>'+error.error+'</p></div>');
                            } catch(e) {
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
                            reload_ui(enrolid);
                        });

                    return false;
                });
            }

            function set_cancel_actions() {
                // Lorsqu'on clique sur le bouton "annuler"...
                $('.apsolu-cancel-a').click(function(event){
                    event.preventDefault();

                    // console.log('call cancel');

                    $('#apsolu-enrol-form').popup('hide');
                    // $('#apsolu-enrol-form').remove();
                });
            }

            function reload_ui(enrolid) {
                // TODO: modifier l'icone edit/add

                // On rafraichit le bloc "Choix restants".
                $.ajax({
                    url: wwwroot+"/enrol/select/ajax/reload_block_remaining.php",
                    dataType: 'html'
                })
                .done(function(result) {
                    $('#apsolu-select-remaining-ajax').html(result);

                    $('#apsolu-rules-summary').css('display', 'none');
                    // $('#apsolu-select-remaining-ajax').append('<p class="text-right"><a id="apsolu-rules-summary-a" href="#apsolu-rules-summary">Tableau récapitulatif</a></p>');

                    $('#apsolu-rules-summary_background, #apsolu-rules-summary_wrapper').remove();

                    $('#apsolu-rules-summary-a').click(function(evt){
                        evt.preventDefault();

                        $('#apsolu-rules-summary').css({backgroundColor: '#EEEEEE', padding: '.5em', cursor: 'default', maxWidth: '50%'});
                        $('#apsolu-rules-summary').popup('show');
                    });
                })
                .fail(function(result) {

                });

                // On rafraichit le bloc "Je souhaite m'inscrire à...".
                $.ajax({
                    url: wwwroot+"/enrol/select/ajax/reload_block_enrolments.php",
                    dataType: 'html'
                })
                .done(function(result) {
                    $('#apsolu-select-enrolments-ajax').html(result);
                })
                .fail(function(result) {

                });

                // On rafraichit la ligne "Places disponibles".
                $.ajax({
                    url: wwwroot+"/enrol/select/ajax/reload_column_left_places.php",
                    type: 'POST',
                    data: {enrolid: enrolid},
                    dataType: 'html'
                })
                .done(function(result) {
                    $('#apsolu-select-left-places-'+enrolid+'-ajax').replaceWith(result);
                })
                .fail(function(result) {

                });

                // On rafraichit l'icône "actions".
                var img_src = $('.apsolu-enrol-a[data-enrolid='+enrolid+'] img').attr('src');
                if ($('.apsolu-enrol-a[data-enrolid='+enrolid+']').hasClass('apsolu-enroled-a')) {
                    $('.apsolu-enrol-a[data-enrolid='+enrolid+']').removeClass('apsolu-enroled-a');
                    $('.apsolu-enrol-a[data-enrolid='+enrolid+']').addClass('apsolu-not-enroled-a');
                    $('.apsolu-enrol-a[data-enrolid='+enrolid+']').parent().parent().removeClass('info');
                    $('.apsolu-enrol-a[data-enrolid='+enrolid+'] img').attr('src', img_src.replace('i/completion-manual-y', 'i/completion-manual-n'));
                } else {
                    $('.apsolu-enrol-a[data-enrolid='+enrolid+']').removeClass('apsolu-not-enroled-a');
                    $('.apsolu-enrol-a[data-enrolid='+enrolid+']').addClass('apsolu-enroled-a');
                    $('.apsolu-enrol-a[data-enrolid='+enrolid+']').parent().parent().addClass('info');
                    $('.apsolu-enrol-a[data-enrolid='+enrolid+'] img').attr('src', img_src.replace('i/completion-manual-n', 'i/completion-manual-y'));
                }
            }
        }
    };
});
