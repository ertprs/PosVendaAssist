<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include_once "class/tdocs.class.php";
$tDocs = new TDocs($con, $login_fabrica);

/* Liberado para todos os Postos da Britania - HD 5686 - Fabio

$sql = "SELECT estado FROM tbl_posto WHERE posto = $login_posto";
$res = pg_exec ($con,$sql);
$estado = pg_result ($res,0,0);
if ($login_fabrica == 3 and $estado != 'SP') {
	header ("Location: menu_pedido.php");
}
*/

// Liberado para todos os Postos da Britania - HD 5686 - Fabio
if ($login_fabrica <> 3) {
	header ("Location: menu_pedido.php");
}

// Liberado para todos os Postos da Britania (Nova Versão da LV)- HD 7549 - Fabio
if ($login_fabrica==3){
	include 'lv_completa.php';
	exit;
}

session_name("carrinho");
session_start();

#$indice = $_SESSION[cesta][numero];

$numero_pedido = $_GET['pedido'];
$status        = $_GET['status'];

$layout_menu = 'pedido';
$title="BEM-VINDO a loja virtual da Britania ";

include "cabecalho.php";
echo "<div style='position: absolute;opacity:.90;z-index:1; overflow: auto;position:absolute;top:0px;right: 5px;'><table id='mensagem' style='border: 1px solid rgb(211, 190, 150); background-color: rgb(252, 240, 216);' ><tbody><tr><td><b>Carregando dados...</b></td></tr></tbody></table></div>";

?>
<style>
.Titutlo2{
	font-family: Arial;
	font-size: 12px;
	font-weight:bold;
	color: #333;
}
.Titulo{
	font-family: Arial;
	font-size: 14px;
	font-weight:bold;
	color: #333;
}
.Conteudo{
	font-family: Arial;
	font-size: 11px;
	color: #333333;
}

</style>
<?
include 'loja_menu.php';

$sql="SELECT posto, capital_interior 
		FROM tbl_posto WHERE posto=$login_posto";

$res = pg_exec ($con,$sql);
if(pg_numrows($res)>0){
	$posto            = trim(pg_result ($res,0,posto));
	$capital_interior = trim(pg_result ($res,0,capital_interior));
}

$sql = "SELECT valor_pedido_minimo, valor_pedido_minimo_capital 
		FROM tbl_fabrica 
		WHERE fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);
$valor_pedido_minimo         = trim(pg_result($res,valor_pedido_minimo));
$valor_pedido_minimo_capital = trim(pg_result($res,valor_pedido_minimo_capital));

$valor_pedido_minimo         = number_format($valor_pedido_minimo,2,".",",");
$valor_pedido_minimo_capital = number_format($valor_pedido_minimo_capital,2,".",",");

if($capital_interior=="CAPITAL"){
	$msg="O valor mínimo de faturamento é de R$ $valor_pedido_minimo_capital..";
}

if($capital_interior=="INTERIOR"){
	$msg="O valor mínimo de faturamento é de R$ $valor_pedido_minimo.";
}

if (strlen ($msg_erro) == 0){
	# Retorna todos os pedidos da Loja Virtual que n?o foram exportados
	$sql = "SELECT  
				tbl_pedido.pedido,
				to_char(tbl_pedido.finalizado,'DD/MM/YYYY') as finalizado,
				to_char(tbl_pedido.data,'DD/MM/YYYY') as data,
				tbl_pedido.total,
				tbl_condicao.descricao AS condicao
		FROM  tbl_pedido
		LEFT JOIN tbl_condicao USING(condicao)
		WHERE tbl_pedido.pedido_loja_virtual IS TRUE
		AND   tbl_pedido.exportado IS NULL
		AND   tbl_pedido.posto   = $login_posto
		AND   tbl_pedido.fabrica = $login_fabrica
		ORDER BY tbl_pedido.pedido DESC";
	$res = pg_exec ($con,$sql);
	if (pg_numrows($res) > 0) {
		
		$qtde_produto = 0;
		$total        = 0;
		$pedidos      = "";

		for ($i=0;$i<pg_numrows($res);$i++){
			$pedido      = trim(pg_result($res,$i,pedido));
			$pedidos    .= $pedido. ", ";
			$finalizado  = trim(pg_result($res,$i,finalizado));
			$data        = trim(pg_result($res,$i,data));
			$condicao    = trim(pg_result($res,$i,condicao));
			$total      += trim(pg_result($res,$i,total));

			$sql = "SELECT  SUM(qtde) as qtde
					FROM  tbl_pedido_item
					WHERE pedido = $pedido";
			$res2 = pg_exec ($con,$sql);
			$qtde_produto  += pg_result($res2,0,qtde);
		}

		$total = number_format ( $total,2,'.',',');

		$pedidos = trim($pedidos);
		$pedidos = substr ($pedidos,0,strlen ($pedidos)-1);

		if (strlen($finalizado)>0){
			$status_pedido = "Finalizado - <a href='loja_carrinho.php'>Clique aqui para visualizar o Carrinho</a>";
		}else{
			$status_pedido = "Não Finalizado - <a href='loja_carrinho.php'>Clique aqui para abrir o Carrinho</a>";
		}

		echo "<br>";
		echo "<table width='700' border='1'  cellpadding='2' cellspacing='0' STYLE='border-collapse:collapse' bordercolor='#d2e4fc' align='center'>";

		if ($status=="finalizado"){
			echo "<tr>";
			echo "<td colspan='4' align='center' class='Titulo' bgcolor='#e6eef7' >";
			echo "<font size='3' color='blue'><b>Pedido finalizado com sucesso!</b></font><br>";
			echo "<font size='2' color='black'>Este pedido será enviado para a Fábrica esta noite.<br> É possível adicionar mais peças antes que seja enviado.</font><br>";
			echo "<font size='1' color='black'>Este pedido poderá ser consultado na <a href='pedido_relacao.php?listar=todas'>Consulta de Pedidos</a></font><br><br>";
			echo "</td>";
			echo "</tr>";
		}

		echo "<tr>";
		echo "<td  align='left' class='Titutlo2' bgcolor='#E7EFF8' >Número do seu Pedido</td>";
		echo "<td colspan='3' align='left' class='Conteudo' bgcolor='#e6eef7' >$pedidos</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td  align='left' class='Titutlo2' bgcolor='#E7EFF8' >Status da sua Compra</td>";
		echo "<td colspan='3' align='left' class='Conteudo' bgcolor='#e6eef7' >$status_pedido</td>";
		echo "</tr>";

		if (strlen($finalizado)>0){
			echo "<tr>";
			echo "<td  align='left' class='Titutlo2' bgcolor='#E7EFF8' ></td>";
			echo "<td colspan='3' align='left' class='Conteudo' bgcolor='#e6eef7' ><b>A compra da Loja Virtual será enviado hoje à noite para a Fábrica.</b></td>";
			echo "</tr>";
		}

		echo "<tr>";
		echo "<td align='left' class='Titutlo2' bgcolor='#E7EFF8'>Quantidade de Produtos</td>";
		echo "<td align='left' class='Conteudo' bgcolor='#e6eef7'>$qtde_produto</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td align='left' class='Titutlo2' bgcolor='#E7EFF8'>Valor</td>";
		echo "<td colspan='3' align='left' class='Conteudo' bgcolor='#e6eef7' >R$ $total</td>";
		echo "</tr>";

		if (strlen($condicao)>0){
			echo "<tr>";
			echo "<td align='left' class='Titutlo2' bgcolor='#E7EFF8'>Condição de Pagamento</td>";
			echo "<td align='left' class='Conteudo' bgcolor='#e6eef7'>$condicao</td>";
			echo "</tr>";
		}

		if (strlen($finalizado)==0){
			echo "<tr>";
			echo "<td colspan='4' align='left' class='Conteudo' bgcolor='#e6eef7' >O pedido da Loja Virtual não será enviado para a Fábrica até que seja FECHADO. Acesse o carrinho de compras e clique em Fechar Pedido</td>";
			echo "</tr>";
		}

		echo "</table>";
		echo "<br>";		
	}
}

# AVISO
echo "<table width='700' border='0' align='center' cellpadding='0' cellspacing='0' style=' border:#B5CDE8 1px solid;  bordercolor='#d2e4fc'>";
echo "<tr height='40'>";
echo "<td height='40' colspan='4' align='center' class='Titulo' bgcolor='#e6eef7' >
	<font size='2' color='red'><b>ATENÇÃO</b></font><BR>
	<font size='1' color='red'>$msg<BR>
	 ** Todos os pedidos serão CIF **<br>
	* Abaixo de R$200,00 prazo de pagamento 30dd<BR>
	* Entre R$ 200,01 e R$ 400,00 prazo de pagamento 030/060dd<BR>
	* Acima de R$ 400,00 030/060/090dd<BR>
	(*) Prazo de pagamento
	</font>
	<br>
	";
echo "<br>";
echo "<span style='font-size:13px; color:#00446C'>";
echo "<a href='pedido_cadastro.php'>Clique aqui para fazer pedido na tela de Cadastro de Pedido</a>";
echo "</span>";
echo "</td>";
echo "</tr>";
echo "</table>";
echo "<br>";


# BUSCA POR PE?A
echo "<table width='700' border='0' align='center' cellpadding='0' cellspacing='0' style=' border:#B5CDE8 1px solid;  bordercolor='#d2e4fc'>";
echo "<tr height='40'>";
	echo "<td height='40' colspan='4' align='center' class='Titulo' bgcolor='#E0EBF5' >
	<font size='3' color='black'>Busca de Peças</font><br>";
	echo "</td>";
echo "</tr>";
//produtos linha a cima
echo "<TR>";
	echo "<TD colspan='4'>";
	/*###################################################################################
	################################### DIV PESQUISA ###################################*/
		$busca = trim($_POST["busca"]);
		$tipo  = trim($_POST["tipo"]);

		if(strlen($busca) ==0){
			$busca = trim($_GET["busca"]);
		}
		if(strlen($tipo) ==0){
			$tipo = trim($_GET["tipo"]);
		}

		?>

		<form method='POST' name="Pesquisar" action="<? echo $PHP_SELF; ?>">
		<table align='center'>
		<tr>
			<td><input name="busca" size="41" maxlength="2048" value="<?=$busca?>" title="Pesquisar" type="text" ><font size="-1"> <input name="btnG" value="Pesquisar" type="submit"></td>
		</tr>
		<tr>
			<td class='Conteudo'>Pesquisar por Status: <input type='radio' name='tipo' value='d' checked> Descrição<input type='radio' name='tipo' value='c' <?if($tipo=='c') echo "CHECKED";?>> Referência</td>
		</tr>
		</table>
		</form>
		<?

		if (strlen($msg_erro) == 0 and strlen($busca)>0) {
			//--=== Busca por Descri??o =============================================
			
			if(($tipo == 'd') && (strlen($busca)>0)){
					$buscas = strtoupper($busca);
					$pesquisa = "   AND tbl_peca.descricao     LIKE '%$buscas%' ";
				}
			//--=== Busca por Referencia=============================================
			if(($tipo == 'c') && (strlen($busca)>0)){
					$pesquisa = "   AND tbl_peca.referencia    LIKE '%$busca%' ";
				}
		}
	/*################################# FIM DIV PESQUISA ################################
	#####################################################################################*/
	echo "</TD>";
	echo "</TR>";

if (strlen($msg_erro) == 0) {
	if (strlen($pesquisa)== 0){
		$pesquisa = " AND   tbl_peca.promocao_site IS TRUE ";
	}

	//pega produtos

$sql="	SELECT DISTINCT tbl_produto.produto
	INTO TEMP temp_produto_$login_posto
	FROM tbl_produto 
	JOIN tbl_lista_basica ON tbl_lista_basica.produto = tbl_produto.produto AND tbl_lista_basica.fabrica=$login_fabrica
	JOIN tbl_peca         USING(peca) 
	WHERE tbl_peca.fabrica = $login_fabrica
	AND   tbl_peca.ativo   IS TRUE
	AND tbl_produto.linha IN (
		SELECT linha
		FROM tbl_posto_linha
		WHERE posto = $login_posto
	)
	AND tbl_peca.peca NOT IN (SELECT peca FROM tbl_peca_fora_linha WHERE fabrica = $login_fabrica);

	CREATE INDEX temp_produto_PRODUTO_$login_posto ON temp_produto_$login_posto(produto);";
	$res = pg_exec ($con,$sql);

	$sql = "SELECT DISTINCT tbl_peca.peca,
					referencia    ,
					descricao     ,
					preco_sugerido,
					tbl_peca.ipi  ,
					promocao_site ,
					qtde_disponivel_site
	FROM temp_produto_$login_posto prod
	JOIN tbl_lista_basica ON prod.produto  = tbl_lista_basica.produto
	JOIN tbl_peca         ON tbl_peca.peca = tbl_lista_basica.peca
	WHERE tbl_peca.fabrica = $login_fabrica
	
	$pesquisa

	ORDER BY  promocao_site";

//echo nl2br($sql);

	// ##### PAGINACAO ##### //
	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";

	require "_class_paginacao.php";

	// definicoes de variaveis
	$max_links = 11;				// m?ximo de links ? serem exibidos
	$max_res   = 5;					// m?ximo de resultados ? serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o n?mero de pesquisas (detalhada ou n?o) por p?gina

	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

	// ##### PAGINACAO ##### //

	if(pg_numrows($res) < 1){
		echo "<div align='center'><FONT SIZE='2' COLOR='#FF0000'><b>Nenhuma peça encontrada!</b></FONT></div>";
	}

	for ($i = 0 ; $i < pg_numrows($res); $i++){
		$peca                 = trim(pg_result ($res,$i,peca));
		$referencia           = trim(pg_result ($res,$i,referencia));
		$preco_sugerido       = trim(pg_result ($res,$i,preco_sugerido));
		$ipi                  = trim(pg_result ($res,$i,ipi));
		$descricao            = trim(pg_result ($res,$i,descricao));
		$promocao_site        = trim(pg_result ($res,$i,promocao_site));
		$qtde_disponivel_site = trim(pg_result ($res,$i,qtde_disponivel_site));
		
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
		$res2 = pg_exec ($con,$sql2);
		if(pg_numrows($res2)<1) {
			$preco       = 0;
			continue;
		}else{
			$preco       = trim(pg_result ($res2,0,preco));
		}
		$preco_formatado = number_format($preco, 2, ',', '');
		$preco_formatado = str_replace(".",",",$preco_formatado);

		if($i%2==0) $cor='#F4EBD7';
		else        $cor='#EFEFEF';

		#if($i==4)   $cor='#F4EBD7';

	#	if($i==0 ) {
			echo "<tr class='Conteudo'>";
	#	}

#		if($i<>0') {
#			echo "</tr><tr class='Conteudo'>";
#		}

		echo "<td align='center' bgcolor='$cor'>\n";
		echo "<BR><div class='contenedorfoto'>\n";
		if( $preco > 0){
			echo "<a href='loja_detalhe.php?cod_produto=$peca'>";
			$saida == "";

            $xpecas = $tDocs->getDocumentsByRef($peca, "peca");
            if (!empty($xpecas->attachListInfo)) {

				$a = 1;
				foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
				    $fotoPeca = $vFoto["link"];
				    if ($a == 1){break;}
				}
				echo "<img src='$fotoPeca' border='0'>
					<input type='hidden' name='peca_imagem' value='$fotoPeca'>";
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
								<img src='imagens_pecas/pequena/<?echo $filename; ?>' border='0'>
								<input type='hidden' name='peca_imagem' value='<?echo $filename; ?>'>
				<?			}
						}
					}
				}
			}
			// alterado HD 3780 - GUSTAVO
			echo "</a>";
		}else{
			$xpecas = $tDocs->getDocumentsByRef($peca, "peca");
            if (!empty($xpecas->attachListInfo)) {

				$a = 1;
				foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
				    $fotoPeca = $vFoto["link"];
				    if ($a == 1){break;}
				}
				echo "<img src='$fotoPeca' border='0'>
					<input type='hidden' name='peca_imagem' value='$fotoPeca'>";
            } else {
				$saida == "";
				if ($dh = opendir('imagens_pecas/pequena/')) {
					$contador=0;
					while (false !== ($filename = readdir($dh))) {
						if($contador == 1) break;
						if (strpos($filename,$referencia) !== false){
							$contador++;
							//$peca_referencia = ntval($peca_referencia);
							$po = strlen($referencia);
							if(substr($filename, 0,$po)==$referencia){?>
								<img src='imagens_pecas/pequena/<?echo $filename; ?>' border='0'>
								<input type='hidden' name='peca_imagem' value='<?echo $filename; ?>'>
				<?			}
						}
					}
				}
			}
		}
		echo "</div>\n";

		echo "</td>\n";

		#if(($qtde_disponivel_site) > 0 && ($preco) > 0){

		if( $preco > 0 ){
			echo "<td bgcolor='$cor' align='left' style='text-align:left'>\n";
			if ($promocao_site == 't' OR $qtde_disponivel_site > 0) {
				echo "<br><font color='#FF0000'><b>EM PROMOÇÃO</b></font><BR>\n";
			}
			echo "<a href='loja_detalhe.php?cod_produto=$peca' >$descricao";
			echo "<br><font color='#333333'>Referencia: </b>$referencia</b></font>";
			echo "<br><font color='#333333'>Qtde Disponível: </b>$qtde_disponivel_site</b></font>";
			echo "<br><font color='#FF0000' size='+1'><b>R$ $preco_formatado</b></font> ( + $ipi % IPI )
			<BR>Adicione ao carrinho</a>\n";
			echo "</td>\n";
		}else{
			echo "<td  bgcolor='$cor' align='left' style='text-align:left'>\n";
			if($promocao_site == 't'){
				echo "<br><font color='#FF0000'><b>EM PROMOÇÃO</b></font><BR>\n";
			}
			echo "$descricao";
			echo "<br><font color='#333333'>Referencia: </b>$referencia</b></font>\n";
			echo "<br><font color='#333333'><b>Indisponivel</b></font>\n";
			echo "<br><font color='#FF0000' size='+1'><b>R$ $preco_formatado</b></font> ( + $ipi % IPI )<BR>";
			echo "</td>\n";
		}
		echo "</tr>";
	}
	echo "<script>document.getElementById('mensagem').style.visibility = 'hidden';</script>";

	echo "<tr>\n";
	echo "<td align='right' bgcolor='#e6eef7' colspan='5' class='Titulo'><a href='javascript:history.back()'>Voltar</a></td>\n";
	echo "</tr>\n";

echo "</table>";
### P? PAGINACAO###

	echo "<table border='0' align='center'>";
	echo "<tr>";
	echo "<td colspan='9' align='center'>";
	
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
		echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>";
	}

	$resultado_inicial = ($pagina * $max_res) + 1;
	$resultado_final   = $max_res + ( $pagina * $max_res);
	$registros         = $mult_pag->Retorna_Resultado();

	$valor_pagina   = $pagina + 1;
	$numero_paginas = intval(($registros / $max_res) + 1);

	if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

	if ($registros > 0){
		echo "<br>";
		echo "<font size='2'>Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.</font>";
		echo "<font color='#cccccc' size='1'>";
		echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
		echo "</font>";
		echo "</div>";
	}
	// ##### PAGINACAO ##### //
}
include 'rodape.php';
?>