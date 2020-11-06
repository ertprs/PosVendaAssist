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

	$fabrica     = 20;
	$dia_mes     = date('d');

	$vet['fabrica'] = 'bosch';
	$vet['tipo']    = 'lista_basica';
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
	$arq_lbm = $origem . "lbm.txt";
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

	if (file_exists($arq_lbm) and (filesize($arq_lbm) > 0))
	{
		$sql = "CREATE TEMP TABLE tmp_bosch_lbm (
				referencia_produto      text,
				referencia_peca         text,
				posicao                 text,
				qtde                    text
			)";

		$res= pg_query($con,$sql);
		if (pg_last_error())
		{
			Log::log2($vet, pg_last_error());
			throw new Exception(pg_last_error());
		}

		$conteudo = file_get_contents($arq_lbm);
		$conteudo_array = explode("\n",$conteudo);

		foreach ($conteudo_array as $linha)
		{
			$linha_vf = explode("\t",$linha);
			$linha_ct = count($linha_vf);

			if (empty($linha))
				continue;

			if (!empty($linha) and $linha_ct >= 3 and $linha_ct <= 4)
			{
				list($referencia_produto, $referencia_peca , $posicao, $qtde) = explode("\t",$linha);
				$referencia_produto = trim($referencia_produto);
				$referencia_peca    = trim($referencia_peca);
				$posicao            = trim($posicao);
				$qtde               = trim($qtde);
				$qtde = preg_replace('/\D/', '', $qtde);

				$string = $referencia_produto . "\t" . $referencia_peca . "\t" . $posicao . "\t" . $qtde . "\n";
				if (strlen($referencia_produto) > 0 and strlen($referencia_peca) > 0 and strlen($qtde) > 0)
				{
					$sql = "INSERT INTO tmp_bosch_lbm (referencia_produto, referencia_peca, posicao, qtde) VALUES ('$referencia_produto','$referencia_peca','$posicao','$qtde')";
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

		$alter_table = pg_query($con,"ALTER TABLE tmp_bosch_lbm add produto int4");
		$msg_erro .= msgErro(pg_last_error());
		$alter_table = pg_query($con,"ALTER TABLE tmp_bosch_lbm add peca int4;");
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) 
		{
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$begin = pg_query($con,"BEGIN TRANSACTION");

		$sql = "UPDATE tmp_bosch_lbm 
				SET produto= tbl_produto.produto 
				FROM tbl_produto 
				JOIN tbl_linha USING(linha) 
				WHERE fabrica = $fabrica 
				AND UPPER(TRIM(tmp_bosch_lbm.referencia_produto)) =  UPPER(TRIM(tbl_produto.referencia));";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) 
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "UPDATE tmp_bosch_lbm 
				SET peca = tbl_peca.peca
				FROM tbl_peca
				WHERE fabrica=$fabrica
				AND UPPER(TRIM(tmp_bosch_lbm.referencia_peca)) =  UPPER(TRIM(tbl_peca.referencia));";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) 
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$alter_table = pg_query($con,"ALTER TABLE tmp_bosch_lbm add tem_lbm BOOLEAN");

		$sql = "UPDATE tmp_bosch_lbm 
				SET tem_lbm = TRUE 
				FROM tbl_lista_basica 
				WHERE tbl_lista_basica.produto = tmp_bosch_lbm.produto
				AND   tbl_lista_basica.peca    = tmp_bosch_lbm.peca
				AND   fabrica                  = $fabrica;";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) 
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "SELECT count(*) from tmp_bosch_lbm WHERE produto IS NULL OR peca IS NULL or qtde IS NULL";
		$res = pg_query($con,$sql);

		if ( pg_result($res, 0, 0) > 0 ) {
			echo '<p style="color:red;">Alguns registros n&atilde;o foram importados e foram enviados por e-mail.</p>';
		} 

		$sql = "INSERT INTO tbl_lista_basica (
						produto ,
						peca    ,
						posicao ,
						qtde    ,
						ativo   ,
						fabrica
						) 
				SELECT  
						produto   ::integer   ,
						peca      ::integer   ,
						posicao               ,
						trim(qtde)::numeric,
						't'                   ,
						$fabrica
				FROM  tmp_bosch_lbm
				WHERE tmp_bosch_lbm.tem_lbm IS NOT TRUE
				AND produto IS NOT NULL
				AND peca    IS NOT NULL
				AND qtde    IS NOT NULL
				AND qtde<>'';";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) 
		{
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "SELECT  *
				INTO temp tmp_bosch_lbm_falha
				FROM      tmp_bosch_lbm
				WHERE   (
					tmp_bosch_lbm.produto IS NULL 
					OR tmp_bosch_lbm.peca IS NULL 
					OR tmp_bosch_lbm.qtde IS NULL 
					OR tmp_bosch_lbm.qtde =''
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
			echo "<script>alert('".traduz("arquivo.importado.com.sucesso.",$con,$cook_idioma)."');</script>";
		}

		if (empty($msg_erro))
		{
			$sql = "SELECT  
						referencia_produto ,
						 referencia_peca    ,
						 posicao            ,
						 qtde               ,
						 produto, 
						 peca
				FROM  tmp_bosch_lbm
				WHERE tmp_bosch_lbm.tem_lbm IS NOT TRUE
				AND produto IS NOT NULL
				AND peca    IS NOT NULL
				AND qtde    IS NOT NULL
				AND qtde<>''";
			$res = pg_query($con,$sql);

			if (pg_num_rows($res) > 0)
			{
				$msg = traduz("os.seguintes.itens.foram.cadastrados.com.sucesso.",$con,$cook_idioma)."<br><br>";
				$resultado = pg_fetch_all($res);
				foreach ($resultado as $value)
				{
					$msg .= implode("\t", $value) . "<br>";
				}

				Log::envia_email($vet, "BOSCH - ".traduz("importacao.de.lista.basica.",$con,$cook_idioma), $msg);
			}
			
		}

		$sql = "SELECT  
					referencia_produto ,
					referencia_peca    ,
					posicao            ,
					qtde               ,
					produto, 
					peca
				FROM tmp_bosch_lbm_falha;";
		$res  = pg_query($con,$sql);
		$msg_rni = '';
		if (pg_num_rows($res) > 0)
		{
			$msg_rni  = traduz("nao.foram.importadas.os.seguintes.registros.",$con,$cook_idioma)."<br><br>";
			$resultado = pg_fetch_all($res);

			foreach ($resultado as $value)
			{
				$msg_rni .= implode("\t", $value) . "<br>";
			}
		}

		if (!empty($msg_rni))
		{
			Log::envia_email($vet, "BOSCH - ".traduz("registros.nao.importados.lista.basica.",$con,$cook_idioma), $msg_rni);
		}

	}
}
catch (Exception $e) 
{
	$arq_erro = '/tmp/' . $vet['fabrica'] . '/importa-lista_basica-' . $data . '.erro';
	if (file_exists($arq_erro))
	{
		$hoje = date("d/m/Y - H:i:s");
		$msg  = "<div style='background-color: #eeeecc; border: 1px solid #000;'><h2 style='color: #4441FF;'>($hoje) ".traduz("erro.na.importacao.de.lista.basica.de.materiais.",$con,$cook_idioma)."</h2><br>";
		$msg .= file_get_contents($arq_erro);
		$msg .= "</div>";
		Log::envia_email($vet, "BOSCH - ".traduz("erro.na.importacao.de.lista.basica.de.materiais.",$con,$cook_idioma), $msg);
	}
	echo $e->getMessage();
}

?>
