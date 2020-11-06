<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
//include "autentica_usuario_financeiro.php";

if ($login_fabrica <> 1) {
	header ("Location: os_extrato.php");
	exit;
}

$msg_erro = "";

$layout_menu = "os";
$title = "Extratos";

$pendencia= $_GET['pendencia'];
if(strlen($pendencia)>0){
	$sql ="UPDATE tbl_extrato_status set confirmacao_pendente='t'
			WHERE extrato=$pendencia
			and pendente='t'";
	$res = pg_exec($con,$sql);
	$erro = pg_errormessage($con);
	if (strlen($erro) == 0){

		$xsql = "SELECT protocolo from tbl_extrato where extrato=$pendencia";
		$xres = pg_exec($con,$xsql);
		$xprotocolo = pg_result($xres,0,protocolo);

		$remetente    = "Telecontrol <telecontrol@telecontrol.com.br>"; 
		$destinatario = "takashi@telecontrol.com.br"; 
		$assunto      = "Pendência em extrato resolvido"; 
		$mensagem     = "A <BR>Blackedecker<BR><BR>
		Minha pendência do extrato de número $xprotocolo foi resolvida, favor verificar.
<BR><BR>
PA $login_codigo_posto - $login_nome"; 
		$headers="Return-Path: <telecontrol@telecontrol.com.b>\nFrom:".$remetente."\nBcc:suporte@telecontrol.com.br \nContent-type: text/html\n"; 
		
		if ( mail($destinatario,$assunto,$mensagem,$headers) ) {
	
		}else{
			echo "erro";
		}
	}
}

$liberado= $_POST['liberado'];
if (
   ($login_posto == 5080 OR $login_posto == 5258 OR $login_posto == 5074 OR $login_posto == 5367 OR $login_posto == 5449
OR $login_posto == 5252 OR $login_posto == 5312 OR $login_posto == 5053 OR $login_posto == 5239 OR $login_posto == 5137
OR $login_posto == 5242 OR $login_posto == 2312 OR $login_posto == 5328 OR $login_posto == 5214 OR $login_posto == 5077
OR $login_posto == 5082 OR $login_posto == 5447 OR $login_posto == 5342 OR $login_posto == 5184 OR $login_posto == 5335
OR $login_posto == 5097 OR $login_posto == 1254 OR $login_posto == 5311 OR $login_posto == 5162 OR $login_posto == 5348
OR $login_posto == 891 OR $login_posto == 5351 OR $login_posto == 1844 OR $login_posto == 5310 OR $login_posto == 5132
OR $login_posto == 5433 OR $login_posto == 5219 OR $login_posto == 5297 OR $login_posto == 5368 OR $login_posto == 5223
OR $login_posto == 5237 OR $login_posto == 5289 OR $login_posto == 5256 OR $login_posto == 5436 OR $login_posto == 814
OR $login_posto == 5157 OR $login_posto == 5287 OR $login_posto == 5355 OR $login_posto == 5087 OR $login_posto == 580
OR $login_posto == 836 OR $login_posto == 5236 OR $login_posto == 5361 OR $login_posto == 5138 ) AND $liberado == 0
){

	include "comunicados/procedimento.php";

}else{

include "cabecalho.php";

?>

<p>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.Mensagem{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#7192C4;
	font-weight: bold;
}
.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
	}
</style>

<?
/*
if(strlen($msg)>0){
	echo "<table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td bgcolor='FFFFFF' width='60'><img src='imagens/cadeado1.jpg' align='absmiddle'></td><td  class='Mensagem' bgcolor='FFFFFF' align='left'>$msg</td>";
	echo "</tr>";
	echo "</table><br>";
	echo "<a href='os_extrato_senha.php?acao=alterar'>Alterar senha</a>";
	echo "&nbsp;&nbsp;<a href='os_extrato_senha.php?acao=libera'>Liberar tela</a>";
}else{
	echo "<table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td bgcolor='FFFFFF' width='60'><img src='imagens/cadeado2.jpg' align='absmiddle'></td><td  class='Mensagem' bgcolor='FFFFFF' align='left'><a href='os_extrato_senha.php?acao=inserir' >Esta area não está protegida por senha! <br>Para inserir senha para Restrição do Extrato, clique aqui e saiba mais! </a></td>";
	echo "</tr>";
	echo "</table><br>";
}*/

$sql =	"SELECT DISTINCT
				tbl_extrato.extrato                                            ,
				tbl_extrato.protocolo                                          ,
				tbl_extrato.data_geracao                       AS ordem        ,
				TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao ,
				tbl_extrato.mao_de_obra                                        ,
				tbl_extrato.mao_de_obra_postos                                 ,
				tbl_extrato.pecas                                              ,
				tbl_extrato.total                                              ,
				tbl_extrato.aprovado                                           ,
				tbl_extrato.posto                                              ,
				tbl_posto_fabrica.codigo_posto                                 ,
				tbl_posto.nome                                                 ,
				TO_CHAR(tbl_extrato_financeiro.data_envio,'DD/MM/YYYY') AS data_envio ,
				tbl_extrato_status.obs,
				tbl_extrato_status.pendente,
				tbl_extrato_status.confirmacao_pendente
		FROM      tbl_extrato
		JOIN      tbl_posto              ON tbl_posto.posto                = tbl_extrato.posto
		JOIN      tbl_posto_fabrica      ON tbl_posto_fabrica.posto        = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica      = $login_fabrica
		LEFT JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato
		LEFT JOIN tbl_extrato_status     ON tbl_extrato_status.extrato     = tbl_extrato.extrato
		WHERE tbl_extrato.fabrica = $login_fabrica
		AND   tbl_extrato.posto   = $login_posto
		AND   tbl_extrato.aprovado NOTNULL
		GROUP BY tbl_extrato.extrato               ,
				 tbl_extrato.protocolo             ,
				 tbl_extrato.data_geracao          ,
				 tbl_extrato.mao_de_obra           ,
				 tbl_extrato.mao_de_obra_postos    ,
				 tbl_extrato.pecas                 ,
				 tbl_extrato.total                 ,
				 tbl_extrato.aprovado              ,
				 tbl_extrato.posto                 ,
				 tbl_posto_fabrica.codigo_posto    ,
				 tbl_posto.nome                    ,
				 tbl_extrato_financeiro.data_envio ,
				 tbl_extrato_status.obs            ,
				 tbl_extrato_status.pendente,
				 tbl_extrato_status.confirmacao_pendente
		ORDER BY ordem DESC";
//if ($ip == '201.43.11.216') { echo nl2br($sql); exit; }
$res = pg_exec($con,$sql);

// echo nl2br($sql) . "<br>" . pg_numrows($res);

echo "<table width='700' height='16' border='0' cellspacing='0' cellpadding='0' align='center'>";
echo "<tr>";
echo "<td align='center' width='16' bgcolor='#FFE1E1'>&nbsp;</td>";
echo "<td align='left'><font size=1><b>&nbsp; Extrato Avulso</b></font></td>";
echo "</tr>";
echo "</table>";
echo "<br>";

		echo "<h3><center><b>Obs.: Após o envio do extrato ao financeiro, o prazo para pagamento é de aproximadamente 15 dias.</b></center></h3>";

echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
if (pg_numrows($res) > 0) {
	echo "<tr class='menu_top'>\n";
	echo "<td>EXTRATO</td>\n";
	echo "<td>POSTO</td>\n";
	echo "<td>DATA GERAÇÃO</td>\n";
	echo "<td nowrap>ENVIADO AO<br>FINANCEIRO</td>\n";
	echo "<td>TOTAL</td>\n";
	echo "<td>TOTAL + AVULSO</td>\n";
	echo "<td>STATUS</td>\n";
	echo "<td>AÇÕES</td>\n";
	echo "</tr>\n";

	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$xmao_de_obra       = 0;
		$posto              = trim(pg_result($res,$i,posto));
		$posto_codigo       = trim(pg_result($res,$i,codigo_posto));
		$posto_nome         = trim(pg_result($res,$i,nome));
		$extrato            = trim(pg_result($res,$i,extrato));
		$data_geracao       = trim(pg_result($res,$i,data_geracao));
		$mao_de_obra        = trim(pg_result($res,$i,mao_de_obra));
		$mao_de_obra_postos = trim(pg_result($res,$i,mao_de_obra_postos));
		$pecas              = trim(pg_result($res,$i,pecas));
		$extrato            = trim(pg_result($res,$i,extrato));
		$total_avulso       = trim(pg_result($res,$i,total));
		$protocolo          = trim(pg_result($res,$i,protocolo));
		$data_envio         = trim(pg_result($res,$i,data_envio));
		$obs                = trim(pg_result($res,$i,obs));
		$aprovado           = trim(pg_result($res,$i,aprovado));
		$pendente           = trim(pg_result($res,$i,pendente));
		$confirmacao_pendente  = trim(pg_result($res,$i,confirmacao_pendente));


		if (strlen($aprovado) > 0 AND strlen($data_envio) == 0) $status = "Aguardando documentação";
		/*HD 1163*/
		if (strlen($aprovado) > 0 AND strlen($data_envio) == 0 and $pendente=='t' AND $ip=="201.43.142.16" AND $confirmacao_pendente<>'t') $status = "Pendente, vide observação";
		
		if (strlen($aprovado) > 0 AND strlen($data_envio)  > 0 and $pendente=='f' AND $ip=="201.43.142.16" AND $confirmacao_pendente=='f') $status = "Enviado para o financeiro";
		
		# soma valores
		$xmao_de_obra += $mao_de_obra_postos;
		$xvrmao_obra   = $mao_de_obra_postos;
		
		if ($xvrmao_obra == 0)  $xvrmao_obra   = $mao_de_obra;
		if ($xmao_de_obra == 0) $xmao_de_obra += $mao_de_obra;
		
		$total = $xmao_de_obra + $pecas;

		if ($i % 2 == 0) {
			$cor = "#F1F4FA";
			$btn = "azul";
		}else{
			$cor = "#F7F5F0";
			$btn = "amarelo";
		}

		##### LANÇAMENTO DE EXTRATO AVULSO - INÍCIO #####
		if (strlen($extrato) > 0) {
			$sql = "SELECT COUNT(*) AS existe
					FROM tbl_extrato_lancamento
					WHERE extrato = $extrato
					AND   posto   = $login_posto
					AND   fabrica = $login_fabrica";
			$res_avulso = pg_exec($con,$sql);
			if (@pg_numrows($res_avulso) > 0) {
				if (@pg_result($res_avulso,0,existe) > 0) $cor = "#FFE1E1";
			}
		}
		##### LANÇAMENTO DE EXTRATO AVULSO - FIM #####

		echo "<tr class='table_line' style='background-color: $cor;'>\n";
		echo "<td align='center'>$protocolo</td>\n";
		echo "<td nowrap><acronym title='POSTO: $posto_codigo\nRAZÃO SOCIAL: $posto_nome' style='cursor: help;'>$posto_codigo - " . substr($posto_nome,0,20) . "</acronym></td>\n";
		echo "<td align='center'>$data_geracao</td>\n";
		echo "<td align='center'>$data_envio</td>\n";
		echo "<td align='right' nowrap> R$ ". number_format($total,2,",",".") ."</td>\n";
		echo "<td align='right' nowrap> R$ ". number_format($total_avulso,2,",",".") ."</td>\n";
		echo "<td align='center' nowrap>$status</td>\n";
		echo "<td><a href='os_extrato_detalhe_print_blackedecker.php?extrato=$extrato','extrato' target='_blank'><img src='imagens/btn_imprimir.gif' ALT=\"Imprimir detalhado\" border='0' style=\"cursor:pointer;\"></a></td>\n";
		echo "</tr>\n";

		if (strlen($obs) > 0) {
			echo "<tr class='table_line' style='background-color: $cor;'>\n";
			echo "<td nowrap colspan='7'><b>OBS.:</b> $obs";
			if($pendente=='t' and $login_fabrica==1  AND $ip=="201.43.142.16" AND $confirmacao_pendente<>'t') echo " | <a href=\"javascript: if (confirm('Confirmar resolução da pendência ?') == true) { window.location='$PHP_SELF?pendencia=$extrato'; }\">Pendência resolvida, clique aqui para confirmar</a>";
	echo "</td>\n";
			echo "</tr>\n";
		}
	}
}else{
	echo "<tr class='table_line'>\n";
	echo "<td align='center'>NENHUM EXTRATO FOI ENCONTRADO</td>\n";
	echo "</tr>\n";
}
echo "</table>\n";

echo "<br>";

include "rodape.php";

}
?>
