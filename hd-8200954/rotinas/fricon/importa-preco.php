<?php




/**
 *
 * importa-preco.php
 *
 *
 *
 * @author  Anderson Luciano
 * @version 2013.11.18
 *
 */
error_reporting(E_ALL ^ E_NOTICE);
define('ENV', 'producao');  // producao Alterar para produção ou algo assim

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    $data['login_fabrica'] = 52;
    $data['fabrica'] = 'fricon';
    $data['arquivo_log'] = 'importa-preco';
    $data['tipo'] = 'importa-preco';
    $data['log'] = 2;
    $data['arquivos'] = "/tmp";
    $data['data_sistema'] = Date('Y-m-d');
    $logs = array();
    $logs_erro = array();
    $logs_cliente = array();
    $erro = false;

    if (ENV == 'producao') {        $data['dest'] = 'helpdesk@telecontrol.com.br';
        $data['dest_cliente'] = 'posvendapreco@fricon.com.br';
        $data['dest_cliente'] = 'antoniocarlos@fricon.com.br';
        $data['origem'] = "/home/fricon/fricon-telecontrol/";
        $data['file'] = 'telecontrol-preco.txt';
    } else {
        $data['dest'] = 'anderson.luciano@telecontrol.com.br';
        $data['dest_cliente'] = 'anderson.luciano@telecontrol.com.br';
        $data['origem'] = dirname(__FILE__) . "/entrada/";
        $data['file'] = 'telecontrol-preco.txt';
    }

    extract($data);

    define('APP', 'Importa preco - ' . $login_fabrica_nome);

    $arquivo_err = "{$arquivos}/{$login_fabrica_nome}/{$arquivo_log}-{$data_sistema}.err";
    $arquivo_log = "{$arquivos}/{$login_fabrica_nome}/{$arquivo_log}-{$data_sistema}.log";
    system("mkdir {$arquivos}/{$login_fabrica_nome}/ 2> /dev/null ; chmod 777 {$arquivos}/{$login_fabrica_nome}/");

    if (file_exists($origem . $file)) {
    	# tabela - fricon_preco_sem_peca
    	$sql = "DROP TABLE IF EXISTS fricon_preco_sem_tabela;";
        $res = pg_query($con, $sql);
        $msg_erro .= pg_errormessage($con);


        $sql = "CREATE TABLE fricon_preco_sem_tabela (
				  sigla_tabela           text,
				  referencia    text,
				  txt_preco          text,		
				  preco float,
				  tabela int4,
				  peca int4	,
				  fabrica int
			  )";
        $res = pg_query($con, $sql);
        $msg_erro .= pg_errormessage($con);

        # tabela - fricon_preco_sem_peca
        $sql = "DROP TABLE IF EXISTS fricon_preco_sem_peca;";
        $res = pg_query($con, $sql);
        $msg_erro .= pg_errormessage($con);


        $sql = "CREATE TABLE fricon_preco_sem_peca (
				  sigla_tabela           text,
				  referencia    text,
				  txt_preco          text,		
				  preco float,
				  tabela int4,
				  peca int4	,
				  fabrica int
			  )";
        $res = pg_query($con, $sql);
        $msg_erro .= pg_errormessage($con);


        # tabela - fricon_preco
        $sql = "DROP TABLE IF EXISTS fricon_preco;";
        $res = pg_query($con, $sql);
        $msg_erro .= pg_errormessage($con);


        $sql = "CREATE TABLE fricon_preco (
				  sigla_tabela           text,
				  referencia    text,
				  txt_preco          text,		
				  preco float,
				  tabela int4,
				  peca int4			  		  
			  )";
        $res = pg_query($con, $sql);
        $msg_erro .= pg_errormessage($con);

        $linhas = file_get_contents($origem . $file);
        $linhas = explode("\n", $linhas);

        $erro = $msg_erro;

        foreach ($linhas AS $linha) {
            $msg_erro = "";

            list($txt_sigla_tabela, $txt_referencia, $txt_preco) = explode(";", $linha);
            $txt_preco = str_replace(',', '.', $txt_preco);

            $sql = "SELECT peca,referencia from tbl_peca where referencia = '" . trim($txt_referencia) . "' and fabrica = 52;";

            $res = pg_query($con, $sql);
            if (pg_num_rows($res) > 0) {
                $peca = pg_fetch_result($res, 0, 'peca');

                $sql = "SELECT tabela from tbl_tabela where fabrica = 52 and sigla_tabela = '".$txt_sigla_tabela."';";
                $res = pg_query($con, $sql);
	            if (pg_num_rows($res) > 0) {
	            	$tabela = pg_fetch_result($res, 0, 'tabela');

	            	$res = pg_query($con, "BEGIN");
	                $sql = "INSERT INTO fricon_preco ( 
										sigla_tabela    ,
									    referencia         ,
									    txt_preco,
									    peca,
									    tabela
									  ) VALUES (
										'" . trim($txt_sigla_tabela) . "',
										'" . trim($txt_referencia) . "',
										'" . trim($txt_preco) . "',
										" . trim($peca) . ",
										" . trim($tabela) . "
									  );";
	                $res = pg_query($con, $sql);
	                $msg_erro .= pg_errormessage($con);

	                if (!empty($msg_erro)) {
	                    $res = pg_query($con, "ROLLBACK");
	                    $erro .= $msg_erro;
	                } else {
	                    $res = pg_query($con, "COMMIT");
	                }

	            }else{
	            	$res = pg_query($con, "BEGIN");
	                $sql = "INSERT INTO fricon_preco_sem_tabela ( 
										sigla_tabela    ,
									    referencia         ,
									    txt_preco								    
									  ) VALUES (
										'" . trim($txt_sigla_tabela) . "',
										'" . trim($txt_referencia) . "',
										'" . trim($txt_preco) . "'									
									  );";
	                $res = pg_query($con, $sql);
	                $msg_erro .= pg_errormessage($con);

	                if (!empty($msg_erro)) {
	                    $res = pg_query($con, "ROLLBACK");
	                    $erro .= $msg_erro;
	                } else {
	                    $res = pg_query($con, "COMMIT");
	                }
	            }                
            } else {
                $res = pg_query($con, "BEGIN");
                $sql = "INSERT INTO fricon_preco_sem_peca ( 
									sigla_tabela    ,
								    referencia         ,
								    txt_preco								    
								  ) VALUES (
									'" . trim($txt_sigla_tabela) . "',
									'" . trim($txt_referencia) . "',
									'" . trim($txt_preco) . "'									
								  );";
                $res = pg_query($con, $sql);
                $msg_erro .= pg_errormessage($con);

                if (!empty($msg_erro)) {
                    $res = pg_query($con, "ROLLBACK");
                    $erro .= $msg_erro;
                } else {
                    $res = pg_query($con, "COMMIT");
                }
            }
        }

        $sql = "UPDATE fricon_preco SET
				preco 		= txt_preco::numeric";

        $res = pg_query($con, $sql);
        $msg_erro .= pg_errormessage($con);

        $sql = "UPDATE tbl_tabela_item
                SET   preco = fricon_preco.preco
                FROM  fricon_preco
                WHERE tbl_tabela_item.tabela = fricon_preco.tabela
                AND   tbl_tabela_item.peca   = fricon_preco.peca
                ";
		

        $res = pg_query($con, $sql);
        $msg_erro .= pg_errormessage($con);                
        $msg_erro = $erro;

        $sql = "DELETE FROM fricon_preco 
                    USING tbl_tabela_item
                    WHERE tbl_tabela_item.tabela = fricon_preco.tabela 
                    AND tbl_tabela_item.peca     = fricon_preco.peca
                ";
        

        $res = pg_query($con, $sql);
        $msg_erro .= pg_errormessage($con);                
        $msg_erro = $erro;        

        $sql = "INSERT INTO tbl_tabela_item (tabela, peca, preco) 
                    (SELECT DISTINCT tabela, peca, preco FROM fricon_preco) ";

        $res = pg_query($con, $sql);
        $msg_erro .= pg_errormessage($con);                
        $msg_erro = $erro;        
                            

       	$sql = "SELECT * from fricon_preco_sem_peca;";       	
       	$res = pg_query($con, $sql);
        $msg_erro .= pg_errormessage($con);
        $msg_sempeca = "";
        if(pg_num_rows($res) > 0){
        	for($i = 0; $i < pg_num_rows($res);$i++){
        		$auxsigla = pg_fetch_result($res, $i, 'sigla_tabela');
        		$auxreferencia = pg_fetch_result($res, $i, 'referencia');        		
        		$msg_sempeca .= "Não existe peça de referência <b>".$auxreferencia."</b><br>";        		
        	}
        }

        $sql = "SELECT * from fricon_preco_sem_tabela;";
       	$res = pg_query($con, $sql);
        $msg_erro .= pg_errormessage($con);
        $msg_semtabela = "";
        if(pg_num_rows($res) > 0){
        	for($i = 0; $i < pg_num_rows($res);$i++){
        		$auxsigla = pg_fetch_result($res, $i, 'sigla_tabela');
        		$auxreferencia = pg_fetch_result($res, $i, 'referencia');        		
        		if(trim($sigla_tabela) != ""){
        			$msg_semtabela .= "Tabela descrita pela sigla <b>".$auxsigla."</b> na peça <b>".$auxreferencia."</b> não foi encontrada"."<br>";
        		}else{
        			$msg_semtabela .= "Tabela da peça <b>".$auxreferencia."</b> não foi encontrada"."<br>";
        		}
        		
        	}
        }


        if($msg_semtabela != "" || $msg_sempeca != ""){
        	$msg = "<b>Foram encontradas as seguinte ocorrências no arquivo de importação</b> <br>";
        	$msg .= "  ".$msg_semtabela."  ".$msg_sempeca."<br>";
        	$msg .= "Arquivo processado em ".date('d-m-yyyy h:i:s');
            try{
                Log::envia_email($data, APP, $msg);    
            }catch(Exception $e){
                Log::log2($data, APP . ' - Erro ao enviar email - '.date('Y-m-d-H-i'));
            }
			
            Log::log2($data, utf8_encode(APP . ' - Executado com Algumas falhas - ' . date('Y-m-d-H-i')."\n".$msg_semtabela.$msg_sempeca));
            
        }

        if (!empty($msg_erro)) {
			$msg_erro .= "\n\n".$log_erro;
			$fp = fopen("entrada/preco.err","w");
			fwrite($fp,$msg_erro);
			fclose($fp);
			$msg = 'Script: '.__FILE__.'<br />' . $msg_erro;         
			Log::envia_email($data, APP, $msg);
		} else {
			$fp = fopen("/tmp/fricon/preco.err","w");
			fwrite($fp,$log_erro);
			fclose($fp);

			system("mv $origem$file /tmp/fricon/telecontrol-preco-".date('Y-m-d-H-i').".txt");

			Log::log2($data, APP . ' - Executado com Sucesso - ' . date('Y-m-d-H-i'));
		}
    }
} catch (Exception $e) {
    $e->getMessage();
    $msg = "Arquivo: " . __FILE__ . "\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() . "<hr /><br /><br />" . implode("<br /><br />", $logs);

    Log::envia_email($data, Date('d/m/Y H:i:s') . " - fricon - Importa preco (importa-preco.php)", $msg);
}
?>
