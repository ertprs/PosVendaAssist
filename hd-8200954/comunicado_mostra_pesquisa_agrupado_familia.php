<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

// SELECIONA AS FAMILIAS (Intelbras) OU LINHAS (resto do mundo)
	if ($login_fabrica == 14) {
		$sql = "SELECT familia,descricao FROM tbl_posto_linha JOIN tbl_familia USING(familia) WHERE posto = $login_posto";
		$cond_linhas = "AND (tbl_comunicado.familia IN (%s) OR tbl_comunicado.familia IS NULL) ";
		$campo_pesquisa = 'familia';
	} elseif ($login_fabrica==20) {
		$sql = "SELECT linha,nome FROM tbl_linha WHERE fabrica=20 ";
		$cond_linhas = "AND (tbl_comunicado.linha in (%s) OR tbl_comunicado.linha IS NULL) ";
		$campo_pesquisa = 'linha';
	} else {
		$sql = "SELECT linha,nome FROM tbl_posto_linha JOIN tbl_linha USING(linha) WHERE posto = $login_posto AND fabrica = $login_fabrica";
		$cond_linhas = "AND (tbl_comunicado.linha in (%s) OR tbl_comunicado.linha IS NULL) ";
		$campo_pesquisa = 'familia';
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
				FROM	tbl_posto_fabrica
				WHERE   fabrica = $login_fabrica
				AND     posto   = $login_posto ";
	$res = pg_query($con, $sql);
	if (pg_num_rows($res) != 0) {
		$tipo_posto = 'tbl_comunicado.tipo_posto = '.trim(pg_fetch_result($res,0,'tipo_posto'));
	}
	$tipo_posto.= (trim($tipo_posto)!='') ? ' OR ':'';
	$tipo_posto.= 'tbl_comunicado.tipo_posto IS NULL';


if (!function_exists('getAttachLink')) {
	function getAttachLink($arquivo) { // BEGIN function getAttachLink

		$tipo_arquivo = array(
			'imagens'	=> array(
				'ext'	=> '/gif|jpg|png|bmp$/',
				'ico'   =>  'image.ico',   /*  Imagen_PNG.ico  */
				'desc'  => 'click.ver.imagem',
				'acao'	=> "<a href='%s' title='%s' target='_blank'>Visualizar imagem&nbsp;<!--%s--><img src='./imagens/%s'></a> \n"),
			'docs'		=> array(
				'ext'	=> '/doc|docx|odt|ppd|odp$/',
				'desc'  => 'click.ver.doc.online',
				'ico'   =>  'Text_Document.ico',
				'acao'	=> "<a href='https://docs.google.com/viewer?url=%s' title='%s' target='_blank'>Abrir documento</a> \n".
						   "<a href='%s' title='Baixar'>&nbsp;<img src='./imagens/%s'></a>"),
			'pdf'		=> array(
				'ext'	=> '/pdf$/',
				'desc'  => 'click.ver.doc.pdf',
				'ico'   =>  'Oficina_PDF.ico',
				'acao'	=> "<a href='https://docs.google.com/viewer?url=%s' title='%s' target='_blank'>Abrir&nbsp;PDF</a> \n".
						   "<a href='%s' title='Baixar documento'><img src='./imagens/%s'></a>"),
			'planilhas'	=> array(
				'ext'	=> '/xls|xlsx|ods|sxw|sxc|sxi|rtf$/',
				'desc'  => 'click.ver.doc.online',
				'ico'   =>  'Spreadsheet2.ico',
				'acao'	=> "<a href='http://viewer.zoho.com/api/view.do?cache=true&url=%s' title='%s' target='_blank'>Abrir documento &nbsp;<a href='%s' title='Baixar documento'><img src='./imagens/%s'></a> \n"),
			'compactado'=> array(
				'ext'	=> '/7z|arj|lha|gzip|lzh|rar|tar|zip$/',
				'desc'  => 'click.baixar.arquivo',
				'ico'   =>  'Comprimidos_ZIP.ico',
				'acao'	=> "<a href='%s' title='%s'>Baixar Arquivo<!--%s--><img src='./imagens/%s'></a> \n")
		);

		foreach ($tipo_arquivo as $tipo_arquivo=>$tipo_desc) {
			$linkFilename = strpos($arquivo, '?') !== false ?
				basename(substr($arquivo, 0, strpos($arquivo, '?'))) :
				basename($arquivo);
			if (preg_match($tipo_desc['ext'], $linkFilename)) {
				//echo "Arquivo $linkFilename tipo $tipo_arquivo, extensão bate com ".$tipo_desc['ext'].chr(10) . '<br />';
				$ret = sprintf($tipo_desc['acao'], $arquivo, traduz($tipo_desc['desc'], $con, $cook_idioma), $arquivo, $tipo_desc['ico']);
				return $ret;
			}
		}

		return $url;
	} // END function getAttachLink
}

//  Começa o AJAX
//  Pega os produtos da linha...
if ($_GET['ajax']=='produtos') {
	$linha = $_GET['linha'];
	$sql = "SELECT DISTINCT tbl_produto.produto,referencia,tbl_produto.descricao
			FROM tbl_produto
			LEFT JOIN tbl_comunicado_produto USING (produto)
			LEFT JOIN tbl_comunicado ON tbl_comunicado.comunicado = tbl_comunicado_produto.comunicado
									 OR tbl_comunicado.produto    = tbl_produto.produto
			WHERE (tbl_produto.$campo_pesquisa = $linha
			   OR tbl_comunicado.$campo_pesquisa = $linha)
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
		echo "<p>".traduz('nao.ha.comunicados.disponiveis', $con, $cook_idioma)."</p>\n";
	}
	exit;
}

if ($S3_sdk_OK) {
	include_once S3CLASS;

	$s3 = new anexaS3('ve', (int) $login_fabrica);
	$S3_online = is_object($s3);
}

if ($_REQUEST['ajax']=='comunicados') {
	$produto = $_GET['produto'];
	$familia = $_GET['familia'];
	$sem_titulo = traduz('sem.titulo', $con);

	$sql = "SELECT DISTINCT tbl_comunicado.comunicado,
				   CASE WHEN descricao IS NULL OR TRIM(descricao) = ''
				        THEN '($sem_titulo)'
				        ELSE descricao
				   END AS descricao,
				   tbl_comunicado.mensagem,
				   tbl_comunicado.tipo,
				   tbl_comunicado.versao,
				   tbl_comunicado.video,
				   TO_CHAR(tbl_comunicado.data,'DD/MM/YYYY') AS data,
				   tbl_comunicado.data AS ordem
			 FROM tbl_comunicado
        LEFT JOIN tbl_comunicado_produto USING(comunicado)
			WHERE tbl_comunicado.fabrica = $login_fabrica
			  AND ($tipo_posto)
			  AND (tbl_comunicado.posto = $login_posto OR tbl_comunicado.posto IS NULL)
			  AND (tbl_comunicado_produto.produto = $produto
						OR tbl_comunicado.produto = $produto
						OR tbl_comunicado.familia = $familia)
			  AND tbl_comunicado.ativo IS TRUE
			ORDER BY ordem DESC, comunicado DESC";
	$res = pg_query($con, $sql);
	$num_comunicados = pg_num_rows($res);

	if ($num_comunicados) { ?>
		<table>
			<caption><?=traduz('comunicados.para.este.produto', $con, $cook_idioma)?>: <?=$num_comunicados?></caption>
			<thead>
			<tr>
				<th><?=traduz('data', $con)?></th>
				<th><?=traduz('titulo', $con)?></th>
				<th><?=traduz('tipo', $con)?></th>
			</tr>
			</thead>
		<tbody>
	<?for ($i=0; $i < $num_comunicados; $i++) {
		$info_comunicado = pg_fetch_assoc($res, $i);
		extract($info_comunicado);
		if ($usa_versao_produto and $versao and $tipo == 'Vista Explodida')
			$tipo = "$tipo <span class='versao'>(versão $versao)</span>";
		?>
		<tr>
			<td><?=$data?></td>
			<td><a href='comunicado_mostra.php?comunicado=<?=$comunicado?>' target='_blank'><?=$descricao?></a></td>
			<td><?=$tipo?></td>
		</tr>
		<tr>
	<?  $colspan = ($video !='') ? 2 : 3;
		if ($S3_online) {
			$tipo_s3 = $s3->set_tipo_anexoS3($tipo)->tipo_anexo;
			$s3->temAnexos($comunicado); //Array ou false
			$arquivos = (array) $s3->url;
		} else {
			$arquivos = glob("./comunicados/$comunicado.*");
			sort($arquivos);
		}
	if (count($arquivos)) $colspan--; ?>
			<td colspan='<?=$colspan?>' style='white-space:normal'>
			<?=(preg_match('/<[^>]+>(.*)<\/[^>]+>/', $mensagem)) ? $mensagem : nl2br($mensagem);	// Não converte se o texto já é HTML... Para o futuro :)	?>
			</td>
		<?	if($video<>""){?>
			<td>
				<span class='video' video='<?=$video?>'>
					<a href="javascript:window.open('/assist/video.php?video=<?=$video?>','_blank','toolbar=no, status=no, scrollbars=no, resizable=yes, width=460, height=380');void(0);">Abrir vídeo</a>
				</span>
			</td>
		<?	}
		if (count($arquivos)) {
			echo "<td style='white-space:normal'>";
			foreach ($arquivos as $anexo) {
				echo getAttachLink($anexo);
			}
			echo "</td>\n";
		} ?>
		</tr>
	<?}
	echo "</tbody>\n";
	echo "</table>\n";
	} else {
		echo "<p>".traduz('nao.ha.comunicados.disponiveis', $con, $cook_idioma)."</p>\n";
	}
	exit;
} //  FIM AJAX


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
	ul.linha ul.familia {display:none}
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
	#linhas td a img {height:20px; width: 20px}

	div.comunicados table {
        table-layout: fixed;
	    font-size: 11px;
	    color: #333;
	    margin: 3px 0 1em 0;
	    width:550px;
	    border: 2px solid #D9E3ED;
        border-radius: 6px;
        -moz-border-radius: 6px;
        -webkit-border-radius: 6px;
        box-shadow: 3px 3px 4px #666;
        -moz-box-shadow: 3px 3px 4px #666;
        -webkit-box-shadow: 3px 3px 4px #666;
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
	    width: 80px;
	}
	div.comunicados table thead tr > th:first-child {
        border-top-left-radius: 6px;
        -moz-border-radius-topleft: 6px;
        -webkit-border-top-left-radius: 6px ;
	}
	div.comunicados table th+th{width:330px;}
	ul.familia div.comunicados table th+th+th{
		width:130px;
        border-top-right-radius: 6px;
        -moz-border-radius-topright: 6px;
        -webkit-border-top-right-radius: 6px;
	}
/*  Para a Intelbras e a Bosch...   */
	div.comunicados table th+th{width:320px;}
	#linhas ul li ul li div.comunicados table th+th+th{
		width:148px;
	}

	div.comunicados table td {
	    font-size: 10px;
	}
	div.comunicados table tr > td {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
	}
	div.comunicados table TBODY tr:nth-child(odd) {
		background-color: #ddd;
	}
</style>

<script src="js/jquery-1.6.1.min.js" type="text/javascript"></script>
<?	if ($login_fabrica == 14 or $login_fabrica == 20) {	?>
<script type="text/javascript">
$(function() {
	$('div#linhas > ul').children('li').find('span')
	.parent()
	.click(function() {
		linha = $(this).attr('alt');
		$('div#produtos_'+linha).html('<?=traduz('carregando.lista.de.produtos.com.comunicado', $con, $cook_idioma)?>')
		.load('<?=$PHP_SELF?>?ajax=produtos&linha='+linha,function() {
			lista_produtos = $('div#produtos_'+linha);
			$('div#linhas > ul li[alt='+linha+']').unbind('click')
				.children('span')
				.click(function(){
					$(this).next().slideToggle('normal');
			});

			// Eventos para os produtos
			lista_produtos.find('li span')
			.parent()
			.each(function(){
				$(this).click(function () {
					var produto = $(this).attr('alt');
					var familia = $(this).parents('div[id^=prod]').attr('id').replace(/\D/g, '');
					$(this).find('div').html('<?=traduz('carregando.lista.de.comunicados', $con, $cook_idioma)?>')
					.load('<?=$PHP_SELF?>?ajax=comunicados&produto='+produto+'&familia='+familia);
					$(this).unbind('click')
					.children('span')
					.click(function() {
						$(this).next().slideToggle('normal');
					});
				});
			});
		});
	});
});
</script>
<? } else {	?>
<script type="text/javascript">
$(function() {
	$('div#linhas > ul.linha > li > span').click(function () {
		$(this).parent().children('ul.familia').slideToggle();
	});
	$('div#linhas ul.linha li > ul.familia > li').click(function() {
		var familia        = $(this).attr('alt');
		var div_familia    = 'div#produtos_'+familia;
		var lista_produtos = $(div_familia);
		$(div_familia).html('<?=traduz('carregando.lista.de.produtos.com.comunicado', $con, $cook_idioma)?>')
		.load('<?=$PHP_SELF?>?ajax=produtos&linha='+familia,function(div_familia) {
			$(this).parent().unbind('click')
			.children('span')
			.click(function(){
				$(this).next().slideToggle('normal');
			});
			$(this).parent()
			.find('ul li')
			.each(function(){
				$(this).click(function () {
					var produto = $(this).attr('alt');
					var familia = $(this).parents('div[id^=prod]').attr('id').replace(/\D/g, '');
					$(this).find('div').html('<?=traduz('carregando.lista.de.comunicados', $con, $cook_idioma)?>')
					.load('<?=$PHP_SELF?>?ajax=comunicados&produto='+produto+'&familia='+familia);
					$(this).unbind('click')
					.children('span')
					.click(function() {
						$(this).next().slideToggle('normal');
					});
				});
			});
		});
	});
});
</script>
<? }	?>

<?
	// print_r($a_lista_linhas);
	if (is_array($a_lista_linhas)) {    ?>
<h1><?=traduz('selecionar.linha.e.familia', $con, $cook_idioma)?></h1>
<h3 style='text-align:center!important'><?=traduz('ctrl.f.para.procurar.produto', $con, $cook_idioma)?></h3>
<div id='linhas'>
	<ul class='linha'>
<?
    foreach ($a_lista_linhas as $linha=>$nome_linha) {
		$li_title = iif(($login_fabrica != 14), "Clique para listar as famílias da linha $nome_linha", "Clique para ver os produtos da linha $nome_linha");
    	echo "\t<li alt='$linha' title='$li_title'>".
			 "<span>$nome_linha</span>\n";
		if ($login_fabrica != 14 and $login_fabrica != 20) {
			$sql_familias = "SELECT DISTINCT familia,tbl_familia.descricao AS nome_familia
								FROM tbl_produto
								JOIN tbl_familia USING(familia)
							WHERE tbl_produto.linha=$linha
							  AND tbl_familia.fabrica = $login_fabrica
							ORDER BY 2";
			$familias = pg_fetch_all($res_familias = pg_query($con, $sql_familias));
			if (pg_num_rows($res_familias)) {
				echo "\t\t<ul class='familia'>\n";
				foreach ($familias AS $a_familia) {
					$familia = $a_familia['familia'];
					$nome_familia = $a_familia['nome_familia'];
					$li_title = "Clique para ver os produtos da família $nome_familia";
					echo "\t\t\t<li alt='$familia' title='$li_title'><span>$nome_familia</span>\n";
			    	echo "\t<div id='produtos_$familia'></div>\n</li>\n";
				}
				echo "\t\t</ul>\n";
			} else {
			    echo "<p>".traduz('nao.ha.comunicados.disponiveis', $con, $cook_idioma)."</p>\n";
			}
		} else {
    		echo "\t<div id='produtos_$linha'></div>\n</li>\n";
		}
    }
?>
	</ul>
</div>
<?
	} else {
		pre_echo('','<div style="height:60%">Posto sem linhas credenciadas</div>');
	}
include "rodape.php";
