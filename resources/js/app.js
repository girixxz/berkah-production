import "./bootstrap";

// Turbo for smooth page transitions (no full reload)
import * as Turbo from "@hotwired/turbo";
Turbo.start();

// Alpine.js
import Alpine from "alpinejs";

window.Alpine = Alpine;

Alpine.start();
