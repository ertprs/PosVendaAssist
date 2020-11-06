<?php
header('Content-Type: text/html; charset=windows-1252');
require 'dbconfig.php';
require 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,auditoria";
include "autentica_admin.php";

/*  Query para ver quais postos tem quantas OS em aberto:
SELECT posto,tbl_posto.cnpj,count(os) FROM tbl_os JOIN tbl_posto USING(posto) WHERE data_fechamento IS NULL and fabrica=80 GROUP BY posto,cnpj;

	Query para pegar as referências dos produtos das OS em aberto da Precision...
SELECT DISTINCT tbl_produto.referencia FROM tbl_os JOIN tbl_produto USING (produto) WHERE data_fechamento IS NULL AND tbl_os.fabrica = 80
*/

/*
OS ABAIXO DE 5 DIAS - COM PEDIDO DE PEÇAS (barra em verde claro)#
OS ABAIXO DE 5 DIAS - SEM PEDIDOS DE PEÇAS (barra em verde escuro)#
OS ABAIXO DE 15 DIAS - COM PEDIDO DE PEÇAS (barra em amarelo)#
OS ABAIXO DE 15 DIAS - SEM PEDIDOS DE PEÇAS (barra em laranja)#
OS ABAIXO DE 25 DIAS - COM PEDIDO DE PEÇAS (barra em azul)#
OS ABAIXO DE 25 DIAS - SEM PEDIDOS DE PEÇAS (barra em lilás)#
OS IGUAL OU ACIMA DE 30 DIAS - COM PEDIDO DE PEÇAS (barra em vermelho)#
OS IGUAL OU ACIMA DE 30 DIAS - SEM PEDIDOS DE PEÇAS (barra em marrom)#
00D07F|F0F07F|0000FF|FF0000,008040|FF8000|7F00F0|7F3000

Assim como disponibilizar essa informação no LOGIN ADMINISTRADOR, com opção de pesquisa por posto, por modelo ou TODAS.
*/
$title = "Gráfico de OS em aberto por periodo";
include "cabecalho.php";
?>
<!--  <link rel="stylesheet" href="../css/tc09_layout.css" type="text/css"> -->
<link rel="stylesheet" href="js/jquery.autocomplete.css" type="text/css" />

<script src="js/jquery-1.3.2.js" type="text/javascript" language="JavaScript"></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<script type="text/javascript">
	$(function() {
		function formatItem(row) {
			return row[2] + " - " + row[1];
		}

		function formatResult(row) {
			return row[0];
		}

		$('.oculto').hide();
		
		$('form').submit(function() {
			$('input[type=image]').attr('disabled','disabled');
		});

		$("#codigo_posto").autocomplete("comunicado_produto.php?tipo_busca=posto&busca=codigo", {
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
		$("#posto_nome").autocomplete("comunicado_produto.php?tipo_busca=posto&busca=nome", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[1];}
		});

		$("#posto_nome").result(function(event, data, formatted) {
			$("#codigo_posto").val(data[2]) ;
		});

		/* Busca por Produto */
		$("#descricao").autocomplete("comunicado_produto.php?tipo_busca=produto&busca=nome", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[1];}
		});

		$("#descricao").result(function(event, data, formatted) {
			$("#referencia").val(data[2]) ;
		});

		/* Busca pelo Nome */
		$("#referencia").autocomplete("comunicado_produto.php?tipo_busca=produto&busca=codigo", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[2];}
		});

		$("#referencia").result(function(event, data, formatted) {
			$("#descricao").val(data[1]) ;
		});

		$('.toggle_data').click(function () {
		    var dias	= $(this).attr('dias');
		    var posto	= $(this).attr('posto');
		    var produto	= $(this).attr('produto');
		    var item = "#data_"+dias;
		    if ($(item).html().length > 30) {
				$(item).toggle('normal');
			} else{
				$(item).html('<p>Aguarde...</p>').show('normal');
// 		alert ("Consultando OS de até "+dias+" dias... Posto "+posto+", Produto "+produto);
		        $.get('consulta_os_aberto_ajax.php',
				   {'ajax'		:'consulta',
					'dias'		: dias,
					'posto' 	: posto,
					'produto'   : produto
					},
					function(data) {
// 				    alert (data);
		        	    if (data == 'ko' || data == undefined) {
						    $(item).text('Erro ao consultar as OS. Tente em alguns minutos.');
						} else if (data == 'NO RESULTS' || data.indexOf('<p>') != 0) {
						    $(item).html('').hide('fast');
						} else {
							$(item).html(data).show('normal');
						}
		        });
			}
		});
	});
</script>

<style type="text/css">
/*  Tabela de resumo de OS para a Precision	*/
	table#resumoOS {
		color: #333;
		width: 480px;
		margin:1em 0;
		padding-top: 2px;
		background-color:#FFF;
		font: normal normal normal 12px normal Verdana,Arial,Helvetiva,sans-serif;
		border-collapse: separate;
		border-spacing: 3px;
		border: 2px solid #d2e4fc;
		border-radius: 6px;
		-moz-border-radius: 6px;
	    box-shadow: 3px 3px 1px #444;
	    -moz-box-shadow: 3px 3px 1px #444;
	    -webkit-box-shadow: 3px 3px 1px #444;
	}
	table#resumoOS thead {background-color: #485989;color: white}
	table#resumoOS tr:nth-child(4n+3) {
		background-color: #eee;
		cursor: default;
	}
	table#resumoOS tr.bold td {
		font-weight:bold;
		cursor: default;
	}
	table#resumoOS td {
		border: 1px dotted #aaa;
		text-align:center;
		cursor: s-resize;
	}
	table#resumoOS td a {
		text-align: center;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: xx-small;
		font-weight: normal;
	    text-decoration: none;
		color:#596d9b;
	}
	table#resumoOS td p {font: normal normal 10px Verdana, Geneva, Arial, Helvetica, sans-serif;}
	table#resumoOS td a:hover {color: #405080;text-decoration: underline}
	div.oculto {text-align: left;padding: 8px 16px;background-color: #f0f0fa;}

	form {
		margin: 2em 0 1em 0;
		color: black;
		font-weight:bold;
		font-size: 10.5px;
	}
	fieldset {
	    padding: 0 0 0.5em 0;
		border: 1px solid #d2e4fc;
		position:relative;
	}
	form fieldset p.legend {
	    position:relative;
	    display: block;
	    text-align: center;
	    margin: 0;
	    padding:0;
	    padding-top: 5px;
	    top: -10px;
	    left: 0;
	    width: 100%;
		height: 2em;
	    background-image: url(imagens_admin/azul.gif);
	    color: white;
	    font-size: 1.1em;
	    font-weight: bold;
	}

	label {display:inline-block;_zoom:1;width: 20%;text-align:right}

	img#gChart {
		border: 2px solid #5989FF;
		margin:1em 0;
		padding: 0 0.7em;
		background-color: #CDDDFF;
		background-image: -moz-linear-gradient(top, #CDDDFF, #EDEDFF);
	    background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#CDDDFF), to(#EDEDFF));
		-pie-background: linear-gradient(top, #CDDDFF, #EDEDFF);
		border-radius:8px;
		-moz-border-radius:8px;
	    box-shadow: 0 0 8px #444;
	    -moz-box-shadow:  0 0 8px #444;
	    -webkit-box-shadow: 0 0 8px #444;
behavior: url(/mlg/js/PIE.php);
	}
</style>

	<center>
    <form action="<?=$PHP_SELF?>" name="contar_os" method="post" style='width: 640px'>
		<fieldset>
		<p class='legend'>Parâmetros para a consulta</p>
		<br>
        <label for="codigo_posto">C&oacute;digo do Posto:</label>
		<input class='frm' type="text" name='codigo_posto' id='codigo_posto' value='<?=$_POST['codigo_posto']?>'>
        <label for="nome">Nome do Posto:</label>
		<input class='frm' type="text" name='nome' id='posto_nome' value='<?=$_POST['nome']?>'>
		<br>
		<br>
		<hr width='90%'>
		<br>
        <label for="referencia">Ref. do produto:</label>
		<input class='frm' type="text" name='referencia' id='referencia' value='<?=$_POST['referencia']?>'>
        <label for="descricao">Descri&ccedil;&atilde;o:</label>
		<input class='frm' type="text" name='descricao' id='descricao' value='<?=$_POST['descricao']?>'>
		<br>
		<br>
		<hr width='90%'>
		<br>
        <input type="image" name="acao" value="contar" alt='Consultar' src='imagens/btn_consulta.gif' width='64'>
        </button>
		&nbsp;&nbsp;&nbsp;<a href='<?=$PHP_SELF?>' class='frm' style='margin-bottom:3px'>&nbsp;Limpar&nbsp;</a>
        </fieldset>
	</form>
<?
if (count($_POST)) {
	$codigo_posto	= $_POST['codigo_posto'];
	$referencia     = $_POST['referencia'];
	$descricao      = $_POST['descricao'];

	$anterior = 0;
	$os_dias = Array();
	if ($codigo_posto or $cnpj) {
// 		if ($cnpj and !ValidateBRTaxID($cnpj)) {
// 			$cnpj = "";  // apaga o CNPJ se não é válido
// 			$msg_erro = "CNPJ inválido!";
// 		}
		$cond_cp	= ($codigo_posto)?"codigo_posto = '$codigo_posto'":"";
		if ($cnpj) {
			$cond_cnpj	= ($cond_cp!="")?" AND ":"";
			$cond_cnpj .= "cnpj = '$cnpj'";
		}
		if ($cond_cp or $cond_cnpj) {
		    $cnpj = preg_replace("/\D/","",$cnpj);
		    $sql = "SELECT posto,nome FROM tbl_posto_fabrica JOIN tbl_posto USING(posto) WHERE $cond_cp $cond_cnpj AND fabrica=$login_fabrica";
			$res = pg_query($con,$sql);
			if(is_resource($res)) {
			    if (pg_num_rows($res)==1) {
					$posto = pg_fetch_result($res,0,posto);
					$razao_social = pg_fetch_result($res,0,nome);
				}
			}
			if (isset($posto)) {
				$cond_posto = "AND posto	= $posto";
			} else {
				$msg_erro = "Posto não localizado! Confira o código do posto ($codigo_posto) ou o CNPJ($cnpj) fornecidos.";
			}
		}
	}
	if ($referencia or $descricao) {
        $sql = "SELECT produto, referencia FROM tbl_produto JOIN tbl_linha USING(linha) WHERE ";
		$sql.= ($descricao)?"descricao ILIKE '$descricao%'" : "";
		$sql.= ($descricao and $referencia)?" AND " : "";
        $sql.= ($referencia) ? "referencia = '$referencia'" : "";
        $sql.= " AND fabrica = $login_fabrica";
// echo "Consulta: <pre>$sql</pre>\n";
		$res = pg_query($con,$sql);
		if(is_resource($res)) {
		    if (pg_num_rows($res)==1) {
				$produto= pg_fetch_result($res,0,produto);
				$db_ref = pg_fetch_result($res,0,referencia);
			}
		}
		if (isset($produto)) {
			$cond_produto = "AND tbl_os.produto = $produto";
		} else {
			$msg_erro.= "Produto não localizado! Confira a referência ($referencia) ou a descrição ($descricao) fornecidas.";
		}
	}
	if ($msg_erro and count($_POST)>2) echo "<div class='erro'>$msg_erro</div>\n";
    for ($i=5; $i<36; $i+=10) {
		$sql = "SELECT DISTINCT count(posto),os,count(os_produto) AS qtde_itens
					    FROM tbl_os
					    LEFT JOIN tbl_os_produto USING(os)
					  WHERE fabrica	= $login_fabrica
					    $cond_posto
					    $cond_produto
					    AND data_fechamento IS NULL
					    AND tbl_os.excluida IS NOT TRUE
					    AND data_abertura::date BETWEEN current_date - INTERVAL '$i days' AND current_date - INTERVAL '$anterior days'
					  GROUP BY posto,os
		";

//  Mais de 30 dias...:
		if ($anterior==26) {
			$sql = "SELECT DISTINCT count(posto),os,count(os_produto) AS qtde_itens
					    FROM tbl_os
					    LEFT JOIN tbl_os_produto USING(os)
					  WHERE fabrica	= $login_fabrica
					    $cond_posto
					    $cond_produto
					    AND data_fechamento IS NULL
					    AND tbl_os.excluida IS NOT TRUE
					    AND data_abertura::date < current_date-INTERVAL '25 days'
					  GROUP BY posto,os
			";
		}
		$res = pg_query($con, $sql);
		$os_dias[$i]["total"] = pg_num_rows($res);
// 		if ($os_dias[$i]["total"] == 0) continue;
		$num_row = 0;
        while (is_array($row = @pg_fetch_assoc($res, $num_row++))) {
		    $os_dias[$i]["sem_pecas"] += intval(($row['qtde_itens']=="0"));
        }
        if ($os_dias[$i]["total"]==0) $os_dias[$i]["sem_pecas"] = 0;
        $os_dias[$i]["com_pecas"] = $os_dias[$i]["total"] - $os_dias[$i]["sem_pecas"];
		$anterior = $i + 1;
    }
?>
	<table id='resumoOS' align='center'>
	    <caption>Posto: <?=$razao_social?>&nbsp;&nbsp;|&nbsp;&nbsp;Produto: <?=$db_ref?></caption>
		<thead>
		<tr>
			<th>Até...</th>
			<th>Sem peças</th>
			<th>Com pedido</th>
			<th>Total</th>
		</tr>
		</thead>
		<tbody>
<?
	$anterior= 0;
	foreach ($os_dias as $dias => $dados) {
		echo "\t<tr title='Clique para visualizar as OS'>\n";
		echo "\t\t<td class='toggle_data' dias='$dias' posto='$posto' produto='$produto'>";
		echo ($dias==35)?"> 25":"De $anterior até $dias";
		echo " dias</td>\n";
		echo "\t\t<td class='toggle_data' dias='$dias' posto='$posto' produto='$produto'>{$dados['sem_pecas']}</td>\n";
		echo "\t\t<td class='toggle_data' dias='$dias' posto='$posto' produto='$produto'>{$dados['com_pecas']}</td>\n";
		echo "\t\t<td class='toggle_data' dias='$dias' posto='$posto' produto='$produto'>{$dados['total']}</td>\n";
		echo "\t</tr>\n";
		echo "\t<tr id='fila_data_$dias'>\n";
		echo "<td colspan='4'><div class='oculto' id='data_$dias'></div></td></tr>\n";
		$total	+= $dados['total'];
		$sem    += $dados['sem_pecas'];
		$com    += $dados['com_pecas'];
		$anterior = $dias + 1;
	}
?>		<tr class='bold'>
			<td>TOTAIS:</td>
			<td><?=$sem?></td>
			<td><?=$com?></td>
			<td><?=$total?></td>
		</tr>
		</tbody>
	</table>
<?
	$os_dias['t']['total'] = $total;
	$os_dias['t']['sem_pecas'] = $sem;
	$os_dias['t']['com_pecas'] = $com;
//  Agora, muntar a query para o GoogleChart...
	foreach ($os_dias as $dias => $dados) {
	    $a_data_sem[] = $dados['sem_pecas'];
	    $a_data_com[] = $dados['com_pecas'];
	    $a_totais[]   = $dados['total'];
	}
	$chart_data = implode(",",$a_data_sem);
	$chart_data.= "|".implode(",",$a_data_com);
	$max = max($com,$sem);
// 	$max = strval(substr($max,0,-1)+1)*10;
	$max_pos= (strlen($max)<3) ? 1: strlen($max)-2;
// 	$max	= ($max<10) ? 10 : intval(intval(($max)/pow(10,strlen($max)-1))+1)*pow(10,strlen($max)-1);
	if ($max < 10) {
		$max = 10;
	} else {
	    $mais   = intval($max_pos > 1);
		$max    = (ceil(($max / pow(10,$max_pos)))+$mais) * pow(10,$max_pos);
	}
	$chart_height = 100*strlen($max);

	$chart = "http://chart.apis.google.com/chart?";
	$chart.= "chs=640x".$chart_height."&cht=bvg&chbh=r,0.2,0.8&";		//tipo e tamanho
	$chart.= "chco=00D07F|F0F07F|0000FF|FF0000|991111,008040|FF8000|7F00F0|7F3000|2F2F2F&";		//cores das barras
	$chart.= "chf=bg,lg,60,CDDDFF,1,EDEDFF,0&";	//cor de fundo
	$chart.= "chtt=OS+em+aberto|(Sem+pedido+e+Com+pedido)&";   //  Título da imagem
	$chart.= "chxt=x,y,r&chxl=0:|At&#233;+5+dias|6-15+dias|16-25+dias|Mais+de+25+dias|Total|&";
	$chart.= "chdl=&lt;5+Dias+Sem+pe&#231;a|6-15+Dias+Sem+pe&#231;a|16-25+Dias+Sem+pe&#231;a|&gt;25+Dias+Sem+pe&#231;a|Total+Sem+pe&#231;as|".
				  "&lt;5+Dias+Com+pe&#231;a|6-15+Dias+Com+pe&#231;a|16-25+Dias+Com+pe&#231;a|&gt;25+Dias+Com+pe&#231;a|Total+Com+pe&#231;as&".
				  "chdlp=t&";	//	Legenda de cores...
	$chart.= "chm=N*f0*+OS,000000,0,-1,12|N*f0*+OS,000099,1,-1,12&";
//	Legenda de cores... "chdl=Sem+pedido|Com+pedido&chdlp=b&"
	$chart.= "chd=t:$chart_data&chds=0,$max".
			 "&chxr=1,0,$max|2,0,$max";

?>
</center>
<p>&nbsp;</p>
	<img alt='Gráfico de OS em aberto' id='gChart' src='<?=$chart?>' alt='Resumo de OS em aberto'>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<?}
include "rodape.php";
?>
