// Admin.js
document.addEventListener("DOMContentLoaded", () => {
    // Handle creating new surveys
    const createSurveyButton = document.getElementById("create-survey");
    if (createSurveyButton) {
      createSurveyButton.addEventListener("click", () => {
        showModal("Create Survey", "Enter survey details here...");
      });
    }
  
    // Handle creating new templates
    const createTemplateButton = document.getElementById("create-template");
    if (createTemplateButton) {
      createTemplateButton.addEventListener("click", () => {
        showModal("Create Template", "Enter template details here...");
      });
    }
  
    // Handle table actions (edit/delete)
    const tableButtons = document.querySelectorAll(".btn-edit, .btn-delete");
    tableButtons.forEach((button) => {
      button.addEventListener("click", (event) => {
        const action = event.target.classList.contains("btn-edit") ? "edit" : "delete";
        const row = event.target.closest("tr");
        const itemName = row ? row.children[0].textContent : "";
  
        if (action === "edit") {
          showModal(`Edit Item: ${itemName}`, "Modify the details here...");
        } else if (action === "delete") {
          if (confirm(`Are you sure you want to delete ${itemName}?`)) {
            row.remove();
            alert(`${itemName} has been deleted.`);
          }
        }
      });
    });
  
    // Form submission feedback
    const settingsForm = document.querySelector("form");
    if (settingsForm) {
      settingsForm.addEventListener("submit", (event) => {
        event.preventDefault(); // Prevent actual submission for demo purposes
        alert("Settings have been saved!");
      });
    }
  
    // Chart rendering for analytics (if applicable)
    const surveyAnalyticsCanvas = document.getElementById("survey-analytics");
    if (surveyAnalyticsCanvas) {
      renderChart(surveyAnalyticsCanvas);
    }
  });
  
  // Show modal dialog
  function showModal(title, content) {
    const modal = document.createElement("div");
    modal.classList.add("modal");
  
    modal.innerHTML = `
      <div class="modal-content">
        <h2>${title}</h2>
        <p>${content}</p>
        <button class="btn btn-close">Close</button>
      </div>
    `;
  
    document.body.appendChild(modal);
  
    // Close modal functionality
    const closeButton = modal.querySelector(".btn-close");
    closeButton.addEventListener("click", () => {
      modal.remove();
    });
  }
  
  // Render a simple chart for analytics (example using Chart.js)
  function renderChart(canvas) {
    const chartData = {
      labels: ["Survey 1", "Survey 2", "Survey 3", "Survey 4"],
      datasets: [
        {
          label: "Responses",
          data: [12, 19, 3, 5],
          backgroundColor: ["#ff6384", "#36a2eb", "#cc65fe", "#ffce56"],
        },
      ],
    };
  
    new Chart(canvas, {
      type: "bar",
      data: chartData,
    });
  }
  