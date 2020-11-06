<?php

  /*
  Autor: Guilherme Silva
  Data: 13/09/2013
  */

  // error_reporting(E_ALL ^ E_NOTICE);

  include dirname(__FILE__) . '/../../dbconfig.php';
  include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
  require dirname(__FILE__) . '/../funcoes.php';
  include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    $sqlAdmin = "
        SELECT
          tbl_admin.login                                      ,
          tbl_admin.nome_completo                              ,
          tbl_admin.fone                                       ,
		  tbl_admin.email                                     ,
			tbl_admin.fabrica
      FROM tbl_hd_chamado
      JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
      JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_admin.fabrica
      LEFT JOIN tbl_admin atend ON tbl_hd_chamado.atendente = atend.admin
      WHERE       tbl_hd_chamado.exigir_resposta = 't'
      AND         tbl_hd_chamado.status <> 'Aprovação'
      AND         tbl_hd_chamado.status <> 'Resolvido'
      AND         tbl_hd_chamado.status <> 'Cancelado'
      GROUP BY tbl_admin.login, tbl_admin.nome_completo, tbl_admin.fone, tbl_admin.email, tbl_admin.fabrica
    ";
    $resAdmin = pg_query($con, $sqlAdmin);

    if(pg_num_rows($resAdmin) > 0){

      while($data = pg_fetch_object($resAdmin)){

        $admin_login   = $data->login;
        $nome_admin   = $data->nome_completo;
        $fone_admin   = $data->fone;
        $fabrica   = $data->fabrica;
        $email_admin['dest'][0]   = $data->email;

        $sqlChamado = "
                SELECT
                tbl_hd_chamado.hd_chamado                            ,
              tbl_hd_chamado.titulo                                ,
              tbl_hd_chamado.categoria                             ,
              tbl_hd_chamado.status                                ,
              tbl_hd_chamado.atendente                             ,
              tbl_hd_chamado.fabrica_responsavel                   ,
              tbl_hd_chamado.fabrica                               ,
              atend.nome_completo AS atendente_nome
            FROM tbl_hd_chamado
            JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
            JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_admin.fabrica
            LEFT JOIN tbl_admin atend ON tbl_hd_chamado.atendente = atend.admin
            WHERE       tbl_hd_chamado.exigir_resposta = 't'
            AND         tbl_hd_chamado.status <> 'Aprovação'
            AND         tbl_hd_chamado.status <> 'Resolvido'
			AND         tbl_hd_chamado.status <> 'Cancelado'
			AND		tbl_admin.fabrica = $fabrica
			AND     tbl_admin.login = '$admin_login'
        ";
        $resChamado = pg_query($con, $sqlChamado);

        if(pg_num_rows($resChamado) > 0){

          $chamados = "";

          while($data2 = pg_fetch_object($resChamado)){

            $chamados .= "<b>Chamado:</b> ".$data2->hd_chamado."<br>";
            $chamados .= "<b>Titulo:</b> ".$data2->titulo."<br> <br>";
            /* $chamados .= "<b>Categoria:</b> ".$data2->categoria."<br>";
            $chamados .= "<b>Status:</b> ".$data2->status."<br>";
            $chamados .= "<b>Atendente:</b> ".$data2->atendente."<br>";
            $chamados .= "<b>Fabrica Responsável:</b> ".$data2->fabrica."<br>";
            $chamados .= "<b>Fábrica:</b> ".$data2->fabrica_responsavel."<br>";
            $chamados .= "<b>Atendente:</b> ".$data2->atendente_nome."<br> <br>"; */

          }

        }

        $mensagem = "
          Prezado(a) <b>$nome_admin</b>, <br>
          Para que possamos dar continuidade ao desenvolvimento dos chamados mencionados abaixo, precisamos que as interações sejam respondidas. <br>
          Acesse o Help-Desk com seu <b>usuário</b> e <b>senha</b> e verifique as interações dentro de cada chamado inserindo sua resposta. <br> <br>
        ";

        $mensagem .= $chamados;

        $mensagem .= "Att. <b>Suporte Telecontrol</b> <br> <img src='http://www.telecontrol.com.br/wp-content/uploads/2012/02/logo_tc_2009_texto.png' > <br> <br> <i>Não responda este e-mail, pois ele é gerado automaticamente pelo sistema.</i> <br>";

        Log::envia_email($email_admin,"CHAMADO PENDENTE (EXIGINDO RESPOSTA)",$mensagem);

      // $mail = new PHPMailer();
      // $mail->IsSMTP();
      // $mail->IsHTML();
      // $mail->AddReplyTo("suporte@telecontrol.com.br", "Suporte Telecontrol");
      // $mail->Subject = "CHAMADO PENDENTE (EXIGINDO RESPOSTA)";
      // $mail->Body = $mensagem;
      // $mail->AddAddress($email_admin);
      // $mail->Send();

      }

    }

?>
