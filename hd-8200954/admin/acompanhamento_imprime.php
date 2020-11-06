<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
?>
<head>

	<title><? echo $title ?></title>

	<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
	<meta http-equiv="Expires"       content="0">
	<meta http-equiv="Pragma"        content="no-cache, public">
	<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
	<meta name      ="Author"        content="Telecontrol Networking Ltda">
	<meta name      ="Generator"     content="na mão...">
	<meta name      ="Description"   content="Sistema de gerenciamento para Postos de Assistência Técnica e Fabricantes.">
	<meta name      ="KeyWords"      content="Assistência Técnica, Postos, Manutenção, Internet, Webdesign, Orçamento, Comercial, Jóias, Callcenter">

	<link type="text/css" rel="stylesheet" href="css/css_press.css">

</head>
<style>
.tabela{
	font-size: 11px;
	font-weight: bold;
	text-align: left;
	color: #000000;
	background: #ffffff;
	border-bottom: dotted 0px #000000;
	border-right: dotted 0px #a0a0a0;
 	border-left: dotted 0px #000000;
	padding: 0px,0px,0px,0px;
	border: solid 0px #c0c0c0;
}
</style>
<?
	$title = "Impressão do relatório";
	echo "<br>";

	if($_GET["data_inicial"])		$data_inicial_01    = trim($_GET["data_inicial"]);
	if($_GET["data_final"])			$data_final_01      = trim($_GET["data_final"]);
	if($_GET['posto'])				$posto_codigo       = trim($_GET['posto']);
	if($_GET['estado'])				$estado             = trim($_GET['estado']);
	if($_GET['atendente'])			$xatendente         = trim($_GET['atendente']);
	if(strlen($data_inicial_01) > 0 AND strlen($data_final_01) > 0) {
		$data_inicial     = $data_inicial_01;
		$data_final       = $data_final_01;
		$sql_data = " AND (tbl_posto_pesquisa.data_cadastro::date BETWEEN fnc_formata_data('$data_inicial') AND fnc_formata_data('$data_final')) ";
	}else{
		$sql_data = "";
	}

	if(strlen($posto_codigo) > 0){
		$sql_posto = " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";
	}else{
		$sql_posto = "";
	}

	if(strlen($estado) > 0){
		$sql_estado = " AND tbl_posto_fabrica.contato_estado = '$estado' ";
	}else{
		$sql_estado = "";
	}

	if(strlen($atendente) > 0){
		$sql_atendente = " AND tbl_posto_pesquisa.admin = '$xatendente' ";
	}else{
		$sql_atendente = "";
	}

	if(strlen($sql_data) == 0 AND strlen($sql_posto) == 0 AND strlen($sql_estado) == 0 AND strlen($sql_atendente) == 0){
		echo "<center><p style='font-size: 14px; color: #FF0000'><b>Especifique algum campo para a pesquisa</b></p></center>";;
	}else{
		$sql = "SELECT tbl_posto_fabrica.posto             ,
					tbl_posto.fone                         ,
					tbl_posto.nome                         ,
					tbl_posto_fabrica.contato_email        ,
					to_char(tbl_posto_pesquisa.data,'DD/MM/YYYY') as data_pesquisa,
					tbl_posto_pesquisa.posto_pesquisa      ,
					tbl_posto_pesquisa.data_cadastro       ,
					tbl_posto_pesquisa.contato             ,
					tbl_posto_pesquisa.linha_atende        ,
					tbl_admin.login    AS atendente
				FROM tbl_posto_pesquisa
				JOIN tbl_posto USING(posto)
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_admin ON tbl_admin.admin = tbl_posto_pesquisa.admin
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica
				$sql_data
				$sql_posto
				$sql_estado
				$sql_atendente
				;";
	#echo nl2br($sql);
		$res = pg_exec($con,$sql);
		if(pg_numrows($res) > 0){
			if(strlen($posto_codigo) == 0){
				for($i=0;$i<pg_numrows($res);$i++){

					$posto          = pg_result($res,$i,posto);
					$posto_pesquisa = pg_result($res,$i,posto_pesquisa);
					$posto_nome     = pg_result($res,$i,nome);
					$contato        = pg_result($res,$i,contato);
					$linha_atende   = pg_result($res,$i,linha_atende);
					$posto_fone     = pg_result($res,$i,fone);
					$posto_email    = pg_result($res,$i,contato_email);
					$data_pesquisa  = pg_result($res,$i,data_pesquisa);
					$atendente      = pg_result($res,$i,atendente);

					if($i % 4 ==0 and $i <> 0) {
						echo "<br style='page-break-before:always'>";
					}
					echo "<table width='700' border='1' align='center' cellpadding='1' cellspacing='1' style='border:#000000 2px solid; font-size:12px'>";

					if($i ==0) {
						echo "<caption style='font-size: 20px;'>RELATÓRIO DE ASSISTÊNCIA TÉCNICA  $estado</caption>";
					}
						echo "<tr>";
							echo "<td align='center' colspan='4'><strong>POSTO AUTORIZADO</strong></td>";
						echo "</tr>";

						echo "<tr>";
							echo "<td nowrap width='80'>Razão Social:</td> ";
							echo "<td><b>$posto_nome</b></td>";
							echo "<td width='80'>Contato: </td>";
							echo "<td><b>$contato</b></td>";
						echo "</tr>";

						echo "<tr>";
							echo "<td>Telefone:</td>";
							echo "<td><b>$posto_fone</b></td>";
							echo "<td>Email:</td>";
							echo "<td><b>$posto_email</b></td>";
						echo "</tr>";

						echo "<tr>";
							echo "<td colspan='1' width='80'>Linha de Atendimento:</td>";
							echo "<td colspan='3'><b>$linha_atende</b></td>";
						echo "</tr>";

						echo "<tr>";
							echo "<td colspan='1'>Data:</td>";
							echo "<td colspan='1'><b>$data_pesquisa</b></td>";

							echo "<td colspan='1'>Atendente:</td>";
							echo "<td colspan='1'><b>$atendente</b></td>";
						echo "</tr>";

						echo "<tr>";
							echo "<td colspan='4'>&nbsp;</td>";
						echo "</tr>";


					$sql2 = "SELECT posto_pesquisa, titulo, descricao, seleciona
								FROM tbl_posto_pesquisa_item
								WHERE posto_pesquisa = $posto_pesquisa ;
							";
					$res2 = pg_exec($con,$sql2);

				if(pg_numrows($res2) > 0){
					for($j=0;$j<pg_numrows($res2);$j++){
						$seleciona        = pg_result($res2,$j,seleciona);
						if(strlen($descricao) == 0) {
							$descricao     = pg_result($res2,$j,descricao);
						}

						echo "<tr width='100%'>";
							echo "<td nowrap><INPUT TYPE='checkbox' NAME='reclamacao_$i' value='$pesquisa_posto' "; if($seleciona == 't'){ echo "CHECKED";} echo " disabled ></td> ";
							echo "<td><b>". pg_result($res2,$j,titulo) ."</b></td>";
						if(strlen(@pg_result($res2,$j+1,titulo)) > 0){
							$j++;
							$seleciona        = pg_result($res2,$j,seleciona);
							echo "<td nowrap><INPUT TYPE='checkbox' NAME='reclamacao_$i' value='$pesquisa_posto' "; if($seleciona == 't'){ echo "CHECKED";} echo " disabled ></td> ";
							echo "<td><b>". pg_result($res2,$j,titulo) ."</b></td>";
						}
						echo "</tr>";
					}
					echo "<tr>";
						echo "<td>Descrição</td> ";
						echo "<td colspan='3'><TEXTAREA NAME='descricao' ROWS='3' COLS='50' readonly='readonly'>"; if(strlen($descricao) > 0) { echo "$descricao"; } echo "</TEXTAREA></td>";
					echo "</tr>";
					$descricao = '';
					echo "</table>";
					}
				}

			}else{
				$cont = '0';
				for($i=0;$i<pg_numrows($res);$i++){

					$posto          = pg_result($res,$i,posto);
					$posto_pesquisa = pg_result($res,$i,posto_pesquisa);
					$posto_nome     = pg_result($res,$i,nome);
					$contato        = pg_result($res,$i,contato);
					$linha_atende   = pg_result($res,$i,linha_atende);
					$posto_fone     = pg_result($res,$i,fone);
					$posto_email    = pg_result($res,$i,contato_email);
					$data_pesquisa  = pg_result($res,$i,data_pesquisa);
					$atendente      = pg_result($res,$i,atendente);

					if($cont == '0'){
						$cont = '1';
						echo "<table width='700' class='tabela' align='center'>";
						if($i ==0) {
							echo "<caption style='font-size: 18px;'>RELATÓRIO DE ASSISTÊNCIA TÉCNICA - $posto_nome</caption>";
						}
						echo "<tr>";
							echo "<td aling='center'>";
							if($i % 4 ==0 and $i <> 0) {
								echo "<br style='page-break-before:always'>";
							}
							echo "<table width='700' border='1' align='center' cellpadding='1' cellspacing='1' style='border:#000000 2px solid; font-size:10px'>";

							echo "<tr>";
								echo "<td align='center' colspan='4'><strong>POSTO AUTORIZADO</strong></td>";
							echo "</tr>";

							echo "<tr>";
								echo "<td nowrap width='80'>Razão Social:</td> ";
								echo "<td><b>$posto_nome</b></td>";
								echo "<td width='80'>&nbsp;</td>";
								echo "<td>&nbsp;</td>";
							echo "</tr>";

							echo "<tr>";
								echo "<td>Telefone:</td>";
								echo "<td><b>$posto_fone</b></td>";
								echo "<td>Email:</td>";
								echo "<td><b>$posto_email</b></td>";
							echo "</tr>";

							echo "<tr>";
								echo "<td colspan='1' width='80'>Linha de Atendimento:</td>";
								echo "<td colspan='3'><b>$linha_atende</b></td>";
							echo "</tr>";

							echo "<tr>";
								echo "<td colspan='1'>Atendente:</td>";
								echo "<td colspan='1'><b>$atendente</b></td>";

								echo "<td colspan='1'>&nbsp;</td>";
								echo "<td colspan='1'>&nbsp;</td>";
							echo "</tr>";
						echo "</table>";
						echo "</td>";
						echo "</tr>";
						echo "</table>";
					}

					echo "<table width='700' border='1' align='center' cellpadding='1' cellspacing='1' style='border:#000000 2px solid; font-size:9px'>";

					echo "<tr>";
						echo "<td nowrap width='80'>Contato:</td> ";
						echo "<td><b>$contato</b></td>";
						echo "<td colspan='1'>Data:</td>";
						echo "<td colspan='1'><b>$data_pesquisa</b></td>";
					echo "</tr>";



					$sql2 = "SELECT posto_pesquisa, titulo, descricao, seleciona, contato
								FROM tbl_posto_pesquisa_item
								JOIN tbl_posto_pesquisa USING(posto_pesquisa)
								WHERE posto_pesquisa = $posto_pesquisa ;
							";
					$res2 = pg_exec($con,$sql2);
	#echo nl2br($sql2);

				if(pg_numrows($res2) > 0){
					for($j=0;$j<pg_numrows($res2);$j++){
						$seleciona        = pg_result($res2,$j,seleciona);
						$contato          = pg_result($res2,$j,contato);
						if(strlen($descricao) == 0) {
							$descricao     = pg_result($res2,$j,descricao);
						}

						echo "<tr width='700'>";
							echo "<td nowrap><INPUT TYPE='checkbox' NAME='reclamacao_$i' value='$pesquisa_posto' "; if($seleciona == 't'){ echo "CHECKED";} echo " disabled ></td> ";
							echo "<td><b>". pg_result($res2,$j,titulo) ."</b></td>";
						if(strlen(@pg_result($res2,$j+1,titulo)) > 0){
							$j++;
							$seleciona        = pg_result($res2,$j,seleciona);
							echo "<td nowrap><INPUT TYPE='checkbox' NAME='reclamacao_$i' value='$pesquisa_posto' "; if($seleciona == 't'){ echo "CHECKED";} echo " disabled ></td> ";
							echo "<td><b>". pg_result($res2,$j,titulo) ."</b></td>";
						}
						echo "</tr>";
					}
					echo "<tr>";
						echo "<td>Descrição</td> ";
						echo "<td colspan='3'><TEXTAREA NAME='descricao' ROWS='3' COLS='50' readonly='readonly'>"; if(strlen($descricao) > 0) { echo "$descricao"; } echo "</TEXTAREA></td>";
					echo "</tr>";
					$descricao = '';
					echo "</table>";
					}
					echo "<br>";
				}
			}
		}else{
			echo "<center><p style='font-size: 12px'>Nenhum resgistro encontrado!</p></center>";
		}
	}
?>

</BODY>
</html>