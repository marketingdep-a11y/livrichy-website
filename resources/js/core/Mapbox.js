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
            console.log('🗺️ Mapbox Init - Full data received:', data);
            console.log('🗺️ Map type:', type);
            
            data.map((item) => {
                console.log('---');
                console.log('📍 Processing item:', item);
                
                // For normalmap (single property page), only require coordinates
                // For listing maps, require all fields including price
                const isNormalMap = type === 'normalmap';
                const hasCoordinates = item.longitude !== undefined && 
                                      item.latitude !== undefined &&
                                      item.longitude !== '' && 
                                      item.latitude !== '';
                
                console.log('🗺️ Is normalmap:', isNormalMap);
                console.log('📍 Coordinates:', item.longitude, item.latitude);
                console.log('✅ Has coordinates:', hasCoordinates);
                
                // Strict validation for listing maps
                const hasRequiredFields = item.price !== undefined &&
                    item.title !== undefined &&
                    item.address !== undefined &&
                    item.url !== undefined &&
                    item.featured_image !== undefined &&
                    item.price !== null &&
                    item.price !== 0;
                
                console.log('📋 Has required fields:', hasRequiredFields);
                
                // For normalmap: only check coordinates
                // For other maps: check all required fields
                const shouldAddMarker = isNormalMap ? hasCoordinates : (hasCoordinates && hasRequiredFields);
                
                console.log('🎯 Should add marker:', shouldAddMarker);
                
                if (shouldAddMarker) {
                    // Parse coordinates as numbers to avoid string concatenation issues
                    const longitude = parseFloat(item.longitude);
                    const latitude = parseFloat(item.latitude);
                    
                    // Validate coordinates
                    if (isNaN(longitude) || isNaN(latitude)) {
                        console.warn('Invalid coordinates for property:', item);
                        return;
                    }
                    
                    geojson.features.push({
                        type: "Feature",
                        geometry: {
                            type: "Point",
                            coordinates: [longitude, latitude],
                        },
                        properties: {
                            url: item.url || '#',
                            featured_image: item.featured_image || '',
                            title: item.title || 'Property',
                            price: Number(item.price) || 0,
                            address: item.address || '',
                            property_features: item.property_features || [],
                            property_status: item.property_status || 'rent',
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

        const createSimpleMarker = () => {
            console.log('🎯 Creating simple marker for normalmap');
            console.log('🎯 Features to render:', geojson.features);
            
            if (geojson.features.length === 0) {
                console.warn('⚠️ No features to render');
                return;
            }

            // Create a simple marker for single property page
            const feature = geojson.features[0];
            const coordinates = feature.geometry.coordinates;
            
            console.log('🎯 Creating marker at:', coordinates);

            const el = document.createElement("div");
            el.className = "marker";
            el.dataset.variant = "property";

            const size = 40;
            el.style.width = `${size}px`;
            el.style.height = `${size}px`;
            el.style.backgroundColor = "transparent";
            el.style.backgroundImage = `url(/svg/marker-icon.svg)`;
            el.style.backgroundSize = "100%";
            el.style.cursor = "pointer";

            const marker = new mapboxgl.Marker(el)
                .setLngLat(coordinates)
                .addTo(map);

            markers.push(marker);
            
            console.log('✅ Marker created and added to map');
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
                if (!url || typeof url !== 'string') return '';
                // Remove any whitespace
                let cleanUrl = url.trim().replace(/\s+/g, '');
                // If URL doesn't start with http/https, it might be a relative path or missing protocol
                if (cleanUrl && !cleanUrl.match(/^https?:\/\//i)) {
                    // If it's a relative path starting with /, keep it
                    // Otherwise, try to add https://
                    if (!cleanUrl.startsWith('/')) {
                        console.warn('Image URL missing protocol:', cleanUrl);
                    }
                }
                // For HTML attributes, escape quotes that could break the attribute
                cleanUrl = cleanUrl.replace(/"/g, '&quot;').replace(/'/g, '&#x27;');
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
                
                // Status badge
                const propertyStatus = feature.properties.property_status || 'rent';
                const statusText = propertyStatus === 'sale' ? 'FOR SALE' : 'FOR RENT';
                const statusIcon = propertyStatus === 'sale' ? 'tag' : 'key';
                const statusBadge = `
                    <div class="absolute flex items-center p-2 gap-x-2 rounded-lg bg-brand-200 left-4 top-4">
                        <svg class="w-4 h-4 fill-current shrink-0 text-brand-950" viewBox="0 0 24 24">
                            ${statusIcon === 'tag' ? '<path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82zM7 9a2 2 0 100-4 2 2 0 000 4z"/>' : '<path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6z"/>'}
                        </svg>
                        <span class="text-xs font-bold font-heading text-brand-950 mt-0.5">${statusText}</span>
                    </div>
                `;
                
                return `
                    <a href="${listingUrl}"
                        class="flex flex-col w-full duration-200 ease-in-out border rounded-2xl border-dark-100 hover:border-brand-950 hover:ring-1 hover:ring-brand-950">
                        <div class="relative shrink-0 rounded-t-2xl overflow-hidden">
                            ${statusBadge}
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
                                            } else if (propFeature.type?.value == "parking") {
                                                return `<div class="mr-4 mt-4 inline-flex items-center">
                                                ${icon ? `<div class="shrink-0 mr-2 w-5 h-5">${icon}</div>` : ''}
                                                <p class="text-xs font-light text-dark-600">
                                                    ${description} Parking
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

                // Ensure coordinates are numbers, not strings
                const lng = parseFloat(coordinates[0]);
                const lat = parseFloat(coordinates[1]);
                
                // Validate parsed coordinates
                if (isNaN(lng) || isNaN(lat)) {
                    console.warn('Invalid parsed coordinates:', lng, lat);
                    return features.map((f) => ({ 
                        ...f, 
                        spiderCoords: f.geometry.coordinates 
                    }));
                }
                
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
                    const offsetLng = parseFloat((Math.cos(angle) * radius).toFixed(10));
                    const offsetLat = parseFloat((Math.sin(angle) * radius).toFixed(10));
                    
                    // Ensure we're adding numbers, not concatenating strings
                    const spiderLng = parseFloat((lng + offsetLng).toFixed(10));
                    const spiderLat = parseFloat((lat + offsetLat).toFixed(10));
                    
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
            console.log('🗺️ renderMarkers called, type:', type);
            clearMarkers();

            if (contactjson.length > 0) {
                createContactMarkers();
                return;
            }

            if (geojson.features.length > 0) {
                // For normalmap (single property page), use simple marker
                if (type === 'normalmap') {
                    console.log('🎯 Using simple marker for normalmap');
                    createSimpleMarker();
                } else {
                    console.log('🎯 Using property markers with clustering');
                    renderPropertyMarkers();
                }
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
