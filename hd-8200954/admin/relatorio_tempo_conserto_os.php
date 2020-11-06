<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
if ($trava_cliente_admin) {
	$admin_privilegios="call_center";
	$layout_menu = "callcenter";
}else {
	$admin_privilegios="gerencia,financeiro";
	$layout_menu = "gerencia";
}
include "autentica_admin.php";

$excel = $_GET["excel"];

$title = "Relatório de Tempo de Permanência em Conserto";

if ($excel) {
	ob_start();
}
else {
	include "cabecalho.php";
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

<?

//HD 216470: Adicionando algumas validações básicas
$posto				= trim($_GET['posto']);
$mes				= trim($_GET['mes']);
$ano				= trim($_GET['ano']);
$data_inicial 		= trim($_GET['data_inicial']);
$data_final 		= trim($_GET['data_final']);
$estado				= trim($_GET['estado']);
$pais				= trim($_GET['pais']);
$linha				= trim($_GET['linha']);
$familia			= trim($_GET['familia']);
$cliente_admin		= trim($_GET['cliente_admin']);
$produto_referencia	= trim($_GET['produto_referencia']);
$periodo			= trim($_GET['periodo']);
$tipo_os			= trim($_GET['tipo_os']);
$data_referencia	= trim($_GET["data_referencia"]);
if ($login_fabrica == 52)
{
	if (strlen($_GET['cidade']) > 0)
	{
		$cidade = trim($_GET["cidade"]);
	}
	if (strlen($_GET['marca_logo']) > 0)
	{
		$marca_logo = trim($_GET["marca_logo"]);
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

if ($data_referencia == "") {
	$data_referencia = "data_fechamento";
}

// Efetua validação de mês e ano apenas se não foi informado a data inicial e a data final
if(!strlen($data_inicial) or !strlen($data_final)) {

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
}

if (strlen($msg_erro)) {
	echo "
	<br>
	<div width=100% style='background-color:#CC0000; color:#FFFFFF; font-size:11pt; font-weight: bold;'>$msg_erro</div>
	<br>";
}
else {
	if(!strlen($data_inicial) and !strlen($data_final)) {
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

	} /*else {

		$data_inicial .= ' 00:00';
		$data_final   .= ' 23:59';
	}*/


	//HD 216470: Retirada a subquery e substitida por condições na cláusula WHERE
	if ($familia) {
		$cond_familia = "AND tbl_produto.familia=$familia";
	}

	if ($produto_referencia) {
		$cond_produto = "AND tbl_produto.referencia = '$produto_referencia'";
	}

	//HD 216470: Adicionada a busca por linha
	if ($linha) {
		$cond_linha = "AND tbl_produto.linha in ($linha) ";
	}

	//HD 216470: Adicionada a busca por cliente admin
	if ($cliente_admin) {
		$cond_cliente_admin = "AND tbl_os.cliente_admin=$cliente_admin";

		if ($login_fabrica == 52)
		{
			$select_52 = ", tbl_cliente_admin.nome AS cliente_admin_nome,
					tbl_os.consumidor_nome,
					tbl_os.marca,
					tbl_os.consumidor_cidade,
					tbl_os.consumidor_estado,
					tbl_numero_serie.ordem";
			$from_52 = "
					JOIN tbl_cliente_admin ON tbl_os.cliente_admin=tbl_cliente_admin.cliente_admin
					LEFT JOIN tbl_numero_serie ON tbl_os.produto = tbl_numero_serie.produto AND tbl_os.serie = tbl_numero_serie.serie AND tbl_numero_serie.fabrica = $login_fabrica";
			$groupby_52 = ", tbl_cliente_admin.nome, tbl_os.consumidor_nome,tbl_os.marca, tbl_os.consumidor_cidade, tbl_os.consumidor_estado, tbl_numero_serie.ordem";

		}
	}

	if ($tipo_os == "todas") {
	}

	if ($login_fabrica == 52) {

			$select_52 =", tbl_cliente_admin.nome AS cliente_admin_nome";
			$from_52 = "LEFT JOIN tbl_cliente_admin ON tbl_os.cliente_admin=tbl_cliente_admin.cliente_admin ";
			$groupby_52 = ", tbl_cliente_admin.nome ";

		$select_52 .= ",
					tbl_os.consumidor_nome,
					tbl_os.consumidor_cidade,
					tbl_os.marca,
					tbl_os.consumidor_estado,
					tbl_numero_serie.ordem,
					tbl_hd_chamado.protocolo_cliente";

		$from_52 .= "
					LEFT JOIN tbl_numero_serie ON tbl_os.produto = tbl_numero_serie.produto AND tbl_os.serie = tbl_numero_serie.serie AND tbl_numero_serie.fabrica = $login_fabrica
					LEFT JOIN tbl_hd_chamado ON tbl_os.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado.fabrica = $login_fabrica";

		$groupby_52 .= ", tbl_os.consumidor_nome, tbl_os.consumidor_cidade,tbl_os.marca, tbl_os.consumidor_estado, tbl_numero_serie.ordem, tbl_numero_serie.ordem, tbl_hd_chamado.protocolo_cliente";

		if ($cidade)
		{
			$cond_cidade = " AND TO_ASCII(tbl_os.consumidor_cidade,'LATIN1') = TO_ASCII('".mb_strtoupper($cidade)."','LATIN1')";
		}
		if ($marca_logo>0)
		{
			$cond_marca_logo = " AND tbl_os.marca = $marca_logo";
		}
	}

	if (in_array($login_fabrica, array(96)))
	{
		$finalizada = "tbl_os.data_fechamento";
	}
	else
	{
		$finalizada = $data_referencia;
	}

	if ($login_fabrica == 30) {
		$diferenca = "  ((select count(1) from fn_calendario(tbl_os.data_abertura,tbl_os.$data_referencia) where nome_dia not in ('Domingo') and data <> tbl_os.data_abertura)) ";
	} else {
		$diferenca = " DISTINCT tbl_os.$data_referencia::date - tbl_os.data_abertura ";
	}

	if($login_fabrica == 163){
		$join_163 = " JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica} ";
		$cond_163 = " AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE ";
	}

	$sql = "SELECT	DISTINCT
					tbl_os.os                                                                ,
					tbl_os.sua_os                                                           ,
					tbl_os.serie                                                           ,
					TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY')        AS data_fechamento  ,
					TO_CHAR(tbl_os.data_conserto,'DD/MM/YYYY')        AS data_conserto  ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')          AS data_abertura    ,
					tbl_produto.referencia_fabrica                                                  ,
					tbl_produto.referencia                                                  ,
					tbl_produto.descricao                                                   ,
					SUM($diferenca) AS data_diferenca
					$select_52

			FROM 	tbl_os
			JOIN    tbl_produto    ON tbl_os.produto = tbl_produto.produto
			$join_163
			$from_52

			WHERE tbl_os.fabrica = $login_fabrica
			AND	 	tbl_os.posto   = $posto
			AND		((tbl_os.$finalizada   BETWEEN '$data_inicial' AND '$data_final' AND tbl_os.$data_referencia::date NOTNULL)" . ($login_fabrica != 52 ? " OR (tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final' AND tbl_os.$data_referencia::date IS NULL)" : "" ) . ")
			AND		tbl_os.excluida IS NOT TRUE
			$cond_familia
			$cond_produto
			$cond_marca_logo
			$cond_linha
			$cond_cliente_admin
			$cond_163
			";
			//HD 216470: Retirada a subquery e substitida por condições na cláusula WHERE

			$sql .="GROUP BY tbl_os.os,tbl_os.sua_os, tbl_os.serie , tbl_os.data_fechamento , tbl_os.data_conserto, tbl_os.data_abertura , tbl_produto.referencia , tbl_produto.descricao,tbl_produto.referencia_fabrica $groupby_52" ;
			$sql .= " ORDER BY data_diferenca";
#exit(nl2br($sql));
			//echo nl2br($sql);
			$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
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

		echo "
		<TABLE width='700' border='1' align='center' cellspacing='2' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>
		<tr  class='Principal'>
		<td>Posto</td>
		<td>$posto_nome ($posto_cidade - $posto_estado)</td>
		</tr>
		<tr class=Conteudo>
		<td>Período (data finalizada)</td>
		<td>$data_inicial_press até $data_final_press</td>
		</tr>
		<tr class=Conteudo>
		<td>Referência</td>
		<td>" . ucwords(str_replace("_", " ", $data_referencia)) . "</td>
		</tr>
		";

		if ($estado) {
			echo "
		<tr class=Conteudo>
		<td>Estado</td>
		<td>$estado</td>
		</tr>
			";
		}
		else {
			echo "
		<tr class=Conteudo>
		<td>Estado</td>
		<td>TODOS OS ESTADOS</td>
		</tr>
			";
		}

		if ($pais) {
			echo "
		<tr class=Conteudo>
		<td>País</td>
		<td>$pais</td>
		</tr>
			";
		}

		if ($linha) {
			$sql_linha = "SELECT nome FROM tbl_linha WHERE linha=$linha";
			$res_linha = pg_query($con, $sql_linha);
			$linha_nome = pg_fetch_result($res_linha, 0, nome);
			echo "
		<tr class=Conteudo>
		<td>Linha</td>
		<td>$linha_nome</td>
		</tr>
			";
		}

		if ($familia) {
			$sql_familia = "SELECT descricao FROM tbl_familia WHERE familia=$familia";
			$res_familia = pg_query($con, $sql_familia);
			$familia_descricao = pg_fetch_result($res_familia, 0, descricao);
			echo "
		<tr class=Conteudo>
		<td>Família</td>
		<td>$familia_descricao</td>
		</tr>
			";
		}

		echo "
		</TABLE>
		<br>
		";

		echo "
			<TABLE width='700' border='1' align='center' cellspacing='3' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>
			<tr  class='Principal'>
			<td>OS</td>";

		if ($login_fabrica <> 52)
		{
			echo "<td>ABERTO PELO</td>";
		}

			if ($login_fabrica == 52) {
				echo "
			<td>Número de Série</td>
			<td>Número do Ativo</td>
			<td>Nº Controle Cliente</td>
			<td>Cliente ADM</td>
				";
			}
			if ($login_fabrica == 171) {
			echo "<TD>Referência Fábrica</td>";
			}
			echo "
			<td colspan='2'>PRODUTO</td>";
			if ($login_fabrica == 52) {
				echo "
			<td>Marca</td>
				";
			}

			echo "
			<td>Abertura</td>
			<td>Conserto</td>
			<td>Fechamento</td>
			<td>Qtde Dias</td>";

			if ($login_fabrica == 52) {
				echo "<td>Consumidor</td>";
			}

			if($login_fabrica == 52){
				echo "<td>Cidade</td>
				  <td>Estado</td>";
			}

			echo "</tr>";


		$total_os = 0;
		$total_dias = 0;

		for ($i = 0 ; $i <  pg_num_rows ($res) ; $i++) {
			$os                 = pg_fetch_result($res,$i,'os');
			$sua_os             = pg_fetch_result($res,$i,'sua_os');
			$serie              = pg_fetch_result($res,$i,'serie');
			$total_diferenca    = pg_fetch_result($res,$i,'data_diferenca');
			$referencia         = pg_fetch_result($res,$i,'referencia');
			$referencia_fabrica         = pg_fetch_result($res,$i,'referencia_fabrica');
			$descricao          = pg_fetch_result($res,$i,'descricao');
			$data_conserto      = pg_fetch_result($res,$i,'data_conserto');
			$data_fechamento    = pg_fetch_result($res,$i,'data_fechamento');
			$data_abertura      = pg_fetch_result($res,$i,'data_abertura');

			if ($login_fabrica == 52) {
				$cliente_admin_nome 	= pg_fetch_result($res,$i,'cliente_admin_nome');
				$consumidor_nome    	= pg_fetch_result($res,$i,'consumidor_nome');
				$consumidor_cidade  	= pg_fetch_result($res,$i,'consumidor_cidade');
				$consumidor_marca_logo 	= pg_fetch_result($res,$i,'marca');
				$consumidor_estado  	= pg_fetch_result($res,$i,'consumidor_estado');
				$ordem              	= pg_fetch_result($res,$i,'ordem');
				$protocolo_cliente  	= pg_fetch_result($res,$i,'protocolo_cliente');
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
			echo "<TD nowrap><a href='os_press.php?os=$os'target='_blank'>$sua_os</a></TD>\n";
			if ($login_fabrica <> 52)
			{
				echo "<TD nowrap>";
				if (strlen($admin)==0) echo "POSTO";
				else					echo "SAC";
				echo "</td>";
			}
			if ($login_fabrica == 52) {

				$numero_serie = null;
				$numero_ativo = null;
				$sql_52 = " SELECT tbl_os.serie, tbl_numero_serie.ordem
							FROM tbl_os
							JOIN 	tbl_produto
								ON tbl_os.produto = tbl_produto.produto
							JOIN 	tbl_numero_serie
								ON tbl_numero_serie.produto = tbl_produto.produto
								AND tbl_numero_serie.fabrica = $login_fabrica
														WHERE
								tbl_os.fabrica = $login_fabrica
								AND tbl_numero_serie.serie = '$serie'
								AND tbl_os.os = $os";

				$res_52 = pg_query($con,$sql_52);
				if (pg_num_rows($res_52)>0){

					$numero_serie = pg_result($res_52,0,'serie');
					$numero_ativo = pg_result($res_52,0,'ordem');

				}else{
					$numero_serie = "&nbsp;";
					$numero_ativo = "&nbsp;";
				}
				echo "
				<td>$numero_serie</td>
				<td>$numero_ativo</td>
				<td>$protocolo_cliente</td>
				<td nowrap>$cliente_admin_nome</td>				";
			}
			if ($login_fabrica == 171) {
			echo "<TD nowrap>$referencia_fabrica</td>";
			}

			echo "<TD nowrap>$referencia</td>";
			echo "<TD nowrap>$descricao</td>";
			if ($login_fabrica == 52) {

				if ($consumidor_marca_logo > 0 ) {
					$sqlx="select nome from  tbl_marca where marca = $consumidor_marca_logo;";
					$resx=pg_exec($con,$sqlx);
					$marca_logo_nome         = pg_fetch_result($resx, 0, 'nome');
				}else{
					$marca_logo_nome = '';
				}
				echo "
				<td nowrap>$marca_logo_nome</td>				";
			}
			echo "<TD nowrap>$data_abertura</td>";
			echo "<TD nowrap>$data_conserto</td>";
			echo "<TD nowrap>$data_fechamento</td>";
			echo "<TD nowrap>$total_diferenca</td>";

			if ($login_fabrica == 52) {
				echo "
				<td nowrap>$consumidor_nome</td>
				<td nowrap>$consumidor_cidade</td>
				<td nowrap>$consumidor_estado</td>
				";
			}

			echo "</tr>";
		}
	}

	if ($login_fabrica == 52)
	{
		$colspan = 12;
	}elseif ($login_fabrica == 171) {
		$colspan = 7;

	}
	else
	{
		$colspan = 6;
	}

	echo "<tr class=Principal>
		<td>
		$total_os
		</td>
		<td colspan=$colspan>
		</td>
		<td>
		$total_dias
		</td>";

	echo "</tr>";

	if ($login_fabrica == 52) {
		$colspan = 14;
	}elseif ($login_fabrica == 171) {
		$colspan = 12;

	}
	else {
		$colspan = 8;
	}

	if ($total_os) {
		$total = number_format($total_dias / $total_os, 2, ",", "");
	}
	else {
		$total = "0.00";
	}

	echo "
	<tr class=Principal>
		<td colspan=$colspan>
		MÉDIA DE DIAS POR OS NO PERÍODO CONSULTADO: " . $total . "
		</td>
	</tr>
	</table>";
}

if ($excel) {
	$conteudo_excel = ob_get_clean();
	$arquivo = fopen("xls/relatorio_tempo_conserto_os_$login_fabrica$login_admin.xls", "w+");
	fwrite($arquivo, $conteudo_excel);
	fclose($arquivo);
	header("location:xls/relatorio_tempo_conserto_os_$login_fabrica$login_admin.xls");
}
else {
	echo "<br><br>";
	echo "<a href='" . $PHP_SELF . "?" . $_SERVER["QUERY_STRING"] . "&excel=1' style='font-size: 10pt;'><img src='imagens/excell.gif'> Clique aqui para download do relatório em Excel</a>";
	echo "<br><br>";
	include "rodape.php";
}

?>
