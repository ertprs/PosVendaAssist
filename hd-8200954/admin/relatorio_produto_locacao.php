<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["tipo_busca"];

	if (strlen($q)>3){
		if($tipo_busca =='produto') {
			$sql = "SELECT tbl_produto.produto,
								tbl_produto.referencia,
								tbl_produto.descricao
						FROM tbl_produto
						JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
						WHERE tbl_linha.fabrica = $login_fabrica
						AND   tbl_linha.linha = 198 ";

			if ($busca == "codigo"){
				$sql .= " AND tbl_produto.referencia ilike '%$q%' ";
			}else{
				$sql .= " AND UPPER(tbl_produto.descricao) ilike UPPER('%$q%') ";
			}

			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$produto            = trim(pg_result($res,$i,produto));
					$referencia         = trim(pg_result($res,$i,referencia));
					$descricao          = trim(pg_result($res,$i,descricao));
					echo "$produto|$descricao|$referencia";
					echo "\n";
				}
			}
		}
		if($tipo_busca =='posto') {
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
	}
	exit;
}



if($_GET['ajax']=='sim') {
		$produto            = $_GET['produto'];
		$produto_referencia = $_GET['produto_referencia'];
		$codigo_posto       = $_GET['codigo_posto'];
		if(strlen($produto_referencia) > 0){
			$cond_1 = " AND tbl_produto.referencia = '$produto_referencia' ";
		}
		if(strlen($codigo_posto) > 0) {
			$cond_2 = " AND tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
		}
		$sql = "SELECT  DISTINCT tbl_produto.referencia                         ,
						tbl_produto.referencia_fabrica                 ,
						tbl_produto.descricao                          ,
						tbl_produto.voltagem                           ,
						tbl_produto.ativo                              ,
						tbl_posto_fabrica.codigo_posto                 ,
						tbl_posto.nome                                  
				FROM tbl_produto
				JOIN tbl_locacao USING(produto)
				JOIN tbl_posto   USING(posto)
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_linha on tbl_produto.linha = tbl_linha.linha
				WHERE tbl_linha.fabrica = $login_fabrica
				$cond_1
				$cond_2
				ORDER BY tbl_posto_fabrica.codigo_posto,tbl_produto.referencia;";

		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			$resposta  .=  "<table border='1' cellpadding='2' cellspacing='1' class='tabela'  align='center' >";
			$resposta  .= "<thead>";
			$resposta  .= "<TR class='titulo_coluna' >";
			$resposta  .= "<Th><b>Referência</b></Th>";
			$resposta  .= "<th><b>Descrição</b></th>";
			$resposta  .= "<th><b>Voltagem</b></th>";
			$resposta  .= "<th><b>Status</b></th>";
			$resposta  .= "<th><b>Referência Interna</b></th>";
			$resposta  .= "<th><b>Locador</b></th>";
			$resposta  .= "</TR>";
			$resposta  .= "</thead>";
			$resposta  .= "<tbody>";
			for ($i=0; $i<pg_numrows($res); $i++){
				$referencia         = trim(pg_result($res,$i,referencia));
				$referencia_fabrica = trim(pg_result($res,$i,referencia_fabrica));
				$descricao          = trim(pg_result($res,$i,descricao));
				$voltagem           = trim(pg_result($res,$i,voltagem));
				$ativo              = trim(pg_result($res,$i,ativo));
				$codigo_posto       = trim(pg_result($res,$i,codigo_posto));
				$nome               = trim(pg_result($res,$i,nome));
				
				if($ativo =='t') {
					$ativo = "Ativo";
				}else{
					$ativo = "Inativo";
				}
				$cor="";
				if($i%2==0)$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';

				$resposta  .=  "<TR bgcolor='$cor'>";
				$resposta  .=  "<TD align='center'nowrap>$referencia</TD>";
				$resposta  .=  "<TD align='center' >$descricao</TD>";
				$resposta  .=  "<TD align='center'>$voltagem</TD>";
				$resposta  .=  "<TD align='center'>$ativo</TD>";
				$resposta  .=  "<TD align='center'>$referencia_fabrica</TD>";
				$resposta  .=  "<TD align='center'>$codigo_posto - $nome</TD>";
				$resposta  .=  "</TR>";
			}
			$resposta .="</tbody>";
			$resposta .= " </TABLE>";

		}else{
			$resposta .=  "<br>";
			$resposta .= "<b>Nenhum resultado encontrado</b>";
		}
		echo $resposta;
		exit;
}

$layout_menu = "gerencia";
$title = "RELATÓRIO DE PRODUTOS DE LOCAÇÃO";

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
	font-size: 12px;
	font-weight: normal;
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

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
}

</style>


<? include "javascript_pesquisas.php" ?>


<script language="javascript" src="js/jquery-1.3.2.js"></script>
<script language="javascript" src="../js/bibliotecaAJAX.js"></script>
<script language='javascript'>


function Exibir (componente,fabrica) {
	var var1 = document.frm_relatorio.produto_referencia.value;
	var var2 = document.frm_relatorio.codigo_posto.value;

	var com = document.getElementById(componente);

	$.ajax({
		type: "GET",
		url: "<?=$PHP_SELF?>",
		data: 'produto_referencia='+var1+'&ajax=sim'+'&codigo_posto='+var2,
		beforeSend: function(){
			$('#consulta').slideUp('slow');
			$('#dados').html("&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='js/loadingAnimation.gif'> ");
			$('#dados').show('slow');
		},
		complete: function(http) {
			results = http.responseText;
			$(com).html(results);
			$('#consulta').addClass('botao');
			$('#consulta').show('slow');
		}
	});
}

</script>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	$("#produto_descricao").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#produto_descricao").result(function(event, data, formatted) {
		$("#produto_referencia").val(data[2]) ;
		$("#produto").val(data[0]) ;
	});

	/* Busca pelo Nome */
	$("#produto_referencia").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#produto_referencia").result(function(event, data, formatted) {
		$("#produto_descricao").val(data[1]) ;
		$("#produto").val(data[0]) ;
		//alert(data[2]);
	});

	/* Busca pelo Código */
	$("#codigo_posto").autocomplete("<?echo $PHP_SELF.'?busca=codigo&tipo_busca=posto'; ?>", {
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
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome&tipo_busca=posto'; ?>", {
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


<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
<div id='erro' style='position: absolute; top: 150px; left: 80px; opacity:.85;'></div>
<div id='carregando' style='position: absolute;visibility:hidden;opacity:.90;' class='Carregando'></div>
<table width='700' class='formulario'  border='0' cellpadding='5' cellspacing='1' align='center' >
	<tr class="titulo_tabela">
		<td colspan="2">Parâmetros de Pesquisa</td>
	</tr>

	<tr>
		<td width="100">&nbsp;</td>
		<td valign='bottom'>

			<table width='100%' border='0' cellspacing='1' cellpadding='2' class="formulario">
				<tr class="Conteudo" bgcolor="#D9E2EF">
					<td nowrap><label for='produto_referencia'>Referência</label><br>
					<input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" >
					<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'referencia')">
					</td>
					<td nowrap><label for='produto_descricao'>Descrição do Produto</label><br>
					<input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="30" value="<? echo $produto_descricao ?>" >
					<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'descricao')">
					</td>
				</tr>
				<tr class="Conteudo" bgcolor="#D9E2EF">
					<td nowrap><label for='codigo_posto'>Código Posto</label><br>
						<input class="frm" type="text" name="codigo_posto" id="codigo_posto" size="12"  value="<? echo $codigo_posto ?>" >
						<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'codigo')">
					</td>
					<td nowrap><label for='posto_nome'>Nome do Posto</label><br>
						<input class="frm" type="text" name="posto_nome" id="posto_nome" size="30"  value="<?echo $posto_nome?>" >
						<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'nome')">
					</td>
				</tr>
				</table><br>
			
		</td>
	</tr>
	<tr>
		<td colspan="2" align="center">
			<input type='button' onclick="javascript:Exibir('dados','<?=$login_fabrica?>');" style="cursor:pointer " value='Consultar' id='consulta'>
		</td>
	</tr>
</table>
</FORM>
<p>
<?
echo "<div id='dados'></div>";
?>
<p>
<? include "rodape.php" ?>
