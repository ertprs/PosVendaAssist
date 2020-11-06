<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$TITULO    = 'Alteração de postos';

include "menu.php";

function getRegras() {
  global $con;

  $sql = "SELECT parametros_adicionais
          FROM tbl_unidade_negocio";
  $res = pg_query($con, $sql);


  $retornoRegras = [];
  while ($dados = pg_fetch_object($res)) {

    $parametros_adicionais = json_decode($dados->parametros_adicionais, true);

    foreach ($parametros_adicionais as $regra => $valor) {

      if ($valor) {

        $retornoRegras[$regra] = $regra;

      }

    }

  }

  return $retornoRegras;

}

$regrasUn = getRegras();

unset($regrasUn["resumoOsSqlAbe"], $regrasUn["resumoOsSqlFruki"], $regrasUn["resumoOsSqlTa"], $regrasUn["resumoOsSqlItu"]);

?>
<br /><br />
<div style="font-size: 10px;">
  <table data-toggle="table" data-stripe="true" data-sort-name="Fábrica" data-pagination="true" data-page-size="20" class="table table-condensed table-bordered table-fixed table-hover">
    <thead>
      <tr>
        <th style="background-color: #2b2c50 !important;color: white;text-align: center;font-size: 15px;" colspan="100%">Regras Unidades de Negócio</th>
      </tr>
      <tr class="titulo_coluna">
        <th style="background-color: #2b2c50 !important;color: white;text-align: center;">Unidade Negócio</th>
        <th style="background-color: #2b2c50 !important;color: white;text-align: center;">Centro Custo Garantia</th>
        <th style="background-color: #2b2c50 !important;color: white;text-align: center;">Centro Custo Fora Garantia</th>
        <th style="background-color: #2b2c50 !important;color: white;text-align: center;">Cód. Centro Custo</th>
        <th style="background-color: #2b2c50 !important;color: white;text-align: center;">Grupo Centro Custo</th>
        <?php
        foreach ($regrasUn as $regra => $valor) {

          echo '<th style="background-color: #2b2c50 !important;color: white;text-align: center;">'.$valor.'</th>';

        }
        ?>
      </tr>
    </thead>
    <tbody>
      <?php
      $sqlUnidades = "SELECT codigo || ' - ' || nome as unidade_negocio,
                             codigo as unidade_codigo,
                             centro_custo,
                             codigo_centro_custo,
                             grupo_centro_custo,
                             centro_custo_garantia
                      FROM tbl_unidade_negocio
                      ";
      $resUnidades = pg_query($con, $sqlUnidades);

      while ($dadosUn = pg_fetch_object($resUnidades)) {
      ?>
        <tr>
          <td><strong><?= $dadosUn->unidade_negocio ?></strong></td>
          <td style="text-align: center;"><?= $dadosUn->centro_custo_garantia ?></td>
          <td style="text-align: center;"><?= $dadosUn->centro_custo ?></td>
          <td style="text-align: center;"><?= $dadosUn->codigo_centro_custo ?></td>
          <td style="text-align: center;"><?= $dadosUn->grupo_centro_custo ?></td>
          <?php
          foreach ($regrasUn as $regraUn => $valorUn) {

            $sql = " 
            SELECT DISTINCT tbl_unidade_negocio.codigo
            FROM tbl_unidade_negocio
            JOIN tbl_distribuidor_sla ON tbl_distribuidor_sla.unidade_negocio = tbl_unidade_negocio.codigo
            WHERE (tbl_unidade_negocio.parametros_adicionais->>'{$valorUn}')::boolean IS TRUE
            AND tbl_unidade_negocio.codigo = '{$dadosUn->unidade_codigo}'
            ";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
              echo "<td align='center'><img src='imagens/status_verde.png'></td>";
            }else{
              echo "<td align='center'><img src='imagens/status_vermelho.png'></td>";
            }

          }
          ?>
        </tr>
      <?php
      }
      ?>
    </tbody>
  </table>
</div>
<?php
include "rodape.php"
?>
