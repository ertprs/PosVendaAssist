<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

if (isset($_POST['excluir']) && $_POST['excluir']) {
    $tp_at = $_POST['tipo'];
    $sql = "SELECT parametros_adicionais FROM tbl_admin WHERE fabrica = $login_fabrica AND admin =".$_POST['admin'];
    $res = pg_query($con, $sql);

    $parametros_adicionais = json_decode(pg_fetch_result($res, 0, 'parametros_adicionais'), true);

    if ((isset($parametros_adicionais['email_tipo_atendimento']) || isset($parametros_adicionais['email_linha']))) {
        unset($parametros_adicionais['email_tipo_atendimento']);
        unset($parametros_adicionais['email_linha']);
    } else if (isset($parametros_adicionais['email_treinamento'])) {
        unset($parametros_adicionais['email_treinamento']);
    }

    $parametros_adicionais = json_encode($parametros_adicionais);

    $sql = "UPDATE tbl_admin SET parametros_adicionais = '$parametros_adicionais' WHERE admin =".$_POST['admin']." AND fabrica = $login_fabrica";
    $res = pg_query($con, $sql);
    if (pg_last_error()) {
        echo 'erro';
    } else {
        echo 'ok';
    }

    exit();
}

if (isset($_GET['admin']) && $_GET['admin'] != '') {
    $tp_at = $_GET['tipo'];
    $admins = [];
    $admins[] = $_GET['admin'];
    $sql = "SELECT parametros_adicionais FROM tbl_admin WHERE fabrica = $login_fabrica AND admin =".$_GET['admin'];
    $res = pg_query($con, $sql);
    $parametros_adicionais = "";
    $parametros_adicionais = json_decode(pg_fetch_result($res, 0, 'parametros_adicionais'), true);
    
    if ((isset($parametros_adicionais['email_tipo_atendimento']) || isset($parametros_adicionais['email_linha'])) && $tp_at == 'os_garantia') {
        $tipo_email = 'os_garantia';
        $tp = $parametros_adicionais['email_tipo_atendimento'];
        $ln = $parametros_adicionais["email_linha"];
    } else if (isset($parametros_adicionais['email_treinamento']) && $tp_at == 'treinamento') {
        $tipo_email = 'treinamento';
        $ln = $parametros_adicionais['email_treinamento'];
    }
}

if ($_POST["btn_acao"] == 1) {
    $msg_erro = [];
    $msg_success = [];

    if (!isset($_POST['admins'])) {
        $msg_erro['campos'][] = 'admins';
    } else {
        $admins = $_POST['admins'];
    }

    if (!isset($_POST['tipo_atendimento']) && !isset($_POST['linha'])) {
        $msg_erro['campos'][] = 'tipo_atendimento';
        $msg_erro['campos'][] = 'linha';
    } else {
        if (isset($_POST['tipo_atendimento'])) {
            $tp = $_POST['tipo_atendimento'];
        } else {
            $ln = $_POST['linha'];
        }
    }

    if (in_array($login_fabrica, [148])) {

        $tp = $_POST["tipo_atendimento"];
        $ln = $_POST["linha"]; 

    }

    $tipo_email = $_POST['tipo_email'];

    if (count($msg_erro["campos"]) == 0) {

        pg_query($con, "BEGIN");
    
        if ($tipo_email == 'os_garantia') {
            foreach ($admins as $key => $value) {
                $sql = "SELECT parametros_adicionais FROM tbl_admin WHERE fabrica = $login_fabrica AND admin = $value";
                $res = pg_query($con, $sql);
                if (pg_num_rows($res) > 0) {
                    $parametros_adicionais = json_decode(pg_fetch_result($res, 0, 'parametros_adicionais'), true);
                    $parametros_adicionais['email_tipo_atendimento'] = $tp;

                    if (in_array($login_fabrica, [148])) {
                        $parametros_adicionais['email_linha'] = $ln;
                    }

                    $campo_update = json_encode($parametros_adicionais);
                } else {

                    $campo_update = [];
                    $campo_update['email_tipo_atendimento'] = $tp;

                    if (in_array($login_fabrica, [148])) {
                        $campo_update['email_linha'] = $ln;
                    }

                    $campo_update = json_encode($campo_update);
                }

                $sql = "UPDATE tbl_admin SET parametros_adicionais = '$campo_update' WHERE admin = $value AND fabrica = $login_fabrica";
                $res = pg_query($con, $sql);
                if (pg_last_error()) {
                    $msg_erro['sql'][] = 'Erro UPDATE';
                }
            }

        } else {
            foreach ($admins as $key => $value) {
                $sql = "SELECT parametros_adicionais FROM tbl_admin WHERE fabrica = $login_fabrica AND admin = $value";
                $res = pg_query($con, $sql);
                if (pg_num_rows($res) > 0) {
                    $parametros_adicionais = json_decode(pg_fetch_result($res, 0, 'parametros_adicionais'), true);
                    $parametros_adicionais['email_treinamento'] = $ln;

                    $campo_update = json_encode($parametros_adicionais);
                } else {
                    $campo_update = [];
                    $campo_update['email_treinamento'] = $ln;
                    $campo_update = json_encode($campo_insert);
                }

                $sql = "UPDATE tbl_admin SET parametros_adicionais = '$campo_update' WHERE admin = $value AND fabrica = $login_fabrica";
                $res = pg_query($con, $sql);
                if (pg_last_error()) {
                    $msg_erro['sql'][] = 'Erro UPDATE';
                }
            }
        }

        if (count($msg_erro['sql']) == 0) {
            pg_query($con, "COMMIT");
            $msg_success['msg']  = 'ok';
            $tp = [];
            $admins = [];
            $ln = [];
        } else {
            pg_query($con, "ROLLBACK");
        }
    }
}

$layout_menu = "gerencia";
$title = "Manutenção de email dos Admins";

include "cabecalho_new.php";
$plugins = array( 	"multiselect",
					"dataTable"
);
include "plugin_loader.php";
?>

<script>
    $(function()
    {

    	$("#admins, #tipo_atendimento, #linha").multiselect({
        	selectedText: "selecionados # de #"
        });

    	$.dataTableLoad({
    		table: "#resultado_pesquisa"
     	});

        $("#os_garantia").click(function() {
            <?php
            if ($login_fabrica != 148) { ?>

                if ($("#os_garantia").prop('checked') === true) {
                    $(".ln").hide();
                    $(".tp").show();
                }

            <?php
            } else { ?>

                if ($("#os_garantia").prop('checked') === true) {
                    $(".ln").show();
                    $(".tp").show();
                } else {
                    $(".tp").hide();
                }

            <?php
            } ?>

        });

        $("#treinamento").click(function() {
            if ($("#treinamento").prop('checked') === true) {
                $(".tp").hide();
                $(".ln").show();
            }
        });

    
        $(".btn_alterar").click(function() {
            var adm = $(this).data("admin");
            var tp_at = $(this).data("tipo");
            window.location.href = 'manutencao_email_admin.php?admin='+adm+'&tipo='+tp_at;
        });

        $(".btn_excluir").click(function() {
            var adm = $(this).data("admin");
            var tp_at = $(this).data("tipo");
            $.ajax({
                url: 'manutencao_email_admin.php',
                type: 'POST',
                data: {excluir: true, admin: adm, tipo: tp_at},
            })
            .done(function(data) {
                if (data == 'ok') {
                    window.location.href = 'manutencao_email_admin.php?msg=ok';        
                } else {
                    window.location.href = 'manutencao_email_admin.php?msg=erro';
                }
            });
        });
    });

</script>

<?
if (count($msg_erro["campos"]) > 0) {
?>
    <div class="ms alert alert-error">
		<h4>Preencha os campos obrigatorios</h4>
    </div>
<?php
} else if (count($msg_erro['sql']) > 0) {
?>
    <div class="ms alert alert-error">
        <h4>Erro ao gravar</h4>
    </div>
<?php    
} else if (count($msg_success['msg']) > 0) {
?>
    <div class="ms alert alert-success">
        <h4>Gravado com sucesso</h4>
    </div>
<?php
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'ok') {
?>
        <div class="alert alert-success">
            <h4>Excluido com sucesso</h4>
        </div>
<?php
    } else {
?>
        <div class="alert alert-danger">
            <h4>Erro ao excluir</h4>
        </div>
<?php
    }
}
?>

<form name='frm_pesquisa' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
	<div class='titulo_tabela '>Parâmetros de Pesquisass</div>
	<br/>

	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
            <div class='control-group <?=(in_array("admins", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='admins'>Admins</label>
                <div class='controls controls-row'>
                    <?php
                        $sql_admin = "SELECT
                                            admin,
                                            nome_completo
                                      FROM tbl_admin
                                      WHERE fabrica = $login_fabrica
                                      AND ativo IS TRUE
                                      ORDER BY nome_completo ";                   
                        $res_admin = pg_query($con, $sql_admin);
                    ?>
                    <select name="admins[]" id="admins" multiple="multiple" class='span12'>
                        <?php
                        $selected_admin = array();
                        foreach (pg_fetch_all($res_admin) as $key) {
                            if(isset($admins)){
                                foreach ($admins as $id) {
                                    if ( isset($admins) && ($id == $key['admin']) ){
                                        $selected_admin[] = $id;
                                    }
                                }
                            }
                        ?>
                        <option value="<?php echo $key['admin']?>" <?php if( in_array($key['admin'], $selected_admin)) echo "SELECTED"; ?> >
                            <?php echo $key['nome_completo']?>
                        </option>
                      <?php } ?>
                    </select>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("tipo_email", $msg_erro["campos"])) ? "error" : ""?>'>
                <label>Tipo de E-mail</label>
                <br />
                <label class="radio">
                   <input type="radio" id="os_garantia" name="tipo_email" value="os_garantia" <? if($tipo_email == 'os_garantia' OR $tipo_email == ''){ ?> checked <?}?> > Ordem de Serviço
                </label>
                &nbsp;
                <label class="radio">
                   <input type="radio" id="treinamento" name="tipo_email" value="treinamento" <? if ($tipo_email == 'treinamento'){?> checked <?}?> > Treinamento
                </label>
            </div>
        </div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4 tp' style='display: none;'>
			<div class='control-group <?=(in_array("tipo_atendimento", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='tipo_atendimento'>Tipo Atendimento</label>
				<div class='controls controls-row'>
					<?php
    					$sql_tp = "SELECT
    										tipo_atendimento,
    										descricao
    								  FROM tbl_tipo_atendimento
    								  WHERE fabrica = $login_fabrica
                                      AND ativo IS TRUE
    								  ORDER BY descricao ";
					   $res_tp = pg_query($con, $sql_tp);
                    ?>
					<select name="tipo_atendimento[]" id="tipo_atendimento" multiple="multiple" class='span12'>
						<?php
						$selected_tp = array();
						foreach (pg_fetch_all($res_tp) as $key) {
							if(isset($tp)){
								foreach ($tp as $id) {
									if ( isset($tp) && ($id == $key['tipo_atendimento']) ){
										$selected_tp[] = $id;
									}
								}
							}
                        ?>
						<option value="<?php echo $key['tipo_atendimento']?>" <?php if( in_array($key['tipo_atendimento'], $selected_tp)) echo "SELECTED"; ?> >
							<?php echo $key['descricao']?>
						</option>
					  <?php } ?>
					</select>
				</div>
			</div>
		</div>
        <div class='span4 ln' style='display: none;'>
            <div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='linha'>Linha</label>
                <div class='controls controls-row'>
                    <?php
                        $sql_linha = "SELECT
                                            linha,
                                            nome
                                      FROM tbl_linha
                                      WHERE fabrica = $login_fabrica
                                      AND ativo IS TRUE
                                      ORDER BY nome ";
                    $res_linha = pg_query($con, $sql_linha);
                    ?>
                    <select name="linha[]" id="linha" multiple="multiple" class='span12'>
                        <?php
                        $selected_linha = array();
                        foreach (pg_fetch_all($res_linha) as $key) {
                            if(isset($ln)){
                                foreach ($ln as $id) {
                                    if ( isset($ln) && ($id == $key['linha']) ){
                                        $selected_linha[] = $id;
                                    }
                                }
                            }
                        ?>
                        <option value="<?php echo $key['linha']?>" <?php if( in_array($key['linha'], $selected_linha)) echo "SELECTED"; ?> >
                            <?php echo $key['nome']?>
                        </option>
                      <?php } ?>
                    </select>
                </div>
            </div>
        </div>
	</div>
  
	<input type='hidden' id='btn_click' name='btn_acao' value=''><br/>
	<div class="row-fluid">
        <div class="span4"></div>
        <div class="span4">
            <div class="control-group">
                <div class="controls controls-row tac">
                    <button type="button" class="btn" value="Gravar" alt="Gravar formulário" onclick="submitForm($(this).parents('form'),1);" > Gravar</button>
                </div>
            </div>
        </div>
        <div class="span4"></div>
    </div>
</form>
<?php
    
    $array_1 = [];
    $array_2 = [];
    $array_3 = [];

    $sql_1 = " SELECT  admin,
                        nome_completo, 
                        parametros_adicionais::jsonb->>'email_tipo_atendimento'  AS email_tipo_atendimento
                FROM tbl_admin 
                WHERE fabrica = $login_fabrica
                AND (parametros_adicionais::jsonb->>'email_tipo_atendimento' IS NOT NULL 
                OR parametros_adicionais::jsonb->>'email_linha' IS NOT NULL )
                ORDER BY 1";
                
    $res_1 = pg_query($con, $sql_1);
    if (pg_num_rows($res_1) > 0) {
        $array_1 = pg_fetch_all($res_1);
    }
        
    $sql_2 = " SELECT  admin,
                        nome_completo, 
                        parametros_adicionais::jsonb->>'email_treinamento' AS email_treinamento
                FROM tbl_admin 
                WHERE fabrica = $login_fabrica
                AND parametros_adicionais::jsonb->>'email_treinamento' notnull 
                ORDER BY 1";
    $res_2 = pg_query($con, $sql_2);
    if (pg_num_rows($res_2) > 0) {
        $array_2 = pg_fetch_all($res_2);
    }
   
    $array_3 = array_merge($array_1, $array_2);
    sort($array_3);

    $qtde = count($array_3);

    if($qtde == 0){
?>
     <div class="row-fluid">
        <div class="tac">
            <div class="span12 alert alert-warning">
                <h4>Não foram Encontrados Resultados para esta Pesquisa.<h4>
            </div>
        </div>
    </div>
</div>
<?php 
    } else {
?>
</div>        
	<table id="resultado_pesquisa" class='table table-striped table-bordered table-hover table-large' style="min-width: 850px;">
		<thead>
			<tr class='titulo_tabela'>
				<td style="text-align: center;">Admin</td>
				<td style="text-align: center;">Tipo/Email</td>
				<td style="text-align: center;">Tipo Atendimento</td>
                <td style="text-align: center;">Linhas</td>
                <td style="text-align: center;">Ações</td>
			</tr>
		</thead>
        <tbody>
<?php
                $admins = [];
                $tp = [];
                $ln = [];
        		foreach ($array_3 as $p => $val) {
        			$admins[] = $val['admin'];
                    $parametros_adicionais = []; 

                    if (isset($val['email_tipo_atendimento'])) {
                        $parametros_adicionais['email_tipo_atendimento'] = json_decode($val['email_tipo_atendimento'], true);
                    } else {
                        $parametros_adicionais['email_treinamento'] = json_decode($val['email_treinamento'], true);
                    }

                    if (isset($parametros_adicionais['email_tipo_atendimento'])) {
                        $tipo_email = 'os_garantia';
                        $tp = implode(",", $parametros_adicionais['email_tipo_atendimento']);
                        $sql_t = " SELECT
                                        descricao
                                    FROM tbl_tipo_atendimento
                                    WHERE fabrica = $login_fabrica
                                    AND tipo_atendimento IN ($tp)";
                        $res_t = pg_query($con, $sql_t);
                        $desc_tp = [];
                        $desc_t = "";
                        for ($t = 0; $t < pg_num_rows($res_t); $t++) {
                            $desc_tp[] = pg_fetch_result($res_t, $t, 'descricao'); 
                        }
                        $desc_t = implode("<br />", $desc_tp);

                    } else if (isset($parametros_adicionais['email_treinamento'])) {
                        $tipo_email = 'treinamento';
                        $ln = implode(",", $parametros_adicionais['email_treinamento']);
                        $sql_l = " SELECT
                                        nome
                                    FROM tbl_linha
                                    WHERE fabrica = $login_fabrica
                                    AND linha IN ($ln)";
                        $res_l = pg_query($con, $sql_l);
                        $desc_ln = [];
                        $desc_l = "";
                        for ($l = 0; $l< pg_num_rows($res_l); $l++) {
                            $desc_ln[] = pg_fetch_result($res_l, $l, 'nome'); 
                        }
                        $desc_l = implode("<br />", $desc_ln);
                    }

                    $sqlAdicionais = "SELECT parametros_adicionais
                                      FROM tbl_admin
                                      WHERE admin = {$val['admin']}";
                    $resAdicionais = pg_query($con, $sqlAdicionais);

                    $arrAdicionais = json_decode(pg_fetch_result($resAdicionais, 0, "parametros_adicionais"), true);

                    $listaLinhas = "";
                    if (count($arrAdicionais["email_linha"])) {

                        $sqlLinha = "SELECT nome
                                     FROM tbl_linha
                                     WHERE linha IN (".implode(",", $arrAdicionais["email_linha"]).")";
                        $resLinha = pg_query($con, $sqlLinha);

                        $arrLinhas = "";
                        while ($dados = pg_fetch_object($resLinha)) {

                            $arrLinhas[] = $dados->nome;

                        }

                        $listaLinhas = implode("<br />", $arrLinhas);

                    }

                    if ($i % 2 == 0) {
                        $cor = "#dddddd";
                    } else {
                        $cor = "";
                    }

                    if ($tipo_email != "os_garantia") {

                        $listaLinhas = $desc_l;

                        $desc_t = "";

                    }

        			?>
					<tr bgcolor='<?=$cor?>'>
						<td style="text-align: center;" nowrap><font size='1'><?=$val['nome_completo']?></font></td>
                        <?php if ($tipo_email == 'os_garantia') { ?>
                                <td style="text-align: center;" nowrap><font size='1'>Ordem de Serviço</font></td>
                        <?php } else { ?>
                                <td style="text-align: center;" nowrap><font size='1'>Treinamento</font></td>
                        <?php }?>
                            <td style="text-align: center;" nowrap><font size='1'><?= $tipo_email == "os_garantia" && empty($desc_t) ? "TODAS" : $desc_t ?></font></td>
                            <td style="text-align: center;" nowrap><font size='1'><?= empty($listaLinhas) ? "TODAS" : $listaLinhas ?></font></td>
							<td style="text-align: center;" nowrap>
                                <button data-tipo="<?=$tipo_email?>" data-admin="<?=$val['admin']?>" class="btn_alterar btn btn-success btn-small">Alterar</button>
                                <button data-tipo="<?=$tipo_email?>" data-admin="<?=$val['admin']?>" class="btn_excluir btn btn-danger btn-small">Excluir</button>
                            </td>
					</tr>
		<?php }	?>
	   	    <tbody>
        </table>
<?php 
    }
?>

    <script>
        $(document).ready(function() {
            <?php
            if ($login_fabrica != 148) { ?>

                if ($("#os_garantia").prop('checked') === true) {
                    $(".ln").hide();
                    $(".tp").show();
                } else {
                    $(".tp").hide();
                    $(".ln").show();
                }

            <?php
            } else { ?>

                if ($("#os_garantia").is(":checked")) {
                    $(".ln").show();
                } else {
                    $(".ln").hide();
                }

                $(".tp").show();

            <?php
            }
            ?>
        
        });
    </script>

<?php
include "rodape.php";
?>
