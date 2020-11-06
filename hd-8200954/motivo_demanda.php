<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
	include 'autentica_admin.php';
} else {
	include 'autentica_usuario.php';
}

$pedido = $_REQUEST['pedido'];
$pedidos_itens = $_GET['pedidos_itens'];
$array_pedidos_itens = '';

if ($_REQUEST['gravaObsItem']) {
	
	$msg_err = [];
	$pedidos = $_REQUEST['pedidos'];

	$motivos = [
					'1' => 'Pedido para estoque, mas ainda não tem cliente aguardando.', 
					'2' => 'Pedido para estoque porque temos muita demanda de atendimentos.',
					'3' => 'Clientes já aguardando essas peças (garantia/orçamento).',
					'4' => 'Atender solicitação de algum cliente específico.',
					'5' => 'Outros motivos'
			   ];

	if (count($pedidos) > 0) {
		
		$res = pg_query($con, "BEGIN TRANSACTION");
		
		foreach ($pedidos as $key => $value) {
			$outros_obs = "";
			$obs = utf8_encode($motivos[$value[1]]);

			if (!empty($value[2])) {
				$outros_obs = ["outros_obs"=>$value[2]];
			} else {
				$outros_obs = ["outros_obs"=>null];
			}

			$outros_obs = json_encode($outros_obs);
			$outros_obs = str_replace('\\u', '\\\\u', $outros_obs);
			$valores_add = ", valores_adicionais = coalesce(valores_adicionais::jsonb, '$outros_obs') || '$outros_obs' ";

			$sql = "UPDATE tbl_pedido_item SET obs = '$obs' $valores_add WHERE pedido_item =".$value[0];			
			$res = pg_query($con, $sql);

			if (pg_last_error()) {
				$msg_err[] = ["Erro" => pg_last_error()];
			}
		}

		if (count($msg_err) == 0) {
			$res = pg_query ($con,"COMMIT TRANSACTION");
			$retorno = "gravou";			
		} else {
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
			$retorno = "erro";
		}
	} else {
		$retorno = "erro";
	}

	exit($retorno);
}

if ($areaAdmin) {
	
	$sql_qtde_pecas = "
	    SELECT tbl_pedido_item.peca,
	           tbl_peca.parametros_adicionais,
	           tbl_pedido_item.qtde,
	           tbl_pedido_item.data_item,
	           tbl_pedido_item.pedido_item
	    FROM tbl_pedido_item
	    INNER JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido
	    INNER JOIN tbl_peca   ON tbl_peca.peca     = tbl_pedido_item.peca
	         WHERE tbl_pedido_item.pedido = $pedido
	           AND JSON_FIELD('qtde_demanda', tbl_peca.parametros_adicionais) IS NOT NULL
	           AND JSON_FIELD('qtde_demanda', tbl_peca.parametros_adicionais) ~'\\d'
	           AND JSON_FIELD('qtde_demanda', tbl_peca.parametros_adicionais)::NUMERIC < tbl_pedido_item.qtde
        ORDER BY data_item";
    $res_qtde_pecas = pg_query($con, $sql_qtde_pecas);

    $rQtPc = pg_num_rows($res_qtde_pecas);
    $motivo_demanda = false;
    $pedidos_itens = [];

    for ($i = 0; $i < $rQtPc; $i++) {
        $qtde                  = pg_fetch_result($res_qtde_pecas, $i, 'qtde');
        $parametros_adicionais = pg_fetch_result($res_qtde_pecas, $i, 'parametros_adicionais');
        $parametros_adicionais = json_decode($parametros_adicionais, true);
        $qtde_demanda          = $parametros_adicionais['qtde_demanda'];

        if($qtde <= 3){
        	continue;
        }else{
        	$motivo_demanda = true;
        	$pedidos_itens[] = pg_fetch_result($res_qtde_pecas, $i, 'pedido_item');
        }
    }

    $pedidos_itens = implode(",", $pedidos_itens);
}

if (!empty($pedidos_itens)) {
	$sql_itens = " SELECT tbl_peca.referencia || ' - ' || tbl_peca.descricao AS ref_desc, 
						  tbl_pedido_item.qtde, 
						  tbl_pedido_item.pedido_item,
						  tbl_pedido_item.valores_adicionais,
						  tbl_pedido_item.obs
						  FROM tbl_pedido_item 
						  JOIN tbl_peca USING(peca) 
						  WHERE tbl_peca.fabrica = $login_fabrica 
						  AND tbl_pedido_item.pedido_item IN ($pedidos_itens)";
	$res_itens = pg_query($con, $sql_itens);
	if (pg_num_rows($res_itens) > 0) {
		$array_pedidos_itens = pg_fetch_all($res_itens);
	}
}

function limpaString ($string){
	$stringLimpa = str_replace("'", "", $string);
	return $stringLimpa;
}

?>
<!DOCTYPE html />
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="plugins/dataTable.js"></script>
		<script src="plugins/resize.js"></script>
		<script src='plugins/shadowbox_lupa/shadowbox.js'></script>
		<script src="plugins/shadowbox_lupa/lupa.js"></script>

		<script>
			$(function () {
				/*$("#motivo").change(function(){
					var motivo = $(this).val();

					if(motivo == 'outros'){
						$("#observacao").removeAttr('readonly');
					}else{
						//$("#observacao").attr('readonly','readonly');
						$("#observacao").val('');
					}
				});*/

				$("#btn_gravar").click(function() {
					let i = 0
					let ped_itens = []
					let pedido_item = 0
					let motivo = ''
					let obs = ''
					let area_admin = ''
					let tem_erro = false
					area_admin = '<?=$areaAdmin?>'

					$(".todos_itens").each(function() {
						pedido_item = $("#ped_item_"+i).val()
						motivo      = $("#motivo_"+i+" :selected").val();
						obs         = $("#observacao_"+i).val().trim();
						$(".conteudo_demanda_"+i).css('background-color', '#FFFFFF')

						if (motivo == 5 && obs == '') {
							alert("Observação obrigatória !")
							$(".conteudo_demanda_"+i).css('background-color', '#FF7C7C')
							tem_erro = true
							return false
						}

						if (motivo == '' || motivo == undefined || motivo == 0) {
							alert("Informe o motivo !")
							$(".conteudo_demanda_"+i).css('background-color', '#FF7C7C')
							tem_erro = true
							return false	
						}

						ped_itens.push([pedido_item, motivo, obs])
						i++
					})

					if (!tem_erro) {
						$.ajax({
							url: "motivo_demanda.php",
							type: "POST",
							data: {gravaObsItem:true, pedidos:ped_itens}
						})
						.done(function(data) {
							if(data == "gravou"){
								if (area_admin == '') {
									window.parent.gravarObs(obs = true, '', '')
									window.parent.Shadowbox.close()
								} else {
									window.parent.submitFormDemanda()
									window.parent.Shadowbox.close()
								}
							}else{
								alert("Erro ao processar os dados !")
								$('tr[class^="conteudo_demanda_"]').css('background-color', '#FF7C7C')
								return false
							}
						})
					}
				})
			})
		</script>
		<style type="text/css">
			.errorauditoria, .errormsg{
				color:red;
			}

			#observacao {
				width: 300px; 
				height: 100px;
			}

		</style>
	</head>
	<body style="background:white;">
		<div id="container_lupa" style="overflow-y:auto;">
			<div id="topo">
				<img class="espaco" src="imagens/logo_new_telecontrol.png">
			</div>
			
			<form>
					<input type="hidden" name="pedido" id="pedido" value="<?=$pedido?>">
					<input type="hidden" name="pedido_dewalt" id="pedido_dewalt" value="<?= $_REQUEST['pedido_dewalt'] ?>">
			</form>
			<br /><hr />  
			<div id="loading_obs_pedido" style="display: none; text-align: center;">
		            <img width="40" height="40" src="imagens/loading_img.gif" />
		            <p>Aguarde...</p>
		        </div>
			<div id="produto_pedido">
				
			</div>
			<div id="pecas">  
				<br />
					<table class="table" >
						<thead>
							<tr class='titulo_coluna'>
								<th colspan='4'><b>Motivo Demanda</b></th>
							</tr>
							<tr>
								 <th colspan='4' style="font-size: 12px">
								 	<p> 
								 		<b>
								 		A quantidade solicitada de peças excede a demanda mensal para toda a rede autorizada, por favor, verifique se a quantidade está correta e corrija se necessário. Se a quantidade estiver correta informe o motivo para controlarmos melhor nossa demanda e evitarmos possíveis faltas. 
								 		</b>
								 	</p>
								</th>          
							</tr>
							<tr>
								<th><p><b>Peça</b></p></th> 
								<th><p><b>Qtde</b></p></th>          
							    <th><p><b>Motivo</b></p></th>          
							    <th><p><b>Observação</b></p></th>          
							</tr>
						</thead>
						<tbody>
						<?php
							if (!empty($array_pedidos_itens)) {
								foreach ($array_pedidos_itens as $key => $value) {
									$xoutros_obs = "";
									$xobs        = "";

									$xobs = (mb_check_encoding($value['obs'], "UTF-8")) ? utf8_decode($value['obs']) : $value['obs'];
									$xvalores_adicionais = str_replace("\\\\u", "\\u", $value['valores_adicionais']);
									$xvalores_adicionais = json_decode($xvalores_adicionais, true);
									$xoutros_obs = (mb_check_encoding($xvalores_adicionais['outros_obs'], "UTF-8")) ? utf8_decode($xvalores_adicionais['outros_obs']) : $xvalores_adicionais['outros_obs']; 
						?>
									<tr class="conteudo_demanda_<?=$key?> todos_itens">
										<input type="hidden" name="item" id="ped_item_<?=$key?>" value="<?=$value['pedido_item']?>">
										<td style="text-align: center;">
											<?=$value['ref_desc']?>
										</td>
										<td style="text-align: center;">
											<?=$value['qtde']?>
										</td>
										<td  style="text-align: center;">
											<select name="motivo" id="motivo_<?=$key?>">
												<option value="">Selecione o Motivo</option>
												<?php
													$xmotivos = [
																	"1" => "Pedido para estoque, mas ainda não tem cliente aguardando.", 
																	"2" => "Pedido para estoque porque temos muita demanda de atendimentos.",
																	"3" => "Clientes já aguardando essas peças (garantia/orçamento).",
																	"4" => "Atender solicitação de algum cliente específico.",
																	"5" => "Outros motivos"
													     	    ];

													foreach ($xmotivos as $k => $v) {
														$sel = '';
														if ($xobs == $v) {
															$sel = "SELECTED";
														}
														echo '<option '.$sel.' value="'.$k.'">'.$v.'</option>';
													}
												?>
											<!-- 	
												<option value="2">Pedido para estoque porque temos muita demanda de atendimentos.</option>
												<option value="3">Clientes já aguardando essas peças (garantia/orçamento).</option>
												<option value="4">Atender solicitação de algum cliente específico</option>
												<option value="5">Outros motivos</option> -->
											</select>
										</td>
										<td style="text-align: center;">
											<!-- <input type="text" name="observacao"> -->
											<textarea name='observacao' id="observacao_<?=$key?>" value="<?=$xoutros_obs?>"><?=$xoutros_obs?></textarea>
										</td>
									</tr>
						<?php
								}
						?>
							<tr>
								<td style="text-align: center;" colspan="4">
									<br />
									<button class="btn btn-primary" type="button" id="btn_gravar"> Gravar </button>
								</td>
							</tr>	
						<?php
							}
						?>
						</tbody>
					</table>
					<br>
					<?php if($status_pedido == 18 && !isset($_REQUEST['pedido_dewalt'])) { ?>
						<table width="100%">
								<tr>
									<td style="color:#ff0000; text-align:center"> *Para que o Pedido seja aprovado, é preciso clicar no botão "<b>Aprovar Total</b>".</td>
								</tr>
								<tr>
									<td style="text-align: center"><button type='button' id="cancelar" class='btn btn-danger'>
										<?php echo ($login_fabrica == 1)? "Excluir" : "Cancelar"; ?>
									</button>
									<button type='button' id="finalizar" class='btn btn-success'>Aprovar Total</button></td>
								</tr>
								<tr>
									<td style="text-align: center">
										<br>
										<a href="xls/<?=$arquivo_nome?>" target='_blank'>
											<div class='btn_excel'>
												<span>
													<img src='imagens/excel.png' />
												</span>
												<span class='txt'>Download em Excel</span>
											</div>
										</a><br />
									</td>
								</tr>
						</table>
					<?php
						}

						if (empty($array_pedidos_itens)) {
					?>
							<div class="alert alert_shadobox">
                                <h4>Nenhum resultado encontrado</h4>
                           </div>
                    <?php
						}
					?>
			</div>
		</div>
	</body>
</html>
