<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

if (strlen($_POST["btn_acao"]) == 0 && strlen($_POST["select_acao"]) == 0) {
	setcookie("link", $REQUEST_URI, time()+60*60*24); # Expira em 1 dia
}

$msg_erro = "";

$layout_menu = "financeiro";
$title = "Pré Fechamento de Extrato do Posto";

if (strlen($_POST["btnacao"]) > 0) $btnacao = trim(strtolower($_POST["btnacao"]));

if (strlen($_POST["select_acao"]) > 0) $select_acao = strtoupper($_POST["select_acao"]);

if ($btnacao == "aprovar" AND $login_fabrica == 6){
	$posto       = $_POST["posto"];
	$total       = $_POST["total"];
	$data_limite = $_POST["data_limite"];

	if (strlen ($data_limite) < 10) $data_limite = date ("d/m/Y");
	$x_data_limite = substr ($data_limite,6,4) . "-" . substr ($data_limite,3,2) . "-" . substr ($data_limite,0,2);

	for($i=0; $i <= $total; $i++){
		$os_i = $_POST['os_'.$i];

		if (strlen($os_i) > 0) {

			$sql = "SELECT fn_fechamento_extrato_detalhado($posto,$login_fabrica,'$x_data_limite'::date, $os_i)";
			$res = pg_exec ($con,$sql);
			if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);

			$extrato = pg_result ($res,0,0);
		}
	}

	if (strlen($msg_erro) == 0){
		if (strlen($extrato) > 0) {
			$sql = "SELECT fn_calcula_extrato($login_fabrica,$extrato)";
			$res = pg_exec ($con,$sql);
			if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);

			if (strlen($msg_erro) == 0){
				$sql = "SELECT fn_aprova_extrato($posto,$login_fabrica,$extrato)";
				$res = pg_exec ($con,$sql);
				if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);
			}
		}
	}

	//header("Location: os_extrato.php");
	header("Location: os_extrato_detalhe.php?posto=$posto&data_limite=$data_limite");
	exit;

}

if ($btnacao == "recusar") {
	$qtde_os = $_POST["qtde_os"];
	for ($k = 0 ; $k < $qtde_os; $k++) {
		$x_os  = trim($_POST["os_" . $k]);
		$x_obs = trim($_POST["obs_" . $k]);

		if (strlen($x_obs) == 0) {
			$msg_erro    = " Informe a observação na OS $x_os. ";
			$linha_erro  = $k;
			$select_acao = "RECUSAR";
		}

		if (strlen($msg_erro) == 0) {
			$res = pg_exec($con,"BEGIN TRANSACTION");

			$sql =	"INSERT INTO tbl_os_status (
							os         ,
							observacao ,
							status_os
						) VALUES (
							$x_os    ,
							'$x_obs' ,
							13
						);";

			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);

			if (strlen($msg_erro) == 0) {
					$sql = "UPDATE tbl_os_extra SET extrato = null
							WHERE  tbl_os_extra.os      = $x_os ";

					if (strlen($extrato) > 0) {
						$sql .= "AND    tbl_os_extra.extrato = $extrato ";
					}

					$sql .= "AND    tbl_os_extra.os     = tbl_os.os
							AND    tbl_os_extra.extrato = tbl_extrato.extrato
							AND    tbl_extrato.extrato  = tbl_extrato_extra.extrato
							AND    tbl_extrato_extra.baixado IS NULL
							AND    tbl_os.fabrica  = $login_fabrica;";
				$res = @pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

			if (strlen($msg_erro) == 0) {
				if (strlen($extrato) > 0) {
					$sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
					$res = @pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
				}
			}

			if (strlen($msg_erro) == 0) {
				$res = pg_exec($con,"COMMIT TRANSACTION");
				$link = $_COOKIE["link"];
				header ("Location: $link");
				exit;
			}else{
				$res = pg_exec($con,"ROLLBACK TRANSACTION");
			}
		}
	}
}

if ($btnacao == "acumular") {
	$qtde_os = $_POST["qtde_os"];
	for ($k = 0 ; $k < $qtde_os; $k++) {
		$x_os  = trim($_POST["os_" . $k]);
		$x_obs = trim($_POST["obs_" . $k]);

		if (strlen($x_obs) == 0) {
			$msg_erro    = " Informe a observação na OS $x_os. ";
			$linha_erro  = $k;
			$select_acao = "ACUMULAR";
		}

		if (strlen($msg_erro) == 0) {
			$res = pg_exec($con,"BEGIN TRANSACTION");

			$sql =	"INSERT INTO tbl_os_status (
							os         ,
							observacao ,
							status_os
						) VALUES (
							$x_os    ,
							'$x_obs' ,
							14
						);";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);

			if (strlen($msg_erro) == 0) {
					$sql = "UPDATE tbl_os_extra SET extrato = null
							WHERE  tbl_os_extra.os      = $x_os ";

					if (strlen($extrato) > 0) {
						$sql .= "AND    tbl_os_extra.extrato = $extrato ";
					}

					$sql .= "AND    tbl_os_extra.os     = tbl_os.os
							AND    tbl_os_extra.extrato = tbl_extrato.extrato
							AND    tbl_extrato.extrato  = tbl_extrato_extra.extrato
							AND    tbl_extrato_extra.baixado IS NULL
							AND    tbl_os.fabrica  = $login_fabrica;";
				$res = @pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

			if (strlen($msg_erro) == 0) {
				if (strlen($extrato) > 0) {
					$sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
					$res = @pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
				}
			}

			if (strlen($msg_erro) == 0) {
				$res = pg_exec($con,"COMMIT TRANSACTION");
				$link = $_COOKIE["link"];
				header ("Location: $link");
				exit;
			}else{
				$res = pg_exec($con,"ROLLBACK TRANSACTION");
			}
		}
	}
}

include "cabecalho.php";

?>

<style type="text/css">
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B
}
.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: normal;
}
</style>

<script language="JavaScript">
var ok = false;
function checkaTodos() {
	f = document.frm_extrato;
	if (!ok) {
		for (i=0; i<f.length; i++){
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = true;
				ok=true;
			}
		}
	}else{
		for (i=0; i<f.length; i++) {
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = false;
				ok=false;
			}
		}
	}
}
</script>

<p>
<?

$posto = $_GET ['posto'];
if ($_POST['posto'] > 0) $posto = $_POST ['posto'];

$data_limite = $_GET ['data_limite'];
if ($_POST['data_limite'] > 0) $data_limite = $_POST ['data_limite'];

if (strlen ($data_limite) < 10) $data_limite = date ("d/m/Y");
$x_data_limite = substr ($data_limite,6,4) . "-" . substr ($data_limite,3,2) . "-" . substr ($data_limite,0,2);

if(strlen($msg_erro) > 0){
	echo "<TABLE width=\"700\" align='center' border=0>";
	echo "	<TR>";
	echo "		<TD align='center'>$msg_erro</TD>";
	echo "	</TR>";
	echo "</TABLE>";
}

// INICIO DA SQL POSTO
$sql = "SELECT nome AS posto_nome
		FROM   tbl_posto
		WHERE  posto = $posto";
$res = pg_exec ($con,$sql);

$posto_nome	= trim(pg_result ($res,0,posto_nome));

echo "<FORM METHOD='POST' NAME='frm_extrato' ACTION=\"$PHP_SELF\">\n";
echo "<input type='hidden' name='posto' value='$posto'>\n";
echo "<input type='hidden' name='data_limite' value='$data_limite'>\n";
echo "<input type='hidden' name='data_limite_post' value='$x_data_limite'>\n";

echo "<TABLE width=\"700\" height=\"18\" align='center'>\n";

echo "	<TR>\n";
echo "		<TD colspan ='3' background='imagens_admin/barrabg_titulo.gif'><b>$posto_nome<b><br></TD>\n";
echo "	</TR>\n";

echo "	<TR>\n";
echo "		<TD background='imagens_admin/barrabg_titulo.gif' style='color: #596d9b;'><b>Para ver detalhes de qualquer uma das OS clique em seu respectivo número. <br> Mantenha o cursor sobre o número da OS para ver datas de abertura e fechamento.<br></TD>\n";
echo "	</TR>\n";
echo "</TABLE>\n\n";
echo"<br>\n\n";

$sql =	"SELECT tbl_os.posto                                                          ,
				tbl_os.os                                                             ,
				tbl_os.sua_os                                                         ,
				tbl_os.mao_de_obra                                                    ,
				tbl_os.consumidor_revenda                                             ,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura         ,
				to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento       ,
				tbl_os.consumidor_nome                       AS consumidor            ,
				tbl_os.revenda_nome                          AS revenda               ,
				tbl_os.pecas                                                          ,
				tbl_produto.descricao                                                 ,
				subconjunto.descricao                        AS subconjunto_descricao ,
				tbl_os.serie                                                          ,
				tbl_defeito_constatado.descricao             AS defeito_constatado    ,
				tbl_peca.referencia                          AS peca_referencia       ,
				tbl_peca.descricao                           AS peca_descricao        ,
				tbl_os_item.posicao
		FROM tbl_os
		JOIN (
				SELECT  tbl_posto_fabrica.posto,
						tbl_posto_fabrica.fabrica
				FROM    tbl_posto_fabrica
				WHERE   tbl_posto_fabrica.posto   = $posto
				AND     tbl_posto_fabrica.fabrica = $login_fabrica
			) AS tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
		JOIN (
				SELECT tbl_os_extra.os
				FROM   tbl_os_extra
				WHERE  tbl_os_extra.extrato ISNULL
			) AS tbl_os_extra ON tbl_os_extra.os = tbl_os.os
		JOIN      tbl_produto                ON tbl_produto.produto            = tbl_os.produto
		JOIN      tbl_os_produto             ON tbl_os_produto.os              = tbl_os.os
		LEFT JOIN tbl_os_item                ON tbl_os_produto.os_produto      = tbl_os_item.os_produto
		LEFT JOIN tbl_produto AS subconjunto ON subconjunto.produto            = tbl_os_produto.produto
		LEFT JOIN tbl_lista_basica           ON tbl_lista_basica.produto       = tbl_os_produto.produto
											AND tbl_lista_basica.peca          = tbl_os_item.peca
											AND trim(tbl_lista_basica.posicao) = trim(tbl_os_item.posicao)
		LEFT JOIN tbl_defeito_constatado     ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
		LEFT JOIN tbl_peca                   ON tbl_peca.peca             = tbl_os_item.peca
		LEFT JOIN tbl_os_status              ON tbl_os_status.os = tbl_os.os
		WHERE     tbl_os.data_fechamento <= '$x_data_limite'
		AND       tbl_os.finalizada IS NOT NULL
		AND       tbl_os.excluida   IS NOT TRUE
		AND       tbl_os_status.status_os NOT IN (13,14,15)
		ORDER BY  lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,'0') ASC,
					replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC";


$sql =	"SELECT     tbl_os.posto                                                          ,
					tbl_os.os                                                             ,
					tbl_os.sua_os                                                         ,
					tbl_os.mao_de_obra                                                    ,
					tbl_os.consumidor_revenda                                             ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura         ,
					TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento       ,
					tbl_os.consumidor_nome                       AS consumidor            ,
					tbl_os.revenda_nome                          AS revenda               ,
					tbl_os.pecas                                                          ,
					tbl_produto.descricao                                                 ,
					subconjunto.descricao                        AS subconjunto_descricao ,
					tbl_os.serie                                                          ,
					tbl_defeito_constatado.descricao             AS defeito_constatado    ,
					tbl_peca.referencia                          AS peca_referencia       ,
					tbl_peca.descricao                           AS peca_descricao        ,
					tbl_os_item.posicao                                                   ,
					tbl_servico_realizado.descricao              AS servico_realizado
		FROM      tbl_os
		JOIN      tbl_posto_fabrica          ON tbl_posto_fabrica.posto                   = tbl_os.posto
											AND tbl_posto_fabrica.fabrica                 = tbl_os.fabrica
		JOIN      tbl_os_extra               ON tbl_os_extra.os                           = tbl_os.os
		JOIN      tbl_produto                ON tbl_produto.produto                       = tbl_os.produto
		LEFT JOIN tbl_os_produto             ON tbl_os_produto.os                         = tbl_os.os
		LEFT JOIN tbl_os_item                ON tbl_os_produto.os_produto                 = tbl_os_item.os_produto
		LEFT JOIN tbl_produto AS subconjunto ON subconjunto.produto                       = tbl_os_produto.produto
		LEFT JOIN tbl_lista_basica           ON tbl_lista_basica.produto                  = tbl_os_produto.produto
											AND tbl_lista_basica.peca                     = tbl_os_item.peca
											AND trim(tbl_lista_basica.posicao)            = trim(tbl_os_item.posicao)
		LEFT JOIN tbl_defeito_constatado     ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
		LEFT JOIN tbl_servico_realizado      ON tbl_servico_realizado.servico_realizado   = tbl_os_item.servico_realizado
		LEFT JOIN tbl_peca                   ON tbl_peca.peca                             = tbl_os_item.peca
		LEFT JOIN tbl_os_status              ON tbl_os_status.os                          = tbl_os.os
		WHERE tbl_os.data_fechamento <= '$x_data_limite'
		AND   tbl_os.posto            = $posto
		AND   tbl_os.fabrica          = $login_fabrica
		AND   tbl_os.finalizada      IS NOT NULL
		AND   tbl_os_extra.extrato   ISNULL
		AND   tbl_os.excluida        IS NOT TRUE
		AND   (tbl_os_status.status_os NOT IN (13,15) OR tbl_os_status.status_os IS NULL)
		ORDER BY lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,'0') ASC,
				 replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC";

//if ($ip == "201.0.9.216") echo nl2br($sql);
$res = pg_exec ($con,$sql);

//$sqlCount  = "SELECT count(*) FROM (";
//$sqlCount .= $sql;
//$sqlCount .= ") AS count";

//if ($ip == '201.0.9.216') echo "<br>".nl2br($sql)."<br>";

$totalRegistros = pg_numrows($res);

if ($totalRegistros > 0) {
	echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
	echo "<TR class='menu_top'>\n";
	echo "<TD>OS</TD>\n";
	echo "<TD>CLIENTE</TD>\n";
	echo "<TD>PRODUTO</TD>\n";
	echo "<TD>SUBPRODUTO</TD>\n";
	echo "<TD>Nº SÉRIE</TD>\n";
	echo "<TD>DEFEITO</TD>\n";
	echo "<TD>COMPONENTE</TD>\n";
	echo "<TD>SERVIÇO</TD>\n";
	echo "<TD>POSIÇÃO</TD>\n";
	echo "<TD>MO</TD>\n";
	echo "<TD>PEÇAS</TD>\n";
	echo "</TR>\n";

	$valorTotal				= 0;
	$valorMaoDeObra			= 0;
	$valorPeca				= 0;
	$valorMaoDeObraRevenda	= 0;
	$valorPecaRevenda		= 0;

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$os                    = trim(pg_result($res,$i,os));
		$sua_os                = trim(pg_result($res,$i,sua_os));
		$mao_de_obra           = trim(pg_result($res,$i,mao_de_obra));
		$data_abertura         = trim(pg_result($res,$i,data_abertura));
		$data_fechamento       = trim(pg_result($res,$i,data_fechamento));
		$pecas                 = trim(pg_result($res,$i,pecas));
		$consumidor            = trim(pg_result($res,$i,consumidor));
		$revenda               = trim(pg_result($res,$i,revenda));
		$consumidor_revenda    = trim(pg_result($res,$i,consumidor_revenda));
		$descricao             = trim(pg_result($res,$i,descricao));
		$subconjunto_descricao = trim(pg_result($res,$i,subconjunto_descricao));
		$serie                 = trim(pg_result($res,$i,serie));
		$defeito_constatado    = trim(pg_result($res,$i,defeito_constatado));
		$peca_referencia       = trim(pg_result($res,$i,peca_referencia));
		$peca_descricao        = trim(pg_result($res,$i,peca_descricao));
		$posicao               = trim(pg_result($res,$i,posicao));
		$servico_realizado     = trim(pg_result($res,$i,servico_realizado));

		# soma valores
		if ($consumidor_revenda == 'R' AND $login_fabrica == 6){
			$valorMaoDeObraRevenda		= $valorMaoDeObraRevenda + $mao_de_obra;
			$valorPecaRevenda			= $valorPecaRevenda + $peca;
			$mao_de_obraForm			= '0,00';
			$mao_de_obra_revendaForm	= number_format($mao_de_obra,2,",",".");
			$pecasForm					= '0,00';
			$pecasFormRevenda			= number_format($pecas,2,",",".");
		}else{
			if ($os <> $os_anterior) { // Não deixa somar o valor da mão-de-obra quando for a mesma OS
				$valorMaoDeObra				= $valorMaoDeObra + $mao_de_obra;
			}
			$valorPeca					= $valorPeca + $peca;
			$mao_de_obraForm			= number_format($mao_de_obra,2,",",".");
			$mao_de_obra_revendaForm	= '0,00';
			$pecasForm					= number_format($pecas,2,",",".");
			$pecasFormRevenda			= '0,00';
		}

		$valor			= $mao_de_obra + $pecas;
		if ($os <> $os_anterior) { // Não deixa somar o valor total quando for a mesma OS
			$valorTotal		= $valorTotal + $valor;
		}
		# formata valores
		$pecasForm		= number_format($pecas,2,",",".");

		$osX = $sua_os ;
		if (strlen ($osX) == 0) $osX = $os ;

		if ($os <> $os_anterior) {
			$cor = "#D9E2EF";
			$btn = 'amarelo';
			if ($j % 2 == 0){
				$cor = '#F1F4FA';
				$btn = 'azul';
			}
		}
		else $j++;

		if (strstr($matriz, ";" . $i . ";")) $cor = '#E49494';

		echo "	<TR class='table_line' style='background-color: $cor;' rowspan='$colspanX'>\n";

		if ($os == $os_anterior) {
				echo "		<TD>&nbsp;</TD>\n";
				echo "		<TD>&nbsp;</TD>\n";
				echo "		<TD>&nbsp;</TD>\n";
				echo "		<TD nowrap><acronym title='Subproduto: $subconjunto_descricao'>".substr($subconjunto_descricao,0,20)."</acronym></TD>\n";
				echo "		<TD>&nbsp;</TD>\n";
				echo "		<TD>&nbsp;</TD>\n";
				echo "		<TD nowrap><acronym title='Componente: $peca_descricao'>" . substr($peca_descricao,0,15) . "</TD>\n";
				echo "		<TD nowrap><acronym title='Serviço: $servico_realizado'>" . substr($servico_realizado,0,15) . "</TD>\n";
				echo "		<TD><acronym title='Posição: $posicao'>".substr($posicao,0,8)."</TD>\n";
				echo "		<TD>&nbsp;</TD>\n";
				echo "		<TD>&nbsp;</TD>\n";
		}else{
			echo "<TD rowrap align='center'><acronym title='Abertura: $data_abertura | Fechamento: $data_fechamento'><a href='os_press.php?os=$os&posto=$posto'>$osX</a></acronym></TD>\n";
			if (strlen($consumidor) > 0) {
				echo "<TD nowrap align='left'><acronym title='Consumidor: $consumidor'>".substr($consumidor,0,12)."</acronym></TD>\n";
			}else{
				if (strlen($revenda) > 0) echo "<TD nowrap align='left'><acronym title='Revenda: $revenda'>".substr($revenda,0,15)."</acronym></TD>\n";
				else					echo "<TD nowrap ='left'>&nbsp;</TD>\n";
			}
			echo "		<TD nowrap><acronym title='Produto: $descricao'>".substr($descricao,0,25)."</acronym></TD>\n";
			echo "		<TD nowrap><acronym title='Subproduto: $subconjunto_descricao'>".substr($subconjunto_descricao,0,20)."</acronym></TD>\n";
			echo "		<TD nowrap align='center'> $serie </TD>\n";
			echo "		<TD nowrap><acronym title='Defeito: $defeito_constatado'>".substr($defeito_constatado,0,15)."</TD>\n";
			echo "		<TD nowrap><acronym title='$peca_descricao'>".substr($peca_descricao,0,15)."</acronym></TD>\n";
			echo "		<TD nowrap><acronym title='Serviço: $servico_realizado'>" . substr($servico_realizado,0,15) . "</TD>\n";
			echo "		<TD nowrap><acronym title='Posição: $posicao'>".substr($posicao,0,8)."</acronym></TD>\n";
			echo "		<TD nowrap align='right'> $mao_de_obraForm </TD>\n";
			echo "		<TD nowrap align='right'> $pecasForm </TD>\n";

			$os_anterior = $os;
		}
		echo "	</TR>\n";
		$j++;
	}

	# formata valores
	$valorMaoDeObra			= number_format($valorMaoDeObra,2,",",".");
	$valorMaoDeObraRevenda	= number_format($valorMaoDeObraRevenda,2,",",".");
	$valorPeca				= number_format($valorPeca,2,",",".");
	$valorPecaRevenda		= number_format($valorPecaRevenda,2,",",".");
	$valorTotal				= number_format($valorTotal,2,",",".");

	echo "	<TR class='table_line'>\n";

	echo "<TD align='right' bgcolor='#F1F4FA' style='padding-right:5px' class='menu_top' colspan='9'>SUB-TOTAIS</TD>\n";
	echo "<TD align='right' bgcolor='#F1F4FA' style='padding-right:5px'><b>$valorMaoDeObra</b></TD>\n";
	echo "<TD align='right' bgcolor='#F1F4FA' style='padding-right:5px'><b>$valorPeca</b></TD>\n";
	echo "</TR>\n";
	echo "<TR>\n";
	echo "<TD align='center' style='padding-right:10px' class='menu_top' colspan='9'><b>TOTAL (MO + Peças)</b></TD>\n";
	echo "<TD bgcolor='$cor' align='center' colspan='4'><b>R$ $valorTotal</b></TD>\n";
	echo "</TR>\n";
}

echo "</TABLE>\n";


echo "<br>";
?>

<TABLE align='center'>
<TR>
	<TD>
		<br>
		<input type='hidden' name='total' value='<? echo $totalRegistros; ?>'>
		<input type='hidden' name='btnacao' value=''>
		<? if ($login_fabrica == 6){ ?>
		<img src="imagens/btn_fechar_azul.gif" onclick="javascript: document.frm_extrato.btnacao.value = 'aprovar'; document.frm_extrato.submit();" ALT="Aprovar" border='0' style="cursor:pointer;">
		<? } ?>
		<img src="imagens/btn_voltar.gif" onclick="javascript: window.location='os_extrato.php';" ALT="Voltar" border='0' style="cursor:pointer;">
		<?
			if ($login_fabrica == 2){
		?>
		<a href='os_extrato_detalhe_print.php?posto=<? echo $posto; ?>&data_limite=<? echo $data_limite; ?>' target='_blank'><img src="imagens/btn_imprimir.gif" ALT="Imprimir" border='0' style="cursor:pointer;"></a>
		<?
			}
		?>
	</TD>
</TR>
</TABLE>

</FORM>

<p>
<p>

<? include "rodape.php"; ?>
