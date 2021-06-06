<?php

$token = $_SESSION['oidc_access_token'];

if ( empty( $token ) ) {
    $token_id = $_COOKIE['oidc_token_id'];
    $token = get_transient( 'oidc_access_token_' . $token_id );
    $_SESSION['oidc_access_token'] = $token;
}

echo $token;

?>
