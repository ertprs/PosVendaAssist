<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "cadastro";

$array_estados = array(
    'AC' => 'Acre',
    'AL' => 'Alagoas',
    'AM' => 'Amazonas',
    'AP' => 'Amapá',
    'BA' => 'Bahia',
    'CE' => 'Ceara',
    'DF' => 'Distrito Federal',
    'ES' => 'Espírito Santo',
    'GO' => 'Goiás',
    'MA' => 'Maranhão',
    'MG' => 'Minas Gerais',
    'MS' => 'Mato Grosso do Sul',
    'MT' => 'Mato Grosso',
    'PA' => 'Pará',
    'PB' => 'Paraíba',
    'PE' => 'Pernambuco',
    'PI' => 'Piauí­',
    'PR' => 'Paraná',
    'RJ' => 'Rio de Janeiro',
    'RN' => 'Rio Grande do Norte',
    'RO' => 'Rondônia',
    'RR' => 'Roraima',
    'RS' => 'Rio Grande do Sul',
    'SC' => 'Santa Catarina',
    'SE' => 'Sergipe',
    'SP' => 'São Paulo',
    'TO' => 'Tocantins'
);

if (isset($_POST["ajax_busca_cidade"]) && !empty($_POST["estado"])) {
    $estado = strtoupper($_POST["estado"]);

    if (array_key_exists($estado, $array_estados)) {
        $sql = "
        	SELECT DISTINCT *
        	FROM (
	            	SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('{$estado}')
	            UNION (
	            	SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('{$estado}')
	            )
            ) AS cidade
            ORDER BY cidade ASC;
        ";
        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {
            $array_cidades = array();

            while ($result = pg_fetch_object($res)) {
                $array_cidades[] = $result->cidade;
            }

            $retorno = array("cidades" => $array_cidades);
        } else {
            $retorno = array("error" => utf8_encode("Nenhuma cidade encontrada para o estado: {$estado}"));
        }
    } else {
        $retorno = array("error" => utf8_encode("Estado não encontrado"));
    }

    exit(json_encode($retorno));
}

if (isset($_REQUEST['ajax_excluir_jornada']) && !empty($_REQUEST['hd_jornada'])) {
    $hd_jornada = $_REQUEST['hd_jornada'];

    $sql = "SELECT * FROM tbl_hd_jornada WHERE hd_jornada = {$hd_jornada} AND fabrica = {$login_fabrica};";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {

        $sql = "DELETE FROM tbl_hd_jornada WHERE fabrica = {$login_fabrica} AND hd_jornada = {$hd_jornada};";
        $res = pg_query($con, $sql);

        if (!pg_last_error()) {
            $retorno = array("retorno" => "success");
        } else {
            $retorno = array("retorno" => utf8_encode("Erro ao deletar registro."));
        }
    } else {
        $retorno = array("retorno" => utf8_encode("Registro não encontrado."));
    }

    exit(json_encode($retorno));

}

if ($_REQUEST['btn_acao'] == 'gravar') {

    $form['estado'] = $_REQUEST['estado'];
    $form['cidade'] = $_REQUEST['cidade'];
    $form['produto_referencia'] = $_REQUEST['referencia'];
    $form['produto_descricao'] = $_REQUEST['descricao'];

    if ($login_fabrica == 175){
        $form['peca_referencia']    = $_REQUEST['peca_referencia'];
        $form['peca_descricao']     = $_REQUEST['peca_descricao'];
        $form['serie_peca']         = $_REQUEST['serie_peca'];
        $form['numero_serie']       = $_REQUEST['numero_serie'];
        $form['data_inicial']       = $_REQUEST['data_inicial'];
        $form['data_final']         = $_REQUEST['data_final'];
        $form['motivo']             = $_REQUEST['motivo'];
        
        if (!empty($form["peca_referencia"]) OR !empty($form["peca_descricao"])){
            $sql = "
                SELECT peca
                FROM tbl_peca
                WHERE fabrica = {$login_fabrica}
                AND (
                    (UPPER(referencia) = UPPER('{$peca_referencia}')) OR (UPPER(descricao) = UPPER('{$peca_descricao}'))
                )";
            $res = pg_query($con ,$sql);

            if (!pg_num_rows($res)) {
                $msg_erro["msg"][]    = "Peça não encontrada";
                $msg_erro["campos"][] = "peca";
            } else {
                $peca = pg_fetch_result($res, 0, "peca");
            }
        }

        if (!empty($form['serie_peca'])){
            if (!empty($peca)){
                $sql = "SELECT numero_serie_peca 
                        FROM tbl_numero_serie_peca 
                        WHERE peca = {$peca}
                        AND serie_peca = '{$form['serie_peca']}'
                        AND fabrica = {$login_fabrica}";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) == 0){
                    $msg_erro["msg"][]    = "Número de série da peça não encontrado";
                    $msg_erro["campos"][] = "serie_peca";
                }else{
                    $numero_serie_peca = pg_fetch_result($res, 0, 'numero_serie_peca');
                }
            }else{
                $msg_erro["msg"][]    = "É obrigatório a peça e o número de série da peça para realizar o cadastro.";
                $msg_erro["campos"][] = "peca";
            }
        }

        if (empty($form["motivo"])){
            $msg_erro["campos"][] = "motivo";
        }

        if (empty($form['data_inicial']) OR empty($form['data_final'])) {
            $msg_erro["campos"][] = "data";
        } else {
            list($di, $mi, $yi) = explode("/", $form['data_inicial']);
            list($df, $mf, $yf) = explode("/", $form['data_final']);

            if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
                $msg_erro["msg"][]    = "Data Inválida";
                $msg_erro["campos"][] = "data";
            } else {
                $aux_data_inicial = "{$yi}-{$mi}-{$di}";
                $aux_data_final   = "{$yf}-{$mf}-{$df}";

                if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
                    $msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
                    $msg_erro["campos"][] = "data";
                }
            }
        }
    }

    if (!empty($form['cidade'])){
        $sqlCidade = "
            SELECT DISTINCT *
            FROM (
                    SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER('{$form['cidade']}')
                UNION (
                    SELECT cod_ibge FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER('{$form['cidade']}')
                )) AS cidade
            ORDER BY cidade ASC;
        ";
        $resCidade = pg_query($con, $sqlCidade);

        if (pg_num_rows($resCidade) == 0 || strlen(pg_last_error()) > 0) {
            $msg_erro['msg'][] = "Ocorreu um problema na seleção da cidade.";
            $msg_erro['campos'][] = "cidade";
        } else {
            $idCidade = pg_fetch_result($resCidade, 0, cidade);
        }
    }
    
    if (!empty($form['produto_referencia'])){
        $sql = "
            SELECT
                produto
            FROM tbl_produto
            WHERE fabrica_i = {$login_fabrica}
            AND referencia = '{$form['produto_referencia']}';
        ";

        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {
            $produto = pg_fetch_result($res, 0, produto);
        } else {
            $msg_erro['msg'][] = "O produto selecionado não foi encontrado";
            $msg_erro['campos'][] = 'produto';
        }
    }

    if ($login_fabrica == 175) {
        if (!empty($form['numero_serie'])){
            if (!empty($produto)){
                $sql = "SELECT numero_serie 
                        FROM tbl_numero_serie
                        WHERE produto =  {$produto}
                        AND serie     = '{$form['numero_serie']}'
                        AND fabrica   =  {$login_fabrica}";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) == 0){
                    $msg_erro["msg"][]    = "Número de série do produto não encontrado";
                    $msg_erro["campos"][] = "serie_peca";
                }else{
                    $numero_serie_produto = pg_fetch_result($res, 0, 'numero_serie');
                }
            }
        }
    }

    if ($login_fabrica != 175){
        if (empty($form['estado']) AND empty($form['cidade']) AND empty($form['produto_referencia'])){
            $msg_erro['msg'][] = "Erro ao gravar jornada, selecione um campo para efeturar o cadastro.";
            $msg_erro['campos'][] = 'produto';
            $msg_erro['campos'][] = 'cidade';
            $msg_erro['campos'][] = 'estado';
        }
    }else{
        if (empty($form['estado']) AND empty($form['cidade']) AND empty($form['produto_referencia']) AND empty($form['peca_referencia'])){
            $msg_erro['msg'][] = "Erro ao gravar jornada, selecione um campo para efeturar o cadastro.";
            $msg_erro['campos'][] = 'produto';
            $msg_erro['campos'][] = 'cidade';
            $msg_erro['campos'][] = 'estado';
            $msg_erro['campos'][] = 'peca';
        }
    }

    if ($login_fabrica == 175 AND count($msg_erro['campos']) > 0 AND (in_array("data", $msg_erro['campos']) OR in_array("motivo", $msg_erro['campos']))){
        $msg_erro["msg"][] = "Preencha os campos obrigatórios";
    }

    if (count($msg_erro['msg']) == 0) {

        if (strlen(trim($cidade)) > 0){
            $cond_cidade = " AND cidade = {$idCidade} ";
        }

        if (strlen(trim($produto)) > 0){
            $cond_produto = " AND produto = {$produto} AND estado IS NULL AND cidade IS NULL";
        }

        if ($login_fabrica == 175){
            if (!empty($peca)){
                $cond_peca = " AND tbl_hd_jornada.peca = {$peca} ";
            }

            if (!empty($serie_peca)){
                $cond_serie_peca = " AND tbl_hd_jornada.numero_serie_peca = {$numero_serie_peca} ";
            }

            if (!empty($aux_data_inicial) AND !empty($aux_data_final)){
                $cond_data = " AND tbl_hd_jornada.data_inicio = '{$aux_data_inicial}' AND tbl_hd_jornada.data_fim = '{$aux_data_final}' ";
            }

			if(!empty($numero_serie_produto)) {
                $cond_serie_produto = " AND tbl_hd_jornada.numero_serie_produto = {$numero_serie_produto} ";
			}
        }

        if (!empty($form['estado']) AND !empty($cidade)){
            $cond_estado = " AND estado = '{$estado}' ";
        }else if(!empty($form['estado']) AND empty($cidade)){
            $cond_estado = " AND estado = '{$estado}' AND cidade IS NULL ";
        }

        $sql = "
            SELECT *
            FROM tbl_hd_jornada
            WHERE fabrica = {$login_fabrica}
            $cond_cidade
            $cond_produto
            $cond_estado
            $cond_peca
            $cond_serie_peca
            $cond_data
			$cond_serie_produto
        ";

        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {
            $msg_erro['msg'][] = "Jornada já cadastrada.";
        } else {
            if (strlen(trim($cidade)) > 0){
                $campo_cidade = " , cidade ";
                $value_cidade = " , {$idCidade} ";
            }

            if (strlen(trim($produto)) > 0){
                $campo_produto = " , produto ";
                $value_produto = " , {$produto} ";
            }

            if (!empty($form['motivo'])){
                $campo_motivo = " , motivo ";
                $value_motivo = " ,'{$form['motivo']}' ";
            }

            if (!empty($form['estado'])){
                $campo_estado = " , estado ";
                $value_estado = " , '{$form['estado']}' ";
            }

            if ($login_fabrica == 175){
                if (!empty($peca)){
                    $campo_peca = ", peca ";
                    $value_peca = ", $peca ";
                }

                if (!empty($serie_peca)){
                    $campo_serie_peca = ", numero_serie_peca ";
                    $value_serie_peca = ", $numero_serie_peca ";
                }

                if (!empty($numero_serie_produto)){
                    $campo_serie_produto = ", numero_serie_produto ";
                    $value_serie_produto = ", $numero_serie_produto ";
                }

                if (!empty($aux_data_inicial) AND !empty($aux_data_final)){
                    $campo_datas = ", data_inicio , data_fim";
                    $value_datas = ", '$aux_data_inicial' , '$aux_data_final' ";
                }
            }

            $sql = "
                INSERT INTO tbl_hd_jornada (fabrica {$campo_cidade} {$campo_produto} {$campo_estado} {$campo_peca} {$campo_serie_peca} {$campo_datas} {$campo_motivo} {$campo_serie_produto})
                VALUES ({$login_fabrica} {$value_cidade} {$value_produto} {$value_estado} {$value_peca} {$value_serie_peca} {$value_datas} {$value_motivo} {$value_serie_produto});
            ";
            $res = pg_query($con,$sql);

            if (strlen(pg_last_error()) > 0) {
                $msg_erro['msg'][] = "Ocorreu um erro cadastrando jornada";
            } else {
                $msg = "Jornada cadastrada com sucesso";
                unset($form);
            }
        }
    }

}

$title = "CADASTRO DE JORNADAS";

include "cabecalho_new.php";

#unset($form);

$plugins = array(
    "tooltip",
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "select2",
    "dataTable"
);

include ("plugin_loader.php");

if (count($msg_erro['msg']) > 0) { ?>
	<div class="alert alert-error">
		<h4><?= implode('<br/>', $msg_erro['msg']); ?></h4>
	</div>
<? }

if (strlen($msg) > 0) { ?>
	<div class="alert alert-success">
		<h4><?= $msg; ?></h4>
	</div>
<? } ?>

<br/>
<div class="row">
	<b class="obrigatorio pull-right">* Campos obrigatórios</b>
</div>
<form class='form-search form-inline tc_formulario' name="frm_familia" method="post" action="<?= $PHP_SELF; ?>">
	<input type="hidden" name="hd_jornada" value="<?= $hd_jornada; ?>" />
	<div class="titulo_tabela">Cadastro de Jornada</div>
	<br />
    <?php if ($login_fabrica == 175){ ?>
        <div class='row-fluid'>
            <div class='span2'></div>
                <div class='span4'>
                    <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='data_inicial'>Data Inicial</label>
                        <div class='controls controls-row'>
                            <div class='span4'>
                                <h5 class='asteristico'>*</h5>
                                <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$form['data_inicial']?>">
                            </div>
                        </div>
                    </div>
                </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_final'>Data Final</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <h5 class='asteristico'>*</h5>
                                <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$form['data_final']?>" >
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
    <?php } ?>
	<div class="row-fluid">

		<div class="span2"></div>
		<div class="span4">
            <div class="control-group <?= (in_array('estado', $msg_erro['campos'])) ? "error" : ""; ?>">
                <label class="control-label" for="estado">Estado</label>
                <div class="controls controls-row">
                    <div class="span11">
                        <select id="estado" name="estado" class="span12" >
                            <option value="" >Selecione</option>
                            <? foreach ($array_estados as $sigla => $nome_estado) {
                                $selected = ($sigla == $form['estado']) ? "selected" : ""; ?>
								<option value="<?= $sigla; ?>" <?= $selected; ?> ><?= $nome_estado; ?></option>
                            <? } ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="span4">
            <div class="control-group <?= (in_array('cidade', $msg_erro['campos'])) ? "error" : ""; ?>">
                <label class="control-label" for="cidade">Cidade</label>
                <div class="controls controls-row">
                    <div class="span11">
                        <select id="cidade" name="cidade" class="span12" >
                            <option value="" >Selecione</option>
                            <? if (strlen($form['estado']) > 0) {
                                $sql = "
                                	SELECT DISTINCT * FROM (
                                        SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('{$estado}')
                                        UNION (
                                            SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('{$estado}')
                                        )
                                    ) AS cidade
                                    ORDER BY cidade ASC;
                                ";

                                $res = pg_query($con,$sql);

                                if (pg_num_rows($res) > 0) {
                                    while ($result = pg_fetch_object($res)) {
                                        $selected = (trim($result->cidade) == trim($form['cidade'])) ? "SELECTED" : ""; ?>
                                        <option value="<?= $result->cidade; ?>" <?= $selected; ?> ><?= $result->cidade; ?></option>
                                    <? }
                                }
                            } ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
	    <div class="span2"></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?= (in_array('produto', $msg_erro['campos'])) ? "error" : ""; ?>'>
				<label class='control-label' for='produto_referencia'>Referência Produto</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" class="frm" id="produto_referencia" name="referencia" value="<?= $form['produto_referencia']; ?>" size="12" maxlength="20">
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?= (in_array('produto', $msg_erro['campos'])) ? "error" : ""; ?>'>
				<label class='control-label' for='produto_descricao'>Descrição Produto</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" class="frm" id="produto_descricao" name="descricao" value="<?= $form['produto_descricao']; ?>" size="40" maxlength="50">
						<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>

    <?php if ($login_fabrica == 175){ ?>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span4">
                <div class='control-group <?=(in_array("numero_serie", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='numero_serie'>Número de Série Produto</label>
                    <div class='controls controls-row'>
                        <div class='span7 input-append'>
                            <input type="text" id="numero_serie" name="numero_serie" maxlength="20" value="<?= $form['numero_serie'] ?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                            <input type="hidden" name="lupa_config" tipo="produto" parametro="numero_serie" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span4"></div>
            <div class="span2"></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='peca_referencia'>Referência Peças</label>
                    <div class='controls controls-row'>
                        <div class='span7 input-append'>
                            <input type="text" id="peca_referencia" name="peca_referencia" maxlength="20" value="<?= $form['peca_referencia'] ?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                            <input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='peca_descricao'>Descrição Peça</label>
                    <div class='controls controls-row'>
                        <div class='span7 input-append'>
                            <input type="text" id="peca_descricao" name="peca_descricao" value="<?= $form['peca_descricao'] ?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("serie_peca", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='serie_peca'>Número de Série Peça</label>
                    <div class='controls controls-row'>
                        <div class='span7 input-append'>
                            <input type="text" id="serie_peca" name="serie_peca" maxlength="20" value="<?= $form['serie_peca'] ?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                            <input type="hidden" name="lupa_config" tipo="peca" parametro="serie_peca" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'></div>
            <div class='span2'></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span8'>
                <div class='control-group <?=(in_array("motivo", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='serie_peca'>Motivo</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" class='span11' id="motivo" name="motivo" maxlength="100" value="<?= $form['motivo'] ?>" >
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
    <?php } ?>
	<br/>
	<div class="row-fluid">
		<div class="span4"></div>
		<div class="span4 tac">
			<button type="button" class="btn" onclick="submitForm($(this).parents('form'),'gravar');" alt="Gravar formulário" >Gravar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</div>
		<div class="span4"></div>
	</div>
	<br/>
</form>
</div>
<?
$sql = "
    SELECT
        hj.hd_jornada,
        hj.estado,
        TO_CHAR(hj.data_input, 'DD/MM/YYYY') AS data_input,
        c.nome AS cidade_nome,
        p.referencia||' - '||p.descricao AS produto,
        pc.referencia||' - '||pc.descricao AS peca,
        hj.motivo,
        np.serie_peca,
        ns.serie,
        TO_CHAR(hj.data_inicio, 'DD/MM/YYYY') as data_inicio,
        TO_CHAR(hj.data_fim, 'DD/MM/YYYY') as data_fim
    FROM tbl_hd_jornada hj
    LEFT JOIN tbl_cidade c USING(cidade)
    LEFT JOIN tbl_produto p ON p.produto = hj.produto AND p.fabrica_i = {$login_fabrica}
    LEFT JOIN tbl_peca pc ON pc.peca = hj.peca AND pc.fabrica = {$login_fabrica}
    LEFT JOIN tbl_numero_serie_peca np ON np.numero_serie_peca = hj.numero_serie_peca AND np.fabrica = {$login_fabrica}
    LEFT JOIN tbl_numero_serie ns ON ns.numero_serie = hj.numero_serie_produto AND ns.fabrica = {$login_fabrica}
    WHERE hj.fabrica = {$login_fabrica} ";
$res = pg_query($con,$sql);
$cont = pg_num_rows($res);

if ($cont > 0) { ?>
    <div class="container-fluid">
    <table id="resultado" class='table table-striped table-bordered table-hover table-fixed'>
    	<thead>
    		<tr class='titulo_tabela'>
                <?php 
                    if (in_array($login_fabrica, [175])) { 
                        $colspanTitle = '11';
                    } else {
                        $colspanTitle = '10';
                    } 
                ?>
    			<th colspan='<?=$colspanTitle;?>'>Relação das Jornadas Cadastradas</th>
    		</tr>
    		<tr class='titulo_coluna'>
                <?php if ($login_fabrica == 175){ ?>
                    <th>Data do Cadastro</th>
                    <th>Data Inicial</th>
                    <th>Data Final</th>
                    <th>Estado</th>
                    <th>Cidade</th>
                    <th>Produto</th>
                    <th>Número série produto</th>
                    <th>Peça</th>
                    <th>Número série peça</th>
                    <th>Motivo</th>
                    <th>Opções</th>
                <?php } else {?>
        			<th>Estado</th>
                    <th>Cidade</th>
        			<th>Produto</th>
        			<th>Opções</th>
                <?php } ?>
    		</tr>
    	</thead>
    	<tbody>
        <? for ($i = 0; $i < $cont; $i++) {
            $resEstado       = pg_fetch_result($res, $i, estado);
            $resHdJornada    = pg_fetch_result($res, $i, hd_jornada);
            $resCidade       = pg_fetch_result($res, $i, cidade_nome);
            $resProduto      = pg_fetch_result($res, $i, produto); 
        
            $resDataInicial  = pg_fetch_result($res, $i, data_inicio);
            $resDataFinal    = pg_fetch_result($res, $i, data_fim);
            $resPeca         = pg_fetch_result($res, $i, peca);
            $resSeriePeca    = pg_fetch_result($res, $i, serie_peca);
            $resSerieProduto = pg_fetch_result($res, $i, serie);
            $resMotivo       = pg_fetch_result($res, $i, motivo);
            $resDataInput    = pg_fetch_result($res, $i, data_input);
        ?>
            <tr id="linha_<?= $resHdJornada; ?>">
                <?php if ($login_fabrica == 175){ ?>
                    <td class='tac'><?=$resDataInput?></td>
                    <td class='tac'><?= $resDataInicial; ?></td>
                    <td class='tac'><?= $resDataFinal; ?></td>
                <?php } ?>
                <td class='tac'><?= $resEstado; ?></td>
                <td><?= $resCidade; ?></td>
                <td><?= $resProduto; ?></td>

                <?php if ($login_fabrica == 175){ ?>
                    <td><?= $resSerieProduto; ?></td>
                    <td><?= $resPeca; ?></td>
                    <td><?= $resSeriePeca; ?></td>
                    <td><?= $resMotivo; ?></td>
                <?php } ?>
                <td class="tac"><button type="button" class="btn btn-danger btn-small excluir_jornada" data-hd-jornada="<?= $resHdJornada; ?>">Excluir</button></td>
            </tr>
        <? } ?>
    	</tbody>
    </table>
<? } else { ?>
    <div class="alert">
        <h4>Nenhuma jornada encontrada</h4>
    </div>
<? } ?>
    </div>
<script type="text/javascript">
$(function() {
	$.autocompleteLoad(Array("produto"));
    
    $("#data_inicial").datepicker({dateFormat: "dd/mm/yy", minDate: 0 }).mask("99/99/9999");
    $("#data_final").datepicker({dateFormat: "dd/mm/yy", minDate: 0 }).mask("99/99/9999");


    Shadowbox.init();

	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});

    $('#estado').select2();
    $('#cidade').select2();

	/**
     * Evento para quando alterar o estado carregar as cidades do estado
     */
    $("#estado").change(function() {
        busca_cidade($(this).val());
    });

    $(".excluir_jornada").click(function() {
        if (confirm('Deseja excluir o registro ?')) {
            var btn = $(this);
            var text = $(this).text();
            var hd_jornada = $(btn).data('hd-jornada');
            $(btn).prop({disabled: true}).text("Excluindo...");
            $.ajax({
                url: "jornada_cadastro.php",
                type: "POST",
                data: { ajax_excluir_jornada: true, hd_jornada: hd_jornada},
                timeout: 8000
            }).fail(function(){
                alert("Não foi possível excluir o registro, tempo limite esgotado!");
            }).done(function(data) {
                data = $.parseJSON(data);
                if (data.retorno == "success") {
                    $(btn).text("Excluido");
                    $('#linha_'+hd_jornada).remove();
                }else{
                    $(btn).prop({disabled: false}).text(text);
                }
            });
        }else{
            return false;
        }
    });
});

$.dataTableLoad({ table: "#resultado" });

function retorna_produto (retorno) {
    $("#produto_referencia").val(retorno.referencia);
    $("#produto_descricao").val(retorno.descricao);
}

function retorna_peca(retorno){
    $("#peca_referencia").val(retorno.referencia);
    $("#peca_descricao").val(retorno.descricao);
}

/**
 * Função que busca as cidades do estado e popula o select cidade
 */
function busca_cidade(estado, cidade) {
    $("#cidade").find("option").first().nextAll().remove();

    if (estado.length > 0) {
        $.ajax({
            url: "jornada_cadastro.php",
            type: "POST",
            timeout: 60000,
            data: { ajax_busca_cidade: true, estado: estado },
            beforeSend: function() {
                if ($("#cidade").next("img").length == 0) {
                    $("#cidade").hide().after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
                }
            },
            complete: function(data) {
                data = $.parseJSON(data.responseText);

                if (data.error) {
                    alert(data.error);
                } else {
                    $.each(data.cidades, function(key, value) {
                        var option = $("<option></option>", { value: value, text: value });
                        $("#cidade").append(option);
                    });
                }

                $("#cidade").show().next().remove();
            }
        });
    }

    if(typeof cidade != "undefined" && cidade.length > 0){
        $("#cidade option[value='"+cidade+"']").attr('selected','selected');
    }

}
</script>
<? include "rodape.php"; ?>
