<?php
/**
 *
 * relatorio_extratificacao.class.php
 *
 * RelatÛrio de ExtratificaÁ„o - desenvolvido para Colormaq conforme HD 819523
 *   - atualizado conforme HD's 1134629, 1144726.
 *
 * @author  Francisco Ambrozio
 * @version 2014.08
 *
 *
 * class relatorioExtratificacao - Classe principal do programa
 *
 *  REGRAS:
 *
 *   - o relatÛrio È extraÌdo de 24 meses retroativo ao mÍs atual
 *   - o admin seleciona familia, meses e Ìndice
 *   - de acordo com o n˙mero de meses selecionado conta as OSs de cada mÍs
 *   - taxa de falha: total de OSs (meses) / total produÁ„o
 *   - populaÁ„o: soma de N meses anteriores
 *
 */

class relatorioExtratificacao
{
    private $login_fabrica;
    private $fabrica_nome;
    private $login_admin;
    private $meses;
    private $familia;
    private $regiao;
    private $estados_regiao;
    private $index_irc;
    private $data_inicial;
    private $data_final;
    private $pecas = array();
    private $periodo;
    private $fornecedores = array();
    private $revendas = array();
    private $produtos = array();
    private $produtos_unico = array();
    private $data_inicial_global;
    private $data_final_global;
    private $datas = array();
    private $msg_erro;
    private $result_view;
    private $sem_peca;
    private $flag_peca = false;
    private $posto;

    private $arr_os = array();
    private $arr_os_anterior = array();
    private $arr_os_15M = array();
    private $arr_total = array();
    private $arr_total_anterior = array();
    private $arr_total_15M = array();
    private $arr_falha = array();
    private $arr_falha_anterior = array();
    private $arr_fornecedor = array();

    private $populacao_1M = array();
    private $populacao_2M = array();
    private $populacao_3M = array();
    private $populacao_4M = array();
    private $populacao_5M = array();
    private $populacao_6M = array();
    private $populacao_7M = array();
    private $populacao_8M = array();
    private $populacao_9M = array();
    private $populacao_10M = array();
    private $populacao_11M = array();
    private $populacao_12M = array();
    private $populacao_13M = array();
    private $populacao_14M = array();
    private $populacao_15M = array();

    private $os_1M = array();
    private $os_2M = array();
    private $os_3M = array();
    private $os_4M = array();
    private $os_5M = array();
    private $os_6M = array();
    private $os_7M = array();
    private $os_8M = array();
    private $os_9M = array();
    private $os_10M = array();
    private $os_11M = array();
    private $os_12M = array();
    private $os_13M = array();
    private $os_14M = array();
    private $os_15M = array();

    private $irc_1M = array();
    private $irc_2M = array();
    private $irc_3M = array();
    private $irc_4M = array();
    private $irc_5M = array();
    private $irc_6M = array();
    private $irc_7M = array();
    private $irc_8M = array();
    private $irc_9M = array();
    private $irc_10M = array();
    private $irc_11M = array();
    private $irc_12M = array();
    private $irc_13M = array();
    private $irc_14M = array();
    private $irc_15M = array();

    private $cfe = array();
    private $cfe_per_unit_prod = array();
    private $cfe_per_unit_fat = array();
    private $cfe_per_unit_15 = array();
    private $faturados = array();

    private $pop_tmp = array();

    private $matriz_filial;

    private $temp_tbls = array(
            'os_serie' => '',
            'numero_serie1' => '',
            'numero_serie2' => '',
            'numero_serie' => ''
        );

    public function __construct()
    {
        $this->pop_tmp = array_combine(range(1, 15), array_fill(1, 15, 0));
        $this->bootstrap();
    }

    /**
     * getters
     */
    public function getMeses()
    {
        return $this->meses;
    }

    public function getFamilia()
    {
        return $this->familia;
    }

    public function getSemPeca()
    {
        return $this->sem_peca;
    }

    public function getIndexIRC()
    {
        return $this->index_irc;
    }

    public function getDataInicial()
    {
        return $this->data_inicial;
    }

    public function getDataFinal()
    {
        return $this->data_final;
    }

    public function getFornecedores()
    {
        return $this->fornecedores;
    }

    public function getRevendas()
    {
        return $this->revendas;
    }

    public function getProdutos()
    {
        return $this->produtos;
    }

    public function getProdutosUnico()
    {
        return $this->produtos_unico;
    }

    public function getDataInicialGlobal()
    {
        return $this->data_inicial_global;
    }

    public function getDataFinalGlobal()
    {
        return $this->data_final_global;
    }

    public function getPecas()
    {
        return $this->pecas;
    }

    public function getPeriodo()
    {
        return $this->periodo;
    }

    public function getPostos()
    {
        return $this->posto;
    }

    public function getMsgErro()
    {
        return $this->msg_erro;
    }

    public function getResultView()
    {
        return $this->result_view;
    }

    /**
     * setters
     */
    private function setMeses()
    {
        if (!empty($_POST["meses"])) {
            $this->meses = $_POST["meses"];
        }
        return $this;
    }

    private function setFamilia(){
        if (!empty($_POST["familia"])) {
            if ($_POST["familia"] == "irc_global") {
                $this->familia = 'global';
            } else {
                $this->familia = (int) $_POST["familia"];
            }
        }
        return $this;
    }

    private function setIndexIRC()
    {
        if (!empty($_POST["index_irc"])) {
            $this->index_irc = $_POST["index_irc"];
        } else {
            $this->index_irc = 1;
        }
        return $this;
    }

    private function setDataInicial()
    {
        if (!empty($_POST["ano_pesquisa"])) {
            $ano = $_POST["ano_pesquisa"];
        }

        if (!empty($_POST["mes_pesquisa"])) {
            $mes = $_POST["mes_pesquisa"];
        }

        if (!empty($ano) and !empty($mes)) {
            $this->data_inicial = $ano . '-' . $mes . '-01';
        } else {
            date_default_timezone_set('America/Sao_Paulo');
            $this->data_inicial = date('Y-m-01');
        }
        return $this;
    }

    private function setDataFinal()
    {
        $this->is_con();
        $data_inicial = $this->getDataInicial();
        $query = pg_query("select to_char(('$data_inicial'::date + interval '1 month') - interval '1 day', 'YYYY-MM-DD')::date");
        $this->data_final = pg_fetch_result($query, 0, 0);
        return $this;

    }

    private function setPeca01()
    {
        if (!empty($_POST['peca01'])) {
            $this->is_con();
            $arr_peca01 = explode('-', $_POST['peca01']);
            $referencia = trim($arr_peca01[0]);

            $sql = "SELECT peca FROM tbl_peca WHERE referencia = '$referencia' AND fabrica = $this->login_fabrica";
            $query = pg_query($sql);

            if (pg_num_rows($query) == 0) {
                $this->msg_erro = 'PeÁa 1 inv·lida.';
                return false;
            }

            $this->pecas[] = pg_fetch_result($query, 0, 'peca');
        }
        return $this;
    }

    private function setPeca02()
    {
        if (!empty($_POST['peca02'])) {
            $this->is_con();
            $arr_peca02 = explode('-', $_POST['peca02']);
            $referencia = trim($arr_peca02[0]);

            $sql = "SELECT peca FROM tbl_peca WHERE referencia = '$referencia' AND fabrica = $this->login_fabrica";
            $query = pg_query($sql);

            if (pg_num_rows($query) == 0) {
                $this->msg_erro = 'PeÁa 2 inv·lida.';
                return false;
            }

            $this->pecas[] = pg_fetch_result($query, 0, 'peca');
        }
        return $this;
    }

    public function setPecas($pecas = array())
    {
        if (!empty($pecas)) {
            $this->pecas = $pecas;
        }
        elseif (!empty($_POST['peca'])) {
            $this->pecas = $_POST['peca'];
            $this->flag_peca = true;
        }
    foreach($this->pecas as $key => $value){
        if(empty($value)) {
            unset($this->pecas[$key]);
        }
    }
        return $this;
    }

    private function setPeriodo()
    {
        if (!empty($_POST["periodo"])) {
            $this->periodo = $_POST["periodo"];
        }
        return $this;
    }

    private function setPosto()
    {
        if (!empty($_POST['posto'])) {
            $this->posto = $_POST['posto'];

            //$arr_posto = explode('-', $_POST['posto']);

            /*if (!empty($arr_posto[0])) {
                $this->is_con();
                $codigo_posto = trim($arr_posto[0]);
                $sql = "SELECT tbl_posto.posto FROM tbl_posto
                        JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
                        AND tbl_posto_fabrica.fabrica = {$this->login_fabrica}
                        WHERE codigo_posto = '{$codigo_posto}'";
                $query = pg_query($sql);

                if (pg_num_rows($query) == 1) {
                    $this->posto = pg_fetch_result($query, 0, 'posto');
                }
            }*/
        }

        return $this;
    }

    private function setCFE($value)
    {
        $this->cfe[] = $value;
    }

    public function setFornecedores($fornecedores = null)
    {
        if (!empty($_POST['fornecedor'])) {
            $this->fornecedores = $_POST['fornecedor'];
        }
        elseif (!empty($fornecedores)) {
            $this->fornecedores = $fornecedores;
        }

        return $this;
    }

    private function setProdutos()
    {
        if (!empty($_POST['produto'])) {
            $this->produtos = $_POST['produto'];
        }

        
        //  Pegar o $_POST['produto_unico'], se tiver valor, faz select para pegar produtos e injetar no $this->produtos
        if (!empty($_POST['produto_unico'])) {
            $ref_fab = implode(",", $_POST['produto_unico']);
            $ref_fab = "'".str_replace(",", "','", $ref_fab)."'";
            
            $this->is_con();
            
            $sql = pg_query("SELECT produto FROM tbl_produto WHERE referencia_fabrica IN ($ref_fab) AND fabrica_i = $this->login_fabrica");
            $res = pg_fetch_all($sql);
            if (count($res) > 0) {
                $cod_prod = [];
                foreach ($res as $key => $value) {
                    $cod_prod[] = $value['produto']; 
                }
                $this->produtos = array_merge($this->produtos, $cod_prod);
            }
        }
        return $this;
    }

    private function setProdutosUnico()
    {

        if (!empty($_POST['produto_unico'])) {
            $this->produtos_unico = $_POST['produto_unico'];
        }

        return $this;
    }

    private function setDataInicialGlobal()
    {

        if (!empty($_POST['data_inicial_global'])) {
            $dt_ini_array = explode("/", $_POST['data_inicial_global']);
            $dt_ini = $dt_ini_array[1]."-".$dt_ini_array[0]."-01";

            $this->data_inicial_global = $dt_ini;
        }

        return $this;
    }

    private function setDataFinalGlobal()
    {

        if (!empty($_POST['data_final_global'])) {
            $dt_fin_array = explode("/", $_POST['data_final_global']);
            $ultimo_dia_mes = date('t', mktime(0,0,0,$dt_fin_array[0],'01',$dt_fin_array[1]));
            $dt_fin = $dt_fin_array[1]."-".$dt_fin_array[0]."-".$ultimo_dia_mes;

            $this->data_final_global = $dt_fin;
        }

        return $this;
    }

    private function setSemPeca()
    {
        if (!empty($_POST['sem_peca'])) {
            $this->sem_peca = $_POST['sem_peca'];
        }

        return $this;
    }
    private function setRevendas()
    {
        if (!empty($_POST['revenda'])) {
            $this->revendas = $_POST['revenda'];
        }

        return $this;
    }

    /**
     * @param array $data Array 'dia|mes|ano' => 'valor'
     */
    public function setDatas($datas = null)
    {

        if (!empty($datas)) {
            $this->datas = $datas;
            return $this;
        }

        $datas = array(
            'dia_01',
            'mes_01',
            'ano_01',
            'dia_02',
            'mes_02',
            'ano_02',
            'dia_03',
            'mes_03',
            'ano_03',
        );

        foreach ($datas as $d) {
            if (!empty($_POST[$d])) {
                list($tipo, $i) = explode('_', $d);
                $this->datas[(int) $i][$tipo] = $_POST[$d];
            }
        }

        return $this;
    }

    public function setRegiao()
    {
        if (!empty($_POST['regiao'])) {
            $this->regiao = (int) $_POST['regiao'];

            $qry_estados_regiao = pg_query("SELECT estados_regiao FROM tbl_regiao WHERE regiao = {$this->regiao}");
            $this->estados_regiao = pg_fetch_result($qry_estados_regiao, 0, 'estados_regiao');

            if (!empty($this->estados_regiao)) {
                $tmp = explode(',', $this->estados_regiao);
                $quoted = array();

                foreach ($tmp as $t) {
                    $quoted[] = "'" . trim($t) . "'";
                }

                $this->estados_regiao = implode(', ', $quoted);

            }
        }

        return $this;
    }

    public function getRegiao()
    {
        return $this->regiao;
    }

    /**
     * Monta a condiÁ„o de fornecedores
     * @return boolean|string
     */
    public function montaCondFornecedores()
    {
        if (empty($this->fornecedores)) {
            return false;
        }

        $cond = " AND (tbl_ns_fornecedor.nome_fornecedor = '";
        $cond.= implode("' OR tbl_ns_fornecedor.nome_fornecedor = '", $this->fornecedores);
        $cond.= "')";

        return $cond;
    }

    private function montaCondProdutos()
    {
        if (empty($this->produtos)) {
            return false;
        }

        if (in_array($this->login_fabrica, array('24','50', '120',201, '175'))) {
            $tbl = '';
        } else {
            $tbl = 'tbl_produto.';
        }

        $cond = " AND {$tbl}produto IN(";
        $cond.= implode(",", $this->produtos);
        $cond.= ")";

        return $cond;
    }

    private function montaCondRevendas()
    {
        if (empty($this->revendas)) {
            return false;
        }

        $tbl = $this->fabrica_nome . '_os_serie.';

        $cond = " AND {$tbl}cnpj IN('";
        $cond.= implode("','", $this->revendas);
        $cond.= "')";

        return $cond;
    }

    /**
     * Monta as condiÁıes com as datas as serem pesquisada
     * @return boolean|array
     */
    public function montaCondDatas()
    {

        if (empty($this->datas)) {
            return false;
        }

        $cond = array();
        $count = count($this->datas);

        if ($count == 1) {
            $cond[] = $this->subArrayData($this->datas);
        } else {
            $datas = $this->matrizData();

            foreach ($datas as $data) {
                $param[1] = $data;
                $cond[] = $this->subArrayData($param);
            }
        }

        return $cond;
    }

    private function matrizData()
    {
        $datas = array();
        $dias = array();
        $meses = array();
        $anos = array();
        $i = 0;

        foreach ($this->datas as $val) {
            if (array_key_exists('dia', $val)) {
                $dias[] = $val['dia'];
            } else {
                $dias[] = '00';
            }

            if (array_key_exists('mes', $val)) {
                $meses[] = $val['mes'];
            } else {
                $meses[] = '00';
            }

            if (array_key_exists('ano', $val)) {
                $anos[] = $val['ano'];
            } else {
                $anos[] = '00';
            }
        }


        foreach ($dias as $d) {
            foreach ($meses as $m) {
                foreach ($anos as $a) {
                    if ($d <> "00") {
                        $datas[$i]['dia'] = $d;
                    }
                    if ($m <> "00") {
                        $datas[$i]['mes'] = $m;
                    }
                    if ($a <> "00") {
                        $datas[$i]['ano'] = $a;
                    }

                    if (count($datas[$i]) > 1) {
                        $i++;
                    }
                }
            }
        }

        return $datas;

    }

    private function subArrayData(array $data)
    {
        $return = '';

        if (array_key_exists('dia', $data[1]) and array_key_exists('mes', $data[1]) and array_key_exists('ano', $data[1])) {
            $strData = $data[1]['ano'] . '-' . $data[1]['mes'] . '-' . $data[1]['dia'];
            $return = " tbl_ns_fornecedor.data_fabricacao = '$strData' ";
        }
        elseif (array_key_exists('dia', $data[1]) and array_key_exists('mes', $data[1])) {
            $return = " ((SELECT EXTRACT(DAY FROM tbl_ns_fornecedor.data_fabricacao)) = '{$data[1]['dia']}' and (SELECT EXTRACT(MONTH FROM tbl_ns_fornecedor.data_fabricacao)) = '{$data[1]['mes']}') ";
        }
        elseif (array_key_exists('dia', $data[1]) and array_key_exists('ano', $data[1])) {
            $return = " ((SELECT EXTRACT(DAY FROM tbl_ns_fornecedor.data_fabricacao)) = '{$data[1]['dia']}' and (SELECT EXTRACT(YEAR FROM tbl_ns_fornecedor.data_fabricacao)) = '{$data[1]['ano']}') ";
        }
        elseif (array_key_exists('mes', $data[1]) and array_key_exists('ano', $data[1])) {
            $return = " ((SELECT EXTRACT(MONTH FROM tbl_ns_fornecedor.data_fabricacao)) = '{$data[1]['mes']}' and (SELECT EXTRACT(YEAR FROM tbl_ns_fornecedor.data_fabricacao)) = '{$data[1]['ano']}') ";
        }

        return $return;

    }

    private function setFaturados(){
        if ($this->familia == "global") {
          $cond_familia = ' AND tbl_produto.familia IN (SELECT familia FROM tbl_familia WHERE fabrica = ' . $this->login_fabrica . ' AND ativo) ';
        } else {
          $cond_familia = " AND tbl_produto.familia = $this->familia ";
        }

        if (!empty($this->revendas)) {
            $cond_f_revendas = "AND tbl_numero_serie.cnpj IN('".implode("','", $this->revendas)."')";
        }else{            
            $cond_f_revendas = "";
        }

        $month23 = 23;
        if  ($this->login_fabrica == 24 && isset($this->diff_mes)) {
            $month23 = $this->diff_mes;
        }

        $sql1 = "
                select count(tbl_numero_serie.serie) as total,
                extract (month from data_venda) as mes,
                extract (year from data_venda) as ano
                into temp tf1
                from tbl_numero_serie
                join tbl_produto using(produto)
                where fabrica = $this->login_fabrica
                and fabrica_i = $this->login_fabrica
                $cond_familia
                $cond_f_revendas
                and data_venda between to_char(('$this->data_inicial'::date - interval '$month23 month'), 'YYYY-MM-DD')::date and '$this->data_final'
                group by mes,ano order by ano,mes
                ";

        $sql2 = "
                select 0 as total,
                extract(month from to_char(('$this->data_inicial'::date - interval '$month23 month'), 'YYYY-MM-DD')::date + s * interval '1 month') as mes,
                extract(year from to_char(('$this->data_inicial'::date - interval '$month23 month'), 'YYYY-MM-DD')::date + s * interval '1 month') as ano
                into temp tf2
                from generate_series(0, $month23) as s
                ";

        $sql4 = "SELECT qtde_produto_produzido as total, cf.mes, cf.ano
                 INTO temp tf1 from tbl_custo_falha cf
                 JOIN tf2 ON tf2.mes = cf.mes AND tf2.ano = cf.ano
                 WHERE cf.fabrica = $this->login_fabrica 
                 AND cf.familia = $this->familia";

        $sql3 = "CREATE TEMP TABLE rf as SELECT mes, ano from tf1 union select mes, ano from tf2 order by ano, mes;
                    ALTER TABLE rf add total text;";

        if ($this->login_fabrica == 120 or $login_fabrica == 201) {
            $query = pg_query($sql2);
            $query = pg_query($sql4);
        } else {
            $query = pg_query($sql1);
            $query = pg_query($sql2);
        }

        $query = pg_query($sql3);

        $begin = pg_query("BEGIN");
        $sql_updates = "update rf set total = tf1.total from tf1 where rf.mes = tf1.mes and rf.ano = tf1.ano;
                        update rf set total = 0 where total is null;";
        $query = pg_query($sql_updates);

        if (!pg_last_error()) {
            $commit = pg_query("COMMIT");
        } else {
            $rollback = pg_query("ROLLBACK");
            return '0';
        }

        $query = pg_query("SELECT total from rf order by ano, mes");

        while ($fetch = pg_fetch_assoc($query)) {
            $this->faturados[] = $fetch['total'];
        }

        if ($this->login_fabrica == 24 && isset($this->diff_mes)) {
            $count_faturados = count($this->faturados);
            if ($count_faturados > $this->diff_mes) {
                //$xremover = $count_faturados - 1;
                //unset($this->faturados[$xremover]);
                unset($this->faturados[0]);

            }
        }

    }

    private function setCFEPerUnit15()
    {
        if (empty($this->cfe)) {
            return false;
        }

        if (empty($this->meses)) {
            return false;
        }

        if (empty($this->arr_os_15M)) {
            return false;
        }

        if (empty($this->arr_total_15M)) {
            return false;
        }

        foreach ($this->arr_os_15M as $key => $value) {
            $tot = $this->arr_total_15M[$key];
            $producao[$value['mes']] = $tot;
        }

        array_pop($producao);
        $producao = array_values($producao);
        $count_p = count($producao);
        $count_c = count($this->cfe);
        $diffk = $count_p - $count_c;

        foreach ($this->cfe as $idx => $value) {
            $producao_sum = 0;
            $start = $idx + $diffk;
            $step = 0;

            for ($i = $start; $i >= 0; --$i) {
                if ($step == 15) {
                    break;
                } else {
                    $step++;
                }
                $producao_sum+= $producao[$i];
            }

            $resdiv = bcdiv($this->cfe[$idx], $producao_sum, 2);

            if (empty($resdiv)) {
                $resdiv = 0;
            }

            $this->cfe_per_unit_15[] = $resdiv;
        }

    }

    private function setArrOS15M()
    {
        $month = $this->setIndexesArray15();
        $tmp_arr_os_15M = array();

        if (!empty($this->arr_os)) {
            $tmp_arr_os_15M = $this->arr_os;
        }

        $arr_result = $this->producao($month);        

        foreach ($arr_result as $k => $fetch) {
            $mes = $fetch["mes"];
            $ano = $fetch["ano"];

            $this->arr_os[$k] = array("mes" => sprintf("%02d", $mes) . '/' . $ano, "os" => array());
            $this->popOS($ano, $mes, $k, false);
        }

        $this->arr_os_15M = $this->arr_os;
        $this->arr_os = $tmp_arr_os_15M;
        unset($tmp_arr_os_15M);

    }

    private function setArrTotal15M()
    {
        $month = $this->setIndexesArray15();
        $arr_result = $this->producao($month);

        foreach ($arr_result as $k => $fetch) {
            $this->arr_total_15M[$k] = $fetch['total'];
        }
    }

    private function setIndexesArray15()
    {
        $data_obj = new DateTime($this->data_inicial);
        $sub = $data_obj->sub(new DateInterval('P38M'));
        $data_obj2 = new DateTime($sub->format('Y-m-d'));
        $data_corte = new DateTime('2010-01-01');

        if ($data_obj2 < $data_corte) {
            $date1 = date(strtotime($data_corte->format('Y-m-d')));
            $date2 = date(strtotime($this->data_inicial));

            $difference = $date2 - $date1;
            $months = floor($difference / 86400 / 30 );

            return (string) $months;
        } else {
            return '39';
        }
    }

    private function setLoginFabrica(){
        global $login_fabrica;

        if (empty($login_fabrica)) {
            echo '<meta http-equiv="Refresh" content="0 ; url=http://www.telecontrol.com.br" />';
            exit;
        }

        $this->login_fabrica = $login_fabrica;

        $this->is_con();
        $qry = pg_query("SELECT LOWER(TRIM(nome)) AS nome FROM tbl_fabrica WHERE fabrica = {$this->login_fabrica}");

        if (pg_num_rows($qry) == 0) {
            echo '<meta http-equiv="Refresh" content="0 ; url=http://www.telecontrol.com.br" />';
            exit;
        }

        $this->fabrica_nome = pg_fetch_result($qry, 0, 'nome');

        return $this;
    }

    private function setLoginAdmin()
    {
        global $login_admin;


        $this->login_admin = $login_admin;
        return $this;
    }


    /**
     * Inicializa os atributos necess·rios para a execuÁ„o do programa
     */
    private function bootstrap()
    {
        $this->msg_erro = '';
        $this->result_view = '';

        $this->setLoginFabrica()
             ->setLoginAdmin()
             ->setMeses()
             ->setFamilia()
             ->setRegiao()
             ->setIndexIRC()
             ->setDataInicial()
             ->setDataFinal()
             ->setPeca01()
             ->setPeca02()
             ->setPecas()
             ->setPeriodo()
             ->setPosto()
             ->setFornecedores()
             ->setProdutos()
             ->setProdutosUnico()
             ->setDataInicialGlobal()
             ->setDataFinalGlobal()
             ->setSemPeca()
             ->setRevendas()
             ->setDatas();

    }

    /**
     * Verifica se existe conex„o com o banco de dados
     */
    private function is_con()
    {
        global $con;
        if (!is_resource($con)) {
            echo 'ERRO: conex„o com banco de dados!';
            exit;
        }
    }

    /**
     * Verifica se est· submetendo dados
     */
    private function isRequest()
    {
        if (!empty($_POST['btn_acao'])) {
            if ($_POST['btn_acao'] == "Consultar" and $this->validate()) {
                return true;
            }
        }

        return false;
    }

    /**
     * ValidaÁ„o de campos obrigatÛrios
     */
    private function validate()
    {
        $campos = array();

        if (empty($_POST['ano_pesquisa'])) {
            $campos[] = 'Ano';
        }

        if (empty($_POST['mes_pesquisa'])) {
            $campos[] = 'MÍs';
        }

        if (empty($_POST['familia'])) {
            $campos[] = 'FamÌlia';
        }

        if (empty($_POST['meses'])) {
            $campos[] = 'Qtde Meses';
        }

        if (!empty($_POST['data_inicial_global']) && !empty($_POST['data_final_global'])) {
            $xdt_ini_array = explode("/", $_POST['data_inicial_global']);
            $xdt_ini = $xdt_ini_array[1]."-".$xdt_ini_array[0]."-01";

            $xdt_fin_array = explode("/", $_POST['data_final_global']);
            $xultimo_dia_mes = date('t', mktime(0,0,0,$xdt_fin_array[0],'01',$xdt_fin_array[1]));
            $xdt_fin = $xdt_fin_array[1]."-".$xdt_fin_array[0]."-".$xultimo_dia_mes;

            $xa1 = ($xdt_fin_array[1] - $xdt_ini_array[1])*12;
            $xm1 = ($xdt_fin_array[0] - $xdt_ini_array[0])+1;
            $xm3 = ($xm1 + $xa1); 

            if ($xm3 > 24) {
                $campos[] = "Intervalo entre as data superior ao limite";
            }

        }

        if (!empty($campos)) {
            $this->msg_erro = 'Verifique os seguintes campos: ' . implode(', ', $campos);
            return false;
        }

        if ((!empty($this->fornecedores) or !empty($this->datas)) and empty($this->pecas)) {
            $this->msg_erro = 'Para pesquisar por fornecedor/datas È preciso selecionar pela menos uma peÁa';
            return false;
        }
        elseif (!empty($this->datas)) {
            foreach ($this->datas as $valid) {
                if (count($valid) < 2) {
                    $this->msg_erro = 'Para pesquisar por datas È necess·rio selecionar pelo menos 2 par‚metros de dia, mÍs ou ano.';
                    return false;
                }
            }
        }

        if (!empty($this->msg_erro)) {
            return false;
        }

        return true;

    }

    private function preparaTabelaTemp()
    {
        $this->is_con();

        $login_admin = $this->login_admin;

        if (in_array($this->login_fabrica, array('24','50','120','175','201'))) {
            $tbl = '';
        }else{
            $tbl = 'tbl_os.';
        }

        if($this->sem_peca){
            $condSemPeca = ' AND '. $tbl . 'defeito_constatado = ' . $this->sem_peca;
        }

        $condPosto = '';

        if (!empty($this->posto)) {
            $condPosto = ' AND ' . $tbl . 'posto in(' . implode(',',$this->posto).')';
        }

        $condProdutos = $this->montaCondProdutos();

        if (!empty($this->pecas)) {
            $cond = implode(', ', $this->pecas);
            $condFornecedores = $this->montaCondFornecedores();
            $condProdutos = $this->montaCondProdutos();
            $condDatas = $this->montaCondDatas();

            $cond_datas = '';

            if (!empty($condDatas)) {
                $cond_datas = ' AND (';
                $cond_datas.= implode('OR', $condDatas);
                $cond_datas.= ')';
            }

            $prepare = pg_prepare("tmp_peca_serie","select os,os_produto,peca into temp peca_serie_$login_admin from tbl_os_item join tbl_os_produto using(os_produto) where fabrica_i = $1 and peca in ($cond); " );
            
            if (!empty($condFornecedores) or !empty($condDatas)) {
                $prepare = pg_prepare("tmp_fornecedor_serie","select numero_serie,peca into temp fornecedor_serie_$login_admin from tbl_ns_fornecedor  WHERE fabrica = $1 AND peca in ($cond) $condFornecedores $cond_datas" );
                
            }
        }

        $return = array(
                'os_serie' => false,
                'numero_serie1' => false,
                'numero_serie2' => false,
            );

        if ($this->login_fabrica == '50') {
            
            $this->temp_tbls = array(
                'os_serie' => 'colormaq_os_serie',
                'numero_serie1' => 'colormaq_numero_serie1',
                'numero_serie2' => 'colormaq_numero_serie2',
                'numero_serie' => 'colormaq_numero_serie',
            );

            if (!empty($condProdutos) or !empty($condSemPeca) or !empty($condPosto)) {
                $sql = "SELECT *  
                            INTO TEMP os_serie_$login_admin
                            FROM {$this->temp_tbls['os_serie']}
                            WHERE {$this->temp_tbls['os_serie']}.familia = $this->familia
                            $condProdutos 
                            $condSemPeca 
                            $condPosto;

                        create index os_serie_os_$login_admin on os_serie_$login_admin(os);
                        create index os_serie_sp_$login_admin on os_serie_$login_admin(serie,produto);
                        create index os_serie_familia_$login_admin on os_serie_$login_admin(familia);

                ";
                $qry = pg_query($sql);
                $this->temp_tbls['os_serie'] = 'os_serie_' . $login_admin;
            }

            if (!empty($condProdutos)) {
                $sql = "SELECT numero_serie,
                               serie,
                               produto,
                               data_fabricacao
                            INTO TEMP numero_serie1_$login_admin
                            FROM {$this->temp_tbls['numero_serie1']}
                            WHERE {$this->temp_tbls['numero_serie1']}.familia = $this->familia
                            $condProdutos";
                $qry = pg_query($sql);
                $this->temp_tbls['numero_serie1'] = 'numero_serie1_' . $login_admin;

                $sql = "SELECT numero_serie,
                               substr(serie,1,length(serie) -1) as serie,
                               produto,
                               data_fabricacao
                            INTO TEMP numero_serie2_$login_admin
                            FROM {$this->temp_tbls['numero_serie2']}
                            WHERE {$this->temp_tbls['numero_serie2']}.familia = $this->familia
                            $condProdutos";
                $qry = pg_query($sql);
                $this->temp_tbls['numero_serie2'] = 'numero_serie2_' . $login_admin;

                $this->temp_tbls['numero_serie'] = 'numero_serie_' . $login_admin;
            }

        } elseif (in_array($this->login_fabrica, array('24', '120', '175','201'))) {
            $this->temp_tbls = array(
                'os_serie' => $this->fabrica_nome . '_os_serie',
                'numero_serie' => $this->fabrica_nome . '_numero_serie',
            );
            if($this->login_fabrica == 24) {
                $matriz_filial = $_POST["matriz_filial"];
                $this->matriz_filial = $matriz_filial;
                if($matriz_filial == '02'){
                    $cond_matriz_filial = " AND ".$this->fabrica_nome."_os_serie.matriz IS TRUE ";
                }else{
                    $cond_matriz_filial = " AND ".$this->fabrica_nome."_os_serie.matriz IS FALSE ";
                }
            }
            if (!empty($condProdutos) or !empty($condSemPeca) or !empty($condPosto)) {
                $sql = "SELECT *  
                            INTO TEMP os_serie_$login_admin
                            FROM {$this->temp_tbls['os_serie']}
                            WHERE {$this->temp_tbls['os_serie']}.familia = $this->familia
                            $condProdutos 
                            $condSemPeca 
                            $cond_matriz_filial
                            $condPosto;

                        create index os_serie_os_$login_admin on os_serie_$login_admin(os);
                        create index os_serie_sp_$login_admin on os_serie_$login_admin(serie,produto);
                        create index os_serie_familia_$login_admin on os_serie_$login_admin(familia);

                ";
                $qry = pg_query($sql);
                $this->temp_tbls['os_serie'] = 'os_serie_' . $login_admin;
            }
        } else {

            $prepare = pg_prepare("tmp_os_serie","SELECT os, data_abertura, data_nf, serie, tbl_os.produto,fabrica INTO TEMP os_serie_$login_admin from tbl_os join tbl_produto on tbl_os.produto = tbl_produto.produto where fabrica = $1 and familia = $2 and fabrica_i = $1 $condProdutos $condSemPeca $condPosto");
            $prepare = pg_prepare("tmp_numero_serie1","SELECT numero_serie, serie, produto,data_fabricacao INTO TEMP numero_serie1_$login_admin from tbl_numero_serie join tbl_produto using(produto) where fabrica = $1 and familia = $2 and fabrica_i = $1 $condProdutos;  " );
            $prepare = pg_prepare("tmp_numero_serie2"," SELECT numero_serie, substr(serie,1,length(serie) -1) as serie, produto,data_fabricacao INTO TEMP numero_serie2_$login_admin from tbl_numero_serie join tbl_produto using(produto) where fabrica = $1 and familia = $2 and fabrica_i = $1 and data_fabricacao between '2013-07-25' and '2013-09-13' $condProdutos;" );

            $return = array(
                'os_serie' => true,
                'numero_serie1' => true,
                'numero_serie2' => true,
            );

            $this->temp_tbls = array(
                'os_serie' => 'os_serie_' . $login_admin,
                'numero_serie1' => 'numero_serie1_' . $login_admin,
                'numero_serie2' => 'numero_serie2_' . $login_admin,
                'numero_serie' => 'numero_serie_' . $login_admin,
            );
        }

        return $return;

    }

    /**
     * Prepare das consultas que ser„o executadas para trazer
     *   o n˙mero de OSs mÍs a mÍs
     */
    private function prepareStatements()
    {
        $this->is_con();

        $login_admin = $this->login_admin;
        $prepare = pg_prepare("ultimo_dia_do_mes", "select to_char(($1::date + interval '1 month') - interval '1 day', 'YYYY-MM-DD')::date as ultimo_dia");

        $join_regiao = '';
        $cond_regiao = '';
        if (!empty($this->estados_regiao)) {
            $join_regiao = ' JOIN tbl_posto USING(posto) ';
            $cond_regiao = " AND tbl_posto.estado IN ({$this->estados_regiao}) ";
        }

        if (!empty($this->revendas)) {
            $cond_revendas = "AND {$this->temp_tbls['os_serie']}.cnpj IN ('".implode("','", $this->revendas)."')";
        }

        $cond_conversor = "";

        if (!empty($_POST["desconsidera_conversor"])) {
            $cond_conversor = " AND defeito_constatado <> 23118 AND solucao_os <> 4504 ";
        }

        if($this->login_fabrica == 24){
            $matriz_filial = $_POST["matriz_filial"];
            $this->matriz_filial = $matriz_filial;
        }else{
             $this->matriz_filial = '';
        }
        

        if (empty($this->pecas)) {
            if(empty($this->produtos)) {
                if($this->familia == "global"){
                    $cond_familia_os = " AND {$this->temp_tbls['os_serie']}.familia IN (SELECT familia FROM tbl_familia WHERE fabrica = " . $this->login_fabrica . " AND ativo) ";
                } else {
                    $cond_familia_os = " AND {$this->temp_tbls['os_serie']}.familia = $this->familia ";
                }
            }
            if ($this->login_fabrica == 24) {
            
                $matriz_filial = $_POST["matriz_filial"];
                $this->matriz_filial = $matriz_filial;
                if($matriz_filial == '02'){
                    $cond_matriz_filial = " AND matriz IS TRUE ";
                }else{
                    $cond_matriz_filial = " AND matriz IS FALSE ";
                }
            
                $prepare = pg_prepare(
                    "oss",
                    "select distinct(os) as os,data_nf, data_abertura, data_fabricacao
                    into temp temp_oss from {$this->temp_tbls['os_serie']}
                    join {$this->temp_tbls['numero_serie']} USING(serie,produto)
                    $join_regiao
                    where data_fabricacao between $1 and $2
                    and data_nf >= data_fabricacao
                    $cond_familia_os
                    $cond_regiao
                    $cond_revendas
                    $cond_conversor
                    $cond_matriz_filial
                    order by data_nf"
                );
            } else {
                $prepare = pg_prepare("total_os", "select count(distinct(os)) as total from {$this->temp_tbls['os_serie']} join {$this->temp_tbls['numero_serie']} USING(serie,produto) $join_regiao where data_fabricacao between $1 and $2 and data_abertura between $3 and $4 $cond_familia_os $cond_regiao $cond_revendas");
            }
            
        } else {
            $cond = implode(', ', $this->pecas);

            $condFornecedores = $this->montaCondFornecedores();
            $condDatas = $this->montaCondDatas();
            if($this->familia == "global"){
                $cond_familia_os = " AND {$this->temp_tbls['os_serie']}.familia IN (SELECT familia FROM tbl_familia WHERE fabrica = " . $this->login_fabrica . " AND ativo) ";
            } else {
                $cond_familia_os = " AND {$this->temp_tbls['os_serie']}.familia = $this->familia ";
            }
            if (!empty($condFornecedores) or !empty($condDatas)) {
                $joinNSFornecedor = " JOIN fornecedor_serie_$login_admin ON fornecedor_serie_$login_admin.numero_serie = {$this->temp_tbls['numero_serie']}.numero_serie AND peca_serie_$login_admin.peca = fornecedor_serie_$login_admin.peca   ";
            } else {
                $joinNSFornecedor = '';
            }

            if ($this->login_fabrica == 24) {
                $matriz_filial = $_POST["matriz_filial"];
                $this->matriz_filial = $matriz_filial;
                if($matriz_filial == '02'){
                    $cond_matriz_filial = " AND ".$this->fabrica_nome."_os_serie.matriz IS TRUE ";
                }else{
                    $cond_matriz_filial = " AND ".$this->fabrica_nome."_os_serie.matriz IS FALSE ";
                }
                $prepare = pg_prepare(
                    "oss",
                    "select distinct(os) as os, data_nf, data_abertura, data_fabricacao
                    into temp temp_oss from {$this->temp_tbls['os_serie']}
                    join {$this->temp_tbls['numero_serie']} using(serie,produto)
                    JOIN peca_serie_$login_admin USING(os)
                    $joinNSFornecedor
                    where data_fabricacao between $1 and $2
                    and data_nf >= data_fabricacao
                    $cond_conversor
                    $cond_matriz_filial
                    order by data_nf"
                );
            } else {
                $prepare = pg_prepare("total_os", "select count(distinct({$this->temp_tbls['os_serie']}.os)) as total from {$this->temp_tbls['os_serie']} join {$this->temp_tbls['numero_serie']} using(serie,produto) JOIN peca_serie_$login_admin USING(os) $join_regiao $joinNSFornecedor where data_fabricacao between $1 and $2 and data_abertura between $3 and $4 $cond_familia_os $cond_regiao $cond_revendas");
            }
        }

    }

    /**
     * Executa consultas previamente preparadas
     *
     * @param string $query nome da prepared statement
     * @param array $params par‚metros da consulta
     *
     * @return array resultado da query ou string '0' se nada
     *
     */
    private function executePreparedStatements($query, $params)
    {
        //echo "iniciando executePreparedStatements<br>";
        if (!is_array($params)) {
            echo 'Erro: tipo n„o suportado.';
            exit;
        }
        $x = pg_execute($query, $params);

        if (pg_num_rows($x)) {
            return pg_fetch_all($x);
        } else {
            return '0';
        }

    }

    /**
     *
     * @param array $array Array a ser reduzido
     * @param int $indices N˙mero de Ìndices a serem mantidos
     * @param int $ordem se 0, retira os elementos do inÌcio do array
     *     qualquer outro valor, retira do final
     *
     */
    private function arrayReduce($array, $indices, $ordem = 1)
    {        
        if (!is_array($array)) {
            return false;
        }

        if (!is_int($indices)) {
            return $array;
        }

        if (!is_int($ordem)) {
            $ordem = 1;
        }


        if ($ordem == 0) {
            $php_function = 'array_shift';
        } else {
            $php_function = 'array_pop';
        }

        $count = count($array);
        $end = $count - $indices;

        if ($end < 0) {
            return false;
        }

        for ($i = 0; $i < $end; $i++) {
            $php_function($array);
        }

        return $array;

    }

    /**
     * ObtÈm o n˙mero de produtos produzidos nos ˙ltimos X meses
     *   usando como base a tbl_custo_falha
     *
     * @param string $month par‚metro usado no select - padrao '23'
     * @return array resultado ou string '0' se nada
     *
     */
    private function producao($month = null)
    {
        
        if (empty($month)) {
            $month = '23';
        }

        $this->is_con();

        if (empty($this->familia)) {
            die('Erro interno.');
        }

        if(!empty($this->revendas)){
            $revendas = $this->revendas;
            $condProdRevenda = "AND tbl_numero_serie.cnpj IN ('".implode("','", $revendas)."')";
        }

        if($this->familia == "global"){
            $cond_familia_os = " tbl_produto.familia IN (SELECT familia FROM tbl_familia WHERE fabrica = " . $this->login_fabrica . " AND ativo) ";
        } else {
            $cond_familia_os = " tbl_produto.familia = $this->familia ";
        }

        $between1 = "and data_fabricacao between to_char(('$this->data_inicial'::date - interval '$month month'), 'YYYY-MM-DD')::date and '$this->data_final'";
        $extract1 = "extract(month from to_char(('$this->data_inicial'::date - interval '$month month'), 'YYYY-MM-DD')::date + s * interval '1 month') as mes,";
        $extract2 = "extract(year from to_char(('$this->data_inicial'::date - interval '$month month'), 'YYYY-MM-DD')::date + s * interval '1 month') as ano,";

        if (!empty($this->data_inicial_global) && !empty($this->data_final_global)) {
            $data_explodida = explode("-", $this->data_final_global);
            $nova_data_ini = $data_explodida[0].'-'.$data_explodida[1].'-01';
            //$nova_data_ini = date('Y-m-d', strtotime("+1 month", strtotime($nova_data_ini)));
            //$nova_data_fin = date('Y-m-d', strtotime("+1 month", strtotime($this->data_final_global)));

            $between1 = "and data_fabricacao between to_char(('$nova_data_ini'::date - interval '$month month'), 'YYYY-MM-DD')::date and '$this->data_final_global'";
            $extract1 = "extract(month from to_char(('$nova_data_ini'::date - interval '$month month'), 'YYYY-MM-DD')::date + s * interval '1 month') as mes,";
            $extract2 = "extract(year from to_char(('$nova_data_ini'::date - interval '$month month'), 'YYYY-MM-DD')::date + s * interval '1 month') as ano,";

            /*$between1 = "and data_fabricacao between '$this->data_inicial_global 00:00' and '$this->data_final_global 23:59'";
            $extract1 = "extract(month from to_char(('$this->data_inicial_global'::date), 'YYYY-MM-DD')::date + s * interval '1 month') as mes,";
            $extract2 = "extract(year from to_char(('$this->data_inicial_global'::date), 'YYYY-MM-DD')::date + s * interval '1 month') as ano,";*/
        }

        $cond_produto = '';
        $matriz_filial_filtro = '';

        if ($this->login_fabrica == 24) {
            if ($this->produtos) {
                $p = implode(', ', $this->produtos);
                $cond_produto = ' AND tbl_numero_serie.produto IN (' . $p . ') ';
            }

            if ($_POST["matriz_filial"] == 02) {
                $matriz_filial_filtro = " AND substr(tbl_numero_serie.serie,length(tbl_numero_serie.serie) - 1, 2) = '02' ";
            } else {
                $matriz_filial_filtro = " AND substr(tbl_numero_serie.serie,length(tbl_numero_serie.serie) - 1, 2) <> '02' ";
            }
        }

        $sql1 = "
                select count(tbl_numero_serie.serie) as total,
                extract (month from data_fabricacao) as mes,
                extract (year from data_fabricacao) as ano,
                0 as cfe
                into temp t1
                from tbl_numero_serie
                join tbl_produto using(produto)
                where $cond_familia_os
                and fabrica = $this->login_fabrica
                and fabrica_i = $this->login_fabrica          
                $between1
                $cond_produto
                $matriz_filial_filtro
                $condProdRevenda
                group by mes,ano order by ano,mes
                ";

        $sql2 = "
                select 0 as total,
                $extract1
                $extract2
                0 as cfe
                into temp t2
                from generate_series(0, $month) as s
                ";

        if (!empty($this->regiao)) {
            $cond_regiao = " AND cf.regiao = {$this->regiao} ";
        } else {
            $cond_regiao = " AND cf.regiao IS NULL ";
        }

        if ($this->login_fabrica != 24) {
            if (empty($this->produtos)) {
                $c_qtde_produto_produzido = 'qtde_produto_produzido';
                $c_cfe = 'cf.cfe';
                $cond_produto = ' AND cf.produto IS NULL ';
            } else {
                $c_qtde_produto_produzido = 'SUM(qtde_produto_produzido)';
                $c_cfe ='SUM(cf.cfe) AS cfe';
                $p = implode(', ', $this->produtos);
                $cond_produto = ' AND cf.produto IN (' . $p . ') GROUP BY cf.mes, cf.ano ';
            }
        }


        if ($this->familia == "global") {
            $cond_familia = " AND cf.familia IN (SELECT familia FROM tbl_familia WHERE fabrica = " . $this->login_fabrica . " AND ativo) ";
        } else {
            $cond_familia = " AND cf.familia = $this->familia ";
        }

        if ($this->login_fabrica != 24) {
            $sql4 = "SELECT $c_qtde_produto_produzido as total, cf.mes, cf.ano, $c_cfe
                     INTO temp t1 from tbl_custo_falha cf
                     JOIN t2 ON t2.mes = cf.mes AND t2.ano = cf.ano
                     WHERE cf.fabrica = $this->login_fabrica $cond_familia $cond_regiao $cond_produto ";
        } 

        $sql3 = "CREATE TEMP TABLE r as SELECT mes, ano from t1 union select mes, ano from t2 order by ano, mes;
                    ALTER TABLE r add total text;
                    ALTER TABLE r add cfe text";

        $query = pg_query($sql2);

        if ($this->login_fabrica == 120 or $this->login_fabrica == 201 || $this->login_fabrica == 24) {
            $query = pg_query($sql1);
        } else {
            $query = (!empty($this->revendas)) ? pg_query($sql1) : pg_query($sql4);        
        }

        $query = pg_query($sql3);        

        $update_from = 't1';

        if ($this->familia == 'global') {
            $query = pg_query("SELECT SUM(total) AS total, SUM(cfe) AS cfe, mes, ano INTO TEMP s FROM t1 GROUP BY mes, ano ORDER BY ano, mes");
            $update_from = 's';
        }

        $begin = pg_query("BEGIN");
        $sql_updates = "update r set total = {$update_from}.total, cfe = {$update_from}.cfe
                        from $update_from where r.mes = {$update_from}.mes and r.ano = {$update_from}.ano;
                        update r set total = 0 where total is null;
                        update r set cfe = 0 where cfe is null";

        $query = pg_query($sql_updates);

        if (!pg_last_error()) {
            $commit = pg_query("COMMIT");
        } else {
            $rollback = pg_query("ROLLBACK");
            return '0';
        }

        $query = pg_query("SELECT * from r order by ano, mes");

        $drop = pg_query("DROP TABLE t1; DROP TABLE t2; DROP TABLE r");

        if ($this->familia == 'global') {
            $drop = pg_query("DROP TABLE s");
        }

        if (pg_num_rows($query) > 0) {
            return pg_fetch_all($query);
        } else {
            return '0';
        }
    }

    /**
     * Popula arrays populacao
     *
     * @param int $m qtde de meses [1, 4, 6 ou 15]
     * @param int $curr indÌce atual de $this->arr_total_15M
     *
     */
    private function populacao($m, $curr)
    {
        if ($this->login_fabrica == 24) {
            if (isset($this->diff_mes)) {
                if ($this->diff_mes < 4) {
                    $possiveis = array(1);
                } else if ($this->diff_mes < 6) {
                    $possiveis = array(1, 4);
                } else if ($this->diff_mes < 15) {
                    $possiveis = array(1, 4, 6);    
                } else {
                    $possiveis = array(1, 4, 6, 15);
                }
            } else {
                $possiveis = array(1, 4, 6, 15);
            }
        } else {
             $possiveis = range(1, 15);
        }
        
        if (!in_array($m, $possiveis)) {
            echo 'Erro: par‚metro inv·lido - ' , $m , '!';
            exit;
        }

        $count = count($this->arr_total_15M);
        $curr = ($count - 24) + $curr;

        $mpopulacao = 'populacao_' . $m . 'M';

        $this->pop_tmp[$m] = 0;

        if ($m == 1) {
            $this->pop_tmp[$m] = $this->arr_total_15M[$curr - 1];
        } else {

            $ctl = 0;
            $s = $curr - 1;
            for ($i = $s; $i >= 0; $i--) {

                if ($ctl == $m) {
                    break;
                }

                $this->pop_tmp[$m]+= $this->arr_total_15M[$i];

                $ctl++;

            }

        }

        array_push($this->$mpopulacao, $this->pop_tmp[$m]);

    }

    /**
     * Monta populaÁ„o de OSs lanÁadas nos meses anteriores
     *
     *   Quando 1M pega a "soma" do mÍs anterior, nos outros casos, quando (k + 1) >= N
     *     retrocede N meses - em cada mÍs que retrocede, aumenta um Ìndice atÈ
     *     chegar no Ìndice N.
     *
     * @param int $m qtde de meses [1, 4, 6 ou 15]
     * @param int $curr index atual do array $this->arr_os_15M
     *
     */
    private function populacaoPopOS($m, $curr)
    {
        if ($this->login_fabrica == 24) {
            if (isset($this->diff_mes)) {
                if ($this->diff_mes < 4) {
                    $possiveis = array(1);
                } else if ($this->diff_mes < 6) {
                    $possiveis = array(1, 4);
                } else if ($this->diff_mes < 15) {
                    $possiveis = array(1, 4, 6);    
                } else {
                    $possiveis = array(1, 4, 6, 15);
                }
            } else {
                $possiveis = array(1, 4, 6, 15);
            }
        } else {
            $possiveis = range(1, 15);
        }

        if (!in_array($m, $possiveis)) {
            echo 'Erro: par‚metro inv·lido - ' , $m , '!';
            exit;
        }

        $count = count($this->arr_os_15M);
        $curr = ($count - 24) + $curr;

        $os = 'os_' . $m . 'M';

        $this->pop_tmp[$m] = 0;

        if ($m == 1) {
            $this->pop_tmp[$m] = $this->arr_os_15M[$curr - 1]["os"][0];
        } else {
            /**
             *
             * volta um indice no array - soma um valor
             * volta +um indice - soma um valor +1
             * para cada indice que retrocede soma +1
             *
             */
            $ctl = 0;
            $s = $curr - 1;
            for ($i = $s; $i >= 0; $i--) {

                if ($ctl == $m) {
                    break;
                }

                for ($j = 0; $j <= $ctl; $j++) {
                    $this->pop_tmp[$m]+= $this->arr_os_15M[$i]["os"][$j];
                }

                $ctl++;

            }
        }

        array_push($this->$os, $this->pop_tmp[$m]);

    }

    /**
     * Quantas OS foram lanÁadas nos N meses apÛs a fabricaÁ„o do produto
     *
     * @param int $ano
     * @param int $mes
     * @param int $idx index atual do array $this->arr_os
     *
     */
    private function popOS($ano, $mes, $idx, $break = true)
    {
        if (empty($this->meses)) {
            return 0;
        }

        $mes_loop = $mes + 1;
        $ano_loop = $ano;

        if ($this->login_fabrica == 24) {
            $last = (int) $this->meses - 1;

            for ($i = 1; $i <= $this->meses; $i++) {
                $data_fabricacao_inicio = $ano . '-' . sprintf("%02d", $mes) . '-01';
                $x = $this->executePreparedStatements("ultimo_dia_do_mes", array($data_fabricacao_inicio));

                $data_fabricacao_final = $x[0]["ultimo_dia"];

                if ($mes_loop > 12) {
                    $ano_loop+= 1;
                    $mes_loop = 1;
                }

                if ($i == 1) {
                    $period = 'P2M';
                } else {
                    $period = 'P1M';
                }

                $data_abertura_inicio = $ano_loop . '-' . sprintf("%02d", $mes_loop) . '-01';

                $data1 = new DateTime($data_abertura_inicio);
                $data2 = new DateTime($this->data_inicial);
                $data2->add(new DateInterval($period));

                if ($data1 == $data2) {
                    $q_drop_temp_oss = pg_query("DROP TABLE IF EXISTS temp_oss");
                    $q_drop_temp_total_os = pg_query("DROP TABLE IF EXISTS temp_total_os");
                    break;
                }
                elseif ($idx == 23 and $i == 2 and true === $break) {
                    $q_drop_temp_oss = pg_query("DROP TABLE IF EXISTS temp_oss");
                    $q_drop_temp_total_os = pg_query("DROP TABLE IF EXISTS temp_total_os");
                    break;
                }

                $x = $this->executePreparedStatements("ultimo_dia_do_mes", array($data_abertura_inicio));                
                $data_abertura_final = $x[0]["ultimo_dia"];

                if ($i == 1) {
                    $x_oss = $this->executePreparedStatements("oss", array($data_fabricacao_inicio, $data_fabricacao_final));

                    $sqlTotal = "SELECT count(os) as total, fn_qtos_meses_entre(data_fabricacao, data_abertura) as meses into temp temp_total_os from temp_oss group by meses order by meses";
                    $qryTotal = pg_query($sqlTotal);                    
                }

                $sqlTotalOs = "SELECT total FROM temp_total_os WHERE meses = $i";
                $qryTotalOs = pg_query($sqlTotalOs);
                
                if (pg_num_rows($qryTotalOs) == 0) {
                    $total_os = 0;                
                } else {
                    $total_os = pg_fetch_result($qryTotalOs, 0, 'total');
                }

                if ($i == $last) {
                    $q_drop_temp_oss = pg_query("DROP TABLE IF EXISTS temp_oss");
                    $q_drop_temp_total_os = pg_query("DROP TABLE IF EXISTS temp_total_os");
                }

                array_push($this->arr_os[$idx]["os"], $total_os);

                $mes_loop++;

            }
        } else {

            for ($i = 1; $i <= $this->meses; $i++) {
                $data_fabricacao_inicio = $ano . '-' . sprintf("%02d", $mes) . '-01';
                $x = $this->executePreparedStatements("ultimo_dia_do_mes", array($data_fabricacao_inicio));
                $data_fabricacao_final = $x[0]["ultimo_dia"];


                if ($mes_loop > 12) {
                    $ano_loop+= 1;
                    $mes_loop = 1;
                }

                if ($i == 1) {
                    $period = 'P2M';
                } else {
                    $period = 'P1M';
                }

                $data_abertura_inicio = $ano_loop . '-' . sprintf("%02d", $mes_loop) . '-01';

                $data1 = new DateTime($data_abertura_inicio);
                $data2 = new DateTime($this->data_inicial);
                $data2->add(new DateInterval($period));

                if ($data1 == $data2) {
                    break;
                }
                elseif ($idx == 23 and $i == 2 and true === $break) {
                    break;
                }

                $x = $this->executePreparedStatements("ultimo_dia_do_mes", array($data_abertura_inicio));
                $data_abertura_final = $x[0]["ultimo_dia"];

                $x_os = $this->executePreparedStatements("total_os", array( $data_fabricacao_inicio, $data_fabricacao_final, $data_abertura_inicio, $data_abertura_final));

                if (is_array($x_os)) {
                    $total_os = $x_os[0]["total"];
                } else {
                    $total_os = $x_os;
                }

                array_push($this->arr_os[$idx]["os"], $total_os);

                $mes_loop++;

            }
        }

    }

    /**
     * Ordena chaves do Araay
     */
    private function ordenaArray($array)
    {
        $novo_array = [];
        for ($i=0; $i < count($array); $i++) { 
            $novo_array[] = $array[$i+1];
        }

        return $novo_array;
    }

    /**
     * Preenche os arrays populacao_{1-15}M e os_{1-15}M
     */
    private function irc($m, $idx)
    {
        if ($this->login_fabrica == 24) {
            if (isset($this->diff_mes)) {
                if ($this->diff_mes < 4) {
                    $possiveis = array(1);
                } else if ($this->diff_mes < 6) {
                    $possiveis = array(1, 4);
                } else if ($this->diff_mes < 15) {
                    $possiveis = array(1, 4, 6);    
                } else {
                    $possiveis = array(1, 4, 6, 15);
                }
            } else {
                $possiveis = array(1, 4, 6, 15);
            }
        } else {
            $possiveis = range(1, 15);
        }

        if (!in_array($m, $possiveis)) {
            echo 'Erro: par‚metro inv·lido - ' , $m , '!';
            exit;
        }

        $irc = 'irc_' . $m . 'M';

        switch ($m) {
            case 1:
                $populacao = $this->populacao_1M[$idx];
                $os = $this->os_1M[$idx];
                break;
            case 2:
                $populacao = $this->populacao_2M[$idx];
                $os = $this->os_2M[$idx];
                break;
            case 3:
                $populacao = $this->populacao_3M[$idx];
                $os = $this->os_3M[$idx];
                break;
            case 4:
                $populacao = $this->populacao_4M[$idx];
                $os = $this->os_4M[$idx];
                break;
            case 5:
                $populacao = $this->populacao_5M[$idx];
                $os = $this->os_5M[$idx];
                break;
            case 6:
                $populacao = $this->populacao_6M[$idx];
                $os = $this->os_6M[$idx];
                break;
            case 7:
                $populacao = $this->populacao_7M[$idx];
                $os = $this->os_7M[$idx];
                break;
            case 8:
                $populacao = $this->populacao_8M[$idx];
                $os = $this->os_8M[$idx];
                break;
            case 9:
                $populacao = $this->populacao_9M[$idx];
                $os = $this->os_9M[$idx];
                break;
            case 10:
                $populacao = $this->populacao_10M[$idx];
                $os = $this->os_10M[$idx];
                break;
            case 11:
                $populacao = $this->populacao_11M[$idx];
                $os = $this->os_11M[$idx];
                break;
            case 12:
                $populacao = $this->populacao_12M[$idx];
                $os = $this->os_12M[$idx];
                break;
            case 13:
                $populacao = $this->populacao_13M[$idx];
                $os = $this->os_13M[$idx];
                break;
            case 14:
                $populacao = $this->populacao_14M[$idx];
                $os = $this->os_14M[$idx];
                break;
            case 15:
                $populacao = $this->populacao_15M[$idx];
                $os = $this->os_15M[$idx];
                break;
        }

        $res_div = ($os / $populacao) * 100;
        $res_mult = bcmul($res_div, $this->index_irc, 2);

        array_push($this->$irc, $res_mult);
    }

    /**
     * Monta a extratificaÁ„o dos dados
     *
     * @param array resultado obtido de $this->producao()
     *
     */
    private function extratifica($resultado)
    {
        if (!is_array($resultado)) {
            echo 'Erro: par‚metro inv·lido!';
            exit;
        }

        $login_admin = $this->login_admin;

        $temps = $this->preparaTabelaTemp();

        if (true === $temps['os_serie']) {
            $this->executePreparedStatements('tmp_os_serie',array($this->login_fabrica,$this->familia));
        }

        if (true === $temps['numero_serie1']) {
            $this->executePreparedStatements('tmp_numero_serie1',array($this->login_fabrica,$this->familia));
        }

        if (true === $temps['numero_serie2']) {
            $this->executePreparedStatements('tmp_numero_serie2',array($this->login_fabrica,$this->familia));
        }

        if (($this->temp_tbls['numero_serie1'] == "numero_serie1_$login_admin") and ($this->temp_tbls['numero_serie2'] == "numero_serie2_$login_admin") AND $this->login_fabrica == 50) {
            $sql = "SELECT numero_serie,
                                    serie, 
                                    produto,
                                    data_fabricacao 
                                INTO TEMP numero_serie_$login_admin
                                FROM (select * from numero_serie1_$login_admin UNION select * from numero_serie2_$login_admin) x;
                    create index numero_serie_sp_$login_admin on numero_serie_$login_admin(serie,produto);
            ";
            $res = pg_query($sql);
        }

        if (!empty($this->pecas)) {
            $this->executePreparedStatements('tmp_peca_serie',array($this->login_fabrica));
            $condFornecedores = $this->montaCondFornecedores();
            $condDatas = $this->montaCondDatas();
            $sql = "create index peca_serie_peca_$login_admin on peca_serie_$login_admin(peca) ; create index peca_serie_os_$login_admin on peca_serie_$login_admin(os);";
            $res = pg_query($sql);
            if (!empty($condFornecedores) or !empty($condDatas)) {
                $this->executePreparedStatements('tmp_fornecedor_serie',array($this->login_fabrica));
                //echo pg_last_error();
            }
        }

        $this->prepareStatements();
        $this->setArrOS15M();
        $this->setArrTotal15M();

        
        if ($this->login_fabrica == 24) {
            if (isset($this->diff_mes)) {
                if ($this->diff_mes < 4) {
                    $apopulacao = array(1);
                } else if ($this->diff_mes < 6) {
                    $apopulacao = array(1, 4);
                } else if ($this->diff_mes < 15) {
                    $apopulacao = array(1, 4, 6);    
                } else {
                    $apopulacao = array(1, 4, 6, 15);
                }
            } else {
                $apopulacao = array(1, 4, 6, 15);
            }

        } else {
            $apopulacao = range(1, 15);
        }

        foreach ($resultado as $k => $fetch) {
            $mes = $fetch["mes"];
            $ano = $fetch["ano"];
            $total = $fetch["total"];
            $cfe = $fetch["cfe"];

            $this->arr_total[$k] = $total;
            $this->arr_os[$k] = array("mes" => sprintf("%02d", $mes) . '/' . $ano, "os" => array());
            $this->setCFE($cfe);

            $this->popOS($ano, $mes, $k);

            foreach ($apopulacao as $v) {
                $this->populacao($v, $k);
                $this->populacaoPopOS($v, $k);
                $this->irc($v, $k);
            }

        }

        $this->setFaturados();

        foreach ($this->cfe as $k => $cfe) {
            $div = bcdiv($cfe, $this->arr_total[$k], 2);
            $div_fat = bcdiv($cfe, $this->faturados[$k], 2);
            $this->cfe_per_unit_prod[] = (!empty($div)) ? $div : '0.00';
            $this->cfe_per_unit_fat[] = (!empty($div_fat)) ? $div_fat : '0.00';
        }

    }

    /**
     * GeraÁ„o dos gr·ficos
     */
    private function geraGraficoTaxaFalha($meses, $taxa_falha, $oss, $titulo)
    {
        $titulo = preg_replace("/[^a-zA-Z ]/", "", strtr($titulo, "·‡„‚ÈÍÌÛÙı˙¸ÁÒ¡¿√¬… Õ”‘’⁄‹«—", "aaaaeeiooouucnAAAAEEIOOOUUCN"));

        $script = '
        <script src="js/highcharts_4.1.5.js"></script>
        <script src="js/exporting.js"></script>
        <div id="taxa_falha" style="min-width: 1500px; height: 400px; margin-top: 30px; display: none;"></div>
        ';

        $script.= "
        <script>
            $(function () {
                var chart;
                $(document).ready(function() {
                    chart = new Highcharts.Chart({
                        chart: {
                            renderTo: 'taxa_falha',
                            zoomType: 'xy'
                        },
                        title: {
                            text: 'Taxa Falha - OS (%) - $titulo'
                        },
                        subtitle: {
                            text: 'Producao/Mes'
                        },
                        exporting: {
                            width: 1500,
                            sourceWidth: 1500
                        },
                        credits: {
                            enabled: false
                        },
                        xAxis: [{
                            categories: $meses
                        }],
                        yAxis: [{ // Primary yAxis
                            min: 0,
                            labels: {
                                formatter: function() {
                                    return this.value +'%';
                                },
                                style: {
                                    color: '#89A54E'
                                }
                            },
                            title: {
                                text: 'Taxa Falha',
                                style: {
                                    color: '#89A54E'
                                }
                            }
                        }, { // Secondary yAxis
                            title: {
                                text: 'OSs',
                                style: {
                                    color: '#4572A7'
                                }
                            },
                            labels: {
                                formatter: function() {
                                    return this.value;
                                },
                                style: {
                                    color: '#4572A7'
                                }
                            },
                            opposite: true
                        }],
                        tooltip: {
                            formatter: function() {
                                return ''+
                                    this.x +': '+ this.y +
                                    (this.series.name == 'Taxa Falha' ? '%' : '');
                            }
                        },
                        legend: {
                            backgroundColor: '#FFFFFF',
                            reversed: true
                        },
                        series: [{
                            name: 'OS\'s',
                            color: '#4572A7',
                            type: 'column',
                            yAxis: 1,
                            data: [$oss]

                        }, {
                            name: 'Taxa Falha',
                            color: '#C00000',
                            type: 'line',
                            data: [$taxa_falha]
                        }]
                    });
                });

            });
        </script>
        ";

        return $script;

    }

    private function geraGraficoTaxaFalhaComparativo($meses, $oss_anterior, $oss_atual, $tx_falha_anterior, $tx_falha_atual, $titulo = '', $idx = '0')
    {
        $titulo = preg_replace("/[^a-zA-Z ]/", "", strtr($titulo, "·‡„‚ÈÍÌÛÙı˙¸ÁÒ¡¿√¬… Õ”‘’⁄‹«—", "aaaaeeiooouucnAAAAEEIOOOUUCN"));

        $script = '<div id="tx_falha_comparativo_' . $idx . '" style="min-width: 1500px; height: 400px; margin-top: 30px;; display: none;"></div>';

        $script.= "
        <script>
            $(function () {
                var chart;
                $(document).ready(function() {
                    chart = new Highcharts.Chart({
                        chart: {
                            renderTo: 'tx_falha_comparativo_$idx',
                            zoomType: 'xy'
                        },
                        title: {
                            text: 'Taxa Falha - OS (%) - $titulo - Comparativo'
                        },
                        subtitle: {
                            text: 'Producao/Mes'
                        },
                        exporting: {
                            width: 1500,
                            sourceWidth: 1500
                        },
                        credits: {
                            enabled: false
                        },
                        xAxis: [{
                            categories: $meses
                        }],
                        yAxis: [{ // Primary yAxis
                            min: 0,
                            labels: {
                                formatter: function() {
                                    return this.value +'%';
                                },
                                style: {
                                    color: '#89A54E'
                                }
                            },
                            title: {
                                text: 'Taxa Falha',
                                style: {
                                    color: '#89A54E'
                                }
                            }
                        }, { // Secondary yAxis
                            title: {
                                text: 'OSs',
                                style: {
                                    color: '#4572A7'
                                }
                            },
                            labels: {
                                formatter: function() {
                                    return this.value;
                                },
                                style: {
                                    color: '#4572A7'
                                }
                            },
                            opposite: true
                        }],
                        tooltip: {
                            formatter: function() {
                                var unit = {
                                    'OS\'s - Anterior': '',
                                    'OS\'s - Atual': '',
                                    'Taxa Falha - Anterior': '%',
                                    'Taxa Falha - Atual' : '%'
                                }[this.series.name];

                                return ''+
                                    this.x +': '+ this.y +' '+ unit;
                            }
                        },
                        legend: {
                            backgroundColor: '#FFFFFF',
                            reversed: true
                        },
                        series: [{
                            name: 'OS\'s - Anterior',
                            color: '#808080',
                            type: 'column',
                            yAxis: 1,
                            data: [$oss_anterior]

                        }, {
                            name: 'OS\'s - Atual',
                            color: '#4572A7',
                            type: 'column',
                            yAxis: 1,
                            data: [$oss_atual]
                        }, {
                            name: 'Taxa Falha - Anterior',
                            color: '#000000',
                            type: 'line',
                            data: [$tx_falha_anterior]
                        }, {
                            name: 'Taxa Falha - Atual',
                            color: '#C00000',
                            type: 'line',
                            data: [$tx_falha_atual]
                        }]
                    });
                });

            });
        </script>
        ";

        return $script;

    }

    private function geraGraficoIRC($meses, $tx_falha, array $ircs, $titulo = '', $idx = '0')
    {
        $titulo = preg_replace("/[^a-zA-Z ]/", "", strtr($titulo, "·‡„‚ÈÍÌÛÙı˙¸ÁÒ¡¿√¬… Õ”‘’⁄‹«—", "aaaaeeiooouucnAAAAEEIOOOUUCN"));

        $script = '<div id="irc_' . $idx . '" style="min-width: 1500px; height: 400px; margin-top: 30px;; display: none;"></div>';

        $script.= "
        <script>
            $(function () {
                var chart;
                $(document).ready(function() {
                    chart = new Highcharts.Chart({
                        chart: {
                            renderTo: 'irc_$idx',
                            zoomType: 'xy'
                        },
                        title: {
                            text: 'IRC (%) - $titulo'
                        },
                        subtitle: {
                            text: 'Producao/Mes'
                        },
                        exporting: {
                            width: 1500,
                            sourceWidth: 1500
                        },
                        credits: {
                            enabled: false
                        },
                        xAxis: [{
                            categories: $meses
                        }],
                        yAxis: [{ // Primary yAxis
                            min: 0,
                            labels: {
                                formatter: function() {
                                    return this.value +'%';
                                },
                                style: {
                                    color: '#89A54E'
                                }
                            },
                            title: {
                                text: 'IRC',
                                style: {
                                    color: '#89A54E'
                                }
                            }
                        }, { // Secondary yAxis
                            title: {
                                text: 'Taxa Falha',
                                style: {
                                    color: '#4572A7'
                                }
                            },
                            labels: {
                                formatter: function() {
                                    return this.value +'%';
                                },
                                style: {
                                    color: '#4572A7'
                                }
                            },
                            opposite: true
                        }],
                        tooltip: {
                            formatter: function() {
                                return ''+
                                    this.x +': '+ this.y + '%';
                            }
                        },
                        legend: {
                            backgroundColor: '#FFFFFF',
                            reversed: true
                        },
                        series: [{
                            name: 'Taxa Falha - OS\'s (%)',
                            color: '#4572A7',
                            type: 'column',
                            yAxis: 1,
                            data: [$tx_falha]

                        },";

        foreach ($ircs as $key => $value) {
            $mes = $key + 1;
            $data = implode(', ', $value);
            $script.= "{
                            name: 'IRC {$mes}M',
                            type: 'line',
                            data: [$data]
                       }";
            if ($mes <> 15) {
                $script.= ", ";
            }
        }

        $script.= "]
                    });
                });

            });
        </script>
        ";

        return $script;

    }

    private function geraGraficoIRC15Mes($meses, $arr_oss, array $ircs, $titulo = '')
    {
        $titulo = preg_replace("/[^a-zA-Z ]/", "", strtr($titulo, "·‡„‚ÈÍÌÛÙı˙¸ÁÒ¡¿√¬… Õ”‘’⁄‹«—", "aaaaeeiooouucnAAAAEEIOOOUUCN"));

        $script = '<div id="irc_15_mes" style="min-width: 1500px; height: 400px; margin-top: 30px;; display: none;"></div>';

        $data = '';

        $arr_mes_os = array();
        foreach ($arr_oss as $k => $v) {
            $work = $v['os'];
            foreach ($work as $j => $x) {
                $arr_mes_os[$j][] = $x;
            }
        }

        $i = count($arr_mes_os);
        $arr_mes_os = array_reverse($arr_mes_os, true);
        foreach ($arr_mes_os as $k => $v) {
            $data.= '{ name: \'' . $i . 'M\', data: [' . implode(", ", $v) . '] }, ';
            $i--;
        }

        $script.= "
        <script>
            $(function () {
            var chart;
            $(document).ready(function() {
                chart = new Highcharts.Chart({
                    chart: {
                        renderTo: 'irc_15_mes',
                        type: 'column'
                    },
                    title: {
                        text: 'IRC(%) - 15 meses - $titulo'
                    },
                    exporting: {
                        width: 1500,
                        sourceWidth: 1500
                    },
                    credits: {
                        enabled: false
                    },
                    colors: [
                        '#ADC683',
                        '#FFC000',
                        '#808080',
                        '#95B3D7',
                        '#B3A2C7',
                        '#C3D69B',
                        '#D99694',
                        '#948A54',
                        '#A6A6A6',
                        '#C27637',
                        '#3B879C',
                        '#654E7F',
                        '#7A9346',
                        '#973F3C',
                        '#3E6595'
                    ],
                    xAxis: {
                        categories: $meses
                    },
                    yAxis: [{
                        min: 0,
                        title: {
                            text: 'OS\'s'
                        }
                    }, {
                        title: {
                            text: 'IRC',
                            style: {
                                color: '#4572A7'
                            }
                        },
                        opposite: true
                    }],
                    legend: {
                        backgroundColor: '#FFFFFF',
                        reversed: true
                    },
                    tooltip: {
                        formatter: function() {
                            return ''+
                                this.series.name +': '+ this.y +'';
                        }
                    },
                    plotOptions: {
                        column: {
                            stacking: 'normal'
                        }
                    },
                    series: [
                        $data";

                    foreach ($ircs as $key => $value) {
                        $mes = $key + 1;
                        $data = implode(', ', $value);
                        $script.= "{
                                    name: 'IRC {$mes}M',
                                    type: 'line',
                                    yAxis: 1,
                                    data: [$data]
                                }";
                        if ($mes <> 15) {
                            $script.= ", ";
                        }
                    }

                    $script.= "
                    ]
                });
            });

        });
        </script>
        ";

        return $script;

    }

    private function geraGraficoCFEParqueInstalado($meses, $cfe, $cfe_per_unit, $titulo, $idx = '0')
    {
        $titulo = preg_replace("/[^a-zA-Z ]/", "", strtr($titulo, "·‡„‚ÈÍÌÛÙı˙¸ÁÒ¡¿√¬… Õ”‘’⁄‹«—", "aaaaeeiooouucnAAAAEEIOOOUUCN"));

        $script = '<div id="gr_cfe_' . $idx . '" style="min-width: 1500px; height: 400px; margin-top: 30px; display: none;"></div>';

        $script.= "
        <script>
            $(function () {
                var chart;
                $(document).ready(function() {
                    chart = new Highcharts.Chart({
                        chart: {
                            renderTo: 'gr_cfe_$idx',
                            zoomType: 'xy'
                        },
                        title: {
                            text: 'CFE $titulo'
                        },
                        subtitle: {
                            text: 'Producao/Mes'
                        },
                        exporting: {
                            width: 1500,
                            sourceWidth: 1500
                        },
                        credits: {
                            enabled: false
                        },
                        xAxis: [{
                            categories: $meses
                        }],
                        yAxis: [{ // Primary yAxis
                            min: 0,
                            labels: {
                                formatter: function() {
                                    return 'R$' + this.value;
                                },
                                style: {
                                    color: '#89A54E'
                                }
                            },
                            title: {
                                text: 'CFE Per Unit',
                                style: {
                                    color: '#89A54E'
                                }
                            }
                        }, { // Secondary yAxis
                            title: {
                                text: 'CFE',
                                style: {
                                    color: '#4572A7'
                                }
                            },
                            labels: {
                                formatter: function() {
                                    return 'R$ ' + this.value;
                                },
                                style: {
                                    color: '#4572A7'
                                }
                            },
                            opposite: true
                        }],
                        tooltip: {
                            formatter: function() {
                                return '' + this.series.name +': R$ '+ Highcharts.numberFormat(this.y, 2, ',', '.') +'';
                            }
                        },
                        legend: {
                            backgroundColor: '#FFFFFF',
                            reversed: true
                        },
                        series: [{
                            name: 'CFE',
                            color: '#4572A7',
                            type: 'column',
                            yAxis: 1,
                            data: [$cfe]

                        }, {
                            name: 'CFE Per Unit',
                            color: '#C00000',
                            type: 'line',
                            data: [$cfe_per_unit]
                        }]
                    });
                });

            });
        </script>
        ";

        return $script;

    }

    private function geraGraficoTaxaFalhaFornecedor($meses,$produzidos_grafico){
        $periodo = $this->getPeriodo();
        if(empty($periodo)){
            $periodo = 15;
        }

//         $this->arr_total_15M

        $data_inicial_pesquisa = $this->getDataInicial();
        if (empty($data_inicial_pesquisa)) {
            return $vazio;
        }

        $periodo1 = ($periodo == 1) ? 1 : ($periodo - 1);

        $qry = pg_query("SELECT ('{$data_inicial_pesquisa}'::date - interval '0 month')::date");
        $data_mes_anterior = pg_fetch_result($qry, 0, 0);

        $qry = pg_query("SELECT ('{$data_mes_anterior}'::date - interval '$periodo1 month')::date");
        $data_inicial_defeitos = pg_fetch_result($qry, 0, 0);

        $qUltimoDiaMes = $this->executePreparedStatements("ultimo_dia_do_mes", array($data_mes_anterior));
        $data_final_defeitos = $qUltimoDiaMes[0]["ultimo_dia"];

        $familia = $this->getFamilia();
        $produtos = str_replace("produto", "colormaq_os_serie_{$familia}.produto", $this->montaCondProdutos());
        $fornecedores = $this->montaCondFornecedores();        
        $datas = $this->montaCondDatas();
        $postos = $this->getPostos();        

        $pecas = $this->getPecas();
        if (empty($familia)) {
            return $vazio;
        }
        if(count($pecas) > 0) {
            $cond_pecas = implode(', ', $pecas);
        }
        $cond_datas = '';
        $cond_fornecedores = '';
        $cond_postos = '';
        $join = '';

        if (!empty($datas)) {
            $cond_datas = ' AND (';
            $cond_datas.= implode('OR', $datas);
            $cond_datas.= ')';
        }

        if (!empty($fornecedores)) {
            $cond_fornecedores = $fornecedores;
        }

        if (!empty($cond_pecas)) {
            $sql = "SELECT numero_serie INTO TEMP temp_numero_serie
                            FROM tbl_ns_fornecedor
                            WHERE peca IN ($cond_pecas)
                                $cond_fornecedores
                        $cond_datas";
            $qry = pg_query($sql);

            $join = "JOIN tbl_numero_serie ns ON ns.serie = colormaq_os_serie.serie
                       AND ns.produto = colormaq_os_serie.produto
                       JOIN temp_numero_serie ON temp_numero_serie.numero_serie = ns.numero_serie";
            $cond_ns_pecas = " AND tbl_ns_fornecedor.peca in ($cond_pecas) ";
        }

        if (!empty($postos)) {
            $cond_postos = " AND posto IN (" . implode(', ', $postos) . ") ";
        }

        $fabricacao_inicio = $data_mes_anterior;
        $fabricacao_final = $data_final_defeitos;
        $abertura_inicio = '0000-00-00';
        $abertura_final = '0000-00-00';

        $sqlForn = "
            SELECT  DISTINCT
                    nome_fornecedor
            FROM    tbl_ns_fornecedor
                /*WHERE   fabrica = 50*/
            WHERE fabrica = ".$this->login_fabrica."
                $cond_fornecedores
                $cond_ns_pecas
           ORDER BY      nome_fornecedor;
        ";
        $resForn = pg_query($sqlForn);
        $fornecedores = pg_fetch_all_columns($resForn,0);

        for ($i = 1; $i <= $periodo; $i++) {

            if ($i == 1) {
                $qAbI = pg_query("SELECT ('{$fabricacao_inicio}'::date + interval '1 month')::date");
                $abertura_inicio = pg_fetch_result($qAbI, 0, 0);

                $qAbF = $this->executePreparedStatements("ultimo_dia_do_mes", array($abertura_inicio));
                $abertura_final = $qAbF[0]["ultimo_dia"];
                $data_final_defeitos = $abertura_final;
            } else {
                $qFaI = pg_query("SELECT ('{$fabricacao_inicio}'::date - interval '1 month')::date");
                $fabricacao_inicio = pg_fetch_result($qFaI, 0, 0);

                $qFaF = $this->executePreparedStatements("ultimo_dia_do_mes", array($fabricacao_inicio));
                $fabricacao_final = $qFaF[0]["ultimo_dia"];

                $qAbI = pg_query("SELECT ('{$abertura_inicio}'::date - interval '1 month')::date");
                $abertura_inicio = pg_fetch_result($qAbI, 0, 0);
                $abertura_final = $data_final_defeitos;
            }

            $join_regiao = '';
            $cond_regiao = '';
            if (!empty($this->estados_regiao)) {
                $join_regiao = ' JOIN tbl_posto USING(posto) ';
                $cond_regiao = " AND tbl_posto.estado IN ({$this->estados_regiao}) ";
            }

            if(is_numeric($familia)) {
                $sql = "
                    SELECT  tbl_ns_fornecedor.nome_fornecedor   AS fornecedor_nome,
                    COUNT(1)                            AS fornecedor_qtde
                    INTO TEMP    tmp_res{$i}
                    FROM    tbl_ns_fornecedor
                    JOIN    colormaq_numero_serie1 ON colormaq_numero_serie1.numero_serie   = tbl_ns_fornecedor.numero_serie
                    WHERE   colormaq_numero_serie1.data_fabricacao    BETWEEN '$fabricacao_inicio' AND '$fabricacao_final'
                    AND     colormaq_numero_serie1.familia  = $familia
                    AND     tbl_ns_fornecedor.fabrica       = $this->login_fabrica
                    $cond_fornecedores
                    $cond_ns_pecas
                    GROUP BY      tbl_ns_fornecedor.nome_fornecedor
                    ORDER BY      tbl_ns_fornecedor.nome_fornecedor;
                ";

                $sql2 = "
                    SELECT  tbl_ns_fornecedor.nome_fornecedor   AS fornecedor_taxa_falha_nome,
                    COUNT(tbl_os_item.peca)             AS fornecedor_taxa_falha_qtde
                    INTO TEMP    tmp_falha_res{$i}
                    FROM    tbl_ns_fornecedor
                    JOIN    colormaq_numero_serie1  ON  colormaq_numero_serie1.numero_serie = tbl_ns_fornecedor.numero_serie
                    JOIN    colormaq_os_serie       ON  colormaq_numero_serie1.produto      = colormaq_os_serie.produto
                    AND colormaq_numero_serie1.serie        = colormaq_os_serie.serie
                    $join
                    JOIN    tbl_os_produto          ON  tbl_os_produto.os                   = colormaq_os_serie.os
                    JOIN    tbl_os_item             ON  tbl_os_item.os_produto              = tbl_os_produto.os_produto
                    AND tbl_os_item.peca                    = tbl_ns_fornecedor.peca
                    WHERE colormaq_numero_serie1.data_fabricacao    BETWEEN '$fabricacao_inicio 00:00:00' AND '$fabricacao_final 23:59:59'                
                    AND     colormaq_numero_serie1.familia  = $familia
                    AND     colormaq_os_serie.familia       = $familia
                    $cond_fornecedores
                    $cond_ns_pecas 
                    GROUP BY      tbl_ns_fornecedor.nome_fornecedor
                    ORDER BY      tbl_ns_fornecedor.nome_fornecedor;
                ";

                $sql3 = "
                    SELECT   COUNT(1)                            AS total_fornecedores           
                    INTO TEMP    tmp_total_falha_res{$i}
                    FROM    tbl_ns_fornecedor
                    JOIN    colormaq_numero_serie1 ON colormaq_numero_serie1.numero_serie   = tbl_ns_fornecedor.numero_serie
                    WHERE   colormaq_numero_serie1.data_fabricacao    BETWEEN '$fabricacao_inicio' AND '$fabricacao_final'
                    AND     colormaq_numero_serie1.familia  = $familia
                    AND     tbl_ns_fornecedor.fabrica       = $this->login_fabrica
                    $cond_fornecedores
                    $cond_ns_pecas;
                ";

                $res = pg_query($sql);
                $res2 = pg_query($sql2);
                $res3 = pg_query($sql3);
            }
            $sql = "
                SELECT * FROM tmp_res$i;
            ";
            $res = pg_query($sql);

            $sql2 = "
                SELECT * FROM tmp_falha_res$i;
            ";            
            $res2 = pg_query($sql2);

            $sql3 = "
                SELECT * FROM tmp_total_falha_res{$i};
            ";
            $res3 = pg_query($sql3);


            $grafico_fornecedores[$i] = pg_fetch_all($res);
            $grafico_tx_falha[$i]     = pg_fetch_all($res2);
            $grafico_total_fornecedores[$i] = pg_fetch_all($res3);
        }


        $acerto_fornecedores = array_reverse($grafico_fornecedores,TRUE);
        $acerto_tx_falha     = array_reverse($grafico_tx_falha,TRUE);
        $acerto_total_fornecedores = array_reverse($grafico_total_fornecedores,TRUE);

//         print_r($fornecedores);
//         echo "<br>";
        // echo "<pre>";
        // echo "acerto fornecedores <br>";
        // print_r($acerto_fornecedores);
        // echo "acerto tx falha <br>";
        // print_r($acerto_tx_falha);

        // echo "total fornecedores <br>";
        // print_r($acerto_total_fornecedores);
        // echo "</pre>";

        foreach($acerto_fornecedores  as $chave=>$array_fornecedor){
            foreach($array_fornecedor as $campo=>$valor){
                if(in_array($valor['fornecedor_nome'],$fornecedores)){
                    $array_grafico_fornecedores[$valor['fornecedor_nome']][$chave] = (int)$valor['fornecedor_qtde'];
                }
            }
        }

        foreach($acerto_tx_falha  as $chave=>$array_fornecedor){
            foreach($array_fornecedor as $campo=>$valor){
                if(in_array($valor['fornecedor_taxa_falha_nome'],$fornecedores)){
                    $array_grafico_taxa_falha[$valor['fornecedor_taxa_falha_nome']][$chave] = (int)$valor['fornecedor_taxa_falha_qtde'];
                }
            }
        }
        
        // echo "<pre>";
        // echo "grafico fornecedor <br>";
        // print_r($array_grafico_fornecedores);
        // echo "grafico tx falha <br>";
        // print_r($array_grafico_taxa_falha);

        // echo "produzidos <br>";
        // print_r($produzidos_grafico);

        // echo "</pre>";

        // //calculo do grafico
        // foreach($fornecedores as $fornecedor_grafico){
        //     echo $fornecedor_grafico;
        //     echo ": ";
        //      for($j = $periodo; $j >= 1; $j--){
        //         echo " J: ".$j;
        //         //echo "<br>";

        //         $valor = (isset($array_grafico_fornecedores[$fornecedor_grafico][$j])) ? $array_grafico_fornecedores[$fornecedor_grafico][$j] : 0 ;

        //         echo " | valor: ".$valor;
        //         //echo "<br>";
        //         echo " | produzidos: ".$produzidos_grafico[$j]['total'];
        //         //echo "<br>";

        //         $porcentagem_participacao = (($valor * 100) / $acerto_total_fornecedores[$j][0]['total_fornecedores']);
        //         $porcentagem_participacao = number_format($porcentagem_participacao,2,'.','');
        //         $taxa_falha_conta = (isset($array_grafico_taxa_falha[$fornecedor_grafico][$j])) ? $array_grafico_taxa_falha[$fornecedor_grafico][$j] : 0 ;
        //         $porcentagem[$fornecedor_grafico][$j] = (($taxa_falha_conta * 100) / $valor);
        //         echo " | grafico: ".$porcentagem_participacao;
                
        //         if($j > 1){
        //             echo ", ";
        //             echo "<br>";
        //         }
        //     }
        //     echo "<br>";

        // }

        //  foreach($fornecedores as $fornecedor_grafico){
        //     echo $fornecedor_grafico;
        //     echo ": ";
        //     for($j = $periodo; $j >= 1; $j--){
        //         $valor = $porcentagem[$fornecedor_grafico][$j];
        //         $mostrar = number_format($valor,2,'.','');
        //         echo $mostrar;

        //         if($j > 1){
        //             echo ", ";
        //         }
        //     }
        //     echo "<br>";
        // }


        $script = "
            <div id='taxa_falha_fornecedor' style='min-width: 1500px; height: 400px; margin-top: 30px; display: none;'></div>

            <script type='text/javascript'>
            $(function () {
                $('#taxa_falha_fornecedor').highcharts({
                    chart: {
                        zoomType:'xy'
                    },
                    title: {
                        text: 'Taxa Falha Fornecedor'
                    },
                    subtitle: {
                        text: 'ProduÁ„o / Taxa Falha'
                    },
                    xAxis: {
                        categories:
                            $meses
                        ,
                        crosshair: true
                    },
                    yAxis: [{
                        title: {
                            text: 'ParticipaÁ„o na ProduÁ„o'
                        },
                        labels: {
                            format: '{value} %',
                            style: {
                                color: '#4572A7'
                            }
                        }
                    },{
                        gridLineWidth: 0,
                        title: {
                            text: 'Taxa Falha',
                            style: {
                                color: '#4572A7'
                            }
                        },
                        labels: {
                            format: '{value} %',
                            style: {
                                color: '#4572A7'
                            }
                        },
                        opposite: true
                    }],
                    tooltip: {
                        headerFormat: '<span style=\'font-size:10px\'>{point.key}</span><table>',
                        pointFormat: '<tr><td style=\'color:{series.color};padding:0\'>{series.name}: </td>' +
                            '<td style=\'padding:0\'><b>{point.y} </b></td></tr>',
                        footerFormat: '</table>',
                        shared: true,
                        useHTML: true
                    },
                    plotOptions: {
                        column: {
                            pointPadding: 0.2,
                            borderWidth: 0
                        }
                    },
                    series: [
        ";
        foreach($fornecedores as $fornecedor_grafico){
            $script .= "
                {
                    name: '$fornecedor_grafico',
                    type: 'column',
                    data: [
            ";
            for($j = $periodo; $j >= 1; $j--){
                $valor = (isset($array_grafico_fornecedores[$fornecedor_grafico][$j])) ? $array_grafico_fornecedores[$fornecedor_grafico][$j] : 0 ;

                $porcentagem_participacao = (($valor * 100) / $acerto_total_fornecedores[$j][0]['total_fornecedores']);
                $porcentagem_participacao = number_format($porcentagem_participacao,2,'.','');
                $taxa_falha_conta = (isset($array_grafico_taxa_falha[$fornecedor_grafico][$j])) ? $array_grafico_taxa_falha[$fornecedor_grafico][$j] : 0 ;
                $porcentagem[$fornecedor_grafico][$j] = (($taxa_falha_conta * 100) / $valor);
                $script .= "
                    $porcentagem_participacao
                ";
                if($j > 1){
                    $script .= ", ";
                }
            }
            $script .= "
                    ],
                    tooltip: {
                            valueSuffix: '%'
                        }

                },
            ";
        }
        foreach($fornecedores as $fornecedor_grafico){
            $script .= "
                {
                    name: '$fornecedor_grafico',
                    type: 'spline',
                    yAxis: 1,
                    data: [
            ";
            for($j = $periodo; $j >= 1; $j--){
                $valor = $porcentagem[$fornecedor_grafico][$j];
                $mostrar = number_format($valor,2,'.','');
                $script .= "
                    $mostrar
                ";
                if($j > 1){
                    $script .= ", ";
                }
            }
            $script .= "
                    ],
                    tooltip: {
                        valueSuffix: '%'
                    }
                },
            ";
        }
        $script .= "
                    ]
                });
            });
            </script>
        ";
        
        return $script;
    }

    private function geraGraficoMaioresDefeitosPorPecas()
    {
        $pecas = $this->getPecas();
        $periodo = $this->getPeriodo();
        $vazio = '';

        if (empty($pecas)) {
            return $vazio;
        }

        $data_inicial_pesquisa = $this->getDataInicial();

        if (empty($data_inicial_pesquisa)) {
            return $vazio;
        }

        if(empty($periodo)){
            $periodo = 15;
        }

        $periodo1 = ($periodo == 1) ? 1 : ($periodo - 1);

        $qry = pg_query("SELECT ('{$data_inicial_pesquisa}'::date - interval '1 month')::date");
        $data_mes_anterior = pg_fetch_result($qry, 0, 0);

        $qry = pg_query("SELECT ('{$data_mes_anterior}'::date - interval '$periodo1 month')::date");
        $data_inicial_defeitos = pg_fetch_result($qry, 0, 0);

        $qUltimoDiaMes = $this->executePreparedStatements("ultimo_dia_do_mes", array($data_mes_anterior));
        $data_final_defeitos = $qUltimoDiaMes[0]["ultimo_dia"];

        $familia = $this->getFamilia();
        $produtos = str_replace("produto", "colormaq_os_serie_{$familia}.produto", $this->montaCondProdutos());
        $fornecedores = $this->montaCondFornecedores();
        $datas = $this->montaCondDatas();
        $postos = $this->getPostos();

        if (empty($familia)) {
            return $vazio;
        }

        $cond_pecas = implode(', ', $pecas);
        $cond_datas = '';
        $cond_fornecedores = '';
        $cond_postos = '';
        $join = '';

        if (!empty($datas)) {
            $cond_datas = ' AND (';
            $cond_datas.= implode('OR', $datas);
            $cond_datas.= ')';
        }

        if (!empty($fornecedores)) {
            $cond_fornecedores = $fornecedores;
        }

        if (!empty($cond_fornecedores) or !empty($cond_datas)) {
            $sql = "SELECT numero_serie INTO TEMP temp_numero_serie
                    FROM tbl_ns_fornecedor
                    WHERE peca IN ($cond_pecas)
                    $cond_fornecedores
                    $cond_datas";
            $qry = pg_query($sql);

            $join = "JOIN tbl_numero_serie ON tbl_numero_serie.serie = colormaq_os_serie_{$familia}.serie
                       AND tbl_numero_serie.produto = colormaq_os_serie_{$familia}.produto
                     JOIN temp_numero_serie ON temp_numero_serie.numero_serie = tbl_numero_serie.numero_serie";
        }

        if (!empty($postos)) {
            $cond_postos = " AND posto IN (" . implode(', ', $postos) . ") ";
        }

        $fabricacao_inicio = $data_mes_anterior;
        $fabricacao_final = $data_final_defeitos;
        $abertura_inicio = '0000-00-00';
        $abertura_final = '0000-00-00';

        for ($i = 1; $i <= $periodo; $i++) {

            if ($i == 1) {
                $qAbI = pg_query("SELECT ('{$fabricacao_inicio}'::date + interval '1 month')::date");
                $abertura_inicio = pg_fetch_result($qAbI, 0, 0);

                $qAbF = $this->executePreparedStatements("ultimo_dia_do_mes", array($abertura_inicio));
                $abertura_final = $qAbF[0]["ultimo_dia"];
                $data_final_defeitos = $abertura_final;
            } else {
                $qFaI = pg_query("SELECT ('{$fabricacao_inicio}'::date - interval '1 month')::date");
                $fabricacao_inicio = pg_fetch_result($qFaI, 0, 0);

                $qFaF = $this->executePreparedStatements("ultimo_dia_do_mes", array($fabricacao_inicio));
                $fabricacao_final = $qFaF[0]["ultimo_dia"];

                $qAbI = pg_query("SELECT ('{$abertura_inicio}'::date - interval '1 month')::date");
                $abertura_inicio = pg_fetch_result($qAbI, 0, 0);
                $abertura_final = $data_final_defeitos;
            }

            $join_regiao = '';
            $cond_regiao = '';
            if (!empty($this->estados_regiao)) {
                $join_regiao = ' JOIN tbl_posto USING(posto) ';
                $cond_regiao = " AND tbl_posto.estado IN ({$this->estados_regiao}) ";
            }

            $sql = "
                select tbl_os_item.defeito, tbl_defeito.descricao
                into temp tmp_res{$i}
                from tbl_os_item
                join tbl_os_produto on tbl_os_item.os_produto = tbl_os_produto.os_produto
                join colormaq_os_serie_{$familia} on tbl_os_produto.os = colormaq_os_serie_{$familia}.os
                join tbl_defeito on tbl_os_item.defeito = tbl_defeito.defeito
                join colormaq_numero_serie_{$familia} ON colormaq_numero_serie_{$familia}.serie = colormaq_os_serie_{$familia}.serie
                  and colormaq_os_serie_{$familia}.produto = colormaq_numero_serie_{$familia}.produto
                $join 
                $join_regiao
                where colormaq_numero_serie_{$familia}.data_fabricacao between '{$fabricacao_inicio}' and '{$fabricacao_final}'
                and colormaq_os_serie_{$familia}.data_abertura between '{$abertura_inicio}' and '{$abertura_final}'
                and tbl_os_item.peca in ($cond_pecas)
                $produtos                
                $cond_postos $cond_regiao";
            $query = pg_query($sql);
        }

        $sql = "
            SELECT count(defeito) as total, descricao
            FROM ( ";
                for ($i = 1; $i <= $periodo; $i++) {
                    if($i == 1){
                        $sql .= " SELECT defeito, descricao FROM tmp_res{$i} ";
                    }else{
                        $sql .= " UNION ALL SELECT defeito, descricao FROM tmp_res{$i} ";
                    }
                }
            $sql .= ") x
            group by descricao
            order by total desc limit 10
            ";
        $query = pg_query($sql);

        if (pg_num_rows($query) == 0) {
            return $vazio;
        }

        $total_defeitos = array();
        $defeitos = array();

        while ($fetch = pg_fetch_assoc($query)) {
            $total_defeitos[] = $fetch['total'];
            $defeitos[] = '"' . $fetch['descricao'] . '"';
        }

        $categories = implode(', ', $defeitos);
        $data = implode(', ', $total_defeitos);

        $script = '<div id="gr_maiores_defeitos" style="min-width: 1500px; height: 400px; margin-top: 30px; display: none;"></div>';

        $script.= "
        <script>
            $(function () {
                var chart;
                $(document).ready(function() {
                    chart = new Highcharts.Chart({
                        chart: {
                            renderTo: 'gr_maiores_defeitos',
                            zoomType: 'xy'
                        },
                        title: {
                            text: '10 maiores defeitos por peca'
                        },
                        exporting: {
                            width: 1500,
                            sourceWidth: 1500
                        },
                        credits: {
                            enabled: false
                        },
                        xAxis: [{
                            categories: [$categories]
                        }],
                        yAxis: [{ // Primary yAxis
                            min: 0,
                            labels: {
                                style: {
                                    color: '#89A54E'
                                }
                            },
                            title: {
                                text: 'Defeitos',
                                style: {
                                    color: '#89A54E'
                                }
                            }
                        },],
                        legend: {
                            backgroundColor: '#FFFFFF',
                            reversed: true
                        },
                        series: [{
                            name: 'Defeitos',
                            color: '#4572A7',
                            type: 'column',
                            data: [$data]

                        }]
                    });
                });

            });
        </script>
        ";

        return $script;

    }

    /**
     * Monta a tabela que exibe o resultado do relatÛrio
     */
    private function montaResultado()
    {

        $arr_single = array();


        foreach ($this->arr_os as $key => $value) {
            $tot = $this->arr_total[$key];
            $sum = array_sum($value['os']);
            $count = count($value['os']);
            $producao[$value['mes']] = $tot;
            $oss[$value['mes']] = $sum;
            $oss_count[$value['mes']] = $count;
            $this->arr_falha[$value['mes']] =  bcmul($sum / $tot, 100, 2);
        }
        
        foreach ($this->arr_os_anterior as $key => $value) {
            $tot = $this->arr_total_anterior[$key];
            $sum = array_sum($value['os']);
            $oss_anterior[$value['mes']] = $sum;
            $this->arr_falha_anterior[$value['mes']] =  bcmul($sum / $tot, 100, 2);
        }

        $this->arr_os_anterior = $oss_anterior;

        $count = count($this->arr_os[0]['os']);

        for ($i = 0; $i < $count; $i++) {
            foreach ($this->arr_os as $key => $value) {
                $arr_tmp[$value['mes']] = $value['os'][$i];
            }
            $arr_single[] = $arr_tmp;
        }

        $meses = range(0, $this->meses - 1);

        $arr_meses = array_keys($producao);

        unset($arr_tmp);

        $this->result_view = '<table class="tabela" cellspacing="1" align="center">
                                <tr class="titulo_coluna">
                                    <th>MÍs</th>';

        foreach ($producao as $key => $value) {
            $this->result_view.= '<th>' . $key . '</th>';
        }

        $this->result_view.= '</tr>';

        $titulo_producao = 'ProduÁ„o';
        $regiao = $this->getRegiao();
        if (!empty($regiao)) {
            $titulo_producao = 'Faturados';
        }

        $this->result_view.= '<tr>';
            $this->result_view.= '<td class="titulo_coluna">' . $titulo_producao . '</td>';

            foreach ($producao as $key => $value) {
                $this->result_view.= '<td>' . $value . '</td>';
            }
        $this->result_view.= '</tr>';

        $adicional_pareto_params = array();

        if (!empty($this->pecas) and false === $this->flag_peca) {
            $adicional_pareto_params = $this->pecas;
        } else {
            $adicional_pareto_params = array('0', '0');
        }

        if (!empty($this->fornecedores)) {
            $adicional_pareto_params[] = '\'' . implode('|', $this->fornecedores) . '\'';
        } else {
            $adicional_pareto_params[] = "''";
        }

        $datas = '';

        if (!empty($this->datas)) {

            $datas.= "'";

            foreach ($this->datas as $key => $value) {
                $datas.= $key . '*';

                foreach ($value as $idx => $val) {
                    $datas.= $idx . ':' . $val . ';';
                }

                $datas.= '|';
            }

            $datas.= "'";

            $adicional_pareto_params[] = $datas;
        } else {
            $adicional_pareto_params[] = "''";
        }

        if (!empty($this->revendas)) {
            $adicional_pareto_params[] = '\'' . implode('|', $this->revendas) . '\'';
        } else {
            $adicional_pareto_params[] = "''";
        }

        if (!empty($this->produtos)) {
            $adicional_pareto_params[] = '\'' . implode('|', $this->produtos) . '\'';
        } else {
            $adicional_pareto_params[] = "''";
        }

        if (!empty($this->pecas) and true === $this->flag_peca) {
            $adicional_pareto_params[] = '\'' . implode('|', $this->pecas) . '\'';
        } else {
            $adicional_pareto_params[] = "''";
        }

        if (!empty($this->posto)) {
            $adicional_pareto_params[] = '\'' . implode('|', $this->posto) . '\'';
        }

        if (empty($adicional_pareto_params)) {
            $adicional_pareto_params[] = "''";
        }

        $this->result_view.= '<tr>';
            $this->result_view.= '<td class="titulo_coluna">OS\'s</td>';
            foreach ($oss as $key => $value) {
                $param_data_year  = substr($key, 3, 4);
                $param_data_month = substr($key, 0 , 2);

                $next_month = $param_data_month + 1;

                if ($next_month == 13) {
                    $next_month = '01';
                    $next_year  = $param_data_year + 1;
                } else {
                    $next_month = sprintf('%02d', $next_month);
                    $next_year  = $param_data_year;
                }

                $param_data_fb = $param_data_year . '-' . $param_data_month;
                $param_data_ab = $next_year . '-' . $next_month;
                $meses_pareto = (int) $oss_count[$key];

                $this->result_view.= '<td style="cursor: pointer" onClick="pareto(\'' . $param_data_fb . '\', \'' . $param_data_ab . '\', \'' . $meses_pareto . '\', \'' . $this->familia . '\',\''. $this->matriz_filial .'\''.', ' . implode(', ', $adicional_pareto_params) .  ')">' . $value . '</td>';
            }
        $this->result_view.= '</tr>';

        $seq_meses = array(1 => '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');

        foreach ($meses as $key => $value) {
            $curr = $value + 1;
            $this->result_view.= '<tr>';
            $this->result_view.= '<td class="titulo_coluna">';
            $this->result_view.= $curr;
            $this->result_view.= 'M</td>';

            foreach ($arr_single[$key] as $x => $y) {
                $arr_abertura = explode('/', $x);
                $mes_fabricacao = (int) $arr_abertura[0] + $curr;
                $ano_fabricacao = $arr_abertura[1];
                if ($mes_fabricacao > 12) {
                    $div = (int) ($mes_fabricacao / 12);
                    $res = $mes_fabricacao % 12;
                    if ($res == 0) {
                        $res = 12;
                        $div = $div - 1;
                    }
                    $mes_fabricacao = $seq_meses[$res];
                    $ano_fabricacao = $ano_fabricacao + $div;
                }
                $abertura = $arr_abertura[1] . '-' . $arr_abertura[0];
                $fabricacao = $ano_fabricacao . '-' . sprintf('%02d', $mes_fabricacao);

                /**
                 * A lÛgica que faz a atribuiÁ„o de valores de $abertura e $fabricacao est· invertida.
                 * Foi mais f·cil alterar a ordem em que estas vari·veis s„o passadas para o JS do que reescrever a lÛgica. :)
                 */
                $abrePareto = '';
                if (!empty($y)) {
                    $abrePareto = ' style="cursor: pointer" onClick="pareto(\'' . $abertura . '\', \'' . $fabricacao . '\', \'1\', \'' . $this->familia . '\', \''. $this->matriz_filial. '\', ' . implode(', ', $adicional_pareto_params) . ' )"';
                }

                $this->result_view.= '<td' . $abrePareto . '>';
                $this->result_view.=  $y;
                $this->result_view.= '</td>';
            }
            $this->result_view.= '</tr>';
        }

        $colspan_26 = "26";
        if ($this->login_fabrica == 24 && isset($this->diff_mes)) {
            $colspan_26 = $this->diff_mes + 1;
        }

        $this->result_view.= '<tr><td colspan="'.$colspan_26.'">&nbsp;</td></tr>';

        $this->result_view.= '<tr>';
            $this->result_view.= '<td class="titulo_coluna">Taxa Falha - OS\'s (%)</td>';
            foreach ($this->arr_falha as $falha) {
                $this->result_view.= '<td>' . str_replace('.', ',', $falha) . '%</td>';
            }
        $this->result_view.= '</tr>';

        $this->result_view.= '<tr><td colspan="'.$colspan_26.'">&nbsp;</td></tr>';

        if ($this->login_fabrica == 24) {
            if (isset($this->diff_mes)) {
                if ($this->diff_mes < 4) {
                    $populacao_pop = array(1);
                } else if ($this->diff_mes < 6) {
                    $populacao_pop = array(1, 4);
                } else if ($this->diff_mes < 15) {
                    $populacao_pop = array(1, 4, 6);    
                } else {
                    $populacao_pop = array(1, 4, 6, 15);
                }
            } else {
                $populacao_pop = array(1, 4, 6, 15);
            }
            foreach ( $populacao_pop as $mes) {
                $populacao = 'populacao_' . $mes . 'M';
                $this->result_view.= '<tr>';
                    $this->result_view.= '<td class="titulo_coluna" nowrap>PopulaÁ„o - ' . $mes . 'M</td>';
                    foreach ($this->$populacao as $pop) {
                        $this->result_view.= '<td>' . $pop . '</td>';
                    }
                $this->result_view.= '</tr>';
            }            
        } else {
            foreach (range(1, 15) as $mes) {
                $populacao = 'populacao_' . $mes . 'M';
                $this->result_view.= '<tr>';
                    $this->result_view.= '<td class="titulo_coluna" nowrap>PopulaÁ„o - ' . $mes . 'M</td>';
                    foreach ($this->$populacao as $pop) {
                        $this->result_view.= '<td>' . $pop . '</td>';
                    }
                $this->result_view.= '</tr>';
            }
        }

        

        $this->result_view.= '<tr><td colspan="'.$colspan_26.'">&nbsp;</td></tr>';

        if ($this->login_fabrica == 24) {
            if (isset($this->diff_mes)) {
                if ($this->diff_mes < 4) {
                    $array_mes = array(1);
                } else if ($this->diff_mes < 6) {
                    $array_mes = array(1, 4);
                } else if ($this->diff_mes < 15) {
                    $array_mes = array(1, 4, 6);    
                } else {
                    $array_mes = array(1, 4, 6, 15);
                }
            } else {
                $array_mes = array(1,4,6,15);
            }

            foreach ($array_mes as $mes) {
                $os = 'os_' . $mes . 'M';
                $this->result_view.= '<tr>';
                    $this->result_view.= '<td class="titulo_coluna">OS\'s - ' . $mes . 'M</td>';
                    foreach ($this->$os as $pop) {
                        $this->result_view.= '<td>' . $pop . '</td>';
                    }
                $this->result_view.= '</tr>';
            }
        } else {
            foreach (range(1, 15) as $mes) {
                $os = 'os_' . $mes . 'M';
                $this->result_view.= '<tr>';
                    $this->result_view.= '<td class="titulo_coluna">OS\'s - ' . $mes . 'M</td>';
                    foreach ($this->$os as $pop) {
                        $this->result_view.= '<td>' . $pop . '</td>';
                    }
                $this->result_view.= '</tr>';
            }
        }
        
        

        $this->result_view.= '<tr><td colspan="'.$colspan_26.'">&nbsp;</td></tr>';

        $this->result_view.= '<tr>';
            $this->result_view.= '<td class="titulo_coluna">' . number_format($this->index_irc, 2, ',', '') . '</td>';
            $this->result_view.= '<td colspan="25">&nbsp;</td>';
        $this->result_view.= '</tr>';

        if ($this->login_fabrica == 24) {
            if (isset($this->diff_mes)) {
                if ($this->diff_mes < 4) {
                    $populacao_pop = array(1);
                } else if ($this->diff_mes < 6) {
                    $populacao_pop = array(1, 4);
                } else if ($this->diff_mes < 15) {
                    $populacao_pop = array(1, 4, 6);    
                } else {
                    $populacao_pop = array(1, 4, 6, 15);
                }
            } else {
                $populacao_pop = array(1, 4, 6, 15);
            }

            foreach ( $populacao_pop as $mes) {
                $populacao = 'populacao_' . $mes . 'M';
                $os = 'os_' . $mes . 'M';
                $irc = 'irc_' . $mes . 'M';
                $this->result_view.= '<tr>';
                    $this->result_view.= '<td class="titulo_coluna" nowrap>PopulaÁ„o - ' . $mes . 'M</td>';
                    foreach ($this->$populacao as $pop) {
                        $this->result_view.= '<td>' . $pop . '</td>';
                    }
                $this->result_view.= '</tr>';
                $this->result_view.= '<tr>';
                    $this->result_view.= '<td class="titulo_coluna">OS\'s - ' . $mes . 'M</td>';
                    foreach ($this->$os as $pop) {
                        $this->result_view.= '<td>' . $pop . '</td>';
                    }
                $this->result_view.= '</tr>';
                $this->result_view.= '<tr>';
                    $this->result_view.= '<td class="titulo_coluna">IRC - ' . $mes . 'M</td>';
                    foreach ($this->$irc as $pop) {
                        $this->result_view.= '<td>' . $pop . '</td>';
                    }
                $this->result_view.= '</tr>';
            }
        } else {
            foreach (range(1, 15) as $mes) {
                $populacao = 'populacao_' . $mes . 'M';
                $os = 'os_' . $mes . 'M';
                $irc = 'irc_' . $mes . 'M';
                $this->result_view.= '<tr>';
                    $this->result_view.= '<td class="titulo_coluna" nowrap>PopulaÁ„o - ' . $mes . 'M</td>';
                    foreach ($this->$populacao as $pop) {
                        $this->result_view.= '<td>' . $pop . '</td>';
                    }
                $this->result_view.= '</tr>';
                $this->result_view.= '<tr>';
                    $this->result_view.= '<td class="titulo_coluna">OS\'s - ' . $mes . 'M</td>';
                    foreach ($this->$os as $pop) {
                        $this->result_view.= '<td>' . $pop . '</td>';
                    }
                $this->result_view.= '</tr>';
                $this->result_view.= '<tr>';
                    $this->result_view.= '<td class="titulo_coluna">IRC - ' . $mes . 'M</td>';
                    foreach ($this->$irc as $pop) {
                        $this->result_view.= '<td>' . $pop . '</td>';
                    }
                $this->result_view.= '</tr>';
            }
        }

        $this->result_view.= '<tr><td colspan="'.$colspan_26.'">&nbsp;</td></tr>';

        $this->result_view.= '<tr>';
            $this->result_view.= '<td class="titulo_coluna">CFE</td>';
            foreach ($this->cfe as $cfe) {
                $this->result_view.= '<td nowrap>R$ ' . number_format($cfe, 2, ',', '.') . '</td>';
            }
        $this->result_view.= '</tr>';


        $this->result_view.= '<tr>';
            if (!empty($regiao)) {
                $cfe_label = 'Faturamento';
            } else {
                $cfe_label = 'ProduÁ„o';
            }
            $this->result_view.= '<td class="titulo_coluna">CFE per Unit (' . $cfe_label . ')</td>';
            foreach ($this->cfe_per_unit_prod as $cfe) {
                $this->result_view.= '<td nowrap>R$ ' . number_format($cfe, 2, ',', '.') . '</td>';
            }
        $this->result_view.= '</tr>';

        if (empty($regiao)) {
            $this->result_view.= '<tr>';
                $this->result_view.= '<td class="titulo_coluna">Produtos Faturados</td>';
                foreach ($this->faturados as $faturados) {
                    $this->result_view.= '<td nowrap>' . $faturados . '</td>';
                }
            $this->result_view.= '</tr>';

            $this->result_view.= '<tr>';
                $this->result_view.= '<td class="titulo_coluna">CFE per Unit (Faturamento)</td>';
                foreach ($this->cfe_per_unit_fat as $cfe) {
                    $this->result_view.= '<td nowrap>R$ ' . number_format($cfe, 2, ',', '.') . '</td>';
                }
            $this->result_view.= '</tr>';
        }

        $this->setCFEPerUnit15();
        $this->result_view.= '<tr>';
            $this->result_view.= '<td class="titulo_coluna">CFE per Unit (Parque Instalado - ' . $this->meses . 'M)</td>';
            foreach ($this->cfe_per_unit_15 as $cfe) {
                $this->result_view.= '<td nowrap>R$ ';
                if ($cfe == '-') {
                    $this->result_view.= $cfe;
                } else {
                    $this->result_view.= number_format($cfe, 2, ',', '.');
                }
                $this->result_view.= '</td>';
            }
        $this->result_view.= '</tr>';

        if (empty($regiao)) {
            $this->result_view.= '<tr><td colspan="'.$colspan_26.'">&nbsp;</td></tr>';

            $this->result_view.= '<tr class="titulo_coluna">
                                        <th>MÍs</th>';

            foreach ($producao as $key => $value) {
                $this->result_view.= '<th>' . $key . '</th>';
            }

            $this->result_view.= '</tr>';

            $this->result_view.= '<tr>';
                $this->result_view.= '<td class="titulo_coluna">Produtos Faturados</td>';
                foreach ($this->faturados as $faturados) {
                    $this->result_view.= '<td nowrap>' . $faturados . '</td>';
                }
            $this->result_view.= '</tr>';
        }

        $this->result_view.= '<tr><td colspan="'.$colspan_26.'">&nbsp;</td></tr>';

        array_shift($this->arr_os_anterior);
        array_push($this->arr_os_anterior, 0);
        array_shift($this->arr_falha_anterior);
        array_push($this->arr_falha_anterior, 0);
        $arr_diferenca_os = array();

        $this->result_view.= '<tr>';
            $this->result_view.= '<td class="titulo_coluna">Taxa Falha - OS\'s (%) - Atual</td>';
            foreach ($this->arr_falha as $falha) {
                $this->result_view.= '<td>' . str_replace('.', ',', $falha) . '%</td>';
            }
        $this->result_view.= '</tr>';

        $this->result_view.= '<tr>';
            $this->result_view.= '<td class="titulo_coluna">OS\'s - Atual</td>';
            foreach ($oss as $key => $value) {
                $this->result_view.= '<td>' . $value . '</td>';
            }
        $this->result_view.= '</tr>';

        $this->result_view.= '<tr>';
            $this->result_view.= '<td class="titulo_coluna">Taxa Falha - OS\'s (%) - Anterior</td>';
            foreach ($this->arr_falha_anterior as $falha) {
                $this->result_view.= '<td>' . str_replace('.', ',', $falha) . '%</td>';
            }
        $this->result_view.= '</tr>';

        $this->result_view.= '<tr>';
            $this->result_view.= '<td class="titulo_coluna">OS\'s - Anterior</td>';
            foreach ($this->arr_os_anterior as $key => $value) {
                $this->result_view.= '<td>' . $value . '</td>';
                $arr_diferenca_os[] = $oss[$key] - $value;
            }
        $this->result_view.= '</tr>';

        $this->result_view.= '<tr>';
            $this->result_view.= '<td class="titulo_coluna">DiferenÁa OS\'s</td>';
            foreach ($arr_diferenca_os as $value) {
                $this->result_view.= '<td>' . $value . '</td>';
            }
        $this->result_view.= '</tr>';


        $this->result_view.= '</table><br/>';

        $this->geraExcel();
        $this->pegaProdutos($this->produtos);
        $this->pegaRevendas($this->revendas);

        $query_desc_familia = pg_query("SELECT descricao from tbl_familia where familia = $this->familia");
        $familia_descricao = pg_fetch_result($query_desc_familia, 0, 'descricao');

        $this->result_view.= $this->geraGraficoTaxaFalha(json_encode($arr_meses), implode(", ", $this->arr_falha), implode(", ", $oss), $familia_descricao);

        $this->result_view.= $this->geraGraficoTaxaFalhaComparativo(
                                                                    json_encode($arr_meses),
                                                                    implode(", ", $this->arr_os_anterior),
                                                                    implode(", ", $oss),
                                                                    implode(", ", $this->arr_falha_anterior),
                                                                    implode(", ", $this->arr_falha),
                                                                    $familia_descricao
                                                                );

        $n_meses = (int) $this->meses;
        $arr_meses_15 = $this->arrayReduce($arr_meses, $n_meses, 0);
        $arr_os_anterior_comp15 = $this->arrayReduce($this->arr_os_anterior, $n_meses, 0);
        $arr_os_comp15 = $this->arrayReduce($oss, $n_meses, 0);
        $arr_falha_anterior_comp15 = $this->arrayReduce($this->arr_falha_anterior, $n_meses, 0);
        $arr_falha_comp15 = $this->arrayReduce($this->arr_falha, $n_meses, 0);

        $this->result_view.= $this->geraGraficoTaxaFalhaComparativo(
                                                                    json_encode($arr_meses_15),
                                                                    implode(", ", $arr_os_anterior_comp15),
                                                                    implode(", ", $arr_os_comp15),
                                                                    implode(", ", $arr_falha_anterior_comp15),
                                                                    implode(", ", $arr_falha_comp15),
                                                                    "$this->meses Meses - $familia_descricao",
                                                                    1
                                                                );

        $produzidos_grafico = $this->arrayReduce($this->producao(),$n_meses,0);

        if ($this->login_fabrica == 50) {
            $this->result_view.= $this->geraGraficoTaxaFalhaFornecedor(json_encode($arr_meses_15),$produzidos_grafico);
        }

        $ircs = array(
            $this->irc_1M,
            $this->irc_2M,
            $this->irc_3M,
            $this->irc_4M,
            $this->irc_5M,
            $this->irc_6M,
            $this->irc_7M,
            $this->irc_8M,
            $this->irc_9M,
            $this->irc_10M,
            $this->irc_11M,
            $this->irc_12M,
            $this->irc_13M,
            $this->irc_14M,
            $this->irc_15M,
        );

        $this->result_view.= $this->geraGraficoIRC(
                                                    json_encode($arr_meses),
                                                    implode(", ", $this->arr_falha),
                                                    $ircs,
                                                    $familia_descricao
                                                );

        $arr_falha_reduced = $this->arrayReduce($this->arr_falha, $n_meses, 0);
        $ircs_reduced = array(
            $this->arrayReduce($this->irc_1M, $n_meses, 0),
            $this->arrayReduce($this->irc_2M, $n_meses, 0),
            $this->arrayReduce($this->irc_3M, $n_meses, 0),
            $this->arrayReduce($this->irc_4M, $n_meses, 0),
            $this->arrayReduce($this->irc_5M, $n_meses, 0),
            $this->arrayReduce($this->irc_6M, $n_meses, 0),
            $this->arrayReduce($this->irc_7M, $n_meses, 0),
            $this->arrayReduce($this->irc_8M, $n_meses, 0),
            $this->arrayReduce($this->irc_9M, $n_meses, 0),
            $this->arrayReduce($this->irc_10M, $n_meses, 0),
            $this->arrayReduce($this->irc_11M, $n_meses, 0),
            $this->arrayReduce($this->irc_12M, $n_meses, 0),
            $this->arrayReduce($this->irc_13M, $n_meses, 0),
            $this->arrayReduce($this->irc_14M, $n_meses, 0),
            $this->arrayReduce($this->irc_15M, $n_meses, 0),
        );

        $this->result_view.= $this->geraGraficoIRC(
                                                    json_encode($arr_meses_15),
                                                    implode(", ", $arr_falha_reduced),
                                                    $ircs_reduced,
                                                    "$this->meses Mesess - $familia_descricao",
                                                    1
                                                );

        $arr_os_15M = $this->arrayReduce($this->arr_os, $n_meses, 0);
        $this->result_view.= $this->geraGraficoIRC15Mes(
                                                        json_encode($arr_meses_15),
                                                        $arr_os_15M,
                                                        $ircs_reduced,
                                                        $familia_descricao
                                                    );

        $this->cfe_per_unit_15[0] = "0";
        $this->result_view.= $this->geraGraficoCFEParqueInstalado(
                                                                json_encode($arr_meses),
                                                                implode(", ", $this->cfe),
                                                                implode(", ", $this->cfe_per_unit_15),
                                                                "(Parque Instalado - $this->meses M) - $familia_descricao"
                                                            );

        $this->result_view.= $this->geraGraficoCFEParqueInstalado(
                                                                json_encode($arr_meses),
                                                                implode(", ", $this->cfe),
                                                                implode(", ", $this->cfe_per_unit_prod),
                                                                "({$cfe_label}) - $familia_descricao",
                                                                1
                                                            );

        if (empty($regiao)) {
            $this->result_view.= $this->geraGraficoCFEParqueInstalado(
                                                                    json_encode($arr_meses),
                                                                    implode(", ", $this->cfe),
                                                                    implode(", ", $this->cfe_per_unit_fat),
                                                                    "(Faturamento) - $familia_descricao",
                                                                    2
                                                                );
        }

        $maiores_defeitos = $this->geraGraficoMaioresDefeitosPorPecas();

        if (empty($maiores_defeitos)) {
            $maiores_defeitos = '<div id="gr_maiores_defeitos" style="min-width: 1500px; margin-top: 30px; display: none; text-align: center">Nenhum resultado encontrado.</div>';
        }

        $this->result_view.= $maiores_defeitos;


    }

    private function geraExcel()
    {
        if (!empty($this->result_view)) {
            $destino = dirname(__FILE__) . '/xls';
            date_default_timezone_set('America/Sao_Paulo');
            $data = date('YmdGis');
            $arq_nome = 'relatorio_extratificacao-' . $this->login_fabrica . $data . '.xls';
            $file = $destino . '/' . $arq_nome ;
            $f = fopen($file, 'w');
            fwrite($f, $this->result_view);
            fclose($f);

            $this->result_view.= '<div align="center">';
            $this->result_view.= '<input type="button" value="Download do arquivo Excel" onClick="download(\'xls/' . $arq_nome . '\')" />';
            $this->result_view.= '<input type="button" value="Gr·fico Taxa Falha - OS" onClick="mostraRelatorio(\'tx_os\')" />';
            
            if ($this->login_fabrica == 24) {
                if ($this->diff_mes >= 15 || !isset($this->diff_mes)) {
                    
                    $this->result_view.= '<input type="button" value="Gr·fico Taxa Falha - OS - ' . $this->meses . ' Meses - Comparativo" onClick="mostraRelatorio(\'tx_os_comp_15\')" />';
                    $this->result_view.= '<input type="button" value="Gr·fico IRC ' . $this->meses . '" onClick="mostraRelatorio(\'irc_15\')" />';
                    $this->result_view.= '<input type="button" value="Gr·fico IRC ' . $this->meses . ' MÍs" onClick="mostraRelatorio(\'irc_15_mes\')" />';
                }
                $this->result_view.= '<input type="button" value="Gr·fico Taxa Falha - OS - Comparativo" onClick="mostraRelatorio(\'tx_os_comp\')" />';
                $this->result_view.= '<input type="button" value="Gr·fico IRC" onClick="mostraRelatorio(\'irc\')" />';
            } else {
                $this->result_view.= '<input type="button" value="Gr·fico Taxa Falha - OS - Comparativo" onClick="mostraRelatorio(\'tx_os_comp\')" />';
                $this->result_view.= '<input type="button" value="Gr·fico Taxa Falha - OS - ' . $this->meses . ' Meses - Comparativo" onClick="mostraRelatorio(\'tx_os_comp_15\')" />';
                if ($this->login_fabrica == 50) {
                    $this->result_view.= '<input type="button" value="Gr·fico Taxa Falha - Fornecedor" onClick="mostraRelatorio(\'tx_forn\')" />';
                }
                $this->result_view.= '<input type="button" value="Gr·fico IRC" onClick="mostraRelatorio(\'irc\')" />';
                $this->result_view.= '<input type="button" value="Gr·fico IRC ' . $this->meses . '" onClick="mostraRelatorio(\'irc_15\')" />';
                $this->result_view.= '<input type="button" value="Gr·fico IRC ' . $this->meses . ' MÍs" onClick="mostraRelatorio(\'irc_15_mes\')" />';
            }
            
            $this->result_view.= '<input type="button" value="CFE - Parque Instalado" onClick="mostraRelatorio(\'cfe_parq\')" />';

            $regiao = $this->getRegiao();
            $cfe_label = 'ProduÁ„o';

            if (!empty($regiao)) {
                $cfe_label = 'Faturamento';
            }

            $this->result_view.= '<input type="button" value="CFE - ' . $cfe_label . '" onClick="mostraRelatorio(\'cfe_prod\')" />';

            if (empty($regiao)) {
                $this->result_view.= '<input type="button" value="CFE - Faturamento" onClick="mostraRelatorio(\'cfe_fat\')" />';
            }

            $pecas = $this->getPecas();

            if (!empty($pecas)) {
                $this->result_view.= '<input type="button" value="Maiores Defeitos" onClick="mostraRelatorio(\'maiores_defeitos\')" />';
            }

            $this->result_view.= '</div>';

        }

    }
    private function geraTabelaRevendas($revendas){
        if(count($revendas) > 0){

            $this->result_view.= "<br /><table align='center' class='tabela'>
                        <tr class='titulo_coluna'><th colspan='2' align='center'>Revendas</td></tr>
                        <tr class='titulo_coluna'><th>CNPJ</th><th>Nome</th></tr>";
            foreach ($revendas as $revenda) {
                $this->result_view.= "<tr>
                                        <td>{$revenda['cnpj']}</td>
                                        <td align='left'>{$revenda['nome']}</td>
                                      </tr>";
            }
            $this->result_view.= "</table>";
        }
    }

    private function pegaRevendas($revendas){
        if(count($revendas) > 0){
            //$revendas = implode(',', $revendas);
            $sql = "SELECT cnpj, nome FROM tbl_revenda where cnpj IN ('".implode("','", $revendas)."')";
            $query_revenda = pg_query($sql);

            if (pg_num_rows($query_revenda) > 0) {
                $revendas = pg_fetch_all($query_revenda);
                $this->geraTabelaRevendas($revendas);
            } else {
                return '0';
            }
        }
    }

    private function geraTabelaProdutos($produtos){
        if(count($produtos) > 0){

            $this->result_view.= "<table align='center' class='tabela'>
                         <tr class='titulo_coluna'><th colspan='2' align='center'>Produtos</td></tr>
                        <tr class='titulo_coluna'><th>ReferÍncia</th><th>DescriÁ„o</th></tr>";
            foreach ($produtos as $produto) {
                $this->result_view.= "<tr>
                                        <td>{$produto['referencia']}</td>
                                        <td align='left'>{$produto['descricao']}</td>
                                      </tr>";
            }
            $this->result_view.= "</table>";
        }
    }

    /**
    * Pega a referÍncia e descriÁ„o dos produtos para montar tabela informando os Produtos selecionados
    */
    private function pegaProdutos($produtos){
        if(count($produtos) > 0){
            $produtos = implode(',', $produtos);

            $sql = "SELECT referencia,descricao FROM tbl_produto WHERE produto IN($produtos)";
            $query_produto = pg_query($sql);

            if (pg_num_rows($query_produto) > 0) {
                $produtos = pg_fetch_all($query_produto);
                $this->geraTabelaProdutos($produtos);
            } else {
                return '0';
            }
        }
    }

    /**
     * run - executa o programa - ˙nico mÈtodo acessÌvel via interface
     */
    public function run()
    {
        $m3 = "";

        if (!empty($_POST['data_inicial_global']) && !empty($_POST['data_final_global'])) {
            $dt_ini_array = explode("/", $_POST['data_inicial_global']);
            $dt_ini = $dt_ini_array[1]."-".$dt_ini_array[0]."-01";

            $dt_fin_array = explode("/", $_POST['data_final_global']);
            $ultimo_dia_mes = date('t', mktime(0,0,0,$dt_fin_array[0],'01',$dt_fin_array[1]));
            $dt_fin = $dt_fin_array[1]."-".$dt_fin_array[0]."-".$ultimo_dia_mes;

            $a1 = ($dt_fin_array[1] - $dt_ini_array[1])*12;
            $m1 = ($dt_fin_array[0] - $dt_ini_array[0])+1;
            $m3 = ($m1 + $a1); 

            $this->diff_mes = $m3;
        }

        $result = '0';
        if ($this->isRequest()) {
            if (!empty($m3)) {
                $result = $this->producao($m3);
                if ($result) {
                    //$count_ttl = count($result);
                    //$remover = $count_ttl - 1;
                    //unset($result[$remover]);
                    unset($result[0]);
                    $result = $this->ordenaArray($result);
                }
            } else {
                $result = $this->producao();
            }
        }

        if ($result <> '0') {
            $this->extratifica($result);

            if (!empty($this->arr_os)) {
                $tmp_data_inicial = $this->data_inicial;
                $tmp_data_final = $this->data_final;
                $tmp_arr_os = $this->arr_os;

                $date_i = new DateTime($this->data_inicial);
                $date_f = new DateTime($this->data_final);
                $date_i->sub(new DateInterval('P1M'));
                $date_f->sub(new DateInterval('P1M'));
                $this->data_inicial = $date_i->format('Y-m-d');
                $this->data_final = $date_f->format('Y-m-d');
                $result_anterior = $this->producao();

                if ($result_anterior <> '0') {
                    $this->arr_os = array();
                    foreach ($result_anterior as $k => $fetch) {
                        $mes = $fetch["mes"];
                        $ano = $fetch["ano"];
                        $total = $fetch["total"];
                        $cfe = $fetch["cfe"];

                        $this->arr_total_anterior[$k] = $total;
                        $this->arr_os[$k] = array("mes" => sprintf("%02d", $mes) . '/' . $ano, "os" => array());
                        $this->popOS($ano, $mes, $k);
                    }
                }

                $this->data_inicial = $tmp_data_inicial;
                $this->data_final = $tmp_data_final;

                if (!empty($this->arr_os) && isset($this->diff_mes) && $this->login_fabrica == 24) {
                    $old_arr_os = $this->arr_os;
                    unset($this->arr_os);
                    $this->arr_os = $this->ordenaArray($this->arr_os);

                    foreach ($old_arr_os as $xkey => $xvalue) {
                        if ($xkey >= $this->diff_mes) {
                            break;
                        }
                        $this->arr_os[$xkey] = $xvalue;
                    }
                }
                $this->arr_os_anterior = $this->arr_os;
                $this->arr_os = $tmp_arr_os;
                unset($tmp_arr_os);

                $this->montaResultado();

            }
        }
    }

}
