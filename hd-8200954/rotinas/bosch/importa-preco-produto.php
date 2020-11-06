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
	$vet['tipo']    = 'preco-produto';
	$admin = $argv[1];
	$sql_admin = "SELECT email FROM tbl_admin WHERE admin = $admin AND fabrica = $fabrica";
	$res_admin = pg_query($con,$sql_admin);
	$vet['dest'][0] = "anderson.luciano@telecontrol.com.br";
	// $vet['dest'][0] = "helpdesk@telecontrol.com.br";
	// $vet['dest'][1] = "suporte@telecontrol.com.br";
	// $vet['dest'][2] = "robson.gastao@br.bosch.com";
	if (pg_num_rows($res_admin) > 0)
	{
		$email_admin = pg_result($res_admin,0,"email");
		//$vet['dest'][5] = "$email_admin";
	}
	else
	{ 
		echo "<script>alert('".traduz("seu.usuario.nao.possui.e.mail.cadastrado.por.favor.cadastre.um.e.mail",$con,$cook_idioma)."');</script>";
	}

	$vet['log']     = 2;

	$data       = date('Y-m-d-H');
	$arquivos = "/tmp";
	$origem   = "/tmp/bosch/";
	$arq_preco = $origem . "preco-produto.txt";
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
	
	if (file_exists($arq_preco) and (filesize($arq_preco) > 0))
	{
				
		$sql = "DROP TABLE IF EXISTS tmp_bosch_preco_produto;
				CREATE TABLE tmp_bosch_preco_produto 
					(
						produto_referencia   		varchar(10),
						preco        	double precision      ,
						pais         	varchar(2),
						garantia		integer,
						produto			integer
					)";

		$res = pg_query($con,$sql);
		if (pg_last_error())			
		{	
			Log::log2($vet, pg_last_error());
			throw new Exception(pg_last_error());
		}

		$conteudo = file_get_contents($arq_preco);
		$conteudo_array = explode("\n",$conteudo);

		foreach ($conteudo_array as $linha)
		{			
			$linha_vf = explode("\t",$linha);			
			$linha_ct = count($linha_vf);
			if (!empty($linha) and $linha_ct >= 3 and $linha_ct <= 4)
			{
				list($produto,$pais,$garantia,$preco) = explode("\t",$linha);
				
				$produto = trim($produto);
				$pais = trim($pais);
				$garantia = trim($garantia);
				$preco = str_replace(',', '.', trim($preco)) ;

				//$string = $referencia . "\t" . $preco . "\t" . $ipi . "\n";
				if (strlen($produto) > 0 and strlen($preco) > 0)
				{
					$sql = "INSERT INTO tmp_bosch_preco_produto (produto_referencia, preco, pais, garantia) VALUES ('$produto',$preco,'$pais',$garantia)";
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

		$sql = "UPDATE tmp_bosch_preco_produto 
				SET    produto = tbl_produto.produto 
				FROM   tbl_produto
				WHERE  tbl_produto.referencia =  trim(tmp_bosch_preco_produto.produto_referencia) 
				AND    tmp_bosch_preco_produto.produto IS NULL";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());
		if ($msg_erro <> "")
		{	
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
			break;
		}
				
		$sql = "UPDATE tbl_produto_pais  set valor = tmp_bosch_preco_produto.preco, 				
				garantia = tmp_bosch_preco_produto.garantia 
				FROM tmp_bosch_preco_produto
				WHERE tmp_bosch_preco_produto.produto = tbl_produto_pais.produto
				AND tmp_bosch_preco_produto.pais = tbl_produto_pais.pais
				AND tmp_bosch_preco_produto.produto IS NOT NULL";								
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());
		if ($msg_erro <> "")
		{	
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
			break;
		}				

		$sql = "DELETE FROM tmp_bosch_preco_produto where produto in(SELECT x.produto from tbl_produto_pais y join tmp_bosch_preco_produto x on x.produto = y.produto and x.pais = y.pais)";										
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());
		if ($msg_erro <> "")
		{	
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
			break;
		}				

		$commit = pg_query($con,"COMMIT TRANSACTION");

		if (empty($msg_erro))
		{
			rename($origem . "preco.txt", "/tmp/bosch/bkp-entrada/importa-preco-" . $data . ".txt");
			echo "<script>alert('".traduz("arquivo.importado.com.sucesso.",$con,$cook_idioma)."');</script>";
		}

		$sql = "SELECT  
					produto_referencia 
				FROM tmp_bosch_preco_produto
				WHERE produto is null"; 
		$res = pg_query($con,$sql);
		if (pg_num_rows($res) > 0)
		{
			$msg_rni  = "Não foram encontrados os seguintes produtos<br>";
			$resultado = pg_fetch_all($res);

			foreach ($resultado as $value)
			{
				$msg_rni .= implode("\t", $value) . "<br>";
			}
		}

		if (!empty($msg_rni))
		{	

			Log::envia_email($vet, "BOSCH - produtos nao encontrados.", $msg_rni);			
		}

		$msg_sem_preco = "";
		$sql = "SELECT  
					produto_referencia 
				FROM tmp_bosch_preco_produto
				WHERE preco is null"; 
		$res = pg_query($con,$sql);
		if (pg_num_rows($res) > 0)
		{
			$$msg_sem_preco  = "Os produtos a seguir estão sem preço<br>";
			$resultado = pg_fetch_all($res);

			foreach ($resultado as $value)
			{
				$msg_sem_preco .= implode("\t", $value) . "<br>";
			}
		}		
		if (!empty($$msg_sem_preco))
		{	

			Log::envia_email($vet, "BOSCH - produtos sem preço.", $msg_rni);
		}

		$sql = "DELETE FROM tmp_bosch_preco_produto
				WHERE preco IS NULL OR produto IS NULL"; 				
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());
		if ($msg_erro <> "")
		{	
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
			break;
		}				

		$sql = "INSERT INTO tbl_produto_pais (produto,pais,garantia,valor) 
				SELECT DISTINCT produto,pais,garantia,preco
				FROM tmp_bosch_preco_produto 
				WHERE produto IS NOT NULL
				AND pais IS NOT NULL 
				AND  preco IS NOT NULL";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());
		if ($msg_erro <> "")
		{	
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
			break;
		}				
	}
}
catch (Exception $e)
{
	$arq_erro = '/tmp/' . $vet['fabrica'] . '/importa-preco-' . $data . '.erro';
	if (file_exists($arq_erro))
	{
		$hoje = date("d/m/Y - H:i:s");
		$msg  = "<div style='background-color: #eeeecc; border: 1px solid #000;'><h2 style='color: #4441FF;'>($hoje) ".traduz("erro.na.importacao.de.precos.",$con,$cook_idioma)."</h2><br>";
		$msg .= file_get_contents($arq_erro);
		$msg .= "</div>";
		Log::envia_email($vet, "BOSCH - ".traduz("erro.na.importacao.de.precos.",$con,$cook_idioma), $msg);
	}
	echo $e->getMessage();
}

?>
