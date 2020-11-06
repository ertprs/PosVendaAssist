<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

if (strlen($_GET["referencia"]) > 0)  $referencia = trim($_GET["referencia"]);
if (strlen($_POST["referencia"]) > 0) $referencia = trim($_POST["referencia"]);
if (strlen($_POST["garantia_select"]) > 0) $garantiaPecaValue = trim($_POST["garantia_select"]);

if (strlen($_POST["btnacao"]) > 0) $btnacao = trim($_POST["btnacao"]);


if ($btnacao == "confirmar") {

	if( (strlen($referencia)==0 && $login_fabrica != 3) || (strlen($referencia)==0 && empty($garantiaPecaValue) && $login_fabrica == 3) )
		$msg_erro = traduz("Preencha os Parâmetros de Pesquisa");

		if (!empty($garantiaPecaValue)) {
			$condGar = ($garantiaPecaValue == 'sim') ? " AND libera_garantia IS TRUE " : " AND libera_garantia IS NOT TRUE ";
		}

	if (strlen($peca) > 0) {
		$sql = "SELECT  tbl_peca_fora_linha.referencia,
						(
						SELECT tbl_peca.descricao
						FROM   tbl_peca
						WHERE  tbl_peca.referencia = tbl_peca_fora_linha.referencia
						AND tbl_peca.fabrica = $login_fabrica
						) AS descricao
				FROM    tbl_peca_fora_linha
				WHERE   tbl_peca_fora_linha.fabrica = $login_fabrica
				AND     tbl_peca_fora_linha.referencia  = '$referencia'
				$condGar;";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows($res) > 0) {
			$referencia   = trim(pg_result($res,0,referencia));
			$descricao    = trim(pg_result($res,0,descricao));
		}
	}
}

$layout_menu = 'callcenter';
$title = traduz("CONSULTAS PEÇAS FORA DE LINHA");

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

<script language="JavaScript">
	$(function(){
		Shadowbox.init();
		
		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});
	});


	function retorna_peca(retorno){
        $("#peca_referencia").val(retorno.referencia);
		$("#peca_descricao").val(retorno.descricao);
    }

function fnc_pesquisa_peca_fora_linha (campo, tipo, controle) {

	if (campo != "") {
		var url = "";
		url = "peca_fora_linha_pesquisa.php?controle=" + controle + "&campo=" + campo + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.referencia= document.frm_peca_fora_linha.referencia;
		janela.descricao = document.frm_peca_fora_linha.descricao;
		janela.focus();
	}

	else{
		alert('<?=traduz("Informar toda ou parte da informação para realizar a pesquisa!")?>');
	}
}
</script>

<? if (strlen($msg_erro) > 0) { ?>
	<div class="alert alert-danger"><h4><? echo $msg_erro; ?></h4></div>
<? } ?>
<form name="frm_peca_fora_linha" method="post" action="<? echo $PHP_SELF ?>" class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela '><?=traduz('Parâmetros de Pesquisa')?></div>
	<br />
	<div class='row-fluid'>
		<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label'><?=traduz('Referência Peça')?></label>
						<div class='controls controls-row input-append'>
							<div class='span4'>
								<?php if ($login_fabrica != 3) { ?>
									<h5 class='asteristico'>*</h5>
								<?php } ?>
								<input type="text" id="peca_referencia" name="referencia" value="<? echo $referencia; ?>" size="20" maxlength="20" class="frm"><span class='add-on' rel="lupa"><i class='icon-search'></i></span><input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
							</div>
						</div>
					</div>
				</div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label'><?=traduz('Descrição')?></label>
						<div class='controls controls-row input-append'>
							<div class='span4'>
								<?php if ($login_fabrica != 3) { ?>
									<h5 class='asteristico'>*</h5>
								<?php } ?>
								<input type="text" id="peca_descricao" name="descricao"  value="<? echo $descricao; ?>"  size="50" maxlength="50" class="frm"><span class='add-on' rel="lupa"><i class='icon-search' ></i></span><input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
							</div>
						</div>
					</div>
				</div>
			<div class='span2'></div>
	</div>
	<?php if ($login_fabrica == 3) { ?>
		<div class="row-fluid">
			<div class='span2'></div>
			<div class="span4">
				<label class="checkbox"><?=traduz("Garantia")?></label>
				<div class='controls controls-row'>
					<div class='span8 input-append'>
						<select name="garantia_select" class="span12">
							<option value="" <?=(empty($garantiaPecaValue)) ? 'selected' : ''?> ><?=traduz("Selecione")?></option>
							<option value="sim" <?=($garantiaPecaValue == 'sim') ? 'selected' : ''?>><?=traduz("Sim")?></option>
							<option value="nao" <?=($garantiaPecaValue == 'nao') ? 'selected' : ''?>><?=traduz("Não")?></option>
						</select>
					</div>
				</div>
			</div>
		</div>
	<?php } ?>
	<input type='hidden' name='btnacao' value=''>
	<input class="btn" type="button"  value='<?=traduz("Pesquisar")?>' ONCLICK="javascript: if (document.frm_peca_fora_linha.btnacao.value == '' ) { document.frm_peca_fora_linha.btnacao.value='confirmar'; document.frm_peca_fora_linha.submit() } else { alert ('<?=traduz("Aguarde submissão")?>') }" ALT='<?=traduz("Confirmar dados")?>'' border='0' >
	<br /><br />
</form>

<br>

<?
if ($btnacao == "confirmar") {
	if (strlen($referencia) > 0 || ($login_fabrica == 3 && !empty($garantiaPecaValue))) {

		if (!empty($garantiaPecaValue)) {
			$condGar = ($garantiaPecaValue == 'sim') ? " AND libera_garantia IS TRUE " : " AND libera_garantia IS NOT TRUE ";
		}

		if (!empty($referencia)) {
			$sql = "SELECT  tbl_peca_fora_linha.peca_fora_linha,
							tbl_peca_fora_linha.referencia     ,
							(
								SELECT tbl_peca.descricao
								FROM   tbl_peca
								WHERE  tbl_peca.referencia = tbl_peca_fora_linha.referencia
								AND    tbl_peca.fabrica = $login_fabrica
							) AS descricao      ,
							(
								SELECT tbl_peca.referencia_fabrica
								FROM   tbl_peca
								WHERE  tbl_peca.referencia = tbl_peca_fora_linha.referencia
								AND    tbl_peca.fabrica = $login_fabrica
							) AS referencia_fabrica                     ,
							libera_garantia                    ,
							TO_CHAR(tbl_peca_fora_linha.digitacao,'DD/MM/YYYY') AS digitacao
					FROM    tbl_peca_fora_linha
					WHERE   tbl_peca_fora_linha.fabrica = $login_fabrica
					AND     tbl_peca_fora_linha.referencia  = '$referencia'
					$condGar
					ORDER BY descricao;";
		} else {
			$sql = "SELECT  tbl_peca_fora_linha.peca_fora_linha,
							tbl_peca_fora_linha.referencia     ,
							tbl_peca.descricao                 ,
							tbl_peca.referencia_fabrica        ,
							libera_garantia                    ,
							TO_CHAR(tbl_peca_fora_linha.digitacao,'DD/MM/YYYY') AS digitacao
					FROM    tbl_peca_fora_linha
					JOIN    tbl_peca USING(peca)
					WHERE   tbl_peca_fora_linha.fabrica = $login_fabrica
					$condGar
					ORDER BY descricao;";
		}

		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<table align='center'>\n";
			echo "<table class='table table-striped table-bordered table-hover table-large'>";
			echo "<tr class='titulo_coluna'>";
				if ($login_fabrica == 171) {
					echo "<th>".traduz("Referência Fábrica")."</th>";
				}
				echo "<th>".traduz("Referência")."</th>";
				echo "<th>".traduz("Descrição")."</th>";
				if($login_fabrica==3)echo "<th>".traduz("Inclusão")."</th>";
				echo "<th>".traduz("Liberado garantia?")."</th>";
			echo "</tr>";
			for ($y = 0 ; $y < pg_numrows($res) ; $y++){
			$peca_fora_linha = trim(pg_result($res,$y,peca_fora_linha));
			$referencia      = trim(pg_result($res,$y,referencia));
			$referencia_fabrica      = trim(pg_result($res,$y,referencia_fabrica));
			$descricao       = trim(pg_result($res,$y,descricao));
			$libera_garantia = trim(pg_result($res,$y,libera_garantia));
			$digitacao       = trim(pg_result($res,$y,digitacao));
			
			if($y%2==0)  $cor = "#F7F5F0";
			else         $cor = "#F1F4FA";
			
				if($libera_garantia=="t"){$libera_garantia="Sim";}else{$libera_garantia="Não";}
				echo "<tr bgcolor='$cor'>";
				if ($login_fabrica == 171) {
					echo "<td><font size='1'>$referencia_fabrica</font></td>";
				}
				echo "<td>";
				echo "<font size='1'>$referencia </font>";
				echo "</td>";
				echo "<td align='left'>";
				echo "<font size='1'>$descricao</font>";
				echo "</td>";
				if($login_fabrica==3)echo "<td><font size='1'>$digitacao</font></td>";
				echo "<td>";
				echo "<font size='1'>$libera_garantia</font>";
				echo "</td>";
				echo "</tr>";
			}
			echo"</table>";
		}else{
			echo "<table align='center'>\n";
			echo "<tr>\n";
			echo "<td ><B>".traduz("Peça $referencia não encontrada.")."</B></td>\n";
			echo "</tr>\n";
			echo "</table>\n";
		}
		echo "<br><br>\n";
	}
}


// todas as peças
if (strlen($_GET["exibir"]) > 0)  $exibir = trim($_GET["exibir"]);
if (strlen($_POST["exibir"]) > 0) $exibir = trim($_POST["exibir"]);

if (strlen($exibir) > 0 AND $exibir == 'todas'){
	$sql = "SELECT  tbl_peca_fora_linha.peca_fora_linha,
					tbl_peca_fora_linha.referencia     ,
					tbl_peca.descricao                 ,
					tbl_peca.referencia_fabrica                 ,
						libera_garantia,
						TO_CHAR(tbl_peca_fora_linha.digitacao,'DD/MM/YYYY') AS digitacao
			FROM    tbl_peca_fora_linha
			JOIN    tbl_peca ON tbl_peca.peca=tbl_peca_fora_linha.peca
			WHERE   tbl_peca_fora_linha.fabrica = $login_fabrica
			ORDER BY descricao";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<table class='table table-striped table-bordered table-large'>";
		echo "<thead><tr class='titulo_tabela'>";
			if ($login_fabrica == 171) {
				echo "<th>".traduz("Referência Fábrica")."</th>";
			}
			echo "<th>".traduz("Referência")."</th>";
			echo "<th>".traduz("Descrição")."</th>";
			if($login_fabrica==3)echo "<th>".traduz("Inclusão")."</th>";
			echo "<th>".traduz("Liberado garantia?")."</th>";
		echo "</thead></tr>";
		for ($y = 0 ; $y < pg_numrows($res) ; $y++){
			$peca_fora_linha = trim(pg_result($res,$y,peca_fora_linha));
			$referencia      = trim(pg_result($res,$y,referencia));
			$referencia_fabrica      = trim(pg_result($res,$y,referencia_fabrica));
			$descricao       = trim(pg_result($res,$y,descricao));
			$libera_garantia = trim(pg_result($res,$y,libera_garantia));
			$digitacao       = trim(pg_result($res,$y,digitacao));
			
			if($y%2==0)  $cor = "#F7F5F0";
			else         $cor = "#F1F4FA";

				if($libera_garantia=="t"){$libera_garantia="Sim";}else{$libera_garantia="Não";}
				echo "<tr bgcolor='$cor'>";
				if ($login_fabrica == 171) {
					echo "<td><font size='1'>$referencia_fabrica</font></td>";
				}

				echo "<td>";
				echo "<font size='1'>$referencia </font>";
				echo "</td>";
				echo "<td align='left'>";
				echo "<font size='1'>$descricao</font>";
				echo "</td>";
				if($login_fabrica==3)echo "<td><font size='1'>$digitacao</font></td>";
				echo "<td>";
				echo "<font size='1'>$libera_garantia</font>";
				echo "</td>";
				echo "</tr>";
		}
		echo"</table>";
		
	}
}
?>
<center><input class="btn btn-primary" type="button" onclick="javascript: window.location='<? echo $PHP_SELF;?>?exibir=todas'" value='<?=traduz("Exibir Todas as Peças fora de Linha")?>'></center>
<br>
<br>

<?
include "rodape.php";
?>