<?php
/**
 * Koneksi database & konfigurasi base URL.
 *
 * BASE_URL dideteksi otomatis dari posisi folder project relatif terhadap
 * DOCUMENT_ROOT, jadi kode yang sama bisa jalan di:
 *   - Laragon lokal (project di /uas → BASE_URL = "/uas")
 *   - Hosting (project di public_html → BASE_URL = "")
 *   - Subdomain / subfolder lain → otomatis menyesuaikan
 *
 * Cara pakai di file lain:
 *   include '../config/koneksi.php';
 *   header('Location: ' . BASE_URL . '/users/login.php');
 *   <a href="<?= BASE_URL ?>/dokter/data_dokter.php">...</a>
 */

// ============== Koneksi Database ==============
$conn = mysqli_connect("localhost", "root", "", "uas_pbw");

if (!$conn) {
    die("Koneksi gagal : " . mysqli_connect_error());
}

// ============== Auto-detect Base URL ==============
if (!defined('BASE_URL')) {
    // Folder root project (1 level di atas /config)
    $root_fs = str_replace('\\', '/', dirname(__DIR__));
    // Document root server (Apache/Nginx/cPanel)
    $doc_root = isset($_SERVER['DOCUMENT_ROOT'])
        ? str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/\\'))
        : '';

    $base = '';
    if ($doc_root !== '' && stripos($root_fs, $doc_root) === 0) {
        $base = substr($root_fs, strlen($doc_root));
    }
    // Pastikan diawali "/" dan tidak diakhiri "/"
    $base = '/' . trim($base, '/');
    if ($base === '/') $base = '';

    define('BASE_URL', $base);
}
