<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

if (!function_exists('getAttachLink')) {
	function getAttachLink($arquivo) { // BEGIN function getAttachLink

		$tipo_arquivo = array(
			'imagens'	=> array(
				'ext'	=> '/gif|jpg|png|bmp$/',
				'ico'   =>  'image.ico',   /*  Imagen_PNG.ico  */
				'desc'  => 'click.ver.imagem',
				'acao'	=> "<a href='%s' title='%s' target='_blank' target='_blank'>Visualizar imagem&nbsp;<!--%s--><img src='./imagens/%s'></a> \n"),
			'docs'		=> array(
				'ext'	=> '/doc|docx|odt|ppd|odp$/',
				'desc'  => 'click.ver.doc.online',
				'ico'   =>  'Text_Document.ico',
				'acao'	=> "<a href='https://docs.google.com/viewer?url=%s' title='%s' target='_blank'>Abrir documento</a> \n".
						   "<a href='%s' title='Baixar' target='_blank'>&nbsp;<img src='./imagens/%s'></a>"),
			'pdf'		=> array(
				'ext'	=> '/pdf$/',
				'desc'  => 'click.ver.doc.pdf',
				'ico'   =>  'Oficina_PDF.ico',
				'acao'	=> "<a href='https://docs.google.com/viewer?url=%s' title='%s' target='_blank'>Abrir&nbsp;PDF</a> \n".
						   "<a href='%s' title='Baixar documento' target='_blank'><img src='./imagens/%s'></a>"),
			'planilhas'	=> array(
				'ext'	=> '/xls|xlsx|ods|sxw|sxc|sxi|rtf$/',
				'desc'  => 'click.ver.doc.online',
				'ico'   =>  'Spreadsheet2.ico',
				'acao'	=> "<a href='http://viewer.zoho.com/api/view.do?cache=true&url=%s' title='%s' target='_blank'>Abrir documento &nbsp;<a href='%s' title='Baixar documento'><img src='./imagens/%s' target='_blank'></a> \n"),
			'compactado'=> array(
				'ext'	=> '/7z|arj|lha|gzip|lzh|rar|tar|zip$/',
				'desc'  => 'click.baixar.arquivo',
				'ico'   =>  'Comprimidos_ZIP.ico',
				'acao'	=> "<a href='%s' title='%s' target='_blank'>Baixar Arquivo<!--%s--><img src='./imagens/%s'></a> \n")
		);

		foreach ($tipo_arquivo as $tipo_arquivo=>$tipo_desc) {
			$linkFilename = strpos($arquivo, '?') !== false ?
				basename(substr($arquivo, 0, strpos($arquivo, '?'))) :
				basename($arquivo);
			if (preg_match($tipo_desc['ext'], $linkFilename)) {
				//echo "Arquivo $linkFilename tipo $tipo_arquivo, extensão bate com ".$tipo_desc['ext'].chr(10) . '<br />';
				$ret = sprintf($tipo_desc['acao'], urlencode($arquivo), traduz($tipo_desc['desc'], $con, $cook_idioma), $arquivo, $tipo_desc['ico']);
				return $ret;
			}
		}

		return $url;
	} // END function getAttachLink
}

include "helpdesk/mlg_funciones.php";

if ($S3_sdk_OK) {
	include_once S3CLASS;

	$s3 = new anexaS3('ve', (int) $login_fabrica);
	$S3_online = is_object($s3);
}

// SELECIONA AS FAMILIAS (Intelbras) OU LINHAS (resto do mundo)

	if ($login_fabrica == 14) {
		$sql = "SELECT familia,descricao FROM tbl_posto_linha JOIN tbl_familia USING(familia) WHERE posto = $login_posto AND fabrica = $login_fabrica ";
		$cond_linhas = "AND (tbl_comunicado.familia IN (%s) OR tbl_comunicado.familia IS NULL) ";
	} elseif ($login_fabrica==20) {
		$sql = "SELECT linha,nome FROM tbl_linha WHERE fabrica=20";
		$cond_linhas = "AND (tbl_comunicado.linha in (%s) OR tbl_comunicado.linha IS NULL) ";
	} else {
		$sql = "SELECT linha,nome FROM tbl_posto_linha JOIN tbl_linha USING(linha) WHERE posto = $login_posto AND fabrica = $login_fabrica";
		$cond_linhas = "AND (tbl_comunicado.linha in (%s) OR tbl_comunicado.linha IS NULL) ";
	}
	$res_linhas = @pg_query($con, $sql.' ORDER BY 2');
	if (!is_resource($res_linhas)) {
	    $msg_erro[] = 'Erro ao acessar as informações do posto. Por favor, tente novamente.';
	    echo "Erro!<br><pre>$sql</pre>\n<p>".pg_last_error($res_linhas).'</p>';
	    exit;
	} else {
		if (@pg_num_rows($res_linhas)) {
		    $i = 0;
	        while ($linha = @pg_fetch_result($res_linhas, $i, 0)) {
				$a_linhas[] = $linha;
				$a_lista_linhas[$linha] = @pg_fetch_result($res_linhas, $i++, 1);
	        }
	        $linhas_posto = implode(',',$a_linhas);
		}
		$cond_linhas = sprintf($cond_linhas, $linhas_posto);
	}

//PEGA O TIPO DO POSTO PARA MOSTRAR OS COMUNICADOS DOS MESMOS. QDO COMU = NULL TODOS PODEM VER
	$sql = "SELECT tipo_posto
			  FROM tbl_posto_fabrica
			 WHERE fabrica = $login_fabrica
			   AND posto   = $login_posto ";
	$res = pg_query($con, $sql);
	if (pg_num_rows($res) != 0) {
		$tipo_posto = 'tbl_comunicado.tipo_posto = '.trim(pg_fetch_result($res,0,tipo_posto));
	}
	$tipo_posto.= (trim($tipo_posto)!='') ? ' OR ':'';
	$tipo_posto.= 'tbl_comunicado.tipo_posto IS NULL';


//  Começa o AJAX
//  Pega os produtos da linha...
if ($_GET['ajax']=='produtos') {
	$linha = $_GET['linha'];
	$campo_pesquisa = ($login_fabrica == 14) ? 'familia' : 'linha';
	$sql = "SELECT DISTINCT tbl_produto.produto,referencia,tbl_produto.descricao
			  FROM tbl_produto
		 LEFT JOIN tbl_comunicado_produto USING (produto)
		 LEFT JOIN tbl_comunicado		  ON tbl_comunicado.comunicado = tbl_comunicado_produto.comunicado
										  OR tbl_comunicado.produto = tbl_produto.produto
			 WHERE tbl_produto.$campo_pesquisa = $linha
			   AND tbl_comunicado.ativo IS TRUE
			 ORDER BY referencia";
	$res = pg_query($con, $sql);
	$num_produtos = @pg_num_rows($res);
	if ($num_produtos > 0) {
		echo "<ul>\n";
		for ($i=0; $i < $num_produtos; $i++) {
			extract (@pg_fetch_assoc($res, $i));
			echo "\t<li id='produto_$i' alt='$produto' title='Lista de comunicados para este produto'>".
				"<span>$referencia &ndash; $descricao</span>\n";
			echo "\t<div class='comunicados' id='lista_$produto'></div>\n</li>\n";
		}
	    echo "</ul>\n";
	} else {
	    echo "<p>Não há comunicados para produtos desta linha</p>\n";
	}
	exit;
}

if ($_REQUEST['ajax']=='comunicados') {
	$produto = $_GET['produto'];

	$sql = "SELECT DISTINCT tbl_comunicado.comunicado					,
					CASE WHEN descricao IS NULL OR TRIM(descricao) = ''
						 THEN '(Comunicado sem título)'
						 ELSE descricao
					END AS descricao									,
					tbl_comunicado.mensagem								,
					tbl_comunicado.tipo									,
					tbl_comunicado.video								,
					TO_CHAR(tbl_comunicado.data,'DD/MM/YYYY') AS data   ,
					tbl_comunicado.data AS ordem
				 FROM tbl_comunicado
			LEFT JOIN tbl_comunicado_produto USING(comunicado)
			WHERE tbl_comunicado.fabrica = $login_fabrica
			  AND ($tipo_posto)
			  AND (tbl_comunicado.posto   		  = $login_posto OR tbl_comunicado.posto   IS NULL)
			  AND (tbl_comunicado_produto.produto = $produto	 OR tbl_comunicado.produto =  $produto)
			  AND tbl_comunicado.ativo IS TRUE
			ORDER BY ordem DESC, comunicado DESC";
	$res = pg_query($con, $sql);
	$num_comunicados = pg_num_rows($res);

	if ($num_comunicados) { ?>
	<table>
		<caption>Comunicados para este produto: <?=$num_comunicados?></caption>
		<thead>
		<tr>
		    <th>Data</th>
		    <th>Título</th>
		    <th>Tipo</th>
		</tr>
		</thead>
	<tbody>
	<?	for ($i=0; $i < $num_comunicados; $i++) {
			$info_comunicado = pg_fetch_assoc($res, $i);
			extract($info_comunicado);	?>
		<tr>
		    <td><?=$data?></td>
		    <td><a href='comunicado_mostra.php?comunicado=<?=$comunicado?>' target='_blank'><?=$descricao?></a></td>
		    <td><?=$tipo?></td>
		</tr>
		<tr>
<?
			$colspan = ($video !='') ? 2 : 3;

			if ($S3_online) {
				$tipo_s3 = in_array($tipo_comunicado, explode(',', utf8_decode(anexaS3::TIPOS_VE))) ? 've' : 'co';

				if ($tipo_s3 != $s3->tipo_anexo)
					$s3->set_tipo_anexoS3($tipo_s3);

				$arquivos = (array) $s3->url; //Array ou false
			} else {
				$arquivos = glob("./comunicados/$comunicado.*");
				sort($arquivos);
			}

			if (count($arquivos)) $colspan--; ?>
			<td colspan='<?=$colspan?>' style='white-space:normal'><?=(preg_match('/<[^>]+>(.*)<\/[^>]+>/', $mensagem)) ? $mensagem : nl2br($mensagem);?></td>
	<?		if($login_fabrica==50 and $video<>"") {?>
			<td><span class='video' video='<?=$video?>'>
				<a href="javascript:window.open('/assist/video.php?video=$video','_blank','toolbar=no, status=no, scrollbars=no, resizable=yes, width=460, height=380');void(0);">
					 Abrir vídeo
				</a></span>
			</td>
		<?	}
			if (count($arquivos)) {
				echo "<td style='white-space:normal'>";
			  foreach ($arquivos as $anexo) {
	// 	            p_echo($anexo);
					echo getAttachLink($anexo, 'http://posvenda.telecontrol.com.br/assist/');
				}
				echo "</td>\n";
			} ?>
		</tr>
	<? 	}
	    echo "</tbody>\n";
	    echo "</table>\n";
	}
	exit;
}
//  FIM AJAX


$title = traduz("comunicados",$con,$cook_idioma)." $login_fabrica_nome";
$layout_menu = "tecnica";

include 'cabecalho.php';
?>
<style type="text/css">
	#linhas {
	    width: 700px;
	    margin: 0.5 em 0 0 0;
	    padding: 0;
	    text-align: left;
	    height: 65%;
	    _height: 400px;
	    *height: 400px;
	    overflow-y: auto;
	}
	#linhas > ul {
	    width: 640px;
        list-style: square outside;
        text-align: left;
        color: black;
        font-weight: bold;
        font-size: 12px;
	}
	#linhas li span {
		cursor: pointer;
	}
	#linhas li span:hover {
		text-decoration: underline;
		background-color: #ddf;
		width: 95%;
	}
	#linhas li.aberto {
	    color: blue;
	}
	#linhas td a img {height:16px}

	div.comunicados table {
        table-layout: fixed;
	    font-size: 11px;
	    color: #333;
	    margin: 3px 0 1em 0;
	    width:590px;
	    border: 2px solid #D9E3ED;
        border-radius: 6px;
        -moz-border-radius: 6px;
        box-shadow: 3px 3px 4px #666;
        -moz-box-shadow: 3px 3px 4px #666;
	}
	div.comunicados table caption {
	    text-align: right;
        font-style: italic;
        font-aize: 11px;
        padding-bottom: 4px;
	}
	div.comunicados table th {
	    font-weight: bold;
	    font-size: 11px;
	    background-color: #193A88;
	    color: white;
	    text-align: center;
	    width: 70px;
	}
	div.comunicados table th+th{width:350px;}
	div.comunicados table th+th+th{width:150px;}

	div.comunicados table td {
	    font-size: 10px;
	}
	div.comunicados table tr > td {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
	}
	div.comunicados table tbody tr:nth-child(odd) {
		background-color: #ddd;
	}
</style>

<script src="js/jquery-1.6.1.min.js" type="text/javascript"></script>
<script type="text/javascript">
	$(function() {
		$('div#linhas > ul').children('li').find('span').parent() .click(function() {
			linha = $(this).attr('alt');
			$('div#produtos_'+linha).html('Carregando lista de produtos com comunicados...')
									.load('<?=$PHP_SELF?>?ajax=produtos&linha='+linha,function() {
				
				lista_produtos = $('div#produtos_'+linha);
				$('div#linhas > ul li[alt='+linha+']').unbind('click').children('span').click(function(){
					$(this).next().slideToggle('normal');
				});
				
				lista_produtos.find('li span').parent().each(function(){
				$(this).click(function () {
					produto = $(this).attr('alt');
						$(this).find('div').html('Carregando comunicados para este produto...').load('<?=$PHP_SELF?>?ajax=comunicados&produto='+produto);
						$(this).unbind('click').children('span').click(function(){$(this).next().slideToggle('normal');});
					});
				});
			});
		});
	});
</script>

<?
// print_r($a_lista_linhas);
if (is_array($a_lista_linhas)) {    ?>
<h1>Clique sobre a linha desejada para listar os produtos</h1>
<h3 style='text-align:center!important'>Para procurar um produto, aperte <b>Ctrl+F</b> e digite a referência ou a descrição do produto</h3>
<div id='linhas'>
	<ul>
<?
    foreach ($a_lista_linhas as $linha=>$nome_linha) {
    	echo "\t<li alt='$linha' title='Clique para ver os produtos da linha $nome_linha'>".
			 "<span>$nome_linha</span>\n";
    	echo "\t<div id='produtos_$linha'></div>\n</li>\n";
    }
?>
	</ul>
</div>
<?
} else {
	pre_echo('','<div style="height:60%">Posto sem linhas credenciadas</div>');
}
include "rodape.php";
