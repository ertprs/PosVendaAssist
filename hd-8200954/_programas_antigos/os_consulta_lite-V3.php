<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "funcoes.php";

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);

$os = $_GET['excluir'];

if (strlen ($os) > 0) {
/*
	$sql = "DELETE FROM tbl_os
			WHERE  os      = $os
			AND    posto   = $login_posto
			AND    fabrica = $login_fabrica";

	$sql = "UPDATE tbl_os SET excluida = 't'
			WHERE  tbl_os.os      = $os
			AND    tbl_os.posto   = $login_posto
			AND    tbl_os.fabrica = $login_fabrica;";
	$res = @pg_exec ($con,$sql);
*/

	$sql = "SELECT fn_os_excluida($os,$login_fabrica,null);";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
/*
	if (strlen($msg_erro) == 0) {
		header("Location: os_parametros.php");
		exit;
	}
*/
}

$msg = "";

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (strlen($acao) > 0 && $acao == "PESQUISAR") {
	if (strlen(trim($_POST["opcao1"])) > 0) $opcao1 = trim($_POST["opcao1"]);
	if (strlen(trim($_GET["opcao1"])) > 0)  $opcao1 = trim($_GET["opcao1"]);
	if (strlen(trim($_POST["opcao2"])) > 0) $opcao2 = trim($_POST["opcao2"]);
	if (strlen(trim($_GET["opcao2"])) > 0)  $opcao2 = trim($_GET["opcao2"]);
	if (strlen(trim($_POST["opcao3"])) > 0) $opcao3 = trim($_POST["opcao3"]);
	if (strlen(trim($_GET["opcao3"])) > 0)  $opcao3 = trim($_GET["opcao3"]);

	if (strlen($opcao1) == 0 && strlen($opcao2) == 0 && strlen($opcao3) == 0 && strlen($opcao4) == 0 && strlen($opcao5) == 0 && strlen($opcao6) == 0) {
		$erro .= " Selecione pelo menos uma opção para realizar a pesquisa. ";
	}
	if (strlen($erro) == 0 && strlen($opcao1) > 0) {
		if (strlen(trim($_POST["mes"])) > 0) $mes = trim($_POST["mes"]);
		if (strlen(trim($_GET["mes"])) > 0)  $mes = trim($_GET["mes"]);
		if (strlen(trim($_POST["ano"])) > 0) $ano = trim($_POST["ano"]);
		if (strlen(trim($_GET["ano"])) > 0)  $ano = trim($_GET["ano"]);

		if ($mes == 0) $erro .= " Selecione o mês para realizar a pesquisa. ";
		if (strlen($ano) == 0) $erro .= " Selecione o ano para realizar a pesquisa. ";

		if (strlen($erro) == 0) {
			$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
			$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
		}
	}

	if (strlen($erro) == 0 && strlen($opcao2) > 0) {
		if (strlen(trim($_POST["numero_os"])) > 0) $numero_os = trim($_POST["numero_os"]);
		if (strlen(trim($_GET["numero_os"])) > 0)  $numero_os = trim($_GET["numero_os"]);
	}

	if (strlen($erro) == 0 && strlen($opcao3) > 0) {
		if (strlen(trim($_POST["numero_serie"])) > 0) $numero_serie = trim($_POST["numero_serie"]);
		if (strlen(trim($_GET["numero_serie"])) > 0)  $numero_serie = trim($_GET["numero_serie"]);
	}

	if (strlen($erro) == 0 && strlen($opcao4) > 0) {
		if (strlen(trim($_POST["numero_nf"])) > 0) $numero_nf = trim($_POST["numero_nf"]);
		if (strlen(trim($_GET["numero_nf"])) > 0)  $numero_nf = trim($_GET["numero_nf"]);
	}

	if (strlen($erro) == 0 && strlen($opcao5) > 0) {
		if (strlen(trim($_POST["consumidor_nome"])) > 0) $consumidor_nome = trim($_POST["consumidor_nome"]);
		if (strlen(trim($_GET["consumidor_nome"])) > 0)  $consumidor_nome = trim($_GET["consumidor_nome"]);
	}

	if (strlen($erro) == 0 && strlen($opcao6) > 0) {
		if (strlen(trim($_POST["consumidor_cpf"])) > 0) $consumidor_cpf = trim($_POST["consumidor_cpf"]);
		if (strlen(trim($_GET["consumidor_cpf"])) > 0)  $consumidor_cpf = trim($_GET["consumidor_cpf"]);
	}

	if (strlen(trim($_POST["excluida"])) > 0) $excluida = trim($_POST["excluida"]);
	if (strlen(trim($_GET["excluida"])) > 0)  $excluida = trim($_GET["excluida"]);
}

$layout_menu = "os";
$title = "Seleção de Parâmetros para Relação de Ordens de Serviços Lançadas";
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

<? include "javascript_pesquisas.php"; ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/assist.js"></script>

<br>

<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Titulo">
		<td colspan="5" align="center">Selecione os parâmetros para a pesquisa.</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="5">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td nowrap><input type="checkbox" name="opcao1" value="1" <? if (strlen($opcao1) > 0) echo "checked"; ?>> Período </td>
		<td>Mês</td>
		<td>Ano</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="2">&nbsp;</td>
		<td>
			<select name="mes" size="1" class="frm">
				<?
				for ($i = 0 ; $i <= count($meses) ; $i++) {
					echo "<option value='$i'";
					if ($mes == $i) echo " selected";
					echo ">" . $meses[$i] . "</option>";
				}
				?>
			</select>
		</td>
		<td>
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
		<td>&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="5">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td colspan="2"><input type="checkbox" name="opcao2" value="2" <? if (strlen($opcao2) > 0) echo "checked"; ?>> Número da OS</td>
		<td><input type="text" name="numero_os" size="17" value="<?echo $numero_os?>"></td>
		<td>&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="5">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td colspan="2"><input type="checkbox" name="opcao3" value="3" <? if (strlen($opcao3) > 0) echo "checked"; ?>> Número de Série</td>
		<td><input type="text" name="numero_serie" size="17" value="<?echo $numero_serie?>"></td>
		<td>&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="5">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td colspan="2"><input type="checkbox" name="opcao4" value="4" <? if (strlen($opcao4) > 0) echo "checked"; ?>> Número da NF</td>
		<td><input type="text" name="numero_nf" size="17" value="<?echo $numero_nf?>"></td>
		<td>&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="5">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td colspan="2"><input type="checkbox" name="opcao5" value="5" <? if (strlen($opcao5) > 0) echo "checked"; ?>> Nome do Consumidor</td>
		<td><input type="text" name="consumidor_nome" size="17" value="<?echo $consumidor_nome?>"></td>
		<td>&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="5">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td colspan="2"><input type="checkbox" name="opcao6" value="6" <? if (strlen($opcao6) > 0) echo "checked"; ?>> CPF/CNPJ do Consumidor</td>
		<td><input type="text" name="consumidor_cpf" size="17" value="<?echo $consumidor_cpf?>"></td>
		<td>&nbsp;</td>
	</tr>
<!--<tr bgcolor="#D9E2EF">
		<td colspan="5">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="2">&nbsp;</td>
		<td><input type="radio" name="excluida" value="t" <? if ($excluida == "t") echo "checked"; ?>> Excluída</td>
		<td><input type="radio" name="excluida" value="f" <? if ($excluida == "f" || strlen($excluida) == 0) echo "checked"; ?>> Não Excluída</td>
		<td>&nbsp;</td>
	</tr>-->
	<tr bgcolor="#D9E2EF">
		<td colspan="5">&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="5"><img src="imagens/btn_pesquisar_400.gif" onClick="document.frm_consulta.acao.value='PESQUISAR'; document.frm_consulta.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</table>

</form>

<?
if (strlen($acao) > 0 && strlen($erro) == 0) {
		// OS não excluída
		$sql =	"SELECT tbl_os.os                                                          ,
						tbl_os.sua_os                                                      ,
						LPAD(tbl_os.sua_os,20,'0')                   AS ordem              ,
						TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')  AS digitacao          ,
						TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
						TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento        ,
						tbl_os.serie                                                      ,
						tbl_os.nota_fiscal                                                ,
						tbl_os.excluida                                                   ,
						tbl_os.motivo_atraso                                              ,
						tbl_os.tipo_os_cortesia                                           ,
						tbl_os.consumidor_revenda                                         ,
						tbl_os.consumidor_nome                                            ,
						tbl_os.revenda_nome                                               ,
						tbl_posto_fabrica.codigo_posto              AS posto_codigo       ,
						tbl_posto.nome                              AS posto_nome         ,
						tbl_os_extra.impressa                                             ,
						tbl_os_extra.extrato                                              ,
						tbl_os_extra.os_reincidente                                       ,
						tbl_produto.referencia                      AS produto_referencia ,
						tbl_produto.descricao                       AS produto_descricao  ,
						tbl_produto.voltagem                        AS produto_voltagem   ,
						(
							SELECT MAX(tbl_os_item.pedido) AS pedido
							FROM tbl_os_produto
							JOIN tbl_os_item USING (os_produto)
							WHERE tbl_os_produto.os = tbl_os.os
						)                                           AS pedido             ,
						(
							SELECT COUNT(tbl_os_item.os_item) AS qtde_item
							FROM   tbl_os_item
							JOIN   tbl_os_produto USING (os_produto)
							WHERE  tbl_os_produto.os = tbl_os.os
						)                                           AS qtde_item
				FROM      tbl_os
				JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_os.posto
				JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto
											AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN      tbl_produto       ON  tbl_produto.produto       = tbl_os.produto
				JOIN      tbl_os_extra      ON  tbl_os_extra.os           = tbl_os.os
				LEFT JOIN tbl_posto_linha   ON  tbl_posto_linha.linha = tbl_produto.linha
											AND tbl_posto_linha.posto = tbl_os.posto
				WHERE tbl_os.fabrica = $login_fabrica
				AND   (tbl_os.posto = $login_posto OR tbl_os.digitacao_distribuidor = $login_posto OR tbl_posto_linha.distribuidor = $login_posto)
				AND   tbl_os.excluida IS NOT TRUE";

		if (strlen($opcao1) > 0) {
			$sql .= " AND tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'";
		}

		if (strlen($numero_os) > 0) {
			if ($login_fabrica == 1) {
				$pos = strpos($numero_os, "-");
				if ($pos === false) {
					$pos = strlen($numero_os) - 5;
				}else{
					$pos = $pos - 5;
				}
				$numero_os = substr($numero_os, $pos,strlen($numero_os));
			}
#			$sql .= " AND tbl_os.sua_os ILIKE '%$numero_os%'";
			$sql .= " AND tbl_os.sua_os = '$numero_os'";
		}

		if (strlen($numero_serie) > 0) {
			$sql .= " AND tbl_os.serie = '$numero_serie'";
		}

		if (strlen($numero_nf) > 0) {
			$sql .= " AND tbl_os.nota_fiscal = '$numero_nf'";
		}

		if (strlen($consumidor_nome) > 0) {
			$sql .= " AND tbl_os.consumidor_nome ILIKE '%$consumidor_nome%'";
		}

		if (strlen($consumidor_cpf) > 0) {
			$sql .= " AND tbl_os.consumidor_cpf ILIKE '$consumidor_cpf%'";
		}

		if ($login_fabrica == 1) {
			$sql .= " AND tbl_os.consumidor_revenda = 'C'";
		}
		$sql .= " ORDER BY LPAD(tbl_os.sua_os,20,'0') DESC";

	/*elseif ($excluida == "t") {
		// OS excluída
		$sql =	"SELECT tbl_os_excluida.os                                                          ,
						tbl_os_excluida.sua_os                                                      ,
						LPAD(tbl_os_excluida.sua_os,20,'0')                   AS ordem              ,
						TO_CHAR(tbl_os_excluida.data_digitacao,'DD/MM/YYYY')  AS digitacao          ,
						TO_CHAR(tbl_os_excluida.data_abertura,'DD/MM/YYYY')   AS abertura           ,
						TO_CHAR(tbl_os_excluida.data_fechamento,'DD/MM/YYYY') AS fechamento         ,
						tbl_os_excluida.serie                                                       ,
						't'                                                   AS excluida           ,
						NULL                                                  AS motivo_atraso      ,
						NULL                                                  AS tipo_os_cortesia   ,
						NULL                                                  AS consumidor_revenda ,
						tbl_os_excluida.consumidor_nome                                             ,
						NULL                                                  AS revenda_nome       ,
						tbl_posto_fabrica.codigo_posto                        AS posto_codigo       ,
						tbl_posto.nome                                        AS posto_nome         ,
						tbl_os_extra.impressa                                                       ,
						tbl_os_extra.extrato                                                        ,
						tbl_os_extra.os_reincidente                                                 ,
						tbl_produto.referencia                                AS produto_referencia ,
						tbl_produto.descricao                                 AS produto_descricao  ,
						tbl_produto.voltagem                                  AS produto_voltagem   ,
						NULL                                                  AS pedido             ,
						NULL                                                  AS qtde_item
				FROM      tbl_os_excluida
				JOIN      tbl_os_extra      ON  tbl_os_extra.os           = tbl_os_excluida.os
				JOIN      tbl_produto       ON  tbl_produto.produto       = tbl_os_excluida.produto
				JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_os_excluida.posto
				JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto
											AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_posto_linha   ON  tbl_posto_linha.linha     = tbl_produto.linha
											AND tbl_posto_linha.posto     = tbl_os_excluida.posto
				WHERE tbl_os_excluida.fabrica = $login_fabrica
				AND   (tbl_os_excluida.posto = $login_posto OR tbl_posto_linha.distribuidor = $login_posto)
				AND   tbl_os_excluida.data_digitacao BETWEEN '$data_inicial' AND '$data_final'";
		if (strlen($numero_os) > 0) {
			if ($login_fabrica == 1) $numero_os = substr($numero_os,0,5);
			$sql .= " AND tbl_os_excluida.sua_os ILIKE '%$numero_os%'";
		}
		if (strlen($numero_serie) > 0) {
			$sql .= " AND tbl_os_excluida.serie = '$numero_serie'";
		}
		if (strlen($consumidor_nome) > 0) {
			$sql .= " AND tbl_os_excluida.consumidor_nome ILIKE '%$consumidor_nome%'";
		}
		if (strlen($consumidor_cpf) > 0) {
			$sql .= " AND tbl_os_excluida.consumidor_cpf ILIKE '%$consumidor_cpf%'";
		}
		$sql .= " ORDER BY LPAD(tbl_os.sua_os,20,'0') DESC";
	}
	*/
#	if (getenv("REMOTE_ADDR") == "201.0.9.216") echo nl2br($sql) . "<br>" . pg_numrows($res);

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		##### LEGENDAS - INÍCIO #####
		echo "<div align='left' style='position: relative; left: 25'>";
		echo "<table border='0' cellspacing='0' cellpadding='0'>";
		if ($excluida == "t") {
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FFE1E1'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; Excluídas do sistema</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
		}
		if ($login_fabrica != 1) {
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#D7FFE1'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; Reincidências</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
		}else{
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FFCC66'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; OSs sem lancamento de itens há mais de 5 dias, efetue o lançamento</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
			echo "<tr>";
			echo "<td align='center' width='10' bgcolor='#91C8FF'>&nbsp;</td>";
			echo "<td align='left'><font size=1><B>&nbsp; OSs sem fechamento há mais de 20 dias, informar \"Motivo\"</B></font></td>";
			echo "</tr>";
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FF0000'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; OSs que excederam o prazo limite de 30 dias para fechamento, informar \"Motivo\"</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
		}

		if ($login_fabrica == 14) {
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#91C8FF'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; OSs abertas há mais de 3 dias sem data de fechamento</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FF0000'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; OSs abertas há mais de 5 dias sem data de fechamento</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
		}

		if ($login_fabrica <> 1){
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#91C8FF'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; OSs abertas há mais de 25 dias sem data de fechamento</b></font></td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "</div>";
		##### LEGENDAS - FIM #####

		echo "<br>";

		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			if ($i == 0) {
				echo "<tr class='Titulo' height='15'>";
				echo "<td>OS</td>";
				echo "<td>SÉRIE</td>";
				echo "<td>AB</td>";
				echo "<td>FC</td>";
				echo "<td>CONSUMIDOR</td>";
				echo "<td>PRODUTO</td>";
				echo "<td><img border='0' src='imagens/img_impressora.gif' alt='Imprimir OS'></td>";
				if ($login_fabrica == 1) {
					echo "<td>Item</td>";
					$colspan = "6";
				}else{
					$colspan = "4";
				}
				echo "<td colspan='$colspan'>AÇÕES</td>";
				echo "</tr>";
			}

			$os                 = trim(pg_result($res,$i,os));
			$sua_os             = trim(pg_result($res,$i,sua_os));
			$digitacao          = trim(pg_result($res,$i,digitacao));
			$abertura           = trim(pg_result($res,$i,abertura));
			$fechamento         = trim(pg_result($res,$i,fechamento));
			$serie              = trim(pg_result($res,$i,serie));
			$excluida           = trim(pg_result($res,$i,excluida));
			$motivo_atraso      = trim(pg_result($res,$i,motivo_atraso));
			$tipo_os_cortesia   = trim(pg_result($res,$i,tipo_os_cortesia));
			$consumidor_revenda = trim(pg_result($res,$i,consumidor_revenda));
			$consumidor_nome    = trim(pg_result($res,$i,consumidor_nome));
			$revenda_nome       = trim(pg_result($res,$i,revenda_nome));
			$posto_codigo       = trim(pg_result($res,$i,posto_codigo));
			$posto_nome         = trim(pg_result($res,$i,posto_nome));
			$impressa           = trim(pg_result($res,$i,impressa));
			$extrato            = trim(pg_result($res,$i,extrato));
			$os_reincidente     = trim(pg_result($res,$i,os_reincidente));
			$produto_referencia = trim(pg_result($res,$i,produto_referencia));
			$produto_descricao  = trim(pg_result($res,$i,produto_descricao));
			$produto_voltagem   = trim(pg_result($res,$i,produto_voltagem));
			$pedido             = trim(pg_result($res,$i,pedido));
			$qtde_item          = trim(pg_result($res,$i,qtde_item));

			if ($i % 2 == 0) {
				$cor   = "#F1F4FA";
				$botao = "azul";
			}else{
				$cor   = "#F7F5F0";
				$botao = "amarelo";
			}

			##### VERIFICAÇÕES PARA OS CRITÉRIOS DA LEGENDA - INÍCIO #####
			if ($excluida == "t")            $cor = "#FFE1E1";
			if (strlen($os_reincidente) > 0) $cor = "#D7FFE1";

			// OSs abertas há mais de 25 dias sem data de fechamento
			if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica != 14) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '25 days','YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_atual = pg_result ($resX,0,0);

				if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#91C8FF";
			}

			// CONDIÇÕES PARA INTELBRÁS - INÍCIO
			if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 14) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '3 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_atual = pg_result($resX,0,0);

				if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#91C8FF";

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_atual = pg_result ($resX,0,0);

				if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#FF0000";
			}
			// CONDIÇÕES PARA INTELBRÁS - FIM

			// CONDIÇÕES PARA BLACK & DECKER - INÍCIO
			// Verifica se não possui itens com 5 dias de lançamento
			if ($login_fabrica == 1) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR(current_date + INTERVAL '5 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$data_hj_mais_5 = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sql = "SELECT COUNT(tbl_os_item.*) AS total_item
						FROM tbl_os_item
						JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
						JOIN tbl_os         ON tbl_os.os                 = tbl_os_produto.os
						WHERE tbl_os.os = $os
						AND   tbl_os.data_abertura::date >= '$aux_consulta'";
				$resItem = pg_exec($con,$sql);

				$itens = pg_result($resItem,0,total_item);

				if ($itens == 0 && $aux_consulta > $data_hj_mais_5) $cor = "#FFCC66";

				$mostra_motivo = 2;
			}

			// Verifica se está sem fechamento há 20 dias ou mais da data de abertura
			if (strlen($fechamento) == 0 && $mostra_motivo == 2 && $login_fabrica == 1) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '20 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_atual = pg_result($resX,0,0);

				if ($consumidor_revenda != "R") {
					if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) {
						$mostra_motivo = 1;
						$cor = "#91C8FF";
					}
				}
			}

			// Se estiver acima dos 30 dias, não exibirá os botões
			if (strlen($fechamento) == 0 && $login_fabrica == 1) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '30 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_atual = pg_result($resX,0,0);

				if ($consumidor_revenda != "R"){
					if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) {
						$mostra_motivo = 1;
						$cor = "#FF0000";
					}
				}
			}
			// CONDIÇÕES PARA BLACK & DECKER - FIM

			##### VERIFICAÇÕES PARA OS CRITÉRIOS DA LEGENDA - FIM #####

			if (strlen($sua_os) == 0) $sua_os = $os;
			if ($login_fabrica == 1) $sua_os = $posto_codigo.$sua_os;

			echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
			echo "<td nowrap>" . $sua_os . "</td>";
			echo "<td nowrap>" . $serie . "</td>";
			echo "<td nowrap><acronym title='Data Abertura: $abertura' style='cursor: help;'>" . substr($abertura,0,5) . "</acronym></td>";
			echo "<td nowrap><acronym title='Data Fechamento: $fechamento' style='cursor: help;'>" . substr($fechamento,0,5) . "</acronym></td>";
			echo "<td nowrap><acronym title='Consumidor: $consumidor_nome' style='cursor: help;'>" . substr($consumidor_nome,0,15) . "</acronym></td>";
			$produto = $produto_referencia . " - " . $produto_descricao;
			echo "<td nowrap><acronym title='Referência: $produto_referencia \nDescrição: $produto_descricao \nVoltagem: $produto_voltagem' style='cursor: help;'>" . substr($produto,0,20) . "</acronym></td>";

			##### VERIFICAÇÃO SE A OS FOI IMPRESSA #####
			echo "<td width='30' align='center'>";
			if (strlen($impressa) > 0) echo "<img border='0' src='imagens/img_ok.gif' alt='OS já foi impressa'>";
			else                       echo "<img border='0' src='imagens/img_impressora.gif' alt='Imprimir OS'>";
			echo "</td>";

			##### VERIFICAÇÃO SE TEM ITEM NA OS PARA A FÁBRICA 1 #####
			if ($login_fabrica == 1) {
				echo "<td width='30' align='center'>";
				if ($qtde_item > 0) echo "<img border='0' src='imagens/img_ok.gif' alt='OS com item'>";
				else                echo "&nbsp;";
				echo "</td>";
			}

			echo "<td width='60' align='center'>";
			if ($excluida == "f" || strlen($excluida) == 0) echo "<a href='os_press.php?os=$os' target='_blank'><img border='0' src='imagens/btn_consulta.gif'></a>";
			echo "</td>\n";

			echo "<td width='60' align='center'>";
			if ($excluida == "f" || strlen($excluida) == 0) {
				if ($login_fabrica == 1 && $tipo_os_cortesia == "Compressor") {
					echo "<a href='os_print_blackedecker_compressor.php?os=$os' target='_blank'>";
				}else{
					echo "<a href='os_print.php?os=$os' target='_blank'>";
				}
				echo "<img border='0' src='imagens/btn_imprime.gif'></a>";
			}
			echo "</td>\n";

			if ($login_fabrica == 1) {
				echo "<td width='60' align='center'>";
				if (($excluida == "f" || strlen($excluida) == 0) && strlen($fechamento) == 0) {
					echo "<a href='os_cadastro.php?os=$os'><img border='0' src='imagens/btn_alterar_cinza.gif'></a>";
				}else{
					echo "&nbsp;";
				}
				echo "</td>\n";
			}

			echo "<td width='60' align='center' nowrap>";
			if (($login_fabrica == 3 || $login_fabrica == 6) && strlen ($fechamento) == 0) {
				if ($excluida == "f" || strlen($excluida) == 0) {
					echo "<a href='os_item.php?os=$os' target='_blank'><img border='0' src='imagens/btn_lanca.gif'></a>";
				}
			}elseif ($login_fabrica == 1 && strlen ($fechamento) == 0 ) {
				if ($excluida == "f" || strlen($excluida) == 0) {
					echo "<a href='os_item.php?os=$os' target='_blank'><img border='0' src='imagens/btn_lanca.gif'></a>";
				}
			}elseif ($login_fabrica == 7 && strlen ($fechamento) == 0 ) {
				echo "<a href='os_filizola_valores.php?os=$os' target='_blank'><img border='0' src='imagens/btn_lanca.gif'></a>";
			}elseif (strlen($fechamento) == 0 ) {
				if ($excluida == "f" || strlen($excluida) == 0) {
					if ($login_fabrica == 1 && $tipo_os_cortesia == "Compressor") {
						echo "<a href='os_blackedecker_valores.php?os=$os'>";
					}else{
						echo "<a href='os_item.php?os=$os' target='_blank'>";
					}
					echo "<img border='0' src='imagens/btn_lanca.gif'></a>";
				}
			}elseif (strlen($fechamento) > 0 && strlen($extrato) == 0) {
				if ($excluida == "f" || strlen($excluida) == 0) {
					echo "<a href='os_item.php?os=$os&reabrir=ok'><img border='0' src='imagens/btn_reabriros.gif'></a>";
				}
			}else{
				echo "&nbsp;";
			}
			echo "</td>\n";

			if ($login_fabrica == 1) {
				echo "<td width='60' align='center'>";
				if (strlen ($fechamento) == 0 AND ($excluida == "f" OR strlen($excluida) == 0) AND $mostra_motivo == 1) {
					echo "<a href='os_motivo_atraso.php?os=$os' target='_blank'><img border='0' src='imagens/btn_motivo.gif'></a>";
				}else{
					echo "&nbsp;";
				}
				echo "</td>\n";
			}

			echo "<td width='60' align='center'>";
			if (strlen($fechamento) == 0 && strlen($pedido) == 0 && $login_fabrica != 7 ) {
				if ($excluida == "f" || strlen($excluida) == 0) {
					echo "<a href=\"javascript: if (confirm('Deseja realmente excluir a OS $sua_os ?') == true) { window.location='$PHP_SELF?excluir=$os'; }\"><img border='0' src='imagens/btn_excluir.gif'></a>";
				}
			}else{
				echo "&nbsp;";
			}
			echo "</td>\n";

			if ($login_fabrica == 7) {
				echo "<td width='60' align='center'>";
				echo "<a href='os_matricial.php?os=$os' target='_blank'>Matricial</a>";
				echo "</td>\n";
			}

			echo "</tr>";
		}
		echo "</table>";
	}else{
		echo "<table border='0' cellpadding='2' cellspacing='0'>";
		echo "<tr height='50'>";
		echo "<td valign='middle'><img src='imagens/atencao.gif' border='0'> &nbsp; &nbsp;<B>Não foram encontrados registros com os parâmetros informados/digitados!!!</B></td>";
		echo "</tr>";
		echo "</table>";
	}

}
?>

<? include "rodape.php" ?>
