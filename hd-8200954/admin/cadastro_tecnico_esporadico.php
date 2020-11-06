<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "gravar") {
	$tecnico  = $_POST["id_tec"];
	$codigo   = $_POST["codigo"];
	$nome     = $_POST["nome"];
	$telefone = $_POST["telefone"];
	$endereco = $_POST["endereco"];
	$cidade   = $_POST["cidade"];
	$estado   = $_POST["estado"];

	if (empty($codigo)) {
		$msg_erro["msg"][]    = "Favor informar o código do técnico";
		$msg_erro["campos"][] = "codigo";
	}

	if (empty($nome)) {
		$msg_erro["msg"][]    = "Favor informar o nome do técnico";
		$msg_erro["campos"][] = "nome";
	}

	if (empty($msg_erro)) {
		if (!empty($tecnico)) {
			$sql_set = array(" codigo_externo = '$codigo' ", " nome = '$nome' ");
			
			if (!empty($telefone)) {
				$sql_set[] = " telefone = '$telefone' ";
			}

			if (!empty($endereco)) {
				$sql_set[] = " endereco = '$endereco' ";
			}

			if (!empty($cidade)) {
				$sql_set[] = " cidade = '$cidade' ";
			}

			if (!empty($estado)) {
				$sql_set[] = " estado = '$estado' ";
			}

			$sql = "UPDATE tbl_tecnico SET ". implode(",", $sql_set) . " WHERE fabrica = $login_fabrica AND tecnico = $tecnico";

		} else {
			$sql_campos = array("fabrica", "codigo_externo", "nome");
			$sql_values = array($login_fabrica, "'$codigo'", "'$nome'");

			if (!empty($telefone)) {
				$sql_campos[] = "telefone";
				$sql_values[] = "'$telefone'";
			}

			if (!empty($endereco)) {
				$sql_campos[] = "endereco";
				$sql_values[] = "'$endereco'";
			}

			if (!empty($cidade)) {
				$sql_campos[] = "cidade";
				$sql_values[] = "'$cidade'";
			}

			if (!empty($estado)) {
				$sql_campos[] = "estado";
				$sql_values[] = "'$estado'";
			}

			$sql = "INSERT INTO tbl_tecnico(". implode(',', $sql_campos) .") VALUES (". implode(',', $sql_values) .")";
		}

		pg_query($con, "BEGIN");

		$res = pg_query($con, $sql);

		if (pg_last_error($con)) {
			pg_query($con, "ROLLBACK");
			$msg_erro["msg"][] = "Erro ao cadastrar o téncico";
		} else {
			pg_query($con, "COMMIT");
			$msg_success[] = "Técnico salvo com sucesso!";
		}
		
		unset($codigo, $nome, $telefone, $endereco, $cidade, $estado, $tecnico);
	}
}

if ($_POST["btn_acao"] == "listar") {
	$sql  = "SELECT tecnico, codigo_externo, nome, telefone, endereco, cidade, estado, qtde_atendimento, ativo FROM tbl_tecnico WHERE fabrica = $login_fabrica ORDER BY ativo DESC, nome";
	$res  = pg_query($con, $sql);
	$rows = pg_num_rows($res);

	if (!empty($rows)) {
		$tecnicos = array();

		for ($i = 0; $i < $rows; $i++) { 
			$tecnico  = pg_fetch_result($res, $i, 'tecnico');
	 		$codigo   = pg_fetch_result($res, $i, 'codigo_externo');
	 		$nome     = pg_fetch_result($res, $i, 'nome');
	 		$telefone = pg_fetch_result($res, $i, 'telefone');
	 		$endereco = pg_fetch_result($res, $i, 'endereco');
	 		$cidade   = pg_fetch_result($res, $i, 'cidade');
	 		$estado   = pg_fetch_result($res, $i, 'estado');
	 		$qtde     = pg_fetch_result($res, $i, 'qtde_atendimento');
	 		$ativo    = pg_fetch_result($res, $i, 'ativo');

	 		$tecnicos[$i]["tecnico"]  = $tecnico;
	 		$tecnicos[$i]["codigo"]   = $codigo;
	 		$tecnicos[$i]["nome"]     = $nome;
	 		$tecnicos[$i]["telefone"] = $telefone;
	 		$tecnicos[$i]["endereco"] = $endereco;
	 		$tecnicos[$i]["cidade"]   = $cidade;
	 		$tecnicos[$i]["estado"]   = $estado;
	 		$tecnicos[$i]["qtde"]     = $qtde;
	 		$tecnicos[$i]["ativo"]    = $ativo;

	 		unset($codigo, $nome, $telefone, $endereco, $cidade, $estado, $tecnico);
		} 
	}

	$listar = true;
}

if ($_POST["btn_acao"] == "upload") {
	if (empty($_FILES["arquivo"]['tmp_name'])){
        $msg_erro["msg"][] = 'Nenhum arquivo selecionado';
        $msg_erro["campos"][] = 'arquivo';
    }

    if (!in_array($_FILES["arquivo"]["type"], array('text/plain', 'text/csv'))) {
    	$msg_erro["msg"][] = "O formato do arquivo é inválido!<br>Por favor, insira um arquivo TXT ou CSV";
        $msg_erro["campos"][] = 'arquivo';
    }

    if (empty($msg_erro)) {
    	$arquivo  = $_FILES["arquivo"];
    	$conteudo = file_get_contents($arquivo["tmp_name"]);

    	if (empty($conteudo)) {
    		$msg_erro["msg"][] = "O arquivo está vazio!";
        	$msg_erro["campos"][] = 'arquivo';
    	} else {
        	$linhas = explode("\n", $conteudo);
        	pg_query($con, "BEGIN");

        	foreach ($linhas as $linha) {
        		$dados = explode(";", $linha);

        		if (count($dados) != 6) {
        			$msg_erro["msg"][] = "A estrutura do arquivo está errada!";
        			$msg_erro["campos"][] = 'arquivo';
        			break;
        		} else {
        			$codigo   = trim($dados[0]);
        			$nome     = trim($dados[1]);
        			$telefone = trim($dados[2]);
        			$endereco = trim($dados[3]);
        			$cidade   = trim($dados[4]);
        			$estado   = trim($dados[5]);

        			if (empty($codigo) || empty($nome)) {
        				$msg_erro["msg"][] = "A formatação do arquivo está errada!";
        				$msg_erro["campos"][] = 'arquivo';
        				break;
        			} else {
        				$sql = "SELECT tecnico FROM tbl_tecnico WHERE codigo_externo = '$codigo' AND fabrica = $login_fabrica";
        				$res = pg_query($con, $sql);
        				$row = pg_num_rows($res);

        				if ($row > 0) {
							$sql_set = array("codigo_externo = '$codigo'", "nome = '$nome'");

							if (!empty($telefone)) {
								$sql_set[]    = "telefone = '$telefone'";
							}

							if (!empty($endereco)) {
								$endereco = str_replace("'", "\'", $endereco);
								$endereco = str_replace('"', '\"', $endereco);

								$sql_set[]    = "endereco = E'$endereco'";
							}

							if (!empty($cidade)) {
								$cidade = str_replace("'", "\'", $cidade);
								$cidade = str_replace('"', '\"', $cidade);

								$sql_set[]    = "cidade = E'$cidade'";
							}

							if (!empty($estado)) {
								$sql_set[]    = "estado = '$estado'";
							}

							$sql = "UPDATE tbl_tecnico SET ". implode(',', $sql_set) ." WHERE codigo_externo = '$codigo' AND fabrica = $login_fabrica";
        				} else {
	        				$sql_campos = array("fabrica", "codigo_externo", "nome");
							$sql_values = array($login_fabrica, "'$codigo'", "'$nome'");

							if (!empty($telefone)) {
								$sql_campos[] = "telefone";
								$sql_values[] = "'$telefone'";
							}

							if (!empty($endereco)) {
								$endereco = str_replace("'", "\'", $endereco);
								$endereco = str_replace('"', '\"', $endereco);

								$sql_campos[] = "endereco";
								$sql_values[] = "E'$endereco'";
							}

							if (!empty($cidade)) {
								$cidade = str_replace("'", "\'", $cidade);
								$cidade = str_replace('"', '\"', $cidade);

								$sql_campos[] = "cidade";
								$sql_values[] = "E'$cidade'";
							}

							if (!empty($estado)) {
								$sql_campos[] = "estado";
								$sql_values[] = "'$estado'";
							}

							$sql = "INSERT INTO tbl_tecnico(". implode(',', $sql_campos) .") VALUES (". implode(',', $sql_values) .")";
        				}

						$res = pg_query($con, $sql);
						
						if (pg_last_error($con)) {
							$msg_erro["msg"][] = "Erro ao cadastrar o técnico $codigo, favor verificar se o arquivo de upload segue o layout informado.";
        					$msg_erro["campos"][] = 'arquivo';
        					break;
						}
        			}
        		}
        	}

        	if (!empty($msg_erro)) {
        		pg_query($con, "ROLLBACK");
        	} else {
        		pg_query($con, "COMMIT");
        		$msg_success[] = "O upload do arquivo foi realizado com sucesso<br>Ténicos cadastrados com sucesso!";
        	}
    	}

    }
}

if ($_POST["alterarStatus"]) {
	$tecnico = $_POST["tecnico"];
	$acao    = $_POST["acao"];
	
	if ($acao == "inativar") {
		$aux_sql  = " = FALSE ";
		$aux_echo = "inativado";

	} else {
		$aux_sql = " = TRUE ";
		$aux_echo = "ativado";
	}

	$sql = "UPDATE tbl_tecnico SET ativo $aux_sql WHERE fabrica = $login_fabrica AND tecnico = $tecnico";
	$res = pg_query($con, $sql);

	if (pg_last_error($con)) {
		echo "KO|Erro ao $acao o tecnico!";
	} else {
		echo "OK|O técnico foi $aux_echo com sucesso!";
	}

	exit;
}

$layout_menu = "gerencia";
$title = "CADASTRO DE TÉCNICOS ESPORÁDICOS";
include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
	function alterarStatus(tecnico, acao, linha) {
		$.ajax({
	        type: 'POST',
	        url: "<?=$PHP_SELF?>",
	        data: {
	            alterarStatus: true,
	            tecnico: tecnico,
	            acao: acao
	        },
		}).done(function(data) {
			data = data.split("|");
			if (data[0] == "KO") {
				alert(data[1]);
			} else {
				if (acao == "ativar") {
					$("#btn_inativar_" + linha).css("display", "block");
					$("#btn_ativar_" + linha).css("display", "none");
				} else {
					$("#btn_ativar_" + linha).css("display", "block");
					$("#btn_inativar_" + linha).css("display", "none");
				}
				alert(data[1]);
			}
		});
	}

	function editarTecnico(id_tec, codigo, nome, telefone, endereco, cidade, estado) {
		$("#id_tec").val(id_tec);
		$("#codigo").val(codigo);
		$("#nome").val(nome);
		$("#telefone").val(telefone);
		$("#endereco").val(endereco);
		$("#cidade").val(cidade);
		$("#estado").val(estado);
		$('html, body').animate({scrollTop:0}, 'slow');
	}

	$(function() {
		$("#telefone").mask("(99) 99999-9999");

		var table = new Object();
        table['table'] = '#resultado_tecnicos';
        table['type'] = 'full';
        $.dataTableLoad(table);
	});
</script>

<?php if (count($msg_erro["msg"]) > 0) {?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php } ?>

<?php if (count($msg_success) > 0) {?>
    <div class="alert alert-success">
		<h4><?=implode("<br />", $msg_success)?></h4>
    </div>
<?php } ?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name="frm_tecnico_esporadico" method="POST" action="<?=$PHP_SELF?>" class="form-search form-inline tc_formulario">
	<div class='titulo_tabela '>Informações de Cadastro e Pesquisa</div>
	<br/>
	<input type="hidden" id="id_tec" name="id_tec" value="<?=isset($id_tec)?$id_tec : ''?>">
	<div class='row-fluid'>
	    <div class='span2'></div>
	    <div class='span4'>
	        <div class='control-group <?=(in_array("codigo", $msg_erro["campos"])) ? "error" : ""?>'>
	            <label class='control-label' for='codigo'>Código</label>
	            <div class='controls controls-row'>
	                <div class='span7 input-append'>
	                    <h5 class='asteristico'>*</h5>
	                    <input type="text" id="codigo" name="codigo" class='span12' value="<?=isset($codigo)? $codigo : ''?>" >
	                </div>
	            </div>
	        </div>
	    </div>
	    <div class='span4'>
	        <div class='control-group <?=(in_array("nome", $msg_erro["campos"])) ? "error" : ""?>'>
	            <label class='control-label' for='nome'>Nome</label>
	            <div class='controls controls-row'>
	                <div class='span7 input-append'>
	                    <?php if (!in_array($login_fabrica,array(94,120,201,165,167,203))) { ?>
	                    <h5 class='asteristico'>*</h5>
	                    <?php } ?>
	                    <input type="text" id="nome" name="nome" class='span12' value="<?=isset($nome) ? $nome :''?>" >
	                </div>
	            </div>
	        </div>
	    </div>
	</div>
	<div class='row-fluid'>
	    <div class='span2'></div>
	    <div class='span4'>
	        <div class='control-group <?=(in_array("telefone", $msg_erro["campos"])) ? "error" : ""?>'>
	            <label class='control-label' for='telefone'>Telefone</label>
	            <div class='controls controls-row'>
	                <div class='span7 input-append'>
	                    <input type="text" id="telefone" name="telefone" class='span12' value="<?=isset($telefone)?$telefone:''?>" >
	                </div>
	            </div>
	        </div>
	    </div>
	    <div class='span6'>
	        <div class='control-group <?=(in_array("endereco", $msg_erro["campos"])) ? "error" : ""?>''>
	            <label class='control-label' for='produto_descricao'>Endereço</label>
	            <div class='controls controls-row'>
	                <div class='span7 input-append'>
	                    <input type="text" id="endereco" name="endereco" class='span12' value="<?=isset($endereco)?$endereco:''?>" >
	                </div>
	            </div>
	        </div>
	    </div>
	</div>
	<div class='row-fluid'>
	    <div class='span2'></div>
	    <div class='span4'>
	        <div class='control-group <?=(in_array("cidade", $msg_erro["campos"])) ? "error" : ""?>'>
	            <label class='control-label' for='cidade'>Cidade</label>
	            <div class='controls controls-row'>
	                <div class='span7 input-append'>
	                    <input type="text" id="cidade" name="cidade" class='span12' value="<?=isset($cidade)?$cidade:''?>" >
	                </div>
	            </div>
	        </div>
	    </div>
	    <div class='span4'>
	        <div class='control-group <?=(in_array("estado", $msg_erro["campos"])) ? "error" : ""?>'>
	            <label class='control-label' for='estado'>Estado</label>
	            <div class='controls controls-row'>
	                <div class='span7 input-append'>
	                    <select name="estado" id="estado">
	                        <option value="">Selecione</option>
	                        <option val="AC">AC</option>
	                        <option val="AL">AL</option>
	                        <option val="AM">AM</option>
	                        <option val="AP">AP</option>
	                        <option val="BA">BA</option>
	                        <option val="CE">CE</option>
	                        <option val="DF">DF</option>
	                        <option val="ES">ES</option>
	                        <option val="GO">GO</option>
	                        <option val="MA">MA</option>
	                        <option val="MG">MG</option>
	                        <option val="MS">MS</option>
	                        <option val="MT">MT</option>
	                        <option val="PA">PA</option>
	                        <option val="PB">PB</option>
	                        <option val="PE">PE</option>
	                        <option val="PI">PI</option>
	                        <option val="PR">PR</option>
	                        <option val="RJ">RJ</option>
	                        <option val="RN">RN</option>
	                        <option val="RO">RO</option>
	                        <option val="RR">RR</option>
	                        <option val="RS">RS</option>
	                        <option val="SC">SC</option>
	                        <option val="SE">SE</option>
	                        <option val="SP">SP</option>
	                        <option val="TO">TO</option>
	                    </select>
	                </div>
	            </div>
	        </div>
	    </div>
	</div>
	<br />
	<p><br/>
		<button class='btn' type="submit" name="btn_acao" value="gravar">Gravar</button>
	    <button class='btn btn-info' type="submit" name="btn_acao" value="listar">Listar</button>
	</p><br/>
</form>

<div class="alert alert-warning">
    O arquivo deverá seguir o seguinte layout em seu conteúdo, sendo <strong>código;nome;telefone;endereço;cidade;estado</strong>, separados por <strong>ponto e virgula (;)</strong>.
    Confira o exemplo abaixo: <br> <br>
    codigo123;Técnico de Exemplo 1;1134154896;Avenida Paulista;São Paulo;SP  <br>
    codigo321;Técnico de Exemplo 2;1134154896;Avenida Paulista;São Paulo;SP  <br>
</div>

<form name="frm_input_file" method="POST" action="<?=$PHP_SELF?>" class='form-search form-inline tc_formulario' enctype="multipart/form-data">

    <div class='titulo_tabela '>Upload de Arquivos de Técnicos Esporádicos</div>
    <br/>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class='span8'>
            <label class='control-label' for='peca_referencia'>Arquivo</label>
            <div class='controls controls-row'>
                <div class='span7 input-append'>
                    <h5 class='asteristico'>*</h5>
                    <input type="file" id="arquivo" name="arquivo" class='span12'>
                </div>
            </div>
        </div>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span8 tac">
            <button class='btn btn-info' type="submit" name="btn_acao" value="upload">Realizar Upload</button>
        </div>
    </div>
</form>

<?php if ($listar == true) {?>
</div>
<div style="padding: 5px;">
	<table id="resultado_tecnicos" class='table table-striped table-bordered table-hover table-fixed'>
		<thead>
			<tr class="titulo_tabela">
				<th colspan="8">Técnicos Cadastrados</th>
			</tr>
			<tr class='titulo_coluna' >
	            <th>Código</th>
	            <th>Nome</th>
	            <th>Telefone</th>
	            <th>Endereço</th>
	            <th>Cidade</th>
	            <th>Estado</th>
	            <th>Qtde Atendimento</th>
	            <th>Ação</th>
	        </tr>
		</thead>
		<tbody>
			<?php
				if (!empty($tecnicos)) {
					foreach ($tecnicos as $key => $tecnico) {
						$id_tec   = $tecnico['tecnico'];
				 		$codigo   = $tecnico['codigo'];
				 		$nome     = $tecnico['nome'];
				 		$telefone = $tecnico['telefone'];
				 		$endereco = $tecnico['endereco'];
				 		$cidade   = $tecnico['cidade'];
				 		$estado   = $tecnico['estado'];
				 		$qtde     = $tecnico['qtde'];
				 		$ativo    = $tecnico['ativo'];

						$display_ativar   = " style='display: none' ";
						$display_inativar = " style='display: none' ";

						if ($ativo == "t") {
							$display_inativar = " style='display:block' ";
						} else {
							$display_ativar = " style='display:block' ";
						}

				 		$aux_cidade = $cidade;
				 		$aux_end    = $endereco;
				 		$aux_cidade = str_replace("'", "\'", $aux_cidade);
						$aux_cidade = str_replace('"', '\"', $aux_cidade);
				 		$aux_end    = str_replace("'", "\'", $aux_end);
						$aux_end    = str_replace('"', '\"', $aux_end);

				 		$val_editar = "'$id_tec', '$codigo', '$nome', '$telefone', '$aux_end', '$aux_cidade', '$estado'";
				 		?>
							<tr>
								<td class='tal'>
									<button id="btn_alterar" type="button" class="btn btn-link"onclick="javascript:editarTecnico(<?=$val_editar;?>);" ><?=$codigo;?></button>		
								</td>
								<td class='tal'> <?=$nome;?> </td>
								<td class='tac'> <?=$telefone;?> </td>
								<td class='tal'> <?=$endereco;?> </td>
								<td class='tal'> <?=$cidade;?> </td>
								<td class='tac'> <?=$estado;?> </td>
								<td class='tal'> <?=$qtde;?> </td>
								<td class='tac'>
									<center>
										<button id="btn_ativar_<?=$key;?>" <?=$display_ativar;?> type="button" class="btn btn-success" onclick="javascript:alterarStatus(<?=$id_tec;?>,'ativar',<?=$key;?>);" >Ativar</button>&nbsp;
										<button id="btn_inativar_<?=$key;?>" <?=$display_inativar;?> type="button" class="btn btn-danger"onclick="javascript:alterarStatus(<?=$id_tec;?>,'inativar',<?=$key;?>);" >Inativar</button>&nbsp;
									</center>
								</td>
							</tr>
						<?
					}
				}
			?>
		</tbody>
	</table>
</div>
<?php } ?>
<br>
<?php include 'rodape.php'; ?>
