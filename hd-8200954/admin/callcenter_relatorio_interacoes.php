<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios = "call_center";
include 'autentica_admin.php';

include 'funcoes.php';

$url_base     = "https://posvenda.telecontrol.com.br/assist/"; // Online;

$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
	$data_inicial = $_POST['data_inicial'];
	$data_final   = $_POST['data_final'];
	$produto_referencia = $_POST['produto_referencia'];
	$produto_descricao  = $_POST['produto_descricao'];
	$natureza_chamado   = $_POST['natureza_chamado'];
	$status             = $_POST['status'];
	$atendente          = $_POST['xatendente'];

	if(in_array($login_fabrica, array(169,170))){
		$origem = $_POST["origem"];

		if(strlen(trim($origem)) > 0){
			$cond_origem = "AND tbl_hd_chamado_extra.hd_chamado_origem = $origem ";
		}
	}

	$cond_1 = " 1 = 1 ";
	$cond_2 = " 1 = 1 ";
	$cond_3 = " 1 = 1 ";
	$cond_4 = " 1 = 1 ";
	if(strlen($data_inicial)>0 and $data_inicial <> "dd/mm/aaaa"){
		$xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
		$xdata_inicial = str_replace("'","",$xdata_inicial);
	}else{
		$msg_erro = traduz("Data Inválida");
	}

	if(strlen($data_final)>0 and $data_final <> "dd/mm/aaaa"){
		$xdata_final =  fnc_formata_data_pg(trim($data_final));
		$xdata_final = str_replace("'","",$xdata_final);
	}else{
		$msg_erro = traduz("Data Inválida");
	}

	if(strlen($msg_erro)==0){
		$dat = explode ("/", $data_inicial );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $msg_erro = traduz("Data Inválida");
	}
	if(strlen($msg_erro)==0){
		$dat = explode ("/", $data_final );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $msg_erro = traduz("Data Inválida");
	}

	if($xdata_inicial > $xdata_final)
		$msg_erro = traduz("Data Inválida");

	if(strlen($produto_referencia)>0){
		$sql = "SELECT produto from tbl_produto where referencia='$produto_referencia' limit 1";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$produto = pg_result($res,0,0);
			$cond_1 = " tbl_hd_chamado_extra.produto = $produto ";
		}
	}

	if (strlen($atendente)>0){
		$cond_atend = "AND tbl_hd_chamado.atendente = $atendente";
	}

	if(strlen($natureza_chamado)>0){
		$cond_2 = " tbl_hd_chamado.categoria = '$natureza_chamado' ";
	}elseif($login_fabrica == 85 and strlen(trim($natureza_chamado))==0){
		$cond_2 = " tbl_hd_chamado.categoria <> 'garantia_estendida' ";
	}

	if(strlen($status)>0){
		if($login_fabrica == 74 AND $status == "nao_resolvido"){
			$cond_3 = " lower(tbl_hd_chamado.status) <> 'resolvido'  ";
		}else{
			$cond_3 = " tbl_hd_chamado.status = '$status'  ";
		}
	}
	if($login_fabrica==6){
		$cond_4 = " tbl_hd_chamado.status <> 'Cancelado'  ";
	}

	if($login_fabrica==2){
		$condicoes = $produto . ";" . $natureza_chamado . ";" . $status . ";" . $posto . ";" . $xdata_inicial . ";" .$xdata_final;
	}
}
$layout_menu = "callcenter";
$title = traduz("RELATÓRIO MAIOR TEMPO ENTRE INTERAÇÕES");

include "cabecalho_new.php";

$plugins = array(
	"maskedinput",
    "datepicker",
    "shadowbox"
);

include "plugin_loader.php";
?>

<script type="text/javascript" src="js/grafico/highcharts_v3.js"></script>

<script type="text/javascript">

	$(function(){
		$("#data_inicial").datepicker().mask("99/99/9999");
		$("#data_final").datepicker().mask("99/99/9999");

		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

		var chart = $('#chart').html();
		var div = $('#chart').parent('div');

		div.html('');
		div.highcharts($.parseJSON(chart));

	});

	function retorna_produto (retorno) {
        $("#produto_referencia").val(retorno.referencia);
        $("#produto_descricao").val(retorno.descricao);
    }

</script>
<script language='javascript' src='../ajax.js'></script>
<script>
function AbreCallcenter(data_inicial,data_final,produto,natureza,status,tipo,atendente,origem){
janela = window.open("callcenter_relatorio_interacoes_callcenter.php?data_inicial=" +data_inicial+ "&data_final=" +data_final+ "&produto=" +produto+ "&natureza=" +natureza+ "&status=" +status+"&tipo="+tipo+"&atendente="+atendente+"&origem="+origem, "Callcenter",'scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}

/* POP-UP IMPRIMIR */
function abrir(URL) {
	var width = 700;
	var height = 600;
	var left = 90;
	var top = 90;

	window.open(URL,'janela', 'width='+width+', height='+height+', top='+top+', left='+left+', scrollbars=yes, status=no, toolbar=no, location=no, directories=no, menubar=no, resizable=no, fullscreen=no');
}

</script>

	<? if(strlen($msg_erro)>0){ ?>
		<div class='alert alert-danger'><h4><? echo $msg_erro; ?></h4></div>
	<? } ?>
<div class="row">
   	<b class="obrigatorio pull-right">* <?=traduz('Campos obrigatórios ')?></b>
</div>
<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" class="form-search form-inline tc_formulario">

	<div class='titulo_tabela'>
		<?=traduz('Parâmetros de Pesquisa')?>
	</div>
	<br />
	<div class='row-fluid'>
		<div class='span2'></div>
            <div class='span4'>
            	<div class='control-group <?=($msg_erro == "Data Inválida") ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'><?=traduz('Data Inicial')?></label>
					<div class='controls controls-row'>
						<h5 class='asteristico'>*</h5>
						<input class='span4' type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>">
						<!--<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript:showCal('DataInicial')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">-->
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=($msg_erro == "Data Inválida") ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'><?=traduz('Data Final')?></label>
					<div class='controls controls-row'>
						<h5 class='asteristico'>*</h5>
						<input class='span4' type="text" name="data_final" id="data_final" size="12" maxlength="10" value="<? if (strlen($data_final) > 0) echo $data_final; ?>" >
						<!--<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript:showCal('DataFinal')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">-->
					</div>
				</div>
			</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
				<label class='control-label' for='produto_referencia'><?=traduz('Ref. Produto')?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" id="produto_referencia" name="produto_referencia" size="12" class='frm' maxlength="20" value="<? echo $produto_referencia ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        	<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='produto_descricao'><?=traduz('Descrição')?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" id="produto_descricao" name="produto_descricao" size="12" class='frm' value="<? echo $produto_descricao ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        	<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for=''><?=traduz('Natureza')?></label>
				<div class='controls controls-row'>
					<select name='natureza_chamado' class='controls'>
					<option value=''></option>
					<?PHP
						//HD39566
						$sqlx = "SELECT nome            ,
										descricao
								FROM tbl_natureza
								WHERE fabrica=$login_fabrica
								AND ativo = 't'
								ORDER BY nome";

						$resx = pg_exec($con,$sqlx);
							if(pg_numrows($resx)>0){
								for($y=0;pg_numrows($resx)>$y;$y++){
									$nome     = trim(pg_result($resx,$y,nome));
									$descricao     = trim(pg_result($resx,$y,descricao));
									echo $nome;
									echo "<option value='$nome'";
										if($natureza_chamado == $nome) {
											echo "selected";
										}
									echo ">$descricao</option>";
								}

							}
					?>

					</select>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group'>
			<label class='control-label' for='status'><?=traduz('Status')?></label>
				<div class='controls controls-row'>
					<div class='span4'>
						<select name="status" class='controls'>
							<option value=''></option>
							<?
							if($login_fabrica == 74){
								$selected = ($status == "nao_resolvido") ? "selected" : "";
								echo "<option value='nao_resolvido' $selected>".traduz('Não resolvido')."</option>";
							}
								$sql = "select distinct status from tbl_hd_status where fabrica = $login_fabrica order by status";
								$res = pg_exec($con,$sql);
								if(pg_numrows($res)>0){
									for($x=0;pg_numrows($res)>$x;$x++){
										$xstatus = pg_result($res,$x,status);
										echo "<option value='$xstatus'"; if ($xstatus == $status) echo "SELECTED"; echo ">$xstatus</option>";
									}

								}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for=''><?=traduz('Atendente')?></label>
				<div class='controls controls-row'>
					<select name="xatendente" class="frm" >
						 <option value=''></option>
						<?

						if($login_fabrica == 74){

							$tipo = "producao"; // teste - producao

							$admin_fale_conosco = ($tipo == "producao") ? 6409 : 6437;

							$cond_admin_fale_conosco = " AND tbl_admin.admin NOT IN ($admin_fale_conosco) ";

						}

						$sql = "SELECT admin, login
									from tbl_admin
									where fabrica = $login_fabrica
									and ativo is true
									and (privilegios like '%call_center%' or privilegios like '*') $cond_admin_fale_conosco order by login";
							$res = pg_exec($con,$sql);
							if(pg_numrows($res)>0){
								for($i=0;pg_numrows($res)>$i;$i++){
									$atendente = pg_result($res,$i,admin);
									$atendente_nome = pg_result($res,$i,login);
									echo "<option value='$atendente'";
										if($xatendente == $atendente) {
												echo "selected";
											}
									echo ">$atendente_nome</option>";
								}
							}
						?>
					</select>
				</div>
			</div>
		</div>
		<?php if(in_array($login_fabrica, array(169,170))){ ?>
		<div class='span4'>
			<div class='control-group <?=(in_array("origem", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='origem'><?=traduz('Origem')?></label>
				<div class='controls controls-row'>
					<div class='span4'>
						<select name="origem" id="origem">
							<option value=""></option>
							<?php

								$sql = "SELECT hd_chamado_origem, descricao FROM tbl_hd_chamado_origem WHERE fabrica = $login_fabrica and ativo IS TRUE order by descricao";
								$res = pg_query($con,$sql);
								foreach (pg_fetch_all($res) as $key) {
									$selected_origem = ( isset($origem) and ($origem == $key['hd_chamado_origem']) ) ? "SELECTED" : '' ;
								?>
									<option value="<?php echo $key['hd_chamado_origem']?>" <?php echo $selected_origem ?> >
										<?php echo $key['descricao']?>
									</option>
								<?php
								}
							?>
						</select>
					</div>
					<div class='span2'></div>
				</div>
			</div>
		</div>
		<?php } ?>
		<div class='span2'></div>
	</div>
			<input class='btn' type='submit' style="cursor:pointer" name='btn_acao' value='<?=traduz('Consultar')?>'>
			<br /><br />
</FORM>

<br />
<?

if(strlen($btn_acao)>0){

	if(strlen($msg_erro)==0){

		if($login_fabrica == 74){
            $cond_admin_fale_conosco = " AND tbl_hd_chamado.status IS NOT NULL ";
        }

$sql = "
		SELECT	(dias/item)::integer   AS media,
				count(*) as qtde
		FROM(
			SELECT	hd_chamado,
					CASE WHEN (dias_aberto - feriado - fds) = 0 THEN 1
					ELSE (dias_aberto - feriado - fds)
					END AS dias,
					item
			FROM (
				SELECT	X.hd_chamado,
						(	SELECT COUNT(*)
							FROM fn_calendario(X.data_abertura::date,X.ultima_data::date)
							where nome_dia in('Domingo','Sábado')
						) AS fds,
						(	SELECT COUNT(*)
							FROM tbl_feriado
							WHERE tbl_feriado.fabrica = 6 AND tbl_feriado.ativo IS TRUE
							AND tbl_feriado.data BETWEEN X.data_abertura::date AND X.ultima_data::date
						) AS feriado,
						X.item ,
						EXTRACT('days' FROM X.ultima_data::timestamp - X.data_abertura ::timestamp) AS dias_aberto,
						X.data_abertura, X.ultima_data
				FROM(	SELECT	tbl_hd_chamado.hd_chamado,
								TO_CHAR(tbl_hd_chamado.data,'YYYY-MM-DD') AS data_abertura,
								COUNT(tbl_hd_chamado_item.hd_chamado) AS item,
								(	SELECT to_char(tbl_hd_chamado_item.data,'YYYY-MM-DD')
									FROM tbl_hd_chamado_item
									WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
									ORDER BY tbl_hd_chamado_item.hd_chamado_item DESC LIMIT 1
								) AS ultima_data
						FROM tbl_hd_chamado
						JOIN tbl_hd_chamado_item using(hd_chamado)
						WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
						AND tbl_hd_chamado_item.interno is not true
						and tbl_hd_chamado.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'
						$cond_atend
						and $cond_1
						and $cond_2
						and $cond_3
						and $cond_4

						$cond_admin_fale_conosco
						GROUP BY tbl_hd_chamado.hd_chamado, tbl_hd_chamado.data
				) AS X
			) as Y
		) as w
		group by media
		order by media

";

//select date_part('day',interval '02:04:25.296765');
$sql = "SELECT count(X.hd_chamado) as qtde	,
				X.intervalo
		FROM (
		SELECT tbl_hd_chamado.hd_chamado,
				CASE WHEN
					(	SELECT MAX( DATE_PART('day',(tbl_hd_chamado_item.tempo_interacao)::interval))
						FROM  tbl_hd_chamado_item
						WHERE tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado
						AND   tbl_hd_chamado_item.interno IS NOT TRUE
						AND tbl_hd_chamado_item.data >= tbl_hd_chamado.data
						LIMIT 1) IS NULL THEN '0'
				WHEN
					(	SELECT MAX( DATE_PART('day',(tbl_hd_chamado_item.tempo_interacao)::interval))
						FROM  tbl_hd_chamado_item
						WHERE tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado
						AND   tbl_hd_chamado_item.interno IS NOT TRUE
						AND tbl_hd_chamado_item.data >= tbl_hd_chamado.data
						LIMIT 1) < 0 THEN '0'
				ELSE
					(	SELECT MAX( DATE_PART('day',(tbl_hd_chamado_item.tempo_interacao)::interval))
						FROM  tbl_hd_chamado_item
						WHERE tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado
						AND   tbl_hd_chamado_item.interno IS NOT TRUE
						AND tbl_hd_chamado_item.data >= tbl_hd_chamado.data
						LIMIT 1)
				END AS intervalo
		FROM tbl_hd_chamado
		JOIN tbl_hd_chamado_extra     on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
		WHERE tbl_hd_chamado.fabrica  = $login_fabrica
		AND   tbl_hd_chamado.data BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59'
		AND tbl_hd_chamado.posto is null
		$cond_atend
		AND   $cond_1
		AND   $cond_2
		AND   $cond_3
		AND   $cond_4
		$cond_origem
		) AS X";

		if(in_array($login_fabrica, array(169,170,174))){
			$sql .= " group by intervalo order by intervalo desc";
		}else{
			$sql .= " group by intervalo order by qtde desc, intervalo";
		}

		$qtdeSum = 0;
		$res = pg_exec($con,$sql);
		$arrayToChart = array();
		if(pg_numrows($res)>0){
			echo "<table class='table table-striped table-bordered table-fixed'>";
			echo "<TR class='titulo_coluna'>\n";
			echo "<th>".traduz('Quantidade de Dias')."</TH>\n";
			echo "<Th>".traduz('Quantidade de Chamados')."</Th>\n";
			echo "</TR >\n";
			for($y=0;pg_numrows($res)>$y;$y++){
				$intervalo = pg_result($res,$y,intervalo);
				$qtde   = pg_result($res,$y,qtde);
				$arrayToChart[$intervalo] = $qtde;

				if($intervalo=="0"){$xintervalo = traduz("Mesmo dia");}else{
					$xintervalo = "$intervalo dia(s)";
				}

				$qtdeSum += $qtde;

				if ($y % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#F7F5F0';}
				echo "<TR bgcolor='$cor'>\n";
				echo "<TD align='left' nowrap><a href=\"javascript: AbreCallcenter('$xdata_inicial','$xdata_final','$produto','$natureza_chamado','$status','$intervalo','$xatendente','$origem')\">$xintervalo</a></TD>\n";
				echo "<TD class='tac' align='center' nowrap>$qtde</TD>\n";
				echo "</TR >\n";
			}
			//$chart['series'][] = $pieChart;
			echo '<tr><td>'.traduz('Total de Chamados:').'</td><td class="tac">'.$qtdeSum.'</td></tr>';
			echo "</table>";
			$height = 150+count($arrayToChart)*15;
			$subtitle = DateTime::createFromFormat('Y-m-d',$xdata_inicial)->format('d/m/Y');
			$subtitle .= ' - ';
			$subtitle .= DateTime::createFromFormat('Y-m-d',$xdata_final)->format('d/m/Y');
			echo "<br /><br />";

			if($login_fabrica==6){
				$txt =	"PROCEDIMENTO OPERACIONAL<br />
				SAC – PO 09- SERVIÇO DE ATENDIMENTO AO CLIENTE<br />
				PROCESSO : Pós venda<br />
				ATIVIDADE : Monitoramento da qualidade de campo de produtos<br />            / Atendimento ao cliente.<br />
				OBJETIVO : Prestar um serviço de atendimento ao cliente, a fim <br />         de esclarecer dúvidas e eventuais falhas <br />        em relação ao produto ou serviço.
				META :   Atender 90% das ocorrências abertas mensalmente devem <br />        ser respondidas em até 3 dias da abertura.";
				echo '<div style="text-align:center;">'.$txt.'</div>';
			}

			echo '<div style="width:700px; min-height:'.$height.'px; margin: 0 auto" ><pre id="chart" hidden="hidden">';
			echo json_encode(makeBarChart($arrayToChart,$subtitle));
			echo '</pre></div>';
		} else{
			echo "
				<div class='container'>
					<div class='alert alert-warning'><h4>".traduz('Nenhum resultado encontrado!')."</h4></div>
				</div>
				<br />";
		}
	}
}

?>

<p>

<? include "rodape.php" ?>
<?php
	function makePieChart($result){
		$chart = array();
		$chart['title'] = array('text'=>'titulo');
		$chart['subtitle'] = array('text'=>'subtitulo');
		$chart['credits'] = array(
			'enabled' => false
		);
		$chart['tooltip'] = array(
			'pointFormat' => '{series.name}: {point.y:1f} ({point.percentage:.1f}%)'
		);
		$pie = array('name'=>'Chamados','type'=>'pie','data'=>array());
		$chart['series'] = array();
		foreach($result as $k => $v){
			if($k == 0)
				$name = 'Mesmo Dia';
			else
				$name = $k.' Dia(s)';
			$pie['data'][] = array($name,(int)$v);
		}
		$chart['series'][] = $pie;
		return $chart;
	}

	function makeBarChart($result,$subtitle=''){
		$chart = array();
		$chart['chart'] = array('type'=>'bar');
		$chart['title'] = array('text'=>utf8_encode('Relatório maior tempo entre interações'));
		$chart['subtitle'] = array('text'=>$subtitle);
		$chart['xAxis'] = array(
			'type' => 'category'
		);
		$chart['yAxis'] = array(
			'type'=>'logarithmic',
			'title'=>array(
				'text'=>'Chamados'
			)
		);
		$chart['plotOptions'] = array(
			'series'=>array(
				'pointPadding'=>0,
				'groupPadding'=>0,
				'dataLabels' => array(
					'enabled' => true,
					'format' => '{point.y: 1f} ({point.percentual: .2f}%)'
				)
			)
		);
		$chart['credits'] = array(
			'enabled' => false
		);
		$chart['tooltip'] = array(
			'followPointer' => true
		);
		$chart['legend'] = array(
			'enabled' => false
		);
		$chart['series']= array();
		$serie =  array(
			'name' => utf8_encode('Chamados'),
			'colorByPoint' => true,
			'data' => array()
		);
		$sum = array_sum($result);
		foreach($result as $k => $v){
			if($k == 0)
				$name = 'Mesmo Dia';
			else
				$name = $k.' Dia(s)';
			$serie['data'][] = array(
				'name'=>$name,
				'y'=>(int)$v,
				'percentual' => ($v/$sum)*100
				);
		}
		$chart['series'][] = $serie;
		return $chart;
	}