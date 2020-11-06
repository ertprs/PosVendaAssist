<?
include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";
$admin_privilegios="call_center";
include_once "autentica_admin.php";
include_once 'funcoes.php';
include_once '../helpdesk/mlg_funciones.php';

$achou = "";

$os   = $_GET["os"];
$tipo = $_GET["tipo"];

$btn_acao    = trim($_POST["btn_acao"]);
$select_acao = trim($_POST["select_acao"]);
$achou       = trim($_POST["achou"]); 


if (strlen($btn_acao)>0 AND strlen($select_acao)>0) {

	$qtde_os     = trim($_POST["qtde_os"]);
	$observacao  = trim($_POST["observacao"]);

	if(strlen($observacao) > 0){
		$observacao = "' Observação: $observacao '";
	}else{
		$msg_erro["msg"][] = "É necessário que o motivo seja informado.";
	}	

	if (strlen($qtde_os)==0){
		$qtde_os = 0;
	}

	if(!count($msg_erro["msg"])){
		for ($x=0;$x<$qtde_os;$x++){

			$xxos = trim($_POST["check_".$x]);

			if (strlen($xxos) > 0 AND count($msg_erro["msg"]) == 0){

				$res_os = pg_exec($con,"BEGIN TRANSACTION");

				$sql = "SELECT status_os
						FROM tbl_os_status
						WHERE status_os IN (92,93,94)
						AND os = $xxos
						ORDER bY data DESC
						LIMIT 1";
				$res_os = pg_query($con,$sql);
				if (pg_num_rows($res_os)>0){
					$status_da_os = trim(pg_result($res_os,0,status_os));

					if ($login_fabrica == 20){

						$sql = "SELECT contato_email,tbl_os.sua_os, tbl_os.posto, tbl_os.tipo_atendimento,tbl_posto_fabrica.contato_pais
								FROM tbl_posto_fabrica
								JOIN tbl_os            ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
								WHERE tbl_os.os      = $xxos
								AND   tbl_os.fabrica = $login_fabrica";
						$res_x = pg_query($con,$sql);
						$posto_email = pg_result($res_x,0,'contato_email');
						$posto_pais  = pg_result($res_x,0,'contato_pais');
						$sua_os      = pg_result($res_x,0,'sua_os');
						$posto       = pg_result($res_x,0,'posto');

						$sql = "SELECT nome_completo FROM tbl_admin WHERE admin = $login_admin";
						$res_x = pg_query($con,$sql);
						$promotor = pg_result($res_x,0,nome_completo);

						$email_origem  = "pt.garantia@br.bosch.com";
						$email_destino = "$posto_email, helpdesk@telecontrol.com.br";

					}
					if ($status_da_os == 92){
						//Aprovada
						if($select_acao == "93"){

							$sql = "INSERT INTO tbl_os_status
									(os,status_os,data,observacao,admin)
									VALUES ($xxos,93,current_timestamp,$observacao,$login_admin)";

							$res = pg_query($con, $sql);

							
							if(strlen(pg_last_error()) > 0){
								$msg_erro["msg"][] = "Erro ao aprovar a OS: " .pg_last_error();
							}else{

								if ($login_fabrica == 20){

									$assunto       = "OS Aprovada";

									$corpo ="<br>A OS n°$sua_os foi aprovada.\n\n";
									$corpo.="<br>Promotor que concedeu a aprovação: $promotor\n\n";
									if (!empty($observacao) and trim($observacao) <> "NULL"){
										$aux_observacao = str_replace("'", "", $observacao);

										$corpo.="<br>$aux_observacao";
									}
									$corpo.="<br>_______________________________________________\n";
									$corpo_comunicado = $corpo;
									$corpo.="<br>ObS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

									$body_top  = "--Message-boundary\n";
									$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
									$body_top .= "Content-transfer-encoding: 7bIT\n";
									$body_top .= "Content-description: Mail message body\n\n";

									if ( @mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$email_origem." \n $body_top " ) ){

									}
								}
							}
						}

						//Recusada
						if($select_acao == "94"){
							$sql = "INSERT INTO tbl_os_status
									(os,status_os,data,observacao,admin)
									VALUES ($xxos,94,current_timestamp,$observacao,$login_admin)";

							$res = pg_query($con, $sql);

							if(strlen(pg_last_error()) > 0){
								$msg_erro["msg"][] = "Erro ao reprovar a OS: " .pg_last_error();
							}else{

								$sql = "UPDATE tbl_os SET
										excluida  = 't'
									WHERE os = $xxos
									AND fabrica = $login_fabrica ";
								
								$res = pg_query($con,$sql);

								if(strlen(pg_last_error()) > 0){
									$msg_erro["msg"][] = "Erro ao excluir a OS $xxos " . pg_last_error();
								}

								$sql = "UPDATE tbl_os_extra SET
											status_os = 94
										WHERE os = $xxos";

								$res = pg_query($con,$sql);

								if(strlen(pg_last_error()) > 0){
									$msg_erro["msg"][] = "Erro ao atualizar a OS $xxos " . pg_last_error();
								}

								if ($login_fabrica == 20){

									$assunto       = "OS Reprovada";

									$corpo ="<br>A OS n°$sua_os foi reprovada.\n\n";
									$corpo.="<br>Promotor que reprovou: $promotor\n\n";
									if (!empty($observacao) and trim($observacao) != 'NULL'){
										$aux_observacao = str_replace("'", "", $observacao);
										$corpo.="<br> $aux_observacao";
									}
									$corpo.="<br>_______________________________________________\n";
									$corpo_comunicado = $corpo;
									$corpo.="<br>ObS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

									$body_top = "--Message-boundary\n";
									$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
									$body_top .= "Content-transfer-encoding: 7bIT\n";
									$body_top .= "Content-description: Mail message body\n\n";

									if ( @mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$email_origem." \n $body_top " ) ){

									}

								}
							}
						}

						if ($login_fabrica == 20){

							$sql = "INSERT INTO tbl_comunicado (
								fabrica,
								posto,
								mensagem,
								obrigatorio_site,
								ativo,
								pais,
								tipo
							)VALUES(
								$login_fabrica,
								$posto,
								'$corpo_comunicado',
								true,
								true,
								'$posto_pais',
								'Comunicado'

							)";

							$resComunicado = pg_query($con,$sql);
							if (strlen(pg_last_error()) > 0) {
								$msg_erro["msg"][] = "Erro ao registrar o comunicado para o posto: " . pg_last_error();
							}
						}
					}
				}

				if (count($msg_erro["msg"]) == 0){
					$res = pg_exec($con,"COMMIT TRANSACTION");
					$msg_sucesso["msg"][] = "Registro(s) salvo(s) com sucesso";

				}else{
					$res = pg_exec($con,"ROLLBACK TRANSACTION");
				}
			}
		}
	}
}

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if($btn_acao == 'Pesquisar' || $achou == "sim") {
	$data_inicial = trim($_POST['data_inicial']);
	$data_final   = trim($_POST['data_final']);
	$aprova       = trim($_POST['aprova']);
	$promotor     = trim($_POST['promotor_os']);
	$os           = trim($_POST['os']);

	if(!empty($promotor)){
		$sql = "SELECT admin FROM tbl_promotor_treinamento WHERE promotor_treinamento = $promotor";
		$res = pg_query($con,$sql);
		$promotor_os = pg_fetch_result($res,0,'admin');
	}else{
		// $promotor_os = $login_admin;
		$promotor_os = "";
	}

	if (!strlen($data_inicial) or !strlen($data_final)) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data";
	} else {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		} else {
			$xdata_inicial = "{$yi}-{$mi}-{$di} 00:00:00";
			$xdata_final   = "{$yf}-{$mf}-{$df} 23:59:59";

			if (strtotime($xdata_final) < strtotime($xdata_inicial)) {
				$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
				$msg_erro["campos"][] = "data";
			}
		}
	}

	if(!count($msg_erro["msg"]) || $achou == "sim"){
		if (strlen($os)>0){
			$Xos = " AND os = $os ";
		}

		if(strlen($aprova) == 0){
			$aprova = "aprovacao";
			$aprovacao = "92";
		}elseif($aprova=="aprovacao"){
			$aprovacao = "92";
		}elseif($aprova=="aprovadas"){
			$aprovacao = "93";
		}elseif($aprova=="reprovadas"){
			$aprovacao = "94";
		}
	}
}

$layout_menu = 'callcenter';
$title = 'APROVAÇÃO ORDEM DE SERVIÇO DE CORTESIA';
include 'cabecalho_new.php';


$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		var table = new Object();
        table['table'] = '#resultado_os_cortesia';
        table['type'] = 'full';
        $.dataTableLoad(table);

	});

</script>

<script>
function abreObs(os,codigo_posto,sua_os){
	janela = window.open("obs_os_troca.php?os=" + os + "&codigo_posto=" + codigo_posto +"&sua_os=" + sua_os,"formularios",'resizable=1,scrollbars=yes,width=400,height=250,top=0,left=0');
	janela.focus();
}

var ok = false;
var cont=0;
function checkaTodos() {
	f = document.frm_pesquisa2;
	if (!ok) {
		for (i=0; i<f.length; i++){
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = true;
				ok=true;
				cont++;
			}
		}
	}else{
		for (i=0; i<f.length; i++) {
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = false;
				ok=false;
				cont++;
			}
		}
	}
}

function setCheck(theCheckbox,mudarcor,cor){
	if (document.getElementById(theCheckbox)) {
//		document.getElementbyId(theCheckbox).checked = (document.getElementbyId(theCheckbox).checked ? false : true);
	}
	if (document.getElementById(mudarcor)) {
		document.getElementById(mudarcor).style.backgroundColor  = (document.getElementById(theCheckbox).checked ? "#FFF8D9" : cor);
	}
}
</script>
<style>
	.qualquer_coisa a {
		color: white;
	}
</style>
<!-- LEGENDAS hd 14631
echo "<p>";
echo "<div align='center' style='position: relative; left: 10'>";
echo "<table border='0' cellspacing='0' cellpadding='0'>";
echo "<tr height='18'>";
echo "<td nowrap width='18' >";
echo "<span style='background-color:#FDEbD0;color:#FDEbD0;border:1px solid #F8b652'>__</span></td>";
echo "<td align='left'><font size='1'><b>&nbsp;  OS com origem da Intervenção</b></font></td><bR>";
echo "</tr>";
echo "<tr height='18'>";
echo "<td nowrap width='18' >";
echo "<span style='background-color:#99FF66;color:#00FF00;border:1px solid #F8b652'>__</span></td>";
echo "<td align='left'><font size='1'><b>&nbsp;  OS com Observação</b></font></td><bR>";
echo "</tr>";
echo "<tr height='18'>";
echo "<td nowrap width='18' >";
echo "<span style='background-color:#CCFFFF; color:#D7FFE1;border:1px solid #F8b652'>__</span></td>";
echo "<td align='left'><font size='1'><b>&nbsp; Reincidências</b></font></td><bR>";
echo "</tr>";
echo "</table>";
echo "</div>";
echo "</p>"; -->

<?
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?
}
?>

<?
if (count($msg_sucesso["msg"]) > 0) {
?>
    <div class="alert alert-success">
		<h4><?=implode("<br />", $msg_sucesso["msg"])?></h4>
    </div>
<?
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>" class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
		<br/>

		<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_inicial'>Data Inicial</label>
						<div class='controls controls-row'>
							<div class='span4'>
								<h5 class='asteristico'>*</h5>
								<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
							</div>
						</div>
					</div>
				</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'>Data Final</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("numero_os", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='numero_os'>Número OS</label>
					<div class='controls controls-row'>
						<div class='span6'>
							<input type="text" name="os" id="os" class='span12' maxlength="20" value="<?=$os?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("promotor", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='promotor'>Promotor</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<select name="promotor_os" id="promotor_os">
								<option value=""></option>
									<?php
										$sql = "SELECT promotor_treinamento,nome FROM tbl_promotor_treinamento WHERE fabrica = $login_fabrica AND aprova_troca IS TRUE AND pais = 'BR'";
										$res = pg_query($con,$sql);

										if(pg_num_rows($res) > 0){
											for($i=0;$i<pg_num_rows($res);$i++){
												$promotorp = pg_fetch_result($res,$i,'promotor_treinamento');
												$nomep = pg_fetch_result($res,$i,'nome');

												$selected = ($promotor == $promotorp) ? "SELECTED" : "";

												echo "<option value='$promotorp' $selected>$nomep</option>";
											}
										}
									?>
							</select>
						</div>
						<div class='span2'></div>
					</div>
				</div>
			</div>
		</div>
		<div class="row-fluid">
			<div class="span2"></div>
				<div class="span6">
					<div class='control-group ' id="campo_nume+ro_pedido">
							<br><label class='control-label' for='aprova'><b>Mostrar a OS</b></label><br><br>
							<div class="controls controls-row">
								<fieldset>
									<input type="radio" name="aprova" value='aprovacao' <? if(trim($aprova) == 'aprovacao' OR trim($aprova)==0) echo "checked='checked'"; ?>>&nbsp;&nbsp;&nbsp;&nbsp;Em aprovação &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
									<input type="radio" name="aprova" value='aprovadas' <? if(trim($aprova) == 'aprovadas') echo "checked='checked'"; ?>>&nbsp;&nbsp;&nbsp;&nbsp;Aprovadas  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
									<input type="radio" name="aprova" value='reprovadas' <? if(trim($aprova) == 'reprovadas') echo "checked='checked'"; ?>>&nbsp;&nbsp;&nbsp;&nbsp;Reprovadas &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
								</fieldset>
						</div>
					</div>
				</div>	
			<div class="span2"></div>
		</div>						
			
		<p><br/>
		<input type='hidden' name='btn_acao' value=''>
		<input type="button" class='btn' onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='Pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer;" value="Pesquisar" />
		</p><br/>
</form>
</div>
<div style="margin-left: 0.3cm; margin-right: 0.3cm;">
<br>
<?
if ((strlen($btn_acao) > 0 AND !count($msg_erro["msg"]) > 0) || $achou == "sim") {

	/* select os from  tmp_interv_$login_admin; */

	if(strlen($promotor_os) > 0){
		$cond_promotor = " AND tbl_promotor_treinamento.admin = $promotor_os ";
	}else{
		$cond_promotor = "";
	}

	$sql =  "SELECT interv.os
			INTO TEMP tmp_interv_$login_admin
			FROM (
			SELECT
			ultima.os,
			(SELECT status_os FROM tbl_os_status WHERE status_os IN (92,93,94) AND tbl_os_status.fabrica_status = $login_fabrica AND tbl_os_status.os = ultima.os ORDER BY data DESC LIMIT 1) AS ultimo_status
			FROM (SELECT DISTINCT os FROM tbl_os_status WHERE status_os IN (92,93,94) AND tbl_os_status.fabrica_status = $login_fabrica ) ultima
			) interv
			WHERE interv.ultimo_status IN ($aprovacao)
			$Xos
			;

			CREATE INDEX tmp_interv_OS_$login_admin ON tmp_interv_$login_admin(os);

			/* select os from  tmp_interv_$login_admin; */

			SELECT	tbl_os.os                                                   ,
					tbl_os.sua_os                                               ,
					tbl_os.consumidor_nome                                      ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
					tbl_os.fabrica                                              ,
					tbl_os.consumidor_nome                                      ,
					tbl_os.nota_fiscal_saida                                    ,
					to_char(tbl_os.data_nf_saida,'DD/MM/YYYY') AS data_nf_saida ,
					tbl_posto.nome                     AS posto_nome            ,
					tbl_posto_fabrica.codigo_posto                              ,
					tbl_posto_fabrica.contato_estado                            ,
					tbl_produto.referencia             AS produto_referencia    ,
					tbl_produto.descricao              AS produto_descricao     ,
					tbl_produto.voltagem                                        ,
					(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (92,93,94) ORDER BY data DESC LIMIT 1) AS status_os         ,
					(SELECT observacao FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (92,93,94) ORDER BY data DESC LIMIT 1) AS status_observacao,
					(SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os.os = tbl_os_status.os AND status_os IN (92,93,94) ORDER BY data DESC LIMIT 1) AS status_descricao
				FROM tmp_interv_$login_admin X
				JOIN tbl_os ON tbl_os.os = X.os
				JOIN tbl_produto              ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica
				JOIN tbl_posto                ON tbl_os.posto        = tbl_posto.posto
				JOIN tbl_posto_fabrica        ON tbl_posto.posto     = tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_promotor_treinamento ON tbl_promotor_treinamento.promotor_treinamento = tbl_os.promotor_treinamento {$cond_promotor}											 
				WHERE tbl_os.fabrica = $login_fabrica
				";
	if($login_fabrica==20){
		$sql .= " AND tbl_os.tipo_atendimento <> 13 and tbl_os.tipo_atendimento <> 66 ";
	}
	if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0) {
		$sql .= " AND tbl_os.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final'
				ORDER BY tbl_posto_fabrica.codigo_posto,tbl_os.os ";
	}

if ($ip == "201.76.77.3"){
	//echo nl2br($sql);
}
	$res = pg_query($con,$sql);

	//echo nl2br($sql);

	if(pg_num_rows($res)>0){
		$achou = "sim"; ?>

		<form name='frm_pesquisa2' METHOD='POST' ACTION='<?=$PHP_SELF;?>'>

			<input type='hidden' name='data_inicial'   value='<?=$data_inicial;?>'>
			<input type='hidden' name='data_final'     value='<?=$data_final;?>'>
			<input type='hidden' name='aprova'         value='<?=$aprova;?>'>

			<table id="resultado_os_cortesia" class='table table-striped table-bordered table-fixed'>
				<thead>
					<tr class='titulo_coluna' >
						<th class='qualquer_coisa'>
							<a onclick='javascript: checkaTodos();' title='Selecionar todos' style='cursor: hand;'>Todas
							</a>
						</th>
						<th>OS</th>
						<th>Digitação</th>
						<th>Abertura</th>
						<th>Posto</th>
						<th>Produto</th>
						<th>Descrição</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>

		<?
		/*$cores = '';
		$qtde_intervencao = 0;*/

		for ($x=0; $x<pg_numrows($res);$x++){

			$os						= pg_result($res, $x, os);
			$sua_os					= pg_result($res, $x, sua_os);
			$codigo_posto			= pg_result($res, $x, codigo_posto);
			$posto_nome				= pg_result($res, $x, posto_nome);
			$consumidor_nome		= pg_result($res, $x, consumidor_nome);
			$produto_referencia		= pg_result($res, $x, produto_referencia);
			$produto_descricao		= pg_result($res, $x, produto_descricao);
			$produto_voltagem		= pg_result($res, $x, voltagem);
			$data_digitacao			= pg_result($res, $x, data_digitacao);
			$data_abertura			= pg_result($res, $x, data_abertura);
			$status_os				= pg_result($res, $x, status_os);
			$status_observacao		= pg_result($res, $x, status_observacao);
			$status_descricao		= pg_result($res, $x, status_descricao);

			/*$cores++;
			$cor = ($cores % 2 == 0) ? "#FEFEFE": '#E8EbEE';*/

				echo "
					<tr id='linha_$x'>
						<td class='tal'>
				";

				if($status_os==92){
					echo "
							<input type='checkbox' name='check_$x' id='check_$x' value='$os' onclick=\"setCheck('check_$x','linha_$x','$cor');\" 
						";
					if (count($msg_erro["msg"]) > 0){
						if (strlen($_POST["check_".$x])>0){
							echo " CHECKED ";
						}
					}
					echo ">";
				} ?>
			
					</td>
					<td class='tac'>
						<a href='os_press.php?os=<?=$os;?>' target='_blank'><?=$sua_os;?></a>
					</td>
					<td class='tac'><?=$data_digitacao;?></td>
					<td class='tac'><?=$data_abertura;?></td>
					<td class='tal'  title='<?=$codigo_posto;?> - <?=$posto_nome;?>'>
						<? echo "$codigo_posto - ".substr($posto_nome,0,20);?>
					</td>
					<td class='tal' title='Produto: <?=$produto_referencia;?>' style='cursor: help'>
						<?=$produto_referencia;?>
					</td>
					<td class="tal" title='Produto: <?=$produto_referencia;?> - <?=$produto_descricao;?>' style='cursor: help'>
						<?=$produto_descricao;?>
					</td>
					<td title='Observação do Promotor: <?=$status_observacao;?>'>
						<?=$status_descricao;?>
					</td>
			<!-- <td style='font-size: 9px; font-family: verdana'><a href=\"javascript: abreObs('$os','$codigo_posto','$sua_os')\">VER</a></td> -->
			</tr>
		<? } ?>
				</tbody>
				<tfoot>
					<tr class="titulo_coluna">

					<?	if(trim($aprova) == 'aprovacao'){ ?>

						<th colspan='8' class='tal'>
							<img border='0' src='imagens/seta_checkbox.gif' align='left'>&nbsp;

					 		Com Marcados:&nbsp;

							<select name='select_acao' style="height: 24px;">
								<option value=''></option>
								<option value='93' <? if ($_POST["select_acao"] == "93")  echo " selected"; echo ">APROVADO PARA PAGAMENTO"; ?> 
								</option>
								<option value='94' <? if ($_POST["select_acao"] == "94") echo " selected"; echo ">GARANTIA RECUSADA"; ?>
								</option>
							</select>&nbsp;

							Motivo:&nbsp;

							<input type='text' style="height: 15px;" name='observacao' id='observacao' value='' <? if ($_POST["select_acao"] == "19") echo " DISAbLED "; ?>>&nbsp;

							<button class='btn btn-small' onclick='javascript: document.frm_pesquisa2.submit();'>Gravar</button>&nbsp;
						</th>
					</tr>

				<? }
		echo "<input type='hidden' name='btn_acao' value='Pesquisar'>";
		echo "<input type='hidden' name='achou' value='{$achou}'>";
		echo "<input type='hidden' name='qtde_os' value='{$x}'>";
		echo "</tfoot>";
		echo "</table>";
		echo "</form>";
	}else{
			$achou = "nao"; ?>
			<div class='container'>
		        <div class="alert">
		            <h4>Nenhum resultado encontrado</h4>
		        </div>  
		    </div>
	<? }
}
?> 
	</div> 
	<br>
	<br>
<?
include "rodape.php" ?>
