<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

include_once '../helpdesk/mlg_funciones.php';
include_once 'autentica_admin.php';


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

	if (trim($ano) == '') {
		$msg_erro[] = 'Informe o Ano para fazer a pesquisa';
	} else {

		// Valida ano inicial e final
		$sql_aim = "SELECT MIN(EXTRACT(year FROM data_abertura)) FROM tbl_os WHERE fabrica = $login_fabrica"; //ano_inicial_min
		$res_aim = pg_query($con, $sql_aim);
		$ano_min = pg_fetch_result($res_aim, 0,0);
		$ano_min_garantia = $ano_min; // Preserva o anterior
		$ano_max = date('Y');

		if (in_array($login_fabrica, array(20,96))) { // Bosch [SECURITY] usa tbl_os_orcamento...
			$sql_aim = "SELECT MIN(EXTRACT(year FROM abertura)) FROM tbl_os_orcamento"; //ano_inicial_min
			$res_aim = pg_query($con, $sql_aim);
			$ano_min = pg_fetch_result($res_aim, 0,0);
			if ($login_fabrica == 96){
				$ano_min_garantia = 2005;
				$ano_min = 2005;
			}
		}
	//p_echo("$login_fabrica: Ano mínimo: $ano_min / $ano_min_garantia, atual: $ano_max, solicitado: $ano");

		if (($tipo_relatorio == 'os' and $ano < $ano_min) or
			($tipo_relatorio != 'os' and $ano < $ano_min_garantia) or $ano > $ano_max) {
			$msg_erro[] = ($ano <= $ano_max) ? "Não existem dados anteriores a $ano_min." :
											  'Não existem dados além da data atual.';
		}
		else {
			$data_inicial = "$ano-01-01 00:00:00";
			$data_final = "$ano-12-31 23:59:59";
		}
	}

	//  Valida o código do Posto
	if (strlen(trim($codigo_posto))>0) {
		$sql_posto = "SELECT posto, nome AS nome_posto FROM tbl_posto_fabrica JOIN tbl_posto USING(posto)
					   WHERE codigo_posto = '$codigo_posto'
					   	 AND fabrica	  = $login_fabrica
						 AND credenciamento = 'CREDENCIADO'";
		$res_posto = @pg_query($con, $sql_posto);
		if (!is_resource($res_posto)) {
			$msg_erro[] = "Erro ao conferir o código do Posto.";
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
		if($login_fabrica == 96)
			$condicao_orcamento = 'AND tbl_os.tipo_atendimento <> 93 ';

		$sql_g = " SELECT COUNT(os) AS total_os, EXTRACT(month FROM data_abertura) AS mes
					 FROM tbl_os
					WHERE fabrica = $login_fabrica
					AND data_abertura BETWEEN '$data_inicial' AND '$data_final'
					$condicao_orcamento
				GROUP BY EXTRACT(month FROM data_abertura)
				ORDER BY mes";
		$res_g = @pg_query($con, $sql_g);
		if (!is_resource($res_g)) {
			$msg_erro[] = 'Erro na consulta.';
			if ($login_admin == 1375 || $login_admin == 3011) {
				$msg_erro[]=$sql_g;
				$msg_erro[]=pg_last_error($con);
			}
		} else {
			$dados_g = pg_fetch_all($res_g);
		}
		if($login_fabrica != 96)
			$sql_o = " SELECT COUNT(os_orcamento) AS total_or, EXTRACT(month FROM abertura) AS mes
					 FROM tbl_os_orcamento
					WHERE /* fabrica = $login_fabrica -- por enquanto esta tabela não tem campo fabrica */
					/* AND posto = xxx -- BOSCH Security será um 'posto Interno' da Bosch... */
						 abertura BETWEEN '$data_inicial' AND '$data_final'
				GROUP BY EXTRACT(month FROM abertura)
				ORDER BY mes ASC";
		else
			$sql_o = " SELECT COUNT(os) AS total_or, EXTRACT(month FROM data_abertura) AS mes
					 FROM tbl_os
					WHERE  fabrica = $login_fabrica
						   AND data_abertura BETWEEN '$data_inicial' AND '$data_final'
						   AND tbl_os.tipo_atendimento = 93
					GROUP BY EXTRACT(month FROM data_abertura)
					ORDER BY mes ASC";

		$res_o = @pg_query($con, $sql_o);
		if (!is_resource($res_o)) {
			$msg_erro[] = 'Erro na consulta.';
			if ($login_admin == 1375) {
				$msg_erro[]=$sql_o;
				$msg_erro[]=pg_last_error($con);
			}
		} else {
			$dados_o = pg_fetch_all($res_o);
		}
	}


	// Relatório gráfico de linha, mostra a comparativa de dois anos, o solicitado e o anterior.
	if (in_array($tipo_relatorio, array('an', 'ant', 'quad')) and !count($msg_erro)) {
		if ($ano < $ano_min_garantia) {
			$msg_erro[] = "Não existem dados anteriores a $ano_min_garantia.";
		} else {
			// Este deveria ser o resultado final...
			/*pre_echo (array(
			  '2008' => array(1 => 25, 30, 45, 12, 28, 50, 40, 33, 22, 35, 67, 18, 15),
			  '2009' => array(1 => 25, 50, 40, 33, 22, 35, 30, 45, 12, 28, 67, 18, 15),
			  '2010' => array(1 => 25, 30, 45, 22, 35, 67, 18, 12, 28, 50, 40, 33, 15),
			  '2011' => array(1 => 40, 33, 22, 35, 67, 18, 15, 25, 30, 45, 12, 28, 50)
			  ), "Exemplo");*/

			function pg_query2array($conn, $sql, $idx = 0) {
				if (!is_resource($conn)) return false; //Sai se não há conexão com o banco de dados

				$res = @pg_query($conn, $sql);
				if (!is_resource($res)) return pg_last_error($conn); //Sai se deu erro na query

				if (pg_num_rows($res) == 0) return 0;

				$a_temp = pg_fetch_all($res);
				if (!is_array($a_temp)) return false;

				foreach($a_temp as $registro) {
					$result[$idx][$registro['mes']] = $registro['total_os'];
				}
				return $result;
			}


			$sql_ab_ano = " SELECT COUNT(os) AS total_os, EXTRACT(month FROM data_abertura) AS mes
				FROM tbl_os
				WHERE fabrica = $login_fabrica
				AND data_abertura BETWEEN '$data_inicial' AND '$data_final'
				GROUP BY EXTRACT(month FROM data_abertura)
				ORDER BY mes";

			$sql_fech_ano = " SELECT COUNT(os) AS total_os, EXTRACT(month FROM data_fechamento) AS mes
				FROM tbl_os
				WHERE fabrica = $login_fabrica
				AND data_fechamento BETWEEN '$data_inicial' AND '$data_final'
				GROUP BY EXTRACT(month FROM data_fechamento)
				ORDER BY mes";


			$sql_ab_ant = " SELECT COUNT(os) AS total_os, EXTRACT(month FROM data_abertura) AS mes
				FROM tbl_os
				WHERE fabrica = $login_fabrica
				AND data_abertura BETWEEN '$data_inicial'::date - INTERVAL '1 YEAR'
				AND '$data_final'::date - INTERVAL '1 YEAR'
				GROUP BY EXTRACT(month FROM data_abertura)
				ORDER BY mes";

			$sql_fech_ant = " SELECT COUNT(os) AS total_os, EXTRACT(month FROM data_fechamento) AS mes
				FROM tbl_os
				WHERE fabrica = $login_fabrica
				AND data_fechamento BETWEEN '$data_inicial'::date - INTERVAL '1 YEAR'
				AND '$data_final'::date - INTERVAL '1 YEAR'
				GROUP BY EXTRACT(month FROM data_fechamento)
				ORDER BY mes";



			$a_ab_ano   = pg_query2array($con, $sql_ab_ano, $ano);
			$a_fech_ano = pg_query2array($con, $sql_fech_ano, $ano);

			if (!is_array($a_ab_ano) or !is_array($a_fech_ano)) {
				$msg_erro[] = ($a_ab_ano === false or $a_fech_ano === false) ? 'Erro ao recuperar as informações.' : 'Sem informações no período.';
			} else {
				foreach($a_ab_ano[$ano] as $mes => $total_os) {
					$tot_fech = $a_fech_ano[$ano][$mes];
					$a_dados_por_ano[$ano][$mes] = ($tot_fech != 0) ? round($total_os / $tot_fech, 2) : 0;
				}
			}

			// Se o usuário pede ver o ano anterior para comparar...
			if (in_array($tipo_relatorio, array('ant', 'quad')) and ($ano >= $ano_min_garantia) and !count($msg_erro)) {

				$verifica_ano_minimo = ($tipo_relatorio=='ant') ? 1 : 3;
				$c = 0;
				//echo "Ano FIM: " . $verifica_ano_minimo;
				$contador_for = $verifica_ano_minimo;
				for ($ano_ant = $ano - $verifica_ano_minimo; $ano_ant <  $ano; $ano_ant++) {
					//$sql_ab_ant = str_replace($c." YEAR",$c + 1 ." YEAR",$sql_ab_ant);
					//$sql_fech_ant = str_replace($c." YEAR",$c++ ." YEAR",$sql_fech_ant);


					//sql_ab_ant		= str_replace($c++." YEAR",$c." YEAR",$sql_ab_ant);
					//$sql_fech_ant	= str_replace($c." YEAR",$c." YEAR",$sql_fech_ant);
					$c++;
					if(in_array($tipo_relatorio, array('quad'))){
						if($c == '1'){
							$sql_ab_ant		= str_replace("1 YEAR","3 YEAR",$sql_ab_ant);
							$sql_fech_ant	= str_replace("1 YEAR","3 YEAR",$sql_fech_ant);
						}
						if($c == '2'){
							$sql_ab_ant		= str_replace("3 YEAR","2 YEAR",$sql_ab_ant);
							$sql_fech_ant	= str_replace("3 YEAR","2 YEAR",$sql_fech_ant);
						}
						if($c == '3'){
							$sql_ab_ant		= str_replace("2 YEAR","1 YEAR",$sql_ab_ant);
							$sql_fech_ant	= str_replace("2 YEAR","1 YEAR",$sql_fech_ant);
						}
					}else{
						$sql_ab_ant		= str_replace("1 YEAR","1 YEAR",$sql_ab_ant);
						$sql_fech_ant	= str_replace("1 YEAR","1 YEAR",$sql_fech_ant);
					}
					//echo "CONTADOR =".$c;
					$a_ab_ant   = pg_query2array($con, $sql_ab_ant, $ano_ant);
					$a_fech_ant = pg_query2array($con, $sql_fech_ant, $ano_ant);
					//echo "<br>";
					//echo $sql_fech_ant;
					//echo "<br><br><br>";
					//pre_echo($a_ab_ant, 'Dados AB ano anterior '. $ano_ant . '- ' . count($a_ab_ant));
					//pre_echo($a_fech_ant, 'Dados FECH ano '. $ano_ant . '- ' . count($a_fech_ant));
					if (!is_array($a_ab_ant) or !is_array($a_fech_ant)) {
						if ($a_ab_ant === false or $a_fech_ant === false) $msg_erro[] = 'Erro ao recuperar as informações.';
					} else {
						if (count($a_ab_ant[$ano_ant]) > 1 and count($a_fech_ant[$ano_ant])>1) {
							foreach($a_ab_ant[$ano_ant] as $mes => $total_os) {
								$tot_fech = $a_fech_ant[$ano_ant][$mes];
								$a_dados_por_ano[$ano_ant][$mes] = ($tot_fech != 0) ? round($total_os / $tot_fech, 2) : 0;
								//echo $a_dados_por_ano[$ano_ant][$mes]." ===  ABERTO =".$total_os." --FECHADO".$tot_fech."<BR>";
							}
						}
					}
				}

				ksort($a_dados_por_ano);

				//pre_echo($sql_ab_ano, 'Dados AB ano anterior '. $ano_ant . '- ' . count($a_ab_ant));
				//pre_echo($sql_fech_ano, 'Dados FECH ano '. $ano_ant . '- ' . count($a_fech_ant));
			}
		}
	}
}

$layout_menu = "auditoria";
$title = "OS ABERTAS EM GARANTIA E FORA DE GARANTIA";
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

<script type="text/javascript">
$().ready(function(){
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
		<tr><td colspan='2'>&nbsp;</td></tr>
 		<tr valign='middle'>
 			<td width='50%' class='espaco'>
				<label for='ano'>Ano</label><br />
				<select name="ano" id="ano" class="frm">
				<?php

					$sql = "SELECT MIN(EXTRACT(year FROM data_abertura)) FROM tbl_os WHERE fabrica = $login_fabrica"; //ano_inicial_min
					$res = pg_query($con,$sql);
					if(pg_num_rows($res)) {
						$ano_atual = date('Y');
						$ano = pg_result($res,0,0);
						for($i=$ano_atual;$i>=$ano; $i--) {

							$check = ($_POST['ano'] == $i) ? 'selected' : '';
							echo '<option value="'.$i.'" '.$check.'>'.$i.'</option>';

						}
					}

				?>
				</select>
			</td>
			<td>
				<label for='ano'>Tipo de Gráfico</label><br />
				<select id='tipo_rel' name='tipo_relatorio' class='frm'>
					<option value='os'  <?=($tipo_relatorio=='os') ? " selected='selected'":''?>>Relatório de Manutenção</option>
					<option value='an'  <?=($tipo_relatorio=='an') ? " selected='selected'":''?>>Relatório anual OS/Fechadas/mês   </option>
					<option value='ant' <?=($tipo_relatorio=='ant')? " selected='selected'":''?>>Relatório bienal OS/Fechadas/mês  </option>
					<?php
					if($login_fabrica == '96'){
					?>
					<option value='quad' <?=($tipo_relatorio=='quad')? " selected='selected'":''?>>Relatório Quadrienal OS/Fechadas/mês  </option>
					<?php
					}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td colspan='2' align='center'>
				<input type='hidden' value='pesquisa' name='btn_acao'>
				<button type='submit'>Pesquisar</button>
			</td>
		</tr>
 	</tbody>
 </table>
</form>

<?
// pre_echo($dados, 'Informações');

if ($tipo_relatorio== 'os' and count($dados_o)) {
	//Criando o array $a_dados para que os meses não saiam de ordem
	foreach($dados_o as $indice => $dados) {
		$a_dados[$dados["mes"]] = array();
	}

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
		$a_per_garantia[$mes]  = round(($tot_os / ($tot_os + $tot_or)*100), 2);
		$a_per_orcamento[$mes] = round(($tot_or / ($tot_os + $tot_or)*100), 2);

		/**
		 * @hd 746228 - adicionado a qtde de OS
		 */
		$a_qtde_garantia[$mes]  = $tot_os;
		$a_qtde_orcamento[$mes] = $tot_or;
	}

	$geral_garantia  = round((array_sum($a_per_garantia) / count($a_per_garantia)), 2);
	$geral_orcamento = round((array_sum($a_per_orcamento)/count($a_per_orcamento)), 2);

	$geral_qtde_garantia  = array_sum($a_qtde_garantia);
	$geral_qtde_orcamento = array_sum($a_qtde_orcamento);

	$meses = array(1 => 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez');
	$legenda_horizontal = array_keys($a_dados);
	foreach($legenda_horizontal as $indice => $mes) {
		$legenda_horizontal[$indice] = $meses[$mes];
	}

	$graph_data = "cht=bvs&chs=640x460"; //Bars, Vertical, Stacked
	$graph_data.= "&chco=9c99c4,70465f" . //Cores das barras
				  "&chd=t:" . implode(',', $a_per_garantia) . ",$geral_garantia" . '|' . implode(',', $a_per_orcamento) . ",$geral_orcamento" . //Dados
				  //"&chds=" . max($a_per_garantia) . '|' . max($a_per_orcamento) . // Escala
				  '&chbh=28,15' . // Largura da barra, espaço entre barras
				  "&chdl=Garantia|Fora de Garantia" .
				  '&chm=N*f0*%,ffffff,0,-1,13,,c|N*f0*%,ffff99,1,-1,13,,e&chdlp=t' .
				  "&chxt=x,y&chxl=0:|". implode('|', $legenda_horizontal).'|Media'. // Nome dos meses
				  "&chtt=Manutençao+$ano&chts=000033,15"; //E, finalmente, o título do relatório

	//echo "<p>&nbsp;</p><img src='https://chart.googleapis.com/chart?$graph_data' alt='grafico' />\n";
	ob_start();
?>
<p>&nbsp;</p>
<div style="float: left; width: 650px;">
<div style="float: left; width: 320px;">
<table class='tabela' width='300'>
	<colgroup>
		<col>
		<col>
		<col align='right'>
	</colgroup>
	<caption>OS Garantia vs. Fora de Garantia</caption>
	<thead>
		<tr>
			<th>Mês</th>
			<th>Garantia</th>
			<th>Fora de Garantia</th>
		</tr>
	</thead>
	<tbody>
<?	for($i = 1; $i < count($a_per_garantia) + 1; $i++) {
		$g = number_format($a_per_garantia[$i], 2, ',', '.');
		$o = number_format($a_per_orcamento[$i],2, ',', '.');
?>
		<tr>
			<td style='font-weight:bold'><?=$meses[$i]?></td>
			<td align='right'><?=$g?>%</td>
			<td align='right'><?=$o?>%</td>
		</tr>
<?	}?>
		<tr style='font-weight:bold;background-color:#f0f0f0'>
			<td>Média</td>
			<td align='right'><?=number_format($geral_garantia, 2, ',', '.')?>%</td>
			<td align='right'><?=number_format($geral_orcamento,2,  ',', '.')?>%</td>
		</tr>

	</tbody>
</table>
</div>

<?php
if ($login_fabrica == 96) {
	?>
	<div style="float: left; width: 320px;">
	<table class='tabela' width='230'>
		<colgroup>
			<col>
			<col>
			<col align='right'>
		</colgroup>
		<caption>Quantidade</caption>
		<thead>
			<tr>
				<th>Mês</th>
				<th>Garantia</th>
				<th>Fora de Garantia</th>
			</tr>
		</thead>
		<tbody>
			<?php
			$limit = count($a_qtde_garantia) + 1;

			for($i = 1; $i < $limit; $i++) {

				echo '<tr>
						<td style="font-weight:bold">' , $meses[$i] , '</td>
						<td align="center">' , $a_qtde_garantia[$i] , '</td>
						<td align="center">' , $a_qtde_orcamento[$i] , '</td>
			 	  	  </tr>';
			}
			?>
			<tr style='font-weight:bold;background-color:#f0f0f0'>
				<td>Total</td>
				<td align='center'><?php echo $geral_qtde_garantia; ?></td>
				<td align='center'><?php echo $geral_qtde_orcamento; ?></td>
			</tr>
		</tbody>
	</table>
	</div>
</div>
	<?php
}
?>

<?
	$tabela = ob_get_contents();
	ob_clean();
//	include 'rodape.php';
//	exit;
}

if (in_array($tipo_relatorio, array('an', 'ant', 'quad')) and count($a_dados_por_ano) and !count($msg_erro)) {
	foreach($a_dados_por_ano as $ano => $info) {
		// Se os dados do ano em questão forem inimpletos, deixar como 0
		if (count($info != 12)/* and $tipo_relatorio == 'ant' and count($a_dados_por_ano > 1)*/) {
			for ($a = 1; $a < 12; $a++) {
				$info[$a] = (isset($info[$a])) ? $info[$a] : '0';
			}
		}
		$data_label[] = "$ano";
		$data_string[]= implode(',', $info);
		$a_min[] = min($info);
		$a_max[] = max($info);
	}
	$max_value = round(max($a_max) + 0.5);
	$min_value = round(min($a_min));
	if ($min_value < 1) $min_value = 0;

	$graph_data = "chxl=0:||Jan|Fev|Mar|Abr|Mai|Jun|Jul|Ago|Set|Out|Nov|Dez".
				 "&chxp=0,0".
				 "&chxr=0,1,12|1,0,$max_value".
				 "&chds=0,$max_value".
				 "&chxs=0,676767,11,0.45,lt,4A4A4A".
				 "&chxt=x,y".
				 "&chs=640x460".
				 "&cht=lc" .
				 "&chco=3072F3,FF0000,32CD32,FFD700"  .// implode(',', $data_cores).
				 "&chd=t:" . implode('|', $data_string).
				 "&chdl="  . implode('|', $data_label) .
				 "&chdlp=b".
				 "&chma=10,10,5,25".
				 "&chtt=OS+Abertas+vs+OS+Fechadas+por+ano+e+mês".
				 "&chts=676767,14";
}
?>
<table WIDTH = '700px' style="background:;" border='0'>
	<tr>
		<td  valign='bottom'>
<?php
if ($graph_data != '') {
	echo "<p><br /><img src='https://chart.googleapis.com/chart?$graph_data' alt='grafico' />\n<br /></p>";
	echo $tabela;
	if ($tipo_relatorio == 'ant' and count($data_label) == 1) {
		echo "<h2 class='erro'>Não há informações sobre o ano de " . ($data_label[0] - 1) . ", mostrando apenas o ano informado.</h2>";
	}
} else {
	if ($_POST and !count($msg_erro)) echo "<h2 class='erro'>Não há informações a mostrar com estes parâmetros</h2>";
}
?>
		</td>
<?php
	//pre_echo($a_dados_por_ano, 'Dados anuais');
if ($graph_data != '' and in_array($tipo_relatorio, array('an', 'ant', 'quad')) and $login_fabrica == 96) {

	$tamanho_grafico = 420;
	$dez_px = $tamanho_grafico / $max_value;

	$altura_verde = 0.8 * $dez_px;
	$altura_amarelo = 1.2 * $dez_px;

	$verde	= round($altura_verde);
	$amarelo	= round($altura_amarelo-$altura_verde);
	$vermelho	= $tamanho_grafico - round($amarelo+$verde);
?>
		<td valign='bottom' style='width: 60px; text-align: center;'>
			<div style='width: 30px; height: <?php echo $vermelho?>px; margin: 0 auto; background: #D62020;'></div>
			<div style='width: 30px; height: <?php echo $amarelo?>px; margin: 0 auto; background: #FFCC33;'></div>
			<div style='width: 30px; height: <?php echo ($verde)?>px; margin: 0 auto; background: #009966 ; text-align: center '></div>
			<!-- <div style='width: 60px; height: 41px; margin: 0 auto; background: url(imagens/QuadrienalSetaVerde.jpg) no-repeat bottom left'></div> //-->
			<div style='height: 47px;'></div>
		</td>
<?php
}
?>
	</tr>
</table>
<?php
include 'rodape.php';
