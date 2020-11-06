<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "auditoria";
#Para a rotina automatica - Fabio - HD 11750
$gera_automatico = trim($_GET["gera_automatico"]);

if (isset($argv[1])) {
	parse_str($argv[1], $get);
	$gera_automatico = $get['gera_automatico'];
}

if ($gera_automatico != 'automatico') {
	include "autentica_admin.php";
}

if ($login_fabrica == 20) {
    include 'relatorio_os_por_posto_peca_hd2756970.php';
    exit;
}

//include "gera_relatorio_pararelo_include.php"; BATATA
include 'funcoes.php';

$msg_erro = "";
$agendar  = 0;
$verifica = 1;

$layout_menu = "auditoria";
$title = traduz("RELATÓRIO DE OS DIGITADAS");

include "cabecalho.php";
include "javascript_pesquisas.php";

# Fábricas que utilizam o formato XLS
$fabricas_xls = array(59, 94);

?>



<script language="JavaScript">

// ===================Para ocultar campos Os que Entraram em Extrato=================

function desabilitaOS_entraram_Extrato(tipo){
	var tipo = tipo;
	$('#entra_extrato').attr("disabled", tipo);
}



// ========= Função PESQUISA DE POSTO POR CÓDIGO OU NOME ========= //
function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
	else{
		alert('<?=traduz("Preencha toda ou parte da informação para realizar a pesquisa!")?>');
	}
}

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
		alert('<?=traduz("Preencha toda ou parte da informação para realizar a pesquisa!")?>');
	}
}

function SomenteNumero(e){
    var tecla=(window.event)?event.keyCode:e.which;
    if((tecla > 47 && tecla < 58)) return true;
    else{
    if (tecla != 8) return false;
    else return true;
    }
}
</script>

<?php
#HD 337758 - Inserindo javascipt de datas
if($login_fabrica == 59){
	include "../js/js_css.php";
	?>
	<script>
	$(document).ready(function(){
		$("#data_inicial").datepick({startDate : "01/01/2000"});
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").datepick({startDate : "01/01/2000"});
		$("#data_final").mask("99/99/9999");
	});
	</script>
	<?php
}
#HD 337758 - FIM inserindo javascipt de datas
?>

<style type="text/css">
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#000000;
	background-color: #d9e2ef
}
.topo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

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

.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.sucesso{
	background-color:green;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
</style>

<p><?php

$btn_acao         = trim($_POST['btn_acao']);
$posto_codigo     = trim($_POST["posto_codigo"]);
$posto_nome       = trim($_POST["posto_nome"]);
$ano              = trim($_POST["ano"]);
$mes              = trim($_POST["mes"]);
$produto_ref      = trim($_POST['produto_referencia']);
$produto_desc     = trim($_POST['produto_descricao']);
$tipo_atendimento = trim($_POST['tipo_atendimento']);
$pais             = trim($_POST['pais']);
$linha            = trim($_POST['linha']);
$familia          = trim($_POST['familia']);
$origem           = trim($_POST['origem']);
$hoje             = date("Y");
//HD 14953
$entra_extrato    = trim($_POST['entra_extrato']);
$tipo_data        = trim($_POST['tipo_data']);
$pais             = trim($_POST['pais']);
# HD 27525
$data_in          = trim($_POST['data_in']);
$data_fl          = trim($_POST['data_fl']);
# HD 337758 - INICIO
$data_inicial     = trim($_POST['data_inicial']);
$data_final	      = trim($_POST['data_final']);
$estado           = trim($_POST['estado']);
$tecnico          = trim($_POST['tecnico']);
$data_referencia  = trim($_POST['data_referencia']);
# HD 337758 - FIM

if($login_fabrica == 20 and $tipo_data == "finalizada"){
	$entra_extrato = "";
}

if (strlen(trim($_GET["btn_acao"])) > 0)		$btn_acao = trim($_GET["btn_acao"]);
if (strlen(trim($_GET["posto_codigo"])) > 0)	$posto_codigo = trim($_GET["posto_codigo"]);
if (strlen(trim($_GET["posto_nome"])) > 0)		$posto_nome = trim($_GET["posto_nome"]);
if (strlen(trim($_GET["ano"])) > 0)				$ano = trim($_GET["ano"]);
if (strlen(trim($_GET["mes"])) > 0)				$mes= trim($_GET["mes"]);
if (strlen(trim($_GET["produto_referencia"])) > 0)		$produto_ref = trim($_GET["produto_referencia"]);
if (empty($produto_desc)) {
	$produto_desc = trim($_GET["produto_descricao"]);
}
if (strlen(trim($_GET["tipo_atendimento"])) > 0) $tipo_atendimento= trim($_GET["tipo_atendimento"]);
if (strlen(trim($_GET["tipo_data"])) > 0)		$tipo_data= trim($_GET["tipo_data"]);
if (strlen(trim($_GET["pais"])) > 0)			$pais= trim($_GET["pais"]);
if (strlen(trim($_GET["linha"])) > 0)			$linha= trim($_GET["linha"]);
if (strlen(trim($_GET["familia"])) > 0)			$familia= trim($_GET["familia"]);
if (strlen(trim($_GET["origem"])) > 0)			$origem= trim($_GET["origem"]);
if (strlen(trim($_GET["entra_extrato"])) > 0)	$entra_extrato= trim($_GET["entra_extrato"]);
if (strlen(trim($_GET["pais"])) > 0)			$pais= trim($_GET["pais"]);
if (strlen(trim($_GET["data_in"])) > 0)			$data_in= trim($_GET['data_in']);
if (strlen(trim($_GET["data_fl"])) > 0)			$data_fl= trim($_GET['data_fl']);
# HD 337758 - INICIO
if (strlen(trim($_GET["data_inicial"])) > 0)	$data_inicial= trim($_GET['data_inicial']);
if (strlen(trim($_GET["data_final"])) > 0)		$data_final= trim($_GET['data_final']);
if (strlen(trim($_GET["estado"])) > 0)			$estado= trim($_GET['estado']);
if (strlen(trim($_GET["tecnico"])) > 0)			$tecnico= trim($_GET['tecnico']);
if (strlen(trim($_GET["data_referencia"])) > 0)	$data_referencia= trim($_GET['data_referencia']);
# HD 337758 - FIM

if (isset($get)) {
	$login_fabrica    = $get['login_fabrica'];
	$login_admin      = $get['login_admin'];
	$btn_acao         = $get['btn_acao'];
	$posto_codigo     = $get['posto_codigo'];
	$posto_nome       = $get['posto_nome'];
	$ano              = $get['ano'];
	$mes              = $get['mes'];
	$produto_ref      = $get['produto_referencia'];
	$produto_desc     = $get['produto_descricao'];
	$tipo_atendimento = $get['tipo_atendimento'];
	$tipo_data        = $get['tipo_data'];
	$pais             = $get['pais'];
	$linha            = $get['linha'];
	$familia 		  = $get['familia'];
	$origem           = $get['origem'];
	$entra_extrato    = $get['entra_extrato'];
	$pais             = $get['pais'];
	$data_in          = $get['data_in'];
	$data_fl          = $get['data_fl'];
	$data_inicial     = $get['data_inicial'];
	$data_final       = $get['data_final'];
	$estado           = $get['estado'];
	$tecnico          = $get['tecnico'];
	$data_referencia  = $get['data_referencia'];
}

if (strlen($btn_acao) > 0) {

	# HD 27525
	if ($login_fabrica  == 25) {
	// 10/09/2010 MLG - Ajustando esagens de erro ao novo padrão
		if (!$data_in or !$data_fl) $msg_erro = traduz("Data inválida!");
		if (!$msg_erro) {
			list($d, $m, $y) = explode("/", $data_inicial);
			if(!checkdate($m,$d,$y)) $msg_erro = traduz("Data Inválida");

			list($d, $m, $y) = explode("/", $data_inicial);
			if(!checkdate($m,$d,$y)) $msg_erro = traduz("Data Inválida");
		}

	} else {

		if (strlen($posto_codigo) == 0 OR strlen($posto_nome) == 0) {
			if (!in_array($login_fabrica, [20,51,85,123])) {
				if (!$telecontrol_distrib) { 
					$msg_erro .= traduz("É obrigatório selecionar um posto. ");
				}
			}
		}

		if ($login_fabrica == 51 || ($telecontrol_distrib && $login_fabrica != 123)) {
			if(empty($mes)){
				$msg_erro = traduz("É obrigatório selecionar o mês.");
			}
		}
		#HD 337758 - INICIO
		if(in_array($login_fabrica,array(59))){
			if (empty($data_inicial) or empty($data_final))
				$msg_erro = traduz("Data inválida!");

			if (!$msg_erro) {
				list($di, $mi, $yi) = explode("/", $data_inicial);

				$ano = date('Y');
				$mes = date('m');

				if(!checkdate($mi,$di,$yi))
					$msg_erro = traduz("Data Inválida");

				list($df,$mf,$yf) = explode("/", $data_final);

				if(!checkdate($mf,$df,$yf))
					$msg_erro = traduz("Data Inválida.");

				if(strlen($msg_erro)==0){
					$aux_data_inicial = "$yi-$mi-$di";
					$aux_data_final = "$yf-$mf-$df";
				}
				if(strlen($msg_erro)==0){
					if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
						$msg_erro = traduz("Data Inválida.");
					}
				}

				if(strlen($msg_erro)==0){
					if (strtotime($aux_data_inicial) < strtotime($aux_data_final.' -3 month')) {
						$msg_erro = traduz('O intervalo entre as datas não pode ser maior que 3 meses.');
					}
				 }

			}
		}else{
		#HD 337758 - FIM

			if (in_array($login_fabrica,array(20,123))) {

				if (!$data_in or !$data_fl) $msg_erro = traduz("Data inválida!");

				if (!$msg_erro) {

					list($di, $mi, $yi) = explode("/", $data_in);
					if(!checkdate($mi,$di,$yi)) $msg_erro = traduz("Data Inválida");

					list($df, $mf, $yf) = explode("/", $data_fl);
					if(!checkdate($mf,$df,$yf)) $msg_erro = traduz("Data Inválida");

				}

				$data_inicial = $yi . '-' . $mi . '-' . $di;
				$data_final   = $yf . '-' . $mf . '-' . $df;


				if(strtotime($data_final) < strtotime($data_inicial)){

					$msg_erro = traduz("Data Inválida.");

				}

				if($login_fabrica == 123){

					if (strtotime($data_inicial) < strtotime($data_final.' -3 month')){
						$msg_erro = traduz("Período não pode ser superior a 3 meses");
					}
				}else{
					if (strtotime($data_inicial) < strtotime($data_final.' - 1 year')) {

						$agendar = 1;


					}elseif( (strtotime($data_inicial.' + 1 month') >= strtotime($data_final) ) and $entra_extrato == 'SIM' ){

						$agendar = 0;

					}elseif( (strtotime($data_inicial) < strtotime($data_final.' - 1 month') ) and $entra_extrato == 'SIM' and empty($pais) ){

						$agendar = 1;


					}elseif( !empty($pais) and $entra_extrato == 'SIM' and ( strtotime($data_inicial.'+ 3 month') >= strtotime($data_final) ) ){

						$agendar = 0;


					}elseif (strtotime($data_inicial) < strtotime($data_final.' -3 month')) {

						$checkFiltros = array (
											$posto_codigo,
											$posto_nome,
											$pais,
											$tipo_atendimento,
											$linha,
											$familia,
											$origem
										);

						$agendar = 1;

						if (!empty($produto_ref) and !empty($produto_desc)) {
							foreach ($checkFiltros as $filtro) {

								if (!empty($filtro)) {
									$agendar = 0;
									break;
								}

							}

						}

					}
				}

			} else {

				if (strlen($ano) == 0 AND strlen($mes) == 0){
					$msg_erro .= traduz(" O preenchimento do Ano é obrigatório! ");
				}

				if(($ano < 1990) || ($ano > $hoje) || (strlen($ano)< 4)){
					$msg_erro .= traduz(" O Ano informado está incorreto! ");
				}

				if ($login_fabrica == 85) {

					$mes_inicial = $_POST['mes_inicial'];
					$mes_final   = $_POST['mes_final'];

					if(empty($mes_inicial)) {
						$msg_erro .= traduz(" É obrigatório selecionar o mês inicial.")."<br />";
					}

					if(empty($mes_final)) {
						$msg_erro .= traduz(" É obrigatório selecionar o mês final.")."<br />";
					}

					if (strlen($ano) > 0 && strlen($mes_inicial) > 0 && strlen($mes_final) > 0) {
						$data_inicial = "$ano-$mes_inicial-01 00:00:00";
						$data_final   = "$ano-$mes_final-" . date("t", mktime(0, 0, 0, $mes_final, 1, $ano)) . " 23:59:59";
						
						$dateTimeAtual  = new DateTime($data_inicial);
						$dateTimeFinal  = new DateTime($data_final);

						$intervalo = $dateTimeAtual->diff($dateTimeFinal);

						if ($intervalo->m > 3 || $intervalo->y > 0) {

							$msg_erro .= traduz(" Prazo limitado para pesquisa de 3 meses");
						} 
					}

				}

			}

		}

	}

}

if ($login_fabrica == 20) {
	echo '<div class="texto_avulso" style="width: 735px; padding: 5px;">';
		echo traduz('O limite entre datas para a geração do relatório no mesmo dia é de 90 dias, caso ultrapasse este período, o relatório será agendado para rodar na rotina de madrugada e será enviado por email para o usuário.<br/>');
		echo traduz('Quando informado o produto e mais algum outro filtro o período para geração no mesmo dia pode ser de até 1 ano.');
	echo '</div><br/>';
}

#HD 15551
if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
	/**
	 * @since HD 341949
	 */
	if ($agendar == 1) {
		$aviso = '';

		$sql = "SELECT relatorio_agendamento FROM tbl_relatorio_agendamento
				WHERE admin = $login_admin
				AND fabrica = $login_fabrica
				AND executado IS NULL
				AND agendado IS NOT FALSE";
		$qry = pg_query($con, $sql);

		if (pg_num_rows($qry) > 0) {
			$cancela = "UPDATE tbl_relatorio_agendamento SET executado = current_date
						WHERE admin = $login_admin AND fabrica = $login_fabrica AND executado IS NULL";
			$qry_cancela = pg_query($con, $cancela);

			if (!pg_last_error()) {
				$aviso = '<br/><br/>'.traduz('AVISO: Os relatórios anteriores agendados pelo seu usuário foram cancelados em razão do agendamento atual.');
			}
		}

		$parametros = "";
		foreach ($_POST as $key => $value){
			$parametros .= $key."=".$value."&";
		}
		foreach ($_GET as $key => $value){
			$parametros .= $key."=".$value."&";
		}

		$sql = "INSERT INTO tbl_relatorio_agendamento (admin, fabrica, programa, parametros, titulo, agendado)
				VALUES ($login_admin, $login_fabrica, '$PHP_SELF', '$parametros', '$title', 't')";
		$res = pg_query($con,$sql);

		if (!pg_last_error()) {
			echo "<div style='width:735px; padding:  5px; margin: 0 auto;' class='sucesso' align='center'>".traduz("O relatório foi agendado e será processado nesta madrugada.")."<br/>".traduz("Um email lhe será enviado ao final do processo.$aviso")."</div><br/>";
		}
	} else {
		//include "gera_relatorio_pararelo.php"; BATATA
	}
}

if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){

	if ($agendar == 0 and $verifica == 1) {
		//include "gera_relatorio_pararelo_verifica.php";  BATATA
	}

}

?>

<form name='frm_os_posto' action='<? echo $PHP_SELF ?>' method="POST">
<input type="hidden" name='btn_acao' value="" />
<table width='700' class="formulario" align='center' border='0' cellspacing='2' cellpadding='2'><?php
	if (strlen($msg_erro) > 0) {?>
		<tr class='msg_erro'>
			<td colspan="5"><?php echo $msg_erro; ?></td>
		</tr><?php
	}?>
	<tr class='titulo_tabela'>
		<td colspan='5'><?=traduz("Parâmetros de Pesquisa")?></td>
	</tr><?php
	# HD 27525
	if ($login_fabrica == 25) {
		include "javascript_calendario.php"; ?>

		<script type="text/javascript" charset="utf-8">
			$(function() {
				$("input[name^='data']").mask("99/99/9999");
			});
		</script>
		<tr style='font-weight:bold'>
			<td><?=traduz("Data inicial")?></td>
			<td><?=traduz("Data final")?></td>
		</tr>
		<tr>
			<td><input class='frm' type='text' name='data_in' value='<?=$data_in?>' size='12' maxlength='20'></td>
			<td><input class='frm' type='text' name='data_fl' value='<?=$data_fl?>' size='12' maxlength='20'></td>
		</tr>
<?	} else { ?>

		<?php if($login_fabrica == 59) {?>
			<tr>
				<td width="60">&nbsp;</td>
				<td><?=traduz("Data inicial")?></td>
				<td><?=traduz("Data final")?></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td><input class='frm' type='text' id='data_inicial' name='data_inicial' value='<?=$data_inicial?>' size='12' maxlength='10'></td>
				<td><input class='frm' type='text' id='data_final' name='data_final' value='<?=$data_final?>' size='12' maxlength='10'></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td><?=traduz("Data de Referência")?></td>
				<td><?=traduz("Técnico")?></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>
					<select name="data_referencia" class="frm">
						<?php
						$referencias = array(1=>'Digitação',2=>'Abertura',3=>'Conserto',4=>'Finalizada',5=>'Fechamento',6=>'Geração do Extrato',7=>'Aprovação do Extrato');
						foreach($referencias as $idReferencia => $nomeReferencia) {
							$selected = ($idReferencia == $data_referencia) ? ' selected="selected"' : null;
							echo '<option value="'.$idReferencia.'" '.$selected.'>'.$nomeReferencia.'</option>'."\n";
						}?>
					</select>
				</td>
				<td>
					<select name="tecnico" id="tecnico" class="frm">
						<?php
						$sqlTecnico = "SELECT DISTINCT(tbl_tecnico.nome) AS nome
										 FROM tbl_tecnico
										WHERE tbl_tecnico.fabrica = $login_fabrica;";
						$resTecnico = pg_exec($con,$sqlTecnico);
						$numTecnicos = pg_numrows($resTecnico);

						if ($numTecnicos > 0) {

							echo '<option value="">Selecione</option>';

							for($i=0;$i<$numTecnicos;$i++) {
								$nomeTecnico = pg_result($resTecnico,$i,'nome');
								$selected = ($nomeTecnico == $tecnico) ? ' selected="selected"' : null;
								echo '<option value="'.$nomeTecnico.'" '.$selected.'>'.$nomeTecnico.'</option>'."\n";
							}
						}else{
							echo '<option value="">'.traduz("Nenhum técnico cadastrado").'</option>';
						}?>
					</select>

				</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td><?=traduz("Estado")?></td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>
					<select name="estado" id="estado" class="frm">
						<option value=""><?=traduz("Selecione")?></option>
						<?php
						$estados = array('AC'=>'Acre', 'AL'=>'Alagoas', 'AM'=>'Amazonas', 'AP'=>'Amapá','BA'=>'Bahia','CE'=>'Ceará','DF'=>'Distrito Federal','ES'=>'Espírito Santo','GO'=>'Goiás','MA'=>'Maranhão','MT'=>'Mato Grosso','MS'=>'Mato Grosso do Sul','MG'=>'Minas Gerais','PA'=>'Pará','PB'=>'Paraíba','PR'=>'Paraná','PE'=>'Pernambuco','PI'=>'Piauí','RJ'=>'Rio de Janeiro','RN'=>'Rio Grande do Norte','RO'=>'Rondônia','RS'=>'Rio Grande do Sul','RR'=>'Roraima','SC'=>'Santa Catarina','SE'=>'Sergipe','SP'=>'São Paulo','TO'=>'Tocantins');
						foreach($estados as $sigla => $nomeEstado) {
							$selected = ($estado == $sigla) ? ' selected="selected"' : null;
							echo '<option value="'.$sigla.'" '.$selected.'>'.$nomeEstado.'</option>'."\n";
						}?>
					</select>
				</td>
				<td>&nbsp;</td>
			</tr>
		<?php }?>

		<tr>
			<td>&nbsp;</td>
			<?php if ($login_fabrica == 85 || $telecontrol_distrib) {  ?>
				<td align="left"><?=traduz("Código do Posto")?></td>
				<td align="left"><?=traduz("Nome do Posto")?></td>
			<?php } else { ?>
				<td align="left"><?=traduz("Código do Posto *")?></td>
				<td align="left"><?=traduz("Nome do Posto *")?></td>
			<?php } ?> 
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td align="left">
				<input class="frm" type="text" name="posto_codigo" size="13" value="<? echo $posto_codigo ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm_os_posto.posto_codigo,document.frm_os_posto.posto_nome,'codigo')" <? } ?>>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_os_posto.posto_codigo,document.frm_os_posto.posto_nome,'codigo')">
			</td>
			<td align="left" nowrap>
				<input class="frm" type="text" name="posto_nome" size="50" value="<? echo $posto_nome ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm_os_posto.posto_codigo,document.frm_os_posto.posto_nome,'nome')" <? } ?>>&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_os_posto.posto_codigo,document.frm_os_posto.posto_nome,'nome')" style="cursor:pointer;">
			</td>
		</tr>
		<?php if($login_fabrica != 59):?>

			<?php if (in_array($login_fabrica,array(20,123))) { ?>

<?php
                    include_once 'javascript_calendario_new.php';
                    include_once 'javascript_calendario_new.php';
?>
                <style type="text/css">
                    @import "../plugins/jquery/datepick/telecontrol.datepick.css";
                </style>
                <link rel="stylesheet" type="text/css" href="js/jquery-ui-1.8.23.custom/development-bundle/themes/base/jquery.ui.all.css" media="all">

                <script src="js/jquery-ui-1.8.23.custom/js/jquery-1.8.0.min.js"></script>
                <script src="../plugins/jquery/datepick/jquery.datepick.js"></script>
                <script src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
                <script type="text/javascript" src="js/jquery.mask.js"></script>
				<script type='text/javascript'>

					$(function(){
						$('.mask_date').datepick({startDate:'01/01/2000'});
						$('.mask_date').mask("99/99/9999");
						$('#data_in').datepick({startDate:'01/01/2000'});
						$('#data_in').mask("99/99/9999");
						$('#data_fl').datepick({startDate:'01/01/2000'});
						$('#data_fl').mask("99/99/9999");
					});

				</script>

				<tr>
					<td>&nbsp;</td>
					<td><?=traduz("Data inicial")?></td>
					<td><?=traduz("Data final")?></td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>
						<input class='frm' type='text' name='data_in' id='data_in' value='<?php echo $data_in; ?>' size='12' maxlength='20'>
					</td>
					<td>
						<input class='frm' type='text' name='data_fl' id='data_fl' value='<?php echo $data_fl; ?>' size='12' maxlength='20'>
					</td>
				</tr>
			<?php } else { ?>
			<tr>
				<td>&nbsp;</td>
				<td align="left"><?=traduz("Ano *")?></td>
				<?php if ($login_fabrica == 85) { ?>
					<td>Data Inicial *</td>
					<td><div style="left: -250px !important;position: relative;">Data Final *</div></td>
				<?php } else { ?> 
					<?php if ($telecontrol_distrib) { ?>
						<td align="left"><?=traduz("Mês")?></td>
					<?php } else { ?> 
						<td align="left"><?=traduz("Mês")?></td>
					<?php } ?>
				<?php } ?>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td align="left">
					<input class="frm" type="text" name="ano" size="13" maxlength="4" value="<? echo $ano ?>" onkeyup="re = /\D/g; this.value = this.value.replace(re, '');">
				</td>

				<?php 
					$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
				?>

				<?php if ($login_fabrica == 85) { ?>

					<td align="left">
						<select name="mes_inicial" class="frm" >
							<option value=''></option><?php
							$total_mes = count($meses);
							for ($i = 1 ; $i <= $total_mes; $i++) {
								echo "<option value='$i'";
								if ($mes_inicial == $i) echo " selected";
								echo ">".$meses[$i]."</option>\n";
							}?>
						</select>
					</td>
					<td align="left" width="40%">
						<select style="left: -250px !important;position: relative;" name="mes_final" class="frm">
							<option value=''></option><?php
							$total_mes = count($meses);
							for ($i = 1 ; $i <= $total_mes; $i++) {
								echo "<option value='$i'";
								if ($mes_final == $i) echo " selected";
								echo ">".$meses[$i]."</option>\n";
							}?>
						</select>
					</td>

				<?php } else { ?>

					<td align="left">
						<select name="mes" class="frm">
							<option value=''></option><?php
							$total_mes = count($meses);
							for ($i = 1 ; $i <= $total_mes; $i++) {
								echo "<option value='$i'";
								if ($mes == $i) echo " selected";
								echo ">".$meses[$i]."</option>\n";
						}?>
						</select>
					</td>

				<?php } ?>
			</tr>
			<?php } ?>

		<?php
		endif;
	}

	if ($login_fabrica == 20) {
		// MLG 2009-08-04 HD 136625
		$sql   = 'SELECT pais,nome FROM tbl_pais where america_latina is TRUE';
		$res   = pg_query($con,$sql);
		$p_tot = pg_num_rows($res);

		for ($i = 0; $i < $p_tot; $i++) {
			list($p_code,$p_nome) = pg_fetch_row($res, $i);
			$sel_paises .= "\t\t\t\t<option value='$p_code'";
			$sel_paises .= ($pais==$p_code)?" selected":"";
			$sel_paises .= ">$p_nome</option>\n";
		}?>
		<tr>
			<td>&nbsp;</td>
			<td><?=traduz("País")?></td>
			<td><?=traduz("Tipo Atendimento")?></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>
					<select name='pais' size='1' class='frm' onchange="javascript: if (this.value != 'BR') {
					document.getElementById('tipo_datae').disabled=true; document.getElementById('tipo_datae').checked=false; } else document.getElementById('tipo_datae').disabled=false">
					 <option></option>
					<?echo $sel_paises;?>
					</select>
			</td>
			<td>
				<select name="tipo_atendimento" size="1" class="frm">
					<option <? if (strlen ($tipo_atendimento) == 0) echo " selected " ?> ></option><?php
					$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica ORDER BY tipo_atendimento";
					$res = pg_exec ($con,$sql) ;

					$total_atendimento = pg_numrows($res);
					for ($i = 0 ; $i <  $total_atendimento; $i++ ) {
						echo "<option ";
						if ($tipo_atendimento == pg_result ($res,$i,tipo_atendimento) ) echo " selected ";
						echo " value='" . pg_result ($res,$i,tipo_atendimento) . "'" ;
						echo " > ";
						echo pg_result ($res,$i,codigo) . " - " . pg_result ($res,$i,descricao) ;
						echo "</option>\n";
					}?>
				</select>
			</td>
		</tr><?php

		if ($login_fabrica == 20) { ?>

			<tr bgcolor="#D9E2EF">
				<td>&nbsp;</td>
				<td><?=traduz("Linha")?></td>
				<td><?=traduz("Origem")?></td>
			</tr>
			<tr bgcolor="#D9E2EF">
				<td>&nbsp;</td>
				<td><?php
					$sql = "SELECT  *
							FROM    tbl_linha
							WHERE   tbl_linha.fabrica = $login_fabrica
							ORDER BY tbl_linha.nome;";
					$res = pg_exec ($con,$sql);

					if (pg_numrows($res) > 0) {
						echo "<select class='frm' style='width: 280px;' name='linha'>\n";
						echo "<option value=''>".traduz("ESCOLHA")."</option>\n";

						for ($x = 0 ; $x < pg_numrows($res) ; $x++){
							$aux_linha = trim(pg_result($res,$x,linha));
							$aux_nome  = trim(pg_result($res,$x,nome));

							echo "<option value='$aux_linha'"; if ($linha == $aux_linha) echo " SELECTED "; echo ">$aux_nome</option>\n";
						}
						echo "</select>\n";
					}?>
				</td>
				<td>
					<select name="origem" class="frm">
						<option value=""><?=traduz("ESCOLHA")?></option>
						<option value="Nac" <? if ($origem == "Nac") echo " SELECTED "; ?>><?=traduz("Nacional")?></option>
						<option value="Imp" <? if ($origem == "Imp") echo " SELECTED "; ?>><?=traduz("Importado")?></option>
						<option value="USA" <? if ($origem == "USA") echo " SELECTED "; ?>><?=traduz("Importado USA")?></option>
						<option value="Asi" <? if ($origem == "Asi") echo " SELECTED "; ?>><?=traduz("Importado Asia")?></option>
					</select>
				</td>
			</tr>
			<tr bgcolor="#D9E2EF">
				<td>&nbsp;</td>
				<td ><?=traduz("Familia")?></td>
			</tr>
			<tr bgcolor="#D9E2EF">
				<td>&nbsp;</td>
				<td><?php
					$sql = "SELECT  familia,descricao
							FROM    tbl_familia
							WHERE   tbl_familia.fabrica = $login_fabrica
							AND tbl_familia.ativo IS TRUE
							ORDER BY tbl_familia.descricao;";
					$res = pg_exec ($con,$sql);

					if (pg_numrows($res) > 0) {
						echo "<select class='frm' style='width: 280px;' name='familia'>\n";
						echo "<option value=''>".traduz("ESCOLHA")."</option>\n";

						for ($x = 0 ; $x < pg_numrows($res) ; $x++){
							$aux_familia = trim(pg_result($res,$x,familia));
							$aux_descricao  = trim(pg_result($res,$x,descricao));

							echo "<option value='$aux_familia'"; if ($familia == $aux_familia) echo " SELECTED "; echo ">$aux_descricao</option>\n";
						}
						echo "</select>\n";
					}?>
				</td>
			</tr><?php


		}

	}?>
	<tr>
		<td>&nbsp;</td>
		<td align="left"><?=traduz("Referência")?></td>
		<td align="left"><?=traduz("Descrição Produto")?></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td align="left"><input type="text" name="produto_referencia" value="<? echo $produto_referencia ?>" size="15" maxlength="20" class='frm'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os_posto.produto_referencia,document.frm_os_posto.produto_descricao,'referencia')" alt='Clique aqui para pesquisar pela referência do produto' style='cursor:pointer;'></td>
		<td align="left"><input type="text" name="produto_descricao" value="<? echo $produto_descricao ?>" size="45" maxlength="50" class='frm'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os_posto.produto_referencia,document.frm_os_posto.produto_descricao,'descricao')" alt='Clique aqui para pesquisar pela descrição do produto' style='cursor:pointer;'></td>
	</tr><?php
	if ($login_fabrica == 20) { //HD 14953?>
		<tr class='menu_top'>
			<td colspan='2' style="padding:0 0 0 60px;">
				<fieldset style="width:170px;" align='left'>
					<legend><?=traduz("Tipo de Data")?></legend>
					<table border='0' width='100%'>
						<tr align="left">
							<td>
								<input type='radio' name='tipo_data' onclick="desabilitaOS_entraram_Extrato(false)" id='tipo_datae' value='exportacao' <? if($tipo_data=='exportacao') echo "checked"; ?>><?=traduz("Data Exportação")?> &nbsp;
								<span title=<?=traduz("Data exportação do PA para Bosch")?>style="color:red;font-weight:bold"><img src="imagens/help.png"></span>
							</td>
						</tr>
						<tr align="left">
							<td>

								<input type='radio' name='tipo_data' onclick="desabilitaOS_entraram_Extrato(false)" value='geracao'<? if($tipo_data=='geracao' or strlen($tipo_data)==0) echo "checked"; ?>>
								<?=traduz("Data Geração")?> &nbsp;
								<span title= <?=traduz("Data de criação de extrato")?> style="color:red;font-weight:bold"><img src="imagens/help.png"></span>
							</td>
						</tr>
						<tr align="left">
							<td>
								<input type='radio' name='tipo_data' onclick="desabilitaOS_entraram_Extrato(false)" value='aprovacao'<? if($tipo_data=='aprovacao') echo "checked"; ?>>
								<?=traduz("Data Aprovação")?>
								<span title=<?=traduz("Data de aprovação de extratos aprovados da OS")?> style="color:red;font-weight:bold"><img src="imagens/help.png"></span>
							</td>
						</tr>
						<?php
						if ($login_fabrica == 20) {
						?>
						<tr align="left">
							<td>
								<input type='radio' name='tipo_data' onclick="desabilitaOS_entraram_Extrato(true)" value='finalizada'<? if($tipo_data=='finalizada') echo "checked"; ?>>
								<?=traduz("Data Finalizada")?>
								<span title=<?=traduz("OS's que foram fechadas dentro do período")?> style="color:red;font-weight:bold"><img src="imagens/help.png"></span>
							</td>
						</tr>
						<?php	
						}
						?>						
					</table>
				</fieldset>
			</td>

			<td align='left'>
				<fieldset style="width:200px;">
					<legend><?=traduz("OS que Entraram em Extrato")?></legend>
					<?php
					$checked = '';
					if ($login_fabrica == 20) {
						if(strlen($entra_extrato) ==0 ) {
							$checked = "checked";
						}elseif($entra_extrato=='NAO' or $entra_extrato=='SIM'){
							$checked = "checked";
						}
					}else{
						if($entra_extrato=='NAO' or strlen($entra_extrato) ==0 ) {
							$checked = "checked";
						}elseif($entra_extrato=='SIM'){
							$checked = "checked";
						}
					}
					?>

					<table width='100%'>
					<tr>
						<td>
							<input type='radio' name='entra_extrato' id="entra_extrato" value='NAO' <?=$checked?> > Não &nbsp;

							<input type='radio' name='entra_extrato' id="entra_extrato" value='SIM'<?=$checked?> > SIM
						</td>
					</tr>
					</table>
				</fieldset>

			</td>
		</tr>

	<?php
		flush();
	}?>
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="4" align="center">
			<input type="button" style='background:url(imagens_admin/btn_confirmar.gif); width:95px; cursor:pointer;' value="&nbsp;" onclick="javascript: if (document.frm_os_posto.btn_acao.value == '' ) { document.frm_os_posto.btn_acao.value='continuar' ; document.frm_os_posto.submit() } else { alert ('<?=traduz("Aguarde submissão")?>') }" ALT="Confirmar" border='0'>
		<td>
	</tr>
</table>
<br />
</form>
<br /><?php

if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0 and $agendar == 0) {
//  Fabricas que querem uma linha por OS, com as peças como colunas adicionais
$os_por_linha_com_pecas = in_array($login_fabrica, array(15, 59));

	# HD 27525
	if ($login_fabrica == 25) {

		$ano_di = substr($data_in, -4);
		$mes_di = substr($data_in, 3, 2);
		$dia_di = substr($data_in, 0, 2);
		$data_inicial = date("Y-m-d", mktime(0, 0, 0, $mes_di, $dia_di, $ano_di));
		$ano_df = substr($data_fl, -4);
		$mes_df = substr($data_fl, 3, 2);
		$dia_df = substr($data_fl, 0, 2);
		$data_final = date("Y-m-d", mktime(0, 0, 0, $mes_df, $dia_df, $ano_df));

	} elseif ($login_fabrica == 85) { 
		
		if (strlen($ano) > 0 && strlen($mes_inicial) > 0 && strlen($mes_final) > 0) {
			$data_inicial = "$ano-$mes_inicial-01 00:00:00";
			$data_final   = "$ano-$mes_final-" . date("t", mktime(0, 0, 0, $mes_final, 1, $ano)) . " 23:59:59";
		}

	} else {

		if (strlen($mes) > 0 OR strlen($ano) > 0) {

			if (strlen($mes) > 0) {
				if (strlen($mes) == 1) $mes = "0".$mes;
				$data_inicial = "2005-$mes-01 00:00:00";
				$data_final   = "2005-$mes-".date("t", mktime(0, 0, 0, $mes, 1, 2005))." 23:59:59";
			}

			if (strlen($ano) > 0) {
				$data_inicial = "$ano-01-01 00:00:00";
				$data_final   = "$ano-12-".date("t", mktime(0, 0, 0, 12, 1, 2005))." 23:59:59";
			}

			if (strlen($mes) > 0 AND strlen($ano) > 0) {
				$data_inicial = "$ano-$mes-01 00:00:00";
				$data_final   = "$ano-$mes-".date("t", mktime(0, 0, 0, $mes, 1, 2005))." 23:59:59";
			}
		}
	}

	if (strlen($posto_codigo) > 0) {

		$sqlPosto =	"SELECT posto
					FROM tbl_posto_fabrica
					WHERE codigo_posto = '$posto_codigo'
					AND fabrica = $login_fabrica";
		$res = pg_exec($con,$sqlPosto);
		if (pg_numrows($res) == 1) {
			$posto = pg_result($res,0,0);
		}

	}

	//TAKASHI COLOCOU CAMPO DIGITACAO DO ITEM
	//TULIO - Tornar obrigatoria digitacao de DATA INICIAL E FINAL - junho/2007
	//HD14953
	if ($entra_extrato == 'SIM' and $login_fabrica == 20 and $tipo_data != 'finalizada') {

		$sql_join ="JOIN tbl_extrato    on tbl_os_extra.extrato=tbl_extrato.extrato and tbl_extrato.fabrica = $login_fabrica
					JOIN tbl_extrato_extra on tbl_extrato.extrato=tbl_extrato_extra.extrato";

		if ($pais == 'BR') {
			$sql_valor=", 	tbl_extrato.extrato,
							to_char(tbl_extrato_extra.exportado,'DD/MM/YYYY') as exportado,
							to_char(tbl_extrato.aprovado, 'DD/MM/YYYY') as data_aprovado";

			switch ($tipo_data) {
				case 'exportacao':
		 			$sql_cond="AND tbl_os.posto=tbl_extrato.posto AND tbl_extrato_extra.exportado BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'";
					break;
				case 'geracao':
					$sql_cond="AND tbl_os.posto=tbl_extrato.posto AND tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final'";
					break;
				case 'aprovacao':
					$sql_cond="AND tbl_os.posto=tbl_extrato.posto AND tbl_extrato.aprovado BETWEEN '$data_inicial' AND '$data_final'";
					break;
			}

		} else if($pais <> 'BR') {

			$sql_valor=", tbl_extrato.extrato ,
						to_char(tbl_extrato.data_geracao,'DD/MM/YYYY') as exportado ,
						to_char(tbl_extrato.aprovado, 'DD/MM/YYYY') as data_aprovado";

			switch ($tipo_data) {
				case 'geracao':
					$sql_cond="AND tbl_os.posto=tbl_extrato.posto AND tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final'";
					break;
				case 'aprovacao':
					$sql_cond="AND tbl_os.posto=tbl_extrato.posto AND tbl_extrato.aprovado BETWEEN '$data_inicial' AND '$data_final'";
					break;
			}
		}
	}

	# HD 337758
	if($login_fabrica == 59){

		$data_inicial = $aux_data_inicial.' 00:00:00';
		$data_final = $aux_data_final.' 23:59:59';

		$campoBusca = null;

		switch($data_referencia){
			case '1':
				//Digitação -> tbl_os.data_digitacao
				$sql_cond .= " AND tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'";
				break;

			case '2':
				//Abertura -> tbl_os.data_abertura
				$sql_cond .= " AND tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final'";
				break;

			case '3':
				//Conserto -> tbl_os.data_conserto
				$sql_cond .= " AND tbl_os.data_conserto BETWEEN '$data_inicial' AND '$data_final'";
				break;

			case '4':
				//Finalizada -> tbl_os.finalizada
				$sql_cond .= " AND tbl_os.finalizada BETWEEN '$data_inicial' AND '$data_final'";
				break;

			case '5':
				//Fechamento -> tbl_os.data_fechamento
				$sql_cond .= " AND tbl_os.data_fechamento BETWEEN '$data_inicial' AND '$data_final'";
				break;

			case '6':
				//Geração do Extrato -> tbl_extrato.data_geracao (tbl_os JOIN tbl_os_extra USING(os) JOIN tbl_extrato USING(extrato)
				$sql_valor .= ", TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao ";
				$sql_join .= "JOIN tbl_extrato USING(extrato)";
				$sql_cond .= " AND tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final'";
				$campoRelatorio = '\tDATA GERAÇÃO';
				$campoBusca = 'data_geracao';
				break;

			case '7':
				//Aprovação do Extrato -> tbl_extrato.aprovado (tbl_os JOIN tbl_os_extra USING(os) JOIN tbl_extrato USING(extrato)
				$sql_valor .= ", TO_CHAR(tbl_extrato.aprovado,'DD/MM/YYYY') AS aprovado ";
				$sql_join .= "JOIN tbl_extrato USING(extrato)";
				$sql_cond .= " AND tbl_extrato.aprovado BETWEEN '$data_inicial' AND '$data_final'";
				$campoRelatorio = '\tDATA APROVAÇÃO';
				$campoBusca = 'aprovado';
				break;
		}

		if($tecnico){

			$sqlTecnico = "SELECT tbl_tecnico.tecnico AS tecnico
							 FROM tbl_tecnico
							 JOIN tbl_posto USING (posto)
							WHERE tbl_tecnico.fabrica = $login_fabrica
							  AND tbl_posto.posto = $posto
							  AND tbl_tecnico.nome = '$tecnico';";
			$resTecnico = pg_exec($con,$sqlTecnico);
			$numTecnicos = pg_numrows($resTecnico);

			if ($numTecnicos > 0) {
				$idTecnico = pg_result($resTecnico,0,'tecnico');
			}

			$sql_cond_dig .= " AND tbl_os.tecnico = $idTecnico";
		}

		if($estado){
			$sql_cond_dig .= " AND tbl_os.consumidor_estado = '$estado'";
		}

	}else{
	#HD 337758

		//hd 17003 11/4/2008
		if ($login_fabrica == 20) {
			if ($tipo_data == 'finalizada') {
				$sql_cond=" AND tbl_os.finalizada BETWEEN '$data_inicial' AND '$data_final'";
			}elseif($entra_extrato == 'NAO') {
				$sql_cond_dig ="AND tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'";
			}
			
		} else if($login_fabrica <> 20) {
			$sql_cond_dig ="AND tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'";
		}


		# HD 27525
		if ($login_fabrica == 25) {
			$sql_cond_dig ="AND tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'";
		}

	}

	if($login_fabrica == 123){
		$sql_ped_fat = " LEFT JOIN tbl_pedido ON tbl_os_item.pedido = tbl_pedido.pedido AND tbl_pedido.fabrica = $login_fabrica
				LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_pedido.pedido
				LEFT JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento ";
		$campos_ped_fat = ", tbl_pedido.pedido,tbl_faturamento.nota_fiscal AS nota_envio, to_char(tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao, tbl_faturamento.conhecimento ";
	}

	#HD 100410 select modificado para ter somente uma os por linha
	if ($os_por_linha_com_pecas) {

		$sql = "SELECT DISTINCT tbl_os.sua_os                                           ,
					tbl_os.os                                                          ,
					tbl_os.tecnico_nome                                                ,
					tbl_os.tecnico                                                     ,
					tbl_os.consumidor_nome                                             ,
					tbl_os.consumidor_cpf                                              ,
					tbl_os.consumidor_fone                                             ,
					tbl_os.consumidor_estado                                           ,
					tbl_os.revenda_nome                                                ,
					tbl_os.serie                                                       ,
					tbl_os.pecas                                                       ,
					tbl_os.mao_de_obra                                                 ,
					tbl_os.nota_fiscal                                                 ,
					tbl_os.solucao_os                                                  ,
					tbl_os_extra.tipo_troca                                            ,
					to_char (tbl_os.data_digitacao,'DD/MM/YYYY')  AS data_digitacao    ,
					to_char (tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura     ,
					to_char (tbl_os.data_conserto,'DD/MM/YYYY')   AS data_conserto     ,
					to_char (tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento   ,
					to_char (tbl_os.finalizada,'DD/MM/YYYY')      AS data_finalizada   ,
					to_char (tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf           ,
					data_abertura - data_nf                       AS dias_uso          ,
					tbl_produto.produto                                                ,
					tbl_produto.referencia                       AS produto_referencia ,
					tbl_linha.nome                               AS linha_nome         ,
					tbl_produto.descricao                        AS produto_descricao  ,
					tbl_produto.origem                           AS origem             ,";
				if($login_fabrica == 3){
					$sql .= "tbl_produto.garantia                AS garantia           ,";
					$sql .= "tbl_cliente_garantia_estendida.garantia_mes      AS garantia_estendida           ,";
				}
				$sql .="
					tbl_defeito_constatado.defeito_constatado    AS defeito_constatado_id,
					tbl_defeito_constatado.descricao             AS defeito_constatado ,
					tbl_posto_fabrica.codigo_posto                                     ,
					tbl_posto.nome AS nome_posto                                       ,
					tbl_posto.pais AS posto_pais                                       ,
					tbl_tipo_atendimento.codigo                  AS ta_codigo          ,
					tbl_tipo_atendimento.descricao               AS ta_descricao       ,
					tbl_causa_defeito.descricao                  AS causa_defeito      ,
					tbl_causa_defeito.codigo                     AS causa_defeito_codigo,
					tbl_os_campo_extra.campos_adicionais
					$sql_valor
			FROM      tbl_os
			JOIN tbl_os_extra   on tbl_os.os=tbl_os_extra.os AND tbl_os_extra.i_fabrica=tbl_os.fabrica
			$sql_join
			JOIN      tbl_produto             ON  tbl_os.produto              = tbl_produto.produto AND tbl_produto.fabrica_i = tbl_os.fabrica
			JOIN      tbl_posto               ON  tbl_os.posto                 = tbl_posto.posto
			JOIN      tbl_posto_fabrica       ON  tbl_posto.posto              = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN      tbl_linha               ON  tbl_linha.linha              = tbl_produto.linha AND tbl_linha.fabrica = $login_fabrica
			";
			if($login_fabrica == 3){
				$sql .= " LEFT JOIN tbl_cliente_garantia_estendida ON tbl_cliente_garantia_estendida.numero_serie = tbl_os.serie ";
			}
			$sql .="
			LEFT JOIN tbl_os_produto          ON  tbl_os.os                    = tbl_os_produto.os
			LEFT JOIN tbl_defeito_constatado  ON  tbl_os.defeito_constatado    = tbl_defeito_constatado.defeito_constatado
			LEFT JOIN tbl_tipo_atendimento USING(tipo_atendimento)
			LEFT JOIN tbl_causa_defeito ON tbl_os.causa_defeito = tbl_causa_defeito.causa_defeito
			LEFT JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os AND tbl_os_campo_extra.fabrica = $login_fabrica
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.excluida IS NOT TRUE
			$sql_cond_dig
			$sql_cond ";

	} else {

		$sql =" SELECT  tbl_os.sua_os                                                                   ,
                        tbl_os.os                                                                       ,
                        tbl_os.tecnico_nome                                                             ,
                        tbl_os.tecnico                                                                  ,
                        tbl_os.consumidor_nome                                                          ,
                        tbl_os.consumidor_cpf                                                           ,
                        tbl_os.consumidor_fone                                                          ,
                        tbl_os.consumidor_estado                                                        ,
                        tbl_os.revenda_nome                                                             ,
                        tbl_os.serie                                                                    ,
                        tbl_os.pecas                                                                    ,
                        tbl_os.mao_de_obra                                                              ,
                        tbl_os.nota_fiscal                                                              ,
                        tbl_os.solucao_os                                                               ,
                        tbl_os_extra.tipo_troca                                                         ,
                        to_char (tbl_os.data_digitacao,'DD/MM/YYYY')        AS data_digitacao           ,
                        to_char (tbl_os.data_abertura,'DD/MM/YYYY')         AS data_abertura            ,
                        to_char (tbl_os.data_conserto,'DD/MM/YYYY')         AS data_conserto            ,
                        to_char (tbl_os.data_fechamento,'DD/MM/YYYY')       AS data_fechamento          ,
                        to_char (tbl_os.finalizada,'DD/MM/YYYY')            AS data_finalizada          ,
                        to_char (tbl_os.data_nf,'DD/MM/YYYY')               AS data_nf                  ,
                        data_abertura - data_nf                             AS dias_uso                 ,
                        tbl_os_item.preco                                   AS precounitario            ,
                        tbl_os_item.qtde                                    AS qtdeunitario             ,
                        tbl_produto.produto                                                             ,
                        tbl_produto.referencia                              AS produto_referencia       ,
                        linha_produto.nome                                  AS linha_produto_descricao  ,
                        tbl_produto.descricao                               AS produto_descricao        ,
                        tbl_produto.origem                                  AS origem                   ,
				";

				if($login_fabrica == 20){
					$sql .= "
                        tbl_os.consumidor_email,";
					$sql .= "
                        familia_produto.descricao                           AS familia_produto_descricao     ,\n";
				}
				if($login_fabrica == 3){
					$sql .= "
                        tbl_produto.garantia                                AS garantia                 ,\n";
					$sql .= "
                        tbl_cliente_garantia_estendida.garantia_mes         AS garantia_estendida       ,\n";
				}
				$sql .="
                        tbl_peca.referencia                                 AS peca_referencia          ,
                        tbl_peca.descricao                                  AS peca_descricao           ,\n";
                if($login_fabrica == 20){
					$sql .= "
                        familia_peca.descricao                              AS familia_peca_descricao   ,
                    ";
                }
                $sql .= "
                        tbl_servico_realizado.descricao                     AS servico                  ,
                        tbl_defeito_constatado.defeito_constatado           AS defeito_constatado_id    ,
                        tbl_defeito_constatado.descricao                    AS defeito_constatado       ,
                        TO_CHAR (tbl_os_item.digitacao_item,'DD/MM')        AS data_digitacao_item      ,
                        tbl_posto_fabrica.codigo_posto                                                  ,
                        tbl_posto.nome AS nome_posto                                                    ,
                        tbl_posto.pais AS posto_pais                                                    ,
                        tbl_tecnico.nome AS tecnico_2                                                   ,
                        tbl_tipo_atendimento.codigo                         AS ta_codigo                ,
                        tbl_tipo_atendimento.descricao                      AS ta_descricao             ,
                        tbl_causa_defeito.descricao                         AS causa_defeito            ,
			tbl_causa_defeito.codigo                            AS causa_defeito_codigo     
			$sql_valor
			$campos_ped_fat
                FROM    tbl_os
                JOIN    tbl_os_extra                    ON  tbl_os.os                                   = tbl_os_extra.os
                                                        AND tbl_os_extra.i_fabrica                      = tbl_os.fabrica
                $sql_join
                JOIN    tbl_produto                     ON  tbl_os.produto                              = tbl_produto.produto AND tbl_produto.fabrica_i = tbl_os.fabrica
                JOIN    tbl_posto                       ON  tbl_os.posto                                = tbl_posto.posto
                JOIN    tbl_posto_fabrica               ON  tbl_posto.posto                             = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                JOIN    tbl_linha linha_produto         ON  linha_produto.linha                         = tbl_produto.linha AND linha_produto.fabrica = $login_fabrica
                                                        
			";

			//AND linha_produto.fabrica                       = $login_fabrica
        if($login_fabrica == 3){
				$sql .= "
           LEFT JOIN    tbl_cliente_garantia_estendida  ON  tbl_cliente_garantia_estendida.numero_serie = tbl_os.serie ";
        }
			$sql .="
           LEFT JOIN    tbl_os_produto                  ON  tbl_os.os                                   = tbl_os_produto.os";
        if($login_fabrica == 20 ){
            /*$sql .= "
                JOIN    tbl_familia familia_produto     ON  tbl_produto.familia                         = familia_produto.familia
                                                        AND familia_produto.fabrica                     = $login_fabrica ";*/

            $sql .= "
                JOIN    tbl_familia familia_produto     ON  tbl_produto.familia                         = familia_produto.familia";
        }
        $sql .="
		   LEFT JOIN    tbl_os_item                     ON  tbl_os_produto.os_produto                   = tbl_os_item.os_produto AND tbl_os_item.fabrica_i=$login_fabrica
		   LEFT JOIN    tbl_peca                        ON  tbl_os_item.peca                            = tbl_peca.peca AND tbl_peca.fabrica = tbl_os_item.fabrica_i ";
        if($login_fabrica == 20 ){
            /*$sql .= "
           LEFT JOIN    tbl_familia familia_peca        ON  familia_peca.familia                        = tbl_peca.familia_peca
                                                        AND familia_peca.fabrica                        = $login_fabrica
                ";*/

            $sql .= "
           LEFT JOIN    tbl_familia familia_peca        ON  familia_peca.familia                        = tbl_peca.familia_peca AND familia_peca.fabrica = $login_fabrica
                                                        
                ";

        }
        $sql .= "
           LEFT JOIN    tbl_defeito_constatado          ON  tbl_os.defeito_constatado                   = tbl_defeito_constatado.defeito_constatado
		   LEFT JOIN    tbl_servico_realizado           ON  tbl_os_item.servico_realizado               = tbl_servico_realizado.servico_realizado
		   LEFT JOIN    tbl_tipo_atendimento            ON  tbl_os.tipo_atendimento                     = tbl_tipo_atendimento.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica
		   LEFT JOIN    tbl_causa_defeito               ON  tbl_os.causa_defeito                        = tbl_causa_defeito.causa_defeito
		   LEFT JOIN    tbl_tecnico                     ON  tbl_tecnico.tecnico                         = tbl_os.tecnico
                                                        AND tbl_tecnico.posto                           = tbl_os.posto
							AND tbl_tecnico.fabrica                         = $login_fabrica
		$sql_ped_fat
                WHERE   tbl_os.fabrica  = $login_fabrica
                AND     tbl_os.excluida IS NOT TRUE
			$sql_cond_dig
			$sql_cond ";
	}
	if (strlen($posto) > 0)             $sql .= " AND tbl_os.posto = $posto ";
	if (strlen($uf) > 0)                $sql .= " AND tbl_posto.estado = '$uf' ";
	if (strlen($produto_ref) > 0)       $sql .= " AND tbl_produto.referencia = '$produto_ref' " ;

	if ($login_fabrica == 20) {
		if (strlen($linha) > 0)             $sql .= " AND tbl_produto.linha = '$linha' " ;
		if (strlen($origem) > 0)            $sql .= " AND tbl_produto.origem = '$origem' " ;
		if (strlen($familia) > 0)            $sql .= " AND tbl_produto.familia = '$familia' " ;

	}

	if (strlen($pais) > 0)              $sql .= " AND tbl_posto.pais = '$pais' " ;
	if (strlen($tipo_atendimento) > 0)  $sql .= " AND tbl_os.tipo_atendimento = '$tipo_atendimento' " ;
	$sql .= "  ORDER BY tbl_os.sua_os ";
	#echo nl2br($sql); exit;
	$res = pg_query($con,$sql);

	$numero_registros = pg_num_rows($res);

	if ($numero_registros > 0) {

		$data = date ("d-m-Y-H-i");
		$arquivo_nome = "relatorio_os_digitada-$login_fabrica-$ano-$mes-$data.";
		$arquivo_nome.= (in_array($login_fabrica, $fabricas_xls)) ? 'xls':'txt';

		$path     = __DIR__."/xls";
		$path_tmp = '/tmp/';

		$mkdir = "mkdir -p -m 777 /tmp/";

		$arquivo_completo     = $path . $arquivo_nome;
		$arquivo_completo_tmp = $path_tmp . $arquivo_nome;

		$fp = fopen ($arquivo_completo_tmp,"w");

		/* IGOR HD 6161 - 23/10/2007*/
		if ($login_fabrica == 20) {
			$tipo_atendimento_hdr = "\tTipo Atendimento";
			$valor_mo_hdr = "\tVLR TOTAL MO";
			$valor_pecas_hdr = "\tVLR TOT. PEÇAS";
			$tot_mo_peca = "\tVLR TOT.";
			$titulo_pais_hdr = " \tPAÍS";

            $familia_produto    = "\tFAMÍLIA PRODUTO";
            $linha_produto      = "\tLINHA PRODUTO";
            $familia_peca       = "\tFAMÍLIA PEÇA";
			# HD 32405 - Francisco
			$qtdeheader			= "\tQTDE.";
			$valpecaheader		= "\tVALOR PEÇA";

			//HD 19805 28/5/2008
			$identificacao_hdr = " \tIDENTIFICAÇÃO";
			$valor_ut_hdr = " \tVALOR DE UT";

			//HD 14953
			if($entra_extrato=='SIM'){

				$data_extrato_hdr = "\tEXTRATO";
				$data_extrato_hdr .= "\tDATA ABERTURA EXTRATO";
				$data_extrato_hdr .= "\tDATA DE APROVAÇÃO EXTRATO";
			}

		}

		if ($login_fabrica == 3) {//HD 38403 15/9/2008
			$nota_fiscal_hdr = " \tGARANTIA ESTENDIDA";
			$nota_fiscal_hdr .= " \tNF DE COMPRA";
		}

		if($login_fabrica == 123){
			$nota_fiscal_hdr .= " \tNOTA FISCAL";
		}

		if($login_fabrica == 20 ){
			$reparo_hdr = " \tREPARO";
			$label_cpf_hdr = " \tCPF/CNPJ";
		}else{
			$reparo_hdr = " \tIDENTIFICAÇÃO";
		}
		$revenda= "";
		if($login_fabrica == 3){
			$revenda_hdr = " \tREVENDA";
		}

		if ($login_fabrica == 59) {
			$consumidor_estado_hdr = "\tESTADO";
			$tecnico_nome_hdr  = "\tTÉCNICO";
			$hd_data_conserto_hdr = "\tCONSERTO";
			$origem_os = "\tORIGEM";

			#HD 337758 - INICIO
			$data_relatorio_hdr = $campoRelatorio;
			#HD 337758 - FIM
		} else {
			$data_relatorio_hdr = '';
		}

		/**
		 * Prepared statments
		 */
		$prepare = pg_prepare($con, "query_descricao_servico_realizado", 'SELECT descricao from tbl_servico_realizado where servico_realizado= $1 limit 1');
		$prepare = pg_prepare($con, "query_ut_defeito_constatado", 'SELECT tbl_produto_defeito_constatado.unidade_tempo FROM tbl_produto_defeito_constatado WHERE defeito_constatado = $1 AND produto = $2');
		$prepare = pg_prepare($con, "query_nome_tecnico", 'SELECT nome FROM tbl_tecnico WHERE tecnico = $1');
		$prepare = pg_prepare($con, "query_pecas", "SELECT tbl_os_item.os_item, tbl_peca.referencia, tbl_peca.descricao, tbl_servico_realizado.descricao as servico, TO_CHAR (tbl_os_item.digitacao_item,'DD/MM') AS data_digitacao_item FROM tbl_os_item JOIN tbl_peca USING (peca) JOIN tbl_os_produto USING (os_produto) JOIN tbl_servico_realizado USING(servico_realizado) WHERE tbl_os_produto.os = $1");

		$linhas_arquivo = array();
		$max_pecas = array();

		while ($row = pg_fetch_array($res)) {
			$linha_arquivo = '';

			$sua_os             = $row['sua_os'];

			#HD 100410
			if ($os_por_linha_com_pecas){
				$os                 = $row['os'];
				$campos_adicionais = json_decode($row['campos_adicionais'],true);
			}

			$tipo_troca         = $row['tipo_troca'];
			$tecnico_nome       = $row['tecnico_nome'];
			$tecnico			= $row['tecnico'];
			$consumidor_nome    = $row['consumidor_nome'];
			$consumidor_cpf     = $row['consumidor_cpf'];
			$consumidor_fone    = $row['consumidor_fone'];
			$consumidor_estado  = $row['consumidor_estado'];
			$revenda_nome       = $row['revenda_nome'];
			$serie              = $row['serie'];
			$nota_fiscal        = $row['nota_fiscal'];
			if($login_fabrica == 3){
				$garantia           = $row['garantia'];
				$garantia_estendida = $row['garantia_estendida'];
			}
			$data_digitacao     = $row['data_digitacao'];
			$data_abertura      = $row['data_abertura'];
			$data_conserto      = $row['data_conserto'];
			$data_fechamento    = $row['data_fechamento'];
			$data_finalizada    = $row['data_finalizada'];
			$data_nf            = $row['data_nf'];
			$dias_uso           = $row['dias_uso'];
			$produto_referencia = $row['produto_referencia'];
			$produto_descricao  = $row['produto_descricao'];
			$tecnico_2  		= $row['tecnico_2'];
			if (!$os_por_linha_com_pecas) {
				$peca_referencia    = $row['peca_referencia'];
				$peca_descricao     = $row['peca_descricao'];
				$servico            = $row['servico'];
			}

			$codigo_posto       = $row['codigo_posto'];
			$nome_posto         = $row['nome_posto'];
			$defeito_constatado	= $row['defeito_constatado'];

			//TAKASHI COLOCOU CAMPO DIGITACAO DO ITEM
			if(!$os_por_linha_com_pecas) {
				$data_digitacao_item= $row['data_digitacao_item'];
			}

			$posto_pais         = $row['posto_pais'];
			$ta_codigo          = $row['ta_codigo'];
			$ta_descricao       = $row['ta_descricao'];

			if($login_fabrica == 123){
				$conhecimento = $row['conhecimento'];
				$pedido = $row['pedido'];
				$nota_envio = $row['nota_envio'];
				$emissao = $row['emissao'];
			}

			$xsolucao = '';
			$unidade_tempo = '';

			if ($login_fabrica == 20) {
				$linha_produto_descricao    = $row['linha_produto_descricao'];
				$familia_produto_descricao  = $row['familia_produto_descricao'];
				$familia_peca_descricao     = $row['familia_peca_descricao'];
				$origem                     = $row['origem'];
				$causa_defeito              = $row['causa_defeito']; // HD 19805 28/5/2008
				$causa_defeito_codigo       = $row['causa_defeito_codigo'];
				$solucao_os                 = $row['solucao_os']; // HD 19805 28/5/2008
				$produto                    = $row['produto']; // HD 19805 28/5/2008
				$defeito_constatado_id      = $row['defeito_constatado_id'];

				$email_consumidor           = $row['consumidor_email'];

				$xres = pg_execute($con, "query_descricao_servico_realizado", array($solucao_os));
				if (pg_num_rows($xres)) {
					$xsolucao = trim(pg_fetch_result($xres, 0, 'descricao'));
				}

				$res2 = pg_execute($con, "query_ut_defeito_constatado", array($defeito_constatado_id, $produto));
				if (pg_num_rows($res2)) {
					$unidade_tempo = trim(pg_fetch_result($res2, 0, 'unidade_tempo'));
				}

				$precounitario = $row['precounitario'];
				$qtdeunitario  = $row['qtdeunitario'];

			}

			$tecnico_nome = '';

			if (strlen($tecnico) > 0) {
				$res_tecnico = pg_execute($con, "query_nome_tecnico", array($tecnico));

				if (pg_num_rows($res_tecnico)) {
					$tecnico_nome = pg_fetch_result($res_tecnico, 0, 'nome');
				}
			}

			//HD 14953
			if($login_fabrica==20 and $entra_extrato=='SIM'){
				$exportado     = $row['exportado'];
				$extrato       = $row['extrato'];
				$data_aprovado = $row['data_aprovado'];
			}

			if ($login_fabrica == 1) $sua_os = $codigo_posto.$sua_os;

			if ($login_fabrica == 20){
				//HD 17838 16/4/2008
				$produto_referencia = str_pad($produto_referencia, 0);
			}

			$linha_arquivo.= "$sua_os\t";

			if ($login_fabrica == 20) {
				//$ta_codigo==4  troca em garantia
				//$tipo_troca==1 troca em cortesia comercial
				if ($ta_codigo == 4 AND $tipo_troca == 1) {
					$ta_codigo    = 00;
					$ta_descricao = traduz("Troca em Cortesia Comercial");
				}
				$linha_arquivo.= "$ta_codigo - $ta_descricao\t";
			}

			if ($login_fabrica == 20) {
				$linha_arquivo.= "$consumidor_cpf\t";
			}

			$linha_arquivo.= "$consumidor_nome\t";

			if ($login_fabrica == 59) {
				$linha_arquivo.= "$consumidor_estado\t";
			}

			$linha_arquivo.= "$consumidor_fone\t";

			if ($login_fabrica == 3) {
				$linha_arquivo.= "$revenda_nome\t";
			}

			$linha_arquivo.= "$serie\t";
			$linha_arquivo.= "$data_digitacao\t";
			$linha_arquivo.= "$data_abertura\t";

			if ($login_fabrica == 59) {
				$linha_arquivo.= "$data_conserto\t";
				$linha_arquivo .= strtoupper($campos_adicionais['origem'])."\t";
			}

			$linha_arquivo.= "$data_fechamento\t";
			$linha_arquivo.= "$data_finalizada\t";

			#HD 337758 - INICIO // Verifica se a busca teve mais campos, que estão condicionados à fabrica 59, caso sim, traz o resultado
			if ($login_fabrica == 59 && $campoBusca) {
				fputs($fp, $row[$campoBusca] . "\t");
				$linha_arquivo.= $row[$campoBusca] . "\t";
			}
			#HD 337758 - FIM

			if(in_array($login_fabrica,array(3,123)) ) {
/*
				$explodeDataAbertura = explode("-", $data_abertura);

				$dataGarantiaNormal = date('Y-m-d',mktime(0,0,0, $explodeDataAbertura[1] + $garantia, $explodeDataAbertura[2],$explodeDataAbertura[0]));

				$dataGarantiaEstendida = date('Y-m-d',mktime(0,0,0, $explodeDataAbertura[1] + $garantia_estendida, $explodeDataAbertura[2],$explodeDataAbertura[0]));

				if(strtotime($data_abertura) < strtotime($dataGarantiaEstendida) || strtotime($data_abertura) > strtotime($dataGarantiaNormal)){
					if(!empty($garantia_estendida)){
						$linha_arquivo.= "$garantia_estendida\t";
					}else{
						$linha_arquivo.= "0\t";
					}
				}else{
					$linha_arquivo.= "0\t";
				}*/

				if($login_fabrica ==3){
					$linha_arquivo.= "$garantia_estendida\t";
				}

				$linha_arquivo.= "$nota_fiscal\t";
			}

			$linha_arquivo.= "$data_nf\t";

			if ($login_fabrica == 59) {
				$linha_arquivo.= "$tecnico_nome\t";
			}

			$linha_arquivo.= "$dias_uso\t";
			$linha_arquivo.= "\"$produto_referencia\"\t";
			$linha_arquivo.= "$produto_descricao\t";
            if($login_fabrica == 20){
				$linha_arquivo.= "$familia_produto_descricao\t";
                $linha_arquivo.= "$linha_produto_descricao\t";
            }
			# 100410 exibe todas as referencias de peças e descrições das mesma em ma linha só
			if ($os_por_linha_com_pecas) {
				$res_x = pg_execute($con, "query_pecas", array($os));

				$peca_referencia     = "";
				$peca_descricao      = "";
				$servico             = "";
				$data_digitacao_item = "";

				$vet_peca_referencia = array();
				$vet_peca_descricao  = array();
				$vet_peca = array();

				if (pg_num_rows($res_x) > 0) {
					$vet_result = pg_fetch_all($res_x);
					$max_pecas[] = pg_num_rows($res_x);

					foreach ($vet_result as $row_x) {
						$servico			   = $row_x['servico'];
						$a_data_digitacao[]	   = $row_x['data_digitacao_item'];

						$vet_peca[] = $row_x['referencia'];
						$vet_peca[] = $row_x['descricao'];
					}

					$escreve_peca = implode("\t", $vet_peca);

					$linha_arquivo.= '@_PECAS@' . $escreve_peca . '@_PECAS@' . "\t";

					$data_digitacao_item = implode(',', $a_data_digitacao);
					unset($a_data_digitacao);
					unset($vet_result);
				} else {
					$linha_arquivo.= '@_PECAS@' . "\t\t" . '@_PECAS@' . "\t";
				}

			} else {
				$linha_arquivo.= "$peca_referencia\t";
				$linha_arquivo.= "$peca_descricao\t";
			}

			if ($login_fabrica == 20) {
				$linha_arquivo.= "$familia_peca_descricao\t";
                $linha_arquivo.= "$origem\t";
				if($sua_os == $sua_os_anterior){
					$mao_de_obra  = 0;
					$pecas  = 0;
				}else{
					$mao_de_obra = $row['mao_de_obra'];
					$pecas = $row['pecas'];
				}

				$vet_pecas[$sua_os] = $pecas;
				$vet_mao_de_obra[$sua_os] = $mao_de_obra;

				# HD 32405 - Francisco
				$valpecaunica = number_format($precounitario,2,',','.');
				$v_mo = number_format($vet_mao_de_obra[$sua_os],2,',','.');
				$v_pec = number_format($vet_pecas[$sua_os],2,',','.');

				$linha_arquivo.= "$qtdeunitario\t";
				$linha_arquivo.= "$valpecaunica\t";
				$linha_arquivo.= "$v_mo\t";
				$linha_arquivo.= "$v_pec\t";
				$tot_mo_peca_vl = $vet_mao_de_obra[$sua_os] + $vet_pecas[$sua_os];
				$tot_mo_peca_vl = number_format($tot_mo_peca_vl,2,',','.');

				$linha_arquivo.= "$tot_mo_peca_vl\t";

				//DEPOIS DE IMPRIMIR, APAGA O VALOR PARA NÃO DUPLICAR QUANTO TIVER VARIAS OS_ITEM
				$vet_pecas[$sua_os]= "";
				$vet_mao_de_obra[$sua_os]= "";
				$tot_mo_peca_vl = "";

			}

			$linha_arquivo.= "$data_digitacao_item\t";

			if ($login_fabrica == 20) {
				$linha_arquivo.= "$xsolucao\t";
				$linha_arquivo.= "$causa_defeito_codigo - $causa_defeito\t";
				$linha_arquivo.= "$unidade_tempo\t";
			}

			$linha_arquivo.= "$defeito_constatado\t";

			if ($login_fabrica <> 20){
				$linha_arquivo.= "$servico\t";
			}

			$linha_arquivo.= "$codigo_posto\t";
			$linha_arquivo.= "$nome_posto\t";
			if ($login_fabrica == 3) {
				$linha_arquivo.= "$tecnico_2\t";
			}

			if ($login_fabrica == 20) {
				$linha_arquivo.= "$posto_pais\t";
				//HD 14953
				if ($entra_extrato == 'SIM') {
					$linha_arquivo.= "$extrato\t";
					$linha_arquivo.= "$exportado\t";
					$linha_arquivo.= "$data_aprovado\t";
				}
				$linha_arquivo.= "$email_consumidor \t";
			}

			if($login_fabrica == 123){
				$linha_arquivo .= "$pedido\t$nota_envio\t$emissao\t$conhecimento\t";
			}

			$linhas_arquivo[] = $linha_arquivo;
			unset($linha_arquivo);

			$sua_os_anterior = $sua_os;
		}

		$header  = "OS$tipo_atendimento_hdr$label_cpf_hdr\tCONSUMIDOR$consumidor_estado_hdr\tTELEFONE$revenda_hdr\tNº SÉRIE\tDIGITAÇÃO\tABERTURA$hd_data_conserto_hdr $origem_os\tFECHAMENTO\tFINALIZADA$data_relatorio_hdr";
		$header .= "$nota_fiscal_hdr\tDATA NF$tecnico_nome_hdr\tDIAS EM USO\tPRODUTO REFERÊNCIA\tPRODUTO DESCRIÇÃO";
        $header .= "$familia_produto$linha_produto";

		if ($os_por_linha_com_pecas) {
			$final_for = max($max_pecas);
			unset($max_pecas);
			for ($j = 0; $j < $final_for; $j++) {
				$header.= "\tPEÇA REFERÊNCIA - ". ($j+1);
				$header.= "\tPEÇA DESCRIÇÃO - " . ($j+1);
			}
		} else {
			$header.= "\tPEÇA REFERÊNCIA\tPEÇA DESCRIÇÃO";
            $header .= "$familia_peca";
		}
		if ($login_fabrica == 20) {
			$header.= "\tORIGEM";
        }
		$header.= $qtdeheader.$valpecaheader.$valor_mo_hdr.$valor_pecas_hdr.$tot_mo_peca;

		$header.= "\tDATA ITEM$identificacao_hdr\tDEFEITO CONSTATADO$valor_ut_hdr $reparo_hdr\tCÓDIGO POSTO\tRAZÃO SOCIAL$titulo_pais_hdr";
		if ($login_fabrica == 20) {
			if ($entra_extrato == 'SIM') $header.= "$data_extrato_hdr";

			$header .= "\tEMAIL CONSUMIDOR";
		}
		if($login_fabrica == 3)
			$header.= "\tTÉCNICO RESPONSÁVEL";

		if($login_fabrica == 123){
			$header .= "\tPEDIDO\tNF ENVIO\tEMISSÃO\tCONHECIMENTO";
		}

		fputs ($fp, "$header\r\n");

		foreach ($linhas_arquivo as $escreve) {
			if ($os_por_linha_com_pecas) {
				preg_match('/@_PECAS@(.*)@_PECAS@/', $escreve, $match);

				$arr_temp = explode("\t", $match[1]);
				$exp_temp = array_pad($arr_temp, $final_for*2, "");

				$replace_temp = implode("\t", $exp_temp);

				unset($arr_temp);
				unset($exp_temp);

				$escreve = preg_replace('/@_PECAS@.*@_PECAS@/', $replace_temp, $escreve);

			}
			fwrite($fp, $escreve . "\r\n");
		}
		unset($all);
		unset($linhas_arquivo);

		fclose($fp);

		flush();

		echo "<tr>";
		echo "<td nowrap align='left'>";

		if($login_fabrica == 20) {
			echo `cd $path_tmp && mv $arquivo_nome $path`;
		} else {
			echo `cd $path_tmp && rm -f $arquivo_nome.zip ; zip -o $arquivo_nome.zip $arquivo_nome 1> /dev/null && mv $arquivo_nome.zip $path`;
		}

		echo "<table width='700' border='0' cellspacing='2' cellpadding='2' align='center' class='texto_avulso'>";
		echo "<tr>";
		if (in_array($login_fabrica, $fabricas_xls)) {
			echo "<td align='center'>".traduz("Download em formato XLS (Colunas separadas com TABULAÇÃO")."<br><a href='xls/$arquivo_nome.zip'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>".traduz("Clique aqui para fazer o download")."</font></a> </td>";
		} else {
			if($login_fabrica == 20){
				echo "<td align='center'>".traduz("Download em formato TXT (Colunas separadas com TABULAÇÃO")."<br><a href='xls/$arquivo_nome' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>".traduz("Clique aqui para fazer o download")."</font></a> </td>";
			} else {
				echo "<td align='center'>Download em formato ZIP.<br />".traduz(
				"Para visualizar este arquivo será necessário descompactá-lo!")."
				<br />".traduz("O arquvo zipado contém colunas separadas com tabulação")."<br>
				<input type='button' value='Download do Arquivo' onclick=\"window.location='xls/$arquivo_nome.zip'\"
				 </td>";
			}

		}
		echo "</tr>";
		echo "</table>";

		echo "</td>";
		echo "</table>";
		echo "<br>";

		/* if(file_exists($arquivo_completo_tmp)){
			echo $arquivo_completo_tmp."<br />";
			echo $arquivo_completo;
			system("mv $arquivo_completo_tmp $arquivo_completo");
		} */

	} else {
		echo "<br><center>";
		echo traduz("Não existem OS neste período!");
		echo "</center>";
	}
}

echo "<br />";

include "rodape.php";

?>
