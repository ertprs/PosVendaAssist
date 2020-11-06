<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "funcoes.php";

$erro     = "";
$erro_data= "";
$erro_os  = "";

if (strlen($_POST["acao"]) > 0 ) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET["acao"]) > 0 )  $acao = strtoupper($_GET["acao"]);
if (strlen(trim($_POST["sua_os"])) > 0) $x_sua_os = trim($_POST['sua_os']);
if (strlen(trim($_GET["sua_os"])) > 0)  $x_sua_os = trim($_POST['sua_os']);

if (strlen($acao) > 0 && $acao == "PESQUISAR") {
	if (strlen(trim($_POST["data_inicial"])) > 0) $x_data_inicial = trim($_POST["data_inicial"]);
	if (strlen(trim($_GET["data_inicial"])) > 0)  $x_data_inicial = trim($_GET["data_inicial"]);
	
	$aux_data_inicial = str_replace("/","",$x_data_inicial);
	$aux_data_inicial = str_replace("-","",$aux_data_inicial);
	$aux_data_inicial = str_replace(".","",$aux_data_inicial);
	$aux_data_inicial = fnc_so_numeros($aux_data_inicial);

	if (strlen($aux_data_inicial) < 8){
		if($sistema_lingua == "ES") $erro_data .= "Fecha inicial en formato invalido";
		else                        $erro_data .= "Formato da data inicial inválido";
	}
	
	if (strlen($erro_data) == 0){
		$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
		
		if (strlen(trim($_POST["data_final"])) > 0) $x_data_final   = trim($_POST["data_final"]);
		if (strlen(trim($_GET["data_final"])) > 0) $x_data_final = trim($_GET["data_final"]);
		
		$x_data_final   = fnc_formata_data_pg($x_data_final);
		
		if (strlen($x_data_inicial) > 0 && $x_data_inicial != "null") {
			$x_data_inicial = str_replace("'", "", $x_data_inicial);
			$dia_inicial = substr($x_data_inicial, 8, 2);
			$mes_inicial = substr($x_data_inicial, 5, 2);
			$ano_inicial = substr($x_data_inicial, 0, 4);
			$data_inicial = $dia_inicial . "/" . $mes_inicial . "/" . $ano_inicial;
		}else{
			if($sistema_lingua == "ES") $erro_data .= "Informe la fecha inicial para realizar la busca ";
			else                        $erro_data .= " Informe a Data Inicial para realizar a pesquisa."; 
			echo "data: $x_data_final";
		}
		if (strlen($x_data_final) > 0 && $x_data_final != "null") {

			$aux_data_final = str_replace("/","",$x_data_final);
			$aux_data_final = str_replace("-","",$aux_data_final);
			$aux_data_final = str_replace(".","",$aux_data_final);
			$aux_data_final = fnc_so_numeros($aux_data_final);
			
			if (strlen($aux_data_final) < 8) {
				if($sistema_lingua == "ES") $erro_data .= "Fecha cierre en formato invalido";
				else                        $erro_data .= "Data final em formato inválida";
			}

			if (strlen($erro_data) == 0){
				$x_data_final = str_replace("'", "", $x_data_final);
				$dia_final = substr($x_data_final, 8, 2);
				$mes_final = substr($x_data_final, 5, 2);
				$ano_final = substr($x_data_final, 0, 4);
				$data_final = $dia_final . "/" . $mes_final . "/" . $ano_final;
			}
		}else{

			if($sistema_lingua == "ES") $erro_data .= "Informe la fecha inicial para realizar la busca ";
			else                        $erro_data .= " Informe a Data Inicial para realizar a pesquisa."; 
		}
		if (strlen(trim($_POST["status"])) > 0) $status = trim($_POST["status"]);
		if (strlen(trim($_GET["status"])) > 0)  $status = trim($_GET["status"]);
		
		$link_status = "http://" . $HTTP_HOST . $REQUEST_URI . "?data_inicial=" . $_POST["data_inicial"] . "&data_final=" . $_POST["data_final"] . "&acao=PESQUISAR";
		setcookie("LinkStatus", $link_status);
	}

	//PARA A BLACK E DECKER NÃO É FEITO CONSULTA POR OS, ENTÃO NAO APARECE ESSA MENSAGEM
	if(strlen($x_sua_os) == 0 and $login_fabrica <> 1) {
		 $erro_os .= "Digite o Número da OS ";
	}

	if(strlen($erro_data) > 0 and strlen($erro_os) > 0) {
		if(strlen($erro_data) > 0) $erro =$erro_data ."<BR>";	
		if(strlen($erro_os)   > 0) $erro .=$erro_os;	
	}else{
		if($login_fabrica == 1 and strlen($erro_data) >0){
			$erro ="Informe a Data Inicial para realizar a pesquisa.";
		}	
	}
}

$layout_menu = "os";
$title = "Relação de Status da Ordem de Serviço";
if($sistema_lingua=='ES') $title = "Relación de status de órdenes de servicio";
include "cabecalho.php";


#--------- TULIO 19/04 - Acertar SQL , Restringir a no maximo 1 mes - Colocar mais parametros para restringir
// somente Fabiola
//if ($ip <> '12.148.189.25' AND $ip <> '201.0.9.216'){
//	echo "<h1>Programa em Manutenção</h1>";
//	exit;
//}
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12 px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12 px;
	font-weight: normal;
}
</style>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script type="text/javascript">
function MostraEsconde(dados)
{
	if (document.getElementById)
	{
		var style2 = document.getElementById(dados);
		if (style2==false) return;
		if (style2.style.display=="block"){
			style2.style.display = "none";
			}
		else{
			style2.style.display = "block";
		}
	}
}

function Limpa_OS_ou_Data(dados){
	if(dados == "data"){
		document.getElementById("sua_os").value ="";
	}else{
		document.getElementById("data_inicial").value="";
		document.getElementById("data_final").value="";
	}
}


</script>

<br>

<? if (strlen($erro) > 0) { ?>
<table width="600" border="0" cellspacing="0" cellpadding="2" align="center" class="Error">
	<tr>
		<td><?echo $erro?></td>
	</tr>
</table>
<br>
<? } ?>

<form name="frm_relatorio" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="acao">
<table width="400" border="0" cellspacing="0" cellpadding="2" align="center">
	<TR >
	<TD colspan="4" align='center'><b><FONT COLOR = 'BLUE'><? if($sistema_lingua == 'ES') echo "CONSULTE ENTRE FECHAS OU POR OS";else echo "PESQUISE ENTRE DATAS OU POR OS";?></FONT></b></TD>
	</TR>
	<tr class="Titulo">
		<td colspan="4"><b><? if($sistema_lingua == 'ES') echo "CONSULTE ENTRE FECHAS";else echo "PESQUISE ENTRE DATAS";?></b></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td align='left'><? if($sistema_lingua == 'ES') echo "Fecha Inicial";else echo "Data Inicial";?></td>
		<td align='left'><? if($sistema_lingua == 'ES') echo "Fecha Final";else echo "Data Final";?></td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td>
			<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';  Limpa_OS_ou_Data('data'); "  onfocus="javascript: if (this.value == 'dd/mm/aaaa') this.value='';  Limpa_OS_ou_Data('data'); ">
			&nbsp;
			<? if ($sistema_lingua=='ES') { ?>
				<img border="0" src="imagens/btn_lupa.gif" align="absmiddle" onclick="javascript:showCal('DataInicial')" style="cursor: hand;" alt="Haga um click aquí para abrir el calendario">
			<? } else { ?>
				<img border="0" src="imagens/btn_lupa.gif" align="absmiddle" onclick="javascript:showCal('DataInicial')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
			<? } ?>
		</td>
		<td>
			<input type="text" name="data_final" id="data_final" size="12" maxlength="10" value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value=''; Limpa_OS_ou_Data('data');" onfocus="javascript: if (this.value == 'dd/mm/aaaa') this.value=''; Limpa_OS_ou_Data('data');" >
			&nbsp;
			<? if ($sistema_lingua=='ES') { ?>
				<img border="0" src="imagens/btn_lupa.gif" align="absmiddle" onclick="javascript:showCal('DataFinal')" style="cursor: hand;" alt="Haga um click aquí para abrir el calendario">
			<? } else { ?>
				<img border="0" src="imagens/btn_lupa.gif" align="absmiddle" onclick="javascript:showCal('DataFinal')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
			<? } ?>
		</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" height='20' valign='middle'>
		<td colspan='4' align='center'>
			<select name="status" size="1" class="frm">
			<option <?if ($status == "00") echo " selected ";?> value='00'><? if($sistema_lingua == 'ES') echo "Todas";else echo "Todas";?></option>
<option <?if ($status == "14") echo " selected ";?> value='14'><? if($sistema_lingua == 'ES') echo "Acumuladas";else echo "Acumuladas";?></option>			
<option <?if ($status == "01") echo " selected ";?> value='01'><? if($sistema_lingua == 'ES') echo "Aprobadas";else echo "Aprovadas";?></option>
<option <?if ($status == "15") echo " selected ";?> value='15'><? if($sistema_lingua == 'ES') echo "Excluídas";else echo "Excluídas";?></option>			
<option <?if ($status == "99") echo " selected ";?> value='99'><? if($sistema_lingua == 'ES') echo "Cerradas";else echo "Finalizadas";?></option>
<option <?if ($status == "13") echo " selected ";?> value='13'><? if($sistema_lingua == 'ES') echo "Rechazadas";else echo "Recusadas";?></option>
			
			
			</select>
		</td>
	</tr>
<? if ($login_fabrica !=1) {?> 
	<tr class="Titulo">
		<td colspan="4"><b><? if($sistema_lingua == 'ES') echo "CONSULTE POR N° DE OS";else echo "PESQUISE POR N° DE OS";?></b></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td align='center' colspan="4"><? if($sistema_lingua == 'ES') echo "N° DE OS";else echo "N° DE OS";?></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td colspan="4" align ="center">
			<input type="text" name="sua_os" id="sua_os" size="12" onclick="Limpa_OS_ou_Data('sua_os');" onfocus="Limpa_OS_ou_Data('sua_os');"maxlength="20" value="<? if (strlen($x_sua_os) > 0) echo $x_sua_os; else echo ""; ?>">
		</td>
	</tr>
<? }else{
	echo "	<input type='hidden' name='sua_os' id='sua_os'>";
} ?>
	<tr bgcolor="#D9E2EF">
		<td colspan="4"><img border="0" src="<?if($sistema_lingua=='ES')echo "admin_es/";?>imagens/btn_pesquisar_400.gif" onClick="document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor: hand;" alt="<?if($sistema_lingua =='ES') echo "Llene las opciones e click aquí para buscar";else echo "Preencha as opções e clique aqui para pesquisar";?>"></td>
	</tr>
</table>

<br>

<?
if (strlen($acao) > 0 && strlen($erro) == 0) {

	//SOMENTE OSs QUE NÃO ESTÃO EXCLUIDAS
	if ($status <> "15") {
		$sql = "SELECT *  FROM (
		
				SELECT  DISTINCT
						tbl_posto_fabrica.codigo_posto                                    ,
						tbl_os.os                                                         ,
						tbl_os.sua_os                                                     ,
						TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')    AS data_digitacao  ,
						TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')     AS data_abertura   ,
						TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY')   AS data_fechamento ,
						TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY')        AS finalizada      ,
						tbl_os.pecas                                                      ,
						tbl_os.mao_de_obra                                                ,
						tbl_os.admin                                                      ,
						tbl_extrato.extrato                                               ,
						tbl_extrato_extra.exportado                                       ,
						TO_CHAR(tbl_extrato.aprovado,'DD/MM/YYYY')     AS aprovado        ,
						TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao    ,
						(
							select tbl_os_status.status_os 
							from tbl_os_status 
							where tbl_os_status.os = tbl_os.os 
							order by data desc limit 1
						)                                              AS status_os       ,
						(
							select tbl_os_status.observacao 
							from tbl_os_status 
							where tbl_os_status.os = tbl_os.os 
							order by data desc limit 1
						)                                              AS observacao
				FROM  tbl_os 
				LEFT JOIN  tbl_os_extra          ON tbl_os_extra.os           = tbl_os.os
				LEFT JOIN  tbl_extrato           ON tbl_extrato.extrato       = tbl_os_extra.extrato
				LEFT JOIN  tbl_extrato_extra     ON tbl_extrato_extra.extrato = tbl_extrato.extrato
				JOIN  tbl_posto_fabrica          ON tbl_posto_fabrica.posto   = tbl_os.posto
												AND tbl_posto_fabrica.fabrica = tbl_os.fabrica";
			if(strlen($erro_data) == 0 and strlen($erro_os) == 0) {	
				$sql .=" WHERE tbl_os.data_digitacao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
			}
			else {	
				if(strlen($erro_data) == 0 ) {
					$sql .=" WHERE tbl_os.data_digitacao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59' ";
				}else{
					if(strlen($erro_os) == 0) {
					$sql .=" WHERE tbl_os.sua_os = '$x_sua_os' ";
					}
				}
			}
				$sql .=" AND tbl_os.posto   = $login_posto
						 AND tbl_os.fabrica = $login_fabrica
				GROUP BY tbl_posto_fabrica.codigo_posto    ,
						 tbl_os.os                         ,
						 tbl_os.sua_os                     ,
						 tbl_os.data_digitacao             ,
						 tbl_os.data_abertura              ,
						 tbl_os.data_fechamento            ,
						 tbl_os.finalizada                 ,
						 tbl_os.pecas                      ,
						 tbl_os.mao_de_obra                ,
						 tbl_os.admin                      ,
						 tbl_extrato.extrato               ,
						 tbl_extrato_extra.exportado       ,
						 tbl_extrato.aprovado              ,
						 tbl_extrato.data_geracao) x";
				
		//TODAS
		if ($status == "99") {
			$sql.= " WHERE data_fechamento NOTNULL
					 AND aprovado IS NULL 
					 AND extrato IS NULL";
		}

		//TODAS
		if ($status == "00") {
			$sql.= " WHERE data_fechamento NOTNULL ";
		}
		
		//APROVADA
		if ($status == "01") {
			if ($login_fabrica == 19) {
				$sql.= " WHERE status_os <> 13
						 AND aprovado NOTNULL 
						 AND data_fechamento NOTNULL ";
			}else{
				$sql.= " WHERE aprovado NOTNULL 
						 AND extrato NOTNULL 
						 AND data_fechamento NOTNULL ";
			}
		}
		
		//PESQUISA POR RECUSADAS
		if ($status == "13") {
			if ($login_fabrica == 19)
				$sql.= " WHERE status_os = 13";
			else 
				$sql.= " WHERE status_os = 13 AND data_fechamento IS NULL";
		}
		
		//ACUMULADA
		if ($status == "14") {
			$sql.= " WHERE status_os = 14 
					 AND aprovado IS NULL 
					 AND extrato IS NULL 
					 AND data_fechamento NOTNULL";
		}
		
		//EXCLUIDA
		if ($status == "15") {
			$sql.= " WHERE status_os = 15 
					 AND extrato IS NULL 
					 AND data_fechamento NOTNULL";
		}

		$sql.=" ORDER BY sua_os, status_os;";
echo $sql;
//if ($ip=="201.26.23.85") echo $sql;
		$res = pg_exec($con,$sql);


		if (pg_numrows($res) > 0) {
			echo "<table width='700' border='0' cellspacing='2' cellpadding='0' align='center'>";
			echo "<tr>";
			echo "<td width='20' height='20' bgcolor='#FFE1E1'>&nbsp;</td>";
			echo "<td><font size=1>&nbsp; <b>";
			if($sistema_lingua == 'ES') echo "OS rechazadas por el fabricante";else echo "OS RECUSADA pelo fabricante";
			echo "</b></font></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td colspan='2' height='5'></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td width='20' height='20' bgcolor='#D7FFE1'>&nbsp;</td>";
			echo "<td><font size='1'>&nbsp; <b>";
			if($sistema_lingua == 'ES') echo "OS acumuladas por el fabricante</b>(Click en la línea de la OS p/ realizar la alteración deseada en la <a href='os_parametros.php'>consulta de OS</a>)";else echo "OS ACUMULADA pelo fabricante</b> (Clique na linha da OS p/ realizar a alteração desejada na <a href='os_parametros.php'>Consulta de OS</a>)";
			echo "</font></td>";
			echo "</tr>";
			if($login_fabrica == 15){//chamado 2235
				echo "<tr>";
				echo "<td colspan='2' height='5'></td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td width='20' height='20' bgcolor='#FF9900'>&nbsp;</td>";
				echo "<td><font size='1'>&nbsp; <b>";
				echo "OS Digitada pela Latinatec";
				echo "</font></td>";
				echo "</tr>";
			}
			echo "</table>";
			echo "<br>";
			
			echo "<input type='hidden' name='qtde_os' value='" . pg_numrows($res) . "'>";
			
			echo "<table width='650' border='1' cellpadding='2' cellspacing='0' align='center' style='border-collapse: collapse' bordercolor='#000000'>";
			echo "<tr class='Titulo'>";
			echo "<td colspan='8'>";
			if($sistema_lingua == 'ES') echo "RELACIÓN DE OS";else echo "RELAÇÃO DE OS";
			echo "</td>";
			echo "</tr>";
			echo "<tr class='Titulo'>";
			echo "<td>OS</td>";
			echo "<td>";
			if($sistema_lingua == 'ES') echo "DIGITALIZACIÓN";else echo "DIGITAÇÃO";
			echo "</td>";
			echo "<td>ABERTURA</td>";
			echo "<td>";
			if($sistema_lingua == 'ES') echo "CERRAMIENTO";else echo "FECHAMENTO";
			echo "</td>";
			echo "<td>TOTAL</td>";
			echo "<td>PROTOCOLO</td>";
			echo "<td>STATUS</td>";
			echo "</tr>";
			
			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$codigo_posto    = trim(pg_result($res,$i,codigo_posto));
				$os              = trim(pg_result($res,$i,os));
				$sua_os          = trim(pg_result($res,$i,sua_os));
				$data_digitacao  = trim(pg_result($res,$i,data_digitacao));
				$data_abertura   = trim(pg_result($res,$i,data_abertura));
				$data_fechamento = trim(pg_result($res,$i,data_fechamento));
				$finalizada      = trim(pg_result($res,$i,finalizada));
				$pecas           = trim(pg_result($res,$i,pecas));
				$mao_de_obra     = trim(pg_result($res,$i,mao_de_obra));
				$os_admin        = trim(pg_result($res,$i,admin));
				$total           = $pecas + $mao_de_obra;
				$extrato         = trim(pg_result($res,$i,extrato));
				$exportado         = trim(pg_result($res,$i,exportado));
				$aprovado        = trim(pg_result($res,$i,aprovado));
				$data_geracao    = trim(pg_result($res,$i,data_geracao));
				$status_os       = trim(pg_result($res,$i,status_os));
				$observacao      = trim(pg_result($res,$i,observacao));
				
				$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

				if(strlen($os_admin) >0 AND $login_fabrica == 15) $cor = "#FF9900";

				if ( ($login_fabrica == 19 AND $status_os == 13) OR
					 ($login_fabrica <> 19 AND $status_os == 13 AND strlen(trim($data_fechamento)) == 0) ) {
					$cor = "#FFE1E1";
					$rowspan = "2";
					$rowspan = "1";
				}else{
					$rowspan = "1";
				}
				
				if ($status_os == 14 AND strlen($extrato) == 0) {
					$cor = "#D7FFE1";
				}
				
				echo "<tr class='Conteudo' bgcolor='$cor'";
				if ($status_os == 14 OR $status_os == 13) echo " onclick=\"javascript: window.location='os_consulta_lite.php?btn_acao=PESQUISAR&sua_os=$sua_os';\" style='cursor: hand;'";
				/*if ($status_os == 14 OR $status_os == 13) echo " onclick=\"javascript: window.location='os_consulta_lite.php?acao=PESQUISAR&opcao5=5&numero_os=$sua_os';\" style='cursor: hand;'";*/
				echo ">";
				echo "<td nowrap rowspan='$rowspan'>";
				if ($login_fabrica == 1) echo $codigo_posto;
				echo $sua_os;
				echo "</td>";
				echo "<td nowrap align='center'>" . $data_digitacao . "</td>";
				echo "<td nowrap align='center'>" . $data_abertura . "</td>";
				echo "<td nowrap align='center'><acronym title='";
				if($sistema_lingua == "ES") echo "Fecha de cierre digitada";else echo "Data de fechamento digitada: ";
				echo "$data_fechamento' style='cursor: help;'>" . $finalizada . "</acronym></td>";
				echo "<td nowrap align='right'>" . number_format($total,2,",",".") . "</td>";
				if($login_fabrica==20){
					if ($sistema_lingua=='ES') 	echo "<td nowrap align='center' title='Extracto/ lote que fue paga la OS'>" . $extrato . "</td>";
					else echo "<td nowrap align='center' title='Extrato/Lote que a OS foi paga'>" . $extrato . "</td>";
				}
				else echo "<td nowrap align='center'>" . $os . "</td>";
				echo "<td nowrap align='center'>";

				//VERIFICAR TODOS AS VALIDAÇÕES
				
				/*
				00 Todas
				01 Aprovadas
				13 Recusadas
				14 Acumulada
				15 Excluídas
				*/
				/*
				if (($status == "00") or ($status == "99")) {
					if     (strlen($data_geracao) >  0  AND strlen($aprovado) == 0)                                                      echo "Em aprovação";
					elseif (strlen($data_geracao) == 0  AND strlen($aprovado) == 0 AND strlen($status_os) == 0 AND strlen(trim($data_fechamento)) <> 0)echo "Finalizada";
					elseif ($login_fabrica == 19 AND $status_os <> 13 AND strlen($aprovado) > 0)                                         echo "Aprovada";
					elseif ($login_fabrica == 20 AND strlen($aprovado) > 0 AND strlen($extrato) > 0 AND strlen($exportado)>0)            echo "Pagamento efetuado";
					elseif ($login_fabrica <> 19 AND $login_fabrica <> 1 AND strlen($aprovado) > 0 AND strlen($extrato) > 0)             echo "Aprovada";
					elseif ($login_fabrica == 1 AND strlen($aprovado) > 0 AND strlen($extrato) > 0 AND $status_os == 18)                 echo "Em Análise";
					elseif ($login_fabrica == 20 AND $status_os == 13 )                                                                  echo "Recusada";
					elseif ($login_fabrica == 20 AND $status_os == 14 )                                                                  echo "Acumulada";
					elseif ($login_fabrica == 1 AND strlen($aprovado) > 0 AND strlen($extrato) > 0 )                                     echo "Aprovada";
					elseif ($login_fabrica == 19 AND $status_os == 13 AND strlen($extrato) > 0)                                          echo "Recusada";
					elseif ($login_fabrica <> 19 AND $status_os == 13 AND strlen($extrato) == 0 AND strlen(trim($data_fechamento)) == 0) echo "Recusada";
					elseif ($login_fabrica <> 19 AND $status_os == 13 AND strlen($extrato) == 0 AND strlen(trim($data_fechamento)) > 0)  echo "Finalizada";
					elseif ($status_os == 14 AND strlen($extrato) == 0)                                                                  echo "Acumulada";
					elseif ($status_os == 15 AND strlen($extrato) == 0)                                                                  echo "Excluída";
					elseif ($login_fabrica == 20 AND strlen(trim($data_fechamento))>0 and strlen($extrato)==0)                           echo "Finalizada";
				}

				if ($status == "01") {
					if ($login_fabrica == 19 AND $status_os <> 13 AND strlen($aprovado) > 0)         echo "Aprovada";// (Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
					elseif ($login_fabrica == 20 AND strlen($aprovado) > 0 AND strlen($extrato) > 0 AND strlen($exportado)>0)                           echo "Pagamento efetuado";
					elseif ($login_fabrica <> 19 AND strlen($aprovado) > 0 AND strlen($extrato) > 0) echo "Aprovada";// (Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
				}
				elseif ($status == "13") {
					if ($login_fabrica == 19 AND $status_os == 13 AND strlen($extrato) > 0)                                              echo "Recusada";// (Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
					elseif ($login_fabrica <> 19 AND $status_os == 13 AND strlen($extrato) == 0 AND strlen(trim($data_fechamento)) == 0) echo "Recusada";// (Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
				}
				elseif ($status == "14") {
					if ($status_os == 14 AND strlen($extrato) == 0) echo "Acumulada";// (Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
				}
				elseif ($status == "15") {
					if ($status_os == 15 AND strlen($extrato) == 0) echo "Excluída";// (Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";	
				}

				echo "</td>";
				echo "</tr>\n";

				if ($login_fabrica == 19 AND strlen($observacao) > 0 AND strtoupper($observacao) <> "ACEITA" AND strtoupper($observacao) <> "IMPORTADA" AND $status_os == 13) {
					echo "<tr class='Conteudo' bgcolor='$cor'>";
					echo "<td colspan='7'><b>Obs. Fábrica: </b>" . $observacao . "</td>";
					echo "</tr>";
				}elseif ($login_fabrica == 14 and strlen($aprovado) == 0 AND strlen($observacao) > 0) {
					echo "<tr class='Conteudo' bgcolor='$cor'>";
					echo "<td colspan='7'><b>Obs. Fábrica: </b>" . $observacao . "</td>";
					echo "</tr>";
				}elseif (strlen($aprovado) == 0 AND strlen($observacao) > 0 AND $status_os <> 14) {
					echo "<tr class='Conteudo' bgcolor='$cor'>";
					echo "<td colspan='7'><b>Obs. Fábrica: </b>" . $observacao . "</td>";
					echo "</tr>";
				}elseif($login_fabrica == 20 and strlen($aprovado) == 0 AND strlen($observacao) > 0){
					echo "<tr class='Conteudo' bgcolor='$cor'>";
					echo "<td colspan='7'><b>Obs. Fábrica: </b>" . $observacao . "</td>";
					echo "</tr>";
				}
				$extrato='';
			}
*/
				if (($status == "00") or ($status == "99")) {
					if(strlen($data_geracao) >  0  AND strlen($aprovado) == 0){
						if($sistema_lingua == 'ES') echo "En aprobación";else echo "Em aprovação";
					}elseif (strlen($data_geracao) == 0  AND strlen($aprovado) == 0 AND strlen($status_os) == 0 AND strlen(trim($data_fechamento)) <> 0){
						if($sistema_lingua == 'ES') echo "Cerradas";else echo "Finalizada";
					}elseif ($login_fabrica == 19 AND $status_os <> 13 AND strlen($aprovado) > 0) { 
						echo "Aprovada";
					}elseif ($login_fabrica == 20 AND strlen($aprovado) > 0 AND strlen($extrato) > 0 AND strlen($exportado)>0){
		            	if($sistema_lingua == 'ES') echo "Pagamiento efectuado";else echo "Pagamento efetuado";
					}elseif ($login_fabrica <> 19 AND $login_fabrica <> 1 AND strlen($aprovado) > 0 AND strlen($extrato) > 0){
		            	if($sistema_lingua == 'ES') echo "Aprobadas";else echo "Aprovada";
					}elseif ($login_fabrica == 1 AND strlen($aprovado) > 0 AND strlen($extrato) > 0 AND $status_os == 18){
						echo "Em Análise";
					}elseif ($login_fabrica == 20 AND $status_os == 13 ){
						if($sistema_lingua == 'ES') echo "Rechazada";else echo "Recusada";
					}elseif ($login_fabrica == 20 AND $status_os == 14 ){
						if($sistema_lingua == 'ES') echo "Acumulada";else echo "Acumulada";
					}elseif ($login_fabrica == 1 AND strlen($aprovado) > 0 AND strlen($extrato) > 0 ){ 
						if($sistema_lingua == 'ES') echo "Aprobadas";else echo "Aprovada";
					}elseif ($login_fabrica == 19 AND $status_os == 13 AND strlen($extrato) > 0){
	                     if($sistema_lingua == 'ES') echo "Rechazada";else echo "Recusada";
					}elseif ($login_fabrica <> 19 AND $status_os == 13 AND strlen($extrato) == 0 AND strlen(trim($data_fechamento)) == 0){
						 if($sistema_lingua == 'ES') echo "Rechazada";else echo "Recusada";
					}elseif ($login_fabrica <> 19 AND $status_os == 13 AND strlen($extrato) == 0 AND strlen(trim($data_fechamento)) > 0){
						if($sistema_lingua == 'ES') echo "Cerradas";else echo "Finalizada";
					}elseif ($status_os == 14 AND strlen($extrato) == 0){
					    if($sistema_lingua == 'ES') echo "Acumulada";else echo "Acumulada";
					}elseif ($status_os == 15 AND strlen($extrato) == 0){
					    if($sistema_lingua == 'ES') echo "Excluída";else echo "Excluída";
					}elseif ($login_fabrica == 20 AND strlen(trim($data_fechamento))>0 and strlen($extrato)==0){
						if($sistema_lingua == 'ES') echo "Cerradas";else echo "Finalizada";
					}
				}

				if ($status == "01") {
					if ($login_fabrica == 19 AND $status_os <> 13 AND strlen($aprovado) > 0){
						 echo "Aprovada";
					}elseif ($login_fabrica == 20 AND strlen($aprovado) > 0 AND strlen($extrato) > 0 AND strlen($exportado)>0){
						if($sistema_lingua == 'ES') echo "Pagamiento efectuado";else  echo "Pagamento efetuado";
					}elseif ($login_fabrica <> 19 AND strlen($aprovado) > 0 AND strlen($extrato) > 0){
						if($sistema_lingua == 'ES') echo "Aprobadas";else echo "Aprovada";
					}
				}
				elseif ($status == "13") {
					if ($login_fabrica == 19 AND $status_os == 13 AND strlen($extrato) > 0){
						 echo "Recusada";
					}elseif ($login_fabrica <> 19 AND $status_os == 13 AND strlen($extrato) == 0 AND strlen(trim($data_fechamento)) == 0){
						if($sistema_lingua == 'ES') echo "Rechazada";else echo "Recusada";
					}
				}
				elseif ($status == "14") {
					if ($status_os == 14 AND strlen($extrato) == 0){
						if($sistema_lingua == 'ES') echo "Acumulada";else echo "Acumulada";
					}
				}
				elseif ($status == "15") {
					if ($status_os == 15 AND strlen($extrato) == 0){ 
						if($sistema_lingua == 'ES') echo "Excluída";else echo "Excluída";
					}
				}

				echo "</td>";
				echo "</tr>\n";

				if ($login_fabrica == 19 AND strlen($observacao) > 0 AND strtoupper($observacao) <> "ACEITA" AND strtoupper($observacao) <> "IMPORTADA" AND $status_os == 13) {
					echo "<tr class='Conteudo' bgcolor='$cor'>";
					echo "<td colspan='7'><b>Obs. Fábrica: </b>" . $observacao . "</td>";
					echo "</tr>";
				}elseif ($login_fabrica == 14 and strlen($aprovado) == 0 AND strlen($observacao) > 0) {
					echo "<tr class='Conteudo' bgcolor='$cor'>";
					echo "<td colspan='7'><b>";
					if($sistema_lingua == 'ES') echo "Obs.: Planta:";else echo "Obs. Fábrica: ";
					echo "</b>" . $observacao . "</td>";
					echo "</tr>";
				}elseif (strlen($aprovado) == 0 AND strlen($observacao) > 0 AND $status_os <> 14) {
					echo "<tr class='Conteudo' bgcolor='$cor'>";
					echo "<td colspan='7'><b>";
					if($sistema_lingua == 'ES') echo "Obs.: Planta:";else echo "Obs. Fábrica: ";
					echo "</b>" . $observacao . "</td>";
					echo "</tr>";
				}elseif($login_fabrica == 20 and strlen($aprovado) == 0 AND strlen($observacao) > 0){
					echo "<tr class='Conteudo' bgcolor='$cor'>";
					echo "<td colspan='7'><b>";
					if($sistema_lingua == 'ES') echo "Obs.: Planta:";else echo "Obs. Fábrica: ";
						echo "</b>" . $observacao . "</td>";
					echo "</tr>";
				}
				$extrato='';
			}




			if($login_fabrica == 1){
				$sql2 = "SELECT	tbl_os_status.observacao    ,
							tbl_os.sua_os               ,
							TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura
						FROM tbl_os_status 
						JOIN tbl_os ON tbl_os.os = tbl_os_status.os";
			if(strlen($erro_data) == 0 and strlen($erro_os) == 0) {	
				$sql .=" WHERE tbl_os.data_digitacao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59' and tbl_os.sua_os = '$x_sua_os' ";
			}
			else {	
				if(strlen($erro_data) == 0 ) {
					$sql .=" WHERE tbl_os.data_digitacao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59' ";
				}else{
					if(strlen($erro_os) == 0) {
					$sql .=" WHERE tbl_os.sua_os = '$x_sua_os' ";
					}
				}
			}
					$sql .=" AND status_os = '71' 
							 AND tbl_os.os = $os
							 ORDER BY tbl_os.sua_os;";
				$res2 = pg_exec($con,$sql2);

				if(pg_numrows($res2) > 0){
					echo "<tr class='Conteudo' bgcolor='$cor'>";
					echo "<td colspan='7' style='font-size: 10px'><b>Obs. Fábrica: </b> <FONT COLOR='#FF3300'><u><b onClick=\"MostraEsconde('conteudo')\" style='cursor:pointer; cursor:hand;'>Peças excluídas</b></u>:</FONT>";
					echo "<div id='conteudo' style='display: none;'>";
					echo "<table border='0' align='center' cellspacing='0' cellpadding='0' style='font-size: 10px'>";

					for($j = 0; $j < pg_numrows($res2); $j++){

						$os_peca          = trim(pg_result($res2,$j,observacao));
						$os_sua_os        = trim(pg_result($res2,$j,sua_os));
						$os_data_abertura = trim(pg_result($res2,$j,data_abertura));

						$sqlZ = "SELECT tbl_peca.referencia    ,
										tbl_peca.descricao
									FROM tbl_peca
								WHERE tbl_peca.peca = '$os_peca'
								AND fabrica = $login_fabrica";
						$resZ = pg_exec($con,$sqlZ);
						
						for($z = 0; $z < pg_numrows($resZ); $z++){

							$os_peca_referencia  = pg_result($resZ,0,referencia);
							$os_peca_descricao   = pg_result($resZ,0,descricao);

							echo "<tr height='5'><td align='left'>$os_peca_referencia - $os_peca_descricao</td></tr>";
						}
					}
					echo "</table>";
					echo "</div>";
					echo "</td>";
					echo "</tr>";
				}
			}

			echo "</table>";
			echo "<br>";

			$achou = "sim";
		}else{
			$achou = "nao";
		}
	}

	//PESQUISA POR TODAS E/OU EXCLUÍDAS
	echo "<br><br>ante if";
	exit;
	if ($status == "00" OR $status == "15") {
		$sql = "SELECT  tbl_os_excluida.codigo_posto                                                        ,
						tbl_os_excluida.sua_os                                              ,
						tbl_os_excluida.referencia_produto                                  ,
						tbl_os_excluida.serie                                               ,
						tbl_os_excluida.nota_fiscal                                         ,
						to_char(tbl_os_excluida.data_nf,'DD/MM/YYYY')       AS data_nf      ,
						to_char(tbl_os_excluida.data_exclusao,'DD/MM/YYYY') AS data_exclusao
				FROM    tbl_os_excluida
				WHERE   tbl_os_excluida.fabrica = $login_fabrica
				AND     tbl_os_excluida.posto   = $login_posto";
			if(strlen($erro_os) == 0) {
				$sql .=" AND tbl_os_excluida.sua_os = '$x_sua_os' ";
			}
				$sql .=" ORDER BY tbl_os_excluida.data_exclusao;";
						echo "<br><br>sql: $sql";
		echo "<br><br>sql: $sql";
		exit;
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<br>";
			
			echo "<input type='hidden' name='qtde_os' value='" . pg_numrows($res) . "'>";
			
			echo "<table width='600' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
			echo "<tr class='Titulo'>";
			echo "<td colspan='7'>";
			if($sistema_lingua == 'ES') echo "RELACIÓN DE EXCLUÍDAS";else echo "RELAÇÃO DE OS EXCLUÍDAS";
			echo "</td>";
			echo "</tr>";
			echo "<tr class='Titulo'>";
			echo "<td>OS</td>";
			echo "<td>";
			if($sistema_lingua == 'ES') echo "PRODUCTO";else echo "PRODUTO";
			echo "</td>";
			echo "<td>SÉRIE</td>";
			echo "<td>";
			if($sistema_lingua == 'ES') echo "FACTURA COMERCIAL";else echo "NOTA FISCAL";
			echo "</td>";
			echo "<td>";
			if($sistema_lingua == 'ES') echo "FECHA FACTURA";else echo "DATA NF";
			echo "</td>";
			echo "<td>";
			if($sistema_lingua == 'ES') echo "FECHA EXCLUSIÓN";else echo "DATA EXCLUSÃO";
			echo "</td>";
			echo "<td>STATUS</td>";
			echo "</tr>";
			
			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
				$sua_os         = trim(pg_result($res,$i,sua_os));
				$produto        = trim(pg_result($res,$i,referencia_produto));
				$serie          = trim(pg_result($res,$i,serie));
				$nota_fiscal    = trim(pg_result($res,$i,nota_fiscal));
				$data_nf        = trim(pg_result($res,$i,data_nf));
				$data_exclusao  = trim(pg_result($res,$i,data_exclusao));
				
				$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
				
				if ($status == "00" OR $status == "15") {
					$cor = "#FFE1E1";
				}
				
				echo "<tr class='Conteudo' bgcolor='$cor'>";
				echo "<td>";
				if ($login_fabrica == 1) {
					echo $codigo_posto;
				}
				echo $sua_os;
				echo "</td>";
				echo "<td nowrap align='center'>" . $produto . "</td>";
				echo "<td nowrap align='right'>" . $serie . "</td>";
				echo "<td nowrap align='right'>" . $nota_fiscal . "</td>";
				echo "<td nowrap align='right'>" . $data_nf . "</td>";
				echo "<td nowrap align='center'>" . $data_exclusao . "</td>";
				echo "<td nowrap align='center'>Excluída</td>";
				echo "</tr>";
			}
			echo "</table>";
			echo "<br>";
			$achou = "sim";
		}else{
			$achou = "nao";
		}
	}
	
	if ($achou == "nao") {
		echo "<table border='0' cellpadding='2' cellspacing='0' align='center'>";
		echo "<tr height='50'>";
		echo "<td valign='middle' align='center'><img src='imagens/atencao.gif' border='0'>
			<font size=\"2\"><b>";
		if($sistema_lingua == 'ES') echo "No fueran encuentrados registros con parámetros informados/digitados!!!";
		else
		echo "Não foram encontrados OS excluídas com os parâmetros informados/digitados!!!";
	
		echo "</b></font>";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
	}
	
	##### OS NÃO FINALIZADAS (SOMENTE PESQUISA POR TODAS) #####
	if ($status == "00") {
		$sql =	"SELECT tbl_posto_fabrica.codigo_posto                                   ,
						tbl_os.os                                                        ,
						tbl_os.sua_os                                                    ,
						TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')    AS data_digitacao ,
						TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')     AS data_abertura  ,
						tbl_os.mao_de_obra                                               ,
						tbl_os.pecas                                                     ,
						tbl_os.data_fechamento                                           ,
						(
							SELECT tbl_os_status.status_os
							FROM tbl_os_status
							WHERE tbl_os_status.os = tbl_os.os
							ORDER BY data DESC LIMIT 1
						)                                              AS status_os      ,
						(
							SELECT tbl_os_status.observacao
							FROM tbl_os_status
							WHERE tbl_os_status.os = tbl_os.os
							ORDER BY data DESC LIMIT 1
						)                                              AS observacao
				FROM tbl_os
				JOIN tbl_os_extra USING (os)
				JOIN tbl_posto    USING (posto)
				JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica";
			if(strlen($erro_data) == 0 and strlen($erro_os) == 0) {	
				$sql .=" WHERE tbl_os.data_digitacao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59' and tbl_os.sua_os = '$x_sua_os' ";
			}
			else {	
				if(strlen($erro_data) == 0 ) {
					$sql .=" WHERE tbl_os.data_digitacao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59' ";
				}else{
					if(strlen($erro_os) == 0) {
						$sql .=" WHERE tbl_os.sua_os = '$x_sua_os' ";
					}
				}
			}
			$sql.="	AND tbl_os.finalizada      ISNULL
					AND tbl_os.data_fechamento ISNULL 
					AND tbl_os_extra.extrato   ISNULL
					AND tbl_os.excluida = 'f'
					AND tbl_os.posto   = $login_posto
					AND tbl_os.fabrica = $login_fabrica;";

//foi adicionado a linha acima AND tbl_os.excluida = 'f' para nao pegar as os que ja foram excluidas.

		$res = pg_exec($con,$sql);
		
		if (pg_numrows($res) > 0) {
			echo "<table width='600' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
			echo "<tr class='Titulo'>";
			echo "<td colspan='7'>";
			if($sistema_lingua == 'ES') echo "RELACIÓN DE NO CERRADAS";else echo "RELAÇÃO DE OS NÃO FINALIZADAS";
			echo "</td>";
			echo "</tr>";
			echo "<tr class='Titulo'>";
			echo "<td>OS</td>";
			echo "<td>";
			if($sistema_lingua == 'ES') echo "DIGITALIZACIÓN";else echo "DIGITAÇÃO";
			echo "</td>";
			echo "<td>ABERTURA</td>";
			echo "<td>TOTAL</td>";
			echo "<td>PROTOCOLO</td>";
			echo "<td>STATUS</td>";
			echo "</tr>";

			$extrato = '';
			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
				$os             = trim(pg_result($res,$i,os));
				$sua_os         = trim(pg_result($res,$i,sua_os));
				$data_digitacao = trim(pg_result($res,$i,data_digitacao));
				$data_abertura  = trim(pg_result($res,$i,data_abertura));
				$pecas          = trim(pg_result($res,$i,pecas));
				$mao_de_obra    = trim(pg_result($res,$i,mao_de_obra));
				$total          = $pecas + $mao_de_obra;
				$status_os      = trim(pg_result($res,$i,status_os));
				$observacao     = trim(pg_result($res,$i,observacao));
				$data_fechamento= trim(pg_result($res,$i,data_fechamento));

				$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
				
				if ($status_os == 13) {
					$cor = "#FFE1E1";
					$rowspan = "1";
				}else{
					$rowspan = "1";
				}

				echo "<tr class='Conteudo' bgcolor='$cor'";
				
				if (($status_os == 14 OR $status_os == 13) AND $login_fabrica<>20) echo " onclick=\"javascript: window.location='os_consulta_lite.php?btn_acao=PESQUISAR&sua_os=$sua_os';\" style='cursor: hand;' TITLE='CLIQUE PARA ACESSAR A OS'";
				echo ">";
				echo "<td rowspan='$rowspan'>";
				if ($login_fabrica == 1) echo $codigo_posto;
				if(strlen($sua_os)==0 AND $login_fabrica==20)echo $os;
				else                  echo $sua_os;
				echo "</td>";
				echo "<td nowrap align='center'>" . $data_digitacao . "</td>";
				echo "<td nowrap align='center'>" . $data_abertura . "</td>";
				echo "<td nowrap align='right'>" . number_format($total,2,",",".") . "</td>";
				echo "<td nowrap align='center'>" . $os . "</td>";
				echo "<td nowrap align='center'>";
				if ($status_os == 13 and strlen($extrato) == 0) {
					if ($sistema_lingua=='ES') echo "Rechazada";// (Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
					echo "Recusada";// (Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
				}
				elseif ($status_os == 14 and strlen($extrato) == 0)    echo "Acumulada";//(Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
				elseif ($status_os == 15 and strlen($extrato) == 0)    echo "Excluída";// (Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
				elseif ($status_os == 62 and strlen($extrato) == 0) {
					if ($sistema_lingua=='ES') echo "Intervención de la planta";// (Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
					else echo "Intervenção da Fábrica";// (Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
				} 
				elseif ($status_os == 65 and strlen($extrato) == 0) {
					if ($sistema_lingua=='ES') echo "Reparo en la planta";// (Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
					else echo "Reparo na Fábrica";// (Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
				}
				else                                                   echo "Aguardando";//finalização (Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
				echo "</td>";
				echo "</tr>";
				
				if (strlen($observacao) > 0 AND ($status_os == 14 OR $status_os == 13)) {
					echo "<tr class='Conteudo' bgcolor='$cor'";
										
					if ($status_os == 14 OR $status_os == 13 and $login_fabrica<>20) {
						if ($sistema_lingua=='ES') echo " onclick=\"javascript: window.location='os_consulta_lite.php?btn_acao=PESQUISAR&sua_os=$sua_os';\" style='cursor: hand;' TITLE='Click aquí para acceder la OS'";
						else echo " onclick=\"javascript: window.location='os_consulta_lite.php?btn_acao=PESQUISAR&sua_os=$sua_os';\" style='cursor: hand;' TITLE='Click aquí para acceder la OS'";
					}
					echo ">";
					echo "<td colspan='6'><b>Obs. Fábrica: </b><a href=\"os_consulta_lite.php?btn_acao=PESQUISAR&sua_os=$sua_os\">" . $observacao . "</a></td>";
					echo "</tr>";
				}
			}
			echo "</table>";
			echo "<br>";
		}
	}
	
	//Se consultar por OS, entao não entra aqui - só a fabrida 1(black) nao consulta por OS 

	if(strlen($erro_data) == 0 ) {
		echo "passou aqui sedex";
		
		##### OS SEDEX FINALIZADAS #####

		$sql = "SELECT tbl_posto_fabrica.codigo_posto                                     ,
						tbl_os_sedex.os_sedex                                              ,
						tbl_os_sedex.sua_os_origem                                         ,
						tbl_os_sedex.sua_os_destino                                        ,
						TO_CHAR(tbl_os_sedex.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
						tbl_os_sedex.total_pecas                                           ,
						tbl_os_sedex.despesas                                              ,
						tbl_os_sedex.total                                                 ,
						TO_CHAR(tbl_extrato.aprovado,'DD/MM/YYYY')     AS aprovado         ,
						TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao     ,
						tbl_os_status.observacao,
						tbl_os_status.status_os
				FROM tbl_os_sedex
				JOIN tbl_posto           on tbl_posto.posto = tbl_os_sedex.posto_origem
				JOIN tbl_posto_fabrica   ON  tbl_posto_fabrica.posto   = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_extrato    ON tbl_extrato.extrato = tbl_os_sedex.extrato_origem
				LEFT JOIN tbl_os_status  ON tbl_os_status.os_sedex = tbl_os_sedex.os_sedex
				WHERE tbl_os_sedex.data_digitacao::date BETWEEN '$x_data_inicial' AND '$x_data_final' 
				AND tbl_os_sedex.finalizada   NOTNULL
						 AND tbl_os_sedex.posto_origem = $login_posto
						 AND tbl_os_sedex.fabrica      = $login_fabrica";

		$res = pg_exec($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<table width='600' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
			echo "<tr class='Titulo'>";
			echo "<td colspan='7'>RELAÇÃO DE OS SEDEX</td>";
			echo "</tr>";
			echo "<tr class='Titulo'>";
			echo "<td>OS SEDEX</td>";
			echo "<td>DIGITAÇÃO</td>";
			echo "<td>PEÇAS</td>";
			echo "<td>DESPESAS</td>";
			echo "<td>TOTAL</td>";
			echo "<td>PROTOCOLO</td>";
			echo "<td>STATUS</td>";
			echo "</tr>";

			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
				$os_sedex       = trim(pg_result($res,$i,os_sedex));
				$xos_sedex      = "00000".$os_sedex;
				$xos_sedex      = substr($xos_sedex,strlen($xos_sedex) - 5,strlen($xos_sedex));
				$sua_os         = trim(pg_result($res,$i,sua_os_origem));
				$sua_os_destino = trim(pg_result($res,$i,sua_os_destino));
				$data_digitacao = trim(pg_result($res,$i,data_digitacao));
				$pecas          = trim(pg_result($res,$i,total_pecas));
				$despesas       = trim(pg_result($res,$i,despesas));
				$total          = trim(pg_result($res,$i,total));
				$aprovado       = trim(pg_result($res,$i,aprovado));
				$data_geracao   = trim(pg_result($res,$i,data_geracao));
				$observacao     = trim(pg_result($res,$i,observacao));
				$status_os      = trim(pg_result($res,$i,status_os));

				$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

				echo "<tr class='Conteudo' bgcolor='$cor'";
				if ($status_os == 14 OR $status_os == 13) echo " onclick=\"javascript: window.location='os_consulta_lite.php?btn_acao=PESQUISAR&sua_os=$sua_os';\" style='cursor: hand;'";
				echo ">";
				echo "<td>";
				if ($login_fabrica == 1) {
					echo $codigo_posto;
				}
			
				if($sua_os_destino == 'CR' AND $login_fabrica == 1){
					$sql2   = "SELECT sua_os FROM tbl_os WHERE os = '$sua_os' AND tbl_os.fabrica = '$login_fabrica'; ";
					$res2   = pg_exec($con, $sql2);
					$cr_sua_os = pg_result($res2, 0, sua_os);
					$cr_sua_os = $posto_origem_codigo.$cr_sua_os;
				}
				
				if($sua_os_destino == 'CR' AND $login_fabrica == 1){
					echo "$cr_sua_os";
				}else{
					echo $xos_sedex;
				}
				echo "</td>";
				echo "<td align='center'>" . $data_digitacao . "</td>";
				echo "<td align='right'>" . number_format($pecas,2,",",".") . "</td>";
				echo "<td align='right'>" . number_format($despesas,2,",",".") . "</td>";
				echo "<td align='right'>" . number_format($total,2,",",".") . "</td>";
				echo "<td align='center'>" . $os . "</td>";
				echo "<td align='center'>";
				
				if (strlen($data_geracao) > 0 AND strlen($aprovado) == 0 AND strlen($status_os) == 0) { 
					if ($sistema_lingua=='ES') {
						echo "En aprobación";// (Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
					} else {
						echo "Em aprovação";// (Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
					}
				} elseif (strlen($data_geracao) == 0 AND strlen($aprovado) == 0 AND strlen($status_os) == 0) {
					if ($sistema_lingua=='ES') {
						echo "En aprobación";//(Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
					} else {
						echo "Em aprovação";//(Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
					}
				} elseif (strlen($aprovado) > 0)                                                             echo "Aprovada";//(Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
				elseif ($status_os == 13)                                                                  echo "Recusada";//(Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
				elseif ($status_os == 14)                                                                  echo "Acumulada";//(Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
				elseif ($status_os == 15)                                                                  echo "Excluída:<br><font color='#FF0000'>$observacao</font>";
				elseif ($status_os == 62)                                                                  echo "Intervenção da Fábrica";
				elseif ($status_os == 65)                                                                  echo "Reparo na Fábrica";
				echo "</td>";
				echo "</tr>";
				
				if ($status_os == 13 AND $login_fabrica == 1) {
					echo "<tr class='Conteudo' bgcolor='$cor'>";
					echo "<td colspan='6'><b>Obs. Fábrica: </b>" . $observacao . "</td>";
					echo "</tr>";
				}
			}
			echo "</table>";
			echo "<br>";
		}


		##### OS SEDEX NÃO FINALIZADAS #####

		$sql = "SELECT tbl_posto_fabrica.codigo_posto                                     ,
						tbl_os_sedex.os_sedex                                              ,
						tbl_os_sedex.sua_os_origem                                         ,
						tbl_os_sedex.sua_os_destino                                        ,
						TO_CHAR(tbl_os_sedex.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
						tbl_os_sedex.total_pecas                                           ,
						tbl_os_sedex.despesas                                              ,
						tbl_os_sedex.total                                                 ,
						TO_CHAR(tbl_extrato.aprovado,'DD/MM/YYYY')     AS aprovado         ,
						TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao     ,
						tbl_os_status.observacao                                           ,
						tbl_os_status.status_os
				FROM tbl_os_sedex
				JOIN tbl_posto           on tbl_posto.posto = tbl_os_sedex.posto_origem
				JOIN tbl_posto_fabrica   ON  tbl_posto_fabrica.posto   = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_extrato    ON tbl_extrato.extrato = tbl_os_sedex.extrato_origem
				LEFT JOIN tbl_os_status  ON tbl_os_status.os_sedex = tbl_os_sedex.os_sedex
				WHERE tbl_os_sedex.data_digitacao::date BETWEEN '$x_data_inicial' AND '$x_data_final' AND tbl_os_sedex.finalizada   ISNULL
						 AND tbl_os_sedex.posto_origem = $login_posto
						 AND tbl_os_sedex.fabrica      = $login_fabrica";
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<table width='600' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
			echo "<tr class='Titulo'>";
			echo "<td colspan='7'>RELAÇÃO DE OS SEDEX NÃO FINALIZADAS</td>";
			echo "</tr>";
			echo "<tr class='Titulo'>";
			echo "<td>OS SEDEX</td>";
			echo "<td>DIGITAÇÃO</td>";
			echo "<td>PEÇAS</td>";
			echo "<td>DESPESAS</td>";
			echo "<td>TOTAL</td>";
			echo "<td>PROTOCOLO</td>";
			echo "<td>STATUS</td>";
			echo "</tr>";

			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
				$os_sedex       = trim(pg_result($res,$i,os_sedex));
				$xos_sedex      = "00000".$os_sedex;
				$xos_sedex      = substr($xos_sedex,strlen($xos_sedex) - 5,strlen($xos_sedex));
				$sua_os         = trim(pg_result($res,$i,sua_os_origem));
				$data_digitacao = trim(pg_result($res,$i,data_digitacao));
				$pecas          = trim(pg_result($res,$i,total_pecas));
				$despesas       = trim(pg_result($res,$i,despesas));
				$total          = trim(pg_result($res,$i,total));
				$aprovado       = trim(pg_result($res,$i,aprovado));
				$data_geracao   = trim(pg_result($res,$i,data_geracao));
				$observacao     = trim(pg_result($res,$i,observacao));
				$status_os      = trim(pg_result($res,$i,status_os));
				
				$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
				
				echo "<tr class='Conteudo' bgcolor='$cor'>";
				echo "<td>";
				if ($login_fabrica == 1) echo $codigo_posto;
				echo $xos_sedex;
				echo "</td>";
	//			echo "<td rowspan='$rowspan'>" . $codigo_posto . $xos_sedex . "</td>";
				echo "<td align='center'>" . $data_digitacao . "</td>";
				echo "<td align='right'>" . number_format($pecas,2,",",".") . "</td>";
				echo "<td align='right'>" . number_format($despesas,2,",",".") . "</td>";
				echo "<td align='right'>" . number_format($total,2,",",".") . "</td>";
				echo "<td align='center'>" . $os . "</td>";
				echo "<td align='center'>Não 07/05/2007</td>";
				echo "</tr>";
				if ($status_os == 13 AND $login_fabrica == 1) {
					echo "<tr class='Conteudo' bgcolor='$cor'>";
					echo "<td colspan='6'><b>Obs. Fábrica: </b>" . $observacao . "</td>";
					echo "</tr>";
				}
				
			}
			echo "</table>";
			echo "<br>";
		}

		##### OS SEDEX NÃO FINALIZADAS RECUSADAS#####
		if($login_fabrica == 1){
			$sql = "SELECT tbl_posto_fabrica.codigo_posto                                     ,
							tbl_os_sedex.os_sedex                                              ,
							tbl_os_sedex.sua_os_origem                                         ,
							tbl_os_sedex.sua_os_destino                                        ,
							TO_CHAR(tbl_os_sedex.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
							tbl_os_sedex.total_pecas                                           ,
							tbl_os_sedex.despesas                                              ,
							tbl_os_sedex.total                                                 ,
							TO_CHAR(tbl_extrato.aprovado,'DD/MM/YYYY')     AS aprovado         ,
							TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao     ,
							tbl_os_status.observacao                                           ,
							tbl_os_status.status_os
					FROM tbl_os_sedex
					JOIN tbl_posto           on tbl_posto.posto = tbl_os_sedex.posto_destino
					JOIN tbl_posto_fabrica   ON  tbl_posto_fabrica.posto   = tbl_posto.posto
											AND tbl_posto_fabrica.fabrica = $login_fabrica
					LEFT JOIN tbl_extrato    ON tbl_extrato.extrato = tbl_os_sedex.extrato_origem
					LEFT JOIN tbl_os_status  ON tbl_os_status.os_sedex = tbl_os_sedex.os_sedex
					WHERE tbl_os_sedex.data_digitacao::date BETWEEN '$x_data_inicial' AND '$x_data_final'
					AND tbl_os_sedex.finalizada   ISNULL
					AND tbl_os_sedex.posto_destino = $login_posto
					AND tbl_os_sedex.fabrica      = $login_fabrica";
			$res = pg_exec($con,$sql);

			if (pg_numrows($res) > 0) {
				echo "<table width='600' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
				echo "<tr class='Titulo'>";
				echo "<td colspan='7'>RELAÇÃO DE OS SEDEX NÃO FINALIZADAS</td>";
				echo "</tr>";
				echo "<tr class='Titulo'>";
				echo "<td>OS SEDEX</td>";
				echo "<td>DIGITAÇÃO</td>";
				echo "<td>PEÇAS</td>";
				echo "<td>DESPESAS</td>";
				echo "<td>TOTAL</td>";
				echo "<td>PROTOCOLO</td>";
				echo "<td>STATUS</td>";
				echo "</tr>";

				for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
					$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
					$os_sedex       = trim(pg_result($res,$i,os_sedex));
					$xos_sedex      = "00000".$os_sedex;
					$xos_sedex      = substr($xos_sedex,strlen($xos_sedex) - 5,strlen($xos_sedex));
					$sua_os         = trim(pg_result($res,$i,sua_os_origem));
					$data_digitacao = trim(pg_result($res,$i,data_digitacao));
					$pecas          = trim(pg_result($res,$i,total_pecas));
					$despesas       = trim(pg_result($res,$i,despesas));
					$total          = trim(pg_result($res,$i,total));
					$aprovado       = trim(pg_result($res,$i,aprovado));
					$data_geracao   = trim(pg_result($res,$i,data_geracao));
					$observacao     = trim(pg_result($res,$i,observacao));
					$status_os      = trim(pg_result($res,$i,status_os));
					
					$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
					
					echo "<tr class='Conteudo' bgcolor='$cor'>";
					echo "<td>";
					if ($login_fabrica == 1) echo $codigo_posto;
					echo $xos_sedex;
					echo "</td>";
		//			echo "<td rowspan='$rowspan'>" . $codigo_posto . $xos_sedex . "</td>";
					echo "<td align='center'>" . $data_digitacao . "</td>";
					echo "<td align='right'>" . number_format($pecas,2,",",".") . "</td>";
					echo "<td align='right'>" . number_format($despesas,2,",",".") . "</td>";
					echo "<td align='right'>" . number_format($total,2,",",".") . "</td>";
					echo "<td align='center'>" . $os . "</td>";
					if($status_os == 13 ) echo "<td align='center'>Recusada</td>";
					else echo "<td align='center'>Não finalizada</td>";
					echo "</tr>";
					if ($status_os == 13 AND $login_fabrica == 1) {
						echo "<tr class='Conteudo' bgcolor='$cor'>";
						echo "<td colspan='7'><b>Obs. Fábrica: </b>" . $observacao . "</td>";
						echo "</tr>";
					}
					
				}
				echo "</table>";
				echo "<br>";
			}
		}
	}
}

include "rodape.php";
?>
