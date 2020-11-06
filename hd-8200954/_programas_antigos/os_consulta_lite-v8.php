<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "funcoes.php";

if ($login_fabrica == 1) {
	#exit;
}

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET["acao"]) > 0) $acao = strtoupper($_GET["acao"]);

# ---- excluir ---- #
$os = $_GET['excluir'];

if (strlen ($os) > 0) {
	$sql = "SELECT fn_os_excluida($os,$login_fabrica,null);";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
}


# ---- fechar ---- #
$os = $_GET['fechar'];
if (strlen ($os) > 0) {
	include "ajax_cabecalho.php";

	$msg_erro = "";
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$sql = "UPDATE tbl_os SET data_fechamento = CURRENT_TIMESTAMP WHERE os = $os AND fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con) ;

	if (strlen ($msg_erro) == 0) {
		$sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con) ;
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		echo "ok;XX$os";
	}else{
		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		echo "erro;$sql ==== $msg_erro ";
	}
	flush();
	exit;
}


$msg = "";

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (strlen($_POST['btn_acao']) > 0 ) {

	$sua_os    = trim (strtoupper ($_POST['sua_os']));
	$serie     = trim (strtoupper ($_POST['serie']));
	$nf_compra = trim (strtoupper ($_POST['nf_compra']));
	$consumidor_cpf = trim (strtoupper ($_POST['consumidor_cpf']));

	$mes = trim (strtoupper ($_POST['mes']));
	$ano = trim (strtoupper ($_POST['ano']));

	$codigo_posto       = trim(strtoupper($_POST['codigo_posto']));
	$posto_nome         = trim(strtoupper($_POST['posto_nome']));
	$consumidor_nome    = trim(strtoupper($_POST['consumidor_nome']));
	$produto_referencia = trim(strtoupper($_POST['produto_referencia']));
	$os_aberta          = trim(strtoupper($_POST['os_aberta']));
	$revenda_cnpj       = trim(strtoupper($_POST['revenda_cnpj']));

	if ($login_e_distribuidor <> 't') $codigo_posto = $login_codigo_posto ;

	if (strlen ($consumidor_nome) > 0 AND strlen ($codigo_posto) == 0 ) {
		$msg = "Especifique o posto";
	}

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

/*
	if ( (strlen ($codigo_posto) > 0 OR strlen ($posto_nome) > 0 OR strlen ($consumidor_nome) > 0 OR strlen ($produto_referencia) > 0 ) AND ( strlen ($mes) == 0 OR strlen ($ano) == 0) )  {
		$msg = "Digite o mês e o ano para fazer a pesquisa";
	}
*/

	if ( strlen ($sua_os) == 0 AND strlen ($serie) == 0 AND strlen ($nf_compra) == 0 AND strlen ($consumidor_cpf) == 0 AND  strlen ($mes) == 0 AND strlen ($ano) == 0 )  {
		$msg = "Selecione o mês e o ano para fazer a pesquisa";
	}
/*
	if ( (strlen ($codigo_posto) == 0 AND strlen ($posto_nome) == 0 AND strlen ($consumidor_nome) == 0 AND strlen ($produto_referencia) == 0 AND strlen ($os_aberta) == 0 ) AND ( strlen ($mes) > 0 OR strlen ($ano) > 0) ) {
		$msg = "Especifique mais um campo para a pesquisa";
	}
*/
	if (strlen ($mes) == 0 AND strlen ($ano) > 0) {
		$msg = "Selecione o mês";
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

$layout_menu = "os";
$title = "Seleção de Parâmetros para Relação de Ordens de Serviços Lançadas";
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

<script language='javascript' src='ajax.js'></script>


<script language='javascript'>

function retornaFechamentoOS (http , sinal, excluir, lancar) {
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split(";");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					alert ('OS ' + results[0] + ' fechada com sucesso' );
					sinal.src='/assist/admin/imagens_admin/status_vermelho.gif';
					sinal.src='/assist/imagens/pixel.gif';
					excluir.src='/assist/imagens/pixel.gif';
					lancar.src='/assist/imagens/pixel.gif';
				}else{
					alert ('Erro no Fechamento da OS. \nPor favor, verifique os dados digitados, defeito constatado e solução, no lançamento de itens ' + results[1]);
				}
			}else{
				alert ('Fechamento nao processado');
			}
		}
	}
}

function fechaOS (os , sinal , excluir , lancar ) {
	url = "<?= $PHP_SELF ?>?fechar=" + escape(os) ;
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaFechamentoOS (http , sinal, excluir, lancar) ; } ;
	http.send(null);
}

</script>


<br>




<?
if (strlen($msg) > 0) {
	echo "<h1>$msg</h1>";
}

if (strlen($msg_erro) > 0) {
	echo "<font face='arial' size='+1' color='#FF6633'><b>$msg_erro</b></font>";
}


if (strlen($_POST['btn_acao']) > 0 AND strlen($msg) == 0) {

		if ($login_e_distribuidor <> 't') {
			$posto = $login_posto ;
		}

		$join_especifico = "";
		$especifica_mais_1 = "1=1";
		$especifica_mais_2 = "1=1";

		if (strlen ($data_inicial) > 0) {
			if (strlen ($produto_referencia) > 0) {
				$sqlX = "SELECT produto FROM tbl_produto JOIN tbl_linha USING (linha) WHERE tbl_linha.fabrica = $login_fabrica AND tbl_produto.referencia = '$produto_referencia'";
				$resX = pg_exec ($con,$sqlX);
				$produto = pg_result ($resX,0,0);
				$especifica_mais_1 = "tbl_os.produto = $produto";
			}

			if (strlen($os_aberta) > 0) {
				$especifica_mais_2 = "tbl_os.os_fechada IS FALSE";
			}

			$join_especifico = "JOIN (  SELECT os 
										FROM tbl_os
										JOIN tbl_os_extra USING (os)
										JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
										LEFT JOIN tbl_posto_linha ON tbl_produto.linha = tbl_posto_linha.linha AND tbl_posto_linha.posto = tbl_os.posto
										WHERE fabrica = $login_fabrica 
										AND   tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'
										AND   (tbl_os.posto   = $login_posto OR (tbl_posto_linha.distribuidor = $login_posto AND tbl_posto_linha.distribuidor IS NOT NULL AND $login_fabrica=3))
										AND   $especifica_mais_1
										AND   $especifica_mais_2
								) oss ON tbl_os.os = oss.os ";
		}

		// OS não excluída
		$sql =  "SELECT distinct tbl_os.os                                                         ,
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
						tbl_os.tipo_atendimento                                           ,
						tbl_os.tecnico_nome                                               ,
						tbl_os.admin                                                      ,
						tbl_tipo_atendimento.descricao                                    ,
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
				$join_especifico
				LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
				JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_os.posto
				JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN      tbl_produto       ON  tbl_produto.produto       = tbl_os.produto
				JOIN      tbl_os_extra      ON  tbl_os_extra.os           = tbl_os.os";
				
		if (strlen($os_situacao) > 0) {
			$sql .= " JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato";
			if ($os_situacao == "PAGA")
				$sql .= " JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato";
		}
//TULIO		
		$sql .=	"
				LEFT JOIN tbl_os_status             ON tbl_os_status.os              = tbl_os.os
				LEFT JOIN tbl_posto_linha           ON tbl_posto_linha.linha         = tbl_produto.linha AND tbl_posto_linha.posto = tbl_os.posto
				LEFT JOIN tbl_posto_fabrica distrib ON tbl_os.digitacao_distribuidor = distrib.posto AND distrib.fabrica = $login_fabrica
				WHERE tbl_os.fabrica = $login_fabrica
				AND   (tbl_os.posto  = $login_posto OR (tbl_posto_linha.distribuidor = $login_posto AND tbl_posto_linha.distribuidor IS NOT NULL ))
				AND   tbl_os.excluida IS NOT TRUE
				AND  (tbl_os_status.status_os NOT IN (13,15) OR tbl_os_status.status_os IS NULL)";


#				AND   (tbl_os.posto   = $login_posto OR tbl_os_extra.distribuidor = $login_posto)

		if (strlen($mes) > 0) {
			$sql .= " AND tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'";
		}
		
		if (strlen($posto_nome) > 0) {
			$posto_nome = strtoupper ($posto_nome);
			$sql .= " AND tbl_posto.nome LIKE '$posto_nome%' ";
		}
		if (strlen($codigo_posto) > 0) {
			$sql .= " AND (tbl_posto_fabrica.codigo_posto = '$codigo_posto' OR (distrib.codigo_posto = '$codigo_posto' AND distrib.codigo_posto IS NOT NULL ))";
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
			$sua_os = strtoupper ($sua_os);
#			$sql .= " AND tbl_os.sua_os LIKE '%$sua_os%'";
			$sql .= " AND (tbl_os.sua_os LIKE '$sua_os%' OR tbl_os.sua_os LIKE '0$sua_os%' OR tbl_os.sua_os LIKE '00$sua_os%') ";
		}

		if (strlen($serie) > 0) {
			$sql .= " AND tbl_os.serie = '$serie'";
		}
		
		if (strlen($nf_compra) > 0) {
			$sql .= " AND tbl_os.nota_fiscal = '$nf_compra'";
		}

		if (strlen($consumidor_nome) > 0) {
			$consumidor_nome = strtoupper ($consumidor_nome);
			$sql .= " AND tbl_os.consumidor_nome LIKE '$consumidor_nome%'";
		}

		if (strlen($consumidor_cpf) > 0) {
			$sql .= " AND tbl_os.consumidor_cpf = '$consumidor_cpf'";
		}

		if (strlen($os_aberta) > 0) {
			$sql .= " AND tbl_os.os_fechada IS FALSE ";
		}
		
		if (strlen($revenda_cnpj) > 0) {
			$sql .= " AND (tbl_os.data_fechamento IS NULL AND tbl_os.consumidor_revenda = 'R' AND tbl_os.revenda_cnpj LIKE '$revenda_cnpj%') ";
		}

		$sql .= " ORDER BY LPAD(tbl_os.sua_os,20,'0') DESC";

	$sqlT = str_replace ("\n"," ",$sql) ;
	$sqlT = str_replace ("\t"," ",$sqlT) ;
	
	$resT = @pg_exec ($con,"/* QUERY -> $sqlT  */");


#if ($login_fabrica == 1) echo $sql ;

if ($login_admin == 19) { echo $sql ; exit ; }
flush();
//echo "<!-- $sql -->";
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
		echo "</table>";
		echo "</div>";
		##### LEGENDAS - FIM #####

		echo "<br>";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			if ($i % 50 == 0) {
				echo "</table>";
				flush();
				echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' width='98%'>";
			}

			if ($i % 50 == 0) {
				echo "<tr class='Titulo' height='15'>";
				echo "<td width='100'>OS</td>";
				echo "<td width='150'>SÉRIE</td>";
				echo "<td>AB</td>";
				echo "<td><acronym title='Data de fechamento registrada pelo sistema' style='cursor:help;'>FC</a></td>";
				echo "<td>CONSUMIDOR</td>";
				echo "<td>PRODUTO</td>";
				if($login_fabrica==19){
					echo "<td>ATENDIMENTO</td>";
					echo "<td nowrap>TÉCNICO</td>";
					}
				echo "<td><img border='0' src='imagens/img_impressora.gif' alt='Imprimir OS'></td>";
				if ($login_fabrica == 1) {
					echo "<td>Item</td>";
					$colspan = "7";
				}else{
					$colspan = "5";
				}
				echo "<td colspan='$colspan'>AÇÕES</td>";
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
			$tipo_atendimento   = trim(pg_result($res,$i,tipo_atendimento));
			$tecnico_nome       = trim(pg_result($res,$i,tecnico_nome));
			$nome_atendimento   = trim(pg_result($res,$i,descricao));
			$admin              = trim(pg_result($res,$i,admin));

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
			if ($login_fabrica == 1) $xsua_os = "<a href='etiqueta_print.php?os=$os' target='_blank'>" . $codigo_posto.$sua_os . "</a>";

			echo "<tr class='Conteudo' height='15' bgcolor='$cor' align='left'>";
			echo "<td  width='50' nowrap>" ;
			if ($login_fabrica == 1) echo $xsua_os; else echo $sua_os;
			echo "</td>";
			echo "<td width='55' nowrap>" . $serie . "</td>";
			echo "<td nowrap ><acronym title='Data Abertura: $abertura' style='cursor: help;'>" . substr($abertura,0,5) . "</acronym></td>";



			if ($login_fabrica == 1) $aux_fechamento = $finalizada;
			else                     $aux_fechamento = $fechamento;
			echo "<td nowrap><acronym title='Data Fechamento: $aux_fechamento' style='cursor: help;'>" . substr($aux_fechamento,0,5) . "</acronym></td>";
			echo "<td width='120' nowrap><acronym title='Consumidor: $consumidor_nome' style='cursor: help;'>" . substr($consumidor_nome,0,15) . "</acronym></td>";
			$produto = $produto_referencia . " - " . $produto_descricao;
			echo "<td width='150' nowrap><acronym title='Referência: $produto_referencia \nDescrição: $produto_descricao \nVoltagem: $produto_voltagem' style='cursor: help;'>" . substr($produto,0,20) . "</acronym></td>";
			if($login_fabrica==19){
				echo"<td nowrap>$tipo_atendimento - $nome_atendimento </td>";
				echo"<td width='90' nowrap><acronym title='Nome do técnico: $tecnico_nome' style='cursor: help;'>" . substr($tecnico_nome,0,11) . "</acronym></td>";
				}

			
			##### VERIFICAÇÃO SE A OS FOI IMPRESSA #####
			echo "<td width='30' align='center'>";
			if (strlen($admin) > 0 and $login_fabrica == 19) echo "<img border='0' src='imagens/img_sac_lorenzetti.gif' alt='OS lançada pelo SAC Lorenzetti'>";
			else if (strlen($impressa) > 0)                  echo "<img border='0' src='imagens/img_ok.gif' alt='OS já foi impressa'>";
			else                                             echo "<img border='0' src='imagens/img_impressora.gif' alt='Imprimir OS'>";
			echo "</td>";



			##### VERIFICAÇÃO SE TEM ITEM NA OS PARA A FÁBRICA 1 #####
			if ($login_fabrica == 1) {
				echo "<td width='30' align='center'>";
				if ($qtde_item > 0) echo "<img border='0' src='imagens/img_ok.gif' alt='OS com item'>";
				else                echo "&nbsp;";
				echo "</td>";
			}

			echo "<td width='60' align='center'>";
			if ($excluida == "f" || strlen($excluida) == 0) echo "<a href='os_press.php?os=$os' target='_blank'><img border='0' src='imagens/btn_consulta.gif'></a>";
			echo "</td>\n";

			echo "<td width='60' align='center'>";
			if ($excluida == "f" || strlen($excluida) == 0) {
				if ($login_fabrica == 1 && $tipo_os_cortesia == "Compressor") {
					echo "<a href='os_print_blackedecker_compressor.php?os=$os' target='_blank'>";
				}else{
					echo "<a href='os_print.php?os=$os' target='_blank'>";
				}
				echo "<img border='0' src='imagens/btn_imprime.gif'></a>";
			}
			echo "</td>\n";

			if ($login_fabrica == 1) {
				echo "<td width='60' align='center'>";
				if (($excluida == "f" || strlen($excluida) == 0) && strlen($fechamento) == 0) {
					echo "<a href='os_cadastro.php?os=$os'><img border='0' src='imagens/btn_alterar_cinza.gif'></a>";
				}else{
					echo "&nbsp;";
				}
				echo "</td>\n";
			}

			echo "<td width='60' align='center' nowrap>";
			if ($troca_garantia == "t") {
			}elseif (($login_fabrica == 3 || $login_fabrica == 6) && strlen ($fechamento) == 0) {
				if ($excluida == "f" || strlen($excluida) == 0) {
					echo "<a href='os_item.php?os=$os' target='_blank'><img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
				}
			}elseif ($login_fabrica == 1 && strlen ($fechamento) == 0 ) {
				if ($excluida == "f" || strlen($excluida) == 0) {
					if ($login_fabrica == 1 AND $tipo_os_cortesia == "Compressor") {
						echo "<a href='os_blackedecker_valores.php?os=$os'>";
					}else{
						echo "<a href='os_item.php?os=$os' target='_blank'>";
					}
					echo "<img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
				}
			}elseif ($login_fabrica == 7 && strlen ($fechamento) == 0 ) {
				echo "<a href='os_filizola_valores.php?os=$os' target='_blank'><img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
			}elseif (strlen($fechamento) == 0 ) {
				if ($excluida == "f" OR strlen($excluida) == 0) {
					if ($login_fabrica == 1 AND $tipo_os_cortesia == "Compressor") {
						echo "<a href='os_blackedecker_valores.php?os=$os'>";
					}else{
						echo "<a href='os_item.php?os=$os' target='_blank'>";
					}
					echo "<img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
				}
			}elseif (strlen($fechamento) > 0 && strlen($extrato) == 0) {
				if ($excluida == "f" || strlen($excluida) == 0) {
					if (strlen ($importacao_fabrica) == 0) {
						echo "<a href='os_item.php?os=$os&reabrir=ok'><img border='0' src='imagens/btn_reabriros.gif'></a>";
					}
				}
			}else{
				echo "&nbsp;";
			}
			echo "</td>\n";

			if ($login_fabrica == 1) {
				echo "<td width='60' align='center'>";
				if (strlen($admin) == 0 AND strlen ($fechamento) == 0 AND ($excluida == "f" OR strlen($excluida) == 0) AND $mostra_motivo == 1) {
					echo "<a href='os_motivo_atraso.php?os=$os' target='_blank'><img border='0' src='imagens/btn_motivo.gif'></a>";
				}else{
					echo "&nbsp;";
				}
				echo "</td>\n";
			}

			echo "<td width='60' align='center'>";
			if (strlen($fechamento) == 0 && strlen($pedido) == 0 && $login_fabrica != 7 ) {
				if ($excluida == "f" || strlen($excluida) == 0) {
					if (strlen ($admin) == 0) {
						echo "<a href=\"javascript: if (confirm('Deseja realmente excluir a OS $sua_os ?') == true) { window.location='$PHP_SELF?excluir=$os'; }\"><img id='excluir_$i' border='0' src='imagens/btn_excluir.gif'></a>";
					}else{
						echo "<img id='excluir_$i' border='0' src='imagens/pixel.gif'>";
					}
				}
			}else{
				echo "&nbsp;";
			}
			echo "</td>\n";


			echo "<td width='60' align='center'>";
			if (strlen($fechamento) == 0) {
				if ($excluida == "f" || strlen($excluida) == 0) {
					echo "<a href=\"javascript: if (confirm('Confirma fechamento da OS $sua_os ?') == true) { fechaOS ($os,sinal_$i,excluir_$i, lancar_$i) ; }\"><img id='sinal_$i' border='0' src='/assist/imagens/btn_fecha.gif'></a>";
				}
			}else{
				echo "&nbsp;";
			}
			echo "</td>\n";


			if ($login_fabrica == 7) {
				echo "<td width='60' align='center'>";
				echo "<a href='os_matricial.php?os=$os' target='_blank'>Matricial</a>";
				echo "</td>\n";
			}

			echo "</tr>";
		}
		echo "</table>";
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
?>


<?
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


<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">

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
		<td colspan='2'> Data referente à digitação da OS no site (obrigatório para a pesquisa)</td>
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



	<?
	if ($login_e_distribuidor == 't' and $login_fabrica == 3) {
	?>
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
	<?
	}
	?>




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
