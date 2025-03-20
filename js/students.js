document.addEventListener("DOMContentLoaded", () => {
  // Add Student Form Submission
  const addStudentForm = document.getElementById("addStudentForm")
  if (addStudentForm) {
    addStudentForm.addEventListener("submit", (e) => {
      e.preventDefault()

      // Get form data
      const formData = new FormData(addStudentForm)

      // Send data to server
      fetch("../php/add_student.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            alert("Student added successfully!")
            addStudentForm.reset()
            // Refresh student list
            loadStudents()
          } else {
            alert(data.message || "Failed to add student.")
          }
        })
        .catch((error) => {
          console.error("Error:", error)
          alert("An error occurred. Please try again.")
        })
    })
  }

  // Search functionality
  const searchStudent = document.getElementById("searchStudent")
  if (searchStudent) {
    searchStudent.addEventListener("input", function () {
      const searchTerm = this.value.toLowerCase()
      const studentRows = document.querySelectorAll("#studentsList tr")

      studentRows.forEach((row) => {
        const text = row.textContent.toLowerCase()
        if (text.includes(searchTerm)) {
          row.style.display = ""
        } else {
          row.style.display = "none"
        }
      })
    })
  }

  // Load students from server
  function loadStudents() {
    fetch("../php/get_students.php")
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          const studentsList = document.getElementById("studentsList")
          if (studentsList) {
            studentsList.innerHTML = ""

            data.students.forEach((student) => {
              const row = document.createElement("tr")
              row.innerHTML = `
                            <td>${student.id}</td>
                            <td>${student.name}</td>
                            <td>${student.email}</td>
                            <td>${student.course}</td>
                            <td>${student.enrollment_date}</td>
                            <td>${student.status}</td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-view" data-id="${student.id}"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-edit" data-id="${student.id}"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-delete" data-id="${student.id}"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        `
              studentsList.appendChild(row)
            })

            // Add event listeners to action buttons
            addActionButtonListeners()
          }
        } else {
          console.error("Failed to load students:", data.message)
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
        const studentId = this.getAttribute("data-id")
        viewStudent(studentId)
      })
    })

    // Edit button
    document.querySelectorAll(".btn-edit").forEach((button) => {
      button.addEventListener("click", function () {
        const studentId = this.getAttribute("data-id")
        editStudent(studentId)
      })
    })

    // Delete button
    document.querySelectorAll(".btn-delete").forEach((button) => {
      button.addEventListener("click", function () {
        const studentId = this.getAttribute("data-id")
        deleteStudent(studentId)
      })
    })
  }

  // View student details
  function viewStudent(studentId) {
    fetch(`../php/get_student.php?id=${studentId}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Display student details in a modal or redirect to details page
          alert(
            `Student Details:\nID: ${data.student.id}\nName: ${data.student.name}\nEmail: ${data.student.email}\nCourse: ${data.student.course}\nEnrollment Date: ${data.student.enrollment_date}\nStatus: ${data.student.status}`,
          )
        } else {
          alert(data.message || "Failed to load student details.")
        }
      })
      .catch((error) => {
        console.error("Error:", error)
        alert("An error occurred. Please try again.")
      })
  }

  // Edit student
  function editStudent(studentId) {
    // Redirect to edit page or show edit form
    alert(`Edit student with ID: ${studentId}`)
  }

  // Delete student
  function deleteStudent(studentId) {
    if (confirm("Are you sure you want to delete this student?")) {
      fetch(`../php/delete_student.php?id=${studentId}`, {
        method: "DELETE",
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            alert("Student deleted successfully!")
            // Refresh student list
            loadStudents()
          } else {
            alert(data.message || "Failed to delete student.")
          }
        })
        .catch((error) => {
          console.error("Error:", error)
          alert("An error occurred. Please try again.")
        })
    }
  }

  // Load students when page loads
  loadStudents()
})

