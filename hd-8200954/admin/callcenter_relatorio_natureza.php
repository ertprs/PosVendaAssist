<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$layout_menu = "callcenter";
$admin_privilegios = 'call_center';
include 'autentica_admin.php';

include_once 'funcoes.php';
include_once '../helpdesk/mlg_funciones.php';
include_once '../class/fn_sql_cmd.php';

$title     = traduz('RELATÓRIO DE NATUREZA DE CHAMADO');
$cabecalho = traduz('RELATÓRIO DE NATUREZA DE CHAMADO');

$btn_acao = $_POST['btn_acao'];

//HD 263699: Foram alterados assunstos no CallCenter e Fale Conosco
//			 É no arquivo abaixo que é definido o array $assuntos
include_once("callcenter_suggar_assuntos.php");
$NaturezaSql = pg_fetch_pairs(
    $con, sql_cmd(
        'tbl_natureza', 'nome, descricao',
        array('fabrica' => $login_fabrica, 'ativo' => true)
    ). ' ORDER BY nome'
);

$table = null;

if (count($_POST) and !empty($_POST['btn_acao'])) {
	$data_ini           = is_date($data_inicial = getPost('data_inicial'));
    $data_fim           = is_date($data_final   = getPost('data_final'));
    $produto            = (int)getPost('produto');
	$produto_referencia = getPost('produto_referencia');
	$produto_descricao  = getPost('produto_descricao');
	$natureza_chamado   = getPost('natureza_chamado');
    $status             = getPost('status');

    if (!empty($status)) {
    	$cond_status   = (empty($status)) ? " AND tbl_hd_chamado.status <> 'Cancelado'  "
                                  : " AND tbl_hd_chamado.status = '$status'"; 
    }

	if (!empty($natureza_chamado)) {
    	$cond_natureza  = (empty($natureza_chamado)) ? "  "
                                  : " AND tbl_hd_chamado.categoria='$natureza_chamado'"; 
    }
    if (!empty($produto)) {
    	$cond_produto  = " AND tbl_hd_chamado_extra.produto = $produto ";
    }

    $where = array('fabrica_responsavel' => $login_fabrica);

    if (!$data_ini or !$data_fim) {
        $msg_erro['msg'][] = traduz('Data inválida!');
    } else {
        if ($data_ini > $data_fim) {
            $msg_erro['msg'][] = traduz('Data inicial é posterior à data final!');
        } else
            $where['tbl_hd_chamado.data'] = "$data_ini::$data_fim 23:59:59";
    }

	if (!$produto and strlen($produto_referencia)>0) {
        $res = pg_query(
            $con, sql_cmd(
                'tbl_produto', 'produto', array(
                    'produto_referencia' => $produto_referencia,
                    'fabrica_i' => $login_fabrica
                )
            )
        );

		if (pg_num_rows($res)>0) {
			$produto = pg_fetch_result($res, 0, 0);
		}
	}

    if ($produto) {
        $where['tbl_hd_chamado_extra.produto'] = $produto;
    }

	if (strlen($natureza_chamado)>0) {
		$where['tbl_hd_chamado.categoria'] = $natureza_chamado;
	}

	if (strlen($status)>0) {
        $where['tbl_hd_chamado.status$'] = $status;
    } else {
        $where['!tbl_hd_chamado.status'] = traduz('Cancelado');
    }

	if ($login_fabrica==2) {
		$condicoes = "$produto;$natureza_chamado;$status;$posto;$data_ini;$data_fim";
    }

    if (DEBUG === true and count($msg_erro['msg']))
        pre_echo($msg_erro, 'ERRORS', true);

	$sql_tabelas = array('tbl_hd_chamado', 'JOIN tbl_hd_chamado_extra USING(hd_chamado)');
	$sql_fields  = array('tbl_hd_chamado.categoria', "'' AS faq", 'COUNT(tbl_hd_chamado.hd_chamado) AS qtde');

	if ($natureza_chamado == 'duvida_produto' and isFabrica(161)) {
		$faqDesc = pg_fetch_pairs($con, "SELECT faq, situacao FROM tbl_faq WHERE fabrica = $login_fabrica");

		$sql_fields  = array('tbl_faq.situacao AS categoria', "tbl_faq.faq", 'COUNT(tbl_hd_chamado.hd_chamado) AS qtde');
        $sql_tabelas[] = 'JOIN tbl_hd_chamado_faq USING(hd_chamado)';
        $sql_tabelas[] = "JOIN tbl_faq ON tbl_faq.faq = tbl_hd_chamado_faq.faq AND tbl_faq.fabrica = $login_fabrica";
	}

    $where['tbl_hd_chamado.posto'] = "null";

    if (!count($msg_erro['msg'])) {
        $sql = sql_cmd($sql_tabelas, $sql_fields, $where) .
            iif(count($sql_tabelas)>2, "\n GROUP by tbl_faq.situacao,tbl_faq.faq", "\n GROUP BY tbl_hd_chamado.categoria, faq").
            "\n ORDER BY qtde DESC";
        $res = pg_query($con, $sql);

        if (DEBUG === true)
            pre_echo($sql, 'Consulta');

		if (is_resource($res) and pg_num_rows($res)) {
            $recs = pg_fetch_all($res);
            // echo array2table($res, 'RESULTADOS');
			$table = array(
				'attrs' => array(
					'trColors'     => array('#F7F5F0', '#F1F4FA'),
					'tableAttrs'   => ' class="table table-striped table-bordered table-hover table-fixed"',
					'captionAttrs' => ' class="titulo_tabela"',
					'headerAttrs'  => ' class="titulo_coluna"',
				)
			);

            $total_qtde = array_sum(array_column($recs, 'qtde'));
            $graphData = array(
                0 => array('Natureza', 'Quantidade')
            );

            foreach ($recs as $idx => $rec) {
                $faq      = $rec['faq'];
                $natureza = (empty($faq)) ? $rec['categoria'] : 'duvida_produto';

                $linkParams = isFabrica(24) ?
                    http_build_query(
						array(
							'btn_acao'           => 'consultar',
							'status'   => $status,
							'data_inicial'       => $data_inicial,
							'data_final'         => $data_final,
							'produto_referencia' => $produto_referencia,
							'produto_descricao'  => $produto_descricao,
							'natureza_chamado'	=> $natureza
						)
                    ) :
                    http_build_query(compact('data_inicial', 'data_final', 'produto', 'natureza_chamado', 'status', 'tipo', 'periodo', 'defeito_reclamado', 'faq'));
				$link = isFabrica(24) ?
					"callcenter_relatorio_produto.php?$linkParams" :
					"#' data-params='$linkParams' onClick='AbreCallcenter(this);return false";

				$rotulo = ($faq) ? $faqDesc[$faq] : (
					isFabrica(24) ?
						$NaturezaSelect[$rec['categoria']] :
						$NaturezaSql[$rec['categoria']]
					);
                $qtde      = $rec['qtde'];

                $graph['status'][] = $rotulo;
                $graph['qtde'][]   = $qtde;
				$perc = $qtde/$total_qtde*100;

                $table[$idx] = array(
                    'Natureza'   => "<a href='$link' target='_new'>$rotulo</a>",
                    'Quantidade' => $qtde,
                );

                $table[$idx]['Percent.'] = priceFormat($perc) . ' %';
                $graphData[] = array(utf8_encode($rotulo), (int)$qtde);
            }

            // Resumo da tabela
            $jsonData = json_encode($graphData);
            $table[] = array(
                'status'   => 'Total',
                'qtde'     => $total_qtde,
                'Percent.' => '100,00 %'
            );

		} else $table = array();
    }
}

include "cabecalho_new.php";

// formulário pesquisa
$formData = array(
    'section' => array(
        'name' => traduz('Parâmetros de Pesquisa'),
        'visible' => true
    ),
    'data_inicial' => array(
        'label' => traduz('Data Inicial'),
        'type' => 'input/text',
        'span' => 2,
        'width' => 12,
        'required' => true,
    ),
    'data_final' => array(
        'label' => traduz('Data Inicial'),
        'type' => 'input/text',
        'span' => 2,
        'width' => 12,
        'required' => true,
    ),
    'natureza_chamado' => array(
        'label' => traduz('Natureza'),
        'type' => 'select',
        'span' => 3,
        'width' => 12,
        'options' => $login_fabrica == 24 ?
            $NaturezaSelect : $NaturezaSql
    ),
    'status' => array(
        'label' => traduz('Status do Atendimento'),
        'type' => 'select',
        'span' => 3,
        'width' => 12,
        'options' => pg_fetch_pairs(
            $con,
            "SELECT DISTINCT status, status FROM tbl_hd_status WHERE fabrica = $login_fabrica ORDER BY status"
        )
    ),
    'produto_referencia' => array(
        'label' => traduz('Referência do Produto'),
        'type' => 'input/text',
        'span' => 3,
        'width' => 10,
        'lupa' => array(
            'name' => 'lupa',
            'tipo' => 'produto',
            'parametro' => 'referencia'
        )
    ),
    'produto_descricao' => array(
        'label' => traduz('Descrição do Produto'),
        'type' => 'input/text',
        'span' => 4,
        'width' => 10,
        'lupa' => array(
            'name' => 'lupa',
            'tipo' => 'produto',
            'parametro' => 'descricao'
        )
    ),
);

$plugins = array(
    'datepicker',
    'mask',
    'autocomplete',
    'shadowbox'
);

include 'plugin_loader.php';
?>
<style>
    table td+td {
        text-align: right!important;
    }
    table.table th+th {
        width: 12.5%
    }
    table.table.table-bordered tr:last-child {
        font-weight: bold;
    }
    table.table.table-bordered tr:last-child td {
        border-top-width: 3px!important;
    }
    caption.titulo_tabela {
        line-height: 2em;
        padding: 0;
    }
</style>
<script type="text/javascript" src="js/modules/exporting.js"></script>

<? include "javascript_pesquisas.php" ?>

<script type="text/javascript" charset="utf-8">
    var ccWinParams    = 'scrollbars=yes,width=750,height=450,top=315,left=0';
    var printWinParams = 'width=700, height=600, top=90, left=90, scrollbars=yes, status=no, toolbar=no, location=no, directories=no, menubar=no, resizable=no, fullscreen=no';
	$(function(){
		$('#data_inicial').datepicker({startdate:'01/01/2000'});
		$('#data_final').datepicker({startDate:'01/01/2000'});
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");

        $('#btn-clear').click(function () {
            $(frm_relatorio).find('select,input').val('');
        })

        Shadowbox.init();

        $(".defeito_reclamado_procon").click(function() {
        	var defeito_reclamado = $(this).attr("reclamado");
        	var data_inicial 	  = $(this).attr("inicial");
        	var data_final        = $(this).attr("final");
        	var status 	  		  = $(this).attr("status");
        	var produto 		  = $(this).attr("produto");

        	Shadowbox.open({
                content: "callcenter_relatorio_defeito_callcenter.php?data_inicial="+data_inicial+"&data_final="+data_final+"&status="+status+"&produto="+produto+"&defeito_reclamado="+defeito_reclamado,
                player: "iframe",
                width: 700,
                height: 250
            });
        });

	});

    function AbreCallcenter(el) {
        var params = '?' + $(el).data('params');
        var url    = 'callcenter_relatorio_defeito_callcenter.php';
        janela = window.open(url + params, 'Callcenter', ccWinParams);
		janela.focus();
	}

</script>

<?php if (count($table)): ?>
<?php endif; ?>
<?php if (count($msg_erro["msg"]) > 0) { ?>
<br />
	<div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
	</div>
<?php } ?>
<p>&nbsp;</p>
<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios ')?></b>
</div>
<form name="frm_relatorio" method="POST" class="form-search form-inline tc_formulario">
<?php
$subFormCfg     = array_shift($formData);
$subFormTitulo  = $subFormCfg['name'];
$subFormID      = $subFormCfg['id'];
$subFormStretch = $subFormCfg['nomargin'] ? : true;

if ($subFormCfg['visible'] !== false) { ?>
    <div class="titulo_tabela"><?=$subFormTitulo?></div>
    <?php montaForm($formData, null, $subFormStretch); ?>
<?php } ?>
        <p>&nbsp;</p>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span10 text-right">
                <button type='submit' id='btn-search' name='btn_acao' class="btn btn-default" value='search'><?=traduz('Pesquisar')?></button>
                <button type='button' id='btn-clear'  name='btn_clr'  class="btn btn-warning"><?=traduz('Limpar')?></button>
            </div>
            <div class="span1"></div>
        </div>
        <p>&nbsp;</p>
    </form>
    <br />
<?

if(strlen($btn_acao) > 0){

    if($login_fabrica == 35 && $natureza_chamado == 'procon') {
    ?>
	    <div class="row">
	        <div id="piechart" class="span12" style="height: 440px"></div>
	    </div>
    <?php
	}

	if(strlen($msg_erro)==0){
		
        if($natureza_chamado == "duvida_produto" AND $login_fabrica == 161){
			$sql = "SELECT tbl_faq.situacao AS categoria,
				tbl_faq.faq,
				count(tbl_hd_chamado.hd_chamado) as qtde
				from tbl_hd_chamado
				join tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
				join tbl_hd_chamado_faq on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_faq.hd_chamado
				join tbl_faq on tbl_hd_chamado_faq.faq = tbl_faq.faq and tbl_faq.fabrica = $login_fabrica
				where tbl_hd_chamado.fabrica_responsavel = $login_fabrica
				and tbl_hd_chamado.data between '$data_ini 00:00:00' and '$data_fim 23:59:59'
				$cond_status
				$cond_natureza
				$cond_produto
				AND  tbl_hd_chamado.status <> 'Cancelado'
				GROUP by tbl_faq.situacao,tbl_faq.faq
				order by qtde desc
				";
		} else if($login_fabrica == 35 && $natureza_chamado == 'procon') {
			$sql = "SELECT DISTINCT ON (tbl_defeito_reclamado.defeito_reclamado)
						   tbl_hd_chamado_extra.defeito_reclamado,
						   tbl_defeito_reclamado.descricao,
						   (
							SELECT count(*) as qtde_reclamado
							FROM   tbl_hd_chamado
							JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
							WHERE tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado
							AND tbl_hd_chamado.data BETWEEN '$data_ini 00:00:00' AND '$data_fim 23:59:59'
							AND tbl_hd_chamado.fabrica = $login_fabrica
							) AS qtde_procon
						FROM tbl_hd_chamado
						JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
						LEFT JOIN tbl_defeito_reclamado ON tbl_hd_chamado_extra.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
						WHERE tbl_hd_chamado.fabrica = $login_fabrica
						AND tbl_hd_chamado.data BETWEEN '$data_ini 00:00:00' AND '$data_fim 23:59:59'
						$cond_status
						$cond_produto
						AND tbl_defeito_reclamado.duvida_reclamacao = 'PR'
				";
		} else {	
			$sql = "SELECT tbl_hd_chamado.categoria,
				'' AS faq,
				count(tbl_hd_chamado.hd_chamado) as qtde
				from tbl_hd_chamado
				join tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
				where tbl_hd_chamado.fabrica_responsavel = $login_fabrica
				and tbl_hd_chamado.data between '$data_ini 00:00:00' and '$data_fim 23:59:59'
				AND  tbl_hd_chamado.status <> 'Cancelado'
				$cond_natureza
				$cond_status
				$cond_produto
				GROUP by tbl_hd_chamado.categoria,faq
				order by qtde desc
				";
		}        

		$res = pg_exec($con,$sql);

		if(pg_numrows($res)>0 and $login_fabrica == 35){
			if(in_array($login_fabrica, array(24,161))){
				for($y=0;pg_numrows($res)>$y;$y++)
					$total_calcula_porcentagem += pg_result($res,$y,qtde);
			}

			echo "<table class='table table-bordered table-fixed'>";
			if ($login_fabrica == 35 && $natureza_chamado == 'procon') { ?>
				<tr class="titulo_tabela">
					<th colspan="3"><?=traduz('Percentual de Defeitos por Natureza Procon/JEC')?></th>
				</tr>
			<?php 
			}
			echo "<TR class='titulo_coluna'>\n";

			if ($login_fabrica == 35 && $natureza_chamado == 'procon') { ?>
				<th><?=traduz('Defeito Reclamado')?></th>
			<?php 
			} else {
				echo "<th align='left'>Status</th>\n";
			}

			echo "<th>Qtde</th>\n";

			if ($login_fabrica == 35 && $natureza_chamado == 'procon') { ?>
				<th><?=traduz('Percentual')?></th>
			<?php 
			} 

			if(in_array($login_fabrica, array(24,161)))
				echo "<th>&nbsp;%&nbsp;</th>\n";
			echo "</TR >\n";

			if ($login_fabrica == 35 && $natureza_chamado == 'procon') {
				$dadosGrafico = array(
	                0 => array('Natureza', 'Quantidade')
	            );
			}

			if ($login_fabrica == 35 && $natureza_chamado == 'procon') {
				for ($i=0;$i<pg_num_rows($res);$i++) {
					$total_qtde_procon += pg_fetch_result($res,$i,'qtde_procon');
				}
			}

			for($y=0;pg_numrows($res)>$y;$y++){
				$categoria = pg_result($res,$y,categoria);
				$faq	   = pg_result($res,$y,faq);
				if ($login_fabrica == 35 && $natureza_chamado == 'procon') {
					$defeito_procon_id   = pg_fetch_result($res,$y,'defeito_reclamado');
					$qtde      			 = pg_fetch_result($res,$y,'qtde_procon');
					$descricao_reclamado = pg_fetch_result($res, $y, 'descricao');

					$percentual_qtde_procon = (($qtde * 100) / $total_qtde_procon);

					$dadosGrafico[] = array(utf8_encode($descricao_reclamado), (int)$qtde);
				} else {
					$qtde      = pg_result($res,$y,qtde);
				}

				$cat = (empty($faq)) ? $categoria : "duvida_produto";

				echo "<TR>\n";


				echo "<TD>";

				if($login_fabrica == 24) {
					echo "<a href='callcenter_relatorio_produto.php?btn_acao=consultar&natureza_chamado=$cat&data_inicial=$data_inicial&data_final=$data_final&produto_referencia=$produto_referencia&produto_descricao=$produto_descricao' target='_blank' >";
				} else if($login_fabrica == 35 && $natureza_chamado == 'procon') {
				?>
					<a href="#" class="defeito_reclamado_procon" reclamado="<?= $defeito_procon_id ?>" inicial="<?= $data_ini ?>" final="<?= $data_fim ?>" status="<?= $status ?>" produto="<?= $produto ?>">
						<?= $descricao_reclamado ?>
					</a>	
				<?php
				} else {
					echo "<a href=\"javascript: AbreCallcenter('$xdata_inicial','$xdata_final','$produto','$cat','$status','$xperiodo','$defeito_reclamado','$faq');\" >";
				}

				if ($login_fabrica == 24) {
					$achou = false;
					foreach($assuntos as $topico => $itens) {
						foreach($itens as $label => $valor) {
							if ($valor == $categoria) {
								$rotulo = $topico . " >> " . $label;
								$achou = true;
								break;
							}
						}
					}

					if ($achou) {
					}
						elseif($categoria == 'troca_produto') $rotulo = "ANTIGOS >> Troca do Produto";
						elseif($categoria == 'reclamacao_produto') $rotulo = "ANTIGOS >> Reclamação de Produto";
						elseif($categoria == 'duvida_produto') $rotulo = "ANTIGOS >> Dúvida sobre produto";
						elseif($categoria == 'reclamacao_empresa') $rotulo = "ANTIGOS >> Reclamação de empresa";
						elseif($categoria == 'reclamacao_at') $rotulo = "ANTIGOS >> Reclamação de atendimento";
						elseif($categoria == 'sugestao') $rotulo = "ANTIGOS >> Sugestão";
						else $rotulo = "$categoria";
				}else {

					if($categoria == 'troca_produto') $rotulo = traduz("Troca do Produto");
                    elseif($categoria == 'reclamacao_produto') $rotulo = traduz("Reclamação de Produto");
					elseif($categoria == 'duvida_produto') $rotulo = traduz("Dúvida sobre produto");
					elseif($categoria == 'reclamacao_empresa') $rotulo = traduz("Reclamação de empresa");
					elseif($categoria == 'reclamacao_at') $rotulo = traduz("Reclamação de atendimento");
					else $rotulo = "$categoria";
				}

				echo $rotulo;

				$grafico_status[] = substr($rotulo, 0, 40);
				$grafico_qtde[] = $qtde;
				$total = $total + $qtde;

				$total_qtde += $qtde;
				$registro += 1;

				echo "</a></TD>\n";
				echo "<TD class='tac'>$qtde</TD>\n";

				if ($login_fabrica == 35 && $natureza_chamado == 'procon') { ?>
					<th><?= number_format($percentual_qtde_procon, 2) ?>%</th>
				<?php 
				} 

				if($login_fabrica == 24){
					echo "<TD>".number_format((($qtde/$total_calcula_porcentagem)*100),2)." % </TD>\n";
				}
				if($login_fabrica == 161){
					echo "<TD>".round(number_format((($qtde/$total_calcula_porcentagem)*100),2))." % </TD>\n";
				}
				echo "</TR >\n";
			}


			if($login_fabrica==2 || $login_fabrica == 24 || ($login_fabrica == 35 && $natureza_chamado == 'procon')){//HD 36906 9/10/2008
			echo "<TR>\n";
				echo "<TD nowrap><B>".traduz('Total')."</B></TD>\n";
				echo "<TD class='tac' nowrap>$total</TD>\n";
				echo "<TD class='tac' nowrap>100%</TD>\n";
			echo "</TR >\n";
			}
			echo "</table>";


			$media = $total_qtde / $registro;

			for ($i=0; count($grafico_status)>$i ; $i++){
				if($login_fabrica == 24 AND count($grafico_status) > 10){
					if ($grafico_qtde[$i] > $media ){
						$grafico_status_fim[] = $grafico_status[$i];
						$grafico_qtde_fim[] = $grafico_qtde[$i];
					}else{
						$outros += $grafico_qtde[$i];
					}
				}else{
					$grafico_status_fim[] = $grafico_status[$i];
					$grafico_qtde_fim[] = $grafico_qtde[$i];
				}
			}

			if($login_fabrica == 24 AND count($grafico_status) > 10){
				$grafico_status_fim[] = "OUTROS";
				$grafico_qtde_fim[] = $outros;
			}

		if ($login_fabrica != 35 || ($login_fabrica == 35 && $natureza_chamado != 'procon')) {
			echo "<BR><BR>";
			include ("../jpgraph2/jpgraph.php");
			include ("../jpgraph2/jpgraph_pie.php");
			include ("../jpgraph2/jpgraph_pie3d.php");
			$img = time();
			$image_graph = "png/4_call$img.png";

			// seleciona os dados das médias
			setlocale (LC_ALL, 'et_EE.ISO-8859-1');

			if ($login_fabrica == 24) {
				$graph = new PieGraph(900,600,"auto");
			}
			else {
				$graph = new PieGraph(1000,350,"auto");
			}
			$graph->SetShadow();

			$graph->title->Set("Relatório de Reclamação $data_inicial - $data_final");
			$p1 = new PiePlot3D($grafico_qtde_fim);
			$p1->SetAngle(35);
			$p1->SetSize(0.4);
			$p1->SetCenter(0.32,0.7); // x.y
			//$p1->SetLegends($gDateLocale->GetShortMonth());
			$p1->SetLegends($grafico_status_fim);
			//$p1->SetSliceColors(array('blue','red'));
			$graph->Add($p1);
			$graph->Stroke($image_graph);
			echo "\n\n<img src='$image_graph'>\n\n";
			//	echo "<BR><a href='callcenter_relatorio_atendimento_xls.php?data_inicial=$xdata_inicial&data_final=$xdata_final&produto=$produto&natureza_chamado=$natureza_chamado&status=$status&imagem=$image_graph' target='blank'>Gerar Excel</a>";

			if($login_fabrica==2){//hd 36906 9/10/2008
				$title = traduz("RELATORIO DE NATUREZA DE CHAMADO");
				echo "<BR><BR>";
				echo "<A HREF=\"javascript:abrir('impressao_callcenter.php?condicoes=$condicoes;$title')\">";
				echo "<IMG SRC=\"imagens/btn_imprimir_azul.gif\" BORDER='0' ALT=''>";
				echo "</A>";
			}

			}else{
				if ($login_fabrica != 35 || ($login_fabrica == 35 && $natureza_chamado != 'procon')) {
					echo "<center>Nenhum Resultado Encontrado</center>";
				}
			}
		}

	    if (is_array($table) and count($table)) {
	       	 	if ($login_fabrica != 35 || ($login_fabrica == 35 && $natureza_chamado != 'procon')) { 
	            	echo array2table($table, $title);
	            }
			?>
			</div>
			<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
		    <script type="text/javascript">
		    google.charts.load('current', {'packages':['corechart']});
		    google.charts.setOnLoadCallback(drawChart);

		    function drawChart() {

		    	<?php if ($login_fabrica == 35 && $natureza_chamado == 'procon') { ?>
		    		var data = google.visualization.arrayToDataTable(<?= json_encode($dadosGrafico) ?>);

		    	<?php } else { ?>
		    		var data = google.visualization.arrayToDataTable(<?=$jsonData?>);
		    	<?php 
		    	}
		    	?>

		        var chart = new google.visualization.PieChart(document.getElementById('piechart'));
		        chart.draw(data,
		            {title: '<?=$title?>', is3D: true}
		        );
		      }
	    </script>
		<?php 
		} else {
	            echo traduz("<h4 class='alert alert-warning'>Nenhum Resultado Encontrado</h4>");
	    }
	}
}

include "rodape.php";

