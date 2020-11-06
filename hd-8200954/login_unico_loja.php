<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include_once "class/tdocs.class.php";
$tDocs = new TDocs($con, $login_fabrica);

$ajax = $_GET['ajax'];
if(strlen($ajax)>0){
	$peca = $_GET['peca'];
	$idpeca = $_GET['idpeca'];

    $xpecas = $tDocs->getDocumentsByRef($idpeca, "peca");
    if (!empty($xpecas->attachListInfo)) {

		$a = 1;
		foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
		    $fotoPeca = $vFoto["link"];
		    if ($a == 1){break;}
		}
		echo"<center>
				<img src='$fotoPeca' border='0'>
				</center>";
    } else {

		if ($dh = opendir('imagens_pecas/media/')) {
			echo"<center>
				<img src='imagens_pecas/media/$peca' border='0'>
				</center>";
		}
	}
	exit;
}

$aba = 2;
$title="Loja Virtual Telecontrol!";
include "login_unico_cabecalho.php";

?>
<script type="text/javascript" src="admin/js/jquery-latest.pack.js"></script>

<script src="admin/js/jquery.MultiFile.js" type="text/javascript" language="javascript"></script>
<script src="admin/js/jquery.MetaData.js" type="text/javascript" language="javascript"></script>
<script type="text/javascript" src="admin/js/thickbox.js"></script>
<link rel="stylesheet" href="admin/js/thickbox.css" type="text/css" media="screen" />

<script language='javascript'>
function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseInt(num);
	if (campo.value=='NaN') {
		campo.value='';
	}
}
</script>

<?

$cod_produto = $_GET['cod_produto'];

$sql = "SELECT  random()                         ,
		tbl_peca.peca                    ,
		tbl_peca.referencia              ,
		tbl_peca.ipi                     ,
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
	WHERE fabrica = 10
	AND   promocao_site IS TRUE
	order by random()";

$res = pg_exec ($con,$sql);

if(pg_numrows($res)>0){
	echo "<table width='700' border='0' align='center' cellpadding='0' cellspacing='0' style=' border:#B5CDE8 1px solid;  bordercolor='#d2e4fc'>";
	
	echo "<tr>";
	echo "<td align='left'  colspan='2' bgcolor='#FFFFFF' >";
		echo "<TABLE><TR>";
	for ( $i = 0 ; $i < pg_numrows($res) ; $i++ ) {
		$peca                  = trim(pg_result ($res,$i,peca));
		$referencia            = trim(pg_result ($res,$i,referencia));
		$ipi                   = trim(pg_result ($res,$i,ipi));
		$produto_nome          = trim(pg_result ($res,$i,descricao));
		$estoque               = trim(pg_result ($res,$i,estoque));
		$garantia_diferenciada = trim(pg_result ($res,$i,garantia_diferenciada));
		$informacoes           = trim(pg_result ($res,$i,informacoes));
		$linha                 = trim(pg_result ($res,$i,linha_peca));
		$multiplo_site         = trim(pg_result ($res,$i,multiplo_site));
		$qtde_minima_site      = trim(pg_result ($res,$i,qtde_minima_site));
		$qtde_max_site         = trim(pg_result ($res,$i,qtde_max_site));
		$qtde_disponivel_site  = trim(pg_result ($res,$i,qtde_disponivel_site));
		$preco                 = trim(pg_result ($res,$i,preco));
	
		$preco_formatado = number_format($preco,2,'.',',');

		$parcelas = ($preco/2);
		$parcelas = number_format($parcelas, 2, ',', '');
		$preco_formatado    = number_format($preco, 2, ',', '');

		echo "<td align='center'>";
	    $xpecas = $tDocs->getDocumentsByRef($peca, "peca");
	    if (!empty($xpecas->attachListInfo)) {

			$a = 1;
			foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
			    $fotoPeca = $vFoto["link"];
			    if ($a == 1){break;}
			}
			echo "<div class='contenedorfoto'>
			         <a href='login_unico_loja_detalhe.php?cod_produto=<?=$peca?>'>
			         <img src='$fotoPeca' border='0'>
			         <input type='hidden' name='peca_imagem' value='$fotoPeca'>
			        </a>
			      </div>";
	    } else {
			if ($dh = opendir('imagens_pecas/pequena/')) {
				$contador=0;
				while (false !== ($filename = readdir($dh))) {
					if($contador == 1) break;
					if (strpos($filename,$referencia) !== false){
						$contador++;
						//$peca_referencia = ntval($peca_referencia);
						$po = strlen($referencia);
						if(substr($filename, 0,$po)==$referencia){?>
							<div class='contenedorfoto'><a href='login_unico_loja_detalhe.php?cod_produto=<?=$peca?>'>
							<img src='imagens_pecas/pequena/<?echo $filename; ?>' border='0'>
							<input type='hidden' name='peca_imagem' value='<?echo $filename; ?>'></a>
			<?			}
					}
				}
			}
		}

		
		echo "<br><font size='1' color='#333333' ><b>$produto_nome</b></FONT></a><br>";
		echo "<font size='3' color='#FF0000'><B>R$ $preco_formatado</B></font><BR>";
		if($produto_frete=='t') 
			echo "<br><FONT COLOR='#6600FF'>FRETE GRÁTIS</font>";
		if($produto_parcela_sem_juros>0) 
			echo "<br><span class='Produto_detalhe'>ou até <span class='Produto_parcela'>$produto_parcela_sem_juros X S/ JUROS</span> de <span class='Produto_parcela'>R$$produto_parcela_valor</span> no cartão</span>";
		echo "</td>";
		$coluna++;
		if ($coluna == 3) {
			echo "</tr><tr><td></td></tr>";
			echo "<tr>";
			$coluna = 0;
		}

	}
		echo "</table>";
	echo "</td>";
	echo "<tr>";
	echo "<td bgcolor='#e6eef7'></td>";
	echo "<td align='right' bgcolor='#e6eef7' ><a href='javascript:history.back()'>Voltar</a></td>";
	echo "</tr>";
	echo "</table>";
}

include "login_unico_rodape.php";
?>