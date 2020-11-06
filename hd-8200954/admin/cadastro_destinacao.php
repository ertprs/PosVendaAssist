<?php

    include 'dbconfig.php';
    include 'includes/dbconnect-inc.php';

    $admin_privilegios = "cadastros";

    include 'autentica_admin.php';
    include 'funcoes.php';

    if (strlen($_GET["segmento_atuacao"]) > 0) {
        $segmento_atuacao = trim($_GET["segmento_atuacao"]);
    }

    $msg_erro = "";

    if (isset($_POST["btnacao"])) {

        $nome             = $_POST["nome"];
        $segmento_atuacao = $_POST['segmento_atuacao'];
        $ativo            = ($_POST['ativo'] == "t") ? "'t'" : "'f'";

        if (strlen($nome) == 0) {
            $msg_erro = "Por favor, informe o nome da Destinação.";
        }

        if (strlen($msg_erro) == 0) {

            $res = pg_query ($con,"BEGIN TRANSACTION");
            
            if (strlen($segmento_atuacao) == 0) {

                $sql = "INSERT INTO tbl_segmento_atuacao (
                            descricao,
                            fabrica,
                            ativo
                        ) VALUES (
                            '{$nome}',
                            {$login_fabrica},
                            {$ativo}
                        );";
                
            }else{

                $sql = "UPDATE tbl_segmento_atuacao SET
                            descricao = '{$nome}',
                            ativo = {$ativo} 
                        WHERE  
                            tbl_segmento_atuacao.fabrica = {$login_fabrica}
                            AND tbl_segmento_atuacao.segmento_atuacao = {$segmento_atuacao};";
            }
        
            $res = pg_query ($con, $sql);

            if (strlen(pg_last_error()) == 0) {

                $res         = pg_query ($con, "COMMIT TRANSACTION");
                $msg_sucesso = 'Gravado com sucesso';

                header ("Location: $PHP_SELF?mensagem=$msg_sucesso");
                exit;

            }else{
                
                $nome             = $_POST["nome"];
                $segmento_atuacao = $_POST["segmento_atuacao"];
                $ativo            = $_POST["ativo"];
                
                $res = pg_query ($con,"ROLLBACK TRANSACTION");

                $msg_erro = "Erro ao gravar a Destinação";

            }

        }

    }

    if (strlen($segmento_atuacao) > 0) {

        $sql = "SELECT  
                    tbl_segmento_atuacao.segmento_atuacao,
                    tbl_segmento_atuacao.descricao,
                    tbl_segmento_atuacao.ativo  
                FROM tbl_segmento_atuacao
                WHERE 
                    tbl_segmento_atuacao.fabrica = {$login_fabrica}
                    AND tbl_segmento_atuacao.segmento_atuacao = {$segmento_atuacao}
                ";
        $res = pg_query ($con, $sql);

        if (pg_numrows($res) > 0) {

            $segmento_atuacao = pg_fetch_result($res, 0, "segmento_atuacao");
            $nome             = pg_fetch_result($res, 0, "descricao");
            $ativo            = pg_fetch_result($res, 0, "ativo");

        }

    }

    $layout_menu = "cadastro";
    $title       = "CADASTRO DE DESTINAÇÃO";

    include 'cabecalho_new.php';

    include("plugin_loader.php");

?>

<?php if(strlen($msg_erro) > 0) { ?>
<div class='alert alert-error'>
    <h4><?php echo $msg_erro; ?></h4>
</div>
<?php } ?>

<?php
if(isset($_GET['mensagem'])){
?>
<div class="alert alert-success">
    <h4><?php echo $_GET['mensagem']; ?></h4>
</div>
<?php
}
?>

<form method="POST" action="<?php echo $PHP_SELF; ?>" align='center' class='form-search form-inline tc_formulario'>

    <input type="hidden" name="segmento_atuacao" value="<?php echo $segmento_atuacao ?>">

    <div class='titulo_tabela '>Cadastro</div>
    <br/>
    <div class="container">
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span6'>
                <div class='control-group'>
                    <label class='control-label' for='nome'>Nome da Destinação</label>
                    <div class='controls controls-row'>
                        <div class='span11'>
                            <input type="text" id="nome" name="nome" class='span12' value="<?php echo $nome; ?>" >
                        </div>
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class="control-group">
                    <label>Status da Destinação</label>
                    <div class="controls controls-row">
                        <input type='checkbox' name='ativo' value='t' <?php if($ativo == 't') echo "checked"; ?> />
                        <label class="control-label" for="ativo">Ativo</label>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
    </div>

    <p>
        <br/>
        <input type='submit' name="btnacao" class="btn btn-default" value="Gravar"> &nbsp; &nbsp; 
        <input type='reset' class="btn btn-danger" value='Limpar'>
    </p>
    <br/>

</form>

<table class='table table-striped table-bordered table-hover table-large'>
    
    <thead>
        <tr class='titulo_tabela'>
            <th colspan='2'>Relação de Destinações Cadastradas</th>
        </tr>
        <tr class='titulo_coluna'>
            <th>Nome da Destinação</th>
            <th>Ativo</th>
        </tr>
    </thead>
    <tbody>
    <?php

        $sql = "SELECT 
                    tbl_segmento_atuacao.segmento_atuacao,
                    tbl_segmento_atuacao.descricao,
                    tbl_segmento_atuacao.ativo
                FROM tbl_segmento_atuacao
                WHERE 
                    tbl_segmento_atuacao.fabrica = {$login_fabrica}
                ORDER BY tbl_segmento_atuacao.descricao;";
        $res = pg_query ($con, $sql);

        if(pg_num_rows($res) == 0){

            echo "<tr> <td colspan='2' class='tac'> Nenhum resultado encontrado! </td> </tr>";

        }else{

            for ($x = 0 ; $x < pg_num_rows($res) ; $x++){

                $segmento_atuacao = trim(pg_fetch_result($res, $x ,"segmento_atuacao"));
                $nome             = trim(pg_fetch_result($res, $x, "descricao"));
                $ativo            = trim(pg_fetch_result($res, $x, "ativo"));

                $ativo = ($ativo == "t") ? "<img title='Ativo' src='imagens/status_verde.png'>" : "<img title='Inativo' src='imagens/status_vermelho.png'>";

                echo "<tr>";
                    echo "<td>";
                        echo "<a href='$PHP_SELF?segmento_atuacao={$segmento_atuacao}'>$nome</a>";
                    echo "</td>";
                    echo "<td class='tac'>$ativo</td>";
                echo "</tr>";

            }   
        }

    ?>
    </tbody>
</table>

<br /> <br />

<?php include "rodape.php"; ?>
