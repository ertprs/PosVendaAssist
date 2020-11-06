<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
$layout_menu = "cadastro";
$title = "CADASTRO DE GRUPOS DE SERVIÇOS";

include 'autentica_admin.php';
include 'funcoes.php';
include 'cabecalho_new.php';

$descricao = '';
$sucesso = '';
$erro = '';

if (!empty($_POST)) {
    $descricao = $_POST['descricao'];

    if (!empty($descricao)) {

        if (!empty($_POST['grupo'])) {
            $grupo = (int) $_POST['grupo'];

            if (!empty($_POST['excluir'])) {
                $sql = "SELECT count(*) as t from tbl_servico_realizado where servico_realizado_grupo = $grupo and fabrica= $login_fabrica";
                $qry = pg_query($con, $sql);

                $t = pg_fetch_result($qry, 0, 't');

                if ($t == 0) {
                    $del = "DELETE FROM tbl_servico_realizado_grupo WHERE servico_realizado_grupo = $grupo";
                    $qry = pg_query($con, $del);

                    if (!pg_last_error()) {
                        $sucesso = '
                            <div class="container">
                                 <div class="alert alert-success">
                                     <h4>Grupo excluido com sucesso.</h4>
                                  </div> 
                             </div>';
                        $descricao = '';
                        $grupo = '';
                    }
                } else {
                     $erro = '
                        <div class="container">
                             <div class="alert alert-error">
                                 <h4>Não foi possível excluir o grupo - deve ser excluido os agrupamentos primeiro.</h4>
                              </div>
                         </div>
                        ';
   
                }
            } else {
                $chk = "SELECT descricao FROM tbl_servico_realizado_grupo WHERE servico_realizado_grupo = $grupo and fabrica = $login_fabrica";
                $qry = pg_query($con, $chk);

                if (pg_num_rows($qry) == 1) {
                    $sql = "UPDATE tbl_servico_realizado_grupo SET descricao = '{$descricao}' WHERE servico_realizado_grupo = $grupo";
                    $msg = 'Atualizado';
                    $descricao = '';
                    $grupo = '';
                }
            }
        } else {
            $chk = "SELECT descricao FROM tbl_servico_realizado_grupo WHERE descricao = '{$descricao}' and fabrica = $login_fabrica";
            $qry = pg_query($con, $chk);

            if (pg_num_rows($qry) > 0) {
                $erro = '
                    <div class="container">
                         <div class="alert alert-error">
                             <h4>Grupo já cadastrado.</h4>
                          </div> 
                     </div>
                    ';
            }

            $sql = "INSERT INTO tbl_servico_realizado_grupo (
                        descricao, fabrica
                    ) VALUES (
                        '{$descricao}', $login_fabrica
                    )";
            $msg = 'Gravado';
        }

        if (empty($erro) and empty($_POST['excluir'])) {
            $qry = pg_query($con, $sql);

            if (!pg_last_error()) {
                $sucesso = '
                    <div class="container">
                         <div class="alert alert-success">
                             <h4>' . $msg . ' com sucesso.</h4>
                          </div> 
                     </div>';
                $descricao = '';
            }
        }
    } else {
        $erro = '
            <div class="container">
                 <div class="alert alert-error">
                     <h4>Favor preencher a descrição.</h4>
                  </div> 
             </div>
            ';
    }


}

if (!empty($_GET['grupo'])) {
    $grupo = (int) $_GET['grupo'];

    $sql = "SELECT descricao FROM tbl_servico_realizado_grupo WHERE servico_realizado_grupo = $grupo and fabrica = $login_fabrica";
    $qry = pg_query($con, $sql);

    if (pg_num_rows($qry) == 1) {
        $descricao = pg_fetch_result($qry, 0, 'descricao');
    }

}


echo $sucesso;
echo $erro;

?>


<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='servico_realizado_grupo_cadastro.php' align='center' class='form-search form-inline tc_formulario' >
		<div class='titulo_tabela '>Cadastro de Grupos</div>
		<br/>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao'>Descrição</label>
					<div class='controls controls-row'>
						<div class='span12input-append'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="descricao" id="descricao" class='span12' value="<? echo $descricao ?>" >
                            <input type="hidden" name="grupo" value="<?php echo $grupo ?>" >
						</div>
					</div>
				</div>
			</div>

			<div class='span2'></div>
		</div>
		

		<p><br/>

			<input type='hidden' id="btn_click" name='btn_acao' value='' />

			<?php
			if (!empty($grupo)) {
				$value_btn = "atualizar";
                $acao = 'Atualizar';
			?>
				
			<?php
			}else{
				$value_btn = "gravar";
                $acao = 'Gravar';
			}
			?>
                <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'),'<?=$value_btn?>');"><?php echo $acao ?></button>

<?php
if ($acao == 'Atualizar') {
    echo '<button type="submit" name="excluir" value="true" class="btn btn-danger" style="margin-left: 20px;">Excluir</button>';
}
              ?>
		</p><br/>
</form>


<?php

$sql = "SELECT servico_realizado_grupo, descricao FROM tbl_servico_realizado_grupo WHERE fabrica = {$login_fabrica} AND ativo ORDER BY descricao";
$qry = pg_query($con, $sql);
$i = 0;

echo '<table id="resultado_grupos" class="table table-striped table-bordered table-hover table-large" >';

echo '<thead>';
    echo "<tr class='titulo_coluna'>\n";
        echo "<td>Descrição</td>\n";
    echo "</tr>\n";
echo '</thead>';

echo '<tbody>';
while ($fetch = pg_fetch_assoc($qry)) {
    $cor = ($i % 2 == 0) ? '#F1F4FA' : '#F7F5F0';

    $servico_realizado_grupo = $fetch['servico_realizado_grupo'];
    $open_a = '<a href="?grupo=' . $servico_realizado_grupo . '">';
    $close_a = '</a>';

    echo '<tr>';
        echo '<td align="left">' . $open_a . $fetch['descricao'] . $close_a . '</td>'; 
    echo '</tr>';

    $i++;
}
echo '</tbody>';

echo '</table>';

include "rodape.php";

