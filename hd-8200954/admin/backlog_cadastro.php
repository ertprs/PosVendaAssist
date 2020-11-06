<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$layout_menu       = 'callcenter';
$admin_privilegios = 'cadastro';

include 'autentica_admin.php';
include '../token_cookie.php';

include_once 'funcoes.php';

$token_cookie = $_COOKIE['sess'];

$cookie_login = get_cookie_login($token_cookie);

$grupo_admin     = $cookie_login['cook_grupo_admin'];
$acesso_cadastro = array(1,2,6,7);

function verificaHDBackLog($backlog, $hd) {

    if (!empty($backlog) AND !empty($hd)) {

        $sql = "SELECT DISTINCT backlog FROM tbl_backlog_item WHERE backlog != '$backlog' AND hd_chamado = $hd;";
        $res = @pg_query($sql);

        if (pg_num_rows($res)) {

            for ($i = 0; $i < pg_num_rows($res); $i++) {

                $backlog_anterior .= pg_fetch_result($res, $i, 0).", ";    
            }

            echo " title = 'BackLog: ". substr($backlog_anterior,0,-2)."' style='color: #F00' ";

        }            

    }

}

function verificaBackLog($backlog, $hd) {

    if (!empty($backlog) AND !empty($hd)) {

        $sql = "SELECT DISTINCT backlog FROM tbl_backlog_item WHERE backlog != '$backlog' AND hd_chamado = $hd;";
        $res = @pg_query($sql);

        if (pg_num_rows($res)) {

        }

    }

}

header('Content-Type: text/html; charset=iso-8859-1');

/* HD 756560 - AJAX Tabela detalhe Horas Trabalhadas */
if ($_GET['ajax'] == 'detHT') {

    $hd_chamadoHT = $_GET['hd_chamado'];

	include '../helpdesk/mlg_funciones.php';

    $sqlx = "SELECT tbl_admin.login,
                    TO_CHAR(tbl_hd_chamado_atendente.data_inicio,'DD/MM/YYYY hh24:mi:ss') as inicio,
                    TO_CHAR(tbl_hd_chamado_atendente.data_termino,'hh24:mi:ss') as fim,
                    DATE_TRUNC('second', (data_termino - DATE_TRUNC('second', data_inicio::time))::time) AS intervalo
            FROM tbl_hd_chamado_atendente
            JOIN tbl_admin USING(admin)
            WHERE hd_chamado = $hd_chamadoHT
            AND grupo_admin = 4
            ORDER BY tbl_hd_chamado_atendente.data_inicio";

    $sqlw = "SELECT SUM( termino - data_inicio )
	FROM tbl_hd_chamado_atendente
	JOIN tbl_admin using(admin)
	JOIN tbl_hd_chamado_item USING(hd_chamado,admin) 
	WHERE tbl_hd_chamado_atendente.hd_chamado = $hd_chamadoHT
	AND  data = data_inicio
	AND  data_inicio NOTNULL
	AND  termino NOTNULL
            AND grupo_admin = 4";

    $resx = @pg_query($con, $sqlx);

	if (!is_resource($resx)) die ($sqlx . '<br />' . pg_last_error($con));

    $resw = @pg_query($con, $sqlw);

	$referer = basename($_SERVER['HTTP_REFERER']);
	if ($referer != basename(__FILE__))
		echo "<button type='button' id='fechar_msg' class='btn'>Fechar</button>";

    if (pg_num_rows($resx)) {
		$tableAttrs = array(
			'tableAttrs' => 'data-toggle="table" class="table table-condensed table-bordered table-hover table-striped "'
		);
        $total_horas = substr(pg_fetch_result($resw, 0, 0), 0, 8);
        die(array2table(pg_fetch_all($resx), "HD nº $hd_chamadoHT &ndash; Total de horas trabalhadas: $total_horas"));
    }

    die("<p>Não há registro de horas trabalhadas para o HD nº $hd_chamadoHT</p>");

} // FIM AJAX Horas Trabalhadas

/* Request para excluir */
if (isset($_GET['excluir'])) {

    $backlog = (int) $_GET['excluir'];

    if (!empty($backlog)) {

        $sql = "DELETE FROM tbl_backlog WHERE backlog = $backlog";
        $res = @pg_query($con, $sql);
        $msg_erro = pg_errormessage($con);

        if (empty($msg_erro)) {
            header('Location: ?msg=Excluído com Sucesso');
        } else if (substr($msg_erro, 'foreign key') ) {
            $msg_erro = "Backlog já possuí itens e não pode ser excluído";
        }

    }

}

if (isset($_POST['btn_acao']) && $_POST['btn_acao'] == 'submit') {

    $data_inicio = $_POST['data_inicio'];
    $data_fim    = $_POST['data_fim'];

    //Este trecho da validação é para verificar se os campos de data foram preenchidos.
    //Válido apenas para as telas que tornam obrigatório o preencimento das datas.
    if (empty($data_inicio) OR empty($data_fim)) {
        $msg_erro = "Data Inválida";
    }

    if (strlen($msg_erro) == 0) {

        list($di, $mi, $yi) = explode("/", $data_inicio);
        if (!checkdate($mi,$di,$yi)) $msg_erro = "Data Inválida";

    }

    if (strlen($msg_erro) == 0) {

        list($df, $mf, $yf) = explode("/", $data_fim);
        if (!checkdate($mf,$df,$yf)) $msg_erro = "Data Inválida";

    }

    if (strlen($msg_erro) == 0) {

        $aux_data_inicial = "$yi-$mi-$di";
        $aux_data_final   = "$yf-$mf-$df";

    }

    if (strlen($msg_erro) == 0) {//INTERVALO 30 DIAS

        if (strtotime($aux_data_inicial.'+1 month') < strtotime($aux_data_final)) {
            $msg_erro = 'O intervalo entre as datas não pode ser maior que 1 mês';
        }

    }

    if (strlen($msg_erro) == 0) {

        if (empty($qtde_horas) && $qtde_horas == 0) {
            $msg_erro = 'Quantidade de horas inválida';
        }

    }

    if (strlen($msg_erro) == 0) {

        if (empty($qtde_desenvolvedores) && $qtde_desenvolvedores == 0) {
            $msg_erro = 'Quantidade de desenvolvedores inválida';
        }

    }

    if (empty($msg_erro)) {

        pg_query($con, "BEGIN TRANSACTION");

        if (!empty($backlog)) {

            $sql = "SELECT backlog FROM tbl_backlog WHERE backlog = $backlog";
            $res = pg_query($con,$sql);

            if (pg_num_rows($res)) {

                $sql = "UPDATE    tbl_backlog
                         SET     data_inicio          = '$aux_data_inicial',
                                data_fim             = '$aux_data_final'  ,
                                qtde_horas           = '$qtde_horas'      ,
                                qtde_desenvolvedores = '$qtde_desenvolvedores'
                         WHERE    backlog = $backlog";

                $res = pg_query($con,$sql);
                $msg_erro = pg_errormessage($con);

                if (empty($msg_erro)) $msg = 'Gravado com Sucesso';

            } else {
                $msg_erro = 'Backlog não encontrado';
            }

        } else {

            $sql = "INSERT INTO tbl_backlog(
                        data_inicio         ,
                        data_fim            ,
                        qtde_horas          ,
                        qtde_desenvolvedores 
                    ) VALUES (
                        '$aux_data_inicial' ,
                        '$aux_data_final'   ,
                        '$qtde_horas'       ,
                        '$qtde_desenvolvedores'
                    )";

            $res = pg_query($con,$sql);
            $msg_erro = pg_errormessage($con);

            if (empty($msg_erro)) $msg = "Gravado com Sucesso";

        }

        if (empty($msg_erro)) {
            pg_query($con,"COMMIT");
        } else {
            pg_query($con,"ROLLBACK");
        }

    }

} else if ($acao == 'salvar') {

    $prioridade = utf8_decode($prioridade);
    $faturado   = utf8_decode($faturado);
    $msg_erro   = '';

    //VALIDACOES
    if (empty($hd_chamado)) {
        $msg_erro .= "Chamado é um campo obrigatório.\n";
    } else {

        $sql_hd = "SELECT hd_chamado FROM tbl_hd_chamado WHERE hd_chamado = $hd_chamado";
        $res_hd = pg_query($con, $sql_hd);

        if (!pg_num_rows($res_hd)) $msg_erro .= "Chamado não encontrado.\n";

    }

    if (empty($projeto)) {

        $msg_erro .= "Projeto é um campo obrigatório.\n";

    } else {

        $sql_projeto = "SELECT projeto FROM tbl_projeto WHERE projeto = $projeto";
        $res_projeto = pg_query($con, $sql_projeto);

        if (!pg_num_rows($res_projeto)) $msg_erro .= "Projeto não encontrado.\n";

    }

    if (empty($prioridade)) $msg_erro .= "Prioridade é um campo obrigatório.\n";

    if (empty($faturado)) $msg_erro .= "Faturado é um campo obrigatório.\n";
    else if (!in_array($faturado, array('t','f'))) $msg_erro .= "Faturado não encontrada.\n";

    if (empty($fator_hora)) $msg_erro .= "Fator Hora é um campo obrigatório.\n";
    else if (is_int($fator_hora)) $msg_erro .= "Fator Hora inválido.\n";

    if (empty($analista)) {
        //$msg_erro .= "Analista é um campo obrigatório.\n";
        $analista = 'null';
    } else {

        $sql_analista = "SELECT admin FROM tbl_admin WHERE admin = $analista";
        $res_analista = pg_query($con, $sql_analista);
        if (!pg_num_rows($res_analista)) $msg_erro .= "Analista não encontrado.\n";

    }

    if (empty($desenvolvedor)) {
        //$msg_erro .= "Desenvolvedor é um campo obrigatório.\n";
        $desenvolvedor = 'null';
    } else {

        $sql_desenvolvedor = "SELECT admin FROM tbl_admin WHERE admin = $desenvolvedor";
        $res_desenvolvedor = pg_query($con, $sql_desenvolvedor);
        if (!pg_num_rows($res_desenvolvedor)) $msg_erro .= "Desenvolvedor não encontrado.\n";

    }

    if (empty($suporte)) {
        //$msg_erro .= "Desenvolvedor é um campo obrigatório.\n";
        $suporte = 'null';
    } else {

        $sql_suporte = "SELECT admin FROM tbl_admin WHERE admin = $suporte";
        $res_suporte = pg_query($con, $sql_suporte);

        if (!pg_num_rows($res_suporte)) $msg_erro .= "suporte não encontrado.\n";

    }

    if (empty($msg_erro)) {//OPERACOES

        pg_query($con, "BEGIN TRANSACTION");

        if (!empty($backlog_item)) {

            $sql = "SELECT backlog_item FROM tbl_backlog_item WHERE backlog_item = $backlog_item";
            $res = pg_query($con,$sql);

            if (pg_num_rows($res)) {

				$chamado_causador = ($_POST['chamado_causador']) ? $_POST['chamado_causador'] : 'null';
				$setor_causador   = ($_POST['setor_causador']) ? $_POST['setor_causador'] : 'null';

                $sql = "UPDATE tbl_backlog_item
                         SET   hd_chamado       = '$hd_chamado'     ,
                               prioridade       = '$prioridade'     ,
                               faturado         = '$faturado'       ,
                               horas_analisadas = $horas_analisadas ,
                               horas_faturadas  = $horas_faturadas  ,
                               horas_utilizadas = $horas_utilizadas ,
                               backlog          = '$backlog'        ,
                               analista         = $analista         ,
                               fator_hora       = $fator_hora       ,
                               desenvolvedor    = $desenvolvedor    ,
                               suporte          = $suporte          ,
                               admin_alterou    = '$login_admin'    ,
                               projeto          = '$projeto'        ,
							   chamado_causador = $chamado_causador ,
							   grupo_admin      = $setor_causador
                         WHERE backlog_item = $backlog_item";

                $res      = pg_query($con, $sql);
                $msg_erro = pg_errormessage($con);

                if (empty($msg_erro)) $msg = 'Gravado com Sucesso';

            } else {

                $msg_erro = 'O Item do Backlog não foi encontrado';

            }

        } else {

            //verifica se o hd já existe neste backlog
            $sql = "SELECT backlog_item FROM tbl_backlog_item WHERE hd_chamado = '$hd_chamado' AND backlog = '$backlog' ;";
            $res = pg_query($con,$sql);

            if (pg_num_rows($res) > 0) $msg_erro = "HD $hd_chamado, já cadastro neste Backlog";

            $sql = "INSERT INTO tbl_backlog_item (
                        backlog          ,
                        hd_chamado       ,
                        prioridade       ,
                        fator_hora       ,
                        horas_analisadas ,
                        horas_faturadas  ,
                        horas_utilizadas ,
                        faturado         ,
                        admin            ,
                        analista         ,
                        desenvolvedor    ,
                        suporte          ,
                        projeto
                    ) VALUES (
                        '$backlog'        ,
                        '$hd_chamado'     ,
                        '$prioridade'     ,
                        $fator_hora       ,
                        $horas_analisadas ,
                        $horas_faturadas  ,
                        $horas_utilizadas ,
                        '$faturado'       ,
                        '$login_admin'    ,
                        $analista         ,
                        $desenvolvedor    ,
                        $suporte        ,
                        '$projeto'
                    )";

            $res = pg_query($con,$sql);

            if (empty($msg_erro)) $msg_erro = pg_errormessage($con);
            if (empty($msg_erro)) $msg      = "Gravado com Sucesso";

        }

        if (empty($msg_erro)) {
            pg_query($con,"COMMIT");
            echo '1|'.$msg;
        } else {
            pg_query($con,"ROLLBACK");
            echo '0|'.($msg_erro);
        }

    } else {

        echo '0|'.($msg_erro);

    }

    die;

} else if ($acao == 'inserir') {

	if ($tipo == 'cadastrar') {

        	if (!empty($backlog_item)) {
		$sql = "SELECT tbl_backlog_item.backlog_item, 
                           tbl_backlog_item.backlog,
                           tbl_backlog_item.hd_chamado,
                           tbl_backlog_item.prioridade,
                           tbl_backlog_item.faturado,
                           tbl_backlog_item.fator_hora,
                           tbl_backlog_item.projeto,
                           tbl_backlog_item.horas_analisadas,
                           tbl_backlog_item.horas_faturadas,
                           tbl_backlog_item.horas_utilizadas,
                           tbl_backlog_item.analista,
                           tbl_hd_chamado.fabrica,
                           tbl_hd_chamado.tipo_chamado,
                           tbl_hd_chamado.status,
                           tbl_backlog_item.desenvolvedor,
                           tbl_backlog_item.suporte,
                           tbl_backlog_item.fator_hora,
						   tbl_backlog_item.causador,
						   tbl_backlog_item.grupo_admin,
						   tbl_backlog_item.chamado_causador
                      FROM tbl_backlog_item
                      JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_backlog_item.hd_chamado
                     WHERE backlog_item = $backlog_item
                     ORDER BY backlog_item";

            $res = pg_query($con, $sql);

            if (pg_num_rows($res)) {
	            $vet = @pg_fetch_assoc($res);
				$vet = array_map('utf8_encode',$vet);
	            echo json_encode($vet);

	   }
        }
    } else if ($tipo == 'listar_tipo_status') {

		$aux_dev  = ($linha != 0) ? (' AND tbl_backlog_item.desenvolvedor = ' . $linha) : '';
        $sql_tipo = "SELECT COUNT(tbl_hd_chamado.tipo_chamado) as total,
                            tbl_tipo_chamado.descricao
                       FROM tbl_backlog_item
                       JOIN tbl_hd_chamado   ON tbl_hd_chamado.hd_chamado     = tbl_backlog_item.hd_chamado
                       JOIN tbl_tipo_chamado ON tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
                      WHERE tbl_backlog_item.backlog = $backlog
					  $aux_dev
                   GROUP BY tbl_tipo_chamado.descricao";

        $res_tipo = pg_query($con, $sql_tipo);
        $tot_tipo = pg_num_rows($res_tipo);

        if ($tot_tipo) {

            echo '<tr><td>';

                echo '<table width="100%" height="100%">';

                for ($x = 0; $x < $tot_tipo; $x++) {

                    $cor        = $x % 2 ? '#CCC' : '#EEE';
                    $total_tipo = pg_result($res_tipo, $x, 'total');
                    $desc_tipo  = pg_result($res_tipo, $x, 'descricao');

                    echo '<tr>';
                        echo '<td style="background-color:'.$cor.'" align="center">'.$desc_tipo.'</td>';
                        echo '<td style="background-color:'.$cor.'" align="center">'.$total_tipo.'</td>';
                    echo '</tr>';

                }

                echo '</table>';

            echo '</td>';

        }

        $sql_status = "SELECT COUNT(tbl_hd_chamado.status) as total,
                              tbl_hd_status.status
                         FROM tbl_backlog_item
                         JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_backlog_item.hd_chamado
                         JOIN tbl_hd_status  ON tbl_hd_status.status      = tbl_hd_chamado.status       AND tbl_hd_status.fabrica = 10
                        WHERE tbl_backlog_item.backlog = $backlog
					    $aux_dev
                     GROUP BY tbl_hd_status.status";

        $res_status = pg_query($con, $sql_status);
        $tot_status = pg_num_rows($res_status);

        if ($tot_status) {

            echo '<td>';

                echo '<table width="100%">';

                for ($x = 0; $x < $tot_status; $x++) {

                    $cor          = $x % 2 ? '#CCC' : '#EEE';
                    $total_status = pg_result($res_status, $x, 'total');
                    $desc_status  = pg_result($res_status, $x, 'status');

                    echo '<tr>';
                        echo '<td style="background-color:'.$cor.'" align="center">'.$desc_status.'</td>';
                        echo '<td style="background-color:'.$cor.'" align="center">'.$total_status.'</td>';
                    echo '</tr>';

                }

                echo '</table>';

            echo '</td></tr>';

        }

        die;

    } else if ($tipo == 'listar') {

        if ($grupo_admin == 4) {
            $sql_add = " AND desenvolvedor = $login_admin ";
        }

        $sql = "SELECT tbl_backlog_item.backlog_item, 
                       tbl_backlog_item.backlog,
                       tbl_backlog_item.hd_chamado,
                       tbl_backlog_item.prioridade,
                       tbl_backlog_item.faturado,
                       tbl_backlog_item.fator_hora,
                       tbl_backlog_item.projeto,
                       tbl_backlog_item.horas_analisadas,
                       tbl_backlog_item.horas_faturadas,
                       tbl_backlog_item.horas_utilizadas,
                       tbl_backlog_item.analista,
                       tbl_hd_chamado.fabrica,
                       tbl_hd_chamado.tipo_chamado,
                       tbl_hd_chamado.status,
                       tbl_backlog_item.desenvolvedor,
                       tbl_backlog_item.suporte,
                       tbl_backlog_item.fator_hora,
					   tbl_backlog_item.causador,
					   tbl_backlog_item.grupo_admin,
					   tbl_backlog_item.chamado_causador,
                       tbl_backlog_item.impresso
                  FROM tbl_backlog_item
                  JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_backlog_item.hd_chamado
                 WHERE backlog = $backlog $sql_add
                UNION
                SELECT tbl_backlog_item.backlog_item, 
                       tbl_backlog_item.backlog,
                       tbl_backlog_item.hd_chamado,
                       tbl_backlog_item.prioridade,
                       tbl_backlog_item.faturado,
                       tbl_backlog_item.fator_hora,
                       tbl_backlog_item.projeto,
                       tbl_backlog_item.horas_analisadas,
                       tbl_backlog_item.horas_faturadas,
                       tbl_backlog_item.horas_utilizadas,
                       tbl_backlog_item.analista,
                       tbl_hd_chamado.fabrica,
                       tbl_hd_chamado.tipo_chamado,
                       tbl_hd_chamado.status,
                       tbl_backlog_item.desenvolvedor,
                       tbl_backlog_item.suporte,
                       tbl_backlog_item.fator_hora,
					   tbl_backlog_item.causador,
					   tbl_backlog_item.grupo_admin,
					   tbl_backlog_item.chamado_causador,
                       tbl_backlog_item.impresso
                  FROM tbl_backlog_item
                  JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_backlog_item.hd_chamado
                 WHERE backlog < $backlog
                 AND   tbl_hd_chamado.status NOT IN('Resolvido','Efetivação','Cancelado','Suspenso')
                 $sql_add 
                 ORDER BY backlog_item ";

        $res = pg_query($con, $sql);
        $tot = pg_num_rows($res);

        $sql_proj = "SELECT * FROM tbl_projeto;";
        $res_proj = pg_query($con, $sql_proj);
        $tot_proj = pg_num_rows($res_proj);
        $vet_proj = array();

        $sql_tipo = "SELECT * FROM tbl_tipo_chamado ORDER BY descricao;";
        $res_tipo = pg_query($con, $sql_tipo);
        $tot_tipo = pg_num_rows($res_tipo);
        $vet_tipo = array();

        $sql_admin = "SELECT * FROM tbl_admin WHERE fabrica = 10 AND ativo IS TRUE ORDER BY nome_completo;";
        $res_admin = pg_query($con, $sql_admin);
        $tot_admin = pg_num_rows($res_admin);
        $vet_admin = array();

        $sql_fabrica = "SELECT * FROM tbl_fabrica where ativo_fabrica ORDER BY nome;";
        $res_fabrica = pg_query($con, $sql_fabrica);
        $tot_fabrica = pg_num_rows($res_fabrica);
        $vet_fabrica = array();

        for ($x = 0; $x < $tot_proj; $x++) {
            $vet_proj[$x]['projeto'] = pg_result($res_proj, $x, 'projeto');
            $vet_proj[$x]['nome']    = pg_result($res_proj, $x, 'nome');
        }

        for ($x = 0; $x < $tot_tipo; $x++) {
            $vet_tipo[$x]['tipo'] = pg_result($res_tipo, $x, 'tipo_chamado');
            $vet_tipo[$x]['desc'] = pg_result($res_tipo, $x, 'descricao');
        }

        for ($x = 0; $x < $tot_fabrica; $x++) {
            $vet_fabrica[$x]['fabrica'] = pg_result($res_fabrica, $x, 'fabrica');
            $vet_fabrica[$x]['nome']    = pg_result($res_fabrica, $x, 'nome');
        }

        for ($x = 0; $x < $tot_admin; $x++) {
            $vet_admin[$x]['admin'] = pg_result($res_admin, $x, 'admin');
            $vet_admin[$x]['nome']  = pg_result($res_admin, $x, 'nome_completo');
        }

        $vet_prio = array('Alta', 'Média', 'Baixa');

        $vet_faturado[0]['valor'] = 't';
        $vet_faturado[0]['desc']  = 'Sim';
        $vet_faturado[1]['valor'] = 'f';
        $vet_faturado[1]['desc']  = 'Não';

        for ($i = 0; $i < $tot; $i++) {

            $backlog_item     = ($i < $tot) ? pg_result($res, $i, 'backlog_item')      : '';
            $id_backlog       = ($i < $tot) ? pg_result($res, $i, 'backlog')           : '';
            $hd_chamado       = ($i < $tot) ? pg_result($res, $i, 'hd_chamado')        : '';
            $prioridade       = ($i < $tot) ? pg_result($res, $i, 'prioridade')        : '';
            $faturado         = ($i < $tot) ? pg_result($res, $i, 'faturado')          : '';
            $fator_hora       = ($i < $tot) ? pg_result($res, $i, 'fator_hora')        : '';
            $horas_analisadas = ($i < $tot) ? pg_result($res, $i, 'horas_analisadas')  : '';
            $horas_faturadas  = ($i < $tot) ? pg_result($res, $i, 'horas_faturadas')   : '';
            $horas_utilizadas = ($i < $tot) ? pg_result($res, $i, 'horas_utilizadas')  : '';
            $tipo             = ($i < $tot) ? pg_result($res, $i, 'tipo_chamado')      : '';
            $fabrica          = ($i < $tot) ? pg_result($res, $i, 'fabrica')           : '';
            $projeto          = ($i < $tot) ? pg_result($res, $i, 'projeto')           : '';
            $analista         = ($i < $tot) ? pg_result($res, $i, 'analista')          : '';
            $status           = ($i < $tot) ? pg_result($res, $i, 'status')            : '';
            $desenvolvedor    = ($i < $tot) ? pg_result($res, $i, 'desenvolvedor')     : '';
            $suporte          = ($i < $tot) ? pg_result($res, $i, 'suporte')           : '';
            $statusResolvido  = ($status == 'Resolvido') ? ' class="resolvido oculto"' : '';
			$causador         = ($i < $tot) ? pg_result($res, $i, 'causador')          : '';
			//$grupo_admin      = ($i < $tot) ? pg_result($res, $i, 'grupo_admin')       : '';
			$chamado_causador = ($i < $tot) ? pg_result($res, $i, 'chamado_causador')  : '';
            $impresso    = strlen(pg_result($res, $i, 'impresso')) > 0 ? true : false;

			$status = ($horas_analisadas == 0 and $tipo == 5 and $desenvolvedor == "") ? "Erro sem analise" : $status;
			?>

            <tr title="<?=$status?>" id="ln_<?=$backlog_item;?>" <?=$statusResolvido?>>
                            <td align="center"><a href="../helpdesk/adm_chamado_detalhe.php?hd_chamado=<?=$hd_chamado?>" target="_blank" <?php verificaHDBackLog($backlog,$hd_chamado);?> ><?=$hd_chamado?></a></td>
                <td align="center"><?php
                    for ($y = 0; $y < count($vet_proj); $y++) {
                        echo ($vet_proj[$y]['projeto'] == $projeto) ? '<span onclick="buscarChamado('.$hd_chamado.')">'.$vet_proj[$y]['nome'].'</span>' : '';
                    }?>
                </td>
                <td align="center"><?php
                    for ($y = 0; $y < count($vet_fabrica); $y++) {
                        echo ($vet_fabrica[$y]['fabrica'] == $fabrica) ? '<span onclick="buscarChamado('.$hd_chamado.')">'.$vet_fabrica[$y]['nome'].'</span>' : '';
                    }?>
                </td>
                <td align="center"><?php
                    for ($y = 0; $y < count($vet_tipo); $y++) {
                        echo ($vet_tipo[$y]['tipo'] == $tipo) ? '<span onclick="buscarChamado('.$hd_chamado.')">'.$vet_tipo[$y]['desc'].'</span>' : '';
                    }?>
                </td>
                <td align="center"><span onclick="buscarChamado(<?=$hd_chamado?>)"><?=$status?></span></td>
                <td align="center"><?php
                    for ($y = 0; $y < count($vet_prio); $y++) {
                        echo ($vet_prio[$y] == $prioridade) ? '<span onclick="buscarChamado('.$hd_chamado.')">'.$vet_prio[$y].'</span>' : '';
                    }?>
                </td>
                <td align="center"><?php
                    for ($y = 0; $y < count($vet_faturado); $y++) {
                        echo ($vet_faturado[$y]['valor'] == $faturado) ? '<span onclick="buscarChamado('.$hd_chamado.')">'.$vet_faturado[$y]['desc'].'</span>' : '';
                    }?>
                </td>
                <td align="center"><span onclick="buscarChamado(<?=$hd_chamado?>)"><?=$fator_hora?></span></td>
                <td align="center"><span onclick="buscarChamado(<?=$hd_chamado?>)"><?=$horas_analisadas?></span></td>
                <td align="center"><span onclick="buscarChamado(<?=$hd_chamado?>)"><?=$horas_utilizadas?></span>
                <? if (in_array($login_admin,array(586,822,1553,1630,6835))) {?>
                    <img src="../imagens/chronometer.png" class='ht_detalhe' alt="<?=$hd_chamado?>" title='Detalhe de horas trabalhadas' height='20' /></td>
                <?}?>
                <td align="center"><span onclick="buscarChamado(<?=$hd_chamado?>)"><?=$horas_faturadas?></span></td>
                <td align="center"><?php
                    for ($y = 0; $y < count($vet_admin); $y++) {
                        echo ($vet_admin[$y]['admin'] == $analista) ? '<span onclick="buscarChamado('.$hd_chamado.')">'.$vet_admin[$y]['nome'].'</span>' : '';
                    }?>
                </td>
                <td align="center"><?php
                    for ($y = 0; $y < count($vet_admin); $y++) {
                        echo ($vet_admin[$y]['admin'] == $desenvolvedor) ? '<span onclick="buscarChamado('.$hd_chamado.')">'.$vet_admin[$y]['nome'].'</span>' : '';
                    }?>
                </td>
                <td align="center"><?php
                    for ($y = 0; $y < count($vet_admin); $y++) {
                        echo ($vet_admin[$y]['admin'] == $suporte) ? '<span onclick="buscarChamado('.$hd_chamado.')">'.$vet_admin[$y]['nome'].'</span>' : '';
                    }?>
                </td>
                <?php if(in_array($grupo_admin, $acesso_cadastro)){?>
                    <td align="center">
                        <button class='btn' type="button" name="excluir_<?=$i?>" id="excluir_<?=$i?>" onclick="excluirItem(<?=$backlog_item?>)">Excluir</button>
                    </td>
                <?php }?>
            </tr><?php

        }

    }

    die;

} else if ($acao == 'excluir') {

    $backlog_item = (int) $_REQUEST['backlog_item'];

    if (!empty($backlog_item)) {

        $sql = "DELETE FROM tbl_backlog_item WHERE backlog_item = $backlog_item";
        $res = @pg_query($con, $sql);

        $msg_erro = pg_errormessage($con);

        if (empty($msg_erro)) {

            echo '1|Item do backlog excluído com sucesso.';

        } else if (substr($msg_erro, 'foreign key')) {

            echo '0|'.$msg_erro;

        }

    } else {

        echo '0|Item do backlog não encontrado.';

    }

    die;

} else if ($acao == 'buscar') {

    $chamado = (int) $_REQUEST['chamado'];

    if (!empty($chamado)) {

	    $sql = "SELECT tbl_backlog_item.backlog_item, 
                           tbl_backlog_item.backlog,
                           tbl_hd_chamado.hd_chamado,
                           tbl_backlog_item.prioridade,
                           tbl_backlog_item.faturado,
                           tbl_backlog_item.fator_hora,
                           tbl_backlog_item.projeto,
                           tbl_backlog_item.horas_analisadas,
                           tbl_backlog_item.horas_faturadas,
                           tbl_backlog_item.horas_utilizadas,
                           tbl_backlog_item.analista,
                           tbl_hd_chamado.fabrica,
                           tbl_hd_chamado.tipo_chamado,
                           tbl_hd_chamado.status as status_fabrica,
                           tbl_backlog_item.desenvolvedor,
                           tbl_backlog_item.suporte,
                           tbl_backlog_item.fator_hora,
						   tbl_backlog_item.causador,
						   tbl_backlog_item.grupo_admin,
						   tbl_backlog_item.chamado_causador
                      FROM tbl_hd_chamado
                      LEFT JOIN tbl_backlog_item ON tbl_hd_chamado.hd_chamado = tbl_backlog_item.hd_chamado
                     WHERE tbl_hd_chamado.hd_chamado = $chamado
                     ORDER BY backlog_item desc limit 1";


        $res = @pg_query($con, $sql);

        if (@pg_num_rows($res)) {

            $vet = @pg_fetch_assoc($res);

            $vet['erro']           = '';
			$vet = array_map('utf8_encode',$vet);
            echo json_encode($vet);

        } else {

            echo json_encode(array('erro' => 'Erro ao retornar registro'));

        }

    } else {

        echo json_encode(array('erro' => 'Chamado inexistente.'));

    }

    die;

} else if ($acao == "causador") {

	$backlog_item = (int) $_REQUEST['backlog_item'];
	$causador     = $_REQUEST['causador'];
	$setor        = $_REQUEST['setor'];

	$sql = "UPDATE  tbl_backlog_item SET
					tbl_backlog_item.chamado_causador = $causador,
					tbl_backlog_item.grupo_admin = $setor
				WHERE backlog_item = $backlog_item";
	$res = pg_query($con,$sql);
	$msg_erro = pg_last_error($con);

	if (empty($msg_erro)) {
		echo "OK";
	}
	exit;

} else if ($acao == "tem_causador") {

	$backlog_item = (int) $_REQUEST['backlog_item'];
	$causador     = $_REQUEST['causador'];

	if($causador == 't'){
		$sql = "UPDATE  tbl_backlog_item SET
						causador = 't',
						admin_causador = $login_admin
					WHERE backlog_item = $backlog_item";
	} else {
		$sql = "UPDATE  tbl_backlog_item SET
						causador = 'f',
						admin_causador = $login_admin,
						chamado_causador = null,
						grupo_admin = null
					WHERE backlog_item = $backlog_item";
	}

	$res = pg_query($con,$sql);
	$msg_erro = pg_last_error($con);
	echo $msg_erro;
	if (empty($msg_erro)) {
		echo "OK";
	}
	exit;

} else if ($acao == "busca_causador") {

	$grupo_admin = $_REQUEST['grupo_admin'];
	$chamado_causador = $_REQUEST['chamado'];

	switch($grupo_admin){
		case 2 : $responsavel = "analista"; break;
		case 4 : $responsavel = "desenvolvedor"; break;
		case 6 : $responsavel = "suporte"; break;
	}

	echo $sql = "SELECT backlog FROM tbl_backlog_item WHERE tbl_backlog_item.hd_chamado = $chamado_causador";
	$res = pg_query($con,$sql);
	if(pg_numrows($res) == 0){
		echo "NO";
		exit;
	}

	$sql = "SELECT backlog 
				FROM tbl_backlog_item 
				WHERE tbl_backlog_item.hd_chamado = $chamado_causador
				AND   $responsavel IS NULL
				LIMIT 1";
	$res = pg_query($con,$sql);

	if(pg_numrows($res) > 0){
		$backlog = pg_result($res,0,'backlog');
		echo "OK|$backlog";
		exit;
	} 

	exit;
} else if (isset($_POST['finalizar'])) {

    $backlog = (int) $_POST['finalizar'];

    $sql = "UPDATE tbl_backlog SET
                   data_finalizado = CURRENT_TIMESTAMP
             WHERE backlog = $backlog
               AND data_finalizado ISNULL";

    $res = pg_query($con,$sql);
    $msg_erro = pg_last_error($con);

    if (empty($msg_erro)) {
        echo "OK";
    }
	exit;

}

if (isset($_REQUEST['backlog'])) {

    $backlog = (int) $_REQUEST['backlog'];

    if (!empty($backlog)) {

        $sql = "SELECT backlog, 
                       TO_CHAR(data_inicio, 'DD/MM/YYYY') as data_inicio,
                       TO_CHAR(data_fim, 'DD/MM/YYYY')    as data_fim,
                       qtde_horas,
                       qtde_desenvolvedores
                  FROM tbl_backlog
                 ORDER BY data_inicio";

        $res = pg_query($con,$sql);

        if (pg_num_rows($res)) {
		$_RESULT['backlog'] = $backlog;
		$_RESULT['data_inicio']          = pg_result($res, 0, 'data_inicio');
		$_RESULT['data_fim']             = pg_result($res, 0, 'data_fim');
		$_RESULT['qtde_horas']           = pg_result($res, 0, 'qtde_horas');
		$_RESULT['qtde_desenvolvedores'] = pg_result($res, 0, 'qtde_desenvolvedores');

        }

    }

}

if (isset($_GET['msg']) && !empty($_GET['msg']) ) { 
    $msg = $_GET['msg'];
}

if (!empty($backlog)) {

    $sql_backlog = "SELECT TO_CHAR(data_inicio, 'DD/MM/YYYY') AS data_inicio, TO_CHAR(data_fim, 'DD/MM/YYYY') AS data_fim FROM tbl_backlog WHERE backlog = $backlog;";
    $res_backlog = pg_query($con, $sql_backlog);

    $title = "CADASTRO DE BACKLOG - ".pg_result($res_backlog, 0, 'data_inicio')." à ".pg_result($res_backlog, 0, 'data_fim');

} else {

    $title = "CADASTRO DE BACKLOG";

}

include 'cabecalho_new.php';
$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");

$form1 = array(
	"data_inicio" => array(
		"span"      => 2,
		"label"     => "Data Inicio",
		"type"      => "input/text",
		"width"     => 10,
		"required"  => true,
		"maxlength" => 10
	),
	"data_fim" => array(
		"span"      => 2,
		"label"     => "Data Fim",
		"type"      => "input/text",
		"width"     => 10,
		"required"  => true,
		"maxlength" => 10
	),
	"qtde_horas" => array(
		"span"      => 2,
		"label"     => "Qtde Horas",
		"type"      => "input/number",
		"width"     => 5,
		"required"  => true,
		"maxlength" => 3
	),
	"qtde_desenvolvedores" => array(
		"span"      => 2,
		"label"     => "Qtde Desenv.",
		"type"      => "input/number",
		"width"     => 5,
		"required"  => true,
		"maxlength" => 2
	),
);

$hiddens = array("backlog");

?>

<script type="text/javascript">
	var vet_admin = new Array();
	$(document).ready(function() { 
		$.datepickerLoad(Array("data_inicio","data_fim"));
	});
</script>

<style type="text/css">
    #relatorio tr td span{ cursor:pointer; }
    #grid_list tr td span{ cursor:pointer; }

    .titulo_tabela{
        background-color:#596d9b;
        font: bold 14px "Arial";
        color:#FFFFFF;
        text-align:center;
    }
    .formulario{
        background-color:#D9E2EF;
        font:11px Arial;
        text-align:left;
    }
    .oculto {
        display: none;
    }
    #divDetHT {
        display: none;
        position: fixed;
        top: 10%;
        height: 75%;
        left: 30%;
        width: 35%;
        padding: 1em 2ex;
        background-color: white;
        border: 5px solid #d9e6ff;
        border-radius: 6px;
        -moz-border-radius: 6px;
        box-shadow: -2px 2px 5px grey;
        -moz-box-shadow: -2px 2px 5px grey;
        -webkit-box-shadow: -2px 2px 5px grey;
        display: none;
        overflow-y: auto;
        text-align: left;
        z-index: 100;
    }

    #divDetHT #fechar_msg {
        position: absolute;
        top: 10px;
        right: 10px;
    }
    #divDetHT table {
        table-layout: fixed;
        cellspacing: 2px;
        border-collapse: separate;
        border: 1px  #D9E6FF solid;
        border-radius: 5px;
        font-size: 12px;
    }
    #divDetHT caption, #divDetHT thead {
        color: white;
        background-color: #596D9B;
        font-weight:bold;
    }
    #divDetHT th, #divDetHT td {
        border: 1px solid #D9E6FF;
        padding: 2px 1em;
    }
    #divDetHT table tbody tr:nth-child(even) {
        background-color: #f0f0f6;
    }
</style><?php

if (isset($msg_erro) && !empty($msg_erro)) {?>
    <div class="alert alert-error"><?=$msg_erro?></div><?php
} ?>

    <div class="alert alert-success" id='sucesso' style='display:none;'><?=$msg?></div>

<?
if (empty($acao)) {?>
        <form action="backlog_cadastro.php" method="POST"  class="form-search form-inline tc_formulario" >

        <div class="titulo_tabela"><?=$title?></div><br/>
	<? echo montaForm($form1, $hiddens); ?>
	<p><br/>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
			<?if (!empty($backlog)) {?>
                            &nbsp;&nbsp;<input type="button" name="limpar" value="Limpar" />
                            &nbsp;&nbsp;<input type="button" name="excluir" id="<?=$backlog?>" value="Excluir" /><?php
			} ?>
	</p><br/>
        </div>
        </form>
    <?php

    $sql = "SELECT backlog, 
                   TO_CHAR(data_inicio, 'DD/MM/YYYY') as data_inicio,
                   TO_CHAR(data_fim, 'DD/MM/YYYY')    as data_fim,
                   qtde_horas,
                   qtde_desenvolvedores,
                   SUM(tbl_backlog_item.horas_utilizadas) as horas_utilizadas,
                      SUM(tbl_backlog_item.horas_analisadas) as horas_analisadas,
                      Count(*) as qtde_chamados,
                   (select count(*) from tbl_backlog_item where tbl_backlog_item.backlog = tbl_backlog.backlog and tbl_backlog_item.horas_analisadas = 0) as qtde_chamados_s_horas
              FROM tbl_backlog
             LEFT JOIN tbl_backlog_item USING(backlog)
             WHERE data_finalizado ISNULL
              GROUP BY tbl_backlog.backlog,
                   tbl_backlog.data_inicio,
                   tbl_backlog.data_fim,
                   tbl_backlog.qtde_horas,
                   tbl_backlog.qtde_desenvolvedores
             ORDER BY tbl_backlog.data_inicio desc";

    $res = pg_query($con, $sql);
    $tot = pg_num_rows($res);

    if ($tot) {?>

	<div class='container'> <br />
	<table id="relatorio" class='table table-striped table-bordered table-hover table-large'>
		<thead>
            <tr class="titulo_coluna">
                <th>Data Inicio</th>
                <th>Data Final</th>
                <th>Qtde Horas</th>
                <th>Qtde Desenvolvedores</th>
                <th>Qtde Analisadas</th>
                <th>Qtde Utilizadas</th>
                <th>Qtde de Chamados</th>
                <th>Chamados sem horas analisadas</th>
                <th nowrap>Ações</th>
            </tr>
		</thead>
            <tbody><?php 
                for ($i = 0; $i < $tot; $i++) { 

                    $backlog              = pg_result($res, $i, 'backlog');
                    $data_inicio          = pg_result($res, $i, 'data_inicio');
                    $data_fim             = pg_result($res, $i, 'data_fim');
                    $qtde_horas           = pg_result($res, $i, 'qtde_horas');
                    $qtde_desenvolvedores = pg_result($res, $i, 'qtde_desenvolvedores');
                    $qtde_utilizadas      = pg_result($res, $i, 'horas_utilizadas');
                    $qtde_analisadas      = pg_result($res, $i, 'horas_analisadas');
                    $qtde_chamados            = pg_result($res, $i, 'qtde_chamados');
                    $qtde_chamados_s_horas  = pg_result($res, $i, 'qtde_chamados_s_horas');

                    ?>

                    <tr >
                        <td align="center"><span id="<?=$backlog?>">&nbsp;<?=$data_inicio?></span></td>
                        <td align="center"><span id="<?=$backlog?>">&nbsp;<?=$data_fim?></span></td>
                        <td align="center"><span id="<?=$backlog?>">&nbsp;<?=$qtde_horas?></span></td>
                        <td align="center"><span id="<?=$backlog?>">&nbsp;<?=$qtde_desenvolvedores?></span></td>
                        <td align="center"><span id="<?=$backlog?>">&nbsp;<?=$qtde_analisadas?></span></td>
                        <td align="center"><span id="<?=$backlog?>">&nbsp;<?=$qtde_utilizadas?></span></td>
                        <td align="center"><span id="<?=$backlog?>">&nbsp;<?=$qtde_chamados?></span></td>
                        <td align="center"><span id="<?=$backlog?>">&nbsp;<?=$qtde_chamados_s_horas?></span></td>
                        <td align="center" nowrap>
                            <button class='btn' type="button"  onclick="abrirItens(<?=$backlog?>)">Itens</button>&nbsp;
                            <button class='btn' type="button" rel="<?=$backlog?>" onclick="finalizaBacklog(<?=$backlog?>)" >Finalizar</button>
                        </td>
                    </tr><?php

                } ?>
            </tbody>
        </table></div><?php

    }

} else if ($acao == 'item') {
        $sql_proj = "SELECT projeto,nome FROM tbl_projeto ORDER BY nome;";
        $res_proj = pg_query($con, $sql_proj);
        $tot_proj = pg_num_rows($res_proj);
	$vet_proj = pg_fetch_all($res_proj);
	$projA = array();
	foreach($vet_proj as $projK){
		$projA[$projK['projeto']] = $projK['nome'];
	}
        $sql_tipo = "SELECT * FROM tbl_tipo_chamado ORDER BY descricao;";
        $res_tipo = pg_query($con, $sql_tipo);
        $tot_tipo = pg_num_rows($res_tipo);
        $vet_tipo = pg_fetch_all($res_tipo);
	$tipA = array();
	foreach($vet_tipo as $tipK){
		$tipA[$tipK['tipo_chamado']] = $tipK['descricao'];
	}

        $sql_admin_analista = "SELECT * FROM tbl_admin WHERE fabrica = 10 AND ativo IS TRUE  and grupo_admin in (1,2,7) ORDER BY nome_completo;";
        $res_admin_analista = pg_query($con, $sql_admin_analista);
        $tot_admin_analista = pg_num_rows($res_admin_analista);
        $vet_admin_analista = pg_fetch_all($res_admin_analista);
	$anaA = array();
	foreach($vet_admin_analista as $anaK){
		$anaA[$anaK['admin']] = $anaK['nome_completo'];
	}
        $sql_admin_suporte = "SELECT * FROM tbl_admin WHERE fabrica = 10 AND ativo IS TRUE  and grupo_admin in (6) ORDER BY nome_completo;";
        $res_admin_suporte = pg_query($con, $sql_admin_suporte);
        $tot_admin_suporte = pg_num_rows($res_admin_suporte);
        $vet_admin_suporte= pg_fetch_all($res_admin_suporte);
	$supA = array();
	foreach($vet_admin_suporte as $supK){
		$supA[$supK['admin']] = $supK['nome_completo'];
	}

        $sql_admin_des = "SELECT * FROM tbl_admin WHERE fabrica = 10 AND ativo IS TRUE and grupo_admin in (4,2,7,1) ORDER BY nome_completo;";
        $res_admin_des = pg_query($con, $sql_admin_des);
        $tot_admin_des = pg_num_rows($res_admin_des);
        $vet_admin_des = pg_fetch_all($res_admin_des);
	$desA = array();
	foreach($vet_admin_des as $desK){
		$desA[$desK['admin']] = $desK['nome_completo'];
	}

        $sql_status = "SELECT * FROM tbl_hd_status WHERE fabrica = 10 ORDER BY status;";
        $res_status = pg_query($con, $sql_status);
        $tot_status = pg_num_rows($res_status);
        $vet_status = pg_fetch_all($res_status);
	$staA = array();
	$staA[0]=" ";
	foreach($vet_status as $staK){
		$staA[$staK['status']] = $staK['status'];
	}

        $sql_fabrica = "SELECT * FROM tbl_fabrica where ativo_fabrica  ORDER BY nome;";
        $res_fabrica = pg_query($con, $sql_fabrica);
        $tot_fabrica = pg_num_rows($res_fabrica);
        $vet_fabrica = pg_fetch_all($res_fabrica);
	$fabA = array();
	foreach($vet_fabrica as $fabK){
		$fabA[$fabK['fabrica']] = $fabK['nome'];
	}
        $fatA['t'] = 'Sim';
        $fatA['f'] = 'Não';

		$priA = array();
		for($n = 1; $n<=10;$n++) {
			$priA[$n] = $n;
		}
	$linha = 0 ;
	if (strlen($_GET["Ichamado"]) > 0){
		$_RESULT['hd_chamado_'.$linha] = $_GET["Ichamado"];
	}

	$item_input = array(
		"hd_chamado_".$linha => array(
			"span"      => 2,
			"label"     => "Chamado",
			"type"      => "input/text",
			"width"     => 8,
			"required"  => true,
			"extra" => array("onblur" => "buscarChamado(this.value)"),
			"maxlength" => 7
		),
			"projeto_".$linha => array(
				"span"      => 3,
				"label"     => "Projeto",
				"type"      => "select",
				"width"     => 8,
				"required"  => true,
		 		"options"  => $projA, 
			),
			"fabrica_".$linha => array(
				"span"      => 3,
				"label"     => "Fábrica",
				"type"      => "select",
				"width"     => 10,
				"required"  => true,
				"extra" => array("disabled"=>"disabled"),
		 		"options"  => $fabA, 
 			),
			"tipo_".$linha => array(
				"span"      => 2,
				"label"     => "Tipo",
				"type"      => "select",
				"width"     => 12,
				"required"  => true,
				"extra" => array("disabled"=>"disabled"),
		 		"options"  => $tipA, 
 			),
			"status_".$linha => array(
				"span"      => 2,
				"label"     => "Status",
				"type"      => "input/text",
				"width"     => 12,
				"required"  => true,
				"extra" => array("disabled"=>"disabled"),
 			),
			"prioridade_".$linha => array(
				"span"      => 2,
				"label"     => "Prioridade",
				"type"      => "select",
				"width"     => 6,
				"required"  => true,
		 		"options"  => $priA, 
 			),
			"faturado_".$linha => array(
				"span"      => 2,
				"label"     => "Faturar",
				"type"      => "select",
				"width"     => 8,
				"required"  => true,
		 		"options"  => $fatA, 
 			),
			"fator_hora_".$linha => array(
				"span"      => 2,
				"label"     => "Fator Hora",
				"type"      => "input/text",
				"width"     => 4,
				"required"  => true,
				"extra"	    => array("value"=>1),
				"maxlength" => 3
			),
			"horas_analisadas_".$linha => array(
				"span"      => 2,
				"label"     => "Hr. Analis",
				"type"      => "input/text",
				"width"     => 4,
				"required"  => true,
				"maxlength" => 3
			),
			"horas_utilizadas_".$linha => array(
				"span"      => 2,
				"label"     => "Hr. Util.",
				"type"      => "input/text",
				"width"     => 4,
				"required"  => true,
				"maxlength" => 3
			),
			"horas_faturadas_".$linha => array(
				"span"      => 2,
				"label"     => "Hr. Fat.",
				"type"      => "input/text",
				"width"     => 4,
				"required"  => true,
				"maxlength" => 3
			),
			"analista_".$linha => array(
				"span"      => 3,
				"label"     => "Analista",
				"type"      => "select",
				"width"     => 8,
				"required"  => true,
		 		"options"  => $anaA, 
 			),
			"desenvolvedor_".$linha => array(
				"span"      => 3,
				"label"     => "Desenvolvedor",
				"type"      => "select",
				"width"     => 8,
				"required"  => true,
		 		"options"  => $desA, 
 			),
			"suporte_".$linha => array(
				"span"      => 2,
				"label"     => "Suporte",
				"type"      => "select",
				"width"     => 11,
				"required"  => true,
		 		"options"  => $supA, 
 			),

		);
	$hidden = array("backlog_item_".$linha,"backlog","acao");
	$display = "none";
	?>
<?php

?>

<?php if(in_array($grupo_admin, $acesso_cadastro)){?>
    <form action="backlog_cadastro.php" method="POST" class='form-search form-inline tc_formulario'>
	<div class='container' >
	<div class='titulo_tabela '>Cadastro Item</div>
		<? echo montaForm($item_input,$hidden); ?>
			<p class='tac'><br/>
				<button class='btn tac' type="button"  name="enviar_<?=$linha?>" id="btn_acao" onclick="salvarItem(<?=$linha?>)">Gravar</button>
                    <button style='display:none' type="button" name="cancelar_<?=$linha?>" id="cancelar_<?=$linha?>" onclick="window.location='backlog_cadastro.php?acao=item&backlog=<?=$backlog?>';" >Cancelar</button>
			</p><br/>
			<?php if($causador == "t"){
						$checked = "checked";
						$display = "none;";

						echo "<script> exibeLabels(); </script>";
					} else {
						$checked = "";
						$display = "none;";
					}
			?>
			<div style="display:<?php echo $display; ?>" id="tem_causador_coluna" align="center">
				<input type="checkbox" value="t" name="tem_causador" id="tem_causador" onclick="exibeCausador(<?=$backlog_item?>)" <?php echo $checked; ?>>
			</div>

			<div style="display:<?php echo $display; ?>" id="causador_coluna">
				<?php if(empty($chamado_causador)){ ?>
						<input type="text" name="causador" id="causador" size="10" value="<?php echo $chamado_causador; ?>" >
				<?php } else { ?>
						<input type="hidden" name="causador" id="causador" size="10" value="<?php echo $chamado_causador; ?>">
						<a href="../helpdesk/adm_chamado_detalhe.php?hd_chamado=<?php echo $chamado_causador; ?>" target="_blank"><?php echo $chamado_causador; ?></a>
				<?php  } ?>
			</div>

			<div style="display:<?php echo $display; ?>" id="setor_causador_coluna">
				<select name="setor_causador" id="setor_causador" onchange="javascript: varificaAnalista(this.value);">
					<option value="">Selecione</option>
					<?php
						$sql = "SELECT grupo_admin, descricao FROM tbl_grupo_admin WHERE ativo IS TRUE AND grupo_admin IN(2,4,6) ORDER BY descricao";
						$res = pg_query($con,$sql);

						if(pg_num_rows($res) > 0){

							for($i = 0; $i < pg_num_rows($res); $i++){
								$grupo = pg_result($res,$i,'grupo_admin');
								$desc  = pg_result($res,$i,'descricao');

								$selected = ($grupo == $grupo_admin) ? "selected" : "";
					?>
								<option value="<?php echo $grupo; ?>" <?php echo $selected; ?>><?php echo $desc; ?></option>
					<?php
							}

						}
					?>
				</select>
			</div>

			<div class='container'>
</div>
	</div>
	</form>
<?}?>
        <input type="hidden" name="total" id="total" value="0" />
	<input type="hidden" name="baclog" id="backlog" value="<?=$backlog?>" />

        <br />
        <table id="grid_status" class='table table-striped table-bordered table-hover table-large' >
            <thead>
                <tr class="titulo_coluna">
                    <th nowrap>Total Tipo</th>
                    <th nowrap>Total Status</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>

        <br />
        <center>Sua foto vai aparecer caso as horas utilizadas forem superior a 10 horas</center>
        <table class='table table-striped table-bordered table-hover table-large'  id="grid_analistas" >
            <thead>
                <tr class="titulo_coluna">
                    <th nowrap>Desenvolvedor</th>
                    <th nowrap>Qtde de Chamados </th>
                    <th nowrap>Horas analisadas </th>
                    <th nowrap>Horas Utilizadas </th>
                </tr>
            </thead><?php

            if ($grupo_admin == 4) {
                $sql_add5 = " AND desenvolvedor = $login_admin ";
            }

            $sql2 = "SELECT COUNT(*)              as qtde_chamados, 
                            sum(horas_analisadas) as horas_analisadas,
                            sum(horas_utilizadas) as horas_utilizadas,
                            nome_completo,
                            tbl_admin.admin,
                            tbl_admin.grupo_admin
                       FROM tbl_backlog_item 
                       JOIN tbl_admin ON tbl_admin.admin = tbl_backlog_item.desenvolvedor
                      WHERE backlog = $backlog $sql_add5
                   GROUP BY nome_completo,
                            tbl_admin.admin,
                            tbl_admin.grupo_admin";

            $res2 = pg_query($sql2);

            if (pg_num_rows($res2) > 0) {

				$fa = new TDocs($con, $login_fabrica);
				$fa->setContext('fa');

                for ($w = 0; $w < pg_num_rows($res2); $w++) {

					$qtde_chamados    = pg_result($res2, $w, 'qtde_chamados');
					$admin            = pg_result($res2, $w, 'admin');
					$grupo_admin1     = pg_result($res2, $w, 'grupo_admin');
					$nome_completo    = pg_result($res2, $w, 'nome_completo');
					$horas_analisadas = pg_result($res2, $w, 'horas_analisadas');
					$horas_utilizadas = pg_result($res2, $w, 'horas_utilizadas');

					if ($fa->getDocumentsByRef($admin)->temAnexo and $horas_utilizadas > 10) {

						$caminho = $fa->url;

					} else {
						unset($caminho);
						$caminho = $fa->url;
						$caminho = ($grupo_admin1 == 4 or !$fa->temAnexo) ? "imagens/triste.jpg" : $fa->url;
					}?>

					<tr bgcolor="<?=$cor?>">
						<td align="center" rowspan="2" style="vertical-align: middle"><?php if(!empty($caminho)) { ?><img src='<?=$caminho?>' width='95' height='95'><br><?php } ?><?=$nome_completo?></td>
						<td align="center" style="vertical-align: middle"><?=$qtde_chamados?></td>
						<td align="center" style="vertical-align: middle"><?=$horas_analisadas?></td>
						<td align="center" style="vertical-align: middle"><?=$horas_utilizadas?></td>
					</tr>
					<tr>
						<td colspan="3">
							<table class="formulario tablesorter" id="grid_status_dev_<?=$admin?>" width="100%" align="center" cellspacing="1">
								<thead>
									<tr class="titulo_coluna">
										<th nowrap>Total Tipo</th>
										<th nowrap>Total Status</th>
									</tr>
								</thead>
								<tbody>
									<script>
										vet_admin[<?=$w?>] = '<?=$admin?>';
									</script>
								</tbody>
							</table>
						</td>
					</tr><?php

				}
				unset($fa);

			}?>

		</table>
	</div>
        <br /><div class='container-fluid'><p class='tac'>
        <button id="toggleResolvido" type='button' class='btn tac'>Resolvidos</button></p>
        <table class='table table-striped table-bordered table-hover table-large tal' id="grid_list" style="visibility:hidden !important;">
            <thead>
                <tr class="titulo_tabela">
                    <th nowrap>Chamado</th>
                    <th nowrap>Projeto</th>
                    <th nowrap>Fábrica</th>
                    <th nowrap>Tipo</th>
                    <th nowrap>Status</th>
                    <th nowrap>Prioridade</th>
                    <th nowrap>Faturar</th>
                    <th>Fator Hora</th>
                    <th> Hr. Analis </th>
                    <th>Hr. Util.</th>
                    <th>Hr. Fatur.</th>
                    <th nowrap>Analista</th>
                    <th nowrap>Desenvolvedor</th>
                    <th nowrap>Suporte</th>
                    <?php if(in_array($grupo_admin,$acesso_cadastro)){?><th nowrap>Ações</th><? }?>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <p class='tac'><br />
	<button class='btn' name="btn_voltar" id="btn_voltar" onclick="window.location='backlog_cadastro.php'" />Voltar</button>
	</p>
	</div>
    <div id="divDetHT"></div><?php

}?>

<script type="text/javascript">

    $(document).ready(function() { 
        $("#relatorio > tbody > tr > td > span").click(function() {

            tipo = $(this).attr('id');
            window.location = '?backlog=' + tipo;

        });<?php

        if (!empty($backlog)) {?>

            $("input[name=excluir]").click(function(e) {

                if (confirm ("Deseja mesmo excluir este backlog?")) {
                    window.location = '?excluir=' + $(this).attr('id');
                }

                e.preventDefault();

            });

            $("input[name=limpar]").click(function(e) {

                e.preventDefault();
                window.location = 'backlog_cadastro.php';

            });<?php

        }

        if ($acao == 'item') {?>

			$.each(vet_admin, function(i, val) {

				addLinha(val, 'listar_tipo_status', '');

			});

            addLinha(0, 'cadastrar', '');
            addLinha(0, 'listar_tipo_status', '');
            addLinha(0, 'listar', '');<?php

        }?>

    });

    function impresso(item){
        $("#impresso_"+item).html("<img border='0' alt='Impresso' src='imagens/img_ok.gif'>");
    }

    function paginacao() {

        $("#grid_list").bind("sortStart",function() { 
            legendaBackLog(); 
        }).bind("sortEnd",function() { 
            legendaBackLog(); 
        }); 

    }

    function legendaBackLog() {

        $('#grid_list tr').each(function(indice) {

            var status = $(this).attr('title');
            var id     = $(this).attr('id');

            if (status == 'Resolvido') {
                $("#"+id+" td ").css('background','#C1DBA2');
            }

            if (status == 'Aguard.Admin') {
                $("#"+id+" td ").css('background','#FF7F50');
            }

			if (status == 'Erro sem analise') {
                $("#"+id+" td ").css('background','#00FFFF');
            }

        });

    }

    function selecionaTodos() {

        if ($('#checkAll').attr('checked') == true) {

            $('#grid_list input[name*="printPostIt_"]').each(function(indice) {
                this.checked = true;
            });

        } else {

            $('#grid_list input[name*="printPostIt_"]').each(function(indice) {
                this.checked = false;
            });

        }

    }

    function imprimirPostIt() {

        var hd = "";
        $('#grid_list input[name*="printPostIt_"]').each(function(indice) {

            if ($(this).attr('checked') == true) {

                if (parseFloat(hd) > 0) {

                    var value = $(this).val();
                    hd += ","+value;

                } else {

                    hd = $(this).val();

                }

            }

        });

        if (parseFloat(hd) > 0) {

            Shadowbox.open({
                content : "backlog_print_postit.php?hds="+hd+"&backlog=<?php echo $backlog; ?>",
                player  : "iframe",
                title   : "Imprensão Back Log",
                width   : 800,
                height  : 500
            });

        } else {

            alert("Check os chamados para imprensão!");

        }

    }

    function salvarItem(i) {

		var chamado_causador = $("#causador").val();
		var setor_causador   = $("#setor_causador").val();
		var desenvolvedor    = $("#desenvolvedor_"+i).val();
		var suporte          = $("#suporte_"+i).val();

		if($('#tem_causador').is(':checked') && (chamado_causador == "" || setor_causador == "")){

			alert("Informe o chamado causador do erro e/ou setor causador do erro");
			return false;

		} else if($('#tem_causador').is(':checked') && (desenvolvedor == "" || suporte == "")){

			alert("Informe o desenvolvedor e/ou suporte do chamado");
			return false;

		}

        $.ajax({

            url: 'backlog_cadastro.php?acao=salvar',
            type: "POST",
            data: {
                backlog          : $('#backlog').val()            ,
                backlog_item     : $('#backlog_item_'+i).val()    ,
                hd_chamado       : $('#hd_chamado_'+i).val()      ,
                prioridade       : $('#prioridade_'+i).val()      ,
                fator_hora       : $('#fator_hora_'+i).val()      ,
                horas_analisadas : $('#horas_analisadas_'+i).val(),
                horas_faturadas  : $('#horas_faturadas_'+i).val() ,
                horas_utilizadas : $('#horas_utilizadas_'+i).val(),
                projeto          : $('#projeto_'+i).val()         ,
                faturado         : $('#faturado_'+i).val()        ,
                analista         : $('#analista_'+i).val()        ,
                suporte          : $('#suporte_'+i).val()         ,
                desenvolvedor    : $('#desenvolvedor_'+i).val()   ,
				chamado_causador : $('#causador').val()           ,
				setor_causador   : $('#setor_causador').val()
            },
            success: function(result) {

                vet = result.split('|');

                if (vet[0] == 1) {

			addLinha($('#total').val(), 'listar', '');
			$("#tem_causador_label").attr('style','display:none');
			$("#causador_label").attr('style','display:none');
			$("#setor_causador_label").attr('style','display:none');

                }

                if (vet[1] != 'Gravado com Sucesso') {
                    alert(vet[1]);
		}else{
                    $('#sucesso').html(vet[1]).attr('style','display:block');
		}

            }

        });

    }

    function excluirItem(i) {

        if (confirm('Você deseja excluir este registro?')) {

            $.ajax({

                url: 'backlog_cadastro.php?acao=excluir&backlog_item='+i,
                type: "POST",
                success: function(result) {

                    vet = result.split('|');

                    //$('#total').val(parseInt($('#total').val()) - 1);
                    addLinha($('#total').val(), 'listar', '');
                    alert(vet[1]);

                }

            });

        }

    }

    $('#toggleResolvido').click(function() {

        var resolvidos = $('tr.resolvido');

        if (resolvidos.hasClass('oculto')) {
            resolvidos.removeClass('oculto');
        } else {
            resolvidos.addClass('oculto');
        }

    });

    function buscarChamado(chamado) {

        if (chamado != '') {

            $('#sucesso').attr('style','display:none');
            $.ajax({

                url: 'backlog_cadastro.php?acao=buscar&chamado='+chamado,
                type: "POST",
                dataType: 'json',
                success: function(data) {

                    if (data.erro == '') {
			if(data.fator_hora == null || data.fator_hora == '' ) data.fator_hora = 1;
			if(data.horas_utilizadas == null ) data.horas_utilizadas = 0;
			if(data.horas_faturadas == null ) data.horas_faturadas = 0;
                        $('#fabrica_0').val(data.fabrica);
                        $('#hd_chamado_0').val(data.hd_chamado);
                        $('#tipo_0').val(data.tipo_chamado);
                        $('#status_0').val(data.status_fabrica);
                        $('#prioridade_0').val(data.prioridade);
                        $('#analista_0').val(data.analista);
                        $('#suporte_0').val(data.suporte);
                        $('#desenvolvedor_0').val(data.desenvolvedor);
                        $('#projeto_0').val(data.projeto);
                        $('#fator_hora_0').val(data.fator_hora);
                        $('#horas_analisadas_0').val(data.horas_analisadas);
                        $('#horas_faturadas_0').val(data.horas_faturadas);
                        $('#horas_utilizadas_0').val(data.horas_utilizadas);
                        $('#faturado_0').val(data.faturado);
                        $('#backlog_item_0').val(data.backlog_item);
                        $('#cancelar_0').attr('display','visible');
			scroll(0,0);
                    } else {

                        alert(data.erro);

                    }
                }

            });

        }

    }

    function addLinha(i, tipo, backlog_item) {

        <?php if(!in_array($grupo_admin, $acesso_cadastro)) {?>
            if (tipo == 'cadastrar') {
                return false;
            }
        <?php }?>

        $.ajax({

            url: "backlog_cadastro.php?acao=inserir&tipo="+tipo+"&linha="+i+"&backlog="+$("#backlog").val()+"&backlog_item="+backlog_item+"&Ichamado=<?=$_GET['Ichamado']?>",
            type: "POST",
            success: function(result) {

                if (tipo == 'cadastrar') {
                    $('#hd_chamado_1').focus();
					if(backlog_item != ""){
						var tipo_chamado = $("#tipo_0").val();
						if(tipo_chamado == 5){
							$("#tem_causador_label").attr('style','display:block');
							$("#tem_causador_coluna").attr('style','display:block');
						} else {
							$("#tem_causador_label").attr('style','display:none');
							$("#tem_causador_coluna").attr('style','display:none');
							$("#causador_label").attr('style','display:none');
							$("#causador_coluna").attr('style','display:none');
							$("#setor_causador_label").attr('style','display:none');
							$("#setor_causador_coluna").attr('style','display:none');
						}
					}

                } else if (tipo == 'listar') {

			$('#grid_list tbody').html(result);
			if ($('.dataTable').length == 0) {
				$.dataTableLoad({ table: "#grid_list" });
			}else{
				$('#grid_list').fnDestroy();
				$.dataTableLoad({ table: "#grid_list" });
                $('#sucesso').attr('style','display:none');
			}
			paginacao();
			legendaBackLog();

                    $('.ht_detalhe').click(function() {

                        var uri  = 'hd_chamado=' + $(this).attr('alt');
                            uri += '&ajax=detHT';

                        $.get(window.location.pathname,
                            uri,
                            function(data) {

                                $('#divDetHT').html(data).show('normal');
                                $('#fechar_msg').click(function() {
                                    $('#divDetHT').hide('normal');
                                });

                        });

                    });

                } else if (tipo == 'listar_tipo_status' && i == 0) {

                    $('#grid_status tbody').html(result);

                } else if (tipo == 'listar_tipo_status' && i != 0) {

                    $('#grid_status_dev_'+i+' tbody').html(result);

                }

                if (tipo != 'listar_tipo_status') {

                    //$('#total').val(parseInt($('#total').val()) + 1);

                    if (i == 0) {

                        <?php if (in_array($grupo_admin, $acesso_cadastro)) {?>
                            $('#grid_cad').attr('style', '');
                        <?php }?>

                        $('#grid_list').attr('style', '');

                    }

                }

            }

        });
    }

    function editarItem(item) {

        <?php if (!in_array($grupo_admin, $acesso_cadastro)) {?>
            return false;
        <?php }?>

	$.ajax({
		url: "backlog_cadastro.php",
		data:"acao=inserir&tipo=cadastrar&linha="+i+"&backlog="+$("#backlog").val()+"&backlog_item="+item+"&Ichamado=<?=$_GET['Ichamado']?>",
                type: "GET",
                dataType: 'json',
		complete: function(data) {
			$('#hd_chamado_0').val(data.hd_chamado);
			$('#tipo_0').val(data.tipo_chamado);
		}
	});
	scroll(0,0);
    }

	function exibeCausador(backlog_item){
		var tem_causador = '';

		if($('#tem_causador').is(':checked')){
			tem_causador = 't';
		} else {
			tem_causador = 'f';
		}
		$.ajax({
				url     : "backlog_cadastro.php?acao=tem_causador&causador="+tem_causador+"&backlog_item="+backlog_item,
				type    : "POST",
				success : function(result) {

					if(result == "OK"){

						if(tem_causador == "t"){
							$("#causador_label").attr('style','display:table-cell');
							$("#causador_coluna").attr('style','display:table-cell');
							$("#setor_causador_label").attr('style','display:table-cell');
							$("#setor_causador_coluna").attr('style','display:table-cell');
						} else {
							$("#causador_label").attr('style','display:none');
							$("#causador_coluna").attr('style','display:none');
							$("#setor_causador_label").attr('style','display:none');
							$("#setor_causador_coluna").attr('style','display:none');
						}

					}
				}
		});
	}

	function gravaCausador(backlog_item){
		var causador = $('#causador').val();
		var setor    = $('#setor_causador').val();
		$.ajax({
				url     : "backlog_cadastro.php?acao=causador&causador="+causador+"&setor="+setor+"&backlog_item="+backlog_item,
				type    : "POST",
				success : function(result) {
					if(result == "OK"){

					}
				}
		});
	}

	function exibeLabels(){
		$("#tem_causador_label").attr('style','display:table-cell');
		$("#causador_label").attr('style','display:table-cell');
		$("#setor_causador_label").attr('style','display:table-cell');
	}

	function varificaAnalista(grupo_admin){
		var chamado_causador = $("#causador").val();
		$.ajax({
				url     : "backlog_cadastro.php?acao=busca_causador&chamado="+chamado_causador+"&grupo_admin="+grupo_admin,
				type    : "POST",
				success : function(result) {
					vet = result.split('|');
					if(vet[0] == "OK"){
						if(confirm("Deseja mir para o backlog do chamado causador")){
							$("#setor_causador").val('');
							window.open("backlog_cadastro.php?acao=item&backlog="+vet[1]);
						}
					} else if(vet[0] == "OK") {
						alert("O chamado causador não está cadastrado em nenhum backlog");
					}
				}
		});
	}

    function abrirItens(backlog) {
        window.location = '?acao=item&backlog='+backlog;
    }

    function finalizaBacklog(backlog) {

        $.post(
            "<?=$PHP_SELF?>",
            {
                finalizar: backlog
            },
            function(resposta){
            }
        );

    }

</script><?php

include 'rodape.php';?>
