export const Mapbox = ({ data = [], type }) => ({
    init() {
        mapboxgl.accessToken = import.meta.env.VITE_MAPBOX_TOKEN;

        // Initial map creation
        let defaultFocus = [20.9144, 41.7895];
        let focus =
            data[0].longitude && data[0].latitude
                ? [data[0].longitude, data[0].latitude]
                : defaultFocus;

        let map = new mapboxgl.Map({
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

        // Toggles between map modes (Satelite, Light etc.)
        const layerList = document.getElementById("menu");
        const inputs = layerList.getElementsByTagName("input");

        for (const input of inputs) {
            input.onclick = (layer) => {
                const layerId = layer.target.id;
                map.setStyle("mapbox://styles/mapbox/" + layerId);
            };
        }

        // Here we fill the geojson array with the data we got from Data prop, if we have any
        let geojson = [];
        let contactjson = [];

        data.length > 0 &&
            data.map((item) => {
                geojson.push({
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

        // Add markers to map (Contact Variant)
        for (const contact of contactjson) {
            const el = document.createElement("div");
            el.className = "marker";

            let width = 40;
            let height = 40;
            el.style.width = `${width}px`;
            el.style.height = `${height}px`;
            el.style.backgroundColor = "transparent";
            el.style.backgroundImage = `url(svg/marker-icon.svg)`;
            el.style.backgroundSize = "100%";

            new mapboxgl.Marker(el)
                .setLngLat(contact.geometry.coordinates)
                .addTo(map);
        }

        // Add markers to map (Normal Variant)
        if (contactjson.length < 1) {
            for (const feature of geojson) {
                if (
                    feature.properties.price === undefined ||
                    feature.properties.title === undefined ||
                    feature.properties.address === undefined ||
                    feature.properties.url === undefined ||
                    feature.properties.featured_image === undefined ||
                    feature.properties.property_features === undefined
                )
                    return;

                const el = document.createElement("div");
                el.className = "marker";

                el.style.color = "#fff";
                el.style.fontSize = "16px";
                el.innerHTML = `$${new Intl.NumberFormat().format(
                    feature.properties.price
                )}`; // Here we add price

                // If there's no price, we don't render the marker
                if (
                    feature.properties.price !== null &&
                    feature.properties.price !== undefined &&
                    feature.properties.price !== 0
                ) {
                    new mapboxgl.Marker(el)
                        .setLngLat(feature.geometry.coordinates)
                        .setPopup(
                            new mapboxgl.Popup({ offset: 25 }) // Add popups
                                .setHTML(`
                            <a href="${feature.properties.url}"
                                class="flex flex-col w-full duration-200 ease-in-out border rounded-2xl border-dark-100 hover:border-brand-950 hover:ring-1 hover:ring-brand-950">
                                <div class="shrink-0 rounded-t-2xl overflow-hidden">
                                    <img src="${
                                        feature.properties.featured_image
                                    }" class="object-cover w-full max-h-48 h-full" />
                                </div>

                                <div class="relative flex flex-col justify-between flex-1 p-4 bg-white rounded-b-2xl">
                                    <div class="flex-1">
                                        <p class="font-bold font-heading [&>i]:font-sans [&>i]:font-medium text-xl text-brand-800">
                                        $${new Intl.NumberFormat().format(
                                            feature.properties.price
                                        )}
                                        </p>
                                        <p class="font-bold font-heading [&>i]:font-sans [&>i]:font-medium text-lg leading-8 text-dark-950 mb-2">
                                            ${feature.properties.title}
                                        </p>
                                        <p class="text-base leading-6 text-dark-950 font-light">
                                            ${feature.properties.address}
                                        </p>
                                    </div>

                                    ${
                                        feature.properties.property_features
                                            ? `<div class="mt-4 border-t flex flex-wrap items-center">
                                                ${feature.properties.property_features
                                                    .map((feature) => {
                                                        if (
                                                            feature.type
                                                                .value ==
                                                            "bedrooms"
                                                        ) {
                                                            return `<div class="mr-4 mt-4 inline-flex items-center">
                                                            ${
                                                                feature.icon
                                                                    ? `<div class="shrink-0 mr-2 w-5 h-5">${feature.icon}</div>`
                                                                    : ``
                                                            }
                                                            <p class="text-xs font-light text-dark-600">
                                                                ${
                                                                    feature.description
                                                                } Bedrooms
                                                            </p>
                                                        </div>`;
                                                        } else if (
                                                            feature.type
                                                                .value ==
                                                            "bathrooms"
                                                        ) {
                                                            return `<div class="mr-4 mt-4 inline-flex items-center">
                                                            ${
                                                                feature.icon
                                                                    ? `<div class="shrink-0 mr-2 w-5 h-5">${feature.icon}</div>`
                                                                    : ``
                                                            }
                                                            <p class="text-xs font-light text-dark-600">
                                                                ${
                                                                    feature.description
                                                                } Bathrooms
                                                            </p>
                                                        </div>`;
                                                        } else if (
                                                            feature.type
                                                                .value ==
                                                            "property_size"
                                                        ) {
                                                            return `<div class="mr-4 mt-4 inline-flex items-center">
                                                            ${
                                                                feature.icon
                                                                    ? `<div class="shrink-0 mr-2 w-5 h-5">${feature.icon}</div>`
                                                                    : ``
                                                            }
                                                            <p class="text-xs font-light text-dark-600">
                                                                ${
                                                                    feature.description
                                                                } Square Area
                                                            </p>
                                                        </div>`;
                                                        } else if (
                                                            feature.type
                                                                .value ==
                                                            "style"
                                                        ) {
                                                            return `<div class="mr-4 mt-4 inline-flex items-center">
                                                            ${
                                                                feature.icon
                                                                    ? `<div class="shrink-0 mr-2 w-5 h-5">${feature.icon}</div>`
                                                                    : ``
                                                            }
                                                            <p class="text-xs font-light text-dark-600">
                                                                ${
                                                                    feature.description
                                                                }
                                                            </p>
                                                        </div>`;
                                                        } else {
                                                            return ``;
                                                        }
                                                    })
                                                    .join("")}
                                            </div>`
                                            : ``
                                    }
                                </div>
                            </a>
                        `)
                        )
                        .addTo(map);
                }
            }
        }

        // Updating size when mapbox/modal is opened.
        if (!type) {
            var canvas = document.querySelector(".mapboxgl-canvas");
            canvas.style.width = "100%";
            canvas.style.height = "650px";
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
