<?php
include_once '/var/www/assist/www/dbconfig.php';
include_once '/var/www/assist/www/includes/dbconnect-inc.php';

include_once '../helpdesk/mlg_funciones.php';
include_once 'autentica_admin.php';

header('Content-Type: text/html; charset=iso-8859-1');

/**
 *  Relatrório gráfico. Tem duas partes:
 *	1. Relação mensal comparando total de OS abertas e OS em Garantia
 *	2. Linha de tempo, mensal e por ano (uma linha por ano), com a relação
 *	   entre o total de OS abertas no mês, e as que foram fechadas nesse mesmo mês
 *		(data abertura e data fechamento)
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

	// Valida ano inicial e final
	$sql_aim = "SELECT MIN(EXTRACT(year FROM data_abertura)) FROM tbl_os WHERE fabrica = $login_fabrica"; //ano_inicial_min
	$res_aim = pg_query($con, $sql_aim);
	echo $ano_min = pg_fetch_result($res_aim, 0,0);
	echo $ano_max = date('Y');

	if ($ano_inicial < $ano_min or $ano_inicial > $ano_max)
		$msg_erro[] = 'Data inválida!';

	if ($tipo_relatorio == 'an') { //O relatório de OS Garantia/OS Orçamento é por ano, não intervalo de anos
		if ($ano_final < $ano_inicial or $ano_final > $ano_max)
			$msg_erro[] = 'Data inválida!';
	}

	//  Valida o código do Posto
	if (strlen(trim($codigo_posto))>0) {
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
	/*} else if (strlen($codigo_posto)==0 and !$estado and !$cidade) {
		$msg_erro[]= 'Selecione o Posto';*/
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
	// Relatório 1: OS Garantia (tbl_os) vs OS Fora (tbl_os_orcamento)
	if ($tipo_relatorio == 'os' and !count($msg_erro)) {

		$sql_g = " SELECT COUNT(os) AS total_os, EXTRACT(month FROM data_abertura) AS mes
					 FROM tbl_os
					WHERE fabrica = $login_fabrica
					AND EXTRACT(year FROM data_abertura) = $ano_inicial
				GROUP BY EXTRACT(month FROM data_abertura)
				ORDER BY mes";
		$res_g = @pg_query($con, $sql_g);
		if (!is_resource($res_g)) {
			$msg_erro[] = 'Erro na consulta!';
			if ($login_admin == 1375) {
				$msg_erro[]=$sql_g;
				$msg_erro[]=pg_last_error($con);
			}
		} else {
			$dados_g = pg_fetch_all($res_g);
		}

		$sql_o = " SELECT COUNT(os_orcamento) AS total_or, EXTRACT(month FROM abertura) AS mes
					 FROM tbl_os_orcamento
					WHERE /* fabrica = $login_fabrica -- por enquanto esta tabela não tem campo fabrica */
					/* AND posto = xxx -- BOSCH Security será um 'posto Interno' da Bosch... */
						 EXTRACT(year FROM abertura::date) = $ano_inicial
				GROUP BY EXTRACT(month FROM abertura)
				ORDER BY mes";
		$res_o = @pg_query($con, $sql_o);
		if (!is_resource($res_o)) {
			$msg_erro[] = 'Erro na consulta!';
			if ($login_admin == 1375) {
				$msg_erro[]=$sql_o;
				$msg_erro[]=pg_last_error($con);
			}
		} else {
			$dados_o = pg_fetch_all($res_o);
		}
	}
	if ($tipo_relatorio == 'an' and !count($msg_erro)) {
		$a_dados_por_ano = array(
			'2008' => array(1 => 25, 30, 45, 12, 28, 50, 40, 33, 22, 35, 67, 18, 15),
			'2009' => array(1 => 25, 50, 40, 33, 22, 35, 30, 45, 12, 28, 67, 18, 15),
			'2010' => array(1 => 25, 30, 45, 22, 35, 67, 18, 12, 28, 50, 40, 33, 15),
			'2011' => array(1 => 40, 33, 22, 35, 67, 18, 15, 25, 30, 45, 12, 28, 50)
		); // Este deveria ser o resultado final...
	}
}

$layout_menu = "auditoria";
$title = ($tipo_relatorio == 'os') ? 'GRÁFICO DE OS EM GARANTIA vs OS ORÇAMENTO' : 'GRÁFICOS INDICADORES OS';
$title = ($tipo_relatorio == '')   ? 'GRÁFICOS INDICADORES OS' : 'GRÁFICO ANUAL DE RELAÇAO ENTRE OS ABERTAS E FINALIZADAS';
include 'cabecalho.php';
//include "javascript_calendario.php";
?>
<script src='/js/jquery.min.js'></script>
<style type="text/css">
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
<script type="text/javascript">
$().ready(function(){
    $('#ano_inicial').maskedinput("9999");
    $('#ano_final').maskedinput("9999");

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


</script>
<? if (is_array($parametros)) {extract($parametros);} // Só para re-popular o formulário. $parametros vai ter o _POST ou o _GET... ?>
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
				<label for='ano_inicial'>Data Inicial</label> <br />
				<input type="text" name='ano_inicial' id='ano_inicial' value='<?=$ano_inicial?>' size='14' class='frm' />
			</td>
 			<td width='100'>
				<label for='ano_final'>Data Final</label> <br />
				<input type="text" name='ano_final' id='ano_final' value='<?=$ano_final?>' size='14' class='frm' />
			</td>
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
			<td>
				<select id='tipo_rel' name='tipo_relatorio'>
					<option value='os'>Relatório de OS Gar./Orçamento</option>
					<option value='an'>Relatório anual OS/Fechadas/mês</option>
				</select>
			</td>
			<td align='center' style='padding-top: 3px'>
			<input type='hidden' value='pesquisa' name='btn_acao'>
				<button type='submit' >Pesquisar</button>
				<span style='display:inline-block;_zoom:1;width:3em'>&nbsp;</span>
				<button type='button' id='reset'>Limpar</button>
			</td>
		</tr>
 	</tbody>
 </table>
</form>
<!-- <?=$sql_g?> -->
<?
// pre_echo($dados, 'Informações');
if ($tipo_relatorio== 'os' and count($dados_o)) {

	/* Array: mês: Total OS, OS Garantia, OS Orçamento*/
	foreach($dados_g as $os_garantia) {
		extract($os_garantia);
		$a_dados[$mes]['garantia'] = $total_os;
	}
	foreach($dados_o as $os_orcamento) {
		extract($os_orcamento);
		$a_dados[$mes]['orcamento'] = $total_or;
	}
	foreach($a_dados as $mes => $data) {
		$tot_os = $data['garantia'];
		$tot_or = $data['orcamento'];
		$a_per_garantia[$mes]  = ($tot_os / ($tot_os + $tot_or)*100);
		$a_per_orcamento[$mes] = ($tot_or / ($tot_os + $tot_or)*100);
	}

	$graph_data = "cht=bvs&chs=640x460"; //Bars, Vertical, Stacked
	$graph_data.= "&chco=9c99c4,70465f" . //Cores das barras
				  "&chd=t:" . implode(',', $a_per_garantia) . '|' . implode(',', $a_per_orcamento) . //Dados
				  //"&chds=" . max($a_per_garantia) . '|' . max($a_per_orcamento) . // Escala
				  "&chxt=x,y&chxl=0:|". implode('|', array_keys($a_dados)) . // Núms. dos meses
				  "&chtt=Manutençao+$ano_inicial&chts=000033,15"; //E, finalmente, o título do relatório

	echo "<img src='https://chart.googleapis.com/chart?$graph_data' alt='grafico' />\n";

	include 'rodape.php';
	exit;
}

if ($tipo_relatorio== 'an' and count($a_dados_por_ano)) {
	foreach($a_dados_por_ano as $ano => $info) {
		$data_label[] = "$ano";
		$data_string[]= implode(',', $info);
	}

	$graph_data = "chxl=0:||Jan|Fev|Mar|Abr|Mai|Jun|Jul|Ago|Set|Out|Nov|Dez".
				 "&chxp=0,0".
				 "&chxr=0,12".
				 "&chxs=0,676767,11,0.45,lt,4A4A4A".
				 "&chxt=x,y".
				 "&chs=640x460".
				 "&cht=lc".
				 "&chco=3072F3,FF0000".
				 "&chd=t:" . implode('|', $data_string).
				 "&chdl="  . implode('|', $ano_label) .
				 "&chdlp=b".
				 "&chls=2,4,1|1,2,2".
				 "&chma=10,10,5,25".
				 "&chtt=OS+Abertas+vs+OS+Fechadas+por+ano+e+mês".
				 "&chts=676767,14";
}

if ($graph_data != '') {
	echo "<img src='https://chart.googleapis.com/chart?$graph_data' alt='grafico' />\n<br />";
} else {
	if ($_POST and !count($msg_erro)) echo "<h2 class='erro'>Não há informações a mostrar com estes parâmetros</h2>";
}

include 'rodape.php';
