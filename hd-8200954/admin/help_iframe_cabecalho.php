<?php

//Desenvolvedor Inicial: Ébano Lopes
//HD 205958
//Este arquivo mostra um help devidamente formatado

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<style>
body {
	margin: 0px;
	background: none;
}

img {
	border: none;
}
</style>

<script type="text/javascript" src="../js/jquery.js"></script>
<script type="text/javascript" src="../js/thickbox.js"></script>
<link rel="stylesheet" type="text/css" href="../js/thickbox.css" />

<script language="javascript">
function abre_help_tbl_help(help) {
	tb_show("Telecontrol - Ajuda", "help_visualiza.php?help="+help+"&engana=<?echo rand();?>&keepThis=true&TB_iframe=true&height=450&width=760&modal=true",  null);
}


</script>

</head>

<body>
<?

$help = $_GET["help"];
if(!empty($help)){
	$sql = "
	SELECT
	help

	FROM
	tbl_help_admin

	WHERE
	help=$help
	AND admin=$login_admin
	";
	$res = pg_query($con, $sql);
	if (pg_num_rows($res)) {
	}
	else {
		echo "
		<script language=javascript>
			$().ready(function() {
				abre_help_tbl_help($help);
				self.parent.document.getElementById('iframe_tbl_help').style.display='block';
			});
		</script>
		";
	}
}
?>
</body>

</html>
