<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

$layout_menu = "financeiro";
$title = strtoupper("RelatÓrio de Custo Tempo por Extrato");

?>

<style type="text/css">
.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.espaco td{
	padding:10px 0 10px;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
	border:1px solid #596d9b;
}

</style>
<?
include "cabecalho.php";

include "javascript_pesquisas.php"; 
?>
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/assist.js"></script>
<script language="javascript" src="js/jquery.js"></script>
<script language="javascript" src="js/jquery.maskedinput.js"></script>
<script language="javascript" src="js/jquery.datePicker.js"></script>

<script type="text/javascript">
$().ready(function(){

    //$("#data_inicial").datePicker({startDate : "01/01/2000"});
    $("#data_inicial").maskedinput("99/99/9999");
	//$("#data_final").datePicker({startDate : "01/01/2000"});
    $("#data_final").maskedinput("99/99/9999");

});
function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}


	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.focus();
		
	}
	else{
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	}
}
</script>
<?php include 'javascript_calendario.php'; ?>
<div id="msg" style="width:700px;margin:auto;"></div>
<div class="texto_avulso">Este relatório mostra apenas extratos fechados do posto</div><br />
<div class="titulo_tabela" style="width:700px;margin:auto;">Parâmetros de Pesquisa</div>
<?

echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='0' class='formulario'>\n";

echo "<FORM METHOD='GET' NAME='frm_extrato' ACTION=\"$PHP_SELF\">";
echo "<input type='hidden' name='btnacao' value=''>";
echo '<tr><td>&nbsp;</td></tr>';
echo "<TR>\n";
echo "<TD ALIGN='left' style='padding-left:20px; width:150px;'>Data Inicial (Fechamento)<INPUT size='12' maxlength='10' TYPE='text' NAME='data_inicial' value='$data_inicial' class='frm' id='data_inicial'>&nbsp;\n";
echo "</TD>\n";

echo "<TD ALIGN='left' style='width:150px;'>Data Final (Fechamento)<INPUT size='12' maxlength='10' TYPE='text' NAME='data_final' value='$data_final' class='frm' id=\"data_final\">&nbsp;\n";
echo "</TD>\n";

echo "<td align='left'>Referência Produto:&nbsp;";
echo "<input class='frm' type='text' name='codigo_referencia' size='15' maxlength='20' value='$codigo_referencia'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor:pointer' onclick=\"javascript: fnc_pesquisa_produto (document.frm_extrato.codigo_referencia,document.frm_extrato.produto_descricao,'referencia')\"></td>";

echo "<td align='left'>Descrição Produto:&nbsp;";
echo "<input class='frm' type='text' name='produto_descricao' size='30' value='$produto_descricao'>&nbsp;<img src='imagens/lupa.png'  style='cursor:pointer' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_produto (document.frm.codigo_referencia,document.frm.produto_descricao,'descricao')\"></A></td>";

echo "</TR>\n";

echo '<tr><td>&nbsp;</td></tr>';
echo "<tr><td colspan='5' align='center'>
	<input type='button' value='Filtrar' onclick=\"javascript: document.frm_extrato.btnacao.value='filtrar' ; document.frm_extrato.submit() \" style=\"cursor:pointer; \"/>";
echo '<tr><td>&nbsp;</td></tr>';
echo "</form>";
echo "</table>\n";

if($_GET['btnacao'] == 'filtrar')
{
	if($_GET["data_inicial"]) $data_inicial = $_GET["data_inicial"];
    if($_POST["data_inicial"]) $data_inicial = $_POST["data_inicial"];
    if($_GET["data_final"]) $data_final = $_GET["data_final"];
    if($_POST["data_final"]) $data_final = $_POST["data_final"];

    //Início Validação de Datas
    if(!$data_inicial OR !$data_final){
        $msg_erro = "Data Inválida";
    }
	if($data_inicial > $data_final)
        $msg_erro = "Data Inválida";
    if(strlen($msg_erro)==0){
        $dat = explode ("/", $data_inicial );
            $d = $dat[0];
            $m = $dat[1];
            $y = $dat[2];
            if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
    }
    if(strlen($msg_erro)==0){
        $dat = explode ("/", $data_final );
            $d = $dat[0];
            $m = $dat[1];
            $y = $dat[2];
            if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
    }
if (!empty($msg_erro)) {
?>
	<div id="status" class="msg_erro" style="display:none;"><? echo $msg_erro; ?></div>
	<script type="text/javascript">
		$("#status").appendTo("#msg").fadeIn("slow");
	</script>
<?php
}
else if (strlen ($posto_codigo) > 0 OR (strlen ($data_inicial) > 0 and strlen ($data_final) > 0) && $_GET['btnacao'] == 'filtrar' ) {

$sql = pg_query("SELECT referencia FROM tbl_produto WHERE referencia ilike '%$codigo_referencia%'");
$prod_existe = pg_numrows($sql) > 0 ? TRUE : FALSE;

if(strlen ($codigo_referencia) > 0 && $prod_existe === FALSE) {
	$codigo_referencia = "";
	echo '<div id="erro" class="msg_erro" style="display:none;">Produto não Existe</div>';
?>
	<script type="text/javascript">
		$("#erro").appendTo("#msg").fadeIn("slow");
	</script>
<?php
}
if(strlen($msg_erro)==0){
if (strlen ($codigo_referencia) > 0){
	$codigo_referencia = str_replace ("*" , "_" , $codigo_referencia);
	$cond_1 = " and tbl_produto.referencia ilike '%$codigo_referencia%'";
}else
	$cond_1 = " and 1=1"; //nao retornar nada

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
			JOIN tbl_extrato_extra                   ON tbl_extrato_extra.extrato              = tbl_extrato.extrato
			JOIN tbl_os_extra                        ON  tbl_os_extra.extrato                  = tbl_extrato.extrato
			JOIN tbl_os                              ON  tbl_os.os                             = tbl_os_extra.os AND tbl_os.fabrica = $login_fabrica
			JOIN tbl_produto                         ON tbl_produto.produto                    = tbl_os.produto
			JOIN tbl_posto_fabrica                   ON tbl_posto_fabrica.posto                = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_posto                           ON tbl_posto.posto                        = tbl_os.posto
			LEFT JOIN tbl_causa_defeito              ON tbl_causa_defeito.causa_defeito        = tbl_os.causa_defeito AND tbl_causa_defeito.fabrica = $login_fabrica
			LEFT JOIN tbl_produto_defeito_constatado ON tbl_produto_defeito_constatado.produto = tbl_os.produto 
						AND tbl_produto_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
			WHERE tbl_extrato.fabrica = $login_fabrica
			AND tbl_extrato.aprovado IS NOT NULL
			AND tbl_extrato.posto <> 6359
			AND tbl_os.tipo_atendimento NOT IN(11,12)
			$cond_1";

	if (strlen ($data_inicial) < 8) $data_inicial = date ("d/m/Y");
		$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

	if (strlen ($data_final) < 10) $data_final = date ("d/m/Y");
		$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);

	if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
		$sql .= " AND tbl_extrato_extra.exportado BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
	
	$sql .= " ORDER BY tbl_posto.nome,tbl_extrato.extrato,tbl_os.sua_os";


#echo nl2br($sql); exit;

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
	if (pg_numrows($res) == 0){
		echo '<div>Nenhum Resultado Encontrado</div>';

	}
	else  {
		echo "<br><table border='0' cellspacing='1' cellpadding='0' align='center' class='tabela' width='700'>";
		echo "<tr>";
		echo "<td><img src='imagens/excell.gif'></td><td align='left'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='relatorio_custo_tempo_xls.php?btnacao=filtrar&data_inicial=$data_inicial&data_final=$data_final&codigo_referencia=$codigo_referencia' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		echo "</tr>";
		echo "</table>";

		echo "<table border='2' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center'>";
		echo "<tr class='titulo_coluna'>";
		echo "<td >OS</td>";
		echo "<td >Posto</td>";
		echo "<td >Produto</td>";
		echo "<td WIDTH='60'>Localização</td>";
		echo "<td WIDTH='100'>Número De Série</td>";
		echo "<td WIDTH='60'>Abertura</td>";
		echo "<td WIDTH='60'>Data NF</td>";
		echo "<td >Tempo VT</td>";
		echo "<td >Reclamação</td>";

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

			echo "<tr class='Conteudo'align='center'>";
			echo "<td bgcolor='$cor' >$sua_os</td>";
			echo "<td bgcolor='$cor' align='left'><acronym title='Posto: $codigo_posto - $posto_nome' style='cursor: help;'>$posto_codigo</acronym></td>";
			echo "<td bgcolor='$cor' align='left'><acronym title='Produto: $produto_referencia - $produto_nome' style='cursor: help;'>$produto_referencia</acronym></td>";
			echo "<td bgcolor='$cor' >$xsolucao$causa_defeito</td>";
			echo "<td bgcolor='$cor' >$numero_serie</td>";
			echo "<td bgcolor='$cor' >$data_abertura</td>";
			echo "<td bgcolor='$cor' >$data_nf</td>";
			echo "<td bgcolor='$cor' align='rigth'>$custo_tempo</td>";
			echo "<td bgcolor='$cor' align='rigth'>$reclamacao</td>";
			echo "</tr>";

			}
		}
		echo "</table>";
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
		echo "<font size='2'>Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.</font>";
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
}
//#TODO verificar datas

include 'rodape.php';
?>