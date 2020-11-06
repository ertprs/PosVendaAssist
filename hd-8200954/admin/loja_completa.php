<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include_once "../class/tdocs.class.php";
$tDocs = new TDocs($con, $login_fabrica);

session_name("carrinho");
session_start();


$ajax = $_GET['ajax'];
if(strlen($ajax)>0){
echo "ok";

}

?>
<?

#$indice = $_SESSION[cesta][numero];

$numero_pedido = $_GET['pedido'];
$status        = $_GET['status'];

$layout_menu = 'pedido';
$title="BEM-VINDO a loja virtual";

include "cabecalho.php";
echo "<div style='position: absolute;opacity:.90;z-index:1; overflow: auto;position:absolute;top:0px;right: 5px;'><table id='mensagem' style='border: 1px solid rgb(211, 190, 150); background-color: rgb(252, 240, 216);' ><tbody><tr><td><b>Carregando dados...</b></td></tr></tbody></table></div>";

include 'loja_menu.php';
# AVISO
echo "<BR>";
# BUSCA POR PEÇA
echo "<table width='98%' border='0' align='center' cellpadding='2' cellspacing='2'>";
echo "<tr>";
echo "<td width='180' valign='top' align='left'>";
/*MENU*/
include "loja_menu_lateral.php";

echo "</td>";
echo "<td valign='top' align='right'>";
//echo "	<center><img src='imagens/liquidacao2.png' border='0'></center>";
echo "<table width='100%' border='0' align='center' cellpadding='0' cellspacing='0'>";

	$busca      = trim($_POST["busca"]);
	$tipo       = trim($_POST["tipo"]);
	$categoria  = trim($_GET["categoria"]);
	$categoria_tipo  = trim($_GET["categoria_tipo"]);

	if(strlen($busca) ==0){
		$busca = trim($_GET["busca"]);
	}

	if(strlen($tipo) ==0){
		$tipo = trim($_GET["tipo"]);
	}

	if (strlen($msg_erro) == 0 and strlen($busca)>0) {
		$buscas = strtoupper($busca);
		$pesquisa = "   AND (tbl_peca.descricao     LIKE '%$buscas%' or tbl_peca.referencia    LIKE '%$busca%')";
	}

	$join_pesquisa = "";

	if(strlen($categoria)>0){
		if($categoria_tipo == "familia"){
			$join_pesquisa = " JOIN tbl_lista_basica on tbl_lista_basica.peca = tbl_peca.peca
								JOIN tbl_produto on tbl_produto.produto = tbl_lista_basica.produto ";
			$pesquisa = "AND tbl_produto.familia = $categoria ";
		}
		if($categoria_tipo == "linha"){
			$join_pesquisa = " JOIN tbl_lista_basica on tbl_lista_basica.peca = tbl_peca.peca
								JOIN tbl_produto on tbl_produto.produto = tbl_lista_basica.produto ";
			$pesquisa = " AND tbl_produto.linha = $categoria ";
		}
	}

if (strlen($msg_erro) == 0) {
	if (strlen($pesquisa)== 0){
		$pesquisa = " AND   tbl_peca.promocao_site IS TRUE ";
	}

	//pega produtos
/*segundo o samuel, deve-se fazer compra independente da linha*/

$sql = "SELECT	X.peca,
				X.referencia    ,
				X.descricao     ,
				X.preco_sugerido,
				X.ipi  ,
				X.promocao_site ,
				X.qtde_disponivel_site,
				X.posicao_site,
				X.liquidacao
		FROM (
			SELECT DISTINCT tbl_peca.peca,
							tbl_peca.referencia    ,
							tbl_peca.descricao     ,
							tbl_peca.preco_sugerido,
							tbl_peca.ipi  ,
							tbl_peca.promocao_site ,
							tbl_peca.qtde_disponivel_site,
							tbl_peca.posicao_site,
							tbl_peca.liquidacao
			FROM tbl_peca

			$join_pesquisa

			WHERE tbl_peca.fabrica = $login_fabrica

			AND tbl_peca.ativo is not false
			AND tbl_peca.peca NOT IN (SELECT peca FROM tbl_peca_fora_linha WHERE fabrica = $login_fabrica AND peca is not null)
			$pesquisa
		) as X
		ORDER BY  X.posicao_site";
//echo nl2br($sql);
/*
$sql = "
		SELECT  tbl_peca.peca
		INTO TEMP TABLE Y
		FROM tbl_peca
		$join_pesquisa
		WHERE tbl_peca.fabrica = $login_fabrica
		AND tbl_peca.ativo is not false
		$pesquisa

		EXCEPT

		SELECT peca
		FROM tbl_peca_fora_linha
		WHERE fabrica = $login_fabrica ;


		SELECT	tbl_peca.peca,
				tbl_peca.referencia    ,
				tbl_peca.descricao     ,
				tbl_peca.preco_sugerido,
				tbl_peca.ipi  ,
				tbl_peca.promocao_site ,
				tbl_peca.qtde_disponivel_site,
				tbl_peca.posicao_site
		FROM Y
		join tbl_peca on tbl_peca.peca = Y.peca
		ORDER BY  tbl_peca.posicao_site";
*/
//echo nl2br($sql);
//exit;
	// ##### PAGINACAO ##### //
	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";

	require "_class_paginacao.php";

	// definicoes de variaveis
	$max_links = 11;				// m?ximo de links ? serem exibidos
	$max_res   = 20;					// m?ximo de resultados ? serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o n?mero de pesquisas (detalhada ou n?o) por p?gina

	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");
	
	// ##### PAGINACAO ##### //

	if(pg_numrows($res) == 0){
		echo "<tr>\n";
		echo "<td align='center'>\n";
				echo "<div align='center'><FONT SIZE='2' COLOR='#FF0000'><b>Nenhuma peça encontrada!</b></FONT></div>";
		echo "</td>";
		echo "</tr>";
	}else{
		echo "<tr>\n";
		echo "<td align='center'>\n";

	for ($i = 0 ; $i < pg_numrows($res); $i++){
		$peca                 = trim(pg_result ($res,$i,peca));
		$referencia           = trim(pg_result ($res,$i,referencia));
		$preco_sugerido       = trim(pg_result ($res,$i,preco_sugerido));
		$ipi                  = trim(pg_result ($res,$i,ipi));
		$descricao            = trim(pg_result ($res,$i,descricao));
		$descricao            = substr($descricao,0,25)."...";
		$promocao_site        = trim(pg_result ($res,$i,promocao_site));
		$qtde_disponivel_site = trim(pg_result ($res,$i,qtde_disponivel_site));
		$posicao_site         = trim(pg_result ($res,$i,posicao_site));
		$liquidacao           = trim(pg_result ($res,$i,liquidacao));

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

			$sql2 = "SELECT distinct tbl_tabela_item.preco
						FROM tbl_tabela
						JOIN tbl_tabela_item USING(tabela)
						WHERE peca  = $peca
						AND tbl_tabela.fabrica = $login_fabrica
						AND   tbl_tabela.tabela IN (
							SELECT tbl_tabela.tabela
							FROM tbl_posto_linha
							JOIN tbl_tabela       USING(tabela)
							JOIN tbl_posto        USING(posto)
							JOIN tbl_linha        USING(linha)
							WHERE   tbl_posto_linha.linha in (
								SELECT DISTINCT tbl_produto.linha
								FROM tbl_produto
								JOIN tbl_lista_basica USING(produto)
								JOIN tbl_peca USING(peca)
								WHERE peca = $peca
								AND tbl_peca.fabrica=$login_fabrica
							 )
						)";
					//	echo $sql2."<BR>";

		}

		$res2 = pg_exec ($con,$sql2);
		if(pg_numrows($res2)==0) {
			$preco       = 0;
//			continue;
		}else{
			$preco_formatado = "";
			for ($j = 0 ; $j < pg_numrows($res2); $j++){
				$X_preco = number_format(trim(pg_result ($res2,$j,preco)),2,",",".");
				$preco_formatado .= "<a href='preco_cadastro.php?peca=$peca' target='blank'><strong><font color='#FF0000'>R$ $X_preco</font></strong></a> <BR>";
			}

		}

		if($i%2==0) {$cor='#F4EBD7';
		}else{        $cor='#EFEFEF';}

		echo "\n<div rel='box_content' class=\"content_box\"> \n";
		echo "<a href='peca_cadastro.php?peca=$peca' target='blank'>";
			$saida == "";
			$xpecas  = $tDocs->getDocumentsByRef($peca, "peca");
			if (!empty($xpecas->attachListInfo)) {

				$a = 1;
				foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
				    $fotoPeca = $vFoto["link"];
				    if ($a == 1){break;}
				}
				echo "<center><img src='$fotoPeca' width='50' border='0'></center>";	
			} else {
				if($login_fabrica == '85'){
					$dh = scandir('../imagens_pecas/85/pequena/');
					$filename_final = "";
					foreach ($dh as $value) {
						if($value != "." && $value != ".."){
							$pecaImagem = strstr($value, '.',true);						
	                        if($pecaImagem != false){
	                        	if($peca == $pecaImagem){
	                        		$filename_final = $value;
	                        	}
	                        }						
						}					
					}
				}else{
					if ($dh = opendir('../imagens_pecas/pequena/')) {
						$contador=0;
						$filename_final = "";
						while (false !== ($filename = readdir($dh))) {
							if($contador == 1) break;

							if (strpos($filename,$referencia) !== false){
								$contador++;
								$po = strlen($referencia);
								if(substr($filename, 0,$po)==$referencia){
									$filename_final = $filename;
								}
							}
						}
					}	
				}

			
				if(strlen($filename_final)>0){
					if($login_fabrica == '85'){
						echo "<center><img src='../imagens_pecas/85/pequena/$filename_final' width='50' border='0'></center>";	
					}else{
						echo "<center><img src='../imagens_pecas/pequena/$filename_final' width='50' border='0'></center>";	
					}
					
					echo "<input type='hidden' name='peca_imagem' value='$filename_final'>\n";
				}else{
					echo "<center><img src='../imagens_pecas/semimagem.jpg' border='0' width='50'></center>";
					echo "<input type='hidden' name='peca_imagem' value='semimagem.jpg'>\n";
				}
			}

			if ($liquidacao == 't') { //HD 118243
				echo "<font color='#FF0000'  size='1'><b>EM PROMOÇÃO</b></font><BR>\n";
			}

			$qtde_disponivel_site = (strlen($qtde_disponivel_site) > 0) ? $qtde_disponivel_site : 0;

			echo "<font size='1' color='#363636'> <b>$referencia</b> - $descricao</b></font></a>\n";
			echo "<BR><font size='1' color='#363636'>Disponivel: </font><font size='1' color='#FC552C'>$qtde_disponivel_site</font>\n";
			if( strlen($preco_formatado) > 0 ){
				echo "<br>$preco_formatado\n";
			}else{
				echo "<br><font color='#EF8B03' size='1'><b>Sem Preço</b></font><BR>\n";
			}

			if(strlen($posicao_site) > 0){
				echo "<font size='1'>Prioridade <acronym class='ac' title='Prioridade de exibição da peça na Loja Virtual. (1,2,3...1000)'>[?]</acronym> : </font>";
				echo "<B><font size='1'>$posicao_site</font></b>";
			}

		echo "</div>\n";
	flush();
	}
		echo "</td>";
		echo "</tr>\n";
	}
	echo "<script>document.getElementById('mensagem').style.visibility = 'hidden';</script>";

echo "</table>\n";
### P? PAGINACAO###
echo "<BR>";
	echo "<table border='0' align='center' width='100%'>";
	echo "<tr>";
	echo "<td align='center'>";

	// ##### PAGINACAO ##### //

	// links da paginacao
	echo "<br>";

	if($pagina < $max_links){
		$paginacao = pagina + 1;
	}else{
		$paginacao = pagina;
	}

	// paginacao com restricao de links da paginacao

	// pega todos os links e define que 'Pr?xima' e 'Anterior' ser?o exibidos como texto plano
	$todos_links	= $mult_pag->Construir_Links("strings", "sim");

	// fun??o que limita a quantidade de links no rodape
	$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

	for ($n = 0; $n < count($links_limitados); $n++) {
		echo "<font color='#DDDDDD' size='1'>".$links_limitados[$n]."</font> ";
	}

	$resultado_inicial = ($pagina * $max_res) + 1;
	$resultado_final   = $max_res + ( $pagina * $max_res);
	$registros         = $mult_pag->Retorna_Resultado();

	$valor_pagina   = $pagina + 1;
	$numero_paginas = intval(($registros / $max_res) + 1);

	if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

	if ($registros > 0){
		echo "<br>";
		echo "<font size='2'  color='#363636'>Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.</font>";
		echo "<font color='#cccccc' size='1'>";
		echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
		echo "</font>";
		echo "</div>";
	}
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	// ##### PAGINACAO ##### //
}
echo "</td>";
echo "</tr>";
echo "</table>";

include 'rodape.php';
?>