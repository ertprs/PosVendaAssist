<?php
/**
 *
 * custo_falha_cadastro.php
 *
 * @author  Francisco Ambrozio
 * @version v1779248
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

$fluxo = 0;
$data_inicial = '';
$data_final = '';
$familia = '';
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

    $regiao = 0;

    if (!empty($_POST['regiao'])) {
        $regiao = (int) $_POST['regiao'];
    }

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
            $regiao = $_POST['regiao_' . $i];
			$cfe = str_replace(',', '.',$_POST['cfe_' . $i]);
			$qtde_produto_produzido = $_POST['qtde_produto_produzido_' . $i];

			if (empty($mes) or empty($ano) or empty($familia) or empty($cfe) or empty($qtde_produto_produzido)) {
				continue;
			}

            if (empty($regiao)) {
                $regiao = 'NULL';
                $cond_regiao = '';
            } else {
                $cond_regiao = " AND regiao = $regiao ";
            }

			if (empty($custo_falha)) {
				$sql = "INSERT INTO tbl_custo_falha (mes, ano, familia, regiao, cfe, qtde_produto_produzido, fabrica) VALUES ($mes, $ano, $familia, $regiao, $cfe, $qtde_produto_produzido, $login_fabrica)";
			} else {
				$sql = "UPDATE tbl_custo_falha SET 	cfe = $cfe, qtde_produto_produzido = $qtde_produto_produzido
						WHERE custo_falha = $custo_falha
                        AND familia = $familia
                        $cond_regiao
						AND mes = $mes
						AND ano = $ano
						AND fabrica = $login_fabrica";
            }

			$qry = pg_query($con, $sql);

			if (!pg_last_error()) {
				$msg_exito = 'Gravado com sucesso!';
			}
		}
		$fluxo = 0;
	}
	
}


include 'cabecalho.php';

?>

<style>

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.tabela_linha { padding: 2px 0px; }


.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}


.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

</style>

<?php include 'javascript_calendario.php'; ?>

<script type='text/javascript' src='js/ajax.js'></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>

<script>
	$().ready(function(){
		$( "#data_inicial" ).maskedinput("99/9999");
		$( "#data_final" ).maskedinput("99/9999");

		$(".cfe").numeric({allow:','});
		$(".qtde_produto_produzido").numeric();
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
		var regiao = document.getElementById('regiao_' + linha).value;

		if (!mes || !ano || !familia) {
			alert('Erro ao gravar.');
			return false;
		}

		div.innerHTML = 'Gravando...';

		var url = "custo_falha_cadastro_ajax.php";
		var params = "custo_falha=" + custo_falha + "&mes=" + mes + "&ano=" + ano + "&familia=" + familia + "&regiao=" + regiao + "&cfe=" + cfe + "&qtde_produto_produzido=" + qtde_produto_produzido + "&linha=" + linha;
		
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
</script>

<?php
switch ($fluxo) {
	case 0:
		?>
		<form name="datas" method="post" action="">
		<table align="center" class="formulario" width="700" border="0">
			<?php
			if (!empty($msg_erro)) {
			    ?>
			    <tr class="msg_erro">
					<td colspan="4">
						<?php echo implode("<br/>", $msg_erro); ?>
					</td>
				</tr>
			    <?php
			}
			elseif (!empty($msg_exito)) {
				?>
				<tr class="sucesso">
					<td colspan="6">
						<?php echo $msg_exito; ?>
					</td>
				</tr>
				<?php
			}
			?>
			<tr class="titulo_tabela">
				<td colspan="5">Intervalo de Datas</td>
			</tr>
			<tr><td colspan="5">&nbsp;</td></tr>
			<tr>
				<td>&nbsp;</td>
				<td>
					Data Inicial: <input type="text" id="data_inicial" name="data_inicial" class="frm" size="6" value="<?php echo $data_inicial; ?>" /></td>
				<td>
					Data Final: <input type="text" id="data_final" name="data_final" class="frm" size="6" style="text-align: left;" value="<?php echo $data_final; ?>" />
				</td>
				<td>
					Família:
					<?php
					$query_familia = pg_query($con, "SELECT familia, descricao FROM tbl_familia WHERE fabrica = $login_fabrica AND ativo is true ORDER BY descricao");
					echo '<select name="familia">';
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
				</td>
                <td>
                    Região:
                    <?php
                    $qry_reg = pg_query($con, "SELECT regiao, estados_regiao FROM tbl_regiao WHERE fabrica = $login_fabrica AND ativo ORDER BY descricao");
					echo '<select name="regiao">';
						echo '<option value=""></option>';
						while ($fetch = pg_fetch_assoc($qry_reg)) {
							echo '<option value="' , $fetch['regiao'] , '"';
							if ($regiao == $fetch['regiao']) {
								echo ' selected="selected" ';
							}
							echo '>' , $fetch['estados_regiao'] , '</option>';
						}
					echo '</select>';
                    ?>
                </td>
			</tr>
			<tr><td colspan="5">&nbsp;</td></tr>
			<tr><td colspan="5" style="text-align: center"><input type="submit" name="submit" value="Cadastrar" /></td></tr>
			<tr><td colspan="5">&nbsp;</td></tr>
		</table>
		</form>
		<?php
		break;
	case 2:
		?>
		<form name="custo_falhas" method="post" action="">
			<table align="center" class="formulario" width="700" border="0">
			<?php
			if (!empty($msg_erro)) {
			    ?>
			    <tr class="msg_erro">
					<td colspan="6">
						<?php echo implode("<br/>", $msg_erro); ?>
					</td>
				</tr>
			    <?php
			}

            $titulo_qtde = 'Qtde. Produzida';
            if (!empty($regiao)) {
                $titulo_qtde = 'Qtde. Faturada';
            }
			?>
			<tr class="titulo_tabela">
				<td>Mês/Ano</td>
				<td>Família</td>
				<td>Região</td>
				<td>CFE</td>
                <td><?php echo $titulo_qtde ?></td>
				<td>Ação</td>
			</tr>
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


            if (empty($regiao)) {
                $prepare = pg_prepare($con, "check_cf", "select custo_falha, cfe, qtde_produto_produzido from tbl_custo_falha where fabrica = $1 and ano = $2 and mes = $3 and familia = $4 and regiao is null and produto is null");
            } else {
                $prepare = pg_prepare($con, "check_cf", "select custo_falha, cfe, qtde_produto_produzido from tbl_custo_falha where fabrica = $1 and ano = $2 and mes = $3 and familia = $4 and regiao = $5 and produto is null");
            }

			$query_familia = pg_query($con, "SELECT descricao FROM tbl_familia WHERE familia = $familia");
            $familia_descricao = pg_fetch_result($query_familia, 0, 'descricao');

            if (empty($regiao)) {
                $estados_regiao = 'Todas';
            } else {
                $qry_regiao = pg_query($con, "SELECT estados_regiao FROM tbl_regiao WHERE regiao = $regiao");
                $estados_regiao = pg_fetch_result($qry_regiao, 0, 'estados_regiao');
            }

			$i = 0;

			while ($fetch = pg_fetch_assoc($query)) {
				$mes = $fetch['mes'];
				$ano = $fetch['ano'];

                $auxMes = str_pad($mes, 2, 0, STR_PAD_LEFT);

                if (empty($regiao)) {
                    $params = array($login_fabrica, $ano, $mes, $familia);
                } else {
                    $params = array($login_fabrica, $ano, $mes, $familia, $regiao);
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

                        $verificaQteNroSerie = "SELECT count(*) as qtde
                                            FROM tbl_numero_serie
                                            JOIN tbl_produto ON tbl_produto.produto = tbl_numero_serie.produto AND
                                                                tbl_produto.fabrica_i = tbl_numero_serie.fabrica
                                            JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND
                                                                tbl_familia.fabrica = tbl_produto.fabrica_i AND
                                                                tbl_familia.familia = {$familia} AND
                                                                tbl_familia.ativo IS TRUE 
                                            WHERE tbl_numero_serie.fabrica = {$login_fabrica} AND
                                                  tbl_numero_serie.data_fabricacao BETWEEN '{$ano}-{$auxMes}-01' AND '{$ultimoDiaMes}' ";
                        $resVerifica = pg_query($con, $verificaQteNroSerie);
                        if(pg_num_rows($resVerifica) > 0 ){
                            $qtde_produto_produzido = pg_fetch_result($resVerifica,0,"qtde");
                        }
                    }
                }
				?>

				<tr class="tabela_linha" align="center">
					<td class="tabela_linha">
						<strong><?php echo sprintf("%02d", $mes) , '/' , $ano ?></strong>
					</td>
					<td class="tabela_linha">
						<strong><?php echo $familia_descricao ?></strong>
					</td>
					<td class="tabela_linha">
						<strong><?php echo $estados_regiao ?></strong>
					</td>
					<td class="tabela_linha">
						<input type="text" id="cfe_<?php echo $i ?>" name="cfe_<?php echo $i ?>" value="<?php echo $cfe ?>" class="frm cfe" style="width: 80px;" />
					</td>
					<td class="tabela_linha">
						<input type="text" id="qtde_produto_produzido_<?php echo $i ?>" name="qtde_produto_produzido_<?php echo $i ?>" value="<?php echo $qtde_produto_produzido ?>" class="frm qtde_produto_produzido" style="width: 80px;" />
					</td>
					<td class="tabela_linha">
						<?php
						echo '<input type="hidden" name="custo_falha_' , $i , '" id="custo_falha_' , $i , '" value="' , $custo_falha , '" />';
						echo '<input type="hidden" name="mes_' , $i , '" id="mes_' , $i , '" value="' , $mes , '" />';
						echo '<input type="hidden" name="ano_' , $i , '" id="ano_' , $i , '" value="' , $ano , '" />';
						echo '<input type="hidden" name="familia_' , $i , '" id="familia_' , $i , '" value="' , $familia , '" />';
						echo '<input type="hidden" name="regiao_' , $i , '" id="regiao_' , $i , '" value="' , $regiao , '" />';
						?>
						<div id="gravar_item_<?php echo $i ?>" style="width: 80px;">
							<input type="button" value="Gravar" onClick="gravar(<?php echo $i ?>)" />
						</div>
					</td>
				</tr>
				
				<?php
				
				$i++;
			}
			
			?>
			<tr><td colspan="6">&nbsp;</td></tr>
			<tr>
				<td colspan="6" style="text-align: center">
					<input type="hidden" name="linhas" value="<?php echo $i ?>" />
					<input type="submit" name="submit" value="Gravar" />
				</td>
			</tr>
			<tr><td colspan="6">&nbsp;</td></tr>
		</table>
		<div align="center" style="margin-top: 20px;">
			<a href="custo_falha_cadastro_v1779248.php">
				<input type="button" value="Selecionar outro período/família" />
			</a>
		</div>
		</form>
		<?php
		break;
}
?>

<?php

include 'rodape.php';

