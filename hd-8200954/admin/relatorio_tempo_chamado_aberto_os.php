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

$title = "Relatório de Tempo de OS abertas";

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

if (strlen($familia)) {
	$familia = intval($familia);
	$sql = "SELECT familia FROM tbl_familia WHERE fabrica=$login_fabrica";
	$res = pg_query($con, $sql);
	if (pg_num_rows($res) == 0) {
		$msg_erro = "Família escolhida não existe";
	}
}

if (strlen($linha)) {
	$linha = intval($linha);
	$sql = "SELECT linha FROM tbl_linha WHERE fabrica=$login_fabrica";
	$res = pg_query($con, $sql);
	if (pg_num_rows($res) == 0) {
		$msg_erro = "Linha escolhida não existe";
	}
}

if (strlen($posto)) {
	$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica=$login_fabrica AND posto=$posto";
	$res = pg_query($con, $sql);
	if (pg_num_rows($res) == 0) {
		$msg_erro = "Posto inexistente";
	}
	else {
		$posto_id = pg_fetch_result($res, 0, 0);
	}
}

if (strlen($produto_referencia)) {
	$sql = "SELECT produto FROM tbl_produto JOIN tbl_linha ON tbl_produto.linha=tbl_linha.linha WHERE tbl_linha.fabrica=$login_fabrica AND tbl_produto.referencia ilike '$produto_referencia'";
	$res = pg_query($con, $sql);
	if (pg_num_rows($res) == 0) {
		$msg_erro = "Produto $produto_referencia inexistente";
	}
}

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

	$cond_os = "AND tbl_os.finalizada IS NULL";

	if ($login_fabrica == 52) {
		$select_52 = ",
					tbl_cliente_admin.nome AS cliente_admin_nome,
					tbl_os.consumidor_nome,
					tbl_os.consumidor_cidade,
					tbl_os.consumidor_estado";

		$from_52 = "
					JOIN tbl_cliente_admin ON tbl_os.cliente_admin=tbl_cliente_admin.cliente_admin";

		$groupby_52 = ", tbl_cliente_admin.nome, tbl_os.consumidor_nome, tbl_os.consumidor_cidade, tbl_os.consumidor_estado";
	}

	$sql_peca = "SELECT	DISTINCT
						tbl_os.os                                                               ,
						tbl_os.status_checkpoint											    ,
						tbl_os.sua_os                                                           ,
						tbl_os.hd_chamado													    ,
						TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY')        AS data_fechamento  ,
						TO_CHAR(tbl_os.data_conserto,'DD/MM/YYYY')        AS data_conserto      ,
						TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')          AS data_abertura    ,
						tbl_produto.referencia                                                  ,
						tbl_produto.descricao                                                   ,
						SUM(DISTINCT current_date::date - tbl_os.data_abertura) AS data_diferenca
						$select_52
						INTO TEMP temp_relatorio_aberto_pecas_$mes
				FROM    tbl_os
				JOIN    tbl_produto    ON tbl_os.produto = tbl_produto.produto
				$from_52

				WHERE   tbl_os.fabrica = $login_fabrica
				AND		tbl_os.posto   = $posto
				AND		tbl_os.data_abertura > '$data_inicial'
				AND		status_checkpoint IN ('1','2') 
				$cond_familia
				$cond_produto
				$cond_linha
				$cond_cliente_admin
				";
				//HD 216470: Retirada a subquery e substitida por condições na cláusula WHERE
				$sql_peca .="GROUP BY tbl_os.os,tbl_os.status_checkpoint,tbl_os.sua_os , tbl_os.hd_chamado, tbl_os.data_fechamento , tbl_os.data_conserto, tbl_os.data_abertura , tbl_produto.referencia , tbl_produto.descricao $groupby_52";
				$sql_peca .= " ORDER BY SUM(DISTINCT current_date::date - tbl_os.data_abertura)";
				$res_peca = pg_query($con,$sql_peca);
       	//echo "<br><br><br>";
	    //echo nl2br($sql_peca);
	    //echo "<br><br><br>";



		$sql_finalizada ="SELECT	DISTINCT
								tbl_os.os                                                               ,
								tbl_os.status_checkpoint												,
								tbl_os.sua_os                                                           ,
								tbl_os.hd_chamado,
								TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY')        AS data_fechamento  ,
								TO_CHAR(tbl_os.data_conserto,'DD/MM/YYYY')        AS data_conserto  ,
								TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')          AS data_abertura    ,
								tbl_produto.referencia                                                  ,
								tbl_produto.descricao                                                   ,
								SUM(DISTINCT current_date::date - tbl_os.data_abertura) AS data_diferenca
								$select_52
								INTO TEMP temp_relatorio_finalizada_$mes
						FROM    tbl_os
						JOIN    tbl_produto    ON tbl_os.produto = tbl_produto.produto
						$from_52

						WHERE   tbl_os.fabrica = $login_fabrica
						AND		tbl_os.posto   = $posto
						AND		tbl_os.data_abertura > '$data_inicial'
						AND		tbl_os.finalizada IS NOT NULL 
						$cond_familia
						$cond_produto
						$cond_linha
						$cond_cliente_admin
						";
						//HD 216470: Retirada a subquery e substitida por condições na cláusula WHERE
						$sql_finalizada .="GROUP BY tbl_os.os,tbl_os.status_checkpoint,tbl_os.sua_os , tbl_os.hd_chamado, tbl_os.data_fechamento , tbl_os.data_conserto, tbl_os.data_abertura , tbl_produto.referencia , tbl_produto.descricao $groupby_52";
						$sql_finalizada .= " ORDER BY SUM(DISTINCT current_date::date - tbl_os.data_abertura)";
						$res_finalizada = pg_query($con,$sql_finalizada);
	 //echo "<br><br><br>";
	 //echo nl2br($sql_finalizada);
	 //echo "<br><br><br>";

	 $sql_bus_posto =  "select * FROM temp_relatorio_aberto_pecas_$mes where status_checkpoint = '1'";
   	 //echo nl2br($sql_bus_posto);
	 $res = pg_exec($con,$sql_bus_posto);


	
			$sql_posto = "
		SELECT
		nome,
		cidade,
		estado

		FROM
		tbl_posto

		WHERE
		posto=$posto
		";
		$res_posto = pg_query($con, $sql_posto);
		$posto_nome = pg_fetch_result($res_posto, 0, nome);
		$posto_cidade = pg_fetch_result($res_posto, 0, cidade);
		$posto_estado = pg_fetch_result($res_posto, 0, estado);

		$data_inicial_press = explode(" ", $data_inicial);
		$data_inicial_press = implode("/", array_reverse(explode("-", $data_inicial_press[0])));

		$data_final_press = explode(" ", $data_final);
		$data_final_press = implode("/", array_reverse(explode("-", $data_final_press[0])));
		
		
		echo "<TABLE width='700' border='1' align='center' cellspacing='2' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>
		<tr class='Principal'>
		<td>Posto</td>
		<td>$posto_nome ($posto_cidade - $posto_estado)</td>
		</tr>
		<tr class=Conteudo>
		<td>Período (data finalizada)</td>
		<td>$data_inicial_press até $data_final_press</td>
		</tr>
		<tr class=Conteudo>
		<td>Data de Referência <br>para tempo conserto<br>(conserto ou finalização)</td>
		<td>" . ucwords(str_replace("_", " ", $data_referencia)) . "</td>
		</tr>";

		fputs ($fp,"<TABLE width='700' border='1' align='center' cellspacing='2' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>
		<tr  class='Principal'>
		<td bgcolor='#485989'><font color='#FFFFFF'>Posto</font></td>
		<td bgcolor='#485989'><font color='#FFFFFF'>$posto_nome ($posto_cidade - $posto_estado)</font></td>
		</tr>
		<tr class=Conteudo>
		<td>Período (data finalizada)</td>
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
		}
		else {
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


	//echo nl2br($sql);
	if (pg_num_rows($res) > 0) {

			echo "<TABLE width='700' border='1' align='center' cellspacing='2' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>";
			fputs ($fp,"<TABLE width='700' border='1' align='center' cellspacing='2' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>");
			
			
			echo "<tr class='Principal'>
					<td nowrap colspan='12'>Abertos</td>
				 </tr>";
			fputs ($fp,"<tr class='Principal'>
						  <td nowrap colspan='11' bgcolor='#485989'><font color='#FFFFFF'><center>Abertos</center></font></td>
						</tr>");

			echo "<tr  class='Principal'>
			<td>OS</td>
			<td>Atendimento</td>";
			fputs ($fp,"<tr  class='Principal'>
			<td bgcolor='#485989'><font color='#FFFFFF'>OS</font></td>
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


		$total_os = 0;
		$total_dias = 0;

		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
			$os                 = pg_fetch_result($res,$i,os);
			$sua_os             = pg_fetch_result($res,$i,sua_os);
			$total_diferenca    = pg_fetch_result($res,$i,data_diferenca);
			$referencia         = pg_fetch_result($res,$i,referencia);
			$descricao          = pg_fetch_result($res,$i,descricao);
			$data_conserto      = pg_fetch_result($res,$i,data_conserto);
			$data_fechamento    = pg_fetch_result($res,$i,data_fechamento);
			$data_abertura      = pg_fetch_result($res,$i,data_abertura);

			$atendimento = pg_fetch_result($res, $i, 'hd_chamado');

			if ($login_fabrica == 52) {
				$cliente_admin_nome = pg_fetch_result($res,$i,cliente_admin_nome);
				$consumidor_nome    = pg_fetch_result($res,$i,consumidor_nome);
				$consumidor_cidade  = pg_fetch_result($res,$i,consumidor_cidade);
				$consumidor_estado  = pg_fetch_result($res,$i,consumidor_estado);
			}

			$total_os++;
			$total_dias += intval($total_diferenca);

			if ($i % 2 == 0) {
				$cor = "#F1F4FA";
				$btn = "azul";
			}else{
				$cor = "#F7F5F0";
				$btn = "amarelo";
			}

			echo "<TR class='Conteudo' style='background-color: $cor;'>\n";
			fputs ($fp,"<TR class='Conteudo'>");
			echo "<TD nowrap>";
			fputs ($fp,"<TD nowrap  style='background-color: $cor;'>");

			echo "<a href='os_press.php?os=$os' target='_blank'>$sua_os</a></TD>\n";
			fputs ($fp,"$sua_os</TD>");

			
			echo "<TD nowrap>";
			fputs ($fp,"<TD nowrap style='background-color: $cor;'>");

			echo '<a href="callcenter_interativo_new.php?callcenter=' . $atendimento . '" target="_blank">' . $atendimento .'</a>';
			fputs ($fp,"$atendimento");

			echo "</td>";
			fputs ($fp,"</td>");

			echo "<td nowrap>$cliente_admin_nome</td>";
		    fputs ($fp,"<td nowrap  style='background-color: $cor;'>$cliente_admin_nome</td>");

			echo "<TD nowrap>$referencia</td>";
			fputs ($fp,"<TD nowrap  style='background-color: $cor;'>$referencia</td>");
			echo "<TD nowrap>$descricao</td>";
			fputs ($fp,"<TD nowrap  style='background-color: $cor;'>$descricao</td>");
			echo "<TD nowrap>$data_abertura</td>";
			fputs ($fp,"<TD nowrap  style='background-color: $cor;'>$data_abertura</td>");
		    
			$data_atual_x = date('d/m/Y');
			echo "<td nowrap>$data_atual_x</td>";
			fputs ($fp,"<td nowrap  style='background-color: $cor;'>$data_atual_x</td>");
			echo "<TD nowrap>$total_diferenca</td>";
			fputs ($fp,"<TD nowrap  style='background-color: $cor;'>$total_diferenca</td>");


			echo "<td nowrap>$consumidor_nome</td>
			<td nowrap>$consumidor_cidade</td>
			<td nowrap>$consumidor_estado</td>";
			fputs ($fp,"<td nowrap  style='background-color: $cor;'>$consumidor_nome</td>
			<td nowrap>$consumidor_cidade</td>
			<td nowrap>$consumidor_estado</td>");


			echo "</tr>";
			fputs ($fp,"</tr>");
		}

			$colspan = 6;


			echo "<tr class=Principal>
					<td>$total_os</td>
					<td colspan=$colspan></td>
					<td>$total_dias</td>";
		   fputs ($fp,"<tr class=Principal>
						<td bgcolor='#485989'>
							<font color='#FFFFFF'>
								$total_os
							</font>
						</td>
						<td colspan=$colspan bgcolor='#485989'>
						</td>
						<td bgcolor='#485989'>
							<font color='#FFFFFF'>
								$total_dias
							</font>
						</td>");

			echo "<td colspan=3></td>";
			fputs ($fp,"<td colspan=3 bgcolor='#485989'></td>");

			echo "</tr>";
			fputs ($fp,"</tr>");

			$colspan = 12;


			if ($total_os) {
				$total = number_format($total_dias / $total_os, 2, ",", "");
			}
			else {
				$total = "0.00";
			}

			echo "<tr class=Principal>
				<td colspan=$colspan>
				MÉDIA DE DIAS POR OS NO PERÍODO CONSULTADO: " . $total . "
				</td>
			</tr>
			</table><br><br>";

			fputs ($fp,"<tr class=Principal>
				<td colspan=11 bgcolor='#485989'><center><font color='#FFFFFF'>
				MÉDIA DE DIAS POR OS NO PERÍODO CONSULTADO: " . $total . "</font></center>
				</td>
			</tr>
			</table><br><br>");
	}





	  $sql_bus_posto_b =  "select * FROM temp_relatorio_aberto_pecas_$mes where status_checkpoint = '2'";
	  $res_busca_b = pg_exec($con,$sql_bus_posto_b);
	  	if (pg_num_rows($res_busca_b) > 0) {

			echo "<TABLE width='700' border='1' align='center' cellspacing='2' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>";
			fputs ($fp,"<TABLE width='700' border='1' align='center' cellspacing='2' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>");
			

			echo "<tr class='Principal'>
					<td nowrap colspan='12'>Aguardando peça</td>
				 </tr>";
			fputs ($fp,"<tr class='Principal'>
						  <td nowrap colspan='11' bgcolor='#485989'><font color='#FFFFFF'><center>Aguardando peça</center></font></td>
						</tr>");

			echo "<tr  class='Principal'>
			<td>OS</td>
			<td>Atendimento</td>";
			fputs ($fp,"<tr  class='Principal'>
			<td bgcolor='#485989'><font color='#FFFFFF'>OS</font></td>
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


		$total_os_2 = 0;
		$total_dias_2 = 0;

		for ($b = 0 ; $b < pg_num_rows ($res_busca_b) ; $b++) {
			$os_2                 = pg_fetch_result($res_busca_b,$b,os);
			$sua_os_2             = pg_fetch_result($res_busca_b,$b,sua_os);
			$total_diferenca_2    = pg_fetch_result($res_busca_b,$b,data_diferenca);
			$referencia_2         = pg_fetch_result($res_busca_b,$b,referencia);
			$descricao_2          = pg_fetch_result($res_busca_b,$b,descricao);
			$data_conserto_2      = pg_fetch_result($res_busca_b,$b,data_conserto);
			$data_fechamento_2    = pg_fetch_result($res_busca_b,$b,data_fechamento);
			$data_abertura_2      = pg_fetch_result($res_busca_b,$b,data_abertura);

			$atendimento_2 = pg_fetch_result($res_busca_b, $b, 'hd_chamado');

			if ($login_fabrica == 52) {
				$cliente_admin_nome_2 = pg_fetch_result($res_busca_b,$b,cliente_admin_nome);
				$consumidor_nome_2    = pg_fetch_result($res_busca_b,$b,consumidor_nome);
				$consumidor_cidade_2  = pg_fetch_result($res_busca_b,$b,consumidor_cidade);
				$consumidor_estado_2  = pg_fetch_result($res_busca_b,$b,consumidor_estado);
			}

			$total_os_2++;
			$total_dias_2 += intval($total_diferenca_2);

			if ($b % 2 == 0) {
				$cor_2 = "#F1F4FA";
				$btn_2 = "azul";
			}else{
				$cor_2 = "#F7F5F0";
				$btn_2 = "amarelo";
			}

			echo "<TR class='Conteudo' style='background-color: $cor_2;'>\n";
			fputs ($fp,"<TR class='Conteudo'>");
			echo "<TD nowrap>";
			fputs ($fp,"<TD nowrap  style='background-color: $cor_2;'>");

			echo "<a href='os_press.php?os=$os_2' target='_blank'>$sua_os_2</a></TD>\n";
			fputs ($fp,"$sua_os_2</TD>");

			
			echo "<TD nowrap>";
			fputs ($fp,"<TD nowrap style='background-color: $cor_2;'>");

			echo '<a href="callcenter_interativo_new.php?callcenter=' . $atendimento_2 . '" target="_blank">' . $atendimento_2 .'</a>';
			fputs ($fp,"$atendimento_2");

			echo "</td>";
			fputs ($fp,"</td>");

			echo "<td nowrap>$cliente_admin_nome_2</td>";
		    fputs ($fp,"<td nowrap  style='background-color: $cor_2;'>$cliente_admin_nome_2</td>");

			echo "<TD nowrap>$referencia_2</td>";
			fputs ($fp,"<TD nowrap  style='background-color: $cor_2;'>$referencia_2</td>");
			echo "<TD nowrap>$descricao</td>";
			fputs ($fp,"<TD nowrap  style='background-color: $cor_2;'>$descricao_2</td>");
			echo "<TD nowrap>$data_abertura_2</td>";
			fputs ($fp,"<TD nowrap  style='background-color: $cor_2;'>$data_abertura_2</td>");
		    
			$data_atual_x_2 = date('d/m/Y');
			echo "<td nowrap>$data_atual_x_2</td>";
			fputs ($fp,"<td nowrap  style='background-color: $cor_2;'>$data_atual_x_2</td>");
			echo "<TD nowrap>$total_diferenca_2</td>";
			fputs ($fp,"<TD nowrap  style='background-color: $cor_2;'>$total_diferenca_2</td>");


			echo "<td nowrap>$consumidor_nome_2</td>
			<td nowrap>$consumidor_cidade_2</td>
			<td nowrap>$consumidor_estado_2</td>";
			fputs ($fp,"<td nowrap  style='background-color: $cor_2;'>$consumidor_nome_2</td>
			<td nowrap>$consumidor_cidade_2</td>
			<td nowrap>$consumidor_estado_2</td>");


			echo "</tr>";
			fputs ($fp,"</tr>");
		}

			$colspan_2 = 6;


			echo "<tr class=Principal>
					<td>$total_os_2</td>
					<td colspan=$colspan_2></td>
					<td>$total_dias_2</td>";
		   fputs ($fp,"<tr class=Principal>
						<td bgcolor='#485989'>
							<font color='#FFFFFF'>
								$total_os_2
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


			if ($total_os) {
				$total_2 = number_format($total_dias_2 / $total_os_2, 2, ",", "");
			}
			else {
				$total_2 = "0.00";
			}

			echo "<tr class=Principal>
				<td colspan=$colspan>
				MÉDIA DE DIAS POR OS NO PERÍODO CONSULTADO: " . $total_2 . "
				</td>
			</tr>
			</table><br><br>";

			fputs ($fp,"<tr class=Principal>
				<td colspan=11 bgcolor='#485989'><center><font color='#FFFFFF'>
				MÉDIA DE DIAS POR OS NO PERÍODO CONSULTADO: " . $total_2 . "</font></center>
				</td>
			</tr>
			</table><br><br>");
		}



		
	  $sql_bus_posto_c =  "select * FROM temp_relatorio_finalizada_$mes";
	  $res_busca_c = pg_exec($con,$sql_bus_posto_c);
	  	if (pg_num_rows($res_busca_c) > 0) {

			echo "<TABLE width='700' border='1' align='center' cellspacing='2' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>";
			fputs ($fp,"<TABLE width='700' border='1' align='center' cellspacing='2' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>");
		

			echo "<tr class='Principal'>
					<td nowrap colspan='12'>Resolvidos</td>
				 </tr>";
			fputs ($fp,"<tr class='Principal'>
						  <td nowrap colspan='11' bgcolor='#485989'><font color='#FFFFFF'><center>Resolvidos</center></font></td>
						</tr>");

			echo "<tr  class='Principal'>
			<td>OS</td>
			<td>Atendimento</td>";
			fputs ($fp,"<tr  class='Principal'>
			<td bgcolor='#485989'><font color='#FFFFFF'>OS</font></td>
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


		$total_os_3 = 0;
		$total_dias_3 = 0;

		for ($b = 0 ; $b < pg_num_rows ($res_busca_c) ; $b++) {
			$os_3                 = pg_fetch_result($res_busca_c,$b,os);
			$sua_os_3             = pg_fetch_result($res_busca_c,$b,sua_os);
			$total_diferenca_3    = pg_fetch_result($res_busca_c,$b,data_diferenca);
			$referencia_3         = pg_fetch_result($res_busca_c,$b,referencia);
			$descricao_3          = pg_fetch_result($res_busca_c,$b,descricao);
			$data_conserto_3      = pg_fetch_result($res_busca_c,$b,data_conserto);
			$data_fechamento_3    = pg_fetch_result($res_busca_c,$b,data_fechamento);
			$data_abertura_3      = pg_fetch_result($res_busca_c,$b,data_abertura);

			$atendimento_3 = pg_fetch_result($res_busca_c, $b, 'hd_chamado');

			if ($login_fabrica == 52) {
				$cliente_admin_nome_3 = pg_fetch_result($res_busca_c,$b,cliente_admin_nome);
				$consumidor_nome_3    = pg_fetch_result($res_busca_c,$b,consumidor_nome);
				$consumidor_cidade_3  = pg_fetch_result($res_busca_c,$b,consumidor_cidade);
				$consumidor_estado_3  = pg_fetch_result($res_busca_c,$b,consumidor_estado);
			}

			$total_os_3++;
			$total_dias_3 += intval($total_diferenca_3);

			if ($b % 2 == 0) {
				$cor_3 = "#F1F4FA";
				$btn_3 = "azul";
			}else{
				$cor_3 = "#F7F5F0";
				$btn_3 = "amarelo";
			}

			echo "<TR class='Conteudo' style='background-color: $cor_3;'>\n";
			fputs ($fp,"<TR class='Conteudo'>");
			echo "<TD nowrap>";
			fputs ($fp,"<TD nowrap  style='background-color: $cor_3;'>");

			echo "<a href='os_press.php?os=$os_3' target='_blank'>$sua_os_3</a></TD>\n";
			fputs ($fp,"$sua_os_3</TD>");

			
			echo "<TD nowrap>";
			fputs ($fp,"<TD nowrap style='background-color: $cor_3;'>");

			echo '<a href="callcenter_interativo_new.php?callcenter=' . $atendimento_3 . '" target="_blank">' . $atendimento_3 .'</a>';
			fputs ($fp,"$atendimento_3");

			echo "</td>";
			fputs ($fp,"</td>");

			echo "<td nowrap>$cliente_admin_nome_3</td>";
		    fputs ($fp,"<td nowrap  style='background-color: $cor_3;'>$cliente_admin_nome_3</td>");

			echo "<TD nowrap>$referencia_3</td>";
			fputs ($fp,"<TD nowrap  style='background-color: $cor_3;'>$referencia_3</td>");
			echo "<TD nowrap>$descricao</td>";
			fputs ($fp,"<TD nowrap  style='background-color: $cor_3;'>$descricao_3</td>");
			echo "<TD nowrap>$data_abertura_3</td>";
			fputs ($fp,"<TD nowrap  style='background-color: $cor_3;'>$data_abertura_3</td>");
		    
			$data_atual_x_3 = date('d/m/Y');
			echo "<td nowrap>$data_atual_x_3</td>";
			fputs ($fp,"<td nowrap  style='background-color: $cor_3;'>$data_atual_x_3</td>");
			echo "<TD nowrap>$total_diferenca_3</td>";
			fputs ($fp,"<TD nowrap  style='background-color: $cor_3;'>$total_diferenca_3</td>");


			echo "<td nowrap>$consumidor_nome_3</td>
			<td nowrap>$consumidor_cidade_3</td>
			<td nowrap>$consumidor_estado_3</td>";
			fputs ($fp,"<td nowrap  style='background-color: $cor_3;'>$consumidor_nome_3</td>
			<td nowrap>$consumidor_cidade_3</td>
			<td nowrap>$consumidor_estado_3</td>");


			echo "</tr>";
			fputs ($fp,"</tr>");
		}

			$colspan_3 = 6;


			echo "<tr class=Principal>
					<td>$total_os_3</td>
					<td colspan=$colspan_3></td>
					<td>$total_dias_3</td>";
		   fputs ($fp,"<tr class=Principal>
						<td bgcolor='#485989'>
							<font color='#FFFFFF'>
								$total_os_3
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


			if ($total_os_3) {
				$total_3 = number_format($total_dias_3 / $total_os_3, 2, ",", "");
			}
			else {
				$total_3 = "0.00";
			}

			echo "<tr class=Principal>
				<td colspan=$colspan_3>
				MÉDIA DE DIAS POR OS NO PERÍODO CONSULTADO: " . $total_3 . "
				</td>
			</tr>
			</table><br><br>";

			fputs ($fp,"<tr class=Principal>
				<td colspan=11 bgcolor='#485989'><center><font color='#FFFFFF'>
				MÉDIA DE DIAS POR OS NO PERÍODO CONSULTADO: " . $total_3 . "</font></center>
				</td>
			</tr>
			</table><br><br>");
		}


}


if(file_exists($caminho_arquivo)) {
	echo "<br>";
	echo "<table width='700px' border='0' cellspacing='2' cellpadding='2' align='center'>";
	echo "<tr>";
	echo "<td align='center'><button type='button' onclick=\"window.location='$caminho_arquivo'\">Download em Excel</button></td>";
	echo "</tr>";
	echo "</table>";
}

include_once "rodape.php";
?>
