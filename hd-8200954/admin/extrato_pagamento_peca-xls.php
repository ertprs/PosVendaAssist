<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

$layout_menu = "financeiro";
$title = "Relatório de Peça X Custo";

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

$peca = $_GET['peca'];
$condicao = " and 1=1 ";//takashi hd 2003
if(strlen($peca)>0) $condicao = " and tbl_peca.peca = $peca ";//takashi hd 2003

/*nao agrupado  takashi 21-12 HD 916*/
if(strlen($data_inicial)>0 AND strlen($data_final)>0 AND $agrupar<>'sim'){

	$tipo_atendimento = trim($_GET["tipo_atendimento"]);
	$familia          = trim($_GET["familia"]);
	$origem           = trim($_GET["origem"]);
	$aux              = trim($_GET["aux"]);
	$posto            = trim($_GET["posto"]);


	if (strlen ($tipo_atendimento)> 0 ) $cond_5 = " AND tbl_os.tipo_atendimento = $tipo_atendimento";
	if (strlen ($familia)         > 0 ) $cond_6 = " AND tbl_produto.familia     = $familia ";
	if (strlen ($origem)          > 0 ) $cond_7 = " AND tbl_produto.origem      = '$origem' ";
	if (strlen ($aux)             > 0 ) $cond_8 = " AND substr(serie,0,4) IN ($aux)";
	if (strlen ($posto)           > 0 ) $cond_9 = " AND tbl_extrato.posto       = '$posto' ";

	$sql = "SELECT 
		sum(tbl_os_item.preco)                           AS preco             ,
		sum(tbl_os_item.custo_peca)                      AS custo_peca        ,
		sum(tbl_os_item.qtde)                            AS qtde              ,
		tbl_peca.peca                                                         ,
		tbl_peca.referencia                              AS peca_referencia   ,
		tbl_peca.descricao                               AS peca_descricao
	FROM tbl_os_extra
	JOIN tbl_os                 ON tbl_os_extra.os           = tbl_os.os and tbl_os.fabrica = $login_fabrica
	JOIN tbl_extrato            ON tbl_extrato.extrato = tbl_os_extra.extrato
	JOIN tbl_extrato_extra      ON tbl_extrato_extra.extrato = tbl_os_extra.extrato
	LEFT JOIN tbl_os_produto    ON tbl_os_produto.os         = tbl_os_extra.os
	LEFT JOIN tbl_os_item       USING (os_produto)
	LEFT JOIN tbl_peca          USING (peca)
	WHERE tbl_extrato.fabrica = $login_fabrica 
	$condicao
	$cond_5
	$cond_6
	$cond_7
	$cond_8
	$cond_9";
//pq left join em tbl_peca, se o relatorio é em cima de peças e seu custo, obrigatoriamente teria que ter peca, nao é?     Takashi 08-05-07

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
	$sql .= "
		GROUP BY tbl_peca.peca,
			 peca_referencia,
			 peca_descricao
		ORDER BY peca_descricao ";

if($login_admin ==568){
	echo "sql: $sql";
}
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

		echo `rm /tmp/assist/relatorio_peca_custo-$login_fabrica.xls`;

		$fp = fopen ("/tmp/assist/relatorio_peca_custo-$login_fabrica.html","w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>RELATÓRIO DE PRODUTO X CUSTO - $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");


		fputs ($fp, "<br><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='750'>");
		fputs ($fp, "<tr class='Titulo' height='25' bgcolor='#485989'>");
		fputs ($fp, "<td ><font color='FFFFFF'>PEÇA</font></td>");
		fputs ($fp, "<td ><font color='FFFFFF'>PREÇO</font></td>");
		fputs ($fp, "<td ><font color='FFFFFF'>QTDE</font></td>");
		fputs ($fp, "</tr>");

		for ($i=0; $i<pg_numrows($res); $i++){


			$peca_descricao          = trim(pg_result($res,$i,peca_descricao))    ;
			$peca_referencia         = trim(pg_result($res,$i,peca_referencia))   ;
			$preco                   = trim(pg_result($res,$i,preco))             ;
			$qtde                    = trim(pg_result($res,$i,qtde))              ;
			if($login_fabrica == 1) $preco = trim(pg_result($res,$i,custo_peca))  ;

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';

			$preco = number_format ($preco,2,",",".");
		
			fputs ($fp, "<tr class='Conteudo'>");
			fputs ($fp, "<td bgcolor='$cor' align='left'>$peca_referencia - $peca_descricao</td>");
			fputs ($fp, "<td bgcolor='$cor' align='right'>R$ $preco</td>");
			fputs ($fp, "<td bgcolor='$cor' align='right'>$qtde</td>");

			fputs ($fp, "</tr>");
		}

		fputs ($fp,"</table>");
		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
		fclose ($fp);

		echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/relatorio_peca_custo-$login_fabrica.$data.xls /tmp/assist/relatorio_peca_custo-$login_fabrica.html`;
		
		echo"<table  border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td><img src='imagens/excell.gif'></td><td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/relatorio_peca_custo-$login_fabrica.$data.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		echo "</tr>";
		echo "</table>";
	}
}






?>
