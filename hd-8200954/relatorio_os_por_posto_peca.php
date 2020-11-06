<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "funcoes.php";

#Para a rotina automatica - Fabio - HD 11750
$gera_automatico = trim($_GET["gera_automatico"]);

if (isset($argv[1])) {
	parse_str($argv[1], $get);
	$gera_automatico = $get['gera_automatico'];
}

$msg_erro = "";
$agendar  = 0;
$verifica = 1;

$layout_menu = "os";
$title = "RELATÓRIO DE OS DIGITADAS";

include "cabecalho.php";
include "javascript_pesquisas.php";

# Fábricas que utilizam o formato XLS
$fabricas_xls = array(59, 94);

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
$posto_codigo     = $login_posto;
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
$tecnico	 = trim($_POST['tecnico']);
$data_referencia	 = trim($_POST['data_referencia']);
# HD 337758 - FIM

if (strlen(trim($_GET["btn_acao"])) > 0)		$btn_acao = trim($_GET["btn_acao"]);
if (strlen(trim($_GET["posto_codigo"])) > 0)	$posto_codigo = $login_posto;
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
if (strlen(trim($_GET["tecnico"])) > 0)			$tecnico= trim($_GET['tecnico']);
if (strlen(trim($_GET["data_referencia"])) > 0)	$data_referencia= trim($_GET['data_referencia']);
# HD 337758 - FIM

if (isset($get)) {
	$login_fabrica    = $get['login_fabrica'];
	$login_admin      = $get['login_admin'];
	$btn_acao         = $get['btn_acao'];
	$posto_codigo     = $login_posto;
	$ano              = $get['ano'];
	$mes              = $get['mes'];
	$produto_ref      = $get['produto_referencia'];
	$produto_desc     = $get['produto_descricao'];
	$tipo_atendimento = $get['tipo_atendimento'];
	$tipo_data        = $get['tipo_data'];
	$pais             = $get['pais'];
	$linha            = $get['linha'];
	$origem           = $get['origem'];
	$entra_extrato    = $get['entra_extrato'];
	$pais             = $get['pais'];
	$data_in          = $get['data_in'];
	$data_fl          = $get['data_fl'];
	$data_inicial     = $get['data_inicial'];
	$data_final       = $get['data_final'];
	$tecnico          = $get['tecnico'];
	$data_referencia  = $get['data_referencia'];
}

if (strlen($btn_acao) > 0) {

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
		}
}


#HD 15551
if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
	/**
	 * @since HD 341949
	 */ 
	$agendar = 0; // BATATA
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
				$aviso = '<br/><br/>AVISO: Os relatórios anteriores agendados pelo seu usuário foram cancelados em razão do agendamento atual.';
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
			echo "<div style='width:735px; padding:  5px; margin: 0 auto;' class='sucesso' align='center'>O relatório foi agendado e será processado nesta madrugada.<br/> Um email lhe será enviado ao final do processo.$aviso</div><br/>";
		}
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
		<td colspan='5'>Parâmetros de Pesquisa</td>
	</tr>

		<?php if($login_fabrica == 59) { ?>
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
		<?php }?>	
	<tr>
		<td>&nbsp;</td>
		<td align="left">Referência</td>
		<td align="left">Descrição Produto</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td align="left"><input type="text" name="produto_referencia" value="<? echo $produto_referencia ?>" size="15" maxlength="20" class='frm'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os_posto.produto_referencia,document.frm_os_posto.produto_descricao,'referencia')" alt='Clique aqui para pesquisar pela referência do produto' style='cursor:pointer;'></td>
		<td align="left"><input type="text" name="produto_descricao" value="<? echo $produto_descricao ?>" size="45" maxlength="50" class='frm'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os_posto.produto_referencia,document.frm_os_posto.produto_descricao,'descricao')" alt='Clique aqui para pesquisar pela descrição do produto' style='cursor:pointer;'></td>
	</tr>
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="4" align="center">
			<input type="button" style='background:url(admin/imagens_admin/btn_confirmar.gif); width:95px; cursor:pointer;' value="&nbsp;" onclick="javascript: if (document.frm_os_posto.btn_acao.value == '' ) { document.frm_os_posto.btn_acao.value='continuar' ; document.frm_os_posto.submit() } else { alert ('Aguarde submissão') }" ALT="Confirmar" border='0'>
		<td>
	</tr>
</table>
<br />
</form>
<br /><?php

if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0 and $agendar == 0) {
//  Fabricas que querem uma linha por OS, com as peças como colunas adicionais
$os_por_linha_com_pecas = in_array($login_fabrica, array(15, 59));

	

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
			JOIN tbl_os_extra   on tbl_os.os=tbl_os_extra.os AND tbl_os_extra.i_fabrica=tbl_os.fabrica
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

		$sql =	"SELECT tbl_os.sua_os                                                 ,
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
				tbl_tecnico.nome AS tecnico_2 						,
				tbl_tipo_atendimento.codigo                       AS ta_codigo          ,
				tbl_tipo_atendimento.descricao                    AS ta_descricao       ,
				tbl_causa_defeito.descricao                       AS causa_defeito      ,
				tbl_causa_defeito.codigo                          AS causa_defeito_codigo
				$sql_valor																
			FROM tbl_os
			JOIN tbl_os_extra ON tbl_os.os=tbl_os_extra.os AND tbl_os_extra.i_fabrica = tbl_os.fabrica
			$sql_join
			JOIN      tbl_produto             ON  tbl_os.produto                = tbl_produto.produto AND tbl_produto.fabrica_i = tbl_os.fabrica
			JOIN      tbl_posto               ON  tbl_os.posto                  = tbl_posto.posto
			JOIN      tbl_posto_fabrica       ON  tbl_posto.posto               = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN      tbl_linha               ON  tbl_linha.linha               = tbl_produto.linha AND tbl_linha.fabrica = $login_fabrica
			LEFT JOIN tbl_os_produto          ON  tbl_os.os                     = tbl_os_produto.os
			LEFT JOIN tbl_os_item             ON  tbl_os_produto.os_produto     = tbl_os_item.os_produto AND tbl_os_item.fabrica_i=$login_fabrica
			LEFT JOIN tbl_peca                ON  tbl_os_item.peca              = tbl_peca.peca AND tbl_peca.fabrica = tbl_os_item.fabrica_i
			LEFT JOIN tbl_defeito_constatado  ON  tbl_os.defeito_constatado     = tbl_defeito_constatado.defeito_constatado
			LEFT JOIN tbl_servico_realizado   ON  tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
			LEFT JOIN tbl_tipo_atendimento    ON  tbl_os.tipo_atendimento       = tbl_tipo_atendimento.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica
			LEFT JOIN tbl_causa_defeito       ON  tbl_os.causa_defeito          = tbl_causa_defeito.causa_defeito
			LEFT JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_os.tecnico AND tbl_tecnico.posto = tbl_os.posto AND tbl_tecnico.fabrica = {$login_fabrica}
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.excluida IS NOT TRUE
			$sql_cond_dig
			$sql_cond ";
	}

	if (strlen($posto_codigo) > 0)             $sql .= " AND tbl_os.posto = $posto_codigo ";
	if (strlen($produto_ref) > 0)       $sql .= " AND tbl_produto.referencia = '$produto_ref' " ;

	if ($login_fabrica == 20) {
		if (strlen($linha) > 0)             $sql .= " AND tbl_produto.linha = '$linha' " ;
		if (strlen($origem) > 0)            $sql .= " AND tbl_produto.origem = '$origem' " ;
	}

	if (strlen($pais) > 0)              $sql .= " AND tbl_posto.pais = '$pais' " ;
	if (strlen($tipo_atendimento) > 0)  $sql .= " AND tbl_os.tipo_atendimento = '$tipo_atendimento' " ;
	$sql .= " ORDER BY tbl_os.sua_os";
	$res = pg_query($con,$sql);
	$numero_registros = pg_num_rows($res);

	if ($numero_registros > 0) {

		$data = date ("d-m-Y-H-i");
		$arquivo_nome = "relatorio_os_digitada-$login_fabrica-$ano-$mes-$data.";
		$arquivo_nome.= (in_array($login_fabrica, $fabricas_xls)) ? 'xls':'txt';

		
		$path     = "/www/assist/www/xls";
		// $path     = "/mnt/home/otavio/public_html/assist/xls/";
		$path_tmp = '/tmp/';

		$mkdir = "mkdir -p -m 777 /tmp/";

		$arquivo_completo     = $path . $arquivo_nome;
		$arquivo_completo_tmp = $path_tmp . $arquivo_nome;

		$fp = fopen ($arquivo_completo_tmp,"w");

		$revenda= "";

		if ($login_fabrica == 59) {
			$consumidor_estado_hdr = "\tESTADO";
			$tecnico_nome_hdr  = "\tTÉCNICO";
			$hd_data_conserto_hdr = "\tCONSERTO";

			#HD 337758 - INICIO
			$data_relatorio_hdr = $campoRelatorio;
			#HD 337758 - FIM
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

			$xsolucao = '';
			$unidade_tempo = '';

			$tecnico_nome = '';

			if (strlen($tecnico) > 0) {
				$res_tecnico = pg_execute($con, "query_nome_tecnico", array($tecnico));

				if (pg_num_rows($res_tecnico)) {
					$tecnico_nome = pg_fetch_result($res_tecnico, 0, 'nome');
				}
			}

			

			$linha_arquivo.= "$sua_os\t";


			$linha_arquivo.= "$consumidor_nome\t";

			if ($login_fabrica == 59) {
				$linha_arquivo.= "$consumidor_estado\t";
			}

			$linha_arquivo.= "$consumidor_fone\t";

			

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
				}

			} else {
				$linha_arquivo.= "$peca_referencia\t";
				$linha_arquivo.= "$peca_descricao\t";
			}

			$linha_arquivo.= "$data_digitacao_item\t";

			$linha_arquivo.= "$defeito_constatado\t";

			$linha_arquivo.= "$codigo_posto\t";
			$linha_arquivo.= "$nome_posto\t";
			$linha_arquivo.= "$posto_pais\t";

			
			$linhas_arquivo[] = $linha_arquivo;
			unset($linha_arquivo);

			$sua_os_anterior = $sua_os;
		}
		$header = "OS\tCONSUMIDOR$consumidor_estado_hdr\tTELEFONE\tNº SÉRIE\tDIGITAÇÃO\tABERTURA$hd_data_conserto_hdr\tFECHAMENTO\tFINALIZADA$data_relatorio_hdr";
		$header.= "\tDATA NF\tTÉCNICO NOME\tDIAS EM USO\tPRODUTO REFERÊNCIA\tPRODUTO DESCRIÇÃO";

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
		$header.= "\tDATA ITEM\tDEFEITO CONSTATADO\tCÓDIGO POSTO\tRAZÃO SOCIAL\tPOSTO PAÍS";
		
		fputs ($fp, $header."\r\n");

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
		
		
			echo `cd $path_tmp && rm -f $arquivo_nome.zip ; zip -o $arquivo_nome.zip $arquivo_nome 1> /dev/null && mv $arquivo_nome.zip $path`;
		

		echo "<table width='700' border='0' cellspacing='2' cellpadding='2' align='center' class='texto_avulso'>";
		echo "<tr>";
		if (in_array($login_fabrica, $fabricas_xls)) {
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

		/* if(file_exists($arquivo_completo_tmp)){
			echo $arquivo_completo_tmp."<br />";
			echo $arquivo_completo;
			system("mv $arquivo_completo_tmp $arquivo_completo");
		} */

	} else {
		echo "<br><center>";
		echo "Não existem OS neste período!";
		echo "</center>";
	}
}

echo "<br />";

include "rodape.php";

?>
