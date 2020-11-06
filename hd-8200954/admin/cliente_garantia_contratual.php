<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastro, gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

$array_estados = $array_estados();

$cliente = $_REQUEST['cliente'];

function cliente_contratual() {
	global $con, $login_fabrica;

	$sql = "SELECT grupo_cliente FROM tbl_grupo_cliente
			WHERE fabrica = $login_fabrica AND descricao = 'Garantia Contratual'
			AND ativo IS TRUE";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		return pg_fetch_result($res, 0, 'grupo_cliente');
	}

}

function retorna_cidade($cidade) {
	global $con, $login_fabrica;

	if (is_string($cidade)) {
		$sql = "SELECT DISTINCT cidade AS cidade FROM tbl_cidade WHERE UPPER(nome) = UPPER('".$cidade."')
	                ";
	    $res = pg_query($con, $sql);

	    if (pg_num_rows($res) > 0) {
	    	return pg_fetch_result($res, 0, 'cidade');
	    } else {
	    	return false;
	    }
	}
}

if (isset($_POST["ajax_busca_cidade"]) && !empty($_POST["estado"])) {
    $estado = strtoupper($_POST["estado"]);

    if (array_key_exists($estado, $array_estados)) {
        $sql = "SELECT DISTINCT * FROM (
                    SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('{$estado}')
                    UNION (
                        SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('{$estado}')
                    )
                ) AS cidade
                ORDER BY cidade ASC";
        $res = pg_query($con, $sql);
        if (pg_num_rows($res) > 0) {
            $array_cidades = array();

            while ($result = pg_fetch_object($res)) {
                $array_cidades[] = $result->cidade;
            }

            $retorno = array("cidades" => $array_cidades);
        } else {
            $retorno = array("error" => utf8_encode("nenhuma cidade encontrada para o estado: {$estado}"));
        }
    } else {
        $retorno = array("error" => utf8_encode("estado não encontrado"));
    }

    exit(json_encode($retorno));
}

if (isset($_POST['btn_upload'])) {
	$registro    = array();
    $extensao    = strtolower(preg_replace("/.+\./", "", $_FILES["arquivo"]["name"]));
    
    if (!in_array($extensao, array("csv","txt"))) {
        $msg_erro["msg"][] = "Formato de arquivo deve ser CSV ou TXT";
    }

    $arquivo = fopen($_FILES['arquivo']['tmp_name'], 'r+');

    if ($arquivo && count($msg_erro) == 0) {

        while(!feof($arquivo)){

            $linha = fgets($arquivo,4096);
            
            if (strlen(trim($linha)) > 0) {
                $registro[] = explode(";", $linha);
            }

        }

        fclose($f);

	    pg_query($con,"BEGIN TRANSACTION");
	    $count = 1;

	    foreach ($registro as $key => $registro) {

	        $codigo_cliente = trim($registro[0]);
			$nome_cliente   = pg_escape_string(trim(utf8_decode($registro[1])));
			$telefone       = trim($registro[2]);
			$endereco       = pg_escape_string(trim(utf8_decode($registro[3])));
			$estado         = trim($registro[4]);
			$cidade         = retira_acentos(utf8_decode($registro[5]));
			$grupo_cliente  = cliente_contratual();

			if (!empty($cidade)) {
				$cidade_id = retorna_cidade(trim($cidade));

				if (empty($cidade_id)) {
					$msg_erro["msg"][] = "Linha ".$count.": Cidade não encontrada";
				}

			} else {
				$cidade_id = "null";
			}

			if (strlen($codigo_cliente) < 3) {
				$msg_erro['msg'][] = "Linha ".$count.": O limite minímo de caracteres do campo código é 3";
			}

			if (empty($codigo_cliente) || empty($nome_cliente)) {
				$msg_erro["msg"][] = "Linha ".$count.": Código e nome do cliente não podem ser vazios";
			}

			if (count($msg_erro) == 0) {
				$sql = "SELECT tbl_cliente.cliente 
						FROM tbl_cliente
						JOIN tbl_grupo_cliente 
						ON tbl_grupo_cliente.grupo_cliente = tbl_cliente.grupo_cliente
						WHERE tbl_cliente.grupo_cliente = $grupo_cliente
						AND UPPER(tbl_cliente.codigo_cliente) = UPPER('$codigo_cliente')";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					$cliente = pg_fetch_result($res, 0, 'cliente');

					$sql = "UPDATE tbl_cliente 
							SET  codigo_cliente = '$codigo_cliente', 
								nome = '$nome_cliente', 
								endereco = '$endereco', 
								fone = '$telefone', 
								estado = '$estado', 
								cidade = $cidade_id, 
							grupo_cliente = $grupo_cliente
							WHERE cliente = $cliente
						";
					$res = pg_query($con, $sql);
				} else {
					$sql = "INSERT INTO tbl_cliente (codigo_cliente, nome, endereco, fone, estado, cidade, grupo_cliente) 
						VALUES ('$codigo_cliente', '$nome_cliente', '$endereco', '$telefone', '$estado', $cidade_id, $grupo_cliente) 
						RETURNING cliente";
					$res = pg_query($con, $sql);
				}

				if (pg_last_error()) {
					$msg_erro["msg"][] = "Linha ".$count.": Erro ao gravar dados";
				}
			}

			$count++;
	    }

	    if (count($msg_erro) > 0) {
	    	pg_query($con, "ROLLBACK TRANSACTION");
	    } else {
	    	pg_query($con, "COMMIT TRANSACTION");

	    	$codigo_cliente = "";
			$nome_cliente   = "";
			$telefone       = "";
			$endereco       = "";
			$estado         = "";
			$cidade         = "";
			$grupo_cliente  = "";
			$cliente        = "";

	    	$msg_success['msg'][] = "Clientes cadastrados/alterados com sucesso";
	    }
    }
}

if (isset($_POST['btn_gravar'])) {
	$codigo_cliente = $_POST['cliente_codigo'];
	$nome_cliente   = pg_escape_string($_POST['cliente_nome']);
	$telefone       = $_POST['telefone'];
	$endereco       = pg_escape_string($_POST['endereco']);
	$estado         = $_POST['estado'];
	$cidade_nome    = $_POST['cidade'];

	if (strlen($codigo_cliente) < 3) {
		$msg_erro['msg'][]    = "O limite minímo de caracteres do campo código é 3";
		$msg_erro['campos'][] = "cliente";
	}

	if (!empty($cidade_nome)) {
		$cidade = retorna_cidade($cidade_nome);
	} else {
		$msg_erro['msg'][]      = "Preencha a cidade";
		$msg_erro['campos'][] = "cidade";
	}

	if (empty($estado)) {
		$msg_erro['msg'][]      = "Preencha o estado";
		$msg_erro['campos'][] = "estado";
	}

	$grupo_cliente  = cliente_contratual();

	if (empty($codigo_cliente) || empty($nome_cliente)) {
		$msg_erro['msg'][]    = "Preencha os campos obrigatórios";
		$msg_erro['campos'][] = "cliente";
	} else if (empty($cliente)) {
		$sql = "SELECT tbl_cliente.cliente 
				FROM tbl_cliente
				JOIN tbl_grupo_cliente 
				ON tbl_grupo_cliente.grupo_cliente = tbl_cliente.grupo_cliente
				WHERE tbl_cliente.grupo_cliente = $grupo_cliente
				AND UPPER(tbl_cliente.codigo_cliente) = UPPER('$codigo_cliente')";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$msg_erro['msg'][]    = "Já existe um cliente cadastrado com o código informado";
			$msg_erro['campos'][] = "cliente";
		}
	}

	if (count($msg_erro) == 0) {
		if (!empty($cliente)) {
			$sql = "UPDATE tbl_cliente 
					SET  codigo_cliente = '$codigo_cliente', 
					nome = '$nome_cliente', 
					endereco = '$endereco', 
					fone = '$telefone', 
					estado = '$estado', 
					cidade = $cidade, 
					grupo_cliente = $grupo_cliente
					WHERE cliente = $cliente
					";
			$res = pg_query($con, $sql);

			if (!pg_last_error()) {
				$msg_success['msg'][]    = "Cliente alterado com sucesso";

				$cliente = pg_fetch_result($res, 0, 'cliente');
			} else {
				$msg_erro['msg'][]    = "Erro ao alterar cliente";
			}
		} else {
			$sql = "INSERT INTO tbl_cliente (codigo_cliente, nome, endereco, fone, estado, cidade, grupo_cliente) 
					VALUES ('$codigo_cliente', '$nome_cliente', '$telefone', '$endereco', '$estado', $cidade, $grupo_cliente) 
					RETURNING cliente";
			$res = pg_query($con, $sql);

			if (!pg_last_error()) {
				$codigo_cliente = "";
				$nome_cliente   = "";
				$telefone       = "";
				$endereco       = "";
				$estado         = "";
				$cidade         = "";
				$grupo_cliente  = "";
				$cliente        = "";
				$cliente_codigo = "";
				$cliente_nome   = "";

		    	$msg_success['msg'][] = "Clientes cadastrados/alterados com sucesso";
			} else {
				$msg_erro['msg'][]    = "Erro ao inserir cliente";
			}
		}
	}
} else if (!empty($cliente)) {

	$sqlLista = "SELECT cliente,
						codigo_cliente,
						tbl_cliente.nome,
						fone,
						endereco,
						tbl_cliente.estado,
						tbl_cidade.nome as nome_cidade,
						tbl_cidade.cidade
				 FROM tbl_cliente
				 LEFT JOIN tbl_cidade USING(cidade)
				 WHERE tbl_cliente.grupo_cliente = ".cliente_contratual()."
				 AND tbl_cliente.cliente = $cliente
				 ";
	$resLista = pg_query($con, $sqlLista);

	if (!empty($cliente)) {
		$cliente_codigo = pg_fetch_result($resLista, 0, 'codigo_cliente');
		$cliente_nome   = pg_fetch_result($resLista, 0, 'nome');
		$telefone       = pg_fetch_result($resLista, 0, 'fone');
		$endereco       = pg_fetch_result($resLista, 0, 'endereco');
		$estado         = pg_fetch_result($resLista, 0, 'estado');
		$cidade         = pg_fetch_result($resLista, 0, 'cidade');
		$cidade_nome    = pg_fetch_result($resLista, 0, 'nome_cidade');
	}
}

$layout_menu = "gerencia";
$title = "RELATÓRIO DE OS x Atendimentos";
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
<script>
	$(function() {

		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			var attrAdicionais = ["contratual"];

			$.lupa($(this), attrAdicionais);
		});

		$("#estado").change(function() {
	        busca_cidade($(this).val());
	    });
	});

	function retorna_cliente(retorno){
		window.location.href = "cliente_garantia_contratual.php?cliente="+retorno.cliente;
    }

    function busca_cidade(estado) {
	    $("#cidade").find("option").first().nextAll().remove();

	    if (estado.length > 0) {
	        $.ajax({
	            async: false,
	            url: "cliente_garantia_contratual.php",
	            type: "POST",
	            data: { ajax: true, ajax_busca_cidade: true, estado: estado },
	            beforeSend: function() {
	                if ($("#cidade").next("img").length == 0) {
	                    $("#cidade").hide().after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
	                }
	            },
	            complete: function(data) {
	                data = $.parseJSON(data.responseText);

	                if (data.error) {
	                    alert(data.error);
	                } else {
	                	console.log(data);
	                    $.each(data.cidades, function(key, value) {
	                        var option = $("<option></option>", { value: value, text: value });
	                        $("#cidade").append(option);
	                    });
	                }

	                $("#cidade").show().next().remove();
	            }
	        });
	    }

	    if(typeof cidade != "undefined" && cidade.length > 0){

	        $("#cidade option[value='"+cidade+"']").attr('selected','selected');

	    }

	}
</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<?php
if (count($msg_success["msg"]) > 0) {
?>
    <div class="alert alert-success">
		<h4><?=implode("<br />", $msg_success["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela '>Cadastro de Clientes Contratuais</div>
	<br />
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("cliente", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='cliente_codigo'>Código Cliente</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="text" id="cliente_codigo" name="cliente_codigo" class='span12' maxlength="20" value="<? echo $cliente_codigo ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="cliente" parametro="codigo" contratual="t" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("cliente", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='cliente_nome'>Nome Cliente</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" id="cliente_nome" name="cliente_nome" class='span12' value="<? echo $cliente_nome ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="cliente" parametro="nome" contratual="t" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("telefone", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='cliente_nome'>Telefone</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" id="telefone" name="telefone" class='span9' value="<? echo $telefone ?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("endereco", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='cliente_codigo'>Endereço</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" id="endereco" name="endereco" class='span12' maxlength="20" value="<? echo $endereco ?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("estado", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='cliente_codigo'>Estado</label>
				<div class='controls controls-row'>
					<div class='span10 input-append'>
						<h5 class='asteristico'>*</h5>
						<select id="estado" name="estado" class="span12" >
                            <option value="" >Selecione</option>
                            <?php
                            #O $array_estados está no arquivo funcoes.php
                            foreach ($array_estados as $sigla => $nome_estado) {
                                $selected = ($sigla == $estado) ? "selected" : "";

                                echo "<option value='{$sigla}' {$selected} >" . $nome_estado . "</option>";
                            }
                            ?>
                        </select>
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("cidade", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='cliente_nome'>Cidade</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
					  <h5 class='asteristico'>*</h5>
				      <select id="cidade" name="cidade" class="span12">
                            <option value="" >Selecione</option>

                            <?php
                            if (strlen($estado) > 0) {
                                $sql = "SELECT DISTINCT * FROM (
                                            SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('".$estado."')
                                            UNION (
                                                SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('".$estado."')
                                            )
                                        ) AS cidade
                                        ORDER BY cidade ASC";
                                $res = pg_query($con, $sql);

                                if (pg_num_rows($res) > 0) {
                                    while ($result = pg_fetch_object($res)) {
                                        $selected  = (trim($result->cidade) == strtoupper(retira_acentos(trim($cidade_nome)))) ? "SELECTED" : "";

                                        echo "<option value='{$result->cidade}' {$selected} >{$result->cidade} </option>";
                                    }
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
	<br />
	<input type="hidden" name="cliente" id="cliente" value="<?= $cliente ?>" />
	<button class="btn" name="btn_gravar"><?= (!empty($cliente)) ? 'Alterar' : 'Gravar' ?></button>
	<a href="cliente_garantia_contratual.php?listar_todos=sim">
		<button type="button" class="btn btn-primary">Listar Todos</button>
	</a>
	<br /><br />
</form>
<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data" align='center' class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela '>Cadastro via Upload</div>
	<div class="alert alert-warning"><h5><strong>O arquivo deve ser no formato CSV, com os dados separados por ;<br />Ex:</strong>codigo;nome;telefone;endereço;uf;cidade</h5></div>
	<div class="row-fluid">
		<div class="span4"></div>
		<div class='span8'>
            <div class='control-group'>
                <label class='control-label' for='peca_referencia'>Arquivo CSV</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="file" id="arquivo" name="arquivo" class='span12' />
                    </div>
                </div>
            </div>
        </div>
	</div>
	<button class="btn btn-info" name="btn_upload">Realizar Upload</button>
	<br /><br />
</form>
<?php
	if (isset($_GET['listar_todos'])) {
		$sqlLista = "SELECT cliente,
						codigo_cliente,
						tbl_cliente.nome,
						fone,
						endereco,
						tbl_cliente.estado,
						tbl_cidade.nome as nome_cidade,
						tbl_cidade.cidade
					 FROM tbl_cliente
					 LEFT JOIN tbl_cidade USING(cidade)
					 WHERE tbl_cliente.grupo_cliente = ".cliente_contratual();
		$resLista = pg_query($con, $sqlLista);

		if (pg_num_rows($resLista) > 0) {
		?>
		<br />
		<table id="resultado_cliente" class='table table-striped table-bordered table-hover table-fixed' >
			<thead>
				<tr>
					<th class="titulo_tabela" colspan="6">Lista de Clientes Garantia Contratual</th>
				</tr>
				<tr class='titulo_coluna' >
					<th>Código Cliente</th>
					<th>Nome Cliente</th>
					<th>Telefone</th>
		            <th>Endereço</th>
		            <th>Estado</th>
		            <th>Cidade</th>
				</tr>
			</thead>
			<tbody>
				<?php
				for ($x=0;$x < pg_num_rows($resLista);$x++) { 
					$cliente_codigo = pg_fetch_result($resLista, $x, 'codigo_cliente');
					$cliente_nome   = pg_fetch_result($resLista, $x, 'nome');
					$telefone       = pg_fetch_result($resLista, $x, 'fone');
					$endereco       = pg_fetch_result($resLista, $x, 'endereco');
					$estado         = pg_fetch_result($resLista, $x, 'estado');
					$cidade_nome    = pg_fetch_result($resLista, $x, 'nome_cidade');
					$cliente        = pg_fetch_result($resLista, $x, 'cliente');
				?>
					<tr>
						<td class="tac">
							<a href="<?=$PHP_SELF?>?cliente=<?= $cliente ?>&listar_todos=sim"><?= $cliente_codigo ?></a>
						</td>
						<td>
							<a href="<?=$PHP_SELF?>?cliente=<?= $cliente ?>&listar_todos=sim"><?= $cliente_nome ?></a>
						</td>
						<td class="tac"><?= $telefone ?></td>
						<td><?= $endereco ?></td>
						<td class="tac"><?= $estado ?></td>
						<td><?= $cidade_nome ?></td>
					</tr>
				<?php
				} ?>
			</tbody>
		</table>
		<br />

		<script>
			$.dataTableLoad({ table: "#resultado_cliente" });
		</script>
	<?php
	}
}

include "rodape.php";
?>

                                