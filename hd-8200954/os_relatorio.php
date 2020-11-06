<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "funcoes.php";

$erro = "";

if (strlen($_POST["acao"]) > 0 )
	$acao = strtoupper($_POST["acao"]);
if (strlen($_GET["acao"]) > 0 )
	$acao = strtoupper($_GET["acao"]);

if (strlen($acao) > 0 && $acao == "PESQUISAR") {
	if($login_fabrica == 14){
		if ($_GET["acao"] == 'PESQUISAR' and $_GET["recusar"] == 'sim'){
			$os_recusada      = 'sim';
			$data_inicial     = date("Y/m/d", strtotime('-3 month'));
			$data_final       = date("Y/m/d");
			$x_data_inicial = $data_inicial;
			$x_data_final   = $data_final;
		}
	}

	$radio_cons = 'sua_os';
	if (strlen(trim($_POST["sua_os"])) > 0)
		$x_sua_os = trim($_POST['sua_os']);
	if (strlen(trim($_GET["sua_os"])) > 0)
		$x_sua_os = trim($_GET['sua_os']);

	if(strlen($_GET["status"])){
		$status = $_GET["status"];
	}

	if ($os_recusada != 'sim'){
		if(strlen($sua_os)>0){
			// passou
		}
	}

	if (!$x_sua_os){
		if ((strlen($data_inicial) > 0 or $data_inicial != "null") and (strlen($data_final) > 0 or $data_final != "null") and (strlen($sua_os) == 0)){
			$radio_cons = 'data';

			if (strlen(trim($_POST["data_inicial"])) > 0)
				$x_data_inicial = trim($_POST["data_inicial"]);
			if (strlen(trim($_GET["data_inicial"])) > 0)
				$x_data_inicial = trim($_GET["data_inicial"]);

			if (strlen(trim($_POST["data_final"])) > 0)
					$x_data_final   = trim($_POST["data_final"]);
			if (strlen(trim($_GET["data_final"])) > 0)
				$x_data_final = trim($_GET["data_final"]);

			//Início Validação de Datas
			if(!$x_data_inicial OR !$x_data_final)
				$erro = traduz("Data Inválida.");

				if($login_fabrica == 42){
					$data_hoje     = date("Y-m-d", strtotime('-6 month'));					

					$d_ini = explode ("/",  $x_data_inicial);//tira a barra
					$nova_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";

					if (strtotime($nova_data_inicial) < strtotime($data_hoje) ) { //hd_chamado=2737551 alterando pesquisa p/ 90 dias
			             $erro = traduz("O limite para a pesquisa é de 6 meses");
			        }
				}

			if(strlen($erro)==0){
				if($x_data_inicial){
					$dat = explode ("/", $x_data_inicial );//tira a barra
						$d = $dat[0];
						$m = $dat[1];
						$y = $dat[2];
						if(!checkdate($m,$d,$y)) $erro = traduz("Data Inválida<br>");
				}
				if($x_data_final){
					$dat = explode ("/", $x_data_final );//tira a barra
						$d = $dat[0];
						$m = $dat[1];
						$y = $dat[2];
						if(!checkdate($m,$d,$y)) $erro = traduz("Data Inválida");
				}
				if(strlen($erro)==0){
					$d_ini = explode ("/",  $x_data_inicial);//tira a barra
					$nova_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...


					$d_fim = explode ("/", $x_data_final);//tira a barra
					$nova_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

					if($nova_data_final < $nova_data_inicial){
						$erro = traduz("Data Inválida.");
					}

					//Fim Validação de Datas
				}
			}


			if (strlen($erro) == 0){
				$data_inicial   = $x_data_inicial;
				$x_data_inicial = str_replace("'","",fnc_formata_data_pg($x_data_inicial));
				$data_final     = $x_data_final;
				$x_data_final   = str_replace("'","",fnc_formata_data_pg($x_data_final));

				if (strlen(trim($_POST["status"])) > 0)
					$status = trim($_POST["status"]);
				if (strlen(trim($_GET["status"])) > 0)
					$status = trim($_GET["status"]);

				$link_status = "http://" . $HTTP_HOST . $REQUEST_URI . "?data_inicial=" . $_POST["data_inicial"] . "&data_final=" . $_POST["data_final"] . "&acao=PESQUISAR";
				setcookie("LinkStatus", $link_status);
			}
		}
	}
}

$layout_menu = "os";
$title = traduz("RELAÇÃO DE STATUS DA ORDEM DE SERVIÇO"); /*"Relação de Status da Ordem de Serviço";*/
//if($sistema_lingua=='ES') $title = "Relación de status de órdenes de servicio";
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
	background-color: #596D9B;
	text-transform:uppercase;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12 px;
	font-weight: normal;
}

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
</style>

<?include "javascript_calendario_new.php";
include 'js/js_css.php';
?>
<script type="text/javascript">
$(function()
{
	$('#data_inicial').datepick({startdate:'01/01/2000'});
	$('#data_final').datepick({startDate:'01/01/2000'});
	$("#data_inicial").mask("99/99/9999");
	$("#data_final").mask("99/99/9999");
});
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
</script>


<? if ($os_recusada != 'sim'){?>
<form name="frm_relatorio" method="post" action="<?echo $PHP_SELF?>">
	<input type="hidden" name="acao" />
	<table width='700' border='0' cellpadding='4' cellspacing='1' align='center' class='Conteudo' style='background-color: #485989'>
		<!-- QUADRO COM OS RADIO BUTTONS PARA SELEÇÃO -->
		<? if (strlen($erro) > 0) { ?>
			<tr class="msg_erro">
				<td><?echo $erro?></td>
			</tr>
		<? } ?>
		<tr class="titulo_tabela"><td><?=traduz('Parâmetros de Pesquisa')?></td></tr>
		<tr>
			<td bgcolor='#DBE5F5' valign='bottom'>
				<table width='100%' border='0' cellspacing='0' cellpadding='0' >
					<? if ($login_fabrica != 1) {?>
						<td width="100%" class='titulo_tabela'>
								<?=traduz('Seleção por Número da Ordem de Serviço')?>
						</td>
						<table width='100%' border='0' cellspacing='0' cellpadding='0' >
							<td width='5'>&nbsp;</td>
							<td colspan="2" align="left">
								<span><font size='2'><? fecho ("Numero", $con, $cook_idioma);?></font></span>
								<br>
                                <input type="text" name="sua_os" id="sua_os" size="12" maxlength="20" value="<? if (strlen($x_sua_os) > 0) echo $x_sua_os; else echo ""; ?>" >
							</td>
						</table>
						<table width='100%' border='0' cellspacing='0' cellpadding='0' >
						<br />
					<?}else{
						echo "	<input type='hidden' name='sua_os' id='sua_os'>";
					}?>
					<td width="100%" class='titulo_tabela'>
						<?=traduz('Período de Seleção')?>
					</td>
					<table width='100%' border='0' cellspacing='0' cellpadding='0' >
						<tr class="table_line">
							<td width='5'>&nbsp;</td>
							<td  align="left">

									<span><font size='2'><? fecho ("Data Inicial", $con, $cook_idioma);?></font></span>
                                    <br>
									<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "" ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" />
							</td>
							<td  align="left">

									<span><font size='2'><? fecho ("Data Final", $con, $cook_idioma);?></font></span>
									<br>
                                    <input type="text" name="data_final" id="data_final" size="12" maxlength="10"  value="<? if (strlen($data_final) > 0) echo $data_final; else echo ""; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" />

							</td>
						</tr>
					</table>
					<table width='100%' border='0' cellspacing='0' cellpadding='0' >
						<br />
						<td width="100%" class='titulo_tabela'>
							<?=traduz('Filtro do Tipo de Período (por data)')?>
						</td>
						<table width='100%' border='0' cellspacing='0' cellpadding='0' >
							<br />
							<tr class="Conteudo" bgcolor="#D9E2EF">
								<td>&nbsp;</td>
								<td width='33%' align='left' nowrap>
									<input type="radio" name="radio_periodo" value="gera_extrato" >
									<font size='2'>
										<? fecho ("Geração de extrato", $con, $cook_idioma);?>
									</font>
								</td>
								<td width='33%' align='left' nowrap>
									<input type="radio" name="radio_periodo" value="abre_os" >
									<font size='2'>
										<? fecho ("Abertura de  O.S.", $con, $cook_idioma);?>
									</font>
								</td>
								<td width='33%' align='left' nowrap>
									<input type="radio" name="radio_periodo" value="digita_os" checked="checked">
									<font size='2'>
										<? fecho ("Digitação de O.S.", $con, $cook_idioma);?>
									</font>
								</td>
							</tr>
							<tr class="Conteudo" bgcolor="#D9E2EF">
								<td>&nbsp;</td>
								<td width='33%' align='left' nowrap>
									<input type="radio" name="radio_periodo" value="aprova_extrato" >
									<font size='2'>
										<? fecho ("Aprovação de extrato", $con, $cook_idioma);?>
									</font>
								</td>
								<td width='33%' align='left' nowrap>
									<input type="radio" name="radio_periodo" value="fecha_os" >
									<font size='2'>
										<? fecho ("Fechamento de  O.S.", $con, $cook_idioma);?>
									</font>
								</td>
								<td width='33%' align='left' nowrap>
									<input type="radio" name="radio_periodo" value="finaliza_os" >
									<font size='2'>
										<? fecho ("Finalização de O.S.", $con, $cook_idioma);?>
									</font>
								</td>
							</tr>
						</table>
						<table width='100%' border='0' cellspacing='0' cellpadding='0' >
							<br />
							<td width="100%" class='titulo_tabela' >
								<?=traduz('Tipo de Status das OS')?>
							</td>
							<table width='100%' border='0' cellspacing='0' cellpadding='0' >
								<br />
								<tr class="Conteudo" bgcolor="#D9E2EF">
									<td>&nbsp;</td>
									<td width='33%' align='left' nowrap>
										<input type="radio" name="status" value="00" checked="checked">
										<font size='2'>
											<? fecho ("Todas", $con, $cook_idioma);?>
										</font>
									</td>
									<td width='33%' align='left' nowrap>
										<input type="radio" name="status" value="14" <? echo ($status == "14") ? "checked" : ""; ?> >
										<font size='2'>
											<? fecho ("Acumuladas", $con, $cook_idioma);?>
										</font>
									</td>
									<td width='33%' align='left' nowrap>
										<input type="radio" name="status" value="01" <? echo ($status == "01") ? "checked" : ""; ?>>
										<font size='2'>
											<? fecho ("Aprovadas", $con, $cook_idioma);?>
										</font>
									</td>
								</tr>
								<tr class="Conteudo" bgcolor="#D9E2EF">
									<td>&nbsp;</td>
									<td width='33%' align='left' nowrap>
										<input type="radio" name="status" value="15" <? echo ($status == "15") ? "checked" : ""; ?>>
										<font size='2'>
											<? fecho ("excluidas");?>
										</font>
									</td>
										<td width='33%' align='left' nowrap>
											<input type="radio" name="status" value="15a"  <? echo ($status == "15a") ? "checked" : ""; ?>>
											<font size='2'>
												<? fecho ("Excluídas pelo fabricante", $con, $cook_idioma);?>
											</font>
										</td>
									<td width='33%' align='left' nowrap>
										<input type="radio" name="status" value="99"  <? echo ($status == "99") ? "checked" : ""; ?>>
										<font size='2'>
											<? fecho ("Finalizadas", $con, $cook_idioma);?>
										</font>
									</td>
									<td width='33%' align='left' nowrap>
										<input type="radio" name="status" value="13" <? echo ($status == "13") ? "checked" : ""; ?>>
										<font size='2'>
											<? fecho ("Recusadas", $con, $cook_idioma);?>
										</font>
									</td>
								</tr>
							</table>
							<table width='100%' border='0' cellspacing='0' cellpadding='0'>
								<br />
								<table width='100%' border='0' cellspacing='0' cellpadding='0' >
									<br />
									<tr class="Conteudo" bgcolor="#D9E2EF">
										<td width='100%' colspan="4" align = 'center' style="text-align: center;">
										<input type="image" src="<?if ($sistema_lingua=='ES') echo "admin_es/"; ?>imagens_admin/btn_pesquisar_400.gif" onClick="document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor:hand;" alt="<? fecho ("Preencha as opções e clique aqui para pesquisar",$con, $cook_idioma);?>">
										</td>
									</tr>
								</table>
							</table>
						</table>
						<br>
					</table>
				</table>
			</td>
		</tr>
	</table>
	<br>
</form>
<?}

if (strlen($acao) > 0 && strlen($erro) == 0) {

	//SOMENTE OSs QUE NÃO ESTÃO EXCLUIDAS
	if ($status <> "15" and $status <> "15a") {

		$cond_fabrica_extra         = (in_array($login_fabrica, array(11,172))) ? " tbl_os_extra.i_fabrica IN (11,172) " : " tbl_os_extra.i_fabrica = $login_fabrica "; 
		$cond_fabrica_extrato       = (in_array($login_fabrica, array(11,172))) ? " tbl_extrato.fabrica IN (11,172) " : " tbl_extrato.fabrica = $login_fabrica "; 
		$cond_fabrica_os            = (in_array($login_fabrica, array(11,172))) ? " tbl_os.fabrica IN (11,172) " : " tbl_os.fabrica = $login_fabrica "; 
		$cond_fabrica_posto_fabrica = (in_array($login_fabrica, array(11,172))) ? " tbl_posto_fabrica.fabrica IN (11,172) " : " tbl_posto_fabrica.fabrica = $login_fabrica "; 

		$sql = "SELECT *  FROM (

				SELECT
						tbl_posto_fabrica.codigo_posto                                    ,
						tbl_os.os                                                         ,
						tbl_os.sua_os                                                     ,
						TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')    AS data_digitacao  ,
						TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')     AS data_abertura   ,
						TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY')   AS data_fechamento ,
						TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY')        AS finalizada      ,
						tbl_os.pecas                                                      ,
						tbl_os.mao_de_obra                                                ,
						tbl_os_extra.mao_de_obra                       AS mao_de_obra_extra,
						tbl_os_extra.mao_de_obra_desconto                                 ,
						tbl_os.admin                                                      ,
						tbl_os.tipo_atendimento                                           ,
						tbl_extrato.extrato                                               ,
						tbl_extrato_extra.exportado                                       ,
						TO_CHAR(tbl_extrato.aprovado,'DD/MM/YYYY')     AS aprovado        ,
						TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao    ,
						CASE
							WHEN tbl_os.cortesia IS TRUE THEN
								'Cortesia'
							WHEN tbl_os.tipo_atendimento = 35 THEN
								'Troca cortesia'
							WHEN tbl_os.consumidor_revenda = 'C' AND (tbl_os.tipo_atendimento = 17 OR tbl_os.tipo_atendimento = 18) THEN
								'Troca consumidor'
							WHEN tbl_os.consumidor_revenda = 'R' AND (tbl_os.tipo_atendimento = 17 OR tbl_os.tipo_atendimento = 18) THEN
								'Troca de revenda'
							WHEN tbl_os.consumidor_revenda = 'R' THEN
								'Revenda'
							ELSE
								'Consumidor'
						END AS tipo_os,
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
						)                                              AS observacao     ,
						(
							select tbl_os_status.status_os_troca
							from tbl_os_status
							where tbl_os_status.os = tbl_os.os
							order by data desc limit 1
						)                                              AS status_os_troca
				FROM  tbl_os
				LEFT JOIN  tbl_os_extra          ON tbl_os_extra.os           = tbl_os.os AND {$cond_fabrica_extra}
				LEFT JOIN  tbl_extrato           ON tbl_extrato.extrato       = tbl_os_extra.extrato AND {$cond_fabrica_extrato}
				LEFT JOIN  tbl_extrato_extra     ON tbl_extrato_extra.extrato = tbl_extrato.extrato
				JOIN  tbl_posto_fabrica          ON tbl_posto_fabrica.posto   = tbl_os.posto
												AND {$cond_fabrica_posto_fabrica} ";


		//CONSULTA POR DATA OU POR SUA_OS - COMO A BLACK NÃO CONSULTA POR OS, ENTAO CONSULTA DIRETO POR DATA

		if($radio_cons == "data" and $os_recusada!='sim'){
			switch ($_POST['radio_periodo']){
				case 'gera_extrato':
						if (($status == "01") or ($status == "99")){
							$sql .=" WHERE tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
						}else{
							$erro = "NÃO HÁ POSSIBILIDADE DE EXECUTAR OS FILTROS SELECIONADOS";
							echo "<table width='700' border='0' cellspacing='0' cellpadding='2' align='center' class='Error'>";
								echo"<tr>";
									echo "<td><?echo $erro?></td>";
								echo "</tr>";
							echo "</table>";
							echo "<br>";
						}
					break;
				case 'abre_os':
					$sql .=" WHERE tbl_os.data_abertura BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
					break;
				case 'digita_os':
					$sql .=" WHERE tbl_os.data_digitacao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
					break;
				case 'aprova_extrato':
						if (($status == "01") or ($status == "99")){
							$sql .=" WHERE tbl_extrato.aprovado BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
						}else{
							$erro = traduz("NÃO HÁ POSSIBILIDADE DE EXECUTAR OS FILTROS SELECIONADOS");
							echo "<table width='700' border='0' cellspacing='0' cellpadding='2' align='center' class='Error'>";
								echo"<tr>";
									echo "<td>";
										echo $erro;
									echo "</td>";
								echo "</tr>";
							echo "</table>";
							echo "<br>";
						}
					break;
				case 'fecha_os':
					$sql .=" WHERE tbl_os.data_fechamento BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
					break;
				case 'finaliza_os':
					$sql .=" WHERE tbl_os.finalizada BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
					break;
			}
		}elseif($os_recusada == "sim"){
				$sql .=" WHERE tbl_os.data_digitacao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
		}else {
			if($login_fabrica == 42){
				$sql .=" WHERE tbl_os.sua_os = '$x_sua_os' ";
				$sql .=" AND tbl_os.data_digitacao BETWEEN '". date("Y-m-d", strtotime('-6 month')) ." 00:00:00' AND '".date("Y-m-d")." 23:59:59'";
			}else{
				$sql .=" WHERE tbl_os.sua_os = '$x_sua_os' ";
			}
		}

		$sql.=" AND tbl_os.posto   = $login_posto
				AND $cond_fabrica_os ) x";
		//TODAS
		if ($status == "99") {
			$sql.= " WHERE data_fechamento NOTNULL
					 AND aprovado IS NULL";
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
		if ($status == "13" or $os_recusada == 'sim') {
			if ($login_fabrica == 19){
				$sql.= " WHERE status_os = 13";
			}else{
				$sql.= " WHERE status_os = 13 AND data_fechamento IS NULL";
			}
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

		$res = pg_query($con,$sql);

		if ((pg_num_rows($res) > 0) && strlen($erro) == 0){
			echo "<table width='700' border='0' cellspacing='2' cellpadding='0' align='center'>";
			echo "<tr>";
			echo "<td width='20' height='20' bgcolor='#FFE1E1'>&nbsp;</td>";
			echo "<td><font size=1>&nbsp; <b>";
			/*if($sistema_lingua == 'ES') echo "OS rechazadas por el fabricante";else echo "OS RECUSADA pelo fabricante";*/
			fecho ("os.recusada.pelo.fabricante", $con, $cook_idioma);
			echo "</b></font></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td colspan='2' height='5'></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td width='20' height='20' bgcolor='#D7FFE1'>&nbsp;</td>";
			echo "<td><font size='1'>&nbsp; <strong>(";
			fecho ("os.acumuladas.pelo.fabricante", $con, $cook_idioma);
			echo "</strong>";
			fecho ("clique.na.linha.da.os.para.realizar.a.alteracao.desejada.na", $con, $cook_idioma);
			echo "<a href='os_parametros.php'>";
			fecho ("consulta.de.os", $con, $cook_idioma);
			echo ")";
			echo "</a>";
			echo "</font></td>";
			echo "</tr>";
			if($login_fabrica == 15){//chamado 2235
				echo "<tr>";
				echo "<td colspan='2' height='5'></td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td width='20' height='20' bgcolor='#FF9900'>&nbsp;</td>";
				echo "<td><font size='1'>&nbsp; <b>";
				#echo "OS Digitada pela Latinatec";
				fecho ("os.digitada.pela.latinatec", $con, $cook_idioma);
				echo "</font></td>";
				echo "</tr>";
			}
			echo "</table>";
			echo "<br>";

			echo "<input type='hidden' name='qtde_os' value='" . pg_num_rows($res) . "'>";

			echo "<table width='700' border='1' cellpadding='2' cellspacing='0' align='center' style='border-collapse: collapse' bordercolor='#000000'>";
			echo "<tr class='Titulo'>";
			echo "<td colspan='8'>";
			fecho ("relacao.de.os.maiu", $con, $cook_idioma);
			echo "</td>";
			echo "</tr>";
			echo "<tr class='Titulo'><td>";
			fecho ("os", $con, $cook_idioma);
			echo "</td>";
			echo "<td>";
			fecho ("digitacao.maiu", $con, $cook_idioma);
			echo "</td>";
			echo "<td>"; #ABERTURA</td>";
			fecho ("abertura.maiu", $con, $cook_idioma);
			echo "</td>";
			echo "<td>";
			fecho ("fechamento.maiu", $con, $cook_idioma);
			echo "</td>";
			echo "<td>";#TOTAL</td>";
			fecho ("total.maiu", $con, $cook_idioma);
			echo "</td>";
			echo "<td>";#PROTOCOLO</td>";
			fecho ("protocolo.maiu", $con, $cook_idioma);
			echo "</td>";
			echo "<td>";#STATUS</td>";
			fecho ("status.maiu", $con, $cook_idioma);
			echo "</td>";
			if($login_fabrica == 1){
				echo "<td>";#TIPO OS</td>";
				fecho ("tipo.os", $con, $cook_idioma);
				echo "</td>";
			}
			echo "</tr>";

			for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
				$codigo_posto			 = trim(pg_fetch_result($res,$i,codigo_posto));
				$os						 = trim(pg_fetch_result($res,$i,os));
				$sua_os					 = trim(pg_fetch_result($res,$i,sua_os));
				$data_digitacao			 = trim(pg_fetch_result($res,$i,data_digitacao));
				$data_abertura			 = trim(pg_fetch_result($res,$i,data_abertura));
				$data_fechamento		 = trim(pg_fetch_result($res,$i,data_fechamento));
				$finalizada				 = trim(pg_fetch_result($res,$i,finalizada));
				$pecas					 = trim(pg_fetch_result($res,$i,pecas));
				$mao_de_obra			 = trim(pg_fetch_result($res,$i,mao_de_obra));
				$os_admin				 = trim(pg_fetch_result($res,$i,admin));
				$mao_de_obra_desconto	 = trim(pg_fetch_result($res,$i,mao_de_obra_desconto));
				$mao_de_obra_extra		 = trim(pg_fetch_result($res,$i,mao_de_obra_extra));

				if ($login_fabrica == 3){
					$total = ($mao_de_obra_extra - $mao_de_obra_desconto);
				} else{
					$total          = $pecas + $mao_de_obra;
				}
				$extrato          = trim(pg_fetch_result($res,$i,extrato));
				$exportado        = trim(pg_fetch_result($res,$i,exportado));
				$aprovado         = trim(pg_fetch_result($res,$i,aprovado));
				$data_geracao     = trim(pg_fetch_result($res,$i,data_geracao));
				$status_os        = trim(pg_fetch_result($res,$i,status_os));
				$observacao       = trim(pg_fetch_result($res,$i,observacao));
				$tipo_atendimento = trim(pg_fetch_result($res,$i,tipo_atendimento));
				$status_os_troca  = trim(pg_fetch_result($res,$i,status_os_troca));
				$tipo_os  = trim(pg_fetch_result($res,$i,tipo_os));

				$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

				//HD 15569 Para verificar recusada
				if($login_fabrica == 1) {
					$sql2 = "SELECT status_os FROM tbl_os_troca WHERE os = $os";
					$res2 = pg_query($con,$sql2);
					if(pg_num_rows($res2)>0){
						if(pg_fetch_result($res2,0,0)==15) continue;
					}
				}


				if(strlen($os_admin) >0 AND $login_fabrica == 15) $cor = "#FF9900";

				if ( ($login_fabrica == 19 AND $status_os == 13) OR
					 ($login_fabrica <> 19 AND $status_os == 13 AND strlen(trim($data_fechamento)) == 0) ) {
					$cor = "#FFE1E1";
				}

				$rowspan = "1";

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
				#if($sistema_lingua == "ES") echo "Fecha de cierre digitada";else echo "Data de fechamento digitada: ";
				fecho ("data.de.fechamento.digitada", $con, $cook_idioma);
				echo ": $data_fechamento' style='cursor: help;'>" . $finalizada . "</acronym></td>";
				echo "<td nowrap align='right'>" . number_format($total,2,",",".") . "</td>";
				if($login_fabrica==20){
					/*if ($sistema_lingua=='ES')*/echo "<td nowrap align='center' title='";
					fecho ("extrato.lote.em.que.a.os.foi.paga", $con, $cook_idioma); /*Extracto/ lote que fue paga la OS*/
					echo "'>" . $extrato . "</td>";
					#else echo "<td nowrap align='center' title='Extrato/Lote que a OS foi paga'>" . $extrato . "</td>";
				}
				else echo "<td nowrap align='center'>" . $os . "</td>";
				echo "<td nowrap align='center'>";

				if (($status == "00") or ($status == "99")) {
					if(strlen($data_geracao) >  0  AND strlen($aprovado) == 0){
						#if($sistema_lingua == 'ES') echo "En aprobación";else echo "Em aprovação";
						fecho ("em.aprovacao", $con, $cook_idioma);
					}elseif ($status_os == 92) {
						#echo "Aguardando Aprovação";
						fecho ("aguardando.aprovacao", $con, $cook_idioma);
					}elseif ($status_os == 93 and $tipo_atendimento==13) {
						#echo "Troca Aprovada";
						fecho ("troca.aprovada", $con, $cook_idioma);
					}elseif ($status_os == 94 and $tipo_atendimento==13) {
						#echo "Troca Recusada";
						fecho ("troca.recusada", $con, $cook_idioma);
					}elseif (strlen($data_geracao) == 0  AND strlen($aprovado) == 0 AND strlen($status_os) == 0 AND strlen(trim($data_fechamento)) <> 0){
						#if($sistema_lingua == 'ES') echo "Cerradas";else echo "Finalizada";
						fecho ("finalizada", $con, $cook_idioma);
					}elseif ($login_fabrica == 19 AND $status_os <> 13 AND strlen($aprovado) > 0) {
						#echo "Aprovada";
						fecho ("aprovada", $con, $cook_idioma);
					}elseif ($login_fabrica == 20 AND strlen($aprovado) > 0 AND strlen($extrato) > 0 AND strlen($exportado)>0){
		            	#if($sistema_lingua == 'ES') echo "Pagamiento efectuado";else echo "Pagamento efetuado";
						fecho ("pagamento.efetuado", $con, $cook_idioma);
					}elseif ($login_fabrica <> 19 AND $login_fabrica <> 1 AND strlen($aprovado) > 0 AND strlen($extrato) > 0){
		            	#if($sistema_lingua == 'ES') echo "Aprobadas";else echo "Aprovada";
						fecho ("aprovada", $con, $cook_idioma);
					}elseif ($login_fabrica == 1 AND strlen($aprovado) > 0 AND strlen($extrato) > 0 AND $status_os == 18){
						#echo "Em Análise";
						fecho ("em.analise", $con, $cook_idioma);
					}elseif ($login_fabrica == 20 AND $status_os == 13 ){
						#if($sistema_lingua == 'ES') echo "Rechazada";else echo "Recusada";
						fecho ("recusada", $con, $cook_idioma);
					}elseif ($login_fabrica == 20 AND $status_os == 14 ){
						#if($sistema_lingua == 'ES') echo "Acumulada";else echo "Acumulada";
						fecho ("acumulada", $con, $cook_idioma);
					}elseif ($login_fabrica == 1 AND strlen($aprovado) > 0 AND strlen($extrato) > 0 ){
						#if($sistema_lingua == 'ES') echo "Aprobadas";else echo "Aprovada";
						fecho ("aprovada", $con, $cook_idioma);
					}elseif ($login_fabrica == 19 AND $status_os == 13 AND strlen($extrato) > 0){
	                     #if($sistema_lingua == 'ES') echo "Rechazada";else echo "Recusada";
						 fecho ("recusada", $con, $cook_idioma);
					}elseif ($login_fabrica <> 19 AND $status_os == 13 AND strlen($extrato) == 0 AND strlen(trim($data_fechamento)) == 0){
						 /*if($sistema_lingua == 'ES') {
							 echo "Rechazada";
						 } else {*/
							 if ($status_os_troca=='t'){
								#echo "Troca Recusada";
								fecho ("troca.recusada", $con, $cook_idioma);
							 }else{
								#echo "Recusada";
								fecho ("recusada", $con, $cook_idioma);
							 #}
						 }
					}elseif ($login_fabrica <> 19 AND $status_os == 13 AND strlen($extrato) == 0 AND strlen(trim($data_fechamento)) > 0){
						#if($sistema_lingua == 'ES') echo "Cerradas";else echo "Finalizada";
						fecho ("finalizada", $con, $cook_idioma);
					}elseif ($status_os == 14 AND strlen($extrato) == 0){
					    #if($sistema_lingua == 'ES') echo "Acumulada";else echo "Acumulada";
						fecho ("acumulada", $con, $cook_idioma);
					}elseif ($status_os == 91 AND strlen($extrato) == 0){
					    #echo "Pendência Doc.";
						fecho ("pendencia.doc", $con, $cook_idioma);
					}elseif ($status_os == 15 AND strlen($extrato) == 0){
					    #if($sistema_lingua == 'ES') echo "Excluída";else echo "Excluída";
						fecho ("excluida", $con, $cook_idioma);
					}elseif ($login_fabrica == 20 AND strlen(trim($data_fechamento))>0 and strlen($extrato)==0){
						#if($sistema_lingua == 'ES') echo "Cerradas";else echo "Finalizada";
						fecho ("finalizada", $con, $cook_idioma);
					}
				}

				if ($status == "01") {
					if ($login_fabrica == 19 AND $status_os <> 13 AND strlen($aprovado) > 0){
						 #echo "Aprovada";
						fecho ("aprovada", $con, $cook_idioma);
					}elseif ($login_fabrica == 20 AND strlen($aprovado) > 0 AND strlen($extrato) > 0 AND strlen($exportado)>0){
						#if($sistema_lingua == 'ES') echo "Pagamiento efectuado";else  echo "Pagamento efetuado";
						fecho ("pagamento.efetuado", $con, $cook_idioma);
					}elseif ($login_fabrica <> 19 AND strlen($aprovado) > 0 AND strlen($extrato) > 0){
						#if($sistema_lingua == 'ES') echo "Aprobadas";else echo "Aprovada";
						fecho ("aprovada", $con, $cook_idioma);
					}
				}
				elseif ($status == "13") {
					if ($login_fabrica == 19 AND $status_os == 13 AND strlen($extrato) > 0){
						 #echo "Recusada";
						 fecho ("recusada", $con, $cook_idioma);
					}elseif ($login_fabrica <> 19 AND $status_os == 13 AND strlen($extrato) == 0 AND strlen(trim($data_fechamento)) == 0){
						/*if($sistema_lingua == 'ES') {
							echo "Rechazada";
						}else {*/
							if ($status_os_troca=='t'){
								#echo "Troca Recusada";
								fecho ("troca.recusada", $con, $cook_idioma);
							}else{
								#echo "Recusada";
								fecho ("recusada", $con, $cook_idioma);
							}
						#}
					}
				}
				elseif ($status == "14") {
					if ($status_os == 14 AND strlen($extrato) == 0){
						#if($sistema_lingua == 'ES') echo "Acumulada";else echo "Acumulada";
						fecho ("acumulada", $con, $cook_idioma);
					}
				}
				elseif ($status == "15") {
					if ($status_os == 15 AND strlen($extrato) == 0){
						#if($sistema_lingua == 'ES') echo "Excluída";else echo "Excluída";
						fecho ("excluida", $con, $cook_idioma);
					}
				}
				elseif ($status_os == "131" and $login_fabrica == 14) {
						fecho ("recusada", $con, $cook_idioma);
				}
//echo $status.$status_os;
				echo "</td>";
				if($login_fabrica == 1){
					echo "<td>$tipo_os</td>";
				}
				echo "</tr>\n";

				if ($login_fabrica == 19 AND strlen($observacao) > 0 AND strtoupper($observacao) <> "ACEITA" AND strtoupper($observacao) <> "IMPORTADA" AND $status_os == 13) {
					echo "<tr class='Conteudo' bgcolor='$cor'>";
					echo "<td colspan='7'><b>";
					fecho ("obs.fabrica", $con, $cook_idioma); /*Obs. Fábrica:*/
					echo ":</b>" . $observacao . "</td>";
					echo "</tr>";
				}elseif ($login_fabrica == 14 and strlen($aprovado) == 0 AND strlen($observacao) > 0) {
					echo "<tr class='Conteudo' bgcolor='$cor'>";
					echo "<td colspan='7'><b>";
					#if($sistema_lingua == 'ES') echo "Obs.: Planta:";else echo "Obs. Fábrica: ";
					fecho ("obs.fabrica", $con, $cook_idioma);
					echo ":</b>" . $observacao . "</td>";
					echo "</tr>";
				}elseif (strlen($aprovado) == 0 AND strlen($observacao) > 0 AND $status_os <> 14) {
					echo "<tr class='Conteudo' bgcolor='$cor'>";
					echo "<td colspan='7'><b>";
					#if($sistema_lingua == 'ES') echo "Obs.: Planta:";else echo "Obs. Fábrica: ";
					fecho ("obs.fabrica", $con, $cook_idioma);
					echo ":</b>" . $observacao . "</td>";
					echo "</tr>";
				}elseif($login_fabrica == 20 and strlen($aprovado) == 0 AND strlen($observacao) > 0){
					echo "<tr class='Conteudo' bgcolor='$cor'>";
					echo "<td colspan='7'><b>";
					#if($sistema_lingua == 'ES') echo "Obs.: Planta:";else echo "Obs. Fábrica: ";
					fecho ("obs.fabrica", $con, $cook_idioma);
					echo ":</b>" . $observacao . "</td>";
					echo "</tr>";
				}
				$extrato='';
			}

			if($login_fabrica == 1){
				$sql2 = "SELECT	tbl_os_status.observacao    ,
							tbl_os.sua_os               ,
							TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura
						FROM tbl_os_status
						JOIN tbl_os ON tbl_os.os = tbl_os_status.os
					WHERE tbl_os.data_digitacao::date BETWEEN '$x_data_inicial' AND '$x_data_final'
					AND status_os = '71'
					AND tbl_os.os = $os
					ORDER BY tbl_os.sua_os;";
				$res2 = pg_query($con,$sql2);

				if(pg_num_rows($res2) > 0){
					echo "<tr class='Conteudo' bgcolor='$cor'>";
					echo "<td colspan='7' style='font-size: 10px'><b>Obs. Fábrica: </b> <FONT COLOR='#FF3300'><u><b onClick=\"MostraEsconde('conteudo')\" style='cursor:pointer; cursor:hand;'>";
					fecho ("pecas.excluidas", $con, $cook_idioma); /*Peças excluídas*/
					echo "</b></u>:</FONT>";
					echo "<div id='conteudo' style='display: none;'>";
					echo "<table border='0' align='center' cellspacing='0' cellpadding='0' style='font-size: 10px'>";

					for($j = 0; $j < pg_num_rows($res2); $j++){

						$os_peca          = trim(pg_fetch_result($res2,$j,observacao));
						$os_sua_os        = trim(pg_fetch_result($res2,$j,sua_os));
						$os_data_abertura = trim(pg_fetch_result($res2,$j,data_abertura));

						$sqlZ = "SELECT tbl_peca.referencia    ,
										tbl_peca.descricao
									FROM tbl_peca
								WHERE tbl_peca.peca = '$os_peca'
								AND fabrica = $login_fabrica";
						$resZ = pg_query($con,$sqlZ);

						for($z = 0; $z < pg_num_rows($resZ); $z++){

							$os_peca_referencia  = pg_fetch_result($resZ,0,referencia);
							$os_peca_descricao   = pg_fetch_result($resZ,0,descricao);

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
	if ($status == "00" OR $status == "15" OR $status == "15a") {
		#HD 122900 acrescentado obs/motivo para fabrica 15 também
		if(in_array($login_fabrica,array(1,15,30,91,140))){ // HD 33253
			if ($login_fabrica <> 30){
				$obs=" , (SELECT tbl_os_status.observacao FROM tbl_os_status WHERE tbl_os_excluida.os = tbl_os_status.os AND status_os =15 order by data desc LIMIT 1) AS observacao ";
			}else{
				$obs=" , (SELECT tbl_os_status.observacao FROM tbl_os_status WHERE tbl_os_excluida.os = tbl_os_status.os AND status_os in(15,104) order by data desc LIMIT 1) AS observacao ";
			}
		}
		$sql = "SELECT  tbl_os_excluida.codigo_posto                                                        ,
						tbl_os_excluida.sua_os                                              ,
						tbl_os_excluida.referencia_produto                                  ,
						tbl_os_excluida.serie                                               ,
						tbl_os_excluida.nota_fiscal                                         ,
						tbl_os_excluida.admin                                               ,
						tbl_os_excluida.consumidor_nome                                     ,
						to_char(tbl_os_excluida.data_nf,'DD/MM/YYYY')       AS data_nf      ,
						tbl_admin.nome_completo                                             ,
						to_char(tbl_os_excluida.data_exclusao,'DD/MM/YYYY') AS data_exclusao,
						CASE
							WHEN tbl_os.cortesia IS TRUE THEN
								'Cortesia'
							WHEN tbl_os.tipo_atendimento = 35 THEN
								'Troca cortesia'
							WHEN tbl_os.consumidor_revenda = 'C' AND (tbl_os.tipo_atendimento = 17 OR tbl_os.tipo_atendimento = 18) THEN
								'Troca consumidor'
							WHEN tbl_os.consumidor_revenda = 'R' AND (tbl_os.tipo_atendimento = 17 OR tbl_os.tipo_atendimento = 18) THEN
								'Troca de revenda'
							WHEN tbl_os.consumidor_revenda = 'R' THEN
								'Revenda'
							ELSE
								'Consumidor'
						END AS tipo_os
						$obs
				FROM    tbl_os_excluida
				LEFT JOIN tbl_os_status USING (os)
				LEFT JOIN tbl_admin ON tbl_os_excluida.admin = tbl_admin.admin
				JOIN    tbl_os ON tbl_os_excluida.os = tbl_os.os
				WHERE   tbl_os_excluida.fabrica = $login_fabrica
				AND     tbl_os_excluida.posto   = $login_posto";
		if ($status == '15a') {
			$sql .= " AND tbl_os_excluida.admin is not null ";
		}
		else if ($status == '00'){
			//opção todas se continuar assim quando a OS é excluida e grava o admin não está mostrando (MARISA-25/03/2011)
		}
		else {
			if($login_fabrica <> 40){
				$sql .= " AND tbl_os_excluida.admin is null ";
			}
		}
		if(strlen($x_data_inicial) >0 AND strlen($x_data_final) >0){
			$sql.="AND     tbl_os_excluida.data_exclusao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
		}
		if($radio_cons == "sua_os") {
			$sql .=" AND tbl_os_excluida.sua_os = '$x_sua_os' ";
		}
		$sql .=" group by tbl_os_excluida.os              ,
						  tbl_os_excluida.codigo_posto    ,
						tbl_os_excluida.sua_os            ,
						tbl_os_excluida.referencia_produto,
						tbl_os_excluida.serie             ,
						tbl_admin.nome_completo           ,
						tbl_os_excluida.nota_fiscal       ,
						tbl_os_excluida.admin             ,
						tbl_os_excluida.data_nf           ,
						data_exclusao                     ,
						tbl_os.tipo_atendimento           ,
						tbl_os.consumidor_revenda         ,
						tbl_os_excluida.consumidor_nome         ,
						tbl_os.cortesia
		ORDER BY tbl_os_excluida.data_exclusao ";


		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0) {
			echo "<br>";
			echo "<input type='hidden' name='qtde_os' value='" . pg_num_rows($res) . "'>";

			if($login_fabrica==1){
				echo "<table width='650' border='0' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
					echo "<TR>";
						echo "<TD width='20' bgcolor='#FFE1E1'>&nbsp;</TD>";
						echo "<TD>";
						fecho ("oss.excluidas.pelo.posto", $con, $cook_idioma); /*OSs excluidas pelo posto.*/
						echo "</TD>";
					echo "</TR>";
					echo "</TABLE>";
				echo "<BR>";
			}

			echo "<table width='700' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
			echo "<tr class='Titulo'>";
			echo "<td colspan='8'>";
			#if($sistema_lingua == 'ES') echo "RELACIÓN DE EXCLUÍDAS";else echo "RELAÇÃO DE OS EXCLUÍDAS";
			fecho ("relacao.de.os.excluidas", $con, $cook_idioma);
			echo "</td>";
			echo "</tr>";
			echo "<tr class='Titulo'>";
			#echo "<td>OS</td>";
			echo "<td>";
			fecho ("os", $con, $cook_idioma);
			echo "</td>";
			#if($sistema_lingua == 'ES') echo "PRODUCTO";else echo "PRODUTO";
			echo "<td>";
			fecho ("produto.maiu", $con, $cook_idioma);
			echo "</td>";
			#echo "<td>SÉRIE</td>";
			echo "<td>";
			fecho ("serie.maiu", $con, $cook_idioma);
			echo "</td>";
			echo "<td>";
			fecho ("nome.consumidor", $con, $cook_idioma);
			echo "</td>";
			#if($sistema_lingua == 'ES') echo "FACTURA COMERCIAL";else echo "NOTA FISCAL";
			echo "<td>";
			fecho ("nota.fiscal.maiu", $con, $cook_idioma);
			echo "</td>";
			echo "<td>";
			#if($sistema_lingua == 'ES') echo "FECHA FACTURA";else echo "DATA NF";
			fecho ("data.nf.maiu", $con, $cook_idioma);
			echo "</td>";
			echo "<td>";
			#if($sistema_lingua == 'ES') echo "FECHA EXCLUSIÓN";else echo "DATA EXCLUSÃO";
			fecho ("data.exclusao.maiu", $con, $cook_idioma);
			echo "</td>";
			#echo "<td>STATUS</td>";
			echo "<td>";
			fecho ("status.maiu", $con, $cook_idioma);
			echo "</td>";
			if ($login_fabrica == 30) {
				echo "<td>";
					fecho ("admin", $con, $cook_idioma);
				echo "</td>";
			}
			if($login_fabrica == 1){
				echo "<td>";#TIPO OS</td>";
				fecho ("tipo.os", $con, $cook_idioma);
				echo "</td>";
			}
			echo "</tr>";

			for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
				$codigo_posto   = trim(pg_fetch_result($res,$i,codigo_posto));
				$sua_os         = trim(pg_fetch_result($res,$i,sua_os));
				$produto        = trim(pg_fetch_result($res,$i,referencia_produto));
				$nome           = trim(pg_fetch_result($res,$i,nome_completo));
				$serie          = trim(pg_fetch_result($res,$i,serie));
				$nota_fiscal    = trim(pg_fetch_result($res,$i,nota_fiscal));
				$data_nf        = trim(pg_fetch_result($res,$i,data_nf));
				$data_exclusao  = trim(pg_fetch_result($res,$i,data_exclusao));
				$admin          = trim(pg_fetch_result($res,$i,admin));
				$consumidor_nome          = trim(pg_fetch_result($res,$i,'consumidor_nome'));
				$tipo_os        = trim(pg_fetch_result($res,$i,tipo_os));

				if(in_array($login_fabrica,array(1,15,30,91))){ // HD 33253
					$observacao     = trim(pg_fetch_result($res,$i,observacao));
				}

				if(strpos($observacao,'da pelo posto')!==false){
					list($obs1,$obs2) = explode("motivo:",$observacao);
					$observacao = $obs1." motivo: " .utf8_decode($obs2);
				}
				$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

				if($login_fabrica==1){
					if (strlen($admin)==0) {
						$cor = "#FFE1E1";
					}
				}else{
					if ($status == "00" OR $status == "15") {
						$cor = "#FFE1E1";
					}
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
				echo "<td nowrap align='right'>" . $consumidor_nome . "</td>";
				echo "<td nowrap align='right'>" . $nota_fiscal . "</td>";
				echo "<td nowrap align='right'>" . $data_nf . "</td>";
				echo "<td nowrap align='center'>" . $data_exclusao . "</td>";
				echo "<td nowrap align='center'>Excluída</td>";
				if ($login_fabrica == 30) {
					echo "<td nowrap align='center'>$nome</td>";
				}
				if ($login_fabrica == 1) {
					echo "<td nowrap align='center'>$tipo_os</td>";
				}
				echo "</tr>";
				#HD 122900
				if(in_array($login_fabrica,array(1,15,30,91))){ // HD 33253
					echo "<tr class='Conteudo' bgcolor='$cor'>";
					echo "<td align='left' colspan='100%'>" . $observacao . "</td>";
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

	if ($achou == "nao") {
		echo "<table border='0' cellpadding='2' cellspacing='0' align='center'>";
		echo "<tr height='50'>";
		echo "<td valign='middle' align='center'><img src='imagens/atencao.gif' border='0'>
			<font size=\"2\"><b>";
		/*if($sistema_lingua == 'ES') echo "No fueran encuentrados registros con parámetros informados/digitados!!!";
		else
		echo "Não foram encontrados OS's excluídas com os parâmetros informados/digitados!!!";*/

		echo traduz('Não foram encontradas OS excluídas para os parâmetros informados!');

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
						tbl_os.obs                                                       ,
						TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')    AS data_digitacao ,
						TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')     AS data_abertura  ,
						tbl_os.mao_de_obra                                               ,
						tbl_os_extra.mao_de_obra                       AS mao_de_obra_extra,
						tbl_os_extra.mao_de_obra_desconto                                ,
						tbl_os.pecas                                                     ,
						tbl_os.data_fechamento                                           ,
						CASE
							WHEN tbl_os.cortesia IS TRUE THEN
								'Cortesia'
							WHEN tbl_os.tipo_atendimento = 35 THEN
								'Troca cortesia'
							WHEN tbl_os.consumidor_revenda = 'C' AND (tbl_os.tipo_atendimento = 17 OR tbl_os.tipo_atendimento = 18) THEN
								'Troca consumidor'
							WHEN tbl_os.consumidor_revenda = 'R' AND (tbl_os.tipo_atendimento = 17 OR tbl_os.tipo_atendimento = 18) THEN
								'Troca de revenda'
							WHEN tbl_os.consumidor_revenda = 'R' THEN
								'Revenda'
							ELSE
								'Consumidor'
						END AS tipo_os,
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




		//CONSULTA POR OS OU POR SUA_OS
		if($radio_cons == "data") {
			$sql .=" 				WHERE tbl_os.data_digitacao::date BETWEEN '$x_data_inicial' AND '$x_data_final'";
		}else {
				$sql .=" WHERE tbl_os.sua_os = '$x_sua_os' ";
		}

		$sql.=" AND tbl_os.finalizada      ISNULL
				AND tbl_os.data_fechamento ISNULL
				AND tbl_os_extra.extrato   ISNULL
				AND tbl_os.excluida = 'f'
				AND tbl_os.posto   = $login_posto
				AND tbl_os.fabrica = $login_fabrica;";

//foi adicionado a linha acima AND tbl_os.excluida = 'f' para nao pegar as os que ja foram excluidas.

	//	echo "QUERY 2 <BR><BR>";
//echo nl2br($sql);
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0) {
			echo "<table width='700' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
			echo "<tr class='titulo_tabela'>";
			echo "<td colspan='7'>";
			#if($sistema_lingua == 'ES') echo "RELACIÓN DE NO CERRADAS";else echo "RELAÇÃO DE OS NÃO FINALIZADAS";
			fecho ("relacao.de.oss.nao.finalizadas", $con, $cook_idioma);
			echo "</td>";
			echo "</tr>";
			echo "<tr class='titulo_coluna'>";
			echo "<td>";
			#OS
			echo "OS";
			echo "</td>";
			echo "<td>";
			#if($sistema_lingua == 'ES') echo "DIGITALIZACIÓN";else echo "DIGITAÇÃO";
			echo "Digitação";
			echo "</td>";
			echo "<td>";
			echo "Abertura";
			echo "</td>";
			echo "<td>";
			echo "Total";
			echo "</td>";
			echo "<td>";
			echo "Protocolo";
			echo "</td>";
			echo "<td>";
			echo "Status";
			echo "</td>";
			if($login_fabrica == 1){
				echo "<td>";
				echo "Tipo OS";
				echo "</td>";
			}
			echo "</tr>";

			$extrato = '';
			for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
				$codigo_posto		  = trim(pg_fetch_result($res,$i,codigo_posto));
				$os					  = trim(pg_fetch_result($res,$i,os));
				$sua_os				  = trim(pg_fetch_result($res,$i,sua_os));
				$data_digitacao		  = trim(pg_fetch_result($res,$i,data_digitacao));
				$data_abertura		  = trim(pg_fetch_result($res,$i,data_abertura));
				$pecas				  = trim(pg_fetch_result($res,$i,pecas));
				$mao_de_obra_extra    = trim(pg_fetch_result($res,$i,mao_de_obra_extra));
				$mao_de_obra_desconto = trim(pg_fetch_result($res,$i,mao_de_obra_desconto));
				$mao_de_obra		  = trim(pg_fetch_result($res,$i,mao_de_obra));
				$tipo_os			  = trim(pg_fetch_result($res,$i,tipo_os));

				if ($login_fabrica == 3){
					$total = ($mao_de_obra_extra - $mao_de_obra_desconto);
				} else{
					$total          = $pecas + $mao_de_obra;
				}

				$status_os		     = trim(pg_fetch_result($res,$i,status_os));
				$observacao			 = trim(pg_fetch_result($res,$i,observacao));
				$obs				 = trim(pg_fetch_result($res,$i,obs));
				$data_fechamento	 = trim(pg_fetch_result($res,$i,data_fechamento));

				$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

				if ($status_os == 13) {
					$cor = "#FFE1E1";
					$rowspan = "1";
				}else{
					$rowspan = "1";
				}

				echo "<tr class='Conteudo' bgcolor='$cor'";

				if (($status_os == 14 OR $status_os == 13) AND $login_fabrica<>20) echo " onclick=\"javascript: window.location='os_consulta_lite.php?btn_acao=PESQUISAR&sua_os=$sua_os';\" style='cursor: hand;' TITLE='";
				fecho ("clique.para.acessar.a.os", $con, $cook_idioma); #CLIQUE PARA ACESSAR A OS
				echo "'";
				echo ">";
				echo "<td rowspan='$rowspan'>";
				if ($login_fabrica == 1) echo $codigo_posto;
				echo (strlen($sua_os)==0 AND $login_fabrica==20) ? $os : $sua_os;
				echo "</td>";
				echo "<td nowrap align='center'>" . $data_digitacao . "</td>";
				echo "<td nowrap align='center'>" . $data_abertura . "</td>";
				echo "<td nowrap align='right'>" . number_format($total,2,",",".") . "</td>";
				echo "<td nowrap align='center'>" . $os . "</td>";
				echo "<td nowrap align='center'>";
				if ($status_os == 13 and strlen($extrato) == 0) {
					#if ($sistema_lingua=='ES') echo "Rechazada";// (Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
					#echo "Recusada";// (Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
					fecho ("recusada", $con, $cook_idioma);
				}
				elseif ($status_os == 14 and strlen($extrato) == 0)  fecho ("acumulada", $con, $cook_idioma);  #echo "Acumulada";//(Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
				elseif ($status_os == 15 and strlen($extrato) == 0)  fecho ("excluida", $con, $cook_idioma); #echo "Excluída";// (Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
				elseif ($status_os == 62 and strlen($extrato) == 0) {
					fecho ("intervencao.de.fabrica", $con, $cook_idioma);
					#if ($sistema_lingua=='ES') echo "Intervención de la planta";// (Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
					#else echo "Intervenção da Fábrica";// (Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
				}elseif ($status_os == 91 AND strlen($extrato) == 0){
					#echo "Pendência Doc.";
					fecho ("pendencia.doc", $con, $cook_idioma);
				}elseif ($status_os == 65 and strlen($extrato) == 0) {
					#if ($sistema_lingua=='ES') echo "Reparo en la planta";// (Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
					#else echo "Reparo na Fábrica";// (Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
					fecho ("reparo.na.fabrica", $con, $cook_idioma);
				} elseif ($status_os == 120){
					echo "Bloqueada";
				}
				else    fecho ("aguardando", $con, $cook_idioma); #echo "Aguardando";//finalização (Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
				echo "</td>";
				if($login_fabrica == 1){
					echo "<td>$tipo_os</td>";
				}
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
					#HD 111073
					if($login_fabrica==20){
						$sql_y = "select observacao, extrato from tbl_os_status where os=$os and extrato IS NOT NULL;";
						$res_y = pg_query($con,$sql_y);
						if (pg_num_rows($res_y) > 0){
							for ($w = 0 ; $w < pg_num_rows($res_y) ; $w++) {
								$nums = $w+1;
								$obs_w   = trim(pg_fetch_result($res_y,$w,observacao));
								$extrato_w   = trim(pg_fetch_result($res_y,$w,extrato));
								echo "<tr class='Conteudo' bgcolor='$cor'>";
								echo "<td colspan='6'><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Obs.".$nums." Extrato : $extrato_w; </b>" . $obs_w . "</td>";
								echo "</tr>";
							}
						}
					}
				}
			}
			echo "</table>";
			echo "<br>";
		}
	}
	##### OS SEDEX FINALIZADAS #####

	if($radio_cons == "data"){
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
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0) {
			echo "<table width='700' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
			echo "<tr class='titulo_tabela'>";
			echo "<td colspan='7'>";
			fecho ("relacao.de.os.sedex", $con, $cook_idioma); #RELAÇÃO DE OS SEDEX
			echo "</td>";
			echo "</tr>";
			echo "<tr class='titulo_coluna'>";
			echo "<td>";
			fecho ("os.sedex", $con, $cook_idioma); #OS SEDEX
			echo "</td>";
			echo "<td>";
			fecho ("digitacao.maiu", $con, $cook_idioma); #DIGITAÇÃO
			echo "</td>";
			echo "<td>";
			fecho ("pecas.maiu", $con, $cook_idioma); #PEÇAS
			echo "</td>";
			echo "<td>";
			fecho ("despesas.maiu", $con, $cook_idioma); #DESPESAS
			echo "</td>";
			echo "<td>";
			fecho ("total.maiu", $con, $cook_idioma); #TOTAL
			echo "</td>";
			echo "<td>";
			fecho ("protocolo.maiu", $con, $cook_idioma); #PROTOCOLO
			echo "</td>";
			echo "<td>";
			fecho ("status.maiu", $con, $cook_idioma); #STATUS
			echo "</td>";
			echo "</tr>";

			for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
				$codigo_posto   = trim(pg_fetch_result($res,$i,codigo_posto));
				$os_sedex       = trim(pg_fetch_result($res,$i,os_sedex));
				$xos_sedex      = "00000".$os_sedex;
				$xos_sedex      = substr($xos_sedex,strlen($xos_sedex) - 5,strlen($xos_sedex));
				$sua_os         = trim(pg_fetch_result($res,$i,sua_os_origem));
				$sua_os_destino = trim(pg_fetch_result($res,$i,sua_os_destino));
				$data_digitacao = trim(pg_fetch_result($res,$i,data_digitacao));
				$pecas          = trim(pg_fetch_result($res,$i,total_pecas));
				$despesas       = trim(pg_fetch_result($res,$i,despesas));
				$total          = trim(pg_fetch_result($res,$i,total));
				$aprovado       = trim(pg_fetch_result($res,$i,aprovado));
				$data_geracao   = trim(pg_fetch_result($res,$i,data_geracao));
				$observacao     = trim(pg_fetch_result($res,$i,observacao));
				$status_os      = trim(pg_fetch_result($res,$i,status_os));

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
					$res2   = pg_query($con, $sql2);
					$cr_sua_os = pg_fetch_result($res2, 0, sua_os);
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
					/*if ($sistema_lingua=='ES') {
						echo "En aprobación";*/// (Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
					/*} else {
						echo "Em aprovação";*/// (Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
					//}
					fecho ("em.aprovacao", $con, $cook_idioma);
				} elseif (strlen($data_geracao) == 0 AND strlen($aprovado) == 0 AND strlen($status_os) == 0) {
					/*if ($sistema_lingua=='ES') {
						echo "En aprobación";*///(Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
					/*} else {
						echo "Em aprovação";*///(Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
					#}
					fecho ("em.aprovacao", $con, $cook_idioma);
				} elseif (strlen($aprovado) > 0)    fecho ("aprovada", $con, $cook_idioma); # echo "Aprovada";//(Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
				elseif ($status_os == 13)    fecho ("recusada", $con, $cook_idioma); #echo "Recusada";//(Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
				elseif ($status_os == 14)    fecho ("acumulada", $con, $cook_idioma); #echo "Acumulada";//(Sts=".$status_os.") (geracao=".$data_geracao.") (aprovado=".$aprovado.") (extrato=".$extrato.") (fechamento=".$data_fechamento.")";
				elseif ($status_os == 15){
					fecho ("excluida", $con, $cook_idioma); #echo "Excluída
					echo ":<br><font color='#FF0000'>$observacao</font>";
				}elseif ($status_os == 62)    fecho ("intervencao.da.fabrica", $con, $cook_idioma); #echo "Intervenção da Fábrica";
				elseif ($status_os == 65)    fecho ("reparo.na.fabrica", $con, $cook_idioma); #echo "Reparo na Fábrica";
				echo "</td>";
				echo "</tr>";

				if ($status_os == 13 AND $login_fabrica == 1) {
					echo "<tr class='Conteudo' bgcolor='$cor'>";
					echo "<td colspan='6'><b>";
					fecho ("obs.fabrica", $con, $cook_idioma); #Obs. Fábrica:
					echo "</b>" . $observacao . "</td>";
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
				WHERE tbl_os_sedex.data_digitacao::date BETWEEN '$x_data_inicial' AND '$x_data_final'
				AND tbl_os_sedex.finalizada   ISNULL
				AND tbl_os_sedex.posto_origem = $login_posto
				AND tbl_os_sedex.fabrica      = $login_fabrica";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0) {
			echo "<table width='700' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
			echo "<tr class='titulo_tabela'>";
			echo "<td colspan='7'>";
			fecho ("relacao.de.os.sedex.nao.finalizadas", $con, $cook_idioma); #RELAÇÃO DE OS SEDEX NÃO FINALIZADAS
			echo "</td>";
			echo "</tr>";
			echo "<tr class='titulo_coluna'>";
			echo "<td>";
			fecho ("os.sedex", $con, $cook_idioma); #OS SEDEX
			echo "</td>";
			echo "<td>";
			fecho ("digitacao.maiu", $con, $cook_idioma); #DIGITAÇÃO
			echo "</td>";
			echo "<td>";
			fecho ("pecas.maiu", $con, $cook_idioma); #PEÇAS
			echo "</td>";
			echo "<td>";
			fecho ("despesas.maiu", $con, $cook_idioma); #DESPESAS
			echo "</td>";
			echo "<td>";
			fecho ("total.maiu", $con, $cook_idioma); #TOTAL
			echo "</td>";
			echo "<td>";
			fecho ("protocolo.maiu", $con, $cook_idioma); #PROTOCOLO
			echo "</td>";
			echo "<td>";
			fecho ("status.maiu", $con, $cook_idioma); #STATUS
			echo "</td>";
			echo "</tr>";

			for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
				$codigo_posto   = trim(pg_fetch_result($res,$i,codigo_posto));
				$os_sedex       = trim(pg_fetch_result($res,$i,os_sedex));
				$xos_sedex      = "00000".$os_sedex;
				$xos_sedex      = substr($xos_sedex,strlen($xos_sedex) - 5,strlen($xos_sedex));
				$sua_os         = trim(pg_fetch_result($res,$i,sua_os_origem));
				$data_digitacao = trim(pg_fetch_result($res,$i,data_digitacao));
				$pecas          = trim(pg_fetch_result($res,$i,total_pecas));
				$despesas       = trim(pg_fetch_result($res,$i,despesas));
				$total          = trim(pg_fetch_result($res,$i,total));
				$aprovado       = trim(pg_fetch_result($res,$i,aprovado));
				$data_geracao   = trim(pg_fetch_result($res,$i,data_geracao));
				$observacao     = trim(pg_fetch_result($res,$i,observacao));
				$status_os      = trim(pg_fetch_result($res,$i,status_os));

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
					echo "<td colspan='6'><b>";
					fecho ("obs.fabrica", $con, $cook_idioma); #Obs. Fábrica:
					echo "</b>" . $observacao . "</td>";
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
			$res = pg_query($con,$sql);

			if (pg_num_rows($res) > 0) {
				echo "<table width='700' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
				echo "<tr class='titulo_tabela'>";
				echo "<td colspan='7'>";
				fecho ("relacao.de.os.sedex.nao.finalizadas", $con, $cook_idioma); #RELAÇÃO DE OS SEDEX NÃO FINALIZADAS
				echo "</td>";
				echo "</tr>";
				echo "<tr class='titulo_coluna'>";
				echo "<td>";
				fecho ("os.sedex", $con, $cook_idioma); #OS SEDEX
				echo "</td>";
				echo "<td>";
				fecho ("digitacao.maiu", $con, $cook_idioma); #DIGITAÇÃO
				echo "</td>";
				echo "<td>";
				fecho ("pecas.maiu", $con, $cook_idioma); #PEÇAS
				echo "</td>";
				echo "<td>";
				fecho ("despesas.maiu", $con, $cook_idioma); #DESPESAS
				echo "</td>";
				echo "<td>";
				fecho ("total.maiu", $con, $cook_idioma); #TOTAL
				echo "</td>";
				echo "<td>";
				fecho ("protocolo.maiu", $con, $cook_idioma); #PROTOCOLO
				echo "</td>";
				echo "<td>";
				fecho ("status.maiu", $con, $cook_idioma); #STATUS
				echo "</td>";
				echo "</tr>";

				for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
					$codigo_posto   = trim(pg_fetch_result($res,$i,codigo_posto));
					$os_sedex       = trim(pg_fetch_result($res,$i,os_sedex));
					$xos_sedex      = "00000".$os_sedex;
					$xos_sedex      = substr($xos_sedex,strlen($xos_sedex) - 5,strlen($xos_sedex));
					$sua_os         = trim(pg_fetch_result($res,$i,sua_os_origem));
					$data_digitacao = trim(pg_fetch_result($res,$i,data_digitacao));
					$pecas          = trim(pg_fetch_result($res,$i,total_pecas));
					$despesas       = trim(pg_fetch_result($res,$i,despesas));
					$total          = trim(pg_fetch_result($res,$i,total));
					$aprovado       = trim(pg_fetch_result($res,$i,aprovado));
					$data_geracao   = trim(pg_fetch_result($res,$i,data_geracao));
					$observacao     = trim(pg_fetch_result($res,$i,observacao));
					$status_os      = trim(pg_fetch_result($res,$i,status_os));

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
					if($status_os == 13 ){
						echo "<td align='center'>";
						fecho ("recusada", $con, $cook_idioma); #Recusada
						echo "</td>";
					}else{
						echo "<td align='center'>";
						fecho ("nao.finalizada", $con, $cook_idioma); #Não finalizada
						echo "</td>";
					}
					echo "</tr>";
					if ($status_os == 13 AND $login_fabrica == 1) {
						echo "<tr class='Conteudo' bgcolor='$cor'>";
						echo "<td colspan='7'><b>";
						fecho ("obs.fabrica", $con, $cook_idioma); #Obs. Fábrica:
						echo "</b>" . $observacao . "</td>";
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
