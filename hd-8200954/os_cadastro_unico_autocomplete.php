<?php

require 'dbconfig.php';
require 'includes/dbconnect-inc.php';
require 'autentica_usuario.php';

$tipo = $_GET["tipo"];
$cond_acessorio = "";
if ($login_fabrica == 20) {
    $tipo_atendimento = $_GET["tipo_atendimento"];
    
    if ($tipo_atendimento == 12) {
        $cond_acessorio = ' AND tbl_peca.acessorio = true';
    } else {
        $cond_acessorio = ' AND tbl_peca.acessorio = false';
    }

}

if (!empty(trim($_GET['pecas_desconsidera']))) {

	$pecas_desconsidera = explode("|", $_GET['pecas_desconsidera']);

	foreach ($pecas_desconsidera as $peca) {
		if (!empty($peca)) {
			$pecas_desconsidera_aux[] = $peca;
		}
	}
	if (count($pecas_desconsidera_aux) > 0) {
		$cond_pecas_desconsidera = "AND tbl_peca.peca NOT IN (".implode(",", $pecas_desconsidera_aux).")";
	}

}

$tipo_pesquisa = $_GET['tipo_pesquisa'];

$q = $_GET["q"];
$q = str_replace (".","",$q);
$q = str_replace (",","",$q);
$q = str_replace ("-","",$q);
$q = str_replace ("/","",$q);

if($login_fabrica == 20){ //hd_chamado=2806280
    $sql_pais = "SELECT pais FROM tbl_posto WHERE posto = $login_posto";
    $res_pais = pg_query($con, $sql_pais);
    $posto_pais = pg_fetch_result($res_pais, 0, pais);

    $join_pais = " JOIN tbl_produto_pais ON tbl_produto_pais.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica ";
    $cond_pais = " AND tbl_produto_pais.pais = '$posto_pais'";

}

if ($tipo_pesquisa == 'referencia' || in_array($tipo_atendimento, [11,12])) {
	$condPesquisa = "tbl_produto.referencia_pesquisa LIKE UPPER('$q%')
			     	 OR tbl_produto.referencia_fabrica LIKE UPPER('$q%')";
} else {
	$condPesquisa = "tbl_produto.descricao          ILIKE '%$q%'
		             OR tbl_produto_idioma.descricao   ILIKE '%$q%'
		             OR tbl_produto.nome_comercial     ILIKE '%$q%'
			     	 OR tbl_produto.referencia_pesquisa LIKE UPPER('$q%')
			     	 OR tbl_produto.referencia_fabrica LIKE UPPER('$q%')";
}

if (!in_array($tipo_atendimento, [11,12])) {
	
    $condPesquisaPeca = "tbl_peca.referencia_pesquisa ILIKE '{$q}%'
			             OR tbl_peca.descricao ILIKE '%{$q}%'
			             OR tbl_peca_idioma.descricao ILIKE '%{$q}%'
			             OR tbl_depara.para ILIKE '{$q}%'";

} else {
    $condPesquisaPeca = "tbl_peca.referencia_pesquisa ILIKE '{$q}%'
                		 OR tbl_depara.para ILIKE '{$q}%'";
}

switch($tipo) {
	case "produto":
		$sql = "
		SELECT
            tbl_produto.produto,
            tbl_produto.linha,
            tbl_produto.referencia,
            COALESCE(tbl_produto_idioma.descricao, tbl_produto.descricao) AS descricao,
            tbl_produto.nome_comercial,
            tbl_produto.voltagem,
            tbl_produto.garantia,
            tbl_produto.mao_de_obra,
            tbl_produto.off_line

		FROM
            tbl_produto
            JOIN tbl_linha ON tbl_produto.linha=tbl_linha.linha AND tbl_linha.fabrica=$login_fabrica
		    LEFT JOIN tbl_produto_idioma ON tbl_produto_idioma.produto = tbl_produto.produto AND idioma = UPPER('$sistema_lingua')
		    $join_pais
        WHERE
            (
                {$condPesquisa}
            )
            AND tbl_produto.ativo
            AND tbl_produto.produto_principal
            $cond_pais
		ORDER BY
		tbl_produto.descricao

		LIMIT 10
		";
		$res = pg_query($con, $sql);
		$n = pg_num_rows($res);

		for($i = 0; $i < $n; $i++) {
			extract(pg_fetch_array($res));
			echo "$produto|$referencia|$descricao|$nome_comercial|$voltagem\n";
		}
	break;

	case "peca":
		$produto = intval($_GET["produto"]);

		if ($produto == 0 || strlen($produto) == 0) {
			echo "||" . traduz(array('prencha.o.campo','produto'), $con);
			exit;
		}

		$sql = "
		SELECT DISTINCT ON (referencia_atual)
            tbl_peca.peca,
            tbl_peca.referencia,
			COALESCE(tbl_peca_idioma.descricao, tbl_peca.descricao) AS descricao,
			tbl_peca.descricao as descricao_peca,
            tbl_depara.para,
            (SELECT tbl_peca.peca|| '-' ||tbl_peca.referencia || '-' || tbl_peca.descricao FROM tbl_peca WHERE peca = tbl_depara.peca_para) AS pecapara,
            tbl_lista_basica.qtde,
            CASE
            	WHEN tbl_depara.para IS NOT NULL
            	THEN tbl_depara.para
            	ELSE tbl_peca.referencia
            END as referencia_atual
		FROM
            tbl_peca
	    JOIN tbl_lista_basica
		  ON tbl_peca.peca = tbl_lista_basica.peca
		 AND tbl_lista_basica.fabrica = tbl_peca.fabrica
		 AND tbl_lista_basica.produto = {$produto}
		LEFT JOIN tbl_peca_fora_linha       ON tbl_peca_fora_linha.peca = tbl_peca.peca         AND tbl_peca.fabrica = tbl_peca_fora_linha.fabrica
		LEFT JOIN tbl_depara                ON tbl_depara.peca_de       = tbl_peca.peca         AND tbl_peca.fabrica = tbl_depara.fabrica AND (expira IS NULL OR expira > current_timestamp)
		LEFT JOIN tbl_peca AS tbl_peca_para ON tbl_peca_para.peca       = tbl_depara.peca_para  AND tbl_peca.fabrica = tbl_peca_para.fabrica
		LEFT JOIN tbl_peca_idioma           ON tbl_peca_idioma.peca     = tbl_peca.peca         AND idioma           = UPPER('$sistema_lingua')
		WHERE
            tbl_peca.fabrica = {$login_fabrica}
            AND tbl_peca.ativo IS TRUE
            $cond_acessorio
            AND tbl_peca.produto_acabado IS NOT TRUE
            AND (tbl_peca_fora_linha.libera_garantia IS TRUE OR tbl_peca_fora_linha.libera_garantia IS NULL)
            AND (
                {$condPesquisaPeca}
            )
          	{$cond_pecas_desconsidera}
		LIMIT 10
		";
		$res = pg_query($con, $sql);
		$n = pg_num_rows($res);

		if ($n > 0) {
			for($i = 0; $i < $n; $i++) {
				extract(pg_fetch_array($res));

				if (strlen($para) > 0) {
                    $pecapara   = explode('-', $pecapara);
                    $peca       = $pecapara[0];
                    $descricao  = $pecapara[2];
					$descricao .= ' ' . traduz('ref.anterior', $con, $cook_idioma, array($referencia));
                    $referencia = $pecapara[1];
				}

				echo "$peca|$referencia|$descricao|$qtde\n";
			}
		}
		else {
			echo "||" . traduz('sem.resultados', $con);
		}
	break;

	case "defeito_constatado":
		$produto = $_GET["produto"];

		if ($produto == 0 || strlen($produto) == 0) {
			echo "||" . traduz(array('prencha.o.campo','produto'), $con);
			exit;
		}

		$sql = "
		SELECT DISTINCT
			tbl_diagnostico.defeito_constatado,
			tbl_defeito_constatado.descricao
		FROM
			tbl_diagnostico
		JOIN
			tbl_produto ON tbl_diagnostico.linha=tbl_produto.linha AND tbl_diagnostico.familia=tbl_produto.familia
		JOIN
			tbl_defeito_constatado                ON tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado
   LEFT JOIN tbl_defeito_constatado_idioma AS DCI ON tbl_defeito_constatado.defeito_constatado = DCI.defeito_constatado AND idioma = UPPER('$sistema_lingua')

		WHERE
			tbl_diagnostico.fabrica = $login_fabrica
			AND tbl_produto.produto = $produto
			AND tbl_diagnostico.ativo = 't'
			AND tbl_defeito_constatado.ativo = 't'
			AND (
				tbl_defeito_constatado.codigo LIKE '%$q%'
				OR
				tbl_defeito_constatado.descricao ILIKE '%$q%'
			)
			AND tbl_diagnostico.familia = tbl_produto.familia
		ORDER BY
			tbl_diagnostico.defeito_constatado
		";
		$res = pg_query($con, $sql);
		$n = pg_numrows($res);

		for($i = 0; $i < $n; $i++) {
			extract(pg_fetch_array($res));

            if($login_fabrica == 20){
                $sql_idioma = "SELECT descricao FROM tbl_defeito_reclamado_idioma WHERE defeito_reclamado = $defeito_reclamado AND UPPER(idioma) = '$sistema_lingua'";
                $res_idioma = @pg_query($con,$sql_idioma);

                if (@pg_num_rows($res_idioma) >0)
                    $descricao = trim(@pg_fetch_result($res_idioma,0,'descricao'));
            }

			echo "$defeito_constatado|$codigo|$descricao\n";
		}
	break;

	case "revenda_nome":
        if($login_fabrica == 87){
            $join_revenda_fabrica = " JOIN tbl_revenda_fabrica ON tbl_revenda_fabrica.cnpj = tbl_revenda.cnpj AND tbl_revenda_fabrica.fabrica = $login_fabrica";
        }
		$sql = "
            SELECT
            tbl_revenda.cnpj,
            tbl_revenda.nome,
            tbl_revenda.fone,
            tbl_revenda.cep,
            tbl_revenda.endereco,
            tbl_revenda.numero,
            tbl_revenda.complemento,
            tbl_revenda.bairro,
            tbl_cidade.nome AS cidade,
            tbl_cidade.estado

            FROM
            tbl_revenda
            JOIN tbl_cidade ON tbl_revenda.cidade=tbl_cidade.cidade
            {$join_revenda_fabrica}
            WHERE
            tbl_revenda.nome ILIKE '%{$q}%'

            LIMIT 20
		";
		$res = pg_query($con, $sql);
		$n = pg_num_rows($res);

		for($i = 0; $i < $n; $i++) {
			extract(pg_fetch_array($res));

			echo "$cnpj|$nome|$fone|$cep|$endereco|$numero|$complemento|$bairro|$cidade|$estado\n";
		}
	break;

	case "revenda_cnpj":
        if($login_fabrica == 87){
            $join_revenda_fabrica = " JOIN tbl_revenda_fabrica ON tbl_revenda_fabrica.cnpj = tbl_revenda.cnpj AND tbl_revenda_fabrica.fabrica = $login_fabrica";
        }
		$sql = "
		SELECT
		tbl_revenda.cnpj,
		tbl_revenda.nome,
		tbl_revenda.fone,
		tbl_revenda.cep,
		tbl_revenda.endereco,
		tbl_revenda.numero,
		tbl_revenda.complemento,
		tbl_revenda.bairro,
		tbl_cidade.nome AS cidade,
		tbl_cidade.estado

		FROM
		tbl_revenda
		JOIN tbl_cidade ON tbl_revenda.cidade=tbl_cidade.cidade
        {$join_revenda_fabrica}
		WHERE
		tbl_revenda.cnpj LIKE '{$q}%'

		LIMIT 10
		";
		$res = pg_query($con, $sql);
		$n = pg_num_rows($res);

		for($i = 0; $i < $n; $i++) {
			extract(pg_fetch_array($res));

			echo "$cnpj|$nome|$fone|$cep|$endereco|$numero|$complemento|$bairro|$cidade|$estado\n";
		}
	break;

	case "serie":
		$sql = "
		SELECT
			tbl_produto.produto,
			tbl_produto.referencia,
			tbl_produto.descricao,
			tbl_produto.voltagem,
			tbl_numero_serie.serie
		FROM tbl_produto
			JOIN tbl_numero_serie ON tbl_numero_serie.produto=tbl_produto.produto
		WHERE
			tbl_numero_serie.serie ILIKE '{$q}%'
			AND tbl_numero_serie.fabrica = $login_fabrica
		LIMIT 10;";

		$res = pg_query($con, $sql);

		for($i = 0; $i < pg_num_rows($res); $i++) {
			extract(pg_fetch_array($res));

			echo "$produto|$referencia|$descricao|$voltagem|$serie\n";
		}
	break;

	case "cidade_estado":
      $estado = $_GET['estado'];
       $q = utf8_decode($q);

       $where_estado = !empty($estado) ?  " AND estado = '$estado' " : "";

       $sql = " SELECT cod_ibge, cidade, estado FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) ~*  UPPER(fn_retira_especiais('$q')) $where_estado LIMIT 20;";

       // echo pg_last_error($con);
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            for($i = 0; $i < pg_num_rows($res); $i++) {
                extract(pg_fetch_array($res));

                echo "$cod_ibge|$cidade|$estado\n";
            }
        }

	break;
}

