<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
    $tipo_busca = $_GET["busca"];

    if (strlen($q)>2){
        $sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
                FROM tbl_posto
                JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

        if ($tipo_busca == "codigo"){
            $sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
        }else{
            $sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
        }

        $res = pg_query($con,$sql);
        if (pg_num_rows ($res) > 0) {
            for ($i=0; $i<pg_num_rows ($res); $i++ ){
                $cnpj           = trim(pg_fetch_result($res,$i,cnpj));
                $nome           = trim(pg_fetch_result($res,$i,nome));
                $codigo_posto   = trim(pg_fetch_result($res,$i,codigo_posto));
                echo "$codigo_posto|$nome|$cnpj";
                echo "\n";
            }
        }
    }
    exit;
}

if(strlen($_GET['lancamento']) > 0){
    $lancamento = $_GET['lancamento'];

    $sql = " INSERT INTO tbl_extrato_lancamento_excluido (
                extrato_lancamento,
                posto             ,
                fabrica           ,
                lancamento        ,
                descricao         ,
                debito_credito    ,
                historico         ,
                valor             ,
                competencia_futura,
                data_lancamento   ,
                data_exclusao     ,
                admin
            ) SELECT extrato_lancamento,
                     posto             ,
                     fabrica           ,
                     lancamento        ,
                     case when descricao is null  then ' ' when descricao is not null then descricao end,
                     debito_credito    ,
                     historico         ,
                     valor             ,
                     competencia_futura,
                     data_lancamento   ,
                     current_timestamp ,
                     $login_admin
            FROM tbl_extrato_lancamento
            WHERE extrato_lancamento = $lancamento;

            UPDATE tbl_extrato_lancamento set fabrica=0 WHERE extrato_lancamento= $lancamento;

            SELECT fn_calcula_extrato($login_fabrica,extrato)
            FROM tbl_extrato_lancamento
            WHERE extrato_lancamento= $lancamento
            AND   extrato IS NOT NULL;
            ";
    //die(nl2br($sql));
    $res = pg_query($con,$sql);
    echo (strlen(pg_errormessage($con)) == 0) ? "OK" : "Erro";
    exit;
}

if($_POST['tipo'] == "gravarData"){
    $extrato_lancamento = $_POST['lancamento_data_validade'];
    $data_validade      = $_POST['data_validade'];
    
    $separa = explode("/",$data_validade);
    $data_validade = $separa[1]."-".$separa[0]."-01";
    
    $sql = "UPDATE  tbl_extrato_lancamento
            SET     competencia_futura = '$data_validade'
            WHERE   extrato_lancamento = $extrato_lancamento
    ";
    $res = pg_query($con,$sql);
    echo (strlen(pg_errormessage($con)) == 0) ? "OK" : "Erro";
    exit;
}

if($_POST['ajax'] == 'exclusao') {
    $lancamento = $_POST['lancamento'];

    if($login_fabrica == 74){
        $res = pg_query($con,"BEGIN TRANSACTION");

        $sql = "
            DELETE  FROM tbl_extrato_lancamento
            WHERE   extrato_lancamento = $lancamento
        ";
        $res = pg_query($con,$sql);
        if(!pg_last_error($con)){
            $res = pg_query($con,"COMMIT TRANSACTION");
            $retorno = array("resultado"=>"ok");
            echo json_encode($retorno);
        }else{
            $res = pg_query($con,"ROLLBACK TRANSACTION");
            echo "erro: ".pg_last_error($con);
        }
    }else{
        echo "erro: Fábrica não aceita exclusão";
    }
    exit;
}

if($_POST['ajax']=='sim') {

    header( 'Content-type: text/html; charset=iso-8859-1'); // Corrigindo bug de encoding ao chamar ajax

    $data_inicial = $_REQUEST["data_inicial"];
    $data_final = $_REQUEST["data_final"];

    if(empty($data_inicial) OR empty($data_final)){
        $erro = "Data Inválida";
    }

    if(strlen($erro)==0){
        list($di, $mi, $yi) = explode("/", $data_inicial);
        if(!checkdate($mi,$di,$yi))
            $erro = "Data Inválida";
    }

    if(strlen($erro)==0){
        list($df, $mf, $yf) = explode("/", $data_final);
        if(!checkdate($mf,$df,$yf))
            $erro = "Data Inválida";
    }

    if(strlen($erro)==0){
        $aux_data_inicial = "$yi-$mi-$di";
        $aux_data_final = "$yf-$mf-$df";
    }
    if(strlen($erro)==0){
        if(strtotime($aux_data_final) < strtotime($aux_data_inicial)
        or strtotime($aux_data_final) > strtotime('today')){
            $erro = "Data Inválida";
        }
    }

    $tipo = $_POST['tipo'];
    $cond_1 = " 1=1 ";
    if(strlen($tipo)>0){
        $cond_1 = " tbl_extrato_lancamento.lancamento = '$tipo' ";
    }

    $codigo_posto = $_POST["codigo_posto"];
    $cond_2 = " 1=1 ";
    if(strlen($codigo_posto) > 0){
        $cond_2 = " tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
    }

    $tipo_lancamento = $_POST["tipo_lancamento"] ;
    if($tipo_lancamento == 'lancamento_excluido') {
        $excluido = "_excluido" ;
        $sql_join = "";
        $sql_valor = ",TO_CHAR(tbl_extrato_lancamento_excluido.data_exclusao,'DD/MM/YY') AS data_exclusao ";
    }else{
        $sql_join = " LEFT JOIN tbl_extrato USING (extrato) LEFT JOIN tbl_extrato_extra ON tbl_extrato.extrato = tbl_extrato_extra.extrato ";
        $sql_valor = ",tbl_extrato.extrato, TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao,                 tbl_extrato.protocolo ";
    }

    $debito_credito = $_POST["debito_credito"] ;
    $cond_4 = ($debito_credito == 'D') ? " AND tbl_lancamento.debito_credito ='D' " : (($debito_credito == 'C') ? " AND tbl_lancamento.debito_credito ='C'" : "");

    $tipo = $_POST['tipo'];
    $cond_1 = " 1=1 ";

    if($login_fabrica == 1) {
        $sql_valor = " ,(select obs from tbl_os_sedex where tbl_os_sedex.os_sedex = tbl_extrato_lancamento.os_sedex limit 1) as obs ";
    }

    if(strlen($tipo)>0){
        $cond_1 = " tbl_extrato_lancamento$excluido.lancamento = '$tipo' ";

        if($tipo == '000') {
            $sql_valor = " , x.obs ";
            $join = " JOIN tbl_os_sedex x ON tbl_extrato_lancamento.os_sedex = x.os_sedex ";
            $cond_1 = " tbl_extrato_lancamento.lancamento = 42 AND x.obs like 'Débito gerado por troca de produto na OS%' ";
        }
    }

    $tipo_lancamento = (!in_array($tipo_lancamento,array('sem_extrato','lancamento_excluido'))) ? "" : $tipo_lancamento;

     if(!empty($_POST['marca'])){

        $cond_5 = " AND tbl_extrato_lancamento.marca = '$marca' " ;

     }

     if($login_fabrica == 104){
        $left_marca = " LEFT JOIN tbl_marca ON tbl_extrato_lancamento.marca = tbl_marca.marca AND tbl_marca.fabrica = $login_fabrica ";
        $campo_marca = ", tbl_marca.nome AS marca_nome ";
     }

    if (strlen($erro) > 0) {
        $data_inicial    = trim($_POST["data_inicial"]);
        $data_final      = trim($_POST["data_final"]);
        $codigo_posto    = trim($_POST["codigo_posto"]);
        $tipo            = trim($_POST["tipo"]);
        $tipo_lancamento = trim($_POST["tipo_lancamento"]);

        //$msg  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
        $msg .= $erro;

    }else $listar = "ok";

    if ($listar == "ok" and empty($erro) ) {

        $tipo_data = " tbl_extrato_lancamento$excluido.data_lancamento ";
        if($login_fabrica==1) {
            $tipo_data = " tbl_extrato_financeiro.data_envio ";
        }
        if($login_fabrica == 1){
            $sql = "SELECT tbl_posto_fabrica.codigo_posto                                         ,
                    tbl_posto.nome                                                                ,
                    tbl_extrato.extrato                                                           ,
                    TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao                ,
                    tbl_extrato.protocolo                                                         ,
                    tbl_extrato_lancamento.valor                                                  ,
                    tbl_extrato_lancamento.descricao                                              ,
                    tbl_extrato_lancamento.extrato_lancamento                                     ,
                    tbl_lancamento.debito_credito                                         ,
                    tbl_extrato_lancamento.os_sedex                                               ,
                    TO_CHAR(tbl_extrato_lancamento.data_lancamento,'DD/MM/YY') AS data_lancamento ,
                    tbl_extrato_financeiro.data_envio
                    $sql_valor
                FROM tbl_extrato
                JOIN tbl_extrato_financeiro USING (extrato)
                JOIN tbl_extrato_lancamento USING (extrato)
                JOIN tbl_lancamento USING (lancamento)
                JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto
                JOIN tbl_posto_fabrica ON tbl_extrato.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                LEFT JOIN tbl_extrato_extra ON tbl_extrato.extrato = tbl_extrato_extra.extrato
                $join
                WHERE tbl_extrato.fabrica = $login_fabrica
                AND NOT (tbl_extrato_financeiro.data_envio IS NULL)
                AND $tipo_data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
                AND $cond_1
                AND $cond_2
                $cond_4
                ORDER BY tbl_posto_fabrica.codigo_posto";
        }else{

            $sql = "SELECT tbl_posto_fabrica.codigo_posto                           ,
                    tbl_posto.nome                                                  ,
                    tbl_admin.login                                                 ,
                    tbl_extrato_lancamento$excluido.valor                           ,
                    tbl_extrato_lancamento$excluido.descricao                       ,
                    tbl_extrato_lancamento$excluido.historico                       ,
                    tbl_extrato_lancamento$excluido.extrato_lancamento              ,
                    tbl_lancamento.debito_credito                  ,
                    TO_CHAR(tbl_extrato_lancamento$excluido.data_lancamento,'DD/MM/YY') AS data_lancamento,
                    TO_CHAR(tbl_extrato_lancamento.competencia_futura,'MM/YYYY') AS data_validade
                    $sql_valor
                    $campo_marca
                FROM tbl_extrato_lancamento$excluido
                $sql_join
                JOIN tbl_lancamento USING (lancamento)
                JOIN tbl_posto         ON tbl_extrato_lancamento$excluido.posto = tbl_posto.posto
                JOIN tbl_posto_fabrica ON tbl_extrato_lancamento$excluido.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                JOIN tbl_admin         ON tbl_admin.admin = tbl_extrato_lancamento$excluido.admin
                $left_marca
                WHERE tbl_extrato_lancamento$excluido.fabrica = $login_fabrica
                AND tbl_extrato_lancamento.extrato IS NULL
                AND $tipo_data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
                AND NOT (tbl_extrato_lancamento$excluido.admin is null )
                AND $cond_1
                AND $cond_2
                $cond_4
                $cond_5
                ORDER BY tbl_posto_fabrica.codigo_posto";
        }

        $res = pg_query ($con,$sql);

        if (pg_num_rows($res) > 0) {
            $total = 0;

            $resposta  .=  "<b>Resultado de pesquisa entre os dias $data_inicial e $data_final <br>";
            $resposta  .=  ($tipo_lancamento=='lancamento_excluido') ? "Lançamento Excluído" : (($tipo_lancamento=='sem_extrato') ? "Lançamento em nenhum extrato" : "");
            $resposta  .=  "</b>";
            $resposta  .=   "<table  cellpadding='2' cellspacing='0' align='center' width='700px' >";
            $resposta  .=   "<tr>";
            $resposta  .=   "<td><BR></td>";
            $resposta  .=   "</tr>";
            $resposta  .=   "<tr>";
            $resposta  .=   "<td bgcolor='#ffcccc' width='20' align='left'>&nbsp;</td>";
            $resposta  .=   "<td style='font-size: 10px; text-align: left;' align='left'>Débito</td>";
            $resposta  .=   "</tr>";
            $resposta  .=   "</table>";
            $resposta  .=  "<br><br>";
            $resposta  .=  "<table border='0' width='700' cellpadding='2' cellspacing='1' class='tabela'>";
            $resposta  .=  "<TR class='Titulo' height='25'>";
            $resposta  .=  (in_array($login_fabrica,array(3,74))) ? "<th>Ação</th>" : "";
            $resposta  .=  "<th>Data</th>";
            $resposta  .=  "<th>Posto</th>";
            $resposta  .=  "<th>Dt. Validade</th>";
            if($login_fabrica == 104){
            $resposta  .=  "<th>Marca</th>";
            }
            $resposta  .=  "<th>Valor</th>";
            $resposta  .=  "<th>Descrição</th>";
            $resposta  .=  "</TR>";
            for ($i=0; $i<pg_num_rows($res); $i++){
                $codigo_posto    = trim(pg_fetch_result($res,$i,'codigo_posto'))   ;
                $nome            = trim(pg_fetch_result($res,$i,'nome'))           ;
                $descricao       = trim(pg_fetch_result($res,$i,'descricao'))      ;
                $valor           = trim(pg_fetch_result($res,$i,'valor'))          ;
                $debito_credito  = trim(pg_fetch_result($res,$i,'debito_credito')) ;
                $data_lancamento    = trim(pg_fetch_result($res,$i,'data_lancamento'));
                $data_validade    = trim(pg_fetch_result($res,$i,'data_validade'));
                $extrato_lancamento = trim(pg_fetch_result($res,$i,'extrato_lancamento'));

                $descricao = (!empty($descricao )) ? $descricao : trim(pg_fetch_result($res,$i,'historico'));
                if($login_fabrica == 1) {
                    $obs = pg_fetch_result($res,$i,'obs');
                }

                if(strpos($obs,'Débito gerado por troca de produto na OS') !==false) {
                    if($tipo !='000') {
                        continue;
                    }
                }

                if($login_fabrica == 104){
                    $marca_nome = trim(pg_fetch_result($res,$i,'marca_nome'));
                }


                if($login_fabrica == 1) $extrato = $protocolo;

                if($debito_credito =='D') {
                    $style = " style='background-color:#ffcccc;' ";
                }else{
                    $style = "";
                }

                $cor = ($i%2) ? '#F7F5F0' : '#F1F4FA';

                $resposta  .=  "<TR bgcolor='$cor'class='Conteudo' $style>";
                if(in_array($login_fabrica,array(3,74))) {
                    $resposta  .=  ($tipo_lancamento <>'lancamento_excluido') ? "<TD id='extrato_$extrato_lancamento'><a href=\"javascript: excluirLancamento('$extrato_lancamento','extrato_$extrato_lancamento')\" ><img src='imagens/btn_x.gif'  border='0'></a></TD>" : "<td></td>";
                }

                $resposta  .=  "<TD align='center'>$data_lancamento</TD>";
                $resposta  .=  "<TD align='left' nowrap>$codigo_posto - $nome</TD>";
                $resposta  .= "<TD align='center'>";
                $resposta  .= "<a href=\"javascript: alterarDataValidade('$data_validade','$extrato_lancamento')\" >";
                $resposta  .= $data_validade;
                $resposta  .= "</a>";
                $resposta  .= "</TD>";

                if($login_fabrica == 104){
                    $resposta  .= "<TD align='center'>";
                    $resposta  .= $marca_nome;
                    $resposta  .= "</TD>";
                }
                $resposta  .=  "<TD >R$". number_format($valor,2,",",".") ." </TD>";
                $resposta  .=  ($tipo_lancamento =='lancamento_excluido') ? "<TD align='center'>$data_exclusao</TD>" : "";
                $resposta  .=  ($tipo == '000') ? "<TD align='right'>$obs</TD>":"<TD align='right'>$descricao</TD>";

                $resposta  .=  "</TR>";

                if($login_fabrica == 3){
                    $resposta  .=  "<TR  bgcolor='$cor'>";
                    $resposta  .=  "<td colspan='100%' style='text-align:left;padding-left:10px;padding-right:10px' align='top'>";
                    $resposta  .=  "<I>Histórico: $historico</I>";
                    $resposta  .=  "</TD>";
                    $resposta  .=  "</TR>";
                }

                $total = $valor + $total;

            }

            $resposta .= " </TABLE>";

            $resposta .=  "<br>";
            $resposta .=  "<hr width='600'>";
            $resposta .=  "<br>";

            $data_inicial = trim($_POST["data_inicial"]);
            $data_final   = trim($_POST["data_final"]);
            $linha        = trim($_POST["linha"]);
            $estado       = trim($_POST["estado"]);
            $criterio     = trim($_POST["criterio"]);
        }else{
            $resposta .= "<br>";
            $resposta .= "<b>Nenhum resultado encontrado entre $data_inicial e $data_final <br>";
            $resposta .= ($tipo_lancamento=='lancamento_excluido') ? "Lançamento Excluído" : (($tipo_lancamento=='sem_extrato') ? "Lançamento em nenhum extrato" : "");
            $resposta .= "</b>";
        }
        $listar = "";
    }
    if (strlen($erro) > 0) {
        echo $msg;
    }else{
        echo $resposta;
    }
    exit;

    flush();
}

$layout_menu = "financeiro";
$title = "RELATÓRIO DE EXTRATOS AVULSOS LANÇADOS";

include "cabecalho.php";

?>
<style>
.Titulo {
    text-align: center;
    font-family: Arial;
    font-size: 10px;
    font-weight: bold;
    color: #FFFFFF;
    background-color: #485989;
}
.Conteudo {
    font-family: Arial;
    font-size: 10px;
    font-weight: normal;
}
.ConteudoBranco {
    font-family: Arial;
    font-size: 9px;
    color:#FFFFFF;
    font-weight: normal;
}
.Mes{
    font-size: 9px;
}
.Caixa{
    BORDER-RIGHT: #6699CC 1px solid;
    BORDER-TOP: #6699CC 1px solid;
    FONT: 8pt Arial ;
    BORDER-LEFT: #6699CC 1px solid;
    BORDER-BOTTOM: #6699CC 1px solid;
    BACKGROUND-COLOR: #FFFFFF;
}
.Exibe{
    font-family: Arial, Helvetica, sans-serif;
    font-size: 8 px;
    font-weight: none;
    color: #000000;
    text-align: center;
}
.Erro{
    BORDER-RIGHT: #990000 1px solid;
    BORDER-TOP: #990000 1px solid;
    FONT: 10pt Arial ;
    COLOR: #ffffff;
    BORDER-LEFT: #990000 1px solid;
    BORDER-BOTTOM: #990000 1px solid;
    BACKGROUND-COLOR: #FF0000;
}
.Carregando{
    TEXT-ALIGN: center;
    BORDER-RIGHT: #aaa 1px solid;
    BORDER-TOP: #aaa 1px solid;
    FONT: 10pt Arial ;
    COLOR: #000000;
    BORDER-LEFT: #aaa 1px solid;
    BORDER-BOTTOM: #aaa 1px solid;
    BACKGROUND-COLOR: #FFFFFF;
    margin-left:20px;
    margin-right:20px;
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

.subtitulo{

color: #7092BE
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}

.msg_sucesso{
background-color: green;
font: bold 16px "Arial";
color: #FFFFFF;
text-align:center;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:solid #596d9b;
    border-width:1px 0 1px 0;
}

</style>

<?
include "javascript_calendario_new.php";
include "../js/js_css.php";
?>

<script language="javascript" src="js/effects.explode.js"></script>
<link rel="stylesheet" type="text/css" href="css/jquery.alerts.css" />
<script language="javascript" src="js/jquery.alerts.js"></script>
<script language='javascript'>

function Exibir (componente,componente_erro,componente_carregando,fabrica) {
    var var1 = document.frm_relatorio.data_inicial.value;
    var var2 = document.frm_relatorio.data_final.value;
    var var4 = document.frm_relatorio.tipo.value;
    var var5 = document.frm_relatorio.codigo_posto.value;
    var var6 = $('input[name=tipo_lancamento]:checked').val() ;
    var var7 = $('input[name=debito_credito]:checked').val() ;
    if (typeof document.frm_relatorio.marca != 'undefined') // Corrigindo bug, campo existe apenas para vonder/dwt
        var var8 = document.frm_relatorio.marca.value;
    else
        var var8 = '';

    var curDateTime = new Date();
    $.ajax({
        type: "POST",
        dataType:"html",
        url: "<?=$PHP_SELF?>",
        data: {
            ajax:"sim",
            data_inicial:var1,
            data_final:var2,
            tipo:var4,
            codigo_posto:var5,
            tipo_lancamento:var6,
            debito_credito:var7,
            marca:var8,
            hora:curDateTime
        },
        beforeSend: function(){
            $('#consultar').hide('');
            $('#dados').html("<br>&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br>").removeClass("Erro");
        },
    })
    .done(function(data){
        $('#consultar').show('');
        $('#dados').html('');
        $('#dados_resultado').html(data);
        $('input[name=tipo_lancamento]:checked').attr("checked",true);
        $('input[name=debito_credito]:checked').attr("checked",true);
    })
    .fail(function(){
        $('#dados').html("<br> Favor, corrigir os dados da pesquisa").addClass("Erro");
        $('input[name=tipo_lancamento]:checked').attr("checked",false);
    });
}

function alterarDataValidade(data_validade,id){
    var extrato_lancamento = document.getElementById(id);
    var data = data_validade.split("/");
    var dataAno = "20"+data[1];
    var novaData = data[0]+"/"+dataAno;

    $('#altera_data').show('');
    $('input[name=data_validade]').attr("value",data_validade);
    $('input[name=lancamento_data_validade]').attr("value",id);
}

function cancelarValidade(){
    $('input[name=data_validade]').attr("value","");
    $('input[name=lancamento_data_validade]').attr("value","");
    $('#altera_data').hide('');
}

function excluirLancamento(extrato,linha){
    if(confirm("Deseja continuar?")){
        var guarda = $("#"+linha).parent().clone();
        $.ajax({
            type:"POST",
            url:"<?=$PHP_SELF?>",
            dataType:"JSON",
            data:{
                ajax:"exclusao",
                lancamento:extrato
            },
            beforeSend:function(){
                $("#"+linha).parent().html("<td colspan='6'>"
                +"Aguarde enquanto cancelamos o lançamento do avulso"
                +"</td>"
                )
                .css({
                    "background-color":"#D3D3D3",
                    "font-size":"12"
                })
                .addClass("remover");
            }
        })
        .done(function(data){
            if(data["resultado"] == "ok"){
                alert("Lançamento "+extrato+" excluído com sucesso");
                $(".remover").html("");
            }
        })
        .fail(function(){
            alert("Não foi possível excluir o lançamento");
            $(".remover").html(guarda);
        });
    }
}

function gravarValidade(lancamento_data_validade,data_validade){
    var vetData = data_validade.value.split("/");

    if (vetData.length != 2) {
        alert("Ocorreu um erro ao gravar a nova data de validade");
        return false;
    }

    var curdate = new Date();
    var dia = curdate.getDate();
    var strData = vetData[0] + ' ' + dia + ', ' + vetData[1];

    var vetValidade = new Date(strData);
    
    if (vetValidade > curdate) {
        $.ajax({
            type:"POST",
            url:"<?=$PHP_SELF?>",
            data:{
                tipo:"gravarData",
                lancamento_data_validade : lancamento_data_validade.value,
                data_validade : data_validade.value
            }
        })
        .done(function(resposta){
            if(resposta == "OK"){
                $('input[name=data_validade]').attr("value","");
                $('input[name=lancamento_data_validade]').attr("value","");
                $('#altera_data').hide('');
                Exibir('dados','erro','carregando','<?=$login_fabrica?>');
            }else{
                alert("Ocorreu um erro ao gravar a nova data de validade");
            }
        });
    }else{
        alert("Data escolhida é menor que atual");
        document.frm_relatorio.data_validade.value = "";
        document.frm_relatorio.data_validade.focus();
    }
}
</script>

<? include "javascript_pesquisas.php" ?>

<script type="text/javascript" charset="utf-8">
    $(function(){
        $('#data_inicial').datepick({startDate:'01/01/2000'});
        $('#data_final').datepick({startDate:'01/01/2000'});
        $("#data_inicial").mask("99/99/9999");
        $("#data_final").mask("99/99/9999");
        $("#data_validade").mask("99/9999");
    });
</script>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />

<script language="JavaScript">
$().ready(function() {

    function formatItem(row) {
        return row[0] + " - " + row[1];
    }

    function formatResult(row) {
        return row[0];
    }

    /* Busca pelo Código */
    $("#codigo_posto").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
        minChars: 3,
        delay: 150,
        width: 350,
        matchContains: true,
        formatItem: formatItem,
        formatResult: function(row) {return row[0];}
    });

    $("#codigo_posto").result(function(event, data, formatted) {
        $("#posto_nome").val(data[1]) ;
    });

    /* Busca pelo Nome */
    $("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
        minChars: 3,
        delay: 150,
        width: 350,
        matchContains: true,
        formatItem: formatItem,
        formatResult: function(row) {return row[1];}
    });

    $("#posto_nome").result(function(event, data, formatted) {
        $("#codigo_posto").val(data[0]) ;
    });

});
</script>

<form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
<table width='700px' cellpadding='0' class="msg_erro" cellspacing='0' align='center'>
    <tr>
        <td>
            <?php
            echo "<div id='dados'></div>";
            ?>
        </td>
    </tr>
</table>
<table width='700px' class='formulario' cellpadding='0' cellspacing='0' align='center'>
    <tr>
        <td>
            <div class="texto_avulso" style="width:700px;">Serão mostrados somente os extratos que foram enviados para o financeiro.</div>
        </td>
    </tr>
    <tr>
        <td class='titulo_tabela'>Parâmetros de Pesquisa</td>
    </tr>

    <tr>
        <td valign='bottom'>

            <table width='100%' border='0' cellspacing='1' cellpadding='2' >

                <tr>
                    <td width="100">&nbsp;</td>
                    <td align='left'>Data Inicial<br>
                        <input class='frm' type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" >
                    </td>
                    <td align='left'>Data Final<br>
                        <input class='frm' type="text" name="data_final" id="data_final" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_final) > 0) echo $data_final;  ?>" >
                    </td>
                    <td width="10">&nbsp;</td>
                </tr>
                <tr>
                    <td width="10">&nbsp;</td>
                    <td align='left' nowrap>Cod Posto<br>
                        <input class='frm' type="text" name="codigo_posto" id="codigo_posto" size="12"  value="<? echo $codigo_posto ?>" class="Caixa">
                        <img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'codigo')">
                    </td>
                    <td align='left' nowrap>Nome do Posto<br>
                        <input  class='frm' type="text" name="posto_nome" id="posto_nome" size="30"  value="<?echo $posto_nome?>" class="Caixa">
                        <img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'nome')">
                    </td>
                    <td width="10">&nbsp;</td>
                </tr>
    </table>

    <table width='700px' class='formulario' cellpadding='0' cellspacing='0' align='center'>
                <tr >
                    <td width="108">&nbsp;</td>
                    <td align='left'nowrap>Tipo<br>
                    <select class='frm' name='tipo' size='1' style='width:320px'>
                    <option></option>
                    <?
                    $sql = "select distinct lancamento ,descricao
                            from tbl_lancamento
                            where fabrica = $login_fabrica
                            AND   ativo";
                    $res = pg_query($con,$sql);
                    if(pg_num_rows($res)>0){
                        for($i=0;pg_num_rows($res)>$i;$i++){
                            $extrato_lancamento = pg_fetch_result($res,$i,lancamento);
                            $descricao = pg_fetch_result($res,$i,descricao);
                            echo "<option value='$extrato_lancamento'>$descricao</option>";
                        }

                        if($login_fabrica == 1) {
                            echo "<option value='000'>Débito gerado por troca de produto na OS</option>";
                        }
                        ?>
                    </select>
                    </td>
                    <td width="10">&nbsp;</td>
                </tr>
    </table>

    <? if($login_fabrica == 104){ ?>
        <table width='700px' class='formulario' cellpadding='0' cellspacing='0' align='center'>
            <tr>
                <td width="108">&nbsp;</td>
                    <?
                        $sqlM = "SELECT tbl_marca.marca,tbl_marca.nome FROM tbl_marca WHERE tbl_marca.fabrica = $login_fabrica AND tbl_marca.nome in('DWT','OVD') ORDER BY tbl_marca.nome";
                        $resM = pg_exec($con,$sqlM);

                        if(pg_num_rows($resM) > 0){
                            echo "<td align='left'nowrap>";
                            echo "Grupo <br/><select name='marca' class='frm'>";
                            echo "<option value=''>Todos Grupos</option>";
                            for($i = 0; $i < pg_num_rows($resM); $i++){
                                $marca = pg_result($resM,$i,'marca');
                                $nome_marca = pg_result($resM,$i,'nome');
                                $selected = ($marca == $marca_aux) ? "SELECTED" : "";

                                echo "<option value='".$marca."' $selected>".$nome_marca."</option>";
                            }
                            echo "</select>";
                            echo "</td>";
                        }
                    ?>
            </tr>
        </table>

    <? }    ?>

    <?}?>

    <table width='700px' class='formulario' cellpadding='0' cellspacing='0' align='center'>
                <tr>
                    <td width="20px">&nbsp;</td>
                    <td colspan='100%' align='left' >
                        <fieldset  style='margin-left: 86px; padding: 4px 0'>
                            <legend style='margin-left: 25px'>Débito ou Crédito</legend>
                            <label for='credito' style='margin-left: 65px;'>
                                <input class='frm' type='radio' name='debito_credito' value='C' id='credito'>Crédito
                            </label>
                            &nbsp;&nbsp;
                            <label for='debito'>
                                <input type='radio' name='debito_credito' value='D' id='debito'>Débito
                            </label>
                            <label for='qualquer'>
                                <input type='radio' name='debito_credito' value='todos' checked id='qualquer'>Todos
                            </label>
                        </fieldset>
                    </td>
                    <td width="300px">&nbsp;</td>
                </tr>
            </table><br>
            <!-- HD 285060: Coloquei "return false;" ao final do evento onclick -->
            <!--<input type='image' src='imagens_admin/btn_pesquisar_400.gif' class='frm' onclick="javascript:Exibir('dados','erro','carregando','<?=$login_fabrica?>'); return false;" style="cursor:pointer " value='Consultar' id='consultar'>-->
            <input type="submit" name="consultar" onclick="javascript:Exibir('dados','erro','carregando','<?=$login_fabrica?>'); return false;" style="cursor:pointer " value='Pesquisa' id='consultar'>
        </td>
    </tr>
</table>
<br>
<div id="altera_data" style="display:none">
    <table width='700px' class='formulario' cellpadding='0' cellspacing='0'>
        <tr>
            <td class='titulo_tabela'>Alteração de Data de Validade</td>
        </tr>
        <tr>
            <td align='left' style="padding-left:120px;">
                Data Validade
            </td>
        </tr>
        <tr>
            <td align='left' style="padding-left:120px;">
                <input type="hidden" name="lancamento_data_validade" id="lancamento_data_validade" value="" />
                <input class='frm' type="text" name="data_validade" id="data_validade" size="10" maxlength="7" class='Caixa' value="" >
            </td>
        </tr>
        <tr>
            <td align='center'>
                <input type="button" name="gravar_validade" onclick="javascript:gravarValidade(document.frm_relatorio.lancamento_data_validade,document.frm_relatorio.data_validade); " style="cursor:pointer " value='Gravar' id='gravar_validade'>
                <input type="button" name="cancelar_validade" onclick="javascript:cancelarValidade(); " style="cursor:pointer " value='Cancelar' id='cancelar_validade'>
            </td>
        </tr>
    </table>
</div>
</form>

<table>
    <tr>
        <td>
            <div id='erro' style='position: absolute; top: 150px; left: 80px;opacity:.85;visibility:hidden;' class='Erro'></div>
<div id='carregando' style='position: absolute;visibility:hidden;opacity:.90;' class='Carregando'></div>
        </td>
    </tr>
</table>
<table align='center' >
    <tr>
        <td>
<?

echo "<div id='dados_resultado'></div>";

?>
        </td>
    </tr>
<table>
<p>
<? include "rodape.php" ?>
