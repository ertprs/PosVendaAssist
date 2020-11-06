<?php
# Relatório de atendimentos categorias A.T. ou Procon/Jec
# Telecontrol Networking
# 23/12/2008

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="callcenter";
include 'autentica_admin.php';

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

$layout_menu = "callcenter";
$title = "Relatório Callcenter Classificação dos Postos";

include "cabecalho.php";
?>

<style>
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
	color:#ffffff;
/*	background-color: #445AA8;*/
}
.Erro{
	BORDER-RIGHT: #990000 1px solid;
	BORDER-TOP: #990000 1px solid;
	FONT: 10pt Arial ;
	COLOR: #ffffff;
	BORDER-LEFT: #990000 1px solid;
	BORDER-BOTTOM: #990000 1px solid;
	BACKGROUND-COLOR: #FF0000;
}
.expandTD {
	border:0px solid white;
	margin:0 5px 5px 0;
}
.expandTD A:link {text-decoration:none;font-weight:bold;}

.expandTD span{/*CSS para o popup*/
	background-color: #E6EEF7;
	border-color: white;
    border: 3px white solid;
    border-radius: 10px; /* Só CSS3, se tiver, boa, se não, nem vai perceber...  */
		-o-border-radius: 10px;
		-icab-border-radius: 10px;
		-khtml-border-radius: 10px;
		-moz-border-radius: 10px;
		-webkit-border-radius: 10px;
	font-size: 12pt;
	left:-2000px;
	padding:5px;
	position:absolute;
	text-decoration:none;
	visibility:hidden;
	width:350px;/* tamanho do popup */
	height:200px;
	z-index:0;
}
.enlace span P {
	padding-top: 1.5em;
    text-align: justify;
}
.expandTD:hover span {/*CSS poara o popup*/
	left:37%;/* posição do popup */
	top:10px;
	visibility:visible;
	z-index:2;
}
</style>

<?php
if($_POST['consulta'] == "Consultar") {

	$data_inicial = trim($_POST["data_inicial"]);
	$data_final   = trim($_POST["data_final"]);
	$codigo_posto = $_POST["codigo_posto"];
	$tipo_aba     = $_POST["tipo_aba"];

	if (strlen($data_inicial) == 0){
		$msg_erro = "Favor informar a data inicial para pesquisa<br/>";
	}else{
		$fnc           = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
		$data_in       = pg_result($fnc,0,0);
		$xdata_inicial = "$data_in 00:00:00";
	}

	if (strlen($data_final) == 0){
		$msg_erro .= "Favor informar a data final para pesquisa<br>";
	}else{
		$fnc         = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
		$data_fl     = pg_result($fnc,0,0);
		$xdata_final = "$data_fl 23:59:59";
	}

	if(strlen($codigo_posto) > 0){
		$sql_posto = "SELECT tbl_posto.posto FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica WHERE tbl_posto_fabrica.codigo_posto = '$codigo_posto'";
		$res_posto = pg_exec($con,$sql_posto);
		if (pg_numrows($res_posto)>0){
			$posto_busca = pg_result($res_posto,0,0);
			$cond_posto = "AND tbl_hd_chamado_extra.posto = $posto_busca";
		}else{
			$msg_erro .= "Verifique o posto digitado!<br/>";
		}
	}

	if(strlen($tipo_aba) == 0){
		$msg_erro .= "Selecione uma das duas opções: A.T. ou Procon/Jec";
	}
	
	if (strlen($msg_erro) > 0) {
		$ok = 1;
		$data_inicial = trim($_POST["data_inicial"]);
		$data_final   = trim($_POST["data_final"]);

		echo "<div class='Erro'>$msg_erro</div>";
	}else{
		$ok = 0;
	}
}

include "javascript_pesquisas.php";
include "javascript_calendario.php"; ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="../js/bibliotecaAJAX.js"></script>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>


<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">
$().ready(function() {

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
	});

});
</script>


<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
<table width='600' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>
	<tr>
		<td class='Titulo' background='imagens_admin/azul.gif'>RELATÓRIO CALLCENTER CLASSIFICAÇÃO POSTOS</td>
	</tr>

	<tr>
		<td bgcolor='#DBE5F5' valign='bottom'>

			<table width='100%' border='0' cellspacing='1' cellpadding='2' >

				<tr class="Conteudo" bgcolor="#D9E2EF">
					<td width="10">&nbsp;</td>
					<td align='right'><font size='2'>Data Inicial</td>
					<td align='left'>
						<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" >
					</td>
					<td align='right'><font size='2'>Data Final</td>
					<td align='left'>
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_final) > 0) echo $data_final;  ?>" >
					</td>
					<td width="10">&nbsp;</td>
				</tr>
				<tr>
					<td width="10">&nbsp;</td>
					<td align='right' nowrap><font size='2'>Código Posto</td>
					<td align='left'>
						<input type="text" name="codigo_posto" id="codigo_posto" size="8"  value="<? echo $codigo_posto ?>" class="Caixa">
						<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'codigo')">
					</td>
					<td align='right' nowrap><font size='2'>Nome do Posto</td>
					<td align='left'>
						<input type="text" name="posto_nome" id="posto_nome" size="15"  value="<?echo $posto_nome?>" class="Caixa">
						<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'nome')">
					</td>
					<td width="10">&nbsp;</td>
				</tr>
				<tr>
					<td width="8">&nbsp;</td>
					<td align='right' nowrap><font size='2'>A.T.</td>
					<td align='left'>
						<input type="radio" name="tipo_aba" value="at" class="Caixa"
						<?php
						if ($tipo_aba == "at"){
							echo " CHECKED";
						}
						?>>
					</td>
					<td align='right' nowrap><font size='2'>Procon/Jec</td>
					<td align='left'>
						<input type="radio" name="tipo_aba" value="procon" class="Caixa"
						<?php
						if ($tipo_aba == "procon"){
							echo " CHECKED";
						}
						?>>
					</td>
					<td width="12">&nbsp;</td>
				</tr>
			</table><br>
			<input type='submit' value='Consultar' name='consulta'>
		</td>
	</tr>
</table>
</FORM>

<? 
if (isset($ok) and $ok == 0) {
	if ($tipo_aba == "at"){
		$condicao = "'reclamacao_at', 'reclamacao_at_info', 'mau_atendimento', 'posto_nao_contribui', 'demonstra_desorg',' possui_bom_atend', 'demonstra_org'";
	}else{
		$condicao = "'pr_reclamacao_at', 'pr_info_at', 'pr_mau_atend', 'pr_posto_n_contrib', 'pr_demonstra_desorg', 'pr_bom_atend', 'pr_demonstra_org'";
	}

	$sql = "SELECT tbl_hd_chamado.hd_chamado,
				tbl_hd_chamado.categoria, 
				tbl_hd_chamado_extra.posto,
				tbl_hd_chamado_extra.reclamado,
				tbl_posto.nome,
				tbl_posto_fabrica.codigo_posto
			FROM tbl_hd_chamado
			JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
			LEFT JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto 
			LEFT JOIN tbl_posto_fabrica ON tbl_hd_chamado_extra.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_hd_chamado.data between '$xdata_inicial' and '$xdata_final'
			AND tbl_hd_chamado.fabrica = $login_fabrica
			AND tbl_hd_chamado.categoria in ($condicao)
			$cond_posto
			ORDER BY tbl_posto.nome";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0){
		echo "<br/>";
		echo "<table width='500' border='0' align='center' cellpadding='1' cellspacing='1' style='border: #485989 1px solid; background-color: #485989;font-size:11px'>";
		echo "<TR >\n";
		echo "<td background='imagens_admin/azul.gif' style='font-size: 11px; font-weight: bold; color:#ffffff;'>Atendimento</TD>\n";
		echo "<td background='imagens_admin/azul.gif' style='font-size: 11px; font-weight: bold; color:#ffffff;' nowrap>Cód. Posto</TD>\n";
		echo "<td background='imagens_admin/azul.gif' style='font-size: 11px; font-weight: bold; color:#ffffff;'>Nome Posto</TD>\n";
		echo "<TD background='imagens_admin/azul.gif' style='font-size: 11px; font-weight: bold; color:#ffffff;'>Categoria</TD>\n";
		#echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Reclamação</TD>\n"; #e6eef7
		echo "</TR >\n";
		for ($i=0; $i<pg_numrows($res); $i++){
			$atendimento  = pg_result($res,$i,hd_chamado);
			$categoria    = pg_result($res,$i,categoria);
			$codigo_posto = pg_result($res,$i,codigo_posto);
			$posto_nome   = pg_result($res,$i,nome);
			$reclamado    = pg_result($res,$i,reclamado);

			if ($i % 2 == 0){
				$cor = '#F1F4FA';
			}else{
				$cor = '#E6EEF7';
			}

			switch ($categoria) {
				case "reclamacao_at":
				case "pr_reclamacao_at":
					$categoria = "RECLAMAÇÃO DA ASSIST. TÉCN.";
					break;
				case "reclamacao_at_info":
				case "pr_info_at":
					$categoria = "INFORMAÇÕES DE A.T";
					break;
				case "mau_atendimento":
				case "pr_mau_atend":
					$categoria = "MAU ATENDIMENTO";
					break;
				case "posto_nao_contribui":
				case "pr_posto_n_contrib":
					$categoria = "POSTO NÃO CONTRIBUI COM INFORMAÇÕES";
					break;
				case "demonstra_desorg":
				case "pr_demonstra_desorg":
					$categoria = "DEMONSTRA DESORGANIZAÇÃO";
					break;
				case "possui_bom_atend":
				case "pr_bom_atend":
					$categoria = "POSSUI BOM ATENDIMENTO";
					break;
				case "demonstra_org":
				case "pr_demonstra_org":
					$categoria = "DEMONSTRA ORGANIZAÇÃO";
					break;					
			}

			echo "<TR bgcolor='$cor'>\n";
			echo "<TD align='center'><a href='callcenter_interativo_new.php?callcenter=$atendimento' target='_BLANK'>$atendimento</a></TD>\n";
			echo "<TD align='center'>$codigo_posto</TD>\n";
			echo "<TD nowrap style='padding-left: 2px'>$posto_nome</TD>\n";
			echo "<TD nowrap nowrap style='padding-left: 2px'>$categoria</TD>\n";
			#$span_pos = 20 + $i;
			#$visualizacao = substr($reclamado,0,7);
			#$visualizacao = "$visualizacao ...";
			#echo "<TD><A href='#' class='expandTD'>$visualizacao<SPAN style='top:";
			#echo " $span_pos";
			#echo "em;'><p>$reclamado</p></SPAN></A></TD>\n";<TEXTAREA ROWS='6' COLS='110' class='input' style='font-size:12px' READONLY>
			echo "</TR >\n";
			echo "<TR bgcolor='$cor'>";
			#echo "<TD><strong>Reclamação/Elogio:</strong></TD>";
			echo "<TD colspan='4'>".nl2br($reclamado)."</TD>";#</TEXTAREA></TD>";
			echo "</TR>";
		}
	}else{
		echo "<strong>Nenhum resultado encontrado!</strong>";
	}
	echo "</table>";
}

include "rodape.php" 
?>