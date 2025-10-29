import { Mapbox, initializeSliders } from "./core";
import collapse from "@alpinejs/collapse";
import persist from "@alpinejs/persist";
import clipboard from "@ryangjchandler/alpine-clipboard";
import screen from "@victoryoalli/alpinejs-screen";
import Alpine from "alpinejs";

window.Alpine = Alpine;

Alpine.plugin(screen);
Alpine.plugin(persist);
Alpine.plugin(clipboard);
Alpine.plugin(collapse);

Alpine.data("mapbox", Mapbox);

Alpine.start();

// Sliders
document.addEventListener("DOMContentLoaded", initializeSliders);
