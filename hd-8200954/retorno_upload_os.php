<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$title = "Retorno Upload de OS";
include "cabecalho.php";

$diretorio = '/var/www/tmp';

$dir = opendir($diretorio);
while(false !== ($arq = readdir($dir))){
	$arq_nomes[] = $arq;
}

foreach($arq_nomes as $listar){
	if ($listar!="." && $listar!=".."){
		if (!is_dir($listar)) {
			$arquivos[]=$listar;
		}
	}
}

echo "<br>";
echo "<table border='0' cellpadding='2' cellspacing='2'>";
for($x=0; $x<count($arquivos); $x++){
	if (preg_match("/.htm/", $arquivos[$x])){
		if(strpos($arquivos[$x],$login_posto)>0){
			echo "<tr>";
				echo "<td>";
					echo "<a href='xls/$arquivos[$x]' target='_blank'>$arquivos[$x]</a>";
				echo "</td>";
			echo "</tr>";
		}
	}
}
echo "</table>";
?>
