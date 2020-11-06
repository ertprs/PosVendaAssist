<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';


    if ($_POST["btn_acao"] == "submit") {
        $data_inicial       = $_POST['data_inicial'];
        $data_final         = $_POST['data_final'];
        $produto_referencia = $_POST['produto_referencia'];
        $produto_descricao  = $_POST['produto_descricao'];   
        $data_filtro        = $_POST['data_filtro'];       
        $nome_posto         = $_POST['descricao_posto'];    
        $codigo_posto       = $_POST['codigo_posto'];   
        $os_pesquisa        = $_POST['os'] ;
        $op_faturamento        = $_POST['faturamento'] ;

        if($op_faturamento == 'com'){
            $join = " INNER ";
            $condComFaturamento = "";
        }else{
            $join = " LEFT ";
            $condComFaturamento = " AND tbl_faturamento.faturamento is null ";
        }

        if(strlen($data_inicial)>0 and $data_inicial <> "dd/mm/aaaa"){
            $xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
            $xdata_inicial = str_replace("'","",$xdata_inicial);
        }else{
            $msg_erro["msg"][]    ="Data Inicial Inválida";
            $msg_erro["campos"][] = "data";
        }

        if(strlen($data_final)>0 and $data_final <> "dd/mm/aaaa"){
            $xdata_final =  fnc_formata_data_pg(trim($data_final));
            $xdata_final = str_replace("'","",$xdata_final);
        }else{
             $msg_erro["msg"][]    ="Data Final Inválida";
            $msg_erro["campos"][] = "data";
        }

        if(!count($msg_erro["msg"])){
            $dat = explode ("/", $data_inicial );//tira a barra
            $d = $dat[0];
            $m = $dat[1];
            $y = $dat[2];

            if(!checkdate($m,$d,$y)){
                $msg_erro["msg"][]    ="Data Inválida";
                $msg_erro["campos"][] = "data";
            }
        }
        if(!count($msg_erro["msg"])){
            $dat = explode ("/", $data_final );//tira a barra
            $d = $dat[0];
            $m = $dat[1];
            $y = $dat[2];
            if(!checkdate($m,$d,$y)){
                $msg_erro["msg"][]    ="Data Inválida";
                $msg_erro["campos"][] = "data";
            }
        }

        if($xdata_inicial > $xdata_final) {
            $msg_erro["msg"][]    ="Data Inicial maior que final";
            $msg_erro["campos"][] = "data";
        }


        ## VERIFICANDO INTERVALO MENSAL
        if(count($msg_erro["msg"]) == 0  ) {
            $sql_data = " SELECT '$xdata_inicial'::date < '$xdata_final'::date + INTERVAL '-6 months' ";
            if (pg_fetch_result(pg_query($con, $sql_data), 0) == 't') {
                $msg_erro["msg"][]    = "O intervalo entre as datas não pode ser maior que 6 meses.";
                $msg_erro["campos"][] = "data";
            }
        }


        if(!count($msg_erro["msg"])){

            if($data_filtro == 'abertura'){
                $sql_data = " AND tbl_os.data_abertura between  '$xdata_inicial 00:00:00' AND  '$xdata_final 23:59:59' ";
            }elseif($data_filtro == 'digitacao'){
                $sql_data = " AND tbl_os.data_digitacao between  '$xdata_inicial 00:00:00' AND  '$xdata_final 23:59:59' ";
            }elseif($data_filtro == 'fechamento'){
                $sql_data = " AND tbl_os.data_fechamento between  '$xdata_inicial 00:00:00' AND  '$xdata_final 23:59:59' ";
            }else{
                $sql_data = " AND tbl_os.data_abertura between  '$xdata_inicial 00:00:00' AND  '$xdata_final 23:59:59' ";
            }

            if(strlen(trim($os))>0){
                $sql_os = " AND tbl_os.os = $os_pesquisa ";
            }


            if(strlen(trim($codigo_posto))>0 ){
                $cond_posto  = " AND tbl_posto_fabrica.codigo_posto = '$codigo_posto' AND tbl_posto_fabrica.posto = tbl_os.posto ";
            }

            if(strlen(trim($produto_referencia))> 0 ){
                $cond_produto = " AND tbl_produto.referencia = '$produto_referencia' ";
            }

            $sql = "SELECT distinct tbl_os.os,
                    tbl_os_item.os_item,
                    tbl_os_item.digitacao_item,
                    tbl_embarque_item.embarcado, 
                    tbl_embarque_item.embarque_item, 
                    tbl_embarque_item.embarque, 
                    tbl_os.data_digitacao, 
                    tbl_os.data_abertura, 
                    tbl_os.data_conserto, 
                    tbl_os.data_fechamento, 
                    tbl_os.data_nf, 
                    tbl_produto.referencia, 
                    tbl_produto.descricao, 
                    tbl_posto_fabrica.codigo_posto, 
                    tbl_posto.nome, 
                    tbl_faturamento_item.faturamento, 
                    tbl_etiqueta_servico.etiqueta,
                    tbl_peca.referencia as referencia_peca,
                    tbl_peca.descricao as descricao_peca,
                    tbl_faturamento.emissao,
                    tbl_os_item.digitacao_item::date - tbl_os.data_abertura AS tempo_lancamento_peca,
                    tbl_embarque_item.embarcado::date - tbl_os_item.digitacao_item::date AS tempo_embarque,
                    tbl_faturamento_correio.data::date - tbl_embarque_item.embarcado::date AS prazo_recebimento,
                    tbl_os.data_conserto::date - tbl_faturamento_correio.data::date AS tempo_conserto,
                    tbl_os.data_digitacao::date - tbl_os.data_abertura AS qtde_dias_digitacao,
                    tbl_pedido.data AS data_pedido,
                    tbl_pedido.data::date - tbl_os_item.digitacao_item::date AS qtde_dias_gerar_pedido,
                    tbl_os.data_fechamento - tbl_os.data_conserto::date AS qtde_dias_finalizar,
                    tbl_os.data_fechamento - tbl_os.data_abertura AS qtde_dias_total,
                    tbl_posto_fabrica.contato_estado,
		    tbl_pedido_item.pedido,
		    tbl_faturamento_correio.data AS data_recebimento
            FROM tbl_os
            INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto and tbl_produto.fabrica_i = $login_fabrica
            INNER join tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            INNER JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
            INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
            INNER JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto 
            INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
            $join JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_os_item.pedido and tbl_pedido_item.peca = tbl_os_item.peca
            $join JOIN tbl_pedido ON tbl_pedido_item.pedido = tbl_pedido.pedido
            $join JOIN tbl_embarque_item on tbl_embarque_item.pedido_item = tbl_pedido_item.pedido_item AND tbl_embarque_item.os_item = tbl_os_item.os_item
            $join JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_os_item.pedido AND tbl_faturamento_item.os_item = tbl_os_item.os_item
            $join JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
            $join JOIN tbl_etiqueta_servico ON tbl_etiqueta_servico.embarque = tbl_embarque_item.embarque
            $join JOIN tbl_faturamento_correio ON tbl_faturamento.faturamento = tbl_faturamento_correio.faturamento AND tbl_faturamento_correio.situacao ~* 'entregue'
            WHERE tbl_os.fabrica = $login_fabrica 
            $sql_data
            $cond_posto
            $cond_produto
            $sql_os 
            $condComFaturamento
            ORDER BY tbl_os.os ";
        /*
        DU979198077BR
        os 45549043
        */
            $res = pg_query($con, $sql);
            echo pg_last_error($con);
            if(isset($_POST['gerar_excel'])){
                
                $data = date("d-m-Y-H:i");                
                $filename = "relatorio-rastreamento-{$data}.csv";
                $file = fopen("/tmp/{$filename}", "w");

                $thead = "OS;Data Abertura;".utf8_encode("Data Digitação").";".utf8_encode("Qtde dias Digitação").";Data NF;Posto;UF;Produto;".utf8_encode("Peça").";".utf8_encode("Lançamento Peças").";".utf8_encode("Qtde dias Digitação Item").";Pedido;Data Pedido;Qtde dias Pedido;Embarque;Qtde dias Embarque;".utf8_encode("Expedição").";Recebimento;Qtde dias Recebimento;Conserto;Qtde dias Conserto;Finalizado;Qtde dias Finalizar;Qtde dias Total\r\n";
                fwrite($file, $thead);              
            }

            $conteudo = "";
            $qtde = pg_num_rows($res);
            for($i=0; $i<pg_num_rows($res); $i++) { 
                $os                     = pg_fetch_result($res, $i, 'os');
                $data_digitacao         = mostra_data(substr(pg_fetch_result($res, $i, data_digitacao),0 ,10));
                $digitacao_item         = mostra_data(substr(pg_fetch_result($res, $i, digitacao_item),0 ,16));
                $data_nf                = mostra_data(substr(pg_fetch_result($res, $i, data_nf),0,10));
                $referencia             = pg_fetch_result($res, $i, referencia);
                $descricao              = substr(pg_fetch_result($res, $i, descricao),0,25);
                $codigo                 = pg_fetch_result($res, $i, codigo_posto);
                $nome_posto             = substr(pg_fetch_result($res, $i, nome),0,25);
                $embarcado              = mostra_data(substr(pg_fetch_result($res, $i, embarcado),0 ,10));
                $emissao                = mostra_data(substr(pg_fetch_result($res, $i, emissao),0 ,10));
                $etiqueta               = pg_fetch_result($res, $i, etiqueta);
                $referencia_peca        = pg_fetch_result($res, $i, referencia_peca);
                $descricao_peca         = pg_fetch_result($res, $i, descricao_peca);
                $tempo_lancamento_peca  = pg_fetch_result($res, $i, tempo_lancamento_peca);
                $tempo_embarque         = pg_fetch_result($res, $i, tempo_embarque);
                $data_abertura          = mostra_data(substr(pg_fetch_result($res, $i, data_abertura),0 ,10));
                $prazo_recebimento      = pg_fetch_result($res, $i, prazo_recebimento);
                $tempo_conserto         = pg_fetch_result($res, $i, tempo_conserto);
                $qtde_dias_digitacao    = pg_fetch_result($res, $i, qtde_dias_digitacao);
                $data_pedido            = mostra_data(substr(pg_fetch_result($res, $i, data_pedido),0 ,10));
                $data_conserto          = mostra_data(substr(pg_fetch_result($res, $i, data_conserto),0 ,10));
		$data_fechamento        = mostra_data(substr(pg_fetch_result($res, $i, data_fechamento),0 ,10));
		$data_recebimento       = mostra_data(substr(pg_fetch_result($res, $i, data_recebimento),0 ,10));
                $qtde_dias_gerar_pedido = pg_fetch_result($res, $i, qtde_dias_gerar_pedido);
                $qtde_dias_finalizar    = pg_fetch_result($res, $i, qtde_dias_finalizar);
                $qtde_dias_total        = pg_fetch_result($res, $i, qtde_dias_total);
                $uf                     = pg_fetch_result($res, $i, contato_estado);
                $pedido                 = pg_fetch_result($res, $i, pedido);

                if(isset($_POST['gerar_excel'])){
                    $tbody .= "$os;$data_abertura;$data_digitacao;$qtde_dias_digitacao;$data_nf;".utf8_encode($nome_posto).";$uf;$referencia - $descricao;$referencia_peca - ".utf8_encode($descricao_peca).";$digitacao_item;$tempo_lancamento_peca;$pedido;$data_pedido;$qtde_dias_gerar_pedido;$embarcado;$tempo_embarque;$emissao;$data_recebimento;$prazo_recebimento;$data_conserto;$tempo_conserto;$data_fechamento;$qtde_dias_finalizar;$qtde_dias_total\r\n";
                }

                $conteudo .=  "<tr>
                            <td class='tac'><a href='os_press.php?os=$os' target='_blank'>$os</a></td>
                            <td class='tac'>$data_abertura</td>
                            <td class='tac'>$data_digitacao</td>
                            <td class='tac'>$qtde_dias_digitacao</td>
                            <td class='tac'>$data_nf</td>
                            <td nowrap>$nome_posto</td>
                            <td class='tac'>$uf</td>
                            <td nowrap>$referencia - $descricao</td>
                            <td nowrap>$referencia_peca - $descricao_peca</td>
                            <td class='tac'>$digitacao_item</td>
                            <td class='tac'>$tempo_lancamento_peca</td>
                            <td class='tac'>$pedido</td>
                            <td class='tac'>$data_pedido</td>
                            <td class='tac'>$qtde_dias_gerar_pedido</td>                            
                            <td class='tac'>$embarcado</td>
                            <td class='tac'>$tempo_embarque</td>
                            <td class='tac'>$emissao</td>
                            <td class='tac'>$data_recebimento</td>
                            <td class='tac'>$prazo_recebimento</td>
                            <td class='tac'>$data_conserto</td>
                            <td class='tac'>$tempo_conserto</td>
                            <td class='tac'>$data_fechamento</td>
                            <td class='tac'>$qtde_dias_finalizar</td>
                            <td class='tac'>$qtde_dias_total</td>
                        </tr>";
            }
        }
    }
    if(isset($_POST['gerar_excel'])){
        
        fwrite($file, $tbody);
        fclose($file);

        if (file_exists("/tmp/{$filename}")) {
            system("mv /tmp/{$filename} xls/{$filename}");

            echo "xls/{$filename}";
        }
        exit;
    }

$array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
  "AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
  "ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
  "MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
  "PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
  "RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
  "RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
  "SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");

$layout_menu = "gerencia";
$title = "RELATÓRIO DE RASTREAMENTO";

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable",
    "multiselect"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
    function AbreCallcenter(data_inicial,data_final,produto,natureza,status,tipo,posto,atendente,tipo_data,tipo_cliente, motivo_atendimento,linhaDeProduto,tipo_atendimento,motivo_transferencia,origem){
        janela = window.open("callcenter_relatorio_atendimento_callcenter.php?data_inicial=" +data_inicial+ "&data_final=" +data_final+ "&produto=" +produto+ "&natureza=" +natureza+ "&status=" +status+"&tipo="+tipo+"&atendente="+atendente+"&posto="+posto+"&tipo_data="+tipo_data+"&motivo_atendimento="+motivo_atendimento+"&linhaDeProduto="+linhaDeProduto+"&tipo_atendimento="+tipo_atendimento+"&motivo_transferencia="+motivo_transferencia+"&tipo_cliente="+tipo_cliente+"&origem="+origem, "Callcenter",'scrollbars=yes,width=750,height=450,top=315,left=0');
        janela.focus();
    }

    $(function() {
        $.datepickerLoad(Array("data_final", "data_inicial"));
        $.autocompleteLoad(Array("produto", "posto"));
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

        <? if ($login_fabrica == 50) { ?>

        $("#motivo_atendimento").multiselect({
        selectedText: "# de # opções"

        });
        <? } ?>

    });

    function retorna_produto (retorno) {
        $("#produto_referencia").val(retorno.referencia);
        $("#produto_descricao").val(retorno.descricao);
    }

    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }

</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<!-- <script type="text/javascript" src="js/grafico/highcharts.js"></script> -->
<script type="text/javascript" src="js/novo_highcharts.js"></script>
<script type="text/javascript" src="js/modules/exporting.js"></script>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>
    

    <div class='row-fluid'>
        <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'>Data Inicial</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <h5 class='asteristico'>*</h5>
                                <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
                        </div>
                    </div>
                </div>
            </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_final'>Data Final</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
            <div class='span8'>
                <label class='control-label' for='descricao_posto'>Data de Referência</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <input type="radio" name="data_filtro" value="abertura">
                        Abertura &nbsp;&nbsp;
                    </div>
                    <div class='span4'>
                        <input type="radio" name="data_filtro" value="digitacao">
                        Digitação &nbsp;&nbsp;
                    </div>
                    <div class='span4'>
                        <input type="radio" name="data_filtro" value="fechamento">
                        Fechamento
                    </div>
                </div>
            </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='produto_referencia'>Ref. Produto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='produto_descricao'>Descrição Produto</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>

    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='codigo_posto'>Código Posto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" id="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='descricao_posto'>Nome Posto</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='codigo_posto'>OS</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <input type="text" name="os" id="os" class='span12' value="<? echo $os_pesquisa ?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span5'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='codigo_posto'>Faturamento</label>
                <div class='controls controls-row'>
                    <div class='span4'>                      
                        <input type="radio" name="faturamento" value="com" <?php if($faturamento == 'com'){ echo " checked "; }?>>Faturadas
                    </div>
                    <div class='span6'>
                        <input type="radio" name="faturamento" value="sem" <?php if($faturamento == 'sem'){ echo " checked "; }?> >Não Faturadas
                    </div>
                </div>
            </div>
        </div>
        <div class='span1'></div>
    </div>
    
    <p><br/>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</FORM>

</div>
    <?php 

    if($qtde >0){ ?>
        <table id="callcenter_relatorio_atendimento" class='table table-striped table-bordered table-hover table-fixed' >
            <thead>
                <TR class='titulo_coluna'>
                    <td class='tac'>OS</td>
                    <td class='tac'>Data Abertura</td>
                    <td class='tac'>Data Digitação</td>
                    <td class='tac'>Qtde dias Digitação</td>
                    <td class='tac'>Data NF</td>                    
                    <td class='tac'>Posto</td>
                    <td class='tac'>UF</td>
                    <td class='tac'>Produto</td>
                    <td class='tac'>Peça</td>
                    <td class='tac'>Lancamento Peças</td>
                    <td class='tac'>Qtde dias Digitação Item</td>
                    <td class='tac'>Pedido</td>
                    <td class='tac'>Data Pedido</td>
                    <td class='tac'>Qtde dias Pedido</td>
                    <td class='tac'>Embarque</td>
                    <td class='tac'>Qtde dias Embarque</td>
                    <td class='tac'>Expedição</td>
                    <td class='tac'>Recebimento</td>
                    <td class='tac'>Qtde dias Recebimento</td>
                    <td class='tac'>Conserto</td>
                    <td class='tac'>Qtde dias Conserto</td>
                    <td class='tac'>Finalizado</td>
                    <td class='tac'>Qtde dias Finalizar</td>
                    <td class='tac'>Qtde dias Total</td>
                </tr>
            </thead>
            <?=$conteudo ?>
        </table>
        <br>
        <?php $jsonPOST = excelPostToJson($_POST); ?>
        <div id='gerar_excel' class="btn_excel">
            <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
            <span><img src='imagens/excel.png' /></span>
            <span class="txt">Gerar Arquivo Excel</span>
        </div>
<?php

	    if($qtde > 50){
?>
		<script> $.dataTableLoad({ table: "#callcenter_relatorio_atendimento"});</script>
<?php
	    }
    }?>

<? include "rodape.php" ?>
