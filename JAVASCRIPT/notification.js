/**
 * Logika Navigasi Halaman Notifikasi - AJIB Coffee
 */

document.addEventListener("DOMContentLoaded", () => {
  const dashboardLink = document.querySelector(".nav-dashboard");
  const notificationButton = document.getElementById("notificationButton");

  // Jika tulisan Dashboard diklik, kembali ke halaman dashboard.html
  if (dashboardLink) {
    dashboardLink.addEventListener("click", (e) => {
      e.preventDefault();
      window.location.href = "dashboard.html";
    });
  }

  // Aksi interaksi pada tombol notifikasi
  if (notificationButton) {
    notificationButton.addEventListener("click", () => {
      const notifMenu = document.getElementById("notificationMenu");
      if (notifMenu) {
        notifMenu.hidden = !notifMenu.hidden;
      }
    });
  }
});