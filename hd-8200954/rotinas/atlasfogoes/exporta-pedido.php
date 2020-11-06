<?php
error_reporting(E_ALL ^ E_NOTICE);

$fabrica_nome = "atlas";

define('APP', 'Exporta Pedido - '.$fabrica_nome);

try {
  include dirname(__FILE__) . '/../../dbconfig.php';
  include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
  require_once dirname(__FILE__) . '/../funcoes.php';
  require_once dirname(__FILE__) . '/../../class/communicator.class.php';

  $mail = new TcComm("smtp@posvenda");


  $login_fabrica = 74;
  $vet['fabrica'] = 'atlas';
  $vet['tipo']    = 'exporta-pedido';
  $vet['dest']    = array('helpdesk@telecontrol.com.br');
  // $vet['dest']    = array('rafael.macedo@telecontrol.com.br');
  $vet['log']     = 1;

// pg_query($con,"BEGIN");

  $sql = "SELECT fn_atualiza_status_pedido(fabrica,pedido) FROM tbl_pedido 
		WHERE fabrica = $login_fabrica 
			AND tbl_pedido.recebido_fabrica IS NULL 
			AND tbl_pedido.posto <> 6359
			AND tbl_pedido.finalizado IS NOT NULL
      AND tbl_pedido.finalizado > '2016-07-19'
			AND tbl_pedido.exportado isnull
			AND tbl_pedido.status_pedido NOT IN(14,18);

		SELECT pedido FROM tbl_pedido 
		WHERE fabrica = $login_fabrica 
			AND tbl_pedido.recebido_fabrica IS NULL 
			AND tbl_pedido.posto <> 6359
			AND tbl_pedido.finalizado IS NOT NULL
      AND tbl_pedido.finalizado > '2016-07-19'
			AND tbl_pedido.exportado isnull
			AND tbl_pedido.status_pedido NOT IN(14,18);";
	$result = pg_query($sql);

	if (strlen(pg_last_error()) > 0) {
		$msg_erro = "Erro ao fazer busca na tabela tbl_pedido (encontrar pedido)";
		$msg_erro .= " ".pg_last_error($con);
        throw new Exception($msg_erro);
	}
 
	if(pg_num_rows($result) > 0){

		$dir = "/tmp/$fabrica_nome/pedidos";    

        if (!is_dir($dir)) {
          if (!mkdir($dir)) {
            throw new Exception('Erro ao criar diretório do fabricante.'."\n");
          }
          if (!chmod($dir, 0777)) {
            throw new Exception('Erro ao dar permissão ao diretório.'."\n");
          }
        }

        $file_pedido      = $dir.'/pedido.txt';
        $file_pedido_item = $dir.'/pedido-item.txt';
        $fp               = fopen($file_pedido, 'w');
        $fi               = fopen($file_pedido_item,'w');


    while($objeto_pedido = pg_fetch_object($result)){
      $pedido = $objeto_pedido->pedido;

      $sql = "SELECT 
          to_char (tbl_pedido.data,'DD/MM/YYYY') AS emissao,
          tbl_posto.cnpj as cnpj,
          RPAD (tbl_posto_fabrica.codigo_posto,8,' ') AS codigo_posto,
          tbl_pedido.pedido,
          UPPER (tbl_tipo_pedido.codigo) AS tipo_pedido,
          tbl_tabela.sigla_tabela,
          tbl_condicao.codigo_condicao AS condicao,
          tbl_pedido.origem_cliente,
          tbl_hd_chamado_extra.cpf as consumidor_cpf
        FROM tbl_pedido
          JOIN tbl_posto_fabrica ON tbl_pedido.posto = tbl_posto_fabrica.posto 
            AND tbl_posto_fabrica.fabrica = $login_fabrica
          JOIN tbl_posto         ON tbl_posto.posto = tbl_pedido.posto
          JOIN tbl_tipo_pedido USING (tipo_pedido)
          JOIN tbl_tabela        ON tbl_pedido.tabela = tbl_tabela.tabela
          JOIN tbl_condicao      ON tbl_pedido.condicao = tbl_condicao.condicao
          LEFT JOIN tbl_hd_chamado_extra On tbl_hd_chamado_extra.pedido = tbl_pedido.pedido
        WHERE tbl_pedido.pedido = $pedido";
      $res      = pg_query($con, $sql);

      $numrows  = pg_num_rows($res);
      $msg_erro = pg_errormessage($con);
      $data     = date('Y-m-d');

      if (!empty($msg_erro)) {
          throw new Exception($msg_erro);
      }

      if ($numrows) {


        if (!is_resource($fp)) {
          throw new Exception('Erro ao criar arquivo de exportação.'."\n");
        }

        for ($i = 0; $i < $numrows; $i++) {
          $emissao     = pg_fetch_result($res, $i, 'emissao');
          $cnpj        = pg_fetch_result($res, $i, 'cnpj');
          $tipo_pedido = pg_fetch_result($res, $i, 'tipo_pedido');
          $condicao    = pg_fetch_result($res, $i, 'condicao');
          $origem_cliente = pg_fetch_result($res, $i, 'origem_cliente');

          if($origem_cliente == 't'){
              $cnpj = pg_fetch_result($res, $i, 'consumidor_cpf');
              $tipo_pedido = "CLI";

              $join_hd_chamado_extra = " JOIN tbl_hd_chamado_extra on tbl_pedido.pedido = tbl_hd_chamado_extra.pedido ";
              $campo_cpf = ", tbl_hd_chamado_extra.cpf as consumidor_cpf";
          }else{
            $join_hd_chamado_extra = "";
            $campo_cpf = ", tbl_os.consumidor_cpf  ";
          }
          /**
           *
           *  Layout:
           *
           *  Emissao | CNPJ | Tipo Pedido | Pedido | Condicao | Tipo Frete
           *
          */
          fwrite($fp, $emissao);
          fwrite($fp, "\t");
          fwrite($fp, $cnpj);
          fwrite($fp, "\t");
          fwrite($fp, $tipo_pedido);
          fwrite($fp, "\t");
          fwrite($fp, $pedido);
          fwrite($fp, "\t");
          fwrite($fp, $condicao);
          fwrite($fp, "\r\n");

          $sql_pecas = "SELECT 
              tbl_pedido_item.pedido_item,
              tbl_pedido.pedido,
              tbl_peca.referencia AS peca_referencia,
              LPAD ((tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada)::text,6,'0') AS qtde,
              LPAD (TRIM (TO_CHAR (tbl_pedido_item.preco,'999999.99')),9,'0') AS preco, 
              tbl_peca.origem,
              tbl_os_produto.os
              $campo_cpf
            FROM tbl_pedido
              JOIN tbl_pedido_item USING (pedido)
              JOIN tbl_peca        USING (peca)
              $join_hd_chamado_extra
              LEFT JOIN tbl_os_item ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
              LEFT JOIN tbl_os_produto USING(os_produto)
              LEFT JOIN tbl_os ON tbl_os.os = tbl_os_produto.os
            WHERE tbl_pedido.pedido = $pedido
              AND   tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada > 0";
          $res_pecas = pg_query($con, $sql_pecas);
          $tot_pecas = pg_num_rows($res_pecas);
          $msg_erro  = pg_errormessage($con);

          if (!empty($msg_erro)) {
            throw new Exception($msg_erro);
          }

          for ($x = 0; $x < $tot_pecas; $x++) {
            $peca_referencia = trim(pg_fetch_result($res_pecas, $x, 'peca_referencia'));
            $pedido_item     = trim(pg_fetch_result($res_pecas, $x, 'pedido_item'));
            $qtde            = trim(pg_fetch_result($res_pecas, $x, 'qtde'));
            $preco           = trim(pg_fetch_result($res_pecas, $x, 'preco'));
            $consumidor_cpf  = trim(pg_fetch_result($res_pecas, $x, 'consumidor_cpf'));

            if (empty($os_item)) {
              $os_item = "000000000";
            }

            if (empty($os)) {
              $os = "000000000";
            }

            fwrite($fi, $peca_referencia);
            fwrite($fi, "\t");
            fwrite($fi, $qtde);
            fwrite($fi, "\t");
            fwrite($fi, $pedido);
            fwrite($fi, "\t");
            fwrite($fi, $pedido_item);
            fwrite($fi, "\t");
            fwrite($fi, $preco);
            fwrite($fi, "\t");
            fwrite($fi, $consumidor_cpf);
            fwrite($fi, "\r\n");

            $sql_up = "UPDATE tbl_pedido SET 
                exportado     = CURRENT_TIMESTAMP,
                status_pedido = 2
              WHERE pedido    = $pedido
                AND fabrica   = $login_fabrica
                AND exportado IS NULL ";
            $res_up   = pg_query($con, $sql_up);
            $msg_erro = pg_errormessage($con);

            if (!empty($msg_erro)) {
              throw new Exception($msg_erro);
            }
          }
        }

        if (!empty($msg_erro)) {
          $msg = 'Script: '.__FILE__.'<br />' . $msg_erro;
          $mail->sendMail($vet["dest"], $vet["tipo"], $msg, "rafael.macedo@telecontrol.com.br");

        } else {
          $mail->sendMail($vet["dest"], $vet["tipo"], APP . ' - Executado com Sucesso - ' . date('d-m-Y H:i:s'), "rafael.macedo@telecontrol.com.br");
        }


      // var_dump(pg_fetch_array($result));
      // pg_query($con,"ROLLBACK");
      // exit;
	  }
      }

		fclose($fp);
		fclose($fi);

		if (file_exists($file_pedido) and (filesize($file_pedido) > 0)) {
			date_default_timezone_set('America/Sao_Paulo');
			$data_arquivo = date('Y-m-d-H-i');

			$destino = "/home/atlas/telecontrol-atlas/pedido-$data_arquivo.txt";
			$destino2 = "/home/atlas/telecontrol-atlas/pedido_item-$data_arquivo.txt";

			copy($file_pedido, $dir . '/pedido-' . $data_arquivo . '.txt');
			system("mv $file_pedido $destino");
			copy($file_pedido_item, $dir . '/pedido_item-' . $data_arquivo . '.txt');
			system("mv $file_pedido_item $destino2");
		}

  }
} catch (Exception $e) {
  $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
  Log::envia_email($vet["dest"], $vet["tipo"], $msg, "rafael.macedo@telecontrol.com.br");
}
?>
