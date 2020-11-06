<?php

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

function msgErro($msg = ""){
    $retorno = '';

    if (!empty($msg)){
        if (strpos ($msg,"invalid input syntax") > 0 or strpos ($msg,"value too long for type character") > 0) {
            $msg = traduz("erro.favor.verificar.o.layout.do.arquivo.",$con,$cook_idioma);
        }
        if (strpos ($msg,"does not exist") > 0 or strpos ($msg,"syntax error at or near") > 0) {
            $msg = traduz("erro.arquivo.nao.processado.favor.entrar.em.contato.com.o.suporte.da.telecontrol.",$con,$cook_idioma);
        }

        $retorno = "<div class='alert alert-error'><h4>$msg</h4></div>";
        echo $retorno;
    }
}

$fabrica  		= 20;
$dia_mes     	= date('d');
$vet['fabrica'] = 'bosch';
$vet['tipo']    = 'preco';
$vet['log']     = 2;
$vet['dest'][0] = "suporte@telecontrol.com.br";
$vet['dest'][1] = "robson.gastao@br.bosch.com";
$data       	= date('Y-m-d-H');
$arquivos 		= "/tmp";
$origem   		= "/tmp/bosch/";
$arq_preco 		= $origem . "preco.txt";
$msg_erro 		= "";
$admin 			= $argv[1];

$sql_admin = "SELECT email FROM tbl_admin WHERE admin = $admin AND fabrica = $fabrica";
$res_admin = pg_query($con,$sql_admin);
$vet['dest'][2] = pg_result($res_admin,0,"email");

try{
	if (file_exists($arq_preco) and (filesize($arq_preco) > 0)){
	    $sql = "CREATE TEMP TABLE tmp_bosch_preco 
	                (
	                    referencia   varchar(20),
	                    preco        float      ,
	                    ipi          double precision,
	                    tabela       integer
	                )
	            WITH OIDS";
	    pg_query($con,$sql);
	    if (strlen(pg_last_error()) > 0){
	    	throw new Exception(traduz("ocorreu.um.erro.ao.tentar.importar.o.arquivo",$con,$cook_idioma));
	    }

		pg_prepare($con, 'id_tabela', "SELECT tabela FROM tbl_tabela WHERE fabrica = 20 AND sigla_tabela = $1");

        $conteudo 		= file_get_contents($arq_preco);
        $conteudo_array = explode("\n",$conteudo);
        foreach ($conteudo_array as $linha){
            $linha_vf = explode("\t",$linha);
            if (!empty($linha) and count($linha_vf) >= 2 and count($linha_vf) <= 4){
                list($referencia, $preco, $sigla_tabela, $ipi) = explode("\t",$linha);
                $referencia   = trim($referencia);
                $preco        = trim($preco);
                $ipi          = trim($ipi);
                $sigla_tabela = trim($sigla_tabela);

                if (strlen($referencia) > 0 and strlen($preco) > 0 and strlen($sigla_tabela) > 0){
                    $res_tabela = pg_execute($con, 'id_tabela', array($sigla_tabela));
                    $id_tabela = pg_fetch_result($res_tabela, 0, 'tabela');

                    $sql = "INSERT INTO tmp_bosch_preco (referencia, preco, ipi, tabela) VALUES ('$referencia','$preco','$ipi', $id_tabela)";
                    $res = pg_query($con,$sql);
					if (strlen(pg_last_error()) > 0){
						throw new Exception(traduz("erro.na.importacao.de.precos.",$con,$cook_idioma));
                    }                	
                }else{
                	throw new Exception(traduz("erro.favor.verificar.o.layout.do.arquivo.",$con,$cook_idioma));
                }
            }
        }
        pg_query($con,"BEGIN TRANSACTION");

        pg_query($con,"ALTER TABLE tmp_bosch_preco ADD peca int4");
        if (strlen(pg_last_error()) > 0){
        	pg_query($con,"ROLLBACK TRANSACTION");
        	throw new Exception(traduz("erro.na.importacao.de.precos.",$con,$cook_idioma));
        }

        $sql = "DELETE FROM tmp_bosch_preco USING 
                    (
                        SELECT referencia, MIN (oid) AS oid, tabela
                        FROM tmp_bosch_preco 
                        GROUP BY referencia,tabela having count(*)>1
                    ) x 
                WHERE tmp_bosch_preco.referencia = x.referencia
                AND tmp_bosch_preco.tabela = x.tabela
                AND x.oid <> tmp_bosch_preco.oid;";
        pg_query($con,$sql);
        if (strlen(pg_last_error()) > 0){
        	pg_query($con,"ROLLBACK TRANSACTION");
        	throw new Exception(traduz("erro.na.importacao.de.precos.",$con,$cook_idioma));
        }

        $sql = "UPDATE tmp_bosch_preco 
                SET    peca = tbl_peca.peca 
                FROM   tbl_peca 
                WHERE  trim(tbl_peca.referencia) =  trim(tmp_bosch_preco.referencia) 
                AND    tmp_bosch_preco.peca IS NULL";
        pg_query($con,$sql);
        if (strlen(pg_last_error()) > 0){
        	pg_query($con,"ROLLBACK TRANSACTION");
        	throw new Exception(traduz("erro.na.importacao.de.precos.",$con,$cook_idioma));
        }

        pg_query($con,"ALTER TABLE tmp_bosch_preco ADD tem_preco BOOLEAN");
        if (strlen(pg_last_error()) > 0){
        	pg_query($con,"ROLLBACK TRANSACTION");
        	throw new Exception(traduz("erro.na.importacao.de.precos.",$con,$cook_idioma));
        }

        $sql = "UPDATE tmp_bosch_preco 
                SET tem_preco = TRUE 
                FROM tbl_tabela_item 
                WHERE tmp_bosch_preco.peca = tbl_tabela_item.peca 
                AND tbl_tabela_item.tabela = tmp_bosch_preco.tabela";
        $res = pg_query($con,$sql);
        if (strlen(pg_last_error()) > 0){
        	pg_query($con,"ROLLBACK TRANSACTION");
        	throw new Exception(traduz("erro.na.importacao.de.precos.",$con,$cook_idioma));
        }

        $sql = "INSERT INTO tbl_tabela_item (tabela,peca,preco) 
                SELECT DISTINCT tabela::integer,peca::integer,preco::float 
                FROM tmp_bosch_preco 
                WHERE tem_preco IS NOT TRUE
                AND peca IS NOT NULL";
        $res = pg_query($con,$sql);
        if (strlen(pg_last_error()) > 0){
        	pg_query($con,"ROLLBACK TRANSACTION");
        	throw new Exception(traduz("erro.na.importacao.de.precos.",$con,$cook_idioma));
        }

        $sql = "UPDATE tbl_tabela_item 
                SET preco = tmp_bosch_preco.preco::FLOAT
                FROM tmp_bosch_preco
                WHERE tmp_bosch_preco.peca = tbl_tabela_item.peca 
                AND tmp_bosch_preco.preco <> tbl_tabela_item.preco
                AND (tmp_bosch_preco.preco is NOT null AND tmp_bosch_preco.preco>0) 
                AND tbl_tabela_item.tabela=tmp_bosch_preco.tabela
                AND tem_preco IS TRUE";
        $res = pg_query($con,$sql);
        if (strlen(pg_last_error()) > 0){
        	pg_query($con,"ROLLBACK TRANSACTION");
        	throw new Exception(traduz("erro.na.importacao.de.precos.",$con,$cook_idioma));
        }

        $sql = "UPDATE tbl_peca 
	            SET ipi = tmp_bosch_preco.ipi
	            FROM tmp_bosch_preco
	            WHERE tmp_bosch_preco.peca = tbl_peca.peca 
	            AND tbl_peca.fabrica = $fabrica";
        $res = pg_query($con,$sql);
        if (strlen(pg_last_error()) > 0){
        	pg_query($con,"ROLLBACK TRANSACTION");
        	throw new Exception(traduz("erro.na.importacao.de.precos.",$con,$cook_idioma));
        }

        pg_query($con,"COMMIT TRANSACTION");
        rename($origem . "preco.txt", "/tmp/bosch/bkp-entrada/importa-preco-" . $data . ".txt");

        $sql = "SELECT  
                    referencia ,
                    preco      ,
                    ipi
                FROM tmp_bosch_preco
                WHERE peca is null"; 
        $res = pg_query($con,$sql);
        if (pg_num_rows($res) > 0){
            $msg_rni  = traduz("nao.foram.as.seguintes.pecas.",$con, $cook_idioma)."<br>";
            $resultado = pg_fetch_all($res);

            foreach ($resultado as $value){
                $msg_rni .= implode("\t", $value) . "<br>";
            }
            Log::envia_email($vet, "BOSCH - ".traduz("pecas.nao.encontradas.",$con, $cook_idioma), $msg_rni);
        }
	}else{
		throw new Exception(traduz("erro.favor.verificar.o.layout.do.arquivo.", $con, $cook_idioma));
	}
}catch (Exception $e){
	Log::log2($vet, date('d/m/Y h:m:s').' - '.$e->getMessage());
	msgErro($e->getMessage());
}
