<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="info_tecnica";
include 'autentica_admin.php';
//include 'funcoes.php';
include_once "../class/tdocs.class.php";

$tipo  = trim (strtolower ($_GET['tipo']));
$campo = strtoupper($_GET["campo"]);


$idioma  = $_GET['idioma'];
if(strlen($idioma)==0) $idioma = $_POST['idioma'];

if(strlen($tipo) > 0) { // HD 44271
	if($tipo=='referencia') {
		echo "<h4>Pesquisando por <b>descrição do produto</b>: <i>$campo</i></h4>";
	}elseif($tipo=='descricao'){
		echo "<h4>Pesquisando por <b>descrição do produto</b>: <i>$campo</i></h4>";
	}else{
		echo "<h4>Pesquisando por <b>descrição do produto fornecedor</b>: <i>$campo</i></h4>";
	}
	echo "<p>";
	$sql = "SELECT  PR.produto                  ,
					PR.referencia               ,
					PR.descricao                ,
					PI.descricao  AS f_descricao
					$select_idioma
			FROM      tbl_produto            PR
			JOIN      tbl_linha              LI ON LI.linha              = PR.linha
			JOIN      tbl_produto_fornecedor PF ON PF.produto_fornecedor = PR.produto_fornecedor
			LEFT JOIN tbl_produto_idioma     PI ON PI.produto            = PR.produto            AND PI.idioma='EN' 
			WHERE LI.fabrica            = $login_fabrica
			and   PF.produto_fornecedor in (SELECT produto_fornecedor
				FROM  tbl_produto_fornecedor_admin
				WHERE admin = $login_admin ) ";

	if ($tipo == "referencia"){
		$sql .= " AND PR.referencia ILIKE '%$campo%' ";
	}elseif ($tipo == "descricao"){
		$sql .= " AND UPPER(PR.descricao) LIKE '%$campo%' ";
	}else{
		$sql .= " AND UPPER(PI.descricao) LIKE '%$campo%' ";
	}
	$sql .= " ORDER BY PR.referencia,PR.descricao";
	$res = pg_exec($con,$sql);

	if (@pg_numrows ($res) == 0) {
		echo "<h1>Produto '$campo' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
	if (pg_numrows($res) == 1) {
		$produto     = trim(pg_result($res,0,produto));
		$referencia  = trim(pg_result($res,0,referencia));
		$descricao   = trim(pg_result($res,0,descricao));
		$f_descricao = trim(pg_result($res,0,f_descricao));
		echo "<script language='JavaScript'>\n";
		echo "produto.value = '$produto'; descricao.value = '$descricao'; referencia.value = '$referencia'; f_descricao.value='$f_descricao'; ";
		echo "this.close();";
		echo "</script>\n";
	}
	echo "<table width='100%' border='0'>\n";


	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$produto     = trim(pg_result($res,$i,produto));
		$referencia  = trim(pg_result($res,$i,referencia));
		$descricao   = trim(pg_result($res,$i,descricao));
		$f_descricao = trim(pg_result($res,$i,f_descricao));
		echo "<tr>\n";
		echo "<td>\n";
		echo "<a href=\"javascript: produto.value = '$produto'; descricao.value = '$descricao'; referencia.value = '$referencia'; f_descricao.value='$f_descricao'; this.close() ; \" >";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$referencia</font>\n";
		echo "</a>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "<a href=\"javascript: ";
		echo "produto.value = '$produto'; descricao.value = '$descricao'; referencia.value = '$referencia'; f_descricao.value='$f_descricao';this.close() ; \" >";

		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao</font>\n";
		echo "</a>\n";
		echo "</td>\n";
		echo "<td>\n";
		echo "<a href=\"javascript: ";
		echo "produto.value = '$produto'; descricao.value = '$descricao'; referencia.value = '$referencia'; f_descricao.value='$f_descricao'; this.close() ; \" >";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$f_descricao</font>\n";
		echo "</a>\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	echo "</table>\n";
	exit;
}
if(strlen($ajax)>0){
	$imagem         = $_GET['imagem'];
	$peca           = $_GET['peca'];
	$peca_item_foto = $_GET['peca_item_foto'];
	if ($dh = opendir("../imagens_pecas/media/")) {

		if (file_exists("../imagens_pecas/media/$imagem")) {
			echo "<center><img src=\"../imagens_pecas/media/$imagem\" border='0'></center>";
			if (strlen($peca_item_foto)>0) {
				echo "<a href='$PHP_SELF?excluir_peca=sim&peca=$peca&peca_item_foto=$peca_item_foto'&idioma=$idioma>Excluir Foto</a>";
			}
		}else{
			echo"<center>Imagem não existe! <a href='javascript:self.parent.tb_remove()'>Fechar Janela</a></center>";
		}
	}
	exit;
}

$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];

	if (strlen($q)>2){

		if ($tipo_busca=="produto"){
				$sql = "SELECT produto_fornecedor
						FROM  tbl_produto_fornecedor_admin
						WHERE admin = $login_admin";
				$res = pg_exec($con,$sql);
				if(pg_numrows($res)>0){
					$produto_fornecedor = pg_result($res,0,0);
				}
			$sql = "SELECT  PR.produto                  ,
							PR.referencia               ,
							PR.descricao                ,
							PI.descricao  AS f_descricao
					FROM      tbl_produto            PR
					JOIN      tbl_linha              LI ON LI.linha              = PR.linha
					JOIN      tbl_produto_fornecedor PF ON PF.produto_fornecedor = PR.produto_fornecedor
					LEFT JOIN tbl_produto_idioma     PI ON PI.produto            = PR.produto            AND PI.idioma='EN'
					WHERE LI.fabrica            = $login_fabrica
					and   PF.produto_fornecedor in (
						SELECT produto_fornecedor
						FROM  tbl_produto_fornecedor_admin
						WHERE admin = $login_admin
					) ";

			if ($busca == "codigo"){
				$sql .= " AND PR.referencia ILIKE '%$q%' ";
			}elseif ($busca == "descricao"){
				$sql .= " AND UPPER(PR.descricao) LIKE UPPER('%$q%') ";
			}else
				$sql .= " AND UPPER(PI.descricao) LIKE UPPER('%$q%') ";

			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$produto     = trim(pg_result($res,$i,produto));
					$referencia  = trim(pg_result($res,$i,referencia));
					$descricao   = trim(pg_result($res,$i,descricao));
					$f_descricao = trim(pg_result($res,$i,f_descricao));
					echo "$produto|$descricao|$referencia|$f_descricao";
					echo "\n";
				}
			}
		}
	}
	exit;
}
//echo "$admin_autorizado";

function existe_foto($dir, $nome) { //BEGIN function existe_foto
//  $dir: diretório onde deveria estar o arquivo
//  $nome:nome do arquivo, SEM EXTENSÃO

    $a_exts = explode(",","jpg,gif,bmp,png,jpeg,JPG,GIF,BMP,PNG,JPEG");
    foreach ($a_exts as $ext) {
    	if (file_exists($dir.$nome.".".$ext)) {
            return $nome.".".$ext;
        }
    }
    return false;
} // END function existe_foto

$title = "CADASTRO DE FORNECEDORES DE PRODUTO";
$layout_menu = "tecnica";
echo "<center>";
include 'cabecalho.php';

?>
<link rel="stylesheet" href="js/jquery.treeview.css" />
<link rel="stylesheet" href="js/screen.css" />

<script src="js/jquery.js" type="text/javascript"></script>
<script src="js/jquery.cookie.js" type="text/javascript"></script>
<script src="js/jquery.treeview.js" type="text/javascript"></script>
<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen">
<script type="text/javascript">
		$(function() {
			$("#tree").treeview({
				collapsed: true,
				animated: "medium",
				persist: "location"
			});
		})

	</script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1] + " | " + row[3];
	}

	/* Busca por Produto */
	$("#produto_descricao").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=descricao&idioma=$idioma'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#produto_descricao").result(function(event, data, formatted) {
		$("#produto").val(data[0]) ;
		$("#produto_referencia").val(data[2]) ;
		$("#produto_idioma").val(data[3]) ;
	});

	/* Busca pelo Nome */
	$("#produto_referencia").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=codigo&idioma=$idioma'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#produto_referencia").result(function(event, data, formatted) {
		$("#produto").val(data[0]) ;
		$("#produto_descricao").val(data[1]) ;
		$("#produto_idioma").val(data[3]) ;
		//alert(data[2]);
	});

	$("#produto_idioma").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=idioma&idioma=$idioma'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[3];}
	});

	$("#produto_idioma").result(function(event, data, formatted) {
		$("#produto").val(data[0]) ;
		$("#produto_referencia").val(data[2]) ;
		$("#produto_descricao").val(data[1]) ;
		//alert(data[2]);
	});




});


function fnc_pesquisa_produto (campo, campo2,campo3, tipo,produto) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}
	if (tipo == "f_descricao" ) {
		var xcampo = campo3;
	}


	if (xcampo.value != "") {
		var url = "";
		url = "<?$PHP_SELF?>?campo=" + xcampo.value + "&tipo=" + tipo + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia   = campo;
		janela.descricao    = campo2;
		janela.f_descricao  = campo3;
		janela.produto  = produto;
		janela.focus();
	}

	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}

function fnc_pesquisa_peca (campo, tipo, produto_fornecedor, peca) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "peca_pesquisa_4.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo + "&produto_fornecedor=" + produto_fornecedor + "&peca=" + peca;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.referencia= campo;
		janela.peca= peca;
	
		janela.focus();
	}
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}
</script>

<style type="text/css">
.Relatorio{
	font-family: Verdana,sans;
	font-size:10px;
}
.Relatorio thead{
	background: #596D9B ;
	color:#FFFFFF;
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

.espaco{
	padding:10px 0 0 150px;
}
</style>

<?
	if($msg_erro){
?>
<table width='700px' align='center' border='0' cellspacing="1" cellpadding="0">
<tr align='center'>
	<td class='msg_erro'>
		<? echo $msg_erro; ?>
	</td>
</tr>
</table>
<?}

$sql2 = "SELECT produto_fornecedor
		FROM  tbl_produto_fornecedor_admin
		WHERE admin = $login_admin";
$res2 = pg_exec($con,$sql2);
if(pg_numrows($res2)>0){
for ($x=0;$x<pg_numrows($res2);$x++) {
	$produto_fornecedor_peca .= pg_result($res2,$x,0).", ";
}
	$produto_fornecedor_peca = substr($produto_fornecedor_peca,0,strlen($produto_fornecedor_peca)-2);
}

?>

<form name="frm_situacao" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="produto_fornecedor" value="<? echo $produto_fornecedor ?>">
<input type="hidden" name="peca" id="peca"  class='frm' value="<?=$peca?>">

<table border="0" cellpadding="0" cellspacing="0" align="center" class="formulario">
<tr>
	<td valign="top" align="left">
		<table class="formulario" align='center' width='700' border='0'>
			<tr class="titulo_tabela">
				<th colspan='4'>
				<? if ($idioma=='EN'){echo 'Suppliers - Spare Parts';}else{echo 'Cadastro ';}
				?></th>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
			<tr  align='left'>
				
				<td width='160' <?if ($login_fabrica <> 3){ echo  "colspan=\'3\'";} ?> class='espaco'>
					<? 
						if ($idioma=='EN'){echo 'Product Code';}else{echo 'Referência do Produto ';}
					?> <br />	
					<input type='hidden' name='produto' id='produto' value='<?=$produto?>' class='frm'><input type='text' name='produto_referencia' id='produto_referencia' value='<?=$produto_referencia?>' class='frm' size='20'>
					<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="fnc_pesquisa_produto(document.frm_situacao.produto_referencia, document.frm_situacao.produto_descricao,document.frm_situacao.produto_idioma, 'referencia',document.frm_situacao.produto)" style="cursor: pointer;">
				</td>

				<?if ($login_fabrica == 3){ ?>
				<td STYLE='padding-top:10px;' width='250'>
				<? if ($idioma=='EN'){echo 'Parts Code';}else{echo 'Referência da Peça ';}
				?> <br />
					<input type='hidden' name='referencia' id='referencia' value='<?=$peca_referencia?>' class='frm'><input type='text' name='peca_referencia' id='peca_referencia' value='<?=$peca_referencia?>' class='frm' size='21'><img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript: fnc_pesquisa_peca (document.frm_situacao.peca_referencia,'referencia','<?=$produto_fornecedor_peca?>',frm_situacao.peca)" style="cursor: pointer;">
				</td>
				<? } ?>
			</tr>
			<tr>
				<td class='espaco' colspan='2'>
					<? if ($idioma=='EN'){echo 'Product Description (Britania/Philco):';}else{echo 'Descrição Produto na Fábrica ';}
					?> <br />
					<input type='text' name='produto_descricao' id='produto_descricao' value='<?=$produto_descricao?>'  class='frm' size='60'>&nbsp;<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="fnc_pesquisa_produto (document.frm_situacao.produto_referencia, document.frm_situacao.produto_descricao,document.frm_situacao.produto_idioma, 'descricao',document.frm_situacao.produto);" style="cursor: pointer;">
				</td>
			</tr>
			<tr>
				<td class='espaco' colspan='2'>
					<? if ($idioma=='EN'){echo 'Product Description (Supplaiers):';}else{echo 'Descrição Produto do Fornecedor ';}
					?> <br />
					<input type='text' name='produto_idioma' id='produto_idioma' value='<?=$produto_idioma?>'  class='frm' size='60'><img border="0" src="imagens/lupa.png" align="absmiddle" onclick="fnc_pesquisa_produto (document.frm_situacao.produto_referencia, document.frm_situacao.produto_descricao,document.frm_situacao.produto_idioma, 'f_descricao',document.frm_situacao.produto)" style="cursor: pointer;">
				</td>
			</tr>
			<tr>
				<th align='center' colspan='4' style='padding-top:10px;'>
				<input type='hidden' name='idioma' value='<?echo $idioma?>'>
				<input type='submit' name='btn_acao' value='<? if ($idioma=='EN'){echo 'Search';}else{echo 'Pesquisar';}
				?>'></th>
			</tr>
		</table>

		
	</td>
</tr>
</table>

<br>
<table width='700' align='center' class='formulario' cellspacing='1' border='0'>
			<tr align='left'>
				<td colspan='4' class='titulo_tabela'>
				<? if ($idioma=='EN'){echo 'PRODUCTS';}else{echo 'PRODUTOS';}
				?>
				</td>
			</tr>
			<tr>
				<td>
				<?
				$sql = "SELECT produto_fornecedor
						FROM  tbl_produto_fornecedor_admin
						WHERE admin = $login_admin";
				$res = pg_exec($con,$sql);
				if(pg_numrows($res)>0){
					for ($x=0;$x<pg_numrows($res);$x++) {
						$produto_fornecedor .= pg_result($res,$x,0).", ";
					}
					$produto_fornecedor = substr($produto_fornecedor,0,strlen($produto_fornecedor)-2);
					//$produto_fornecedor = pg_result($res,0,0);
					$xlinha_nome = "";
					$xfamilia_nome = "";
					$sql = "SELECT  tbl_linha.nome       AS linha_nome                      ,
									tbl_familia.descricao  AS familia_descricao             ,
									tbl_produto.produto                                     ,
									tbl_produto.referencia AS produto_referencia            ,
									tbl_produto.descricao  AS produto_descricao             ,
									tbl_produto_idioma.descricao  AS fornecedor_descricao   ,
									tbl_linha_idioma.descricao AS linha_idioma              ,
									tbl_familia_idioma.descricao AS familia_idioma
							FROM      tbl_produto            
							JOIN      tbl_familia            tbl_familia USING(familia)
							JOIN      tbl_linha              ON tbl_produto.linha                = tbl_linha.linha and tbl_linha.fabrica=$login_fabrica
							LEFT JOIN tbl_linha_idioma       ON tbl_linha_idioma.linha     = tbl_linha.linha
							LEFT JOIN tbl_familia_idioma     ON tbl_familia_idioma.familia = tbl_familia.familia
							JOIN      tbl_produto_fornecedor ON tbl_produto_fornecedor.produto_fornecedor   = tbl_produto.produto_fornecedor
							LEFT JOIN tbl_produto_idioma     ON tbl_produto_idioma.produto = tbl_produto.produto AND tbl_produto_idioma.idioma = 'EN'
							WHERE tbl_produto_fornecedor.fabrica            = $login_fabrica
							AND   tbl_produto_fornecedor.produto_fornecedor IN ($produto_fornecedor)
							ORDER BY tbl_linha.nome,tbl_familia.descricao";
					
					$res = pg_exec($con,$sql);
					$fechou_linha   = true;
					$fechou_familia = true;
					if(pg_numrows($res)>0){
						echo "\n\n<ul id='tree'>\n";
						for($i=0;$i<pg_numrows($res);$i++){
							$linha_nome           = pg_result($res,$i,linha_nome);
							$familia_nome         = pg_result($res,$i,familia_descricao);
							$produto              = pg_result($res,$i,produto);
							$produto_referencia   = pg_result($res,$i,produto_referencia);
							$produto_descricao    = pg_result($res,$i,produto_descricao);
							$fornecedor_descricao = pg_result($res,$i,fornecedor_descricao);
							$familia_idioma       = pg_result($res,$i,familia_idioma);
							$linha_idioma         = pg_result($res,$i,linha_idioma);

							$fechou_familia = 'f';

							if($linha_nome <> $xlinha_nome) {
								if ($xlinha_nome<>'') {
									$xlinha_nome   = $linha_nome;
									echo "</ul></ul>\n";
									$fechou_familia = "t";
								}

								echo "<li><span><strong>";
								if ($idioma=='EN'){echo $linha_idioma;}else{echo $linha_nome;}
								echo "</strong></span>\n";
								echo "<ul>\n";
							}

							if($familia_nome<>$xfamilia_nome){
								//mudança apenas de família
								if($xfamilia_nome <> '' and $fechou_familia <> 't'){
									echo "</ul>\n";
									//echo "</li>\n";
								}
								echo "<li><span><strong>";
								if ($idioma=='EN'){echo $familia_idioma;}else{echo $familia_nome;}
								echo "</strong></span>\n";
								echo "<ul>\n";
								//$fechou_familia = false;
							}
							
							echo "<li><a href='$PHP_SELF?idioma=$idioma&produto=$produto'>$produto_referencia - $produto_descricao - $fornecedor_descricao</a>\n";

							$xfamilia_nome = $familia_nome;
							$xlinha_nome   = $linha_nome;

							//if($linha_nome<>$xlinha_nome){
							//	echo "</ul>\n";
							//	$fechou = true;
							//}
						}


						if($fechou_familia == false){
							//echo "</ul>\n";
							//echo "</li>\n";
						}
						if($fechou_linha == false){
							//echo "</ul>\n";
							//echo "</li>\n";
						}
						echo "</u1>";

					}
				}else echo "<br><br><center>Nenhum produto cadastrado para o Fornecedor</center>";
				?>
				</td>
			</tr>
		</table>

</form>

<?
$produto = $_GET["produto"];
if(strlen($btn_acao)>0)$produto = $_POST["produto"];
if(strlen($produto)>0){
	$sql = "SELECT  PR.referencia                        ,
					PR.descricao                         ,
					PI.descricao  AS fornecedor_descricao
			FROM      tbl_produto PR
			LEFT JOIN tbl_produto_idioma     PI ON PI.produto = PR.produto AND PI.idioma = 'EN'
			WHERE PR.produto = $produto";
	$res = pg_exec($con,$sql);
	$referencia           = pg_result($res,0,referencia);
	$descricao            = pg_result($res,0,descricao);
	$fornecedor_descricao = pg_result($res,0,fornecedor_descricao);
	if ($idioma=='EN'){
		echo '<h1>Spare Parts of product: '.$referencia.' - '.$descricao.' - '.$fornecedor_descricao.'</h1>';
	}else{
		echo '<h1>Lista Básica do produto: '.$referencia.' - '.$descricao.'<br>Descrição do Fornecedor: '.$fornecedor_descricao.'</h1>';
	}
	$sql = "SELECT 
				PE.peca                                   ,
				PE.referencia                             ,
				PE.descricao                              ,
				tbl_peca_idioma.descricao as peca_idioma
			FROM tbl_lista_basica
			JOIN tbl_peca PE USING(peca)
			left JOIN tbl_peca_idioma USING(peca)
			WHERE tbl_lista_basica.produto = $produto
			ORDER BY PE.descricao";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {

		echo "<table style='border:#485989 1px solid;' align='center' width='700' border='0' class='Relatorio'>";
		echo "<thead style='font-weight: bold;'>";
		echo "<TR>";
		echo "<th width='100' height='15'>";
		if ($idioma=='EN'){echo 'Parts Code';}else{echo 'Referência';}
		echo "</th>";
		echo "<th height='15'>";
		if ($idioma=='EN'){echo 'Parts';}else{echo 'Peças';}
		echo "</th>";
		echo "<th width='120' height='15'>";
		if ($idioma=='EN'){echo 'Photo';}else{echo 'Foto';}
		echo "</th>";
		echo "</TR>";
 		echo "</thead>";
		echo "<tbody>";
		for ($i=0; $i<pg_numrows($res); $i++){
			$peca         = trim(pg_result($res,$i,peca));
			$referencia   = trim(pg_result($res,$i,referencia));
			$descricao    = trim(pg_result($res,$i,descricao));
			$peca_idioma  = trim(pg_result($res,$i,peca_idioma));

			if ($peca_idioma =="") {
				$peca_idioma=$descricao;
			}

			if($cor <>'#FFFFFF') $cor = '#FFFFFF';
			else                 $cor = '#dddddd';

			echo "<tr bgcolor='$cor'>";

			echo "<TD align='left' nowrap>$referencia</TD>";
			echo "<TD align='left' nowrap>";
			if ($idioma=='EN'){echo $peca_idioma;}else{echo $descricao;}
			echo "</TD>";

			if ($login_fabrica == 3 or $login_fabrica == 10)  {
				$tDocs = new TDocs($con, $login_fabrica);
	            $xpecas = $tDocs->getDocumentsByRef($peca, "peca");
	            if (!empty($xpecas->attachListInfo)) {

					$a = 1;
					foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
					    $fotoPeca = $vFoto["link"];
					    if ($a == 1){break;}
					}
					echo "<td align='center'>
							<a href='".$fotoPeca."' title='$descricao' class='thickbox'>
							<img width='60' src='$fotoPeca' border='0' $limita></a>
							<input type='hidden' name='peca_imagem' value='$fotoPeca'>\n
						</td>\n";
	            } else {


					if (false !== ($filename_final = existe_foto($localdir, $peca))) {
						$localdir= "/var/www/assist/www/imagens_pecas/$login_fabrica/pequena/";
						$dir     = "/assist/imagens_pecas/$login_fabrica/pequena/";

						list($width, $height) = getimagesize($localdir.$filename_final);
						$limita = ($height>100)?" height='80'":"";
						//&keepThis=trueTB_iframe=true&height=340&width=420
						echo "<td align='center'><a href='".str_replace("pequena", "media", $dir.$filename_final)."' title='$descricao' class='thickbox'><img src='$dir$filename_final' border='0'$limita></a><input type='hidden' name='peca_imagem' value='$dir$filename_final'>\n</td>\n";
					} else {
						echo "<td align='center'><img src='../imagens_pecas/semimagem.jpg' border='0'></td>\n";
					}
				}
			}
			
			echo "</TR>";
			flush();
			$total = $ocorrencia + $total;

		}
		echo "</tbody>";
		echo " </TABLE></div>";
	}
}
if(strlen($idioma)==0){
	include "rodape.php";
}
?>