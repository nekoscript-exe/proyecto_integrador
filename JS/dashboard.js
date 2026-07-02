const cards = document.querySelectorAll(".student-card, .ranking-row, .metric-card");
const navToggles = document.querySelectorAll("[data-nav-toggle]");
const navToggle = navToggles[0] || null;
const navOverlay = document.querySelector("[data-nav-overlay]");
const sidebar = document.querySelector(".sidebar");

cards.forEach((card) => {
    card.addEventListener("keydown", (event) => {
        if (event.key === "Enter" && card.tagName.toLowerCase() === "a") {
            card.click();
        }
    });
});

const closeMobileNav = () => {
    document.body.classList.remove("dashboard-nav-open");
    navToggles.forEach((toggle) => {
        toggle.setAttribute("aria-expanded", "false");
        toggle.setAttribute("aria-label", "Abrir menu");
    });
};

const openMobileNav = () => {
    document.body.classList.add("dashboard-nav-open");
    navToggles.forEach((toggle) => {
        toggle.setAttribute("aria-expanded", "true");
        toggle.setAttribute("aria-label", "Cerrar menu");
    });
};

if (navToggles.length && sidebar) {
    navToggles.forEach((toggle) => toggle.addEventListener("click", () => {
        if (document.body.classList.contains("dashboard-nav-open")) {
            closeMobileNav();
        } else {
            openMobileNav();
        }
    }));
}

if (navOverlay) {
    navOverlay.addEventListener("click", closeMobileNav);
}

document.querySelectorAll(".sidebar a").forEach((link) => {
    link.addEventListener("click", () => {
        if (window.matchMedia("(max-width: 780px)").matches) {
            closeMobileNav();
        }
    });
});

window.addEventListener("resize", () => {
    if (!window.matchMedia("(max-width: 780px)").matches) {
        closeMobileNav();
    }
});

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
        closeMobileNav();
    }
});

document.documentElement.classList.add("dashboard-ready");
