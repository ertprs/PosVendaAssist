<?php
/* Relatorio usado tambem para tectoy, no arquivo relatorio_os_aberta_tectoy - HD 681409 */
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

include "funcoes.php";

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];

	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$cnpj			= trim(pg_result($res,$i,cnpj));
				$nome			= trim(pg_result($res,$i,nome));
				$codigo_posto	= trim(pg_result($res,$i,codigo_posto));
				echo "$codigo_posto|$nome|$cnpj";
				echo "\n";
			}
		}
	}
	exit;
}
$msg_erro = "";
$codigo_posto    = "";
$qtde_dias       = "";
$estado          = "";

if (strlen($_POST["btn_acao"]) > 0 ) $btn_acao = strtoupper($_POST["btn_acao"]);
if (strlen($_GET["btn_acao"]) > 0 )  $btn_acao = strtoupper($_GET["btn_acao"]);

if (strlen($_POST["mostra_os"]) > 0 ) $mostra_os = strtoupper($_POST["mostra_os"]);
if (strlen($_GET["mostra_os"]) > 0 )  $mostra_os = strtoupper($_GET["mostra_os"]);

if (strlen($_POST["posto"]) > 0 ) $posto = $_POST["posto"];
if (strlen($_GET["posto"]) > 0 )  $posto = $_GET["posto"];

if (strlen($_POST["estado"]) > 0 ) $estado = $_POST["estado"];
if (strlen($_GET["estado"]) > 0 )  $estado = $_GET["estado"];

if (strlen(trim($_POST["qtde_dias"])) > 0) $qtde_dias = abs($_POST["qtde_dias"]);
if (strlen(trim($_GET["qtde_dias"])) > 0)  $qtde_dias = abs($_GET["qtde_dias"]);

if (strlen(trim($_POST["qtde_dias"])) > 0) $qtde_dias = abs($_POST["qtde_dias"]);
if (strlen(trim($_GET["qtde_dias"])) > 0)  $qtde_dias = abs($_GET["qtde_dias"]);

if (strlen(trim($_POST["estado"])) > 0) $estado = trim($_POST["estado"]);
if (strlen(trim($_GET["estado"])) > 0)  $estado = trim($_GET["estado"]);

if (strlen(trim($_POST["codigo_posto"])) > 0) $codigo_posto = trim($_POST["codigo_posto"]);
if (strlen(trim($_GET["codigo_posto"])) > 0)  $codigo_posto = trim($_GET["codigo_posto"]);

if (strlen($_POST["situacao"]) > 0 ) $situacao = strtoupper($_POST["situacao"]);
if (strlen($_GET["situacao"]) > 0 )  $situacao = strtoupper($_GET["situacao"]);

if (strlen($_POST["os_consertada"]) > 0 ){
	$os_consertada = strtoupper($_POST["os_consertada"]);
}

if (strlen($_GET["os_consertada"]) > 0 ){
	$os_consertada = strtoupper($_GET["os_consertada"]);
}

if (strlen($_REQUEST["ressarcimento"]) > 0 ){
	$ressarcimento = $_REQUEST["ressarcimento"];
}

if (strlen($btn_acao) > 0) {

	if (strlen($msg_erro) == 0) {

		if (strlen($codigo_posto)>0) {
			$sqlp = "SELECT posto
					 FROM tbl_posto_fabrica
					 WHERE fabrica = $login_fabrica
					 AND codigo_posto = '$codigo_posto'";
			$resp = pg_exec($con, $sqlp);

			if (pg_numrows($resp) > 0) {
				$posto = pg_result($resp, 0, 0);
			} else {
				$msg_erro = "Posto informado não encontrado.";
			}
		}

		if (strlen($codigo_posto)==0 && strlen($estado)==0 && !in_array($login_fabrica, array(11,172)) ) {
			$msg_erro = "Para pesquisar deve ser informado posto ou estado.";
		}

		if ((!is_numeric($qtde_dias) or strlen($qtde_dias)==0) ) {
			$msg_erro = "Digite a quantidade de dias.";
		}

		// HD 61255
		/*if ($qtde_dias<10) {
			$msg_erro = "No mínimo 10 dias.";
		}*/
	}
}

$layout_menu = "auditoria";
$title = "RELAÇÃO DE ORDENS DE SERVIÇO EM ABERTO";

include "cabecalho.php";
?>

<style type="text/css">
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.titulo_coluna{
	background-color:#596d9b;
	font: bold 10px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.Titulo2 {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}
.button{
	border:1 ;
	border-style:outset;
	background:#D9E2EF;
	font:normal 11px tahoma,verdana,helvetica;
	padding-left:3px;
	padding-right:3px;
	cursor:pointer;
	margin:0;
	overflow:visible;
	width:auto;-moz-outline:0 none;
	outline:0 none;
}

label {
	cursor:pointer;
}
.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}
table.tabela tr td{
    font-family: Verdana, Tahoma, arial;
    font-size: 9px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

</style>

<script type='text/javascript' src='js/jquery-1.3.0.js'></script>
<script type='text/javascript' src='js/jquery.alphanumeric.js'></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script language="JavaScript">
$().ready(function() {
	$("input[name=qtde_dias]").numeric();
	function formatItem(row) {
		return row[0] + " - " + row[1];
	}

	function formatResult(row) {
		return row[0];
	}

	/* Busca pelo Código */
	$("#codigo_posto").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#codigo_posto").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#codigo_posto").val(data[0]) ;
		//alert(data[2]);
	});

});
</script>

<script>
function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
	else
		alert('Preencha toda ou parte da informação para efetuar a pesquisa');
}
jQuery.fn.extend({
	uncheck: function() {
	return this.each(function() { this.checked = false; });
	}
});

function tiraClick(){
	$("input[type=radio]").uncheck();
}

</script>
<script type="text/javascript" src="js/jquery.tablesorter.js"></script>
<script>
	$(function() {
		$.tablesorter.addWidget({
			id: "repeatHeaders",
			format: function(table) {
				if(!this.headers) {
					var h = this.headers = [];
					$("thead th",table).each(function() {
						h.push(
							"<th>" + $(this).text() + "</th>"
						);

					});
				}
				$("tr.repated-header",table).remove();
			}
		});
		$("table").tablesorter({
			widgets: ['zebra','repeatHeaders']
		});
	});
</script>
<?

?>
<? include "javascript_pesquisas.php"; ?>

<? if (strlen($msg_erro) > 0) { ?>
<table width="700" border="0" cellspacing="0" cellpadding="0" align="center" class="msg_erro">
	<tr>
		<td><?echo $msg_erro?></td>
	</tr>
</table>
<? } ?>
<? if(strlen($mostra_os)==0){ ?>
<form name="frm_relatorio" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="btn_acao">
<table width='700' class='formulario' border='0' cellpadding='5' cellspacing='0' align='center'>
	<tr>
		<td class='titulo_tabela' colspan='3'>Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td align='right' height='20'>Código Posto&nbsp;</td>
		<td align='left'>
			<input class="frm" type="text" name="codigo_posto" size="10" id='codigo_posto' value="<? echo $codigo_posto ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm.codigo_posto,document.frm.posto_nome,'codigo')" <? } ?>>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'codigo')"></A>
		</td>
	</tr>
	<tr>
		<td align='right'>Razão Social&nbsp;</td>
		<td align='left'><input class="frm" type="text" name="posto_nome" id='posto_nome' size="30" value="<? echo $posto_nome ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm.codigo_posto,document.frm.posto_nome,'nome')" <? } ?>>&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'nome')" style="cursor:pointer;"></A>
		</td>
	</tr>
	<tr>
		<td align='right'>Estado&nbsp;</td>
		<td align='left'>
			<select name="estado" class='frm'>
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
		</td>
	</tr>
	<td align='right'>Linha&nbsp;</td>
		<td align='left'>
			<select name="linha" class='frm'>
				<option value=""   <? if (strlen($linha) == 0)    echo " selected "; ?>>TODAS AS LINHAS</option>
				<?php

					$sql =	"SELECT linha,
									nome
							FROM tbl_linha
							WHERE fabrica = $login_fabrica
							ORDER BY nome;";
					$res = pg_query($con,$sql);
				if (pg_numrows($res) > 0) { 
	                for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
	                    $aux_linha = trim(pg_result($res,$i,linha));
	                    $aux_nome  = trim(pg_result($res,$i,nome));
                        ?>
                        <option value='<?php echo $aux_linha ?>'
                            <?php if ($linha == $aux_linha){
                            	echo "selected"; 
                            }?>
                        > <?php echo $aux_nome; ?></option>
                    	<?php } 
                 	}
				?>
			</select>
		</td>
	</tr>


	<tr>
		<td align='right' height='20'>OS em aberto a mais de&nbsp;</td>
		<td align='left'><input type="text" class="frm" name="qtde_dias" size="3" maxlength="3" <? if (strlen($qtde_dias)> 0) echo "value=\"$qtde_dias\""; else echo "VALUE=\"\"";?>> dias</td>
	</tr>
	<tr>
		<td align="right">&nbsp;</td>
		<td bgcolor="#D9E2EF">
			<fieldset style="width:250px;">
				<legend>Situação</legend>
				<table>
					<tr>
						<td>
							<input type='radio' name='situacao' id="situacao_c" value='c' <?if ($situacao=="C") echo "checked";?> /><label for="situacao_c">Com peça</label>
						</td>
						<td colspan="2">
							<input type='radio' name='situacao' id="situacao_s" value='s' <?if ($situacao=="S") echo "checked";?> /><label for="situacao_s">Sem peça</label>
						</td>
					</tr>
					<tr>
						<td>
							<input type="radio" name="os_consertada" id="os_consertada_com" value="s" <?php if ($os_consertada == "S") echo " checked"; ?>/>
							<label for="os_consertada_com">Consertado</label>
						</td>
						<td colspan="2">
							<input type="radio" name="os_consertada" id="os_consertada_sem" value="n" <?php if ($os_consertada == "N") echo " checked"; ?> />
							<label for="os_consertada_sem">Sem Conserto</label>
						</td>
					</tr>
					<tr>
						<td>
							<input type="radio" name="ressarcimento" id="ressarcimento_conserto" value="m" <?=($ressarcimento == "m") ? 'checked="checked"' : ''?> /><label for="ressarcimento_conserto">Conserto</label>
						</td>
						<td>
							<input type="radio" name="ressarcimento" id="ressarcimento_troca" value="f" <?=($ressarcimento == "f") ? 'checked="checked"' : ''?> /><label for="ressarcimento_troca">Troca</label>
						</td>
						<td>
							<input type="radio" name="ressarcimento" id="ressarcimento_reembolso" value="t" <?=($ressarcimento == "t") ? 'checked="checked"' : ''?> /><label for="ressarcimento_reembolso">Reembolso</label>
						</td>
					</tr>
				</table>
			</fieldset>
		</td>
	</tr>
	<?php if ($login_fabrica == 6) : ?>
		<tr>
			<td align="right">&nbsp;</td>
			<td>
				<fieldset style="width:250px; text-align:left;">
					<legend>Tipo de OS</legend>
					<input type="radio" name="tipo_de_os" id="revenda" value="R" <?php echo ($_POST['tipo_de_os'] == 'R')? 'checked' : ''; ?> /> &nbsp; <label for="revenda">Revenda</label> &nbsp;
					<input type="radio" name="tipo_de_os" id="consumidor" value="C" <?php echo ($_POST['tipo_de_os'] == 'C')? 'checked' : ''; ?> /> &nbsp; <label for="consumidor">Consumidor</label> &nbsp;
					<input type="radio" name="tipo_de_os" id="todas" value="" <?php echo ( empty( $_POST['tipo_de_os'] ) )? 'checked' : ''; ?> /> &nbsp; <label for="todas">Todas</label>
				</fieldset>
			</td>
		</tr>
	<?php endif; ?>
	<tr bgcolor="#D9E2EF">
		<td colspan="2" align="center" >
			<input type='button' name='tira_click' id='tira_click' onClick="javascript: tiraClick();" value='Desmarcar'>
			<button onclick="document.frm_relatorio.btn_acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor: pointer;" alt="Preencha as opções e clique aqui para pesquisar">Pesquisar</button>
		</td>
	</tr>
</table>
</form>
<?}?>
<br>
<?

if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {

	 $cond_posto = " 1=1 ";
	 $cond_aux_posto = " 1=1 ";
	if (strlen($posto)>0) {
		$cond_posto = " tbl_os.posto = $posto ";
		$cond_aux_posto = "temp_os_aberta.posto = $posto ";
	}
	if( isset($_POST['tipo_de_os']) && !empty($_POST['tipo_de_os']) && strlen($_POST['tipo_de_os']) == 1 )
		$cond_tipo_os = " AND consumidor_revenda = '" . $_POST['tipo_de_os'] . "'";

	if (strlen($estado)>0){
		$tmp_estado = "select
				tbl_posto.posto
				INTO TEMP temp_postoestado
				from tbl_posto
				join tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
					AND tbl_posto_fabrica.fabrica = $login_fabrica
				where tbl_posto_fabrica.contato_estado = '$estado';

				create index temp_postoestado_estado ON temp_postoestado(posto);";
		$cond_estado = "JOIN temp_postoestado ON tbl_os.posto = temp_postoestado.posto ";
		$cond_aux_estado = "JOIN temp_postoestado ON temp_os_aberta.posto = temp_postoestado.posto ";
	}

	$cond_sem_peca = " AND (temp_os_aberta.os_produto IS NULL OR temp_os_aberta.os_produto IS NOT NULL)";

	if($situacao=='S') {
		$cond_sem_peca = " AND temp_os_aberta.os_produto IS NULL";
	}
	if($situacao=='C') {
		$cond_sem_peca = " AND temp_os_aberta.os_produto IS NOT NULL";
	}

	if ($os_consertada == "S"){
		$cond_os_consertada = "AND temp_os_aberta.data_conserto IS NOT NULL";
	}

	if ($os_consertada == "N"){
		$cond_os_consertada = "AND temp_os_aberta.data_conserto IS NULL";
	}

	if ($ressarcimento == "m") {//OS SEM TROCA E RESSARCIMENTO - APENAS MO
		$cond_ressarcimento  = " AND tbl_os.ressarcimento IS NULL";
		$cond_ressarcimento .= " AND tbl_os.troca_garantia IS NULL";
	}

	if ($ressarcimento == "f") {//TROCA
		$cond_ressarcimento  = " AND tbl_os.ressarcimento IS FALSE";
		$cond_ressarcimento .= " AND tbl_os.troca_garantia IS TRUE";
	}

	if ($ressarcimento == "t") {//RESSARCIMENTO
		$cond_ressarcimento = " AND tbl_os.ressarcimento IS TRUE";
	}
	if (strlen($linha)>0){
		$tipo_linha = "AND tbl_produto.linha = $linha";
		$cond_linha = "JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = tbl_os.fabrica";
	}else{
		$tipo_linha = "";
		$cond_linha = "";
	}

	$sql_temp = "   $tmp_estado
			SELECT tbl_os.os                  ,
			tbl_posto_fabrica.codigo_posto    ,
			tbl_posto_fabrica.contato_estado  ,
			tbl_posto.nome                    ,
			tbl_posto.posto                   ,
			tbl_os.data_conserto              ,
			tbl_os_produto.os_produto 		  
			INTO TEMP TABLE temp_os_aberta
			FROM tbl_os
			JOIN tbl_posto         ON tbl_os.posto            = tbl_posto.posto
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			$cond_linha
			LEFT JOIN tbl_os_produto USING(os)
			$cond_estado
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.data_fechamento IS NULL
			AND   tbl_os.excluida   IS NOT TRUE
			AND   tbl_posto_fabrica.credenciamento <>'DESCREDENCIADO'
			AND   $cond_posto
			$cond_ressarcimento
			$cond_tipo_os
			$tipo_linha
			AND   data_abertura < current_date - INTERVAL '$qtde_dias days';

			CREATE INDEX temp_os_aberta_os ON temp_os_aberta(os);
			";

	$sql = "$sql_temp

			SELECT
			count(DISTINCT temp_os_aberta.os) AS qtde,
			temp_os_aberta.codigo_posto  ,
			temp_os_aberta.contato_estado,
			temp_os_aberta.nome          ,
			temp_os_aberta.posto 		 
		INTO TEMP TABLE temp_os_aberta_resultado
		FROM temp_os_aberta
		$cond_aux_estado
		WHERE temp_os_aberta.posto <> 6359
		AND   $cond_aux_posto
		$cond_sem_peca
		$cond_os_consertada
		GROUP BY temp_os_aberta.codigo_posto, temp_os_aberta.contato_estado  ,
				temp_os_aberta.nome, temp_os_aberta.posto;

		CREATE INDEX temp_os_aberta_resultado_data_abertura ON temp_os_aberta_resultado(posto);
		SELECT * FROM temp_os_aberta_resultado
		ORDER BY qtde DESC ;";

	// echo nl2br($sql);
	// exit;
	$res = pg_exec($con,$sql);
	$numero_registros = pg_numrows($res);

	if ($numero_registros > 0) {
		echo "<table width='700' border='0' cellpadding='2' cellspacing='1' align='center' name='relatorio' id='relatorio' class='tabela'>";
			echo "<thead>";
				echo "<tr class='titulo_coluna'>";
					echo "<td colspan='4'>RELAÇÃO DE OS POR POSTO</td>";
				echo "</tr>";
				echo "<tr class='Titulo2'>";
					echo "<th>CÓDIGO DO POSTO</th>";
					echo "<th>NOME DO POSTO</th>";
					echo "<th>UF</th>";
					echo "<th>TOTAL</th>";
				echo "</tr>";
			echo "</thead>";
			echo "<tbody>";
			for ($i = 0; $i < pg_numrows($res); $i++) {
				$posto          = pg_result($res,$i,posto);
				$codigo_posto   = pg_result($res,$i,codigo_posto);
				$posto_nome     = pg_result($res,$i,nome);
				$qtde_os        = pg_result($res,$i,qtde);
				$contato_estado = pg_result($res,$i,contato_estado);

				$qtde_os_total = $qtde_os + $qtde_os_total;

				$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

				echo "<tr bgcolor='$cor'>";
					if($login_fabrica == 6) {
						$tipo_os = $_POST['tipo_de_os'];
						echo "<td align='center' nowrap><a href='relatorio_os_aberta_tectoy.php?posto=$posto&mostra_os=SIM&qtde_dias=$qtde_dias&situacao=$situacao&os_consertada=$os_consertada&ressarcimento=$ressarcimento&tipo_de_os=$tipo_os' target='_blank'><b>$codigo_posto</b></a></td>";
					}
					else
						echo "<td align='center' nowrap><a href='relatorio_os_aberta_lenoxx.php?posto=$posto&mostra_os=SIM&qtde_dias=$qtde_dias&situacao=$situacao&os_consertada=$os_consertada&ressarcimento=$ressarcimento&linha=$linha' target='_blank'><b>$codigo_posto</b></a></td>";
					echo "<td nowrap align='center'>$posto_nome </td>";
					echo "<td nowrap align='center'>$contato_estado</td>";
					echo "<td nowrap align='right'>$qtde_os</td>";
				echo "</tr>";
			}
			echo "</tbody>";
			echo "<tfoot>";
				echo "<tr class='titulo_coluna'>";
					echo "<td colspan='3' align='right'><B>Total</B></td>";
					echo "<td align='center'><B>$qtde_os_total</B></td>";
				echo "</tr>";
			echo "</tfoot>";
		echo "</table>";

		flush();
		$data = date ("d/m/Y H:i:s");

		$arquivo_nome     = "relatorio-os-aberta-$login_fabrica.xls";
		$path             = "/www/assist/www/admin/xls/";
		$path_tmp         = "/tmp/";

		$arquivo_completo     = $path.$arquivo_nome;
		$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

		echo `rm $arquivo_completo_tmp `;
		echo `rm $arquivo_completo `;

		$fp = fopen ($arquivo_completo_tmp,"w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>Relatório Ordens de Serviço em aberto - $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");
		fputs($fp, "<table width='550' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center'>");
			fputs($fp, "<tr class='Titulo'>");
				fputs($fp, "<td colspan='4'>RELAÇÃO DE OS POR POSTO</td>");
			fputs($fp, "</tr>");
			fputs($fp, "<tr class='Titulo2'>");
				fputs($fp, "<td>CÓDIGO DO POSTO</td>");
				fputs($fp, "<td>NOME DO POSTO</td>");
				fputs($fp, "<td>UF</td>");
				fputs($fp, "<td>TOTAL</td>");
			fputs($fp, "</tr>");
			for ($i = 0; $i < pg_numrows($res); $i++) {
				$posto          = pg_result($res,$i,posto);
				$codigo_posto   = pg_result($res,$i,codigo_posto);
				$posto_nome     = pg_result($res,$i,nome);
				$qtde_os        = pg_result($res,$i,qtde);
				$contato_estado = pg_result($res,$i,contato_estado);

				$qtde_os_total = "";
				$qtde_os_total = $qtde_os + $qtde_os_total;

				$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

				fputs($fp, "<tr class='Conteudo' bgcolor='$cor'>");
					fputs($fp, "<td align='center' nowrap><a href='relatorio_os_aberta_lenoxx.php?posto=$posto&mostra_os=SIM&qtde_dias=$qtde_dias&situacao=$situacao&os_consertada=$os_consertada&ressarcimento=$ressarcimento' target='_blank'>$codigo_posto</a></td>");
					fputs($fp, "<td nowrap>$posto_nome </td>");
					fputs($fp, "<td nowrap>$contato_estado</td>");
					fputs($fp, "<td nowrap>$qtde_os</td>");
				fputs($fp, "</tr>");
			}
			fputs($fp, "<tr class='Conteudo'>");
				fputs($fp, "<td colspan='3' align='right'><B>Total</B></td>");
				fputs($fp, "<td align='center'><B>$qtde_os_total</B></td>");
			fputs($fp, "</tr>");
		fputs($fp, "</table>");
		fputs($fp, "</body>");
		fputs($fp, "</html>");

		echo ` cp $arquivo_completo_tmp $path `;
		$data = date("Y-m-d").".".date("H-i-s");

		echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;
		echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
			echo"<tr>";
				echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/$arquivo_nome'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
			echo "</tr>";
		echo "</table>";
	} else {
		echo "<table border='0' cellpadding='2' cellspacing='0' align='center'>";
			echo "<tr height='50'>";
				echo "<td valign='middle' align='center'>
						<font size=\"2\"><b>Não foram encontrados registros com os parâmetros informados/digitados!!!</b></font>";
				echo "</td>";
			echo "</tr>";
		echo "</table>";
	}
}

/**
 * DISCRIMINAÇÃO DAQUELE POSTO
 * QUANDO CLICA NO LINK DO POSTO
 */
if (strlen($posto) > 0 && strlen($msg_erro) == 0 && $mostra_os == "SIM") {
	$linha = $_REQUEST['linha'];

	$cond_sem_peca = " AND (temp_os_aberta.os_produto IS NULL OR temp_os_aberta.os_produto IS NOT NULL)";

	if($situacao=='S'){
		 $cond_sem_peca = " AND temp_os_aberta.os_produto IS NULL";
	}
	if($situacao=='C'){
		 $cond_sem_peca = " AND temp_os_aberta.os_produto IS NOT NULL";
	}
	if ($os_consertada == "S") {
		$cond_os_consertada = "AND tbl_os.data_conserto IS NOT NULL";
	}

	if ($os_consertada == "N") {
		$cond_os_consertada = "AND tbl_os.data_conserto IS NULL";
	}

	if (strlen($posto) > 0) $cond_posto = " tbl_os.posto = $posto ";
	else                    $cond_posto = " 1=1 ";

	if ($ressarcimento == "m") {//OS SEM TROCA E RESSARCIMENTO - APENAS MO
		$cond_ressarcimento  = " AND tbl_os.ressarcimento IS NULL";
		$cond_ressarcimento .= " AND tbl_os.troca_garantia IS NULL";
	}

	if ($ressarcimento == "f") {//TROCA
		$cond_ressarcimento  = " AND tbl_os.ressarcimento IS FALSE";
		$cond_ressarcimento .= " AND tbl_os.troca_garantia IS TRUE";
	}

	if ($ressarcimento == "t") {//RESSARCIMENTO
		$cond_ressarcimento = " AND tbl_os.ressarcimento IS TRUE";
	}
	if (strlen($linha)>0){
		$tipo_linha = "AND tbl_produto.linha = $linha";
		$cond_linha = "JOIN tbl_produto USING(produto) ";
	}else{
		$tipo_linha = "";
		$cond_linha = "";
	}

	if( isset($_GET['tipo_de_os']) && !empty($_GET['tipo_de_os']) && strlen($_GET['tipo_de_os']) == 1 )
		$cond_tipo_os = " AND consumidor_revenda = '" . $_GET['tipo_de_os'] . "'";


	$sql_temp = "SELECT tbl_os.os,
                        tbl_os.sua_os                                              ,
                        to_char(tbl_os.data_abertura,'DD/MM/YYYY') as data_abertura,
			tbl_os.posto,
			tbl_os.produto,
			tbl_os.fabrica,
			tbl_os_produto.os_produto,
			tbl_os.data_conserto
			INTO TEMP TABLE temp_os_aberta
			FROM tbl_os
			$cond_linha
			LEFT JOIN  tbl_os_produto USING(os)
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.data_fechamento IS NULL
			AND   tbl_os.excluida   IS NOT TRUE
			AND   tbl_os.posto <> 6359
			AND   $cond_posto
			$tipo_linha
			$cond_ressarcimento
			$cond_tipo_os
			$cond_os_consertada
			AND   data_abertura < current_date - INTERVAL '$qtde_dias days';
			CREATE INDEX temp_os_aberta_os ON temp_os_aberta(os);
			";

	$sql = "$sql_temp
			SELECT DISTINCT
			temp_os_aberta.os,
			temp_os_aberta.sua_os                                              ,
			temp_os_aberta.data_abertura,
			tbl_posto.posto                                            ,
			tbl_posto.nome,
			tbl_posto_fabrica.codigo_posto
		INTO TEMP TABLE temp_os_aberta_resultado
		FROM temp_os_aberta
		JOIN tbl_produto       ON tbl_produto.produto = temp_os_aberta.produto AND tbl_produto.fabrica_i=$login_fabrica
     	        LEFT JOIN tbl_marca    ON tbl_marca.marca         = tbl_produto.marca
		JOIN tbl_posto         ON temp_os_aberta.posto    = tbl_posto.posto
		JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = temp_os_aberta.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE temp_os_aberta.fabrica = $login_fabrica
		$cond_sem_peca
		;

		CREATE INDEX temp_os_aberta_resultado_data_abertura ON temp_os_aberta_resultado(os);
		SELECT * FROM temp_os_aberta_resultado
		ORDER BY nome;
			";
	// echo nl2br($sql);
	// exit;
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		$codigo_posto = pg_result($res, 0, codigo_posto);
		$posto_nome   = pg_result($res, 0, nome);

		echo "<table width='400' border='0' cellpadding='2' cellspacing='1' align='center' class='tabela'>";
			echo "<tr class='titulo_coluna'>";
				echo "<td colspan='2'>$codigo_posto - $posto_nome</td>";
			echo "</tr>";
			echo "<tr class='Titulo2'>";
				echo "<td>OS</td>";
				echo "<td>DATA ABERTURA</td>";
			echo "</tr>";
			for ($i = 0; $i < pg_numrows($res); $i++) {
				$os            = pg_result($res,$i,os);
				$sua_os        = pg_result($res,$i,sua_os);
				if(empty($sua_os)) $sua_os = $os;
				$data_abertura = pg_result($res,$i,data_abertura);

				$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

				echo "<tr class='Conteudo' bgcolor='$cor'>";
				echo "<td align='center'><a href='os_press.php?os=$os' target='_blank'><b>$sua_os</b></a></td>";
				echo "<td align='center'>$data_abertura</td>";
				echo "</tr>";
			}
		echo "</table>";

		flush();
		$data = date ("d/m/Y H:i:s");

		$arquivo_nome     = "relatorio-os-aberta-posto-$login_fabrica.xls";
		$path             = "xls/";
		$path_tmp         = "/tmp/";

		$arquivo_completo     = $path.$arquivo_nome;
		$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

		echo `rm $arquivo_completo_tmp `;
		echo `rm $arquivo_completo `;

		$fp = fopen ($arquivo_completo_tmp,"w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
			fputs ($fp,"<title>Relatório Ordens de Serviço em aberto - $data</title>");
			fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");
		fputs($fp, "<table width='350' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center'>");
			fputs($fp, "<tr class='Titulo'>");
				fputs($fp, "<td colspan='2'>$codigo_posto - $posto_nome</td>");
			fputs($fp, "</tr>");
			fputs($fp, "<tr class='Titulo2'>");
				fputs($fp, "<td>OS</td>");
				fputs($fp, "<td>DATA ABERTURA</td>");
			fputs($fp, "</tr>");
			for ($i = 0; $i < pg_numrows($res); $i++) {
				$os            = pg_result($res,$i,os);
				$sua_os        = pg_result($res,$i,sua_os);
				$data_abertura = pg_result($res,$i,data_abertura);

				$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

				fputs($fp, "<tr class='Conteudo' bgcolor='$cor'>");
				fputs($fp, "<td align='center'><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>");
				fputs($fp, "<td align='center'>$data_abertura</td>");
				fputs($fp, "</tr>");
			}
		fputs($fp, "</table>");
		fputs($fp, "</body>");
		fputs($fp, "</html>");

		echo ` cp $arquivo_completo_tmp $arquivo_completo `;
		$data = date("Y-m-d").".".date("H-i-s");

		//echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;
		echo "<br>";
		echo "<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
			echo "<tr>";
				echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/$arquivo_nome'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
			echo "</tr>";
		echo "</table>";

	}
	else
		echo 'Nenhuma OS Encontrada';
}

include "rodape.php";

?>
