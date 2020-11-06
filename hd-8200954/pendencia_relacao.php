<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';

$title = traduz('consulta.pendencia.de.pecas', $con);
$layout_menu = 'pedido';
include "cabecalho.php";

if($login_fabrica==3){
	echo "<table style=' border: #D3BE96 1px solid; background-color: #FCF0D8 ' align='center' width='700'>";
	echo "<tr>";
	echo "<td height='20' class='Exibe'><b>Foi sincronizada juntamente com a fábrica as pendência de pedidos, e agora você pode realizar suas consultas.</b></td>";
	echo "</tr>";
	echo "</table>";
}
?>
<style type='text/css'>
.texto_avulso{
    font: 14px Arial;
	color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: justify;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
	text-transform: uppercase;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.espaco{
	padding: 0 0 0 140px
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
	text-align:center;
	text-transform: capitalize;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #ACACAC;
	empty-cells:show;
}
</style>
<table width="700" border="0" cellpadding="2" cellspacing="0" align="center">
<form name='frm_pendencia_consulta' action='<? echo $PHP_SELF; ?>' method='get'>
<input type='hidden' name='btn_acao_pesquisa' value=''>
<tr height="22" bgcolor="#F0F0F6">
	<td align='right'>
		<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b><? fecho("peca.para.consulta",$con,$cook_idioma); ?></b></font> &nbsp; 
	</td>
	<td align='left' width='100'>
		<input type='text' name='referencia' value=''>
	</td>
	<td align='left' valign='bottom'>
		<button type="submit" style0'cursor:pointer'
		onclick="if (document.frm_pendencia_consulta.btn_acao_pesquisa.value == '' ) { document.frm_pendencia_consulta.btn_acao_pesquisa.value='continuar' ; document.frm_pendencia_consulta.submit() } else { alert ('<?=traduz('aguarde.submissao', $con)?>') }">
			<?=traduz(array('pesquisar', 'pedido'), $con)?>
		</button>
	</td>
</tr>
<tr height="22" bgcolor="#F0F0F6">
	<td align="center" nowrap colspan='3'>
		<input type='hidden' name='listar' value='todas'>
		<input type='submit' value='<? fecho("listar.todas.as.pecas",$con)?>'>
		<!--<a href='<? echo $PHP_SELF."?listar=todas"; ?>'><? fecho("listar.todas.as.pecas",$con,$cook_idioma); ?></a>-->
	</td>
</tr>
</form>
</table>
<br>

<?
$btn_acao_pesquisa = $_POST['btn_acao_pesquisa'];
if (strlen($_GET['btn_acao_pesquisa']) > 0) $btn_acao_pesquisa = $_GET['btn_acao_pesquisa'];

$listar = $_POST['listar'];
if (strlen($_GET['listar']) > 0) $listar = $_GET['listar'];

$referencia = $_POST['referencia'];
if (strlen($_GET['referencia']) > 0) $referencia = $_GET['referencia'];

if (strlen($referencia) > 0) {
	$referencia = trim($referencia);
	$referencia = str_replace (".","",$referencia);
	$referencia = str_replace ("-","",$referencia);
	$referencia = str_replace ("/","",$referencia);
}

$peca = $_GET['peca'];

if (strlen($peca) == 0) {
	if ((strlen($referencia) > 0 AND $btn_acao_pesquisa == 'continuar') OR strlen($listar) > 0){
		###############################################################################
		# ESTAVA ASSIM, MAS TEM QUE AGRUPAR COM OS NÃO FATURADOS
		$sql = "SELECT tbl_peca.peca                                  ,
					   tbl_peca.referencia                            ,
					   tbl_peca.descricao                             ,
					   sum(tbl_faturamento_item.pendente) AS pendente 
				FROM   tbl_faturamento_item
				JOIN   tbl_faturamento ON  tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
				JOIN   tbl_fabrica     ON  tbl_faturamento.fabrica     = tbl_fabrica.fabrica
				JOIN   tbl_peca        ON  tbl_peca.peca               = tbl_faturamento_item.peca
									   AND tbl_peca.fabrica            = tbl_fabrica.fabrica
				WHERE  tbl_faturamento.posto   = $login_posto
				AND    tbl_faturamento.fabrica = $login_fabrica
				AND    tbl_faturamento_item.pendente > 0 ";
		
		if (strlen($referencia) > 0) $sql .= " AND tbl_peca.referencia_pesquisa ILIKE '%$referencia' ";
		
		$sql .= " GROUP BY  tbl_peca.peca       ,
							tbl_peca.referencia ,
							tbl_peca.descricao  
				ORDER BY    sum(tbl_faturamento_item.pendente) DESC ";
		###############################################################################
		
		###############################################################################
		# BUSCA OS PEDIDOS COM FATURAMENTO E OS PEDIDOS JÁ EXPORTADOS E SEM FATURAMENTO
		#$sql = "SELECT * FROM (
		#				(
		#					SELECT   tbl_peca.peca                 ,
		#							 tbl_peca.referencia           ,
		#							 tbl_peca.descricao            ,
		#							 tbl_faturamento_item.pendente 
		#					FROM     tbl_faturamento_item
		#					JOIN     tbl_faturamento ON tbl_faturamento.faturamento = #tbl_faturamento_item.faturamento
		#					JOIN     tbl_fabrica     ON tbl_faturamento.fabrica     = #tbl_fabrica.fabrica
		#					JOIN     tbl_peca        ON tbl_peca.peca               = tbl_faturamento_item.peca
		#												AND tbl_peca.fabrica            = tbl_fabrica.fabrica
		#					WHERE    tbl_faturamento.posto   = $login_posto
		#					AND      tbl_faturamento.fabrica = $login_fabrica
		#					AND      tbl_faturamento_item.pendente > 0
		#					GROUP BY tbl_peca.peca                 ,
		#							 tbl_peca.referencia           ,
		#							 tbl_peca.descricao            ,
		#							 tbl_faturamento_item.pendente 
		#					ORDER BY tbl_faturamento_item.pendente DESC
		#				)UNION(
		#					SELECT   tbl_peca.peca                         ,
		#							 tbl_peca.referencia                   ,
		#							 tbl_peca.descricao                    ,
		#							 sum(tbl_pedido_item.qtde) AS pendente 
		#					FROM     tbl_pedido_item
		#					JOIN     tbl_pedido  ON  tbl_pedido.pedido   = tbl_pedido_item.pedido
		#					JOIN     tbl_fabrica ON  tbl_fabrica.fabrica = tbl_pedido.fabrica
		#					JOIN     tbl_peca    ON  tbl_peca.peca       = tbl_pedido_item.peca
		#										 AND tbl_peca.fabrica    = tbl_fabrica.fabrica
		#					WHERE    tbl_pedido.posto   = $login_posto
		#					AND      tbl_pedido.fabrica = $login_fabrica
		#					AND      tbl_pedido.exportado NOTNULL
		#					AND      tbl_pedido.pedido NOT IN (
		#							SELECT tbl_faturamento.pedido
		#							FROM   tbl_faturamento
		#							AND    tbl_faturamento.posto   = $login_posto 
		#							WHERE  tbl_faturamento.fabrica = $login_fabrica
		#					)
		#					AND tbl_pedido_item.status_pedido NOT IN (4,13)
		#					GROUP BY tbl_peca.peca       ,
		#							 tbl_peca.referencia ,
		#							 tbl_peca.descricao  
		#					ORDER BY sum(tbl_pedido_item.qtde) DESC
		#				)
		#			) AS x ";
		# HD 55041

		if ($login_fabrica == 87 or $login_fabrica==42) {
    		$cond = "AND qtde > (qtde_faturada + qtde_cancelada)";
		}
		else {
			$cond ="AND    tbl_pedido.pedido NOT IN (
					SELECT tbl_faturamento.pedido
					FROM   tbl_faturamento
					JOIN   tbl_faturamento_item USING (faturamento)
					JOIN   tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca and tbl_peca.fabrica= tbl_faturamento.fabrica
					WHERE  tbl_faturamento.fabrica = $login_fabrica
					AND    tbl_faturamento.posto   = $login_posto)";
		}	
		$sqlTMP = "SELECT tbl_peca.peca ,
						tbl_peca.referencia ,
						tbl_peca.descricao ,
						tbl_pedido_item.qtde,
						case when tbl_pedido.distribuidor = 4311 then tbl_pedido_item.qtde_faturada_distribuidor
						else tbl_pedido_item.qtde_faturada end as faturada,
						tbl_pedido_item.qtde_cancelada,
						tbl_pedido.pedido
						INTO TEMP tmp_pendenrel_peca_$login_posto
						FROM tbl_pedido_item
						JOIN tbl_pedido  ON tbl_pedido.pedido   = tbl_pedido_item.pedido
						JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_pedido.fabrica
						JOIN tbl_peca    ON tbl_peca.peca       = tbl_pedido_item.peca
						                AND tbl_peca.fabrica    = tbl_fabrica.fabrica
						WHERE tbl_pedido.posto = $login_posto
						AND tbl_pedido.fabrica = $login_fabrica";

		if (strlen($referencia) > 0) $sqlTMP .= " AND tbl_peca.referencia_pesquisa ILIKE '%$referencia' ";

		$sqlTMP .="AND tbl_pedido.exportado NOTNULL
				AND (tbl_pedido.status_pedido NOT IN (4,13,14) OR tbl_pedido.status_pedido IS NULL)
				$cond";

		$resTMP = pg_exec($con,$sqlTMP);
		$sql = "SELECT * FROM (
						(
							SELECT   tbl_peca.peca                 ,
									 tbl_peca.referencia           ,
									 tbl_peca.descricao            ,
									 tbl_faturamento_item.pendente 
							FROM     tbl_faturamento_item
							JOIN     tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
							JOIN     tbl_fabrica     ON tbl_faturamento.fabrica     = tbl_fabrica.fabrica
							JOIN     tbl_peca        ON tbl_peca.peca               = tbl_faturamento_item.peca
													AND tbl_peca.fabrica            = tbl_fabrica.fabrica
							WHERE    tbl_faturamento.posto   = $login_posto
							AND      tbl_faturamento.fabrica = $login_fabrica
							AND      tbl_faturamento_item.pendente > 0
							GROUP BY tbl_peca.peca                 ,
									 tbl_peca.referencia           ,
									 tbl_peca.descricao            ,
									 tbl_faturamento_item.pendente 
							ORDER BY tbl_faturamento_item.pendente DESC
						)UNION(
							SELECT  tmp_pendenrel_peca_$login_posto.peca                         ,
								tmp_pendenrel_peca_$login_posto.referencia                   ,
								tmp_pendenrel_peca_$login_posto.descricao                    ,
								sum(qtde - faturada - qtde_cancelada) AS pendente
							FROM     tmp_pendenrel_peca_$login_posto
							JOIN     tbl_pedido  ON  tbl_pedido.pedido   = tmp_pendenrel_peca_$login_posto.pedido
							JOIN     tbl_fabrica ON  tbl_fabrica.fabrica = tbl_pedido.fabrica
							JOIN     tbl_peca    ON  tbl_peca.peca       = tmp_pendenrel_peca_$login_posto.peca AND tbl_peca.fabrica    = tbl_fabrica.fabrica
							WHERE    tbl_pedido.posto   = $login_posto
							AND      tbl_pedido.fabrica = $login_fabrica
							AND      tbl_pedido.exportado NOTNULL
							AND      (tbl_pedido.status_pedido NOT IN (4,13,14) OR tbl_pedido.status_pedido IS NULL)
							GROUP BY tmp_pendenrel_peca_$login_posto.peca       ,
									 tmp_pendenrel_peca_$login_posto.referencia ,
									 tmp_pendenrel_peca_$login_posto.descricao  
							HAVING SUM (qtde - faturada - qtde_cancelada) > 0
							ORDER BY sum(tmp_pendenrel_peca_$login_posto.qtde) DESC
						)
					) AS x ";

		$referencia = strtoupper($referencia);
		#if (strlen($referencia) > 0) $sql .= " WHERE x.referencia = '$referencia' ";
		$sql .= " ORDER BY x.pendente DESC ";

# if($ip == "201.76.78.194"){
#	echo nl2br($sql);
#	}

		$sqlCount  = "SELECT count(*) FROM (";
		$sqlCount .= $sql;
		$sqlCount .= ") AS count";
		// ##### PAGINACAO ##### //
		require "_class_paginacao.php";

		// definicoes de variaveis
		$max_links = 11;				// máximo de links à serem exibidos
		$max_res   = 30;				// máximo de resultados à serem exibidos por tela ou pagina
		$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
		$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

		$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

		// ##### PAGINACAO ##### //

		if (@pg_numrows($res) > 0) {
			echo "<table width='700px' border='0' cellpadding='2' cellspacing='1' align='center' bgcolor='#ffffff' class='tabela'>";
				echo "<tr class='titulo_coluna'>";
					echo "<td>"; fecho("referencia",    $con, $cook_idioma); echo "</td>";
					echo "<td>"; fecho("descricao",     $con, $cook_idioma); echo "</td>";
					echo "<td>"; fecho("qtde.pendente", $con, $cook_idioma); echo "</td>";
				echo "</tr>";
			
				for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
					
					$peca       = trim(pg_result($res,$i,peca));
					$referencia = trim(pg_result($res,$i,referencia));
					$descricao  = trim(pg_result($res,$i,descricao));
					$pendencia  = trim(pg_result($res,$i,pendente));
					
					echo "<tr bgcolor='$cor'>";
						echo "<td><a href='$PHP_SELF?peca=$peca'>$referencia</a></td>";
						echo "<td align='left'>$descricao</font></td>";
						echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$pendencia</font></td>";
					echo "</tr>";
				}
			echo "</table>";
			
			// ##### PAGINACAO ##### //
			// links da paginacao
			echo "<br>";
			
			echo "<div>";
			
			if($pagina < $max_links) { 
				$paginacao = pagina + 1;
			}else{
				$paginacao = pagina;
			}
			
			// paginacao com restricao de links da paginacao
			
			// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
			$todos_links		= $mult_pag->Construir_Links("strings", "sim");
			
			// função que limita a quantidade de links no rodape
			$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);
			
			for ($n = 0; $n < count($links_limitados); $n++) {
				echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
			}
			
			echo "</div>";
			
			$resultado_inicial = ($pagina * $max_res) + 1;
			$resultado_final   = $max_res + ( $pagina * $max_res);
			$registros         = $mult_pag->Retorna_Resultado();
			
			$valor_pagina   = $pagina + 1;
			$numero_paginas = intval(($registros / $max_res) + 1);
			
			if ($valor_pagina == $numero_paginas) $resultado_final = $registros;
			
			if ($registros > 0){
				echo "<br>";
				echo "<div>";
				fecho("resultados.de.%.a.%.do.total.de.%.registros",$con,$cook_idioma,array("<b>$resultado_inicial</b>","<b>$resultado_final</b>","<b>$registros</b>"));
#				echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
				echo "<font color='#cccccc' size='1'>";
				fecho("pagina.%.de.%",$con,$cook_idioma,array("<b>$valor_pagina</b>","<b>$numero_paginas</b>"));
				#echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
				echo "</font>";
				echo "</div>";
			}
			// ##### PAGINACAO ##### //
		}else{
			echo "<p>";
			
			echo "<div class='texto_avulso' style='text-align:center;wqidth:700px'>";
			fecho("nao.foi.encontrado.pecas.pendentes",$con,$cook_idioma);
			#echo "<h4>Não foi encontrado Peças Pendentes.</h4>";
			echo "</div>";
		}
	}
}else{
	###############################################################################
	# ESTAVA ASSIM, MAS TEM QUE AGRUPAR COM OS NÃO FATURADOS
	$sql = "SELECT  tbl_peca.referencia           ,
					tbl_peca.descricao            ,
					tbl_faturamento.pedido        ,
					tbl_faturamento.nota_fiscal   ,
					tbl_faturamento_item.pendente 
			FROM    tbl_faturamento_item
			JOIN    tbl_faturamento ON  tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
			JOIN    tbl_fabrica     ON  tbl_faturamento.fabrica     = tbl_fabrica.fabrica
			JOIN    tbl_peca        ON  tbl_peca.peca               = tbl_faturamento_item.peca
									AND tbl_peca.fabrica            = tbl_fabrica.fabrica
			WHERE   tbl_faturamento.posto     = $login_posto
			AND     tbl_faturamento.fabrica   = $login_fabrica
			AND     tbl_faturamento_item.peca = $peca
			AND     tbl_faturamento_item.pendente > 0
			ORDER BY tbl_faturamento.nota_fiscal DESC";
	###############################################################################
	
	###############################################################################
	# BUSCA OS PEDIDOS COM FATURAMENTO E OS PEDIDOS JÁ EXPORTADOS E SEM FATURAMENTO
	#$sql = "SELECT * FROM (
	#				(
	#					SELECT      tbl_peca.referencia        ,
	#								tbl_peca.descricao         ,
	#								tbl_faturamento.pedido     ,
	#								tbl_faturamento.nota_fiscal,
	#								tbl_faturamento_item.pendente,
	#								NULL AS pedido_blackedecker
	#					FROM        tbl_faturamento_item
	#					JOIN        tbl_faturamento  ON tbl_faturamento.faturamento = #tbl_faturamento_item.faturamento
	#					JOIN        tbl_fabrica      ON tbl_faturamento.fabrica     = #tbl_fabrica.fabrica
	#					JOIN        tbl_peca         ON tbl_peca.peca               = #tbl_faturamento_item.peca
	#												AND tbl_peca.fabrica            = #tbl_fabrica.fabrica
	#					WHERE       tbl_faturamento.posto     = $login_posto
	#					AND         tbl_faturamento.fabrica   = $login_fabrica
	#					AND         tbl_faturamento_item.peca = $peca
	#					AND         tbl_faturamento_item.pendente > 0
	#				)UNION(
	#					SELECT tbl_peca.referencia,
	#								tbl_peca.descricao ,
	#								tbl_pedido.pedido  ,
	#								'' AS nota_fiscal  ,
	#								tbl_pedido_item.qtde,
	#								tbl_pedido.pedido_blackedecker
	#					FROM        tbl_pedido_item
	#					JOIN        tbl_pedido       ON tbl_pedido.pedido            = #tbl_pedido_item.pedido
	#					JOIN        tbl_fabrica      ON tbl_fabrica.fabrica          = #tbl_pedido.fabrica
	#					JOIN        tbl_peca         ON tbl_peca.peca                = #tbl_pedido_item.peca
	#												AND tbl_peca.fabrica             = #tbl_fabrica.fabrica
	#					WHERE       tbl_pedido.posto     = $login_posto
	#					AND         tbl_pedido.fabrica   = $login_fabrica
	#					AND         tbl_pedido_item.peca = $peca
	#					AND         tbl_pedido.exportado NOTNULL
	#					AND         tbl_pedido.pedido NOT IN (
	#						SELECT tbl_faturamento.pedido
	#						FROM   tbl_faturamento
	#			
	#						WHERE  tbl_faturamento.fabrica = $login_fabrica
	#					)
	#				)
	#			) AS x ORDER BY x.nota_fiscal";


	# HD 55041
	$sqlTMP = "SELECT tbl_peca.peca ,
						tbl_peca.referencia ,
						tbl_peca.descricao ,
						tbl_pedido_item.qtde,
						case when tbl_pedido.distribuidor = 4311 then tbl_pedido_item.qtde_faturada_distribuidor
						else tbl_pedido_item.qtde_faturada end as faturada,
						tbl_pedido_item.qtde_cancelada,
						tbl_pedido.pedido,
						tbl_pedido.pedido_cliente
						INTO TEMP tmp_pendenrel_peca_$login_posto
						FROM tbl_pedido_item
						JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido
						JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_pedido.fabrica
						JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca AND tbl_peca.fabrica = tbl_fabrica.fabrica
						WHERE tbl_pedido.posto = $login_posto
						AND tbl_pedido.fabrica = $login_fabrica
						AND tbl_pedido.exportado NOTNULL
						AND (tbl_pedido.status_pedido NOT IN (4,13,14) OR tbl_pedido.status_pedido IS NULL)
						$cond ";
	$resTMP = pg_exec($con,$sqlTMP);

	$sql = "SELECT * FROM (
					(
						SELECT      tbl_peca.referencia        ,
									tbl_peca.descricao         ,
									tbl_pedido.pedido_cliente,
									tbl_faturamento.pedido     ,
									tbl_faturamento.nota_fiscal,
									tbl_faturamento_item.pendente,
									NULL AS pedido_blackedecker
						FROM tbl_faturamento_item
						JOIN tbl_faturamento ON  tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
						JOIN tbl_fabrica     ON  tbl_faturamento.fabrica     = tbl_fabrica.fabrica
						JOIN tbl_peca        ON  tbl_peca.peca               = tbl_faturamento_item.peca
						                     AND tbl_peca.fabrica            = tbl_fabrica.fabrica
						LEFT JOIN tbl_pedido ON  tbl_pedido.pedido           = tbl_faturamento.pedido
						                     AND tbl_pedido.fabrica          = tbl_faturamento.fabrica
						WHERE       tbl_faturamento.posto     = $login_posto
						AND         tbl_faturamento.fabrica   = $login_fabrica
						AND         tbl_faturamento_item.peca = $peca
						AND         tbl_faturamento_item.pendente > 0
					)UNION(
						SELECT tmp_pendenrel_peca_$login_posto.referencia,
									tmp_pendenrel_peca_$login_posto.descricao ,
									tmp_pendenrel_peca_$login_posto.pedido_cliente,
									tbl_pedido.pedido  ,
									'' AS nota_fiscal  ,
									sum(qtde - faturada - qtde_cancelada) as pendente,
									tbl_pedido.pedido_blackedecker
						FROM        tmp_pendenrel_peca_$login_posto
						JOIN        tbl_pedido       ON tbl_pedido.pedido            = tmp_pendenrel_peca_$login_posto.pedido
						JOIN        tbl_fabrica      ON tbl_fabrica.fabrica          = tbl_pedido.fabrica
						JOIN        tbl_peca         ON tbl_peca.peca                = tmp_pendenrel_peca_$login_posto.peca
													AND tbl_peca.fabrica             = tbl_fabrica.fabrica
						WHERE       tbl_pedido.posto     = $login_posto
						AND         tbl_pedido.fabrica   = $login_fabrica
						AND         tmp_pendenrel_peca_$login_posto.peca = $peca
						AND         tbl_pedido.exportado NOTNULL
						AND        (tbl_pedido.status_pedido NOT IN (4,13,14) OR tbl_pedido.status_pedido IS NULL)
						group by tmp_pendenrel_peca_$login_posto.referencia,
									tmp_pendenrel_peca_$login_posto.descricao ,
									tmp_pendenrel_peca_$login_posto.pedido_cliente,
									tbl_pedido.pedido  ,
									nota_fiscal  ,
									tbl_pedido.pedido_blackedecker
						having sum(qtde - faturada - qtde_cancelada)>0
						
					)
				) AS x ORDER BY x.nota_fiscal";

	//echo nl2br($sql);
	$res = pg_exec($con,$sql);

		echo "<table width='700px' border='0' cellpadding='2' cellspacing='1' align='center' bgcolor='#ffffff' class='tabela'>";
			echo "<tr class='titulo_coluna'>";
				if ($login_fabrica == 87) {
					echo "<td>"; fecho("ordem.de.compra",$con,$cook_idioma); echo "</td>";
				}

				echo "<td>"; fecho("pedido",      $con, $cook_idioma); echo "</td>";
				echo "<td>"; fecho("nota.fiscal", $con, $cook_idioma); echo "</td>";
				echo "<td>"; fecho("peca",        $con, $cook_idioma); echo "</td>";
				echo "<td>"; fecho("pendencia",   $con, $cook_idioma); echo "</td>";
			echo "</tr>";

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
			
			$pedido_cliente      = trim(pg_result($res, $i, 'pedido_cliente'));
			$referencia          = trim(pg_result($res, $i, 'referencia'));
			$descricao           = trim(pg_result($res, $i, 'descricao'));
			$pedido              = trim(pg_result($res, $i, 'pedido'));
			$nota_fiscal         = trim(pg_result($res, $i, 'nota_fiscal'));
			$pendencia           = trim(pg_result($res, $i, 'pendente'));
			$pedido_blackedecker = trim(pg_result($res, $i, 'pedido_blackedecker'));
			
			echo "<tr bgcolor='$cor'>";
				echo "</td>";
				if ($login_fabrica == 87) {
					echo "<td>$pedido_cliente</td>";
				}
				echo "<td align='center'><a href='pedido_finalizado.php?pedido=$pedido'>";
					if ($login_fabrica == 1) echo $pedido_blackedecker; else echo $pedido;
				echo "</a></td>";
				if (strlen($nota_fiscal) > 0) {
					echo "<td align='center'><a href='nota_fiscal_detalhe.php?nota_fiscal=$nota_fiscal'>$nota_fiscal</a></td>";
				}else{
					echo "<td align='center'>$nota_fiscal</td>";
				}
				echo "<td align='left'>$referencia - $descricao</td>";
				echo "<td align='center'>$pendencia</td>";
			echo "</tr>";
		}
	echo "</table>";

	echo "<center><br><button type='button' onclick='history.back()'>" . traduz('voltar', $con) . "</button></center>";
}
?>
<p>
<? include "rodape.php"; ?>
