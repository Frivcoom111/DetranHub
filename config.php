<?php 

$dbHost = 'localhost';
$dbName = 'detranhub_db';
$dbUser = 'root';
$dbPass = '';

$conn = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);

// if (!$conn) {
//     die('Erro ao conectar ao banco de dados: ' . mysqli_connect_error());
// } else {
//     echo 'Conexão bem-sucedida ao banco de dados.';
// }

?>