<?php
$areaAdminCliente = preg_match('/\/admin_cliente\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

if ($areaAdminCliente == true) {
    include_once "../dbconfig.php";
    include_once "../includes/dbconnect-inc.php";
    //include 'autentica_admin.php';
    include_once '../funcoes.php';
} else {
    $admin_privilegios = "call_center";
    include "dbconfig.php";
	include "includes/dbconnect-inc.php";
}

//if($login_fabrica<>19)$admin_privilegios="gerencia";
include "autentica_admin.php";

##### Função para exibe os Estados #####
function selectUF($selUF=""){
	$cfgUf = array("","AC","AL","AM","AP","BA","CE","DF","ES","GO","MA","MG","MS","MT","PA","PB","PI","PE","PR","RJ","RN","RO","RR","RS","SC","SE","SP","TO");
	if($selUF == "") $selUF = $cfgUf[0];

	$totalUF = count($cfgUf) - 1;
	for($currentUF=0; $currentUF <= $totalUF; $currentUF++){
		echo "                      <option value=\"$cfgUf[$currentUF]\"";
		if($selUF == $cfgUf[$currentUF]) print(" selected");
		echo ">$cfgUf[$currentUF]</option>\n";
	}
}
if($login_fabrica==19) $layout_menu="callcenter";
else                   $layout_menu = "gerencia";
$title = "Relatório de Ordens de Serviços";

include "cabecalho.php";

// include "javascript_calendario.php"; //adicionado por Gustavo 2009-03-06
include "../js/js_css.php";

?>

<script type="text/javascript">
$(function(){
    $('#data_inicial').datepick({startDate:'01/01/2000'});
    $('#data_final').datepick({startDate:'01/01/2000'});
    $("#data_inicial").mask("99/99/9999");
    $("#data_final").mask("99/99/9999");

    $('#extrato_data_inicial').datepick({startDate:'01/01/2000'});
    $('#extrato_data_final').datepick({startDate:'01/01/2000'});
    $("#extrato_data_inicial").mask("99/99/9999");
    $("#extrato_data_final").mask("99/99/9999");
});

function CarregaDefeito (campo, tipo) {
	if (tipo == "reclamado") {
		RemoveDefeito ("defeito_reclamado");
		document.all.FrameDefeito.src = "carrega_defeitos.php?reclamado_familia=" + campo.value + "&tipo=reclamado";
	}
	if (tipo == "constatado") {
		RemoveDefeito ("defeito_constatado");
		document.all.FrameDefeito.src = "carrega_defeitos.php?constatado_familia=" + campo.value + "&tipo=constatado";
	}
}

function RemoveDefeito (objeto) {
	var tamanho = document.frm_consulta[objeto].length;
	while (tamanho > 0) {
		document.frm_consulta[objeto].remove(tamanho-1);
		tamanho--;
	}
}

function AdicionaDefeito (texto, valor, objeto) {
	linha = document.createElement("option");
	linha.text = texto;
	linha.value = valor;
	document.frm_consulta[objeto].add(linha);
}

function selecionaCheck(num){

	$("input[rel=check]").removeAttr("checked");
	$("input[name^=chk_opt"+num+"]").attr("checked", true);
}

</script>

<style type="text/css">
.titulo_tabela{
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

<? include "javascript_pesquisas.php" ?>
<div style='width:700px;' class='texto_avulso'>
	Este Relatório considera a Data de Digitação da OS
</div>
<br />
<form name="frm_consulta" method="get" action="defeito_os_consulta-xls.php">
<input type='hidden' name='btn_acao' value='pesquisar'>
<table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class='formulario'>
	<tr class='titulo_tabela'><td colspan='6'>Parâmetros de Pesquisa</td></tr>
	<tr>
		<td colspan="6"><hr color="#EEEEEE"></td>
	</tr>
	<tr >
		<td colspan="6"><center><input type='button' value='Pesquisar' onClick="document.frm_consulta.submit();" style="cursor: pointer;" alt="Preencha as opções e clique aqui para pesquisar"></center></td>
	</tr>
	<tr >
		<td colspan="6"><hr color="#EEEEEE"></td>
	</tr>
	<tr >
		<td width="10">&nbsp;</td>
		<td colspan="4"><input type="checkbox" name="chk_opt1" value="1" rel="check" class="frm" onclick="selecionaCheck(this.value)"> OS Lançadas Hoje</td>
		<td width="70">&nbsp;</td>
	</tr>
	<tr >
		<td>&nbsp;</td>
		<td colspan="4"><input type="checkbox" name="chk_opt2" value="2" rel="check" class="frm" onclick="selecionaCheck(this.value)"> OS Lançadas Ontem</td>
		<td>&nbsp;</td>
	</tr>
	<tr >
		<td>&nbsp;</td>
		<td colspan="4"><input type="checkbox" name="chk_opt3" value="3" rel="check" class="frm" onclick="selecionaCheck(this.value)"> OS Lançadas Nesta Semana</TD>
		<td>&nbsp;</td>
	</tr>
	<tr >
		<td>&nbsp;</td>
		<td colspan="4"><input type="checkbox" name="chk_opt4" value="4" rel="check" class="frm" onclick="selecionaCheck(this.value)"> OS Lançadas Neste Mês</TD>
		<td>&nbsp;</td>
	</tr>
	<tr >
		<td colspan="6"><hr color="#EEEEEE"></td>
	</tr>
	<tr >
		<td>&nbsp;</td>
		<td width="130"><input type="radio" name="consumidor_revenda" value="T" checked> Todas</td>
		<td width="130"><input type="radio" name="consumidor_revenda" value="C"> Consumidor</td>
		<td width="160"><input type="radio" name="consumidor_revenda" value="R"> Revenda</td>
		<td colspan='2'>&nbsp;</td>
	</tr>
	<tr >
		<td colspan="6"><hr color="#EEEEEE"></td>
	</tr>
	<tr >
		<td>&nbsp;</td>
		<td colspan="2">Situação da OS</TD>
		<td>
			<select name="situacao" size="1" class="frm">
				<option value="" selected>Todas</option>
				<option value="IS NULL">Em Aberto</option>
				<option value="NOTNULL">Fechadas</option>
			</select>
		</td>
		<td colspan='2'>&nbsp;</td>
	</tr><?php
	if ($login_fabrica == 19) {//HD 227132
		//OBS quando adicionar novas fábricar verificar os tipos de atendimento
		//pois cada fabricante tem regras diferentes, verificar no arquivo os_cadastro_tudo.php?>
		<tr >
			<td colspan="6"><hr color="#EEEEEE"></td>
		</tr>
		<tr >
			<td>&nbsp;</td>
			<td colspan="2">Tipo de Atendimento da OS</TD>
			<td>
				<select name="tipo_atendimento" size="1" class="frm">
					<option value="">Todos</option><?php
					$sql = "SELECT *
							  FROM tbl_tipo_atendimento
							 WHERE fabrica = $login_fabrica
							   AND ativo IS TRUE
					ORDER BY tipo_atendimento";

					$res = pg_query ($con,$sql);

					for ($i = 0; $i < pg_num_rows($res); $i++) {
						$tipo_atend = pg_fetch_result($res,$i,'tipo_atendimento');
						$desc_atend = pg_fetch_result($res,$i,'descricao');
						$cod_atend  = pg_fetch_result($res,$i,'codigo');
						echo '<option value="'.$tipo_atend.'">'.$cod_atend . ' - ' .$desc_atend.'</option>';
					}?>
				</select>
			</td>
			<td colspan='2'>&nbsp;</td>
		</tr><?php
	}?>
	<tr >
		<td colspan="6"><hr color="#EEEEEE"></td>
	</tr>
	<tr >
		<td>&nbsp;</td>
		<td colspan="2"><input type="checkbox" name="chk_opt5" value="5" class="frm"> OS Lançadas em Aberto</td>
		<td colspan='3'>Quantidade de Dias em Aberto <input type="text" size="3" maxlength="3" name="dia_em_aberto" value="" onclick="this.value=''" class="frm"></td>
	</tr>
	<tr >
		<td colspan="6"><hr color="#EEEEEE"></td>
	</tr>
	<tr >
		<td>&nbsp;</td>
		<td><input type="checkbox" name="chk_opt6" value="6" rel="check" class="frm" onclick="selecionaCheck(this.value)"> Entre Datas</td>
		<td width='130'>Data Inicial</td>
		<td>Data Final</td>
		<td colspan='2'>&nbsp;</td>
	</tr>
	<tr >
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td width='130'>
		<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10"  value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';"class='frm' >
		</td>
		<td>
		<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
		<td colspan='2'>&nbsp;</td>
	</tr>

	<tr >
		<td colspan="6"><hr color="#EEEEEE"></td>
	</tr>
<? if ($login_fabrica==14 or $login_fabrica == 43){ ?>

	<tr >
		<td>&nbsp;</td>
		<td><input type="checkbox" name="chk_opt21" value="21" class="frm" onclick="selecionaCheck(this.value)" rel="check"><acronym title='OS Aprovadas no extrato'> OS Aprovadas</acronym></td>
		<td width='130' >Data Inicial</td>
		<td colspan='3'>Data Final</td>
	</tr>
	<tr >
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td  width='130'>
		<input type="text" name="extrato_data_inicial" id="extrato_data_inicial" size="12" maxlength="10" class='frm' value="<? if (strlen($extrato_data_inicial) > 0) echo $extrato_data_inicial; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
		</td>

		<td colspan='3'>
		<input type="text" name="extrato_data_final" id="extrato_data_final" size="12" maxlength="10" class='frm' value="<? if (strlen($extrato_data_final) > 0) echo $extrato_data_final; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
		</td>

	</tr>
	<tr >
		<td colspan="6"><hr color="#EEEEEE"></td>
	</tr>
<? }?>
	<tr >
		<td>&nbsp;</td>
		<td><input type="checkbox" name="chk_opt7" value="7" class="frm"> Posto</td>
		<td>Código do Posto</td>
		<td>Nome do Posto</td>
		<td colspan='2'>Estado</td>
	</tr>
	<tr >
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td><input type="text" name="codigo_posto" size="8" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto (document.frm_consulta.codigo_posto,document.frm_consulta.nome_posto,'codigo')" <? } ?> class="frm"> <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto,document.frm_consulta.nome_posto,'codigo')"></td>
		<td><input type="text" name="nome_posto" size="18" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto (document.frm_consulta.codigo_posto,document.frm_consulta.nome_posto,'nome')" <? } ?> class="frm"> <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisas postos pelo nome" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto,document.frm_consulta.nome_posto,'nome')"></td>
		<td colspan='2'>
			<select name="estado_posto" class="frm" style='width:200px;'>
				<option value="centro-oeste" <? if ($estado_posto == "centro-oeste") echo " selected ";?>>Região Centro-Oeste (GO,MT,MS,DF)</option>
				<option value="nordeste"     <? if ($estado_posto == "nordeste")     echo " selected ";?>>Região Nordeste (MA,PI,CE,RN,PB,PE,AL,SE,BA)</option>
				<option value="norte"        <? if ($estado_posto == "norte")        echo " selected ";?>>Região Norte (AC,AM,RR,RO,PA,AP,TO)</option>
				<option value="sudeste"      <? if ($estado_posto == "sudeste")      echo " selected ";?>>Região Sudeste (MG,ES,RJ,SP)</option>
				<option value="sul"          <? if ($estado_posto == "sul")          echo " selected ";?>>Região Sul (PR,SC,RS)</option>
				<option value='SP-capital'   <? if ($estado_posto == "SP-capital")   echo " selected ";?>>SÃO PAULO - CAPITAL</option>
				<option value='SP-interior'  <? if ($estado_posto == "SP-interior")  echo " selected ";?>>SÃO PAULO - INTERIOR</option>
				<? selectUF($uf); ?>
			</select>
		</td>
	</tr>
	<tr >
		<td colspan="6"><hr color="#EEEEEE"></td>
	</tr>
	<tr >
		<td>&nbsp;</TD>
		<td><input type="checkbox" name="chk_opt8" value="8" class="frm"> Produto</td>
		<td>Referência</td>
		<td>Descrição</td>
		<td colspan='2'>Voltagem</TD>
	</tr>
	<tr >
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td><input type="text" name="produto_referencia" size="8" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_produto (document.frm_consulta.produto_referencia,document.frm_consulta.produto_nome,'referencia',document.frm_consulta.produto_voltagem)" <? } ?> class="frm"> <img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia,document.frm_consulta.produto_nome,'referencia',document.frm_consulta.produto_voltagem)"></td>
		<td><input type="text" name="produto_nome" size="18" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_produto (document.frm_consulta.produto_referencia,document.frm_consulta.produto_nome,'descricao',document.frm_consulta.produto_voltagem)" <? } ?> class="frm"> <img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisas pela referência do aparelho." onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia,document.frm_consulta.produto_nome,'descricao',document.frm_consulta.produto_voltagem)"></td>
		<td colspan='2'><input type="text" name="produto_voltagem" size="7" class="frm"></td>
	</tr>
	<tr >
		<td colspan="6"><hr color="#EEEEEE"></td>
	</tr>
	<tr >
		<td>&nbsp;</td>
		<td colspan="2"><input type="checkbox" name="chk_opt9" value="9" class="frm"> Serviço Realizado</td>
		<td colspan="3">
			<?
			$sql =	"SELECT servico_realizado ,
							descricao
					FROM tbl_servico_realizado
					WHERE fabrica = $login_fabrica;";
			$res = pg_exec($con,$sql);

			if (pg_numrows($res) > 0) {
				echo "<select name='servico_realizado' size='1' class='frm' style='width: 209px'>";
				echo "<option value=''></option>";
				for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
					echo "<option value='" . pg_result($res, $i, servico_realizado) . "'>" . substr(pg_result($res, $i, descricao), 0, 23) . "</option>";
				}
				echo "</select>";
			}else{
				echo "&nbsp;";
			}
			?>
		</td>
	</tr>
	<tr >
		<td colspan="6"><hr color="#EEEEEE"></td>
	</tr>
	<tr >
		<td>&nbsp;</td>
		<td colspan="2"><input type="checkbox" name="chk_opt10" value="10" class="frm"> Defeito em Peça</td>
		<td colspan="3">
			<?
			$sql =	"SELECT defeito   ,
							descricao
					FROM tbl_defeito
					WHERE fabrica = $login_fabrica;";
			$res = pg_exec($con,$sql);

			if (pg_numrows($res) > 0) {
				echo "<select name='defeito' size='1' class='frm' style='width: 209px'>";
				echo "<option value=''></option>";
				for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
					echo "<option value='" . pg_result($res, $i, defeito) . "'>" . substr(pg_result($res, $i, descricao), 0, 23) . "</option>";
				}
				echo "</select>";
			}else{
				echo "&nbsp;";
			}
			?>
		</td>
	</tr>
	<tr >
		<td colspan="6"><hr color="#EEEEEE"></td>
	</tr>
	<tr >
		<td>&nbsp;</td>
		<td colspan="2"><input type="checkbox" name="chk_opt11" value="11" class="frm"> Defeito Reclamado</td>
		<td colspan="3">
			Família<br>
			<select name="reclamado_familia" size="1" class="frm" style="width: 209px" <? if($login_fabrica <> 43) { ?> onChange="javascript: CarregaDefeito (this, 'reclamado'); <? } ?>">
			<option value=""></option>
			<?
			if($login_fabrica == 43) {
				$sql =	"SELECT  DISTINCT
								 tbl_familia.familia   ,
								 tbl_familia.descricao
						FROM     tbl_familia
						WHERE    tbl_familia.fabrica = $login_fabrica
						ORDER BY tbl_familia.descricao;";
			}else{
				$sql =	"SELECT  DISTINCT
							 tbl_familia.familia   ,
							 tbl_familia.descricao
					FROM     tbl_defeito_reclamado
					JOIN     tbl_familia USING (familia)
					WHERE    tbl_familia.fabrica = $login_fabrica
					ORDER BY tbl_familia.descricao;";
			}
			$res_reclamado1 = pg_exec($con,$sql);

			if (pg_numrows($res_reclamado1) > 0) {
				for ($i = 0 ; $i < pg_numrows($res_reclamado1) ; $i++) {
					echo "<option value='" . pg_result($res_reclamado1,$i,familia) . "'>" . substr(pg_result($res_reclamado1,$i,descricao), 0, 23) . "</option>";
				}
			}
			?>
			</select>
		</td>
	</tr>
	<tr >
		<td colspan="3">&nbsp;</td>
		<td colspan="3">
			Defeito<br>
			<select name="defeito_reclamado" size="1" class="frm" style="width: 209px">
			<?
			if($login_fabrica == 43) {
				$sql =	"SELECT  DISTINCT
								 tbl_defeito_reclamado.defeito_reclamado ,
								 tbl_defeito_reclamado.descricao
						FROM     tbl_defeito_reclamado
						WHERE    tbl_defeito_reclamado.fabrica = $login_fabrica
						ORDER BY tbl_defeito_reclamado.descricao;";
				$res_reclamado2 = pg_exec($con,$sql);
			}
			if (@pg_numrows($res_reclamado2) > 0) {
				echo "<option value=''></option>";
				for ($i = 0 ; $i < @pg_numrows($res_reclamado2) ; $i++) {
					echo "<option value='" . @pg_result($res_reclamado2,$i,defeito_reclamado) . "'>" . @pg_result($res_reclamado2,$i,descricao) . "</option>";
				}
			}
			?>
		</td>
	</tr>
	<tr >
		<td colspan="6"><hr color="#EEEEEE"></td>
	</tr>
	<tr >
		<td>&nbsp;</td>
		<td colspan="2"><input type="checkbox" name="chk_opt12" value="12" class="frm"> Defeito Constatado</td>
		<td colspan="3">
			Família<br>
			<select name="constatado_familia" size="1" class="frm" style="width: 209px" <? if($login_fabrica <> 43) { ?>
			onChange="javascript: CarregaDefeito (this, 'constatado'); <?}?>">
			<option value=""></option>
			<?
			if($login_fabrica == 43) {
				$sql =	"SELECT  DISTINCT
								 tbl_familia.familia   ,
								 tbl_familia.descricao
						FROM     tbl_familia
						WHERE    tbl_familia.fabrica = $login_fabrica
						ORDER BY tbl_familia.descricao;";
			}else{
				$sql =	"SELECT  DISTINCT
								 tbl_familia.familia   ,
								 tbl_familia.descricao
						FROM     tbl_defeito_constatado
						JOIN     tbl_familia_defeito_constatado USING (defeito_constatado)
						JOIN     tbl_familia ON tbl_familia_defeito_constatado.familia = tbl_familia.familia
						WHERE    tbl_defeito_constatado.fabrica = $login_fabrica
						ORDER BY tbl_familia.descricao;";
			}
			$res_constatado1 = pg_exec($con,$sql);

			if (pg_numrows($res_constatado1) > 0) {
				for ($i = 0 ; $i < pg_numrows($res_constatado1) ; $i++) {
					echo "<option value='" . pg_result($res_constatado1,$i,familia) . "'>" . substr(pg_result($res_constatado1,$i,descricao), 0, 23) . "</option>";
				}
			}
			?>
			</select>
		</td>
	</tr>
	<tr >
		<td colspan="3">&nbsp;</td>
		<td colspan="3">
			Defeito<br>
			<select name="defeito_constatado" size="1" class="frm" style="width: 209px">
			<?
			if($login_fabrica == 43) {
				$sql =	"SELECT  DISTINCT
								 tbl_defeito_constatado.defeito_constatado ,
								 tbl_defeito_constatado.descricao
						FROM     tbl_defeito_constatado
						WHERE    tbl_defeito_constatado.fabrica = $login_fabrica
						and      ativo is true
						ORDER BY tbl_defeito_constatado.descricao;";
				$res_constatado2 = pg_exec($con,$sql);
			}
			if (@pg_numrows($res_constatado2) > 0) {
				echo "<option value=''></option>";
				for ($i = 0 ; $i < @pg_numrows($res_constatado2) ; $i++) {
					echo "<option value='" . @pg_result($res_constatado2,$i,defeito_constatado) . "'>" . @pg_result($res_constatado2,$i,descricao) . "</option>";
				}
			}
			?>
			</select>
		</td>
	</tr>
	<tr >
		<td colspan="6"><hr color="#EEEEEE"></td>
	</tr>

	<?php if ($login_fabrica == 19) {?>
		<tr >
			<td>&nbsp;</td>
			<td colspan="2"><input type="checkbox" name="chk_opt22" value="22" class="frm"> Checklist</td>
			<td colspan="3">
				Checklist<br>
				<select name="checklist" size="1" class="frm" style="width: 209px">
				<option value=""></option>
				<?php
					$sqlCHECK =	"SELECT  DISTINCT
									 codigo, 
									 descricao 
							   FROM tbl_checklist_fabrica
							  WHERE fabrica = $login_fabrica
						   ORDER BY descricao ASC;";
					$resCHECK = pg_query($con, $sqlCHECK);

					if (pg_num_rows($resCHECK) > 0) {
						for ($i = 0 ; $i < pg_num_rows($resCHECK) ; $i++) {
							$codigo    = pg_fetch_result($resCHECK, $i, 'codigo');
							$descricao = pg_fetch_result($resCHECK, $i, 'descricao');
							echo "<option value='" . $codigo . "'>{$codigo}  -  {$descricao} </option>";
						}
					}
				?>
				</select>
			</td>
		</tr>
		<tr >
			<td colspan="6"><hr color="#EEEEEE"></td>
		</tr>
	<?php }?>
	<tr >
		<td>&nbsp;</td>
		<td colspan="2"><input type="checkbox" name="chk_opt13" value="13" class="frm"> Família</td>
		<td colspan="3">
			Família<br>
			<select name="familia" size="1" class="frm" style="width: 209px">
			<option value=""></option>
			<?
			$sql =	"SELECT  tbl_familia.familia   ,
							 tbl_familia.descricao
					FROM     tbl_familia
					WHERE    tbl_familia.fabrica = $login_fabrica
					ORDER BY tbl_familia.descricao;";
			$res_familia = pg_exec($con,$sql);

			if (pg_numrows($res_familia) > 0) {
				for ($i = 0 ; $i < pg_numrows($res_familia) ; $i++) {
					$familia           = pg_result($res_familia,$i,familia);
					$familia_descricao = pg_result($res_familia,$i,descricao);
					echo "<option value='" . $familia . "'>" . substr($familia_descricao, 0, 23) . "</option>\n";
				}
			}
			?>
			</select>
		</td>
	</tr>
	<tr >
		<td colspan="6"><hr color="#EEEEEE"></td>
	</tr>
	<tr >
		<td>&nbsp;</td>
		<td colspan="2"><input type="checkbox" name="chk_opt14" value="14" class="frm"> Número Série</td>
		<td colspan='2'><input type="text" name="numero_serie" size="17" class="frm"></td>
		<td>&nbsp;</TD>
	</tr>
	<tr >
		<td colspan="3">&nbsp;</td>
		<td colspan="3">
			Família<br>
			<select name="familia_serie" size="1" class="frm" style="width: 209px">
			<option value=""></option>
			<?
			$sql =	"SELECT  tbl_familia.familia   ,
							 tbl_familia.descricao
					FROM     tbl_familia
					WHERE    tbl_familia.fabrica = $login_fabrica
					ORDER BY tbl_familia.descricao;";
			$res_familia = pg_exec($con,$sql);

			if (pg_numrows($res_familia) > 0) {
				for ($i = 0 ; $i < pg_numrows($res_familia) ; $i++) {
					$familia_descricao = pg_result($res_familia,$i,descricao);
					echo "<option value='";
					if (strlen(strpos($familia_descricao, "CENTRAIS")) > 0) echo "CE";
					if (strlen(strpos($familia_descricao, "TELEFONES")) > 0) echo "TE";

					echo "'>" . substr($familia_descricao, 0, 23) . "</option>\n";
				}
			}
			?>
			</select>
		</td>
	</tr>
	<tr >
		<td colspan="5"><hr color="#EEEEEE"></td>
	</tr>
	<tr >
		<td>&nbsp;</td>
		<td colspan="2"><input type="checkbox" name="chk_opt15" value="15" class="frm"> Nome do Consumidor</td>
		<td><input type="text" name="nome_consumidor" size="17" class="frm"></td>
		<td colspan='2'>&nbsp;</td>
	</tr>
	<tr >
		<td>&nbsp;</td>
		<td colspan="2"><input type="checkbox" name="chk_opt16" value="16" class="frm"> CPF/CNPJ do Consumidor</td>
		<td><input type="text" name="cpf_consumidor" size="17" class="frm"></td>
		<td colspan='2'>&nbsp;</td>
	</tr>
	<tr >
		<td>&nbsp;</td>
		<td colspan="2"><input type="checkbox" name="chk_opt17" value="17" class="frm"> Cidade</td>
		<td><input type="text" name="cidade" size="17" class="frm"></td>
		<td colspan='2'>&nbsp;</td>
	</tr>
	<tr >
		<td>&nbsp;</td>
		<td colspan="2"><input type="checkbox" name="chk_opt18" value="18" class="frm"> Estado</TD>
		<td colspan="3">
			<select name="estado" size="1" class="frm">
				<option value="centro-oeste" <? if ($estado == "centro-oeste") echo " selected "; ?>>Região Centro-Oeste (GO,MT,MS,DF)</option>
				<option value="nordeste"     <? if ($estado == "nordeste")     echo " selected "; ?>>Região Nordeste (MA,PI,CE,RN,PB,PE,AL,SE,BA)</option>
				<option value="norte"        <? if ($estado == "norte")        echo " selected "; ?>>Região Norte (AC,AM,RR,RO,PA,AP,TO)</option>
				<option value="sudeste"      <? if ($estado == "sudeste")      echo " selected "; ?>>Região Sudeste (MG,ES,RJ,SP)</option>
				<option value="sul"          <? if ($estado == "sul")          echo " selected "; ?>>Região Sul (PR,SC,RS)</option>
				<option value='SP-capital'   <? if ($estado == "SP-capital")    echo " selected ";?>>SÃO PAULO - CAPITAL</option>
				<option value='SP-interior'  <? if ($estado == "SP-interior")   echo " selected ";?>>SÃO PAULO - INTERIOR</option>
				<? selectUF($uf); ?>
			</select>
		</td>
	</tr>
	<tr >
		<td>&nbsp;</td>
		<td colspan="2"><input type="checkbox" name="chk_opt19" value="19" class="frm"> Número da OS</td>
		<td><input type="text" name="numero_os" size="17" class="frm"></td>
		<td colspan='2'>&nbsp;</td>
	</tr>
	<tr >
		<td>&nbsp;</td>
		<td colspan="2"><input type="checkbox" name="chk_opt20" value="20" class="frm"> Número da NF de Compra</td>
		<td><input type="text" name="numero_nf" size="17" class="frm"></td>
		<td colspan='2'>&nbsp;</td>
	</tr>
	<tr >
		<td colspan="6"><hr color="#EEEEEE"></td>
	</tr>
	<tr >
		<td colspan="6"><center><input type='button' value='Pesquisar' border="0" onclick="document.frm_consulta.submit();" style="cursor: pointer;" alt="Preencha as opções e clique aqui para pesquisar"></center></td>
	</tr>
	<tr >
		<td colspan="6"><hr color="#EEEEEE"></td>
	</tr>
</table>

<iframe style="visibility: hidden; position: absolute;" id="FrameDefeito"></iframe>

</form>

<br>

<? include "rodape.php" ?>
