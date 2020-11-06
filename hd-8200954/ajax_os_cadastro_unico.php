<?php

require 'dbconfig.php';
require 'includes/dbconnect-inc.php';
require 'autentica_usuario.php';

$tipo               = $_POST["tipo"];
$produto_referencia = @$_POST["produto_referencia"];
$peca_referencia    = @$_POST["peca_referencia"];
$id_solucao_os      = @$_POST["id_solucao_os"];

switch($tipo) {

	case "solucao_os": // @TODO

        if ($login_fabrica == 20 AND $tipo_atendimento == 12) { //HD-2843341 Interação 142
            $cond_acessorio = " AND SR.garantia_acessorio is true ";
        }else{
            $cond_acessorio = " AND SR.garantia_acessorio is not true ";
        }
		$sql = "
			SELECT SR.servico_realizado, COALESCE(SRI.descricao, SR.descricao) AS descricao
			  FROM tbl_servico_realizado        SR
			  LEFT JOIN tbl_servico_realizado_idioma SRI
			    ON SR.servico_realizado = SRI.servico_realizado
			   AND idioma  = UPPER('{$cook_idioma}')
			 WHERE SR.fabrica = $login_fabrica
			   AND SR.solucao IS NOT TRUE
			   AND SR.ativo IS TRUE
			   $cond_acessorio
			 ORDER BY descricao ";
		$res = pg_query($con,$sql);
        echo '<option></option>';
		for($i = 0; $i< pg_num_rows($res); $i++) {
            $id_servico_realizado = pg_result($res,$i,'servico_realizado');

            if($id_solucao_os == $id_servico_realizado){
                $selected = " selected ";
            }else{
                $selected = "";
            }

			echo '<option value="'.pg_result($res,$i,'servico_realizado').'"'. $selected.'>'.pg_result($res,$i,'descricao').'</option>';

		}

		break;

	case "atendimento_pela_familia_produto" :
		if(!empty($produto_referencia)){
			$sql = "
				SELECT
                    tbl_produto.familia
				FROM
                    tbl_produto
                        JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
				WHERE
                    tbl_produto.referencia = '$produto_referencia'
                    AND tbl_linha.fabrica = $login_fabrica;";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res) == 1){
				$familia = pg_fetch_result($res, 0, "familia");
				$sql = "SELECT tipo_atendimento, descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND familia = $familia";
				$res = pg_query($con, $sql);

				if(pg_num_rows($res) > 0){
					echo "<option value='' selected>".traduz("selecione", $con, $cook_idioma)."</option>";
					for($i = 0; $i < pg_num_rows($res); $i++) {
						extract(pg_fetch_array($res));

						echo "<option value='$tipo_atendimento' label='$descricao'>$descricao</option>";
					}
				}else
                    echo "<option value='0' selected>Nenhum Atendimento Encontrado</option>";
			}else
                echo "<option value='0' selected>Produto Inválido</option>";
		}

	break;

	case "defeito_constatado_pela_familia_produto" :
		if(!empty($produto)){
			$sql = "
				SELECT familia
				FROM tbl_produto
				WHERE produto = $produto;";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res) == 1){
				$familia = pg_fetch_result($res, 0, "familia");

                if($login_fabrica == 20){
                    $sql = "SELECT DISTINCT
                                tbl_defeito_constatado.defeito_constatado,
                                tbl_defeito_constatado.descricao
                            FROM tbl_familia
                            JOIN   tbl_familia_defeito_constatado USING(familia)
                            JOIN   tbl_defeito_constatado         USING(defeito_constatado)
                            WHERE tbl_defeito_constatado.fabrica = $login_fabrica
                            AND tbl_familia_defeito_constatado.familia = $familia
                            ORDER BY tbl_defeito_constatado.descricao ASC;";
                }else{
                    $sql = "SELECT DISTINCT
                                tbl_defeito_constatado.defeito_constatado,
                                tbl_defeito_constatado.descricao
                            FROM tbl_diagnostico
                                JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado
                            WHERE
                                tbl_defeito_constatado.fabrica = $login_fabrica
                                AND tbl_diagnostico.familia = $familia ORDER BY tbl_defeito_constatado.descricao ASC;";
                }

                /*
				$sql = "


                        SELECT
                            tbl_defeito_constatado.defeito_constatado,
                            tbl_defeito_constatado.codigo,
                            tbl_defeito_constatado.descricao
                        FROM tbl_defeito_constatado
                            JOIN tbl_produto_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_produto_defeito_constatado.defeito_constatado
                                AND tbl_produto_defeito_constatado.produto = $produto
                        WHERE
                            fabrica = $login_fabrica
                        ORDER BY
                            tbl_defeito_constatado.descricao;";
                            */
				$res = pg_query($con, $sql);

				if(pg_num_rows($res) > 0){
					echo "<option value='' selected>".traduz("selecione", $con, $cook_idioma)."</option>";
					for($i = 0; $i < pg_num_rows($res); $i++) {
						extract(pg_fetch_array($res));

						echo "<option value='$defeito_constatado' label='$descricao'>$descricao</option>";
					}
				}else
					echo "<option value='0' selected>nenhum defeito constatado encontrado!</option>";
			}else
				echo "<option value='0' selected>produto não encontrado</option>";
		}

	break;

	case "causa_defeito_peca_referencia" :

		if(!empty($peca_referencia)){
			$sql = "
				SELECT peca
				    FROM tbl_peca
				WHERE referencia = '$peca_referencia'
                    AND fabrica = $login_fabrica;";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res) == 1){
				$peca = pg_fetch_result($res, 0, "peca");

				$sql = "
                        SELECT
                            tbl_defeito.defeito,
                            tbl_defeito.descricao,
                            tbl_defeito.codigo_defeito
                        FROM tbl_peca_defeito
                            JOIN tbl_defeito ON tbl_defeito.defeito = tbl_peca_defeito.defeito
                        WHERE tbl_peca_defeito.peca = $peca
                            AND tbl_defeito.ativo IS TRUE
                        ORDER BY descricao ASC;";
				$res = pg_query($con, $sql);

				if(pg_num_rows($res) > 0){
					echo "<option value='' selected>Selecione</option>";
					for($i = 0; $i < pg_num_rows($res); $i++) {
						extract(pg_fetch_array($res));

						echo "<option value='$defeito' label='$codigo_defeito - $descricao'>$descricao</option>";
					}
				}else
					echo "<option value='0' selected>nenhum resultado encontrado</option>";
			}else
				echo "<option value='0' selected>peça inválida</option>";
        }
	break;

    case "defeito_constatado_pelo_produto" :
		if(!empty($produto_referencia)){
			$sql = "
				SELECT
                    tbl_produto.produto
				FROM
                    tbl_produto
                        JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
				WHERE
                    tbl_produto.referencia = '$produto_referencia'
                    AND tbl_linha.fabrica = $login_fabrica;";
			$res = pg_query($con, $sql);
            if (pg_num_rows($res) == 1) {
				$produto = pg_fetch_result($res, 0, "produto");

                $sql = "
                    SELECT
                        tbl_defeito_constatado.defeito_constatado,
                        tbl_defeito_constatado.codigo,
						COALESCE(DCI.descricao, tbl_defeito_constatado.descricao) AS descricao
                    FROM tbl_defeito_constatado
                        JOIN tbl_produto_defeito_constatado ON tbl_produto_defeito_constatado.defeito_constatado = tbl_defeito_constatado.defeito_constatado
                   LEFT JOIN tbl_defeito_constatado_idioma AS DCI
                          ON DCI.defeito_constatado = tbl_defeito_constatado.defeito_constatado
                         AND DCI.idioma = '$sistema_lingua'
                    WHERE
                        tbl_defeito_constatado.fabrica = {$login_fabrica}
                        AND tbl_defeito_constatado.ativo IS TRUE
                        AND tbl_produto_defeito_constatado.produto = {$produto}
                        $cond
                    ORDER BY tbl_defeito_constatado.descricao ASC;";
                $res = pg_query($con,$sql);

				if(pg_num_rows($res) > 0){
					echo "<option value='' selected>".traduz("selecione", $con, $cook_idioma)."</option>";
					for($i = 0; $i < pg_num_rows($res); $i++) {
						extract(pg_fetch_assoc($res));

						$mostra_codigo = strlen($codig) ? $codigo.' - ' : '';

						if(empty($codigo))
							echo "<option value='$defeito_constatado' label='$mostra_codigo $descricao'>$descricao</option>";
						else
							echo "<option value='$defeito_constatado' label='$mostra_codigo $descricao'>".sprintf("%02d",$codigo)." - {$descricao}</option>";
					}
				}else
					echo "<option value='0' selected>".traduz("nenhum.resultado.encontrado", $con, $cook_idioma)."</option>";
			}else
				echo "<option value='0' selected>".traduz("produto.nao.encontrado", $con, $cook_idioma)."</option>";
		}
	break;

	case "defeito_reclamado_pela_linha" :
		if(!empty($produto_referencia)){
			$sql = "
				SELECT
                    tbl_produto.linha
				FROM
                    tbl_produto
                        JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
				WHERE
                    tbl_produto.referencia = '$produto_referencia'
                    AND tbl_linha.fabrica = $login_fabrica;";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res) == 1){
				$linha = pg_fetch_result($res, 0, "linha");

				if ($login_fabrica <> 20) {
					$cond = empty($defeito_reclamado)  ? '' : " AND DR.defeito_reclamado = $defeito_reclamado";
				}
				$sql = "SELECT DR.defeito_reclamado, COALESCE(DRI.descricao, DR.descricao) AS descricao
						  FROM tbl_defeito_reclamado
						  JOIN tbl_linha USING(linha, fabrica)
					 LEFT JOIN tbl_defeito_reclamado_idioma DRI
							ON DRI.defeito_reclamado = DR.defeito_reclamado
						 WHERE DR.fabrica = $login_fabrica
						   AND tbl_linha.ativo IS TRUE
						   $cond
						   AND DR.ativo IS TRUE
						 ORDER BY descricao ASC;";
				$res = pg_query($con, $sql);

				if(pg_num_rows($res) > 0){
					echo "<option value='' selected>".traduz("selecione", $con, $cook_idioma)."</option>";
					for($i = 0; $i < pg_num_rows($res); $i++) {
						extract(pg_fetch_array($res));
						echo "<option value='$defeito_reclamado' label='$descricao'>$descricao</option>";
					}
				}else
					echo "<option value='0' selected>nenhum defeito encontrado</option>";
			}else
				echo "<option value='0' selected>produto não encontrado</option>";
		}else
            echo "<option value='0' selected>produto não informado</option>";

	break;

    case "garantia_produto_ou_cortesia";
        if($login_fabrica == 20){ //hd_chamado=2843341 interação 314
            $cond_acessorio = "AND SR.garantia_acessorio IS NOT TRUE";
        }
		$sql = "
			SELECT SR.servico_realizado, COALESCE(SRI.descricao, SR.descricao) AS descricao
			  FROM tbl_servico_realizado        SR
			  LEFT JOIN tbl_servico_realizado_idioma SRI
				ON SR.servico_realizado = SRI.servico_realizado
			   AND idioma  = UPPER('{$cook_idioma}')
			 WHERE SR.fabrica = $login_fabrica
			   AND SR.solucao IS NOT TRUE
			   AND SR.ativo IS TRUE
			   $cond_acessorio
			 ORDER BY descricao";
        $res = pg_exec ($con,$sql);
        echo '<option></option>';
        for($i=0; $i<pg_num_rows($res); $i++){
            echo '<option value="'.pg_result($res,$i,'servico_realizado').'"'. $selected.'>'.pg_result($res,$i,'descricao').'</option>';
        }
    break;
}
