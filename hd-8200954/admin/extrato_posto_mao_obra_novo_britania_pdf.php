<?php set_time_limit(0);?>

<style type="text/css">
.Tabela{
    border:1px solid #d2e4fc;
    background-color: white; 
}
.menu{
	border: 1px solid #d2e4fc;
	color: #ffffff;
	background-color: #596D9B;
}
.titulo{
	border: 1px solid #d2e4fc;
	color: #ffffff;
	background-color: #596D9B;
}
</style>
<meta name="viewport" content="width=device-width, initial-scale = 1.0, maximum-scale = 1.0, user-scalable=no">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<?php
ini_set('display_errors',1);
ini_set('display_startup_erros',1);
error_reporting(E_ALL);
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$autentica = true;

if ($_GET['hash']) {
	$param_hash = $_GET['hash'];
	$param_admin = (int) $_GET['admin'];
	$param_fabrica = (int) $_GET['fabrica'];
	$param_extrato = $_GET['extrato'];

	$hash_comp = sha1("extrato=$param_extrato");

	if ($param_hash == $hash_comp) {
		$sql = "SELECT * FROM tbl_admin WHERE admin = $param_admin AND fabrica = $param_fabrica";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$autentica = false;
			$login_fabrica = $param_fabrica;
			$login_admin = $param_admin;
		}
	}
}

if ($autentica == true) {
	include "autentica_admin.php";
}

include "funcoes.php";
if ($_GET['agendar']) {
	$sqlInsert = "INSERT INTO tbl_relatorio_agendamento(
			admin,
			programa,
			parametros,
			titulo,
			fabrica, 
			agendado 
		) VALUES(
			$login_admin,
			'/assist/admin/extrato_posto_mao_obra_novo_britania_pdf.php',
			'extrato={$_GET['extrato']}',
			'Geração arquivo PDF NFs e Anexos do Extrato {$_GET['extrato']}',
			$login_fabrica,
			TRUE
		);";
	pg_query($con, $sqlInsert);																	
exit('ok');
}
include_once('../anexaNF_inc.php');
include "../classes/mpdf61/mpdf.php";
include "../plugins/fileuploader/TdocsMirror.php";
$extrato = $_GET['extrato'];
$sqlPDF = "SELECT 	tbl_os_extra.os,
					tbl_os.sua_os,
					tbl_os.os_numero,
					tbl_os.consumidor_nome,
					tbl_os.data_nf,
					tbl_os.nota_fiscal,
					tbl_os.revenda_nome,
					tbl_produto.referencia,
					tbl_produto.descricao,
					tbl_marca.nome
			FROM tbl_os_extra 
				JOIN tbl_os USING (os) 
				JOIN tbl_produto USING (produto)
				JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
			WHERE tbl_os.fabrica = $login_fabrica 
				AND tbl_os_extra.extrato = $extrato 
				AND coalesce(tbl_os.sinalizador, 0) <> 3 ;
			";
$resPDF =  pg_query($con, $sqlPDF);
$s3_tdocs = new TdocsMirror();
//$print = "<button id='imprimir' name='imprimir' value='imprimir'>Salvar / Imprimir</button>";
foreach (pg_fetch_all($resPDF) as $pdf) {
	$print .= "<table class='Tabela'>";
	$print .= "<tr>";
	$print .= "<td rowspan='8'>";
	$print .= "<center>OS FABRICANTE<br><br>&nbsp;<b>";
	$print .= "<FONT SIZE='6' COLOR='#C67700'>"; 
	$print .= "{$pdf['sua_os']}";
	$print .= "</FONT>";
	$print .= "<center>CONSUMIDOR&nbsp;<b>";
	$print .= "<BR><font color='#D81005' SIZE='4' ><strong>{$pdf['nome']}</strong></font>";	
	$print .= "</td>";
	$print .= "</tr>";
	$print .= "<td colspan='2' class='menu'>";
	$print .= "INFORMACOES DA OS";
	$print .= "</td>";
	$print .= "<tr>";
	$print .= "<td class='titulo'>";
	$print .= "DATA DA NF";
	$print .= "</td>";
	$print .= "<td>";
	$print .= "{$pdf['data_nf']}";
	$print .= "</td>";
	$print .= "</tr>";
	$print .= "<tr>";
	$print .= "<td class='titulo'>";
	$print .= "NF";
	$print .= "</td>";
	$print .= "<td>";
	$print .= "{$pdf['nota_fiscal']}";
	$print .= "</td>";
	$print .= "</tr>";
	$print .= "<tr>";
	$print .= "<td class='titulo'>";
	$print .= "REVENDA";
	$print .= "</td>";
	$print .= "<td>";
	$print .= "{$pdf['revenda_nome']}";
	$print .= "</td>";
	$print .= "</tr>";
	$print .= "<tr>";
	$print .= "<td class='titulo'>";
	$print .= "REFERENCIA";
	$print .= "</td>";
	$print .= "<td>";
	$print .= "{$pdf['referencia']}";
	$print .= "</td>";
	$print .= "</tr>";
	$print .= "<tr>";
	$print .= "<td class='titulo'>";
	$print .= "DESCRICAO";
	$print .= "</td>";
	$print .= "<td>";
	$print .= "{$pdf['descricao']}";
	$print .= "</td>";
	$print .= "</tr>";
	$print .= "<tr>";
	$print .= "<td class='titulo'>";
	$print .= "NOME";
	$print .= "</td>";
	$print .= "<td>";
	$print .= "{$pdf['consumidor_nome']}";
	$print .= "</td>";
	$print .= "</tr>";
	$print .= "</table>";

	$os = $pdf['os'];
	$sqlTdocs = "SELECT tdocs_id, obs FROM tbl_tdocs where fabrica = {$login_fabrica} AND referencia = 'os' AND referencia_id = '{$os}' ;";
	$resultImg = pg_query($con, $sqlTdocs);	
	if (pg_num_rows($resultImg) > 0) {
		$getTdocs = $s3_tdocs->get(pg_fetch_result($resultImg, 0, tdocs_id));

		$obs = json_decode(pg_fetch_result($resultImg, 0, obs));
		$ext = end(explode('.', $obs[0]->filename));

		if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png'])) {
			$print .= "<img src='{$getTdocs['link']}' >";
		} else {
			$im = new Imagick();

			$data = file_get_contents($getTdocs['link']);
			$content_os = 'xls/' . $os . '.pdf';
			file_put_contents($content_os, $data);

			chmod($content_os, 0777);
			$im->readimage($content_os . '[0]'); 
			$im->setImageFormat('jpeg');    
			$im->writeImage('xls/' . $os . '.jpg'); 
			$im->clear(); 
			$im->destroy();

			$postImage = $s3_tdocs->post("xls/{$os}.jpg");
			$image = $s3_tdocs->get($postImage[0]["{$os}.jpg"]['unique_id']);

			$print .= "<img src='{$image['link']}' >";
			unlink('xls/' . $os . '.jpg');
			unlink($os . '.pdf');
		}
	}else{
		$selectRevenda = "SELECT os_revenda FROM tbl_os_revenda WHERE fabrica = {$login_fabrica} AND sua_os = '0{$pdf['os_numero']}' ";
		$resultRevenda = pg_query($con, $selectRevenda);
		$os = pg_fetch_result($resultRevenda, 0, os_revenda);
		$sqlTdocs = "SELECT tdocs_id, obs FROM tbl_tdocs where fabrica = {$login_fabrica} AND referencia = 'revenda' AND referencia_id = '{$os}' ;";
		$resultImg = pg_query($con, $sqlTdocs);	
		if (pg_num_rows($resultImg) > 0) {
			$getTdocs = $s3_tdocs->get(pg_fetch_result($resultImg, 0, tdocs_id));

			$obs = json_decode(pg_fetch_result($resultImg, 0, obs));
			$ext = end(explode('.', $obs[0]->filename));

			if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png'])) {
				$print .= "<img src='{$getTdocs['link']}'  >";
			} else {
				$im = new Imagick();

				$data = file_get_contents($getTdocs['link']);
				$content_os = 'xls/' . $os . '.pdf';
				file_put_contents($content_os, $data);

				chmod($content_os, 0777);
				$im->readimage($content_os . '[0]'); 
				$im->setImageFormat('jpeg');    
				$im->writeImage('xls/' . $os . '.jpg'); 
				$im->clear(); 
				$im->destroy();

				$postImage = $s3_tdocs->post("xls/{$os}.jpg");
				$image = $s3_tdocs->get($postImage[0]["{$os}.jpg"]['unique_id']);

				$print .= "<img src='{$image['link']}'  >";
				unlink('xls/' . $os . '.jpg');
				unlink($os . '.pdf');
			}
		} else {		
			$print .= "<a>Não imprimiu {$os}</a>";
		}
	}
	
	$print .= "</br>";
	$print .= "</br>";
	$print .= "</br>";
}

$data = date("Y-m-d").".".date("H-i-s");

$arquivo_nome     = "relatorio_extratos_{$login_fabrica}_{$login_admin}.html";
$path_tmp         = "/tmp/";

$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

$fp = fopen ($arquivo_completo_tmp,"w");
fputs ($fp,$print);
fclose ($fp);
flush();

$response = $s3_tdocs->post($arquivo_completo_tmp);
$insertTDOCS = "INSERT INTO tbl_tdocs (
			tdocs_id,
			fabrica,
			contexto,
			referencia,
			referencia_id
		) VALUES (
			'{$response[0][$arquivo_nome]['unique_id']}',
			$login_fabrica,
			'pdf_extrato',
			'tbl_extrato',
			'{$_GET['extrato']}'
		) ";
pg_query($con, $insertTDOCS);
//echo $print;
