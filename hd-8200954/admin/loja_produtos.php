<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios='cadastros';
include 'autentica_admin.php';

include 'funcoes.php';
include_once "../class/tdocs.class.php";
$tDocs = new TDocs($con, $login_fabrica);

$title = "Cadastros do Sistema";
$layout_menu = "cadastro";
include 'cabecalho.php';

echo $login_master;
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
.Caixa{
	FONT: 8pt Arial ;
	BORDER-RIGHT:     #6699CC 1px solid;
	BORDER-TOP:       #6699CC 1px solid;
	BORDER-LEFT:      #6699CC 1px solid;
	BORDER-BOTTOM:    #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}
.Erro{
	font-family: Verdana;
	font-size: 14px;
	color:#FFF;
	border:#485989 1px solid; 
	background-color: #990000;
}

</style>
<?
//include 'loja_menu.php';

echo "<table width='700' border='0' align='center' cellpadding='0' cellspacing='0' style=' border:#B5CDE8 1px solid;  bordercolor='#d2e4fc'>";
echo "<tr height='40'>";

	echo "<td height='40' colspan='4' align='left'  align='center' class='Titulo' bgcolor='#e6eef7' >&nbsp;&nbsp;<font size='2'>Lista de Produtos Promoção Loja Virtual</font>";
	echo "</td>";
	
echo "</tr>";
//produtos linha a cima
	echo "<TD colspan='4'>";
	/*###################################################################################
	################################### DIV PESQUISA ###################################*/
		$busca = trim($_POST["busca"]);
		$tipo  = trim($_POST["tipo"]);
		$btnG  = trim($_POST["btnG"]);
		?>

		<form method='POST'>
		<table align='center' border='0'>
		<tr>
			<td><input name="busca" size="41" maxlength="2048" value="<?=$busca?>" title="Pesquisar" type="text" ><font size="-1"> <input name="btnG" value="Pesquisar" type="submit"></td>
		</tr>
		<tr>
			<td class='Conteudo'>Pesquisar por: <input type='radio' name='tipo' value='d' checked> Descrição<input type='radio' name='tipo' value='c' <?if($tipo=='c') echo "CHECKED";?>> Referência</td>
		</tr>
		</table>
		</form>
		<?
		if($tipo == 'd' AND strlen($busca) == 0){
				$btnG = "";
		}

		if($tipo == 'c' AND strlen($busca) == 0){
				$btnG = "";
		}

		if (strlen($msg_erro) == 0 and strlen($btnG)>0) {
			//--=== Busca por Descrição =============================================
			if(($tipo == 'd') && (strlen($busca)>0)){
					$buscas = strtoupper($busca);
					$pesquisa = " AND tbl_peca.descricao like '%$buscas%'";
				}
			//--=== Busca por Referencia=============================================
			if(($tipo == 'c') && (strlen($busca)>0)){
					$pesquisa = "AND tbl_peca.referencia like '$busca'";
			}

			
			if (strlen($msg_erro) > 0 ){
				echo "<font color='#FF0000'>$msg_erro</font><br>";
			}else{
			//pega produtos
			$sqlx = "SELECT 
						tbl_peca.peca       ,
						referencia          ,
						descricao           ,
						informacoes         ,
						multiplo_site       ,
						qtde_max_site       ,
						qtde_disponivel_site
					FROM tbl_peca 
					WHERE promocao_site IS TRUE $pesquisa
					AND fabrica = $login_fabrica";
			$xres = pg_exec ($con,$sqlx);

			// ##### PAGINACAO ##### //
			$sqlCount  = "SELECT count(*) FROM (";
			$sqlCount .= $sql;
			$sqlCount .= ") AS count";

			require "_class_paginacao.php";

			// definicoes de variaveis
			$max_links = 11;				// máximo de links à serem exibidos
			$max_res   = 6;				// máximo de resultados à serem exibidos por tela ou pagina
			$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
			$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

			$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

			// ##### PAGINACAO ##### //
			//		echo "$sql";

				for ($i = 0 ; $i < pg_numrows($xres); $i++){
					
					$peca                 = trim(pg_result ($xres,$i,peca));
					$referencia           = trim(pg_result ($xres,$i,referencia));
					$descricao            = trim(pg_result ($xres,$i,descricao));
					$informacoes          = trim(pg_result ($xres,$i,informacoes));
					$multiplo_site        = trim(pg_result ($xres,$i,multiplo_site));
					$qtde_max_site        = trim(pg_result ($xres,$i,qtde_max_site));
					$qtde_disponivel_site = trim(pg_result ($xres,$i,qtde_disponivel_site));
					$tabela_precos = "";
//tbl_tabela.tabela,tbl_tabela.sigla_tabela,
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
					$res2 = pg_exec ($con,$sql2);
					if(pg_numrows($res2)<1) {
						$tabela_precos  = "";
					}else{
						$tabela_precos = "";
						for ($j = 0 ; $j < pg_numrows($res2); $j++){
							$X_preco = number_format(trim(pg_result ($res2,$j,preco)),2,",",".");
						//	$X_table = trim(pg_result ($res2,$j,sigla_tabela));
//							$tabela_precos .= "R$ $X_preco ";
							$tabela_precos .= "<a href='preco_cadastro.php?peca=$peca' target='blank'><strong><font color='#FF0000'>R$ $X_preco</font></strong></a> <BR>";
						}
					}

					if($i%3==0) $cor='#F4EBD7';
					else        $cor='#EFEFEF';
					if($i==4)   $cor='#F4EBD7';
					if($i==0 )echo "<tr class='Conteudo'>";
					if($i%2==0 AND $i<>0) {
						echo "</tr><tr class='Conteudo'>";
					}
					echo "<td  bgcolor='$cor' width='350' valign='top'>";
					echo "<table width='98%' border='0' align='center' cellpadding='5' cellspacing='1' >";
					echo "<tr>";
					echo "<td align='center' bgcolor='$cor' width='110' valign='top'><div class='contenedorfoto'>";

		            $xpecas = $tDocs->getDocumentsByRef($peca, "peca");
		            if (!empty($xpecas->attachListInfo)) {

						$a = 1;
						foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
						    $fotoPeca = $vFoto["link"];
						    if ($a == 1){break;}
						}
						echo "<img src='$fotoPeca' border='0'>
							  <input type='hidden' name='peca_imagem' value='$fotoPeca'>\n";
		            } else {

						if ($dh = opendir('../imagens_pecas/pequena/')) {
							$contador=0;
							while (false !== ($filename = readdir($dh))) {
								if($contador == 1) break;
								if (strpos($filename,$referencia) !== false){
									$contador++;
									//$peca_referencia = ntval($peca_referencia);
									$po = strlen($referencia);
									if(substr($filename, 0,$po)==$referencia){?>
										<img src='../imagens_pecas/pequena/<?echo $filename; ?>' border='0'>
										<input type='hidden' name='peca_imagem' value='<?echo $filename; ?>'>
						<?			}
								}
							}
						}
					}

					// trabalhando no HD 3780 - GUSTAVO
					echo "</div>";
					echo "</td>";

					if(($qtde_disponivel_site) > 0 && strlen($tabela_precos) > 0){
						echo "<td align='left'><font color='#FF0000' size='2'><STRONG>EM PROMOÇÃO</STRONG></font><BR>$descricao";
						echo "<br><font color='#333333'>Referencia: </b>$referencia</b></font>";
						echo "<br><font color='#333333'>Informações: </b>$informacoes</b></font>";
						echo "<br><font color='#333333'>Qtde Disponível: <b>$qtde_disponivel_site</b></font>";
						echo "<br><font color='#333333'>Qtde Max. Site: </b>$qtde_max_site</b></font>";
						echo "<br><font color='#333333'>Qtde Multipla: </b>$multiplo_site</b></font>";
						echo "<BR><font color='#FF0000' size='2'><b>$tabela_precos</b></font>";			
						echo "</td>";

					}else{
						echo "<td  align='left'><font color='#FF0000' size='2'><STRONG>EM PROMOÇÃO</STRONG></font><BR>$descricao";
						echo "<br><font color='#333333'>Referencia: </b>$referencia</b></font>";
						echo "<br><font color='#333333'>Informações: </b>$informacoes</b></font>";
						echo "<br><font color='#333333'><b>Indisponível</b></font>";
						echo "<br><font color='#333333'>Qtde Max. Site: </b>$qtde_max_site</b></font>";
						echo "<br><font color='#333333'>Qtde Multipla: </b>$multiplo_site</b></font>";
						echo "<br><font color='#FF0000' size='2'><b>$tabela_precos</b></font><br></td>";
					}
					echo "</td>";
				echo "</tr>";
				echo "</table>";
				echo "</td>";
				}
			}
		}
	/*################################# FIM DIV PESQUISA ################################
	#####################################################################################*/
	echo "</TD>";

	//pega produtos

if ( strlen($btnG)==0) {
	$sql = "SELECT 
			tbl_peca.peca       ,
			referencia          ,
			descricao           ,
			informacoes         ,
			multiplo_site       ,
			qtde_max_site       ,
			qtde_disponivel_site
			FROM tbl_peca where promocao_site IS TRUE
			AND fabrica = $login_fabrica";

	// ##### PAGINACAO ##### //
$sqlCount  = "SELECT count(*) FROM (";
$sqlCount .= $sql;
$sqlCount .= ") AS count";

require "_class_paginacao.php";

// definicoes de variaveis
$max_links = 11;					// máximo de links à serem exibidos
$max_res   = 10;						// máximo de resultados à serem exibidos por tela ou pagina
$mult_pag  = new Mult_Pag();		// cria um novo objeto navbar
$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

// ##### PAGINACAO ##### //
//		echo "$sql";

for ($i = 0 ; $i < pg_numrows($res); $i++){
	$peca                 = trim(pg_result ($res,$i,peca));
	$referencia           = trim(pg_result ($res,$i,referencia));
	$descricao            = trim(pg_result ($res,$i,descricao));
	$informacoes          = trim(pg_result ($res,$i,informacoes));
	$multiplo_site        = trim(pg_result ($res,$i,multiplo_site));
	$qtde_max_site        = trim(pg_result ($res,$i,qtde_max_site));
	$qtde_disponivel_site = trim(pg_result ($res,$i,qtde_disponivel_site));
//					tbl_tabela.sigla_tabela,
//					tbl_tabela.tabela,
	$sql2 = "SELECT distinct 
					tbl_tabela_item.preco
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
	$res2 = pg_exec ($con,$sql2);
	if(pg_numrows($res2)<1) {
		$tabela_precos  = "";
	}else{
		$tabela_precos = "";
		for ($j = 0 ; $j < pg_numrows($res2); $j++){
			$X_preco = number_format(trim(pg_result ($res2,$j,preco)),2,",",".");
		//	$X_table = trim(pg_result ($res2,$j,sigla_tabela));
			$tabela_precos .= "<a href='preco_cadastro.php?peca=$peca' target='blank'><strong><font color='#FF0000'>R$ $X_preco</font></strong></a> <BR>";
			//<span style='font-size:10px;color:#4D4D4D;font-weight:normal'>(Tbl $X_table)</span><BR>
		}
	}

	if($i%3==0) $cor='#F4EBD7';
	else        $cor='#EFEFEF';
//	if($i==4)   $cor='#F4EBD7';
	if($i==0 )echo "<tr class='Conteudo'>";
	if($i%2==0 AND $i<>0) {
		echo "</tr><tr class='Conteudo'>";
	}
	echo "<td  bgcolor='$cor' width='350' valign='top'>";
	echo "<table width='98%' border='0' align='center' cellpadding='5' cellspacing='1' >";
	echo "<tr>";
	echo "<td align='center' bgcolor='$cor' width='110' valign='top'><div class='contenedorfoto'>";
	$xpecas = $tDocs->getDocumentsByRef($peca, "peca");
    if (!empty($xpecas->attachListInfo)) {

		$a = 1;
		foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
		    $fotoPeca = $vFoto["link"];
		    if ($a == 1){break;}
		}
		echo "<img src='$fotoPeca' border='0'>
			  <input type='hidden' name='peca_imagem' value='$fotoPeca'>\n";
    } else {
		$saida == "";
		if ($dh = opendir('../imagens_pecas/pequena/')) {
			$contador=0;
			while (false !== ($filename = readdir($dh))) {
				if($contador == 1) break;
				if (strpos($filename,$referencia) !== false){
					$contador++;
					//$peca_referencia = ntval($peca_referencia);
					$po = strlen($referencia);
					if(substr($filename, 0,$po)==$referencia){?>
						<img src='../imagens_pecas/pequena/<?echo $filename; ?>' border='0' >
						<input type='hidden' name='peca_imagem' value='<?echo $filename; ?>'>
		<?			}
				}
			}
		}
	}
	echo "</div>";
	echo "</td>";

		if(($qtde_disponivel_site) > 0 && strlen($tabela_precos) > 0){
		
			echo "<td align='left'><font color='#FF0000' size='2'><STRONG>EM PROMOÇÃO</STRONG></font><BR>$descricao";
			echo "<br><font color='#333333'>Referencia: </b>$referencia</b></font>";
			echo "<br><font color='#333333'>Informações: </b>$informacoes</b></font>";
			echo "<br><font color='#333333'>Qtde Disponível: <b>$qtde_disponivel_site</b></font>";
			echo "<br><font color='#333333'>Qtde Max. Site: </b>$qtde_max_site</b></font>";
			echo "<br><font color='#333333'>Qtde Multipla: </b>$multiplo_site</b></font>";
			echo "<BR><font color='#FF0000' size='2'><b>$tabela_precos</b></font>";			
			echo "</td>";

		}else{
			echo "<td align='left'><font color='#FF0000' size='2'><STRONG>EM PROMOÇÃO</STRONG></font><BR>$descricao";
			echo "<br><font color='#333333'>Referencia: </b>$referencia</b></font>";
			echo "<br><font color='#333333'>Informações: </b>$informacoes</b></font>";
			echo "<br><font color='#333333'><b>Indisponível</b></font>";
			echo "<br><font color='#333333'>Qtde Max. Site: </b>$qtde_max_site</b></font>";
			echo "<br><font color='#333333'>Qtde Multipla: </b>$multiplo_site</b></font>";
			echo "<br><font color='#FF0000' size='2'><b>$tabela_precos</b></font><br></td>";
		}
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "</td>";
}
	echo "<tr>";
	echo "<td align='right' bgcolor='#e6eef7' colspan='5' class='Titulo'><a href='javascript:history.back()'>Voltar</a></td>";
	echo "</tr>";

echo "</table>";
### PÉ PAGINACAO###

	echo "<table border='0' align='center'>";
	echo "<tr>";
	echo "<td colspan='9' align='center'>";
		// ##### PAGINACAO ##### //

	// links da paginacao
	echo "<br>";

	if($pagina < $max_links) {
		$paginacao = pagina + 1;
	}else{
		$paginacao = pagina;
	}

	// paginacao com restricao de links da paginacao

	// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
	$todos_links = $mult_pag->Construir_Links("strings", "sim");

	// função que limita a quantidade de links no rodape
	$links_limitados = $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

	for ($n = 0; $n < count($links_limitados); $n++) {
		echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
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