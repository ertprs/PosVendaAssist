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

	if (strlen($q)>3){
		$sql = "SELECT tbl_produto.produto,
							tbl_produto.referencia,
							tbl_produto.descricao
					FROM tbl_produto
					JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
					WHERE tbl_linha.fabrica = $login_fabrica ";

		if ($busca == "codigo"){
			$sql .= " AND tbl_produto.referencia ilike '%$q%' ";
		}else{
			$sql .= " AND UPPER(tbl_produto.descricao) ilike UPPER('%$q%') ";
		}

		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {
			for ($i=0; $i<pg_num_rows ($res); $i++ ){
				$produto            = trim(pg_fetch_result($res,$i,produto));
				$referencia         = trim(pg_fetch_result($res,$i,referencia));
				$descricao          = trim(pg_fetch_result($res,$i,descricao));
				echo "$produto|$descricao|$referencia";
				echo "\n";
			}
		}
	}
	exit;
}



if($_GET['ajax']=='sim') {
		$produto            = $_GET['produto'];
		$produto_referencia = $_GET['produto_referencia'];
		$familia = trim($_GET['familia']);
		if(strlen($produto_referencia) > 0){
			$cond_1 = " AND tbl_produto.referencia = '$produto_referencia' ";
		}
		if(strlen($familia) > 0) {
			$cond_2 = " AND tbl_produto.familia = $familia ";
		}
		$sql = "SELECT  tbl_cliente_garantia_estendida.nome               ,
						tbl_cliente_garantia_estendida.numero_serie       ,
						tbl_cliente_garantia_estendida.revenda_nome       ,
						tbl_cliente_garantia_estendida.nota_fiscal        ,
						to_char(data_compra,'DD/MM/YYYY') as data_compra  ,
						tbl_produto.referencia                            ,
						tbl_produto.descricao                             ,
						tbl_os.os                                         ,
						tbl_os.sua_os
				FROM    tbl_cliente_garantia_estendida
				JOIN    tbl_produto USING(produto)
				LEFT JOIN tbl_os ON tbl_produto.produto = tbl_os.produto AND tbl_os.serie= tbl_cliente_garantia_estendida.numero_serie and lpad(tbl_cliente_garantia_estendida.nota_fiscal,6,'0') = tbl_os.nota_fiscal
				WHERE   tbl_cliente_garantia_estendida.produto IS NOT NULL
				$cond_1
				$cond_2 ";

		$res = pg_query ($con,$sql);

		if (pg_num_rows($res) > 0) {
			$resposta  .=  "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' >";
			$resposta  .= "<thead>";
			$resposta  .= "<TR class='Titulo' background='imagens_admin/azul.gif' height='25'>";
			$resposta  .= "<Th><b>Nome</b></Th>";
			$resposta  .= "<th><b>Série</b></th>";
			$resposta  .= "<th><b>Revenda</b></th>";
			$resposta  .= "<th><b>Nota Fiscal</b></th>";
			$resposta  .= "<th><b>Data NF</b></th>";
			$resposta  .= "<th><b>Produto</b></th>";
			$resposta  .= "<th><b>OS</b></th>";
			$resposta  .= "</TR>";
			$resposta  .= "</thead>";
			$resposta  .= "<tbody>";
			for ($i=0; $i<pg_num_rows($res); $i++){
				$nome          = trim(pg_fetch_result($res,$i,nome));
				$numero_serie  = trim(pg_fetch_result($res,$i,numero_serie)) ;
				$revenda_nome  = trim(pg_fetch_result($res,$i,revenda_nome));
				$nota_fiscal   = trim(pg_fetch_result($res,$i,nota_fiscal)) ;
				$data_compra   = trim(pg_fetch_result($res,$i,data_compra)) ;
				$referencia    = trim(pg_fetch_result($res,$i,referencia));
				$descricao     = trim(pg_fetch_result($res,$i,descricao)) ;
				$os            = trim(pg_fetch_result($res,$i,os));
				$sua_os        = trim(pg_fetch_result($res,$i,sua_os)) ;

				$cor = ($i%2) ? '#F7F5F0' : '#F1F4FA';

				$resposta  .=  "<tr bgcolor='$cor'class='Conteudo'>";
				$resposta  .=  "<td align='center'nowrap>$nome</TD>";
				$resposta  .=  "<td align='center' >$numero_serie</TD>";
				$resposta  .=  "<td align='center'>$revenda_nome</TD>";
				$resposta  .=  "<td align='center'>$nota_fiscal</TD>";
				$resposta  .=  "<td align='center'>$data_compra</TD>";
				$resposta  .=  "<td align='center'>$referencia - $descricao</TD>";
				$resposta  .=  "<td align='center'>";
				$resposta  .= (strlen($os) > 0) ? "<a href='os_press.php?os=$os' target='_blank'>$sua_os</a>" : "";
				$resposta  .=  "</td>";
				$resposta  .=  "</tr>";
			}
			$resposta .="</tbody>";
			$resposta .= " </table>";

			flush();
			$data = date ("d/m/Y H:i:s");

			$arquivo_nome     = "relatorio-cliente-garantia-estendida-$login_fabrica.xls";
			$path             = "/www/assist/www/admin/xls/";
			$path_tmp         = "/tmp/";

			$arquivo_completo     = $path.$arquivo_nome;
			$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

			echo `rm $arquivo_completo_tmp `;
			echo `rm $arquivo_completo `;

			$fp = fopen ($arquivo_completo_tmp,"w");

			fputs ($fp,"<html>");
			fputs ($fp,"<head>");
			fputs ($fp,"<title>RELATÓRIO DE CLIENTE CADASTRADA COM GARANTIA ESTENDIDA - $data");
			fputs ($fp,"</title>");
			fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
			fputs ($fp,"</head>");
			fputs ($fp,"<body>");
			fputs ($fp,"<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' >");
			fputs ($fp,"<thead>");
			fputs ($fp,"<tr class='Titulo' background='imagens_admin/azul.gif' height='25'>");
			fputs ($fp,"<Th><b>Nome</b></Th>");
			fputs ($fp,"<th><b>Série</b></th>");
			fputs ($fp,"<th><b>Revenda</b></th>");
			fputs ($fp,"<th><b>Nota Fiscal</b></th>");
			fputs ($fp,"<th><b>Data NF</b></th>");
			fputs ($fp,"<th><b>Produto</b></th>");
			fputs ($fp,"<th><b>OS</b></th>");
			fputs ($fp,"</tr>");
			fputs ($fp,"</thead>");
			fputs ($fp,"<tbody>");
			for ($i=0; $i<pg_num_rows($res); $i++){
				$nome          = trim(pg_fetch_result($res,$i,nome));
				$numero_serie  = trim(pg_fetch_result($res,$i,numero_serie)) ;
				$revenda_nome  = trim(pg_fetch_result($res,$i,revenda_nome));
				$nota_fiscal   = trim(pg_fetch_result($res,$i,nota_fiscal)) ;
				$data_compra   = trim(pg_fetch_result($res,$i,data_compra)) ;
				$referencia    = trim(pg_fetch_result($res,$i,referencia));
				$descricao     = trim(pg_fetch_result($res,$i,descricao)) ;
				$os            = trim(pg_fetch_result($res,$i,os));
				$sua_os        = trim(pg_fetch_result($res,$i,sua_os)) ;

				$cor = ($i%2) ? '#F7F5F0' : '#F1F4FA';

				fputs ($fp,"<TR bgcolor='$cor'class='Conteudo'>");
				fputs ($fp,"<TD align='center'nowrap>$nome</TD>");
				fputs ($fp,"<TD align='center' >$numero_serie</TD>");
				fputs ($fp,"<TD align='center'>$revenda_nome</TD>");
				fputs ($fp,"<TD align='center'>$nota_fiscal</TD>");
				fputs ($fp,"<TD align='center'>$data_compra</TD>");
				fputs ($fp,"<TD align='center'>$referencia - $descricao</TD>");
				fputs ($fp,"<TD align='center'>$sua_os</TD>");
				fputs ($fp,"</TR>");

			}
			fputs ($fp,"</tbody>");
			fputs ($fp, " </TABLE>");


			echo ` cp $arquivo_completo_tmp $path `;
			$data = date("Y-m-d").".".date("H-i-s");

			echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;
			$resposta .= "<br>";
			$resposta .="<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
			$resposta .="<tr>";
			$resposta .= "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/$arquivo_nome' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
			$resposta .= "</tr>";
			$resposta .= "</table>";
		}else{
			$resposta .=  "<br>";
			$resposta .= "<b>Nenhum resultado encontrado</b>";
		}
		echo $resposta;
		exit;
		flush();
}

$layout_menu = "callcenter";
$title = "RELATÓRIO DE CLIENTE CADASTRADA COM GARANTIA ESTENDIDA";

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
</style>


<? include "javascript_pesquisas.php" ?>

<? include "javascript_calendario_new.php"; //adicionado por Fabio 27-09-2007 ?>

<script language="javascript" src="js/effects.explode.js"></script>
<script language="javascript" src="../js/bibliotecaAJAX.js"></script>
<script language='javascript'>


function Exibir (componente,fabrica) {
	var var1 = document.frm_relatorio.produto_referencia.value;
	var var2 = document.frm_relatorio.familia.value;

	var com = document.getElementById(componente);

	$.ajax({
		type: "GET",
		url: "<?=$PHP_SELF?>",
		data: 'produto_referencia='+var1+'&ajax=sim'+'&familia='+var2,
		beforeSend: function(){
			$('#consulta').effect('explode');
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


});
</script>


<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
<div id='erro' style='position: absolute; top: 150px; left: 80px; opacity:.85;'></div>
<div id='carregando' style='position: absolute;visibility:hidden;opacity:.90;' class='Carregando'></div>
<table width='600' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center' >
	<tr>
		<td class='Titulo' background='imagens_admin/azul.gif'><?=$title?></td>
	</tr>

	<tr>
		<td bgcolor='#DBE5F5' valign='bottom'>

			<table width='100%' border='0' cellspacing='1' cellpadding='2' >
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
					<td colspan='2'><label for='familia'>Familia</label>
						<select name='familia' size="1" class="frm" id='familia'>
						<option value=''>Todas</option>
						<?
							$sql="SELECT DISTINCT tbl_familia.familia,
										 tbl_familia.descricao
									FROM tbl_familia
									WHERE fabrica = $login_fabrica
									AND   ativo IS TRUE
									ORDER BY descricao ";
							$res=pg_query($con,$sql);
							if(pg_num_rows($res) > 0) {
								$resultados = pg_fetch_all($res);
								foreach($resultados as $resultado){
									echo "<option value='".$resultado['familia']."'>".$resultado['descricao']."</option>";
								}
							}
						?>
						</select>
					</td>
				</tr>
				</table><br>
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
