<?php

//set_time_limit(0);
ini_set("memory_limit","512M");

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "auditoria";
#Para a rotina automatica - Fabio - HD 11750
$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico') {
	include "autentica_admin.php";
}

include "gera_relatorio_pararelo_include.php";
include 'funcoes.php';

$msg_erro = "";

$layout_menu = "auditoria";
$title = "RELATÓRIO DE OS DIGITADAS";

include "cabecalho.php";
include "javascript_pesquisas.php";

?>

<script language="JavaScript" src="js/cal2.js"></script>
<script language="JavaScript" src="js/cal_conf2.js"></script>

<script language="JavaScript">
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
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
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
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
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
	include "javascript_calendario.php";
	?>
	<script>
	$(document).ready(function(){
		$("#data_inicial").datePicker({startDate : "01/01/2000"});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").datePicker({startDate : "01/01/2000"});
		$("#data_final").maskedinput("99/99/9999");
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
$origem           = trim($_POST['origem']);
$hoje			  = date("Y");
//HD 14953
$entra_extrato    = trim($_POST['entra_extrato']);
$tipo_data        = trim($_POST['tipo_data']);
$pais             = trim($_POST['pais']);
# HD 27525
$data_in         = trim($_POST['data_in']);
$data_fl         = trim($_POST['data_fl']);
# HD 337758 - INICIO
$data_inicial	 = trim($_POST['data_inicial']);
$data_final	 = trim($_POST['data_final']);
$estado	 = trim($_POST['estado']);
$tecnico	 = trim($_POST['tecnico']);
$data_referencia	 = trim($_POST['data_referencia']);
# HD 337758 - FIM

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


if (strlen($btn_acao) > 0) {

	# HD 27525
	if ($login_fabrica  == 25) {
	// 10/09/2010 MLG - Ajustando esagens de erro ao novo padrão
		if (!$data_in or !$data_fl) $msg_erro = "Data inválida!";
		if (!$msg_erro) {
			list($d, $m, $y) = explode("/", $data_inicial);
			if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";

			list($d, $m, $y) = explode("/", $data_inicial);
			if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
		}
	} else {

		if (strlen($posto_codigo) == 0 OR strlen($posto_nome) == 0) {
			if ($login_fabrica  <> 20) {
				$msg_erro .= "É obrigatório selecionar um posto. ";
			}
		}

		#HD 337758 - INICIO
		if($login_fabrica == 59){
			if (empty($data_inicial) or empty($data_final))
				$msg_erro = "Data inválida!";

			if (!$msg_erro) {

				list($di, $mi, $yi) = explode("/", $data_inicial);

				$ano = date('Y');
				$mes = date('m');

				if(!checkdate($mi,$di,$yi))
					$msg_erro = "Data Inválida";

				list($df,$mf,$yf) = explode("/", $data_final);

				if(!checkdate($mf,$df,$yf))
					$msg_erro = "Data Inválida.";

				if(strlen($msg_erro)==0){
					$aux_data_inicial = "$yi-$mi-$di";
					$aux_data_final = "$yf-$mf-$df";
				}
				if(strlen($msg_erro)==0){
					if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
						$msg_erro = "Data Inválida.";
					}
				}

				if(strlen($msg_erro)==0){
					if (strtotime($aux_data_inicial) < strtotime($aux_data_final.' -3 month')) {
						$msg_erro = 'O intervalo entre as datas não pode ser maior que 3 meses.';
					}
				 }

			}
		}else{
		#HD 337758 - FIM

			if ($login_fabrica == 20) {
				if (!$data_in or !$data_fl) $msg_erro = "Data inválida!";

				if (!$msg_erro) {
					list($di, $mi, $yi) = explode("/", $data_in);
					if(!checkdate($mi,$di,$yi)) $msg_erro = "Data Inválida";

					list($df, $mf, $yf) = explode("/", $data_fl);
					if(!checkdate($mf,$df,$yf)) $msg_erro = "Data Inválida";
				}

				$data_inicial = $yi . '-' . $mi . '-' . $di;
				$data_final   = $yf . '-' . $mf . '-' . $df;

				if (strtotime($data_inicial) < strtotime($data_final.' - 1 year')) {
					$msg_erro = 'O intervalo entre as datas não pode ser maior que 1 ano.';
				}

			} else {
				if (strlen($ano) == 0 AND strlen($mes) == 0){
					$msg_erro = " O preenchimento do Ano é obrigatório! ";
				}

				if(($ano < 1990) || ($ano > $hoje) || (strlen($ano)< 4)){
					$msg_erro = " O Ano informado está incorreto! ";
				}
			}

		}

	}

	if ($entra_extrato=='SIM' and strlen($pais) == 0) {
		$msg_erro="Para verificar as OSs que entraram em extrato, deveria selecionar o país a ser consultado";
	}

}

#HD 15551
if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
	include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
	include "gera_relatorio_pararelo_verifica.php";
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
		<td colspan='5'>Parâmetros de Pesquisa</td>
	</tr><?php
	# HD 27525
	if ($login_fabrica == 25) {
		include "javascript_calendario.php"; ?>

		<script type="text/javascript" charset="utf-8">
			$(function() {
				$("input[name^='data']").maskedinput("99/99/9999");
			});
		</script>
		<tr style='font-weight:bold'>
			<td>Data inicial</td>
			<td>Data final</td>
		</tr>
		<tr>
			<td><input class='frm' type='text' name='data_in' value='<?=$data_in?>' size='12' maxlength='20'></td>
			<td><input class='frm' type='text' name='data_fl' value='<?=$data_fl?>' size='12' maxlength='20'></td>
		</tr>
<?	} else { ?>

		<?php if($login_fabrica == 59) {?>
			<tr>
				<td width="60">&nbsp;</td>
				<td>Data inicial</td>
				<td>Data final</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td><input class='frm' type='text' id='data_inicial' name='data_inicial' value='<?=$data_inicial?>' size='12' maxlength='10'></td>
				<td><input class='frm' type='text' id='data_final' name='data_final' value='<?=$data_final?>' size='12' maxlength='10'></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>Data de Referência</td>
				<td>Técnico</td>
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
							echo '<option value="">Nenhum técnico cadastrado</option>';
						}?>
					</select>

				</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>Estado</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>
					<select name="estado" id="estado" class="frm">
						<option value="">Selecione</option>
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
			<td align="left">Código do Posto </td>
			<td align="left">Nome do Posto</td>
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

			<?php if ($login_fabrica == 20) { ?>

				<?php include_once 'javascript_calendario.php'; ?>

				<script language='javascript'>

					$(function(){
						$('.mask_date').datePicker({startDate:'01/01/2000'}).maskedinput("99/99/9999");
						$('input[id=data_in]').datePicker({startDate:'01/01/2000'}).maskedinput("99/99/9999");
						$('input[id=data_fl]').datePicker({startDate:'01/01/2000'}).maskedinput("99/99/9999");
					});

				</script>

				<tr>
					<td>&nbsp;</td>
					<td>Data inicial</td>
					<td>Data final</td>
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
				<td align="left">Ano *</td>
				<td align="left">Mês</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td align="left">
					<input class="frm" type="text" name="ano" size="13" maxlength="4" value="<? echo $ano ?>" onkeyup="re = /\D/g; this.value = this.value.replace(re, '');">
				</td>
				<td align="left"><?php
					$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");?>
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
			</tr>
			<?php } ?>

		<?php
		endif;
	}

	if ($login_fabrica == 20) {
		// MLG 2009-08-04 HD 136625
		$sql   = 'SELECT pais,nome FROM tbl_pais';
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
			<td>País</td>
			<td>Tipo Atendimento</td>
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
				<td >Linha</td>
				<td>Origem</td>
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
						echo "<option value=''>ESCOLHA</option>\n";

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
						<option value="">ESCOLHA</option>
						<option value="Nac" <? if ($origem == "Nac") echo " SELECTED "; ?>>Nacional</option>
						<option value="Imp" <? if ($origem == "Imp") echo " SELECTED "; ?>>Importado</option>
						<option value="USA" <? if ($origem == "USA") echo " SELECTED "; ?>>Importado USA</option>
						<option value="Asi" <? if ($origem == "Asi") echo " SELECTED "; ?>>Importado Asia</option>
					</select>
				</td>
			</tr><?php
		}

	}?>
	<tr>
		<td>&nbsp;</td>
		<td align="left">Referência</td>
		<td align="left">Descrição Produto</td>
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
					<legend>Tipo de Data</legend>
					<table border='0' width='100%'>
						<tr align="left">
							<td>
								<input type='radio' name='tipo_data'  id='tipo_datae' value='exportacao' <? if($tipo_data=='exportacao') echo "checked"; ?>>Data Exportação &nbsp;
							</td>
						</tr>
						<tr align="left">
							<td>

								<input type='radio' name='tipo_data' value='geracao'<? if($tipo_data=='geracao' or strlen($tipo_data)==0) echo "checked"; ?>>
								Data Geração &nbsp;
							</td>
						</tr>
						<tr align="left">
							<td>
								<input type='radio' name='tipo_data' value='aprovacao'<? if($tipo_data=='aprovacao') echo "checked"; ?>>
								Data Aprovação
							</td>

						</tr>
					</table>
				</fieldset>
			</td>

			<td align='left'>
				<fieldset style="width:200px;">
					<legend>OS que Entraram em Extrato</legend>
					<table width='100%'>
					<tr>
						<td>
							<input type='radio' name='entra_extrato' value='NAO' <? if($entra_extrato=='NAO' or strlen($entra_extrato) ==0 ) echo "checked"; ?>> Não &nbsp;

							<input type='radio' name='entra_extrato' value='SIM'<? if($entra_extrato=='SIM') echo "checked"; ?>> SIM
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
			<input type="button" style='background:url(imagens_admin/btn_confirmar.gif); width:95px; cursor:pointer;' value="&nbsp;" onclick="javascript: if (document.frm_os_posto.btn_acao.value == '' ) { document.frm_os_posto.btn_acao.value='continuar' ; document.frm_os_posto.submit() } else { alert ('Aguarde submissão') }" ALT="Confirmar" border='0'>
		<td>
	</tr>
</table>
<br />
</form>
<br /><?php

if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0) {
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

	$sql_join = '';
	$sql_cond = '';

	//TAKASHI COLOCOU CAMPO DIGITACAO DO ITEM
	//TULIO - Tornar obrigatoria digitacao de DATA INICIAL E FINAL - junho/2007
	//HD14953
	if ($entra_extrato == 'SIM' and $login_fabrica == 20) {
		$sql_join = " JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
					  JOIN tbl_extrato ON tbl_os_extra.extrato = tbl_extrato.extrato and tbl_extrato.fabrica = $login_fabrica
					  JOIN tbl_extrato_extra ON tbl_extrato.extrato = tbl_extrato_extra.extrato";
		if ($pais == 'BR') {
			$sql_valor=", to_char(tbl_extrato_extra.exportado,'DD/MM/YYYY') as exportado ";

			switch ($tipo_data) {
				case 'exportacao':
					$sql_cond="AND tbl_extrato_extra.exportado BETWEEN '$data_inicial' AND '$data_final'";
					break;
				case 'geracao':
					$sql_cond="AND tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final'";
					break;
				case 'aprovacao':
					$sql_cond="AND tbl_extrato.aprovado BETWEEN '$data_inicial' AND '$data_final'";
					break;

			}

		} else if($pais <> 'BR') {

			$sql_valor=", to_char(tbl_extrato.data_geracao,'DD/MM/YYYY') as exportado ";

			switch ($tipo_data) {
				case 'geracao':
					$sql_cond="AND tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final'";
					break;
				case 'aprovacao':
					$sql_cond="AND tbl_extrato.aprovado BETWEEN '$data_inicial' AND '$data_final'";
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
		if ($entra_extrato == 'NAO' and $login_fabrica == 20) {
			$sql_cond_dig ="AND tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'";
		} else if($login_fabrica <> 20) {
			$sql_cond_dig ="AND tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'";
		}

		# HD 27525
		if ($login_fabrica == 25) {
			$sql_cond_dig ="AND tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'";
		}
	}

	#HD 100410 select modificado para ter somente uma os por linha
	if ($os_por_linha_com_pecas) {
		$sql = "SELECT DISTINCT tbl_os.sua_os                                                  ,
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
					tbl_produto.origem                           AS origem             ,
					tbl_defeito_constatado.defeito_constatado    AS defeito_constatado_id,
					tbl_defeito_constatado.descricao             AS defeito_constatado ,
					tbl_posto_fabrica.codigo_posto                                     ,
					tbl_posto.nome AS nome_posto                                       ,
					tbl_posto.pais AS posto_pais                                       ,
					tbl_tipo_atendimento.codigo                  AS ta_codigo          ,
					tbl_tipo_atendimento.descricao               AS ta_descricao       ,
					tbl_causa_defeito.descricao                  AS causa_defeito      ,
					tbl_causa_defeito.codigo                     AS causa_defeito_codigo
					$sql_valor
			FROM      tbl_os
			JOIN tbl_os_extra   on tbl_os.os=tbl_os_extra.os
			$sql_join
			JOIN      tbl_produto             ON  tbl_os.produto              = tbl_produto.produto AND tbl_produto.fabrica_i = tbl_os.fabrica
			JOIN      tbl_posto               ON  tbl_os.posto                 = tbl_posto.posto
			JOIN      tbl_posto_fabrica       ON  tbl_posto.posto              = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN      tbl_linha               ON  tbl_linha.linha              = tbl_produto.linha AND tbl_linha.fabrica = $login_fabrica
			LEFT JOIN tbl_os_produto          ON  tbl_os.os                    = tbl_os_produto.os
			LEFT JOIN tbl_defeito_constatado  ON  tbl_os.defeito_constatado    = tbl_defeito_constatado.defeito_constatado
			LEFT JOIN tbl_tipo_atendimento USING(tipo_atendimento)
			LEFT JOIN tbl_causa_defeito ON tbl_os.causa_defeito = tbl_causa_defeito.causa_defeito
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.excluida IS NOT TRUE
			$sql_cond_dig
			$sql_cond ";

	} else {

		$sql =	"/* -=================
						Query antiga, fragmentada em três outras abaixo
					=================-
		 		 */
				SELECT tbl_os.sua_os                                                 ,
				tbl_os.os                                                               ,
				tbl_os.tecnico_nome                                                     ,
				tbl_os.tecnico                                                          ,
				tbl_os.consumidor_nome                                                  ,
				tbl_os.consumidor_cpf                                                   ,
				tbl_os.consumidor_fone                                                  ,
				tbl_os.consumidor_estado                                                ,
				tbl_os.revenda_nome                                                     ,
				tbl_os.serie                                                            ,
				tbl_os.pecas                                                            ,
				tbl_os.mao_de_obra                                                      ,
				tbl_os.nota_fiscal                                                      ,
				tbl_os.solucao_os                                                       ,
				tbl_os_extra.tipo_troca                                                 ,
				to_char (tbl_os.data_digitacao,'DD/MM/YYYY')      AS data_digitacao     ,
				to_char (tbl_os.data_abertura,'DD/MM/YYYY')       AS data_abertura      ,
				to_char (tbl_os.data_conserto,'DD/MM/YYYY')       AS data_conserto      ,
				to_char (tbl_os.data_fechamento,'DD/MM/YYYY')     AS data_fechamento    ,
				to_char (tbl_os.finalizada,'DD/MM/YYYY')          AS data_finalizada    ,
				to_char (tbl_os.data_nf,'DD/MM/YYYY')             AS data_nf            ,
				data_abertura - data_nf                           AS dias_uso           ,
				tbl_os_item.preco                                 AS precounitario      ,
				tbl_os_item.qtde                                  AS qtdeunitario       ,
				tbl_produto.produto                                                     ,
				tbl_produto.referencia                            AS produto_referencia ,
				tbl_linha.nome                                    AS linha_nome         ,
				tbl_produto.descricao                             AS produto_descricao  ,
				tbl_produto.origem                                AS origem             ,
				tbl_peca.referencia                               AS peca_referencia    ,
				tbl_peca.descricao                                AS peca_descricao     ,
				tbl_servico_realizado.descricao                   AS servico            ,
				tbl_defeito_constatado.defeito_constatado         AS defeito_constatado_id,
				tbl_defeito_constatado.descricao                  AS defeito_constatado ,
				TO_CHAR (tbl_os_item.digitacao_item,'DD/MM')      AS data_digitacao_item,
				tbl_posto_fabrica.codigo_posto                                          ,
				tbl_posto.nome AS nome_posto                                            ,
				tbl_posto.pais AS posto_pais                                            ,
				tbl_tipo_atendimento.codigo                       AS ta_codigo          ,
				tbl_tipo_atendimento.descricao                    AS ta_descricao       ,
				tbl_causa_defeito.descricao                       AS causa_defeito      ,
				tbl_causa_defeito.codigo                          AS causa_defeito_codigo
				$sql_valor
			FROM tbl_os
			JOIN tbl_os_extra ON tbl_os.os=tbl_os_extra.os
			$sql_join
			JOIN      tbl_produto             ON  tbl_os.produto                = tbl_produto.produto AND tbl_produto.fabrica_i = tbl_os.fabrica
			JOIN      tbl_posto               ON  tbl_os.posto                  = tbl_posto.posto
			JOIN      tbl_posto_fabrica       ON  tbl_posto.posto               = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN      tbl_linha               ON  tbl_linha.linha               = tbl_produto.linha AND tbl_linha.fabrica = $login_fabrica
			LEFT JOIN tbl_os_produto          ON  tbl_os.os                     = tbl_os_produto.os
			LEFT JOIN tbl_os_item             ON  tbl_os_produto.os_produto     = tbl_os_item.os_produto
			LEFT JOIN tbl_peca                ON  tbl_os_item.peca              = tbl_peca.peca
			LEFT JOIN tbl_defeito_constatado  ON  tbl_os.defeito_constatado     = tbl_defeito_constatado.defeito_constatado
			LEFT JOIN tbl_servico_realizado   ON  tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
			LEFT JOIN tbl_tipo_atendimento    ON  tbl_os.tipo_atendimento       = tbl_tipo_atendimento.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica
			LEFT JOIN tbl_causa_defeito       ON  tbl_os.causa_defeito          = tbl_causa_defeito.causa_defeito
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.excluida IS NOT TRUE
			$sql_cond_dig
			$sql_cond ";


		/**
		 * condições extras - vão na primeira query
		 */
		$join_tbl_os = '';
		$cond_tbl_os = '';

		$join_posto   = 0;
		$join_produto = 0;

		if (!empty($posto)) {
			$join_posto = 1;
			$cond_tbl_os.= " AND tbl_os.posto = $posto ";
		}

		if (!empty($estado)) {
			$join_posto = 1;
			$cond_tbl_os.= " AND tbl_posto.estado = '$estado' ";
		}

		if (!empty($produto_ref)) {
			$join_produto = 1;
			$cond_tbl_os.= " AND tbl_produto.referencia = '$produto_ref' ";
		}

		if ($login_fabrica == 20) {
			if (!empty($linha)) {
				$join_produto = 1;
				$cond_tbl_os.= " AND tbl_produto.linha = '$linha' " ;
			}

			if (!empty($origem)) {
				$join_produto = 1;
				$cond_tbl_os.= " AND tbl_produto.origem = '$origem' ";
			}
		}

		if (!empty($pais)) {
			$join_posto = 1;
			$cond_tbl_os.= "  AND tbl_posto.pais = '$pais' ";
		}

		if (!empty($tipo_atendimento)) {
		    $cond_tbl_os.= " AND tbl_os.tipo_atendimento = '$tipo_atendimento' ";
		}

		if ($join_posto == 1) {
		    $join_tbl_os.= " JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto ";
		}

		if ($join_produto == 1) {
		    $join_tbl_os.= " JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = tbl_os.fabrica ";
		}


		$sql_tbl_os = "SELECT tbl_os.sua_os                                                 ,
							tbl_os.os                                                               ,
							tbl_os.tecnico_nome                                                     ,
							tbl_os.tecnico                                                          ,
							tbl_os.consumidor_nome                                                  ,
							tbl_os.consumidor_cpf                                                   ,
							tbl_os.consumidor_fone                                                  ,
							tbl_os.consumidor_estado                                                ,
							tbl_os.revenda_nome                                                     ,
							tbl_os.serie                                                            ,
							tbl_os.pecas                                                            ,
							tbl_os.mao_de_obra                                                      ,
							tbl_os.nota_fiscal                                                      ,
							tbl_os.solucao_os                                                       ,
							to_char (tbl_os.data_digitacao,'DD/MM/YYYY')      AS data_digitacao     ,
							to_char (tbl_os.data_abertura,'DD/MM/YYYY')       AS data_abertura      ,
							to_char (tbl_os.data_conserto,'DD/MM/YYYY')       AS data_conserto      ,
							to_char (tbl_os.data_fechamento,'DD/MM/YYYY')     AS data_fechamento    ,
							to_char (tbl_os.finalizada,'DD/MM/YYYY')          AS data_finalizada    ,
							to_char (tbl_os.data_nf,'DD/MM/YYYY')             AS data_nf            ,
							tbl_os.data_abertura - tbl_os.data_nf             AS dias_uso,
							tbl_os.produto,
							tbl_os.posto,
							tbl_os.defeito_constatado,
							tbl_os.tipo_atendimento,
							tbl_os.causa_defeito,
							tbl_os.fabrica,
							tbl_os_produto.os_produto
						INTO TEMP tmp_roppp_os_$login_admin
						FROM tbl_os
						JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
						$sql_join
						$join_tbl_os
						WHERE tbl_os.fabrica = $login_fabrica
						AND   tbl_os.excluida IS NOT TRUE
						$sql_cond_dig
						$sql_cond
						$cond_tbl_os;

						CREATE INDEX idx_os_roppp_os_$login_admin ON tmp_roppp_os_$login_admin (os);
						CREATE INDEX idx_sua_os_roppp_os_$login_admin ON tmp_roppp_os_$login_admin (sua_os);
						CREATE INDEX idx_fabrica_roppp_os_$login_admin ON tmp_roppp_os_$login_admin (fabrica);
						CREATE INDEX idx_produto_roppp_os_$login_admin ON tmp_roppp_os_$login_admin (produto);
						CREATE INDEX idx_posto_roppp_os_$login_admin ON tmp_roppp_os_$login_admin (posto);
						CREATE INDEX idx_defeito_constatado_roppp_os_$login_admin ON tmp_roppp_os_$login_admin (defeito_constatado);
						CREATE INDEX idx_tipo_atendimento_roppp_os_$login_admin ON tmp_roppp_os_$login_admin (tipo_atendimento);
						CREATE INDEX idx_causa_defeito_roppp_os_$login_admin ON tmp_roppp_os_$login_admin (causa_defeito);
						CREATE INDEX idx_os_produto_roppp_os_$login_admin ON tmp_roppp_os_$login_admin (os_produto);
						";

		$sql_itens = "SELECT tmp_roppp_os_$login_admin.os,
							tbl_os_item.preco AS precounitario ,
							tbl_os_item.qtde AS qtdeunitario ,
							TO_CHAR (tbl_os_item.digitacao_item,'DD/MM') AS data_digitacao_item,
							tbl_os_item.peca,
							tbl_os_item.servico_realizado
					INTO TEMP tmp_ropp_item_$login_admin
					FROM tmp_roppp_os_$login_admin
					JOIN tbl_os_item ON tmp_roppp_os_$login_admin.os_produto = tbl_os_item.os_produto AND tbl_os_item.fabrica_i = $login_fabrica;

					CREATE INDEX idx_os_ropp_item_$login_admin ON tmp_ropp_item_$login_admin (os);
					CREATE INDEX idx_peca_ropp_item_$login_admin ON tmp_ropp_item_$login_admin (peca);
					CREATE INDEX idx_servico_realizado_ropp_item_$login_admin ON tmp_ropp_item_$login_admin (servico_realizado);";

		$sql_resultado = "SELECT tmp_roppp_os_$login_admin.*, tmp_ropp_item_$login_admin.*,
									tbl_os_extra.tipo_troca,
									tbl_produto.produto                                                     ,
									tbl_produto.referencia                            AS produto_referencia ,
									tbl_linha.nome                                    AS linha_nome         ,
									tbl_produto.descricao                             AS produto_descricao  ,
									tbl_produto.origem                                AS origem             ,
									tbl_peca.referencia                               AS peca_referencia    ,
									tbl_peca.descricao                                AS peca_descricao     ,
									tbl_servico_realizado.descricao                   AS servico            ,
									tbl_defeito_constatado.defeito_constatado         AS defeito_constatado_id,
									tbl_defeito_constatado.descricao                  AS defeito_constatado ,
									tbl_posto_fabrica.codigo_posto                                          ,
									tbl_posto.nome AS nome_posto                                            ,
									tbl_posto.pais AS posto_pais                                            ,
									tbl_tipo_atendimento.codigo                       AS ta_codigo          ,
									tbl_tipo_atendimento.descricao                    AS ta_descricao       ,
									tbl_causa_defeito.descricao                       AS causa_defeito      ,
									tbl_causa_defeito.codigo                          AS causa_defeito_codigo
								FROM tmp_roppp_os_$login_admin
								LEFT JOIN tmp_ropp_item_$login_admin ON tmp_ropp_item_$login_admin.os = tmp_roppp_os_$login_admin.os
								JOIN tbl_os_extra ON tmp_roppp_os_$login_admin.os = tbl_os_extra.os
								JOIN      tbl_produto             ON  tmp_roppp_os_$login_admin.produto                = tbl_produto.produto
								JOIN      tbl_posto               ON  tmp_roppp_os_$login_admin.posto                  = tbl_posto.posto
								JOIN      tbl_posto_fabrica       ON  tbl_posto.posto               = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
								JOIN      tbl_linha               ON  tbl_linha.linha               = tbl_produto.linha AND tbl_linha.fabrica = $login_fabrica
								LEFT JOIN tbl_peca                ON  tmp_ropp_item_$login_admin.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica
								LEFT JOIN tbl_defeito_constatado  ON  tmp_roppp_os_$login_admin.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
								LEFT JOIN tbl_servico_realizado   ON  tmp_ropp_item_$login_admin.servico_realizado = tbl_servico_realizado.servico_realizado
								LEFT JOIN tbl_tipo_atendimento    ON  tmp_roppp_os_$login_admin.tipo_atendimento       = tbl_tipo_atendimento.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica
								LEFT JOIN tbl_causa_defeito       ON  tmp_roppp_os_$login_admin.causa_defeito          = tbl_causa_defeito.causa_defeito
								ORDER BY tmp_roppp_os_$login_admin.sua_os;";

		$sql = $sql_tbl_os . ' ' . $sql_itens . ' ' . $sql_resultado;

	}

	//echo nl2br($sql);
	//exit;

	$res = pg_query($con,$sql);
	$numero_registros = pg_num_rows($res);

	if ($numero_registros > 0) {

		$data = date ("d-m-Y-H-i");
		$arquivo_nome = "relatorio_os_digitada-$login_fabrica-$ano-$mes-$data.";
		$arquivo_nome.= ($login_fabrica == 59) ? 'xls':'txt';

		$path             = '/www/assist/www/admin/xls/';
		$path_tmp         = '/tmp/assist/';

		$mkdir = `mkdir -p -m 777 /tmp/assist`;

		$arquivo_completo     = $path . $arquivo_nome;
		$arquivo_completo_tmp = $path_tmp . $arquivo_nome;

		$fp = fopen ($arquivo_completo_tmp,"w");

		/* IGOR HD 6161 - 23/10/2007*/
		if ($login_fabrica == 20) {
			$tipo_atendimento_hdr = "\tTipo Atendimento";
			$valor_mo_hdr = "\tVLR TOTAL MO";
			$valor_pecas_hdr = "\tVLR TOT. PEÇAS";
			$titulo_pais_hdr = " \tPAÍS";

			# HD 32405 - Francisco
			$qtdeheader			= "\tQTDE.";
			$valpecaheader		= "\tVALOR PEÇA";

			//HD 19805 28/5/2008
			$identificacao_hdr = " \tIDENTIFICAÇÃO";
			$valor_ut_hdr = " \tVALOR DE UT";

			//HD 14953
			if($entra_extrato=='SIM'){
				$data_extrato_hdr = "\tDATA EXTRATO";
			}

		}

		if ($login_fabrica == 3) {//HD 38403 15/9/2008
			$nota_fiscal_hdr = " \tNF DE COMPRA";
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
			$data_digitacao     = $row['data_digitacao'];
			$data_abertura      = $row['data_abertura'];
			$data_conserto      = $row['data_conserto'];
			$data_fechamento    = $row['data_fechamento'];
			$data_finalizada    = $row['data_finalizada'];
			$data_nf            = $row['data_nf'];
			$dias_uso           = $row['dias_uso'];
			$produto_referencia = $row['produto_referencia'];
			$produto_descricao  = $row['produto_descricao'];

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

			$xsolucao = '';
			$unidade_tempo = '';

			if ($login_fabrica == 20) {

				$linha_nome            = $row['linha_nome'];
				$origem                = $row['origem'];
				$causa_defeito         = $row['causa_defeito']; // HD 19805 28/5/2008
				$causa_defeito_codigo  = $row['causa_defeito_codigo'];
				$solucao_os            = $row['solucao_os']; // HD 19805 28/5/2008
				$produto               = $row['produto']; // HD 19805 28/5/2008
				$defeito_constatado_id = $row['defeito_constatado_id'];

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
				$exportado          = $row['exportado'];
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
					$ta_descricao = "Troca em Cortesia Comercial";
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
			}

			$linha_arquivo.= "$data_fechamento\t";
			$linha_arquivo.= "$data_finalizada\t";

			#HD 337758 - INICIO // Verifica se a busca teve mais campos, que estão condicionados à fabrica 59, caso sim, traz o resultado
			if ($login_fabrica == 59 && $campoBusca) {
				fputs($fp, $row[$campoBusca] . "\t");
				$linha_arquivo.= $row[$campoBusca] . "\t";
			}
			#HD 337758 - FIM

			if($login_fabrica == 3 ) {
				$linha_arquivo.= "$nota_fiscal\t";
			}

			$linha_arquivo.= "$data_nf\t";

			if ($login_fabrica == 59) {
				$linha_arquivo.= "$tecnico_nome\t";
			}

			$linha_arquivo.= "$dias_uso\t";
			$linha_arquivo.= "\"$produto_referencia\"\t";
			$linha_arquivo.= "$produto_descricao\t";

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

				$pecas = $row['pecas'];
				$mao_de_obra = $row['mao_de_obra'];

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

				//DEPOIS DE IMPRIMIR, APAGA O VALOR PARA NÃO DUPLICAR QUANTO TIVER VARIAS OS_ITEM
				$vet_pecas[$sua_os]= "";
				$vet_mao_de_obra[$sua_os]= "";

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
			$linha_arquivo.= "$pais\t";

			if ($login_fabrica == 20) {
				$linha_arquivo.= "$linha_nome\t";
				$linha_arquivo.= "$origem\t";
				//HD 14953
				if ($entra_extrato == 'SIM') {
					$linha_arquivo.= "$exportado";
				}
			}

			$linhas_arquivo[] = $linha_arquivo;
			unset($linha_arquivo);

		}

		$header = "OS$tipo_atendimento_hdr$label_cpf_hdr\tCONSUMIDOR$consumidor_estado_hdr\tTELEFONE$revenda_hdr\tNº SÉRIE\tDIGITAÇÃO\tABERTURA$hd_data_conserto_hdr\tFECHAMENTO\tFINALIZADA$data_relatorio_hdr";
		$header.= "$nota_fiscal_hdr\tDATA NF$tecnico_nome_hdr\tDIAS EM USO\tPRODUTO REFERÊNCIA\tPRODUTO DESCRIÇÃO";

		if ($os_por_linha_com_pecas) {
			$final_for = max($max_pecas);
			unset($max_pecas);
			for ($j = 0; $j < $final_for; $j++) {
				$header.= "\tPEÇA REFERÊNCIA - ". ($j+1);
				$header.= "\tPEÇA DESCRIÇÃO - " . ($j+1);
			}
		} else {
			$header.= "\tPEÇA REFERÊNCIA\tPEÇA DESCRIÇÃO";
		}

		$header.= $qtdeheader.$valpecaheader.$valor_mo_hdr.$valor_pecas_hdr;
		$header.= "\tDATA ITEM$identificacao_hdr\tDEFEITO CONSTATADO$valor_ut_hdr $reparo_hdr\tCÓDIGO POSTO\tRAZÃO SOCIAL$titulo_pais_hdr";
		if ($login_fabrica == 20) {
			$header.= "\tLINHA\tORIGEM";
			if ($entra_extrato == 'SIM') $header.= "$data_extrato_hdr";
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

		unset($linhas_arquivo);

		fclose($fp);

		flush();

		echo "<tr>";
		echo "<td nowrap align='left'>";

		echo `cd $path_tmp && rm -f $arquivo_nome.zip ; zip -o $arquivo_nome.zip $arquivo_nome 1> /dev/null && mv $arquivo_nome.zip $path`;

		echo "<table width='700' border='0' cellspacing='2' cellpadding='2' align='center' class='texto_avulso'>";
		echo "<tr>";
		if ($login_fabrica == 59) {
			echo "<td align='center'>Download em formato XLS (Colunas separadas com TABULAÇÃO)<br><a href='xls/$arquivo_nome.zip'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>Clique aqui para fazer o download </font></a> </td>";
		} else {
			echo "<td align='center'>Download em formato ZIP.<br />
					Para visualizar este arquivo será necessário descompactá-lo!
					<br /> O arquvo zipado contém colunas separadas com tabulação<br>
					<input type='button' value='Download do Arquivo' onclick=\"window.location='xls/$arquivo_nome.zip'\"
				  </td>";

		}
		echo "</tr>";
		echo "</table>";

		echo "</td>";
		echo "</table>";
		echo "<br>";


	} else {
		echo "<br><center>";
		echo "Não existem OS neste período!";
		echo "</center>";
	}
}

echo "<br />";

include "rodape.php";

?>
