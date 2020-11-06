<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
#Para a rotina automatica - Fabio - HD 12625
$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico == 'automatico'){
	$login_fabrica = trim($_GET["login_fabrica"]);
	$login_admin   = trim($_GET["login_admin"]);
}else{
	include "autentica_admin.php";
}


$data_inicial = trim($_GET["data_inicial"]);
$data_final   = trim($_GET["data_final"]);
$familia      = trim($_GET["familia"]);
$estado       = trim($_GET["estado"]);
$posto        = trim($_GET["posto"]);
$criterio     = trim($_GET["criterio"]);

if (strlen($data_inicial)==0 or strlen($data_final)==0){
	$data_inicial = trim($_GET["data_inicial_01"]);
	$data_final   = trim($_GET["data_final_01"]);
}

if (strlen($data_inicial) == 0) $erro .= "Favor informar a data inicial para pesquisa<br>";

if (strlen($erro) == 0) {
	$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");

	if (strlen ( pg_errormessage ($con) ) > 0) $erro = pg_errormessage ($con) ;

	if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
}

if (strlen($erro) == 0) {
	if (strlen($data_final) == 0) $erro .= "Favor informar a data final para pesquisa<br>";

	if (strlen($erro) == 0) {
		$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");

		if (strlen ( pg_errormessage ($con) ) > 0) $erro = pg_errormessage ($con) ;

		if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
	}
}

$layout_menu = "gerencia";
$title = "RELATÓRIO - FIELD CALL-RATE : LINHA DE PRODUTO";

include "cabecalho.php";

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>

<?
if (strlen($aux_data_inicial) > 0 AND strlen($aux_data_final) > 0) {

	$cond_1 = "1=1";
	$cond_2 = "1=1";
	$cond_3 = "1=1";
	if (strlen ($familia) > 0) $cond_1 = " tbl_produto.familia = $familia ";
	if (strlen ($estado)  > 0) $cond_2 = " tbl_posto.estado    = '$estado' ";
	if (strlen ($posto)  > 0)  $cond_3 = " tbl_posto.posto     = $posto ";

	$aux_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2) . " 00:00:00";
	$aux_data_final   = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2) . " 23:59:59";

/*
	$sql = "SELECT  tbl_produto.produto   ,
					tbl_produto.familia   ,
					tbl_produto.referencia,
					tbl_produto.descricao ,
					os.ocorrencia
			FROM tbl_produto
			JOIN (
					SELECT tbl_os.produto, COUNT(*) AS ocorrencia
					FROM   tbl_os
					JOIN   tbl_posto        ON tbl_posto.posto     = tbl_os.posto
					JOIN   tbl_produto      ON tbl_produto.produto = tbl_os.produto
					LEFT JOIN tbl_os_status ON tbl_os_status.os    = tbl_os.os
					WHERE  tbl_os.fabrica = $login_fabrica
					AND    tbl_os.data_digitacao BETWEEN '$aux_data_inicial' AND '$aux_data_final'
					AND    tbl_os.excluida IS NOT TRUE
					AND    (tbl_os_status.status_os NOT IN (13,15) OR tbl_os_status.status_os IS NULL)
					AND    $cond_1
					AND    $cond_2
					AND    $cond_3
					GROUP BY tbl_os.produto
			) os ON tbl_produto.produto = os.produto
			ORDER BY os.ocorrencia DESC";
*/

	$sql = "SELECT tbl_produto.produto, tbl_produto.referencia, tbl_produto.descricao, fcr1.qtde AS ocorrencia, tbl_produto.familia, fcr1.mao_de_obra
			FROM tbl_produto
			JOIN (SELECT tbl_os.produto, tbl_os.mao_de_obra, COUNT(*) AS qtde
					FROM tbl_os
					JOIN (SELECT tbl_os_extra.os , (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status
							FROM tbl_os_extra
							JOIN tbl_extrato USING (extrato)
							WHERE tbl_extrato.fabrica = $login_fabrica
							AND   tbl_extrato.data_geracao BETWEEN '$aux_data_inicial' AND '$aux_data_final'
							AND   tbl_extrato.liberado IS NOT NULL
					) fcr ON tbl_os.os = fcr.os
					JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
					WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
					AND tbl_os.excluida IS NOT TRUE
					AND $cond_2
					AND $cond_3
					GROUP BY tbl_os.produto, tbl_os.mao_de_obra
			) fcr1 ON tbl_produto.produto = fcr1.produto
			WHERE $cond_1
			ORDER BY fcr1.qtde DESC " ;
			#echo nl2br($sql);
	$res = pg_exec ($con,$sql);
	$numero_registros = pg_numrows($res);
//	if ($ip == "201.0.9.216") { echo nl2br($sql) . "<br>"; echo pg_numrows($res) . "<br>"; }

	if ($numero_registros > 0) {
		flush();

		echo "<br><br>";
		echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td align='center'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Aguarde, processando arquivo PostScript</font></td>";
		echo "</tr>";
		echo "</table>";

		flush();

		$data = date ("d/m/Y H:i:s");


		$arquivo_nome     = "field-call-rate-serie-$login_fabrica.xls";
		$path             = "/www/assist/www/admin/xls/";
		$path_tmp         = "/tmp/";

		$arquivo_completo     = $path.$arquivo_nome;
		$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

		echo `rm $arquivo_completo_tmp `;
		echo `rm $arquivo_completo `;

		$fp = fopen ($arquivo_completo_tmp,"w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>FIELD CALL-RATE - $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");

		fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='2' cellspacing='2'>");
		fputs ($fp,"<tr>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>REFERÊNCIA PRODUTO</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>DESCRIÇÃO PRODUTO</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>OCORRÊNCIA</b></td>");
		if($login_fabrica==14){
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>MÃO DE OBRA</b></td>");
		}
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>%</b></td>");
		fputs ($fp,"</tr>");

		for ($x = 0 ; $x < pg_numrows($res) ; $x++) {
			$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,ocorrencia);
		}

		for ($i = 0 ; $i < pg_numrows($res) ; $i++){
			$referencia  = trim(pg_result($res,$i,referencia));
			$descricao   = trim(pg_result($res,$i,descricao));
			$ocorrencia  = trim(pg_result($res,$i,ocorrencia));
			$mao_de_obra = trim(pg_result($res,$i,mao_de_obra));

			if ($total_ocorrencia > 0) $porcentagem = (($ocorrencia * 100) / $total_ocorrencia);

			fputs ($fp,"<tr>");
			fputs ($fp,"<td bgcolor='$cor' align='left'>&nbsp;" . $referencia . "&nbsp;</td>");
			fputs ($fp,"<td bgcolor='$cor' align='left' nowrap>&nbsp;" . $descricao . "&nbsp;</td>");
			fputs ($fp,"<td bgcolor='$cor' align='center' nowrap>&nbsp;" . $ocorrencia . "&nbsp;</td>");
			fputs ($fp,"<td bgcolor='$cor' align='center' nowrap>&nbsp;" . $mao_de_obra . "&nbsp;</td>");
			if($login_fabrica==14){
			fputs ($fp,"<td bgcolor='$cor' align='center' nowrap>&nbsp;" . number_format($porcentagem,2,",",".") . "%&nbsp;</td>");
			}
			fputs ($fp,"</tr>");
		}

		fputs ($fp,"</table>");

		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
		fclose ($fp);
		echo ` cp $arquivo_completo_tmp $path `;
	}

	$data = date("Y-m-d").".".date("H-i-s");

	rename($arquivo_completo_tmp, $arquivo_completo);
//	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;
	#echo ` cd $path; rm $arquivo_nome.zip; zip $arquivo_nome.zip $arquivo_nome > NULL`;

	if ($gera_automatico == 'automatico'){

		$email_hd = 'helpdesk@telecontrol.com.br';
		if (strlen($login_admin)>0){
			$sql = "SELECT email FROM tbl_admin WHERE admin = $login_admin AND fabrica = $login_fabrica";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res)>0){
				$email_para = trim(pg_result($res,0,'email'));
			}
		}

		$from    = 'Suporte Telecontrol <helpdesk@telecontrol.com.br>';
		$to      = ($email_para == '') ? $email_hd : $email_para;

		$bcc     = "helpdesk@telecontrol.com.br";
		$subject = "Relatorio: ".$title." - (Data: $data)";

		if ($numero_registros>0){
			$body    = " Em anexo relatório $title.";
		}else{
			$body    = " Relatório $title foi processado mas não foi encontrado nenhum registro com os parâmetros informados.";
		}

		$mailheaders = "From: $from\n";
		$mailheaders .= "Reply-To: $from\n";
		#$mailheaders .= "Cc: $cc\n";
		$mailheaders .= "Bcc: $bcc\n";
		$mailheaders .= "X-Mailer: ".$title." - AUTOMATICO \n";

		$msg_body = stripslashes($body);

		if ($numero_registros>0){
			$attach      = $arquivo_completo;
			$attach_name = $arquivo_nome;

			$file = fopen("$attach", "r");
			$contents = fread ($file, filesize ($attach));
			$encoded_attach = chunk_split(base64_encode($contents));
			fclose($file);
			$mailheaders .= "MIME-version: 1.0\n";
			$mailheaders .= "Content-type: multipart/mixed; ";
			$mailheaders .= "boundary=\"Message-Boundary\"\n";
			$mailheaders .= "Content-transfer-encoding: 7BIT\n";
			$mailheaders .= "X-attachments: $attach_name";
			$body_top = "--Message-Boundary\n";
			$body_top .= "Content-type: text/plain; charset=US-ASCII\n";
			$body_top .= "Content-transfer-encoding: 7BIT\n";
			$body_top .= "Content-description: Mail message body\n\n";
		}

		$msg_body = $body_top . $msg_body;

		if ($numero_registros>0){
			$msg_body .= "\n\n--Message-Boundary\n";
			$msg_body .= "Content-type: $attach_type; name=\"$attach_name\"\n";
			$msg_body .= "Content-Transfer-Encoding: BASE64\n";
			$msg_body .= "Content-disposition: attachment; filename=\"$attach_name\"\n\n";
			$msg_body .= "$encoded_attach\n";
			$msg_body .= "--Message-Boundary--\n";
		}

		if (mail($to, stripslashes(utf8_encode($subject)), utf8_encode($msg_body), $mailheaders)){
			$sql = "SELECT relatorio_agendamento
					FROM tbl_relatorio_agendamento
					WHERE admin   = $login_admin
					AND programa  = '$PHP_SELF'
					AND executado IS NULL";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) > 0) {
				$relatorio_agendamento = trim(pg_result($res,0,relatorio_agendamento));
				$sql = "UPDATE tbl_relatorio_agendamento SET executado = CURRENT_TIMESTAMP
						WHERE relatorio_agendamento = $relatorio_agendamento";
				$res = pg_exec($con,$sql);
			}
		}else{
			mail('fabio@telecontrol.com.br', stripslashes('RELATORIO AGENDADO NAO ENVIOU EMAIL'), 'NAO ENVIOU O EMAIL PARA'.$to.' - Fabrica: '.$login_fabrica.' - Admin: '.$login_admin, '');
		}
		exit;
	}

	echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
	echo"<tr>";
	echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/field-call-rate-serie-$login_fabrica.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
	echo "</tr>";
	echo "</table>";
}

?>

<p>

<? include "rodape.php" ?>
