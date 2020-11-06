<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "tecnica";

$title = "RELATÓRIO DE INTERAÇÕES";

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

$busca      = $_GET["busca"];
$tipo_busca = $_GET["tipo_busca"];
$btn_acao   = $_POST["btn_acao"];

if ($btn_acao=="Consultar" or strlen($busca)>0){
$ano = $_POST["ano"];
$mes = $_POST["mes"];

if( strlen($mes) == 0 OR strlen($ano)==0 ){
	$msg_erro = "Informe o Mês e o Ano";
}

	if (strlen($mes) > 0) {
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
	}

	if ($tipo_busca=="posto"){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
		
		if ($busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}
		
		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$cnpj = trim(pg_result($res,$i,cnpj));
				$nome = trim(pg_result($res,$i,nome));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	exit;
	}
	if ($tipo_busca=="produto"){
		$sql = "SELECT tbl_produto.produto,
						tbl_produto.referencia,
						tbl_produto.descricao
				FROM tbl_produto
				JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
				WHERE tbl_linha.fabrica = $login_fabrica ";
		
		if ($busca == "codigo"){
			$sql .= " AND UPPER(tbl_produto.descricao) like UPPER('%$q%') ";
		}else{
			$sql .= " AND tbl_produto.referencia like '%$q%' ";
		}
		
		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$produto    = trim(pg_result($res,$i,produto));
				$referencia = trim(pg_result($res,$i,referencia));
				$descricao  = trim(pg_result($res,$i,descricao));
				echo "$produto|$descricao|$referencia";
				echo "\n";
			}
		}
	exit;
	}
}

include "cabecalho.php";

?>
<? include "javascript_pesquisas.php"; ?>
<? include "../js/js_css.php"; ?>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 


<script language="JavaScript">
//Pesquisa pelo AutoComplete AJAX

$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

});


$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}
	
	
	/* Busca pelo Código */
	$("#codigo_posto").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#codigo_posto").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#codigo_posto").val(data[2]) ;
		//alert(data[2]);
	});

	
	/* Busca por Produto */
	$("#produto_descricao").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#produto_descricao").result(function(event, data, formatted) {
		$("#produto_referencia").val(data[2]) ;
	});

	/* Busca pelo Nome */
	$("#produto_referencia").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#produto_referencia").result(function(event, data, formatted) {
		$("#produto_descricao").val(data[1]) ;
		//alert(data[2]);
	});

});
</script>


<style>
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

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}


.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
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

.sub_os{
	font-size:10px;
	color:#676767;
}
</style>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");
	});
</script>

<script language='javascript' src='../ajax.js'></script>

<? 

if(strlen($msg_erro)>0){
	echo "<table width='700' border='0' cellpadding='0' cellspacing='0' align='center' class='msg_erro'>";
	echo "<tr>";
		echo "<td valign='middle' align='center'>";
			echo "$msg_erro";
		echo "</td>";
	echo "</tr>";
	echo "</table>";
}
?>

<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
<table width='700' class='formulartio' border='0' cellpadding='0' cellspacing='1' align='center'>
	<tr height='25'>
		<td class='titulo_tabela'>Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td valign='bottom'>
			<table width='100%' border='0' cellspacing='1' cellpadding='0' class='formulario'>
				<tr align='left'>
					<td width='110'>&nbsp;</td>
					<td>Mês</td>
					<td>Ano</td>
					<td width='50'>&nbsp;</td>
				</tr>
				<tr align='left'>
					<td width='10'>&nbsp;</td>
					<td>
						<select name="mes" size="1" class="frm">
						<option value=''></option>
						<?
						for ($i = 1 ; $i <= count($meses) ; $i++) {
							echo "<option value='$i'";
							if ($mes == $i) echo " selected";
							echo ">" . $meses[$i] . "</option>";
						}
						?>
						</select>
					</td>
					<td>
						<select name="ano" size="1" class="frm">
						<option value=''></option>
						<?
						for ($i = 2003 ; $i <= date("Y") ; $i++) {
							echo "<option value='$i'";
							if ($ano == $i) echo " selected";
							echo ">$i</option>";
						}
						?>
						</select>
					</td>
					<td width='10'>&nbsp;</td>
				</tr>
				<tr align='left'>
					<td width='10'>&nbsp;</td>
					<td>Referência</td>
					<td>Nome do Produto</td>
					<td width='10'>&nbsp;</td>
				</tr>
				<tr align='left'>
					<td width='10'>&nbsp;</td>
					<td>
					<input class="frm" type="text" name="produto_referencia" size="15" id="produto_referencia" maxlength="20" value="<? echo $produto_referencia ?>" > 
					&nbsp;
					<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'referencia')">
					</td>
					<td>
					<input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="40" value="<? echo $produto_descricao ?>" >
					&nbsp;
					<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'descricao')">
					</td>
					<td width='10'>&nbsp;</td>
						<tr align='left'>
					<td width='10'>&nbsp;</td>
							<td>Cód. Posto</td>
							<td>Nome do Posto</td>
					<td width='10'>&nbsp;</td>
				</tr>
				<tr align='left'>
					<td width='10'>&nbsp;</td>
					<td>
						<input type="text" name="codigo_posto" size="15" id="codigo_posto" value="<? echo $codigo_posto ?>" class="frm">
						<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'codigo')">
					</td>
					<td>
						<input type="text" name="posto_nome" size="40" id="posto_nome" value="<?echo $posto_nome?>" class="frm">
						<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'nome')">
					</td>
				</tr>
				<tr>
					<td align='center' colspan='4' style='padding:20px 0 10px 0;'nowrap><input type='submit' style="cursor:pointer" name='btn_acao' value='Consultar'></td>
				</tr>
			</table>
		</tr>
</table>
</FORM>

<?
if($btn_acao=="Consultar" and strlen($msg_erro)==0){
$codigo_posto=$_POST['codigo_posto'];
$codigo_posto       = trim(strtoupper($_POST['codigo_posto']));
$posto_nome         = trim(strtoupper($_POST['posto_nome']));
$produto_referencia = trim(strtoupper($_POST['produto_referencia']));

if (strlen ($produto_referencia) > 0) {
	$sqlX = "SELECT produto FROM tbl_produto JOIN tbl_linha USING (linha) WHERE tbl_linha.fabrica = $login_fabrica AND tbl_produto.referencia = '$produto_referencia'";
	$resX = pg_exec ($con,$sqlX);
	$produto = pg_result ($resX,0,0);
}

if (strlen($codigo_posto) > 0 && strlen($posto_nome) > 0) {
	$sql =	"SELECT tbl_posto.posto
			FROM tbl_posto
			JOIN tbl_posto_fabrica USING (posto)
			WHERE tbl_posto_fabrica.fabrica = $login_fabrica
			AND   tbl_posto_fabrica.codigo_posto = '$codigo_posto';";
	$res = @pg_exec($con,$sql);
	$msg_erro=@pg_errormessage($con);
	if (pg_numrows($res) == 1) {
		$posto        = trim(pg_result($res,0,posto));
	}else{
		$msg_erro .= " Posto não encontrado. ";
	}
}

$largunta_tabela = "90%";

$cond_1 =" 1=1 ";
$cond_2 =" 1=1 ";
$cond_3 =" 1=1 ";

if(strlen($data_inicial) > 0 AND strlen($data_final) > 0){
	$cond_1 =" tbl_os_interacao.data BETWEEN '$data_inicial' AND '$data_final'";
}

if(strlen($produto) > 0){
	$cond_2 =" tbl_os.produto = $produto";	
}

if(strlen($posto) > 0){
	$cond_3 =" tbl_os.posto = $posto";	
}

if(strlen($msg_erro)==0){
			$sql = "SELECT DISTINCT tbl_posto_fabrica.codigo_posto,
							tbl_posto.nome AS nome_fantasia,
							tbl_os.sua_os,
							tbl_os.os ,
							to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura ,
							tbl_produto.referencia ,
							tbl_produto.descricao AS produto_descricao,
							(select to_char(tbl_os_interacao.data, 'dd/mm/yyyy') AS data FROM tbl_os_interacao WHERE tbl_os_interacao.os = tbl_os.os ORDER BY os_interacao DESC LIMIT 1) AS data_resolvido,
							(select to_char(tbl_os_interacao.data, 'dd/mm/yyyy') AS data FROM tbl_os_interacao WHERE tbl_os_interacao.os = tbl_os.os ORDER BY os_interacao ASC LIMIT 1) AS data_chamado
					FROM tbl_os
					JOIN tbl_os_interacao USING(os)
					JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					JOIN tbl_produto ON tbl_produto.produto=tbl_os.produto 
					WHERE tbl_os.fabrica = $login_fabrica
					AND $cond_1
					AND $cond_2
					AND $cond_3
					ORDER BY sua_os, codigo_posto";
#echo nl2br($sql);
	$res = pg_exec($con,$sql);
	if (pg_numrows($res)>0) {
		echo "</table>";
		echo "<br><br>";
		echo "<table width='$largunta_tabela' border='0' align='center' cellpadding='1' cellspacing='1' id='relatorio' name='relatorio' class='tablesorter tabela'>";
		echo "<thead>";
		echo "<TR class='titulo_coluna'>\n";
			echo "<td>OS</TD>\n";
			echo "<td nowrap>Abertura OS</TD>\n";
			echo "<td nowrap>Abertura Chamado</TD>\n";
			echo "<td nowrap>Chamado Resolvido</TD>\n";
			echo "<td>Posto</TD>\n";
			echo "<TD>Produto</TD>\n";
		echo "</TR >\n";
		echo "</thead>\n";
		echo "<tbody>\n";
			for($y=0;pg_numrows($res)>$y;$y++){
				$codigo_posto        = pg_result($res,$y,codigo_posto);
				$sua_os              = pg_result($res,$y,sua_os);
				$os                  = pg_result($res,$y,os);
				$posto_nome          = pg_result($res,$y,nome_fantasia);
				$data_abertura       = pg_result($res,$y,data_abertura);
				$data_chamado        = pg_result($res,$y,data_chamado);
				$data_resolvido      = pg_result($res,$y,data_resolvido);
				$produto_descricao   = pg_result($res,$y,produto_descricao);
				$produto_referencia  = pg_result($res,$y,referencia);

				$posto_nome = strtoupper($posto_nome);

			if ($y % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#F7F5F0';}

			echo "<TR bgcolor='$cor'>\n";
				echo "<TD align='center' nowrap><a href= 'os_press.php?os=$os' target='_blank'>$sua_os</a></TD>\n";
				echo "<TD align='center' nowrap>$data_abertura</TD>\n";
				echo "<TD align='center' nowrap>$data_chamado</TD>\n";
				echo "<TD align='center' nowrap>$data_resolvido</TD>\n";
				echo "<TD align='left' nowrap>$codigo_posto - $posto_nome</TD>\n";
				echo "<TD align='left' nowrap>$produto_referencia - $produto_descricao</TD>\n";
			echo "</TR >\n";
			echo "</tbody>";
			}
		echo "</table>";
	}else{
		echo "<P>Nenhum resultado encontrado</P>";
	}
}
}
?>

<p>

<? include "rodape.php" ?>
