<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$msg_erro;

if (strlen($_POST["btn_acao"]) > 0)   $btn_acao   = $_POST["btn_acao"];
else $btn_acao   = $_GET["btn_acao"];

if (strlen($_POST["linha"]) > 0)    $linha    = trim($_POST["linha"]);
else $linha    = trim($_GET["linha"]);

if ($btn_acao == "GRAVAR") {
	$produto_qtde = $_POST["produto_qtde"];
	$pagina_atual = $_POST["pagina_atual"];

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	for ($i = 0 ; $i < $produto_qtde ; $i++) {
		$xproduto                  = trim($_POST["produto_".$i]);
		$xreferencia               = trim($_POST["referencia_".$i]);
		$xgarantia                 = trim($_POST["garantia_".$i]);
		$xmao_de_obra              = trim($_POST["mao_de_obra_posto_".$i]);
		$xmao_de_obra              = str_replace(",",".",$xmao_de_obra);
		$xmao_de_obra_admin        = trim($_POST["mao_de_obra_admin_".$i]);
		$xmao_de_obra_admin        = str_replace(",",".",$xmao_de_obra_admin);
		$xradical_serie            = trim($_POST["radical_serie_".$i]);
		$xnumero_serie_obrigatorio = trim($_POST["numero_serie_obrigatorio_".$i]);
		$xativo                    = trim($_POST["ativo_".$i]);
		$xtroca_obrigatoria        = trim($_POST["troca_obrigatoria_".$i]);
		$xorigem                   = trim($_POST["origem_".$i]);

//takashi
		if (strlen($_POST["origem_$i"]) > 0) $xorigem = "'". trim($_POST["origem_$i"]) ."'";
		else $xorigem = "null";
//takashi

		if (strlen($xgarantia) > 0) $xgarantia = "'".$xgarantia."'";
		else                        $msg_erro = " Digite a quantidade de meses da garantia do Produto $xreferencia. ";

		if (strlen($xmao_de_obra) > 0) $xmao_de_obra = "'".$xmao_de_obra."'";
		else                           $msg_erro = " Digite o valor da Mão de Obra do Posto do Produto $xreferencia. ";

		if (strlen($msg_erro) > 0) $produto_erro = $xproduto;

		if (strlen($xmao_de_obra_admin) > 0) $xmao_de_obra_admin = "'".$xmao_de_obra_admin."'";
		else                                 $xmao_de_obra_admin = '0';

		if (strlen($xradical_serie) > 0) $xradical_serie = "'".$xradical_serie."'";
		else                             $xradical_serie = 'null';

		if (strlen($xnumero_serie_obrigatorio) > 0) $xnumero_serie_obrigatorio = "'".$xnumero_serie_obrigatorio."'";
		else                                        $xnumero_serie_obrigatorio = 'null';

		if (strlen($xativo) > 0) $xativo = "'".$xativo."'";
		else                     $xativo = "'f'";

		if (strlen($xtroca_obrigatoria) > 0) $xtroca_obrigatoria = "'".$xtroca_obrigatoria."'";
		else                                 $xtroca_obrigatoria = "'f'";


		if (strlen($msg_erro) == 0) {
			$sql =	"UPDATE tbl_produto SET
						garantia                 = $xgarantia                 ,
						mao_de_obra              = $xmao_de_obra              ,
						mao_de_obra_admin        = $xmao_de_obra_admin        ,
						radical_serie            = $xradical_serie            ,
						numero_serie_obrigatorio = $xnumero_serie_obrigatorio ,
						ativo                    = $xativo                    ,
						troca_obrigatoria        = $xtroca_obrigatoria        ,
						origem                   = $xorigem 
					WHERE produto = $xproduto;";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header("Location: $PHP_SELF?btn_acao=LISTAR&linha=$linha");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		$btn_acao = "LISTAR";
	}
}

$layout_menu = "cadastro";
$title = "Manutenção de Produtos";
include 'cabecalho.php';
?>

<style type='text/css'>
.conteudo {
	font: bold xx-small Verdana, Arial, Helvetica, sans-serif;
	color: #000000;
}

</style>

<? if (strlen($msg_erro) > 0) { ?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '600'>
	<tr>
		<td valign="middle" align="center" class='error'><? echo $msg_erro; ?></td>
	</tr>
</table>
<? } ?>

<br>
<form name="frm_produto" method="POST" action="<? echo $PHP_SELF ?>">
<input type='hidden' name='btn_acao' value=''>
<? if ($btn_acao <> 'LISTAR') { ?>
	<table width='450' border='0' class='conteudo' cellpadding='2' cellspacing='1' align='center'>
		<tr>
			<td width='150' bgcolor='#D9E2EF' align='center'><b>Selecione a Linha :</b></td>
			<td width='300' bgcolor='#F1F4FA'>
				<?
				$sql = "SELECT * FROM tbl_linha WHERE fabrica = $login_fabrica ORDER BY nome;";
				$res = pg_exec ($con,$sql);
				if (pg_numrows($res) > 0) {
					echo "<select class='frm' name='linha' style='width: 280px;' onChange=\"javascript: if (document.frm_produto.btn_acao.value == '') { document.frm_produto.btn_acao.value='LISTAR'; document.frm_produto.submit() }else{ alert('Aguarde Submissão') } \">\n";
					echo "<option value=''>ESCOLHA</option>\n";
					for ($x = 0 ; $x < pg_numrows($res) ; $x++){
						$linha = trim(pg_result($res,$x,linha));
						$nome  = trim(pg_result($res,$x,nome));
						echo "<option value='$linha'>$nome</option>\n";
					}
					echo "</select>\n";
				}
				?>
			</td>
		</tr>
	</table>
<? }else{ ?>
	<input type='hidden' name='linha' value='<?echo $linha?>'>
	<table width='450' border='0' class='conteudo' cellpadding='2' cellspacing='1' align='center'>
		<tr>
			<td width='150' bgcolor='#D9E2EF' align='center'><b>Linha selecionada :</b></td>
			<td width='300' bgcolor='#F1F4FA' align='left'>
			&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
			<?
			if (strlen($linha) > 0) {
				$sql = "SELECT nome FROM tbl_linha WHERE fabrica = $login_fabrica AND linha = $linha;";
				$res = pg_exec ($con,$sql);
				if (pg_numrows($res) > 0) {
					echo pg_result($res,0,0);
				}
			}
			?>
			</td>
		</tr>
	</table>
	<p align='center'><a href='<?echo $PHP_SELF?>?'>Clique aqui para localizar outra Linha</a></p>
<?
}

echo "<br>\n";

if ($btn_acao == "LISTAR" AND strlen($linha) > 0) {

	$sql =	"SELECT tbl_produto.produto                                       ,
					tbl_produto.referencia                                    ,
					tbl_produto.descricao                                     ,
					tbl_produto.voltagem                                      ,
					tbl_produto.garantia                                      ,
					tbl_produto.mao_de_obra              AS mao_de_obra_posto ,
					tbl_produto.mao_de_obra_admin                             ,
					tbl_produto.radical_serie                                 ,
					tbl_produto.ativo                                         ,
					tbl_produto.numero_serie_obrigatorio                      ,
					tbl_familia.familia                                       ,
					tbl_familia.descricao                AS familia_descricao ,
					tbl_produto.origem,
					tbl_produto.troca_obrigatoria
			FROM	tbl_produto
			JOIN	tbl_familia USING (familia)
			JOIN	tbl_linha USING (linha)
			WHERE	tbl_linha.fabrica   = $login_fabrica
			AND		tbl_familia.fabrica = $login_fabrica
			AND		tbl_linha.linha     = $linha
			ORDER BY tbl_familia.descricao, tbl_produto.referencia";
#echo nl2br($sql);
	$res = pg_exec($con,$sql);
//echo "$sql";
	if (pg_numrows($res) == 0) {
		echo "<table width='700' height='50' align='center'><tr><td align='center'>Nenhum resultado encontrado.</td></tr></table>";
	}else{

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {

			$cor = ($i % 2 == 0) ? "#FFFFFF": '#F1F4FA';

			if (strlen($msg_erro) == 0) {
				$produto                  = pg_result($res,$i,produto);
				$referencia               = pg_result($res,$i,referencia);
				$descricao                = pg_result($res,$i,descricao);
				$voltagem                 = pg_result($res,$i,voltagem);
				$garantia                 = pg_result($res,$i,garantia);
				$mao_de_obra_posto        = pg_result($res,$i,mao_de_obra_posto);
				$mao_de_obra_posto        = number_format($mao_de_obra_posto, 2, ',', '');
				$mao_de_obra_admin        = pg_result($res,$i,mao_de_obra_admin);
				$mao_de_obra_admin        = number_format($mao_de_obra_admin, 2, ',', '');
				$radical_serie            = pg_result($res,$i,radical_serie);
				$ativo                    = pg_result($res,$i,ativo);
				$numero_serie_obrigatorio = pg_result($res,$i,numero_serie_obrigatorio);
				$familia                  = pg_result($res,$i,familia);
				$familia_descricao        = pg_result($res,$i,familia_descricao);
				$troca_obrigatoria        = pg_result($res,$i,troca_obrigatoria);
				$origem                   = pg_result($res,$i,origem);
			}else{
				$produto                  = $_POST["produto_".$i];
				$referencia               = $_POST["referencia_".$i];
				$descricao                = stripslashes($_POST["descricao_".$i]);
				$voltagem                 = $_POST["voltagem_".$i];
				$garantia                 = $_POST["garantia_".$i];
				$mao_de_obra_posto        = $_POST["mao_de_obra_posto_".$i];
				$mao_de_obra_admin        = $_POST["mao_de_obra_admin_".$i];
				$radical_serie            = $_POST["radical_serie_".$i];
				$ativo                    = $_POST["ativo_".$i];
				$numero_serie_obrigatorio = $_POST["numero_serie_obrigatorio_".$i];
				$familia                  = $_POST["familia_".$i];
				$familia_descricao        = $_POST["familia_descricao_".$i];
				$troca_obrigatoria        = $_POST["troca_obrigatoria_".$i];
				$origem					  = $_POST["origem".$i];
				if ($produto == $produto_erro) $cor ="#FF0000";
			}

			if ($familia <> $familia_anterior OR $i == 0) {
				if ($i <> 0) echo "</table>\n<br>\n";
				echo "<table width='700' border='0' class='conteudo' cellpadding='2' cellspacing='1' align='center'>\n";
				echo "<tr bgcolor='#D9E2EF'>\n";
				echo "<td colspan='10'>$familia_descricao<b></b></td>\n";
				echo "</tr>\n";
				echo "<tr bgcolor='#D9E2EF'>\n";
				echo "<td><b>Referência</b></td>\n";
				echo "<td><b>Descrição</b></td>\n";
				echo "<td><b>Voltagem</b></td>\n";
				echo "<td><b>Garantia</b></td>\n";
				echo "<td><b>M. Obra<br>Posto</b></td>\n";
				echo "<td><b>M. Obra<br>Admin</b></td>\n";
//inserido takashi
				echo "<td><b>Origem</b></td>\n";
//inserido takashi
				echo "<td><b>Radical<br>Nº Série</b></td>\n";
				echo "<td><b>Nº Série<br>Obrigatório</b></td>\n";
				echo "<td><b>Ativo</b></td>\n";
				echo "<td><b>Troca<br>obrigatória</b></td>\n";
				echo "</tr>\n";
			}

			echo "<tr bgcolor='$cor'>\n";
			echo "<td nowrap align='center'>";
			echo "<input type='hidden' name='produto_$i' value='$produto'>\n";
			echo "<input type='hidden' name='referencia_$i' value='$referencia'>\n";
			echo "<input type='hidden' name='descricao_$i' value='$descricao'>\n";
			echo "<input type='hidden' name='voltagem_$i' value='$voltagem'>\n";
			echo "<input type='hidden' name='familia_$i' value='$familia'>\n";
			echo "<input type='hidden' name='familia_descricao_$i' value='$familia_descricao'>\n";
			echo $referencia;
			echo "</td>\n";
			echo "<td nowrap align='left'>$descricao</td>\n";
			echo "<td nowrap align='center'>$voltagem</td>\n";
			echo "<td nowrap align='center'><input type='text' class='frm' name='garantia_$i' value='$garantia' size='5' maxlength='20'> meses</td>\n";
			echo "<td nowrap align='center'>R$ <input type='text' class='frm' name='mao_de_obra_posto_$i' value='$mao_de_obra_posto' size='7' maxlength='20'></td>\n";
			echo "<td nowrap align='center'>R$ <input type='text' class='frm' name='mao_de_obra_admin_$i' value='$mao_de_obra_admin' size='7' maxlength='20'></td>\n";
//inserido takashi
			echo "<td nowrap align='center'>";
			echo "<select name='origem_$i'>";
			echo "<option value=''>ESCOLHA</option>";
			echo "<option value='Nac'"; if ($origem == "Nac") echo " SELECTED "; echo ">Nacional</option>";
			echo "<option value='Imp'"; if ($origem == "Imp") echo " SELECTED "; echo ">Importado</option>";
		echo "</select>";

 //<input type='text' class='frm' name='origem_$i' value='$origem' size='7' maxlength='20'></td>\n";
//inserido takashi			
			echo "<td nowrap align='center'><input type='text' class='frm' name='radical_serie_$i' value='$radical_serie' size='5' maxlength='10'></td>\n";
			echo "<td nowrap align='center'><input type='checkbox' class='frm' name='numero_serie_obrigatorio_$i'"; if ($numero_serie_obrigatorio == 't' ) echo " checked"; echo " value='t'></td>\n";
			echo "<td nowrap align='center'><input type='checkbox' class='frm' name='ativo_$i'"; if ($ativo == 't' ) echo " checked"; echo " value='t'></td>\n";
			echo "<td nowrap align='center'><input type='checkbox' class='frm' name='troca_obrigatoria_$i'"; if ($troca_obrigatoria == 't' ) echo " checked"; echo " value='t'></td>\n";
			echo "</tr>\n";

			$familia_anterior = $familia;

		} # FIM DO FOR

		echo "</table>\n";

		echo "<input type='hidden' name='produto_qtde' value='".pg_numrows($res)."'>";

	}

echo "<br>\n";

echo "<center><img src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_produto.btn_acao.value == '' ) { document.frm_produto.btn_acao.value='GRAVAR'; document.frm_produto.submit(); } else { document.frm_produto.submit(); }\" ALT='Gravar' border='0' style='cursor:pointer;'></center>";

}
?>

</form>

<? include "rodape.php"; ?>