<?php

include '../config/koneksi.php';

if(isset($_POST['register'])){

$user=$_POST['username'];

$pass=

password_hash(

$_POST['password'],

PASSWORD_DEFAULT

);

mysqli_query(

$conn,

"INSERT INTO users

(username,password,role)

VALUES

(

'$user',

'$pass',

'pasien'

)"

);

header(
"Location:login.php"
);

}

?>

<!DOCTYPE html>

<html>

<head>

<title>Register</title>

<link rel="stylesheet"

href="../assets/css/style.css">

</head>

<body>

<div class="container">

<div class="header">

<div class="logo">

🏥

</div>

<h1>Klinik Kemala</h1>

<p>

Registrasi Pasien

</p>

</div>

<div class="orange-line">

</div>

<div class="form-box">

<h2>Buat Akun</h2>

<br>

<form method="POST">

<input

name="username"

placeholder="Username"

required>

<input

type="password"

name="password"

placeholder="Password"

required>

<button name="register">

REGISTER

</button>

</form>

<div class="link">

Sudah punya akun?

<a href="login.php">

Login

</a>

</div>

</div>

</div>

</body>

</html>