import "./bootstrap";

// Turbo for smooth page transitions (no full reload)
import * as Turbo from "@hotwired/turbo";

// Disable prefetch untuk avoid loading bar trigger saat hover
Turbo.session.drive = true;
Turbo.setProgressBarDelay(0); // Disable Turbo's default progress bar

Turbo.start();

// NProgress for loading bar
import NProgress from "nprogress";
import "nprogress/nprogress.css";

// Configure NProgress
NProgress.configure({
    showSpinner: false, // Hide spinner, hanya progress bar
    speed: 300, // Animation speed
    minimum: 0.2, // Minimum percentage
    trickleSpeed: 200, // How often to trickle
});

// Expose NProgress to global scope for Blade templates
window.NProgress = NProgress;

// Track actual navigation (bukan prefetch)
let isNavigating = false;

// Integrate NProgress with Turbo events
// Hanya start saat BENAR-BENAR KLIK (bukan prefetch)
document.addEventListener("turbo:click", () => {
    isNavigating = true;
    NProgress.start();
});

document.addEventListener("turbo:before-fetch-request", (event) => {
    // Ignore prefetch request
    if (!isNavigating) {
        return;
    }
});

document.addEventListener("turbo:before-render", () => {
    if (isNavigating) {
        NProgress.inc(); // Increment progress
    }
});

document.addEventListener("turbo:render", () => {
    if (isNavigating) {
        NProgress.done();
        isNavigating = false;
    }
});

document.addEventListener("turbo:load", () => {
    if (isNavigating) {
        NProgress.done();
        isNavigating = false;
    }
});

// Handle errors
document.addEventListener("turbo:fetch-request-error", () => {
    NProgress.done();
    isNavigating = false;
});

// ApexCharts
import ApexCharts from "apexcharts";

// Expose ApexCharts to global scope
window.ApexCharts = ApexCharts;

// Alpine.js
import Alpine from "alpinejs";

window.Alpine = Alpine;

Alpine.start();
