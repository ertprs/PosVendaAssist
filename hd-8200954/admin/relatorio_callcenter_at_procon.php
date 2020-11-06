<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="call_center";
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



if($_GET['ajax']=='sim') {

	/*if ($_GET["data_inicial"] == 'dd/mm/aaaa'){
		$erro .= "Favor informar a data inicial para pesquisa<br>";
	}
	if ($_GET["data_final"] == 'dd/mm/aaaa'){
		$erro .= "Favor informar a data final para pesquisa<br>";
	}*/

	$data_inicial = trim($_GET["data_inicial"]);
	$data_final   = trim($_GET["data_final"]);

	if (strlen($data_inicial) == 0){
		$erro .= "Favor informar a data inicial para pesquisa<br>";
	}else{
		$fnc           = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
		$data_in       = pg_result($fnc,0,0);
		$xdata_inicial = "$data_in 00:00:00";
	}

	if (strlen($data_final) == 0){
		$erro .= "Favor informar a data final para pesquisa<br>";
	}else{
		$fnc         = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
		$data_fl     = pg_result($fnc,0,0);
		$xdata_final = "$data_fl 23:59:59";
	}
	$codigo_posto = $_GET["codigo_posto"];
	if(strlen($codigo_posto) > 0){
		$sql_posto = "SELECT tbl_posto.posto FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica WHERE tbl_posto_fabrica.codigo_posto = '$codigo_posto'";
		$res_posto = pg_exec($con,$sql_posto);
		if (pg_numrows($res_posto)>0){
			$posto_busca = pg_result($res_posto,0,0);
			$cond_posto = "AND tbl_hd_chamado_extra.posto = $posto_busca";
		}else{
			$erro .= "Verifique o posto digitado!<br/>";
		}
	}

	/*if (strlen($erro) == 0) {
		if (strlen($_GET["data_final"]) == 0){
			$erro .= "Favor informar a data final para pesquisa<br>";
		}
		if (strlen($erro) == 0) {
			$data_final   = trim($_GET["data_final_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}

			if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
		}
	}*/
	$tipo_aba  = $_GET["tipo_aba"];


	if (strlen($erro) > 0) {
		$data_inicial = trim($_GET["data_inicial"]);
		$data_final   = trim($_GET["data_final"]);

		$msg  = "<b>Foi(ram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
		$msg .= $erro;
	}else{
		$listar = "ok";
	}

	if ($listar == "ok") {

		if ($tipo_aba == "at"){
			$condicao = "'reclamacao_at', 'reclamacao_at_info', 'mau_atendimento', 'posto_nao_contribui', 'demonstra_desorg',' possui_bom_atend', 'demonstra_org'";
		}elseif($tipo_aba == "procon"){
			$condicao = "'pr_reclamacao_at', 'pr_info_at', 'pr_mau_atend', 'pr_posto_n_contrib', 'pr_demonstra_desorg', 'pr_bom_atend', 'pr_demonstra_org'";
		}

		$sql = "SELECT	tbl_hd_chamado.hd_chamado       ,
						tbl_hd_chamado.categoria        ,
						tbl_hd_chamado_extra.posto      ,
						tbl_hd_chamado_extra.reclamado  ,
						tbl_posto.nome                  ,
						tbl_posto_fabrica.codigo_posto
		FROM tbl_hd_chamado
		JOIN tbl_hd_chamado_extra using (hd_chamado)
		JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto= tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_hd_chamado.data between '$xdata_inicial' and '$xdata_final'
		AND tbl_hd_chamado.fabrica = $login_fabrica
		AND tbl_hd_chamado.categoria in ($condicao)
		$cond_posto";
		$res = pg_exec($con,$sql);
		if (pg_numrows($res) > 0) {
			$resposta  .= "<b>Resultado de pesquisa entre os dias $data_inicial e $data_final </b>";
			$resposta  .=  "<br><br>";
			$resposta  .=  "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center'>";
			$resposta  .=  "<TR class='Titulo' background='imagens_admin/azul.gif' height='25'>";
			$resposta  .=  "<TD><b>Nº Chamado</b></TD>";
			$resposta  .=  "<TD><b>Código</b></TD>";
			$resposta  .=  "<TD><b>Nome do posto</b></TD>";
			$resposta  .=  "<TD><b>Tipo</b></TD>";
			$resposta  .=  "</TR>";
			for ($i=0; $i<pg_numrows($res); $i++){
				$hd_chamado   = pg_result($res,$i,hd_chamado);
				$categoria    = pg_result($res,$i,categoria);
				$reclamado    = pg_result($res,$i,reclamado);
				$nome         = pg_result($res,$i,nome);
				$codigo_posto = pg_result($res,$i,codigo_posto);
				$posto        = pg_result($res,$i,posto);


				if($categoria =='reclamacao_at' or $categoria == 'pr_reclamacao_at') {
					$categoria_descricao = "RECLAMAÇÃO DA ASSIST. TÉCN.";
				}
				if($categoria == 'reclamacao_at_info' or $categoria == 'pr_info_at') {
					$categoria_descricao = "INFORMAÇÕES DE A.T";
				}
				if($categoria == 'mau_atendimento' or $categoria == 'pr_mau_atend') {
					$categoria_descricao = "MAU ATENDIMENTO";
				}
				if($categoria =='posto_nao_contribui' or $categoria =='pr_posto_n_contrib') {
					$categoria_descricao = "POSTO NÃO CONTRIBUI COM INFORMAÇÕES";
				}
				if($categoria =='demonstra_desorg' or $categoria =='pr_demonstra_desorg') {
					$categoria_descricao = "DEMONSTRA DESORGANIZAÇÃO";
				}
				if($categoria =='possui_bom_atend' or $categoria =='pr_bom_atend') {
					$categoria_descricao = "POSSUI BOM ATENDIMENTO";
				}
				if($categoria =='demonstra_org' or $categoria =='pr_demonstra_org') {
					$categoria_descricao = "DEMONSTRA ORGANIZAÇÃO";
				}

				if($cor=="#F1F4FA")$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';

				$resposta  .=  "<TR bgcolor='$cor'class='Conteudo'>";
				$resposta  .=  "<TD align='center'><a href='callcenter_interativo_new.php?callcenter=$hd_chamado#$categoria' target='_blank'>$hd_chamado</a></TD>";
				$resposta  .=  "<TD align='center' nowrap>$codigo_posto</TD>";
				$resposta  .=  "<TD align='center'nowrap>$nome</TD>";
				$resposta  .=  "<TD align='center'>$categoria_descricao</TD>";
				$resposta  .=  "</TR>";
				$resposta  .=  "<TR bgcolor='#FFFFFF'class='Conteudo'>";
				$resposta  .=  "<TD align='LEFT' colspan='4'>Obs: $reclamado</TD>";
				$resposta  .=  "</TR>";
			}
			$resposta .= " </TABLE>";
			// monta URL
			$data_inicial = trim($_POST["data_inicial_01"]);
			$data_final   = trim($_POST["data_final_01"]);

		}else{
			$resposta .=  "<br>";
			$resposta .= "<b>Nenhum resultado encontrado entre $data_inicial e $data_final</b>";
		}
		$listar = "";

	}
	if (strlen($erro) > 0) {
		echo "no|".$msg;
	}else{
//		$resposta .=  "$sql";
		echo "ok|".$resposta;
	}
	exit;

	flush();

}

$layout_menu = "callcenter";
$title = "RELATÓRIO CALL-CENTER POSTO";

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
.Conteudo {
	font-family: Arial;
	font-size: 10px;
	font-weight: normal;
}


.Caixa{
	BORDER-RIGHT: #6699CC 1px solid;
	BORDER-TOP: #6699CC 1px solid;
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid;
	BORDER-BOTTOM: #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}
.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 8 px;
	font-weight: none;
	color: #000000;
	text-align: center;
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
.Carregando{
	TEXT-ALIGN: center;
	BORDER-RIGHT: #aaa 1px solid;
	BORDER-TOP: #aaa 1px solid;
	FONT: 10pt Arial ;
	COLOR: #000000;
	BORDER-LEFT: #aaa 1px solid;
	BORDER-BOTTOM: #aaa 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
	margin-left:20px;
	margin-right:20px;
}
.botal{
	position: absolute;
}
</style>

<? include "javascript_pesquisas.php" ?>

<? include "javascript_calendario.php";?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="../js/bibliotecaAJAX.js"></script>
<script language='javascript'>


function Exibir (componente,fabrica) {
	var var1 = document.frm_relatorio.data_inicial.value;
	var var2 = document.frm_relatorio.data_final.value;
	var var3 = document.frm_relatorio.codigo_posto.value;
	if(document.frm_relatorio.tipo_aba[0].checked){
		var var4 = document.frm_relatorio.tipo_aba[0].value;
	}else{
		var var4 = document.frm_relatorio.tipo_aba[1].value;
	}
	var com = document.getElementById(componente);

	$.ajax({
		type: "GET",
		url: "<?=$PHP_SELF?>",
		data: 'data_inicial='+var1+'&data_final='+var2+'&codigo_posto='+var3+'&tipo_aba='+var4+'&ajax=sim&tempo='+Date(),
		beforeSend: function(){
			$('#consulta').slideUp('fast');
			$('#dados').html("&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='js/loadingAnimation.gif'> ");
			$('#dados').show('slow');
		},
		complete: function(http) {
			results = http.responseText.split("|");
			if(results[0] =='no') {
				$('#erro').addClass('Erro');
				$('#erro').show('slow');
				$('#erro').html(results[1]);
				$('#dados').slideToggle('slow');
			}else{
				$('#erro').hide();
				$(com).html(results[1]);
			}
			$('#consulta').show('slow');

		}
	});
}

</script>

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
<div id='erro'></div>
<div id='carregando' style='position: absolute;visibility:hidden;opacity:.90;' class='Carregando'></div>
<table width='600' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>
	<tr>
		<td class='Titulo' background='imagens_admin/azul.gif'>RELATÓRIO CALL-CENTER POSTO</td>
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
						<input type="text" name="codigo_posto" id="codigo_posto" size="12"  value="<? echo $codigo_posto ?>" class="Caixa">
						<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'codigo')">
					</td>
					<td align='right' nowrap><font size='2'>Nome do Posto</td>
					<td align='left'>
						<input type="text" name="posto_nome" id="posto_nome" size="30"  value="<?echo $posto_nome?>" class="Caixa">
						<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'nome')">
					</td>
					<td width="10">&nbsp;</td>
				</tr>
				<tr>
					<td width="8">&nbsp;</td>
					<td align='right' nowrap><font size='2'>A.T.</td>
					<td align='left'>
						<input type="radio" name="tipo_aba" value="at" class="Caixa" <?if(strlen($tipo_aba) ==0) { echo "CHECKED";
						}?>>
					</td>
					<td align='right' nowrap><font size='2'>Procon/Jec</td>
					<td align='left'>
						<input type="radio" name="tipo_aba" value="procon" class="Caixa">
					</td>
					<td width="12">&nbsp;</td>
				</tr>
			</table><br>
			<input type='button' onclick="javascript:Exibir('dados','<?=$login_fabrica?>');" style="cursor:pointer " value='Consultar' id='consulta'>
		</td>
	</tr>
</table>
</FORM>


<?

echo "<div id='dados'></div>";

?>

<p>

<? include "rodape.php" ?>