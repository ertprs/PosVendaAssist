<?php
header('Content-Type: text/html; charset=windows-1252');
require 'dbconfig.php';
require 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,auditoria";
include "autentica_admin.php";

//  Limpa a string para evitar SQL injection
if (!function_exists('anti_injection')) {
	function anti_injection($string) {
		$a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
		return strtr(strip_tags(trim($string)), $a_limpa);
	}
}
if (!function_exists("file_put_contents") and $_POST['arquivo'] != '') {
	function file_put_contents($filename,$data,$append=false) {
	    $mode = ($append)?"ab":"wb";
// 	    if (!is_writable($filename)) return false;
		$file_resource = fopen($filename,$mode);
		if (!$file_resource===false):
		    system ("chmod 664 $filename");
			$bytes = fwrite($file_resource, $data);
		else:
		    return false;
		endif;
		fclose($file_resource);
		return $bytes;
	}
}

if (count($_POST)) {
	$data_ini		= anti_injection($_POST['data_ini']);
	$data_fim		= anti_injection($_POST['data_fim']);
	$tipo           = anti_injection($_POST['tipo_saida']);
	$arquivo        = anti_injection($_POST['arquivo']);

	if ($data_ini and $data_fim) {
	    $cond_datas.= " tbl_pedido.data BETWEEN '$data_ini' AND '$data_fim 23:59:59' ";
	} else {
	    $msg_erro.= 'Por favor, forneça data de início E data final para o relatório';
	}
	if ($arquivo != '' and $tipo == '') $msg_erro = 'Digitou um nome para o arquivo, mas não selecionou o formato.';

	if (!$arquivo and !$tipo) $tipo = 'xls'; // Quando não pede para gerar arquivo, mostra a tabela na tela

	if ($msg_erro == '') {
		$sql_pedidos = "SELECT DISTINCT TO_CHAR(tbl_pedido.data,'DD/MM/YYYY HH24:MI') AS \"Data Pedido\",
       tbl_pedido.pedido      AS \"Pedido\",  tbl_status_pedido.descricao AS \"Status\",
       tbl_posto.cnpj         AS \"CNPJ\",    TRIM(tbl_posto.nome)        AS \"Razão Social\",
       tbl_os.consumidor_nome AS \"Cliente\", tbl_os.sua_os               AS \"OS\",
       tbl_os_extra.tecnico   AS \"Técnico\", tbl_tabela.descricao        AS \"Tabela de Preço\",
       to_char(tbl_pedido.finalizado,'DD/MM/YYYY HH24:MI') AS \"Data Finalizado\",
       to_char(tbl_pedido.exportado,'DD/MM/YYYY HH24:MI')  AS \"Data Exportado\",
       tbl_tipo_pedido.descricao AS \"Tipo pedido\",
       tbl_condicao.descricao    AS \"Condição\",
       CASE
           WHEN (SELECT sum(tbl_pedido_item.qtde*tbl_pedido_item.preco) FROM tbl_pedido_item
           WHERE tbl_pedido_item.pedido=tbl_os.pedido_cliente AND tbl_pedido_item.peca != 842805)
              IS NULL
           THEN '(Peça(s) sem preço)'
       ELSE
       TO_CHAR(
         (SELECT sum(tbl_pedido_item.qtde*tbl_pedido_item.preco) FROM tbl_pedido_item
           WHERE tbl_pedido_item.pedido=tbl_os.pedido_cliente AND tbl_pedido_item.peca != 842805),
       '9G999G990D99')
       END AS \"Valor Peças\",
      TO_CHAR(
         (SELECT SUM(tbl_os_item.qtde*tbl_os_item.preco)
		 	FROM tbl_os_produto
			JOIN tbl_os_item USING(os_produto)
			WHERE tbl_os_produto.os = tbl_os.os AND tbl_os_item.peca != 842805),
       '9G999G990D99') AS \"Valor Total Peças\",
	   CASE WHEN tbl_os_revenda.os_manutencao IS NOT TRUE THEN
			CASE WHEN (tbl_os_extra.valor_total_hora_tecnica > 0) OR (tbl_os_extra.valor_total_diaria > 0)
				THEN TO_CHAR((tbl_os_extra.valor_total_hora_tecnica + tbl_os_extra.valor_total_diaria),'9G999G990D99')
				WHEN 842805 IN (SELECT peca FROM tbl_pedido_item
					   WHERE tbl_pedido_item.pedido=tbl_os.pedido_cliente AND tbl_pedido_item.peca = 842805)
				THEN TO_CHAR((SELECT preco*qtde AS preco FROM tbl_pedido_item
					   WHERE tbl_pedido_item.pedido=tbl_os.pedido_cliente AND tbl_pedido_item.peca = 842805),'9G999G990D99')
			END
		ELSE TO_CHAR((tbl_os_revenda.valor_total_hora_tecnica + tbl_os_revenda.valor_total_diaria),'9G999G990D99')
	   END																	   AS \"Mão de Obra OS\",
       CASE WHEN tbl_os_revenda.os_manutencao IS TRUE
            THEN TO_CHAR(tbl_os_revenda.regulagem_peso_padrao,'9G999G990D99')
            ELSE TO_CHAR(tbl_os_extra.regulagem_peso_padrao,'9G999G990D99')
	   END																	   AS \"Regulagem\",
       TO_CHAR(tbl_os_extra.certificado_conformidade,'9G999G990D99')           AS \"Certificado\",
       CASE WHEN tbl_os_extra.visita_por_km IS TRUE AND tbl_os_extra.valor_por_km > 0
			THEN TO_CHAR(tbl_os_extra.valor_total_deslocamento,'9G999G990D99')
			WHEN tbl_os_revenda.os_manutencao IS TRUE
			THEN TO_CHAR(tbl_os_revenda.taxa_visita,'9G999G990D99')
			ELSE TO_CHAR(tbl_os_extra.taxa_visita,'9G999G990D99')
       END																	   AS \"Taxa de visita\",
       CASE WHEN pedido_os IS TRUE THEN 'OS' ELSE 'Compra Manual' END          AS \"Pedido OS\",
       CASE WHEN origem_cliente IS TRUE THEN 'Cliente' ELSE 'PTA' END          AS \"Origem\",
       tbl_pedido.obs AS \"Observação\"
FROM   tbl_pedido
    JOIN      tbl_posto            ON tbl_posto.posto = tbl_pedido.posto
    LEFT JOIN tbl_pedido_item      ON tbl_pedido_item.pedido            = tbl_pedido.pedido
    JOIN      tbl_tabela           ON tbl_pedido.tabela                 = tbl_tabela.tabela
    LEFT JOIN tbl_peca             ON tbl_peca.peca                     = tbl_pedido_item.peca
    LEFT JOIN tbl_representante    ON tbl_pedido.representante          = tbl_representante.representante
    LEFT JOIN tbl_status_pedido    ON tbl_status_pedido.status_pedido   = tbl_pedido.status_pedido
    LEFT JOIN tbl_condicao         ON tbl_condicao.condicao             = tbl_pedido.condicao
    JOIN      tbl_tipo_pedido      ON tbl_tipo_pedido.tipo_pedido       = tbl_pedido.tipo_pedido
    LEFT JOIN tbl_os_item          ON tbl_os_item.peca					= tbl_peca.peca
								  AND tbl_os_item.pedido_cliente		= tbl_pedido.pedido
    LEFT JOIN tbl_os_produto       ON tbl_os_produto.os_produto         = tbl_os_item.os_produto
    LEFT JOIN tbl_os_extra         ON tbl_os_extra.os                   = tbl_os_produto.os
    LEFT JOIN tbl_os               ON tbl_os.os				= tbl_os_produto.os
								  AND tbl_os.pedido_cliente	= tbl_pedido.pedido
    LEFT JOIN tbl_os_revenda       ON tbl_os_revenda.os_revenda = tbl_os.os_numero
								  AND tbl_os_revenda.posto		= tbl_os.posto
								  AND tbl_os.fabrica		= $login_fabrica
WHERE $cond_datas
    AND   tbl_pedido.exportado IS NOT NULL
    AND   tbl_pedido.fabrica = $login_fabrica
    AND   tbl_pedido_item.peca != 842805
ORDER BY \"Data Pedido\",\"Pedido\",\"OS\"";
	}
}
/*  'field-call' de pedidos da Filizola	*/

$title = "Relatório de Pedidos";
include "cabecalho.php";
?>
<!--  <link rel="stylesheet" href="../css/tc09_layout.css" type="text/css"> -->
<link rel="stylesheet" href="/mlg/js/jquery-ui.css" type="text/css" />

<script type="text/javascript" language="JavaScript" src="/mlg/js/jquery-1.4.2.js"></script>
<script type="text/javascript" language="JavaScript" src="/mlg/js/jquery-ui.min.js"></script>
<script type="text/javascript" language="JavaScript" src="/mlg/js/jquery.ui.datepicker-pt-BR.js"></script>

<script src='/loja/media/scripts/tinyTableSorter.js' type='text/javascript'></script>

<script type="text/javascript">
	var sorter = new TINY.table.sorter('sorter');
	$(function() {

		$('form[name=consulta_pedidos]').submit(function() {
			$('input[type=image]').attr('disabled','disabled');
		});

		/* Busca por datas */
// 		$.datepicker.setDefaults($.datepicker.regional['pt-BR']);
		$("#data_ini").datepicker({
		    maxDate: '-1',
		    minDate: '-1y',
		    changeMonth: true,
		    dateFormat: 'dd/mm/yy',
		    onSelect: function(dateText) {
		    	$("#data_fim").datepicker('option','minDate',dateText);
			}
		});
		$("#data_fim").datepicker({
		    maxDate: 0,
		    minDate: $('#data_ini').val(),
		    changeMonth: true,
		    dateFormat: 'dd/mm/yy',
		    onSelect: function(dateText) {
		    	$("#data_ini").datepicker('option','maxDate',dateText);
			}
		});
		$('#tipo_saida option[value=<?=$_POST['tipo_saida']?>]').attr('selected','selected');
		$('#msg_erro').click(function() {$(this).hide('fast');});
});
</script>

<style type="text/css">
	form {
		margin: 2em 0 1em 0;
		color: black;
		font-weight:bold;
		font-size: 10.5px;
	}
	fieldset {
	    padding: 0 0 1em 0;
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

	label {display:inline-block;_zoom:1;width: 120px;text-align:right}
	input {width: 128px;text-align:right}
	input#arquivo {text-align:left}
	input#acao {width:auto; height: 16px;position: relative; top: 4px;}

	.erro {
		position: relative;
		width: 640px;
		margin: 1em;
		background-color: #c00;
		color: white;
		font-size: 12px;
		font-weight: bold;
		min-height: 3em;
	    vertical-align: middle;
	    border-radius: 8px;
	    -moz-border-radius: 8px;
	    -webkit-border-radius: 8px;
	    box-shadow: 3px 3px 2px #200000;
	    -moz-box-shadow: 3px 3px 2px #200000;
	    -webkit-box-shadow: 3px 3px 2px #200000;
	    filter:progid:DXImageTransform.Microsoft.DropShadow(color='#200000', offX=3, offY=3,enabled=true,positive='false');
	}
/*  tiny TableSorter */
table#tbl_pedidos {maxwidth: 1024px;border-left:1px solid #c6d5e1; border-top:1px solid #c6d5e1; border-bottom:none; margin:0 auto 15px}
table#tbl_pedidos th {background:url(/loja/media/css/images/header-bg.gif); text-align:left; color:#cfdce7; border:1px solid #fff; border-right:none}
table#tbl_pedidos th h3 {
	font-size:10px;
	padding:5px 8px 6px;
	text-overflow:ellipsis;
	overflow:hidden;
	white-space:nowrap;
	margin: 0;
	color: white;
}
table#tbl_pedidos td {
	padding:1px 6px 3px;
	border-bottom:1px solid #c6d5e1;
	border-right:1px solid #c6d5e1;
	font-size: 9px;
    white-space: nowrap;
    text-overflow: ellipsis;
    -o-text-overflow: ellipsis;
}
table#tbl_pedidos .head h3 {background:url(/loja/media/css/images/sort.gif) 7px center no-repeat; cursor:pointer; padding-left:18px}
table#tbl_pedidos .desc, table#tbl_pedidos .asc {background:url(/loja/media/css/images/header-selected-bg.gif)}
table#tbl_pedidos .desc h3 {background:url(/loja/media/css/images/desc.gif) 7px center no-repeat; cursor:pointer; padding-left:18px}
table#tbl_pedidos .asc h3 {background:url(/loja/media/css/images/asc.gif) 7px  center no-repeat; cursor:pointer; padding-left:18px}
table#tbl_pedidos .head:hover, table#tbl_pedidos .desc:hover, table#tbl_pedidos .asc:hover {color:#fff}
table#tbl_pedidos .evenrow td {background:#fff}
table#tbl_pedidos .oddrow td {background:#ecf2f6}
table#tbl_pedidos td.evenselected {background:#ecf2f6}
table#tbl_pedidos td.oddselected {background:#dce6ee}

#controls {width:750px; margin:0 auto; height:20px}
#perpage {float:left; width:100px}
#perpage select {float:left; font-size:11px}
#perpage span {float:left; margin:2px 0 0 5px}
#navigation {float:left; width:520px; text-align:center}
#navigation img {cursor:pointer}
#text {float:left; width:100px; text-align:right; margin-top:2px}
</style>

	<center>
<?	if ($msg_erro != '') {?>
	<div id='msg_erro' class='erro'><?=$msg_erro?></div>
<?}?>
    <form action="<?=$PHP_SELF?>" name="consulta_pedidos" id="consulta_pedidos" method="post" style='width: 640px'>
		<fieldset>
			<p class='legend'>Parâmetros para a consulta</p>
			<br>
	        <label for="data_ini" class='label'>Data inicial:</label>
			<input class='frm' type="text" name='data_ini' id='data_ini' value='<?=$_POST['data_ini']?>' readonly>
	        <label for="data_fim" class='label'>Data final:</label>
			<input class='frm' type="text" name='data_fim' id='data_fim' value='<?=$_POST['data_fim']?>' readonly>
			<br>
			<hr width='90%'>
			<br>
	        <label for="arquivo" class='label'>Nome do arquivo:</label>
            <input type="text" name="arquivo" id="arquivo" class="frm" value='<?=$_POST['arquivo']?>'
				  title="Digite o nome do arquivo a gerar. A data será adicionada automáticamente.">
	        <label for="tipo_saida" class='label'>Formato:</label>
            <select name="tipo_saida" class="frm" id="tipo_saida" title="Selecione o tipo de arquivo">
             <option value="">Tipo de arquivo</option>
             <option value="xls" selected="selected">Excel (XLS)</option>
             <option value="csv" title='Dados sep. por ponto-e-vírgula'>CSV</option>
            </select>
			<br>
			<hr width='90%'>
			<br>
	        <input type="image" name="acao" id="acao" value="contar" alt='Consultar' src='imagens/btn_consulta.gif' width='64'>
			&nbsp;&nbsp;&nbsp;<a href='<?=$PHP_SELF?>' class='frm' style='margin-bottom:3px'>&nbsp;Limpar&nbsp;</a>
        </fieldset>
	</form>
<?
if (count($_POST)) {
	if ($sql_pedidos) {
// 	echo "<pre>$sql_pedidos</pre>\n";
		$res = @pg_query($con, $sql_pedidos);
		if (is_resource($res)) $num_rows = pg_num_rows($res);
		if ($num_rows) {
// 			$tipo = $_POST['tipo_saida'];
	//		Pega os nomes dos campos para o cabeçalho
			$numfields = @pg_num_fields($res);
			for ($c=0; $c <= $numfields; $c++) {
			    $campo = str_replace("_"," ",@pg_field_name($res, $c));
		 		$campos.= "\t\t<th><h3>$campo</h3></th>\n";
				if ($campo == 'Taxa de visita') $campos.= "\t\t<th><h3>Total OS</h3></th>\n";
				if ($tipo == 'csv') {
					$a_campos_CSV[] = $campo;
					if ($campo == 'Taxa de visita') $a_campos_CSV[] = 'Total OS';
				}
			}

	//	Prepara a saída
			$table = "<table id='tbl_pedidos' align='center'>\n";
			$table.= "<thead>\n".
					 "	<tr>\n".
					 "		".$campos.
					 "	</tr>".
					 "</thead>\n";

			$table.= "\t<tbody>\n";
			for ($r=0; $r < $num_rows; $r++) {
			$table.= "\t<tr>\n";
				$row = @pg_fetch_row($res, $r);
				foreach ($row as $campo => $data) {
					if ($data=='t') $data = "V";
					if ($data=='f') {
						$data = "F";
						$attrs= " style='color:red'";
					}
					if (in_array($data,array("",null,'null'))) {   // Os 'null' viram traço, pra não ficarem em branco
                        $attrs	= " style='color:red'";
                        $data	= " &mdash; ";
					}
					//  Formata CNPJ/CPF
// 					if (is_numeric($data) and (strlen(trim($data))==11 or strlen($data)==14)) {
// 					    $formatoMask = (strlen(trim($data))==11) ? '/(\d{3})(\d{3})(\d{3})(\d{2})/' : '/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/';
// 					    $formato     = (strlen(trim($data))==11) ? '$1.$2.$3-$4' : '$1.$2.$3/$4-$5';
// 					    $data = preg_replace($formatoMask, $formato, $data);
// 					}
					if (preg_match('/[\d{0,3}.]?\d{0,3},\d{2}/',$data) or is_numeric($data) or
						preg_match('/^\d{6,7}-\d{1,2}/',$data)) $attrs.= ' align="right"';  // Alinha números, OS Revenda e moeda à direita
					if (preg_match('/\d{2}[-|\/]\d{2}[-|\/]\d{4} \d{2}:\d{2}/',$data)) $attrs.= ' align="center"'; // Datas centralizado
					$table.= "\t\t<td$attrs>$data</td>\n";
                    if ($campo > 12 and $campo <19) {
						$total += floatval(str_replace(',', '.', $data));
					}
					if ($campo == 18) {
						$table.= "\t\t<td align='right'>".number_format($total, 2)."</td>\n";
						$total = 0;
					}
					unset ($attrs);
				}
				$table.= "\t</tr>\n";
			}
			$table.= "\t</tbody>\n</table>\n";
			if ($tipo=='xls') {  // Tabela HTML, ou falso XLS...
			    $filetype = 'xls';
			}
			$tabela_web = $table;
			if ($tipo=='csv' or $tipo=='tsv') { // Arquivo de texto, com valores separados por ; ou TAB
			    $filetype = 'csv';
			    $table = '';
			    $sep = iif($tipo=='csv',';',"\t");
				if (array_filter(count($a_campos_CSV)) > 0) $camposCSV = implode($sep,$a_campos_CSV)."\n";

				for ($r=0; $r<$numrows; $r++) {
					$row = @pg_fetch_row($res, $r);
						$table .= implode($sep,$row)."\n";
				}
			}

		// 	Rotina para gerar o arquivo (tanto faz o formato) e o link para baixá-lo
			if ($arquivo != '' and $tipo) {
				$arquivo = "{$arquivo}_{$login_admin}_".date('Y-m-d').".$filetype";
				$filepath= './xls/'.$arquivo;
				if (file_exists($filepath)) unlink($filepath);
		// 		if (file_exists("./".$arquivo)) system("rm -f ./".$nomeusr[0]."*");
				if (!is_bool(file_put_contents($filepath, $table))) {
					echo "<div id='link_arquivo'>".
						 "<p>Para baixar o arquivo em formato $tipo, clique com o botão direito sobre o nome do arquivo e selecione 'Salvar como...': ".
						 "<a href='$filepath' target='_blank'>$arquivo</a>".
						 "</p>";
						 "</div>\n<hr width='75%' color='#ffffff'>\n";
				} else {
					echo "Erro ao gerar o arquivo <b><u>$arquivo</u></b>! Tente novamente ou ".
						 "<a href='mailto:helpdesk@telecontrol.com.br'>contate com a Telecontrol</a>";
				}
			}

		//  Mostra o resultado, seja qual for o formato selecionado, mostra uma tabela.
			echo $tabela_web;

		//	Adiciona paginador para o formato tabela
			if ($tipo == 'xls' and $num_rows >= 30) {?>
			<div id="controls">
				<div id="perpage">
					<select onchange="sorter.size(this.value)">
					<option value="5">5</option>
						<option value="10">10</option>
						<option value="20" selected="selected">20</option>
						<option value="50">50</option>
						<option value="100">100</option>
					</select>
					<span>filas por pág.</span>
				</div>
				<div id="navigation">
					<img width="16" height="16" alt="Pág. 1"	src="/loja/media/css/images/first.gif"		onclick="sorter.move(-1,true)" />
					<img width="16" height="16" alt="Pág. Ant."	src="/loja/media/css/images/previous.gif"	onclick="sorter.move(-1)" />
					<img width="16" height="16" alt="Próx. Pg."	src="/loja/media/css/images/next.gif"		onclick="sorter.move(1)" />
					<img width="16" height="16" alt="Últ. Pág."	src="/loja/media/css/images/last.gif"		onclick="sorter.move(1,true)" />
				</div>
				<div id="text">Pág. <span id="currentpage"></span> de <span id="pagelimit"></span></div>
			</div>
	    <script type="text/javascript">
			sorter.head		= 'head'; //header class name
			sorter.asc		= 'asc'; //ascending header class name
			sorter.desc		= 'desc'; //descending header class name
			sorter.even		= 'evenrow'; //even row class name
			sorter.odd		= 'oddrow'; //odd row class name
			sorter.evensel	= 'evenselected'; //selected column even class
			sorter.oddsel	= 'oddselected'; //selected column odd class
			sorter.pagesize	= 20; //toggle for pagination logic
			sorter.currentid= 'currentpage'; //current page id
			sorter.limitid	= 'pagelimit'; //page limit id
			sorter.paginate = true; //toggle for pagination logic
			sorter.init('tbl_pedidos',0);
	    </script>
	<?		}
		} else {?>
	<div id='msg_erro' class='erro'>Não há pedidos no periodo de <?=$data_ini?> &mdash; <?=$data_fim?></div>
<?		}
	}
}
include "rodape.php";
?>
