<?php

try
{

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require_once dirname(__FILE__) . '/../funcoes.php';
	include  ('/var/www/assist/www/traducao.php');

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
	$vet['tipo']    = 'produto';
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
	$arq_produto = $origem . "produto.txt";
	$msg_erro = "";

	function msgErro($msg = "")
	{
		$retorno = '';

		if (!empty($msg))
		{
			if (strpos($msg,'insert or update on table "tbl_produto_pais" violates foreign key constraint "pais_fk"') > 0) {
				$rr = preg_replace("/.*([A-Z]{2})\).*/", '$1', substr($msg, strpos($msg, 'Key')));
				$msg      = traduz("erro.o.pais.com.a.sigla.%.nao.existe.em.nosso.banco.de.dados.ou.a.sigla.%.esta.incorreta.favor.corrigir.o.arquivo.e.fazer.o.upload.novamente.",$con,$cook_idioma,$rr,$rr);
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

	if (file_exists($arq_produto) and (filesize($arq_produto) > 0))
	{
		$begin = pg_query($con,"BEGIN TRANSACTION");

        $sql = "CREATE TEMP TABLE tmp_bosch_produto
                (
                    referencia                  text,
                    descricao                   text,
                    linha_nome                  text,
                    familia_nome                text,
                    voltagem                    text,
                    status                      boolean,
                    nome_comercial              text,
                    numero_serie_obrigatorio    boolean,
                    origem                      text,
                    referencia_fabrica          text,
                    pais                        char(2),
                    garantia                    text,
                    descricao_espanhol          text,
                    categoria                   text
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
		if (pg_num_rows($res_pais) > 0) {
			$a_xpais = pg_fetch_all($res_pais);
			foreach ($a_xpais as $pais) {
				$xpais[] = $pais['pais'];
			}
			unset($pais);
			$msg_pais = "";
		} else {
			$msg_erro .= msgErro(traduz("erro.arquivo.nao.processado.favor.entrar.em.contato.com.o.suporte.da.telecontrol.",$con,$cook_idioma));
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$conteudo = file_get_contents($arq_produto);
		$conteudo_array = explode("\n",$conteudo);

		foreach ($conteudo_array as $linha) {
			$linha_vf = explode("\t",$linha);
			$linha_ct = count($linha_vf);

			if (!empty($linha) and $linha_ct >= 6 and $linha_ct <= 15) {
				list($referencia, $descricao, $linha_nome, $familia_nome, $voltagem, $status, $nome_comercial, $numero_serie_obrigatorio, $origem, $referencia_fabrica, $pais, $garantia, $descricao_espanhol,$categoria) = explode("\t",$linha);
                $referencia                 = trim($referencia);
                $descricao                  = trim($descricao);
                $linha_nome                 = trim($linha_nome);
                $familia_nome               = trim($familia_nome);
                $voltagem                   = trim($voltagem);
                $status                     = trim($status);
		$nome_comercial             = trim($nome_comercial);
		$nome_comercial             = substr($nome_comercial,0,20);
                $numero_serie_obrigatorio   = trim($numero_serie_obrigatorio);
                $origem                     = trim($origem);
                $referencia_fabrica         = trim($referencia_fabrica);
                $pais                       = trim($pais);
                $descricao_espanhol         = trim($descricao_espanhol);
                $categoria                  = trim($categoria);
				if (!in_array($pais, $xpais)) {
					$msg_pais .= $pais.",";
				}
				$garantia                 = trim($garantia);

				$string = $referencia . "\t" . $descricao . "\t" . $linha_nome . $familia_nome . "\t" . $voltagem . "\t" . $status . $nome_comercial . "\t" . $numero_serie_obrigatorio . "\t" . $origem . $referencia_fabrica . "\t" . $pais . "\t" . $garantia . "\t" . $descricao_espanhol . "\t" . $categoria . "\n";

				if (strlen($referencia) > 0 and strlen($descricao) > 0 and strlen($origem) > 0 and strlen($pais) > 0) {
					$sql = "INSERT INTO tmp_bosch_produto (
                                referencia,
                                descricao,
                                linha_nome,
                                familia_nome,
                                voltagem,
                                status,
                                nome_comercial,
                                numero_serie_obrigatorio,
                                origem,
                                referencia_fabrica,
                                pais,
                                garantia,
                                descricao_espanhol,
                                categoria
                            ) VALUES (
                                '$referencia',
                                '$descricao',
                                '$linha_nome',
                                '$familia_nome',
                                '$voltagem',
                                '$status',
                                '$nome_comercial',
                                '$numero_serie_obrigatorio',
                                '$origem',
                                '$referencia_fabrica',
                                '$pais',
                                '$garantia',
                                '$descricao_espanhol',
                                '$categoria'
                            )";
					$res = pg_query($con,$sql);
					$msg_erro .= msgErro(pg_last_error());
					if ($msg_erro <> "") {
						Log::log2($vet, $msg_erro);
						throw new Exception($msg_erro);
						break;
					}
				} else {
					$msg_erro = msgErro(traduz("erro.favor.verificar.o.layout.do.arquivo.",$con,$cook_idioma));
					break;
				}
			}

		}

		if (strlen($msg_pais) > 0) {
			$msg_pais     = substr($msg_pais,0,-1);
			$a_msg_pais[0] = $msg_pais;
			$a_msg_pais[1] = $msg_pais;
			$msg_pais     = traduz("erro.o.pais.com.a.sigla.%.nao.existe.em.nosso.banco.de.dados.ou.a.sigla.%.esta.incorreta.favor.corrigir.o.arquivo.e.fazer.o.upload.novamente.",$con,$cook_idioma,$a_msg_pais);
			$msg_erro .= msgErro($msg_pais);
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		if (!empty($msg_erro)) {
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "DELETE FROM tmp_bosch_produto USING
					(
						SELECT referencia, min(oid) AS oid
						FROM tmp_bosch_produto
						GROUP BY referencia having count(*)>1
					) x
				WHERE tmp_bosch_produto.referencia = x.referencia
				AND x.oid <> tmp_bosch_produto.oid";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) {
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$alter_table = pg_query($con,"ALTER TABLE tmp_bosch_produto ADD produto int4");
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) {
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "UPDATE tmp_bosch_produto SET produto =  tbl_produto.produto
				FROM tbl_produto JOIN tbl_linha USING(linha)
				WHERE TRIM(UPPER(tbl_produto.referencia)) = TRIM(UPPER(tmp_bosch_produto.referencia))
				AND fabrica = $fabrica";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) {
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$alter_table = pg_query($con,"ALTER TABLE tmp_bosch_produto ADD linha int4");
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) {
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "UPDATE tmp_bosch_produto SET linha = tbl_linha.linha
				FROM tbl_linha
				WHERE trim(tbl_linha.codigo_linha) = trim(tmp_bosch_produto.linha_nome)
				AND fabrica = $fabrica";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) {
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$alter_table = pg_query($con,"ALTER TABLE tmp_bosch_produto ADD familia int4");
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) {
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "UPDATE tmp_bosch_produto SET familia = tbl_familia.familia
				FROM tbl_familia
				WHERE tbl_familia.codigo_familia = tmp_bosch_produto.familia_nome
				AND fabrica = $fabrica";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) {
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$alter_table = pg_query($con,"ALTER TABLE tmp_bosch_produto ADD cat_id int4");
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) {
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "UPDATE tmp_bosch_produto SET cat_id = tbl_categoria.categoria
				FROM tbl_categoria
				WHERE tbl_categoria.descricao = tmp_bosch_produto.categoria
				AND fabrica = $fabrica";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) {
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "UPDATE tbl_produto SET
					referencia               = tmp_bosch_produto.referencia               ,
					descricao                = tmp_bosch_produto.descricao                ,
					voltagem                 = tmp_bosch_produto.voltagem                 ,
					ativo                    = tmp_bosch_produto.status                   ,
					nome_comercial           = tmp_bosch_produto.nome_comercial           ,
					numero_serie_obrigatorio = tmp_bosch_produto.numero_serie_obrigatorio ,
					origem                   = tmp_bosch_produto.origem                   ,
					linha                    = tmp_bosch_produto.linha   ::numeric        ,
					familia                  = tmp_bosch_produto.familia ::numeric        ,
					referencia_fabrica       = tmp_bosch_produto.referencia_fabrica       ,
					garantia                 = tmp_bosch_produto.garantia ::numeric       ,
					categoria                = tmp_bosch_produto.cat_id ::numeric
				FROM tmp_bosch_produto
				WHERE tmp_bosch_produto.produto = tbl_produto.produto
				AND  tmp_bosch_produto.produto  IS NOT NULL
				AND   tmp_bosch_produto.linha   IS NOT NULL
				AND   tmp_bosch_produto.familia IS NOT NULL
				AND   tmp_bosch_produto.origem  IS NOT NULL";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) {
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "UPDATE tbl_produto_idioma SET
					descricao               = tmp_bosch_produto.descricao_espanhol
				FROM tmp_bosch_produto
				WHERE tmp_bosch_produto.produto = tbl_produto_idioma.produto
				AND  tmp_bosch_produto.produto  IS NOT NULL
				AND tbl_produto_idioma.idioma = 'ES'
				AND LENGTH(tmp_bosch_produto.descricao_espanhol) > 0";
		$res = pg_query($con, $sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) {
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "INSERT INTO tbl_produto (
					referencia                 ,
					descricao                  ,
					voltagem                   ,
					ativo                      ,
					nome_comercial             ,
					numero_serie_obrigatorio   ,
					origem                     ,
					linha                      ,
					familia                    ,
					garantia                   ,
					mao_de_obra                ,
					mao_de_obra_admin          ,
					referencia_fabrica         ,
					off_line                   ,
					categoria
					)
				SELECT
					referencia                 ,
					descricao::char(20)        ,
					voltagem                   ,
					status                     ,
					nome_comercial::char(20)   ,
					numero_serie_obrigatorio   ,
					origem                     ,
					linha   ::numeric          ,
					familia ::numeric          ,
					garantia ::numeric         ,
					0                          ,
					0                          ,
					referencia_fabrica         ,
					FALSE                      ,
					cat_id
				FROM tmp_bosch_produto
				WHERE produto IS NULL
				AND   familia IS NOT NULL
				AND   linha   IS NOT NULL
				AND   origem  IS NOT NULL";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) {
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "UPDATE tmp_bosch_produto SET produto =  tbl_produto.produto
				FROM tbl_produto JOIN tbl_linha USING(linha)
				WHERE TRIM(UPPER(tbl_produto.referencia)) = TRIM(UPPER(tmp_bosch_produto.referencia))
				AND fabrica = $fabrica";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) {
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "INSERT INTO
					tbl_produto_idioma (produto, idioma, descricao)
				SELECT
					tmp_bosch_produto.produto, 'ES', tmp_bosch_produto.descricao_espanhol
				FROM tmp_bosch_produto
				WHERE tmp_bosch_produto.produto IS NOT NULL
				AND tmp_bosch_produto.produto NOT IN (SELECT tbl_produto_idioma.produto FROM tbl_produto_idioma WHERE tbl_produto_idioma.produto = tmp_bosch_produto.produto)
				AND LENGTH(tmp_bosch_produto.descricao_espanhol) > 0";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) {
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$alter_table = pg_query($con,"ALTER TABLE tmp_bosch_produto ADD tem_pais BOOLEAN");
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) {
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "UPDATE tmp_bosch_produto SET tem_pais = TRUE
				FROM tbl_produto_pais
				WHERE tbl_produto_pais.produto = tmp_bosch_produto.produto
				AND   tbl_produto_pais.pais    = tmp_bosch_produto.pais";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) {
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$sql = "INSERT INTO tbl_produto_pais (produto,pais,garantia)
				SELECT produto,pais,garantia::numeric FROM tmp_bosch_produto
				WHERE produto  IS NOT NULL
				AND   pais     IS NOT NULL
				AND   garantia IS NOT NULL
				AND   tem_pais IS NOT TRUE";
		$res = pg_query($con,$sql);
		$msg_erro .= msgErro(pg_last_error());

		if ( !empty($msg_erro) ) {
			$rollback = pg_query($con,"ROLLBACK TRANSACTION");
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
		}

		$commit = pg_query($con,"COMMIT TRANSACTION");
		if (empty($msg_erro)) {
			echo "<script>alert('".traduz("arquivo.importado.com.sucesso.",$con,$cook_idioma)."');</script>";
		}

	}
} catch (Exception $e) {
	$arq_erro = '/tmp/' . $vet['fabrica'] . '/importa-produto-' . $data . '.erro';
	if (file_exists($arq_erro)) {
		$hoje = date("d/m/Y - H:i:s");
		$msg  = "<div style='background-color: #eeeecc; border: 1px solid #000;'><h2 style='color: #4441FF;'>($hoje) ".traduz("erro.na.importacao.de.produtos.",$con,$cook_idioma)."</h2><br>";
		$msg .= file_get_contents($arq_erro);
		$msg .= "</div>";
		Log::envia_email($vet, "BOSCH - ".traduz("erro.na.importacao.de.produtos.",$con,$cook_idioma), $msg);
	}
	echo $e->getMessage();
}
