<?
require_once('banco.inc.php');

// include_once "dbconfig.php";
// include_once "includes/dbconnect-inc.php";


if (!isset($_SESSION['sess_nivel'])){
    header("Location: login.php");
}
?>