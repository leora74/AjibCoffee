// Ajib Coffee — halaman statis untuk latihan frontend.
// Tombol sengaja belum menjalankan aksi, sesuai permintaan.

document.addEventListener("DOMContentLoaded", () => {
  // Tandai tautan navigasi saat diklik (hanya efek visual).
  const navLinks = document.querySelectorAll(".nav-link");

  navLinks.forEach((link) => {
    link.addEventListener("click", () => {
      navLinks.forEach((item) => item.classList.remove("active"));
      link.classList.add("active");
    });
  });

  // menambahkan alert di main page
  const params = new URLSearchParams(window.location.search);

if (params.get("status") === "success") {
    alert("Login berhasil! Selamat datang.");

    window.history.replaceState(
        {},
        document.title,
        window.location.pathname
    );
}
});
