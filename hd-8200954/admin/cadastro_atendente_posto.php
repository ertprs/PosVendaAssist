<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios = "cadastros";
include 'autentica_admin.php';
include_once "../helpdesk.inc.php";

$layout_menu = "cadastro";
$title = "ATENDENTE POR POSTO";

if ($_POST['acao'] == 'refresh') {
    switch ($_POST['objeto']) {
    case 'cidade':
        $cidades = carregaCidades($_POST['estado']);
        echo json_encode($cidades);
        break;
    case 'posto':
        $postos = carregaPostos($_POST['tipo'],$_POST['carga']);
        echo json_encode($postos);
        break;
    }
    exit;
}

if (filter_input(INPUT_POST,'ajax_delete',FILTER_VALIDATE_BOOLEAN)) {
    $id = $_POST['id'];

    if ($login_fabrica == 30) {
        $id_atendente = $_POST['id_atendente'];
    }

    if (strlen($id) == 0) {
        $msg["erro"]   = true;   
        $msg["msn"]    = "Erro ao remover registro.";
        $msg["classe"] = "alert-danger";
    }

    if (strlen($msg) == 0) {
        pg_query($con, 'BEGIN TRANSACTION');

        if ($login_fabrica == 30) {
            $sql_tem_sap = " SELECT posto FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND posto = {$id} AND admin_sap = {$id_atendente}";
            $res_tem_sap = pg_query($con, $sql_tem_sap);
            if (pg_num_rows($res_tem_sap) > 0) {
                $sqlDelete = "UPDATE tbl_posto_fabrica SET admin_sap = NULL WHERE fabrica={$login_fabrica} AND posto={$id}";
                $resDelete = pg_query($con, $sqlDelete);
            } else {
                $sql_delete_posto = "DELETE FROM tbl_admin_atendente_estado WHERE posto_filial = {$id} AND admin = {$id_atendente} AND fabrica = {$login_fabrica}";
                $res_delete_posto = pg_query($con, $sql_delete_posto);
            }
        } else {
            $sqlDelete = "UPDATE tbl_posto_fabrica SET admin_sap = NULL WHERE fabrica={$login_fabrica} AND posto={$id}";
            $resDelete = pg_query($con, $sqlDelete);
        }

        if (pg_last_error($con)) {
            $res = pg_query($con,"ROLLBACK TRANSACTION");
            $msg["erro"]   = true;
            $msg["msn"]    = pg_last_error($con);
            $msg["classe"] = "alert-danger";
            exit(json_encode($msg));
        }

        $res = pg_query($con,"COMMIT TRANSACTION");
        $msg["erro"]   = false;
        $msg["msn"]    = "Removido com sucesso.";
        $msg["classe"] = "alert-success";
    }
    exit(json_encode($msg));
}

if (strlen($_POST['btn_acao']) > 0) {
    $atendente       = $_POST['atendente'];
    $estado          = $_POST['estado'];
    $cidade          = $_POST['cidade'];
    $codigo_posto    = $_POST['codigo_posto'];
    $descricao_posto = $_POST['descricao_posto'];
    $posto_select    = $_POST['posto_select'];

    $hd_classificacao = "";
    if ($login_fabrica == 30 && isset($_POST['hd_classificacao'])) {
        $hd_classificacao = $_POST['hd_classificacao'];
    }


    if (strlen($atendente) == 0) {
        $msg["erro"][]   = "Escolha um Atendente";
        $msg["campo"][] = "atendente";
    }

    if (strlen($estado) == 0 && empty($codigo_posto)) {
        $msg["erro"][]   = "Escolha um Estado";
        $msg["campo"][] = "estado";
    }

    $condEstado = "";
    $condEstadoCidade = "";
    $joinEstadoCidade = "";

    if (count($msg["erro"]) == 0) {

        $postos = array();
        if (strlen($codigo_posto) == 0 && count($posto_select) == 0) {
            if (strlen($estado) > 0 && empty($cidade)) {
                $condEstado = " AND b.estado = UPPER('{$estado}')";
            } elseif (strlen($estado) > 0 && !empty($cidade)) {
                if (is_array($cidade)) {

                    $condEstadoCidade = " AND c.estado = UPPER('{$estado}') AND c.cod_ibge in (".implode(",", $cidade).")";
                } else {
                    $condEstadoCidade = " AND c.estado = UPPER('{$estado}') AND c.cod_ibge={$cidade}";
                }
                
                $joinEstadoCidade = " JOIN tbl_ibge c ON UPPER(fn_retira_especiais(c.cidade)) = UPPER(fn_retira_especiais(b.cidade))";
            }

            $sql = "SELECT a.posto, a.codigo_posto || ' -  ' || b.nome AS nome
                  FROM tbl_posto_fabrica a
                  JOIN tbl_posto b ON b.posto = a.posto
                  $joinEstadoCidade
                 WHERE a.fabrica = $login_fabrica
                   $condEstado
                   $condEstadoCidade
                   AND a.credenciamento = 'CREDENCIADO'
              ORDER BY b.estado, b.cidade, b.nome";

            $res    = pg_query($con, $sql);

	    } elseif (strlen($codigo_posto) == 0 && count($posto_select) > 0) {
			$sql = "SELECT a.posto, a.codigo_posto || ' -  ' || b.nome AS nome
				FROM tbl_posto_fabrica a
				JOIN tbl_posto b ON b.posto = a.posto
				WHERE a.fabrica = $login_fabrica
				AND a.credenciamento = 'CREDENCIADO'
				and b.posto IN(".implode(',',$posto_select).")";
			$res = pg_query($con,$sql);
        } else {
            $sql = "SELECT a.posto, a.codigo_posto || ' -  ' || b.nome AS nome
                  FROM tbl_posto_fabrica a
                  JOIN tbl_posto b ON b.posto = a.posto
                 WHERE a.fabrica = $login_fabrica
                   AND a.credenciamento = 'CREDENCIADO'
                   AND a.codigo_posto = '$codigo_posto'";
            $res    = pg_query($con, $sql);
	}

	for($i = 0; $i < pg_num_rows($res); $i++){
		$postos[$i]['posto'] = pg_fetch_result($res, $i, "posto");
		$postos[$i]['nome']  = pg_fetch_result($res, $i, "nome");
	}

        pg_query($con, 'BEGIN TRANSACTION');
        if (!empty($postos)) {

    		foreach ($postos as $kPosto => $vPosto) {
                if (count($msg["erro"]) == 0) {
                    if (!existeAdminPosto($vPosto['posto'])) {
                        if ($login_fabrica != 30 || ($login_fabrica == 30 && empty($hd_classificacao))) {
                            $sqlUpAtend = "UPDATE tbl_posto_fabrica SET admin_sap=".$atendente." WHERE fabrica={$login_fabrica} AND posto={$vPosto['posto']}";
                            $resUpAtend = pg_query($con, $sqlUpAtend);
                             if (pg_last_error($con)) {
                                $msg["erro"][] = pg_last_error($con);
                            }
                        } else {
                            $sql_possui_classificacao = "   SELECT hd_classificacao 
                                                            FROM tbl_admin_atendente_estado 
                                                            WHERE fabrica = {$login_fabrica }
                                                            AND hd_classificacao = {$hd_classificacao}
                                                            AND admin = {$atendente}
                                                            AND posto_filial = {$vPosto['posto']}";
                            $res_possui_classificacao = pg_query($con, $sql_possui_classificacao);                                                        
                            if (pg_num_rows($res_possui_classificacao) == 0) {
                                $sql_insert_classificacao = "   INSERT INTO tbl_admin_atendente_estado (
                                                                                                         admin, 
                                                                                                         fabrica, 
                                                                                                         posto_filial, 
                                                                                                         hd_classificacao, 
                                                                                                         data_input
                                                                                                        ) VALUES 
                                                                                                        (
                                                                                                         $atendente,
                                                                                                         $login_fabrica,
                                                                                                         {$vPosto['posto']},
                                                                                                         $hd_classificacao,
                                                                                                         now()
                                                                                                        ) ";
                                $res_insert_classificacao = pg_query($con, $sql_insert_classificacao);
                                if (pg_last_error($con)) {
                                    $msg["erro"][] = pg_last_error($con);
                                }
                            } else {
                                $sql_classificacao = "  SELECT descricao 
                                                        FROM tbl_hd_classificacao 
                                                        WHERE fabrica = $login_fabrica 
                                                        AND hd_classificacao = $hd_classificacao";
                                $res_classificacao = pg_query($con,$sql_classificacao);
                                $desc_classificacao = pg_fetch_result($res_classificacao, 0, 'descricao');
                                $msg["erro"][] ="Já existe atendente amarrado a classificação {$desc_classificacao}";        
                            }
                        }
                    } else {
                        $msg["erro"][] ="Já existe atendente amarrado o posto {$vPosto['nome']}";
                    }
                }
            }


            if (count($msg['erro']) == 0) {
                $res = pg_query($con,"COMMIT TRANSACTION");
                $msg["sucesso"]    = "Gravado com sucesso";
                $atendente         = "";
                $estado            = "";
                $cidade            = "";
                $codigo_posto      = "";
                $descricao_posto   = "";
                $hd_classificacao  = "";
                $_POST['btn_acao'] = "";
                $posto_select      = array();
            } else {
                $res = pg_query($con,"ROLLBACK TRANSACTION");
            }
        } else {
            $msg["erro"][] ="Nenhum posto encontrado.";
        }
    }
}

function existeAdminPosto($posto) {
    global $con, $login_fabrica;

    if (empty($posto)) {
        return false;
    }

    $sql = "SELECT tbl_posto_fabrica.admin_sap
              FROM tbl_posto_fabrica
             WHERE posto = {$posto}
               AND fabrica={$login_fabrica}
               AND admin_sap IS NOT NULL";
    $res    = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        return true;
    } else {
        return false;
    }

}

function carregaCidades($estado) {
    global $con;
    $sql = "SELECT cod_ibge, cidade
              FROM tbl_ibge
             WHERE UPPER(estado) = '{$estado}'
          ORDER BY cidade";
    $res = pg_query($con, $sql);
    $cidades = array();
    for ($i = 0; $i < pg_num_rows($res); $i++ ) {
        $cidades[] = array(
            "codigo" => pg_fetch_result($res, $i, "cod_ibge"),
            "nome"   => utf8_encode(pg_fetch_result($res, $i, "cidade"))
        );
    }
    return $cidades;
}

function carregaPostos($tipo,$carga) {
    global $login_fabrica, $con;
    // $xcidades = retira_acentos(utf8_decode(trim($cidade)));

    switch ($tipo) {
        case "estado":
            $whereCarga = " AND b.estado = '$carga'";
            break;
        case "cidade":
            if (!empty($carga) && strpos($carga, "|")) {
                $carga = explode("|", retira_acentos(utf8_decode($carga)));
                $xcidades = "UPPER(fn_retira_especiais('".implode("')),UPPER(fn_retira_especiais('",$carga)."'))";
            } else {
                $xcidades = "UPPER(fn_retira_especiais('".retira_acentos(utf8_decode($carga))."'))";
            }

            $whereCarga = " AND b.estado IS NOT NULL
            AND UPPER(fn_retira_especiais(b.cidade)) in ( {$xcidades} )";
            break;
    }

    $sql = "SELECT
                b.estado,
                b.cidade,
                b.nome,
                a.posto AS codigo,
                a.codigo_posto
              FROM tbl_posto_fabrica a
              JOIN tbl_posto b ON b.posto = a.posto
             WHERE a.fabrica = $login_fabrica
               $whereCarga
               AND a.credenciamento = 'CREDENCIADO'
               AND a.admin_sap IS NULL
          ORDER BY b.estado, b.cidade, b.nome";
    $res    = pg_query($con, $sql);

    $postos = array();
    for ($i = 0; $i < pg_num_rows($res); $i++) {
        $postos[] = array(
                            'posto' => pg_fetch_result($res, $i, "codigo"),
                            'codigo_posto' => pg_fetch_result($res, $i, "codigo_posto"),
                            'nome'  => utf8_encode(pg_fetch_result($res, $i, "nome")),
                        );
    }
    return $postos;
}

function getEstadoList() {
    return array(
      "AC" => "Acre",         "AL" => "Alagoas",    "AM" => "Amazonas",           "AP" => "Amapá",
      "BA" => "Bahia",        "CE" => "Ceará",      "DF" => "Distrito Federal",   "ES" => "Espírito Santo",
      "GO" => "Goiás",        "MA" => "Maranhão",   "MG" => "Minas Gerais",       "MS" => "Mato Grosso do Sul",
      "MT" => "Mato Grosso",  "PA" => "Pará",       "PB" => "Paraíba",            "PE" => "Pernambuco",
      "PI" => "Piauí",        "PR" => "Paraná",     "RJ" => "Rio de Janeiro",     "RN" => "Rio Grande do Norte",
      "RO" => "Rondônia",     "RR" => "Roraima",    "RS" => "Rio Grande do Sul",  "SC" => "Santa Catarina",
      "SE" => "Sergipe",      "SP" => "São Paulo",  "TO" => "Tocantins"
    );
}

include "cabecalho_new.php";

$plugins = array(
                "multiselect",
                "autocomplete",
                "select2",
                "shadowbox",
                "mask",
                "dataTable"
                );
include ("plugin_loader.php");

?>
<style type="text/css">
    .btn_delete, .btn_edit {
        cursor: pointer;
        width: 14px;
    }
    .borda-vermelha span[class="select2-selection select2-selection--single"],
    .borda-vermelha button[class="ui-multiselect ui-widget ui-state-default ui-corner-all"] {
        border-color: #b94a48;
    }
</style>
<script type="text/javascript">
$( function() {
    $.autocompleteLoad(Array("posto"));
    Shadowbox.init();
    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });
    
    $("#atendente").change(function() {
        removerBordaVermelha();
    });

    $(".select2").select2();
    $("#estado").change(function() {
        $("#select2-cidade-container").html('');
        getCidades($(this).val());
        getPostos("estado",$("#estado :selected").val());
        removerBordaVermelha();
    });

    $("#cidade").change( function() {
        var cidades = [];
        $("#cidade :selected").map(function(index, elem) {
            cidades.push($(elem).text());
        });
        getPostos("cidade",cidades);
        removerBordaVermelha();
    });    

    $('#posto_select').multiselect({
        selectedText: "selecionados # de #"
    });   

    <?php
    if (in_array($login_fabrica, [35])) { ?>
        $('#cidade').multiselect({
            selectedText: "selecionados # de #"
        });
    <?php
    } ?>

    $(".btn_delete").click(function() {
        var obj = $(this);
        var id = $(this).data('id');
        var id_atendente = $(this).data('atendente');
//         alert($(this).parent("tr").val());
        if (id != '' || id != undefined) {
            $.ajax({
                url: "cadastro_atendente_posto.php",
                type: "POST",
                dataType:"JSON",
                data: { ajax_delete: true, id: id, id_atendente: id_atendente},
                beforeSend: function() { loading("show"); },
            }).done(function(data) {

                $('.alert-error').hide();
                $('.alert-success').hide();
                removerBordaVermelha();

                if (data.erro) {
                    $("#class_alert_mensagem").show('slow');
                    $("#class_alert_mensagem").addClass(data.classe);
                    $("#txt_mensagem").html(data.msn);
                    window.location.href="cadastro_atendente_posto.php";
                } else {
                    $("#class_alert_mensagem").show('slow');
                    $("#class_alert_mensagem").addClass(data.classe);
                    $("#txt_mensagem").html(data.msn);
                    let linha = $(obj).parent().parent();
                    if(linha.parent().children().length == 1)
                        linha.parent().parent().parent().parent().remove();
                    else
                        linha.remove();
                    //window.location.href="cadastro_atendente_posto.php";
                }
                setTimeout(() => $("#class_alert_mensagem").hide('slow'), 3000);

                loading("hide");
            });

        }
    });

    $('.btn_edit').click(function(){
        var atendente_id = $(this).data('id');
        Shadowbox.open({
            content: "atendente_edicao_modal.php?atendente_id="+atendente_id,
            player: "iframe",
            width: 850,
            height: 600
        });
    });

});

function redirect(){
    window.location.href="<?=$_SERVER['PHP_SELF']?>";
}

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
}
function getCidades(codigoEstado) {
    $.ajax({
        url: "cadastro_atendente_posto.php",
        type: "POST",
        data: { objeto: "cidade", acao: "refresh", estado: codigoEstado },
        beforeSend: function() { loading("show"); },
    }).done(function(data) {
        refreshCidadeList(data);
    });
}
function getPostos(tipo,carga) {
    
    if (tipo == 'cidade') {
        carga = carga.join("|");
    }

    $.ajax({
        url: "cadastro_atendente_posto.php",
        type: "POST",
        dataType:"JSON",
        data: {
            objeto: "posto",
            acao: "refresh",
            tipo:tipo,
            carga: carga
        },
        beforeSend: function() { loading("show"); },
    }).done(function(data) {
        refreshPostosList(data);
    });
}
<?php
if (in_array($login_fabrica, [35])) { ?>
    function refreshCidadeList(data) {
        data    = $.parseJSON(data);
        objeto  = $('#cidade');
        objeto.empty();
        objeto.append('<option value="" selected="selected">Selecione ...</option>');
        for (i=0; i < data.length; i++) {
            objeto.append(
                $('<option></option>').val(data[i]['codigo']).html(data[i]['nome'])
            );
        }
        $('#cidade').multiselect();
        $('#cidade').multiselect("refresh");
        $('#cidade').multiselect("uncheckAll");
        loading("hide");
    }

<?php
} else { ?>
    function refreshCidadeList(data) {
        data    = $.parseJSON(data);
        objeto  = $('#cidade');
        objeto.empty();
        objeto.append('<option value="" selected="selected">Selecione ...</option>');
        for (i=0; i < data.length; i++) {
            objeto.append(
                $('<option></option>').val(data[i]['codigo']).html(data[i]['nome'])
            );
        }

        loading("hide");
    }
<?php
} ?>

function refreshPostosList(data) {
    objeto  = $('body #posto_select');
    objeto.empty();
    for (i=0; i < data.length; i++) {
        objeto.append(
            $('<option></option>').val(data[i].posto).html(data[i].codigo_posto+' - '+data[i].nome)
        );
    }
    $('#posto_select').multiselect();
    $('#posto_select').multiselect("refresh");
    $('#posto_select').multiselect("uncheckAll");
    loading("hide");
}

function removerBordaVermelha(){
    $(".borda-vermelha").removeClass('error');
    $(".borda-vermelha").removeClass('borda-vermelha');
};

</script>
<?php
    if (count($msg["erro"]) > 0) {
        foreach ($msg["erro"] as $error) {
            echo '<div class="alert alert-error"><h4>'.$error.'</h4></div>';
        }
    }
    if (count($msg["sucesso"]) > 0) {
        foreach ($msg["sucesso"] as $sucesso) {
            echo '<div class="alert alert-success"><h4>'.$sucesso.'</h4></div>';
        }
    }
    // msg pro ajax
    echo '<div class="alert" id="class_alert_mensagem" style="display:none"><h4 id="txt_mensagem"></h4></div>';
?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_atendente_posto' METHOD='POST' ACTION='<?=$PHP_SELF?>' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela'>Cadastro</div>
    <br />
    <div class='row-fluid'>
        <div class='span1'></div>
        <div class='span4'>
            <div class='control-group <?php echo (in_array("atendente", $msg["campo"])) ? "error borda-vermelha" : ""?>'>
                <label class='control-label' for='atendente'>Atendente</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <h5 class='asteristico'>*</h5>
                        <select id='atendente' name='atendente' class='span12 select2'>
                            <?php
                                $sqlAdmin = "SELECT DISTINCT admin,
                                            nome_completo
                                          FROM tbl_admin
                                         WHERE fabrica = {$login_fabrica}
                                           AND admin_sap IS TRUE
                                           AND ativo IS TRUE
                                      ORDER BY nome_completo";
                                $resAdmin = pg_query($con, $sqlAdmin);

                                if (pg_num_rows($resAdmin) > 0) {
                                    echo "<option value=''>Selecione ...</option>";
                                    for ($i = 0 ; $i < pg_num_rows($resAdmin) ; $i++) {
                                        $id   = pg_result ($resAdmin, $i, admin);
                                        $nome = pg_result ($resAdmin, $i, nome_completo);
                                        $selected = ($atendente == $id) ? "selected='selected'" : "";
                                        $retorno .= "<option value='$id' {$selected}>$nome</option>";
                                    }
                                    echo $retorno;
                                } else {
                                    echo "<option value=''>Selecione ...</option>";
                                }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'>
            <div class='control-group <?php echo (in_array("estado", $msg["campo"])) ? "error borda-vermelha" : ""?>'>
                <label class='control-label' for='estado'>Estado</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <!--<h5 class='asteristico'>*</h5>-->
                        <select name="estado" id="estado" class='span12 select2'>
                            <option value="">Selecione ...</option>
                            <?php
                                foreach( getEstadoList() as $codigoEstado => $nomeEstado ) {
                                    $selected = ($estado == $codigoEstado) ? "selected='selected'" : "";
                                    echo '<option value="' . $codigoEstado . '" '.$selected.'>' . $nomeEstado . '</option>';
                                }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?php echo (in_array("cidade", $msg["campo"])) ? "error borda-vermelha" : ""?>'>
                <label class='control-label' for='cidade'>Cidade</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <?php
                        if (in_array($login_fabrica, [35])) { ?>
                            <select name='cidade[]' id='cidade' multiple='multiple' class='span12'>
                        <?php
                        } else { ?>
                            <select name="cidade" id="cidade" class='span12 select2'>
                        <?php
                        }
                                echo '<option value="" selected="selected">Selecione ...</option>';
                                if (strlen($_POST['btn_acao']) > 0 && strlen($msg_success) == 0) {
                                    $sql = "SELECT cod_ibge, cidade
                                              FROM tbl_ibge
                                             WHERE UPPER(estado) = '{$estado}'
                                          ORDER BY cidade";
                                    $res = pg_query($con, $sql);
                                    $cidades = array();
                                    for ($i = 0; $i < pg_num_rows($res); $i++ ) {
                                        $codigo = pg_fetch_result($res, $i, "cod_ibge");
                                        $nome   = pg_fetch_result($res, $i, "cidade");
                                        $selected = ($cidade == $codigo) ? "selected='selected'" : "";
                                        echo '<option value="' . $codigo . '" '.$selected.'>' . $nome . '</option>';

                                    }
                                }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span1'></div>
    </div>
    <div class="row-fluid">
        <div class='span1'></div>
        <div class='span4'>
            <div class='control-group <?php echo (in_array("posto", $msg["campo"])) ? "error borda-vermelha" : ""?>'>
                <label class='control-label' for='posto_select'>Posto</label>
                 <div class='controls controls-row'>
                    <div class='span12'>
                        <select name='posto_select[]' id='posto_select' multiple='multiple' class='span12'>
                            <?php
                                if (strlen($_POST['btn_acao']) > 0) {

                                    $sql = "SELECT
                                                b.estado,
                                                b.cidade,
                                                b.nome,
                                                a.posto AS codigo,
                                                a.codigo_posto
                                              FROM tbl_posto_fabrica a
                                              JOIN tbl_posto b ON b.posto = a.posto
                                              LEFT JOIN tbl_ibge c ON UPPER(c.cidade) = UPPER(b.cidade)
                                             WHERE a.fabrica = $login_fabrica
                                               AND b.estado IS NOT NULL
                                               AND c.cod_ibge = {$cidade}
                                               AND a.credenciamento = 'CREDENCIADO'
                                          ORDER BY b.estado, b.cidade, b.nome";
                                    $res    = pg_query($con, $sql);
                                    $postos = array();
                                    for ($i = 0; $i < pg_num_rows($res); $i++) {
                                        $posto_id        = pg_fetch_result($res, $i, "codigo");
                                        $codigo_posto1 = pg_fetch_result($res, $i, "codigo_posto");
                                        $nome         = pg_fetch_result($res, $i, "nome");
                                        $selected     = (in_array($posto_id, $posto_select)) ? "selected='selected'" : "";
                                        echo '<option value="' . $posto_id . '" '.$selected.'>' . $codigo_posto1 . ' - ' . $nome . '</option>';
                                    }

                                }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'>
            <div class='control-group <?php echo (in_array("codigo_posto", $msg["campo"])) ? "error borda-vermelha" : ""?>'>
                <label class='control-label' for='codigo_posto'>Código Posto</label>
                <div class='controls controls-row'>
                    <div class='span9 input-append'>
                        <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?php echo (in_array("descricao_posto", $msg["campo"])) ? "error borda-vermelha" : ""?>'>
                <label class='control-label' for='descricao_posto'>Razão Social</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span1'></div>
    </div>
    <br />
    <?php if ($login_fabrica == 30) { ?>
            <div class="row-fluid">
                <div class='span1'></div>
                <div class='span4'>
                    <div class='control-group <?php echo (in_array("classificacao", $msg["campo"])) ? "error borda-vermelha" : ""?>'>
                        <label class='control-label' for='posto_select'>Classificação do Atendimento</label>
                         <div class='controls controls-row'>
                            <div class='span12'>
                                <select name='hd_classificacao' id='hd_classificacao' class='span12'>
                                    <option value="">Selecione ...</option>
                                    <?php
                                        $sqlClassificacao = "SELECT hd_classificacao, descricao FROM tbl_hd_classificacao WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY descricao";
                                        $resClassificacao = pg_query($con,$sqlClassificacao);
                                        for ($i = 0; $i < pg_num_rows($resClassificacao); $i++) {

                                            $hd_classificacao_aux = pg_fetch_result($resClassificacao,$i,'hd_classificacao');
                                            $classificacao    = pg_fetch_result($resClassificacao,$i,'descricao');

                                            echo " <option value='".$hd_classificacao_aux."' ".($hd_classificacao_aux == $hd_classificacao ? "selected='selected'" : '').">$classificacao</option>";
                                        }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <br />
    <?php } ?>
    <div class="row-fluid">
        <div class="span4"></div>
        <div class="span4">
            <div class="control-group">
                <div class="controls controls-row tac">
                    <button id='limpar' type="button" class="btn btn-warning" value="Limpar" onclick="javascript:window.location='<? echo $PHP_SELF ?>'; return false;" ALT="Limpar campos">Limpar</button>
                    <button class='btn' id="btn_acao" type="button" onclick="submitForm($(this).parents('form'));">Gravar</button>
                    <input type='hidden' id="btn_click" name='btn_acao' value='' />
                </div>
            </div>  
        </div>  
    </div>  
</form>

<table id="atendente_cadastrados" class='table table-striped table-bordered table-hover table-fixed' >
    <thead>
        <tr class="titulo_coluna" >
            <th>Atendente</th>
            <th>Posto</th>
        </tr>
    </thead>
    <tbody>
        <?php
             $sqlConsPrin = "SELECT  tbl_posto_fabrica.admin_sap,
                                    array_to_string(array_agg(tbl_posto.posto || '|' || tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome),';') AS nome_posto,
                                    tbl_admin.nome_completo AS nome_atendente
                            FROM    tbl_posto_fabrica
                            JOIN    tbl_posto ON tbl_posto.posto=tbl_posto_fabrica.posto
                            AND     tbl_posto_fabrica.fabrica={$login_fabrica}
                            JOIN    tbl_admin ON tbl_admin.admin = tbl_posto_fabrica.admin_sap
                            WHERE   tbl_posto_fabrica.fabrica = {$login_fabrica}
                            AND     tbl_posto_fabrica.admin_sap IS NOT NULL
                      GROUP BY      tbl_posto_fabrica.admin_sap,
                                    tbl_admin.nome_completo
                      ORDER BY      tbl_admin.nome_completo";

            if ($login_fabrica == 30) {         
                $sqlConsPrin = "SELECT  tbl_posto_fabrica.admin_sap,
                                        array_to_string(array_agg(tbl_posto.posto || '|' || tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome),';') AS nome_posto,
                                        tbl_admin.nome_completo AS nome_atendente
                                FROM    tbl_posto_fabrica
                                JOIN    tbl_posto ON tbl_posto.posto=tbl_posto_fabrica.posto
                                AND     tbl_posto_fabrica.fabrica={$login_fabrica}
                                JOIN    tbl_admin ON tbl_admin.admin = tbl_posto_fabrica.admin_sap
                                WHERE   tbl_posto_fabrica.fabrica = {$login_fabrica}
                                AND     tbl_posto_fabrica.admin_sap IS NOT NULL
                            GROUP BY    tbl_posto_fabrica.admin_sap,
                                        tbl_admin.nome_completo
                            UNION 
                                SELECT  tbl_admin_atendente_estado.admin, 
                                        array_to_string(array_agg(tbl_posto.posto || '|' || tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome),';') AS nome_posto, 
                                        tbl_admin.nome_completo as nome_atendente 
                                FROM    tbl_admin_atendente_estado 
                                JOIN    tbl_posto ON tbl_admin_atendente_estado.posto_filial = tbl_posto.posto 
                                JOIN    tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}  
                                JOIN    tbl_admin ON tbl_admin_atendente_estado.admin = tbl_admin.admin 
                                WHERE   tbl_admin_atendente_estado.fabrica = {$login_fabrica} 
                                AND     tbl_admin_atendente_estado.hd_classificacao NOTNULL 
                                AND     tbl_admin_atendente_estado.posto_filial NOTNULL 
                            GROUP BY    tbl_admin_atendente_estado.admin, 
                                        tbl_admin.nome_completo
                            ORDER BY    nome_atendente";
            }

//                             echo nl2br($sqlConsPrin);
            $resConsPrin = pg_query($con, $sqlConsPrin);

            if (pg_num_rows($resConsPrin) > 0) {
                for ($i=0; $i < pg_num_rows($resConsPrin); $i++) {

                    $atendente_id = pg_fetch_result($resConsPrin, $i, "admin_sap");
                    $nome_atendente = pg_fetch_result($resConsPrin, $i, "nome_atendente");
                    $postos = pg_fetch_result($resConsPrin, $i, "nome_posto");
                    $postos = explode(";",$postos);
                    ?>
                    <tr>
                        <td>
                            <table width="100%">
                                <tr>
                                    <td><?=$nome_atendente?></td>
                                    <td width='20' align='center'><i class='icon-pencil btn_edit' data-id="<?=$atendente_id?>" title="Transferir"></i></td>
                                </tr>
                            </table>
                        </td>
                        <td>
                            <table width="100%">
                            <?php
                                foreach ($postos as $kPostos => $vPostos) {

                                    $posto = explode("|",$vPostos);
                                    $delete = " <img class='btn_delete' data-id='".$posto[0]."' data-atendente='".$atendente_id."' src='imagens/btn_delete.png' />";
                                    echo "<tr><td width='20' align='center'>{$delete}</td><td>{$posto[1]}</td></tr>";
                                }
                            ?>
                            </table>
                        </td>
                    </tr>
            <?php
            }
        }?>
    </tbody>
</table>
<?php include "rodape.php";?>