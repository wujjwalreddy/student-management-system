document.addEventListener("DOMContentLoaded", () => {
  // Check if user is logged in and has admin role
  const user = JSON.parse(localStorage.getItem("user") || "{}")
  const role = localStorage.getItem("role")

  if (!user || !role || role !== "admin") {
    window.location.href = "../index.html"
    return
  }

  // Set user info
  document.querySelector(".user-info h4").textContent = user.fullname || "Admin User"

  // Load dashboard data
  loadDashboardData()

  // Function to load dashboard data
  function loadDashboardData() {
    fetch("../php/get_dashboard_data.php")
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          updateDashboardCards(data.data)
          updateRecentEnrollments(data.data.recentEnrollments)
          updateRecentActivities(data.data.recentActivities)
        } else {
          console.error("Failed to load dashboard data:", data.message)
        }
      })
      .catch((error) => {
        console.error("Error:", error)
      })
  }

  // Function to update dashboard cards
  function updateDashboardCards(data) {
    // Update students card
    const studentsCard = document.querySelector(".card:nth-child(1) .card-value")
    if (studentsCard) {
      studentsCard.textContent = data.totalStudents || 0
    }

    // Update faculty card
    const facultyCard = document.querySelector(".card:nth-child(2) .card-value")
    if (facultyCard) {
      facultyCard.textContent = data.totalFaculty || 0
    }

    // Update courses card
    const coursesCard = document.querySelector(".card:nth-child(3) .card-value")
    if (coursesCard) {
      coursesCard.textContent = data.totalCourses || 0
    }

    // Update assignments card
    const assignmentsCard = document.querySelector(".card:nth-child(4) .card-value")
    if (assignmentsCard) {
      assignmentsCard.textContent = data.totalAssignments || 0
    }
  }

  // Function to update recent enrollments table
  function updateRecentEnrollments(enrollments) {
    const enrollmentsTable = document.getElementById("recentEnrollments")
    if (!enrollmentsTable || !enrollments || !enrollments.length) return

    enrollmentsTable.innerHTML = ""

    enrollments.forEach((enrollment) => {
      const row = document.createElement("tr")
      row.innerHTML = `
        <td>${enrollment.student_id}</td>
        <td>${enrollment.student_name}</td>
        <td>${enrollment.course_name}</td>
        <td>${enrollment.enrollment_date}</td>
        <td>${enrollment.status}</td>
        <td>
          <div class="action-buttons">
            <button class="btn btn-view" data-id="${enrollment.id}"><i class="fas fa-eye"></i></button>
            <button class="btn btn-edit" data-id="${enrollment.id}"><i class="fas fa-edit"></i></button>
            <button class="btn btn-delete" data-id="${enrollment.id}"><i class="fas fa-trash"></i></button>
          </div>
        </td>
      `
      enrollmentsTable.appendChild(row)
    })

    // Add event listeners to action buttons
    addActionButtonListeners()
  }

  // Function to update recent activities table
  function updateRecentActivities(activities) {
    const activitiesTable = document.getElementById("recentActivities")
    if (!activitiesTable || !activities || !activities.length) return

    activitiesTable.innerHTML = ""

    activities.forEach((activity) => {
      const row = document.createElement("tr")
      row.innerHTML = `
        <td>${activity.user} (${activity.role})</td>
        <td>${activity.activity}</td>
        <td>${activity.timestamp}</td>
      `
      activitiesTable.appendChild(row)
    })
  }

  // Function to add event listeners to action buttons
  function addActionButtonListeners() {
    // View button
    document.querySelectorAll(".btn-view").forEach((button) => {
      button.addEventListener("click", function () {
        const id = this.getAttribute("data-id")
        alert(`View enrollment with ID: ${id}`)
      })
    })

    // Edit button
    document.querySelectorAll(".btn-edit").forEach((button) => {
      button.addEventListener("click", function () {
        const id = this.getAttribute("data-id")
        alert(`Edit enrollment with ID: ${id}`)
      })
    })

    // Delete button
    document.querySelectorAll(".btn-delete").forEach((button) => {
      button.addEventListener("click", function () {
        const id = this.getAttribute("data-id")
        if (confirm("Are you sure you want to delete this enrollment?")) {
          alert(`Delete enrollment with ID: ${id}`)
        }
      })
    })
  }

  // Logout functionality
  const logoutLink = document.querySelector('a[href="../index.html"]')
  if (logoutLink) {
    logoutLink.addEventListener("click", (e) => {
      e.preventDefault()
      localStorage.removeItem("user")
      localStorage.removeItem("role")
      window.location.href = "../index.html"
    })
  }
})

