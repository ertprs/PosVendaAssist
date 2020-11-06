<?
# Alterado por Sono em 14/08/2006 - Chamado 442 Help-Desk #

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro";
include "autentica_admin.php";

$posto  = trim ($_GET['posto']);

if (strlen($_POST["btnacao"]) > 0)      $btnacao      = trim(strtolower($_POST["btnacao"]));
if (strlen($_GET["btnacao"])  > 0)      $btnacao      = trim(strtolower($_GET["btnacao"]));

if (strlen($_POST["codigo_posto"]) > 0) $codigo_posto = $_POST["codigo_posto"];
if (strlen($_GET["codigo_posto"])  > 0) $codigo_posto = $_GET["codigo_posto"];

$posto_nome                                           = $_POST['posto_nome'];
if (strlen($_GET['posto_nome']) > 0)    $posto_nome   = $_GET['posto_nome'];

$msg_erro = "";

if(isset($btnacao)) {
	if(!empty($codigo_posto)) {
		$sql="  SELECT * from tbl_posto_fabrica 
				JOIN TBL_POSTO      ON tbl_posto.posto           = tbl_posto_fabrica.posto
				JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto =  tbl_posto_fabrica.tipo_posto
				WHERE codigo_posto              = '$codigo_posto'
				AND tbl_tipo_posto.distribuidor = 't';";

		$res = pg_exec ($con,$sql);
		if( pg_numrows ($res)==0){
			$msg_erro = "Este Não é um Distribuidor";
		}
	}
	else
		$msg_erro = 'Escolha o Posto que deseja Pesquisar';
}

$layout_menu = "financeiro";
$title = "CONSULTA E MANUTENÇÃO DE EXTRATOS DO POSTO";

include "cabecalho.php";

?>
<style>
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
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
	border-collapse: collapse;
	border:1px solid #596d9b;
}
</style>
<? include "javascript_pesquisas.php"; ?>
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/assist.js"></script>
<? if( !empty($msg_erro) ) { ?>
<div class="msg_erro" style="width:700px;margin:auto"><?=$msg_erro?></div>
<? } ?>
<center>
<table class='formulario' width='700' cellspacing='0'  cellpadding='0'  align='center'>
	
	<tr >
		<td class="titulo_tabela" >Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td >
			<TABLE width='100%' align='center' border='0' cellspacing='0' cellpadding='0' class='formulario'>
				<FORM METHOD='GET' NAME='frm_extrato' ACTION="<?=$PHP_SELF?>">
					
					<tr>
						<td style='padding:10px 0 0px 200px;' width='100'>
							Cod. Posto <br />
							<input type="text" name="codigo_posto" size="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_extrato.codigo_posto, document.frm_extrato.posto_nome, 'codigo');" <? } ?> value="<? echo $codigo_posto ?>" class="frm">
							<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_extrato.codigo_posto, document.frm_extrato.posto_nome, 'codigo')"></td>
					
						<td style='padding:10px 0 0px 0px;'>
							Nome do Posto <br />
							<input type="text" name="posto_nome" size="30" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_extrato.codigo_posto, document.frm_extrato.posto_nome, 'nome');" <? } ?> value="<?echo $posto_nome?>" class="frm">
							<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_extrato.codigo_posto, document.frm_extrato.posto_nome, 'nome')">
						</td>
					</tr>
					<tr><td colspan='2' bgcolor="#D9E2EF">&nbsp;<? if($msg)echo "<center><FONT COLOR='#FF0000'><b>".$msg."</b></font></center>";?></td></tr>
					<tr><td colspan='2' bgcolor="#D9E2EF" align='center'><INPUT TYPE="submit" name='btnacao'value="Pesquisar" ></td></tr>
				</form>
			</TABLE>
		</td>
	</tr>
	<tr><td bgcolor='#D9E2EF'>

<?
if ( (strlen ($codigo_posto) > 0 OR strlen ($posto)>0) && empty($msg_erro) ) {

	echo "&nbsp;</td></tr>";
	echo "<tr><td bgcolor='#D9E2EF'>";
	
	if(strlen ($posto)==0){

		$sql = "SELECT * FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto =  tbl_posto.posto
				WHERE tbl_posto_fabrica.codigo_posto = '$codigo_posto'
				AND fabrica = $login_fabrica";
		$res = pg_exec ($con,$sql);
		$posto = trim(pg_result($res,0,posto));
	}
	
	$periodo = trim($_POST['periodo']);
	if (strlen ($periodo) == 0) $periodo = trim ($_GET['periodo']);

	if (strlen ($periodo) == 0) {

		$sql = "SELECT  DISTINCT
						date_trunc('day',tbl_extrato.data_geracao)      AS data_extrato,
						to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data        ,
						to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD') AS periodo
				FROM    tbl_extrato
				JOIN    tbl_posto_linha ON tbl_extrato.posto = tbl_posto_linha.posto
				WHERE   (tbl_posto_linha.distribuidor = $posto OR tbl_extrato.posto = $posto)
				AND     tbl_extrato.fabrica = $login_fabrica
				AND     tbl_extrato.aprovado IS NOT NULL
				AND     tbl_extrato.data_geracao >= '2005-03-30'
				ORDER   BY  to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD') DESC";

		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<center>Data do Extrato&nbsp;";
			echo "<form name='frm_periodo' method='post' action='$PHP_SELF'>";
			echo "<INPUT TYPE='hidden' name='codigo_posto' value='$codigo_posto'>";
			echo "<select name='periodo' onchange='javascript:frm_periodo.submit()'";if ($msg)echo "disabled"; echo ">\n";
			echo "<option value=''></option>\n";
			
			for ($x = 0 ; $x < pg_numrows($res) ; $x++){
				$aux_data  = trim(pg_result($res,$x,data));
				$aux_extr  = trim(pg_result($res,$x,data_extrato));
				$aux_peri  = trim(pg_result($res,$x,periodo));
				
				echo "<option value='$aux_peri' "; if ($periodo == $aux_peri) echo " SELECTED "; echo ">$aux_data</option>\n";
			}
			
			echo "</select>\n";
			echo "</form>";
		}
		else{
			echo "</table> <br />";
			echo "<center>Não foram encontrados resultados para esta pesquisa!</center>";
		}
	}else{
		$periodo_inicial = $periodo . " 00:00:00";
		$periodo_final   = $periodo . " 23:59:59";

		$sql = "SELECT  tbl_linha.nome                        AS linha_nome  ,
					tbl_os_extra.mao_de_obra              AS unitario    ,
					COUNT(*) AS qtde                                     ,
					ROUND (SUM (tbl_os_extra.mao_de_obra)::numeric,2)           AS mao_de_obra_posto     ,
					ROUND (SUM (tbl_os_extra.mao_de_obra_adicional)::numeric,2) AS mao_de_obra_adicional ,
					ROUND (SUM (tbl_os_extra.adicional_pecas)::numeric,2)       AS adicional_pecas
				FROM tbl_os_extra
				JOIN tbl_os USING (os)
				JOIN tbl_linha ON tbl_os_extra.linha = tbl_linha.linha
				JOIN tbl_posto_linha ON tbl_os_extra.linha = tbl_posto_linha.linha AND tbl_os.posto = tbl_posto_linha.posto
				WHERE tbl_os_extra.extrato IN (
					SELECT extrato FROM tbl_extrato 
					WHERE fabrica = $login_fabrica
					AND tbl_extrato.data_geracao BETWEEN '$periodo_inicial' AND '$periodo_final'
				)
				AND (tbl_os.posto = $posto OR tbl_posto_linha.distribuidor = $posto)
				GROUP BY tbl_linha.nome, tbl_os_extra.mao_de_obra
				ORDER BY tbl_linha.nome";

		
		$Xsql = "SELECT	tbl_linha.nome AS linha_nome              ,
						tbl_os_extra.mao_de_obra AS unitario      ,
						COUNT (*) AS qtde                         ,
						SUM (tbl_os_extra.mao_de_obra)           AS mao_de_obra_posto ,
						SUM (tbl_os_extra.mao_de_obra_adicional) AS mao_de_obra_adicional ,
						SUM (tbl_os_extra.adicional_pecas)       AS adicional_pecas ,
							";

	#if ($_SERVER['REMOTE_ADDR'] == '201.0.9.216') { echo $sql ; flush(); exit; } ;
	#echo $sql;
	flush();
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0) {
		$x_periodo = substr ($periodo,8,2) . "/" . substr ($periodo,5,2) . "/" . substr ($periodo,0,4) ;
		echo $x_periodo;

		echo "<table width='700' align='center' border='0' cellspacing='1' class='tabela'>";
		echo "<tr class='titulo_coluna' >";
		echo "<td align='center'>Linha</td>";
		echo "<td align='center'>M.O.Unit.</td>";
		echo "<td align='center'>Qtde</td>";
		echo "<td align='center'>M.O.Postos</td>";
		echo "<td align='center'>M.O.Adicional</td>";
		if ($login_posto == 4311) {
			echo "<td align='center'>Adicional Peças</td>";
		}
		echo "</tr>";

		$total_qtde            = 0 ;
		$total_mo_posto        = 0 ;
		$total_mo_adicional    = 0 ;
		$total_adicional_pecas = 0 ;

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {

			$cor = "#F1F4FA";
			if ($i % 2 == 0) $cor = "#F7F5F0";

			echo "<tr bgcolor='$cor'>";

			echo "<td>";
			echo pg_result ($res,$i,linha_nome);
			echo "</td>";

			echo "<td align='right'>";
			echo number_format (pg_result ($res,$i,unitario),2,',','.');
			echo "</td>";

			echo "<td align='right'>";
			echo number_format (pg_result ($res,$i,qtde),0,',','.');
			echo "</td>";

			echo "<td align='right'>";
			echo number_format (pg_result ($res,$i,mao_de_obra_posto),2,',','.');
			echo "</td>";

			echo "<td align='right'>";
			echo number_format (pg_result ($res,$i,mao_de_obra_adicional),2,',','.');
			echo "</td>";

			if ($login_posto == 4311) {
				echo "<td align='right'>";
				echo number_format (pg_result ($res,$i,adicional_pecas),2,',','.');
				echo "</td>";
			}


			echo "</tr>";

			$total_qtde            += pg_result ($res,$i,qtde) ;
			$total_mo_posto        += pg_result ($res,$i,mao_de_obra_posto) ;
			$total_mo_adicional    += pg_result ($res,$i,mao_de_obra_adicional) ;
			$total_adicional_pecas += pg_result ($res,$i,adicional_pecas) ;

		}

		echo "<tr bgcolor='#FF9900' style='font-size:14px ; color:#ffffff ; font-weight:bold ' >";
		echo "<td align='center'>TOTAIS</td>";
		echo "<td align='center'></td>";
		echo "<td align='right'>" . number_format ($total_qtde           ,0,",",".") . "</td>";
		echo "<td align='right'>" . number_format ($total_mo_posto       ,2,",",".") . "</td>";
		echo "<td align='right'>" . number_format ($total_mo_adicional   ,2,",",".") . "</td>";
		if ($login_posto == 4311) {
			echo "<td align='right'>" . number_format ($total_adicional_pecas,2,",",".") . "</td>";
		}
		echo "</tr>";

		echo "</table>";

		echo "<p align='center'>";
	#	echo "<font style='font-size:12px'>Enviar Nota Fiscal de Prestação de Serviços para o fabricante<br>no valor de <b>R$ " . trim (number_format ($total_mo_posto + $total_mo_adicional + $total_adicional_pecas,2,",",".")) . "</b> descontados os tributos na forma da Lei.";
		}
		else{
			echo "<center>Não foram encontrados resultados para esta pesquisa!</center>";
		}
	}
	
	if (strlen ($periodo) > 0) {
		echo "<p>";
		echo "<a href='extrato_distribuidor_posto.php?data=$periodo&posto=$posto'>Ver extratos dos postos</a>";

		echo "<p>";
		echo "<a href='extrato_distribuidor_retornaveis.php?data=$periodo&posto=$posto'>Peças Retornáveis</a>";

		echo "<p>";
		echo "<a href='extrato_distribuidor.php'>Outro extrato</a>";

		if ($login_posto == 595) {
			echo "<p>";
			echo "<a href='extrato_distribuidor_pecas_estoque.php?data=$periodo&posto=$posto'>Peças do Estoque</a>";
		}

		if ($login_posto == 4311) {
			echo "<p>";
			echo "<a href='extrato_distribuidor_adicional_pecas.php?data=$periodo'>Adicional de Peças</a>";
		}

	}
	
}


?>
		</td>
	</tr>
	<tr><td bgcolor='#D9E2EF'>&nbsp;</td></tr>

</table>
<p><p>
<p><p>

<? include "rodape.php"; ?>
