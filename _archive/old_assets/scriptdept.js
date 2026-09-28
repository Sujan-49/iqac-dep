document.addEventListener("DOMContentLoaded", () => {
  const departmentLinks = document.querySelectorAll(".department-list a");
  const yearSelection = document.querySelector(".year-selection");
  const yearLinks = document.querySelectorAll(".year-list a");
  const mouDetails = document.querySelector(".mou-details");
  const selectedYearSpan = document.getElementById("selected-year");

  // When a department is clicked
  departmentLinks.forEach(link => {
    link.addEventListener("click", (e) => {
      e.preventDefault();
      const selectedDepartment = link.dataset.department;

      // Show year section
      yearSelection.style.display = "block";

      // Optionally, you can reset the year + mou section
      mouDetails.style.display = "none";
      selectedYearSpan.textContent = "";
    });
  });

  // When a year is clicked
  yearLinks.forEach(link => {
    link.addEventListener("click", (e) => {
      e.preventDefault();
      const selectedYear = link.dataset.year;

      // Update span text
      selectedYearSpan.textContent = selectedYear;

      // Show MOU details
      mouDetails.style.display = "block";

      // (Optional) You can dynamically load different MOUs based on year
    });
  });
});
