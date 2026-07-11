<?php
/**
 * Konfigurasi koneksi database
 * SPK Seleksi Karyawan - Metode AHP-SAW
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'spk_seleksi_karyawan');

function getConnection(): mysqli
{
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die('Koneksi database gagal: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}
