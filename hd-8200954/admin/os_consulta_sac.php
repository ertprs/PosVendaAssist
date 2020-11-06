<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

include "funcoes.php";

$admin_privilegios = "gerencia,call_center";
include "autentica_admin.php";
$layout_menu = "call_center";
$title = "Relação de Ordens de Serviços Lançadas Pelo SAC e não visualizadas pelos POSTOS";
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
<?
$sql = "SELECT 
			tbl_os.sua_os                                                     ,
			LPAD(tbl_os.sua_os,20,'0')                   AS ordem             ,
			TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')  AS digitacao         ,
			TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
			TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento        ,
			TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY')      AS finalizada        ,
			tbl_os.serie                                                      ,
			tbl_os.excluida                                                   ,
			tbl_os.motivo_atraso                                              ,
			tbl_os.tipo_os_cortesia                                           ,
			tbl_os.consumidor_revenda                                         ,
			tbl_os.consumidor_nome                                            ,
			tbl_os.revenda_nome                                               ,
			tbl_os.tipo_atendimento                                           ,
			tbl_os.tecnico_nome                                               ,
			tbl_os.admin                                                      ,
			tbl_tipo_atendimento.descricao                                    ,
			tbl_posto_fabrica.codigo_posto                                    ,
			tbl_posto.nome                              AS posto_nome         ,
			tbl_os_extra.impressa                                             ,
			tbl_os_extra.extrato                                              ,
			tbl_os_extra.os_reincidente                                       ,
			tbl_produto.referencia                      AS produto_referencia ,
			tbl_produto.descricao                       AS produto_descricao  ,
			tbl_produto.voltagem                        AS produto_voltagem   		
		FROM      tbl_os
		JOIN      tbl_os_extra      ON  tbl_os_extra.os          = tbl_os.os
		LEFT JOIN tbl_os_status     ON tbl_os_status.os          = tbl_os.os
		JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_os.posto
		JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN      tbl_produto       ON  tbl_produto.produto       = tbl_os.produto
		LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
		WHERE tbl_os.fabrica=$login_fabrica
		and tbl_os_extra.impressa is not null
		AND   tbl_os.excluida IS NOT TRUE
		AND tbl_os.admin IS NOT NULL
		AND  (tbl_os_status.status_os NOT IN (13,15) OR tbl_os_status.status_os IS NULL)
		ORDER BY LPAD(tbl_os.sua_os,20,'0') DESC
		LIMIT 50";
	$res = pg_exec($con,$sql);
?>

<table>
<tr class='Titulo' height='15' >
	<td width='100'>OS</td>
	<td>POSTP</td>
	<td width='150'>SÉRIE</td>
	<td>AB</td>
	<td><acronym title='Data de fechamento registrada pelo sistema' style='cursor:help;'>FC</a></td>
	<td>CONSUMIDOR</td>
	<td>PRODUTO</td>
	<td>ATENDIMENTO</td>
	<td nowrap>TÉCNICO</td>
	<td><img border='0' src='imagens/img_impressora.gif' alt='Imprimir OS'></td>
</tr>
<?
	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
//		$os                 = trim(pg_result($res,$i,os));
		$sua_os             = trim(pg_result($res,$i,sua_os));
		$digitacao          = trim(pg_result($res,$i,digitacao));
		$abertura           = trim(pg_result($res,$i,abertura));
		$fechamento         = trim(pg_result($res,$i,fechamento));
		$finalizada         = trim(pg_result($res,$i,finalizada));
		$serie              = trim(pg_result($res,$i,serie));
//		$excluida           = trim(pg_result($res,$i,excluida));
//		$motivo_atraso      = trim(pg_result($res,$i,motivo_atraso));
//		$tipo_os_cortesia   = trim(pg_result($res,$i,tipo_os_cortesia));
//		$consumidor_revenda = trim(pg_result($res,$i,consumidor_revenda));
		$consumidor_nome    = trim(pg_result($res,$i,consumidor_nome));
//		$revenda_nome       = trim(pg_result($res,$i,revenda_nome));
		$codigo_posto       = trim(pg_result($res,$i,codigo_posto));
		$posto_nome         = trim(pg_result($res,$i,posto_nome));
		$impressa           = trim(pg_result($res,$i,impressa));
//		$extrato            = trim(pg_result($res,$i,extrato));
//		$os_reincidente     = trim(pg_result($res,$i,os_reincidente));
		$produto_referencia = trim(pg_result($res,$i,produto_referencia));
		$produto_descricao  = trim(pg_result($res,$i,produto_descricao));
		$produto_voltagem   = trim(pg_result($res,$i,produto_voltagem));
		$tipo_atendimento   = trim(pg_result($res,$i,tipo_atendimento));
		$tecnico_nome       = trim(pg_result($res,$i,tecnico_nome));
		$nome_atendimento   = trim(pg_result($res,$i,descricao));
		$admin              = trim(pg_result($res,$i,admin));

		if ($login_fabrica == 1) $aux_fechamento = $finalizada;
		else                     $aux_fechamento = $fechamento;

		if ($i % 2 == 0) {
			$cor   = "#F1F4FA";
			$botao = "azul";
		}else{
			$cor   = "#F7F5F0";
			$botao = "amarelo";
		}

		echo "<tr>";
		echo "<tr class='Conteudo' height='15' bgcolor='$cor' align='left'>";
		echo "<td  width='50' nowrap>";
		if ($login_fabrica == 1) echo $xsua_os; else echo $sua_os;
		echo "</td>";
		echo "<td  nowrap>$codigo_posto - $posto_nome</td>";
		echo "<td width='55' nowrap> $serie </td>";
		echo "<td nowrap ><acronym title='Data Abertura: $abertura' style='cursor: help;'>".substr($abertura,0,5)."</acronym></td>";
		echo "<td nowrap><acronym title='Data Fechamento: $aux_fechamento' style='cursor: help;'>".substr($aux_fechamento,0,5) ."</acronym></td>";
		echo "<td width='120' nowrap><acronym title='Consumidor: $consumidor_nome' style='cursor: help;'>".substr($consumidor_nome,0,15)." </acronym></td>";
		echo "<td width='150' nowrap><acronym title='Referência: $produto_referencia \nDescrição: $produto_descricao \nVoltagem: $produto_voltagem' style='cursor: help;'>".substr($produto_descricao,0,20)." </acronym></td>";
		echo "<td nowrap>$tipo_atendimento - $nome_atendimento </td>";
		echo "<td width='90' nowrap><acronym title='Nome do técnico:$tecnico_nome' style='cursor: help;'>".substr($tecnico_nome,0,11)." </acronym></td>";
		echo "<td width='30' align='center'>";
		
		if (strlen($admin) > 0 and $login_fabrica == 19)   echo "<img border='0' src='imagens/img_sac_lorenzetti.gif' alt='OS lançada pelo SAC Lorenzetti'>";
		else if (strlen($impressa) > 0)                       echo "<img border='0' src='imagens/img_ok.gif' alt='OS já foi impressa'>";
		else                                                  echo "<img border='0' src='imagens/img_impressora.gif' alt='Imprimir OS'>";
		
		echo "</td>";
		echo "</tr>";
	}
?>
</table>
</body>
</html>