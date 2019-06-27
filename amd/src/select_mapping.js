define(['jquery', 'enrol_select/ol', 'enrol_select/jquery.popupoverlay'], function($, ol) {
    return {
        initialise : function(){
            return; // Désactivé temporairement.

            // Création de la map.
            var map = new ol.Map({
                layers: [
                    new ol.layer.Tile({
                        source: new ol.source.OSM()
                    }),
                    new ol.layer.Vector({
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            image: new ol.style.Icon(/** @type {olx.style.IconOptions} */ ({
                                anchor: [0.5, 46],
                                anchorXUnits: 'fraction',
                                anchorYUnits: 'pixels',
                                opacity: 0.75,
                                src: $('#apsolu-location-marker-img').attr('src')
                            }))
                        })
                    })
                ],
                target: 'apsolu-location-map',
                controls: ol.control.defaults({
                        attributionOptions: /** @type {olx.control.AttributionOptions} */ ({
                            collapsible: false
                        })
                    }).extend([
                        new ol.control.FullScreen()
                    ])
            });

            $('#apsolu-location-map').css('visilibity', 'hidden');
            $('#apsolu-location-map').css('position', 'absolute');

            // Lorsque l'utilisateur clique sur le marqueur de localisation...
            $('.apsolu-location-markers-a').click(function(event){
                event.preventDefault();

                var longitude = $(this).data('longitude');
                var latitude = $(this).data('latitude');

                map.setView(new ol.View({
                    center: ol.proj.transform([longitude, latitude], 'EPSG:4326', 'EPSG:3857'),
                    zoom: 13
                }));

                layers = map.getLayers().getArray();
                source = layers[1].getSource();
                source.clear();
                source.addFeature(new ol.Feature({geometry: new ol.geom.Point(ol.proj.transform([longitude, latitude], 'EPSG:4326',   'EPSG:3857'))}));

                // Affiche l'overlay.
                $('#apsolu-location-map').popup('show');

                // Affiche la map.
                $('#apsolu-location-map').css('visilibity', 'visible');
                $('#apsolu-location-map').css('position', 'relative');
            });
        }
    };
});
