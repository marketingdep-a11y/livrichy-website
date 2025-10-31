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

            // Add cluster layer (color matches marker style #C5AC62)
            map.addLayer({
                id: 'clusters',
                type: 'circle',
                source: 'properties',
                filter: ['has', 'point_count'],
                paint: {
                    'circle-color': '#C5AC62',
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

            // Helper function to escape HTML and URLs
            const escapeHtml = (str) => {
                if (!str) return '';
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            };

            // Helper function to validate and escape image URL for HTML attributes
            const escapeImageUrl = (url) => {
                if (!url) return '';
                // Remove any whitespace
                let cleanUrl = url.trim().replace(/\s+/g, '');
                // For HTML attributes, we need to escape quotes but not encode the entire URL
                // Replace quotes and other special characters that could break HTML
                cleanUrl = cleanUrl.replace(/"/g, '&quot;').replace(/'/g, '&#x27;');
                // Return as-is for valid URLs (browser will handle encoding)
                return cleanUrl;
            };

            // Helper function to create popup HTML
            const createPopupHTML = (feature) => {
                const imageUrl = escapeImageUrl(feature.properties.featured_image || '');
                const listingUrl = escapeHtml(feature.properties.url || '#');
                const title = escapeHtml(feature.properties.title || '');
                const address = escapeHtml(feature.properties.address || '');
                const price = new Intl.NumberFormat().format(feature.properties.price || 0);
                
                const placeholderSvg = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'300\'%3E%3Crect fill=\'%23ddd\' width=\'400\' height=\'300\'/%3E%3Ctext fill=\'%23999\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\'%3EImage not available%3C/text%3E%3C/svg%3E';
                
                return `
                    <a href="${listingUrl}"
                        class="flex flex-col w-full duration-200 ease-in-out border rounded-2xl border-dark-100 hover:border-brand-950 hover:ring-1 hover:ring-brand-950">
                        <div class="shrink-0 rounded-t-2xl overflow-hidden">
                            <img src="${imageUrl}" 
                                 class="object-cover w-full max-h-48 h-full" 
                                 alt="${title}"
                                 onerror="this.onerror=null; this.src='${placeholderSvg}';" />
                        </div>

                        <div class="relative flex flex-col justify-between flex-1 p-4 bg-white rounded-b-2xl">
                            <div class="flex-1">
                                <p class="font-bold font-heading [&>i]:font-sans [&>i]:font-medium text-xl text-brand-800">
                                AED ${price}
                                </p>
                                <p class="font-bold font-heading [&>i]:font-sans [&>i]:font-medium text-lg leading-8 text-dark-950 mb-2">
                                    ${title}
                                </p>
                                <p class="text-base leading-6 text-dark-950 font-light">
                                    ${address}
                                </p>
                            </div>

                            ${feature.properties.property_features
                                ? `<div class="mt-4 border-t flex flex-wrap items-center">
                                    ${feature.properties.property_features
                                        .map((propFeature) => {
                                            const description = escapeHtml(propFeature?.description || '');
                                            const icon = propFeature?.icon || '';
                                            
                                            if (propFeature.type?.value == "bedrooms") {
                                                return `<div class="mr-4 mt-4 inline-flex items-center">
                                                ${icon ? `<div class="shrink-0 mr-2 w-5 h-5">${icon}</div>` : ''}
                                                <p class="text-xs font-light text-dark-600">
                                                    ${description} Bedrooms
                                                </p>
                                            </div>`;
                                            } else if (propFeature.type?.value == "bathrooms") {
                                                return `<div class="mr-4 mt-4 inline-flex items-center">
                                                ${icon ? `<div class="shrink-0 mr-2 w-5 h-5">${icon}</div>` : ''}
                                                <p class="text-xs font-light text-dark-600">
                                                    ${description} Bathrooms
                                                </p>
                                            </div>`;
                                            } else if (propFeature.type?.value == "property_size") {
                                                return `<div class="mr-4 mt-4 inline-flex items-center">
                                                ${icon ? `<div class="shrink-0 mr-2 w-5 h-5">${icon}</div>` : ''}
                                                <p class="text-xs font-light text-dark-600">
                                                    ${description} Square Area
                                                </p>
                                            </div>`;
                                            } else if (propFeature.type?.value == "style") {
                                                return `<div class="mr-4 mt-4 inline-flex items-center">
                                                ${icon ? `<div class="shrink-0 mr-2 w-5 h-5">${icon}</div>` : ''}
                                                <p class="text-xs font-light text-dark-600">
                                                    ${description}
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

            // Spiderfy function to spread markers with same coordinates in a circle
            const spiderfy = (coordinates, features) => {
                // Validate coordinates
                if (!coordinates || !Array.isArray(coordinates) || coordinates.length < 2 || 
                    isNaN(coordinates[0]) || isNaN(coordinates[1])) {
                    console.warn('Invalid coordinates for spiderfy:', coordinates);
                    return features.map((f) => ({ 
                        ...f, 
                        spiderCoords: f.geometry.coordinates 
                    }));
                }

                const [lng, lat] = coordinates;
                
                if (features.length <= 1) {
                    return features.map((f) => ({ 
                        ...f, 
                        spiderCoords: f.geometry.coordinates 
                    }));
                }

                // Dynamic radius based on number of markers (increases for more markers)
                // Base radius: 0.0015 degrees (~167m), scales up for larger groups
                const baseRadius = 0.0015;
                const scaleFactor = Math.min(features.length / 10, 3); // Scale up to 3x for large groups
                const radius = baseRadius * scaleFactor;
                
                const angleStep = (2 * Math.PI) / features.length;
                
                return features.map((feature, index) => {
                    const angle = index * angleStep;
                    const offsetLng = Math.cos(angle) * radius;
                    const offsetLat = Math.sin(angle) * radius;
                    
                    const spiderLng = lng + offsetLng;
                    const spiderLat = lat + offsetLat;
                    
                    // Validate spider coordinates
                    if (isNaN(spiderLng) || isNaN(spiderLat)) {
                        console.warn('Invalid spider coordinates:', spiderLng, spiderLat);
                        return { ...feature, spiderCoords: feature.geometry.coordinates };
                    }
                    
                    return {
                        ...feature,
                        spiderCoords: [spiderLng, spiderLat]
                    };
                });
            };

            // Group features by coordinates
            const groupFeaturesByCoordinates = (features) => {
                const groups = new Map();
                
                features.forEach(feature => {
                    const [lng, lat] = feature.geometry.coordinates;
                    // Round coordinates to avoid floating point precision issues
                    const key = `${Math.round(lng * 10000) / 10000},${Math.round(lat * 10000) / 10000}`;
                    
                    if (!groups.has(key)) {
                        groups.set(key, []);
                    }
                    groups.get(key).push(feature);
                });
                
                return groups;
            };

            // Create custom markers for individual points (when zoomed in) with original style
            const createCustomMarkers = () => {
                clearMarkers();
                
                // Get all unclustered points from the source
                const source = map.getSource('properties');
                if (!source || !source.loaded) {
                    console.warn('Source not loaded yet');
                    return;
                }

                // Get visible bounds
                const bounds = map.getBounds();
                
                // Query all features in visible area that are not clusters
                const visibleFeatures = geojson.features.filter(feature => {
                    if (!feature || !feature.geometry || !feature.geometry.coordinates) {
                        return false;
                    }
                    const [lng, lat] = feature.geometry.coordinates;
                    if (isNaN(lng) || isNaN(lat)) {
                        console.warn('Invalid coordinates in feature:', feature);
                        return false;
                    }
                    return bounds.contains([lng, lat]);
                });

                if (visibleFeatures.length === 0) {
                    console.warn('No visible features found');
                    return;
                }

                // Group features by coordinates for spiderfy
                const groupedFeatures = groupFeaturesByCoordinates(visibleFeatures);
                
                // Create markers with spiderfy for overlapping coordinates
                groupedFeatures.forEach((features, key) => {
                    if (!features || features.length === 0) return;
                    
                    const firstFeature = features[0];
                    if (!firstFeature || !firstFeature.geometry || !firstFeature.geometry.coordinates) {
                        console.warn('Invalid first feature:', firstFeature);
                        return;
                    }
                    
                    const [lng, lat] = firstFeature.geometry.coordinates;
                    if (isNaN(lng) || isNaN(lat)) {
                        console.warn('Invalid coordinates:', lng, lat);
                        return;
                    }
                    
                    const coordinates = [lng, lat];
                    
                    // Apply spiderfy to spread markers
                    const spiderfiedFeatures = spiderfy(coordinates, features);
                    
                    spiderfiedFeatures.forEach(feature => {
                        if (!feature || !feature.spiderCoords) {
                            console.warn('Feature missing spiderCoords:', feature);
                            return;
                        }
                        
                        const [spiderLng, spiderLat] = feature.spiderCoords;
                        if (isNaN(spiderLng) || isNaN(spiderLat)) {
                            console.warn('Invalid spider coordinates:', spiderLng, spiderLat);
                            return;
                        }
                        
                        const el = document.createElement("div");
                        el.className = "marker";
                        el.dataset.variant = "price";

                        // Original marker style (golden/orange color with price)
                        el.style.color = "#fff";
                        el.style.fontSize = "16px";
                        el.innerHTML = `AED ${new Intl.NumberFormat().format(
                            feature.properties.price
                        )}`;

                        try {
                            const marker = new mapboxgl.Marker(el)
                                .setLngLat([spiderLng, spiderLat])
                                .setPopup(
                                    new mapboxgl.Popup({ offset: 25 }).setHTML(createPopupHTML(feature))
                                )
                                .addTo(map);

                            unclusteredMarkers.push(marker);
                        } catch (error) {
                            console.error('Error creating marker:', error, feature);
                        }
                    });
                });
            };

            // Update markers when zoom or move changes
            let updateTimeout;
            const updateMarkers = () => {
                clearTimeout(updateTimeout);
                updateTimeout = setTimeout(() => {
                    const zoom = map.getZoom();
                    const source = map.getSource('properties');
                    
                    if (!source || !source.loaded) {
                        // Wait for source to load
                        map.once('sourcedata', updateMarkers);
                        return;
                    }
                    
                    if (zoom >= 14) {
                        // Hide cluster layers and show custom markers
                        if (map.getLayer('clusters')) {
                            map.setLayoutProperty('clusters', 'visibility', 'none');
                        }
                        if (map.getLayer('cluster-count')) {
                            map.setLayoutProperty('cluster-count', 'visibility', 'none');
                        }
                        // Small delay to ensure layers are hidden before creating markers
                        setTimeout(() => {
                            createCustomMarkers();
                        }, 50);
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
            map.on('move', updateMarkers); // Also update on move for smoother experience

            // Initial marker creation if zoom is high enough
            const initMarkers = () => {
                const source = map.getSource('properties');
                if (!source || !source.loaded) {
                    map.once('sourcedata', initMarkers);
                    return;
                }
                
                const zoom = map.getZoom();
                if (zoom >= 14) {
                    if (map.getLayer('clusters')) {
                        map.setLayoutProperty('clusters', 'visibility', 'none');
                    }
                    if (map.getLayer('cluster-count')) {
                        map.setLayoutProperty('cluster-count', 'visibility', 'none');
                    }
                    setTimeout(() => {
                        createCustomMarkers();
                    }, 100);
                }
            };
            
            map.once('sourcedata', initMarkers);
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
