document.addEventListener("submit", (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const confirmMessage = form.querySelector("[data-confirm]")?.dataset.confirm;
    if (confirmMessage) {
        const ok = window.confirm(confirmMessage);
        if (!ok) {
            event.preventDefault();
        }
    }
});
