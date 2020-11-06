<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria";
include 'autentica_admin.php';
include "funcoes.php";

$msg_erro = "";

$data_inicial   = $_GET['data_inicial'];
$data_final     = $_GET['data_final'];
$marca          = $_GET['data_final'];
$linha          = $_GET['data_final'];
//Início Validação de Datas
	if($data_inicial){
		$dat = explode ("/", $data_inicial );
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
	}
	if($data_final){
		$dat = explode ("/", $data_final );
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
	}
	if(strlen($erro)==0){
		$d_ini = explode ("/", $data_inicial);
		$x_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";


		$d_fim = explode ("/", $data_final);//tira a barra
		$x_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";

		if($x_data_final < $x_data_inicial){
			$msg_erro = "Data Inválida.";
		}

		//Fim Validação de Datas
	}

$layout_menu = "auditoria";
$title = "RELATÓRIO DE GASTOS COM CONSERTO";

$pais = $_POST["pais"];
if(strlen($pais)==0){
	$pais = $_GET["pais"];
}

include "cabecalho.php";
?>
<script>

function AbrePosto(ano,mes,estado,linha){
	janela = window.open("gasto_por_posto_estado.php?ano=" + ano + "&mes=" + mes + "&estado=" + estado+ "&linha=" + linha,"Gasto",'width=700,height=300,top=0,left=0, scrollbars=yes' );
	janela.focus();
}
</script>

<?
    include "javascript_calendario_new.php";
    include "../js/js_css.php";
?>

<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");
	});
</script>
<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />
<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 
<script>
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

});
</script>



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



.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}

</style>
<p>

<table align='center' border='0' cellspacing='0' cellpadding='2' width='700' class='formulario'>
<? if(strlen($msg_erro)>0){ ?>
	<tr class="msg_erro"><td colspan="5"><? echo $msg_erro ?> </td></tr>
<? } ?>
<tr class="titulo_tabela"><td colspan="5">Parâmetros de Pesquisa</td></tr>
<tr><td colspan="5">&nbsp;</td></tr>
<form name='frm_percentual' method="get" action='<? echo $PHP_SELF ?>'>
<tr>
	<td width="20">&nbsp;</td>
	<td>Data inicial</td>
	<td>Data final</td>
    <td>Selecione a MARCA</td>
	<td>Selecione a LINHA</td>
	<td></td>
		
		
</tr>
<tr >
	<td width="20">&nbsp;</td>
	<td >
<?
/*--------------------------------------------------------------------------------
selectMesSimples()
Cria ComboBox com meses de 1 a 12
--------------------------------------------------------------------------------*/
/*function selectMesSimples($selectedMes){
	for($dtMes=1; $dtMes <= 12; $dtMes++){
		$dtMesTrue = ($dtMes < 10) ? "0".$dtMes : $dtMes;
		
		echo "<option value=$dtMesTrue ";
		if ($selectedMes == $dtMesTrue) echo "selected";
		echo ">$dtMesTrue</option>\n";
	}
}*/
?>
		<input type="text" name="data_inicial" id='data_inicial' size="13" maxlength="10" value="<? echo $data_inicial ?>" class="frm">
	</td>
	<td >
<?
/*--------------------------------------------------------------------------------
selectAnoSimples($ant,$pos,$dif,$selectedAno)
// $ant = qtdade de anos retroceder
// $pos = qtdade de anos posteriores
// $dif = ve qdo ano termina
// $selectedAno = ano já setado
Cria ComboBox com Anos
--------------------------------------------------------------------------------*/
/*function selectAnoSimples($ant,$pos,$dif=0,$selectedAno)
{
	$startAno = date("Y"); // ano atual
	for($dtAno = $startAno - $ant; $dtAno <= $startAno + ($pos - $dif); $dtAno++){
		echo "<option value=$dtAno ";
		if ($selectedAno == $dtAno) echo "selected";
		echo ">$dtAno</option>\n";
	}
}*/
?>
		<input type="text" name='data_final' id="data_final" size="13" maxlength="10" value="<? echo $data_final ?>" class="frm">
	</td>
    <td>
        <select name="marca" class="frm">
            <option value=''>Todas</option>
<?
    $sqlMarca = "
        SELECT  marca,
                nome
        FROM    tbl_marca
        WHERE   fabrica = $login_fabrica;
    ";
    $resMarca = pg_query($con,$sqlMarca);
    $marcas = pg_fetch_all($resMarca);

    foreach($marcas as $chave => $valor){
?>
            <option value="<?=$valor['marca']?>" <?=($valor['marca'] == $_GET['marca']) ? "selected='selected'" : "" ?>><?=$valor['nome']?></option>
<?
    }
?>
        </select>
    </td>
	<td >
		<select name='linha' class="frm">
			<option value=''>Todas</option>
<?
	// LINHA
	$sql = "SELECT linha,
					nome
			FROM tbl_linha
			WHERE fabrica = $login_fabrica
			ORDER BY nome ASC";
	$res = pg_exec($con,$sql);
	for($i=0; $i<pg_numrows($res); $i++){
		$xlinha = pg_result($res,$i,linha);
		$nome   = pg_result($res,$i,nome);
		echo "<option value=$xlinha ";
		if ($xlinha == $_GET['linha']) echo "selected";
		echo ">$nome</option>\n";
	}
?>
		</select>
        </td>
    </tr>
    <tr>
		<td colspan="6" style="text-align:center;"><input type='submit' value='Pesquisar'></td>
    </tr>
<tr><td colspan="6">&nbsp;</td></tr>
</form>
</table>

<br><center>

<?
//HD 3237 - SE LOGIN FOR DIFERENTE DE SAMEL, DEVERÁ SER APENAS PARA O BRASIL
if(strlen($pais) == 0 ) {
	$pais = 'BR';
}
flush();

$join_pais = "  ";
$cond_pais = " 1=1 ";
	
##### Pesquisa entre datas #####
#print_r($_GET);
#print_r($_POST);

if(strlen($msg_erro)==0){
	$x_data_inicial = trim($_GET["data_inicial"]);
    $x_data_final   = trim($_GET["data_final"]);
    $x_linha        = trim($_GET["linha"]);
	$x_marca        = trim($_GET["marca"]);

	if ($x_data_inicial != "dd/mm/aaaa" && $x_data_final != "dd/mm/aaaa") {

		if (strlen($x_data_inicial) > 0) {
			$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
			$x_data_inicial = str_replace("'", "", $x_data_inicial);
			$data_inicial   = $x_data_inicial.' 00:00:00';
			$dia_inicial    = substr($x_data_inicial, 8, 2);
			$mes_inicial    = substr($x_data_inicial, 5, 2);
			$ano_inicial    = substr($x_data_inicial, 0, 4);
	//		$data_inicial = date("01/m/Y H:i:s", mktime(0, 0, 0, $mes_inicial, $dia_inicial, $ano_inicial));
		}else{
			$msg_erro = " Preencha o campo Data Inicial para realizar a pesquisa. ";
		}

		if (strlen($x_data_final) > 0) {
			$x_data_final = fnc_formata_data_pg($x_data_final);
			$x_data_final = str_replace("'", "", $x_data_final);
			$data_final   = $x_data_final.' 23:59:59';
			$dia_final    = substr($x_data_final, 8, 2);
			$mes_final    = substr($x_data_final, 5, 2);
			$ano_final    = substr($x_data_final, 0, 4);
	//		$data_final   = date("t/m/Y H:i:s", mktime(23, 59, 59, $mes_final, $dia_final, $ano_final));
		}else{
			$msg_erro = " Preencha o campo Data Final para realizar a pesquisa. ";
		}
	}else{
		$msg_erro = " Informe as datas corretas para realizar a pesquisa. ";
	}
	if ((strlen($data_inicial) > 0 AND strlen($data_final) > 0) OR (strlen($msg_erro) == 0 and $login_fabrica == 1)){

        if (strlen($x_marca) > 0){
            $cond_marca = " AND tbl_produto.marca = $x_marca";
        }

        if (strlen($x_linha) > 0){
			$join_linha = "JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha ";
			$cond_linha = " AND tbl_linha.linha = $x_linha ";
		}

		$sql =" SELECT tbl_extrato.fabrica, tbl_extrato.extrato
			  INTO TEMP tmp_extrato_aprovado_$login_admin	
			  FROM tbl_extrato
			 WHERE tbl_extrato.aprovado BETWEEN '$data_inicial' AND '$data_final'
			   AND tbl_extrato.fabrica = $login_fabrica;

			CREATE INDEX  tmp_extrato_aprovado_fabrica_$login_admin ON tmp_extrato_aprovado_$login_admin(fabrica,extrato);
                        SELECT  tbl_os.fabrica, tbl_os.posto,tbl_os.os,
                                tbl_os.mao_de_obra,
                                tbl_os.consumidor_revenda
                        INTO TEMP tmp_os_extrato_aprovada_$login_admin
                        FROM tbl_os
                        JOIN tbl_produto ON tbl_produto.produto=tbl_os.produto AND tbl_produto.fabrica_i=tbl_os.fabrica
                        $join_linha
                        JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os AND tbl_os_extra.i_fabrica=tbl_os.fabrica
                        JOIN  tmp_extrato_aprovado_$login_admin ON  tmp_extrato_aprovado_$login_admin.extrato=tbl_os_extra.extrato AND  tmp_extrato_aprovado_$login_admin.fabrica=$login_fabrica
                        WHERE  tbl_os.fabrica = $login_fabrica
                        $cond_marca
                        $cond_linha
                        AND    tbl_os.finalizada IS NOT NULL
                        AND    tbl_os.data_fechamento IS NOT NULL
                        AND    tbl_os.excluida IS NOT TRUE
                        AND    tbl_os.posto <> 6359;

			CREATE INDEX tmp_os_extrato_aprovada_os_$login_admin ON tmp_os_extrato_aprovada_$login_admin(os);

";
// 		echo nl2br($sql);
		$res = pg_exec ($con,$sql);


		$sql = "SELECT  tmp_os_extrato_aprovada_$login_admin.fabrica, 
				tmp_os_extrato_aprovada_$login_admin.posto,
				tmp_os_extrato_aprovada_$login_admin.os,
				tmp_os_extrato_aprovada_$login_admin.mao_de_obra,
				tmp_os_extrato_aprovada_$login_admin.consumidor_revenda,
				tbl_os_item.custo_peca AS custop,
				tbl_os_item.qtde
			INTO TEMP tmp_gpp_os_extrato_aprovada_$login_admin
			FROM tmp_os_extrato_aprovada_$login_admin
			LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tmp_os_extrato_aprovada_$login_admin.os
			LEFT JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_item.fabrica_i=$login_fabrica
			WHERE tmp_os_extrato_aprovada_$login_admin.fabrica = $login_fabrica;

			CREATE INDEX tmp_gpp_os_extrato_aprovada_OS_$login_admin ON tmp_gpp_os_extrato_aprovada_$login_admin(os); 
";
// 		echo nl2br($sql);
		$res = pg_exec ($con,$sql);

		$sql = "SELECT fabrica,
			       posto,
			       os,
			       mao_de_obra,
			       consumidor_revenda,
			       SUM(custop * qtde) as custo_peca
			INTO TEMP tmp_gpp_os_custo_extrato_aprovada_$login_admin
			FROM   tmp_gpp_os_extrato_aprovada_$login_admin
			GROUP BY fabrica,posto,os,mao_de_obra,consumidor_revenda;";

		#echo nl2br($sql);
		$res = pg_exec ($con,$sql);

		/*HD: 55221 - PARA A BLACK NÃO É GRAVADO O CAMPO CUSTO_PECA NA OS*/
		/*HD 55221 ALTERADO FORAM SEPARADOS OS SELECTS e ALTERADO O CALCULO DA MEDIA*/

		
		if($login_fabrica == 1){
			$sql = "
					SELECT tmp_gpp_os_custo_extrato_aprovada_$login_admin.os,
					CASE WHEN custo_peca IS NULL THEN 0 ELSE custo_peca END
					INTO TEMP tmp_gpp_os_os_custo_extrato_aprovada_$login_admin
					FROM tmp_gpp_os_custo_extrato_aprovada_$login_admin;

					CREATE INDEX tmp_gpp_os_os_custo_extrato_aprovada_OS_$login_admin ON tmp_gpp_os_os_custo_extrato_aprovada_$login_admin(os);
";
		}

		/*HD: 55221 - PARA A BLACK NÃO É GRAVADO O CAMPO CUSTO_PECA NA OS*/
		if($login_fabrica ==1){
			$sql_custo_peca = " SUM ( CASE WHEN tmp_gpp_os_custo_extrato_aprovada_$login_admin.custo_peca  IS NULL THEN 0 ELSE tmp_gpp_os_custo_extrato_aprovada_$login_admin.custo_peca  END )     AS pecas             ,";
			$sql_desvio     = " STDDEV (CASE WHEN tmp_gpp_os_custo_extrato_aprovada_$login_admin.mao_de_obra IS NULL OR tmp_gpp_os_custo_extrato_aprovada_$login_admin.custo_peca IS NULL THEN 0 ELSE tmp_gpp_os_custo_extrato_aprovada_$login_admin.mao_de_obra + tmp_gpp_os_custo_extrato_aprovada_$login_admin.custo_peca END ) AS desvio  ,";
				
		}else{
			$sql_custo_peca = " SUM ( CASE WHEN tmp_gpp_os_custo_extrato_aprovada_$login_admin.custo_peca  IS NULL THEN 0 ELSE tmp_gpp_os_custo_extrato_aprovada_$login_admin.custo_peca  END )     AS pecas             ,";
			$sql_desvio     = " STDDEV (CASE WHEN tmp_gpp_os_custo_extrato_aprovada_$login_admin.mao_de_obra IS NULL OR tmp_gpp_os_custo_extrato_aprovada_$login_admin.custo_peca IS NULL THEN 0 ELSE tmp_gpp_os_custo_extrato_aprovada_$login_admin.mao_de_obra + tmp_gpp_os_custo_extrato_aprovada_$login_admin.custo_peca END ) AS desvio  ,";
		}

		$sql .= "
			SELECT  SUM ( CASE WHEN tmp_gpp_os_custo_extrato_aprovada_$login_admin.mao_de_obra IS NULL THEN 0 ELSE tmp_gpp_os_custo_extrato_aprovada_$login_admin.mao_de_obra END )   AS mao_de_obra,			
				COUNT (tmp_gpp_os_custo_extrato_aprovada_$login_admin.os) AS qtde,
				$sql_custo_peca
				$sql_desvio
				COUNT  (CASE WHEN tmp_gpp_os_custo_extrato_aprovada_$login_admin.consumidor_revenda = 'C' OR tmp_gpp_os_custo_extrato_aprovada_$login_admin.consumidor_revenda IS NULL THEN 1 ELSE NULL END) AS qtde_os_consumidor,
				COUNT  (CASE WHEN tmp_gpp_os_custo_extrato_aprovada_$login_admin.consumidor_revenda = 'R' THEN 1 ELSE NULL END) AS qtde_os_revenda
			INTO TEMP tmp_gpp_$login_admin
			FROM    tmp_gpp_os_custo_extrato_aprovada_$login_admin
			$join_pais 
			WHERE   tmp_gpp_os_custo_extrato_aprovada_$login_admin.fabrica = $login_fabrica 
			AND     $cond_pais ;
			
			SELECT * FROM tmp_gpp_$login_admin";

	#if($ip == '201.76.83.17') {
// 		echo nl2br($sql);
#		exit;
	#}
		$res = pg_exec ($con,$sql);
		//exit;
		$mao_de_obra = pg_result ($res,0,mao_de_obra);
		$pecas       = pg_result ($res,0,pecas);
		$total_geral = $mao_de_obra + $pecas ;
		
		$qtde_geral         = pg_result ($res,0,qtde) ;
		$desvio_geral       = pg_result ($res,0,desvio) ;
		if (strlen($desvio_geral) == 0) $desvio_geral = 0;

		$qtde_os_consumidor = pg_result ($res,0,qtde_os_consumidor);
		$qtde_os_revenda    = pg_result ($res,0,qtde_os_revenda);
		
		echo "<table width='700' class='tabela' cellspacing='1'>";
		echo "<tr class='titulo_tabela'><td colspan='3' style='font-size:13px;'>Valores Totais Pagos</td></tr>";
		echo "<tr class='titulo_coluna'>";
		echo "<td width='33%'>";
		echo "Mão de Obra - R$ " . number_format ($mao_de_obra,2,",",".");
		echo "</td>";
		echo "<td width='33%'>";
		echo "Peças - R$ " . number_format ($pecas,2,",",".");
		echo "</td>";
		echo "<td width='34%'>";
		echo "Total - R$ " . number_format ($total_geral,2,",",".");
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		
		echo "<table width='700' class='tabela' cellspacing='1'>";
		echo "<tr class='titulo_coluna'>";
		echo "<td width='33%'>";
		echo "Qtde de OS - " .number_format ($qtde_geral,0,",",".") ;
		echo "</td>";
		echo "<td width='33%'>";
		echo "Gasto Médio - R$ ";
		if ($total_geral > 0){
			$gasto_medio = $total_geral / $qtde_geral;
		}else {
			$gasto_medio = 0;
		}
		
		echo number_format ($gasto_medio,2,",",".");
		echo "</td>";
		echo "<td width='34%'>";
		echo "Desvio Padrão - R$ " ;
		echo number_format ($desvio_geral,2,",",".") ;
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		
		echo "<table width='700' class='tabela' cellspacing='1'>";
		echo "<tr class='titulo_coluna'>";
		echo "<td width='S%'>";
		echo "Qtd OS Consumidor - " . number_format ($qtde_os_consumidor,0,",",".");
		echo "</td>";
		echo "<td width='50%'>";
		echo "Qtde OS Revenda - " . number_format ($qtde_os_revenda,0,",",".") ;
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		
		echo "<p>";

		flush();
		if($login_fabrica == 1){
				$colspan='7';
		}
		echo "<table width='700' class='tabela' cellspacing='1'>";
		if($login_fabrica == 1){
				echo "<tr class='titulo_tabela'><td colspan='$colspan' style='font-size:13px;'>Maiores Postos em Valores Nominais</td></tr>";
		}
		echo "<tr class='titulo_coluna'>";
		echo "<td>Posto</td>";
		echo "<td>Nome</td>";
		
		echo "<td>Estado</td>";
		echo "<td>Qtde</td>";
		echo "<td>MO</td>";
		echo "<td>Peças</td>";
		echo "<td>Total</td>";
		echo "</tr>";

		$limit = '10';

		if($login_fabrica ==1){
			$sql_custo_peca = " CASE WHEN SUM   (tmp_gpp_os_custo_extrato_aprovada_$login_admin.custo_peca)  IS NULL THEN 0 ELSE SUM   (tmp_gpp_os_custo_extrato_aprovada_$login_admin.custo_peca)  END AS pecas  ,";
		}else{
			$sql_custo_peca = " CASE WHEN SUM   (tmp_gpp_os_custo_extrato_aprovada_$login_admin.custo_peca)  IS NULL THEN 0 ELSE SUM   (tmp_gpp_os_custo_extrato_aprovada_$login_admin.custo_peca)  END AS pecas      ,";
		}

		$sql = "SELECT  maiores.*,
				tbl_posto.nome                ,
				tbl_posto.cidade              ,
				tbl_posto.estado              ,
				tbl_posto_fabrica.codigo_posto
				FROM (
					SELECT * FROM (
							SELECT  tmp_gpp_os_custo_extrato_aprovada_$login_admin.posto,
								CASE WHEN SUM   (tmp_gpp_os_custo_extrato_aprovada_$login_admin.mao_de_obra) IS NULL THEN 0 ELSE SUM   (tmp_gpp_os_custo_extrato_aprovada_$login_admin.mao_de_obra) END AS mao_de_obra,
									$sql_custo_peca
								CASE WHEN COUNT (tmp_gpp_os_custo_extrato_aprovada_$login_admin.os)          IS NULL THEN 0 ELSE COUNT (tmp_gpp_os_custo_extrato_aprovada_$login_admin.os)          END AS qtde
							FROM    tmp_gpp_os_custo_extrato_aprovada_$login_admin
							JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tmp_gpp_os_custo_extrato_aprovada_$login_admin.posto
							$join_pais
							WHERE   tmp_gpp_os_custo_extrato_aprovada_$login_admin.fabrica            = $login_fabrica
							AND     tbl_posto_fabrica.fabrica = $login_fabrica 
							AND     $cond_pais
							GROUP BY tmp_gpp_os_custo_extrato_aprovada_$login_admin.posto ";
		
			$sql .= " ) AS x ORDER BY (x.mao_de_obra + x.pecas) DESC ";
		
		$sql .= "   ) maiores
				JOIN tbl_posto         ON maiores.posto = tbl_posto.posto
				JOIN tbl_posto_fabrica ON maiores.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica  = $login_fabrica
				WHERE $cond_pais ";
#		echo nl2br($sql);
#		exit;
		$res = pg_exec ($con,$sql);
		#echo nl2br($sql);
		$total_mao_de_obra = 0 ;
		$total_pecas = 0 ;
		$total_qtde = 0 ;
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$cor = "#F7F5F0"; 
			if ($i % 2 == 0) 
			{
				$cor = '#F1F4FA';
			}
			
			echo "<tr   style='background-color: $cor;'>";
			
			echo "<td align='left'>";
			echo pg_result ($res,$i,codigo_posto);
			echo "</td>";
			
			echo "<td align='left'>";
			echo pg_result ($res,$i,nome);
			echo "</td>";
			
			echo "<td align='center'>";
			echo pg_result ($res,$i,estado);
			echo "</td>";
			
			echo "<td align='right'> ";
			$qtde = pg_result ($res,$i,qtde);
			echo $qtde;
			echo "</td>";
			
			echo "<td align='right'>";
			$mao_de_obra = pg_result ($res,$i,mao_de_obra);
			echo number_format ($mao_de_obra,2,",",".");
			echo "</td>";
			
			echo "<td align='right'>";
			$pecas = pg_result ($res,$i,pecas);
			echo number_format ($pecas,2,",",".");
			echo "</td>";
			
			echo "<td align='right'>";
			$total = $mao_de_obra + $pecas ;
			echo number_format ($total,2,",",".");
			echo "</td>";
			
			echo "</tr>";
			
			$total_mao_de_obra += pg_result ($res,$i,mao_de_obra) ;
			$total_pecas       += pg_result ($res,$i,pecas) ;
			$total_qtde        += pg_result ($res,$i,qtde) ;
		}
		
		$total = $total_mao_de_obra + $total_pecas ;

		$colspan='3';
		
		echo "<tr class='menu_top'>";
		echo "<td align='rigth' colspan='$colspan'>";
		echo "&nbsp;&nbsp;PERCENTUAL: ";
		if ($total_geral > 0 ) $perc = $total / $total_geral * 100 ;
		echo number_format ($perc,0) . "% do total";
		echo "</td>";
		
		echo "<td align='right'>";
		echo $total_qtde;
		echo "</td>";
		
		echo "<td align='right'>";
		echo number_format ($total_mao_de_obra,2,",",".");
		echo "</td>";
		
		echo "<td align='right'>";
		echo number_format ($total_pecas,2,",",".");
		echo "</td>";
		
		echo "<td align='right'>";
		echo number_format ($total,2,",",".");
		echo "</td>";
		echo "</tr>";
		
		echo "</table>";
		echo "<p>";	
		
		flush();
		
		
		
	}
}

include "rodape.php"; 

?>
