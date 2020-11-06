<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

if(strlen($_POST['acao'])>0) $acao = $_POST['acao'];
else                         $acao = $_GET['acao'];

$mes          = $_POST['mes'];
$ano          = $_POST['ano'];
$select_peca  = $_POST['select_peca'];
$codigo_posto = $_POST['codigo_posto'];

if($acao=='PESQUISAR'){
	if (strlen($mes) > 0 AND strlen($ano) > 0) {
		$xdata_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$xdata_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
	}else{
		$msg_erro = 'Escolha o filtro Mês/Ano para fazer a pesquisa';
	}

	if (strlen($codigo_posto) > 0) {
		$sqlP = "SELECT tbl_posto.posto
				 FROM tbl_posto_fabrica
				 JOIN tbl_posto USING(posto)
				 WHERE tbl_posto_fabrica.codigo_posto = '$codigo_posto'
				 AND   tbl_posto_fabrica.fabrica      = $login_fabrica";
		$resP = pg_exec($con,$sqlP);

		if(pg_numrows($resP)>0){
			$posto      = pg_result($resP,0,posto);
			$cond_posto = " AND tbl_os.posto = $posto ";
		}
	}

	$cond_peca = " 1=1 ";
	if($select_peca=='sem_peca'){
		$cond_peca = " tmp_os_aberta_sem_peca_$login_admin.os_sem_peca > 0 ";
	}

	if($select_peca=='peca_pendente'){
		$cond_peca = " tmp_os_aberta_peca_pendente_$login_admin.os_peca_pendente > 0 ";
	}

	if($select_peca=='peca_atendida'){
		$cond_peca = " tmp_os_aberta_os_peca_atendida_$login_admin.os_peca_atendida > 0 ";
	}

	if($select_peca=='os_consertada'){
		$cond_peca = " tmp_os_aberta_os_consertada_$login_admin.os_consertada > 0 ";
	}
}

$layout_menu = "auditoria";
$title = "Relatorio de OS Abertas";
include "cabecalho.php";

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", 
"Dezembro");

?>

<style type="text/css">
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
}
</style>

<? include "javascript_pesquisas.php"; ?>

<?
if(strlen($msg_erro)>0){
	echo "<div class='msg_erro'>$msg_erro</div>";
}
?>

<BR>
<FORM METHOD="POST" NAME="frm_consulta" ACTION="<? echo $PHP_SELF; ?>">
	<table width="500" align="center" border="0" cellspacing="2" cellpadding="2" class="formulario">
		<tr class="titulo_tabela" height="30">
			<td colspan='3'>Parâmetros de Pesquisa</td>
		</tr>
		<tr>
			<td> &nbsp; </td>
			<td> * Mês</td>
			<td> * Ano</td>
		</tr>
		<tr>
			<td> &nbsp; </td>
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
		</tr>
		<tr>
			<td> &nbsp; </td>
			<td>Posto</td>
			<td>Nome do Posto</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>
				<input type="text" name="codigo_posto" id="codigo_posto" size="8" value="<? echo $codigo_posto ?>" class="frm">
				<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'codigo')">
			</td>
			<td nowrap>
				<input type="text" name="posto_nome" id="posto_nome" size="30" value="<?echo $posto_nome?>" class="frm">
				<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'nome')">
			</td>
		</tr>
		<tr>
			<td> &nbsp; </td>
			<td>Motivo</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td colspan='2'>
				<SELECT NAME="select_peca">
					<OPTION VALUE=""></OPTION>
					<OPTION VALUE="sem_peca" <? if($select_peca=='sem_peca') echo 'selected'; ?>>OS sem lançamento de peça</OPTION>
					<OPTION VALUE="peca_pendente" <? if($select_peca=='peca_pendente') echo 'selected'; ?>>OS com lançamento de peça pendente</OPTION>
					<OPTION VALUE="peca_atendida" <? if($select_peca=='peca_atendida') echo 'selected'; ?>>OS com lançamento de peça atendida</OPTION>
					<OPTION VALUE="os_consertada" <? if($select_peca=='os_consertada') echo 'selected'; ?>>OS consertada e não finalizada</OPTION>
				</SELECT>
			</td>
		</tr>
		<tr>
			<td colspan="3" align="center">
			<BR>
				<INPUT TYPE="hidden" NAME="acao">
				<input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" value="&nbsp;" onclick="document.frm_consulta.acao.value='PESQUISAR'; document.frm_consulta.submit();" alt="Preencha as opções e clique aqui para pesquisar">
			</td>
		</tr>
	</table>
</FORM>

<?
// $cond_peca

	if($acao=='PESQUISAR' AND strlen($msg_erro)==0){
		$sql = "SELECT distinct tbl_os.posto, tbl_os.data_conserto, tbl_os.os
				INTO TEMP tmp_os_aberta_$login_admin
				FROM tbl_os
				LEFT JOIN tbl_os_produto USING(os)
				LEFT JOIN tbl_os_item    USING(os_produto)
				WHERE tbl_os.fabrica = $login_fabrica
				AND   tbl_os.finalizada IS NULL
				AND   tbl_os.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final'
				$cond_posto;

				CREATE INDEX tmp_os_aberta_OS_$login_admin ON tmp_os_aberta_$login_admin(os);

				SELECT posto, count(tmp_os_aberta_$login_admin.os) AS os_aberta INTO TEMP tmp_os_aberta_qtde_$login_admin FROM tmp_os_aberta_$login_admin GROUP BY posto;

				CREATE INDEX tmp_os_aberta_qtde_POSTO_$login_admin ON tmp_os_aberta_qtde_$login_admin(posto);

				SELECT posto, count(tmp_os_aberta_$login_admin.os) AS os_sem_peca
				INTO TEMP tmp_os_aberta_sem_peca_$login_admin
				FROM tmp_os_aberta_$login_admin
				LEFT JOIN tbl_os_produto USING(os)
				LEFT JOIN tbl_os_item    USING(os_produto)
				WHERE tbl_os_item.os_item IS NULL
				GROUP BY posto;

				CREATE INDEX tmp_os_aberta_sem_peca_POSTO_$login_admin ON tmp_os_aberta_sem_peca_$login_admin(posto);

				SELECT tmp_os_aberta_$login_admin.posto, count(distinct tmp_os_aberta_$login_admin.os) AS os_peca_pendente
				INTO TEMP tmp_os_aberta_peca_pendente_$login_admin
				FROM tmp_os_aberta_$login_admin
				JOIN tbl_os_produto USING(os)
				JOIN tbl_os_item    USING(os_produto)
				JOIN tbl_pedido     ON tbl_pedido.pedido = tbl_os_item.pedido AND tbl_pedido.fabrica = $login_fabrica
				JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido AND tbl_pedido_item.peca = tbl_os_item.peca
				LEFT JOIN tbl_pedido_item_faturamento_item on tbl_pedido_item.pedido_item = tbl_pedido_item_faturamento_item.pedido_item
				LEFT JOIN tbl_faturamento_item ON tbl_pedido_item_faturamento_item.faturamento_item = tbl_faturamento_item.faturamento_item AND tbl_faturamento_item.peca = tbl_pedido_item.peca
				LEFT JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
				WHERE tbl_faturamento.faturamento IS NULL
				GROUP BY tmp_os_aberta_$login_admin.posto;

				CREATE INDEX tmp_os_aberta_peca_pendente_POSTO_$login_admin ON tmp_os_aberta_peca_pendente_$login_admin(posto);

				SELECT tmp_os_aberta_$login_admin.posto, count(distinct tmp_os_aberta_$login_admin.os) AS os_peca_atendida
				INTO TEMP tmp_os_aberta_os_peca_atendida_$login_admin
				FROM tmp_os_aberta_$login_admin
				JOIN tbl_os_produto USING(os)
				JOIN tbl_os_item    USING(os_produto)
				JOIN tbl_pedido     ON tbl_pedido.pedido = tbl_os_item.pedido AND tbl_pedido.fabrica = $login_fabrica
				JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido AND tbl_pedido_item.peca = tbl_os_item.peca
				JOIN tbl_pedido_item_faturamento_item on tbl_pedido_item.pedido_item = tbl_pedido_item_faturamento_item.pedido_item
				JOIN tbl_faturamento_item ON tbl_pedido_item_faturamento_item.faturamento_item = tbl_faturamento_item.faturamento_item AND tbl_faturamento_item.peca = tbl_pedido_item.peca
				JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
				GROUP BY tmp_os_aberta_$login_admin.posto;

				CREATE INDEX tmp_os_aberta_os_peca_atendida_POSTO_$login_admin ON tmp_os_aberta_os_peca_atendida_$login_admin(posto);

				SELECT posto, count(tmp_os_aberta_$login_admin.os) AS os_consertada
				INTO TEMP tmp_os_aberta_os_consertada_$login_admin
				FROM tmp_os_aberta_$login_admin
				WHERE tmp_os_aberta_$login_admin.data_conserto IS NOT NULL
				GROUP BY posto;

				CREATE INDEX tmp_os_aberta_os_consertada_POSTO_$login_admin ON tmp_os_aberta_os_consertada_$login_admin(posto);

				SELECT  tmp_os_aberta_qtde_$login_admin.posto                                                            ,
						tbl_posto.nome AS nome_posto                                                                     ,
						CASE WHEN length(os_aberta::text)>0 THEN os_aberta ELSE '0' END AS os_aberta                     ,
						CASE WHEN length(os_sem_peca::text)>0 THEN os_sem_peca ELSE '0' END AS os_sem_peca               ,
						CASE WHEN length(os_peca_pendente::text)>0 THEN os_peca_pendente ELSE '0' END AS os_peca_pendente,
						CASE WHEN length(os_peca_atendida::text)>0 THEN os_peca_atendida ELSE '0' END AS os_peca_atendida,
						CASE WHEN length(os_consertada::text)>0 THEN os_consertada ELSE '0' END AS os_consertada
				FROM tmp_os_aberta_qtde_$login_admin
				JOIN tbl_posto USING(posto)
				LEFT JOIN tmp_os_aberta_sem_peca_$login_admin         USING(posto)
				LEFT JOIN tmp_os_aberta_peca_pendente_$login_admin    USING(posto)
				LEFT JOIN tmp_os_aberta_os_peca_atendida_$login_admin USING(posto)
				LEFT JOIN tmp_os_aberta_os_consertada_$login_admin    USING(posto)
				WHERE $cond_peca
				ORDER BY tbl_posto.nome";
		#echo nl2br($sql);
		$res = pg_exec($con,$sql);
		$res_xls = $res;

		if(pg_numrows($res_xls)>0){
			$arquivo_nome     = "relatorio-os-aberta-$login_fabrica-$login_admin.xls";
			$path             = "/www/assist/www/admin/xls/";
			$path_tmp         = "/tmp/";

			$arquivo_completo     = $path.$arquivo_nome;
			$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

			$fp = fopen ($arquivo_completo_tmp,"w");

			fputs ($fp,"<TABLE width='700' border='1' cellpadding='0' cellspacing='0' align='center'>");
			fputs ($fp,"<TR>");
			fputs ($fp,"<TD>Código Posto</TD>");
			fputs ($fp,"<TD>Nome Posto</TD>");
			fputs ($fp,"<TD nowrap>Os aberta</TD>");
			fputs ($fp,"<TD nowrap>Os sem peça</TD>");
			fputs ($fp,"<TD nowrap>Os com peça pendente</TD>");
			fputs ($fp,"<TD nowrap>Os peça atendida</TD>");
			fputs ($fp,"<TD nowrap>Os consertada</TD>");
			fputs ($fp,"</TR>");

			for($i=0; $i<pg_numrows($res_xls); $i++){
				$posto            = pg_result($res_xls,$i,posto);
				$nome_posto       = pg_result($res_xls,$i,nome_posto);
				$os_aberta        = pg_result($res_xls,$i,os_aberta);
				$os_sem_peca      = pg_result($res_xls,$i,os_sem_peca);
				$os_peca_pendente = pg_result($res_xls,$i,os_peca_pendente);
				$os_peca_atendida = pg_result($res_xls,$i,os_peca_atendida);
				$os_consertada    = pg_result($res_xls,$i,os_consertada);

				if(strlen($posto)>0){
					$sqlp = "SELECT tbl_posto_fabrica.codigo_posto
							 FROM tbl_posto_fabrica
							 WHERE tbl_posto_fabrica.fabrica = $login_fabrica
							 AND   tbl_posto_fabrica.posto   = $posto";
					$resp = pg_exec($con, $sqlp);

					if(pg_numrows($resp)>0){
						$codigo_posto = pg_result($resp,0,codigo_posto);
					}
				}

				fputs ($fp,"<TR bgcolor='$cor'>");
				fputs ($fp,"<TD nowrap align='center'>$codigo_posto</TD>");
				fputs ($fp,"<TD nowrap align='left'>$nome_posto</TD>");
				fputs ($fp,"<TD>$os_aberta</TD>");
				fputs ($fp,"<TD>$os_sem_peca</TD>");
				fputs ($fp,"<TD>$os_peca_pendente</TD>");
				fputs ($fp,"<TD>$os_peca_atendida</TD>");
				fputs ($fp,"<TD>$os_consertada</TD>");
				fputs ($fp,"</TR>");
			}
			fputs ($fp,"</TABLE>");
			fclose ($fp);
			echo ` cp $arquivo_completo_tmp $path `;

			echo "<TABLE align='center'>";
			echo "<TR>";
				echo "<TD><p><a href='xls/$arquivo_nome' target='_blank'>Clique aqui para fazer download do relatório em XLS</a></p></TD>";
			echo "</TR>";
			echo "</TABLE>";
		}

		if(pg_numrows($res)>0){
			echo "<TABLE width='700' border='0' cellpadding='2' cellspacing='2' align='center' class='tabela'>";
			echo "<TR class='titulo_tabela'>";
				echo "<TD>Código Posto</TD>";
				echo "<TD>Nome Posto</TD>";
				echo "<TD nowrap>Os aberta</TD>";
				echo "<TD nowrap>Os sem peça</TD>";
				echo "<TD nowrap>Os com peça pendente</TD>";
				echo "<TD nowrap>Os peça atendida</TD>";
				echo "<TD nowrap>Os consertada</TD>";
			echo "</TR>";
			for($x=0; $x<pg_numrows($res); $x++){
				$posto            = pg_result($res,$x,posto);
				$os_aberta        = pg_result($res,$x,os_aberta);
				$os_sem_peca      = pg_result($res,$x,os_sem_peca);
				$os_peca_pendente = pg_result($res,$x,os_peca_pendente);
				$os_peca_atendida = pg_result($res,$x,os_peca_atendida);
				$os_consertada    = pg_result($res,$x,os_consertada);

				if($x%2) $cor = '#E6E6E6'; else $cor = '#FFFFFF';

				if(strlen($posto)>0){
					$sqlp = "SELECT tbl_posto_fabrica.codigo_posto,
									tbl_posto.nome
							 FROM tbl_posto_fabrica
							 JOIN tbl_posto USING(posto)
							 WHERE tbl_posto_fabrica.fabrica = $login_fabrica
							 AND   tbl_posto_fabrica.posto   = $posto";
					$resp = pg_exec($con, $sqlp);

					if(pg_numrows($resp)>0){
						$codigo_posto = pg_result($resp,0,codigo_posto);
						$nome_posto   = pg_result($resp,0,nome);
					}

					echo "<TR bgcolor='$cor'>";
						echo "<TD nowrap align='center'>$codigo_posto</TD>";
						echo "<TD nowrap align='left'>$nome_posto</TD>";
						echo "<TD>$os_aberta</TD>";
						echo "<TD>$os_sem_peca</TD>";
						echo "<TD>$os_peca_pendente</TD>";
						echo "<TD>$os_peca_atendida</TD>";
						echo "<TD>$os_consertada</TD>";
					echo "</TR>";
				}
			}
			echo "</TABLE>";
			echo "<br><br>";
		}else{
			echo "<P align='center'>Nenhum resultado encontrado!</P>";
		}
	}
?>

<? include "rodape.php"; ?>