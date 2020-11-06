<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include_once __DIR__ . '/../class/AuditorLog.php';
require_once __DIR__ . '/../class/ComunicatorMirror.php';

# Pesquisa pelo AutoComplete AJAX
$q = trim($_GET["q"]);
if (isset($_GET["q"])){
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];
	if (strlen($q)>2){
		if ($tipo_busca=="produto"){
			$sql = "SELECT tbl_peca.peca,
							tbl_peca.referencia,
							tbl_peca.descricao
					FROM tbl_peca
					WHERE tbl_peca.fabrica = $login_fabrica ";

			if ($busca == "codigo"){
				$sql .= " AND UPPER(tbl_peca.referencia) like UPPER('%$q%') ";
			}else{
				$sql .= " AND UPPER(tbl_peca.descricao) like UPPER('%$q%') ";
			}

			$res = pg_query($con,$sql);
			if (pg_num_rows ($res) > 0) {
				for ($i=0; $i<pg_num_rows ($res); $i++ ){
					$peca       = trim(pg_fetch_result($res,$i,peca));
					$referencia = trim(pg_fetch_result($res,$i,referencia));
					$descricao  = trim(pg_fetch_result($res,$i,descricao));
					echo "$peca|$descricao|$referencia";
					echo "\n";
				}
			}
		}
	}
	exit;
}

$ordem = $_GET['ordena'];
if(!$ordem)
	$ordem = "tbl_depara.de;";


		/*HD 15873 18/3/2008*/

if (strlen($_GET["depara"]) > 0) {
	$depara = trim($_GET["depara"]);
}

if (strlen($_POST["depara"]) > 0) {
	$depara = trim($_POST["depara"]);
}

if (strlen($_POST["btnacao"]) > 0) {
	$btnacao = trim($_POST["btnacao"]);
}

if ($btnacao == "deletar" and strlen($depara) > 0) {
	$objLog = new AuditorLog();
	$objLog->retornaDadosTabela('tbl_depara', array('depara'=>$depara, 'fabrica'=>$login_fabrica) );

	$res = pg_query ($con,"BEGIN TRANSACTION");

	$sql = "DELETE FROM tbl_depara
			WHERE  tbl_depara.depara  = $depara
			AND    tbl_depara.fabrica = $login_fabrica;";
	$res = @pg_query ($con,$sql);
	$msg_erro = pg_errormessage($con);

	if (strlen ($msg_erro) == 0) {
		
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		$msg = traduz("Excluido com Sucesso!");

		$objLog->retornaDadosTabela()->enviarLog('update', "tbl_depara", $login_fabrica."*".$depara);

		header ("Location: $PHP_SELF?msg=$msg");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		$referencia_de   = $_POST["referencia_de"];
		$descricao_de    = $_POST["descricao_de"];
		$referencia_para = $_POST["referencia_para"];
		$descricao_para  = $_POST["descricao_para"];
		$expira          = $_POST["expira"];
		$digitacao       = $_POST["digitacao"];

		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
}


function existeDePara($de, $para) {

	global $login_fabrica, $con;

	$sqlDepara = "SELECT depara, peca_para 
		  	  	  FROM tbl_depara 
		  	  	  WHERE fabrica = {$login_fabrica}
		  	  	  AND de   = '{$de}'
		  	  	  AND para = '{$para}'";

	$res = pg_query($con, $sqlDepara);

	$selectRes = pg_num_rows($res);

	$info_depara = [];

	if ($selectRes > 0) {
		
		$info_depara['depara']    = pg_fetch_result($res, 0, 'depara');
		$info_depara['peca_para'] = pg_fetch_result($res, 0, 'peca_para');
	}

	return $info_depara;
}

function verificaDePara($de, $para) {

	global $login_fabrica, $con;
	
	$validacaoDe = "SELECT peca 
					FROM tbl_peca 
					WHERE referencia = '{$de}'
				    AND fabrica = {$login_fabrica}";
	
	$resValidacaoDe = pg_query($con, $validacaoDe);

	$pecaDe = pg_fetch_result($resValidacaoDe, 0, 'peca');

	$validacaoPara = "SELECT peca 
				  	  FROM tbl_peca 
				  	  WHERE referencia = '{$para}'
				  	  AND fabrica = {$login_fabrica}";

	$resValidacaoPara = pg_query($con, $validacaoPara);

	$pecaPara = pg_fetch_result($resValidacaoPara, 0, 'peca');

	if (strlen($pecaDe) == 0 || strlen($pecaPara) == 0) {
		
		return False;
	} 

	$pecas = ["de" => $pecaDe, "para" => $pecaPara];

	return $pecas;
}

function excluirDePara($de, $para) {

	global $login_fabrica, $con;

	$sql = "SELECT depara, peca_para 
	  	  	FROM tbl_depara 
	  	  	WHERE fabrica = {$login_fabrica}
	  	  	AND de   = '{$de}'
	  	  	AND para = '{$para}'";

	$res = pg_query($con, $sql);

	if (strlen(pg_result_error($res)) > 0) {

		return False;
	}

	$depara = pg_fetch_result($res, 0, 'depara');

	$query = "DELETE FROM tbl_depara WHERE depara = {$depara}";

	$res = pg_query($con, $query);

	if (strlen(pg_result_error($res)) > 0) {

		return False;
	}

	return True;
}

function addLog($logReport, $linha, $deArquivo, $paraArquivo, $status) {

	$msg['sucesso'] = ' importado com sucesso';
	$msg['erro']    = ' com falha na importação';
	$msg['excluir_sucesso'] = ' excluído com sucesso';
	$msg['excluir_erro']    = ' erro ao excluir registro';
	$msg['nao_encontrado']  = ' não encontrado no sistema';
	$msg['data_invalida']   = ' DE -> PARA com data vencida';

	$logReport[] = 'Linha ' . $linha . ' De: ' . $deArquivo . ' Para: ' . $paraArquivo . $msg[$status] . PHP_EOL;

	return $logReport;
}

function criarDePara($deArquivo, $paraArquivo, $pecas, $dataExpArquivo) {

	global $login_fabrica, $con;

	$expira = "";
	$expiraVal = "";

	if (strlen($dataExpArquivo) > 0) {

		$expira = ", expira";
		$expiraVal = ", '{$dataExpArquivo}'";
	}

	$depara = existeDePara($paraArquivo, $deArquivo);

	$excluiu = True;
	$retorno = [];

	if (count($depara) > 0) {

		$excluiu = excluirDePara($paraArquivo, $deArquivo);

		if ($excluiu) {

			$retorno['delete'] = True;
		}
	}

	if ($excluiu) { 

		$query = "INSERT INTO tbl_depara 
				  (fabrica, de, para, peca_de, peca_para {$expira}) 
				  VALUES 
				  ($login_fabrica, '{$deArquivo}', '{$paraArquivo}', {$pecas["de"]}, {$pecas["para"]} {$expiraVal})";
		
		$res = pg_query($con, $query);

		if (pg_result_error($res) > 0) {

			return False;
		}

		$retorno['insert'] = True;
		
		return $retorno;
	}

	return False;
}

function atualizarDePara($deArquivo, $paraArquivo, $peca, $dataExpArquivo, $depara) {

	global $login_fabrica, $con;

	$addData = "";

	if (strlen($dataExpArquivo) > 0) {

		$addData = ", expira = '{$dataExpArquivo}' ";
	}

	$query = "UPDATE tbl_depara 
	  	      SET de   = '{$deArquivo}'
	      	  	  , para = '{$paraArquivo}'
	      	      , peca_para = {$peca}
	      	      {$addData}
	  	      WHERE depara  = {$depara}";

	$res = pg_query($con, $query);

	if (pg_result_error($res) > 0) {

		return False;
	}

	return True;
}

if (isset($_POST['upload_excel'])) {

	$arquivo = $_FILES['arquivo_excel']['tmp_name'];

	$handle = fopen($arquivo, "r");

	$logReport = "";

	$tipoArquivo = explode('.', $_FILES['arquivo_excel']['name']);

	if (in_array($tipoArquivo[1], ['csv','txt'])) {

		$auditorLog = new AuditorLog();

		for ($i = 0; $linha = fgetcsv($handle); $i++) {
			
			$linha = explode(';', $linha[0]);
			$deArquivo   = $linha[0];
			$paraArquivo = $linha[1];

			if (strlen($linha[2]) > 0) {
				
				$dataExpArquivo = $linha[2];

				$dataExpArquivo = explode('/', $dataExpArquivo);
				
				$ano = $dataExpArquivo[2];
				
				if (strlen($ano) == 2) {

					$ano = '20' . $dataExpArquivo[2];
				}

				$dataExpArquivo = $ano . '-' . $dataExpArquivo[1] . '-' . $dataExpArquivo[0];

				$dataAtual = date('Y-m-d');

				$fim = strtotime($dataExpArquivo) - strtotime($dataAtual);

				if ($fim < 0) {

					$logReport = addLog($logReport, $i, $deArquivo, $paraArquivo, 'data_invalida');

					pg_query($con,'COMMIT');

					continue;
				}
			}

		 	$sqlAuditor = "SELECT DISTINCT * 
	                       FROM    tbl_depara
	                       WHERE   fabrica = {$login_fabrica}
	                       AND     de = '{$deArquivo}'
	                       AND 	   para = '{$paraArquivo}'";
	            
	    	$auditorLog->RetornaDadosSelect($sqlAuditor);

			pg_query($con,'BEGIN');
			
			if ($linha[3] == "excluir") {

				$excluirDePara = excluirDePara($deArquivo, $paraArquivo);

				if ($excluirDePara) {
					
					$status = 'excluir_sucesso';

					pg_query($con,'COMMIT');

					$auditorLog->RetornaDadosSelect($sqlAuditor)->enviarLog('delete', 'tbl_depara', $login_fabrica);

				} else {

					$status = 'excluir_erro';
					pg_query($con,'ROLLBACK');
				}

				$logReport = addLog($logReport, $i, $deArquivo, $paraArquivo, $status);

				continue;	
			} 

			$pecas = verificaDePara($deArquivo, $paraArquivo);

			if ($pecas) {

				$selectRes = existeDePara($deArquivo, $paraArquivo);

				if (count($selectRes) > 0) {
					
					$res = atualizarDePara($deArquivo, $paraArquivo, $selectRes['peca_para'], $dataExpArquivo, $selectRes['depara']);

					if ($res) {

						pg_query($con,'COMMIT');
						
						$logReport = addLog($logReport, $i, $deArquivo, $paraArquivo, 'sucesso');

						$auditorLog->RetornaDadosSelect($sqlAuditor)->enviarLog('update', 'tbl_depara', $login_fabrica);
		
					} else {

						pg_query($con,'ROLLBACK');

						$logReport = addLog($logReport, $i, $deArquivo, $paraArquivo, 'erro');
					}
				
				} else {

					$res = criarDePara($deArquivo, $paraArquivo, $pecas, $dataExpArquivo);

					if (count($res) > 0) {

						pg_query($con,'COMMIT');

						$logReport = addLog($logReport, $i, $deArquivo, $paraArquivo, 'sucesso');

						if ($res['delete']) {

							$auditorLog->RetornaDadosSelect($sqlAuditor)->enviarLog('delete', 'tbl_depara', $login_fabrica);
						}

						if ($res['insert']) {
							$auditorLog->RetornaDadosSelect($sqlAuditor)->enviarLog('insert', 'tbl_depara', $login_fabrica);
						}

					} else {

						pg_query($con,'ROLLBACK');

						$logReport = addLog($logReport, $i, $deArquivo, $paraArquivo, 'erro');
					}
				} 

			} else { 

				pg_query($con,'ROLLBACK');

				$logReport = addLog($logReport, $i, $deArquivo, $paraArquivo, 'nao_encontrado');
			}
		}

		fclose($arquivo);

		$stringLog = '';
		
		$arquivoLog = '../tmpFiles/britania_depara_em_massa_log.txt';

		$arquivoLog = fopen($arquivoLog, 'w');


		$stringLog = "";

		foreach ($logReport as $linha) {

			$stringLog .= $linha . '<br>';
			fwrite($arquivoLog, $linha);
		}

		fclose($arquivoLog);

		#enviar email
/*		try {

			$titulo = "Upload de DE >> PARA Britania " . date("d/m/Y");
			$email  = "thiago@telecontrol.com.br";

			$comunicatorMirror = new ComunicatorMirror();
			$comunicatorMirror->post($email, utf8_encode($titulo), utf8_encode($stringLog), "smtp@posvenda");

		} catch (Exception $e) {

			$msg_erro = $e->getMessage();
		}*/

		$msg = "Importação realizada com Sucesso!";

		$_GET['msg'] = $msg;

	} else {

		$msg_erro = "Erro ao Importar: Verifique o tipo do arquivo utilizado";
	}
}

if ($btnacao == "gravar") {
	$depara = $_REQUEST['depara'];
	if (strlen($_POST["referencia_de"]) > 0) {
		$aux_referencia_de = "'". trim($_POST["referencia_de"]) ."'";
	}else{
		$msg_erro = traduz("Favor informar a referência da peça 'DE'.");
	}

	if (strlen($msg_erro) == 0) {
		$sql = "SELECT *
				FROM   tbl_peca
				WHERE  upper(trim(tbl_peca.referencia)) = upper(trim($aux_referencia_de))
				AND    tbl_peca.fabrica = $login_fabrica;";
		$res = @pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (strlen($msg_erro) == 0) {
			if (pg_numrows($res) == 0) $msg_erro = traduz("Peça 'DE' informada não encontrada.");
			else                       $peca_de  = pg_result($res,0,peca);
		}
	}

	if (strlen($msg_erro) == 0) {
		if (strlen($_POST["referencia_para"]) > 0) {
			$aux_referencia_para = "'". trim($_POST["referencia_para"]) ."'";
		}else{
			$msg_erro = traduz("Favor informar a referência da peça 'PARA'.");
		}

		if (strlen($msg_erro) == 0) {
			$sql = "SELECT *
					FROM   tbl_peca
					WHERE  upper(trim(tbl_peca.referencia)) = upper(trim($aux_referencia_para))
					AND    tbl_peca.fabrica = $login_fabrica;";
			$res = @pg_query ($con,$sql);
			$msg_erro = pg_errormessage($con);

			if (strlen($msg_erro) == 0) {
				if (pg_numrows($res) == 0) $msg_erro = traduz("Peça 'PARA' informada não encontrada.");
				else                       $peca_para = pg_result($res,0,peca);
			}
		}
	}

	if(empty($msg_erro)) {
		if($peca_de == $peca_para) {
			$msg_erro = traduz("A Peça 'PARA' não pode ser igual a Peça 'DE'");
		}

		$sql = " SELECT peca_de
				FROM tbl_depara
				WHERE peca_para = $peca_de
				AND   peca_de = $peca_para";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			$msg_erro = traduz("JÁ TEM UM DE-PARA INVERTIDA CADASTRADA");
		}

        if($login_fabrica != 74){
            $sql = "SELECT  depara, case when expira < current_date then 't' else 'f' end as expira
                    FROM    tbl_depara
                    WHERE   peca_de = $peca_de";
            $res = pg_query($con,$sql);

            if(pg_num_rows($res) > 0 and empty($depara)){
				$expira = pg_fetch_result($res,0,'expira');
				if($expira == 't') {
					$msg_erro = "JÁ TEM UM DE-PARA CADASTRADA PARA ESSA PEÇA ".$_POST["referencia_de"] ." e está expirada, favor alterar a data de expiração ou deixar em branco";
				}else{
					$msg_erro = "JÁ TEM UM DE-PARA CADASTRADA PARA ESSA PEÇA ".$_POST["referencia_de"];
				}
            }
		}
	}

	$expira = trim($_POST["expira"]);
	if (strlen($expira)==0){
		$expira = " NULL ";
	}else{
		$dat = explode ("/", $expira );//tira a barra
		$d = $dat[0];
		$m = $dat[1];
		$y = $dat[2];
		if(!checkdate($m,$d,$y)) $msg_erro .= traduz("Data Inválida");
		if (strlen($msg_erro) == 0) {
			$aux = formata_data($expira);
			$expira_aux = $aux;
			$expira = "'".$aux."'";
		}
	}

	if($login_fabrica == 74){
        $apartir = trim($_POST['apartir']);
        if(strlen($apartir) == 0){
            $apartir = "NULL";
        }else{
            $dat = explode ("/", $apartir );//tira a barra
            $d = $dat[0];
            $m = $dat[1];
            $y = $dat[2];
            if(!checkdate($m,$d,$y)) $msg_erro .= traduz("Data Inválida");
            if (strlen($msg_erro) == 0) {
                $aux = formata_data($apartir);
                if(strtotime($aux) < strtotime($expira_aux)){
                    $msg_erro .= traduz("Data da peça PARA não pode ser menor que data da peça DE");
                }else{
                    $apartir = "'".$aux."'";
                }
            }
        }
	}else{
        $apartir = "NULL";
	}

	if($login_fabrica != 74){
        if (strlen($msg_erro) == 0 && $aux) {
            $dt_hoje = date("Y-m-d");
            if($aux < $dt_hoje)
                $msg_erro = traduz("Data Informada Menor que Data Atual.");
        }
	}

	if (strlen($msg_erro) == 0) {
		if (strlen($depara) == 0) {
			$auditorLog = new AuditorLog('insert');
			$tpAuditor = "insert";
		} else {
			$auditorLog = new AuditorLog();
			$auditorLog->retornaDadosTabela('tbl_depara', array('depara'=>$depara, 'fabrica'=>$login_fabrica) );
			$tpAuditor = "update";
		}		

		$res = pg_query ($con,"BEGIN TRANSACTION");

		if (strlen ($msg_erro) == 0 && $login_fabrica == 42) {
			$sql = "SELECT fn_depara_lbm($aux_referencia_de,$aux_referencia_para,$login_fabrica);";
			$res = @pg_query ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if (strlen($depara) == 0) {

			###INSERE NOVO REGISTRO
			$sql = "INSERT INTO tbl_depara (
						fabrica     ,
						de          ,
						para        ,
						expira      ,
						data_inicio
					) VALUES (
						$login_fabrica      ,
						$aux_referencia_de  ,
						$aux_referencia_para,
						$expira             ,
						$apartir
					) RETURNING depara;";
			$msg = "Gravado com Sucesso!";

			$res = pg_query ($con,$sql);
			$depara = pg_fetch_result($res, 0, "depara");
			$msg_erro = pg_errormessage($con);

		}else{

			###ALTERA REGISTRO
			$sql = "UPDATE  tbl_depara
                    SET     de          = $aux_referencia_de  ,
							para        = $aux_referencia_para,
							expira      = $expira             ,
							data_inicio = $apartir
					WHERE   tbl_depara.depara = $depara
					AND     tbl_depara.fabrica = $login_fabrica;";
			$msg = "Gravado com Sucesso!";

			$res = pg_query ($con,$sql);		
			$msg_erro = pg_errormessage($con);
		}
	}

	if (strlen ($msg_erro) == 0 && $login_fabrica != 42) {
		$sql = "SELECT fn_depara_lbm($aux_referencia_de,$aux_referencia_para,$login_fabrica);";
		$res = @pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0 and $login_fabrica == 1) {

		$sql = "SELECT posto, qtde
			FROM  tbl_estoque_posto
			WHERE fabrica = $login_fabrica
			AND peca = $peca_de";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){

			for($i = 0; $i < pg_num_rows($res); $i++){
				$posto_aux = pg_fetch_result($res,$i,posto);
				$qtde_aux  = pg_fetch_result($res,$i,qtde);

				if($qtde_aux > 0){
					$sqlS = "SELECT peca FROM tbl_estoque_posto
						 WHERE fabrica = $login_fabrica
						 AND posto = $posto_aux
						 AND peca = $peca_para";
					$resS = pg_query($con,$sqlS);

					if(pg_num_rows($resS) > 0){
						$sqlU = "UPDATE tbl_estoque_posto SET qtde = qtde + $qtde_aux
							 WHERE fabrica = $login_fabrica
							 AND posto = $posto_aux
							 AND peca = $peca_para";
					} else {
						$sqlU = "INSERT INTO tbl_estoque_posto(
											fabrica,
											posto,
											peca,
											qtde
											) VALUES (
											$login_fabrica,
											$posto_aux,
											$peca_para,
											$qtde_aux
											)";
					}
					$resU = pg_query($con,$sqlU);

					$sqlI = "INSERT INTO tbl_estoque_posto_movimento(
											fabrica,
											posto,
											peca,
											data,
											qtde_entrada,
											admin,
											obs
											) VALUES (
											$login_fabrica,
											$posto_aux,
											$peca_para,
											current_date,
											$qtde_aux,
											$login_admin,
											'Saldo transferido do item $referencia_de'
											)";
					$resI = pg_query($con,$sqlI);

					$sqlP = "UPDATE tbl_estoque_posto SET qtde = 0
							 WHERE fabrica = $login_fabrica
							 AND posto = $posto_aux
							 AND peca = $peca_de";
					$resP = pg_query($con,$sqlP);
				}
			}
		}
		$msg_erro = pg_last_error($con);
	}

	if (strlen ($msg_erro) == 0) {

		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_query ($con,"COMMIT TRANSACTION");

		if ($tpAuditor == "insert") {
			$auditorLog->retornaDadosTabela('tbl_depara', array('depara'=>$depara, 'fabrica'=>$login_fabrica) )
						->enviarLog('insert', 'tbl_depara', $login_fabrica."*".$depara);
        } else {
            $auditorLog->retornaDadosTabela()
                   ->enviarLog('update', 'tbl_depara', $login_fabrica."*".$depara);
        }

		header ("Location: $PHP_SELF?msg=$msg");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		$referencia_de    = $_POST["referencia_de"];
		$descricao_de     = $_POST["descricao_de"];
		$referencia_para  = $_POST["referencia_para"];
		$descricao_para   = $_POST["descricao_para"];
		$expira           = $_POST["expira"];
        $digitacao        = $_POST["digitacao"];
		$apartir          = $_POST["apartir"];

		if(strpos($msg_erro,'"tbl_depara_unico"'))
		$msg_erro = traduz("De-Para já cadastrado.");

		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
}


###CARREGA REGISTRO
if (strlen($depara) > 0) {
	$sql = "SELECT  tbl_depara.de  ,
					tbl_depara.para,
                    TO_CHAR(tbl_depara.expira,'DD/MM/YYYY')         AS expira,
                    TO_CHAR(tbl_depara.digitacao,'DD/MM/YYYY')      AS digitacao,
					TO_CHAR(tbl_depara.data_inicio,'DD/MM/YYYY')    AS apartir,
					de.descricao as descricao_de,
					para.descricao as descricao_para
					FROM    tbl_depara
					JOIN tbl_peca de ON de.peca = peca_de AND de.fabrica = $login_fabrica
					JOIN tbl_peca para ON para.peca  = peca_para AND para.fabrica = $login_fabrica
					WHERE   tbl_depara.fabrica = $login_fabrica
					AND     tbl_depara.depara  = $depara;";

	$res = pg_query ($con,$sql);

	if (pg_num_rows($res) > 0) {
		$referencia_de      = trim(pg_fetch_result($res,0,de));
		$descricao_de       = trim(pg_fetch_result($res,0,descricao_de));
		$referencia_para    = trim(pg_fetch_result($res,0,para));
		$descricao_para     = trim(pg_fetch_result($res,0,descricao_para));
		$expira             = trim(pg_fetch_result($res,0,expira));
        $digitacao          = trim(pg_fetch_result($res,0,digitacao));
		$apartir            = trim(pg_fetch_result($res,0,apartir));
	}
}

	#### EXCEL FABRICA 3
	if(in_array($login_fabrica, array(3,140))){
		if ($_POST["gerar_excel"]) {
		
			$sql = "SELECT tbl_depara.depara,
					       tbl_depara.de, 
					       tbl_depara.para, 
					       TO_CHAR(tbl_depara.expira,'DD/MM/YYYY') AS expira, 
					       TO_CHAR(tbl_depara.digitacao,'DD/MM/YYYY') AS digitacao, 
					       pc1.descricao AS descricao_de, 
					       pc2.descricao AS descricao_para 
					FROM tbl_depara 
					JOIN tbl_peca pc1 ON pc1.referencia = tbl_depara.de AND pc1.fabrica = $login_fabrica
					JOIN tbl_peca pc2 ON pc2.referencia = tbl_depara.para AND pc2.fabrica = $login_fabrica
					WHERE tbl_depara.fabrica = $login_fabrica 
					ORDER BY tbl_depara.de";

				$resDepara = pg_query ($con,$sql);
	
		
			if (pg_num_rows($resDepara) > 0) {
				$data = date("d-m-Y-H:i");
				$fileName = "relatorio_de_para-{$data}.xls";
		
				$file = fopen("/tmp/{$fileName}", "w");

				$thead = "
					<table border='1'>
						<thead>
							<tr>
								<th colspan='2' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
									".traduz("DE")."
								</th>
								<th colspan='4' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
									".traduz("PARA")."
								</th>
							</tr>
							<tr>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Referência")."</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Descrição")."</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Referência")."</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Descrição")."</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Expira")."</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Inclusão")."</th>
							</tr>
						</thead>
						<tbody>";
				fwrite($file, $thead);

					for ($y = 0 ; $y < pg_num_rows($resDepara) ; $y++){
						$depara          = trim(pg_fetch_result($resDepara,$y,depara));
						$referencia_de   = trim(pg_fetch_result($resDepara,$y,de));
						$descricao_de    = trim(pg_fetch_result($resDepara,$y,descricao_de));
						$referencia_para = trim(pg_fetch_result($resDepara,$y,para));
						$descricao_para  = trim(pg_fetch_result($resDepara,$y,descricao_para));
						$expira          = trim(pg_fetch_result($resDepara,$y,expira));
						$digitacao       = trim(pg_fetch_result($resDepara,$y,digitacao));	
					
						$body .= "<tr>
									<td nowrap align='center' valign='top'>{$referencia_de}</td>
									<td nowrap align='center' valign='top'>{$descricao_de}</td>
									<td nowrap align='center' valign='top'>{$referencia_para}</td>
									<td nowrap align='center' valign='top'>{$descricao_para}</td>
									<td nowrap align='center' valign='top'>{$expira}</td>
									<td nowrap align='center' valign='top'>{$digitacao}</td>

								</tr>";
					}

				fwrite($file, $body);
				fwrite($file, "
							<tr>
								<th colspan='4' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >".traduz("Total de % registros", null,null,[pg_num_rows($resDepara)])."</th>
							</tr>
						</tbody>
					</table>
				");
				fclose($file);
				if (file_exists("/tmp/{$fileName}")) {
					system("mv /tmp/{$fileName} xls/{$fileName}");

					echo "xls/{$fileName}";
				}
			}exit;
		}
	}

//echo "Teste====".$descricao_para;
$layout_menu = 'cadastro';
$title = traduz("CADASTRO DE-PARA");
include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");
?>

<style type='text/css'>

</style>

<script type="text/javascript" charset="utf-8">

	$(function(){

		$.dataTableLoad({
			table: "#depara_cadastro", 
			type: "custom", 
			config: [ "pesquisa" ]
		});

		$('#digitacao').datepicker({startdate:'01/01/2000'});
        $('#expira').datepicker({startdate:'01/01/2000'});
		$('#apartir').datepicker({startdate:'01/01/2000'});
		$('#digitacao').datepicker().mask('99/99/9999');
        $('#expira').datepicker().mask('99/99/9999');
		$('#apartir').datepicker().mask('99/99/9999');


		function formatItem(row)
		{
			return row[2] + " - " + row[1];
		}
		
		Shadowbox.init();

		$.autocompleteLoad(Array("produto"));

		$("span[rel=lupa]").click(function () {
			$.lupa($(this), ['de','de_para']);
		});

		$("#btnMessage").click(function() {
        	var res = window.confirm('<?=traduz("Deseja realmente excluir o registro?")?>');
	        if (res === true) {
	            $("#resExcluir").html('<?=traduz("Você confirmou a exclusão.")?>');
	        } else {
	            $("#resExcluir").html('<?=traduz("Você cancelou a exclusão.")?>');
	        }
 	    });

	});

	function retorna_peca (retorno) {

		if(retorno.de != undefined && retorno.de == true){
			$("#referencia_de").val(retorno.referencia);
			$("#descricao_de").val(retorno.descricao);
		}

		if(retorno.de_para != undefined && retorno.de_para == true){
			$("#referencia_para").val(retorno.referencia);
			$("#descricao_para").val(retorno.descricao);
		}
	}

</script>

<?php
    #include "javascript_calendario_new.php"; //adicionado por Fabio 27-09-2007
   # include '../js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */
?>
<? if(strlen($_GET['msg'])>0){ ?>
	<div class="alert alert-success">
		<h4> <? echo $msg; ?> </h4>
	</div>
<? } ?>

<? if(strlen($msg_erro)>0){ ?>
	<div class="alert alert-error">
		<h4> <? echo $msg_erro; ?> </h4>
	</div>
<? } ?>

<form name='frm_depara' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
<input type="hidden" name="depara" value="<? echo $depara ?>">

	<div class='titulo_tabela '>De</div><br />

	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='referencia_de'><?=traduz('Referência')?></label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" id="referencia_de" name="referencia_de" class='span12' maxlength="20" value="<? echo $referencia_de ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" de='true'/>
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='descricao_de'><?=traduz('Descrição')?></label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" id="descricao_de" name="descricao_de" class='span12' value="<? echo $descricao_de ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" de='true'/>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
<?php
if($login_fabrica == 74){
?>

    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span8'>
            <div class='control-group'>
                <label class='control-label' for='expira'><?=traduz('Até')?></label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <input type="text" name="expira" id="expira" size="12" maxlength="10" class='span12' value= "<?=$expira?>">
                    </div>
                </div>
            </div>
        <div class='span2'></div>
        </div>
    </div>
<?php
}
?>
	<br />
	
	<div class='titulo_tabela '><?=traduz('Para')?></div><br />

    <div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='referencia_para'><?=traduz('Referência')?></label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" id="referencia_para" name="referencia_para" class='span12' maxlength="20" value="<? echo $referencia_para ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" de_para='true' />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='descricao_para'><?=traduz('Descrição')?></label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" id="descricao_para" name="descricao_para" class='span12' value="<? echo $descricao_para ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" de_para='true' />
					</div>
				</div>
			</div>
		</div>
        <div class='span2'></div>
    </div>
<?php
if($login_fabrica == 74){
?>

	<div class='row-fluid'>
		<div class='span2'></div>
        <div class='span8'>
            <div class='control-group'>
                <label class='control-label' for='apartir'><?=traduz('A Partir de')?></label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <input type="text" name="apartir" id="apartir" size="12" maxlength="10" class='span12' value= "<?=$apartir?>">
                    </div>
                </div>
            </div>
        <div class='span2'></div>
        </div>
	</div>
<?php
}
?>

	<br />
<?php
if($login_fabrica != 74){
?>
	<div class='titulo_tabela '><?=traduz('Data de Expiração')?></div><br />

	<div class="row-fluid">
		<div class="span2"></div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='expira'><?=traduz('Data')?></label>
				<div class='controls controls-row'>
					<div class='span4'>
						<input type="text" name="expira" id="expira" size="12" maxlength="10" class='span12' value= "<?=$expira?>">
					</div>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span8 text-info"><?=traduz('Campo opcional, se preenchido, o cadastro será apagado nesta data')?></div>
		<div class="span2"></div>
	</div>
<?php
}
?>

	<p><br/>
		<input type='hidden' name='btnacao' value=''>
		<input type="button" class="btn" value='<?=traduz("Gravar")?>' ONCLICK="javascript: if (document.frm_depara.btnacao.value == '' ) { document.frm_depara.btnacao.value='gravar' ; document.frm_depara.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário">
		<input type="button" id="btnMessage" class="btn btn-danger" value='<?=traduz("Apagar")?>' ONCLICK="javascript: if (document.frm_depara.btnacao.value == '' ) { document.frm_depara.btnacao.value='deletar' ; document.frm_depara.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar produto">
		<input type="button" class="btn" value='<?=traduz("Limpar")?>' ONCLICK="javascript: if (document.frm_depara.btnacao.value == '' ) { document.frm_depara.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos">
	</p><br/>
<?php
if (!is_null($depara)) {
?>
	<div class='row-fluid'>
		<div class='span9'></div>
		<div class='span3'>
			<div class="control-group">
            	<a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_depara&id=<?php echo $depara; ?>' name="btnAuditorLog"><?=traduz('Visualizar Log Auditor')?></a>
        </div>
		</div>
	</div>
<?php
}
?>
	
</form>

<?php if($login_fabrica == 3) { ?>
	

	<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype='multipart/form-data' class='form-search form-inline tc_formulario'>
		<div class='titulo_tabela '>Upload de De >> Para via CSV</div><br/>
		<div class="row-fluid">
			<div class="span1"></div>
			<div class='span10'>
				<div class="control-group">
					<div class="text-info"><h4>Arquivo</h4></div>
					<div class="alert alert" style="text-align: justify">
						<h4>Dicas importantes sobre o upload: </h4>
						
						<p>O seguinte layout deve ser obedecido: </p>

						<p>
							<strong>codigo_de;codigo_para;data_expiração;excluir;</strong>
						</p>

						<p>Os campos data_expiração e excluir são opcionais e devem ser preenchidos da seguinte forma : </p>
						<div class="row-fluid">
							<div class="span6">
								<p>
									<strong>
										Data Expiração:
									</strong>
								</p>
									<p>A data deve obedecer o formato brasileiro</p>
									<p>Ex : <?= date('d/m/Y')?></p>
									<p><strong>
										* Caso a data seja anterior ao dia em que o upload é realizado, o registro não será salvo
									</strong></p>
							</div>
							<div class="span6">	
								<p>
									<strong>
										Excluir
									</strong>
								</p>
									<p>apenas acrescente a palavra "excluir" na quarta coluna</p>

									<p><strong>
										* Mesmo se não houver data de expiração na terceira coluna, é necessário que o "excluir" esteja localizado na quarta coluna
									</strong></p>
							</div>
						</div>
					</div>
					<div class="span8">
						<input type="hidden" name="upload_excel"/>
						<input type="file" name="arquivo_excel" id="arquivo_excel">
						<br><br>
						<input class="btn btn-primary" id="upload_arquivo_britania" type="submit" value="Upload">

						<br><br>
						<a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_depara&id=<?php echo $login_fabrica; ?>' name="btnAuditorLog">
								Visualizar Log Auditor
						</a>
						<br><br>
					</div>
				</div>
			</div>
			<div class="span1"></div>
		</div>
	</form>

	<?php 
	
	#$linkArquivo = 'xls/britania_depara_em_massa_log.txt';
	$linkArquivo = '../tmpFiles/britania_depara_em_massa_log.txt';

	if (file_exists($linkArquivo)) { ?>

		<div class="alert alert"> 
	    	 <h4>Ver Resultado</h4> <a target="blank" download href="<?=$linkArquivo?>"> Download do log de upload de De >> Para em TXT</a>
	    </div>
		<br><br><br>
	<?php } ?>
<?php } ?>

<table id="depara_cadastro" class='table table-striped table-bordered table-hover table-fixed'>
	<thead>
		<tr class='titulo_coluna'>
			<th colspan='3'>De</th>
			<th colspan='4'>Para</th>
		</tr>
		<tr class='titulo_coluna'>
			<?php if ($login_fabrica == 171) {?>
            <th><?=traduz('Referência Fábrica')?></th>
			<?php }?>
			<th><?=traduz('Referência')?></th>
			<th><?=traduz('Descrição')?></th>
			<? if($login_fabrica == 74){?>
            <th><?=traduz('Até')?></th>
			<?}?>
			<?php if ($login_fabrica == 171) {?>
            <th><?=traduz('Referência Fábrica')?></th>
			<?php }?>
			<th><?=traduz('Referência')?></th>
			<th><?=traduz('Descrição')?></th>
			<th><?=($login_fabrica == 74) ? "A Partir de" : "Expira"?></th>
			<? if($login_fabrica == 3){ ?>		
			<th><?=traduz('Inclusão')?></th>
			<? } ?>
		</tr>
	</thead>
	<tbody>

	<? 
		
		$sql = "SELECT tbl_depara.depara,
				       tbl_depara.de, 
				       tbl_depara.para, 
				       TO_CHAR(tbl_depara.expira,'DD/MM/YYYY') AS expira, 
                       TO_CHAR(tbl_depara.digitacao,'DD/MM/YYYY') AS digitacao,
				       TO_CHAR(tbl_depara.data_inicio,'DD/MM/YYYY') AS data_inicio,
				       pc1.descricao AS descricao_de, 
				       pc1.referencia_fabrica AS referencia_fabrica_de, 
				       pc2.referencia_fabrica AS referencia_fabrica_para, 
					   pc2.descricao AS descricao_para ,
						case when expira < current_date then 't' else 'f' end as expirada
				FROM tbl_depara 
				JOIN tbl_peca pc1 ON pc1.peca = tbl_depara.peca_de AND pc1.fabrica = $login_fabrica
				JOIN tbl_peca pc2 ON pc2.peca = tbl_depara.peca_para AND pc2.fabrica = $login_fabrica
				WHERE tbl_depara.fabrica = $login_fabrica 
				ORDER BY expira";
		
		$res = pg_query ($con,$sql);

		$contadorRES = pg_num_rows($res);

		if ($contadorRES > 0) {

			for ($y = 0 ; $y < $contadorRES; $y++){
				$depara          = trim(pg_fetch_result($res,$y,depara));
				$referencia_fabrica_de   = trim(pg_fetch_result($res,$y,referencia_fabrica_de));
				$referencia_fabrica_para   = trim(pg_fetch_result($res,$y,referencia_fabrica_para));
				$referencia_de   = trim(pg_fetch_result($res,$y,de));
				$descricao_de    = trim(pg_fetch_result($res,$y,descricao_de));
				$referencia_para = trim(pg_fetch_result($res,$y,para));
				$descricao_para  = trim(pg_fetch_result($res,$y,descricao_para));
                $expira          = trim(pg_fetch_result($res,$y,expira));
                $expirada          = trim(pg_fetch_result($res,$y,expirada));
				$data_inicio    = trim(pg_fetch_result($res,$y,data_inicio));
				$digitacao       = trim(pg_fetch_result($res,$y,digitacao));

				$expira_css = ($expirada == 't') ? 'style=" color:red "' : '';
?>
	<tr <?=$expira_css?>> 
				<?php if ($login_fabrica == 171) {?>
	            <td><?php echo $referencia_fabrica_de; ?></td>
				<?php }?>

				<td><? echo $referencia_de; ?></td>
				<td><? echo $descricao_de; ?></td>
				<?
				if($login_fabrica == 74){
				?>
				<td><? echo $expira; ?></td>
				<?
				}
				?>
				<?php if ($login_fabrica == 171) {?>
	            <td><?php echo $referencia_fabrica_para; ?></td>
				<?php }?>
				<td><a href="<? echo $PHP_SELF.'?depara='.$depara; ?>"> <? echo $referencia_para; ?></td>
				<td><a href="<? echo $PHP_SELF.'?depara='.$depara; ?>"> <? echo $descricao_para; ?></td>
				<td><?=($login_fabrica == 74) ? $data_inicio :$expira?></td>
				<? if($login_fabrica == 3){ ?>
				<td> <? echo $digitacao; ?> </td>
				<? } ?>
			</tr>
<?
			}
		}
?>
	</tbody>
</table>

	<?php
		echo '<center><br>Total de registros <b>', $y, '</b></center><br><br>';
		$jsonPOST = excelPostToJson($_POST);

		if(in_array($login_fabrica, array(3,140))){
	?>

		<div id='gerar_excel' class="btn_excel">
			<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
			<span><img src='imagens/excel.png' /></span>
			<span class="txt"><?=traduz('Gerar Arquivo Excel')?></span>
		</div>
	<?
	}
	?>

<?
	include "rodape.php";
?>
</body>
</html>
