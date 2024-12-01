document.addEventListener("DOMContentLoaded", function () {
  // Function to show the selected section and hide others
  function showSection(sectionId) {
      // Hide all sections
      document.querySelectorAll(".content-section").forEach((section) => {
          section.style.display = "none";
      });

      // Show the selected section
      const targetSection = document.getElementById(sectionId);
      targetSection.style.display = "block";

      // Special handling for the Future Events section (render calendar)
      if (sectionId === "future-section" && !calendarRendered) {
          renderCalendar();
      }
  }

  // Add click event listeners to menu items
  document.querySelectorAll("aside a").forEach((link) => {
      link.addEventListener("click", function (e) {
          e.preventDefault(); // Prevent the default link behavior
          const sectionId = this.getAttribute("data-section"); // Get section ID from data attribute
          showSection(sectionId); // Show the selected section
      });
  });

  // Show the default section (Dashboard)
  showSection("dashboard-section");
 

  // Image carousel functionality
  const images = document.querySelectorAll(".cdm-shops img");
  let currentIndex = 0;

  // Function to change images
  function changeImage() {
      // Hide all images
      images.forEach((img) => img.classList.remove("active"));

      // Show the next image
      currentIndex = (currentIndex + 1) % images.length;
      images[currentIndex].classList.add("active");
  }

  // Start the image change interval (every 3 seconds)
  setInterval(changeImage, 3000);

  // Initialize the first image
  if (images.length > 0) {
      images[currentIndex].classList.add("active");
  }

  // Dropdown toggle
  function toggleDropdown() {
      document.getElementById("dropdown").classList.toggle("show");
  }

  // Close the dropdown if the user clicks outside of it
  window.onclick = function (event) {
      if (!event.target.matches(".user-icon")) {
          const dropdowns = document.getElementsByClassName("dropdown-content");
          for (let i = 0; i < dropdowns.length; i++) {
              const openDropdown = dropdowns[i];
              if (openDropdown.classList.contains("show")) {
                  openDropdown.classList.remove("show");
              }
          }
      }
  };



  // Initialize the mini calendar
  const miniCalendarEl = document.getElementById("calendar-mini");
  if (miniCalendarEl) {
    const miniCalendar = new FullCalendar.Calendar(miniCalendarEl, {
      initialView: "dayGridMonth",    // Ensure full visibility
      headerToolbar: false,     // Disable header for compact style
      events: [],               // Add events if necessary
      editable: false,          // Disable editing for mini calendar
    });
    miniCalendar.render();
  }
});


  function toggleDropdown() {
    document.getElementById("dropdown").classList.toggle("show");
  }
  
  // Close the dropdown if the user clicks outside of it
  window.onclick = function(event) {
    if (!event.target.matches('.user-icon')) {
      var dropdowns = document.getElementsByClassName("dropdown-content");
      for (var i = 0; i < dropdowns.length; i++) {
        var openDropdown = dropdowns[i];
        if (openDropdown.classList.contains('show')) {
          openDropdown.classList.remove('show');
        }
      }
    }
  };
 
function changeImage() {
  // Hide all images
  images.forEach(img => img.classList.remove('active'));
  
  // Show the next image
  currentIndex = (currentIndex + 1) % images.length;
  images[currentIndex].classList.add('active');
}

// Start the image change interval (every 3 seconds)
setInterval(changeImage, 3000);

// Initialize the first image
changeImage();


