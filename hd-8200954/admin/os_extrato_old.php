<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";

$admin_privilegios="financeiro";
include "autentica_admin.php";

if ($login_fabrica == 3) {
	echo "<h1>Fechamento de Extrato realizado pela TELECONTROL</h1>";
	exit;
}

$erro = "";

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET ["acao"]) > 0) $acao = strtoupper($_GET ["acao"]);

if ($acao == "BUSCAR") {
	$data_limite_01 = $_GET["data_limite_01"];
	$posto_codigo   = $_GET["posto_codigo"];
	$posto_nome     = $_GET["posto_nome"];
	
	if ($data_limite_01 != "dd/mm/aaaa" && strlen($data_limite_01) > 0) {
		$data_limite = fnc_formata_data_pg($data_limite_01);
		if ($data_limite != "null") {
			$data_limite    = str_replace("'", "", $data_limite);
			$dia_limite     = substr($data_limite, 8, 2);
			$mes_limite     = substr($data_limite, 5, 2);
			$ano_limite     = substr($data_limite, 0, 4);
			$data_limite_01 = $dia_limite . "/" . $mes_limite . "/" . $ano_limite;
		}else{
			$erro .= " Preencha o campo Data Limite corretamente. ";
		}
	}else{
		$erro .= " Preencha o campo Data Limite. ";
	}
	

	if (strlen($posto) > 0) {
		$sql = "SELECT	tbl_posto.posto                ,
						tbl_posto.nome                 ,
						tbl_posto_fabrica.codigo_posto
				FROM	tbl_posto
				JOIN	tbl_posto_fabrica USING (posto)
				WHERE	tbl_posto_fabrica.fabrica = $login_fabrica
				AND		tbl_posto.posto = $posto";
		$res = pg_exec($con,$sql);
		if (pg_numrows($res) == 1) {
			$posto        = trim(pg_result($res,0,posto));
			$posto_codigo = trim(pg_result($res,0,codigo_posto));
			$posto_nome   = trim(pg_result($res,0,nome));
		}else{
			$erro .= " Posto não encontrado. ";
		}
	}else{
		$erro .= " Selecione o Posto. ";
	}
}

##### APROVAR TODOS #####
if ($acao == "APROVAR_TODOS") {
	$posto       = $_POST["posto"];
	$data_limite = $_POST["data_limite"];
	$total       = $_POST["total"];

	$res = pg_exec($con,"BEGIN TRANSACTION");

	if (strlen($posto) > 0 && strlen($data_limite) > 0 && strlen($total) > 0) {
		for ($i = 0 ; $i < $total ; $i++){
			$sql = "SELECT fn_fechamento_extrato ($posto, $login_fabrica, '$data_limite'::date);";
			$res = pg_exec($con,$sql);
			$extrato = pg_result($res,0,0);
			$erro .= pg_errormessage($con);

			if (strlen($erro) == 0){
				$sql = "SELECT fn_aprova_extrato($posto, $login_fabrica, $extrato)";
				$res = pg_exec ($con,$sql);
				$erro .= pg_errormessage($con);
			}
			
			if (strlen($erro) > 0) break;
		}
	}

	if (strlen($erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		header("Location: $PHP_SELF");
		exit;
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}

##### APROVAR SELECIONADOS #####
if ($acao == "APROVAR_SELECIONADOS") {
	$data_limite = $_POST["data_limite"];
	$total       = $_POST["total"];

#	$res = pg_exec($con,"BEGIN TRANSACTION");

	for ($i = 0 ; $i < $total ; $i++) {
		$extrato_fecha = trim($_POST["extrato_fecha_" . $i]);
		if (strlen($extrato_fecha) > 0) {
			$sql = "SELECT fn_fechamento_extrato ($extrato_fecha, $login_fabrica, '$data_limite'::date);";
			$res = pg_exec($con,$sql);
			$extrato = pg_result($res,0,0);
			$erro .= pg_errormessage($con);
			
			if (strlen($erro) == 0) {
				$sql = "SELECT fn_aprova_extrato ($extrato_fecha, $login_fabrica, $extrato);";
				$res = pg_exec($con,$sql);
				$erro .= pg_errormessage($con);
			}
		}
		
		if (strlen($erro) > 0) break;
	}

	if (strlen($erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		header("Location: $PHP_SELF");
		exit;
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}

// FECHAR EXTRATO DO POSTO
if ($acao == "FECHAR") {
	$posto       = $_GET["posto"];
	$data_limite = $_GET["data_limite"];

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$sql = "SELECT fn_fechamento_extrato ($posto, $login_fabrica, '$data_limite'::date);";
	$res = pg_exec($con,$sql);
	$extrato = pg_result($res,0,0);
	$erro .= pg_errormessage($con);
	
	if (strlen($erro) == 0){
		$sql = "SELECT fn_aprova_extrato ($posto, $login_fabrica, $extrato);";
		$res = pg_exec($con,$sql);
		$erro .= pg_errormessage($con);
	}
	
	if (strlen($erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		header("Location: $PHP_SELF");
		exit;
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}

$layout_menu = "financeiro";
$title = "Pré Fechamento de Extrato";

include "cabecalho.php";

?>

<style type="text/css">
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
function fnc_pesquisa_posto(campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}
</script>

<script language="JavaScript" src="js/cal2.js"></script>
<script language="JavaScript" src="js/cal_conf2.js"></script>

<? if (strlen($erro) > 0){ ?>
<br>
<table width="420" border="0" cellpadding="2" cellspacing="0" align="center" class="error">
	<tr>
		<td><? echo $erro; ?></td>
	</tr>
</table>
<? } ?>


<?
if (strlen($acao) == 0 OR strlen($erro) > 0) {
?>

<table width="400" border="0" cellpadding="2" cellspacing="0" align="center">
<form method="get" action="<?echo $PHP_SELF?>" name="FormExtrato">
<input type="hidden" name="acao">
	<tr class="Titulo">
		<td colspan="4" height="20">PREENCHA OS CAMPOS ABAIXO PARA REALIZAR A PESQUISA</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td nowrap align="center">
			Postos com OSs para gerar extrato<br>
<?

if (strlen($data_limite) == 0) $data_limite = date("Y-m-d");

$sql = "SELECT distinct tbl_posto.posto, tbl_posto_fabrica.codigo_posto, tbl_posto.nome 
		FROM   tbl_os 
		JOIN   tbl_os_extra  ON tbl_os_extra.os = tbl_os.os 
							AND tbl_os.fabrica  = $login_fabrica
		JOIN   tbl_posto_fabrica  ON tbl_posto_fabrica.posto   = tbl_os.posto 
								 AND tbl_posto_fabrica.fabrica = tbl_os.fabrica 
								 AND tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN   tbl_posto on tbl_posto.posto = tbl_os.posto
		LEFT JOIN tbl_os_status ON tbl_os_status.os = tbl_os.os 
		WHERE  tbl_os.finalizada      IS NOT NULL 
		AND    tbl_os.data_fechamento IS NOT NULL 
		AND    tbl_os.data_fechamento::date <= '$data_limite' 
		AND    tbl_os.excluida        IS NOT TRUE 
		AND    tbl_os_extra.extrato   IS NULL 
		AND    tbl_os.fabrica          = $login_fabrica 
		AND    (tbl_os_status.status_os NOT IN (13,15) OR tbl_os_status.status_os IS NULL)
		ORDER BY tbl_posto.nome;";

$sql = "SELECT distinct tbl_posto.posto, tbl_posto_fabrica.codigo_posto, tbl_posto.nome 
		FROM   tbl_posto
		JOIN   tbl_posto_fabrica     ON tbl_posto_fabrica.posto   = tbl_posto.posto 
									AND tbl_posto_fabrica.fabrica = $login_fabrica
		ORDER BY tbl_posto.nome;";

//if ($ip == "201.0.9.216") { echo nl2br($sql); exit; }
$res = pg_exec ($con,$sql);
flush();

echo "<select name='posto' size='1' class='frm'>";
echo "<option value='' selected></option>";
for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
	$postoX        = trim(pg_result($res,$i,posto));
	$codigo_postoX = trim(pg_result($res,$i,codigo_posto));
	$nomeX         = trim(pg_result($res,$i,nome));
	echo "<option value='$postoX'>$codigo_postoX - $nomeX</option>";
}
echo "</select>";
?>
		</td>
		<td width="10">&nbsp;</td>
	</tr>

<!-- 
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td nowrap align="center">
			Código do Posto<br>
			<input type="text" name="posto_codigo" size="10" value="<?echo $posto_codigo?>" class="frm">
			<img src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.FormExtrato.posto_codigo, document.FormExtrato.posto_nome, 'codigo');">
		</td>
		<td nowrap align="center">
			Nome do Posto<br>
			<input type="text" name="posto_nome" size="25" value="<?echo $posto_nome?>" class="frm">
			<img src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisas postos pelo nome" onclick="javascript: fnc_pesquisa_posto (document.FormExtrato.posto_codigo, document.FormExtrato.posto_nome, 'nome');">
		</td>
		<td width="10">&nbsp;</td>
	</tr>
 -->
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td>
			Data Limite<br>
			<input type="text" name="data_limite_01" size="13" maxlength="10" value="<? if (strlen($data_limite_01) > 0) echo $data_limite_01; else echo "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
			<img src="imagens_admin/btn_lupa.gif" align="absmiddle" onclick="javascript: showCal('DataLimite01');" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4" align="center"><img src="imagens_admin/btn_pesquisar_400.gif" onClick="javascript: document.FormExtrato.acao.value='BUSCAR'; document.FormExtrato.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</form>
</table>
<?
}else{
	echo "<br><br><center><a href='javascript:history.back()'>Voltar</a></center><br>";
}
?>

<?
// INICIO DA SQL
if (strlen($acao) > 0 AND strlen($erro) == 0) {
	$sql =	"SELECT b.posto                           ,
					b.cnpj                            ,
					b.nome                            ,
					b.codigo_posto                    ,
					SUM(b.mao_de_obra) AS mao_de_obra
			FROM (
				SELECT  tbl_posto.posto ,
						tbl_posto.cnpj  ,
						tbl_posto.nome  ,
						a.codigo_posto  ,
						a.mao_de_obra   ,
						a.os
				FROM (
					SELECT  tbl_os.os                                               ,
							tbl_os.posto                                            ,
							tbl_posto_fabrica.codigo_posto                          ,
							CASE WHEN tbl_os.mao_de_obra IS NOT NULL
							THEN tbl_os.mao_de_obra
							ELSE 0 END                               AS mao_de_obra
					FROM tbl_os
					JOIN tbl_os_extra       ON  tbl_os_extra.os           = tbl_os.os
											AND tbl_os.fabrica            = $login_fabrica
					JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_os.posto
											AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
											AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE tbl_os.finalizada      IS NOT NULL
					AND   tbl_os.data_fechamento IS NOT NULL
					AND   tbl_os.excluida        IS NOT TRUE
					AND   tbl_os_extra.extrato   IS NULL
					AND   tbl_os.fabrica = $login_fabrica
					AND   tbl_os.posto   = $posto
					AND   tbl_os.data_fechamento::timestamp <= '$data_limite 23:59:59'
				) AS a
				JOIN tbl_posto ON tbl_posto.posto = a.posto
			) AS b
			LEFT JOIN tbl_os_status ON tbl_os_status.os = b.os
			WHERE (tbl_os_status.status_os NOT IN (13,15) OR tbl_os_status.status_os IS NULL)
			GROUP BY b.posto        ,
					 b.cnpj         ,
					 b.nome         ,
					 b.codigo_posto
			ORDER BY b.nome;";
	
	$res = pg_exec ($con,$sql);

//if ($ip == "201.0.9.216") { echo nl2br($sql) . "<br>" . pg_numrows($res); }

	flush();

	if (@pg_numrows($res) > 0) {
		echo "<form method='post' name='frm_extrato' action='$PHP_SELF'>";
		
		echo "<input type='hidden' name='acao'>";
		
		echo "<input type='hidden' name='posto' value='$posto'>";
		echo "<input type='hidden' name='data_limite' value='$data_limite'>";
		
		echo "<table width='700' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
/*
		echo "<tr class='Titulo'>";
		echo "<td colspan='3'><img border='0' src='imagens/btn_aprovar_selecionados.gif' onclick=\"javascript: if (document.frm_extrato.acao.value == '') { document.frm_extrato.acao.value='APROVAR_SELECIONADOS'; document.frm_extrato.submit(); }else{ alert('Aguarde submissão'); }\" alt='Aprovar fechamento de postos selecionados' style='cursor: hand;'></td>";
		echo "<td colspan='3'><img border='0' src='imagens_admin/btn_aprovar_todos.gif' onclick=\"javascript: if (document.frm_extrato.acao.value == '') { document.frm_extrato.acao.value='APROVAR_TODOS'; document.frm_extrato.submit(); }else{ alert('Aguarde submissão'); }\" alt='Aprovar fechamento de todos os Postos' style='cursor: hand;'></td>";
		echo "</tr>";
*/
		echo "<tr class='Titulo' height='15'>";
		echo "<td>&nbsp;</td>";
		echo "<td>CNPJ POSTO</td>";
		echo "<td>NOME POSTO</td>";
		echo "<td>MÃO DE OBRA</td>";
		echo "<td colspan='2'>AÇÕES</td>";
		echo "</tr>";
		
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$posto        = trim(pg_result($res,$i,posto));
			$cnpj         = trim(pg_result($res,$i,cnpj));
			$nome         = trim(pg_result($res,$i,nome));
			$codigo_posto = trim(pg_result($res,$i,codigo_posto));
			$mao_de_obra  = trim(pg_result($res,$i,mao_de_obra));
			$mao_de_obra  = number_format($mao_de_obra, 2, ",", "");
			
			if ($i % 2 == 0) {
				$cor = "#F1F4FA";
				$btn = "azul";
			}else{
				$cor = "#F7F5F0";
				$btn = "amarelo";
			}
			
			if ($i % 10 == 0 && $i <> 0) {
				echo "</table>";
				echo "<table width='700' border='0' cellspacing='2' cellpadding='1' align='center'>";
				flush();
			}
			
			echo "<tr class='Conteudo' bgcolor='$cor'>";
			echo "<td nowrap><input type='hidden' name='aux_posto[$i]' value='$posto'><input type='checkbox' name='extrato_fecha_$i' value='$posto'></td>";
			echo "<td nowrap>$cnpj</td>";
			if ($codigo_posto == $cnpj || strlen($codigo_posto) == 0) {
				echo "<td nowrap><acronym title='Razão Social: $nome' style='cursor: help;'>" . substr($nome,0,33) . "</acronym></td>";
			}else{
				echo "<td nowrap><acronym title='Código: $codigo_posto\nRazão Social: $nome' style='cursor: help;'>" . $codigo_posto . " - " . substr($nome,0,33) . "</acronym></td>";
			}
			echo "<td nowrap>$mao_de_obra</td>";
			echo "<td nowrap><a href=\"javascript: if (confirm('Deseja fechar o extrato para este posto?') == true) { window.location='$PHP_SELF?acao=FECHAR&posto=$posto&data_limite=$data_limite'; } \"><img src='imagens/btn_fechar_$btn.gif'></a></td>";
			echo "<td nowrap><a href='os_extrato_detalhe.php?posto=$posto&data_limite=$data_limite_01' target='_blank'><img src='imagens_admin/btn_detalhar_$btn.gif'></a></td>";
			echo "</tr>";
		}
		
		echo "</table>";
		echo "<input type='hidden' name='total' value='$i'>";
		echo "</form>";
	}
}

echo "<br>";

include "rodape.php";
?>
