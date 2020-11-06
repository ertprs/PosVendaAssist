<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria,gerencia";
include 'autentica_admin.php';

$cachebypass=md5(time());

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];
	
	if (strlen($q)>3){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
		
		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$sql .= " LIMIT 50 ";
		
		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$cnpj = trim(pg_result($res,$i,cnpj));
				$nome = trim(pg_result($res,$i,nome));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				echo "$codigo_posto|$nome|$cnpj";
				echo "\n";
			}
		}
	}
	exit;
}

$layout_menu = "callcenter";
$title = "Callcenter -  Relatório de Indicação de Postos";

include 'cabecalho.php';
?>

<style type="text/css">
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
	font-size: 11px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 11px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 8px;
}
</style>

<?include "javascript_pesquisas.php"; ?>

<? include "javascript_calendario.php"; ?>
<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 
<script>
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

});
</script>

<script language="JavaScript">
function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa.php?forma=&campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.referencia= campo;
		janela.descricao= campo2;
		janela.focus();
	}
}
</script>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial_01').datePicker({startDate:'01/01/2000'});
		$('#data_final_01').datePicker({startDate:'01/01/2000'});
		$("#data_inicial_01").maskedinput("99/99/9999");
		$("#data_final_01").maskedinput("99/99/9999");
	});
</script>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>



<script language="javascript">

function chamaAjax2(linha,data,fone,cache) {
	
	if (document.getElementById('div_sinal2_' + linha).innerHTML == '+' || document.getElementById('div_sinal2_' + linha).innerHTML == '<a>+</a>') {

	requisicaoHTTP('GET','mostra_os_posto_indicacao_ajax.php?linha='+linha+'&cachebypass='+cache+'&data='+data+'&fone='+fone, true , 'div_detalhe_carrega2');
	}
	else
	{
		document.getElementById('div_detalhe2_' + linha).innerHTML = "";
		document.getElementById('div_sinal2_' + linha).innerHTML = '+';
	}

}

function load2(linha) {
	document.getElementById('div_detalhe2_' + linha).innerHTML = "<img src='a_imagens/ajax-loader.gif'>";
}

function div_detalhe_carrega2 (campos) {
	campos_array = campos.split("|");
	linha = campos_array [0];
	document.getElementById('div_detalhe2_' + linha).innerHTML = campos_array[1];
	document.getElementById('div_sinal2_' + linha).innerHTML = '-';
}
</script>

<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>
<?
$data_inicial = $_POST['data_inicial_01'];
$data_final = $_POST['data_final_01'];
?>
<form name="frm_pesquisa" method="POST" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">
<table width="500px" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Titulo">
		<td colspan="4">Preencha os campos para realizar a pesquisa.</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
			<td width='10%'></td>
			<td width='40%' align='left'>Data Inicial</td>
			<td width='40%' align='left'>Data Final</td>
			<td width='10%'></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td></td>
			<TD ><INPUT size="12" maxlength="10" TYPE="text" class='frm' NAME="data_inicial_01" id="data_inicial_01" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>"></TD>
		<TD><INPUT size="12" maxlength="10" TYPE="text" class='frm'  NAME="data_final_01" id="data_final_01" value="<? if (strlen($data_final) > 0) echo $data_final; ?>"></TD>
		<td></td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="4" align="center"><img border="0" src="imagens/btn_pesquisar_400.gif" onclick="document.frm_pesquisa.acao.value='PESQUISAR'; document.frm_pesquisa.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</table>

<?
$btn_acao = $_POST['acao'];

if(strlen($btn_acao)>0) {
	flush();
	$referencia = $_POST['referencia'];
	$descricao = $_POST['descricao'];
	
	if (strlen($erro) == 0) {
		if (strlen($_POST["data_inicial_01"]) == 0 or $_POST["data_inicial_01"]=='dd/mm/aaaa') {
			$erro .= "Favor informar a data inicial para pesquisa<br>";
		}
		
		if (strlen($erro) == 0) {
			$data_inicial   = trim($_POST["data_inicial_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}
			if (strlen($erro) == 0) {
				$aux_data_inicial = @pg_result ($fnc,0,0);
			}
		}
	}

	if (strlen($erro) == 0) {
		if (strlen($_POST["data_final_01"]) == 0 or $_POST["data_final_01"] == 'dd/mm/aaaa') {
			$erro .= "Favor informar a data final para pesquisa<br>";
		}
		
		if (strlen($erro) == 0) {
			$data_final   = trim($_POST["data_final_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}
			
			if (strlen($erro) == 0){
				$aux_data_final = @pg_result ($fnc,0,0);
			}
		}
	}

	$codigo_posto = $_POST['codigo_posto'];
	$posto_nome   = $_POST['posto_nome'];


	
	$cond_1 = " 1=1 ";
	if(strlen($codigo_posto)>0){
		$sql = "SELECT posto from tbl_posto_fabrica where codigo_posto='$codigo_posto' and fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$posto = pg_result($res,0,posto);
			$cond_1 = "tbl_hd_chamado_extra.posto = $posto ";
		}
	}

	if (strlen($erro) == 0) {
	
	$sql = "SELECT	tbl_hd_chamado_extra.hd_chamado as callcenter,
					to_char(tbl_hd_chamado.data,'DD/MM/YYYY') as data,
					tbl_hd_chamado_extra.nome,
					tbl_hd_chamado_extra.fone ,
					tbl_hd_chamado_extra.celular ,
					tbl_cidade.nome as cidade_nome,
					tbl_cidade.estado
		FROM tbl_hd_chamado
		JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
		LEFT JOIN tbl_cidade on tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
		LEFT JOIN tbl_posto on tbl_hd_chamado_extra.posto = tbl_posto.posto
		LEFT JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto  and tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
		AND tbl_hd_chamado.data between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'
		AND tbl_hd_chamado.titulo = 'Indicação de Posto'";
				

	//echo nl2br($sql);// exit;
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {

		echo "<BR><BR><font size='1' face='verdana'>$referencia - $descricao ";
		echo $_POST["data_inicial_01"]; 
		echo " até ";
		echo $_POST["data_final_01"]; 
		echo "</font><BR>";
		echo "<table border=1 cellpadding=1 cellspacing=0 style=border-collapse: collapse bordercolor=#d2e4fc align=center width=500>";
		echo "<tr class=Titulo>";
		echo "<td ></td>";
		echo "<td>Chamado</td>";
		echo "<td>Data</td>";
		echo "<td >Cliente</td>";
		echo "<td >Fone</td>";
		echo "<td >Celular</td>";
		echo "<td >Cidade</td>";
		echo "<td >Estado</td>";
		echo "</tr>";
	
		$total = pg_numrows($res);
		$total_pecas = 0;
		
		for ($y=0; $y<pg_numrows($res); $y++){

			$hd_chamado               = trim(pg_result($res,$y,callcenter));
			$nome                     = trim(pg_result($res,$y,nome));
			$data                     = trim(pg_result($res,$y,data));
			$fone                     = trim(pg_result($res,$y,fone));
			$fone_s_mascara           = trim(pg_result($res,$y,fone));
			$celular                  = trim(pg_result($res,$y,celular));
			$cidade                   = trim(pg_result($res,$y,cidade_nome));
			$estado                   = trim(pg_result($res,$y,estado));

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';
			
			$palavras = explode(' ',$fone_s_mascara);
			$count = count($palavras);
			for($x=0 ; $x < $count ; $x++){
				if(strlen(trim($palavras[$x]))>0){
					$fone_s_mascara = trim($palavras[$x]);
					$fone_s_mascara = str_replace (' ','',$fone_s_mascara);
					$fone_s_mascara = str_replace ('(','',$fone_s_mascara);
					$fone_s_mascara = str_replace (')','',$fone_s_mascara);
				}
			}

			echo "<tr>";
			echo "<td><a href=# onClick=load2($y);chamaAjax2($y,'$data','$fone_s_mascara','$cache')><div id=div_sinal2_$y>+</div></a></td>";
			echo "<td bgcolor=$cor align=center nowrap><a href=callcenter_interativo_new.php?callcenter=$hd_chamado target=_blank>$hd_chamado</a></td>";
			echo "<td bgcolor=$cor align=left nowrap>$data</td>";
			echo "<td bgcolor=$cor align=left nowrap>$nome</td>";
			echo "<td bgcolor=$cor nowrap>$fone</td>";
			echo "<td bgcolor=$cor nowrap>$celular</td>";
			echo "<td bgcolor=$cor nowrap>$cidade</td>";
			echo "<td bgcolor=$cor nowrap>$estado</td>";
			echo "</tr>";
			echo "<tr><td colspan=8>";
			echo "<div id=div_detalhe2_$y></div>";
			echo "</td></tr>";
				}

		$total = pg_numrows($res);
		echo "<tr class='Conteudo'>";
		echo "<td colspan='7'><B>Total</b></td>";
		echo "<td >$total</td>";
		echo "</tr>";
		echo "</table>";
	}else{
	echo "<br><center>Nenhum resultado encontrado</center>";
	}
	}
}
if (strlen($erro) > 0) {
	?>
<table width="730" border="0" cellpadding="2" cellspacing="2" align='center'>
<tr>
	<td align="center" class='error'>
			<? echo $erro ?>
			
	</td>
</tr>
</table>
<?
}

include "rodape.php" ;
?>
