<?

$dbname = 'hbflexc_garantia';
$ip        = getenv ("REMOTE_ADDR");
$dbhost    = "www.hbflex.com";
$dbusuario = "hbflexc_garantia";
$dbsenha   = "h2f0e0x7bl";
$con_hbtech = mysql_connect ($dbhost, $dbusuario, $dbsenha) or die ('Não foi possível acessar a base de dados por causa deste erro: ' . mysql_error());
mysql_select_db ($dbusuario); 
?>