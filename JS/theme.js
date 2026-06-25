(function () {
    const storageKey = "atenea-theme";
    const root = document.documentElement;

    const savedTheme = localStorage.getItem(storageKey);
    const preferredTheme =
        savedTheme === "light" || savedTheme === "dark"
            ? savedTheme
            : (window.matchMedia && window.matchMedia("(prefers-color-scheme: light)").matches ? "light" : "dark");

    root.dataset.theme = preferredTheme;

    const buttons = document.querySelectorAll("[data-theme-toggle]");

    function syncButtons(theme) {
        buttons.forEach((button) => {
            button.textContent = theme === "light" ? "Modo oscuro" : "Modo claro";
            button.setAttribute("aria-pressed", theme === "light" ? "true" : "false");
        });
    }

    syncButtons(preferredTheme);

    buttons.forEach((button) => {
        button.addEventListener("click", () => {
            const nextTheme = root.dataset.theme === "light" ? "dark" : "light";
            root.dataset.theme = nextTheme;
            localStorage.setItem(storageKey, nextTheme);
            syncButtons(nextTheme);
        });
    });
})();
