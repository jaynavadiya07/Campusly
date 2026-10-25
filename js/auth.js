document.addEventListener("DOMContentLoaded", function () {
  const loggedIn = localStorage.getItem("studentLoggedIn") === "true";
  const currentPath = window.location.pathname;
  const currentPage = currentPath.substring(currentPath.lastIndexOf("/") + 1) || "index.html";

  // List of pages requiring login
  const protectedPages = [
    "dashboard.html",
    "timetable.html",
    "material.html",
    "events.html",
    "profile.html"
  ];

  // Route Guard
  if (protectedPages.includes(currentPage) && !loggedIn) {
    alert("Access Denied: Please log in or register first to access this page.");
    window.location.href = "login.html";
    return;
  }

  // Update primary navigation dynamically
  const navContainer = document.querySelector("nav.primary-nav ul");
  if (navContainer) {
    let navItems = "";

    if (loggedIn) {
      // Logged In Menu
      navItems = `
        <li><a href="index.html" class="${currentPage === 'index.html' ? 'active-page' : ''}">Home</a></li>
        <li><a href="dashboard.html" class="${currentPage === 'dashboard.html' ? 'active-page' : ''}">Dashboard</a></li>
        <li><a href="timetable.html" class="${currentPage === 'timetable.html' ? 'active-page' : ''}">Timetable</a></li>
        <li><a href="material.html" class="${currentPage === 'material.html' ? 'active-page' : ''}">Material</a></li>
        <li><a href="events.html" class="${currentPage === 'events.html' ? 'active-page' : ''}">Events</a></li>
        <li><a href="profile.html" class="${currentPage === 'profile.html' ? 'active-page' : ''}">Profile</a></li>
        <li><a href="contact.html" class="${currentPage === 'contact.html' ? 'active-page' : ''}">Contact</a></li>
        <li><a href="about.html" class="${currentPage === 'about.html' ? 'active-page' : ''}">About</a></li>
        <li><a href="#" id="logout-btn" class="logout-link">Log Out</a></li>
      `;
    } else {
      // Logged Out Menu
      navItems = `
        <li><a href="index.html" class="${currentPage === 'index.html' ? 'active-page' : ''}">Home</a></li>
        <li><a href="register.html" class="${currentPage === 'register.html' ? 'active-page' : ''}">Register</a></li>
        <li><a href="login.html" class="${currentPage === 'login.html' ? 'active-page' : ''}">Login</a></li>
        <li><a href="contact.html" class="${currentPage === 'contact.html' ? 'active-page' : ''}">Contact</a></li>
        <li><a href="about.html" class="${currentPage === 'about.html' ? 'active-page' : ''}">About</a></li>
      `;
    }
    navContainer.innerHTML = navItems;

    // Attach active page highlighting
    const activeLink = navContainer.querySelector(".active-page");
    if (activeLink) {
      activeLink.setAttribute("aria-current", "page");
    }
  }

  // Handle Logout
  const logoutBtn = document.getElementById("logout-btn");
  if (logoutBtn) {
    logoutBtn.addEventListener("click", function (e) {
      e.preventDefault();
      localStorage.removeItem("studentLoggedIn");
      alert("You have logged out successfully.");
      window.location.href = "index.html";
    });
  }

  // Handle Login & Registration form submissions dynamically
  const loginForm = document.getElementById("login-form");
  if (loginForm) {
    loginForm.addEventListener("submit", function (e) {
      e.preventDefault();
      localStorage.setItem("studentLoggedIn", "true");
      alert("Sign-in successful! Welcome to the Campusly Portal.");
      window.location.href = "dashboard.html";
    });
  }

  const registerForm = document.getElementById("register-form");
  if (registerForm) {
    registerForm.addEventListener("submit", function (e) {
      e.preventDefault();
      localStorage.setItem("studentLoggedIn", "true");
      alert("Registration successful! Welcome to the Campusly Portal.");
      window.location.href = "dashboard.html";
    });
  }

  // Handle Tab Switching for Unified Login/Register Panels
  const tabButtons = document.querySelectorAll(".tab-btn");
  const tabPanes = document.querySelectorAll(".tab-pane");

  tabButtons.forEach(button => {
    button.addEventListener("click", function () {
      const targetPaneId = this.getAttribute("data-target");

      // Deactivate all tabs
      tabButtons.forEach(btn => btn.classList.remove("active-tab"));
      // Deactivate all panes
      tabPanes.forEach(pane => pane.classList.remove("active-pane"));

      // Activate current tab & pane
      this.classList.add("active-tab");
      const targetPane = document.getElementById(targetPaneId);
      if (targetPane) {
        targetPane.classList.add("active-pane");
      }
    });
  });

  // Handle Dashboard Spotlight Modal Popup
  const welcomeModal = document.getElementById("welcome-modal");
  if (welcomeModal && currentPage === "dashboard.html") {
    const hasModalBeenShown = sessionStorage.getItem("welcomeModalShown") === "true";
    if (!hasModalBeenShown) {
      // Delay slightly for transition impact
      setTimeout(() => {
        welcomeModal.classList.add("show-modal");
      }, 500);
    }

    const closeModal = () => {
      welcomeModal.classList.remove("show-modal");
      sessionStorage.setItem("welcomeModalShown", "true");
    };

    const closeBtn = document.getElementById("close-modal-btn");
    const dismissBtn = document.getElementById("dismiss-modal-btn");

    if (closeBtn) closeBtn.addEventListener("click", closeModal);
    if (dismissBtn) dismissBtn.addEventListener("click", closeModal);
  }
});
