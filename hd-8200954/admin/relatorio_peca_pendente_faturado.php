<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';
include "monitora.php";
$layout_menu = "gerencia";
$title = "RELATÓRIO DE PEDIDOS FATURADOS COM PEÇAS PENDENTES";
# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
    $tipo_busca = $_GET["busca"];
    if (strlen($q)>2){
        $sql = "SELECT  tbl_posto.cnpj,
                        tbl_posto.nome,
                        tbl_posto_fabrica.codigo_posto
                FROM    tbl_posto
                JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                WHERE   tbl_posto_fabrica.fabrica = $login_fabrica ";
        $sql .= ($tipo_busca == "codigo") ? " AND tbl_posto_fabrica.codigo_posto = '$q' " :  " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
        $res = pg_query($con,$sql);
        if (pg_num_rows ($res) > 0) {
            for ($i=0; $i<pg_num_rows ($res); $i++ ){
                $cnpj         = trim(pg_fetch_result($res,$i,cnpj));
                $nome         = trim(pg_fetch_result($res,$i,nome));
                $codigo_posto = trim(pg_fetch_result($res,$i,codigo_posto));
                echo "$codigo_posto|$nome|$cnpj";
                echo "\n";
            }
        }
    }
    exit;
}
include 'cabecalho.php';
?>

<style type="text/css">
    .Titulo {
        text-align: center;
        font-family: Arial;
        font-size: 11px;
        font-weight: bold;
        color: #FFFFFF;
        background-color: #485989;
    }
    .Erro {
        text-align: center;
        font-family: Arial;
        font-size: 16px;
        font-weight: bold;
        color: #FFFFFF;
        background-color: #FF0000;
    }
    .Conteudo {
        text-align: left;
        font-family: Arial;
        font-size: 11px;
        font-weight: normal;
    }
    .Conteudo2 {
        text-align: center;
        font-family: Arial;
        font-size: 11px;
        font-weight: normal;
    }
    .Caixa{
        BORDER-RIGHT: #6699CC 0px solid;
        BORDER-TOP: #6699CC 0px solid;
        FONT: 8pt Arial ;
        BORDER-LEFT: #6699CC 0px solid;
        BORDER-BOTTOM: #6699CC 0px solid;
        BACKGROUND-COLOR: #FFFFFF
    }
    #tooltip{
        background: #5D92B1;
        border:0px solid #000;
        display:none;
        padding: 2px 4px;
        color: #FFFFFF;
        text-align: center;
        font-family: Arial;
        font-size: 11px;
        font-weight: normal;
        width: 250px;
    }
    .titulo_tabela{
        background-color:#596d9b;
        font: bold 14px "Arial";
        color:#FFFFFF;
        text-align:center;
    }


    .titulo_coluna{
        background-color:#596d9b;
        font: bold 11px "Arial";
        color:#FFFFFF;
        text-align:center;
    }

    .msg_erro{
        background-color:#FF0000;
        font: bold 16px "Arial";
        color:#FFFFFF;
        text-align:center;
    }

    .formulario{
        background-color:#D9E2EF;
        font:11px Arial;
    }

    table.tabela tr td{
        font-family: verdana;
        font-size: 11px;
        border-collapse: collapse;
        border:1px solid #596d9b;
    }
</style>
<script type="text/javascript">
    window.onload = function(){
        tooltip.init();
    }
</script>
<? include "javascript_pesquisas.php" ?>
<script type="text/javascript">
    function abreOpcao(valor){
        if (valor == 'aberta'){
            $("#opcao_os_aberta").css('display','');
        }else{
            $("#opcao_os_aberta").css('display','none');
        }
    }
    function fnc_pesquisa_posto2 (campo, campo2, tipo) {
        if (tipo == "codigo" ) {
            var xcampo = campo;
        }

        if (tipo == "nome" ) {
            var xcampo = campo2;
        }

        if (xcampo.value != "") {
            var url = "";
            url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
            janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
            janela.codigo  = campo;
            janela.nome    = campo2;
            janela.focus();
        }
        else{
            alert("Preencha toda ou parte da informação para realizar a pesquisa!");
        }
    }

    function fnc_pesquisa_produto2 (campo, campo2, tipo) {
        if (tipo == "referencia" ) {
            var xcampo = campo;
        }

        if (tipo == "descricao" ) {
            var xcampo = campo2;
        }


        if (xcampo.value != "") {
            var url = "";
            url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&exibe=/assist/admin/relatorio_peca_pendente.php";
            janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
            janela.referencia   = campo;
            janela.descricao    = campo2;


            janela.focus();
        } else {
            alert("Preencha toda ou parte da informação para realizar a pesquisa!");
        }
    }

    function fnc_pesquisa_peca2 (campo, campo2, tipo) {
        if (tipo == "referencia" ) {
            var xcampo = campo;
        }

        if (tipo == "descricao" ) {
            var xcampo = campo2;
        }


        if (xcampo.value != "") {
            var url = "";
            url = "peca_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&exibe=/assist/admin/relatorio_peca_pendente.php";
            janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
            janela.referencia   = campo;
            janela.descricao    = campo2;


            janela.focus();
        } else {
            alert("Preencha toda ou parte da informação para realizar a pesquisa!");
        }
    }

</script>
<style type="text/css">
    @import "../plugins/jquery/datepick/telecontrol.datepick.css";
</style>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">

<script type="text/javascript" src="js/jquery-1.8.3.min.js"></script>
<script type="text/javascript" src="js/jquery.bgiframe.min.js"></script>
<script type="text/javascript" src="js/jquery.autocomplete.js"></script>
<script type="text/javascript" src="js/jquery.dimensions.js"></script>
<script type="text/javascript" src="../plugins/shadowbox/shadowbox.js" ></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>

<script type="text/javascript">
    var hora = new Date();
    var engana = hora.getTime();
    $().ready(function() {

        $("#cidade").alpha({allow:" "});

        $('#data_inicial').datepick({startDate:'01/01/2000'});
        $('#data_final').datepick({startDate:'01/01/2000'});
        $("#data_inicial").maskedinput("99/99/9999");
        $("#data_final").maskedinput("99/99/9999");

        Shadowbox.init();

        $('#produto_referencia').autocomplete("autocomplete_produto_ajax.php?engana=" + engana,{
            minChars: 3,
            delay: 150,
            width: 450,
            scroll: true,
            scrollHeight: 200,
            matchContains: false,
            highlightItem: false,
            formatItem: function (row)   {return row[1]+"&nbsp;-&nbsp;"+row[2]},
            formatResult: function(row)  {return row[1];}
        });
        $('#produto_referencia').result(function(event, data, formatted) {
            $("#produto_referencia").val(data[1]);
            $("#produto_descricao").val(data[2]);
        });
        $('#descricao_produto').autocomplete("autocomplete_produto_ajax.php?engana=" + engana,{
            minChars: 3,
            delay: 150,
            width: 450,
            scroll: true,
            scrollHeight: 200,
            matchContains: false,
            highlightItem: false,
            formatItem: function (row)   {return row[1]+"&nbsp;-&nbsp;"+row[2]},
            formatResult: function(row)  {return row[1];}
        });
        $('#descricao_produto').result(function(event, data, formatted) {
            $("#produto_referencia").val(data[1]);
            $("#descricao_produto").val(data[2]);
        });
        $('#peca_referencia').autocomplete("autocomplete_peca_ajax.php?engana=" + engana,{
        minChars: 3,
        delay: 150,
        width: 450,
        scroll: true,
        scrollHeight: 200,
        matchContains: false,
        highlightItem: false,
        formatItem: function (row)   {return row[1]+"&nbsp;-&nbsp;"+row[2]},
        formatResult: function(row)  {return row[1];}
        });
        $('#peca_referencia').result(function(event, data, formatted) {
            $("#peca_referencia").val(data[1]);
            $("#peca_descricao").val(data[2]);
        });
        $('#descricao_peca').autocomplete("autocomplete_peca_ajax.php?engana=" + engana,{
            minChars: 3,
            delay: 150,
            width: 450,
            scroll: true,
            scrollHeight: 200,
            matchContains: false,
            highlightItem: false,
            formatItem: function (row)   {return row[1]+"&nbsp;-&nbsp;"+row[2]},
            formatResult: function(row)  {return row[1];}
        });
        $('#descricao_peca').result(function(event, data, formatted) {
            $("#peca_referencia").val(data[1]);
            $("#descricao_peca").val(data[2]);
        });
        $('#codigo_posto').autocomplete("autocomplete_posto_ajax.php?engana=" + engana,{
            minChars: 3,
            delay: 150,
            width: 450,
            scroll: true,
            scrollHeight: 200,
            matchContains: false,
            highlightItem: false,
            formatItem: function (row)   {return row[0]+"&nbsp;-&nbsp;"+row[1]},
            formatResult: function(row)  {return row[0];}
        });
        $('#codigo_posto').result(function(event, data, formatted) {
            $("#codigo_posto").val(data[0]);
            $("#descricao_posto").val(data[1]);
        });
        $('#descricao_posto').autocomplete("autocomplete_posto_ajax.php?engana=" + engana,{
            minChars: 3,
            delay: 150,
            width: 450,
            scroll: true,
            scrollHeight: 200,
            matchContains: false,
            highlightItem: false,
            formatItem: function (row)   {return row[0]+"&nbsp;-&nbsp;"+row[1]},
            formatResult: function(row)  {return row[0];}
        });
        $('#descricao_posto').result(function(event, data, formatted) {
            $("#codigo_posto").val(data[0]);
            $("#descricao_posto").val(data[1]);
        });

        $('#posto_referencia,#posto_descricao').blur(function() {
            if($(this).val().length == 0){
                $('input[name=posto]').val('');
            }
        });
    })

    function fnc_pesquisa_posto_novo(codigo, nome) {
        var codigo = jQuery.trim(codigo.value);
        var nome   = jQuery.trim(nome.value);
        if (codigo.length > 2 || nome.length > 2){
            Shadowbox.open({
                content:    "posto_pesquisa_2_nv.php?os=&codigo=" + codigo + "&nome=" + nome,
                player: "iframe",
                title:      "Pesquisa Posto",
                width:  800,
                height: 500
            });
        }else{
            alert("Preencha toda ou parte da informação para realizar a pesquisa!");
        }
    }

    function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento){
        gravaDados("posto_referencia",codigo_posto);
        gravaDados("posto_descricao",nome);
        gravaDados("posto",posto);
        $('#uf_posto').val(estado);
    }


    function gravaDados(name, valor){
        try {
            $("input[name="+name+"]").val(valor);
        } catch(err){
            return false;
        }
    }
</script>
<?
flush();
if (strlen($_GET['data_inicial']) > 0){
    $data_inicial = $_GET['data_inicial'];
}else{
    $data_inicial = $_POST['data_inicial'];
}

if (strlen($_GET['data_final']) > 0){
    $data_final   = $_GET['data_final'];
}else{
    $data_final   = $_POST['data_final'];
}

if (strlen($_GET['qtde_dias']) > 0){
    $qtde_dias   = $_GET['qtde_dias'];
}else{
    $qtde_dias   = $_POST['qtde_dias'];
}

if (strlen($_GET['peca_referencia']) > 0){
    $peca_referencia = $_GET['peca_referencia'];
}else{
    $peca_referencia = $_POST['peca_referencia'];
}

if (strlen($_GET['peca_descricao']) > 0){
    $peca_descricao = $_GET['peca_descricao'];
}else{
    $peca_descricao = $_POST['peca_descricao'];
}

if($btn_acao=="Consultar"){

    //Início Validação de Datas
    if((!$data_inicial) OR (!$data_final)){
        $msg_erro = "Data Inválida";
    }

    if(strlen($msg_erro)==0){
        $dat = explode ("/", $data_inicial );//tira a barra
            $d = $dat[0];
            $m = $dat[1];
            $y = $dat[2];
            if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
    }

    if(strlen($msg_erro)==0){
        $dat = explode ("/", $data_final );//tira a barra
            $d = $dat[0];
            $m = $dat[1];
            $y = $dat[2];
            if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
    }

    if(strlen($msg_erro)==0){
        $d_ini = explode ("/", $data_inicial);//tira a barra
        $aux_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...


        $d_fim = explode ("/", $data_final);//tira a barra
        $aux_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

        if($aux_data_final < $aux_data_inicial){
            $msg_erro = "Data Inválida";
        }

        $nova_data_inicial = mktime(0,0,0,$d_ini[1],$d_ini[0],$d_ini[2]); // timestamp da data inicial
        $nova_data_final = mktime(0,0,0,$d_fim[1],$d_fim[0],$d_fim[2]); // timestamp da data final
        $cont = 0;
        while($nova_data_inicial <= $nova_data_final){//enquanto uma data for inferior a outra {
          $nova_data_inicial += 86400; // adicionando mais 1 dia (em segundos) na data inicial
          $cont++;
        }

        if($cont > 31){
            $msg_erro="Período Não Pode ser Maior que 31 Dias";
            //$msg_erro = 'Data Invalida';
        }
        //Fim Validação de Datas
    }
}

?>
<form name="frm_relatorio" method="POST" action="<?=$PHP_SELF?>">
    <br />
    <table width="700px" class="formulario" border="0" cellpadding="1" cellspacing="1" align="center">
<?
if(strlen($msg_erro)>0){
?>
        <tr>
            <td class="msg_erro"><?=$msg_erro?></td>
        </tr>
<?
}
?>
        <tr class="titulo_tabela">
            <td colspan='2'align='center'>
                Parâmetros de Pesquisa
            </td>
        </tr>
        <tr>
            <td >
                <table width='90%' border='0' cellspacing='1' cellpadding='2' class='Conteudo' align='center'>
                    <tr>
                        <td>
                            <table width='100%' align='left' border='0'  cellpadding="0" cellspacing="0">
                                <tr class="Conteudo" bgcolor="#D9E2EF">
                                    <td width="210px">Data Inicial (abertura)</td>
                                    <td>Data Final (abertura)</td>
                                </tr>
                                <tr>
                                    <td>
                                        <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm' value= "<?=$data_inicial?>" >
                                    </td>
                                    <td>
                                        <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<?=$data_final?>" >
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <table width='100%' align='left' border="0" cellpadding="0" cellspacing="0">
                                <tr class="Conteudo">
                                    <td  align='left' >Ref. Peça</td>
                                    <td align='left'  >Descrição Peça</td>
                                </tr>
                                <tr>
                                    <td align='left'>
                                        <input type="text" id="peca_referencia" name="peca_referencia" style="width:80%" class='frm' maxlength="20" value="<? echo $peca_referencia ?>" >
                                        <img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_peca2 (document.frm_relatorio.peca_referencia, document.frm_relatorio.peca_descricao,'referencia')" >
                                    </td>
                                    <td  align='left'>
                                        <input type="text" id="peca_descricao" name="peca_descricao" size="45" class='frm' value="<? echo $peca_descricao ?>" >
                                        <img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_peca2 (document.frm_relatorio.peca_referencia, document.frm_relatorio.peca_descricao,'descricao')" >
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <center>
                    <br>
                    <input type="button" style="cursor:pointer;" value="Pesquisar" onclick="document.frm_relatorio.btn_acao.value='Consultar'; document.frm_relatorio.submit();"  alt="Preencha as opções e clique aqui para pesquisar">
                    <input type='hidden' name='btn_acao' value='<?=$acao?>'>
                </center>
            </td>
        </tr>
    </table>
<?
if($btn_acao=="Consultar" AND strlen($msg_erro) == 0){

    $sql = "SELECT  tipo_pedido
            FROM    tbl_tipo_pedido
            WHERE   fabrica = $login_fabrica
            AND     tbl_tipo_pedido.pedido_faturado IS TRUE
            LIMIT   1
    ";
    $res = pg_query($con,$sql);
    $tipo_pedido = pg_fetch_result($res,0,tipo_pedido);


    $sql = "SELECT  tbl_peca.referencia                             AS referencia_peca  ,
                    TO_CHAR(tbl_pedido.data,'DD/MM/YYYY')           AS data_abertura    ,
                    tbl_peca.peca                                                       ,
                    tbl_peca.descricao                              AS descricao_peca   ,
                    tbl_posto.nome                                                      ,
                    tbl_posto_fabrica.codigo_posto                                      ,
                    tbl_posto_fabrica.contato_estado                AS posto_estado     ,
                    tbl_pedido.pedido                                                   ,
		    ((current_date)::date - tbl_pedido.data::date)  AS dias_pendentes   ,
		    SUM(tbl_pedido_item.qtde - (tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada)) AS qtde_pendente
            FROM    tbl_pedido
            JOIN    tbl_pedido_item USING (pedido)
            JOIN    tbl_peca        USING (peca)
       LEFT JOIN    tbl_faturamento ON tbl_faturamento.pedido = tbl_pedido.pedido
       LEFT JOIN    tbl_faturamento_item USING (faturamento)
            JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto     = tbl_pedido.posto
                                        AND tbl_posto_fabrica.fabrica   = $login_fabrica
            JOIN    tbl_posto           ON  tbl_posto.posto             = tbl_pedido.posto
            WHERE   tbl_pedido.fabrica      = $login_fabrica
            AND     tbl_pedido.tipo_pedido  = $tipo_pedido
            AND     tbl_faturamento_item.faturamento IS NULL
            AND NOT (tbl_pedido.status_pedido = 14)
    ";

    if (strlen($aux_data_inicial)>0 AND strlen($aux_data_final)>0) {
        $sql .= "
            AND     tbl_pedido.data BETWEEN '$aux_data_inicial' AND '$aux_data_final'
        ";
    }
    if(strlen($peca_referencia) > 0 && strlen($peca_descricao) > 0){
        $sql .= "
            AND     tbl_peca.referencia = '$peca_referencia'
            AND     tbl_peca.descricao  = '$peca_descricao'
        ";
    }

    $sql .= "GROUP BY tbl_peca.referencia ,
	    	tbl_pedido.data ,
	    	tbl_peca.peca ,
	    	tbl_peca.descricao ,
	    	tbl_posto.nome ,
	    	tbl_posto_fabrica.codigo_posto ,
	    	tbl_posto_fabrica.contato_estado ,
		tbl_pedido.pedido";
#echo nl2br($sql);exit;
    $res = pg_query($con,$sql);

    $so_excel = 0;
    $total_reg = pg_num_rows($res);
    if ($total_reg > 500) {
        $so_excel = 1;
    }
    if (pg_num_rows($res) > 0) {
        if ($so_excel == 0) {
?>
    <br>
    <table border='0' cellpadding='2' cellspacing='1' class='tabela' width='1500' align='center'>
        <tr class='titulo_coluna' height='25'>
            <td >Peça</td>
            <td >Descrição Peça</td>
            <td >Pedido</td>
	    <td >Abertura</td>
	    <td>Qtde Pendente</td>
            <td >Qtde dias Pendentes</td>
            <td >Cód. Posto</td>
            <td >Posto</td>
            <td >Posto UF</td>
        </tr>
<?
            for ($i=0; $i<$total_reg; $i++){
                $referencia_peca    = trim(pg_fetch_result($res,$i,'referencia_peca'));
                $descricao_peca     = trim(pg_fetch_result($res,$i,'descricao_peca'));
                $codigo_posto       = trim(pg_fetch_result($res,$i,'codigo_posto'));
                $data_abertura      = trim(pg_fetch_result($res,$i,'data_abertura'));
                $posto_nome         = trim(pg_fetch_result($res,$i,'nome'));
                $pedido             = trim(pg_fetch_result($res,$i,'pedido'));
                $posto_estado       = trim(pg_fetch_result($res,$i,'posto_estado'));
		$dias_pendentes     = trim(pg_fetch_result($res,$i,'dias_pendentes'));
		$qtde_pendente      = trim(pg_fetch_result($res,$i,'qtde_pendente'));

                if($cor=="#F1F4FA"){
                    $cor = '#F7F5F0';
                }else{
                    $cor = '#F1F4FA';
                }
?>
        <tr style="background-color:<?=$cor?>;">
            <td><?=$referencia_peca?></td>
            <td><?=$descricao_peca?></td>
            <td>
                <a href="pedido_admin_consulta.php?pedido=<?=$pedido?>" target="_blank">
                    <?=$pedido?>
                </a>
            </td>
	    <td><?=$data_abertura?></td>
	    <td><?=$qtde_pendente?></td>
            <td><?=$dias_pendentes?></td>
            <td><?=$codigo_posto?></td>
            <td><?=$posto_nome?></td>
            <td><?=$posto_estado?></td>
        </tr>
<?
            }
?>
    </table>
<?
        }
    }else{
?>
    <p style="font-size: 12px; text-align=center; ">Nenhum resultado encontrado</p>
<?
    }
    if (pg_num_rows($res) > 0){
?>
    <br>
    <font size='2'>Total de <b><? echo pg_num_rows($res); ?></b> peças.</font>
<?
    }

    // INÍCIO DO PROCESSO DE GERAÇÃO DE ARQUIVO
    if (pg_num_rows($res) > 0) {
        $data = date ("dmYHi");
        $arquivo_nome3        = "relatorio_peca_pendente-$login_fabrica-$data.xls";
        $path                 = "/var/www/assist/www/admin/xls/";
        $path_tmp             = "/tmp/assist/";
        $arquivo_completo     = $path.$arquivo_nome3;
        $arquivo_completo_tmp = $path_tmp.$arquivo_nome3;
        echo `rm -f $arquivo_completo_tmp `;
        echo `rm -f $arquivo_completo_tmp.zip `;
        echo `rm -f $arquivo_completo `;
        echo `rm -f $arquivo_completo.zip `;
        $fp = fopen ($arquivo_completo_tmp,"w");

        fputs ($fp, "Peça \t Descrição Peça \t Pedido \t Abertura \t Qtde pendente \t Qtde dias pendentes \t Cód. Posto \t Posto Nome \t Posto UF  \r\n");


        for ($i=0; $i<$total_reg; $i++){
            $referencia_peca    = trim(pg_fetch_result($res,$i,'referencia_peca'));
            $descricao_peca     = trim(pg_fetch_result($res,$i,'descricao_peca'));
            $codigo_posto       = trim(pg_fetch_result($res,$i,'codigo_posto'));
            $data_abertura      = trim(pg_fetch_result($res,$i,'data_abertura'));
            $posto_nome         = trim(pg_fetch_result($res,$i,'nome'));
            $pedido             = trim(pg_fetch_result($res,$i,'pedido'));
            $posto_estado       = trim(pg_fetch_result($res,$i,'posto_estado'));
            $dias_pendentes     = trim(pg_fetch_result($res,$i,'dias_pendentes'));
			$qtde_pendente      = trim(pg_fetch_result($res,$i,'qtde_pendente'));

            $escreve = "$referencia_peca\t$descricao_peca\t$pedido\t$data_abertura\t$qtde_pendente\t$dias_pendentes\t$codigo_posto\t$posto_nome\t$posto_estado";

            $escreve.= "\r\n";
            fwrite($fp, $escreve);

        }
        fclose ($fp);
        echo `cd $path_tmp; rm -f $arquivo_nome3.zip; zip -o $arquivo_nome3.zip $arquivo_nome3 > /dev/null ; mv  $arquivo_nome3.zip $path `;
?>
<p id='id_download3'>
    <input type="button" value="Baixar Relatório em Excel" onclick="window.location='xls/<?=$arquivo_nome3?>.zip'">
</p>
<?
    }
    // FIM DO PROCESSO DE GERAÇÃO DE ARQUIVO
}
include 'rodape.php';
?>
</form>
