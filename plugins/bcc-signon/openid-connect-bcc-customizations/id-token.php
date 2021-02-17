<?php

session_start();
$token = $_SESSION["oidc_id_token"];
if (empty($token))
{
    require_once  "../../../../wp-load.php";

    $token_id = $_COOKIE['oidc_token_id'];
    $token = get_transient('oidc_id_token_' . $token_id);
    $_SESSION["oidc_id_token"] = $token;
}
echo $token;

?>