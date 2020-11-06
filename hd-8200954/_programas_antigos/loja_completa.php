<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

session_name("carrinho");
session_start();

$indice = $_SESSION[cesta][numero];

$numero_pedido = $_GET['pedido'];
$status        = $_GET['status'];

if($indice==0 OR strlen($indice)==0){
	//alterado HD 3780 - Gustavo 
	/*############################ SELECT CARRINHO VAZIO ########################################*/
	if (strlen ($msg_erro) == 0){
		# Deve ser retornado somente 1 pedido de venda (pedido_loja_virtual IS TRUE)
		# Não pode ter mais que 1 pedido de loja Virtual
		$sql = "SELECT  
					tbl_pedido_item.pedido,
					tbl_pedido_item.pedido_item,
					tbl_peca.peca                  ,
					tbl_peca.referencia            ,
					tbl_peca.descricao             ,
					tbl_peca.ipi                   ,
					tbl_pedido_item.qtde           ,
					tbl_pedido_item.preco
			FROM  tbl_pedido
			JOIN  tbl_pedido_item USING (pedido)
			JOIN  tbl_peca        USING (peca)
			WHERE tbl_pedido.pedido_loja_virtual IS TRUE
			AND   tbl_pedido.exportado IS NULL
			AND   tbl_pedido.posto   = $login_posto
			AND   tbl_pedido.fabrica = $login_fabrica
			ORDER BY tbl_pedido_item.pedido_item";
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0) {
			$_SESSION[cesta][numero] = 0;
			$_SESSION[cesta][pedido] =  trim(pg_result($res,0,pedido));
			for($i=1; $i<= pg_numrows($res); $i++) {
				$_SESSION[cesta][$i][pedido]      = trim(pg_result($res,$i-1,pedido));
				$_SESSION[cesta][$i][pedido_item] = trim(pg_result($res,$i-1,pedido_item));
				$_SESSION[cesta][$i][produto]     = trim(pg_result($res,$i-1,peca));
				$_SESSION[cesta][$i][descricao]   = trim(pg_result($res,$i-1,descricao));
				$_SESSION[cesta][$i][qtde]        = trim(pg_result($res,$i-1,qtde));
				$_SESSION[cesta][$i][valor]       = trim(pg_result($res,$i-1,preco));
				$_SESSION[cesta][$i][ipi]         = trim(pg_result($res,$i-1,ipi));
				$_SESSION[cesta][numero]++;
			}
		}
	}
}


$layout_menu = 'pedido';
$title="BEM-VINDO a loja virtual da Britânia ";
include "cabecalho.php";
?>
<style>
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

$sql="select posto, capital_interior from tbl_posto where posto=$login_posto";

$res = pg_exec ($con,$sql);
if(pg_numrows($res)>0){
	$posto            = trim(pg_result ($res,0,posto));
	$capital_interior = trim(pg_result ($res,0,capital_interior));
}

if($capital_interior=="CAPITAL"){
	$msg="O valor mínimo de faturamento é de R$100,00.";
}

if($capital_interior=="INTERIOR"){
	$msg="O valor mínimo de faturamento é de R$50,00.";
}


if ($status=="finalizado" AND strlen($numero_pedido)>0){
	echo "<table width='700' border='0' align='center' cellpadding='0' cellspacing='0' style=' border:#B5CDE8 1px solid;  bordercolor='#d2e4fc'>";
	echo "<tr height='40'>";
	echo "<td height='40' colspan='4' align='center' class='Titulo' bgcolor='#e6eef7' >
		<br>
		<font size='3' color='blue'><b>Pedido finalizado com sucesso!</b></font><br>
		<font size='2' color='black'>O número do seu pedido é <b>$numero_pedido</b></font><BR><BR>
		<font size='1' color='black'>Este pedido será enviado para a Fábrica nesta noite. Mas é possível adicionar mais peças antes que seja enviado.<br>Este pedido poderá ser consultado na <a href='pedido_relacao.php?listar=todas'>Consulta de Pedidos</a><br></font><br><br>";
	echo "</td>";
	echo "</tr>";
}


echo "<table width='700' border='0' align='center' cellpadding='0' cellspacing='0' style=' border:#B5CDE8 1px solid;  bordercolor='#d2e4fc'>";
echo "<tr height='40'>";
	echo "<td height='40' colspan='4' align='center' class='Titulo' bgcolor='#e6eef7' >
	<font size='2' color='red'><b>ATENÇÂO</b></font><BR>
	<font size='1' color='red'>$msg<BR>
	* Faturamento CIF abaixo de R$200,00 prazo de pagamento 30dd<BR>
	* Faturamento CIF abaixo de R$ 400,00 prazo de pagamento 030/060dd<BR>
	* Faturamento CIF acima de R$ 400,00 030/060/090dd<BR>
	</font>";
	echo "</td>";
echo "</tr>";

//produtos linha a cima
echo "<TR>";
	echo "<TD colspan='4'>";
			/*###################################################################################
			################################### DIV PESQUISA ###################################*/
				$busca = trim($_POST["busca"]);
				$tipo  = trim($_POST["tipo"]);
				$btnG  = trim($_POST["btnG"]);
			
				if(strlen($busca) ==0){
					$busca = trim($_GET["busca"]);
				}
				if(strlen($tipo) ==0){
					$tipo = trim($_GET["tipo"]);
				}
				if(strlen($btnG) ==0){
					$btnG = trim($_GET["btnG"]);
				}
				?>

				<form method='POST' name="Pesquisar" action="<? echo $PHP_SELF; ?>">
				<table align='center'>
				<tr>
					<td><input name="busca" size="41" maxlength="2048" value="<?=$busca?>" title="Pesquisar" type="text" ><font size="-1"> <input name="btnG" value="Pesquisar" type="submit"></td>
				</tr>
				<tr>
					<td class='Conteudo'>Pesquisar por: <input type='radio' name='tipo' value='d' checked> Descrição<input type='radio' name='tipo' value='c' <?if($tipo=='c') echo "CHECKED";?>> Referência</td>
				</tr>
				</table>
				</form>
				<?

				if (strlen($msg_erro) == 0 and strlen($btnG)>0) {
					//--=== Busca por Descrição =============================================
					
					if(($tipo == 'd') && (strlen($busca)>0)){
							$buscas = strtoupper($busca);
							$pesquisa = " AND tbl_peca.descricao like '%$buscas%'";
						}
					//--=== Busca por Referencia=============================================
					if(($tipo == 'c') && (strlen($busca)>0)){
							$pesquisa = "AND tbl_peca.referencia like '%$busca%'";
						}
				}
			/*################################# FIM DIV PESQUISA ################################
			#####################################################################################*/
	echo "</TD>";
	echo "</TR>";

if (strlen($msg_erro) == 0) {
	if (strlen($pesquisa)== 0){
		$pesquisa = "AND tbl_peca.promocao_site is true";
	}

	//pega produtos
	$sql = "
	SELECT DISTINCT tbl_peca.peca,
					referencia    ,
					descricao     ,
					preco_sugerido,
					tbl_peca.ipi  ,
					promocao_site ,
					qtde_disponivel_site
	FROM (
		SELECT DISTINCT tbl_produto.produto
		FROM tbl_produto 
		JOIN tbl_lista_basica ON tbl_lista_basica.produto = tbl_produto.produto AND tbl_lista_basica.fabrica=$login_fabrica
		JOIN tbl_peca USING(peca) 
		WHERE tbl_peca.fabrica=$login_fabrica
		AND linha IN (
		SELECT linha
		FROM tbl_posto_linha
		WHERE posto = $login_posto
		)
	) prod
	JOIN tbl_lista_basica ON prod.produto  = tbl_lista_basica.produto
	JOIN tbl_peca         ON tbl_peca.peca =tbl_lista_basica.peca
	WHERE tbl_peca.fabrica=$login_fabrica $pesquisa ORDER BY  promocao_site
	";

	// ##### PAGINACAO ##### //
	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";

	require "_class_paginacao.php";

	// definicoes de variaveis
	$max_links = 11;				// máximo de links à serem exibidos
	$max_res   = 6;					// máximo de resultados à serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

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
					 AND   tbl_posto_linha.linha in (
						SELECT DISTINCT tbl_produto.linha
						FROM tbl_produto 
						JOIN tbl_lista_basica USING(produto)
						JOIN tbl_peca USING(peca)
						WHERE peca = $peca LIMIT 1
					)
				)";
		$res2 = pg_exec ($con,$sql2);
		if(pg_numrows($res2)<1) {
			$preco       = 0;
		}else{
			$preco       = trim(pg_result ($res2,0,preco));
		}
		$preco_formatado = number_format($preco, 2, ',', '');
		$preco_formatado = str_replace(".",",",$preco_formatado);

		if($i%3==0) $cor='#F4EBD7';
		else        $cor='#EFEFEF';
		if($i==4)   $cor='#F4EBD7';

	#	if($i==0 ) {
			echo "<tr class='Conteudo'>";
	#	}

#		if($i<>0) {
#			echo "</tr><tr class='Conteudo'>";
#		}

		echo "<td align='center' bgcolor='$cor'>\n";
		echo "<BR><div class='contenedorfoto'>\n";
		if(($qtde_disponivel_site) > 0 && ($preco) > 0){
			echo "<a href='loja_detalhe.php?cod_produto=$peca'>";
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
			// alterado HD 3780 - GUSTAVO
			echo "</a>";
		}else{
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
		echo "</div>\n";
		# o que é isso?????? Fabio
		#if(strlen($saida)>0){
		#	//echo "$saida"; # o que é isso?
		#}
		echo "</td>\n";

		if(($qtde_disponivel_site) > 0 && ($preco) > 0){
			echo "<td bgcolor='$cor' align='left' style='text-align:left'>\n";
			if($promocao_site == 't'){
				echo "<br><font color='#FF0000'><b>EM PROMOÇÃO</b></font><BR>\n";
			}
			echo "<a href='loja_detalhe.php?cod_produto=$peca'>$descricao";
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
	echo "<tr>\n";
	echo "<td align='right' bgcolor='#e6eef7' colspan='5' class='Titulo'><a href='javascript:history.back()'>Voltar</a></td>\n";
	echo "</tr>\n";

echo "</table>";
### PÉ PAGINACAO###

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

	// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
	$todos_links	= $mult_pag->Construir_Links("strings", "sim");

	// função que limita a quantidade de links no rodape
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

?>