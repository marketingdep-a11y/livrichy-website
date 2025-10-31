export const Mapbox = ({ data = [], type }) => ({
    init() {
        mapboxgl.accessToken = import.meta.env.VITE_MAPBOX_TOKEN;

        // Initial map creation
        const defaultFocus = [20.9144, 41.7895];
        const focus =
            data[0] && data[0].longitude && data[0].latitude
                ? [data[0].longitude, data[0].latitude]
                : defaultFocus;

        const map = new mapboxgl.Map({
            container: type ?? "map",
            style: "mapbox://styles/mapbox/light-v10",
            center: focus,
            zoom: 12,
        });

        // Controls - Disabling 3D, mouse rotation
        map.dragRotate.disable();
        map.touchZoomRotate.disableRotation();

        const controls = new mapboxgl.NavigationControl({
            showCompass: false,
        });
        map.addControl(controls, "bottom-right");

        // Toggles between map modes (Satellite, Light etc.)
        const layerList = document.getElementById("menu");
        if (layerList) {
            const inputs = layerList.getElementsByTagName("input");

            for (const input of inputs) {
                input.onclick = (layer) => {
                    const layerId = layer.target.id;
                    map.setStyle("mapbox://styles/mapbox/" + layerId);
                };
            }
        }

        // Here we fill the geojson array with the data we got from Data prop, if we have any
        const geojson = {
            type: "FeatureCollection",
            features: []
        };
        const contactjson = [];

        if (data.length > 0) {
            data.map((item) => {
                // Only add valid properties with required fields
                if (
                    item.price !== undefined &&
                    item.title !== undefined &&
                    item.address !== undefined &&
                    item.url !== undefined &&
                    item.featured_image !== undefined &&
                    item.longitude !== undefined &&
                    item.latitude !== undefined &&
                    item.price !== null &&
                    item.price !== 0
                ) {
                    geojson.features.push({
                        type: "Feature",
                        geometry: {
                            type: "Point",
                            coordinates: [item.longitude, item.latitude],
                        },
                        properties: {
                            url: item.url,
                            featured_image: item.featured_image,
                            title: item.title,
                            price: Number(item.price),
                            address: item.address,
                            property_features: item.property_features,
                        },
                    });
                }

                if (item.is_contact_variant == "1") {
                    contactjson.push({
                        type: "Contact",
                        geometry: {
                            type: "Point",
                            coordinates: [item.longitude, item.latitude],
                        },
                    });
                }
            });
        }

        const markers = [];
        const unclusteredMarkers = [];

        const clearMarkers = () => {
            while (markers.length) {
                markers.pop().remove();
            }
            while (unclusteredMarkers.length) {
                unclusteredMarkers.pop().remove();
            }
        };

        const createContactMarkers = () => {
            for (const contact of contactjson) {
                const el = document.createElement("div");
                el.className = "marker";
                el.dataset.variant = "contact";

                const size = 40;
                el.style.width = `${size}px`;
                el.style.height = `${size}px`;
                el.style.backgroundColor = "transparent";
                el.style.backgroundImage = `url(svg/marker-icon.svg)`;
                el.style.backgroundSize = "100%";

                const marker = new mapboxgl.Marker(el)
                    .setLngLat(contact.geometry.coordinates)
                    .addTo(map);

                markers.push(marker);
            }
        };

        const renderPropertyMarkers = () => {
            // Remove existing layers and sources if they exist
            if (map.getLayer('clusters')) {
                map.removeLayer('clusters');
            }
            if (map.getLayer('cluster-count')) {
                map.removeLayer('cluster-count');
            }
            if (map.getLayer('unclustered-point')) {
                map.removeLayer('unclustered-point');
            }
            if (map.getSource('properties')) {
                map.removeSource('properties');
            }

            // Add GeoJSON source with clustering
            map.addSource('properties', {
                type: 'geojson',
                data: geojson,
                cluster: true,
                clusterMaxZoom: 14,
                clusterRadius: 50,
            });

            // Add cluster layer
            map.addLayer({
                id: 'clusters',
                type: 'circle',
                source: 'properties',
                filter: ['has', 'point_count'],
                paint: {
                    'circle-color': '#f59e0b',
                    'circle-stroke-width': 2,
                    'circle-stroke-color': '#fff',
                    'circle-radius': [
                        'step',
                        ['get', 'point_count'],
                        20,
                        10,
                        30,
                        50,
                        40
                    ]
                }
            });

            // Add cluster count labels
            map.addLayer({
                id: 'cluster-count',
                type: 'symbol',
                source: 'properties',
                filter: ['has', 'point_count'],
                layout: {
                    'text-field': '{point_count_abbreviated}',
                    'text-font': ['Open Sans Semibold', 'Arial Unicode MS Bold'],
                    'text-size': 12
                },
                paint: {
                    'text-color': '#fff'
                }
            });

            // Helper function to create popup HTML
            const createPopupHTML = (feature) => {
                return `
                    <a href="${feature.properties.url}"
                        class="flex flex-col w-full duration-200 ease-in-out border rounded-2xl border-dark-100 hover:border-brand-950 hover:ring-1 hover:ring-brand-950">
                        <div class="shrink-0 rounded-t-2xl overflow-hidden">
                            <img src="${feature.properties.featured_image}" class="object-cover w-full max-h-48 h-full" />
                        </div>

                        <div class="relative flex flex-col justify-between flex-1 p-4 bg-white rounded-b-2xl">
                            <div class="flex-1">
                                <p class="font-bold font-heading [&>i]:font-sans [&>i]:font-medium text-xl text-brand-800">
                                AED ${new Intl.NumberFormat().format(feature.properties.price)}
                                </p>
                                <p class="font-bold font-heading [&>i]:font-sans [&>i]:font-medium text-lg leading-8 text-dark-950 mb-2">
                                    ${feature.properties.title}
                                </p>
                                <p class="text-base leading-6 text-dark-950 font-light">
                                    ${feature.properties.address}
                                </p>
                            </div>

                            ${feature.properties.property_features
                                ? `<div class="mt-4 border-t flex flex-wrap items-center">
                                    ${feature.properties.property_features
                                        .map((propFeature) => {
                                            if (propFeature.type?.value == "bedrooms") {
                                                return `<div class="mr-4 mt-4 inline-flex items-center">
                                                ${propFeature.icon ? `<div class="shrink-0 mr-2 w-5 h-5">${propFeature.icon}</div>` : ''}
                                                <p class="text-xs font-light text-dark-600">
                                                    ${propFeature.description} Bedrooms
                                                </p>
                                            </div>`;
                                            } else if (propFeature.type?.value == "bathrooms") {
                                                return `<div class="mr-4 mt-4 inline-flex items-center">
                                                ${propFeature.icon ? `<div class="shrink-0 mr-2 w-5 h-5">${propFeature.icon}</div>` : ''}
                                                <p class="text-xs font-light text-dark-600">
                                                    ${propFeature.description} Bathrooms
                                                </p>
                                            </div>`;
                                            } else if (propFeature.type?.value == "property_size") {
                                                return `<div class="mr-4 mt-4 inline-flex items-center">
                                                ${propFeature.icon ? `<div class="shrink-0 mr-2 w-5 h-5">${propFeature.icon}</div>` : ''}
                                                <p class="text-xs font-light text-dark-600">
                                                    ${propFeature.description} Square Area
                                                </p>
                                            </div>`;
                                            } else if (propFeature.type?.value == "style") {
                                                return `<div class="mr-4 mt-4 inline-flex items-center">
                                                ${propFeature.icon ? `<div class="shrink-0 mr-2 w-5 h-5">${propFeature.icon}</div>` : ''}
                                                <p class="text-xs font-light text-dark-600">
                                                    ${propFeature.description}
                                                </p>
                                            </div>`;
                                            }
                                            return '';
                                        })
                                        .join("")}
                                </div>`
                                : ''
                            }
                        </div>
                    </a>
                `;
            };

            // Click handler for clusters - zoom in
            map.on('click', 'clusters', (e) => {
                const features = map.queryRenderedFeatures(e.point, {
                    layers: ['clusters']
                });
                const clusterId = features[0].properties.cluster_id;
                map.getSource('properties').getClusterExpansionZoom(
                    clusterId,
                    (err, zoom) => {
                        if (err) return;

                        map.easeTo({
                            center: features[0].geometry.coordinates,
                            zoom: zoom
                        });
                    }
                );
            });

            // Change cursor on hover
            map.on('mouseenter', 'clusters', () => {
                map.getCanvas().style.cursor = 'pointer';
            });
            map.on('mouseleave', 'clusters', () => {
                map.getCanvas().style.cursor = '';
            });

            // Create custom markers for individual points (when zoomed in)
            const createCustomMarkers = () => {
                clearMarkers();
                
                // Get all unclustered points from the source
                const source = map.getSource('properties');
                if (!source || !source.loaded) return;

                // Get visible bounds
                const bounds = map.getBounds();
                
                // Query all features in visible area that are not clusters
                const unclusteredPoints = geojson.features.filter(feature => {
                    const [lng, lat] = feature.geometry.coordinates;
                    return bounds.contains([lng, lat]);
                });

                // Create markers for unclustered points
                unclusteredPoints.forEach(feature => {
                    const el = document.createElement("div");
                    el.className = "marker";
                    el.dataset.variant = "price";
                    el.style.color = "#fff";
                    el.style.fontSize = "14px";
                    el.style.fontWeight = "bold";
                    el.style.textAlign = "center";
                    el.style.padding = "6px 10px";
                    el.style.backgroundColor = "#f59e0b";
                    el.style.borderRadius = "8px";
                    el.style.border = "2px solid #fff";
                    el.style.boxShadow = "0 2px 4px rgba(0,0,0,0.2)";
                    el.style.whiteSpace = "nowrap";
                    el.innerHTML = `AED ${new Intl.NumberFormat().format(feature.properties.price)}`;

                    const marker = new mapboxgl.Marker(el)
                        .setLngLat(feature.geometry.coordinates)
                        .setPopup(
                            new mapboxgl.Popup({ offset: 25 }).setHTML(createPopupHTML(feature))
                        )
                        .addTo(map);

                    unclusteredMarkers.push(marker);
                });
            };

            // Update markers when zoom or move changes
            let updateTimeout;
            const updateMarkers = () => {
                clearTimeout(updateTimeout);
                updateTimeout = setTimeout(() => {
                    const zoom = map.getZoom();
                    if (zoom >= 14) {
                        // Hide cluster layers and show custom markers
                        if (map.getLayer('clusters')) {
                            map.setLayoutProperty('clusters', 'visibility', 'none');
                        }
                        if (map.getLayer('cluster-count')) {
                            map.setLayoutProperty('cluster-count', 'visibility', 'none');
                        }
                        createCustomMarkers();
                    } else {
                        // Show cluster layers and hide custom markers
                        if (map.getLayer('clusters')) {
                            map.setLayoutProperty('clusters', 'visibility', 'visible');
                        }
                        if (map.getLayer('cluster-count')) {
                            map.setLayoutProperty('cluster-count', 'visibility', 'visible');
                        }
                        clearMarkers();
                    }
                }, 100);
            };

            map.on('zoom', updateMarkers);
            map.on('moveend', updateMarkers);

            // Initial marker creation if zoom is high enough
            map.once('sourcedata', () => {
                if (map.getSource('properties').loaded) {
                    const zoom = map.getZoom();
                    if (zoom >= 14) {
                        if (map.getLayer('clusters')) {
                            map.setLayoutProperty('clusters', 'visibility', 'none');
                        }
                        if (map.getLayer('cluster-count')) {
                            map.setLayoutProperty('cluster-count', 'visibility', 'none');
                        }
                        createCustomMarkers();
                    }
                }
            });
        };

        const renderMarkers = () => {
            clearMarkers();

            if (contactjson.length > 0) {
                createContactMarkers();
                return;
            }

            if (geojson.features.length > 0) {
                renderPropertyMarkers();
            }
        };

        if (map.loaded()) {
            renderMarkers();
        } else {
            map.once("load", renderMarkers);
        }

        map.on("style.load", renderMarkers);

        // Updating size when mapbox/modal is opened.
        if (!type) {
            const canvas = document.querySelector(".mapboxgl-canvas");
            if (canvas) {
                canvas.style.width = "100%";
                canvas.style.height = "650px";
            }
        }

        // Blurry map loading fix.
        map.on("load", function () {
            setTimeout(() => {
                map.resize();
            }, 500);
        });
    },
});

export default Mapbox;
