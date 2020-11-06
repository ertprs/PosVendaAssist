<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

include "funcoes.php";

$admin_privilegios = "gerencia,call_center";
include "autentica_admin.php";

$msg = "";

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (strlen($_POST["botao"]) > 0) $botao = strtoupper($_POST["botao"]);

if (strlen($botao) > 0 ) {
	$mes          = $_POST["mes"];
	$ano          = $_POST["ano"];
	$posto_codigo = trim($_POST["posto_codigo"]);
	$posto_nome   = trim($_POST["posto_nome"]);
	$linha        = $_POST["linha"];
	$os_situacao  = $_POST["os_situacao"];

	if (strlen($mes) > 0 && strlen($ano) > 0) {
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
	}

	if (strlen($posto_codigo) > 0 || strlen($posto_nome) > 0) {
		$sql =	"SELECT tbl_posto_fabrica.posto               ,
						tbl_posto_fabrica.codigo_posto ,
						tbl_posto.nome                
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING (posto)
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica";
		if (strlen($posto_codigo) > 0)
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo'";
		if (strlen($posto_nome) > 0)
			$sql .= " AND tbl_posto.nome ILIKE '%$posto_nome%'";
		$res = pg_exec($con,$sql);
		
		if (pg_numrows($res) == 1) {
			$posto        = trim(pg_result($res,0,posto));
			$posto_codigo = trim(pg_result($res,0,codigo_posto));
			$posto_nome   = trim(pg_result($res,0,nome));
		}else{
			$msg .= " Posto não encontrado. ";
		}
	}
}

$layout_menu = "call_center";
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

<br>

<? if (strlen($msg) > 0) { ?>
<table width="600" align="center" border="0" cellspacing="0" cellpadding="2" class="error">
	<tr>
		<td align="center"><? echo $msg; ?></td>
	</tr>
</table>
<? } ?>

<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="botao">

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Titulo" height="30">
		<td colspan="4" align="center">SELECIONE OS PARÂMETROS PARA A PESQUISA</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td>Mês</td>
		<td>Ano</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td>
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

	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td>Posto</td>
		<td>Nome do Posto</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td>
			<input type="text" name="posto_codigo" size="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.forms[0].posto_codigo, document.forms[0].posto_nome, 'codigo');" <? } ?> value="<? echo $posto_codigo ?>" class="frm">
			<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.forms[0].posto_codigo, document.forms[0].posto_nome, 'codigo')">
		</td>
		<td>
			<input type="text" name="posto_nome" size="30" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.forms[0].posto_codigo, document.forms[0].posto_nome, 'nome');" <? } ?> value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.forms[0].posto_codigo, document.forms[0].posto_nome, 'nome')">
		</td>
		<td>&nbsp;</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td colspan="2">
			Linha<br>
			<select name="linha" class="frm">
				<option value=''></option>
				<?
				$sql = "SELECT linha, nome
						FROM tbl_linha
						WHERE fabrica = $login_fabrica
						ORDER BY linha;";
				$res = pg_exec($con,$sql);
				if (pg_numrows($res) > 0) {
					for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
						$x_linha = pg_result($res,$i,linha);
						$x_nome  = pg_result($res,$i,nome);
						echo "<option value='$x_linha'";
						if ($linha == $x_linha) echo " selected";
						echo ">$x_nome</option>";
					}
				}
				?>
			</select>
		</td>
		<td>&nbsp;</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td><input type="radio" name="os_situacao" value="APROVADA" <? if ($os_situacao == "APROVADA") echo "checked"; ?>> OS´s Aprovadas</td>
		<td><input type="radio" name="os_situacao" value="PAGA" <? if ($os_situacao == "PAGA") echo "checked"; ?>> OS´s Pagas</td>
		<td>&nbsp;</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4" align="center"><img src="imagens_admin/btn_pesquisar_400.gif" onClick="javascript: if (document.forms[0].botao.value == '' ) { document.forms[0].botao.value='PESQUISAR'; document.forms[0].submit(); }else{ alert('Aguarde submissão'); }" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</table>

</form>

<?
if (strlen($botao) > 0 && strlen($msg) == 0) {
		$sql =  "SELECT tbl_os.os                                                         ,
						tbl_os.sua_os                                                     ,
						LPAD(tbl_os.sua_os,10,'0')                   AS os_ordem          ,
						TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')  AS digitacao         ,
						TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
						TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento        ,
						tbl_os.serie                                                      ,
						tbl_os.excluida                                                   ,
						tbl_os.motivo_atraso                                              ,
						tbl_os.tipo_os_cortesia                                           ,
						tbl_os.consumidor_revenda                                         ,
						tbl_os.consumidor_nome                                            ,
						tbl_os.revenda_nome                                               ,
						tbl_posto_fabrica.codigo_posto                                    ,
						LPAD(tbl_posto_fabrica.codigo_posto,10,'0') AS posto_ordem        ,
						tbl_posto.nome                              AS posto_nome         ,
						tbl_os_extra.impressa                                             ,
						tbl_os_extra.extrato                                              ,
						tbl_os_extra.os_reincidente                                       ,
						tbl_produto.referencia                      AS produto_referencia ,
						tbl_produto.descricao                       AS produto_descricao  ,
						tbl_produto.voltagem                        AS produto_voltagem   ,
						distrib.codigo_posto                        AS codigo_distrib
				FROM      tbl_os
				JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_os.posto
				JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN      tbl_produto       ON  tbl_produto.produto       = tbl_os.produto
				JOIN      tbl_os_extra      ON  tbl_os_extra.os           = tbl_os.os";
				
		if (strlen($os_situacao) > 0) {
			$sql .= " JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato";
			if ($os_situacao == "PAGA")
				$sql .= " JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato";
		}
		
		$sql .=	" LEFT JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_produto.linha AND tbl_posto_linha.posto = tbl_os.posto
				LEFT JOIN tbl_posto_fabrica distrib ON tbl_os.digitacao_distribuidor = distrib.posto AND distrib.fabrica = $login_fabrica
				WHERE tbl_os.fabrica = $login_fabrica
				AND   tbl_os.excluida IS NOT TRUE";
		
		if (strlen($mes) > 0)           $sql .= " AND tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'";
		
		if (strlen($posto_nome) > 0)    $sql .= " AND tbl_posto.nome ILIKE '%$posto_nome%' ";
		
		if (strlen($codigo_posto) > 0)  $sql .= " AND (tbl_posto_fabrica.codigo_posto ilike '$codigo_posto' OR distrib.codigo_posto = '$codigo_posto')";
		
		if ($os_situacao == "APROVADA") $sql .= " AND tbl_extrato.aprovado IS NOT NULL ";
		
		if ($os_situacao == "PAGA")     $sql .= " AND tbl_extrato_financeiro.data_envio IS NOT NULL ";
		
		if (strlen($revenda_cnpj) > 0)  $sql .= " AND (tbl_os.data_fechamento IS NULL AND tbl_os.consumidor_revenda = 'R' AND tbl_os.revenda_cnpj ILIKE '$revenda_cnpj%') ";

		$sql .= " ORDER BY posto_ordem ASC, os_ordem ASC";

	$res = pg_exec($con,$sql);

	if (getenv("REMOTE_ADDR") == "201.0.9.216") echo nl2br($sql) . "<br>" . pg_numrows($res);

	if (pg_numrows($res) > 0) {
		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			if ($i == 0) {
				echo "<tr class='Titulo' height='15'>";
				echo "<td>&nbsp;</td>";
				echo "<td>OS</td>";
				echo "<td>SÉRIE</td>";
				echo "<td>AB</td>";
				echo "<td>FC</td>";
				echo "<td>POSTO</td>";
				echo "<td>CONSUMIDOR</td>";
				echo "<td>PRODUTO</td>";
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
			$codigo_posto       = trim(pg_result($res,$i,codigo_posto));
			$posto_nome         = trim(pg_result($res,$i,posto_nome));
			$impressa           = trim(pg_result($res,$i,impressa));
			$extrato            = trim(pg_result($res,$i,extrato));
			$os_reincidente     = trim(pg_result($res,$i,os_reincidente));
			$produto_referencia = trim(pg_result($res,$i,produto_referencia));
			$produto_descricao  = trim(pg_result($res,$i,produto_descricao));
			$produto_voltagem   = trim(pg_result($res,$i,produto_voltagem));

			if ($i % 2 == 0) {
				$cor   = "#F1F4FA";
				$botao = "azul";
			}else{
				$cor   = "#F7F5F0";
				$botao = "amarelo";
			}

			if (strlen($sua_os) == 0) $sua_os = $os;
			if ($login_fabrica == 1) $sua_os = $codigo_posto . $sua_os;

			echo "<tr class='Conteudo' height='15' bgcolor='$cor' align='left'>";
			echo "<td nowrap><input type='checkbox' name='os_etiqueta_$i' value='$os'></td>";
			echo "<td nowrap>" . $sua_os . "</td>";
			echo "<td nowrap>" . $serie . "</td>";
			echo "<td nowrap ><acronym title='Data Abertura: $abertura' style='cursor: help;'>" . substr($abertura,0,5) . "</acronym></td>";
			echo "<td nowrap><acronym title='Data Fechamento: $fechamento' style='cursor: help;'>" . substr($fechamento,0,5) . "</acronym></td>";
			echo "<td nowrap><acronym title='Posto: $codigo_posto - $posto_nome' style='cursor: help;'>" . substr($posto_nome,0,15) . "</acronym></td>";
			echo "<td nowrap><acronym title='Consumidor: $consumidor_nome' style='cursor: help;'>" . substr($consumidor_nome,0,15) . "</acronym></td>";
			$produto = $produto_referencia . " - " . $produto_descricao;
			echo "<td nowrap><acronym title='Referência: $produto_referencia \nDescrição: $produto_descricao \nVoltagem: $produto_voltagem' style='cursor: help;'>" . substr($produto,0,20) . "</acronym></td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "<br>";
		echo "<img border='0' src='' onclick=''>";
	}
}

echo "<br>";

include "rodape.php";
?>
