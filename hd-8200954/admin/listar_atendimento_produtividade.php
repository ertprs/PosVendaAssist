<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include 'funcoes.php';

$params = json_decode(str_replace("\\","",$_GET["params"]));


if ( $params->gerar_excel == 'true' ) {
	$params->admin = str_replace("[","",(json_encode($params->admin)));
	$params->admin = str_replace("]","",($params->admin));
	//var_dump($params->admin);exit;
	$jsonPOST = excelPostToJson($params);
}

switch ($params->tipo) {
	case 'abertos':
		if (is_array($params->categoria) == true) {
			$params->categoria = implode(", ", $params->categoria);

			$where_categoria = " AND tbl_hd_chamado.categoria IN ({$params->categoria}) ";
		} else {
			$where_categoria = " AND tbl_hd_chamado.categoria = '{$params->categoria}' ";
		}

		if (isset($params->estado)) {
			$where_estado = " AND tbl_cidade.estado = '{$params->estado}' ";

			$params->admin = implode(", ", $params->admin);

			$where_admin = " AND tbl_hd_chamado.admin IN ({$params->admin}) ";
		} else {
			$where_admin = " AND tbl_hd_chamado.admin = {$params->admin} ";
		}
		if ( $login_fabrica <> 74)  {
           $cond_totais .= " AND tbl_hd_chamado.status <> 'Resolvido' ";
        }

        if($login_fabrica == 162){
        	if(strlen(trim($params->origem)) > 0){
        		$cond_origem = " AND tbl_hd_chamado_extra.origem = '{$params->origem}'";
        	}
        	if(strlen(trim($params->classificacao)) > 0){
        		$cond_classificacao = " AND tbl_hd_chamado.hd_classificacao = $params->classificacao";
        	}
        }

		$sql = "SELECT
					tbl_hd_chamado.hd_chamado,
					tbl_hd_chamado.status,
					(SELECT TO_CHAR(tbl_hd_chamado_item.data, 'DD/MM/YYYY')
						FROM tbl_hd_chamado_item
						WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY tbl_hd_chamado_item.data DESC LIMIT 1 ) as data_interacao ,
					TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YYYY') AS data_abertura,
					TO_CHAR(tbl_hd_chamado.resolvido, 'DD/MM/YYYY') AS data_fechamento,
					tbl_hd_chamado_extra.os,
					tbl_cidade.nome AS cidade,
					tbl_cidade.estado,
					tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto,
					tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome AS posto,
					tbl_hd_chamado_extra.origem,
					tbl_hd_classificacao.descricao AS descricao_classificacao
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
				JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
				LEFT JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
				LEFT JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
				AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_produto ON tbl_hd_chamado_extra.produto = tbl_produto.produto
				AND tbl_produto.fabrica_i = $login_fabrica
				LEFT JOIN tbl_hd_classificacao ON tbl_hd_classificacao.hd_classificacao = tbl_hd_chamado.hd_classificacao AND tbl_hd_classificacao.fabrica = $login_fabrica
				WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
				AND tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
				AND tbl_hd_chamado.data BETWEEN '{$params->data_inicial} 00:00:00' AND '{$params->data_final} 23:59:59'
				{$cond_totais}
				{$where_admin}
				{$cond_origem}
				{$cond_classificacao}
				{$where_estado}
				{$where_categoria}
				AND (tbl_hd_chamado_extra.posto <> 6359 OR tbl_hd_chamado_extra.posto IS NULL)";
		break;

	case 'interacoes':

		 $sql_cond = ($login_fabrica == 74) ? " AND tbl_hd_chamado_item.interno IS FALSE " : "";

		if (is_array($params->categoria) == true) {
			$params->categoria = implode(", ", $params->categoria);

			$where_categoria = " AND tbl_hd_chamado.categoria IN ({$params->categoria}) $sql_cond";
		} else {
			$where_categoria = " AND tbl_hd_chamado.categoria = '{$params->categoria}' $sql_cond";
		}

		if($login_fabrica == 162){
        	if(strlen(trim($params->origem)) > 0){
        		$cond_origem = " AND tbl_hd_chamado_extra.origem = '{$params->origem}'";
        	}
        	if(strlen(trim($params->classificacao)) > 0){
        		$cond_classificacao = " AND tbl_hd_chamado.hd_classificacao = $params->classificacao";
        	}
        }

		if (isset($params->estado)) {
			$where_estado = " AND tbl_cidade.estado = '{$params->estado}' ";

			$params->admin = implode(", ", $params->admin);

			$where_admin = " AND tbl_hd_chamado_item.admin IN ({$params->admin}) ";
		} else {
			$where_admin = " AND tbl_hd_chamado_item.admin = {$params->admin} ";
		}

		$sql = "SELECT
					DISTINCT tbl_hd_chamado_item.hd_chamado,
					tbl_hd_chamado.status,
					(SELECT TO_CHAR(tbl_hd_chamado_item.data, 'DD/MM/YYYY')
                                                FROM tbl_hd_chamado_item
                                                WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY tbl_hd_chamado_item.data DESC LIMIT 1 ) as data_interacao ,
					TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YYYY') AS data_abertura,
					TO_CHAR(tbl_hd_chamado.resolvido, 'DD/MM/YYYY') AS data_fechamento,
					tbl_hd_chamado_extra.os,
					tbl_cidade.nome AS cidade,
					tbl_cidade.estado,
					tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto,
					tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome AS posto,
					tbl_hd_chamado_extra.origem,
					tbl_hd_classificacao.descricao AS descricao_classificacao
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
				JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
				JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
				LEFT JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
				LEFT JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
				AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_produto ON tbl_hd_chamado_extra.produto = tbl_produto.produto
				AND tbl_produto.fabrica_i = $login_fabrica
				LEFT JOIN tbl_hd_classificacao ON tbl_hd_classificacao.hd_classificacao = tbl_hd_chamado.hd_classificacao AND tbl_hd_classificacao.fabrica = $login_fabrica
				WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
				AND tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
				AND tbl_hd_chamado_item.data BETWEEN '{$params->data_inicial} 00:00:00' AND '{$params->data_final} 23:59:59'
				{$where_admin}
				{$cond_origem}
				{$cond_classificacao}
				{$where_estado}
				{$where_categoria}
				AND (tbl_hd_chamado_extra.posto <> 6359 OR tbl_hd_chamado_extra.posto IS NULL)";
		break;

	case 'resolvidos':
		if (is_array($params->categoria) == true) {
			$params->categoria = implode(", ", $params->categoria);

			$where_categoria = " AND tbl_hd_chamado.categoria IN ({$params->categoria}) ";
		} else {
			$where_categoria = " AND tbl_hd_chamado.categoria = '{$params->categoria}' ";
		}

		if (isset($params->estado)) {
			$where_estado = " AND tbl_cidade.estado = '{$params->estado}' ";

			$params->admin = implode(", ", $params->admin);

			$where_admin = " AND tbl_hd_chamado_item.admin IN ({$params->admin}) ";
		} else {
			$where_admin = " AND tbl_hd_chamado_item.admin = {$params->admin} ";
		}

		if($login_fabrica == 162){
        	if(strlen(trim($params->origem)) > 0){
        		$cond_origem = " AND tbl_hd_chamado_extra.origem = '{$params->origem}'";
        	}
        	if(strlen(trim($params->classificacao)) > 0){
        		$cond_classificacao = " AND tbl_hd_chamado.hd_classificacao = $params->classificacao";
        	}
        }

		$sql = "SELECT
					DISTINCT tbl_hd_chamado_item.hd_chamado,
					tbl_hd_chamado.status,
					(SELECT TO_CHAR(tbl_hd_chamado_item.data, 'DD/MM/YYYY')
                                                FROM tbl_hd_chamado_item
                                                WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY tbl_hd_chamado_item.data DESC LIMIT 1 ) as data_interacao ,
					TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YYYY') AS data_abertura,
					TO_CHAR(tbl_hd_chamado.resolvido, 'DD/MM/YYYY') AS data_fechamento,
					tbl_hd_chamado_extra.os,
					tbl_cidade.nome AS cidade,
					tbl_cidade.estado,
					tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto,
					tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome AS posto,
					tbl_hd_chamado_extra.origem,
					tbl_hd_classificacao.descricao AS descricao_classificacao
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
				JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
				JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
				LEFT JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
				LEFT JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
				AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_produto ON tbl_hd_chamado_extra.produto = tbl_produto.produto
				AND tbl_produto.fabrica_i = $login_fabrica
				LEFT JOIN tbl_hd_classificacao ON tbl_hd_classificacao.hd_classificacao = tbl_hd_chamado.hd_classificacao AND tbl_hd_classificacao.fabrica = $login_fabrica
				WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
				AND tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
				AND tbl_hd_chamado_item.data BETWEEN '{$params->data_inicial} 00:00:00' AND '{$params->data_final} 23:59:59'
				{$where_admin}
				{$where_estado}
				{$cond_origem}
				{$cond_classificacao}
				AND upper(tbl_hd_chamado.status) = 'RESOLVIDO'
				AND upper(tbl_hd_chamado_item.status_item) = 'RESOLVIDO'
				{$where_categoria}
				AND (tbl_hd_chamado_extra.posto <> 6359 OR tbl_hd_chamado_extra.posto IS NULL)";
		break;

	case 'total':
		if (isset($params->estado)) {
			$where_estado = " AND tbl_cidade.estado = '{$params->estado}' ";

			$params->admin = implode(", ", $params->admin);

			$where_hd_admin      = " AND tbl_hd_chamado.admin IN ({$params->admin}) ";
			$where_hd_item_admin = " AND tbl_hd_chamado_item.admin IN ({$params->admin}) ";
		} else {
			$where_hd_admin      = " AND tbl_hd_chamado.admin = {$params->admin} ";
			$where_hd_item_admin = " AND tbl_hd_chamado_item.admin = {$params->admin} ";
		}
		 if ($login_fabrica <> 74 ){
                        $cond_todos = " AND tbl_hd_chamado.status <> 'Resolvido'  ";
                }


		$sql_cond = ($login_fabrica == 74) ? " AND tbl_hd_chamado_item.interno IS FALSE " : "";

		if($login_fabrica == 162){
        	if(strlen(trim($params->origem)) > 0){
        		$cond_origem = " AND tbl_hd_chamado_extra.origem = '{$params->origem}'";
        	}
        	if(strlen(trim($params->classificacao)) > 0){
        		$cond_classificacao = " AND tbl_hd_chamado.hd_classificacao = $params->classificacao";
        	}
        }

		$sql = "SELECT
					tbl_hd_chamado.hd_chamado,
					tbl_hd_chamado.status,
					(SELECT TO_CHAR(tbl_hd_chamado_item.data, 'DD/MM/YYYY')
                                                FROM tbl_hd_chamado_item
                                                WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY tbl_hd_chamado_item.data DESC LIMIT 1 ) as data_interacao ,
					TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YYYY') AS data_abertura,
					TO_CHAR(tbl_hd_chamado.resolvido, 'DD/MM/YYYY') AS data_fechamento,
					tbl_hd_chamado_extra.os,
					tbl_cidade.nome AS cidade,
					tbl_cidade.estado,
					tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto,
					tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome AS posto,
					tbl_hd_chamado_extra.origem,
					tbl_hd_classificacao.descricao AS descricao_classificacao
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
				JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
				JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
				LEFT JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
				LEFT JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
				AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_produto ON tbl_hd_chamado_extra.produto = tbl_produto.produto
				AND tbl_produto.fabrica_i = $login_fabrica
				LEFT JOIN tbl_hd_classificacao ON tbl_hd_classificacao.hd_classificacao = tbl_hd_chamado.hd_classificacao AND tbl_hd_classificacao.fabrica = $login_fabrica
				WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
				AND tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
				AND tbl_hd_chamado.data BETWEEN '{$params->data_inicial} 00:00:00' AND '{$params->data_final} 23:59:59'
				{$cond_todos}
				{$where_hd_admin}
				{$where_estado}
				{$cond_origem}
				{$cond_classificacao}
				$sql_cond
				AND tbl_hd_chamado.categoria = '{$params->categoria}'
				AND (tbl_hd_chamado_extra.posto <> 6359 OR tbl_hd_chamado_extra.posto IS NULL)
			UNION (
				SELECT
					DISTINCT tbl_hd_chamado_item.hd_chamado,
					tbl_hd_chamado.status,
					(SELECT TO_CHAR(tbl_hd_chamado_item.data, 'DD/MM/YYYY')
                                                FROM tbl_hd_chamado_item
                                                WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY tbl_hd_chamado_item.data DESC LIMIT 1 ) as data_interacao ,
					TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YYYY') AS data_abertura,
					TO_CHAR(tbl_hd_chamado.resolvido, 'DD/MM/YYYY') AS data_fechamento,
					tbl_hd_chamado_extra.os,
					tbl_cidade.nome AS cidade,
					tbl_cidade.estado,
					tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto,
					tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome AS posto,
					tbl_hd_chamado_extra.origem,
					tbl_hd_classificacao.descricao AS descricao_classificacao
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
				JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
				JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
				LEFT JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
				LEFT JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
				AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_produto ON tbl_hd_chamado_extra.produto = tbl_produto.produto
				AND tbl_produto.fabrica_i = $login_fabrica
				LEFT JOIN tbl_hd_classificacao ON tbl_hd_classificacao.hd_classificacao = tbl_hd_chamado.hd_classificacao AND tbl_hd_classificacao.fabrica = $login_fabrica
				WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
				AND tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
				AND tbl_hd_chamado_item.data BETWEEN '{$params->data_inicial} 00:00:00' AND '{$params->data_final} 23:59:59'
				{$where_hd_item_admin}
				{$cond_origem}
				{$cond_classificacao}
				{$where_estado}
				AND tbl_hd_chamado.categoria = '{$params->categoria}'
				AND (tbl_hd_chamado_extra.posto <> 6359 OR tbl_hd_chamado_extra.posto IS NULL)
			) UNION (
				SELECT
					DISTINCT tbl_hd_chamado_item.hd_chamado,
					tbl_hd_chamado.status,
					(SELECT TO_CHAR(tbl_hd_chamado_item.data, 'DD/MM/YYYY')
                                                FROM tbl_hd_chamado_item
                                                WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY tbl_hd_chamado_item.data DESC LIMIT 1 ) as data_interacao ,
					TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YYYY') AS data_abertura,
					TO_CHAR(tbl_hd_chamado.resolvido, 'DD/MM/YYYY') AS data_fechamento,
					tbl_hd_chamado_extra.os,
					tbl_cidade.nome AS cidade,
					tbl_cidade.estado,
					tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto,
					tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome AS posto,
					tbl_hd_chamado_extra.origem,
					tbl_hd_classificacao.descricao AS descricao_classificacao
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
				JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
				JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
				LEFT JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
				LEFT JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
				AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_produto ON tbl_hd_chamado_extra.produto = tbl_produto.produto
				AND tbl_produto.fabrica_i = $login_fabrica
				LEFT JOIN tbl_hd_classificacao ON tbl_hd_classificacao.hd_classificacao = tbl_hd_chamado.hd_classificacao AND tbl_hd_classificacao.fabrica = $login_fabrica
				WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
				AND tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
				AND tbl_hd_chamado_item.data BETWEEN '{$params->data_inicial} 00:00:00' AND '{$params->data_final} 23:59:59'
				{$where_hd_item_admin}
				{$where_estado}
				{$cond_origem}
				{$cond_classificacao}
				AND tbl_hd_chamado.status = 'Resolvido'
				AND tbl_hd_chamado_item.status_item = 'Resolvido'
				AND tbl_hd_chamado.categoria = '{$params->categoria}'
				AND (tbl_hd_chamado_extra.posto <> 6359 OR tbl_hd_chamado_extra.posto IS NULL)
			)";
		break;

	case 'outra_categoria':
		if (isset($params->estado)) {
			$where_estado = " AND tbl_cidade.estado = '{$params->estado}' ";

			$params->admin = implode(", ", $params->admin);

			$where_admin = " AND tbl_hd_chamado.admin IN ({$params->admin}) ";
		} else {
			$where_admin = " AND tbl_hd_chamado.admin = {$params->admin} ";
		}

		if($login_fabrica == 162){
        	if(strlen(trim($params->origem)) > 0){
        		$cond_origem = " AND tbl_hd_chamado_extra.origem = '{$params->origem}'";
        	}
        	if(strlen(trim($params->classificacao)) > 0){
        		$cond_classificacao = " AND tbl_hd_chamado.hd_classificacao = $params->classificacao";
        	}
        }

		$sql = "SELECT
					tbl_hd_chamado.hd_chamado,
					tbl_hd_chamado.status,
					(SELECT TO_CHAR(tbl_hd_chamado_item.data, 'DD/MM/YYYY')
                                                FROM tbl_hd_chamado_item
                                                WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY tbl_hd_chamado_item.data DESC LIMIT 1 ) as data_interacao ,
					TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YYYY') AS data_abertura,
					TO_CHAR(tbl_hd_chamado.resolvido, 'DD/MM/YYYY') AS data_fechamento,
					tbl_hd_chamado_extra.os,
					tbl_cidade.nome AS cidade,
					tbl_cidade.estado,
					tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto,
					tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome AS posto,
					tbl_hd_chamado_extra.origem,
					tbl_hd_classificacao.descricao AS descricao_classificacao
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
				JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
				LEFT JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
				LEFT JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
				AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_produto ON tbl_hd_chamado_extra.produto = tbl_produto.produto
				AND tbl_produto.fabrica_i = $login_fabrica
				LEFT JOIN tbl_hd_classificacao ON tbl_hd_classificacao.hd_classificacao = tbl_hd_chamado.hd_classificacao AND tbl_hd_classificacao.fabrica = $login_fabrica
				WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
				AND tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
				AND tbl_hd_chamado.data BETWEEN '{$params->data_inicial} 00:00:00' AND '{$params->data_final} 23:59:59'
				{$where_admin}
				{$where_estado}
				{$cond_origem}
				{$cond_classificacao}
				AND tbl_hd_chamado.categoria = '{$params->categoria}'
				AND (tbl_hd_chamado_extra.posto <> 6359 OR tbl_hd_chamado_extra.posto IS NULL)";
		break;

	case 'total_geral':
		$where_categoria       = " AND tbl_hd_chamado.categoria IN ('reclamacao_produto', 'reclamacao_empresa', 'reclamacao_at') ";
		$where_outra_categoria = " AND tbl_hd_chamado.categoria IN ('duvida_produto', 'sugestao', 'procon', 'onde_comprar', 'informacoes') ";

		if (isset($params->estado)) {
			$where_estado = " AND tbl_cidade.estado = '{$params->estado}' ";

			$params->admin = implode(", ", $params->admin);

			$where_hd_admin      = " AND tbl_hd_chamado.admin IN ({$params->admin}) ";
			$where_hd_item_admin = " AND tbl_hd_chamado_item.admin IN ({$params->admin}) ";
		} else {
			if ($params->gerar_excel == 'true'){
				$where_hd_admin      = " AND tbl_hd_chamado.admin IN ({$params->admin}) ";
				$where_hd_item_admin = " AND tbl_hd_chamado_item.admin IN ({$params->admin}) ";

			}else{
			$where_hd_admin      = " AND tbl_hd_chamado.admin = {$params->admin} ";
			$where_hd_item_admin = " AND tbl_hd_chamado_item.admin = {$params->admin} ";
			}
		}
		if ($login_fabrica <> 74 ) {
			$cond_todos = " AND tbl_hd_chamado.status <> 'Resolvido'  ";
		}

		$sql_cond = ($login_fabrica == 74) ? " AND tbl_hd_chamado_item.interno IS FALSE " : "";

		if($login_fabrica == 162){
        	if(strlen(trim($params->origem)) > 0){
        		$cond_origem = " AND tbl_hd_chamado_extra.origem = '{$params->origem}'";
        	}
        	if(strlen(trim($params->classificacao)) > 0){
        		$cond_classificacao = " AND tbl_hd_chamado.hd_classificacao = $params->classificacao";
        	}
        }

		$sql = "SELECT
					tbl_hd_chamado.hd_chamado,
					tbl_hd_chamado.status,
					(SELECT TO_CHAR(tbl_hd_chamado_item.data, 'DD/MM/YYYY')
                                                FROM tbl_hd_chamado_item
                                                WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY tbl_hd_chamado_item.data DESC LIMIT 1 ) as data_interacao ,
					TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YYYY') AS data_abertura,
					TO_CHAR(tbl_hd_chamado.resolvido, 'DD/MM/YYYY') AS data_fechamento,
					tbl_hd_chamado_extra.os,
					tbl_cidade.nome AS cidade,
					tbl_cidade.estado,
					tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto,
					tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome AS posto,
					tbl_hd_chamado_extra.origem,
					tbl_hd_classificacao.descricao AS descricao_classificacao
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
				JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
				LEFT JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
				LEFT JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
				AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_produto ON tbl_hd_chamado_extra.produto = tbl_produto.produto
				AND tbl_produto.fabrica_i = $login_fabrica
				LEFT JOIN tbl_hd_classificacao ON tbl_hd_classificacao.hd_classificacao = tbl_hd_chamado.hd_classificacao AND tbl_hd_classificacao.fabrica = $login_fabrica
				WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
				AND tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
				AND tbl_hd_chamado.data BETWEEN '{$params->data_inicial} 00:00:00' AND '{$params->data_final} 23:59:59'
				{$cond_todos}
				{$where_hd_admin}
				{$where_estado}
				{$cond_origem}
				{$cond_classificacao}
				{$where_categoria}
				AND (tbl_hd_chamado_extra.posto <> 6359 OR tbl_hd_chamado_extra.posto IS NULL)
				UNION (
					SELECT
						DISTINCT tbl_hd_chamado_item.hd_chamado,
						tbl_hd_chamado.status,
						(SELECT TO_CHAR(tbl_hd_chamado_item.data, 'DD/MM/YYYY')
                                                FROM tbl_hd_chamado_item
                                                WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY tbl_hd_chamado_item.data DESC LIMIT 1 ) as data_interacao ,
						TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YYYY') AS data_abertura,
						TO_CHAR(tbl_hd_chamado.resolvido, 'DD/MM/YYYY') AS data_fechamento,
						tbl_hd_chamado_extra.os,
						tbl_cidade.nome AS cidade,
						tbl_cidade.estado,
						tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto,
						tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome AS posto,
						tbl_hd_chamado_extra.origem,
						tbl_hd_classificacao.descricao AS descricao_classificacao
					FROM tbl_hd_chamado
					JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
					JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
					JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
					LEFT JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
					LEFT JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
					AND tbl_posto_fabrica.fabrica = $login_fabrica
					LEFT JOIN tbl_produto ON tbl_hd_chamado_extra.produto = tbl_produto.produto
					AND tbl_produto.fabrica_i = $login_fabrica
					LEFT JOIN tbl_hd_classificacao ON tbl_hd_classificacao.hd_classificacao = tbl_hd_chamado.hd_classificacao AND tbl_hd_classificacao.fabrica = $login_fabrica
					WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
					AND tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
					AND tbl_hd_chamado_item.data BETWEEN '{$params->data_inicial} 00:00:00' AND '{$params->data_final} 23:59:59'
					{$where_hd_item_admin}
					{$where_estado}
					{$cond_origem}
					{$cond_classificacao}
					{$where_categoria}
					AND (tbl_hd_chamado_extra.posto <> 6359 OR tbl_hd_chamado_extra.posto IS NULL)
				) UNION (
					SELECT
						DISTINCT tbl_hd_chamado_item.hd_chamado,
						tbl_hd_chamado.status,
						(SELECT TO_CHAR(tbl_hd_chamado_item.data, 'DD/MM/YYYY')
                                                FROM tbl_hd_chamado_item
                                                WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY tbl_hd_chamado_item.data DESC LIMIT 1 ) as data_interacao ,
						TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YYYY') AS data_abertura,
						TO_CHAR(tbl_hd_chamado.resolvido, 'DD/MM/YYYY') AS data_fechamento,
						tbl_hd_chamado_extra.os,
						tbl_cidade.nome AS cidade,
						tbl_cidade.estado,
						tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto,
						tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome AS posto,
						tbl_hd_chamado_extra.origem,
						tbl_hd_classificacao.descricao AS descricao_classificacao
					FROM tbl_hd_chamado
					JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
					JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
					JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
					LEFT JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
					LEFT JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
					AND tbl_posto_fabrica.fabrica = $login_fabrica
					LEFT JOIN tbl_produto ON tbl_hd_chamado_extra.produto = tbl_produto.produto
					AND tbl_produto.fabrica_i = $login_fabrica
					LEFT JOIN tbl_hd_classificacao ON tbl_hd_classificacao.hd_classificacao = tbl_hd_chamado.hd_classificacao AND tbl_hd_classificacao.fabrica = $login_fabrica
					WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
					AND tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
					AND tbl_hd_chamado_item.data BETWEEN '{$params->data_inicial} 00:00:00' AND '{$params->data_final} 23:59:59'
					{$where_hd_item_admin}
					{$where_estado}
					{$cond_origem}
					{$cond_classificacao}
					AND tbl_hd_chamado.status = 'Resolvido'
					AND tbl_hd_chamado_item.status_item = 'Resolvido'
					{$where_categoria}
					$sql_cond
					AND (tbl_hd_chamado_extra.posto <> 6359 OR tbl_hd_chamado_extra.posto IS NULL)
				) UNION (
					SELECT
						tbl_hd_chamado.hd_chamado,
						tbl_hd_chamado.status,
						(SELECT TO_CHAR(tbl_hd_chamado_item.data, 'DD/MM/YYYY')
                                                FROM tbl_hd_chamado_item
                                                WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY tbl_hd_chamado_item.data DESC LIMIT 1 ) as data_interacao ,
						TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YYYY') AS data_abertura,
						TO_CHAR(tbl_hd_chamado.resolvido, 'DD/MM/YYYY') AS data_fechamento,
						tbl_hd_chamado_extra.os,
						tbl_cidade.nome AS cidade,
						tbl_cidade.estado,
						tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto,
						tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome AS posto,
						tbl_hd_chamado_extra.origem,
						tbl_hd_classificacao.descricao AS descricao_classificacao
					FROM tbl_hd_chamado
					JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
					JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
					JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
					LEFT JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
					LEFT JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
					AND tbl_posto_fabrica.fabrica = $login_fabrica
					LEFT JOIN tbl_produto ON tbl_hd_chamado_extra.produto = tbl_produto.produto
					AND tbl_produto.fabrica_i = $login_fabrica
					LEFT JOIN tbl_hd_classificacao ON tbl_hd_classificacao.hd_classificacao = tbl_hd_chamado.hd_classificacao AND tbl_hd_classificacao.fabrica = $login_fabrica
					WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
					AND tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
					AND tbl_hd_chamado.data BETWEEN '{$params->data_inicial} 00:00:00' AND '{$params->data_final} 23:59:59'
					{$where_hd_admin}
					{$where_estado}
					{$cond_origem}
					{$cond_classificacao}
					{$where_outra_categoria}
					AND (tbl_hd_chamado_extra.posto <> 6359 OR tbl_hd_chamado_extra.posto IS NULL)
				)";
		break;
}

if (isset($sql)) {
	 //echo nl2br($sql);exit;
	$res  = pg_query($con, $sql);

	echo pg_last_error();

	$rows = pg_num_rows($res);
}

if ( $params->gerar_excel == 'true' ) {

	if ($rows > 0) {
		$data = date("d-m-Y-H:i");

		$fileName = "relatorio_detalhado_callcenter-{$data}.xls";

		$file = fopen("/tmp/{$fileName}", "w");

					list($dy, $dm, $dd) = explode("-", $params->data_inicial);
					$data_inicial = "{$dd}/{$dm}/{$dy}";
					//var_dump($_POST);exit;
					list($dy, $dm, $dd) = explode("-", $params->data_final);
					$data_final = "{$dd}/{$dm}/{$dy}";
					//fwrite($file, "<th> Data: {$data_inicial} á {$data_final} </th> ");

					if ($params->tipo != "total_geral") {
						$categorias = array(
							"reclamacao_produto"                                          => "Produto/Defeito",
							"reclamacao_empresa"                                          => "Recl. Empresa",
							"reclamacao_at"                                               => "Recl. A.T",
							"'reclamacao_produto', 'reclamacao_empresa', 'reclamacao_at'" => "Produto/Defeito, Recl. Empresa, Recl. A.T",
							"duvida_produto"                                              => "Dúvida Prod.",
							"sugestao"                                                    => "Sugestão",
							"procon"                                                      => "Procon",
							"onde_comprar"                                                => "Onde Comprar",
							"informacoes"                                                 => "Informações"
						);

						$categoria = $categorias[$params->categoria];
					} else {
						$categoria = "Todas";
					}

					//fwrite($file, "<th>Categoria: {$categoria}</th >");

					if (in_array($params->categoria, array("reclamacao_produto", "reclamacao_empresa", "reclamacao_at", "'reclamacao_produto', 'reclamacao_empresa', 'reclamacao_at'"))) {
						$tipo = $params->tipo;
						switch ($params->tipo) {
							case 'abertos':
								$tipo = "Em Aberto";
								break;

							case 'resolvidos':
								$tipo = "Fechados";
								break;

							case 'interacoes':
								$tipo = "Em Acompanhamento";
								break;

							case 'total':
								$tipo = "Todos";
								break;
						}

						//fwrite($file, "<th>Atendimentos: {$tipo}</th >");

					}

					if (!isset($params->estado)) {
						$xsql = "SELECT CASE WHEN LENGTH(nome_completo) = 0 THEN login ELSE nome_completo END AS atendente FROM tbl_admin WHERE fabrica = {$login_fabrica} AND admin = {$params->admin}";
						$xres = pg_query($con, $xsql);

						$atendente = pg_fetch_result($xres, 0, "atendente");
						//fwrite($file, "<th>Atendente: {$atendente}</th >");

					} else {
						$estado = $params->estado;
						//fwrite($file, "<th>Estado: {$estado}</th >");

					}


					fwrite($file, "
							<table  >
								<thead>
									<tr >
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important';>Atendimento</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important';>Data Abertura</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important';>Produto</th>");
								if($login_fabrica == 162){
									fwrite($file, "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important';>Classificação</th>
													<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important';>Origem</th>");
								}
					fwrite($file, "
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important';>OS</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important';>Cidade</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important';>Estado</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important';>Posto</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important';>Data interação</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important';>Status</th>
									</tr>
								</thead>
								<tbody>
								");


								for ($i = 0; $i < $rows; $i++) {
									$hd_chamado      = pg_fetch_result($res, $i, "hd_chamado");
									$status          = pg_fetch_result($res, $i, "status");
									$data_interacao  = pg_fetch_result($res, $i, "data_interacao");
									$data_abertura   = pg_fetch_result($res, $i, "data_abertura");
									$data_fechamento = pg_fetch_result($res, $i, "data_fechamento");
									$os              = pg_fetch_result($res, $i, "os");
									$cidade          = pg_fetch_result($res, $i, "cidade");
									$estado          = pg_fetch_result($res, $i, "estado");
									$produto         = pg_fetch_result($res, $i, "produto");
									$posto           = pg_fetch_result($res, $i, "posto");
									$descricao_classificacao = pg_fetch_result($res, $i, "descricao_classificacao");
									$origem = pg_fetch_result($res, $i, "origem");
									fwrite($file, "
									<tr>
										<th>{$hd_chamado}</th>
										<th>{$data_abertura}</th>
										<th>{$produto}</th>");
									if($login_fabrica == 162){//HD-3352176
										fwrite($file, " <th>{$descricao_classificacao}</th>
														<th>{$origem}</th>");
									}
									fwrite($file, "<th>{$os}</th>
										<th>{$cidade}</th>
										<th>{$estado}</th>
										<th>{$posto}</th>
										<th>{$data_interacao}</th>
										<th>{$status}</th>
									</tr>
									");
								}

								fwrite($file, "
							  	<tr>
                                    <th colspan='9' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".$rows." registros</th>
                                </tr>
                                </tbody>
							</table>
						");
			fclose($file);
		if (file_exists("/tmp/{$fileName}")) {
	        system("mv /tmp/{$fileName} xls/{$fileName}");

	        // mv xls xls2
			// mkdir -m 777 xls
	        // devolve para o ajax o nome doa rquivo gerado

	        echo "xls/{$fileName}";
	    }
		exit;
	}
}

?>
<!DOCTYPE html />
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="plugins/dataTable.js"></script>
		<script src="plugins/resize.js"></script>
		<script src="plugins/shadowbox_lupa/lupa.js"></script>

		<script>
			$(function () {
				$.dataTableLoad({ table: "#resultado" });
				function loading (display) {
		    		switch (display) {
		    			case "show":
		    				$("#loading").show();
							$("#loading_action").val("t");
		    				break;

		    			case "hide":
		    				$("#loading").hide();
							$("#loading_action").val("f");
		    				break;
		    		}
		    	}
				function ajaxAction () {
		    		if ($("#loading_action").val() == "t") {
		    			alert("Espere o processo atual terminar!");
		    			return false;
		    		} else {
		    			return true;
		    		}
		    	}
				$("#gerar_excel").click(function () {
	    			if (ajaxAction()) {
	    				var json = $.parseJSON($("#jsonPOST").val());
	    				json["gerar_excel"] = true;

		    			$.ajax({
		    				url: "<?=$_SERVER['PHP_SELF']?>",
		    				type: "POST",
		    				data: json,
		    				beforeSend: function () {
		    					loading("show");
		    				},
		    				complete: function (data) {
		    					window.open(data.responseText, "_blank");

		    					loading("hide");
		    				}
		    			});
	    			}

    			});

			});
		</script>
	</head>

	<body>
		<div id="container_lupa" style="overflow-y:auto;">
			<div id="topo">
				<img class="espaco" src="../imagens/logo_new_telecontrol.png">
			</div>
			<br /><hr />
			<?
			if ($rows > 0) {
			?>
				<div id="border_table">
					<style>
						h1, h2, h3, h4, h5, h6 {
							display: inline;
						}
					</style>
					<?php
					list($dy, $dm, $dd) = explode("-", $params->data_inicial);
					$data_inicial = "{$dd}/{$dm}/{$dy}";

					list($dy, $dm, $dd) = explode("-", $params->data_final);
					$data_final = "{$dd}/{$dm}/{$dy}";
					echo "<h5>Data:</h5> {$data_inicial} á {$data_final}<br />";

					if ($params->tipo != "total_geral") {
						$categorias = array(
							"reclamacao_produto"                                          => "Produto/Defeito",
							"reclamacao_empresa"                                          => "Recl. Empresa",
							"reclamacao_at"                                               => "Recl. A.T",
							"'reclamacao_produto', 'reclamacao_empresa', 'reclamacao_at'" => "Produto/Defeito, Recl. Empresa, Recl. A.T",
							"duvida_produto"                                              => "Dúvida Prod.",
							"sugestao"                                                    => "Sugestão",
							"procon"                                                      => "Procon",
							"onde_comprar"                                                => "Onde Comprar",
							"informacoes"                                                 => "Informações"
						);

						$categoria = $categorias[$params->categoria];
					} else {
						$categoria = "Todas";
					}

					echo "<h5>Categoria:</h5> {$categoria}<br />";

					if (in_array($params->categoria, array("reclamacao_produto", "reclamacao_empresa", "reclamacao_at", "'reclamacao_produto', 'reclamacao_empresa', 'reclamacao_at'"))) {
						$tipo = $params->tipo;
						switch ($params->tipo) {
							case 'abertos':
								$tipo = "Em Aberto";
								break;

							case 'resolvidos':
								$tipo = "Fechados";
								break;

							case 'interacoes':
								$tipo = "Em Acompanhamento";
								break;

							case 'total':
								$tipo = "Todos";
								break;
						}

						echo "<h5>Atendimentos:</h5> {$tipo}<br />";
					}

					if (!isset($params->estado)) {
						$xsql = "SELECT CASE WHEN LENGTH(nome_completo) = 0 THEN login ELSE nome_completo END AS atendente FROM tbl_admin WHERE fabrica = {$login_fabrica} AND admin = {$params->admin}";
						$xres = pg_query($con, $xsql);

						$atendente = pg_fetch_result($xres, 0, "atendente");
						echo "<h5>Atendente:</h5> {$atendente}<br />";
					} else {
						$estado = $params->estado;
						echo "<h5>Estado:</h5> {$estado}<br />";
					}
					?>
					<hr />
					<table id="resultado" class="table table-striped table-bordered table-hover table-lupa" >
						<thead>
							<tr class='titulo_coluna' >
								<th>Atendimento</th>
								<th>Data Abertura</th>
								<th>Produto</th>
								<?php if($login_fabrica == 162){ ?>
									<th>Classificação</th>
									<th>Origem</th>
								<?php } ?>
								<th>OS</th>
								<th>Cidade</th>
								<th>Estado</th>
								<th>Posto</th>
								<th>Data interação</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>
						<?php
						for ($i = 0; $i < $rows; $i++) {
							$hd_chamado      = pg_fetch_result($res, $i, "hd_chamado");
							$status          = pg_fetch_result($res, $i, "status");
							$data_interacao  = pg_fetch_result($res, $i, "data_interacao");
							$data_abertura   = pg_fetch_result($res, $i, "data_abertura");
							$data_fechamento = pg_fetch_result($res, $i, "data_fechamento");
							$os              = pg_fetch_result($res, $i, "os");
							$cidade          = pg_fetch_result($res, $i, "cidade");
							$estado          = pg_fetch_result($res, $i, "estado");
							$produto         = pg_fetch_result($res, $i, "produto");
							$posto           = pg_fetch_result($res, $i, "posto");
							$descricao_classificacao 	 = pg_fetch_result($res, $i, "descricao_classificacao");
							$origem 		 = pg_fetch_result($res, $i, "origem");

							echo "<tr>
								<td><a href='callcenter_interativo_new.php?callcenter={$hd_chamado}' target='_blank'>{$hd_chamado}</a>&nbsp;</td>
								<td>{$data_abertura}&nbsp;</td>
								<td>{$produto}&nbsp;</td>";
							if($login_fabrica == 162){
								echo "<td>{$descricao_classificacao}&nbsp;</td>
									  <td>{$origem}&nbsp;</td>";
							}
							echo "<td><a href='os_press.php?os={$os}' target='_blank'>{$os}</a>&nbsp;</td>
								<td>{$cidade}&nbsp;</td>
								<td>{$estado}&nbsp;</td>
								<td>{$posto}&nbsp;</td>
								<td>{$data_interacao}&nbsp;</td>
								<td>{$status}&nbsp;</td>
							</tr>";
						}
						?>
						</tbody>
					</table>
				</div>
			<?php
			} else {
				echo '<div class="alert alert_shadobox">
				    <h4>Nenhum resultado encontrado</h4>
				</div>';
			}
			?>
		</div>
	</body>
</html>
