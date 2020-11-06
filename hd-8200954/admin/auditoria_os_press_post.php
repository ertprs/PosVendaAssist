<?php

 /**
  * @description - Página criada para concentrar todas
  * as entradas de auditorias da OS, junto com suas
  * devidas aprovaçoes (INTERAÇÕES NO BANCO DE DADOS)
  *
  * @todo SEMPRE lembrar de, ao ter que mexer nessa página, fazer as mesmas alterações nas outras,
  * @author William Ap. Brandino
  */

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';


$ajax           = filter_input(INPUT_POST,"ajax");
$fabrica        = filter_input(INPUT_POST,"fabrica",FILTER_VALIDATE_INT);
$admin          = filter_input(INPUT_POST,"admin",FILTER_VALIDATE_INT);
$os             = filter_input(INPUT_POST,"os",FILTER_VALIDATE_INT);
$status_os      = filter_input(INPUT_POST,"status_os",FILTER_VALIDATE_INT);
$os_status      = filter_input(INPUT_POST,"os_status",FILTER_VALIDATE_INT);
$km             = filter_input(INPUT_POST,"km",FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
$original_km    = filter_input(INPUT_POST,"original_km",FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
$acao           = filter_input(INPUT_POST,"acao");
$motivo         = filter_input(INPUT_POST,"motivo",FILTER_SANITIZE_FULL_SPECIAL_CHARS);

$motivo = utf8_encode($motivo);
/**
 *
 * - ARRAY contendo os status de aprovacao e reprovação
 * de cada tipo de auditoria / intervenção
 */

$auditorias = array(
    62 => array(
        "aprova"    => 64
    ),
    67 => array(
        "aprova"    => 19,
        "reprova"   => 13
    ),
    98 => array(
        "aprova"    => array(99,100),
        "reprova"   => 101
    ),
    102 => array(
        "aprova"    => 103,
        "reprova"   => 104
    ),
    105 => array(
        "aprova"    => 106,
        "reprova"   => 107
    ),
    118 => array(
        "aprova"    => 187
    ),
    164 => array(
        "aprova"    => 166,
        "reprova"   => 165
    )
);

if($ajax == "gravacao"){
    switch($status_os){
        case 62:

        break;
        case 67:
        case 102:
        case 105:
        case 118:
        case 164:
            $sql = pg_query($con,"BEGIN TRANSACTION");
            if($acao == "aprova"){
                $sql = "
                    INSERT INTO tbl_os_status (
                        os,
                        status_os,
                        data,
                        observacao,
                        admin
                    ) VALUES (
                        $os,
                        ".$auditorias[$status_os]['aprova'].",
                        CURRENT_TIMESTAMP,
                        'Obs: $motivo',
                        $admin
                    );
                ";
            }else if($acao == "reprova"){
                $sql = "
                    INSERT INTO tbl_os_status (
                        os,
                        status_os,
                        data,
                        observacao,
                        admin
                    ) VALUES (
                        $os,
                        ".$auditorias[$status_os]['reprova'].",
                        CURRENT_TIMESTAMP,
                        'Obs: $motivo',
                        $admin
                    );
                ";
            }
            $res = pg_query($con,$sql);
            if(!pg_last_error($con)){
                if($acao == "reprova"){
                    $sqlPosto = "
                        SELECT  posto
                        FROM    tbl_os
                        WHERE   os = $os
                    ";
                    $resPosto = pg_query($con,$sqlPosto);
                    $posto = pg_fetch_result($resPosto,0,posto);

                    $sqlComu = "
                        INSERT INTO tbl_comunicado(
                            fabrica,
                            posto,
                            mensagem,
                            tipo,
                            obrigatorio_site,
                            ativo
                        ) VALUES (
                            $fabrica,
                            $posto,
                            'A OS $os foi Recusada pelo fabricante. Motivo: $motivo',
                            'Comunicado',
                            TRUE,
                            TRUE
                        );
                    ";
                    $resComu = pg_query($con,$sqlComu);
                }
                if(pg_last_error($con)){
                    $erro = pg_last_error($con);
                    $res = pg_query($con,"ROLLBACK TRANSACTION");
                    echo "erro: ".$erro;
                }else{
                    $res = pg_query($con,"COMMIT TRANSACTION");
                    echo json_encode(array("status" => "ok"));
                }
            }else{
                $erro = pg_last_error($con);
                $res = pg_query($con,"ROLLBACK TRANSACTION");
                echo "erro: ".$erro;
            }
        break;
    }
    exit;
}else if($ajax == "km"){
    $sql = "
        SELECT  qtde_km
        FROM    tbl_os
        WHERE   os = $os
    ";
    $res = pg_query($con,$sql);

    $qtde_km = pg_fetch_result($res,0,qtde_km);
    echo json_encode(array("res_km" => (float)$qtde_km));

    exit;
}else if($ajax == "gravacao_km"){
    $res = pg_query($con,"BEGIN TRANSACTION");
    if($acao == "aprova"){
        if($km == $original_km){
            $sql = "
                INSERT INTO tbl_os_status (
                    os,
                    status_os,
                    data,
                    observacao,
                    admin
                ) VALUES (
                    $os,
                    99,
                    CURRENT_TIMESTAMP,
                    '$motivo',
                    $admin
                );
            ";
            $res = pg_query($con,$sql);
        }else{
            $sql = "
                INSERT INTO tbl_os_status(
                    os,
                    status_os,
                    data,
                    observacao,
                    admin
                ) VALUES (
                    $os,
                    100,
                    CURRENT_TIMESTAMP,
                    '$motivo - O Km foi ALTERADO de $original_km para $km ',
                    $admin
                )";
            $res = pg_query($con,$sql);

            $sqlCalcOs  = "SELECT fn_calcula_os_esmaltec($os,$fabrica)";
            $resCalcOs  = pg_query($con, $sqlCalcOs);

            $sqlCalcExt = "SELECT fn_calcula_extrato($fabrica, extrato) FROM tbl_os_extra JOIN tbl_extrato using(extrato) WHERE os = $os AND extrato IS NOT NULL and fabrica = $fabrica";
            $resCalcExt = pg_query($con,$sqlCalcExt);
        }
    }else{
        $sql = "
            INSERT INTO tbl_os_status (
                os,
                status_os,
                data,
                observacao,
                admin
            ) VALUES (
                $os,
                101,
                CURRENT_TIMESTAMP,
                '$motivo',
                $admin
            )";

        $res = pg_query($con,$sql);
        $sqlZeraKm = "
            UPDATE  tbl_os
            SET     qtde_km             = 0,
                    qtde_km_calculada   = 0
            WHERE   os      = $os
            AND     fabrica = $fabrica;

            UPDATE  tbl_os_extra
            SET     qtde_km         = 0,
                    valor_por_km    = 0
            WHERE   os = $os;
        ";
        $resZeraKm = pg_query($con,$sqlZeraKm);

        $sqlCalcExt = "
            SELECT  fn_calcula_extrato($fabrica, extrato)
            FROM    tbl_os_extra
            JOIN    tbl_extrato USING(extrato)
            WHERE   os = $os
            AND     extrato IS NOT NULL
            AND     fabrica = $fabrica
        ";
        $resCalcExt = pg_query($con,$sqlCalcExt);
    }

    if(!pg_last_error($con)){
        $res = pg_query($con,"COMMIT TRANSACTION");
        echo json_encode(array("status" => "ok"));
    }else{
        $erro = pg_last_error($con);
        $res = pg_query($con,"ROLLBACK TRANSACTION");
        echo "erro: ".$erro;
    }

    exit;
}else if($ajax == "peca_critica"){
    $res = pg_query($con,"BEGIN TRANSACTION");

    $sql = "
        INSERT INTO tbl_os_status (
            os,
            status_os,
            data,
            observacao,
            admin
        ) VALUES (
            $os,
            64,
            CURRENT_TIMESTAMP,
            'Liberada peça crítica. Motivo: $motivo',
            $admin
        );
    ";
    $res = pg_query($con,$sql);

    if(pg_last_error($con)){
        $erro = pg_last_error($con);
        $res = pg_query($con,"ROLLBACK TRANSACTION");
        echo "erro: ".$erro;
    }else{
        $res = pg_query($con,"COMMIT TRANSACTION");
        echo json_encode(array("status" => "ok"));
    }
    exit;
}
?>
