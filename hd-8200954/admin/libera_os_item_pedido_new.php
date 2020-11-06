<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

if ($login_fabrica <> 6) {
	header ("Location: menu_callcenter.php");
	exit;
}

if (strlen($_POST["btn_acao"]) > 0) $btn_acao = $_POST["btn_acao"];
if (strlen($_GET["btn_acao"]) > 0)  $btn_acao = $_GET["btn_acao"];

if (strtoupper($btn_acao) == "GRAVAR") {
	$qtde_os_item = $_POST["qtde_os_item"];
	$posto        = $_POST["posto"];
	
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	for ($i = 0 ; $i < $qtde_os_item; $i++) {
		$os_item = trim($_POST["os_item_".$i]);
		$recusar = trim($_POST["recusar_".$i]);
		$estoque = trim($_POST["estoque_".$i]);
		$postox  = trim($_POST["posto_".  $i]);
		
		if (strlen($os_item) > 0 and $postox == $posto) {
			$sql = "SELECT tbl_os.os
					FROM   tbl_os
					JOIN   tbl_os_produto USING (os)
					JOIN   tbl_os_item    USING (os_produto)
					WHERE  tbl_os.fabrica      = $login_fabrica
					AND    tbl_os.posto        = $posto
					AND    tbl_os_item.os_item = $os_item;";
			$res = pg_exec ($con,$sql);

			if (pg_numrows($res) > 0) {
				$os = pg_result($res,0,0);

				$sql = "SELECT fn_libera_os_item ($login_fabrica, $os, $os_item, $login_fabrica, 't')";
				$res = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
			if (strlen($msg_erro) > 0) break;
		}
		
		if (strlen($recusar) > 0) {
			$sql = "SELECT tbl_os.os
					FROM   tbl_os
					JOIN   tbl_os_produto USING (os)
					JOIN   tbl_os_item    USING (os_produto)
					WHERE  tbl_os.fabrica      = $login_fabrica
					AND    tbl_os.posto        = $posto
					AND    tbl_os_item.os_item = $recusar;";
			$res = pg_exec($con,$sql);

			if (pg_numrows($res) > 0) {
				$os = pg_result($res,0,0);

				$sql = "SELECT fn_libera_os_item ($login_fabrica, $os, $recusar, $login_fabrica, 'f')";
				$res = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

			if (strlen($msg_erro) > 0) break;
		}
		
		if (strlen($estoque) > 0) {
			$sql = "SELECT tbl_os.os
					FROM   tbl_os
					JOIN   tbl_os_produto USING (os)
					JOIN   tbl_os_item    USING (os_produto)
					WHERE  tbl_os.fabrica      = $login_fabrica
					AND    tbl_os.posto        = $posto
					AND    tbl_os_item.os_item = $estoque;";
			$res = pg_exec ($con,$sql);

			if (pg_numrows($res) > 0) {
				$os = pg_result ($res,0,0);

				$sql = "SELECT fn_altera_servico_realizado_os_item ($login_fabrica, $os, $estoque, $login_admin)";
				$res = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

			if (strlen($msg_erro) > 0) break;
		}
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

$layout_menu = "cadastro";
$title = "Manutenção de Itens de OS para Pedidos";
include 'cabecalho.php';
?>

<style type='text/css'>
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>

<br>

<? if (strlen($msg_erro) > 0) { ?>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
	<tr>
		<td valign="middle" align="center" class='error'><?echo $msg_erro?></td>
	</tr>
</table>
<br>
<? } ?>

<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
	<tr class="Conteudo">
		<td bgcolor="#FFCCCC">&nbsp;&nbsp;&nbsp;</td>
		<td width='100%' valign="middle" align="left">&nbsp;Diferença entre data de fechamento e data de finalização maior que 30 dias</td>
	</tr>
	<tr>
		<td colspan="2" height="3"></td>
	</tr>
	<tr class="Conteudo">
		<td bgcolor="#D7FFE1">&nbsp;&nbsp;&nbsp;</td>
		<td width='100%' valign="middle" align="left">&nbsp;Itens de Aparência</td>
	</tr>
	<tr>
		<td colspan="2" height="3"></td>
	</tr>
	<tr class="Conteudo">
		<td bgcolor="#91C8FF">&nbsp;&nbsp;&nbsp;</td>
		<td width='100%' valign="middle" align="left">&nbsp;OS digitada pelo Admin</td>
	</tr>
</table>

<br>

<?
$sql =	"SELECT tbl_posto_fabrica.posto                            AS posto                ,
				tbl_posto_fabrica.codigo_posto                     AS posto_codigo         ,
				tbl_posto_fabrica.item_aparencia                   AS posto_item_aparencia ,
				tbl_posto.nome                                     AS posto_nome           ,
				tbl_os.os                                                                  ,
				tbl_os.sua_os                                                              ,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY')         AS abertura             ,
				to_char(tbl_os.data_fechamento,'DD/MM/YYYY')       AS fechamento           ,
				(tbl_os.finalizada::date - tbl_os.data_fechamento) AS diferenca            ,
				tbl_peca.referencia                                AS peca_referencia      ,
				tbl_peca.descricao                                 AS peca_descricao       ,
				tbl_peca.item_aparencia                            AS peca_item_aparencia  ,
				tbl_os_item.os_item                                                        ,
				to_char(tbl_os_item.digitacao_item,'DD/MM/YYYY')   AS inserida             ,
				tbl_os_item.qtde                                                           ,
				tbl_admin.login                                                            
		FROM tbl_os_item
		JOIN      tbl_os_produto    ON  tbl_os_produto.os_produto = tbl_os_item.os_produto
		JOIN      tbl_os            ON  tbl_os.os                 = tbl_os_produto.os
		JOIN      tbl_peca          ON  tbl_peca.peca             = tbl_os_item.peca
									AND tbl_peca.fabrica          = $login_fabrica
		JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_os.posto
									AND tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_posto_fabrica.posto
		LEFT JOIN tbl_admin         ON  tbl_admin.admin           = tbl_os_item.admin
									AND tbl_admin.admin           = tbl_os.admin
		WHERE tbl_os_item.servico_realizado           IN (1,53)
		AND   tbl_os_item.pedido                      IS NULL
		AND   tbl_os_item.liberacao_pedido_analisado  IS FALSE
		AND   tbl_os.validada                         IS NOT NULL
		AND   tbl_os.fabrica                 = $login_fabrica
		AND   tbl_posto_fabrica.codigo_posto <> 'teste'
		ORDER BY    tbl_posto.nome                                               ASC,
					tbl_os.data_digitacao                                        ASC,
					lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,0) ASC,
					lpad(tbl_os.os,20,0)                                         ASC;";
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	echo "<form name='frm_os_item' method='post' action='$PHP_SELF'>\n";
	echo "<input type='hidden' name='btn_acao'>\n";
	echo "<input type='hidden' name='posto'>\n";
	
	echo "<table border='0' cellpadding='2' cellspacing='1'>\n";
	
	$qtde_liberar_inicio = 0;
	$qtde_liberar_final  = 0;
	$cont = 0;

	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$posto                = trim(pg_result($res,$i,posto));
		$posto_codigo         = trim(pg_result($res,$i,posto_codigo));
		$posto_item_aparencia = trim(pg_result($res,$i,posto_item_aparencia));
		$posto_nome           = trim(pg_result($res,$i,posto_nome));
		$os                   = trim(pg_result($res,$i,os));
		$sua_os               = trim(pg_result($res,$i,sua_os));
		$abertura             = trim(pg_result($res,$i,abertura));
		$fechamento           = trim(pg_result($res,$i,fechamento));
		$os_item              = trim(pg_result($res,$i,os_item));
		$diferenca            = trim(pg_result($res,$i,diferenca));
		$peca_referencia      = trim(pg_result($res,$i,peca_referencia));
		$peca_descricao       = trim(pg_result($res,$i,peca_descricao));
		$peca_item_aparencia  = trim(pg_result($res,$i,peca_item_aparencia));
		$inserida             = trim(pg_result($res,$i,inserida));
		$qtde                 = trim(pg_result($res,$i,qtde));
		$login                = trim(pg_result($res,$i,login));
		$checked              = "";
		
		if ($posto_codigo_anterior != $posto_codigo) {
			if ($i != 0) {
				echo "<tr class='Conteudo'>\n";
				echo "<td>$cont\n";
				echo "<script language=\"JavaScript\">\n";
				echo "var CheckFlagLiberar$cont = \"false\";\n";
				echo "function SelecionarLiberar$cont (campo, campo_inicio, campo_final) {\n";
				echo "if (CheckFlagLiberar$cont == \"false\") {\n";
				echo "alert(campo_inicio + ' - ' + campo_final);\n";
				echo "var i = campo_inicio;";
				echo "while (i < campo_final) {\n";
#				echo "alert(campo_inicio + ' - ' + campo_final);\n";
				echo "alert(i);\n";
				echo "if (document.frm_os_item.elements[campo + \"_\" + i].disabled == false) {";
				echo "document.frm_os_item.elements[campo + \"_\" + i].checked = true;\n";
#				echo "alert(document.frm_os_item.elements[campo + \"_\" + i].checked);\n";
				echo "}\n";
				echo " i++;\n";
				echo "}\n";
				echo "CheckFlagLiberar$cont = \"true\";\n";
				echo "return true;\n";
				echo "}else{\n";
				echo "for (i = campo_inicio; i < campo_final; i++) {\n";
				echo "alert('teste');\n";
				echo "if (document.frm_os_item.elements[campo + \"_\" + i].disabled == false) {";
				echo "document.frm_os_item.elements[campo + \"_\" + i].checked = false;\n";
				echo "alert(document.frm_os_item.elements[campo + \"_\" + i].checked);\n";
				echo "}\n";
				echo "}\n";
				echo "CheckFlagLiberar$cont = \"false\";\n";
				echo "return true;\n";
				echo "}\n";
				echo "}\n";
				echo "</script>\n";
				echo "<input type='checkbox' name='liberar' value='liberar' onclick=\"SelecionarLiberar$cont ('os_item', '$qtde_liberar_inicio', '$qtde_liberar_final');\" class='frm'>";
				echo "</td>\n";
				echo "<td>\n";
				echo "<script language=\"JavaScript\">\n";
				echo "var CheckFlagRecusar$cont = \"false\";\n";
				echo "function SelecionarRecusar$cont (campo, campo_inicio, campo_final) {\n";
				echo "if (CheckFlagRecusar$cont == \"false\") {\n";
				echo "for (i = campo_inicio; i < campo_final; i++) {\n";
				echo "if (document.frm_os_item.elements[campo + \"_\" + i].disabled == false) {";
				echo "document.frm_os_item.elements[campo + \"_\" + i].checked = true;\n";
				echo "}\n";
				echo "}\n";
				echo "CheckFlagRecusar$cont = \"true\";\n";
				echo "return true;\n";
				echo "}else{\n";
				echo "for (i = campo_inicio; i < campo_final; i++) {\n";
				echo "if (document.frm_os_item.elements[campo + \"_\" + i].disabled == false) {";
				echo "document.frm_os_item.elements[campo + \"_\" + i].checked = false;\n";
				echo "}\n";
				echo "}\n";
				echo "CheckFlagRecusar$cont = \"false\";\n";
				echo "return true;\n";
				echo "}\n";
				echo "}\n";
				echo "</script>\n";
				echo "<input type='checkbox' name='recusar' value='recusar' onclick=\"SelecionarRecusar$cont ('recusar', '$qtde_liberar_inicio', '$qtde_liberar_final');\" class='frm'>\n";
				echo "</td>\n";
				echo "<td>\n";
				echo "<script language=\"JavaScript\">\n";
				echo "var CheckFlagEstoque$cont = \"false\";\n";
				echo "function SelecionarEstoque$cont (campo, campo_inicio, campo_final) {\n";
				echo "if (CheckFlagEstoque$cont == \"false\") {\n";
				echo "for (i = campo_inicio; i < campo_final; i++) {\n";
				echo "if (document.frm_os_item.elements[campo + \"_\" + i].disabled == false) {";
				echo "document.frm_os_item.elements[campo + \"_\" + i].checked = true;\n";
				echo "}\n";
				echo "}\n";
				echo "CheckFlagEstoque$cont = \"true\";\n";
				echo "return true;\n";
				echo "}else{\n";
				echo "for (i = campo_inicio; i < campo_final; i++) {\n";
				echo "if (document.frm_os_item.elements[campo + \"_\" + i].disabled == false) {";
				echo "document.frm_os_item.elements[campo + \"_\" + i].checked = false;\n";
				echo "}\n";
				echo "}\n";
				echo "CheckFlagEstoque$cont = \"false\";\n";
				echo "return true;\n";
				echo "}\n";
				echo "}\n";
				echo "</script>\n";
				echo "<input type='checkbox' name='estoque' value='estoque' onclick=\"SelecionarEstoque$cont ('estoque', '$qtde_liberar_inicio', '$qtde_liberar_final');\" class='frm'>\n";
				echo "</td>\n";
				echo "<td colspan='6' align='left'>Clique no campo ao lado para selecionar todos</td>\n";
				echo "</tr>\n";
				
				$qtde_liberar_inicio = $qtde_liberar_final;
				
				echo "<tr class='Conteudo'>\n";
				echo "<td colspan='9'>\n";
				
				echo "<img border='0' src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_os_item.btn_acao.value == '') { document.frm_os_item.btn_acao.value='GRAVAR'; document.frm_os_item.posto.value = '$posto_anterior'; document.frm_os_item.submit(); } else { alert('Aguarde submissão'); }\" ALT='Gravar' style='cursor: hand;'>";
				echo "<br>";
				echo "<br>";
				echo "</td>\n";
				echo "</tr>\n";
				
				$cont++;
			}
			echo "<tr class='Titulo'>\n";
			echo "<td colspan='9'><b>Posto: $posto_codigo - $posto_nome</b></td>\n";
			echo "</tr>\n";
			
			echo "<tr class='Titulo'>\n";
			echo "<td>Liberar</td>\n";
			echo "<td>Recusar</td>\n";
			echo "<td>Estoque</td>\n";
			echo "<td>OS</td>\n";
			echo "<td>Abertura</td>\n";
			echo "<td>Fechamento</td>\n";
			echo "<td>Peça</td>\n";
			echo "<td>Inserida</td>\n";
			echo "<td>Qtde</td>\n";
			echo "</tr>\n";
		}
		
		$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F1F4FA";
		
		if ($diferenca > 30) $cor = "#FFCCCC";
		
		if ($peca_item_aparencia == "t" && $posto_item_aparencia == "t") $cor = "#D7FFE1";
		
		if (strlen($login) > 0) $cor = "#91C8FF";
		
		if ($os_item == $_POST["os_item_".$i]) $checked = "checked";
		
		echo "<tr class='Conteudo' bgcolor='$cor' height='16'>\n";
		
		echo "<td>$i";
		echo "<input type='hidden' name='posto_$i' value='$posto'>\n";
		echo "<input type='checkbox' name='os_item_$i' value='$os_item' class='frm'";
		if ($os_item == $_POST["os_item_".$i]) echo " checked";
		if ($peca_item_aparencia == "t" && $posto_item_aparencia == "t") {
			echo " checked";
			if (strlen($login) == 0) echo " disabled";
			echo ">";
			echo "<input type='hidden' name='os_item_$i' value='$os_item'>";
		}else{
			echo ">";
		}
		echo "</td>\n";
		echo "<td><input type='checkbox' $checked name='recusar_$i' value='$os_item' class='frm'></td>\n";
		echo "<td><input type='checkbox' $checked name='estoque_$i' value='$os_item' class='frm'></td>\n";
		echo "<td nowrap><a href='os_item.php?os=$os' target='_blank'>$sua_os</a></td>\n";
		echo "<td nowrap align='left'>";
		if (strlen($login) > 0) echo "Digita por " . strtoupper($login) . "<br>em ";
		echo $abertura;
		echo "</td>\n";
		echo "<td nowrap align='left'>$fechamento</td>\n";
		echo "<td nowrap align='left'>" . $peca_referencia . " - " . $peca_descricao . "</td>\n";
		echo "<td nowrap align='left'>" . $inserida . "</td>\n";
		echo "<td nowrap align='right'>" . $qtde . "</td>\n";
		echo "</tr>\n";
		
		$qtde_liberar_final++;
		$posto_codigo_anterior = $posto_codigo;
		$posto_anterior = $posto;
	}
	echo "<tr class='Conteudo'>\n";
	echo "<td>";
	echo "<script language=\"JavaScript\">\n";
	echo "var CheckFlagLiberar$cont = \"false\";\n";
	echo "function SelecionarLiberar$cont (campo, campo_inicio, campo_final) {\n";
	echo "if (CheckFlagLiberar$cont == \"false\") {\n";
	echo "for (i = campo_inicio; i < campo_final; i++) {\n";
	echo "if (document.frm_os_item.elements[campo + \"_\" + i].disabled == false) {";
	echo "document.frm_os_item.elements[campo + \"_\" + i].checked = true;\n";
	echo "}\n";
	echo "}\n";
	echo "CheckFlagLiberar$cont = \"true\";\n";
	echo "return true;\n";
	echo "}else{\n";
	echo "for (i = campo_inicio; i < campo_final; i++) {\n";
	echo "if (document.frm_os_item.elements[campo + \"_\" + i].disabled == false) {";
	echo "document.frm_os_item.elements[campo + \"_\" + i].checked = false;\n";
	echo "}\n";
	echo "}\n";
	echo "CheckFlagLiberar$cont = \"false\";\n";
	echo "return true;\n";
	echo "}\n";
	echo "}\n";
	echo "</script>\n";
	echo "<input type='checkbox' name='liberar' value='liberar' onclick=\"SelecionarLiberar$cont ('os_item', '$qtde_liberar_inicio', '$qtde_liberar_final');\" class='frm'>";
	echo "</td>\n";
	echo "<td>";
	echo "<script language=\"JavaScript\">\n";
	echo "var CheckFlagRecusar$cont = \"false\";\n";
	echo "function SelecionarRecusar$cont (campo, campo_inicio, campo_final) {\n";
	echo "if (CheckFlagRecusar$cont == \"false\") {\n";
	echo "for (i = campo_inicio; i < campo_final; i++) {\n";
	echo "if (document.frm_os_item.elements[campo + \"_\" + i].disabled == false) {";
	echo "document.frm_os_item.elements[campo + \"_\" + i].checked = true;\n";
	echo "}\n";
	echo "}\n";
	echo "CheckFlagRecusar$cont = \"true\";\n";
	echo "return true;\n";
	echo "}else{\n";
	echo "for (i = campo_inicio; i < campo_final; i++) {\n";
	echo "if (document.frm_os_item.elements[campo + \"_\" + i].disabled == false) {";
	echo "document.frm_os_item.elements[campo + \"_\" + i].checked = false;\n";
	echo "}\n";
	echo "}\n";
	echo "CheckFlagRecusar$cont = \"false\";\n";
	echo "return true;\n";
	echo "}\n";
	echo "}\n";
	echo "</script>\n";
	echo "<input type='checkbox' name='recusar' value='recusar' onclick=\"SelecionarRecusar$cont ('recusar', '$qtde_liberar_inicio', '$qtde_liberar_final');\" class='frm'></td>\n";
	echo "<td>";
	echo "<script language=\"JavaScript\">\n";
	echo "var CheckFlagEstoque$cont = \"false\";\n";
	echo "function SelecionarEstoque$cont (campo, campo_inicio, campo_final) {\n";
	echo "if (CheckFlagEstoque$cont == \"false\") {\n";
	echo "for (i = campo_inicio; i < campo_final; i++) {\n";
	echo "if (document.frm_os_item.elements[campo + \"_\" + i].disabled == false) {";
	echo "document.frm_os_item.elements[campo + \"_\" + i].checked = true;\n";
	echo "}\n";
	echo "}\n";
	echo "CheckFlagEstoque$cont = \"true\";\n";
	echo "return true;\n";
	echo "}else{\n";
	echo "for (i = campo_inicio; i < campo_final; i++) {\n";
	echo "if (document.frm_os_item.elements[campo + \"_\" + i].disabled == false) {";
	echo "document.frm_os_item.elements[campo + \"_\" + i].checked = false;\n";
	echo "}\n";
	echo "}\n";
	echo "CheckFlagEstoque$cont = \"false\";\n";
	echo "return true;\n";
	echo "}\n";
	echo "}\n";
	echo "</script>\n";
	echo "<input type='checkbox' name='estoque' value='estoque' onclick=\"SelecionarEstoque$cont ('estoque', '$qtde_liberar_inicio', '$qtde_liberar_final');\" class='frm'>";
	echo "</td>\n";
	echo "<td colspan='6' align='left'>Clique no campo ao lado para selecionar todos</td>\n";
	echo "</tr>\n";
	echo "<tr class='Conteudo'>\n";
	echo "<td colspan='9'>";
	echo "<input type='hidden' name='qtde_os_item' value='" . pg_numrows($res) . "'>";
	//echo "<img border='0' src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_os_item.btn_acao.value == '') { document.frm_os_item.btn_acao.value='GRAVAR'; document.frm_os_item.submit() } else { alert ('Aguarde submissão') }\" ALT='Gravar' style='cursor: hand;'>";
	echo "<img border='0' src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_os_item.btn_acao.value == '') { document.frm_os_item.btn_acao.value='GRAVAR'; document.frm_os_item.posto.value = '$posto'; document.frm_os_item.submit(); } else { alert('Aguarde submissão'); }\" ALT='Gravar' style='cursor: hand;'>";
	echo "<br>";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "<br>\n";
}
?>

</form>

<? include "rodape.php"; ?>
