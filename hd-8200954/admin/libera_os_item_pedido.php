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

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

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

				$sql = "SELECT fn_libera_os_item ($login_fabrica, $os, $os_item, $login_admin, 't')";
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

				$sql = "SELECT fn_libera_os_item ($login_fabrica, $os, $recusar, $login_admin, 'f')";
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
		header ("Location: $PHP_SELF?mes=$mes&ano=$ano");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}




/*Critério de Datas*/
$mes = trim (strtoupper ($_POST['mes']));
$ano = trim (strtoupper ($_POST['ano']));
if(isset($_GET['mes']))$mes = trim (strtoupper ($_GET['mes']));
if(isset($_GET['mes']))$ano = trim (strtoupper ($_GET['ano']));

if (strlen($mes) > 0) {
	$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
	$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
}else{
	if($_POST['acao'])
		$msg_erro = "É obrigatória selecionar o mês e o ano";
}




$layout_menu = "gerencia";
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
<script language="JavaScript">

function SelecionarLiberar (campo,entrada, campo_inicio, campo_final) {
	if (entrada.checked)	var resultado = true;
	else					var resultado = false;
	var i=0;
	for (i = campo_inicio; i < campo_final; i++) {
		if (!document.getElementById(campo+"_"+i).disabled){
			document.getElementById(campo+"_"+i).checked = resultado;
		}
	}
}

</script>

<br>

<form name="frm_relatorio" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="acao">
<table width="400" border="0" cellspacing="0" cellpadding="2" align="center">
	<tr class="Titulo">
		<td colspan="4">PESQUISA</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr>
		<td class="Conteudo" bgcolor="#D9E2EF" style="width: 10px">&nbsp;</td>
		<td class="Conteudo" bgcolor="#D9E2EF" colspan='2' style="font-size: 10px"><center>Este relatório considera o mês inteiro de OS <br> pela data da digitação.</center></td>
		<td class="Conteudo" bgcolor="#D9E2EF" style="width: 10px">&nbsp;</td>
	</tr>



	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td>Mês</td>
		<td align='left'>
			<select name="mes" size="1" class="frm">
			<option value=''></option>
			<?
			for ($i = 1 ; $i <= count($meses) ; $i++) {
				echo "<option value='$i'";
				if ($mes == $i) echo " selected";
				echo ">" . $meses[$i] . "</option>";
			}
			?>
			</select>
		</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td>Ano</td>
		<td align='left'>
			<select name="ano" size="1" class="frm">
			<option value=''></option>
			<?
			for ($i = 2003 ; $i <= date("Y") ; $i++) {
				echo "<option value='$i'";
				if ($ano == $i) echo " selected";
				echo ">$i</option>";
			}
			?>
			</select>
		</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4"><img src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor:pointer " alt="Clique AQUI para pesquisar"></td>
	</tr>
</table>
</form>

<? if (strlen($msg_erro) > 0) { ?>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
	<tr>
		<td valign="middle" align="center" class='error'><?echo $msg_erro?></td>
	</tr>
</table>
<br>
<? } ?>

<?


if(strlen($mes)>0 and strlen($msg_erro)==0){
	$sql =	"
		SELECT 
			tbl_posto_fabrica.posto                            AS posto                ,
			tbl_posto_fabrica.codigo_posto                     AS posto_codigo         ,
			tbl_posto_fabrica.item_aparencia                   AS posto_item_aparencia ,
			tbl_posto.nome                                     AS posto_nome           ,
			tbl_os.os                                                                  ,
			tbl_os.sua_os                                                              ,
			tbl_os.serie                                                               ,
			to_char(tbl_os.data_abertura,'DD/MM/YY')           AS abertura             ,
			to_char(tbl_os.data_fechamento,'DD/MM/YY')         AS fechamento           ,
			(tbl_os.finalizada::date - tbl_os.data_fechamento) AS diferenca            ,
			tbl_peca.referencia                                AS peca_referencia      ,
			tbl_peca.descricao                                 AS peca_descricao       ,
			tbl_peca.item_aparencia                            AS peca_item_aparencia  ,
			tbl_os_item.os_item                                                        ,
			to_char(tbl_os_item.digitacao_item,'DD/MM/YY')     AS inserida             ,
			tbl_os_item.qtde                                                           ,
			tbl_admin.login
		FROM      tbl_os_item
		JOIN      tbl_os_produto    ON  tbl_os_produto.os_produto = tbl_os_item.os_produto
		JOIN      tbl_os            ON  tbl_os.os                 = tbl_os_produto.os
		JOIN      tbl_servico_realizado USING(servico_realizado,fabrica)
		JOIN      tbl_peca          ON  tbl_peca.peca             = tbl_os_item.peca        AND tbl_peca.fabrica          = $login_fabrica
		JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_os.posto            AND tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_posto_fabrica.posto
		LEFT JOIN tbl_admin         ON  tbl_admin.admin           = tbl_os_item.admin       AND tbl_admin.admin           = tbl_os.admin

		WHERE tbl_servico_realizado.gera_pedido         
		AND   tbl_os_item.pedido                     IS NULL
		AND   tbl_os_item.liberacao_pedido           IS FALSE
		AND   tbl_os_item.liberacao_pedido_analisado IS FALSE
		AND   tbl_os.validada                        IS NOT NULL
		AND   tbl_os.fabrica   = $login_fabrica
		AND   tbl_os.excluida IS NOT TRUE
		AND   tbl_os_item.digitacao_item BETWEEN '$data_inicial' AND '$data_final'
		AND   tbl_posto.posto <> 6359
		ORDER BY    tbl_posto.nome                                               ASC,
					tbl_os.data_digitacao                                        ASC,
					lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,'0') ASC,
					lpad(tbl_os.os::text,20,'0')                                         ASC
			;";
	/*if($login_admin=="852"){
	echo nl2br($sql); exit;
	}*/
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {

		echo "<table width='700' border='0' cellpadding='0' cellspacing='0' align='center'>";
		echo "<tr class='Conteudo'>";
		echo "<td bgcolor='#FFCCCC'>&nbsp;&nbsp;&nbsp;</td>";
		echo "<td width='100%' valign='middle' align='left'>&nbsp;Diferença entre data de fechamento e data de finalização maior que 30 dias</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td colspan='2' height='3'></td>";
		echo "</tr>";
		echo "<tr class='Conteudo'>";
		echo "<td bgcolor='#D7FFE1'>&nbsp;&nbsp;&nbsp;</td>";
		echo "<td width='100%' valign='middle' align='left'>&nbsp;Itens de Aparência</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td colspan='2' height='3'></td>";
		echo "</tr>";
		echo "<tr class='Conteudo'>";
		echo "<td bgcolor='#91C8FF'>&nbsp;&nbsp;&nbsp;</td>";
		echo "<td width='100%' valign='middle' align='left'>&nbsp;OS digitada pelo Admin</td>";
		echo "</tr>";
		echo "</table>";

		echo "<form name='frm_os_item' method='post' action='$PHP_SELF'>\n";
		echo "<input type='hidden' name='btn_acao'>\n";
		echo "<input type='hidden' name='posto'>\n";
		echo "<input type='hidden' name='mes' value='$mes'>\n";
		echo "<input type='hidden' name='ano' value='$ano'>\n";
		echo "<input type='hidden' name='qtde_os_item' value='" . pg_numrows($res) . "'>";
		echo "<table border='0' cellpadding='2' cellspacing='1'  align='center'>\n";
		
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
			$serie                = trim(pg_result($res,$i,serie));
			$qtde                 = trim(pg_result($res,$i,qtde));
			$login                = trim(pg_result($res,$i,login));
			$checked              = "";
			
			if ($posto_codigo_anterior != $posto_codigo) {
				if ($i != 0) {
					echo "<tr class='Conteudo'>\n";
					echo "<td>\n";
					echo "<input type='checkbox' name='liberar' value='liberar' onclick=\"SelecionarLiberar ('os_item',this,$qtde_liberar_inicio, $qtde_liberar_final);\" class='frm'>";
					echo "</td>\n";
					echo "<td>\n";
					echo "<input type='checkbox' name='recusar' value='recusar' onclick=\"SelecionarLiberar ('recusar',this, $qtde_liberar_inicio, $qtde_liberar_final);\" class='frm'>\n";
					echo "</td>\n";
					echo "<td>\n";
					echo "<input type='checkbox' name='estoque' value='estoque' onclick=\"SelecionarLiberar ('estoque',this, $qtde_liberar_inicio,$qtde_liberar_final);\" class='frm'>\n";
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
				echo "<td>Série</td>\n";
				echo "<td>Aber</td>\n";
				echo "<td>Fech</td>\n";
				echo "<td>Peça</td>\n";
				echo "<td>Inserida</td>\n";
				echo "<td>Qtd</td>\n";
				echo "</tr>\n";
			}
			
			$cor = ($i % 2 == 0) ? "#d2d7e1" : "#efeeea";
			
			if ($diferenca > 30) $cor = "#FFCCCC";
			
			if ($peca_item_aparencia == "t" && $posto_item_aparencia == "t") $cor = "#D7FFE1";
			
			if (strlen($login) > 0) $cor = "#91C8FF";
			
			if ($os_item == $_POST["os_item_".$i]) $checked = "checked";
			
			echo "<tr class='Conteudo' bgcolor='$cor' height='16'>\n";
			
			echo "<td bgcolor='#4c664b'>";
			echo "<input type='hidden' name='posto_$i' value='$posto'>\n";
			echo "<input type='checkbox' name='os_item_$i' id='os_item_$i' value='$os_item' class='frm'";
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
			echo "<td bgcolor='#dcc6c6'><input type='checkbox' $checked name='recusar_$i' id='recusar_$i' value='$os_item' class='frm'></td>\n";
			echo "<td><input type='checkbox' $checked name='estoque_$i' id='estoque_$i' value='$os_item' class='frm'></td>\n";
			echo "<td nowrap><a href='os_item.php?os=$os' target='_blank'>$serie</a></td>\n";
			echo "<td nowrap align='left'>";
			if (strlen($login) > 0) echo "Digita por " . strtoupper($login) . "<br>em ";
			echo $abertura;
			echo "</td>\n";
			echo "<td nowrap align='left'>$fechamento</td>\n";
			echo "<td nowrap align='left'>" . $peca_referencia . " - " . substr($peca_descricao,0,35) . "</td>\n";
			echo "<td nowrap align='left'>" . $inserida . "</td>\n";
			echo "<td nowrap align='right'>" . $qtde . "</td>\n";
			echo "</tr>\n";
			
			$qtde_liberar_final++;
			$posto_codigo_anterior = $posto_codigo;
			$posto_anterior = $posto;
		}
		echo "<tr class='Conteudo'>\n";
		echo "<td>";
		echo "<input type='checkbox' name='liberar'  value='liberar' onclick=\"SelecionarLiberar('os_item',this, $qtde_liberar_inicio, $qtde_liberar_final);\" class='frm'>";
		echo "</td>\n";
		echo "<td>";
		echo "<input type='checkbox' name='recusar' value='recusar' onclick=\"SelecionarLiberar('recusar',this, $qtde_liberar_inicio, $qtde_liberar_final);\" class='frm'></td>\n";
		echo "<td>";
		echo "<input type='checkbox' name='estoque' value='estoque' onclick=\"SelecionarLiberar('estoque',this, $qtde_liberar_inicio, $qtde_liberar_final);\" class='frm'>";
		echo "</td>\n";
		echo "<td colspan='6' align='left'>Clique no campo ao lado para selecionar todos</td>\n";
		echo "</tr>\n";
		echo "<tr class='Conteudo'>\n";
		echo "<td colspan='9'>";
		//echo "<img border='0' src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_os_item.btn_acao.value == '') { document.frm_os_item.btn_acao.value='GRAVAR'; document.frm_os_item.submit() } else { alert ('Aguarde submissão') }\" ALT='Gravar' style='cursor: hand;'>";
		echo "<img border='0' src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_os_item.btn_acao.value == '') { document.frm_os_item.btn_acao.value='GRAVAR'; document.frm_os_item.posto.value = '$posto'; document.frm_os_item.submit(); } else { alert('Aguarde submissão'); }\" ALT='Gravar' style='cursor: hand;'>";
		echo "<br>";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "<br>\n";
	}else{
		echo "<center><b>Não foi encontrado</b></center>";
	}
	?>

	</form>

<?
}
include "rodape.php"; ?>
