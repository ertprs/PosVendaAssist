<?php
include_once '/var/www/assist/www/dbconfig.php';
include_once '/var/www/assist/www/includes/dbconnect-inc.php';

include_once '../helpdesk/mlg_funciones.php';
include_once 'autentica_admin.php';

header('Content-Type: text/html; charset=iso-8859-1');

/**
 *  Relatório gráfico de OS aberta há mais de X dias sem análise do Posto,
 *  comparando com o nº de OS abertas no período de uma semana a contar desde esses X dias.
 *
 *  author: Manuel López
 *  (c):    Telecontrol Networking, Ltda.
 */

# Pesquisa pelo AutoComplete AJAX
if ($_GET['ajax'] == 'posto' and isset($_GET['q'])) {
	//  O preg_replace é para trocar qualquer caractere que não seja letra ou número num '.',
	//	assim, 'eletrônica' vai achar 'eletronica' (mas não ao contrário...)
	$q		= preg_replace('/\W/', '.', strtolower(anti_injection($_GET["q"])));
	$busca	= $_GET['busca'];

	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica
				  AND credenciamento = 'CREDENCIADO'";

		if ($busca == 'codigo'){
			$sql .= " AND tbl_posto_fabrica.codigo_posto LIKE UPPER('$q%') ";
		}else{
			$q = utf8_decode(anti_injection($_GET["q"]));
			$q = tira_acentos($q);
			$nome = preg_replace('/(\W)/', '($1|.)', $q);
			$sql .= " AND tbl_posto.nome ~* '$nome'";
		}

		$res= @pg_query($con,$sql);
		$tp = @pg_num_rows($res);
		if ($tp) {
			for ($i=0; $i<$tp; $i++){
				extract(pg_fetch_array($res,$i));
				echo "$cnpj|$nome|$codigo_posto\n";
			}
		}
	}
	exit;
}

if ($_GET['ajax']=='rv_cidade' and isset($_GET['q'])) {
	$q = utf8_decode(anti_injection($_GET["q"]));
	$q = tira_acentos($q);
	$cidade = preg_replace('/(\W)/', '($1|.)', $q);
	$limite = anti_injection($_GET['limit']);
	$estado = anti_injection($_GET['estado']);
    if (is_numeric($limite)) $limite = "LIMIT $limite";

	if (strlen($estado)==2) $w_estado = "estado = '$estado' AND";

	$sql_c = "SELECT cidade, estado FROM tbl_ibge
			   WHERE $w_estado TRANSLATE(TRIM(cidade),
										'áâàãäéêèëíîìïóôòõúùüçÁÂÀÃÄÉÊÈËÍÎÌÏÓÔÒÕÚÙÜÇ',
										'aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC'
							   ) ~* '$cidade'
	   ORDER BY estado, cidade $limite";

	$res_c = pg_query($con, $sql_c);
	if (!is_resource($res_c) or @pg_num_rows($res_c) == 0) exit();
	$cidades = pg_fetch_all($res_c);

	foreach ($cidades as $info_cidade) {
		extract($info_cidade);
		echo "$cidade|$estado\n";
    }
	exit;
}

$dados = array();
if (count(array_filter($_POST)) or count(array_filter($_GET))) {
	$parametros = (count($_POST)) ? array_map('anti_injection', $_POST) : array_map('anti_injection', $_GET);
	extract($parametros);

//  Valida a data final
	if (!$data_final) $data_final = date('d-m-Y');
	list($d, $m, $y) = preg_split('/\D/', $data_final);
	$data_final = "$y-$m-$d";

	if (!checkdate($m, $d, $y) or
		!is_between(strtotime($data_final), strtotime('today -4 months'), strtotime('today'))) {
		$msg_erro[] = 'Data inválida!';
		$dada_final = false;
	} else {
		$ts_data_final = strtotime($data_final);
	}

//  Valida o código do Posto
	if (strlen($codigo_posto)>0) {
		$sql_posto = "SELECT posto, nome AS nome_posto FROM tbl_posto_fabrica JOIN tbl_posto USING(posto)
					   WHERE codigo_posto = '$codigo_posto'
					   	 AND fabrica	  = $login_fabrica
							AND credenciamento = 'CREDENCIADO'";
		$res_posto = @pg_query($con, $sql_posto);
		if (!is_resource($res_posto)) {
			$msg_erro[] = "Erro ao conferir o código do Posto!";
			$codigo_posto = false;
		} elseif (pg_num_rows($res_posto) != 1) {
			$msg_erro[] = "Código do Posto ($codigo_posto) não existe ou não está credenciado";
			$codigo_posto = false;
		} else {
			extract(pg_fetch_assoc($res_posto, 0));
			$w_posto = "AND tbl_os_demanda.posto = $posto";
		}
	} else if (strlen($codigo_posto)==0 and !$estado and !$cidade) {
		$msg_erro[]= 'Selecione o Posto';
	} else {
		$w_posto = "AND credenciamento = 'CREDENCIADO'";
	}

	if ($cidade) {
		$cidade   = preg_replace('/\W/', '.', $cidade);
		$w_cidade = "AND tbl_posto_fabrica.contato_cidade ~* '$cidade'";
	}

	if ($estado) {
		if (strlen($estado) != 2) {
			$msg_erro[] = 'Estado inválido!';
		} else {
			$w_estado = "AND tbl_posto_fabrica.contato_estado = '$estado'";
		}
	}

// if ($_POST) pre_echo($_POST);
// if (count($msg_erro)) pre_echo($msg_erro);
	if (!count($msg_erro)) {
//  Segundo inteação da validação, tem que mostrar os dados semanalmente, de segunda em segunda-feira e até 6 meses
		$data_final		= pg_fetch_result(pg_query($con, "SELECT DATE_TRUNC('week', current_date)::date AS data_inicial"), 0, 0);
		$ts_monday		= strtotime($data_final);
		$data_inicial	= date('Y-m-d', strtotime('-24 weeks', $ts_monday)); // timestamp da data inicial= $data_final - 6 meses
		$ts_data_inicial= strtotime($data_inicial);

		$intervalo = 7; // 1: todas as datas. 7, todas as segundas-feiras
		if ($intervalo != 1) {
			$intervalo = "-$intervalo days"; // De X em X dias...
			$mondays[] = date('Y-m-d', $ts_monday);
			while ($ts_monday > $ts_data_inicial) {
				$ts_monday = strtotime($intervalo, $ts_monday);
				$new_monday = date('Y-m-d', $ts_monday);
				$mondays[] = $new_monday;
	        }

			asort($mondays);
			$datas = "data_geracao IN ('" . implode("','", $mondays) . "')";
		} else {
			$datas = "data_geracao BETWEEN '$data_inicial' AND '$data_final'";
	    }

		$sql_d = "SELECT data_geracao, tbl_os_demanda.posto,
					   codigo_posto, tbl_posto.nome AS nome_posto,
					   contato_cidade AS cidade, tbl_estado.nome AS estado,
					   qtde_os, qtde_os_sem_analise
				  FROM tbl_os_demanda
				  JOIN tbl_posto USING(posto)
				  JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto	 = tbl_os_demanda.posto
										AND tbl_posto_fabrica.fabrica= tbl_os_demanda.fabrica
				  JOIN tbl_estado ON tbl_estado.estado = tbl_posto_fabrica.contato_estado
				 WHERE tbl_os_demanda.fabrica = $login_fabrica
				   $w_posto
				   $w_cidade
				   $w_estado
				   AND $datas
				ORDER BY posto, data_geracao";
		$res = @pg_query($con, $sql_d);
		if (!is_resource($res)) {
			$msg_erro[] = 'Erro na consulta!';
			if ($login_admin == 1375) {
				$msg_erro[]=$sql;
				$msg_erro[]=pg_last_error($con);
			}
		} else {
			$dados = pg_fetch_all($res);
		}
	}
}

$layout_menu = "auditoria";
$title = 'OS SEM INTERVENÇÃO X DEMANDA DE OS';
include 'cabecalho.php';
include "javascript_calendario.php";
?>
<style type="text/css">
/*@import url('consultas2.css');*/
	@import url("/assist/admin/js/jquery.autocomplete.css");
	.titulo_tabela, table.formulario>caption, table.formulario>thead{
	    background-color:#596d9b;
	    font: bold 14px "Arial";
	    color:#FFFFFF;
	    text-align:center;
	}
	.formulario{
	    background-color:#D9E2EF;
	    font:11px Arial;
	    text-align:left;
	}
	.formulario label {text-align:left;}

	input[type=search] {-webkit-appearance: none}

	table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border:1px solid #ACACAC;
	border-collapse: collapse;
}
	table.tabela thead,
	.titulo_coluna,
	table.tabela caption {
		background-color: #596D9B;
		color: white;
		text-align: center;
		border: 1px solid #596D9B;
		border-collapse: collapse;
		font: normal bold 11px/14px verdana;
		padding: 0;
		height: 22px;
	}
	.numeric {text-align: right}
	.tabela {border-spacing: 1px;}
	.celula {
	    font-family: verdana;
	    font-size: 11px;
	    border-collapse: separate;
		border-spacing: 1px;
	    border:1px solid #596d9b;
	}
	.impar {
		background-color: #F1F4FA;
	}

	.par {
		background-color: #F7F5F0;
	}

	.msg_erro{
	    background-color:#FF0000;
	    font: bold 16px "Arial";
	    color:#FFFFFF;
	    text-align:center;
		margin: auto;
		width: 700px;
	}

	.espaco {padding:0 0 0 100px;}
</style>
<!--[if lt IE 8]>
<style>
table.tabela{
	empty-cells:show;
    border-collapse:collapse;
	border-spacing: 2px;
}
</style>
<![endif]-->
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<?
	$minDate = date('d/m/Y', strtotime('-6 months'));
	$maxDate = date('d/m/Y');
?>
<script type="text/javascript">
$().ready(function(){
    $('#data_final').datePicker({
		startDate	: "<?=$minDate?>",
		endDate		: "<?=$maxDate?>"});
    $('#data_final').maskedinput("99/99/9999");

	$('#reset').click(function() {
		$('#frm_params input,#frm_params select').val('');
	});

	$('.numeric').keypress(function(e) {
       	if (e.altKey || e.ctrlKey) return true;
       	var k = e.which;
       	var c = String.fromCharCode(k);
       	k = e.keyCode;
       	var allowed = '1234567890';
       	if (allowed.indexOf(c) >= 0) return true;
       	ignore=(k < 16 || (k > 16 && k < 32) || (k > 32 && k < 41));
       	if (ignore || allowed.indexOf(c) < 0 ) return false;
	}).keyup(function(e) {
       	k = e.keyCode;
       	if (k == 86 && e.ctrlKey) $(this).val($(this).val().replace(/\D/g, ''));
	});

	
	/* Busca pelo Nome */
	/*$("#posto_nome").autocomplete("<?=$PHP_SELF?>?ajax=posto&busca=nome", {*/
	

	$('#cidade').autocomplete(location.pathname, {
		minChars: 3,
		delay: 250,
		width: 350,
		extraParams: {
			ajax: 'rv_cidade',
			estado: function() {return $('#estado option:selected').val();}
		},
		matchContains: true,
		formatItem: function(row) {return row[0] + " - " + row[1];},
		formatResult: function(row) {return row[0];}
	}).result(function(event, data) {
		$("#estado").val(data[1]);
	});

	$('#estado').val('<?=$estado?>')
				.change(function() {
					$('#cidade').val('');
	});
	$('#frm_params').submit(function() {
		$('button').attr('disabled', 'disabled');
	});
});

function fnc_pesquisa_posto (campo, campo2, tipo) {

	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value.length >=3) {
		var url = "";
		url = "posto_pesquisa_2<?=$pp_suffix?>.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=t" + "&os=t";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;

		janela.focus();
	}

	else{
		alert("Informar pelo menos 3 caracteres para realizar a pesquisa!");
	}
}
</script>
<? if (is_array($parametros)) extract($parametros); // Só para re-popular o formulário. $parametros vai ter o _POST ou o _GET... ?>
<center>

<form name='frm_params' id='frm_params' action='<?=$PHP_SELF?>' method='post'>
<?if (count($msg_erro)) {?>
<div class="msg_erro"><?=implode('<br>', $msg_erro);?></div>
<?}?>
 <table class='formulario' width='700' align='center' style='margin:auto;padding:auto;'>
 	<caption>Parâmetros de Pesquisa</caption>
 	<tbody>
 		<tr valign='bottom'>
 			<td width='150' class='espaco'>
				<label for='data_final'>Data Final</label> <br />
				<input type="text" name='data_final' id='data_final' value='<?=$data_final?>' size='14' class='frm' />
			</td>
 			<td width='100'>&nbsp;</td>
 		</tr>
 		<tr>
 			<td class='espaco'>
				<label for="codigo_posto">Código do Posto&nbsp;*</label> <br />
				<input type="search" name="codigo_posto" id="codigo_posto" class="frm"
				value="<?=$codigo_posto; ?>" size="15" maxlength="20" />&nbsp;
				<img src="../imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle"
					 onclick="javascript: fnc_pesquisa_posto(document.frm_params.codigo_posto,document.frm_params.posto_nome,'codigo')">
				&nbsp;&nbsp;
			</td>
			<td>
				<label for="posto_nome">Nome do Posto&nbsp;*</label> <br>
				<input type="search" size="40" name='posto_nome' id='posto_nome' class='frm'
					  value='<?=$posto_nome?>'>&nbsp;
				<img src="../imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle"
					  onclick="javascript: fnc_pesquisa_posto(document.frm_params.codigo_posto,document.frm_params.posto_nome,'nome')">
				&nbsp;&nbsp;
			</td>
 		</tr>
		<tr>
			<td class='espaco'>
				<label for="cidade">Cidade</label> <br />
				<input type="search" name='cidade' id='cidade' class='frm' value='<?=$cidade?>'>
			</td>
			<td>
				<label for="estado">Estado</label> <br />
				<select name='estado' id='estado' class='frm'>
					<option value=""></option>
					<option value="AC">Acre</option>
					<option value="AL">Alagoas</option>
					<option value="AP">Amapá</option>
					<option value="AM">Amazonas</option>
					<option value="BA">Bahia</option>
					<option value="CE">Ceará</option>
					<option value="DF">Distrito Federal</option>
					<option value="ES">Espírito Santo</option>
					<option value="GO">Goiás</option>
					<option value="MA">Maranhão</option>
					<option value="MT">Mato Grosso</option>
					<option value="MS">Mato Grosso do Sul</option>
					<option value="MG">Minas Gerais</option>
					<option value="PA">Pará</option>
					<option value="PB">Paraíba</option>
					<option value="PR">Paraná</option>
					<option value="PE">Pernambuco</option>
					<option value="PI">Piauí</option>
					<option value="RJ">Rio de Janeiro</option>
					<option value="RN">Rio Grande do Norte</option>
					<option value="RS">Rio Grande do Sul</option>
					<option value="RO">Rondônia</option>
					<option value="RR">Roraima</option>
					<option value="SC">Santa Catarina</option>
					<option value="SE">Sergipe</option>
					<option value="SP">São Paulo</option>
					<option value="TO">Tocantins</option>
				</select>
			</td>
			
		</tr>
		<tr>
			<td colspan="2" align='center' style='padding-top: 3px'>
			<input type='hidden' value='pesquisa' name='btn_acao'>
				<button type='submit' >Pesquisar</button>
				<span style='display:inline-block;_zoom:1;width:3em'>&nbsp;</span>
				<button type='button' id='reset'>Limpar</button>
			</td>
		</tr>
 	</tbody>
 </table>
</form>
<!-- <?=$sql_d?> -->
<?
// pre_echo($dados, 'Informações');
if (count($dados) and is_array($dados)) {
	$json = "{
	cols:[{id:'data', label:'Data', type:'date'},
		  ";
	foreach ($dados as $a_dado) {
// pre_echo($a_dado);
//  Cada $a_dado tem esta informação:
//  data_geracao, tbl_os_demanda.posto, codigo_posto, tbl_posto.nome AS nome_posto, qtde_os, qtde_os_sem_analise
		extract($a_dado);
		$info[$data_geracao][$posto] = array(
			'cp'		=> $codigo_posto,
			'nome_posto'=> $nome_posto,
			'cidade'    => $cidade,
			'estado'    => $estado,
			compact('qtde_os', 'qtde_os_sem_analise'));
//     	$info[$posto][$data_geracao] = compact('codigo_posto', 'nome_posto', 'qtde_os', 'qtde_os_sem_analise'); // Agrupa as informações por posto e data
    }
	$temp = (reset($info));

	$total_datas	= count($info);
	$total_postos	= count($temp);
	foreach($temp as $posto => $temp_item) {
		extract($temp_item);
		if ($total_postos == 1) {
			$codigo_do_posto = " do posto $cp";
			unset($cp);
		}
		$json.= "
		  {id:'qtde_os_$posto', label:'Demanda de OS $cp', type:'number'},
		  {id:'qtde_os_sem_analise_$posto', label:'Sem Análise $cp', type:'number'},";
	}
	$json = preg_replace('/,$/', '', $json);
	$json.= "],
	rows:  [";
	foreach($info as $data => $info_posto) {
		$rows = Array();
		list($y, $mm, $dd) = preg_split('/\W/', $data);
		$m = (int) $mm;
		$d = (int) $dd;
		$json.= "{c:[{v: new Date($y, $m, $d), f:'$dd-"."$mm'}, ";

		foreach($info_posto as $info_data => $data_info) {
			$codigo_posto = $data_info['cp'];
			$qtde_os	  = $data_info[0]['qtde_os'];
			$qtde_os_si	  = $data_info[0]['qtde_os_sem_analise'];
			$json.= "{v: $qtde_os},{v: $qtde_os_si}, ";
		}
		$json = preg_replace('/,\s?$/', "]},\n", $json);
// 		$json.= "},\n";
	}
	$json = preg_replace('/,\s?$/', '', $json);
	$json.= "]}";
//  pre_echo($rows);
// 	pre_echo($json);

//  Estabelece a altura do gráfico, e que tipo de tabela e gráfico mostrar
	$mostrar_gTabela= ($total_postos == 1);
	$timeLineGraph  = ($total_postos > 6 or $total_datas > 24);
	$altura_grafico	= 400 + (round($total_postos / 10));
	$altura_tabela	= ($total_datas < 19) ? 30 + 20 * $total_datas : 416; // É a altura das 18 linhas
	$altura_tabela	= ($mostrar_gTabela) ? '' : $altura_tabela; // É a altura das 18 linhas
?>
	<p>&nbsp;</p>
<?	if ($mostrar_gTabela) { ?>
	<div id="table_div" align='center'></div>
<?	} else { ?>
		<table class='tabela' width='700' cellspacing='0' cellpadding='2'>
		<thead>
			<tr>
				<th>Código</th>
				<th>Razão Social</th>
				<th>Cidade</th>
				<th>Estado</th>
			</tr>
		</thead>
		<tbody>
<?  asort($temp);
	foreach($temp as $dados_posto) {
		extract($dados_posto);
		$classetr = ($classetr == 'par') ? 'impar':'par';
		?>
			<tr class='<?=$classetr?>'>
				<td>
					<a href="<?=$PHP_SELF."?codigo_posto=$cp&intervalo=$intervalo&data_final=$data_final"?>"" target='_blank'>
					<?=$cp?>
					</a>
				</td>
				<td><?=$nome_posto?></td>
				<td><?=$cidade?></td>
				<td align='center'><?=$estado?></td>
			</tr>
<?}?>
		</tbody>
		</table>
<?}?>
	<p>&nbsp;</p>
<? if ($timeLineGraph) { ?>
	<div id="tline_div" align='center' style='width:800px;height:500px'>
<?} else {
	echo "<div id='graph_div' align='center'>";
}?>		Aguarde enquanto os dados são processados...
	</div>
</center>
<script type="text/javascript" src="http://danvk.org/dygraphs/dygraph-combined.js"></script>
<script type='text/javascript' src='http://www.google.com/jsapi'></script>
<script type='text/javascript'>
	google.load('visualization', '1', {packages:['table','corechart','annotatedtimeline'], 'language' : 'pt_BR'});

	$().ready(function() {
	    var tabela = new google.visualization.DataTable(<?=$json?>, 0.6);
		tabela.setTableProperty('class', 'tabela');
// 		var euro_date_format = new google.visualization.DateFormat({pattern: 'dd/MM/yyyy'});

// 		$.each(heads, function(nome, formato) {
// 			tabela.addColumn(formato, nome);
// 		});
// 		tabela.addRows(data);
// 		euro_date_format.format(tabela, 0); // Formata a data no formato d/m/Y
<? if ($mostrar_gTabela) { 
	$paginacao = ($total_datas > 18) ? "pageSize: 10, page='enable'," : '';	?>
	    var gTabela = new google.visualization.Table(document.getElementById('table_div'));
	    gTabela.draw(tabela, {showRowNumber: false, allowHtml: true,
							  width: 700, 
							  <?=$paginacao?>
								alternatingRowStyle: true,
							  cssClassNames : {
							  		headerRow: 'titulo_coluna',
									tableRow : 'par',
									tableCell: 'celula',
									oddTableRow: 'impar'
							  }
		});
<?}?>
<? if ($timeLineGraph) { ?>
	    var gTimeChart = new google.visualization.AnnotatedTimeLine(document.getElementById('tline_div'));
//	    var gTimeChart = new Dygraph.GVizChart(document.getElementById('tline_div'));
	    gTimeChart.draw(tabela, {
								 dateFormat: 'dd/MM/yyyy',
								 legendPosition: 'newRow'});
//		gTimeChart.draw(tabela,{displayAnnotations:false});
<?} else {?>
	    new google.visualization.LineChart(document.getElementById('graph_div')).draw(
			tabela, {
				width: 800,
				height: <?=$altura_grafico?>,
				legend: '<?=($mostrar_gTabela)?'right':'bottom';?>',
				legendTextStyle: {fontSize: 11},
				title: 'OS abertas x OS sem análise<?=$codigo_do_posto?>',
				vAxis: {
					minValue:	0},
				hAxis: {
					title: 'Data de processamento',
					titleTextStyle: {color: '#444'},
					textStyle: {color: '#900', fontName: 'Arial', fontSize: 10},
					slantedText: true,
					slantedTextAngle: 60,
					showTextEvery: 7
				},
				lineWidth: 1,
				pointSize: 4
		});
<?}?>
	});
</script>
<p style='color: #444;border-top:1px dashed #ccc;padding-top:1ex;width:700px;text-align:left;margin: 1em auto'>
	*	<code style='font-weight:bold'>Demanda de OS&nbsp;&nbsp;</code> Corresponde ao número de OS abertas pelo
	posto durante a semana anterior à data de referência. Por exemplo, para a data de hoje (<?=$maxDate?>),
	corresponderia ao período entre <?=date('d-m-Y', strtotime('-17 days')) . ' e ' . date('d-m-Y', strtotime('-7 days'));?>.
		<br>
	*	<code style='font-weight:bold'>OS Sem Análise&nbsp;</code> Corresponde ao número de OS abertas por mais de 10 dias
	(a partir da data de referência) e sem defeito constatado ou sem solução.
</p>
<img src="../imagens/img_os_demanda.png" alt="Gráfico OS" style='text-align:left;margin: 1em auto' />
<?} else {
	if ($_POST and !count($msg_erro)) echo "<h2 class='erro'>Não há informações a mostrar com estes parâmetros</h2>";
}

include 'rodape.php';
