<?php
$conn = mysqli_connect("localhost", "root", "", "uas_pbw");

if (!$conn) {
    die("Koneksi gagal : " . mysqli_connect_error());
}
?>