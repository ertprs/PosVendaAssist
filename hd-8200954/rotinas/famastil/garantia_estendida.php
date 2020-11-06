<?php

try {
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';

	$fabrica = 86;

	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();

	$vet['fabrica'] = 'famastil';
	$vet['tipo']    = 'importa_csv';
	$vet['dest']    = 'helpdesk@telecontrol.com.br';
	//$vet['dest']    = 'guilherme.curcio@telecontrol.com.br';
	$vet['log']     = 1;

	$file = "/home/famastil/famastil-telecontrol/garantia_adicional.txt";

	if (file_exists($file)) {
		$file_content = file_get_contents($file);
		$lines        = explode("\n", $file_content);

		$i = 1;

		foreach ($lines as $line) {
			list(
                    $produto        ,
                    $serie          ,
                    $meses          ,
                    $nome_completo  ,
                    $cpf            ,
                    $data_compra    ,
                    $razao_social   ,
                    $cnpj           ,
                    $estado         ,
                    $cidade         ,
                    $email          ,
                    $telefone
                ) = explode(";", $line);

			$produto        = strtoupper(trim($produto));
			$serie          = trim($serie);
			$meses          = (int) trim($meses);
            $nome_completo  = strtoupper(trim($nome_completo));
            $cpf            = trim($cpf);
            $data           = explode("/",$data_compra);
            $data_compra    = $data[2]."-".$data[1]."-".$data[0];
            $razao_social   = strtoupper(trim($razao_social));
            $cnpj           = trim($cnpj);
            $estado         = strtoupper(trim($estado));
            $cidade         = strtoupper(trim($cidade));
            $email          = trim($email);
            $telefone       = trim($telefone);

            if(strlen($nome_completo) > 0){
                $nome = $nome_completo;
            }else{
                $nome = $razao_social;
            }

            if(strlen($cpf) > 0){
                $doc = preg_replace("/[^0-9]/","",$cpf);
            }else{
                $doc = preg_replace("/[^0-9]/","",$cnpj);
            }

			if (!empty($produto) && !empty($serie) && !empty($meses)) {
				$sql = "SELECT produto
						FROM tbl_produto
						WHERE fabrica_i = {$fabrica} AND UPPER(referencia) = '{$produto}'";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					$produto = pg_fetch_result($res, 0, "produto");

					$sql = "INSERT INTO tbl_cliente_garantia_estendida  (
                                                                            produto             ,
                                                                            numero_serie        ,
                                                                            garantia_mes        ,
                                                                            fabrica             ,
                                                                            nome                ,
                                                                            cpf                 ,
                                                                            email               ,
                                                                            fone                ,
                                                                            endereco            ,
                                                                            numero              ,
                                                                            cep                 ,
                                                                            cidade              ,
                                                                            revenda_nome        ,
                                                                            nota_fiscal         ,
                                                                            data_compra         ,
                                                                            estado
                                                                        )VALUES(
                                                                            $produto            ,
                                                                            '$serie'            ,
                                                                            $meses              ,
                                                                            $fabrica            ,
                                                                            '$nome'             ,
                                                                            $doc                ,
                                                                            '$email'            ,
                                                                            '$telefone'         ,
                                                                            'rua'               ,
                                                                            'n'                 ,
                                                                            'cep'               ,
                                                                            '$cidade'           ,
                                                                            'Famastil'          ,
                                                                            'nf'                ,
                                                                            '$data_compra'      ,
                                                                            '$estado'
                                                                        )
                    ";
					$res = pg_query($con, $sql);
					echo pg_last_error();

					if (pg_last_error()) {
						$msg_erro[] = "Erro ao gravar a linha {$i}. Erro: ".pg_last_error();
					}
				} else {
					$msg_erro[] = "Erro na linha {$i} produto: {$produto} não encontrado";
				}
			} else {
				$msg_erro[] = "Erro de layout na linha {$i}";
			}

			$i++;
		}

		if (count($msg_erro) > 0) {
			$msg_erro = implode("\n", $msg_erro);
			Log::envia_email($vet, "Importa Garantia Estendida - Famastil", $msg_erro);
		}

		$data = date('Y-m-d-H');
		system("mv /home/famastil/famastil-telecontrol/garantia_adicional.txt /tmp/famastil/garantia_adicional_{$data}.txt");
	}

	$phpCron->termino();
} catch (Exception $e) {
	$msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
	Log::envia_email($vet, "Importa Garantia Estendida - Erro ao rodar - Famastil", $msg);

	$phpCron->termino();
}

exit;
