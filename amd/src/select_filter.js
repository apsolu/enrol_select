define(['jquery', 'enrol_select/select2'], function($) {
    return {
        initialise : function(){

            // Filtre le tableau en fonction des filtres utilisés.
            function apsolu_filter(){
                var rows = $("#apsolu-activities-table tbody tr");

                rows.css("display", "table-row");

                var selections = $("#apsolu-wishes-filters .select2-selection__clear");
                // console.log('selection__clear: '+selections.length);
                if (selections.length != 0) {
                    rows.each(function(){
                        var row = $(this);
                        var match = true;

                        // Pour chaque filtre
                        selections.each(function(){
                            if (match == false) {
                                return;
                            }

                            var found = false;
                            var choices = $(this).parent().find(".select2-selection__choice");

                            // pour chaque valeur de filtres
                            choices.each(function(){
                                if (found == false) {
                                    var text = $(this).attr("title");
                                    var colname = $(this).parents(":eq(3)").prev().attr("data-column-name");
                                    var th = $('#apsolu-activities-table th[data-column='+colname+']');
                                    var colnum = $("#apsolu-activities-table tr th").index(th);

                                    /*
                                    console.log(colname+" "+colnum);
                                    console.log(row.children().length);
                                    */

                                    if (row.children().length >= colnum) {
                                        if (colname == 'role') {
                                            // console.log(row.children().eq(colnum).html());
                                            found = row.children().eq(colnum).html().indexOf(text) > -1;
                                        } else if (colname == 'starttime' || colname == 'endtime') {
                                            found = row.children().eq(colnum).text().substr(0, 3) == text.substr(0, 2)+':';
                                        } else if (colname == 'sport' || colname == 'location') {
                                            // console.log('area: '+row.children().eq(colnum).children().eq(0).text()+'=='+text);
                                            found = row.children().eq(colnum).children().eq(0).text() == text;
                                        } else {
                                            found = row.children().eq(colnum).text() == text;
                                        }
                                    }
                                }
                            });

                            if (found == false) {
                                match = false;
                            }
                        });

                        if (match == false) {
                            $(this).css("display", "none");
                        }
                    });
                } else {
                    $("#apsolu-activities-table tbody tr th").each(function(){
                        $(this).css("display", "table-cell");

                        if ($(this).hasClass("apsolu-expandable")) {
                            $(this).parent().nextUntil(".apsolu-sports-tr").css("display", "none");
                        } else {
                            $(this).parent().nextUntil(".apsolu-sports-tr").css("display", "table-row");
                        }
                    });
                }
            }

            function apsolu_filter_set_expandable_icons(){
                if ($("#apsolu-wishes-filters legend").hasClass("apsolu-expandable")) {
                    $("#apsolu-wishes-filters legend").attr("class", "apsolu-collapsible");
                } else if ($("#apsolu-wishes-filters legend").hasClass("apsolu-collapsible")) {
                    $("#apsolu-wishes-filters legend").attr("class", "apsolu-expandable");
                } else if ($("#apsolu-wishes-filters span:visible").length == 0) {
                   $("#apsolu-wishes-filters legend").attr('class', 'apsolu-expandable');
                } else {
                   $("#apsolu-wishes-filters legend").attr('class', 'apsolu-collapsible');
                }
            }

            function rebuild_filter() {
                /*
                    // VOIR ÇA :
                var data = [{ id: 0, text: 'enhancement' }, { id: 1, text: 'bug' }, { id: 2, text: 'duplicate' }, { id: 3, text: 'invalid' }, { id: 4, text: 'wontfix' }];

                $(".js-example-data-array").select2({
                          data: data
                })
                */
                $('#menufilterscategory li').each(function() {
                    var found = false;
                    var rows = $("#apsolu-activities-table tbody tr");
                    rows.each(function(){
                        if (found == false) {
                            found = row.children().eq(0).text() == text;
                        }
                    });


                });
            }

            // Masque toutes les lignes du tableau contenant un créneau, pour ne garder que le sport
            function apsolu_collapse_rows() {
                $('.apsolu-sports-th').children('span').attr("class", "apsolu-expandable");
                $('.apsolu-sports-th').parent().nextUntil(".apsolu-sports-tr").css("display", "none");
            }

            $('.filters').each(function(){
                $(this).select2({width: '100%'});

                $(this).on('change.select2', function(){
                    apsolu_filter()
                });
            });

            $('#filters-submit, .apsolu-categories-col').css('display', 'none');
            apsolu_filter();

            /*
               // commenter : masque le formulaire de filter
            $("#apsolu-wishes-filters legend").click(function(){
                $("#apsolu-wishes-filters span").toggle();

                apsolu_filter_set_expandable_icons();
            });

            if($("#apsolu-wishes-filters .select2-selection__clear").length == 0){
                $("#apsolu-wishes-filters span").toggle();
            }

            apsolu_filter_set_expandable_icons();
            */

            $('#filters-reset').click(function(){
                // $('.filters').select2().val(null).trigger('change');
                $('.filters').select2('val', '');
                apsolu_filter();

                /*
                $(this).on('change.select2', function(){
                         apsolu_filter()
                });
                */
                apsolu_collapse_rows();
            });
        }
    };
});
