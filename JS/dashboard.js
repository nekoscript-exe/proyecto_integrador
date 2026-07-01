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

const datasetSearch = document.querySelector("[data-dataset-search]");
const datasetTableWrap = document.querySelector("[data-dataset-table-wrap]");
const datasetTable = document.querySelector("[data-dataset-table]");
const visibleCount = document.querySelector("[data-visible-count]");

if (datasetSearch && datasetTable && datasetTableWrap) {
    const rows = Array.from(datasetTable.querySelectorAll("tbody tr"));

    const updateVisibleCount = () => {
        if (!visibleCount) {
            return;
        }

        const totalVisible = rows.filter((row) => row.style.display !== "none").length;
        visibleCount.textContent = totalVisible.toString();
    };

    datasetSearch.addEventListener("input", () => {
        const query = datasetSearch.value.trim().toLowerCase();

        rows.forEach((row) => {
            const match = row.textContent.toLowerCase().includes(query);
            row.style.display = match ? "" : "none";
        });

        updateVisibleCount();
    });

    updateVisibleCount();
}

document.querySelectorAll("[data-column-focus]").forEach((button) => {
    button.addEventListener("click", () => {
        const column = button.getAttribute("data-column-focus");
        if (!column || !datasetTableWrap) {
            return;
        }

        const currentFocus = datasetTableWrap.getAttribute("data-focus-column");
        const nextFocus = currentFocus === column ? "" : column;

        if (nextFocus === "") {
            datasetTableWrap.removeAttribute("data-focus-column");
        } else {
            datasetTableWrap.setAttribute("data-focus-column", nextFocus);
            datasetTableWrap.scrollIntoView({ behavior: "smooth", block: "start" });
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
