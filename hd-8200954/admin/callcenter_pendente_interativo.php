<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$title = "ATENDIMENTO CALL-CENTER";
$layout_menu = 'callcenter';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';


$array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
  "AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
  "ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
  "MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
  "PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
  "RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
  "RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
  "SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");
/*
if(strlen($_POST['gerar_xls'])>0) $gerar_xls = $_POST['gerar_xls'];
else                              $gerar_xls = $_GET['gerar_xls'];
*/
$gerar_xls = 't';

if(strlen($_POST['bi_latina'])>0){
	$bi_latina = $_POST['bi_latina'];
 }

/*MARCAR O ADMIN SUPERVISOR DO CALLCENTER*/
$sql = "SELECT callcenter_supervisor from tbl_admin where fabrica = $login_fabrica and admin = $login_admin";
$res = pg_query($con,$sql);
if(pg_num_rows($res)>0){
	$callcenter_supervisor = pg_result($res,0,0);
}
if ($callcenter_supervisor=="t") {
	$supervisor="true";
}

$maisAtendimento = $_GET['maisAtendimento'];


if(strlen($_POST['btn_acao']) > 0) {

	$cond_classificacao = "";
	if ($classificacaoHD) {
		$hd_classificacao = $_POST['hd_classificacao'];
		$cond_classificacao = " AND tbl_hd_chamado.hd_classificacao = {$hd_classificacao}";
	}


	$data_inicial = $_POST['data_inicial'];
	$data_final   = $_POST['data_final'];

	$data_inicial = $_REQUEST["data_inicial"];
	$data_final   = $_REQUEST["data_final"];

	$xjornada 	  = $_POST['jornada'];

	$providencia3 	= $_POST['providencia_nivel_3'];
	$motivo_contato = $_POST['motivo_contato'];

	if (strlen($data_final) > 0 AND strlen($data_inicial) > 0 && ($_POST["status"] == 'Resolvido' || $_POST["status"] == 'Cancelado')) {
		$xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
		$xdata_inicial = str_replace("'","",$xdata_inicial);
		$xdata_inicial = "$xdata_inicial 00:00:00";

		$xdata_final =  fnc_formata_data_pg(trim($data_final));
		$xdata_final = str_replace("'","",$xdata_final);
		$xdata_final   =  "$xdata_final 23:59:59";


		$sqlX = "SELECT '$xdata_inicial'::date + interval '6 months' > '$xdata_final'";
		$resX = pg_query($con,$sqlX);
		$periodo_6meses = pg_fetch_result($resX,0,0);
		if($periodo_6meses == 'f'){
			$msg_erro = "Intervalo máximo de datas para o status ".$_POST['status']." é de 6 meses";
		}
	}

	$data_inicial_retorno = $_POST['data_inicial_retorno'];
	$data_final_retorno   = $_POST['data_final_retorno'];

	$tipo         = $_POST['tipo'];
	$xtipo        = $_POST['tipo'];
	$atendimento  = $_POST['atendimento'];
	$os           = $_POST['os'];
	$status       = $_POST['status'];
	$atendente    = $_POST['atendente'];
	$uf           = $_POST['estado'];

	$codigo_posto = $_POST['codigo_posto'];
	$nome_posto   = $_POST['nome_posto'];

	$email_callcenter = $_POST['email_callcenter'];

	if (!empty($email_callcenter)) {
		$cond_email_callcenter = " AND tbl_hd_chamado_extra.email = '{$email_callcenter}'";
	}

	if ($login_fabrica == 52) {
		$hd_classificacao = $_POST["hd_classificacao"];

		if (strlen($hd_classificacao) > 0) {
			$cond_hd_classificacao = " AND tbl_hd_classificacao.hd_classificacao = $hd_classificacao ";
		} else {
			$cond_hd_classificacao = "";
		}
	}

	if(strlen($data_inicial)==0 && strlen($data_final)==0 && strlen($data_inicial_retorno)==0 AND strlen($tipo)==0 && strlen($atendimento)==0 AND strlen($os)==0 && strlen($status)==0 AND strlen($atendente)==0 && strlen($uf)==0 AND strlen($codigo_posto)==0 AND strlen($nome_posto)==0){
		if (!isset($hd_classificacao)) {
			$msg_erro = "Preencha algum parâmetro para pesquisa";
		}
	}

	$origem = $_POST['origem'];
	if(strlen($origem) > 0){
		$cond_origem = "AND tbl_hd_chamado_extra.origem = '$origem' ";
	}else{
		$cond_origem = "";
	}

	if (in_array($login_fabrica, [169, 170])) {
    	if (!empty($providencia3)) {
    		$condProv3 = "AND tbl_hd_chamado_extra.hd_providencia = {$providencia3}";
    	}

    	if (!empty($motivo_contato)) {
    		$condMotivoContato = "AND tbl_hd_chamado_extra.motivo_contato = {$motivo_contato}";
    	}
    }

	if(strlen($atendimento)==0 AND strlen($os)==0){

		if (strlen($data_inicial)>0  AND strlen($data_final) == 0) {
			$msg_erro = "Data Final Inválida.";
		}

		if (strlen($data_inicial) == 0  AND strlen($data_final) > 0) {
			$msg_erro = "Data Inicial Inválida.";
		}

		if (strlen($data_inicial)>0  AND strlen($data_final)>0 AND strlen($msg_erro) == 0) {
			if(strlen($data_inicial)>0){
				list($di, $mi, $yi) = explode("/", $data_inicial);
				if(!checkdate($mi,$di,$yi))
					$msg_erro = "Data Inválida.";
			}
			if(strlen($msg_erro)==0){
				list($df, $mf, $yf) = explode("/", $data_final);
				if(!checkdate($mf,$df,$yf))
					$msg_erro = "Data Inválida.";
			}
			if(strlen($msg_erro)==0){
				$xdata_inicial = "$yi-$mi-$di";
				$xdata_final = "$yf-$mf-$df";
			}
			if(strlen($msg_erro)==0){
				if(strtotime($xdata_final) < strtotime($xdata_inicial)){
					$msg_erro = "Data Inválida.";
				}
			}
		}

		if (strlen($data_inicial_retorno)>0  AND strlen($data_final_retorno) == 0) {
			$msg_erro = "Data de Retorno Final Inválida.";
		}

		if (strlen($data_inicial_retorno) == 0  AND strlen($data_final_retorno) > 0) {
			$msg_erro = "Data de Retorno Inicial Inválida.";
		}

		if (strlen($data_inicial_retorno)>0  AND strlen($data_final_retorno)>0 AND strlen($msg_erro) == 0) {
			//retorno
			if(strlen($data_inicial_retorno)>0){
				list($dir, $mir, $yir) = explode("/", $data_inicial_retorno);
				if(!checkdate($mir,$dir,$yir))
					$msg_erro = "Data Retorno Inválida";
			}
			//retorno
			if(strlen($msg_erro)==0){
				list($dfr, $mfr, $yfr) = explode("/", $data_final_retorno);
				if(!checkdate($mfr,$dfr,$yfr))
					$msg_erro = "Data Retorno Inválida";
			}
			//retorno
			if(strlen($msg_erro)==0){
				$xdata_inicial_retorno = "$yir-$mir-$dir";
				$xdata_final_retorno = "$yfr-$mfr-$dfr";
			}
			//retorno
			if(strlen($msg_erro)==0){
				if(strtotime($xdata_final_retorno) < strtotime($xdata_inicial_retorno)){
					$msg_erro = "Data Retorno Inválida.";
				}
			}
		}
	}
}


if ($login_fabrica == 30 && strlen($codigo_posto) > 0 && strlen($nome_posto) > 0) {

	$sql = "SELECT tbl_posto.posto
                    FROM tbl_posto
                    JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                    WHERE tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto_fabrica.codigo_posto='{$codigo_posto}'";
    $res = pg_query($con,$sql);
    if (pg_num_rows ($res) > 0) {
        $xposto = trim(pg_fetch_result($res, 0, 'posto'));
		$cond_ext_posto = " AND tbl_hd_chamado_extra.posto={$xposto}";
	} else {
		$msg_erro = "Posto autorizado não encontrado.";
	}
}

if (strlen($xdata_inicial) > 0 and strlen($xdata_final) > 0) {
	$cond_2 = " AND tbl_hd_chamado.data between '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59' ";
}

if(empty($cond_2) and empty($atendente)) {
	$cond_2 = " AND tbl_hd_chamado.data between current_timestamp - interval '24 months' and current_timestamp ";
}
//retorno
if(strlen($xdata_inicial_retorno) > 0 and strlen($xdata_final_retorno) > 0) {
	$cond_ret = " AND tbl_hd_chamado.data_providencia between '$xdata_inicial_retorno 00:00:00' AND '$xdata_final_retorno 23:59:59' ";
}
if(!empty($tipo) > 0) {
	if ($login_fabrica == 50) {
		$tipo = implode("','", $tipo);
	}
	$tipo = "'".$tipo."'";
	
	/*HD - 4382764*/
	if (in_array($login_fabrica, [174,189])) {
		$cond_3 = " AND tbl_hd_chamado_extra.consumidor_revenda = $tipo ";
	} else {
		$cond_3 = " AND tbl_hd_chamado.categoria in ($tipo) ";
	}
}

if(strlen($atendimento) > 0) {
	$cond_6 = " AND tbl_hd_chamado.hd_chamado = '$atendimento' ";
}

if(strlen($os) > 0) {
	if($os <= 2147483647)
		$cond_7 = " AND tbl_hd_chamado_extra.os = '$os' ";
	else
		$msg_erro = 'Número da OS maior que o permitido';
}

if(strlen($status) > 0) {
	$status = strtoupper($status);
	$cond_8 = " AND upper(tbl_hd_chamado.status) = '$status' ";
}else{
	if(strlen($atendimento)==0 and strlen($os)==0) {
		if ($login_fabrica == 35){
			$cond_8 = " AND tbl_hd_chamado.status = 'Aberto' ";
		}else{
			$cond_8 = " AND upper(tbl_hd_chamado.status) <> upper('Resolvido') and tbl_hd_chamado.status <> 'Cancelado' AND tbl_hd_chamado.status <> 'PROTOCOLO DE INFORMACAO' ";
			if ($login_fabrica == 15 ){
				$cond_8 = "";
			}
		}
	}
}

if(strlen($atendente) > 0) {
	$cond_10 = " AND tbl_hd_chamado.atendente = '$atendente' ";
	if ($login_fabrica == 24 || $login_fabrica == 85) {
		$cond_10 = " AND (tbl_hd_chamado.atendente = '$atendente' or tbl_hd_chamado.sequencia_atendimento = $atendente)";
	}
}

if(strlen($uf) > 0){
	$cond_11 = " AND tbl_cidade.estado = '$uf' ";
}
if(strlen($_POST['bi_latina']) > 0) {
	if ((strlen($xdata_inicial)>0) AND (strlen($xdata_final)>0))  {
		if ((strtotime($xdata_inicial)) < (strtotime($xdata_final."- 6 MONTH"))){
			$msg_erro .= " Data pesquisa maior que 6 meses ";
		}
	}
	if (strlen($msg_erro) == 0) {

		$sql = "SELECT  tbl_hd_chamado.hd_chamado ,
						tbl_admin.nome_completo 							as nome_admin ,
						tbl_hd_chamado_extra.nome 							as nome_consumidor ,
						tbl_hd_chamado_extra.endereco 						as endereco_consumidor ,
						tbl_hd_chamado_extra.complemento 					as complemento_consumidor ,
						tbl_hd_chamado_extra.bairro 						as bairro_consumidor ,
						tbl_hd_chamado_extra.cep 							as cep_consumidor ,
						tbl_hd_chamado_extra.fone 							as fone1_consumidor ,
						tbl_hd_chamado_extra.fone2 							as fone_comercial ,
						tbl_hd_chamado_extra.celular 						as celular_consumidor ,
						tbl_hd_chamado_extra.numero	 						as numero_consumidor ,
						tbl_hd_chamado_extra.email 							as email_consumidor ,
						tbl_hd_chamado_extra.cpf 							as cpf_consumidor ,
						tbl_hd_chamado_extra.rg 							as rg_consumidor ,
						tbl_hd_chamado_extra.origem 						as origem_consumidor ,
						tbl_hd_chamado_extra.consumidor_revenda 			as tipo_consumidor ,
						tbl_hd_chamado_extra.hora_ligacao	 				as hora_ligacao ,
						tbl_cidade.nome 									as cidade ,
						tbl_cidade.estado	 								as estado ,
						tbl_hd_chamado_extra.receber_info_fabrica 			as informacoes_fabrica ,
						tbl_produto.descricao 								as produto_descricao,
						tbl_produto.referencia 								as produto_referencia ,
						tbl_produto.voltagem 								as produto_voltagem	,
						tbl_hd_chamado_extra.nota_fiscal 					as produto_nf	,
						to_char(tbl_hd_chamado_extra.data_nf, 'DD/MM/YYYY')	as produto_data_nf	,
						tbl_hd_chamado_extra.serie	 						as produto_serie	,
						tbl_posto_fabrica.nome_fantasia	 					as nome_fantasia ,
						tbl_posto_fabrica.codigo_posto	 					as cnpj_posto ,
						tbl_posto_fabrica.contato_fone_comercial 			as telefone_posto ,
						tbl_posto_fabrica.contato_email 					as email_posto ,
						tbl_os.sua_os 											as os ,
						tbl_hd_chamado.categoria 							as aba_callcenter ,					tbl_hd_chamado_extra.abre_os 						as pre_os ,
						tbl_hd_chamado_extra.defeito_reclamado_descricao	as aba_reclamacao ,
						tbl_hd_chamado_extra.defeito_reclamado 				as defeito_reclamado ,
						tbl_hd_chamado_extra.reclamado 						as descricao ,
						tbl_revenda.nome 								as nome_revenda ,
						tbl_revenda.cnpj 								as cnpj_revenda ,
						to_char(tbl_hd_chamado.data, 'DD/MM/YYYY') 			as data_abertura ,
						tbl_hd_chamado.status 								as status ,
						admin_atendente.nome_completo 						as atendente ,
						tbl_hd_chamado_extra.array_campos_adicionais 				as array_campos_adicionais ,
						(SELECT max(to_char(ci.data, 'DD/MM/YYYY')) FROM tbl_hd_chamado_item ci WHERE UPPER(ci.status_item) = 'RESOLVIDO' and ci.hd_chamado = tbl_hd_chamado.hd_chamado) as data_finalizacao
						FROM tbl_hd_chamado
						JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
						LEFT JOIN tbl_os on tbl_hd_chamado_extra.os = tbl_os.os
						LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_extra.produto and tbl_produto.fabrica_i = 15
						LEFT JOIN tbl_revenda on tbl_revenda.revenda = tbl_hd_chamado_extra.revenda
						JOIN tbl_admin on tbl_admin.admin = tbl_hd_chamado.admin AND tbl_admin.fabrica = 15
						LEFT JOIN tbl_admin AS admin_atendente ON admin_atendente.admin = tbl_hd_chamado.atendente AND admin_atendente.fabrica = 15
						LEFT JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
						LEFT JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = 15
						LEFT JOIN tbl_hd_situacao ON tbl_hd_situacao.hd_situacao = tbl_hd_chamado_extra.hd_situacao
						LEFT JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
						WHERE tbl_hd_chamado.fabrica_responsavel = 15
						$cond_2
						$cond_3
						$cond_6
						$cond_7
						$cond_8
						$cond_10
						$cond_11
						$cond_ret
						$cond_classificacao
						ORDER BY tbl_hd_chamado.hd_chamado DESC
					";

				$resSubmit = pg_query($con,$sql);
		}
}


/*MARCAR O ADMIN SUPERVISOR DO CALLCENTER*/
/* se a fabrica utiliza o callcenter New colocar aqui */
if($login_fabrica == 3){
	$programaphp = "callcenter_interativo_new.php";
}else{
	$programaphp = "callcenter_interativo.php";
}
if(strlen($login_cliente_admin)>0){
	if ($login_fabrica <> 7) {
		$programaphp = "../admin_cliente/pre_os_cadastro_sac_esmaltec.php";	# code...
	} else {
		$programaphp = "../admin_cliente/pre_os_cadastro_sac_filizola.php";	# code...
	}

}
include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
	"multiselect",
        "alphanumeric",
	"select2",
);

include("plugin_loader.php");

?>

<script type="text/javascript" charset="utf-8">

$(function(){
		$("select[name=atendente], #providencia").select2();

		/*if (document.body.scrollWidth > $(window).width()) {
			$('.container').attr('style', '-moz-transform: scale(0.80);');

			$('#content_pendente > td').each(function() {
				$(this).attr('style', 'font-size: 11pt;');
			});
		}*/

		var span = 0;
		$('#content_pendente .titulo_coluna th').each(function(){
			span += 1;
		});

		$('#content_pendente .titulo_tabela th').attr("colspan", span);

		var span = 0;
		$('#content_status .titulo_coluna th').each(function(){
			span += 1;
		});

		$('#content_status .titulo_tabela th').attr("colspan", span);

		$("#data_inicial").datepicker().mask("99/99/9999");
		$("#data_final").datepicker().mask("99/99/9999");

		$("#data_inicial_retorno").datepicker().mask("99/99/9999");
		$("#data_final_retorno").datepicker().mask("99/99/9999");

		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$("#atendimento").numeric();
		$("#os").numeric();

// 		$("#content").tablesorter({
//     		headers: { 4 : { sorter: 'shortDate'},
//     				   5 : { sorter: 'shortDate'},
// 					   6 : { sorter: 'shortDate'},
// 						7: { sorter: 'digit'},
// 						8: { sorter: 'digit'}
// 			}
//
// 		});
		<?php if ($login_fabrica == 50) { ?>
			$("select[id=tipo]").multipleSelect();
		<?php } ?>

	});

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#nome_posto").val(retorno.nome);
    }
</script>
<?php if (strlen($msg_erro) > 0) { ?>
	<div id='erro' class='Erro alert alert-danger'>
		<h4><?= $msg_erro; ?></h4>
	</div>
<? } ?>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form class='form-search form-inline tc_formulario' name="frm_relatorio" method="POST" action="<?= $PHP_SELF ?>">
	<div id='carregando' style='position:absolute;visibility:hidden;opacity:.90;' class='Carregando'></div>
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<br />
	<?php if (in_array($login_fabrica, array(30))) { ?>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span4">
				<div class="control-group">
					<label class="control-label">Código do Posto</label>
					<div class="controls controls-row">
						<div class="input-append">
							<input type="text" value="<?php echo $codigo_posto;?>" name="codigo_posto" id="codigo_posto" size="8" class='frm'>
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class="span4">
				<div class="control-group">
					<label class="control-label" for="">Nome do Posto</label>
					<div class="controls controls-row">
						<div class="input-append">
							<input type="text" value="<?php echo $nome_posto;?>" name="nome_posto" id="nome_posto" size="15"  class='frm'>
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class="span2"></div>
		</div>
	<?php } ?>
	<div class="row-fluid">
		<div class="span2"></div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='data_inicial'>Data Inicial</label>
				<div class='controls controls-row'>
					<input class="span6" type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" >
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='data_final'>Data Final</label>
				<div class='controls controls-row'>
					<input class="span6" type="text" name="data_final" id="data_final" size="12" maxlength="10" value="<? if (strlen($data_final) > 0) echo $data_final;  ?>" >
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>
	<?php if (in_array($login_fabrica, array(30,115,116))) { ?>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label'>Data de Retorno Inicial</label>
						<div class='controls controls-row'>
							<input type="text" name="data_inicial_retorno" id="data_inicial_retorno" size="12" maxlength="10" class="span6" value="<? if (strlen($data_inicial_retorno) > 0) echo $data_inicial_retorno; ?>" >
						</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label'>Data de Retorno Final</label>
						<div class='controls controls-row'>
							<input type="text" name="data_final_retorno" id="data_final_retorno" size="12" maxlength="10" class="span6" value="<? if (strlen($data_final_retorno) > 0) echo $data_final_retorno;  ?>" >
						</div>
				</div>
			</div>
			<div class="span2"></div>
		</div>
	<?php } ?>
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<label class="control-label">Nº Atendimento</label>
			<div class='controls controls-row'>
				<input type="text" name="atendimento" id="atendimento" size="12" maxlength="10" class='frm' value="<? if (strlen($atendimento) > 0) echo $atendimento; ?>" >
			</div>
		</div>
		<div class="span4">
			<?php if ((strlen($supervisor) > 0 && $login_fabrica == 24) || $login_fabrica <> 24) { ?>
				<label class="control-label">Atendente</label>
				<div class='controls controls-row'>
					<?php if($login_fabrica == 74) {
						$tipo = "producao"; // teste - producao
						$admin_fale_conosco = ($tipo == "producao") ? 6409 : 6437;
						$cond_admin_fale_conosco = " AND tbl_admin.admin NOT IN ($admin_fale_conosco) ";
					}

					$sqlA = "SELECT admin, login, nome_completo FROM tbl_admin WHERE fabrica = {$login_fabrica} AND ativo IS TRUE {$cond_admin_fale_conosco} ORDER BY nome_completo asc";
					$resA = pg_query($con, $sqlA);

					if (pg_num_rows($resA) > 0) { ?>
						<select name='atendente' class='frm'>
							<option value=''></option>
							<?php for ($x = 0; $x < pg_num_rows($resA); $x++) {
								$xadmin = pg_result($resA, $x, admin);
								$login = pg_result($resA, $x, login);
								$nome_completo = pg_result($resA, $x, nome_completo);
								$selAtendente = ($atendente == $xadmin) ? "selected" : ""; ?>
								<option value="<?= $xadmin; ?>" <?= $selAtendente; ?>><?= $nome_completo; ?></option>
							<?php } ?>
						</select>
					<?php } ?>
				</div>
			<?php } ?>
		</div>
		<div class="span2"></div>
	</div>
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<label class="control-label">Status</label>
			<div class='controls controls-row'>
				<select name="status" class="frm" >
					<option value=""></option>
					<?php
					$sqlS = " SELECT status FROM tbl_hd_status where fabrica=$login_fabrica ";
					$resS = pg_query($con,$sqlS);

					for ($i = 0; $i < pg_num_rows($resS); $i++) {
						$status_hd = pg_result($resS, $i, 0);
						$selected_status = (strtoupper($status_hd) == $status) ? "SELECTED" : null; ?>
							<option value="<?=$status_hd?>" <?= $selected_status?> ><?= $status_hd?></option>
					<?php }	?>
				</select>
			</div>
			<?= $status_descricao; ?>
		</div>
		<?php if ($login_fabrica != 24) { ?>
			<div class="span4">
				<label class="control-label">
					<?php if ($login_fabrica == 137) {
						echo "Natureza" ;
					} elseif ($login_fabrica == 189) {
						echo "Identificação";
					} else {
						echo "Tipo"; 
					} ?>
				</label>
				<?php if ($login_fabrica <> 189) { ?>
					<div class='controls controls-row'>
						<?php if ($login_fabrica == 50) {
							$campoNome = 'name="tipo[]"';
							$campoMultiplo = 'multiple="multiple"';
							$optionVazio = '';
						} else {
							$campoNome = 'name="tipo"';
							$campoMultiplo = '';
							$optionVazio = '<option></option>';
						} ?>
						<select <?= $campoNome; ?> id="tipo" <?= $campoMultiplo; ?>>
							<?php
							echo $optionVazio;
							if ($login_fabrica == 174) { ?>
								<option value="C">B2C</option>
								<option value="R">B2B</option>
							<?php } else {
								$sql = "
									SELECT
										nome,
										descricao
									FROM tbl_natureza
									WHERE fabrica = {$login_fabrica}
										AND ativo = 't'
									ORDER BY nome;
								";
								$res = pg_query($con, $sql);
								if (pg_num_rows($res) > 0) {
									for ($y = 0; pg_num_rows($res) > $y; $y++) {
										$nome       = trim(pg_result($res, $y, nome));
										$descricao  = trim(pg_result($res, $y, descricao));
										echo "<option value='$nome'";
										if ($tipo == $nome) {
											echo "selected";
										}
										echo ">$descricao</option>";
									}
								}
							} ?>
						</select>
					</div>
				<?php } else { ?>
					<div class='controls controls-row'>
						<select name='tipo' style='width:250px' class='frm'>
							<option value=""> Selecione...</option>
							<option value="R" <?php echo ($xtipo == "R") ? "selected" : "";?>> Representante</option>
							<option value="V" <?php echo ($xtipo == "V") ? "selected" : "";?>> Viapol</option>
							<option value="C" <?php echo ($xtipo == "C") ? "selected" : "";?>> Clientes</option>
							<option value="T" <?php echo ($xtipo == "T") ? "selected" : "";?>> Transportadora</option>
						</select>
					</div>
				<?php } ?>
			</div>
		<?php } ?>
	</div>
	<?php if (in_array($login_fabrica, array(177))) { ?>
        <div class="row-fluid">
			<div class="span2"></div>
            <div class="span4">
               <label class='control-label'>Origem</label>
               <div class='controls controls-row'>
					<select id="origem" name="origem">
						<option value="">Selecione</option>
                        <?php                                                                   
                        $sqlOrige = "SELECT hd_chamado_origem, descricao FROM tbl_hd_chamado_origem WHERE fabrica = {$login_fabrica} ORDER BY descricao;";
                        $resOrigem = pg_query($con, $sqlOrige);
                        if (pg_num_rows($resOrigem) > 0) {
							while ($objeto_origem = pg_fetch_object($resOrigem)) {
								if($objeto_origem->descricao == $origem) {
									$selected = "selected='selected'";
								} else {
									$selected = "";
								} ?>
								<option value="<?=$objeto_origem->descricao?>" <?=$selected?>> <?=$objeto_origem->descricao?></option>
							<?php }                                                                              
						} ?>
					</select>
				</div>
			</div>
		</div>
	<?php }

	if (in_array($login_fabrica, [186])) { ?>
		<div class="row-fluid">
			<div class="span2"></div>
            <div class="span4">
               <label class='control-label'>E-mail do Consumidor</label>
               <div class='controls controls-row'>
					<input type="text" name="email_callcenter" id="email_callcenter" value="<?= $email_callcenter ?>" />
				</div>
			</div>
		</div>
	<?php
	}

	if ($login_fabrica == 30) { ?>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span4">
				<label class="control-label">Nº OS</label>
					<div class='controls controls-row'>
						<input type="text" name="os" id="os" size="12" maxlength="10" class='frm' value="<? if (strlen($os) > 0) echo $os; ?>" >
					</div>
			</div>
			<div class="span2"></div>
		</div>
	<?php }
	if ($login_fabrica == 74) { ?>
		<div class="row-fluid">
			<div class="span2"></div>
				<div class='span4'>
				<div class='control-group'>
					<label class='control-label'>Estado</label>
					<div class='controls controls-row'>
						<select name="estado" class="frm">
							<option value="">Selecione um Estado</option>
							<?php foreach ($array_estado as $k => $v) {
								$selectedEstado = ($estado == $k) ? ' selected="selected"' : ''; ?>
								<option value="<?= $k; ?>" <?= $selectedEstado; ?>><?= $v; ?></option>
							<?php } ?>
						</select>
					</div>
				</div>
			</div>
		</div>
	<?php }
	if($moduloProvidencia AND !in_array($login_fabrica,array(30,11,172,174))) { ?>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label'><?= ($login_fabrica == 189) ? "Ação" :"Providência";?></label>
					<div class='controls controls-row'>
						<select id="providencia" name="providencia">
							<option value="">Selecione</option>
							<?php
							$sql = "SELECT hd_motivo_ligacao, descricao FROM tbl_hd_motivo_ligacao WHERE fabrica = {$login_fabrica} ORDER BY descricao;";
							$resProvidencia = pg_query($con,$sql);

							if (pg_num_rows($resProvidencia) > 0) {
								while ($objeto_providencia = pg_fetch_object($resProvidencia)) {
									if ($objeto_providencia->hd_motivo_ligacao == $providencia) {
										$selected = "selected='selected'";
									} else {
										$selected = "";
									} ?>
									<option value="<?=$objeto_providencia->hd_motivo_ligacao?>" <?=$selected?>><?=$objeto_providencia->descricao?></option>
								<?php }
							} ?>
						</select>
					</div>
				</div>
			</div>
			<?php if (in_array($login_fabrica, array(169,170,178))) { ?>
				<div class='span4'>
					<div class='control-group'>
						<label class='control-label'>Origem</label>
						<div class='controls controls-row'>
							<select id="origem" name="origem">
								<option value="">Selecione</option>
								<?php
								$sql = "SELECT hd_chamado_origem, descricao	FROM tbl_hd_chamado_origem WHERE fabrica = {$login_fabrica} ORDER BY descricao;";
								$resOrigem = pg_query($con, $sql);

								if (pg_num_rows($resOrigem) > 0) {
									while ($objeto_origem = pg_fetch_object($resOrigem)) {
										if ($objeto_origem->descricao == $origem) {
											$selected = "selected='selected'";
										} else {
											$selected = "";
										} ?>
										<option value="<?=$objeto_origem->descricao?>" <?=$selected?>> <?=$objeto_origem->descricao?></option>
									<?php }
								} ?>
							</select>
						</div>
					</div>
				</div>
			<?php } ?>
			<div class="span2"></div>
		</div>
		<?php if (in_array($login_fabrica, [169,170])) { ?>
			<div class="row-fluid">
				<div class="span2"></div>
					<div class='span4'>
						<div class='control-group'>
							<label class='control-label'>Providência Nível 3</label>
							<div class='controls controls-row'>
								<select name="providencia_nivel_3" id='providencia_nivel_3' class='frm'>
									<option value=""></option>
									<?php
										$sqlProvidencia3 = "
											SELECT
												hd_providencia,
												descricao
											FROM tbl_hd_providencia WHERE fabrica = {$login_fabrica}
											AND ativo IS TRUE
											ORDER BY descricao DESC;
										";
										$resProvidencia3 = pg_query($con,$sqlProvidencia3);
										if (pg_num_rows($resProvidencia3) > 0) {
											while ($dadosProv = pg_fetch_object($resProvidencia3)) {
												$selected = ($dadosProv->hd_providencia == $_POST['providencia_nivel_3']) ? "selected" : ""; ?>
												<option value="<?=$dadosProv->hd_providencia?>" <?=$selected?>><?= $dadosProv->descricao ?></option>
											<?php }
										} ?>
								</select>
							</div>
						</div>
					</div>
					<div class='span4'>
						<div class='control-group'>
							<label class='control-label'>Motivo Contato</label>
							<div class='controls controls-row'>
								<select name="motivo_contato" id='motivo_contato' class='frm'>
									<option value=""></option>
									<?php
									$sqlMotivoContato = "
										SELECT
											motivo_contato,
											descricao
										FROM tbl_motivo_contato WHERE fabrica = {$login_fabrica}
										AND ativo IS TRUE
										ORDER BY descricao DESC;
									";
									$resMotivoContato = pg_query($con,$sqlMotivoContato);
									if (pg_num_rows($resMotivoContato) > 0) {
										while ($dadosContato = pg_fetch_object($resMotivoContato)) {
											$selected = ($dadosContato->motivo_contato == $_POST['motivo_contato']) ? "selected" : ""; ?>
											<option value="<?=$dadosContato->motivo_contato?>" <?=$selected?>><?= $dadosContato->descricao ?></option>
										<?php }
									} ?>
								</select>
							</div>
						</div>
					</div>
				<div class="span2"></div>
			</div>
		<?php }
	} ?>

	<?php
	if ($classificacaoHD) { ?>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label'>Classificação</label>
					<div class='controls controls-row'>
						<select id="hd_classificacao" name="hd_classificacao">
							<option value="">Selecione</option>
							<?php
							$sql = "SELECT hd_classificacao, descricao FROM tbl_hd_classificacao WHERE fabrica = {$login_fabrica} AND ativo IS TRUE ORDER BY descricao;";
							$res = pg_query($con, $sql);

							if (pg_num_rows($res) > 0) {
								while($objeto = pg_fetch_object($res)) {
									if ($objeto->hd_classificacao == $hd_classificacao) {
										$selected = "selected='selected'";
									} else {
										$selected = "";
									} ?>
									<option value="<?=$objeto->hd_classificacao?>" <?=$selected?>><?=$objeto->descricao?></option>
								<?php }
							} ?>
						</select>
					</div>
				</div>
			</div>
		</div>
	<?php }
	if ($login_fabrica == 15) { ?>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span8">
				<label class='checkbox'>
					<input type='checkbox' name='bi_latina' value='t' class='frm' <?php if($_POST['bi_latina']=='t') { echo 'checked'; } ?>> BI Callcenter
				</label>
			</div>
		</div>
	<?php }
	if (in_array($login_fabrica, array(169,170))) { ?>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span3">
				<div class="control-group">
					<label class="control-label" for="">&nbsp;</label>
					<div class="controls controls-row">
						<label class="checkbox" >
							<input type='checkbox' name='jornada' id='jornada' value='true' <?if($xjornada == 'true') echo "CHECKED";?> /> Jornada
						</label>
					</div>
				</div>
			</div>
			<div class="span2"></div>
		</div>
	<?php }
	if ($login_fabrica == 52) { ?>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for="hd_classificacao">Classificação do Atendimento</label>
					<div class='controls controls-row'>
						<select name="hd_classificacao" id="hd_classificacao">
							<option value=""></option>
							<?php
							$aux_sql = "SELECT hd_classificacao, descricao FROM tbl_hd_classificacao WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY descricao;";
							$aux_res = pg_query($con, $aux_sql);
							$aux_row = pg_num_rows($aux_res);

							for ($wx = 0; $wx < $aux_row; $wx++) { 
								$hd_classificacao = pg_fetch_result($aux_res, $wx, 'hd_classificacao');
								$descricao        = pg_fetch_result($aux_res, $wx, 'descricao');
								if ($_POST["hd_classificacao"] == $hd_classificacao) {
									$aux_select = "SELECTED";
								} else {
									$aux_select = "";
								} ?>
								<option <?=$aux_select;?> value="<?=$hd_classificacao;?>"><?=$descricao;?></option>
							<?php } ?>
						</select>
					</div>
				</div>
			</div>
			<div class="span2"></div>
		</div>
	<?php } ?>
	<br />
	<div class="tac">
		<input class="btn" type='submit' name='btn_acao' value='Consultar'> <br /> <br />
		<?php if ($login_fabrica != 177) { ?>
			<input class="btn btn-primary" type='submit' name='btn_lista_todos' value='Listar Todas as Pendências'><br /><br />
			<?php if ($telecontrol_distrib OR $interno_telecontrol OR $login_fabrica == 174) { ?>
				<input class="btn btn-primary" type='submit' name='btn_resumo_atendimento_aberto' value='Resumo de Atendimentos Aberto por Atendente'>
				<br /><br />
			<?php }
		} ?>
	</div>
</form>
</div>
<?php

		if (isset($_POST['btn_resumo_atendimento_aberto']) AND $telecontrol_distrib OR $interno_telecontrol OR $login_fabrica == 174){	
				$subUltimaInteracao = "(
					SELECT  tbl_hd_chamado_item.data
					FROM   tbl_hd_chamado_item
					WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
					ORDER BY tbl_hd_chamado_item.data DESC
					LIMIT 1
				)";

				$sql_resumo_atendente = "SELECT COUNT(1) AS qtde, tbl_admin.login, tbl_admin.nome_completo,		
					COUNT(1) FILTER(WHERE current_timestamp - data BETWEEN interval '0 day' 
					AND interval '6 days') AS aberto1a6,
					COUNT(1) FILTER(WHERE current_timestamp - data BETWEEN interval '6 days' 
					AND interval '14 days') AS aberto7a14,
					COUNT(1) FILTER(WHERE current_timestamp - data BETWEEN interval '14 days' AND interval '29 days') AS aberto15a29,
					COUNT(1) FILTER(WHERE current_timestamp - data BETWEEN interval '29 days' AND interval '44 days') AS aberto30a44,
					COUNT(1) FILTER(WHERE current_timestamp - data BETWEEN interval '44 days' AND interval '59 days') AS aberto45a59,
					COUNT(1) FILTER(WHERE current_timestamp - data BETWEEN interval '59 days' AND interval '89 days') AS aberto60a89,
					COUNT(1) FILTER(WHERE current_timestamp - data BETWEEN interval '89 days' AND interval '120 days') AS aberto90a120,
					COUNT(1) FILTER(WHERE current_timestamp - data > interval '119 days') AS abertomais120,
					COUNT(1) FILTER(WHERE current_timestamp - {$subUltimaInteracao} BETWEEN interval '0 day' 
					AND interval '6 days') AS ultima1a6,
					COUNT(1) FILTER(WHERE current_timestamp - {$subUltimaInteracao} BETWEEN interval '6 days' 
					AND interval '14 days') AS ultima7a14,
					COUNT(1) FILTER(WHERE current_timestamp - {$subUltimaInteracao} BETWEEN interval '14 days' AND interval '29 days') AS ultima15a29,
					COUNT(1) FILTER(WHERE current_timestamp - {$subUltimaInteracao} BETWEEN interval '29 days' AND interval '44 days') AS ultima30a44,
					COUNT(1) FILTER(WHERE current_timestamp - {$subUltimaInteracao} BETWEEN interval '44 days' AND interval '59 days') AS ultima45a59,
					COUNT(1) FILTER(WHERE current_timestamp - {$subUltimaInteracao} BETWEEN interval '59 days' AND interval '89 days') AS ultima60a89,
					COUNT(1) FILTER(WHERE current_timestamp - {$subUltimaInteracao} BETWEEN interval '89 days' AND interval '120 days') AS ultima90a120,
					COUNT(1) FILTER(WHERE current_timestamp - {$subUltimaInteracao} > interval '119 days') AS ultimamais120
					FROM tbl_hd_chamado
					JOIN tbl_admin ON atendente = tbl_admin.admin AND tbl_admin.fabrica={$login_fabrica}
					join tbl_hd_chamado_extra using(hd_chamado)
					WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica AND tbl_hd_chamado.status='Aberto'
					AND tbl_hd_chamado.posto isnull
					GROUP BY tbl_admin.login, tbl_admin.nome_completo
					ORDER BY tbl_admin.nome_completo ASC
";

				$res_resumo_atendente = pg_query($con,$sql_resumo_atendente);

				if(strlen($msg_erro) == 0) {
					echo "<form name='frm_resumo_atendimento_aberto' method='post' action='#'>";
					echo "<br>";	
					echo "<table border='1'>";
					echo "<tr style='text-align:center;' class='titulo_tabela'>
						<td colspan='2' style='background-color: white;'>&nbsp;</td>
						<td colspan='3'>&nbsp;+120 dias&nbsp;</td>
						<td colspan='3'>&nbsp;90 a 120 dias&nbsp;</td>
						<td colspan='3'>&nbsp;60 a 80 dias&nbsp;</td>
						<td colspan='3'>&nbsp;45 a 59 dias&nbsp;</td>
						<td colspan='3'>&nbsp;30 a 44 dias&nbsp;</td>
						<td colspan='3'>&nbsp;15 a 29 dias&nbsp;</td>
						<td colspan='3'>&nbsp;7 a 14 dias&nbsp;</td>
						<td colspan='3'>&nbsp;1 a 6 dias&nbsp;</td>
						</tr>		  
						<tr style='text-align:center;font-weight: bold;' class='tc_formulario'>
						<td>Atendente</td>
						<td>Total</td>
						<td>Qtde</td>
						<td>%</td>
						<td>Última Interação</td>
						<td>Qtde</td>
						<td>%</td>
						<td>Última Interação</td>
						<td>Qtde</td>
						<td>%</td>
						<td>Última Interação</td>
						<td>Qtde</td>
						<td>%</td>
						<td>Última Interação</td>
						<td>Qtde</td>
						<td>%</td>
						<td>Última Interação</td>
						<td>Qtde</td>
						<td>%</td>
						<td>Última Interação</td>
						<td>Qtde</td>
						<td>%</td>
						<td>Última Interação</td>
						<td>Qtde</td>
						<td>%</td>		
						<td>Última Interação</td>	
						</tr>
";	

				for($x=0;pg_num_rows($res_resumo_atendente)>$x;$x++){						

					$qtde_resumo      = pg_result($res_resumo_atendente,$x,qtde);
					$login_resumo     = pg_result($res_resumo_atendente,$x,login);			
					$nome_resumo      = pg_result($res_resumo_atendente,$x,nome_completo);	
					$aberto1a6        = pg_result($res_resumo_atendente,$x,aberto1a6);
					$aberto7a14       = pg_result($res_resumo_atendente,$x,aberto7a14);
					$aberto15a29      = pg_result($res_resumo_atendente,$x,aberto15a29);
					$aberto30a44      = pg_result($res_resumo_atendente,$x,aberto30a44);
					$aberto45a59      = pg_result($res_resumo_atendente,$x,aberto45a59);
					$aberto60a89      = pg_result($res_resumo_atendente,$x,aberto60a89);
					$aberto90a120     = pg_result($res_resumo_atendente,$x,aberto90a120);
					$abertomais120    = pg_result($res_resumo_atendente,$x,abertomais120);
					$ultima1a6        = pg_result($res_resumo_atendente,$x,ultima1a6);
					$ultima7a14       = pg_result($res_resumo_atendente,$x,ultima7a14);
					$ultima15a29      = pg_result($res_resumo_atendente,$x,ultima15a29);
					$ultima30a44      = pg_result($res_resumo_atendente,$x,ultima30a44);
					$ultima45a59      = pg_result($res_resumo_atendente,$x,ultima45a59);
					$ultima60a89      = pg_result($res_resumo_atendente,$x,ultima60a89);
					$ultima90a120     = pg_result($res_resumo_atendente,$x,ultima90a120);	
					$ultimamais120    = pg_result($res_resumo_atendente,$x,ultimamais120);

					echo "<tr style='text-align:center;'>
						<td>
						$nome_resumo
						<input type='hidden' name='login_resumo_" . $x . "' class='login_resumo' id='login_resumo_" . $x . "' value='$login_resumo'/>
						</td>				
						<td>$qtde_resumo</td>
						<td>";										
				if($abertomais120 > 0) {
					$periodo = "mais120";
					echo "<a href='#' data-login='$login_resumo' data-loginfabrica='$login_fabrica' data-periodo='$periodo' class='ver_detalhe_atendimento'>$abertomais120</a>";	
					echo "<input type='hidden' name='periodo_" . $x . "' id='periodo_" . $x . "' value='$periodo' class='periodo'></td>";						
				} else {
					echo "$abertomais120</td>";
				}
				echo "
<td>" . number_format((($abertomais120 / $qtde_resumo) * 100), 2, ',', '.') . "%</td>
<td>";
				if($ultimamais120 > 0) {
					$periodo = "mais120";
					echo "<a href='#' data-login='$login_resumo' data-loginfabrica='$login_fabrica' data-periodo='$periodo' class='ver_detalhe_atendimento' data-tipo='ultima'>$ultimamais120</a>";	
					echo "<input type='hidden' name='periodo_" . $x . "' id='periodo_" . $x . "' value='$periodo' class='periodo'></td>";						
				} else {
					echo "$ultimamais120</td>";
				}

				echo "
<td>";					
if($aberto90a120 > 0) {
$periodo = "90a120";
echo "<a href='#' data-login='$login_resumo' data-loginfabrica='$login_fabrica' data-periodo='$periodo' class='ver_detalhe_atendimento'>$aberto90a120</a>";	
echo "<input type='hidden' name='periodo_" . $x . "' id='periodo_" . $x . "' value='$periodo' class='periodo'></td>";											
} else {
echo "$aberto90a120</td>";
}
echo "<td>" . number_format((($aberto90a120 / $qtde_resumo) * 100), 2, ',', '.') . "%</td>
<td>";
				if($ultima90a120 > 0) {
					$periodo = "90a120";
					echo "<a href='#' data-login='$login_resumo' data-loginfabrica='$login_fabrica' data-periodo='$periodo' class='ver_detalhe_atendimento' data-tipo='ultima'>$ultima90a120</a>";	
					echo "<input type='hidden' name='periodo_" . $x . "' id='periodo_" . $x . "' value='$periodo' class='periodo'></td>";											
				} else {
					echo "$ultima90a120</td>";
				}
				echo "
<td>";					
if($aberto60a89 > 0) {
$periodo = "60a89";
echo "<a href='#' data-login='$login_resumo' data-loginfabrica='$login_fabrica' data-periodo='$periodo' class='ver_detalhe_atendimento'>$aberto60a89</a>";
echo "<input type='hidden' name='periodo_" . $x . "' id='periodo_" . $x . "' value='$periodo' class='periodo'></td>";						
} else {
echo "$aberto60a89</td>";
}
echo "
<td>" . number_format((($aberto60a89 / $qtde_resumo) * 100), 2, ',', '.') . "%</td>
<td>";
if($ultima60a89 > 0) {
$periodo = "60a89";
echo "<a href='#' data-login='$login_resumo' data-loginfabrica='$login_fabrica' data-periodo='$periodo' class='ver_detalhe_atendimento' data-tipo='ultima'>$ultima60a89</a>";
echo "<input type='hidden' name='periodo_" . $x . "' id='periodo_" . $x . "' value='$periodo' class='periodo'></td>";						
} else {
echo "$ultima60a89</td>";
}

echo "
<td>";					
if($aberto45a59 > 0) {
$periodo = "45a59";
echo "<a href='#' data-login='$login_resumo' data-loginfabrica='$login_fabrica' data-periodo='$periodo' class='ver_detalhe_atendimento'>$aberto45a59</a>";	
echo "<input type='hidden' name='periodo_" . $x . "' id='periodo_" . $x . "' value='$periodo' class='periodo'></td>";						
} else {
echo "$aberto45a59</td>";
}
echo "
<td>" . number_format((($aberto45a59 / $qtde_resumo) * 100), 2, ',', '.') . "%</td>
<td>";
if($ultima45a59 > 0) {
$periodo = "45a59";
echo "<a href='#' data-login='$login_resumo' data-loginfabrica='$login_fabrica' data-periodo='$periodo' class='ver_detalhe_atendimento' data-tipo='ultima'>$aberto45a59</a>";	
echo "<input type='hidden' name='periodo_" . $x . "' id='periodo_" . $x . "' value='$periodo' class='periodo'></td>";						
} else {
echo "$aberto45a59</td>";
}
echo "		
<td>";					
if($aberto30a44 > 0) {
$periodo = "30a44";
echo "<a href='#' data-login='$login_resumo' data-loginfabrica='$login_fabrica' data-periodo='$periodo' class='ver_detalhe_atendimento'>$aberto30a44</a>";	
echo "<input type='hidden' name='periodo_" . $x . "' id='periodo_" . $x . "' value='$periodo' class='periodo'></td>";						
} else {
echo "$aberto30a44</td>";
}
echo "
<td>" . number_format((($aberto30a44 / $qtde_resumo) * 100), 2, ',', '.') . "%</td>
<td>";
if($ultima30a44 > 0) {
$periodo = "30a44";
echo "<a href='#' data-login='$login_resumo' data-loginfabrica='$login_fabrica' data-periodo='$periodo' class='ver_detalhe_atendimento' data-tipo='ultima'>$ultima30a44</a>";	
echo "<input type='hidden' name='periodo_" . $x . "' id='periodo_" . $x . "' value='$periodo' class='periodo'></td>";						
} else {
echo "$ultima30a44</td>";
}
echo "			
<td>";					
if($aberto15a29 > 0) {
$periodo = "15a29";
echo "<a href='#' data-login='$login_resumo' data-loginfabrica='$login_fabrica' data-periodo='$periodo' class='ver_detalhe_atendimento'>$aberto15a29</a>";
echo "<input type='hidden' name='periodo_" . $x . "' id='periodo_" . $x . "' value='$periodo' class='periodo'></td>";						
} else {
echo "$aberto15a29</td>";
}
echo "
<td>" . number_format((($aberto15a29 / $qtde_resumo) * 100), 2, ',', '.') . "%</td>
<td>";
if($ultima15a29 > 0) {
$periodo = "15a29";
echo "<a href='#' data-login='$login_resumo' data-loginfabrica='$login_fabrica' data-periodo='$periodo' class='ver_detalhe_atendimento' data-tipo='ultima'>$ultima15a29</a>";
echo "<input type='hidden' name='periodo_" . $x . "' id='periodo_" . $x . "' value='$periodo' class='periodo'></td>";						
} else {
echo "$ultima15a29</td>";
}
echo "
<td>";					
if($aberto7a14 > 0) {
$periodo = "7a14";
echo "<a href='#' data-login='$login_resumo' data-loginfabrica='$login_fabrica' data-periodo='$periodo' class='ver_detalhe_atendimento'>$aberto7a14</a>";	
echo "<input type='hidden' name='periodo_" . $x . "' id='periodo_" . $x . "' value='$periodo' class='periodo'></td>";						
} else {
echo "$aberto7a14</td>";
}
echo "
<td>" . number_format((($aberto7a14 / $qtde_resumo) * 100), 2, ',', '.') . "%</td>
<td>";
if($ultima7a14 > 0) {
$periodo = "7a14";
echo "<a href='#' data-login='$login_resumo' data-loginfabrica='$login_fabrica' data-periodo='$periodo' class='ver_detalhe_atendimento' data-tipo='ultima'>$ultima7a14</a>";	
echo "<input type='hidden' name='periodo_" . $x . "' id='periodo_" . $x . "' value='$periodo' class='periodo'></td>";						
} else {
echo "$ultima7a14</td>";
}
echo "		
<td>";					
if($aberto1a6 > 0) {
$periodo = "1a6";
echo "<a href='#' data-login='$login_resumo' data-loginfabrica='$login_fabrica' data-periodo='$periodo' class='ver_detalhe_atendimento'>$aberto1a6</a>";
echo "<input type='hidden' name='periodo_" . $x . "' id='periodo_" . $x . "' value='$periodo' class='periodo'></td>";						
} else {
echo "$aberto1a6</td>";
}					
echo "
<td>" . number_format((($aberto1a6 / $qtde_resumo) * 100), 2, ',', '.') . "%</td>
<td>";
if($ultima1a6 > 0) {
$periodo = "1a6";
echo "<a href='#' data-login='$login_resumo' data-loginfabrica='$login_fabrica' data-periodo='$periodo' class='ver_detalhe_atendimento' data-tipo='ultima'>$ultima1a6</a>";
echo "<input type='hidden' name='periodo_" . $x . "' id='periodo_" . $x . "' value='$periodo' class='periodo'></td>";						
} else {
echo "$ultima1a6</td>";
}					
echo "</tr>";

				}
				echo "</table>";
				echo "<br></form>";
				}
}

if(($login_fabrica==59 and strlen($_POST['btn_acao']) > 0 and strlen($msg_erro)==0) or (($login_fabrica<>59 AND strlen($msg_erro)==0) AND (!isset($bi_latina)) && (isset($_POST['btn_lista_todos']))) || (strlen($_POST['btn_acao']) > 0 && strlen($msg_erro)==0)) {
	$widthEsmaltec = '700';
	$alignEsmaltec = 'left';
	if ($login_fabrica == 30) {
		$widthEsmaltec = '88%';
		$alignEsmaltec = 'center';
	}

	echo "<BR>";
	echo "<table width='$widthEsmaltec' align='center' class='formulario'>";
		echo "<TR >\n";
			echo "<TD width='50%'>";
				echo "<table width='$widthEsmaltec' border='0' align='$alignEsmaltec' cellpadding='2' cellspacing='2' style='font-size:10px'>";
                    if ($login_fabrica == 74) {
                        $vHoje = '#FFFF00';
                        $v2dias = '#FABD6B';
                        $v3dias = '#F8615C';

                        echo "<TR >\n";
                            echo "<TD width='10'><span style='background:$vHoje;width:15px;height:15px;display:block;'></span></TD >";
                            echo "<TD align='left'>Data de providência que se encerra hoje</TD >";
                        	echo "<td width='10'><span style='background:$v2dias;width:15px;height:15px;display:block;border:solid 1px #ccc'></span></td>";
                        	echo '<td align="left">Providência vencida há 2 dias</td>';
                        	echo "<td width='10'><span style='background:$v3dias;width:15px;height:15px;display:block;'></span></td>";
                        	echo '<td align="left">Providência vencida há 3 dias ou mais</td>';
                        echo '</tr>';
                    } elseif($login_fabrica !=151){
	                    echo "<TR >\n";
                        if ($login_fabrica == 51){
                            echo "<TD width='10'><span style='background:#91C8FF;width:15px;height:15px;display:block;'></span></TD >";
                            echo "<TD align='left'>Chamados que você ainda não abriu</TD >";
                        }
                        if ($login_fabrica == 30) {
	                            echo "<TD width='10'><span style='background:#F8615C;width:15px;height:15px;display:block;'></span></TD >";
	                            echo "<TD width='39%' align='left'>Protocolos acima de 30 dias com base na data de abertura</TD >";
	                            echo "<TD width='10'>
                                     <span style='background:#FABD6B;width:15px;height:15px;display:block;'></span>
	                            </TD >";
	                            echo "<TD width='39%' align='left'>Protocolos acima de 20 dias com base na data de abertura</TD >";
                                echo "<TD width='10'>
	                               <span style='background:#BA8DFF;width:15px;height:15px;display:block;'></span>
                                </TD >";
                                echo "<TD width='20%' align='left'>Data providência em atraso</TD >";
                    	} else {
                    			echo "<TD width='10'><span style='background:#91C8FF;width:15px;height:15px;display:block;'></span></TD >";
                        		echo "<TD align='left'>Último contato hoje</TD >";
	                            echo "<TD width='10'><span style='background:#F8615C;width:15px;height:15px;display:block;'></span></TD >";
	                            echo "<TD align='left'>Último contato há mais de 3 dias</TD >";
	                            echo "<TD width='10'><span style='background:#FABD6B;width:15px;height:15px;display:block;'></span></TD >";
	                            echo "<TD align='left'>Último contato há 2 dias</TD >";
                    	}

                        if ($login_fabrica == 11 or $login_fabrica == 172) {
                                echo "<TD width='10'><span style='background:#BA8DFF;width:15px;height:15px;display:block;'></span></TD >";
                                echo "<TD align='left'>Data Programada (Resolução) atrasada</TD >";
                        }
                        if ($login_fabrica == 85) {
                                echo "<TD width='10'><span style='background:#81F781;width:15px;height:15px;display:block;'></span></TD >";
                                echo "<TD align='left'>Atendimento com Ordem de Serviço finalizada</TD >";
                                echo "<TD width='10'><span style='background:#9370DB;width:15px;height:15px;display:block;'></span></TD >";
                                echo "<TD align='left'>Atendimento com Ordem de Serviço finalizada há menos de 36 hrs</TD >";
                        }
                        echo "</TR >\n";

                        if (in_array($login_fabrica, array(169,170))){
                        	echo "<TR>";
                            echo "<TD width='10'><span style='background:#33cc33;width:15px;height:15px;display:block;'></span></TD >";
                            echo "<TD align='left'>Status Jornada de 0 - 15 Dias.</TD >";

                        	echo "<TD width='10'><span style='background:#ffff00;width:15px;height:15px;display:block;'></span></TD >";
                            echo "<TD align='left'>Status Jornada de 16 - 25 Dias.</TD >";

                            echo "<TD width='10'><span style='background:#ff0000;width:15px;height:15px;display:block;'></span></TD >";
                            echo "<TD align='left'>Status Jornada Acima de 25 Dias.</TD >";
                        	echo "</TR>";
                        }

					} else {
?>
                    <TR >
                        <TD width='10'>
							<span style='background:#F8615C;width:15px;height:15px;display:block;'></span>
                        </TD >
                        <TD align='left'>Protocolos que estão com prazo vencido</TD >
                        <TD width='10'>
							<span style='background:#91C8FF;width:15px;height:15px;display:block;'></span>
                        </TD >
                        <TD align='left'>Protocolos que estão vencendo no dia</TD >
                        <TD width='10'>
							<span style='background:#F1F4FA;border:solid 1px #ccc;width:15px;height:15px;display:block;'></span>
                        </TD >
                        <TD align='left'>Protocolos que estão no prazo</TD >
                    </TR >
<?
					}
				echo "</TABLE >\n";
				/*imagens_admin/callcenter_interativo.gif
				imagens_admin/consulta_callcenter.gif*/
			echo "</TD >\n";
		echo "</TR >\n";
	echo "</TABLE >\n";
	echo "<BR>";

	if(strlen($supervisor)>0){
		$cond1 = " 1 = 1 ";

		if ($login_fabrica == 2) {
			$cond_site = "OR tbl_hd_chamado.atendente = 2029";
		}
	}else {
		if($login_fabrica == 7){
			$cond1 = " tbl_hd_chamado.atendente = $login_admin or tbl_hd_chamado.admin = $login_admin";
		}else{
			$cond1 = " tbl_hd_chamado.atendente = $login_admin or tbl_hd_chamado.sequencia_atendimento = $login_admin";
		}
	}

	if ( $login_fabrica == 5 ) {
		 // providencia
		 $providencia_chk = ( isset($_POST['providencia_chk']) ) ? $_POST['providencia_chk'] : $_GET['providencia_chk'];
		 if ( isset($providencia_chk) && ! empty($providencia_chk) ) {
		 	$providencia = ( isset($_POST['providencia']) ) ? $_POST['providencia'] : $_GET['providencia'];
		 	$providencia = ( ! empty($providencia) ) ? pg_escape_string($providencia) : null ;
		 	$cond_4       = ( ! empty($providencia) ) ? ' tbl_hd_chamado_extra.data_providencia = '.$providencia : $cond1_4 ;
		 }
		 unset($providencia_chk,$providencia);
		 // data providencia
		 $providencia_data_chk = ( isset($_POST['providencia_data_chk']) ) ? $_POST['providencia_data_chk'] : $_GET['providencia_data_chk'];
		 if ( isset($providencia_data_chk) && ! empty($providencia_data_chk) ) {
		 	$providencia_data = ( isset($_POST['providencia_data']) ) ? $_POST['providencia_data'] : $_GET['providencia_data'];
		 	$providencia_data = ( ! empty($providencia_data) ) ? pg_escape_string(fnc_formata_data_pg($providencia_data)) : null ;
		 	$cond_5            = ( ! empty($providencia_data) ) ? ' tbl_hd_chamado.previsao_termino = '.$providencia_data : $cond_5 ;
		 }
	}
	# atendente 2029 = SAC ABERTO PELO SITE.
	#para suggar separar pendecia de aberto

	if ($login_fabrica == 24 && $_POST['status'] != "Cancelado" && $_POST['status'] != "Resolvido") {
		$sql_pendente = " AND tbl_hd_chamado.status = 'Pendente' ";
	}

	$campo_revenda = "";
	$joinRevenda   = "";
	if ($login_fabrica == 74) {
		$cond_admin_fale_conosco = " AND tbl_hd_chamado.status IS NOT NULL ";
		$joinRevenda   = "JOIN tbl_revenda ON tbl_revenda.revenda=tbl_hd_chamado_extra.revenda";
		$campo_revenda = "tbl_revenda.nome AS nome_revenda,";
	}

	if($moduloProvidencia AND !in_array($login_fabrica,array(30,11,172))){
		$providencia = $_POST["providencia"];

		if(!empty($providencia)){
			$cond_providencia = " AND tbl_hd_motivo_ligacao.hd_motivo_ligacao = {$providencia}";
		}
	}

	if ($login_fabrica == 158) {
		$leftJoinCockpit = "LEFT JOIN tbl_hd_chamado_cockpit ON tbl_hd_chamado_cockpit.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado_cockpit.fabrica = {$login_fabrica}";
		$whereCockpit = "AND tbl_hd_chamado_cockpit.hd_chamado_cockpit IS NULL";
	}

	if (in_array($login_fabrica, array(153))) {
		$cond_sem_helpdesk = "AND tbl_hd_chamado.titulo <> 'Help-Desk Posto'";
	}

	if(in_array($login_fabrica, array(169,170))){
		$campo_select = "(select to_char(tbl_hd_chamado_item.data,'DD/MM/YYYY')
						from tbl_hd_chamado_item
					where tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
						order by data desc limit 1) as data_interacao ,";

		if ($xjornada == "true"){
			$cond_jornada = "
				AND (
					(tbl_hd_jornada.cidade = tbl_cidade.cidade AND tbl_hd_jornada.produto = tbl_os.produto)
					OR (tbl_hd_jornada.estado = tbl_cidade.estado AND tbl_hd_jornada.cidade IS NULL AND tbl_hd_jornada.produto = tbl_os.produto)
					OR (tbl_hd_jornada.estado IS NULL AND tbl_hd_jornada.cidade IS NULL AND tbl_hd_jornada.produto = tbl_os.produto)
					OR (tbl_hd_jornada.cidade = tbl_cidade.cidade AND tbl_hd_jornada.produto IS NULL)
					OR (tbl_hd_jornada.estado = tbl_cidade.estado AND tbl_hd_jornada.cidade IS NULL AND tbl_hd_jornada.produto IS NULL)
				)
			";
		}
		$join_jornada = "
			LEFT JOIN tbl_hd_jornada ON tbl_hd_jornada.fabrica = {$login_fabrica}
			LEFT JOIN tbl_os ON (tbl_os.os = tbl_hd_chamado_extra.os OR tbl_hd_chamado.hd_chamado = tbl_os.hd_chamado) AND tbl_os.fabrica = {$login_fabrica}
			LEFT JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint
		";

		$campo_jornada = "
			, CASE WHEN tbl_os.os IS NOT NULL AND (
				(tbl_hd_jornada.cidade = tbl_cidade.cidade AND tbl_hd_jornada.produto = tbl_os.produto)
				OR (tbl_hd_jornada.estado = tbl_cidade.estado AND tbl_hd_jornada.cidade IS NULL AND tbl_hd_jornada.produto = tbl_os.produto)
				OR (tbl_hd_jornada.estado IS NULL AND tbl_hd_jornada.cidade IS NULL AND tbl_hd_jornada.produto = tbl_os.produto)
				OR (tbl_hd_jornada.cidade = tbl_cidade.cidade AND tbl_hd_jornada.produto IS NULL)
				OR (tbl_hd_jornada.estado = tbl_cidade.estado AND tbl_hd_jornada.cidade IS NULL AND tbl_hd_jornada.produto IS NULL)
			) THEN
				TRUE
			ELSE
				FALSE
			END AS jornada,
			tbl_status_checkpoint.descricao AS status_os,
			extract(day from current_timestamp - tbl_os.data_digitacao) AS status_jornada
		";

		$distinct_on = "DISTINCT ON (tbl_hd_chamado.hd_chamado)";
		$order_by = "ORDER BY tbl_hd_chamado.hd_chamado ASC, jornada DESC";

	}else{
		$campo_select = "(select to_char(tbl_hd_chamado_item.data,'DD/MM/YYYY')
						from tbl_hd_chamado_item
					where tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
					order by data desc limit 1) as data_interacao ,";
		$order_by = "ORDER BY tbl_hd_chamado.hd_chamado ASC";
	}

	if($login_fabrica == 161){
		$campo_numero_serie = " tbl_hd_chamado_extra.serie,  ";
	}

	if (in_array($login_fabrica, array(30))) {
		$cond_sem_helpdesk = "AND tbl_hd_chamado.titulo <> 'Help-Desk Admin'";
	}
	$busca = true;
}

if($busca or $maisAtendimento ){
	if($gerar_xls=='t'){
		ob_start();//INICIA BUFFER
	}

	if($maisAtendimento){
		$cond1 = " tbl_hd_chamado.atendente = $login_admin or tbl_hd_chamado.sequencia_atendimento = $login_admin";
		if(!in_array($login_fabrica, array(183,189))){
			$cond_2 = " AND     tbl_hd_chamado.data_providencia::date <= CURRENT_DATE "; 
		}

		$cond_8 = " AND tbl_hd_chamado.status not in ('Resolvido', 'Cancelado') ";
		$order_by = " ORDER BY tbl_hd_chamado.hd_chamado desc "; 
	}

	if (in_array($login_fabrica, [174])) {
		$campoTransferido = ",adminD.nome_completo AS transferido_por";
		$joinHdItem = "LEFT JOIN tbl_hd_chamado_item item_adm ON tbl_hd_chamado.hd_chamado = item_adm.hd_chamado
					   AND item_adm.admin_transferencia IS NOT NULL
					   LEFT JOIN tbl_admin adminD ON adminD.admin = item_adm.admin_transferencia AND adminD.fabrica = {$login_fabrica}";
	}
	
	$campo_hd_classificacao = "";

	if ($login_fabrica == 52) {
		$campo_hd_classificacao = " ,tbl_hd_classificacao.descricao AS hd_classificacao ";
		$join_hd_classificacao  = " LEFT JOIN tbl_hd_classificacao ON tbl_hd_classificacao.hd_classificacao = tbl_hd_chamado.hd_classificacao AND tbl_hd_classificacao.fabrica = $login_fabrica ";
	}

	$left_os_hd = (!empty($join_jornada)) ? "" : " LEFT JOIN tbl_os ON tbl_os.os = tbl_hd_chamado_extra.os ";

	$sql = "SELECT $distinct_on  tbl_hd_chamado.hd_chamado,
					tbl_hd_chamado.titulo,
					tbl_hd_chamado.hd_classificacao,
					tbl_hd_chamado.status,
					tbl_hd_chamado.sequencia_atendimento,
					to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data,
					to_char(tbl_hd_chamado.data,'HH24:MI') AS hora,
					to_char(tbl_hd_chamado.data+ INTERVAL '5 DAYS','DD/MM/YYYY') AS data_maxima,
					tbl_hd_chamado.categoria,
					tbl_hd_chamado_extra.nome as cliente_nome,
					tbl_hd_chamado_extra.origem as origem_consumidor ,
					tbl_hd_providencia.descricao AS descricao_providencia,
					tbl_motivo_contato.descricao AS descricao_motivo_contato,
					tbl_adminA.login as atendente,
					tbl_adminA.nome_completo as nome_completo_admin,
					tbl_adminB.login as admin,
					tbl_adminC.login as intervensor,
					$campo_select
					tbl_hd_chamado_extra.dias_aberto,
					$campo_numero_serie
					tbl_hd_chamado_extra.dias_ultima_interacao,
					tbl_hd_chamado_extra.leitura_pendente,
					{$campo_revenda}
					tbl_cidade.nome as nome_cidade,
					tbl_posto.nome,
					tbl_posto_fabrica.codigo_posto,
					tbl_os.sua_os,
					tbl_hd_chamado_extra.defeito_reclamado as defeito_reclamado,
					tbl_hd_chamado_extra.os,
					tbl_hd_chamado.cliente_admin,
					tbl_cliente_admin.nome as nome_cliente_admin,
					tbl_hd_chamado_extra.array_campos_adicionais as campos_adicionais,
					to_char(tbl_hd_chamado.data_providencia,'DD/MM/YYYY') AS providencia_data,
					tbl_hd_situacao.descricao AS providencia,
					tbl_hd_chamado.data_providencia,
					tbl_cidade.estado,
					tbl_produto.referencia,
					tbl_produto.descricao as produto_descricao,
					tbl_hd_motivo_ligacao.descricao AS providencia_motivo
					{$campo_jornada}
					{$campoTransferido}
					{$campo_hd_classificacao}
			FROM tbl_hd_chamado
			JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
			LEFT JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
			LEFT JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_admin tbl_adminA on tbl_adminA.admin = tbl_hd_chamado.atendente
			LEFT JOIN tbl_admin tbl_adminB on tbl_adminB.admin = tbl_hd_chamado.admin
			LEFT JOIN tbl_admin tbl_adminC on tbl_adminC.admin = tbl_hd_chamado.sequencia_atendimento
			LEFT JOIN tbl_hd_situacao ON tbl_hd_situacao.hd_situacao = tbl_hd_chamado_extra.hd_situacao
			LEFT JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
			LEFT JOIN tbl_cliente_admin on tbl_cliente_admin.cliente_admin = tbl_hd_chamado.cliente_admin
			$left_os_hd
			LEFT JOIN tbl_produto ON tbl_hd_chamado_extra.produto = tbl_produto.produto
			LEFT JOIN tbl_hd_motivo_ligacao ON tbl_hd_chamado_extra.hd_motivo_ligacao = tbl_hd_motivo_ligacao.hd_motivo_ligacao AND tbl_hd_motivo_ligacao.fabrica = $login_fabrica
			LEFT JOIN tbl_hd_providencia ON tbl_hd_providencia.hd_providencia = tbl_hd_chamado_extra.hd_providencia
			AND tbl_hd_providencia.fabrica = {$login_fabrica}
			LEFT JOIN tbl_motivo_contato ON tbl_hd_chamado_extra.motivo_contato = tbl_motivo_contato.motivo_contato
			AND tbl_motivo_contato.fabrica = {$login_fabrica}
			{$leftJoinCockpit}
			{$joinRevenda}
			{$join_jornada}
			{$joinHdItem}
			{$join_hd_classificacao}
			WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
			AND   (tbl_hd_chamado.titulo isnull or (tbl_hd_chamado.titulo !~* 'help-desk' and tbl_hd_chamado.titulo !~* 'Atendimento Revenda'))
			AND ($cond1 $cond_site)
			$sql_pendente
			$cond_ext_posto
			$cond_2
			$cond_3
			$cond_4
			$cond_5
			$cond_6
			$cond_7
			$cond_8
			$cond_9
			$cond_10
			$cond_11
			$cond_admin_fale_conosco
			$cond_ret
			$cond_providencia
			$cond_origem
			$condProv3
			$condMotivoContato
			{$whereCockpit}
			{$cond_sem_helpdesk}
			{$cond_jornada}
			{$cond_hd_classificacao}
			{$cond_email_callcenter}
			$order_by";

			$res = pg_query($con,$sql);
	if(pg_num_rows($res)>0){
			echo "<br>";
			$table_cor = ($login_fabrica == 74) ? 'tablesorter ':'';

		$width = ($login_fabrica == 30) ? "900" : "700";
		echo "<table id='content_pendente' width='10%' class='$table_cor table table-bordered'>";
			echo "<THEAD>";
			if ($login_fabrica == 24) {
				?>
				<tr class='titulo_tabela'>
					<th>Chamados com Status de <?= ($_POST['status']) ? $_POST['status'] : "Pendente"?></th>
				</tr>
<?php
			}
			echo "<TR class='titulo_coluna'>\n";
				switch ($login_fabrica) {

					case 24:
						echo "<TH>Origem do Chamado</TH>\n";
						echo "<TH>Atendente Responsável</TH>\n";
						echo "<TH>Interventor</TH>\n";
						echo "<TH>Status</TH>\n";
						echo "<TH class='date_column'>Data Recebimento/Abertura</TH>\n";
						echo "<TH class='date_column'>Data Solução</TH>\n";
						echo "<TH class='date_column'>Ligação Agendada</TH>\n";
						echo "<TH>Nº Chamado</TH>\n";
						echo "<TH>Cliente</TH>\n";
						echo "<TH>Cidade</TH>\n";
					break;

					default:
						echo "<TH style='background-color:#596D9B;'><ACRONYM TITLE=\"NÚMERO ATENDIMENTO\">Atendimento</ACRONYM></TH>\n";
						if (in_array($login_fabrica,array(11,85,172))) {
							echo "<TH nowrap>Interventor</TH>\n";
						}
						if($login_fabrica == 161){
							echo "<TH style='background-color:#596D9B;'>Número de Série</TH>\n";
						}
						if($login_fabrica == 174){
							echo "<TH style='background-color:#596D9B;'>Aberto por</TH>\n";
						}
						
						echo "<TH style='background-color:#596D9B;'>Cliente</TH>\n";
						if(in_array($login_fabrica,[178,191])){
							echo "<TH style='background-color:#596D9B;'>Origem</TH>\n";
						}

						if ($classificacaoHD) {
							echo "<TH style='background-color:#596D9B;'>Classificação do atendimento:</TH>\n";
						}


						if ($login_fabrica == 50) {
							echo "<TH style='background-color:#596D9B;'>Tipo</TH>\n";
						}

						if($login_fabrica == 74){
							echo "<TH style='background-color:#596D9B;'>Revenda</TH>\n";
							echo "<TH style='background-color:#596D9B;'><ACRONYM TITLE=\"OS\">OS</ACRONYM></TH>\n";
							echo "<TH style='background-color:#596D9B;'><ACRONYM TITLE=\"Código do Posto\">Código do Posto</ACRONYM></TH>\n";
							echo "<TH style='background-color:#596D9B;'><ACRONYM TITLE=\"Posto\">Posto</ACRONYM></TH>\n";
						}
						if ($login_fabrica == 15) {
							echo "<TH style='background-color:#596D9B;'><ACRONYM TITLE=\"Defeito Reclamado\">Defeito Reclamado</ACRONYM></TH>\n";
						}
						if ($login_fabrica == 51) {
						echo "<TH style='background-color:#596D9B;'>Código do Posto</TH>\n";
						echo "<TH style='background-color:#596D9B;'>Posto</TH>\n";
						}
						if ($login_fabrica == 35) {
						echo "<TH style='background-color:#596D9B;'>Produto</TH>\n";
						}

						echo "<TH style='background-color:#596D9B;' class='date_column'>Abertura</TH>\n";
						if($login_fabrica == 7){
							echo "<TH style='background-color:#596D9B;'>Hora abertura</TH>\n";
						}
						echo "<TH style='background-color:#596D9B;' class='date_column'><ACRONYM TITLE=\"ÚLTIMA INTERAÇÃO\">Última Interação</ACRONYM></TH>\n";
						if ($login_fabrica == 15) {
							echo "<TH style='background-color:#596D9B;' class='date_column'><ACRONYM TITLE=\"Data Retorno\">Data Retorno</ACRONYM></TH>\n";
						}
						$uteis = $login_fabrica == 35 ? "" : " ÚTEIS" ;
						echo "<TH style='background-color:#596D9B;'><ACRONYM TITLE=\"QUANTIDADE DE DIAS$uteis ABERTO\">Dias Úteis em Aberto</ACRONYM></TH>\n";
						echo "<TH style='background-color:#596D9B;'><ACRONYM TITLE=\"QUANTIDADE DE DIAS$uteis DA ÚLTIMA INTERAÇÃO\">Dias Úteis Última Interação</ACRONYM></TH>\n";
						echo "<TH style='background-color:#596D9B;'>Status</TH>\n";

						/*HD - 4393005 */
						if ($login_fabrica == 174) {
							echo "<TH style='background-color:#596D9B;'>Transferido Por</TH>\n";							
						}

						if($login_fabrica == 74){
							echo "<TH style='background-color:#596D9B;'>Produto</TH>\n";
							echo "<TH style='background-color:#596D9B;'>Defeito</TH>\n";
						}

						echo "<TH style='background-color:#596D9B;'>Atendente</TH>\n";

						/* HD - 6165391 */
						if ($login_fabrica == 52) {
							echo "<TH style='background-color:#596D9B;'>Classificação do Atendimento</TH>\n";							
						}

						if ($login_fabrica == 11 or $login_fabrica == 172) {
							echo "<TH style='background-color:#596D9B;' class='date_column'>Data Programada</TH>\n";
							echo "<TH style='background-color:#596D9B;' class='date_column'>Data Providência</TH>\n";
						}
						if ($login_fabrica == 30) {
							echo "<TH style='background-color:#596D9B;' class='date_column'>Data Providência ao Cliente</TH>\n";
							echo "<TH style='background-color:#596D9B;' class='date_column'>Data Limite</TH>\n";
						}
						if( !in_array($login_fabrica, array(11,15,74,172)) && $moduloProvidencia){
							if($login_fabrica <> 115){
								$txt_prov = ($login_fabrica == 189) ? "Ação" : "Providência";

								echo "<TH style='background-color:#596D9B;'>{$txt_prov}</TH>\n";

								if (in_array($login_fabrica, [169,170])) {
									echo "<TH style='background-color:#596D9B;'>Providência nv. 3</TH>\n";
									echo "<TH style='background-color:#596D9B;'>Motivo Contato</TH>\n";
								}

								if($moduloProvidencia AND !in_array($login_fabrica,array(30))){
									echo "<TH style='background-color:#596D9B;' class='date_column'>Data {$txt_prov}</TH>\n";
								}
							}else{
								echo "<TH style='background-color:#596D9B;' class='date_column'>Data de Retorno</TH>\n";
							}
						}
						if($login_fabrica == 7){
							echo "<TH style='background-color:#596D9B;'>OS</TH>\n";
							echo "<TH style='background-color:#596D9B;'>DR</TH>\n";
							echo "<TH style='background-color:#596D9B;'>Número Contrato</TH>";
						}
						if($login_fabrica == 30){
							echo "<TH style='background-color:#596D9B;'>Código do Posto</TH>\n";
							echo "<TH style='background-color:#596D9B;'>Posto</TH>\n";
							echo "<TH style='background-color:#596D9B;'>OS</TH>\n";
						}
						if($login_fabrica == 74){
							echo "<TH style='background-color:#596D9B;'>UF</TH>\n";
							echo "<TH style='background-color:#596D9B;'><ACRONYM TITLE=\"DATA DA PROVIDÊNCIA\">DATA PROVIDÊNCIA</ACRONYM></TH>\n";
						}
						if($login_fabrica == 156){
							echo "<TH style='background-color:#596D9B;'>Tipo Atendimento</TH>\n";
						}

						if(in_array($login_fabrica, array(169,170))){
							echo "<th tyle='background-color:#596D9B;' >Origem</th>";
							echo "<th tyle='background-color:#596D9B;' > OS </th>";
							echo "<th tyle='background-color:#596D9B;' > Status OS </th>";
							echo "<th tyle='background-color:#596D9B;' > Status Jornada </th>";
						}
					break;

					}
			echo "</TR>\n";
			echo "</THEAD>";
			echo "<TBODY>";
			for($x=0;pg_num_rows($res)>$x;$x++){
				$callcenter             = pg_result($res,$x,hd_chamado);
				$titulo                 = pg_result($res,$x,titulo);
				$status                 = pg_result($res,$x,status);
				$categoria              = pg_result($res,$x,categoria);
				$data                   = pg_result($res,$x,data);
				$hora                   = pg_result($res,$x,hora);
				$data_maxima            = pg_result($res,$x,data_maxima);
				$data_interacao         = pg_result($res,$x,data_interacao);
				$cliente_nome           = pg_result($res,$x,cliente_nome);
				$posto_nome             = pg_result($res,$x,nome);
				$codigo_posto           = pg_result($res,$x,codigo_posto);
				$admin                  = pg_result($res,$x,admin);
				$nome_cidade            = pg_result($res,$x,nome_cidade);
				$interventor            = pg_result($res,$x,intervensor);
				$atendente              = pg_result($res,$x,atendente);
				$dias_aberto            = pg_result($res,$x,dias_aberto);
				$dias_ultima_interacao  = pg_result($res,$x,dias_ultima_interacao);
				$leitura_pendete        = pg_result($res,$x,leitura_pendente);
				$providencia            = pg_result($res,$x,providencia);
				$providencia_data       = pg_result($res,$x,providencia_data);
				$data_providencia       = pg_result($res,$x,'data_providencia');
				$providencia_motivo     = pg_result($res,$x,providencia_motivo);
				$sua_os 				= pg_result($res,$x,sua_os);
				$os 					= pg_result($res,$x,os);
				$nome_cliente_admin		= pg_result($res,$x,nome_cliente_admin);
				$uf 					= pg_result($res,$x,estado);
				$referencia				= pg_result($res,$x,'referencia');
				$produto_descricao		= pg_result($res,$x,'produto_descricao');
				$campos_adicionais_json	= pg_result($res,$x,campos_adicionais);
				$defeito_reclamado	 	= pg_result($res,$x,defeito_reclamado);
				$defeito 			 	= pg_result($res,$x,defeito);
				$cliente_admin 			= pg_result($res,$x,cliente_admin);
				$origem_consumidor 		= pg_fetch_result($res, $x, 'origem_consumidor');
				$nome_completo_admin    = pg_fetch_result($res, $x, 'nome_completo_admin');

				if ($login_fabrica == 52) {
					$hd_classificacao = pg_fetch_result($res, $x, 'hd_classificacao');
				}

				$descricao_classificacao = "";
				$transferido_por        = pg_fetch_result($res, $x, 'transferido_por');
				$motivo_contato_callcenter = pg_fetch_result($res, $x, 'descricao_motivo_contato');
            	$providencia_nivel3     = pg_fetch_result($res, $x, 'descricao_providencia');

				if ($classificacaoHD) {
					$hd_classificacao = pg_result($res,$x,hd_classificacao);
					if (strlen($hd_classificacao) > 0) {

						$sqlClassificacao = "SELECT hd_classificacao, descricao 
						                       FROM tbl_hd_classificacao 
						                      WHERE fabrica = $login_fabrica 
						                        AND hd_classificacao = $hd_classificacao 
						                        AND ativo IS TRUE ";
						$resClassificacao = pg_query($con,$sqlClassificacao);
						$descricao_classificacao = pg_fetch_result($resClassificacao, 0, 'descricao');
					}
				}

				if ($login_fabrica == 74) {
					$nome_revenda 			= pg_result($res,$x,nome_revenda);
				}

				if($login_fabrica == 161){
					$numero_serie 			= pg_result($res,$x,serie);
				}
				if (($login_fabrica == 15 or $login_fabrica == 74) AND strlen(trim($defeito_reclamado)) > 0) {

					$sqlx="select descricao from  tbl_defeito_reclamado where defeito_reclamado = '$defeito_reclamado';";
					$resx=pg_query($con,$sqlx);
					$xdefeito_reclamado         = strtoupper(trim(pg_fetch_result($resx, 0, 'descricao')));
				}

				$campos_adicionais = json_decode($campos_adicionais_json);
				$numero_contrato = $campos_adicionais->numero_contrato;

				if($login_fabrica == 24){
					$campos_adicionais = json_decode($campos_adicionais_json, true);

					if (array_key_exists("ligacao_agendada", $campos_adicionais) && (strlen($campos_adicionais["ligacao_agendada"]) > 0) ){
						list($laa, $lam, $lad) = explode("-", $campos_adicionais["ligacao_agendada"]);
						if(checkdate($lam, $lad, $laa)){
							$ligacao_agendada = "{$lad}/{$lam}/{$laa}";
						}else{
							$ligacao_agendada = "<div style='display: none;'>00/00/0000</div>";
						}
					} else {
						$ligacao_agendada = "<div style='display: none;'>00/00/0000</div>";
					}
				}
				if ($x % 2 == 0){
					$xcor = $cor = '#F1F4FA';
				}else{
					$xcor = $cor = '#F7F5F0';
				}

                if(!$moduloProvidencia){
                    if($dias_ultima_interacao == 2){
                        $cor = '#FABD6B';
                    }
                    if($dias_ultima_interacao >= 3){
                        $cor = '#F8615C';
                    }
				}

                if ($login_fabrica == 30) {

					if ($dias_aberto > 20 AND $dias_aberto <= 30) {
						$cor = '#FABD6B';
					}

					if ($dias_aberto > 30) {
						$cor = '#F8615C';
					}
				}

				$data_providencia = substr($data_providencia, 0, 10);
                if ($login_fabrica == 74) {
                   $data_prov = $data_providencia;
                }
				$data_providencia_linha_amarela = substr($data_providencia, 0, 10);
				if(strlen(trim($data_providencia))>0){
					list($pd, $pm, $pa) = explode("-", $data_providencia);
					$data_providencia = "{$pa}/{$pm}/{$pd}";
				}

		    	if($moduloProvidencia AND !in_array($login_fabrica,array(30,11,172))){
                    $date = date('Y-m-d');
                    // echo strtotime($data_providencia_linha_amarela)."(".$data_providencia_linha_amarela.") -- ".strtotime($date)."[".$date."]<br>";
                    if(strlen(trim($providencia_data)) > 0 and (strtotime($data_providencia_linha_amarela) < strtotime($date) ) ) {
                           $cor = "#F8615C";
                    } else if(strlen(trim($providencia_data)) > 0 && (strtotime($data_providencia_linha_amarela) == strtotime($date)) && ($moduloProvidencia AND !in_array($login_fabrica,array(30)))){
                        $cor = "#91C8FF";
                    } else if(strlen(trim($providencia_data)) > 0 && (strtotime($data_providencia_linha_amarela) > strtotime($date) || strlen($data_providencia_linha_amarela) == 0) && ($moduloProvidencia AND !in_array($login_fabrica,array(30)))){
                        $cor = "#F1F4FA";
                    }

				}

				if ($login_fabrica == 74) {
                    if (empty($data_prov)) {
                        $cor = '#F7F5F0';
                        if ($x % 2 == 0) {
                            $cor = '#F1F4FA';
                        }
                    } else {
                        $date = date('Y-m-d');

                        $dp = new DateTime($data_prov);
                        $hj = new DateTime($date);

						if ($dp <= $hj) {
							$cor = $vHoje;

							$hj->sub(new DateInterval('P02D'));

							if ($dp == $hj) {
								$cor = $v2dias;
							} else {
								$hj->sub(new DateInterval('P01D'));

								if ($dp <= $hj) {
									$cor = $v3dias;
								}
							}
						} else {
							$cor = '#F7F5F0';
							if ($x % 2 == 0) {
								$cor = '#F1F4FA';
							}
						}
                    }
				}

				if ($login_fabrica == 51 and $leitura_pendete == "t"){
					$cor = '#91C8FF';
				}

				if($login_fabrica == 85){

                    if (!empty($os)) {
                        $sql_os_finalizada = "
                            SELECT  data_fechamento,
                                    CASE WHEN tbl_os.data_digitacao_fechamento < (tbl_hd_chamado.data + INTERVAL '36 hours')
                                            THEN 'abaixo'
                                            ELSE 'acima'
                                    END  AS fechada36hrs
                            FROM    tbl_hd_chamado
                            JOIN    tbl_hd_chamado_extra USING(hd_chamado)
                            JOIN    tbl_os USING(os)
                            WHERE   os = $os";
                        $res_os_finalizada = pg_query($con, $sql_os_finalizada);
                        if(pg_num_rows($res_os_finalizada) > 0){
                            $data_fechamento = pg_fetch_result($res_os_finalizada, 0, 'data_fechamento');
                            $fechada36hrs = pg_fetch_result($res_os_finalizada, 0, 'fechada36hrs');
                            if(!empty($data_fechamento)){
                                    $cor = ($fechada36hrs == "acima") ? '#81F781' : "#9370DB";
                            }
                        }
                    }
                }

				if (($login_fabrica == 11 or $login_fabrica == 172) && strlen($campos_adicionais->data_programada) > 0) {
					list($dpd, $dpm, $dpa) = explode("/", $campos_adicionais->data_programada);
					$aux_data_programada = "{$dpa}-{$dpm}-{$dpd}";
					$aux_data_hoje       = date("Y-m-d");

					if (strtotime($aux_data_programada) < strtotime($aux_data_hoje)) {
						$cor = '#BA8DFF';
					}
				}

				if ($login_fabrica == 30 && strlen($data_providencia) > 0) {
					$aux_data_hoje = date("Y-m-d");
					list($dpd, $dpm, $dpa) = explode("/", $data_providencia);
					$aux_data_providencia = "{$dpa}-{$dpm}-{$dpd}";
					if (strtotime($aux_data_providencia) < strtotime($aux_data_hoje)) {
						$cor = '#BA8DFF';
					}
				}

				if ($login_fabrica == 15 && strlen($campos_adicionais->data_retorno) > 0) {
					list($dra, $drm, $drd) = explode("-", $campos_adicionais->data_retorno);
					$campos_adicionais->data_retorno = "{$drd}/{$drm}/{$dra}";
				}

				if ($login_fabrica == 15 && strtoupper($status) == "RESOLVIDO") {
					$cor = $xcor;
				}

				if (in_array($login_fabrica, array(169,170))){
					$jornada 			= pg_fetch_result($res, $x, 'jornada');
					$status_os 			= pg_fetch_result($res, $x, 'status_os');
					$status_jornada		= pg_fetch_result($res, $x, 'status_jornada');

					if (strlen(trim($status_jornada)) > 0){
						if ($status_jornada <= '15'){
							$texto_jornada = "0 - 15 Dias";
								$cor = '#33cc33';
						}else if ($status_jornada > '15' AND $status_jornada <= '25'){
							$texto_jornada = "16 - 25 Dias";
								$cor = '#ffff00';
						}else if ($status_jornada  > '25'){
							$texto_jornada = "Acima de 26 Dias";
								$cor = '#ff0000';
						}
					}else{
						$texto_jornada = "";
						$cor = $cor;
					}
				}



				echo "<TR bgcolor='$cor' onmouseover=\"this.bgColor='#F0EBC8'\" onmouseout=\"this.bgColor='$cor'\">\n";

				switch ($login_fabrica) {

					case 24:
						echo "<TD class='linha' align='center' nowrap>$admin</TD>\n";
						echo "<TD class='tal' align='center'>$atendente</TD>\n";
						echo "<TD class='tac' align='center'>$interventor</TD>\n";
						echo "<TD class='tac' align='center'>$status</TD>\n";

						echo "<TD class='tac' align='center'>$data</TD>\n";
						echo "<TD class='tac' align='center'>$data_maxima</TD>\n";
						echo "<TD class='tac' align='center'>$ligacao_agendada</TD>\n";
						echo "<TD class='tac' align='center'><a href='callcenter_interativo_new.php?callcenter=$callcenter' target='_blank'>$callcenter</a></TD>\n";
						echo "<TD class='tal' align='center' nowrap>" . substr($cliente_nome,0,30) . "</TD>\n";
						echo "<TD class='tal' align='center' nowrap>$nome_cidade</TD>\n";
					break;
					default:
						echo "<TD style='background-color:$cor;' class='linha' align='center' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha' style='color:#596d9b;font-weight:bold;' target='_blank'>$callcenter</a></TD>\n";
						if ($login_fabrica == 11 or $login_fabrica == 172 OR $login_fabrica == 85) {
							echo "<TD style='background-color:$cor;' class='linha' nowrap>$interventor</TD>\n";
						}
						if($login_fabrica == 161){
							echo "<TD style='background-color:$cor;' class='linha'  align='left' nowrap>$numero_serie</TD>\n";
						}
						if($login_fabrica == 174){
							echo "<TD style='background-color:$cor;' class='linha'  align='left' nowrap>$admin</TD>\n";
						}

						echo "<TD style='background-color:$cor;' class='linha'  align='left' nowrap>".substr($cliente_nome,0,17)."</TD>\n";

						if(in_array($login_fabrica,[178,191])){
							echo "<TD style='background-color:$cor;' class='linha'  align='left' nowrap>$origem_consumidor</TD>\n";
						}

						if ($classificacaoHD) {
							echo "<TD style='background-color:$cor;' class='linha'  align='left' nowrap>$descricao_classificacao</TD>\n";
						}
						if ($login_fabrica == 50) {
						echo "<TD style='background-color:$cor;' class='linha'  align='left' nowrap>{$categoria}</TD>\n";
						}

						if($login_fabrica == 74){
							echo "<TD style='background-color:$cor;' class='linha'>{$nome_revenda}</TD>\n";
							echo "<TD style='background-color:$cor;' class='linha'>{$os}</TD>\n";
							echo "<TD style='background-color:$cor;' class='linha'>{$codigo_posto}</TD>\n";
							echo "<TD style='background-color:$cor;' class='linha'>{$posto_nome}</TD>\n";
						}
						if ($login_fabrica == 15) {
							echo "<TD style='background-color:$cor;' class='linha'  align='left' nowrap>$xdefeito_reclamado</TD>\n";
						}
						if ($login_fabrica == 51) {
							echo "<TD style='background-color:$cor;' class='linha'  align='left' nowrap>".$codigo_posto." </TD>\n";
							echo "<TD style='background-color:$cor;' class='linha'  align='left' nowrap>".substr($posto_nome,0,30)."</TD>\n";
						}
						if ($login_fabrica == 35) {
							echo "<TD style='background-color:$cor;' class='linha'  align='left' nowrap>$referencia</TD>\n";
						}

						echo "<TD style='background-color:$cor;' class='linha' nowrap>$data</TD>\n";
						if($login_fabrica == 7){
							echo "<TD style='background-color:$cor;' class='linha' nowrap>$hora</TD>\n";
						}
						echo "<TD style='background-color:$cor;' class='linha tac' align=center nowrap>$data_interacao</TD>\n";
						if ($login_fabrica == 15) {
							echo "<TD style='background-color:$cor;' class='linha' align=center nowrap>{$campos_adicionais->data_retorno}</TD>";
						}
						echo "<TD style='background-color:$cor;' class='linha tac' align=center nowrap>$dias_aberto</TD>";
						echo "<TD style='background-color:$cor;' class='linha tac' align=center nowrap>$dias_ultima_interacao</TD>";

						echo "<TD style='background-color:$cor;' class='linha' align=center nowrap>$status</TD>";

						/*HD - 4393005*/
						if ($login_fabrica == 174) {

							echo "<TD style='background-color:$cor;' class='linha' align=center nowrap>$transferido_por</TD>";							
						}

						if($login_fabrica == 74){
							echo "<TD style='background-color:$cor;' class='linha' align=center nowrap>$referencia - $produto_descricao</TD>";
							echo "<TD style='background-color:$cor;' class='linha' align=center nowrap>$xdefeito_reclamado</TD>";
						}

						echo "<TD style='background-color:$cor;' class='linha' width=85 align=center>$atendente</TD>";

						/* HD - 6165391 */
						if ($login_fabrica == 52) {
							echo "<TD style='background-color:$cor;' class='linha' width=85 align=center>$hd_classificacao</TD>";
						}

						if ($login_fabrica == 11 or $login_fabrica == 172) {
							echo "<TD style='background-color:$cor;' class='linha' width=85 align=center>{$campos_adicionais->data_programada}</TD>";
							echo "<TD style='background-color:$cor;' class='linha' width=85 align=center>{$data_providencia}</TD>";
						}

						if ($login_fabrica == 30) {

							if (!$campos_adicionais->data_limite) {
								$data_limite = str_replace("\\","",$campos_adicionais->data_limite_cb);
							} else {
								$data_limite = $campos_adicionais->data_limite;
							}

							echo "<TD style='background-color:$cor;' class='linha' width='85' align=center>{$data_providencia}</TD>";
							echo "<TD style='background-color:$cor;' class='linha' align='center'>&nbsp;&nbsp;{$data_limite}&nbsp;&nbsp; </TD>";
						}

						if((!in_array($login_fabrica,array(15)) AND !$moduloProvidencia) OR ($moduloProvidencia)){
							if(!in_array($login_fabrica, [74,198])){
								if ($login_fabrica == 115) {
									echo "<TD style='background-color:$cor;' class='linha' align=center>{$providencia_data}</TD>";
								}elseif($login_fabrica == 30) {
									echo "<TD style='background-color:$cor;' class='linha' align=center>".substr($providencia_motivo,0,60)."</TD>";
								}elseif($login_fabrica <> 151 and $moduloProvidencia) {

									if (!in_array($login_fabrica, array(90,125,164,166,169,170,174,183,186,189))) {
									echo "<TD style='background-color:$cor;' class='linha' align=center>".substr($providencia,0,60)."</TD>";
									}
								}
							}
						}


						if($moduloProvidencia and $login_fabrica <> 30){
							if($login_fabrica <> 35) {
								echo "<TD style='background-color:$cor;' class='linha' align=center nowrap>".substr($providencia_motivo,0,60)."</TD>";

							if (in_array($login_fabrica, [169,170])) {
								echo "<TD style='background-color:$cor;' class='linha' align=center>".substr($providencia_nivel3,0,60)."</TD>";
								echo "<TD style='background-color:$cor;' class='linha' align=center>".substr($motivo_contato_callcenter,0,60)."</TD>";
							}


							}
							if($login_fabrica != 30){
								echo "<TD style='background-color:$cor;' class='linha' width=85 align=center>$providencia_data</TD>";
							}
                        }

						if($login_fabrica == 7){
							echo "<TD style='background-color:$cor;' class='linha' width=85 align=center>$os</TD>";
							echo "<TD style='background-color:$cor;' class='linha' width=85 align=center>$nome_cliente_admin</TD>";
							echo "<TD style='background-color:$cor;' class='linha' width=85 align=center>$numero_contrato</TD>";
						}
						if($login_fabrica == 30){
							echo "<TD style='background-color:$cor;' class='linha'>{$codigo_posto}</TD>\n";
							echo "<TD style='background-color:$cor;' class='linha'>{$posto_nome}</TD>\n";
							echo "<TD style='background-color:$cor;' class='linha'><a href='os_press.php?os=$os' target='_blank'>{$sua_os}</a></TD>\n";
						}
						if($login_fabrica == 74){
							echo "<TD style='background-color:$cor;' class='linha' width=85 align=center>$uf</TD>\n";
							echo "<TD style='background-color:$cor;' class='linha' width=85 align=center>$providencia_data</TD>";
						}
						if($login_fabrica == 156){
							$tipo_atendimento = (strlen($cliente_admin) > 0) ? "Cliente Admin" : "Normal";
							echo "<TD style='background-color:$cor;' class='linha'align=center>$tipo_atendimento</TD>\n";
						}

						if(in_array($login_fabrica, array(169,170))){
							echo "<td style='background-color:$cor;' class='linha' align=center >$origem_consumidor</td>";
							#if ($jornada == 't'){
								echo "<td style='background-color:$cor;' class='linha' align=center >$sua_os</td>";
								echo "<td style='background-color:$cor;' class='linha' align=center >$status_os</td>";
								echo "<td style='background-color:$cor;' class='linha' align=center >$texto_jornada</td>";
							#}
						}
						echo "\n";
					break;
				}
				echo "</TR>\n";
			}
			echo "</TBODY>";
		echo "</table>";

		flush();

	}else{

			echo "<div class='alert alert-warning' style='left: 18%;width: 60%;position: relative;'><h4>Nenhum chamado encontrado</h4></div>";
	}

		if($login_fabrica == 24) {

			$sql_pendente = " AND tbl_hd_chamado.status = 'Aberto' ";

			$sql = "SELECT  tbl_hd_chamado.hd_chamado,
					tbl_hd_chamado.titulo,
					tbl_hd_chamado.status,
					tbl_hd_chamado.sequencia_atendimento,
					to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data,
					to_char(tbl_hd_chamado.data+ INTERVAL '5 DAYS','DD/MM/YYYY') AS data_maxima,
					tbl_hd_chamado.categoria,
					tbl_hd_chamado_extra.nome as cliente_nome,
					tbl_adminA.login as atendente,
					tbl_adminB.login as admin,
					tbl_adminC.login as intervensor,
					(select to_char(tbl_hd_chamado_item.data,'DD/MM/YYYY')
						from tbl_hd_chamado_item
					where tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
						and tbl_hd_chamado_item.interno is not true order by data desc limit 1) as data_interacao ,
					tbl_hd_chamado_extra.dias_aberto,
					tbl_hd_chamado_extra.dias_ultima_interacao,
					tbl_hd_chamado_extra.leitura_pendente,
					tbl_cidade.nome as nome_cidade,
					tbl_posto.nome,
					tbl_posto_fabrica.codigo_posto,
					to_char(tbl_hd_chamado.data_providencia,'DD/MM/YYYY') AS providencia_data,
					tbl_hd_situacao.descricao AS providencia
			FROM tbl_hd_chamado
			JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
			LEFT JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
			LEFT JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_admin tbl_adminA on tbl_adminA.admin = tbl_hd_chamado.atendente
			LEFT JOIN tbl_admin tbl_adminB on tbl_adminB.admin = tbl_hd_chamado.admin
			LEFT JOIN tbl_admin tbl_adminC on tbl_adminC.admin = tbl_hd_chamado.sequencia_atendimento
			LEFT JOIN tbl_hd_situacao ON tbl_hd_situacao.hd_situacao = tbl_hd_chamado_extra.hd_situacao
			LEFT JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
			WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
			AND UPPER(tbl_hd_chamado.status) <> 'RESOLVIDO' and UPPER(tbl_hd_chamado.status) <> 'CANCELADO'
			AND ($cond1 $cond_site)
			$sql_pendente
			$cond_2
			$cond_3
			$cond_4
			$cond_5
			$cond_6
			$cond_7
			$cond_8
			$cond_9
			$cond_10
			ORDER BY tbl_hd_chamado.hd_chamado DESC";
	$res = pg_query($con,$sql);
	//echo nl2br($sql);
	//exit;

	if(pg_num_rows($res)>0){

		echo "<br /><table class='table table-bordered' width='10%' id='content_status'>";
			echo "<thead>";
			if ($login_fabrica == 24) {
			?>
				<tr class='titulo_tabela'>
					<th>Chamados com Status Aberto</th>
				</tr>
			<?
			}
			echo "<TR class='titulo_coluna'>\n";
				switch ($login_fabrica) {

					case 24:
					case 85:
						echo "<th class='menu_top'>Origem do Chamado</TH>\n";
						echo "<TH class='menu_top'>Atendente Responsável</TH>\n";
						echo "<TH class='menu_top'>Interventor</TH>\n";
						echo "<TH class='menu_top'>Status</TH>\n";
						echo "<TH class='date_column'>Data Recebimento/abertura</TH>\n";
						echo "<TH class='date_column'>Data Solução</TH>\n";
						echo "<TH class='menu_top'>Nº Chamado</TH>\n";
						echo "<TH class='menu_top'>Cliente</TH>\n";
						echo "<TH class='menu_top'>Cidade</TH>\n";
					break;

					default:
						echo "<th class='menu_top'><ACRONYM TITLE=\"NÚMERO ATENDIMENTO\">AT.</ACRONYM></TH>\n";
						echo "<TH class='menu_top'>CLIENTE</TH>\n";
						if ($login_fabrica == 51) {
						echo "<TH class='menu_top'>CÓDIGO DO POSTO</TH>\n";
						echo "<TH class='menu_top'>POSTO</TH>\n";
						}
						echo "<TH class='menu_top'>ABERTURA</TH>\n";
						echo "<TH class='menu_top'><ACRONYM TITLE=\"ÚLTIMA INTERAÇÃO\">ÚLT.INTER</ACRONYM></TH>\n";
						echo "<TH class='menu_top'><ACRONYM TITLE=\"QUANTIDADE DE DIAS ÚTEIS ABERTO\">DIAS AB.</ACRONYM></TH>\n";
						echo "<TH class='menu_top'><ACRONYM TITLE=\"QUANTIDADE DE DIAS ÚTEIS DA ÚLTIMA INTERAÇÃO\">Dias Úteis Última Interação</ACRONYM></TH>\n";
						echo "<TH class='menu_top'>STATUS</TH>\n";
						echo "<TH class='menu_top'>Atendente</TH>\n";
						echo "<TH class='menu_top'>Providência</TH>\n";
						echo "<TH class='menu_top'><ACRONYM TITLE=\"DATA DA PROVIDÊNCIA\">DTA.PRO.</ACRONYM></TH>\n";
					break;

					}
			echo "</TR></thead>";
			for($x=0;pg_num_rows($res)>$x;$x++){
				$callcenter             = pg_result($res,$x,hd_chamado);
				$titulo                 = pg_result($res,$x,titulo);
				$status                 = pg_result($res,$x,status);
				$categoria              = pg_result($res,$x,categoria);
				$data                   = pg_result($res,$x,data);
				$data_maxima            = pg_result($res,$x,data_maxima);
				$data_interacao         = pg_result($res,$x,data_interacao);
				$cliente_nome           = pg_result($res,$x,cliente_nome);
				$posto_nome             = pg_result($res,$x,nome);
				$codigo_posto           = pg_result($res,$x,codigo_posto);
				$admin                  = pg_result($res,$x,admin);
				$nome_cidade            = pg_result($res,$x,nome_cidade);
				$interventor            = pg_result($res,$x,intervensor);
				$atendente              = pg_result($res,$x,atendente);
				$dias_aberto            = pg_result($res,$x,dias_aberto);
				$dias_ultima_interacao  = pg_result($res,$x,dias_ultima_interacao);
				$leitura_pendete        = pg_result($res,$x,leitura_pendente);
				$providencia            = pg_result($res,$x,providencia);
				$providencia_data       = pg_result($res,$x,providencia_data);
				if ($x % 2 == 0){
					$cor = '#F1F4FA';
				}else{
					$cor = '#e6eef7';
				}

				if (!in_array($login_fabrica,array(30))) {
					if ($dias_ultima_interacao == "2") {
						$cor = '#FABD6B';
					}
					if ($dias_ultima_interacao >= "3") {
						$cor = '#F8615C';
					}
				}

				if ($login_fabrica == 30) {
					if ($dias_ultima_interacao > 20 AND $dias_ultima_interacao <= 30) {
						$cor = '#FABD6B';
					}

					if ($dias_ultima_interacao > 30) {
						$cor = '#F8615C';
					}
				}

				if ($login_fabrica == 51 and $leitura_pendete == "t"){
					$cor = '#91C8FF';
				}

				if($login_fabrica == 85){

					if(!empty($os)){
						$sql_os_finalizada = "SELECT data_fechamento FROM tbl_os WHERE os = $os";
						$res_os_finalizada = pg_query($con, $sql_os_finalizada);
						if(pg_num_rows($res_os_finalizada) > 0){
							$data_fechamento = pg_fetch_result($res_os_finalizada, 0, 'data_fechamento');
							if(!empty($data_fechamento)){
								$cor = '#81F781';

							}
						}
					}
				}

				echo "<TR bgcolor='$cor' onmouseover=\"this.bgColor='#F0EBC8'\" onmouseout=\"this.bgColor='$cor'\">\n";

				switch ($login_fabrica) {

					case 24:
					case 85:
						echo "<TD class='tal' align='center'>$admin</TD>\n";
						echo "<TD class='tal' align='center'>$atendente</TD>\n";
						echo "<TD class='tac' align='center' nowrap>$interventor</TD>\n";
						echo "<TD class='tal' align='center' nowrap>$status</TD>\n";
						echo "<TD class='tac' align='center' nowrap>$data</TD>\n";
						echo "<TD class='tac' align='center' nowrap>$data_maxima</TD>\n";
						echo "<TD class='tac' align='center' nowrap><a href='callcenter_interativo_new.php?callcenter=$callcenter' target='_blank'>$callcenter</a></TD>\n";
						echo "<TD class='tal' align='center' nowrap>$cliente_nome</TD>\n";
						echo "<TD class='tal' align='center' nowrap>$nome_cidade</TD>\n";
					break;

				}
				echo "</TR>\n";
			}
				echo "</table>";

			?>
				<script>
					$(".alert-warning").hide();
				</script>
			<?
			}

		}

	if($gerar_xls=='t'){
		$conteudo = ob_get_contents();//PEGA O CONTEUDO EM BUFFER
		ob_end_clean();//LIMPA O BUFFER

		$arquivo_nome = "relatorio_callcenter_pendente_$login_fabrica.$login_admin".date('YmdHis').".xls";
		$path         = "xls/";
		$path_tmp     = "/tmp/";

		$arquivo_completo     = $path.$arquivo_nome;
		$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

		$file = fopen($arquivo_completo_tmp, 'w');

		if ($telecontrol_distrib || in_array($login_fabrica, [174])) {

			$cabecalho = "
						<table>
							<thead>
							<tr>
								<th>Atendimento</th>
								";

						if($login_fabrica == 174){

							$cabecalho .= "<TH>Aberto por</TH>";

						}

						$cabecalho .= "
								<th>Cliente</th>
								<th>Abertura</th>
								<th>Última Interação</th>
								<th>Dias Úteis Última Interação</th>";

						if ($login_fabrica == 174) {

							$cabecalho .= "<th>Transferido Por</th>";

						}

							$cabecalho .="
								<th>Status</th>
								<th>Atendente</th>";

						if ($login_fabrica == 174) {

							$cabecalho .= "
								<th>Providência</th>
								<th>Data da Providência</th>";

						}

							$cabecalho .= "
							</tr>
							</thead>
							<tbody>";

			fwrite($file, $cabecalho);

			while ($dadosAt = pg_fetch_object($res)) {

				$bodyExcel .= "<tr>
								<td>{$dadosAt->hd_chamado}</td>";

							if($login_fabrica == 174){

								$bodyExcel .= "<TD>{$dadosAt->admin}</TD>";

							}

							$bodyExcel .= "
								<td>{$dadosAt->cliente_nome}</td>
								<td>{$dadosAt->data}</td>
								<td>{$dadosAt->data_interacao}</td>
								<td>{$dadosAt->dias_ultima_interacao}</td>";

							if ($login_fabrica == 174) {

								$bodyExcel .= "<TD>{$dadosAt->transferido_por}</TD>";

							}

							$bodyExcel .= "
								<td>{$dadosAt->status}</td>
								<td>{$dadosAt->atendente}</td>";

							if ($login_fabrica == 174) {
								$bodyExcel .= "<td>{$dadosAt->providencia_motivo}</td>
											   <td>{$dadosAt->providencia_data}</td>";
							}	

							$bodyExcel .= "
							</tr>";

			}

			$bodyExcel .= "</tbody>
					</table>";

			fwrite($file, $bodyExcel);

		} else {
			fwrite($file, $conteudo);
		}
		
		fclose($file);

		system("cp $arquivo_completo_tmp $path");//COPIA ARQUIVO PARA DIR XLS

		echo $conteudo;
		echo '<br />';

	echo "<br /> <br />

	<a href='../admin/xls/".$arquivo_nome."' target='_blank'>
		<div class='btn_excel'>
			<span>
				<img src='imagens/excel.png' />
			</span>
			<span class='txt'>Download em Excel</span>
		</div>
	</a><br />";
	}
}
if ((strlen($bi_latina) > 0 ) AND (pg_num_rows($resSubmit) > 0)) {
	$data = date("d-m-Y-H:i");
    $fileName = "BI-DE-ATENDIMENTOS-{$data}.xls";
    $file = fopen("/tmp/{$fileName}", "w");

    fwrite($file, "
	    <table border='1' id='content'>
                    <thead>
                        <tr>
                           	<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Atendimento</th>
                           	<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Admin responsavel</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Consumidor</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Endereço</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Complemento</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Bairro</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CEP</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Telefone</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Telefone comercial</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Celular</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Numero residencial</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Email</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CPF</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>RG</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Origem consumidor</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Tipo Consumidor</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Melhor horario p/ contato</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cidade</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Estado</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Receber Informações da Fábrica</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Produto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Referencia</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Voltagem</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nota Fiscal</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data da Nota Fiscal</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Serie do produto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nome do posto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CNPJ do posto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Telefone do posto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Email do Posto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>OS</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Aba Selecionada</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Abre pre-os</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Reclamacao</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Descricao</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nome da revenda</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CNPJ da revenda</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data de Abertura</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Status</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data para Retorno</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Atendente atual</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data de finalização</th>
                        </tr>
                    </thead>
                    <tbody>
                ");
	for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
            $hd_chamado = pg_fetch_result($resSubmit, $i,'hd_chamado');
            $nome_admin = pg_fetch_result($resSubmit, $i,'nome_admin');
			$nome_consumidor = pg_fetch_result($resSubmit, $i,'nome_consumidor');
			$endereco_consumidor = pg_fetch_result($resSubmit, $i,'endereco_consumidor');
			$complemento_consumidor = pg_fetch_result($resSubmit, $i,'complemento_consumidor');
			$bairro_consumidor = pg_fetch_result($resSubmit, $i,'bairro_consumidor');
			$cep_consumidor = pg_fetch_result($resSubmit, $i,'cep_consumidor');
			$fone1_consumidor = pg_fetch_result($resSubmit, $i,'fone1_consumidor');
			$fone_comercial = pg_fetch_result($resSubmit, $i,'fone_comercial');
			$celular_consumidor = pg_fetch_result($resSubmit, $i,'celular_consumidor');
			$numero_consumidor = pg_fetch_result($resSubmit, $i,'numero_consumidor');
			$email_consumidor = pg_fetch_result($resSubmit, $i,'email_consumidor');
			$cpf_consumidor = pg_fetch_result($resSubmit, $i,'cpf_consumidor');
			$rg_consumidor = pg_fetch_result($resSubmit, $i,'rg_consumidor');
			$origem_consumidor = pg_fetch_result($resSubmit, $i,'origem_consumidor');
			$tipo_consumidor = pg_fetch_result($resSubmit, $i,'tipo_consumidor');
			$hora_ligacao = pg_fetch_result($resSubmit, $i,'hora_ligacao');
			$cidade = pg_fetch_result($resSubmit, $i,'cidade');
			$estado = pg_fetch_result($resSubmit, $i,'estado');
			$informacoes_fabrica = pg_fetch_result($resSubmit, $i,'informacoes_fabrica');
			$produto_descricao = pg_fetch_result($resSubmit, $i,'produto_descricao');
			$produto_referencia = pg_fetch_result($resSubmit, $i,'produto_referencia');
			$produto_voltagem	 = pg_fetch_result($resSubmit, $i,'produto_voltagem');
			$produto_nf = pg_fetch_result($resSubmit, $i,'produto_nf');
			$produto_data_nf = pg_fetch_result($resSubmit, $i,'produto_data_nf');
			$produto_serie	 = pg_fetch_result($resSubmit, $i,'produto_serie');
			$nome_fantasia = pg_fetch_result($resSubmit, $i,'nome_fantasia');
			$cnpj_posto = pg_fetch_result($resSubmit, $i,'cnpj_posto');
			$telefone_posto = pg_fetch_result($resSubmit, $i,'telefone_posto');
			$email_posto = pg_fetch_result($resSubmit, $i,'email_posto');
			$os = pg_fetch_result($resSubmit, $i,'sua_os');
			$aba_callcenter = pg_fetch_result($resSubmit, $i,'aba_callcenter');
			$pre_os = pg_fetch_result($resSubmit, $i,'pre_os');
			$aba_reclamacao = pg_fetch_result($resSubmit, $i,'aba_reclamacao');
			$descricao = pg_fetch_result($resSubmit, $i,'descricao');
			$nome_revenda = pg_fetch_result($resSubmit, $i,'nome_revenda');
			$cnpj_revenda = pg_fetch_result($resSubmit, $i,'cnpj_revenda');
			$data_abertura = pg_fetch_result($resSubmit, $i,'data_abertura');
			$status = pg_fetch_result($resSubmit, $i,'status');
			$atendente = pg_fetch_result($resSubmit, $i,'atendente');
			$data_finalizacao = pg_fetch_result($resSubmit, $i,'data_finalizacao');
			$array_campos_adicionais  = pg_fetch_result($resSubmit,$i,'array_campos_adicionais');
			$defeito_reclamado  = pg_fetch_result($resSubmit,$i,'defeito_reclamado');

			if ($login_fabrica == 15 AND strlen(trim($defeito_reclamado)) > 0) {

				$sqlx="select descricao from  tbl_defeito_reclamado where defeito_reclamado = '$defeito_reclamado';";
				$resx=pg_query($con,$sqlx);
				$xdefeito_reclamado         = strtoupper(trim(pg_fetch_result($resx, 0, 'descricao')));
			}

	if (isset($tipo_consumidor)){
		if ($tipo_consumidor == 'C'){
			$tipo_consumidor = 'Consumidor';
		}else{
			$tipo_consumidor = 'Revenda';
		}
	}else{
		$tipo_consumidor = '';
	}
	if (isset($informacoes_fabrica)){
		if ($informacoes_fabrica == 't'){
			$informacoes_fabrica = 'Sim';
		}else{
			$informacoes_fabrica = 'Não';
		}
	}else{
		$informacoes_fabrica = '';
	}
	if (isset($pre_os)){
		if ($pre_os == 't'){
			$pre_os = 'Sim';
		}else{
			$pre_os = 'Não';
		}
	}else{
		$pre_os = '';
	}
	if (($produto_nf == 'NULL')OR ($produto_nf == 'null')){
		$produto_nf = '';
	}
	$array_campos_adicionais = json_decode($array_campos_adicionais, true);
	extract($array_campos_adicionais, EXTR_OVERWRITE);
	if ((strlen($data_retorno) > 0) AND ($login_fabrica == 15)) {
		 $data_retorno = $array_campos_adicionais['data_retorno'];
		if ((strlen($data_retorno) > 0) AND ($login_fabrica == 15)) {
			list($dmsa, $dmsm, $dmsd) = explode("-", $data_retorno);
			$data_retorno = "{$dmsd}/{$dmsm}/{$dmsa}";
		}
	}
        fwrite($file, "
                <tr>
					<td nowrap align='center'>{$hd_chamado}</td>
					<td nowrap align='center'>{$nome_admin}</td>
					<td nowrap align='center'>{$nome_consumidor}</td>
					<td nowrap align='center'>{$endereco_consumidor}</td>
					<td nowrap align='center'>{$complemento_consumidor}</td>
					<td nowrap align='center'>{$bairro_consumidor}</td>
					<td nowrap align='center'>{$cep_consumidor}</td>
					<td nowrap align='center'>{$fone1_consumidor}</td>
					<td nowrap align='center'>{$fone_comercial}</td>
					<td nowrap align='center'>{$celular_consumidor}</td>
					<td nowrap align='center'>{$numero_consumidor}</td>
					<td nowrap align='center'>{$email_consumidor}</td>
					<td nowrap align='center'>{$cpf_consumidor}</td>
					<td nowrap align='center'>{$rg_consumidor}</td>
					<td nowrap align='center'>{$origem_consumidor}</td>
					<td nowrap align='center'>{$tipo_consumidor}</td>
					<td nowrap align='center'>{$hora_ligacao}</td>
					<td nowrap align='center'>{$cidade}</td>
					<td nowrap align='center'>{$estado}</td>
					<td nowrap align='center'>{$informacoes_fabrica}</td>
					<td nowrap align='center'>{$produto_descricao}</td>
					<td nowrap align='center'>{$produto_referencia}</td>
					<td nowrap align='center'>{$produto_voltagem}</td>
					<td nowrap align='center'>{$produto_nf}</td>
					<td nowrap align='center'>{$produto_data_nf}</td>
					<td nowrap align='center'>{$produto_serie}</td>
					<td nowrap align='center'>{$nome_fantasia}</td>
					<td nowrap align='center'>{$cnpj_posto}</td>
					<td nowrap align='center'>{$telefone_posto}</td>
					<td nowrap align='center'>{$email_posto}</td>
					<td nowrap align='center'>{$os}</td>
					<td nowrap align='center'>{$aba_callcenter}</td>
					<td nowrap align='center'>{$pre_os}</td>
					<td nowrap align='center'>{$xdefeito_reclamado}</td>
					<td nowrap align='center'>{$descricao}</td>
					<td nowrap align='center'>{$nome_revenda}</td>
					<td nowrap align='center'>{$cnpj_revenda}</td>
					<td nowrap align='center'>{$data_abertura}</td>
					<td nowrap align='center'>{$status}</td>
					<td nowrap align='center'>{$data_retorno}</td>
					<td nowrap align='center'>{$atendente}</td>
					<td nowrap align='center'>{$data_finalizacao}</td>
                </tr>"
        );
    }
    fwrite($file, "
                            <tr>
                                    <th colspan='42' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmit)." registros</th>
                            </tr>
                    </tbody>
            </table>
    ");
    fclose($file);
        if (file_exists("/tmp/{$fileName}")) {
                system("mv /tmp/{$fileName} xls/{$fileName}");

                // devolve para o ajax o nome do arquivo gerado
                    $jsonPOST = excelPostToJson($_POST);

            echo "<br /> <br />

			<a href='../admin/xls/".$fileName."' target='_blank'>
				<div class='btn_excel'>
					<span>
						<img src='imagens/excel.png' />
					</span>
					<span class='txt'>Download em Excel</span>
				</div>
			</a><br />";
        }
} else {
	if ((strlen($bi_latina) > 0 ) AND (pg_num_rows($resSubmit) == 0) and (strlen($msg_erro) == 0)) {
	 echo '<br />';
		echo '<font size="2" color="#000">Nenhum atendimento foi encontrado!</font>';
	 echo '<br />';
	}


}?>
<script type="text/javascript">
    $(function(){
    	$('.ver_detalhe_atendimento').on('click', function(){
    		var login_resumo = $(this).data('login');
    		var login_fabrica = $(this).data('loginfabrica');
    		var periodo = $(this).data('periodo');
    		var tipo_pesquisa = $(this).data('tipo');		  

		        Shadowbox.init();
		        Shadowbox.open({
		            content: "resumo_atendimento_aberto.php?login_resumo="+login_resumo+"&login_fabrica="+login_fabrica+"&periodo="+periodo+"&tipo_pesquisa="+tipo_pesquisa,
		            player: "iframe",
		            width: 700,
		            height: 350
		        });		        	    		
    	})
    })


	var tds = $('#content_pendente').find(".titulo_coluna");

        var colunas = [];

        $(tds).find("th").each(function(){
            if ($(this).attr("class") == "date_column") {
                colunas.push({"sType":"date"});
            }else if ($(this).attr("class") == "money_column") {
                colunas.push({"sType":"numeric"});
            } else {
                colunas.push(null);
            }
        });

    var tds = $('#content_status').find(".titulo_coluna");

        var colunas_status = [];

        $(tds).find("th").each(function(){
            if ($(this).attr("class") == "date_column") {
                colunas_status.push({"sType":"date"});
            }else if ($(this).attr("class") == "money_column") {
                colunas_status.push({"sType":"numeric"});
            } else {
                colunas_status.push(null);
            }
        });

<?php
	if ($login_fabrica == 24) {
?>
		$.dataTableLoad({table: "#content_pendente",aoColumns:colunas});
		$.dataTableLoad({ table: "#content_status",aoColumns:colunas_status});
<?
	} elseif($login_fabrica == 90) {
?>
	var tblPend;
	$(document).ready(function() {
		
		$.dataTableLoad({table: "#content_pendente",aoColumns:colunas});

	    //tblPend.fnSort([[9,'asc']]);
    } );



<?
	}else{		
?>
	$.dataTableLoad({table: "#content_pendente",'order':[[8,'desc']]});
	$.dataTableLoad({table: "#content_status"});
<?php } ?>
</script>
<?
include "rodape.php";
?>
