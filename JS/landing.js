(function () {
    const toggle = document.querySelector("[data-landing-menu-toggle]");
    const nav = document.querySelector("#landingNav");

    if (!toggle || !nav) {
        return;
    }

    const closeMenu = () => {
        document.body.classList.remove("landing-nav-open");
        toggle.setAttribute("aria-expanded", "false");
        toggle.setAttribute("aria-label", "Abrir menu");
    };

    const openMenu = () => {
        document.body.classList.add("landing-nav-open");
        toggle.setAttribute("aria-expanded", "true");
        toggle.setAttribute("aria-label", "Cerrar menu");
    };

    toggle.addEventListener("click", () => {
        if (document.body.classList.contains("landing-nav-open")) {
            closeMenu();
        } else {
            openMenu();
        }
    });

    nav.querySelectorAll("a").forEach((link) => {
        link.addEventListener("click", closeMenu);
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            closeMenu();
        }
    });

    window.addEventListener("resize", () => {
        if (!window.matchMedia("(max-width: 760px)").matches) {
            closeMenu();
        }
    });
})();
