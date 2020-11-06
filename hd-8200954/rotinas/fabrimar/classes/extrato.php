<?php


use Posvenda\Model\Extrato as ExtratoModel;

class ExtratoTela
{

    private $_extrato;
    private $_fabrica;
    public $_model;
    public $_erro;

    private $_qtde_oss;

    private $_qtde_peca;
    private $_imposto_al;
    private $_total_os;
    private $_avulso = 0;
    private $_extrato_lancamento;
    private $_posto;

    public function __construct($fabrica, $extrato)
    {
        if (!empty($extrato)) {
            $this->_extrato = $extrato;
        }

        $this->_fabrica = $fabrica;

        if(!empty($this->_fabrica)){
            $this->_model = new ExtratoModel($this->_fabrica);
        }

    }

    public function getOsPostoExtrato($fabrica, $posto){
        if(empty($fabrica)){
            $fabrica = $this->_fabrica;
        }

        $pdo = $this->_model->getPDO();

        $sql = "SELECT  tbl_os.posto, COUNT(*) AS qtde, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
            FROM tbl_os
            JOIN tbl_os_extra USING (os)
            JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
            JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $fabrica
            WHERE tbl_os.fabrica = $fabrica
            AND   tbl_os_extra.extrato IS NULL
            AND   tbl_os.excluida      IS NOT TRUE
            AND tbl_os.posto IN ($posto)
            AND   tbl_os.finalizada::date <= current_date
            GROUP BY tbl_os.posto, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
            ORDER BY tbl_os.posto";

        $query  = $pdo->query($sql);
        $res    = $query->fetchAll(\PDO::FETCH_ASSOC);

        return $res;
    }

    public function verificaExtrato($fabrica, $qtde_dias){
        if(empty($fabrica)){
            $fabrica = $this->_fabrica;
        }

        $pdo = $this->_model->getPDO();

        $sql = "SELECT posto FROM
                (SELECT DISTINCT posto, max(to_char(data_geracao, 'YYYY-MM-DD')) AS data_geracao
                    FROM tbl_extrato WHERE fabrica = {$fabrica} GROUP BY posto) extrato
            WHERE data_geracao <= to_char(current_date - interval '".$qtde_dias." day', 'YYYY-MM-DD');";

        $query  = $pdo->query($sql);
        $res    = $query->fetchAll(\PDO::FETCH_ASSOC);

        return $res;
    }

    public function gerarComunicadoPosto($fabrica, $posto, $mensagem){
        if(empty($fabrica)){
            $fabrica = $this->_fabrica;
        }

        $pdo = $this->_model->getPDO();

        if(!empty($posto)){
            $sql = "INSERT INTO tbl_comunicado (mensagem, fabrica, posto, obrigatorio_site, ativo) VALUES
                ('$mensagem',{$fabrica}, {$posto},'t','t')";
            $query = $pdo->query($sql);

            if(!$query){
                $this->_erro = $pdo->errorInfo();
                throw new \Exception("Erro ao atualizar o avulso para o posto {$posto}");
            }
            return true;
        }else{
            throw new \Exception("Não foi informado o posto para gerar o comunicado.");
        }
    }

    public function getErro(){

        if(is_array($this->_erro)){
            return $this->_erro["2"];
        }else{
            return $this->_erro;
        }
    }

}

function relacionaExtratoOSTela($fabrica, $posto, $extrato = "", $dia_extrato = "", $marca = null, $os){
    global $con, $login_fabrica;

    if(empty($fabrica)){
        $fabrica = $login_fabrica;
    }elseif(empty($extrato)){
        throw new Exception("Extrato não informado para relacionar as OSs com o extrato");
    }elseif (empty($dia_extrato)) {
        throw new Exception("Dia de Geração de Extrato não informado para relacionar as OSs com o extrato");
    }elseif (empty($os)){
        throw new Exception("OS não informado para relacionar com extrato gerado");
    }

    if ($marca == null) {
        $sql = "UPDATE tbl_os_extra
                    SET extrato = $extrato
                FROM  tbl_os
                WHERE tbl_os.posto = $posto
                AND tbl_os.fabrica = $fabrica
                AND tbl_os.os = tbl_os_extra.os
                AND tbl_os.os = {$os}
                AND tbl_os.excluida IS NOT TRUE
                AND tbl_os.finalizada <= '$dia_extrato 23:59:59'";
    } else {
        $sql = "UPDATE tbl_os_extra
                    SET extrato = $extrato
                FROM  tbl_os
                INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
                WHERE tbl_os.posto = $posto
                AND tbl_os.fabrica = $fabrica
                AND tbl_os.os = tbl_os_extra.os
                AND tbl_os.os = {$os}
                AND tbl_os.excluida IS NOT TRUE
                AND tbl_os.finalizada <= '$dia_extrato 23:59:59'
                AND tbl_produto.marca = {$marca}";
    }

    $query  = pg_query($con, $sql);

    if(pg_last_error() > 0){
        throw new \Exception("Erro ao relacionar OS ao extrato");
    }

}

function atualizaAvulsoDoPosto($fabrica, $posto, $extrato = ""){
    global $con, $login_fabrica;

    if(empty($fabrica)){
        $fabrica = $login_fabrica;
    }elseif(empty($extrato)){
        throw new Exception("Extrato não informado para a atualizar o avulso para o posto {$posto}");
    }

    $sql = "UPDATE tbl_extrato_lancamento SET extrato = $extrato
            WHERE tbl_extrato_lancamento.fabrica = $fabrica
            AND tbl_extrato_lancamento.extrato IS NULL
            AND tbl_extrato_lancamento.posto = $posto";
    $query  = pg_query($con,$sql);

    if(pg_last_error() > 0){
        return false;
    }

    return true;
}

function atualizaValor($fabrica, $extrato){
    global $con, $login_fabrica;

    if(empty($fabrica)){
        $fabrica = $login_fabrica;
    }elseif (empty($extrato)) {
        throw new Exception("Extrato não informado para a atualizar o avulso para o posto {$posto}");
    }

    $sql = "UPDATE tbl_extrato
                SET avulso = (
                    SELECT SUM (valor)
                    FROM tbl_extrato_lancamento
                    WHERE tbl_extrato_lancamento.extrato = {$extrato}
                )
            WHERE tbl_extrato.fabrica = $fabrica
            AND tbl_extrato.extrato = {$extrato}";
    $query  = pg_query($con,$sql);

    if(pg_last_error() > 0){
        throw new \Exception("Erro ao atualizar os valores dos lançamentos avulsos");
    }

}

function verificaTotalAvulsoClasse($posto,$extrato,$fabrica){
    global $con, $login_fabrica;

    /*
    *   somar todos os avulsos a serem lancado dps total do extrato
    *   se o (total do extrat - total avulsos)<= 0 erro === rollback
    *   se for maior q zero pegar todos os avulsos para lancar  e fazer o atuluzaavulso para linkar o avulso com o extrato que vai ser gerado
    *   valor tootal do avulso e colocar no valor avulso no extrato
    *   depos calcular o extrato novamente
    */

    if(empty($fabrica)){
        $fabrica = $login_fabrica;
    }elseif(empty($extrato)){
        throw new Exception("Extrato não informado para a atualizar o avulso para o posto {$posto}");
    }elseif(empty($posto)){
        throw new Exception("Extrato não informado para a atualizar o avulso para o extrato {$extrato}");
    }

    $sql = "SELECT SUM(valor) from tbl_extrato_lancamento
            WHERE tbl_extrato_lancamento.fabrica = $fabrica
            AND tbl_extrato_lancamento.extrato IS NULL
            AND tbl_extrato_lancamento.posto = $posto";
    $res = pg_query($con,$sql);

    if(pg_num_rows($res) == 0){
        throw new \Exception("Erro ao atualizar o avulso para o posto {$posto}");
    }

    $total_avulsos = pg_fetch_result($res,0,0);

    return $total_avulsos;
}

function calculaExtrato($extrato = ""){
    global $con;

    /* Calcula OS e seus Itens */
    $sql = "SELECT
        SUM(tbl_os.mao_de_obra) as total_mo,
        SUM(tbl_os.qtde_km_calculada) as total_km,
        SUM(tbl_os.pecas) as total_pecas,
        SUM(tbl_os.valores_adicionais) as total_adicionais,
        tbl_extrato.avulso
        FROM tbl_os
            INNER JOIN tbl_os_extra USING(os)
            INNER JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
        WHERE tbl_os_extra.extrato = {$extrato}
    GROUP BY tbl_extrato.avulso";
    $query  = pg_query($con,$sql);
    $res    = pg_fetch_all($query);

    if(count($res) > 0){
        $res = $res[0];
        $total_mo         = (!empty($res['total_mo']))         ? $res['total_mo']         : 0;
        $total_km         = (!empty($res['total_km']))         ? $res['total_km']         : 0;
        $total_pecas      = ($res['total_pecas'] != "0")       ? $res['total_pecas']      : 0;
        $total_adicionais = (!empty($res['total_adicionais'])) ? $res['total_adicionais'] : 0;
        $avulso           = (strlen($res['avulso']) > 0) ? $res['avulso'] : 0;

        $total = $total_mo + $total_km + $total_pecas + $total_adicionais + $avulso;

        $sql = "UPDATE
                tbl_extrato
            SET
                total           = {$total},
                mao_de_obra     = {$total_mo},
                pecas           = {$total_pecas },
                deslocamento    = {$total_km},
                valor_adicional = {$total_adicionais}
            WHERE
                extrato = {$extrato}";
        pg_query($con,$sql);

        return $total;
    }else{
        $total = 0;
    }
}

function verificaLGRClasse($extrato = "", $posto = "", $data_15 = "", $fabrica = "", $lgr_troca_produto = false){
    global $con, $login_fabrica;

    if(empty($extrato)){
        $desc_posto = (!empty($posto)) ? "- Posto {$posto}" : "";
        throw new \Exception("Extrato não informado para a verificação de LGR {$desc_posto}");
    }

    if(empty($posto)){
        throw new \Exception("Posto não informado para a verificação de LGR - Extrato {$extrato}");
    }

    if(empty($data_15)){
        throw new \Exception("Período de geração não informado para a verificação de LGR - Extrato {$extrato}");
    }

    if(empty($fabrica)){
        $fabrica = $login_fabrica;
    }

    if ($lgr_troca_produto == true) {
         $sql = "UPDATE tbl_faturamento_item SET
                extrato_devolucao = $extrato
                FROM tbl_os_item,tbl_faturamento,tbl_extrato, tbl_peca
                WHERE tbl_os_item.os_item = tbl_faturamento_item.os_item
                AND tbl_faturamento.posto = tbl_extrato.posto
                AND tbl_faturamento.fabrica = tbl_extrato.fabrica
                AND tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                AND tbl_faturamento.fabrica = $fabrica
                AND tbl_faturamento.emissao >='2010-01-01'
                AND tbl_faturamento.emissao <='$data_15'
                AND tbl_faturamento.cancelada IS NULL
                AND tbl_faturamento_item.extrato_devolucao IS NULL
                AND tbl_peca.peca = tbl_os_item.peca
                AND (tbl_os_item.peca_obrigatoria OR tbl_peca.produto_acabado IS TRUE)
                AND tbl_peca.aguarda_inspecao IS NOT TRUE
                AND (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
                AND tbl_extrato.extrato = $extrato";
    } else {
        $sql = "UPDATE tbl_faturamento_item SET
                extrato_devolucao = $extrato
                FROM tbl_os_item,tbl_faturamento,tbl_extrato, tbl_peca
                WHERE tbl_os_item.os_item = tbl_faturamento_item.os_item
                AND tbl_faturamento.posto = tbl_extrato.posto
                AND tbl_faturamento.fabrica = tbl_extrato.fabrica
                AND tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                AND tbl_faturamento.fabrica = $fabrica
                AND tbl_faturamento.emissao >='2010-01-01'
                AND tbl_faturamento.emissao <='$data_15'
                AND tbl_faturamento.cancelada IS NULL
                AND tbl_faturamento_item.extrato_devolucao IS NULL
                AND tbl_peca.peca = tbl_os_item.peca
                AND tbl_os_item.peca_obrigatoria
                AND tbl_peca.aguarda_inspecao IS NOT TRUE
                AND (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
                AND tbl_extrato.extrato = $extrato";
    }
    pg_query($con,$sql);

    if(pg_last_error() > 0){
        throw new Exception("Erro ao verificar o LGR do extrato {$extrato} - /* SQL 1 */");
    }

   $sql = "UPDATE tbl_faturamento SET extrato_devolucao = $extrato
            FROM tbl_faturamento_item
            WHERE tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
            AND tbl_faturamento.posto = $posto
            AND tbl_faturamento.fabrica = $fabrica
            AND tbl_faturamento.emissao >='2010-01-01'
            AND tbl_faturamento.emissao <='$data_15'
            AND tbl_faturamento_item.extrato_devolucao = $extrato";
    pg_query($con,$sql);

    if(pg_last_error() > 0){
         throw new Exception("Erro ao verificar o LGR do extrato {$extrato} - /* SQL 2 */");
    }
   $sql = "INSERT INTO tbl_extrato_lgr (extrato, posto, peca, qtde)
            SELECT
            tbl_extrato.extrato,
            tbl_extrato.posto,
            tbl_faturamento_item.peca,
            SUM (tbl_faturamento_item.qtde)
            FROM tbl_extrato
            JOIN tbl_faturamento_item ON tbl_extrato.extrato = tbl_faturamento_item.extrato_devolucao
            WHERE tbl_extrato.fabrica = $fabrica
            AND tbl_extrato.extrato = $extrato
            GROUP BY tbl_extrato.extrato,
            tbl_extrato.posto,
            tbl_faturamento_item.peca";
    pg_query($con,$sql);

    if(pg_last_error() > 0){
         throw new Exception("Erro ao verificar o LGR do extrato {$extrato} - /* SQL 3 */");
    }
    return true;
}

function LGRNovo($extrato, $posto, $fabrica){
	global $con, $login_fabrica;

	$sql = "SELECT distinct tbl_os_extra.extrato, tbl_os_extra.os
			FROM tbl_os_extra
			inner join tbl_os_produto ON  tbl_os_produto.os = tbl_os_extra.os
			inner join tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
			WHERE tbl_os_extra.extrato = $extrato
			and tbl_os_item.servico_realizado = 11142
			AND tbl_os_item.peca_obrigatoria is true ";
	$res = pg_query($con,$sql);
	$result = pg_fetch_all($res);

	foreach ($result as $dadosLinha) {
		$os = $dadosLinha['os'];

		$sqlfaturamento = "INSERT INTO tbl_faturamento (fabrica, cfop, extrato_devolucao, emissao, saida, total_nota, posto) VALUES ($fabrica, '5949', $extrato, now(), now(), '0' ,$posto ) returning faturamento ";
		$resf = pg_query($con,$sqlfaturamento);

		$faturamento = pg_fetch_result($resf,0,0);

		$sql_os = "SELECT tbl_os_item.peca, tbl_os_item.qtde, tbl_os_item.custo_peca, tbl_os_item.os_item
					from tbl_os_produto
					inner join tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
					where tbl_os_produto.os = $dadosLinha[os]
					and tbl_os_item.fabrica_i = $fabrica ";
		$res_os = pg_query($con,$sql_os);
		$dadosItens    = pg_fetch_all($res_os);

		foreach($dadosItens as $dados){
			$peca       = $dados['peca'];
			$qtde       = $dados['qtde'];
			$custo_peca = ($dados['custo_peca'] == '')? '0': $dados['custo_peca'] ;
			$os_item    = $dados['os_item'];

			$sql_fat_item_existente = "SELECT faturamento_item FROM tbl_faturamento_item WHERE os = {$os} AND os_item = {$os_item}";
			$res_os = pg_query($con,$sql_fat_item_existente);

			if(pg_num_rows($res_os) == 0){

				$sql_fat_item = "INSERT INTO tbl_faturamento_item (faturamento, peca, devolucao_obrig, extrato_devolucao,  qtde, preco, os, os_item)
								VALUES ($faturamento, $peca, 't', $extrato, '$qtde', '$custo_peca', $os, $os_item)";
				$res_os = pg_query($con,$sql_fat_item);

				$sql_ext_lgr = "INSERT INTO tbl_extrato_lgr (peca, qtde, faturamento, posto, extrato) VALUES ($peca, $qtde, $faturamento, $posto, $extrato)";
				$res_os = pg_query($con,$sql_ext_lgr);

			}

		}
	}
}

function getPeriodoDiasLGR($qtde_dias = 0, $dia_extrato = ""){
    global $con, $_serverEnvironment;

    if($_serverEnvironment == "production"){
        if(empty($dia_extrato)){
            throw new \Exception("Dia de geração do Extrato não informado");
        }

        $sql = "SELECT ('$dia_extrato'::date - INTERVAL '1 month' + INTERVAL '{$qtde_dias} days')::date AS data";

        $query  = pg_query($con, $sql);
        $res    = pg_fetch_result($query, 0, "data");

    }else{
        $res = date("Y-m-d");

    }

    return $res;

}
?>
