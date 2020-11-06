<?

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

if(strlen($_POST['gerar_xls'])>0) $gerar_xls = $_POST['gerar_xls'];
else                              $gerar_xls = $_GET['gerar_xls'];

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
if(strlen($_POST['btn_acao']) > 0) {
	$data_inicial = $_POST['data_inicial'];
	$data_final   = $_POST['data_final'];

	$data_inicial_retorno = $_POST['data_inicial_retorno'];
	$data_final_retorno   = $_POST['data_final_retorno'];

	$tipo         = $_POST['tipo'];
	$atendimento  = $_POST['atendimento'];
	$os           = $_POST['os'];
	$status       = $_POST['status'];
	$atendente    = $_POST['atendente'];
	$uf           = $_POST['estado'];

	if(strlen($atendimento)==0 AND strlen($os)==0){

		if (strlen($data_inicial)>0  AND strlen($data_final)>0) {
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

		if (strlen($data_inicial_retorno)>0  AND strlen($data_final_retorno)>0) {
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



if(strlen($xdata_inicial) > 0 and strlen($xdata_final) > 0) {
	$cond_2 = " AND tbl_hd_chamado.data between '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59' ";
}
//retorno
if(strlen($xdata_inicial_retorno) > 0 and strlen($xdata_final_retorno) > 0) {
	$cond_ret = " AND tbl_hd_chamado.data_providencia between '$xdata_inicial_retorno 00:00:00' AND '$xdata_final_retorno 23:59:59' ";
}
if(strlen($tipo) > 0) {
	$cond_3 = " AND tbl_hd_chamado.categoria = '$tipo' ";
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
	$cond_8 = " AND tbl_hd_chamado.status = '$status' ";
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
						tbl_os.os 											as os ,
						tbl_hd_chamado.categoria 							as aba_callcenter ,
						tbl_hd_chamado_extra.abre_os 						as pre_os ,
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
include 'cabecalho.php';
?>
<?php
	include_once '../js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */
?>

<script type="text/javascript" charset="utf-8">

	$(function(){
		Shadowbox.init();
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");

		$('#data_inicial_retorno').datepick({startDate:'01/01/2000'});
		$('#data_final_retorno').datepick({startDate:'01/01/2000'});
		$("#data_inicial_retorno").mask("99/99/9999");
		$("#data_final_retorno").mask("99/99/9999");

		$("#atendimento").numeric();
		$("#os").numeric();

		$("#content").tablesorter({
    		headers: { 4 : { sorter: 'shortDate'},
    				   5 : { sorter: 'shortDate'},
    				   6 : { sorter: 'shortDate'} }
		});
	});
</script>
<style type="text/css">
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
	color:#ffffff;
	background-color: #445AA8;
}
.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}
.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.linha{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: normal;
	color:#393940 ;
}
a.linha:link, a.linha:visited, a.linha:active{
	text-decoration: none;
	font-weight: normal;
	color: #393940;
}

a.linha:hover {
	text-decoration: underline overline;
	color: #393940;
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



.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
</style>
	<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
		<div id='erro' style='position: absolute; top: 150px; left: 80px;visibility:hidden;opacity:.85;' class='Erro'>
		</div>
		<div id='carregando' style='position: absolute;visibility:hidden;opacity:.90;' class='Carregando'>
		</div>
		<table width='700' class='formulario' border='0' cellpadding='5' cellspacing='1' align='center'>
		<? if(strlen($msg_erro)>0){ ?>
			<tr class="msg_erro"><td><? echo $msg_erro; ?></td></tr>
		<? } ?>
			<tr class='titulo_tabela'>
				<td>
					Parâmetros de Pesquisa
				</td>
			</tr>
			<tr>
				<td valign='bottom'>
					<table width='100%' border='0' cellspacing='1' cellpadding='2' >
						<tr>
							<td width="100">
								&nbsp;
							</td>
							<td align='left'>
								Data Inicial <br />
								<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" >
							</td>
							<td align='left'>
								Data Final <br />
								<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final;  ?>" >
							</td>
							<td width="100">
								&nbsp;
							</td>
						</tr>
						<?php
						if ($login_fabrica == 115 OR $login_fabrica == 116) {?>
							<tr>
								<td width="100">
									&nbsp;
								</td>
								<td align='left'>
									Data de Retorno Inicial <br />
									<input type="text" name="data_inicial_retorno" id="data_inicial_retorno" size="12" maxlength="10" class='frm' value="<? if (strlen($data_inicial_retorno) > 0) echo $data_inicial_retorno; ?>" >
								</td>
								<td align='left'>
									Data de Retorno Final <br />
									<input type="text" name="data_final_retorno" id="data_final_retorno" size="12" maxlength="10" class='frm' value="<? if (strlen($data_final_retorno) > 0) echo $data_final_retorno;  ?>" >
								</td>
								<td width="100">
									&nbsp;
								</td>
							</tr>
						<?php
						}
						?>
						


						<tr>
							<td width="10">
								&nbsp;
							</td>
							<td align='left'>
								Nº Atendimento <br />
								<input type="text" name="atendimento" id="atendimento" size="12" maxlength="10" class='frm' value="<? if (strlen($atendimento) > 0) echo $atendimento; ?>" >
							</td>
							<?php if ( ( strlen($supervisor) > 0 && $login_fabrica == 24) || $login_fabrica <> 24 ) { ?>
							<td align='left'>
								Atendente <br />
								<?

								if($login_fabrica == 74){

									$tipo = "producao"; // teste - producao

									$admin_fale_conosco = ($tipo == "producao") ? 6409 : 6437;

									$cond_admin_fale_conosco = " AND tbl_admin.admin NOT IN ($admin_fale_conosco) ";

								}

								$sqlA = "select admin, login from tbl_admin where fabrica = $login_fabrica and ativo is true $cond_admin_fale_conosco order by login";
								$resA = pg_query($con, $sqlA);

								if(pg_num_rows($resA)>0){
									echo "<select name='atendente' class='frm'>";
										echo "<option value=''></option>";
										for($x=0; $x<pg_num_rows($resA); $x++){
											$xadmin = pg_result($resA,$x,admin);
											$login = pg_result($resA,$x,login);
											echo "<option value='$xadmin'";
											if($atendente==$xadmin) echo "selected";
											echo ">$login</option>";
										}
									echo "</select>";
								}
								?>
							</td>
							<?php } ?>
							<td width="10">
								&nbsp;
							</td>
						</tr>
						<tr>
							<td width="10">
								&nbsp;
							</td>
							<td align='left'>
								Status <br />

								<select name="status" style='width:80px; font-size:9px' class="frm" >
									<option value=""></option>

									<?php
										$sqlS = " SELECT status FROM tbl_hd_status where fabrica=$login_fabrica ";
										$resS = pg_query($con,$sqlS);

										for ($i = 0; $i < pg_num_rows($resS);$i++){

											$status_hd = pg_result($resS,$i,0);

											$selected_status = ($status_hd == $status) ? "SELECTED" : null;
									?>
											<option value="<?=$status_hd?>" <?echo $selected_status?> ><?echo $status_hd?></option>
									<?
										}
									?>
								</select>
							</td>
							<? if($login_fabrica==30){?>
							<td align='left'>
								Nº OS <br />
								<input type="text" name="os" id="os" size="12" maxlength="10" class='frm' value="<? if (strlen($os) > 0) echo $os; ?>" >
							</td>
							<? } ?>
							<td width="10">
								&nbsp;
							</td>
						</tr>



						<tr>
							<td width="10">
								&nbsp;
							</td>
							<td align='left'nowrap width='210'>
									<?php echo ($login_fabrica == 137) ? "Natureza" : "Tipo"; ?> <br>
								<select name='tipo' size='1' style='width:250px' class='frm'>
									<option>
									</option>
									<?
									$sql =
										"SELECT nome,
												descricao
										FROM tbl_natureza
										WHERE fabrica=$login_fabrica
											AND ativo = 't'
										ORDER BY nome";
									$res = pg_query($con,$sql);
									if(pg_num_rows($res)>0){
										for($y=0;pg_num_rows($res)>$y;$y++){
											$nome       = trim(pg_result($res,$y,nome));
											$descricao  = trim(pg_result($res,$y,descricao));
											echo $nome;
											echo "<option value='$nome'";
											if($tipo == $nome) {
												echo "selected";
											}
											echo ">$descricao</option>";
										}
									}?>
								</select>
							</td>
							<td width="10">
								<? if($login_fabrica == 74){ ?>
								Estado <br />
								<select name="estado" class="frm">
									<option value="">Selecione um Estado</option>
									<?php
									foreach ($array_estado as $k => $v) {
									echo '<option value="'.$k.'"'.($estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
									}?>
								</select>
								<? }else{
									echo "&nbsp;";
								}
								?>
							</td>
						</tr>

						<? if(in_array($login_fabrica, array(151))){?>
						<tr>
							<td width="10">
								&nbsp;
							</td>
							<td align='left'>Providência<br />
								<select id="providencia" name="providencia">
									<option value="">Selecione</option>
									<?php

									$sql = "SELECT hd_motivo_ligacao, descricao 
										FROM tbl_hd_motivo_ligacao WHERE fabrica = {$login_fabrica} ORDER BY descricao";
									$resProvidencia = pg_query($con,$sql);

									if(pg_num_rows($resProvidencia) > 0){
										while($objeto_providencia = pg_fetch_object($resProvidencia)){
											if($objeto_providencia->hd_motivo_ligacao == $providencia){
												$selected = "selected='selected'";
											}else{
												$selected = "";
											}
											?>
											<option value="<?=$objeto_providencia->hd_motivo_ligacao?>" <?=$selected?>><?=$objeto_providencia->descricao?></option>
											<?php
										}
									}

									?>
								</select>
							</td>

						</tr>
						<? } ?>

						<tr>
							<td width="10">
								&nbsp;
							</td>
							<td colspan='3' align='left'>
								<input type="checkbox" name="gerar_xls" value="t" class="frm" <? if($gerar_xls=='t') echo "checked"; ?>>&nbsp;Gerar Excel
							</td>

						</tr>
						<?php
						if ($login_fabrica == 15){
							?>
								<tr>
									<td width='10'>
										&nbsp;
									</td>
									<td colspan='3' align='left'>
										<input type='checkbox' name='bi_latina' value='t' class='frm' <?php if($_POST['bi_latina']=='t') { echo 'checked'; } ?>> BI Callcenter
									</td>
								</tr>
						<?php
						}
						?>
					</table>
					<br>
					<input type='submit' name='btn_acao' value='Consultar'>
				</td>
			</tr>
		</table>
	</FORM>
<?

if(($login_fabrica==59 and strlen($_POST['btn_acao']) > 0 and strlen($msg_erro)==0) or (($login_fabrica<>59 AND strlen($msg_erro)==0) AND (!isset($bi_latina)))) {


	echo "<BR>";
	echo "<table width='700' align='center' class='formulario'>";
		echo "<TR >\n";
			echo "<TD width='50%'>";
				echo "<table width='300' border='0' align='left' cellpadding='2' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>";
                    if ($login_fabrica == 74) {
                        $vHoje = '#FFFF00';
                        $v2dias = '#FABD6B';
                        $v3dias = '#F8615C';

                        echo "<TR >\n";
                            echo "<TD bgcolor='{$vHoje}' width='10'>&nbsp;</TD >";
                            echo "<TD align='left'>Data de providência que se encerra hoje</TD >";
                        echo "</TR >\n";
                        echo '<tr>';
                        echo '<td bgcolor="' . $v2dias . '" width="10">&nbsp;</td>';
                        echo '<td align="left">Providência vencida há 2 dias</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo '<td bgcolor="' . $v3dias . '" width="10">&nbsp;</td>';
                        echo '<td align="left">Providência vencida há 3 dias ou mais</td>';
                        echo '</tr>';
                    } elseif($login_fabrica !=151){
                        if ($login_fabrica == 51){
                            echo "<TR >\n";
                            echo "<TD bgcolor='#91C8FF' width='10'>&nbsp;</TD >";
                            echo "<TD align='left'>Chamados que você ainda não abriu</TD >";
                            echo "</TR >\n";
                        }
                        echo "<TR >\n";
                            echo "<TD bgcolor='#F8615C' width='10'>&nbsp;</TD >";
                            echo "<TD align='left'>Último contato a mais de 3 dias</TD >";
                        echo "</TR >\n";
                        echo "<TR >\n";
                            echo "<TD bgcolor='#FABD6B' width='10'>&nbsp;</TD >";
                            echo "<TD align='left'>Último contato a 2 dias</TD >";
                        echo "</TR >\n";

                        if ($login_fabrica == 11 or $login_fabrica == 30) {
                            echo "<TR >\n";
                                echo "<TD bgcolor='#BA8DFF' width='10'>&nbsp;</TD >";
                                echo "<TD align='left'>Data Programada (Resolução) atrasada</TD >";
                            echo "</TR >\n";
                        }
                        if ($login_fabrica == 85) {
                            echo "<TR >\n";
                                echo "<TD bgcolor='#81F781' width='10'>&nbsp;</TD >";
                                echo "<TD align='left'>Atendimento com Ordem de Serviço finalizada</TD >";
                            echo "</TR >\n";
                        }
					} else {
?>
                    <TR >
                        <TD bgcolor='#F8615C' width='10' style="border: 1px solid #000;">&nbsp;</TD >
                        <TD align='left'>Protocolos que estão com prazo vencido</TD >
                    </TR >
                    <TR >
                        <TD bgcolor='#91C8FF' width='10' style="border: 1px solid #000;">&nbsp;</TD >
                        <TD align='left'>Protocolos que estão vencendo no dia</TD >
                    </TR >
                    <TR >
                        <TD bgcolor='#F1F4FA' width='10' style="border: 1px solid #000;">&nbsp;</TD >
                        <TD align='left'>Protocolos que estão no prazo</TD >
                    </TR >
<?
					}
				echo "</TABLE >\n";
				/*imagens_admin/callcenter_interativo.gif
				imagens_admin/consulta_callcenter.gif*/
			echo "</TD >\n";
			echo "<TD width='50%'>";
				echo "<table width='300' border='0' align='left' cellpadding='2' cellspacing='2' style='font-size:10px'>";
					echo "<TR >\n";
						echo "<TD width='10'><a href='$programaphp'><img src='imagens_admin/cadastra_callcenter.gif' border='0' width='15'></a></TD >";
						echo "<TD align='left'><a href='$programaphp'>Cadastrar Atendimento</a></TD >";
					echo "</TR >\n";
					echo "<TR >\n";
						echo "<TD width='10'><a href='callcenter_parametros_interativo.php'><img src='imagens_admin/consulta_callcenter.gif' width='15' border='0'></a></TD >";
						echo "<TD align='left'><a href='callcenter_parametros_interativo.php'>Consultar Atendimento</a></TD >";
					echo "</TR >\n";
				echo "</TABLE >\n";
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

	if($gerar_xls=='t'){
		ob_start();//INICIA BUFFER
	}

	if ($login_fabrica == 24) {
		$sql_pendente = " AND tbl_hd_chamado.status = 'Pendente' ";
	}

	if($login_fabrica == 74){
		$cond_admin_fale_conosco = " AND tbl_hd_chamado.status IS NOT NULL ";
	}

	if(in_array($login_fabrica, array(151))){
		$providencia = $_POST["providencia"];

		if(!empty($providencia)){
			$cond_providencia = " AND tbl_hd_motivo_ligacao.hd_motivo_ligacao = {$providencia}";
		}
	}

	$sql = "SELECT  tbl_hd_chamado.hd_chamado,
					tbl_hd_chamado.titulo,
					tbl_hd_chamado.status,
					tbl_hd_chamado.sequencia_atendimento,
					to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data,
					to_char(tbl_hd_chamado.data,'HH24:MI') AS hora,
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
					tbl_hd_chamado_extra.sua_os,
					tbl_hd_chamado_extra.defeito_reclamado as defeito_reclamado,
					tbl_hd_chamado_extra.os,
					tbl_cliente_admin.nome as nome_cliente_admin,
					tbl_hd_chamado_extra.array_campos_adicionais as campos_adicionais,
					to_char(tbl_hd_chamado.data_providencia,'DD/MM/YYYY') AS providencia_data,
					tbl_hd_situacao.descricao AS providencia,
					tbl_hd_chamado.data_providencia,
					tbl_cidade.estado,
					tbl_produto.referencia,
					tbl_produto.descricao as produto_descricao,
					tbl_hd_motivo_ligacao.descricao AS providencia_motivo
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
			LEFT JOIN tbl_produto ON tbl_hd_chamado_extra.produto = tbl_produto.produto
			LEFT JOIN tbl_hd_motivo_ligacao ON tbl_hd_chamado_extra.hd_motivo_ligacao = tbl_hd_motivo_ligacao.hd_motivo_ligacao AND tbl_hd_motivo_ligacao.fabrica = $login_fabrica
			WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
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
			$cond_11
			$cond_admin_fale_conosco
			$cond_ret
			$cond_providencia
			ORDER BY tbl_hd_chamado.hd_chamado ASC";
	$res = pg_query($con,$sql);
	//exit;
//	echo nl2br($sql);
	if(pg_num_rows($res)>0){
			echo "<br>";
			if ($login_fabrica == 24) {
				echo "<table width='800' border='0' align='center' cellpadding='1' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:11px'>";
				echo "<tr>
					<td><h1>Chamados com Status de Pendentes</h1></td>
					</tr>";
			echo "</table>";
			}

			$table_cor = ($login_fabrica == 74) ? 'tablesorter ':'';

		echo "<table width='700' border='1' align='center' id='content' cellpadding='1' cellspacing='1' class='$table_cor tabela'>";
			echo "<THEAD>";
			echo "<TR class='titulo_coluna'>\n";
				switch ($login_fabrica) {

					case 24:
						echo "<TH nowrap>Origem do Chamado</ACRONYM></TH>\n";
						echo "<TH nowrap>Atendente Responsável</TH>\n";
						echo "<TH nowrap>Interventor</TH>\n";
						echo "<TH nowrap>Status</TH>\n";
						echo "<TH nowrap>Data Recebimento/Abertura</TH>\n";
						echo "<TH ><ACRONYM TITLE=\"QUANTIDADE DE DIAS ÚTEIS DA ÚLTIMA INTERAÇÃO\">Data Máxima para Solução</TH>\n";
						echo "<TH nowrap>Ligação Agendada</TH>\n";
						echo "<TH nowrap>Nº Chamado</TH>\n";
						echo "<TH nowrap>Cliente</TH>\n";
						echo "<TH nowrap>Cidade</TH>\n";
						echo "<TH >Categoria</TH>\n";
					break;

					default:
						echo "<TH style='background-color:#596D9B;'><ACRONYM TITLE=\"NÚMERO ATENDIMENTO\">AT.</ACRONYM></TH>\n";
						if ($login_fabrica == 11 OR $login_fabrica == 85) {
							echo "<TH nowrap>Interventor</TH>\n";
						}
						echo "<TH style='background-color:#596D9B;'>Cliente</TH>\n";
						if($login_fabrica == 74){
							echo "<TH style='background-color:#596D9B;'><ACRONYM TITLE=\"OS\">OS</ACRONYM></TH>\n";
							echo "<TH style='background-color:#596D9B;'><ACRONYM TITLE=\"P.A.\">P.A.</ACRONYM></TH>\n";
						}
						if ($login_fabrica == 15) {
							echo "<TH style='background-color:#596D9B;'><ACRONYM TITLE=\"Defeito Reclamado\">Defeito Reclamado</ACRONYM></TH>\n";
						}
						if ($login_fabrica == 51) {
						echo "<TH style='background-color:#596D9B;'>Posto</TH>\n";
						}
						if ($login_fabrica == 35) {
						echo "<TH style='background-color:#596D9B;'>Produto</TH>\n";
						}

						echo "<TH style='background-color:#596D9B;'>Abertura</TH>\n";
						if($login_fabrica == 7){
							echo "<TH style='background-color:#596D9B;'>Hora abertura</TH>\n";
						}
						echo "<TH style='background-color:#596D9B;'><ACRONYM TITLE=\"ÚLTIMA INTERAÇÃO\">Últ.Inter</ACRONYM></TH>\n";
						if ($login_fabrica == 15) {
							echo "<TH style='background-color:#596D9B;'><ACRONYM TITLE=\"Data Retorno\">Data Retorno</ACRONYM></TH>\n";
						}
						$uteis = $login_fabrica == 35 ? "" : " ÚTEIS" ;
						echo "<TH style='background-color:#596D9B;'><ACRONYM TITLE=\"QUANTIDADE DE DIAS$uteis ABERTO\">Dias AB.</ACRONYM></TH>\n";
						echo "<TH style='background-color:#596D9B;'><ACRONYM TITLE=\"QUANTIDADE DE DIAS$uteis DA ÚLTIMA INTERAÇÃO\">Dias INT.</ACRONYM></TH>\n";
						echo "<TH style='background-color:#596D9B;'>Status</TH>\n";
						if($login_fabrica == 74){
							echo "<TH style='background-color:#596D9B;'>Produto</TH>\n";
							echo "<TH style='background-color:#596D9B;'>Defeito</TH>\n";
						}

						echo "<TH style='background-color:#596D9B;'>Atendente</TH>\n";
						if ($login_fabrica == 11) {
							echo "<TH style='background-color:#596D9B;'>Data Programada</TH>\n";
						}
						if ($login_fabrica == 30) {
							echo "<TH style='background-color:#596D9B;'>Data de Retorno ao Cliente</TH>\n";
							echo "<TH style='background-color:#596D9B;'>Data Limite</TH>\n";
						}
                        			if($login_fabrica <> 15 AND $login_fabrica <> 74){
                        				if($login_fabrica <> 115){
								echo "<TH style='background-color:#596D9B;'>Providência</TH>\n";

								if($login_fabrica == 151){
									echo "<TH style='background-color:#596D9B;'>Data Providência</TH>\n";
								}
                        				}else{
                        					echo "<TH style='background-color:#596D9B;'>Data de Retorno</TH>\n";
                        				}
                        			}
						if($login_fabrica == 7){
							echo "<TH style='background-color:#596D9B;'>OS</TH>\n";
							echo "<TH style='background-color:#596D9B;'>DR</TH>\n";
							echo "<TH style='background-color:#596D9B;'>Número Contrato</TH>";
						}
						if($login_fabrica == 74){
							echo "<TH style='background-color:#596D9B;'>UF</TH>\n";
							echo "<TH style='background-color:#596D9B;'><ACRONYM TITLE=\"DATA DA PROVIDÊNCIA\">DTA.PRO.</ACRONYM></TH>\n";
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

				if ($login_fabrica == 15 or $login_fabrica == 74 AND strlen(trim($defeito_reclamado)) > 0) {

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
							$ligacao_agendada = "";
						}
					} else {
						$ligacao_agendada = "";
					}
				}
				if ($x % 2 == 0){
					$xcor = $cor = '#F1F4FA';
				}else{
					$xcor = $cor = '#F7F5F0';
				}

                if($login_fabrica != 151){
                    if($dias_ultima_interacao == 2){
                        $cor = '#FABD6B';
                    }
                    if($dias_ultima_interacao >= 3){
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

				if(in_array($login_fabrica,array(151))){
                    $date = date('Y-m-d');
//                     echo strtotime($data_providencia_linha_amarela)."(".$data_providencia_linha_amarela.") -- ".strtotime($date)."[".$date."]<br>";
                    if(strlen(trim($providencia_data)) > 0 and (strtotime($data_providencia_linha_amarela) < strtotime($date) ) ) {
                           $cor = "#F8615C";
                    } else if(strlen(trim($providencia_data)) > 0 && (strtotime($data_providencia_linha_amarela) == strtotime($date)) && $login_fabrica == 151){
                        $cor = "#91C8FF";
                    } else if(strlen(trim($providencia_data)) > 0 && (strtotime($data_providencia_linha_amarela) > strtotime($date) || strlen($data_providencia_linha_amarela) == 0) && $login_fabrica == 151){
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

				if ($login_fabrica == 11 or $login_fabrica == 30 && strlen($campos_adicionais->data_programada) > 0) {
					list($dpd, $dpm, $dpa) = explode("/", $campos_adicionais->data_programada);
					$aux_data_programada = "{$dpa}-{$dpm}-{$dpd}";
					$aux_data_hoje       = date("Y-m-d");

					if (strtotime($aux_data_programada) < strtotime($aux_data_hoje)) {
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
				echo "<TR bgcolor='$cor' onmouseover=\"this.bgColor='#F0EBC8'\" onmouseout=\"this.bgColor='$cor'\">\n";

				switch ($login_fabrica) {

					case 24:
						echo "<TD class='linha' align='center' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$admin</a></TD>\n";
						echo "<TD class='linha' align='center' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$atendente</a></TD>\n";
						echo "<TD class='linha' align='center' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$interventor</a></TD>\n";
						echo "<TD class='linha' align='center' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$status</a></TD>\n";
						echo "<TD class='linha' align='center' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$data</a></TD>\n";
						echo "<TD class='linha' align='center' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$data_maxima</a></TD>\n";
						echo "<TD class='linha' align='center' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$ligacao_agendada</a></TD>\n";
						echo "<TD class='linha' align='center' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$callcenter</a></TD>\n";
						echo "<TD class='linha' align='center' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$cliente_nome</a></TD>\n";
						echo "<TD class='linha' align='center' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$nome_cidade</a></TD>\n";
						echo "<TD class='linha' align='center' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$categoria</a></TD>\n";
					break;
					default:
						echo "<TD style='background-color:$cor;' class='linha' align='center' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$callcenter</a></TD>\n";
						if ($login_fabrica == 11 OR $login_fabrica == 85) {
							echo "<TD style='background-color:$cor;' class='linha' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$interventor</a></TD>\n";
						}
						echo "<TD style='background-color:$cor;' class='linha'  align='left' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>".substr($cliente_nome,0,17)."</a></TD>\n";
						if($login_fabrica == 74){
							echo "<TD style='background-color:$cor;' class='linha'><a href='os_press.php?os={$os}' target='_blank'>{$os}</a></TD>\n";
							echo "<TD style='background-color:$cor;' class='linha'>{$codigo_posto} - {$posto_nome}</TD>\n";
						}
						if ($login_fabrica == 15) {
							echo "<TD style='background-color:$cor;' class='linha'  align='left' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$xdefeito_reclamado</a></TD>\n";
						}
						if ($login_fabrica == 51) {
							echo "<TD style='background-color:$cor;' class='linha'  align='left' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>".$codigo_posto." - ".substr($posto_nome,0,30)."</a></TD>\n";
						}
						if ($login_fabrica == 35) {
							echo "<TD style='background-color:$cor;' class='linha'  align='left' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$referencia</a></TD>\n";
						}

						echo "<TD style='background-color:$cor;' class='linha' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$data</a></TD>\n";
						if($login_fabrica == 7){
							echo "<TD style='background-color:$cor;' class='linha' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$hora</a></TD>\n";
						}
						echo "<TD style='background-color:$cor;' class='linha' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$data_interacao</a></TD>\n";
						if ($login_fabrica == 15) {
							echo "<TD style='background-color:$cor;' class='linha' align=center nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>{$campos_adicionais->data_retorno}</a></TD>";
						}
						echo "<TD style='background-color:$cor;' class='linha' align=center nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$dias_aberto</a></TD>";
						echo "<TD style='background-color:$cor;' class='linha' align=center nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$dias_ultima_interacao</a></TD>";

						echo "<TD style='background-color:$cor;' class='linha' align=center nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$status</a></TD>";

						if($login_fabrica == 74){
							echo "<TD style='background-color:$cor;' class='linha' align=center nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$referencia - $produto_descricao</a></TD>";
							echo "<TD style='background-color:$cor;' class='linha' align=center nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$xdefeito_reclamado</a></TD>";
						}

						echo "<TD style='background-color:$cor;' class='linha' width=85 align=center><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$atendente</a></TD>";
						if ($login_fabrica == 11) {
							echo "<TD style='background-color:$cor;' class='linha' width=85 align=center><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>{$campos_adicionais->data_programada}</a></TD>";
							echo "<TD style='background-color:$cor;' class='linha' width=85 align=center><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>{$data_providencia}</a></TD>";
						}

						if ($login_fabrica == 30) {
							echo "<TD style='background-color:$cor;' class='linha' width=85 align=center><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>{$data_providencia}</a></TD>";
							echo "<TD style='background-color:$cor;' class='linha' width=85 align=center><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>{$campos_adicionais->data_limite}</a></TD>";
						}

						if(!in_array($login_fabrica,array(15,151))){
							if($login_fabrica <> 74){
								if ($login_fabrica == 115) {
									echo "<TD style='background-color:$cor;' class='linha' align=center nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>{$providencia_data}</a></TD>";
								}else{
									echo "<TD style='background-color:$cor;' class='linha' align=center nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>".substr($providencia,0,60)."</a></TD>";
								}
								
							}
						}

                        if(in_array($login_fabrica,array(151))){
                            echo "<TD style='background-color:$cor;' class='linha' align=center nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>".substr($providencia_motivo,0,60)."</a></TD>";
                            echo "<TD style='background-color:$cor;' class='linha' width=85 align=center><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$providencia_data</a></TD>";
                        }

						if($login_fabrica == 7){
							echo "<TD style='background-color:$cor;' class='linha' width=85 align=center><a target='_BLANK' href='os_press_filizola.php?os=$os' class='linha'>$os</a></TD>";
							echo "<TD style='background-color:$cor;' class='linha' width=85 align=center><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$nome_cliente_admin</a></TD>";
							echo "<TD style='background-color:$cor;' class='linha' width=85 align=center><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$numero_contrato</a></TD>";
						}
						if($login_fabrica == 74){
							echo "<TD style='background-color:$cor;' class='linha' width=85 align=center>$uf</TD>\n";
							echo "<TD style='background-color:$cor;' class='linha' width=85 align=center><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$providencia_data</a></TD>";
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
		echo "<center>Nenhum chamado pendente!</center>";
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

		if ($login_fabrica == 24) {
			echo "<table width='800' border='0' align='center' cellpadding='1' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:11px'>";
			echo "<tr>
					<td><h1>Chamados com Status Aberto</h1></td>
				</tr>";
			echo "</table>";
		}

		echo "<table width='800' border='0' align='center' cellpadding='1' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:11px'>";
			echo "<TR >\n";
				switch ($login_fabrica) {

					case 24:
					case 85:
						echo "<td class='menu_top' background='imagens_admin/azul.gif' nowrap>ORIGEM DO CHAMADO.</ACRONYM></TD>\n";
						echo "<TD class='menu_top' background='imagens_admin/azul.gif' nowrap>ATENDENTE RESPONSAVEL</TD>\n";
						echo "<TD class='menu_top' background='imagens_admin/azul.gif' nowrap>INTERVENTOR</TD>\n";
						echo "<TD class='menu_top' background='imagens_admin/azul.gif' nowrap>STATUS</TD>\n";
						echo "<TD class='menu_top' background='imagens_admin/azul.gif' nowrap>DATA RECEBIMENTO/ABERTURA</TD>\n";
						echo "<TD class='menu_top' background='imagens_admin/azul.gif'><ACRONYM TITLE=\"QUANTIDADE DE DIAS ÚTEIS DA ÚLTIMA INTERAÇÃO\">DATA MAXIMA PARA SOLUÇÃO</TD>\n";
						echo "<TD class='menu_top' background='imagens_admin/azul.gif' nowrap>Nº CHAMADO</TD>\n";
						echo "<TD class='menu_top' background='imagens_admin/azul.gif' nowrap>CLIENTE</TD>\n";
						echo "<TD class='menu_top' background='imagens_admin/azul.gif' nowrap>CIDADE</TD>\n";
						echo "<TD class='menu_top' background='imagens_admin/azul.gif'>CATEGORIA</TD>\n";
					break;

					default:
						echo "<td class='menu_top' background='imagens_admin/azul.gif'><ACRONYM TITLE=\"NÚMERO ATENDIMENTO\">AT.</ACRONYM></TD>\n";
						echo "<TD class='menu_top' background='imagens_admin/azul.gif'>CLIENTE</TD>\n";
						if ($login_fabrica == 51) {
						echo "<TD class='menu_top'	background='imagens_admin/azul.gif'>POSTO</TD>\n";
						}
						echo "<TD class='menu_top' background='imagens_admin/azul.gif'>ABERTURA</TD>\n";
						echo "<TD class='menu_top' background='imagens_admin/azul.gif'><ACRONYM TITLE=\"ÚLTIMA INTERAÇÃO\">ÚLT.INTER</ACRONYM></TD>\n";
						echo "<TD class='menu_top' background='imagens_admin/azul.gif'><ACRONYM TITLE=\"QUANTIDADE DE DIAS ÚTEIS ABERTO\">DIAS AB.</ACRONYM></TD>\n";
						echo "<TD class='menu_top' background='imagens_admin/azul.gif'><ACRONYM TITLE=\"QUANTIDADE DE DIAS ÚTEIS DA ÚLTIMA INTERAÇÃO\">DIAS INT.</ACRONYM></TD>\n";
						echo "<TD class='menu_top' background='imagens_admin/azul.gif'>STATUS</TD>\n";
						echo "<TD class='menu_top' background='imagens_admin/azul.gif'>ATENDENTE</TD>\n";
						echo "<TD class='menu_top' background='imagens_admin/azul.gif'>PROVIDÊNCIA</TD>\n";
						echo "<TD class='menu_top' background='imagens_admin/azul.gif'><ACRONYM TITLE=\"DATA DA PROVIDÊNCIA\">DTA.PRO.</ACRONYM></TD>\n";
					break;

					}
			echo "</TR>\n";
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
				if($dias_ultima_interacao == "2"){
					$cor = '#FABD6B';
				}
				if($dias_ultima_interacao >= "3"){
					$cor = '#F8615C';
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
						echo "<TD class='linha' align='center' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$admin</a></TD>\n";
						echo "<TD class='linha' align='center' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$atendente</a></TD>\n";
						echo "<TD class='linha' align='center' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$interventor</a></TD>\n";
						echo "<TD class='linha' align='center' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$status</a></TD>\n";
						echo "<TD class='linha' align='center' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$data</a></TD>\n";
						echo "<TD class='linha' align='center' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$data_maxima</a></TD>\n";
						echo "<TD class='linha' align='center' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$callcenter</a></TD>\n";
						echo "<TD class='linha' align='center' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$cliente_nome</a></TD>\n";
						echo "<TD class='linha' align='center' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$nome_cidade</a></TD>\n";
						echo "<TD class='linha' align='center' nowrap><a href='$programaphp?callcenter=$callcenter&categoria=$categoria' class='linha'>$categoria</a></TD>\n";
					break;

				}
				echo "</TR>\n";
			}
				echo "</table>";
			}else{
				echo "<center>Nenhum chamado com Status de Aberto!</center>";
			}
		}

	if($gerar_xls=='t'){
		$conteudo = ob_get_contents();//PEGA O CONTEUDO EM BUFFER
		ob_end_clean();//LIMPA O BUFFER

		$arquivo_nome = "relatorio_callcenter_pendente_$login_fabrica.$login_admin.xls";
		$path         = "/www/assist/www/admin/xls/";
		$path_tmp     = "/tmp/";

		$arquivo_completo     = $path.$arquivo_nome;
		$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

		$file = fopen($arquivo_completo_tmp, 'w');
		fwrite($file, $conteudo);
		fclose($file);

		system("cp $arquivo_completo_tmp $path");//COPIA ARQUIVO PARA DIR XLS

		echo $conteudo;
		echo '<br />';
		echo '<a href="../admin/xls/'.$arquivo_nome.'" target="_blank" >';
			echo '<img src="/assist/imagens/excel.gif" border="0">';
			echo '<br />';
			echo '<font size="2" color="#000">Fazer download do relatório!</font>';
		echo '</a>';
		echo '<br />';
	}
}
if ((strlen($bi_latina) > 0 ) AND (pg_num_rows($resSubmit) > 0)) {
	$data = date("d-m-Y-H:i");
    $fileName = "BI-DE-ATENDIMENTOS-{$data}.xls";
    $file = fopen("/tmp/{$fileName}", "w");

    fwrite($file, "
                <table border='1'>
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
			$os = pg_fetch_result($resSubmit, $i,'os');
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
              echo '<br />';
			echo '<a href="../admin/xls/'.$fileName.'" target="_blank" >';
				echo '<img src="../imagens/excel.gif" border="0">';
				echo '<br />';
				echo '<font size="2" color="#000">Fazer download do relatório!</font>';
			echo '</a>';
			echo '<br />';
        }
} else {
	if ((strlen($bi_latina) > 0 ) AND (pg_num_rows($resSubmit) == 0) and (strlen($msg_erro) == 0)) {
	 echo '<br />';
		echo '<font size="2" color="#000">Nenhum atendimento foi encontrado!</font>';
	 echo '<br />';
	}
}

include "rodape.php";
?>
