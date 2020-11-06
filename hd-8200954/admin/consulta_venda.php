<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

if ($areaAdmin === true) {
    include 'autentica_admin.php';
} else {
    include 'autentica_usuario.php';
}

include 'funcoes.php';

if (isset($_POST['excluir_venda'])) {

	$venda = $_POST['venda'];

	$query = 'UPDATE tbl_venda 
			  SET fabrica = 0
			  WHERE venda = ' . $venda;

	$res = pg_query($con, $query);

	$resposta['result'] = "error";
	
	if (strlen(pg_last_error()) == 0) {

		$resposta['result'] = "success";
	}
	
	$resposta = json_encode($resposta);

	exit($resposta);
}

if ($_POST["btn_acao"] == "submit") {
    $data_inicial                   = $_POST["data_inicial"];
    $data_final                     = $_POST["data_final"];
    $produto_referencia_equivalente = trim($_POST["produto_referencia_equivalente"]);
    $produto_referencia             = trim($_POST["produto_referencia"]);
    $produto_descricao              = trim($_POST["produto_descricao"]);
    $consumidor_nome                = trim($_POST["consumidor_nome"]);
    $consumidor_cpf                 = trim($_POST["consumidor_cpf"]);
    $numero_serie                   = trim($_POST["numero_serie"]);

	if ((!strlen($data_inicial) || !strlen($data_final)) && empty($os)) {
        if (!in_array($login_fabrica,array(148,161)) || (empty($numero_serie) && empty($produto_referencia_equivalente))) {
            $msg_erro["msg"][]    = "Preencha os campos obrigatórios";
            $msg_erro["campos"][] = "data";

            if (in_array($login_fabrica,array(161))) {
                $msg_erro["campos"][] = "numero_serie";
            }
        }
	} else if (!empty($data_inicial) && !empty($data_final)) {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) || !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di}";
			$aux_data_final   = "{$yf}-{$mf}-{$df}";

			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = "Data final não pode ser menor que a Data inicial";
				$msg_erro["campos"][] = "data";
			}
			if ($login_fabrica == 148) {
				if (strtotime($aux_data_inicial.'+6 month') < strtotime($aux_data_final) ) {
					$msg_erro["msg"][]    = "O intervalo entre as datas não pode ser maior que 6 meses";
					$msg_erro["campos"][] = "data";
				}
			} else {
				if (strtotime($aux_data_inicial.'+3 month') < strtotime($aux_data_final) ) {
					$msg_erro["msg"][]    = "O intervalo entre as datas não pode ser maior que 3 meses";
					$msg_erro["campos"][] = "data";
				}
			}
		}
	}

	if ($areaAdmin === true) {
		$codigo_posto    = trim($_POST["codigo_posto"]);
		$descricao_posto = trim($_POST["descricao_posto"]);

		if (strlen($codigo_posto) > 0 || strlen($descricao_posto) > 0) {
			$sql = "SELECT tbl_posto_fabrica.posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica USING(posto)
					WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
					AND (
						(UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
						OR
						(UPPER(fn_retira_especiais(tbl_posto.nome)) = UPPER(fn_retira_especiais('{$descricao_posto}')))
					)";
			$res = pg_query($con ,$sql);

			if (!pg_num_rows($res)) {
				$msg_erro["msg"][]    = "Posto não encontrado";
				$msg_erro["campos"][] = "posto";
			} else {
                $posto = pg_fetch_result($res, 0, "posto");
                if ($login_fabrica == 148 && !empty($posto) && empty($data_inicial) && empty($data_final)) {
                    unset($msg_erro);
                }
			}
		}
	}

	if (strlen($produto_referencia) > 0 or strlen($produto_descricao) > 0){
        if ($login_fabrica == 148 && (!empty($produto_referencia) || !empty($produto_descricao)) && empty($data_inicial) && empty($data_final)) {
            unset($msg_erro);
        }
		$sql = "SELECT produto
				FROM tbl_produto
				WHERE fabrica_i = {$login_fabrica}
				AND (
                    (UPPER(referencia) = UPPER('{$produto_referencia}'))
                    OR
                    (UPPER(descricao) = UPPER('{$produto_descricao}'))
                )";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Produto não encontrado";
			$msg_erro["campos"][] = "produto";
		} else {
			$produto = pg_fetch_result($res, 0, "produto");
		}
	}

	if (strlen($consumidor_nome) > 0 || strlen($consumidor_cpf) > 0){
        if ($login_fabrica == 148 && (!empty($consumidor_nome) || !empty($consumidor_cpf)) && empty($data_inicial) && empty($data_final)) {
            unset($msg_erro);
        }
        $cond_consumidor = array();

        if (!empty($consumidor_nome)) {
            $cond_consumidor[] =  "(UPPER(nome) = UPPER('{$consumidor_nome}'))";
        }

        if (!empty($consumidor_cpf)) {
            $cond_consumidor[] = "(UPPER(cpf) = UPPER('".preg_replace("/\D/", "", $consumidor_cpf)."'))";
        }

		$sql = "SELECT cliente
				FROM tbl_cliente
                WHERE (" . implode(" AND ", $cond_consumidor) . ")";
//                 echo nl2br($sql);
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Consumidor não encontrado";
			$msg_erro["campos"][] = "consumidor";
		} else {
			$cliente = pg_fetch_result($res, 0, "cliente");
		}
	}

	if (!count($msg_erro["msg"])) {
		if ($areaAdmin === true) {
			if (!empty($posto)) {
				$wherePosto = "AND tbl_venda.posto = {$posto}";
			}
		} else {
			$wherePosto = "AND tbl_venda.posto = {$login_posto}";
		}

		if (!empty($produto)) {
			$whereProduto = "AND tbl_venda.produto = {$produto}";
		}

		if (!empty($cliente)) {
			$whereCliente = "AND tbl_venda.cliente = {$cliente}";
		}

        $whereSerie = '';
        if (!empty($numero_serie)) {
            $whereSerie = "AND LOWER(tbl_venda.serie) = LOWER('{$numero_serie}')";
        }

        $whereData = '';
        if (!empty($aux_data_inicial) and !empty($aux_data_final)) {
            $whereData = "AND tbl_venda.data_nf BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59'";
        }

        $whereProdEquiv = "";
        if (!empty($produto_referencia_equivalente)) {
            $whereProdEquiv = " AND tbl_produto.referencia ILIKE '%$produto_referencia_equivalente%'";
        }

        $sql = "
            SELECT  tbl_venda.venda,
                    tbl_posto_fabrica.codigo_posto AS posto_codigo,
                    tbl_posto.nome AS posto_nome,
                    tbl_cliente.nome AS cliente_nome,
                    tbl_cliente.cpf AS cliente_cpf,
                    tbl_cidade.estado AS cliente_estado,
                    tbl_cidade.nome AS cliente_cidade,
                    tbl_produto.referencia AS produto_referencia,
                    tbl_produto.descricao AS produto_descricao,
                    tbl_venda.serie,
                    tbl_venda.serie_motor,
                    tbl_venda.serie_transmissao,
                    tbl_venda.nota_fiscal,
                    TO_CHAR(tbl_venda.data_nf, 'DD/MM/YYYY') AS data_nf
            FROM    tbl_venda
       LEFT JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto     = tbl_venda.posto
                                        AND tbl_posto_fabrica.fabrica   = {$login_fabrica}
       LEFT JOIN    tbl_posto           ON  tbl_posto.posto             = tbl_posto_fabrica.posto
            JOIN    tbl_produto         ON  tbl_produto.produto         = tbl_venda.produto
                                        AND tbl_produto.fabrica_i       = {$login_fabrica}
            JOIN    tbl_cliente         ON  tbl_cliente.cliente         = tbl_venda.cliente
            JOIN    tbl_cidade          ON  tbl_cidade.cidade           = tbl_cliente.cidade
            WHERE   tbl_venda.fabrica = {$login_fabrica}
            $whereData
            {$wherePosto}
            {$whereProduto}
            {$whereCliente}
            $whereSerie
            $whereProdEquiv
      ORDER BY      tbl_venda.data_nf DESC";
//       echo nl2br($sql);
		$resSubmit = pg_query($con, $sql);

		if (pg_last_error($con)) {
			$msg_erro["msg"][] = "Erro ao realizar pesquisa";
		}
	}
}


if ($areaAdmin === true) {
    $layout_menu = "callcenter";
} else {
    $layout_menu = "os";
}

$title = "Consulta de Venda de Produto";

include 'cabecalho_new.php';

$plugins = array(
    "mask",
    "maskedinput",
    "shadowbox",
    "dataTable",
    "datepicker",
    "autocomplete"
);

include "plugin_loader.php";
?>

<script>

function excluirVenda(venda) {

	if (confirm("Deseja mesmo excluir a venda " + venda + " ?")) {
	    
	    $.ajax({
	        url:"<?=$PHP_SELF?>",
	        type:"POST",
	        dataType:"JSON",
	        data:{
	            excluir_venda: true,
	            venda : venda
	        }
	    }).done(function(data) {

	    	let linha = '.venda-' + venda; 

	        if (data.result == "success") {
	          
	        	let html = "<td colspan='13' style='text-align:center'; bgcolor='#32CD32'><h4 style='color:white'>Venda removida com sucesso</h4></td>";

	        	$(linha).html(html);

	        	setTimeout(function () {

	        		$(linha).detach();

	        	}, 3000);


	        } else {

	        	let html = "<td colspan='13' style='text-align:center'; bgcolor='tomato'><h4 style='color:white'>Erro ao remover venda</h4></td>";

				let original = $(linha).html();

	        	$(linha).html(html);

	        	setTimeout(function () {

	        		$(linha).html(original);

	        	}, 3000);
	        }
	    });
	}
}

$(function() {

	Shadowbox.init();
	$.datepickerLoad(["data_final", "data_inicial"]);

	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});

	$("input[name=cnpjCpf]").change(function(){
        $("#consumidor_cpf").unmask();

        var tipo = $(this).val();

        if(tipo == 'cnpj'){
            $("#consumidor_cpf").mask("99.999.999/9999-99");
        }else{
            $("#consumidor_cpf").mask("999.999.999-99");
        }
    });

});

function retorna_posto(retorno) {
    $("#codigo_posto").val(retorno.codigo);
	$("#descricao_posto").val(retorno.nome);
}

function retorna_produto(retorno) {
	$("#produto_referencia").val(retorno.referencia);
	$("#produto_descricao").val(retorno.descricao);
}

function retorna_cliente(retorno) {
	if(retorno.cpf.length == 14) {
		$("input[name=cnpjCpf][value=cnpj]")[0].checked = true;
		$("input[name=cnpjCpf][value=cnpj]").change();
	}else{
		$("input[name=cnpjCpf][value=cpf]")[0].checked = true;
		$("input[name=cnpjCpf][value=cpf]").change();
	}
	$("#consumidor_nome").val(retorno.nome);
	$("#consumidor_cpf").val(retorno.cpf);

}

</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?= implode("<br />", $msg_erro["msg"]) ?></h4>
    </div>
<?php
}
?>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_consulta' method="POST" class="form-search form-inline tc_formulario" >

	<div class="titulo_tabela" >Parâmetros de Pesquisa</div>

	<br/>

	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<div class="control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>" >
				<label class="control-label" for="data_inicial" >Data inicial</label>
				<div class="controls controls-row" >
					<div class="span12" >
<?php
    if ($login_fabrica != 148) {
?>
						<h5 class="asteristico" >*</h5>
<?php
    }
?>
						<input type="text" name="data_inicial" id="data_inicial" class="span6" value="<?=$_POST['data_inicial']?>" />
					</div>
				</div>
			</div>
		</div>
		<div class="span4">
			<div class="control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>" >
				<label class="control-label" for="data_final" >Data final</label>
				<div class="controls controls-row" >
					<div class="span12" >
<?php
    if ($login_fabrica != 148) {
?>
						<h5 class="asteristico" >*</h5>
<?php
    }
?>
						<input type="text" name="data_final" id="data_final" class="span6" value="<?=$_POST['data_final']?>" />
					</div>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>

<?php
    if ($areaAdmin === true && $login_fabrica != 161) {
?>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span4">
				<div class="control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>" >
					<label class="control-label" for="codigo_posto" >Código do Posto</label>
					<div class="controls controls-row" >
						<div class="span12 input-append" >
							<input type="text" name="codigo_posto" id="codigo_posto" class="span8" maxlength="20" value="<?=$_POST['codigo_posto']?>" />
							<span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class="span4">
				<div class="control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>" >
					<label class="control-label" for="descricao_posto" >Nome do Posto</label>
					<div class="controls controls-row" >
						<div class="span12 input-append" >
							<input type="text" name="descricao_posto" id="descricao_posto" class="span12" maxlength="150" value="<?=$_POST['descricao_posto']?>" />
							<span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class="span2"></div>
		</div>
<?php
	}
?>
    <div class="row-fluid">
        <div class="span2"></div>
    <?php if (in_array($login_fabrica,array(148,161))) { ?>
		<div class="span4">
            <div class='control-group <?=(in_array('numero_serie', $msg_erro['campos'])) ? "error" : "" ?>' >
                <label class="control-label" for="numero_serie">Número de Série</label>
                <div class="controls controls-row">
                    <div class="span10 input-append">
<?php
    if ($login_fabrica != 148) {
?>
                        <h5 class="asteristico" >*</h5>
<?php
    }
?>
                        <input id="numero_serie" name="numero_serie" class="span12" type="text" value="<?=$_POST['numero_serie']?>" />
                    </div>
                </div>
            </div>
        </div>
<?php
    }
    if ($login_fabrica == 148) {
?>
        <div class="span4">
            <div class='control-group' >
                <label class="control-label" for="produto_referencia_equivalente">Produto Referência Equivalente</label>
                <div class="controls controls-row">
                    <div class="span10 input-append">
                        <input id="produto_referencia_equivalente" name="produto_referencia_equivalente" class="span12" type="text" value="<?=$_POST['produto_referencia_equivalente']?>" />
                    </div>
                </div>
            </div>
        </div>
<?php
    }
?>
        <div class="span2"></div>
    </div>
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
            <div class='control-group <?=(in_array('consumidor', $msg_erro['campos'])) ? "error" : "" ?>' >
                <label class="control-label" for="consumidor_nome">Nome do Cliente</label>
                <div class="controls controls-row">
                    <div class="span10 input-append">
                        <input id="consumidor_nome" name="consumidor_nome" class="span12" type="text" value="<?=$_POST['consumidor_nome']?>" />
                        <span class="add-on" rel="lupa" ><i class="icon-search"></i></span>
                        <input type="hidden" name="lupa_config" tipo="cliente" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
		<div class="span4">
            <div class='control-group <?=(in_array('consumidor', $msg_erro['campos'])) ? "error" : "" ?>' >
                 <label class="control-label" for="consumidor_cpf">
                    CPF <input type="radio" id="cpf_cnpj" name="cnpjCpf" <?=(getValue('cnpjCpf') =='cpf') ? 'checked="checked"': ''?> value="cpf" />
                    CNPJ <input type="radio" id="cnpj_cpf" name="cnpjCpf" <?=(getValue('cnpjCpf') =='cnpj') ? 'checked="checked"': ''?> value="cnpj" />
                </label>
                <div class="controls controls-row">
	                <div class="span10 input-append">
	                    <input id="consumidor_cpf" name="consumidor_cpf" class="span12 " type="text" value="<?=$_POST['consumidor_cpf']?>" />
	                    <span class="add-on" rel="lupa" ><i class="icon-search"></i></span>
	                    <input type="hidden" name="lupa_config" tipo="cliente" parametro="cpf" />
	                </div>
                </div>
            </div>
        </div>
		<div class="span2"></div>
	</div>

	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<div class="control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>" >
				<label class="control-label" for="produto_referencia" >Referência do Produto</label>
				<div class="controls controls-row" >
					<div class="span12 input-append" >
						<input type="text" name="produto_referencia" id="produto_referencia" class="span8" maxlength="20" value="<?=$_POST['produto_referencia']?>" />
						<span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
					</div>
				</div>
			</div>
		</div>
		<div class="span4">
			<div class="control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>" >
				<label class="control-label" for="produto_descricao" >Descrição do Produto</label>
				<div class="controls controls-row" >
					<div class="span12 input-append" >
						<input type="text" name="produto_descricao" id="produto_descricao" class="span12" maxlength="150" value="<?=$_POST['produto_descricao']?>" />
						<span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
					</div>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>

	<br />

	<p>
		<button type="submit" class="btn" name="btn_acao" onclick="submitForm($(this).parents('form'));" value="submit" >Pesquisar</button>
	</p>

	<br />

</form>
</div>

<?php
if (isset($resSubmit)) {
	if (pg_num_rows($resSubmit) > 0) {
        if ($login_fabrica == 148) {
            $thead = "VENDA;POSTO;CLIENTE NOME;CLIENTE CPF/CNPJ;CLIENTE ESTADO;CLIENTE CIDADE;PRODUTO;NÚMERO SÉRIE;NÚEMRO SÉRIE MOTOR;NÚMERO SÉRIE TRANSMISSÃO;NOTA FISCAL;DATA NF\r\n";
        }
?>
		<table id="pesquisa_resultado" class="table table-bordered table-large" style="margin: 0 auto;" >
			<thead>
				<tr class="titulo_coluna" >
					<th>Venda</th>
					<?php
					if ($areaAdmin === true) {
					?>
						<th>Posto</th>
					<?php
					}
					?>
					<th>Cliente Nome</th>
					<th>Cliente CPF/CNPJ</th>
					<th>Cliente Estado</th>
					<th>Cliente Cidade</th>
					<th>Produto</th>
					<th>Número de Série</th>
                    <?php if ($login_fabrica != 161){ ?>
					<th>Número de Série Motor</th>
                    <th>Número de Série Transmissão</th>
                    <?php } ?>
					<th>Nota Fiscal</th>
					<th>Data Nota Fiscal</th>
					<?php if ($login_fabrica == 148) {?>
					<th>Ações</th>
					<?php }?>
				</tr>
			</thead>
			<tbody>
<?php
				pg_result_seek($resSubmit, 0);

				while ($result = pg_fetch_object($resSubmit)) {
					if (strlen($result->cliente_cpf) > 11) {
						$cliente_cpf = substr($result->cliente_cpf, 0, 2).".".substr($result->cliente_cpf, 2, 3).".".substr($result->cliente_cpf, 5, 3)."/".substr($result->cliente_cpf, 8, 4)."-".substr($result->cliente_cpf, 12, 2);
					} else {
						$cliente_cpf = substr($result->cliente_cpf, 0, 3).".".substr($result->cliente_cpf, 3, 3).".".substr($result->cliente_cpf, 6, 3)."-".substr($result->cliente_cpf, 9, 2);
					}

                    if ($login_fabrica == 148) {
                        $tbody .= $result->venda.";".$result->posto_codigo." - ".$result->posto_nome.";".$result->cliente_nome.";".$cliente_cpf.";";
                        $tbody .= $result->cliente_estado.";".$result->cliente_cidade.";".$result->produto_referencia." - ".$result->produto_descricao.";";
                        $tbody .= $result->serie.";".$result->serie_motor.";".$result->serie_transmissao.";".$result->nota_fiscal.";".$result->data_nf."\r\n";

                    }
?>
					<tr class="venda-<?=$result->venda?>">
						<td><a href="cadastro_venda.php?id=<?=$result->venda?>" target="_blank" ><?=$result->venda?></a></td>
						<?php
						if ($areaAdmin === true) {
						?>
							<td><?=$result->posto_codigo?> - <?=$result->posto_nome?></td>
						<?php
						}
						?>
						<td><?=$result->cliente_nome?></td>
						<td><?=$cliente_cpf?></td>
						<td><?=$result->cliente_estado?></td>
						<td><?=$result->cliente_cidade?></td>
						<td><?=$result->produto_referencia?> - <?=$result->produto_descricao?></td>
						<td><?=$result->serie?></td>
                        <?php if ($login_fabrica != 161){ ?>
						<td><?=$result->serie_motor?></td>
						<td><?=$result->serie_transmissao?></td>
                        <?php } ?>
						<td><?=$result->nota_fiscal?></td>
						<td><?=$result->data_nf?></td>
						<?php if ($login_fabrica == 148) {?>
						<td nowrap>
							<a href="cadastro_venda.php?id=<?=$result->venda?>&acao=alterar" target="_blank" class="btn btn-success">
							Alterar
							</a> 
							<button class="btn btn-danger" onclick="excluirVenda(<?=$result->venda?>)">Excluir</button>
						</td>
						<?php }?>
					</tr>
				<?php
				}
				?>
			</tbody>
		</table>
		<script>
			$.dataTableLoad({ table: "#pesquisa_resultado" });
		</script>
<?php
        $gerarCSV = $thead.$tbody;

        $data = date("d-m-Y-H:i");
        $filename = "consulta_venda-$data.csv";

        $file = fopen("/tmp/{$filename}", "w");

        fwrite($file,$gerarCSV);

        fclose($file);

        if (file_exists("/tmp/{$filename}")) {
            system("mv /tmp/{$filename} xls/{$filename}");
        }
?>
        <div id='gerar_excel' class="btn_excel">
            <a href="xls/<?=$filename?>" role="button" class="btn btn-success">
                <img src='imagens/excel.png' style="width:25px;height:25px;" />Gerar Arquivo Excel
            </a>
        </div>
<?php
	} else {
?>
		<div class="container" >
			<div class="alert alert-danger" >
		    	<h4>Nenhum resultado encontrado</h4>
			</div>
		</div>
	<?php
	}
}

include 'rodape.php';
?>
