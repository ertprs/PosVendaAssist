<?php



$regras["os|data_abertura"] = array(
    "obrigatorio" => true,
    "function" => array('valida_abertura')
);

	$regras["consumidor|telefone"]["obrigatorio"] = true;
    $regras["consumidor|celular"]["obrigatorio"] = true;

if (strlen(trim(getValue("consumidor[celular]"))) > 0 OR strlen(trim(getValue("consumidor[telefone]"))) > 0) {
    $regras["consumidor|telefone"]["obrigatorio"] = false;
    $regras["consumidor|celular"]["obrigatorio"] = false;
}


$auditorias = array(
  "auditoria_reincidente",
  "auditoria_peca_critica",
  "auditoria_troca_obrigatoria",
  "auditoria_pecas_excedentes",
  "auditoria_revenda",
  "auditoria_km",
  "auditoria_pecas",
  "auditoria_ressarcimento"
);

$valida_anexo_boxuploader = "valida_anexo_boxuploader";

$funcoes_fabrica = array("verifica_defeito_constatado");

function valida_anexo_osTermo(){
  global $campos, $os, $anexos_obrigatorios, $con, $login_fabrica;

  if(!empty($os)){

    $sql_ja_gerou = "SELECT JSON_FIELD('termo_entrega_produto', campos_adicionais) AS ja_gerou 
                     FROM tbl_os_campo_extra 
                     WHERE os = $os 
                     AND fabrica = $login_fabrica";
    $res_ja_gerou = pg_query($con, $sql_ja_gerou);
    if(pg_num_rows($res_ja_gerou)>0){
      $ja_gerou = pg_fetch_result($res_ja_gerou, 0, 'ja_gerou');

      if(!empty($ja_gerou)){
        $anexos_obrigatorios = ['termo_entrega'];
      }
    }
    
  }

}

function valida_anexo_termo(){
  global $login_fabrica, $campos, $os, $con, $login_admin;

  if (isset($campos['anexo_termo_'])) {
  
    $data_corte = '26-11-2018';
    
    $sql_data_digitacao = "SELECT to_char(data_digitacao,'DD-MM-YYYY') AS data_digitacao FROM tbl_os WHERE os = {$os} AND fabrica = {$login_fabrica}";
    $res_data_digitacao = pg_query($con, $sql_data_digitacao);
    $dt_digitacao = pg_fetch_result($res_data_digitacao, 0, 'data_digitacao');

    if (strtotime($dt_digitacao) >= strtotime($data_corte)) {
      if ($campos['anexo_termo_'][0] == '' && $campos['anexo_termo_'][1] == '') {
          throw new Exception("Favor Anexar o Termo de Entrega");
      }
    }
  }
}


/**
 * Função para validação de data de abertura
 */
function valida_abertura() {
	global $campos, $os;

	$data_abertura = $campos["os"]["data_abertura"];

	if (!empty($data_abertura) && empty($os)) {
		list($dia, $mes, $ano) = explode("/", $data_abertura);

		if (!checkdate($mes, $dia, $ano)) {
			throw new Exception("Data de abertura inválida");
		} else if (strtotime("{$ano}-{$mes}-{$dia}") < strtotime("today - 5 days")) {
			throw new Exception("Data de abertura não pode ser anterior a 5 dias");
		}
	}
}


function verifica_defeito_constatado(){
  global $login_fabrica, $campos, $os, $con, $login_admin;

  $auditoria_status = 6;

  if(!empty($campos['produto']['defeito_constatado'])) {
	  $sql = "select tbl_produto.familia, tbl_produto.descricao, tbl_diagnostico.garantia from tbl_produto inner join tbl_diagnostico on tbl_diagnostico.familia = tbl_produto.familia  where tbl_diagnostico.defeito_constatado = ".$campos['produto']['defeito_constatado']." and tbl_produto.produto = ".$campos['produto']['id'];
	  $res = pg_query($con, $sql);
	  if(pg_num_rows($res)>0){
		  $garantia = pg_fetch_result($res, 0, 'garantia');

		  if($garantia == 'f'){
			$sql_verifica_auditoria = "SELECT os from tbl_auditoria_os WHERE os = $os and auditoria_status = $auditoria_status and observacao = 'Auditoria de Defeito Constatado' ";
			$res_verifica_auditoria = pg_query($con, $sql_verifica_auditoria);

			if(pg_num_rows($res_verifica_auditoria) == 0){
			  $sql_insert = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES ($os, $auditoria_status, 'Auditoria de Defeito Constatado')";
			  $res_insert = pg_query($con, $sql_insert);
			}
		  }
	  }
  }

}



function valida_anexo_fora_garantia(){

    global $campos, $msg_erro;

    $count_anexo = array();

    foreach ($campos["anexo"] as $key => $value) {
      if (strlen($value) > 0) {
        $count_anexo[] = "ok";
      }
    }

    if(count($count_anexo) < 2){
      $msg_erro["msg"][] = "Para produto fora de garantia são obrigatórios 2 anexos (NF e Laudo)";
    }
  }


function auditoria_revenda(){

    global $login_fabrica, $campos, $os, $con, $login_admin;

    $posto_id = $campos["posto"]["id"];
    $auditoria_status = 6;

    $sql_posto = "SELECT tipo_revenda FROM tbl_posto_fabrica
                  INNER JOIN tbl_tipo_posto on tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                  WHERE posto = $posto_id and tbl_posto_fabrica.fabrica = $login_fabrica";
    $res_posto = pg_query($con, $sql_posto);

    if(strlen(trim(pg_last_error($con)))>0){
      $msg_erro .= "Erro ao encontrar tipo do posto - Auditoria de Revenda";
    }

    if(pg_num_rows($res_posto)>0){
        $tipo_revenda = pg_fetch_result($res_posto, 0, tipo_revenda);

        if($tipo_revenda == 't'){
          $sql_update = "SELECT os from tbl_auditoria_os WHERE os = $os and auditoria_status = $auditoria_status";
          $res_update = pg_query($con, $sql_update);

          if(pg_num_rows($res_update) == 0){
            $sql_insert = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES ($os, $auditoria_status, 'Auditoria de Revenda')";
            $res_insert = pg_query($con, $sql_insert);
          }
        }
    }
}

function auditoria_km(){
    global $login_fabrica, $campos, $os, $con, $login_admin;

    $tipo_atendimento = $campos["os"]["tipo_atendimento"];
    $auditoria_status = 2;
    $qtde_km          = $campos["os"]["qtde_km"];
    $qtde_km_hidden   = $campos["os"]["qtde_km_hidden"];

    $sql = "SELECT descricao FROM tbl_tipo_atendimento WHERE tipo_atendimento = $tipo_atendimento";
    $res = pg_query($con, $sql);
    if(strlen(trim(pg_last_error($con)))>0){
      $msg_erro = "Erro ao encontrar tipo atendimento - Auditoria de KM";
    }

    if(pg_num_rows($res)>0){
      $descricao = pg_fetch_result($res, 0, 'descricao');

      if($descricao == "Deslocamento"){
          $sql_update = "SELECT os from tbl_auditoria_os WHERE os = $os and auditoria_status = $auditoria_status";
          $res_update = pg_query($con, $sql_update);
          if(pg_num_rows($res_update) ==0 or ($qtde_km != $qtde_km_hidden)){

            if($qtde_km != $qtde_km_hidden){
                $observacao = "Auditoria de Km - $qtde_km - Km Alterado Manualmente";
            }else{
                $observacao = "Auditoria de Km - $qtde_km";
            }

              $sql_insert = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES ($os, $auditoria_status, '$observacao')";
              $res_insert = pg_query($con, $sql_insert);
              if(strlen(trim(pg_last_error($con)))>0){
                    $msg_erro = "Erro gravar auditoria de KM - Auditoria de KM";
              }
          }

      }

    }
}


function auditoria_reincidente(){

    global $login_fabrica, $campos, $os, $con, $login_admin;

    $produto        = $campos["produto"]["id"];
    $nf             = $campos["os"]["nota_fiscal"];
    $revenda_cnpj   = $campos["revenda"]["cnpj"];
    $auditoria_status = 1;

    $sql_verifica_auditoria = "SELECT os from tbl_auditoria_os WHERE os = $os and auditoria_status = $auditoria_status";
    $res_verifica_auditoria = pg_query($con, $sql_verifica_auditoria);

    if(pg_num_rows($res_verifica_auditoria) == 0){
        $retirar = array(".", "/", "-");
        $revenda_cnpj = str_replace($retirar, "", $revenda_cnpj);

        $sql = "select os from tbl_os
            where  revenda_cnpj = '$revenda_cnpj'
            and nota_fiscal = '$nf'
            and produto = $produto
            and fabrica = $login_fabrica
            and os < $os
            and data_abertura >= (data_abertura - INTERVAL '90 days') limit 1";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0){
          $os_reincidente_einhell = pg_fetch_result($res, 0, 'os');

          $sql_reincidencia = "UPDATE tbl_os SET os_reincidente = TRUE where fabrica = $login_fabrica AND os = $os";
          $res_reincidencia = pg_query($con, $sql_reincidencia);

          $sql_reincidencia_extra = "UPDATE tbl_os_extra SET os_reincidente = $os_reincidente_einhell
                        WHERE os = $os";
          $res_reincidencia_extra = pg_query($con, $sql_reincidencia_extra);

          $sql_auditoria = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
                          ({$os}, $auditoria_status, 'OS em Auditoria - Reincidencia')";
          $res_auditoria = pg_query($con, $sql_auditoria);

        }
    }
}


function auditoria_fora_garantia(){
    global $login_fabrica, $campos, $os, $con, $login_admin;

    $fora_garantia = $campos['produto']['fora_garantia'];

    //$valida_garantia = "";

    $auditoria_status = 3;

    if($fora_garantia == 17){
      $sql = "SELECT os FROM tbl_auditoria_os WHERE os = $os and auditoria_status = $auditoria_status";
      $res = pg_query($con, $sql);

      if(pg_num_rows($res)==0){
        $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
                      ({$os}, $auditoria_status, 'OS em Auditoria de Produto Fora de Garantia')";
        $res = pg_query($con, $sql);
      }
    }
}

function grava_os_fabrica(){

      global $campos;

      $versao = $campos["produto"]["versao"];
      $tipo_os = $campos["produto"]["fora_garantia"];

      if(strlen(trim($tipo_os))==0){
        $tipo_os = 'null';
      }

      return array(
        "type" => "'{$versao}'",
        "tipo_os" => "$tipo_os"
      );

}

// Todas as OS's com peças lançadas entraram em auditoria de fabrica HD-6296845 
function auditoria_pecas(){

  global $login_fabrica, $campos, $os, $con, $login_admin;

  if(verifica_peca_lancada() === true){

    $auditoria_status = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");
    $auditoria_status = $auditoria_status['auditoria'];

    $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
                                         ({$os}, $auditoria_status, 'OS em auditoria de fábrica')";
    $res = pg_query($con, $sql);
  
  }

}

function auditoria_ressarcimento(){

    global $login_fabrica, $campos, $os, $con, $login_admin;

    $produto        = $campos["produto"]["id"];
    $auditoria_status = 6;

	$sql_produto = "SELECT produto
					from tbl_produto
                  WHERE produto = $produto and parametros_adicionais::jsonb->>'ressarcimento_obrigatoria' ='t'";
    $res_produto = pg_query($con, $sql_produto);


    if(pg_num_rows($res_produto)>0){

          $sql_update = "SELECT os from tbl_auditoria_os WHERE os = $os and auditoria_status = $auditoria_status and observacao = 'Auditoria de Ressarcimento Obrigatorio' and admin isnull";
          $res_update = pg_query($con, $sql_update);

          if(pg_num_rows($res_update) == 0){
            $sql_insert = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES ($os, $auditoria_status, 'Auditoria de Ressarcimento Obrigatorio')";
            $res_insert = pg_query($con, $sql_insert);
          }
    }
}


?>
