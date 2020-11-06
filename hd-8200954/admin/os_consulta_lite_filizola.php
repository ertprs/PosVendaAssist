<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

include "funcoes.php";

$admin_privilegios = "gerencia,call_center";
include "autentica_admin.php";


# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];
	
	if (strlen($q)>2){

		if ($tipo_busca=="posto"){
			$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
			
			if ($busca == "codigo"){
				$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
			}else{
				$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
			}
			
			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$cnpj = trim(pg_result($res,$i,cnpj));
					$nome = trim(pg_result($res,$i,nome));
					$codigo_posto = trim(pg_result($res,$i,codigo_posto));
					echo "$cnpj|$nome|$codigo_posto";
					echo "\n";
				}
			}
		}

		if ($tipo_busca=="produto"){
			$sql = "SELECT tbl_produto.produto,
							tbl_produto.referencia,
							tbl_produto.descricao
					FROM tbl_produto
					JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
					WHERE tbl_linha.fabrica = $login_fabrica ";
			
			if ($busca == "codigo"){
				$sql .= " AND tbl_produto.referencia like '%$q%' ";
			}else{
				$sql .= " AND UPPER(tbl_produto.descricao) like UPPER('%$q%') ";
			}
			
			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$produto    = trim(pg_result($res,$i,produto));
					$referencia = trim(pg_result($res,$i,referencia));
					$descricao  = trim(pg_result($res,$i,descricao));
					echo "$produto|$descricao|$referencia";
					echo "\n";
				}
			}
		}
	}
	exit;
}


$msg = "";

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (strlen($_POST['btn_acao']) > 0 ) {

	$os_off    = trim (strtoupper ($_POST['os_off']));
	$codigo_posto_off      = trim(strtoupper($_POST['codigo_posto_off']));
	$posto_nome_off        = trim(strtoupper($_POST['posto_nome_off']));

	$sua_os    = trim (strtoupper ($_POST['sua_os']));
	$serie     = trim (strtoupper ($_POST['serie']));
	$nf_compra = trim (strtoupper ($_POST['nf_compra']));
	$consumidor_cpf = trim (strtoupper ($_POST['consumidor_cpf']));

	$marca     = trim ($_POST['marca']);
	if(strlen($marca)>0){ $cond_marca = " tbl_marca.marca = $marca ";}else{ $cond_marca = " 1 = 1 ";}


//takashi - não sei pq colocaram isso, estava com problema... caso necessite voltar, consulte o suporte
//takashi alterei novamente conforme Tulio e Samuel falaram
	if((strlen($sua_os)>0) and (strlen($sua_os)<4))$msg="Digite no minímo 3 caracteres para fazer a pesquisa";
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
	$pais               = trim(strtoupper($_POST['pais']));

	if (strlen ($consumidor_nome) > 0 AND strlen ($codigo_posto) == 0 AND strlen ($produto_referencia) == 0) {
		$msg = "Especifique o posto ou o produto";
	}

	$consumidor_cpf = str_replace (".","",$consumidor_cpf);
	$consumidor_cpf = str_replace (" ","",$consumidor_cpf);
	$consumidor_cpf = str_replace ("-","",$consumidor_cpf);
	$consumidor_cpf = str_replace ("/","",$consumidor_cpf);
	if (strlen ($consumidor_cpf) <> 11 AND strlen ($consumidor_cpf) <> 14 AND strlen ($consumidor_cpf) <> 0) {
		#HD 17333
		if ($login_fabrica<>20){
			$msg = "Tamanho do CPF do consumidor inválido";
		}
	}

	$revenda_cnpj = str_replace (".","",$revenda_cnpj);
	$revenda_cnpj = str_replace (" ","",$revenda_cnpj);
	$revenda_cnpj = str_replace ("-","",$revenda_cnpj);
	$revenda_cnpj = str_replace ("/","",$revenda_cnpj);
	if (strlen ($revenda_cnpj) <> 8 AND strlen ($revenda_cnpj) > 0) {
		$msg = "Digite os 8 primeiros dígitos do CNPJ";
	}

	if (strlen ($nf_compra) > 0 ) {
		if ($login_fabrica==19 and strlen($nf_compra) > 6) {
			$nf_compra = "0000000" . $nf_compra;
			$nf_compra = substr ($nf_compra,strlen ($nf_compra)-7);
		} else {
			$nf_compra = "000000" . $nf_compra;
			$nf_compra = substr ($nf_compra,strlen ($nf_compra)-6);
		}
	}

	if ( (strlen ($codigo_posto) > 0 OR strlen ($posto_nome) > 0 OR strlen ($consumidor_nome) > 0 OR strlen ($produto_referencia) > 0 ) AND ( strlen ($mes) == 0 OR strlen ($ano) == 0) )  {
		$msg = "Digite o mês e o ano para fazer a pesquisa";
	}

	if ( (strlen ($codigo_posto) == 0 AND strlen ($posto_nome) == 0 AND strlen ($consumidor_nome) == 0 AND strlen ($produto_referencia) == 0 AND strlen ($admin) == 0 ) AND ( strlen ($mes) > 0 OR strlen ($ano) > 0) and ($login_fabrica==20 and ($pais=='BR' or $pais=='' )))  {
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

	//validacao para pegar o posto qdo for digitado a os_off
	if(strlen($os_off)>0){
		if ((strlen($codigo_posto_off)==0) OR (strlen($posto_nome_off)==0)){
			$msg = "Informe o Posto desejado";
		}
	}
	//IGOR HD 1967 BLACK - PARA CONSULTAR OS É OBRIGATÓRIO SELECIONAR O POSTO
	if($login_fabrica==1) {
		if ((strlen($codigo_posto)== 0 ) and (strlen($sua_os)>0) )
			$msg = "Para consultar pelo número de OS é necessário Informar o código do posto";
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
$title = "Seleção de Parâmetros para Relação de Ordens de Serviços Lançadas";
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

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}
	
	/* OFFF Busca pelo Código */
	$("#codigo_posto_off").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#codigo_posto_off").result(function(event, data, formatted) {
		$("#posto_nome_off").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome_off").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome_off").result(function(event, data, formatted) {
		$("#codigo_posto_off").val(data[2]) ;
		//alert(data[2]);
	});

		
	/* Busca pelo Código */
	$("#codigo_posto").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#codigo_posto").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#codigo_posto").val(data[2]) ;
		//alert(data[2]);
	});

	
	/* Busca por Produto */
	$("#produto_descricao").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#produto_descricao").result(function(event, data, formatted) {
		$("#produto_referencia").val(data[2]) ;
	});

	/* Busca pelo Nome */
	$("#produto_referencia").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#produto_referencia").result(function(event, data, formatted) {
		$("#produto_descricao").val(data[1]) ;
		//alert(data[2]);
	});

});
</script>

<script language="javascript" src="js/assist.js"></script>

<br>




<?
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
	strlen ($produto_referencia) == 0 ) {
		$msg = "Necessário especificar mais campos para pesquisa";
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
				$sqlX = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND upper(codigo_posto) = upper('$codigo_posto')";
				$resX = pg_exec ($con,$sqlX);
				$posto = pg_result ($resX,0,0);
				$especifica_mais_2 = "tbl_os.posto = $posto";
			}
			$sqlTP = "
			SELECT os 
			INTO TEMP tmp_consulta_$login_admin
			FROM tbl_os 
			WHERE fabrica = $login_fabrica 
			AND   tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'
			AND   $especifica_mais_1
			AND   $especifica_mais_2 ;
			CREATE INDEX tmp_consulta_OS_$login_admin ON tmp_consulta_$login_admin(os)";

			//echo "$sqlTP<br><br>";
			$resX = pg_exec ($con,$sqlTP);


			$join_especifico = "JOIN tmp_consulta_$login_admin oss ON tbl_os.os = oss.os ";
		}
		//HD 14927
		if($login_fabrica == 11 or $login_fabrica == 45 or $login_fabrica == 15 or $login_fabrica == 3 ){
			$sql_data_conserto=" , to_char(tbl_os.data_conserto,'DD/MM/YYYY') as data_conserto ";
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
						tbl_os.os_reincidente                      AS reincidencia        ,
						tbl_os.aparencia_produto                                          ,
						tbl_os.tecnico_nome                                               ,
						tbl_tipo_atendimento.descricao                                    ,
						tbl_posto_fabrica.codigo_posto                                    ,
						tbl_posto.nome                              AS posto_nome         ,
						tbl_os_extra.impressa                                             ,
						tbl_os_extra.extrato                                              ,
						tbl_os_extra.os_reincidente                                       ,
						tbl_produto.referencia                      AS produto_referencia ,
						tbl_produto.descricao                       AS produto_descricao  ,
						tbl_produto.voltagem                        AS produto_voltagem   ,
						distrib.codigo_posto                        AS codigo_distrib     ,";
						if ($login_fabrica == 3) {
							$sql .= "tbl_marca.marca ,
										tbl_marca.nome as marca_nome,";
						}
			$sql .= " (SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os ORDER BY data DESC LIMIT 1) AS status_os
			$sql_data_conserto
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
		if ($login_fabrica == 3) {
			$sql .= " LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca ";
		}
		$sql .=	"
				LEFT JOIN tbl_posto_linha           ON tbl_posto_linha.linha         = tbl_produto.linha AND tbl_posto_linha.posto = tbl_os.posto
				LEFT JOIN tbl_posto_fabrica distrib ON tbl_os.digitacao_distribuidor = distrib.posto AND distrib.fabrica = $login_fabrica
				WHERE tbl_os.fabrica = $login_fabrica ";

		if($login_fabrica <>3 AND $login_fabrica <> 11 AND $login_fabrica<>45 AND $login_fabrica<>20) {
			$sql .=" AND   tbl_os.excluida IS NOT TRUE 
					 AND  (status_os NOT IN (13,15) OR status_os IS NULL)";
		}
		#HD 13940 - Para mostrar as OS recusadas
		if($login_fabrica==20) {
			$sql .=" AND (tbl_os.excluida IS NOT TRUE OR tbl_os_extra.status_os = 94 )
					 AND  (status_os NOT IN (13,15) OR status_os IS NULL)";
		}

		if (strlen($mes) > 0) {
			$sql .= " AND tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'";
		}
		 //takashi colocou 11/12/07 hd 9542
		/* postos que tinham */
			if (strlen($posto_nome) > 0) {
				$posto_nome = strtoupper ($posto_nome);
				$sql .= " AND upper(tbl_posto.nome) LIKE upper('$posto_nome%') ";
			}

			if (strlen($codigo_posto) > 0) {
				$sql .= " AND (upper(tbl_posto_fabrica.codigo_posto) = '$codigo_posto' OR upper(distrib.codigo_posto) = '$codigo_posto')";
			}//TAKASHI COLOCOU OS 2 UPPERS NO CÓDIGO, POSTOS DA CADENCE QUE TEM LETRA NO CÓDIGO NAO ESTAVAM ACHANDO, FIZ O EXPLAIN E NÃO DEU DIFERENCA COM UPPER OU NÃO. Caso dê problema uma alternativa seria igualar diretamente com o posto, pois antes do sql ele ja localiza o posto.
			//HD 9542 11/12/07
		
		if(1==2){
			if (strlen($posto) > 0) {
				$sql .= " AND tbl_os.posto = $posto";
			}
		}
		if (strlen($produto_referencia) > 0) {
			$sql .= " AND tbl_produto.referencia = '$produto_referencia' ";
		}
		
		if (strlen($admin) > 0) {
			$sql .= " AND tbl_os.admin = '$admin' ";
		}
		if($login_fabrica == 3 ){
			$sql .= " AND $cond_marca ";
		}

		if (strlen($sua_os) > 0) {
			#A Black tem consulta separada(os_consulta_avancada.php).
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

			$pos = strpos($sua_os, "-");
			if ($pos === false) {
				if(!ctype_digit($sua_os)){
					$sql .= " AND tbl_os.sua_os = '$sua_os' ";
				}else{
					if($login_fabrica==5){
						$sql .= " AND (tbl_os.os_numero = '$sua_os' OR tbl_os.sua_os = '$sua_os')";
					}else{
						$sql .= " AND tbl_os.os_numero = '$sua_os'";
					}
				}
			}else{
				$conteudo = explode("-", $sua_os);
				$os_numero    = $conteudo[0];
				$os_sequencia = $conteudo[1];
				if(!ctype_digit($os_sequencia)){
					$sql .= " AND tbl_os.sua_os = '$sua_os' ";
				}else{
					$sql .= " AND tbl_os.os_numero = '$os_numero' AND tbl_os.os_sequencia = '$os_sequencia' ";
				}
			}
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
			$sql .= " AND tbl_os.revenda_cnpj LIKE '$revenda_cnpj%' ";
		}

		if (strlen($pais) > 0) {
			$sql .= " AND tbl_posto.pais ='$pais' ";
		}

		$sql .= " ORDER BY LPAD(tbl_os.sua_os,20,'0') DESC";

//VALIDAÇÃO FEITA POR RAPHAEL
		if (strlen($serie) > 0 AND $login_fabrica<>6) {
			//$sql .= " LIMIT 1";
		}


	$sqlT = str_replace ("\n"," ",$sql) ;
	$sqlT = str_replace ("\t"," ",$sqlT) ;

	$resT = @pg_exec ($con,"/* QUERY -> $sqlT  */");


//if ($login_admin == 852) { echo $sql ; exit ; }
//echo nl2br($sql); 
flush();
	$res = pg_exec($con,$sql);

//echo $sql;

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
		if ($excluida == "t" ) {
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
			if($login_fabrica==50){
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#91C8FF'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; OSs abertas há mais de 5 dias sem data de fechamento</b></font></td>";
				echo "</tr>";
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#FF6633'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; OSs abertas há mais de 10 dias sem data de fechamento</b></font></td>";
				echo "</tr>";
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#FF0000'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; OSs abertas há mais de 20 dias sem data de fechamento</b></font></td>";
				echo "</tr>";


			}else{
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#91C8FF'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; OSs abertas há mais de 25 dias sem data de fechamento</b></font></td>";
				echo "</tr>";
			}
		}
		if($login_fabrica == 3 OR $login_fabrica==11){
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FFCCCC'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; ";
			echo "OS com Intervenção da Fábrica. Aguardando Liberação";
			echo "</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FFFF99'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; ";
			echo "OS com Intervenção da Fábrica. Reparo na Fábrica";
			echo "</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#00EAEA'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; ";
			echo "OS Liberada Pela Fábrica";
			echo "</b></font></td>";
			echo "</tr>";
		}
		if($login_fabrica == 3 OR $login_fabrica == 11 OR $login_fabrica==45){
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FF0000'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; ";
			echo "OS Cancelada";
			echo "</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#CCCCFF'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; ";
			echo "OS com Ressarcimento Financeiro";
			echo "</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
		}
		if ($login_fabrica == 20) {
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#CACACA'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; OS Reprovada pelo Promotor</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
		}

		echo "<tr height='18'>";
		echo "<td width='18' bgcolor='#FFCC66'>&nbsp;</td>";
		echo "<td align='left'><font size='1'><b>&nbsp; ";
		echo "OS com Troca de Produto";
		echo "</b></font></td>";
		echo "</tr>";

		echo "<tr height='3'><td colspan='2'></td></tr>";

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
				if($login_fabrica==19 OR $login_fabrica==10 OR $login_fabrica==1){
					echo "<td>OS OFF LINE</td>";
				}
				echo "<td>";
				if($login_fabrica ==35){
					echo "PO#";
				}else{
					echo "SÉRIE";
				}
				echo "</td>";
				echo "<td>AB</td>";
				//HD 14927
				if($login_fabrica ==3 or $login_fabrica ==11 or $login_fabrica ==15 or $login_fabrica ==45){
					echo "<td><acronym title='Data de conserto do produto' style='cursor:help;'>DC</a></td>";
				}
				echo "<td>FC</td>";
				echo "<td>POSTO</td>";
				echo "<td>CONSUMIDOR</td>";
				echo "<td>TELEFONE</td>";
				if($login_fabrica==3){
					echo "<td>MARCA</td>";
				}
				echo "<td>PRODUTO</td>";
				if($login_fabrica==19){
					echo "<td>Atendimento</td>";
					echo "<td>Nome do técnico</td>";
				}
				if($login_fabrica==1){//TAKASHI HD925
					echo "<td>APARÊNCIA</td>";
				}				
				if($login_fabrica==7) $colspan = 3;
				else                  $colspan = 2;
				echo "<td colspan='$colspan'>AÇÕES</td>";
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
			$produto_referencia = trim(pg_result($res,$i,produto_referencia));
			$produto_descricao  = trim(pg_result($res,$i,produto_descricao));
			$produto_voltagem   = trim(pg_result($res,$i,produto_voltagem));
			$tipo_atendimento   = trim(pg_result($res,$i,tipo_atendimento));
			$tecnico_nome       = trim(pg_result($res,$i,tecnico_nome));
			$nome_atendimento   = trim(pg_result($res,$i,descricao));
			$sua_os_offline     = trim(pg_result($res,$i,sua_os_offline));
			$reincidencia       = trim(pg_result($res,$i,reincidencia));
			$aparencia_produto  = trim(pg_result($res,$i,aparencia_produto));//TAKASHI HD925
			$status_os          = trim(pg_result($res,$i,status_os)); //fabio
			if($login_fabrica==3){
				$marca     = trim(pg_result($res,$i,marca));
				$marca_nome     = trim(pg_result($res,$i,marca_nome));
			}
			//HD 14927
			if($login_fabrica == 11 or $login_fabrica == 45 or $login_fabrica == 15 or $login_fabrica == 3 ){
				$data_conserto=trim(pg_result($res,$i,data_conserto));
			}

			if ($i % 2 == 0) {
				$cor   = "#F1F4FA";
				$botao = "azul";
			}else{
				$cor   = "#F7F5F0";
				$botao = "amarelo";
			}
			if ($login_fabrica==3 OR $login_fabrica==11){
				if ($status_os=="62") $cor="#E6E6FA";
				if ($status_os=="62") $cor="#FFCCCC";
				if ($status_os=="72") $cor="#FFCCCC";
	
				if (($status_os=="64" OR $status_os=="73") && strlen($fechamento)==0) $cor="#00EAEA";
				if ($status_os=="65") $cor="#FFFF99";
			}
			##### VERIFICAÇÕES PARA OS CRITÉRIOS DA LEGENDA - INÍCIO #####
			if ($reincidencia =='t') $cor = "#D7FFE1";
			if ($excluida == "t")    $cor = "#FF0000";

			if ($login_fabrica==20 AND $status_os == "94" AND $excluida == "t"){
				$cor = "#CACACA";
			}

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



			// CONDIÇÕES PARA COLORMAQ - INÍCIO
			if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 50) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_atual = pg_result($resX,0,0);

				if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#91C8FF";

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '10 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_atual = pg_result ($resX,0,0);

				if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#FF6633";


				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '20 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_atual = pg_result ($resX,0,0);

				if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#FF0000";
			}
			// CONDIÇÕES PARA COLORMAQ - FIM


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

			$sqlX = "SELECT os_troca,ressarcimento FROM tbl_os_troca WHERE os = $os";
			$resX = pg_exec($con,$sqlX);
			if(pg_numrows($resX)==1){
				$cor = "#FFCC66";
				if(pg_result($resX,0,ressarcimento)=='t')$cor = "#CCCCFF";
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
			echo "<td nowrap ><acronym title='Data Abertura: $abertura' style='cursor: help;'>" . substr($abertura,0,5) . "</acronym></td>";
			//HD 14927
			if($login_fabrica ==3 or $login_fabrica ==11 or $login_fabrica ==15 or $login_fabrica ==45){
				echo "<td nowrap ><acronym title='Data do Conserto: $data_conserto' style='cursor: help;'>" . substr($data_conserto,0,5) . "</acronym></td>";
			}
			if ($login_fabrica == 1) $aux_fechamento = $finalizada;
			else                     $aux_fechamento = $fechamento;
			echo "<td nowrap><acronym title='Data Fechamento: $aux_fechamento' style='cursor: help;'>" . substr($aux_fechamento,0,5) . "</acronym></td>";

			echo "<td nowrap><acronym title='Posto: $codigo_posto - $posto_nome' style='cursor: help;'>" . substr($posto_nome,0,15) . "</acronym></td>";
			echo "<td nowrap><acronym title='Consumidor: $consumidor_nome' style='cursor: help;'>" . substr($consumidor_nome,0,15) . "</acronym></td>";
			echo "<td nowrap><acronym title='Telefone: $consumidor_fone' style='cursor: help;'>" .
				$consumidor_fone. "</acronym></td>";
			if($login_fabrica==3){//TAKASHI HD925
				echo "<td nowrap>$marca_nome</td>";
			}
			$produto = $produto_referencia . " - " . $produto_descricao;
			echo "<td nowrap><acronym title='Referência: $produto_referencia \nDescrição: $produto_descricao \nVoltagem: $produto_voltagem' style='cursor: help;'>" . substr($produto,0,20) . "</acronym></td>";
			if($login_fabrica==19){
				echo "<td>$tipo_atendimento $nome-atendimento</td>";
				echo "<td>$tecnico_nome</td>";
				}
			if($login_fabrica==1){//TAKASHI HD925
				echo "<td>$aparencia_produto</td>";
				}
			echo "<td width='60' align='center'>";
 			if($excluida <>'t'){
				if ($login_fabrica==1 AND ($tipo_atendimento==17 OR $tipo_atendimento==18)){
					echo "<a href='os_cadastro_troca.php?os=$os' target='_blank'><img border='0' src='imagens_admin/btn_alterar_$botao.gif'></a>";
				}else{
					echo "<a href='os_cadastro.php?os=$os' target='_blank'><img border='0' src='imagens_admin/btn_alterar_$botao.gif'></a>";
				}
			}
			echo "</td>\n";

			echo "<td width='60' align='center'>";
			echo "<a href='os_press.php?os=$os' target='_blank'><img border='0' src='imagens/btn_consultar_$botao.gif'></a>";
			echo "</td>\n";

			if($login_fabrica==7 AND $consumidor_revenda!="R"){//HD 31598
				echo "<td width='60' align='center'>";
				echo "<a href='os_transferencia.php?sua_os=$sua_os&posto_codigo_origem=$codigo_posto&posto_nome_origem=$posto_nome' target='_blank'><img border='0' src='imagens/btn_transferir_$botao.gif'></a>";
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
	$consumidor_fone = trim (strtoupper ($_POST['consumidor_fone']));
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
		<td>
			<?
			if($login_fabrica==35){
				echo "PO#";
			}else{
				echo "Número de Série";
			}
			?>
		</td>
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








<!-- CONSULTA OS OFF LINE -->
<?if($login_fabrica==19 OR $login_fabrica==10){?>
	<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td colspan='2'> <hr> </td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td colspan='2'> Consulta OS Off Line</td>
		</tr>

		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td colspan='2'>OS Off Line
			</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td colspan='2'><input type="text" name="os_off" size="10" value="" class="frm">
			</td>
		</tr>
		
		
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td>Posto</td>
			<td>Nome do Posto</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td>
				<input type="text" name="codigo_posto_off" id="codigo_posto_off" size="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto_off, document.frm_consulta.posto_nome_off, 'codigo');" <? } ?> value="<? echo $codigo_posto_off ?>" class="frm">
				<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto_off, document.frm_consulta.posto_nome_off, 'codigo')">
			</td>
			<td>
				<input type="text" name="posto_nome_off" id="posto_nome_off" size="30" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto_off, document.frm_consulta.posto_nome_off, 'nome');" <? } ?> value="<?echo $posto_nome_off ?>" class="frm">
				<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto_off, document.frm_consulta.posto_nome_off, 'nome')">
			</td>
		</tr>


		<tr class="Conteudo" bgcolor="#D9E2EF" align='center'>
			<td colspan='3' align='center'><input type="submit" name="btn_acao" value="Pesquisar">
			</td>
		</tr>
	</table>
<?}?>

<!--fim consulta off line -->


























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
			<input type="text" name="codigo_posto" id="codigo_posto" size="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'codigo');" <? } ?> value="<? echo $codigo_posto ?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'codigo')">
		</td>
		<td>
			<input type="text" name="posto_nome" id="posto_nome" size="30" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'nome');" <? } ?> value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'nome')">
		</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td><?
		if($login_fabrica==3){echo "Marca";}
		?></td>
		<td>Nome do Consumidor</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td><?
		if($login_fabrica==3){
			echo "<select name='marca' size='1' class='frm' style='width:95px'>";
			echo "<option value=''></option>";
			$sql = "SELECT marca, nome from tbl_marca where fabrica = $login_fabrica order by nome";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)>0){
				for($i=0;pg_numrows($res)>$i;$i++){
					$xmarca = pg_result($res,$i,marca);
					$xnome = pg_result($res,$i,nome);
					?>
					<option value="<?echo $xmarca;?>" <? if ($xmarca == $marca) echo " SELECTED "; ?>><?echo $xnome;?></option>

					<?

				}
			
			}
			echo "</SELECT>";
		}
		?></td>
		<td><input type="text" name="consumidor_nome" size="30" value="<?echo $consumidor_nome?>" class="frm"></td>
	</tr>




	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>Ref. Produto</td>
		<td>Descrição Produto</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>
		<input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" > 
		&nbsp;
		<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia, document.frm_consulta.produto_descricao,'referencia')">
		</td>

		<td>
		<input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="30" value="<? echo $produto_descricao ?>" >
		&nbsp;
		<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia, document.frm_consulta.produto_descricao,'descricao')">
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

<?if($login_fabrica == 20){
// MLG 2009-08-04 HD 136625
    $sql = 'SELECT pais,nome FROM tbl_pais';
    $res = pg_query($con,$sql);
    $p_tot = pg_num_rows($res);
    for ($i; $i<$p_tot; $i++) {
        list($p_code,$p_nome) = pg_fetch_row($res, $i);
    	$sel_paises .= "\t\t\t\t<option value='$p_code'";
        $sel_paises .= ($pais==$p_code)?" selected":"";
        $sel_paises .= ">$p_nome</option>\n";
    }
?>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='2'>País<br>
			<select name='pais' size='1' class='frm'>
			 <option></option>
            <?echo $sel_paises;?>
			</select>
		</td>
	</tr>
<?}?>

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
