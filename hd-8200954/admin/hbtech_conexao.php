<?

$dbname = 'hbflexc_garantia';
$ip        = getenv ("REMOTE_ADDR");
$dbhost    = "www.hbflex.com";
$dbusuario = "hbflexc_garantia";
$dbsenha   = "h2f0e0x7bl";
$con=mysql_connect ($dbhost, $dbusuario, $dbsenha) or die ('Não foi possível acessar a base de dados por causa deste erro: ' . mysql_error());
mysql_select_db ($dbusuario); 

if (!mysql_connect('www.hbflex.com', 'hbflexc_garantia', 'h2f0e0x7bl')) {
echo 'Could not connect to mysql';
exit;
}

$sql = "SHOW TABLES FROM $dbname";
$result = mysql_query($sql);

if (!$result) {
echo "DB Error, could not list tables\n";
echo 'MySQL Error: ' . mysql_error();
exit;
}

while ($row = mysql_fetch_row($result)) {
echo "Table: {$row[0]}\n";
echo "
";
}

/*

if (!mysql_connect('70.86.75.82', 'hbflexc_garantia', 'h2f0e0x7bl')) {
echo 'Could not connect to mysql';
exit;
}
mysql_select_db ($dbname);

$result = mysql_query("SHOW COLUMNS FROM garantia");
if (!$result) {
echo 'Could not run query: ' . mysql_error();
exit;
}
if (mysql_num_rows($result) > 0) {
while ($row = mysql_fetch_assoc($result)) {
print_r($row); echo "
";
}
}*/
?>