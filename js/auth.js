document.addEventListener("DOMContentLoaded", () => {
  // Toggle between login and signup forms
  const showSignupBtn = document.getElementById("showSignup")
  const showLoginBtn = document.getElementById("showLogin")
  const loginContainer = document.querySelector(".login-container")
  const signupContainer = document.querySelector(".signup-container")

  if (showSignupBtn) {
    showSignupBtn.addEventListener("click", (e) => {
      e.preventDefault()
      loginContainer.style.display = "none"
      signupContainer.style.display = "block"
    })
  }

  if (showLoginBtn) {
    showLoginBtn.addEventListener("click", (e) => {
      e.preventDefault()
      signupContainer.style.display = "none"
      loginContainer.style.display = "block"
    })
  }

  // Login form submission
  const loginForm = document.getElementById("loginForm")
  if (loginForm) {
    loginForm.addEventListener("submit", (e) => {
      e.preventDefault()

      const username = document.getElementById("username").value
      const password = document.getElementById("password").value
      const role = document.getElementById("role").value

      // Disable form during submission
      const submitBtn = loginForm.querySelector("button[type='submit']")
      submitBtn.disabled = true
      submitBtn.textContent = "Logging in..."

      // Create form data
      const formData = new FormData()
      formData.append("username", username)
      formData.append("password", password)
      formData.append("role", role)

      // Send login data to server
      fetch("php/login.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Store user data in localStorage
            localStorage.setItem("user", JSON.stringify(data.user))
            localStorage.setItem("role", data.role)

            // Redirect based on role
            switch (role) {
              case "admin":
                window.location.href = "admin/dashboard.html"
                break
              case "faculty":
                window.location.href = "faculty/dashboard.html"
                break
              case "student":
                window.location.href = "student/dashboard.html"
                break
              default:
                alert("Invalid role")
            }
          } else {
            alert(data.message || "Login failed. Please check your credentials.")
            // Re-enable form
            submitBtn.disabled = false
            submitBtn.textContent = "Login"
          }
        })
        .catch((error) => {
          console.error("Error:", error)
          alert("An error occurred during login. Please try again.")
          // Re-enable form
          submitBtn.disabled = false
          submitBtn.textContent = "Login"
        })
    })
  }

  // Signup form submission
  const signupForm = document.getElementById("signupForm")
  if (signupForm) {
    signupForm.addEventListener("submit", (e) => {
      e.preventDefault()

      const fullname = document.getElementById("fullname").value
      const email = document.getElementById("email").value
      const username = document.getElementById("newUsername").value
      const password = document.getElementById("newPassword").value
      const confirmPassword = document.getElementById("confirmPassword").value
      const role = document.getElementById("signupRole").value

      // Validate password match
      if (password !== confirmPassword) {
        alert("Passwords do not match")
        return
      }

      // Disable form during submission
      const submitBtn = signupForm.querySelector("button[type='submit']")
      submitBtn.disabled = true
      submitBtn.textContent = "Signing up..."

      // Create form data
      const formData = new FormData()
      formData.append("fullname", fullname)
      formData.append("email", email)
      formData.append("username", username)
      formData.append("password", password)
      formData.append("role", role)

      // Send signup data to server
      fetch("php/signup.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            alert("Registration successful! Please login.")
            // Switch to login form
            signupContainer.style.display = "none"
            loginContainer.style.display = "block"
            signupForm.reset()
          } else {
            alert(data.message || "Registration failed. Please try again.")
          }
          // Re-enable form
          submitBtn.disabled = false
          submitBtn.textContent = "Sign Up"
        })
        .catch((error) => {
          console.error("Error:", error)
          alert("An error occurred during registration. Please try again.")
          // Re-enable form
          submitBtn.disabled = false
          submitBtn.textContent = "Sign Up"
        })
    })
  }

  // Check if user is already logged in
  const checkAuth = () => {
    const user = localStorage.getItem("user")
    const role = localStorage.getItem("role")

    if (user && role) {
      // User is logged in, redirect to dashboard
      switch (role) {
        case "admin":
          window.location.href = "admin/dashboard.html"
          break
        case "faculty":
          window.location.href = "faculty/dashboard.html"
          break
        case "student":
          window.location.href = "student/dashboard.html"
          break
      }
    }
  }

  // Check auth on page load
  checkAuth()
})

