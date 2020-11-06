<?php
// Ambiente de execução.
if (!defined('SERVER_ENV'))
    define ('SERVER_ENV', (($_serverEnvironment == 'development') ? 'DEVELOPMENT' : 'PRODUCTION'));


if (!function_exists('mt_substr')) {
    include_once __DIR__ . '/mt_substr.inc.php';
}

// FUNÇÕES CABEÇALHO NEW //
if (!function_exists("codigo_visitar_loja")) {
    function codigo_visitar_loja($login, $is_lu=true, $fabrica='') { // BEGIN function codigo_visitar_loja
        $lu = ($is_lu) ? "1" : "0";
        $cp_len     = dechex(strlen($login));   // Comprimento do código_posto / login_unico, em hexa (até 15 chars)
        $ctrl_pos   = str_pad(4 + $cp_len,2, "0",STR_PAD_LEFT); // Posição do código de controle, 2 dígitos (até 255 chars... suficiente)
        $fabrica    = str_pad($fabrica,   2, "0",STR_PAD_LEFT);// Código da fábrica. '00' se é login_unico
        $controle   = ((date('d')*24) + date('h')) * 3600;    // Pega apenas dia do mês e hora, para
                                                            // minimizar divergências se passarem vários minutos desde
                                                            // que carregou a página até que clica em visitar loja...
        return $lu . $cp_len . $ctrl_pos . $fabrica . $login . $controle;
    } // END function codigo_visitar_loja
}

if (!function_exists('TempoExec')) {
    function TempoExec($pagina, $sql, $time_start, $time_end){
        $time = $time_end - $time_start;
        $time = str_replace ('.',',',$time);
        $sql  = str_replace ('\t',' ',$sql);
        $fp = fopen ("/home/telecontrol/tmp/postgres.log","a");
        fputs ($fp,$pagina);
        fputs ($fp,"#");
        fputs ($fp,$sql);
        fputs ($fp,"#");
        fputs ($fp,$time);
        fputs ($fp,"\n");
        fclose ($fp);
    }
}
// FIM FUNÇÕES CABEÇALHO NEW //

if(!function_exists('array_column')) {
	function array_column(array $array, $columnKey, $indexKey = null) {
		if (is_null($indexKey))
			return array_map(
				function($element) use($columnKey) {
					return $element[$columnKey];
				},
				$array
			);

		$result = array();
		foreach ($array as $subArray) {
			if (is_array($subArray)) {
				if (array_key_exists($indexKey, $subArray)) {
					if (is_null($columnKey)) {
						$result[$subArray[$indexKey]] = $subArray;
					} elseif (array_key_exists($columnKey, $subArray)) {
						$result[$subArray[$indexKey]] = $subArray[$columnKey];
					}
				}
			}
		}
		return $result;
	}
}

//**

function getCidadeDoPosto($posto){
	global $con;

	$sql = "SELECT cidade FROM tbl_posto WHERE posto = {$posto}";
	$pgResource = pg_query($con, $sql);
	$cidadePosto = pg_fetch_assoc($pgResource)['cidade'];

	return $cidadePosto;	
}

function getEstadoDoPosto($posto){
	global $con;

	$sql = "SELECT estado FROM tbl_posto WHERE posto = {$posto}";
	$pgResource = pg_query($con, $sql);
	$estadoPosto = pg_fetch_assoc($pgResource)['estado'];
	
	if( $estadoPosto != 'EX' ){
		return $estadoPosto;
	}

	$sql = "SELECT parametros_adicionais::json->>'estado' as posto_estado FROM tbl_posto_fabrica WHERE posto = {$posto}";
	$pgResource = pg_query($con, $sql);
	$estadoPosto = pg_fetch_assoc($pgResource)['posto_estado'];

	$sql = "SELECT estado FROM tbl_estado_exterior WHERE upper(nome) = upper('$estadoPosto')";
	$pgResource = pg_query($con, $sql);

	$estadoPosto = pg_fetch_assoc($pgResource)['estado'];	

	return $estadoPosto;
}

function getEstadoDoConsumidor($os) {
    global $con;

    $sql = "SELECT consumidor_estado FROM tbl_os WHERE os = {$os}";
    $pgResource = pg_query($con, $sql);
    $estadoConsumidor = pg_fetch_assoc($pgResource)['consumidor_estado'];

    if( $estadoConsumidor != 'EX' ){
        return $estadoConsumidor;
    }

    $sql = "SELECT campos_adicionais::json->>'estado' AS consumidor_estado FROM tbl_os_campo_extra WHERE os = {$os}";
    $pgResource = pg_query($con, $sql);
    $estadoConsumidor = pg_fetch_assoc($pgResource)['consumidor_estado'];

    return $estadoConsumidor;
}

function getListaDeEstadosDoPais($pais = 'BR'){
    global $con;

    $sqlBrasil = "SELECT estado AS sigla, fn_retira_especiais(nome) AS descricao FROM tbl_estado WHERE pais = 'BR' AND visivel = 't' ORDER BY descricao ASC";
    $sqlOutrosPaises = "SELECT estado AS sigla, fn_retira_especiais(nome) AS descricao FROM tbl_estado_exterior WHERE pais = '{$pais}' AND visivel = 't' ORDER BY descricao ASC";

    $pgResource = ($pais === 'BR') ? pg_query($con, $sqlBrasil) : pg_query($con, $sqlOutrosPaises);

    return pg_fetch_all($pgResource);
}

function getCidadesDoEstado($pais, $siglaDoEstado){
       global $con;

       if($pais <> 'BR') {
               $sql = "SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(pais) = UPPER('{$pais}') AND UPPER(estado_exterior) = UPPER('{$siglaDoEstado}') ORDER BY nome ASC";
       }else{
		$sql = "
			SELECT DISTINCT * FROM (
                        	SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('{$siglaDoEstado}') AND pais = 'BR' AND cod_distrito NOTNULL
                                UNION (
                                SELECT UPPER(fn_retira_especiais(tbl_ibge.cidade)) AS cidade FROM tbl_ibge JOIN tbl_cidade USING(cod_ibge) WHERE UPPER(tbl_ibge.estado) = UPPER('{$siglaDoEstado}') AND tbl_cidade.cod_distrito IS NULL AND tbl_cidade.pais = '{$pais}'
                                )
                        ) AS cidade
                        ORDER BY cidade ASC;
		";
       }
       $pgResource = pg_query($con, $sql);
        
       $retorno = [];
       if (pg_num_rows($pgResource) > 0) {
          $retorno = pg_fetch_all($pgResource);
       }

       return $retorno;

}

function getPaisDaFabrica($fabrica) {
    global $con;

    $pgResource = pg_query($con, "SELECT parametros_adicionais::json->>'pais' as pais FROM tbl_fabrica WHERE fabrica = {$fabrica}");
    return pg_fetch_assoc($pgResource)['pais'] ?? 'BR';
}

// ##

if (!function_exists('verifica_checklist_tipo_atendimento')) {
	function verifica_checklist_tipo_atendimento($tipo_at) {
		global $con, $login_fabrica;

		$sql = "SELECT checklist_fabrica
				FROM tbl_checklist_fabrica
				WHERE fabrica = {$login_fabrica}
				AND tipo_atendimento = {$tipo_at}
				LIMIT 1";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			return true;
		}
		return false;
	}
}

// Verifica se tem posto bloqueado Suggar
if (!function_exists('verifica_posto_bloqueado_os')) {
    function verifica_posto_bloqueado_os($posto) {
        global $con, $login_fabrica;

        if (!empty($posto)) {
            $sql = "SELECT posto 
                    FROM tbl_posto_bloqueio
                    WHERE posto = {$posto}
                    AND fabrica = {$login_fabrica}
                    AND os IS TRUE
                    LIMIT 1";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                return true;
            }
            return false;
        } else {
            return false;
        }
    }
}

if (!function_exists('verifica_msg_os_7_dias')) {
    function verifica_msg_os_7_dias($os, $msg) {
        global $con, $login_fabrica;

        if (!empty($os) && !empty($msg)) {
            $sql = "SELECT campos_adicionais 
                    FROM tbl_os_campo_extra
                    WHERE fabrica = {$login_fabrica}
                    AND os = $os";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $campos_adicionais = json_decode(pg_fetch_result($res, 0, 'campos_adicionais'), true);

                if ($campos_adicionais['mensagem_os'] == $msg && $campos_adicionais['os_7_dias_sem_peca']) {
                    return true;
                }
            }
        
            return false;
        } else {
            return false;
        }
    }
}

if (!function_exists('retrona_os_bloqueada_interacao_posto')) {
    function retrona_os_bloqueada_interacao_posto($posto) {
        global $con, $login_fabrica;

        if (!empty($posto)) {
            $sql = "SELECT distinct tbl_os_interacao.os, tbl_os.posto, (select case when admin notnull and current_date > data + interval '3 days' then 'sim' else 'nao' end as bloqueia from tbl_os_interacao h where h.os = tbl_os.os order by data desc limit 1) as bloqueia_os
                  FROM tbl_os_interacao
                  JOIN tbl_os USING(os)
                  WHERE tbl_os_interacao.fabrica= $login_fabrica
                  AND tbl_os_interacao.admin IS NOT NULL
                  AND tbl_os_interacao.interno is false
                  AND tbl_os_interacao.exigir_resposta is true
                  AND tbl_os.excluida is not true
                  AND tbl_os_interacao.data > '2015-01-01 00:00:00'
									AND tbl_os_interacao.data < current_date - interval '3 days'
									AND tbl_os.fabrica = $login_fabrica
                  AND tbl_os.data_fechamento IS NULL 
                  AND tbl_os.posto = $posto";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $dados = pg_fetch_all($res);
                $array_os = [];

                foreach ($dados as $key => $value) {
                    if ($value['bloqueia_os'] == 'nao') {
                        continue;
                    }

                    $array_os[] = "<a href='os_press.php?os=".$value['os']."' target='_new'>".$value['os']."</a>"; 
                }
                
                return $array_os;
            }

            return false;
        } else {
            return false;
        }
    }
}

if (!function_exists('verifica_checklist_lancado')) {
	function verifica_checklist_lancado($os) {
		global $con, $login_fabrica;

		$sql = "SELECT checklist_fabrica
			FROM tbl_os_defeito_reclamado_constatado
			WHERE fabrica = {$login_fabrica}
			AND os = {$os}
			AND checklist_fabrica NOTNULL";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) == 0) {
			return true;
		}
		return false;
	}
}

if($login_fabrica == 42){
    function getLinkNF($nf, $cnpj){

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "http://api.lupeon.com.br/ws/sla/postpesquisa",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => "{\n\t\"DestinatarioCNPJ\":\"$cnpj\",\n\t\"NFeNumero\":\"$nf\"\n}",
          CURLOPT_HTTPHEADER => array(
            "authorization: Basic QVBJTWFraXRhMjo0MjdtYWtpdGE=",
            "content-type: application/json",
            "origin: http://makita.lupeon.com.br",
            "referer: http://makita.lupeon.com.br/"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $response = json_decode($response, true);         

        if($response["Status"]== 'Success'){
            $NFeId = $response['Data'][0]['NFeId'];
            return $NFeId; 
        }
        curl_close($curl);

        if ($err) {
          echo "cURL Error #:" . $err;
        } else {
          echo $response;
        }
    }  

    function getImpostosPecas($filialImp, $cnpjImp, $condicaoImp, $itensArray, $tipo_pedido = null) {
        global $_serverEnvironment;

        $itensImp = json_decode($itensImp, true);

	#$urlImposto = "http://201.23.94.187:8079/rest/wspD2vDoS/"; //URL TESTE
        $keyChv     = "Mak1t@";

        if ($_serverEnvironment == "production") {
            // ver url e senha produção
            $urlImposto = "http://200.219.240.146:8075/rest//wspD2vDoS/";
        }

        $bodyPecas = [];

        $bodyPecas["keyChv"] = $keyChv;
        /*$bodyPecas["cabPed"] = [
                                  "empresaped":"01",
                                  "filialped":"01",
                                  "orcamento":"123456",
                                  "cliente":"17194994000399",
                                  "transp":"RETIRA",
                                  "condpagto":"003",
                                  "pedgaranti":""
                               ]*/
        //$cnpjImp = "17194994000399"; // cliente para teste

        $bodyPecas["cabPed"] = [
                                  "empresaped"=>$filialImp,
                                  "cliente"=>$cnpjImp,
                                  "condpagto"=>$condicaoImp
                               ];

        if (!empty($tipo_pedido)) {
          if ($tipo_pedido == 187 || $tipo_pedido == 135) {
            $bodyPecas["cabPed"]["tipocli"] = "F";
          } else if ($tipo_pedido == 136) {
            $bodyPecas["cabPed"]["tipocli"] = "R";
          }
        }

        $bodyPecas["ItemPed"] = $itensArray;

        $curl = curl_init();

        curl_setopt_array($curl, array(
          
          CURLOPT_URL => $urlImposto,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 300,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => json_encode($bodyPecas),
          CURLOPT_HTTPHEADER => array(
            "Accept: */*",
            "Accept-Encoding: gzip, deflate",
            "Cache-Control: no-cache",
            "Connection: keep-alive",
            "Content-Type: application/json",
            "Host: 201.23.94.187:8079",
            "Postman-Token: a0ca051e-9e3f-4dfd-8111-04b43944f072,d37b8566-99a3-423e-b918-5f2ef7fcbede",
            "User-Agent: PostmanRuntime/7.16.3",
            "cache-control: no-cache"
          ),
        ));


/*"Access-Application-Key: {$this->_appKey}",
"Access-Env: {$this->_accessEnv}",
"Cache-Control: no-cache",
"Content-Type: application/json"*/


        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          return  json_encode(["erro"=>$err]);
        } else {
          return $response;
        }
    }
}

function VerificaVideoTela($tela){
    global $con, $login_fabrica;

    //Paulo pediu para buscar por strpos
    $sql = "SELECT programa, video, descricao 
              FROM tbl_comunicado 
             WHERE fabrica = $login_fabrica AND tipo = 'video_explicativos' AND ativo = 't' AND strpos(programa,'".$tela."') > 0 ";

    $pgResource = pg_query($con, $sql);

    $listaDeVideos = pg_fetch_all($pgResource);

    // echo '<pre>';
    // exit(print_r($listaDeVideos));

    if( empty($listaDeVideos) ){
        return;
    }

    // $link_video = pg_fetch_result($reslinkvideo, 0, 'video');  
    $htmlVideo = "<div id='videos-explicativos' style='display:flex; justify-content:center; flex-direction: column; cursor:pointer; position: relative'>
                    <div style='align-self: center;'> 
                        <img width='40' style='display: block' src='imagens/videos_m.png' />
                    </div>
                    <div style='align-self: center;'>
                        Videos Explicativos da Tela
                    </div>
                    <div id='container-videos-explicativos' style='box-shadow:3px 6px 6px 0px rgba(0,0,0,0.3); background-color:#F0F0F0; z-index:100; padding:5px 5px; display:none; position:absolute; top: 63px; right:0px; width: 100%; text-align: center';>
                        <table border='1' style='border-collapse: collapse; width: 100%'>
                                <tr>
                                    <th>  <strong> Videos Explicativos </strong> </th>
                                </tr>
                                {videos}
                        </table>
                    </div>
                </div>";

    $videos = '';
    foreach( $listaDeVideos as $videoInfo ){
        // Verifica se existe um título no vídeo
        // Necessário para os vídeos antigos sem título
        $titulo = !empty($videoInfo['descricao']) ? $videoInfo['descricao'] : 'Vídeo sem título';

        // Verifica se no link existe o 'http' ou 'https'
        // Sem o 'http' ou 'https' vira um link relativo e é concatenado no final do link atual
        $http  = strpos($videoInfo['video'], 'https://');
        $https = strpos($videoInfo['video'], 'http://');
        
        if( $http === false AND $https === false ){
            $videoInfo['video'] = "http://" . $videoInfo['video'];
        }

        $videos .= "<tr>
                        <td> <a href=\"{$videoInfo['video']}\" target='_blank' style='display: block; font-size: 13px; padding: 5px 3px;'> {$titulo} </a> </td>
                   </tr>";
    }

    $htmlVideo = str_replace('{videos}', $videos, $htmlVideo);

    $script = "<script>
                $(function(){
                    $('#videos-explicativos').click(function(){
                        var containerVideos = document.getElementById('container-videos-explicativos');
                        if( containerVideos.style.display == 'block' ){
                            containerVideos.style.display = 'none';
                        }else if( containerVideos.style.display == 'none' ){
                            containerVideos.style.display = 'block';
                        }
                    });
                });
              </script>";

    return $htmlVideo . $script;
}



function estoque_abastecido_distrib() {
    global $con;

    $sqlAguardEstoque = "SELECT os 
						 FROM tbl_os
						JOIN tbl_fabrica using(fabrica)
                         WHERE status_checkpoint = 35
                         AND tbl_fabrica.parametros_adicionais ~'telecontrol_distrib' and tbl_os.excluida is not true and ativo_fabrica";
    $resAguardEstoque = pg_query($con, $sqlAguardEstoque);

    while ($linha = pg_fetch_array($resAguardEstoque)) {
        $os = $linha['os'];

        $sqlVerificaEstoque = "SELECT tbl_os_item.peca
                               FROM tbl_os_produto
                               JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                               JOIN tbl_posto_estoque ON tbl_posto_estoque.posto = 4311 
                               AND tbl_posto_estoque.peca = tbl_os_item.peca
                               JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado 
                               AND tbl_servico_realizado.troca_de_peca IS TRUE
                               WHERE tbl_posto_estoque.qtde < tbl_os_item.qtde
                               AND tbl_os_produto.os = {$os}
                               ";
        $resVerificaEstoque = pg_query($con, $sqlVerificaEstoque);
        
        if (pg_num_rows($resVerificaEstoque) == 0) {
            atualiza_status_checkpoint($os, 'Aguardando Peças');
        }
    }
}

function os_em_intervencao($os) {
    global $con, $login_fabrica, $novaTelaOs;

    if (isset($novaTelaOs)) {

        $sql = "SELECT auditoria_os
                FROM tbl_auditoria_os
                WHERE liberada IS NULL
                AND cancelada IS NULL
                AND reprovada IS NULL
                AND os = {$os}";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            return true;
        }

    } else {
        $sql = "SELECT interv_produto_critico.os
                FROM (
                        SELECT
                        ultima_produto_critico.os,
                        (
                            SELECT status_os
                            FROM tbl_os_status
                            WHERE tbl_os_status.os = ultima_produto_critico.os
                            AND   tbl_os_status.fabrica_status = $login_fabrica
                            AND   status_os IN (13,19,62,64)
                            AND   tbl_os_status.os = {$os}
                            ORDER BY os_status DESC LIMIT 1
                        ) AS ultimo_produto_critico_status

                        FROM (
                            SELECT DISTINCT os
                            FROM tbl_os_status
                            WHERE tbl_os_status.fabrica_status = $login_fabrica
                            AND   status_os IN (13,19,62,64)
                            AND   tbl_os_status.os = {$os}
                        ) ultima_produto_critico
                    ) interv_produto_critico
                WHERE interv_produto_critico.ultimo_produto_critico_status IN (13,62);
        ";
        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {
            return true;
        }

        $sql = "SELECT interv_produto_critico.os
                FROM (
                        SELECT
                        ultima_produto_critico.os,
                        (
                            SELECT status_os
                            FROM tbl_os_status
                            WHERE tbl_os_status.os = ultima_produto_critico.os
                            AND   tbl_os_status.fabrica_status = $login_fabrica
                            AND   status_os IN (118,185,187)
                            AND   tbl_os_status.os = {$os}
                            ORDER BY os_status DESC LIMIT 1
                        ) AS ultimo_produto_critico_status

                        FROM (
                            SELECT DISTINCT os
                            FROM tbl_os_status
                            WHERE tbl_os_status.fabrica_status = $login_fabrica
                            AND   status_os IN (118,185,187)
                            AND   tbl_os_status.os = {$os}
                        ) ultima_produto_critico
                    ) interv_produto_critico
                    WHERE interv_produto_critico.ultimo_produto_critico_status IN (118,185);
        ";
        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {
            return true;
        }
    }

    return false;

}

function data_corte_termo($os) {
    global $con, $login_fabrica;
    
    $data_corte = '2018-11-26';
    
    
    if ($login_fabrica == 123) {
		# $data_corte = '2019-04-01';
		return false;
    }

    // Removido Termo Eihell 6796695 - Para habilitar novamente adicionar uma data de corte
    if ($login_fabrica == 160) {
        return false;
    }
    
    $sql_data_digitacao = "SELECT data_digitacao::DATE FROM tbl_os WHERE os = {$os} AND fabrica = {$login_fabrica} AND posto <> 20682 ";
    $res_data_digitacao = pg_query($con, $sql_data_digitacao);

    if(pg_num_rows($res_data_digitacao)>0){     
        $dt_digitacao = pg_fetch_result($res_data_digitacao, 0, 'data_digitacao');

        $oDataCorte = new DateTime($data_corte);
        $oDataDigitacao = new DateTime($dt_digitacao);
        if ($oDataDigitacao >= $oDataCorte) {
            return true;
        } else {
            return false;
        }
    }else{
        return false;
    } 
}

//funcao para converter data
// mandar d/m/y H:i:s
function convertDataHora($data){
    $data_contas_formatar = date_create_from_format('d/m/Y H:i:s', $data);
    $data_contas_formatar = date_format($data_contas_formatar, 'Y-m-d H:i:s');
    return $data_contas_formatar;
}




// Função tem como base uma data de corte e verifica se a data informada é superior a data de corte
function verifica_data_corte($data_corte = null, $data_validar = null) {
    global $con, $login_fabrica;
    
    if (empty($data_corte) || empty($data_validar)) {
        return false;
    }
    
    $dt_validar = date_create_from_format('d/m/Y', $data_validar);
    $dt_validar = date_format($dt_validar, 'Y-m-d');

    $dt_corte = date_create_from_format('d/m/Y', $data_corte);
    $dt_corte = date_format($dt_corte, 'Y-m-d');
    
    if ($dt_validar >= $dt_corte) {
        return true;
    } else {
        return false;
    }
}

function observacao_auditoria($os) {
    global $con, $login_fabrica;
    
    $sql_obs = "SELECT observacao FROM tbl_auditoria_os WHERE os = $os";
    $res_obs = pg_query($con, $sql_obs);

    if(pg_num_rows($res_obs) > 0) {     
        $obs = pg_fetch_result($res_obs, 0, 'observacao');
        return $obs;
    }else{
        return false;
    } 
}

function get_ultimo_status_os($os) {
    global $con;

    if (empty($os)) {
        return false;
    }

    $sql = "SELECT tbl_status_checkpoint.descricao
                      FROM tbl_os_historico_checkpoint
                      JOIN tbl_status_checkpoint ON 
                      tbl_os_historico_checkpoint.status_checkpoint = tbl_status_checkpoint.status_checkpoint
                      WHERE (
                        SELECT tbl_os.status_checkpoint
                        FROM tbl_os
                        WHERE tbl_os.os = {$os}
                        LIMIT 1
                      ) <> tbl_os_historico_checkpoint.status_checkpoint
                      AND tbl_os_historico_checkpoint.os = {$os}
                      ORDER BY tbl_os_historico_checkpoint.os_historico_checkpoint DESC
                      LIMIT 1
                      ";
      $res = pg_query($con, $sql);

      if (pg_num_rows($res) > 0) {
        return pg_fetch_result($res, 0, 'descricao');
      } else {
        return false;
      }
}

function tem_pedido_os($os) {
    global $con, $login_fabrica;

    if (empty($os)) {
        return false;
    }

    $pode_excluir = [];

    $sql = "    SELECT DISTINCT(tbl_pedido_item.pedido_item), 
                       tbl_pedido_item.qtde_cancelada, 
                       tbl_pedido_item.qtde_faturada 
                FROM tbl_os 
                JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os 
                JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto 
                JOIN tbl_pedido_item ON tbl_os_item.pedido = tbl_pedido_item.pedido 
                WHERE tbl_os.os = $os
                AND tbl_os.fabrica = $login_fabrica ";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        for ($i=0; $i < pg_num_rows($res); $i++) { 
            $qtde_cancelada = pg_fetch_result($res, $i, 'qtde_cancelada'); 
            $qtde_faturada  = pg_fetch_result($res, $i, 'qtde_faturada');

            if (($qtde_faturada > 0) || ($qtde_cancelada == 0 && $qtde_faturada == 0)) {
                $pode_excluir[] = 'nao';
            }
        }
    } else {
        return true;
    }

    if (in_array('nao', $pode_excluir)) {
        return false;
    } else {
        return true;
    }
}

function valida_produto_pacific_lennox($referencia) {
    global $con;

    if (!empty($referencia)) {

        //verifica qual das duas fábricas atendem o produto
        $sqlVerificaFabricas = "SELECT referencia, 
                                       fabrica_i,
                                       parametros_adicionais,
                                       produto
                                FROM tbl_produto 
                                WHERE UPPER(referencia)  = UPPER('{$referencia}')
                                AND fabrica_i IN (11,172)
                                AND ativo";
        $resVerificaFabricas = pg_query($con, $sqlVerificaFabricas);

        if (pg_num_rows($resVerificaFabricas) > 0) {

            $contador_interno_obrigatorio = 0;
            $contador_cod_interno         = 0;
            while ($dadosVerifica = pg_fetch_object($resVerificaFabricas)) {

                $arrAdicionais = json_decode($dadosVerifica->parametros_adicionais, true);

                if ($arrAdicionais["codigo_interno_obrigatorio"] == "t") {
                    $contador_interno_obrigatorio++;
                }

                if (!empty($arrAdicionais["codigo_interno"])) {
                    $contador_cod_interno++;
                }

                $dados["fabrica"][$dadosVerifica->fabrica_i] = [
                    "referencia"                 => $dadosVerifica->referencia,
                    "produto"                    => $dadosVerifica->produto,
                    "codigo_interno"             => $arrAdicionais["codigo_interno"],
                    "codigo_interno_obrigatorio" => $arrAdicionais["codigo_interno_obrigatorio"]
                ]; 

            }

            $dados["total_fabricas_cod_obrigatorio"] = $contador_interno_obrigatorio;
            $dados["total_cod_interno"]              = $contador_cod_interno;

        } else {

            $dados = ["msg_erro" => traduz("Nenhum produto encontrado com as referências informadas")];

        }

    } else {

        $dados = ["msg_erro" => traduz("Referência não informada")];

    }

    return $dados;

}

// Função para deixar a localização das peças iguais Lenoxx
function atualiza_localizacao_lenoxx($peca = "", $localizacao = "", $login_posto_estoque = "") {
    global $con;

    $msg_erro = "";

    if (empty($peca) || empty($localizacao)) {
        return false;
    }

    if (empty($login_posto_estoque)) {
        $login_posto_estoque = 4311;
    }

    $localizacao = str_replace("'", "", $localizacao);
    $localizacao = trim($localizacao);

       
    $sql_fab_peca = "SELECT fabrica, referencia FROM tbl_peca WHERE peca = $peca";
    $res_fab_peca = pg_query($con, $sql_fab_peca);
    $fab_peca = pg_fetch_result($res_fab_peca, 0, 'fabrica');
    $ref_peca = pg_fetch_result($res_fab_peca, 0, 'referencia');

    $fab = ($fab_peca == 11) ? 172 : 11;
            
    $sql_pecas = "SELECT peca FROM tbl_peca WHERE referencia = '$ref_peca' AND fabrica = $fab";
    $res_pecas = pg_query($con, $sql_pecas);
    
    if (pg_num_rows($res_pecas) > 0) {
        $peca_outra_fab = pg_fetch_result($res_pecas, 0, 'peca');
        $sql_tem_localizacao = "SELECT peca FROM tbl_posto_estoque_localizacao WHERE peca = $peca_outra_fab";
        $res_tem_localizacao = pg_query($con, $sql_tem_localizacao);
      
        if (pg_num_rows($res_tem_localizacao) > 0) {
            $sql_outra_peca = " UPDATE tbl_posto_estoque_localizacao SET
                                        localizacao = '$localizacao', posto = $login_posto_estoque
                                WHERE peca = $peca_outra_fab";
        } else {
            $sql_outra_peca = "INSERT INTO tbl_posto_estoque_localizacao (posto, peca, localizacao) VALUES ($login_posto_estoque, $peca_outra_fab, '$localizacao')"; 
        }
        $res_outra_peca = pg_query ($con,$sql_outra_peca);
    }

    $sql_localizacao = "SELECT peca FROM tbl_posto_estoque_localizacao WHERE peca = $peca";
    $res_localizacao = pg_query($con, $sql_localizacao);
    
    if (pg_num_rows($res_localizacao) > 0) {
        $sql_peca = " UPDATE tbl_posto_estoque_localizacao SET
                                    localizacao = '$localizacao', posto = $login_posto_estoque
                            WHERE peca = $peca";
    } else {
        $sql_peca = "INSERT INTO tbl_posto_estoque_localizacao (posto, peca, localizacao) VALUES ($login_posto_estoque, $peca, '$localizacao')"; 
    }     
    $res_peca = pg_query ($con,$sql_peca);

    return true;    
}


function atualiza_status_checkpoint($os, $descricao, $fabrica = "") {
    global $con;
    
    if (empty($os) || empty($descricao)) {
        return false;
    }

    if ($fabrica == 177) {
        
        $c = "SELECT os FROM tbl_auditoria_os 
            WHERE os = $os 
            AND liberada IS NULL 
            AND reprovada IS NULL";

        $result_c = pg_query($con, $c);

        if (pg_num_rows($result_c) > 0) {
            return false;
        } 

    }

    $busca_status = "SELECT status_checkpoint
                     FROM tbl_status_checkpoint
                     WHERE UPPER(descricao) = UPPER('".$descricao."')
                     LIMIT 1";

    $sql_status = "UPDATE tbl_os
                   SET status_checkpoint = (
                        {$busca_status}
                   )
                   WHERE os = {$os}
                   AND finalizada IS NULL";

    $res_status = pg_query($con,$sql_status);

    if (pg_last_error($con)) {
        return false;
    } 

    return true;
}

function grava_gera_pedido_os($id_posto, $os_produto){
    global $con, $login_fabrica, $msg_erro;

   /*Marca se posto vai gerar pedido ou não tbl_posto_fabrica.gera_pedido */
    $sql_posto_fabrica = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = $id_posto and fabrica = $login_fabrica";
    $res_posto_fabrica = pg_query($con, $sql_posto_fabrica);

    if(strlen(trim(pg_last_error($con)))== 0 ){
        if(pg_num_rows($res_posto_fabrica)>0){
            $parametros_adicionais = pg_fetch_result($res_posto_fabrica, 0, parametros_adicionais);
            $parametros_adicionais = json_decode($parametros_adicionais, true);
            $gera_pedido = (strlen(trim($parametros_adicionais['gera_pedido']))>0 )? $parametros_adicionais['gera_pedido'] : 'f';
          
            $sql = "UPDATE tbl_os_item SET liberacao_pedido = '$gera_pedido' WHERE os_produto = $os_produto";
            $res = pg_query($con, $sql);
        }
    }
}

if (!function_exists('serie_produto_versao')) {
	function serie_produto_versao($produto, $serie) {

		global $con, $campos, $login_fabrica;

		$mascara_ok = null;
		$versao     = null;

		if (!empty($produto) && !empty($serie)) {
			$sql = "SELECT mascara, posicao_versao
					  FROM tbl_produto_valida_serie
					 WHERE produto = {$produto}
					   AND fabrica = {$login_fabrica}";
			$res = pg_query($con, $sql);

			while ($mascara = pg_fetch_object($res)) {
				$regExp = str_replace(array('L','N'), array('[A-Z]', '[0-9]'), $mascara->mascara);

				if (preg_match("/$regExp/i", $serie)) {
					$mascara_ok = $mascara->mascara;

					break;
				}
			}

			if ($mascara_ok != null) {
				$versao = mt_substr($serie, $mascara->posicao_versao);
			}
		}

		return $versao;
	}
}

function VerificaBloqueioRevenda($cnpj, $fabrica){
    global $con;

    $sql = "SELECT tbl_revenda_fabrica.motivo_bloqueio
            FROM tbl_revenda_fabrica
            INNER JOIN tbl_revenda on tbl_revenda.revenda = tbl_revenda_fabrica.revenda
            WHERE tbl_revenda_fabrica.fabrica = $fabrica
            AND tbl_revenda.cnpj = '$cnpj'
            AND tbl_revenda_fabrica.data_bloqueio IS NOT NULL";
    $res = pg_query($con, $sql);
    if(pg_num_rows($res) > 0){
        $motivo = pg_fetch_result($res,0,'motivo_bloqueio');
        $msg_bloqueio = "Revenda Bloqueada para abertura de Ordem de Serviço. Motivo: $motivo .";
        return $msg_bloqueio;
    }else{
        return false;
    }
}

/*FUNÇÕES DA BLACK&DECKER*/
function VerificaDemanda($pedidoVerificar, $obs_motivo = null, $auditoria_tipo= null, $aprovaAutomaticoAdmin = false) {
   global $con, $login_fabrica, $login_admin;

    $sql_qtde_pecas = "
        SELECT tbl_pedido_item.peca,
                tbl_pedido_item.pedido_item,
               tbl_peca.parametros_adicionais,
               tbl_pedido_item.qtde,
               TO_CHAR(tbl_pedido_item.data_item,'YYYY-MM-DD') AS data_item
          FROM tbl_pedido_item
    INNER JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido
    INNER JOIN tbl_peca   ON tbl_peca.peca     = tbl_pedido_item.peca
         WHERE tbl_pedido_item.pedido = $pedidoVerificar
           AND JSON_FIELD('qtde_demanda', tbl_peca.parametros_adicionais) IS NOT NULL
           AND JSON_FIELD('qtde_demanda', tbl_peca.parametros_adicionais) ~'\\d'
           AND JSON_FIELD('qtde_demanda', tbl_peca.parametros_adicionais)::NUMERIC < tbl_pedido_item.qtde
           AND (tbl_pedido_item.valores_adicionais::JSONB->>'aprovado' <> 'true' OR tbl_pedido_item.valores_adicionais::JSONB->>'aprovado' IS NULL)
      ORDER BY data_item";
    $res_qtde_pecas = pg_query($con, $sql_qtde_pecas);

    $black_entra_auditoria = false;

    $aprovarAutomatico = false;
    $colocarAuditoria = false;

    $sql_data_pedido_status = "SELECT TO_CHAR(data,'YYYY-MM-DD') AS data , status, pedido_status FROM tbl_pedido_status WHERE pedido = $pedidoVerificar  AND status IN (1, 14, 18, 33) ORDER BY data DESC LIMIT 1";
    $res_data_pedido_status = pg_query($con, $sql_data_pedido_status);

    $data  = pg_fetch_result($res_data_pedido_status, 0, data);
    $status_ultimo = pg_fetch_result($res_data_pedido_status, 0, 'status');
    $pedido_status_id = pg_fetch_result($res_data_pedido_status, 0, 'pedido_status');

    $rQtPc = pg_num_rows($res_qtde_pecas);

    for ($i = 0; $i < $rQtPc; $i++) {
        
        $parametros_adicionais = pg_fetch_result($res_qtde_pecas, $i, 'parametros_adicionais');
        $qtde                  = pg_fetch_result($res_qtde_pecas, $i, 'qtde');
        $data_item             = pg_fetch_result($res_qtde_pecas, $i, 'data_item');
        $parametros_adicionais = json_decode($parametros_adicionais, true);
        $qtde_demanda          = $parametros_adicionais['qtde_demanda'];
        $pedido_item           = pg_fetch_result($res_qtde_pecas, $i, 'pedido_item');

        $valores_adicionais = array();       

        $black_entra_auditoria = (strlen($data) == 0 OR strtotime($data) <= strtotime($data_item)) ? true : $black_entra_auditoria;

        if ($black_entra_auditoria == true) {

            if($qtde > $qtde_demanda and $qtde_demanda >= 0 and (strlen(trim($qtde_demanda)) > 0) AND  strtotime($data_item)  >= strtotime($data)){
                if($qtde > 3 OR $aprovaAutomaticoAdmin == true){
                    $aprovarAutomatico = false;
                    $colocarAuditoria = true;
                    $valores_adicionais['demanda'] = 'true';
                    $valores_adicionais['aprovado'] = false;
                    $valores_adicionais['qtde_demanda'] = $qtde_demanda;

                    $valores_adicionais = json_encode($valores_adicionais);

                    $pedidosItem[] = $pedido_item;

                    $sql = "UPDATE tbl_pedido_item set valores_adicionais = coalesce(valores_adicionais::jsonb, '$valores_adicionais') || '$valores_adicionais' where pedido_item = $pedido_item";
                    $res = pg_query($con, $sql);
                }else{
                    $aprovarAutomatico = true;                   
                }                
            }
        }
    }

    if($aprovarAutomatico == true and $colocarAuditoria == false){
        $sqlVerStatus = "SELECT pedido FROM tbl_pedido_status WHERE pedido = $pedidoVerificar ";
        $resVerStatus = pg_query($sqlVerStatus);
        if(pg_num_rows($resVerStatus)==0){
            
            $valores_adicionais['demanda'] = "true";
            $valores_adicionais['aprovado'] = "true";
            $valores_adicionais['aprovadoAutomatico'] = "true";

            $valores_adicionais = json_encode($valores_adicionais);

            $sql = "UPDATE tbl_pedido_item set valores_adicionais = coalesce(valores_adicionais::jsonb, '$valores_adicionais') || '$valores_adicionais', obs = 'Aprovado Automaticamente'  where pedido_item = $pedido_item";
            $res = pg_query($con, $sql);

            $sql_insert_status = "INSERT INTO tbl_pedido_status(pedido,status ,observacao) VALUES($pedidoVerificar,1, 'Aprovado Automaticamente')";
            $res_insert_status = pg_query($con, $sql_insert_status);            

            $sql_upd_status = "UPDATE tbl_pedido SET status_pedido = 1 WHERE pedido = $pedidoVerificar and fabrica = $login_fabrica";
            $res_upd_status = pg_query($con, $sql_upd_status);                        
        }
    }

    if($colocarAuditoria == true AND $aprovaAutomaticoAdmin == false){
        if($status_ultimo != 33 && $status_ultimo != 18){    
			$obs_motivo = empty($obs_motivo) ? 'Auditoria de demanda' : $obs_motivo;
            $sql_insert_status = "INSERT INTO tbl_pedido_status(pedido,status ,observacao) VALUES($pedidoVerificar, 18, '$obs_motivo ')";
            $res_insert_status = pg_query($con, $sql_insert_status);

            $sql_upd_status = "UPDATE tbl_pedido SET status_pedido = 18 WHERE pedido = $pedidoVerificar and fabrica = $login_fabrica";
            $res_upd_status = pg_query($con, $sql_upd_status);
        }
    } 

    if($aprovaAutomaticoAdmin == true){
        if($status_ultimo != 33){    
            $sql_insert_status = "UPDATE tbl_pedido_status SET status = 33, observacao = '$obs_motivo',admin = $login_admin where pedido = $pedidoVerificar and pedido_status = $pedido_status_id";
            $res_insert_status = pg_query($con, $sql_insert_status);
        }   
        
            $valores_adicionais = array();
            $valores_adicionais['aprovado'] = "true";
            $valores_adicionais['demanda'] = "true";
            $valores_adicionais['qtde_demanda'] = $qtde_demanda;
            $valores_adicionais['admin'] = $login_admin;
            $valores_adicionais['auditoria'] = $auditoria_tipo;
            $valores_adicionais = json_encode($valores_adicionais);

            // HD-7168740 A obs será distinguida por peça
            //$sql_pedido_item = "UPDATE tbl_pedido_item set obs = '$obs_motivo', valores_adicionais = '$valores_adicionais' WHERE pedido_item in (". implode(",", $pedidosItem).")";
            //$res_pedido_item = pg_query($con, $sql_pedido_item);
         
            $sql_upd_status = "UPDATE tbl_pedido SET status_pedido = 33 WHERE pedido = $pedidoVerificar and fabrica = $login_fabrica";
            $res_upd_status = pg_query($con, $sql_upd_status);        
    }

    if($colocarAuditoria == false && empty($login_admin) && $black_entra_auditoria == false){

        $sqlVerStatus = "SELECT status from tbl_pedido_status where pedido = $pedidoVerificar order  by data desc limit 1";
        $resVerStatus = pg_query($sqlVerStatus);

        if(pg_num_rows($resVerStatus)>0){
            $status_ped = pg_fetch_result($resVerStatus, 0, 'status');
        }

        if($status_ped == 18){
            $sqlVerDemandaItem = "SELECT tbl_pedido_item.pedido_item 
                                from tbl_pedido_item 
                                join tbl_pedido on tbl_pedido.pedido = tbl_pedido_item.pedido and tbl_pedido.fabrica = 1
                                where tbl_pedido.pedido = $pedidoVerificar 
                                and tbl_pedido.status_pedido = 18 
                                and tbl_pedido_item.valores_adicionais::JSON->>'demanda' = 'true'";
            $resVerDemandaItem = pg_query($sqlVerDemandaItem);         
            if (pg_num_rows($resVerDemandaItem) == 0) {
     
                $sql_insert_status = "INSERT INTO tbl_pedido_status(pedido,status ,observacao) VALUES($pedidoVerificar,1, 'Liberado Automaticamente')";
                $res_insert_status = pg_query($con, $sql_insert_status);
     
                $sql_upd_status = "UPDATE tbl_pedido SET status_pedido = 1 WHERE pedido = $pedidoVerificar and fabrica = $login_fabrica";
                $res_upd_status = pg_query($con, $sql_upd_status);
            }
        }
    }

    
}

function DividePedidos($pedido_antigo){

    global $con, $login_fabrica, $pedidos;

    $sql = "SELECT count(1) FROM tbl_pedido_item WHERE pedido = $pedido_antigo";
    $res = pg_query($con,$sql);
    $count = pg_fetch_result($res, 0, 0);

    $qtde_corte = 250;

    $pedidos[] = $pedido_antigo;

    if($count > $qtde_corte){

        $itens = ceil($count / $qtde_corte);

        if($itens > 1){
            $cond_pedido_offline = ", pedido_offline = $pedido_antigo ";
        }

        $sql = "UPDATE tbl_pedido SET total_original = total  $cond_pedido_offline  WHERE pedido = $pedido_antigo";
        $res = pg_query($con, $sql);
        if(strlen(pg_last_error($con))>0){
            $msg_erro['erro'][] = pg_last_error($con);
        }

        for($i = 1; $i <= $itens; $i++){

            if ($i == 1){
                continue;
            }

            $sql = "INSERT INTO tbl_pedido(fabrica,posto,tabela,tipo_pedido,condicao,tipo_posto,unificar_pedido,total_original,pedido_sedex, pedido_acessorio, pedido_os,pedido_offline, status_pedido,admin) SELECT fabrica,posto,tabela,tipo_pedido,condicao,tipo_posto,unificar_pedido,total_original,pedido_sedex, pedido_acessorio, pedido_os, $pedido_antigo, status_pedido, admin FROM tbl_pedido WHERE pedido = $pedido_antigo RETURNING pedido";
            $res = pg_query($con,$sql);
            if(strlen(pg_last_error($con))>0){
                $msg_erro['erro'][] = pg_last_error($con);
            }

            $pedido_novo = pg_fetch_result($res, 0, 0);

            $pedidos[] = $pedido_novo;

            $sql = "UPDATE tbl_pedido_item SET pedido = $pedido_novo WHERE pedido_item in (SELECT pedido_item FROM tbl_pedido_item WHERE pedido = $pedido_antigo OFFSET $qtde_corte LIMIT $qtde_corte)";
            $res = pg_query($con,$sql);
            if(strlen(pg_last_error($con))>0){
                $msg_erro['erro'][] = pg_last_error($con);
            }

            $sql_pedido_status = "INSERT INTO tbl_pedido_status (pedido, status, observacao, admin) SELECT $pedido_novo, status, observacao, admin FROM tbl_pedido_status WHERE pedido = $pedido_antigo ";
            $res_pedido_status = pg_query($con,$sql_pedido_status);
            if(strlen(pg_last_error($con))>0){
                $msg_erro['erro'][] = pg_last_error($con);
            }
        }
    }
    if(count($msg_erro['erro'])==0){
        return $pedidos;
    }else{
        return $msg_erro;
    }
}

function calculaTaxaAdministrativa($con,$login_fabrica,$login_posto,$os)
{
    $sqlTaxa = "
		SELECT  case when tbl_posto_fabrica.parametros_adicionais~'\\\\\\\'
				 then tbl_posto_fabrica.parametros_adicionais::JSONB->>'recebeTaxaAdm' else json_field('recebeTaxaAdm' , tbl_posto_fabrica.parametros_adicionais) end as recebeTaxaAdm,
                tbl_excecao_mobra.tx_administrativa
        FROM    tbl_posto_fabrica
   LEFT JOIN    tbl_excecao_mobra USING (posto,fabrica)
        WHERE   fabrica = $login_fabrica
        AND     posto = $login_posto
        AND     adicional_mao_de_obra IS NULL
        ";

    $resTaxa = pg_query($con,$sqlTaxa);

    $recebeTaxaAdm         = pg_fetch_result($resTaxa,0,recebeTaxaAdm);
    $taxa_administrativa   = pg_fetch_result($resTaxa,0,tx_administrativa);

    if ($recebeTaxaAdm == "sim") {
        if ($taxa_administrativa == 0) {
            $sqlDiff = "
                SELECT  tbl_os.data_abertura - tbl_os.data_conserto::DATE AS diff
                FROM    tbl_os
                WHERE   fabrica = $login_fabrica
                AND     os = $os and data_conserto notnull";
            $resDiff = pg_query($con,$sqlDiff);
			$taxa_administrativa = 0;
			if(pg_num_rows($resDiff) > 0) {
				$diff = pg_fetch_result($resDiff,0,diff);

				$diff = filter_var($diff,FILTER_SANITIZE_NUMBER_INT);
				$valorDiferenca = $diff * -1;
				/*
					* - Para assumir a nova taxa
					* gradual de administração, vai gravar em um campo
					* da OS para, depois, ser resgatada na função SQL
					*/

				if ($valorDiferenca >= 0 && $valorDiferenca <= 10) {
					$taxa_administrativa = 1.2;
				} else if ($valorDiferenca > 10 && $valorDiferenca <= 20) {
					$taxa_administrativa = 1.15;
				} else if ($valorDiferenca > 20 && $valorDiferenca <= 30) {
					$taxa_administrativa = 1.1;
				} else {
					$taxa_administrativa = 0;
				}
			}
        }
		$sqlo = "select os  from tbl_os_campo_extra where os = $os  "; 
		$reso = pg_query($con, $sqlo); 
		if(pg_num_rows($reso) > 0) {
        $sqlUpdTx = "
            UPDATE  tbl_os_campo_extra
            SET     campos_adicionais = case when campos_adicionais ~'{' then JSONB_SET(regexp_replace(campos_adicionais,'(\w)\\\\u','\\1\\\\\\\\u','g')::jsonb,'{TxAdmGrad}','".$taxa_administrativa."') else '{\"TxAdmGrad\":\"$taxa_administrativa\"}' end
            WHERE   os = $os
";
		}else{
			$sqlUpdTx = "insert into tbl_os_campo_extra(os, campos_adicionais,fabrica) values( $os, '{\"TxAdmGrad\":$taxa_administrativa}', $login_fabrica) "; 

		}
        $resUpdTx = pg_query($con,$sqlUpdTx);
    }
    if (pg_last_error($con)) {
        return false;
    }

    return true;
}
/*FUNÇÕES DA BLACK&DECKER*/

if (!function_exists('insereInteracaoOs')) {
	function insereInteracaoOs($arrData){

		global $con;

		$camposTblComunicado = array_keys($arrData);
		$insertCampos = implode(',', $camposTblComunicado);
		$insertValores = array_map(
			function($i) {
				if (is_string($i)) {
					$i = utf8_encode(stripslashes($i));
					return $i;
				}
			}
		, array_values($arrData));
		$insertValores = implode(',', $insertValores);
		$insert = "INSERT INTO tbl_os_interacao ({$insertCampos}) VALUES ({$insertValores})";

		$res = pg_query($con,$insert);

		if(!$res){
			throw new Exception(utf8_encode('Erro ao enviar comunicado ao posto'));
		}
	}
}

if (!function_exists('dateFormat')) {

    /**
     * @name:           dateFormat
     * @author: Manuel López -2012
     * @param:  string  $datahora   data (e hora, se precisar) em formato numérico, com 2 dígitos para dia, mes e ano, ou 4 dígitos para o ano
     * @param:  string  $orgFmt     ordem de dia mês e ano na string $datahora. p.e.: "mdy"
     * @param:  string  $destFmt    default 'y-m-d' (ISO date). Formato de saída, incluíndo os separadores de data.
     *                              Também pode ser "long" ou "extenso" para data longa (Terça-feira, 24 de julho de 2012)
     *                              Também pode ser 'unix', para retornar o unix timestamp
     * @param   string  $idioma     optional    Idioma de saída para data longa, só funciona se existe a função 'traduz()'
     * @returns: mixed  string com a data no formato solicitado ou FALSE se a data não for válida
     *
     * TO-DO: validar hora, tratar hora, min,seg
     **/

    function dateFormat($datahora, $orgFmt, $destFmt='y-m-d', $idioma = null) {

        global $cook_idioma, $debug;

        $longFormat = false;

        list($data, $hora) = preg_split('/\s|T/', $datahora);

        $dataSoNums = preg_replace('/\D/', '', $data);

        $hasTime = ($hora != '');

        // Retorna FALSE se a data não tem 6 ou 8 dígitos (dd/mm/yy ou dd/mm/yyyy)
        if (!in_array(strlen($dataSoNums), array(6,8)))
            return false;

        switch (strtolower($destFmt)) {
            case 'long':
            case 'long format':
            case 'full':
            case 'fulldate':
            case 'extenso':
            case 'completa':
                $destFmt    = 'y-m-d';
                $longFormat = true;
                $idioma     = (is_null($idioma)) ? $cook_idioma : $idioma;
                break;

            case 'iso':
            case 'postgres':
                $destFmt = 'y-m-d';
                break;

            case 'br':
                $destFmt = 'd/m/y';
                break;

            case 'euro':
                $destFmt = 'd-m-y';
                break;

            case 'usa':
                $destFmt = 'm/d/y';
                break;

            case 'deu':
                $destFmt = 'd.m.y';
                break;

            case 'timestamp':
                $destFmt = 'unix';
                break;

        }

        if (!$idioma) $idioma = 'pt-br';

        $longDateFmts = array(
            'pt-br' => '%s, %d de %s de %d',
            'es'    => '%s, %d de %s de %d',
            'en-us' => '%s, %3$s %2$d, %4$d',
            'de'    => '%s, %d. %s %d' // Formatação copiada do cabeçalho do Frankfurter Allgemeine
        );

        $regexs = array(
            'dmy' => '/(?P<d>\d{2})(?P<m>\d{2})(?P<y>\d{2,4})/',
            'mdy' => '/(?P<m>\d{2})(?P<d>\d{2})(?P<y>\d{2,4})/',
            'ymd' => '/(?P<y>\d{2,4})(?P<m>\d{2})(?P<d>\d{2})/',
        );

        $a = preg_match($regexs[$orgFmt], $dataSoNums, $atoms);

        if ($a === false)
            return false;

        if (isCLI and $debug)
            print_r($atoms);

        extract($atoms); // Cria as variáveis $d $m $y

        if (strlen($y) == 2)
            $y = ($y>60) ? "19$y" : "20$y";

        if (!checkdate($m, $d, $y))
            return false;

        if($destFmt == 'unix'):
            $ret = date('U', "$y-$m-$d $hora");
        else:

            $ret = str_replace('d', $d, $destFmt);
            $ret = str_replace('m', $m, $ret);
            $ret = str_replace('y', $y, $ret);

        endif;

        if ($longFormat) {

            $dias  = array('domingo', 'segunda-feira', 'terca-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sabado');
            $meses = array(1=>'janeiro', 'fevereiro', 'marco', 'abril', 'maio','junho','julho','agosto', 'setembro', 'outubro','novembro','dezembro');

            if (function_exists('traduz')) {
                array_unshift($meses, 'void');
                $diaSemana = explode(' ', traduz($dias,  $con));
                $mes       = explode(' ', traduz($meses, $con));
            } else {
                $diaSemana = array_filter($dias,  'ucwords');
                $mes       = array_filter($meses, 'ucwords');
            }

            $weekDay = date("w", strtotime("$y-$m-$d"));

            $vardia = intval($d);
            $varmes = $mes[intval($m)];
            $varano = intval($y);
            $diaSem = $diaSemana[$weekDay];

            if (isCLI and $debug)
                echo "$diaSem ($weekDay), Dia $vardia, mês $varmes, Ano $varano ($idioma)\n";

            $ret = sprintf($longDateFmts[$idioma], $diaSem, $vardia, $varmes, $varano);
        }
        if ($hasTime)
            $ret = "$ret $hora"; // adiciona a hora, se é que veio
        return $ret;
    }
}

if (!function_exists('toUtf8')) {
    function toUtf8($toUtf8) {
        if (is_string($toUtf8)) {
            return utf8_encode($toUtf8);
        }
        if (!is_array($toUtf8)) {
            return $toUtf8;
        }
        $newArray = array();
        foreach ($toUtf8 as $key => $value) {
            $newKey = toUtf8($key);
            $newValue = toUtf8($value);
            $newArray[$newKey] = $newValue;
        }
        return $newArray;

    }
}

if (!function_exists('formata_data')) {
    function formata_data($data) {
        return dateFormat($data, 'dmy', 'y-m-d');
    }
}

if (!function_exists('mostra_data')) {
    function mostra_data($data) {
        $r = dateFormat($data, 'ymd', 'd/m/y');
        return ($r === false) ? null : $r;
    }
}

if (!function_exists('mostra_data_hora')) {
    function mostra_data_hora($data) {
        $r = dateFormat($data, 'ymd', 'd/m/y');
        return ($r === false) ? null : substr($r, 0, 16); //Tira os segundos
    }
}

if (!function_exists('fnc_formata_data_pg')) {
    function fnc_formata_data_pg ($string) {

        $xdata = trim ($string);
        $xdata = str_replace ('/','',$xdata);
        $xdata = str_replace ('-','',$xdata);
        $xdata = str_replace ('.','',$xdata);

        if (strlen ($xdata) > 0) {

            if (strlen ($xdata) >= 6) {
                $dia = substr ($xdata,0,2);
                $mes = substr ($xdata,2,2);
                $ano = substr ($xdata,4,4);

                if (strpos ($xdata,"/") > 0) {
                    list ($dia,$mes,$ano) = explode ("/",$xdata);
                }
                if (strpos ($xdata,"-") > 0) {
                    list ($dia,$mes,$ano) = explode ("-",$xdata);
                }
                if (strpos ($xdata,".") > 0) {
                    list ($dia,$mes,$ano) = explode (".",$xdata);
                }
            }else{
                $dia = substr ($xdata,0,2);
                $mes = substr ($xdata,2,2);
                $ano = substr ($xdata,4,4);
            }

            if (strlen($ano) == 2) {
                if ($ano > 50) {
                    $ano = "19" . $ano;
                }else{
                    $ano = "20" . $ano;
                }
            }
            if (strlen($ano) == 1) {
                $ano = $ano + 2000;
            }

            $mes = "00" . trim ($mes);
            $mes = substr ($mes, strlen ($mes)-2, strlen ($mes));

            $dia = "00" . trim ($dia);
            $dia = substr ($dia, strlen ($dia)-2, strlen ($dia));

            $xdata = "'". $ano . "-" . $mes . "-" . $dia ."'";

        }else{
            $xdata = "null";
        }

        return $xdata;

    }
}

if (!function_exists('validaData')) {
    function validaData($data_inicial, $data_final, $periodo = null){
        $datas = array();

        if ($data_inicial != null) {
            $datas[] = $data_inicial;
        }

        if ($data_final != null) {
            $datas[]  = $data_final;
        }

        if (!count($datas)) {
            throw new Exception("Informe a data");
        }

        $dataValida = array_filter($datas, function($data) {
            if (preg_match("/\//", $data)) {
                list ($dia, $mes, $ano) = explode("/", $data);
            } else if (preg_match("/\-/", $data)) {
                list ($ano, $mes, $dia) = explode("-", $data);
            } else {
                return false;
            }

            return checkdate($mes, $dia, $ano);
        });

        if (count($dataValida) != count($datas)) {
            throw new Exception("Data inválida");
        }

        if(preg_match("/\//", $data_inicial)){
            list($dia,$mes,$ano) = explode("/",$data_inicial);
            $aux_data_inicial = $ano."-".$mes."-".$dia;
        }else{
            $aux_data_inicial = $data_inicial;
        }

        if(preg_match("/\//", $data_final)){
            list($dia,$mes,$ano) = explode("/",$data_final);
            $aux_data_final = $ano."-".$mes."-".$dia;
        }else{
            $aux_data_final = $data_final;
        }

        if (count($datas) == 2 && strtotime($aux_data_inicial) > strtotime($aux_data_final)) {
            throw new Exception("Data inicial não pode ser maior que data final");
        }

        if (count($datas) == 2 && $periodo != null && strtotime("{$aux_data_inicial}+{$periodo} months") < strtotime($aux_data_final)) {
            throw new Exception( "O intervalo entre as datas não pode ser maior que {$periodo} ".($periodo > 1 ? "meses" : "mês"));
        }

        return true;
    }
}

if (!function_exists('fnc_formata_data_hora_pg')) {
    function fnc_formata_data_hora_pg ($data) {

        if (strlen ($data) == 0) return null;

        $xdata = $data.":00 ";
        $aux_ano  = substr ($xdata,6,4);
        $aux_mes  = substr ($xdata,3,2);
        $aux_dia  = substr ($xdata,0,2);
        $aux_hora = substr ($xdata,11,5).":00";

        return "'" . $aux_ano."-".$aux_mes."-".$aux_dia." ".$aux_hora . "'";
    }
}

/*
======================================================================================================
Formata uma string para um valor aceito como moeda (REAIS)
- text                : string informada
Retornos:
- float8  : numero convertido
uso:
    echo fnc_limpa_moeda('01.234.567,89')."<br>";
    echo fnc_limpa_moeda('01234567,89')."<br>";
    echo fnc_limpa_moeda('01.234.567,00')."<br>";
    echo fnc_limpa_moeda('01234567.00')."<br>";
    echo fnc_limpa_moeda('01,234,567.89')."<br>";
    echo fnc_limpa_moeda('0123456789')."<br>";

======================================================================================================
*/

if (!function_exists('fnc_limpa_moeda')) {
    function fnc_limpa_moeda($text) {

        $text = trim($text) ;

        if (substr($text,1,1) == ',' OR substr($text,1,1) == '.')
            $text = '0'.$text;

        if (strlen($text) == 0){
            return false;
        }else{
            $m_pos = -1;
            while ($m_pos < strlen($text)){
                $m_pos ++;
                $m_letra = substr($text,$m_pos,1);
                if (strpos("\,\.", $m_letra) > 0){
                    $m_letra = '*';
                    $m_aux   = $m_pos;
                }
                if ($m_letra <> '*') $m_limpar = $m_limpar . $m_letra;
            }

            if ($m_aux > 0){
                $m_aux = strlen($text) - $m_aux;

                $m_limpar = fnc_so_numeros(substr($m_limpar,0,strlen($m_limpar)-$m_aux+1)) .".". fnc_so_numeros (substr($m_limpar,strlen($m_limpar)-$m_aux+1,$m_aux));
                $m_retorno = $m_limpar;
            }else{
                $m_limpar   = fnc_so_numeros($m_limpar) .'.00';
                $m_retorno  = $m_limpar;
            }
        }
        return $m_retorno;
    }
}

/*-----------------------------------------------------------------------------
SoNumeros($string)
$string = para ser retirado somente os números
Pega uma string e retorna somente os numeros da mesma
-----------------------------------------------------------------------------*/
if (!function_exists('fnc_so_numeros')) {
    function fnc_so_numeros($string){
        $numeros = preg_replace("/[^0-9]/", "", $string);
        return trim($numeros);
    }
}

// ###############################################################
// Funcao para calcular diferenca entre duas horas
// ###############################################################
if (!function_exists('calcula_hora')) {
    function calcula_hora($hora_inicio, $hora_fim){
        // Explode
        $ehora_inicio = explode(":",$hora_inicio);
        $ehora_fim    = explode(":",$hora_fim);

        // Tranforma horas em minutos
        $mhora_inicio = ($ehora_inicio[0] * 60) + $ehora_inicio[1];
        $mhora_fim    = ($ehora_fim[0] * 60) + $ehora_fim[1];

        // Subtrai as horas
        $total_horas = ( $mhora_fim - $mhora_inicio );

        // Tranforma em horas
        $total_horas_div = $total_horas / 60;

        // Valor de horas inteiro
        $total_horas_int = intval($total_horas_div);

        // Resto da subtracao = pega minutos
        $total_horas_sub = $total_horas - ($total_horas_int * 60);
        // Horas trabalhadas
        if ($total_horas_sub < 10) {
            $total_horas_sub = "0".$total_horas_sub;
        }
        $horas_trabalhadas = $total_horas_int.":".$total_horas_sub;

        // Retorna valor
        return $horas_trabalhadas;
    }
}

if (!function_exists('calcula_hora_simples')) {
    function calcula_hora_simples($hora){
        // Explode
        $ehora = explode(":",$hora);

        $total_horas   = $ehora[0] * 60;    // Tranforma em minutos
        $total_minutos = $ehora[1];         // atribui minutos

        $total_horas_minutos = $total_horas + $total_minutos; // soma horas tranformadas em minutos e minutos

        $horas_trabalhadas = ( intval($total_horas_minutos) / 60); // transforma em decimais

        // Retorna valor
        return $horas_trabalhadas;
    }
}

//-----------------------------------------------------
//Funcao: validaCNPJ($cnpj) HD 34921
//Sinopse: Verifica se o valor passado é um CNPJ válido
// Retorno: Booleano
//-----------------------------------------------------
if (!function_exists('checa_cnpj')) {
    function checa_cnpj($cnpj) {
        if ((!is_numeric($cnpj)) or (strlen($cnpj) <> 14))
        {
            return 2;
        }
        else
        {
            $i = 0;
            while ($i < 14)
            {
                $cnpj_d[$i] = substr($cnpj,$i,1);
                $i++;
            }
            $dv_ori = $cnpj[12] . $cnpj[13];
            $soma1 = 0;
            $soma1 = $soma1 + ($cnpj[0] * 5);
            $soma1 = $soma1 + ($cnpj[1] * 4);
            $soma1 = $soma1 + ($cnpj[2] * 3);
            $soma1 = $soma1 + ($cnpj[3] * 2);
            $soma1 = $soma1 + ($cnpj[4] * 9);
            $soma1 = $soma1 + ($cnpj[5] * 8);
            $soma1 = $soma1 + ($cnpj[6] * 7);
            $soma1 = $soma1 + ($cnpj[7] * 6);
            $soma1 = $soma1 + ($cnpj[8] * 5);
            $soma1 = $soma1 + ($cnpj[9] * 4);
            $soma1 = $soma1 + ($cnpj[10] * 3);
            $soma1 = $soma1 + ($cnpj[11] * 2);
            $rest1 = $soma1 % 11;
            if ($rest1 < 2)
            {
                $dv1 = 0;
            }
            else
            {
                $dv1 = 11 - $rest1;
            }
            $soma2 = $soma2 + ($cnpj[0] * 6);
            $soma2 = $soma2 + ($cnpj[1] * 5);
            $soma2 = $soma2 + ($cnpj[2] * 4);
            $soma2 = $soma2 + ($cnpj[3] * 3);
            $soma2 = $soma2 + ($cnpj[4] * 2);
            $soma2 = $soma2 + ($cnpj[5] * 9);
            $soma2 = $soma2 + ($cnpj[6] * 8);
            $soma2 = $soma2 + ($cnpj[7] * 7);
            $soma2 = $soma2 + ($cnpj[8] * 6);
            $soma2 = $soma2 + ($cnpj[9] * 5);
            $soma2 = $soma2 + ($cnpj[10] * 4);
            $soma2 = $soma2 + ($cnpj[11] * 3);
            $soma2 = $soma2 + ($dv1 * 2);
            $rest2 = $soma2 % 11;
            if ($rest2 < 2)
            {
                $dv2 = 0;
            }
            else
            {
                $dv2 = 11 - $rest2;
            }
            $dv_calc = $dv1 . $dv2;
            if ($dv_ori == $dv_calc)
            {
                return 0;
            }
            else
            {
                return 1;
            }
        }
    }
}

if (!function_exists('iif')) {
    function iif($condition, $val_true, $val_false = "") {
        if (is_numeric($val_true) and is_null($val_false)) $val_false = 0;
        if (is_null($val_true) or is_null($val_false) or !is_bool($condition)) return null;
        return ($condition) ? $val_true : $val_false;
    }
}

// if (!function_exists('getValorFabrica')) {
// 	function getValorFabrica($values, $key=null) {
//         if (!is_array($values))
//             return $values;
//
//         if (is_null($key))
//             $key = $GLOBALS['login_fabrica'];
//
//         return array_key_exists($key, $values)
//             ? $values[$key]
//             : $values[0];
//     }
// }

if (!function_exists('menuTCAdmin')) {
    /**
     * @name    menu_item           imprime uma linha do menu das telas [sub]menu_*
     * @returns string              (print direto em tela)
     * @param   array  $item        dados da linha do menu
     * @param   string $bg_color    cor de fundo
     **/
    function menu_item($item, $bg_color=null, $tipo_menu=null) {
        global $login_fabrica, $login_posto, $login_admin, $login_cliente_admin, $login_unico;

        if (!is_array($item)) return false;

        extract($item);

        if ($item['disabled']==true or $item['link']=='')
            return false;

        if (isset($fabrica)) {
            if (is_bool($fabrica) and $fabrica === false)
                return false;

            if (is_int($fabrica))
                if ($login_fabrica != $fabrica)
                    return false;

            if (is_array($fabrica))
                if (!in_array($login_fabrica, $fabrica))
                    return false;
        }

        if (isset($admin)) {
            if (is_bool($admin) and $admin === false)
                return false;

            if (is_int($admin))
                if ($login_admin != $admin)
                    return false;

            if (is_array($admin))
                if (!in_array($login_admin, $admin))
                    return false;
        }

        if (isset($posto)) {
            if (is_array($posto)) { // p.e.: 'posto' => array(4311, 6359),
                if (!in_array($login_posto, $posto)) return false;
            }
            if ($posto === false) // caso haja um ítem no array tal que: 'posto' => ($tipo_posto == 56), por exemplo...
                return false;

            if (is_int($posto)) { // por exemplo, 'posto' => 4311,
                if ($posto != $login_posto) return false;
            }
        }

        if ($so_testes and $login_posto != 6359)
            return false;

        if (isset($fabrica_no)) {
            if (is_bool($fabrica_no) and $fabrica_no !== false)
                return false;

            if (is_int($fabrica_no))
                if ($login_fabrica == $fabrica_no)
                    return false;

            if (is_array($fabrica_no))
                if (in_array($login_fabrica, $fabrica_no))
                    return false;
        }

        if ($blank == true)
            $bgc .= ' target="_blank"';

        if (!is_null($bg_color))
            $bgc = " bgcolor='$bg_color'";

        if ($blank === true)
            $bgc .= ' target="_blank"';

        if (!is_null($tipo_menu) and isset($descr))
            $bgc .= " title='$descr'";

        if (isset($attr)) {
            $bgc .= (is_array($attr)) ? implode('', $attr) : $attr;
        }

        // E ainda, pode ter alguns outros parâmetros visuais...
        // Dá para adicionar conforme a necessidade
        // background vai como bgcolor no <TR></TR>
        if (isset($background))
            $bgc .= " bgcolor='$background'";

        if ($link == 'linha_de_separação') {
            if ($login_posto) {
                echo "<tr bgcolor='#D9E2EF'><td colspan='3'><img src='imagens/spacer.gif' height='3'></td></tr>";
            }
            return false;
        }

        if ($link == "cadastro_os.php" && $login_fabrica == 178) {
            $link = "cadastro_os_revenda.php";
        }

        // Exclusivo para o cabeçalho de seção do menu do Admin
        if ($tipo_menu == 'secao_admin') {
            if ($login_admin) {
                echo "<h3 class='ui-accordion-header'>$titulo</h3>";
            }

            if ($login_posto) {
                $colExpandImg = ($noexpand) ? '' : "            <img src='imagens/icon_collapse.png' class='colexpand' style='float:right'>";
                echo "<div class='cabecalho'>
                        <img src='imagens/corner_se_laranja.gif' style='float:left' />
                        <span style='text-align:center;height:1.4em;vertical-align:middle'>$titulo</span>
                        <img src='imagens/corner_sd_laranja.gif' style='float:right'>
                        $colExpandImg
                 </div>";
            }
            return true;
        }

        //  Agora sim...
        //
        //  Se o ítem for de um submenu ou das abas, a tratativa é diferente

        if ($tipo_menu == 'tab') {

            // if (strpos('sair', $imagem) !== false)
            //  $bgc .= ' style="float:right"';

            $img = implode('_',
                        array_filter(
                            array($idioma, $imagem, $ativo)
                        )
                    );
            if (!file_exists("imagens/aba/$img" . '.gif'))
                $img = implode('_', array_filter( array($imagem, $ativo)));

            return sprintf("<a href='%s' border='0' %s><img src='imagens/aba/%s.gif' border='0' /></a>", $link, $bgc, $img);
        }

        if ($tipo_menu == 'sub') {
            return sprintf("<span class='submenu_telecontrol submenu_telecontrol_callcenter'><a href='%s' %s>%s</a></span>", $link, $bgc, $titulo);
        }

        if ($tipo_menu == 'subAdm') {
            if ($blank == true) {
                $link = ($link=='#' or $link=='void()') ? '"void()"' : '"window.open(' . "'$link'" . ')"';
            } else {
                $link = ($link=='#' or $link=='void()') ? '"void()"' : '"window.location=' . "'$link'" . '"';
            }

            return "<span onclick=$link $bgc>$titulo</span>";
        }

        $bcg .= $TRattrs . $TITLEattrs . $DESCattrs;

        if ($login_posto) {
            $style['img']      = 'style="width: 25px;"';
            $style['img_path'] = 'imagens/';
            $style['a_link']   = 'class="menu"';
        }

        if ($login_admin) {
            $codigoTD = "<td class='ui-content-codigo' style='width: 75px;' title='Código, link curto!'>{$codigo}</td>";
            $style['img']      = "class='ui-content-img'";
            $style['img_path'] = !empty($login_cliente_admin) ? '../admin/imagens/icon/' : "imagens/icon/";
            $style['link']     = "class='ui-content-link'";
            $style['desc']     = "class='ui-content-desc'";
            unset($bgc);
            unset($TRattrs);
            unset($TITLEattrs);
            unset($LINKattrs);
        }

        echo "<tr {$bgc} {$TRattrs}>
            <td {$style['img']}>
                <img src='{$style['img_path']}{$icone}' />
            </td>
            {$codigoTD}
            <td style='width: 250px;' $TITLEattrs {$style['link']}>";
            if (is_array($titulo) and is_array($link)) {
                $num_titulos = count($titulo);

                // Não é foreach, pq o índice controla se dá 'ENTER', e para pegar
                // o link correto para a descrição
                for ($t=0; $t < $num_titulos; $t++) {
                    //echo ($t != 0)?"\n":'';
                    echo "<a href='{$link[$t]}' {$style['a_link']}>{$titulo[$t]}</a>";
                }
            } else {
                if ($login_admin && $blank == true) {
                    echo "<a href='$link' $LINKattrs target='_blank' {$style['a_link']}>$titulo</a>";
                } else {
                    echo "<a href='$link' $LINKattrs {$bgc} {$style['a_link']}>$titulo</a>";
                }
            }

            $descricao = is_array($descr) ? implode('<br />', $descr) : $descr;
            echo "</td>
            <td {$style['desc']} $DESCattrs>
                $descricao
            </td>
        </tr>";

        return true;
    }

    /***
     * @name:   menuTC()
     * @param   $menu   array   Ítens do menu, para seerem repassado à função menu_item()
     * @param   $tabela array   Opcional. Parâmetros para a tabela, key com o atributo e o valor com o valor do atributo.
     *                          Os valores passados neste array sobrescrevem os valores padrão.
     * @param   $cor    string  Opcional. Cor para as linhas ímpares (default #fafafa).
     * @param   $cor2   string  Opcional. Cor para as linhas pares   (default #f0f0f0).
     * @returns int,false       Imprime o menu na saída, ou devolve false se houve erro. Se não há erro, retorna o nº de ítens da saída.
     * @seealso menu_item()
     **/
    function menuTC($menu, $tbl_param=null, $cor = '#fafafa', $cor2 = '#f0f0f0') {
        global $login_posto, $login_cliente_admin, $login_admin;

        $tbl_params = array(
            'border'      => 0,
            'id'          => 'tbl_menu',
            'cellpadding' => 0,
            'cellspacing' => 0,
            'align'       => 'center'
        );
        if (!is_null($tbl_param)) {
            $tbl_params = array_merge($tbl_params, $tbl_param);
        }

        if ($login_admin) {
            $style["width"] = "100%";
            $style["class"] = "ui-accordion-content";
        }

        if ($login_posto) {
            $style["width"] = "700px";
        }

        // $table = "<table ";
        // foreach ($tbl_params as $attr=>$val) {
        //  $table .= " $attr='$val'";
        // }
        // echo $table . ">\n";

        echo "<div style='margin: 0 auto;' class='{$style['class']}'><table style='width: {$style['width']};'";
        foreach ($tbl_params as $attr=>$val) {
            $table .= " $attr='$val'";
        }
        echo "$table >";

        $c = 0;
        $bgcolor  = $cor;
        foreach ($menu as $menu_item) {

            if ($menu_item["disabled"] == true){
                continue; // Já nem repassa se está deshabilitado...
            }

            // menu_item devolve true se imprimiu o ítem ou false se não... Só altera a cor se imprimiu
            if (menu_item($menu_item, $bgcolor)) {
                $c++;
                $bgcolor = ($bgcolor == $cor2) ? $cor : $cor2;
            }
        }
        //echo "</table>";
        echo "</table></div> <br />";
        return $c;
    }

    function menuTCAdmin($menu, $tbl_param=null, $corA='#fafafa', $corA2='#f0f0f0') {

        global $login_admin, $login_fabrica;

        //pre_echo ("Procesando o menu para a fábrica <strong>$login_fabrica</strong>");

        foreach($menu as $secao=>$itens) {
            if (key($menu[$secao]) == 'secao') {

                if (isset($menu[$secao]['secao']['fabrica'])) {
                    $fabricas_sim = $menu[$secao]['secao']['fabrica'];
                    //echo "Mostrar a seção " . $menu[$secao]['secao']['titulo'] . " apenas para as fábricas " . implode(', ',$menu[$secao]['secao']['fabrica']);
                    //print_r($menu[$secao]['secao']['fabrica']);

                    if (is_bool($fabricas_sim))
                        if ($fabricas_sim === false)
                            continue;
                    $ver_fabrica = (is_array($fabricas_sim)) ? $fabricas_sim : array($fabricas_sim);
                    if (!in_array($login_fabrica, $ver_fabrica))
                        continue;
                }

                if (isset($menu[$secao]['secao']['fabrica_no'])) {
                    //echo "Não mostrar a seção " . $menu[$secao]['secao']['titulo'] . " para as fábricas " . implode(', ',$menu[$secao]['secao']['fabrica_no']);
                    //print_r($menu[$secao]['secao']['fabrica_no']);
                    if ($menu[$secao]['secao']['fabrica_no'] === true)
                        continue;
                    if (in_array($login_fabrica, $menu[$secao]['secao']['fabrica_no']))
                        continue;
                }

                echo "<div style='margin: 0 auto;' class='ui-accordion'>";
                menu_item($itens['secao'],
                          array(
                              'id'=>'tbl_menu_'.$menu[$secao]['secao']['titulo'],

                          ),
                          'secao_admin'
                      );

                unset($menu[$secao]['secao']);

            }
            //echo $menu[$secao]['titulo'];

            $this_section_itens = menuTC($menu[$secao], $tbl_param);
            echo "</div>";

            if ($this_section_itens > 0):
                echo ob_get_clean();
            endif;
            ob_end_clean();
        }
    }

    function subMenu($itens, $idioma=null) {

        global $cook_idioma, $login_posto, $login_fabrica, $login_admin;

        if (!is_array($itens))
            return false;
        if (!count($itens))
            return false;

        if (is_null($idioma))
            $idioma = strtolower($cook_idioma);

        if (!in_array($idioma,  array('pt-br'))) // No array, colocar idiomas cuja tradução é "localizada" (não 'genérica')
            $img_suffix = substr($idioma, 0, 2); // Pega os dos primeiros caracteres: es, en, de, zn, etc.

        foreach($itens as $menu_item) {
            $menu_item[]['idioma'] = $img_suffix;
            $submenu[] = ($login_posto) ? menu_item($menu_item, null, 'sub') : menu_item($menu_item, null, 'subAdm');
        }

        foreach ($submenu as $key => $value) {
            if (strlen(trim($value)) == 0) {
                unset($submenu[$key]);
            }
        }

        if ($login_admin) {
            echo implode('', $submenu);
        } else if ($login_posto) {
            echo '<div class="sys_submenu"> | ' . implode(' | ', array_filter($submenu)) . ' | </div>';
        }

        return count(array_filter($submenu)); // Retorna os ítens utilizados
    }

    function tabsMenu($itens, $layout, $idioma=null) {

        global $cook_idioma;

        if (!is_array($itens))
            return false;
        if (!count($itens))
            return false;

        if (is_null($idioma))
            $idioma = strtolower($cook_idioma);

        if ($idioma != 'pt-br')
            $img_suffix = substr($idioma, 0, 2); // Pega os dos primeiros caracteres: es, en, de, zn, etc.

        $width = $itens['largura'];
        unset($itens['largura']);

        foreach($itens['abas'] as $nome => $menu_item) {
            $menu_item['idioma'] = $img_suffix;
            $menu_item['ativo']  = ($nome == $layout) ? 'ativo' : '';
            $tabs[] = menu_item($menu_item, null, 'tab');
        }

        echo "<div class='sys_tabs' style='width: $width'>" .
             implode('', array_filter($tabs)) .
             '</div>';
        return count(array_filter($tabs)); // Retorna os ítens utilizados
    }

    // Array com os nomes das imagens para os menus
    $icone = array(
        "limpar"     => "limpar.png",
        "acesso"     => "acesso.png",
        "computador" => "computador.png",
        "cadastro"   => "cadastro.png",
        "consulta"   => "consulta.png",
        "relatorio"  => "relatorio.png",
        "bi"         => "bi.png",
        "email"      => "email.png",
        "upload"     => "upload.png",
        "anexo"      => "anexo.png",
        "print"      => "print.png",
        "chart"      => "chart.png",
        "usuario"    => "usuario.png"
    );
}

if (!function_exists('calcula_frete')) {
    /**
     *
     * FUNÇÃO calcula_frete()
     *
     * @Parametros: $cep_origem, $cep_destino. $peso, $codigo_servico
     * @Retorno   : float;
     * HD 40324
     *
     **/
    function calcula_frete($cep_origem,$cep_destino,$peso,$codigo_servico = null ){

        $url = "www.correios.com.br";
        $ip = gethostbyname($url);
        $fp = fsockopen($ip, 80, $errno, $errstr, 10);

        if ($codigo_servico == null){
            $codigo_servico     = "40010"; #Código SEDEX
        }

        if (strlen($cep_origem)>0 AND strlen($cep_destino)>0 AND strlen($peso)>0){
            $saida  = "GET /encomendas/precos/calculo.cfm?servico=$codigo_servico&CepOrigem=$cep_origem&CepDestino=$cep_destino&Peso=$peso HTTP/1.1\r\n";
            $saida .= "Host: www.correios.com.br\r\n";
            $saida .= "Connection: Close\r\n\r\n";
            fwrite($fp, $saida);

            $resposta = "";
            while (!feof($fp)) {
                $resposta .= fgets($fp, 128);
            }
            fclose($fp);
            #echo htmlspecialchars ($resposta);

            $posicao = strpos ($resposta,"Tarifa=");
            $tarifa  = substr ($resposta,$posicao+7);
            $posicao = strpos ($tarifa,"&");
            $tarifa  = substr ($tarifa,0,$posicao);
            return $tarifa;
        }else{
            return null;
        }
    }

}


if (!function_exists('mostraMarcaExtrato')) {
    function mostraMarcaExtrato($extrato){
        global $con;
        global $login_fabrica;

        if($login_fabrica == 104){
            $campo_marca = " CASE WHEN tbl_marca.nome <> 'DWT' THEN 'OVD' ELSE 'DWT' END ";
        }else{
            $campo_marca = " tbl_marca.nome ";
        }

        $sqlM = "SELECT  $campo_marca as marca
            FROM   tbl_os
            JOIN   tbl_os_extra ON tbl_os.os = tbl_os_extra.os AND tbl_os_extra.i_fabrica = $login_fabrica
            JOIN   tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
            JOIN   tbl_marca ON tbl_produto.marca = tbl_marca.marca AND tbl_marca.fabrica = $login_fabrica
            WHERE  tbl_os_extra.extrato = $extrato ;";
        $resM = pg_query($con,$sqlM);

        if(pg_num_rows($resM) > 0){
            $marca = pg_result($resM,0,'marca');
            return $marca;
        }
    }
}

if(!function_exists("verificaCpfCnpj")){
	function verificaCpfCnpj($cpf_cnpj){
	// return "ENTREI AQUI";
		global $login_pais;
		$cpf_cnpj = trim($cpf_cnpj);
		if(!empty($cpf_cnpj) AND $login_pais == "BR"){
			if(strlen($cpf_cnpj) <> 11 and strlen($cpf_cnpj) <> 14){
				$erro = "Quantidade de dígitos do CPF/CNPJ inválida <br />";
			}

			if(!is_numeric($cpf_cnpj) AND empty($erro)){
				$erro = "CPF/CNPJ não deve conter letras <br />";
			}

			if(strlen($cpf_cnpj) == 14 AND empty($erro)){
				switch($cpf_cnpj){
					case "11111111111111":
					case "22222222222222":
					case "33333333333333":
					case "44444444444444":
					case "55555555555555":
					case "66666666666666":
					case "77777777777777":
					case "88888888888888":
					case "99999999999999":
					case "00000000000000":
						$erro = "$cpf_cnpj não é um CNPJ válido <br />";
					break;

				}
			}

			if(strlen($cpf_cnpj) == 11 AND empty($erro)){
				switch($cpf_cnpj){
					case "11111111111":
					case "22222222222":
					case "33333333333":
					case "44444444444":
					case "55555555555":
					case "66666666666":
					case "77777777777":
					case "88888888888":
					case "99999999999":
					case "00000000000":
						$erro = "$cpf_cnpj não é um CPF válido <br />";
					break;

				}
			}
		}

		return $erro;
	}
}
if (!function_exists('validaCPF')) {
    /**
    * validaCPF
    *
    * - Realiza a validação do numeral do cpf
    * @param String $cpf
    */
    function validaCPF($cpf = null) {

        // Verifica se um número foi informado
        if(empty($cpf)) {
        return false;
        }

        // Elimina possivel mascara
        #$cpf = preg_replace('[^0-9]', '', $cpf);
        $cpf = preg_replace('/\D/','', $cpf);
        $cpf = str_pad($cpf, 11, '0', STR_PAD_LEFT);

        // Verifica se o numero de digitos informados é igual a 11
        if (strlen($cpf) != 11) {
        return false;
        }
        // Verifica se nenhuma das sequências invalidas abaixo
        // foi digitada. Caso afirmativo, retorna falso
        else if ($cpf == '00000000000' ||
        $cpf == '11111111111' ||
        $cpf == '22222222222' ||
        $cpf == '33333333333' ||
        $cpf == '44444444444' ||
        $cpf == '55555555555' ||
        $cpf == '66666666666' ||
        $cpf == '77777777777' ||
        $cpf == '88888888888' ||
        $cpf == '99999999999') {
        return false;
         // Calcula os digitos verificadores para verificar se o
         // CPF é válido
         } else {

        for ($t = 9; $t < 11; $t++) {

            for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf{$c} * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf{$c} != $d) {
            return false;
            }
        }

        return true;
        }
    }
}

if (!function_exists('excelPostToJson')) {
    function excelPostToJson ($post) {
        $json = array();

        $json["gerar_excel"] = true;

        foreach ($post as $key => $value) {
            if(!is_array($value)){
                $json[$key] = utf8_encode($value);
            }else{
                $json[$key] = array_map('utf8_encode', $value);
            }
        }

        return json_encode($json);
    }
}

if (!function_exists('excelGetToJson')) {
    function excelGetToJson ($get) {
        $json = array();

        $json["gerar_excel"] = true;

        foreach ($get as $key => $value) {
            if(!is_array($value)){
                $json[$key] = utf8_encode($value);
            }else{
                $json[$key] = $value;
            }
        }

        return json_encode($json);
    }
}

function csvPostToJson($post) { // HD-2393979
    $json = array();

    $json["gerar_csv"] = true;

    foreach ($post as $key => $value) {
        if (!is_array($value)) {
            $json[$key] = utf8_encode($value);
        } else {
            $json[$key] = $value;
        }
    }

    return json_encode($json);
}

function arrayToJson ($post) {
    $json = array();

    foreach ($post as $key => $value) {
        if(!is_array($value)){
            $json[$key] = utf8_encode($value);
        }else{
            $json[$key] = $value;
        }
    }

    return json_encode($json);
}

if (!function_exists('moneyDB')) {
    function moneyDB($money) {

        $money = preg_replace("/[^0-9.,]/", "", $money);
        $money = str_replace(".", "", $money);
        $money = str_replace(",", ".", $money);

        if(empty($money))
                return "null";
        else
                return $money;
    }
}

if(!function_exists('priceFormat')){
    function priceFormat($price){
        global $login_fabrica;
        $dontFormat =  array(81,114,122,123,125,128);
        if(empty($price)){
            return 0;
        }else{
            /* 'true' anterior:  number_format($price,4,",",".")*/
            return (in_array($login_fabrica, $dontFormat))? $price: number_format($price,2,",",".");
        }
    }
}

if (!function_exists('getValorFabrica')) {
    function getValorFabrica($values, $key=null) {
        if (!is_array($values))
            return $values;

        if (is_null($key))
            $key = $GLOBALS['login_fabrica'];

        $defaultKey = array_key_exists('default', $values)
            ? 'default' : 0;

        return array_key_exists($key, $values)
            ? $values[$key]
            : $values[$defaultKey];
    }
}

function getValue ($key, $post = true) {
    if(empty($key))
        return null;
    /*
        $_RESULT  -> armazena o resultado dos inputs pegos a través de select no banco
    */
    global $_RESULT;

    /*
        Pega o valor do campo
        Regra:
        se existe o $_RESULT do campo e não existe o $_POST o campo recebera o valor do $_RESULT
        se não irá receber o valor do $_POST
    */
    $split = preg_split("@[\\[\\]]+@",$key);

    if(count($split) == 1){
        $key = $split[0];

        if ($post === true) {
            return (isset($_RESULT[$key]) && !isset($_POST[$key])) ? $_RESULT[$key] : $_POST[$key];
        } else {
            return $_RESULT[$key];
        }

    }

    if ($post === true) {
        $value = (isset($_RESULT) && !count($_POST)) ? $_RESULT : $_POST;
    } else {
        $value = $_RESULT;
    }

    foreach($split as $key){
        if(!strlen($key))
            continue;

        $value = $value[$key];
    }

    if (is_array($value))
        return $value;

    $value = addslashes($value);
    return $value;
}



function montaForm ($inputs = array(), $hiddens = array(), $no_margin = false) {

    /*
        $msg_erro -> usada para deixar o campo com a class de erro caso ele exista no $msg_erro["campos"]
        $_RESULT  -> armazena o resultado dos inputs pegos atravez de select no banco
    */
    global $msg_erro, $_RESULT, $con;

    /*
        Monta o elemento dos campos hiddens
    */
    if (count($hiddens) > 0) {
        /*  $hiddens = array("name") ou $hiddens = array("name" => array("value"=>"valor"))
            $name_id -> será o name e id do campo
                     -> Se $name_id for array, $key será o name e id e $name_id['value'] = valor
        */
        foreach ($hiddens as $key => $name_id) {
            /*
                Populate do campo
            */

            if(is_array($name_id)){

                echo "<input type='hidden' id='{$key}' name='{$key}' value=\"{$name_id["value"]}\" />";

            }else{
                $value = getValue($name_id);
                echo "<input type='hidden' id='{$name_id}' name='{$name_id}' value=\"{$value}\" />";

            }
        }
    }

    /*
        Monta o elemento dos campos
    */
    if (count($inputs) > 0) {
        /*
            $k -> contador para definir a key de cada campo dentro da array $html
        */
        $k = 0;

        /*
            $key    -> será o id e name do campo
            todo name de checkbox será array name[]

            $config -> array de configuração do campo, pode conter as seguintes configurações

            span      -> do espaço ocupado em tela pelo campo (1 a 12)
            label     -> texto que irá aparecer no elemento <label> do campo
            type      -> tipo do campo, input/(types do elemento input), select, option, checkbox, radio
            width     -> tamanho do input (1 a 12)
            inptc     -> tamanho do input que utiliza a class inptc que é uma class para tamanhos especificos (1 a 12) substitui o tamanho normal
            required  -> se for true irá colocar o * na frente do campo
            maxlength -> coloca o valor para o atributo maxlength
            readonly  -> se for true coloca o atributo readonly no campo
            class     -> classes adicionais para o campo deve ser uma string
            title     -> atributo title do elemento

            atributo especifico do select
            options -> array que armazena os options do select
            a key será o value do option e o valor será o texto do option
                    -> Caso o option precisar de parâmetros adicionais:
                        [key]['label'] = Label do campo
                        [key]['extra'] = array("nome_do_atributo" => valor)

            atributo especifico do checkbox
            checks -> array que armazena os checkboxs desta familia de checkbox
            a key será o value do checkbox e o valor será o label do checkbox

            atributo especifido do radio
            radios -> array que armazena os radios desta familia de radio
            a key será o value do radio e o valor será o label do radio

            icon-append -> adiciona um icone ou texto no formato de icone ao final do campo
            deve ser um array, a key deve ser text ou icon, se for text o valor pode ser o que desejar
            se for icon deve olhar o nome do icon na pagina de icones na doc

            icon-prepend -> adiciona um icone ou texto no formato de icone no inicio do campo
            deve ser um array, a key deve ser text ou icon, se for text o valor pode ser o que desejar
            se for icon deve olhar o nome do icon na pagina de icones na doc

            extra -> array de configuração de atributos extras
            a key sera o nome do atributo e o value o valor do atributo

            lupa -> monta html da lupa no campo, deve ser uma array com as seguintes configurações
                name      -> nome da lupa irá no rel do span do icone da lupa
                tipo      -> define pelo o que quer pesquisar (produto, posto, peça)
                parametro -> define pelo o que esta pequisando (referencia, nome, cpf)
                extra     -> parametros extras da lupa deve ser uma array
                             a key sera o nome do atributo extra e o value do valor do atributo extra
        */
        foreach ($inputs as $key => $config) {
            /*
                unset realizado em todas as variaveis usadadas dentro do foreach
                para evitar elses e possiveis problemas em relação a memoria
            */
            unset($elemento, $type, $class, $maxlength, $readonly, $value, $options, $title, $width, $span, $extra, $inptc, $array, $icon, $i, $lupa, $lupa_extra, $checks, $radios, $style_modelo);

            /*
                $elemento -> tipo do elemento
                $type -> caso seja um input irá pegar o type do input
            */
            list($elemento, $type) = explode("/", $config["type"]);

            /*
                pega os atributos do campo
            */

            $class = $config["class"];
            $width = $config["width"];
            $span  = $config["span"];

            if (isset($config["id"])) {
                if (strlen($config["id"]) > 0) {
                    $key_id = "id='{$config["id"]}'";
                }
            } else {
                $key_id = "id='{$key}'";
            }

            if($elemento == "textarea"){
                $cols = $config["cols"];
                $rows = $config["rows"];
            }

            if ($config["title"]) {
                $title = "title='{$config["title"]}'";
            }

            if ($config["readonly"] == true) {
                $readonly = "readonly='true'";
            }

            if ($config["inptc"]) {
                $inptc = $config["inptc"];
            }

            if (isset($config["mostra_opcao_vazia"])) {
                $mostra_opcao_vazia = $config["mostra_opcao_vazia"];
            } else {
                $mostra_opcao_vazia = true;
            }

            if (count($config["extra"]) > 0) {
                foreach ($config["extra"] as $attr => $attrValue) {
                    $extra[] = "{$attr}='{$attrValue}'";

                    if ($attr == "modelo" && $attrValue == true) {
                        $extra[] = "disabled='disabled'";
                    }

                    if ($config['type'] == "select" and $attr == "multiple") {
                        $mostra_opcao_vazia = false;
                    }
                }

                $extra = implode(" ", $extra);
            }

            if ($config['type'] == "select" and substr($key, -2, 2) == "[]") {
                $mostra_opcao_vazia = false;
            }

            if ($config["icon-append"]) {
                $icon["append"]["class"] = "input-append";

                switch (key($config["icon-append"])) {
                    case 'icon':
                        $i = "<i class='{$config["icon-append"]["icon"]}'></i>";
                        break;

                    case 'text':
                        $i = $config["icon-append"]["text"];
                        break;
                }

                $icon["append"]["span"]  = "<span class='add-on'>{$i}</span>";
            }

            if ($config["icon-prepend"]) {
                $icon["prepend"]["class"] = "input-prepend";

                switch (key($config["icon-prepend"])) {
                    case 'icon':
                        $i = "<i class='{$config["icon-prepend"]["icon"]}'></i>";
                        break;

                    case 'text':
                        $i = $config["icon-prepend"]["text"];
                        break;
                }

                $icon["prepend"]["span"]  = "<span class='add-on'>{$i}</span>";
            }

            if (is_array($config["lupa"]) && count($config["lupa"]) > 0) {
                $icon["append"]["class"] = "input-append";
                $icon["append"]["span"]  = "<span class='add-on' rel='{$config["lupa"]["name"]}' ><i class='icon-search'></i></span>";

                if (count($config["lupa"]["extra"]) > 0) {
                    foreach ($config["lupa"]["extra"] as $attr => $attrValue) {
                        $lupa_extra[] = "{$attr}='{$attrValue}'";
                    }

                    $lupa_extra = implode(" ", $lupa_extra);
                }

                $lupa = "<input type='hidden' name='lupa_config' tipo='{$config["lupa"]["tipo"]}' parametro='{$config["lupa"]["parametro"]}' {$lupa_extra} />";
            }

            if (is_array($config["popover"]) && count($config["popover"]) > 0) {
                $icon["append"]["class"] = "input-append";
                $icon["append"]["span"]  = "<span class='add-on'><i id='{$config["popover"]["id"]}'  rel='popover' data-placement='top' data-trigger='hover' data-delay='500' title='Informação' class='icon-question-sign' data-content='{$config["popover"]["msg"]}' class='icon-question-sign'></i></span>";
            }

            /*
                Cria o elemento
            */
            switch ($elemento) {
                case "file":
                    /*
                        Como maxlength é um atributo especifico do input ele so é pego se o elemento for input
                    */


                    /*
                        Populate
                    */
                    $value = getValue($key);

                    $elemento = "<input type='{$type}' {$key_id} name='{$key}' class='span12 {$class}' {$maxlength} {$readonly} {$title} {$extra} value=\"{$value}\" />";
                    break;
                case "input":
                    /*
                        Como maxlength é um atributo especifico do input ele so é pego se o elemento for input
                    */
                    if ($config["maxlength"]) {
                        $maxlength = "maxlength='{$config["maxlength"]}'";
                    }

                    /*
                        Populate
                    */
                    $value = getValue($key);

                    $elemento = "<input type='{$type}' {$key_id} name='{$key}' class='span12 {$class}' {$maxlength} {$readonly} {$title} {$extra} value=\"{$value}\" />";
                    break;

                case "textarea":
                    /*
                        Populate
                    */
                    $value = nl2br(getValue($key));

                    $elemento = "<textarea {$key_id} cols='{$cols}' rows='{$rows}' name='{$key}' value='{$value}' {$extra} >$value</textarea>";
                break;

                case "checkbox":
                    /*
                        Verifica se tem checkbox a ser criado nesta familia de check
                    */
                    if (count($config["checks"]) > 0) {
                        /*
                            Populate
                        */
                        $xvalue = getValue($key);

                        if (is_array($xvalue)) {
                            $array = true;
                        }

                        foreach ($config["checks"] as $value => $label) {
                            /*
                                Verifica se o checkbox recebera o atributo CHECKED
                            */
                            if ($array) {
                                $checked = (in_array($value, $xvalue)) ? "CHECKED" : "";
                            } else {
                                $checked = ($value == $xvalue) ? "CHECKED" : "";
                            }

                            /*
                                Cria o elemento checkbox
                            */
                            $checks[] = "<label class='checkbox'><input type='checkbox' class='{$class}' name='{$key}[]' value='{$value}' {$checked} {$readonly} {$title} {$extra} /> {$label}</label>";
                        }
                    }

                    if (count($checks) > 0) {
                        $elemento = implode("&nbsp;&nbsp;&nbsp;", $checks);
                    }
                    break;

                case "radio":
                    /*
                        Verifica se tem radio a ser criado nesta familia de radio
                    */
                    if (count($config["radios"]) > 0) {
                        /*
                            Populate
                        */
                        $xvalue = getValue($key);

                        foreach ($config["radios"] as $value => $label) {
                            /*
                                Verifica se o radio recebera o atributo CHECKED
                            */
                            $checked = ($value == $xvalue) ? "CHECKED" : "";

                            /*
                                Cria o elemento radio
                            */
                            $radios[] = "<label class='radio' ><input type='radio' name='{$key}' value='{$value}' {$checked} {$readonly} {$title} {$extra} /> {$label}</label>";
                        }
                    }

                    if (count($radios) > 0) {
                        $elemento = implode("&nbsp;&nbsp;&nbsp;", $radios);
                    }
                    break;

                case "select":
                    /*
                        Verifica se tem options a serem criados no select
                    */
                        
                    $multiple = "";
                    
                    if (isset($config["multiple"])) {
                        $multiple = "multiple";
                    }

                    if (count($config["options"]) > 0) {
                        /*
                            Populate
                        */
                        if (isset($config["extra"]["multiple"])) {
                            $xvalue = getValue(preg_replace("/\[|\]/", "", $key));
                        } else {
                            $xvalue = getValue($key);
                        }

                        if (isset($config["options"]["sql_query"])) {
                            $options_sql = pg_query($con, $config["options"]["sql_query"]);

                            if (pg_num_rows($options_sql) > 0) {
                                while ($option = pg_fetch_object($options_sql)) {
                                    if (isset($config["options"]["extra"])) {
                                        $extra = $config["options"]["extra"]."='".$option->$config["options"]["extra"]."'";
                                    }

                                    if (is_array($xvalue)) {
                                        $selected = (in_array($option->value, $xvalue)) ? "SELECTED" : "";
                                    } else {
                                        $selected = ($option->value == $xvalue) ? "SELECTED" : "";
                                    }

                                    $options[] = "<option value='{$option->value}' {$selected} ${extra} >{$option->label}</option>";
                                }
                            }
                        } else {
                            foreach ($config["options"] as $value => $label) {
                                /*
                                    Verifica se $label é array para colocar atributos extras
                                */
                                if(is_array($label)){
                                    $option_extra = array();
                                    /*
                                    Verifica se o option recebera o atributo SELECTED
                                    */

                                    if (is_array($xvalue)) {
                                        $selected = (in_array($value, $xvalue)) ? "SELECTED" : "";
                                    } else {
                                        $selected = ($value == $xvalue) ? "SELECTED" : "";
                                    }

                                    if (count($label["extra"]) > 0) {
                                        foreach ($label["extra"] as $attr => $attrValue) {

                                            $option_extra[] = "{$attr}='{$attrValue}'";

                                        }

                                        $param_extra = implode(" ", $option_extra);
                                    }
                                    /*
                                        Cria o elemento option
                                    */
                                    $options[] = "<option value='{$value}' {$param_extra} {$selected} >{$label['label']}</option>";
                                }else{
                                    /*
                                    Verifica se o option recebera o atributo SELECTED
                                    */
                                    if (is_array($xvalue)) {
                                        $selected = (in_array($value, $xvalue)) ? "SELECTED" : "";
                                    } else {
                                        $selected = (strlen($xvalue) > 0 && $value == $xvalue) ? "SELECTED" : "";
                                    }

                                    /*
                                        Cria o elemento option
                                    */
                                    $options[] = "<option value='{$value}' {$selected} >{$label}</option>";
                                }

                            }
                        }
                    }

                    /*
                        Cria o elemento select
                    */
                    $elemento = "<select {$multiple} {$key_id} name='{$key}' class='span12 {$class}' {$readonly} {$title} {$extra} >";
                    if (true === $mostra_opcao_vazia) {
                        $elemento.= "<option value=''>Selecione</option>";
                    }
                    $elemento.= implode("", $options)."</select>";
                    break;
            }

            /*
                Armazena todas as configurações do elemento
                tamanho, icone, required, label etc
            */

            if (strlen($config["label"]) > 0) {
                $html[$k]["label"] = "<label class='control-label' for='{$key}'>{$config['label']}</label>";
            }

            $html[$k]["campo"] = $elemento;
            $html[$k]["width"] = $width;
            $html[$k]["span"]  = $span;

            if ($type == "hidden") {
                $html[$k]["sem_html"] = true;
            }

            if ($inptc) {
                $html[$k]["inptc"] = $inptc;
            }

            if ($config["required"] == true) {
                $html[$k]["required"] = "<h5 class='asteristico'>*</h5>";
            }

            /*
                se existe o id do campo dentro da array $msg_erro["campos"] quer armazena os campos que devem receber a
                class de erro seta a configuração error como true isso irá adicionar a class error no elemento
            */
            if(is_array($msg_erro["campos"])){
                if (in_array($key, $msg_erro["campos"])) {
                    $html[$k]["error"] = true;
                }
            }

            if (isset($icon)) {
                $html[$k]["icon"] = $icon;
            }

            if (isset($lupa)) {
                $html[$k]["lupa"] = $lupa;
            }

            $k++;
        }

        /*
            $i -> contador utilizado para fazer a soma de tamanhos para controle de criação de linhas
            $x -> contador utilizado para contagem de elementos para controle do fechamento da ultima linha
        */
        $i = 0;
        $x = 1;

        /*
            Monta o form
        */

        foreach ($html as $elemento) {
            if ($elemento["sem_html"] == true) {
                echo $elemento["campo"];

                $x++;

                continue;
            }

            /*
                if {
                    Se $i for 0 cria a primeira linha do form ja com a margem
                    aqui o $i ja passa a ser 2(tamanho da margem) + tamanho do espaço do elemento
                }

                else if {
                    Se $i + tamanho do espaço do elemento a ser criado for maior que 10 fecha a linha atual ja com margem da
                    direita
                    e cria uma nova linha ja com a margem para este elemento
                    e o $i passa a ser $i 2(tamanho da margem) + tamanho do espaço do elemento
                }

                else {
                    $i + tamanho do espaço do elemento
                }
            */
            if ($i == 0) {
                if ($no_margin == true) {
                    echo "<div class='row-fluid'><div class='span1'></div>";
                } else {
                    echo "<div class='row-fluid'><div class='span2'></div>";
                }

                $i = 2 +  $elemento["span"];
            } else if ((($i + $elemento["span"]) > 10 && $no_margin == false) || (($i + $elemento["span"]) > 12 && $no_margin == true)) {
                if ($no_margin == true) {
                    echo "<div class='span1'></div></div>";
                    echo "<div class='row-fluid'><div class='span1'></div>";
                } else {
                    echo "<div class='span2'></div></div>";
                    echo "<div class='row-fluid'><div class='span2'></div>";
                }

                $i = 2 +  $elemento["span"];
            } else {
                $i = $i + $elemento["span"];
            }

            /*
                Se error for true adiciona a class error no elemento
            */
            $classError = ($elemento["error"] == true) ? "error" : "";

            /*
                Verifica se o tamanho do input irá usar o tamanho padrão do bootstrap ou o inptc
            */
            if ($elemento["inptc"]) {
                $width = "inptc{$elemento['inptc']}";
            } else {
                $width = "span{$elemento['width']}";
            }

            /*
                Elemento
            */
            echo "<div class='span{$elemento["span"]}'>
                <div class='control-group {$classError}'>
                    {$elemento['label']}
                    <div class='controls controls-row'>
                        <div class='{$width} {$elemento['icon']['prepend']['class']} {$elemento['icon']['append']['class']}'>
                            {$elemento['required']}
                            {$elemento['icon']['prepend']['span']}
                            {$elemento['campo']}
                            {$elemento['icon']['append']['span']}
                            {$elemento['lupa']}
                        </div>
                    </div>
                </div>
            </div>";

            /*
                Verifica se é o utlimo item da array para fazer o fechamento da linha
            */
            if ($x == count($html)) {
                if ($no_margin == true) {
                    echo "<div class='span1'></div></div>";
                } else {
                    echo "<div class='span2'></div></div>";
                }
            } else {
                $x++;
            }
        }
    }
}

if (!function_exists('array_map_recursive')) {
    function array_map_recursive($callback, $array) {
        foreach ($array as $key => $value) {
            if (is_array($array[$key])) {
                $array[$key] = array_map_recursive($callback, $array[$key]);
            }
            else {
                $array[$key] = call_user_func($callback, $array[$key]);
            }
        }
        return $array;
    }
}

if (!function_exists('location')) {
    function location ($url) {
        echo "<script> window.location = '{$url}'; </script>";
    }
}

if(!function_exists('retira_acentos')){
    function retira_acentos( $texto ){
        $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç","º","&","%","$","?","@", "'" );
        $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C","_","_","_","_","_","_","" );
        return str_replace( $array1, $array2, $texto );
    }
}


 if (!function_exists('moneyDB')){
     function moneyDB($money){

         $money = preg_replace("/[^0-9.,]/", "", $money);
         $money = str_replace(".", "", $money);
         $money = str_replace(",", ".", $money);

         if(empty($money))
             return "null";
         else
             return $money;
     }
}

#   Declaração de variáveis usadas normalmente
#   Dias e Meses do ano. Os dias começam com o '0' em Domingo, para ficar
#   igual o padrão do pSQL e PHP, fica mais fácil de mexer
$Dias = array(
    'pt-br' => array(
        0 => 'Domingo',      'Segunda-feira', 'Terça-feira',
             'Quarta-feira', 'Quinta-feira',  'Sexta-feira',
             'Sábado',       'Domingo'),
    'es'    => array(
        0 => 'Domingo', 'Lunes',   'Martes',   'Miércoles',
             'Jueves',  'Viernes', 'Sábado' ),
    'en-us' => array(
        0 => 'Sunday',  'Monday', 'Tuesday', 'Wednesday',
             'Thursday','Friday', 'Saturday')
);

$meses_idioma = array(
    'pt-br' => array(
        1 => 'Janeiro',  'Fevereiro','Março',   'Abril',
             'Maio',     'Junho',    'Julho',   'Agosto',
             'Setembro', 'Outubro',  'Novembro','Dezembro'),
    'es'    => array(
        1 => 'Enero',      'Febrero', 'Marzo',     'Abril',
             'Mayo',       'Junio',   'Julio',     'Agosto',
             'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'),
    'en-us' => array(
        1 => 'January',   'February', 'March',    'April',
             'May',       'June',     'July',     'August',
             'September', 'October',  'November', 'December')
);

$estadosBR = array(
    'AC' => 'Acre',             'AL' => 'Alagoas',             'AM' => 'Amazonas',
    'AP' => 'Amapá',            'BA' => 'Bahia',               'CE' => 'Ceará',
    'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo',      'GO' => 'Goiás',
    'MA' => 'Maranhão',         'MG' => 'Minas Gerais',        'MS' => 'Mato Grosso do Sul',
    'MT' => 'Mato Grosso',      'PA' => 'Pará',                'PB' => 'Paraíba',
    'PE' => 'Pernambuco',       'PI' => 'Piauí',               'PR' => 'Paraná',
    'RJ' => 'Rio de Janeiro',   'RN' => 'Rio Grande do Norte', 'RO'=>'Rondônia',
    'RR' => 'Roraima',          'RS' => 'Rio Grande do Sul',   'SC' => 'Santa Catarina',
    'SE' => 'Sergipe',          'SP' => 'São Paulo',           'TO' => 'Tocantins'
);

$array_paises = function() {
    global $con;

    $sql = "SELECT DISTINCT tbl_pais.pais,
                            tbl_pais.nome
            FROM tbl_pais
            LEFT JOIN tbl_estado_exterior ON tbl_estado_exterior.pais = tbl_pais.pais
            LEFT JOIN tbl_estado          ON tbl_estado.pais = tbl_pais.pais
            WHERE (
                tbl_estado_exterior.estado IS NOT NULL 
                OR tbl_estado.estado IS NOT NULL
            )
            ORDER BY tbl_pais.nome";
    $res = pg_query($con, $sql);

    while ($dados = pg_fetch_object($res)) {
        $arrPaises[$dados->pais] = $dados->nome;
    }

    return $arrPaises;

};


$array_estados = function($pais = 'BR') {

    global $con;
    
    $sql = "
        SELECT DISTINCT *
	FROM (
	    SELECT
	    	estado,
            	fn_retira_especiais(nome) AS nome
            FROM tbl_estado
            WHERE visivel IS TRUE 
            AND tbl_estado.pais = '".strtoupper($pais)."' 
	    UNION
	    SELECT
            	estado,
            	fn_retira_especiais(nome) AS nome
            FROM tbl_estado_exterior
            WHERE visivel IS TRUE 
            AND pais = '".strtoupper($pais)."'
	) x
	ORDER BY estado;
    ";

    $res = pg_query($con,$sql);
    while ($result = pg_fetch_object($res)) {
        $oArrayEstados[$result->estado] = $result->nome;
    }
    
    return $oArrayEstados;
};

function getEstadosNacional() {

    global $con;

    $sql = "SELECT estado, nome 
            FROM tbl_estado 
            WHERE pais = 'BR'";
    
    $re = pg_query($con, $sql);

    $provincias = [];

    for ($i = 0; $i < pg_numrows($re); $i++) {

       $nome_provincia = pg_fetch_result($re, $i, nome);

       $sigla = pg_fetch_result($re, $i, estado);

       $provincias[$sigla] = $nome_provincia;
    }

   return $provincias;
}

function getProvinciasExterior($pais) {

    global $con;

    $sql = "SELECT estado AS sigla, nome 
            FROM tbl_estado_exterior 
            WHERE pais = '{$pais}'";

    $re = pg_query($con, $sql);

    $provincias = [];

    for ($i = 0; $i < pg_numrows($re); $i++) {

       $nome_provincia  = pg_fetch_result($re, $i, nome);
       $sigla_provincia = pg_fetch_result($re, $i, sigla);

       $provincias[$sigla_provincia] = $nome_provincia;
    }

    asort($provincias);

    return $provincias;

}

function getPostoInfo($postoId) {

    global $con;

    $query = "SELECT p.cidade, p.pais, pf.parametros_adicionais 
              FROM tbl_posto AS p
              JOIN tbl_posto_fabrica AS pf ON (pf.posto = p.posto) 
              WHERE p.posto = $postoId";

    $res = pg_query($con, $query);

    $parametros = pg_fetch_result($res, 0, parametros_adicionais);
    $parametros = json_decode($parametros, true);

    $posto['pais']      = pg_fetch_result($res, 0, pais);
    $posto['provincia'] = $parametros['estado'];
    $posto['cidade']    = pg_fetch_result($res, 0, cidade); 

    return $posto;

}

$array_pais_estado = function() {

    global $con;

    $sql = "SELECT DISTINCT ON (nome) * FROM (
                SELECT pais, UPPER(nome) as nome, estado
                FROM tbl_estado_exterior
                WHERE visivel
                UNION
                SELECT pais, UPPER(nome) as nome, estado
                FROM tbl_estado
                WHERE visivel
            ) estados
            ";
    $res = pg_query($con, $sql);

    while ($dados = pg_fetch_object($res)) {
        $arrPaisEstado[$dados->pais][] = [$dados->estado => $dados->nome];
    }

    return $arrPaisEstado;

};

$array_pais = array(
    "Brazil"                        => "Brazil",
    "Albania"                       => "Albania",
    "Algeria"                       => "Algeria",
    "Andorra"                       => "Andorra",
    "Angola"                        => "Angola",
    "Antigua and Barbuda"           => "Antigua and Barbuda",
    "Argentina"                     => "Argentina",
    "Armenia"                       => "Armenia",
    "Australia"                     => "Australia",
    "Austria"                       => "Austria",
    "Azerbaijan"                    => "Azerbaijan",
    "Bahamas"                       => "Bahamas",
    "Bahrain"                       => "Bahrain",
    "Bangladesh"                    => "Bangladesh",
    "Barbados"                      => "Barbados",
    "Belarus"                       => "Belarus",
    "Belgium"                       => "Belgium",
    "Belize"                        => "Belize",
    "Benin"                         => "Benin",
    "Bhutan"                        => "Bhutan",
    "Bolivia"                       => "Bolivia",
    "Bosnia and Herzegivona"        => "Bosnia and Herzegivona",
    "Botswana"                      => "Botswana",
    "Brazil"                        => "Brazil",
    "Brunei Darussalam"             => "Brunei Darussalam",
    "Bulgaria"                      => "Bulgaria",
    "Burkina Faso"                  => "Burkina Faso",
    "Burundi"                       => "Burundi",
    "Cambodia"                      => "Cambodia",
    "Cameroon"                      => "Cameroon",
    "Canada"                        => "Canada",
    "Canary Islands (Spain)"        => "Canary Islands (Spain)",
    "Cape Verde"                    => "Cape Verde",
    "Central African Republic"      => "Central African Republic",
    "Ceuta (Spain)"                 => "Ceuta (Spain)",
    "Chad"                          => "Chad",
    "Chile"                         => "Chile",
    "China, People's Republic of"   => "China, People's Republic of",
    "Colombia"                      => "Colombia",
    "Comoros"                       => "Comoros",
    "Congo, Democratic Rep. of the" => "Congo, Democratic Rep. of the",
    "Congo, Republic of the"        => "Congo, Republic of the",
    "Costa Rica"                    => "Costa Rica",
    "Cote d'Ivoire"                 => "Cote d'Ivoire",
    "Croatia"                       => "Croatia",
    "Cuba"                          => "Cuba",
    "Cyprus"                        => "Cyprus",
    "Czech Republic"                => "Czech Republic",
    "Denmark"                       => "Denmark",
    "Deunion"                       => "Deunion",
    "Djibouti"                      => "Djibouti",
    "Dominica"                      => "Dominica",
    "Dominican Republic"            => "Dominican Republic",
    "Dubai"                         => "Dubai",
    "Ecuador"                       => "Ecuador",
    "Egypt"                         => "Egypt",
    "El Salvador"                   => "El Salvador",
    "Equatorial Guinea"             => "Equatorial Guinea",
    "Eritrea"                       => "Eritrea",
    "Estonia"                       => "Estonia",
    "Ethiopia"                      => "Ethiopia",
    "Fiji"                          => "Fiji",
    "Finland"                       => "Finland",
    "France"                        => "France",
    "French Polynesia"              => "French Polynesia",
    "Gabon"                         => "Gabon",
    "Gambia"                        => "Gambia",
    "Georgia"                       => "Georgia",
    "Germany"                       => "Germany",
    "Ghana"                         => "Ghana",
    "Greece"                        => "Greece",
    "Grenada"                       => "Grenada",
    "Guadeloupe"                    => "Guadeloupe",
    "Guatemala"                     => "Guatemala",
    "Guinea"                        => "Guinea",
    "Guinea-Bissau"                 => "Guinea-Bissau",
    "Guyana"                        => "Guyana",
    "Haiti"                         => "Haiti",
    "Honduras"                      => "Honduras",
    "Hungary"                       => "Hungary",
    "Iceland"                       => "Iceland",
    "India"                         => "India",
    "Indonesia"                     => "Indonesia",
    "Iran"                          => "Iran",
    "Iraq"                          => "Iraq",
    "Ireland"                       => "Ireland",
    "Israel"                        => "Israel",
    "Italy"                         => "Italy",
    "Jamaica"                       => "Jamaica",
    "Japan"                         => "Japan",
    "Jordan"                        => "Jordan",
    "Kazakhstan"                    => "Kazakhstan",
    "Kenya"                         => "Kenya",
    "Korea, Republic of"            => "Korea, Republic of",
    "Kuwait"                        => "Kuwait",
    "Kyrgystan"                     => "Kyrgystan",
    "Latvia"                        => "Latvia",
    "Lebanon"                       => "Lebanon",
    "Lesotho"                       => "Lesotho",
    "Liberia"                       => "Liberia",
    "Libya"                         => "Libya",
    "Lithuania"                     => "Lithuania",
    "Macedonia"                     => "Macedonia",
    "Madagascar"                    => "Madagascar",
    "Madeira (Portugal)"            => "Madeira (Portugal)",
    "Malawi"                        => "Malawi",
    "Malaysia"                      => "Malaysia",
    "Maldives"                      => "Maldives",
    "Mali"                          => "Mali",
    "Malta"                         => "Malta",
    "Marshall Islands"              => "Marshall Islands",
    "Martinique"                    => "Martinique",
    "Mauritania"                    => "Mauritania",
    "Mauritius"                     => "Mauritius",
    "Mayotte (France)"              => "Mayotte (France)",
    "Melilla (Spain)"               => "Melilla (Spain)",
    "Mexico"                        => "Mexico",
    "Micronesia, Fed. States of"    => "Micronesia, Fed. States of",
    "Middle East"                   => "Middle East",
    "Moldova"                       => "Moldova",
    "Monaco"                        => "Monaco",
    "Mongolia"                      => "Mongolia",
    "Montenegro"                    => "Montenegro",
    "Morocco"                       => "Morocco",
    "Mozambique"                    => "Mozambique",
    "Myanmar"                       => "Myanmar",
    "Namibia"                       => "Namibia",
    "Nepal"                         => "Nepal",
    "Netherlands"                   => "Netherlands",
    "New Caledonia"                 => "New Caledonia",
    "New Zealand"                   => "New Zealand",
    "Nicaragua"                     => "Nicaragua",
    "Niger"                         => "Niger",
    "Nigeria"                       => "Nigeria",
    "Norway"                        => "Norway",
    "Oman"                          => "Oman",
    "Pakistan"                      => "Pakistan",
    "Palau"                         => "Palau",
    "Panama"                        => "Panama",
    "Papua New Guinea"              => "Papua New Guinea",
    "Paraguay"                      => "Paraguay",
    "Peru"                          => "Peru",
    "Philippines"                   => "Philippines",
    "Poland"                        => "Poland",
    "Portugal"                      => "Portugal",
    "Qatar"                         => "Qatar",
    "Reunion (France)"              => "Reunion (France)",
    "Romania"                       => "Romania",
    "Russia"                        => "Russia",
    "Rwanda"                        => "Rwanda",
    "Samoa"                         => "Samoa",
    "Sao Tome and Principe"         => "Sao Tome and Principe",
    "Saudi Arabia"                  => "Saudi Arabia",
    "Senegal"                       => "Senegal",
    "Serbia"                        => "Serbia",
    "Seychelles"                    => "Seychelles",
    "Sierra Leone"                  => "Sierra Leone",
    "Singapore"                     => "Singapore",
    "Slovakia"                      => "Slovakia",
    "Slovenia"                      => "Slovenia",
    "Solomon Islands"               => "Solomon Islands",
    "Somalia"                       => "Somalia",
    "South Africa"                  => "South Africa",
    "Spain"                         => "Spain",
    "Sri Lanka"                     => "Sri Lanka",
    "St Helena (UK)"                => "St Helena (UK)",
    "St Kitts and Nevis"            => "St Kitts and Nevis",
    "St Lucia"                      => "St Lucia",
    "St Vincent and the Grenadines" => "St Vincent and the Grenadines",
    "Sudan"                         => "Sudan",
    "Suriname"                      => "Suriname",
    "Swaziland"                     => "Swaziland",
    "Sweden"                        => "Sweden",
    "Switzerland"                   => "Switzerland",
    "Syria"                         => "Syria",
    "Tahiti"                        => "Tahiti",
    "Taiwan"                        => "Taiwan",
    "Tajikistan"                    => "Tajikistan",
    "Tanzania, United Republic of"  => "Tanzania, United Republic of",
    "Tchad"                         => "Tchad",
    "Thailand"                      => "Thailand",
    "Togo"                          => "Togo",
    "Trinidad and Tobago"           => "Trinidad and Tobago",
    "Tunisia"                       => "Tunisia",
    "Turkey"                        => "Turkey",
    "Turkmenistan"                  => "Turkmenistan",
    "Uganda"                        => "Uganda",
    "Ukraine"                       => "Ukraine",
    "United Arab Emirates"          => "United Arab Emirates",
    "United Kingdom"                => "United Kingdom",
    "United States of America"      => "United States of America",
    "Uruguay"                       => "Uruguay",
    "Uzbekistan"                    => "Uzbekistan",
    "Vanuatu"                       => "Vanuatu",
    "Venezuela"                     => "Venezuela",
    "Vietnam"                       => "Vietnam",
    "Western Sahara"                => "Western Sahara",
    "Yemen"                         => "Yemen",
    "Zambia"                        => "Zambia",
    "Zimbabwe"                      => "Zimbabwe"
);

function createThumb ($file) {
    $basename = basename($file);
    $basename = preg_replace("/\?+.*/", "", $basename);
    $type     = strtolower(preg_replace("/.+\./", "", $basename));

    list($width, $height) = getimagesize($file);

    $widthNew  = 100;
    $heightNew = 90;

    if ($width < $widthNew) {
        $widthNew = $width;
    }

    if ($height < $heightNew) {
        $heightNew = $height;
    }

    $thumb = imagecreatetruecolor($widthNew, $heightNew);

    switch ($type) {
        case "jpeg":
        case "jpg":
            $source = imagecreatefromjpeg($file);
            break;

        case "png":
            $source = imagecreatefrompng($file);
            break;

        case "gif":
            $source = imagecreatefromgif($file);
            break;
    }

    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $widthNew, $heightNew, $width, $height);

    if (file_exists("../osImagem/thumb/{$basename}")) {
        system("rm -rf ../osImagem/thumb/{$basename}");
    }

    switch ($type) {
        case "jpeg":
        case "jpg":
            $fileMini = imagejpeg($thumb, "../osImagem/thumb/{$basename}");
            break;

        case "png":
            $fileMini = imagepng($thumb, "../osImagem/thumb/{$basename}");
            break;

        case "gif":
            $fileMini = imagegif($thumb, "../osImagem/thumb/{$basename}");
            break;
    }

    return "../osImagem/thumb/{$basename}";

}


function auditorLog($primary_key,$auditor_antes,$auditor_depois, $table, $program_url = null, $action){

    global $login_fabrica, $login_admin, $login_posto;

    $auditor_ip = $_SERVER['REMOTE_ADDR'];
    if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARTDED_FOR'] != '') {
        $auditor_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $auditor_ip = $_SERVER['REMOTE_ADDR'];
    }

    if (strlen ($auditor_ip) == 0) {
        $auditor_ip = "0.0.0.0";
    }

    //Tratativa para usar
    $identificacao = $login_admin;
    $user_level = "admin";
    if($login_admin == null OR $login_admin == ""){
        $identificacao = $login_posto;
        $user_level = "posto";
    }

    $auditor_url_api = "https://api2.telecontrol.com.br/auditor/auditor";

    $auditor_array_dados = array (

        "application" => "02b970c30fa7b8748d426f9b9ec5fe70",
        "table"       => $table,
        "ip_access"   => "$auditor_ip",
        "owner"       => "$login_fabrica",
        "action"      => $action,
        "program_url" => $program_url,
        "primary_key" => $login_fabrica . "*" . $primary_key,
        "user"        => "$identificacao",
        "user_level"  => "$user_level",
        "content"     => json_encode(
            array(
                "antes" => array_map('utf8_encode', $auditor_antes),
                "depois" => array_map('utf8_encode', $auditor_depois),
            )
        )
    );

    $auditor_json_dados = json_encode($auditor_array_dados);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $auditor_url_api);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch,CURLOPT_TIMEOUT,2);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $auditor_json_dados);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
    $response = curl_exec($ch);
    curl_close($ch);
}

function is_json($string) {
    return !empty($string) && is_string($string) && is_array(json_decode($string, true)) && json_last_error() == 0;
}

function isFabrica() {
    global $login_fabrica;
    $_lf = $login_fabrica == 172 ? 11 : $login_fabrica;
    return in_array($_lf, func_get_args());
}

function ifFabrica() {
    global $login_fabrica;
    $_lf = $login_fabrica == 172 ? 11 : $login_fabrica;

    $fargs  = func_get_args();
    $fargsc = count($fargs);

    if (count(array_filter($fargs, 'is_int')) == $fargsc) {
        return in_array($_lf, func_get_args());
    }

    if (!function_exists('_is_in'))
        include_once(__DIR__ . DIRECTORY_SEPARATOR . 'helpdesk/mlg_funciones.php');
    return _is_in($_lf, $fargs);
}

$estadosBrasil = $array_estados();

function pecaInativaBlack($peca, $qtde = null){
	global $con, $login_fabrica, $login_posto;

	if (!empty($qtde)) {
		$sql = "SELECT peca ,qtde
					FROM tbl_estoque_posto
					WHERE fabrica = {$login_fabrica}
						AND posto = {$login_posto}
						AND peca = {$peca}
						AND qtde > 0;";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0) {
			$xqtde = pg_fetch_result($res, 0, qtde);
			if ($xqtde >= $qtde) {
				//estoque suficiente
				return true;
			}else{
				//estoque insuficiente
				return false;
			}

		}else{
			//não tem estoque
			return false;
		}
	}else{
		$sql = "SELECT peca
					FROM tbl_estoque_posto
					WHERE fabrica = {$login_fabrica}
						AND posto = {$login_posto}
						AND peca = {$peca}
						AND qtde > 0;";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0) {
			return true;
		}else{
			return false;
		}
	}
}

function verificaAlteracaoDadosAtendimento($hd_chamado,$os, $os_item = null){
    global $con, $login_fabrica, $login_posto;

    if(empty($os_item)){
        $campos_hd = "tbl_hd_chamado_extra.nome,
                        tbl_hd_chamado_extra.endereco ,
                        tbl_hd_chamado_extra.numero ,
                        tbl_hd_chamado_extra.complemento ,
                        tbl_hd_chamado_extra.bairro ,
                        tbl_hd_chamado_extra.cep ,
                        tbl_hd_chamado_extra.fone ,
                        tbl_hd_chamado_extra.email,
                        tbl_hd_chamado_extra.cpf ,
                        tbl_cidade.nome                                    AS cidade_nome,
                        tbl_cidade.estado                                  AS estado,
                        tbl_produto.voltagem                               As produto_voltagem,
                        tbl_produto.referencia                             AS produto_referencia,
                        tbl_produto.descricao                              AS produto_nome,
                        tbl_hd_chamado_extra.defeito_reclamado_descricao,
                        to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') AS data_nf,
                        tbl_hd_chamado_extra.nota_fiscal                   AS nota_fiscal,
                        tbl_hd_chamado_extra.qtde_km,
                        tbl_hd_chamado_extra.revenda_cnpj,
                        tbl_hd_chamado_extra.revenda_nome";
    }else{
        $campos_hd = "  tbl_produto.referencia                             AS produto_referencia,
                        tbl_produto.descricao                              AS produto_nome,
                        tbl_hd_chamado_extra.qtde_km";
    }

    $sql = "SELECT $campos_hd
                FROM tbl_hd_chamado_extra
                LEFT JOIN tbl_produto       ON tbl_produto.produto        = tbl_hd_chamado_extra.produto AND tbl_produto.fabrica_i = {$login_fabrica}
                LEFT JOIN tbl_cidade        ON tbl_cidade.cidade          = tbl_hd_chamado_extra.cidade
                WHERE tbl_hd_chamado_extra.hd_chamado = {$hd_chamado}";
    $res = pg_query($con,$sql);
    $dados_atendimento = pg_fetch_all($res);

    if(empty($os_item)){

        $campos_os = "tbl_os.consumidor_nome                AS nome,
                    tbl_os.consumidor_endereco              AS endereco,
                    tbl_os.consumidor_numero                AS numero,
                    tbl_os.consumidor_complemento           AS complemento,
                    tbl_os.consumidor_bairro                AS bairro,
                    tbl_os.consumidor_cep                   AS cep,
                    tbl_os.consumidor_fone                  AS fone,
                    tbl_os.consumidor_email                 AS email,
                    tbl_os.consumidor_cpf                   AS cpf,
                    tbl_os.consumidor_cidade                AS cidade_nome,
                    tbl_os.consumidor_estado                AS estado,
                    tbl_produto.voltagem                    AS produto_voltagem,
                    tbl_produto.referencia                  AS produto_referencia,
                    tbl_produto.descricao                   AS produto_nome,
                    tbl_os.defeito_reclamado_descricao      AS defeito_reclamado_descricao,
                    to_char(tbl_os.data_nf,'DD/MM/YYYY')    AS data_nf,
                    tbl_os.nota_fiscal,
                    tbl_os.qtde_km,
                    tbl_os.revenda_cnpj,
                    tbl_os.revenda_nome";
    }else{
        $campos_os = "tbl_produto.referencia                AS produto_referencia,
                    tbl_produto.descricao                   AS produto_nome,
                    tbl_os.qtde_km";
    }

    $sql = "SELECT  $campos_os
                    FROM tbl_os
                    JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
                    WHERE os = {$os}
                    AND fabrica = {$login_fabrica}
                    AND posto = {$login_posto}";
    $res = pg_query($con,$sql);
    $dados_os = pg_fetch_all($res);

    $teste = array();
    foreach ($dados_os[0] as $key => $value) {

        if(trim($dados_atendimento[0][$key]) <> trim($value)){

            $diff[$key] = $value;

        }

    }

    if(count($diff) > 0){

        $msg = "<b>As seguintes informações foram alteradas na Ordem de Serviço " . $os . ":</b> <br /><br />";

        foreach ($diff as $key => $value) {

            switch ($key) {
                case 'nome'                         : $campo = "Nome do Consumidor"; break;
                case 'endereco'                     : $campo = "Endereço do Consumidor"; break;
                case 'numero'                       : $campo = "Número do Consumidor"; break;
                case 'complemento'                  : $campo = "Complemento do Consumidor"; break;
                case 'bairro'                       : $campo = "Bairro do Consumidor"; break;
                case 'cep'                          : $campo = "CEP do Consumidor"; break;
                case 'fone'                         : $campo = "Telefone do Consumidor"; break;
                case 'email'                        : $campo = "Email do Consumidor"; break;
                case 'cidade_nome'                  : $campo = "Cidade do Consumidor"; break;
                case 'estado'                       : $campo = "Estado do Consumidor"; break;
                case 'produto_voltagem'             : $campo = "Voltagem do Produto"; break;
                case 'produto_referencia'           : $campo = "Referência do Produto"; break;
                case 'produto_nome'                 : $campo = "Nome do Produto"; break;
                case 'defeito_reclamado_descricao'  : $campo = "Defeito Reclamado"; break;
                case 'data_nf'                      : $campo = "Data da Nota Fiscal"; break;
                case 'nota_fiscal'                  : $campo = "Nota Fiscal"; break;
                case 'qtde_km'                      : $campo = "Deslocamento"; break;
                case 'revenda_cnpj'                 : $campo = "CNPJ da Revenda"; break;
                case 'revenda_nome'                 : $campo = "Nome da Revenda"; break;
            }

            if(strlen(trim($dados_os[0][$key])) > 0){
                $msg .= $campo . " foi alterado para " . $dados_os[0][$key] . "<br />";
            }

        }

        $sql = "SELECT status FROM tbl_hd_chamado WHERE hd_chamado = {$hd_chamado} AND fabrica_responsavel = {$login_fabrica}";
        $res = pg_query($con,$sql);
        $status_item = pg_fetch_result($res, 0, 'status');

        $sql = "INSERT INTO tbl_hd_chamado_item
                    (hd_chamado, interno, comentario, status_item)
                    VALUES
                    ({$hd_chamado}, TRUE, '{$msg}','{$status_item}')";
        $res = pg_query($con, $sql);

    }

}

function Utf8_ansi($valor='') {

    $utf8_ansi2 = array(
        "u00c0" =>"À",
        "u00c1" =>"Á",
        "u00c2" =>"Â",
        "u00c3" =>"Ã",
        "u00c4" =>"Ä",
        "u00c5" =>"Å",
        "u00c6" =>"Æ",
        "u00c7" =>"Ç",
        "u00c8" =>"È",
        "u00c9" =>"É",
        "u00ca" =>"Ê",
        "u00cb" =>"Ë",
        "u00cc" =>"Ì",
        "u00cd" =>"Í",
        "u00ce" =>"Î",
        "u00cf" =>"Ï",
        "u00d1" =>"Ñ",
        "u00d2" =>"Ò",
        "u00d3" =>"Ó",
        "u00d4" =>"Ô",
        "u00d5" =>"Õ",
        "u00d6" =>"Ö",
        "u00d8" =>"Ø",
        "u00d9" =>"Ù",
        "u00da" =>"Ú",
        "u00db" =>"Û",
        "u00dc" =>"Ü",
        "u00dd" =>"Ý",
        "u00df" =>"ß",
        "u00e0" =>"à",
        "u00e1" =>"á",
        "u00e2" =>"â",
        "u00e3" =>"ã",
        "u00e4" =>"ä",
        "u00e5" =>"å",
        "u00e6" =>"æ",
        "u00e7" =>"ç",
        "u00e8" =>"è",
        "u00e9" =>"é",
        "u00ea" =>"ê",
        "u00eb" =>"ë",
        "u00ec" =>"ì",
        "u00ed" =>"í",
        "u00ee" =>"î",
        "u00ef" =>"ï",
        "u00f0" =>"ð",
        "u00f1" =>"ñ",
        "u00f2" =>"ò",
        "u00f3" =>"ó",
        "u00f4" =>"ô",
        "u00f5" =>"õ",
        "u00f6" =>"ö",
        "u00f8" =>"ø",
        "u00f9" =>"ù",
        "u00fa" =>"ú",
        "u00fb" =>"û",
        "u00fc" =>"ü",
        "u00fd" =>"ý",
        "u00ff" =>"ÿ"
    );

    return strtr($valor, $utf8_ansi2);

}

function temPedido($os){
    global $con, $login_fabrica, $login_posto;

    //verifico se a OS tem item para gerar pedido e se não contem pedido.
    $sql_pedido = "SELECT   tbl_os.os,
                            tbl_os_item.servico_realizado,
                            tbl_servico_realizado.gera_pedido
                        FROM tbl_os
                            JOIN tbl_os_produto on tbl_os.os = tbl_os_produto.os
                            JOIN tbl_os_item on tbl_os_produto.os_produto = tbl_os_item.os_produto
                            JOIN tbl_servico_realizado on tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
                            LEFT JOIN tbl_pedido on tbl_os_item.pedido = tbl_pedido.pedido
                        WHERE   tbl_os.fabrica = $login_fabrica
                                AND tbl_os.os =  $os
                                AND tbl_servico_realizado.gera_pedido = 't'
                                AND tbl_pedido.pedido is null ;";
    $res_pedido = pg_query($con,$sql_pedido);

    if (pg_num_rows($res_pedido) > 0) {
        return $msg_erro = "A O.S. $os só pode ser finalizada após a geração do pedido da peça à fábrica. Favor aguardar até o dia seguinte. Dúvidas, contatar o fabricante.";
    }
}

if(!function_exists('relatorio_data')) {
	function relatorio_data($data_inicio, $data_fim){
		$data1 = new DateTime($data_inicio);
		$data2 = new DateTime($data_fim);

		$diferenca = $data1->diff($data2);
		$dias = $diferenca->days;

		$divisao = ($dias > 30) ? 30:($dias > 10) ? 10 : 3;
		$interval = ($dias > 30) ? "P30D": ($dias >10) ? "P10D":"P3D";


		$periodo = (int)$dias/$divisao;
		settype($periodo,"int");

		$datas = array();
		if($periodo == 0) {
			$datas[0][] = $data_inicio;
			$datas[0][] = $data_fim;

			return $datas;
		}
		for($i=0;$i<$periodo;$i++) {
			$data_inicial = $data1->format('Y-m-d');
			if($i > 0 ) {
				$data1->add(new DateInterval('P1D'));
				$data_inicial = $data1->format('Y-m-d');
			}
			$data1->add(new DateInterval($interval));
			$data_final = $data1->format('Y-m-d') ;
			if($data_inicial > $data_fim) break;
			$datas[$i][] = $data_inicial;

			if($i+1 == $periodo) {
				$datas[$i][]=$data_fim;
			}else{
				if($data_final > $data_fim) $data_final = $data_fim;
				$datas[$i][] = $data_final;
			}
		}
		return $datas;
	}

}

if(!function_exists('getDates')) {
    function getDates($data_inicio, $data_fim){
        $data_inicio    = $data_inicio;
        $data_inicio    = implode('-', array_reverse(explode('/', substr($data_inicio, 0, 10)))).substr($data_inicio, 10);
        $data_inicio    = new DateTime($data_inicio);
        
        $data_fim       = $data_fim;
        $data_fim       = implode('-', array_reverse(explode('/', substr($data_fim, 0, 10)))).substr($data_fim, 10);
        $data_fim       = new DateTime($data_fim);
        
        $data_range = array();
        while($data_inicio <= $data_fim){
            $data_range[] = $data_inicio->format('d/m/Y');
            $data_inicio = $data_inicio->modify('+1day');
        }
        return $data_range;
    }

}

function unique_multidim_array($array, $key) {
	$temp_array = array();
	$i = 0;
	$key_array = array();

	foreach($array as $val) {
		$val = explode(',',$val[0]);
		if (!in_array($val[$key], $key_array)) {
			$key_array[$i] = $val[$key];
			$temp_array[$i] = $val;
		}
		$i++;
	}
	return $temp_array;
}

function pg_format_array_multidimensional($array_string) {
    $array_string = preg_replace("/({|})/", "", $array_string);
    $array_string = preg_replace("/\"\(/", "[", $array_string);
    $array_string = preg_replace("/\)\"/", "]", $array_string);
    $array_string = preg_replace("/\"\[/", "[", $array_string);
    $array_string = preg_replace("/\\\\\[/", "[", $array_string);
    $array_string = preg_replace("/\]\\\\\"/", "]", $array_string);
    $array_string = preg_replace("/\\\\\"\\\\\"/", "\"", $array_string);
    $array_string = preg_replace("/\\\\\"/", "", $array_string);
    $array_string = preg_replace("/\]\,/", "];", $array_string);

    $array_string = explode(";", $array_string);

    $array_string = array_map(function($r) {
        if (preg_match("/\[/", $r)) {
            $r = preg_replace("/\"\,/", "\"|", $r);
        }

        $r = preg_replace("/^\[|\]$/", "", $r);
        $r = preg_replace("/\"\,\"/", "\"&&&\"", $r);
        $r = explode("&&&", $r);

        $r = array_map(function($s) {
            if (preg_match("/\[/", $s)) {
                $s = preg_replace("/\"\|/", "\",", $s);
            }

            return $s;
        }, $r);

        return $r;
    }, $array_string);

    return $array_string;
}

function secondsToTimeString($seconds) {
    $hours = floor($seconds / 3600);
    $seconds %= 3600;
    $minutes = floor($seconds / 60);

    return $hours . ":" . $minutes;
}

function googleMapsGeraRota($origem,$destino){
    $curl = curl_init();

    curl_setopt_array($curl, array(
        //CURLOPT_URL => "https://maps.googleapis.com/maps/api/directions/json?origin=".utf8_encode($origem)."&destination=".utf8_encode($destino)."&language=pt-br&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ",
        CURLOPT_URL => "https://maps.googleapis.com/maps/api/directions/json?".http_build_query(array(
            "origin" => utf8_encode($origem),
            "destination" => utf8_encode($destino),
            "language" => "pt-br",
            "key" => "AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ"
        )),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET"
        ));

    $response = curl_exec($curl);

    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        return array("exception" => $err);
    } else {
        return json_decode($response,true);
    }
}

function googleMapsGeraMapaEstatico($size,$origem,$destino){
    $rota = googleMapsGeraRota($origem,$destino);

    if(is_array($rota) && array_key_exists("exception", $rota)){
        return $rota;
    }

    $renderLink = "http://maps.googleapis.com/maps/api/staticmap?size=".$size."&path=enc:".$rota['routes'][0]['overview_polyline']['points']."&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ";


    $rota['render_link'] = $renderLink;
    return $rota;
}

function googleMapsGeraLocalizacaoEstatico($size, $location,$zoom = 15)
{
    if(is_array($rota) && array_key_exists("exception", $rota)){
        return $rota;
    }

    $renderLink = "http://maps.googleapis.com/maps/api/staticmap?size=".$size."&center=".$location."&zoom=".$zoom."&markers=color:red%7Clabel:P%7C".$location."&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ";

    $rota['render_link'] = $renderLink;
    return $rota;
}

function googleMapsGeraMapaEstaticoByPolyline($overview_polyline,$size){

    $renderLink = "http://maps.googleapis.com/maps/api/staticmap?size=".$size."&path=enc:".$overview_polyline."&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ";

    $rota['render_link'] = $renderLink;
    return $rota;
}

function textoProvidencia($texto, $hd_chamado,$consumidor_nome,$numero_objeto){
    $alteracoes["[_consumidor_]"] = $consumidor_nome;
    $alteracoes["[_protocolo_]"]  = $hd_chamado;
    $alteracoes["[_rastreio_]"]   = $numero_objeto;
    foreach ($alteracoes as $key => $value) {
        $texto = str_replace($key, $value, $texto);
    }

    return $texto;
}
if(!function_exists('textoProvidencia_new')) {
    function textoProvidencia_new($texto, $hd_chamado,$consumidor_nome,$numero_objeto = null , $fabrica = null){
        global $con , $login_fabrica;

		if(empty($login_fabrica) and !empty($fabrica)) {
			$login_fabrica = $fabrica;
		}

        if(preg_match("/\[_rastreio_\]/", $texto))  {
            unset($numero_objeto);
            $pedidos = array();

            $sql = "SELECT pedido FROM tbl_hd_chamado_extra WHERE hd_chamado = {$hd_chamado}";
            $res = pg_query($con, $sql);

            if (strlen(pg_fetch_result($res, 0, "pedido")) > 0) {
                $pedidos[] = pg_fetch_result($res, 0, "pedido");
            }

            $sql = "SELECT DISTINCT tbl_pedido.pedido
                FROM tbl_os
                INNER JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.os = tbl_os.os
                INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                INNER JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_os_item.pedido
                INNER JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido AND tbl_pedido.fabrica = {$login_fabrica}
                WHERE tbl_os.fabrica = {$login_fabrica}
                AND tbl_hd_chamado_extra.hd_chamado = {$hd_chamado}";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {

                if(pg_num_rows($res) ==1) {
                    $pedidos[] = pg_fetch_result($res, 0, "pedido");
                }else{
                    while ($pedido_os = pg_fetch_object($res)) {
                        $pedidos[] = $pedido_os->pedido;
                    }
                }

            }

            if(count($pedidos) >0 ) {
                $pedido = implode(",",$pedidos);
            }
            if(strlen($pedido)>0){
                $WhereCancelamento = "";
                if (in_array($login_fabrica, array(151))) {
                    $WhereCancelamento = "AND tbl_faturamento.cancelada IS NULL";
                }

                $sql =  "SELECT tbl_faturamento.conhecimento
                    FROM tbl_faturamento_item
                    INNER JOIN tbl_faturamento using(faturamento)
                    WHERE tbl_faturamento.fabrica = {$login_fabrica}
                    AND tbl_faturamento_item.pedido in ({$pedido})
                    {$WhereCancelamento}";

                $res = pg_query($con,$sql);

                if (pg_num_rows($res)>0){
                    $numero_objeto = pg_fetch_result($res,0,"conhecimento");

                    if(is_array(json_decode($numero_objeto,true))){
                        $numero_objeto = implode(",", json_decode($numero_objeto,true));
                    }
                }
            }
        }

        if(preg_match("/\[_codigo_postagem_\]/", $texto))  {

            $sql = "SELECT numero_postagem FROM tbl_hd_chamado_postagem
                    INNER JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_postagem.hd_chamado
                WHERE tbl_hd_chamado_postagem.hd_chamado = $hd_chamado AND tbl_hd_chamado.fabrica = $login_fabrica";
            $res_codigo_postagem = pg_query($con,$sql);

            if(pg_num_rows($res_codigo_postagem) > 0){
                $codigo_postagem = pg_fetch_result($res_codigo_postagem, 0, "numero_postagem");

                $alteracoes["[_codigo_postagem_]"] = $codigo_postagem;
            }

        }

        if(preg_match("/\[_ordem_servico_\]/", $texto))  {
            $sql_os = "SELECT os FROM tbl_hd_chamado_extra WHERE hd_chamado = {$hd_chamado}";
            $res_os = pg_query($con, $sql_os);

            if (pg_num_rows($res_os) > 0){
                $alteracoes["[_ordem_servico_]"] = pg_fetch_result($res_os, 0, 'os');
            }
        }

       if(preg_match("/\[_voucher_\]/", $texto))  {
            $sql_voucher = "SELECT codigo FROM tbl_voucher WHERE fabrica = {$login_fabrica} AND hd_chamado = {$hd_chamado}";
            $res_voucher = pg_query($con, $sql_voucher);

            if (pg_num_rows($res_voucher) > 0){
                $alteracoes["[_voucher_]"] = pg_fetch_result($res_voucher, 0, 'codigo');
            }
        }

        if(preg_match("/\[_tabela_produtos_\]/", $texto)){

            $sql_produto = "SELECT tbl_produto.produto, tbl_produto.referencia, tbl_produto.descricao FROM tbl_hd_chamado_item JOIN tbl_produto USING(produto) WHERE tbl_produto.fabrica_i = {$login_fabrica} AND tbl_hd_chamado_item.hd_chamado = {$hd_chamado} AND tbl_hd_chamado_item.produto IS NOT NULL";
            $res_produto = pg_query($con, $sql_produto);
            $msgHtml = "";

            if (pg_num_rows($res_produto) > 0){

                $msgHtml = "<html><head><style> table{font-family: arial, sans-serif; margin-top:20px; margin-bottom:20px}td,th{border: 1px solid #dddddd;text-align: left; padding: 8px;}</style></head>";
                $msgHtml .= "<body><table><tr><th>Produtos</th><th>Quantidade</th></tr>";
            }

            while($row = pg_fetch_array($res_produto)){

                $msgHtml .= "<tr><td>" . $row['referencia'] . " - " . $row['descricao']. "</td>";

                $sql_qtde_produto = "SELECT COUNT(1) AS qtde FROM tbl_hd_chamado_item WHERE tbl_hd_chamado_item.hd_chamado = {$hd_chamado} AND tbl_hd_chamado_item.produto = {$row['produto']}";
                $res_qtde_produto = pg_query($con, $sql_qtde_produto);

                if (pg_num_rows($res_qtde_produto) > 0){
                    $msgHtml .= "<td>" . pg_fetch_result($res_qtde_produto, 0, 'qtde'); "</td></tr>"; 
                }  
            }

            $msgHtml .= "</table></body></html>";
            $alteracoes["[_tabela_produtos_]"] = $msgHtml; 
        }

        if(preg_match("/\[_produto_\]/", $texto)) {

            $sql_produto = "SELECT tbl_produto.produto, tbl_produto.referencia, tbl_produto.descricao FROM tbl_hd_chamado_item JOIN tbl_produto USING(produto) WHERE tbl_produto.fabrica_i = {$login_fabrica} AND tbl_hd_chamado_item.hd_chamado = {$hd_chamado} AND tbl_hd_chamado_item.produto IS NOT NULL";

            $res_produto = pg_query($con, $sql_produto);

            if (pg_num_rows($res_produto) > 0){

                while($row = pg_fetch_array($res_produto)){

                    $alteracoes["[_produto_]"] .= $row['referencia'] . " - " . $row['descricao'];

                    if(preg_match("/\[_quantidade_produto_\]/", $texto)) {

                        $sql_qtde_produto = "SELECT COUNT(1) AS qtde FROM tbl_hd_chamado_item WHERE tbl_hd_chamado_item.hd_chamado = {$hd_chamado} AND tbl_hd_chamado_item.produto = {$row['produto']}";

                        $res_qtde_produto = pg_query($con, $sql_qtde_produto);

                        if (pg_num_rows($res_qtde_produto) > 0){
                            $alteracoes["[_quantidade_produto_]"] .= " ". pg_fetch_result($res_qtde_produto, 0, 'qtde');
                        }
                    }
                }
            }
        }

        if (in_array($login_fabrica, array(169,170))) {
            $consumidor_nome = explode(" ", trim($consumidor_nome));
            $consumidor_nome = $consumidor_nome[0];
        }

        $alteracoes["[_consumidor_]"]   = $consumidor_nome;
        $alteracoes["[_protocolo_]"]    = $hd_chamado;
        $alteracoes["[_rastreio_]"]     = $numero_objeto;

        foreach ($alteracoes as $key => $value) {
            $texto = str_replace($key, $value, $texto);
        }

        return $texto;

    }
}
function somaHoras($horas=array()){
    if (count($horas) == 0) {
        return false;
    }

    $segundos = 0;

    foreach ($horas as $h) {
        list($horas, $minutos) = explode(":", $h);
        $segundos += intval($horas) * 3600;
        $segundos += intval($minutos) * 60;
    }

    $horas    = floor( $segundos / 3600 ); //converte os segundos em horas e arredonda caso nescessario
    $segundos %= 3600; // pega o restante dos segundos subtraidos das horas
    $minutos  = floor( $segundos / 60 );//converte os segundos em minutos e arredonda caso nescessario
    $segundos %= 60;// pega o restante dos segundos subtraidos dos minutos

    if ($horas < 10) {
        $horas = "0".$horas;
    }
    if ($minutos < 10) {
        $minutos = "0".$minutos;
    }
    return $horas . ":" . $minutos;
}

function verifica_tipo_posto_geral($tipo, $valor, $posto_id = null) {
    global $con, $msg_erro, $login_fabrica, $campos, $tipo_posto_multiplo;

    if (is_null($posto_id)) {
        $posto_id = $campos["posto"]["id"];
    }

    if (!strlen($posto_id)) {
        $msg_erro['msg']['erro_tipo_posto'] = traduz("Erro ao verificar tipo do posto");
    }

    if (isset($tipo_posto_multiplo)) {
        $sql = "
          SELECT tbl_tipo_posto.tipo_posto
          FROM tbl_posto_tipo_posto
          INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_tipo_posto.tipo_posto
          WHERE tbl_posto_tipo_posto.fabrica = {$login_fabrica}
          AND tbl_posto_tipo_posto.posto = {$posto_id}
          AND tbl_tipo_posto.{$tipo} IS {$valor}
        ";
    } else {
        $sql = "
          SELECT tbl_tipo_posto.tipo_posto
          FROM tbl_posto_fabrica
          INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
          WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
          AND tbl_posto_fabrica.posto = {$posto_id}
          AND tbl_tipo_posto.{$tipo} IS {$valor}
        ";
    }

    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        return true;
    } else {
        return false;
    }
}

function qual_tipo_posto ($l_posto) {
    global $con, $login_fabrica;
    
    $sql = "SELECT tipo_posto FROM tbl_posto_fabrica WHERE tbl_posto_fabrica.posto = $l_posto AND tbl_posto_fabrica.fabrica = $login_fabrica";
    $res = pg_query($con, $sql);
    
    if (pg_num_rows($res) > 0) {
        return pg_fetch_result($res, 0, 'tipo_posto');
    } else {
        return 'erro';
    }
}

if(!function_exists('valida_celular')) {
    function valida_celular($celular) {

        if (strlen($celular) > 0) {
            $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();

            $celular          = $phoneUtil->parse("+55".$celular, "BR");
            $isValid          = $phoneUtil->isValidNumber($celular);
            $numberType       = $phoneUtil->getNumberType($celular);
            $mobileNumberType = \libphonenumber\PhoneNumberType::MOBILE;

            if (!$isValid || $numberType != $mobileNumberType) {
                return "Número de Celular inválido. <br />";
            }

        }
    }
}

if (!function_exists('phone_format')) {
    function phone_format($fone_str) {

        $fone_limpo = preg_replace('/\D/', '', $fone_str);

        $value = $fone_limpo;

        switch (strlen($fone_limpo)) {  // 13/04/2011 MLG - Formatando números de telefone...
            case  7: $value = preg_replace('/(\d{3})(\d{4})/', '$1-$2', $fone_limpo);
            break;
            case  8: $value = preg_replace('/(\d{4})(\d{4})/', '$1-$2', $fone_limpo);
            break;
            case  9: $value = preg_replace('/(\d{2})(\d{3})(\d{4})/', '($1) $2-$3', $fone_limpo);
            break;
            case 10: $value = preg_replace('/(\d{2})(\d{4})(\d{4})/', '(0$1) $2-$3', $fone_limpo);
            break;
            case 11:
                if ($fone_limpo[0] == '0') {
                    $value = preg_replace('/(0\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $fone_limpo);
                } else {
                    $value =  preg_replace('/(\d\d)(9\d{4})(\d{4})|(\d{3})(\d{4})(\d{4})/', '($1$4) $2$5-$3$6', $fone_limpo);
                }
                break;
            case 12: $value = preg_replace('/(\d{2})(\d{2})(\d{4})(\d{4})/', '+$1 ($2) $3-$4', $fone_limpo);
                break;
            case 13: $value = preg_replace('/(\d{2})(\d{3})(\d{4})(\d{4})|(55)(\d\d)(9\d{4})(\d{4})/', '+$1 ($2) $3-$4', $fone_limpo);
                break;
            case 14: $value = preg_replace('/(\d{3})(\d{3})(\d{4})(\d{4})/', '$1 ($2) $3-$4', $fone_limpo);
                break;
            default:
                $value = $fone_limpo;
                break;
        }

        if ($value == '() -' or value == '(0) -' or $value == '')
            $value = $fone_limpo;
        return $value;
    }
}

if(!function_exists('numero_por_extenso')) {
    function numero_por_extenso($valor = 0, $maiusculas = false) {
        if(!$maiusculas){
            $singular = ["centavo", "real", "mil", "milhão", "bilhão", "trilhão", "quatrilhão"];
            $plural   = ["centavos", "reais", "mil", "milhões", "bilhões", "trilhões", "quatrilhões"];
            $u        = ["", "um", "dois", "três", "quatro", "cinco", "seis",  "sete", "oito", "nove"];
        }else{
            $singular = ["CENTAVO", "REAL", "MIL", "MILHÃO", "BILHÃO", "TRILHÃO", "QUADRILHÃO"];
            $plural   = ["CENTAVOS", "REAIS", "MIL", "MILHÕES", "BILHÕES", "TRILHÕES", "QUADRILHÕES"];
            $u        = ["", "um", "dois", "TRÊS", "quatro", "cinco", "seis",  "sete", "oito", "nove"];
        }

        $c   = ["", "cem", "duzentos", "trezentos", "quatrocentos", "quinhentos", "seiscentos", "setecentos", "oitocentos", "novecentos"];
        $d   = ["", "dez", "vinte", "trinta", "quarenta", "cinquenta", "sessenta", "setenta", "oitenta", "noventa"];
        $d10 = ["dez", "onze", "doze", "treze", "quatorze", "quinze", "dezesseis", "dezesete", "dezoito", "dezenove"];

        $z  = 0;
        $rt = "";

        $valor = number_format($valor, 2, ".", ".");
        $inteiro = explode(".", $valor);
        for($i = 0;$i < count($inteiro);$i++)
            for($ii = strlen($inteiro[$i]);$ii < 3;$ii++)
                $inteiro[$i] = "0".$inteiro[$i];

        $fim = count($inteiro) - ($inteiro[count($inteiro)-1] > 0 ? 1 : 2);
        for ($i=0;$i<count($inteiro);$i++) {
            $valor = $inteiro[$i];
            $rc = (($valor > 100) && ($valor < 200)) ? "cento" : $c[$valor[0]];
            $rd = ($valor[1] < 2) ? "" : $d[$valor[1]];
            $ru = ($valor > 0) ? (($valor[1] == 1) ? $d10[$valor[2]] : $u[$valor[2]]) : "";

            $r = $rc.(($rc && ($rd || $ru)) ? " e " : "").$rd.(($rd &&
            $ru) ? " e " : "").$ru;
            $t = count($inteiro)-1-$i;
            $r .= $r ? " ".($valor > 1 ? $plural[$t] : $singular[$t]) : "";
            if ($valor == "000")$z++; elseif ($z > 0) $z--;
            if (($t==1) && ($z>0) && ($inteiro[0] > 0)) $r .= (($z>1) ? " de " : "").$plural[$t];
            if ($r) $rt = $rt . ((($i > 0) && ($i <= $fim) && ($inteiro[0] > 0) && ($z < 1)) ? ( ($i < $fim) ? ", " : " e ") : " ") . $r;
        }

        if(!$maiusculas){
            $return = $rt ? $rt : "zero";

            return trim(str_replace(" E "," e ",ucwords($return)));
        } else {
            if ($rt) {
                $rt = str_replace(" E "," e ",ucwords($rt));
            }
            $return = ($rt) ? ($rt) : "Zero";

            return strtoupper(trim($return));
        }
    }
}

function valida_mascara_localizacao($localizacao){
    $mascaras = array('LL-LNN-LNN', 'LLL-LNNN', 'LNN-LNN','LNNN-LNN','LL');
    $erro_mascara = false;
    foreach($mascaras as $mascara){
        $qtde_caracteres = strlen($mascara);
        $qtde_localizacao = strlen($localizacao);

        if($qtde_caracteres != $qtde_localizacao){
            $erro_mascara = true;
            continue;
        }

        for($a=1; $a<=strlen($mascara); $a++ ){

            $validador = substr($mascara, ($a-1), 1);
            $digito_localizacao =  substr($localizacao, ($a-1), 1);

            if($validador == "L" and is_numeric($digito_localizacao) == false){
                $erro_mascara = false;
            }elseif($validador == "-" and $digito_localizacao == '-' ){
                $erro_mascara = false;
            }elseif($validador == "N" and is_numeric($digito_localizacao) == true ){
                $erro_mascara = false;
            }else{
                $erro_mascara = true;
                continue 2;
            }
        }
        if($erro_mascara == false){
            return true;
            break;
        }
    }
    if($erro_mascara == true){
        return false;
    }
}

function verificaAdminSupervisor($admin) {
    global $con;

    $sql = "SELECT admin
            FROM tbl_admin
            WHERE admin = $admin
            AND  privilegios='*'";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        return true;
    } else {
        return false;
    }
}

// Criada por Pinsard em 2018-04-06
if(!function_exists('formata_cpf_cnpj')) {
    function formata_cpf_cnpj($documento) {
        switch( strlen($documento) ) {
        case 11:
            return preg_replace("/^(\d{3})(\d{3})(\d{3})(\d{2})$/", "$1.$2.$3-$4", $documento);

        case 14:
            return preg_replace("/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/", "$1.$2.$3/$4-$5", $documento);
        }

        return $documento;
    }
}


//Criada por Pinsard em 2018-06-01
//Compõe um nome de arquivo de log. A necessidade de uma função para isso
//é controlar onde os arquivos de log serão criados, e o formato de seus nomes
if(!function_exists('logFile')) {
    function logFile( $fileName ) {
        return fopen( './log/' . date('Ymd-') . $fileName . '.log', 'a+' );
    }
}


if(!function_exists('somaTxExtratoBlack')) {
	function somaTxExtratoBlack($extrato) {
		global $con;

		$sql = "SELECT round(sum(tbl_os.pecas * (((regexp_replace(campos_adicionais,'(\w)\\\\u','\\1\\\\\\\\u','g')::jsonb->>'TxAdmGrad')::float -1)))::numeric,2) totaltx 
				from tbl_os
				join tbl_os_extra using(os)
				join tbl_os_campo_extra on tbl_os_campo_extra.os = tbl_os.os and campos_adicionais ~'TxAdm'
				where tbl_os_extra.extrato = $extrato
				and tbl_os.pecas > 0
				and (((regexp_replace(campos_adicionais,'(\w)\\\\u','\\1\\\\\\\\u','g')::jsonb->>'TxAdmGrad')::float)) > 0
";
		$resX = pg_query($con, $sql);
		if(pg_num_rows($resX) > 0) {
			$totalTx = pg_fetch_result($resX,0, 'totaltx'); 
		}else{
			$totalTx = 0 ;
		}

		return $totalTx;
	}
}



if(!function_exists('escape_sequence_decode')) {
	function escape_sequence_decode($str) {

		$regex = '/\\\u([dD][89abAB][\da-fA-F]{2})\\\u([dD][c-fC-F][\da-fA-F]{2})
			|\\\u([\da-fA-F]{4})/sx';

		return preg_replace_callback($regex, function($matches) {

			if (isset($matches[3])) {
				$cp = hexdec($matches[3]);
			} else {
				$lead = hexdec($matches[1]);
				$trail = hexdec($matches[2]);

				$cp = ($lead << 10) + $trail + 0x10000 - (0xD800 << 10) - 0xDC00;
			}

			if ($cp > 0xD7FF && 0xE000 > $cp) {
				$cp = 0xFFFD;
			}


			if ($cp < 0x80) {
				return chr($cp);
			} else if ($cp < 0xA0) {
				return chr(0xC0 | $cp >> 6).chr(0x80 | $cp & 0x3F);
			}

			return html_entity_decode('&#'.$cp.';');
		}, $str);
	}
}

function gravaValoresAdicionaisProduto($produto,$familia_atual, $nova_familia = null){
    global $con, $login_fabrica;

    $familia_atual = str_replace("'", "", $familia_atual);
    $nova_familia = str_replace("'", "", $nova_familia);

    if (!empty($familia_atual)) {
        $sql = "SELECT valores_adicionais FROM tbl_familia WHERE familia = {$familia_atual} AND fabrica = {$login_fabrica}";
        $res = pg_query($con,$sql);
        $valores_adicionais_atual = json_decode(pg_fetch_result($res, 0, 'valores_adicionais'),true);
    }

    if(!empty($nova_familia)){
        $sql = "SELECT valores_adicionais FROM tbl_familia WHERE familia = {$nova_familia} AND fabrica = {$login_fabrica}";
        $res = pg_query($con,$sql);
        $valores_adicionais_novo = json_decode(pg_fetch_result($res, 0, 'valores_adicionais'),true);
    }

    $sql = "SELECT valores_adicionais FROM tbl_produto WHERE produto = {$produto} AND fabrica_i = {$login_fabrica}";
    $res = pg_query($con,$sql);
    $valores_adicionais = json_decode(pg_fetch_result($res, 0, 'valores_adicionais'),true);

    $valores = [];

    if(empty($nova_familia)){

        if(count($valores_adicionais) > 0 AND count($valores_adicionais_atual) > 0){

            $valores = array_merge($valores_adicionais,$valores_adicionais_atual);

        }else if(count($valores_adicionais_atual) > 0){

            $valores = $valores_adicionais_atual;
        }

    }else{

        if(count($valores_adicionais) > 0 AND count($valores_adicionais_atual) > 0){

            $valores = array_diff($valores_adicionais, $valores_adicionais_atual);
        }else{
            $valores = $valores_adicionais;
        }


        if(count($valores_adicionais_novo) > 0){

            $valores = array_merge($valores,$valores_adicionais_novo);

        }

    }

    if(count($valores) > 0){
        $valores_adicionais = "'".json_encode($valores)."'";
    }else{
        $valores_adicionais = "null";
    }

    $sql = "UPDATE tbl_produto SET valores_adicionais = $valores_adicionais WHERE fabrica_i = {$login_fabrica} AND produto = {$produto}";

    $res = pg_query($con,$sql);

    if (pg_last_error()) {
        return false;
    } 

    return true;

}

function getCategoriaPesquisa($pesquisa) {
    global $con;

    $sqlCategoria = "SELECT categoria FROM tbl_pesquisa WHERE pesquisa = {$pesquisa}";
    $resCategoria = pg_query($con, $sqlCategoria);

    return pg_fetch_result($resCategoria, 0, "categoria");
    
}

function msgBloqueioMenu(){
	global $login_fabrica;

	if(in_array($login_fabrica,[165])){
		echo "<div class='alert alert-warning'><h4><b>Acesso temporariamente bloqueado. <br>Favor entrar em contato com a Telecontrol</b></h4></div>";
		exit;
	}
}

function getProdutoEmGarantia($os) {
    global $login_fabrica, $con;

    $produto_em_estoque = 'Não';
    $sqlVl = " SELECT JSON_FIELD('produto_em_estoque', valores_adicionais) AS produto_em_estoque FROM tbl_os_campo_extra WHERE os = $os AND fabrica = $login_fabrica";
    $resVl = pg_query($con, $sqlVl);
    if (pg_num_rows($resVl) > 0) {
        $produto_em_estoque = (pg_fetch_result($resVl, 0, "produto_em_estoque") == "sim") ? "Sim" : "Não"; 
    }

    return $produto_em_estoque;
}

function anexoExtratoEnviadoBosch($extrato) {
    global $login_fabrica, $con;

    $sql = "SELECT extrato FROM tbl_extrato_status WHERE fabrica = $login_fabrica AND extrato = $extrato AND obs = 'Anexos Enviados'  ";
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0) {
        return true;
    }

    return false;
}

// Função para por mascaras nos campos.
// Como usar:
// MaskCamposPhp("##.###.###/####-##",$cnpj)
// MaskCamposPhp("###.###.###-##",$cpf)
function MaskCamposPhp($mask,$str){

    $str = str_replace(" ","",$str);

    for($i=0;$i<strlen($str);$i++){
        $mask[strpos($mask,"#")] = $str[$i];
    }

    return $mask;

}