<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios='cadastros';
require_once 'autentica_admin.php';
include_once 'helper.php';

include_once 'funcoes.php';

$title = traduz("Peça X Defeito X Garantia");
$layout_menu = "cadastro";
include 'cabecalho_new.php';
?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@9"></script>
    
<script>
    function fnc_pesquisa_peca(campo, campo2, tipo) {
        var login_fabrica = <?=$login_fabrica?>;
        var tipo_peca = '';

        if (tipo == "referencia") {
            var xcampo = campo.value;
        }
        if (tipo == "descricao") {
            var xcampo = campo2.value;
        }

            if (xcampo != "") {
                var url = "";
                url     = "peca_pesquisa_2.php?peca_pedido=t&campo=" + xcampo + "&tipo=" + tipo;
                janela  = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");

                peca_referencia = campo;
                peca_descricao  = campo2;

                janela.focus();
            } else {
                alert("Informe toda ou parte da informação para realizar a pesquisa!");
            }
    }

    function excluir(peca_defeito_garantia) {
        Swal.fire({
            title: 'Tem certeza que deseja excluir esse registro?',
            icon: 'warning',
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sim'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    type: 'POST',
                    url: "<?=$_SERVER['PHP_SELF']?>",
                    data: {
                        excluir: true,
                        peca_defeito_garantia : peca_defeito_garantia,
                    }
                }).done(function(msg){
                    Swal.fire("Registro apagado com sucesso!", '', "success").then((result) =>{
                        $('#consultar').click();
                    });
                }).fail(function(msg){
                    Swal.fire("Ocorreu um erro ao excluir o arquivo!", '', "danger");
                });
            }
        });
 	}

</script>


<?php

if(isset($_POST['excluir'])){
    $peca_defeito_garantia = $_POST['peca_defeito_garantia'];

    try{

		$query = "DELETE FROM tbl_peca_defeito_garantia 
                        WHERE tbl_peca_defeito_garantia.peca_defeito_garantia = $peca_defeito_garantia";

		$res = pg_query($con, $query);
		if (strlen(pg_last_error($res)) > 0) {
            exit(json_encode(['msg' => 'error']));
		}
		exit(json_encode(['msg' => 'success']));

	} catch (Exception $e) {
		exit(json_encode(['msg' => 'error']));
	}
}

if($_POST['gravar'] == 'Gravar'){
    $ref_peca = $_POST['ref_peca'];
    $defeito = $_POST['defeito'];
    $garantia = $_POST['garantia'];
    
    $sql_peca = "SELECT peca FROM tbl_peca WHERE referencia = '$ref_peca' and fabrica = {$login_fabrica}";
    $res_peca = pg_query($con, $sql_peca);

    if(pg_num_rows($res_peca) > 0){
        $peca = pg_fetch_result($res_peca, 'peca');
    }

    $insert = "INSERT INTO tbl_peca_defeito_garantia(peca,defeito,garantia) VALUES($peca,$defeito,$garantia)";
    $res_insert = pg_query($con, $insert);

    if($res_insert == true){ ?>
        <div class="alert alert-sucess">
            <h4>Gravado com sucesso</h4>
        </div>
    <?php 
        $ref_peca = '';
        $nome = '';
        $_POST['defeito']  = '';
        $garantia = '';
    }else { ?>
         <div class="alert alert-error">
            <h4>Registro já cadastrado</h4>
            <br>
        </div>
    <?php }
}

?>

<div id="principal">
    <form name="frm_peca" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
        <div class='titulo_tabela '>Consulta Tabela de Preço</div>
        <br/>
        <div class="row-fluid">
            <div class='span2'></div>
            <div class="span4">
                <div class='control-group'>
                    <label class='control-label' for='ref_peca'>Ref. Peça</label>
                    <div class='controls controls-row'>
                        <div class='span10 input-append'>
                            <input type="text" id="ref_peca" name="ref_peca" class='span12' maxlength="20" value="<? echo $ref_peca ?>" style="width: 245px;">
                            <a href="#"><span class='add-on' rel="lupa" >
                                <i class='icon-search' onclick="javascript: fnc_pesquisa_peca (window.document.frm_peca.ref_peca , window.document.frm_peca.nome, 'referencia')">
                            </i></span></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class='control-group'>
                    <label class='control-label' for='nome'>Descrição Peça</label>
                    <div class='controls controls-row'>
                        <div class='span10 input-append'>
                            <input type="text" id="nome" name="nome" class='span12' maxlength="20" value="<?php echo $nome ?>" style="width: 245px;">
                            <a href="#"><span class='add-on' rel="lupa" >
                                <i class='icon-search' onclick="javascript: fnc_pesquisa_peca (window.document.frm_peca.ref_peca , window.document.frm_peca.nome, 'descricao')">
                            </i></span></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class='span2'></div>
                <div class="span4">
                <div class="control-group">
                    <label class="control-label" for="defeito">Defeito</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <select id="defeito" name="defeito" class="span12">
                            <option value=''></option>
                            <?php
                                $sql_defeito = "SELECT defeito, descricao
                                                FROM tbl_defeito
                                                WHERE fabrica = {$login_fabrica}
                                                AND ativo = 't'; ";
                                $res_defeito = pg_query($con,$sql_defeito);

                                if (pg_num_rows($res_defeito) > 0) {
                                    $defeitos = pg_fetch_all($res_defeito);
                                    foreach ($defeitos as $resultado) {
                                        if ($_POST['defeito'] == $resultado['defeito']) {
                                            $selected = "SELECTED";
                                        }else{
                                            $selected = "";
                                        }
                                        
                                        echo "<option value='".$resultado['defeito']."'".$selected.">".$resultado['descricao']."</option>";
                                        
                                    }
                                }
                            ?>                       
                            
                            </select>
                        </div>
                    </div>
                </div>  
            </div>
            <div class="span4">
                <div class="control-group">
                    <label class="control-label" for="garantia">Garantia</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <input type="number" id="garantia" name="garantia" class='span12' maxlength="20" value="<?php echo $garantia ?>" style="width: 272px;">
                        </div>
                    </div>
                </div>  
            </div>
            <div class='span2'></div>
        </div>
        <br>
        <br />
        <p><br/>
            <input type="submit" class="btn" name="consultar" id="consultar" value="Consultar" />
            <input type="submit" class="btn" id="btn_submit" name="gravar" value="Gravar" />
        </p><br/>
    </form>
</div>

<?php if($_POST['consultar'] == 'Consultar'){

    $arquivo_nome = "peca_defeito_garantia.csv";
    $file     = "xls/{$arquivo_nome}";
    $fileTemp = "/tmp/{$arquivo_nome}";
    $fp     = fopen($fileTemp,'w');
    fwrite($fp, "Referência;Descrição Peça;Descrição Defeito;Garantia\n");

    
    $ref_peca = $_POST['ref_peca'];
    $defeito  = $_POST['defeito'];
    $garantia = $_POST['garantia'];
   
    $sql_consulta .= "SELECT tbl_peca_defeito_garantia.peca_defeito_garantia,
                            tbl_peca_defeito_garantia.garantia, 
                            tbl_peca.referencia, 
                            tbl_peca.descricao AS DescPeca, 
                            tbl_defeito.descricao AS DescDefeito
                            FROM tbl_peca_defeito_garantia 
                            JOIN tbl_defeito on (tbl_peca_defeito_garantia.defeito = tbl_defeito.defeito)
                            JOIN tbl_peca on (tbl_peca.peca = tbl_peca_defeito_garantia.peca)
                            JOIN tbl_fabrica on (tbl_fabrica.fabrica = tbl_peca.fabrica)
                            WHERE tbl_fabrica.fabrica = {$login_fabrica}";
                            
        if(strlen($codigo) > 0 ){
            $sql_consulta .= " AND tbl_peca.referencia = '$ref_peca'";
        }

        if(strlen($defeito) > 0 ){
            $sql_consulta .= " AND tbl_defeito.defeito = $defeito";
        }

        if(strlen($tabela) > 0 ){
            $sql_consulta .= " AND tbl_peca_defeito_garantia.garantia = $garantia";
        }

        $res_consulta = pg_query($con, $sql_consulta);
?>
    <div class="alert alert-sucess" id="mensagem" style="display: none"></div>
    <table class='table table-striped table-bordered table-hover table-fixed'>
        <thead>
            <tr class='titulo_coluna'>
                <td>Referência</td>
                <td>Descrição Peça</td>
                <td>Descrição Defeito</td>
                <td>Garantia</td>
                <td>Ações</td>
            </tr>
        </thead>
        <tbody>
        <?php
            if(pg_num_rows($res_consulta) > 0){
                for($c = 0; $c < pg_num_rows($res_consulta); $c++){
                    $id          = pg_fetch_result($res_consulta, $c, 'peca_defeito_garantia');
                    $ref_peca          = pg_fetch_result($res_consulta, $c, 'referencia'); 
                    $descricao_peca    = pg_fetch_result($res_consulta, $c, 'DescPeca');
                    $descricao_defeito = pg_fetch_result($res_consulta, $c, 'DescDefeito');
                    $garantia          = pg_fetch_result($res_consulta, $c, 'garantia');

                    $body .= $ref_peca .";";
                    $body .= $descricao_peca. ";";
                    $body .= $descricao_defeito. ";";
                    $body .= ($garantia == 1)  ? $garantia . " mês" : $garantia . " mêses"."\n";
                    
                    
                    ?>
                <tr>
                    <td><?= $ref_peca ?></td>
                    <td><?= $descricao_peca ?></td>
                    <td><?= $descricao_defeito ?></td>
                    <td><?= ($garantia == 1)  ? $garantia . " mês" : $garantia . " mêses"?></td>
                    <td>
                        <a class="btn btn-danger" href="javascript: excluir('<?=$id?>')">Excluir</a>
                    </td>
                </tr>
                <?php }?>
            <?php } else {
                echo '<td colspan="5" style="text-align: center">Nenhum Registro Encontrado</td>';
            } 
            
            fwrite($fp, $body);
            fclose($fp);
            if(file_exists($fileTemp)){
                system("mv $fileTemp $file");
                if(pg_num_rows($res_consulta) > 0){
                    if(file_exists($file)){
                        echo '<div style="margin: 10px 377px;"><a class="btn btn-success" href="'.$file.'" download>Gerar CSV</a></div>';
                    }
                }
            }
            ?>
        </tbody>
    </table>
<?php 
}?>
