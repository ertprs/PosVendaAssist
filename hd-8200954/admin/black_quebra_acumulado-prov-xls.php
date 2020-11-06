<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "gerencia,call_center";
include "autentica_admin.php";

include "funcoes.php";

$erro = "";

$x_data_inicial = trim($_GET["data_inicial"]);
$x_data_final   = trim($_GET["data_final"]);
$linha          = $_GET["linha"];
$estado         = $_GET["estado"];
$ordem          = $_GET["ordem"];
$ordem1         = $_GET["ordem1"];

if (strlen($x_data_inicial) == 0) $erro .= " Preencha o campo Data Inicial.<br> ";
if (strlen($x_data_final) == 0)   $erro .= " Preencha o campo Data Final.<br> ";


if (strlen($erro) == 0) {

	if ($x_data_inicial != "null") {
		$data_inicial = substr($x_data_inicial,9,2) . "/" . substr($x_data_inicial,6,2) . "/" . substr($x_data_inicial,1,4);
	}else{
		$data_inicial = "";
		$erro .= " Preencha correto o campo Data Inicial.<br> ";
	}
	
	if ($x_data_final != "null") {
		$data_final = substr($x_data_final,9,2) . "/" . substr($x_data_final,6,2) . "/" . substr($x_data_final,1,4);
	}else{
		$data_final = "";
		$erro .= " Preencha correto o campo Data Final.<br> ";
	}
}

$xdata_i = str_replace("'","",$x_data_inicial);
$xdata_f = str_replace("'","",$x_data_final);

if (strlen($erro) > 0) {
	$msg = "Foi detectado o seguinte erro:<br>";
	$msg .= $erro;
}else{
	$relatorio = "gerar";

	$cond_linha     = "1=1";
	if (strlen ($linha) > 0) $cond_linha = " tbl_produto.linha = $linha ";

	$cond_estado    = "1=1";
	$cond_estado2   = "1=1";
	if (strlen ($estado) > 0) {
		$cond_estado  = " tbl_posto.estado = '$estado' ";
		$cond_estado2 = " black_antigo_os.estado = '$estado' ";
	}

	$sql = "SELECT  tbl_produto.descricao AS nome            ,
				tbl_produto.voltagem  AS voltagem            ,
				tbl_linha.nome        AS linha_nome          ,
				tbl_produto.referencia AS referencia         ,
				SUM (xos.pecas)       AS pecas               ,
				SUM (xos.mao_de_obra) AS mao_de_obra         ,
				SUM (xos.ocorrencia)  AS ocorrencia
			FROM (
				SELECT  tbl_os.produto                 ,
						tbl_produto.linha                      ,
						SUM (tbl_os.pecas) AS pecas_x          ,
						SUM (tbl_os.pecas_pagas) AS pecas      ,
						SUM (tbl_os.mao_de_obra) AS mao_de_obra,
						COUNT(*) AS ocorrencia 
					FROM tbl_os 
					JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
					JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
					JOIN tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
					JOIN tbl_extrato_financeiro ON tbl_extrato.extrato = tbl_extrato_financeiro.extrato
					JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
				WHERE tbl_os.fabrica = 1
				AND   $cond_estado
				AND   tbl_extrato_financeiro.data_envio BETWEEN '$x_data_inicial' AND '$x_data_final' 
				GROUP BY tbl_os.produto, tbl_produto.linha
			) xos
			JOIN tbl_produto ON xos.produto = tbl_produto.produto
			JOIN tbl_linha   ON tbl_produto.linha = tbl_linha.linha
			WHERE tbl_linha.linha = $linha 
			GROUP BY tbl_produto.descricao, tbl_produto.voltagem, tbl_linha.nome, tbl_produto.referencia_fabrica 
			ORDER BY tbl_produto.referencia_fabrica";

	/*if ($ip == '201.68.13.116') 
		echo $sql; 
exit;
*/		echo $sql; 
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {


		$data = date ("d/m/Y H:i:s");
		echo `rm /www/assist/www/credenciamento/black-quebra-acumulado-prov-teste-$login_fabrica.xls`;
		$fp = fopen ("/www/assist/www/credenciamento/black-quebra-acumulado-prov-teste-$login_fabrica.html","w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>FIELD CALL-RATE - $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");
		
		fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='2' cellspacing='2'>");
		fputs ($fp,"<tr>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>REFERÊNCIA</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>VOLTAGEM</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>OCORRENCIA</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>MÃO DE OBRA</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>ORDEM SERVIÇO</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>PEÇAS</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>LINHA</b></td>");
		fputs ($fp,"</tr>");

		echo "<table width='700' border='0' cellpadding='2' cellspacing='2' align='center'>";
		echo "<tr>";
		
		echo "<td bgcolor='#B6DADA' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>Produto</b></font>";
		echo "</td>";
		
		echo "<td bgcolor='#B6DADA' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>Referência</b></font>";
		echo "</td>";
		
		echo "<td bgcolor='#B6DADA' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>Ocorrência</b></font>";
		echo "</td>";
		
		echo "<td bgcolor='#B6DADA' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>Total MO</b></font>";
		echo "</td>";
		
		echo "<td bgcolor='#B6DADA' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>Total PC</b></font>";
		echo "</td>";
		
		echo "<td bgcolor='#B6DADA' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>Total GERAL</b></font>";
		echo "</td>";
		
		echo "<td bgcolor='#B6DADA' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>%</b></font>";
		echo "</td>";
		
		echo "</tr>";
		
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,ocorrencia);
			$total_mobra      = $total_mobra + pg_result($res,$x,mao_de_obra);
			$total_peca       = $total_peca + pg_result($res,$x,pecas);
			$total_geral      = $total_geral + pg_result($res,$x,mao_de_obra) + pg_result($res,$x,pecas);
		}
		
		$total_final = $total_geral + $total_sedex + $total_avulso;
		
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$referencia = pg_result($res,$x,referencia);
			$voltagem   = pg_result($res,$x,voltagem);
			$ocorrencia = pg_result($res,$x,ocorrencia);
			$soma_mobra = pg_result($res,$x,mao_de_obra);//esta pegando esse valor na tbl_os
			$soma_peca  = pg_result($res,$x,pecas);//esta pegando esse valor na tbl_os_item
			$linha_nome = pg_result($res,$x,linha_nome);
			$soma_total = $soma_mobra + $soma_peca;
			
			if ($soma_total > 0 AND $total_geral > 0) {
				$porcentagem = ($soma_total / $total_geral * 100);
			}
			
			$total_porcentagem	= $total_porcentagem + $porcentagem;
			
			fputs ($fp,"<tr>");
			fputs ($fp,"<td bgcolor='$cor' align='center'>&nbsp;" . pg_result ($res,$x,referencia) . "&nbsp;</td>");
			fputs ($fp,"<td bgcolor='$cor' align='left' nowrap>"  . pg_result ($res,$x,voltagem) . "</td>");
			fputs ($fp,"<td bgcolor='$cor' align='center'>&nbsp;" . pg_result ($res,$x,ocorrencia) . "&nbsp;</td>");
			fputs ($fp,"<td bgcolor='$cor' align='left' nowrap>"  . pg_result ($res,$x,mao_de_obra) . "</td>");
			fputs ($fp,"<td bgcolor='$cor' align='center'>&nbsp;"  .$os."&nbsp;</td>");
			fputs ($fp,"<td bgcolor='$cor' align='center'>&nbsp;" . pg_result ($res,$x,pecas) . "&nbsp;</td>");
			fputs ($fp,"<td bgcolor='$cor' align='center'>&nbsp;" . pg_result ($res,$X,linha_nome) . "&nbsp;</td>");
			fputs ($fp,"</tr>");


			$cor = '#EFF5F5';
			
			if ($x % 2 == 0) $cor = '#B6DADA';
			
			echo "<tr>";
			
			echo "<td bgcolor='$cor' align='left' nowrap>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo substr(pg_result($res,$x,nome),0,45);
			echo "</font>";
			echo "</td>";

			$x_data_inicial = str_replace ("'","",$x_data_inicial);
			$x_data_final   = str_replace ("'","",$x_data_final);

			echo "<td bgcolor='$cor' align='left' nowrap>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2' nowrap>";
			echo "<a href='black_quebra_acumulado_pecas-prov.php?referencia=$referencia&voltagem=$voltagem&data_inicial=$x_data_inicial&data_final=$x_data_final&linha=$linha&estado=$estado' target='_blank'>";
			echo $referencia ." - ". $voltagem ;
			echo "</a>";
			echo "</font>";
			echo "</td>";
			
			echo "<td bgcolor='$cor' align='center'>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo "<a href='black_quebra_acumulado_os-prov.php?referencia=$referencia&voltagem=$voltagem&data_inicial=$x_data_inicial&data_final=$x_data_final&linha=$linha&estado=$estado' target='_blank'>";
			echo $ocorrencia;
			echo "</a>";
			echo "</font>";
			echo "</td>";
			
			echo "<td bgcolor='$cor' align='right'>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo number_format($soma_mobra,2,",",".");
			echo "</td>";
			
			echo "<td bgcolor='$cor' align='right'>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo number_format($soma_peca,2,",",".");
			echo "</font>";
			echo "</td>";
			
			echo "<td bgcolor='$cor' align='right'>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo number_format($soma_total,2,",",".");
			echo "</font>";
			echo "</td>";
			
			echo "<td bgcolor='$cor' align='center'>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo number_format($porcentagem,2,",",".");
			echo "</font>";
			echo "</td>";
			echo "<td bgcolor='$cor' align='center' nowrap >";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo $linha_nome;
			echo "</font>";
			echo "</td>";
			echo "</tr>";
	
		}

		echo "<tr>";

		echo "<td bgcolor='#B6DADA' align='left' colspan='2'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>TOTAL</b></font>";
		echo "</td>";
		
		echo "<td bgcolor='#B6DADA' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>$total_ocorrencia</font>";
		echo "</td>";
		
		echo "<td bgcolor='#B6DADA' align='right'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>". number_format($total_mobra,2,",",".") ."</font>";
		echo "</td>";
		
		echo "<td bgcolor='#B6DADA' align='right'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>". number_format($total_peca,2,",",".") ."</font>";
		echo "</td>";
		
		echo "<td bgcolor='#B6DADA' align='right'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>". number_format($total_geral,2,",",".") ."</font>";
		echo "</td>";
		
		echo "<td bgcolor='#B6DADA' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>100%</font>";
		echo "</td>";
		
		echo "</tr>";
		echo "</table>";
		
#		echo "<p><center><a href='/download/quebra_produto.csv'>Clique aqui</a> com o botão direito do mouse para salvar o arquivo em seu computador</center></p>";
		
		fclose ($fp);
	#	unlink ($arquivo);

	$data = date("Y-m-d").".".date("H-i-s");
	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/credenciamento/black-quebra-acumulado-prov-teste-$login_fabrica.$data.xls /www/assist/www/credenciamento/black-quebra-acumulado-prov-teste-$login_fabrica.html`;
	echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
	echo"<tr>";
	echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='/www/assist/www/credenciamento/black-quebra-acumulado-prov-teste-$login_fabrica.$data.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
	echo "</tr>";
	echo "</table>";

	}else{
		echo "está vazio";
	}
}

//include 'rodape.php';

?>
