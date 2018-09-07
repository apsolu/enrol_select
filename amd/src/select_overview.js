define(["jquery", "enrol_select/jquery.popupoverlay"], function($) {
    return {
        initialise : function(){
            function apsolu_overview_set_expandable_icons(th_element){
                if ($(th_element).hasClass("apsolu-expandable")) {
                    $(th_element).attr("class", "apsolu-collapsible");
                } else if ($(th_element).hasClass("apsolu-collapsible")) {
                    $(th_element).attr("class", "apsolu-expandable");
                } else if ($(th_element).parent().next().css("display") == "none") {
                    $(th_element).attr("class", "apsolu-expandable");
                } else {
                    $(th_element).attr("class", "apsolu-collapsible");
                }
            }

            $(".apsolu-sports-th-span").click(function(){
                $(this).parent().parent().nextUntil(".apsolu-sports-tr").toggle("slow", "swing");
                apsolu_overview_set_expandable_icons(this);
            });

            // si les filtres ne sont pas activés...
            if ($("#apsolu-wishes-filters .select2-selection__clear").length == 0) {
                $("#apsolu-activities-table tbody tr:not(.apsolu-sports-tr)").css("display", "none");
            }

            $(".apsolu-sports-th-span").each(function(){
                apsolu_overview_set_expandable_icons(this)
            });

            // Overlay : http://dev.vast.com/jquery-popup-overlay/.
            // Affiche la description d'un sport.
            $('.apsolu-sports-description-info-img').click(function(){
                var id = $(this).data('popup');
                var description = $('#'+id);

                description.css({backgroundColor: '#EEEEEE', padding: '.5em', cursor: 'default', maxWidth: '50%', textAlign: 'justify'});
                description.popup('show');
            });

            // Masque le tableau récapitulatif des voeux.
            $('#apsolu-rules-summary').css('display', 'none');
            // $('#apsolu-select-remaining-ajax').append('<p class="text-right"><a id="apsolu-rules-summary-a" href="#apsolu-rules-summary">Tableau récapitulatif</a></p>');

            $('#apsolu-rules-summary-a').click(function(evt){
                evt.preventDefault();

                $('#apsolu-rules-summary').css({backgroundColor: '#EEEEEE', padding: '.5em', cursor: 'default', maxWidth: '50%'});
                $('#apsolu-rules-summary').popup('show');
            });
        }
    };
});
