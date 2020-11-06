<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";

if ($gera_automatico != 'automatico'){
	include "autentica_admin.php";
}

include "gera_relatorio_pararelo_include.php";

include "monitora.php";
if ($login_fabrica == 14){
	header("Location: relatorio_field_call_rate_produto_familia.php");
	exit;
}

// Criterio padrão
$_POST["criterio"] = "data_digitacao";

if (strlen(trim($_POST["btn_acao"])) > 0) $btn_acao = trim($_POST["btn_acao"]);
if (strlen(trim($_GET["btn_acao"])) > 0) $btn_acao = trim($_GET["btn_acao"]);

if (strlen($btn_acao) > 0) {

	if (strlen(trim($_POST["data_inicial_01"])) > 0) $data_inicial = trim($_POST["data_inicial_01"]);
	if (strlen(trim($_GET["data_inicial_01"])) > 0) $data_inicial = trim($_GET["data_inicial_01"]);

	$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
	if (strlen ( pg_errormessage ($con) ) > 0) {
		$msg_erro = pg_errormessage ($con) ;
	}

	if (strlen($msg_erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);

	if (strlen(trim($_POST["data_final_01"])) > 0) $data_final = trim($_POST["data_final_01"]);
	if (strlen(trim($_GET["data_final_01"])) > 0) $data_final = trim($_GET["data_final_01"]);

	$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
	if (strlen ( pg_errormessage ($con) ) > 0) {
		$msg_erro = pg_errormessage ($con) ;
	}

	if (strlen($msg_erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);

	if (strlen(trim($_POST["linha"])) > 0) $linha = trim($_POST["linha"]);
	if (strlen(trim($_GET["linha"])) > 0) $linha = trim($_GET["linha"]);

	if(strlen($_POST["estado"]) > 0){
		if (strlen(trim($_POST["estado"])) > 0) $estado = trim($_POST["estado"]);
		if (strlen(trim($_GET["estado"])) > 0) $estado = trim($_GET["estado"]);
		$mostraMsgEstado = "<br>no ESTADO $estado";
	}
}

$layout_menu = "gerencia";
$title = "RELATÓRIO - FIELD CALL-RATE : LINHA DE PRODUTO";

include "cabecalho.php";

?>

<script language="JavaScript">

function date_onkeydown() {
	if (window.event.srcElement.readOnly) return;
	var key_code = window.event.keyCode;
	var oElement = window.event.srcElement;
	if (window.event.shiftKey && String.fromCharCode(key_code) == "T") {
		var d = new Date();
		oElement.value = String(d.getMonth() + 1).padL(2, "0") + "/" + String(d.getDate()).padL(2, "0") + "/" + d.getFullYear();
		window.event.returnValue = 0;
	}
	if (!window.event.shiftKey && !window.event.ctrlKey && !window.event.altKey) {
		if ((key_code > 47 && key_code < 58) || (key_code > 95 && key_code < 106)) {
			if (key_code > 95) key_code -= (95-47);
			oElement.value = oElement.value.replace(/[dma]/, String.fromCharCode(key_code));
		}
		if (key_code == 8) {
			if (!oElement.value.match(/^[dma0-9]{2}\/[dma0-9]{2}\/[dma0-9]{4}$/))
				oElement.value = "dd/mm/aaaa";
			oElement.value = oElement.value.replace(/([dma\/]*)[0-9]([dma\/]*)$/,
				function ($0, $1, $2) {
					var idx = oElement.value.search(/([dma\/]*)[0-9]([dma\/]*)$/);
					if (idx >= 5) {
						return $1 + "a" + $2;
					} else if (idx >= 2) {
						return $1 + "m" + $2;
					} else {
						return $1 + "d" + $2;
					}
				} );
			window.event.returnValue = 0;
		}
	}
	if (key_code != 9) {
		event.returnValue = false;
	}
}

function AbrePeca(produto,data_inicial,data_final,linha,estado,pais,tipo){
	janela = window.open("relatorio_field_call_rate_pecas2_latinatec.php?produto=" + produto + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado +"&pais=" + pais + "&consumidor_revenda=" + tipo ,"produto",'resizable=1,scrollbars=yes,width=750,height=550,top=0,left=0');
	janela.focus();
}

</script>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: center;
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

/*****************************
ELEMENTOS DE POSICIONAMENTO
*****************************/

#container {
	border: 0px;
	padding:0px 0px 0px 0px;
	margin:0px 0px 0px 0px;
	background-color: white;
}

</style>

<style type="text/css">
<!--
.titPreto14 {
	color: #000000;
	text-align: center;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titDatas12 {
	color: #000000;
	text-align: center;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titChamada10{
	background-color: #596D9B;
	color: #ffffff;
	text-align: center;
	font:11px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.conteudo10 {
	color: #000000;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.bgTRConteudo1{
	background-color: #FEFEFF;
}

.bgTRConteudo2{
	background-color: #F9FCFF;
}
.bgTRConteudo3{
	background-color: #FFCCCC;
}

-->
</style>

<? include "javascript_pesquisas.php"; ?>
<? include "../js/js_css.php"; ?>
<script>
$(function(){
	$('#data_inicial').datepick({startDate:'01/01/2000'});
	$('#data_final').datepick({startDate:'01/01/2000'});
	$("#data_inicial").mask("99/99/9999");
	$("#data_final").mask("99/99/9999");
});
</script>
<DIV ID="container" style="width: 100%; ">

<?

if (strlen(trim($_POST["btn_acao"])) > 0) $btn_acao = trim($_POST["btn_acao"]);
if (strlen(trim($_GET["btn_acao"])) > 0) $btn_acao = trim($_GET["btn_acao"]);

if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
	include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
	include "gera_relatorio_pararelo_verifica.php";
}
?>

<!-- =========== PESQUISA POR INTERVALO ENTRE DATAS ============ -->
<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<? if (strlen($msg_erro) > 0){ ?>
<table width="730" border="0" cellpadding="2" cellspacing="2" align='center'>
<tr>
	<td align="center" class='error'>
		<? echo $msg_erro ?>
	</td>
</tr>
</table>
<br>
<? } ?>

<br>

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr>
		<td colspan="4" class="menu_top" background='imagens_admin/azul.gif' align='center'><b>Pesquisa</b></td>
	</tr>

	<tr>
		<td class="table_line" style="width: 10px">&nbsp;</td>
		<td class="table_line" colspan='2'><center>Este relatório considera a data de geração do extrato aprovado.</center></TD>
		<td class="table_line" style="width: 10px">&nbsp;</td>
	</tr>

	<tr>
		<td class="table_line" style="width: 10px">&nbsp;</td>
		<td class="table_line"><center>Data Inicial</center></td>
		<td class="table_line"><center>Data Final</center></td>
		<td class="table_line" style="width: 10px">&nbsp;</td>
	</tr>
	<tr>
		<td class="table_line" style="width: 10px">&nbsp;</td>
		<td class="table_line" style="width: 185px"><center><input size="12" maxlength="10" type="text" name="data_inicial_01" id='data_inicial' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo ""; ?>" onclick="this.value=''"></center></td>
		<td class="table_line" style="width: 185px"><center><input size="12" maxlength="10" type="text" name="data_final_01" id='data_final' value="<? if (strlen($data_final) > 0) echo $data_final; else echo ""; ?>" onclick="this.value=''"></center></td>
		<td class="table_line" style="width: 10px">&nbsp;</td>
	</tr>

	<tr width = '100%' align="center">
		<td colspan='4' class='table_line' ><center>Linha</center></td>
	</tr>

	<tr width='100%' align="center">
		<td colspan='4' class='table_line'>
			<center>
				<?
				$w = "";
				// HD 2670 - IGOR - PARA A TECTOY, NÃO MOSTRAR A LINHA GERAL, QUE VAI SER EXCLUIDA
				if($login_fabrica==6){
					$w = " AND linha<>39 ";
				}

				$sql = "SELECT  *
						FROM    tbl_linha
						WHERE   tbl_linha.fabrica = $login_fabrica
						$w
						ORDER BY tbl_linha.nome;";
				$res = pg_exec ($con,$sql);

				if (pg_numrows($res) > 0) {
					echo "<select name='linha'>\n";
					echo "<option value=''>ESCOLHA</option>\n";
		/*				if($login_fabrica == 15){
						echo "<option value='LAVADORAS LE'>";
						echo "LAVADORAS LE</option>";
						echo "<option value='LAVADORAS LS'>";
						echo "LAVADORAS LS</option>";
						echo "<option value='LAVADORAS LX'>";
						echo "LAVADORAS LX</option>";
						echo "<option value='IMPORTAÇÃO DIRETA WAL-MART'>";
						echo "IMPORTAÇÃO DIRETA WAL-MART</option>";
						echo "<option value='Purificadores / Bebedouros - Eletrônicos'>";
						echo "Purificadores / Bebedouros - Eletrônicos</option>";
					}
		*/				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
						$aux_linha = trim(pg_result($res,$x,linha));
						$aux_nome  = trim(pg_result($res,$x,nome));

						echo "<option value='$aux_linha'";
						if ($linha == $aux_linha){
							echo " SELECTED ";
							$mostraMsgLinha = "<br> da LINHA $aux_nome";
						}
						echo ">$aux_nome</option>\n";
					}
					echo "</select>\n&nbsp;";
				}
			?>
			</center>
		</td>
	</tr>
<? if($login_fabrica==20){ ?>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<TD class="table_line" style="width: 10px">&nbsp;</TD>
		<td class="table_line" >Ref. Produto</td>
		<td class="table_line" >Descrição Produto</td>
		<TD class="table_line" style="width: 10px">&nbsp;</TD>
	</tr>

	<tr align="center">
		<TD class="table_line" style="width: 10px">&nbsp;</TD>
		<td CLASS='table_line' align='center'>
		<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" >
		&nbsp;
		<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao,'referencia')">
		</td>

		<td CLASS='table_line'>
		<input class="frm" type="text" name="produto_descricao" size="15" value="<? echo $produto_descricao ?>" >
		&nbsp;
		<img src='imagens/btn_lupa.gif'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao,'descricao')">
		<TD class="table_line" style="width: 10px">&nbsp;</TD>
	</tr>
<? } ?>
<? if($login_fabrica==24){ ?>
	<tr width = '100%' align="center">
		<td colspan = '4' class='table_line' ><center>Por tipo</center></td>
	</tr>
	<tr width = '100%' align="center">
		<td colspan = '4' class='table_line' >
			<center>
			<select name="tipo_os" size="1">
				<option value=""></option>
				<option value="C">Consumidor</option>
				<option value="R">Revenda</option>
			</select>
			</center>
		</td>
	</tr>

<? }
	if($login_fabrica == 20){    ?>
		<TR width = '100%' align="center">
			<TD colspan='4' CLASS='table_line' >País</TD>
		</TR>

		<TR width='100%' align="center">
			<TD colspan='4' CLASS='table_line' >
			<?
				$sql = "SELECT  *
						FROM    tbl_pais
						ORDER BY tbl_pais.nome;";
				$res = pg_exec ($con,$sql);

				if (pg_numrows($res) > 0) {
					echo "<select name='pais'>\n";
					if(strlen($pais) == 0 ) {
						$pais = 'BR';
					}
					echo "<option value=''>TODOS</option>\n";

					for ($x = 0 ; $x < pg_numrows($res) ; $x++){
						$aux_pais  = trim(pg_result($res,$x,pais));
						$aux_nome  = trim(pg_result($res,$x,nome));

						echo "<option value='$aux_pais'";
						if ($pais == $aux_pais){
							echo " SELECTED ";
							$mostraMsgPais = "<br> do PAÍS $aux_nome";
						}
						echo ">$aux_nome</option>\n";
					}
					echo "</select>\n";
				} ?>
			</td>
		</tr>
	<? } ?>
  <TR width = '100%' align="center">
	  <TD colspan = '4' CLASS='table_line' > <center>Por região</center></TD>
  </TR>
	<TR width = '100%' align="center">
		<td colspan = '4' CLASS='table_line'>
			<center>
			<select name="estado" size="1">
				<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>>TODOS OS ESTADOS</option>
				<option value="AC" <? if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
				<option value="AL" <? if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
				<option value="AM" <? if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
				<option value="AP" <? if ($estado == "AP") echo " selected "; ?>>AP - Amapá</option>
				<option value="BA" <? if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
				<option value="CE" <? if ($estado == "CE") echo " selected "; ?>>CE - Ceará</option>
				<option value="DF" <? if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
				<option value="ES" <? if ($estado == "ES") echo " selected "; ?>>ES - Espírito Santo</option>
				<option value="GO" <? if ($estado == "GO") echo " selected "; ?>>GO - Goiás</option>
				<option value="MA" <? if ($estado == "MA") echo " selected "; ?>>MA - Maranhão</option>
				<option value="MG" <? if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
				<option value="MS" <? if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
				<option value="MT" <? if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
				<option value="PA" <? if ($estado == "PA") echo " selected "; ?>>PA - Pará</option>
				<option value="PB" <? if ($estado == "PB") echo " selected "; ?>>PB - Paraíba</option>
				<option value="PE" <? if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
				<option value="PI" <? if ($estado == "PI") echo " selected "; ?>>PI - Piauí</option>
				<option value="PR" <? if ($estado == "PR") echo " selected "; ?>>PR - Paraná</option>
				<option value="RJ" <? if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
				<option value="RN" <? if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
				<option value="RO" <? if ($estado == "RO") echo " selected "; ?>>RO - Rondônia</option>
				<option value="RR" <? if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
				<option value="RS" <? if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
				<option value="SC" <? if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
				<option value="SE" <? if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
				<option value="SP" <? if ($estado == "SP") echo " selected "; ?>>SP - São Paulo</option>
				<option value="TO" <? if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
			</select>
			</center>
		</td>
	</TR>

	<TR>
		<input type='hidden' name='btn_acao' value='0'>
		<TD colspan="4" class="table_line" style="text-align: center;"><IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '0' ) { document.frm_pesquisa.btn_acao.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'></TD>
	</TR>
</TABLE>
</FORM>

<?
/* Chamado 1444

LINHA IMPORTAÇÃO DIRETA WAL-MART Compreende:
Atlantic Breese, Audiologic, Aurora, Climatizador De Vinho, Coby,
Derens, Digistar, Digital Lab, Digix, Durabrand, Envirocaire,
Galanz, Gourmet Wave, Gpx, Honeywell, Ihome, In Motion, Memorex,
Monacia, Pelonis, Ritech, Royal, Simz, Studebacker, Trc Sound,
Venturer, Vivitar
*/

/*
Purificadores / Bebedouros - Eletrônicos
*/

/* Chamado 2009
LE = Deve compreender os modelos:
	LE4.6 / LE4.6 / LE 4.16A / LE 4.6M / GL / MN / TI / CA / FLEX.
('21641','21640','11753','11750','11690','11905','11906','11907','11908','11909','11910','11543','11524','11525','11819','11818')

LS = Deve compreender os modelos:
	LS5E / LS5A / 5M / 20AR / 20AN e LS32RE.
('21639','21638','11529','11820','11863','11552','11553','11530','11531','11521','11522','11532','11533','11838','11984','11821','11911','12015','12008','11519','11520','12002','11854','11528','11542','11912','11511','11913','11523','11526','11527','11510')

LX = Deve compreender os modelos:
	VL / VR / CT / LX / LX4.5 / MAX / VIP.
('21645','21644','21643','21642','21639','21638','11745','12003','11746','11917','11916','11991','11914','11915')

*/
?>
<!-- =========== AQUI TERMINA O FORMULÁRIO FRM_PESQUISA =========== -->
</DIV>

<?

if (strlen(trim($_POST["btn_acao"])) > 0) $btn_acao = trim($_POST["btn_acao"]);
if (strlen(trim($_GET["btn_acao"])) > 0) $btn_acao = trim($_GET["btn_acao"]);

if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0) {
	$cond_1 = "1=1";
	$cond_2 = "1=1";
	$cond_3 = "1=1";
	$cond_4 = "1=1";
	$cond_5 = "1=1";
	$cond_6 = "1=1";
	if($login_fabrica == 6){
		$cond_1 = " linha <> 39 ";
	}

	if (strlen ($linha)    > 0) $cond_1 = " tbl_produto.linha = $linha ";
	if (strlen ($estado)   > 0) $cond_2 = " tbl_posto.estado  = '$estado' ";
	if (strlen ($posto)    > 0) $cond_3 = " tbl_posto.posto   = $posto ";
	if (strlen ($produto)  > 0) $cond_4 = " tbl_os.produto    = $produto ";
	if (strlen ($tipo_os)  > 0) $cond_5 = " tbl_os.consumidor_revenda = '$tipo_os' ";
	if (strlen ($pais)     > 0) $cond_6 = " tbl_posto.pais	  = '$pais' ";
	//Chamado = 1444
/*	if ($linha == "IMPORTAÇÃO DIRETA WAL-MART" AND $login_fabrica == 15) $cond_1 = " tbl_produto.linha in('398','344','311','403','390','343','329','400','342','317','401','338','399','346','307','393','395','345','310','375','339','396','330','376','392','341','402') ";
	if ($linha == "LAVADORAS LE" AND $login_fabrica == 15) $cond_1 = " tbl_produto.produto in('21641','21640','11753','11750','11690','11905','11906','11907','11908','11909','11910','11543','11524','11525','11819','11818') ";
	if ($linha == "LAVADORAS LS" AND $login_fabrica == 15) $cond_1 = " tbl_produto.produto in('21639','21638','11529','11820','11863','11552','11553','11530','11531','11521','11522','11532','11533','11838','11984','11821','11911','12015','12008','11519','11520','12002','11854','11528','11542','11912','11511','11913','11523','11526','11527','11510') ";
	if ($linha == "LAVADORAS LX" AND $login_fabrica == 15) $cond_1 = " tbl_produto.produto in('21645','21644','21643','21642','21639','21638','11745','12003','11746','11917','11916','11991','11914','11915') ";
	if ($linha == "Purificadores / Bebedouros - Eletrônicos" AND $login_fabrica == 15) $cond_1 = " tbl_produto.produto in('12007','12017') ";
*/


	$sql = "SELECT tbl_produto.produto, tbl_produto.ativo, tbl_produto.referencia, tbl_produto.descricao, ";

	if($login_fabrica == 20 and $pais !='BR')
		$sql .="tbl_produto_idioma.descricao as descricao_espanhol,";

		$sql .= "fcr1.qtde AS ocorrencia, tbl_produto.familia, tbl_produto.linha
				 FROM tbl_produto";

	if($login_fabrica == 20 and $pais !='BR')
		$sql .=" JOIN tbl_produto_idioma ON tbl_produto.produto = tbl_produto_idioma.produto";

		$sql .=" JOIN (SELECT tbl_os.produto, COUNT(*) AS qtde
						FROM tbl_os
						JOIN (
							SELECT tbl_os_extra.os , (
									SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1)	AS status
								FROM tbl_os_extra
								JOIN tbl_extrato        USING (extrato)
								JOIN tbl_extrato_extra  ON tbl_extrato_extra.extrato = tbl_extrato.extrato
								WHERE tbl_extrato.fabrica = $login_fabrica
								AND tbl_extrato.data_geracao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
					) fcr ON tbl_os.os = fcr.os
					JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
					WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
					AND tbl_os.excluida IS NOT TRUE
					AND $cond_2
					AND $cond_3
					AND $cond_4
					AND $cond_5
					GROUP BY tbl_os.produto
			) fcr1 ON tbl_produto.produto = fcr1.produto
			WHERE $cond_1
			ORDER BY fcr1.qtde DESC " ;


		$sql = "
				SELECT tbl_os_extra.os
				INTO TEMP tmp_fcrl_$login_admin
				FROM tbl_os_extra
				JOIN tbl_extrato        USING (extrato)
				JOIN tbl_extrato_extra  ON tbl_extrato_extra.extrato = tbl_extrato.extrato
				WHERE tbl_extrato.fabrica = $login_fabrica
				AND tbl_extrato.data_geracao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ;
				CREATE INDEX tmp_fcrl_OS_$login_admin ON tmp_fcrl_$login_admin(os);

				SELECT tbl_os.produto, COUNT(*) AS qtde
				INTO TEMP tmp_fcr2_$login_admin
				FROM tbl_os
				JOIN tmp_fcrl_$login_admin fcr ON tbl_os.os = fcr.os
				JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
				WHERE fabrica = $login_fabrica
				AND tbl_os.excluida IS NOT TRUE
				AND $cond_2
				AND $cond_3
				AND $cond_4
				AND $cond_5
				AND $cond_6
				GROUP BY tbl_os.produto;

				CREATE INDEX tmp_fcr2_produto_$login_admin ON tmp_fcr2_$login_admin(produto);

				SELECT  tbl_produto.produto    ,
						tbl_produto.ativo      ,
						tbl_produto.referencia ,
						tbl_produto.descricao  ,
						fcr1.qtde AS ocorrencia,
						tbl_produto.familia    ,
						tbl_produto.linha
				FROM tbl_produto
				JOIN tmp_fcr2_$login_admin fcr1 ON tbl_produto.produto = fcr1.produto
				WHERE $cond_1
				ORDER BY fcr1.qtde DESC " ;

if($login_fabrica==24){
	$sql = "SELECT tbl_produto.produto, tbl_produto.ativo, tbl_produto.referencia, tbl_produto.descricao, fcr1.qtde AS ocorrencia, tbl_produto.familia, tbl_produto.linha
			FROM tbl_produto
			JOIN (SELECT tbl_os.produto, COUNT(*) AS qtde
					FROM tbl_os
					JOIN (SELECT tbl_os_extra.os
							FROM tbl_os_extra
							JOIN tbl_extrato       USING (extrato)
							JOIN tbl_extrato_extra USING (extrato)
							WHERE tbl_extrato.fabrica = $login_fabrica
							AND  tbl_extrato.data_geracao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ) fcr ON tbl_os.os = fcr.os
					JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
					WHERE tbl_os.excluida IS NOT TRUE
					AND $cond_2
					AND $cond_3
					AND $cond_4
					AND $cond_5
					GROUP BY tbl_os.produto
			) fcr1 ON tbl_produto.produto = fcr1.produto
			WHERE $cond_1
			ORDER BY fcr1.qtde DESC " ;
}
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$total = 0;
		echo "<br>";

		echo "<b>Resultado de pesquisa entre os dias $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado $mostraMsgPais </b>";

		echo "<br><br>";

		if ($login_fabrica==5){
			echo "<div name='leg' align='center' style='padding-left:10px'>";
			echo "<b style='border:1px solid #666666;background-color:#FFCCCC;'>&nbsp; &nbsp;&nbsp;</b>&nbsp;<b> Produtos que estão inativos</b>";
			echo "</div>";
		}else{
			echo "<FONT SIZE=\"2\">(*) Produtos que estão inativos.</FONT>";
		}

		echo "<TABLE width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo "	<TR>";
		echo "		<TD width='30%' height='15' class='table_line'><b>Referência</b></TD>";
		echo "		<TD width='55%' height='15' class='table_line'><b>Produto</b></TD>";
		echo "		<TD width='10%' height='15' class='table_line'><b>Ocorrência</b></TD>";
		echo "		<TD width='05%' height='15' class='table_line'><b>%</b></TD>";
		echo "	</TR>";

		for ($x = 0; $x < pg_numrows($res); $x++) {
			$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,ocorrencia);
		}

		for ($i=0; $i<pg_numrows($res); $i++){
			$referencia   = trim(pg_result($res,$i,referencia));
			$ativo        = trim(pg_result($res,$i,ativo));
			$descricao    = trim(pg_result($res,$i,descricao));
			if($login_fabrica == 20 and $pais !='BR'){
				$descricaoes  = trim(pg_result($res,$i,descricao_espanhol));
			}
			$produto      = trim(pg_result($res,$i,produto));
			if (strlen($linha) > 0) $linha = trim(pg_result($res,$i,linha));
			$ocorrencia = trim(pg_result($res,$i,ocorrencia));

			if ($total_ocorrencia > 0) $porcentagem = (($ocorrencia * 100) / $total_ocorrencia);

			$cor = ($i % 2 == 0) ? '1' : '2';

			$ativo = ($ativo == 'f') ? "<B>*</B>" : '';

			if ($login_fabrica==5 and $ativo=='f') {
				$ativo="";
				$cor="3";
			}

			echo "<TR class='bgTRConteudo$cor'>";
			echo "<TD class='conteudo10' align='left' nowrap>$ativo<a href='javascript:AbrePeca(\"$produto\",\"$aux_data_inicial\",\"$aux_data_final\",\"$linha\",\"$estado\",\"$pais\",\"$tipo_os\");'>$referencia</a></TD>";
			if($login_fabrica == 20 and $pais != 'BR')
				echo "<TD class='conteudo10' align='left' nowrap>$descricaoes</TD>";
			else
				echo "<TD class='conteudo10' align='left' nowrap>$descricao</TD>";
				echo "<TD class='conteudo10' align='center' nowrap>$ocorrencia</TD>";
				echo "<TD class='conteudo10' align='right' nowrap>". number_format($porcentagem,2,",",".") ." %</TD>";
				echo "</TR>";
				$total = $ocorrencia + $total;
		}
		echo "<tr class='table_line'><td colspan='2'><font size='2'><b><CENTER>TOTAL DE PRODUTOS COM DEFEITOS</b></td><td colspan='2'><font size='2' color='009900'><b>$total</b></td></tr>";
		echo " </TABLE>";

		echo "<br>";
		echo "<hr width='600'>";
		echo "<br>";

		echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='relatorio_field_call_rate_produto-xls.php?data_inicial=$aux_data_inicial&data_final=$aux_data_final&linha=$linha&estado=$estado&pais=$pais&criterio=$criterio' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		echo "</tr>";
		echo "</table>";
	}else{
		echo "<br>";
		echo "<b>Nenhum resultado encontrado entre $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado $mostraMsgPais</b>";
	}
}
?>
<p>
<? include "rodape.php" ?>