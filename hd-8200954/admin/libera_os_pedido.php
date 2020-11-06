<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

# 32672 - Francisco Ambrozio (13/8/08)
#  Liberado para Filizola
if ($login_fabrica <> 6 and $login_fabrica <> 7) {
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
}else $msg_erro = "É obrigatória selecionar o mês e o ano";

$layout_menu = "gerencia";
$title = "Manutenção de Itens de OS para Pedidos";
include 'cabecalho.php';
?>

<script type="text/javascript" src="js/jquery-1.2.1.pack.js"></script>
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
		$('input[name=veiculo]').each(function (){
				if (this.checked){
					atualizaValorKM(this);
				}
			});

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
			tbl_os.os                                          AS os                   ,
			tbl_os.sua_os                                      AS sua_os               ,
			tbl_os.serie                                       AS serie                ,
			to_char(tbl_os.data_abertura,'DD/MM/YY')           AS abertura             ,
			to_char(tbl_os.data_fechamento,'DD/MM/YY')         AS fechamento           ,
			(tbl_os.finalizada::date - tbl_os.data_fechamento) AS diferenca            ,
			tbl_os.consumidor_nome                             AS consumidor_nome      ,
			tbl_os.consumidor_cpf                              AS consumidor_cpf
		FROM      tbl_os
		JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_os.posto
		JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto  AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_os.validada       IS NOT NULL
		AND   tbl_os.fabrica        = $login_fabrica
		AND   tbl_os.excluida       IS NOT TRUE
		AND   tbl_os.posto          = 6359
		ORDER BY    tbl_posto.nome                                                 ASC,
					tbl_os.consumidor_cpf                                          ASC,
					tbl_os.data_digitacao                                          ASC,
					lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,'0') ASC,
					lpad(tbl_os.os::text,20,'0')                                   ASC
			;";

	if($login_admin=="567"){
		#echo nl2br($sql); 
		#exit;
	}
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
		
		echo "<table border='0' cellpadding='2' cellspacing='1'  align='center'>\n";
		
		$qtde_liberar_inicio = 0;
		$qtde_liberar_final  = 0;
		$cont = 0;
		$consumidor_cpf_anterior = "****";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$posto                = trim(pg_result($res,$i,posto));
			$posto_codigo         = trim(pg_result($res,$i,posto_codigo));
			$posto_item_aparencia = trim(pg_result($res,$i,posto_item_aparencia));
			$posto_nome           = trim(pg_result($res,$i,posto_nome));
			$os                   = trim(pg_result($res,$i,os));
			$sua_os               = trim(pg_result($res,$i,sua_os));
			$abertura             = trim(pg_result($res,$i,abertura));
			$fechamento           = trim(pg_result($res,$i,fechamento));
			$diferenca            = trim(pg_result($res,$i,diferenca));
			$consumidor_nome      = trim(pg_result($res,$i,consumidor_nome));
			$consumidor_cpf       = trim(pg_result($res,$i,consumidor_cpf));
			$checked              = "";
			
			if ($consumidor_cpf_anterior != $consumidor_cpf) {
				if ($i != 0) {
					echo "<tr class='Conteudo'>\n";
					echo "<td>\n";
					echo "<input type='checkbox' name='liberar' value='liberar' onclick=\"SelecionarLiberar ('liberar',this,$qtde_liberar_inicio, $qtde_liberar_final);\" class='frm'>";
					echo "</td>\n";
					echo "<td>\n";
					echo "<input type='checkbox' name='recusar' value='recusar' onclick=\"SelecionarLiberar ('recusar',this, $qtde_liberar_inicio, $qtde_liberar_final);\" class='frm'>\n";
					echo "</td>\n";
					echo "<td colspan='8' align='left'>Clique no campo ao lado para selecionar todos</td>\n";
					echo "</tr>\n";
					
					$qtde_liberar_inicio = $qtde_liberar_final;
					
					echo "<tr class='Conteudo'>\n";
					echo "<td colspan='10'>\n";
					echo "<img border='0' src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_os_item.btn_acao.value == '') { document.frm_os_item.btn_acao.value='GRAVAR'; document.frm_os_item.posto.value = '$posto_anterior'; document.frm_os_item.submit(); } else { alert('Aguarde submissão'); }\" ALT='Gravar' style='cursor: hand;'>";
					echo "<br>";
					echo "<br>";
					echo "</td>\n";
					echo "</tr>\n";
					
					$cont++;
				}
				echo "<tr class='Titulo'>\n";
				echo "<td colspan='10'>$i<b>Posto: $posto_codigo - $posto_nome</b></td>\n";
				echo "</tr>\n";
				
				echo "<tr class='Titulo'>\n";
				echo "<td>Liberar</td>\n";
				echo "<td>Recusar</td>\n";
				echo "<td>Série</td>\n";
				echo "<td>Abertura</td>\n";
				echo "<td>Fechamento</td>\n";
				echo "<td>CPF / CNPJ</td>\n";
				echo "<td>Cliente</td>\n";
				echo "<td>Deslocamento</td>\n";
				echo "<td>Mão de Obra</td>\n";
				echo "<td>Outros Valores</td>\n";
				echo "</tr>\n";
			}
			
			$cor = ($i % 2 == 0) ? "#D2D7E1" : "#EFEEEA";
			
			if ($diferenca > 30) $cor = "#FFCCCC";
			
			if ($os_item == $_POST["acao_".$i]) $checked = "checked";

			echo "<tr class='Conteudo' bgcolor='$cor' height='16'>\n";
			echo "<td bgcolor='#4c664b'><input type='hidden' name='posto_$i' value='$posto'>\n
					<input type='radio' name='acao_$i' value='liberar' class='frm' $checked1 ></td>\n";
			echo "<td bgcolor='#dcc6c6'><input type='radio' name='acao_$i' value='recusar' class='frm' $checked2 ></td>\n";
			echo "<td nowrap><a href='os_item.php?os=$os' target='_blank'>".$serie."</a></td>\n";
			echo "<td nowrap align='left'>".$abertura."</td>\n";
			echo "<td nowrap align='left'>".$fechamento."</td>\n";
			echo "<td nowrap align='left'>".$consumidor_cpf."</td>\n";
			echo "<td nowrap align='left'>".$consumidor_nome."</td>\n";
			echo "<td nowrap align='right'>"."</td>\n";
			echo "<td nowrap align='right'>"."</td>\n";
			echo "<td nowrap align='right'>"."</td>\n";
			echo "</tr>\n";
			
			$qtde_liberar_final++;
			$consumidor_cpf_anterior = $consumidor_cpf;
		}
		echo "<tr class='Conteudo'>\n";
		echo "<td><input type='checkbox' name='liberar'  value='liberar' onclick=\"SelecionarLiberar('os_item',this, $qtde_liberar_inicio, $qtde_liberar_final);\" class='frm'></td>\n";
		echo "<td><input type='checkbox' name='recusar' value='recusar' onclick=\"SelecionarLiberar('recusar',this, $qtde_liberar_inicio, $qtde_liberar_final);\" class='frm'></td>\n";
		echo "<td colspan='8' align='left'>Clique no campo ao lado para selecionar todos</td>\n";
		echo "</tr>\n";

		echo "<tr class='Conteudo'>\n";
		echo "<td colspan='10'>";
		echo "<input type='hidden' name='qtde_os_item' value='" . pg_numrows($res) . "'>";
		//echo "<img border='0' src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_os_item.btn_acao.value == '') { document.frm_os_item.btn_acao.value='GRAVAR'; document.frm_os_item.submit() } else { alert ('Aguarde submissão') }\" ALT='Gravar' style='cursor: hand;'>";
		echo "<img border='0' src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_os_item.btn_acao.value == '') { document.frm_os_item.btn_acao.value='GRAVAR'; document.frm_os_item.posto.value = '$posto'; document.frm_os_item.submit(); } else { alert('Aguarde submissão'); }\" ALT='Gravar' style='cursor: hand;'>";
		echo "<br>";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "</form>";
		echo "<br>\n";
	}else{
		echo "<center><b>Não foi encontrado</b></center>";
	}
	?>

	</form>

<?
}
include "rodape.php"; ?>
