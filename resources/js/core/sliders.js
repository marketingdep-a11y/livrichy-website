import Splide, { LOOP } from "@splidejs/splide";

import { AutoScroll } from "@splidejs/splide-extension-auto-scroll";

export default function initializeSliders() {
    const heroSlider = document.getElementById("hero-slider");

    const testimonialSlider = document.getElementById("testimonial-carousel");
    const testimonialSliderV2 = document.getElementById(
        "testimonial-carousel-v2"
    );
    const testimonialSliderV3 = document.getElementById(
        "testimonial-carousel-v3"
    );

    const teamsection1Slider = document.getElementById("team-carousel");
    const propertySlider = document.getElementById("property-carousel");

    if (heroSlider) {
        new Splide("#hero-slider", {
            type: "loop",
            direction: "ttb",
            height: "100vh",
            pagination: false,
            perPage: 3,
            perMove: 1,
            arrows: false,
            gap: 32,
            autoScroll: {
                pauseOnFocus: false,
                pauseOnHover: false,
                speed: 1,
            },
        }).mount({ AutoScroll });
    }

    if (testimonialSlider) {
        let slider = new Splide(testimonialSlider, {
            arrows: false,
            autoplay: true,
            speed: 700,
            rewind: true,
            pauseOnHover: false,
            pagination: false,
            perPage: 1,
        }).mount();

        // First slide add progress bar
        let firstSlide = document.getElementById("progress__0");
        firstSlide.classList.add("splide__progress__bar");

        slider.on("move", function (index) {
            const progressId = `progress__${index}`;

            // Get all dom elements that have this class
            let progressBars = document.querySelectorAll(
                ".splide__progress__bar"
            );

            // Remove the class from all progress elements
            progressBars.forEach((progressBar) => {
                progressBar.classList.remove("splide__progress__bar");
            });

            // Get the progress element for the current slide
            let testimonialSliderProgress = document.getElementById(progressId);

            // Add the class to the current slide
            testimonialSliderProgress.classList.add("splide__progress__bar");
        });
    }

    if (testimonialSliderV2) {
        new Splide(testimonialSliderV2, {
            breakpoints: {
                640: { perPage: 1 },
                768: { perPage: 2 },
                1024: { perPage: 3 },
            },
            perPage: 3,
            gap: "2rem",
            type: "loop",
            arrows: false,
            drag: "free",
            pagination: false,
        }).mount({ AutoScroll });
    }

    if (testimonialSliderV3) {
        new Splide(testimonialSliderV3, {
            perPage: 2,
            arrows: false,
            perMove: 2,
            pagination: true,
            breakpoints: {
                1024: {
                    perPage: 2,
                },
                768: {
                    perPage: 1,
                },
            },
        }).mount();
    }

    if (teamsection1Slider) {
        new Splide(teamsection1Slider, {
            gap: 24,
            type: LOOP,
            perPage: 4,
            speed: 900,
            focus: 0,
            perMove: 1,
            flickPower: 74,
            arrows: false,
            breakpoints: {
                1280: {
                    perPage: 3,
                },
                768: {
                    perPage: 2,
                },
                425: {
                    perPage: 1,
                },
            },
        }).mount();
    }

    if (propertySlider) {
        new Splide(propertySlider, {
            perPage: 1,
            gap: 32,
            arrows: false,
            perMove: 1,
            pagination: false,
        }).mount();
    }
}
