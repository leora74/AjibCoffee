document.addEventListener("DOMContentLoaded", () => {

    // Mengambil elemen navbar
    const dashboardLink = document.querySelector(".btn-register-nav");
    const notificationButton = document.getElementById("notificationButton");

    // Navigasi ke halaman Dashboard
    if (dashboardLink) {
        dashboardLink.addEventListener("click", () => {
            window.location.href = "dashboard.html";
        });
    }

    // Interaksi dropdown notifikasi
    if (notificationButton) {
        notificationButton.addEventListener("click", (e) => {
            e.preventDefault();

            const notifMenu = document.getElementById("notificationMenu");

            if (notifMenu) {
                notifMenu.hidden = !notifMenu.hidden;
            }
        });
    }

});