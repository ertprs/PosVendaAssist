<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

include "funcoes.php";

$admin_privilegios = "gerencia,call_center";
include "autentica_admin.php";


$msg = "";

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (trim($_POST["acao"]) == "IMPRESSAO") {
	$qtde_os = trim($_POST["qtde_os"]);
	$os_enviadas = array();
	
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	for ($i = 0 ; $i < $qtde_os; $i++) {
		$os_etiqueta = trim($_POST["os_etiqueta_" . $i]);
		if (strlen($os_etiqueta) > 0) {
			$sql =	"INSERT INTO tbl_etiqueta_os (
						os   ,
						data
					) VALUES (
						$os_etiqueta      ,
						current_timestamp
					);";
			$res = pg_exec($con,$sql);
			$msg = pg_errormessage($con);
			
			if (strlen($msg) > 0) break;
			
			$sql =	"SELECT tbl_os.sua_os                  ,
							tbl_posto_fabrica.codigo_posto
					FROM tbl_os
					JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_os.posto
											AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE tbl_os.fabrica = $login_fabrica
					AND   tbl_os.os      = $os_etiqueta;";
			$res = pg_exec($con,$sql);
			$msg = pg_errormessage($con);
			
			if (strlen($msg) > 0) break;
			
			if (pg_numrows($res) == 1) {
				$os_enviadas[] = trim(pg_result($res,0,codigo_posto)) . trim(pg_result($res,0,sua_os));
			}
		}
	}
	
	if (strlen($msg) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if (strlen($_POST['btn_acao']) > 0 ) {
	$sua_os    = trim(strtoupper($_POST['sua_os']));
	$serie     = trim(strtoupper($_POST['serie']));
	$nf_compra = trim(strtoupper($_POST['nf_compra']));
	$consumidor_cpf = trim (strtoupper ($_POST['consumidor_cpf']));

	$mes = trim (strtoupper ($_POST['mes']));
	$ano = trim (strtoupper ($_POST['ano']));

	$codigo_posto       = trim(strtoupper($_POST['codigo_posto']));
	$posto_nome         = trim(strtoupper($_POST['posto_nome']));
	$consumidor_nome    = trim(strtoupper($_POST['consumidor_nome']));
	$produto_referencia = trim(strtoupper($_POST['produto_referencia']));
	$admin              = trim($_POST['admin']);
	$os_aberta          = trim(strtoupper($_POST['os_aberta']));
	$os_situacao        = trim(strtoupper($_POST['os_situacao']));
	$revenda_cnpj       = trim(strtoupper($_POST['revenda_cnpj']));

	$consumidor_cpf = str_replace (".","",$consumidor_cpf);
	$consumidor_cpf = str_replace (" ","",$consumidor_cpf);
	$consumidor_cpf = str_replace ("-","",$consumidor_cpf);
	$consumidor_cpf = str_replace ("/","",$consumidor_cpf);
	if (strlen ($consumidor_cpf) <> 11 AND strlen ($consumidor_cpf) <> 14 AND strlen ($consumidor_cpf) <> 0) {
		$msg = "Tamanho do CPF do consumidor inválido";
	}

	$revenda_cnpj = str_replace (".","",$revenda_cnpj);
	$revenda_cnpj = str_replace (" ","",$revenda_cnpj);
	$revenda_cnpj = str_replace ("-","",$revenda_cnpj);
	$revenda_cnpj = str_replace ("/","",$revenda_cnpj);
	if (strlen ($revenda_cnpj) <> 8 AND strlen ($revenda_cnpj) > 0) {
		$msg = "Digite os 8 primeiros dígitos do CNPJ";
	}

	if (strlen ($nf_compra) > 0 ) {
		$nf_compra = "000000" . $nf_compra;
		$nf_compra = substr ($nf_compra,strlen ($nf_compra)-6);
	}

	if ( (strlen ($codigo_posto) > 0 OR strlen ($posto_nome) > 0 OR strlen ($consumidor_nome) > 0 OR strlen ($produto_referencia) > 0 ) AND ( strlen ($mes) == 0 OR strlen ($ano) == 0) )  {
		$msg = "Digite o mês e o ano para fazer a pesquisa";
	}

	if ( (strlen ($codigo_posto) == 0 AND strlen ($posto_nome) == 0 AND strlen ($consumidor_nome) == 0 AND strlen ($produto_referencia) == 0 AND strlen ($admin) == 0 ) AND ( strlen ($mes) > 0 OR strlen ($ano) > 0) )  {
		$msg = "Especifique mais um campo para a pesquisa";
	}

	if ( strlen ($posto_nome) > 0 AND strlen ($posto_nome) < 5 ) {
		$msg = "Digite no mínimo 5 letras para o nome do posto";
	}

	if ( strlen ($consumidor_nome) > 0 AND strlen ($consumidor_nome) < 5) {
		$msg = "Digite no mínimo 5 letras para o nome do consumidor";
	}

	if ( strlen ($serie) > 0 AND strlen ($serie) < 5) {
		$msg = "Digite no mínimo 5 letras para o número de série";
	}


	if (strlen($mes) > 0) {
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
	}

	if (strlen($msg) == 0 && strlen($opcao2) > 0) {
		if (strlen(trim($_POST["posto_codigo"])) > 0) $posto_codigo = trim($_POST["posto_codigo"]);
		if (strlen(trim($_GET["posto_codigo"])) > 0)  $posto_codigo = trim($_GET["posto_codigo"]);
		if (strlen(trim($_POST["posto_nome"])) > 0) $posto_nome = trim($_POST["posto_nome"]);
		if (strlen(trim($_GET["posto_nome"])) > 0)  $posto_nome = trim($_GET["posto_nome"]);
		if (strlen(trim($_GET["produto_referencia"])) > 0)  $produto_referencia = trim($_GET["produto_referencia"]);
		
		if (strlen($posto_codigo) > 0 && strlen($posto_nome) > 0) {
			$sql =	"SELECT tbl_posto.posto                ,
							tbl_posto.nome                 ,
							tbl_posto_fabrica.codigo_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica USING (posto)
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica
					AND   tbl_posto_fabrica.codigo_posto = '$posto_codigo';";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) == 1) {
				$posto        = trim(pg_result($res,0,posto));
				$posto_codigo = trim(pg_result($res,0,codigo_posto));
				$posto_nome   = trim(pg_result($res,0,nome));
			}else{
				$erro .= " Posto não encontrado. ";
			}
		}
	}
}

$layout_menu = "call_center";
$title = "GERAR ETIQUETAS - Relação de Ordens de Serviços Lançadas ";
include "cabecalho.php";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>

<? include "javascript_pesquisas.php"; ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/assist.js"></script>

<script language="JavaScript">
function FuncGerarEtiqueta (qtde_os) {
	var a;
	var os_selecionada = "";
	
	document.FormEtiqueta.BotaoEnviar.disabled = true;
	
	for (a = 0; a < qtde_os; a++) {
		if (eval("document.FormEtiqueta.os_etiqueta_" + a + ".checked") == true) {
			os_selecionada += "S";
		}
	}
	
	if (os_selecionada.length == 0) {
		alert("Favor selecionar a OS que deseja enviar p/ impressão!");
		document.FormEtiqueta.BotaoEnviar.disabled = false;
	}else{
		document.FormEtiqueta.acao.value = "IMPRESSAO";
		document.FormEtiqueta.submit();
	}
}

function FuncMouseOver (linha, cor) {
	linha.style.cursor = "hand";
	linha.style.backgroundColor = cor;
}

function FuncMouseOut (linha, cor) {
	linha.style.cursor = "default";
	linha.style.backgroundColor = cor;
}

</script>

<br>

<?
$sql = "SELECT COUNT (etiqueta_os) AS total_os FROM tbl_etiqueta_os WHERE impressao IS NULL";
$res = pg_exec($con,$sql);
if (count($os_enviadas) > 0 || trim(pg_result($res,0,total_os)) > 0) {
	echo "<table width='500' border='0' cellpadding='2' cellspacing='0' align='center'>";
	echo "<tr>";
	echo "<td class='error'>";
	echo "OS enviada p/ geração de etiqueta:<br>";
	if (count($os_enviadas) > 0) {
		while (list($chave, $valor) = each($os_enviadas)) {
			echo $valor . "<br>";
		}
	}
	echo "Quantidade de OS enviada p/ geração de etiqueta: " . count($os_enviadas) ."<br>";
	echo "<br>";
	echo "Quantidade de OS acumulada p/ impressão de etiqueta: " . trim(pg_result($res,0,total_os));
	echo "<br><br>";
	echo "<button type='button' name='botao' title='Clique aqui para visualizar as OS' onclick=\"javascript: window.open('etiqueta_print.php','');\">Imprimir Etiqueta</button>";
	echo "<br><br>";
 	echo "<font size='1'>OBS.: A página deve ser configuraca com os seguintes dados:<br>";
	echo "- Letter 8,5 x 11 pol. (Carta)<br>";
	echo "- Retirar cabeçalho e rodapé<br>";
	echo "- Direita  :  6 mm<br>";
	echo "- Esquerda :  6 mm<br>";
	echo "- Cabeçalho: 12 mm<br>";
	echo "- Rodapé   : 12 mm</font>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}

if (strlen($msg) > 0) echo "<h1>$msg</h1>";

if (strlen($_POST['btn_acao']) > 0 AND strlen($msg) == 0) {
	// OS não excluída
	$sql =  "SELECT tbl_os.os                                                         ,
					tbl_os.sua_os                                                     ,
					LPAD(tbl_os.sua_os,20,'0')                   AS ordem             ,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')  AS digitacao         ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
					TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento        ,
					TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY')      AS finalizada        ,
					tbl_os.serie                                                      ,
					tbl_os.excluida                                                   ,
					tbl_os.motivo_atraso                                              ,
					tbl_os.tipo_os_cortesia                                           ,
					tbl_os.consumidor_revenda                                         ,
					tbl_os.consumidor_nome                                            ,
					tbl_os.revenda_nome                                               ,
					tbl_posto_fabrica.codigo_posto                                    ,
					tbl_posto.nome                              AS posto_nome         ,
					tbl_os_extra.impressa                                             ,
					tbl_os_extra.extrato                                              ,
					tbl_os_extra.os_reincidente                                       ,
					tbl_produto.referencia                      AS produto_referencia ,
					tbl_produto.descricao                       AS produto_descricao  ,
					tbl_produto.voltagem                        AS produto_voltagem   ,
					distrib.codigo_posto                        AS codigo_distrib
			FROM      tbl_os
			JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_os.posto
			JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN      tbl_produto       ON  tbl_produto.produto       = tbl_os.produto
			JOIN      tbl_os_extra      ON  tbl_os_extra.os           = tbl_os.os";
			
	if (strlen($os_situacao) > 0) {
		$sql .= " JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato";
		if ($os_situacao == "PAGA")
			$sql .= " JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato";
	}
	
	$sql .=	" LEFT JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_produto.linha AND tbl_posto_linha.posto = tbl_os.posto
			LEFT JOIN tbl_posto_fabrica distrib ON tbl_os.digitacao_distribuidor = distrib.posto AND distrib.fabrica = $login_fabrica
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.excluida IS NOT TRUE";

	if (strlen($mes) > 0) {
		$sql .= " AND tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'";
	}
	
	if (strlen($posto_nome) > 0) {
		$sql .= " AND tbl_posto.nome ILIKE '%$posto_nome%' ";
	}

	if (strlen($codigo_posto) > 0) {
		$sql .= " AND (tbl_posto_fabrica.codigo_posto ilike '$codigo_posto' OR distrib.codigo_posto = '$codigo_posto')";
	}

	if (strlen($produto_referencia) > 0) {
		$sql .= " AND tbl_produto.referencia = '$produto_referencia' ";
	}
	
	if (strlen($admin) > 0) {
		$sql .= " AND tbl_os.admin = '$admin' ";
	}

	if (strlen($sua_os) > 0) {
		if ($login_fabrica == 1) {
			$pos = strpos($sua_os, "-");
			if ($pos === false) {
				$pos = strlen($sua_os) - 5;
			}else{
				$pos = $pos - 5;
			}
			$sua_os = substr($sua_os, $pos,strlen($sua_os));
		}
#			$sql .= " AND tbl_os.sua_os ILIKE '%$sua_os%'";
		$sql .= " AND (tbl_os.sua_os ILIKE '$sua_os%' OR tbl_os.sua_os ILIKE '0$sua_os%' OR tbl_os.sua_os ILIKE '00$sua_os%') ";
	}

	if (strlen($serie) > 0) {
		$sql .= " AND tbl_os.serie = '$serie'";
	}
	
	if (strlen($nf_compra) > 0) {
		$sql .= " AND tbl_os.nota_fiscal = '$nf_compra'";
	}

	if (strlen($consumidor_nome) > 0) {
		$sql .= " AND tbl_os.consumidor_nome ILIKE '%$consumidor_nome%'";
	}

	if (strlen($consumidor_cpf) > 0) {
		$sql .= " AND tbl_os.consumidor_cpf ILIKE '%$consumidor_cpf%'";
	}

	if (strlen($os_aberta) > 0) {
		$sql .= " AND tbl_os.data_fechamento IS NULL ";
	}
	
	if ($os_situacao == "APROVADA") {
		$sql .= " AND tbl_extrato.aprovado IS NOT NULL ";
	}
	if ($os_situacao == "PAGA") {
		$sql .= " AND tbl_extrato_financeiro.data_envio IS NOT NULL ";
	}

	if (strlen($revenda_cnpj) > 0) {
		$sql .= " AND (tbl_os.data_fechamento IS NULL AND tbl_os.consumidor_revenda = 'R' AND tbl_os.revenda_cnpj ILIKE '$revenda_cnpj%') ";
	}

	$sql .= " ORDER BY LPAD(tbl_os.sua_os,20,'0') DESC";
	$res = pg_exec($con,$sql);

//	if (getenv("REMOTE_ADDR") == "201.0.9.216") { echo nl2br($sql) . "<br>" . pg_numrows($res); exit;}

/*	##### PAGINAÇÃO - INÍCIO #####
	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";

	require "_class_paginacao.php";

	// definicoes de variaveis
	$max_links = 11;				// máximo de links à serem exibidos
	$max_res   = 30;				// máximo de resultados à serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

	##### PAGINAÇÃO - FIM #####*/
	
	$resultados = pg_numrows($res);

	if (pg_numrows($res) > 0) {
		$qtde_os = pg_numrows($res);
		
		##### LEGENDAS - INÍCIO #####
		echo "<div align='left' style='position: relative; left: 25'>";
		echo "<table border='0' cellspacing='0' cellpadding='0'>";
		if ($excluida == "t") {
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FFE1E1'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; Excluídas do sistema</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
		}
		if ($login_fabrica != 1) {
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#D7FFE1'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; Reincidências</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
		}else{
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FFCC66'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; OSs sem lancamento de itens há mais de 5 dias, efetue o lançamento</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FF0000'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; OSs que excederam o prazo limite de 30 dias para fechamento, informar \"Motivo\"</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
		}
		if ($login_fabrica == 14) {
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#91C8FF'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; OSs abertas há mais de 3 dias sem data de fechamento</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FF0000'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; OSs abertas há mais de 5 dias sem data de fechamento</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
		}else{
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#91C8FF'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; OSs abertas há mais de 25 dias sem data de fechamento</b></font></td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "</div>";
		##### LEGENDAS - FIM #####

		echo "<br>";

		echo "<form name='FormEtiqueta' method='post' action='$PHP_SELF'>";
		
		echo "<input type='hidden' name='acao'>";
		
		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			if ($i == 0) {
				echo "<tr class='Titulo' height='15'>";
				echo "<td width='20'></td>";
				echo "<td>OS</td>";
				echo "<td>SÉRIE</td>";
				echo "<td>AB</td>";
				echo "<td>FC</td>";
				echo "<td>POSTO</td>";
				echo "<td>CONSUMIDOR</td>";
				echo "<td>PRODUTO</td>";
				echo "<td>&nbsp;</td>";
				echo "</tr>";
			}

			$os                 = trim(pg_result($res,$i,os));
			$sua_os             = trim(pg_result($res,$i,sua_os));
			$digitacao          = trim(pg_result($res,$i,digitacao));
			$abertura           = trim(pg_result($res,$i,abertura));
			$fechamento         = trim(pg_result($res,$i,fechamento));
			$finalizada         = trim(pg_result($res,$i,finalizada));
			$serie              = trim(pg_result($res,$i,serie));
			$excluida           = trim(pg_result($res,$i,excluida));
			$motivo_atraso      = trim(pg_result($res,$i,motivo_atraso));
			$tipo_os_cortesia   = trim(pg_result($res,$i,tipo_os_cortesia));
			$consumidor_revenda = trim(pg_result($res,$i,consumidor_revenda));
			$consumidor_nome    = trim(pg_result($res,$i,consumidor_nome));
			$revenda_nome       = trim(pg_result($res,$i,revenda_nome));
			$codigo_posto       = trim(pg_result($res,$i,codigo_posto));
			$posto_nome         = trim(pg_result($res,$i,posto_nome));
			$impressa           = trim(pg_result($res,$i,impressa));
			$extrato            = trim(pg_result($res,$i,extrato));
			$os_reincidente     = trim(pg_result($res,$i,os_reincidente));
			$produto_referencia = trim(pg_result($res,$i,produto_referencia));
			$produto_descricao  = trim(pg_result($res,$i,produto_descricao));
			$produto_voltagem   = trim(pg_result($res,$i,produto_voltagem));

			if ($i % 2 == 0) {
				$cor   = "#F1F4FA";
				$botao = "azul";
			}else{
				$cor   = "#F7F5F0";
				$botao = "amarelo";
			}

			##### VERIFICAÇÕES PARA OS CRITÉRIOS DA LEGENDA - INÍCIO #####
			if ($excluida == "t")            $cor = "#FFE1E1";
			if (strlen($os_reincidente) > 0) $cor = "#D7FFE1";

			// OSs abertas há mais de 25 dias sem data de fechamento
			if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica != 14) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '25 days','YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_atual = pg_result ($resX,0,0);

				if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#91C8FF";
			}

			// CONDIÇÕES PARA INTELBRÁS - INÍCIO
			if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 14) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '3 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_atual = pg_result($resX,0,0);

				if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#91C8FF";

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_atual = pg_result ($resX,0,0);

				if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#FF0000";
			}
			// CONDIÇÕES PARA INTELBRÁS - FIM

			// CONDIÇÕES PARA BLACK & DECKER - INÍCIO
			// Verifica se não possui itens com 5 dias de lançamento
			if ($login_fabrica == 1) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR(current_date + INTERVAL '5 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$data_hj_mais_5 = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sql = "SELECT COUNT(tbl_os_item.*) AS total_item
						FROM tbl_os_item
						JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
						JOIN tbl_os         ON tbl_os.os                 = tbl_os_produto.os
						WHERE tbl_os.os = $os
						AND   tbl_os.data_abertura::date >= '$aux_consulta'";
				$resItem = pg_exec($con,$sql);

				$itens = pg_result($resItem,0,total_item);

				if ($itens == 0 && $aux_consulta > $data_hj_mais_5) $cor = "#FFCC66";

				$mostra_motivo = 2;
			}

			// Verifica se está sem fechamento há 20 dias ou mais da data de abertura
			if (strlen($fechamento) == 0 && $mostra_motivo == 2 && $login_fabrica == 1) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '20 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_atual = pg_result($resX,0,0);

				if ($consumidor_revenda != "R") {
					if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) {
						$mostra_motivo = 1;
						$cor = "#91C8FF";
					}
				}
			}

			// Se estiver acima dos 30 dias, não exibirá os botões
			if (strlen($fechamento) == 0 && $login_fabrica == 1) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '30 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_atual = pg_result($resX,0,0);

				if ($consumidor_revenda != "R"){
					if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) {
						$mostra_motivo = 1;
						$cor = "#FF0000";
					}
				}
			}
			// CONDIÇÕES PARA BLACK & DECKER - FIM

			##### VERIFICAÇÕES PARA OS CRITÉRIOS DA LEGENDA - FIM #####

			if (strlen($sua_os) == 0) $sua_os = $os;
			if ($login_fabrica == 1) $sua_os = $codigo_posto.$sua_os;

			echo "<tr class='Conteudo' height='15' bgcolor='$cor' onmouseover=\"javascript: FuncMouseOver (this, '#FFCC99');\" onmouseout=\"javascript: FuncMouseOut (this, '$cor');\">";
			echo "<td nowrap><input type='checkbox' name='os_etiqueta_$i' value='$os'></td>";
			echo "<td nowrap>" . $sua_os . "</td>";
			echo "<td nowrap>" . $serie . "</td>";
			echo "<td nowrap ><acronym title='Data Abertura: $abertura' style='cursor: help;'>" . substr($abertura,0,5) . "</acronym></td>";
			if ($login_fabrica == 1) $aux_fechamento = $finalizada;
			else                     $aux_fechamento = $fechamento;
			echo "<td nowrap><acronym title='Data Fechamento: $aux_fechamento' style='cursor: help;'>" . substr($aux_fechamento,0,5) . "</acronym></td>";
			echo "<td nowrap><acronym title='Posto: $codigo_posto - $posto_nome' style='cursor: help;'>" . substr($posto_nome,0,15) . "</acronym></td>";
			echo "<td nowrap><acronym title='Consumidor: $consumidor_nome' style='cursor: help;'>" . substr($consumidor_nome,0,15) . "</acronym></td>";
			$produto = $produto_referencia . " - " . $produto_descricao;
			echo "<td nowrap><acronym title='Referência: $produto_referencia \nDescrição: $produto_descricao \nVoltagem: $produto_voltagem' style='cursor: help;'>" . substr($produto,0,20) . "</acronym></td>";
			echo "<td width='60' align='center'>";
			echo "<a href='os_press.php?os=$os' target='_blank'><img border='0' src='imagens/btn_consultar_$botao.gif'></a>";
			echo "</td>\n";

			echo "</tr>";
		}
		echo "</table>";
		echo "<br>";
		echo "<input type='hidden' name='qtde_os' value='$qtde_os'>";
		echo "<center>";
		echo "<button type='button' name='BotaoEnviar' title='Enviar OS para Impressão de Etiqueta' onclick=\"javascript: FuncGerarEtiqueta($qtde_os);\">Enviar OS</button>";
		echo "</center>";
		
		echo "</form>";
	}

/*	##### PAGINAÇÃO - INÍCIO #####
	echo "<br>";
	echo "<div>";

	if($pagina < $max_links) $paginacao = pagina + 1;
	else                     $paginacao = pagina;

	// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
	$todos_links		= $mult_pag->Construir_Links("strings", "sim");

	// função que limita a quantidade de links no rodape
	$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

	for ($n = 0; $n < count($links_limitados); $n++) {
		echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
	}

	echo "</div>";

	$resultado_inicial = ($pagina * $max_res) + 1;
	$resultado_final   = $max_res + ( $pagina * $max_res);
	$registros         = $mult_pag->Retorna_Resultado();

	$valor_pagina   = $pagina + 1;
	$numero_paginas = intval(($registros / $max_res) + 1);

	if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

	if ($registros > 0){
		echo "<br>";
		echo "<div>";
		echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
		echo "<font color='#cccccc' size='1'>";
		echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
		echo "</font>";
		echo "</div>";
	}
	##### PAGINAÇÃO - FIM #####*/
	
	echo "<br><h1>Resultado: $resultados registro(s).</h1>";
}

$sua_os             = trim (strtoupper ($_POST['sua_os']));
$serie              = trim (strtoupper ($_POST['serie']));
$nf_compra          = trim (strtoupper ($_POST['nf_compra']));
$consumidor_cpf     = trim (strtoupper ($_POST['consumidor_cpf']));
$produto_referencia = trim (strtoupper ($_POST['produto_referencia']));
$produto_descricao  = trim (strtoupper ($_POST['produto_descricao']));

$mes = trim (strtoupper ($_POST['mes']));
$ano = trim (strtoupper ($_POST['ano']));

$codigo_posto    = trim (strtoupper ($_POST['codigo_posto']));
$posto_nome      = trim (strtoupper ($_POST['posto_nome']));
$consumidor_nome = trim (strtoupper ($_POST['consumidor_nome']));
$os_situacao     = trim (strtoupper ($_POST['os_situacao']));

?>

<form name="frm_consulta" method="post" action="<? echo $PHP_SELF; ?>">

<input type="hidden" name="acao">

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Titulo" height="30">
		<td align="center">Selecione os parâmetros para a pesquisa.</td>
	</tr>
</table>

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>Número da OS</td>
		<td>Número de Série</td>
		<td>NF. Compra</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td><input type="text" name="sua_os"    size="10" value="<?echo $sua_os?>"    class="frm"></td>
		<td><input type="text" name="serie"     size="10" value="<?echo $serie?>"     class="frm"></td>
		<td><input type="text" name="nf_compra" size="10" value="<?echo $nf_compra?>" class="frm"></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>CPF Consumidor</td>
		<td></td>
		<td></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td><input type="text" name="consumidor_cpf" size="17" value="<?echo $consumidor_cpf?>" class="frm"></td>
		<td></td>
		<td></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan='3' align='center'><br><input type="submit" name="btn_acao" value="Pesquisar"></td>
	</tr>
</table>

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='2'> <hr> </td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td> * Mês</td>
		<td> * Ano</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
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

			&nbsp;&nbsp;&nbsp;Apenas OS em aberto <input type='checkbox' name='os_aberta' value='1' <? if (strlen ($os_aberta) > 0 ) echo " checked " ?> >

		</td>

	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>Posto</td>
		<td>Nome do Posto</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>
			<input type="text" name="codigo_posto" size="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'codigo');" <? } ?> value="<? echo $codigo_posto ?>" class="frm">
			<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'codigo')">
		</td>
		<td>
			<input type="text" name="posto_nome" size="30" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'nome');" <? } ?> value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'nome')">
		</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td></td>
		<td>Nome do Consumidor</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td></td>
		<td><input type="text" name="consumidor_nome" size="30" value="<?echo $consumidor_nome?>" class="frm"></td>
	</tr>




	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>Ref. Produto</td>
		<td>Descrição Produto</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>
		<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" > 
		&nbsp;
		<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia, document.frm_consulta.produto_descricao,'referencia')">
		</td>

		<td>
		<input class="frm" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>" >
		&nbsp;
		<img src='imagens/btn_lupa.gif'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia, document.frm_consulta.produto_descricao,'descricao')">
	</tr>
	
	
	
	<? if ($login_fabrica == 3) { ?>
	
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>&nbsp;</td>
		<td>Admin</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>
		&nbsp;
		</td>

		<td>
		<select name="admin" size="1" class="frm">
			<option value=''></option>
			<?
			$sql =	"SELECT admin, login
					FROM tbl_admin
					WHERE fabrica = $login_fabrica
					ORDER BY login;";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) > 0) {
				for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
					$x_admin = pg_result($res,$i,admin);
					$x_login = pg_result($res,$i,login);
					echo "<option value='$x_admin'";
					if ($admin == $x_admin) echo " selected";
					echo ">$x_login</option>";
				}
			}
			?>
			</select>
		</td>
	</tr>

	<? } ?>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td><input type="radio" name="os_situacao" value="APROVADA" <? if ($os_situacao == "APROVADA") echo "checked"; ?>> OS´s Aprovadas</td>
		<td><input type="radio" name="os_situacao" value="PAGA" <? if ($os_situacao == "PAGA") echo "checked"; ?>> OS´s Pagas</td>
	</tr>



	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='2'> <hr> </td>
	</tr>


	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='2'> OS em aberto da Revenda = CNPJ 
		<input class="frm" type="text" name="revenda_cnpj" size="8" value="<? echo $revenda_cnpj ?>" > /0001-00
		</td>
	</tr>
</table>
	
	
<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan='2' align='center'><br><input type="submit" name="btn_acao" value="Pesquisar"></td>
	</tr>
</table>

</table>

</form>

<? include "rodape.php" ?>
