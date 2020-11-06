<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

/* Área do Admin    */
//Opções: 'auditoria', 'cadastros', 'call_center', 'financeiro', 'gerencia'  'info_tecnica'
$admin_privilegios = "financeiro";
include 'autentica_admin.php';

/*------------------*/
include 'funcoes.php';
// Opcional
include '../helpdesk/mlg_funciones.php'; //Admin

if (count(array_filter($_POST)) > 0) { //Se recebeu o formulário..
	extract(array_filter($_POST, 'anti_injection')); // cria as variáveis com os campos do formulário

	if (!empty($data_inicial) || !empty($data_final)) {
		if(strlen($msg_erro)==0 and !empty($data_inicial)) {
			list($di, $mi, $yi) = explode("/", $data_inicial);
			if(!checkdate($mi,$di,$yi))
			$msg_erro = "Data Inválida!";
		}

		if(strlen($msg_erro)==0 and !empty($data_final)){
			list($df, $mf, $yf) = explode("/", $data_final);
			if(!checkdate($mf,$df,$yf))
			$msg_erro = "Data Inválida!";
		}

		if(strlen($msg_erro)==0){
			if(strtotime("$mf/$df/$yf") < strtotime("$mi/$di/$yi")
			or strtotime("$mf/$df/$yf") > strtotime('today')) {
				$msg_erro = "Data Inválida!";
			}
		}

		if(strlen($msg_erro)==0){
			if (pg_fetch_result(pg_query($con, "SELECT '$yi-$mi-$di'::date < '$yf-$mf-$df'::date + INTERVAL '-1 month' "), 0) == 't') {
				$msg_erro = 'O intervalo entre as datas não pode ser maior que 1 mês.';
			}
		}
		if(strlen($msg_erro)==0){
			$data_inicial = "$yi-$mi-$di 00:00:00";
			$data_final   = "$yf-$mf-$df 23:59:59";
		}
	} else {
		$msg_erro = 'Informe o período para a Consulta';
	}

/* INI Debug:

p_echo ( "Data inicial $data_inicial, <b>Data final</b>: $data_final");
$msg_erro .= "Data final - 1 mês com SQL:<br><code>SELECT '$yi-$mi-$di'::date < '$yf-$mf-$df'::date + INTERVAL '-1 month'</code>";
if (count($msg_erro) and $login_admin == 1375) echo "<h2>$msg_erro</h2>\n<textarea style='width:693px;height:220px'>".
	 preg_replace(array('/^\s+/', '/\s+,/', '/\s+/'),
				  array('', ', ', ' '), $sql_rel).
	 "</textarea>\n";**/
//exit();
/* FIM Debug */
	if (!$msg_erro) { // Gerar o relatório
		$sql_rel = <<<SQL_QUERY
SET datestyle TO postgres, dmy;
SELECT
tbl_familia.descricao AS LINHA_PRODUTO,
tbl_esmaltec_categoria_produto.segmento AS SEGMENTO,
tbl_extrato.data_geracao::date AS DAT_GERACAO_EXTRATO,
tbl_os.sua_os AS ORDEM_SERVICO,
tbl_esmaltec_categoria_produto.linha AS LINHA,
tbl_os.data_abertura AS DAT_ORDEM_SERVICO,
tbl_os.data_fechamento AS DAT_FECHAMENTO,
tbl_os.data_fechamento - tbl_os.data_abertura AS TEMPO_ATENDIMENTO,
tbl_os.data_nf AS DAT_COMPRA,
SUBSTR(tbl_esmaltec_item_servico.codigo, 9, 2) AS LST_GARANTIA,
RPAD(SUBSTR(tbl_defeito_constatado.codigo, 1, 2), 6, '0') AS COD_DEF_PRODUTO,
(SELECT descricao FROM tbl_defeito_constatado AS tbl_defeito_constatado_interno WHERE fabrica=$login_fabrica AND codigo=RPAD(SUBSTR(tbl_defeito_constatado.codigo, 1, 2), 6, '0')) AS DES_DEF_PRODUTO,
RPAD(SUBSTR(tbl_defeito_constatado.codigo, 1, 4), 6, '0') AS COD_GRUP_DEFEITO,
(SELECT descricao FROM tbl_defeito_constatado AS tbl_defeito_constatado_interno WHERE fabrica=$login_fabrica AND codigo=RPAD(SUBSTR(tbl_defeito_constatado.codigo, 1, 4), 6, '0')) AS DES_GRUP_DEFEITO,
tbl_defeito_constatado.codigo AS COD_DEFEITO,
tbl_defeito_constatado.descricao AS DES_DEFEITO,
CASE WHEN tbl_os_defeito_reclamado_constatado.defeito_constatado=tbl_os.defeito_constatado THEN tbl_os.mao_de_obra ELSE 0 END AS mao_de_obra,

CASE WHEN tbl_os.qtde_km_calculada IS NULL THEN 0 ELSE tbl_os.qtde_km_calculada END as valor_do_km,
CASE WHEN tbl_os.certificado_garantia IS NULL THEN '0' ELSE tbl_os.certificado_garantia END as LGI,

tbl_produto.referencia AS COD_ITEM,
tbl_produto.descricao AS DEN_ITEM,
tbl_os.serie AS NUM_SERIE_ITEM,
(SUBSTR(tbl_os.serie, 5, 2) || '/' || SUBSTR(tbl_os.serie, 3, 2) || '/' || SUBSTR(tbl_os.serie, 1, 2)) AS DATA_FABRICACAO,
tbl_os.consumidor_revenda,
tbl_os.consumidor_nome,
tbl_os.revenda_cnpj,
tbl_os.revenda_nome,
UPPER(tbl_posto.nome) AS POSTO_AUTORIZADO,
UPPER(tbl_posto.cidade) AS CIDADE,
UPPER(tbl_posto.estado) AS UF

FROM
tbl_extrato
JOIN tbl_os_extra ON tbl_extrato.extrato=tbl_os_extra.extrato AND tbl_extrato.fabrica=tbl_os_extra.i_fabrica
JOIN tbl_os ON tbl_os_extra.os=tbl_os.os AND tbl_extrato.fabrica=tbl_os.fabrica
JOIN tbl_produto ON tbl_os.produto=tbl_produto.produto
LEFT JOIN tbl_esmaltec_categoria_produto ON tbl_produto.referencia=tbl_esmaltec_categoria_produto.item
JOIN tbl_familia ON tbl_produto.familia=tbl_familia.familia AND tbl_extrato.fabrica=tbl_familia.fabrica
JOIN tbl_posto ON tbl_os.posto=tbl_posto.posto
JOIN tbl_os_defeito_reclamado_constatado ON tbl_os.os=tbl_os_defeito_reclamado_constatado.os
JOIN tbl_defeito_constatado ON tbl_os_defeito_reclamado_constatado.defeito_constatado=tbl_defeito_constatado.defeito_constatado
JOIN tbl_esmaltec_item_servico ON tbl_defeito_constatado.esmaltec_item_servico=tbl_esmaltec_item_servico.esmaltec_item_servico

WHERE
tbl_extrato.fabrica = $login_fabrica
AND tbl_os.posto <> 6359
AND tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final'
	
SQL_QUERY;
////die(nl2br($sql_rel));
/* INI Debug:

p_echo ( "Data inicial $data_inicial, Data final: $data_final");
//if (count($msg_erro) and $login_admin == 1375)
	echo "<h2>$msg_erro</h2>\n<textarea style='width:693px;height:220px'>".
	 preg_replace(array('/^\s+/', '/\s+,/', '/\s+/'),
				  array('', ', ', ' '), $sql_rel).
	 "</textarea>\n";
exit();

/* FIM Debug */
		$res = @pg_query($con, $sql_rel);
		if (is_resource($res)) {
			if ($formato_arquivo == 'xls') {
				define('XLS_FMT', TRUE);
			} else {
				define('XLS_FMT', FALSE);
			}

			if (pg_num_rows($res) > 0) { //Tem resultados...
				$hoje = date('Y-m-d');
				$total= pg_num_rows($res);

				if (XLS_FMT) {
					header('Content-type: application/msexcel');
					header("Content-Disposition: attachment; filename=dados_atualizados_postos_$hoje.xls");
				} else {
					header('Content-type: text/csv');
					header("Content-Disposition: attachment; filename=dados_atualizados_postos_$hoje.csv");
				}
				$row = pg_fetch_assoc($res, 0);
				$campos = array_keys($row);

				foreach($campos as $campo) {
					$campo = str_replace('_', ' ', $campo);
					$xls_header  .= "<th>$campo</th>";
					$csv_campos[] = $campo;
				}
				if (XLS_FMT) {  // Monta o cabeçalho com os nomes dos campos, XLS-fake ou CSV
					echo "<table><thead><tr>$xls_header</tr></thead><tbody>";
				} else {
					echo implode(";", $csv_campos); //CSV
				}
				echo "\n";
				for ($i=0; $i < $total; $i++) {
		        	$row = pg_fetch_assoc($res, $i);
					$xls_linha = "\t\t<tr>\n";
					unset($csv_linha); //array

					foreach($row as $key => $campo) {
						$campo = str_replace("\t", ' ', $campo); //Retira a tabulação
						if ($formato != 'xls') $campo = str_replace("\n", '|', $campo); //Retira a quebra de linha, substinui ela por '|'
						if (stripos($key, 'cpf') !== false or stripos($key, 'cnpj') !== false) {
							$campo = (strlen($campo) == 14) ? preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', "$1.$2.$3/$4-$5", $campo) : // CNPJ
															  preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', "$1.$2.$3-$4", $campo); // CPF, vai que um dia precisa...
						}
						$xls_linha  .= "\t\t\t<td>$campo</td>\n";
						$csv_linha[] = (preg_match('/(\s|\n|\r|;)/', $campo) or //Entre aspas se tiver aglum tipo de espaço ou dígito grande, tipo nº série
										in_array($key, array('referencia','nota_fiscal','serie','peca_referencia'))) ? "\"$campo\"" : $campo;
					}
					echo (XLS_FMT) ? "$linha\t\t</tr>" : implode(";", $csv_linha);
					echo "\n";
				}
				if (XLS_FMT) echo "\t</tbody>\n</table>";
				exit; // FIM do arquivo 'Excel'
			} else {
				$msg_erro = 'Sem dados para o período selecionado.';
			}
		} else { // Não deu erro no banco...
			$msg_erro = 'Erro ao recuperar os dados';
		}
	}
}
/* Include cabeçalho Admin */
	$title = "RELATÓRIO ANALÍTICO DE DEFEITO DE OS EM EXTRATO";
	//Opções: 'cadastro', 'callcenter', 'financeiro', 'gerencia', 'tecnica'
	$layout_menu = 'gerencia';
	include "cabecalho.php";
	extract(array_filter($_POST, 'anti_injection')); // cria as variáveis com os campos do formulário

// Style para relatórios (formulário + tabela de resultados) para a área do admin
include "javascript_calendario.php";
?>
<script type="text/javascript" charset="utf-8">
    $(function(){
        $('#data_inicial').datePicker({startDate:'01/01/2000'})
						  .maskedinput("99/99/9999");
        $('#data_final').datePicker({startDate:'01/01/2000'})
						.maskedinput("99/99/9999");
		$('button[type=reset]').click(function() {
			$('input[name^=data]').val('');
			return false;
		});
    });
 </script>

<style type="text/css">
.menu_top{text-align:center;font:normal bold 10px Verdana,Geneva,Arial,Helvetica,sans-serif;border:1px solid;color:#596d9b;background-color:#d9e2ef;}
.border{border:1px solid #ced7e7;}
.table_line{text-align:center;font:normal normal 10px Verdana,Geneva,Arial,Helvetica,sans-serif;font-family:Verdana,Geneva,Arial,Helvetica,sans-serif;border:0px solid;background-color:white;}
input{font-size:10px;}
.top_list{text-align:center;font:normal bold 10px Verdana,Geneva,Arial,Helvetica,sans-serif;color:#596d9b;background-color:#d9e2ef;}
.line_list{text-align:left;font-family:Verdana,Geneva,Arial,Helvetica,sans-serif;font-size:x-small;font-weight:normal;color:#596d9b;background-color:white;}
caption, .titulo_tabela {background-color:#596d9b;font:bold 14px "Arial";color:white;text-align:center;}
thead, .titulo_coluna {background-color:#596d9b;font:bold 11px "Arial";color:white;text-align:center;}
.formulario{background-color:#D9E2EF;font:normal normal 11px Arial;width:700px;margin:auto;text-align:left;}
.msg, .msg_erro{background-color:#FF0000;font:bold 16px "Arial";color:white;text-align:center;}
.msg{background-color:#51AE51;color:white;}
table.tabela tr td{font-family:verdana;font-size:11px;border-collapse:collapse;border:1px solid #596d9b;}
.texto_avulso{font:14px Arial;color:rgb(89,109,155);background-color:#d9e2ef;text-align:center;width:700px;margin:0 auto;border-collapse:collapse;border:1px solid #596d9b;}
</style>


<table width='700' align='center' border='0' bgcolor='#d9e2ef'>
<? if (strlen ($msg_erro) > 0) { ?>
	<tr class="msg_erro">
		<td> <? echo $msg_erro; ?></td>
	</tr>
<? } ?>

<? if (strlen ($msg) > 0) { ?>
	<tr class="msg">
		<td> <? echo $msg; ?></td>
	</tr>
<? } ?>
</table>

<form method="post" name="frm_os_aprovada" action="<?=$PHP_SELF?>">
	<table class="formulario">
		<caption>PARÂMETROS DA CONSULTA - ANALÍTICO DE DEFEITO</caption>
		<tr><td colspan='4'>&nbsp;</td></tr>
		<tr>
			<td style='width:135px'>&nbsp;</td>
			<td style='width:165px'>
				<label for="data_inicial">&nbsp;Data Inicial *</label>
			</td>
			<td style='width:134px'>&nbsp;</td>
			<td>
				<label for="data_final">&nbsp;Data Final *</label>
			</td>
		</tr>
		<tr>
			<td style='width:135px'>&nbsp</td>
			<td style='width:165px'>
				<input id="data_inicial" maxlength="10" name="data_inicial" size='12' type="text" class="frm" value="<?=$data_inicial?>">
			</td>
			<td style='width:134px'>&nbsp;</td>
			<td>
				<input id="data_final" maxlength="10" name="data_final" size='12' type="text" class="frm" value="<?=$data_final?>">
			</td>
		</tr>
		<tr><td colspan='4'>&nbsp;</td></tr>
		<tr style='text-align:center!important'>
			<td colspan="4">
				<input name="btn_acao" type="hidden" value='t' />
				<button value="" type='submit'>Filtrar</button>
				&nbsp;&nbsp;&nbsp;
				<button value="" type='reset'>Limpar</button>
			</td>
		</tr>
		<tr><td colspan='4'>&nbsp;</td></tr>
	</table>
</form>

<? include 'rodape.php'; ?>
