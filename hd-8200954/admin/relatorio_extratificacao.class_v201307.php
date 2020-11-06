<?php
/**
 *
 * relatorio_extratificacao.class.php
 *
 * RelatÛrio de ExtratificaÁ„o - desenvolvido para Colormaq conforme HD 819523
 *   - atualizado conforme HD 1134629
 *
 * @author  Francisco Ambrozio
 * @version 2013.07
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
	private $meses;
	private $familia;
	private $index_irc;
	private $data_inicial;
	private $data_final;
	private $pecas = array();
	private $msg_erro;
	private $result_view;

	private $arr_os = array();
	private $arr_os_anterior = array();
	private $arr_os_15M = array();
	private $arr_total = array();
	private $arr_total_anterior = array();
	private $arr_total_15M = array();
	private $arr_falha = array();
	private $arr_falha_anterior = array();

	private $populacao_1M = array();
	private $populacao_4M = array();
	private $populacao_6M = array();
	private $populacao_15M = array();

	private $os_1M = array();
	private $os_4M = array();
	private $os_6M = array();
	private $os_15M = array();

	private $irc_1M = array();
	private $irc_4M = array();
	private $irc_6M = array();
	private $irc_15M = array();

	private $cfe = array();
	private $cfe_per_unit_prod = array();
	private $cfe_per_unit_fat = array();
	private $cfe_per_unit_15 = array();
	private $faturados = array();

	private $pop_tmp = array(1 => 0, 4 => 0, 6 => 0, 15 => 0);

	public function __construct()
	{
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
	}

	private function setFamilia()
	{
		if (!empty($_POST["familia"])) {
			$this->familia = $_POST["familia"];
		}
	}

	private function setIndexIRC()
	{
		if (!empty($_POST["index_irc"])) {
			$this->index_irc = $_POST["index_irc"];
		} else {
			$this->index_irc = 1;
		}
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
	}

	private function setDataFinal()
	{
		$this->is_con();
		$data_inicial = $this->getDataInicial();
		$query = pg_query("select to_char(('$data_inicial'::date + interval '1 month') - interval '1 day', 'YYYY-MM-DD')::date");
		$this->data_final = pg_fetch_result($query, 0, 0);

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
	}

	private function setCFE($value)
	{
		$this->cfe[] = $value;
	}

	private function setFaturados()
	{
		$sql1 = "
				select count(tbl_numero_serie.serie) as total,
				extract (month from data_venda) as mes,
				extract (year from data_venda) as ano
				into temp tf1
				from tbl_numero_serie
				join tbl_produto using(produto)
				where tbl_produto.familia = $this->familia
				and fabrica = $this->login_fabrica
				and data_venda between to_char(('$this->data_inicial'::date - interval '23 month'), 'YYYY-MM-DD')::date and '$this->data_final'
				group by mes,ano order by ano,mes
				";

		$sql2 = "
				select 0 as total,
				extract(month from to_char(('$this->data_inicial'::date - interval '23 month'), 'YYYY-MM-DD')::date + s * interval '1 month') as mes,
				extract(year from to_char(('$this->data_inicial'::date - interval '23 month'), 'YYYY-MM-DD')::date + s * interval '1 month') as ano
				into temp tf2
				from generate_series(0, 23) as s
				";

		$sql3 = "CREATE TEMP TABLE rf as SELECT mes, ano from tf1 union select mes, ano from tf2 order by ano, mes;
					ALTER TABLE rf add total text;";

		$query = pg_query($sql1);
		$query = pg_query($sql2);
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

	private function setLoginFabrica()
	{
		global $login_fabrica;

		if (empty($login_fabrica)) {
			echo '<meta http-equiv="Refresh" content="0 ; url=http://www.telecontrol.com.br" />';
			exit;
		}

		$this->login_fabrica = $login_fabrica;
	}

	/**
	 * Inicializa os atributos necess·rios para a execuÁ„o do programa
	 */
	private function bootstrap()
	{
		$this->msg_erro = '';
		$this->result_view = '';
		$this->setLoginFabrica();
		$this->setMeses();
		$this->setFamilia();
		$this->setIndexIRC();
		$this->setDataInicial();
		$this->setDataFinal();
		$this->setPeca01();
		$this->setPeca02();
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

		if (!empty($campos)) {
			$this->msg_erro = 'Verifique os seguintes campos: ' . implode(', ', $campos);
			return false;
		}

		if (!empty($this->msg_erro)) {
			return false;
		}

		return true;

	}

	/**
	 * Prepare das consultas que ser„o executadas para trazer
	 *   o n˙mero de OSs mÍs a mÍs
	 */
	private function prepareStatements()
	{
		$this->is_con();

		$prepare = pg_prepare("ultimo_dia_do_mes", "select to_char(($1::date + interval '1 month') - interval '1 day', 'YYYY-MM-DD')::date as ultimo_dia");

		if (empty($this->pecas)) {
			$prepare = pg_prepare("total_os", "select count(tbl_os.os) as total from tbl_os join tbl_produto on tbl_os.produto = tbl_produto.produto join tbl_numero_serie on tbl_os.serie = tbl_numero_serie.serie and tbl_os.produto = tbl_numero_serie.produto where tbl_os.fabrica = $1 and tbl_numero_serie.data_fabricacao between $2 and $3 and tbl_os.data_abertura between $4 and $5 and familia = $6");
		} else {
			$cond = implode(', ', $this->pecas);
			$prepare = pg_prepare("total_os", "select count(tbl_os.os) as total from tbl_os join tbl_produto on tbl_os.produto = tbl_produto.produto join tbl_numero_serie on tbl_os.serie = tbl_numero_serie.serie and tbl_os.produto = tbl_numero_serie.produto join tbl_os_produto on tbl_os.os = tbl_os_produto.os join tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto where tbl_os.fabrica = $1 and tbl_numero_serie.data_fabricacao between $2 and $3 and tbl_os.data_abertura between $4 and $5 and familia = $6 and tbl_os_item.peca in ($cond)");
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
	private function producao($month = '23')
	{
		$this->is_con();

		if (empty($this->familia)) {
			die('Erro interno.');
		}

		$sql1 = "
				select count(tbl_numero_serie.serie) as total,
				extract (month from data_fabricacao) as mes,
				extract (year from data_fabricacao) as ano
				into temp t1
				from tbl_numero_serie
				join tbl_produto using(produto)
				where tbl_produto.familia = $this->familia
				and fabrica = $this->login_fabrica
				and data_fabricacao between to_char(('$this->data_inicial'::date - interval '$month month'), 'YYYY-MM-DD')::date and '$this->data_final'
				group by mes,ano order by ano,mes
				";

		$sql2 = "
				select 0 as total,
				extract(month from to_char(('$this->data_inicial'::date - interval '$month month'), 'YYYY-MM-DD')::date + s * interval '1 month') as mes,
				extract(year from to_char(('$this->data_inicial'::date - interval '$month month'), 'YYYY-MM-DD')::date + s * interval '1 month') as ano,
				0 as cfe
				into temp t2
				from generate_series(0, $month) as s
				";

		$sql4 = "SELECT qtde_produto_produzido as total, cf.mes, cf.ano, cf.cfe
				 INTO temp t1 from tbl_custo_falha cf
				 JOIN t2 ON t2.mes = cf.mes AND t2.ano = cf.ano
				 WHERE cf.fabrica = $this->login_fabrica and cf.familia = $this->familia";


		$sql3 = "CREATE TEMP TABLE r as SELECT mes, ano from t1 union select mes, ano from t2 order by ano, mes;
					ALTER TABLE r add total text;
					ALTER TABLE r add cfe text";

		$query = pg_query($sql2);
		$query = pg_query($sql4);
		$query = pg_query($sql3);

		$begin = pg_query("BEGIN");
		$sql_updates = "update r set total = t1.total, cfe = t1.cfe from t1 where r.mes = t1.mes and r.ano = t1.ano;
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
		$possiveis = array(1, 4, 6, 15);

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
		$possiveis = array(1, 4, 6, 15);

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

			$x_os = $this->executePreparedStatements("total_os", array($this->login_fabrica, $data_fabricacao_inicio, $data_fabricacao_final, $data_abertura_inicio, $data_abertura_final, $this->familia));

			$total_os = $x_os[0]["total"];

			array_push($this->arr_os[$idx]["os"], $total_os);

			$mes_loop++;

		}

	}

	/**
	 * Preenche os arrays populacao_{1,4,6,15}M e os_{1,4,6,15}M
	 */
	private function irc($m, $idx)
	{
		$possiveis = array(1, 4, 6, 15);

		if (!in_array($m, $possiveis)) {
			echo 'Erro: par‚metro inv·lido - ' , $m , '!';
			exit;
		}

		$irc = 'irc_' . $m . 'M';
		$populacao = 'populacao_' . $m . 'M';
		$os = 'os_' . $m . 'M';

		switch ($m) {
			case 1:
				$populacao = $this->populacao_1M[$idx];
				$os = $this->os_1M[$idx];
				break;
			case 4:
				$populacao = $this->populacao_4M[$idx];
				$os = $this->os_4M[$idx];
				break;
			case 6:
				$populacao = $this->populacao_6M[$idx];
				$os = $this->os_6M[$idx];
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

		$this->prepareStatements();
		$this->setArrOS15M();
		$this->setArrTotal15M();

		$apopulacao = array(1, 4, 6, 15);

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
		<script src="http://code.highcharts.com/highcharts.js"></script>
		<script src="http://code.highcharts.com/modules/exporting.js"></script>
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

	private function geraGraficoIRC($meses, $tx_falha, $irc_1, $irc_4, $irc_6, $irc_15, $titulo = '', $idx = '0')
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

						}, {
							name: 'IRC 1M',
							color: '#808000',
							type: 'line',
							data: [$irc_1]
						}, {
							name: 'IRC 4M',
							color: '#000000',
							type: 'line',
							data: [$irc_4]
						}, {
							name: 'IRC 6M',
							color: '#604A7B',
							type: 'line',
							data: [$irc_6]
						}, {
							name: 'IRC 15M',
							color: '#C00000',
							type: 'line',
							data: [$irc_15]
						}]
					});
				});

			});
		</script>
		";

		return $script;

	}

	private function geraGraficoIRC15Mes($meses, $arr_oss, $irc_1, $irc_4, $irc_6, $irc_15, $titulo = '')
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

		$i = 15;
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
						$data
						{
							name: 'IRC 1M',
							color: '#808000',
							type: 'line',
							yAxis: 1,
							data: [$irc_1]
						}, {
							name: 'IRC 4M',
							color: '#000000',
							type: 'line',
							yAxis: 1,
							data: [$irc_4]
						}, {
							name: 'IRC 6M',
							color: '#604A7B',
							type: 'line',
							yAxis: 1,
							data: [$irc_6]
						}, {
							name: 'IRC 15M',
							color: '#C00000',
							type: 'line',
							yAxis: 1,
							data: [$irc_15]
						}
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

		$this->result_view.= '<tr>';
			$this->result_view.= '<td class="titulo_coluna">ProduÁ„o</td>';
			foreach ($producao as $key => $value) {
				$this->result_view.= '<td>' . $value . '</td>';
			}
		$this->result_view.= '</tr>';

		$adicional_pareto_params = '';

		if (!empty($this->pecas)) {
			$adicional_pareto_params = ', ' . implode(', ', $this->pecas);
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

				$this->result_view.= '<td style="cursor: pointer" onClick="pareto(\'' . $param_data_fb . '\', \'' . $param_data_ab . '\', \'' . $meses_pareto . '\', \'' . $this->familia . '\'' . $adicional_pareto_params . ')">' . $value . '</td>';
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
					$abrePareto = ' style="cursor: pointer" onClick="pareto(\'' . $abertura . '\', \'' . $fabricacao . '\', \'1\', \'' . $this->familia . '\'' . $adicional_pareto_params . ')"';
				}
				
				$this->result_view.= '<td' . $abrePareto . '>';
				$this->result_view.=  $y;
				$this->result_view.= '</td>';
			}
			$this->result_view.= '</tr>';
		}

		$this->result_view.= '<tr><td colspan="26">&nbsp;</td></tr>';

		$this->result_view.= '<tr>';
			$this->result_view.= '<td class="titulo_coluna">Taxa Falha - OS\'s (%)</td>';
			foreach ($this->arr_falha as $falha) {
				$this->result_view.= '<td>' . str_replace('.', ',', $falha) . '%</td>';
			}
		$this->result_view.= '</tr>';

		$this->result_view.= '<tr><td colspan="26">&nbsp;</td></tr>';

		$this->result_view.= '<tr>';
			$this->result_view.= '<td class="titulo_coluna" nowrap>PopulaÁ„o - 1M</td>';
			foreach ($this->populacao_1M as $populacao) {
				$this->result_view.= '<td>' . $populacao . '</td>';
			}
		$this->result_view.= '</tr>';

		$this->result_view.= '<tr>';
			$this->result_view.= '<td class="titulo_coluna" nowrap>PopulaÁ„o - 4M</td>';
			foreach ($this->populacao_4M as $populacao) {
				$this->result_view.= '<td>' . $populacao . '</td>';
			}
		$this->result_view.= '</tr>';

		$this->result_view.= '<tr>';
			$this->result_view.= '<td class="titulo_coluna" nowrap>PopulaÁ„o - 6M</td>';
			foreach ($this->populacao_6M as $populacao) {
				$this->result_view.= '<td>' . $populacao . '</td>';
			}
		$this->result_view.= '</tr>';

		$this->result_view.= '<tr>';
			$this->result_view.= '<td class="titulo_coluna" nowrap>PopulaÁ„o - 15M</td>';
			foreach ($this->populacao_15M as $populacao) {
				$this->result_view.= '<td>' . $populacao . '</td>';
			}
		$this->result_view.= '</tr>';

		$this->result_view.= '<tr><td colspan="26">&nbsp;</td></tr>';

		$this->result_view.= '<tr>';
			$this->result_view.= '<td class="titulo_coluna">OS\'s - 1M</td>';
			foreach ($this->os_1M as $populacao) {
				$this->result_view.= '<td>' . $populacao . '</td>';
			}
		$this->result_view.= '</tr>';

		$this->result_view.= '<tr>';
			$this->result_view.= '<td class="titulo_coluna">OS\'s - 4M</td>';
			foreach ($this->os_4M as $populacao) {
				$this->result_view.= '<td>' . $populacao . '</td>';
			}
		$this->result_view.= '</tr>';

		$this->result_view.= '<tr>';
			$this->result_view.= '<td class="titulo_coluna">OS\'s - 6M</td>';
			foreach ($this->os_6M as $populacao) {
				$this->result_view.= '<td>' . $populacao . '</td>';
			}
		$this->result_view.= '</tr>';

		$this->result_view.= '<tr>';
			$this->result_view.= '<td class="titulo_coluna" nowrap>OS\'s - 15M</td>';
			foreach ($this->os_15M as $populacao) {
				$this->result_view.= '<td>' . $populacao . '</td>';
			}
		$this->result_view.= '</tr>';

		$this->result_view.= '<tr><td colspan="26">&nbsp;</td></tr>';

		$this->result_view.= '<tr>';
			$this->result_view.= '<td class="titulo_coluna">' . number_format($this->index_irc, 2, ',', '') . '</td>';
			$this->result_view.= '<td colspan="25">&nbsp;</td>';
		$this->result_view.= '</tr>';

		$this->result_view.= '<tr>';
			$this->result_view.= '<td class="titulo_coluna" nowrap>PopulaÁ„o - 1M</td>';
			foreach ($this->populacao_1M as $populacao) {
				$this->result_view.= '<td>' . $populacao . '</td>';
			}
		$this->result_view.= '</tr>';
		$this->result_view.= '<tr>';
			$this->result_view.= '<td class="titulo_coluna">OS\'s - 1M</td>';
			foreach ($this->os_1M as $populacao) {
				$this->result_view.= '<td>' . $populacao . '</td>';
			}
		$this->result_view.= '</tr>';
		$this->result_view.= '<tr>';
			$this->result_view.= '<td class="titulo_coluna">IRC - 1M</td>';
			foreach ($this->irc_1M as $populacao) {
				$this->result_view.= '<td>' . $populacao . '</td>';
			}
		$this->result_view.= '</tr>';

		$this->result_view.= '<tr>';
			$this->result_view.= '<td class="titulo_coluna" nowrap>PopulaÁ„o - 4M</td>';
			foreach ($this->populacao_4M as $populacao) {
				$this->result_view.= '<td>' . $populacao . '</td>';
			}
		$this->result_view.= '</tr>';
		$this->result_view.= '<tr>';
			$this->result_view.= '<td class="titulo_coluna">OS\'s - 4M</td>';
			foreach ($this->os_4M as $populacao) {
				$this->result_view.= '<td>' . $populacao . '</td>';
			}
		$this->result_view.= '</tr>';
		$this->result_view.= '<tr>';
			$this->result_view.= '<td class="titulo_coluna">IRC - 4M</td>';
			foreach ($this->irc_4M as $populacao) {
				$this->result_view.= '<td>' . $populacao . '</td>';
			}
		$this->result_view.= '</tr>';

		$this->result_view.= '<tr>';
			$this->result_view.= '<td class="titulo_coluna" nowrap>PopulaÁ„o - 6M</td>';
			foreach ($this->populacao_6M as $populacao) {
				$this->result_view.= '<td>' . $populacao . '</td>';
			}
		$this->result_view.= '</tr>';
		$this->result_view.= '<tr>';
			$this->result_view.= '<td class="titulo_coluna">OS\'s - 6M</td>';
			foreach ($this->os_6M as $populacao) {
				$this->result_view.= '<td>' . $populacao . '</td>';
			}
		$this->result_view.= '</tr>';
		$this->result_view.= '<tr>';
			$this->result_view.= '<td class="titulo_coluna">IRC - 6M</td>';
			foreach ($this->irc_6M as $populacao) {
				$this->result_view.= '<td>' . $populacao . '</td>';
			}
		$this->result_view.= '</tr>';

		$this->result_view.= '<tr>';
			$this->result_view.= '<td class="titulo_coluna" nowrap>PopulaÁ„o - 15M</td>';
			foreach ($this->populacao_15M as $populacao) {
				$this->result_view.= '<td>' . $populacao . '</td>';
			}
		$this->result_view.= '</tr>';
		$this->result_view.= '<tr>';
			$this->result_view.= '<td class="titulo_coluna">OS\'s - 15M</td>';
			foreach ($this->os_15M as $populacao) {
				$this->result_view.= '<td>' . $populacao . '</td>';
			}
		$this->result_view.= '</tr>';
		$this->result_view.= '<tr>';
			$this->result_view.= '<td class="titulo_coluna">IRC - 15M</td>';
			foreach ($this->irc_15M as $populacao) {
				$this->result_view.= '<td>' . $populacao . '</td>';
			}
		$this->result_view.= '</tr>';

		$this->result_view.= '<tr><td colspan="26">&nbsp;</td></tr>';

		$this->result_view.= '<tr>';
			$this->result_view.= '<td class="titulo_coluna">CFE</td>';
			foreach ($this->cfe as $cfe) {
				$this->result_view.= '<td nowrap>R$ ' . number_format($cfe, 2, ',', '.') . '</td>';
			}
		$this->result_view.= '</tr>';

		$this->result_view.= '<tr>';
			$this->result_view.= '<td class="titulo_coluna">CFE per Unit (ProduÁ„o)</td>';
			foreach ($this->cfe_per_unit_prod as $cfe) {
				$this->result_view.= '<td nowrap>R$ ' . number_format($cfe, 2, ',', '.') . '</td>';
			}
		$this->result_view.= '</tr>';

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

		$this->result_view.= '<tr><td colspan="26">&nbsp;</td></tr>';

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

		$this->result_view.= '<tr><td colspan="26">&nbsp;</td></tr>';

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

		$this->result_view.= $this->geraGraficoIRC(
													json_encode($arr_meses),
													implode(", ", $this->arr_falha),
													implode(", ", $this->irc_1M),
													implode(", ", $this->irc_4M),
													implode(", ", $this->irc_6M),
													implode(", ", $this->irc_15M),
													$familia_descricao
												);

		$arr_falha_reduced = $this->arrayReduce($this->arr_falha, $n_meses, 0);
		$arr_irc_1M15 = $this->arrayReduce($this->irc_1M, $n_meses, 0);
		$arr_irc_4M15 = $this->arrayReduce($this->irc_4M, $n_meses, 0);
		$arr_irc_6M15 = $this->arrayReduce($this->irc_6M, $n_meses, 0);
		$arr_irc_15M15 = $this->arrayReduce($this->irc_15M, $n_meses, 0);

		$this->result_view.= $this->geraGraficoIRC(
													json_encode($arr_meses_15),
													implode(", ", $arr_falha_reduced),
													implode(", ", $arr_irc_1M15),
													implode(", ", $arr_irc_4M15),
													implode(", ", $arr_irc_6M15),
													implode(", ", $arr_irc_15M15),
													"$this->meses Meses - $familia_descricao",
													1
												);

		$arr_os_15M = $this->arrayReduce($this->arr_os, $n_meses, 0);
		$this->result_view.= $this->geraGraficoIRC15Mes(
														json_encode($arr_meses_15),
														$arr_os_15M,
														implode(", ", $arr_irc_1M15),
														implode(", ", $arr_irc_4M15),
														implode(", ", $arr_irc_6M15),
														implode(", ", $arr_irc_15M15),
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
																"(ProduÁ„o) - $familia_descricao",
																1
															);

		$this->result_view.= $this->geraGraficoCFEParqueInstalado(
																json_encode($arr_meses),
																implode(", ", $this->cfe),
																implode(", ", $this->cfe_per_unit_fat),
																"(Faturamento) - $familia_descricao",
																2
															);

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
			$this->result_view.= '<input type="button" value="Gr·fico Taxa Falha - OS - Comparativo" onClick="mostraRelatorio(\'tx_os_comp\')" />';
			$this->result_view.= '<input type="button" value="Gr·fico Taxa Falha - OS - ' . $this->meses . ' Meses - Comparativo" onClick="mostraRelatorio(\'tx_os_comp_15\')" />';
			$this->result_view.= '<input type="button" value="Gr·fico IRC" onClick="mostraRelatorio(\'irc\')" />';
			$this->result_view.= '<input type="button" value="Gr·fico IRC ' . $this->meses . '" onClick="mostraRelatorio(\'irc_15\')" />';
			$this->result_view.= '<input type="button" value="Gr·fico IRC ' . $this->meses . ' MÍs" onClick="mostraRelatorio(\'irc_15_mes\')" />';
			$this->result_view.= '<input type="button" value="CFE - Parque Instalado" onClick="mostraRelatorio(\'cfe_parq\')" />';
			$this->result_view.= '<input type="button" value="CFE - ProduÁ„o" onClick="mostraRelatorio(\'cfe_prod\')" />';
			$this->result_view.= '<input type="button" value="CFE - Faturamento" onClick="mostraRelatorio(\'cfe_fat\')" />';
			$this->result_view.= '</div>';

		}

	}

	/**
	 * run - executa o programa - ˙nico mÈtodo acessÌvel via interface
	 */
	public function run()
	{
		$result = '0';

		if ($this->isRequest()) {
			$result = $this->producao();
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
				$this->arr_os_anterior = $this->arr_os;
				$this->arr_os = $tmp_arr_os;
				unset($tmp_arr_os);

				$this->montaResultado();

			}
		}
	}

}
