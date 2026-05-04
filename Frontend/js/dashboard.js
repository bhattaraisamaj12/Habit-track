function toggleDropdown() {
    const dropdown = document.getElementById("dropdown");
    dropdown.style.display =
        dropdown.style.display === "block" ? "none" : "block";
}

window.addEventListener("click", function (e) {
    if (!e.target.closest(".profile")) {
        const dropdown = document.getElementById("dropdown");
        if (dropdown) dropdown.style.display = "none";
    }
});


// NOTIFICATIONS
if (Notification.permission !== "granted") {
    Notification.requestPermission();
}

// ADD HABIT
document.getElementById("habitForm").addEventListener("submit", function (e) {
    e.preventDefault();

    let formData = new FormData(this);

    fetch("/habit-tracker/backend/habits/add.php", {
        method: "POST",
        body: formData
    })
        .then(res => res.text())
        .then(data => {
            console.log("Response:", data);

            if (data.trim() === "success") {
                document.getElementById("msg").innerText = "Habit added!";
                this.reset();
                loadHabits();
            } else {
                document.getElementById("msg").innerText = data;
            }
        });
});


// HABITS
let hasHabits = false;
let loopStarted = false;

function formatTime(t) {
    if (!t) return "";
    let [h, m] = t.split(":");
    return `${h}:${m}`;
}

// LOAD HABITS 
function loadHabits() {
    fetch("/habit-tracker/backend/habits/get.php")
        .then(res => res.json())
        .then(data => {

            let habits = data.habits;
            hasHabits = habits.length > 0;
            let mainStreak = data.main_streak;
            let consistency = data.consistency;
            let performance = data.performance;

            document.querySelector("#mainStreak p").innerText =
                " 🔥 " + mainStreak + " days";

            let value = consistency || 0;

            let donutEl = document.querySelector(".donut");
            let textEl = document.getElementById("consistencyValue");

            if (textEl) textEl.innerText = value + "%";
            if (donutEl) donutEl.style.setProperty("--progress", value + "%");

            let perfEl = document.querySelector("#performance p");
            if (perfEl) perfEl.innerText = performance;

            let html = "";

            habits.forEach(habit => {

                let actionHTML = habit.status == 1
                    ? `<span class="done-text">${habit.name} done for today</span>`
                    : `<button onclick="toggleComplete(${habit.id})" class="complete-btn">Mark Done</button>`;

                html += `
                <div class="habit-card ${habit.status == 1 ? "done" : ""}">
                    <div>
                        <span>${habit.name}</span><br>
                        <small>
                            ${habit.start_time && habit.end_time
                        ? `${formatTime(habit.start_time)} - ${formatTime(habit.end_time)}`
                        : "No time set"}
                        </small>
                        <small>🔥 ${habit.streak} day streak</small>
                    </div>

                    <div class="actions">
                        ${actionHTML}
                        <button onclick="deleteHabit(${habit.id})" class="delete-btn">Delete</button>
                    </div>
                </div>`;
            });

            document.getElementById("habitList").innerHTML = html;
            if (!loopStarted) startReminderLoop();
        });
}

// HABIT ACTIONS
function deleteHabit(id) {
    fetch("/habit-tracker/backend/habits/delete.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "id=" + id
    })
        .then(res => res.text())
        .then(data => {
            if (data.trim() === "success") {
                loadHabits();
            } else {
                alert("Delete failed: " + data);
            }
        });
}

function toggleComplete(id) {
    let formData = new FormData();
    formData.append("habit_id", id);

    fetch("/habit-tracker/backend/habits/toggle_complete.php", {
        method: "POST",
        body: formData
    })
        .then(() => loadHabits());
}

// REMINDERS
let shownReminders = new Set(
    JSON.parse(localStorage.getItem("shownReminders") || "[]")
);

function checkReminders() {
    fetch("../backend/reminders/check.php")
        .then(res => res.json())
        .then(data => {

            data.forEach(r => {

                if (!shownReminders.has(r.id)) {

                    if (r.type === "end") {

                        let notif = new Notification("Habit Check ⏳", {
                            body: r.message,
                            icon: "https://cdn-icons-png.flaticon.com/512/1827/1827349.png"
                        });

                        notif.onclick = () => {
                            if (confirm(r.message)) {
                                markDone(r.habit_id);
                            }
                        };

                    } else {
                        new Notification("Habit Reminder 🔔", {
                            body: r.message
                        });
                    }

                    shownReminders.add(r.id);
                    localStorage.setItem(
                        "shownReminders",
                        JSON.stringify([...shownReminders])
                    );
                }
            });
        });
}

function markDone(habit_id) {
    fetch("/habit-tracker/backend/habits/toggle_complete.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "habit_id=" + habit_id
    })
        .then(() => loadHabits());
}

function startReminderLoop() {

    loopStarted = true;

    let delay = (60 - new Date().getSeconds()) * 1000;

    setTimeout(() => {

        if (hasHabits) checkReminders();

        setInterval(() => {
            if (hasHabits) checkReminders();
        }, 60000);

    }, delay);
}

loadHabits();