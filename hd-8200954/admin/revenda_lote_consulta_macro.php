<center>
<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include '../ajax_cabecalho.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao) > 0){

	$lote         = trim($_POST["lote"]);
	$nota_fiscal  = trim($_POST["nota_fiscal"]);
	$codigo_posto = trim($_POST["codigo_posto"]);
	$revenda_cnpj = trim($_POST["revenda_cnpj"]);
	$qtde_dias    = trim($_POST["qtde_dias"]);

	if(strlen($qtde_dias) > 0)   $cond1 = " AND data_recebido::date - data_digitacao::date >= $qtde_dias ";
	if(strlen($lote) > 0)        $cond2 = " AND lote like '%$lote%' ";
	if(strlen($nota_fiscal)==0) {
		//$msg_erro = "Nota Fiscal obrigatória";
	}else{
		$cond3 = " AND nota_fiscal like '%$nota_fiscal%' ";
		$cond_3 = " AND tbl_os.nota_fiscal = '$nota_fiscal' ";
	}

	if (strlen($codigo_posto) > 0 ) {
		$sql =	"SELECT tbl_posto.posto
			FROM    tbl_posto
			JOIN    tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
			WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
			AND tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
		$res = @pg_exec($con,$sql);
		if (pg_numrows($res) == 1) {
			$posto = "'".pg_result($res,0,0)."'";
			$cond4 = " AND tbl_lote_revenda.posto = $posto";
		}else{
			$msg_erro .= " Favor Informe o Posto Correto. ";
		}
	}

	if (strlen($revenda_cnpj) > 0 ) {
		$sql =	"SELECT tbl_revenda.revenda
			FROM    tbl_revenda
			WHERE   tbl_revenda.cnpj = '$revenda_cnpj' ";
		$res = @pg_exec($con,$sql);
		if (pg_numrows($res) == 1) {
			$revenda = "'".pg_result($res,0,0)."'";
			$cond5   = " AND tbl_lote_revenda.revenda = $revenda";
		}else{
			$msg_erro .= " Favor Informe a Revenda Correta. ";
		}
	}else $msg_erro .= "Revenda é Obrigatório";

}

//--====== DETALHES DO TREINAMENTO =================================================================
if($_GET['ajax']=='sim' AND $_GET['acao']=='detalhes') {
	$produto     = $_GET["produto"];
	$revenda     = $_GET["revenda"];
	$posto       = $_GET["posto"];
	$nota_fiscal = $_GET["nota_fiscal"];

	$sql = "SELECT DISTINCT
			tbl_lote_revenda.lote                                              ,
			tbl_lote_revenda.nota_fiscal                                       ,
			tbl_lote_revenda.lote_revenda                                      ,
			data_recebido::date - data_digitacao::date    AS dias_recebimento  ,
			tbl_lote_revenda_item.lote_revenda_item                            ,
			tbl_lote_revenda_item.qtde                                         ,
			tbl_lote_revenda_item.conferencia_qtde                             ,
			tbl_produto.produto                                                ,
			tbl_produto.descricao                                              ,
			tbl_produto.referencia
		FROM      tbl_lote_revenda_item
		JOIN      tbl_lote_revenda USING (lote_revenda)
		LEFT JOIN tbl_produto      USING (produto)
		WHERE   tbl_lote_revenda_item.produto = $produto
		AND     tbl_lote_revenda.posto        = $posto
		AND     tbl_lote_revenda.revenda      = $revenda
		ORDER BY tbl_lote_revenda_item.lote_revenda_item;";
//AND     tbl_lote_revenda.nota_fiscal  = '$nota_fiscal'
	$res = @pg_exec ($con,$sql) ;
	if (pg_numrows($res) > 0) {
		
		$qtde_item = pg_numrows($res);
		for ($k = 0 ; $k <$qtde_item ; $k++) {
			$lote[$k]                    = trim(pg_result($res,$k,lote));
			$lote_revenda[$k]            = trim(pg_result($res,$k,lote_revenda));
			$dias_recebimento[$k]        = trim(pg_result($res,$k,dias_recebimento));
			$item_lote_revenda[$k]       = trim(pg_result($res,$k,lote_revenda_item));
			$item_produto[$k]            = trim(pg_result($res,$k,produto));
			$item_referencia[$k]         = trim(pg_result($res,$k,referencia));
			$item_qtde[$k]               = trim(pg_result($res,$k,qtde));
			$conferencia_qtde[$k]        = trim(pg_result($res,$k,conferencia_qtde));
			$item_descricao[$k]          = trim(pg_result($res,$k,descricao));
			$nota_fiscal[$k]             = trim(pg_result($res,$k,nota_fiscal));

			if(strlen($descricao[$k])==0) $descricao[$k] = $item_descricao[$k];

			/*Neste ponto eu verifico quais itens já constam nota fiscal para devolução*/
			$sql2 = "SELECT count(*) FROM tbl_os
					JOIN tbl_os_revenda_item ON tbl_os.os = tbl_os_revenda_item.os_lote
					JOIN tbl_os_revenda      USING(os_revenda)
					WHERE lote_revenda   = ".$lote_revenda[$k]."
					AND   tbl_os.produto = ".$item_produto[$k]."
					AND   data_nf_saida IS NOT NULL";
			$res2 = pg_exec ($con,$sql2) ;
			$item_devolvido[$k] = trim(pg_result($res2,0,0));

			$sql2 = "SELECT count(*) 
				FROM tbl_os
				JOIN tbl_os_revenda_item ON tbl_os.os = tbl_os_revenda_item.os_lote
				JOIN tbl_os_revenda      USING(os_revenda)
				WHERE tbl_os.produto = $produto
				AND   tbl_os.fabrica = $login_fabrica
				AND   tbl_os.posto   = $posto
				AND    lote_revenda         = ".$lote_revenda[$k]."
				AND   tbl_os_revenda.lote_revenda IS NOT NULL
				AND   tbl_os.data_nf_saida        IS NOT NULL
				AND   tbl_os.conferido_saida      IS TRUE";

			$res2 = pg_exec ($con,$sql2);
			$item_devolvido_recebido[$k] = trim(pg_result($res2,0,0));
		}
	}

	if($qtde_item>0){

		$resposta .= "<font size='2'><b>$item_referencia[0] - $item_descricao[0]</b></font><table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='700' border='0' id='tbl_pecas'>\n";
		$resposta .= "<thead>\n";
		$resposta .= "<tr height='25' bgcolor='#BCCBE0'>\n";
		$resposta .= "<td align='center' class='Conteudo'><b>Lote.</b>&nbsp;</td>\n";
		$resposta .= "<td align='center' class='Conteudo' width='100'><b>Qtde Env.</b>&nbsp;</td>\n";
		$resposta .= "<td align='center'> <b>Div.</td>\n";
		$resposta .= "<td align='center' class='Conteudo' width='100'><b>Qtde Rec.</b>&nbsp;</td>\n";
		$resposta .= "<td align='center' class='Conteudo' width='100'><b>Qtde Dev.</b>&nbsp;</td>\n";
		$resposta .= "<td align='center' class='Conteudo' width='100'><b>Saldo</b>&nbsp;</td>\n";
		$resposta .= "<td align='center' class='Conteudo' width='100'><b>Qtde Ret.</b>&nbsp;</td>\n";
		$resposta .= "<td align='center' class='Conteudo' width='100'> <b>Qtde Div. Ret.</td>\n";
		$resposta .= "</tr>\n";
		$resposta .= "</thead>\n";

		$resposta .= "<tbody>\n";
		for ($k=0;$k<$qtde_item;$k++){

			$saldo = $conferencia_qtde[$k] - $item_devolvido[$k];
			$dive1 = $conferencia_qtde[$k] - $item_qtde[$k];             if($dive1==0)$dive1='';
			$dive2 = $item_devolvido_recebido[$k] - $item_devolvido[$k]; if($dive2==0)$dive2='';

			$resposta .= "<tr style='color: #000000; text-align: center; font-size:10px'>\n";
			$resposta .="<td><a href=\"revenda_lote_consulta.php?ajax=sim&acao=detalhes&ok=no&lote_revenda=$lote_revenda[$k]&keepThis=trueTB_iframe=true&height=450&width=700\" title='Detalhado' class=\"thickbox\">$lote[$k] / NF $nota_fiscal[$k]</a></td>\n";
			$resposta .="<td>$item_qtde[$k]</td>\n";
			$resposta .="<td><font color='#FF0000'>$dive1</font></td>\n";
			$resposta .="<td>$conferencia_qtde[$k]</td>\n";
			$resposta .="<td>$item_devolvido[$k]</td>\n";
			$resposta .="<td><font color='#009900'>".$saldo."</td>\n";
			$resposta .="<td>".$item_devolvido_recebido[$k]."</td>\n";
			$resposta .="<td><font color='#FF0000'>$dive2</font></td>\n";

			$total_item  = $item_qtde[$k];               $valor_total_itens  += $total_item;
			$total_item2 = $conferencia_qtde[$k];        $valor_total_itens2 += $total_item2;
			$total_item3 = $item_devolvido[$k];          $valor_total_itens3 += $total_item3;
			$total_item4 = $saldo;                       $valor_total_itens4 += $total_item4;
			$total_item5 = $item_devolvido_recebido[$k]; $valor_total_itens5 += $total_item5;
			$total_dive1 += $dive1;
			$total_dive2 += $dive2;
			$resposta .="</tr>\n";
		}
	}
	$resposta .="</tbody>\n";

	$resposta .="<tfoot>\n";
	$resposta .="<tr height='12' bgcolor='#BCCBE0' height='25'>\n";
	$resposta .="<td align='center' class='Conteudo'><b>Total</b>&nbsp;</td>\n";
	$resposta .="<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'>$valor_total_itens</span></td>\n";
	$resposta .="<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'><font color='#FF0000'>$total_dive1</font></span></td>\n";
	$resposta .="<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'>$valor_total_itens2</span></td>\n";
	$resposta .="<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'>$valor_total_itens3</span></td>\n";
	$resposta .="<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'><font color='#009900'>$valor_total_itens4</font></span></td>\n";
	$resposta .="<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'>$valor_total_itens5</span></td>\n";
	$resposta .="<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'><font color='#FF0000'>$total_dive2</font></span></td>\n";

	$resposta .="</tr>\n";
	$resposta .="</tfoot>\n";
	$resposta .="</table>\n";

	echo "ok|$resposta";
	exit;
}


if(strlen($nf)>0 AND strlen($revenda)>0 ) {
	$sql = "SELECT DISTINCT
			tbl_lote_revenda.lote                                              ,
			tbl_lote_revenda.nota_fiscal                                       ,
			tbl_lote_revenda.lote_revenda                                      ,
			data_recebido::date - data_digitacao::date    AS dias_recebimento  ,
			tbl_lote_revenda_item.lote_revenda_item                            ,
			tbl_lote_revenda_item.qtde                                         ,
			tbl_lote_revenda_item.conferencia_qtde                             ,
			tbl_produto.produto                                                ,
			tbl_produto.descricao                                              ,
			tbl_produto.referencia
		FROM      tbl_lote_revenda_item
		JOIN      tbl_lote_revenda USING (lote_revenda)
		LEFT JOIN tbl_produto      USING (produto)
		WHERE   tbl_lote_revenda.revenda      = $revenda
		AND     tbl_lote_revenda.nota_fiscal  = '$nf'
		ORDER BY tbl_lote_revenda_item.lote_revenda_item;";

	$res = @pg_exec ($con,$sql) ;
	if (pg_numrows($res) > 0) {
		
		$qtde_item = pg_numrows($res);
		for ($k = 0 ; $k <$qtde_item ; $k++) {
			$lote[$k]                    = trim(pg_result($res,$k,lote));
			$lote_revenda[$k]            = trim(pg_result($res,$k,lote_revenda));
			$dias_recebimento[$k]        = trim(pg_result($res,$k,dias_recebimento));
			$item_lote_revenda[$k]       = trim(pg_result($res,$k,lote_revenda_item));
			$item_produto[$k]            = trim(pg_result($res,$k,produto));
			$item_referencia[$k]         = trim(pg_result($res,$k,referencia));
			$item_qtde[$k]               = trim(pg_result($res,$k,qtde));
			$conferencia_qtde[$k]        = trim(pg_result($res,$k,conferencia_qtde));
			$item_descricao[$k]          = trim(pg_result($res,$k,descricao));
			$nota_fiscal[$k]             = trim(pg_result($res,$k,nota_fiscal));
			
			if(strlen($descricao[$k])==0) $descricao[$k] = $item_descricao[$k];

			/*Neste ponto eu verifico quais itens já constam nota fiscal para devolução*/
			$sql2 = "SELECT count(*) FROM tbl_os
					JOIN tbl_os_revenda_item ON tbl_os.os = tbl_os_revenda_item.os_lote
					JOIN tbl_os_revenda      USING(os_revenda)
					WHERE lote_revenda   = ".$lote_revenda[$k]."
					AND   tbl_os.produto = ".$item_produto[$k]."
					AND   tbl_os.nota_fiscal = '$nf'
					AND   data_nf_saida IS NOT NULL";

			$res2 = @pg_exec ($con,$sql2) ;
			$item_devolvido[$k] = trim(pg_result($res2,0,0));

			$sql2 = "SELECT count(*) 
				FROM tbl_os
				JOIN tbl_os_revenda_item ON tbl_os.os = tbl_os_revenda_item.os_lote
				JOIN tbl_os_revenda      USING(os_revenda)
				WHERE tbl_os.fabrica = $login_fabrica
				AND    lote_revenda         = ".$lote_revenda[$k]."
				AND   tbl_os_revenda.lote_revenda IS NOT NULL
				AND   tbl_os.data_nf_saida        IS NOT NULL
				AND   tbl_os.conferido_saida      IS TRUE
				AND   tbl_os.nota_fiscal = '$nf'";
			$res2 = pg_exec ($con,$sql2);
			$item_devolvido_recebido[$k] = trim(pg_result($res2,0,0));

			$sql2 = "SELECT count(*) 
				FROM tbl_os
				JOIN tbl_os_revenda_item ON tbl_os.os = tbl_os_revenda_item.os_lote
				JOIN tbl_os_revenda      USING(os_revenda)
				WHERE tbl_os.fabrica  = $login_fabrica
				AND    lote_revenda   = ".$lote_revenda[$k]."
				AND    tbl_os.produto = ".$item_produto[$k]."
				AND    tbl_os_revenda.lote_revenda IS NOT NULL
				AND    tbl_os.finalizada IS NOT NULL
				AND    tbl_os.nota_fiscal = '$nf'";
			$res2 = pg_exec ($con,$sql2);
			$item_consertado[$k] = trim(pg_result($res2,0,0));
		}
	}

	if($qtde_item>0){

		echo  "<font size='2'><b>$item_referencia[0] - $item_descricao[0]</b></font><table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='750' border='0' id='tbl_pecas'>\n";
		echo  "<thead>\n";
		echo  "<tr height='25' bgcolor='#BCCBE0' style='color: #000000; text-align: center; font-size:12px'>\n";
		echo  "<td align='center' class='Conteudo'><b>Lote.</b>&nbsp;</td>\n";
		echo  "<td align='center' class='Conteudo' width='60'><b>Qtde Env.</b>&nbsp;</td>\n";
		echo  "<td align='center'> <b>Div.</td>\n";
		echo  "<td align='center' class='Conteudo' width='60'><b>Qtde Rec.</b>&nbsp;</td>\n";
		echo  "<td align='center' class='Conteudo' width='60'><b>Consertado</b>&nbsp;</td>\n";
		echo  "<td align='center' class='Conteudo' width='60'><b>Qtde Dev.</b>&nbsp;</td>\n";
		echo  "<td align='center' class='Conteudo' width='60'><b>Saldo</b>&nbsp;</td>\n";
		echo  "<td align='center' class='Conteudo' width='60'><b>Qtde Ret.</b>&nbsp;</td>\n";
		echo  "<td align='center' class='Conteudo' width='60'> <b>Qtde Div. Ret.</td>\n";
		echo  "</tr>\n";
		echo  "</thead>\n";

		echo  "<tbody>\n";
		for ($k=0;$k<$qtde_item;$k++){

			$saldo = $conferencia_qtde[$k] - $item_devolvido[$k];
			$dive1 = $conferencia_qtde[$k] - $item_qtde[$k];             if($dive1==0)$dive1='';
			$dive2 = $item_devolvido_recebido[$k] - $item_devolvido[$k]; if($dive2==0)$dive2='';

			if($cor == "#D7E1FF") $cor = '#F0F4FF';
			else                  $cor = '#D7E1FF';

			echo  "<tr style='color: #000000; text-align: center; font-size:10px' bgcolor='$cor'>\n";
			echo "<td>$item_referencia[$k] - $item_descricao[$k]</td>\n";
			echo "<td>$item_qtde[$k]</td>\n";
			echo "<td><font color='#FF0000'>$dive1</font></td>\n";
			echo "<td>$conferencia_qtde[$k]</td>\n";
			echo "<td>$item_consertado[$k]</td>\n";
			echo "<td>$item_devolvido[$k]</td>\n";
			echo "<td><font color='#009900'>".$saldo."</td>\n";
			echo "<td>".$item_devolvido_recebido[$k]."</td>\n";
			echo "<td><font color='#FF0000'>$dive2</font></td>\n";

			$total_item  = $item_qtde[$k];               $valor_total_itens  += $total_item;
			$total_item2 = $conferencia_qtde[$k];        $valor_total_itens2 += $total_item2;
			$total_item3 = $item_devolvido[$k];          $valor_total_itens3 += $total_item3;
			$total_item4 = $saldo;                       $valor_total_itens4 += $total_item4;
			$total_item5 = $item_devolvido_recebido[$k]; $valor_total_itens5 += $total_item5;
			$total_item6 += $item_consertado[$k];
			$total_dive1 += $dive1;
			$total_dive2 += $dive2;
			echo "</tr>\n";
			echo "<tr><td colspan=9>";
			$sql2 = "SELECT tbl_os.os                                                      ,
							tbl_os.sua_os                                                  ,
							tbl_os.nota_fiscal                                             ,
							tbl_os.produto                                                 ,
							tbl_os.nota_fiscal_saida                                       ,
							tbl_os.conferido_saida                                         ,
							TO_CHAR(tbl_os.data_nf_saida,'dd/mm/YYYY')   AS data_nf_saida  ,
							TO_CHAR(tbl_os.data_abertura,'dd/mm/YYYY')   AS data_abertura  ,
							TO_CHAR(tbl_os.data_fechamento,'dd/mm/YYYY') AS data_fechamento
				FROM tbl_os
				JOIN tbl_os_revenda_item ON tbl_os.os = tbl_os_revenda_item.os_lote
				JOIN tbl_os_revenda      USING(os_revenda)
				WHERE tbl_os.fabrica = $login_fabrica
				AND   lote_revenda   = $lote_revenda[$k]
				AND   tbl_os_revenda.lote_revenda IS NOT NULL

				AND   tbl_os.nota_fiscal = '$nf'
				AND   tbl_os.produto     = $item_produto[$k]";
//			echo $sql2;
			$res2 = pg_exec ($con,$sql2);
			if(pg_numrows($res2)>0){
				echo "<table width='90%' style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='700' border='0'>";
				echo "<tr  bgcolor='#BCCBE0' style='color: #000000; text-align: center; font-size:12px'>";
				echo "<th>OS</th>";
				echo "<th>Nota Fiscal</th>";
				echo "<th>Abertura</th>";
				echo "<th>Fechamento</th>";
				echo "<th>NF Saída</th>";
				echo "<th>Data NF Saída</th>";
				echo "<th>Recebido</th>";
				echo "</tr>";

				for($y=0; $y < pg_numrows($res2);$y++){
					$os              = pg_result($res2,$y,os);
					$sua_os          = pg_result($res2,$y,sua_os);
					$nota_fiscal     = pg_result($res2,$y,nota_fiscal);
					$produto         = pg_result($res2,$y,produto);
					$data_abertura   = pg_result($res2,$y,data_abertura);
					$data_fechamento = pg_result($res2,$y,data_fechamento);
					$nf_saida        = pg_result($res2,$y,nota_fiscal_saida);
					$data_nf_saida   = pg_result($res2,$y,data_nf_saida);
					$conferido_saida = pg_result($res2,$y,conferido_saida);

					if($conferido_saida=='t') $conferido_saida = "Dev.";
					else                      $conferido_saida = " - ";
					echo "<tr style='color: #000000; text-align: center; font-size:10px'>";
					echo "<td><a href='os_press.php?os=$os'>$sua_os</a></td>";
					echo "<td class='Conteudo'>$nota_fiscal</td>";
					echo "<td>$data_abertura</td>";
					echo "<td>$data_fechamento</td>";
					echo "<td>$nf_saida</td>";
					echo "<td>$data_nf_saida</td>";
					echo "<td>$confencia_saida</td>";
					echo "</tr>";
					
				}
				echo "</table>";
			}
			echo "</td></tr>";


		}
	}
	echo "</tbody>\n";

	echo "<tfoot>\n";
	echo "<tr height='12' bgcolor='#BCCBE0' height='25'>\n";
	echo "<td align='center' class='Conteudo'><b>Total</b>&nbsp;</td>\n";
	echo "<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'>$valor_total_itens</span></td>\n";
	echo "<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'><font color='#FF0000'>$total_dive1</font></span></td>\n";
	echo "<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'>$valor_total_itens2</span></td>\n";
	echo "<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'>$total_item6</span></td>\n";
	echo "<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'>$valor_total_itens3</span></td>\n";
	echo "<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'><font color='#009900'>$valor_total_itens4</font></span></td>\n";
	echo "<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'>$valor_total_itens5</span></td>\n";
	echo "<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'><font color='#FF0000'>$total_dive2</font></span></td>\n";

	echo "</tr>\n";
	echo "</tfoot>\n";
	echo "</table>\n";
	exit;
}




include 'cabecalho.php';


?>
<script language='javascript' src='../ajax.js'></script>
<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[0] + " - " + row[1];
	}
	
	function formatResult(row) {
		return row[0];
	}

	$("#codigo_posto").autocomplete("<?echo 'revenda_consulta_ajax.php?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#codigo_posto").result(function(event, data, formatted) {
		$("#nome_posto").val(data[1]) ;
	});

	$("#nome_posto").autocomplete("<?echo 'revenda_consulta_ajax.php?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#nome_posto").result(function(event, data, formatted) {
		$("#codigo_posto").val(data[0]) ;
	});

	$("#revenda_cnpj").autocomplete("<?echo 'revenda_consulta_ajax.php?busca_revenda=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#revenda_cnpj").result(function(event, data, formatted) {
		$("#revenda_nome").val(data[1]) ;
	});

	$("#revenda_nome").autocomplete("<?echo 'revenda_consulta_ajax.php?busca_revenda=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#revenda_nome").result(function(event, data, formatted) {
		$("#revenda_cnpj").val(data[0]) ;
	});
});
</script>

<script>
function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}

	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}

function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome&proximo=t";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj&proximo=t";
	}
	if (campo.value != "") {
		if (campo.value.length >= 3) {
			janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
			janela.nome			= document.frm_os.revenda_nome;
			janela.cnpj			= document.frm_os.revenda_cnpj;
			janela.fone			= document.frm_os.revenda_fone;
			janela.cidade		= document.frm_os.revenda_cidade;
			janela.estado		= document.frm_os.revenda_estado;
			janela.endereco		= document.frm_os.revenda_endereco;
			janela.numero		= document.frm_os.revenda_numero;
			janela.complemento	= document.frm_os.revenda_complemento;
			janela.bairro		= document.frm_os.revenda_bairro;
			janela.cep			= document.frm_os.revenda_cep;
			janela.email		= document.frm_os.revenda_email;
			janela.proximo		= document.frm_os.codigo_posto;
			janela.focus();
		}else{
			alert("Digite pelo menos 3 caracteres para efetuar a pesquisa");
		}
	}
}

function retornaCrm (http , componente ) {
	com = document.getElementById(componente);

	com.innerHTML   ="Carregando<br><img src='../imagens/carregar2.gif'>\n";
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					com.innerHTML   = results[1];
					tb_init('a.thickbox, area.thickbox, input.thickbox');

					//mostrar_interacao(results[1],'interacao_'+results[1]);
				}else{
					alert ('Erro ao abrir lote da revenda' );
					alert(results[0]);
				}
			}
		}
	}
}

function pegaCrm (id,dados,cor) {
	url = "<?=$PHP_SELF?>?ajax=sim&acao=detalhes" +id+"&cor="+escape(cor) ;

	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaCrm (http , dados) ; } ;
	http.send(null);
}

function MostraEsconde(dados,hd_chamado,imagem,cor)
{
	if (document.getElementById)
	{
		// this is the way the standards work
		var style2 = document.getElementById(dados);
		var img    = document.getElementById(imagem);
		if (style2.style.display){
			style2.style.display = "";
			style2.innerHTML   ="";
			img.src='../imagens/mais.gif';

			}
		else{
			style2.style.display = "block";
			img.src='../imagens/menos.gif';
			pegaCrm(hd_chamado,dados,cor);
		}

	}
}
function bloqueia(){
	$("*[@rel='corpo']").show("");
}

</script>
<style>
.Conteudo{
	font-family: Arial;
	font-size: 10px;
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
	font-size: 12px;
	color:#FFF;
	border:#485989 1px solid; 
	background-color: #990000;
}

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
}
</style>

<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">

<?
$qtde_item=0;
if(strlen($msg_erro)>0) echo "<div name='erro' class='msg_erro' style='width:700px;'>$msg_erro</div>\n";
echo "<table  align='center' width='700' border='0' cellspacing='0' class='formulario'>\n";

echo "<tr height='20' class='titulo_tabela'>\n";
echo "<td align='right' colspan='3'><b>Consulta Lote de Produto</b>&nbsp;</td>\n";
echo "<td align='right' class='Conteudo'><a href='revenda_inicial.php'>Menu de Revendas</a></td>\n";
echo "</tr>\n";
echo "<tr><td colspan='4'><br>\n";
$aba = 7;
include "revenda_cabecalho.php";
echo "<br>&nbsp;</td></tr>\n";

echo "<tr height='20'>\n";
echo "<td align='right' >Lote&nbsp;</td>\n";
echo "<td align='left' colspan='3'><input type='text' name='lote' id='lote' value='$lote' class='frm'></td>\n";
echo "</tr>\n";

echo "<tr height='20'>\n";
echo "<td align='right' >Nota Fiscal&nbsp;</td>\n";
echo "<td align='left' colspan='3'><input type='text' name='nota_fiscal' id='nota_fiscal' value='$nota_fiscal' class='frm'></td>\n";
echo "</tr>\n";

echo "<tr height='20'>\n";
echo "<td align='right'>CNPJ da Revenda&nbsp;</td>\n";
echo "<td align='left'><input type='text' name='revenda_cnpj' id='revenda_cnpj'  maxlength='18' value='$revenda_cnpj' class='frm' onblur=\"fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, 'cnpj')\">&nbsp;<img src='../imagens/lupa.png' style='cursor:pointer' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, 'cnpj')\"></td>\n";
echo "<td align='right' >Nome da Revenda&nbsp;</td>\n";
echo "<td align='left' ><input type='text' name='revenda_nome' id='revenda_nome' size='40' maxlength='60' value='$revenda_nome'class='frm' >&nbsp;<img src='../imagens/lupa.png' style='cursor:pointer' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, 'nome')\"></td>\n";
echo "</tr>\n";
	echo "<input type='hidden' name='revenda_fone'>\n";
	echo "<input type='hidden' name='revenda_cidade'>\n";
	echo "<input type='hidden' name='revenda_estado'>\n";
	echo "<input type='hidden' name='revenda_endereco'>\n";
	echo "<input type='hidden' name='revenda_numero'>\n";
	echo "<input type='hidden' name='revenda_complemento'>\n";
	echo "<input type='hidden' name='revenda_bairro'>\n";
	echo "<input type='hidden' name='revenda_cep'>\n";
	echo "<input type='hidden' name='revenda_email'>\n";

echo "<tr heigth='20'>\n";
echo "<td align='right' >Codigo Posto&nbsp;</td>\n";
echo "<td align='left' ><input type='text' name='codigo_posto' maxlength='14' id='codigo_posto' value='$codigo_posto' class='frm' onFocus=\"nextfield ='nome_posto'\" onblur=\"javascript: fnc_pesquisa_posto2 (document.frm_os.codigo_posto,document.frm_os.nome_posto,'codigo')\">&nbsp;<img src='../imagens/lupa.png' style='cursor: pointer;' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto2 (document.frm_os.codigo_posto,document.frm_os.nome_posto,'codigo')\">
</td>\n";
echo "<td align='right' >Nome&nbsp;</td>\n";
echo "<td align='left'><input type='text' name='nome_posto' id='nome_posto' size='40' maxlength='60' value='$nome_posto' class='frm' onFocus=\"nextfield ='condicao'\">&nbsp;<img src='../imagens/lupa.png' style='cursor: pointer;' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto2 (document.frm_os.codigo_posto,document.frm_os.nome_posto,'nome')\"></td>\n";
echo "</tr>\n";

echo "<tr height='20'>\n";
echo "<td align='right' >Quantidade de dias no posto&nbsp;</td>\n";
echo "<td align='left' colspan='3'><input type='text' name='qtde_dias' maxlength='3' size='3' value='$qtde_dias' class='frm' > dias";
echo "</tr>\n";
echo "</table>\n";
echo "<table class='formulario' align='center' width='700' border='0'height='40'>\n";
echo "<tr><td valign='middle'  align='center' colspan='4'><a href='revenda_lote_consulta_macro_os.php'>Clique aqui para gerar o relatório completo da revenda por XLS</a></td>\n";
echo "</tr>\n";
echo "</table>\n";


echo "<table class='formulario' align='center' width='700' border='0'height='40'>\n";
echo "<tr><td width='50' valign='middle'  align='LEFT' colspan='4'><input type='submit' name='btn_acao'  value='Consultar' onClick=\"if (this.value!='Consultar'){ alert('Aguarde');}else {this.value='Consultando...'; /*gravar(this.form,'sim','$PHP_SELF','nao');*/}\" style=\"width: 150px;\"></td>\n";
echo "<td width='300'><div id='saida' style='display:inline;'></div></td>\n";
echo "</tr>\n";
echo "</table>\n";

flush();


if(strlen($btn_acao) > 0){

	
	if(strlen($msg_erro) == 0){
		$sql = "SELECT DISTINCT
				tbl_posto.nome                                                         ,
				tbl_posto_fabrica.codigo_posto                                         ,
				tbl_revenda.revenda                                                    ,
				tbl_revenda.nome                                       AS revenda_nome ,
				tbl_revenda.cnpj                                       AS revenda_cnpj ,
				sum(tbl_lote_revenda_item.qtde)      AS qtde                           ,
				sum(tbl_lote_revenda_item.conferencia_qtde) as conferencia_qtde        ,
				tbl_produto.produto,
				tbl_posto.posto,
				tbl_produto.referencia                                                 ,
				tbl_produto.descricao
			FROM tbl_lote_revenda 
			JOIN tbl_lote_revenda_item USING(lote_revenda)
			JOIN tbl_produto           USING(produto)
			LEFT JOIN tbl_revenda      USING(revenda)
			JOIN tbl_posto             USING(posto)
			JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_lote_revenda.fabrica = $login_fabrica
			$cond1 $cond2 $cond3 $cond4 $cond5
			GROUP BY tbl_revenda.revenda,tbl_posto.nome,tbl_posto.posto,codigo_posto,revenda_nome,revenda_cnpj,referencia,descricao,tbl_produto.produto;";

		$res = pg_exec ($con,$sql) ;
		if (pg_numrows($res) > 0) {

			echo "<br><table class='tabela' align='center' width='98%' border='0' cellspacing='0'>\n";
			echo "<caption><center><a href='javascript: bloqueia();'>Clique aqui para ver detalhes por produtos</a></center></caption>";
			echo "<thead>";
				echo "<tr  class='titulo_coluna' height='25'>\n";
					echo "<td align='left'> <b> </td>\n";
					echo "<td align='left'> <b>Revenda</td>\n";
					echo "<td align='left'> <b>Posto</td>\n";
					echo "<td align='left'> <b>Produto</td>\n";
					echo "<td align='right'> <b>Qtde Env.</td>\n";
					echo "<td align='right'> <b>Div.</td>\n";
					echo "<td align='right'> <b>Qtde Rec.</td>\n";
					echo "<td align='right'> <b>Qtde Dev.</td>\n";
					echo "<td align='right'> <b>Saldo</td>\n";
					echo "<td align='right'> <b>Qtde Ret.</td>\n";
					echo "<td align='right'> <b>Qtde Div. Ret.</td>\n";
				echo "</tr>\n";
			echo "</thead>";
			
			echo "<tbody rel='corpo' style='display:none;'>";
				$qtde_item = pg_numrows($res);
				for ($i = 0 ; $i<$qtde_item ; $i++) {
					$nome             = pg_result ($res,$i,nome);
					$codigo_posto     = pg_result ($res,$i,codigo_posto);
					$revenda_nome     = pg_result ($res,$i,revenda_nome);
					$revenda_cnpj     = pg_result ($res,$i,revenda_cnpj);
					$produto          = pg_result ($res,$i,produto);
					$referencia       = pg_result ($res,$i,referencia);
					$descricao        = pg_result ($res,$i,descricao);
					$qtde             = pg_result ($res,$i,qtde);
					$revenda          = pg_result ($res,$i,revenda);
					$posto             = pg_result ($res,$i,posto);
					$conferencia_qtde = pg_result ($res,$i,conferencia_qtde);

					$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";
					

					$sql2 = "SELECT count(*) 
						FROM tbl_os
						JOIN tbl_os_revenda_item ON tbl_os.os = tbl_os_revenda_item.os_lote
						JOIN tbl_os_revenda      USING(os_revenda)
						WHERE tbl_os.produto = $produto
						AND   tbl_os.fabrica = $login_fabrica
						AND   tbl_os.posto   = $posto
						AND   tbl_os_revenda.lote_revenda IS NOT NULL
						AND   tbl_os.data_nf_saida        IS NOT NULL
						$cond_3";
					$res2 = pg_exec ($con,$sql2);
					$item_devolvido = trim(pg_result($res2,0,0));

					$sql2 = "SELECT count(*) 
						FROM tbl_os
						JOIN tbl_os_revenda_item ON tbl_os.os = tbl_os_revenda_item.os_lote
						JOIN tbl_os_revenda      USING(os_revenda)
						WHERE tbl_os.produto = $produto
						AND   tbl_os.fabrica = $login_fabrica
						AND   tbl_os.posto   = $posto
						AND   tbl_os_revenda.lote_revenda IS NOT NULL
						AND   tbl_os.data_nf_saida        IS NOT NULL
						AND   tbl_os.conferido_saida      IS TRUE
						$cond_3";
					$res2 = pg_exec ($con,$sql2);
					$item_devolvido_recebido = trim(pg_result($res2,0,0));

					$dive1 = $conferencia_qtde - $qtde;                  if($dive1==0)$dive1='';
					$dive2 = $item_devolvido_recebido - $item_devolvido; if($dive2==0)$dive2='';
					$saldo = $conferencia_qtde-$item_devolvido;
					echo "<tr bgcolor='$cor' >\n";
						echo "<td align='center' width='20' height='20'>\n";
						echo  "<img src='../imagens/mais.gif' id='visualizar_$i' style='cursor: pointer' onclick=\"javascript:MostraEsconde('dados_$i','&produto=$produto&revenda=$revenda&posto=$posto&nota_fiscal=$nota_fiscal','visualizar_$i','$cor');\" align='absmiddle'>\n";
						echo "</td>\n";
						echo "<td align='left'><a href=\"javascript:MostraEsconde('dados_$i','&produto=$produto&revenda=$revenda&posto=$posto&nota_fiscal=$nota_fiscal','visualizar_$i','$cor');\">$revenda_nome </td>\n";
						echo "<td align='left' title='$codigo_posto - $nome'>$nome </td>\n";
						echo "<td align='left' title='$referencia - $descricao'>&nbsp;&nbsp;$descricao</td>\n";
						echo "<td align='right'title='Quantidade Enviada pela Revenda'> $qtde</td>\n";
						echo "<td align='right' title='Divergência entre a qtde enviada pela revenda e a quantidade recebida pelo posto'><font color='#FF0000'>$dive1</font></td>\n";
						echo "<td align='right' title='Quantidade recebida pelo posto'> $conferencia_qtde</td>\n";
						echo "<td align='right' title='Quantidade devolvida pelo posto'> $item_devolvido</td>\n";
						echo "<td align='right' title='Total de produtos presente no posto'><font color='#009900'>$saldo</font></td>\n";
						echo "<td align='right' title='Itens recebidos pela revenda'>$item_devolvido_recebido</td>\n";
						echo "<td align='right' title='Divergência entre a qtde enviada pelo posto e a recebida pela revenda'><font color='#FF0000'>$dive2</font></td>\n";
					echo "</tr>\n";

						echo "<tr heigth='1' bgcolor='$cor'><td colspan='12'>\n";
						echo "<DIV class='exibe' id='dados_$i' value='1' align='center'>\n";
						echo "</DIV>\n";
						echo "</td></tr>\n";

					$total_dive1 += $dive1;
					$total_dive2 += $dive2;
					$total_saldo += $saldo;
					$total_qtde  += $qtde;
					$total_cq    += $conferencia_qtde;
					$total_id    += $item_devolvido;
					$total_idr   += $item_devolvido_recebido;
				}
			echo "</tbody>";
			
			echo "<tfoot>";
			echo "<tr bgcolor='#BCCBE0' >\n";
			echo "<td align='center' width='20' height='20' colspan='4'><b>TOTAL</b>&nbsp;</td>\n";
			echo "<td align='right'title='Quantidade Enviada pela Revenda'><b> $total_qtde</b>&nbsp;</td>\n";
			echo "<td align='right' title='Divergência entre a qtde enviada pela revenda e a quantidade recebida pelo posto'><font color='#FF0000'><b>$total_dive1</b></font></td>\n";
			echo "<td align='right' title='Quantidade recebida pelo posto'><b>$total_cq</b>&nbsp;</td>\n";
			echo "<td align='right' title='Quantidade devolvida pelo posto'><b> $total_id</b>&nbsp;</td>\n";
			echo "<td align='right' title='Total de produtos presente no posto'><font color='#009900'><b>$total_saldo</b></font></td>\n";
			echo "<td align='right' title='Itens recebidos pela revenda'><b>$total_idr</b>&nbsp;</td>\n";
			echo "<td align='right' title='Divergência entre a qtde enviada pelo posto e a recebida pela revenda'><font color='#FF0000'><b>$total_dive2</b></font></td>\n";
			echo "</tr>\n";
			echo "</tfoot>";

			echo "</table><br>\n";
		}else{
			echo "<center><font color='#FF0000'>Nenhum lote encontrado</font></center>\n";
		}
	}
}

 include "rodape.php";
?>
