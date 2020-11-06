<?php

    include 'dbconfig.php';
    include 'includes/dbconnect-inc.php';

    $admin_privilegios = "call_center";

    include 'autentica_admin.php';

    $msg = "";
    $msg_erro = array();

    /* Action Form */

    function valida_date($data = ""){

        if(strlen($data) > 0){

            list($dia, $mes, $ano) = explode("/", $data);
            return checkdate($mes, $dia, $ano);

        }

    }

    function valida_date_maior($data1 = "", $data2 = ""){

        if(strlen($data1) > 0 && strlen($data2) > 0){

            list($d, $m, $a) = explode("/", $data1);
            $data1 = $a."-".$m."-".$d;

            list($d, $m, $a) = explode("/", $data2);
            $data2 = $a."-".$m."-".$d;

            if(strtotime($data2) < strtotime($data1)){
                return false;
            }

            return true;

        }

    }

    function data_limite($data1, $data2){

        list($dia, $mes, $ano) = explode("/", $data1);
        $data1 = $ano."-".$mes."-".$dia;

        list($dia, $mes, $ano) = explode("/", $data2);
        $data2 = $ano."-".$mes."-".$dia;

        $inicio     = new DateTime($data1);
        $fim        = new DateTime($data2);
        $interval   = date_diff($inicio, $fim);

        $interval = $interval->format('%a');

        return ((int)$interval > 180) ? true : false;

    }

    if(isset($_POST["btn_acao"]) || isset($_POST["gerar_excel"])){

        $data_inicial       = $_POST["data_inicial"];
        $data_final         = $_POST["data_final"];
        $linha              = $_POST["linha"];
        $produto            = $_POST["produto"];
        $produto_referencia = $_POST["produto_referencia"];
        $produto_descricao  = $_POST["produto_descricao"];
        $tipo               = $_POST["tipo"];
        $posto              = $_POST["posto"];
        $codigo_posto       = $_POST["codigo_posto"];
        $descricao_posto    = $_POST["descricao_posto"];
        $status_os          = $_POST["status_os"];
        $dias_aberto        = $_POST["dias_aberto"];
        $peca_os            = $_POST["peca_os"];
        $os_callcenter      = $_POST["os_callcenter"];

        if(!isset($_POST["gerar_excel"])){

            if(empty($data_inicial)){
                $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
                $msg_erro["campos"][]   = "data_inicial";
            }

            if(empty($data_final)){
                $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
                $msg_erro["campos"][]   = "data_final";
            }

            if(!empty($data_inicial)){
                if(valida_date($data_inicial) == false){
                    $msg_erro["msg"][]      = "A Data Inicial é invalida";
                    $msg_erro["campos"][]   = "data_inicial";
                }
            }

            if(!empty($data_final)){
                if(valida_date($data_final) == false){
                    $msg_erro["msg"][]      = "A Data Final é invalida";
                    $msg_erro["campos"][]   = "data_final";
                }
            }

            if(!empty($data_inicial) && !empty($data_final)){

                if(valida_date_maior($data_inicial, $data_final) == false){
                    $msg_erro["msg"][]      = "A Data Inicial é maior que a Data Final";
                    $msg_erro["campos"][]   = "data_inicial";
                }

                if(data_limite($data_inicial, $data_final) == true){
                    $msg_erro["msg"][]      = "O intervalo entre as datas não pode ser maior que 6 meses";
                    $msg_erro["campos"][]   = "data_inicial";
                }

            }

        }

        if(count($msg_erro["msg"]) == 0){

            if(strlen($data_inicial) > 0){
                list($d, $m, $a) = explode("/", $data_inicial);
                $data_inicial_opt = $a."-".$m."-".$d;

                list($d, $m, $a) = explode("/", $data_final);
                $data_final_opt = $a."-".$m."-".$d;

                $cond_data = "AND tbl_os.data_abertura BETWEEN '{$data_inicial_opt}' AND '{$data_final_opt}' ";

            }

            if(strlen($linha)){

                $cond_join_linha = "INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto";
                $cond_linha = "AND tbl_produto.linha = {$linha}";

            }

            if(strlen($produto_referencia) > 0 && strlen($produto_descricao) > 0 && strlen($produto) > 0){

                $cond_produto = "AND tbl_os_produto.produto = {$produto}";

            }

            if(strlen($tipo) > 0){

                $cond_tipo = "AND tbl_os.consumidor_revenda = '{$tipo}'";

            }

            if(strlen($codigo_posto) > 0 && strlen($descricao_posto) > 0 && strlen($posto) > 0){

                $cond_posto = "AND tbl_os.posto = {$posto}";

            }

            if(strlen($status_os) > 0){

                $cond_status_os = "AND tbl_os.status_checkpoint = {$status_os}"; 

            }else{

                $cond_status_os = "AND tbl_os.status_checkpoint IN(1,2,3,4,8) ";

            }

            if($peca_os == "1" || strlen($peca_os) == 0){

                $cond_join_os_item = "INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto";

            }else{

                $cond_os_item = "AND (SELECT COUNT(tbl_os_item.os_item) FROM tbl_os_item WHERE tbl_os_item.os_produto = tbl_os_produto.os_produto) = 0";

            }

            $cond_os_callcenter = ((strlen($os_callcenter) > 0 && $os_callcenter == "sim") ? " INNER" : " LEFT")." JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.os = tbl_os.os ";

            $sql_os = "SELECT 
                            DISTINCT tbl_os.posto,
                            tbl_posto.nome,
                            tbl_posto_fabrica.codigo_posto  
                        FROM tbl_os 
                        INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os 
                        INNER JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto 
                        INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica} 
                        $cond_join_linha 
                        $cond_join_os_item 
                        WHERE 
                            tbl_os.fabrica = {$login_fabrica} 
                            AND tbl_os.finalizada ISNULL 
                            AND tbl_os.data_fechamento ISNULL 
                            AND tbl_os.posto != 6359 
                            AND tbl_os.excluida IS NOT TRUE 
                            $cond_data 
                            $cond_linha 
                            $cond_produto
                            $cond_tipo 
                            $cond_posto 
                            $cond_status_os 
                            $cond_os_item";

            // echo nl2br($sql_os); exit;

            $res_postos_os = pg_query($con, $sql_os);

            $cont_postos_os = pg_num_rows($res_postos_os);

            if(isset($_POST["gerar_excel"])){

                if($cont_postos_os > 0){

                    $file     = "xls/relatorio-gerencial-os-{$login_fabrica}.xls";
                    $fileTemp = "/tmp/relatorio-gerencial-os-{$login_fabrica}.xls" ;
		    $fp       = fopen($fileTemp,'w');

		    if ($login_fabrica == 158) {
			    $col_peca_qtde = "<th><font color='#FFFFFF'>Qtde Peça</font></th>";
		    }

                    $head = "
                        <table border='1'>
                            <thead>
                                <tr bgcolor='#596D9B'>
                                    <th><font color='#FFFFFF'>OS</font></th>
                                    <th><font color='#FFFFFF'>Linha</font></th>
                                    <th><font color='#FFFFFF'>Código Produto</font></th>
                                    <th><font color='#FFFFFF'>Referência Produto</font></th>
                                    <th><font color='#FFFFFF'>Código Posto</font></th>
                                    <th><font color='#FFFFFF'>Nome Posto</font></th>
                                    <th><font color='#FFFFFF'>Data Abertura</font></th>
                                    <th><font color='#FFFFFF'>C/R</font></th>
				    <th><font color='#FFFFFF'>Peça</font></th>
		    		    {$col_peca_qtde}
                                    <th><font color='#FFFFFF'>Dias em Aberto</font></th>
                                    <th><font color='#FFFFFF'>Intervalo de Dias</font></th>
                                    <th><font color='#FFFFFF'>Status</font></th>
                                    <th><font color='#FFFFFF'>OS de Callcenter</font></th>
                                </tr>
                            </thead>
                            <tbody>
                    ";

                    fwrite($fp, $head);

                    for($i = 0; $i < $cont_postos_os; $i++){

                        $posto        = pg_fetch_result($res_postos_os, $i, "posto");
                        $codigo_posto = pg_fetch_result($res_postos_os, $i, "codigo_posto");
                        $nome_posto   = pg_fetch_result($res_postos_os, $i, "nome");

                        $cond_posto = "AND tbl_os.posto = {$posto}";

                        if($peca_os == "1" || strlen($peca_os) == 0){
                            $campos_pecas = ",tbl_peca.peca, tbl_peca.referencia AS peca_referencia, tbl_os_item.qtde, tbl_peca.descricao AS peca_descricao"; 
                            $cond_join_peca = "INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca";
                        }

                        $sql_os_postos = "SELECT DISTINCT 
                                            tbl_os.os,
                                            tbl_os.data_abertura,
                                            tbl_os.consumidor_revenda,
                                            current_date - tbl_os.data_abertura As qtde_dias_abertos,
                                            tbl_os.produto,
                                            tbl_produto.referencia AS produto_referencia,
                                            tbl_produto.descricao AS produto_descricao,
                                            tbl_linha.nome AS linha,
                                            tbl_os.status_checkpoint,
                                            tbl_status_checkpoint.descricao AS status,
                                            tbl_hd_chamado_extra.hd_chamado 
                                            $campos_pecas 
                                        FROM tbl_os 
                                        INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os 
                                        INNER JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto 
                                        INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica} 
                                        INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto 
                                        INNER JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica}  
                                        INNER JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint 
                                        $cond_join_linha 
                                        $cond_join_os_item 
                                        $cond_join_peca
                                        $cond_os_callcenter
                                        WHERE 
                                            tbl_os.fabrica = {$login_fabrica} 
                                            AND tbl_os.finalizada ISNULL 
                                            AND tbl_os.data_fechamento ISNULL 
                                            and tbl_os.excluida IS NOT TRUE 
                                            $cond_data 
                                            $cond_linha 
                                            $cond_produto
                                            $cond_tipo 
                                            $cond_posto 
                                            $cond_status_os 
                                            $cond_os_item";
                        $res_os_postos = pg_query($con, $sql_os_postos);
                        $cont_os_postos = pg_num_rows($res_os_postos);

                        for($j = 0; $j < $cont_os_postos; $j++){

                            $os                 = pg_fetch_result($res_os_postos, $j, "os");
                            $data_abertura      = pg_fetch_result($res_os_postos, $j, "data_abertura");
                            $consumidor_revenda = pg_fetch_result($res_os_postos, $j, "consumidor_revenda");
                            $qtde_dias_abertos  = pg_fetch_result($res_os_postos, $j, "qtde_dias_abertos");
                            $produto            = pg_fetch_result($res_os_postos, $j, "produto");
                            $produto_referencia = pg_fetch_result($res_os_postos, $j, "produto_referencia");
                            $produto_descricao  = pg_fetch_result($res_os_postos, $j, "produto_descricao");
                            $linha              = pg_fetch_result($res_os_postos, $j, "linha");
                            $status             = pg_fetch_result($res_os_postos, $j, "status");
                            $peca               = pg_fetch_result($res_os_postos, $j, "peca");
                            $peca_referencia    = pg_fetch_result($res_os_postos, $j, "peca_referencia");
                            $peca_descricao     = pg_fetch_result($res_os_postos, $j, "peca_descricao");
			    $hd_chamado         = pg_fetch_result($res_os_postos, $j, "hd_chamado");

			    if ($login_fabrica == 158) {
				    $peca_qtde = pg_fetch_result($res_os_postos, $j, "qtde");
			    }

                            list($ano, $mes, $dia) = explode("-", $data_abertura);
                            $data_abertura = $dia."/".$mes."/".$ano;

                            if(strlen($peca_referencia) > 0){
                                $desc_peca = $peca_referencia." - ".$peca_descricao;
                            }

                            if($qtde_dias_abertos <= 10){ // Até 10 dias
                                $intervalo_dias = "0-10";
                            }

                            if($qtde_dias_abertos > 10 && $qtde_dias_abertos <= 20){ // De 11 a 20 dias
                                $intervalo_dias = "11-20";
                            }

                            if($qtde_dias_abertos > 20 && $qtde_dias_abertos <= 30){ // Mais que 20 dias
                                $intervalo_dias = "> 20";
                            }

                            if($qtde_dias_abertos > 30 && $qtde_dias_abertos <= 90){ // Mais que 30 dias
                                $intervalo_dias = "> 30";
                            }

                            if($qtde_dias_abertos > 90){ // Mais que 90 dias
                                $intervalo_dias = "> 90";
			    }

			    if ($login_fabrica == 158) {
				    $col_res_peca_qtde = "<td>{$peca_qtde}</td>";
			    }

                            $body = "
                                <tr>
                                    <td>{$os}</td>
                                    <td>{$linha}</td> 
                                    <td>{$produto_referencia}</td> 
                                    <td>{$produto_descricao}</td>
                                    <td>{$codigo_posto}</td>
                                    <td>{$nome_posto}</td>
                                    <td>{$data_abertura}</td>
                                    <td>{$consumidor_revenda}</td>
				    <td>{$desc_peca}</td>
			    	    {$col_res_peca_qtde}
                                    <td>{$qtde_dias_abertos}</td>
                                    <td>{$intervalo_dias}</td>
                                    <td>{$status}</td>
                                    <td>{$hd_chamado}</td>
                                </tr>
                            ";

                            fwrite($fp, $body);

                        }

                    }

                    fwrite($fp, "</tbody></table>");

                    fclose($fp);

                    if(file_exists($fileTemp)){
                        system("mv $fileTemp $file");

                        if(file_exists($file)){
                            echo $file;
                        }
                    }

                    exit;


                }

            }

        }

    }

    /* Fim Action Form */

    $title = "RELATÓRIO GERENCIAL DE OS";
    $layout_menu = "gerencia";

    include 'funcoes.php';

    include 'cabecalho_new.php';

    $plugins = array(
        "autocomplete",
        "datepicker",
        "shadowbox",
        "mask",
        "dataTable"
    );

    include("plugin_loader.php");

?>

        <script type="text/javascript" charset="utf-8">
            $(function() {

                Shadowbox.init();

                $.datepickerLoad(["data_inicial", "data_final"]);

                $("#data_inicial").datepicker().mask('99/99/9999');
                $("#data_final").datepicker().mask('99/99/9999');

                $(document).on("click", "span[rel=lupa]", function () {
                    $.lupa($(this),Array('posicao'));
                });

            });

            function retorna_posto(retorno){
                $("#posto").val(retorno.posto);
                $("#codigo_posto").val(retorno.codigo);
                $("#descricao_posto").val(retorno.nome);
            }

            function retorna_produto (retorno) {
                $("#produto").val(retorno.produto);
                $("#produto_referencia").val(retorno.referencia);
                $("#produto_descricao").val(retorno.descricao);
            }

            function detalhes(posto){

                var table_hidden = $("#detalhe_"+posto).html();
                console.log(table_hidden);

                 Shadowbox.open({
                    content: table_hidden,
                    player: 'html',
                    height: 600,
                    width: 1000
                });

            }

        </script>

        <?php
        if ((count($msg_erro["msg"]) > 0) ) {
        ?>
            <div class="alert alert-error">
                <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
            </div>
        <?php
        }
        ?>

        <div class="row">
            <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
        </div>

        <form name='frm_pesquisa' method='post' action='<?php echo $_SERVER["PHP_SELF"]; ?>' class="form-search form-inline tc_formulario">
        
            <div class="titulo_tabela"><?php echo $title; ?></div>

            <br />

            <div class='row-fluid'>
                <div class='span1'></div>
                <div class="span2">
                    <div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for="data_inicial">Data Inicial</label>
                        <div class='controls controls-row'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span11' value= "<?=$data_inicial?>">
                        </div>
                    </div>
                </div>
                <div class="span2">
                    <div class='control-group <?=(in_array("data_final", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for="data_final">Data Final</label>
                        <div class='controls controls-row'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span11' value= "<?=$data_final?>">
                        </div>
                    </div>
                </div>
                <div class="span6">
                    <div class='control-group'>
                        <label class='control-label' for="linha">Linha</label>
                        <div class='controls controls-row'>
                            <select name='linha' id='linha' class='span12'>
                                <option value=""></option>
                                <?php

                                $sql_linha = "SELECT linha, codigo_linha, nome FROM tbl_linha WHERE fabrica = {$login_fabrica} AND ativo = 't' ORDER BY nome ASC";
                                $res_linha = pg_query($con, $sql_linha);

                                for($i = 0; $i < pg_num_rows($res_linha); $i++){

                                    $linha_sql = pg_fetch_result($res_linha, $i, "linha");
                                    $codigo_sql = pg_fetch_result($res_linha, $i, "codigo_linha");
                                    $nome_sql = pg_fetch_result($res_linha, $i, "nome");

                                    $selected = ($linha == $linha_sql) ? "SELECTED" : "";

                                    echo "<option value='".$linha_sql."' $selected >".$codigo_sql  ." - ".$nome_sql."</option>";

                                }

                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class='row-fluid'>
                <div class='span1'></div>
                <div class='span3'>
                    <input type="hidden" name="produto" id="produto" value="<?php echo $produto; ?>">
                    <div class='control-group'>
                        <label class='control-label' for='produto_referencia'>Ref. Produto</label>
                        <div class='controls controls-row'>
                            <div class='span10 input-append'>
                                <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
                                <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                                <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span5'>
                    <div class='control-group'>
                        <label class='control-label' for='produto_descricao'>Descrição Produto</label>
                        <div class='controls controls-row'>
                            <div class='span10 input-append'>
                                <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
                                <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                                <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span2">
                    <div class="control-group">
                        <label class='control-label' for="tipo">Tipo</label>
                        <div class='controls controls-row'>
                            <select name='tipo' id='tipo' class='span12'>
                                <option value=""></option>
                                <option value="C" <?php echo ($tipo == "C") ? "SELECTED" : ""; ?>>Consumidor</option>
                                <option value="R" <?php echo ($tipo == "R") ? "SELECTED" : ""; ?>>Revenda</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row-fluid">
                <div class="span1"></div>
                <div class="span2">
                    <input type="hidden" name="posto" id="posto" value="<?php echo $posto; ?>">
                    <div class='control-group'>
                        <label class='control-label' for='codigo_posto'>Código Posto</label>
                        <div class='controls controls-row'>
                            <div class='span10 input-append'>
                                <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<?php echo $codigo_posto ?>" >
                                <span class='add-on' rel="lupa">
                                    <i class='icon-search' ></i>
                                    <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                                </span>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="span5">
                    <div class='control-group'>
                        <label class='control-label' for='descricao_posto'>Razão Social</label>
                        <div class='controls controls-row'>
                            <div class='span11 input-append'>
                                <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<?php echo $descricao_posto ?>" >
                                <span class='add-on' rel="lupa">
                                    <i class='icon-search' ></i>
                                    <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span3">
                    <div class='control-group'>
                        <label class='control-label' for="status_os">Status da OS</label>
                        <div class='controls controls-row'>
                            <select name='status_os' id='status_os' class='span12'>
                                <option value=""></option>
                                <?php

                                $sql_status = "SELECT status_checkpoint, descricao FROM tbl_status_checkpoint WHERE status_checkpoint IN(1,2,3,4,8) ORDER BY descricao ASC";
                                $res_status = pg_query($con, $sql_status);

                                for($i = 0; $i < pg_num_rows($res_status); $i++){

                                    $status = pg_fetch_result($res_status, $i, "status_checkpoint");
                                    $descricao = pg_fetch_result($res_status, $i, "descricao");

                                    $selected = ($status_os == $status) ? "SELECTED" : "";

                                    echo "<option value='".$status."' $selected >".$descricao  ."</option>";

                                }

                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row-fluid">
                <div class="span1"></div>
                <div class="span3">
                    <div class="control-group">
                        <label class='control-label' for="dias_aberto">Qtde Dias em Aberto</label>
                        <div class='controls controls-row'>
                            <select name='dias_aberto' id='dias_aberto' class='span12'>
                                <option value=""></option>
                                <option value="0-10"  <?php echo ($dias_aberto == "0-10") ? "SELECTED" : ""; ?>> 0-10</option>
                                <option value="11-20" <?php echo ($dias_aberto == "11-20") ? "SELECTED" : ""; ?>> 11-20</option>
                                <option value="> 20"  <?php echo ($dias_aberto == "> 20") ? "SELECTED" : ""; ?>> > 20</option>
                                <option value="> 30"  <?php echo ($dias_aberto == "> 30") ? "SELECTED" : ""; ?>> > 30</option>
                                <option value="> 90"  <?php echo ($dias_aberto == "> 90") ? "SELECTED" : ""; ?>> > 90</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="span3">
                    <div class="control-group">
                        <label class='control-label' for="peca_os">Peças na OS</label>
                        <div class='controls controls-row'>
                            <select name='peca_os' id='peca_os' class='span12'>
                                <option value=""></option>
                                <option value="1" <?php echo ($peca_os == "1") ? "SELECTED" : ""; ?>> OS com peça</option>
                                <option value="2" <?php echo ($peca_os == "2") ? "SELECTED" : ""; ?>> OS sem peça</option>
                            </select>
                        </div>
                    </div>
                </div>
                 <div class="span2">
                    <div class="control-group">
                        <label class='control-label' for="os_callcenter">OS de Callcenter</label>
                        <div class='controls controls-row'>
                            <select name='os_callcenter' id='os_callcenter' class='span12'>
                                <option value=""></option>
                                <option value="sim" <?php echo ($os_callcenter == "sim") ? "SELECTED" : ""; ?>> Sim</option>
                                <option value="nao" <?php echo ($os_callcenter == "nao") ? "SELECTED" : ""; ?>> Não</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <p>
                <br/>
                <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
                <input type='hidden' id="btn_click" name='btn_acao' value='' />
            </p>

            <br />

        </form>

    </div>

    <?php

    if(isset($cont_postos_os)){

        if($cont_postos_os > 0){

            ?>

            <br />

            <div class="tac">
                <a href="dashboard_fabrica.php?data_inicial=<?php echo $data_inicial_opt ?>&data_final=<?php echo $data_final_opt; ?>" target="_blank" class="btn btn-primary">Dashboard da fabrica</a>
            </div>

            <br />

            <div class="container" style="width: 1000px;">
                <table id="relatorio" class='table table-striped table-bordered table-hover' style="width: 100%;">
                    <thead>
                        <tr class="titulo_tabela">
                            <th colspan="7">Relação Gerencial de OSs</th>
                        </tr>
                        <tr class="titulo_coluna">
                            <th rowspan="2">Postos</th>
                            <th colspan="6">Pendentes (dias)</th>
                        </tr>
                        <tr class="titulo_coluna">
                            <th>0-10</th>
                            <th>11-20</th>
                            <th>> 20</th>
                            <th>> 30</th>
                            <th>> 90</th>
                            <th>Total OS</th>
                        </tr>
                    </thead> 
                    <tbody>

            <?php

                    $cont_0_10_geral = 0;
                    $cont_0_20_geral = 0;
                    $cont_20_geral   = 0;
                    $cont_30_geral   = 0;
                    $cont_90_geral   = 0;

                    $cont_total_geral = 0;
                    $table_hidden = "";

                    for($i = 0; $i < $cont_postos_os; $i++){

                        $posto        = pg_fetch_result($res_postos_os, $i, "posto");
                        $codigo_posto = pg_fetch_result($res_postos_os, $i, "codigo_posto");
                        $nome_posto   = pg_fetch_result($res_postos_os, $i, "nome");

                        $cond_posto = "AND tbl_os.posto = {$posto}";

                        if($peca_os == "1" || strlen($peca_os) == 0){
                            $campos_pecas = ",tbl_peca.peca, tbl_peca.referencia AS peca_referencia, tbl_os_item.qtde, tbl_peca.descricao AS peca_descricao"; 
                            $cond_join_peca = "INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca";
                        }

                        $sql_os_postos = "SELECT DISTINCT 
                                            tbl_os.os,
                                            tbl_os.data_abertura,
                                            tbl_os.consumidor_revenda,
                                            current_date - tbl_os.data_abertura As qtde_dias_abertos,
                                            tbl_os.produto,
                                            tbl_produto.referencia AS produto_referencia,
                                            tbl_produto.descricao AS produto_descricao,
                                            tbl_os.status_checkpoint,
                                            tbl_status_checkpoint.descricao AS status,
                                            tbl_hd_chamado_extra.hd_chamado 
                                            $campos_pecas 
                                        FROM tbl_os 
                                        INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os 
                                        INNER JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto 
                                        INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica} 
                                        INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto 
                                        INNER JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint 
                                        $cond_join_linha 
                                        $cond_join_os_item 
                                        $cond_join_peca 
                                        $cond_os_callcenter
                                        WHERE 
                                            tbl_os.fabrica = {$login_fabrica} 
                                            AND tbl_os.finalizada ISNULL 
                                            AND tbl_os.data_fechamento ISNULL 
                                            AND tbl_os.excluida IS NOT TRUE 
                                            $cond_data 
                                            $cond_linha 
                                            $cond_produto
                                            $cond_tipo 
                                            $cond_posto 
                                            $cond_status_os 
                                            $cond_os_item";
                            $res_os_postos = pg_query($con, $sql_os_postos);
                            $cont_os_postos = pg_num_rows($res_os_postos);

                           //  echo nl2br($sql_os_postos); exit;

                            if($cont_os_postos > 0){

                                echo "
                                    <tr>
                                        <td><a href='javascript:detalhes($posto);'>{$codigo_posto} - {$nome_posto}</a></td>
                                ";

                                    $cont_0_10  = 0;
                                    $cont_11_20 = 0;
                                    $cont_20    = 0;
                                    $cont_30    = 0;
                                    $cont_90    = 0;

                                    $cont_total = 0;

				    $os_arr     = array();

				    if ($login_fabrica == 158) {
					    $col_peca_qtde = "<th>Qtde Peça</th>";
				    }

                                    $table_hidden .= "
                                    <div id='detalhe_{$posto}' style='display: none;'>
                                        <div style='height: 580px; overflow-x: auto !important; padding: 10px;'>
                                            <div style='padding: 5px; text-align: center;'>
                                                <strong>Relação de OS do posto $codigo_posto - $nome_posto</strong>
                                            </div>
                                            <table class='table table-bordered table-striped'>
                                                <thead>
                                                    <tr>
                                                        <th>OS</th>
                                                        <th>Produto</th>
                                                        <th>Posto</th>
                                                        <th>Data de Abertura</th>
                                                        <th>C/R</th>
							<th>Peça</th>
				    			{$col_peca_qtde}
                                                        <th>Dias Aberto</th>
                                                        <th>Status</th>
                                                        <th>OS de Callcenter</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                        ";

                                    for($j = 0; $j < $cont_os_postos; $j++){

                                        $os                 = pg_fetch_result($res_os_postos, $j, "os");
                                        $data_abertura      = pg_fetch_result($res_os_postos, $j, "data_abertura");
                                        $consumidor_revenda = pg_fetch_result($res_os_postos, $j, "consumidor_revenda");
                                        $qtde_dias_abertos  = pg_fetch_result($res_os_postos, $j, "qtde_dias_abertos");
                                        $produto            = pg_fetch_result($res_os_postos, $j, "produto");
                                        $produto_referencia = pg_fetch_result($res_os_postos, $j, "produto_referencia");
                                        $produto_descricao  = pg_fetch_result($res_os_postos, $j, "produto_descricao");
                                        $status             = pg_fetch_result($res_os_postos, $j, "status");
                                        $peca               = pg_fetch_result($res_os_postos, $j, "peca");
                                        $peca_referencia    = pg_fetch_result($res_os_postos, $j, "peca_referencia");
                                        $peca_descricao     = pg_fetch_result($res_os_postos, $j, "peca_descricao");
					$hd_chamado         = pg_fetch_result($res_os_postos, $j, "hd_chamado");

					if ($login_fabrica == 158) {
						$peca_qtde = pg_fetch_result($res_os_postos, $j, "qtde");
					}

                                        if(!in_array($os, $os_arr)){

                                            $os_arr[] = $os;

                                            if($qtde_dias_abertos <= 10){ // Até 10 dias
                                                if(strlen($dias_aberto) == 0 || (strlen($dias_aberto) > 0 && $dias_aberto == "0-10")){
                                                    $cont_0_10++;
                                                }
                                            }

                                            if($qtde_dias_abertos > 10 && $qtde_dias_abertos <= 20){ // De 11 a 20 dias
                                                if(strlen($dias_aberto) == 0 || (strlen($dias_aberto) > 0 && $dias_aberto == "11-20")){
                                                    $cont_11_20++;
                                                }
                                            }

                                            if($qtde_dias_abertos > 20 && $qtde_dias_abertos <= 30){ // Mais que 20 dias
                                                if(strlen($dias_aberto) == 0 || (strlen($dias_aberto) > 0 && $dias_aberto == "> 20")){
                                                    $cont_20++;
                                                }
                                            }

                                            if($qtde_dias_abertos > 30 && $qtde_dias_abertos <= 90){ // Mais que 30 dias
                                                if(strlen($dias_aberto) == 0 || (strlen($dias_aberto) > 0 && $dias_aberto == "> 30")){
                                                    $cont_30++;
                                                }
                                            }

                                            if($qtde_dias_abertos > 90){ // Mais que 90 dias
                                                if(strlen($dias_aberto) == 0 || (strlen($dias_aberto) > 0 && $dias_aberto == "> 90")){
                                                    $cont_90++;
                                                }
                                            }

                                        }

                                        list($ano, $mes, $dia) = explode("-", $data_abertura);
					$data_abertura = $dia."/".$mes."/".$ano;

					if ($login_fabrica == 158) {
						$col_res_peca_qtde = "<td>{$peca_qtde}</td>";
					}

                                        $table_hidden .= "
                                            <tr>
                                                <td><a href='os_press.php?os={$os}' target='_blank'>{$os}</a></td>
                                                <td>{$produto_referencia} - {$produto_descricao}</td>
                                                <td>{$codigo_posto} - {$nome_posto}</td>
                                                <td>{$data_abertura}</td>
                                                <td>{$consumidor_revenda}</td>
						<td>{$peca_referencia} - {$peca_descricao}</td>
						{$col_res_peca_qtde}
                                                <td>{$qtde_dias_abertos} dias</td>
                                                <td>{$status}</td>
                                                <td class='tac'><a href='callcenter_interativo_new.php?callcenter={$hd_chamado}' target='_blank'>{$hd_chamado}</a></td>
                                            </tr>
                                        ";

                                    }

                                    $table_hidden .= "
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    ";

                                    $cont_total = $cont_0_10 + $cont_11_20 + $cont_20 + $cont_30 + $cont_90;

                                    $cont_total_geral += $cont_total;

                                    echo "
                                        <td class='tac'>{$cont_0_10}</td>
                                        <td class='tac'>{$cont_11_20}</td>
                                        <td class='tac'>{$cont_20}</td>
                                        <td class='tac'>{$cont_30}</td>
                                        <td class='tac'>{$cont_90}</td>
                                        <td class='tac'>
                                            <strong>{$cont_total}</strong>
                                        </td>
                                    ";

                                    $cont_0_10_geral  += $cont_0_10;
                                    $cont_11_20_geral += $cont_11_20;
                                    $cont_20_geral    += $cont_20;
                                    $cont_30_geral    += $cont_30;
                                    $cont_90_geral    += $cont_90;

                                echo "</tr>";

                            }

                    }

            ?>
                    </tbody>
                    <tfoot>
                        <tr class="titulo_tabela">
                            <td colspan="1" style="text-align: right;">
                                <strong>Total</strong>
                            </td>
                            <td class="tac"><strong><?php echo $cont_0_10_geral; ?></strong></td>
                            <td class="tac"><strong><?php echo $cont_11_20_geral; ?></strong></td>
                            <td class="tac"><strong><?php echo $cont_20_geral; ?></strong></td>
                            <td class="tac"><strong><?php echo $cont_30_geral; ?></strong></td>
                            <td class="tac"><strong><?php echo $cont_90_geral; ?></strong></td>
                            <td class="tac"><strong><?php echo $cont_total_geral; ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <br /> <br />

            <?php

            echo $table_hidden;

            $arr_excel = array(
                "data_inicial"       => $_POST["data_inicial"],
                "data_final"         => $_POST["data_final"],
                "linha"              => $_POST["linha"],
                "produto"            => $_POST["produto"],
                "produto_referencia" => $_POST["produto_referencia"],
                "produto_descricao"  => $_POST["produto_descricao"],
                "tipo"               => $_POST["tipo"],
                "posto"              => $_POST["posto"],
                "codigo_posto"       => $_POST["codigo_posto"],
                "descricao_posto"    => $_POST["descricao_posto"],
                "status_os"          => $_POST["status_os"],
                "dias_aberto"        => $_POST["dias_aberto"],
                "peca_os"            => $_POST["peca_os"],
                "os_callcenter"      => $_POST["os_callcenter"]
            );

            ?>

            <div id='gerar_excel' class="btn_excel">
                <input type="hidden" id="jsonPOST" value='<?=json_encode($arr_excel)?>' />
                <span><img src='imagens/excel.png' /></span>
                <span class="txt">Gerar Arquivo Excel</span>
            </div>

            <?php if($cont_postos_os > 0) { ?>
            <script>
                $.dataTableLoad({
                    table : "#relatorio"
                });
            </script>
            <?php }?>

        <?php

        }else{
            echo "<br /> <div class='container'><div class='alert alert-warning tav'> <h4>Nenhum resultado encontrado</h4> </div> </div> <br />";
        }

    }

    ?>

<br /> <br />

<?php include "rodape.php"; ?>
