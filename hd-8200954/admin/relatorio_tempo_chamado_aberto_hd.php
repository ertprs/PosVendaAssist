<?php

include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";

if ($trava_cliente_admin) {
	$admin_privilegios = "call_center";
	$layout_menu = "callcenter";
} else {
	$admin_privilegios = "financeiro";
	$layout_menu = "financeiro";
}

include "autentica_admin.php";

$excel = $_GET["excel"];

$title = "Relatório de Tempo de HD (Aberto, Aguardando Peças e Resolvidos)";

if ($excel) {
	ob_start();
} else {
	include_once "cabecalho.php";
}

?>
<p>

<style type="text/css">
.Tabela{
	border:1px solid #d2e4fc;
	background-color:#d2e4fc;
	}

.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.titulo {
	font-family: Arial;
	font-size: 7pt;
	text-align: right;
	color: #000000;
	background: #ced7e7;
}
.titulo2 {
	font-family: Arial;
	font-size: 7pt;
	text-align: center;
	color: #000000;
	background: #ced7e7;
}
.titulo3 {
	font-family: Arial;
	font-size: 7pt;
	text-align: left;
	color: #000000;
	background: #ced7e7;
}
.inicio {
	font-family: Arial;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-align: left;
	color: #000000;
}

.conteudo {
	font-family: Arial;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-align: left;

}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.Conteudo2 {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}
.Principal{
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
div.exibe{
	padding:8px;
	color:  #555555;
	display:none;
}
</style>
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<!--  -->

<?php

$arquivo_nome     = "relatorio_tempo_chamado_aberto_os-".$login_fabrica.".".$login_admin.".xls";
$caminho_arquivo  = "xls/".$arquivo_nome;
fopen($caminho_arquivo, "w+");	
$fp = fopen($caminho_arquivo, "a");


//HD 216470: Adicionando algumas validações básicas
$posto				= trim($_GET['posto']);
$mes				= trim($_GET['mes']);
$ano				= trim($_GET['ano']);
$estado				= trim($_GET['estado']);
$pais				= trim($_GET['pais']);
$linha				= trim($_GET['linha']);
$familia			= trim($_GET['familia']);
$cliente_admin		= trim($_GET['cliente_admin']);
$produto_referencia	= trim($_GET['produto_referencia']);
$periodo			= trim($_GET['periodo']);
$tipo_os			= trim($_GET['tipo_os']);



if (strlen($mes)) {
	$mes = intval($mes);
	if ($mes < 1 || $mes > 12) {
		$msg_erro = "O mês deve ser um número entre 1 e 12";
		$mes = "";
	}
}

if (strlen($ano) != 4) {
	$msg_erro = "O ano deve conter 4 dígitos";
	$ano = "";
}

if ($mes == "" || $ano == "") {
	$msg_erro = "Selecione o mês e o ano para a pesquisa";
}

if (strlen($msg_erro)) {
	echo "
	<br>
	<div width=100% style='background-color:#CC0000; color:#FFFFFF; font-size:11pt; font-weight: bold;'>$msg_erro</div>
	<br>";
}
else {
	if ($periodo == "mes_atual") {
		$sql = "SELECT fn_dias_mes('$ano-$mes-01',0)";
	}
	else {
		$sql = "SELECT fn_dias_mes('$ano-01-01',0)";
	}
	$res3 = pg_query($con,$sql);
	$data_inicial = pg_fetch_result($res3,0,0);

	$sql = "SELECT fn_dias_mes('$ano-$mes-01',1)";
	$res3 = pg_query($con,$sql);
	$data_final = pg_fetch_result($res3,0,0);

	//HD 216470: Retirada a subquery e substitida por condições na cláusula WHERE
	if ($familia) {
		$cond_familia = "AND tbl_produto.familia=$familia";
	}

	if ($produto_referencia) {
		$cond_produto = "AND tbl_produto.referencia = '$produto_referencia'";
	}

	//HD 216470: Adicionada a busca por linha
	if ($linha) {
		$cond_linha = "AND tbl_produto.linha=$linha";
	}

	//HD 216470: Adicionada a busca por cliente admin
	if ($cliente_admin) {
		$cond_cliente_admin = "AND tbl_os.cliente_admin=$cliente_admin";
	}




 	// ========== INICIO CONDIÇÃO DOS FILTROS ==========

	if(strlen($familia) > 0){
		$and_familia = "JOIN tbl_familia 
						ON  tbl_produto.familia = tbl_familia.familia
						AND tbl_familia.fabrica    = $login_fabrica
						AND tbl_familia.familia    = $familia";
	}

	if(strlen($linha) > 0){
		$and_linha = "JOIN tbl_linha  
					  ON  tbl_produto.linha = tbl_linha.linha 
					  AND tbl_linha.fabrica = $login_fabrica
					  AND tbl_linha.linha   = $linha";
	}


	if(strlen($produto_referencia) > 0){
		$and_referencia_pd = " AND tbl_produto.referencia = '$produto_referencia'";
	}

	
	if(strlen($posto_id) > 0){
		$cond_posto = " AND tbl_posto.posto = $posto_id";
	}

	if(strlen($cliente_admin) > 0){
		$cond_cliente_admin = "  JOIN tbl_cliente_admin on tbl_hd_chamado.cliente_admin = tbl_cliente_admin.cliente_admin AND tbl_cliente_admin.cliente_admin = $cliente_admin";
	}else{
		$cond_cliente_admin = " LEFT JOIN tbl_cliente_admin on tbl_hd_chamado.cliente_admin = tbl_cliente_admin.cliente_admin ";
	}

 	// ========== FIM CONDIÇÃO DOS FILTROS ==========


		$sql_peca = "SELECT 
					tbl_hd_chamado.hd_chamado,
					tbl_posto.posto AS codigo_posto,
					tbl_posto.nome AS posto_nome,
					tbl_posto.cidade,
					tbl_posto.estado, 
					tbl_hd_chamado.status, 
					tbl_hd_chamado.titulo,
					tbl_hd_chamado.protocolo_cliente,
					tbl_hd_chamado_extra.serie,
					to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data,
					to_char(tbl_hd_chamado.data_providencia,'DD/MM/YYYY') AS providencia_data,
					current_date - tbl_hd_chamado.data::date AS data_intervalo,
					to_char(current_date,'DD/MM/YYYY') AS data_atual,
					tbl_hd_chamado_extra.os,
					tbl_hd_chamado_item.os as os_item, 
					tbl_produto.descricao as produto_nome,
					tbl_produto.referencia as produto_referencia,
					tbl_hd_chamado_extra.nome as consumidor_nome,
					tbl_hd_chamado_extra.sua_os,
					tbl_posto_fabrica.codigo_posto as codigo_posto_fabrica,
					tbl_hd_chamado.atendente,
					tbl_hd_chamado.sequencia_atendimento as intervensor,
					tbl_hd_chamado.categoria,
					tbl_hd_chamado.admin,

					tbl_cliente_admin.nome AS cliente_admin,
					tbl_cliente_admin.cidade AS cidade_admin,
					tbl_cliente_admin.estado AS estado_admin 

				    INTO TEMP temp_rtc_mostra_hd
					FROM tbl_hd_chamado
					JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado $add_2 
					LEFT JOIN tbl_hd_chamado_item on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado and tbl_hd_chamado_item.produto is not null
					LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_item.produto $and_referencia_pd 

					$and_familia 
					$and_linha  

					LEFT JOIN tbl_hd_situacao ON tbl_hd_situacao.hd_situacao = tbl_hd_chamado_extra.hd_situacao
					LEFT JOIN tbl_cidade on tbl_cidade.cidade = tbl_hd_chamado_extra.cidade
					LEFT JOIN tbl_posto_fabrica on tbl_hd_chamado_extra.posto = tbl_posto_fabrica.posto
					AND tbl_posto_fabrica.fabrica = 52
					LEFT JOIN tbl_posto on tbl_hd_chamado_extra.posto = tbl_posto.posto $cond_posto 

					$cond_cliente_admin 

					LEFT JOIN tbl_os ON tbl_os.os = tbl_hd_chamado_extra.os
					WHERE tbl_hd_chamado.fabrica_responsavel = 52
					AND tbl_hd_chamado.data BETWEEN '$data_inicial' AND '$data_final'
					AND tbl_posto.posto = $posto 
					ORDER BY tbl_hd_chamado.hd_chamado DESC";
		$res_peca = pg_query($con,$sql_peca);
	    //echo nl2br($sql_peca);
		

		

	 $sql_bus_posto =  "SELECT * FROM temp_rtc_mostra_hd WHERE codigo_posto = '$posto'";
   	 //echo nl2br($sql_bus_posto);
	 $res_bus_posto = pg_exec($con,$sql_bus_posto);
	 if(pg_numrows($res_bus_posto)>0){
		$codigo_posto	= trim(pg_result($res_bus_posto, $x, codigo_posto));
		$posto_nome		= trim(pg_result($res_bus_posto, $x, posto_nome));
		$cidade			= trim(pg_result($res_bus_posto, $x, cidade));
		$estado			= trim(pg_result($res_bus_posto, $x, estado));
	 }




		$data_inicial_press = explode(" ", $data_inicial);
		$data_inicial_press = implode("/", array_reverse(explode("-", $data_inicial_press[0])));

		$data_final_press = explode(" ", $data_final);
		$data_final_press = implode("/", array_reverse(explode("-", $data_final_press[0])));
		
		
		echo "<TABLE width='700' border='1' align='center' cellspacing='2' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>
		<tr class='Principal'>
		<td>Posto</td>
		<td>$codigo_posto - $posto_nome ($cidade - $estado)</td>
		</tr>
		<tr class=Conteudo>
		<td>Período (data consulta)</td>
		<td>$data_inicial_press até $data_final_press</td>
		</tr>
		<tr class=Conteudo>
		<td>Data de Referência <br>para tempo conserto<br>(conserto ou finalização)</td>
		<td>" . ucwords(str_replace("_", " ", $data_referencia)) . "</td>
		</tr>";

		fputs ($fp,"<TABLE width='700' border='1' align='center' cellspacing='2' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>
		<tr  class='Principal'>
		<td bgcolor='#485989'><font color='#FFFFFF'>Posto</font></td>
		<td bgcolor='#485989'><font color='#FFFFFF'>$codigo_posto - $posto_nome ($cidade - $estado)</font></td>
		</tr>
		<tr class=Conteudo>
		<td>Período (data consulta)</td>
		<td>$data_inicial_press até $data_final_press</td>
		</tr>
		<tr class=Conteudo>
		<td>Data de Referência <br>para tempo conserto<br>(conserto ou finalização)</td>
		<td>$data_referencia</td>");

		if ($estado) {
			echo "<tr class=Conteudo>
				<td>Estado</td>
				<td>$estado</td>
				</tr>";
			fputs ($fp,"<tr class=Conteudo><td>Estado</td><td>$estado</td></tr>");
		}else {
			echo "<tr class=Conteudo>
				<td>Estado</td>
				<td>TODOS OS ESTADOS</td>
				</tr>";
			fputs ($fp,"<tr class=Conteudo>
				<td>Estado</td>
				<td>TODOS OS ESTADOS</td>
				</tr>");
		}

		if ($pais) {
			echo "<tr class=Conteudo>
				<td>País</td>
				<td>$pais</td>
				</tr>";
				fputs ($fp,"<tr class=Conteudo>
				<td>País</td>
				<td>$pais</td>
				</tr>");
		}

		if ($linha) {
			$sql_linha = "SELECT nome FROM tbl_linha WHERE linha=$linha";
			$res_linha = pg_query($con, $sql_linha);
			$linha_nome = pg_fetch_result($res_linha, 0, nome);
			echo "<tr class=Conteudo>
				<td>Linha</td>
				<td>$linha_nome</td>
				</tr>";
				fputs ($fp,"<tr class=Conteudo>
				<td>Linha</td>
				<td>$linha_nome</td>
				</tr>");
		}

		if ($familia) {
			$sql_familia = "SELECT descricao FROM tbl_familia WHERE familia=$familia";
			$res_familia = pg_query($con, $sql_familia);
			$familia_descricao = pg_fetch_result($res_familia, 0, descricao);
			echo "<tr class=Conteudo>
				<td>Família</td>
				<td>$familia_descricao</td>";
				fputs ($fp,"<tr class=Conteudo>
				<td>Família</td>
				<td>$familia_descricao</td>");
		}

		echo "</TABLE><br>";
		fputs ($fp,"</TABLE><br>");



	    /* === INICIO CHAMADOS ABERTO === */

		$sql_abertos = "SELECT 
						hd_chamado,
						cliente_admin,
						produto_nome,
						produto_referencia,
						data,
						data_intervalo,
						consumidor_nome,
						data_atual,
						cidade_admin,
						estado_admin 
						FROM temp_rtc_mostra_hd WHERE status='Aberto'";
		//echo nl2br($sql_abertos);
		$res_abertos = pg_query($con, $sql_abertos);
	   	if (pg_num_rows($res_abertos) > 0) {

			echo "<TABLE width='700' border='1' align='center' cellspacing='2' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>";
			fputs ($fp,"<TABLE width='700' border='1' align='center' cellspacing='2' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>");
			
			
			echo "<tr class='Principal'>
					<td nowrap colspan='12'>Abertos</td>
				 </tr>";
			fputs ($fp,"<tr class='Principal'>
						  <td nowrap colspan='10' bgcolor='#485989'><font color='#FFFFFF'><center>Abertos</center></font></td>
						</tr>");

			echo "<tr  class='Principal'>
			<td>Atendimento</td>";
			fputs ($fp,"<tr  class='Principal'>
			<td bgcolor='#485989'><font color='#FFFFFF'>Atendimento</font></td>");

			echo "<td>Cliente ADM</td>";
			fputs ($fp,"<td bgcolor='#485989'><font color='#FFFFFF'>Cliente ADM</font></td>");
			echo "<td colspan='2'>PRODUTO</td><td>Abertura</font></td>";
			fputs ($fp,"<td colspan='2' bgcolor='#485989'><font color='#FFFFFF'>PRODUTO</td><td bgcolor='#485989'><font color='#FFFFFF'>Abertura</font></td>");

			echo "<td>Data Atual</td>";
			fputs ($fp,"<td bgcolor='#485989'><font color='#FFFFFF'>Data Atual</font></td>");
			echo "<td>Qtde Dias</td>";
			fputs ($fp,"<td bgcolor='#485989'><font color='#FFFFFF'>Qtde Dias</font></td>");

			echo "<td colspan=1>Consumidor</td>";
			fputs ($fp,"<td colspan=1 bgcolor='#485989'><font color='#FFFFFF'>Consumidor</font></td>");
			

			echo "<td colspan=1>Cidade</td>";
			fputs ($fp,"<td colspan=1 bgcolor='#485989'><font color='#FFFFFF'>Cidade</font></td>");

			echo "<td colspan=1>Estado</td>";
			fputs ($fp,"<td colspan=1 bgcolor='#485989'><font color='#FFFFFF'>Estado</font></td>");
			
			echo "</tr>";
			fputs ($fp,"</tr>");
			

			
			for($a=0;$a < pg_num_rows($res_abertos);$a++) {

				$hd_chamado			= "";
				$cliente_admin		= "";
				$produto_nome		= "";
				$produto_referencia = "";
				$data_abertura		= "";
				$data_atual			= "";
				$data_intervalo		= "";
				$consumidor_nome	= "";
				$cidade_admin		= "";
				$estado_admin		= "";

				$hd_chamado			= pg_fetch_result($res_abertos,$a,hd_chamado);
				$cliente_admin		= pg_fetch_result($res_abertos,$a,cliente_admin);
				$produto_nome		= pg_fetch_result($res_abertos,$a,produto_nome);
				$produto_referencia = pg_fetch_result($res_abertos,$a,produto_referencia);
				$data_abertura		= pg_fetch_result($res_abertos,$a,data);
				$data_atual			= pg_fetch_result($res_abertos,$a,data_atual);
				$data_intervalo		= pg_fetch_result($res_abertos,$a,data_intervalo);
				$consumidor_nome	= pg_fetch_result($res_abertos,$a,consumidor_nome);
				$cidade_admin		= pg_fetch_result($res_abertos,$a,cidade_admin);
				$estado_admin		= pg_fetch_result($res_abertos,$a,estado_admin);
			   
				$total_hd_1++;
				$total_dias_1 += intval($data_intervalo);

				if ($i % 2 == 0) {
					$cor = "#F1F4FA";
					$btn = "azul";
				}else{
					$cor = "#F7F5F0";
					$btn = "amarelo";
				}

				echo "<TR class='Conteudo' style='background-color: $cor;'>\n";
				fputs ($fp,"<TR class='Conteudo'>");


				echo "<TD nowrap style='text-align: left;'>";
				fputs ($fp,"<TD nowrap style='background-color: $cor;text-align: left;'>");
				echo '<a href="callcenter_interativo_new.php?callcenter=' . $hd_chamado . '" target="_blank">' . $hd_chamado .'</a>';
				fputs ($fp,"$hd_chamado");
				echo "</td>";
				fputs ($fp,"</td>");



				echo "<td nowrap style='text-align: left;'>$cliente_admin</td>";
				fputs ($fp,"<td nowrap  style='background-color: $cor;text-align: left;'>$cliente_admin</td>");

				echo "<TD nowrap style='text-align: left;'>$produto_referencia</td>";
				fputs ($fp,"<TD nowrap  style='background-color: $cor;text-align: left;'>$produto_referencia</td>");
				echo "<TD nowrap style='text-align: left;'>$produto_nome</td>";
				fputs ($fp,"<TD nowrap  style='background-color: $cor;text-align: left;'>$produto_nome</td>");
				echo "<TD nowrap style='text-align: left;'>$data_abertura</td>";
				fputs ($fp,"<TD nowrap  style='background-color: $cor;text-align: left;'>$data_abertura</td>");
				
				$data_atual_x = date('d/m/Y');
				echo "<td nowrap style='text-align: left;'>$data_atual</td>";
				fputs ($fp,"<td nowrap  style='background-color: $cor;text-align: left;'>$data_atual</td>");
				echo "<TD nowrap>$data_intervalo</td>";
				fputs ($fp,"<TD nowrap  style='background-color: $cor;'>$data_intervalo</td>");


				echo "<td nowrap style='text-align: left;'>$consumidor_nome</td>
				<td nowrap style='text-align: left;'>$cidade_admin</td>
				<td nowrap style='text-align: left;'>$estado_admin</td>";
				fputs ($fp,"<td nowrap  style='background-color: $cor;text-align: left;'>$consumidor_nome</td>
				<td nowrap>$cidade_admin</td>
				<td nowrap>$estado_admin</td>");


				echo "</tr>";
				fputs ($fp,"</tr>");

			}
			

			$colspan_1 = 5;


			echo "<tr class=Principal>
					<td>$total_hd_1</td>
					<td colspan=$colspan_1></td>
					<td>$total_dias_1</td>";
		   fputs ($fp,"<tr class=Principal>
						<td bgcolor='#485989'>
							<font color='#FFFFFF'>
								$total_hd_1
							</font>
						</td>
						<td colspan=$colspan_1 bgcolor='#485989'>
						</td>
						<td bgcolor='#485989'>
							<font color='#FFFFFF'>
								$total_dias_1
							</font>
						</td>");

			echo "<td colspan=3></td>";
			fputs ($fp,"<td colspan=3 bgcolor='#485989'></td>");

			echo "</tr>";
			fputs ($fp,"</tr>");

			$colspan_1 = 11;


			if ($total_hd_1) {
				$total_1 = number_format($total_dias_1 / $total_hd_1, 2, ",", "");
			}
			else {
				$total_1 = "0.00";
			}

			echo "<tr class=Principal>
				<td colspan=$colspan_1>
				MÉDIA DE DIAS POR CHAMADO NO PERÍODO CONSULTADO: " . $total_1 . "
				</td>
			</tr>
			</table><br><br>";

			fputs ($fp,"<tr class=Principal>
				<td colspan=10 bgcolor='#485989'><center><font color='#FFFFFF'>
				MÉDIA DE DIAS POR CHAMADO NO PERÍODO CONSULTADO: " . $total_1 . "</font></center>
				</td>
			</tr>
			</table><br><br>");

		}
		/* === FIM CHAMADOS ABERTO === */












	   
	    /* === INICIO CHAMADOS  AGUARDADO PEÇA === */

		$sql_agua_peca = "SELECT 
						hd_chamado,
						cliente_admin,
						produto_nome,
						produto_referencia,
						data,
						data_intervalo,
						consumidor_nome,
						data_atual,
						cidade_admin,
						estado_admin 
						FROM temp_rtc_mostra_hd WHERE status='Aguardando Peça'";
		//echo nl2br($sql_agua_peca);
		$res_agua_peca = pg_query($con, $sql_agua_peca);
	   	if (pg_num_rows($res_agua_peca) > 0) {

			echo "<TABLE width='700' border='1' align='center' cellspacing='2' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>";
			fputs ($fp,"<TABLE width='700' border='1' align='center' cellspacing='2' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>");
			
			
			echo "<tr class='Principal'>
					<td nowrap colspan='12'>Aguardando Peça</td>
				 </tr>";
			fputs ($fp,"<tr class='Principal'>
						  <td nowrap colspan='10' bgcolor='#485989'><font color='#FFFFFF'><center>Aguardando Peça</center></font></td>
						</tr>");

			echo "<tr  class='Principal'>
			<td>Atendimento</td>";
			fputs ($fp,"<tr  class='Principal'>
			<td bgcolor='#485989'><font color='#FFFFFF'>Atendimento</font></td>");

			echo "<td>Cliente ADM</td>";
			fputs ($fp,"<td bgcolor='#485989'><font color='#FFFFFF'>Cliente ADM</font></td>");
			echo "<td colspan='2'>PRODUTO</td><td>Abertura</font></td>";
			fputs ($fp,"<td colspan='2' bgcolor='#485989'><font color='#FFFFFF'>PRODUTO</td><td bgcolor='#485989'><font color='#FFFFFF'>Abertura</font></td>");

			echo "<td>Data Atual</td>";
			fputs ($fp,"<td bgcolor='#485989'><font color='#FFFFFF'>Data Atual</font></td>");
			echo "<td>Qtde Dias</td>";
			fputs ($fp,"<td bgcolor='#485989'><font color='#FFFFFF'>Qtde Dias</font></td>");

			echo "<td colspan=1>Consumidor</td>";
			fputs ($fp,"<td colspan=1 bgcolor='#485989'><font color='#FFFFFF'>Consumidor</font></td>");
			

			echo "<td colspan=1>Cidade</td>";
			fputs ($fp,"<td colspan=1 bgcolor='#485989'><font color='#FFFFFF'>Cidade</font></td>");

			echo "<td colspan=1>Estado</td>";
			fputs ($fp,"<td colspan=1 bgcolor='#485989'><font color='#FFFFFF'>Estado</font></td>");
			
			echo "</tr>";
			fputs ($fp,"</tr>");
			

			
			for($b=0;$b < pg_num_rows($res_agua_peca);$b++) {
				
				$hd_chamado			= "";
				$cliente_admin		= "";
				$produto_nome		= "";
				$produto_referencia = "";
				$data_abertura		= "";
				$data_atual			= "";
				$data_intervalo		= "";
				$consumidor_nome	= "";
				$cidade_admin		= "";
				$estado_admin		= "";

				$hd_chamado			= pg_fetch_result($res_agua_peca,$b,hd_chamado);
				$cliente_admin		= pg_fetch_result($res_agua_peca,$b,cliente_admin);
				$produto_nome		= pg_fetch_result($res_agua_peca,$b,produto_nome);
				$produto_referencia = pg_fetch_result($res_agua_peca,$b,produto_referencia);
				$data_abertura		= pg_fetch_result($res_agua_peca,$b,data);
				$data_atual			= pg_fetch_result($res_agua_peca,$b,data_atual);
				$data_intervalo		= pg_fetch_result($res_agua_peca,$b,data_intervalo);
				$consumidor_nome	= pg_fetch_result($res_agua_peca,$b,consumidor_nome);
				$cidade_admin		= pg_fetch_result($res_agua_peca,$b,cidade_admin);
				$estado_admin		= pg_fetch_result($res_agua_peca,$b,estado_admin);
			   
				$total_hd_2++;
				$total_dias_2 += intval($data_intervalo);

				if ($i % 2 == 0) {
					$cor = "#F1F4FA";
					$btn = "azul";
				}else{
					$cor = "#F7F5F0";
					$btn = "amarelo";
				}

				echo "<TR class='Conteudo' style='background-color: $cor;'>\n";
				fputs ($fp,"<TR class='Conteudo'>");


				echo "<TD nowrap style='text-align: left;'>";
				fputs ($fp,"<TD nowrap style='background-color: $cor;text-align: left;'>");
				echo '<a href="callcenter_interativo_new.php?callcenter=' . $hd_chamado . '" target="_blank">' . $hd_chamado .'</a>';
				fputs ($fp,"$hd_chamado");
				echo "</td>";
				fputs ($fp,"</td>");



				echo "<td nowrap style='text-align: left;'>$cliente_admin</td>";
				fputs ($fp,"<td nowrap  style='background-color: $cor;text-align: left;'>$cliente_admin</td>");

				echo "<TD nowrap style='text-align: left;'>$produto_referencia</td>";
				fputs ($fp,"<TD nowrap  style='background-color: $cor;text-align: left;'>$produto_referencia</td>");
				echo "<TD nowrap style='text-align: left;'>$produto_nome</td>";
				fputs ($fp,"<TD nowrap  style='background-color: $cor;text-align: left;'>$produto_nome</td>");
				echo "<TD nowrap style='text-align: left;'>$data_abertura</td>";
				fputs ($fp,"<TD nowrap  style='background-color: $cor;text-align: left;'>$data_abertura</td>");
				
				$data_atual_x = date('d/m/Y');
				echo "<td nowrap style='text-align: left;'>$data_atual</td>";
				fputs ($fp,"<td nowrap  style='background-color: $cor;text-align: left;'>$data_atual</td>");
				echo "<TD nowrap>$data_intervalo</td>";
				fputs ($fp,"<TD nowrap  style='background-color: $cor;'>$data_intervalo</td>");


				echo "<td nowrap style='text-align: left;'>$consumidor_nome</td>
				<td nowrap style='text-align: left;'>$cidade_admin</td>
				<td nowrap style='text-align: left;'>$estado_admin</td>";
				fputs ($fp,"<td nowrap  style='background-color: $cor;text-align: left;'>$consumidor_nome</td>
				<td nowrap>$cidade_admin</td>
				<td nowrap>$estado_admin</td>");


				echo "</tr>";
				fputs ($fp,"</tr>");

			}

				$colspan_2 = 5;


				echo "<tr class=Principal>
						<td>$total_hd_2</td>
						<td colspan=$colspan_2></td>
						<td>$total_dias_2</td>";
			   fputs ($fp,"<tr class=Principal>
							<td bgcolor='#485989'>
								<font color='#FFFFFF'>
									$total_hd_2
								</font>
							</td>
							<td colspan=$colspan_2 bgcolor='#485989'>
							</td>
							<td bgcolor='#485989'>
								<font color='#FFFFFF'>
									$total_dias_2
								</font>
							</td>");

				echo "<td colspan=3></td>";
				fputs ($fp,"<td colspan=3 bgcolor='#485989'></td>");

				echo "</tr>";
				fputs ($fp,"</tr>");

				$colspan_2 = 12;


				if ($total_hd_2) {
					$total_2 = number_format($total_dias_2 / $total_hd_2, 2, ",", "");
				}
				else {
					$total_2 = "0.00";
				}

				echo "<tr class=Principal>
					<td colspan=$colspan_2>
					MÉDIA DE DIAS POR CHAMADO NO PERÍODO CONSULTADO: " . $total_2 . "
					</td>
				</tr>
				</table><br><br>";

				fputs ($fp,"<tr class=Principal>
					<td colspan=10 bgcolor='#485989'><center><font color='#FFFFFF'>
					MÉDIA DE DIAS POR CHAMADO NO PERÍODO CONSULTADO: " . $total_2 . "</font></center>
					</td>
				</tr>
				</table><br><br>");

		}
		/* === FIM CHAMADOS AGUARDADO PEÇA === */












		 
	    /* === INICIO CHAMADOS RESOLVIDO === */

		$sql_resolvido = "SELECT 
						hd_chamado,
						cliente_admin,
						produto_nome,
						produto_referencia,
						data,
						data_intervalo,
						consumidor_nome,
						data_atual,
						cidade_admin,
						estado_admin 
						FROM temp_rtc_mostra_hd WHERE status='Resolvido'";
		//echo "<br><br>".nl2br($sql_resolvido);
		$res_resolvido = pg_query($con, $sql_resolvido);
	   	if (pg_num_rows($res_resolvido) > 0) {

			echo "<TABLE width='700' border='1' align='center' cellspacing='2' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>";
			fputs ($fp,"<TABLE width='700' border='1' align='center' cellspacing='2' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>");
			
			
			echo "<tr class='Principal'>
					<td nowrap colspan='12'>Resolvido</td>
				 </tr>";
			fputs ($fp,"<tr class='Principal'>
						  <td nowrap colspan='10' bgcolor='#485989'><font color='#FFFFFF'><center>Resolvido</center></font></td>
						</tr>");

			echo "<tr  class='Principal'>
			<td>Atendimento</td>";
			fputs ($fp,"<tr  class='Principal'>
			<td bgcolor='#485989'><font color='#FFFFFF'>Atendimento</font></td>");

			echo "<td>Cliente ADM</td>";
			fputs ($fp,"<td bgcolor='#485989'><font color='#FFFFFF'>Cliente ADM</font></td>");
			echo "<td colspan='2'>PRODUTO</td><td>Abertura</font></td>";
			fputs ($fp,"<td colspan='2' bgcolor='#485989'><font color='#FFFFFF'>PRODUTO</td><td bgcolor='#485989'><font color='#FFFFFF'>Abertura</font></td>");

			echo "<td>Data Atual</td>";
			fputs ($fp,"<td bgcolor='#485989'><font color='#FFFFFF'>Data Atual</font></td>");
			echo "<td>Qtde Dias</td>";
			fputs ($fp,"<td bgcolor='#485989'><font color='#FFFFFF'>Qtde Dias</font></td>");

			echo "<td colspan=1>Consumidor</td>";
			fputs ($fp,"<td colspan=1 bgcolor='#485989'><font color='#FFFFFF'>Consumidor</font></td>");
			

			echo "<td colspan=1>Cidade</td>";
			fputs ($fp,"<td colspan=1 bgcolor='#485989'><font color='#FFFFFF'>Cidade</font></td>");

			echo "<td colspan=1>Estado</td>";
			fputs ($fp,"<td colspan=1 bgcolor='#485989'><font color='#FFFFFF'>Estado</font></td>");
			
			echo "</tr>";
			fputs ($fp,"</tr>");
			

			
			for($c=0;$c < pg_num_rows($res_resolvido);$c++) {
				
				$hd_chamado			= "";
				$cliente_admin		= "";
				$produto_nome		= "";
				$produto_referencia = "";
				$data_abertura		= "";
				$data_atual			= "";
				$data_intervalo		= "";
				$consumidor_nome	= "";
				$cidade_admin		= "";
				$estado_admin		= "";

				$hd_chamado			= pg_fetch_result($res_resolvido,$c,hd_chamado);
				$cliente_admin		= pg_fetch_result($res_resolvido,$c,cliente_admin);
				$produto_nome		= pg_fetch_result($res_resolvido,$c,produto_nome);
				$produto_referencia = pg_fetch_result($res_resolvido,$c,produto_referencia);
				$data_abertura		= pg_fetch_result($res_resolvido,$c,data);
				$data_atual			= pg_fetch_result($res_resolvido,$c,data_atual);
				$data_intervalo		= pg_fetch_result($res_resolvido,$c,data_intervalo);
				$consumidor_nome	= pg_fetch_result($res_resolvido,$c,consumidor_nome);
				$cidade_admin		= pg_fetch_result($res_resolvido,$c,cidade_admin);
				$estado_admin		= pg_fetch_result($res_resolvido,$c,estado_admin);
			   
				$total_hd_3++;
				$total_dias_3 += intval($data_intervalo);

				if ($i % 2 == 0) {
					$cor = "#F1F4FA";
					$btn = "azul";
				}else{
					$cor = "#F7F5F0";
					$btn = "amarelo";
				}

				echo "<TR class='Conteudo' style='background-color: $cor;'>\n";
				fputs ($fp,"<TR class='Conteudo'>");


				echo "<TD nowrap style='text-align: left;'>";
				fputs ($fp,"<TD nowrap style='background-color: $cor;text-align: left;'>");
				echo '<a href="callcenter_interativo_new.php?callcenter=' . $hd_chamado . '" target="_blank">' . $hd_chamado .'</a>';
				fputs ($fp,"$hd_chamado");
				echo "</td>";
				fputs ($fp,"</td>");



				echo "<td nowrap style='text-align: left;'>$cliente_admin</td>";
				fputs ($fp,"<td nowrap  style='background-color: $cor;text-align: left;'>$cliente_admin</td>");

				echo "<TD nowrap style='text-align: left;'>$produto_referencia</td>";
				fputs ($fp,"<TD nowrap  style='background-color: $cor;text-align: left;'>$produto_referencia</td>");
				echo "<TD nowrap style='text-align: left;'>$produto_nome</td>";
				fputs ($fp,"<TD nowrap  style='background-color: $cor;text-align: left;'>$produto_nome</td>");
				echo "<TD nowrap style='text-align: left;'>$data_abertura</td>";
				fputs ($fp,"<TD nowrap  style='background-color: $cor;text-align: left;'>$data_abertura</td>");
				
				$data_atual_x = date('d/m/Y');
				echo "<td nowrap style='text-align: left;'>$data_atual</td>";
				fputs ($fp,"<td nowrap  style='background-color: $cor;text-align: left;'>$data_atual</td>");
				echo "<TD nowrap>$data_intervalo</td>";
				fputs ($fp,"<TD nowrap  style='background-color: $cor;'>$data_intervalo</td>");


				echo "<td nowrap style='text-align: left;'>$consumidor_nome</td>
				<td nowrap style='text-align: left;'>$cidade_admin</td>
				<td nowrap style='text-align: left;'>$estado_admin</td>";
				fputs ($fp,"<td nowrap  style='background-color: $cor;text-align: left;'>$consumidor_nome</td>
				<td nowrap>$cidade_admin</td>
				<td nowrap>$estado_admin</td>");


				echo "</tr>";
				fputs ($fp,"</tr>");

			}

				$colspan_3 = 5;


				echo "<tr class=Principal>
						<td>$total_hd_3</td>
						<td colspan=$colspan_3></td>
						<td>$total_dias_3</td>";
			   fputs ($fp,"<tr class=Principal>
							<td bgcolor='#485989'>
								<font color='#FFFFFF'>
									$total_hd_3
								</font>
							</td>
							<td colspan=$colspan_3 bgcolor='#485989'>
							</td>
							<td bgcolor='#485989'>
								<font color='#FFFFFF'>
									$total_dias_3
								</font>
							</td>");

				echo "<td colspan=3></td>";
				fputs ($fp,"<td colspan=3 bgcolor='#485989'></td>");

				echo "</tr>";
				fputs ($fp,"</tr>");

				$colspan_3 = 12;


				if ($total_hd_3) {
					$total_3 = number_format($total_dias_3 / $total_hd_3, 2, ",", "");
				}
				else {
					$total_3 = "0.00";
				}

				echo "<tr class=Principal>
					<td colspan=$colspan_3>
					MÉDIA DE DIAS POR CHAMADO NO PERÍODO CONSULTADO: " . $total_3 . "
					</td>
				</tr>
				</table><br><br>";

				fputs ($fp,"<tr class=Principal>
					<td colspan=10 bgcolor='#485989'><center><font color='#FFFFFF'>
					MÉDIA DE DIAS POR CHAMADO NO PERÍODO CONSULTADO: " . $total_3 . "</font></center>
					</td>
				</tr>
				</table><br><br>");

		}
		/* === FIM CHAMADOS RESOLVIDO === */





		if(file_exists($caminho_arquivo)) {
			echo "<br>";
			echo "<table width='700px' border='0' cellspacing='2' cellpadding='2' align='center'>";
			echo "<tr>";
			echo "<td align='center'><button type='button' onclick=\"window.location='$caminho_arquivo'\">Download em Excel</button></td>";
			echo "</tr>";
			echo "</table>";
		}

}

include_once "rodape.php";
?>