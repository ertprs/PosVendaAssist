<?php

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';

$admin_privilegios = 'call_center';
include_once 'autentica_admin.php';
include_once 'funcoes.php';
include_once __DIR__ . DIRECTORY_SEPARATOR . '../helpdesk/mlg_funciones.php';
include_once __DIR__ . DIRECTORY_SEPARATOR . '../class/fn_sql_cmd.php';

$limite_em_tela = 600;

if ($S3_sdk_OK) {
	include_once S3CLASS;
}

$msg = array();

$sel_tipos = include('menus/comunicado_option_array.php');
$sel_tipos2 = include('menus/vista_tipo_array.php');
$tipos_de_comunicado = array_merge($sel_tipos, $sel_tipos2);
asort($tipos_de_comunicado);
if ($_POST['com']) {
    $comunicado = getPost('com');
    if (!is_numeric($comunicado))
        die(traduz('Erro de comunicação!'));

    $sqlMsg = sql_cmd('tbl_comunicado', 'tipo, mensagem, extensao', $comunicado);
    $comdata = pg_fetch_assoc(pg_query($con, $sqlMsg), 0);

    if ($comdata === false)
        die(traduz("Não há informações do comunicado %.", null, null, [$comunicado]));

    if ($comdata['extensao']) { // determinar se tem anexo
        $s3 = new anexaS3($comdata['tipo'], $login_fabrica, $comunicado);

        if ($s3->temAnexo) {
            $link = ($s3->url) ? : $s3->getS3Link($s3->attachList[0]);
        }

        $tipo_anexo = strpos('.bmp gif jpg jpe jpeg png tif tiff svg', $comdata['extensao']) ?
            'camera' :
            (strpos('.doc xls odt odf pdf', $comdata['extensao']) ? 'book' : 'file');
        $anexo_footer = "
            <div class='modal-footer'>
              ".traduz("Este comunicado tem anexo").": <a href='$link' target='_new' class='btn'><i class='icon-$tipo_anexo'></i>".traduz("Abrir")."</a>
            </div>";
    }
    die("$mensagem\n$anexo_footer");
}

if (count($_POST)) {

	// filtros
	$ano  = getPost('ano_pesquisa');
	$tipo_comunicado    = getPost('tipo_comunicado');
    $produto_descricao  = getPost('produto_descricao');
	$produto_referencia = getPost('produto_referencia');
    $sem_anexo          = in_array('x', $_POST['checks']);
    $com_anexo          = in_array('a', $_POST['checks']);
    $sem_produto        = in_array('p', $_POST['checks']);

	if ($sem_anexo && $com_anexo)
		$sem_anexo = $com_anexo = false;

    // pre_echo($_POST, 'FILTROS');
	// A consulta de comunicados
	$tabelas = array('tbl_comunicado');
	$campos = array('comunicado', 'tbl_comunicado.descricao', 'tipo', 'mensagem',
		'extensao', 'video', 'link_externo', 'tbl_comunicado.data::DATE AS data');
    $filtroComunicado = array(
        'fabrica' => $login_fabrica
    );


    // Se pesquisa SEM produto, tem que informar algum outro filtro.
    // mesmo que tenha informado produto, se selecionou "Sem Produto", não procura
	$dados_produto = !$sem_produto && strlen($produto_descricao.$produto_referencia) > 0;

	if ($sem_produto) {
		$filtroComunicado['produto'] = array(
			'tbl_comunicado.produto' => null,
			'tbl_comunicado_produto.produto' => null
		);
		$tabelas[] = 'LEFT JOIN tbl_comunicado_produto USING (comunicado)';
	}

	if ($dados_produto and $produto_referencia) {
		$filtroProduto = array('fabrica_i' => $login_fabrica);
		if ($produto_referencia)
			$filtroProduto['produto'] = array(
				'referencia' => $produto_referencia,
				'@referencia_pesquisa' => $produto_referencia
			);

		$sqlp = sql_cmd('tbl_produto', 'produto', $filtroProduto);
		// pre_echo($sqlp, 'Buscar Producto', true);

		$resp = pg_query($con, $sqlp);
		if (pg_num_rows($resp)) {
            $produto = pg_fetch_result($resp, 0, 'produto');
            $filtroComunicado['produto'] = array(
                'tbl_comunicado.produto' => $produto,
                '@tbl_comunicado_produto.produto' => $produto
            );
        }
		else
			$msg['erro'][] = traduz("Produto % - % não encontrado!", null, null, [$produto_referencia, $produto_descricao]);
	}

	if ($ano) {
		$ini = "$ano-01-01";
		$fim = ++$ano . '-01-01';
		$filtroComunicado['data'] = "$ini::$fim";
	}

		if (!$produto_referencia)
			$filtroComunicado['posto'] = null;

	if ($tipo_comunicado) {
		$filtroComunicado['tbl_comunicado.tipo'] = $tipo_comunicado;
		if (array_key_exists('posto', $filtroComunicado))
			unset($filtroComunicado['posto']);
	}

    if (count(array_filter($filtroComunicado)) < 2) {
        $msg['erro'][] = traduz("Deve informar algum filtro para a pesquisa");
    } else {
		if ($com_anexo)
			$filtroComunicado['com_anexo'] = array(
				'LENGTH(TRIM(extensao))>' => 0,
				'!extensao' => null
			);

		if ($sem_anexo)
			$filtroComunicado['sem_anexo'] = array(
				'LENGTH(TRIM(extensao))' => 0,
				'@extensao' => null
			);


		if ($produto_voltagem)
			$filtroComunicado['tbl_produto.voltagem'] = $produto_voltagem;

		if (!$sem_produto or $produto_descricao) {
			$campos = array_merge(
				$campos,
				array('ARRAY_AGG(referencia) AS produto_referencia, ARRAY_AGG(tbl_produto.descricao) AS produto_descricao')
			);

                $tabelas[] = 'LEFT JOIN tbl_comunicado_produto USING (comunicado)';
                $tabelas[] = 'LEFT JOIN tbl_produto ON tbl_comunicado.produto = tbl_produto.produto ' .
                    'OR tbl_comunicado_produto.produto = tbl_produto.produto';

			// Se tem referencia, tem que bater 100%, se não tem referencia, procurar
			// pelo nome/descrição
			if (strlen($produto_referencia) == 0 and $produto_descricao) {
				$filtroComunicado['tbl_produto.descricao'] = "%$produto_descricao%";
			}

		}

		$filtroComunicado['tbl_comunicado.ativo'] = true;

		pg_query($con, "SET DateStyle TO SQL, European");

		$sqlcom = sql_cmd(
			$tabelas,
			$campos,
			$filtroComunicado
		);
		if (count($tabelas) > 2)
			$sqlcom .= "\n GROUP BY comunicado, tbl_comunicado.descricao, mensagem, tipo, extensao, video, link_externo, data";
		$sqlcom .= "\n ORDER BY tbl_comunicado.tipo, data DESC";

		// pre_echo($sqlcom, 'CONSULTA DO COMUNICADO');
		// echo nl2br($sqlcom);
		$resCom = pg_query($con, $sqlcom);
		$totcom = pg_num_rows($resCom);

		if (!$totcom)
			$msg['erro'][] = traduz('Sem comunicados para o(s) filtro(s) selecionado(s)');

		if ($totcom > $limite_em_tela)
			$msg['erro'][] = traduz("A consulta trouxe mais de % linhas. Utilize mais filtros para aprimorar a pesquisa.", null, null, [$limite_em_tela]);

	}
}

$layout_menu = in_array($login_fabrica, array(108,111)) ? 'gerencia' : 'callcenter';

$titulo = traduz('Consulta de Comunicados');
$title  = traduz('CONSULTA DE COMUNICADOS');

include "cabecalho_new.php";

$plugins = array(
    'autocomplete',
    'shadowbox'
);

include('plugin_loader.php');
?>
<script>
    $(function() {
		Shadowbox.init();
		$.autocompleteLoad(["produto"]);

        $("span[rel^=lupa]").click(function () {
            $.lupa($(this));
        });
    });

    function retorna_produto (retorno) {
        $("#produto_referencia").val(retorno.referencia);
        $("#produto_descricao").val(retorno.descricao);
    }

    // POPUP COM LINK
    function abrir(URL) {

        var width  = 500;
        var height = 350;
        var left   = 99;
        var top    = 99;

        window.open(URL,'janela', 'width='+width+', height='+height+', top='+top+', left='+left+', scrollbars=yes, status=no, toolbar=no, location=no, directories=no, menubar=no, resizable=no, fullscreen=no');

    }

</script>
<?php
if (count($msg['erro']) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg['erro'])?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>

<?php

$inputs = array(
	'produto_referencia' => array(
		'span' => 3,
		'label' => traduz('Referência do Produto'),
		'type' => 'input/text',
		'width' => 10,
		'maxlength' => 10,
		'lupa' => array(
            'name' => 'lupa_config',
			'tipo' => 'produto',
			'parametro' => 'referencia'
		)
	),
	'produto_descricao' => array(
		'span' => 5,
		'label' => traduz('Descrição do Produto'),
		'type' => 'input/text',
		'width' => 10,
		'maxlength' => 50,
		'lupa' => array(
			'name' => 'lupa_config',
			'tipo' => 'produto',
			'parametro' => 'descricao'
		)
	),
	'ano_pesquisa' => array(
		'span' => 3,
		'label' => traduz('Ano do Comunicado'),
		'type' => 'select',
		'width' => 6,
		'options' => array_combine(
			range(date('Y'), 2005),
			range(date('Y'), 2005)
		)
	),
	'tipo_comunicado' => array(
		'span' => 4,
		'label' => traduz('Tipo de Comunicado'),
		'type' => 'select',
		'options' => $tipos_de_comunicado
	),
	'checks' => array(
		'span' => 5,
		'label' => traduz('Outras condições'),
		'type' => 'checkbox',
		'extras' => '',
		'checks' => array('p' => traduz('Sem Produto'), 'x' => traduz('Sem Anexo'), 'a' => traduz('Com Anexo'))
	)
);
?>

<form name="frm_comunicado" method="POST" class="form-search form-inline tc_formulario">
	<div class='titulo_tabela '><?=$title?></div>
	<br/>
	<?php echo montaForm($inputs); ?>

	<br/>
	<p>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
		<button class='btn'><?=traduz('Consultar')?></button>
		<?php
		if (count($_POST) > 0) { ?>
			<button class='btn btn-warning' type="button"  onclick="window.location = '<?=$_SERVER["PHP_SELF"]?>';"><?=traduz('Limpar')?></button>
		<?php } ?>
	</p>
	<br/>
</form>
<?
flush();

if ($totcom > 0) {

	$tCols = 2 + !$sem_produto + !$sem_anexo;
	$comMsgModal  = ''; // as modais
	$tipo_anterior = null;

	// $comunicado, $descricao, $mensagem ou vídeo...
	$comModalBase = '
		<div id="modal%s" class="modal hide fade" role="dialog">
		  <div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
			<h3>%s</h3>
		  </div>
		  <div class="modal-body">%s</div>
		</div>';

	$editComLink = "<a href='comunicado_produto.php?comunicado=%s' target='_new'>Abrir comunicado <i class='icon-share'></i></a>";

	$sect_sep     = "\t\t<tr><td colspan='$tCols'></td></tr></tbody>\n";
	$tipo_header  = "\t<thead class='titulo_tabela'>\n\t\t<tr><th colspan='$tCols'>".traduz("Tipo do Comunicado").": %s</th></tr>\n";
	$sect_header  = "\t<tr><th>Data</th><th>".traduz("Título")."</th>";

	if (!$sem_produto)
		$sect_header .= '<th>'.traduz("Produto").'</th>';

	if (!$sem_anexo)
		$sect_header .= "<th>".traduz("Anexo")."</th>";

	$sect_header .= "</tr>\n\t</thead>\n";

	echo "<br /><table class='table table-bordered table-hover table-striped'>\n";

    if ($totcom > $limite_em_tela)
        echo "\t<caption>".traduz("Listando $limite_em_tela (de um total de $totcom) comunicados")."</caption>\n";
    else
        echo "\t<caption>".traduz("Listando $totcom comunicados")."</caption>\n";

	$rowCount = 0;
	while ($row = pg_fetch_assoc($resCom, $rowCount++) and $rowCount < $limite_em_tela) {

		$produtoHTML = $colProd = $tituloProduto = $titulo_comunicado = $anexo_footer = '';

		$comunicado         = $row['comunicado'];
		$descricao          = $row['descricao'];
		$video              = $row['video'];
		$link_externo       = $row['link_externo'];
		$extensao           = $row['extensao'];
		$tipo               = $row['tipo'];
		$mensagem           = $row['mensagem'];
		$data               = $row['data'];
		$produto_referencia = $row['produto_referencia'];
		$produto_descricao  = $row['produto_descricao'];

		if ($rowCount%20 === 0) {
			flush();
		}

		if ($tipo_anterior != $tipo) {
			$tipo_anterior = $tipo;

			flush();
			if ($rowCount != 0) echo $sect_sep;

			printf($tipo_header, $tipo);
			echo $sect_header;
		}

		$produto_referencia = pg_parse_array($produto_referencia);
		$produto_descricao  = pg_parse_array($produto_descricao);

		$mediaColContent = '&nbsp;';

		if ($video <> '') { // HD 65474
			// Converte a URL direta para assistir o vídeo para a URL do objeto
			$link_video = str_replace("/watch?v=", "/v/", $video);
			$mediaColContent .= "<span class='span4'><a href='$link_video' alt='".traduz("Vídeo")."' target='_blank'><i class='icon-facetime-video'></i>&nbsp;".traduz("Assistir vídeo")."</a></span>\n";
		}

		if ($link_externo <> '') { // HD 65474
			$mediaColContent .= "<span class='span4'><a href='$link_externo' target='_blank'><i class='icon-share'></i>&nbsp;<i>Link</i> ".traduz("externo")."</a></span>\n";
		}

		if (!$sem_anexo) {
			$s3 = new anexaS3(($tipo ? : 'co'), (int)$login_fabrica, $comunicado);
			if (!$s3->_erro) {
				$s3link = $s3->url;
				if (!empty($s3link)) {
					$s3link = ($s3->url) ? : $s3->getS3Link($s3->attachList[0]);
					$extensao =  substr($extensao = pathinfo($s3link, PATHINFO_EXTENSION), 0, strpos($extensao, '?'));

					$tipo_anexo = strpos('.bmp gif jpg jpe jpeg png tif tiff svg', $extensao) ?
						'picture' : (strpos('.doc xls odt odf pdf', $extensao) ?
						'book' : 'file');

					if ($login_fabrica == 50 and $video <> '') {
						$tipo_anexo = 'film';
						$extensao   = 'mp4'; // só para ter uma...
					}

					if ($s3link)
						$mediaColContent .= "<span class='span4'><a href='$s3link' alt='S3' target='_blank'><i class='icon-$tipo_anexo'></i>&nbsp;".traduz("Abrir Anexo")."</a></span>\n";
				}
			}
		}

        $mediaCol = $sem_anexo ? '' : "<td>$mediaColContent</td>";

		// if (!$sem_produto)
		if (count(array_filter($produto_referencia))) {
			if (count(array_filter($produto_referencia)) === 1) {
				$tituloProduto = "{$produto_referencia[0]} &ndash; {$produto_descricao[0]}";
			} else {
				$tituloProduto = "<em>".traduz("Produtos").": " . join(', ', $produto_referencia) . '</em>';
			}
			foreach ($produto_referencia as $i=>$ref) {
				$produtoHTML .= "\t\t\t<dt>Ref: $ref</dt><dd>$produto_descricao[$i]</dd>\n";
			}
		}

		if (!$sem_produto)
			$colProd = "<td>$produtoHTML</td>";

		if (!$descricao and $produto_descricao and $colProd)
			$descricao = $tituloProduto;

		$descricao = ($descricao) ? : '<i>'.traduz("SEM TÍTULO").'</i>';
		if ($mensagem or $mediaColContent) {
			$titulo_comunicado = "<a href='#modal$comunicado' role='link' class='link' data-toggle='modal'>$descricao</a>";
			if (strpos($mediaColContent, 'href'))
				$anexo_footer = "
					<div class='modal-footer'>$mediaColContent</div>";
			$comMsgModal .= sprintf($comModalBase, $comunicado, $titulo_comunicado, $mensagem.$anexo_footer);
		} else
			$titulo_comunicado = $descricao;

		$titulo_comunicado = join('<br />', array_filter(array($titulo_comunicado, sprintf($editComLink, $comunicado)), 'trim'));
		echo "\t\t<tr>" .
			"<td>$data</td>" .
			"<td>$titulo_comunicado</td>" .
			$colProd .
			$mediaCol .
		'</tr>'.PHP_EOL;
	}
	echo "</table>\n\t$comMsgModal";
}

include "rodape.php";

