<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';

if (isset($_POST)){

	//RECEBE PARAMETROS PARA PESQUISA
	$data_inicial  = $_POST['data_inicial'];
	$data_final    = $_POST['data_final'];
	$codigo_posto  = trim($_POST['codigo_posto']);
	$posto_nome    = trim($_POST['posto_nome']);
	$posto         = $_POST['posto'];
	$tipo_pesquisa = ($_POST['tipo_pesquisa'] <> 'TODOS') ? $_POST['tipo_pesquisa'] : '' ;

	list($di, $mi, $yi) = explode("/", $data_inicial);
    list($df, $mf, $yf) = explode("/", $data_final);
	$aux_data_inicial = "$yi-$mi-$di 00:00:00";
	$aux_data_final   = "$yf-$mf-$df 23:59:59";

	$conditionTipoPesquisa = (!empty($tipo_pesquisa)) ? " AND tbl_tipo_pergunta.tipo_pergunta = $tipo_pesquisa " : '' ;

	$conditionPosto = (!empty($posto)) ? " AND tbl_hd_chamado_extra.posto = $posto " : '' ;

}

$layout_menu = 'callcenter';
$title = "PESQUISA DE PERGUNTAS DO CALLCENTER";
include 'cabecalho.php';
?>

<style type="text/css">
	@import "../plugins/jquery/datepick/telecontrol.datepick.css";
	.formulario{
	    background-color:#D9E2EF;
	    font:11px Arial;
	    text-align:left;
	    margin:auto;
	    width:700px;
	}

	.msg_erro{
	    background-color:#FF0000;
	    font: bold 16px "Arial";
	    color:#FFFFFF;
	    width:700px;
	    margin:auto;
	    text-align:center;
	}

	.sucesso{
	    background-color:#008000;
	    font: bold 14px "Arial";
	    color:#FFFFFF;
	    width:700px;
	    margin:auto;
	    text-align:center;
	}

	.titulo_tabela{
	    background-color:#596d9b;
	    font: bold 14px "Arial";
	    color:#FFFFFF;
	    text-align:center;
	}

	.titulo_coluna{
	    background-color:#596d9b !important;
	    font: bold 11px "Arial";
	    color:#FFFFFF;
	    text-align:center;
	}

	table.tabela{
		width:700px;
		margin:auto;
		background-color: #F7F5F0;
	}

	table.tabela tr td{
	    font-family: verdana;
	    font-size: 11px;
	    border-collapse: collapse;
	    border:1px solid #596d9b;
	}

	.hideTr{
		display:none;
	}
	
</style>

<script src="js/jquery-1.8.3.min.js"></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type="text/javascript" src="js/jquery.maskedinput2.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<script type="text/javascript">
	
	$(function() {

		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");

		/* Busca AutoComplete pelo Código */
		$("#codigo_posto").autocomplete("relatorio_pesquisas_chamado_ajax.php?ajax=true&tipo_busca=posto&busca=codigo", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[2];}
		});

		$("#codigo_posto").result(function(event, data, formatted) {
			$("#posto_nome").val(data[1]) ;
			$("#posto").val(data[3]) ;
		});

		/* Busca AutoComplete pelo Nome */
		$("#posto_nome").autocomplete("relatorio_pesquisas_chamado_ajax.php?ajax=true&tipo_busca=posto&busca=nome", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[1];}
		});

		$("#posto_nome").result(function(event, data, formatted) {
			$("#codigo_posto").val(data[2]) ;
			$("#posto").val(data[3]) ;
		});

		//ENVIA PARA O PROGRAMA DO AJAX VALIDAR O FORM
		$('#btn_pesquisa').click(function(){

			$.ajax({

				type: "GET",
				url: "relatorio_pesquisas_chamado_ajax.php",
				data: "ajax=true&validar=true&"+$('form[name=frm_pesquisa]').find('input').serialize(),
				complete: function(http) {
					
					results = http.responseText;
					results = results.split('|');
					if (results[0] == 1){

						$('div.msg_erro').html(results[1]);

					}else{
						$('form[name=frm_pesquisa]').submit();
					}
				}

			});

		});

		//EXIBE RESPOSTA
		$('.btn_ver_resposta').click(function(){
			relBtn = $(this).attr('rel');

			if ($('tr#'+relBtn).hasClass('hideTr')){
				$('tr#'+relBtn).toggle('slow');
				$('tr#'+relBtn).removeClass('hideTr');
				$(this).html(' Ver Respostas <img src="imagens/barrow_up.png"> ');
			}else{
				$('tr#'+relBtn).toggle('slow');
				$('tr#'+relBtn).addClass('hideTr');
				$(this).html(' Ver Respostas <img src="imagens/barrow_down.png"> ');
			}

		});

		//ZERA o hidden posto se quando der blur no codigo e o valor estiver vazio
		$("#codigo_posto").blur(function() {
			if ($(this).val().length == 0){
				$("#posto").val('');
			}
		});

		//ZERA o hidden "posto" se quando der blur no nome do posto e o valor estiver vazio
		$("posto_nome").blur(function() {
			if ($(this).val().length == 0){
				$("#posto").val('');
			}
		});

	});

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

</script>


<div class="msg_erro"></div>
<div class="sucesso"></div>

<form action="<?=$PHP_SELF?>" method="post" name="frm_pesquisa">
<input type="hidden" name="posto" id="posto" value="<?=$posto?>">
<table class="formulario">
	<tr class="titulo_tabela">
		<th colspan='6'>Parâmetros de Pesquisa</th>
	</tr>

	<tr>	<td colspan='6'>&nbsp;</td>	</tr>

	<tr>
		<td>&nbsp;</td>
		<td>Data Inicial:</td>
		<td>
			<input type="text" name="data_inicial" id="data_inicial" class='frm' size="12" value="<?=$data_inicial?>">
		</td>
		<td>Data Final:</td>
		<td>
			<input type="text" name="data_final" id="data_final" class='frm' size="12" value="<?=$data_final?>">
		</td>
		<td>&nbsp;</td>
	</tr>

	<tr>
		<td>&nbsp;</td>
		<td>Posto Código:</td>
		<td>
			<input type="text" name="codigo_posto" id="codigo_posto" class='frm' value="<?=$codigo_posto?>">
		</td>
		<td>Posto Nome:</td>
		<td>
			<input type="text" name="posto_nome" id="posto_nome" class='frm' value="<?=$posto_nome?>">
		</td>
		<td>&nbsp;</td>
	</tr>


	<tr>
		<td>&nbsp;</td>
		<td colspan="2">
			<fieldset>
				<legend>Tipo de Pesquisa:</legend>
				<?php  
				$sql = "          SELECT tbl_tipo_pergunta.descricao,tbl_tipo_pergunta.tipo_pergunta 
						FROM 	tbl_tipo_pergunta
						JOIN     tbl_tipo_relacao using (tipo_relacao)
						WHERE 	tbl_tipo_pergunta.fabrica = $login_fabrica 
						AND tbl_tipo_relacao.sigla_relacao = 'C'
						AND 	tbl_tipo_pergunta.ativo";

				$res = pg_query($con,$sql);

				if (pg_num_rows($res)>0) {
					if ($_POST['tipo_pesquisa'] == 'TODOS'){
						$checked = "CHECKED";
					}
					?>
					<input type="radio" name="tipo_pesquisa" id="todosPesquisa" <?=$checked?> value="TODOS" > <label for="todosPesquisa">TODOS</label>
					<br>
					<?
					for ($i=0; $i < pg_num_rows($res); $i++) { 

						$tipo_pergunta           = pg_fetch_result($res, $i, 'tipo_pergunta');
						$descricao_tipo_pergunta = pg_fetch_result($res, $i, 'descricao');

						$checked = ($tipo_pergunta == $tipo_pesquisa) ? "CHECKED" : '' ;
						?>
							<input type="radio" name="tipo_pesquisa" id="<?=$tipo_pergunta?>" <?=$checked?> value='<?=$tipo_pergunta?>'>
							<label for="<?=$tipo_pergunta?>"><?=$descricao_tipo_pergunta?></label>
							<br>
							
						<?
					}
				}
				?>
			</fieldset>
		</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>

	<tr>	<td colspan='6'>&nbsp;</td>	</tr>
	
	<tr>
		<td colspan='6' align="center">
			<input type="button" value="Pesquisar" id="btn_pesquisa">			
		</td>
	</tr>

	<tr>	<td colspan='6'>&nbsp;</td>	</tr>
</table>

</form>

<br>

<?php  
if (count($_POST)>0){

	//PROGRAMA QUE VAI GERAR O XLS
	include 'relatorio_pesquisas_chamado_xls.php';

	//PESQUISA OS CHAMADOS DE ACORDO COM OS PARÂMETROS PASSADOS
	$sql = "SELECT  distinct tbl_hd_chamado.hd_chamado,
					TO_CHAR(tbl_resposta.data_input, 'DD/MM/YYYY HH24:MI:SS') AS data,
					tbl_tipo_pergunta.descricao,
					tbl_admin.nome_completo
			FROM tbl_hd_chamado 
			JOIN tbl_hd_chamado_extra on(tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado)
			JOIN tbl_admin    on(tbl_hd_chamado.atendente = tbl_admin.admin) 
			LEFT JOIN tbl_resposta on(tbl_hd_chamado.hd_chamado = tbl_resposta.hd_chamado) 
			JOIN tbl_pergunta on(tbl_resposta.pergunta = tbl_pergunta.pergunta) 
			JOIN tbl_tipo_pergunta on(tbl_pergunta.tipo_pergunta = tbl_tipo_pergunta.tipo_pergunta) 
			WHERE tbl_hd_chamado.status = 'Resolvido' 
			AND tbl_hd_chamado.fabrica = $login_fabrica  
			and tbl_resposta.pesquisa is null
			AND tbl_resposta.data_input between '$aux_data_inicial' and '$aux_data_final' 
			$conditionTipoPesquisa 
			$conditionPosto  
			;
	";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res)>0) {
		
	?>

		<table class="tabela">
			<tr class="titulo_coluna">
				<th>Atendimento</th>
				<th>Data Resposta</th>
				<th>Atendente</th>
				<th>Tipo Pesquisa</th>
				<th>Ação</th>
			</tr>
			
		<?
		foreach (pg_fetch_all($res) as $result => $value) {
			
			//PEGA A DESCRICAO DO TIPO DA PERGUNTA
			$sqlTipoPergunta = "SELECT distinct tbl_tipo_pergunta.tipo_pergunta,tbl_tipo_pergunta.descricao
							FROM tbl_tipo_pergunta 
							JOIN tbl_pergunta on (tbl_tipo_pergunta.tipo_pergunta = tbl_pergunta.tipo_pergunta)
							JOIN tbl_resposta on (tbl_pergunta.pergunta = tbl_resposta.pergunta)
							WHERE tbl_tipo_pergunta.fabrica = $login_fabrica
							AND tbl_pergunta.fabrica = $login_fabrica 
							and tbl_resposta.pesquisa is null
							AND tbl_resposta.hd_chamado = ".$value['hd_chamado']."
			";
			
			$resTipoPergunta = pg_query($con,$sqlTipoPergunta);
			$TipoPergunta = (pg_num_rows($resTipoPergunta)>0) ? pg_fetch_result($resTipoPergunta, 0, 0) : '' ;
			$TipoPerguntaDescricao = (pg_num_rows($resTipoPergunta)>0) ? pg_fetch_result($resTipoPergunta, 0, 1) : '' ;

			//PERGUNTAS E RESPOSTAS DO CHAMADOS
			$sqlPergunta = "SELECT  tbl_pergunta.descricao,
									tbl_resposta.txt_resposta
							FROM tbl_tipo_pergunta 
							JOIN tbl_pergunta on (tbl_tipo_pergunta.tipo_pergunta = tbl_pergunta.tipo_pergunta)
							JOIN tbl_resposta on (tbl_pergunta.pergunta = tbl_resposta.pergunta )
							WHERE tbl_tipo_pergunta.fabrica = $login_fabrica
							AND tbl_pergunta.fabrica = $login_fabrica 
							and tbl_resposta.pesquisa is null
							AND tbl_resposta.hd_chamado = ".$value['hd_chamado']." 
							ORDER BY tbl_pergunta.pergunta
			";
			
			$resPergunta = pg_query($con,$sqlPergunta);

			?>
			<tr>
				<td><?echo $value['hd_chamado']?></td>
				<td><?echo $value['data']?></td>
				<td><?echo $value['nome_completo']?></td>
				<td><?echo $value['descricao']?></td>
				<td>
					
					<button class="btn_ver_resposta" rel="<?=$value['hd_chamado']?>"> Ver Respostas <img src="imagens/barrow_down.png"> </button>
				</td>
			</tr>

			<!-- TR QUE CONTEM AS RESPOSTAS DO CLIENTE -->
			<tr class='hideTr' id='<?=$value["hd_chamado"]?>' >
				<td colspan="5">
					<table class="tabela">
						
						<tr>
							<td  class='titulo_tabela' colspan="3">
								<?php echo $TipoPerguntaDescricao; ?>
							</td>
						</tr>
						
						<tr class="titulo_coluna">
							<th>#</th>
							<th>Pergunta</th>
							<th>Resposta</th>
						</tr>
						
						<?php  
						for ($x=0; $x < pg_num_rows($resPergunta); $x++) { 
							$perguntaDescricao = pg_fetch_result($resPergunta, $x, 'descricao');
							$perguntaResposta  = pg_fetch_result($resPergunta, $x, 'txt_resposta');
							if ($TipoPerguntaDescricao == 'Auditoria em Campo'){

								if (in_array($x, array(8,9,10,11))){
									$num = ($num + 0.1);
								}else{
									if ($x >= 12){
										if ($num <> 9){
											$num = 9;
										}else{
											$num++;
										}
									}else{
										$num = ($x+1);
									}
								}

							}else{
								$num = $x+1;
							}

						?>
							<tr>
								<td><?php echo $num ?></td>
								<td align="left"><?php echo $perguntaDescricao ?></td>
								<td><?php echo $perguntaResposta ?></td>
							</tr>

						<?
						}
						?>

					</table>
				</td>
			</tr>
			<?
		}
		?>
		</table>
		<?
	}else{
		?>
		<div class="msg_erro">Nenhum Resultado Encontrado</div>
		<?
	}

}


require_once 'rodape.php';
?>
