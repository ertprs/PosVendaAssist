<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";

include 'autentica_admin.php';
include 'funcoes.php';

// Tabela Histórico OS Tecvoz
// os_tecvoz text,
// cliente_codigo text,
// cliente_razao text,
// produto_codigo text,
// produto_descricao text,
// qtde text,
// unidade text,
// situacao text,
// garantia text,
// doc_auxiliar text,
// data_abertura text,
// data_fechamento text,
// numero_serie text,
// status text

if ($_POST["btn_acao"] == "submit") {

    $data_inicial       = $_POST["data_inicial"];
    $data_final         = $_POST["data_final"];
    $os_tecvoz          = $_POST["os_tecvoz"];
    $produto_codigo     = $_POST["produto_codigo"];
    $produto_descricao  = $_POST["produto_descricao"];
    $cliente_codigo     = $_POST["cliente_codigo"];
    $cliente_razao      = $_POST["cliente_razao"];
    $numero_serie       = $_POST["numero_serie"];
    $status             = $_POST["status"];
    $cond               = "";

    # Validações
    if (empty($os_tecvoz)) {
        if ((empty($_POST['data_inicial']) || empty($_POST['data_final'])) && empty($os_tecvoz)) {
            $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
            $msg_erro["campos"][]   = "data";
            $msg_erro["campos"][]   = "os_tecvoz";
        }
    } else {
        $cond = "WHERE tbl_os_tecvoz.os_tecvoz = '{$os_tecvoz}'";
    }

    if (!empty($data_inicial) && !empty($data_final)) {

        list($d,$m,$y) = explode("/", $data_inicial);

        if (!checkdate($m, $d, $y)) {
            $msg_erro['msg'] = "Data inicial inválida";
            $msg_erro["campos"][]   = "data";
        } else {
            $data_inicial_formatada = "$y-$m-$d 00:00:00";
        }

        list($d,$m,$y) = explode("/", $data_final);

        if (!checkdate($m, $d, $y)) {
            $msg_erro['msg'] = "Data final inválida";
            $msg_erro["campos"][]   = "data";
        } else {
            $data_final_formatada = "$y-$m-$d 23:59:59";
        }

        $date = new DateTime($data_inicial_formatada); 
        $intervalo = $date->diff(new DateTime($data_final_formatada));
        $ano = $intervalo->format('%Y')*12;
        $mes = $intervalo->format('%m');
        $total_meses = $ano + $mes;

        if ($total_meses > 6) {
            $msg_erro["msg"]["obg"] = "Data Inicial e Data Final, deve estar em um intervalo de no maximo 6 meses.";
            $msg_erro["campos"][]   = "data";
        }

    }

    if (count($msg_erro['msg']) == 0) {

        if (!empty($data_inicial_formatada) && !empty($data_final_formatada)) {
            if (strlen($cond)) {
                $cond .= " AND tbl_os_tecvoz.data_abertura BETWEEN '{$data_inicial_formatada}' AND '{$data_final_formatada}'";
            } else {
                $cond = "WHERE tbl_os_tecvoz.data_abertura BETWEEN '{$data_inicial_formatada}' AND '{$data_final_formatada}'";
            }
        }

        if (!empty($produto_codigo)) {
            $cond .= " AND tbl_os_tecvoz.produto_codigo ILIKE '%{$produto_codigo}%' ";
        }

        if (!empty($produto_descricao)) {
            $cond .= " AND tbl_os_tecvoz.produto_descricao ILIKE '%{$produto_descricao}%' ";
        }

        if (!empty($cliente_codigo)) {
            $cond .= " AND tbl_os_tecvoz.cliente_codigo ILIKE '%{$cliente_codigo}%' ";
        }

        if (!empty($cliente_razao)) {
            $cond .= " AND tbl_os_tecvoz.cliente_razao ILIKE '%{$cliente_razao}%' ";
        }

        if (!empty($cliente_razao)) {
            $cond .= " AND tbl_os_tecvoz.cliente_razao ILIKE '%{$cliente_razao}%' ";
        }

        if (!empty($numero_serie)) {
            $cond .= " AND tbl_os_tecvoz.numero_serie ILIKE '%{$numero_serie}%' ";
        }

        if (!empty($status)) {
            $cond .= " AND tbl_os_tecvoz.status ILIKE '%{$status}%' ";
        }

        $sql = "
            SELECT *
            FROM tbl_os_tecvoz
	    {$cond}
	    ORDER BY data_abertura DESC;
        ";

        $resSubmit = pg_query($con,$sql);
    }
}

$layout_menu = "callcenter";
$title = "Consulta Ordens de Serviço Antigas";

include 'cabecalho_new.php';

$plugins = array(
    "shadowbox",
    "datepicker",
    "mask",
    "dataTable"
);

include("plugin_loader.php"); ?>
<script>
    $(function(){
        Shadowbox.init();
        $.datepickerLoad(Array("data_final", "data_inicial"));
        $("#data_inicial").datepicker(
            "option", {
                minDate: "01/01/2003",
                maxDate: "31/12/2016"
        });

        $('#data_final').datepicker(
            "option", {
                minDate: "01/01/2003",
                maxDate: "31/12/2016"
        });

        $('#btn_acao').click(function(){
            $.ajax( {
                type : "POST",
                url : location.href,
                beforeSend : function() {
                    $("#loading-block").css({    display:"block"   });
                    $("#loading").css({    display:"block"   });
                }
            });
        });

        $("span[rel=numero_serie]").click(function () {
            buscaporlupa($(this));
        });

        $("span[rel=produto_codigo]").click(function () {
            buscaporlupa($(this));

        });

        $("span[rel=produto_descricao]").click(function () {
            buscaporlupa($(this));
        });

        $("span[rel=cliente_codigo]").click(function () {
            buscaporlupa($(this));

        });

        $("span[rel=cliente_razao]").click(function () {
            buscaporlupa($(this));
        });

        function buscaporlupa(that) {
            var dataLupa    = $(that).parent("div").find("input[name=lupaData]");
            var tipo        = $(dataLupa).attr("tipo");
            var parametro   = $(dataLupa).attr("parametro");
            var valor       = $.trim($(that).prev("input").val());

            if(typeof num_aux == "undefined"){
                num_aux = 3;
            }

            if (valor.length >= num_aux) {
                Shadowbox.open({
                    content: tipo+"_legado_lupa_new.php?parametro="+parametro+"&valor="+valor,
                    player: "iframe",
                    width: 850,
                    height: 600
                });
            } else {
                alert("Informe toda ou parte da informação para pesquisar!");
            }
        };
    });

function retorna_produto (retorno) {
        $("#produto_codigo").val(retorno.produto_codigo);
        $("#produto_descricao").val(retorno.produto_descricao);
    }

    function retorna_cliente (retorno) {
        $("#cliente_codigo").val(retorno.cliente_codigo);
        $("#cliente_razao").val(retorno.cliente_razao);
    }
</script>
<div class="container">
    <div class="alert">
        <h4>
            O período disponível para consulta é de 01/01/2003 até 31/12/2016<br />
            Número da OS ou as datas inicial e final são necessárias para a busca.
        </h4>
    </div>
    <? if (count($msg_erro["msg"]) > 0) { ?>
        <div class="alert alert-error">
            <h4><?= implode("<br />", $msg_erro["msg"]); ?></h4>
        </div>
    <? } ?>
    <div class="container">
        <strong class="obrigatorio pull-right"> * Campos obrigatórios</strong>
    </div>
    <form name='frm_relatorio' method='POST' action='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
        <div class='titulo_tabela'>Parâmetros de Pesquisa</div>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span4">
                <div class="control-group <?= (in_array("os_tecvoz", $msg_erro["campos"])) ? "error" : ""; ?>">
                    <label class="control-label" for="os_tecvoz">Número da OS</label>
                    <div class="controls controls-row">
                        <div class="span8">
                            <h5 class="asteristico">*</h5>
                            <input type="text" id="os_tecvoz" name="os_tecvoz" value="<?= $os_tecvoz; ?>" class="span12 ">
                        </div>
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class="control-group ">
                    <label class="control-label" for="status">Status</label>
                    <div class="controls controls-row">
                        <div class="span5">
                            <? $sql = "SELECT DISTINCT status FROM tbl_os_tecvoz ORDER BY status ASC;";
                            $res = pg_query($con, $sql);
                            $allStatus = pg_fetch_all($res); ?>
			    <select id="status" name="status">
				<option value=""></option>
                                <? foreach ($allStatus as $st) {
                                    $selected = ($st['status'] == "$status") ? "selected" : ""; ?>
                                    <option value="<?= $st['status']; ?>" <?= $selected; ?>><?= $st['status']; ?></option>
                                <? } ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2"></div>
        </div>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span4">
                <div class="control-group <?= (in_array("data", $msg_erro["campos"])) ? "error" : ""; ?>">
                    <label class="control-label" for="data_inicial">Data Inicial</label>
                    <div class="controls controls-row">
                        <div class="span5">
                            <h5 class="asteristico">*</h5>
                            <input type="text" id="data_inicial" name="data_inicial" class="span12" maxlength="10" value="<?= $data_inicial; ?>" autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class="control-group <?= (in_array("data", $msg_erro["campos"])) ? "error" : ""; ?>">
                    <label class="control-label" for="data_final">Data Final</label>
                    <div class="controls controls-row">
                        <div class="span5">
                            <h5 class="asteristico">*</h5>
                            <input type="text" id="data_final" value="<?= $data_final; ?>" name="data_final" class="span12" maxlength="10" autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2"></div>
        </div>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span4">
                <div class="control-group ">
                    <label class="control-label" for="produto_codigo">Referência Produto</label>
                    <div class="controls controls-row">
                        <div class="span6 input-append">
                            <input type="text" value="<?= $produto_codigo; ?>" id="produto_codigo" name="produto_codigo" class="span12">
                            <span class='add-on' rel='produto_codigo'><i class='icon-search'></i></span>
                             <input type="hidden" name="lupaData" tipo="produto" parametro="referencia" />                            
                        </div>
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class="control-group ">
                    <label class="control-label" for="produto_descricao">Descrição Produto</label>
                    <div class="controls controls-row">
                        <div class="span12 input-append">
                            <input type="text" value="<?= $produto_descricao; ?>" id="produto_descricao" name="produto_descricao" class="span12">
                            <span class='add-on' rel='produto_descricao'><i class='icon-search'></i></span>
                            <input type="hidden" name="lupaData" tipo="produto" parametro="descricao" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2"></div>
        </div>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span4">
                <div class="control-group ">
                    <label class="control-label" for="cliente_codigo">Código Cliente</label>
                    <div class="controls controls-row">
                        <div class="span10  input-append">
                            <input type="text" value="<?= $cliente_codigo; ?>" id="cliente_codigo" name="cliente_codigo" class="span12">
                            <span class='add-on' rel="cliente_codigo"><i class='icon-search'></i></span>
                            <input type="hidden" name="lupaData" tipo="cliente" parametro="codigo" />

                        </div>
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class="control-group ">
                    <label class="control-label" for="cliente_razao">Razão Social</label>
                    <div class="controls controls-row">
                        <div class="span10  input-append">
                            <input type="text" value="<?= $cliente_razao; ?>" id="cliente_razao" name="cliente_razao" class="span12">
                            <span class='add-on' rel="cliente_razao"><i class='icon-search'></i></span>
                            <input type="hidden" name="lupaData" tipo="cliente" parametro="razao" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2"></div>
        </div>         
        <p>
            <br/>
            <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
        </p>
        <br/>  
    </form>
</div>
</div>
<? if (isset($resSubmit)) {
    $countRowsSubmit = pg_num_rows($resSubmit);
    if ($countRowsSubmit > 0) {
	$data     = date("d-m-Y-H:i");
        $fileName = "csv_ordens_antigas-{$data}.csv";
        $file     = fopen("/tmp/{$fileName}", "w");

        header('Content-Type: application/csv; charset=iso-8859-1');
        header('Content-Disposition: attachment; filename="/tmp/{$fileName}"');

        echo "<br />";
        $count = $countRowsSubmit; ?>
        <table align="center" id="resultado_os" class='table table-striped table-bordered table-hover table-large' >
            <thead>
                <tr class='titulo_coluna'>
                    <th>OS</th>
                    <th>Cliente</th>
                    <th>Número de Série</th>
                    <th>Produto</th>
                    <th>Qtde</th>
                    <th>Unidade</th>
                    <th>Situação</th>
                    <th>Garantia</th>
                    <th>Doc Auxiliar</th>
                    <th>Data Abertura</th>
                    <th>Data Fechamento</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
		<? $cabecalho = array(
		    "OS",
		    "Cliente",
		    "Número de Série",
		    "Produto",
		    "Qtde",
		    "Unidade",
		    "Situação",
		    "Garantia",
		    "Doc Auxiliar",
		    "Data Abertura",
		    "Data Fechamento",
		    "Status"
		);

		for ($i = 0; $i < $countRowsSubmit; $i++) {

                    // Tabela Histórico OS Tecvoz
                    // os_tecvoz text,
                    // cliente_codigo text,
                    // cliente_razao text,
                    // produto_codigo text,
                    // produto_descricao text,
                    // qtde text,
                    // unidade text,
                    // situacao text,
                    // garantia text,
                    // doc_auxiliar text,
                    // data_abertura text,
                    // data_fechamento text,
                    // numero_serie text,
                    // status text

                    $x_os_tecvoz            = pg_fetch_result($resSubmit, $i, os_tecvoz);
                    $x_cliente_codigo       = pg_fetch_result($resSubmit, $i, cliente_codigo);
                    $x_cliente_razao        = pg_fetch_result($resSubmit, $i, cliente_razao);
                    $x_produto_codigo       = pg_fetch_result($resSubmit, $i, produto_codigo);
                    $x_produto_descricao    = pg_fetch_result($resSubmit, $i, produto_descricao);
                    $x_qtde                 = pg_fetch_result($resSubmit, $i, qtde);
                    $x_unidade              = pg_fetch_result($resSubmit, $i, unidade);
                    $x_situacao             = pg_fetch_result($resSubmit, $i, situacao);
                    $x_garantia             = pg_fetch_result($resSubmit, $i, garantia);
                    $x_doc_auxiliar         = pg_fetch_result($resSubmit, $i, doc_auxiliar);
                    $x_data_abertura        = pg_fetch_result($resSubmit, $i, data_abertura);
                    $x_data_fechamento      = pg_fetch_result($resSubmit, $i, data_fechamento);
                    $x_numero_serie         = pg_fetch_result($resSubmit, $i, numero_serie);
                    $x_status               = pg_fetch_result($resSubmit, $i, status);

                    if (!empty($x_produto_codigo) || !empty($x_produto_descricao)) {
                        $produto = $x_produto_codigo . ' - ' . $x_produto_descricao;
                    } else {
                        $produto = 'OS sem produto';
                    }

                    if (!empty($x_cliente_razao) || !empty($x_cliente_codigo)) {
                        $cliente = $x_cliente_codigo.' - '.$x_cliente_razao;
                    } else {
                        $cliente = '';
                    }

                    if ((empty($x_data_abertura) || $x_data_abertura == 'NULL')) {
                        $x_data_abertura = '';
                    } else {
                        $x_data_abertura = explode(" ", $x_data_abertura);
                        list($y,$m,$d) = explode("-", $x_data_abertura[0]);
                        $x_data_abertura = "$d/$m/$y";
                    }

                    if ((empty($x_data_fechamento) || $x_data_fechamento == 'NULL')) {
                        $x_data_fechamento = '';
                    } else {
                        $x_data_fechamento = explode(" ", $x_data_fechamento);
                        list($y,$m,$d) = explode("-", $x_data_fechamento[0]);
                        $x_data_fechamento = "$d/$m/$y";
		    } 

		    $dados = array(
			$x_os_tecvoz,
			$cliente,
			$x_numero_serie,
			$produto,
			$x_qtde,
			$x_unidade,
			$x_situacao,
			$x_garantia,
			$x_doc_auxiliar,
			$x_data_abertura,
			$x_data_fechamento,
			$x_status
		    );

		    $linha .= implode(';',$dados)."\r\n";
            	    unset($dados) ;?>

                    <tr>
                        <td class="tac"><?= $x_os_tecvoz; ?></td>
                        <td><?= $cliente; ?></td>
                        <td><?= $x_numero_serie; ?></td>
                        <td><?= $produto; ?></td>
                        <td class="tac"><?= $x_qtde; ?></td>
                        <td class="tac"><?= $x_unidade; ?></td>
                        <td><?= $x_situacao; ?></td>
                        <td class="tac"><?= $x_garantia; ?></td>
                        <td><?= $x_doc_auxiliar; ?></td>
                        <td class="tac"><?= $x_data_abertura; ?></td>
                        <td class="tac"><?= $x_data_fechamento; ?></td>
                        <td class="tac"><?= $x_status; ?></td>
                    </tr>
		<? }
		
		$arquivo = implode(';',$cabecalho)."\r\n".$linha;
		    
		fwrite($file, $arquivo);
		fclose($file);

		if (file_exists("/tmp/{$fileName}")) {
		    system("mv /tmp/{$fileName} xls/{$fileName}");
		} ?>
	    </tbody>
	    <tfoot>
		<td colspan="12" class="tac">
		    <a class="btn btn-success" href="xls/<?=$fileName?>" role="button">Gerar Arquivo CSV</a>
		</td>
	    </tfoot>
        </table>
    <? } else { ?>
        <div class="container">
            <div class="alert">
                <h4>Nenhum resultado encontrado</h4>
            </div>
        </div>
    <? } ?>
    <script>
        $.dataTableLoad({ table: "#resultado_os" });
    </script>
<? } //fecha if btn_acao ?>

<br />

<? include("rodape.php"); ?>
