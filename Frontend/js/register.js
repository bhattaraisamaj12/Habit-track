document.getElementById("registerForm").addEventListener("submit", function (e) {
    e.preventDefault();

    let password = this.password.value;

    // Strong password validation
    let regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/;

    if (!regex.test(password)) {
        document.getElementById("response").innerText =
            "Password must be 8+ chars with uppercase, lowercase, number & special char.";
        return;
    }

    let formData = new FormData(this);

    fetch("/habit-tracker/backend/auth/register.php", {
        method: "POST",
        body: formData
    })
        .then(res => res.text())
        .then(data => {
            if (data === "success") {
                window.location.href = "/habit-tracker/pages/login.html";
            } else {
                document.getElementById("response").innerText = data;
            }
        })
        .catch(err => {
            document.getElementById("response").innerText = "Something went wrong";
        });
});