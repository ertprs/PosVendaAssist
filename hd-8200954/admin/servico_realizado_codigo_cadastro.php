<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
$layout_menu = "cadastro";
$title = "AGRUPAMENTO DE SERVIÇOS";

include 'autentica_admin.php';
include 'funcoes.php';
include 'cabecalho_new.php';

if (!empty($_POST)) {
    $servico_realizado = (int) $_POST['servico_realizado'];
    $grupo = $_POST['grupo'];

    if (!empty($_POST['excluir']) and !empty($servico_realizado)) {
        $update = "UPDATE tbl_servico_realizado SET servico_realizado_grupo = NULL 
                   WHERE servico_realizado = $servico_realizado
                   AND fabrica = $login_fabrica";
        $qry = pg_query($con, $update);

        if (!pg_result_error($qry)) {
            echo '
                 <div class="container">
                     <div class="alert alert-success">
                         <h4>Excluido com sucesso!</h4>
                      </div> 
                 </div>';
        }
    } else {

        if (!empty($servico_realizado) and !empty($grupo)) {
            $update = "UPDATE tbl_servico_realizado SET servico_realizado_grupo = $grupo 
                       WHERE servico_realizado = $servico_realizado
                       AND fabrica = $login_fabrica";
            $qry = pg_query($con, $update);

            if (!pg_result_error($qry)) {
                echo '
                     <div class="container">
                         <div class="alert alert-success">
                             <h4>Gravado com sucesso!</h4>
                          </div> 
                     </div>';
            }
            
        }
    }

}

if (!empty($_GET['servico'])) {
    $servico = (int) $_GET['servico'];

    $sql = "SELECT tbl_servico_realizado.servico_realizado_grupo, 
                    tbl_servico_realizado_grupo.descricao as grupo,
                    tbl_servico_realizado.descricao
                FROM tbl_servico_realizado 
                LEFT JOIN tbl_servico_realizado_grupo USING (servico_realizado_grupo)
                WHERE servico_realizado = $servico";
    $qry = pg_query($con, $sql);

    if (pg_num_rows($qry) == 1) {
        $servico_realizado_grupo = pg_fetch_result($qry, 0, 'servico_realizado_grupo');
        $grupo = pg_fetch_result($qry, 0, 'grupo');
        $descricao = pg_fetch_result($qry, 0, 'descricao');

        $sql_grupos = "SELECT servico_realizado_grupo, descricao FROM tbl_servico_realizado_grupo
                       WHERE fabrica = $login_fabrica AND ativo";
        $qry_grupos = pg_query($con, $sql_grupos);

        echo '
        <form class="form-horizontal" action="servico_realizado_codigo_cadastro.php" method="post">
            <fieldset>

            <!-- Form Name -->
            <legend>Serviço: <strong>' . $descricao . '</strong></legend>

            <!-- Text input-->
            <div class="control-group">
              <label class="control-label" for="grupo">Grupo</label>
              <div class="controls">';
        
        echo '<select id="grupo" name="grupo" class="input-xlarge">
            <option value="0"></option>';

        while ($fetch_grupos = pg_fetch_assoc($qry_grupos)) {
            echo '<option value="' , $fetch_grupos['servico_realizado_grupo'] , '"';
            if ($servico_realizado_grupo == $fetch_grupos['servico_realizado_grupo']) {
                echo ' selected="selected"';
            }
            echo '>' , $fetch_grupos['descricao'] , '</option>';
        }

        echo '
                    <input type="hidden" name="servico_realizado" value="' . $servico . '">
                    <button type="submit" class="btn btn-success" style="margin-left: 20px;">Gravar</button>
                    <button type="submit" name="excluir" value="true" class="btn btn-danger" style="margin-left: 20px;">Excluir</button>
                </div>
            </div>

            </fieldset>
         </form>';
    }

}

$sql = "SELECT servico_realizado, 
               tbl_servico_realizado_grupo.servico_realizado_grupo,
               tbl_servico_realizado_grupo.descricao as grupo,
               tbl_servico_realizado.descricao 
           FROM tbl_servico_realizado 
           LEFT JOIN tbl_servico_realizado_grupo USING (servico_realizado_grupo)
           WHERE tbl_servico_realizado.fabrica = {$login_fabrica} AND tbl_servico_realizado.ativo 
           ORDER BY tbl_servico_realizado_grupo.descricao, tbl_servico_realizado.descricao";
$qry = pg_query($con, $sql);
$i = 0;

echo '<table id="resultado_servicos" class="table table-striped table-bordered table-hover table-large" >';

echo '<thead>';
    echo "<tr class='titulo_coluna'>\n";
        echo "<td>Grupo</td>\n";
        echo "<td>Serviço</td>\n";
    echo "</tr>\n";
echo '</thead>';

echo '<tbody>';
while ($fetch = pg_fetch_assoc($qry)) {
    $cor = ($i % 2 == 0) ? '#F1F4FA' : '#F7F5F0';

    $servico_realizado = $fetch['servico_realizado'];
    $open_a = '<a href="?servico=' . $servico_realizado . '">';
    $close_a = '</a>';

    echo '<tr>';
        echo '<td>' . $open_a . $fetch['grupo'] . $close_a . '</td>';
        echo '<td align="left">' . $open_a . $fetch['descricao'] . $close_a . '</td>'; 
    echo '</tr>';

    $i++;
}
echo '</tbody>';

echo '</table>';

include "rodape.php";

