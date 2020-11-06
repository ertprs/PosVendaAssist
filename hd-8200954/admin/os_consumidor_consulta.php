<?php
$areaAdminCliente = preg_match('/\/admin_cliente\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
if ($areaAdminCliente == true) {
    include_once "../dbconfig.php";
    include_once "../includes/dbconnect-inc.php";
    //include 'autentica_admin.php';
    include_once '../funcoes.php';
} else {
    $admin_privilegios="gerencia,call_center";
    include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include "funcoes.php";
}

include 'autentica_admin.php';

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);

$msg = "";

if (strlen($acao) > "PESQUISAR") {
	##### Pesquisa entre datas #####
	if (strlen(trim($_POST["data_inicial"])) > 0) $data_inicial = trim($_POST["data_inicial"]);
	if (strlen(trim($_GET["data_inicial"])) > 0)  $data_inicial = trim($_GET["data_inicial"]);
	if (strlen(trim($_POST["data_final"])) > 0)   $data_final   = trim($_POST["data_final"]);
	if (strlen(trim($_GET["data_final"])) > 0)    $data_final   = trim($_GET["data_final"]);
	if (strlen($data_inicial)>0  && strlen($data_final)>0 ) {
		
		list($d, $m, $y) = explode("/", $data_inicial);

		if(!checkdate($m,$d,$y)) $msg = "Data Inválida";
		
		if(strlen($msg)==0){
			list($d, $m, $y) = explode("/", $data_final);

				if(!checkdate($m,$d,$y)) $msg = "Data Inválida";
		}
		if(strlen($msg)==0){
			$d_ini = explode ("/", $data_inicial);//tira a barra
			$x_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...


			$d_fim = explode ("/", $data_final);//tira a barra
			$x_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

			if($x_data_final < $x_data_inicial){
				$msg = "Data Inválida.";
			}
		}
				
	}

	##### Pesquisa de produto #####
	if (strlen(trim($_POST["posto_codigo"])) > 0) $posto_codigo  = trim($_POST["posto_codigo"]);
	if (strlen(trim($_GET["posto_codigo"])) > 0)  $posto_codigo  = trim($_GET["posto_codigo"]);
	if (strlen(trim($_POST["posto_nome"])) > 0)   $posto_nome    = trim($_POST["posto_nome"]);
	if (strlen(trim($_GET["posto_nome"])) > 0)    $posto_nome    = trim($_GET["posto_nome"]);
	if (strlen(trim($_POST["posto_estado"])) > 0) $posto_estado  = trim($_POST["posto_estado"]);
	if (strlen(trim($_GET["posto_estado"])) > 0)  $posto_estado  = trim($_GET["posto_estado"]);
	if (strlen($posto_codigo) > 0 && strlen($posto_nome) > 0 && strlen($msg)==0) {
		$sql =	"SELECT tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome                ,
						tbl_posto.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica
 				AND   tbl_posto_fabrica.codigo_posto = '$posto_codigo';";
		$res = pg_exec($con,$sql);
		if (pg_numrows($res) == 1) {
			$posto        = pg_result($res,0,posto);
			$posto_codigo = pg_result($res,0,codigo_posto);
			$posto_nome   = pg_result($res,0,nome);
		}else{
			$msg .= " Posto não encontrado. ";
		}
	}

	if (strlen(trim($_POST["numero_os"])) > 0) $numero_os = trim($_POST["numero_os"]);
	if (strlen(trim($_GET["numero_os"])) > 0)  $numero_os = trim($_GET["numero_os"]);

	if (strlen($numero_os) > 0 && strlen($numero_os) < 5) {
		$msg = " Preencha a OS com mais de 5 dígitos. ";
	}

	if (strlen(trim($_POST["consumidor_cpf"])) > 0) $consumidor_cpf = trim($_POST["consumidor_cpf"]);
	if (strlen(trim($_GET["consumidor_cpf"])) > 0)  $consumidor_cpf = trim($_GET["consumidor_cpf"]);

	if (strlen($numero_os) == 0 && strlen($consumidor_cpf) == 0 && (strlen($x_data_inicial) == 0 || strlen($x_data_final) == 0) && strlen($msg)==0) {
		$msg = " Informe mais campos para realizar a pesquisa.";
	}

	if (strlen($posto) == 0 && (strlen($x_data_inicial) > 0 || strlen($x_data_final) > 0) && strlen($msg)==0) {
		$msg = " Informe mais campos para realizar a pesquisa. ";
	}

	if (strlen(trim($_POST["solucao"])) > 0) $solucao = trim($_POST["solucao"]);
	if (strlen(trim($_GET["solucao"])) > 0)  $solucao = trim($_GET["solucao"]);
}

/* Fucao que exibe os Estados (UF) */
function selectUF($selUF=""){
	$cfgUf = array("","AC","AL","AM","AP","BA","CE","DF","ES","GO","MA","MG","MS","MT","PA","PB","PI","PR","RJ","RN","RO","RR","RS","SC","SE","SP","TO");
	if($selUF == "") $selUF = $cfgUf[0];

	$totalUF = count($cfgUf) - 1;
	for($currentUF=0; $currentUF <= $totalUF; $currentUF++){
		echo "                      <option value=\"$cfgUf[$currentUF]\"";
		if($selUF == $cfgUf[$currentUF]) print(" selected");
		echo ">$cfgUf[$currentUF]</option>\n";
	}
}

$layout_menu = "callcenter";
$title = "RELAÇÃO DE ORDENS DE SERVIÇOS LANÇADAS";

include "cabecalho.php";
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


table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.center_tabela {
	margin: 0 auto !important;
}

</style>

<?php 
	include "javascript_pesquisas.php";
	include "../js/js_css.php";
?>

<script type="text/javascript" src='js/jquery.alphanumeric.js'></script>
<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");

		$("input[name=consumidor_cpf]").numeric({allow:'./-'});
		$("input[name=numero_os]").numeric();
		
	});

	function abre_certificado(certificado) {

		msg = 'Esse certificado garante ao consumidor a garantia do(s) produto(s). Portanto, é necessário imprimir, solicitar a assinatura e entregar para o consumidor; Alerte o cliente a guardar esse documento, pois se for necessário uma nova garantia nesse período deverá apresentar o certificado.';

		if (confirm(msg)) {
			window.open('certificado_impressao.php?certificado='+certificado);
		}

		return false;

	}
</script>


<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class='formulario'>
<? if (strlen($msg) > 0) { ?>
	<tr CLASS='msg_erro'>
		<td colspan="5"><?echo $msg?></td>
	</tr>

<? } ?>
	<tr class="titulo_tabela">
		<td colspan="5">Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td width="80">&nbsp;</td>
		<td>Data Inicial</td>
		<td colspan="2">Data Final</td>
		<td width="80">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>
			<input size="12" maxlength="10" TYPE="text" NAME="data_inicial" id="data_inicial" value="<?if (strlen($data_inicial) > 0) echo substr($data_inicial,0,10);?>" class="frm">
		</td>
		<td colspan="2">
			<input size="12" maxlength="10" TYPE="text" NAME="data_final" id="data_final" value="<?if (strlen($data_final) > 0) echo substr($data_final,0,10);?>" class="frm">
		</td>
		<td>&nbsp;</td>
	</TR>
	<TR class="table">
		<TD colspan="5"><hr color="#EEEEEE"></TD>
	</TR>
	<TR class="table">
		<TD>&nbsp;</TD>
		<TD>Código do Posto</TD>
		<TD>Nome do Posto</TD>
		<TD>Estado</TD>
		<TD>&nbsp;</TD>
	</TR>
	<TR>
		<TD>&nbsp;</TD>
		<td>
			<input type="text" name="posto_codigo" size="10" value="<?echo $posto_codigo?>" class="frm">
			<img src="imagens/lupa.png" style="cursor: pointer;" align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.posto_codigo, document.frm_consulta.posto_nome,'codigo');">
		</td>
		<td>
			<input type="text" name="posto_nome" size="40" value="<?echo $posto_nome?>" class="frm">
			<img src="imagens/lupa.png" style="cursor: pointer;" align='absmiddle' alt="Clique aqui para pesquisas postos pelo nome" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.posto_codigo, document.frm_consulta.posto_nome,'nome');">
		</td>
		<TD>
			<select name="posto_estado" class="frm">
				<? selectUF($posto_estado); ?>
			</select>
		</TD>
		<td>&nbsp;</td>
	</tr>
	
	<TR>
		<TD>&nbsp;</TD>
		<TD>Número da OS <br />
		<input type="text" name="numero_os" value="<? echo $numero_os; ?>" class="frm"></TD>
		
		<TD>CPF / CNPJ Consumidor <br />
		<input type="text" name="consumidor_cpf" value="<? echo $consumidor_cpf; ?>" class="frm"></TD>
		<TD colspan="2">&nbsp;</TD>
	</TR>
	
	<TR>
		<TD>&nbsp;</TD>
		<TD colspan='3'>Solução <br />
			<select name="solucao" size="1" class="frm">
			<option name=""></option>
			<?
				$sql =	"SELECT solucao,
						descricao
					FROM tbl_solucao
					WHERE fabrica = $login_fabrica
					ORDER BY descricao;";
				$res = pg_exec($con,$sql);

				if (pg_numrows($res) > 0) {
					for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
						$x_solucao = trim(pg_result($res,$i,solucao));
						$x_descricao         = trim(pg_result($res,$i,descricao));
						echo "<option value='$x_solucao'";
						if ($solucao == $x_solucao) echo " selected";
						echo ">$x_descricao</option>";
					}
				}
			?>
			</select>
		</TD>
		<TD>&nbsp;</TD>
	</TR>
	
	<tr class="table">
		<td colspan="5" align="center" style='padding:10px 0 10px 0;'><input type='button' value='Pesquisar' onclick="javascript: document.frm_consulta.acao.value='PESQUISAR'; document.frm_consulta.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</table>
</form>
<br />
<?
if (strlen($msg) == 0 AND strlen($acao) > 0) {
	$sql =	"SELECT tbl_os.os                                                         ,
					tbl_os.sua_os                                                     ,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao     ,
					tbl_os.consumidor_nome                                            ,
					tbl_os.consumidor_fone                                            ,
					tbl_os.custo_peca                                                 ,
					tbl_posto_fabrica.codigo_posto              AS posto_codigo       ,
					tbl_posto.nome                              AS posto_nome         ,
					tbl_produto.referencia                      AS produto_referencia ,
					tbl_produto.descricao                       AS produto_descricao  ,
					tbl_solucao.descricao                       AS solucao
			FROM      tbl_os
			JOIN      tbl_posto          ON  tbl_posto.posto            = tbl_os.posto
			JOIN      tbl_posto_fabrica  ON  tbl_posto_fabrica.posto    = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN      tbl_produto        ON  tbl_produto.produto        = tbl_os.produto
			LEFT JOIN tbl_solucao        ON  tbl_solucao.solucao        = tbl_os.solucao_os
			WHERE tbl_os.fabrica            = $login_fabrica
			AND   tbl_os.consumidor_revenda = 'C' ";

# - AND   tbl_os.finalizada NOTNULL - Retirado a pedido da Silvania em 13/01/2006

	if (strlen($x_data_inicial) > 0 && strlen($x_data_final) > 0) $sql .= " AND tbl_os.data_digitacao BETWEEN '$x_data_inicial 00:00' AND '$x_data_final 23:59'";
	if (strlen($posto) > 0) $sql .= " AND tbl_posto.posto = $posto ";
	if (strlen($posto_estado) > 0) $sql .= " AND tbl_posto.estado = '$posto_estado' ";
	if (strlen($numero_os) > 0) {
		$sua_os = $numero_os;
		$numero_os_aux = $numero_os;
		$pos = strpos($sua_os, "-");

		if ($pos === false) {
			//hd 47506
			if(strlen ($sua_os) > 11){
				$pos = strlen($sua_os) - (strlen($sua_os)-7);
			} elseif(strlen ($sua_os) > 10) {
				$pos = strlen($sua_os) - (strlen($sua_os)-6);
			} elseif(strlen ($sua_os) > 9) {
				$pos = strlen($sua_os) - (strlen($sua_os)-5);
			}else{
				$pos = strlen($sua_os);
			}
		}else{
			//hd 47506
			if(strlen (substr($sua_os,0,$pos)) > 11){#47506
				$pos = $pos - 7;
			} else if(strlen (substr($sua_os,0,$pos)) > 10) {
				$pos = $pos - 6;
			} elseif(strlen ($sua_os) > 9) {
				$pos = $pos - 5;
			}
		}
		$xsua_os = substr($sua_os, $pos,strlen($sua_os));
		if (strlen($posto_codigo)==0) {
			$posto_codigo = substr($sua_os, 0, 5);
		}
		$sua_os = strtoupper ($sua_os);
		$pos = strpos($sua_os, "-");
		if ($pos === false) {
			if(!ctype_digit($sua_os)){
				$sql .= " AND tbl_os.sua_os = '$sua_os' ";
			}else{
				//hd 47506 - acrescentado OR "tbl_os.sua_os = '$sua_os'"
				$sql .= " AND (tbl_os.os_numero = '$sua_os' OR tbl_os.sua_os like '%$sua_os' ";
				if(strlen($xsua_os) > 0) {
					$sql .=" OR tbl_os.sua_os like '%$xsua_os' ";
				}
				$sql.=" )  AND tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";
			}
		}else{
			$conteudo = explode("-", $sua_os);
			$os_numero    = $conteudo[0];
			$os_sequencia = $conteudo[1];
			if(!ctype_digit($os_sequencia)){
				$sql .= " AND tbl_os.sua_os = '$sua_os'  AND tbl_posto_fabrica.codigo_posto = '$posto_codigo'";
			}else{
				$sql .= " AND tbl_os.os_numero = '$os_numero' AND tbl_os.os_sequencia = '$os_sequencia'  AND tbl_posto_fabrica.codigo_posto = '$posto_codigo'";
			}
		}
	}
	if (strlen($consumidor_cpf) > 0) {
		$consumidor_cpf = str_replace(".","",$consumidor_cpf);
		$consumidor_cpf = str_replace("/","",$consumidor_cpf);
		$consumidor_cpf = str_replace("-","",$consumidor_cpf);
		$sql .= " AND tbl_os.consumidor_cpf = '$consumidor_cpf' ";
	}
	if (strlen($solucao) > 0) $sql .= " AND tbl_os.solucao_os = $solucao ";

	//hd 47856
	if ($login_fabrica==1) {
		$sql .= " AND tbl_os.excluida is not true ";
	}

	$sql .= " ORDER BY tbl_posto_fabrica.codigo_posto, tbl_os.sua_os DESC";

//echo nl2br($sql);

	##### PAGINAÇÃO - INÍCIO #####
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
	##### PAGINAÇÃO - FIM #####

//	if (getenv("REMOTE_ADDR") == "201.0.9.216") echo nl2br($sql) . "<br>" . pg_numrows($res);

	if (pg_numrows($res) == 0) {
		echo "<h1>NENHUMA OS ENCONTRADA</h1>";
	}

	if (pg_numrows($res) > 0) {
		echo "<table width='700px' border='1' cellpadding='2' cellspacing='1' class='tabela center_tabela'>";
		echo "<tr class='titulo_coluna' height='15'>";
		echo "<td>Posto</td>";
		if ($login_fabrica == 1) {//HD 235182
			echo "<td>Certificado</td>";
		}
		echo "<td>OS</td>";
		echo "<td>Digitação</td>";
		echo "<td align='left'>&nbsp;Produto</td>";
		echo "<td align='left'>Serviço Realizado</td>";
		echo "<td align='left'>Consumidor</td>";
		echo "<td>Fone Consumidor</td>";
		if($login_fabrica==1){
			echo "<td>Custo de Peças</td>";
		}
		echo "</tr>";
		$total_custo_peca=0;
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$os                 = pg_result($res,$i,os);
			$sua_os             = pg_result($res,$i,sua_os);
			$data_digitacao     = pg_result($res,$i,data_digitacao);
			$consumidor_nome    = pg_result($res,$i,consumidor_nome);
			$consumidor_fone    = pg_result($res,$i,consumidor_fone);
			$posto_codigo       = pg_result($res,$i,posto_codigo);
			$posto_nome         = pg_result($res,$i,posto_nome);
			$produto_referencia = pg_result($res,$i,produto_referencia);
			$produto_descricao  = pg_result($res,$i,produto_descricao);
			$solucao            = pg_result($res,$i,solucao);

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

			echo "<tr bgcolor='$cor'>";
			echo "<td nowrap align='left'>$posto_codigo - $posto_nome</td>";

			if ($login_fabrica == 1) {//HD 235182

				$sql = "SELECT certificado, codigo, faturado FROM tbl_certificado WHERE os = $os AND fabrica = $login_fabrica";
				$res_certificado = pg_query($con, $sql);
				$tot_certificado = pg_num_rows($res_certificado);

				if ($tot_certificado > 0) {

					$codigo      = trim(pg_result($res_certificado, 0, 'codigo'));
					$certificado = trim(pg_result($res_certificado, 0, 'certificado'));
					$faturado    = trim(pg_result($res_certificado, 0, 'faturado'));

					echo "<td width='55' nowrap>";
					if ($login_fabrica == 1) {
						echo "<a href='javascript:void(0)' onclick='abre_certificado($certificado)'>" . $codigo . "</a>";
					} else {
						if ($faturado == 't') {
							echo "<a href='javascript:void(0)' onclick='abre_certificado($certificado)'>" . $codigo . "</a>";
						} else {
							echo $codigo;//CASO NAO SEJA FATURADO APENAS MOSTRA O NUMERO
						}
					}
					echo "</td>";

				} else {

					echo "<td width='55' nowrap>&nbsp;</td>";

				}

			}
			echo "<td nowrap><a href='os_press.php?os=$os' target='_blank'>$posto_codigo$sua_os</a></td>";
			echo "<td nowrap>$data_digitacao</td>";
			echo "<td nowrap align='left'>$produto_referencia - $produto_descricao</td>";
			echo "<td nowrap align='left'>$solucao</td>";
			echo "<td nowrap align='left'>$consumidor_nome</td>";
			echo "<td nowrap align='left'>$consumidor_fone</td>";
			if($login_fabrica==1){
				$sql2 = "SELECT SUM (tbl_os_item.custo_peca) AS total_peca from tbl_os_item
						JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
						WHERE tbl_os_produto.os = $os";
				$res2 = pg_exec($con,$sql2);
				if (pg_numrows($res2)>0) {
					$custo_peca         = pg_result($res2,0,total_peca);
					$custo_peca = number_format($custo_peca, 2, ',', '');
				}else $custo_peca =0;
				echo "<td nowrap align='right'>$custo_peca</td>";
			}
			$total_custo_peca = $total_custo_peca + $custo_peca;
		}
		if ($login_fabrica==1){
			$total_custo_peca = number_format($total_custo_peca, 2, ',', '');
			echo "<tr><td colspan='8'>SUBTOTAL</td>";
			echo "<td align='right'>$total_custo_peca</td></tr>";
		}
		echo "</table>";
	}

	##### PAGINAÇÃO - INÍCIO #####

	// links da paginacao
	echo "<br>";
	echo "<div>";

	if($pagina < $max_links) {
		$paginacao = pagina + 1;
	}else{
		$paginacao = pagina;
	}

	// paginacao com restricao de links da paginacao

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

	##### PAGINAÇÃO - FIM #####

	echo "<br>";
}
?>

<? include "rodape.php" ?>
