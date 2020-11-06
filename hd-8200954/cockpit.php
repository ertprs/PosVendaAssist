<?php
$areaAdmin = (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) ? true : false;

include __DIR__."/dbconfig.php";
include __DIR__."/includes/dbconnect-inc.php";

if ($areaAdmin) {
    $admin_privilegios = "gerencia";
    include __DIR__."/admin/autentica_admin.php";
} else {
    include __DIR__."/autentica_usuario.php";
}

include __DIR__."/funcoes.php";

if ($areaAdmin) {
    include __DIR__."/admin/cabecalho_new.php";
} else {
    include __DIR__."/cabecalho_new.php";
}
?>

<?php

$plugins = array(
   "select2",
   "highcharts",
   "shadowbox",
   "dataTable",
   "mask",
   "datetimepickerbs2",
   "datepicker"
);

include __DIR__."/admin/plugin_loader.php";

global $dbhost, $dbport, $dbnome, $dbusuario, $dbsenha;

use Posvenda\CockpitPosto\Servicos\ServicoDeAgendamento;

$servicoDeAgendamento = new ServicoDeAgendamento($dbnome, $dbhost, $dbport, $dbusuario, $dbsenha);

$primeiroDiaMes       = date('Y-m-01', strtotime(date('Y-m-d')));
$ultimoDiaMes         = date('Y-m-t', strtotime(date('Y-m-d')));


if ($_POST["btn_acao"] == "pesquisar") {
    $os           = $_POST["busca_os"];
    $confirmado   = $_POST["busca_confirmado"];
    $data_inicial = $_POST["busca_data_inicial"];
    $data_final   = $_POST["busca_data_final"];
    $tecnico      = $_POST["busca_tecnico"];
    $busca_tipo_atendimento = $_POST['busca_tipo_atendimento'];

    if (strlen($os) == 0) {
        if(strlen($data_inicial)>0 and $data_inicial <> "dd/mm/aaaa"){
            $xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
            $xdata_inicial = str_replace("'","",$xdata_inicial);
        }else{
            $msg_erro["msg"][]    ="Data Inicial Inválida";
            $msg_erro["campos"][] = "data_inicial";
        }

        if(strlen($data_final)>0 and $data_final <> "dd/mm/aaaa"){
            $xdata_final =  fnc_formata_data_pg(trim($data_final));
            $xdata_final = str_replace("'","",$xdata_final);
        }else{
            $msg_erro["msg"][]    ="Data Final Inválida";
            $msg_erro["campos"][] = "data_final";
        }

        if(empty($msg_erro)){
            $dat = explode ("/", $data_inicial );//tira a barra
            $d = $dat[0];
            $m = $dat[1];
            $y = $dat[2];
            if(!checkdate($m,$d,$y)) {
                $msg_erro["msg"][]    ="Data Inicial Inválida";
                $msg_erro["campos"][] = "data_inicial";
            }
        }
        if(empty($msg_erro)){
            $dat = explode ("/", $data_final );//tira a barra
            $d = $dat[0];
            $m = $dat[1];
            $y = $dat[2];
            if(!checkdate($m,$d,$y)) {
                $msg_erro["msg"][]    ="Data Final Inválida";
                $msg_erro["campos"][] = "data_final";
            }
        } 
    }

    if (count($msg_erro["msg"]) == 0) {
        $cond = [
            "os" => $os,
            "tecnico" => $tecnico,
            "confirmado" => $confirmado,
            "data_inicial" => formata_data($data_inicial),
            "data_final" => formata_data($data_final),
            "busca_tipo_atendimento" => $busca_tipo_atendimento,
            "pesquisa" => true
        ];

        $agendamentos_pesquisa = $servicoDeAgendamento->obtemAgendamentosPorFabrica($login_fabrica, $primeiroDiaMes, $ultimoDiaMes, $cond, $login_posto);
        $agendamentos_pesquisa = json_decode($agendamentos_pesquisa, true);   
    }
} else {
    $agendamentos         = $servicoDeAgendamento->obtemAgendamentosPorFabrica($login_fabrica, $primeiroDiaMes, $ultimoDiaMes, null, $login_posto);   

    $exportacao_pendente = $servicoDeAgendamento->pendenteExportacao($login_fabrica, $login_posto);
}

$tecnicos             = $servicoDeAgendamento->obtemTecnicosPorFabrica($login_fabrica, $login_posto);
$ordensDeServico      = $servicoDeAgendamento->obtemOSPorFabrica($login_fabrica, $login_posto);
$tipo_atendimentos    = $servicoDeAgendamento->obtemTipoAtendimentoPorFabrica($login_fabrica);

//$agendamentos = $servicoDeAgendamento->obtemAgendamentosPorTecnico($login_fabrica, 19066, $primeiroDiaMes, $ultimoDiaMes);

?>

<link rel='stylesheet' href='fullcalendar/fullcalendar.css' />
<script src='fullcalendar/lib/moment.min.js'></script>
<script src='fullcalendar/fullcalendar.js'></script>
<script src='fullcalendar/locale/pt-br.js' charset='utf-8'></script>

<script type="text/javascript">
    
    $(function(){
        $( ".pendente" ).click(function() {
          $( ".pendente2" ).toggle("slow", function() {                
              });
        });
    });

</script>

<style>
    .graficos{
        margin:0 auto;
        width: 90%;
    }
    .modal{
        width: 80%;
        margin-left: -40%;
    }
    .tal{text-align:left;}
    #table_table_length, #table_table_info{
            PADDING-LEFT: 21PX;
    }
    .erro_validacao{
        background: #fbabab !important;
    }
    .error_linha{
        background-color: #f2dede !important;
    }
    #error_area{
        color: #ff0000;
        font-weight: bold;
    }
    #success_area{
        color: green;
        font-weight: bold;
    }
    #calendar{
        max-width: 1200px;
        min-width: 900px;
        margin:0 auto; 
    }
    .mensagem_reagendamento{
        width: 700px;
        text-align: left;
    }
    .pendente i{
        text-align: left;
    }

    .titulo_coluna2{
        background-color: #f2dede;
        color:#b94a48;
        font-size: 14px;
        font-weight: bold;
    }


   /*  .ui-datepicker{
       z-index: 9999999 !important;
   } */
</style>
<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-danger">
        <?=implode("<br />", $msg_erro["msg"])?>
    </div>
<?php
}
?>
<form name="frm_pesquisa" id="frm_pesq" method="POST" class="form-search form-inline" enctype="multipart/form-data" >
    <div id="div_informacoes" class="tc_formulario">
        <div class="titulo_tabela">Pesquisar Agendamentos Realizados</div>
        <br />
        <div class="row-fluid">
            <div class="span2"></div>

            <div class="span3">
                <div class="control-group">
                    <label class="control-label" for="os">OS</label>
                    <div class="controls controls-row" >
                        <div class="span12" >
                            <input type="text" class="span12" name="busca_os" value="<?=getValue('busca_os')?>" />
                        </div>
                    </div>
                </div>
            </div>
            <!-- <div class="span2">
                <div class='control-group'>
                    <label class="control-label" for="status">Confirmado</label>
                    <div class="controls controls-row">
                        <div class='span12'>
                            <select class='frm span12' name='busca_confirmado' >
                                <option value=''>Selecione</option>
                                <option value='sim' <? if ($confirmado == "sim") echo " selected "; ?> >Sim</option>
                                <option value='nao' <? if ($confirmado == "nao") echo " selected "; ?> >Não</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div> -->
            <div class="span5">
                <div class='control-group'>
                    <label class="control-label" for="status">Técnico</label>
                    <div class="controls controls-row">
                        <div class='span12'>
                            <select class='frm span12' name='busca_tecnico' >
                                <option value=''>Selecione</option>
                                <?php foreach ($tecnicos  as $key => $value) {?>
                                    <option value='<?php echo $value["tecnico"];?>' <? if ($value["tecnico"] == $_POST["busca_tecnico"]) echo " selected "; ?> ><?php echo $value["nome"];?></option>
                                <?php }?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2"></div>
        </div>

        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span2">
                <div class="control-group <?=(in_array('data_inicial', $msg_erro['campos'])) ? 'error' : '' ?>" >
                    <label class="control-label" for="data_inicial">Data Inicial</label>
                    <div class="controls controls-row" >
                        <div class="span12" >
                            <input type="text" id="data_inicial" class="span12" name="busca_data_inicial" value="<?=getValue('busca_data_inicial')?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2">
                <div class="control-group <?=(in_array('data_final', $msg_erro['campos'])) ? 'error' : '' ?>" >
                    <label class="control-label" for="data_final">Data Final</label>
                    <div class="controls controls-row" >
                        <div class="span12" >
                            <input type="text" id="data_final" class="span12" name="busca_data_final" value="<?=getValue('busca_data_final')?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class='control-group'>
                    <label class="control-label" for="status">Tipo de Atendimento</label>
                    <div class="controls controls-row">
                        <div class='span12'>
                            <select class='frm span12' name='busca_tipo_atendimento' >
                                <option value=''>Selecione</option>
                                <?php 
                                    foreach($tipo_atendimentos as $tipo){
                                        echo "<option value=$tipo[tipo_atendimento]>$tipo[descricao]</option>";
                                    }
                                ?>                                
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="span2"></div>
        </div>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span8 tac">
                <input type="hidden" name="btn_acao" id="btn_acao" value="">
                
                <button type="button" class="btn btn-default btn_pesquisar" onclick="if ($('#btn_acao').val() == '') { $('#btn_acao').val('pesquisar'); $('form[name=frm_pesquisa]').submit(); } else { alert('Aguarde! A pesquisa está sendo processada.'); return false; }">Pesquisar</button>
            </div>
            <div class="span2"></div>            
        </div>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span8 tac">
                <button type="button" data-toggle="modal" data-target="#modal-adicionar-evento-all" class="btn btn-primary btn-abre-modal-novo" >Novo Agendamento</button>
            </div>
            <div class="span2"></div>
        </div>
    </div>
</form>

<?php if(count(array_filter($exportacao_pendente))>0){ ?>

<div class=" titulo_coluna" style=" font-size:16px; cursor: pointer; line-height: 20px !important; " width="100%"><!--<i class="icon-plus " style="color:#f2dede;"></i>--> Aguardando Envio para o Aplicativo</div>
    <table class='table table-striped table-bordered table-hover' style=' width: 100% !important' >
        <thead>
        <tr>
            <th class="titulo_coluna tac" style="line-height: 20px !important;">OS</th>
            <td class="titulo_coluna tac">Data Agendamento</td>
            <td class="titulo_coluna tac">Técnico</td>
            <td class="titulo_coluna tac">Ações</td>
        </tr> 
        <thead>
        <tbody>
        <?php foreach($exportacao_pendente as $agenda){

            $ag = explode(",", $agenda['agenda']);
            $hora_inicio_trabalho = $ag[0];
            $hora_fim_trabalho = $ag[1];
            $tecnico_agenda = $ag[2];
            $nome_tecnico = str_replace(['"', ')'], "", $ag[3]);

            echo "<tr class='pendente_line_".$agenda['tecnico_agenda']."'>";
                echo "<td class='tac'><a href='os_press.php?os=".$agenda['os']."' target='_blank'>".$agenda['os']."</a></td>";
                echo "<td class='tac'>".mostra_data_hora($hora_inicio_trabalho). " - " .mostra_data_hora($hora_fim_trabalho)."</td>";
                echo "<td class='tac'>".$nome_tecnico."</td>";
                echo "<td class='tac'>
                <img src='imagens/loading_img.gif' style='display: none; height: 20px; width: 20px;' class='loading_img_".$tecnico_agenda."' />
                <button type='button' class='btn btn-primary exportar ' data-tecnico-agenda='".$tecnico_agenda."' data-os='".$agenda['os']."' >Enviar para Aplicativo</button>
                </td>";
            echo "</tr>";
        }?>  
        <tbody>     
    </table>
    <br>
    <br>
    <br>
<?php } ?>
</div>

<?php 

    if($_POST["btn_acao"] == "pesquisar" ){
if(count(array_filter($agendamentos_pesquisa))) {

echo "<table style='width:98%' id='frm_agendamento_cockpit' class='table table-striped table-bordered table-hover ' >";
    echo "<thead>";
    echo "<TR class='titulo_coluna'>"; 
        echo "<th>OS</th>";
        echo "<th>Abertura O.S</th>";
        echo "<th>Agendamento</th>";
        echo "<th>Tipo Atendimento</th>";
        echo "<th>Status OS</th>";
        echo "<th>Status APP</th>";
        echo "<th>Técnico</th>";
        echo "<th>Km</td>";
        echo "<th>Horímetro</td>";
        echo "<th norap>Envio App</td>";
        echo "<th>Check-in</th>";
        echo "<th>Check-out</th>";
        echo "<th nowrap >Horas Trabalhadas <br> Hora:Minutos</th>";
        echo "<th>Aprovado</th>";
        echo "<th>Fechamento O.S</th>";
        echo "<th>Ações</th>";
    echo "</tr>";
    echo "</thead>";

    echo "<body>";

    $quantidade = array(); 

//     $quantidade['Reparo']['Agendado'] = 0;
//     $quantidade['Reparo']['Realizado'] = 0;
//     $quantidade['Reparo']['Cancelado'] = 0;


//     $quantidade['Revisão']['Agendado'] = 0;
//     $quantidade['Revisão']['Realizado'] = 0;
//     $quantidade['Revisão']['Cancelado'] = 0;


//     $quantidade['Garantia']['Agendado'] = 0;
//     $quantidade['Garantia']['Realizado'] = 0;
//     $quantidade['Garantia']['Cancelado'] = 0;

//     $quantidade['Outros']['Agendado'] = 0;
//     $quantidade['Outros']['Realizado'] = 0;
//     $quantidade['Outros']['Cancelado'] = 0;


//     $quantidade['Entrega Técnica']['Agendado'] = 0;
//     $quantidade['Entrega Técnica']['Realizado'] = 0;
//     $quantidade['Entrega Técnica']['Cancelado'] = 0;

//     $quantidade['PMP - Programa Melhoria Produto']['Agendado'] = 0;
//     $quantidade['PMP - Programa Melhoria Produto']['Realizado'] = 0;
//     $quantidade['PMP - Programa Melhoria Produto']['Cancelado'] = 0;


// print_r($quantidade);
//     $quantidade = array_map_recursive('utf8_decode', $quantidade);
// echo "json";
//     echo json_encode($quantidade);

//     exit;


    
    $count = 0;
    foreach($agendamentos_pesquisa as $agenda){

       /* $quantidade['Agendado'][retira_acentos(utf8_decode($agenda['descricao_tipo_atendimento']))] = 0; 
        $quantidade['Realizado'][retira_acentos(utf8_decode($agenda['descricao_tipo_atendimento']))] = 0; 
        $quantidade['Cancelado'][retira_acentos(utf8_decode($agenda['descricao_tipo_atendimento']))] = 0; 
*/

        if(!isset($quantidade[retira_acentos(utf8_decode($agenda['descricao_tipo_atendimento']))])){
            $quantidade[retira_acentos(utf8_decode($agenda['descricao_tipo_atendimento']))]['Agendado'] = 0;
            $quantidade[retira_acentos(utf8_decode($agenda['descricao_tipo_atendimento']))]['Realizado'] = 0;
            $quantidade[retira_acentos(utf8_decode($agenda['descricao_tipo_atendimento']))]['Cancelado'] = 0;
        }
       
        $status_app = "";

        $sql_os_campo_extra = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = ".$agenda['os']." and fabrica = $login_fabrica ";
        $res_os_campo_extra = pg_query($con, $sql_os_campo_extra);
        if(pg_num_rows($res_os_campo_extra)>0){
            $campos_adicionais = json_decode(pg_fetch_result($res_os_campo_extra, 0, 'campos_adicionais'), true);
        }else{$campos_adicionais = null;}        

        if(strlen($agenda['cancelado'])>0){
            $cor = "#f2dede";
            $linha_situacao = "<td style='background-color:$cor !important'><b>Cancelado:</b> ".mostra_data_hora($agenda['cancelado'])."</td>";
            $linha_exportar = "<td nowrap style='background-color:$cor !important; text-align: center' class='tac exportar_".$agenda['tecnico_agenda']."'></td>";

        }elseif($campos_adicionais['data_integrado']){
            $cor = "#dff0d8";
            $linha_situacao = "<td style='background-color:$cor !important'><b>Integrado O.S:</b> ".mostra_data_hora($campos_adicionais['data_integrado'])."</td>";
             $linha_exportar = "<td nowrap style='background-color:$cor !important; text-align: center' class='tac exportar_".$agenda['tecnico_agenda']."'>
                
             </td>";
        }   elseif($campos_adicionais['sincronizado']){
            $cor = "#FFDEAD";
            $linha_situacao = "<td style='background-color:$cor !important'><b>Enviado Aplicativo:</b> ".mostra_data_hora($campos_adicionais['data_sincronizado'])."</td>";
             $linha_exportar = "<td nowrap style='background-color:$cor !important; text-align: center' class='tac exportar_".$agenda['tecnico_agenda']."'>
                <img src='imagens/loading_img.gif' style='display: none; height: 20px; width: 20px;' class='loading_img_".$agenda['tecnico_agenda']."' />
                <button type='button' class='btn btn-primary' onclick='cockpit.obtemAgendamentosPorOS(".$agenda['os'].")'>Editar</button>
                <button type='button' class='btn btn-warning atualizarDadosTicket ' data-tecnico-agenda='".$agenda['tecnico_agenda']."' data-os='".$agenda['os']."' >Atualizar</button>
                <button  class='btn btn-danger' onclick='cockpit.cancelarAgendamentoNoBanco(".$agenda['tecnico_agenda'].",".$agenda['os'].")'>Cancelar</button>
                
             </td>";
        }else{
            $cor = "";
            $linha_situacao = "<td style='background-color:$cor !important'></td>";
            $linha_exportar = "<td nowrap style='background-color:$cor !important; text-align: center' class='tac exportar_".$agenda['tecnico_agenda']."'>
                <img src='imagens/loading_img.gif' style='display: none; height: 20px; width: 20px;' class='loading_img_".$agenda['tecnico_agenda']."' />
                <button type='button' class='btn btn-primary' onclick='cockpit.obtemAgendamentosPorOS(".$agenda['os'].")'>Editar Agendamento</button> 
                <button type='button' class='btn btn-success exportar ' data-tecnico-agenda='".$agenda['tecnico_agenda']."' data-os='".$agenda['os']."' >Exportar Agendamento</button> </td>
                ";
        }            

        $hora_trabalhada = "";
        if(strlen(trim($campos_adicionais['checkin'])) > 0){    
            $dataCheckin = convertDataHora($campos_adicionais['checkin']);
            $dataCheckout = convertDataHora($campos_adicionais['checkout']);
            $datetime1 = new DateTime($dataCheckin);
            $datetime2 = new DateTime($dataCheckout);
            $interval = $datetime1->diff($datetime2);

            $hora_trabalhada = (($interval->d * 24) + ($interval->h)). ":". str_pad($interval->i, 2, "0", STR_PAD_LEFT); 
        }

        if(strlen($campos_adicionais['checkout'])>0){
            $status_app = 'Realizado';
        }elseif(strlen($agenda['cancelado'])>0){
            $status_app = 'Cancelado';
        }elseif($campos_adicionais['sincronizado']){
            $status_app = "Agendado";
        }

        $quantidade[retira_acentos(utf8_decode($agenda['descricao_tipo_atendimento']))][$status_app] += 1;

        echo "<tr  class='agendamento_".$agenda['tecnico_agenda']."'>";
            echo "<td style='background-color:$cor !important' ><a href='os_press.php?os=".$agenda['os']."' target='_blank'>". $agenda['os'] ."</a></td>";
            echo "<td class='tac' style='background-color:$cor !important'>".mostra_data_hora($agenda['data_abertura'])."</td>";

            echo "<td style='background-color:$cor !important'>". mostra_data_hora($agenda['start']) ." - ". mostra_data_hora($agenda['end']) ."</td>";
            echo "<td class='tac' style='background-color:$cor !important'>". utf8_decode($agenda['descricao_tipo_atendimento']) ."</td>";

            echo "<td style='background-color:$cor !important'>". utf8_decode($agenda['descricao_status_checkpoint']) ."</td>";
            echo "<td style='background-color:$cor !important'>". $status_app ."</td>";

            echo "<td style='background-color:$cor !important'>". $agenda['usuario'] ."</td>";
            echo "<td style='background-color:$cor !important' class='tac'>".$agenda['km']."</td>";
            echo "<td style='background-color:$cor !important' class='tac'>".$agenda['horimetro']."</td>";
            echo "<td style='background-color:$cor !important' class='tac'>".mostra_data_hora($campos_adicionais['data_sincronizado'])."</td>";
            echo "<td class='tac' style='background-color:$cor !important'>".substr($campos_adicionais['checkin'],0,16)."</td>";
            echo "<td class='tac' style='background-color:$cor !important'>".substr($campos_adicionais['checkout'], 0 ,16)."</td>";
            
            echo "<td class='tac' nowrap style='background-color:$cor !important'>". $hora_trabalhada ."</td>";

            echo "<td class='tac' style='background-color:$cor !important'>".mostra_data_hora($campos_adicionais['data_integrado'])."</td>";
            echo "<td class='tac' style='background-color:$cor !important'>".mostra_data_hora($agenda['finalizada'])."</td>";
            //echo $linha_situacao;
            echo $linha_exportar;            
        echo "</tr>";        
        $count++;
    }
    echo "</body>";
echo "</table>";

$dados_grafico = json_encode($quantidade);

?>
<br>
<br>
<div class="container-fluid">
    <div class=" titulo_coluna" style=" font-size:16px; cursor: pointer; line-height: 20px !important; " width="100%">GRÁFICOS</div>
</div>
<br>
<br>

<div class="graficos">
    <iframe id="dashboard_cockpit" src="dashboard_cockpit.php?dados=<?=urlencode($dados_grafico)?>" style="width: 50%; height: 450px" frameborder="0" scrolling="no"></iframe>

    <iframe id="dashboard_cockpit_2" src="dashboard_cockpit_2.php?total=<?=$count?>&dados=<?=urlencode($dados_grafico)?>" style="width: 49%; height: 450px" frameborder="0" scrolling="no"></iframe>
</div>

<?php


}elseif(count(array_filter($msg_erro["msg"]))==0){
    echo '<div class="container">
        <div class="alert">
                <h4>Nenhum agendamento encontrado</h4>
        </div>
        </div>';
}

//echo "<div id='calendar'></div>";
}else{ ?>




<div id='calendar'></div>
<?php } ?>
<!-- Modal Confirmar/Editar/Remover Agendamento -->
<div id="modal-confirmar-remover-agendamento" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="modalRemoverEventoLabel" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="modalRemoverEventoLabel">
            Agendamento: <span id="numero_os"></span>
            <input type="hidden" name="campo_os" id="campo_os" value="">
        </h3>
    </div>
    <div class="modal-body">
        <!-- <p id="info-intervalo-evento"></p> -->
        <form  class="form-horizontal" id="confirmar-remover-agendamento">
            <div class="control-group">
                <label class="control-label" for="titulo-evento">Data / Hora Inicio:</label>
                <div class="controls">
                    <div class="input-prepend input-append" id="data_inicio_agendamento">
                        <input type="text" name="data_inicio" class="data-inicio-agendamento" disabled="true" />
                        <span class="add-on"><i class="icon-calendar iconreagendar" style='display:none'></i></span>
                    </div>
                </div>
                    <input type="hidden" name="dia-inteiro-agendamento" id="dia-inteiro-agendamento" />
            </div>
            <div class="row-fluid">
                <div class="span4">
                    <div class="control-group">
                        <label class="control-label" for="titulo-evento">Data / Hora Termino:</label>
                        <div class="controls">
                            
                            <div class="input-prepend input-append" id="data_fim_agendamento">
                                <input type="text" name="data_termino" class="data-fim-agendamento" disabled="true"/>
                                <span class="add-on"><i class="icon-calendar iconreagendar"  style='display:none'></i></span>
                            </div>
                        </div>
                            <input type="hidden" name="fabrica_id" id="fabrica-id" value="<?= $login_fabrica ?>" />
                    </div>
                </div>
            </div>
            <!-- <div class="control-group"> -->
                <!-- <label class="control-label" for="titulo-evento">Título:</label> -->
                <!-- <div class="controls"> -->
                    <!-- <input type="text" id="titulo-agendamento" name="confirmar-titulo-agendamento" disabled="true"/> -->
                    <input type="hidden" name="confirmar-data-inicio-agendamento" id="confirmar-data-inicio-evento" />
                    <input type="hidden" name="confirmar-data-fim-agendamento" id="confirmar-data-fim-evento" />
                    <input type="hidden" name="confirmar-dia-inteiro-agendamento" id="confirmar-dia-inteiro-evento" />
                     <input type="hidden" name="os-agendamento" id="id-os-agendamento" />
                    <input type="hidden" name="confirmar-fabrica-id" id="confirmar-fabrica-id" value="<?=$login_fabrica?>" />
                    <input type="hidden" name="confirmar-tecnico-agenda" id="confirmar-tecnico-agenda" />
                <!-- </div>
            </div> -->

            <div class="row-fluid">
                <div class="span4">
                    <div class="control-group">
                        <label class="control-label" for="tecnico-agendamento">Técnico:</label>
                        <div class="controls">
                            <select id="confirmar-tecnico-agendamento" name="confirmar-tecnico-agendamento" disabled="true"  >
                                <?php foreach ($tecnicos as $tecnico) { ?>
                                    <option value="<?= $tecnico['tecnico'] ?>">
                                        <?= $tecnico['nome'] ?>    
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!--<div class="control-group">
                <label class="control-label" for="os-agendamento">OS:</label>
                <div class="controls">
                    <select id="confirmar-os-agendamento" name="confirmar-os-agendamento">
                        <?php foreach ($ordensDeServico as $os) { ?>
                            <option value="<?= $os['os'] ?>"><?= $os['os'] ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>-->

            <!-- <div class="control-group">
                <label class="control-label" for="confirmado-agendamento">Confirmado:</label>
                <div class="controls">
                    <select id="confirmar-confirmado-agendamento" name="confirmar-confirmado-agendamento" disabled="true">
                        <option value="0">Não</option>
                        <option value="1">Sim</option>
                    </select>
                </div> 
            </div>  -->

            <!-- <div class="control-group">
                <label class="control-label" for="descricao-agendamento">Observação:</label>
                <div class="controls">
                    <input type="text" id="confirmar-descricao-agendamento" name="confirmar-descricao-agendamento" disabled="true" />
                </div>
            </div> -->
            <div class="control-group">
                <label class="control-label" for="justificativa-agendamento">Justificativa:</label>
                <div class="controls">
                    <textarea row="10" id="confirmar-justificativa-agendamento" name="confirmar-justificativa-agendamento" ></textarea>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="descricao-agendamento">Cancelar Agendamento Anterior:</label>
                <div class="controls">
                    <input type="checkbox" id="cancelar_anterior" name="cancelar_anterior" value='sim' disabled="true" />
                </div>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <span class="mensagem_reagendamento">
            
        </span>        
        <button class="btn" data-dismiss="modal" aria-hidden="true" onclick='cockpit.fecharModal()'>Fechar</button>
        <button  id="liberar_campos" class="btn btn-primary" onclick="cockpit.liberarCampos()">Editar </button>
        <button  id="botao-editar-evento" class="btn btn-success" onclick="cockpit.editarEvento()" style='display:none'>Salvar Alteração</button>
        <!-- <button  id="botao-remover-evento" class="btn btn-danger" onclick="cockpit.removerEvento()">Cancelar Agendamento</button> -->
    </div>
</div>

<script>
    // corrigi a localização select2 dentro do modal
    $.fn.modal.Constructor.prototype.enforceFocus = function () {};
</script>

<!-- Modal Adicionar agendamento -->
<div id="modal-adicionar-evento" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="myModalLabel">Adicionar Agendamento</h3>
    </div>
    <div class="modal-body">
        <form class="form-horizontal" id="adicionar-novo-agendamento">
            <div class="control-group">
                <label class="control-label" for="titulo-evento">Data / Hora Inicio:</label>
                <div class="controls">
                    <div class="input-prepend input-append" id="data_inicio_agendamento">
                        <input type="text" name="data_inicio" id="data-inicio-agendamento" />
                        <span class="add-on"><i class="icon-calendar"></i></span>
                    </div>
                </div>
                    <input type="hidden" name="dia-inteiro-agendamento" id="dia-inteiro-agendamento" />
            </div>
            <div class="control-group">
                <label class="control-label" for="titulo-evento">Data / Hora Termino:</label>
                <div class="controls">
                    
                    <div class="input-prepend input-append" id="data_fim_agendamento">
                        <input type="text" name="data_termino" id="data-fim-agendamento" />
                        <span class="add-on"><i class="icon-calendar"></i></span>
                    </div>
                </div>
                    <input type="hidden" name="fabrica_id" id="fabrica-id" value="<?= $login_fabrica ?>" />
            </div>
            <!-- <div class="control-group">
                <label class="control-label" for="titulo-evento">Título:</label>
                <div class="controls">
                    <input type="text" id="titulo-agendamento" name="titulo-agendamento" />
                </div>
            </div> -->
            <div class="row-fluid">
                <div class="span4">
                    <div class="control-group">
                        <label class="control-label" for="tecnico-agendamento">Técnico:</label>
                        <div class="controls">
                            <select id="tecnico-agendamento" name="tecnico_id" class="span4">
                                <?php foreach ($tecnicos as $tecnico) { ?>
                                    <option value="<?= $tecnico['tecnico'] ?>">
                                        <?= $tecnico['nome'] ?>    
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span4">
                    <div class="control-group">
                        <label class="control-label" for="os-agendamento">OS:</label>
                        <div class="controls">
                            <select id="os-agendamento" name="os">
                                <?php foreach ($ordensDeServico as $os) { ?>
                                    <option value="<?= $os['os'] ?>"><?= $os['os'] ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <!-- <div class="control-group">
                <label class="control-label" for="confirmado-agendamento">Confirmado:</label>
                <div class="controls">
                    <select id="confirmado-agendamento" name="confirmado">
                        <option value="0">Não</option>
                        <option value="1">Sim</option>
                    </select>
                </div>
            </div> -->
            <div class="row-fluid">
                <div class="span4">
                    <div class="control-group">
                        <label class="control-label" for="descricao-agendamento">Observação:</label>
                        <div class="controls">
                            <input type="text" id="descricao-agendamento" name="descricao" />
                        </div>
                    </div>
                </div>
            </div>
        </form>

    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Fechar</button>
        <button class="btn btn-primary" onclick="cockpit.adicinarEvento()">Gravar</button>
    </div>
</div>

<!-- Modal Adicionar agendamento -->
<div id="modal-adicionar-evento-all" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="myModalLabel">Adicionar Agendamento</h3>
    </div>
    <div class="modal-body">
        <form class="form-horizontal adicionar-multiplos-agendamento">
        <input type="hidden" name="fabrica_id" value="<?= $login_fabrica ?>" />
            <div class="step2" style="display: none;">
                <div class="row-fluid">
                    <div class="span12">
                        <div class="row-fluid">
                            <div class="span1">
                                <div style="background: #ddd;padding: 10px;">
                                    Técnico
                                </div>
                            </div>
                            <div class="span3">
                                <div style="background: #ddd;padding: 5px 6px;margin-left: -20px;">
                                    <select  name="tecnico_id" class="span12 tecnico_id">
                                        <option value="">Selecione ...</option>
                                        <?php foreach ($tecnicos as $tecnico) { ?>
                                            <option value="<?= $tecnico['tecnico'] ?>">
                                                <?= $tecnico['nome'] ?>    
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <!-- <div class="span2">
                                <div style="background: #ddd;padding: 10px;margin-left: -20px;">
                                Data do Agendamento
                                </div>
                            </div>
                            <div class="span6">
                                <div style="background: #ddd;padding: 5px 6px;margin-left: -20px;">
                                    <div class="input-prepend input-append" id="data_agendamento_<?php echo $k;?>">
                                        <input type="text" id="data-agendamento-<?php echo $k;?>"  class="span11 data_agendamento" data-os="<?= $os['os'];?>" name="data_agendamento[]" />
                                        <span class="add-on"><i class="icon-calendar"></i></span>
                                    </div>
                                </div>
                            </div> -->

                        </div>
                        <table id="tbl_os" class="table table-bordered" style="width: 100% !important">
                            <thead>
                                <tr class="titulo_coluna">
                                    <th><input type="checkbox" onclick="checkedAll();" class="checa_all"></th>
                                    <th>OS</th>
                                    
                                    <th nowrap>Hora Início</th>
                                    <th nowrap>Hora Fim</th>
                                    <th nowrap>Data Abertura</th>
                                    <th class="tal" nowrap>Tipo de Atendimento</th>
                                    <th class="tal">KM</th>
                                    <th class="tal" nowrap>Consumidor/Revenda</th>
                                    <th class="tal">Produto</th>
                                </tr>
                            </thead>
                            <tbody id="frm1">
                                <?php 
                                    foreach ($ordensDeServico as $k => $os) { 
                                        list($ano,$mes, $dia) = explode("-", $os['data_abertura']);
                                        $cor = ($k % 2 == 0) ? "#eee" : "#fff";
                                ?>
                                <tr class="tr-<?= $os['os'];?>" style="background: <?php echo $cor;?>">
                                    <td class="tac"><input type="checkbox" class="checa_os checado_<?=$k?>" name="os[]" posicao="<?=$k?>" fabrica="<?=$login_fabrica?>" value="<?= $os['os'];?>" ></td>
                                    <td class="tac"><a href="os_press.php?os=<?= $os['os'];?>" target="_blank"><?= $os['os'];?></a></td>
                                    <td><div class="input-prepend input-append" id="data_inicio_agendamento_multi_<?=$k?>" posicao="<?=$k?>">
                                            <input type="text" name="data_inicio" class="data_inicio_agendamento_multi_<?=$k?>" style="width: 135px" />
                                            <span class="add-on" ><i class="icon-calendar" posicao="<?=$k?>"></i></span>
                                        </div></td>
                                    <td>                                        
                                        <div class="input-prepend input-append" id="data_fim_agendamento_multi_<?=$k?>" posicao="<?=$k?>">
                                            <input type="text" name="data_fim" class="data-fim-agendamento-multi_<?=$k?>"  style="width: 135px"/>
                                            <span class="add-on"><i class="icon-calendar" posicao="<?=$k?>"></i></span>
                                        </div>                                        
                                    </td>
                                    <td class="tac"><?= $dia."/".$mes."/".$ano;?></td>
                                    <td class="tac"><?= $os['tipo_atendimento'];?></td>
                                    <td class="tac"><?= $os['qtde_km'];?></td>
                                    <td><?= $os['consumidor_nome'];?></td>
                                    <td><?= $os['produto'];?></td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>                       
                       
                    </div>
                </div>
            </div>

            <div class="step3" style="display: none;">
                <div class="row-fluid">
                    <div class="span12">
                        <h3 class="titulo_tabela txt_tit"></h3>
                         <table class="table table-bordered table-striped table-hover" style="width: 100% !important" id="table_table">
                            <thead>
                                <tr class="titulo_coluna">
                                    <th class="tac" width="20%">OS</th>
                                    <th class="tac" width="30%">Data Agendamento</th>
                                    <th class="tal">Técnico</th>
                                </tr>
                            </thead>
                            <tbody id="resultado-agendamento">
                            </tbody>
                        </table>                       
                        <div class="row-fluid">
                            <div class="span1"></div>
                            <div class="span10">
                                <label for="confirmado-agendamento">Confirmado:</label>
                                <select  class="span6" id="mult-confirmado-agendamento" name="confirmado">
                                    <option value="0">Não</option>
                                    <option value="1">Sim</option>
                                </select>
                            </div>
                            <div class="span1"></div>
                        </div>
                        <div class="row-fluid">
                            <div class="span1"></div>
                            <div class="span10">
                                <label for="confirmado-agendamento">Observação:</label>
                                <input type="text" class="span12" id="mult-descricao-agendamento" name="descricao" />
                            </div>
                            <div class="span1"></div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <div id="error_area" style="width: 60%; float: left"></div>
        <div id="success_area" style="width: 60%; float: left"></div>
        <button class="btn" data-dismiss="modal"  aria-hidden="true" onclick="cockpit.fecharModal()" >Fechar</button>
        <button type="button" style="display: none;" class="btn btn-info btn-step2"><i class="icon-chevron-right icon-white"></i> Avançar</button> 
        <button type="button"  class="btn btn-primary btn-step3 btn-grava-multiplo-evento">Agendar</button>
    </div>
</div>
<?php 
    foreach ($ordensDeServico as $k => $os) {
     $dados[] = "#data_agendamento_".$k;   
    }
?>
<script src='classes/Posvenda/CockpitPosto/js/cockpit.js' charset='utf-8'></script>
<script src='classes/Posvenda/CockpitPosto/js/fabricas/configuracoes/fullcalendar_config_169.js'></script>
<script>
    var checked=false;
    function checkedAll() {
        var campos_checkbox = $(".checa_os");
         if (checked == false) {
               checked = true
        } else {
            checked = false
        }
        for (var i =0; i < campos_checkbox.length; i++) {
            campos_checkbox[i].checked = checked;
        }
    }

    $(function() {

        var date = new Date();
        date.setDate(date.getDate());

        $.datepickerLoad(Array("data_final", "data_inicial"), {startDate:0});
        $("<?php echo implode(",",$dados);?>").datetimepicker({  
                                                                format: "dd/MM/yyyy hh:mm",
                                                                pickerPosition: "bottom-left",
                                                                maskInput: true, 
                                                                startDate: date, 
                                                                language: 'pt-BR'
                                                            });
         $("#data_inicio_agendamento, #data_fim_agendamento").datetimepicker({  
                                                                format: "dd/MM/yyyy hh:mm",
                                                                pickerPosition: "bottom-left",
                                                                maskInput: true, 
                                                                startDate: date, 
                                                                language: 'pt-BR',
                                                            });


         $("[id^=data_inicio_agendamento_multi_]").datetimepicker({  
                                                                format: "dd/MM/yyyy hh:mm",
                                                                pickerPosition: "bottom-left",
                                                                maskInput: true, 
                                                                startDate: date, 
                                                                language: 'pt-BR',
                                                            });

        $("[id^=data_fim_agendamento_multi_]").datetimepicker({  
                                                                format: "dd/MM/yyyy hh:mm",
                                                                pickerPosition: "bottom-left",
                                                                maskInput: true, 
                                                                startDate: date, 
                                                                language: 'pt-BR', 
                                                            }).on('hide', function(ev){
                                                                
                                                                var posicao = $(this).attr('posicao');
                                                                $(".checado_"+posicao).attr("checked", true); 
                                                                $(".data-fim-agendamento-multi_"+posicao).focus();
                                                                valida_data();
                                                              });

        $('#calendar').fullCalendar(configuracoes_169) // inicia o calendário
        $("select#tecnico-agendamento").select2({width:"100%"}) // inicia o input de técnicos
        $("select#confirmar-tecnico-agendamento").select2({width:"100%"}) // inicia o input de técnicos

        $("select#os-agendamento").select2({width:"100%"}) // inicia o input de os
        $("select#confirmar-os-agendamento").select2({width:"100%"}) // inicia o input de os
        $.dataTableLoad({
            table: "#table_table",
            table: "#tbl_os"
        });
        
        <?php

         if(isset($agendamentos)) { ?>

            let agendamentos = "";

            agendamentos = <?= $agendamentos ?>;

        // objeto "cockpit" instanciado no arquivo fullcalendar_config_169.js
        cockpit.inserirEventosDoBancoNoCalendario(agendamentos)

        <?php } ?>

        $(document).on("click", ".btn-grava-multiplo-evento", function(){  
            var error = $("#error_area").text("");
            var tecnico_id = $(".tecnico_id").val();
            if(tecnico_id.length == 0){
                $("#error_area").text("Informe o Técnico");
                return false;
            }
            cockpit.adicinarEvento(true);
            //pegar retorno e tratar, se tiver alguma os que não agendou colocar linha vermelha e não fechar o modal 
            // para as que acendou colocar em verde e mostrar msg  
            //window.location.href='cockpit.php';
        });

        $(document).on("click", ".btn-abre-modal-novo", function(){
            $(".step2").show('slow');
            $(".step3").hide('slow');
            $(".btn-step1").show('slow');
            $(".btn-step2").hide('slow');
            //$(".btn-step3").hide('slow');
            $(".modal").css({"width": "80%",  "margin-left": "-40%"});

            $("input[name=titulo]").val('');
        });

        
        $(document).on("click", ".btn-step2", function(){
            
            var agendamentos_id = new Array;
            var tecnicos_id     = new Array;
            var os              = new Array;
            var tecnicos_desc   = new Array;

            $("input[name^=data_agendamento]").each(function(i, v) {

                if (this.value != '') {
                    os.push($(v).data("os"));
                    agendamentos_id.push(this.value);
                    if ($("#tecnico-agendamento-"+i+" option:selected").val()  != '') {
                        tecnicos_id.push($("#tecnico-agendamento-"+i+" option:selected").val());
                        tecnicos_desc.push($("#tecnico-agendamento-"+i+" option:selected").text());
                    }
                }
            });
            if (agendamentos_id.length == 0 || tecnicos_id.length == 0) {
                alert("Efetue pelo menos um agendamento e/ou  escolha um técnico");
                return false;
            }

            var tr_agendamento = "";

            for (var i = 0; i < agendamentos_id.length; i++) {
                tr_agendamento += '<tr>\
                                    <td class="tac">\
                                    <input type="hidden" value="'+os[i]+'" name="os_agendamento[]">\
                                        <a href="os_press.php?os='+os[i]+'" target="_blank">'+os[i]+'</a>\
                                    </td>\
                                    <td class="tac">'+agendamentos_id[i]+'</td>\
                                    <td>'+tecnicos_desc[i]+'</td>\
                                    </tr>';
            }
            $("#resultado-agendamento").html(tr_agendamento);

            $(".step1").hide('slow');
            $(".step2").hide('slow');
            $(".btn-step2").hide('slow');
            $(".step3").show('slow');
            $(".btn-step3").show('slow');
            $(".modal").css({"width": "50%",  "margin-left": "-23%"});

        });

        $(document).on("change", ".tecnico_id", function(){
            $("#error_area").text("");
            var tecnico_this = $(this);
            var data_this    = $(this).parents('tr').find("input[name^=data_agendamento]");
            var os_this      = $(this).parents('tr').find("input[name^=data_agendamento]").data("os");
            var tr_this      = $('.tr-'+os_this);
        
            $("input[name^=data_agendamento]").each(function(i, v) {
                if (os_this != $(this).data("os")) {
                    var tecnico_old  = $(this).parents('tr').find("select[name^=tecnico_id] option:selected");
                    var data_old     = $(this).parents('tr').find("input[name^=data_agendamento]");
                    var os_old       = $(this).data("os");
                    var tr_old = $('.tr-'+os_old);
                    if (tecnico_this.val() == tecnico_old.val() && data_this.val() == data_old.val()) {
                        tr_old.addClass('erro_validacao');
                        tr_this.addClass('erro_validacao');
                        alert("O técnico já possui um agendamento para essa data e hora!");
                        data_this.val('')
                        tecnico_this.val('')
                        data_this.focus()
                        return false;
                    }
                }                 
            });
        });

        $("[class^='data-fim-agendamento-multi_']").blur(function(){
            var valor = $(this).val();
            if(valor.length > 0){
                valida_data();
            }
        });
        
        function valida_data(){
            $("[class^='tr-']").removeClass("error_linha");
            $(".btn-grava-multiplo-evento").attr("disabled", false);
            $("#error_area").text("");

            var data_inicio = "";
            var data_fim = "";
            var datas = [];
            var validar = false; 
            //var os = $(this).val();
            var erro = false;
            var os = "";

            $(".checa_os:checked").each(function(cont){
                var posicao = $(this).attr("posicao");                
                os = $(this).val();

                data_inicio = $(".data_inicio_agendamento_multi_"+posicao).val();
                data_fim = $(".data-fim-agendamento-multi_"+posicao).val();

                if(data_inicio.length == 0 && data_fim.length == 0){
                    return false;
                }

                if(cont > 0 ){
                    validar = true;
                }

                datas.push({
                    dataInicio : data_inicio,
                    dataFinal : data_fim,
                    os:os
                });
            });

            if(validar){            
                $.each(datas, function(index, el){

                    if(!dateCheck(el.dataInicio, el.dataFinal, data_inicio, data_fim)){
                       $(".tr-"+os).addClass("error_linha");
                       erro = true;
                    }

                    if(!dateCheck(data_inicio, data_fim, el.dataInicio, el.dataFinal)){
                       $(".tr-"+os).addClass("error_linha");
                       erro = true;
                    }
                });
                if(erro){
                    $("#error_area").text("O técnico já possui um agendamento para essa data e hora!");
                    $(".btn-grava-multiplo-evento").attr("disabled", true);
                }
            }
        }

        function dateCheck(from,to,checkInicio, checkFim) {

            from = from.split(" ");
            d1 = from[0].split("/");
            t1 = from[1].split(":");

            to = to.split(" ");
            d2 = to[0].split("/");
            t2 = to[1].split(":");

            checkInicio = checkInicio.split(" ");
            d3 = checkInicio[0].split("/");
            t3 = checkInicio[1].split(":");

            checkFim = checkFim.split(" ");
            d4 = checkFim[0].split("/");
            t4 = checkFim[1].split(":");
            //funcao 1 fazer o laço aqui para pegar as datas do arr e coloca no from e to 
            //verificar se a data que estou informando esta dentro de uma data do array 

            //funcao 2 segundo função o laço sera para as datas checkininio e check fim, verifica se tem alguma data do array que esta dentro da data que estou informando .
            var fDate = new Date(d1[2], parseInt(d1[1])-1, d1[0], t1[0], t1[1]);
            var tDate = new Date(d2[2], parseInt(d2[1])-1, d2[0], t2[0], t2[1]);
            var ciDate = new Date(d3[2], parseInt(d3[1])-1, d3[0], t3[0], t3[1]);
            var cfDate = new Date(d4[2], parseInt(d4[1])-1, d4[0], t4[0], t4[1]);

            if((ciDate > fDate && ciDate < tDate) ) {
                return false;
            }

            if((cfDate > fDate && cfDate < tDate)){
                return false;
            }

            return true;
        }

        $(".exportar").click(function(){
            var tecnico_agenda = $(this).data('tecnico-agenda');
            var num_os = $(this).data('os');
            $.ajax({
                url: 'cockpit_exportar_agendamento.php',
                type: "POST",
                data: {exportar:true, tecnico_agenda:tecnico_agenda, num_os:num_os},
                beforeSend: function () {
                    $(".loading_img_"+tecnico_agenda).show();                    
                },
                complete: (data) => {
                    data = $.parseJSON(data.responseText);
                    if(data.sucesso){
                        alert(data.sucesso);
                        $(".exportar_"+tecnico_agenda).text('');
                        $(".loading_img_"+tecnico_agenda).hide();

                        $(".pendente_line_"+tecnico_agenda).remove();
                    }else{
                        alert(data.erro);
                    }
                },
            })
        });

        $(".atualizarDadosTicket").click(function(){
            var tecnico_agenda = $(this).data('tecnico-agenda');
            var num_os = $(this).data('os');
            $.ajax({
                url: 'cockpit_atualizar_ticket.php',
                type: "POST",
                data: {atualizar:true, num_os:num_os},
                beforeSend: function () {
                    $(".loading_img_"+tecnico_agenda).show();                    
                },
                complete: (data) => {
                    data = $.parseJSON(data.responseText);
                    if(data.sucesso){
                        alert(data.sucesso);
                        $(".exportar_"+tecnico_agenda).text('');
                        $(".loading_img_"+tecnico_agenda).hide();

                        $(".pendente_line_"+tecnico_agenda).remove();
                    }else{
                        alert(data.erro);
                    }
                },
            })


        });


    });
  
</script>

<?php
include "rodape.php";
?>
