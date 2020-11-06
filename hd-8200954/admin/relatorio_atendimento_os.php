<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "funcoes.php";

$admin_privilegios="gerencia";
include "autentica_admin.php";
 

$msg = "";

if ($_GET['ajax']== "true" && strlen($_GET['chamado']) > 0 ){
	$erro          = array();
	$hdChamado     = $_GET['chamado'];

	$res = pg_query($con,'BEGIN');

	
	$sql = "UPDATE tbl_hd_chamado SET resolvido = current_date where hd_chamado = $hdChamado ";
	$res = pg_query($con,$sql);

	if (pg_last_error($con)){
		$erro[] = pg_last_error($con) ;
	}

	if (count($erro)>0){
		$erro = implode('<br>ttt', $erro);
		$res = pg_query($con,'ROLLBACK TRANSACTION');
	}else{
		$res = pg_query($con,'COMMIT TRANSACTION');
	}

	if ($erro){
		echo "1|$erro";
	}else{
		echo "0|Sucesso";
	}

	exit;

}

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);


$acao = empty($acao) ? $_GET['acao'] : $acao; // HD 759089 - Se nao passar valor, variavel nao tem valor :-)

if (strlen($acao) > 0) {
	
	$data_inicial = trim (strtoupper ($_POST['data_inicial']));
	$data_final = trim (strtoupper ($_POST['data_final']));
	$estado =  trim (strtoupper ($_POST['estado']));
	$resolvidos =  ($_POST['resolvidos']);

	if (strlen($data_inicial)==0) $data_inicial = trim(strtoupper($_GET['data_inicial']));
	if (strlen($data_final)==0) $data_final = trim(strtoupper($_GET['data_final']));
	if (strlen($estado)==0) $estado = trim(strtoupper($_GET['estado']));
	if (strlen($resolvidos)==0) $resolvidos = ($_GET['resolvidos']);


	if($data_inicial && $data_final){
	        list($di, $mi, $yi) = explode("/", $data_inicial);
	        if(!checkdate($mi,$di,$yi))
	            $msg = "Data Inválida";

	        list($df, $mf, $yf) = explode("/", $data_final);
	        if(!checkdate($mf,$df,$yf))
	            $msg = "Data Inválida";

	        $aux_data_inicial = "$yi-$mi-$di";
	        $aux_data_final = "$yf-$mf-$df";

	        if(strlen($msg)==0){
	            if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
	                $msg = "Data Inválida.";
	            }
	        }

	    }else if(($data_inicial && !$data_final) || (!$data_inicial && $data_final)){
	        $msg = "Data Inválida.";
	    }


}

$layout_menu = "gerencia";
$title = "RELATÓRIO ATENDIMENTO X OS";

include "cabecalho.php";
include "javascript_pesquisas.php" 
?>

<style type="text/css">
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
.subtitulo{
	color: #7092BE
}
table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
</style>

<form name="frm_relatorio" method="get" action="<?echo $PHP_SELF?>">
<input type="hidden" name="acao">

<table width="700" align="center" border="0" cellspacing="0" cellpadding="0" class="formulario">
	<? if (strlen($msg) > 0) { ?>
		<tr class="msg_erro">
		<td colspan="4"><?echo $msg?></td>
	</tr>
	<? } ?>
	<tr class="titulo_tabela" height="20">
		<td colspan="4">Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td colspan="4" width="100">&nbsp;</td>
	</tr>
			<script src="js/jquery-1.8.3.min.js"></script>
			<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
			<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
			<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
			<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
			<link type="text/css" href="../plugins/jquery/datepick/telecontrol.datepick.css" rel="stylesheet" />
			<script src="js/jquery.tabs.pack.js" type="text/javascript"></script>
			<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
			<script type="text/javascript" src="js/jquery.blockUI_2.39.js"></script>
			<script type="text/javascript" src="js/jquery.mask.js"></script>

			<link rel="stylesheet" href="js/jquery.tabs.css" type="text/css" media="print, projection, screen">

			<script type="text/javascript" charset="utf-8">


				$(function(){
					$("input[rel='data_pesq']").datepick({startDate:"01/01/2000"});
					$("input[rel='data_pesq']").mask("99/99/9999");

					$("button[id^=resolve_]").click(function(){
						var hd_chamado;
						var atendimento = $(this).parents("tr");
						hd_chamado = $.trim($(atendimento).find("td[id^=atendimento_]").find("a").text());
						$.ajax({
							url: "<?php echo $PHP_SELF; ?>",
							type: "GET",
							data:  { 
									ajax : true ,
									chamado : hd_chamado ,
									resolve : true
									},
						}).done( function(data) {
								results = data.split('|');
								if (results[0] == 0){
									$(atendimento).remove();
								}
							})

					});
				});
			</script>

			
		<tr>
			<td width='170'>&nbsp;</td>
			<td nowrap>Data inicial</td>
			<td nowrap>Data final</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><input class='frm' type='text' name='data_inicial' rel='data_pesq' value='<?=$data_inicial?>' size='12' maxlength='20'></td>
			<td><input class='frm' type='text' name='data_final' rel='data_pesq' value='<?=$data_final?>' size='12' maxlength='20'></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td colspan="4">&nbsp;</td>
		</tr>
		<tr>
			<td width='170'>&nbsp;</td>
			<td nowrap>Estado</td>
			<td nowrap>Acompanhamentos concluídos</td>
			<td>&nbsp;</td>
		</tr>
		<TR>
		<td width="170">&nbsp;</td>
			<td colspan = '1' >
				
				<select name="estado" class="frm">
					<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>>TODOS OS ESTADOS</option>
					<option value="AC" <? if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
					<option value="AL" <? if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
					<option value="AM" <? if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
					<option value="AP" <? if ($estado == "AP") echo " selected "; ?>>AP - Amapá</option>
					<option value="BA" <? if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
					<option value="CE" <? if ($estado == "CE") echo " selected "; ?>>CE - Ceará</option>
					<option value="DF" <? if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
					<option value="ES" <? if ($estado == "ES") echo " selected "; ?>>ES - Espírito Santo</option>
					<option value="GO" <? if ($estado == "GO") echo " selected "; ?>>GO - Goiás</option>
					<option value="MA" <? if ($estado == "MA") echo " selected "; ?>>MA - Maranhão</option>
					<option value="MG" <? if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
					<option value="MS" <? if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
					<option value="MT" <? if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
					<option value="PA" <? if ($estado == "PA") echo " selected "; ?>>PA - Pará</option>
					<option value="PB" <? if ($estado == "PB") echo " selected "; ?>>PB - Paraíba</option>
					<option value="PE" <? if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
					<option value="PI" <? if ($estado == "PI") echo " selected "; ?>>PI - Piauí</option>
					<option value="PR" <? if ($estado == "PR") echo " selected "; ?>>PR - Paraná</option>
					<option value="RJ" <? if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
					<option value="RN" <? if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
					<option value="RO" <? if ($estado == "RO") echo " selected "; ?>>RO - Rondônia</option>
					<option value="RR" <? if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
					<option value="RS" <? if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
					<option value="SC" <? if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
					<option value="SE" <? if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
					<option value="SP" <? if ($estado == "SP") echo " selected "; ?>>SP - São Paulo</option>
					<option value="TO" <? if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
				</select>
				
			</td>
			<td>
				<input class='frm' type='checkbox' name='resolvidos' value='t' <?php if(strlen($resolvidos) >0 ) { echo "checked"; } ?>  >
			</td>
		</TR>
		<tr>
			<td colspan="4">&nbsp;</td>
		</tr>
			<td colspan="4" align="center"><input type="button" value="Pesquisar" onclick="document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor: pointer;" alt="Clique AQUI para pesquisar"></td>
		</tr>
		<tr><td colspan="4">&nbsp;</td></tr>
</table>

</form>

<br>
<?


if (strlen($acao) > 0 && strlen($msg) == 0) {
	if (!$resolvidos) {
		
		if (strlen($estado) > 0){
			$join_estado = "JOIN tbl_cidade on tbl_hd_chamado_extra.cidade = tbl_cidade.cidade";
			$cond_estado = " AND UPPER(tbl_cidade.estado) = '$estado' ";

		}		

		$sql_relatorio= " SELECT 	tbl_hd_chamado.hd_chamado,
									tbl_hd_chamado_extra.os,
									tbl_hd_chamado_extra.nome as consumidor_nome,
									tbl_hd_chamado.admin,
									tbl_hd_chamado.data::date AS data_abertura,
					(CURRENT_DATE - tbl_hd_chamado.data::date) AS dias_aberto,
									tbl_hd_chamado.resolvido 
								INTO TEMP tmp_atendimento_suggar_$login_admin
							FROM tbl_hd_chamado
								LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
								$join_estado
							WHERE tbl_hd_chamado.fabrica = $login_fabrica
							AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial' and '$aux_data_final'
							-- AND  UPPER(tbl_hd_chamado.titulo) ilike UPPER('%o de Posto')
							AND  UPPER(TO_ASCII(tbl_hd_chamado.titulo,'LATIN9')) = UPPER(TO_ASCII('Indicação de Posto','LATIN9'))
							$cond_estado ";
						// echo nl2br($sql_relatorio);exit;
		$res_relatorio = pg_query($con,$sql_relatorio);
		$sql_temp = "SELECT * FROM tmp_atendimento_suggar_$login_admin ";
		$res_temp = pg_query($con,$sql_temp);
		$count = pg_num_rows($res_temp);
		if ($count > 0 ){
		
			$sql = "		SELECT	TEMP.hd_chamado,
								TEMP.consumidor_nome,
								TEMP.admin
						FROM tmp_atendimento_suggar_$login_admin TEMP
						WHERE TEMP.dias_aberto > 5
						AND TEMP.os IS NULL
						AND TEMP.resolvido IS NULL";
			$res = pg_query($con,$sql);
			// echo nl2br($sql); echo "<br/>"; echo "<br/>";
			
			if (pg_num_rows($res) > 0 ){
				echo "<table width='700' border='0' cellpadding='3' cellspacing='1' class='tabela' align='center'>";
				echo "<tr height='4' class='titulo_tabela' valign='middle'>";
				echo "<td colspan='4'> Chamado há 5 dias sem abertura de OS </td>";
				echo "</tr>";
				echo "<tr height='4' class='titulo_coluna'>";
				echo "<td>Atendimento</td>";
				echo "<td>Nome do consumidor</td>";
				echo "<td>Nome do atendente</td>";
				echo "<td>Ação</td>";
				echo "</tr>";
			
				for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {

						$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

						$hd_chamado 		= trim(pg_fetch_result($res,$i,'hd_chamado'));
						$consumidor_nome	= trim(pg_fetch_result($res,$i,'consumidor_nome'));
						$admin  			= trim(pg_fetch_result($res,$i,'admin'));
						
						if(strlen($admin) >0){
							$sqlx="SELECT login from tbl_admin where admin=$admin";
							$resx=pg_exec($con,$sqlx);
							$atendente          = strtoupper(trim(pg_fetch_result($resx,0,'login')));
						}
						
						if($i%2==0) $cor="#F7F5F0"; else $cor="#F1F4FA";

						echo "<tr height='2' bgcolor='$cor'>";
						echo "<td nowrap id='atendimento_$hd_chamado' align='center'><a href='callcenter_interativo_new.php?callcenter=$hd_chamado&orientacao=t' target='_blank'>$hd_chamado</a></td>";
						echo "<td nowrap align='center'>$consumidor_nome </td>";
						echo "<td nowrap align='center'>$atendente </td>";
						echo "<td nowrap align='center'><button type='button' id='resolve_$hd_chamado' style='display:block'>Concluir atendimento</button></td>";
						echo "</tr>";
				}
				echo "</table> <br /><br />";

				$sql_dias= "SELECT  TEMP.hd_chamado,
									TEMP.consumidor_nome,
									TEMP.admin,
									TEMP.os
							FROM tmp_atendimento_suggar_$login_admin TEMP
							JOIN tbl_os ON tbl_os.os = TEMP.os
							LEFT JOIN tbl_os_produto ON tbl_os_produto.os = TEMP.os
							LEFT JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
							WHERE TEMP.dias_aberto > 5
							AND tbl_os_item.os_item IS NULL
							AND TEMP.resolvido is null ";
				$res_dias = pg_query($con,$sql_dias);
				 // echo nl2br($sql_dias); echo "<br/>"; echo "<br/>";
				
				if (pg_num_rows($res_dias) > 0 ){


					echo "<table width='700' border='0' cellpadding='3' cellspacing='1' class='tabela' align='center'>";
					echo "<tr height='5' class='titulo_tabela' valign='middle'>";
					echo "<td colspan='5'> Chamado há 5 dias sem lançamento de peças</td>";
					echo "</tr>";
					echo "<tr height='5' class='titulo_coluna'>";
					echo "<td>Atendimento</td>";
					echo "<td>Nome do consumidor</td>";
					echo "<td>OS</td>";
					echo "<td>Nome do atendente</td>";
					echo "<td>Ação</td>";
					echo "</tr>";
				
					for ($d = 0 ; $d < pg_num_rows($res_dias) ; $d++) {

							$cor = ($d % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

							$hd_chamado 		= trim(pg_fetch_result($res_dias,$d,'hd_chamado'));
							$consumidor_nome	= trim(pg_fetch_result($res_dias,$d,'consumidor_nome'));
							$os  			= trim(pg_fetch_result($res_dias,$d,'os'));
							$admin  			= trim(pg_fetch_result($res_dias,$d,'admin'));
							
							if(strlen($admin) >0){
								$sqlx="SELECT login from tbl_admin where admin=$admin";
								$resx=pg_exec($con,$sqlx);
								$atendente          = strtoupper(trim(pg_fetch_result($resx,0,'login')));
							}
							
							if($i%2==0) $cor="#F7F5F0"; else $cor="#F1F4FA";

							echo "<tr height='2' bgcolor='$cor'>";
							echo "<td nowrap id='atendimento_$hd_chamado' align='center'><a href='callcenter_interativo_new.php?callcenter=$hd_chamado&orientacao=t' target='_blank'>$hd_chamado</a></td>";
							echo "<td nowrap align='center'>$consumidor_nome </td>";
							echo "<td nowrap id='os_$os' align='center'><a href='os_press.php?os=$os&orientacao=t' target='_blank'>$os</a></td>";
							echo "<td nowrap align='center'>$atendente </td>";
							echo "<td nowrap align='center'><button type='button' id='resolve_$hd_chamado' style='display:block'>Concluir atendimento</button></td>";
							echo "</tr>";
					}
					echo "</table> <br /><br />";
				}
				

				$sql_os= "SELECT 	TEMP.hd_chamado,
									TEMP.consumidor_nome,
									TEMP.os,
									tbl_os.posto,
									TEMP.admin
							FROM tmp_atendimento_suggar_$login_admin TEMP
							JOIN tbl_os_produto ON tbl_os_produto.os = TEMP.os
							JOIN tbl_os ON tbl_os_produto.os = tbl_os.os
							JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
							JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_os_item.pedido AND tbl_faturamento_item.peca = tbl_os_item.peca
							WHERE TEMP.dias_aberto > 10
							AND TEMP.os IS NOT NULL
							AND tbl_os_produto.os_produto IS NOT NULL
							AND tbl_os.data_fechamento IS NOT NULL
							AND TEMP.resolvido is null
							";
				$res_os = pg_query($con,$sql_os);
				// echo nl2br($sql_os);	echo "<br/>"; echo "<br/>";	
				if (pg_num_rows($res_os) > 0 ){

								
					echo "<table width='700' border='0' cellpadding='2' cellspacing='1' class='tabela' align='center'>";
					echo "<tr height='6' class='titulo_tabela' valign='middle'>";
					echo "<td colspan='6'>Chamados abertos há 10 dias com peças enviadas e sem finalização da OS </td>";
					echo "</tr>";
					echo "<tr height='6' class='titulo_coluna'>";
					echo "<td>Atendimento</td>";
					echo "<td>Atendente</td>";
					echo "<td>OS</td>";
					echo "<td>Nome do consumidor</td>";
					echo "<td>Posto</td>";
					echo "<td>Ação</td>";

					echo "</tr>";

					for ($o = 0 ; $o < pg_num_rows($res_os) ; $o++) {

							$cor = ($o % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

							$hd_chamado 		= trim(pg_fetch_result($res_os,$o,'hd_chamado'));
							$admin				= trim(pg_fetch_result($res_os,$o,'admin'));
							$os  				= trim(pg_fetch_result($res_os,$o,'os'));
							$consumidor_nome   	= trim(pg_fetch_result($res_os,$o,'consumidor_nome'));
							$posto   			= trim(pg_fetch_result($res_os,$o,'posto'));
							
							if(strlen($admin) >0){
								$sqlx="SELECT login from tbl_admin where admin=$admin";
								$resx=pg_exec($con,$sqlx);
								$atendente          = strtoupper(trim(pg_fetch_result($resx,0,'login')));
							}
							if(strlen($posto) >0){
								$sqly="SELECT tbl_posto_fabrica.nome 
										FROM tbl_posto 
										JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
										WHERE tbl_posto_fabrica.posto = $posto 
										AND tbl_posto_fabrica.fabrica = $login_fabrica ";
								$resy=pg_exec($con,$sqly);
								$posto_nome          = strtoupper(trim(pg_fetch_result($resy,0,'nome')));
							}
							
							if($i%2==0) $cor="#F7F5F0"; else $cor="#F1F4FA";

							echo "<tr height='2' bgcolor='$cor'>";
							echo "<td nowrap id='atendimento_$hd_chamado' align='center'><a href='callcenter_interativo_new.php?callcenter=$hd_chamado&orientacao=t' target='_blank'>$hd_chamado</a></td>";
							echo "<td nowrap align='center'>$atendente /td>";
							echo "<td nowrap id='os_$os' align='center'><a href='os_press.php?os=$os&orientacao=t' target='_blank'>$os</a></td>";
							echo "<td nowrap align='center'>$consumidor_nome </td>";
							echo "<td nowrap align='center'>$posto_nome</td>";
							echo "<td nowrap align='center'><button type='button' id='resolve_$hd_chamado' style='display:block'>Concluir atendimento</button></td>";
							echo "</tr>";

					}
					echo "</table> <br /><br />";
				}

					// D) Chamados abertos há 25 dias com peças enviadas e sem finalização da OS;
					// número do atendimento - nome do consumidor - número da OS - nome do posto - Nome do atendente;
					// - O sistema deverá mostrar os atendimentos abertos há mais de 25 dias, e que tiveram peças enviadas, e a OS não foi finalizada;
				
				$sql_finalizada = "SELECT 	TEMP.hd_chamado,
											TEMP.consumidor_nome,
											TEMP.os,
											tbl_os.posto,
											TEMP.admin 
									FROM tmp_atendimento_suggar_$login_admin TEMP
									JOIN tbl_os ON tbl_os.os = TEMP.os
									JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
									JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
									JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_os_item.pedido AND tbl_faturamento_item.peca = tbl_os_item.peca
									WHERE dias_aberto > 25
									AND TEMP.os IS NOT NULL
									AND tbl_os.finalizada IS NULL
									AND tbl_os.data_fechamento IS NULL
									AND TEMP.resolvido is null
									";
				$res_finalizada = pg_query($con,$sql_finalizada);
				// echo nl2br($sql_finalizada); echo "<br/>"; echo "<br/>";
				
				if (pg_num_rows($res_finalizada) > 0 ){

					// número do atendimento - nome do consumidor - número da OS - nome do posto - Nome do atendente;
					
					echo "<table width='700' border='0' cellpadding='2' cellspacing='1' class='tabela' align='center'>";
					echo "<tr height='6' class='titulo_tabela' valign='middle'>";
					echo "<td colspan='6'>Chamados abertos há 25 dias com peças enviadas e sem finalização da OS</td>";
					echo "</tr>";
					echo "<tr height='6' class='titulo_coluna'>";
					echo "<td>Atendimento</td>";
					echo "<td>Atendente</td>";
					echo "<td>OS</td>";
					echo "<td>Nome do consumidor</td>";
					echo "<td>Posto</td>";
					echo "<td>Ação</td>";
					echo "</tr>";

					for ($f = 0 ; $f < pg_num_rows($res_finalizada) ; $f++) {

							$cor = ($f % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

							$hd_chamado 		= trim(pg_fetch_result($res_finalizada,$f,'hd_chamado'));
							$admin				= trim(pg_fetch_result($res_finalizada,$f,'admin'));
							$os  				= trim(pg_fetch_result($res_finalizada,$f,'os'));
							$consumidor_nome   	= trim(pg_fetch_result($res_finalizada,$f,'consumidor_nome'));
							$nome   			= trim(pg_fetch_result($res_finalizada,$f,'nome'));
							
							if(strlen($admin) >0){
								$sqlx="SELECT login from tbl_admin where admin=$admin";
								$resx=pg_exec($con,$sqlx);
								$atendente          = strtoupper(trim(pg_fetch_result($resx,0,'login')));
							}
							if(strlen($posto) >0){
								$sqly="SELECT tbl_posto_fabrica.nome 
										FROM tbl_posto 
										JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
										WHERE tbl_posto_fabrica.posto = $posto 
										AND tbl_posto_fabrica.fabrica = $login_fabrica ";
								$resy=pg_exec($con,$sqly);
								$posto_nome          = strtoupper(trim(pg_fetch_result($resy,0,'nome')));
							}
							
							
							if($i%2==0) $cor="#F7F5F0"; else $cor="#F1F4FA";

							echo "<tr height='2' bgcolor='$cor'>";
							echo "<td nowrap id='atendimento_$hd_chamado' align='center'><a href='callcenter_interativo_new.php?callcenter=$hd_chamado&orientacao=t' target='_blank'>$hd_chamado</a></td>";
							echo "<td nowrap align='center'>$atendente /td>";
							echo "<td nowrap id='os_$os' align='center'><a href='os_press.php?os=$os&orientacao=t' target='_blank'>$os</a></td>";
							echo "<td nowrap align='center'>$consumidor_nome </td>";
							echo "<td nowrap align='center'>$posto_nome</td>";
							echo "<td nowrap align='center'><button type='button' id='resolve_$hd_chamado' style='display:block'>Concluir atendimento</button></td>";
							echo "</tr>";

					}
					echo "</table> <br /><br />";
				}
			}else{
				echo "<br><center><B>Não foram encontrados resultados para esta pesquisa!</B></center><br>";
			}

		}else{
				echo "<br><center><B>Não foram encontrados resultados para esta pesquisa!</B></center><br>";
			}
	}else{

		if (strlen($estado) > 0){
			$join_estado = "JOIN tbl_cidade on tbl_hd_chamado_extra.cidade = tbl_cidade.cidade";
			$cond_estado = " AND UPPER(tbl_cidade.estado) = '$estado' ";

		}		

		$sql_relatorio= " SELECT  DISTINCT 	tbl_hd_chamado.hd_chamado,
									tbl_hd_chamado_extra.os,
									tbl_hd_chamado.admin,
									tbl_hd_chamado.resolvido::date - tbl_hd_chamado.data::date as hd_aberto,
									tbl_cidade.estado ,
									tbl_os.data_fechamento::date - tbl_hd_chamado.data::date as dias_abertos
							FROM tbl_hd_chamado
								LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
								LEFT JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
								LEFT JOIN tbl_os ON tbl_os.os = tbl_hd_chamado_extra.os
								LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
								LEFT JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
								LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_os_item.pedido AND tbl_faturamento_item.peca = tbl_os_item.peca
								$join_estado
							WHERE tbl_hd_chamado.fabrica = $login_fabrica
								AND (tbl_os.data_fechamento IS NOT NULL OR tbl_hd_chamado.resolvido IS NOT NULL)
								AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial' and '$aux_data_final'
								-- AND  UPPER(tbl_hd_chamado.titulo) ilike UPPER('%o de Posto')
								AND  UPPER(TO_ASCII(tbl_hd_chamado.titulo,'LATIN9')) = UPPER(TO_ASCII('Indicação de Posto','LATIN9'))
							$cond_estado 
							GROUP BY 	tbl_hd_chamado.hd_chamado,
									 	tbl_hd_chamado_extra.os, 
									 	tbl_hd_chamado.admin,
									 	hd_aberto, 
									 	tbl_cidade.estado , 
									 	dias_abertos 
							ORDER BY  dias_abertos DESC ";
		// echo nl2br($sql_relatorio);exit;
		$res_relatorio = pg_query($con,$sql_relatorio);
		$count = pg_num_rows($res_relatorio);

		if ($count > 0 ){

				echo "<table width='700' border='0' cellpadding='2' cellspacing='1' class='tabela' align='center'>";
				echo "<tr height='4' class='titulo_tabela' valign='middle'>";
				echo "<td colspan='5'>Atendimentos concluídos</td>";
				echo "</tr>";
				echo "<tr height='2' class='titulo_coluna'>";
				echo "<td>Atendimento</td>";
				echo "<td>Dias em aberto</td>";
				echo "<td>OS</td>";
				echo "<td>Atendente</td>";
				echo "<td>Estado</td>";
				echo "</tr>";

				for ($i = 0 ; $i < pg_num_rows($res_relatorio) ; $i++) {

						$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

						$hd_chamado 		= trim(pg_fetch_result($res_relatorio,$i,'hd_chamado'));
						$dias_abertos   	= trim(pg_fetch_result($res_relatorio,$i,'dias_abertos'));
						$os  				= trim(pg_fetch_result($res_relatorio,$i,'os'));
						$hd_aberto  		= trim(pg_fetch_result($res_relatorio,$i,'hd_aberto'));
						$admin				= trim(pg_fetch_result($res_relatorio,$i,'admin'));
						$estado_hd   		= trim(pg_fetch_result($res_relatorio,$i,'estado'));
						
						if (strlen($os) == 0  and strlen($hd_aberto) > 0 ){
							$dias_abertos = $hd_aberto;
						}
						if ($dias_abertos < 0){
							continue;
						}

						if(strlen($admin) >0){
							$sqlx="SELECT login from tbl_admin where admin=$admin";
							$resx=pg_query($con,$sqlx);
							$atendente          = strtoupper(trim(pg_fetch_result($resx,0,'login')));
						}
						
						if($i%2==0) $cor="#F7F5F0"; else $cor="#F1F4FA";

						echo "<tr height='2' bgcolor='$cor'>";
						echo "<td nowrap id='atendimento_$hd_chamado' align='center'><a href='callcenter_interativo_new.php?callcenter=$hd_chamado&orientacao=t' target='_blank'>$hd_chamado</a></td>";
						echo "<td nowrap align='center'>$dias_abertos </td>";
						echo "<td nowrap id='os_$os' align='center'><a href='os_press.php?os=$os&orientacao=t' target='_blank'>$os</a></td>";
						echo "<td nowrap align='center'>$atendente </td>";
						echo "<td nowrap align='center'>$estado_hd</td>";
						echo "</tr>";

				}
				echo "</table> <br /><br />";
		}else{
		echo "<br><center><B>Não foram encontrados resultados para esta pesquisa!</B></center><br>";
		}
	}
}
if (strlen($msg_erro)>0){ ?>
	<table width="730" border="0" cellpadding="2" cellspacing="2" align="center" class="error">
	<tr>
		<td><?echo $msg_erro?></td>
	</tr>
</table>


<?php
}
echo "<br>";

include "rodape.php";
?>