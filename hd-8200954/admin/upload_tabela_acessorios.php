<?php

	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	$admin_privilegios="cadastros";
	include 'autentica_admin.php';
	include 'funcoes.php';

	function cabecalho_mensagem_erro($erro,$referencia, $descricao){
		if(empty($erro)){
			$erro = " O acessório ";

			if(!empty($referencia)){
				$erro .= $referencia." ";
			}

			if(!empty($descricao)){
				$erro .= $descricao." ";
			}

			if(empty($referencia) && empty($descricao)){
				$erro .= " não tem referência, descrição, ";
			}else{
				$erro .= " não tem o(s) seguinte(s) contéudo(s): ";
			}
		}else{
			$erro .= ",";
		}

		return $erro;
	}

	/* Upload */
	if(isset($_POST['upload'])){

		$arquivo = $_FILES['tabela_acessorios'];

		if($arquivo['size'] > 0 ){

			if(!preg_match("/.xls/", $arquivo['name'])){

				$msg_erro["msg"][] = "Arquivo Inválido";

			}else if ($arquivo['size'] > 2000000){
				
				$msg_erro["msg"][] = "Arquivo com Tamanho Superior a 2 MG";

			}else{

				system ("mkdir /tmp/blackedecker/ 2> /dev/null ; chmod 777 /tmp/blackedecker/" );

				$origem = $arquivo['tmp_name'];
				$destino = "/tmp/blackedecker/".date("dmYHis").$arquivo['name'];
				
				if(move_uploaded_file($origem, $destino)){
					require_once 'xls_reader.php';
					$data = new Spreadsheet_Excel_Reader();
					$data->setOutputEncoding('CP1251');
					$data->read($destino);

                    $nao_inativar = array();

					if(count($data->sheets[0]["cells"]) > 0){
						$count = count($data->sheets[0]["cells"]);
						$array_peca_gravada = array();

						for ($i = 1; $i <= $count; $i++) {
							$erro = "";

							$referencia     = ""; // DW2001  Z
							$descricao      = ""; // Ponta 1" Phillips # 1- Tipo BIT TIP
							$origem         = ""; // Importado
							$codigo_origem  = ""; // 1
							$linha          = ""; // ACI
							$ncm            = ""; // 8207.90.00
							$ipi            = ""; // 8
							$qtde_multipla  = ""; // 5
							$valor_mg       = ""; // 2.67
							$valor_sp       = "";
							$demais_estados = ""; // 2.45

							for ($j = 1; $j <= $data->sheets[0]['numCols']; $j++) {

								// if($data->sheets[0]['numCols'] < 9) {
								// 	$msg_erro['msg'][] = "Por favor, verificar o conteúdo de Excel, está faltando algumas colunas";
								// }

								switch($j){

									case 1: $referencia     = $data->sheets[0]['cells'][$i][$j]; break;
									case 2: $descricao      = substr($data->sheets[0]['cells'][$i][$j],0,150); break;
									case 3: $origem         = $data->sheets[0]['cells'][$i][$j]; break;
									case 4: $codigo_origem  = $data->sheets[0]['cells'][$i][$j]; break;
									case 5: $linha          = $data->sheets[0]['cells'][$i][$j]; break;
									case 6: $ncm            = $data->sheets[0]['cells'][$i][$j]; break;
									case 7: $ipi            = $data->sheets[0]['cells'][$i][$j]; break;
									case 8: $qtde_multipla = $data->sheets[0]['cells'][$i][$j]; break;
									case 9: $valor_mg       = $data->sheets[0]['cells'][$i][$j]; break;
									case 10: $valor_sp      = $data->sheets[0]['cells'][$i][$j]; break;
									case 11: $demais_estados = $data->sheets[0]['cells'][$i][$j]; break;

								}
							}

							if(empty($referencia)){
								$erro = cabecalho_mensagem_erro($erro, "", $descricao);
								$erro .= " referência";
							}
							if(empty($descricao)){
								$erro = cabecalho_mensagem_erro($erro, $referencia, "");
								$erro .= " descrição";
							}
							if(empty($origem)){
								$erro = cabecalho_mensagem_erro($erro, $referencia, $descricao);
								$erro .= " origem";
							}
							if(empty($codigo_origem) && $codigo_origem != 0){
								$erro = cabecalho_mensagem_erro($erro, $referencia, $descricao);
								$erro .= " código de origem";
							}
							
							if(empty($linha)){
								$erro = cabecalho_mensagem_erro($erro, $referencia, $descricao);
								$erro .= " código da linha ou marca";
							}else{
								$sql = "SELECT linha FROM tbl_linha WHERE codigo_linha = '$linha' AND fabrica = $login_fabrica";
								$res = pg_query($con,$sql);

								if(pg_num_rows($res) > 0){
									$linha = pg_fetch_result($res, 0, "linha");
									$marca = "";
								}else{
									$sql = "SELECT marca FROM tbl_marca WHERE fabrica = $login_fabrica AND upper(nome) = upper('$linha')";
									$res = pg_query($con,$sql);

									if(pg_num_rows($res) > 0){
										$marca = pg_fetch_result($res, 0, "marca");
										$linha = "";
									}else{
										$msg_erro["msg"][] = "O acessório ".$referencia." ".$descricao." está com o código da linha ou nome da marca incorreta.";
										
									}
								}
							}

							if(empty($ncm)){
								$erro = cabecalho_mensagem_erro($erro, $referencia, $descricao);
								$erro .= " ncm";
							}
							if(empty($ipi) && $ipi != 0){
								$erro = cabecalho_mensagem_erro($erro, $referencia, $descricao);
								$erro .= " ipi";
							}
							if(empty($valor_mg)){
								$erro = cabecalho_mensagem_erro($erro, $referencia, $descricao);
								$erro .= " valor MG";
							}
							if(empty($valor_sp)){
								$erro = cabecalho_mensagem_erro($erro, $referencia, $descricao);
								$erro .= " valor SP";
							}
							if(empty($demais_estados) && $demais_estados != 0){
								$erro = cabecalho_mensagem_erro($erro, $referencia, $descricao);
								$erro .= " valor para demais estados";
							}
							if(empty($qtde_multipla)){
								$erro = cabecalho_mensagem_erro($erro, $referencia, $descricao);
								$erro .= " quantidade múltipla";
							}

							if(empty($erro) && count($msg_erro["msg"]) == 0){
								$referencia = trim($referencia);
								
								$sql = "SELECT peca FROM tbl_peca WHERE fabrica = 1 AND referencia = '$referencia'";
								$res = pg_query($con, $sql);

								switch (strtoupper($origem)) {
									case "IMPORTADO":
										$origem = "IMP";
										break;
									
									case "FABRICAÇÃO":
										$origem = "NAC";
										break;

									case "TERCEIROS":
										$origem = "TER";
										break;

									case "FABRICAÇÃO/SUBSIDIADO":
										$origem = "FAB/SUB";
										break;

									case "IMPORTADO/SUBSIDIADO":
										$origem = "IMP/SUB";
										break;

									case "TERCEIROS/SUBSIDIADO":
										$origem = "TER/SUB";
										break;
								}

								pg_query($con,"BEGIN");
								$sem_tratamento_descricao = $descricao;

								$descricao = str_replace("'", "\'", $descricao);
								$descricao = str_replace('"', '\"', $descricao);

								if (pg_num_rows($res) == 0) {
									$coluna       = "";
									$coluna_valor = "";

									if (!strlen($qtde_multipla)) {
										$qtde_multipla = 1;
									}

									if(empty($marca)){
										$coluna       = " linha_peca ";
										$coluna_valor = $linha;
									}else{
										$coluna       = " marca ";
										$coluna_valor = $marca;
									}

                                    $sql = "INSERT INTO tbl_peca (
                                                fabrica,
                                                referencia,
                                                descricao,
                                                origem,
                                                ncm,
                                                ipi,
                                                classificacao_fiscal,
                                                multiplo_site,
                                                multiplo,
                                                acessorio,
                                                $coluna
                                            ) VALUES (
                                                {$login_fabrica},
                                                '{$referencia}',
                                                E'{$descricao}',
                                                '{$origem}',
                                                '{$ncm}',
                                                {$ipi},
                                                '{$codigo_origem}',
                                                {$qtde_multipla},
                                                {$qtde_multipla},
                                                true,
                                                $coluna_valor
                                            ) RETURNING peca";
									$res = pg_query($con, $sql);

									if(strlen(pg_last_error()) > 0){
										$msg_erro["msg"][] = "Ocorreu um erro ao gravar o novo acessário ".$referencia." ".$descricao;
										pg_query($con,"ROLLBACK");
									}else{
										$peca = pg_fetch_result($res, 0, 0);
										$array_peca_gravada[] = array(
											"referencia" => $referencia,
											"descricao"  => $sem_tratamento_descricao,
											"label"      => "label label-success",
											"resultado"  => "Gravado"
										);
									}
								}else{
									$peca = pg_fetch_result($res, 0, 0);
									$coluna = "";

									if(empty($marca)){
										$coluna = ", linha_peca = $linha, marca = null ";
									}else{
										$coluna = ", linha_peca = null, marca = $marca ";
									}

									$sql = "UPDATE tbl_peca SET
											descricao            = E'{$descricao}',
											origem               = '{$origem}',
											ncm                  = '{$ncm}',
											ipi                  = {$ipi},
											acessorio            = true,
											classificacao_fiscal = '{$codigo_origem}',
											multiplo_site        = {$qtde_multipla},
											multiplo             = {$qtde_multipla},
											admin                = $login_admin,
											data_atualizacao     = current_timestamp
											$coluna
										WHERE peca = $peca AND fabrica = $login_fabrica";
									pg_query($con,$sql);

									if(strlen(pg_last_error()) > 0){
										$msg_erro['msg'][] = "Ocorreu um erro ao atualizar a peça $referencia - $descricao";
										pg_query($con,"ROLLBACK");
									}else{
										$array_peca_gravada[] = array(
											"referencia" => $referencia,
											"descricao"  => $sem_tratamento_descricao,
											"label"      => "label label-success",
											"resultado"  => "Gravado"
										);
									}
								}

								if(count($msg_erro["msg"]) == 0){

                                    foreach ($array_estados() as $sigla_estado => $estado_nome) {
                                        $qry_erp = pg_query(
                                            $con,
                                            "SELECT preco
                                            FROM tbl_tabela_item_erp
                                            WHERE peca = $peca
                                            AND estado = '{$sigla_estado}'
                                            AND tabela = 54"
                                        );

                                        if ($sigla_estado == 'MG') {
                                            $preco_tbl_erp = $valor_mg;
                                        } elseif ($sigla_estado == 'SP') {
                                            $preco_tbl_erp = $valor_sp;
                                        } else {
                                            $preco_tbl_erp = $demais_estados;
                                        }

                                        if (pg_num_rows($qry_erp) > 0) {
                                            $sql_tbl_erp = "UPDATE tbl_tabela_item_erp 
                                                SET preco = $preco_tbl_erp
                                                WHERE peca = $peca
                                                AND tabela = 54
                                                AND estado = '{$sigla_estado}'";
                                        } else {
                                            $sql_tbl_erp = "INSERT INTO tbl_tabela_item_erp 
                                                (tabela, peca, preco, estado)
                                                VALUES
                                                (54, $peca, $preco_tbl_erp, '{$sigla_estado}')";
                                        }

										$res_tbl_erp = pg_query($con, $sql_tbl_erp);
                                    }

									$sql = "SELECT preco, preco_avista FROM tbl_tabela_item WHERE peca = $peca AND tabela = 54";
									$res = pg_query($con, $sql);

									if(pg_num_rows($res) > 0){

										$sql = "UPDATE tbl_tabela_item SET preco = '$demais_estados', preco_avista = '$valor_mg' WHERE tabela = 54 AND  peca = $peca";
										$res = pg_query ($con,$sql);

										if(strlen(pg_last_error()) > 0){
											$msg_erro['msg'][] = "Ocorreu um erro ao atualizar a tabela de acessório ".$referencia." - ".$descricao;
											pg_query($con,"ROLLBACK");
										}

									}else{

										$sql = "INSERT INTO tbl_tabela_item (
													tabela,
													peca,
													preco,
													preco_avista
												) VALUES (
													54, 
													$peca, 
													'$demais_estados',
													'$valor_mg'
												)";

										$res = pg_query ($con,$sql);

										if(strlen(pg_last_error()) > 0){
											$msg_erro['msg'][] = "Ocorreu um erro ao gravar a tabela de acessório ".$referencia." - ".$descricao;
											pg_query($con,"ROLLBACK");
										}
									}

									if(strlen(pg_last_error()) == 0){
                                        $nao_inativar[] = $peca;
										pg_query($con,"COMMIT");
									}
								}

							}else{
								$msg_erro["msg"][] = $erro;
							}
						}

                        if(count($msg_erro['msg']) == 0){

                            if (!empty($nao_inativar)) {
                                $ativar_acessorios = pg_query(
                                    $con,
                                    "UPDATE tbl_peca SET ativo = 't'
                                    WHERE fabrica = $login_fabrica
                                    AND acessorio IS TRUE
                                    AND peca IN (" . implode(', ', $nao_inativar) . ")"
                                );

                                if (empty($_POST["acrescentar_acessorios"])) {
                                    $acrescentar_acessorios = pg_query(
                                        $con,
                                        "UPDATE tbl_peca SET ativo = 'f'
                                        WHERE fabrica = $login_fabrica
                                        AND acessorio IS TRUE
                                        AND peca NOT IN (" . implode(', ', $nao_inativar) . ")"
                                    );
                                }
                            }

							$msg_success = "Upload Realizado com Sucesso";
						}
					}else{
						$msg_erro["msg"][] = "Erro ao ler o arquivo";
					}
					
				}else{
					$msg_erro["msg"][] = "Erro ao Realizar Upload do Arquivo Excel";
				}

			}

		}else{

			$msg_erro["msg"][]    = "Por favor selecione o Arquivo Excel";
			$msg_erro["campos"][] = "tabela_acessorios";

		}

	}

	$layout_menu = "cadastro";
	$title = "UPLOAD DA TABELA DE ACESSÓRIOS";
	include 'cabecalho_new.php';

?>

<?php
	if(count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=$msg_erro["msg"][0];?></h4>
    </div>
<?php
	}
?>

<?php
	if (!empty($msg_success)) {
?>
    <div class="alert alert-success">
		<h4><?=$msg_success?></h4>
    </div>
<?php
	}
?>
<style type="text/css">
	.table > tbody > tr > td {
		text-align: center;
	}
</style>
<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data" align='center' class='form-search form-inline tc_formulario'>

	<input type="hidden" name="upload" value="acao" />

	<div class='titulo_tabela '>Parâmetros para Upload</div>

	<br />

	<div class="row-fluid">

		<div class="span1"></div>

		<div class="span10">

			<div class="alert">
				<h4>O arquivo selecionado deve estar no formato xls, sem cabeçalho e as informações devem estar na seguinte ordem:</h4>
				<p>
                    referência;
                    descrição;
                    origem;
                    código de origem;
                    código da linha ou nome da marca;
                    NCM;
                    IPI;
                    quantidade múltipla;
                    valor MG;
                    valor SP;
                    valor para demais estados;
				</p>
		    </div>

		</div>

		<div class="span1"></div>

    </div>

	<div class="row-fluid">

		<div class="span2"></div>
		
		<div class='span8'>
			<div class='control-group <?=(in_array("tabela_acessorios", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='peca_referencia'>Arquivo Excel</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="file" id="tabela_acessorios" name="tabela_acessorios" class='span12' />
					</div>
				</div>
                <div class='control-group'>
                    <input type="checkbox" name="acrescentar_acessorios"
                    <?php
                    if (!empty($_POST["acrescentar_acessorios"])) {
                        echo 'checked="checked" ';
                    }
                    ?>
                    /> Apenas Acrescentar Acessórios
                </div>
			</div>
		</div>

		<div class="span2"></div>

	</div>

	<p>
		<br/>
		<button class='btn btn-info' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Realizar Upload do Arquivo Excel</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p>

	<br/>

</form>
<?php
	if(count($array_peca_gravada) > 0){
		?>
		<table class='table table-striped table-bordered table-hover table-fixed'>
			<thead>
				<tr class="titulo_tabela">
					<th colspan="3">Acessórios Cadastrados/ Atualizados</th>
				</tr>
				<tr class='titulo_coluna'>
					<th>Código</th>
					<th>Referência</th>
					<th>Resultado</th>
				</tr>
			</thead>
			<tbody>
		<?php
		foreach ($array_peca_gravada as $key => $acessorio) {
			?>
				<tr>
					<td><?=$acessorio["referencia"]?></td>
					<td><?=$acessorio["descricao"]?></td>
					<td><label class="label <?=$acessorio['label']?>"><?=$acessorio["resultado"]?></td>
				</tr>
			<?php			
		}
		?>
			</tbody>
		</table>
		<?php
	}

include "rodape.php";
?>
