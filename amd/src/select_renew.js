define(["jquery"], function($) {
    return {
        initialise : function(){
            function apsolu_renew_set_radio_pairing(input) {
                var name = input.attr("name");
                var index = name.substr(5);

                if (input.attr("value") === "1") {
                    $("#apsolu-enrol-select-renew-table input[name='role"+index+"']").prop("disabled", false);
                    input.parent().next().removeClass("apsolu-inactive-td");
                } else if (input.attr("value") === "0") {
                    $("#apsolu-enrol-select-renew-table input[name='role"+index+"']").prop("disabled", true);
                    input.parent().next().addClass("apsolu-inactive-td");
                }   
            }

            $("#apsolu-enrol-select-renew-table input[name^='renew']").each(function() {
                if ($(this).prop("checked")) {
                    apsolu_renew_set_radio_pairing($(this));
                }

                $(this).change(function() {
                    apsolu_renew_set_radio_pairing($(this));
                });
            });
        }
    };
});
