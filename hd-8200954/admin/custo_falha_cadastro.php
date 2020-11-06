<?php
/**
 *
 * custo_falha_cadastro.php
 *
 * @author  Francisco Ambrozio
 * @version 2012.08.01
 *
 *  CRUD tbl_custo_falha
 *
 */

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = 'cadastros';
$title = 'CADASTRO DE CUSTO FALHA';
$cabecalho = 'CADASTRO DE CUSTO FALHA';
$layout_menu = 'cadastro';
include 'autentica_admin.php';
// echo "<pre>";
// print_r($_POST);
// echo "</pre>";
// echo $ajax_lista;
// exit;
if ($ajax_lista == true) {
	
	$sql_c = "SELECT  	custo_falha,
						tbl_familia.descricao as desc_familia,
						ano,
						mes,
						cfe,
						qtde_produto_produzido,
						regiao,
						tbl_produto.referencia as ref_prod,
						tbl_produto.descricao as desc_prod
				FROM tbl_custo_falha
				JOIN tbl_familia ON tbl_custo_falha.familia = tbl_familia.familia
				LEFT JOIN tbl_produto ON tbl_custo_falha.produto = tbl_produto.produto
				WHERE tbl_custo_falha.fabrica = {$login_fabrica}
				ORDER BY ano desc
				;";
	$res_c = pg_query($con,$sql_c);
	$lista_tabela = pg_fetch_all($res_c);
	$result = "
		<table id='intervalo-list' class='table table-striped table-bordered table-hover table-fixed'>
				<thead>
					<tr class='titulo_coluna'>
						<th>Mês/Ano</th>
						<th>Família</th>
						<th>Produto</th>
						<th>CFE</th>
						<th>Qtde. Produzida</th>
					</tr>
				</thead>
				<tbody>
	";
	foreach ($lista_tabela as $value) {
		$result .= " <tr>
						<td>".$value['mes']."/".$value['ano']."</td>
						<td>".$value['desc_familia']."</td>";

		if (count($value['ref_prod']) > 0 ) {
			$result .= "	<td>".$value['ref_prod']." - ".$value['desc_prod']."</td>";
		}else{
			$result .= "	<td></td>";
		}
			$result .= "	<td>".$value['cfe']."</td>
						<td>".$value['qtde_produto_produzido']."</td>
					</tr>";		
	}
	$result .= "</tbody></table>";

	echo $result;
	exit;	
}

$fluxo = 0;
$data_inicial = '';
$data_final = '';
$familia = '';
$produto = 0;
$msg_erro = array();
$msg_exito = array();

if (!empty($_POST['submit'])) {
	switch ($_POST['submit']) {
		case 'Cadastrar':
			$fluxo = 1;
			break;
		case 'Gravar':
			$fluxo = 3;
			break;
	}
}



if ($fluxo == 1) {
	if (empty($_POST['data_inicial'])) {
		$msg_erro[] = 'Favor digitar a data inicial.';
	} else {
		$data_inicial = $_POST['data_inicial'];
	}

	if (empty($_POST['data_final'])) {
		$msg_erro[] = 'Favor digitar a data final.';
	} else {
		$data_final = $_POST['data_final'];
	}

	if (empty($_POST['familia'])) {
		$msg_erro[] = 'Favor selecione uma família de produtos.';
	} else {
		$familia = $_POST['familia'];
	}

    $produto = (int) $_POST['produto'];

	$ok = 0;

	if (empty($msg_erro)) {
		$arr_data_inicial = explode("/", $data_inicial);
		$arr_data_final = explode("/", $data_final);

		if (!checkdate($arr_data_inicial[0], 1, $arr_data_inicial[1])) {
			$msg_erro[] = 'Data inicial inválida.';
		} else {
			$ok++;
		}

		if (!checkdate($arr_data_final[0], 1, $arr_data_final[1])) {
			$msg_erro[] = 'Data final inválida.';
		} else {
			$ok++;
		}

		$d1 = new DateTime($arr_data_inicial[1] . '-' . $arr_data_inicial[0] . '-01');
		$d2 = new DateTime($arr_data_final[1] . '-' . $arr_data_final[0] . '-01');

		if ($d1 > $d2) {
			$msg_erro[] = 'Data final maior que data inicial.';
		} else {
			$ok++;
		}

	}

	if ($ok <> 3) {
		$fluxo = 0;
	} else {
		$fluxo = 2;
	}

}
elseif ($fluxo == 3) {
	$linhas = $_POST['linhas'];

	if (empty($linhas)) {
		$msg_erro[] = 'Erro ao gravar';
	} else {
		for ($i = 0; $i < $linhas; $i++) {
			$custo_falha = $_POST['custo_falha_' . $i];
			$mes = $_POST['mes_' . $i];
			$ano = $_POST['ano_' . $i];
			$familia = $_POST['familia_' . $i];
            $produto = $_POST['produto_' . $i];
			$cfe = str_replace(',', '.',$_POST['cfe_' . $i]);
			$qtde_produto_produzido = $_POST['qtde_produto_produzido_' . $i];

			if (strlen($mes) == 0 or strlen($ano) == 0 or strlen($familia) == 0 or strlen($cfe) == 0 or strlen($qtde_produto_produzido) == 0) {
				continue;
			}

            if (empty($produto)) {
                $produto = 'NULL';                
            }

			if (empty($custo_falha)) {
				$sql = "INSERT INTO tbl_custo_falha (mes, ano, familia, cfe, qtde_produto_produzido, fabrica, produto) VALUES ($mes, $ano, $familia, $cfe, $qtde_produto_produzido, $login_fabrica, $produto)";
            } else {
				$sql = "UPDATE tbl_custo_falha SET 	cfe = $cfe, qtde_produto_produzido = $qtde_produto_produzido
						WHERE custo_falha = $custo_falha";
			}
			$qry = pg_query($con, $sql);

			if (!pg_last_error()) {
				$msg_exito = 'Gravado com sucesso!';
			}

            $produto = 0;
		}
		$fluxo = 0;
        $familia = 0;
	}
	
}

include 'cabecalho_new.php';

$plugins = array(
   	"datepicker",
   	"dataTable",
   	"maskedinput",
   	"alphanumeric",
   	"ajaxform",   
	"price_format"
);

include 'plugin_loader.php';
?>

<script type='text/javascript' src='js/ajax.js'></script>
<script  type="text/javascript">
	$().ready(function(){
		$("#data_inicial").datepicker({dateFormat: "mm/yy" }).mask("99/9999");
		$("#data_final").datepicker({dateFormat: "mm/yy" }).mask("99/9999");
		// $( "#data_inicial" ).maskedinput("99/9999");
		// $( "#data_final" ).maskedinput("99/9999");
		 

		$(".cfe").numeric({allow:','});
		$(".qtde_produto_produzido").numeric();

		$("#listar").click(function(){
			$.ajax({				
				url: "custo_falha_cadastro.php",
				type: "POST",
				data: { ajax_lista: true},
				complete: function(data) {
					data = data.responseText;
					if (data == "") {
						$("#tabela_listar").html("Intervalos não cadastrados.");
					} else {
						$("#tabela_listar").html(data);
						 $.dataTableLoad({	table : "#intervalo-list"	});
					}
				}
			});

		});
	});

	function gravar(linha) {

		var div = document.getElementById('gravar_item_' + linha);

		var cfe = document.getElementById('cfe_' + linha).value;
		var qtde_produto_produzido = document.getElementById('qtde_produto_produzido_' + linha).value;

		if (!cfe) {
			alert('Favor preencher o CFE');
			return false;
		}

		if (!qtde_produto_produzido) {
			alert('Favor preencher o Qtde. Produzida');
			return false;
		}

		var custo_falha = document.getElementById('custo_falha_' + linha).value;
		var mes = document.getElementById('mes_' + linha).value;
		var ano = document.getElementById('ano_' + linha).value;
		var familia = document.getElementById('familia_' + linha).value;
		var produto = document.getElementById('produto_' + linha).value;

		if (!mes || !ano || !familia) {
			alert('Erro ao gravar.');
			return false;
		}

		div.innerHTML = 'Gravando...';

		var url = "custo_falha_cadastro_ajax.php";
		var params = "custo_falha=" + custo_falha + "&mes=" + mes + "&ano=" + ano + "&familia=" + familia + "&cfe=" + cfe + "&qtde_produto_produzido=" + qtde_produto_produzido + "&produto=" + produto + "&linha=" + linha;
		
		http.open("POST", url, true);

		//Send the proper header information along with the request
		http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		http.setRequestHeader("Content-length", params.length);
		http.setRequestHeader("Connection", "close");

		http.onreadystatechange = function() {//Call a function when the state changes.
			if(http.readyState == 4 && http.status == 200) {
				div.innerHTML = http.responseText;
				var restore = setTimeout('restoreDiv(' + linha + ')', 5000);
			}
		}
		
		http.send(params);

	}

	function restoreDiv(linha) {
		var div = document.getElementById('gravar_item_' + linha);
        var cadastrado = $('#custo_falha_atualizado_' + linha).val();

        if (cadastrado) {
            $('#custo_falha_' + linha).val(cadastrado);
        }

		var html = '<input type="button" value="Gravar" onClick="gravar(' + linha + ')" />';
		div.innerHTML = html;
	}

    function buscaProdutos() {
        var familia = $('#familia :selected').val();

        $.ajax({
			url: "busca_produto_por_familia.php?familia=" + familia,
			dataType: "text",
			success: function(data) {
                var response = $.parseJSON(data);

                if (!response) {
                    return false;
                }

                $('#produto').html('<option value=""></option>');

                for (var i in response) {
                    var option = '<option value="' + response[i].id + '">' + response[i].produto + '</option>';
                    $('#produto').append(option);
                }

			}
		})
    }
</script>

<?php
switch ($fluxo) {
	case 0:
		if (!empty($msg_erro)) {?>
			<br />
			<div class="alert alert-error">
				<h4>
					<?php echo implode("<br/>", $msg_erro); ?>
				</h4>
			</div>
			<br />
		<?php
		}elseif (!empty($msg_exito)) {?>
			<br />
			<div class="alert alert-success">
					<h4> 
						<?php echo $msg_exito; ?>
					</h4>
			</div>
			<br />
		<?php
		}
		?>
		<form name="datas" method="post" action="" class="form-search form-inline" >
			<div id="div_datas" class="tc_formulario">
				<div class="titulo_tabela">Intervalo de Datas</div>
				<br />
				<div class='row-fluid'>
					<div class='span2'></div>
					<div class='span2'>
						<div class='control-group' >
							<label class="control-label" for="data_inicial">Data Inicial</label>
							<div class="controls controls-row">
								<div class="span12">
									<input type="text" id="data_inicial" name="data_inicial" class="span12" size="6" value="<?php echo $data_inicial; ?>" />
								</div>
							</div>
						</div>
					</div>
					<div class='span2'>
						<div class='control-group' >
							<label class="control-label" for="data_final">Data Final</label>
							<div class="controls controls-row">
								<div class="span12">
									<input type="text" id="data_final" name="data_final" class="span12" size="6" value="<?php echo $data_final; ?>" />
								</div>
							</div>
						</div>						
					</div>
					<div class='span4'>
						<div class='control-group' >
							<label class="control-label" for="familia">Família</label>
							<div class="controls controls-row">
								<div class="span12">
									<?php
									$query_familia = pg_query($con, "SELECT familia, descricao 
																			FROM tbl_familia 
																			WHERE fabrica = $login_fabrica 
																			AND ativo is true 
																			ORDER BY descricao");
									echo '<select id="familia" name="familia" onChange="buscaProdutos()">';
										echo '<option value=""></option>';
										while ($fetch = pg_fetch_assoc($query_familia)) {
											echo '<option value="' , $fetch['familia'] , '"';
											if ($familia == $fetch['familia']) {
												echo ' selected="selected" ';
											}
											echo '>' , $fetch['descricao'] , '</option>';
										}
									echo '</select>';
									?>
								</div>
							</div>
						</div>						
					</div>
					<div class='span2'></div>
				</div>

				<!-- DIV PRODUTO -->
				<div class='row-fluid'>
					<div class='span2'></div>
					<div class='span4'>
						<div class='control-group' >
							<label class="control-label" for="produto">Produto</label>
							<div class="controls controls-row">
								<div class="span12">
									<select id="produto" name="produto"><option value=""></option></select>
								</div>
							</div>
						</div>
					</div>
					<div class='span6'></div>
				</div>		
				<br />
				<p class="tac">
					<input type="submit" class="btn" name="submit" value="Cadastrar" />
				</p>
				<p class="tac">
					<button type="button" class="btn btn-info" id="listar">Listar Todos Intervalos</button>
				</p>
				<br />
			</div>
		</form>
		<div id="tabela_listar"></div>
		<?php
		break;
	case 2:
		if (!empty($msg_erro)) {?>
			<br />
			<div class="alert alert-error">
				<h4>
					<?php echo implode("<br/>", $msg_erro); ?>
				</h4>
			</div>
			<br />
		<?php
		}
		?>
		<form name="custo_falhas" method="post" action="" class="form-search form-inline" >
		<div id="div_consulta" class="tc_formulario">
			<table class="table table-striped table-bordered table-hover table-fixed">
				<thead>
					<tr class="titulo_coluna">
						<td>Mês/Ano</td>
						<td>Família</td>
						<?php if (!empty($produto)): ?><td>Produto</td><?php endif ?><td>CFE</td>
						<td>Qtde. Produzida</td>
					</tr>
				</thead>
				<?php
				$str_data_inicial = $arr_data_inicial[1] . '-' . $arr_data_inicial[0] . '-01';
				$str_data_final = $arr_data_final[1] . '-' . $arr_data_final[0] . '-01';

				$date1 = date(strtotime($str_data_inicial));
				$date2 = date(strtotime($str_data_final));

				$difference = $date2 - $date1;
				$meses = floor($difference / 86400 / 30 );
				
				$sql = "SELECT 
							extract(month from to_char(('$str_data_inicial'::date + interval '$meses month'), 'YYYY-MM-DD')::date - s * interval '1 month') as mes,
							extract(year from to_char(('$str_data_inicial'::date + interval '$meses month'), 'YYYY-MM-DD')::date - s * interval '1 month') as ano
						FROM generate_series(0, $meses) as s order by ano, mes";
				$query = pg_query($con, $sql);

		            $cond_produto = 'and produto is null';
		            if (!empty($produto)) {
		                $cond_produto = 'and produto = $5';
		            }

				$prepare = pg_prepare($con, "check_cf", "select custo_falha, cfe, qtde_produto_produzido from tbl_custo_falha where fabrica = $1 and ano = $2 and mes = $3 and familia = $4 {$cond_produto} and regiao IS NULL");

				$query_familia = pg_query($con, "SELECT descricao FROM tbl_familia WHERE familia = $familia");
				$familia_descricao = pg_fetch_result($query_familia, 0, 'descricao');

				$i = 0;

				while ($fetch = pg_fetch_assoc($query)) {
					$mes = $fetch['mes'];
					$ano = $fetch['ano'];

					$auxMes = str_pad($mes, 2, 0, STR_PAD_LEFT);
					$params = array($login_fabrica, $ano, $mes, $familia);

					if (!empty($produto)) {
						$params[] = (string) $produto;
					}

					$pgexec = pg_execute($con, "check_cf", $params);

					$custo_falha = '';
					$cfe = '';
					$qtde_produto_produzido = '';

					if (pg_num_rows($pgexec) > 0) {
						$custo_falha = pg_fetch_result($pgexec, 0, 'custo_falha');
						$cfe = str_replace('.', ',', pg_fetch_result($pgexec, 0, 'cfe'));
						$qtde_produto_produzido = pg_fetch_result($pgexec, 0, 'qtde_produto_produzido');
					}

                			if($login_fabrica == 24){
                				if(strlen($qtde_produto_produzido) == 0){

                					$sqlUltimoDiaMes = "select to_char(('{$ano}-{$auxMes}-01'::date + interval '1 month') - interval '1 day', 'YYYY-MM-DD')::date as ultimo_dia";
                					$resUltimoDia = pg_query($con, $sqlUltimoDiaMes);
                        				$ultimoDiaMes = pg_fetch_result($resUltimoDia, 0, "ultimo_dia");
							$and_produto = (!empty($produto)) ? " AND produto = $produto " : "";

							$verificaQteNroSerie = "SELECT COUNT(*) as qtde
														FROM tbl_numero_serie 
														WHERE fabrica = {$login_fabrica} 
														AND data_fabricacao BETWEEN '{$ano}-{$auxMes}-01' AND '{$ultimoDiaMes}' 
														AND produto IN (SELECT produto FROM tbl_produto WHERE familia = {$familia} $and_produto)";
							$resVerifica = pg_query($con, $verificaQteNroSerie);

                        				if(pg_num_rows($resVerifica) > 0 ){
                        					$qtde_produto_produzido = pg_fetch_result($resVerifica,0,"qtde");
                        				}
                    			}
                			}
					?>
					<tr>
						<td >
							<strong><?php echo sprintf("%02d", $mes) , '/' , $ano ?></strong>
						</td>
						<td >
							<strong><?php echo $familia_descricao ?></strong>
                    			</td>
                    		<?php
                    		if (!empty($produto)) {
                    			$sqlProduto = "SELECT descricao FROM tbl_produto WHERE produto = $produto";
                    			$qryProduto = pg_query($con, $sqlProduto);
                    			$produto_descricao = pg_fetch_result($qryProduto, 0, 'descricao');

                    			echo '<td class="tabela_linha">
                    					<strong>' . $produto_descricao . '</strong>
                    				    </td>';
					}
					?>
					<td class="tabela_linha">
						<input type="text" id="cfe_<?php echo $i ?>" name="cfe_<?php echo $i ?>" value="<?php echo $cfe ?>" class="span12" style="width: 80px;" />
					</td>
					<td class="tabela_linha">
						<input type="text" id="qtde_produto_produzido_<?php echo $i ?>" name="qtde_produto_produzido_<?php echo $i ?>" value="<?php echo $qtde_produto_produzido ?>" class="frm qtde_produto_produzido" style="width: 80px;" />
					</td>
<?php
						echo '<input type="hidden" name="custo_falha_' , $i , '" id="custo_falha_' , $i , '" value="' , $custo_falha , '" />';
						echo '<input type="hidden" name="mes_' , $i , '" id="mes_' , $i , '" value="' , $mes , '" />';
						echo '<input type="hidden" name="ano_' , $i , '" id="ano_' , $i , '" value="' , $ano , '" />';
						echo '<input type="hidden" name="familia_' , $i , '" id="familia_' , $i , '" value="' , $familia , '" />';
						echo '<input type="hidden" name="produto_' , $i , '" id="produto_' , $i , '" value="' , $produto , '" />';
?>
				</tr>
				
				<?php
				
				$i++;
			}

			?>
		</table>
		<br />
		<p class="tac">
			<input type="hidden" name="linhas" value="<?php echo $i ?>" />
			<input type="submit" class="btn" name="submit" value="Gravar" />
		</p>
		<br />

		<p class="tac">
			<a href="custo_falha_cadastro.php">
				<input type="button" class="btn" value="Selecionar outro período/família" />
			</a>
		</p>
		<br />
		</div>
		</form>
		<?php
		break;
}

include 'rodape.php';

