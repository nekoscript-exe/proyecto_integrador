const steps = document.querySelectorAll(".form-step");

const nextBtn = document.getElementById("nextBtn");
const prevBtn = document.getElementById("prevBtn");
const submitBtn = document.getElementById("submitBtn");

const progress = document.getElementById("progress");
const progressText = document.getElementById("progressText");
const form = document.getElementById("multiStepForm");

let currentStep = 0;

function updateSteps(){

    steps.forEach((step) => {

        step.classList.remove("active");

    });

    steps[currentStep].classList.add("active");

    const percent =
        ((currentStep + 1) / steps.length) * 100;

    progress.style.width = percent + "%";

    if(progressText){
        progressText.textContent = Math.round(percent) + "%";
    }

    prevBtn.style.display =
        currentStep === 0
        ? "none"
        : "inline-block";

    if(currentStep === steps.length - 1){

        nextBtn.style.display = "none";
        submitBtn.style.display = "inline-block";

    }else{

        nextBtn.style.display = "inline-block";
        submitBtn.style.display = "none";

    }

}

nextBtn.addEventListener("click", () => {
    const currentFields = steps[currentStep].querySelectorAll("input, select, textarea");
    let validStep = true;

    currentFields.forEach((field) => {
        if(!field.checkValidity()){
            field.reportValidity();
            validStep = false;
        }
    });

    if(!validStep){
        return;
    }

    currentStep = Math.min(currentStep + 1, steps.length - 1);

    updateSteps();

});

prevBtn.addEventListener("click", () => {

    currentStep = Math.max(currentStep - 1, 0);

    updateSteps();

});

form.addEventListener("submit", (event) => {
    if(!form.checkValidity()){
        event.preventDefault();
        form.reportValidity();
    }
});

updateSteps();
