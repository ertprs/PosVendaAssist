<?php

try
{

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require_once dirname(__FILE__) . '/../funcoes.php';
	include  ('/var/www/includes/traducao.php');

	if (!function_exists("traduz")) {
		function traduz($inputText,$con,$cook_idioma_pesquisa,$x_parametros = null){

			global $msg_traducao;
			global $PHP_SELF;

			$cook_idioma_pesquisa = strtolower($cook_idioma_pesquisa);

			if (strlen($cook_idioma_pesquisa)==0){
				$cook_idioma_pesquisa = 'pt-br';
			}

			$mensagem = $msg_traducao[$cook_idioma_pesquisa][$inputText];

			if (strlen($mensagem)==0){
				$mensagem = $msg_traducao['pt-br'][$inputText];
			}

			if (strlen($mensagem)==0){
				$mensagem = $msg_traducao['es'][$inputText];
			}

			if (strlen($mensagem)==0){
				$mensagem = $msg_traducao['en-us'][$inputText];
			}

			if ($x_parametros){
				if (!is_array($x_parametros)){
					$x_parametros = explode(",",$x_parametros);
				}
				while ( list($x_variavel,$x_valor) = each($x_parametros)){
					$mensagem = preg_replace('/%/',$x_valor,$mensagem,1);
				}
			}

			return $mensagem;

		}
	}

	if (!function_exists("fecho")) {
		function fecho($inputText,$con,$cook_idioma,$x_parametros = null){
			echo traduz($inputText,$con,$cook_idioma,$x_parametros);
		};
	}

	$fabrica  = 20;
	$dia_mes     = date('d');

	$vet['fabrica'] = 'bosch';
	$vet['tipo']    = 'peca';
	$admin = $argv[1];
	$sql_admin = "SELECT email FROM tbl_admin WHERE admin = $admin AND fabrica = $fabrica";
	$res_admin = pg_query($con,$sql_admin);
	$vet['dest'][0] = "helpdesk@telecontrol.com.br";
	$vet['dest'][1] = "suporte@telecontrol.com.br";
	$vet['dest'][2] = "robson.gastao@br.bosch.com";
	if (pg_num_rows($res_admin) > 0)
	{
		$email_admin = pg_result($res_admin,0,"email");
		$vet['dest'][5] = "$email_admin";
	}
	else
	{
		echo "<script>alert('".traduz("seu.usuario.nao.possui.e.mail.cadastrado.por.favor.cadastre.um.e.mail",$con,$cook_idioma)."');</script>";
	}

	$vet['log']     = 2;

	$data       = date('Y-m-d-H');
	$arquivos = "/tmp";
	$origem   = "/tmp/bosch/";
	$arq_peca = $origem . "peca.txt";
	$msg_erro = "";

	function msgErro($msg = "")
	{
		$retorno = '';

		if (!empty($msg))
		{
			if (strpos ($msg,"invalid input syntax") > 0 or strpos ($msg,"value too long for type character") > 0) {
				$msg = traduz("erro.favor.verificar.o.layout.do.arquivo.",$con,$cook_idioma);
			}
			if (strpos ($msg,"does not exist") > 0 or strpos ($msg,"syntax error at or near") > 0) {
				$msg = traduz("erro.arquivo.nao.processado.favor.entrar.em.contato.com.o.suporte.da.telecontrol.",$con,$cook_idioma);
			}

			$hoje = date("d/m/Y - H:i:s");
			$retorno = "<p style='color: #ee2222;font-size:16px'>";
			$retorno .= $hoje." / ".$msg;
			$retorno .= "</p>";
		}

		return $retorno;
	}



	if (file_exists($arq_peca) and (filesize($arq_peca) > 0))
	{
		$begin = pg_query($con,"BEGIN TRANSACTION");

		$sql = "CREATE TEMP TABLE tmp_bosch_peca
					(
						referencia   varchar(20),
						descricao    text       ,
						preco        float      ,
						acessorio    boolean,
						descricao_espanhol text
					)
				WITH OIDS";
		$res = pg_query($con,$sql);

		$conteudo = file_get_contents($arq_peca);
		$conteudo_array = explode("\n",$conteudo);

		foreach ($conteudo_array as $linha)
		{
			$linha_vf = explode("\t",$linha);
			$linha_ct = count($linha_vf);
			if (!empty($linha) and $linha_ct == 5)
			{
				list($referencia, $descricao , $preco, $acessorio, $descricao_espanhol) = explode("\t",$linha);
				$referencia = trim($referencia);
				$descricao  = preg_replace("/[\']/","",trim($descricao));
				$preco      = trim($preco);
				$acessorio  = trim($acessorio);
				$descricao_espanhol = preg_replace("/[\']/","",trim($descricao_espanhol));

				$string = $referencia . "\t" . $descricao . "\t" . $preco . "\t" . $acessorio . "\t" . $descricao_espanhol . "\n";
				if (strlen($referencia) > 0 and strlen($descricao) > 0 and strlen($preco) > 0 and strlen($acessorio) > 0)
				{
					$sql = "INSERT INTO tmp_bosch_peca (referencia, descricao, preco, acessorio, descricao_espanhol) VALUES ('$referencia', '$descricao', '$preco', '$acessorio', '$descricao_espanhol')";
					$res = pg_query($con,$sql);
					$msg_erro .= msgErro(pg_last_error());
					if ($msg_erro <> "")
					{
						Log::log2($vet, $msg_erro);
						throw new Exception($msg_erro);
						break;
					}
				}
				else
				{
					$msg_erro .= msgErro(traduz("erro.favor.verificar.o.layout.do.arquivo.",$con,$cook_idioma));
					break;
				}
			}
		}

		if ( !empty($msg_erro) )
		{
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$alter_table = pg_query($con,"ALTER TABLE tmp_bosch_peca ADD peca TEXT");
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) )
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "DELETE FROM tmp_bosch_peca USING
						(
						SELECT referencia, MIN (oid) AS oid
						FROM tmp_bosch_peca
						GROUP BY referencia having count(*)>1
						) x
				WHERE tmp_bosch_peca.referencia = x.referencia
				AND x.oid <> tmp_bosch_peca.oid;";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) )
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "UPDATE tmp_bosch_peca
				SET    peca = tbl_peca.peca::text
				FROM   tbl_peca
				WHERE  trim(tbl_peca.referencia) =  trim(tmp_bosch_peca.referencia)
				AND    tmp_bosch_peca.peca IS NULL";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) )
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "INSERT INTO tbl_peca (fabrica,referencia,descricao,ativo,multiplo,origem,acessorio)
				SELECT DISTINCT 20,referencia,descricao::char(50),TRUE,1,'TER',acessorio
				FROM tmp_bosch_peca
				WHERE peca IS NULL";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) )
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "UPDATE tmp_bosch_peca
				SET   peca = tbl_peca.peca::text
				FROM  tbl_peca
				WHERE tbl_peca.referencia =  tmp_bosch_peca.referencia
				AND   tmp_bosch_peca.peca IS NULL";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) )
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "INSERT INTO
					tbl_peca_idioma (peca, idioma, descricao)
				SELECT
					tmp_bosch_peca.peca::int, 'ES', tmp_bosch_peca.descricao_espanhol
				FROM tmp_bosch_peca
			        LEFT JOIN tbl_peca_idioma on tbl_peca_idioma.peca = tmp_bosch_peca.peca::integer 
				WHERE tmp_bosch_peca.peca IS NOT NULL
				AND   tbl_peca_idioma.peca ISNULL
				AND LENGTH(tmp_bosch_peca.descricao_espanhol) > 0";
		$res = pg_query($con, $sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) )
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$alter_table = pg_query($con,"ALTER TABLE tmp_bosch_peca ADD tem_preco BOOLEAN");
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) )
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "UPDATE tmp_bosch_peca
				SET tem_preco = TRUE
				FROM tbl_tabela_item
				WHERE tmp_bosch_peca.peca = tbl_tabela_item.peca::text
				AND tabela = 123";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) )
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "INSERT INTO tbl_tabela_item (tabela,peca,preco)
				SELECT DISTINCT 123,peca::integer,preco::float
				FROM tmp_bosch_peca
				WHERE tem_preco IS NOT TRUE
				AND peca IS NOT NULL";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) )
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "UPDATE tbl_tabela_item
				SET preco = tmp_bosch_peca.preco::FLOAT
				FROM tmp_bosch_peca
				WHERE tmp_bosch_peca.peca = tbl_tabela_item.peca::text
				AND tmp_bosch_peca.preco::float <> tbl_tabela_item.preco
				AND (tmp_bosch_peca.preco is NOT null AND tmp_bosch_peca.preco::float>0)
				AND tabela=123
				AND tem_preco IS TRUE";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) )
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "INSERT INTO tbl_lista_basica (produto, peca ,qtde ,fabrica ,ativo)
				SELECT 20568,peca::int , 20 ,$fabrica ,'t' 
				FROM tmp_bosch_peca
				WHERE acessorio IS NOT TRUE
				AND peca::int NOT IN (SELECT peca FROM tbl_lista_basica WHERE produto=20568)";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) )
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "INSERT INTO tbl_lista_basica (produto, peca ,qtde ,fabrica ,ativo)
				SELECT 20567,peca::int , 20 ,$fabrica ,'t' 
				FROM tmp_bosch_peca
				WHERE acessorio IS TRUE
				AND peca::int NOT IN (SELECT peca FROM tbl_lista_basica WHERE produto=20567)";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) )
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$commit = pg_query($con,"COMMIT TRANSACTION");

		if (empty($msg_erro))
		{
			rename($origem . "peca.txt", "/tmp/bosch/bkp-entrada/importa-peca-" . $data . ".txt");
			echo "<script>alert('Importado com sucesso');</script>";
		}

	}
}
catch (Exception $e)
{
	$arq_erro = '/tmp/' . $vet['fabrica'] . '/importa-peca-' . $data . '.erro';
	if (file_exists($arq_erro))
	{
		$hoje = date("d/m/Y - H:i:s");
		$msg  = "<div style='background-color: #eeeecc; border: 1px solid #000;'><h2 style='color: #4441FF;'>($hoje) ".traduz("erro.na.importacao.de.pecas.",$con,$cook_idioma)."</h2><br>";
		$msg.= file_get_contents($arq_erro);
		$msg .= "</div>";
		Log::envia_email($vet, "BOSCH - ".traduz("erro.na.importacao.de.pecas.",$con,$cook_idioma), $msg);
	}
	echo $e->getMessage();
}

?>
