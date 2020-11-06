<?

########################################
# comecei a fazer, mas parei - Ricardo #
########################################
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if ($login_fabrica <> 1 OR $extrato) {
	header ("Location: os_extrato.php");
	exit;
}

$msg_erro = "";

$layout_menu = "os";
$title = "Extratos";

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

</style>

<?

	# -- EXIBE VALORES DETALHADOS DOS EXTRATOS -- #
	$sql = "SELECT      tbl_posto_fabrica.codigo_posto                                 ,
						tbl_posto.nome                                                 ,
						tbl_extrato.extrato                                            ,
						to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD') AS data_extrato,
						to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao,
						tbl_extrato.mao_de_obra                                        ,
						tbl_extrato.mao_de_obra_postos                                 ,
						tbl_extrato.protocolo                                          ,
						tbl_extrato.pecas                                              ,
						tbl_posto.estado                                               ,
						tbl_extrato.total                                              ,
						tbl_extrato.aprovado                                           ,
						tbl_os_status.status_os                                        
			FROM        tbl_extrato
			JOIN        tbl_posto_fabrica      ON tbl_extrato.posto              = tbl_posto_fabrica.posto
											  AND tbl_posto_fabrica.fabrica      = $login_fabrica
			JOIN        tbl_posto              ON tbl_extrato.posto              = tbl_posto.posto
			LEFT JOIN   tbl_os_extra           ON tbl_os_extra.extrato           = tbl_extrato.extrato
			LEFT JOIN   tbl_os                 ON tbl_os.os                      = tbl_os_extra.os
			LEFT JOIN   tbl_os_status          ON tbl_os_status.os               = tbl_os.os
			LEFT JOIN   tbl_produto            ON tbl_produto.produto            = tbl_os.produto
			LEFT JOIN   tbl_familia            ON tbl_familia.familia            = tbl_produto.familia
			LEFT JOIN   tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato
			WHERE       tbl_extrato.fabrica = $login_fabrica 
			AND         tbl_extrato.posto   = $login_posto
			AND         tbl_extrato.extrato = $extrato
			AND         tbl_extrato.aprovado NOTNULL
			AND         tbl_extrato_financeiro.data_envio ISNULL";
	$res = pg_exec ($con,$sql);

	echo "<table width='700' height=16 border='0' cellspacing='0' cellpadding='0' align='center'>";
	echo "<tr>";
	echo "<td align='center' width='16' bgcolor='#FFE1E1'>&nbsp;</td>";
	echo "<td align='left'><font size=1><b>&nbsp; Extrato Avulso</b></font></td>";
	echo "</tr>";
	echo "</table>";
	echo "<br>";

	echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
	if (pg_numrows($res) > 0) {
		echo "<form method=post name=frm_extrato action=\"$PHP_SELF\">";
		
		echo "<tr class='menu_top'>\n";
		
		echo "<td align='center'>EXTRATO Nº</td>\n";
		echo "<td align='center'>POSTO</td>\n";
		echo "<td align='center'>DATA GERAÇÃO</td>\n";
		//echo "<td align='center'>MO</td>\n";
		//echo "<td align='center'>PEÇAS</td>\n";
		echo "<td align='center'>TOTAL</td>\n";
		echo "<td align='center'>TOTAL + AVULSO</td>\n";
		echo "<td align='center'>STATUS</td>\n";
		echo "<td align='center' colspan='2'>AÇÕES</td>\n";
		
		echo "</tr>\n";
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
			$xmao_de_obra            = 0;
			$posto                   = trim(pg_result($res,$i,posto));
			$posto_codigo            = trim(pg_result($res,$i,codigo_posto));
			$posto_nome              = trim(pg_result($res,$i,nome));
			$extrato                 = trim(pg_result($res,$i,extrato));
			$data_geracao            = trim(pg_result($res,$i,data_geracao));
			$pedido_via_distribuidor = trim(pg_result($res,$i,pedido_via_distribuidor));
			$data_extrato            = trim(pg_result($res,$i,data_extrato));
			$mao_de_obra             = trim(pg_result($res,$i,mao_de_obra));
			$mao_de_obra_postos      = trim(pg_result($res,$i,mao_de_obra_postos));
			$adicional               = trim(pg_result($res,$i,adicional));
			$pecas                   = trim(pg_result($res,$i,pecas));
			$extrato                 = trim(pg_result($res,$i,extrato));
			$estado                  = trim(pg_result($res,$i,estado));
			$total_avulso            = trim(pg_result($res,$i,total));
			$protocolo               = trim(pg_result($res,$i,protocolo));
			$data_envio              = trim(pg_result($res,$i,data_envio));
			$status_os               = trim(pg_result($res,$i,status_os));
			$aprovado                = trim(pg_result($res,$i,aprovado));
			
			if (strlen($aprovado) > 0 AND strlen($data_envio) == 0 AND strlen($status_os) == 0) $status = "Aguardando documentação";
			if (strlen($aprovado) > 0 AND strlen($data_envio)  > 0 AND strlen($status_os) == 0) $status = "Enviado para o financeiro";
			if (strlen($aprovado) > 0 AND strlen($data_envio) == 0 AND strlen($status_os)  > 0) $status = "Pendente";
			
			if (strlen($adicional) == 0) $adicional = 0;
			
			# soma valores
			$xmao_de_obra += $mao_de_obra_postos;
			$xvrmao_obra   = $mao_de_obra_postos;
			
			if ($xvrmao_obra == 0)  $xvrmao_obra   = $mao_de_obra;
			if ($xmao_de_obra == 0) $xmao_de_obra += $mao_de_obra;
			
			$total = $xmao_de_obra + $pecas;
			
			$data_geracao;
			
			//////////////////////////////////////////////
			$cor = "#F7F5F0"; 
			$btn = 'amarelo';
			if ($i % 2 == 0) 
			{
				$cor = '#F1F4FA';
				$btn = 'azul';
			}

			##### LANÇAMENTO DE EXTRATO AVULSO - INÍCIO #####
			if (strlen($extrato) > 0) {
				$sql = "SELECT count(*) as existe
						FROM   tbl_extrato_lancamento
						WHERE  extrato = $extrato
						and    posto   = $login_posto
						and    fabrica = $login_fabrica";
				$res_avulso = pg_exec($con,$sql);
				
				if (@pg_numrows($res_avulso) > 0) {
					if (@pg_result($res_avulso, 0, existe) > 0) $cor = "#FFE1E1";
				}
			}
			##### LANÇAMENTO DE EXTRATO AVULSO - FIM #####

			echo "<tr class='table_line' style='background-color: $cor;'>\n";
			
			echo "<td align='left' style='padding-left:7px;padding-right:7px;'>";
			echo $protocolo;
			echo "</td>\n";
			
			echo "<td align='left' nowrap><acronym title='$posto_codigo - $posto_nome'>$posto_codigo - " . substr($posto_nome,0,20) . "</acronym></td>\n";
			echo "<td align='center'>$data_geracao</td>\n";
			//echo "<td align='right'  style='padding-right:3px;' nowrap> R$ ". number_format($xvrmao_obra,2,",",".") ."</td>\n";
			//echo "<td align='right'  style='padding-right:3px;' nowrap> R$ ". number_format($pecas,2,",",".") ."</td>\n";
			echo "<td align='right' style='padding-right:3px;' nowrap> R$ ". number_format($total,2,",",".") ."</td>\n";
			echo "<td align='right' style='padding-right:3px;' nowrap> R$ ". number_format($total_avulso,2,",",".") ."</td>\n";
			echo "<td align='right' style='padding-right:3px;' nowrap> $status</td>\n";
			echo "<td><img src='imagens/btn_imprimir.gif' onclick=\"javascript: janela=window.open('os_extrato_detalhe_print_blackedecker.php?extrato=$extrato','extrato');\" ALT=\"Imprimir detalhado\" border='0' style=\"cursor:pointer;\"></td>\n";
			echo "<td><a href='os_extrato_nf.php?extrato=$extrato'><img src='imagens/btn_notafiscal.gif' ALT=\"Cadastrar os dados da nota fiscal de prestação de serviços.\" border='0' style=\"cursor:pointer;\"></a></td>\n";
			echo "</tr>\n";
		}
		echo "<input type='hidden' name='total' value='$i'>";
		
		echo "</form>";
	}else{
		echo "<tr class='table_line'>\n";
		echo "<td align=\"center\">NENHUM EXTRATO FOI ENCONTRADO</td>\n";
		echo "</tr>\n";
	}
	
	echo "</table>\n";

?>

<p><p>

<? include "rodape.php"; ?>
