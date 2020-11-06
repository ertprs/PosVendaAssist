<?php
    /*
    Autor: Felipe Marttos Putti
    Data: 18/11/2016
    */

    // error_reporting(E_ALL ^ E_NOTICE);

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';
    
    $fabricas_sla = array();

    //BUSCA FABRICAS QUE POSSUI SLA
    $sqlFabSLA  = "SELECT fabrica FROM tbl_fabrica WHERE json_field('fabricante_sla',parametros_adicionais) = 't'";
    $resFabSLA  = pg_query($con, $sqlFabSLA);

    if (pg_num_rows($resFabSLA) > 0) {
        $fabricante_sla = pg_fetch_all($resFabSLA);
        foreach ($fabricante_sla as $key_sla => $value_sla) {
            $fabricas_sla[] = $value_sla['fabrica'];
        }
    }

    $email_admin['dest'][] = 'luis.carlos@telecontrol.com.br';
    $email_admin['dest'][] = 'ronaldo@telecontrol.com.br';
    $email_admin['dest'][] = 'ricardo.tamiao@telecontrol.com.br';
    $email_admin['dest'][] = 'paulo@telecontrol.com.br';
    $email_admin['dest'][] = 'joao.junior@telecontrol.com.br';

    $sqlChamado = "SELECT
          tbl_hd_chamado.hd_chamado         ,
          tbl_hd_chamado.titulo             ,
          tbl_hd_chamado.status             ,
          tbl_hd_chamado.atendente          ,
          to_char(tbl_hd_chamado.previsao_termino, 'DD/MM/YYYY')  AS previsao_termino,
          to_char(tbl_hd_chamado.data, 'DD/MM/YYYY')  AS data,
          tbl_fabrica.fabrica,
          tbl_fabrica.nome                             
         FROM tbl_hd_chamado
         JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_hd_chamado.fabrica
        WHERE tbl_hd_chamado.status <> 'Resolvido'
          AND tbl_hd_chamado.status <> 'Cancelado'
          AND tbl_hd_chamado.fabrica_responsavel = 10
          AND tbl_hd_chamado.fabrica IN (".implode(',', $fabricas_sla).")
          AND tbl_hd_chamado.previsao_termino IS NOT NULL
          AND tbl_hd_chamado.previsao_termino BETWEEN '".date('Y-m-d')." 00:00:00' AND '".date('Y-m-d')." 23:59:59'
        ";
    $resChamado = pg_query($con, $sqlChamado);

    if (pg_num_rows($resChamado) > 0) {

      $chamados .= "<br>
                    <table width='800' align = 'center' cellpadding='2' cellspacing='2' border='0' style='font-family: verdana ; font-size:12px ; color: #666666'>
                        <tr style='background-color:#3e83c9;color:#ffffff;'>
                            <th align='center'>HD</th>
                            <th align='center'>Fábrica</th>
                            <th align='left'>Título</th>
                            <th align='center'>Data de abertura</th>
                            <th align='center'>Data de Previsão do Cliente</th>
                        </tr>
                  ";

        while($data2 = pg_fetch_object($resChamado)){
            $hds_pendentes[] = $data2->hd_chamado;
            $chamados .= "<tr style='background-color:{$listras}'>";
                $chamados .= "<td align='center'><b>".$data2->hd_chamado."</b></td>";
                $chamados .= "<td align='center'>(".$data2->fabrica.") - ".$data2->nome."</td>";
                $chamados .= "<td>".$data2->titulo."</td>";
                $chamados .= "<td align='center'>".$data2->data."</td>";
                $chamados .= "<td align='center'><b style='color:#ff0000;'>".$data2->previsao_termino."</b></td>";
            $chamados .= "</tr>";
        }

        $chamados .= "</table><br>";

        $mensagem = "HD(S) abaixo</b>, está(ão) com previsão cadastrado para hoje. <b style='color:#ff0000;'>Favor Priorizar!</b> <br>";

        $mensagem .= $chamados;

        $mensagem .= "
        Att. <br> <b>Suporte Telecontrol</b> <br> 
        <i>Não responda este e-mail, pois ele é gerado automaticamente pelo sistema.</i> <br>";

        Log::envia_email($email_admin,"CHAMADOS SLA PENDENTES COM PREVISÃO DO CLIENTE PARA HOJE",$mensagem);
    }

