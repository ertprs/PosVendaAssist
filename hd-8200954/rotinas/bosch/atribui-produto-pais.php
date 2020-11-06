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
	$vet['tipo']    = 'produto-pais';
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

	$data     = date('Y-m-d-H');
	$arquivos = "/tmp";
	$origem   = "/tmp/bosch/";
	$arq_produto_pais = $origem . "produto-pais.txt";
	$msg_erro = "";

	function msgErro($msg = "")
	{
		$retorno = '';

		if (!empty($msg))
		{
			if (strpos($msg,'insert or update on table "tbl_produto_pais" violates foreign key constraint "pais_fk"') > 0) {
				$rr = preg_replace("/.*([A-Z]{2})\).*/", '$1', substr($msg, strpos($msg, 'Key')));
				$msg      = traduz("erro.o.pais.com.a.sigla.%.nao.existe.em.nosso.banco.de.dados.ou.a.sigla.%.esta.incorreta.favor.corrigir.o.arquivo.e.fazer.o.upload.novamente.",$con,$cook_idioma,array($rr,$rr));
			}
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

	if (file_exists($arq_produto_pais) and (filesize($arq_produto_pais) > 0))
	{
		$sql = "CREATE TEMP TABLE tmp_bosch_produto_pais 
					(
						referencia               text,
						pais                     text,
						garantia                 text
					) 
				WITH OIDS";
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

		$conteudo = file_get_contents($arq_produto_pais);
		$conteudo_array = explode("\n",$conteudo);

		foreach ($conteudo_array as $linha)
		{
			$linha_vf = explode("\t",$linha);
			$linha_ct = count($linha_vf);
			if (!empty($linha) and $linha_ct >= 2 and $linha_ct <= 3)
			{ 
				list($referencia, $pais, $garantia) = explode("\t",$linha);
				$referencia = trim($referencia);
				$pais       = trim($pais);
				if (!in_array($pais, $xpais))
				{
					$msg_pais .= $pais."/";
				}
				$garantia   = trim($garantia); 

				$string = $referencia . "\t" . $pais . "\t" . $garantia . "\n";
				if (strlen($referencia) > 0 and strlen($pais) > 0)
				{
					$sql = "INSERT INTO tmp_bosch_produto_pais (referencia, pais, garantia) VALUES ('$referencia','$pais','$garantia')";
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

		$sql = "DELETE FROM tmp_bosch_produto_pais USING 
					(
						SELECT referencia, min(oid) AS oid 
						FROM tmp_bosch_produto_pais 
						GROUP BY referencia, pais having count(*)>1 
					) x 
				WHERE tmp_bosch_produto_pais.referencia = x.referencia 
				AND x.oid <> tmp_bosch_produto_pais.oid";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) 
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$alter_table = pg_query($con,"ALTER TABLE tmp_bosch_produto_pais ADD produto int4");
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) 
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "UPDATE tmp_bosch_produto_pais SET produto =  tbl_produto.produto 
				FROM tbl_produto 
				JOIN tbl_linha USING(linha) 
				WHERE UPPER(TRIM(tbl_produto.referencia)) = UPPER(TRIM(tmp_bosch_produto_pais.referencia)) 
				AND fabrica = $fabrica";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) 
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$alter_table = pg_query($con,"ALTER TABLE tmp_bosch_produto_pais ADD tem_produto_pais BOOLEAN");
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) 
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "UPDATE tmp_bosch_produto_pais SET tem_produto_pais = 't' 
				FROM tbl_produto_pais 
				WHERE tbl_produto_pais.produto        = tmp_bosch_produto_pais.produto
				AND   tbl_produto_pais.pais    = UPPER(TRIM(tmp_bosch_produto_pais.pais))";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) 
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "UPDATE tbl_produto_pais SET
					garantia = tmp_bosch_produto_pais.garantia::numeric
				FROM tmp_bosch_produto_pais
				WHERE tmp_bosch_produto_pais.produto = tbl_produto_pais.produto
				AND   UPPER(TRIM(tmp_bosch_produto_pais.pais)) = tbl_produto_pais.pais
				AND   tem_produto_pais              IS TRUE";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) 
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "INSERT INTO tbl_produto_pais (
					produto,
					pais,
					garantia
					)
				SELECT
					produto,
					trim(pais),
					garantia::numeric
				FROM tmp_bosch_produto_pais 
				WHERE produto          IS NOT NULL
				AND   pais             IS NOT NULL
				AND   tem_produto_pais IS NOT TRUE";
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
			rename($origem . "produto-pais.txt", "/tmp/bosch/bkp-entrada/" . $data . "produto-pais.txt");
			echo "<script>alert('".traduz("arquivo.importado.com.sucesso.",$con,$cook_idioma)."');</script>";
		}

	}
}
catch (Exception $e)
{
	$arq_erro = '/tmp/' . $vet['fabrica'] . '/importa-produto-pais-' . $data . '.erro';
	if (file_exists($arq_erro))
	{
		$hoje = date("d/m/Y - H:i:s");
		$msg  = "<div style='background-color: #eeeecc; border: 1px solid #000;'><h2 style='color: #4441FF;'>($hoje) ".traduz("erro.na.importacao.de.produtos.pais.",$con,$cook_idioma)."</h2><br>";
		$msg .= file_get_contents($arq_erro);
		$msg .= "</div>";
		Log::envia_email($vet, "BOSCH - ".traduz("erro.na.importacao.de.produtos.pais.",$con,$cook_idioma), $msg);
	}
	echo $e->getMessage();
}

?>
