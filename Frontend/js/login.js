document.getElementById("loginForm").addEventListener("submit", function (e) {
    e.preventDefault();

    let formData = new FormData(this);
    fetch("/habit-tracker/backend/auth/login.php", {
        method: "POST",
        body: formData
    })
        .then(res => {
            if (!res.ok) {
                throw new Error("HTTP error " + res.status);
            }
            return res.text();
        })
        .then(data => {

            console.log("Response:", data);

            if (data.trim() === "success") {
                window.location.href = "/habit-tracker/pages/dashboard.php";
            } else {
                document.getElementById("response").innerText = data;
            }

        })
        .catch(err => {
            console.error(err);
            document.getElementById("response").innerText = "Server error";
        });
});