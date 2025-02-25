document.addEventListener("DOMContentLoaded", function () {
  // Login Form
  const loginForm = document.getElementById("loginForm");
  if (loginForm) {
    loginForm.addEventListener("submit", function (e) {
      e.preventDefault();

      // Get form values
      const email = document.getElementById("email").value;
      const password = document.getElementById("password").value;

      // Simple validation (email/password)
      if (email === "" || password === "") {
        alert("Please fill in both fields");
        return;
      }

      // Simulated login process
      if (email === "admin@votingportal.com" && password === "admin123") {
        alert("Login successful");
        // Redirect to dashboard
        window.location.href = "php/dashboard.php";
      } else {
        alert("Invalid email or password");
      }
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    // Registration Form
    const registerForm = document.getElementById("registerForm");
    if (registerForm) {
      registerForm.addEventListener("submit", function (e) {
        e.preventDefault();

        const name = document.getElementById("name").value;
        const email = document.getElementById("email").value;
        const password = document.getElementById("password").value;
        const confirmPassword =
          document.getElementById("confirmPassword").value;

        // Ensure all fields are filled out
        if (
          name === "" ||
          email === "" ||
          password === "" ||
          confirmPassword === ""
        ) {
          alert("Please fill in all the fields");
          return;
        }

        // Password match validation
        if (password !== confirmPassword) {
          alert("Passwords do not match");
          return;
        }

        // Send the data to the backend via AJAX
        fetch("/php/register.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: new URLSearchParams({
            name: name,
            email: email,
            password: password,
            role: "user", // Assuming role is hardcoded for now
          }),
        })
          .then((response) => response.text())
          .then((data) => {
            if (data.includes("Registration failed")) {
              alert("Registration failed!");
            } else {
              alert(`Registration successful for ${name}`);
              // Redirect to login page after successful registration
              window.location.href = "../php/login.php";
            }
          })
          .catch((error) => {
            alert("An error occurred. Please try again.");
            console.error("Error:", error);
          });
      });
    }
  });

  //Rendering Election Type and Admin ID After Selecting the role

  function toggleElectionType() {
    const role = document.getElementById("role").value;
    const additionalFields = document.getElementById("additional-fields");

    // Clear the additional fields
    additionalFields.innerHTML = "";

    if (role === "voter") {
      additionalFields.innerHTML = `
                <div class="mb-3">
                    <label for="election-type" class="form-label">Election Type</label>
                    <select class="form-control" id="election-type" name="election-type" onchange="handleElectionTypeChange()" required>
                        <option value="">Select Election Type</option>
                        <option value="college">School/College Level Election</option>
                        <option value="local">Local Level Election</option>
                        <option value="org">Organizational Level Election</option>
                    </select>
                </div>
                <div id="election-specific-fields"></div>
            `;
    } else if (role === "admin") {
      additionalFields.innerHTML = `
                <div class="mb-3">
                    <label for="admin-id" class="form-label">Admin ID</label>
                    <input type="text" class="form-control" id="admin-id" name="admin-id" placeholder="Enter your Admin ID" required />
                </div>
            `;
    }
  }

  function handleElectionTypeChange() {
    const electionType = document.getElementById("election-type").value;
    const electionSpecificFields = document.getElementById(
      "election-specific-fields"
    );

    // Clear previous fields
    electionSpecificFields.innerHTML = "";

    if (electionType === "college") {
      electionSpecificFields.innerHTML = `
                <div class="mb-3">
                    <label for="gender" class="form-label">Gender</label>
                    <select class="form-control" id="gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="student-id" class="form-label">Student ID</label>
                    <input type="text" class="form-control" id="student-id" name="student-id" placeholder="Enter your Student ID" required />
                </div>
            `;
    } else if (electionType === "local") {
      electionSpecificFields.innerHTML = `
                <div class="mb-3">
                    <label for="gender" class="form-label">Gender</label>
                    <select class="form-control" id="gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <input type="text" class="form-control" id="address" name="address" placeholder="Enter your Address" required />
                </div>
                <div class="mb-3">
                    <label for="local-id" class="form-label">Local ID</label>
                    <input type="text" class="form-control" id="local-id" name="local-id" placeholder="Enter your Local ID" required />
                </div>
            `;
    } else if (electionType === "org") {
      electionSpecificFields.innerHTML = `
                <div class="mb-3">
                    <label for="gender" class="form-label">Gender</label>
                    <select class="form-control" id="gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <input type="text" class="form-control" id="address" name="address" placeholder="Enter your Address" required />
                </div>
                <div class="mb-3">
                    <label for="employee-id" class="form-label">Employee ID</label>
                    <input type="text" class="form-control" id="employee-id" name="employee-id" placeholder="Enter your Employee ID" required />
                </div>
            `;
    }
  }

  // Dashboard: Simulate fetching data
  const userPanel = document.getElementById("userPanel");
  const adminPanel = document.getElementById("adminPanel");
  const userName = document.getElementById("userName");

  // Simulated user data
  const isAdmin = true; // Simulate admin status (you can toggle this to test)
  const user = {
    name: "Admin User",
    elections: {
      ongoing: ["Presidential Election 2024", "City Council Election 2024"],
      expired: ["General Election 2023"],
    },
  };

  // If we're on the dashboard, populate the data
  if (userPanel || adminPanel) {
    userName.innerText = user.name;

    // Populate ongoing and expired elections for users
    const ongoingElections = document.getElementById("ongoingElections");
    const expiredElections = document.getElementById("expiredElections");

    user.elections.ongoing.forEach((election) => {
      ongoingElections.innerHTML += `<p>${election}</p>`;
    });

    user.elections.expired.forEach((election) => {
      expiredElections.innerHTML += `<p>${election}</p>`;
    });

    // Show the admin panel if the user is an admin
    if (isAdmin && adminPanel) {
      adminPanel.style.display = "block";
    } else if (adminPanel) {
      adminPanel.style.display = "none";
    }
  }
});

document.addEventListener("DOMContentLoaded", function () {
  const electionList = document.querySelector(".list-group");

  // Simulated elections data (you can replace this with actual data)
  const elections = [
    { title: "Presidential Election 2024", status: "Ongoing" },
    { title: "City Council Election 2024", status: "Upcoming" },
    { title: "General Election 2023", status: "Completed" },
  ];

  // Populate the election list
  if (electionList) {
    elections.forEach((election) => {
      electionList.innerHTML += `
                <a href="#" class="list-group-item list-group-item-action">
                    ${election.title} - ${election.status}
                </a>
            `;
    });
  }
});

document.getElementById("voteForm").addEventListener("submit", function (e) {
  e.preventDefault();
  const selectedCandidate = document.querySelector(
    'input[name="candidate"]:checked'
  ).value;
  if (selectedCandidate) {
    // Send the selected candidate to the server via AJAX (or form submission)
    alert(`You voted for ${selectedCandidate}`);
    // Optionally, redirect to a confirmation page
  } else {
    alert("Please select a candidate before submitting");
  }
});
