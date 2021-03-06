define(['jquery', 'enrol_select/notify'], function($) {
    return {
        initialise : function(wwwroot){
            $("input[name='enrolin']").css("display", "none");

            $("select[name=roleid]").change(function(){
                // Get form values.
                var courseid = $(this).attr('id').substring(9);
                var roleid = $(this).children('option:selected')[0].value;
                var enrolid = $(this).parent().find("input[name='enrolid']")[0].value;

                // Set 'apsolu-loading' css class to information cell.
                var information = $(this).parent().parent().next(".apsolu-activities-information-td");
                information.addClass('apsolu-loading');

                var main_list = $(this).parent().parent().next().next(".apsolu-activities-main-list-td");
                // main_list.addClass('apsolu-loading');

                var wait_list = $(this).parent().parent().next().next().next(".apsolu-activities-wait-list-td");
                // wait_list.addClass('apsolu-loading');

                var ajax = $.ajax(
                    {
                        url: wwwroot+"/enrol/select/ajax/subscribe.php",
                        type: 'POST',
                        data: {courseid: courseid, roleid: roleid, enrolid: enrolid},
                        dataType: 'json'
                    })
                    .done(function(result) {
                        switch(result.status_code) {
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
