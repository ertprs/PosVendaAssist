<?php 
error_reporting(E_ALL ^ E_NOTICE);

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../funcoes.php';
    include_once __DIR__ . DIRECTORY_SEPARATOR . '../../class/communicator.class.php';

    define('APP', 'Fechamento do Mês');
    define('ENV','teste');
    $data_atual = date('d-m-Y-h-i-s');

    $vet['fabrica'] = 'Telecontrol';
    $vet['tipo']    = 'Fechamento do Mês';
    $vet['dest']    = ENV == 'teste' ? 'lucas.carlos@telecontrol.com.br' : 'helpdesk@telecontrol.com.br';
    $vet['log']     = 1;

    $arquivo = "erro_fechamento_mes_$data_atual.err";
    $destino = "/tmp/erro_fechamento_mes_$data_atual.err";

    $data_inicial   = date('Y-m-01', strtotime('-1 months', strtotime(date('Y-m-01'))));
    $data_final     = date('Y-m-t', strtotime('-1 months', strtotime(date('Y-m-t'))));

    $sqlx = "select fabrica
                FROM tbl_hd_franquia
                WHERE mes in (SELECT to_char(current_date-5,'MM')::numeric )
                AND   ano in (SELECT to_char(current_date-5,'YYYY')::numeric )
                AND   periodo_fim is null order by fabrica;";
    $resx = pg_query($con,$sqlx);

    for($i =0;$i<pg_num_rows($resx);$i++) {
        $fecha_fabrica = pg_fetch_result($resx,$i,fabrica);

        $sql="SELECT sum(hora_desenvolvimento) AS total_desenvolvimento,
                hora_utilizada
            FROM tbl_hd_chamado
            JOIN tbl_hd_franquia USING(fabrica)
            WHERE tbl_hd_franquia.fabrica=$fecha_fabrica
            AND   mes in  (SELECT to_char(current_date-5,'MM')::numeric )
            AND    ano in (SELECT to_char(current_date-5,'YYYY')::numeric )
            AND data_aprovacao
            BETWEEN '$data_inicial 00:00:00' and '$data_final 23:59:59'
            AND tbl_hd_chamado.status <> 'Cancelado'
            GROUP BY hora_utilizada,tbl_hd_chamado.fabrica,hora_franqueada ";
        $res=pg_query($con,$sql);
        $msg_erro.=pg_last_error($con);

        if(pg_num_rows($res) >0){
            $total_desenvolvimento = pg_fetch_result($res,0,total_desenvolvimento);
            $hora_utilizada        = pg_fetch_result($res,0,hora_utilizada);
        }else{
            $total_desenvolvimento = 0;
        }
        $res = @pg_query($con,"BEGIN TRANSACTION");
        
        $sql="UPDATE tbl_hd_franquia SET periodo_fim='$data_final 23:59:59'
                WHERE fabrica=$fecha_fabrica
                AND mes in (SELECT to_char(current_date-5,'MM')::numeric )
                AND ano in (SELECT to_char(current_date-5,'YYYY')::numeric )
                AND periodo_fim is null ";

        $res=pg_query($con,$sql);
        $msg_erro.=pg_last_error($con);

        if(strlen($total_desenvolvimento) ==0 or $total_desenvolvimento ==null) $total_desenvolvimento = 0;

        $sql="INSERT INTO tbl_hd_franquia (
                fabrica               ,
                mes                   ,
                ano                   ,
                hora_franqueada       ,
                valor_hora_franqueada ,
                saldo_hora            ,
                hora_utilizada        ,
                hora_faturada         ,
                periodo_inicio        ,
                hora_maxima
                )
                SELECT $fecha_fabrica,
                       case when mes =12 then 1 else mes+1 end as mes,
                       case when mes =12 then ano +1 else ano end as ano,
                       hora_franqueada,
                       valor_hora_franqueada,
                       (hora_franqueada+saldo_hora) - ($total_desenvolvimento) + (hora_faturada),
                       0,
                       0,
                       (periodo_fim::date + 1|| ' 00:00:00')::date,
                       hora_maxima
                FROM tbl_hd_franquia
                WHERE fabrica=$fecha_fabrica
                AND    mes in (SELECT to_char(current_date-5,'MM')::numeric )
                AND    ano in (SELECT to_char(current_date-5,'YYYY')::numeric );

                UPDATE tbl_hd_franquia SET saldo_hora = hora_maxima
                WHERE hd_franquia IN (SELECT hd_franquia FROM tbl_hd_franquia WHERE fabrica = $fecha_fabrica ORDER BY hd_franquia DESC LIMIT 1)
                AND   saldo_hora > hora_maxima;

                UPDATE tbl_hd_franquia SET saldo_hora = 0
                WHERE hd_franquia IN (SELECT hd_franquia FROM tbl_hd_franquia WHERE fabrica = $fecha_fabrica ORDER BY hd_franquia DESC LIMIT 1)
                AND   saldo_hora < 0";
        $res=pg_query($con,$sql);
        
        $msg_erro.=pg_last_error($con);

        if(strlen($msg_erro) > 0){
            $res = @pg_query ($con,"ROLLBACK TRANSACTION");
            $msg_erro .= 'Houve um erro na hora de fechar o mês.';
        }else{
            $res = @pg_query($con,"COMMIT TRANSACTION");
        }
    }


if(strlen(trim($msg_erro))> 0) {
    $msg = 'Script: '.__FILE__.'<br />Erro :<br />' . $msg_erro;
    $mail   = new TcComm('smtp@posvenda', 'noreply@telecontrol.com.br');

    $subject = date('d/m/Y') . " - Fechamento Mês Telecontrol";

    $mail->sendMail(
        'helpdesk@telecontrol.com.br',
        $subject,
        $msg,
        'noreply@telecontrol.com.br'
    );
    
    $fp = fopen("$arquivo", "a");
    fwrite($fp, "$msg");
    fclose($fp);

    system("mv {$arquivo} {$destino};");
}

?>