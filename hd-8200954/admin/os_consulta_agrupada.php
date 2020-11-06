<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

include "funcoes.php";

$admin_privilegios = "gerencia";
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

if (strlen($_POST['btn_acao']) > 0 or strlen($_GET['btn_acao']) > 0) {
	$sua_os    = trim (strtoupper ($_POST['sua_os']));
	if (strlen($sua_os)==0) $sua_os = trim(strtoupper($_GET['sua_os']));
	$serie     = trim (strtoupper ($_POST['serie']));
	if (strlen($serie)==0) $serie = trim(strtoupper($_GET['serie']));
	$nf_compra = trim (strtoupper ($_POST['nf_compra']));
	if (strlen($nf_compra)==0) $nf_compra = trim(strtoupper($_GET['nf_compra']));
	$consumidor_cpf = trim (strtoupper ($_POST['consumidor_cpf']));
	if (strlen($consumidor_cpf)==0) $consumidor_cpf = trim(strtoupper($_GET['consumidor_cpf']));

	$marca     = trim ($_POST['marca']);
	if(strlen($marca)>0){ $cond_marca = " tbl_marca.marca = $marca ";}else{ $cond_marca = " 1 = 1 ";}

	$mes = trim (strtoupper ($_POST['mes']));
	if (strlen($mes)==0) $mes = trim(strtoupper($_GET['mes']));
	$ano = trim (strtoupper ($_POST['ano']));
	if (strlen($ano)==0) $ano = trim(strtoupper($_GET['ano']));

	$codigo_posto       = trim(strtoupper($_POST['codigo_posto']));
	if (strlen($codigo_posto)==0) $codigo_posto = trim(strtoupper($_GET['codigo_posto']));
	$posto_nome         = trim(strtoupper($_POST['posto_nome']));
	if (strlen($posto_nome)==0) $posto_nome = trim(strtoupper($_GET['posto_nome']));
	$produto_referencia = trim(strtoupper($_POST['produto_referencia']));
	if (strlen($produto_referencia)==0) $produto_referencia = trim(strtoupper($_GET['produto_referencia']));
	$admin              = trim($_POST['admin']);
	if (strlen($admin)==0) $admin = trim($_GET['admin']);
	$os_situacao        = trim(strtoupper($_POST['os_situacao']));
	if (strlen($os_situacao)==0) $os_situacao = trim(strtoupper($_GET['os_situacao']));
	$revenda_cnpj       = trim(strtoupper($_POST['revenda_cnpj']));
	if (strlen($revenda_cnpj)==0) $revenda_cnpj = trim(strtoupper($_GET['revenda_cnpj']));

	if (strlen ($consumidor_nome) > 0 AND strlen ($codigo_posto) == 0 AND strlen ($produto_referencia) == 0) {
		$msg = "Especifique o posto ou o produto";
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
		$nf_compra = $nf_compra;
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
		$xdata_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$xdata_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
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
$title = "Consulta de Ordem de Serviço agrupada";
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

.mensagem {
    width: 600px;
    margin: 0 auto;
    margin-top: 20px;
    margin-bottom: 20px;
    text-align: center;
    padding: 10px 5px;
    font-size: 10pt;
}

.msg-erro {
    border: 1px solid #FF0000;
    background-color: #FF8F8F;
}

.msg-info {
    border: 1px solid #596D9B;
    background-color: #E6EEF7;
}
</style>

<? include "javascript_pesquisas.php"; ?>
<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">

$(function()
{
	$('#data_inicial').datePicker({startDate:'01/01/2000'});
	$('#data_final').datePicker({startDate:'01/01/2000'});
	$("#data_inicial").maskedinput("99/99/9999");
	$("#data_final").maskedinput("99/99/9999");
});

$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

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
<br>




<?
if ((strlen($_POST['btn_acao']) > 0 or strlen($_GET['btn_acao']) > 0)  and (
	strlen ($serie)  == 0 AND
	strlen ($nf_compra) == 0 AND
	strlen ($consumidor_cpf) == 0 AND
	strlen ($mes) == 0 AND
	strlen ($ano) == 0 AND
	strlen ($consumidor_nome) == 0 AND
	strlen ($posto_codigo) == 0 AND
	strlen ($posto_nome) == 0 AND
	strlen ($produto_referencia) == 0)) {
		$msg = "Necessário especificar mais campos para pesquisa";
}

if ((strlen($_POST['btn_acao']) > 0 or strlen($_GET['btn_acao']) > 0) AND strlen($msg) == 0) {

		$join_especifico = "";
		$especifica_mais_1 = "1=1";
		$especifica_mais_2 = "1=1";

		if (strlen ($xdata_inicial) > 0) {
			if (strlen ($produto_referencia) > 0) {
				$sqlX = "SELECT produto FROM tbl_produto JOIN tbl_linha USING (linha) WHERE tbl_linha.fabrica = $login_fabrica AND tbl_produto.referencia = '$produto_referencia'";
				$resX = pg_exec ($con,$sqlX);
				$produto = pg_result ($resX,0,0);
				$especifica_mais_1 = "tbl_os.produto = $produto";
			}

			if (strlen ($codigo_posto) > 0) {
				$sqlX = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND upper(codigo_posto) = upper('$codigo_posto')";
				$resX = pg_exec ($con,$sqlX);
				if (pg_numrows($resX) > 0) {
					$posto = pg_result ($resX,0,0);
					$especifica_mais_2 = "tbl_os.posto = $posto";
				}
			}

			$sqlTP = "
			SELECT distinct tbl_os.os
			INTO TEMP tmp_consulta_$login_admin
			FROM tbl_os
			$join_troca
			WHERE fabrica = $login_fabrica";
			if (strlen($xdata_inicial) > 0 and strlen($xdata_final)>0) {
				$sqlTP .= " AND   tbl_os.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final' ";
			}
			$sqlTP .=" AND   $especifica_mais_1
					   AND   $especifica_mais_2 ;
			CREATE INDEX tmp_consulta_OS_$login_admin ON tmp_consulta_$login_admin(os)";

			#echo "$sqlTP<br><br>";
			$resX = pg_exec ($con,$sqlTP);

			$join_especifico = "JOIN tmp_consulta_$login_admin oss ON tbl_os.os = oss.os ";
		}

		$sql =  "SELECT __SELECT__
				FROM      tbl_os
				$join_especifico
				LEFT JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_os.posto
				LEFT JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN      tbl_produto       ON  tbl_produto.produto       = tbl_os.produto
				LEFT JOIN      tbl_os_extra      ON  tbl_os_extra.os           = tbl_os.os
				LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca ";

		if (strlen($os_situacao) > 0) {
			$sql .= " JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato";
			if ($os_situacao == "PAGA")
				$sql .= " JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato";
		}

		$sql .=	"
				LEFT JOIN tbl_posto_linha           ON tbl_posto_linha.linha         = tbl_produto.linha AND tbl_posto_linha.posto = tbl_os.posto
				WHERE tbl_os.fabrica = $login_fabrica
				AND $cond_marca ";

		if (strlen($mes) > 0) {
			$sql .= " AND tbl_os.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final'";
		}
		if (strlen($posto_nome) > 0) {
			$posto_nome = strtoupper ($posto_nome);
			$sql .= " AND upper(tbl_posto.nome) LIKE upper('$posto_nome%') ";
		}

		if (strlen($codigo_posto) > 0) {
			$sql .= " AND (upper(tbl_posto_fabrica.codigo_posto) = '$codigo_posto' )";
		}

		if (strlen($produto_referencia) > 0) {
			$sql .= " AND tbl_produto.referencia = '$produto_referencia' ";
		}

		if (strlen($admin) > 0) {
			$sql .= " AND tbl_os.admin = '$admin' ";
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

		if ($os_situacao == "APROVADA") {
			$sql .= " AND tbl_extrato.aprovado IS NOT NULL ";
		}
		if ($os_situacao == "PAGA") {
			$sql .= " AND tbl_extrato_financeiro.data_envio IS NOT NULL ";
		}

		if (strlen($revenda_cnpj) > 0) {
			$sql .= " AND tbl_os.revenda_cnpj LIKE '$revenda_cnpj%' ";
		}
		
		// SQL para disponibilizar resumo (HD 104151)
		// exibir somente pra fabirca 3 (britania)
        $sql_resumo = $sql." AND tbl_os.data_digitacao is not null GROUP BY tbl_produto.produto, tbl_produto.descricao ORDER BY tbl_produto.descricao ASC";
        $sql_resumo = str_replace('__SELECT__',"count(tbl_produto.produto) as qtd, tbl_produto.produto, tbl_produto.descricao",$sql_resumo);
        // fim HD 104151
		$sql        .= "GROUP BY to_char(tbl_os.data_digitacao,'MM/YYYY'),to_char(tbl_os.data_digitacao,'YYYY') ,to_char(tbl_os.data_digitacao,'MM'),tbl_os.posto ,tbl_posto_fabrica.codigo_posto,tbl_posto.nome ORDER BY tbl_posto_fabrica.codigo_posto ASC,to_char(tbl_os.data_digitacao,'YYYY') ASC,to_char(tbl_os.data_digitacao,'MM') ASC";
        
        $campos_select = "tbl_posto_fabrica.codigo_posto, tbl_posto.nome, to_char(tbl_os.data_digitacao,'MM/YYYY') as os_data,
                          count(tbl_os.data_digitacao) as qtde_aberta,
                          count(tbl_os.finalizada)     as qtde_finalizada";
        $sql           = str_replace('__SELECT__',$campos_select,$sql);
        
	$sqlT = str_replace ("\n"," ",$sql) ;
	$sqlT = str_replace ("\t"," ",$sqlT) ;

	$resT = @pg_exec ($con,"/* QUERY -> $sqlT  */");
	flush();
	##### PAGINAÇÃO - INÍCIO #####
	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";
    
	require "_class_paginacao.php";

	// definicoes de variaveis
	$max_links = 11;				// máximo de links à serem exibidos
	$max_res   = 50;				// máximo de resultados à serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página
	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");
	##### PAGINAÇÃO - FIM #####

	$resultados = pg_numrows($res);

	if (pg_numrows($res) > 0) {
		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'  align='center' width='86%'>";
		echo "<tr class='Titulo'>";
		echo "<td>Posto</td>";
		echo "<td>Mês/Ano</td>";
		echo "<td>OS Aberta</td>";
		echo "<td>OS Finalizada</td>";
		echo "</tr>";
		for ($i = 0 ; $i < $resultados ; $i++) {
			if ($i % 50 == 0) {
				flush();
			}
			$qtde_aberta        = pg_result($res,$i,qtde_aberta);
			$qtde_finalizada    = pg_result($res,$i,qtde_finalizada);
			$codigo_posto       = pg_result($res,$i,codigo_posto);
			$nome_posto         = pg_result($res,$i,nome);
			$os_data            = pg_result($res,$i,os_data);

			if ($i % 2 == 0) {
				$cor   = "#FFFFFF";
			}else{
				$cor   = "#D9E2EF";
			}
			echo "<tr class='Conteudo' bgcolor='$cor'>";
			echo "<td align='left'>$codigo_posto - $nome_posto</td>";
			echo "<td align='center'>$os_data</td>";
			echo "<td align='center'>$qtde_aberta</td>";
			echo "<td align='center'>$qtde_finalizada</td>";
			echo "</tr>";
		}
		echo "</table>";
	}else{
		$msg="Nenhum resultado encontrado";
	}
	// ------------------------------ Resumo (HD 104151)
	if ( $login_fabrica == 3 && strlen($_POST['codigo_posto']) > 0 && strlen($_POST['nf_compra']) > 0 ) {
        $res_resumo  = pg_exec($con,$sql_resumo);
    	$rows_resumo = (int) pg_num_rows($res_resumo);
	} else {
	    $rows_resumo = 0;
	}
	?>
	<?php if ( $resultados > 0 && $rows_resumo > 0 ) { ?>
	<p> &nbsp; </p>
	<table border="1" cellpadding="2" cellspacing="0" style="border-collapse: collapse" bordercolor="#000000"  align="center" width="86%">
	   <tr class="Titulo">
	       <td colspan="2"> Resumo dos Produtos em OS abertas </td>
	   </tr>
	   <tr class="Titulo">
	       <td> Produto </td>
	       <td> Quantidade </td>
	   </tr>
           <?php for($i=0;$i<$rows_resumo; $i++) { ?>
    	   <tr class="Conteudo" bgcolor="<?php echo ($i%2)?'#FFFFFF':'#D9E2EF'; ?>">
    	       <td align="left"> <?php echo pg_result($res_resumo,$i,'produto').' - '.pg_result($res_resumo,$i,'descricao'); ?> </td>
    	       <td> <?php echo (int) pg_result($res_resumo,$i,'qtd'); ?> </td>
    	   </tr>
    	   <?php } ?>
	    <?php } ?>
	</table>
	<?php
	// ------------------------------ Resumo (fim)

	##### PAGINAÇÃO - INÍCIO #####
	echo "<br>";
	echo "<div>";

	if($pagina < $max_links) $paginacao = pagina + 1;
	else                     $paginacao = pagina;
	$todos_links		= $mult_pag->Construir_Links("strings", "sim");
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
}
?>


<?
	$serie              = trim (strtoupper ($_POST['serie']));
	if (strlen($serie)==0) $serie = trim(strtoupper($_GET['serie']));
	$nf_compra          = trim (strtoupper ($_POST['nf_compra']));
	if (strlen($nf_compra)==0) $nf_compra = trim(strtoupper($_GET['nf_compra']));
	$consumidor_cpf     = trim (strtoupper ($_POST['consumidor_cpf']));
	if (strlen($consumidor_cpf)==0) $consumidor_cpf = trim(strtoupper($_GET['consumidor_cpf']));
	$produto_referencia = trim (strtoupper ($_POST['produto_referencia']));
	if (strlen($produto_referencia)==0) $produto_referencia = trim(strtoupper($_GET['produto_referencia']));
	$produto_descricao  = trim (strtoupper ($_POST['produto_descricao']));
	if (strlen($produto_descricao)==0) $produto_descricao = trim(strtoupper($_GET['produto_descricao']));

	$mes = trim (strtoupper ($_POST['mes']));
	$ano = trim (strtoupper ($_POST['ano']));

	$codigo_posto    = trim (strtoupper ($_POST['codigo_posto']));
	if (strlen($codigo_posto)==0) $codigo_posto = trim(strtoupper($_GET['codigo_posto']));
	$posto_nome      = trim (strtoupper ($_POST['posto_nome']));
	if (strlen($posto_nome)==0) $posto_nome = trim(strtoupper($_GET['posto_nome']));
	$consumidor_nome = trim (strtoupper ($_POST['consumidor_nome']));
	if (strlen($consumidor_nome)==0) $consumidor_nome = trim(strtoupper($_GET['consumidor_nome']));
	$consumidor_fone = trim (strtoupper ($_POST['consumidor_fone']));
	if (strlen($consumidor_fone)==0) $consumidor_fone = trim(strtoupper($_GET['consumidor_fone']));
	$os_situacao     = trim (strtoupper ($_POST['os_situacao']));
	if (strlen($os_situacao)==0) $os_situacao = trim(strtoupper($_GET['os_situacao']));

if(strlen($msg)>0){
	echo "<table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td bgcolor='FFFFFF' width='60'class='Erro'><img src='imagens/proibido2.jpg' align='middle'></td><td  class='Erro' bgcolor='FFFFFF' align='left'> $msg</td>";
	echo "</tr>";
	echo "</table><br>";
}
?>
<?php if ( $login_fabrica == 3 && $resultados <= 0 ): ?>
    <div class="mensagem msg-info">
        O resumo da quantidade de produtos, só é exibido caso seja filtrado um número de uma <em>Nota Fiscal de Compra</em> e um <em>Posto</em>.
    </div>
<?php endif; ?>
<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Titulo" height="30">
		<td align="center">Selecione os parâmetros para a pesquisa.</td>
	</tr>
<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>Número de Série</td>
		<td>NF. Compra</td>
		<td>CPF Consumidor</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td><input type="text" name="serie"     size="10" value="<?echo $serie?>"     class="frm"></td>
		<td><input type="text" name="nf_compra" size="10" value="<?echo $nf_compra?>" class="frm"></td>
		<td><input type="text" name="consumidor_cpf" size="10" value="<?echo $consumidor_cpf?>" class="frm"></td>
	</tr>
	<?php if ( $login_fabrica != 3 ) { // hd 104151 ?>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan='4' align='center'><br><input type="submit" name="btn_acao" value="Pesquisar"></td>
	</tr>
	<?php } // fim hd 104151 ?>
</table>

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
        <?php if ( $login_fabrica != 3 ) { //hd 104151 ?>
    	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
    		<td colspan='2'> <hr> </td>
    	</tr>
    	<?php } // fim hd 104151 ?>
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
			</td>
		</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>Posto</td>
		<td>Nome do Posto</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>
			<input type="text" name="codigo_posto" id="codigo_posto" size="8" value="<? echo $codigo_posto ?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'codigo')">
		</td>
		<td>
			<input type="text" name="posto_nome" id="posto_nome" size="30"  value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'nome')">
		</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>Marca</td>
		<td>Nome do Consumidor</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td><?
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
		?></td>
		<td><input type="text" name="consumidor_nome" size="30" value="<?echo $consumidor_nome?>" class="frm"></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='2'>
		</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>Ref. Produto</td>
		<td>Descrição Produto</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>
		<input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" >
		<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia, document.frm_consulta.produto_descricao,'referencia')">
		</td>

		<td>
		<input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="30" value="<? echo $produto_descricao ?>" >
		<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia, document.frm_consulta.produto_descricao,'descricao')">
	</tr>
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
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td><input type="radio" name="os_situacao" value="APROVADA" <? if ($os_situacao == "APROVADA") echo "checked"; ?>> OS´s Aprovadas</td>
		<td><input type="radio" name="os_situacao" value="PAGA" <? if ($os_situacao == "PAGA") echo "checked"; ?>> OS´s Pagas</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='2'> <hr> </td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='2'> OS em aberto da Revenda = CNPJ
		<input class="frm" type="text" name="revenda_cnpj" size="8" value="<? echo $revenda_cnpj ?>" maxlength="8"> /0001-00
		</td>
	</tr>
</table>

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan='2' align='center'><br><input type="submit" name="btn_acao" value="Pesquisar"></td>
	</tr>
</table>
</form>


<? include "rodape.php" ?>
