<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "cabecalho-ajax.php";

$login_posto = $_COOKIE['cook_posto'];
$rg          = $_GET['rg'];
$ajax        = $_GET['ajax'];
$acao        = $_GET['acao'];

$login_posto = str_replace ("'","",$login_posto);
$rg          = str_replace ("'","",$rg);
$rg          = trim (strtoupper ($rg));

if ($acao == "gravar" AND $ajax == "sim") {

	$res = pg_exec ($con,"BEGIN TRANSACTION");
	$msg_erro = "";
	$msg = "";

	$sql = "SELECT  RI.produto_rg_item                                       ,
				RI.codigo_barra                                              ,
				RI.rg                                                        ,
				RI.produto                                                   ,
				RI.fabrica                                                   ,
				RI.os
		FROM tbl_produto_rg_item  RI 
		JOIN tbl_produto          PR USING(produto) 
		WHERE RI.posto = $login_posto
		AND   data_devolucao IS NULL
		AND   retorno        IS TRUE
		ORDER BY produto_rg_item DESC ";
	$res = @pg_exec($con,$sql);
	if (strlen (pg_errormessage($con)) > 0) {
		$msg_erro .= pg_errormessage($con) . "($sql)";
	}

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$fabrica         = pg_result ($res,$i,fabrica);
		$produto_rg_item = pg_result ($res,$i,produto_rg_item);
		$os              = pg_result ($res,$i,os);

		$sql = "SELECT os ,sua_os
				FROM tbl_os
				WHERE fabrica    = $fabrica
				AND   posto      = $login_posto
				AND   os         = $os";
		$res2 = @pg_exec($con,$sql);
		if (strlen (pg_errormessage($con)) > 0) {
			$msg_erro .= pg_errormessage($con) . "($sql)";
		}


		if(pg_numrows($res2)>0) {

			$os     = pg_result($res2,0,os);
			$sua_os = pg_result($res2,0,sua_os);

			$sql = "UPDATE tbl_produto_rg_item SET 
						data_devolucao = now()
					WHERE produto_rg_item = $produto_rg_item";
			$res3 = @pg_exec($con,$sql);
			if (strlen (pg_errormessage($con)) > 0) {
				$msg_erro .= pg_errormessage($con) . "($sql)";
			}

			$sql = "UPDATE tbl_os SET
						data_nf_saida     = current_date,
						nota_fiscal_saida = nota_fiscal ,
						data_fechamento   = current_date,
						finalizada        = now()       ,
						conferido_saida   = TRUE
					WHERE os      = $os
					AND   fabrica = $fabrica";
			$res4 = @pg_exec ($con,$sql);
			if (strlen (pg_errormessage($con)) > 0) {
				$msg_erro .= pg_errormessage($con) . "($sql)";
			}

			$sql = "UPDATE tbl_os
					SET   data_conserto = now()
					WHERE os      = $os
					AND   fabrica = $fabrica
					AND   data_conserto IS NULL";
			$res4 = @pg_exec ($con,$sql);
			if (strlen (pg_errormessage($con)) > 0) {
				$msg_erro .= pg_errormessage($con) . "($sql)";
			}

			$sql = "UPDATE tbl_produto_rg_item SET 
						data_devolucao = now()
					WHERE produto_rg_item = $produto_rg_item
					AND   data_conserto IS NULL";
			$res4 = @pg_exec ($con,$sql);
			if (strlen (pg_errormessage($con)) > 0) {
				$msg_erro .= pg_errormessage($con) . "($sql)";
			}

			$sql = "SELECT fn_finaliza_os($os, $fabrica)";
			$res5 = @pg_exec ($con,$sql);
			if (strlen (pg_errormessage($con)) > 0) {
				$msg_erro .= pg_errormessage($con) . "($sql)";
			}
			$msg .= "$sua_os<br>";
		}
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		echo "<ok>Gravado com Sucesso</ok>";
		echo "<msg>Gravado com Sucesso<br>Foram fechadas as seguintes OS:<br>$msg</msg>";
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
		echo "<erro>$msg_erro</erro>";
	}
	exit;
}


if ($acao == "retorno" AND $ajax == "sim") {
	if (strlen($rg)==0 ) {
		$msg_erro = "Inválido";
	}

	if(strlen($msg_erro)==0){
		$sql = "SELECT  RI.produto_rg_item,
						RI.OS             ,
						RI.fabrica
				FROM tbl_produto_rg_item RI 
				WHERE RI.posto = $login_posto 
				AND   RI.rg    = '$rg' 
				AND   RI.data_devolucao IS NULL 
				AND   RI.retorno        IS FALSE ";
		$res1 = @pg_exec($con,$sql);
		if (strlen (pg_errormessage($con)) > 0) {
			$msg_erro .= pg_errormessage($con) . "($sql)";
		}
		if(@pg_numrows($res1)>1) {
			$msg_erro .= "Existe mais de uma OS com o RG $rg<br>";
		}

		if(@pg_numrows($res1) == 1 AND strlen($msg_erro) == 0){
			$fabrica         = pg_result($res1,0,fabrica);
			$produto_rg_item = pg_result($res1,0,produto_rg_item);
			$os              = pg_result($res1,0,os);

			if (strlen ($os) > 0) {
				$sql = "SELECT os ,sua_os
						FROM   tbl_os
						WHERE  fabrica    = $fabrica
						AND    posto      = $login_posto
						AND    os         = $os
						AND    rg_produto = '$rg' ";
				$res2 = @pg_exec($con,$sql);
				if (strlen (pg_errormessage($con)) > 0) {
					$msg_erro .= pg_errormessage($con) . "($sql)";
				}

				if(pg_numrows($res2)>0) {
					$os     = pg_result($res2,0,os);
					$sua_os = pg_result($res2,0,sua_os);
					$sql = "UPDATE tbl_produto_rg_item SET 
								retorno = TRUE
							WHERE produto_rg_item = $produto_rg_item";
					$res3 = @pg_exec($con,$sql);
					if (strlen (pg_errormessage($con)) > 0) {
						$msg_erro .= pg_errormessage($con) . "($sql)";
					}

				}else{
					$msg_erro .= "Não foi encontrada a OS do RG $rg";
				}
			}else{
				$msg_erro .= "RG ($rg) não convertido em OS";
			}
		}else{
			$msg_erro .= "Não foi encontrado nos lotes o RG $rg para devolução, ou já está neste lote.";
		}
	}

	if(strlen($msg_erro)==0) {
		echo "ok|Gravado com Sucesso<br>Foram fechadas as seguintes OS:<br>$msg|$produto_rg";
	}else{
		echo "1|$msg_erro";
	}
	exit;
}


if ($acao == "mostrar" AND $ajax == "sim") {

	$produto_rg = @pg_result($res1,0,0);
	$sql = "SELECT  RI.produto_rg_item                                           ,
				RI.codigo_barra                                              ,
				RI.rg                                                        ,
				RI.produto                                                   ,
				RI.serie                                                     ,
				RI.defeito_reclamado                                         ,
				RI.fabrica                                                   ,
				PR.referencia                           AS produto_referencia,
				PR.descricao                            AS produto_descricao ,
				TO_CHAR(RI.data_devolucao,'dd/mm/YYYY') AS data              ,
				RI.devolucao
		FROM tbl_produto_rg_item  RI 
		JOIN tbl_produto          PR USING(produto) 
		WHERE RI.posto = $login_posto
		AND   data_devolucao IS NULL
		AND   retorno        IS TRUE
		ORDER BY produto_rg_item DESC ";
	$res = @pg_exec($con,$sql);

	$resultado .= "<table class='TabelaRevenda'  cellspacing='3' cellpadding='3' width='98%'>\n";
	$resultado .= "<thead>";
	$resultado .= "<tr bgcolor='#596D9B' style='color:#FFFFFF;'>";
	$resultado .= "<td><b>Código Barra</b></td>";
	$resultado .= "<td><b>P</b></td>";
	$resultado .= "<td><b>Produto - Descrição</b></td>";
	$resultado .= "<td><b>Ação</b></td>";
	$resultado .= "</tr>";
	$resultado .= "</thead>";
	$resultado .= "<tbody>";

	for($i=0;$i<@pg_numrows($res);$i++) {
		$produto_rg_item    = pg_result($res,$i,produto_rg_item);
		$codigo_barra       = pg_result($res,$i,codigo_barra);
		$rg                 = pg_result($res,$i,rg);
		$data               = pg_result($res,$i,data);
		$produto            = pg_result($res,$i,produto);
		$produto_referencia = pg_result($res,$i,produto_referencia);
		$produto_descricao  = pg_result($res,$i,produto_descricao);
		$defeito_reclamado  = pg_result($res,$i,defeito_reclamado);
		$serie              = pg_result($res,$i,serie);
		$fabrica            = pg_result($res,$i,fabrica);
		$devolucao          = pg_result($res,$i,devolucao);

		if($cor<>'#FFFFFF') $cor = '#FFFFFF';
		else                $cor = '';

		if($devolucao=='t') $cor = "#FFFFA8";

		$resultado .= "<tr bgcolor='$cor' >\n";
		$resultado .= "<td><input type='hidden' name='produto_rg_item_$i' value='$produto_rg_item'>$codigo_barra</td>\n";
		$resultado .= "<td>$rg</td>\n";
		$resultado .= "<td>\n";

		$resultado .= "$produto_referencia - $produto_descricao";
		$resultado .= "<input type='hidden' name='id_produto_$i' id='id_produto_$i' value='$produto'>";
		$resultado .= "</td>\n";
		$resultado .= "<td><a href='rg_retorno.php?excluir=$produto_rg_item'><img src='imagens/icone_deletar.png'></a></td>\n";
		$resultado .= "</tr>\n";
	}
	$z = $i ;
	$resultado .= "<tr>";
	$resultado .=  "<td colspan='5'>Total de Produto: $z</td>";
	$resultado .= "</tr>";
	
	$resultado .= "</tbody>";
	$resultado .= "</table>";
	echo "ok|$resultado";
	exit;
}


?>