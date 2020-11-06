<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include_once "class/tdocs.class.php";
$tDocs = new TDocs($con, $login_fabrica);


	$caminho_dir = "imagens_pecas";


$ajax = $_GET['ajax'];
if(strlen($ajax)>0){
	$imagem = $_GET['imagem'];
	$idpeca = $_GET['idpeca'];
    $xpecas = $tDocs->getDocumentsByRef($idpeca, "peca");
    if (!empty($xpecas->attachListInfo)) {
		$a = 1;
		foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
		    $fotoPeca = $vFoto["link"];
		    if ($a == 1){break;}
		}
		echo "<center>
			<img src='$fotoPeca' border='0'>
			</center>";
    } else {

		if ($dh = opendir('imagens_pecas/media/')) {
			echo "<center>
				<img src='$caminho_dir/$imagem' border='0'>
				</center>";
		}
	}
	exit;
}

$aba = 2;
$title="Detalhes do produto!";
include "login_unico_cabecalho.php";

?>
<script type="text/javascript" src="js/jquery-1.1.2.pack.js"></script>
<script src="admin/js/jquery.MultiFile.js" type="text/javascript" language="javascript"></script>
<script src="admin/js/jquery.MetaData.js" type="text/javascript" language="javascript"></script>


<script src="js/lightbox.js" type="text/javascript"></script>
<link rel="stylesheet" href="js/lightbox.css" type="text/css" media="screen" />

<?
/*
<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
*/
?>

<script type="text/javascript" src="js/jquery.jcarousellite.pack.js"></script>
<script type="text/javascript" src="js/jquery.easing.1.1.js"></script>

<script language='javascript'>
function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseInt(num);
	if (campo.value=='NaN') {
		campo.value='';
	}
}

$(function() {

	$(".imageSlider").jCarouselLite({
		btnNext: ".next",
		btnPrev: ".prev",
		visible: 1,
		speed: 500,
		easing: "easeinout",
		circular: false
	});

});

</script>



<?

$cod_produto = $_GET['cod_produto'];

	$sql = "SELECT  
				tbl_peca.peca            ,
				tbl_peca.referencia              ,
				tbl_peca.ipi            ,
				tbl_peca.descricao               ,
				tbl_peca.estoque                 ,
				tbl_peca.garantia_diferenciada   , 
				tbl_peca.informacoes             ,
				tbl_peca.linha_peca              ,
				tbl_peca.multiplo_site           ,
				tbl_peca.qtde_minima_site        ,
				tbl_peca.qtde_max_site           ,
				tbl_peca.qtde_disponivel_site    ,
				tbl_tabela_item.preco
			FROM tbl_peca 
			LEFT JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca
			WHERE tbl_peca.peca='$cod_produto'";

	$res = pg_exec ($con,$sql);

	if(pg_numrows($res)>0){
		$peca						= trim(pg_result ($res,0,peca));
		$referencia					= trim(pg_result ($res,0,referencia));
		$ipi						= trim(pg_result ($res,0,ipi));
		$descricao					= trim(pg_result ($res,0,descricao));
		$estoque					= trim(pg_result ($res,0,estoque));
		$garantia_diferenciada		= trim(pg_result ($res,0,garantia_diferenciada));
		$informacoes				= trim(pg_result ($res,0,informacoes));
		$linha						= trim(pg_result ($res,0,linha_peca));
		$multiplo_site 				= trim(pg_result ($res,0,multiplo_site));
		$qtde_minima_site			= trim(pg_result ($res,0,qtde_minima_site));
		$qtde_max_site				= trim(pg_result ($res,0,qtde_max_site));
		$qtde_disponivel_site = trim(pg_result ($res,0,qtde_disponivel_site));
		$preco                = trim(pg_result ($res,0,preco));
	
		$preco_formatado = number_format($preco,2,'.',',');

		$parcelas = ($preco/2);
		$parcelas = number_format($parcelas, 2, ',', '');
		$preco_formatado    = number_format($preco, 2, ',', '');
	}


echo "<form action='login_unico_loja_carrinho.php?acao=adicionar' method='post' name='frmcarrinho' align='center'>";
echo "<table width='700' border='0' align='center' cellpadding='0' cellspacing='0' style=' border:#B5CDE8 1px solid;  bordercolor='#d2e4fc'>";

echo "<tr>";
echo "<td align='left'  colspan='2' bgcolor='#e6eef7' ><br><font size='4'>&nbsp;<B>$descricao</B></font><br>&nbsp;</td>";
echo "</tr>";

echo "<tr>";
echo "<td align='center' width='150' ><br>";
if ($dh = opendir('imagens_pecas/pequena/')) {
	$contador=0;
	
	echo "<div class='contenedorfoto'>";

	echo "	<div class='imageSlider'>";
	echo "		<ul>";



    $xpecas = $tDocs->getDocumentsByRef($peca, "peca");
    if (!empty($xpecas->attachListInfo)) {
		$a = 1;
		foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
		    $fotoPeca = $vFoto["link"];
		echo "<li>
				<a href='$fotoPeca' rel='lightbox[produtos]' title='$descricao'>
				<img src='$fotoPeca' width='100' alt='' />
				</a>
			</li>";
		}
    } else {

		$sql = "SELECT  peca_item_foto,caminho,caminho_thumb,descricao
				FROM tbl_peca_item_foto
				WHERE peca = $peca";
		$res = pg_exec ($con,$sql) ;
		$num_fotos = pg_num_rows($res);
		if ($num_fotos > 0){
			for ($i=0; $i<$num_fotos; $i++){
				$caminho        = trim(pg_result($res,$i,caminho));
				$caminho_thum   = trim(pg_result($res,$i,caminho_thumb));
				$foto_descricao = trim(pg_result($res,$i,descricao));
				$foto_id        = trim(pg_result($res,$i,peca_item_foto));

				$caminho      = str_replace("/www/assist/www/".$caminho_dir."/",'',$caminho);
				$caminho_thum = str_replace("/www/assist/www/".$caminho_dir."/",'',$caminho_thum);

				$contador++;
				?>
				<li>
				<a href="<?echo $caminho_dir."/".$caminho; ?>" rel="lightbox[produtos]" title="<?=$descricao;?>">
				<img src="<?echo $caminho_dir."/".$caminho_thum; ?>" width="100" alt="" />
				</a>
				</li>

				<?
			}
		}
	}
	echo "		</ul>";
	echo "	</div>";
	echo "	</div>";
	if ($num_fotos > 1){
	echo "	<font size='-4' color='#999999'>clique na foto para visualizar</font>
		<a href='#' class='prev' style='font-size:10px;color:#0033CC'>Anterior</a>
		<a href='#' class='next' style='font-size:10px;color:#0033CC'>Próxima</a>";
	}
}
echo "</td>";
// trabalhando HD 3780 - GUSTAVO
echo "<td valign='middle' align='left' class='Conteudo' width='450' >
	<input type='hidden' name='cod_produto' value='$cod_produto'>
	<input type='hidden' name='valor'       value='$preco'>
	<input type='hidden' name='ipi'         value='$ipi'>
	<input type='hidden' name='descricao'   value='$descricao'>
	<input type='hidden' name='linha'       value='$linha'>
	<input type='hidden' name='qtde_maxi'   value='$qtde_max_site'>
	<input type='hidden' name='qtde_disp'   value='$qtde_disponivel_site'>

	<font size='3' color='#FF0000'><B>Valor: R$ $preco_formatado</B></font><BR>
	Qtde: ";
	if($multiplo_site > 1){
		if (strlen($qtde_max_site)==0){
			$qtde_max_site = 500;
		}
		echo "<select name='qtde' class='Caixa' >";
		for($i=1;$i<=20;$i++){
			$aux = $i * $multiplo_site;
			if(($aux>=$qtde_minima_site) AND (strlen($qtde_max_site)>0 AND $aux<=$qtde_max_site))echo "<option value='$aux'>$aux</option>";
		}
		echo "</select>";
	}else{
		if (strlen($qtde_max_site)==0){
			$qtde_max_site = 500;
		}
		echo "<input type='text' size='3' maxlength='3' name='qtde' value='";
		if(strlen($qtde_minima_site)==0){ $qtde_minima_site= "1"; echo $qtde_minima_site;}
		else                             $qtde_minima_site= "$qtde_minima_site";
		echo "' class='Caixa' ";
		if (strlen($qtde_disponivel_site)>0){
			echo "onblur='javascript:
			checarNumero(this);
			if (this.value < $qtde_minima_site || this.value==\"\" ) {
				alert(\"Quantidade abaixo da mínima permitida. A quantidade m?nima para compra desta peça é de $qtde_minima_site!\");
				this.value=\"$qtde_minima_site\";
			}
			if (this.value > $qtde_max_site || this.value==\"\" ) {
				alert(\"Quantidade acima da máxima permitida. A quantidade máxima para compra desta peça é de $qtde_max_site!\");
				this.value=\"$qtde_minima_site\";
			}'";
		}else{
			echo "onblur='javascript: checarNumero(this);'";
		}
		echo ">";
	}
	echo "&nbsp;&nbsp;&nbsp;<input type='submit' name='btn_comprar' value='Comprar' class='botao'>";
echo "</td>";
echo "</tr>";
echo "<tr>";
	echo "<td colspan='2' align='top' class='Conteudo'>
	<br>";
	if(strlen($informacoes)>0) echo "<B>&nbsp;Informações</b><br><br>&nbsp;".nl2br($informacoes);
	echo "<BR><BR>";
	echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td bgcolor='#e6eef7'></td>";
echo "<td align='right' bgcolor='#e6eef7' ><a href='javascript:history.back()'>Voltar</a></td>";
echo "</tr>";
echo "</table>";
echo "</form>";

include "login_unico_rodape.php";
?>