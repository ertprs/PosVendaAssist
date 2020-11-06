<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';

$origem       = $_GET['origem'];
$data_inicial = $_GET['data_inicial'];
$data_final   = $_GET['data_final'];
$programaphp = "callcenter_interativo_new.php";

$sql = "SELECT
          tbl_hd_chamado.hd_chamado AS atendimento,
          tbl_hd_chamado.categoria,
          tbl_hd_chamado_extra.nome AS nome_consumidor,
          tbl_hd_chamado_extra.origem AS origem_atendimento
        FROM tbl_hd_chamado
        JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
        WHERE tbl_hd_chamado.data between '$data_inicial 00:00:00' AND '$data_final 23:59:59'
        AND tbl_hd_chamado.fabrica = $login_fabrica
        AND tbl_hd_chamado_extra.origem = '$origem'";
$resSubmit = pg_query($con, $sql);


/// EXECEL //

$data = date ("d-m-Y-H-i");

$arquivo_nome = "relatorio_callcenter_origem_detalhe-$data.xls";
$path         = "/var/www/assist/www/admin/xls/";
//$path         = "/home/monteiro/public_html/posvenda/admin/xls/";
$path_tmp     = "/tmp/assist/";

$arquivo_completo     = $path.$arquivo_nome;
$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

echo `rm $arquivo_completo_tmp `;
echo `rm $arquivo_completo_tmp.zip `;
echo `rm $arquivo_completo.zip `;
echo `rm $arquivo_completo `;

$fp = fopen ($arquivo_completo_tmp,"w");
fputs ($fp, "ATENDIMENTO \t NOME CONSUMIDOR \t ORIGEM \r\n");

////
$layout_menu = "callcenter";
$title = "RELATÓRIO CALL-CENTER x ORIGEM DETALHE";
include 'cabecalho_new.php';

$plugins = array(
  "dataTable"
);
include("plugin_loader.php");
?>
<script type="text/javascript">
  var hora = new Date();
  var engana = hora.getTime();

  $(function() {
    $.datepickerLoad(Array("data_final", "data_inicial"));
    $.autocompleteLoad(Array("produto", "peca", "posto"));
    Shadowbox.init();

    $("span[rel=lupa]").click(function () {
      $.lupa($(this));
    });
  });
</script>

<?php
if (isset($resSubmit)) {
  if(pg_num_rows($resSubmit) > 0) {

    $count = pg_num_rows($resSubmit);
?>
  <table id="relatorio_callcenter_origem_detalhe" class='table table-striped table-bordered table-fixed' >
    <thead>
      <tr class='titulo_tabela'>
        <th colspan="3">RELATÓRIO CALLCENTER X ORIGEM DETALHE</th>
      </tr>
      <tr class='titulo_coluna' >
        <th>Atendimento</th>
        <th>Nome Consumidor</th>
        <th>Origem</th>
      </tr>
    </thead>
    <tbody>
    <?php
      for ($i = 0; $i < $count; $i++) {

        $atendimento      = pg_fetch_result($resSubmit, $i, 'atendimento');
        $categoria      = pg_fetch_result($resSubmit, $i, 'categoria');
        $nome_consumidor  = pg_fetch_result($resSubmit, $i, 'nome_consumidor');
        $origem_atendimento   = pg_fetch_result($resSubmit, $i, 'origem_atendimento');

        fputs($fp,pg_fetch_result($resSubmit,$i,'atendimento')."\t");
        fputs($fp,pg_fetch_result($resSubmit,$i,'nome_consumidor')."\t");
        fputs($fp,pg_fetch_result($resSubmit,$i,'origem_atendimento')."\t");
        fputs($fp,"\r\n");


        $body = "
          <tr>
            <td class='tac'><a href='$programaphp?callcenter=$atendimento#$categoria' target='blank'> $atendimento</td>
            <td class='tal'>$nome_consumidor</td>
            <td class='tac'>$origem_atendimento</td>
          </tr>
        ";
        echo $body;
      }
    ?>
</tbody>
</table>
<br />

<?php
  fclose ($fp);
  flush();
  echo `cd $path_tmp; rm -rf $arquivo_nome.zip; zip -o $arquivo_nome.zip $arquivo_nome > /dev/null ; mv  $arquivo_nome.zip $path `;
  echo "<div id='id_download2' class='btn_excel'>
    <a href='xls/$arquivo_nome.zip'>
      <span><img src='../imagens/excel.png'></span>
      <span class='txt'>Gerar Arquivo Excel</span>
    </a>
  </div>";
?>

<br />
<?php
  }
}
include 'rodape.php';?>