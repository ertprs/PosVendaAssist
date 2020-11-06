<?php
include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";
$admin_privilegios = "auditoria";
include_once "autentica_admin.php";
include_once __DIR__.'/funcoes.php';
include_once '../helpdesk/mlg_funciones.php';

include '../../fn_traducao.php';

if ($_POST['ajax_valida_admin']) {
    $sqlAdmin = "SELECT admin FROM tbl_admin WHERE admin = $login_admin AND fabrica = $login_fabrica AND intervensor IS TRUE";
    $resAdmin = pg_query($con, $sqlAdmin);
    if (pg_num_rows($resAdmin) == 0) {
        echo 'erro';
    } else {
        echo 'ok';
    }
    exit();
}

function ultima_interacao($os) {
	global $con, $login_fabrica;

	$select = "SELECT admin, posto FROM tbl_os_interacao WHERE fabrica = {$login_fabrica} AND os = {$os} ORDER BY data DESC LIMIT 1";
	$result = pg_query($con, $select);

	if (pg_num_rows($result) > 0) {
		$admin = pg_fetch_result($result, 0, "admin");
		$posto = pg_fetch_result($result, 0, "posto");

		if (!empty($admin)) {
			$ultima_interacao = "fabrica";
		} else {
			$ultima_interacao = "posto";
		}
	}

	return $ultima_interacao;
}

function os_excluida($os) {
	global $con, $login_fabrica;

    if (in_array($login_fabrica, array(152,180,181,182))) { //hd_chamado=3049906
        $select = "SELECT cancelada FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
        $result = pg_query($con, $select);

        if(pg_fetch_result($result, 0, 'cancelada') == 't'){
            return true;
        }
        return false;
    }else{
    	$select = "SELECT os_excluida FROM tbl_os_excluida WHERE fabrica = {$login_fabrica} AND os = {$os}";
    	$result = pg_query($con, $select);

        if (pg_num_rows($result) > 0) {
            return true;
        }
        return false;

    }
}

function os_status($os, $auditoria_os) {
	global $con, $login_fabrica;
	$mensagem = "";

	$select = "SELECT * FROM tbl_auditoria_os WHERE os = {$os} AND auditoria_os = $auditoria_os";
	$result = pg_query($con, $select);

	if (pg_num_rows($result) > 0) {
		$liberada      = pg_fetch_result($result, 0, "liberada");
		$paga_mao_obra = pg_fetch_result($result, 0, "paga_mao_obra");
		$justificativa = pg_fetch_result($result, 0, "justificativa");
		$reprovada     = pg_fetch_result($result, 0, "reprovada");
		$cancelada     = pg_fetch_result($result, 0, "cancelada");

		if(!empty($liberada)){
			if($paga_mao_obra == 't'){
				$mensagem = "Aprovado";
			}else if($paga_mao_obra == 'f'){
				$mensagem = "Aprovado Sem MO";
			}
		}else{
			if(!empty($cancelada)){
				$mensagem = "Cancelado OS";
			}else if(!empty($reprovada)){
				$mensagem = "Reprovado OS";
			}
		}
	}
	return $mensagem;
}

if(isset($_REQUEST['btn_acao']) && !empty($_REQUEST['btn_acao'])){
	$btn_acao = $_REQUEST['btn_acao'];
}else if(isset($_REQUEST['btn_listar_auditoria']) && !empty($_REQUEST['btn_listar_auditoria'])){
	$btn_acao = "submit";
}

if($btn_acao == 'consultaStatus'){
	$os           = $_POST['os'];
	$auditoria_os = $_POST['auditoria_os'];

	if(os_excluida($os)){
		$resultado = array("tipoStatus" => "important", "descricao" => "OS cancelada");
	}else{
		$status = os_status($os, $auditoria_os);
		if($status == "Aprovado"){
			$resultado = array("tipoStatus" => "success", "descricao" => traduz($status));

		}else if($status == "Aprovado Sem MO"){
			$resultado = array("tipoStatus" => "warning", "descricao" => traduz($status));

		}else if($status == "Reprovado OS"){
			$resultado = array("tipoStatus" => "inverse", "descricao" => traduz($status));
		}
	}
    echo json_encode($resultado); exit;
}

function getOrcamento($osInterna){
    global $con, $login_fabrica;
    $sql = "SELECT SUM(tbl_os_item.custo_peca) as total_pecas
            FROM tbl_os_produto
            JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
            WHERE tbl_os_produto.os = {$osInterna}";

    $res = pg_query($con, $sql);

    if(!$res){
        throw new Exception(utf8_encode(traduz("Não foi possível obter orçamento")));
    }

    $orcamento = pg_result($res, 0, 0);

    if(empty($orcamento)){
        throw new Exception(utf8_encode(traduz("Orçamento do Posto Interno não preenchido")));
    }
    return $orcamento;
}

/**
 *$arrData = array('campo tbl_comunicado'=> 'valor'  )
 *
 */
function insereComunicado($arrData){
    global $con, $login_fabrica;
    $camposTblComunicado = array_keys($arrData);

    $insertCampos = implode(',', $camposTblComunicado);
    $insertValores = array_values($arrData);
    $insertValores = implode(',', $insertValores);
    $insert = "INSERT INTO tbl_comunicado ({$insertCampos}) VALUES ({$insertValores})";

    $res = pg_query($con,$insert);

    if(!$res){
        throw new Exception(utf8_encode(traduz('Erro ao enviar comunicado ao posto')));
    }
}

function getOsExterna($osInterna){
    global $con, $login_fabrica;

    $sql = "SELECT os_numero FROM tbl_os WHERE fabrica = $login_fabrica AND os = $osInterna";
    $res = pg_query($con, $sql);

    if(!pg_num_rows($res)){
        throw new Exception(utf8_encode(traduz("OS não encontrada")));
    }

    $osExterna = pg_fetch_result($res, 0, 0);
    if(empty($osExterna)){
        throw new Exception(utf8_encode(traduz("OS do posto externo não existe")));
    }

    return $osExterna;
}

function mudaStatusOsPostoExterno($osExterna, $status){
	global $con, $login_fabrica, $os, $campos;

    $insert = "INSERT INTO tbl_os_status (os, status_os, fabrica_status, observacao) VALUES ($osExterna, $status, $login_fabrica, 'Orçamento em aprovação')";

    $res = pg_query($con, $insert);

    if(strlen(pg_last_error($con)) > 0){
        throw new Exception(utf8_encode(traduz('Erro ao alterar status da OS externa')));
    }
}

function getPostoExterno($osExterna){
    global $con, $login_fabrica;

    $sqlPostoExterno = "SELECT posto FROM tbl_os WHERE fabrica = $login_fabrica AND os = $osExterna";
    $res = pg_query($con, $sqlPostoExterno);

    if(!$res){
        throw new Exception(utf8_encode(traduz("Erro ao encontrar posto externo")));
    }
    $postoExterno = pg_fetch_result($res, 0, 0);

    if(empty($postoExterno)){
        throw new Exception(utf8_encode(traduz("Posto Externo não encontrado")));
    }

    return $postoExterno;
}
function insereInteracaoOs($arrData){
    global $con, $login_fabrica;
    $camposTblOsInteracao = array_keys($arrData);

    $insertCampos = implode(',', $camposTblOsInteracao);
    $insertValores = array_values($arrData);
    $insertValores = implode(',', $insertValores);

    $insert = "INSERT INTO tbl_os_interacao ({$insertCampos}) VALUES ({$insertValores})";
    $res = pg_query($con,$insert);

    if(!$res){
        throw new Exception(utf8_encode(traduz('Erro ao interagir na OS')));
    }


}

function getOsStatus($os){
    global $con, $login_fabrica;

    $sql = "SELECT status_os FROM tbl_os_status where os = {$os} and fabrica_status = {$login_fabrica} order by os_status desc limit 1";
    $res = pg_query($con, $sql);

    if(!$res){
        throw new Exception(utf8_encode(traduz("Erro ao verificar comunicado")));
    }
    return pg_fetch_result($res, 0, 0);
}

function enviaComunicado($os){

    $statusOs = getOsStatus($os);

    if($statusOs == 230){
        return false;
    }else {
        return true;
    }
}
function getDadosPecasUtilizadas($os){
    global $con, $login_fabrica;

    $sql = 'SELECT referencia as peca_referencia,
                   descricao as peca_descricao,
                   tbl_os_item.custo_peca
            FROM tbl_os_produto
            INNER JOIN tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
            INNER JOIN tbl_peca on tbl_peca.peca = tbl_os_item.peca
            WHERE tbl_os_produto.os = '. $os;

    $res = pg_query($con, $sql);
    if(!$res){
        throw new Exception(utf8_encode(traduz("Erro ao obter peças da OS")));
    }

    return pg_fetch_all($res);
}

function montaStringPecasUtilizadas($pecas){
    $str = '<ul>';

    foreach ($pecas as $dados) {
        $dados['custo_peca'] = number_format($dados['custo_peca'], 2, ',','');
        $str .= '<li>' . $dados['peca_referencia'] . ' - ' . $dados['peca_descricao'] . ': R\$' . $dados['custo_peca'] . '</li>';
    }
    $str .= '</ul>';
    return $str;
}

if($btn_acao == 'envia_orcamento_posto'){
    try{
        pg_query($con, 'BEGIN TRANSACTION');
        if(!isset($_POST['os'])){
            throw new Exception(utf8_encode(traduz("Envie o número da OS")));
        }
        $os = $_POST['os'];

        $osExterna = getOsExterna($os);

        if(!enviaComunicado($osExterna)){
            throw new Exception(utf8_encode(traduz('Comunicado já enviado ao posto')));
        }

        $postoExterno = getPostoExterno($osExterna);
        $orcamento = getOrcamento($os);

        $pecasUtilizadas = getDadosPecasUtilizadas($os);
        $msgPecasUtilizadas = montaStringPecasUtilizadas($pecasUtilizadas);

        $msg = "'Orçamento Disponível. O valor do orçamento para a OS {$osExterna} é de R\${$orcamento}.";

        if ($login_fabrica == 156) {
            $msg .= traduz(' Obs: Garantia de peças e serviços são de 3 meses.');
        }

        $msg .= "'";

        insereComunicado(array('ativo'=>'true','fabrica' =>$login_fabrica, 'posto'  => $postoExterno, 'mensagem'=> $msg, 'obrigatorio_site' => 'true' ));
        mudaStatusOsPostoExterno($osExterna, 230);

        $msg = "'Orçamento Disponível. O valor do orçamento para a OS {$osExterna} é de R\${$orcamento}.<br/>
                 Peças Utilizadas:<br/>
                 {$msgPecasUtilizadas}'";
        insereInteracaoOs(array('os' => $osExterna, 'admin' => $login_admin, 'comentario'=>$msg, 'fabrica' => $login_fabrica));
        insereInteracaoOs(array('os' => $os, 'admin' => $login_admin, 'comentario'=>$msg, 'fabrica' => $login_fabrica));

        pg_query($con, "COMMIT TRANSACTION");

        echo json_encode(array("msg" => utf8_encode(traduz("Comunicado com orçamento enviado ao posto externo"))));
    }catch(Exception $ex){
        pg_query($con, "ROLLBACK TRANSACTION");
        echo json_encode(array("msg" => $ex->getMessage()));
    }
	exit;
}

if ($btn_acao == "inserirKM") {
	$os      = trim($_POST['os']);
	$novo_km = trim($_POST['km']);

	pg_query($con, "BEGIN");

	if ($login_fabrica == 158) {
		$sql = "SELECT
					tbl_os_extra.extrato,
					tbl_os.data_fechamento,
					tbl_os.qtde_km
				FROM tbl_os
				INNER JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
				WHERE tbl_os.fabrica = {$login_fabrica}
				AND tbl_os.os = {$os}";
		$res = pg_query($con, $sql);

		$extrato         = pg_fetch_result($res, 0, "extrato");
		$data_fechamento = pg_fetch_result($res, 0, "data_fechamento");
		$antigo_km       = pg_fetch_result($res, 0, "qtde_km");
	}


	$sql = "UPDATE tbl_os SET qtde_km = $novo_km WHERE tbl_os.os = $os AND tbl_os.fabrica = $login_fabrica";
	pg_query($con, $sql);

	if(strlen(pg_last_error()) > 0 || !empty($msg_erro_calcula_km)){
		pg_query($con, "ROLLBACK");
		$resposta = array("resultado" => false, "mensagem" => traduz("Erro ao atualizar a quantidade de KM da os $os."));
	}else{
		if ($login_fabrica == 158 && !empty($data_fechamento) && empty($extrato)) {
			pg_query($con, "COMMIT");

			try {
				$classOs = new \Posvenda\Os($login_fabrica, $os);
				$classOs->_model->calculaKM($os);

				$resposta = array("resultado" => true);
			} catch (Exception $e) {
				$sql = "UPDATE tbl_os SET qtde_km = $antigo_km WHERE tbl_os.os = $os AND tbl_os.fabrica = $login_fabrica";
				pg_query($con, $sql);

				$resposta = array("resultado" => false, "mensagem" => traduz("Erro ao atualizar a quantidade de KM da os $os."));
			}
		} else {
			pg_query($con, "COMMIT");
			$resposta = array("resultado" => true);
		}
	}

	echo json_encode($resposta); exit;
}

if ($btn_acao == "aprovaOS") {
	$os           = trim($_POST['os']);
	$mao_obra     = trim($_POST['mao_obra']);
	$auditoria_os = trim($_POST['auditoria_os']);
	$condicao     = "";
	$updateJustificativa = "";
    $excepcional = $_POST['excepcional'];

    if ($login_fabrica == 148 && isset($_POST["tipo_auditoria"])) {
        $tp_aud = trim($_POST["tipo_auditoria"]);
    }

    if (in_array($login_fabrica, array(152,180,181,182))) {
        $auxiliar = trim($_POST['classificacao']);

        if (empty($auxiliar)) {
            $resposta = array("resultado" => false, "mensagem", traduz("Erro ao identificar a classificação da O.S."));
            echo json_encode($resposta); exit;
        } else {
            $classificacao = $auxiliar;
        }
    }

	if (in_array($login_fabrica, array(148))) {
        if($excepcional == 'sim' || $tp_aud == 'excepcional'){
            $justificativa = trim($_POST['justificativa']);

            $campos_add = ["tipo_auditoria"=>"excepcional"];
            $campos_add = json_encode($campos_add);

            $updateJustificativa = ", campos_adicionais = coalesce(campos_adicionais, '{}') || '$campos_add'  , justificativa = 'Concedido Garantia Excepcional. Justificativa: $justificativa'";
        }else{
    		$justificativa = trim($_POST['justificativa']);
    		$updateJustificativa = ", justificativa = '$justificativa'";
        }
	}

	pg_query($con, "BEGIN");

	if($mao_obra == "true"){
		$condicao = ", paga_mao_obra = 'f'";
	}

	$sql = "UPDATE tbl_auditoria_os SET liberada = current_timestamp, bloqueio_pedido = false,
				admin = $login_admin $condicao {$updateJustificativa}
		WHERE tbl_auditoria_os.os = $os AND auditoria_os = $auditoria_os;";
	pg_query($con, $sql);

	$sql = "SELECT posto FROM tbl_os WHERE os = {$os};";
	$resPosto = pg_query($con,$sql);

	if (in_array($login_fabrica, array(156))) {

		$sql = "SELECT
				oi.os_item,
				oi.qtde,
				p.referencia as peca_referencia,
				p.referencia||' - '||p.descricao as peca_descricao,
				oi.custo_peca
			FROM tbl_os_item oi
			JOIN tbl_peca p USING(peca)
			WHERE oi.fabrica_i = {$login_fabrica}
			AND oi.os_produto IN (SELECT
							os_produto
						FROM tbl_os_produto
						WHERE os = {$os});
			";

		$resPeca = pg_query($con,$sql);
		$count_k = pg_num_rows($resPeca);
	}

    if ($login_fabrica == 160 or $replica_einhell) {
        $sqlBon = "SELECT auditoria_os,tbl_os_extra.extrato
                    FROM tbl_auditoria_os
                    JOIN tbl_os_extra USING(os)
                    WHERE TO_ASCII(observacao, 'LATIN-9') = 'Auditoria de Aprovacao da Bonificacao'
                    AND auditoria_os = {$auditoria_os}";
        $resBon = pg_query($con, $sqlBon);

        if (pg_num_rows($resBon) > 0) {
            $extrato_lancar = pg_fetch_result($resBon, 0, 'extrato');

            //liberando lançamento no extrato
            $sqlLancamento = "UPDATE tbl_extrato_lancamento SET extrato = {$extrato_lancar}, historico = 'Bonificação de avaliação baixa aprovada, a bonificação foi liberada' WHERE os = {$os} AND descricao = 'bonificacao' AND debito_credito = 'C'";
            $resLancamento = pg_query($con, $sqlLancamento);

            $sqlTotalBonificacao = "SELECT COALESCE(valor, 0) as valor FROM tbl_extrato_lancamento WHERE extrato = {$extrato_lancar} AND os = {$os} AND descricao = 'bonificacao' AND debito_credito = 'C'";
            $resTotalBonificacao = pg_query($con, $sqlTotalBonificacao);

            $totalBonificacao = pg_fetch_result($resTotalBonificacao, 0, 'valor');

            if ($totalBonificacao > 0) {
                $sqlAtualizaExtrato = "UPDATE tbl_extrato SET total = total + {$totalBonificacao} WHERE extrato = {$extrato_lancar}";
                $resAtualizaExtrato = pg_query($con, $sqlAtualizaExtrato);
            }
        }
    }

	$sql = "SELECT
			tbl_auditoria_status.descricao
		FROM tbl_auditoria_os
		INNER JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
		WHERE tbl_auditoria_os.os = {$os}
		AND tbl_auditoria_os.auditoria_os = {$auditoria_os}";

	$resAud = pg_query($con, $sql);

	if(pg_num_rows($resPosto) > 0){
		$posto = pg_fetch_result($resPosto, 0, "posto");
		$auditoria_desc = pg_fetch_result($resAud, 0, "descricao");

		if ($count_k > 0) {
			$tablePecas = "<br /><br />";
			$tablePecas .= "<table border=1>";
			$tablePecas .= "<thead>";
			$tablePecas .= "<tr>";
			$tablePecas .= "<th><b>".traduz("Código")."</b></th>";
			$tablePecas .= "<th><b>".traduz("Descrição")."</b></th>";
			$tablePecas .= "<th><b>".traduz("Qtde")."</b></th>";
			$tablePecas .= "<th><b>".traduz("Valor")."</b></th>";
			$tablePecas .= "</tr>";
			$tablePecas .= "</thead>";
			$tablePecas .= "<tbody>";
			for ($k = 0; $k < $count_k; $k++) {
				$c_os_item                = pg_fetch_result($resPeca, $k, os_item);
				$c_peca_referencia    = pg_fetch_result($resPeca, $k, peca_referencia);
				$c_peca_descricao     = pg_fetch_result($resPeca, $k, peca_descricao);
				$c_qtde                       = pg_fetch_result($resPeca, $k, qtde);
				$c_custo_peca             = pg_fetch_result($resPeca, $k, custo_peca);
				$tablePecas .= "<tr>";
				$tablePecas .= "<td>".$c_peca_referencia."</td>";
				$tablePecas .= "<td>".$c_peca_descricao."</td>";
				$tablePecas .= "<td>".$c_peca_descricao."</td>";
				$tablePecas .= "<td>".$c_qtde."</td>";
				$tablePecas .= "<td>".$c_custo_peca."</td>";
				$tablePecas .= "</tr>";
			}
			$tablePecas .= "</tbody>";
                		$tablePecas .= "</table>";

                		$tablePecas = utf8_encode($tablePecas);
		}

        if($login_fabrica == 156){
            $sql = "INSERT INTO tbl_comunicado
                    (fabrica,posto, obrigatorio_site, tipo, ativo, descricao, mensagem)
            VALUES (
                    {$login_fabrica},{$posto}, true, 'Com. Unico Posto', true,
                    'Auditoria',
                    'O orçamento da OS <a href=os_press.php?os=$os> {$os} </a> foi aprovado e a OS poderá ser consertada')";

        }else{
            $sql = "INSERT INTO tbl_comunicado
                    (fabrica,posto, obrigatorio_site, tipo, ativo, descricao, mensagem)
            VALUES (
                    {$login_fabrica},{$posto}, true, 'Com. Unico Posto', true,
                    'Auditoria',
                    'OS <a href=os_press.php?os=$os>{$os}</a> foi aprovada na ".$auditoria_desc."{$tablePecas}');";
        }

		pg_query($con, $sql);
	}

	if(strlen(pg_last_error()) > 0){
		pg_query($con, "ROLLBACK");
		$resposta = array("resultado" => false, "mensagem", traduz("Erro ao aprovar a os $os."));
	}else{
        if ($login_fabrica == 148) {
            try{
                if (file_exists("classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php")) {
                    include_once "classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php";
                    $className = '\\Posvenda\\Fabricas\\_' . $login_fabrica . '\\Os';
                    $classOs = new $className($login_fabrica, $os);
                } else {
                    $classOs = new \Posvenda\Os($login_fabrica, $os);
                }
                $classOs->VerificaIntervencao($con);
            }catch(Exception $ex){
                $nao_fecha_os = $ex;
            }

            if (empty($nao_fecha_os) && !is_object($nao_fecha_os)) {
                try{
                    $classOs->verificaSolucaoOs($con);
                    $sql = "SELECT  tbl_os_item.os_item ,
                                tbl_os_item.os_produto,
                                tbl_os_item.qtde,
                                tbl_os_item.peca,
                                tbl_servico_realizado.gera_pedido ,
                                tbl_servico_realizado.troca_de_peca ,
                                tbl_pedido_item.preco,
                                tbl_pedido_item.tabela,
                                tbl_peca.referencia
                            FROM tbl_os_item
                            INNER JOIN tbl_os_produto on tbl_os_item.os_produto = tbl_os_produto.os_produto
                            INNER JOIN tbl_os on tbl_os_produto.os = tbl_os.os
                            INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$login_fabrica}
                            LEFT JOIN tbl_pedido_item on tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
                            INNER JOIN tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
                            WHERE tbl_os.os = {$os}
                                AND tbl_os.fabrica = {$login_fabrica} ";
                    $res = pg_query($con, $sql);
                    $itens = pg_fetch_all($res);

                    $preco_peca = \Posvenda\Regras::get("preco_peca", "mao_de_obra", $login_fabrica);

                    if (!empty($preco_peca)) {
                        $tabela_preco_peca = \Posvenda\Regras::get("tabela_preco_peca", "mao_de_obra", $login_fabrica);

                        if (!empty($tabela_preco_peca)) {
                            $sqlTabela = "
                                SELECT tabela FROM tbl_tabela WHERE fabrica = {$login_fabrica} AND LOWER(descricao) = LOWER('{$tabela_preco_peca}')
                            ";
                            $resTabela = pg_query($con, $sqlTabela);

                            if (pg_num_rows($resTabela) > 0) {
                                $tabela_preco_peca = pg_fetch_result($resTabela, 0, "tabela");
                            } else {
                                throw new \Exception(traduz("Erro ao atualizar valores das peças da OS {$os}"));
                            }
                        }
                    }

                    foreach ($itens as $key => $item) {
                        if (!empty($tabela_preco_peca)) {
                            unset($updatePrecoPeca);

                            $sqlPrecoPeca = "
                                SELECT preco FROM tbl_tabela_item WHERE tabela = {$tabela_preco_peca} AND peca = {$item['peca']}
                            ";
                            $resPrecoPeca = pg_query($con, $sqlPrecoPeca);

                            if (!pg_num_rows($resPrecoPeca)) {
                                throw new \Exception(utf8_encode(traduz("Erro ao finalizar a OS {$os}, a peça {$item['referencia']} está sem preço")));
                            } else {
                                $preco_peca = pg_fetch_result($resPrecoPeca, 0, "preco");
                                $updatePrecoPeca = ", preco = {$preco_peca}";
                            }
                        }

                        if($item["gera_pedido"] == "t" and $item["troca_de_peca"] == "t"){
                            $adicional_preco_peca = \Posvenda\Regras::get("adicional_preco_peca", "mao_de_obra", $login_fabrica);

                            if (!empty($adicional_preco_peca)) {
                                switch ($adicional_preco_peca["formula"]) {
                                    case "+%":
                                        if (!empty($adicional_preco_peca["valor"])) {
                                            $preco_porcentagem = $item['total_item'] / 100;
                                            $preco = $item['preco'] + ($preco_porcentagem * $adicional_preco_peca["valor"]);
                                        }
                                    break;
                                }
                            }else{
                                $preco = $item['preco'] ;
                            }

                            if(empty($preco)) {
                                $preco = 0 ;
                            }

                            $sql = "UPDATE tbl_os_item  SET custo_peca = {$preco} {$updatePrecoPeca} WHERE os_item = {$item['os_item']}";
                            $res = pg_query($con,$sql);
                        } elseif ($item["troca_de_peca"] == "t" and $item["gera_pedido"] == "f") {

                            $sql = " SELECT tbl_produto.linha ,tbl_os.posto
                            from tbl_os_produto
                            INNER JOIN tbl_os on tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
                            INNER JOIN tbl_produto on tbl_produto.produto = tbl_os_produto.produto and tbl_produto.fabrica_i = $login_fabrica
                            WHERE tbl_os_produto.os_produto = {$item['os_produto']} ";

                            $res = pg_query($con,$sql);

                            $linha = pg_fetch_result($res, 0, "linha");
                            $posto = pg_fetch_result($res, 0, "posto");

                            $sql = "SELECT tbl_posto_linha.tabela
                                        from tbl_posto_linha
                                        INNER JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha
                                        INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto_linha.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                                        WHERE tbl_posto_linha.linha= {$linha} AND tbl_posto_fabrica.posto = {$posto}";
                            $res = pg_query($con,$sql);
                            $tabela = pg_fetch_result($res, 0, "tabela");

                            $sql = "SELECT tbl_tabela_item.preco
                                    FROM tbl_tabela_item
                                    INNER JOIN tbl_tabela on tbl_tabela.tabela = tbl_tabela_item.tabela and tbl_tabela.fabrica = {$login_fabrica}
                                    WHERE tbl_tabela_item.peca = {$item['peca']}
                                    AND tbl_tabela.tabela = {$tabela} ";
                            $res = pg_query($con,$sql);
                            $preco = pg_fetch_result($res, 0, "preco");

                            if(!empty($preco)) {

                                 $adicional_preco_peca = \Posvenda\Regras::get("adicional_preco_peca", "mao_de_obra", $login_fabrica);

                                if (!empty($adicional_preco_peca)) {
                                    switch ($adicional_preco_peca["formula"]) {
                                        case "+%":
                                            if (!empty($adicional_preco_peca["valor"])) {
                                                $preco_porcentagem = $preco / 100;
                                                $preco = $preco + ($preco_porcentagem * $adicional_preco_peca["valor"]);
                                            }
                                        break;
                                    }
                                }
                                $total = $preco;
                                if(empty($total)) {
                                    $total = 0 ;
                                }
                                $sql = "UPDATE tbl_os_item set custo_peca = {$total} {$updatePrecoPeca} where os_item ={$item['os_item']} ";
                                $res = pg_query($con,$sql);

                            }
                        }

                        if(strlen(pg_last_error()) > 0){
                            throw new \Exception(traduz("Erro ao atualizar mão de obra da OS : {$os} -- ".pg_last_error()));
                        }
                    }

					$updateOrigem = "";

                    if (!is_null($origem)) {
                        $updateOrigem = "UPDATE tbl_os_campo_extra SET origem_fechamento = '$origem' WHERE os = {$os};";
                    }

                    $sql = "
                        UPDATE tbl_os
                        SET data_fechamento = CURRENT_DATE, finalizada = CURRENT_TIMESTAMP
                        WHERE os = {$os} AND fabrica = {$login_fabrica};

                        {$updateOrigem}
                        ";
                    $res = pg_query($con, $sql);

                    if(!$res){
                        throw new \Exception(traduz("Ocorreu um erro ao tentar finalizar a OS"));
                    }
                    pg_query($con, "COMMIT");
                    $resposta = array("resultado" => true);
                    $classOs->calculaOs();
                }catch(Exception $err){
                    pg_query($con, "ROLLBACK");
                    $resposta = array("resultado" => false, "mensagem" => traduz("Erro ao aprovar a os $os. Erro: {$err}"));
                }
            }else{
                pg_query($con, "COMMIT");
                $resposta = array("resultado" => true);
            }
        } else if (in_array($login_fabrica, array(152,180,181,182))) {  /*HD - 4292800*/
            $aux_sql = "SELECT os, campos_adicionais FROM tbl_os_campo_extra WHERE os = $os";
            $aux_res = pg_query($con, $aux_sql);

            if (pg_num_rows($aux_res) > 0) {
                $aux_add                    = json_decode(pg_fetch_result($aux_res, 0, 'campos_adicionais'), true);
                $aux_add["classificacao"][] = $classificacao;
                $aux_add                    = json_encode($aux_add);

                $aux_sql = "UPDATE tbl_os_campo_extra SET campos_adicionais = '$aux_add' WHERE os = $os";
            } else {
                $aux_add                    = array();
                $aux_add["classificacao"][] = $classificacao;
                $aux_add                    = json_encode($aux_add);

                $aux_sql = "INSERT INTO tbl_os_campo_extra(os, fabrica, campos_adicionais) VALUES($os, $login_fabrica, '$aux_add')";
            }

            $aux_res = pg_query($con, $aux_sql);

            $sqlEntregaTecnica = "SELECT tbl_os.os
                                  FROM tbl_os
                                  JOIN tbl_tipo_atendimento USING(tipo_atendimento)
                                  JOIN tbl_auditoria_os ON tbl_auditoria_os.auditoria_os = {$auditoria_os}
                                  AND tbl_auditoria_os.observacao ILIKE 'OS de entrega%'
                                  WHERE tbl_tipo_atendimento.entrega_tecnica IS TRUE
                                  AND tbl_os.os = {$os}";
            $resEntregaTecnica = pg_query($con, $sqlEntregaTecnica);

            if (pg_num_rows($resEntregaTecnica) > 0) {
                atualiza_status_checkpoint($os, "Aguardando Conserto");
            }

            if (pg_last_error()) {
                pg_query($con, "ROLLBACK");
                $resposta = array("resultado" => false, "mensagem", traduz("Erro ao gravar a classificação da O.S. $os"));
            } else {
                pg_query($con, "COMMIT");
                $resposta = array("resultado" => true);
            }
        }else{
            pg_query($con, "COMMIT");
            $resposta = array("resultado" => true);
        }
	}

	echo json_encode($resposta); exit;
}

if ($btn_acao == "reprovaOS") {
	$os            = trim($_POST['os']);
	$justificativa = trim($_POST['justificativa']);
	$auditoria_os  = trim($_POST['auditoria_os']);
    $campos_add_update = '';

    if ($login_fabrica == 148 && isset($_POST["tipo_auditoria"])) {
        $tp_aud = $_POST["tipo_auditoria"];

        if ($tp_aud == 'falta_informacao') {
            
            $campos_add = ["tipo_auditoria"=>"falta_informacao"];
            $campos_add = json_encode($campos_add);

            $campos_add_update = ", campos_adicionais = coalesce(campos_adicionais, '{}') || '$campos_add' ";
        }
    }

	pg_query($con, "BEGIN");

	$cond = " AND tbl_auditoria_os.auditoria_os = $auditoria_os";
	if ($login_fabrica == 148) {

        if (!isset($_POST['auditar'])) {

            $cond = "";

        } else {

            $sql = "SELECT observacao 
                    FROM tbl_auditoria_os
                    WHERE auditoria_os = {$auditoria_os}";

            $res = pg_query($con, $sql);

            $observacao = pg_fetch_result($res, 0, 'observacao');

            if ($observacao == "Auditoria de Fábrica") {

                $cond = "";
            }
        }
	}

	$sql = "UPDATE tbl_auditoria_os 
            SET reprovada = current_timestamp,
			    admin = $login_admin,
			    justificativa = '{$justificativa}'
                $campos_add_update
		    WHERE tbl_auditoria_os.os = $os 
            $cond";

	pg_query($con, $sql);

	$sql = "SELECT posto FROM tbl_os WHERE os = {$os}";
	$resPosto = pg_query($con,$sql);

	$sql = "SELECT
			tbl_auditoria_status.descricao
		FROM tbl_auditoria_os
		INNER JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
		WHERE tbl_auditoria_os.os = {$os}
		$cond";
	$resAud = pg_query($con, $sql);

    if (pg_num_rows($resPosto) > 0) {
        
        $posto = pg_fetch_result($resPosto, 0, "posto");
        $auditoria_desc = pg_fetch_result($resAud, 0, "descricao");

        if ($login_fabrica == 148) {
            
            $rowAud = pg_fetch_all($resAud);
            
            foreach ($rowAud as $keyAud => $valueAud) {
                $auditoria_d[] = $valueAud['descricao'];
            }
            
            $auditoria_desc = implode(', ', $auditoria_d);
        }

        if (!isset($_POST['auditar'])) {
            
            #Retirei porque estava quebrando a transação ERROR:  value too long for type character varying(50)
            #$msg = traduz("OS % foi reprovada na %",null,null,[$os , $auditoria_desc]);
            $msg = "OS $os foi reprovada na $auditoria_desc";
            
        }

        if ($login_fabrica == 156) {
            
            $qry_os_externa = pg_query($con,"SELECT os, posto FROM tbl_os WHERE os = (SELECT os FROM tbl_os WHERE os_numero = {$os})");

            $os_externa = pg_fetch_result($qry_os_externa, 0, 'os');
            $posto_externo = pg_fetch_result($qry_os_externa, 0, 'posto');

            $msg = traduz("OS % O produto não será reparado na fábrica e será devolvido para o posto autorizado.", null , null ,[$os]);

            $msg_externa = str_replace($os, $os_externa, $msg);

            try {
                insereInteracaoOs(
                    array(
                        "os" => $os,
                        "admin" => $login_admin,
                        "comentario" => "'{$msg}'",
                        "fabrica" => $login_fabrica
                    )
                );

                insereInteracaoOs(
                    array(
                        "os" => $os_externa,
                        "admin" => $login_admin,
                        "comentario" => "'{$msg_externa}'",
                        "fabrica" => $login_fabrica
                    )
                );
			} catch (Exception $e) {
                pg_query($con, "ROLLBACK");
                die(json_encode(array("resultado" => false, "mensagem",traduz("Erro ao reprovar a os $os."))));
            }
        }

        $sql = "INSERT INTO tbl_comunicado
                (fabrica,posto, obrigatorio_site, tipo, ativo, descricao, mensagem)
                VALUES ({$login_fabrica},{$posto}, 't', 'Com. Unico Posto', 't','Auditoria','$msg');";
        
				pg_query($con, $sql);
				

        if ($login_fabrica == 156) {
            $sql_externo = str_replace($posto, $posto_externo, $sql);
            $sql_externo = str_replace($msg, $msg_externa, $sql_externo);

            pg_query($con, $sql_externo);
        }
    }

    if ($login_fabrica == 148) {

        $sqlTemPendente = " SELECT os FROM tbl_auditoria_os WHERE reprovada IS NULL AND os = {$os} ";
        $resTemPendente = pg_query($con, $sqlTemPendente);

        if (pg_num_rows($resTemPendente) == 0) {
            $sqlFechamento = "UPDATE tbl_os 
                              SET data_fechamento = CURRENT_TIMESTAMP,
                                  finalizada = CURRENT_TIMESTAMP
                              WHERE os = {$os}";

            pg_query($con, $sqlFechamento);        
        }
    }

	if(strlen(pg_last_error()) > 0){
		pg_query($con, "ROLLBACK");
		$resposta = array("resultado" => false, "mensagem", traduz("Erro ao reprovar a os $os."));
	}else{
		pg_query($con, "COMMIT");
		$resposta = array("resultado" => true);
	}

	echo json_encode($resposta); exit;
}

if ($btn_acao == "cancelaOS") {

	$os            = trim($_POST['os']);
	$justificativa = trim($_POST['justificativa']);
	$auditoria_os  = trim($_POST['auditoria_os']);

	pg_query($con, "BEGIN");
	$cond = " AND tbl_auditoria_os.auditoria_os = $auditoria_os";

    $auditoria_bonificacao = false;
    if ($login_fabrica == 160 or $replica_einhell) {
        $sqlBon = "SELECT auditoria_os,
                          tbl_os_extra.extrato,
                          JSON_FIELD('bonificacao_total', valores_adicionais) as bonificacao_total
                    FROM tbl_auditoria_os
                    JOIN tbl_os_extra USING(os)
                    JOIN tbl_os_campo_extra USING(os)
                    WHERE TO_ASCII(observacao, 'LATIN-9') = 'Auditoria de Aprovacao da Bonificacao'
                    AND auditoria_os = {$auditoria_os}";

        $resBon = pg_query($con, $sqlBon);

        if (pg_num_rows($resBon) > 0) {
            $extrato_lancar        = pg_fetch_result($resBon, 0, 'extrato');
            $bonificacao_total     = pg_fetch_result($resBon, 0, 'bonificacao_total');
            $auditoria_bonificacao = true;

            //cancelando lançamento de bonificação
            $sqlLancamento = "UPDATE tbl_extrato_lancamento SET historico = 'Bonificação removida (OS reprovada na Auditoria de nota baixa)' WHERE os = {$os} AND descricao = 'bonificacao' AND debito_credito = 'D'";
            $resLancamento = pg_query($con, $sqlLancamento);

            //debitando do extrato a bonificacao removida
            $sqlValorDebitar = "SELECT valor, extrato as extrato_atual FROM tbl_extrato_lancamento WHERE os = {$os} AND descricao = 'bonificacao' AND debito_credito = 'D'";
            $resValorDebitar = pg_query($con, $sqlValorDebitar);

            $extrato_atual = pg_fetch_result($resValorDebitar, 0, 'extrato_atual');
            $valorDebitar  = pg_fetch_result($resValorDebitar, 0, 'valor');

            $atualizaExtrato = "UPDATE tbl_extrato SET total = total - {$valorDebitar} WHERE extrato = {$extrato_atual}";
            $resAtualizaExtrato = pg_query($con, $atualizaExtrato);

        }
    }

    if(!in_array($login_fabrica, array(148,152,180,181,182)) ){ //hd_chamado=3049906
        if (!$auditoria_bonificacao) {
            $sql = "SELECT fn_os_excluida($os, $login_fabrica, $login_admin)";
            pg_query($con, $sql);
        }
    }else if ($login_fabrica != 148) {
        $sql_up = "UPDATE tbl_os set cancelada = 'true', status_checkpoint = 28 WHERE os = $os AND fabrica = $login_fabrica";
        $res_up = pg_query($con, $sql_up);
		$cond = "";
    }


	if(strlen(pg_last_error()) > 0){
		pg_query($con, "ROLLBACK");
		$resposta = array("resultado" => false, "mensagem" => utf8_encode(traduz("A os $os não pode ser cancelada.")));
	}else{

		$sql = "UPDATE tbl_auditoria_os SET  cancelada = current_timestamp,
			admin         = $login_admin,
			justificativa = substr('$justificativa',1,200)
		WHERE tbl_auditoria_os.os = $os $cond";
		pg_query($con, $sql);

        if (in_array($login_fabrica, [148])) {
            $sql_up = "UPDATE tbl_os set cancelada = 'true', status_checkpoint = 28 WHERE os = $os AND fabrica = $login_fabrica";
            $res_up = pg_query($con, $sql_up);
            $cond = "";
        }

        if(!in_array($login_fabrica, array(148,152,180,181,182) && !$auditoria_bonificacao) ){
    		$sql = "UPDATE tbl_os_excluida set
    			admin         = $login_admin,
    			motivo_exclusao = substr('$justificativa',1,150)
    		WHERE os = $os ";
    		pg_query($con, $sql);
        }

		if(strlen(pg_last_error()) > 0){
			pg_query($con, "ROLLBACK");
			$resposta = array("resultado" => false, "mensagem" => traduz("Os $os cancelada. Mas ocorreu um erro ao alterar os status na auditoria."));
		}else{

			$sql = "SELECT posto FROM tbl_os WHERE os = {$os}";
			$resPosto = pg_query($con,$sql);

			if (in_array($login_fabrica, array(156))) {
				$sql = "SELECT
						oi.os_item,
						oi.qtde,
						p.referencia as peca_referencia,
						p.referencia||' - '||p.descricao as peca_descricao,
						oi.custo_peca
					FROM tbl_os_item oi
					JOIN tbl_peca p USING(peca)
					WHERE oi.fabrica_i = {$login_fabrica}
					AND oi.os_produto IN (SELECT
									os_produto
								FROM tbl_os_produto
								WHERE os = {$os});
					";

				$resPeca = pg_query($con,$sql);
				$count_k = pg_num_rows($resPeca);
			}


			$sql = "SELECT tbl_auditoria_status.descricao FROM tbl_auditoria_os INNER JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status WHERE tbl_auditoria_os.os = {$os} $cond";
			$resAud = pg_query($con, $sql);


			if(pg_num_rows($resPosto) > 0 && !$auditoria_bonificacao){
				$posto = pg_fetch_result($resPosto, 0, "posto");
				$auditoria_desc = pg_fetch_result($resAud, 0, "descricao");
				if ($login_fabrica == 148) {
					$rowAud = pg_fetch_all($resAud);
					foreach ($rowAud as $keyAud => $valueAud) {
						$auditoria_d[] = $valueAud['descricao'];
					}
					$auditoria_desc = implode(', ', $auditoria_d);
				}
				if ($count_k > 0) {
					$tablePecas = "<br /><br />";
					$tablePecas .= "<table border=1>";
					$tablePecas .= "<thead>";
					$tablePecas .= "<tr>";
					$tablePecas .= "<th><b>".traduz("Código")."</b></th>";
					$tablePecas .= "<th><b>".traduz("Descrição")."</b></th>";
					$tablePecas .= "<th><b>".traduz("Qtde")."</b></th>";
					$tablePecas .= "<th><b>".traduz("Valor")."</b></th>";
					$tablePecas .= "</tr>";
					$tablePecas .= "</thead>";
					$tablePecas .= "<tbody>";
						for ($k = 0; $k < $count_k; $k++) {
						$c_os_item                = pg_fetch_result($resPeca, $k, os_item);
						$c_peca_referencia    = pg_fetch_result($resPeca, $k, peca_referencia);
						$c_peca_descricao     = pg_fetch_result($resPeca, $k, peca_descricao);
						$c_qtde                       = pg_fetch_result($resPeca, $k, qtde);
						$c_custo_peca             = pg_fetch_result($resPeca, $k, custo_peca);
						$tablePecas .= "<tr>";
						$tablePecas .= "<td>".$c_peca_referencia."</td>";
						$tablePecas .= "<td>".$c_peca_descricao."</td>";
						$tablePecas .= "<td>".$c_peca_descricao."</td>";
						$tablePecas .= "<td>".$c_qtde."</td>";
						$tablePecas .= "<td>".$c_custo_peca."</td>";
						$tablePecas .= "</tr>";
					}
					$tablePecas .= "</tbody>";
					$tablePecas .= "</table>";

					$tablePecas = utf8_encode($tablePecas);
				}

                if(in_array($login_fabrica, array(148,157))){
                    $justifi = '<br/>Justificativa: '.$justificativa;
                }

                if($login_fabrica == 157){
                    $msg_comunicado = "OS <a href=os_relatorio.php?acao=PESQUISAR&sua_os={$os}&status=15a>{$os}</a> foi cancelada na {$auditoria_desc}{$tablePecas} $justifi";
                }else{
                    $msg_comunicado = "OS <a href=os_press.php?os=$os>{$os}</a> foi cancelada na {$auditoria_desc}{$tablePecas} $justifi";
                }


				$sql = "INSERT INTO tbl_comunicado
						(fabrica,posto, obrigatorio_site, tipo, ativo, descricao, mensagem)
					VALUES (
						{$login_fabrica},{$posto}, true, 'Com. Unico Posto', true,
						'Auditoria',
						'$msg_comunicado')";
				pg_query($con, $sql);


				if(pg_last_error() > 0){
					pg_query($con,"ROLLBACK");
					$resposta = array("resultado" => false, "mensagem" => traduz("Erro ao gerar comunicado para o posto."));
				}else{
					pg_query($con,"COMMIT");
					$resposta = array("resultado" => true);
				}
			}else{
				pg_query($con, "COMMIT");
				$resposta = array("resultado" => true);
			}
		}
	}

	echo json_encode($resposta); exit;
}

if ($btn_acao == "submit") {
    
	if ($login_fabrica == 148) {
		$tipo_de_os           = $_POST["tipo_de_os"];
	}
	$data_inicial         = trim($_POST["data_inicial"]);
	$data_final           = trim($_POST["data_final"]);
	$status_auditoria     = trim($_REQUEST["status_auditoria"]);
	$posto_codigo         = trim($_POST["posto"]["codigo"]);
	$posto_nome           = trim($_POST["posto"]["nome"]);
	$estado               = trim($_POST["estado"]);
	$auditoria_aprovado   = trim($_POST["auditoria_aprovado"]);
	$auditoria_reprovado  = trim($_POST["auditoria_reprovado"]);

    if (in_array($login_fabrica, [152,180,181,182]) && count($_POST['linha_produto']) > 0) {

        $linhas_produto = implode(",", $_POST['linha_produto']);

        $condLinha     = "AND tbl_produto.linha IN ($linhas_produto)";
    }

    if($login_fabrica == 148){
        $auditoria_excepcional  = trim($_POST["auditoria_excepcional"]);
        $familia                = $_POST['familia'];
        $familias               = implode(",", $familia);

        if(strlen($familias)>0){
           $condFamilia = "AND tbl_produto.familia IN($familias)";
        }
    }

    if ($login_fabrica == 160 or $replica_einhell) {
        $aud_peca           = filter_input(INPUT_POST,'aud_peca');
        $pecas_excedentes   = filter_input(INPUT_POST,'pecas_excedentes');
    }

    $os_pesquisa          = trim($_REQUEST["os_pesquisa"]);
	$btn_listar_auditoria = false;

	if(isset($_REQUEST['btn_listar_auditoria']) && !empty($_REQUEST['btn_listar_auditoria'])){
		$btn_listar_auditoria = true;
	}


	if (empty($tipo_de_os) && empty($os_pesquisa) && $btn_listar_auditoria == false && (empty($data_inicial) || empty($data_final))) {
		$msg_erro['msg']["obg"] = traduz("Preencha os campos obrigatórios");
		$msg_erro['campos'][]   = "data_inicial";
		$msg_erro['campos'][]   = "data_final";
	}

	if (empty($status_auditoria) && empty($tipo_de_os)) {
		$msg_erro['msg']["obg"] = traduz("Preencha os campos obrigatórios");
		$msg_erro['campos'][]   = "status_auditoria";
	}

	if (!count($msg_erro["msg"])) {
		$condicao = "";

		if(empty($os_pesquisa) && !$btn_listar_auditoria){
			try {

				if($login_fabrica == 148){
					validaData($data_inicial, $data_final, 6);
				}else{
					validaData($data_inicial, $data_final, 3);
				}

				list($dia, $mes, $ano) = explode("/", $data_inicial);
				$aux_data_inicial      = $ano."-".$mes."-".$dia;

				list($dia, $mes, $ano) = explode("/", $data_final);
				$aux_data_final        = $ano."-".$mes."-".$dia;

                if (!empty($auditoria_aprovado)) {
				    $condicao = " AND tbl_auditoria_os.liberada BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
                } else if (!empty($auditoria_reprovado)) {
                    $condicao = " AND (tbl_auditoria_os.reprovada BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
                        OR
                        tbl_auditoria_os.cancelada BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59') ";
                } else {
                    $condicao = " AND tbl_os.data_abertura BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
                }
			} catch (Exception $e) {
				$msg_erro["msg"][] = $e->getMessage();
				$msg_erro["campos"][] = "data_inicial";
				$msg_erro["campos"][] = "data_final";
			}
		}else if (!empty($os_pesquisa) && !$btn_listar_auditoria) {
			$condicao = " AND (tbl_os.os = {$os_pesquisa} or tbl_os.sua_os='$os_pesquisa')";
		}

		if(count($msg_erro['msg']) == 0){

			if (strlen($posto_codigo) > 0 or strlen($posto_nome) > 0){
				$sql = "SELECT tbl_posto_fabrica.posto FROM tbl_posto
							JOIN tbl_posto_fabrica USING(posto)
						WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
							AND ((UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$posto_codigo}'))
						)";
				$res = pg_query($con ,$sql);

				if (!pg_num_rows($res)) {
					$msg_erro["msg"][]   .= traduz("Posto não encontrado");
					$msg_erro["campos"][] = "posto";
				} else {
					$posto = pg_fetch_result($res, 0, "posto");
				}
			}

			if(strlen($estado) > 0){
				if(!in_array($login_fabrica, array(152,180,181,182)) && !isset($array_estado[$estado])){
					$msg_erro["msg"][]   .= traduz("Estado não encontrado");
					$msg_erro["campos"][] = "estado";
				}
			}

			if(count($msg_erro["msg"]) == 0){
				if($auditoria_aprovado == "1"){
					$condicao .= " AND tbl_auditoria_os.liberada IS NOT NULL";
					if($login_fabrica == 148){
						$condicao .= " AND tbl_os.cancelada IS NOT TRUE ";
					}
				}else if($auditoria_reprovado == "1"){
							$join_reprovada = " left join tbl_os_excluida on tbl_os_excluida.os = tbl_os.os ";
							$cond_reprovada = " or (tbl_os_excluida.fabrica = $login_fabrica) ";
							$condicao .= " AND (tbl_auditoria_os.reprovada IS NOT NULL OR tbl_auditoria_os.cancelada IS NOT NULL)";
				}elseif($auditoria_excepcional == 1){
                    $condicao .= " AND (tbl_auditoria_os.observacao like '%Garantia Excepcional%' OR tbl_auditoria_os.campos_adicionais->>'tipo_auditoria' = 'excepcional')  ";
                }else{
					$condicao .= " AND tbl_auditoria_os.liberada IS NULL AND tbl_auditoria_os.reprovada IS NULL AND tbl_auditoria_os.cancelada IS NULL ";
					if($login_fabrica == 148){
						$condicao .= " AND tbl_os.cancelada IS NOT TRUE ";
					}
				}

				if (strlen($posto_codigo) > 0 or strlen($posto_nome) > 0){
					$condicao .= " AND tbl_posto_fabrica.posto = $posto";
				}

				if ($login_fabrica == 160 or $replica_einhell) {
                    if ($status_auditoria == 4) {
                        if (!empty($aud_peca) && $aud_peca == "aud_peca_excedente") {
                            $condPeca = " AND tbl_auditoria_os.observacao ILIKE '%excedente%'";
                            if (!empty($pecas_excedentes) && $pecas_excedentes == "excede_3") {
                                $condQtde = " AND (SELECT COUNT(tbl_os_item.os_item) FROM tbl_os_item JOIN tbl_os_produto USING(os_produto) WHERE tbl_os_produto.os = tbl_os.os) < 5";
                            } else if (!empty($pecas_excedentes) && $pecas_excedentes == "excede_5") {
                                $condQtde = " AND (SELECT COUNT(tbl_os_item.os_item) FROM tbl_os_item JOIN tbl_os_produto USING(os_produto) WHERE tbl_os_produto.os = tbl_os.os) >= 5";
                            }
                        } else if (!empty($aud_peca) && $aud_peca == "aud_peca_critica") {
                            $condPeca = " AND tbl_auditoria_os.observacao ILIKE '%Pe%a Cr%tica'";
                        }
                    }

                    $condBonifica = " AND TO_ASCII(tbl_auditoria_os.observacao, 'LATIN-9') != 'Auditoria de Aprovacao da Bonificacao' ";

				}

                if (!in_array($login_fabrica, [24])) {
                    $xexcluida = "AND tbl_os.excluida is not true";
                }

				if(strlen($estado) > 0){
					if(in_array($login_fabrica, array(152,180,181,182))){
						$estado = str_replace(",", "','",$estado);
					}
					$condicao .= " AND tbl_posto.estado IN ('$estado')";
				}

				if (!empty($tipo_de_os) && in_array($login_fabrica, array(148))){
					$tipo_de_os = implode(",", $tipo_de_os);
					$condicao .= " AND tbl_os.tipo_atendimento IN ($tipo_de_os)";
				}

				if (empty($status_auditoria) && !empty($tipo_de_os) && in_array($login_fabrica, array(148))){
					$condicao .= "";
				} else {
                    if ($status_auditoria == 'bonificacao') {
                        $condicao .= " AND tbl_auditoria_status.auditoria_status = 6 AND TO_ASCII(tbl_auditoria_os.observacao, 'LATIN-9') = 'Auditoria de Aprovacao da Bonificacao'";
                    } else {
					   $condicao .= " AND tbl_auditoria_status.auditoria_status = $status_auditoria {$condBonifica}";
                    }
				}

				if($novaTelaOs) {
					$join_os_produto = " JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto "; 
				}else{
                    $join_os_produto = " INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica ";
				}

                if (in_array($login_fabrica, [24])) {
                    $os_bloqueada            = filter_input(INPUT_POST, 'os_bloqueada');
                    $join_tbl_os_campo_extra = " LEFT JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os";
                    $propriedade             = 'tbl_os_campo_extra.os_bloqueada,';

                    if (empty($os_bloqueada)) {
                        $condicao .= " AND (tbl_os_campo_extra.os_bloqueada IS FALSE OR tbl_os_campo_extra.os_bloqueada IS NULL) ";
                    } else {
                        $condicao .= " AND tbl_os_campo_extra.os_bloqueada IS TRUE AND tbl_os.admin_excluida IS NOT NULL ";
                    }
                }

				$sql = "SELECT DISTINCT tbl_os.os,
						tbl_os.sua_os,
						tbl_os.data_abertura,
						tbl_os.serie,
						tbl_os_extra.os_reincidente,
						tbl_posto.nome,
						tbl_posto_fabrica.codigo_posto,
						tbl_os.qtde_km,
						tbl_produto.produto,
						tbl_os.tipo_os,
                        tbl_os.data_fechamento,
                        tbl_os.finalizada,
                        {$propriedade}
						tbl_auditoria_os.auditoria_os,
						tbl_auditoria_os.auditoria_status,
						tbl_auditoria_os.paga_mao_obra,
						tbl_auditoria_os.liberada,
						tbl_auditoria_os.reprovada,
						tbl_auditoria_os.observacao,
                        tbl_auditoria_os.cancelada,
						tbl_auditoria_status.fabricante,
						tbl_auditoria_status.descricao AS descricao_auditoria,
						tbl_tipo_atendimento.km_google,
						tbl_tipo_atendimento.descricao as tipo_atendimento_descricao,
                        tbl_linha.nome as nome_linha
					FROM tbl_os
					JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os and tbl_os_extra.extrato isnull
					LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
					INNER JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
					INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
					INNER JOIN tbl_auditoria_os ON tbl_auditoria_os.os = tbl_os.os
					INNER JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
                    {$join_tbl_os_campo_extra}
					$join_os_produto
                    LEFT  JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha AND tbl_linha.fabrica = {$login_fabrica}
					$join_reprovada
					WHERE (tbl_os.fabrica in({$login_fabrica}) $cond_reprovada)
					{$xexcluida}
					{$condicao}
					$condPeca
					$condQtde
                    {$condFamilia}
                    {$condLinha}
					ORDER BY tbl_os.os";

                    // exit( nl2br($sql) );

                $resConsulta = pg_query($con,$sql);
				// É atribuído novamente o valor original do POST para a variável utilizar no elemento select
				$estado = trim($_POST["estado"]);
				$tipo_de_os = $_POST["tipo_de_os"];
			}
		}
	}
}



if ($_POST['gerar_excel'] == true) {

    $data = date("d-m-Y-H:i");
    $fileName = "relatorio_auditorias-{$data}.csv";
    $file = fopen("/tmp/{$fileName}", "w");

    $tabela_th = traduz("OS").";" . traduz("Data").";";

    if ($auditoria_aprovado == "1") {
        $tabela_th .= "Aprovado em".";";
    }

    $tabela_th .= traduz("Posto").";".traduz("Produto").";".traduz("Código").traduz("Peça").";".traduz("Descrição Peça").";".traduz("Peça Critica").";".traduz("Qtde Peça").";".traduz("Serviço Realizado").";".traduz("Auditoria").";".traduz("Observação").";";
    if ($login_fabrica != 158) {
        $tabela_th .= traduz("Qtde KM").";";
    }
    $tabela_th .= traduz("Reincidente").";".traduz("Núm. de Série").";";

    if (isset($_POST["auditoria_aprovado"])) {
        $tabela_th .= traduz("Data de Aprovação").";";
    } else if (isset($_POST["auditoria_reprovado"])) {
        $tabela_th .= traduz("Data de Reprovação").";";
    }

    if (in_array($login_fabrica, array(152,180,181,182) and $status_auditoria == 6 )) {
        $tabela_th .= traduz("Defeitos e Horas").";";
    }
    $tabela_th .= traduz("Ações").";";
    $tabela_th .= "\n";

    fwrite($file, $tabela_th);

    $count_i = pg_num_rows($resConsulta);
    for ($i = 0 ; $i < $count_i; $i++) {
        $auditoria_os               = pg_fetch_result($resConsulta,$i,'auditoria_os');
        $auditoria_status           = pg_fetch_result($resConsulta,$i,'auditoria_status');
        $os                         = pg_fetch_result($resConsulta,$i,'os');
        $sua_os                     = pg_fetch_result($resConsulta,$i,'sua_os');
        $data_abertura              = pg_fetch_result($resConsulta,$i,'data_abertura');
        $posto                      = pg_fetch_result($resConsulta,$i,'nome');
        $produto                    = pg_fetch_result($resConsulta,$i,'produto');
        $codigo_posto               = pg_fetch_result($resConsulta,$i,'codigo_posto');
        $paga_mao_obra              = pg_fetch_result($resConsulta,$i,'paga_mao_obra');
        $aprovada                   = pg_fetch_result($resConsulta,$i,'liberada');
        $reprovada                  = pg_fetch_result($resConsulta,$i,'reprovada');
        $cancelada                  = pg_fetch_result($resConsulta,$i,'cancelada');
        $auditoria                  = pg_fetch_result($resConsulta,$i,'descricao_auditoria');
        $observacao_auditoria       = pg_fetch_result($resConsulta,$i,'observacao');
        $tipo_atendimento_km_google = pg_fetch_result($resConsulta,$i,'km_google');
        $tipo_os                    = pg_fetch_result($resConsulta,$i,'tipo_os');
        $tipo_atendimento_descricao = pg_fetch_result($resConsulta,$i,'tipo_atendimento_descricao');

        $data_format   = explode("-", $data_abertura);
        $data_abertura = $data_format[2]."/".$data_format[1]."/".$data_format[0];
		$sua_os = empty($sua_os) ? $os : $sua_os;
        if (($auditoria_km == $status_auditoria || $auditoria_fabrica == $status_auditoria) && empty($aprovada) && empty($reprovada)) {
            $qtde_km = 100 * pg_fetch_result($resConsulta,$i,'qtde_km');
        } else {
            $qtde_km = pg_fetch_result($resConsulta, $i, "qtde_km");
        }

        if ($auditoria_reincidente == $status_auditoria) {
            $reincidente = pg_fetch_result($resConsulta,$i,'os_reincidente');
        }

        $numero_serie = pg_fetch_result($resConsulta,$i,'serie');

        $produto_referencia = "";
        $produto_descricao  = "";


        $sql = "SELECT tbl_produto.referencia,
                tbl_produto.descricao AS descricao_produto
            FROM tbl_produto
			where produto = $produto ";
        $resProduto = pg_query($con,$sql);

        $produto_referencia   = pg_fetch_result($resProduto,0,'referencia');
        $produto_descricao    = pg_fetch_result($resProduto,0,'descricao_produto');
        $produto = $produto_referencia." - ".$produto_descricao;



            $sqlPeca = "SELECT tbl_peca.referencia ,
                            tbl_peca.descricao,
                            tbl_peca.peca_critica,
                            tbl_os_item.qtde,
                            tbl_servico_realizado.descricao AS servico_realizado
                    FROM tbl_peca
                        JOIN tbl_os_item USING (peca)
                        JOIN tbl_os_produto USING (os_produto)
                        LEFT JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
                    WHERE tbl_os_produto.os = $os";
            $resPeca = pg_query($con,$sqlPeca);

            if (pg_num_rows($resPeca) > 0) {
                $count_k = pg_num_rows($resPeca);
                    $codigo_peca       = "";
                    $descricao_peca    = "";
                    $peca_critica      = "";
                    $qtde              = "";
                    $servico_realizado = "";

                for ($k=0; $k<$count_k; $k++) {
                    $codigo_peca       = pg_fetch_result($resPeca,$k,'referencia');
                    $descricao_peca    = pg_fetch_result($resPeca,$k,'descricao');
                    $peca_critica      = pg_fetch_result($resPeca,$k,'peca_critica');
                    $qtde              = pg_fetch_result($resPeca,$k,'qtde');
                    $servico_realizado = pg_fetch_result($resPeca,$k,'servico_realizado');

                    if($peca_critica == 't'){
                        $peca_critica = "Sim";
                    }else{
                        $peca_critica = "Não";
                    }

                    $tabela_tr .= "$sua_os;";
                    if ($login_fabrica == 148 and $tipo_os == 17) {
                        $tabela_tr .= traduz("OS Fora de Garantia").";";
                    }
                    $tabela_tr .= "$data_abertura;";

                    if ($auditoria_aprovado == "1") {
                        $dias_aprovado = strtotime(pg_fetch_result($resConsulta,$i,'data_abertura')) - strtotime($aprovada);
                        $dias_aprovado = ((int) floor($dias_aprovado / (60 * 60 * 24))*(-1));
                        $dias_aprovado .= " dias";
                        $tabela_tr .= "$dias_aprovado;";
                    }
                    $tabela_tr .= "{$posto};{$produto};{$codigo_peca};{$descricao_peca};{$peca_critica};{$qtde};{$servico_realizado};{$auditoria};{$observacao_auditoria};";

                    if ($tipo_atendimento_km_google == "t" && $auditoria_km == $auditoria_status) {
                        if (!empty($aprovada) && !empty($reprovada) && !empty($cancelada)) {
                            $tabela_tr .= "$qtde_km;";
                        }
                    } else {
                        $tabela_tr .= ";";
                    }

                    if ($auditoria_reincidente != "") {
                        $tabela_tr .= "$reincidente;";
                    } else {
                        $tabela_tr .= ";";
                    }

                    $tabela_tr .= "$numero_serie;";


                        if (isset($_POST["auditoria_aprovado"])) {
                            $tabela_tr .= is_date(substr($aprovada, 0, 19), 'ISO', 'EUR').";";
                        } else if (isset($_POST["auditoria_reprovado"])) {
                            if (!empty($cancelada)) {
                                $tabela_tr .= is_date(substr($cancelada, 0, 19), 'ISO', 'EUR').";";
                            } else if (!empty($reprovada)) {
                                $tabela_tr .= is_date(substr($reprovada, 0, 19), 'ISO', 'EUR').";";
                            }
                        }

                    if (in_array($login_fabrica, array(152,180,181,182) and $status_auditoria == 6 )){
                        $tabela_tr .= "{$defeito[$i]};";
                    }

                    if ($aprovada != "") {
                        $tabela_tr .= "Aprovado";
                    } elseif($reprovada != "") {
                        $tabela_tr .= "Reprovada";
                    } elseif($cancelada != "") {
                        $tabela_tr .= "Cancelada";
                    } else {
                        $tabela_tr .= "";
                    }

                    $tabela_tr .= "\n";
                }
            } else {
                $tabela_tr .= "$sua_os;";
                if ($login_fabrica == 148 and $tipo_os == 17) {
                    $tabela_tr .= traduz("OS Fora de Garantia").";";
                }
                $tabela_tr .= "$data_abertura;";

                if ($auditoria_aprovado == "1") {
                    $dias_aprovado = strtotime(pg_fetch_result($resConsulta,$i,'data_abertura')) - strtotime($aprovada);
                    $dias_aprovado = ((int) floor($dias_aprovado / (60 * 60 * 24))*(-1));
                    $dias_aprovado .= " dias";
                    $tabela_tr .= "$dias_aprovado;";
                }
                $tabela_tr .= "{$posto};{$produto};;;;;;{$auditoria};{$observacao_auditoria};";

                if ($tipo_atendimento_km_google == "t" && $auditoria_km == $auditoria_status) {
                    if (!empty($aprovada) && !empty($reprovada) && !empty($cancelada)) {
                        $tabela_tr .= "$qtde_km;";
                    }
                } else {
                    $tabela_tr .= ";";
                }

                if ($auditoria_reincidente != "") {
                    $tabela_tr .= "$reincidente;";
                } else {
                    $tabela_tr .= ";";
                }

                $tabela_tr .= "$numero_serie;";

                if (in_array($login_fabrica, array(152,180,181,182) and $status_auditoria == 6 )) {
                    $tabela_tr .= "{$defeito[$i]};";
                }

                if ($aprovada != "") {
                    $tabela_tr .= "Aprovado";
                } elseif($reprovada != "") {
                    $tabela_tr .= "Reprovada";
                } elseif($cancelada != "") {
                    $tabela_tr .= "Cancelada";
                } else {
                    $tabela_tr .= "";
                }

                $tabela_tr .= "\n";
            }
    }


    fwrite($file, $tabela_tr);
    fclose($file);

    if (file_exists("/tmp/{$fileName}")) {
        system("mv /tmp/{$fileName} xls/{$fileName}");
        echo "xls/{$fileName}";
    }

    exit;
}

// Bloquear OS
$acaoBloquearOs = filter_input(INPUT_POST, 'bloquear_os');
if( !empty($acaoBloquearOs) ){
    $osParaBloquear = filter_input(INPUT_POST, 'os');

    try{
        pg_query($con, 'BEGIN TRANSACTION');

        if( empty($osParaBloquear) ){
            throw new Exception("Error");
        }

        // Verifica se existe um registro para essa OS na tabela tbl_os_campo_extra
        $sql = "SELECT * FROM tbl_os_campo_extra WHERE os = {$osParaBloquear} AND fabrica = 24";
        $pgResource = pg_query($con, $sql);
        // Caso ja exista um registro então é para atualizar o campo de os_bloqueada
        if( pg_num_rows($pgResource) > 0 ){
            $sql = "UPDATE tbl_os_campo_extra SET os_bloqueada = 't' WHERE os = {$osParaBloquear} AND fabrica = 24";
            $pgResource = pg_query($con, $sql);

            // Verifica se houve algum erro na hora de fazer o update
            $pgError = pg_last_error();
            if( strlen($pgError) > 0 ){
                $pgError = '';
                throw new Exception("Erro");
            }

        }else{
            // Se não existir um registro na tabela então é para inserir
            $sql = "INSERT INTO tbl_os_campo_extra (os, fabrica, os_bloqueada) VALUES ({$osParaBloquear}, {$login_fabrica}, 't')";
            $pgResource = pg_query($con, $sql);

            // Verifica se houve algum tipo de erro
            $pgError = pg_last_error();
            if( strlen($pgError) > 0 ){
                $pgError = '';
                throw new Exception("Erro");
            }
        }

        // Seta o campo de admin_excluida para o id do admin que fez a alteração
        $pgResource = pg_query($con, "UPDATE tbl_os SET admin_excluida = ${login_admin} WHERE os = {$osParaBloquear}");

        // Verifica se houve algum tipo de erro
        $pgError = pg_last_error();
        if( strlen($pgError) > 0 ){
            $pgError = '';
            throw new Exception("Error");
        }

        pg_query($con, 'COMMIT TRANSACTION');

        // Por fim retorna um json de sucesso
        echo json_encode([
            'error' => false,
            'message' => 'OS bloqueada com sucesso'
        ]);
        exit();

    }catch(Exception $e){
        pg_query($con, 'ROLLBACK TRANSACTION');

        echo json_encode([
            'error' => true,
            'message' => 'Erro ao bloquear OS'
        ]);
        exit();
    }
}

// Desbloquear OS
$acaoDesbloquearOs = filter_input(INPUT_POST, 'desbloquear_os');
if( !empty($acaoDesbloquearOs) ){
    $osParaDesbloquear = filter_input(INPUT_POST, 'os');

    try{
        if( empty($osParaDesbloquear) ){
            throw new Exception("Error");
        }

        // Verifica se existe um registro para essa OS na tabela tbl_os_campo_extra
        $sql = "SELECT * FROM tbl_os_campo_extra WHERE os = {$osParaDesbloquear} AND fabrica = 24";
        $pgResource = pg_query($con, $sql);
        // Caso ja exista um registro então é para atualizar o campo de os_bloqueada
        if( pg_num_rows($pgResource) > 0 ){
            $sql = "UPDATE tbl_os_campo_extra SET os_bloqueada = 'f' WHERE os = {$osParaDesbloquear} AND fabrica = 24";
            $pgResource = pg_query($con, $sql);

            // Verifica se houve algum erro na hora de fazer o update
            $pgError = pg_last_error();
            if( strlen($pgError) > 0 ){
                $pgError = '';
                throw new Exception("Erro");
            }
        }

        // Por fim retorna um json de sucesso
        echo json_encode([
            'error' => false,
            'message' => 'OS desbloqueada com sucesso'
        ]);
        exit();

    }catch(Exception $e){
        echo json_encode([
            'error' => true,
            'message' => 'Erro ao desbloquear OS'
        ]);
        exit();
    }
}

$layout_menu = "auditoria";
$title = traduz("AUDITORIA DE ORDEM DE SERVIÇO");

include "cabecalho_new.php";

$plugins = array(
   "datepicker",
   "shadowbox",
   "maskedinput",
   "alphanumeric",
   "price_format",
   "select2",
   "multiselect"
);

include __DIR__."/plugin_loader.php";

?>
<style>

#sb-player.html{
    overflow-y: auto;
}

#mensagem_justificativa{
	margin-left: 80px;
}

.admin {
	background-color: #FF00FF;
}

.posto {
	background-color: #FFFF00;
}

a {
	cursor: pointer;
}
.ui-multiselect{
	line-height: 15px;
}

#aud_peca, label[for=aud_peca] {
    display:none;
}

#pecas_excedentes, label[for=pecas_excedentes] {
    display:none;
}

#sb-body {
    background: white url() !important;
}

</style>
<script type="text/javascript">
$(function() {
	function enviaOrcamentoPosto(){
		var os = $(this).parents('tr').find('input[id^=os]');

		$.ajax({
			url: 'relatorio_auditoria_status.php',
			type: 'post',
			data: {
				'btn_acao': 'envia_orcamento_posto',
				'os' : $(os).val()
			}
        }).done(function(data){
            var obj = JSON.parse(data);
            if(obj.hasOwnProperty('msg')){
                alert(obj.msg);
            }
        });
	}

	$(document).on('click', '#envia_orcamento_posto', enviaOrcamentoPosto);

	$("#data_inicial").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");
	$("#data_final").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");

	$("#estado").select2();
	$("#status_auditoria").select2();
    $("#tipo_de_os, #familia, #linha_produto").multiselect({
       selectedText: "selecionados # de #",
    });
	/**
	 * Inicia o shadowbox, obrigatório para a lupa funcionar
	 */
	Shadowbox.init();

    $('.lista-pecas').on('click', function(){
        var os = $(this).data('id');
        
        Shadowbox.open({
            content: "relatorio_auditoria_status_lista_pecas.php?os=" + os,
            player: "iframe",
            width: 500,
            height: 500
        });

    });

    $('.bloquear_os').on('click', function(){
        var os = $(this).data('id');

        var data = { os: os, bloquear_os: true };
        $.post(window.location, data, function(response){
            if( response.error == false ){
                alert(response.message);
                $('.btn-desbloquear-' + os).css('display', 'inline-block');
                $('.btn-bloquear-' + os).hide();
            }else if( response.error == true ){
                alert(response.message);
            }
        }, 'json');

    });

    $('.desbloquear_os').on('click', function(){
        var os = $(this).data('id');

        var data = { os: os, desbloquear_os: true };
        $.post(window.location, data, function(response){
            if( response.error == false ){
                alert(response.message);
                $('.btn-bloquear-' + os).css('display', 'inline-block');
                $('.btn-desbloquear-' + os).hide();
            }else if( response.error == true ){
                alert(response.message);
            }
            console.log(response);
        }, 'json');

    });

	$("span[rel=lupa]").click(function() {
		$.lupa($(this));
	});
	<?php if (in_array($login_fabrica, array(148)) == false) { ?>
	$("#resultado_posto > tbody > tr > td[id^=peca_]").click(function(){
		if ( $("#"+this.id).html().length > 20 ){
			Shadowbox.open({content: $("#div_"+this.id).html(), player: "html", width: 800, heigth: 600 });
		}
	});
	<?php } ?>

	$("#resultado_posto > tbody > tr > td > a[id^=produto_]").click(function(){
		if ( $("#"+this.id).html().length > 20 ){
			Shadowbox.open({content: $("#div_"+this.id).html(), player: "html", width: 800, heigth: 600 });
		}
	});

	$("#resultado_posto > tbody > tr > td[id^=defeito_]").click(function(){
		if ( $("#"+this.id).html().length > 20 ){
			Shadowbox.open({content: $("#div_"+this.id).html(), player: "html", width: 800, heigth: 600 });
		}
	});

	$("button[id^=btReprovado_]").click(function(){
		var linha = this.id.replace(/\D/g, "");
		var os    = $("#os_"+linha).val();
		var auditoria_os = $("#auditoria_os_"+linha).val();

		$("input.numero_linha").val(linha);
		$("input.numero_os").val(os);
		$("input.acao_justificativa").val("reprovaOS");
		$("input.numero_auditoria_os").val(auditoria_os);
		// var divClone = $(".div_justificativa").clone();

		Shadowbox.open({
			content: $(".div_justificativa").html(),
			player: "html",
			width: 800,
			heigth: 600,
			options: {
				enableKeys: false
			}
		});
	});

	$("button[id^=btCancelado_]").click(function(){
		var linha        = this.id.replace(/\D/g, "");
		var os           = $("#os_"+linha).val();
		var auditoria_os = $("#auditoria_os_"+linha).val();

		$("input.numero_linha").val(linha);
		$("input.numero_os").val(os);
		$("input.acao_justificativa").val("cancelaOS");
		$("input.numero_auditoria_os").val(auditoria_os);
		// var divClone = $(".div_justificativa").clone();

		Shadowbox.open({
			content: $(".div_justificativa").html(),
			player: "html",
			width: 800,
			heigth: 600,
			options: {
				enableKeys: false
			}
		});
	});

	$("input[id^=qtde_km_]").priceFormat({
		prefix: '',
        thousandsSeparator: '',
        centsSeparator: '.',
        ca: 2
	});


	$("button[id^=btInteragir_]").click(function(){
		var linha = this.id.replace(/\D/g, "");

		var os = $("#os_"+linha).val();
		Shadowbox.open({
			content: "interacao_os.php?os="+os,
			player: "iframe",
			width: 700,
			options: {
				enableKeys: false
			}
		});
	});

	<? if ($auditoria_aprovado == "1" || $auditoria_reprovado == "1") { ?>
		$("input.btn_listar_auditoria").hide();
	<? } else { ?>
		$("input.btn_listar_auditoria").show();
	<? } ?>

	$("input.auditoria_aprovado").on("click",function(){
		if($(this).is(":checked")){
			$("input.btn_listar_auditoria").hide();
			$("input.auditoria_reprovado").attr("checked", false);
            <?php if($login_fabrica == 148){?>
                $("input.auditoria_excepcional").attr("checked", false);
            <?php }?>
		}else{
			$("input.btn_listar_auditoria").show();
		}
	});

	$("input.auditoria_reprovado").on("click",function(){
		if($(this).is(":checked")){
			$("input.btn_listar_auditoria").hide();
			$("input.auditoria_aprovado").attr("checked", false);
            <?php if($login_fabrica == 148){?>
                $("input.auditoria_excepcional").attr("checked", false);
            <?php }?>
		}else{
			$("input.btn_listar_auditoria").show();
		}
	});
<?php if($login_fabrica == 148){?>
    $("input.auditoria_excepcional").on("click",function(){
        if($(this).is(":checked")){
            $("input.btn_listar_auditoria").hide();
            $("input.auditoria_aprovado").attr("checked", false);
            $("input.auditoria_reprovado").attr("checked", false);
        }else{
            $("input.btn_listar_auditoria").show();
        }
    });
<?php } ?>

	<? if (in_array($login_fabrica, array(156, 148))) { ?>
		$("button[id^=btEditarVal_]").click(function () {
			var linha = this.id.replace(/\D/g, "");
			var os = $("#os_"+linha).val();
			Shadowbox.open({
				content: "editar_valor_pecas.php?os="+os,
				player: "iframe",
				width: 800,
				options: {
					enableKeys: false
				}
			});
		});
<?php
    }
	if (in_array($login_fabrica, array(148))) { ?>

		$("a[id^=peca_]").click(function () {
			var linha 			= this.id.replace(/\D/g, "");
			var os 				= $(this).data('id');
			var posto 			= $(this).data('posto');
			var auditoria_os 	= $(this).data('auditoriaos');
			Shadowbox.open({
				content: "atualizar_peca_os.php?os="+os+"&auditoria_os="+auditoria_os+"&posto="+posto+"&linha="+linha,
				player: "iframe",
				width: 800,
				height: 600,
				options: {
					enableKeys: false
				}

			});
		});
<?php
    }
    if ($login_fabrica == 160 or $replica_einhell) {
?>
    $("#status_auditoria").change(function(){
        var tipo_auditoria = $(this).val();

        if (tipo_auditoria == 4) {
            $("#aud_peca").css("display","block");
            $("label[for=aud_peca]").css("display","block");
        } else {
            $("#aud_peca").css("display","none");
            $("label[for=aud_peca]").css("display","none");

            $("#aud_peca").val("");
        }
    });

    $("#aud_peca").change(function(){
        var tipo_aud_peca = $(this).val();

        if (tipo_aud_peca == "aud_peca_excedente") {
            $("#pecas_excedentes").css("display","block");
            $("label[for=pecas_excedentes]").css("display","block");
        } else {
            $("#pecas_excedentes").css("display","none");
            $("label[for=pecas_excedentes]").css("display","none");

            $("#pecas_excedentes").val("");
        }
    });
<?php
        if (!empty($aud_peca)) {
?>
            $("#aud_peca").css("display","block");
            $("label[for=aud_peca]").css("display","block");
<?php
        }

        if (!empty($pecas_excedentes)) {
?>
            $("#pecas_excedentes").css("display","block");
            $("label[for=pecas_excedentes]").css("display","block");
<?php
        }
    }
?>

   var map = {"â":"a","Â":"A","à":"a","À":"A","á":"a","Á":"A","ã":"a","Ã":"A","ê":"e","Ê":"E","è":"e","È":"E","é":"e","É":"E","î":"i","Î":"I","ì":"i","Ì":"I","í":"i","Í":"I","õ":"o","Õ":"O","ô":"o","Ô":"O","ò":"o","Ò":"O","ó":"o","Ó":"O","ü":"u","Ü":"U","û":"u","Û":"U","ú":"u","Ú":"U","ù":"u","Ù":"U","ç":"c","Ç":"C","ñ":"n"};

    function removerAcentos(string) { 
        return string.replace(/[\W\[\] ]/g,function(a) {
            return map[a]||a}) 
    };

   /** select de provincias/estados */
    $(function() {

        $("#estado option").remove();
        
        $("#estado optgroup").remove();

        $("#estado").append("<option value=''>TODOS OS ESTADOS</option>");

        var post = "<?php echo $_POST['estado']; ?>";

        <?php if (in_array($login_fabrica,[181])) { ?> 

            $("#estado").append('<optgroup label="Provincias">');
                
            var select = "";
            
            <?php foreach ($provincias_CO as $provincia) { ?>

                var provincia = '<?= $provincia ?>';

                var semAcento = removerAcentos(provincia);

                if (post == semAcento) {

                    select = "selected";
                }

                var option = "<option value='" + semAcento + "' " + select +">" + provincia + "</option>";

                $("#estado").append(option);

                select = "";

            <?php } ?>

                $("#estado").append('</optgroup>');

        <?php } ?>

        <?php if (in_array($login_fabrica,[182])) { ?>
            
            
            $("#estado").append('<optgroup label="Provincias">');
            
            var select = "";
                
            <?php foreach ($provincias_PE as $provincia) { ?>

                var provincia = '<?= $provincia ?>';

                var semAcento = removerAcentos(provincia);

                if (post == semAcento) {
                    
                    select = "selected";
                }

                var option = "<option value='" + semAcento + "' " + select +">" + provincia + "</option>";

                select = "";

            <?php } ?>

            $("#estado").append(option);
        
        <?php } ?>

        <?php if (in_array($login_fabrica,[180])) {  ?>

            $("#estado").append('<optgroup label="Provincias">');

            var select = "";
                
            <?php foreach ($provincias_AR as $provincia) { ?>

                var provincia = '<?= $provincia ?>';

                var semAcento = removerAcentos(provincia);

                if (post == semAcento) {

                    select = "selected";
                } 

                var option = "<option value='" + semAcento + "' " + select +">" + provincia + "</option>";

                $("#estado").append(option);

                select = "";

            <?php } ?>

            $("#estado").append('</optgroup>');

        <?php } ?> 
        <?php if (in_array($login_fabrica, [152])) { ?>
            
            var array_regioes = [

                    "BA,SE,AL,PE,PB,RN,CE,PI,MA,SP",
                    "MG,DF,GO,MT,RO,AC,AM,RR,PA,AP,TO",
                    "MS,PR,SC,RS,RJ,ES"
                ];

            $("#estado").append('<optgroup label="Regiões">');
         
            var select = "";
            
            $.each(array_regioes, function( index, value ) {
             
                if (post == value) {
                    select = "selected";
                }

                var option = "<option value=" + value + " "+ select + ">" + value + "</option>";

                $("#estado").append(option);

                select = "";
            });
             
            $("#estado").append('</optgroup>');

          <?php } ?>

        <?php if (!in_array($login_fabrica, [180,181,182])) { ?>    

            $("#estado").append('<optgroup label="Estados">');
            
            <?php foreach ($array_estados() as $sigla => $estado) { ?>

                var estado = '<?= $estado ?>';
                var sigla = '<?= $sigla ?>';
                select = '';

                if (post == sigla) {

                    select = "selected";
                }

                var option = "<option value='" + sigla + "'" + select +">" + estado + "</option>";

                $("#estado").append(option);

            <?php } ?>

            $("#estado").append('</optgroup>');

        <?php } ?>       
        
    });

});

/**
 * Função de retorno da lupa do posto
 */
function retorna_posto(retorno) {
	/**
	 * A função define os campos código e nome como readonly e esconde o botão
	 * O posto somente pode ser alterado quando clicar no botão trocar_posto
	 * O evento do botão trocar_posto remove o readonly dos campos e dá um show nas lupas
	 */
	$("#posto_id").val(retorno.posto);
	$("#posto_codigo").val(retorno.codigo).attr({ readonly: "readonly" });
	$("#posto_nome").val(retorno.nome).attr({ readonly: "readonly" });
	$("#div_trocar_posto").show();
	$("#div_informacoes_posto").find("span[rel=lupa]").hide();
}

function salvarKM(linha, os) {
	$("button[id^=btKM_"+linha+"]").button('loading');

	var novo_km = document.querySelector("#qtde_km_"+linha);

	$.ajax({
        url: "<?= $_SERVER['PHP_SELF']; ?>",
        type: "POST",
        data: {
	        os: os,
	        km: novo_km.value,
	        btn_acao: "inserirKM"
	    },
    }).done(function(data) {
    	data = JSON.parse(data);

    	if(data.resultado == false){
    		alert(data.mensagem);
    	}else{
    		$("#mensagem_km_"+linha).show();

    		setTimeout(function() {
    			$("#mensagem_km_"+linha).hide();
    		}, 3000);
    	}

    	$("button[id^=btKM_"+linha+"]").button('reset');
	}).fail(function() {
    	data = JSON.parse(data);
    	if(data.resultado == false){
    		$("#mensagem").html('<div class="alert alert-error"><h4>'+data.mensagem+'</h4> </div>').show();
            setTimeout(function() {
                $('#mensagem').hide()
            }, 3000);
    	}

    	$("button[id^=btKM_"+linha+"]").button('reset');
	});
}

function justificarAprovarOSExcep(linha, os, auditoria_os, mao_obra,tipo_atendimento){
        var excepcional = true;
        Shadowbox.open({
            content: '<div class="div_justificativa_aprovaOS" style="margin: 5px; padding-right: 20px;">\
                        <div id="mensagem_justificativa_aprovaOS">\
                        <br/>\
                        <label>Justificativa</label>\
                        <textarea id="justificativa_aprovaOS" class="justificativa_aprovaOS" name="justificativa_aprovaOS" rows="10" cols="10" style="margin: 0px 0px 10px; width: 603px; height: 200px;"></textarea>\
                        <br/>\
                        <button type="button" style="position:right" onclick=\'aprovarOS("'+linha+'", "'+os+'", "'+auditoria_os+'", "'+mao_obra+'","true", "excep")\' class="btn btn-primary btn-sucess" data-loading-text="Salvando..." id="btJustificativaAprovaOS">Salvar</button>\
                        </div>\
                        </div>',
            player: "html",
            width: 800,
            heigth: 600,
            options: {
                enableKeys: false
            }
        });

}

function justificarAprovarOS(linha, os, auditoria_os, mao_obra,tipo_atendimento){
		Shadowbox.open({
			content: '<div class="div_justificativa_aprovaOS" style="margin: 5px; padding-right: 20px;">\
						<div id="mensagem_justificativa_aprovaOS">\
						<br/>\
						<label>Justificativa</label>\
						<textarea id="justificativa_aprovaOS" class="justificativa_aprovaOS" name="justificativa_aprovaOS" rows="10" cols="10" style="margin: 0px 0px 10px; width: 603px; height: 200px;"></textarea>\
						<br/>\
						<button type="button" style="position:right" onclick=\'aprovarOS("'+linha+'", "'+os+'", "'+auditoria_os+'", "'+mao_obra+'","true")\' class="btn btn-primary btn-sucess" data-loading-text="Salvando..." id="btJustificativaAprovaOS">Salvar</button>\
						</div>\
						</div>',
			player: "html",
			width: 800,
			heigth: 600,
			options: {
				enableKeys: false
			}
		});

}

function aprovarOS(linha, os, auditoria_os, mao_obra,tipo_atendimento, excepcional = null){

    if(excepcional == 'excep'){
        excepcional = 'sim';
    }

	if(confirm('<?=traduz("Deseja realmente aprovar a OS?")?>')){
		if(mao_obra != ""){
			$("button[id=btMaoObrao_"+linha+"]").button('loading');
		}else{
			$("button[id=btAprovado_"+linha+"]").button('loading');
		}

        var fabrica = <?=$login_fabrica;?>;
        var classificacao = "";

        <?php if(in_array($login_fabrica, array(152,180,181,182))) { ?>
            classificacao = $("input[name=classificacao_" + linha + "]:checked").val();
        <?php } ?>

        var justificativa = (tipo_atendimento == 'true') ? $("#justificativa_aprovaOS").val() : '';
        var novo_km = document.querySelector("#qtde_km_"+linha);
		var dataAjax = {
			os: os,
			btn_acao: "aprovaOS",
			mao_obra: mao_obra,
			justificativa : justificativa,
            excepcional : excepcional,
			auditoria_os: auditoria_os,
            classificacao : classificacao
		}

		$.ajax({
			url: "<?= $_SERVER['PHP_SELF']; ?>", //'relatorio_auditoria_status.php',
			type: "POST",
			data: dataAjax,
		}).done( function(data){
			data = JSON.parse(data);
			if(data.resultado == false){
				$("#mensagem").html('<div class="alert alert-error"><h4>'+data.mensagem+'</h4> </div>').show();
                setTimeout(function() {
                    $('#mensagem').hide()
                }, 3000);
			} else {
                atualizaStatus(linha,os);
            }

			if(mao_obra != ""){
				$("button[id=btMaoObrao_"+linha+"]").button('reset');
			}else{
				$("button[id=btAprovado_"+linha+"]").button('reset');
			}
			Shadowbox.close();
		});
	}
}

function reprovarOS(){
	var resposta;
	var bt = $("input.acao_justificativa").val();
    if(bt == "reprovaOS"){
		resposta = confirm("<?=traduz('Deseja realmente Reprovar a OS?')?>");
	}else{
		resposta = confirm("<?=traduz('Deseja realmente Cancelar a OS?')?>");
	}

	if(resposta){
		var linha = $("input.numero_linha").val();

		if(bt == "reprovaOS"){
			$("button[id=btReprovado_"+linha+"]").button('loading');
		}else{
			$("button[id=btCancelado_"+linha+"]").button('loading');
		}

		var dataAjax = {
	        os: $("input.numero_os").val(),
	        justificativa: $.trim($("#sb-container").find("textarea#justificativa").val()),
	        btn_acao: $("input.acao_justificativa").val(),
	        auditoria_os: $("input.numero_auditoria_os").val()
	    };

		$.ajax({
	        url: "<?=$_SERVER['PHP_SELF']?>",//'relatorio_auditoria_status.php',
	        type: "POST",
	        data: dataAjax,
	    }).done( function(data){
            var mensagem;
	    	data = JSON.parse(data);
	    	if(data.resultado == false){
	    		$("#mensagem").html('<div class="alert alert-error"><h4>'+data.mensagem+'</h4> </div>').show();
                setTimeout(function() {
                    $('#mensagem').hide()
                }, 3000);
	    	} else {
                atualizaStatus(linha,$("input.numero_os").val());
            }

			if(bt == "reprovaOS"){
				$("button[id=btReprovado_"+linha+"]").button('reset');
			}else{
				$("button[id=btCancelado_"+linha+"]").button('reset');
			}

			Shadowbox.close();

		}).fail(function(data){
			if(data.resultado == false){
	    		$("#mensagem").html('<div class="alert alert-error"><h4>'+data.mensagem+'</h4> </div>').show();
                setTimeout(function() {
                    $('#mensagem').hide()
                }, 3000);
	    	} else {
                atualizaStatus(linha,$("input.numero_os").val());
            }

			if(bt == "reprovaOS"){
				$("button[id=btReprovado_"+linha+"]").button('reset');
			}else{
				$("button[id=btCancelado_"+linha+"]").button('reset');
			}

			Shadowbox.close();
		});
	}
}

function atualizaStatus(linha, os){

	var dataAjax = {
		os: os,
		auditoria_os: $("#auditoria_os_"+linha).val(),
		btn_acao: "consultaStatus"
	};

	$.ajax({
		url: "<?= $_SERVER['PHP_SELF']; ?>",//'relatorio_auditoria_status.php',
		type: "POST",
		data: dataAjax,
	}).done( function(data){
		data = JSON.parse(data);
        if(data != null){
		  $("#resultado_posto > tbody > tr > td[id=status_"+linha+"]").html('<label class="label label-'+data.tipoStatus+'">'+data.descricao+'</label>');
        }
	}).fail(function(data){
		if(data.resultado == false){
			$("#mensagem").html('<div class="alert alert-error"><h4>'+data.mensagem+'</h4> </div>').show();
            setTimeout(function() {
                $('#mensagem').hide()
            }, 3000);
		}
	});
}

function mostrarAprovar(botaoAprovar) { /*HD - 4292800*/
    $("#" + botaoAprovar).removeAttr("disabled");
    $("#" + botaoAprovar).css("cursor", "pointer");
}

</script>
<? if (count($msg_erro['msg']) > 0) { ?>
	<br/>
	<div class="alert alert-error"><h4><?=implode("<br />", $msg_erro['msg'])?></h4></div>
	<br/>
<? } ?>
<div id="mensagem" style="display:none;"></div>
<div class="div_justificativa" style="display:none; margin: 5px; padding-right: 20px;">
	<div id="mensagem_justificativa">
		<br/>
		<label><?=traduz("Justificativa")?></label>
		<textarea id="justificativa" name="justificativa" rows="10" cols="10" style="margin: 0px 0px 10px; width: 603px; height: 200px;"></textarea>
		<br/>
		<button type="button" style="position:right" class="btn btn-primary btn-sucess" data-loading-text="Salvando..." id="btJustificativa" onclick="reprovarOS();"><?=traduz("Salvar")?></button>
	</div>
</div>



<div id="DivInteragir" style="display: none;" >
	<div class="loading tac" style="display: none;" ><img src="imagens/loading_img.gif" /></div>
	<div class="conteudo" >
		<div class="titulo_tabela" ><?=traduz("Interagir na OS")?></div>
		<div class="row-fluid">
			<div class="span12">
				<div class="controls controls-row">
					<textarea name="text_interacao" class="span12"></textarea>
				</div>
			</div>
		</div>
		<p><br/>
			<button type="button" name="button_interagir" class="btn btn-primary btn-block" rel="__NumeroOs__" ><?=traduz("Interagir")?></button>
		</p>
		<br/>
	</div>
</div>
<div class="row">
        <b class="obrigatorio pull-right">  * <?=traduz("Campos obrigatórios")?></b>
</div>

<form method="POST" action="<?echo $PHP_SELF?>" name="frm_relatorio_auditoria" align='center' class='form-search form-inline tc_formulario'>
		<div class='titulo_tabela '><?=traduz("Parâmetros de Pesquisa")?></div>
		<br/>
		<input type="hidden" class="numero_os" value="" />
		<input type="hidden" class="numero_linha" value="" />
		<input type="hidden" class="numero_auditoria_os" value="" />
		<input type="hidden" class="acao_justificativa" value="" />

		<div class='row-fluid'>
			<div class="span1"></div>
			<div class="span2">
				<div class='control-group'>
					<label class="control-label" for="os_pesquisa"><?=traduz("Número da OS")?></label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="os_pesquisa" name="os_pesquisa" class="span12" type="text" value="<?=$os_pesquisa?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="span2">
				<div class='control-group <?=(in_array('data_inicial', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="data_inicial"><?=traduz("Data Inicial")?></label>
					<div class="controls controls-row">
						<div class="span12"><h5 class='asteristico'>*</h5>
							<input id="data_inicial" name="data_inicial" class="span12" type="text" value="<?=$data_inicial?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="span2">
				<div class='control-group <?=(in_array('data_final', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="data_final"><?=traduz("Data Final")?></label>
					<div class="controls controls-row">
						<div class="span12"><h5 class='asteristico'>*</h5>
							<input id="data_final" name="data_final" class="span12" type="text" value="<?=$data_final?>" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array('status_auditoria', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class='control-label' for='status_auditoria'><?=traduz("Tipo de Auditoria")?></label>
					<div class='controls controls-row'><h5 class='asteristico'>*</h5>
						<select name="status_auditoria" id="status_auditoria" class="span12">
<?php
    						if(in_array($login_fabrica,array(148,194))){
    							echo "<option value=''></option>";
    						}

                                if (in_array($login_fabrica, array(157))) {
                                    $cond_status = " WHERE auditoria_status IN(1,4) ";  
                                } elseif (in_array($login_fabrica, [81,114,122,123,125,147,160,174]) or $replica_einhell) {
                                    $cond_status = " WHERE auditoria_status != 8 ";
                                } else {
                                    $cond_status = "";
                                }

								$sql = "SELECT * FROM tbl_auditoria_status {$cond_status}";
								$res = pg_query($con, $sql);

								if(pg_num_rows($res) > 0){
									while ($auditoria_status = pg_fetch_object($res)) {
										$liberado = false;

										if(($auditorias['reincidente']) && $auditoria_status->reincidente == 't'){
											$auditoria_reincidente = $auditoria_status->auditoria_status;
											$liberado = true;

										}else if(($auditorias['km']) && $auditoria_status->km == 't'){
											$auditoria_km = $auditoria_status->auditoria_status;
											$liberado = true;

										}else if(($auditorias['produto']) && $auditoria_status->produto == 't'){
											$liberado = true;

										}else if(($auditorias['peca']) && $auditoria_status->peca == 't'){
											$liberado = true;

										}else if(($auditorias['numero_serie']) && $auditoria_status->numero_serie == 't'){
											$auditoria_numero_serie = $auditoria_status->auditoria_status;
											$liberado = true;

										}else if(($auditorias['fabricante']) && $auditoria_status->fabricante == 't'){
											$auditoria_fabrica = $auditoria_status->auditoria_status;
											$liberado = true;

										}

										if($liberado){
											$selected = ($auditoria_status->auditoria_status == $status_auditoria) ? "selected" : "";

										?>
											<option value="<?=$auditoria_status->auditoria_status?>" <?=$selected?> ><?=traduz($auditoria_status->descricao)?></option>
									    <?php
										}
									}
								}

                                if ($login_fabrica == 160 or $replica_einhell) { 
                                    $selected = ($status_auditoria == 'bonificacao') ? "selected" : "";
                                    ?>

                                    <option value="bonificacao" <?= $selected ?>><?=traduz("Auditoria de Bonificação (Avaliação baixa no SMS)")?></option>
                            <?php
                                }
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='row-fluid'>
			<div class='span1'></div>
			<input type="hidden" id="posto" name="posto" value="<?=$posto?>" />
			<div class="span2">
				<div class='control-group' >
						<label class="control-label" for="posto_codigo"><?=traduz("Código do Posto")?></label>
						<div class="controls controls-row">
							<div class="span10 input-append">
								<input id="posto_codigo" name="posto[codigo]" class="span12" type="text" value="<?=getValue('posto[codigo]')?>" <?=$posto_readonly?> />
								<span class="add-on" rel="lupa" <?=$posto_esconde_lupa?> >
									<i class="icon-search"></i>
								</span>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
							</div>
						</div>
					</div>
			</div>

			<div class="span4">
				<div class='control-group' >
						<label class="control-label" for="posto_nome"><?=traduz("Nome do Posto")?></label>
						<div class="controls controls-row">
							<div class="span10 input-append">
								<input id="posto_nome" name="posto[nome]" class="span12" type="text" value="<?=getValue('posto[nome]')?>" <?=$posto_readonly?> />
								<span class="add-on" rel="lupa" <?=$posto_esconde_lupa?> >
									<i class="icon-search"></i>
								</span>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
							</div>
						</div>
					</div>
			</div>
<?php
        if (in_array($login_fabrica, [152,180,181,182])) { ?>
            <div class="span3">
                <div class='control-group' >
                    <label class="control-label" for="posto_nome"><?=traduz("Linha")?></label>
                    <div class='controls controls-row'>
                        <select name="linha_produto[]" multiple="" class="span12" id="linha_produto">
                            <?php
                            $sqlLinhas = "SELECT linha, nome
                                          FROM tbl_linha
                                          WHERE ativo AND fabrica = {$login_fabrica}
                                          ";
                            $resLinhas = pg_query($con, $sqlLinhas);

                            while ($dadosLinhas = pg_fetch_assoc($resLinhas)) { 

                                $selectedLinha = (in_array($dadosLinhas['linha'],$_POST['linha_produto'])) ? "selected" : "";

                                ?>
                                <option value="<?= $dadosLinhas['linha'] ?>" <?= $selectedLinha ?>><?= $dadosLinhas['nome'] ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
<?php
        }

        if ($login_fabrica == 160 or $replica_einhell) {
?>
            <div class="span2">
                <div class='control-group' >
                    <label class='control-label' for='aud_peca'><?=traduz("Auditoria de Peças")?></label>
                    <div class='controls controls-row'>
                        <select name="aud_peca" id="aud_peca" />
                            <option value="" <?=(empty($aud_peca)) ? "selected" : ""?>><?=traduz("SELECIONE")?></option>
                            <option value="aud_peca_critica" <?=($aud_peca == "aud_peca_critica") ? "selected" : ""?>><?=traduz("Peça Crítica")?></option>
                            <option value="aud_peca_excedente" <?=($aud_peca == "aud_peca_excedente") ? "selected" : ""?>><?=traduz("Excedente de Peças")?></option>
                        </select>
                    </div>
                </div>
            </div>
<?php
        }
?>
			<div class='span1'></div>
		</div>
		<?php
		if ($login_fabrica == 148) {
			$optionsTipoAtendimento = array();
			$sql = "SELECT tipo_atendimento, descricao
					  FROM tbl_tipo_atendimento
					 WHERE tbl_tipo_atendimento.fabrica = {$login_fabrica}
				  ORDER BY descricao ASC";
			$res  = pg_query($con, $sql);
			$countTipoAtendimento = pg_num_rows($res);
			if (pg_num_rows($res) > 0) {
				for($i = 0; $i < $countTipoAtendimento; $i++){
					$tipo_atendimento = pg_fetch_result($res, $i, "tipo_atendimento");
					$descricao        = pg_fetch_result($res, $i, "descricao");
					$optionsTipoAtendimento[$tipo_atendimento] = $descricao;
				}
			}
		?>
		<div class='row-fluid'>
			<div class='span1'></div>
			<div class="span4">
				<div class='control-group' >
					<label class='control-label' for='tipo_de_os'><?=traduz("Tipo de OS")?></label>
						<div class='controls controls-row'>
						 <select name="tipo_de_os[]" id="tipo_de_os" multiple="multiple" />
							<?php
								foreach ($optionsTipoAtendimento as $valor => $descricao) {
								$selected = (($valor == $tipo_de_os) || in_array($valor, $tipo_de_os)) ? "SELECTED" : '' ;
							?>
	                        <option value="<?php echo $valor;?>" <?php echo $selected;?>> <?php echo $descricao;?></option>
	                        <?php }?>
	                    </select>
					</div>
				</div>
			</div>
            <?php
                $optionsTipoAtendimento = array();
                $sql = "SELECT familia, descricao
                          FROM tbl_familia
                         WHERE tbl_familia.fabrica = {$login_fabrica}
                         AND ativo = 't'
                      ORDER BY descricao ASC";
                $res  = pg_query($con, $sql);
                $countTipoAtendimento = pg_num_rows($res);
                if (pg_num_rows($res) > 0) {
                    for($i = 0; $i < $countTipoAtendimento; $i++){
                        $familia_id = pg_fetch_result($res, $i, "familia");
                        $descricao        = pg_fetch_result($res, $i, "descricao");
                        $optionsFamilia[$familia_id] = $descricao;
                    }
                }
            ?>
            <div class="span4">
                <div class='control-group'>
                    <label class='control-label' for='familia'><?=traduz("Família")?></label>
                        <div class='controls controls-row'>
                         <select name="familia[]" id="familia" multiple="multiple" />
                            <?php
                                foreach ($optionsFamilia as $valor => $descricao) {
                                $selected = ( in_array($valor, $familia)) ? "SELECTED" : '' ;
                            ?>
                            <option value="<?=$valor?>" <?=$selected?>> <?=$descricao?></option>
                            <?php }?>
                        </select>
                    </div>
                </div>
            </div>
		</div>
<?php
		}
?>
    <div class='row-fluid'>
<?php if (!in_array($login_fabrica, [180,181,182])) { ?>
			<div class='span1'></div>
			<div class="span6">
				<div class="control-group">
					<label class="control-label" for="estado" ><?=traduz("Estado/Região")?></label>
					<div class="controls control-row">
						<select id="estado" name="estado" class="span12" >
						</select>
					</div>
				</div>
			</div>
<?php } ?>
<?php
        if ($login_fabrica == 160 or $replica_einhell) {
?>
            <div class="span3">
                <div class="control-group">
                    <label class="control-label" for="pecas_excedentes" ><?=traduz("Peças Excedentes")?></label>
                    <div class='controls controls-row'>
                        <select name="pecas_excedentes" id="pecas_excedentes" />
                            <option value=""><?traduz("SELECIONE")?></option>
                            <option value="excede_3" <?=($pecas_excedentes == "excede_3") ? "selected" : ""?>><?=traduz("Entre 03 e 05")?></option>
                            <option value="excede_5" <?=($pecas_excedentes == "excede_5") ? "selected" : ""?>><?=traduz("+ 05 Peças")?></option>
                        </select>
                    </div>
                </div>
            </div>
<?php
        }
?>
		</div>
		<div class="row-fluid" >
			<div class="span2" ></div>
			<div class='span3'>
				<div class="control-group">
					<div class="controls" >
						<label class="checkbox label label-success" style="padding-left: 5px;">
							<input type='checkbox' class='auditoria_aprovado' name="auditoria_aprovado" value='1' <?php if($_POST["auditoria_aprovado"] == '1'){ echo "CHECKED"; }?> /> <?=traduz("Auditorias Aprovadas")?>
						</label>
					</div>
				</div>
			</div>
			<div class='span3'>
				<div class="control-group">
					<div class="controls" >
						<label class="checkbox label label-important" style="padding-left: 5px;">
							<input type='checkbox' class='auditoria_reprovado' name="auditoria_reprovado" value='1' <?php if($_POST["auditoria_reprovado"] == '1'){ echo "CHECKED"; }?> /> <?=traduz("Auditorias Reprovadas")?>
						</label>
					</div>
				</div>
			</div>

            <?php if( $login_fabrica == 24 ) { ?>
            <div class='span3'>
                <div class="control-group">
                    <div class="controls">
                        <label class="checkbox label label-important" style="padding-left: 5px;">
                            <input type='checkbox' class='os_bloqueada' name="os_bloqueada" value='1' <?= !empty($os_bloqueada) ? 'checked' : '' ?> /> OS's Bloqueadas
                        </label>
                    </div>
                </div>
            </div>
            <?php } ?>

            <?php if($login_fabrica == 148){ ?>
            <div class='span3'>
                <div class="control-group">
                    <div class="controls" >
                        <label class="checkbox label label-warning" style="padding-left: 5px;">
                            <input type='checkbox' class='auditoria_excepcional' name="auditoria_excepcional" value='1' <?php if($_POST["auditoria_excepcional"] == '1'){ echo "CHECKED"; }?> /> <?=traduz("Garantia Excepcional")?>
                        </label>
                    </div>
                </div>
            </div>
            <?php } ?>
		</div>
		<p>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?=traduz('Pesquisar')?></button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
			<input type="submit" class='btn btn-primary btn_listar_auditoria' name="btn_listar_auditoria" id="btn_listar_auditoria" value="Listar Todas">
		</p>
		<br />
</form>
<br>
<?
if ($btn_acao == "submit") {
	if (pg_num_rows($resConsulta) > 0) { ?>
		<table border='0' cellspacing='0' cellpadding='5' style="margin: 0 auto;">
		    <tr>
			    <td style="width:10px; background-color: #dff0d8; border-color: #d6e9c6"></td>
			    <td><b><?=traduz("Interação Admin")?></b></td>
		    </tr>
		    <tr>
			    <td style="width:10px; background-color: #f2dede; border-color: #d6e9c6"></td>
			    <td><b><?=traduz("Interação Posto")?></b></td>
		    </tr>
	    </table>
	    <br/>
	    <?php
	    $count = pg_num_rows($resConsulta);

		for ($i = 0 ; $i < $count; $i++) {
			$os	= pg_fetch_result($resConsulta,$i,'os');

			$sql = "SELECT tbl_peca.referencia ,
							tbl_peca.descricao,
							tbl_peca.peca_critica,
							tbl_os_item.qtde,
							tbl_servico_realizado.descricao AS servico_realizado
					FROM tbl_peca
						JOIN tbl_os_item USING (peca)
						JOIN tbl_os_produto USING (os_produto)
						LEFT JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
					WHERE tbl_os_produto.os = $os";
			$resPeca = pg_query($con,$sql);

			if(pg_num_rows($resPeca) > 0){
				$count_k = pg_num_rows($resPeca);

				?>
				<div id='div_peca_<?=$i?>' style="display:none">
					<h4 style="margin: 10px;">OS: <?=$os?></h4>
					<table id="resultado_peca_<?=$i?>" class='table table-striped table-bordered table-hover table-fixed' style="margin: 10px; padding-right: 20px;">
						<thead>
							<tr class='titulo_coluna'>
								<th><?=traduz("Código")?> "</th>"
								<th><?=traduz("Descrição")?>."</th>"
								<th width="80"><?=traduz("Peça Critica")?>."</th>"
								<th width="20"><?=traduz("Qtde")?>."</th>
								<th width="100"><?=traduz("Serviço Realizado")?></th>
							</tr>
						</thead>  
						<tbody>


				<?

				for($k=0; $k<$count_k; $k++){
					$codigo_peca       = pg_fetch_result($resPeca,$k,'referencia');
					$descricao_peca    = pg_fetch_result($resPeca,$k,'descricao');
					$peca_critica      = pg_fetch_result($resPeca,$k,'peca_critica');
					$qtde              = pg_fetch_result($resPeca,$k,'qtde');
					$servico_realizado = pg_fetch_result($resPeca,$k,'servico_realizado');

					if($peca_critica == 't'){
						$peca_critica = "Sim";
					}else{
						$peca_critica = "Não";
					}
					?>
								<tr>
									<td class="tac"><?=$codigo_peca?></td>
									<td class="tac"><?=$descricao_peca?></td>
									<td class="tac"><?=$peca_critica?></td>
									<td class="tac"><?=$qtde?></td>
									<td class="tac"><?=$servico_realizado?></td>
								</tr>

				<?php
				}
				?>
						</tbody>
					</table>
				</div>
				<?
				$peca[$i] = traduz("LISTAR");
				$hiddenPeca = "";
			}else{
				$peca[$i] = "";
				$hiddenPeca = "hidden";
			}

			if (in_array($login_fabrica, array(152,180,181,182) and $status_auditoria == 6 )) {

				$sql_def = "SELECT tbl_defeito_constatado.descricao ,
									tbl_os_defeito_reclamado_constatado.tempo_reparo,
									tbl_diagnostico.tempo_estimado
							FROM tbl_os_defeito_reclamado_constatado
							INNER JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os_defeito_reclamado_constatado.defeito_constatado
							INNER JOIN tbl_os ON tbl_os_defeito_reclamado_constatado.os = tbl_os.os
                            INNER JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
                            INNER JOIN tbl_diagnostico ON tbl_diagnostico.defeito_constatado = tbl_os_defeito_reclamado_constatado.defeito_constatado AND tbl_diagnostico.familia = tbl_produto.familia
							WHERE tbl_os_defeito_reclamado_constatado.os = {$os}
							AND tbl_diagnostico.fabrica = {$login_fabrica}
							AND tbl_defeito_constatado.fabrica = {$login_fabrica} ";
				$res_def = pg_query($con, $sql_def);

				$count_def = pg_num_rows($res_def);

				if ($count_def > 0) {
					?>
					<div id="div_defeito_<?=$i?>"  style="display:none">
							<h4 style="margin: 10px;">OS: <?=$os?></h4>
							<table id="resultado_defeito_<?=$i?>" class='table table-striped table-bordered table-hover table-fixed' style="margin: 10px; padding-right: 20px;">
								<thead>
									<tr class='titulo_coluna'>
										<th><?=traduz("Defeito")?></th>
										<th><?=traduz("Horas Técnicas")?></th>
										<th><?=traduz("Tempo Estimado")?></th>
									</tr>
								</thead>
								<tbody>

					<?
					for ($d = 0 ; $d < $count_def; $d++) {

						$descricao	= pg_fetch_result($res_def,$d,'descricao');
						$tempo_reparo	= pg_fetch_result($res_def,$d,'tempo_reparo');
						$tempo_estimado	= pg_fetch_result($res_def,$d,'tempo_estimado');
						?>
									<tr>
										<td class="tac"><?=$descricao?></td>
										<td class="tac"><?=$tempo_reparo?></td>
										<td class="tac"><?=$tempo_estimado?></td>
									</tr>
						<?php
						}
						?>

							</tbody>
						</table>
					</div>
					<?php
						$defeito[$i] = "LISTAR";
						$hiddenDefeito = "";
				}else{
					$defeito[$i] = "";
					$hiddenDefeito = "hidden";
				}

				$sql = "SELECT tbl_tipo_atendimento.tipo_atendimento FROM tbl_tipo_atendimento
					INNER JOIN tbl_os ON tbl_os.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento AND tbl_tipo_atendimento.entrega_tecnica IS TRUE
					WHERE tbl_os.os = {$os}";
				$resEntregaTecnica = pg_query($con,$sql);

				if(pg_num_rows($resEntregaTecnica) > 0){
					$os_entrega_tecnica[] = $os;

					$sql = "SELECT tbl_produto.referencia,
							tbl_produto.descricao,
							tbl_os_produto.capacidade
						FROM tbl_os_produto
							INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
						WHERE tbl_os_produto.os = {$os}";
					$resProduto = pg_query($con,$sql);

					if(pg_num_rows($resProduto) > 0){
					?>
						<div id='div_produto_<?=$i?>' style="display:none">
							<h4 style="margin: 10px;">OS: <?=$os?></h4>
							<table id="resultado_produto_<?=$i?>" class='table table-striped table-bordered table-hover table-fixed' style="margin: 10px; padding-right: 20px;">
								<thead>
									<tr class='titulo_coluna'>
										<th><?=traduz("Referência")?></th>
										<th><?=traduz("Descrição")?></th>
										<th><?=traduz("Qtde")?></th>
									</tr>
								</thead>
								<tbody>
									<?php
										$count_produto = pg_num_rows($resProduto);
										for($k = 0; $k<$count_produto; $k++){
									?>
									<tr>
										<td class="tac"><?=pg_fetch_result($resProduto, $k, "referencia")?></td>
										<td class="tac"><?=pg_fetch_result($resProduto, $k, "descricao")?></td>
										<td class="tac"><?=pg_fetch_result($resProduto, $k, "capacidade")?></td>
									</tr>
									<?php
										}
									?>
								</tbody>
							</table>
						</div>
					<?php
					}
				}
			}
		}
		?></div>

		<table id="resultado_posto" class='table table-striped table-bordered table-large' style="margin: 0 auto;" >
			<thead>
				<tr class='titulo_coluna'>
					<th><?=traduz("OS")?></th>
					<th><?=traduz("Data")?></th>

					<?php if($auditoria_aprovado == "1"){ ?>
						<th><?=traduz("Aprovado em")?></th>
					<?php } ?>

					<th><?=traduz("Posto")?></th>
					<th><?=traduz("Produto")?></th>
                    <?php if ($login_fabrica == 148) { ?>
                        <th><?=traduz("Produto em Estoque")?></th>
                    <?php } ?>
                    <?php
                    if (in_array($login_fabrica, [152,180,181,182])) { ?>
                        <th><?=traduz("Linha")?></th>
                    <?php
                    }
                    ?>
					<th><?=traduz("Auditoria")?></th>
					<th><?=traduz("Observação")?></th>
					<th><?=traduz("Peça")?></th>
					<?php if($login_fabrica != 158){ ?>
						<th><?=traduz("Qtde KM")?></th>
					<? }
// 					if($auditoria_reincidente != "" && $auditoria_reincidente == $status_auditoria){
?>
						<th><?=traduz("Reincidente")?></th>
<?
// 					}
// 					if($auditoria_numero_serie != "" && $auditoria_numero_serie == $status_auditoria){
?>
						<th><?=traduz("Núm. de Série")?></th>
<?
                    if (isset($_POST["auditoria_aprovado"])) { ?>
                        <th><?=traduz("Data de Aprovação")?></th>
                    <?php
                    } else if (isset($_POST["auditoria_reprovado"])) { ?>
                        <th><?=traduz("Data de Reprovação")?></th>
                    <?php
                    }

// 					}
?>
					<?php  if (in_array($login_fabrica, array(152,180,181,182) and $status_auditoria == 6 )) { ?>
						<th><?=traduz("Defeitos e Horas")?></th>
					<? }

                    if(in_array($login_fabrica, array(152,180,181,182))) { /*HD - 4292800*/?>
                        <th><?=traduz("Classificação")?></th>
                    <?php } ?>
					<th><?=traduz("Ações")?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$count_i = pg_num_rows($resConsulta);
				for ($i = 0 ; $i < $count_i; $i++) {
					$auditoria_os               = pg_fetch_result($resConsulta,$i,'auditoria_os');
					$auditoria_status           = pg_fetch_result($resConsulta,$i,'auditoria_status');
					$os                         = pg_fetch_result($resConsulta,$i,'os');
					$sua_os                     = pg_fetch_result($resConsulta,$i,'sua_os');
					$data_abertura              = pg_fetch_result($resConsulta,$i,'data_abertura');
					$produto                    = pg_fetch_result($resConsulta,$i,'produto');
					$posto                      = pg_fetch_result($resConsulta,$i,'nome');
					$codigo_posto               = pg_fetch_result($resConsulta,$i,'codigo_posto');
					$paga_mao_obra              = pg_fetch_result($resConsulta,$i,'paga_mao_obra');
					$aprovada                   = pg_fetch_result($resConsulta,$i,'liberada');
					$reprovada                  = pg_fetch_result($resConsulta,$i,'reprovada');
                    $cancelada                  = pg_fetch_result($resConsulta,$i,'cancelada'); //hd_chamado=3049906

                    $data_fechamento            = pg_fetch_result($resConsulta,$i,'data_fechamento');
                    $finalizada                 = pg_fetch_result($resConsulta,$i,'finalizada');

					$auditoria                  = pg_fetch_result($resConsulta,$i,'descricao_auditoria');
					$observacao_auditoria       = pg_fetch_result($resConsulta,$i,'observacao');
					$tipo_atendimento_km_google = pg_fetch_result($resConsulta,$i,'km_google');
					$tipo_os                    = pg_fetch_result($resConsulta,$i,'tipo_os');
					$tipo_atendimento_descricao = pg_fetch_result($resConsulta,$i,'tipo_atendimento_descricao');
                    $linha_produto              = pg_fetch_result($resConsulta,$i,'nome_linha');

					$data_format = explode("-",$data_abertura);
					$data_abertura = $data_format[2]."/".$data_format[1]."/".$data_format[0];

					$sua_os = empty($sua_os) ? $os : $sua_os;
                    if ($login_fabrica == 24) {
                        $temPeca = false;
                        $sqlItem = " SELECT os_item
                                     FROM tbl_os_item
                                     JOIN tbl_os_produto USING(os_produto) 
                                     WHERE fabrica_i = $login_fabrica 
                                     AND os = $os
                                     AND JSON_FIELD('excluida_auditoria', parametros_adicionais)::boolean IS NOT TRUE";
                        $resItem = pg_query($con, $sqlItem);
                        if (pg_num_rows($resItem) > 0) {
                            $temPeca = true;
                        }
                    }

					if(($auditoria_km == $status_auditoria || $auditoria_fabrica == $status_auditoria) && empty($aprovada) && empty($reprovada)){
						$qtde_km = 100 * pg_fetch_result($resConsulta,$i,'qtde_km');
					} else {
						$qtde_km = pg_fetch_result($resConsulta, $i, "qtde_km");
					}
					if($auditoria_reincidente == $status_auditoria){
						$reincidente = pg_fetch_result($resConsulta,$i,'os_reincidente');
					}
					$numero_serie = pg_fetch_result($resConsulta,$i,'serie');

					if(ultima_interacao($os) == "fabrica"){
						$color = "#dff0d8";
						$border_color = "#d6e9c6";
					}else if(ultima_interacao($os) == "posto"){
						$color = "#f2dede";
						$border_color = "#eed3d7";
					}else{
						$color = "";
						$border_color = "";
					}
					$produto_referencia = "";
					$produto_descricao  = "";

					if(in_array($os, $os_entrega_tecnica)){
						$produto = '<a id="produto_'.$i.'">'.traduz("Listar Produtos").'</a>';
					}else{
						$sql = "SELECT tbl_produto.referencia,
								tbl_produto.descricao AS descricao_produto
							FROM tbl_produto
							WHERE produto = $produto ";
						$resProduto = pg_query($con,$sql);

						$produto_referencia   = pg_fetch_result($resProduto,0,'referencia');
						$produto_descricao    = pg_fetch_result($resProduto,0,'descricao_produto');
						$produto = $produto_referencia." - ".$produto_descricao;
					}

					?>
					<tr>
						<td style="background-color: <?=$color?>; border-color: <?=$border_color?>">
							<input type="hidden" id="os_<?=$i?>" value="<?=$os?>">
							<input type="hidden" id="auditoria_os_<?=$i?>" value="<?=$auditoria_os?>">
							<a href='os_press.php?os=<?=$os?>' target='_blank'><?=$sua_os?></a><br/>
							<?if($login_fabrica==148 and $tipo_os ==17) echo '<span class="label label-important">OS Fora <br />  de Garantia</span>' ;?>
						</td>
						<td style="background-color: <?=$color?>; border-color: <?=$border_color?>" class="tac"><?=$data_abertura?></td>
						<?php
						if($auditoria_aprovado == "1"){
							$dias_aprovado = strtotime(pg_fetch_result($resConsulta,$i,'data_abertura')) - strtotime($aprovada);
							$dias_aprovado = ((int) floor($dias_aprovado / (60 * 60 * 24))*(-1));
							$dias_aprovado .= " dias";
						?>
						<td style="background-color: <?=$color?>; border-color: <?=$border_color?>" class="tac"><?=$dias_aprovado?></td>
						<?php
						}
						?>
						<td style="background-color: <?=$color?>; border-color: <?=$border_color?>"><?=$posto?></td>

						<td style="background-color: <?=$color?>; border-color: <?=$border_color?>" id="produto_<?=$i?>"><?=$produto?></td>
                        <?php if ($login_fabrica == 148) { ?>
                            <td style="background-color: <?=$color?>; border-color: <?=$border_color?>" id="produto_em_estoque_<?=$i?>"><?=getProdutoEmGarantia($os)?></td>
                        <?php } ?>
                        <?php
                        if (in_array($login_fabrica, [152,180,181,182])) { ?>
                            <td style="background-color: <?=$color?>; border-color: <?=$border_color?>"><?= $linha_produto ?></td>
                        <?php
                        }
                        ?>
						<td style="background-color: <?=$color?>; border-color: <?=$border_color?>" class="tac"><?=$auditoria?></td>
						<td style="background-color: <?=$color?>; border-color: <?=$border_color?>"><?=$observacao_auditoria?></td>
						<td style="background-color: <?=$color?>; border-color: <?=$border_color?>" id="peca_<?=$i?>" class="tac">
							<?php if(!empty($peca[$i])){?>
							<a data-id="<?=$os?>" id="peca_<?=$i?>" data-auditoriaos="<?=$auditoria_os?>"  data-posto="<?=$codigo_posto?>"><?=$peca[$i]?></a>
							<?php }?>

						</td>
                        <td nowrap style="background-color: <?=$color?>; border-color: <?=$border_color?>" >

<?php
							if($tipo_atendimento_km_google == "t" && $auditoria_km == $auditoria_status){
?>
									<?php
									if (empty($aprovada) && empty($reprovada) && empty($cancelada)) { //hd_chamado=3049906 adicionada cancelada
									?>
										<input type="text" style="height: 30px !important; padding: 4px 6px; width: 70px;" id="qtde_km_<?=$i?>" value="<?=$qtde_km?>" />
										<button type="button" class="btn btn-primary btn-small" style="vertical-align: top; margin-top: 2px;" data-loading-text="Salvando..." id="btKM_<?=$i?>" onclick="salvarKM(<?=$i?>,<?=$os?>);"><?=traduz("Salvar")?></button>
										<div id="mensagem_km_<?=$i?>" class="alert alert-success" style="padding: 0px; width: 100%; height:20x; display: none;" ><?=traduz("KM Gravado")?></div>
									<?php
									} else {
	                                    echo $qtde_km;
									}
							}
?>
                            </td>
                            <td style="background-color: <?=$color?>; border-color: <?=$border_color?>" >
<?php
						if ($auditoria_reincidente != ""){
						?>
							<?=$reincidente?>
<?php
						}
?>
                        </td>
                        <td style="background-color: <?=$color?>; border-color: <?=$border_color?>">
<?php
						?>
							<?=$numero_serie?>
						<?php
?>
                        </td>

<?php
                        if (isset($_POST["auditoria_aprovado"])) {
?>
                            <td><?=is_date(substr($aprovada, 0, 19), 'ISO', 'EUR')?></td>
<?php
                        } else if (isset($_POST["auditoria_reprovado"])) {
?>
                            <td class="tac">
                                <?php
                                    if (!empty($cancelada)) {
                                        echo is_date(substr($cancelada, 0, 19), 'ISO', 'EUR');
                                    } else if (!empty($reprovada)) {
                                        echo is_date(substr($reprovada, 0, 19), 'ISO', 'EUR');
                                    }

                                ?>
                              </td>
<?php
                        }

						if (in_array($login_fabrica, array(152,180,181,182) && $status_auditoria == 6 )) { ?>
							<td style="background-color: <?=$color?>; border-color: <?=$border_color?>" id="defeito_<?=$i?>"><a id="defeito_<?=$i?>"><?=$defeito[$i]?></a></td>
						<?php }

                        if(in_array($login_fabrica, array(152,180,181,182))) { /*HD - 4292800*/?>
                            <td style="background-color: <?=$color?>; border-color: <?=$border_color?>">
                                <label for="tecnico_<?=$i?>"><input type="radio" id="tecnico_<?=$i?>" name="classificacao_<?=$i?>" value="tecnico" onclick='mostrarAprovar("btAprovado_<?=$i?>");'>&nbsp;<?=traduz("Técnico")?>
                                </label>
                                <label for="logistico_<?=$i?>"><input type="radio" id="logistico_<?=$i?>" name="classificacao_<?=$i?>" value="logistico" onclick='mostrarAprovar("btAprovado_<?=$i?>");'>&nbsp;<?=traduz("Logístico")?></label>
                                <label for="comercial_<?=$i?>"><input type="radio" id="comercial_<?=$i?>" name="classificacao_<?=$i?>" value="comercial" onclick='mostrarAprovar("btAprovado_<?=$i?>");'>&nbsp;<?=traduz("Comercial")?></label>
                                
                            </td>
                        <?php } ?>
						<td nowrap style="background-color: <?=$color?>; border-color: <?=$border_color?>" id="status_<?=$i?>" >
    						<button type="button" class="btn btn-primary btn-small" id="btInteragir_<?=$i?>"><?=traduz("Interagir")?></button>
                            <?
                            $statusOs = getOsStatus($os);

                            if($aprovada == "" && empty($reprovada) && os_excluida($os) == false) {
                                if ($login_fabrica == 156 and $tipo_atendimento_descricao == "Orçamento") {
                                    $osExterna = getOsExterna($os);
                                    $statusOsExterna = getOsStatus($osExterna);

                                    if ($statusOsExterna == 220) {
                                        ?>
                                        <button type="button" class="btn btn-success btn-small" data-loading-text="Salvando..." id="btAprovado_<?=$i?>" onclick="aprovarOS(<?=$i?>,<?=$os?>,<?=$auditoria_os?>,'');"><?=traduz("Aprovar")?></button>
                                        <?php
                                    }
                                } elseif (($login_fabrica == 156 && $statusOs == 220) || ($login_fabrica == 156 and $status_auditoria <> 6) || $login_fabrica != 156) {
                                		if ($login_fabrica == 148) {
                                			$funcao_js = "justificarAprovarOS(".$i.",".$os.",".$auditoria_os.",'','".$tipo_atendimento_descricao."');";
                                            $funcao_excep_js = "justificarAprovarOSExcep(".$i.",".$os.",".$auditoria_os.",'','".$tipo_atendimento_descricao."');";
                                		} else{
                                			$funcao_js = "aprovarOS(".$i.",".$os.",".$auditoria_os.",'');";
                                		}

                                        if(in_array($login_fabrica, array(152,180,181,182))) { 
                                            $aux_disabled = " disabled='disabled' style='cursor: not-allowed;' ";
                                        }
                                	?>
                                    <button <?=$aux_disabled;?> type="button" class="btn btn-success btn-small" data-loading-text="Salvando..." id="btAprovado_<?=$i?>" onclick="<?=$funcao_js;?>"><?=($login_fabrica == 148) ? traduz('Procedente') : traduz('Aprovar')?></button>

                                    <?php if($login_fabrica == 148){ ?>
                                        <button type="button" class="btn btn-warning btn-small" data-loading-text="Salvando..." rel="excepcional" id="btGarExcepcional_<?=$i?>" onclick="<?=$funcao_excep_js;?>"><?=traduz("Garantia Excepcional")?></button>
                                    <?php }?>

                                <? }
                                if(!isset($novaTelaOs)){
            						if($paga_mao_obra == 't' && ($login_fabrica == 156 && $tipo_atendimento_descricao != "Orçamento")){ ?>
            							<button type="button" class="btn btn-warning btn-small" data-loading-text="Salvando..." id="btMaoObrao_<?=$i?>" onclick="aprovarOS(<?=$i?>,<?=$os?>,<?=$auditoria_os?>,true);"><?=traduz("Aprovar Sem MO")?></button>
            						<? }
                                }
        						if($login_fabrica == 156 && $tipo_atendimento_descricao == "Orçamento"){ ?>
        							<button type="button" class="btn btn-small" data-loading-text="Enviando" id="envia_orcamento_posto" ><?=traduz("Enviar Orçamento ao Posto")?></button>
        							<button type="button" class="btn btn-primary btn-small" id="btEditarVal_<?=$i?>"><?=traduz("Editar Val. de Peças")?></button>
                                <? }
                                
                                if($login_fabrica == 148 && $auditoria = 'Auditoria Valor Pecas'){?>
                                    <button type="button" class="btn btn-primary btn-small" id="btEditarVal_<?=$i?>"><?=traduz("Editar Val. de Peças")?></button>
                                <?php }

        						if (in_array($login_fabrica, array(157))) {
        							$fabricante = pg_fetch_result($resConsulta, $i, "fabricante");
        							if ($fabricante == "t" && $observacao_auditoria == "OS em Auditoria de Foto de Peça") { ?>
        								<button type="button" class="btn btn-inverse btn-small" data-loading-text="Salvando..." id="btReprovado_<?=$i?>"><?=traduz("Reprovar")?></button>
    								<? }
        						} ?>

                                <?php if (in_array($login_fabrica, [148, 156])): ?>
                                <button type="button" class="btn btn-danger btn-small" data-loading-text="Salvando..." id="btReprovado_<?=$i?>"><?=traduz("Reprovar")?></button>
                                <?php else: ?>
        						<button type="button" class="btn btn-danger btn-small" data-loading-text="Cancelando..." id="btCancelado_<?=$i?>"><?=traduz("Cancelar")?></button>
                                <?php endif ?>
    						<? } else if($aprovada != ""){ ?>
    							<label class="label label-success" style="cursor: none;"><?=traduz("Aprovado")?></label>
    						<? } else if($reprovada != ""){ ?>
    							<label class="label label-important" style="cursor: none;"><?=traduz("Reprovada")?></label>
    						<? }else if($cancelada != ""){ //hd_chamado=3049906 ?>
                                <label class="label label-important" style="cursor: none;"><?=traduz("Cancelada")?></label>
                            <? } ?>

                            <!-- Ações dos botões -->
                            <?php 
                                if( $login_fabrica == 24 ){
                                    $pgResource = pg_query("SELECT * FROM tbl_os_campo_extra WHERE os = {$os} AND fabrica = 24");
                                    if( pg_num_rows($pgResource) > 0 ){
                                        $osBloqueada = pg_fetch_result($pgResource, 0, 'os_bloqueada');
                                    }else{
                                        $osBloqueada = 'f';
                                    }
                                }
                            ?>

                            <?php if( $login_fabrica == 24 ) { ?>
                                <button type="button" class='btn btn-default btn-small bloquear_os btn-bloquear-<?=$os?>' data-id="<?=$os?>" style='display: <?= $osBloqueada == 'f' ? 'inline-block' : 'none'; ?>'> Bloquear </button>

                                <button type="button" class='btn btn-info btn-small desbloquear_os btn-desbloquear-<?=$os?>' data-id="<?=$os?>" style='display: <?= $osBloqueada == 't' ? 'inline-block' : 'none'; ?>'> Desbloquear </button>
                            <?php } ?>

                            <?php if( $login_fabrica == 24 && empty($data_fechamento) && empty($finalizada) && $temPeca) { ?>
                                <button type="button" class='btn btn-warning btn-small lista-pecas' data-id="<?=$os?>"> Excluir Peças </button> 
                            <?php } ?>
                            
						</td>
					</tr>
				<? } ?>
			</tbody>
		</table>
        <br />
        <?php
            unset($_POST["posto"]["nome"]);
            $jsonPOST = excelPostToJson($_POST);
        ?>

        <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
        <div id='gerar_excel' class="btn_excel">
            <span><img src='imagens/excel.png' /></span>
            <span class="txt"><?traduz("Gerar Arquivo Excel")?></span>
        </div>
	<?php }else{ ?>
		<div class="container">
			<div class="alert">
				<h4><?=traduz("Nenhum resultado encontrado")?></h4>
			</div>
		</div>
        <br />
	<?php
	}
}
if ($login_fabrica == 24) {
?>
<script type="text/javascript">
    $(document).ready(function() {
        $.ajax({
            url: 'relatorio_auditoria_status.php',
            type: 'POST',
            data: {ajax_valida_admin: true},
        })
        .done(function(data) {
            if (data == 'erro') {
                alert('Usuário sem permissão !');
                window.location.href='menu_auditoria.php';
            }
        });
    });

</script>
<?php 
}
include "rodape.php";
?>
