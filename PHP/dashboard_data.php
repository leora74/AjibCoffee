
<?php
header('Content-Type: application/json; charset=utf-8');

date_default_timezone_set('Asia/Jakarta');

try {
    // Koneksi database
    $host = 'localhost';
    $dbname = 'ajibcoffee';
    $username = 'root';
    $password = ''; // Sesuaikan jika MySQL lu memakai password

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // Periode grafik: 7, 30, atau 90 hari
    $period = isset($_GET['period'])
        ? (int) $_GET['period']
        : 7;

    if (!in_array($period, [7, 30, 90], true)) {
        $period = 7;
    }

    // Rentang tanggal termasuk hari ini
    $endDate = new DateTimeImmutable('today');

    $startDate = $endDate->modify(
        '-' . ($period - 1) . ' days'
    );

    $start = $startDate->format('Y-m-d');
    $end = $endDate->format('Y-m-d');

    // 1. Mengambil total pendapatan seluruh pesanan
    $queryPendapatan = $pdo->query("
        SELECT COALESCE(SUM(total_harga), 0) AS total_pendapatan
        FROM pesanan
    ");

    $totalPendapatan = (int) $queryPendapatan->fetchColumn();

    // 2. Menghitung seluruh pesanan
    $queryPesanan = $pdo->query("
        SELECT COUNT(*) 
        FROM pesanan
    ");

    $totalPesanan = (int) $queryPesanan->fetchColumn();

    // 3. Menghitung seluruh pelanggan
    $queryPelanggan = $pdo->query("
        SELECT COUNT(*) 
        FROM pelanggan
    ");

    $totalPelanggan = (int) $queryPelanggan->fetchColumn();

    // 4. Mengambil penjualan berdasarkan tanggal
  $querySales = $pdo->prepare("
    SELECT
        DATE(tanggal_pesanan) AS tanggal,
        SUM(total_harga) AS total
    FROM pesanan
    WHERE DATE(tanggal_pesanan) BETWEEN :start_date AND :end_date
    GROUP BY DATE(tanggal_pesanan)
    ORDER BY tanggal ASC
");

    $querySales->execute([
        ':start_date' => $start,
        ':end_date' => $end
    ]);

    $salesDatabase = $querySales->fetchAll();

    // Mengubah hasil query menjadi array berdasarkan tanggal
    $salesMap = [];

    foreach ($salesDatabase as $row) {
        $salesMap[$row['tanggal']] = (int) $row['total'];
    }

    // Membuat data semua tanggal, termasuk yang tidak memiliki transaksi
    $sales = [];

    $dayNames = [
        'Sun' => 'Min',
        'Mon' => 'Sen',
        'Tue' => 'Sel',
        'Wed' => 'Rab',
        'Thu' => 'Kam',
        'Fri' => 'Jum',
        'Sat' => 'Sab'
    ];

    $currentDate = $startDate;

    while ($currentDate <= $endDate) {
        $dateKey = $currentDate->format('Y-m-d');

        $dayName = $dayNames[
            $currentDate->format('D')
        ];

        $sales[] = [
            'label' => $dayName,
            'total' => $salesMap[$dateKey] ?? 0
        ];

        $currentDate = $currentDate->modify('+1 day');
    }

    // Mengirim data ke JavaScript
    echo json_encode([
        'success' => true,
        'total_pendapatan' => $totalPendapatan,
        'total_pesanan' => $totalPesanan,
        'total_pelanggan' => $totalPelanggan,
        'start_date' => $start,
        'end_date' => $end,
        'sales' => $sales
    ]);

} catch (PDOException $error) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Koneksi atau pengambilan data database gagal.',
        'error' => $error->getMessage()
    ]);

} catch (Throwable $error) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan pada dashboard.',
        'error' => $error->getMessage()
    ]);
}
