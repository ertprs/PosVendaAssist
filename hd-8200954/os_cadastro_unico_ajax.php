<?php

require 'dbconfig.php';
require 'includes/dbconnect-inc.php';
require 'autentica_usuario.php';
include_once 'class/tdocs.class.php';

if(isset($_GET['diagnostico'])){
	
	$solucao_os 			= $_GET["solucao_os"];
	$defeito_constatado 	= $_GET["defeito_constatado"];
	$produto_id 			= $_GET["produto_id"];

	if(!empty($solucao_os) and !empty($defeito_constatado)) {
		$sql = "SELECT tbl_diagnostico.diagnostico
				FROM tbl_diagnostico 
				INNER JOIN tbl_produto on tbl_produto.linha = tbl_diagnostico.linha and tbl_produto.familia = tbl_diagnostico.familia
				WHERE solucao = $solucao_os and defeito_constatado = $defeito_constatado 
				AND tbl_produto.produto = $produto_id";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res)> 0){
			$diagnostico = pg_fetch_result($res, 0, 'diagnostico');
		}
	}
	$tDocs = new TDocs($con, $login_fabrica);
	$caminho = $tDocs->getdocumentsByRef($diagnostico, 'diagnostico')->url;

	if(strlen(trim($caminho))>0){
		echo "<a href='$caminho' target='_blank'>Anexo</a>";	
	}
		
	exit;
}


switch($_GET["tipo"]) {
	case "solucao_os":
		$produto = intval($_GET["produto"]);
		$defeito_constatado = intval($_GET["defeito_constatado"]);

		$sql ="
		SELECT
		tbl_solucao.solucao,
		tbl_solucao.descricao

		FROM
		tbl_diagnostico
		JOIN tbl_solucao on tbl_diagnostico.solucao=tbl_solucao.solucao
		JOIN tbl_produto ON tbl_diagnostico.familia=tbl_produto.familia
			AND tbl_diagnostico.linha=tbl_produto.linha
			AND tbl_produto.produto={$produto}

		WHERE
		tbl_diagnostico.ativo = 't'
		AND tbl_diagnostico.defeito_constatado=$defeito_constatado

		ORDER BY
		tbl_solucao.descricao
		";
		$res = pg_query($con, $sql);
		$n = pg_num_rows($res);

		if ($n > 0) {
			echo "<option value=''></option>";
			for($i = 0; $i < $n; $i++) {
				extract(pg_fetch_array($res));
				echo "<option value={$solucao}>{$descricao}</option>";
			}
		}
		else {
			echo "<option value=-1>Nenhuma solução cadastrada para este defeito</option>";
		}
	break;

	case "pre-os":
		try {
			$hd_chamado = intval(trim($_GET["hd_chamado"]));
			$hd_chamado_item = intval(trim($_GET["hd_chamado_item"]));

			if ($hd_chamado == 0 && $hd_chamado_item == 0) throw new Exception("Pré-OS não informada");

			//A variável passa por intval, se estiver vazia, fica com valor ZERO
			if ($hd_chamado_item != 0) {
				$sql = "
				SELECT
				tbl_hd_chamado_item.os,
				tbl_hd_chamado_item.hd_chamado

				FROM
				tbl_hd_chamado_item
				JOIN tbl_hd_chamado ON tbl_hd_chamado_item.hd_chamado=tbl_hd_chamado.hd_chamado
				JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_item.hd_chamado=tbl_hd_chamado_extra.hd_chamado

				WHERE
				tbl_hd_chamado_item.hd_chamado_item={$hd_chamado_item}
				AND tbl_hd_chamado.fabrica={$login_fabrica}
				AND tbl_hd_chamado_extra.posto={$login_posto}
				";
				@$res = pg_query($con, $sql);

				if (strlen(pg_last_error($con)) > 0) throw new Exception("Falha ao consultar pré-OS <erro msg='".pg_last_error($con)."'>");
				if (strlen(pg_num_rows($res)) == 0) throw new Exception("Pré-OS não encontrada");

				extract(pg_fetch_array($res));

				$join_hd_chamado_item = "JOIN tbl_hd_chamado_item ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado_item.hd_chamado AND tbl_hd_chamado_item.hd_chamado_item={$hd_chamado_item}";
				$join_defeito_reclamado = "LEFT JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_item.defeito_reclamado";
			}
			//A variável passa por intval, se estiver vazia, fica com valor ZERO
			elseif ($hd_chamado != 0) {
				$sql = "
				SELECT
				tbl_hd_chamado_extra.os

				FROM
				tbl_hd_chamado_extra
				JOIN tbl_hd_chamado ON tbl_hd_chamado_extra.hd_chamado=tbl_hd_chamado.hd_chamado

				WHERE
				tbl_hd_chamado.hd_chamado={$hd_chamado}
				AND tbl_hd_chamado.fabrica={$login_fabrica}
				AND tbl_hd_chamado_extra.posto={$login_posto}
				";
				@$res = pg_query($con, $sql);

				if (strlen(pg_last_error($con)) > 0) throw new Exception("Falha ao consultar pré-OS <erro msg='".pg_last_error($con)."'>");
				if (strlen(pg_num_rows($res)) == 0) throw new Exception("Pré-OS não encontrada");

				extract(pg_fetch_array($res));

				$join_defeito_reclamado = "LEFT JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado";
			}

			if (strlen($os) > 0) {
				$sql = "
				SELECT
				tbl_os.sua_os

				FROM
				tbl_os

				WHERE
				tbl_os.os={$os}
				";
				@$res = pg_query($con, $sql);

				if (strlen(pg_last_error($con)) > 0) throw new Exception("Já existe OS cadastrada para esta pré-OS: falha ao consultar OS <erro msg='".pg_last_error($con)."'>");
				if (pg_num_rows($res) > 0) {
					extract(pg_fetch_array($res));

					throw new Exception("Já existe OS cadastrada para esta pré-OS: <a href='os_press.php?os={$os}'>{$sua_os}</a>");
				}
				else {
					throw new Exception("Já existe OS cadastrada para esta pré-OS: ID da OS {$os}");
				}

			}
			
			

			$sql = "
			SELECT
			to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data_abertura,
			tbl_hd_chamado_extra.consumidor_revenda,
			tbl_hd_chamado_extra.tipo_atendimento,

			tbl_produto.produto AS produto_id,
			tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto,
			tbl_hd_chamado_extra.serie,
			tbl_hd_chamado_extra.nota_fiscal AS nota_fiscal,
			to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') AS data_nf,
			tbl_defeito_reclamado.defeito_reclamado AS defeito_reclamado,
			COALESCE(tbl_defeito_reclamado.descricao, tbl_hd_chamado_extra.defeito_reclamado_descricao) AS defeito_reclamado_descricao,

			tbl_hd_chamado_extra.cpf AS consumidor_cpf,
			tbl_hd_chamado_extra.rg AS consumidor_rg,
			tbl_hd_chamado_extra.nome AS consumidor_nome,
			tbl_hd_chamado_extra.fone AS consumidor_fone,
			tbl_hd_chamado_extra.fone2 AS consumidor_fone2,
			tbl_hd_chamado_extra.celular AS consumidor_celular,
			tbl_hd_chamado_extra.cep AS consumidor_cep,
			tbl_hd_chamado_extra.endereco AS consumidor_endereco,
			tbl_hd_chamado_extra.numero AS consumidor_numero,
			tbl_hd_chamado_extra.complemento AS consumidor_complemento,
			tbl_hd_chamado_extra.bairro AS consumidor_bairro,
			tbl_cidade.nome AS consumidor_cidade,
			tbl_cidade.estado AS consumidor_estado,
			tbl_hd_chamado_extra.email AS consumidor_email,

			tbl_hd_chamado_extra.qtde_km,

			tbl_hd_chamado_extra.revenda_cnpj,
			tbl_hd_chamado_extra.revenda_nome,
			tbl_revenda.fone AS revenda_fone,
			tbl_revenda.cep AS revenda_cep,
			tbl_revenda.endereco AS revenda_endereco,
			tbl_revenda.numero AS revenda_numero,
			tbl_revenda.complemento AS revenda_complemento,
			tbl_revenda.bairro AS revenda_bairro,
			tbl_revenda_cidade.nome AS revenda_cidade,
			tbl_revenda_cidade.estado AS revenda_estado,

			tbl_hd_chamado.admin,
			tbl_hd_chamado.cliente_admin

			FROM
			tbl_hd_chamado_extra
			JOIN tbl_hd_chamado ON tbl_hd_chamado_extra.hd_chamado=tbl_hd_chamado.hd_chamado
			LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_hd_chamado_extra.produto
			LEFT JOIN tbl_cidade ON tbl_cidade.cidade = tbl_hd_chamado_extra.cidade
			LEFT JOIN tbl_revenda ON tbl_hd_chamado_extra.revenda_cnpj=tbl_revenda.cnpj
			LEFT JOIN tbl_cidade AS tbl_revenda_cidade ON tbl_revenda_cidade.cidade = tbl_revenda.cidade
			{$join_hd_chamado_item}
			{$join_defeito_reclamado}

			WHERE
			tbl_hd_chamado_extra.hd_chamado = {$hd_chamado}
			";
			$res = @pg_query($con, $sql);

			if (strlen(pg_last_error($con)) > 0) throw new Exception("Falha ao consultar pré-OS <erro msg='".pg_last_error($con)."'>");
			if (strlen(pg_num_rows($res)) == 0) throw new Exception("Pré-OS não encontrada");

			$dados = pg_fetch_array($res, 0, PGSQL_ASSOC);

			echo "ok";

			foreach($dados as $campo => $valor) {				
				echo "|{$campo}##{$valor}";
			}
		}
		catch(Exception $e) {
			echo "erro|" . $e->getMessage();
		}
	break;

	default:
		echo "Solicitação inválida";
}

?>
