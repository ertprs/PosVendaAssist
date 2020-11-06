<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="financeiro";
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
	exit;
}



if($_GET['ajax']=='sim') {
		$produto            = $_GET['produto'];
		$produto_referencia = $_GET['produto_referencia'];
		$defeito_constatado = trim($_GET['defeito_constatado']);
		if(strlen($produto_referencia) > 0){
			$cond_1 = " AND tbl_produto.referencia = '$produto_referencia' ";
		}
		if(strlen($defeito_constatado) > 0) {
			$cond_2 = " AND tbl_defeito_constatado.defeito_constatado = $defeito_constatado ";
		}
		$sql = "SELECT  tbl_produto.referencia_fabrica                 ,
						tbl_produto.descricao                          ,
						tbl_produto.mao_de_obra as MO                  ,
						tbl_defeito_constatado.codigo                  ,
						tbl_defeito_constatado.descricao as defeito    ,
						tbl_produto_defeito_constatado.mao_de_obra as MO_def
				FROM tbl_produto_defeito_constatado
				JOIN tbl_defeito_constatado using(defeito_constatado)
				JOIN tbl_produto on tbl_produto_defeito_constatado.produto = tbl_produto.produto
				JOIN tbl_linha on tbl_produto.linha = tbl_linha.linha
				WHERE tbl_linha.fabrica = $login_fabrica
				AND   tbl_linha.linha = 198
				$cond_1
				$cond_2
				AND tbl_produto_defeito_constatado.mao_de_obra > 0
				ORDER BY tbl_defeito_constatado.descricao,tbl_produto.descricao;";

		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			$resposta  .=  "<table border='0' cellpadding='2' cellspacing='1' class='tabela'  align='center' >";
			$resposta  .= "<thead>";
			$resposta  .= "<TR class='titulo_coluna' height='25'>";
			$resposta  .= "<Th><b>Referência</b></Th>";
			$resposta  .= "<th><b>Descrição</b></th>";
			$resposta  .= "<th><b>MO Produto</b></th>";
			$resposta  .= "<th><b>Código</b></th>";
			$resposta  .= "<th><b>Defeito Constatado</b></th>";
			$resposta  .= "<th><b>MO Defeito</b></th>";
			$resposta  .= "</TR>";
			$resposta  .= "</thead>";
			$resposta  .= "<tbody>";
			for ($i=0; $i<pg_numrows($res); $i++){
				$referencia      = trim(pg_result($res,$i,referencia_fabrica));
				$codigo          = trim(pg_result($res,$i,codigo))         ;
				$descricao       = trim(pg_result($res,$i,descricao))         ;
				$mo_produto      = trim(pg_result($res,$i,MO))                ;
				$defeito         = trim(pg_result($res,$i,defeito))           ;
				$mo_defeito      = trim(pg_result($res,$i,MO_def))            ;

				$cor="";
				if($i%2==0)$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';

				$resposta  .=  "<TR bgcolor='$cor'>";
				$resposta  .=  "<TD align='center'nowrap>$referencia</TD>";
				$resposta  .=  "<TD align='center' >$descricao</TD>";
				$resposta  .=  "<TD align='center'>$mo_produto</TD>";
				$resposta  .=  "<TD align='center'>$codigo</TD>";
				$resposta  .=  "<TD align='center'>$defeito</TD>";
				$resposta  .=  "<TD align='center'>$mo_defeito</TD>";
				$resposta  .=  "</TR>";
			}
			$resposta .="</tbody>";
			$resposta .= " </TABLE>";

			flush();
			$data = date ("d/m/Y H:i:s");

			$arquivo_nome     = "relatorio-mo_dewalt-$login_fabrica.xls";
			$path             = "/www/assist/www/admin/xls/";
			$path_tmp         = "/tmp/";

			$arquivo_completo     = $path.$arquivo_nome;
			$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

			echo `rm $arquivo_completo_tmp `;
			echo `rm $arquivo_completo `;

			$fp = fopen ($arquivo_completo_tmp,"w");

			fputs ($fp,"<html>");
			fputs ($fp,"<head>");
			fputs ($fp,"<title>RELATÓRIO DE MÃO-DE-OBRA DEWALT - $data");
			fputs ($fp,"</title>");
			fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
			fputs ($fp,"</head>");
			fputs ($fp,"<body>");
			fputs ($fp,"<table border='0' cellpadding='2' cellspacing='1' class='tabela'  align='center' >");
			fputs ($fp,"<thead>");
			fputs ($fp,"<TR class='titulo_coluna' >");
			fputs ($fp,"<Th><b>Referência</b></Th>");
			fputs ($fp,"<th><b>Descrição</b></th>");
			fputs ($fp,"<th><b>MO produto</b></th>");
			fputs ($fp,"<th><b>Defeito constatado</b></th>");
			fputs ($fp,"<th><b>MO defeito</b></th>");
			fputs ($fp,"</TR>");
			fputs ($fp,"</thead>");
			fputs ($fp,"<tbody>");
			for ($i=0; $i<pg_numrows($res); $i++){
				$referencia      = trim(pg_result($res,$i,referencia_fabrica));
				$descricao       = trim(pg_result($res,$i,descricao))         ;
				$mo_produto      = trim(pg_result($res,$i,MO))                ;
				$defeito         = trim(pg_result($res,$i,defeito))           ;
				$mo_defeito      = trim(pg_result($res,$i,MO_def))            ;

				if($cor=="#F1F4FA")$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';
				fputs ($fp,"<TR bgcolor='$cor'>");
				fputs ($fp,"<TD align='center'nowrap>$referencia</TD>");
				fputs ($fp,"<TD align='center' >$descricao</TD>");
				fputs ($fp,"<TD align='center'>$mo_produto</TD>");
				fputs ($fp,"<TD>$codigo - $defeito</TD>");
				fputs ($fp,"<TD align='center'>$mo_defeito</TD>");
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

$layout_menu = "financeiro";
$title = "RELATÓRIO DE MÃO-DE-OBRA DEWALT";

include "cabecalho.php";

?>

<style>

.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 8 px;
	font-weight: none;
	color: #000000;
	text-align: center;
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
</style>


<? include "javascript_pesquisas.php" ?>

<? include "javascript_calendario_new.php"; //adicionado por Fabio 27-09-2007 ?>

<script language="javascript" src="js/effects.explode.js"></script>
<script language="javascript" src="../js/bibliotecaAJAX.js"></script>
<script language='javascript'>


function Exibir (componente,fabrica) {
	var var1 = document.frm_relatorio.produto_referencia.value;
	var var2 = document.frm_relatorio.defeito_constatado.value;

	var com = document.getElementById(componente);

	$.ajax({
		type: "GET",
		url: "<?=$PHP_SELF?>",
		data: 'produto_referencia='+var1+'&ajax=sim'+'&defeito_constatado='+var2,
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
<table width='700' class='formulario' border='0' cellpadding='5' cellspacing='1' align='center' >
	<tr class="titulo_tabela">
		<td colspan="2">Parâmetros de Pesquisa</td>
	</tr>

	<tr>
		<td width="100">&nbsp;</td>
		<td valign='bottom'>

			<table width='100%' border='0' cellspacing='1' cellpadding='2' class="formulario">
				<tr >
					<td nowrap><label for='produto_referencia'>Referência</label><br>
					<input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" >
					<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'referencia')">
					</td>
					<td nowrap><label for='produto_descricao'>Descrição do Produto</label><br>
					<input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="30" value="<? echo $produto_descricao ?>" >
					<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'descricao')">
					</td>
				</tr>
				<tr >
					<td colspan='2'><label for='defeito_constatado'>Defeito Constatado</label><br>
						<select name='defeito_constatado' size="1" class="frm" id='defeito_constatado'>
						<option value=''>Todos</option>
						<?
							$sql="SELECT DISTINCT tbl_defeito_constatado.defeito_constatado,
										 tbl_defeito_constatado.descricao
									FROM tbl_defeito_constatado
									JOIN tbl_produto_defeito_constatado USING(defeito_constatado)
									WHERE fabrica = $login_fabrica
									AND   ativo IS TRUE
									ORDER BY descricao ";
							$res=pg_exec($con,$sql);
							if(pg_numrows($res) > 0) {
								for($i=0;$i<pg_numrows($res);$i++){
									$defeito_constatado = pg_result($res,$i,defeito_constatado);
									$descricao          = pg_result($res,$i,descricao);
									echo "<option value='$defeito_constatado'>$descricao</option>";
								}
							}
						?>
						</select>
					</td>
				</tr>
				</table>			
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
