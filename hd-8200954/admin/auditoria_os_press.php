<?php

 /**
  * @description - Página criada para concentrar todas
  * as entradas de auditorias da OS, junto com suas
  * devidas aprovaçoes
  *
  * - Tratamento dos dados - auditoria_os_press_post.php
  *
  * @todo SEMPRE lembrar de mexer nas paginas em separado, ao ter que mexer nessa
  * @author William Ap. Brandino
  */

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
$admin_privilegios="auditoria";
include_once 'autentica_admin.php';

$os = filter_input(INPUT_GET,'os');

?>
<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tooltips.css" type="text/css" rel="stylesheet" />
<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>

<script type="text/javascript">

$(function(){
    var login_fabrica   = <?=$login_fabrica?>;
    var login_admin     = <?=$login_admin?>;
    var os              = <?=$os?>;

    $("button[id^=btn_62]").click(function(){
        var qualBotao = $(this).attr("id");
        var os_status = $(this).parents("tr").attr("id").split("_");
        if(qualBotao == "btn_62_alterar"){
            window.open("os_item.php?os=<?=$os?>");
        }else{
            $("#62_"+os_status[1]).after("<tr id='pc_62_"+os_status[1]+"'>"
                +"<td colspan='2' style='text-align:center;'>Digite o motivo:<br /><textarea name='motivo' id='motivo'></textarea></td>"
                +"<td style='text-align:center;vertical-align:bottom;'><button type='button' id='gravarAuditoria' class='btn btn-success'>Gravar</button>"
                +"&nbsp;&nbsp;"
                +"<button type='button' id='cancelarAuditoria' class='btn btn-warning'>Cancelar</button></td>"
                +"</tr>"
            );

            $("#pc_62_"+os_status[1]).delegate("#gravarAuditoria","click",function (){
                var guarda = $("#pc_62_"+os_status[1]).children().clone();
                $.ajax({
                    url:"auditoria_os_press_post.php",
                    method:"POST",
                    dataType:"JSON",
                    data:{
                        ajax:"peca_critica",
                        fabrica:parseInt(login_fabrica),
                        admin:parseInt(login_admin),
                        os:parseInt(os),
                        status_os:parseInt(qualBotao[1]),
                        os_status:parseInt(os_status[1]),
                        motivo:$("#motivo").val()
                    },
                    beforeSend:function(){
                        $("#pc_62_"+os_status[1]).html("<td colspan='3'>Aguarde...</td>")
                        .css({
                            "text-align":"center",
                            "background-color":"#D3D3D3",
                            "font-size":"20px"
                        });
                    }
                })
                .done(function(data){
                    $("#pc_62_"+os_status[1]).detach();
                    $("#"+os_status[0]+"_"+os_status[1]).detach();
                    alert("Realizada a auditoria");
                })
                .fail(function(){
                    alert("Não foi possível realizar a auditoria");
                    $("#pc_62_"+os_status[1]).html(guarda);
                });
            });

            $("#pc_62_"+os_status[1]).delegate("#cancelarAuditoria","click",function (){
                $("button[id^=btn_62]").prop("disabled","");
                $("#pc_62_"+os_status[1]).detach();
            });
        }
    });

    $("button[id^=btn_]").click(function(){
        var qualBotao       = $(this).attr("id").split("_");
        var os_status       = $(this).parents("tr").attr("id").split("_");

        $("button[id^="+qualBotao[0]+"_"+qualBotao[1]+"]").prop("disabled","disabled");

        switch(qualBotao[1]){
            case "67":
            case "102":
            case "105":
            case "118":
            case "164":
                $("#"+qualBotao[1]+"_"+os_status[1]).after("<tr id='cria_"+qualBotao[1]+"_"+os_status[1]+"'>"
                    +"<td colspan='2' style='text-align:center;'>Digite o motivo:<br /><textarea name='motivo' id='motivo'></textarea></td>"
                    +"<td style='text-align:center;vertical-align:bottom;'><button type='button' id='gravarAuditoria' class='btn btn-success'>Gravar</button>"
                    +"&nbsp;&nbsp;"
                    +"<button type='button' id='cancelarAuditoria' class='btn btn-warning'>Cancelar</button></td>"
                    +"</tr>"
                );

                $("#cria_"+qualBotao[1]+"_"+os_status[1]).delegate("#gravarAuditoria","click",function (){
                    var guarda = $("#cria_"+qualBotao[1]+"_"+os_status[1]).children().clone();
                    $.ajax({
                        url:"auditoria_os_press_post.php",
                        method:"POST",
                        dataType:"JSON",
                        data:{
                            ajax:"gravacao",
                            fabrica:parseInt(login_fabrica),
                            admin:parseInt(login_admin),
                            os:parseInt(os),
                            status_os:parseInt(qualBotao[1]),
                            os_status:parseInt(os_status[1]),
                            acao:qualBotao[2],
                            motivo:$("#motivo").val()
                        },
                        beforeSend:function(){
                            $("#cria_"+qualBotao[1]+"_"+os_status[1]).html("<td colspan='3'>Aguarde...</td>")
                            .css({
                                "text-align":"center",
                                "background-color":"#D3D3D3",
                                "font-size":"20px"
                            });
                        }
                    })
                    .done(function(data){
                        $("#cria_"+qualBotao[1]+"_"+os_status[1]).detach();
                        $("#"+os_status[0]+"_"+os_status[1]).detach();
                        alert("Realizada a auditoria");
                    })
                    .fail(function(){
                        alert("Não foi possível realizar a auditoria");
                        $("#cria_"+qualBotao[1]+"_"+os_status[1]).html(guarda);
                    });
                });

                $("#cria_"+qualBotao[1]+"_"+os_status[1]).delegate("#cancelarAuditoria","click",function (){
                    $("button[id^=btn_"+qualBotao[1]).prop("disabled","");
                    $("#cria_"+qualBotao[1]+"_"+os_status[1]).detach();
                });

            break;
            case "98":

                $.ajax({
                    url:"auditoria_os_press_post.php",
                    method:"POST",
                    dataType:"JSON",
                    data:{
                        ajax:"km",
                        fabrica:parseInt(login_fabrica),
                        os:parseInt(os)
                    }
                })
                .done(function(data){
                    var km = data["res_km"];
                    var txt_km = km.toFixed(3);
                    var html_aprova;
                    if(qualBotao[2] == "aprova"){
                        html_aprova = "<input type='hidden' name='hdd_km' id='hdd_km' value='"+txt_km+"' />"
                        +"<td style='text-align:center;'>Km:<input type='text' name='txt_km' id='txt_km' value='"+txt_km+"'></td>"
                        +"<td style='text-align:center;'>Digite o motivo:<br /><textarea name='motivo' id='motivo'></textarea></td>";
                    }else{
                        html_aprova = "<td colspan='2' style='text-align:center;'>Digite o motivo:<br /><textarea name='motivo' id='motivo'></textarea></td>"
                    }

                    $("#"+qualBotao[1]+"_"+os_status[1]).after("<tr id='km_"+qualBotao[1]+"_"+os_status[1]+"'>"
                        +html_aprova
                        +"<td style='text-align:center;vertical-align:bottom;'><button type='button' id='gravarAuditoria' class='btn btn-success'>Gravar</button>"
                        +"&nbsp;&nbsp;"
                        +"<button type='button' id='cancelarAuditoria' class='btn btn-warning'>Cancelar</button></td>"
                        +"</tr>"
                    );

                    $("#km_"+qualBotao[1]+"_"+os_status[1]).delegate("#gravarAuditoria","click",function (){
                        var guarda = $("#km_"+qualBotao[1]+"_"+os_status[1]).children().clone();
                        if(qualBotao[2] == "aprova"){
                            var original_km = $("#hdd_km").val();
                            var km          = $("#txt_km").val();
                        }else{
                            var original_km = km;
                        }
                        $.ajax({
                            url:"auditoria_os_press_post.php",
                            method:"POST",
                            dataType:"JSON",
                            data:{
                                ajax:"gravacao_km",
                                fabrica:parseInt(login_fabrica),
                                admin:parseInt(login_admin),
                                os:parseInt(os),
                                status_os:parseInt(qualBotao[1]),
                                os_status:parseInt(os_status[1]),
                                acao:qualBotao[2],
                                original_km: original_km,
                                km:km,
                                motivo:$("#motivo").val()
                            },
                            beforeSend:function(){
                                $("#km_"+qualBotao[1]+"_"+os_status[1]).html("<td colspan='3'>Aguarde...</td>")
                                .css({
                                    "text-align":"center",
                                    "background-color":"#D3D3D3",
                                    "font-size":"20px"
                                });
                            }
                        })
                        .done(function(data){
                            $("#km_"+qualBotao[1]+"_"+os_status[1]).detach();
                            $("#"+os_status[0]+"_"+os_status[1]).detach();
                            alert("Realizada a auditoria");
                        })
                        .fail(function(){
                            alert("Não foi possível realizar a auditoria");
                            $("#km_"+qualBotao[1]+"_"+os_status[1]).html(guarda);
                        });
                    });

                    $("#km_"+qualBotao[1]+"_"+os_status[1]).delegate("#cancelarAuditoria","click",function (){
                        $("button[id^=btn_"+qualBotao[1]).prop("disabled","");
                        $("#km_"+qualBotao[1]+"_"+os_status[1]).detach();
                    });
                })
                .fail(function(){
                    alert("Não foi possível realizar a auditoria");
                    $("#km_"+qualBotao[1]+"_"+os_status[1]).html(guarda);
                });
            break;
        }
    });
});

</script>

<table id="tbl_auditorias" class="table table-striped table-bordered table-hover table-fixed" cellspacing="1" cellpadding="2">
    <thead>
        <tr>
            <th class="titulo_tabela">Tipo</th>
            <th class="titulo_tabela">Motivo</th>
            <th class="titulo_tabela">Ações</th>
        </tr>
    </thead>
    <tbody>
<?
/**
 * - 1º. Verificar todas as auditorias
 * e depois, retirar os APROVADOS e REPROVADOS
 *
 *
 * - INTERVENÇÃO TÉCNICA: PEÇA CRÍTICA
 * ATENÇÃO: PEÇA CRÍTICA apenas passa por aprovação, ou é realizado a troca da mesma por outra
 *
62 => "aprova" => array(64),
      "reprova" => array() ==> TROCA A PEÇA CRÍTICA
 *
 * - REINCIDÊNCIA
 *
67 => "aprova" => array(19),
      "reprova" => array(13)
 *
 * -  AUDITORIA DE KM
 * ATENÇÃO: Status de aprovação 100 SOMENTE se Km for alterado
 *
98 => "aprova" => array(99,100),
      "reprova" => array(101)
 *
 * - NÚMERO DE SÉRIE
 *
102 => "aprova" => array(103),
       "reprova" => array(104)
 *
 * - LGI
 *
105 => "aprova" => array(106),
       "reprova" => array(107)
 *
 * - PEÇAS EXCEDENTES
 *
118 => "aprova" => array(187),
       "reprova" => array()
 *
 * - TROCA DE PRODUTO / JUDICIAL
 *
164 => "aprova" => array(166),
       "reprova" => array(165)
 */

$sql = "
        SELECT  tbl_os_status.os_status                                     ,
                tbl_os_status.status_os                                     ,
                tbl_os_status.observacao                                    ,
                tbl_admin.login                             AS login        ,
                TO_CHAR(tbl_os_status.data, 'DD/MM/YYYY')   AS data_status  ,
                tbl_os_status.admin                                         ,
                tbl_status_os.descricao
        FROM    tbl_os_status
        JOIN    tbl_status_os using(status_os)
   LEFT JOIN    tbl_admin USING(admin)
        WHERE   os                              = $os
        AND     tbl_os_status.fabrica_status    = $login_fabrica
        AND     tbl_os_status.status_os IN (62,67,98,102,105,118,164)
  ORDER BY      data DESC
";

$res = pg_query($con,$sql);

$valores = pg_fetch_all($res);

/**
 * - PROCEDIMENTOS DE ELIMINAÇÃO, CASO STATUS JÁ ESTEJA APROVADO / REPROVADO
 * 1º - Faz-se a varredura do ARRAY com os dados EM APROVAÇÃO
 * 2º - Para cada status, faz-se a busca de algum status que o aprove / reprove
 *
 * CASO ENCONTRE:
 * * Guarda-se em um outro ARRAY o valor do os_status, para que este não aprove/reprove outro status
 *
 * CASO NÃO ENCONTRE:
 * * Cria-se a linha da tabela, para aprovar / reprovar o status
 *
 * CASOS ESPECIAIS:
 * * Auditoria de KM: Deve-se trazer o KM referente, sendo possível a alteração.
 * *
 * * CASO TENHA MUDANÇAS DO KM:
 * * * Gravar o valor 100 no Status e atualizar o KM
 * *
 * * CASO NÃO HAJA MUDANÇAS DO KM
 * * * Gravar o valor 99 no Status
 * *
 * * Auditoria de peças excedentes: Só há a opção de aprovação, caso contrário, deve-se alterar a OS
 */

$array_auditados = array();

foreach($valores AS $valor){
    switch($valor['status_os']){
        case 62:
            $sql = "SELECT  os_status
                    FROM    tbl_os_status
                    WHERE   os = $os
                    AND     fabrica_status = $login_fabrica
                    AND     status_os IN (64)";
            if(count($array_auditados) > 0){
                $auditados = implode(",",$array_auditados);
                $sql .= "
                    AND     os_status NOT IN ($auditados)
                ";
            }
            $sql .= "
                    LIMIT   1
            ";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res) == 1){
                $status_validado = pg_fetch_result($res,0,os_status);
                array_push($array_auditados,$status_validado);
            }else{
?>
        <tr id="62_<?=$valor['os_status']?>">
            <td><?=$valor['descricao']?></td>
            <td><?=$valor['observacao']?></td>
            <td style="text-align:center;">
                <button type="button" id="btn_62" class="btn btn-success">Autorizar</button>
                &nbsp;&nbsp;
                <button type="button" id="btn_62_alterar" class="btn btn-primary">Alterar</button>
        </tr>
<?
            }

        break;
        case 67:
            $sql = "SELECT  os_status
                    FROM    tbl_os_status
                    WHERE   os = $os
                    AND     fabrica_status = $login_fabrica
                    AND     status_os IN (13,19)";
            if( count($array_auditados) > 0){
                $auditados = implode(",",$array_auditados);
                $sql .= "
                    AND     os_status NOT IN ($auditados)
                ";
            }
            $sql .= "
                    LIMIT   1
            ";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res) == 1){
                $status_validado = pg_fetch_result($res,0,os_status);
                array_push($array_auditados,$status_validado);
            }else{
?>
        <tr id="67_<?=$valor['os_status']?>">
            <td><?=$valor['descricao']?></td>
            <td><?=$valor['observacao']?></td>
            <td style="text-align:center;">
                <button type="button" id="btn_67_aprova" class="btn btn-success">Aprovar</button>
                &nbsp;&nbsp;
                <button type="button" id="btn_67_reprova" class="btn btn-danger">Recusar</button>
            </td>
        </tr>
<?
            }
        break;
        case 98:
            $sql = "SELECT  os_status
                    FROM    tbl_os_status
                    WHERE   os = $os
                    AND     fabrica_status = $login_fabrica
                    AND     status_os IN (99,100,101)";
            if(count($array_auditados) > 0){
                $auditados = implode(",",$array_auditados);
                $sql .= "
                    AND     os_status NOT IN ($auditados)
                ";
            }
            $sql .= "
                    LIMIT   1
            ";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res) == 1){
                $status_validado = pg_fetch_result($res,0,os_status);
                array_push($array_auditados,$status_validado);
            }else{
?>
        <tr id="98_<?=$valor['os_status']?>">
            <td><?=$valor['descricao']?></td>
            <td><?=$valor['observacao']?></td>
            <td style="text-align:center;">
                <button type="button" id="btn_98_aprova" class="btn btn-success">Aprovar</button>
                &nbsp;&nbsp;
                <button type="button" id="btn_98_reprova" class="btn btn-danger">Recusar</button>
            </td>
        </tr>
<?
            }
        break;
        case 102:
            $sql = "SELECT  os_status
                    FROM    tbl_os_status
                    WHERE   os = $os
                    AND     fabrica_status = $login_fabrica
                    AND     status_os IN (103,104)";
            if( count($array_auditados) > 0){
                $auditados = implode(",",$array_auditados);
                $sql .= "
                    AND     os_status NOT IN ($auditados)
                ";
            }
            $sql .= "
                    LIMIT   1
            ";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res) == 1){
                $status_validado = pg_fetch_result($res,0,os_status);
                array_push($array_auditados,$status_validado);
            }else{
?>
        <tr id="102_<?=$valor['os_status']?>">
            <td><?=$valor['descricao']?></td>
            <td><?=$valor['observacao']?></td>
            <td style="text-align:center;">
                <button type="button" id="btn_102_aprova" class="btn btn-success">Aprovar</button>
                &nbsp;&nbsp;
                <button type="button" id="btn_102_reprova" class="btn btn-danger">Recusar</button>
            </td>
        </tr>
<?
            }
        break;
        case 105:
            $sql = "SELECT  os_status
                    FROM    tbl_os_status
                    WHERE   os = $os
                    AND     fabrica_status = $login_fabrica
                    AND     status_os IN (106,107)";
            if( count($array_auditados) > 0){
                $auditados = implode(",",$array_auditados);
                $sql .= "
                    AND     os_status NOT IN ($auditados)
                ";
            }
            $sql .= "
                    LIMIT   1
            ";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res) == 1){
                $status_validado = pg_fetch_result($res,0,os_status);
                array_push($array_auditados,$status_validado);
            }else{
?>
        <tr id="105_<?=$valor['os_status']?>">
            <td><?=$valor['descricao']?></td>
            <td><?=$valor['observacao']?></td>
            <td style="text-align:center;">
                <button type="button" id="btn_105_aprova" class="btn btn-success">Aprovar</button>
                &nbsp;&nbsp;
                <button type="button" id="btn_105_reprova" class="btn btn-danger">Recusar</button>
            </td>
        </tr>
<?
            }
        break;
        case 118:
            $sql = "SELECT  os_status
                    FROM    tbl_os_status
                    WHERE   os = $os
                    AND     fabrica_status = $login_fabrica
                    AND     status_os IN (187)";
            if( count($array_auditados) > 0){
                $auditados = implode(",",$array_auditados);
                $sql .= "
                    AND     os_status NOT IN ($auditados)
                ";
            }
            $sql .= "
                    LIMIT   1
            ";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res) == 1){
                $status_validado = pg_fetch_result($res,0,os_status);
                array_push($array_auditados,$status_validado);
            }else{
?>
        <tr id="118_<?=$valor['os_status']?>">
            <td><?=$valor['descricao']?></td>
            <td><?=$valor['observacao']?></td>
            <td style="text-align:center;"><button type="button" id="btn_118_aprova" class="btn btn-primary">Autorizar</button></td>
        </tr>
<?
            }
        break;
        case 164:
            $sql = "SELECT  os_status
                    FROM    tbl_os_status
                    WHERE   os = $os
                    AND     fabrica_status = $login_fabrica
                    AND     status_os IN (165,166)";
            if( count($array_auditados) > 0){
                $auditados = implode(",",$array_auditados);
                $sql .= "
                    AND     os_status NOT IN ($auditados)
                ";
            }
            $sql .= "
                    LIMIT   1
            ";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res) == 1){
                $status_validado = pg_fetch_result($res,0,os_status);
                array_push($array_auditados,$status_validado);
            }else{
?>
        <tr id="164_<?=$valor['os_status']?>">
            <td><?=$valor['descricao']?></td>
            <td><?=$valor['observacao']?></td>
            <td style="text-align:center;">
                <button type="button" id="btn_164_aprova" class="btn btn-success">Aprovar</button>
                &nbsp;&nbsp;
                <button type="button" id="btn_164_reprova" class="btn btn-danger">Recusar</button>
            </td>
        </tr>
<?
            }
        break;
    }
}

if(count($array_auditados) == count($valores)){
?>
        <tr>
            <td colspan="3" style="text-align:center;background-color:#FF6347;color:#FFF;font-size:16px;font-weight:bold;">
                Esta OS não se encontra em intervenção.
            </td>
        </tr>
<?
}
?>
    </tbody>
</table>
