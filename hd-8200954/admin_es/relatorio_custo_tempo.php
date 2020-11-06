<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

$layout_menu = "financeiro";
$title = "Reporte de costo tiempo por extracto";

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
include "javascript_pesquisas.php"; 
?>
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/assist.js"></script>
<?

include "cabecalho.php";

echo "<TABLE width='600' align='center' border='0' cellspacing='3' cellpadding='2'>\n";
echo "<FORM METHOD='GET' NAME='frm_extrato' ACTION=\"$PHP_SELF\">";
echo "<input type='hidden' name='btnacao' value=''>";

echo "<TR class='menu_top'>\n";
echo "<TD COLSPAN='2' ALIGN='center'>Buscar Costo tiempo en extractos cerrados entre</TD>";
echo "<TR>\n";

echo "<TR>\n";
echo "<TD ALIGN='center'>Fecha Inicial ";
echo "<INPUT size='12' maxlength='10' TYPE='text' NAME='data_inicial' value='$data_inicial' class='frm'>&nbsp;<IMG src=\"imagens_admin/btn_lupa.gif\" align='absmiddle' onclick=\"javascript:showCal('dataPesquisaInicial_Extrato')\" style='cursor:pointer' alt='Haga um click aquí para abrir el calendario'>\n";
echo "</TD>\n";

echo "<TD ALIGN='center'>Fecha Final ";
echo "<INPUT size='12' maxlength='10' TYPE='text' NAME='data_final' value='$data_final' class='frm'>&nbsp;<IMG src=\"imagens_admin/btn_lupa.gif\" align='absmiddle' onclick=\"javascript:showCal('dataPesquisaFinal_Extrato')\" style='cursor:pointer' alt='Haga um click aquí para abrir el calendario'>\n";
echo "</TD>\n";
echo "</TR>\n";

echo "<TR class='menu_top'>\n";
echo "<TD COLSPAN='2' ALIGN='center'>Solamente extractos del servicio</TD>";
echo "</TR>\n";

echo "</TABLE>\n";

echo "<br><img src=\"imagens_admin/btn_filtrar.gif\" onclick=\"javascript: document.frm_extrato.btnacao.value='filtrar' ; document.frm_extrato.submit() \" ALT=\"Filtrar extractos\" border='0' style=\"cursor:pointer;\">\n";

echo "</form>";



$data_inicial = $_POST['data_inicial'];
if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];
$data_final   = $_POST['data_final'];
if (strlen($_GET['data_final']) > 0) $data_final = $_GET['data_final'];
$posto_codigo = $_POST['posto_codigo'];

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



if ( strlen ($data_inicial) > 0 and strlen ($data_final) > 0 ) {

	if (strlen ($data_inicial) < 10){
		$data_inicial = date ("d/m/Y");
	}
	$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

	if (strlen ($data_final) < 10) {
		$data_final = date ("d/m/Y");
	}
	$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);

	#SQL optmizado - HD 17140
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
			FROM tbl_os
			JOIN (
					SELECT tbl_os_extra.os ,
						(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status
					FROM tbl_os_extra
					JOIN tbl_extrato       USING (extrato)
					JOIN tbl_extrato_extra USING (extrato)
					JOIN tbl_posto         USING (posto)
					WHERE tbl_extrato.fabrica = $login_fabrica
					AND   tbl_posto.pais      = '$login_pais'
					AND   tbl_extrato.aprovado IS NOT NULL
					AND   tbl_extrato.posto <> 6359
					AND   tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'
				) fcr ON tbl_os.os = fcr.os
			JOIN tbl_produto                        ON tbl_produto.produto              = tbl_os.produto
			JOIN tbl_posto                          ON tbl_os.posto                     = tbl_posto.posto
			JOIN tbl_posto_fabrica                  ON tbl_posto_fabrica.posto          = tbl_os.posto 
				AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_causa_defeito              ON tbl_causa_defeito.causa_defeito           = tbl_os.causa_defeito
			LEFT JOIN tbl_produto_defeito_constatado ON tbl_produto_defeito_constatado.produto    = tbl_os.produto 
				AND tbl_produto_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
			WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
			AND tbl_os.excluida IS NOT TRUE
			AND tbl_os.tipo_atendimento NOT IN(11,12)
			AND tbl_posto.pais = '$login_pais'
			ORDER BY tbl_posto.nome,tbl_os.posto,tbl_os.sua_os
	";


	// ##### PAGINACAO ##### //
	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";

	
	require "_class_paginacao.php";

	// definicoes de variaveis
	$max_links = 11;				// máximo de links à serem exibidos
	$max_res   = 50;				// máximo de resultados à serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

	// ##### PAGINACAO ##### //


	if (pg_numrows($res) > 0) {
		echo "<br><table border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo "<tr>";
		echo "<td><img src='imagens/excell.gif'></td><td align='left'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Haga un click aquí </font><a href='relatorio_custo_tempo_xls.php?btnacao=filtrar&data_inicial=$data_inicial&data_final=$data_final' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>para hacer el download del archivo en EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Usted puede ver, imprimir y guardar la tabla para consultas off-line.</font></td>";
		echo "</tr>";
		echo "</table>";

		echo "<table border='2' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center'>";
		echo "<tr class='Titulo'>";
		echo "<td >OS</td>";
		echo "<td >SERVICIO</td>";
		echo "<td >PRODUCTO</td>";
		echo "<td WIDTH='60'>LOCALIZACIÓN</td>";
		echo "<td WIDTH='100'>NUMERO DE SÉRIE</td>";
		echo "<td WIDTH='60'>ABERTURA</td>";
		echo "<td WIDTH='60'>FECHA FACTURA</td>";
		echo "<td >TEMPO VT</td>";
		echo "</tr>";

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

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';

			echo "<tr class='Conteudo'align='center'>";
			echo "<td bgcolor='$cor' >$sua_os</td>";
			echo "<td bgcolor='$cor' align='left'><acronym title='Servicio: $codigo_posto - $posto_nome' style='cursor: help;'>$posto_codigo</acronym></td>";
			echo "<td bgcolor='$cor' align='left'><acronym title='Herramienta: $produto_referencia - $produto_nome' style='cursor: help;'>$produto_referencia</acronym></td>";
			echo "<td bgcolor='$cor' >$xsolucao$causa_defeito</td>";
			echo "<td bgcolor='$cor' >$numero_serie</td>";
			echo "<td bgcolor='$cor' >$data_abertura</td>";
			echo "<td bgcolor='$cor' >$data_nf</td>";
			echo "<td bgcolor='$cor' align='rigth'>$custo_tempo</td>";
			echo "</tr>";

		}
		echo "</table>";
	}else{
		echo "<center>Ninguna orden de servicio encuentrada.</center>";
	}
		
### PÉ PAGINACAO###

	echo "<table border='0' align='center'>";
	echo "<tr>";
	echo "<td colspan='9' align='center'>";
		// ##### PAGINACAO ##### //

	// links da paginacao
	echo "<br>";

	if($pagina < $max_links) {
		$paginacao = pagina + 1;
	}else{
		$paginacao = pagina;
	}

	// paginacao com restricao de links da paginacao

	// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
	$todos_links		= $mult_pag->Construir_Links("strings", "sim");

	// função que limita a quantidade de links no rodape
	$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

	for ($n = 0; $n < count($links_limitados); $n++) {
		echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
	}



	$resultado_inicial = ($pagina * $max_res) + 1;
	$resultado_final   = $max_res + ( $pagina * $max_res);
	$registros         = $mult_pag->Retorna_Resultado();

	$valor_pagina   = $pagina + 1;
	$numero_paginas = intval(($registros / $max_res) + 1);

	if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

	if ($registros > 0){
		echo "<br>";
		echo "<font size='2'>Resultado de <b>$resultado_inicial</b> a <b>$resultado_final</b> Del total de <b>$registros</b> Registros.</font>";
		echo "<font color='#cccccc' size='1'>";
		echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
		echo "</font>";
		echo "</div>";
	}
	// ##### PAGINACAO ##### //
	echo "</td>";
	echo "</tr>";

	echo "</table>";

}

include 'rodape.php';
?>
