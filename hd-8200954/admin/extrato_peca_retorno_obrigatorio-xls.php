<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

$layout_menu = "financeiro";

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
//XLS hd 12105 25/1/2008
//--=== RESULTADO DA PESQUISA ====================================================--\\
$btnacao      = $_POST['btnacao'];
$data_inicial = $_POST['data_inicial'];
$data_final   = $_POST['data_final']  ;
$referencia   = $_POST['referencia']  ;

if (strlen($_GET['btnacao']) > 0)      $btnacao      = $_GET['btnacao'];
if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];
if (strlen($_GET['data_final']) > 0)   $data_final   = $_GET['data_final']  ;
if (strlen($_GET['referencia']) > 0)   $referencia   = $_GET['referencia'];

if(strlen($data_inicial)>0 AND strlen($data_final)>0 AND strlen($referencia)>0 AND  $btnacao=='filtrar'){
echo "Ok";
	if (strlen($referencia)>0){
		$sql_adicional_1 = " AND tbl_peca.referencia= '$referencia' ";
	}

	if (strlen($data_inicial)>0 AND strlen($data_final)>0){
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

		$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);
		$x_data_final   = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);
		
		$sql_adicional_2  = " AND tbl_faturamento.emissao BETWEEN '$x_data_inicial' AND '$x_data_final' ";
	}

	if ($login_fabrica==11){
		$posto_da_fabrica = "20321";
	}

	$sql = "SELECT	tbl_peca.peca                        ,
					tbl_peca.referencia                  ,
					tbl_peca.descricao                   ,
					sum(CASE WHEN tbl_faturamento.conferencia IS NOT NULL THEN COALESCE(tbl_faturamento_item.qtde_inspecionada,0) ELSE 0 END ) as qtde_inspecionada,
					sum(CASE WHEN tbl_faturamento.conferencia IS NULL THEN tbl_faturamento_item.qtde ELSE 0 END ) as qtde_nao_inspecionada
			FROM tbl_faturamento
			JOIN tbl_faturamento_item using(faturamento)
			JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
			WHERE tbl_faturamento.distribuidor IS NOT NULL
			AND tbl_faturamento.posto = $posto_da_fabrica
			AND tbl_faturamento.fabrica  = $login_fabrica
			AND tbl_peca.devolucao_obrigatoria is true
			$sql_adicional_1
			$sql_adicional_2
			GROUP BY tbl_peca.referencia,
					 tbl_peca.descricao ,
					 tbl_peca.peca
			ORDER BY qtde_inspecionada desc
		";
	echo nl2br( $sql);
	//exit;

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

		echo `rm /tmp/assist/relatorio_retorno_obrigatorio-$login_fabrica.$data.xls`;

		$fp = fopen ("/tmp/assist/relatorio_retorno_obrigatorio-$login_fabrica.html","w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>RELATÓRIO DE PEÇAS DE RETORNO OBRIGATORIO - $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");


		fputs ($fp,"<br><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center'>\n");
		fputs ($fp,"<tr class='Titulo'>\n");
		fputs ($fp,"<td >REFERENCIA</td>\n");
		fputs ($fp,"<td >DESCRIÇÃO</td>\n");
		fputs ($fp,"<td >QUANTIDADE DEVOLVIDA</td>\n");
		fputs ($fp,"<td >QUANTIDADE A DEVOLVER</td>\n");
		fputs ($fp,"</tr>");

		for ($i=0; $i < pg_numrows($res); $i++){
			$peca                  = trim(pg_result($res,$i,peca));
			$referencia            = trim(pg_result($res,$i,referencia));
			$descricao             = trim(pg_result($res,$i,descricao));
			$qtde_inspecionada     = trim(pg_result($res,$i,qtde_inspecionada));
			$qtde_nao_inspecionada = trim(pg_result($res,$i,qtde_nao_inspecionada));

			$cor = ($i%2==0) ? '#E9E9E9' : '#ffffff';
			fputs ($fp,"<tr>");
			fputs ($fp,"<td bgcolor='$cor' >$peca</td>\n");
			fputs ($fp,"<td bgcolor='$cor' >$referencia</td>\n");
			fputs ($fp,"<td bgcolor='$cor' >$descricao</td>\n");
			fputs ($fp,"<td bgcolor='$cor' >$qtde_inspecionada</td>\n");
			fputs ($fp,"<td bgcolor='$cor' >$qtde_nao_inspecionada</td>\n");
			fputs ($fp,"</tr>");
		}
		fputs ($fp,"</table>\n");
		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
		fclose ($fp);
	}
	
######### FIM ##################################################


		echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /assist/www/admin/xls/relatorio_retorno_obrigatorio-$login_fabrica.$data.xls /tmp/assist/relatorio_retorno_obrigatorio-$login_fabrica.html`;
		
		echo"<table  border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td><img src='imagens/excell.gif'></td><td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/relatorio_retorno_obrigatorio-$login_fabrica.$data.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		echo "</tr>";
		echo "</table>";
	}


?>
