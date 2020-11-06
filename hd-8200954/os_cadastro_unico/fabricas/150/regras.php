<?php
$regras["consumidor|cep"]["obrigatorio"] = true;

$valida_anexo_boxuploader = "valida_anexo_boxuploader";

#Reincidente
function auditoria_os_reincidente_inbrasil() {
	global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;

	$sql = "SELECT garantia 
				FROM tbl_produto 
			INNER JOIN tbl_os on tbl_os.produto = tbl_produto.produto 
			WHERE tbl_os.fabrica = {$login_fabrica} 
			AND tbl_produto.fabrica_i = {$login_fabrica} 
			AND tbl_os.os = {$os}";
	$res = pg_query($con,$sql);
	if(strlen(pg_num_rows($res))){
		$garantia = pg_fetch_result($res, 0, "garantia");
	}else{
		$garantia = 3;
	}


	$sql = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os} AND os_reincidente IS NOT TRUE";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){

		$select = "SELECT tbl_os.os
				FROM tbl_os
				INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
				INNER JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto
				WHERE tbl_os.fabrica = {$login_fabrica}
				AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '".$garantia." months')
				AND tbl_os.excluida IS NOT TRUE
				AND tbl_os.posto = {$campos['posto']['id']}
				AND tbl_os.os < {$os}
				AND tbl_os.nota_fiscal = '{$campos['os']['nota_fiscal']}'
				AND tbl_os.revenda_cnpj = '".preg_replace("/[\.\-\/]/", "", $campos["revenda"]["cnpj"])."'
				AND tbl_os_produto.produto = {$campos['produto']['id']}
				ORDER BY tbl_os.data_abertura DESC
				LIMIT 1";
		$resSelect = pg_query($con, $select);

		if (pg_num_rows($resSelect) > 0 && verifica_auditoria(array(67, 19), array(19, 67), $os) === true) {
			$os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");

			if (verifica_os_reincidente_finalizada($os_reincidente_numero)) {
				$insert = "INSERT INTO tbl_os_status
						(os, status_os, observacao)
						VALUES
						({$os}, 67, 'OS reincidentee de nota fiscal ')";
				$resInsert = pg_query($con, $insert);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao lançar ordem de serviço");
				} else {
					$os_reincidente = true;
				}
			}
		}
	}
}

function valida_numero_serie_inbrasil() {
	global $con, $campos,$os, $login_fabrica,$msg_erro;

	$produto_id = $campos["produto"]["id"];
	$produto_serie = $campos["produto"]["serie"];

	if (strlen($produto_id) > 0) {
		$sql = "select produto from tbl_produto where fabrica_i = $login_fabrica and produto = $produto_id and numero_serie_obrigatorio is true";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0 && empty($produto_serie)){
			$msg_erro["msg"]["campo_obrigatorio"] = "Preencha todos os campos obrigatórios";
			$msg_erro["campos"][] = "produto[serie]";
		} else if (pg_num_rows($res) > 0) {

			if (pg_num_rows($res) > 0) {
				$sql = "SELECT numero_serie FROM tbl_numero_serie WHERE fabrica = {$login_fabrica} AND produto = {$produto_id} AND serie = '{$produto_serie}'";
				$res = pg_query($con, $sql);

				if (!pg_num_rows($res)) {

					$sql = "SELECT garantia 
								FROM tbl_produto 
							INNER JOIN tbl_os on tbl_os.produto = tbl_produto.produto 
							WHERE tbl_os.fabrica = {$login_fabrica} 
							AND tbl_produto.fabrica_i = {$login_fabrica} 
							AND tbl_os.os = {$os}";
					$res = pg_query($con,$sql);
					if(strlen(pg_num_rows($res))){
						$garantia = pg_fetch_result($res, 0, "garantia");
					}else{
						$garantia = 3;
					}

					$select = "SELECT tbl_os.os
							FROM tbl_os
							INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
							INNER JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto
							WHERE tbl_os.fabrica = {$login_fabrica}
							AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '".$garantia." months')
							AND tbl_os.excluida IS NOT TRUE
							AND tbl_os.posto = {$campos['posto']['id']}
							AND tbl_os.os < {$os}
							AND tbl_os.nota_fiscal = '{$campos['os']['nota_fiscal']}'
							AND tbl_os.revenda_cnpj = '".preg_replace("/[\.\-\/]/", "", $campos["revenda"]["cnpj"])."'
							AND tbl_os_produto.produto = {$campos['produto']['id']}
							ORDER BY tbl_os.data_abertura DESC
							LIMIT 1";
					$resSelect = pg_query($con, $select);

					if (pg_num_rows($resSelect) > 0 && verifica_auditoria(array(102, 103), array(103, 102), $os) === true) {
						$os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");

						if (verifica_os_reincidente_finalizada($os_reincidente_numero)) {
							$insert = "INSERT INTO tbl_os_status
									(os, status_os, observacao)
									VALUES
									({$os}, 102, 'OS em auditoria de número de série ')";
							$resInsert = pg_query($con, $insert);

							if (strlen(pg_last_error()) > 0) {
								throw new Exception("Erro ao lançar ordem de serviço");
							} else {
								$os_reincidente = true;
							}
						}
						
					}
					if (pg_num_rows($resSelect) == 0 && verifica_auditoria(array(102, 103), array(103, 102), $os) === true) {

						$insert = "INSERT INTO tbl_os_status
								(os, status_os, observacao)
								VALUES
								({$os}, 102, 'OS em auditoria de número de série ')";
						$resInsert = pg_query($con, $insert);

						if (strlen(pg_last_error()) > 0) {
							throw new Exception("Erro ao lançar ordem de serviço");
						} else {
							$os_reincidente = true;
						}
						
					}
				}
			}
		}
	}
}

$auditorias = array(
	"auditoria_os_reincidente_inbrasil",
	"auditoria_peca_critica",
	"auditoria_troca_obrigatoria",
	"auditoria_pecas_excedentes",
	"valida_numero_serie_inbrasil"
);