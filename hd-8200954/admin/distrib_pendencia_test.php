<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';
include "monitora.php";
$layout_menu = "gerencia";
$title = "Peças Pendentes no Distribuidor";

include 'cabecalho.php';

?>
<? include "javascript_calendario_new.php"; //adicionado por Fabio 27-09-2007 ?>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>

<script language='javascript'>
function carregaDados(peca,tipo,linha,div) {
	var mostra= document.getElementById(div);
	$.ajax({
		type:'GET',
		url: 'distrib_pendencia_detalhe.php',
		data: 'peca=' +peca+'&tipo=' +tipo+'&linha=' +linha,
		beforeSend: function(){
			$(mostra).html("&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='js/loadingAnimation.gif'>").toggle();
		},
		complete: function(resposta){
			resposta_array = resposta.responseText.split("|");
			linha = resposta_array [0];
			$('div[rel=resultado]').hide();
			$(mostra).html(resposta_array[1]).show();
		}
	});
}

</script>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 11px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}

.Erro {
	text-align: center;
	font-family: Arial;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #FF0000;
}

.Conteudo {
	text-align: left;
	font-family: Arial;
	font-size: 11px;
	font-weight: normal;
}

.Conteudo2 {
	text-align: center;
	font-family: Arial;
	font-size: 11px;
	font-weight: normal;
}

</style>

<?
flush();

if($btn_acao=="Consultar"){
	if((strlen($data_inicial) > 0 AND $data_inicial!="dd/mm/aaaa") AND (strlen($data_final)>0 AND $data_final!="dd/mm/aaaa")){
		if (strlen($msg_erro) == 0) {
			$fnc            = @pg_query($con,"SELECT fnc_formata_data('$data_inicial')");
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro = pg_errormessage ($con) ;
			}

			if (strlen($msg_erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
		}

		if (strlen($erro) == 0) {
			if (strlen($msg_erro) == 0) {
				$fnc = @pg_query($con,"SELECT fnc_formata_data('$data_final')");
					if (strlen ( pg_errormessage ($con) ) > 0) {
					$erro = pg_errormessage ($con) ;
				}
				if (strlen($msg_erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
			}
		}
	}else{
		#HD 43695
		#$msg_erro = " Informe a data para pesquisa";
	}

	if(strlen($codigo_posto)>0){
		$sql = "SELECT posto
				FROM tbl_posto_fabrica
				WHERE codigo_posto = '$codigo_posto'
				AND fabrica = $login_fabrica";
		$res = @pg_query($con,$sql);
		if(pg_numrows($res)>0)$posto = pg_result($res,0,0);
	}

	if(strlen($referencia)>0){
		$sql = "SELECT peca
				FROM tbl_peca
				WHERE referencia = '$referencia'
				AND   fabrica    = $login_fabrica";
		$res = @pg_query($con,$sql);
		if(pg_numrows($res)>0)$peca = pg_result($res,0,0);
	}else{
		#$msg_erro = " Informe a peça para pesquisa";
	}
}

if(strlen($msg_erro)>0){
	echo "<table width='700' border='0' cellpadding='5' cellspacing='1' align='center'>";
		echo "<tr>";
			echo "<td class='Erro'>$msg_erro</td>";
		echo "</tr>";
	echo "</table>";
}

?>

<?
$tipo = $_POST['tipo'];

#HD 132147
if($tipo == 'garantia'){
	$cond = " AND tbl_pedido.tipo_pedido=132 ";
}elseif($tipo == 'faturado'){
	$cond = " AND tbl_pedido.tipo_pedido=131 ";
}else{
	$cond = "";
}

$sql = "SELECT	tbl_peca.peca       ,
				tbl_peca.referencia ,
				tbl_peca.descricao  ,
				x.qtde_pendente     ,
				(SELECT qtde FROM tbl_posto_estoque WHERE posto = 4311 AND peca = x.peca) AS estoque
		FROM tbl_peca
		JOIN (
			SELECT SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada_distribuidor) AS qtde_pendente, tbl_pedido_item.peca
			FROM tbl_pedido
			JOIN tbl_pedido_item USING (pedido)
			WHERE tbl_pedido.distribuidor = 4311
			AND   tbl_pedido.fabrica = 51
			AND   tbl_pedido.status_pedido <> 13
			$cond
			GROUP BY tbl_pedido_item.peca
		) x ON tbl_peca.peca = x.peca
		WHERE x.qtde_pendente > 0
		ORDER BY x.qtde_pendente DESC ;";
$res = pg_query ($con,$sql);

?>

<br>
&nbsp;
<br>

<center>
Para ver os pedidos de cada peça, clique na linha
<br>
<form name='form_pend' action='<?=$PHP_SELF?>' method='POST'>
	<input type='radio'name='tipo' value='garantia' id='garantia'><label for='garantia'>Garantia</label>
	<input type='radio'name='tipo' value='faturado' id='faturado'><label for='faturado'>Faturado</label><br>
	<input type='submit' name='envia' value='Atualizar Lista'>
</form>
</center>

<br>

<? echo (isset($tipo)) ? "<h1>".strtoupper($tipo)."</h1>" : "";?>

<table width='600' class='Conteudo' style='background-color: #ffffff' border='1' cellpadding='5' cellspacing='0' align='center'>
	<tr class='Titulo' background='imagens_admin/azul.gif'>
		<td colspan='4'>Peças Pendentes no Distribuidor</td>
	</tr>

	<tr class='Titulo' background='imagens_admin/azul.gif'>
		<td nowrap >Peça</td>
		<td nowrap >Descrição</td>
		<td nowrap >Qtde Total</td>
		<td nowrap >Estoque</td>
	</tr>

	<?
	$resultados = pg_fetch_all($res);

	flush();
	$data = date ("d/m/Y H:i:s");
	
	$arquivo_nome     = "relatorio-pendencia-peca-$login_admin.xls";
	$path             = "/www/assist/www/admin/xls/";
	$path_tmp         = "/tmp/";

	$arquivo_completo     = $path.$arquivo_nome;
	$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

	echo `rm $arquivo_completo_tmp `;
	echo `rm $arquivo_completo `;

	$fp = fopen ($arquivo_completo_tmp,"w");

	fputs ($fp,"<html>");
	fputs ($fp,"<head>");
	fputs ($fp,"<title>RELATÓRIO DE MÃO-DE-OBRA DEWALT - $data");
	fputs ($fp,"</title>");
	fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
	fputs ($fp,"</head>");
	fputs ($fp,"<body>");
	fputs ($fp,"<table width='600' class='Conteudo' style='background-color: #ffffff' border='1' cellpadding='5' cellspacing='0' align='center'>");
	fputs ($fp,"<tr class='Titulo' background='imagens_admin/azul.gif'>");
	fputs ($fp,"<td colspan='4'>Peças Pendentes no Distribuidor</td>");
	fputs ($fp,"</tr>");
	fputs ($fp,"<tr class='Titulo' background='imagens_admin/azul.gif'>");
	fputs ($fp,"<td nowrap >Peça</td>");
	fputs ($fp,"<td nowrap >Descrição</td>");
	fputs ($fp,"<td nowrap >Qtde Total</td>");
	fputs ($fp,"<td nowrap >Estoque</td>");
	fputs ($fp,"</tr>");
	fputs ($fp,"<tbody>");
	
	foreach ($resultados as $resultado_key => $resultado_valor) {

		$peca = $resultado_valor['peca'];

		echo "<tr onMouseOver='this.style.cursor=\"pointer\" ; this.style.background=\"#cccccc\"'  onMouseOut='this.style.backgroundColor=\"#ffffff\" '  onClick=\"carregaDados('$peca','$tipo','$resultado_key','div_detalhe_$resultado_key'); \" >";

		echo "<td>";
		echo $resultado_valor['referencia'];
		echo "</td>";

		echo "<td >";
		echo $resultado_valor['descricao'];
		echo "</td>";

		echo "<td align='right'>";
		echo $resultado_valor['qtde_pendente'];
		echo "</td>";

		echo "<td align='right'>";
		echo $resultado_valor['estoque'];
		echo "</td>";
		echo "</tr>";

		echo "<tr><td colspan='4'>";
		echo "<div id='div_detalhe_$resultado_key' rel='resultado'></div>";
		echo "</td></tr>";

		fputs($fp, "<tr>");

		fputs($fp, "<td>");
		fputs($fp, $resultado_valor['referencia']);
		fputs($fp, "</td>");

		fputs($fp, "<td >");
		fputs($fp, $resultado_valor['descricao']);
		fputs($fp, "</td>");

		fputs($fp, "<td align='right'>");
		fputs($fp, $resultado_valor['qtde_pendente']);
		fputs($fp, "</td>");

		fputs($fp, "<td align='right'>");
		fputs($fp, $resultado_valor['estoque']);
		fputs($fp, "</td>");
		fputs($fp, "</tr>");
	}
	fputs ($fp,"</tbody>");
	fputs ($fp, "</table>");
	fputs ($fp, " </body>");
	fputs ($fp, " </html>");
	
	echo ` cp $arquivo_completo_tmp $path `;

	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;
	$resposta .= "<br>";
	$resposta .="<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
	$resposta .="<tr>";
	$resposta .= "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/$arquivo_nome' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
	$resposta .= "</tr>";
	$resposta .= "</table>";
	echo $resposta;
	?>

</table>

<?
if($btn_acao=="Consultar" AND strlen($msg_erro) == 0){
	if(strlen($posto) > 0) $cond_1 = "AND tbl_os.posto      = $posto";
	if(strlen($peca)  > 0) $cond_2 = "AND tbl_os_item.peca  = $peca";

	$sql = "SELECT DISTINCT tbl_os.os                                     ,
			tbl_os.sua_os                                                 ,
			tbl_os.posto                                                  ,
			to_char (tbl_os.data_abertura,'DD/MM/YY') AS data_abertura    ,
			tbl_os.data_abertura                      AS abertura         ,
			tbl_faturamento.nota_fiscal
			FROM tbl_os
	JOIN tbl_os_produto    USING (os)
	JOIN tbl_os_item       USING (os_produto)
	JOIN tbl_peca          USING (peca)
	LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_os_item.pedido AND tbl_faturamento_item.peca = tbl_os_item.peca
	LEFT JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_os_item.pedido AND tbl_pedido_item.peca = tbl_os_item.peca
	LEFT JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
	WHERE tbl_os.fabrica = $login_fabrica
	";
	if (strlen($aux_data_inicial)>0 AND strlen($aux_data_final)>0) {
		$sql .= " AND   tbl_os.data_abertura BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
	}
	$sql .= "
	AND   tbl_faturamento.nota_fiscal IS NULL
	AND   tbl_os_item.pedido          IS NOT NULL
	AND   tbl_pedido_item.qtde_cancelada <> tbl_pedido_item.qtde
	$cond_1
	$cond_2
	ORDER BY abertura ASC";


	// ##### PAGINACAO ##### //
	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";

	require "_class_paginacao.php";

	// definicoes de variaveis
	$max_links = 11;				// mï¿½imo de links ï¿½serem exibidos
	$max_res   = 50;				// mï¿½imo de resultados ï¿½serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o nmero de pesquisas (detalhada ou nï¿½) por pï¿½ina

	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

	// ##### PAGINACAO ##### //

	if (pg_numrows($res) > 0) {

		echo "<br><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='750'>";
		echo "<tr class='Titulo' height='25' background='imagens_admin/azul.gif'>";
		echo "<td >OS</td>";
		echo "<td >ABERTURA</td>";
		echo "<td >POSTO</td>";
		echo "</tr>";

		for ($i=0; $i<pg_num_rows($res); $i++){

			$os             = trim(pg_result($res,$i,os));
			$posto          = trim(pg_result($res,$i,posto));
			$sua_os         = trim(pg_result($res,$i,sua_os));
			$data_abertura  = trim(pg_result($res,$i,data_abertura));

			if(strlen($posto)>0){
				$sqlP = "SELECT nome AS posto_nome ,
								codigo_posto
						 FROM  tbl_posto_fabrica
						 JOIN  tbl_posto USING(posto)
						 WHERE tbl_posto_fabrica.posto   = $posto
						 AND   tbl_posto_fabrica.fabrica = $login_fabrica ";
				$resP = pg_query($con, $sqlP);

				if(pg_numrows($resP) > 0){
					$codigo_posto   = trim(pg_result($resP,0,codigo_posto));
					$posto_nome     = trim(pg_result($resP,0,posto_nome));
				}
			}

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';

			echo "<tr class='Conteudo2'>";
			echo "<td bgcolor='$cor' ><A HREF='os_press.php?os=$os' target='_blank'>$sua_os</A></td>";
			echo "<td bgcolor='$cor' >$data_abertura</td>";
			echo "<td bgcolor='$cor' align='left'>$codigo_posto - $posto_nome</td>";
			echo "</tr>";
		}
		echo "</table>";
	}else{
		echo "<P style='font-size: 12px; text-align=center; '>Nenhum resultado encontrado</P>";
	}

	### Pï¿½PAGINACAO###
	echo "<table border='0' align='center'>";
	echo "<tr>";
	echo "<td colspan='9' align='center'>";

	// ##### PAGINACAO ##### //

	// links da paginacao
	echo "<br>";

	if($pagina < $max_links) {
		$paginacao = pagina + 1;
	}else{
		$paginacao = pagina;
	}

	// paginacao com restricao de links da paginacao

	// pega todos os links e define que 'Prï¿½ima' e 'Anterior' serï¿½ exibidos como texto plano
	$todos_links		= $mult_pag->Construir_Links("strings", "sim");

	// funï¿½o que limita a quantidade de links no rodape
	$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

	for ($n = 0; $n < count($links_limitados); $n++) {
		echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
	}



	$resultado_inicial = ($pagina * $max_res) + 1;
	$resultado_final   = $max_res + ( $pagina * $max_res);
	$registros         = $mult_pag->Retorna_Resultado();

	$valor_pagina   = $pagina + 1;
	$numero_paginas = intval(($registros / $max_res) + 1);

	if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

	if ($registros > 0){
		echo "<br>";
		echo "<font size='2'>Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.</font>";
		echo "<font color='#cccccc' size='1'>";
		echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
		echo "</font>";
		echo "</div>";
	}
	// ##### PAGINACAO ##### //
	echo "</td>";
	echo "</tr>";

	echo "</table>";

}

include 'rodape.php';
?>
