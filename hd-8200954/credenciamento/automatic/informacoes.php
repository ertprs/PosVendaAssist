<?
include "/www/assist/www/dbconfig.php";
include "/www/assist/www/includes/dbconnect-inc.php";
//include '../autentica_usuario.php';
?>


<style type="text/css">

input.botao {
	background:#ffffff;
	color:#000000;
	border:1px solid #d2e4fc;
}

.Tabela{
	border:1px solid #d2e4fc;
}

.Tabela2{
	border:1px dotted #C3C3C3;
}

a.conteudo{
	color: #FFFFFF;
	font-family: Arial;
	FONT-SIZE: 8pt; 
	font-weight: bold;
	text-decoration: none;
	text-align: center;
}
a.conteudo:visited {
	color: #FFFFFF;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-decoration: none;
	text-align: center;
}

a.conteudo:hover {
	color: #FFFFCC;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-decoration: none;
	text-align: center;
}

a.conteudo:active {
	color: #FFFFFF;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-decoration: none;
	text-align: center;
}

</style>


<TABLE align='center' width='500' style='fon-size: 14px' border='0' class='tabela2' bgcolor='' cellspacing='0' cellpadding='0'>
<TR>
	<TD style='font-size: 25px; font-family: verdana' align='center'>POSTO EM CREDENCIAMENTO</TD>
</TR>
<TR>
	<TD align='center'>Telecontrol Networking</TD>
</TR>
</TABLE>



<?

// pega o endereço do diretório
$diretorio = getcwd(); 

// abre o diretório
$ponteiro  = opendir($diretorio);

// monta os vetores com os itens encontrados na pasta
while ($nome_itens = readdir($ponteiro)) {
	if($nome_itens <> "index.php" AND $nome_itens <> "posto_cadastro_hbtec.php" AND $nome_itens <> "index2.php")
		$itens[] = $nome_itens;
}

// ordena o vetor de itens
sort($itens);

// percorre o vetor para fazer a separacao entre arquivos e pastas 
foreach ($itens as $listar) {

// retira "./" e "../" para que retorne apenas pastas e arquivos
	if ($listar!="." && $listar!=".."){ 

// checa se o tipo de arquivo encontrado é uma pasta
		if (is_dir($listar)) { 

// caso VERDADEIRO adiciona o item à variável de pastas
			$pastas[]=$listar; 
		} else{ 

// caso FALSO adiciona o item à variável de arquivos
			$arquivos[]=$listar;
		}
	}
}


$posto = trim($_GET['posto']);

// lista os arquivos se houverem
if ($arquivos != "" AND strlen($posto) > 0) {
	$xarquivos = $arquivos;
	
/*	$sql = "SELECT nome, cidade, estado, posto , fabricantes, descricao, email
					FROM tbl_posto_extra 
					JOIN tbl_posto using(posto) 
			WHERE (tbl_posto_extra.fabricantes IS NOT NULL OR tbl_posto_extra.descricao IS NOT NULL)
			AND tbl_posto.posto = '$posto'
			ORDER BY tbl_posto.nome limit 1";
*/

	$sql = "SELECT posto, descricao, fabricantes
				FROM temp_automatic 
				WHERE posto = $posto
				ORDER BY cidade ";
	$res = pg_exec($con, $sql);

	$res = pg_exec($con, $sql);
	if(pg_numrows($res) > 0){
		for($i = 0; $i < pg_numrows($res); $i++){
//			$nome        = pg_result($res,$i,nome);
//			$cidade      = pg_result($res,$i,cidade);
//			$estado      = pg_result($res,$i,estado);
			$posto       = pg_result($res,$i,posto);
			$fabricantes = pg_result($res,$i,fabricantes);
			$descricao   = pg_result($res,$i,descricao);

//			$email   = pg_result($res,$i,email);
			
			echo "<br><table  border = '0' cellspacing='0' cellpadding='0' class='tabela' style='font-size: 10px; font-family: verdana;' align='center' width='600'>";
			echo "<tr>";
				echo "<td style='font-size: 12px' nowrap><b>POSTO $posto</b></td>";
			echo "</tr>";
			echo "<tr>";
				echo "<td align='right' colspan='2'>". strtoupper($cidade) . "  " . strtoupper($estado) ."</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td colspan='2'><b>Fabricantes</b>: $fabricantes</td>";
			echo "</tr>";
			echo "<tr>";
				echo "<td><b>Descrição:</b> $descricao</td>";
			echo "</tr>";
			echo "</table>";
			
			$z = 1;
			foreach($xarquivos as $xlistar){
				//validações para não imprimir fotos de outros postos com nome final =
				$testa_1 = "$posto" . "_1.jpg";
				$testa_2 = "$posto" . "_2.jpg";
				$testa_3 = "$posto" . "_3.jpg";

				if(substr_count($xlistar, $posto) > 0 AND ($testa_1 == $xlistar OR $testa_2 == $xlistar OR $testa_3 == $xlistar)) {
					echo "<p align='center'><img src='$xlistar'></p>";
					$z++;
				}
			}
		}
	}
}
?>