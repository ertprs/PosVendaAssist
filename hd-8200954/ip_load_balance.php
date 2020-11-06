<?php
$login_ip = $_SERVER['REMOTE_ADDR'];
$login_ip = trim ($login_ip);
if (strlen ($login_ip) == 0 OR substr ($login_ip,0,3) == "10.")
{
    $ipString=@getenv("HTTP_X_FORWARDED_FOR"); 
    $addr = explode(",",$ipString); 
    $login_ip = $addr[sizeof($addr)-1];
}
