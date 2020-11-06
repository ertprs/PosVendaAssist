<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include '../token_cookie.php';
$token_cookie = $_COOKIE['sess'];

$cookie_login = get_cookie_login($token_cookie);

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
if($login_fabrica==45 AND $extrato == 329130 AND isset($cookie_login['cook_admin'])){
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
	
	$campo_fat_item = ($login_fabrica == 35) ? "pedido_item" : "os_item";
	if ($agrupar == "false") {

		$join_preco = "tbl_os_item.preco ";
		$join_preco = "(SELECT preco FROM tbl_tabela_item JOIN tbl_tabela USING (tabela) WHERE tbl_tabela.fabrica = $login_fabrica AND tbl_tabela_item.peca = tbl_os_item.peca AND tbl_tabela.ativa ORDER BY preco DESC LIMIT 1) AS preco " ;

		// data de corte LGR - 2014-05-01
		$sqlDataGeracao = "SELECT extrato from tbl_extrato where extrato = $extrato and data_geracao < '2014-05-01'";
		$qryDataGeracao = pg_query($con, $sqlDataGeracao);

		if (pg_num_rows($qryDataGeracao) > 0) {
			$join_preco = " tbl_os_item.qtde AS qtde, $join_preco ";
			$join_fatura = '';
			$cond_fatura = '';
		} else {
			$join_preco = " tbl_faturamento_item.qtde, tbl_faturamento_item.preco ";
			$join_fatura = "
			    JOIN    tbl_faturamento_item    ON tbl_faturamento_item.$campo_fat_item = tbl_os_item.$campo_fat_item AND tbl_faturamento_item.extrato_devolucao = $extrato
			    JOIN    tbl_faturamento         ON tbl_faturamento.faturamento  = tbl_faturamento_item.faturamento
			";
			$cond_fatura = " AND tbl_faturamento.emissao < tbl_extrato.data_geracao::DATE ";
		}

		$sql = "SELECT	DISTINCT tbl_os.os                                                      ,
						tbl_os.sua_os                                                  ,
						tbl_os.consumidor_nome                                         ,
						tbl_produto.descricao  AS produto_nome                         ,
						tbl_produto.referencia AS produto_referencia                   ,
						tbl_peca.referencia    AS peca_referencia                      ,
						tbl_peca.descricao     AS peca_nome                            ,
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

				$sql .= "AND ( (tbl_servico_realizado.gera_pedido IS TRUE AND tbl_servico_realizado.troca_de_peca IS TRUE)
						)
						AND (tbl_os_item.peca_obrigatoria and tbl_peca.produto_acabado IS not TRUE)
						$cond_fatura
					union
					SELECT	DISTINCT tbl_os.os , tbl_os.sua_os , tbl_os.consumidor_nome , tbl_produto.descricao AS produto_nome , tbl_produto.referencia AS produto_referencia , tbl_peca.referencia AS peca_referencia , tbl_peca.descricao AS peca_nome , sum(tbl_faturamento_item.qtde) as qtde , tbl_faturamento_item.preco , to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao
					FROM tbl_os
					JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
					JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
					JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					JOIN tbl_faturamento_item ON tbl_faturamento_item.$campo_fat_item = tbl_os_item.$campo_fat_item AND tbl_faturamento_item.extrato_devolucao = $extrato
					JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
					JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado and troca_de_peca
					JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca
					JOIn tbl_extrato on tbl_extrato.extrato = tbl_faturamento_item.extrato_devolucao and tbl_extrato.fabrica =$login_fabrica
					where tbl_os.fabrica = $login_fabrica
					AND tbl_faturamento.distribuidor isnull
					group by tbl_os.os , tbl_os.sua_os , tbl_os.consumidor_nome , tbl_produto.descricao  , tbl_produto.referencia  , tbl_peca.referencia , tbl_peca.descricao , tbl_faturamento_item.preco , tbl_extrato.data_geracao

						";

				if (strlen($servico_realizado) > 0) $sql .= "AND tbl_servico_realizado.servico_realizado = $servico_realizado ";

				$sql .= "$orderby;"; //die($sql);

	}else{
	//agrupado
		$join_preco = "tbl_os_item.preco, sum(tbl_os_item.qtde) AS qtde ";
		$group_preco = ", tbl_os_item.preco ";
		$join_preco = "(SELECT preco FROM tbl_tabela_item JOIN tbl_tabela USING (tabela) WHERE tbl_tabela.fabrica = $login_fabrica AND tbl_tabela_item.peca = tbl_peca.peca AND tbl_tabela.ativa ORDER BY preco DESC LIMIT 1) AS preco " ;
			$group_preco = "";


		// data de corte LGR - 2014-05-01
		$sqlDataGeracao = "SELECT extrato from tbl_extrato where extrato = $extrato and data_geracao < '2014-05-01'";
		$qryDataGeracao = pg_query($con, $sqlDataGeracao);

		if (pg_num_rows($qryDataGeracao) > 0) {
			$join_fatura = '';
			$cond_fatura = '';
		} else {
			$join_preco = " SUM(tbl_faturamento_item.qtde) as qtde, tbl_faturamento_item.preco ";
			$join_fatura = "
				JOIN    tbl_faturamento_item    ON tbl_faturamento_item.$campo_fat_item = tbl_os_item.$campo_fat_item AND tbl_faturamento_item.extrato_devolucao = $extrato
				JOIN    tbl_faturamento         ON tbl_faturamento.faturamento  = tbl_faturamento_item.faturamento
				";
			$cond_fatura = " AND tbl_faturamento.emissao < tbl_extrato.data_geracao::DATE ";
			$group_preco = ", tbl_faturamento_item.preco";
		}

		$campos_produto = '';
		$group_produto = '';
		$sql = "SELECT	$campos_produto	tbl_peca.peca,
						tbl_peca.referencia        AS peca_referencia                      ,
						tbl_peca.descricao         AS peca_nome                            ,
						$join_preco                                                        ,
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

                                                $join_fatura
				WHERE   tbl_os_extra.extrato = $extrato
				AND     tbl_extrato.fabrica  = $login_fabrica ";

					$sql .= "AND ( (tbl_servico_realizado.gera_pedido IS TRUE AND tbl_servico_realizado.troca_de_peca IS TRUE)
							)
                            AND (tbl_os_item.peca_obrigatoria and tbl_peca.produto_acabado IS not TRUE)
                            $cond_fatura
                            ";
				if (strlen($servico_realizado) > 0) $sql .= "AND tbl_servico_realizado.servico_realizado = $servico_realizado ";

				$sql .= "GROUP BY $group_produto tbl_peca.peca,
							tbl_peca.referencia   ,
							tbl_peca.descricao    ,
							to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY')
							$group_preco
						union
						SELECT	tbl_peca.peca , tbl_peca.referencia AS peca_referencia , tbl_peca.descricao AS peca_nome , sum(tbl_faturamento_item.qtde) as qtde, tbl_faturamento_item.preco , to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao
						FROM tbl_os
						JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
						JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
						JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
						JOIN tbl_faturamento_item ON tbl_faturamento_item.$campo_fat_item = tbl_os_item.$campo_fat_item AND tbl_faturamento_item.extrato_devolucao = $extrato
						JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento and distribuidor isnull
						JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado and troca_de_peca
						JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca
						JOIn tbl_extrato on tbl_extrato.extrato = tbl_faturamento_item.extrato_devolucao and tbl_extrato.fabrica = $login_fabrica
						group by tbl_peca.peca , tbl_peca.referencia , tbl_peca.descricao ,  tbl_faturamento_item.preco , tbl_extrato.data_geracao";


	}

	$res = pg_exec ($con,$sql);
	$totalRegistros = pg_numrows($res);
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
?>
<p>
<p>

<? include "rodape.php"; ?>
