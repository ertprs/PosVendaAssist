<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

if (strlen($_GET["referencia_de"]) > 0)    $referencia_de = trim($_GET["referencia_de"]);
if (strlen($_POST["referencia_de"]) > 0)   $referencia_de = trim($_POST["referencia_de"]);

if (strlen($_GET["referencia_para"]) > 0)  $referencia_para = trim($_GET["referencia_para"]);
if (strlen($_POST["referencia_para"]) > 0) $referencia_para = trim($_POST["referencia_para"]);

if (strlen($_GET["btnacao"]) > 0)  $btnacao = trim($_GET["btnacao"]);
if (strlen($_POST["btnacao"]) > 0) $btnacao = trim($_POST["btnacao"]);


//14931 29/2/2008
if(strlen($referencia_de)==0 and strlen($referencia_para)==0 and $btnacao == 'pesquisar'){
	$msg_erro .= traduz("Digite um campo para pesquisa");
}

$layout_menu = 'callcenter';
$title = traduz("CONSULTA DE-PARA");

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
	$(function() {
		
	});

	function retorna_peca(retorno){
        $("referencia_de").val(retorno.referencia);
		$("descricao_de").val(retorno.descricao);
    }

function fnc_pesquisa_depara (campo, tipo, controle) {
	if (campo != "") {
		var url = "";
		url = "depara_pesquisa.php?controle=" + controle + "&campo=" + campo + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.referencia= document.frm_depara.referencia;
		janela.descricao = document.frm_depara.descricao;
		janela.focus();
	}

	else
		alert('<?=traduz("Informar toda ou parte da informação para realizar a pesquisa!")?>');
}
</script>
<?php if (strlen($msg_erro) > 0) { ?>
	<div class="alert alert-danger"><h4><?php echo $msg_erro; ?></h4></div>
<?php } ?>
<form name="frm_depara" method="post" action="<? echo $PHP_SELF ?>" class='form-search form-inline tc_formulario'>
<div class='titulo_tabela'><?=traduz('Parâmetros de Pesquisa')?></div>
	<br />
	<div class='row-fluid'>
		<div class='span2'></div>
				<div class='span4'>
					<div class='control-group'>
						<label class='control-label' for='referencia_peca'><?=traduz('Referência Peça DE')?></label>
						<div class='controls controls-row input-append'>
							<div class='span8'>
								<h5 class='asteristico'>*</h5>
								<input type="text" id='referencia_de' name="referencia_de" value="<? echo $referencia_de ?>" size="20" maxlength="20" class="frm"><span class='add-on' rel="lupa" src="imagens/lupa.png" onclick='javascript:fnc_pesquisa_depara(document.frm_depara.referencia_de.value, "referencia", "de")' style='cursor:pointer'><i class='icon-search'></i></span>
							</div>
						</div>	
					</div>
				</div>
				<div class='span4'>
					<div class='control-group'>
						<label class='control-label' for='descricao_peca'><?=traduz('Descrição DE')?></label>
						<div class='controls controls-row input-append'>
							<div class='span8'>
								<h5 class='asteristico'>*</h5>
								<input type="text" id="descricao_de" name="descricao_de" value="<? echo $descricao_de ?>" size="50" maxlength="50" class="frm"><span class='add-on' rel="lupa" src="imagens/lupa.png" onclick='javascript:fnc_pesquisa_depara(document.frm_depara.descricao_de.value,"descricao", "de")' style='cursor:pointer'><i class='icon-search'></i></span>
							</div>
						</div>
					</div>
				</div>
		<div class='span2'></div>
	</div>
	<br />
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for=''><?=traduz('Referência Peça PARA')?></label>
				<div class='controls controls-row input-append'>
					<div class='span8'>
						<h5 class='asteristico'>*</h5>
						<input type="text" id="referencia_para" name="referencia_para" value="<? echo $referencia_para ?>" size="20" maxlength="20" class="frm"><span class='add-on' rel="lupa" src="imagens/lupa.png" onclick='javascript:fnc_pesquisa_depara(document.frm_depara.referencia_para.value,"referencia", "para")' style='cursor:pointer'><i class='icon-search'></i></span>
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for=''><?=traduz('Descrição PARA')?></label>
				<div class='controls controls-row input-append'>
					<div class='span8'>
						<h5 class='asteristico'>*</h5>
						<input type="text" id="descricao_para" name="descricao_para" value="<? echo $descricao_para ?>" size="50" maxlength="50" class="frm"><span class='add-on' rel="lupa" src="imagens/lupa.png" onclick='javascript:fnc_pesquisa_depara(document.frm_depara.descricao_para.value, "descricao", "para")' style='cursor:pointer'><i class='icon-search'></i></span>
					</div>
				</div>	
			</div>
		</div>	
	<div class='span2'></div>
	</div>
		<br />
		<input type='hidden' name='btnacao' value=''>
		<input class="btn" type="button" value='<?=traduz("Pesquisar")?>' ONCLICK="javascript: if (document.frm_depara.btnacao.value == '' ) { document.frm_depara.btnacao.value='pesquisar' ; document.frm_depara.submit() } else { alert ('<?=traduz("Aguarde submissão")?>') }"  alt='<?=traduz("Clique AQUI para pesquisar")?>'>	
		<br /><br />
<center>
<input class='btn btn-primary' type="button" onclick="javascript: window.location='<? echo $PHP_SELF; ?>?btnacao=listar';" value='<?=traduz("Listar todos os DE-PARA")?>'>
</center>
<br /><br />
</form>
<br />
<?
if (strlen($btnacao) > 0 AND strlen($msg_erro)==0){

		/*HD 15873 18/3/2008*/
	$sql = "SELECT  tbl_depara.depara,
					tbl_depara.de    ,
					tbl_depara.para  ,
					TO_CHAR(tbl_depara.expira,'DD/MM/YYYY') AS expira,
					TO_CHAR(tbl_depara.digitacao,'DD/MM/YYYY') AS digitacao,
					(
						SELECT tbl_peca.descricao
						FROM   tbl_peca
						WHERE  tbl_peca.referencia = tbl_depara.de
						AND    tbl_peca.fabrica    = $login_fabrica
						LIMIT 1
					) AS descricao_de,
					(
						SELECT tbl_peca.referencia_fabrica
						FROM   tbl_peca
						WHERE  tbl_peca.referencia = tbl_depara.de
						AND    tbl_peca.fabrica    = $login_fabrica
						LIMIT 1
					) AS referencia_fabrica_de,
					(
						SELECT tbl_peca.referencia_fabrica
						FROM   tbl_peca
						WHERE  tbl_peca.referencia = tbl_depara.para
						AND    tbl_peca.fabrica    = $login_fabrica
						LIMIT 1
					) AS referencia_fabrica_para,
					(
						SELECT tbl_peca.descricao
						FROM   tbl_peca
						WHERE  tbl_peca.referencia = tbl_depara.para
						AND    tbl_peca.fabrica    = $login_fabrica
						LIMIT 1
					) AS descricao_para
			FROM    tbl_depara
			WHERE   tbl_depara.fabrica = $login_fabrica";

	if (strlen($referencia_de) > 0)   $sql .= " AND tbl_depara.de = '$referencia_de' ";

	if (strlen($referencia_para) > 0) $sql .= " AND tbl_depara.para = '$referencia_para' ";

	$sql .= " ORDER BY tbl_depara.de;";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {

		
		for ($y = 0 ; $y < pg_numrows($res) ; $y++){
			$depara          = trim(pg_result($res,$y,depara));
			$referencia_de   = trim(pg_result($res,$y,de));
			$referencia_fabrica_de    = trim(pg_result($res,$y,referencia_fabrica_de));
			$descricao_de    = trim(pg_result($res,$y,descricao_de));
			$referencia_para = trim(pg_result($res,$y,para));
			$referencia_fabrica_para  = trim(pg_result($res,$y,referencia_fabrica_para));
			$descricao_para  = trim(pg_result($res,$y,descricao_para));
			$expira          = trim(pg_result($res,$y,expira));
			$digitacao       = trim(pg_result($res,$y,digitacao));

			$cor = ($y % 2 == 0) ? "#F7F5F0": "#F1F4FA";

			$colsp  = ($login_fabrica == 171) ? 3 : 2;
			$colsp2 = ($login_fabrica == 171) ? 5 : 4;

			if ($y % 20 == 0 ) {
				if ($y <> 0 ) echo "</table>\n<br>\n";
				echo "<table class='table table-striped table-bordered table-large'>\n";
				echo "<tr class='titulo_coluna' >\n";
				echo "<th width='50%' align='center' colspan='{$colsp}'><font style='font-size:14px;'>De</font></th>\n";
				echo "<th align='center' colspan='{$colsp2}'><font style='font-size:14px;'>".traduz("Para")."</font></th>\n";
				echo "</tr>\n";
				echo "<tr class='titulo_coluna'>\n";
				if ($login_fabrica == 171) {
				echo "<th align='center'>".traduz("Referência Fábrica")."</th>\n";
				}
				echo "<th align='center'>".traduz("Referência")."</th>\n";
				echo "<th align='center'>".traduz("Descrição")."</th>\n";
				if ($login_fabrica == 171) {
				echo "<th align='center'>".traduz("Referência Fábrica")."</th>\n";
				}
				echo "<th align='center'>".traduz("Referência")."</th>\n";
				echo "<th align='center'>".traduz("Descrição")."</th>\n";
				echo "<th align='center'>".traduz("Expira")."</th>\n";
				echo "<th align='center'>".traduz("Inclusão")."</th>\n";
				echo "</tr>\n";
			}

			echo "<tr bgcolor='$cor'>\n";
			if ($login_fabrica == 171) {
			echo "<td align='left' nowrap>$referencia_fabrica_de</td>\n";
			}
			echo "<td align='left' nowrap>$referencia_de</td>\n";
			echo "<td align='left' nowrap>$descricao_de</td>\n";
			if ($login_fabrica == 171) {
			echo "<td align='left' nowrap>$referencia_fabrica_para</td>\n";
			}
			echo "<td align='left' nowrap>$referencia_para</td>\n";
			echo "<td align='left' nowrap>$descricao_para</td>\n";
			echo "<td align='left' nowrap>$expira</td>\n";
			echo "<td align='left' nowrap>$digitacao</td>\n";
			echo "</tr>\n";

		}
		echo "</table>\n";
	}
}
?>

<?
include "rodape.php";
?>
