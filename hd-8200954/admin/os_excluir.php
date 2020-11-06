<style>
table#resultado tr.pintar td {
	background: #FFA500;
}
</style>
<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="financeiro";
include "autentica_admin.php";
include 'funcoes.php';
$excluir_lote = false;

$os   = $_GET["sua_os"];
$tipo = $_GET["tipo"];
$excluir_lote = $_GET["excluir_lote"];

if($login_fabrica == 1) {
    $os      = $_GET["os"];
    $nova_os = $_GET['nova_os'];
}

if ($login_fabrica == 1 && strlen($os) > 0) {
    $aux_sql = "SELECT fabrica FROM tbl_os WHERE os = $os";
    $aux_res = pg_query($con, $aux_sql);
    $aux_fab = pg_fetch_result($aux_res, 0, 'fabrica');

    if ($aux_fab == "0") {
        $os_excluida_black = true;

        if (strlen($nova_os) > 0) {
            $aux_sql  = "SELECT fabrica FROM tbl_os WHERE os = $nova_os";
            $aux_res  = pg_query($con, $aux_sql);
            $aux_fab2 = pg_fetch_result($aux_res, 0, 'fabrica');

            if ($aux_fab2 == "1") {
                header("Location: os_press.php?os=$nova_os");
            }
        }
    } else {
        $os_excluida_black = false;
    }
}

$btn_acao    = trim($_REQUEST["btn_acao"]);
$select_acao = trim($_POST["select_acao"]);
$target = filter_input(INPUT_GET,'target');

if(strlen($btn_acao)>0 AND strlen($select_acao)>0){
    $qtde_os     = trim($_POST["qtde_os"]);
    $observacao  = trim($_POST["observacao"]);

    if($select_acao == "112" AND strlen($observacao) == 0){
        $msg_erro .= "Informe o motivo da reprovação.";
    }

    if(strlen($observacao) > 0){
        $observacao = "' Observação: $observacao '";
    }else{
        $observacao = " NULL ";
    }

    if (strlen($qtde_os)==0){
        $qtde_os = 0;
    }

    for ($x=0;$x<$qtde_os;$x++){

        $xxos = trim($_POST["check_".$x]);

        if (strlen($xxos) > 0 AND strlen($msg_erro) == 0){

            $res_os = pg_query($con,"BEGIN TRANSACTION");

            $sql = "SELECT status_os
                    FROM tbl_os_status
                    WHERE status_os IN (110,111,112)
                    AND os = $xxos
                    ORDER BY data DESC
                    LIMIT 1";
            $res_os = pg_query($con,$sql);
            if (pg_numrows($res_os)>0){
                $status_da_os = trim(pg_result($res_os,0,status_os));
                if ($status_da_os == 110){
                    //Aprovada
                    if($select_acao == "111"){
                        $sql = "INSERT INTO tbl_os_status
                                (os,status_os,data,observacao,admin)
                                VALUES ($xxos,$select_acao,current_timestamp,$observacao,$login_admin)";
                        $res = pg_query($con,$sql);
                        $msg_erro .= pg_errormessage($con);
                        $sql = "SELECT fn_os_excluida($os,$login_fabrica,$login_admin);";
                        $res = @pg_query($con,$sql);
                    }
                    //Recusada
                    if($select_acao == "112"){
                        $sql = "INSERT INTO tbl_os_status
                                (os,status_os,data,observacao,admin)
                                VALUES ($xxos,$select_acao,current_timestamp,$observacao,$login_admin)";
                        $res = pg_query($con,$sql);
                        $msg_erro .= pg_errormessage($con);
                    }
                }
            }

            if (strlen($msg_erro)==0){
                $res = pg_query($con,"COMMIT TRANSACTION");
            }else{
                $res = pg_query($con,"ROLLBACK TRANSACTION");
            }
        }
    }

}

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

$layout_menu = "financeiro";
$title = "EXCLUSÃO DE ORDEM DE SERVIÇO";

include 'cabecalho_new.php';

$plugins = array(
    "shadowbox",
    "dataTable",
    "multiselect"
);

include("plugin_loader.php");

if($btn_acao == 'Pesquisar'){

    $os = trim($_POST['sua_os']);
    $registro = array();

    if ($login_fabrica == 1 && isset($target)) {
        $os = filter_input(INPUT_GET,'os');
    }

    if (count($_FILES['upload']) > 0  && $login_fabrica == 1 && $excluir_lote) {

        if ($_FILES['upload']['size'] > 1048576) {
            $msg_erro = "Tamanho máximo permitido do arquivo é de 1MB. ";
        }
        if (empty($msg_erro)) {
            $arquivo = fopen($_FILES['upload']['tmp_name'], 'r+');
            $x = 0;
            while(!feof($arquivo)){

                $linha = fgets($arquivo,4096);

                if (strlen(trim($linha)) > 0) {
                    list($pedido, $referencia) = explode(";", $linha);
                    $registro[$x]['pedido']        = $pedido;
                    $registro[$x]['referencia']    = $referencia;
                }
                $x++;
            }
            fclose($f);
            $xcnpj   = "";
            $Xjoin   = "";
            foreach ($registro as $key => $rows) {

                $sqlPedido = "SELECT pedido
                                FROM tbl_pedido
                               WHERE tbl_pedido.seu_pedido = '".$rows['pedido']."'
                                 AND tbl_pedido.fabrica = $login_fabrica";
                $resPedido = pg_query($con, $sqlPedido);

                if (pg_num_rows($resPedido) == 0) {
                    continue;
                }
                $xpedido[$key]   = pg_fetch_result($resPedido, 0, 'pedido');
                $sqlPeca = "SELECT tbl_peca.peca
                              FROM tbl_peca
						 LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_peca.produto AND fabrica_i = $login_fabrica
								JOIN tbl_pedido_item USING(peca)
                             WHERE tbl_pedido_item.pedido = ".$xpedido[$key]."
                               AND (tbl_peca.referencia =  '".trim($rows["referencia"])."' OR tbl_produto.referencia =  '".trim($rows["referencia"])."')
                               AND tbl_peca.fabrica = $login_fabrica";
                $resPeca = pg_query($con, $sqlPeca);

                if (pg_num_rows($resPeca) == 0) {
                    continue;
                }
                $xpeca[$key]   = pg_fetch_result($resPeca, 0, 'peca');
				$cond[$key] = " (tbl_os_item.peca = $xpeca[$key] and  tbl_os_item.pedido = $xpedido[$key]) ";
            }

            if (count($xpedido) > 0 && count($xpeca) > 0) {
                $Xcampo = " ,tbl_os_item.pedido,
                             tbl_os_item_nf.nota_fiscal,
                             TO_CHAR (tbl_os_item_nf.data_nf,'DD/MM/YYYY') AS data_nf";
                $Xjoin = " JOIN tbl_os_produto USING(os)
                           JOIN tbl_os_item ON tbl_os_item.os_produto=tbl_os_produto.os_produto
                      LEFT JOIN tbl_os_item_nf ON tbl_os_item.os_item=tbl_os_item_nf.os_item ";
                $Xos = " AND (".implode(" or ", $cond).")
                       ";
            } else{
                $msg_erro = " Nenhum pedido encontrado. ";
            }
        }
    } else {

        if (strlen($os)>0){
                if ($login_fabrica == 1  && !isset($target)) {
                    $sua_os = $os;
                    $pos = strpos($sua_os, "-");

                    if ($pos === false) {

                        //hd 47506
						if(strlen ($sua_os) > 12) {
							$pos = strlen($sua_os) - (strlen($sua_os)-6);
						}else if(strlen ($sua_os) > 11){
                            $pos = strlen($sua_os) - (strlen($sua_os)-5);
                        } elseif(strlen ($sua_os) > 10) {
                            $pos = strlen($sua_os) - (strlen($sua_os)-6);
                        } elseif(strlen ($sua_os) > 9) {
                            $pos = strlen($sua_os) - (strlen($sua_os)-5);
                        }else{
                            $pos = strlen($sua_os);
                        }
                    }else{

                        //hd 47506
                        if(strlen (substr($sua_os,0,$pos)) > 11){#47506
                            $pos = $pos - 7;
                        } else if(strlen (substr($sua_os,0,$pos)) > 10) {
                            $pos = $pos - 6;
                        } elseif(strlen ($sua_os) > 9) {
                            $pos = $pos - 5;
                        }

                    }
				    $xsua_os = substr($sua_os, $pos,strlen($sua_os));
                    $codigo_posto = substr($sua_os,0,$pos);
                    $sqlPosto = "SELECT posto from tbl_posto_fabrica where codigo_posto = '$codigo_posto' and fabrica = $login_fabrica";
                    $res = pg_query($con,$sqlPosto);
                    $posto = pg_result($res,0,posto);
                    $xsua_os = substr($sua_os, $pos,strlen($sua_os));
                    $Xos = " AND tbl_os.posto = $posto AND tbl_os.sua_os = '$xsua_os' ";
                } else if ($login_fabrica == 1 && isset($target)) {
                    $Xos = " AND tbl_os.os = $os ";
                } else {
                    $Xos = " AND tbl_os.sua_os = '$os' ";
                }

        }else{
            $msg_erro = " Informe a OS a ser excluída. ";
        }
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

<?php if (!$excluir_lote) {?>

<form name='frm_pesquisa' METHOD='POST' enctype="multipart/form-data" ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
    <div class='titulo_tabela '>Exclusão de OS</div><br/>
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
<?php if ($login_fabrica == 1 AND strlen($btn_acao)  == 0 OR $excluir_lote) {?>
<form name='frm_upload' METHOD='POST' enctype="multipart/form-data" ACTION='os_excluir.php?excluir_lote=true' align='center' class='form-search form-inline tc_formulario' >
    <div class='titulo_tabela '>Exclusão de OS em lote</div><br/>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span8'>
            <div class="alert alert-warning">
                <b>Arquivo deve ser no formato .CSV ou .TXT</b>, separados por (;) ponto e virgula.<br />
                <b>Layout:</b> <em><b> Nº PEDIDO COM NOMENCLATURA;REFERENCIA</b></em>
                <p>Ex: <em><b> SPDXXXXX;Referência</b></em></p>
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
if (strlen($btn_acao)  > 0 AND strlen($msg_erro)==0) {

    $limitar_query          = "";
    $cond_os_excluida       = " AND tbl_os.excluida IS FALSE ";
    $join_tbl_posto_fabrica = " JOIN tbl_posto_fabrica        ON tbl_posto.posto     = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica ";

    if ($login_fabrica == 1 && $os_excluida_black == true) {
        $cond_os_excluida       = "";
        $join_tbl_posto_fabrica = " JOIN tbl_posto_fabrica        ON tbl_posto.posto     = tbl_posto_fabrica.posto ";
        $limitar_query          = " LIMIT 1 ";
    }

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
                    tbl_status_checkpoint.status_checkpoint,
                    tbl_status_checkpoint.descricao AS status_descricao,
                    (SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (110,111,112) ORDER BY data DESC LIMIT 1) AS status_os         ,
                    (SELECT observacao FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (110,111,112) ORDER BY data DESC LIMIT 1) AS status_observacao,
                    (SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os.os = tbl_os_status.os AND status_os IN (110,111,112) ORDER BY data DESC LIMIT 1) AS status_descricao
                    {$Xcampo}
                FROM tbl_os
                JOIN tbl_produto              ON tbl_produto.produto = tbl_os.produto
                JOIN tbl_posto                ON tbl_os.posto        = tbl_posto.posto
                $join_tbl_posto_fabrica
                JOIN tbl_status_checkpoint  USING (status_checkpoint)
                $Xjoin
                WHERE tbl_os.fabrica = tbl_os.fabrica
                $Xos
                $cond_os_excluida
                $limitar_query
                ";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
?>
</div>
<div class="dataTables_wrapper form-inline" role="grid" style="width: 1562px;" >
<table>
<tr>
<td>
<div style="background-color: #FFA500;width:9px;height:15px;margin:2px 5px;padding:0 5px;border:1px solid #666;">
</td>
<td>
<b>Quantidade de OS acima da quantidade de linhas do arquivo</b>
</td>
</tr>
</table>
</div>
 <form name="fm_exclui_oss_em_lote" id="fm_exclui_oss_em_lote" action="os_excluir_confirmar.php" method='POST'>	
    <table id='resultado' class='table table-striped table-bordered table-hover table-fixed'>
        <thead>
            <tr class='titulo_coluna'>
                <?php if ($login_fabrica == 1 && $excluir_lote) {?>
                <th class="tac"><input type="checkbox" class="select-all" name=""></th>
                <?php }?>
                <th>OS</th>
                <th>Data<br>Abertura</th>
                <th>Data <br>Digitação</th>
                <?php
                    if ($login_fabrica == 1 && $excluir_lote) {
                        echo "<th class='tac'>Status</th>";
                    }
                ?>
                <th>Posto</th>                
                <th>Produto</th>
                <th>Descrição</th>
                <?php
                    if ($login_fabrica == 1 && $excluir_lote) {
                        echo "<th class='tac'>Produto Origem</th>";
                        echo "<th class='tac'>Descrição Origem</th>";
                    }
                ?>
                <?php
                    if ($login_fabrica == 1 && $excluir_lote) {
                        echo "<th class='tac'>Pedido</th>";
                        echo "<th class='tac'>NF</th>";
                        echo "<th class='tac'>Emissão</th>";
                    }
                    if (!$excluir_lote) {
                ?>
                <th>Excluir</th>
                <?php }?>
            </tr>
        </thead>
        <tbody>
        <?php
            $qtde_intervencao = 0;
            $arraySql = [];
            $arrayRegistro = [];
            if (in_array($login_fabrica, [1])) {
                foreach ($registro as $reg) {
                    $keyArray = trim($reg['pedido'] . $reg['referencia']);
                    $arrayRegistro[$keyArray] = ++$arrayRegistro[$keyArray];
                }
                foreach (pg_fetch_all($res) as $sql) {          
                    $sqlPedido =  "SELECT seu_pedido
                                     FROM tbl_pedido
                                    WHERE pedido = {$sql['pedido']}
                                      AND fabrica = {$login_fabrica}";
                                   // echo "<pre>".print_r($sqlPedido, 1)."</pre>";exit;
                    $resPedido = pg_query($con, $sqlPedido);
                    $seu_pedido = pg_fetch_result($resPedido, 0, seu_pedido);
                    $keyArray = trim($seu_pedido .  $sql['produto_referencia']);
                    $arraySql[$keyArray] = ++$arraySql[$keyArray] ;              
                }
            }
            
            for ($x=0; $x < pg_num_rows($res);$x++) {
                $y = $x - 1;
                $os_anterior            = pg_fetch_result($res, $y, os);
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
                $status_descricao          = pg_fetch_result($res, $x, status_descricao);
                $status_checkpoint          = pg_fetch_result($res, $x, status_checkpoint);

                if ($login_fabrica == 1) {
                    $sqlNew = " SELECT tbl_produto.referencia, tbl_produto.descricao
                        FROM tbl_os_produto 
                            JOIN tbl_os_item USING (os_produto) 
                            JOIN tbl_peca USING (peca) 
                            JOIN tbl_produto ON tbl_produto.produto = tbl_peca.produto  
                        WHERE os = {$os}";
                    $resNew = pg_query($con,$sqlNew);
                    $new_referencia = pg_fetch_result($resNew, 0, referencia);
                    $new_descricao = pg_fetch_result($resNew, 0, descricao);

                    $pedido = pg_fetch_result($res, $x, pedido);
                    $sqlPedido =  "SELECT seu_pedido
                                     FROM tbl_pedido
                                    WHERE pedido = {$pedido}
                                      AND fabrica = {$login_fabrica}";
                                   // echo "<pre>".print_r($sqlPedido, 1)."</pre>";exit;
                    $resPedido = pg_query($con, $sqlPedido);
                    if (pg_num_rows($resPedido) > 0) {
                        $xseu_pedido = pg_fetch_result($resPedido, 0, seu_pedido);
                    }


                    $sua_os = $codigo_posto.$sua_os;
                }

                $excluiTroca = ($login_fabrica == 1 && isset($target)) ? "&target=troca&tipo=$tipo" : "";
                
                if (in_array($login_fabrica, [1])) {
                    $keyValidar = trim($xseu_pedido . $new_referencia);
                    if ($arraySql[$keyValidar] != null && $arrayRegistro[$keyValidar] != null && $arraySql[$keyValidar] > $arrayRegistro[$keyValidar]){
			echo "<tr id='linha_$x' class='pintar'>";
		    } else {
                        echo "<tr id='linha_$x'>";
                    }
                }else{
                     echo "<tr id='linha_$x'>";
                }
                
                
                if ($login_fabrica == 1 && $excluir_lote && $nota_fiscal == "") {
                    echo "<td class='tac'><input type='checkbox' name='os_lote[]' value='{$os}'></td>";
                }else if ($login_fabrica == 1 && $excluir_lote && $nota_fiscal != ""){
                    echo "<td class='tac'></td>";
                }
                echo "<td nowrap ><a href='os_press.php?os=$os'  target='_blank'>$sua_os</a></td>";
                echo "<td>".$data_abertura. "</td>";
                echo "<td>".$data_digitacao. "</td>";
                if ($login_fabrica == 1 && $excluir_lote) {
                    echo "<td>".$status_descricao."</td>";
                }
                echo "<td align='left' nowrap title='".$codigo_posto." - ".$posto_nome."'>".$codigo_posto." - ".substr($posto_nome,0,20) ."...</td>";

              
                echo "<td align='left' nowrap><acronym title='Produto: $produto_referencia - ' style='cursor: help'>". $produto_referencia ."</acronym></td>";
                echo "<td align='left' nowrap><acronym title='Produto: $produto_referencia - $produto_descricao' style='cursor: help'>". $produto_descricao ."</acronym></td>";

                if ($login_fabrica == 1 && $excluir_lote) {                    
                    echo "<td align='left' nowrap><acronym title='Produto: $new_referencia - ' style='cursor: help'>". $new_referencia ."</acronym></td>";
                    echo "<td align='left' nowrap><acronym title='Produto: $new_referencia - $new_descricao' style='cursor: help'>". $new_descricao ."</acronym></td>";
                }

                if ($login_fabrica == 1 && $excluir_lote) {
                    echo "<td class='tac'>{$xseu_pedido}</td>";
                    echo "<td class='tac'>{$nota_fiscal}</td>";
                    echo "<td class='tac'>{$data_nf}</td>";
                }
                if (!$excluir_lote) {

                    echo "<td  nowrap><input type='button' class='btn btn-small btn-danger' value='Excluir' onclick=\"window.location='os_excluir_confirmar.php?nova_os=$nova_os&os=$os$excluiTroca'\"></td>";

                }

                echo "</tr>";
            }
        ?>
        </tbody>
        <?php
                if ($login_fabrica == 1 && $excluir_lote) {
                    $selected = ($_POST["select_acao"] == "excluir_os_lote") ? "selected" : "";
                    echo "
                        <tfoot>
                            <tr class='titulo_coluna'>
                                <td colspan='13' bgcolor='#596D9B'>
                                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; <font color='#FFFFFF'><B>COM MARCADOS:</B></font> &nbsp;
                                    <select name='select_acao' size='1'>
                                        <option value='excluir_os_lote' {$selected}>Excluir</option>
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
    <font><b>Quantidade de OS: </b><?=pg_num_rows($res)?></font>
    </form>
<script>                                
$(function(){
        $.dataTableLoad({
                table: "#resultado"
        });
});
</script>
<?php
    }
include "rodape.php"
?>

