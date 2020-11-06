<?

$dir = "documentos/";
$dh  = opendir($dir);

while (false !== ($filename = readdir($dh))) {
	if (strpos($filename,'340') !== false) echo "<!--ARQUIVO-I-->$filename<!--ARQUIVO-F-->";
}

?>

