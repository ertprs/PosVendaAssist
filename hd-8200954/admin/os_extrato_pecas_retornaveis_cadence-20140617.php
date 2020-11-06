<?
//arquivo alterado por takashi em 17/08/2006. Aparecia com a somatoria errada. Qdo agrupava por pe&ccedil;a o valor total ficava diferente e o valor das pecas tambem. Arquivo anterior renomeado para os_extrato_pecas_retornaveis_ant.php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$extrato = trim($_GET['extrato']);
if (strlen($extrato) == 0) $extrato = trim($_POST['extrato']);

$servico_realizado = trim($_GET['servico_realizado']);
if (strlen($servico_realizado) == 0) $servico_realizado = trim($_POST['servico_realizado']);

if(strlen($extrato) == 0){
	header("Location: os_extrato.php");
	exit;
}
if($login_fabrica==2 and $login_posto == 6359){
	header("Location: extrato_posto_lgr.php?extrato=$extrato");
	exit;
}
if($login_fabrica==6){
	header("Location: os_extrato_pecas_retornaveis_tectoy.php?extrato=$extrato");
	exit;
}
if($login_fabrica==24){
	header("Location: os_extrato_pecas_retornaveis_suggar.php?extrato=$extrato");
	exit;
}
if($login_fabrica==43){
	header("Location: extrato_posto_lgr.php?extrato=$extrato");
	exit;
}

/* Liberado provisoriamente para testes para a NKS - Fabio - Solicitado por Ronaldo */
if($login_fabrica==45 AND $extrato == 329130 AND isset($_COOKIE['cook_admin'])){
	header("Location: extrato_posto_lgr.php?extrato=$extrato");
	exit;
}
if($login_fabrica==50){
	header("Location: extrato_posto_lgr.php?extrato=$extrato");
	exit;
}
if($login_fabrica>51){
	header("Location: extrato_posto_lgr.php?extrato=$extrato");
	exit;
}
$msg_erro = "";

$layout_menu = "os";
$title = "Rela&ccedil;&atilde;o de Pe&ccedil;as Retorn&aacute;veis ";
	if ($login_fabrica == 3) {
		$title .= "/ do Estoque ";
	}
$title .= "no Extrato";

if (strlen($_GET["agrupar"]) > 0) {
	$agrupar = trim($_GET["agrupar"]);
}else{
	$agrupar = "false";
}

include "cabecalho.php";

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#000000;
	background-color: #d9e2ef
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>

<p>
<?
if(strlen($msg_erro) > 0){
	echo "<TABLE width=\"650\" align='center' border=0>";
	echo "<TR>";

	echo "<TD align='center' class='error'>$msg_erro</TD>";

	echo "</TR>";
	echo "</TABLE>";
}

if ($login_fabrica == 11 and $ip <> '189.18.85.78') {
	echo "<BR><BR>PROGRAMA DESATIVADO TEMPORARIAMENTE";
	exit;
}

if ($login_fabrica == 2){
	echo "<table width='650' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
	echo "<tr class='menu_top'>\n";
	echo "<td colspan=7 align='center'><font color='#FF0000'<b>AGUARDAR UM BOM VOLUME DE PE&Ccedil;AS COM DEFEITOS PARA O ENVIO DAS MESMAS, ENTRAR EM CONTATO POR EMAIL PARA VERIFICAR O MEIO DE TRANSPORTE A SER ENVIADO.</b></font></td>\n";
	echo "</tr>\n";
	echo "</table>\n";
}

echo "<TABLE width=\"650\" align='center' border=0>";
echo "<TR class='menu_top'>\n";

if ($sistema_lingua=='ES')
	echo "<TD align='center'><a href='$PHP_SELF?agrupar=true&extrato=$extrato'><font color='#000000'>Agrupar por repuesto</font></a></TD>\n";
else
	echo "<TD align='center'><a href='$PHP_SELF?agrupar=true&extrato=$extrato'><font color='#000000'>Agrupar por pe&ccedil;a</font></a></TD>\n";

if ($sistema_lingua=='ES')
	echo "<TD align='center'><a href='$PHP_SELF?agrupar=false&extrato=$extrato'><font color='#000000'>No agrupar</font></a></TD>\n";
else
	echo "<TD align='center'><a href='$PHP_SELF?agrupar=false&extrato=$extrato'><font color='#000000'>N&atilde;o agrupar</font></a></TD>\n";

echo "</TR>";
echo "</TABLE>";

echo "<p>";

if ($login_fabrica == 3) { // BRITANIA
	if ($agrupar == "false") {
		$sql = "SELECT	tbl_os.os                                                      ,
						tbl_os.sua_os                                                  ,
						tbl_os.consumidor_nome                                         ,
						tbl_produto.descricao  AS produto_nome                         ,
						tbl_produto.referencia AS produto_referencia                   ,
						tbl_peca.referencia    AS peca_referencia                      ,
						tbl_peca.descricao     AS peca_nome                            ,
						tbl_tabela_item.preco                                          ,
						tbl_os_item.qtde                                               ,
						to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao
				FROM    tbl_os
				JOIN    tbl_os_extra             ON tbl_os.os                               = tbl_os_extra.os
				JOIN    tbl_produto              ON tbl_os.produto                          = tbl_produto.produto
				JOIN    tbl_linha                ON tbl_linha.linha                         = tbl_produto.linha
												AND tbl_linha.fabrica                       = $login_fabrica
				JOIN    tbl_os_produto           ON tbl_os.os                               = tbl_os_produto.os
				JOIN    tbl_os_item              ON tbl_os_produto.os_produto               = tbl_os_item.os_produto
				JOIN    tbl_servico_realizado    ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
				JOIN    tbl_peca                 ON tbl_os_item.peca                        = tbl_peca.peca
				JOIN    tbl_extrato              ON tbl_extrato.extrato                     = tbl_os_extra.extrato
												AND tbl_extrato.fabrica                     = $login_fabrica
				JOIN    tbl_posto_linha          ON tbl_posto_linha.posto                   = tbl_extrato.posto
												AND tbl_posto_linha.linha                   = tbl_linha.linha
				JOIN    tbl_tabela_item          ON tbl_tabela_item.peca                    = tbl_os_item.peca AND tbl_tabela_item.tabela = tbl_posto_linha.tabela
				WHERE   tbl_os_extra.extrato = $extrato
				AND     tbl_extrato.fabrica  = $login_fabrica
				AND     tbl_os_item.liberacao_pedido    IS NOT FALSE";
				if($login_fabrica<>14){ $sql .=" AND     tbl_peca.devolucao_obrigatoria      IS TRUE";}
				$sql .=" AND     tbl_servico_realizado.gera_pedido   IS TRUE
				AND     tbl_servico_realizado.troca_de_peca IS TRUE ";

				if (strlen($servico_realizado) > 0) $sql .= "AND tbl_servico_realizado.servico_realizado = $servico_realizado ";

				$sql .= "ORDER BY lpad(tbl_os.sua_os,10,' '), tbl_tabela_item.preco;";
	}else{
		$join_preco = "tbl_os_item.preco ";

		$sql = "SELECT  tbl_peca.referencia        AS peca_referencia                      ,
						tbl_peca.descricao         AS peca_nome                            ,
						$join_preco                                                        ,
						sum(tbl_os_item.qtde)      AS qtde                                 ,
						to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao
				FROM    tbl_os
				JOIN    tbl_os_extra             ON tbl_os.os                               = tbl_os_extra.os
				JOIN    tbl_produto              ON tbl_os.produto                          = tbl_produto.produto
				JOIN    tbl_linha                ON tbl_linha.linha                         = tbl_produto.linha
												AND tbl_linha.fabrica                       = $login_fabrica
				JOIN    tbl_os_produto           ON tbl_os.os                               = tbl_os_produto.os
				JOIN    tbl_os_item              ON tbl_os_produto.os_produto               = tbl_os_item.os_produto
				JOIN    tbl_servico_realizado    ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
				JOIN    tbl_peca                 ON tbl_os_item.peca                        = tbl_peca.peca
				JOIN    tbl_extrato              ON tbl_extrato.extrato                     = tbl_os_extra.extrato
												AND tbl_extrato.fabrica                     = $login_fabrica
				JOIN    tbl_posto_linha          ON tbl_posto_linha.tabela                  = tbl_tabela_item.tabela
												AND tbl_posto_linha.posto                   = tbl_extrato.posto
												AND tbl_posto_linha.linha                   = tbl_linha.linha
				WHERE   tbl_os_extra.extrato = $extrato
				AND     tbl_extrato.fabrica  = $login_fabrica
				AND     tbl_os_item.liberacao_pedido    IS NOT FALSE";
				if($login_fabrica<>14){ $sql .=" AND     tbl_peca.devolucao_obrigatoria      IS TRUE";}
				$sql .=" AND     tbl_servico_realizado.gera_pedido   IS TRUE
				AND     tbl_servico_realizado.troca_de_peca IS TRUE ";

				if (strlen($servico_realizado) > 0) $sql .= "AND tbl_servico_realizado.servico_realizado = $servico_realizado ";

				$sql .= "GROUP BY 	tbl_peca.referencia   ,
							tbl_peca.descricao    ,
							to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY');";
	}
//if ($ip=='201.42.44.145') echo $sql; exit;
	$res = pg_exec ($con,$sql);
	$totalRegistros = pg_numrows($res);

	echo "<TABLE width='650' align='center' border='0' cellspacing='1' cellpadding='1'>\n";

	if ($totalRegistros > 0){
		echo "<TR class='menu_top'>\n";

		if ($agrupar == "false") $colspan = "5";
		if ($agrupar == "true")  $colspan = "3";

		echo "<TD colspan='$colspan' align = 'center'>";

		if ($sistema_lingua == 'ES')
			echo "EXTRACTO $extrato GENERADO EN " . pg_result ($res,0,data_geracao) ;
		else
			echo "EXTRATO $extrato GERADO EM " . pg_result ($res,0,data_geracao) ;

		echo "</TD>";

		echo "</TR>\n";

		echo "<TR class='menu_top'>\n";
		echo "<form name='frmServicoRealizado' method='post' action='$PHP_SELF'>\n";
		echo "<input type='hidden' name='extrato' value='$extrato'>\n";

		if ($agrupar == "false") $colspan = "5";
		if ($agrupar == "true")  $colspan = "3";

		echo "<TD colspan='$colspan' align = 'center'>";
		echo "Exibir pe&ccedil;as com o Servi&ccedil;o Realizado ";
		echo "<select class='frm' size='1' name='servico_realizado' style='width:150px	'>";
		echo "<option selected></option>";

		$sql = "SELECT *
				FROM   tbl_servico_realizado
				WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";

		if ($login_pede_peca_garantia == 't') {
			$sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
		}

		$sql .= "AND tbl_servico_realizado.ativo   = 't' ORDER BY descricao ";
//if ($ip=='201.42.44.145') echo "1"; echo $sql;
		$resSR = pg_exec ($con,$sql) ;

		for ($x = 0 ; $x < pg_numrows ($resSR) ; $x++ ) {
			echo "<option ";
			if ($servico_realizado == pg_result ($resSR,$x,servico_realizado)) echo " selected ";
			echo " value='" . pg_result ($resSR,$x,servico_realizado) . "'>" ;
			echo pg_result ($resSR,$x,descricao) ;
			echo "</option>";
		}

		echo "</select>";
		echo "<img src='imagens/btn_continuar.gif' onclick='document.frmServicoRealizado.submit();' style='cursor:pointer;'>";
		echo "</form>\n";
		echo "</TD>";

		echo "</TR>\n";

		echo "<TR class='menu_top'>\n";

		echo "</TR>\n";
		echo "<TR class='menu_top'>\n";

		if ($agrupar == "false") {
			echo "<TD align='center' >OS</TD>\n";
			echo "<TD align='center' >CLIENTE1</TD>\n";
		}

		echo "<TD align='center' >PE&Ccedil;A</TD>\n";
		echo "<TD align='center' >QTDE</TD>\n";
		echo "<TD align='center' >VALOR</TD>\n";

		echo "</TR>\n";

		$soma_preco = 0;

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
			if ($agrupar == "false") {
				$os					= trim(pg_result ($res,$i,os));
				$sua_os				= trim(pg_result ($res,$i,sua_os));
				$consumidor			= trim(pg_result ($res,$i,consumidor_nome));
				$produto_nome		= trim(pg_result ($res,$i,produto_nome));
				$produto_referencia	= trim(pg_result ($res,$i,produto_referencia));
			}

			$peca_referencia	= trim(pg_result ($res,$i,peca_referencia));
			$peca_nome			= trim(pg_result ($res,$i,peca_nome));
			$preco				= trim(pg_result ($res,$i,preco));
			$qtde				= trim(pg_result ($res,$i,qtde));
			if($qtde>1){$preco = $preco*$qtde;}
			$soma_preco			= $soma_preco + $preco;
			$consumidor			= strtoupper($consumidor);
			$preco				= number_format($preco,2,",",".");

			$cor = "#d9e2ef";
			$btn = 'amarelo';

			if ($i % 2 == 0){
				$cor = '#F1F4FA';
				$btn = 'azul';
			}

			if (strstr($matriz, ";" . $i . ";")) {
				$cor = '#E49494';
			}

			if (strlen ($sua_os) == 0) $sua_os = $os;

			echo "<TR class='table_line' style='background-color: $cor;'>\n";

			if ($agrupar == "false") {
				echo "<TD align='center' nowrap><a href='os_press.php?os=$os' target='_blank'><font color='#000000'>$sua_os</font></a></TD>\n";
				echo "<TD align='left' nowrap>$consumidor</TD>\n";
			}

			echo "<TD align='left' nowrap>$peca_referencia - $peca_nome</TD>\n";
			echo "<TD align='center' nowrap>$qtde</TD>\n";
			echo "<TD align='right' nowrap style='padding-right:3px'>$preco</TD>\n";

			echo "</TR>\n";
		}

		echo "<TR class='table_line' style='background-color: $cor;'>\n";

		if ($agrupar == "false") $colspan = '4';
		if ($agrupar == "true")  $colspan = '2';

		echo "<TD align='center' nowrap colspan='$colspan'><b>TOTAL</b></TD>\n";
		echo "<TD align='right' nowrap style='padding-right:3px'>". number_format($soma_preco,2,",",".") ."</TD>\n";

		echo "</TR>\n";
	}

	echo "</TABLE>\n";
}else{
	if ($agrupar == "false") {

		$join_preco = "tbl_os_item.preco ";
		if ($login_fabrica == 6 or $login_fabrica == 35) {
			$join_preco = "(SELECT preco FROM tbl_tabela_item JOIN tbl_tabela USING (tabela) WHERE tbl_tabela.fabrica = $login_fabrica AND tbl_tabela_item.peca = tbl_os_item.peca AND tbl_tabela.ativa ORDER BY preco DESC LIMIT 1) AS preco " ;
            if($login_fabrica == 35){

		// data de corte LGR - 2014-05-01
		$sqlDataGeracao = "SELECT extrato from tbl_extrato where extrato = $extrato and data_geracao < '2014-05-01'";
		$qryDataGeracao = pg_query($con, $sqlDataGeracao);

		if (pg_num_rows($qryDataGeracao) > 0) {
			$join_fatura = '';
			$cond_fatura = '';
		} else {
			$join_fatura = "
			    JOIN    tbl_faturamento_item    ON tbl_faturamento_item.os_item = tbl_os_item.os_item
			    JOIN    tbl_faturamento         ON tbl_faturamento.faturamento  = tbl_faturamento_item.faturamento
			";
			$cond_fatura = " AND tbl_faturamento.emissao < tbl_extrato.data_geracao::DATE ";
		}
            }
		}

                $distinct = '';
                if ($login_fabrica == '35') {
                	$distinct = 'DISTINCT';
		}

		$sql = "SELECT	$distinct tbl_os.os                                                      ,
						tbl_os.sua_os                                                  ,
						tbl_os.consumidor_nome                                         ,
						tbl_produto.descricao  AS produto_nome                         ,
						tbl_produto.referencia AS produto_referencia                   ,
						tbl_peca.referencia    AS peca_referencia                      ,
						tbl_peca.descricao     AS peca_nome                            ,
						tbl_os_item.qtde       AS qtde                                 ,
						$join_preco                                                    ,
						to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao
				FROM    tbl_os
				JOIN    tbl_os_extra             ON tbl_os.os                               = tbl_os_extra.os
				JOIN    tbl_produto              ON tbl_os.produto                          = tbl_produto.produto
				JOIN    tbl_os_produto           ON tbl_os.os                               = tbl_os_produto.os
				JOIN    tbl_os_item              ON tbl_os_produto.os_produto               = tbl_os_item.os_produto
                        $join_fatura
				JOIN    tbl_servico_realizado    ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
				JOIN    tbl_peca                 ON tbl_os_item.peca                        = tbl_peca.peca
				JOIN    tbl_extrato              ON tbl_extrato.extrato                     = tbl_os_extra.extrato
				LEFT JOIN    tbl_posto_linha          ON tbl_posto_linha.linha = tbl_produto.linha AND tbl_posto_linha.posto = tbl_os.posto
				WHERE   tbl_os_extra.extrato = $extrato
				AND     tbl_extrato.fabrica  = $login_fabrica ";

				//hd 14981 20041
				if ($login_fabrica <> 35 AND $login_fabrica <>14 AND $login_fabrica <>51) {
					$sql .= "AND     tbl_os_item.liberacao_pedido_analisado  IS TRUE ";
				}elseif ($login_fabrica ==14) {
					// HD 25148
					$sql .= "AND     tbl_os_item.liberacao_pedido IS TRUE ";
				}

				if($login_fabrica == 35 ){ /*HD 48868*/
					$sql .= "AND ( (tbl_servico_realizado.gera_pedido IS TRUE AND tbl_servico_realizado.troca_de_peca IS TRUE)
								OR tbl_peca.produto_acabado IS TRUE
							)
							AND (tbl_peca.devolucao_obrigatoria IS TRUE or tbl_peca.produto_acabado IS TRUE)
							$cond_fatura
							";
					$orderby = " ORDER BY tbl_os.os ";
				}else{
					$sql .= "AND     tbl_servico_realizado.gera_pedido   IS TRUE ";
					if($login_fabrica<>14){ $sql .=" AND     tbl_peca.devolucao_obrigatoria      IS FALSE";}
					$sql .=" AND     tbl_servico_realizado.troca_de_peca IS TRUE ";
                                       
					$orderby = " ORDER BY lpad(tbl_os.sua_os,10,' '), tbl_os_item.preco ";
				}

				if (strlen($servico_realizado) > 0) $sql .= "AND tbl_servico_realizado.servico_realizado = $servico_realizado ";

				$sql .= "$orderby;";

	/*HD 11259 10/1/2008
	foi trocado
	AND     tbl_os_item.liberacao_pedido    IS NOT FALSE
	AND     tbl_servico_realizado.gera_pedido   IS FALSE
	por
	AND     tbl_os_item.liberacao_pedido_analisado  IS TRUE
	AND     tbl_servico_realizado.gera_pedido   IS TRUE
	*/
	}else{
	//agrupado
		$join_preco = "tbl_os_item.preco ";
		$group_preco = ",tbl_os_item.preco ";
		if ($login_fabrica == 6 or $login_fabrica == 35) {
			$join_preco = "(SELECT preco FROM tbl_tabela_item JOIN tbl_tabela USING (tabela) WHERE tbl_tabela.fabrica = $login_fabrica AND tbl_tabela_item.peca = tbl_peca.peca AND tbl_tabela.ativa ORDER BY preco DESC LIMIT 1) AS preco " ;
			$group_preco = "";
		}


		$sql = "SELECT	tbl_produto.descricao      AS produto_nome                         ,
						tbl_produto.referencia     AS produto_referencia                   ,
						tbl_peca.peca                                                      ,
						tbl_peca.referencia        AS peca_referencia                      ,
						tbl_peca.descricao         AS peca_nome                            ,
						$join_preco                                                        ,
						sum(tbl_os_item.qtde)      AS qtde                                 ,
						to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao
				FROM    tbl_os
				JOIN    tbl_os_extra             ON tbl_os.os                               = tbl_os_extra.os
				JOIN    tbl_produto              ON tbl_os.produto                          = tbl_produto.produto
				JOIN    tbl_linha                ON tbl_linha.linha                         = tbl_produto.linha
												AND tbl_linha.fabrica                       = $login_fabrica
				JOIN    tbl_os_produto           ON tbl_os.os                               = tbl_os_produto.os
				JOIN    tbl_os_item              ON tbl_os_produto.os_produto               = tbl_os_item.os_produto
				JOIN    tbl_servico_realizado    ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
				JOIN    tbl_peca                 ON tbl_os_item.peca                        = tbl_peca.peca
				JOIN    tbl_extrato              ON tbl_extrato.extrato                     = tbl_os_extra.extrato
												AND tbl_extrato.fabrica                     = $login_fabrica
				WHERE   tbl_os_extra.extrato = $extrato
				AND     tbl_extrato.fabrica  = $login_fabrica ";

				//hd 14981 20041
				if ($login_fabrica <> 35  and $login_fabrica <>14 AND $login_fabrica <> 51) {
					$sql .= "AND     tbl_os_item.liberacao_pedido_analisado  IS TRUE ";
				}elseif ($login_fabrica ==14) {
					$sql .= "AND     tbl_os_item.liberacao_pedido IS TRUE ";
				}

				if($login_fabrica == 35 ){ /*HD 48868*/
					$sql .= "AND ( (tbl_servico_realizado.gera_pedido IS TRUE AND tbl_servico_realizado.troca_de_peca IS TRUE)
								OR tbl_peca.produto_acabado IS TRUE
							)
							AND (tbl_peca.devolucao_obrigatoria IS TRUE or tbl_peca.produto_acabado IS TRUE)";
				}else{
					$sql .= "AND    tbl_servico_realizado.gera_pedido   IS TRUE
							AND     tbl_servico_realizado.troca_de_peca IS TRUE
							AND     tbl_peca.devolucao_obrigatoria      IS FALSE ";
				}
				if (strlen($servico_realizado) > 0) $sql .= "AND tbl_servico_realizado.servico_realizado = $servico_realizado ";

				$sql .= "GROUP BY   tbl_produto.descricao ,
							tbl_produto.referencia,
							tbl_peca.peca         ,
							tbl_peca.referencia   ,
							tbl_peca.descricao    ,
							to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY')
							$group_preco
				ORDER BY   tbl_peca.referencia";

	/*HD 11259 10/1/2008
	foi trocado
	AND     tbl_os_item.liberacao_pedido    IS NOT FALSE
	AND     tbl_servico_realizado.gera_pedido   IS FALSE
	por
	AND     tbl_os_item.liberacao_pedido_analisado  IS TRUE
	AND     tbl_servico_realizado.gera_pedido   IS TRUE
	*/


	}
//if ($ip=='201.71.54.144') echo $sql;
//if ($ip=='200.228.76.93') echo "2"; echo $sql;
	$res = pg_exec ($con,$sql);
	$totalRegistros = pg_numrows($res);
//	echo "$sql";
	echo "<TABLE width='650' align='center' border='0' cellspacing='1' cellpadding='1'>\n";

	if ($totalRegistros > 0){
		echo "<TR class='menu_top'>\n";

		if ($agrupar == "false") $colspan = "5";
		if ($agrupar == "true")  $colspan = "3";

		echo "<TD colspan='$colspan' align = 'center'>";
		if ($sistema_lingua=='ES')
			echo "EXTRACTO $extrato GENERADO EN " . pg_result ($res,0,data_geracao) ;
		else
			echo "EXTRATO $extrato GERADO EM " . pg_result ($res,0,data_geracao) ;

		echo "</TD>";

		echo "</TR>\n";

		echo "<TR class='menu_top'>\n";

		if ($sistema_lingua=='ES')
			echo "<TD colspan='$colspan' align = 'center'>Repuesto del estoq que generan cr&eacute;dito pero no necesitan ser devolvidas fisicamiente a la planta.</TD>";
		else
			//hd 35590
			if($login_fabrica <> 35 and $login_fabrica <> 14){
				echo "<TD colspan='$colspan' align = 'center'>Pe&ccedil;as do estoque que geram Cr&eacute;dito mas n&atilde;o<br> precisam ser devolvidas fisicamente para a F&aacute;brica.</TD>";
		}else{
				echo "<TD colspan='$colspan' align = 'center'>Pe&ccedil;as Retorn&aacute;veis.</TD>";
		}
		echo "</TR>\n";
		echo "<TR class='menu_top'>\n";

		if ($agrupar == "false") {
			echo "<TD align='center' >OS</TD>\n";
			echo "<TD align='center' >CLIENTE</TD>\n";
		}

		if ($sistema_lingua=='ES')
			echo "<TD align='center' >REPUESTO</TD>\n";
		else
			echo "<TD align='center' >PE&Ccedil;A</TD>\n";
		echo "<TD align='center' >QTDE</TD>\n";
		echo "<TD align='center' >VALOR</TD>\n";

		echo "</TR>\n";

		$soma_preco = 0;
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
			if ($agrupar == "false") {
				$os					= trim(pg_result ($res,$i,os));
				$sua_os				= trim(pg_result ($res,$i,sua_os));
				$consumidor			= trim(pg_result ($res,$i,consumidor_nome));
			}
			$produto_nome		= trim(pg_result ($res,$i,produto_nome));
			$produto_referencia	= trim(pg_result ($res,$i,produto_referencia));
			$peca_referencia	= trim(pg_result ($res,$i,peca_referencia));
			$peca_nome			= trim(pg_result ($res,$i,peca_nome));
			$preco				= trim(pg_result ($res,$i,preco));
			$qtde				= trim(pg_result ($res,$i,qtde));
			if($qtde>1){$preco = $preco*$qtde;}
			$soma_preco			= $soma_preco + $preco;
			$consumidor			= strtoupper($consumidor);
			$preco				= number_format($preco,2,",",".");

			$cor = "#d9e2ef";
			$btn = 'amarelo';

			if ($i % 2 == 0){
				$cor = '#F1F4FA';
				$btn = 'azul';
			}

			if (strstr($matriz, ";" . $i . ";")) {
				$cor = '#E49494';
			}

			if (strlen ($sua_os) == 0) $sua_os = $os;

			echo "<TR class='table_line' style='background-color: $cor;'>\n";

			if ($agrupar == "false") {
				echo "<TD align='center' nowrap><a href='os_press.php?os=$os' target='_blank'><font color='#000000'>$sua_os</font></a></TD>\n";
				echo "<TD align='left' nowrap>$consumidor</TD>\n";
			}

			echo "<TD align='left' nowrap>$peca_referencia - $peca_nome</TD>\n";
			echo "<TD align='center' nowrap>$qtde</TD>\n";
			echo "<TD align='right' nowrap style='padding-right:3px'>$preco</TD>\n";

			echo "</TR>\n";
		}
		echo "<TR class='table_line' style='background-color: $cor;'>\n";

		if ($agrupar == "false") $colspan = '4';
		if ($agrupar == "true")  $colspan = '2';

		echo "<TD align='center' nowrap colspan='$colspan'><b>TOTAL</b></TD>\n";
		echo "<TD align='right' nowrap style='padding-right:3px'>". number_format($soma_preco,2,",",".") ."</TD>\n";

		echo "</TR>\n";
	}

	echo "</TABLE>\n";

	echo "<br>";

	if ($agrupar == "false") {
		// colocar if para verificar se &eacute; da tectoy, se for, sql ser&aacute; diferente para pegar valores
		if ($login_fabrica == 6){
			$sql = "SELECT x.sua_os, x.preco, x.*
					FROM (
						SELECT  distinct
								tbl_os.os                                                      ,
								tbl_os.sua_os                                                  ,
								tbl_os.consumidor_nome                                         ,
								tbl_produto.descricao  AS produto_nome                         ,
								tbl_produto.referencia AS produto_referencia                   ,
								tbl_peca.referencia    AS peca_referencia                      ,
								tbl_peca.descricao     AS peca_nome                            ,
								(SELECT preco FROM tbl_tabela_item JOIN tbl_tabela USING (tabela) WHERE tbl_tabela.fabrica = $login_fabrica AND tbl_tabela_item.peca = tbl_os_item.peca AND tbl_tabela.ativa ORDER BY preco DESC LIMIT 1) AS preco ,
								tbl_os_item.qtde                                               ,
								to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao
						FROM    tbl_os
						JOIN    tbl_os_extra             ON tbl_os.os                               = tbl_os_extra.os
						JOIN    tbl_produto              ON tbl_os.produto                          = tbl_produto.produto
						JOIN    tbl_linha                ON tbl_linha.linha                         = tbl_produto.linha
														AND tbl_linha.fabrica                       = $login_fabrica
						JOIN    tbl_os_produto           ON tbl_os.os                               = tbl_os_produto.os
						JOIN    tbl_os_item              ON tbl_os_item.os_produto                  = tbl_os_produto.os_produto
						JOIN    tbl_peca                 ON tbl_os_item.peca                        = tbl_peca.peca
						JOIN    tbl_pedido_item          ON tbl_pedido_item.pedido                  = tbl_os_item.pedido
														AND tbl_pedido_item.peca                    = tbl_peca.peca
						JOIN    tbl_servico_realizado    ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
						JOIN    tbl_extrato              ON tbl_extrato.extrato                     = tbl_os_extra.extrato
														AND tbl_extrato.fabrica                     = $login_fabrica
						WHERE   tbl_os_extra.extrato = $extrato
						AND     tbl_extrato.fabrica  = $login_fabrica ";

						//hd 14981
						if ($login_fabrica <> 35 AND $login_fabrica <> 51) {
							$sql .= "AND     tbl_os_item.liberacao_pedido    IS NOT FALSE ";
						}

				if($login_fabrica<>14){ $sql .=" AND     tbl_peca.devolucao_obrigatoria      IS TRUE";}
				$sql .=" AND     tbl_servico_realizado.gera_pedido   IS TRUE
						AND     tbl_servico_realizado.troca_de_peca IS TRUE
					) AS x
					ORDER BY lpad(x.sua_os,10,' '), x.preco;";
		}else{
			$sql = "SELECT	tbl_os.os                                                      ,
							tbl_os.sua_os                                                  ,
							tbl_os.consumidor_nome                                         ,
							tbl_produto.descricao  AS produto_nome                         ,
							tbl_produto.referencia AS produto_referencia                   ,
							tbl_peca.referencia    AS peca_referencia                      ,
							tbl_peca.descricao     AS peca_nome                            ,
							(SELECT preco FROM tbl_tabela_item WHERE peca = tbl_os_item.peca AND tabela = tbl_posto_linha.tabela) AS preco ,
							tbl_os_item.qtde                                               ,
							to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao
					FROM    tbl_os
					JOIN    tbl_os_extra             ON tbl_os.os                                = tbl_os_extra.os
					JOIN    tbl_produto              ON tbl_os.produto                           = tbl_produto.produto
					JOIN    tbl_os_produto           ON tbl_os.os                                = tbl_os_produto.os
					JOIN    tbl_os_item              ON tbl_os_produto.os_produto                = tbl_os_item.os_produto
					JOIN    tbl_servico_realizado    ON tbl_servico_realizado.servico_realizado  = tbl_os_item.servico_realizado
					JOIN    tbl_peca                 ON tbl_os_item.peca                         = tbl_peca.peca
					JOIN    tbl_extrato              ON tbl_extrato.extrato                      = tbl_os_extra.extrato
					JOIN    tbl_posto_linha          ON tbl_posto_linha.posto = tbl_os.posto AND (tbl_posto_linha.linha = tbl_produto.linha OR tbl_posto_linha.familia = tbl_produto.familia)
					WHERE   tbl_os_extra.extrato = $extrato
					AND     tbl_extrato.fabrica  = $login_fabrica ";

					//hd 14981 20041
					if ($login_fabrica <> 35 AND $login_fabrica <> 51) {
						$sql .= "AND     tbl_os_item.liberacao_pedido    IS NOT FALSE ";
					}

				if($login_fabrica<>14){ $sql .=" AND     tbl_peca.devolucao_obrigatoria      IS TRUE";}
				$sql .=" AND     tbl_servico_realizado.gera_pedido   IS TRUE
					AND     tbl_servico_realizado.troca_de_peca IS TRUE
					ORDER BY lpad(tbl_os.sua_os,10,' '), tbl_os_item.preco;";
		}
	}else{
		if ($login_fabrica == 6){
			$sql = "SELECT
						x.preco AS preco,
						sum(x.qtde) AS qtde ,
						x.peca_referencia ,
						x.peca_nome ,
						x.data_geracao
					FROM (
						SELECT tbl_peca.referencia as peca_referencia,
							tbl_peca.descricao as peca_nome,
							tbl_os_item,
							qtde,
							to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao,
							(SELECT  preco as preco
								FROM tbl_tabela_item
								JOIN tbl_tabela USING (tabela)
								WHERE tbl_tabela.fabrica = $login_fabrica
								AND tbl_tabela_item.peca = tbl_peca.peca
								AND tbl_tabela.ativa
								ORDER BY preco DESC LIMIT 1) AS preco
							FROM tbl_os
							JOIN tbl_os_produto USING(os)
							JOIN tbl_os_item USING(os_produto)
							JOIN tbl_peca USING(peca)
							JOIN tbl_os_extra USING(os)
							JOIN    tbl_extrato ON tbl_extrato.extrato  = tbl_os_extra.extrato
							WHERE tbl_os_extra.extrato=$extrato
							AND devolucao_obrigatoria IS TRUE
							ORDER BY os) AS x
						GROUP BY x.peca_referencia, x.peca_nome,x.data_geracao,x.preco
						ORDER BY peca_referencia";
		}else{
			$join_preco = "tbl_os_item.preco ";
			if ($login_fabrica == 6) {
				$join_preco = "(SELECT preco FROM tbl_tabela_item JOIN tbl_tabela USING (tabela) WHERE tbl_tabela.fabrica = $login_fabrica AND tbl_tabela_item.peca = tbl_peca.peca AND tbl_tabela.ativa ORDER BY preco DESC LIMIT 1) AS preco " ;
			}
			$sql = "SELECT	tbl_produto.descricao      AS produto_nome                         ,
							tbl_produto.referencia     AS produto_referencia                   ,
							tbl_peca.referencia        AS peca_referencia                      ,
							tbl_peca.descricao         AS peca_nome                            ,
							$join_preco ,
							sum(tbl_os_item.qtde)      AS qtde                                 ,
							to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao
					FROM    tbl_os
					JOIN    tbl_os_extra             ON tbl_os.os                               = tbl_os_extra.os
					JOIN    tbl_produto              ON tbl_os.produto                          = tbl_produto.produto
					JOIN    tbl_os_produto           ON tbl_os.os                               = tbl_os_produto.os
					JOIN    tbl_os_item              ON tbl_os_produto.os_produto               = tbl_os_item.os_produto
					JOIN    tbl_servico_realizado    ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
					JOIN    tbl_peca                 ON tbl_os_item.peca                        = tbl_peca.peca
					JOIN    tbl_extrato              ON tbl_extrato.extrato                     = tbl_os_extra.extrato
					JOIN    tbl_posto_linha          ON tbl_posto_linha.posto = tbl_os.posto AND (tbl_posto_linha.linha = tbl_produto.linha OR tbl_posto_linha.familia = tbl_produto.familia)
					WHERE   tbl_os_extra.extrato = $extrato
					AND     tbl_extrato.fabrica  = $login_fabrica ";

					//hd 14981
					if ($login_fabrica <> 35 AND $login_fabrica <> 51) {
						$sql .= "AND     tbl_os_item.liberacao_pedido    IS NOT FALSE ";
					}

				if($login_fabrica<>14){ $sql .=" AND     tbl_peca.devolucao_obrigatoria      IS TRUE";}
				$sql .=" AND     tbl_servico_realizado.gera_pedido   IS TRUE
					AND     tbl_servico_realizado.troca_de_peca IS TRUE
					GROUP BY   tbl_produto.descricao ,
								tbl_produto.referencia,
								tbl_peca.referencia   ,
								$join_preco ,
								tbl_peca.descricao    ,
								to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY')
					ORDER BY   sum(tbl_os_item.preco);";
		}
	}
//if ($ip=='201.42.44.145') echo "3"; echo $sql;
	$res = pg_exec ($con,$sql);
	$totalRegistros = pg_numrows($res);
// 	echo $sql;
	echo "<TABLE width='650' align='center' border='0' cellspacing='1' cellpadding='1'>\n";

	if ($totalRegistros > 0 and $login_fabrica <> 14 and $login_fabrica <> 35){
		echo "<TR class='menu_top'>\n";

		if ($agrupar == "false") $colspan = "5";
		if ($agrupar == "true")  $colspan = "3";

		echo "<TD colspan='$colspan' align = 'center'>";
		echo "EXTRATO $extrato GERADO EM " . pg_result ($res,0,data_geracao) ;
		echo "</TD>";

		echo "</TR>\n";

		if ($login_fabrica <> 14) {
			echo "<TR class='menu_top'>\n";
			echo "<TD colspan='$colspan' align = 'center'>Pe&ccedil;as que geram Cr&eacute;dito e que devem <br> ser devolvidas fisicamente para a F&aacute;brica.</TD>";
			echo "</TR>\n";
		}

		echo "<TR class='menu_top'>\n";

		if ($agrupar == "false") {
			echo "<TD align='center' >OS</TD>\n";
			echo "<TD align='center' >CLIENTE</TD>\n";
		}

		echo "<TD align='center' >PE&Ccedil;A</TD>\n";
		echo "<TD align='center' >QTDE</TD>\n";
		if ($login_fabrica <>14) {	echo "<TD align='center' >VALOR</TD>\n";}

		echo "</TR>\n";

		$soma_preco = 0;
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
			if ($agrupar == "false") {
				$os					= trim(pg_result ($res,$i,os));
				$sua_os				= trim(pg_result ($res,$i,sua_os));
				$consumidor			= trim(pg_result ($res,$i,consumidor_nome));
			}
			$peca_referencia	= trim(pg_result ($res,$i,peca_referencia));
			$peca_nome			= trim(pg_result ($res,$i,peca_nome));
			$preco				= trim(pg_result ($res,$i,preco));
			$qtde				= trim(pg_result ($res,$i,qtde));
			if($qtde>1){$preco = $preco*$qtde;}
			$soma_preco			= $soma_preco + $preco;
			$consumidor			= strtoupper($consumidor);
			$preco				= number_format($preco,2,",",".");

			$cor = "#d9e2ef";
			$btn = 'amarelo';

			if ($i % 2 == 0){
				$cor = '#F1F4FA';
				$btn = 'azul';
			}

			if (strstr($matriz, ";" . $i . ";")) {
				$cor = '#E49494';
			}

			if (strlen ($sua_os) == 0) $sua_os = $os;

			echo "<TR class='table_line' style='background-color: $cor;'>\n";

			if ($agrupar == "false") {
				echo "<TD align='center' nowrap><a href='os_press.php?os=$os' target='_blank'><font color='#000000'>$sua_os</font></a></TD>\n";
				echo "<TD align='left' nowrap>$consumidor</TD>\n";
			}
			echo "<TD align='left' nowrap>$peca_referencia - $peca_nome</TD>\n";
			echo "<TD align='center' nowrap>$qtde</TD>\n";
			if ($login_fabrica <>14) {	echo "<TD align='right' nowrap style='padding-right:3px'>$preco</TD>\n";}

			echo "</TR>\n";
		}
		if ($login_fabrica <>14) {
		echo "<TR class='table_line' style='background-color: $cor;'>\n";

		if ($agrupar == "false") $colspan = '4';
		if ($agrupar == "true")  $colspan = '2';

		echo "<TD align='center' nowrap colspan='$colspan'><b>TOTAL</b></TD>\n";
		echo "<TD align='right' nowrap style='padding-right:3px'>". number_format($soma_preco,2,",",".") ."</TD>\n";

		echo "</TR>\n";
		}
	}

	echo "</TABLE>\n";

	echo "<br>";

	if ($agrupar == "false") {
		$sql = "SELECT	tbl_os.os                                                      ,
						tbl_os.sua_os                                                  ,
						tbl_os.consumidor_nome                                         ,
						tbl_produto.descricao  AS produto_nome                         ,
						tbl_produto.referencia AS produto_referencia                   ,
						tbl_peca.referencia    AS peca_referencia                      ,
						tbl_peca.descricao     AS peca_nome                            ,
						tbl_tabela_item.preco                                          ,
						tbl_os_item.qtde                                               ,
						to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao
				FROM    tbl_os
				JOIN    tbl_os_extra          ON tbl_os.os                 = tbl_os_extra.os
				JOIN    tbl_produto           ON tbl_os.produto            = tbl_produto.produto
				JOIN    tbl_os_produto        ON tbl_os.os                 = tbl_os_produto.os
				JOIN    tbl_os_item           ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				JOIN    tbl_tabela_item       ON tbl_tabela_item.peca                    = tbl_os_item.peca
				JOIN    tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
				JOIN    tbl_peca              ON tbl_os_item.peca          = tbl_peca.peca
				JOIN    tbl_extrato           ON tbl_extrato.extrato       = tbl_os_extra.extrato
				WHERE   tbl_os_extra.extrato = $extrato
				AND     tbl_extrato.fabrica  = $login_fabrica ";

				//hd 14981 20041
				if ($login_fabrica <> 35 AND $login_fabrica <>14 AND $login_fabrica <> 51) {
					$sql .= "AND     tbl_os_item.liberacao_pedido    IS NOT FALSE ";
				}

				$sql .= "AND     tbl_servico_realizado.gera_pedido   IS FALSE
						AND     tbl_peca.devolucao_obrigatoria      IS TRUE
						AND     tbl_servico_realizado.troca_de_peca IS TRUE
						ORDER BY lpad(tbl_os.sua_os,10,' '), tbl_os_item.preco;";
	}else{
		$sql = "SELECT	tbl_produto.descricao      AS produto_nome                         ,
						tbl_produto.referencia     AS produto_referencia                   ,
						tbl_peca.referencia        AS peca_referencia                      ,
						tbl_peca.descricao         AS peca_nome                            ,
						sum(tbl_tabela_item.preco) AS preco                                ,
						sum(tbl_os_item.qtde)      AS qtde                                 ,
						to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao
				FROM    tbl_os
				JOIN    tbl_os_extra          ON tbl_os.os                 = tbl_os_extra.os
				JOIN    tbl_produto           ON tbl_os.produto            = tbl_produto.produto
				JOIN    tbl_os_produto        ON tbl_os.os                 = tbl_os_produto.os
				JOIN    tbl_os_item           ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				JOIN    tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
				JOIN    tbl_peca              ON tbl_os_item.peca          = tbl_peca.peca
				JOIN    tbl_tabela_item       ON tbl_tabela_item.peca      = tbl_os_item.peca
				JOIN    tbl_extrato           ON tbl_extrato.extrato       = tbl_os_extra.extrato
				WHERE   tbl_os_extra.extrato = $extrato
				AND     tbl_extrato.fabrica  = $login_fabrica ";

				//hd 14981 20041
				if ($login_fabrica <> 35 and $login_fabrica <>14 AND $login_fabrica <> 51) {
					$sql .= "AND     tbl_os_item.liberacao_pedido    IS NOT FALSE ";
				}

				$sql .= "AND     tbl_servico_realizado.gera_pedido   IS FALSE
						AND     tbl_peca.devolucao_obrigatoria      IS TRUE
						AND     tbl_servico_realizado.troca_de_peca IS TRUE
						GROUP BY   tbl_produto.descricao ,
									tbl_produto.referencia,
									tbl_peca.referencia   ,
									tbl_peca.descricao    ,
									to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY')
						ORDER BY   sum(tbl_os_item.preco);";
	}
//if ($ip == '201.0.9.216') { echo nl2br($sql); exit; }
//if ($ip=='201.42.44.145') echo "4"; echo $sql;
	$res = pg_exec ($con,$sql);
	$totalRegistros = pg_numrows($res);

	echo "<TABLE width='650' align='center' border='0' cellspacing='1' cellpadding='1'>\n";

	if ($totalRegistros > 0 and $login_fabrica <> 35){
		echo "<TR class='menu_top'>\n";

		if ($agrupar == "false") $colspan = "5";
		if ($agrupar == "true")  $colspan = "3";

		echo "<TD colspan='$colspan' align = 'center'>";
		echo "EXTRATO $extrato GERADO EM " . pg_result ($res,0,data_geracao) ;
		echo "</TD>";

		echo "</TR>\n";

		if ($login_fabrica <> 14) {
			echo "<TR class='menu_top'>\n";
			echo "<TD colspan='$colspan' align = 'center'>Pe&ccedil;as que n&atilde;o geram Cr&eacute;dito e que devem <br> ser devolvidas fisicamente para a F&aacute;brica.</TD>";
			echo "</TR>\n";
		}

		echo "<TR class='menu_top'>\n";

		if ($agrupar == "false") {
			echo "<TD align='center' >OS</TD>\n";
			echo "<TD align='center' >CLIENTE</TD>\n";
		}

		echo "<TD align='center' >PE&Ccedil;A</TD>\n";
		echo "<TD align='center' >QTDE</TD>\n";
	if ($login_fabrica <>14) {	echo "<TD align='center' >VALOR</TD>\n";}

		echo "</TR>\n";

		$soma_preco = 0;
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
			if ($agrupar == "false") {
				$os					= trim(pg_result ($res,$i,os));
				$sua_os				= trim(pg_result ($res,$i,sua_os));
				$consumidor			= trim(pg_result ($res,$i,consumidor_nome));
			}
			$produto_nome		= trim(pg_result ($res,$i,produto_nome));
			$produto_referencia	= trim(pg_result ($res,$i,produto_referencia));
			$peca_referencia	= trim(pg_result ($res,$i,peca_referencia));
			$peca_nome			= trim(pg_result ($res,$i,peca_nome));
			$preco				= trim(pg_result ($res,$i,preco));
			$qtde				= trim(pg_result ($res,$i,qtde));
			if($qtde>1){$preco = $preco*$qtde;}
			$soma_preco			= $soma_preco + $preco;
			$consumidor			= strtoupper($consumidor);
			$preco				= number_format($preco,2,",",".");

			$cor = "#d9e2ef";
			$btn = 'amarelo';

			if ($i % 2 == 0){
				$cor = '#F1F4FA';
				$btn = 'azul';
			}

			if (strstr($matriz, ";" . $i . ";")) {
				$cor = '#E49494';
			}

			if (strlen ($sua_os) == 0) $sua_os = $os;

			echo "<TR class='table_line' style='background-color: $cor;'>\n";

			if ($agrupar == "false") {
				echo "<TD align='center' nowrap><a href='os_press.php?os=$os' target='_blank'><font color='#000000'>$sua_os</font></a></TD>\n";
				echo "<TD align='left' nowrap>$consumidor</TD>\n";
			}
			echo "<TD align='left' nowrap>$peca_referencia - $peca_nome</TD>\n";
			echo "<TD align='center' nowrap>$qtde</TD>\n";
		if ($login_fabrica <>14) {	echo "<TD align='right' nowrap style='padding-right:3px'>$preco</TD>\n";}

			echo "</TR>\n";
		}
		if ($login_fabrica <>14) {
		echo "<TR class='table_line' style='background-color: $cor;'>\n";

		if ($agrupar == "false") $colspan = '4';
		if ($agrupar == "true")  $colspan = '2';

		echo "<TD align='center' nowrap colspan='$colspan'><b>TOTAL</b></TD>\n";
		echo "<TD align='right' nowrap style='padding-right:3px'>". number_format($soma_preco,2,",",".") ."</TD>\n";

		echo "</TR>\n";
		}
	}

	echo "</TABLE>\n";
}
?>


<TABLE align='center'>
<TR>
	<TD>
		<br>
		<img src="imagens/btn_voltar.gif" onclick="history.go(-1);" ALT="Voltar" border='0' style="cursor:pointer;">
	</TD>
</TR>
</TABLE>

<p>
<p>

<? include "rodape.php"; ?>
