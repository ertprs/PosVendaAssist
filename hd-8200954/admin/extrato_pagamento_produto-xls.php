<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

$layout_menu = "financeiro";
$title = "Relatório de Produto X Custo";

include 'cabecalho.php';

?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 9px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 9px;
	font-weight: normal;
}
.Mes{
	font-size: 8px;
}
</style>


<?

//--=== RESULTADO DA PESQUISA ====================================================--\\

$data_inicial = $_POST['data_inicial'];
$data_final   = $_POST['data_final']  ;

if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];
if (strlen($_GET['data_final']) > 0)   $data_final   = $_GET['data_final']  ;

$data_inicial = str_replace (" " , "" , $data_inicial);
$data_inicial = str_replace ("-" , "" , $data_inicial);
$data_inicial = str_replace ("/" , "" , $data_inicial);
$data_inicial = str_replace ("." , "" , $data_inicial);

$data_final   = str_replace (" " , "" , $data_final)  ;
$data_final   = str_replace ("-" , "" , $data_final)  ;
$data_final   = str_replace ("/" , "" , $data_final)  ;
$data_final   = str_replace ("." , "" , $data_final)  ;

if (strlen ($data_inicial) == 6) $data_inicial = substr ($data_inicial,0,4) . "20" . substr ($data_inicial,4,2);
if (strlen ($data_final)   == 6) $data_final   = substr ($data_final  ,0,4) . "20" . substr ($data_final  ,4,2);

if (strlen ($data_inicial) > 0)  $data_inicial = substr ($data_inicial,0,2) . "/" . substr ($data_inicial,2,2) . "/" . substr ($data_inicial,4,4);
if (strlen ($data_final)   > 0)  $data_final   = substr ($data_final,0,2)   . "/" . substr ($data_final,2,2)   . "/" . substr ($data_final,4,4);


if(strlen($data_inicial)>0 AND strlen($data_final)>0 AND $agrupar<>'sim'){
$produto_referencia = trim($_GET['produto_referencia']);


	if(strlen($produto_referencia)>0){ // HD 2003 TAKASHI
		$sql = "SELECT produto
				from tbl_produto
				join tbl_familia using(familia)
				where tbl_familia.fabrica = $login_fabrica
				and tbl_produto.referencia = '$produto_referencia'";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$produto = pg_result($res,0,produto);
		}

	}
	$cond_4 = " and 1=1 "; // HD 2003 TAKASHI
	if (strlen ($produto)  > 0) $cond_4 = " and tbl_os.produto    = $produto "; // HD 2003 TAKASHI




$sql = "SELECT  tbl_os.sua_os                                                         ,
		tbl_os.serie                                                          ,
		tbl_os.mao_de_obra                                                    ,
		tbl_os.pecas                                                          ,
		tbl_os.solucao_os                                                     ,
		tbl_produto.descricao                            AS produto_descricao ,
		tbl_produto.referencia                           AS produto_referencia,
		tbl_defeito_constatado.codigo                    AS defeito_codigo    ,
		tbl_defeito_constatado.descricao                 AS defeito_descricao ,
		tbl_causa_defeito.codigo                         AS causa_codigo      ,
		tbl_causa_defeito.descricao                      AS causa_descricao   ,
	(tbl_os.mao_de_obra + tbl_os.pecas + coalesce(tbl_os.qtde_km_calculada,0) + coalesce(tbl_os.valores_adicionais,0))              AS total             ,
		to_char (tbl_extrato_extra.exportado,'DD/MM/YY') AS data_exportado    ,
		to_char (tbl_extrato.data_geracao,'DD/MM/YY')    AS data_geracao      ,
		coalesce(tbl_os.qtde_km_calculada,0) as qtde_km_calculada             ,
		tbl_os.valores_adicionais                                             ,
		to_char (tbl_extrato_pagamento.data_pagamento,'DD/MM/YY') as data_pagamento
	FROM tbl_os
	JOIN tbl_produto            USING (produto)
	JOIN tbl_os_extra           USING (os)
	JOIN tbl_extrato            ON tbl_extrato.extrato = tbl_os_extra.extrato
	JOIN tbl_extrato_extra      ON tbl_extrato_extra.extrato = tbl_os_extra.extrato
	LEFT JOIN tbl_extrato_pagamento      ON tbl_extrato_pagamento.extrato = tbl_os_extra.extrato
	LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
	LEFT JOIN tbl_causa_defeito      ON tbl_causa_defeito.causa_defeito           = tbl_os.causa_defeito
	WHERE tbl_extrato.fabrica = $login_fabrica $cond_4 ";


	if (strlen ($data_inicial) < 8) $data_inicial = date ("d/m/Y");
		$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

	if (strlen ($data_final) < 8) $data_final = date ("d/m/Y");
		$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);

	if($login_fabrica <> 20){
		if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
			$sql .= " AND tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
	}else{
		if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
			$sql .= " AND tbl_extrato_extra.exportado BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
	}
	$sql .= " ORDER BY tbl_produto.descricao ";


	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {

		flush();

		echo "<br><br>";
		echo "<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo "<tr>";
		echo "<td align='center'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Aguarde, processando arquivo PostScript</font></td>";
		echo "</tr>";
		echo "</table>";

		flush();

		$data = date ("dmY");

		echo `rm /tmp/assist/relatorio_produto_custo-$login_fabrica.xls`;

		$fp = fopen ("/tmp/assist/relatorio_produto_custo-$login_fabrica.html","w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>RELATÓRIO DE PRODUTO X CUSTO - $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");


		fputs ($fp, "<br><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center'>");
		fputs ($fp, "<tr class='Titulo' height='25' bgcolor='#485989'>");
		fputs ($fp, "<td ><font color='FFFFFF'>OS</font></td>");
		fputs ($fp, "<td ><font color='FFFFFF'>PRODUTO</font></td>");
		fputs ($fp, "<td ><font color='FFFFFF'>SÉRIE</font></td>");
		fputs ($fp, "<td ><font color='FFFFFF'>");
		if($login_fabrica == 20) fputs ($fp, "REPARO");
		else                     fputs ($fp, "DEIFEITO CONSTATADO");
		fputs ($fp, "</font></td>");
		if($login_fabrica == 20){
			fputs ($fp, "<td ><font color='FFFFFF'>IDENTIFICAÇÃO</font></td>");
			fputs ($fp, "<td ><font color='FFFFFF'>DEFEITO</font></td>");
		}
		fputs ($fp, "<td ><font color='FFFFFF'>DATA GERAÇÃO</font></td>");
		fputs ($fp, "<td ><font color='FFFFFF'>DATA PAGAMENTO</font></td>");
		fputs ($fp, "<td ><font color='FFFFFF'>M.O</font></td>");
		fputs ($fp, "<td ><font color='FFFFFF'>PEÇAS</font></td>");
		if($telecontrol_distrib) {
			fputs ($fp, "<td ><font color='FFFFFF'>KM</font></td>");
			fputs ($fp, "<td ><font color='FFFFFF'>Valor Adicional</font></td>");
		}
		fputs ($fp, "<td ><font color='FFFFFF'>TOTAL</font></td>");
		fputs ($fp, "</tr>");

		for ($i=0; $i<pg_numrows($res); $i++){

			$sua_os                  = trim(pg_result($res,$i,sua_os))            ;
			$produto_referencia      = trim(pg_result($res,$i,produto_referencia));
			$produto_descricao       = trim(pg_result($res,$i,produto_descricao)) ;
			$serie                   = trim(pg_result($res,$i,serie))             ;
			$solucao_os              = trim(pg_result($res,$i,solucao_os))        ;
			$defeito_codigo          = trim(pg_result($res,$i,defeito_codigo))    ;
			$defeito_descricao       = trim(pg_result($res,$i,defeito_descricao)) ;
			$causa_codigo            = trim(pg_result($res,$i,causa_codigo))      ;
			$causa_descricao         = trim(pg_result($res,$i,causa_descricao))   ;
			$mao_de_obra             = trim(pg_result($res,$i,mao_de_obra))       ;
			$pecas                   = trim(pg_result($res,$i,pecas))             ;
			$total                   = trim(pg_result($res,$i,total))             ;
			$data_geracao            = trim(pg_result($res,$i,data_geracao))      ;
			$data_exportado          = trim(pg_result($res,$i,data_exportado))    ;
			$qtde_km_calculada       = trim(pg_result($res,$i,'qtde_km_calculada'))    ;
			$valores_adicionais         = trim(pg_result($res,$i,'valores_adicionais'))    ;
			$data_pagamento          = trim(pg_result($res,$i,'data_pagamento'))    ;

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';

			$pecas       = number_format ($pecas,2,",",".")      ;
			$mao_de_obra = number_format ($mao_de_obra,2,",",".");
			$qtde_km_calculada = number_format ($qtde_km_calculada,2,",",".");
			$valores_adicionais = number_format ($valores_adicionais,2,",",".");
			$total       = number_format ($total,2,",",".")      ;

			fputs ($fp, "<tr class='Conteudo'>");
			fputs ($fp, "<td bgcolor='$cor' >$sua_os</td>");
			fputs ($fp, "<td bgcolor='$cor' align='left' title='$produto_descricao'>$produto_referencia - $produto_descricao</td>");
			fputs ($fp, "<td bgcolor='$cor' >$serie</td>");
			fputs ($fp, "<td bgcolor='$cor' align='left'>$defeito_codigo - $defeito_descricao</td>");
			if($login_fabrica == 20){
				$xsolucao="";
				if(strlen($solucao_os)>0){
					$xsql="SELECT descricao from tbl_servico_realizado where servico_realizado= $solucao_os limit 1";
					$xres = pg_exec($con, $xsql);
					$xsolucao = trim(pg_result($xres,0,descricao));
				}
				fputs ($fp, "<td bgcolor='$cor' align='left'>$xsolucao</td>");
				fputs ($fp, "<td bgcolor='$cor' align='left'>$causa_codigo- $causa_descricao</td>" );
			}
			fputs ($fp, "<td bgcolor='$cor' align='left'>");
			if($login_fabrica == 20) fputs ($fp, "$data_exportado");
			else                     fputs ($fp, "$data_geracao");
			fputs ($fp, "</td>");
			fputs ($fp, "<td bgcolor='$cor' align='right'>$data_pagamento</td>");
			fputs ($fp, "<td bgcolor='$cor' align='right'>R$ $mao_de_obra</td>");
			fputs ($fp, "<td bgcolor='$cor' align='right'>R$ $pecas</td>");
			if($telecontrol_distrib) {
					fputs ($fp, "<td bgcolor='$cor' align='right'>R$ $qtde_km_calculada</td>");
					fputs ($fp, "<td bgcolor='$cor' align='right'>R$ $valores_adicionais</td>");
			}
			fputs ($fp, "<td bgcolor='$cor' align='right'>R$ $total</td>");
			fputs ($fp, "</tr>");
		}

		fputs ($fp,"</table>");
		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
		fclose ($fp);

		echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/relatorio_produto_custo-$login_fabrica.$data.xls /tmp/assist/relatorio_produto_custo-$login_fabrica.html`;

		echo"<table  border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td><img src='imagens/excell.gif'></td><td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/relatorio_produto_custo-$login_fabrica.$data.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		echo "</tr>";
		echo "</table>";
	}
}






?>
