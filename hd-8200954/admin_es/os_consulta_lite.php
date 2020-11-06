<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

include "funcoes.php";

$admin_privilegios = "gerencia,call_center";
include "autentica_admin.php";


$msg = "";

$meses = array(1 => "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");

if (strlen($_POST['btn_acao']) > 0 ) {

	$os_off    = trim (strtoupper ($_POST['os_off']));
	$codigo_posto_off       = trim(strtoupper($_POST['codigo_posto_off']));
	$posto_nome_off        = trim(strtoupper($_POST['posto_nome_off']));


	$sua_os    = trim (strtoupper ($_POST['sua_os']));
	$serie     = trim (strtoupper ($_POST['serie']));
	$nf_compra = trim (strtoupper ($_POST['nf_compra']));
	$consumidor_cpf = trim (strtoupper ($_POST['consumidor_cpf']));

//takashi - não sei pq colocaram isso, estava com problema... caso necessite voltar, consulte o suporte
//takashi alterei novamente conforme Tulio e Samuel falaram
	if((strlen($sua_os)>0) and (strlen($sua_os)<4))$msg="Digite el mínimo 3 caracteres para efetuar la busca";
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

	if (strlen ($consumidor_nome) > 0 AND strlen ($codigo_posto) == 0 AND strlen ($produto_referencia) == 0) {
		$msg = " Especifique el Servicio o la Herramienta";
	}

	$consumidor_cpf = str_replace (".","",$consumidor_cpf);
	$consumidor_cpf = str_replace (" ","",$consumidor_cpf);
	$consumidor_cpf = str_replace ("-","",$consumidor_cpf);
	$consumidor_cpf = str_replace ("/","",$consumidor_cpf);

	#HD 17333
	#if (strlen ($consumidor_cpf) <> 11 AND strlen ($consumidor_cpf) <> 14 AND strlen ($consumidor_cpf) <> 0) {
	#	$msg = "Tamaño del ID consumidor inválido";
	#}

	$revenda_cnpj = str_replace (".","",$revenda_cnpj);
	$revenda_cnpj = str_replace (" ","",$revenda_cnpj);
	$revenda_cnpj = str_replace ("-","",$revenda_cnpj);
	$revenda_cnpj = str_replace ("/","",$revenda_cnpj);
	if (strlen ($revenda_cnpj) <> 8 AND strlen ($revenda_cnpj) > 0) {
		$msg = "Digite los 8 primeros dígitos de la Identificación";
	}

	if (strlen ($nf_compra) > 0 ) {
		$nf_compra = "000000" . $nf_compra;
		$nf_compra = substr ($nf_compra,strlen ($nf_compra)-6);
	}

	if ( (strlen ($codigo_posto) > 0 OR strlen ($posto_nome) > 0 OR strlen ($consumidor_nome) > 0 OR strlen ($produto_referencia) > 0 ) AND ( strlen ($mes) == 0 OR strlen ($ano) == 0) )  {
		$msg = "Digite el mes y el año para hacer la busca";
	}

	/*if ( (strlen ($codigo_posto) == 0 AND strlen ($posto_nome) == 0 AND strlen ($consumidor_nome) == 0 AND strlen ($produto_referencia) == 0 AND strlen ($admin) == 0 ) AND ( strlen ($mes) > 0 OR strlen ($ano) > 0) )  {
		$msg = " Especifique más de un campo para buscar";
	}*/

	if ( strlen ($posto_nome) > 0 AND strlen ($posto_nome) < 5 ) {
		$msg = "Digite en el mínimo 5 letras para el nombre del servicio";
	}

	if ( strlen ($consumidor_nome) > 0 AND strlen ($consumidor_nome) < 5) {
		$msg = "Digite en el mínimo 5 letras para el nombre del consumidor";
	}

	if ( strlen ($serie) > 0 AND strlen ($serie) < 5) {
		$msg = "Digite en el mínimo 5 letras para el número de série";
	}


	if (strlen($mes) > 0) {
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
	}

	//validacao para pegar o posto qdo for digitado a os_off
	if(strlen($os_off)>0){
		if ((strlen($codigo_posto_off)==0) OR (strlen($posto_nome_off)==0)){
			$msg = "Informe el Servicio deseado";
		}
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
					AND   tbl_posto_fabrica.codigo_posto = '$posto_codigo'
					AND   tbl_posto.pais = '$login_pais';";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) == 1) {
				$posto        = trim(pg_result($res,0,posto));
				$posto_codigo = trim(pg_result($res,0,codigo_posto));
				$posto_nome   = trim(pg_result($res,0,nome));
			}else{
				$erro .= " Servicio no encuentrado. ";
			}
		}
	}
}

$layout_menu = "call_center";
$title = "Seleción de Parámetros para Relación de Órdenes de Servicio digitadas";
include "cabecalho.php";
?>

<style type="text/css">
.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
}
.Erro{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
	background-color:#FFFFFF;
}
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

<br>

<?
//Adicionado HD 2216
#-------------- Obriga a digitação de alguns critérios ---------------
#-------------- TULIO 26/02/2007 - Nao mudar sem me avisar -----------
if (strlen ($os_off) == 0 AND 
	strlen ($sua_os) == 0 AND  
	strlen ($serie)  == 0 AND  
	strlen ($nf_compra) == 0 AND  
	strlen ($consumidor_cpf) == 0 AND  
	strlen ($mes) == 0 AND  
	strlen ($ano) == 0 AND  
	strlen ($consumidor_nome) == 0 AND  
	strlen ($posto_codigo) == 0 AND  
	strlen ($posto_nome) == 0 AND  
	strlen ($produto_referencia) == 0 and strlen($_POST['btn_acao']) > 0) {
		$msg = "Necesario especificar más campos para la busca";
}
#--------------------------------------------------------------------


if(strlen($msg)>0){
	echo "<table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td bgcolor='FFFFFF' width='60'class='Erro'><img src='imagens/proibido2.jpg' align='middle'></td><td  class='Erro' bgcolor='FFFFFF' align='left'> $msg</td>";
	echo "</tr>";
	echo "</table><br>";
}

if (strlen($_POST['btn_acao']) > 0 AND strlen($msg) == 0) {

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

			if (strlen ($codigo_posto) > 0) {
				$sqlX = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto'";
				$resX = pg_exec ($con,$sqlX);
				if(pg_numrows($resX)>0){
					$posto = pg_result ($resX,0,0);
					$especifica_mais_2 = "tbl_os.posto = $posto";
				}
			}

			$join_especifico = "JOIN (  SELECT os 
										FROM tbl_os 
										WHERE fabrica = $login_fabrica 
										AND   tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'
										AND   $especifica_mais_1
										AND   $especifica_mais_2
								) oss ON tbl_os.os = oss.os ";
		}

		// OS não excluída
		$sql =  "SELECT tbl_os.os                                                         ,
						tbl_os.sua_os                                                     ,
						sua_os_offline                                                    ,
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
						tbl_os.consumidor_fone                                            ,
						tbl_os.revenda_nome                                               ,
						tbl_os.tipo_atendimento                                           ,
						tbl_os.tecnico_nome                                               ,
						tbl_tipo_atendimento.descricao                                    ,
						tbl_posto_fabrica.codigo_posto                                    ,
						tbl_posto.nome                              AS posto_nome         ,
						tbl_os_extra.impressa                                             ,
						tbl_os_extra.extrato                                              ,
						tbl_os_extra.os_reincidente                                       ,
						tbl_produto.produto                                               ,
						tbl_produto.referencia                      AS produto_referencia ,
						tbl_produto.descricao                       AS produto_descricao  ,
						tbl_produto.voltagem                        AS produto_voltagem   ,
						distrib.codigo_posto                        AS codigo_distrib     ,
						(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os ORDER BY data DESC LIMIT 1) AS status_os
				FROM      tbl_os
				$join_especifico
				LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
				LEFT JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_os.posto
				LEFT JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN      tbl_produto       ON  tbl_produto.produto       = tbl_os.produto
				LEFT JOIN      tbl_os_extra      ON  tbl_os_extra.os           = tbl_os.os";
				
		if (strlen($os_situacao) > 0) {
			$sql .= " JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato";
			if ($os_situacao == "PAGA")
				$sql .= " JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato";
		}
		
		$sql .=	"
				LEFT JOIN tbl_posto_linha           ON tbl_posto_linha.linha         = tbl_produto.linha AND tbl_posto_linha.posto = tbl_os.posto
				LEFT JOIN tbl_posto_fabrica distrib ON tbl_os.digitacao_distribuidor = distrib.posto AND distrib.fabrica = $login_fabrica
				WHERE tbl_os.fabrica = $login_fabrica
				AND   tbl_os.excluida IS NOT TRUE
				AND  (status_os NOT IN (13,15) OR status_os IS NULL)
				AND tbl_posto.pais = '$login_pais'";

		if (strlen($mes) > 0) {
			$sql .= " AND tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'";
		}
		
		if (strlen($posto_nome) > 0) {
			$posto_nome = strtoupper ($posto_nome);
			$sql .= " AND upper(tbl_posto.nome) LIKE upper('$posto_nome%') ";
		}

		if (strlen($codigo_posto) > 0) {
			$sql .= " AND (tbl_posto_fabrica.codigo_posto = '$codigo_posto' OR distrib.codigo_posto = '$codigo_posto')";
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
#			$sql .= " AND (tbl_os.sua_os LIKE '$sua_os%' OR tbl_os.sua_os LIKE '0$sua_os%' OR tbl_os.sua_os LIKE '00$sua_os%' OR tbl_os.sua_os LIKE '000$sua_os%' OR tbl_os.sua_os LIKE '0000$sua_os%' OR tbl_os.sua_os LIKE '00000$sua_os%' OR tbl_os.sua_os LIKE '000000$sua_os%' OR tbl_os.sua_os LIKE '0000000$sua_os%' OR tbl_os.sua_os LIKE '00000000$sua_os%') ";

			$sql .= " AND (
				tbl_os.sua_os = '$sua_os' OR tbl_os.sua_os = '0$sua_os' OR tbl_os.sua_os = '00$sua_os' OR tbl_os.sua_os = '000$sua_os' OR tbl_os.sua_os = '0000$sua_os' OR tbl_os.sua_os = '00000$sua_os' OR tbl_os.sua_os = '000000$sua_os' OR tbl_os.sua_os = '0000000$sua_os' OR tbl_os.sua_os = '00000000$sua_os' OR ";

$sql .= "tbl_os.sua_os = '$sua_os-01' OR
		 tbl_os.sua_os = '$sua_os-02' OR
		 tbl_os.sua_os = '$sua_os-03' OR
		 tbl_os.sua_os = '$sua_os-04' OR
		 tbl_os.sua_os = '$sua_os-05' OR
		 tbl_os.sua_os = '$sua_os-06' OR
		 tbl_os.sua_os = '$sua_os-07' OR
		 tbl_os.sua_os = '$sua_os-08' OR
		 tbl_os.sua_os = '$sua_os-09' OR ";

$suas_oss = "";
for ($x=1;$x<=300;$x++) {
	$suas_oss .= "tbl_os.sua_os = '$sua_os-$x' OR ";
}
$sql .= $suas_oss;

$sql .= "tbl_os.sua_os = '0$sua_os-01' OR
		 tbl_os.sua_os = '0$sua_os-02' OR
		 tbl_os.sua_os = '0$sua_os-03' OR
		 tbl_os.sua_os = '0$sua_os-04' OR
		 tbl_os.sua_os = '0$sua_os-05' OR
		 tbl_os.sua_os = '0$sua_os-06' OR
		 tbl_os.sua_os = '0$sua_os-07' OR
		 tbl_os.sua_os = '0$sua_os-08' OR
		 tbl_os.sua_os = '0$sua_os-09' OR ";

$suas_oss = "";
for ($x=1;$x<=40;$x++) {
	$suas_oss .= "tbl_os.sua_os = '0$sua_os-$x' OR ";
}
$sql .= $suas_oss;



$sql .= "tbl_os.sua_os = '00$sua_os-01' OR
		 tbl_os.sua_os = '00$sua_os-02' OR
		 tbl_os.sua_os = '00$sua_os-03' OR
		 tbl_os.sua_os = '00$sua_os-04' OR
		 tbl_os.sua_os = '00$sua_os-05' OR
		 tbl_os.sua_os = '00$sua_os-06' OR
		 tbl_os.sua_os = '00$sua_os-07' OR
		 tbl_os.sua_os = '00$sua_os-08' OR
		 tbl_os.sua_os = '00$sua_os-09' OR ";

$suas_oss = "";
for ($x=1;$x<=40;$x++) {
	$suas_oss .= "tbl_os.sua_os = '00$sua_os-$x' OR ";
}
$sql .= $suas_oss;


$sql .= "tbl_os.sua_os = '000$sua_os-01' OR
		 tbl_os.sua_os = '000$sua_os-02' OR
		 tbl_os.sua_os = '000$sua_os-03' OR
		 tbl_os.sua_os = '000$sua_os-04' OR
		 tbl_os.sua_os = '000$sua_os-05' OR
		 tbl_os.sua_os = '000$sua_os-06' OR
		 tbl_os.sua_os = '000$sua_os-07' OR
		 tbl_os.sua_os = '000$sua_os-08' OR
		 tbl_os.sua_os = '000$sua_os-09' OR ";

$suas_oss = "";
for ($x=1;$x<=40;$x++) {
	$suas_oss .= "tbl_os.sua_os = '000$sua_os-$x' OR ";
}
$sql .= $suas_oss;

//apenas para terminar o OR
$sql .= "tbl_os.sua_os = '000$sua_os-40'"; 


			$sql .= ") ";
		
		}

		if (strlen($os_off) > 0) {
			$sql .= " AND (tbl_os.sua_os_offline LIKE '$os_off%') ";
		}

		if (strlen($serie) > 0) {
			$sql .= " AND tbl_os.serie = '$serie'";
		}
		
		if (strlen($nf_compra) > 0) {
			$sql .= " AND tbl_os.nota_fiscal = '$nf_compra'";
		}

		if (strlen($consumidor_nome) > 0) {
			$consumidor_nome = strtoupper ($consumidor_nome);
			$sql .= " AND upper(tbl_os.consumidor_nome) LIKE upper('$consumidor_nome%')";
		}

		if (strlen($consumidor_cpf) > 0) {
			$sql .= " AND tbl_os.consumidor_cpf = '$consumidor_cpf'";
		}

		if (strlen($os_aberta) > 0) {
			$sql .= " AND tbl_os.os_fechada IS FALSE ";
		}
		
		if ($os_situacao == "APROVADA") {
			$sql .= " AND tbl_extrato.aprovado IS NOT NULL ";
		}
		if ($os_situacao == "PAGA") {
			$sql .= " AND tbl_extrato_financeiro.data_envio IS NOT NULL ";
		}

		if (strlen($revenda_cnpj) > 0) {
			$sql .= " AND (tbl_os.data_fechamento IS NULL AND tbl_os.consumidor_revenda = 'R' AND tbl_os.revenda_cnpj LIKE '$revenda_cnpj%') ";
		}

		$sql .= " ORDER BY LPAD(tbl_os.sua_os,20,'0') DESC";

//VALIDAÇÃO FEITA POR RAPHAEL
		if (strlen($serie) > 0 AND $login_fabrica<>6) {
			//$sql .= " LIMIT 1";
		}


	$sqlT = str_replace ("\n"," ",$sql) ;
	$sqlT = str_replace ("\t"," ",$sqlT) ;
	
	$resT = @pg_exec ($con,"/* QUERY -> $sqlT  */");

//if ($login_admin == 19) { echo $sql ; exit ; }
flush();
	$res = pg_exec($con,$sql);


//if ($ip == '201.27.214.119') { echo $sql; exit; }
//	if (getenv("REMOTE_ADDR") == "201.42.47.138") { echo nl2br($sql) . "<br>" . pg_numrows($res); exit;}

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
		echo "<table border='0' cellspacing='0' cellpadding='0' align='center'>";
		if ($excluida == "t") {
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FFE1E1'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; Excluídas del sistema</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
		}
		echo "<tr height='18'>";
		echo "<td width='18' bgcolor='#D7FFE1'>&nbsp;</td>";
		echo "<td align='left'><font size='1'><b>&nbsp; Reincidencias</b></font></td>";
		echo "</tr>";
		echo "<tr height='3'><td colspan='2'></td></tr>";

		echo "<tr height='18'>";
		echo "<td width='18' bgcolor='#91C8FF'>&nbsp;</td>";
		echo "<td align='left'><font size='1'><b>&nbsp; OSs abiertas hace más de 25 días sin fecha cierre</b></font></td>";
		echo "</tr>";

		echo "</table>";
		echo "</div>";
		##### LEGENDAS - FIM #####

		echo "<br>";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			if ($i % 50 == 0) {
				echo "</table>";
				flush();
				echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'  align='center' width='96%'>";
			}

			if ($i % 50 == 0) {
				echo "<tr class='Titulo' height='15'>";
				echo "<td>OS</td>";
				echo "<td>SERIE</td>";
				echo "<td>AB</td>";
				echo "<td>FC</td>";
				echo "<td>SERVICIO</td>";
				echo "<td>CONSUMIDOR</td>";
				echo "<td>TELÉFONO</td>";
				echo "<td>HERRAMIENTA</td>";
				echo "<td colspan='2'>ACCIONES</td>";
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
			$consumidor_fone    = trim(pg_result($res,$i,consumidor_fone));
			$revenda_nome       = trim(pg_result($res,$i,revenda_nome));
			$codigo_posto       = trim(pg_result($res,$i,codigo_posto));
			$posto_nome         = trim(pg_result($res,$i,posto_nome));
			$impressa           = trim(pg_result($res,$i,impressa));
			$extrato            = trim(pg_result($res,$i,extrato));
			$os_reincidente     = trim(pg_result($res,$i,os_reincidente));
			$produto            = trim(pg_result($res,$i,produto));
			$produto_referencia = trim(pg_result($res,$i,produto_referencia));
			$produto_descricao  = trim(pg_result($res,$i,produto_descricao));
			$produto_voltagem   = trim(pg_result($res,$i,produto_voltagem));
			$tipo_atendimento   = trim(pg_result($res,$i,tipo_atendimento));
			$tecnico_nome       = trim(pg_result($res,$i,tecnico_nome));
			$nome_atendimento   = trim(pg_result($res,$i,descricao));
			$sua_os_offline     = trim(pg_result($res,$i,sua_os_offline));
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

			//--=== Tradução para outras linguas ============================= Raphael HD:1212
			$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";
		
			$res_idioma = @pg_exec($con,$sql_idioma);
			if (@pg_numrows($res_idioma) >0) {
				$produto_descricao  = trim(@pg_result($res_idioma,0,descricao));
			}
			//--=== Tradução para outras linguas ================================================


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
			if ($login_fabrica == 1) $sua_os = "<a href='etiqueta_print.php?os=$os' target='_blank'>" . $codigo_posto.$sua_os . "</a>";

			echo "<tr class='Conteudo' height='15' bgcolor='$cor' align='left'>";
			echo "<td nowrap>" . $sua_os . "</td>";
			if($login_fabrica==19 OR $login_fabrica==10 OR $login_fabrica==1){
				echo "<td nowrap>" . $sua_os_offline . "</td>";
			}
			echo "<td nowrap>" . $serie . "</td>";
			echo "<td nowrap ><acronym title='Fecha Abertura: $abertura' style='cursor: help;'>" . substr($abertura,0,5) . "</acronym></td>";
			if ($login_fabrica == 1) $aux_fechamento = $finalizada;
			else                     $aux_fechamento = $fechamento;
			echo "<td nowrap><acronym title='Fecha Cierre: $aux_fechamento' style='cursor: help;'>" . substr($aux_fechamento,0,5) . "</acronym></td>";
			echo "<td nowrap><acronym title='Servicio: $codigo_posto - $posto_nome' style='cursor: help;'>" . substr($posto_nome,0,15) . "</acronym></td>";
			echo "<td nowrap><acronym title='Consumidor: $consumidor_nome' style='cursor: help;'>" . substr($consumidor_nome,0,15) . "</acronym></td>";
			echo "<td nowrap><acronym title='Teléfono: $consumidor_fone' style='cursor: help;'>" .
				$consumidor_fone. "</acronym></td>";
			$produto = $produto_referencia . " - " . $produto_descricao;
			echo "<td nowrap><acronym title='Referencia: $produto_referencia \nDescripción: $produto_descricao \nVoltaje: $produto_voltagem' style='cursor: help;'>" . substr($produto,0,20) . "</acronym></td>";
			if($login_fabrica==19){
				echo "<td>$tipo_atendimento $nome-atendimiento</td>";
				echo "<td>$tecnico_nome</td>";
				}
			echo "<td width='60' align='center'>";
			echo "<a href='os_cadastro.php?os=$os' target='_blank'><img border='0' src='imagens_admin/btn_alterar_". $botao ."_es.gif'></a>";
			echo "</td>\n";

			echo "<td width='60' align='center'>";
			echo "<a href='os_press.php?os=$os' target='_blank'><img border='0' src='imagens_admin/btn_consultar_".$botao."_es.gif'></a>";
			echo "</td>\n";

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
	
	echo "<br><h1>Resultado de la busca: $resultados</h1>";
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
	$consumidor_fone = trim (strtoupper ($_POST['consumidor_fone']));
	$os_situacao     = trim (strtoupper ($_POST['os_situacao']));
?>


<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Titulo" height="30">
		<td align="center">Elija los parámetros para consulta </td>
	</tr>
</table>

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>Número da OS</td>
		<td>Número de Série</td>
		<td>Número de Factura</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td><input type="text" name="sua_os"    size="10" value="<?echo $sua_os?>"    class="frm"></td>
		<td><input type="text" name="serie"     size="10" value="<?echo $serie?>"     class="frm"></td>
		<td><input type="text" name="nf_compra" size="10" value="<?echo $nf_compra?>" class="frm"></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>ID Consumidor</td>
		<td></td>
		<td></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td><input type="text" name="consumidor_cpf" size="17" value="<?echo $consumidor_cpf?>" class="frm"></td>
		<td></td>
		<td></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan='3' align='center'><br><input type="submit" name="btn_acao" value="Buscar"></td>
	</tr>
</table>





<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='2'> <hr> </td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td> * Mes</td>
		<td> * Año</td>
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

			&nbsp;&nbsp;&nbsp;Sólo OS abiertas <input type='checkbox' name='os_aberta' value='1' <? if (strlen ($os_aberta) > 0 ) echo " checked " ?> >

		</td>

	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>Cód. Servicio</td>
		<td>Nombre del Servicio</td>
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
		<td>Nombre del Usuário</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td></td>
		<td><input type="text" name="consumidor_nome" size="30" value="<?echo $consumidor_nome?>" class="frm"></td>
	</tr>




	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>Cód. Herramienta</td>
		<td>Descripción de la Herramienta</td>
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
		<td><input type="radio" name="os_situacao" value="APROVADA" <? if ($os_situacao == "APROVADA") echo "checked"; ?>> OS Aprobadas</td>
		<td><input type="radio" name="os_situacao" value="PAGA" <? if ($os_situacao == "PAGA") echo "checked"; ?>> OS Pagadas</td>
	</tr>



	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='2'> <hr> </td>
	</tr>


	
</table>
	
	
<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan='2' align='center'><br><input type="submit" name="btn_acao" value="Buscar"></td>
	</tr>
</table>



				


</table>


</form>


<? include "rodape.php" ?>
