<?php 
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
?>
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml'>
<head>
<style type="text/css">

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

table.tabela tr td{
	font-family: verdana;
	font: bold 11px "Arial";
	border-collapse: collapse;
	border:1px solid #596d9b;
}
.msg_erro{
	width: 100%;
	height: 25px;
	background-color: #ff0000;
	font-size: 21px;
	color: #fff;
	font-weight: bold;
	text-align: center;
	padding: 2px;
	margin-top: 50px;
}
</style>
</head>
<body>
<?php

if(isset($_GET['codigo'])){
	$codigo_posto = $_GET['codigo'];
	$sql = " SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto='{$_GET["codigo"]}' AND fabrica={$login_fabrica}";
	$res = pg_query($con, $sql);
	
	if (pg_num_rows ($res) > 0) {
		$posto = trim(pg_fetch_result($res, 0, posto));
		$sql ="
		SELECT 
		tbl_ibge.cidade || ' - ' || tbl_ibge.estado AS cidade, 
		tbl_posto_fabrica_ibge_tipo.nome AS tipo 
		
		FROM 
		tbl_posto_fabrica_ibge
		JOIN tbl_posto_fabrica_ibge_tipo ON tbl_posto_fabrica_ibge.posto_fabrica_ibge_tipo=tbl_posto_fabrica_ibge_tipo.posto_fabrica_ibge_tipo
		JOIN tbl_ibge ON tbl_posto_fabrica_ibge.cod_ibge = tbl_ibge.cod_ibge
		
		WHERE 
		tbl_posto_fabrica_ibge.fabrica={$login_fabrica} 
		AND tbl_posto_fabrica_ibge.posto={$posto} ";
		
		$res = pg_query($con, $sql);
		if (pg_num_rows ($res) > 0) {
			echo "<table width='100%' border='0' cellspacing='1' class='tabela'>\n";
			echo "<tr class='titulo_tabela'>
			<td><font style='font-size:14px;'>Cidades Atendidas por: &nbsp; {$_GET['nome']}</font></td></tr>";
			echo "<tr class='titulo_coluna'><td>CIDADE</td></tr>";
			$cont = 0;
			while ($linha = pg_fetch_array($res)) {
				extract($linha);
				$cont % 2 == 0 ? $cor= "#F7F5F0" : $cor= "#ffffff"; 
				echo "<tr bgcolor='{$cor}'>";
				//echo "<td>{$codigo_posto}</td>";
				echo "<td> $cidade</td></tr>";
				//echo "<td>{$linha['tipo']}</td></tr>";
				$cont++;
			}
			echo "</table>";
			
		}else{
			echo "<div class='msg_erro'>Não existem registros para essa consulta.</div>";
		}
		
	}else{
		echo "<div class='msg_erro'>Posto não encontrado.</div>";
	}
	
	
}


echo "</body></html>";