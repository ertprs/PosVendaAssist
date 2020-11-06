<?php

$regras["tipo_atendimento"] = array(
    "obrigatorio" => true,
);

$funcoes_fabrica = [
	"verifica_produto_inserido"
];

function verifica_produto_inserido() {
	global $con, $login_fabrica, $campos, $msg_erro;

	foreach ($campos["produtos"] as $posicao => $dados) {

    	if (!empty($dados['nota_fiscal']) && empty($dados['id'])) {

		    if (!$produto_inserido) {
		    	throw new Exception("Nenhum produto informado para a nota fiscal {$dados['nota_fiscal']}");
		    }

    	}

    }

}

/**
 * Função para validar a garantia do produto
 */
function valida_garantia_hikari($boolean = false) {
    global $con, $login_fabrica, $campos, $msg_erro;

    $data_abertura = $campos["data_abertura"];

    foreach ($campos["produtos"] as $posicao => $dados) {

    	if (!empty($dados['id'])) {
	        $data_compra   = $dados["data_nf"];
	        $produto       = $dados["id"];
	        $referencia    = $dados["referencia"];
	        $descricao     = $dados["descricao"];

	        if (!empty($produto) && !empty($data_compra) && !empty($data_abertura)) {
	            $sql = "SELECT garantia FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto}";
	            $res = pg_query($con, $sql);

	            if (pg_num_rows($res) > 0) {
	                $garantia = pg_fetch_result($res, 0, "garantia");

	                if (strtotime(formata_data($data_compra)." +{$garantia} months") < strtotime(formata_data($data_abertura))) {
	                    if ($boolean == false) {
	                        $msg_erro["msg"][] = traduz("Produto {$referencia} da nota fiscal {$dados['nota_fiscal']} está fora de garantia");
	                    } else {
	                        return false;
	                    }
	                } else if ($boolean == true) {
	                    return true;
	                }
	            }
	        }
    	}
    }
}

$valida_garantia = "valida_garantia_hikari";
