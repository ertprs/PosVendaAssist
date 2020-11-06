<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro";
include 'autentica_admin.php';
include 'funcoes.php';



if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];
	
	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
		
		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto.cnpj = '$q' ";
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
	}
	exit;
}

if ($btnacao == "reset"){
	$posto_lancamento = '';
	$posto_codigo     = '';
	$descricao        = '';
	$valor            = '';

	header ("Location: $PHP_SELF");
}

if ($btnacao == "deletar" and strlen($posto_lancamento) > 0 ) {
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	$sql = "DELETE FROM tbl_posto_lancamento
			WHERE  fabrica          = $login_fabrica
			AND    posto_lancamento = $posto_lancamento;";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if ($btnacao == "gravar") {
	$posto_lancamento = trim($_POST["posto_lancamento"]);
	$posto_codigo     = trim($_POST["posto_codigo"]);
	$descricao        = trim($_POST["descricao"]);
	$valor            = trim($_POST["valor"]);

	if(strlen($posto_codigo)==0) $msg_erro .= "Digite o posto<br>";
	if(strlen($descricao)==0)    $msg_erro .= "Digite a descrição do lançamento<br>";
	if(strlen($valor)==0)        $msg_erro .= "Digite o valor do lançamento<br>";

	$sql = "SELECT posto FROM tbl_posto JOIN tbl_posto_fabrica USING(posto)
			WHERE codigo_posto = '$posto_codigo'
			AND   fabrica      = $login_fabrica";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		$posto = pg_result($res,0,0);
	}else{
		$msg_erro .= "Posto não cadastrado<br>";
	}

	$xvalor = str_replace (",",".",$valor);

	if (strlen($msg_erro) == 0) {

		$res = pg_exec ($con,"BEGIN TRANSACTION");
		if (strlen($posto_lancamento) == 0) {
			$sql = "INSERT INTO tbl_posto_lancamento (
						fabrica   ,
						posto     ,
						descricao ,
						valor
					) VALUES (
						$login_fabrica,
						$posto        ,
						'$descricao'  ,
						$xvalor
					);";
						$msg_sucesso = "Gravado com Sucesso!";
		}else{
			$sql = "UPDATE  tbl_posto_lancamento SET
					descricao   = '$descricao',
					valor       = $valor
				WHERE   posto_lancamento = $posto_lancamento
				AND     fabrica          = $login_fabrica
				AND     posto            = $posto;";
					$msg_sucesso = "Gravado com Sucesso!";
		}
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
	
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		
		header ("Location: $PHP_SELF");
		$msg_sucesso = "Gravado com Sucesso!";
		//exit;

		
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


###CARREGA REGISTRO
if (strlen($posto_lancamento) > 0) {
	$sql =	"SELECT posto_lancamento                                    ,
				descricao                                           ,
				valor                                               ,
				TO_CHAR(data_inclusao,'DD/MM/YYYY') AS data_inclusao,
				PO.posto                                            ,
				PO.nome                             AS posto_nome   ,
				PF.codigo_posto                     AS posto_codigo
		FROM      tbl_posto_lancamento PL
		JOIN      tbl_posto            PO ON PL.posto = PO.posto
		JOIN      tbl_posto_fabrica    PF ON PF.posto = PO.posto AND PF.fabrica = $login_fabrica
		WHERE     PL.fabrica          = $login_fabrica  
		AND       PL.posto_lancamento = $posto_lancamento";
	$res = @pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$posto_lancamento = trim(pg_result($res,0,posto_lancamento));
		$descricao        = trim(pg_result($res,0,descricao));
		$valor            = trim(pg_result($res,0,valor));
		$data_inclusao    = trim(pg_result($res,0,data_inclusao));
		$posto            = trim(pg_result($res,0,posto));
		$posto_nome       = trim(pg_result($res,0,posto_nome));
		$posto_codigo     = trim(pg_result($res,0,posto_codigo));
	}
}

$visual_black = "manutencao-admin";
$layout_menu = "cadastro";
$title = "CADASTRO DE VALOR FIXO MENSAL PARA POSTOS";
include 'cabecalho.php';
?>

<style type="text/css">
	.Label{
	font-family: Verdana;
	font-size: 10px;
	}
	.Titulo{
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
	}
	.Erro{
	font-family: Verdana;
	font-size: 12px;
	color:#FFF;
	border:#485989 1px solid; background-color: #990000;
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

.msg_sucesso{
background-color:green;
font: bold 16px "Arial";
color:#FFFFFF;
text-align:center;
}

.formulario{
background-color:#D9E2EF;
font:11px Arial;
}

.subtitulo{

color: #7092BE
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}

</style>

<?
include "javascript_pesquisas.php" ;
include "javascript_calendario.php";
?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />
<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->
<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[0] + " - " + row[1];
	}
	
	function formatResult(row) {
		return row[0];
	}
	
	/* Busca pelo Código */
	$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#posto_codigo").result(function(event, data, formatted) {
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
		$("#posto_codigo").val(data[2]) ;
		//alert(data[2]);
	});

});
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

});
</script>
<? if (strlen($msg_erro) > 0) { ?>
<table width="700px" cellpadding="2" cellspacing="1" class="msg_erro" align='center'>
	<tr>
		<td><?echo $msg_erro;?></td>
	</tr>
</table>
<? } ?>

<? if ( strlen( $msg_sucesso ) > 0 ) { ?>
<table width="700px" cellpadding="2" cellspacing="1" class='msg_sucesso' align='center'>
	<tr>
		<td><? echo $msg_sucesso ?></td>
	</tr>
</table>
<? } ?>
<form name="frm_extrato_avulso" method="post" action="<? echo $PHP_SELF;?>">
<input type="hidden" name="posto_lancamento" value="<? echo $posto_lancamento ?>">
<input type="hidden" name="posto" value="<? echo $posto ?>">

<table cellpadding="0" cellspacing="0" align="center" class='formulario'>
<tr>
	<td valign="top" align="left">
		<table align='center' width='700px'>
		<tr>
			<td colspan='7' class='titulo_tabela'>Cadastro</td>
		</tr>
		<tr>
		<td width='5%'>&nbsp;</td>
			<td nowrap >Código do Posto</td>
			<td>
				<input class="frm" type="text" name="posto_codigo" id="posto_codigo" size="15" value="<? echo $posto_codigo ?>">&nbsp;
				<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto (document.frm_extrato_avulso.posto_codigo,document.frm_extrato_avulso.posto_nome,'codigo')">
			</td>
			<td align='right'>Nome do Posto</td>
			<td nowrap>&nbsp;&nbsp;&nbsp;&nbsp;
				<input class="frm" type="text" name="posto_nome" id="posto_nome" size="30" value="<? echo $posto_nome ?>" >&nbsp;
				<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_extrato_avulso.posto_codigo,document.frm_extrato_avulso.posto_nome,'nome')" style="cursor:pointer;">
			</td>
		</tr>
		<tr>
		<td>&nbsp;</td>
			<td nowrap >Descrição</td>
			<td><input type='text' name='descricao' value='<?=$descricao?>' size='30' class='frm'></td>
			<td nowrap align='left' colspan='3'>&nbsp;&nbsp;&nbsp;Valor &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='text' name='valor' value='<?=$valor?>' size='5' class='frm'></td>
		</tr>
		<tr class='Label'>
			<td nowrap colspan='7' align='center' >
			<center>
			<br />
			<input type='hidden' name='btnacao' value=''>
			<input type='image' SRC="imagens_admin/btn_gravar.gif" ONCLICK="javascript: if (document.frm_extrato_avulso.btnacao.value == '' ) { document.frm_extrato_avulso.btnacao.value='gravar' ; document.frm_extrato_avulso.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style="cursor:pointer;">
			<input type='image' SRC="imagens_admin/btn_apagar.gif" ONCLICK="javascript: if (document.frm_extrato_avulso.btnacao.value == '' ) { document.frm_extrato_avulso.btnacao.value='deletar' ; document.frm_extrato_avulso.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar produto" border='0' style="cursor:pointer;">
			<input type='image' SRC="imagens_admin/btn_limpar.gif" ONCLICK="javascript: if (document.frm_extrato_avulso.btnacao.value == '' ) { document.frm_extrato_avulso.btnacao.value='reset' ; }" ALT="Limpar campos" border='0' style="cursor:pointer;">
			</center>
			</td>
		</tr>
		</table>
	</td>
</tr>
</table>
</form>



<br>

<?
if(strlen($posto)>0) $sql_add = " AND PO.posto = $posto";
$sql =	"SELECT posto_lancamento                                    ,
				descricao                                           ,
				valor                                               ,
				TO_CHAR(data_inclusao,'DD/MM/YYYY') AS data_inclusao,
				PO.posto                                            ,
				PO.nome                             AS posto_nome   ,
				PF.codigo_posto                     AS posto_codigo
		FROM      tbl_posto_lancamento PL
		JOIN      tbl_posto            PO ON PL.posto = PO.posto
		JOIN      tbl_posto_fabrica    PF ON PF.posto = PO.posto AND PF.fabrica = $login_fabrica
		WHERE     PL.fabrica = $login_fabrica  $sql_add";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	echo "<table width='700px' cellspacing='0' cellpadding='2' align='center'  name='relatorio' id='relatorio' class='formulario'>";
	echo "<thead>";
	echo "<tr bgcolor='' class='titulo_coluna'>";
	echo "<td align='left'>Posto</td>";
	echo "<td>Código</td>";
	echo "<td>Descricao</td>";
	echo "<td>Inclusão</td>";
	echo "<td>Valor</td>";
	echo "</tr>";

	echo "</thead>";

	echo "<tbody>";
	for ($i = 0 ; $i < @pg_numrows($res) ; $i++){
		$posto_lancamento = trim(pg_result($res,$i,posto_lancamento));
		$descricao        = trim(pg_result($res,$i,descricao));
		$valor            = trim(pg_result($res,$i,valor));
		$data_inclusao    = trim(pg_result($res,$i,data_inclusao));
		$posto            = trim(pg_result($res,$i,posto));
		$posto_nome       = trim(pg_result($res,$i,posto_nome));
		$posto_codigo     = trim(pg_result($res,$i,posto_codigo));

		$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";
		$valor = number_format($valor,2,'.','');

		echo "<tr bgcolor='$cor'>";
		echo "<td align='left'><a href='$PHP_SELF?posto_lancamento=$posto_lancamento&posto=$posto'>$posto_nome</a></td>";
		echo "<td>$posto_codigo</td>";
		echo "<td>$descricao</td>";
		echo "<td>$data_inclusao</td>";
		echo "<td align='right'>$valor</td>";
		echo "</tr>";

	}
	echo "</tbody>";
	echo "</table>";
}else{
	echo "Posto sem adicionais cadastrados";
}
echo "<br>";

if(!isset($semcab))include "rodape.php";
?>