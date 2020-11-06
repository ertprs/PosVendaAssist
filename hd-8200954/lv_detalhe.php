<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

include 'funcoes.php';

$servidor = $_SERVER[HTTP_HOST];
if($servidor == 'www.telecontrol.com.br' || $servidor == 'ww2.telecontrol.com.br' || $servidor == 'posvenda.telecontrol.com.br') {
    #include '/var/www/telecontrol/www/loja/bootstrap.php';
    #uses('PecaFoto');
    $caminho_media      = "/www/assist/www/$caminho_dir/media/";
    $caminho_pequeno    = "/www/assist/www/$caminho_dir/pequena/";
}else{
    include '../LojaVirtual/bootstrap.php';
    // uses('PecaFoto');
    $caminho_servidor = str_replace('/admin','',$path); 
    $caminho_media    = $caminho_servidor."/".$caminho_dir."/media/";   
    $caminho_pequeno  = $caminho_servidor."/".$caminho_dir."/pequena/";

}

$icms = "0.82";
$caminho_dir = "imagens_pecas";

$produto_acabado = $_GET['produto_acabado'];

if (strlen($cook_fabrica)==0 AND strlen($cook_login_unico)>0){
	include 'login_unico_autentica_usuario.php';
	$login_fabrica = 10;
}else{
	include 'autentica_usuario.php';
}

include_once "class/tdocs.class.php";
$tDocs = new TDocs($con, $login_fabrica);

$ajax = $_GET['ajax'];
if(strlen($ajax)>0){
	$imagem = $_GET['imagem'];
	$idpeca = $_GET['idpeca'];
	if($login_fabrica <> 3 AND $login_fabrica <> 10) $caminho_dir = "imagens_pecas/$login_fabrica/media";

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
		if ($dh = opendir($diretorio)) {
			echo "<center>
				<img src='$caminho_dir/$imagem' border='0'>
				</center>";
		}
	}
	exit;
}

$ajax = $_GET['ajax'];
if(strlen($ajax)>0){
	$peca = $_GET['peca'];
	$idpeca = $_GET['idpeca'];
	if($login_fabrica == 3 OR $login_fabrica == 10) $diretorio = "imagens_pecas/pequena/";
	else                                            $diretorio = "imagens_pecas/$login_fabrica/pequena/";
	
	$xpecas = $tDocs->getDocumentsByRef($idpeca, "peca");
    if (!empty($xpecas->attachListInfo)) {

		$a = 1;
		foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
		    $fotoPeca = $vFoto["link"];
		    if ($a == 1){break;}
		}
		echo "<center><img src='$fotoPeca' border='0'></center>";
    } else {
	if ($dh = opendir($diretorio)) {
		echo "<center><img src='$diretorio$peca' border='0'></center>";
	}
	}
	
	exit;
}

$layout_menu = 'pedido';
$title="Detalhes do produto!";
if (strlen($cook_fabrica)==0 AND strlen($cook_login_unico)>0){
	include "login_unico_cabecalho.php";
}else{
	include "cabecalho.php";
}
if($login_fabrica==1){
	$sqlx ="SELECT   tbl_tipo_posto.acrescimo_tabela_base        ,
					tbl_tipo_posto.acrescimo_tabela_base_venda  ,
					tbl_condicao.acrescimo_financeiro           ,
					((100 - tbl_icms.indice) / 100) AS icms     ,
					tbl_posto_fabrica.pedido_em_garantia
			FROM    tbl_posto
			JOIN    tbl_posto_fabrica    on tbl_posto_fabrica.posto   = tbl_posto.posto
										and tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN    tbl_fabrica          on tbl_fabrica.fabrica       = tbl_posto_fabrica.fabrica
			JOIN    tbl_tipo_posto       on tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
			JOIN    tbl_condicao         on tbl_condicao.fabrica      = $login_fabrica
										and tbl_condicao.condicao     = 50
			JOIN    tbl_icms             on tbl_icms.estado_destino   = tbl_posto.estado
			WHERE   tbl_fabrica.estado        = tbl_icms.estado_origem
			AND     tbl_posto_fabrica.posto   = $login_posto
			AND     tbl_posto_fabrica.fabrica = $login_fabrica;";

	$resx = @pg_exec($con,$sqlx);

	if (pg_numrows($resx) > 0) {
		$picms                        = pg_result($resx, 0, icms);
		$acrescimo_tabela_base       = pg_result($resx, 0, acrescimo_tabela_base);
		$acrescimo_tabela_base_venda = pg_result($resx, 0, acrescimo_tabela_base_venda);
		$acrescimo_financeiro        = pg_result($resx, 0, acrescimo_financeiro);
		$pedido_em_garantia          = pg_result($resx, 0, pedido_em_garantia);
	}else{
		echo "Problemas com vínculo entre posto e fabricante.";
		exit;
	}

}

function ReDimImg($w, $h, $limite) {
	if(($w<($limite*0.7) and $h<($limite*0.7)) or
		($w>$limite or $h>$limite)) {
		return "WIDTH='".$w."' HEIGHT='".$h."'";
	} else {
		if ($w > $h) {
			$h = intval(($limite / $w) * $h);
			$w = $limite;
		}else{
			$w = intval(($limite / $h) * $w);
			$h = $limite;
		}
	}
	return "WIDTH='".$w."' HEIGHT='".$h."'";
}
?>

<script type="text/javascript" src="plugins/jqueryUI/js/jquery-1.7.1.min.js"></script>
<script type="text/javascript" src="js/niftycube.js"></script>
<script type="text/javascript" src="js/niftyLayout.js"></script>

<script language='javascript'>
	function checarNumero(campo){
		var num = campo.value.replace(",",".");
		campo.value = parseInt(num);
		if (campo.value=='NaN') {
			campo.value='';
		}
	}
</script>

<!--<script type="text/javascript" src="js/niftyLayout.js"></script>-->

<script language='javascript'>
	NiftyLoad=function(){
		Nifty("DIV#infomenu");
		Nifty("DIV.mlgMaisInfo","top")
	}
</script>


<script language="JavaScript">
	function abrir(URL) {
		var width = 480;
		var height = 500;
		var left = 90;
		var top = 90;

		window.open(URL,'janela', 'width='+width+', height='+height+', top='+top+', left='+left+', scrollbars=yes, status=no, toolbar=no, location=no, directories=no, menubar=no, resizable=no, fullscreen=no');
	}
</script>

<style type="text/css">
.descricao_produto ul {
	list-style:disc;
	margin: 1em 40px 1em 40px;
}
DIV#infomenu {
	margin-top:6px;
	margin-bottom:6px;
	padding-left: 15px;
	padding-top:6px;
	padding-bottom:6px;
	background-image: url('imagens/barra_dg_azul_tc.jpg');
	background-repeat: repeat-x;
	color:white;
}
.preco {
	color: #f41c1c;
	font-size: 0.9em;
}
.descrproduto {
	font-family: arial, freesans, garuda, helvetica, verdana, sans-serif;
	padding: 5px;
	font-size: 11px;
	min-width: 760px;
}
DIV.mlgMaisInfo {
	background-image: url('imagens/barra_dg_cinza_45.png');
	background-repeat: repeat-x;
	color: white;
	font-size: 14pt;
	font-weight: bold;
	height: 45px;
	padding-left: 10px;
	padding-top: 5px;
}

</style>

<?

$cod_produto	= $_GET['cod_produto'];
$peca			= $cod_produto;

	$sql = "SELECT
				tbl_peca.peca            ,
				referencia              ,
				tbl_peca.ipi            ,
				descricao               ,
				estoque                 ,
				garantia_diferenciada   ,
				informacoes             ,
				linha_peca              ,
				multiplo_site           ,
				qtde_minima_site        ,
				qtde_max_site           ,
				qtde_disponivel_site    ,
				preco_anterior          ,
				liquidacao          ,
				frete_gratis
			FROM tbl_peca  
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
		$informacoes				= nl2br($informacoes);
		$linha						= trim(pg_result ($res,0,linha_peca));
		$multiplo_site 				= trim(pg_result ($res,0,multiplo_site));
		$qtde_minima_site			= trim(pg_result ($res,0,qtde_minima_site));
		$qtde_max_site				= trim(pg_result ($res,0,qtde_max_site));
		$qtde_disponivel_site		= trim(pg_result ($res,0,qtde_disponivel_site));
		$preco_anterior				= trim(pg_result ($res,0,preco_anterior)); #HD 13429
		$frete_gratis				= trim(pg_result ($res,0,frete_gratis)); #HD 40674
		$liquidacao					= trim(pg_result ($res,0,liquidacao)); 

		$sql3 = "SELECT preco
				FROM tbl_tabela_item
				WHERE peca  = $peca";
			if($produto_acabado=="t"){
				# HD 110817
				$sql3 .= " AND tabela = 265;";
			}elseif($liquidacao == "t"){
				$sql3 .= " AND tabela IN(
                            SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa is true AND descricao = 'LOJA VIRTUAL' 
                        ) ORDER BY tabela_item DESC LIMIT 1 ";
			}else{
				$sql3 .= "
				AND   tabela IN (
					SELECT tbl_tabela.tabela
					FROM tbl_posto_linha
					JOIN tbl_tabela       USING(tabela)
					JOIN tbl_posto        USING(posto)
					JOIN tbl_linha        USING(linha)
					WHERE tbl_posto.posto       = $login_posto
					AND   tbl_linha.fabrica     = $login_fabrica
					AND   tbl_posto_linha.linha IN (
						SELECT DISTINCT tbl_produto.linha
						FROM tbl_produto
						JOIN tbl_lista_basica USING(produto)
						JOIN tbl_peca USING(peca)
						WHERE peca = $peca
					)
				)";
			}
		$res3 = pg_exec ($con,$sql3);

		if(pg_numrows($res3) > 0) {
	        $preco = trim(pg_result ($res3,0,preco));                   
            /* $preco = number_format($preco, 2, ',', '');
            $preco_hidden = str_replace(',', '.', $preco); */
		}
		if($login_fabrica<>3 && $login_fabrica <> 85){
			$sql2 = "SELECT (preco / $icms) as preco
					FROM tbl_tabela_item
					WHERE peca  = $peca
					AND   tabela IN (
						SELECT tbl_tabela.tabela
						FROM tbl_tabela
						WHERE fabrica = $login_fabrica
					)";
			$res2 = pg_exec ($con,$sql2);
			if(pg_numrows($res2)==0) {
				$preco = 0;
			}else{
				$preco = trim(pg_result ($res2,0,preco));
			}
		}
		if($login_fabrica==1){
			$sql2 = "SELECT TRUNC((preco / $picms)::NUMERIC,2) as preco
					FROM tbl_tabela_item
					WHERE peca  = $peca
					AND   tabela IN (
						SELECT tbl_tabela.tabela
						FROM tbl_tabela
						WHERE fabrica = $login_fabrica
					)";
			$res2 = pg_exec ($con,$sql2);
			if(pg_numrows($res2)==0) {
				$preco = 0;
			}else{
				$preco = trim(pg_result ($res2,0,preco));
			}
		}

		if (strlen($login_unico)>0 AND $login_fabrica==10 ){
			$sql2 = "SELECT preco
					FROM tbl_tabela_item
					WHERE peca  = $peca
					AND   tabela IN (
						SELECT tbl_tabela.tabela
						FROM tbl_tabela
						WHERE fabrica = $login_fabrica
					)";
			$res2 = pg_exec ($con,$sql2);
			if(pg_numrows($res2)==0) {
				$preco = 0;
			}else{
				$preco = trim(pg_result ($res2,0,preco));
			}
		}

		$preco_formatado = number_format($preco,2,'.',',');

		$parcelas = ($preco/2);
		$parcelas = number_format($parcelas, 2, ',', '');
		$preco_formatado    = number_format($preco, 2, ',', '');

		$sql4 = "SELECT DISTINCT tbl_linha.nome as linha_descricao,
						tbl_linha.linha ,
						tbl_familia.descricao as familia_descricao,
						tbl_familia.familia
					FROM tbl_peca
					JOIN tbl_lista_basica on tbl_peca.peca = tbl_lista_basica.peca
					JOIN tbl_produto on tbl_lista_basica.produto = tbl_produto.produto
					JOIN tbl_linha on tbl_produto.linha = tbl_linha.linha
					JOIN tbl_familia on tbl_familia.familia = tbl_produto.familia
					WHERE tbl_peca.peca = $peca
					AND tbl_peca.fabrica = $login_fabrica
					LIMIT 1";
		if($login_fabrica==10 OR $login_fabrica == 1){
			$sql4 = "SELECT tbl_linha.nome        AS linha_descricao  ,
							tbl_linha.linha                           ,
							tbl_familia.descricao AS familia_descricao,
							tbl_familia.familia
					FROM      tbl_peca
					JOIN      tbl_linha   ON tbl_peca.linha_peca   = tbl_linha.linha
					LEFT JOIN tbl_familia ON tbl_peca.familia_peca = tbl_familia.familia
					WHERE tbl_peca.peca    = $peca
					AND   tbl_peca.fabrica = $login_fabrica";
		}
		$res4 = pg_exec ($con,$sql4);
		if(pg_numrows($res4)>0) {
			$linha_peca        = trim(pg_result ($res4,0,linha));
			$familia_peca      = trim(pg_result ($res4,0,familia));
			$linha_peca_desc   = ucfirst(trim(pg_result ($res4,0,linha_descricao)));
			$familia_peca_desc = ucfirst(trim(pg_result ($res4,0,familia_descricao)));
		}
		$cabeca = " <a href='lv_completa.php?categoria=$linha_peca&categoria_tipo=linha'><font color='#4A4A4A'>$linha_peca_desc</a> > </font> <a href='lv_completa.php?categoria=$familia_peca&categoria_tipo=familia'><font color='#4A4A4A'>$familia_peca_desc</font></a> > Detalhe";

	}

	//corpo do produto
	include 'lv_menu.php';
	echo "<BR>";
	echo "<form action='lv_carrinho.php?acao=adicionar' method='post' name='frmcarrinho' align='center'>\n";
	echo "<table width='98%' border='0' align='center' cellpadding='3' cellspacing='0'>\n";

	echo "<tr>\n";
	if(strlen($produto_acabado)==0){
	/* MENU LATERAL (CATEGORIA) */
	echo "<td width='180' valign='top'>\n";
	include "lv_menu_lateral.php";
	echo "</td>\n";
	}

	echo "<td align='left' valign='top' class='Conteudo'>\n";
	echo "<table width='95%' border='0' align='center' cellpadding='5' cellspacing='5' style = 'font-size:11px'>\n";

	echo "<tr>\n";
	echo "<td colspan='2'>\n";
	echo " $cabeca";
	echo "</td>\n";
	echo "<tr>\n";
	echo "<td colspan='2' height='1px' bgcolor='#4A4A4A'>\n";
	echo "</td>\n";
	echo "<tr>\n";
	echo "<td width='150' align='center' valign='top'><BR>\n";

	if($frete_gratis=='t'){
		echo "<div><INPUT TYPE='image' SRC='imagens/fretegratis.jpeg'>&nbsp;&nbsp;</div>";
	}

	$sql = "SELECT  peca_item_foto      ,
					caminho             ,
					caminho_thumb       ,
					descricao           ,
					(select count(*) from tbl_peca_item_foto where peca = $peca) AS qtde_fotos
			FROM tbl_peca_item_foto
			WHERE peca = $peca
			group by peca_item_foto ,
			caminho                 ,
			caminho_thumb           ,
			descricao               ,
			ordem
			order by ordem LIMIT 1";
	$res = pg_exec ($con,$sql) ;
	$num_fotos = pg_num_rows($res);

	echo "<div class=''>\n";
	$conta_foto = 0;
	if ($num_fotos>0){
		echo "<div class='imageSlider'>";
		echo "<ul>";
		for ($i=0; $i<$num_fotos; $i++){
			$caminho        = trim(pg_result($res,$i,caminho));
			$caminho_thum   = trim(pg_result($res,$i,caminho_thumb));
			$foto_descricao = trim(pg_result($res,$i,descricao));
			$foto_id        = trim(pg_result($res,$i,peca_item_foto));
			$qtde_fotos     = trim(pg_result($res,$i,qtde_fotos));

			$caminho      = str_replace("/www/assist/www/".$caminho_dir."/",'',$caminho);
			$caminho_thum = str_replace("/www/assist/www/".$caminho_dir."/",'',$caminho_thum);			
			exit;
			//Manolo, 24/11/08
			$mlg_infoimagem = getimagesize($caminho_dir."/".$caminho);
			if ($mlg_infoimagem[0] < 150) {
				$mlg_medidasimg = $mlg_infoimagem[3];
				}else{
				$mlg_medidasimg = "WIDTH=150";
			}
			//<a href="/www/assist/www/imagens_pecas/media/TC0001-55.b.jpg" rel="lightbox[produtos]" title="?=$descricao;?"></a>

			//hd 14364 29/2/2008
			//Manolo, 24/11/08: Ronaldo pediu para mostrar a foto e não apenas o thumb...
			$caminho_popup = $caminho_dir."/".$caminho;			
			?>
			<li>				
			<a href="javascript:abrir('lv_detalhe_popup.php?caminho_final=<?echo $caminho_dir."/".$caminho; ?>&peca=<? echo $peca; ?>&caminho=<? echo $caminho_popup; ?>&descricao=<? echo $descricao; ?>&referencia=<? echo $referencia; ?><?php echo "&login_fabrica=$login_fabrica";?>);" title="<?=$descricao;?>" style='text-decoration: none;' >
			<img src="<? echo $caminho_popup ?>" <? echo  $mlg_medidasimg; ?> alt="" />

			<? if($qtde_fotos>1){
				echo "<BR>";
				echo "<font size='-4' color='#999999'> < Veja mais fotos > </font>";
			} ?>

			</a>
			</li>
			<?
			$conta_foto++;
		}
		echo "</ul>";
		echo "</div>";
		if ($num_fotos > 1){
			$tem_foto = "SIM";
			#echo "<a href='#' class='prev' style='font-size:10px;color:#0033CC'>Anterior</a><a href='#' class='next' style='font-size:10px;color:#0033CC'>Próxima</a>";
		}
	#echo "<font size='-4' color='#999999'>Foto Ilustrativa</font>";
	}else{


	$xpecas = $tDocs->getDocumentsByRef($peca, "peca");
    if (!empty($xpecas->attachListInfo)) {

		$a = 1;
		foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
		    $fotoPeca = $vFoto["link"];
		    if ($a == 1){break;}
		}
							echo "<a href=\"javascript:abrir('lv_detalhe_popup.php?caminho_final=$fotoPeca&peca=$peca&caminho=$fotoPeca&descricao=$descricao&referencia=$referencia&login_fabrica=$login_fabrica');\" title=''><img src='$fotoPeca'/></a>";
							echo "<input type='hidden' name='peca_imagem' value='$fotoPeca'><br>";
    } else {

		if($login_fabrica == 3 OR $login_fabrica == 10){
			$diretorio = "imagens_pecas//";	
		}else{
			$diretorio = "imagens_pecas/$login_fabrica/";
		}   

		if($login_fabrica == 85){			

			$diretorio = "imagens_pecas/$login_fabrica/media/";                
            $ret = scandir($diretorio);
            $fotoG = "";
            foreach ($ret as $value) {
                if($value != "." && $value != ".."){
                    
                    $pecaImagem = strstr($value, '.',true);
                    if($pecaImagem != false){
                        if($peca == $pecaImagem){
                            $fotoG = "imagens_pecas/$login_fabrica/media/".$value;                                    
                        }                            
                    }
                }
            }
            if($fotoG == ""){
                $fotoG = "imagens_pecas/semimagem.jpg";                        
            }

			// $oPecaFoto = PecaFoto::find($peca);


			// $fotoG = "";
			// $fotosP = array();

			
			// if ( ! empty($peca) ) {						
			// 	$aIdxFotos = array('p','g','1','2','3','4');
			// 	$oPecaFoto = PecaFoto::find($peca);			

			// 	foreach ($aIdxFotos as $_i) {					
			// 		if($oPecaFoto->temFoto($_i)){						
			// 			$aFoto[] = $_i;
			// 			if($servidor == 'www.telecontrol.com.br' || $servidor == 'ww2.telecontrol.com.br' || $servidor == 'posvenda.telecontrol.com.br') {
			// 				$path  = $oPecaFoto->getFoto($_i);
			// 				$url   = PecaFoto::corrigirCaminhoParaUrl($path);
			// 				if($_i == 'g'){
			// 					$fotoG = $url;
			// 				}else{
			// 					$fotosP[] = $url;
			// 				}
			// 			}else{	
			// 				$path  = $oPecaFoto->getFoto($_i);							
			// 				$exp = explode('/', $path);
			// 				$imagem = $exp[count($exp)-1];
			// 				$url = "http://192.168.0.199/~anderson/LojaVirtual/media/produtos/$imagem";								
			// 				if($_i == 'g'){
			// 					$fotoG = $url;
			// 				}else{
			// 					$fotosP[] = $url;
			// 				}
							
			// 			}								
			// 		}
			// 	}			
			// }

			
			
			if($fotoG != ""){
				echo "<a href='javascript:openPopUp(\"".$fotoG."\")' id='imagemGrandeLink' target='_parent'><img src='".$fotoG."' title='imagem da peça' alt='imagem da peça' width='300' id='imagemGrande'/></a>";
			}else{
				echo "<img src='imagens_pecas/semimagem.jpg' id='imagemGrande' width='300' border='0'>\n";
				echo "<input type='hidden' name='peca_imagem' value='$filename'>";
			}

			// if(count($fotosP) > 0){
			// 	echo "<div>";
			// 	for($i=0;$i<count($fotosP);$i++){
			// 		echo "
			// 		<div style='float:left;margin: 2px 2px 0 0;cursor:pointer;border:1px solid #333;' >
			// 			<img src='".$fotosP[$i]."' width='50'  onclick='alteraImagem(this)'/>
			// 		</div>";
			// 	}
			// 	echo "</div>";
			// }
		}else{
			if ($dh = opendir("$diretorio/pequena")) {						
 				$contador=0;			
				while (false !== ($filename = readdir($dh))) {				
					$Xreferencia = str_replace(" ", "_",$referencia);					
					if (strpos($filename,$Xreferencia) !== false){
						$contador++;
						//$peca_referencia = ntval($peca_referencia);
						$po = strlen($Xreferencia);
						if(substr($filename, 0,$po)==$Xreferencia){
							$file_final = $filename;
							echo "<a href=\"javascript:abrir('lv_detalhe_popup.php?caminho_final=$diretorio/media/$file_final&peca=$peca&caminho=$diretorio/media/$file_final&descricao=$descricao&referencia=$referencia&login_fabrica=$login_fabrica');\" title=''><img src='$diretorio/pequena/$file_final'/></a>";
							echo "<input type='hidden' name='peca_imagem' value='$file_final'><br>";
							$tem_foto = "SIM";
						}
					}
				$conta_foto++;
				}
				if($tem_foto=='SIM'){
					echo "<font size='-3' color='#999999'>Clique na imagem para ampliar</font>";
				}
				//$oPecaFoto = PecaFoto::find('928736');
			}	
		}		
		}		
	}


	if($tem_foto<>'SIM' AND $conta_foto >= 1){		

		echo "<img src='imagens_pecas/semimagem.jpg' width='100' border='0'>\n";
		echo "<input type='hidden' name='peca_imagem' value='$filename'>";
	}
	echo "</div>\n";

	/* Samuel tirou o recomendar produto porque a loja é interna...e seriam poucos os casos de recomendação entre os postos autorizados.
	echo "<div width='100%'>
		<TABLE border='0' width='100%' >
			<TR>
				<TD>&nbsp;</TD>
			</TR>
			<TR>
				<TD valign='middle'>
				<INPUT TYPE='image' SRC='imagens/carta.jpg'>
				</TD>
				<TD valign='middle'>
				<A HREF=javascript:abrir('email_recomenda_produto.php?cod_produto=$cod_produto') style='font-size:11px; color: #385289; text-decoration: none; font-family: arial; font-weight: bold;'>Recomendar Produto</A>
				</TD>
			</TR>
		</TABLE>
	</div>";
	*/
echo "</td>\n";
echo "<td valign='top' width='80%'><BR>\n";
	echo "<input type='hidden' name='cod_produto' value='$cod_produto'>\n";
	
	if($login_fabrica==3 AND $produto_acabado=="t" AND strlen($preco_anterior)>0 AND $preco_anterior>0){
		$preco = $preco_anterior;
	}else{
		if($login_fabrica == 85){
			$preco = str_replace(',','.',$preco_formatado);
		}
	}

	echo "<input type='hidden' name='valor'       value='$preco'>\n";
	echo "<input type='hidden' name='ipi'         value='$ipi'>\n";
	echo "<input type='hidden' name='descricao'   value='$descricao'>\n";
	echo "<input type='hidden' name='linha'       value='$linha'>\n";
	echo "<input type='hidden' name='qtde_maxi'   value='$qtde_max_site'>\n";
	echo "<input type='hidden' name='qtde_disp'   value='$qtde_disponivel_site'>\n";

	echo "<DIV id='infomenu'>\n";
	echo "<P style='font-size:12pt;font-weight:bold;'>\n";
	echo "$descricao<BR>";
	echo "<SPAN style='font-size:8pt;color:#EEE;'>Referência: $referencia</SPAN>\n";
	echo "</P>\n</DIV>\n";
if($preco_formatado > 0 OR $preco_anterior > 0){
	echo "<font color='#777777'>";
	#echo "Qtde Disponível: <b>$qtde_disponivel_site</b><BR>\n"; HD 40674
	if(strlen($multiplo_site)>0) echo " Qtde Múltipla: $multiplo_site <BR>\n";
	if(strlen($qtde_max_site)>0) echo " Qtde Máxima: $qtde_max_site <BR>\n";

	

	if(strlen($preco_anterior) > 0 AND $preco_anterior > 0 && strlen($preco_formatado) > 0) {
		$preco_anterior = number_format($preco_anterior, 2, ',', '');
		$preco_anterior = str_replace(".",",",$preco_anterior);

		if($login_fabrica == 85){

			if(strlen($preco_anterior) > 0){
				echo "<span style='font-size: 1em; font-weight: bold;'> De: R$ $preco_anterior </span><BR>\n";
			}

		}else{
			echo "<span style='font-size: 1em; font-weight: bold;'> De: R$ $preco_anterior </span><BR>\n";
		}
	}	
	
	if($login_fabrica == 85){		
		if($preco_formatado > 0 && trim($preco_formatado) != trim($preco_anterior)){
			echo "Por: </font>";
			echo "<SPAN STYLE='color:#BB0000;font-size:12pt;font-weight:bold;'>R$ $preco_formatado</span><BR>\n";
		}	
	}else{
		echo "Por: </font>";
		if($preco_anterior <= 0){
			echo "<SPAN STYLE='color:#BB0000;font-size:12pt;font-weight:bold;'>R$ $preco_anterior</span><BR>\n";
		}else{
			echo "<SPAN STYLE='color:#BB0000;font-size:12pt;font-weight:bold;'>R$ $preco_formatado</span><BR>\n";
		}	
	}
	
	if($ipi>0) echo "<font color='#777777' size='1'>* Valor + IPI: $ipi %</font><BR>\n";
//  Manolo: alterada string da regra de forma de pagamento
	if(strlen($regra_loja_virtual)>0) echo "<font color='#777777'>" . str_replace('<BR>',' ', $regra_loja_virtual) . "</font><br><br>";

	if($login_fabrica == 85){
		if($qtde_disponivel_site >0){
			echo "Qtde: ";	
		}	
	}

	if (strlen($qtde_max_site)==0){
			$qtde_max_site = 500;
	}

	if($multiplo_site > 1){
		if($login_fabrica == 85){
			if($qtde_disponivel_site >0){
				echo "<select name='qtde' class='Caixa' >\n";
				for($i=1;$i<=20;$i++){
					$aux = $i * $multiplo_site;
					if(($aux>=$qtde_minima_site) AND (strlen($qtde_max_site)>0 AND $aux<=$qtde_max_site))echo "<option value='$aux'>$aux</option>\n";
				}
				echo "</select>\n";
			}
		}else{
			echo "<select name='qtde' class='Caixa' >\n";
			for($i=1;$i<=20;$i++){
				$aux = $i * $multiplo_site;
				if(($aux>=$qtde_minima_site) AND (strlen($qtde_max_site)>0 AND $aux<=$qtde_max_site))echo "<option value='$aux'>$aux</option>\n";
			}
			echo "</select>\n";
		}
		
	}else{
		if($login_fabrica == 85){
			if($qtde_disponivel_site >0){
				echo "<input type='text' size='2' maxlength='3' name='qtde' value='";
				if(strlen($qtde_minima_site)==0){ $qtde_minima_site= "1"; echo $qtde_minima_site;}
				else                             $qtde_minima_site= "$qtde_minima_site";
				echo "'";
				if (strlen($qtde_disponivel_site)>0){
					echo "onblur='javascript:
					checarNumero(this);
					if (this.value < $qtde_minima_site || this.value==\"\" ) {
						alert(\"Quantidade abaixo da mínima permitida. A quantidade mínima para compra desta peça é de $qtde_minima_site!\");
						this.value=\"$qtde_minima_site\";
					}
					if (this.value > $qtde_max_site || this.value==\"\" ) {
						alert(\"Quantidade acima da máxima permitida. A quantidade máxima para compra desta peça é de $qtde_max_site!\");
						this.value=\"$qtde_minima_site\";
					}'";
				}else{
					echo "onblur='javascript: checarNumero(this);'";
				}
				echo ">\n";
			}
		}else{
			echo "<input type='text' size='2' maxlength='3' name='qtde' value='";
			if(strlen($qtde_minima_site)==0){ $qtde_minima_site= "1"; echo $qtde_minima_site;}
			else                             $qtde_minima_site= "$qtde_minima_site";
			echo "'";
			if (strlen($qtde_disponivel_site)>0){
				echo "onblur='javascript:
				checarNumero(this);
				if (this.value < $qtde_minima_site || this.value==\"\" ) {
					alert(\"Quantidade abaixo da mínima permitida. A quantidade mínima para compra desta peça é de $qtde_minima_site!\");
					this.value=\"$qtde_minima_site\";
				}
				if (this.value > $qtde_max_site || this.value==\"\" ) {
					alert(\"Quantidade acima da máxima permitida. A quantidade máxima para compra desta peça é de $qtde_max_site!\");
					this.value=\"$qtde_minima_site\";
				}'";
			}else{
				echo "onblur='javascript: checarNumero(this);'";
			}
			echo ">\n";
		}
	}

	if(($login_fabrica==3 OR $login_fabrica == 85) AND $produto_acabado=="t"){ //HD 98211
		echo "<input type='hidden' name='produto_acabado' value='t' >";
	}

	if($login_fabrica == 85){
		if($qtde_disponivel_site > 0){
			echo "&nbsp;&nbsp;&nbsp;
			<input type='hidden' name='btn_comprar' value='' class='botao' >	
			<img src='imagens/bt_comprar_pq2.gif' onclick=\"javascript: if (document.frmcarrinho.btn_comprar.value == '' ) { document.frmcarrinho.btn_comprar.value='Comprar' ; document.frmcarrinho.submit() } else { alert ('Aguarde submissão') }\" style=\"cursor: pointer;\" align='middle'>\n";		
		}else{
			echo "<h3 style='text-align:left;color: red; font-size:15px'>Produto Indisponível no momento</h3>";
		}
		
	}else{
		echo "&nbsp;&nbsp;&nbsp;
		<input type='hidden' name='btn_comprar' value='' class='botao' >	
		<img src='imagens/bt_comprar_pq2.gif' onclick=\"javascript: if (document.frmcarrinho.btn_comprar.value == '' ) { document.frmcarrinho.btn_comprar.value='Comprar' ; document.frmcarrinho.submit() } else { alert ('Aguarde submissão') }\" style=\"cursor: pointer;\" align='middle'>\n";
	}
	
}else{
	echo "<br><font size='5' color='#EF8B03'><b>Indisponível</b></font>\n";
}
echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td align='top' class='Conteudo' colspan='2'>\n";
	if(strlen($informacoes)>0){
		echo "<DIV CLASS='mlgMaisInfo'>\n+ Informações\n";
		echo "\n\t</DIV>\n";
		echo "&nbsp;$informacoes";
	}
	echo "</td>\n";
echo "</tr>\n";

if(1==1){//tem que criar o campo para o Ronaldo gravar o tempo da garantia
	$sqlG = "SELECT garantia_diferenciada
			 FROM  tbl_peca
			 WHERE peca          = $peca
			 AND   fabrica = $login_fabrica";
			 //echo $sqlG;
	$resG = pg_exec($con, $sqlG);
	if(pg_numrows($resG)>0 and strlen(trim(pg_result($resG, 0, garantia_diferenciada)))>0){
		echo "<tr>\n";
		echo "<td align='top' class='Conteudo' colspan='2'><br>\n";
		echo "<DIV CLASS='mlgMaisInfo'>\n";
		echo "+ Garantia\n</DIV>";
		$peca_garantica = pg_result($resG, 0, garantia_diferenciada);
		echo "<P  class='Conteudo' style='font-size: 12px;'><BR>Este produto tem a garantia de $peca_garantica meses!</P>";
		echo "</td>\n";
		echo "</tr>\n";
	}
}

echo "<tr>\n";
echo "<td align='top' class='Conteudo' colspan='2'><br>\n";
	echo "<DIV CLASS='mlgMaisInfo'
				STYLE='background-image: url(\"imagens/barra_dg_am_tc.png\");'>\n";
	echo "+ Produtos\n";
	echo "</DIV>";

$sql = "SELECT * FROM (
			SELECT DISTINCT tbl_peca.peca          ,
							tbl_peca.referencia    ,
							tbl_peca.descricao     ,
							tbl_peca.preco_sugerido,
							tbl_peca.ipi           ,
							tbl_peca.promocao_site ,
							tbl_peca.qtde_disponivel_site,
							tbl_peca.posicao_site,
							tbl_peca.preco_anterior,
							tbl_peca.liquidacao

			FROM tbl_peca
			JOIN tbl_lista_basica on tbl_peca.peca = tbl_lista_basica.peca 
			LEFT JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca AND tbl_tabela_item.tabela IN(
                            SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa is true AND descricao = 'LOJA VIRTUAL' 
                        )
			WHERE tbl_lista_basica.produto IN(
				SELECT tbl_lista_basica.produto
				FROM tbl_lista_basica
				WHERE tbl_lista_basica.peca = $peca
			)
			AND tbl_peca.peca NOT IN (SELECT peca FROM tbl_peca_fora_linha WHERE fabrica = $login_fabrica)
			AND tbl_peca.peca NOT IN (SELECT DISTINCT peca_de FROM tbl_depara WHERE fabrica = $login_fabrica)
			AND tbl_peca.fabrica = $login_fabrica
			AND tbl_peca.peca  <> $peca
			AND tbl_peca.promocao_site IS TRUE
			UNION
			SELECT DISTINCT tbl_peca.peca,
							tbl_peca.referencia ,
							tbl_peca.descricao ,
							tbl_peca.preco_sugerido,
							tbl_peca.ipi ,
							tbl_peca.promocao_site ,
							tbl_peca.qtde_disponivel_site,
							tbl_peca.posicao_site,
							tbl_peca.preco_anterior,
							tbl_peca.liquidacao
			FROM tbl_peca 
			JOIN tbl_linha ON tbl_linha.linha = tbl_peca.linha_peca
			JOIN tbl_familia ON tbl_familia.familia = tbl_peca.familia_peca
						LEFT JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca AND tbl_tabela_item.tabela IN(
                            SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa is true AND descricao = 'LOJA VIRTUAL' 
                        )
			WHERE 1=1
			AND tbl_linha.linha IN (SELECT linha_peca FROM tbl_peca WHERE peca = $peca)
			AND tbl_peca.peca  <> $peca
			AND tbl_peca.fabrica = $login_fabrica
			AND tbl_peca.promocao_site IS TRUE
		) AS X
		ORDER BY random() LIMIT 4;";
//		AND tbl_peca.promocao_site is true
		
	$res = pg_exec($con,$sql);
#echo nl2br($sql);
/*echo "<BR>".pg_numrows($res);*/
		if($login_fabrica == 3 OR $login_fabrica == 10){
			$diretorio = "imagens_pecas//";	
		} else{
			$diretorio = "imagens_pecas/$login_fabrica/";
		}   

		if($login_fabrica = '85'){
			$arquivos = scandir($diretorio."/pequena");							
		}
		for ($x = 0 ; $x < pg_numrows($res); $x++){
			$peca                 = trim(pg_result ($res,$x,peca));
			$referencia           = trim(pg_result ($res,$x,referencia));
			$preco_sugerido       = trim(pg_result ($res,$x,preco_sugerido));
			$ipi                  = trim(pg_result ($res,$x,ipi));
			$descricao            = trim(pg_result ($res,$x,descricao));
			$promocao_site        = trim(pg_result ($res,$x,promocao_site));
			$qtde_disponivel_site = trim(pg_result ($res,$x,qtde_disponivel_site));
			$preco_anterior       = trim(pg_result ($res,$x,preco_anterior));#HD 13429
			$liquidacao           = trim(pg_result ($res,$x,liquidacao));

			if($login_fabrica <> 10) {
				$descricao            = substr($descricao,0,25)."...";
			}

			if($login_fabrica == 85){

				$sql2 = "SELECT distinct tbl_tabela_item.preco
							FROM tbl_tabela
							JOIN tbl_tabela_item USING(tabela)
							WHERE peca  = $peca
							AND tbl_tabela.fabrica = $login_fabrica
							AND   tbl_tabela.tabela IN (
								SELECT tabela 
								FROM tbl_tabela 
								WHERE fabrica = $login_fabrica 
								AND ativa is true 
								AND descricao = 'LOJA VIRTUAL' 
							)";

			}else{

				$sql2 = "SELECT preco
						FROM tbl_tabela_item
						WHERE peca  = $peca
						AND   tabela IN (
							SELECT tbl_tabela.tabela
							FROM tbl_posto_linha
							JOIN tbl_tabela       USING(tabela)
							JOIN tbl_posto        USING(posto)
							JOIN tbl_linha        USING(linha)
							WHERE tbl_posto.posto       = $login_posto
							AND   tbl_linha.fabrica     = $login_fabrica
							AND   tbl_posto_linha.linha IN (
								SELECT DISTINCT tbl_produto.linha
								FROM tbl_produto
								JOIN tbl_lista_basica USING(produto)
								JOIN tbl_peca USING(peca)
								WHERE peca = $peca
							)
						)";

			}

			if($login_fabrica<>3 && $login_fabrica <> 85){
				$sql2 = "SELECT (preco / $icms) as preco
						FROM tbl_tabela_item
						WHERE peca  = $peca
						AND   tabela IN (
							SELECT tbl_tabela.tabela
							FROM tbl_tabela
							WHERE fabrica = $login_fabrica
						)";
			}
			if (strlen($login_unico)>0 AND $login_fabrica==10 ){
				$sql2 = "SELECT preco
						FROM tbl_tabela_item
						WHERE peca  = $peca
						AND   tabela IN (
							SELECT tbl_tabela.tabela
							FROM tbl_tabela
							WHERE fabrica = $login_fabrica
						)";
			}
			$res2 = pg_exec ($con,$sql2);
			if(pg_numrows($res2)<1) {
				$preco       = 0;
				continue;
			}else{
				$preco       = trim(pg_result ($res2,0,preco));
			}
			$preco_formatado = number_format($preco, 2, ',', '');
			$preco_formatado = str_replace(".",",",$preco_formatado);

			

			echo "<div rel='box_content' class=\"content_box\"><form action='lv_carrinho.php?acao=adicionar' method='post' name='frmcarrinho_$x'>";
			if( $preco > 0){
				echo "<a href='lv_detalhe.php?cod_produto=$peca$xproduto_acabado'>";
			}
			$saida == "";

			if ($dh = opendir("$diretorio/pequena")) {					
				$contador=2;
				$filename_final = "";	

				if($login_fabrica == 85){					
					if(count($arquivos) > 0){
						foreach ($arquivos as $imagem) {							
							if($imagem != "." && $imagem != ".."){
								$pecaImagem = strstr($imagem, '.',true);
		                        if($pecaImagem != false){		                        	
		                            if($peca == $pecaImagem){
		                                $filename_final = $imagem;                                    		                                
		                            }                            
		                        }




								// echo $imagem." - ".$peca;
								// echo strstr($imagem, $peca);
								// if(strstr($imagem, $peca)){
								// 	$filename_final = $imagem;	
								// }								
							}
						}

					}	
				}else{
					while (false !== ($filename = readdir($dh))) {
						if($contador == 0) break;
						if (strpos($filename,$referencia) !== false){
							$contador--;
							$po = strlen($referencia);
							if(substr($filename, 0,$po)==$referencia){
								$filename_final = $filename;
							}
						}
					}	
				}
				
				
			}
						
			if($login_fabrica == '85'){				
				
				if($filename_final != ""){
					$fotoP = "imagens_pecas/$login_fabrica/pequena/".$filename_final;                                    						
					echo "<center>
					  	<div style='height: ".$tamanho."px;' vertical-align: middle;'>
							<a href='lv_detalhe.php?cod_produto=$peca$produto_acabado'>
								<img  width='100' src='".$fotoP."'	border='0' style='height: 90px;'>"  ;

					echo "<input type='hidden' name='peca_imagem' value='$filename_final' >\n";
				}else{
					$fotosP = "imagens_pecas/semimagem.jpg";
					echo "<center>
					   <div style='height: ".$tamanho."px;
									'vertical-align: middle;'>
									<a href='lv_detalhe.php?cod_produto=$peca$produto_acabado'>
						<img  width='100' src='imagens_pecas/semimagem.jpg'
						border='0' style='height: 90px;'>
					  ";
				}
				if ($liquidacao == 't' AND $login_fabrica==10) {
					echo "<img src='imagens/promocao.gif'
							style='position: absolute; float: right;'>\n";
				}

				echo "</div></a>";

				if ($promocao_site == 't' OR $qtde_disponivel_site > 0) {
					echo "<font color='#FF0000'  size='1'><b>EM PROMOÇÃO</b></font><BR>\n";
				}

				if( $preco > 0 ){

					echo "<center><a href='lv_detalhe.php?cod_produto=$peca$produto_acabado' >";
					if($login_fabrica == 10) { // HD 40671
						echo "<span class='descrproduto'>$descricao</span>";
					}else{
						echo "$referencia - $descricao";
					}
					echo "</font>";

				}else{
					echo "<font size='1' color='#363636'> <b>$referencia</b> - $descricao</font>";
					echo "<br><font color='#333333' size='1'><b>Indisponível</b></font>\n";
				}

				if (strlen($preco_anterior)>0 AND $preco_anterior>0){					
					$preco_anterior = number_format($preco_anterior, 2, ',', '');
					$preco_anterior = str_replace(".",",",$preco_anterior);
					echo "<br>";
					echo "<span class='preco' style='font-size: 0.7em;color: #5F5F5F;'>DE: <b>R$ $preco_anterior</b></span>";
					echo "<br>";
					echo "<span class='preco'>POR: <b>R$ $preco_formatado</b></span>";
				}else{					
					echo "<br><span class='preco'>POR: <b>R$ $preco_formatado</b></span>";
				}

				if( $preco > 0 ){
					echo "</a></center>\n";
				}
				echo "</form>";
				echo "</div>\n";
			}else{
				$oPecaFoto = PecaFoto::find($peca);							
				if($oPecaFoto->temFoto('1')){					
					if($servidor == 'www.telecontrol.com.br' || $servidor == 'ww2.telecontrol.com.br' || $servidor == 'posvenda.telecontrol.com.br') {
						$path  = $oPecaFoto->getFoto('1');
						$url   = PecaFoto::corrigirCaminhoParaUrl($path);
						$fotosP = $url;
					}else{	
						$path  = $oPecaFoto->getFoto('1');							
						$exp = explode('/', $path);
						$imagem = $exp[count($exp)-1];
						$url = "http://192.168.0.199/~anderson/LojaVirtual/media/produtos/$imagem";														
						$fotosP = $url;
					}								
					// echo "<center><div style='height: ".($tamanho + 10)."px;
					// 				vertical-align: center;'>
					//   <a href='lv_detalhe.php?cod_produto=$peca$produto_acabado'>
					//   <img src='".$fotosP."' width='100' border='0' style='float:left;' >\n";
					echo "<center>
					  	<div style='height: ".$tamanho."px;' vertical-align: middle;'>
							<a href='lv_detalhe.php?cod_produto=$peca$produto_acabado'>
								<img  width='100' src='".$fotosP."'	border='0' style='height: 90px;'>"  ;

					echo "<input type='hidden' name='peca_imagem' value='$filename_final' >\n";
				}else{
					$fotosP = "imagens_pecas/semimagem.jpg";
					echo "<center>
					   <div style='height: ".$tamanho."px;
									'vertical-align: middle;'>
									<a href='lv_detalhe.php?cod_produto=$peca$produto_acabado'>
						<img  width='100' src='imagens_pecas/semimagem.jpg'
						border='0' style='height: 90px;'>
					  ";
				}

				if ($liquidacao == 't' AND $login_fabrica==10) {
					echo "<img src='imagens/promocao.gif'
							style='position: absolute; float: right;'>\n";
				}

				echo "</div></a>";

				if($login_fabrica ==10){
					echo "<input type='hidden' name='liquida_peca_$x'  value='$peca'>\n";
					echo "<input type='hidden' name='liquida_qtde_$x'  value='1'>\n";
					echo "<input type='hidden' name='liquida_preco_$x' value='$preco'>\n";
					echo "<input type='hidden' name='qtde_maxi_$x'     value='$qtde_max_site'>\n";
					echo "<input type='hidden' name='qtde_disp_$x'     value='$qtde_disponivel_site'>\n";

					echo "<input type='hidden' name='btn_comprar2' value='' class='botao'>
					<img src='imagens/bt_comprar_pq2.gif' onclick=\"javascript: if (document.frmcarrinho_$x.btn_comprar2.value == '' ) { document.frmcarrinho_$x.btn_comprar2.value='Comprar' ; document.frmcarrinho_$x.submit() } else { alert ('Aguarde submissão') }\" style=\"cursor: pointer;\">";
				}

				if ($login_fabrica<>10) {
					if ($promocao_site == 't' OR $qtde_disponivel_site > 0) {
						echo "<font color='#FF0000'  size='1'><b>EM PROMOÇÃO</b></font><BR>\n";
					}
				}
				if( $preco > 0 ){

					echo "<center><a href='lv_detalhe.php?cod_produto=$peca$produto_acabado' >";
					if($login_fabrica == 10) { // HD 40671
						echo "<span class='descrproduto'>$descricao</span>";
					}else{
						echo "$referencia - $descricao";
					}
					echo "</font>";

				}else{
					echo "<font size='1' color='#363636'> <b>$referencia</b> - $descricao</font>";
					echo "<br><font color='#333333' size='1'><b>Indisponível</b></font>\n";
				}

				#HD 13429
				if (strlen($preco_anterior)>0 AND $preco_anterior>0){					
					$preco_anterior = number_format($preco_anterior, 2, ',', '');
					$preco_anterior = str_replace(".",",",$preco_anterior);
					echo "<br>";
					echo "<span class='preco' style='font-size: 0.7em;color: #5F5F5F;'>DE: <b>R$ $preco_anterior</b></span>";
					echo "<br>";
					echo "<span class='preco'>POR: <b>R$ $preco_formatado</b></span>";
				}else{					
					echo "<br><span class='preco'>POR: <b>R$ $preco_formatado</b></span>";
				}

				if( $preco > 0 ){
					echo "</a></center>\n";
				}
				echo "</form>";
				echo "</div>\n";
			}

			
		}

echo "</td>\n";
echo "</tr>\n";


echo "</table>\n";
echo "</td>\n";
echo "</tr>\n";
echo "</table>\n";
echo "</form>\n";

if($login_fabrica == '85'){
?>
    <script type="text/javascript">
        ToggleView(this,document.getElementById('linha_0'));

        function openPopUp(link){
  			abrir(link);
        }


        function alteraImagem(elemento){
        	var caminhoImagem = elemento.getAttribute('src');
        	$('#imagemGrandeLink').attr('href',"javascript:openPopUp(\""+caminhoImagem+"\")");
        	$('#imagemGrande').attr('src',caminhoImagem);
        		
        }


    </script>

<?php
}

if (strlen($cook_fabrica)==0 AND strlen($cook_login_unico)>0) include "login_unico_rodape.php";
else                                                          include "rodape.php";
?>
