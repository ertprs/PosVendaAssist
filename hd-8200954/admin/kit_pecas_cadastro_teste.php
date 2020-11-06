<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

# Pesquisa pelo AutoComplete AJAX
$q = trim($_GET["q"]);
if (isset($_GET["q"])) {
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];
	if (strlen($q) > 2) {
		if ($tipo_busca == "produto") {
			$sql = "SELECT tbl_produto.produto,
							tbl_produto.referencia,
							tbl_produto.descricao
					FROM tbl_produto
					JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
					WHERE tbl_linha.fabrica = $login_fabrica ";

			if ($busca == "codigo") {
				$sql .= " AND UPPER(tbl_produto.referencia) like UPPER('%$q%') ";
			} else {
				$sql .= " AND UPPER(tbl_produto.descricao) like UPPER('%$q%') ";
			}

			$res = pg_query($con,$sql);

			if (pg_num_rows($res) > 0) {
				for ($i = 0; $i < pg_num_rows($res); $i++) {
					$produto    = trim(pg_fetch_result($res, $i, 'produto'));
					$referencia = trim(pg_fetch_result($res, $i, 'referencia'));
					$descricao  = trim(pg_fetch_result($res, $i, 'descricao'));
					echo "$produto|$descricao|$referencia";
					echo "\n";
				}
			}

		}

	}

	exit;

}

$qtde_linhas = 30 ;

$msg_erro = "";

$btn_acao = trim (strtolower ($_POST['btn_acao']));
$kit_peca = (isset($_GET['kit_peca'])) ? $_GET['kit_peca'] : $_POST['kit_peca'] ;

if (trim($btn_acao) == "gravar") {
	
	if (strlen($kit_peca) == 0) {
		$kit_peca = null;
	}

	$referencia     = trim($_POST['referencia']);
	$descricao_pro  = trim($_POST['descricao_pro']);
	$referencia_kit = trim($_POST['referencia_kit']);
	$descricao_kit  = trim($_POST['descricao_kit']);

	if (strlen($referencia_kit) == 0) {
		$msg_erro = "Por favor, informe a referência de Kit";
	}

	if (strlen($descricao_kit) == 0) {
		$msg_erro = "Por favor, informe a descrição de Kit";
	}

	if (strlen($referencia) == 0) {
		$msg_erro = "Por favor, informe a referência do produto";
	}

	if (strlen($referencia) > 0) {

		$sql = "SELECT tbl_produto.produto, tbl_produto.referencia, tbl_produto.descricao 
			FROM tbl_produto 
			JOIN tbl_linha using(linha)
			WHERE tbl_produto.referencia = '$referencia'
			AND   tbl_linha.fabrica = $login_fabrica";

		$res = pg_query($con,$sql);

		if (pg_num_rows($res) == 0) {

			$msg_erro  .= "Produto $referencia não cadastrado";
			$descricao = "";
			$produto   = "";

		} else {

			$produto   = pg_fetch_result($res, 0, 'produto');

		}

	}

	if (strlen($msg_erro) == 0) {

		$res = pg_query($con,"BEGIN TRANSACTION");

		if (strlen($kit_peca) == 0) {

			$sqlx = " INSERT INTO tbl_kit_peca (
						fabrica,
						produto,
						referencia,
						descricao
					) values (
						$login_fabrica,
						$produto,
						'$referencia_kit',
						'$descricao_kit'
					)";

			$resx = pg_query($con,$sqlx);
			$msg_erro = pg_errormessage($con);

			$sqlx = " SELECT currval('seq_kit_peca') as kit_peca;";
			$resx = pg_query($con,$sqlx);

			$msg_erro = pg_errormessage($con);
			$kit_peca = trim(pg_fetch_result($resx,0,0));

		} else {

			$sql = " SELECT kit_peca
				FROM tbl_kit_peca
				WHERE produto = $produto
				AND   kit_peca = $kit_peca";

			$res = pg_query($con,$sql);

			if (pg_num_rows($res) == 0) {

				$kit_peca = trim(pg_fetch_result($res,0,0));

				$sqlu = " UPDATE tbl_kit_peca set 
						produto = $produto,
						referencia = '$referencia_kit',
						descricao = '$descricao_kit'
						WHERE kit_peca = $kit_peca; ";

				$resu     = pg_query($con, $sqlu);
				$msg_erro = pg_errormessage($con);

			}

		}

		if (strlen($kit_peca) > 0) {
			$sql = " DELETE FROM tbl_kit_peca_peca WHERE kit_peca = $kit_peca";
			$res = pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		for ($i = 0; $i < $qtde_linhas; $i++) {

			$xpeca = trim($_POST['peca_' . $i]);
			$descricao = $_POST['descricao_' . $i];
			
			//HD 197671: Acrescentar o campo quantidade
			if (strlen($_POST["qtde_" . $i]) == 0) {

				$qtde = 1;

			} else {

				$qtde = intval(trim($_POST['qtde_' . $i]));

			}

			if (strlen($msg_erro) == 0) {//HD 258901 - item 17

				$sql = "SELECT *
						  FROM tbl_lista_basica
						 WHERE produto = (SELECT produto FROM tbl_produto WHERE referencia = '$referencia' LIMIT 1);";

				$res = pg_query($con,$sql);

				if (@pg_num_rows($res) == 0) {

					$msg_erro .= "Produto sem Lista Básica, para cadastrar um Kit para este produto,";
					$msg_erro .= "<br />";
					$msg_erro .= "cadastre uma lista básica e adiciona a suas respectivas peças.";

				}


			}

			if (strlen($msg_erro) == 0) {

				if (strlen($xpeca) > 0) {

					$sql = "SELECT peca FROM tbl_peca WHERE referencia = '$xpeca' AND fabrica = $login_fabrica";
					$res = @pg_query($con, $sql);
					$msg_erro = pg_errormessage($con);

					if (@pg_num_rows($res) == 0) {

						$msg_erro .= "Peça $peca não cadastrada";

					} else {

						$peca = @pg_fetch_result($res,0,0);

						$sql = " SELECT peca FROM tbl_lista_basica
									WHERE produto = $produto
									AND peca = $peca
									AND fabrica = $login_fabrica";

						$res = pg_query($con,$sql);

						if (pg_num_rows($res) == 0) {
							$msg_erro = "A peça $xpeca não pertence a lista básica desse produto";
						}

						if (strlen($msg_erro) == 0) {

							//HD 197671: Acrescentar o campo quantidade
							if ($qtde > 0) {

								$sql = " INSERT INTO tbl_kit_peca_peca (
												kit_peca,
												peca,
												qtde
											) values(
												$kit_peca,
												$peca,
												$qtde
											) ";

								$res = pg_query($con,$sql);
								$msg_erro = pg_errormessage($con);

							} else {

								$sql = "DELETE FROM tbl_kit_peca_peca
											WHERE kit_peca = $kit_peca
											AND peca = $peca";

								$res = pg_query($con,$sql);
								$msg_erro = pg_errormessage($con);

							}

						}

					}

				}

			}

		}

		if (strlen($msg_erro) == 0) {

			$sql = " SELECT count(*) FROM tbl_kit_peca join tbl_kit_peca_peca using(kit_peca) WHERE produto = $produto";
			$res = pg_query($con,$sql);

			if (pg_fetch_result($res,0,0) == 0) {
				$msg_erro = "Por favor, informe as peças para este Kit ";
			}

		}

	}

	if (strlen($msg_erro) == 0) {

		$res = pg_query($con,"COMMIT TRANSACTION");
		$msg = "ok";

		header("Location: $PHP_SELF?msg=$msg");

		exit;

	} else {

		$res = pg_query($con,"ROLLBACK TRANSACTION");
		$kit_peca = "";

	}

}

$apagar = $_POST["apagar"];

if (trim($btn_acao) == "apagar" and strlen($apagar) > 0) {

	$res = pg_query($con,"BEGIN TRANSACTION");

	$sql = " DELETE FROM tbl_kit_peca_peca
			WHERE kit_peca = $apagar";

	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);

	$sql = "DELETE FROM tbl_kit_peca
			WHERE  tbl_kit_peca.fabrica      = $login_fabrica
			AND    tbl_kit_peca.kit_peca = $apagar;";

	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);

	if (strlen($msg_erro) == 0) {
		$res = pg_query($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF?");
		exit;
	} else {
		$res = pg_query($con,"ROLLBACK TRANSACTION");
	}

}

$layout_menu = "cadastro";
$title       = "Cadastramento de Kit de Peças";

include 'cabecalho.php';?>

<script language='javascript'>

	function fnc_pesquisa_produto (campo, campo2, tipo) {

		if (tipo == "referencia" ) {
			var xcampo = campo;
		}

		if (tipo == "descricao" ) {
			var xcampo = campo2;
		}

		if (xcampo.value != "") {

			var url = "";

			url    = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&lbm=1"+ "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>" ;
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");

			janela.referencia = campo;
			janela.descricao  = campo2;
			janela.produto    = '';//document.frm_kit.produto;
			janela.focus();

			document.getElementById('div_pecas').style.display = 'block';

		}

	}

	function fnc_pesquisa_peca (campo, campo2, tipo, campo_preco) {

		if (tipo == "referencia" || tipo == "referencia_pai") {
			var xcampo = campo;
		}

		if (tipo == "descricao" || tipo == "descricao_pai") {
			var xcampo = campo2;
		}

		if (xcampo.value != "") {

			var url = "";
			url = "peca_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&prod=" + document.getElementById('referencia').value ;
			janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");

			janela.referencia = campo;
			janela.descricao  = campo2;
			//campo_preco.value = "";
			janela.focus();

		}

	}

</script>

<? include "javascript_calendario_new.php"; //adicionado por Fabio 27-09-2007 ?>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">
	$().ready(function() {

		function formatItem(row) {
			return row[2] + " - " + row[1];
		}

		/* Busca por Produto */
		$("#referencia").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=codigo'; ?>", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[2];}
		});

		$("#referencia").result(function(event, data, formatted) {
			document.getElementById('div_pecas').style.display = 'block';
			$("#descricao").val(data[1]);
		});

		/* Busca pelo Nome */
		$("#descricao").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=nome'; ?>", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[1];}
		});

		$("#descricao").result(function(event, data, formatted) {
			document.getElementById('div_pecas').style.display = 'block';
			$("#referencia").val(data[2]);
		});
	});
</script>

<body>

<div id='wrapper'>

	<form name="frm_kit" method="post" action="<? echo $PHP_SELF ?>">

		<input type='hidden' name='kit_peca' value='<?=$kit_peca?>' /><?php

		if (strlen($msg_erro) > 0) {

			if (strpos($msg_erro,"ERROR: ") !== false) {
				$erro = "Foi detectado o seguinte erro:<br />";
				$msg_erro = substr($msg_erro, 6);
			}

			if (strpos($msg_erro,"CONTEXT:")) {
				$x = explode('CONTEXT:',$msg_erro);
				$msg_erro = $x[0];
			}?>

			<div class='error'><? echo $msg_erro; ?></div><?php

		}

		if (isset($_GET['msg'])) {
			echo "<br /><div style='font-size:15px; background-color:#0000CC; color:#FFFFFF'>Cadastrado com Sucesso</div>";
		}

		if (isset($_GET['kit_peca'])) {

			$kit_peca = $_GET['kit_peca'];

			$sql = " SELECT tbl_produto.referencia,
						tbl_produto.descricao,
						tbl_kit_peca.kit_peca,
						tbl_kit_peca.referencia as referencia_kit,
						tbl_kit_peca.descricao as descricao_kit
				FROM    tbl_produto
				JOIN    tbl_kit_peca USING(produto)
				WHERE   fabrica = $login_fabrica
				AND     kit_peca= $kit_peca ";

			$res = pg_query($con,$sql);

			if (pg_num_rows($res) > 0) {
				$referencia     = pg_fetch_result($res, 0, 'referencia');
				$descricao_pro  = pg_fetch_result($res, 0, 'descricao');
				$referencia_kit = pg_fetch_result($res, 0, 'referencia_kit');
				$descricao_kit  = pg_fetch_result($res, 0, 'descricao_kit');
			}

		}?>

		<br />
		<br />

		<table width='600' align='center' border='0'>
			<tr style="background-color:#0000CC; color:#FFFFFF">
				<td align='center'>
					<b>Produto</b>
				</td>
				<td align='center'>
					<b>Descrição</b>
				</td>
			</tr>
			<tr>
				<td align='center'>
					<input type="text" name="referencia" id="referencia" value="<? echo $referencia ?>" size="15" maxlength="20" >&nbsp;<img src='../imagens/btn_buscar5.gif' style="cursor: hand;" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_kit.referencia,document.frm_kit.descricao,'referencia')" />
				</td>
				<td align='center'>
					<input type="text" name="descricao_pro" id="descricao" value="<? echo $descricao_pro; ?>" size="50" maxlength="50" >&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' style="cursor:pointer" align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_kit.referencia,document.frm_kit.descricao_pro,'descricao')" />
				</td>
			</tr>
			<tr>
				<td align='center' colspan='100%'>
					&nbsp;
				</td>
			</tr>
			<tr bgcolor='#d9e2ef'>
				<td align='center'>
					<b>Referência Kit</b>
				</td>
				<td align='center'>
					<b>Descrição Kit</b>
				</td>
			</tr>
			<tr>
				<td align='center'>
					<input type="text" name="referencia_kit" size="15" maxlength="20" value='<?=$referencia_kit?>' />
				</td>
				<td align='center'>
					<input type="text" name="descricao_kit" size="50" maxlength="50" value='<?=$descricao_kit?>' />
				</td>
			</tr>
		</table>

		<br /><?php

		//HD 258901 - item 19
		echo '<div id="div_pecas" style="display:' . (isset($_GET['msg']) || strlen($msg_erro) > 0 || isset($_GET['kit_peca']) ? 'block' : 'none') . '">';

			//HD 197671: Acrescentar o campo quantidade no KIT
			echo "<table width='400' align='center' border='0'>";
				echo "<tr>";
					echo "<td colspan=3 align='left'><u>Quantidade:</u><br />- Se não for preenchido o sistema assumirá o valor um (1)<br />- Para apagar um item, preencha a quantidade com o valor ZERO (0)</td>";
				echo "</tr>";
				echo "<tr bgcolor='#cccccc' style='font-weight:bold'>";
					echo "<td align='center'>Quantidade</td>";
					echo "<td align='center'>Peça</td>";
					echo "<td align='center'>Descrição</td>";
				echo "</tr>";

				if (strlen($kit_peca) > 0) {

					$sql = " SELECT tbl_peca.referencia,
								tbl_peca.descricao,
								tbl_kit_peca_peca.qtde
								FROM    tbl_kit_peca_peca
								JOIN    tbl_peca USING(peca)
								WHERE   fabrica = $login_fabrica
								AND     kit_peca= $kit_peca ";

					$res = pg_query($con,$sql);

					$qtde_linhas = (pg_num_rows($res) + 20 > 30) ? 30 : pg_num_rows($res) + 20;

				}

				for ($i = 0; $i < $qtde_linhas; $i++) {

					$qtde = "qtde_$i" ;
					$qtde = $$qtde;

					$peca = "peca_$i" ;
					$peca = $$peca;

					$descricao = "descricao_$i" ;
					$descricao = $$descricao;

					if (strlen($kit_peca) > 0 and $i < pg_numrows($res)) {
						$qtde      = pg_fetch_result($res, $i, 'qtde');
						$peca      = pg_fetch_result($res, $i, 'referencia');
						$descricao = pg_fetch_result($res, $i, 'descricao');
					}

					echo "<tr>";
						//HD 197671: Acrescentar campo quantidade no kit de peças
						echo "<td nowrap valign='top'>";
							echo "<input type='text' id='qtde_$i' name='qtde_$i' value='$qtde' size='10' maxlength='20' onkeyup=\"re = /\D/g; this.value = this.value.replace(re, '');\">";
						echo "</td>";

						echo "<td nowrap>";
							echo "<input type='text' name='peca_$i' value='$peca' size='20' maxlength='20'>";
							echo "&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle'";
							 echo " onclick='javascript: fnc_pesquisa_peca (document.frm_kit.peca_$i , document.frm_kit.descricao_$i , \"referencia\")' style='cursor:pointer'><br />";
							echo "<font size='-3' color='#ffffff'>$peca</font>";
						echo "</td>";
						echo "<td bgcolor='$cor' nowrap>";
							echo "<input type='text' name='descricao_$i' value='$descricao' size='50' maxlength='50'>";
							echo "&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle'";
							 echo " onclick='javascript: fnc_pesquisa_peca (document.frm_kit.peca_$i , document.frm_kit.descricao_$i , \"descricao\")' style='cursor:pointer'><br />";
							echo "<font size='-3' color='#ffffff'>$descricao</font>";
						echo "</td>";
					echo "</tr>";

				}?>

			</table>

			<p align='center'>
				<input type='hidden' name="btn_acao" />
				<input type='hidden' name="apagar" value="<?=$kit_peca?>" />
				<img src='imagens_admin/btn_gravar.gif' onclick='document.frm_kit.btn_acao.value = "gravar" ; document.frm_kit.submit()' style='cursor:pointer;' /><?php
				if (isset($_GET['kit_peca'])) {?>
					<img src='imagens_admin/btn_apagar.gif' onclick='document.frm_kit.btn_acao.value = "apagar" ; document.frm_kit.submit()' style='cursor:pointer;' /><?php
				}?>
			</p>

			<br />

		</div>

	</form><?php

	$sql = " SELECT tbl_produto.referencia,
					tbl_produto.descricao,
					tbl_kit_peca.kit_peca,
					tbl_kit_peca.referencia as referencia_kit,
					tbl_kit_peca.descricao as descricao_kit
			FROM    tbl_produto
			JOIN    tbl_kit_peca USING(produto)
			WHERE   fabrica = $login_fabrica
			ORDER BY kit_peca";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
			flush();
			echo "<br />\n";
			echo "<table width='700' align='center' border='1' class='conteudo' cellpadding='2' cellspacing='0'>";
			echo "<thead>";
				echo "<tr style='background-color:#0000CC;color:#FFFFFF'>";
					echo "<td align='center' width='200'>";
						echo "<b>Referência Produto</b>";
					echo "</td>";
					echo "<td align='center'>";
						echo "<b>Descrição</b>";
					echo "</td>";
					echo "<td align='center' width='200'>";
						echo "<b>Referência Kit</b>";
					echo "</td>";
					echo "<td align='center'>";
						echo "<b>Descrição</b>";
					echo "</td>";
				echo "</tr>";
			echo "</thead>";
			echo "<tbody>";

			$resultados = pg_fetch_all($res);
			$conta      = 1;

			foreach ($resultados as $resultado) {

				$cor = ($conta%2) ? "#FFFFFF" : "#CCFFFF";
				$conta++;

				echo "<tr style='background-color: $cor'>";
					echo "<td align='center' nowrap>" . $resultado['referencia'] . "&nbsp;</td>";
					echo "<td align='center' nowrap><a href='$PHP_SELF?kit_peca=" . $resultado['kit_peca'] . "'>".$resultado['descricao']."</a>"."&nbsp;</td>";
					echo "<td align='center' nowrap>" . $resultado['referencia_kit'] . "&nbsp;</td>";
					echo "<td align='center' nowrap>" . $resultado['descricao_kit'] . "</a>"."&nbsp;</td>";
				echo "</tr>";

			}

			echo "</tbody>";

		echo "</table>";

	}?>

</div>

<? include "rodape.php"; ?>

</body>
</html>