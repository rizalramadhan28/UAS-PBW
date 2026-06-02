<?php

session_start();

include '../config/koneksi.php';

if(isset($_POST['login'])){

$username=$_POST['username'];

$password=$_POST['password'];

$query=mysqli_query(

$conn,

"SELECT *

FROM users

WHERE username='$username'"

);

$data=mysqli_fetch_assoc($query);

if(
$data &&
password_verify(
$password,
$data['password']
)
){

$_SESSION['login']=true;

$_SESSION['id_user']=$data['id_user'];

$_SESSION['role']=$data['role'];

if(
$data['role']=="admin"
){

header(
"Location:dashboard_admin.php"
);

}else{

header(
"Location:dashboard_pasien.php"
);

}

}else{

$error="Login Gagal";

}

}

?>

<!DOCTYPE html>

<html>

<head>

<title>Login Klinik</title>

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

Medical Center

</p>

</div>

<div class="orange-line">

</div>

<div class="form-box">

<h2>Login</h2>

<br>

<?php

if(isset($error)){

echo $error;

}

?>

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

<button name="login">

LOGIN

</button>

</form>

<div class="link">

Belum punya akun?

<a href="register.php">

Register

</a>

</div>

</div>

</div>

</body>

</html>