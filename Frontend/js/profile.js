// LOAD PROFILE
function loadProfile() {
    fetch("../backend/habits/get.php")
        .then(res => res.json())
        .then(data => {

            document.getElementById("username").innerText = data.username;
            document.getElementById("email").innerText = data.email;

            // format date nicely
            let date = new Date(data.created_at);
            document.getElementById("createdAt").innerText =
                date.toLocaleDateString();

        });
}
// EDIT MODAL
function openEdit() {
    document.getElementById("editModal").style.display = "block";
}

function closeEdit() {
    document.getElementById("editModal").style.display = "none";
}

// SAVE PROFILE
function saveProfile() {
    let username = document.getElementById("newUsername").value;

    let formData = new FormData();
    formData.append("username", username);

    fetch("../backend/profile/update.php", {
        method: "POST",
        body: formData
    })
        .then(res => res.text())
        .then(data => {
            if (data === "success") {
                loadProfile();
                closeEdit();
            }
        });
}
function loadChart() {
    fetch("../backend/habits/get.php")
        .then(res => res.json())
        .then(data => {

            let labels = [];
            let values = [];

            data.weekly.forEach(row => {
                labels.push(
                    new Date(row.day).toLocaleDateString('en-US', { weekday: 'short' })
                );
                values.push(row.completed);
            });

            const ctx = document.getElementById('weeklyChart');

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Habits Completed',
                        data: values
                    }]
                }
            });

        });
}

loadProfile();
loadChart();