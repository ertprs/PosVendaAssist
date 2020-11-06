<?

$dir = "/www/telecontrol/www/download/ASSIST3/";
$dh  = opendir($dir);

while (false !== ($filename = readdir($dh))) {
	if (strpos($filename,'ASSIST3updt') !== false) echo "<!--ARQUIVO-I-->$filename<!--ARQUIVO-F-->";
}

?>

