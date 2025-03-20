document.addEventListener("DOMContentLoaded", () => {
  // Add Faculty Form Submission
  const addFacultyForm = document.getElementById("addFacultyForm")
  if (addFacultyForm) {
    addFacultyForm.addEventListener("submit", (e) => {
      e.preventDefault()

      // Get form data
      const formData = new FormData(addFacultyForm)

      // Send data to server
      fetch("../php/add_faculty.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            alert("Faculty added successfully!")
            addFacultyForm.reset()
            // Refresh faculty list
            loadFaculty()
          } else {
            alert(data.message || "Failed to add faculty.")
          }
        })
        .catch((error) => {
          console.error("Error:", error)
          alert("An error occurred. Please try again.")
        })
    })
  }

  // Search functionality
  const searchFaculty = document.getElementById("searchFaculty")
  if (searchFaculty) {
    searchFaculty.addEventListener("input", function () {
      const searchTerm = this.value.toLowerCase()
      const facultyRows = document.querySelectorAll("#facultyList tr")

      facultyRows.forEach((row) => {
        const text = row.textContent.toLowerCase()
        if (text.includes(searchTerm)) {
          row.style.display = ""
        } else {
          row.style.display = "none"
        }
      })
    })
  }

  // Load faculty from server
  function loadFaculty() {
    fetch("../php/get_faculty.php")
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          const facultyList = document.getElementById("facultyList")
          if (facultyList) {
            facultyList.innerHTML = ""

            data.faculty.forEach((faculty) => {
              const row = document.createElement("tr")
              row.innerHTML = `
                            <td>${faculty.id}</td>
                            <td>${faculty.name}</td>
                            <td>${faculty.email}</td>
                            <td>${faculty.department}</td>
                            <td>${faculty.designation}</td>
                            <td>${faculty.status}</td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-view" data-id="${faculty.id}"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-edit" data-id="${faculty.id}"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-delete" data-id="${faculty.id}"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        `
              facultyList.appendChild(row)
            })

            // Add event listeners to action buttons
            addActionButtonListeners()
          }
        } else {
          console.error("Failed to load faculty:", data.message)
        }
      })
      .catch((error) => {
        console.error("Error:", error)
      })
  }

  // Add event listeners to action buttons
  function addActionButtonListeners() {
    // View button
    document.querySelectorAll(".btn-view").forEach((button) => {
      button.addEventListener("click", function () {
        const facultyId = this.getAttribute("data-id")
        viewFaculty(facultyId)
      })
    })

    // Edit button
    document.querySelectorAll(".btn-edit").forEach((button) => {
      button.addEventListener("click", function () {
        const facultyId = this.getAttribute("data-id")
        editFaculty(facultyId)
      })
    })

    // Delete button
    document.querySelectorAll(".btn-delete").forEach((button) => {
      button.addEventListener("click", function () {
        const facultyId = this.getAttribute("data-id")
        deleteFaculty(facultyId)
      })
    })
  }

  // View faculty details
  function viewFaculty(facultyId) {
    fetch(`../php/get_faculty.php?id=${facultyId}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Display faculty details in a modal or redirect to details page
          alert(
            `Faculty Details:\nID: ${data.faculty.id}\nName: ${data.faculty.name}\nEmail: ${data.faculty.email}\nDepartment: ${data.faculty.department}\nDesignation: ${data.faculty.designation}\nStatus: ${data.faculty.status}`,
          )
        } else {
          alert(data.message || "Failed to load faculty details.")
        }
      })
      .catch((error) => {
        console.error("Error:", error)
        alert("An error occurred. Please try again.")
      })
  }

  // Edit faculty
  function editFaculty(facultyId) {
    // Redirect to edit page or show edit form
    alert(`Edit faculty with ID: ${facultyId}`)
  }

  // Delete faculty
  function deleteFaculty(facultyId) {
    if (confirm("Are you sure you want to delete this faculty?")) {
      fetch(`../php/delete_faculty.php?id=${facultyId}`, {
        method: "DELETE",
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            alert("Faculty deleted successfully!")
            // Refresh faculty list
            loadFaculty()
          } else {
            alert(data.message || "Failed to delete faculty.")
          }
        })
        .catch((error) => {
          console.error("Error:", error)
          alert("An error occurred. Please try again.")
        })
    }
  }

  // Load faculty when page loads
  loadFaculty()
})

