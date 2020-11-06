<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center";
include 'autentica_admin.php';

include 'funcoes.php';

include '../helpdesk/mlg_funciones.php'; //Admin

/*  Parâmetros  */
if (count($_POST)) {
	define('CRLF', "\r\n");
	$param = array_filter($_POST, 'anti_injection');
	extract($param); // Extrai os parâmetros fornecidos de forma 'segura'

	if ($btn_acao != 'Consulta') $msg_erro = 'Parâmetros da consulta incompletos.';
	/* Confere as datas */
	if (!$data_inicial and !$data_final) {
		// Formuário sem datas
		$data_inicial = date('Y-m-d', strtotime('-1 week'));
		$data_final   = date('Y-m-d');

	} else if (!$data_final) {
		// Só a data incial
		list($di, $mi, $yi) = explode("/", $data_inicial);
		if(!checkdate($mi,$di,$yi)) $msg_erro   = "Data Inválida";
		$data_final   = date('Y-m-d');
		$data_inicial = "$yi-$mi-$di";

	} else if (!$data_inicial) {
		// Só a data final
		list($df, $mf, $yf) = explode("/", $data_final);
		if(!checkdate($mf,$df,$yf)) $msg_erro = 'Data Inválida';
		$data_final   = "$yf-$mf-$df";
		$data_inicial = date('Y-m-d', strtotime('-1 week', date_to_timestamp($data_final)));

	} else {
	    if(strlen($msg_erro)==0){
	        list($di, $mi, $yi) = explode("/", $data_inicial);
	        list($df, $mf, $yf) = explode("/", $data_final);

	        if(!checkdate($mi,$di,$yi)) $msg_erro = "Data Inválida";
	        if(!checkdate($mf,$df,$yf)) $msg_erro = "Data Inválida";

		if (date_to_timestamp($data_inicial) < strtotime('-6 months', date_to_timestamp($data_final))) {
			$msg_erro  = 'Data Inválida!';
			$gerar_csv = false;
		}

		    if(strlen($msg_erro)==0){
		        $data_inicial = "$yi-$mi-$di";
		        $data_final = "$yf-$mf-$df";
		    }
		}
	}
	if(strlen($msg_erro)==0){
		$aux_data_inicial = "$yi-$mi-$di";
		$aux_data_final = "$yf-$mf-$df";
		if(strtotime($aux_data_final) < strtotime($aux_data_inicial) or
		   strtotime($data_final) > strtotime('today')) {
			$msg_erro  = "Data Inválida.";
			$gerar_csv = false;
		} else {
			$gerar_csv = true;
		}
	}

} // Fim Parâmetros

$colunas = array(
	'Protocolo',
	'Atendente',
	'Data de Abertura',
	'Nome do Cliente',
	'CPF',
	'RG',
	'E-mail',
	'Telefone',
	'CEP',
	'Endereço',
	'Numero',
	'Complemento',
	'Bairro',
	'Cidade',
	'Estado',
	'Hora Ligação',
	'Origem',
	'Tipo',
	'Receber Informação',
	'Referência do Produto',
	'Descrição do Produto',
	'Linha do Produto',
	'Série',
	'Nota Fiscal',
	'Data NF',
	'Motivo Ligação',
	'Nome do Posto',
	'Código do Posto',
	'Cidade Contato',
	'Estado Contato',
	'Data de Abertura OS',
	'Abre OS',
	'Tipo de Atendimento',
	'CNPJ Revenda',
	'Nome Revenda',
	'Reclamação',
	'Situação',
	'Data Providência'
);


if ($gerar_csv) {
	$sql = "SET dateStyle TO postgres, dmy;
			SELECT /* Chamado */ 
				tbl_hd_chamado_extra.hd_chamado as atendimento, 
				tbl_admin.login as usuario_abriu, 
				TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YYYY HH24:MI') AS data_abertura, 
				/* Consumidor/Cliente */
				tbl_hd_chamado_extra.nome, tbl_hd_chamado_extra.cpf, tbl_hd_chamado_extra.rg,
				tbl_hd_chamado_extra.email, tbl_hd_chamado_extra.fone, tbl_hd_chamado_extra.cep,
				tbl_hd_chamado_extra.endereco, tbl_hd_chamado_extra.numero, tbl_hd_chamado_extra.complemento,
				tbl_hd_chamado_extra.bairro, tbl_cidade.nome as cidade_consumidor, tbl_cidade.estado,
				/* Atendimento */
				tbl_hd_chamado_extra.hora_ligacao, tbl_hd_chamado_extra.origem,
				CASE tbl_hd_chamado_extra.consumidor_revenda
				     WHEN 'C' THEN 'Consumidor'
				     WHEN 'R' THEN 'Revenda'
				END AS tipo,
				CASE tbl_hd_chamado_extra.receber_info_fabrica
				     WHEN TRUE THEN 'Aceita'
				     ELSE 'Não aceita'
				END AS receber_info,
				/* Produto */
				CASE WHEN tbl_hd_chamado_extra.produto IS NOT NULL
				     THEN (SELECT ARRAY_TO_STRING(ARRAY[referencia,descricao,tbl_linha.nome], '·')
					     FROM tbl_produto JOIN tbl_linha USING(linha)
					    WHERE tbl_produto.produto = tbl_hd_chamado_extra.produto)
				     ELSE ' · · '
				 END AS info_produto,
				tbl_hd_chamado_extra.serie, tbl_hd_chamado_extra.nota_fiscal, tbl_hd_chamado_extra.data_nf,
				tbl_hd_chamado_extra.hd_motivo_ligacao,
				/* Posto */
				CASE WHEN tbl_hd_chamado_extra.posto IS NOT NULL THEN
					  (SELECT ARRAY_TO_STRING(ARRAY[nome,codigo_posto,contato_cidade,contato_estado], '·')
					     FROM tbl_posto JOIN tbl_posto_fabrica USING(posto)
					    WHERE tbl_posto_fabrica.fabrica = tbl_hd_chamado.fabrica
					      AND tbl_posto.posto = tbl_hd_chamado_extra.posto)
				     ELSE ' · · · '
				END AS info_posto,
				TO_CHAR(tbl_hd_chamado_extra.data_abertura_os,'DD/MM/YYYY') AS data_abertura_os,
				tbl_hd_chamado_extra.abre_os,
				tbl_hd_chamado.categoria AS tipo_atendimento,
				/* Defeito reclamado */
				CASE tbl_hd_chamado.categoria
				     WHEN 'onde_comprar'  THEN ARRAY_TO_STRING(ARRAY[tbl_revenda.cnpj, tbl_revenda.nome, reclamado], '·')
				     ELSE ARRAY_TO_STRING(ARRAY[' ',' ', reclamado], '·')
				END AS reclamacao,
				tbl_hd_situacao.descricao AS situacao,
				TO_CHAR(tbl_hd_chamado.data_providencia,'DD/MM/YYYY') AS data_providencia 
			FROM      tbl_hd_chamado 
			JOIN      tbl_hd_chamado_extra USING(hd_chamado)
			LEFT JOIN tbl_hd_situacao USING(hd_situacao)
			LEFT JOIN tbl_cidade USING(cidade)
			JOIN      tbl_admin USING(admin)
			LEFT JOIN tbl_revenda USING(revenda)
			WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
			  AND tbl_hd_chamado.data BETWEEN '$data_inicial' AND '$data_final'::date + INTERVAL '1 day - 1 sec'
			ORDER BY data";
	$res = @pg_query($con, $sql);

	$sql_i = 'SELECT TO_CHAR(tbl_hd_chamado_item.data,\'DD/MM/YYYY HH24:MI\') AS data_iteracao,
					 /*CASE WHEN interno IS TRUE THEN \'interno\' ELSE NULL END AS interno,*/
					 status_item,
					 tbl_admin.login AS atendente,
					 comentario
				FROM tbl_hd_chamado_item
				JOIN tbl_admin USING(admin)
			   WHERE hd_chamado = $1
			   ORDER BY data';
	$res_i = pg_prepare($con, 'hd_itens', $sql_i); // Prepara a query no banco de dados

	if (is_resource($res) and is_resource($res_i)) {
		if (pg_num_rows($res)) {
			/* Começa a geração do arquivo */
			$arquivo = 'atendimento_callcenter_' . date('Ymd_') . '.csv';
			header('Content-type: text/csv');
			header("Content-Disposition: attachment; filename=$arquivo");
			
			while ($hd_row = pg_fetch_assoc($res)) {
				$hd_chamado = $hd_row['atendimento'];
				$hd_row['info_produto'] = str_replace('·', ';', $hd_row['info_produto']);
				$hd_row['info_posto']   = str_replace('·', ';', $hd_row['info_posto']);
				$hd_row['reclamacao'] = preg_replace(
													array('/;/', '/\r\n|\n|\r|\\r/', '/\s+/', '/·/'),
													array(',', '|', ' ', ';'),
													$hd_row['reclamacao']);
				$hdi_res    = pg_execute($con, 'hd_itens', array($hd_chamado)); // Executa a query com o hd_chamado como parâmetro
				if (is_resource($hdi_res) and @pg_num_rows($hdi_res)) {
					$hd_itens = pg_fetch_all($hdi_res);
					
					//Cabeçalho do atendimento
					echo implode(';', $colunas). CRLF;
					// Começa o atendimento
					echo implode(';', $hd_row). CRLF; // Linha com os dados do atendimento

					$cont = 1;
					foreach($hd_itens as $interacao) {
						$interacao['comentario'] = preg_replace(
													array('/;/', '/\r\n|\n|\r|\\r/', '/\s+/'),
													array(',', '|', ' '),
													$interacao['comentario']);
						echo filter_var(' ;#'.$cont.';'.implode(';', $interacao) . CRLF, FILTER_SANITIZE_STRING);
						//echo ' ;#'.$cont.';'.implode(';', $interacao) . CRLF;
						$cont++;
					}
				} // Fim interações do atendimento
			} // Fim de cada atendimento
		} else {
			$msg = 'Não há resultados para este período.';
			$gerar_csv = false;
		}

	} else {
		$msg_erro  = 'Erro ao recuperar as informações para o relatório.<div style="display:none"><code>'.$sql.'</code>'.pg_last_error($con).'</div>';
		$gerar_csv = false;
if ($xmg_err = pg_last_error($con) and preg_match('/_mlg|_test|_\d{5,7}/', $PHP_SELF))
	echo "<h2>$xmg_err</h2>\n<textarea style='width:693px;height:220px'>".
		 preg_replace(array('/^\s+/', '/\s+,/', '/\s+/'),
			  array('', ', ', ' '), $sql).
	 "</textarea>\n";

	}
}

if (!$gerar_csv) {
/* Include cabeçalho Posto */
	$title = 'Telecontrol - Título da tela';
/* Include cabeçalho Admin */
	$title = "RELATÓRIO - HISTÓRICO DE CALL-CENTER";
	//Opções: 'cadastro', 'callcenter', 'financeiro', 'gerencia', 'tecnica'
	$layout_menu = 'callcenter';

	include "cabecalho.php";
	// Style para relatórios (formulário + tabela de resultados) para a área do admin
	?>
<style type="text/css">

.menu_top {
	text-align: center;
	font: normal bold 10px Verdana, Geneva, Arial, Helvetica, sans-serif;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef;
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: center;
	font: normal normal 10px Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	border: 0px solid;
	background-color: white;
}

input {
	font-size: 10px;
}

.top_list {
	text-align: center;
	font: normal bold 10px Verdana, Geneva, Arial, Helvetica, sans-serif;
	color:#596d9b;
	background-color: #d9e2ef;
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: white;
}

caption,.titulo_tabela {
	background-color:#596d9b;
	font: bold 14px "Arial";
	color: white;
	text-align:center;
}

thead,.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color: white;
	text-align:center;
}

.formulario {
	background-color:#D9E2EF;
	font: normal normal 11px Arial;
	margin: 0 auto;
	width: 700px;
	text-align: left;
}

.msg,.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color: white;
	text-align:center;
}

.msg{
	background-color:#51AE51;
	color: white;
}

table.tabela tr td {
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
.texto_avulso {
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width: 700px;
    margin: 1px auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
#msg {
	position:fixed;
	display:block;
	top: 48%;
	left:48%;
	padding: 0.5ex 1em;
	pie-background:rgba(32,32,128, 0.75);
	background-color:rgba(32,32,128, 0.75);
	color: white;
	border: 3px solid rgb(32,32,128);
	border-radius: 10px;
	-moz-border-radius: 10px;
	box-shadow: 3px 3px 4px black;
	-moz-box-shadow: 3px 3px 4px black [inset];
	-webkit-box-shadow: 3px 3px 4px black;
	behavior: url(/mlg/js/PIE.php);
}
</style>
<link rel="stylesheet" type="text/css" href="js/datePicker.v1.css" title="default" media="screen" />

<script type="text/javascript" src="/js/jquery.min.js"></script>
<script type="text/javascript" src="js/datePicker.v1.js"></script>
<script type="text/javascript" src="/js/jquery.maskedinput-1.2.2.min.js"></script>

<script type="text/javascript">
	$().ready(function(){
		var lock = $('#btn_acao');
		$.datePicker.setLanguageStrings(
			['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'],
			['Janeiro', 'Fevereiro', 'Março', 'Abril', 'maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
			{p:'Anterior', n:'Próximo', c:'Fechar', b:'Abrir Calendário'}
		);
	    $("#data_inicial").datePicker({startDate: '01/01/2000', endDate: '<?=date('d/m/Y')?>'})
						  .mask("99/99/9999");
	    $("#data_final").datePicker({startDate: '01/01/2000', endDate: '<?=date('d/m/Y')?>'})
						  .mask("99/99/9999");
		$('#data_inicial,#data_final').change(function() {
			if (lock.val() != '') lock.val('');
			if ($('.msg_erro>td').text().indexOf('Data') > -1) $('.msg_erro>td').text('').parent().removeClass('msg_erro');
		});

		$('button:submit').click(function () {
			if (lock.val() != '') {
				alert("A consulta já foi enviada ao servidor. Aguarde uns instantes.");
				return false; // Para não enviar o formulário
			}
			lock.val('Consulta');
			$('form').submit();
		});
		$('#reset_frm').click(function () {
			$('body').prepend('<div id="msg" />')
					 .find('div#msg')
					 	.fadeIn('fast').html('<p>Limpando o formulário...</p>')
						.delay(400)
						.fadeOut('fast', function (){$('#msg').remove()});
			$('input[name^=data]').val('');
			lock.val('').removeAttr('disabled');
        });
		$('.texto_avulso').dblclick(function(){$(this).slideUp('fast')});
	});
</script>

<div class="texto_avulso" colspan='4'>
		A <b>Data Inicial</b>, se não fornecida, será considerada 1 semana <i>antes</i> da <b>Data Final</b>.<br>
		Para a <b>Data Final</b>, se não fornecida, será considerada a data de hoje (<?=date('d-m-Y')?>).<br>
		Para ambas as datas, será considerada <b>sempre</b> a data de abertura do atendimento.
</div>

<?if ($msg or $msg_erro) { ?>
<table width='700' align='center' border='0' bgcolor='#d9e2ef'>
<?	if (strlen ($msg_erro) > 0) { ?>
	<tr class="msg_erro">
		<td> <? echo $msg_erro; ?></td>
	</tr>
<?	}

	if (strlen ($msg) > 0) { ?>
	<tr class="msg">
		<td> <? echo $msg; ?></td>
	</tr>
<?	}?>
</table>
<?}
extract($_POST); //Recupera as informações do formulário como foi enviado
?>

<form action="<?=$PHP_SELF?>" name="frm_rel_callcenter" method="post">
	<table class="formulario">
		<caption>Parâmetros de Pesquisa</caption>
		<tr>
			<td colspan="4">&nbsp;</td>
		</tr>
		<tr class='table_top'>
			<td width="33%">&nbsp;</td>
			<td title='Se não fornecida, será uma semana antes da data final' width="140px">
				<label for="data_inicial">Data Inicial</label><br />
				<input type="text" name="data_inicial" size="12" id="data_inicial" maxlength="10" value="<?=$data_inicial?>" class="frm" />
			</td>
			<td title='Se não fornecida, será considerada a data de hoje (<?=date('d-m-Y')?>)'>
				<label for="data_final">Data Final</label><br />
				<input type="text" name="data_final" size="12" id="data_final"	maxlength="10" value="<?=$data_final?>"   class="frm" />
			</td>
		</tr>		
		<tr><td colspan='4'>&nbsp;</td></tr>
		<tr>
			<td colspan="4" align='center'>
				<input type="hidden" name="btn_acao" id="btn_acao" value='' />
				<button type='submit'>Download CSV</button>
				&nbsp;
				<button type='button' id='reset_frm'>Limpar</button>
			</td>
		</tr>
		<tr><td colspan='4'>&nbsp;</td></tr>
	</table>
</form>
<?include 'rodape.php'; 
}?>
