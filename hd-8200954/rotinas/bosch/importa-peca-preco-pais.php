<?php

try
{

	include_once dirname(__FILE__) . '/../../dbconfig.php';
	include_once dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require_once dirname(__FILE__) . '/../funcoes.php';
	include_once dirname(__FILE__) . '/../traducao.php';

	$admin      = $argv[1];
	$pais_admin = $argv[2];
	if ($pais_admin <> "BR")
	{
		$cook_idioma = "es";
	}else{
		$cook_idioma ='pt-br';
	}

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

	$sql_admin = "SELECT email FROM tbl_admin WHERE admin = $admin AND fabrica = $fabrica";
	$res_admin = pg_query($con,$sql_admin);
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

	$vet['fabrica'] = 'bosch';
	$vet['tipo']    = 'preco-al';
	$vet['log']     = 2;

	$data       = date('Y-m-d-H');
	$arquivos = "/tmp";
	$origem   = "/tmp/bosch/";
	$arq_peca_preco_al = $origem . "peca-preco-al.txt";
	$arq_peca_preco_al_erro = $origem . "pecas_al_nao_encontradas.txt";
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

	if (file_exists($arq_peca_preco_al) and (filesize($arq_peca_preco_al) > 0))
	{		$sql = "CREATE TEMP TABLE tmp_bosch_peca_preco_al (referencia text, preco text, pais text)";
		$res = pg_query($con,$sql);

		if (pg_last_error())
		{
			Log::log2($vet, pg_last_error());
			throw new Exception(pg_last_error());
		}

		$sql_pais = "SELECT pais FROM tbl_pais";
		$res_pais = pg_query($con,$sql_pais);
		if (pg_num_rows($res_pais) > 0)
		{
			$a_xpais = pg_fetch_all($res_pais);
			foreach ($a_xpais as $pais)
			{
				$xpais[] = $pais['pais']; 
			}
			unset($pais);
			$msg_pais = "";
		}
		else
		{
			$msg = traduz("erro.arquivo.nao.processado.favor.entrar.em.contato.com.o.suporte.da.telecontrol.",$con,$cook_idioma);
			$msg_erro .= msgErro($msg);
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$conteudo = file_get_contents($arq_peca_preco_al);
		$conteudo_array = explode("\n",$conteudo);

		foreach ($conteudo_array as $linha)
		{
			$linha_vf = explode("\t",$linha);
			$linha_ct = count($linha_vf);
			if (!empty($linha) and $linha_ct == 3)
			{
				list($referencia, $preco, $pais) = explode("\t",$linha);
				$referencia = trim($referencia);
				$preco      = trim($preco);
				$pais       = trim($pais); 
				if (!in_array($pais, $xpais))
				{
					$msg_pais .= $pais."/";
				}

				$string = $referencia . "\t" . $preco . "\t" . $pais . "\n";
				if (strlen($referencia) > 0 and strlen($preco) > 0 and strlen($pais) > 0)
				{
					$sql = "INSERT INTO tmp_bosch_peca_preco_al (referencia, preco, pais) VALUES ('$referencia', '$preco', '$pais')";
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
					$msg_erro = msgErro(traduz("erro.favor.verificar.o.layout.do.arquivo.",$con,$cook_idioma));
					break;
				}
			}
		}

		if ( !empty($msg_erro) ) 
		{
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		if (strlen($msg_pais) > 0)
		{
			$msg_pais = substr($msg_pais,0,-1);
			$msg = traduz("erro.o.pais.com.a.sigla.%.nao.existe.em.nosso.banco.de.dados.ou.a.sigla.%.esta.incorreta.favor.corrigir.o.arquivo.e.fazer.o.upload.novamente.",$con,$cook_idioma,array($msg_pais,$msg_pais));
			$msg_erro .= msgErro($msg);
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		if ( !empty($msg_erro) ) 
		{
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$begin = pg_query($con,"BEGIN TRANSACTION");


		$sql = "SELECT pais from tmp_bosch_peca_preco_al limit 1";
		$res = pg_query($con,$sql);
		$pais = pg_result($con,0,'pais');

		$alter_table = pg_query($con,"ALTER TABLE tmp_bosch_peca_preco_al ADD peca int4");
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) 
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "UPDATE tmp_bosch_peca_preco_al
				SET    peca = tbl_peca.peca
				FROM   tbl_peca 
				WHERE  TRIM(tbl_peca.referencia) =  TRIM(tmp_bosch_peca_preco_al.referencia)
				AND    tmp_bosch_peca_preco_al.peca IS NULL
				AND    tbl_peca.fabrica = $fabrica";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) 
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "SELECT referencia INTO TEMP tmp_erro_bosch_peca_preco_al FROM tmp_bosch_peca_preco_al WHERE peca IS NULL";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) 
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$res = pg_query($con,"COPY tmp_erro_bosch_peca_preco_al TO stdout");
		$resultado = pg_fetch_all($res);
		foreach ($resultado as $value)
		{
			$conteudo .= implode("\t", $value) . "\n";
			file_put_contents ($arq_peca_preco_al_erro,$conteudo);
		}
		pg_end_copy($con);

		$sql = "SELECT COUNT(pais),referencia INTO TEMP tmp_duplicado FROM tmp_bosch_peca_preco_al GROUP BY referencia HAVING COUNT(pais)>1;";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) 
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$alter_table = pg_query($con,"ALTER TABLE tmp_bosch_peca_preco_al ADD tem_preco BOOLEAN");
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) 
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "UPDATE tmp_bosch_peca_preco_al 
				set preco = replace(preco, ',', '.') ;";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) 
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "UPDATE tmp_bosch_peca_preco_al 
				SET tem_preco = TRUE 
				FROM tbl_tabela_item 
				WHERE tmp_bosch_peca_preco_al.peca = tbl_tabela_item.peca 
				AND tabela in (
					SELECT tabela FROM tbl_tabela 
					WHERE ativa IS TRUE
					AND   UPPER(SUBSTR(TRIM(sigla_tabela),0,3)) = UPPER(TRIM(tmp_bosch_peca_preco_al.pais))
				)";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) 
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "INSERT INTO tbl_tabela_item (tabela,peca,preco) 
				SELECT DISTINCT (
					SELECT tabela FROM tbl_tabela 
					WHERE ativa IS TRUE
					AND   UPPER(SUBSTR(TRIM(sigla_tabela),0,3))::char(2) = UPPER(TRIM(tmp_bosch_peca_preco_al.pais))::char(2)
					AND   fabrica = 20
				),peca::integer,preco::FLOAT 
				FROM tmp_bosch_peca_preco_al 
				WHERE tem_preco IS NOT TRUE
				AND peca IS NOT NULL
				AND referencia not in (select referencia from tmp_duplicado)";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) 
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "UPDATE tbl_tabela_item 
				SET preco = tmp_bosch_peca_preco_al.preco::FLOAT
				FROM tmp_bosch_peca_preco_al
				WHERE tmp_bosch_peca_preco_al.peca = tbl_tabela_item.peca 
				AND tmp_bosch_peca_preco_al.preco::float <> tbl_tabela_item.preco::float
				AND (tmp_bosch_peca_preco_al.preco is NOT null AND tmp_bosch_peca_preco_al.preco::float>0) 
				AND tem_preco  IS TRUE
				AND referencia NOT IN (select referencia from tmp_duplicado)
				AND tabela in (
					SELECT tabela FROM tbl_tabela 
					WHERE ativa IS TRUE
					AND   UPPER(SUBSTR(TRIM(sigla_tabela),0,3)) = UPPER(TRIM(tmp_bosch_peca_preco_al.pais))
				)";
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
			copy($origem . "peca-preco-al.txt", "/tmp/bosch/peca-preco-al-" . $pais . "-" . $data . ".txt");
			echo "<script>alert('".traduz("arquivo.importado.com.sucesso.",$con,$cook_idioma)."');</script>";
		}

		 if (file_exists($arq_peca_preco_al_erro) and (filesize($arq_peca_preco_al_erro) > 0))
		 {
			$msg_rni = traduz("para.o.pais.%.nao.foram.importadas.as.seguintes.pecas.",$con,$cook_idioma,$pais)."<br>";
			

			$conteudo = file_get_contents($arq_peca_preco_al_erro);
			$conteudo_array = explode("\n",$conteudo);

			foreach ($conteudo_array as $linha)
			{
				if (!empty($linha))
				{
					list($referencia, $preco, $pais) = explode("\t",$linha);
					$referencia = trim($referencia);
					$preco      = trim($preco);
					$pais       = trim($pais); 

					$msg_rni .= $referencia . "\t" . $preco . "\t" . $pais . "<br>";
				}
			}

			 Log::envia_email($vet, 'BOSCH - '.traduz("erro.na.importacao.de.preco.al.",$con,$cook_idioma), $msg_rni);
		 }

		rename($origem . "peca-preco-al.txt", "/tmp/bosch/" . $data . "peca-preco-al.txt");
	}
}
catch (Exception $e)
{
	$arq_erro = '/tmp/' . $vet['fabrica'] . '/importa-preco-al-' . $data . '.erro';
	if (file_exists($arq_erro))
	{
		$hoje = date("d/m/Y - H:i:s");
		$msg = "<div style='background-color: #eeeecc; border: 1px solid #000;'><h2 style='color: #4441FF;'>($hoje) ".traduz("nao.foi.feita.a.importacao.de.precos.para.o.pais.%.",$con,$cook_idioma,$pais_admin)."</h2><br>";
		$msg.= file_get_contents($arq_erro);
		$msg .= "</div>";
		Log::envia_email($vet, 'BOSCH - '.traduz("erro.na.importacao.de.preco.al.",$con,$cook_idioma), $msg);
	}
	echo $e->getMessage();
}

?>
