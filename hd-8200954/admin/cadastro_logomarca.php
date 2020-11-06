<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios='cadastros';
require_once 'autentica_admin.php';
include_once 'helper.php';

include_once 'funcoes.php';

$title = traduz("Cadastro de Logomarcas");
$layout_menu = "cadastro";
include 'cabecalho_new.php';

$plugins = array('dataTable');

include 'plugin_loader.php';
?>
<script>
    function alterar(logomarca, codigo, nome, ativo){
        document.getElementById('codigo_logomarca').value = codigo;
        document.getElementById('nome').value = nome;
        document.getElementById('status').value = ativo;
        document.getElementById('logomarca').value = logomarca
        $('.titulo_tabela').html('Atualizar Logomarca')
        document.getElementById('btn_submit').value = 'Atualizar'
    }

    var tipo_tabela = '<?php echo $tipo_tabela ?>';
	if(tipo_tabela == "full"){
		$.dataTableLoad({
			table: "#tbl_logomarca"
		});
	}else{
		$.dataTableLoad({
			table: "#tbl_logomarca",
			type: "custom",
			config: [ "pesquisa" ]
		});
	}
</script>


<?php



if($_POST['gravar'] == 'Atualizar'){
    $logomarca = $_POST['logomarca'];
    $codigo_logomarca = $_POST['codigo_logomarca'];
    $descricao_logomarca = $_POST['nome'];
    $ativo = $_POST['status'];
    
    $sql_update = "UPDATE tbl_logomarca set codigo = '$codigo_logomarca', descricao = '$descricao_logomarca', ativo = '$ativo' WHERE logomarca = $logomarca";
    $res_update = pg_query($con, $sql_update);

    if($res_update == true){?>
        <div class="alert alert-sucess">
            <h4>Alterado com sucesso</h4>
        </div>
    <?php $codigo_logomarca = '';
        $nome = '';
    }else{ ?>
            <div class="alert alert-error">
            <h4>Registro já cadastrado</h4>
            <br>
        </div>
    <?php }
}

if($_POST['gravar'] == 'Gravar'){

    $codigo_logomarca = $_POST['codigo_logomarca'];
    $descricao_logomarca = $_POST['nome'];
    $ativo = $_POST['status'];

    $data = new DateTime();
    $data_input = $data->format('Y-m-d H:i:s.u');
    
    $insert = "INSERT INTO tbl_logomarca(fabrica,codigo,descricao, ativo, data_input) VALUES($login_fabrica,'$codigo_logomarca','$descricao_logomarca', '$ativo', '$data_input')";
    
    $res_insert = pg_query($con, $insert);

    if($res_insert == true){?>
        <div class="alert alert-sucess">
            <h4>Gravado com sucesso</h4>
        </div>
    <?php $codigo_logomarca = '';
        $nome = '';
        $_POST['linha'] = '';
        $_POST['tabela'] ='';
    }else{ ?>
         <div class="alert alert-error">
            <h4>Registro já cadastrado</h4>
            <br>
        </div>
    <?php }
}

?>

<div id="principal">
    <form name="frm_posto" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
        <div class='titulo_tabela '>Consulta Tabela de Preço</div>
        <br/>
        <div class="row-fluid">
            <div class='span2'></div>
            <div class="span4">
                <div class='control-group'>
                    <label class='control-label' for='codigo_logomarca'>Código Logomarca</label>
                    <div class='controls controls-row'>
                        <div class='span10 input-append'>
                            <input type="text" id="codigo_logomarca" name="codigo_logomarca" class='span12' maxlength="20" value="<? echo $codigo_logomarca ?>" style="width: 245px;">
                        </div>
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class='control-group'>
                    <label class='control-label' for='nome'>Descrição Logomarca</label>
                    <div class='controls controls-row'>
                        <div class='span10 input-append'>
                            <input type="text" id="nome" name="nome" class='span12' maxlength="20" value="<?php echo $nome ?>" style="width: 245px;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class='span2'></div>
            <div class="span4">
                <div class='control-group'>
                    <label class='control-label' for='nome'>Status</label>
                    <div class='controls controls-row'>
                        <div class='span10 input-append'>
                            <select name="status" id="status" class="multiple">
                                <option value="t">Ativo</option>
                                <option value="f">Inativo</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <br>
        <br />
        <p><br/>
            <input type="hidden" id="logomarca" name="logomarca">
            <input type="submit" class="btn" id="btn_submit" name="gravar" value="Gravar" />
        </p><br/>
    </form>
</div>

<?php
    $sql_consulta .= "SELECT logomarca, codigo, descricao, ativo
                    FROM tbl_logomarca
                    WHERE fabrica = {$login_fabrica}";

    $res_consulta = pg_query($con, $sql_consulta);
    $tot0 = pg_num_rows($res_consulta);

    if($tot0 > 50){
        $tipo_tabela = 'full';
    }else{
        $tipo_tabela = 'basic';
    }
?>
<div class='container'>
	<div class='alert' <?=$display?>>
		<h4>Para efetuar alterações clique na descrição da Logomarca</h4>
	</div>
</div>

<div class='container'>
	<table id='tbl_logomarca' class='table table-striped table-bordered table-hover table-fixed' >
		<thead>
        <tr class='titulo_coluna'>
            <td>Código</td>
            <td>Descrição</td>
            <td>Status</td>
        </tr>
    </thead>
    <tbody>
    <?php
        if(pg_num_rows($res_consulta) > 0){
            for($c = 0; $c < pg_num_rows($res_consulta); $c++){
                $logomarca        = pg_fetch_result($res_consulta, $c, 'logomarca');
                $codigo_logomarca = pg_fetch_result($res_consulta, $c, 'codigo'); 
                $nome_posto       = pg_fetch_result($res_consulta, $c, 'descricao');
                $ativo_result     = pg_fetch_result($res_consulta, $c, 'ativo');

                if($ativo_result=='t') $ativo = "<img title='Ativo' src='imagens/status_verde.png'>";
			        else        $ativo = "<img title='Inativo' src='imagens/status_vermelho.png'>";
                ?>
                
            <tr>
                <td><?= $codigo_logomarca ?></td>
                <td>
                    <a href="javascript: alterar('<?=$logomarca?>','<?=$codigo_logomarca?>','<?=$nome_posto?>','<?=$ativo_result?>')">
                        <?= $nome_posto ?>
                    </a>
                </td>
                <td><?= $ativo ?></td>
            </tr>
            <?php }?>
        <?php } ?>
    </tbody>
</table>