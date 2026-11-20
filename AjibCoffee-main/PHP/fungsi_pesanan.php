<?php
/**
 * fungsi_pesanan.php
 * Fungsi bersama untuk pesanan, pembayaran, dan struk.
 * Koneksi database memakai conn.php yang sudah ada ($connection).
 */

require_once __DIR__ . '/conn.php';

date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

mysqli_set_charset($connection, 'utf8mb4');

/* Nilai kolom pesanan.status_pesanan */
const STATUS_MENUNGGU = 'menunggu_pembayaran';
const STATUS_DIPROSES = 'diproses';
const STATUS_BATAL    = 'dibatalkan';

/* Pesanan yang belum dibayar dan belum membuat QRIS dibatalkan setelah ... menit */
const BATAS_MENIT_TANPA_PEMBAYARAN = 30;

/* Kesalahan yang pesannya boleh ditampilkan ke pengguna */
class AppException extends Exception
{
}

/* ---------- Respons JSON ---------- */

function json_out(array $data, int $code = 200)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function body_json(): array
{
    $data = json_decode((string) file_get_contents('php://input'), true);
    return is_array($data) ? $data : $_POST;
}

function tangani_error(Throwable $e)
{
    if ($e instanceof AppException) {
        json_out(['success' => false, 'message' => $e->getMessage()], 422);
    }

    error_log(
        'Ajib Coffee error: ' . $e->getMessage() .
        ' | ' . $e->getFile() . ':' . $e->getLine()
    );
    json_out(
        ['success' => false, 'message' => 'Terjadi kesalahan pada server.'],
        500
    );
}

function require_customer(): int
{
    if (empty($_SESSION['idPelanggan'])) {
        json_out([
            'success'        => false,
            'login_required' => true,
            'message'        => 'Silakan login terlebih dahulu.',
        ], 401);
    }
    return (int) $_SESSION['idPelanggan'];
}

/* ---------- Pesanan ---------- */

/* Order ID tampilan, contoh: id 190 -> A0190 */
function kode_order(int $idPesanan): string
{
    return 'A' . str_pad((string) $idPesanan, 4, '0', STR_PAD_LEFT);
}

/* Pesanan milik pelanggan (null bila bukan miliknya / tidak ada) */
function ambil_pesanan_milik(mysqli $c, int $idPesanan, int $idPelanggan): ?array
{
    $stmt = $c->prepare(
        'SELECT * FROM pesanan WHERE id_pesanan = ? AND idPelanggan = ? LIMIT 1'
    );
    $stmt->bind_param('ii', $idPesanan, $idPelanggan);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function ambil_pembayaran(mysqli $c, int $idPesanan): ?array
{
    $stmt = $c->prepare('SELECT * FROM pembayaran WHERE id_pesanan = ? LIMIT 1');
    $stmt->bind_param('i', $idPesanan);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/*
 * Harga satuan menurut varian. Mengembalikan [varianBaku, harga] atau null.
 * Aturan sama dengan form_pemesanan.php: menu ber-harga tunggal tanpa varian.
 */
function harga_menu_varian(array $menu, string $varian): ?array
{
    if ($menu['harga'] !== null) {
        return ['', (int) $menu['harga']];
    }
    if ($varian === 'Hot' && $menu['harga_hot'] !== null) {
        return ['Hot', (int) $menu['harga_hot']];
    }
    if ($varian === 'Ice' && $menu['harga_ice'] !== null) {
        return ['Ice', (int) $menu['harga_ice']];
    }
    return null;
}

/*
 * Tabel detail_pesanan tidak menyimpan varian, jadi varian dicocokkan
 * dari harga_satuan terhadap harga_hot / harga_ice pada tabel menu.
 */
function varian_dari_harga(array $menu, int $harga): string
{
    $hot = $menu['harga_hot'];
    $ice = $menu['harga_ice'];

    if ($hot === null && $ice === null) {
        return '';
    }
    if ($hot !== null && $ice === null) {
        return 'Hot';
    }
    if ($ice !== null && $hot === null) {
        return 'Ice';
    }

    $cocokHot = (int) $hot === $harga;
    $cocokIce = (int) $ice === $harga;

    if ($cocokHot && !$cocokIce) {
        return 'Hot';
    }
    if ($cocokIce && !$cocokHot) {
        return 'Ice';
    }
    return '';
}

/* Item pesanan lengkap dengan nama menu dan varian */
function ambil_item_pesanan(mysqli $c, int $idPesanan): array
{
    $stmt = $c->prepare(
        'SELECT d.id_menu, d.jumlah, d.harga_satuan, d.subtotal, m.*
         FROM detail_pesanan d
         JOIN menu m ON m.id_menu = d.id_menu
         WHERE d.id_pesanan = ?
         ORDER BY d.id_detail ASC'
    );
    $stmt->bind_param('i', $idPesanan);
    $stmt->execute();
    $hasil = $stmt->get_result();

    $items = [];
    while ($row = $hasil->fetch_assoc()) {
        $items[] = [
            'id_menu'  => (int) $row['id_menu'],
            'nama'     => $row['nama_menu'],
            'varian'   => varian_dari_harga($row, (int) $row['harga_satuan']),
            'jumlah'   => (int) $row['jumlah'],
            'harga'    => (int) $row['harga_satuan'],
            'subtotal' => (int) $row['subtotal'],
        ];
    }
    $stmt->close();
    return $items;
}

/*
 * Batalkan pesanan yang masih menunggu pembayaran dan kembalikan stok.
 * Pemanggil WAJIB sudah memulai transaksi. Mengembalikan true bila berubah.
 */
function batalkan_pesanan_menunggu(mysqli $c, int $idPesanan): bool
{
    $batal = STATUS_BATAL;
    $menunggu = STATUS_MENUNGGU;

    $stmt = $c->prepare(
        'UPDATE pesanan SET status_pesanan = ?
         WHERE id_pesanan = ? AND status_pesanan = ?'
    );
    $stmt->bind_param('sis', $batal, $idPesanan, $menunggu);
    $stmt->execute();
    $berubah = $stmt->affected_rows > 0;
    $stmt->close();

    if ($berubah) {
        // Jumlah dijumlahkan per menu dulu: UPDATE ... JOIN langsung hanya
        // menambah sekali walau satu menu muncul di beberapa baris (Hot & Ice).
        $stmt = $c->prepare(
            'UPDATE menu m
             JOIN (SELECT id_menu, SUM(jumlah) AS total
                   FROM detail_pesanan WHERE id_pesanan = ?
                   GROUP BY id_menu) d ON d.id_menu = m.id_menu
             SET m.stok = m.stok + d.total'
        );
        $stmt->bind_param('i', $idPesanan);
        $stmt->execute();
        $stmt->close();
    }
    return $berubah;
}

/*
 * Pesanan yang tidak pernah dilanjutkan ke pembayaran tidak boleh menahan
 * stok selamanya: batalkan setelah BATAS_MENIT_TANPA_PEMBAYARAN menit.
 */
function bersihkan_pesanan_kedaluwarsa(mysqli $c): void
{
    $batas = date('Y-m-d H:i:s', time() - BATAS_MENIT_TANPA_PEMBAYARAN * 60);
    $menunggu = STATUS_MENUNGGU;

    $stmt = $c->prepare(
        'SELECT p.id_pesanan
         FROM pesanan p
         LEFT JOIN pembayaran b ON b.id_pesanan = p.id_pesanan
         WHERE p.status_pesanan = ? AND b.id_pembayaran IS NULL
           AND p.tanggal_pesanan < ?'
    );
    $stmt->bind_param('ss', $menunggu, $batas);
    $stmt->execute();
    $ids = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'id_pesanan');
    $stmt->close();

    foreach ($ids as $id) {
        $c->begin_transaction();
        try {
            batalkan_pesanan_menunggu($c, (int) $id);
            $c->commit();
        } catch (Throwable $e) {
            $c->rollback();
            error_log('Bersihkan pesanan gagal: ' . $e->getMessage());
        }
    }
}

/* Cari file aset di ASSETS/img; kembalikan URL relatif dari folder HTML atau null */
function aset_gambar(array $kandidat): ?string
{
    foreach ($kandidat as $nama) {
        if (is_file(dirname(__DIR__) . '/ASSETS/img/' . $nama)) {
            return '../ASSETS/img/' . rawurlencode($nama);
        }
    }
    return null;
}
