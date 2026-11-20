document.addEventListener("DOMContentLoaded", () => {
    const periodSelect = document.getElementById("periodSelect");

    const totalPendapatan = document.getElementById("totalPendapatan");
    const totalPesanan = document.getElementById("totalPesanan");
    const totalPelanggan = document.getElementById("totalPelanggan");

    const infoPendapatan = document.getElementById("infoPendapatan");
    const infoPesanan = document.getElementById("infoPesanan");
    const infoPelanggan = document.getElementById("infoPelanggan");

    const chartTitle = document.getElementById("chartTitle");
    const barGroup = document.getElementById("barGroup");
    const yAxis = document.getElementById("yAxis");
    const chartMessage = document.getElementById("chartMessage");

    const chartRevenue = document.getElementById("chartRevenue");
    const chartOrders = document.getElementById("chartOrders");

    const formatAngka = (value) =>
        new Intl.NumberFormat("id-ID").format(value);

    const formatRupiah = (value) =>
        new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            maximumFractionDigits: 0
        }).format(value);

    const formatTanggal = (value) => {
        const date = new Date(`${value}T00:00:00`);

        return date.toLocaleDateString("id-ID", {
            day: "2-digit",
            month: "short",
            year: "numeric"
        });
    };

    async function loadDashboard() {
        chartMessage.textContent = "Mengambil data dari database...";
        barGroup.innerHTML = "";
        yAxis.innerHTML = "";

        try {
            const response = await fetch(
                `../PHP/dashboard_data.php?period=${encodeURIComponent(periodSelect.value)}`,
                {
                    headers: {
                        Accept: "application/json"
                    },
                    cache: "no-store"
                }
            );

            const data = await response.json();

            if (response.status === 401) {
                window.location.href = "../HTML/login_admin.html";
                return;
            }

            if (!response.ok || !data.success) {
                throw new Error(
                    data.message || "Data dashboard gagal dimuat."
                );
            }

            // Statistik
            totalPendapatan.textContent =
                formatRupiah(data.total_pendapatan);

            totalPesanan.textContent =
                formatAngka(data.total_pesanan);

            totalPelanggan.textContent =
                formatAngka(data.total_pelanggan);

            infoPendapatan.textContent =
                "Akumulasi seluruh pesanan";

            infoPesanan.textContent =
                "Seluruh pesanan tercatat";

            infoPelanggan.textContent =
                "Pelanggan terdaftar";

            // Informasi grafik
            chartRevenue.textContent =
                formatRupiah(data.total_pendapatan);

            chartOrders.textContent =
                formatAngka(data.total_pesanan);

            chartTitle.textContent =
                `Penjualan ${formatTanggal(data.start_date)} - ${formatTanggal(data.end_date)}`;

            renderChart(data.sales);

        } catch (error) {
            console.error("Dashboard error:", error);

            chartMessage.textContent =
                error.message || "Gagal memuat data dashboard.";

            barGroup.innerHTML =
                "<p>Data penjualan tidak dapat dimuat.</p>";
        }
    }

    function renderChart(sales) {
        const maxSales = Math.max(
            0,
            ...sales.map(item => Number(item.total))
        );

        const step = maxSales > 0
            ? Math.ceil(maxSales / 4 / 1000) * 1000 || maxSales
            : 1000;

        const axisMax = maxSales > 0
            ? Math.ceil(maxSales / step) * step
            : 1000;

        // Sumbu Y
        yAxis.innerHTML = "";

        for (let i = 4; i >= 0; i--) {
            const label = document.createElement("span");

            label.textContent =
                formatAngka((axisMax / 4) * i);

            yAxis.appendChild(label);
        }

        // Batang grafik
        barGroup.innerHTML = "";

        sales.forEach(item => {
            const value = Number(item.total);

            const height = maxSales > 0
                ? (value / axisMax) * 100
                : 0;

            const column = document.createElement("div");
            column.className = "bar-column";

            const bar = document.createElement("div");
            bar.className = "bar";

            bar.style.height = `${height}%`;
            bar.title = `${item.label}: ${formatRupiah(value)}`;

            const label = document.createElement("span");
            label.textContent = item.label;

            column.appendChild(bar);
            column.appendChild(label);

            barGroup.appendChild(column);
        });

        chartMessage.textContent = maxSales === 0
            ? "Belum ada transaksi pada periode ini."
            : "Arahkan kursor ke batang grafik untuk melihat nilai penjualan.";
    }

    periodSelect.addEventListener("change", loadDashboard);

    loadDashboard();
});