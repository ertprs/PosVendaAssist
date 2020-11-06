<?php
include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";
include_once "login_unico_autentica_usuario.php";

$title = "Consulta de OS";
$aba = 5;
    $sql = "SELECT tbl_fabrica.fabrica
            FROM tbl_fabrica
            JOIN tbl_posto_fabrica USING(fabrica)
            WHERE  (tbl_posto_fabrica.credenciamento = 'CREDENCIADO' OR tbl_posto_fabrica.credenciamento = 'EM DESCREDENCIAMENTO')
            AND  fabrica <> 0
            AND posto = $cook_posto ORDER BY fabrica";
    $res = pg_query($con,$sql);
    if(pg_num_rows($res) > 0){
        for($i =0;$i<pg_num_rows($res);$i++) {
            $fabricas .= ($i > 0) ? ",".pg_fetch_result($res,$i,'fabrica'): pg_fetch_result($res,$i,'fabrica');
        }
    }

if(isset($_REQUEST['pesquisa']) > 0) {

    $sua_os          = (strlen(trim(strtoupper($_GET['sua_os'])))         == 0) ? trim(strtoupper($_POST['sua_os']))          : trim(strtoupper($_GET['sua_os']));
    $serie           = (strlen(trim(strtoupper($_GET['serie'])))          == 0) ? trim(strtoupper($_POST['serie']))           : trim(strtoupper($_GET['serie']));
    $nf_compra       = (strlen(trim(strtoupper($_GET['nf_compra'])))      == 0) ? trim(strtoupper($_POST['nf_compra']))       : trim(strtoupper($_GET['nf_compra']));
    $consumidor_cpf  = (strlen(trim(strtoupper($_GET['consumidor_cpf']))) == 0) ? trim(strtoupper($_POST['consumidor_cpf']))  : trim(strtoupper($_GET['consumidor_cpf']));
    $consumidor_nome = (strlen($_GET['nome_consumidor'])                  == 0) ? trim(strtoupper($_POST['nome_consumidor'])) : trim(strtoupper($_GET['nome_consumidor']));

    $mes = (strlen(trim($_GET['mes'])) == 0) ? trim ($_POST['mes']) : trim($_GET['mes']);
    $ano = (strlen($_GET['ano'])       == 0) ? trim ($_POST['ano']) : trim($_GET['ano']);
    $gera_excel = (strlen($_GET['gera_excel'])       == 0) ? trim ($_POST['gera_excel']) : trim($_GET['gera_excel']);


    $os_aberta = (strlen($_GET['os_aberta'])==0) ? trim($_POST['os_aberta']) : trim($_GET['os_aberta']);

    switch (substr($mes,0,3)) {
        case 'Jan':   $mes = "01"; break;
        case 'Fev': $mes = "02"; break;
        case 'Mar':     $mes = "03"; break;
        case 'Abr':     $mes = "04"; break;
        case 'Mai':      $mes = "05"; break;
        case 'Jun':     $mes = "06"; break;
        case 'Jul':     $mes = "07"; break;
        case 'Ago':    $mes = "08"; break;
        case 'Set':  $mes = "09"; break;
        case 'Out':   $mes = "10"; break;
        case 'Nov':  $mes = "11"; break;
        case 'Dez':  $mes = "12"; break;
    }

    if (strlen($mes) > 0) {
        $data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
        $data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
    }
    $join_cond = " FROM tbl_os ";
    if (strlen ($data_inicial) > 0 ) {
            $join_cond= "FROM (  SELECT os
                                        FROM tbl_os
                                        JOIN tbl_os_extra USING (os)
                                        LEFT JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
                                        WHERE fabrica      in ($fabricas)
                                        AND   tbl_os.posto = $login_posto
                                        AND   tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'
                                ) oss JOIN tbl_os ON tbl_os.os = oss.os";
    }

    if (strlen($os_aberta) > 0) {
        $cond1= " AND tbl_os.os_fechada IS FALSE
                  AND tbl_os.excluida IS NOT TRUE ";
    }

    if (strlen($sua_os) > 0) {
        $sua_os = strtoupper ($sua_os);
        $pos = strpos($sua_os, "-");
        if ($pos === false) {
            if ($fabricas == 175){
                $cond2 .= " AND tbl_os.sua_os = '$sua_os' ";
            }else{
                if(!ctype_digit($sua_os)){
                    $cond2 .= " AND tbl_os.sua_os = '$sua_os' ";
                }else{
                    $cond2 .= " AND tbl_os.os_numero = '$sua_os' ";
                }
            }
        }else{
            $conteudo = explode("-", $sua_os);
            $os_numero    = $conteudo[0];
            $os_sequencia = $conteudo[1];

            if ($fabricas == 175){
                $cond2 .= " AND tbl_os.sua_os ILIKE '%$sua_os%' ";
            }else{
                if(!ctype_digit($os_sequencia)){
                    $cond2 .= " AND tbl_os.sua_os = '$sua_os' ";
                }else{
                    $cond2 .= " AND tbl_os.os_numero = '$os_numero' AND tbl_os.os_sequencia = '$os_sequencia' ";
                }
            }
        }
    }

    if (strlen($serie) > 0) {
        $cond3 .= " AND tbl_os.serie = '$serie'";
    }

    if (strlen($nf_compra) > 0) {
        $cond4 .= " AND tbl_os.nota_fiscal = '$nf_compra'";
    }

    if (strlen($consumidor_nome) > 0) {
        $consumidor_nome = strtoupper ($consumidor_nome);
        $cond5 .= " AND UPPER(tbl_os.consumidor_nome) LIKE '%$consumidor_nome%'";
    }

    if (strlen($consumidor_cpf) > 0) {
        $cond6 .= " AND tbl_os.consumidor_cpf = '$consumidor_cpf'";
    }

    $sql = " SELECT count(*)
            $join_cond
            JOIN tbl_produto USING(produto)
            WHERE posto  = $login_posto
            AND   fabrica in ($fabricas)
            $cond1 $cond2 $cond3 $cond4 $cond5 $cond6";
    $res = pg_query($con,$sql);
    $qtde = pg_fetch_result($res,0,0);

    $sql = " SELECT tbl_os.os,
                    data_abertura,
                    data_fechamento,
                    tbl_os.sua_os,
                    serie,
                    referencia,
                    descricao,
                    nota_fiscal,
                    consumidor_nome,
                    tbl_fabrica.nome,
                    tbl_posto_fabrica.codigo_posto,
                    tbl_os.fabrica
            $join_cond
            JOIN tbl_produto USING(produto)
            JOIN tbl_fabrica USING(fabrica)
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto and tbl_posto_fabrica.fabrica = tbl_os.fabrica
            WHERE tbl_os.posto  = $login_posto
            AND   tbl_os.fabrica in ($fabricas)
            $cond1 $cond2 $cond3 $cond4 $cond5 $cond6
            ORDER BY tbl_os.fabrica, tbl_os.os ";
    if(strlen($_POST['start']) > 0 and strlen($_POST['limit']) > 0) {
        $sql .= " offset ".$_POST['start'] ." limit ".$_POST['limit'];
    }
    $res = pg_query($con,$sql);

    if(pg_num_rows($res) > 0){
        //inicio gera excel
        if ($gera_excel == 'sim') {

            $data = date("d-m-Y");
            $fileName = "consulta_os_multi-{$data}.csv";
            $utf8_bom = "\xEF\xBB\xBF";
            $file = fopen("/tmp/{$fileName}", "w");

            fwrite($file, $utf8_bom);

            $thead = "\"Fábrica\";\"OS\";\"Série\";\"NF\";\"AB\";\"FC\";\"Consumidor\";\"Produto\"\n";
            fwrite($file, utf8_encode($thead));

            $resultados       = pg_fetch_all($res);

            $body = "";
            foreach($resultados as $resultado) {
                $os              = $resultado['os'];
                $data_abertura   = $resultado['data_abertura'];
                $data_fechamento = $resultado['data_fechamento'];
                $sua_os          = $resultado['sua_os'];
                $serie           = $resultado['serie'];
                $referencia      = $resultado['referencia'];
                $descricao       = $resultado['descricao'];
                $nota_fiscal     = $resultado['nota_fiscal'];
                $consumidor_nome = $resultado['consumidor_nome'];
                $fabrica_nome    = $resultado['nome'];
                $fabrica         = $resultado['fabrica'];
                $codigo_posto    = $resultado['codigo_posto'];

                if($fabrica == 1) {
                    $sua_os = $codigo_posto."".$sua_os;
                }
                $body .= "\"".$fabrica_nome."\";\"".$sua_os."\";\"".$serie."\";\"".$nota_fiscal."\";\"".$data_abertura."\";\"".$data_fechamento."\";\"".$consumidor_nome."\";\"".$referencia.'-'.$descricao."\"\n";
            }
            fwrite($file, utf8_encode($body));
            fclose($file);

            if (file_exists("/tmp/{$fileName}")) {
                system("mv /tmp/{$fileName} admin/xls/{$fileName}");
                echo "admin/xls/{$fileName}";
            }

            exit;
        }//fim gerar excel

        $resultados       = pg_fetch_all($res);
        $i = 0;
        echo "{'total':'".$qtde."','resultado': [";
        foreach($resultados as $resultado) {
            $os              = $resultado['os'];
            $data_abertura   = $resultado['data_abertura'];
            $data_fechamento = $resultado['data_fechamento'];
            $sua_os          = $resultado['sua_os'];
            $serie           = $resultado['serie'];
            $referencia      = $resultado['referencia'];
            $descricao       = $resultado['descricao'];
            $nota_fiscal     = $resultado['nota_fiscal'];
            $consumidor_nome = $resultado['consumidor_nome'];
            $fabrica_nome    = $resultado['nome'];
            $fabrica         = $resultado['fabrica'];
            $codigo_posto    = $resultado['codigo_posto'];

            if($fabrica == 1) {
                $sua_os = $codigo_posto."".$sua_os;
            }

            echo ($i >0) ? ",": "";
            echo "{'fabrica':'$fabrica','fabrica_nome':'$fabrica_nome','os':'$os','sua_os': '$sua_os','serie':'$serie','nota_fiscal':'$nota_fiscal','data_abertura':'$data_abertura','data_fechamento':'$data_fechamento','consumidor':'$consumidor_nome','produto':'$referencia-$descricao'}";
            $i++;
        }
        echo "] }";
    }else{
        echo "{'total':'0','sucesso':'false'}";
    }

    exit;
}

ob_start();
?>
<link rel="stylesheet" type="text/css" href="css/css/ext-all.css"/>
<style>
.Conteudo {
    font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
}

</style>

<script type="text/javascript" src="js/jquery-1.3.2.js"></script>
<script type="text/javascript" src="js/ext-jquery-adapter.js"></script>
<script type="text/javascript" src="js/ext-all3.js"></script>
<script>

Ext.onReady(function(){
    Ext.QuickTips.init();

    var mes = new Ext.data.SimpleStore({
        fields:['valor', 'texto'],
        data: [['01', 'Janeiro'], ['02', 'Fevereiro'], ['03', 'Março'], ['04', 'Abril'], ['05', 'Maio'], ['06', 'Junho'], ['07', 'Julho'], ['08', 'Agosto'], ['09', 'Setembro'], ['10', 'Outubro'], ['11', 'Novembro'], ['12', 'Dezembro']]
    });

    var ano = new Ext.data.SimpleStore({
        fields:['id', 'value'],
        data: [
            <?
            for($i = date("Y"); $i > 2003; $i--){
                echo ($i <>date("Y")) ? "," : "";
                echo "['$i', '$i']";
            }
        ?>
        ]
    })

    function linkOs(valor, p,record){
        return String.format(
            '<b><a href="os_press.php?os={1}&lu_os=sim&lu_fabrica={2}" target="_blank">{0}</a></b>',valor, record.id,record.data.fabrica
        );
    }

    function exportaExcel(){
        var campos= pesquisa.form.getValues();
        Ext.Ajax.request({
            waitMsg: 'Por favor, aguarde ...',
            url: '<?$PHP_SELF?>?pesquisa=sim&gera_excel=sim&'+Ext.urlEncode(campos),
            method: 'POST',
            params: {
                pesquisa: 'sim',
                gera_excel: 'sim&'+Ext.urlEncode(campos)
            },
            success: function(response){
                //console.log(response);
                window.open(response.responseText);

            },
            failure: function(response){
                console.log(response);
            }
        });
    }

    var pesquisa = new Ext.FormPanel({
        labelAlign: 'top',
        frame:true,
        title: 'Selecione os parâmetros para a pesquisa.',
        bodyStyle:'padding:5px 5px 0',
        width: 500,
        items: [{
            layout:'column',
            items:[{
                columnWidth:.4,
                layout: 'form',
                items: [{
                    xtype:'textfield',
                    fieldLabel: 'Número da OS',
                    name: 'sua_os',
                    anchor:'95%',
                    id: 'sua_os',
                    tabIndex:1
                }, {
                    xtype:'textfield',
                    fieldLabel: 'CPF Consumidor ',
                    name: 'consumidor_cpf',
                    id:   'consumidor_cpf',
                    anchor:'95%',
                    tabIndex:4
                },{
                        xtype:'combo',
                        name:'mes',
                        id:'mes',
                        fieldLabel:'Mês',
                        mode: 'local',
                        triggerAction: 'all',
                        store:mes,
                        forseSelection:true,
                        valueField: 'valor',
                        displayField: 'texto',
                        width:100,
                        tabIndex:7
                }]
            },{
                columnWidth:.3,
                layout: 'form',
                items: [{
                    xtype:'textfield',
                    fieldLabel: 'Número de Série',
                    name: 'serie',
                    id: 'serie',
                    anchor:'95%',
                    tabIndex:2
                },{
                    xtype:'textfield',
                    fieldLabel: 'Nome Consumidor',
                    name: 'nome_consumidor',
                    id: 'nome_consumidor',
                    anchor:'95%',
                    tabIndex:5
                },{
                    xtype:'combo',
                    name:'ano',
                    id:'ano',
                    triggerAction: 'all',
                    fieldLabel:'Ano',
                    mode: 'local',
                    store:ano,
                    valueField: 'id',
                    displayField: 'value',
                    width:100,
                    tabIndex:8
                }]
            },{
                columnWidth:.3,
                layout: 'form',
                items: [{
                    xtype:'textfield',
                    fieldLabel: 'NF. Compra',
                    name: 'nf_compra',
                    id: 'nf_compra',
                    anchor:'95%',
                    tabIndex:3
                },{},{
                    xtype:'checkbox',
                    name:'os_aberta',
                    id:'os_aberta',
                    fieldLabel:'Apenas OS em aberto',
                    width:90,
                    tabIndex:6
                }]
            }]
        },{
            buttons: [{
                text: 'Pesquisar',
                handler: function(){
                    if ( Ext.get('sua_os').dom.value == "" && Ext.get('nome_consumidor').dom.value == "" && Ext.get('serie').dom.value == "" && Ext.get('nf_compra').dom.value == "" && Ext.get('consumidor_cpf').dom.value == "" &&  Ext.get('mes').dom.value == "" && Ext.get('ano').dom.value == "" ) {
                        Ext.Msg.alert("Erro","Selecione o mes e ano para fazer pesquisa");
                        Ext.get('mes').focus;
                        return false;
                    }

                    if ( Ext.get('nome_consumidor').dom.value != "" && Ext.get('nome_consumidor').dom.value.length < 4) {
                        Ext.Msg.alert("Erro","Digite pelo menos 4 letras para pesquisar pelo nome do consumidor");
                        Ext.get('nome_consumidor').focus;
                        return false;
                    }

                    if ( Ext.get('sua_os').dom.value != "" && Ext.get('sua_os').dom.value.length < 4) {
                        Ext.Msg.alert("Erro","Digite pelo menos 4 letras para pesquisar pelo número de OS");
                        Ext.get('sua_os').focus;
                        return false;
                    }

                    if ( Ext.get('serie').dom.value != "" && Ext.get('serie').dom.value.length < 5) {
                        Ext.Msg.alert("Erro","Digite no mínimo 5 letras para número de série");
                        Ext.get('serie').focus;
                        return false;
                    }

                    var valores= pesquisa.form.getValues();
                    $('#resultado').html('');
                    var store = new Ext.data.Store({
                        url: '<?$PHP_SELF?>?pesquisa=sim&'+Ext.urlEncode(valores),
                        reader: new Ext.data.JsonReader({
                            root: 'resultado',
                            totalProperty: 'total',
                            idProperty: 'os',
                            successProperty: "sucesso"
                        }, ['fabrica','fabrica_nome','sua_os','serie','nota_fiscal',{name: 'data_abertura', type: 'date',dateFormat: 'Y-m-d'},{name: 'data_fechamento', type: 'date',dateFormat: 'Y-m-d'},'consumidor','produto'])
                    });

                    store.load({params:{start:0, limit:30},callback: function(r,options,success){
                        if(success==false){
                            Ext.Msg.alert("Mensagem","Nenhum resultado encontrado");
                        }
                    }});

                    var resultado = new Ext.grid.GridPanel({
                        store: store,
                        tbar: ['',{
                            text: 'Gerar Excel',
                            tooltip: 'Exportar para Excel',
                            handler: exportaExcel,
                            //iconCls:'excel',
                          },''],
                        autoDestroy: true,
                        columns: [
                            {
                                header: "Fábrica",
                                width: 130,
                                sortable: true,
                                dataIndex: 'fabrica_nome'
                            },{
                                id:'os',
                                header: "OS",
                                width: 130,
                                sortable: true,
                                dataIndex: 'sua_os',
                                renderer: linkOs
                            },{
                                header: "SÉRIE",
                                width: 100,
                                align:'center',
                                sortable: true,
                                dataIndex: 'serie'
                            },{
                                header: "NF",
                                width: 80,
                                align:'center',
                                sortable: true,
                                dataIndex: 'nota_fiscal'
                            },{
                                id:'data_abertura',
                                header: "AB",
                                align:'center',
                                width: 50,
                                sortable: true,
                                dataIndex: 'data_abertura',
                                renderer: Ext.util.Format.dateRenderer('d/m')
                            },{
                                header: "FC",
                                width: 50,
                                align:'center',
                                sortable: true,
                                dataIndex: 'data_fechamento',
                                renderer: Ext.util.Format.dateRenderer('d/m')
                            },{
                                header: "CONSUMIDOR",
                                width: 160,
                                sortable: true,
                                dataIndex: 'consumidor'
                            },{
                                header: "PRODUTO",
                                width: 210,
                                sortable: true,
                                dataIndex: 'produto',
                                id:'produto'
                            }
                        ],
                        closable: true,
                        stripeRows: true,
                        autoExpandColumn: 'produto',
                        autoHeight:true,
                        width:900,
                        disableSelection:true,
                        title:'Resultado',
                        footer: true,
                        viewConfig: {
                            forceFit:true,
                            enableRowBody:true
                        },

                        bbar: new Ext.PagingToolbar({
                            pageSize: 30,
                            store: store,
                            displayInfo: true,
                            displayMsg: 'Total de Resultado : {2}',
                            emptyMsg: "Nenhum resultado encontrado"
                        })
                    });
                    resultado.render('resultado');
                }
            }]
        }]
    });

    pesquisa.render('consulta');

});
</script>
<?php
$headerHTML = ob_get_clean();
include_once 'cabecalho.php';
?>
<br>
<div id='resultado'></div>
<br/>
<div id='consulta'></div>
<?php
include_once "rodape.php";

