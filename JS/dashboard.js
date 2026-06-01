const cards = document.querySelectorAll(".student-card, .ranking-row, .metric-card");

cards.forEach((card) => {
    card.addEventListener("keydown", (event) => {
        if (event.key === "Enter" && card.tagName.toLowerCase() === "a") {
            card.click();
        }
    });
});

document.documentElement.classList.add("dashboard-ready");
