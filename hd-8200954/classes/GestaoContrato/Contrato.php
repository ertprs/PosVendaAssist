<?php
namespace GestaoContrato;
use GestaoContrato\Comunicacao;


class Contrato extends Controller {
    private $objAud;
    public function __construct($login_fabrica, $con) {
        parent::__construct($login_fabrica, $con);
    }

    public function get($contrato = null, $cliente_admin = null, $data_ini = null, $data_fim = null, $contrato_status = null, $representante_admin = null, $genero_contrato = null, $contrato_status_descricao = null) {

        $cond    = "";

        if (strlen($contrato) > 0) {
            $cond .= " AND tbl_contrato.contrato = {$contrato}";
        }

        if (strlen($cliente_admin) > 0) {
            $cond .= " AND tbl_contrato.cliente = {$cliente_admin}";
        }

        if (strlen($contrato_status) > 0) {
            $cond .= " AND tbl_contrato.contrato IN( 
SELECT contratos.contrato
FROM(
SELECT contrato,
(SELECT tbl_contrato_status_movimento.contrato_status FROM tbl_contrato_status_movimento WHERE contrato = tbl_contrato.contrato ORDER BY data DESC LIMIT 1) AS status
FROM tbl_contrato
WHERE tbl_contrato.fabrica = {$this->_fabrica}
AND tbl_contrato.ativo IS TRUE
) AS contratos
JOIN tbl_contrato_status ON tbl_contrato_status.contrato_status = contratos.status
WHERE contratos.status = {$contrato_status}

)";
        }

        if (strlen($contrato_status_descricao) > 0) {
            $cond .= " AND tbl_contrato_status.descricao = '{$contrato_status_descricao}'";
        }

        if (strlen($representante_admin) > 0) {
            $cond .= " AND tbl_contrato.representante = {$representante_admin}";
        }

        if (strlen($genero_contrato) > 0) {
            $cond .= " AND tbl_contrato.genero_contrato = '{$genero_contrato}'";
        }

        if (strlen($data_ini) > 0 && strlen($data_fim) > 0) {
            $cond .= " AND tbl_contrato.data_input BETWEEN '$data_ini 00:00:00' and '$data_fim 23:59:59' ";
        }

        $sql = "SELECT tbl_contrato.*, 
                        tbl_contrato.data_vigencia::DATE data_vigencia_date,
                       (
                            SELECT SUM(preco) 
                              FROM tbl_contrato_item 
                             WHERE tbl_contrato_item.contrato=tbl_contrato.contrato
                       ) AS total_contrato, 
                       tbl_contrato_tabela.descricao AS nome_tabela,
                       tbl_tipo_contrato.descricao AS tipo_contrato_nome, 
                       tbl_cliente_admin.cliente_admin AS id_cliente_admin, 
                       tbl_cliente_admin.nome AS cliente_nome, 
                       tbl_cliente_admin.cnpj AS cliente_cpf, 
                       tbl_cliente_admin.email AS cliente_email, 
                       tbl_cliente_admin.fone AS cliente_fone, 
                       tbl_cliente_admin.celular AS cliente_celular, 
                       tbl_cliente_admin.complemento AS cliente_complemento, 
                       tbl_cliente_admin.bairro AS cliente_bairro, 
                       tbl_cliente_admin.cidade AS cliente_cidade, 
                       tbl_cliente_admin.estado AS cliente_uf, 
                       tbl_cliente_admin.cep AS cliente_cep, 
                       tbl_cliente_admin.endereco AS cliente_endereco, 
                       tbl_cliente_admin.numero AS cliente_numero, 
                       tbl_representante.representante,
                       tbl_representante.nome AS representante_nome,
                       tbl_representante.codigo AS representante_codigo,
                       tbl_representante.cnpj AS cpf_cnpj_representante,
                       tbl_representante.fone AS fone_representante,
                       tbl_representante.email AS email_representante,
                       tbl_representante.desconto AS desconto_representante,
                       tbl_posto_fabrica.codigo_posto AS posto_codigo,
                       tbl_posto.nome AS posto_nome,

                       tbl_posto_fabrica.posto AS posto_id,
                       tbl_os.os,
                       (
                            SELECT tbl_contrato_status.descricao
                              FROM tbl_contrato_status_movimento 
                              JOIN tbl_contrato_status ON  tbl_contrato_status.contrato_status = tbl_contrato_status_movimento.contrato_status
                             WHERE tbl_contrato_status_movimento.contrato = tbl_contrato.contrato 
                          ORDER BY tbl_contrato_status_movimento.data desc 
                             LIMIT 1
                       ) AS nome_status
                  FROM tbl_contrato 
                  JOIN tbl_representante ON  tbl_representante.representante = tbl_contrato.representante AND tbl_representante.fabrica = {$this->_fabrica}
                  JOIN tbl_cliente_admin ON  tbl_cliente_admin.cliente_admin = tbl_contrato.cliente AND tbl_cliente_admin.fabrica = {$this->_fabrica}
                  JOIN tbl_tipo_contrato ON  tbl_tipo_contrato.tipo_contrato = tbl_contrato.tipo_contrato AND tbl_tipo_contrato.fabrica = {$this->_fabrica}
                  JOIN tbl_contrato_tabela ON  tbl_contrato_tabela.contrato_tabela = tbl_contrato.contrato_tabela AND tbl_contrato_tabela.fabrica={$this->_fabrica}
             LEFT JOIN tbl_posto_fabrica ON  tbl_posto_fabrica.posto = tbl_contrato.posto AND tbl_posto_fabrica.fabrica={$this->_fabrica}
             LEFT JOIN tbl_posto ON  tbl_posto_fabrica.posto = tbl_posto.posto 
             LEFT JOIN tbl_contrato_os ON  tbl_contrato_os.contrato = tbl_contrato.contrato
             LEFT JOIN tbl_os ON  tbl_contrato_os.os = tbl_os.os  AND tbl_os.fabrica={$this->_fabrica}
                 WHERE tbl_contrato.fabrica={$this->_fabrica} 
                 $cond 
              ORDER BY tbl_contrato.contrato DESC";
        $res = pg_query($this->_con, $sql);


        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("Nenhum registro foi encontrado"));
        }

        if (pg_num_rows($res) == 0) {
            return [];
        }
        
        foreach (pg_fetch_all($res) as $key => $value) {
            $retorno[$key] = $value;
            $retorno[$key]["itens"] = $this->getItens($value["contrato"]);
        }
        return $retorno;

    }

    public function getContratoByStatus($contrato_status_descricao) {

        if ($contrato_status_descricao == "Aguardando Aprovação da Proposta") {
            $condicao = " AND c.data_aprovacao_fabrica IS NOT NULL  AND c.data_aprovacao_cliente IS NULL AND c.data_cancelado IS NULL"; 
        } elseif ($contrato_status_descricao == "Aguardando Assinatura") {
            $condicao = " AND c.data_aprovacao_fabrica IS NOT NULL  AND c.data_aprovacao_cliente IS NOT NULL AND c.data_cancelado IS NULL"; 
            $aprovado = true;

        } elseif ($contrato_status_descricao == "Cancelado") {
            $condicao = " AND c.data_cancelado IS NOT NULL"; 
        }

        $sql = "SELECT
                    c.contrato,
                    cm.nome AS nome_cliente,
                    rep.nome AS nome_representante
                    FROM tbl_contrato c
                    JOIN tbl_representante rep ON  rep.representante = c.representante AND rep.fabrica = {$this->_fabrica}
                    JOIN tbl_cliente_admin cm ON  cm.cliente_admin = c.cliente AND cm.fabrica = {$this->_fabrica}
                    WHERE c.fabrica = {$this->_fabrica} {$condicao} ORDER BY c.contrato DESC";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("Nenhum registro foi encontrado"));
        }
        if (pg_num_rows($res) == 0) {
            return [];
        }
        $retorno = [];
        if ($aprovado) {
            return pg_fetch_all($res);
        }
        foreach (pg_fetch_all($res) as $key => $rows) {
            $retorno[$rows["nome_representante"]][] = $rows["contrato"];
        }
        return $retorno;

    }

    public function getUltimoStatusContrato($contrato) {

        $sql = "SELECT tbl_contrato_status.descricao
                  FROM tbl_contrato_status_movimento 
                  JOIN tbl_contrato_status ON  tbl_contrato_status.contrato_status = tbl_contrato_status_movimento.contrato_status
                  WHERE tbl_contrato_status_movimento.contrato = $contrato 
               ORDER BY tbl_contrato_status_movimento.data DESC LIMIT 1";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("Nenhum registro foi encontrado"));
        }
        if (pg_num_rows($res) == 0) {
            return [];
        }
        return pg_fetch_assoc($res);
    }

    public function getRelatorioCustoProdutoByContrato($contrato) {

        $sql = "
                  SELECT tbl_contrato.contrato,
                  tbl_produto.referencia || ' - ' || tbl_produto.descricao NOME_PRODUTO,
                  tbl_contrato_item.produto,
                  tbl_os.qtde_km CUSTO_KM,
                  tbl_os.mao_de_obra CUSTO_MO, 
                  tbl_os.pecas CUSTO_PECA, 
                  tbl_cliente_admin.nome AS cliente_nome, 
                  tbl_contrato.data_vigencia AS data_vigencia, 
                  tbl_representante.nome AS representante_nome,


                  tbl_os.os
                    FROM tbl_contrato
                    JOIN tbl_contrato_item USING(contrato)
                    JOIN tbl_produto ON tbl_produto.produto = tbl_contrato_item.produto AND tbl_produto.fabrica_i = tbl_contrato.fabrica
                    JOIN tbl_contrato_os ON tbl_contrato_os.contrato = tbl_contrato.contrato
                    JOIN tbl_os ON tbl_os.os = tbl_contrato_os.os AND tbl_os.fabrica = tbl_contrato.fabrica
                      JOIN tbl_representante ON  tbl_representante.representante = tbl_contrato.representante AND tbl_representante.fabrica = {$this->_fabrica}
                      JOIN tbl_cliente_admin ON  tbl_cliente_admin.cliente_admin = tbl_contrato.cliente AND tbl_cliente_admin.fabrica = {$this->_fabrica}
                    WHERE tbl_contrato.contrato = {$contrato}";

        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("Nenhum registro foi encontrado"));
        }
        if (pg_num_rows($res) == 0) {
            return [];
        }
        return pg_fetch_all($res);
    }

    public function getPropostasAvencer() {


        $sql = "SELECT tbl_contrato.representante,
                       rep.nome AS nome_representante,

                        (
                            SELECT COUNT(*) AS qtde FROM tbl_contrato WHERE data_input::DATE = CURRENT_DATE - INTERVAL '1 DAYS'   AND data_aprovacao_cliente IS NULL AND data_aprovacao_fabrica IS NULL AND data_cancelado IS NULL 
                        ) AS um_dia,
                        (
                            SELECT COUNT(*) AS qtde FROM tbl_contrato WHERE data_input::DATE  BETWEEN CURRENT_DATE - INTERVAL '5 DAYS'  AND CURRENT_DATE - INTERVAL '1 DAYS'   AND data_aprovacao_cliente IS NULL AND data_aprovacao_fabrica IS NULL AND data_cancelado IS NULL 
                        ) AS cinco_dia,
                        (
                            SELECT COUNT(*) AS qtde FROM tbl_contrato WHERE data_input::DATE  BETWEEN CURRENT_DATE - INTERVAL '10 DAYS' AND CURRENT_DATE - INTERVAL '6 DAYS'   AND data_aprovacao_cliente IS NULL AND data_aprovacao_fabrica IS NULL AND data_cancelado IS NULL 
                        ) AS dez_dia,
                        (
                            SELECT COUNT(*) AS qtde FROM tbl_contrato WHERE data_input::DATE  > CURRENT_DATE - INTERVAL '15 DAYS'  AND data_aprovacao_cliente IS NULL AND data_aprovacao_fabrica IS NULL AND data_cancelado IS NULL 
                        ) AS quinze_dia
                        FROM tbl_contrato
                        JOIN tbl_representante rep ON  rep.representante = tbl_contrato.representante AND rep.fabrica = {$this->_fabrica}
                        WHERE tbl_contrato.fabrica= {$this->_fabrica} 
                        group by tbl_contrato.representante,nome_representante";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("Nenhum registro foi encontrado"));
        }
        if (pg_num_rows($res) == 0) {
            return [];
        }
        return pg_fetch_all($res);

    }
    
    public function getContratoStatus($contrato_status_descricao, $filtro = false) {

        if ($contrato_status_descricao == "Aguardando Aprovação da Proposta") {
            //$condicao = " AND csm.contrato_status=2"; 
            $condicao = " AND c.data_aprovacao_cliente IS NULL AND c.data_cancelado IS NULL"; 
        } elseif ($contrato_status_descricao == "Aguardando Assinatura" && $filtro == false) {
            $condicao = " AND c.data_aprovacao_fabrica IS NOT NULL  AND c.data_aprovacao_cliente IS NULL AND c.data_cancelado IS NULL"; 
            //$condicao = " AND csm.contrato_status=3"; 
        }  elseif (in_array($contrato_status_descricao , ["Aguardando Assinatura", "Aguardando Transporte"])  && $filtro == true) {
	    $contrato_status_descricao = "Aguardando Transporte";
            $condicao = " AND c.data_aprovacao_fabrica IS NOT NULL  AND c.data_aprovacao_cliente IS NOT NULL AND c.data_cancelado IS NULL"; 
            //$condicao = " AND csm.contrato_status=3"; 
        } elseif ($contrato_status_descricao == "Cancelado") {
            //$condicao = " AND c.data_cancelado IS NOT NULL"; 
            $condicao = " AND c.data_cancelado IS NOT NULL"; 
        }

        $sql = "SELECT DISTINCT c.contrato,
                    rep.nome AS nome_representante,
                    cla.nome AS nome_cliente
                    FROM tbl_contrato c
                    JOIN (
                      SELECT
                      MAX(data) AS data_ultimo_status,
                      contrato
                      FROM tbl_contrato_status_movimento
                      GROUP BY contrato
                    ) x ON x.contrato = c.contrato
                    JOIN tbl_contrato_status cs ON UPPER(TRIM(fn_retira_especiais(cs.descricao))) = UPPER(fn_retira_especiais('{$contrato_status_descricao}'))
                    JOIN tbl_contrato_status_movimento csm ON csm.data = x.data_ultimo_status AND csm.contrato_status = cs.contrato_status
                    JOIN tbl_representante rep ON  rep.representante = c.representante AND rep.fabrica = {$this->_fabrica}
                    JOIN tbl_cliente_admin cla ON  cla.cliente_admin = c.cliente AND cla.fabrica = {$this->_fabrica}
                   WHERE c.fabrica = {$this->_fabrica} {$condicao} 
                ORDER BY c.contrato DESC";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("Nenhum registro foi encontrado"));
        }
        if (pg_num_rows($res) == 0) {
            return [];
        }
        if ($filtro) {
            return pg_fetch_all($res);
        }
        $retorno = [];
        foreach (pg_fetch_all($res) as $key => $rows) {
            $retorno[$rows["nome_representante"]][] = $rows["contrato"];
        }
        return $retorno;

    }

    public function getItens($contrato) {

        $sql = "SELECT tbl_contrato_item.*, tbl_produto.descricao AS nome_produto, tbl_produto.referencia AS referencia_produto
                  FROM tbl_contrato_item 
                  JOIN tbl_produto ON  tbl_produto.produto = tbl_contrato_item.produto AND tbl_produto.fabrica_i = {$this->_fabrica}
                 WHERE tbl_contrato_item.contrato={$contrato}";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("Nenhum registro foi encontrado"));
        }

        return pg_fetch_all($res);

    }

    public function deleteItem($contrato_item) {

        $sql = "DELETE FROM tbl_contrato_item WHERE contrato_item={$contrato_item}";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con)) {
            return false;
        }

        return true;

    }

    public function getProduto($referencia = null) {

        $cond    = "";
       
        if (strlen($referencia) > 0) {
            $cond .= " AND referencia = '".trim($referencia)."'";
        }
       
        $sql = "SELECT * FROM tbl_produto WHERE fabrica_i={$this->_fabrica} $cond";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("Nenhum registro foi encontrado"));
        }

        if (pg_num_rows($res) == 0) {
            return [];
        }
        if (strlen($referencia) > 0) {
            return pg_fetch_assoc($res);
        }
        return pg_fetch_all($res);

    }

    public function addProposta($dados = []) {
      global $login_fabrica;
        $existe = false;

        if (empty($dados)) {
            return array("erro" => true, "msn" => traduz("Dados não enviado"));
        }

        if (strlen($dados["valor_contrato_final"]) > 0) {
            $campoV     = "valor_contrato,";
            $campoValor = "'".$dados["valor_contrato_final"]."',";
        }


        if (strlen($dados["desconto_representante"]) > 0) {
            $camposUpdateJson["desconto_representante"] = $dados["desconto_representante"];
        }

        if (strlen($dados["valor_total_produtos"]) > 0) {
            $camposUpdateJson["valor_total_produtos"] = $dados["valor_total_produtos"];
        }

        if (!empty($camposUpdateJson)) {
            $campoExt = "campo_extra,";
            $campoExtValor = "'".json_encode($camposUpdateJson)."',";
        }

        $sql = "INSERT INTO tbl_contrato (
                                        {$campoExt}
                                        {$campoV}
                                        fabrica,
                                        representante, 
                                        genero_contrato, 
                                        numero_contrato, 
                                        descricao, 
                                        tipo_contrato, 
                                        contrato_tabela, 
                                        qtde_preventiva, 
                                        qtde_corretiva, 
                                        cliente
                                    ) VALUES (
                                        {$campoExtValor}
                                        {$campoValor}
                                        ".$this->_fabrica.",
                                        '".$dados["representante"]."',
                                        '".$dados["genero_contrato"]."',
                                        '".$dados["numero_contrato"]."',
                                        '".$dados["descricao"]."',
                                        '".$dados["tipo_contrato"]."',
                                        '".$dados["contrato_tabela"]."',
                                        '".$dados["qtde_preventiva"]."',
                                        '".$dados["qtde_corretiva"]."',
                                        '".$dados["cliente"]."'
                                    ) RETURNING contrato";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error()) {
            return array("erro" => true, "msn" => pg_last_error());
        }

        if ($dados["qtde_preventiva"] > 0 || $dados["qtde_corretiva"] > 0) {
            $this->addAuditoriaContrato(pg_fetch_result($res, 0, 'contrato'), "Proposta em auditoria de Fabrica");
        }

        if ($dados["desconto_representante"] > $dados["desconto_representante_bd"]) {
             $this->addAuditoriaContrato(pg_fetch_result($res, 0, 'contrato'), "Contrato em auditoria de Fabrica - Desconto acima");
        }
          $xcontrato = pg_fetch_result($res, 0, 'contrato');

        if ($dados["qtde_preventiva"] == 0 && $dados["qtde_corretiva"] == 0 && $dados["desconto_representante"] <= $dados["desconto_representante_bd"]) {

          $xsql = "UPDATE tbl_contrato SET data_aprovacao_fabrica = current_timestamp
                   WHERE fabrica={$this->_fabrica} 
                     AND contrato={$xcontrato}";
          $xres = pg_query($this->_con, $xsql);

          $objComunicacao = new Comunicacao($login_fabrica, $this->_con);

          $dadosContrato = $this->get($xcontrato);

          $expira = date('Y-m-d H:i:s', strtotime("+15 days",strtotime($dadosContrato[0]["data_aprovacao_fabrica"]))); 
          $token = trim($dadosContrato[0]["contrato"])."|".trim($login_fabrica)."|".trim($dadosContrato[0]["cliente_email"])."|".trim($expira);
          $dadosContrato[0]["token"] = base64_encode($token);
          $objComunicacao->enviaPropostaAprovacaoCliente($dadosContrato[0]);


        }

        return array("sucesso" => true, "contrato" => $xcontrato);
    }


    public function addAuditoriaContrato($contrato,$obs)
    {
       global $login_admin;


        if (empty($contrato)) {
            return false;
        }

        $sql = "INSERT INTO tbl_contrato_auditoria (
                                        contrato,
                                        admin, 
                                        obs
                                    ) VALUES (
                                        ".$contrato.",
                                        ".$login_admin.",
                                        '".$obs."'
                                    )";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error()) {
            return false;
        }

        return true;

    }


    public function verificaAuditoriaContrato($contrato)
    {

        $sql = "SELECT contrato FROM tbl_contrato_auditoria WHERE contrato = ".$contrato." AND aprovado IS NULL AND reprovado IS NULL";
        $res = pg_query($this->_con, $sql);

        if (pg_num_rows($res) > 0) {
            return true;
        }

        return false;
    }


    public function editContrato($contrato, $dados = []) {

        $existe = false;

        if (empty($dados) || empty($contrato)) {
            return array("erro" => true, "msn" => traduz("Dados não enviado"));
        }

        $camposUpdate = [];
        if (strlen($dados["descricao"]) > 0) {
            $camposUpdate[] = "descricao='".$dados["descricao"]."'";
        }

        if (strlen($dados["qtde_corretiva"]) > 0) {
            $camposUpdate[] = "qtde_corretiva=".$dados["qtde_corretiva"];
        }

        if (strlen($dados["tipo_contrato"]) > 0) {
            $camposUpdate[] = "tipo_contrato=".$dados["tipo_contrato"];
        }

        if (strlen($dados["data_vigencia"]) > 0) {
            $camposUpdate[] = "data_vigencia='".$dados["data_vigencia"]."'";
        }

        if (strlen($dados["qtde_preventiva"]) > 0) {
            $camposUpdate[] = "qtde_preventiva=".$dados["qtde_preventiva"];
        }

        if (strlen($dados["dia_preventiva"]) > 0) {
            $camposUpdate[] = "dia_preventiva=".$dados["dia_preventiva"];
        }

        if (strlen($dados["posto_id"]) > 0) {
            $camposUpdate[] = "posto=".$dados["posto_id"];
        }
        if (strlen($dados["valor_contrato_final"]) > 0) {
            $camposUpdate[] = "valor_contrato='".$dados["valor_contrato_final"]."'";
        }

        $dadosContrato = $this->get($contrato)[0];

        $camposUpdateJson = json_decode($dadosContrato["campo_extra"],1);

        if (strlen($dados["mao_obra_fixa"]) > 0) {
            $camposUpdateJson["mao_obra_fixa"] = $dados["mao_obra_fixa"];
        }

        if (strlen($dados["valor_mao_obra_fixa"]) > 0) {
            $camposUpdateJson["valor_mao_obra_fixa"] = $dados["valor_mao_obra_fixa"];
        }

        if (strlen($dados["desconto_representante"]) > 0) {
            $camposUpdateJson["desconto_representante"] = $dados["desconto_representante"];
        }

        if (strlen($dados["valor_total_produtos"]) > 0) {
            $camposUpdateJson["valor_total_produtos"] = $dados["valor_total_produtos"];
        }

        if (!empty($camposUpdateJson)) {
            $camposUpdate[] = "campo_extra='".json_encode($camposUpdateJson)."'";
        }

        $xcamposUpdate = "";
        if (count($camposUpdate) > 0) {
            $xcamposUpdate = implode(",", $camposUpdate);

            $sql = "UPDATE tbl_contrato SET $xcamposUpdate WHERE contrato = {$contrato} AND fabrica = {$this->_fabrica}";
            $res = pg_query($this->_con, $sql);
            if (pg_last_error()) {
                return array("erro" => true, "msn" => pg_last_error());
            }

            if ($dados["desconto_representante"] > $dados["desconto_representante_bd"]) {
                 $this->addAuditoriaContrato($contrato, "Contrato em auditoria de Fabrica - Desconto acima");
            }

            return array("sucesso" => true, "contrato" => $contrato);
        }
    }


    public function addItens($dados = [], $edit=false) {

        $existe = false;

        if (empty($dados)) {
            return array("erro" => true, "msn" => traduz("Dados não enviado"));
        }
        $horimetro = (strlen($dados["horimetro"]) == 0) ? 0 : $dados["horimetro"];
        $preventiva = $dados["preventiva"];
        if ($edit) {

            $sqlValida = "SELECT contrato_item FROM tbl_contrato_item WHERE contrato=".$dados["contrato"]." AND produto=".$dados["produto"];
            $resValida = pg_query($this->_con, $sqlValida);
            if (pg_num_rows($resValida) > 0) {

                $contrato_item = pg_fetch_result($resValida, 0, 'contrato_item');
                $sqlUp = "UPDATE tbl_contrato_item 
                             SET preco='".$dados["preco"]."',
                                 horimetro='".$horimetro."',
                                 preventiva='".$preventiva."' 
                           WHERE contrato_item={$contrato_item}";
                $resUp = pg_query($this->_con, $sqlUp);

                if (pg_last_error()) {
                    return array("erro" => true, "msn" => "#1 " .pg_last_error());
                }
                return array("sucesso" => true, "contrato_item" => $contrato_item);

            } else {
            $sql = "INSERT INTO tbl_contrato_item (
                                            contrato,
                                            produto, 
                                            preco, 
                                            horimetro, 
                                            preventiva
                                        ) VALUES (
                                            '".$dados["contrato"]."',
                                            '".$dados["produto"]."',
                                            '".$dados["preco"]."',
                                            '".$horimetro."',
                                            '".$preventiva."'
                                        ) RETURNING contrato_item";
            $res = pg_query($this->_con, $sql);

            if (pg_last_error()) {
                return array("erro" => true, "msn" => "#2 " .pg_last_error());
            }
            return array("sucesso" => true, "contrato_item" => pg_fetch_result($res, 0, 'contrato_item'));
                
            }
        } else {
                
            $sql = "INSERT INTO tbl_contrato_item (
                                            contrato,
                                            produto, 
                                            preco, 
                                            horimetro, 
                                            preventiva
                                        ) VALUES (
                                            '".$dados["contrato"]."',
                                            '".$dados["produto"]."',
                                            '".$dados["preco"]."',
                                            '".$horimetro."',
                                            '".$preventiva."'
                                        ) RETURNING contrato_item";
            $res = pg_query($this->_con, $sql);

            if (pg_last_error()) {
                return array("erro" => true, "msn" => "#3 " .pg_last_error());
            }
            return array("sucesso" => true, "contrato_item" => pg_fetch_result($res, 0, 'contrato_item'));
        }
    }

    public function aprova_reprova_proposta_fabrica($contrato, $tipo, $motivo) {

        if ($tipo == "Aprovar") {
            $campo = " data_aprovacao_fabrica = current_timestamp";
            $campo2 = " aprovado = current_timestamp, motivo='$motivo'";
        } else {
            $campo = " data_cancelado = current_timestamp";
            $campo2 = " reprovado = current_timestamp, motivo='$motivo'";
        }

        $sql = "UPDATE tbl_contrato SET {$campo} 
                 WHERE fabrica={$this->_fabrica} 
                   AND contrato={$contrato}";
        $res = pg_query($this->_con, $sql);

        $sql = "UPDATE tbl_contrato_auditoria SET {$campo2}  
                 WHERE contrato={$contrato}";
        $res = pg_query($this->_con, $sql);
        return ($res) ? true : false;

    }

    public function aprova_reprova_proposta_cliente($contrato, $tipo,$aprovacao_cliente) {

        if ($tipo == "Aprovar") {
          $novo_status = 3;
            $campo = " data_aprovacao_cliente = current_timestamp";
        } else {
            $novo_status = 9;
            $campo = " data_cancelado = current_timestamp";
        }

        $sql = "UPDATE tbl_contrato SET {$campo}, aprovacao_cliente='".json_encode($aprovacao_cliente)."'
                 WHERE fabrica={$this->_fabrica} 
                   AND contrato={$contrato}";
        $res = pg_query($this->_con, $sql);
        if ($res) {
               
               $sql = "INSERT INTO  tbl_contrato_status_movimento (contrato, contrato_status) VALUES ({$contrato},$novo_status)";
               $res = pg_query($this->_con, $sql);

        }
        return ($res) ? true : false;

    }

    public function atualizaTotalContrato($contrato, $total_contrato) {

        $sql = "UPDATE tbl_contrato SET valor_contrato='{$total_contrato}' WHERE contrato={$contrato}";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con)) {
            return false;
        }
        return true;
    }


    public function addClienteAdmin($dados)
    {
     

        extract($dados);
     
        $sqlAdmin = "SELECT cliente_admin FROM tbl_cliente_admin WHERE cnpj = '{$cnpj}' AND fabrica = {$login_fabrica}";
        $resAdmin = pg_query($this->_con, $sqlAdmin);

        if (pg_num_rows($resAdmin) > 0) {
            $cliente_admin = pg_fetch_result($resAdmin,0,'cliente_admin');

        } else {
            $sql = "INSERT INTO tbl_cliente_admin (
                        nome,
                        codigo,
                        codigo_representante,
                        cnpj,
                        endereco,
                        numero,
                        complemento,
                        bairro,
                        cep,
                        cidade,
                        estado,
                        email,
                        fone,
                        celular,
                        fabrica
                    ) VALUES (
                        '$nome',
                        '$codigo',
                        '$codigo_representante',
                        '$cnpj',
                        '$endereco',
                        '$numero',
                        '$complemento',
                        '$bairro',
                        '$cep',
                        '$cidade',
                        '$estado',
                        '$email',
                        '$fone',
                        '$celular',
                        $login_fabrica
            ) RETURNING cliente_admin";

            $res = pg_query($this->_con, $sql);

            if (pg_last_error()) {
                return array("erro" => true, "msn" => "Erro ao cadastrar cliente admin");
            }
        
            $cliente_admin = pg_fetch_result($res,0,'cliente_admin');

        }
/*
        $sqlAdmin = "SELECT admin FROM tbl_admin WHERE cliente_admin = {$cliente_admin} AND fabrica = {$login_fabrica}";
        $resAdmin = pg_query($this->_con, $sqlAdmin);

        if (pg_num_rows($resAdmin) == 0) {

            $sql_admin = "INSERT INTO tbl_admin 
                            (
                                nome_completo, 
                                fone,
                                email,
                                cliente_admin,
                                fabrica,
                                login,
                                senha,
                                ativo,
                                cliente_admin_master,
                                privilegios
                            ) VALUES 
                            (
                                '$nome',
                                '$fone',
                                '$email',
                                $cliente_admin,
                                $login_fabrica,
                                '$email',
                                'tele6588',
                                't',
                                't',
                                '*'
                            )";
            $res_admin = pg_query($this->_con, $sql_admin);

            if (pg_last_error()) {
                return array("erro" => true, "msn" => "Erro ao cadastrar cliente admin");
            }
        } 
*/
        return array("sucesso" => true, "cliente_admin" => $cliente_admin);

    }

    public function verificaAnexoAssinatura($contrato)
    {
        
        $sql_tdocs = "SELECT json_field('typeId',obs) AS typeId 
                        FROM tbl_tdocs 
                       WHERE tbl_tdocs.fabrica = $this->_fabrica
                         AND tbl_tdocs.situacao = 'ativo'
                         AND tbl_tdocs.referencia_id = {$contrato}";
        $res_tdocs = pg_query($this->_con, $sql_tdocs);
 
        if (pg_num_rows($res_tdocs) > 0){
 
            $typeId = pg_fetch_all_columns($res_tdocs);

            if (!in_array('assinatura', $typeId)) {
                return false;
            } else {
                return true;
            }
 
        } else {
            return false;
        }
    }
}
