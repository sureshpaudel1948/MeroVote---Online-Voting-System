document.addEventListener('DOMContentLoaded', function() {
    // Login Form
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form values
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            // Simple validation (email/password)
            if (email === '' || password === '') {
                alert('Please fill in both fields');
                return;
            }

            // Simulated login process
            if (email === 'admin@votingportal.com' && password === 'admin123') {
                alert('Login successful');
                // Redirect to dashboard
                window.location.href = 'php/dashboard.php';
            } else {
                alert('Invalid email or password');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Registration Form
        const registerForm = document.getElementById('registerForm');
        if (registerForm) {
            registerForm.addEventListener('submit', function(e) {
                e.preventDefault();
    
                const name = document.getElementById('name').value;
                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirmPassword').value;
    
                // Ensure all fields are filled out
                if (name === '' || email === '' || password === '' || confirmPassword === '') {
                    alert('Please fill in all the fields');
                    return;
                }
    
                // Password match validation
                if (password !== confirmPassword) {
                    alert('Passwords do not match');
                    return;
                }
    
                // Send the data to the backend via AJAX
                fetch('/php/register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'name': name,
                        'email': email,
                        'password': password,
                        'role': 'user' // Assuming role is hardcoded for now
                    })
                })
                .then(response => response.text())
                .then(data => {
                    if (data.includes('Registration failed')) {
                        alert('Registration failed!');
                    } else {
                        alert(`Registration successful for ${name}`);
                        // Redirect to login page after successful registration
                        window.location.href = '../php/login.php';
                    }
                })
                .catch(error => {
                    alert('An error occurred. Please try again.');
                    console.error('Error:', error);
                });
            });
        }
    });
    

    // Dashboard: Simulate fetching data
    const userPanel = document.getElementById('userPanel');
    const adminPanel = document.getElementById('adminPanel');
    const userName = document.getElementById('userName');
    
    // Simulated user data
    const isAdmin = true;  // Simulate admin status (you can toggle this to test)
    const user = {
        name: 'Admin User',
        elections: {
            ongoing: ['Presidential Election 2024', 'City Council Election 2024'],
            expired: ['General Election 2023']
        }
    };

    // If we're on the dashboard, populate the data
    if (userPanel || adminPanel) {
        userName.innerText = user.name;

        // Populate ongoing and expired elections for users
        const ongoingElections = document.getElementById('ongoingElections');
        const expiredElections = document.getElementById('expiredElections');

        user.elections.ongoing.forEach(election => {
            ongoingElections.innerHTML += `<p>${election}</p>`;
        });

        user.elections.expired.forEach(election => {
            expiredElections.innerHTML += `<p>${election}</p>`;
        });

        // Show the admin panel if the user is an admin
        if (isAdmin && adminPanel) {
            adminPanel.style.display = 'block';
        } else if (adminPanel) {
            adminPanel.style.display = 'none';
        }
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const electionList = document.querySelector('.list-group');

    // Simulated elections data (you can replace this with actual data)
    const elections = [
        { title: 'Presidential Election 2024', status: 'Ongoing' },
        { title: 'City Council Election 2024', status: 'Upcoming' },
        { title: 'General Election 2023', status: 'Completed' }
    ];

    // Populate the election list
    if (electionList) {
        elections.forEach(election => {
            electionList.innerHTML += `
                <a href="#" class="list-group-item list-group-item-action">
                    ${election.title} - ${election.status}
                </a>
            `;
        });
    }
});

document.getElementById('voteForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const selectedCandidate = document.querySelector('input[name="candidate"]:checked').value;
    if (selectedCandidate) {
        // Send the selected candidate to the server via AJAX (or form submission)
        alert(`You voted for ${selectedCandidate}`);
        // Optionally, redirect to a confirmation page
    } else {
        alert('Please select a candidate before submitting');
    }
});

