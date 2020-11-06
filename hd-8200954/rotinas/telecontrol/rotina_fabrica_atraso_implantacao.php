<?php
/**
 *
 * rotina_fabrica_atraso_implantacao.php
 *
 * Relatorio de fabricantes com parcela em atraso
 *
 * @author  Éderson Sandre
 * @version 2012.03.30
 *
 */
try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../funcoes.php';

    $vet['fabrica'] = 'Telecontrol';
    $vet['tipo']    = 'implantacao';
    $vet['dest']    = 'ederson.sandre@telecontrol.com.br';
    $vet['log']     = 2;

    $sql = "
            SELECT 
              tbl_fabrica.nome AS fabrica,
              tbl_controle_implantacao.numero_parcela,
              tbl_controle_parcela_implantacao.parcela,
              tbl_controle_parcela_implantacao.valor_entrada,
              to_char(tbl_controle_parcela_implantacao.data_prevista,'DD/MM/YYYY') AS data_prevista,
              tbl_controle_parcela_implantacao.observacao,
              tbl_controle_parcela_implantacao.pago,
              tbl_controle_parcela_implantacao.nf
            FROM tbl_controle_implantacao 
              JOIN tbl_controle_parcela_implantacao ON tbl_controle_parcela_implantacao.controle_implantacao = tbl_controle_implantacao.controle_implantacao
              JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_controle_implantacao.fabrica
            WHERE finalizada IS FALSE
              AND excluido IS FALSE
              AND (DATE(data_prevista) < DATE(NOW()) AND pago IS FALSE)
            ORDER BY fabrica, parcela ASC;";
    $res = pg_query($con, $sql);
    echo pg_last_error();

    if(pg_num_rows($res)){
      $css_titulo = 'font-size: 14px; text-align: center; padding: 3px; color: #fff; background-color: #3A4868';
      $css_th = 'font-size: 13px; text-align: center; padding: 3px; color: #fff; background-color: #596D9B';

      $data = "<br><br><table border='0' cellspacing='1' cellpadding='3' style='width: 95%; margin: 0 auto; font-size: 12px; font-family: Arial; background-color: #3A4868'>";
        $data .= "<tr>";
          $data .= "<th colspan='5' style='{$css_titulo}'>RELAT&Oacute;RIO DE FABRICANTE(S) COM PARCELA EM ATRASO</th>";
        $data .= "</tr>";
        $data .= "<tr>";
          $data .= "<th rowspan='2' style='{$css_th}'>Fabricante(s)</th>";
          //data .= "<th rowspan='2'>Parcelas</th>";
          $data .= "<th colspan='5' style='{$css_th}'>Dados da Parcela</th>";
        $data .= "</tr>";

        $data .= "<tr>";
          $data .= "<th style='{$css_th}'>Parcela</th>";
          $data .= "<th style='{$css_th}'>Nota Fiscal</th>";
          $data .= "<th style='{$css_th}'>Valor</th>";
          $data .= "<th style='{$css_th}'>Data Prevista</th>";
          $data .= "<th style='{$css_th}'>Observa&ccedil;&atilde;o</th>";
        $data .= "</tr>";

        for($i = 0; $i < pg_num_rows($res); $i++){
          extract(pg_fetch_array($res));
          $valor_entrada = number_format($valor_entrada,2,',','.');
 
          $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
          $data .= "<tr bgcolor='{$cor}'>";
            $data .= "<td>&nbsp;{$fabrica}</td>";
            $data .= "<td align='center'>&nbsp;{$parcela}</td>";
            $data .= "<td align='center'>&nbsp;{$nf}</td>";
            $data .= "<td align='right'>&nbsp;R$ {$valor_entrada}</td>";
            $data .= "<td align='center'>&nbsp;{$data_prevista}</td>";
            $data .= "<td>&nbsp;{$observacao}</td>";
          $data .= "</tr>";
        }

      $data .= "</table>";

      $data .= "<div style='width: 95%; margin: 0 auto; font-size: 10px; font-family: Arial; color: #3A4868; text-align: right'>
                  * Rotina autom&aacute;tica do sistema<br />
                  * Data ".Date('d/m/Y H:i:s')."
                </div>"; 

      $data .= "<div style='width: 95%; margin: 10px auto; font-size: 14px; font-family: Arial; color: #3A4868;'>
                  <b>Att.<br>Telecontrol Networking</b>
                </div>"; 

      include_once '../../class/email/mailer/class.phpmailer.php';
      $mailer = new PHPMailer();

      //$mailer->IsSMTP();
      $mailer->IsHTML();
      //$mailer->AddAddress('ederson.sandre@telecontrol.com.br');
      $mailer->AddAddress('valeria@telecontrol.com.br');
      $mailer->AddAddress('celso@telecontrol.com.br');
      $mailer->Subject = utf8_decode(Date('d/m/Y')." - RELATÓRIO DE FABRICANTE(S) COM PARCELA EM ATRASO");
      $mailer->Body = $data;
      $mailer->Send();

    }

    //echo $data;
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
