<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

$layout_menu = "financeiro";
$title = "Relatório de Custo Tempo por Extrato";

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

include "cabecalho.php";


$data_inicial = $_POST['data_inicial'];
if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];
$data_final   = $_POST['data_final'];
if (strlen($_GET['data_final']) > 0) $data_final = $_GET['data_final'];
$posto_codigo = $_POST['posto_codigo'];
$codigo_referencia   = $_POST['codigo_referencia'];
if (strlen($_GET['codigo_referencia']) > 0) $codigo_referencia = $_GET['codigo_referencia'];


$data_inicial = str_replace (" " , "" , $data_inicial);
$data_inicial = str_replace ("-" , "" , $data_inicial);
$data_inicial = str_replace ("/" , "" , $data_inicial);
$data_inicial = str_replace ("." , "" , $data_inicial);


$data_final = str_replace (" " , "" , $data_final);
$data_final = str_replace ("-" , "" , $data_final);
$data_final = str_replace ("/" , "" , $data_final);
$data_final = str_replace ("." , "" , $data_final);


if (strlen ($data_inicial) == 6) $data_inicial = substr ($data_inicial,0,4) . "20" . substr ($data_inicial,4,2);
if (strlen ($data_final)   == 6) $data_final   = substr ($data_final  ,0,4) . "20" . substr ($data_final  ,4,2);

if (strlen ($data_inicial) > 0) $data_inicial = substr ($data_inicial,0,2) . "/" . substr ($data_inicial,2,2) . "/" . substr ($data_inicial,4,4);
if (strlen ($data_final)   > 0) $data_final   = substr ($data_final,0,2)   . "/" . substr ($data_final,2,2)   . "/" . substr ($data_final,4,4);

if (strlen ($codigo_referencia) > 0){
	$codigo_referencia = str_replace ("*" , "_" , $codigo_referencia);
	$cond_1 = " and tbl_produto.referencia ilike '%$codigo_referencia%'";
}else{
	$cond_1 = " and 1=1";
}

if (strlen ($posto_codigo) > 0 OR (strlen ($data_inicial) > 0 and strlen ($data_final) > 0) ) {

	$sql = "SELECT  tbl_os.sua_os                                                        ,
					tbl_os.serie                               AS numero_serie           ,
					TO_CHAR(tbl_os.data_abertura,'dd/mm/yy')   AS data_abertura          ,
					TO_CHAR(tbl_os.data_nf,'dd/mm/yy')         AS data_nf                ,
					tbl_posto_fabrica.codigo_posto             AS posto_codigo           ,
					tbl_posto.nome                             AS posto_nome             ,
					tbl_produto.referencia                     AS produto_referencia     ,
					tbl_produto.descricao                      AS produto_nome           ,
					tbl_produto.nome_comercial                 AS produto_identificacao  ,
					tbl_causa_defeito.codigo                   AS causa_defeito          ,
					tbl_os.solucao_os                          AS solucao_os             ,
					tbl_produto_defeito_constatado.unidade_tempo AS custo_tempo
			FROM tbl_extrato
			JOIN tbl_extrato_extra                   ON tbl_extrato_extra.extrato                 = tbl_extrato.extrato
			JOIN tbl_os_extra                        ON tbl_os_extra.extrato                      = tbl_extrato.extrato
			JOIN tbl_os                              ON tbl_os.os                                 = tbl_os_extra.os AND tbl_os.fabrica = $login_fabrica
			JOIN tbl_produto                         ON tbl_produto.produto                       = tbl_os.produto
			JOIN tbl_posto_fabrica                   ON tbl_posto_fabrica.posto                   = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_posto                           ON tbl_posto.posto                           = tbl_os.posto
			LEFT JOIN tbl_causa_defeito              ON tbl_causa_defeito.causa_defeito           = tbl_os.causa_defeito AND tbl_causa_defeito.fabrica = $login_fabrica
			LEFT JOIN tbl_produto_defeito_constatado ON tbl_produto_defeito_constatado.produto    = tbl_os.produto AND tbl_produto_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
			WHERE tbl_extrato.fabrica = $login_fabrica
			AND tbl_extrato.aprovado IS NOT NULL
			AND tbl_extrato.posto <>6359
			AND tbl_os.tipo_atendimento NOT IN(11,12)
			$cond_1";

	if (strlen ($data_inicial) < 8) $data_inicial = date ("d/m/Y");
		$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

	if (strlen ($data_final) < 10) $data_final = date ("d/m/Y");
		$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);

	if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
		$sql .= " AND tbl_extrato_extra.exportado BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
	$sql .= " ORDER BY tbl_posto.nome,tbl_extrato.extrato,tbl_os.sua_os";


	$res = pg_exec ($con,$sql);


	if (pg_numrows($res) > 0) {

		flush();
		
		echo "<br><br>";
		echo"<table  border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td align='center'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Aguarde, processando arquivo PostScript</font></td>";
		echo "</tr>";
		echo "</table>";
		
		flush();
		
		$data = date ("dmY");

		echo `rm /tmp/assist/relatorio_custo_tempo-$login_fabrica.xls`;

		$fp = fopen ("/tmp/assist/relatorio_custo_tempo-$login_fabrica.html","w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>RELATÓRIO CUSTO TEMPO - $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");
		fputs ($fp, "<table border='2' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center'>");
		fputs ($fp, "<tr class='Titulo'>");
		fputs ($fp, "<td >OS</td>");
		fputs ($fp, "<td >POSTO</td>");
		fputs ($fp, "<td >PRODUTO</td>");
		fputs ($fp, "<td WIDTH='60'>LOCALIZAÇÃO</td>");
		fputs ($fp, "<td WIDTH='100'>NUMERO DE SÉRIE</td>");
		fputs ($fp, "<td WIDTH='60'>ABERTURA</td>");
		fputs ($fp, "<td WIDTH='60'>DATA NF</td>");
		fputs ($fp, "<td >TEMPO VT</td>");
		fputs ($fp, "<td >RECLAMAÇÃO</td>");
		fputs ($fp, "</tr>");

		for ($i=0; $i<pg_numrows($res); $i++){

			$sua_os                  = trim(pg_result($res,$i,sua_os))                 ;
			$posto_codigo            = trim(pg_result($res,$i,posto_codigo))           ;
			$posto_nome              = trim(pg_result($res,$i,posto_nome))             ;
			$produto_referencia      = trim(pg_result($res,$i,produto_referencia))     ;
			$produto_nome            = trim(pg_result($res,$i,produto_nome))           ;
			$solucao_os              = trim(pg_result($res,$i,solucao_os))             ;
			$causa_defeito           = trim(pg_result($res,$i,causa_defeito))         ;
			$custo_tempo             = trim(pg_result($res,$i,custo_tempo))            ;
			$data_abertura           = trim(pg_result($res,$i,data_abertura))          ;
			$data_nf                 = trim(pg_result($res,$i,data_nf))                ;
			$numero_serie            = trim(pg_result($res,$i,numero_serie))           ;

			if(strlen($solucao_os)){
				$xsql="SELECT substr(descricao,1,2) as descricao from tbl_servico_realizado where servico_realizado= $solucao_os limit 1";

//				if($ip=='200.208.222.134')echo $xsql;

				$xres = pg_exec($con, $xsql);
				$xsolucao = trim(pg_result($xres,0,descricao));
			}

			//coluna reclamação Bosch

			//defino data 1 
			$ano1 = substr($data_abertura,6,4); 
			$mes1 = substr($data_abertura,3,2); 
			$dia1 = substr($data_abertura,0,2); 

			//defino data 2 
			$ano2 = substr($data_nf,6,4);
			$mes2 = substr($data_nf,3,2);
			$dia2 = substr($data_nf,0,2); 

			//calculo timestam das duas datas 
			$timestamp1 = mktime(0,0,0,$mes1,$dia1,$ano1); 
			$timestamp2 = mktime(0,0,0,$mes2,$dia2,$ano2);

			$segundos_diferenca = $timestamp1 - $timestamp2; 
			$dias_diferenca = $segundos_diferenca / (60 * 60 * 24); 
			$reclamacao = floor($dias_diferenca);

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';

			fputs ($fp, "<tr class='Conteudo'align='center'>");
			fputs ($fp, "<td bgcolor='$cor' >$sua_os</td>");
			fputs ($fp, "<td bgcolor='$cor' align='left'><acronym title='Posto: $codigo_posto - $posto_nome' style='cursor: help;'>$posto_codigo</acronym></td>");
			fputs ($fp, "<td bgcolor='$cor' align='left'><acronym title='Produto: $produto_referencia - $produto_nome' style='cursor: help;'><font color='FFFFFF'>'</font>$produto_referencia</acronym></td>");
			fputs ($fp, "<td bgcolor='$cor' >$xsolucao$causa_defeito</td>");
			fputs ($fp, "<td bgcolor='$cor' ><font color='FFFFFF'>'</font>$numero_serie</td>");
			fputs ($fp, "<td bgcolor='$cor' >$data_abertura</td>");
			fputs ($fp, "<td bgcolor='$cor' >$data_nf</td>");
			fputs ($fp, "<td bgcolor='$cor' aliign='rigth'>$custo_tempo</td>");
			fputs ($fp, "<td bgcolor='$cor' aliign='rigth'>$reclamacao</td>");
			fputs ($fp, "</tr>");

		}
		
		fputs ($fp, "</table>");
		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
		fclose ($fp);

	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/relatorio_custo_tempo-$login_fabrica.$data.xls /tmp/assist/relatorio_custo_tempo-$login_fabrica.html`;
	
	echo"<table  border='0' cellspacing='2' cellpadding='2' align='center'>";
	echo"<tr>";
	echo "<td><img src='imagens/excell.gif'></td><td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/relatorio_custo_tempo-$login_fabrica.$data.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
	echo "</tr>";
	echo "</table>";


	}



}

include 'rodape.php';
?>