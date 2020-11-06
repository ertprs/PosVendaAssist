<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "autentica_admin.php";

$btn_troca = $_POST['osacao'];

if($btn_troca == "trocar_produto" && strlen($msg_erro) == 0) {
  $msg_erro = "";

  $oss = $_POST["os"];
  $ref_produto_carteira = $_POST['ref_produto_carteira'];

  $setor                 = $_POST["setor"];
  $situacao_atendimento  = $_POST["situacao_atendimento"];
  $envio_consumidor      = $_POST["envio_consumidor"];
  $modalidade_transporte = $_POST["modalidade_transporte"];
  $gerar_pedido          = $_POST["gerar_pedido"];
  $causa_troca           = $_POST["causa_troca"];

  $oss = str_replace("\\", "", $oss);
  $oss = json_decode($oss,true);

  $sql = "BEGIN TRANSACTION";
  $res = pg_query($con,$sql);

  if(strlen($ref_produto_carteira) == 0){
    $msg_erro .= 'Selecione um produto <br /> ';
  }

  if(count($oss) == 0){
    $msg_erro .= 'Selecione uma Ordem de Serviço <br /> ';
  }

  foreach ($oss as $os) {

    #pega id produto troca
    $sql = "SELECT *
             FROM tbl_produto
             JOIN tbl_familia USING(familia)
             WHERE referencia = '$ref_produto_carteira'
             AND fabrica = $login_fabrica;";
    $res = @pg_query($con, $sql);
    $ref_produto_troca = pg_fetch_result($res, 0, 'produto');
    $msg_erro .= pg_errormessage($con);

    #############################

    # pega ID produto, id os e id posto da OS selecionada
    $sql = "SELECT produto, sua_os, posto FROM tbl_os WHERE os = $os;";
    $res = pg_query($con,$sql);
    $msg_erro .= pg_errormessage($con);

    $produto = pg_fetch_result($res, 0, 'produto');
    $sua_os  = pg_fetch_result($res, 0, 'sua_os');
    $posto   = pg_fetch_result($res, 0, 'posto');
    ##############################

    $sql_produto = "SELECT *
            FROM tbl_os_produto
            WHERE produto = $produto
            AND os = $os";
    $res_produto =pg_query($con,$sql_produto );
    $msg_erro .= pg_errormessage($con);

    if(pg_num_rows($res_produto) > 0){

      $sql_con = "SELECT  tbl_os_item.pedido,
                    tbl_os_item.pedido_item,
                    tbl_os_item.os_item,
                    tbl_os_item.peca,
                    tbl_os_item.qtde,
                    tbl_pedido.posto,
                    tbl_peca.referencia,
                    tbl_peca.descricao
              FROM   tbl_os_item
              JOIN   tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
              JOIN   tbl_produto ON tbl_os_produto.produto = tbl_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
              JOIN   tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado AND tbl_servico_realizado.fabrica = {$login_fabrica}
              JOIN   tbl_peca ON tbl_os_item.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica
              LEFT JOIN tbl_pedido ON tbl_os_item.pedido = tbl_pedido.pedido AND tbl_pedido.fabrica = $login_fabrica
              LEFT JOIN tbl_pedido_cancelado ON tbl_os_item.pedido = tbl_pedido_cancelado.pedido AND tbl_pedido_cancelado.pedido_item = tbl_os_item.pedido_item
              WHERE  tbl_os_produto.os = {$os}
              AND    tbl_os_item.produto_i = {$produto}
              AND    tbl_os_item.fabrica_i = {$login_fabrica}
              AND    tbl_pedido_cancelado.pedido IS NULL";
      $res_con = pg_query($con,$sql_con);
      $msg_erro .= pg_errormessage($con);

      if(pg_num_rows($res_con) > 0){
        for ($i=0; $i < $res_con; $i++) {
          $pedido_fat             = pg_fetch_result($res_con, $i, 'pedido');
          $peca_fat               = pg_fetch_result($res_con, $i, 'peca');
          $pedido_item            = pg_fetch_result($res_con, $i, 'pedido_item');
          $os_item                = pg_fetch_result($res_con, $i, 'os_item');
          $qtde                   = pg_fetch_result($res_con, $i, 'qtde');
          $pedido_peca_referencia = pg_fetch_result($res_con,$i,'referencia');
          $pedido_peca_descricao  = pg_fetch_result($res_con,$i,'descricao');
          $pedido_posto           = pg_fetch_result($res_con,$i,'posto');

          if(strlen($pedido_fat) > 0){
            $sql_faturamento = " SELECT *
                      FROM tbl_faturamento_item
                      WHERE pedido = {$pedido_fat}
                      AND tbl_faturamento_item.os = {$os}
                      AND tbl_faturamento_item.peca = {$peca_fat}";
            $res_faturamento = pg_query($con, $sql_faturamento);
            $msg_erro .= pg_errormessage($con);

            if(pg_num_rows($res_faturamento) > 0){
              $msg_erro.= 'A OS '.$sua_os.' já esta faturada, TROCA NÃO REALIZADA <br />';
            }else{
              if(strlen($msg_erro) == 0){
                $distrib = 'null';

                $sql2 = "SELECT fn_pedido_cancela_garantia($distrib,$login_fabrica,$pedido_fat,$peca_fat,$os_item,'Troca de Produto',$login_admin); ";
                $res_x2 = pg_query($con,$sql2);
                $msg_erro .= pg_errormessage($con);

                if(strlen($msg_erro) == 0){
                  $sql = "SELECT fn_atualiza_status_pedido($login_fabrica, $pedido_fat);";
                  $res = pg_exec ($con,$sql);
                  $msg_erro .= pg_errormessage($con);
                }
                if(strlen($msg_erro) > 0){
                    continue;
                }
              }
            }
          }
        }// for..
      }
    }

    $sql = "SELECT *
          FROM tbl_produto
          JOIN tbl_familia USING(familia)
          WHERE produto = '$ref_produto_troca'
          AND fabrica = $login_fabrica";
    $resProd   = pg_query($con,$sql);
    $msg_erro .= pg_errormessage($con);

    if(pg_num_rows($resProd) == 0) {
        $msg_erro .= "Produto informado não encontrado <br />";
    }else{

      $troca_produto    = pg_fetch_result($resProd, 0, 'produto');
      $troca_ipi        = pg_fetch_result($resProd, 0, 'ipi');
      $troca_referencia = pg_fetch_result($resProd, 0, 'referencia');
      $troca_descricao  = pg_fetch_result($resProd, 0, 'descricao');
      $troca_familia    = pg_fetch_result($resProd, 0, 'familia');
      $troca_linha      = pg_fetch_result($resProd, 0, 'linha');

      $troca_descricao = substr($troca_descricao,0,50);
    }

    if (strlen($msg_erro) == 0) {

      $sql = "SELECT *
                FROM tbl_peca
               WHERE referencia = '$troca_referencia'
                 AND fabrica    = $login_fabrica";
      $res = pg_query($con, $sql);
      $msg_erro .= pg_errormessage($con);

      if (pg_num_rows($res) == 0) {
        if (strlen($troca_ipi) == 0) $troca_ipi = 10;
        $sql = "SELECT peca
                      FROM tbl_peca
                     WHERE fabrica    = $login_fabrica
                       AND referencia = '$troca_referencia'
                     LIMIT 1;";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        if (pg_num_rows($res) > 0) {
          $peca = pg_fetch_result($res, 0, 0);
        }else{
          $sql = "INSERT INTO tbl_peca (
                      fabrica,
                      referencia,
                      descricao,
                      ipi,
                      origem,
                      produto_acabado
                  ) VALUES (
                      $login_fabrica,
                      '$troca_referencia',
                      '$troca_descricao',
                      $troca_ipi,
                      'NAC',
                      't'
                  )";

          $res = pg_query($con,$sql);
          $msg_erro .= pg_errormessage($con);

          $sql = "SELECT CURRVAL ('seq_peca')";
          $res = pg_query($con,$sql);
          $msg_erro .= pg_errormessage($con);
          $peca = pg_fetch_result($res,0,0);
        }

        $sql = "INSERT INTO tbl_lista_basica (
                    fabrica,
                    produto,
                    peca,
                    qtde
                ) VALUES (
                    $login_fabrica,
                    $produto,
                    $peca,
                    1
                );";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);
      }else{
        $produto_acabado = pg_fetch_result($res,0,'produto_acabado');
        $peca = pg_fetch_result($res, 0, 'peca');

        if($produto_acabado <> 't') {
          $msg_erro = "Favor verificar o cadastro da peça $troca_referencia, deve estar marcardo como produto acabado para realizar a troca <br />";
        }
      }
    }

    /*$sql_peca2 = "SELECT DISTINCT tbl_tabela_item.preco, tbl_tabela_item.tabela
                    FROM tbl_peca
                    JOIN tbl_tabela_item ON tbl_peca.peca = tbl_tabela_item.peca
                    JOIN tbl_posto_linha ON tbl_tabela_item.tabela = tbl_posto_linha.tabela
                    JOIN tbl_linha ON tbl_posto_linha.linha = tbl_linha.linha
                    AND tbl_linha.fabrica = $login_fabrica
                    WHERE tbl_peca.referencia = '$troca_referencia'
                    AND tbl_peca.fabrica = $login_fabrica
                    AND tbl_posto_linha.posto = $posto
                    AND tbl_tabela_item.tabela = tbl_posto_linha.tabela";
    $res2 = pg_query($con,$sql_peca2);

    if(pg_num_rows($res2) == 0){
      $msg_erro = "O produto $troca_referencia não tem preço na tabela de preço. Cadastre o preço para poder dar continuidade na troca.";
    }*/

    $sql = "SELECT credenciamento
            FROM  tbl_posto_fabrica
            JOIN  tbl_os ON tbl_os.posto = tbl_posto_fabrica.posto
            WHERE tbl_os.fabrica            = $login_fabrica
            AND   tbl_os.os                 = $os
            AND   tbl_posto_fabrica.fabrica = $login_fabrica
            AND   tbl_posto_fabrica.credenciamento = 'DESCREDENCIADO';";
    $res = pg_query ($con,$sql);
    if(pg_num_rows($res)>0){
      $msg_erro .= "Este posto está DESCREDENCIADO. Não é possível efetuar a troca do produto.<br />";
    }

    $sql = "UPDATE tbl_os
          SET data_fechamento = NULL,finalizada=null
          WHERE os = $os
          AND fabrica = $login_fabrica ";
    $res = pg_query($con,$sql);
    $msg_erro .= pg_errormessage($con);

    $sql = "SELECT os_troca,peca,os FROM tbl_os_troca WHERE os = $os AND pedido IS NULL ";
    $res = pg_query ($con,$sql);

    if(pg_num_rows($res)>0){

      $troca_efetuada =  pg_fetch_result($res,0,os_troca);
      $troca_os       =  pg_fetch_result($res,0,os);
      $troca_peca     =  pg_fetch_result($res,0,peca);



      $sql = "DELETE FROM tbl_os_troca WHERE os_troca = $troca_efetuada";
      $sql = "UPDATE tbl_os_troca SET os = 4836000 WHERE os_troca = $troca_efetuada";
      $res = pg_query ($con,$sql);

      // HD 13229
      if(strlen($troca_peca) > 0) {
        $sql = "UPDATE tbl_os_produto set os = 4836000 FROM tbl_os_item WHERE tbl_os_item.os_produto=tbl_os_produto.os_produto AND os=$troca_os and peca = $troca_peca";
        $res = pg_query ($con,$sql);
      }
    }

    $sql = "SELECT status_os FROM tbl_os_status WHERE os=$os AND status_os IN (62,64,65,72,73,87,88,116,117,127) ORDER BY data DESC LIMIT 1";
    $res = pg_query($con,$sql);
    $qtdex = pg_num_rows($res);

    if($qtdex>0){
      $statuss=pg_fetch_result($res,0,status_os);
      $status_arr = array(62,65,72,87,116,127);

      if (in_array($statuss,$status_arr)){

        $proximo_status = "64";

        if ( $statuss == "72"){
            $proximo_status = "73";
        }
        if ( $statuss == "87"){
            $proximo_status = "88";
        }
        if ( $statuss == "116"){
            $proximo_status = "117";
        }

        $sql = "INSERT INTO tbl_os_status
                (os,status_os,data,observacao,admin)
                VALUES ($os,$proximo_status,current_timestamp,'OS Liberada',$login_admin)";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);
      }
    }

    switch($login_fabrica) {
      case 3:
        $id_servico_realizado        = 20;
        $id_servico_realizado_ajuste = 96;
        $id_solucao_os               = 85;
        $defeito_constatado          = 10224;
        break;
    }

    if(strlen($id_servico_realizado_ajuste)>0 AND strlen($id_servico_realizado)>0){

      $sql =  "UPDATE tbl_os_item
              SET servico_realizado = $id_servico_realizado_ajuste
              WHERE os_item IN (
                  SELECT os_item
                  FROM tbl_os
                  JOIN tbl_os_produto USING(os)
                  JOIN tbl_os_item USING(os_produto)
                  JOIN tbl_peca USING(peca)
                  WHERE tbl_os.os       = $os
                  AND tbl_os.fabrica    = $login_fabrica
                  AND tbl_os_item.servico_realizado = $id_servico_realizado
                  AND tbl_os_item.pedido IS NULL
              )";
      $res = pg_query($con,$sql);
      $msg_erro .= pg_errormessage($con);
    }

    if (strlen($defeito_constatado)>0 AND strlen($id_solucao_os)>0){
      $sql = "UPDATE tbl_os
          SET solucao_os         = $id_solucao_os,
            defeito_constatado = $defeito_constatado
          WHERE os       = $os
          AND fabrica    = $login_fabrica
          AND solucao_os IS NULL
          AND defeito_constatado IS NULL";
      $res = pg_query($con,$sql);
      $msg_erro .= pg_errormessage($con);
    }

    $sql_obs = "SELECT orientacao_sac
                  from tbl_os join tbl_os_extra using(os)
                  where os = $os";
    $res_obs            = pg_query($con,$sql_obs);
    $orientacao_sac_aux = pg_fetch_result($res_obs,0,orientacao_sac);
    $orientacao_sac = $orientacao_sac_aux;

    if (strlen($orientacao_sac) == 0) {
      $orientacao_sac  = "null";
    }else{
      $orientacao_sac = htmlentities ($orientacao_sac,ENT_QUOTES);
      $orientacao_sac = nl2br ($orientacao_sac);
    }

    if(strlen(trim($orientacao_sac))>0 AND trim($orientacao_sac)!='null'){
      $orientacao_sac =  date("d/m/Y H:i")." - ".$orientacao_sac;
      $sql = "UPDATE  tbl_os_extra SET
                  orientacao_sac =  CASE WHEN orientacao_sac IS NULL OR orientacao_sac = 'null' THEN '' ELSE orientacao_sac || ' \n' END || trim('$orientacao_sac')
              WHERE tbl_os_extra.os = $os;";
    }

    $res = pg_query ($con,$sql);
    $msg_erro .= pg_errormessage($con);

    if(strlen($msg_erro) == 0){

      $sql = "INSERT INTO tbl_os_produto (os, produto) VALUES ($os, $produto);";
      $res = pg_query($con,$sql);
      $msg_erro .= pg_errormessage($con);

      $sql = "SELECT CURRVAL ('seq_os_produto')";
      $res = pg_query($con,$sql);
      $msg_erro .= pg_errormessage($con);

      $os_produto = pg_fetch_result($res,0,0);

      if(strlen($msg_erro) == 0){
        $sql = "SELECT servico_realizado FROM tbl_servico_realizado WHERE troca_produto AND fabrica = $login_fabrica" ;
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);
        if(pg_num_rows($res) > 0){
            $servico_realizado = pg_fetch_result($res,0,0);
        }

        if(strlen($servico_realizado)==0) $msg_erro .= "Não existe Serviço Realizado de Troca de Produto, favor cadastrar! <br />";

        $aguardando_peca_reparo = 'f';

        $quantidade_item = (int) $_POST["quantidade_item"];
      }

      if(strlen($msg_erro)==0){

        $sql = "INSERT INTO tbl_os_item (os_produto, peca, qtde, servico_realizado, admin,aguardando_peca_reparo)
                VALUES ($os_produto, $peca, " . ($login_fabrica == 81 ? $quantidade_item : 1) . ",$servico_realizado, $login_admin,'$aguardando_peca_reparo')";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        $sql = "SELECT data_fechamento FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica AND data_fechamento IS NOT NULL";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        if (pg_num_rows($res)==1){
            $sql = "UPDATE tbl_os SET
                    troca_garantia          = 't',
                    ressarcimento           = 'f',
                    troca_garantia_admin    = $login_admin
                    WHERE os = $os AND fabrica = $login_fabrica";
        }else{
          $sql = "UPDATE tbl_os SET
                 troca_garantia          = 't',
                  ressarcimento           = 'f',
                  troca_garantia_admin    = $login_admin,
                  data_conserto           = CURRENT_TIMESTAMP
                  WHERE os = $os AND fabrica = $login_fabrica";
        }
        $res = @pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        //--== Novo Procedimento para Troca | Raphael Giovanini ===========

        if(strlen($msg_erro) == 0 ){

        $sql = "INSERT INTO tbl_os_troca (
                      setor                 ,
                      situacao_atendimento  ,
                      envio_consumidor      ,
                      modalidade_transporte ,
                      causa_troca           ,
                      os                    ,
                      admin                 ,
                      peca                  ,
                      gerar_pedido          ,
                      fabric
                  )VALUES(
                      '$setor'                    ,
                      '$situacao_atendimento'     ,
                      '$envio_consumidor'         ,
                      '$modalidade_transporte'    ,
                      $causa_troca              ,
                      $os                       ,
                      $login_admin              ,
                      $peca                     ,
                      '$gerar_pedido'             ,
                      $login_fabrica
                  )";
          $res = pg_query($con,$sql);

          if (strlen(pg_last_error()) > 0) {
            $msg_erro .= pg_last_error();
          }
        }
      }
    }
  }#foreach
  if(strlen($msg_erro) > 0){

    $res = pg_query($con,"ROLLBACK TRANSACTION");

    $arrayRet = array("statuss" => "error","mensagem" => utf8_encode($msg_erro));
    $ret = json_encode($arrayRet);
    echo $ret;
  }else{
    $res = pg_query($con,"COMMIT TRANSACTION");
    $sucess = "Troca realizada com sucesso";
    $arrayRet = array("statuss" => "ok","mensagem" => $sucess);
    $ret = json_encode($arrayRet);
    echo $ret;
  }


}#post produto

if($btn_troca == "trocar_peca" && strlen($msg_erro) == 0){

  $msg_erro = "";
  $oss = $_POST["os"];
  $ref_peca_carteira = $_POST['ref_peca_carteira'];

  $pecaa = $_POST["peca"];

  $pecaa = str_replace("\\", "", $pecaa);
  $pecaa = json_decode($pecaa,true);

  $oss = str_replace("\\", "", $oss);
  $oss = json_decode($oss,true);


  if(strlen($ref_peca_carteira) == 0){
    $msg_erro .= 'Selecione a Peça a ser trocada <br />';
  }

  if(count($pecaa) == 0){
    $msg_erro .= 'Selecione a Peça para Troca <br />';
  }


  if(count($oss) == 0){
    $msg_erro .= 'Selecione uma Ordem de Serviço <br />';
  }



  $sql = "BEGIN TRANSACTION";
  $res = pg_query($con,$sql);

  foreach ($oss as $os) {

    $sql_os = "SELECT sua_os
              FROM tbl_os
              WHERE os = $os
              AND fabrica = $login_fabrica";
    $res = pg_query($con, $sql_os);
    $os_consumidor = pg_fetch_result($res, 0, 'sua_os');

    $sql = "SELECT peca, descricao
           FROM tbl_peca
           WHERE referencia = '$ref_peca_carteira'
           AND fabrica = $login_fabrica;";
	$res = pg_query($con, $sql);
	if(pg_num_rows($res) > 0) {
			$peca_carteira            = pg_fetch_result($res, 0, 'peca');
			$peca_carteira_descricao  = pg_fetch_result($res, 0, 'descricao');


			$sql = "SELECT  tbl_os_item.pedido,
							tbl_os_item.pedido_item,
							tbl_os_item.os_item,
							tbl_os_item.defeito,
							tbl_os_item.parametros_adicionais,
							tbl_os_item.servico_realizado,
							tbl_os_item.posto_i,
							tbl_os_item.qtde,
							tbl_os_produto.produto,
							tbl_produto.referencia,
							tbl_os_produto.os_produto,
							tbl_os.sua_os
					  FROM   tbl_os_item
					  JOIN   tbl_servico_realizado USING (servico_realizado)
					  JOIN   tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
					  JOIN   tbl_produto ON tbl_os_produto.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
					  JOIN   tbl_os ON tbl_os_produto.os = tbl_os.os AND tbl_os.fabrica = $login_fabrica AND tbl_os.os = $os
					  WHERE  tbl_os_produto.os = $os
					  AND    tbl_os_item.peca = $peca_carteira
					  AND    tbl_servico_realizado.troca_de_peca
					  AND    tbl_os_item.fabrica_i = $login_fabrica";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);

			if(pg_num_rows($res)>0){

			  $pedido           = pg_fetch_result($res, 0, 'pedido');
			  $posto            = pg_fetch_result($res, 0, 'posto_i');
			  $qtde_peca        = pg_fetch_result($res, 0, 'qtde');
			  $pedido_item      = pg_fetch_result($res, 0, 'pedido_item');
			  $motivo_cancelado = "'Troca em lote'";
			  $os_item          = pg_fetch_result($res, 0, 'os_item');
			  $os_produto       = pg_fetch_result($res, 0, 'os_produto');
			  $defeito          = pg_fetch_result($res, 0, 'defeito');
			  $servico          = pg_fetch_result($res, 0, "servico_realizado");
			  $pa               = pg_fetch_result($res, 0, 'paramentros_adicionais');
			  $produto          = pg_fetch_result($res, 0, 'produto');
			  $ref_produto      = pg_fetch_result($res, 0, "referencia");
			  $sua_os           = pg_fetch_result($res, 0, "sua_os");

			  if(strlen($msg_erro) == 0 AND $pedido > 0){

				$sql  = "SELECT fn_pedido_cancela_garantia(null,$login_fabrica,$pedido,$peca_carteira,$os_item,$motivo_cancelado,$login_admin)";
				$res  = pg_query ($con,$sql);

				$msg_erro .= pg_errormessage($con);

				$sql = "SELECT fn_atualiza_status_pedido($login_fabrica, $pedido);";
				$res = pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage($con);
			  }

			  if(strlen($msg_erro) == 0){

				$sql2 = "UPDATE tbl_os_item SET
						  servico_realizado = 261
						  WHERE os_item = $os_item
						  AND os_produto = $os_produto
						  AND peca = $peca_carteira
						  AND qtde = $qtde_peca
						  AND fabrica_i = $login_fabrica";

				$res2      = pg_query($con,$sql2);
				$msg_erro .= pg_errormessage($con);

				foreach ($pecaa as $peca) {
				  $sql = "SELECT peca
					FROM tbl_peca
					WHERE referencia = '$peca'
					AND fabrica = $login_fabrica;";
				  $res = pg_query($con, $sql);
				  $msg_erro .= pg_errormessage($con);

				  $peca_troca = pg_fetch_result($res, 0, 'peca');

					$sql3 = "INSERT INTO
							tbl_os_produto (
							  os     ,
							  produto
							)VALUES(
							  $os     ,
							  $produto
							) returning os_produto;";
					$res3      = pg_query($con, $sql3);
					$msg_erro .= pg_errormessage($con);

					$id_os_produto = pg_fetch_result($res3, 0, 'os_produto');

					$sql4 = "INSERT INTO
							tbl_os_item (
							  os_produto            ,
							  peca                  ,
							  qtde                  ,
							  defeito               ,
							  servico_realizado     ,
							  parametros_adicionais
							)VALUES(
							  $id_os_produto        ,
							  $peca_troca           ,
							  1                     ,
							  $defeito              ,
							  $servico              ,
							  '$pa'
						  );";

					$res4      = pg_query($con,$sql4);

					$msg_erro .= pg_errormessage($con);
				}
				$sql = "SELECT fn_valida_os_item($os, $login_fabrica)";
				$res = pg_query($con, $sql);
				$erro = pg_errormessage($con);

				if(strlen($erro) > 0){
				  $recebe = explode('CONTEXT', $erro);
				  $erro = $recebe[0];
				  $erro = explode(':', $erro);
				  $msg_erro.= $erro[1];
				  continue;
				}

			  }

			  $sql = "SELECT nome_completo FROM tbl_admin WHERE admin = $login_admin";
			  $res = pg_query($con, $sql);
			  $msg_erro .= pg_errormessage($con);
			  $nome_completo = pg_fetch_result($res, 0, 'nome_completo');

			  $obs = " A peca ".$ref_peca_carteira." foi trocada pela peca ".implode(",",$pecaa).", admin que realizou a troca: ".$nome_completo;

			  $sql_obs = "UPDATE tbl_os SET
					  obs = obs || '$obs',
					  admin = $login_admin
					  WHERE os = $os
					  AND posto = $posto
					  ";
			  $res_obs      = pg_query($con,$sql_obs);
			  $msg_erro .= pg_errormessage($con);
			}else{
			  $msg_erro.= "A peça $peca_carteira_descricao não esta lançada na Ordem de Serviço $os_consumidor <br />";
			}
  		}
	} #foreach #produto

	  if (strlen($msg_erro) > 0) {
		$arrayRet = array("statuss" => "error","mensagem" => utf8_encode($msg_erro));
		$ret = json_encode($arrayRet);
		echo $ret;

		$sql = "ROLLBACK TRANSACTION";
		$res = pg_query($con, $sql);
		die;
	  }else{
		$sucess = "Troca realizada com sucesso";
		$arrayRet = array("statuss" => "ok","mensagem" => $sucess);
		$ret = json_encode($arrayRet);
		echo $ret;

		$sql = "COMMIT TRANSACTION";
		$res = pg_query($con, $sql);
		die;
	  }
}#post peça
