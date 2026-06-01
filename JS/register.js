const registerForm = document.querySelector(".register-card");
const nameInput = document.getElementById("nombre");
const careerSelect = document.getElementById("carreraOpcion");
const otherCareerInput = document.getElementById("carreraOtra");

function normalizeSpaces(value) {
    return value.trim().replace(/\s+/g, " ");
}

function isFullName(value) {
    const normalized = normalizeSpaces(value);
    const parts = normalized.split(" ");
    const validCharacters = /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ' -]+$/;

    return (
        parts.length >= 3 &&
        parts.every((part) => part.replace(/['-]/g, "").length >= 2) &&
        validCharacters.test(normalized)
    );
}

function validateName() {
    if (!nameInput) {
        return true;
    }

    nameInput.value = normalizeSpaces(nameInput.value);

    if (!isFullName(nameInput.value)) {
        nameInput.setCustomValidity(
            "Ingresa nombre(s) y dos apellidos. Ejemplo: Maria Fernanda Rosales Silva."
        );
        return false;
    }

    nameInput.setCustomValidity("");
    return true;
}

function updateOtherCareer() {
    if (!careerSelect || !otherCareerInput) {
        return;
    }

    const shouldShowOther = careerSelect.value === "Otra";
    otherCareerInput.classList.toggle("is-visible", shouldShowOther);
    otherCareerInput.required = shouldShowOther;

    if (!shouldShowOther) {
        otherCareerInput.value = "";
        otherCareerInput.setCustomValidity("");
    }
}

function validateOtherCareer() {
    if (!otherCareerInput || careerSelect?.value !== "Otra") {
        return true;
    }

    otherCareerInput.value = normalizeSpaces(otherCareerInput.value);

    if (otherCareerInput.value.length < 4) {
        otherCareerInput.setCustomValidity("Escribe el nombre completo de tu carrera.");
        return false;
    }

    otherCareerInput.setCustomValidity("");
    return true;
}

nameInput?.addEventListener("blur", validateName);
careerSelect?.addEventListener("change", updateOtherCareer);
otherCareerInput?.addEventListener("blur", validateOtherCareer);

registerForm?.addEventListener("submit", (event) => {
    const validName = validateName();
    const validOtherCareer = validateOtherCareer();

    if (!validName || !validOtherCareer || !registerForm.checkValidity()) {
        event.preventDefault();
        registerForm.reportValidity();
    }
});

updateOtherCareer();
