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
 * Module javascript.
 *
 * @todo       Description à compléter.
 *
 * @module     enrol_select/select_mapping
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'enrol_select/ol', 'enrol_select/jquery.popupoverlay'], function($, ol) {
    return {
        initialise: function() {
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
            $('.apsolu-location-markers-a').click(function(event) {
                event.preventDefault();

                var longitude = $(this).data('longitude');
                var latitude = $(this).data('latitude');

                map.setView(new ol.View({
                    center: ol.proj.transform([longitude, latitude], 'EPSG:4326', 'EPSG:3857'),
                    zoom: 13
                }));

                let layers = map.getLayers().getArray();
                let source = layers[1].getSource();
                source.clear();
                source.addFeature(new ol.Feature({
                    geometry: new ol.geom.Point(ol.proj.transform([longitude, latitude], 'EPSG:4326', 'EPSG:3857'))
                }));

                // Affiche l'overlay.
                $('#apsolu-location-map').popup('show');

                // Affiche la map.
                $('#apsolu-location-map').css('visilibity', 'visible');
                $('#apsolu-location-map').css('position', 'relative');
            });
        }
    };
});
