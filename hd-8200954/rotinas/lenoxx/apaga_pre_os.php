<?php

try{
    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../funcoes.php';

    define('APP', 'Exclusão Pre-OS Sem movimentação há mais de 60 dias!');

    $fabricas = array(
        11 => "lenoxx",
        172 => "pacific",
    );

    if (array_key_exists(1, $argv)) {
        $login_fabrica = $argv[1];
    } else {
        $login_fabrica = 11;
    }

    if (!array_key_exists($login_fabrica, $fabricas)) {
        die("ERRO: argumento inválido - " . $login_fabrica . "\n");
    }

    $vet['fabrica'] = ucfirst($fabricas[$login_fabrica]);
    $vet['tipo']    = 'excluidos';
    $vet['dest']    = array('helpdesk@telecontrol.com.br');
    $vet['log']     = 2;
    $vet_erro = '';

    /**
    * - Verifica se há PRE-OS abertas há mais de 60 dias sem OS aberta
    */
    $sql = "SELECT  tbl_hd_chamado_extra.hd_chamado
            INTO    TEMP temp_hd_chamado_60_dias
            FROM    tbl_hd_chamado_extra
            JOIN    tbl_hd_chamado  ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
            WHERE   tbl_hd_chamado_extra.abre_os    IS TRUE
            AND     tbl_hd_chamado_extra.os         IS NULL
            AND     tbl_hd_chamado.fabrica = $login_fabrica
            AND     (tbl_hd_chamado.data::date + INTERVAL '60 days') < CURRENT_DATE
    ";
    $res = pg_query($con,$sql);
    $msg_erro = pg_errormessage($con);
    if (!empty($msg_erro)) {
        $vet_erro[] = $msg_erro;
    }

    /**
    * - Às vezes, as OS's são criadas, porém não atualizadas na tabela acima
    * Por isso, faz-se verificação e, caso a OS esteja aberta por um desses chamados, atualiza
    */
    $sqlOS = "  SELECT  tbl_os.os           ,
                        tbl_os.hd_chamado
                FROM    tbl_os
                JOIN    temp_hd_chamado_60_dias USING (hd_chamado)
                WHERE   tbl_os.fabrica = $login_fabrica
    ";
    $resOS = pg_query($con,$sqlOS);
    $msg_erro = pg_errormessage($con);
    if (!empty($msg_erro)) {
        $vet_erro[] = $msg_erro;
    }

    if(pg_num_rows($resOS) > 0){
        for($i=0;$i < pg_num_rows($resOS); $i++){
            $os_atualizar = pg_fetch_result($resOS,$i,os);
            $hd_atualizar = pg_fetch_result($resOS,$i,hd_chamado);

            $sqlUpOS = "UPDATE  tbl_hd_chamado_extra
                        SET     os = $os_atualizar
                        WHERE   hd_chamado = $hd_atualizar
            ";
            $resUpOS = pg_query($con,$sqlUpOS);
            $msg_erro = pg_errormessage($con);
            if (!empty($msg_erro)) {
                $vet_erro[] = $msg_erro;
            }

            /**
            * - Caso exista essa OS, DEVO eliminar a linha da tabela temporária
            * para que não seja marcado abre_os FALSE.
            */
            $sqlDelTemp = " DELETE FROM temp_hd_chamado_60_dias
                            WHERE hd_chamado = $hd_atualizar
            ";
            $resDelTemp = pg_query($con,$sqlDelTemp);
            $msg_erro = pg_errormessage($con);
            if (!empty($msg_erro)) {
                $vet_erro[] = $msg_erro;
            }
        }
    }

    /**
    * - Agora, interação no banco para apagar a Pre-OS
    * com criação há mais de 60 dias e sem movimentação
    */

    $sqlUpHD = "UPDATE  tbl_hd_chamado_extra
                SET     abre_os = false
                WHERE   hd_chamado IN   (
                                            SELECT  temp_hd_chamado_60_dias.hd_chamado
                                            FROM    temp_hd_chamado_60_dias
                                        )
    ";
    $resUpHD = pg_query($con,$sqlUpHD);
    $msg_erro = pg_errormessage($con);
    if (!empty($msg_erro)) {
        $vet_erro[] = $msg_erro;
    }

    $sqlFinal = "   SELECT  COUNT(temp_hd_chamado_60_dias.hd_chamado) AS qtde_hd
                    FROM    temp_hd_chamado_60_dias
    ";
    $resFinal = pg_query($con,$sqlFinal);
    $qtde_hd = pg_fetch_result($resFinal,0,qtde_hd);

    Log::envia_email($vet, APP, 'Rotina rodada com sucesso!<br />Total de '.$qtde_hd.' registros!');

}catch(Exception $e){
    $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();

    Log::log2($vet, $msg);
    Log::envia_email($vet, APP, $msg);
}
?>
