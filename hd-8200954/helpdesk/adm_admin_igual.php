<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';
include_once '../admin/funcoes.php';
include_once 'mlg_funciones.php';
include_once '../class/fn_sql_cmd.php';

define('BS3', true);

$tableAttrs = array(
    'tableAttrs' => 'data-toggle="table" class="table table-condensed table-bordered table-hover table-striped "',
    "headerAttrs" => "class='primary'"
);

if (!in_array($login_admin, [586, 1375, 4789, 5205])) {
    $msg_erro        = 'Tela restrita';
    $desabilita_tela = true;
}

$userActionRequest = $_REQUEST['action'];

/**
 * Retorna um _resource_ pgsql com o resultado da pesquisa.
 * Confere se existe na coluna `admin_igual` o valor `$admin`
 * para evitar duplicidade
 */
function checkAdmin($admin) {
    $sql = sql_cmd('tbl_admin_igual', '*', ['admin_igual' => $admin]);
    return pg_query($GLOBALS['con'], $sql);
}

function getAdminIgual($ID=null) {
    global $con;
    if ($ID) {
        $filtroAdmin = 'AND ' . pg_where('admin', $ID, true);
    }
    $sql = "SELECT DISTINCT
                   admin, F.fabrica, F.nome AS fabricante, login, A.nome_completo
              FROM tbl_admin_igual
              JOIN tbl_admin   AS A USING (admin)
              JOIN tbl_fabrica AS F USING (fabrica)
             WHERE F.ativo_fabrica
               AND A.ativo $filtroAdmin
          ORDER BY nome_completo";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res)) {
        $recs = pg_fetch_all($res);

        foreach ($recs as $rec) {
            $admin = $rec['admin'];
            unset($rec['admin']);
            $admins[$admin] = $rec;

            $sql_i =
                "SELECT admin_igual, F.fabrica, F.nome AS fabricante, login, nome_completo
                   FROM tbl_admin_igual AS I
                   JOIN tbl_admin   AS A ON A.admin = I.admin_igual
                   JOIN tbl_fabrica AS F USING (fabrica)
                  WHERE F.ativo_fabrica
                    AND A.ativo
                    AND I.admin = $admin
               ORDER BY fabricante";

            $iguais = pg_fetch_all(pg_query(
                $con,
                $sql_i
            ));

            foreach ($iguais as $rec) {
                $iadmin = $rec['admin_igual'];
                unset($rec['admin_igual']);
                $admins[$admin]['alter'][$iadmin] = $rec;
            }
        }
        return $admins;
    }
    return array();
}

// AJAX
if ($_REQUEST['ajax'] == 'logins') {
    $fabrica = (int)$_REQUEST['fabricante'];
    $target  = preg_replace('/[^a-z0-9._-]/', '', $_REQUEST['target']);
    $selName = (strpos($target, 'new') !== false) ? 'admin' : 'admin_igual';
    $array_admins = pg_fetch_all(pg_query(
        $con,
        "SELECT admin, login, nome_completo AS nome
           FROM tbl_admin
          WHERE fabrica = $fabrica
            AND ativo IS TRUE
            AND LENGTH(privilegios) > 0
          ORDER BY nome_completo"
    ));

    if (count($array_admins)) {
        $ret  = "\t\t\t<select id='sel_$selName' name='$selName' data-target='$target' class='form-control input-sm'>\n";
        $ret .= "\t\t\t\t<option value=''>Selecione um usuário</option>\n";
        foreach ($array_admins as $admin) {
            $ret .= "\t\t\t\t<option data-name='{$admin['login']}' value='{$admin['admin']}'>{$admin['nome']}</option>\n";
        }
        die($ret . "\t\t\t</select>");
    }
    die("<strong><em>Sem admins</em></strong>");
}

if ($_POST['ajax'] == 'delete') {
    $admin       = getPost('admin');
    $admin_igual = getPost('admin_igual');

    if (!is_numeric($admin) or !is_numeric($admin_igual)) {
        die("Erro nos valores!");
    }
    $sql = sql_cmd('tbl_admin_igual', 'delete', compact('admin', 'admin_igual'));

    if ($sql[0] === 'D') {
        $res = pg_query($con, $sql);

        if (!is_resource($res)) {
            die('Erro de acesso ao banco de dados!');
        }
        if (pg_affected_rows($res) !== 1) {
            // pre_echo($sql);
            die('Erro na execução da consulta!');
        }
        die('ok');
    }
}

if ($userActionRequest == 'wipeout') {
    $admin   = getPost('admin');
    $fabrica = getPost('fabrica');

    try {
        if (!is_numeric($admin) or !is_numeric($fabrica)) {
            throw new Exception("Erro nos valores!");
        }

        $tem = pg_num_rows($login_principal_res = pg_query(
            $con, sql_cmd('tbl_admin', 'login', compact('admin', 'fabrica'))
        ));

        if ($tem === 1) {
            $sql = sql_cmd('tbl_admin_igual', 'delete', compact('admin'));

            if ($sql[0] === 'D') {
                // throw new Exception($sql);
                $res = pg_query($con, $sql);

                if (!is_resource($res)) {
                    throw new Exception('Erro de acesso ao banco de dados!');
                }
                $count = pg_affected_rows($res);

                if (!$count) {
                    pre_echo($sql);
                    throw new Exception('Erro na execução da consulta!');
                }
            }
            $userActionRequest = 'listar';
            $msg_success       = "Todos os registros alternativos do usuário ($count) foram excluídos. Agora só pode logar com seu login em cada fabricante de forma separada.";
        }
    } catch (Exception $e) {
        $msg_erro = $e->getMessage();
        $userActionRequest = 'edit';
    }
}

if ($userActionRequest == 'append') {
    $admin       = getPost('admin');
    $admin_igual = getPost('admin_igual');

    try {
        if ($admin == $admin_igual) {
            throw new Exception("Não pode associar o mesmo usuário!");
        }
        if (!is_numeric($admin) or !is_numeric($admin_igual)) {
            throw new Exception("Erro nos valores a serem adicionados!");
        }

        $tem = checkAdmin([$admin, $admin_igual]);

        if (pg_num_rows($tem) !== 0)
            throw new Exception("O usuário que está tentando associar já está associado com um outro usuário!");

        $sql = sql_cmd('tbl_admin_igual', compact('admin', 'admin_igual'));

        if ($sql[0] === 'I') {
            $res = pg_query($con, $sql);

            if (!is_resource($res)) {
                throw new Exception('Erro de acesso ao banco de dados!');
            } elseif (pg_affected_rows($res) !== 1) {
                throw new Exception("Erro na execução da consulta!");
                // throw new Exception($sql);
            } else {
                $msg_success[] = "Novo acesso cadastrado corretamente!";
            }
        }
    } catch (Exception $e) {
        $msg_erro = $e->getMessage();
        $userActionRequest = 'edit';
    }
}

CONTINUAR:

// Título para novo cadasatro
$titulo_form = "Cadastro de Usuários Multifábrica";
$TITULO = $titulo_form;

if (!in_array($userActionRequest, ['listar', 'novo'])) {
    $admin  = (int)getPost('admin');
    $iguais = getAdminIgual($admin)[$admin];
    $titulo_form = traduz("Alteração do Cadastro do Usuário")." <strong>{$iguais['nome_completo']}</strong>";
    // pre_echo($iguais);
}

if (!count($_REQUEST) or $userActionRequest == 'listar') {
    $admins = getAdminIgual();
    $titulo_form = "Usuários com acesso a mais de um Fabricante";
}

$headerHTML = <<<STYLE
    <style>
    thead.primary th {
        background-color: #494999;
        color: white;
        text-transform: capitalize;
    }
    td:not([title]) {text-align: center}
    </style>
STYLE;

include 'menu.php';

ob_start();?>
<?php
$admin = $admin ? : getPost('admin') ? : 1; // Evita o erro na consulta SQL
$array_fabricas = pg_fetch_pairs(
    $con,
    "SELECT fabrica, nome
       FROM tbl_fabrica
      WHERE ativo_fabrica
        AND fabrica <> (SELECT fabrica FROM tbl_admin WHERE admin = $admin)
      ORDER BY nome"
);

// Se tem cadastrados admin iguais, lista eles
if (in_array($userActionRequest, ['edit', 'insert', 'append'])) {
    foreach ($iguais['alter'] as $admin_igual => $rec) {
        $table[] = array(
            'login' => $rec['login'],
            'fabricante' => $rec['fabricante'],
            'nome' => $rec['nome_completo'],
            'ações' => '<button class="btn btn-xs btn-danger btn-excluir" title="Excluir Acesso" type="button" data-index="' .
            "$admin,$admin_igual" .
            '"><i class="glyphicon glyphicon-remove"></i></button>'.PHP_EOL
        );
        unset($array_fabricas[$rec['fabrica']]);
    }
}

/**
 * Form para admin_igual
 */

// Form para adicionar um login
$table[] = array(
    'nome' => '<em id="fullname">Selecione um fabricante</em>',
    'fabricante' => array2select(
        'fabrica', 'ins_fabrica_master',
        $array_fabricas, $_POST['fabrica'],
        'data-target="ins_admin" data-target2="fullname" class="form-control input-sm"', ' Fabricante', true
    ),
    'login' => '<div id="ins_admin" class="input-control form-inline"><em>Selecione um fabricante</em></div>',
    'ações' => '<button type="submit" class="btn btn-xs btn-default" id="btn-insert" title="Adicionar"><i class="glyphicon glyphicon-plus"></i></button>'
);

if ($userActionRequest == 'novo') {
    unset($table[0]['ações']);?>
            <form id='newUserForm' action='<?=$_SERVER['PHP_SELF']?>' method='POST'>
                <input type="hidden" name="action" value="append">
                <legend>Informações do Usuário</legend>
                <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4">
                    <div class="form-group col-xs-12 col-sm-6 col-md-12 col-lg-12">
                        <label for="ins_fabrica"><?=traduz('fabricante')?></label>
                        <?php echo array2select(
                            'new_fabrica', 'ins_fabrica',
                            $array_fabricas, $_POST['fabrica'],
                            'autofocus data-target="new_admin_igual" data-target2="new_fullname" class="form-control"', 'Fabricante', true
                        )?>
                    </div>
                    <div class="form-group col-xs-12 col-sm-6 col-md-12 col-lg-12">
                        <label for="new_login">Login</label>
                        <div class="input-form" id="new_admin_igual"><em>Selecione um fabricante</em></div>
                    </div>
                    <div class="form-group col-xs-12 col-sm-6 col-md-12 col-lg-12">
                        <label for="new_fullname">Nome do Usuário</label>
                        <p id="new_fullname" class="input-form"><em>Selecione um fabricante</em></p>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-12 col-md-8 col-lg-8">
                    <?=array2table($table, 'Pode acessar como:')?>
                </div>
<? } elseif (isset($userActionRequest) and $userActionRequest != 'listar') { ?>
            <fieldset class="col-xs-12 col-sm-12 col-md-4 col-lg-3 col-xl-3">
                <legend>Informações do Usuário</legend>
                <div class="form-group col-xs-6 col-sm-4 col-md-4 col-lg-4"><label>Fabricante</label><p class="form-control-static"><?=$iguais['fabricante']?></p></div>
                <div class="form-group col-xs-6 col-sm-4 col-md-4 col-lg-4"><label>Nome</label><p class="form-control-static"><?=$iguais['nome_completo']?></p></div>
                <div class="form-group col-xs-6 col-sm-4 col-md-4 col-lg-4"><label>Usuário</label><p class="form-control-static"><?=$iguais['login']?></p></div>
                <label>&nbsp;</label>
                <div class="input-control">
                    <button type="submit" name="action" value="wipeout" id='btn-wipeout' class="btn btn-sm btn-danger"
                    data-admin="<?=$admin?>" data-fabrica="<?=$iguais['fabrica']?>">
                        <i class="glyphicon glyphicon-remove-sign"></i>
                        Excluir Todos
                    </button>
                </div>
            </fieldset>
            <div class="col-xs-12 col-sm-12 col-md-8 col-lg-9 col-xl-9">
                <form id="insNew" method="POST">
                    <input type="hidden" name="admin"  value="<?=$admin?>">
                    <input type="hidden" name="action" value="append">
                    <?=array2table($table, 'Pode acessar como:')?>
                </form>
            </div>
<? } else { ?>
<?php if (isset($admins) and count($admins)): ?>
            <div id="tbl_result">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="dropdown" role="presentation">
                    <a href="#" class="dropdown-toggle" id="myTabDrop1" data-toggle="dropdown" aria-controls="myTabDrop1-contents"
                       aria-expanded="false">Selecione un Atendente <span class="caret"></span></a>
                        <ul class="dropdown-menu">
<?php
foreach ($admins as $admin => $table) {
    // $grouped_data = array_group_by($table_data, 'chamado');
    // pre_echo($table, 'DADOS', true);
    $userLogin  = str_replace(' ', '_', strtolower(retira_acentos($table['nome_completo'])));
    $user_login = $table['nome_completo'];
    $user_count = count($table['alter']) + 1;
    $htmlTABs  .= "\t\t\t\t<li class='menu-item' role='presentation'><a href='#$userLogin' role='tab' data-toggle='tab'>$user_login <span class='badge pull-right'>$user_count</span></a></li>\n";

    $tableAttrs = array(
        'tableAttrs' => 'data-toggle="table" class="table table-condensed table-bordered table-hover table-striped "',
        "headerAttrs" => "class='primary'"
    );

    $user_caption = "
    <div class='col-sm-4 col-md-2 col-xs-6 col-lg-3 text-center text-primary'>$user_login</div>
    <div class='col-sm-8 col-md-10 col-xs-6 col-lg-9 text-right'>
      <a class='btn btn-info btn-sm' role='button' href='?action=edit&amp;admin=$admin'>
        <i class='glyphicon glyphicon-edit'></i>Alterar
      </a>
      <button type='submit' name='action' value='wipeout' id='btn-wipeout-$admin' class='btn btn-sm btn-danger'
              data-admin='$admin' data-fabrica='{$table['fabrica']}'>
        <i class='glyphicon glyphicon-remove-sign'></i>
        Excluir Todos
      </button>
    </div> ";
    $htmlTabContent .= "\t\t\t<div id='$userLogin' class='tab-pane' role='tab-panel'>\n".
        array2table($table['alter'], $user_caption).
        "\t\t\t</div>\n";
}
?>
        <?=$htmlTABs?>
                        </ul>
                    </li>
                </ul>
              <!-- Tab panes -->
                <div class="tab-content">
                    <?=$htmlTabContent?>
                </div>
            </div>
<?php
    endif;
} ?>
        <div class="row">&nbsp;</div>
        <div class="row text-center">
        <?php if (!isset($admins)): ?>
            <a class="btn btn-warning" href="<?=$_SERVER['PHP_SELF']?>"><i class='glyphicon glyphicon-list'></i> Listar Todos os Usuários</a>
        <?php endif;
        if ($userActionRequest != 'novo'): ?>
            <a class='btn btn-success' href='?action=novo'>
                <i class='glyphicon glyphicon-plus'></i> Cadastrar Novo
            </a>
        <?php endif ?>
        <?php if ($userActionRequest == 'novo'): ?>
            <button type="submit" value="newuser" name="insert" form="newUserForm" class="btn btn-primary">
                <i class="glyphicon glyphicon-ok-sign"></i>
                Cadastrar
            </button>
            </form>
        <?php endif ?>
        </div>
        <div class="row">&nbsp;</div>
<?php $content = ob_get_clean(); ?>
<div class="container">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><?=$titulo_form?></h3>
        </div>
        <div class="panel-body">
            <div class="container-fluid">
                <?=$content?>
            </div>
        </div>
    </div>
</div>
<script>
$(function() {
    $("#clear-form").click(function() {
        $("select,input:text").val('');
        $('button').removeAttr('disabled');
    });

    $('select,input').change(function() {$('button').has('disabled').removeAttr('disabled');});

    $('.container form').submit(function() {
        $("button.close").click();
        $('button.btn-primary').attr('disabled', true);
    });

    $("#ins_fabrica_master,#ins_fabrica").change(function() {
        var fabrica = $(this).val();
        var target  = '#'+$(this).data('target');
        var target2 = '#'+$(this).data('target2');
        $(target).load(document.location.pathname, {ajax: 'logins', 'fabricante': fabrica, 'target': target2});
        $(target2).text("Selecione um usuário (Login)");
        $("#btn-insert").data('fabrica', fabrica);
    });

    $(".panel").on('change', '[id^=sel_admin]', function() {
        var nome   = $(this).find(':selected').data('name') || $(this).find(':selected').text();
        var target = '#'+$(this).data('target');
        var admin  = $(this).find(':selected').val();
        $(target).text(nome);
        $("#btn-insert").data('admin', admin);
    });

    $(".btn-excluir").click(function() {
        var $self = $(this);
        var postData = {ajax: 'delete', action: 'delete'};
        var adminData = $(this).data();
        [postData.admin, postData.admin_igual] = adminData.index.split(',');
        $.post(document.location.pathname, postData, function(retorno) {
            if (retorno == 'ok') {
                $self.parents('tr').hide();
                return true;
            }
            alert(retorno);
        });
    });

    $("[id^=btn-wipeout]").click(function(ev) {
        if (confirm("Tem certeza que deseja excluir TODOS os relacionamentos deste Usuário?")) {
            var params     = {action: 'wipeout'};
            params.admin   = $(this).data('admin');
            params.fabrica = $(this).data('fabrica');
            document.location.href = document.location.pathname + '?' + Menu.toQueryString(params);
            return true;
        }
        ev.preventDefault();
        return false;
    });

    if ($('.btn-warning').length > 0 && window.document.visibilityState !== 'visible') {
        NotificationTC.dispatch('Processo finalizado', $(".alert[role=alert]").text());
    }
});
</script>
<?
include 'rodape.php';
// vim: set et ts=4 sw=4 tw=120 cc=120:
