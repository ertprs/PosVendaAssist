<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");

$admin_privilegios="financeiro";
include "autentica_admin.php";
include 'funcoes.php';

$extrato_devolucao = $_REQUEST['extrato_devolucao'];
$posto_telecontrol = $_REQUEST['posto_telecontrol'];
$os_pesquisa       = trim($_REQUEST['os_pesquisa']);

if(isset($_REQUEST['btn_pesquisa']) and !isset($os_pesquisa)) {
	$msg_erro = "Preenche o número de OS para fazer a pesquisa";
}

if(isset($_POST['btn_gravar']) and isset($os_pesquisa) and isset($posto_telecontrol)) {
	if(empty($_POST['laudo_tecnico_'.$os_pesquisa])) {
		$msg_erro = "Por favor, descreva o laudo técnico sobre a OS $os_pesquisa.";
	}else{
		$sqlp = " SELECT peca,referencia,qtde,descricao
				FROM tbl_os
				JOIN tbl_lista_basica USING(produto)
				JOIN tbl_peca USING(peca)
				WHERE os = $os_pesquisa";
		$resp = pg_query($con,$sqlp);
		if(pg_num_rows($resp) > 0){
			$res = pg_query ($con,"BEGIN TRANSACTION");
			for($j =0;$j<pg_num_rows($resp);$j++) {
				$peca       = pg_fetch_result($resp,$j,'peca');
				$referencia = pg_fetch_result($resp,$j,'referencia');
				$qtde       = pg_fetch_result($resp,$j,'qtde');
				$descricao  = pg_fetch_result($resp,$j,'descricao');

				if($_POST[$peca.'_'.$os_pesquisa] == 't') {
					$sqle = "SELECT fn_estoque_scrap($peca,$qtde,$login_fabrica,'Peça Scrapeada em OS $os_pesquisa');";
					$rese = pg_query($con,$sqle);
					$msg_erro = pg_last_error($con);
					$laudo_tecnico_peca .= "<br/>$referencia - $descricao\n";
				}
			}

			if(empty($msg_erro)) {
				$laudo_tecnico = str_replace("\"","",str_replace("'","",$_POST['laudo_tecnico_'.$os_pesquisa]));
				$laudo_tecnico .= "<br/>". $laudo_tecnico_peca;
				$sqll = " UPDATE tbl_os_extra
						SET laudo_tecnico = '$laudo_tecnico',
							scrap = 't',
							data_scrap=current_timestamp
						WHERE tbl_os_extra.os = $os_pesquisa";
				$resl = pg_query($con,$sqll);
				$msg_erro = pg_last_error($con);
			}
			if(empty($msg_erro)) {
				$res = pg_query ($con,"COMMIT TRANSACTION");
			}else{
				$res = pg_query ($con,"ROLLBACK TRANSACTION");
			}
		}
	}
}

if(isset($_REQUEST['extrato_devolucao']) and isset($_POST['btn_acao'])) {
	$sql="SELECT  tbl_os_troca.os,
				  tbl_os.sua_os
			FROM tbl_faturamento
			JOIN tbl_faturamento_item USING(faturamento)
			JOIN tbl_os ON tbl_os.os = tbl_faturamento_item.os AND tbl_os.fabrica =$login_fabrica
			JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os AND tbl_os_troca.fabric= $login_fabrica
			WHERE  tbl_faturamento_item.extrato_devolucao= $extrato_devolucao;";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		for($i =0;$i<pg_num_rows($res);$i++) {
			$os     = pg_fetch_result($res,$i,'os');
			$sua_os = pg_fetch_result($res,$i,'sua_os');

			if(isset($_POST['os_'.$os])) {
				if(empty($_POST['laudo_tecnico_'.$os])) {
					$msg_erro = "Por favor, descreva o laudo técnico sobre a OS $sua_os.";
				}else{
					$sqlp = " SELECT peca,referencia,qtde,descricao
							FROM tbl_os
							JOIN tbl_lista_basica USING(produto)
							JOIN tbl_peca USING(peca)
							WHERE os = $os";
					$resp = pg_query($con,$sqlp);
					if(pg_num_rows($resp) > 0){
						$res = pg_query ($con,"BEGIN TRANSACTION");
						for($j =0;$j<pg_num_rows($resp);$j++) {
							$peca       = pg_fetch_result($resp,$j,'peca');
							$referencia = pg_fetch_result($resp,$j,'referencia');
							$qtde       = pg_fetch_result($resp,$j,'qtde');
							$descricao  = pg_fetch_result($resp,$j,'descricao');

							if($_POST[$peca.'_'.$os] == 't') {
								$sqle = "SELECT fn_estoque_scrap($peca,$qtde,$login_fabrica,'Peça Scrapeada em OS $sua_os');";
								$rese = pg_query($con,$sqle);
								$msg_erro = pg_last_error($con);
								$laudo_tecnico_peca .= "<br/>$referencia - $descricao\n";
							}
						}

						if(empty($msg_erro)) {
							$laudo_tecnico = str_replace("\"","",str_replace("'","",$_POST['laudo_tecnico_'.$os]));
							$laudo_tecnico .= "<br/>". $laudo_tecnico_peca;
							$sqll = " UPDATE tbl_os_extra
									SET laudo_tecnico = '$laudo_tecnico',
										scrap = 't',
										data_scrap=current_timestamp
									WHERE tbl_os_extra.os = $os";
							$resl = pg_query($con,$sqll);
							$msg_erro = pg_last_error($con);
						}
						if(empty($msg_erro)) {
							$res = pg_query ($con,"COMMIT TRANSACTION");
						}else{
							$res = pg_query ($con,"ROLLBACK TRANSACTION");
						}
					}
				}
			}
		}
	}
}


$layout_menu = "financeiro";
$title = "Relação de Ordens de Serviços para Scrap";
include "cabecalho.php";

?>
<p>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B;
	background-image:'imagens_admin/azul.gif'
}


.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF;

}

.error{
	BORDER-RIGHT: #990000 1px solid;
	BORDER-TOP: #990000 1px solid;
	FONT: 10pt Arial ;
	COLOR: #ffffff;
	BORDER-LEFT: #990000 1px solid;
	BORDER-BOTTOM: #990000 1px solid;
	BACKGROUND-COLOR: #FF0000;
}
</style>


<? if (strlen ($msg_erro) > 0) { ?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
		<? echo $msg_erro ; $msg_erro = ''; ?>
	</td>
</tr>
</table>
<? }
if (strlen ($msg_aviso) > 0) {
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
		<? echo $msg_aviso ;
		$msg_aviso = '';
		?>

	</td>
</tr>
</table>
<? } 
echo "<center>";

if (strlen($extrato_devolucao) > 0) {
	$sql = "SELECT  tbl_os_troca.os,
					tbl_os.sua_os  ,
					tbl_os.produto
			INTO TEMP tmp_scrap_$login_admin
			FROM tbl_faturamento
			JOIN tbl_faturamento_item USING(faturamento)
			JOIN tbl_os ON tbl_os.os = tbl_faturamento_item.os AND tbl_os.fabrica =$login_fabrica
			JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os AND tbl_os_troca.fabric= $login_fabrica
			WHERE  tbl_faturamento_item.extrato_devolucao= $extrato_devolucao;

			SELECT 	tbl_produto.referencia,
					tbl_produto.descricao ,
					tbl_produto.produto   ,
					sua_os                ,
					os                    ,
					scrap                 
			FROM    tmp_scrap_$login_admin
			JOIN    tbl_produto USING(produto)
			JOIN    tbl_os_extra USING(os)
			WHERE   1 =1 ;";
	$res = pg_query($con,$sql);
	
	if (@pg_num_rows($res) == 0) {
		echo "<h1>Nenhum resultado encontrado.</h1>";
	}else{
		echo "<table><thead>";
		echo "<tr height='15' class='menu_top'>";
		echo "<td colspan='2'>OS</td>";
		echo "<td colspan='2'>Produto</td>";
		echo "</tr></thead>";
		echo "<tbody>";

		for($i =0;$i<pg_num_rows($res);$i++) {
			$referencia = pg_fetch_result($res,$i,referencia);
			$descricao  = pg_fetch_result($res,$i,descricao);
			$produto    = pg_fetch_result($res,$i,produto);
			$sua_os     = pg_fetch_result($res,$i,sua_os);
			$os         = pg_fetch_result($res,$i,os);
			$scrap      = pg_fetch_result($res,$i,scrap);

			echo "<tr class='table_line'>";
			echo "<td colspan='2'><a href='os_press.php?os=$os'>$sua_os</a></td>";
			echo "<td colspan='2'>$referencia - $descricao</td>";
			echo "</tr>";

			if(!$scrap){
				echo "<form name='frm_$os' method='post' action='$PHP_SELF'>";
				$sqls = " SELECT tbl_peca.referencia,
								tbl_peca.descricao,
								tbl_peca.peca,
								tbl_posto_estoque_localizacao.localizacao
						FROM tbl_os
						JOIN tbl_lista_basica USING(produto)
						JOIN tbl_peca USING(peca)
						LEFT JOIN tbl_posto_estoque_localizacao USING(peca)
						WHERE os=$os
						AND   tbl_peca.produto_acabado IS NOT TRUE
						AND   NOT(tbl_peca.descricao ~* 'embalagem')
						AND   tbl_peca.fabrica = $login_fabrica
						AND   tbl_os.fabrica = $login_fabrica";
				$ress = pg_query($con,$sqls);

				if(pg_num_rows($ress) > 0){
					echo "<input type='hidden' name='os_$os' value='$os'>";
					echo "<input type='hidden' name='extrato_devolucao' value='$extrato_devolucao'>";
					for($j =0;$j<pg_num_rows($ress);$j++) {
						$referencia = pg_fetch_result($ress,$j,'referencia');
						$descricao  = pg_fetch_result($ress,$j,'descricao');
						$localizacao= pg_fetch_result($ress,$j,'localizacao');
						$peca       = pg_fetch_result($ress,$j,'peca');

						echo "<tr>";
						echo "<td><img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'></td>";
						echo "<td><input type='checkbox' name='".$peca."_".$os."' value='t' id='".$peca."_".$os."' CHECKED></td>";
						echo "<td><label for='".$peca."_".$os."'>$referencia - $descricao</label></td>";
						echo "<td>$localizacao</td>";
						echo "</tr>";
					}
					echo "<tr><td colspan='100%' align='center'><br/>Laudo Técnico: <br/><textarea rows='10' cols='60' name='laudo_tecnico_$os'></textarea></td></tr>
					<tr><td colspan='100%' align='center'><input type='submit' name='btn_acao' value='Gravar'><br/></td></tr>";
				}
				echo "</form>";
			}else{
				echo "<tr>";
				echo "<td colspan='100%' align='center'>OS já scrapeada</td>";
				echo "</tr>";
			}
		}
	}
}

if(isset($_REQUEST['posto_telecontrol'])) {?>
<br/>
<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center'>
<input type='hidden' name='posto_telecontrol' value=<?=$posto_telecontrol?>>
<table class='Tabela' width='300' cellspacing='0'  cellpadding='0' bgcolor='#596D9B' align='center'>
	<tr >
		<td class="menu_top" align='center' ><font color='ffffff'>Pesquisa</td>
	</tr>
	<tr>
		<td bgcolor='#F3F8FE' align='center'>
			OS&nbsp;<input type='text' name='os_pesquisa' value='<?=$os_pesquisa?>'>
		</td>
	</tr>
	<tr>
		<td bgcolor='#F3F8FE' align='center'>
			<input type='submit' name='btn_pesquisa' value='Pesquisar'>
		</td>
	</tr>
</table>
</form>

<?}

if(isset($_REQUEST['btn_pesquisa']) and isset($os_pesquisa)) {

	$sql = "SELECT 	DISTINCT tbl_produto.referencia,
					tbl_produto.descricao ,
					tbl_produto.produto   ,
					sua_os                ,
					tbl_os.os                    ,
					scrap                 
			FROM    tbl_os
			JOIN    tbl_produto USING(produto)
			JOIN    tbl_os_extra USING(os)
			JOIN    tbl_os_troca ON tbl_os.os = tbl_os_troca.os
			WHERE   tbl_os.fabrica = $login_fabrica
			AND     tbl_os.posto=4311
			AND     tbl_os.sua_os like '%$os_pesquisa%';";
	$res = pg_query($con,$sql);
	
	if (@pg_num_rows($res) == 0) {
		echo "<h1>Nenhum resultado encontrado.</h1>";
	}else{
		echo "<br/>";
		echo "<table><thead>";
		echo "<tr height='15' class='menu_top'>";
		echo "<td colspan='2'>OS</td>";
		echo "<td colspan='2'>Produto</td>";
		echo "</tr></thead>";
		echo "<tbody>";

		for($i =0;$i<pg_num_rows($res);$i++) {
			$referencia = pg_fetch_result($res,$i,referencia);
			$descricao  = pg_fetch_result($res,$i,descricao);
			$produto    = pg_fetch_result($res,$i,produto);
			$sua_os     = pg_fetch_result($res,$i,sua_os);
			$os         = pg_fetch_result($res,$i,os);
			$scrap      = pg_fetch_result($res,$i,scrap);

			echo "<tr class='table_line'>";
			echo "<td colspan='2'><a href='os_press.php?os=$os'>$sua_os</a></td>";
			echo "<td colspan='2'>$referencia - $descricao</td>";
			echo "</tr>";

			if(!$scrap){
				echo "<form name='frm_$os' method='post' action='$PHP_SELF'>";
				$sqls = " SELECT tbl_peca.referencia,
								tbl_peca.descricao,
								tbl_peca.peca,
								tbl_posto_estoque_localizacao.localizacao
						FROM tbl_os
						JOIN tbl_lista_basica USING(produto)
						JOIN tbl_peca USING(peca)
						LEFT JOIN tbl_posto_estoque_localizacao USING(peca)
						WHERE os=$os
						AND   tbl_peca.produto_acabado IS NOT TRUE
						AND   NOT(tbl_peca.descricao ~* 'embalagem')
						AND   tbl_peca.fabrica = $login_fabrica
						AND   tbl_os.fabrica = $login_fabrica";
				$ress = pg_query($con,$sqls);

				if(pg_num_rows($ress) > 0){
					echo "<input type='hidden' name='os_pesquisa' value='$os_pesquisa'>";
					echo "<input type='hidden' name='posto_telecontrol' value='$posto_telecontrol'>";
					echo "<input type='hidden' name='btn_pesquisa' value='$btn_pesquisa'>";
					for($j =0;$j<pg_num_rows($ress);$j++) {
						$referencia = pg_fetch_result($ress,$j,'referencia');
						$descricao  = pg_fetch_result($ress,$j,'descricao');
						$localizacao= pg_fetch_result($ress,$j,'localizacao');
						$peca       = pg_fetch_result($ress,$j,'peca');

						echo "<tr>";
						echo "<td><img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'></td>";
						echo "<td><input type='checkbox' name='".$peca."_".$os."' value='t' id='".$peca."_".$os."' CHECKED></td>";
						echo "<td><label for='".$peca."_".$os."'>$referencia - $descricao</label></td>";
						echo "<td>$localizacao</td>";
						echo "</tr>";
					}
					echo "<tr><td colspan='100%' align='center'><br/>Laudo Técnico: <br/><textarea rows='10' cols='60' name='laudo_tecnico_$os'></textarea></td></tr>
					<tr><td colspan='100%' align='center'><input type='submit' name='btn_gravar' value='Gravar'><br/></td></tr>";
				}
				echo "</form>";
			}else{
				echo "<tr>";
				echo "<td colspan='100%' align='center'>OS já scrapeada</td>";
				echo "</tr>";
			}
		}
	}
}



echo "</center>";
include "rodape.php"; ?>
