<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="callcenter";
include "autentica_admin.php";
include 'funcoes.php';
$cancelar_lote = false;

$os   = $_GET["sua_os"];
$tipo = $_GET["tipo"];
$cancelar_lote = $_GET["cancelar_lote"];

$btn_acao    = trim($_REQUEST["btn_acao"]);
$select_acao = trim($_POST["select_acao"]);
$target = filter_input(INPUT_GET,'target');


if(isset($_GET["cancelar_os"])){
    $xos = $_GET["cancelar_os"];

    $sql = "SELECT * FROM tbl_os_status WHERE os = $xos order by data desc limit 1";
    $res = pg_query($con, $sql);
    if(pg_num_rows($res)>0){
        $status_os = pg_fetch_result($res, 0, status_os);
    }else{
        $status_os = "";
    }

    if($status_os != 245){
        $sql = "INSERT INTO tbl_os_status
                    (os,status_os,data,observacao,admin)
                    VALUES ($xos,245,current_timestamp,'Cancelamento de O.S',$login_admin)";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_last_error($con);

        if (strlen($msg_erro)==0){
            $ok = "O.S enviada para cancelamento. <Br>";
        }else{
            $msg_erro = "Falha ao cancelar O.S";
        }
    }else{
        $msg_erro .= "O.S já cancelada.";
    }

}

if(strlen($btn_acao)>0 AND strlen($select_acao)>0){
    $observacao  = trim($_POST["xjustificativa"]);

    if(strlen($observacao) > 0){
        $observacao = "' Observação: $observacao '";
    }else{
        $observacao = " NULL ";
    }
    
    foreach($_POST['os_lote'] as $xos){
        $res_os = pg_query($con,"BEGIN TRANSACTION");

        $sql = "SELECT * FROM tbl_os_status WHERE os = $xos order by data desc limit 1";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res)>0){
            $status_os = pg_fetch_result($res, 0, status_os);
        }else{
            $status_os = "";
        }

        if($status_os != 245){
            $sql = "INSERT INTO tbl_os_status
                    (os,status_os,data,observacao,admin)
                    VALUES ($xos,245,current_timestamp,$observacao,$login_admin)";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_last_error($con);

            if (strlen($msg_erro)==0){
                $res = pg_query($con,"COMMIT TRANSACTION");
                $ok = "O.Ss enviadas para cancelamento. <Br>";
            }else{
                $res = pg_query($con,"ROLLBACK TRANSACTION");
                $msg_erro = "Falha ao cancelar O.Ss";
            }
        }
    }
    $lote = true;
}



$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

$layout_menu = "callcenter";
$title = "CANCELAMENTO DE ORDEM DE SERVIÇO";

include 'cabecalho_new.php';

$plugins = array(
    "shadowbox",
    "dataTable",
    "multiselect"
);

include("plugin_loader.php");

if($btn_acao == 'Pesquisar'){

    $os = trim($_POST['sua_os']);
    $os = str_pad($os, 12, "0", STR_PAD_LEFT);

    $registro = array();

    if ($login_fabrica == 1 && isset($target)) {
        $os = filter_input(INPUT_GET,'os');
    }

    if (count($_FILES['upload']) > 0 && $cancelar_lote) {

        if ($_FILES['upload']['size'] > 1048576) {
            $msg_erro = "Tamanho máximo permitido do arquivo é de 1MB. ";
        }
        if (empty($msg_erro)) {
            $arquivo = fopen($_FILES['upload']['tmp_name'], 'r+');
            $x = 0;
            while(!feof($arquivo)){

                $linha = fgets($arquivo,4096);

                if (strlen(trim($linha)) > 0) {
                    list($oss) = explode("\n", $linha);
                    $oss = trim($oss);

                    if(strlen($oss)>12){
                        $os_revenda = explode('-', $oss);
                        $os_revenda[0] = str_pad($os_revenda[0], 12, "0", STR_PAD_LEFT);
                        $oss = $os_revenda[0].'-'.$os_revenda[1];
                    }else{
                        $oss = str_pad($oss, 12, "0", STR_PAD_LEFT);
                    }                    
                    $registro[$x]        = trim($oss);
                }
                $x++;
            }
            fclose($f);
        }
    } 

    if(count($registro) > 0){
            $Xos = " AND tbl_os.sua_os in('".implode("','", $registro)."') ";
    }elseif (strlen($os)>0){                
            $Xos = " AND tbl_os.sua_os = '$os' ";
    }else{
        $msg_erro = " Informe a OS a ser Cancelada. ";
    }
}
?>
<script>
    $(function() {
       $('.select-all').click(function(event) {
          if(this.checked) {
              $(':checkbox').each(function() {
                  this.checked = true;
              });
          } else {
            $(':checkbox').each(function() {
                  this.checked = false;
              });
          }
        });
    });
</script>

<?php if (strlen($msg_erro) > 0) { ?>
    <div class="alert alert-error">
        <h4><?php echo $msg_erro;?></h4>
    </div>
<?php } ?>

<?php if (strlen($ok) > 0) { ?>
    <div class="alert alert-success">
        <h4><?php echo $ok;?></h4>
    </div>
<?php } ?>

<?php if (!$cancelar_lote) {?>

<form name='frm_pesquisa' METHOD='POST' enctype="multipart/form-data" ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
    <div class='titulo_tabela '>Cancelamento de OS</div><br/>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label'>Número da OS&nbsp;</label>
                <div class='controls controls-row'>
                    <div class='span9'>
                        <?php
                            
                            $aux_sql  = "SELECT posto, sua_os FROM tbl_os WHERE os = $os LIMIT 1";
                            $aux_res  = pg_query($con, $aux_sql);
                            $aux_so   = pg_fetch_result($aux_res, 0, 'sua_os');
                            $aux_post = pg_fetch_result($aux_res, 0, 'posto');

                            $aux_sql = "SELECT codigo_posto FROM tbl_posto_fabrica WHERE posto = $aux_post AND fabrica = $login_fabrica LIMIT 1";
                            $aux_res = pg_query($con, $aux_sql);
                            $aux_cp  = pg_fetch_result($aux_res, 0, 'codigo_posto');

                            $sua_os = $aux_cp.$aux_so;
                        ?>
                        <input type="text" name="sua_os" id="sua_os" size="20" maxlength="20" value="<? echo $sua_os ?>" class="span12">
                    </div>
                    <div class='span3'>
                        <input type='hidden' name='btn_acao' value=''>
                        <input type='button' class="btn btn-primary" value='Pesquisar' onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='Pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'>
                    </div>
                </div>
            </div><br />
        </div>
        <div class='span2'></div>
        <div class='span2'></div>
    </div>

</form>
<?php }?>

<br>
<?php if ($login_fabrica == 3 AND strlen($btn_acao)  == 0 OR $cancelar_lote) {?>
<form name='frm_upload' METHOD='POST' enctype="multipart/form-data" ACTION='cancelar_os.php?cancelar_lote=true' align='center' class='form-search form-inline tc_formulario' >
    <div class='titulo_tabela '>Cancelamento de OS em lote</div><br/>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span8'>
            <div class="alert alert-warning">
                <b>Arquivo deve ser no formato .CSV ou .TXT</b>
                <p>Obs: O arquivo deve ter o tamanho máximo de 1mb.</p>
            </div>
            <div class='control-group <?=(in_array("upload", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label'>Arquivo:&nbsp;</label>
                <div class='controls controls-row'>
                    <div class='span7'>
                        <input type="file" name="upload" id="upload">
                    </div>
                    <div class='span5'>
                        <button class='btn btn-success' id="btn_acao" type="button"  onclick="submit('#frm_upload');">Efetuar o Upload</button>
                         <input type='hidden' id="sua_os" name='sua_os' value='011220000130' />
                         <input type='hidden' id="btn_click" name='btn_acao' value='Pesquisar' />
                    </div>
                </div>
            </div><br />
        </div>
        <div class='span2'></div>
    </div>
</form>
<?php }?>
<br>
<?php
if (strlen($btn_acao)  > 0 AND strlen($msg_erro)==0 and !$lote) {

    $sql =  "SELECT tbl_os.os                                                   ,
                    tbl_os.sua_os                                               ,
                    tbl_os.consumidor_nome                                      ,
                    TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura,
                    TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
                    tbl_os.fabrica                                              ,
                    tbl_os.consumidor_nome                                      ,
                    tbl_os.nota_fiscal_saida                                    ,
                    tbl_os.serie                       AS produto_serie         ,
                    to_char(tbl_os.data_nf_saida,'DD/MM/YYYY') AS data_nf_saida ,
                    tbl_posto.nome                     AS posto_nome            ,
                    tbl_posto_fabrica.codigo_posto                              ,
                    tbl_posto_fabrica.contato_estado                            ,
                    tbl_produto.referencia             AS produto_referencia    ,
                    tbl_produto.descricao              AS produto_descricao     ,
                    tbl_produto.voltagem                                        ,
                    (SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (110,111,112) ORDER BY data DESC LIMIT 1) AS status_os         ,
                    (SELECT observacao FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (110,111,112) ORDER BY data DESC LIMIT 1) AS status_observacao,
                    (SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os.os = tbl_os_status.os AND status_os IN (110,111,112) ORDER BY data DESC LIMIT 1) AS status_descricao
                    {$Xcampo}
                FROM tbl_os
                JOIN tbl_produto              ON tbl_produto.produto = tbl_os.produto
                JOIN tbl_posto                ON tbl_os.posto        = tbl_posto.posto
                JOIN tbl_posto_fabrica        ON tbl_posto.posto     = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                $Xjoin
                WHERE tbl_os.fabrica = tbl_os.fabrica
                $Xos
                AND tbl_os.excluida IS FALSE
                ";
    $res = pg_query($con,$sql);
    if (pg_num_rows($res) > 0) {
?>
</div>
    <form name="fm_exclui_oss_em_lote" id="fm_exclui_oss_em_lote" action="cancelar_os.php" method='POST'>
    <table class='table table-striped table-bordered table-hover table-fixed'>
        <thead>
            <tr class='titulo_coluna'>
                <?php if ($cancelar_lote) {?>
                <th class="tac"><input type="checkbox" class="select-all" name=""></th>
                <?php }?>
                <th>OS</th>
                <th>Data<br>Abertura</th>
                <th>Data <br>Digitação</th>
                <th>Posto</th>
                <th>Produto</th>
                <th>Descrição</th>
                <?php
                    if (!$cancelar_lote) {
                ?>
                <th>Excluir</th>
                <?php }?>
            </tr>
        </thead>
        <tbody>
        <?php
            $qtde_intervencao = 0;

            for ($x=0; $x < pg_num_rows($res);$x++) {

                $os                     = pg_fetch_result($res, $x, os);
                $sua_os                 = pg_fetch_result($res, $x, sua_os);
                $codigo_posto           = pg_fetch_result($res, $x, codigo_posto);
                $posto_nome             = pg_fetch_result($res, $x, posto_nome);
                $consumidor_nome        = pg_fetch_result($res, $x, consumidor_nome);
                $produto_referencia     = pg_fetch_result($res, $x, produto_referencia);
                $produto_descricao      = pg_fetch_result($res, $x, produto_descricao);
                $produto_serie          = pg_fetch_result($res, $x, produto_serie);
                $produto_voltagem       = pg_fetch_result($res, $x, voltagem);
                $data_digitacao         = pg_fetch_result($res, $x, data_digitacao);
                $data_abertura          = pg_fetch_result($res, $x, data_abertura);
                $status_os              = pg_fetch_result($res, $x, status_os);
                $status_observacao      = pg_fetch_result($res, $x, status_observacao);
                $status_descricao       = pg_fetch_result($res, $x, status_descricao);
                $nota_fiscal_saida      = pg_fetch_result($res, $x, nota_fiscal_saida);
                $data_nf_saida          = pg_fetch_result($res, $x, data_nf_saida);
                $nota_fiscal          = pg_fetch_result($res, $x, nota_fiscal);
                $data_nf          = pg_fetch_result($res, $x, data_nf);

                if ($login_fabrica == 1) {

                    $pedido = pg_fetch_result($res, $x, pedido);
                    $sqlPedido =  "SELECT seu_pedido
                                     FROM tbl_pedido
                                    WHERE pedido = {$pedido}
                                      AND fabrica = {$login_fabrica}";
                                   // echo "<pre>".print_r($sqlPedido, 1)."</pre>";exit;
                    $resPedido = pg_query($con, $sqlPedido);
                    if (pg_num_rows($resPedido) > 0) {
                        $xseu_pedido = fnc_so_numeros(pg_fetch_result($resPedido, 0, seu_pedido));
                    }


                    $sua_os = $codigo_posto.$sua_os;
                }

                echo "<tr id='linha_$x'>";
                if ($cancelar_lote) {
                    echo "<td class='tac'><input type='checkbox' name='os_lote[]' value='{$os}'></td>";
                }
                echo "<td nowrap ><a href='os_press.php?os=$os'  target='_blank'>$sua_os</a></td>";
                echo "<td>".$data_abertura. "</td>";
                echo "<td>".$data_digitacao. "</td>";
                echo "<td align='left' nowrap title='".$codigo_posto." - ".$posto_nome."'>".$codigo_posto." - ".substr($posto_nome,0,20) ."...</td>";
                echo "<td align='left' nowrap><acronym title='Produto: $produto_referencia - ' style='cursor: help'>". $produto_referencia ."</acronym></td>";
                echo "<td align='left' nowrap><acronym title='Produto: $produto_referencia - $produto_descricao' style='cursor: help'>". $produto_descricao ."</acronym></td>";

                if (!$cancelar_lote) {
                    echo "<td  nowrap><input type='button' class='btn btn-small btn-danger' value='Cancelar' onclick=\"window.location='cancelar_os.php?cancelar_os=$os'\"></td>";
                }
                echo "</tr>";
            }
        ?>
        </tbody>
        <?php
                if ($cancelar_lote) {
                    $selected = ($_POST["select_acao"] == "cancelar_os_lote") ? "selected" : "";
                    echo "
                        <tfoot>
                            <tr class='titulo_coluna'>
                                <td colspan='10' bgcolor='#596D9B'>
                                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; <font color='#FFFFFF'><B>COM MARCADOS:</B></font> &nbsp;
                                    <select name='select_acao' size='1'>
                                        <option value='cancelar_os_lote' {$selected}>Cancelar</option>
                                    </select>
                                   <textarea name='xjustificativa'></textarea>
                                    <button style='margin-top: -7px;' type='button'onclick=\"javascript: if (document.fm_exclui_oss_em_lote.btn_acao.value == '' ) { document.fm_exclui_oss_em_lote.btn_acao.value='Gravar' ;  document.fm_exclui_oss_em_lote.submit() } else { alert ('Aguarde submiss?o') }\" class='btn'>Gravar</button>
                                    <input type='hidden' name='btn_acao' value=''>
                                </td>
                            </tr>
                        </tfoot>";
                }

            } else {
                echo "<tr><td class='tac'><div class='alert alert-error'>Não foram encontrados resultados para esta pesquisa</div></td></tr>";
            }
        ?>
    </table>
    </form>
<?php
    }
include "rodape.php"
?>

