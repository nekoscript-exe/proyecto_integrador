document.addEventListener("submit", (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const editor = form.querySelector("[data-mail-editor]");
    const hiddenBody = form.querySelector("[data-mail-body]");
    if (editor && hiddenBody) {
        hiddenBody.value = editor.innerHTML.trim();
    }

    const submitter = event.submitter instanceof HTMLElement ? event.submitter : null;
    let confirmMessage = submitter?.dataset.confirm || "";

    if (submitter?.matches("[data-mail-send]")) {
        const count = form.querySelector("[data-mail-count]")?.textContent?.trim() || "0";
        confirmMessage = `Deseas enviar este comunicado a ${count} usuarios?`;
    }

    if (confirmMessage) {
        const ok = window.confirm(confirmMessage);
        if (!ok) {
            event.preventDefault();
        }
    }
});

const mailForm = document.querySelector("[data-mail-form]");

if (mailForm) {
    const editor = mailForm.querySelector("[data-mail-editor]");
    const hiddenBody = mailForm.querySelector("[data-mail-body]");
    const specificWrap = mailForm.querySelector("[data-specific-user-wrap]");
    const userSearch = mailForm.querySelector("[data-user-search]");
    const userSelect = mailForm.querySelector("[data-specific-user-select]");
    const countTarget = mailForm.querySelector("[data-mail-count]");
    const scopeInputs = Array.from(mailForm.querySelectorAll("input[name='recipient_scope']"));

    const syncEditor = () => {
        if (editor && hiddenBody) {
            hiddenBody.value = editor.innerHTML.trim();
        }
    };

    const currentScope = () => scopeInputs.find((input) => input.checked)?.value || "active";

    const updateScopeUi = () => {
        const scope = currentScope();
        const selected = scopeInputs.find((input) => input.checked);
        const nextCount = scope === "specific" && userSelect?.value === "0"
            ? 0
            : Number(selected?.dataset.recipientCount || 0);

        if (countTarget) {
            countTarget.textContent = nextCount.toString();
        }

        if (specificWrap) {
            specificWrap.classList.toggle("is-visible", scope === "specific");
        }
    };

    scopeInputs.forEach((input) => input.addEventListener("change", updateScopeUi));

    if (userSelect) {
        userSelect.addEventListener("change", updateScopeUi);
    }

    if (userSearch && userSelect) {
        userSearch.addEventListener("input", () => {
            const query = userSearch.value.trim().toLowerCase();
            Array.from(userSelect.options).forEach((option) => {
                if (option.value === "0") {
                    option.hidden = false;
                    return;
                }

                const haystack = option.dataset.search || option.textContent.toLowerCase();
                option.hidden = query !== "" && !haystack.includes(query);
            });
        });
    }

    if (editor) {
        editor.addEventListener("input", syncEditor);
    }

    mailForm.querySelectorAll("[data-format-command]").forEach((button) => {
        button.addEventListener("click", () => {
            const command = button.dataset.formatCommand;
            if (!command) {
                return;
            }

            editor?.focus();
            document.execCommand(command, false);
            syncEditor();
        });
    });

    mailForm.querySelectorAll("[data-format-block]").forEach((button) => {
        button.addEventListener("click", () => {
            const block = button.dataset.formatBlock;
            if (!block) {
                return;
            }

            editor?.focus();
            document.execCommand("formatBlock", false, block);
            syncEditor();
        });
    });

    const linkButton = mailForm.querySelector("[data-format-link]");
    if (linkButton) {
        linkButton.addEventListener("click", () => {
            const url = window.prompt("Pega el enlace completo empezando con https://");
            if (!url || !url.trim().toLowerCase().startsWith("https://")) {
                return;
            }

            editor?.focus();
            document.execCommand("createLink", false, url.trim());
            syncEditor();
        });
    }

    syncEditor();
    updateScopeUi();
}
