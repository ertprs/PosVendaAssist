<?php
	define('APP','Relatório todas as OSs Mês- Britania'); // Nome da rotina, para ser enviado por e-mail
	define('ENV','producao'); // Alterar para produção ou algo assim

	try {
		include dirname(__FILE__) . '/../../dbconfig.php';
		include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
		require dirname(__FILE__) . '/../funcoes.php';

		$fabrica     	= 3;
		$data           = date('Y-m-d-H');


		$data_inicio = date('Y-m-01');				
		$data_final  = date('Y-m-d');
		if(date("d") == "01"){			
			$data_inicio = date("Y-m-d",strtotime($data_inicio." -1 month"));
			$data_final  = date("Y-m-d",strtotime($data_final." -1 day"));
		}

		$data =  date("Y-m-d-H-i",strtotime($data_final.date('H:i')));

		#echo "data inicio: $data_inicio";
		#echo "data final : $data_final";
		#echo "data       : $data";
		#exit;	

		$phpCron = new PHPCron($fabrica, __FILE__); 
		$phpCron->inicio();

		$vet['fabrica'] = 'britania';
		$vet['tipo']    = 'relatorio_todas_os_mes';
		$vet['dest'][0]    = 'helpdesk@telecontrol.com.br';
                $vet['dest'][1]    = 'marisa.telecontrol@britania.com.br';
		//$vet['dest'][1]    = 'sistemas@britania.com.br';
		//$vet['dest'][2]    = 'airton.garcia@britania.com.br';
		//$vet["dest"][0] = "thiago.tobias@telecontrol.com.br";
		$vet['log']     = 1;
		
		$dir = "/tmp/britania/pedidos";
		if(!is_dir($dir)) {
			system ("mkdir -m 777 $dir 2> /dev/null ; " );
		}

		$file = 'relatorio-todas-os-mes-.xlsx';

		$sql = "SELECT 
						tbl_os.sua_os,
						TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
						TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
						current_date - tbl_os.data_abertura as qtde_dias,
						tbl_os.data_conserto,
						case when tbl_os.consumidor_revenda = 'C' 
							then
								'CONS'
							else
								'REV'
						end AS consumidor_revenda,
						tbl_posto_fabrica.codigo_posto || '-' || tbl_posto.nome AS posto,
						tbl_posto_fabrica.contato_cidade,
						tbl_posto_fabrica.contato_estado,
						case when tbl_os.consumidor_revenda = 'C' 
							then
								tbl_os.consumidor_nome
							else
								tbl_os.revenda_nome
						end AS nome_consumidor_revenda,
						case when tbl_os.consumidor_revenda = 'C' 
							then
								tbl_os.consumidor_fone
							else
								tbl_os.consumidor_revenda
						end AS fone_consumidor_revenda,
						tbl_produto. referencia || '-' || tbl_produto.descricao AS produto,
						tbl_marca.nome as marca_nome,
						tbl_linha.nome as nome_linha,
						tbl_status_checkpoint.descricao as status,
                                                TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY') as data_fechamento
					FROM tbl_os
						JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $fabrica
						JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto and tbl_produto.fabrica_i= $fabrica
						JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha and tbl_linha.fabrica= $fabrica
						LEFT JOIN tbl_marca ON tbl_marca.fabrica= $fabrica AND tbl_marca.marca = tbl_produto.marca
						JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint
					WHERE tbl_os.fabrica = $fabrica
                                                AND tbl_os.data_digitacao >= '$data_inicio 00:00:00' and tbl_os.data_digitacao <= '$data_final 23:59:59'
						and tbl_os.excluida IS NOT TRUE
						AND tbl_os.posto <> 6359
					ORDER BY tbl_os.data_abertura;";

		$result = pg_query($con,$sql);
		
		if (pg_last_error()) {
			$erro_hd = pg_last_error()."<br />";
		} else {

			$fp = fopen( $dir . '/' . $file, "w" );
			$resultado = pg_fetch_all($result);
			$conteudo = "OS;Data Abertura;Data Digitação;Qtde Dias;Data Conserto;Consumidor Revenda;Posto;Contato Cidade;Contato Estado;Nome Consumidor Revenda;Telefone Consumidor Revenda; Produto;Marca; Linha; Status; Fechamento\r\n";
			fputs($fp,$conteudo);

			foreach ( $resultado as $key => $value) {
				$conteudo = $value['sua_os'].";".$value['data_digitacao'].";".$value['data_abertura'].";".$value['qtde_dias'].";".$value['data_conserto'].";".$value['consumidor_revenda'].";".trim($value['posto']).";".trim($value['contato_cidade']).";".$value['contato_estado'].";".$value['nome_consumidor_revenda'].";".$value['fone_consumidor_revenda'].";".trim($value['produto']).";".$value['marca_nome'].";".$value['nome_linha'].";".$value['status'].";".$value['data_fechamento']."\r\n";
				fputs($fp,$conteudo);
			}
			
			fclose ($fp);
		}

		if (file_exists("$dir/$file")) {
			system("mv $dir/$file $dir/relatorio-todas-os-mes-$data.xlsx");
			$ftp_server = "telecontrol.britania.com.br";
			$ftp_user_name = "akacia";
			$ftp_user_pass = "britania2009";

			$local_file = "$dir/relatorio-todas-os-mes-$data.xlsx";
			$server_file = "/Entrada/relatorio-todas-os-mes-$data.xlsx";

			$conn_id = ftp_connect($ftp_server);

			if (is_resource($conn_id)) {
				$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
				ftp_pasv($conn_id, true);

				if (ftp_put($conn_id, $server_file, $local_file, FTP_BINARY))
					#echo "SUBIU?";  

				ftp_close($conn_id);
			} else {
				$erro_ftp = $conn_id;
			}
		}
		if (!empty($erro_hd)){
			$erro_hd = str_replace("ERROR: ", "", $erro_hd);
			unset($vet["dest"]);
			$vet["dest"] = "helpdesk@telecontrol.com.br";
			//$vet["dest"] = "thiago.tobias@telecontrol.com.br";
			$erro = "Erro na Rotina Gera Relatorio de OSs Abertas<br /> $erro_hd";
			Log::envia_email($vet,APP, $erro, true, "erro");
		}

		if (!empty($erro_ftp)) {
			unset($vet["dest"]);
			$vet["dest"] = "helpdesk@telecontrol.com.br";
			//$vet["dest"] = "thiago.tobias@telecontrol.com.br";
			$erro = "Erro ao Conectar no FTP, Rotina Gera Relatorio de OSs Abertas<br /> $erro_hd";
			Log::envia_email($vet,APP, $erro, true, "erro");
		}
		
		$phpCron->termino();
				
	}
	catch (Exception $e) {
	
		$msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
		Log::envia_email($vet,APP, $msg );

	}
