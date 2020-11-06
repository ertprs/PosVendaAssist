<?php
/**
 *
 * @author  Gabriel Tinetti
 *
*/

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';

include '../vendor/autoload.php';

$appId = "1341155769369225";
$appSecret = "15e6166b93f769f86fea7d2cfb2fec6b";

$fbSDK = new Facebook\Facebook([
	'app_id' => $appId,
	'app_secret' => $appSecret,
	'default_graph_version' => 'v3.3'
]);

if (count($_POST) > 0) {
    switch ($_POST['ajax']) {
        case 'removeFacebookAccount':
            $queryParametros = "
            SELECT
                parametros_adicionais
            FROM tbl_fabrica
            WHERE fabrica = $1";

            $resourceParametros = pg_query_params($con, $queryParametros, [$login_fabrica]);
            if (strlen(pg_last_error()) === 0 && pg_num_rows($resourceParametros) === 1) {
                $parametrosAdiconais = pg_fetch_result($resourceParametros, 0, 'parametros_adicionais');
                $parametrosAdiconais = json_decode($parametrosAdiconais, true);

                $oAuthInstagram = $parametrosAdiconais['oAuthInstagram'];
                $userAccessToken = $oAuthInstagram['userLongLivedAccessToken'];
                $instagramBusinessPages = $parametrosAdiconais['instagramBusinessPages'];

                unset($parametrosAdiconais['instagramBusinessPages']);
                unset($parametrosAdiconais['oAuthInstagram']);

                $parametrosAdiconais = json_encode($parametrosAdiconais);
                if (json_last_error() === 0) {
                    pg_query($con, "BEGIN");

                    $queryParametros = "
                    UPDATE
                        tbl_fabrica
                    SET parametros_adicionais = $1
                    WHERE fabrica = $2";

                    $resourceParametros = pg_query_params($con, $queryParametros, [$parametrosAdiconais, $login_fabrica]);
                    if (strlen(pg_last_error()) === 0) {
                        try {
                            $fbSDK->delete('/' . $oAuthInstagram['userId'] . "/permissions", ['access_token' => $userAccessToken]);
                            foreach ($instagramBusinessPages as $page) {
                                $fbSDK->delete('/' . $page['pageId'] . "/subscribed_apps", ['access_token' => $page['pageAccessToken']]);
                            }
                        } catch (\Exception $e) {
                            pg_query($con, "ROLLBACK");
                            exit(json_encode(['exception' => utf8_encode($e->getMessage())]));
                        }

                        pg_query($con, "COMMIT");
                        $response = json_encode(['message' => utf8_encode('Processo concluído com sucesso.')]);
                    } else {
                        pg_query($con, "ROLLBACK");
                        $response = json_encode(['message' => 'Falha ao remover conta do Facebook e contas do Instagram vinculadas.']);
                    }
                } else {
                    $response = json_encode(['message' => 'Falha ao remover conta do Facebook e contas do Instagram vinculadas.']);
                }
            } else {
                $response = json_encode(['exception' => 'Falha ao remover conta do Facebook e contas do Instagram vinculadas.']);
            }

            echo $response;
            break;
        case 'removeLinkedAccount':
            $pageId = trim($_POST['pageId']);

            $queryAccount = "
            SELECT
                x.key,
                x.page,
                x.parametros_adicionais
            FROM (
                SELECT
                    row_number() OVER () - 1 AS key,
                    json_array_elements(parametros_adicionais::json->'instagramBusinessPages') AS page,
                    parametros_adicionais
                FROM tbl_fabrica
                WHERE fabrica = $1
            )x
            WHERE x.page::json->>'pageId' = $2
            LIMIT 1";

            $resourcePages = pg_query_params($con, $queryAccount, [$login_fabrica, $pageId]);
            if (strlen(pg_last_error()) === 0 && pg_num_rows($resourcePages) === 1) {
                $pageArrayKey = pg_fetch_result($resourcePages, 0, 'key');
                $parametrosAdiconais = pg_fetch_result($resourcePages, 0, 'parametros_adicionais');
                $parametrosAdiconais = json_decode($parametrosAdiconais, true);

                $page = $parametrosAdiconais['instagramBusinessPages'][$pageArrayKey];

                unset($parametrosAdiconais['instagramBusinessPages'][$pageArrayKey]);

                $parametrosAdiconais = json_encode($parametrosAdiconais);
                if (json_last_error() === 0) {
                    pg_query($con, "BEGIN");

                    $queryAccount = "
                    UPDATE
                        tbl_fabrica
                    SET parametros_adicionais = $1
                    WHERE fabrica = $2";

                    $resourcePages = pg_query_params($con, $queryAccount, [$parametrosAdiconais, $login_fabrica]);
                    if (strlen(pg_last_error()) === 0) {
                        $fbSDK->delete('/' . $page['pageId'] . "/subscribed_apps", ['access_token' => $page['pageAccessToken']]);

                        pg_query($con, "COMMIT");
                        $response = json_encode(['message' => "Conta removida com sucesso."]);
                    } else {
                        pg_query($con, "ROLLBACK");
                        $response = json_encode(['exception' => "Falha ao remover conta vinculada."]);
                    }
                } else {
                    $response = json_encode(['exception' => "Falha ao remover conta vinculada."]);
                }
            } else {
                $response = json_encode(['exception' => "Falha ao buscar conta vinculada."]);
            }

            echo $response;
            break;
        case 'getLinkedAccounts':
            $queryIgPages = "
            SELECT
                parametros_adicionais::jsonb->>'instagramBusinessPages' AS ig_pages
            FROM tbl_fabrica
            WHERE fabrica = {$login_fabrica}";

            $resourcePages = pg_query($con, $queryIgPages);
            if (strlen(pg_last_error()) === 0) {
                $igPages = pg_fetch_result($resourcePages, 0, 'ig_pages');
                if (strlen($igPages) > 0) {
                    $response = $igPages;
                } else {
                    $response = json_encode(['exception' => utf8_encode('Nenhuma página encontrada.')]);
                }
            } else {
                $response = json_encode(['exception' => utf8_encode("Falha ao buscar páginas.")]);
            }

            echo $response;
            break;
        case 'linkBusinessAccount':
            $igData = $_POST['igData'];

            $queryParametrosAdicionais = "
            SELECT parametros_adicionais
            FROM tbl_fabrica
            WHERE fabrica = {$login_fabrica}";

            $resource = pg_query($con, $queryParametrosAdicionais);
            if (strlen(pg_last_error()) === 0 && pg_num_rows($resource) > 0) {
                $resParametrosAdicionais = pg_fetch_result($resource, 0, 'parametros_adicionais');
                $parametrosAdiconais = json_decode($resParametrosAdicionais, true);

                $exists = false;
                if (count($parametrosAdiconais['instagramBusinessPages']) > 0) {
                    foreach ($parametrosAdiconais['instagramBusinessPages'] as $igPage) {
                        if ($igPage['pageId'] === $igData['pageId']) {
                            $exists = true;
                        }
                    }
                }

                if ($exists === false) {
                    $parametrosAdiconais['instagramBusinessPages'][] = $igData;
                    $parametrosAdiconais = json_encode($parametrosAdiconais);

                    $queryUpdateParams = "
                    UPDATE tbl_fabrica
                    SET parametros_adicionais = $1
                    WHERE fabrica = $2";

                    pg_query($con, "BEGIN");

                    $resourceUpdate = pg_query_params(
                        $con,
                        $queryUpdateParams,
                        [pg_escape_string($parametrosAdiconais), $login_fabrica]
                    );

                    if (strlen(pg_last_error()) > 0) {
                        $response['exception'] = pg_last_error();
                        pg_query($con, "ROLLBACK");
                    } else {
                        try {
                            $fbSDK->post('/' . $igData['pageId'] . "/subscribed_apps", [
                                'access_token' => $igData['pageAccessToken'],
                                'subscribed_fields' => ['mention']
                            ]);
                        } catch (\Exception $e) {
                            pg_query($con, "ROLLBACK");
                            exit(json_encode(['exception' => utf8_encode($e->getMessage())]));
                        }

                        $response = json_decode($parametrosAdiconais, true)['instagramBusinessPages'];
                        pg_query($con, "COMMIT");
                    }
                } else {
                    $response['exception'] = utf8_encode("Esta conta já está vinculada.");
                }
            } else {
                $response['exception'] = "Ocorreu um erro. Tente novamente.";
            }

            echo json_encode($response);
            break;
        case 'listAllPages':
            $accessToken = $_POST['accessToken'];
            $response = [];

            try {
                $pagesNode = $fbSDK->get('/me/accounts', $accessToken);
                $pages = $pagesNode->getGraphEdge();

                $pages = json_decode($pages, true);

                foreach ($pages as $key => $page) {
                    $igBusinessNode = $fbSDK->get('/' . $page['id'] . '?fields=instagram_business_account', $accessToken);
                    $igBusiness = $igBusinessNode->getGraphNode();

                    $igBusiness = json_decode($igBusiness, true);
                    if (array_key_exists('instagram_business_account', $igBusiness)) {
                        $igBusinessDataNode = $fbSDK->get(
                            '/' . $igBusiness['instagram_business_account']['id'] . '?fields=name,username,profile_picture_url',
                            $accessToken
                        );
                        $igBusinessData = $igBusinessDataNode->getGraphNode();
                        $igBusinessData = json_decode($igBusinessData, true);

                        $page['instagram_business_account'] = $igBusinessData;
                        $response[] = $page;
                    }
                }
            } catch (\Exception $e) {
                $response['exception'] = $e->getMessage();
                $response['code'] = $e->getCode();
            }

            echo json_encode($response);
            break;
        case 'getOAuth':
            $queryParametrosAdicionais = "
            SELECT
                parametros_adicionais::jsonb->>'oAuthInstagram' AS oauth
            FROM tbl_fabrica
            WHERE fabrica = {$login_fabrica}";

            $resource = pg_query($con, $queryParametrosAdicionais);
            if (pg_num_rows($resource) === 1) {
                $resParametrosAdicionais = utf8_encode(pg_fetch_result($resource, 0, 'oauth'));
                if (strlen($resParametrosAdicionais) === 0) {
                    $response = ['exception' => utf8_encode('Dados de autenticação não encontrados.')];
                } else {
                    $response = json_decode($resParametrosAdicionais, true);
                }
            } else {
                $response = [];
            }

            echo json_encode($response);
            break;
        case 'persistOAuth':
            $oAuth = $_POST['auth'];

            $queryParametrosAdicionais = "
            SELECT parametros_adicionais
            FROM tbl_fabrica
            WHERE fabrica = {$login_fabrica}";

            $resParametrosAdicionais = pg_query($con, $queryParametrosAdicionais);
            if (strlen(pg_last_error()) === 0 && pg_num_rows($resParametrosAdicionais) > 0) {
                $result = pg_fetch_result($resParametrosAdicionais, 0, 'parametros_adicionais');

                $parametrosAdiconais = json_decode($result, true);
                $parametrosAdiconais['oAuthInstagram'] = [
                    'userId' => $oAuth['userID'],
                    'userLongLivedAccessToken' => $oAuth['accessToken'],
                    'signedRequest' => $oAuth['signedRequest']
                ];

                $parametrosAdiconais = json_encode($parametrosAdiconais);
                if (json_last_error() === 0) {
                    pg_query($con, "BEGIN");

                    $queryParametrosAdicionais = "
                    UPDATE tbl_fabrica
                    SET parametros_adicionais = $1
                    WHERE fabrica = $2";

                    $resParametrosAdicionais = pg_query_params(
                        $con,
                        $queryParametrosAdicionais,
                        [pg_escape_string($parametrosAdiconais), $login_fabrica]
                    );

                    if (strlen(pg_last_error()) === 0) {
                        $response = ['message' => utf8_encode('Vínculo realizado com sucesso.')];
                        pg_query($con, "COMMIT");
                    } else {
                        $response = ['exception' => utf8_encode(pg_last_error())];
                        pg_query($con, "ROLLBACK");
                    }
                } else {
                    $response = ['exception' => utf8_encode(json_last_error_msg())];
                }
            } else {
                $response = ['exception' => 'Ocorreu um erro. Tente novamente.'];
            }

            echo json_encode($response);
            break;
    }

    exit;
}

include 'funcoes.php';

$layout_menu = "cadastro";
$title = "AUTENTICAÇÃO COM INSTAGRAM";

include "cabecalho_new.php";

$plugins = ["font_awesome", "select2"];
include ("plugin_loader.php");

?>

<style>
    .control-label {transition: 0.5s color}
</style>

<div class="tabbable">
    <ul class="nav nav-tabs">
        <li id="linkone" class="active"><a href="#tab1" data-toggle="tab">Contas Adicionadas</a></li>
    	<li id="linktwo"><a href="#tab2" data-toggle="tab">Vincular</a></li>
    </ul>
    <div class="tab-content">
    	<div class="tab-pane active" id="tab1">
    		<form>
	            <table class="pages-list table table-striped table-bordered table-hover">
                    <col width="125">
                    <col width="125">
                    <col width="50">
					<thead style="font-size:15px;background-color:#596D9B;color:#FFF;">
						<tr>
							<th>Página</th>
							<th>Instagram Business Account</th>
							<th>Ativa</th>
						</tr>
					</thead>
					<tbody id="pages-wrapper">
                        <tr class="noaccfound">
                            <td colspan="3" style="text-align:center">Nenhuma conta adicionada</td>
                        </tr>
                    </tbody>
				</table>
			</form>
        </div>
    	<div class="tab-pane" id="tab2">
            <div class="row-fluid">
                <div class="span3"></div>
                <div class="span6" style="text-align:justify;font-size:14px">
                    Para adicionar uma nova página para realizarmos o monitoramento,
                    é necessária a autenticação através do <b>Facebook</b>.
                </div>
                <div class="span3"></div>
            </div>
            <div class="row-fluid" style="text-align:center;font-size:14px;margin:20px 0;">
                <div class="span3"></div>
                <div class="span6">
                    <ul>
                        <li>Um único perfil deverá ser utilizado;</li>
                        <li>
                            O perfil utilizado deverá ser administrador das <b>páginas</b> vinculadas às
                            <span style="font-style:italic;"><b>Instagram Business Accounts</b></span>;
                        </li>
                        <li><b>Leia e confirme</b> as requisições de <b>permissões</b> realizadas.</li>
                    </ul>
                </div>
                <div class="span3"></div>
            </div>
            <div class="row-fluid row-link" style="display:none;">
                <div class="span3"></div>
                <div class="span6" style="text-align:center;">
                    <button type="button" class="btn btn-primary" id="login-fb">
                        <i class="fab fa-facebook" style="margin-right:5px;font-size:1.2em"></i>
                        Continuar com o Facebook
                    </button>
                </div>
                <div class="span3"></div>
            </div>
            <div class="row-fluid row-remove-facebook" style="display:none;">
                <div class="span3"></div>
                <div class="span6" style="text-align:center;">
                    <button class="btn btn-primary" type="button">
                        <i class="fab fa-facebook" style="margin-right:5px;font-size:1.2em"></i>
                        Remover conta do Facebook
                    </button>
                </div>
                <div class="span3"></div>
            </div>
            <form class="form-pages" style="display:none">
                <div class="row-fluid row-pages">
                    <div class="span3"></div>
                    <div class="span6">
                        <div class="control-group">
                            <label class="control-label">Páginas vinculadas:</label>
                            <div class="controls">
                                <select class="control-form" name="ig-pages" style="width:100%">
                                    <option value="">Selecione</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="span3"></div>
                </div>
                <div class="row-fluid">
                    <div class="span3"></div>
                    <div class="span6">
                        <div class="control-group">
                            <div class="controls" style="text-align:right;">
                                <button type="button" class="btn btn-success" id="btn-link-ig">
                                    <i class="fas fa-link" style="margin-right:5px"></i>
                                    Vincular
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="span3"></div>
                </div>
            </form>
        </div>
    </div>
</div>
<? include "rodape.php"; ?>

<script>
    window.fbAsyncInit = function() {
        FB.init({
            appId      : '<?= $appId ?>',
            cookie     : true,
            xfbml      : true,
            version    : 'v3.3'
        });

        FB.AppEvents.logPageView();
    };

    (function(d, s, id){
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) {return;}
        js = d.createElement(s); js.id = id;
        js.src = "https://connect.facebook.net/en_US/sdk.js";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));

    $(function () {
        'use strict';

        $(".form-pages select").select2({width: 'resolve'});

        getLinkedAccounts(function (response) {
            response = JSON.parse(response);

            if (!response.exception && response.length > 0) {
                let tbody = $(".pages-list").find("tbody");
                $(tbody).find(".noaccfound").slideUp('fast');

                $.each(response, function (i, e) {
                    addTableRow(e);
                });
            }
        });

        $(".row-remove-facebook").find("button").on({
            mouseenter: function () {
                $(this).addClass("btn-danger");
                $(this).removeClass("btn-primary");
            },
            mouseleave: function () {
                $(this).addClass("btn-primary");
                $(this).removeClass("btn-danger");
            },
            click: function () {
                if (confirm("Este processo irá remover todas suas contas atualmente vinculadas, além da conta do Facebook registrada no sistema.\nDeseja realizar esta Ação?")) {
                    removeFacebookAccount(function (response) {
                        response = JSON.parse(response);
                        if (response.exception)
                            return alert(response.exception);

                        alert(response.message);
                        window.location.reload();
                    });
                }
            }
        })

        $("#btn-link-ig").on("click", function () {
            let selectPages = $(".form-pages select[name=ig-pages]");
            let selectedOption = $(selectPages).find("option:selected");

            if ($(selectedOption).val().length === 0) {
                $(selectPages).parents(".control-group").addClass("error");
                return setTimeout(function () {
                    $(selectPages).parents(".control-group").removeClass("error");
                }, 1000);
            }

            let igData = JSON.parse($(selectedOption).val());

            linkBusinessAccount(igData, function (response) {
                response = JSON.parse(response);
                if (response.exception)
                    return alert(response.exception);

                let tbody = $(".pages-list").find("tbody");
                $(tbody).find(".noaccfound").slideUp('fast');

                addTableRow(igData);

                $("#linkone").trigger("click");
                $("#linkone").addClass("active");
                $("#tab1").addClass("active");

                $("#tab2").removeClass("active");
                $("#linktwo").removeClass("active");
            });
        });

        $("#login-fb").on("click", function () {
            FB.getLoginStatus(function (response) {
                if (typeof response.authResponse === "undefined" || response.authResponse === null) {
                    FB.login(function (res) {
                        if (res.status === "connected") {
                            checkPermissions(res);
                        }
                    }, {scope: 'manage_pages,instagram_basic,instagram_manage_comments,pages_show_list,instagram_manage_insights'});
                } else {
                    checkPermissions(response);
                }
            });
        });

        $("#linkone").on("click", function () {
            $(".row-link").slideUp('slow');
            $(".form-pages").slideUp('slow');
        })

        $("#linktwo").on("click", function () {
            var select = $(".form-pages select[name=ig-pages]");
            $(select).prop({disabled: true});

            getOAuth(function (response) {
                response = JSON.parse(response);
                if (response.exception || response.length === 0) {
                    $(".row-link").slideDown('slow');
                } else {
                    listAllPages(response.userLongLivedAccessToken, function (res) {
                        res = JSON.parse(res);
                        if (res.exception && res.code == 190) {
                            return $(".row-link").slideDown('slow');
                        } else if (res.exception && res.code != 190) {
                            return alert('Falha ao buscar páginas vinculadas ao usuário: ' + res.exception);
                        }

                        $(select).val("");
                        $(select).find("option").remove();
                        $(select).append("<option value=''>Selecione</option>");
                        $(".form-pages select").select2({width: 'resolve'});

                        $(select).prop({disabled: false});

                        $.each(res, function (i, e) {
                            let value = {
                                pageId: e.id,
                                pageName: e.name,
                                instagramBusinessAccount: e.instagram_business_account,
                                pageAccessToken: e.access_token
                            };

                            let option = $("<option></option>", {
                                value: JSON.stringify(value),
                                text: e.name,
                            });

                            $(select).append(option);
                        });

                        $(".form-pages").slideDown('slow');
                        $(".row-remove-facebook").slideDown('slow');
                    });
                }
            });
        });

        function addTableRow(data) {
            let tbody = $(".pages-list").find("tbody");

            let tr = $("<tr></tr>");
            let tdPage = $("<td></td>", {text: data.pageName, css: {'text-align': 'center', 'vertical-align': 'middle'}});
            let tdIg = $("<td></td>", {text: data.instagramBusinessAccount.name, css: {'text-align': 'center', 'vertical-align': 'middle'}});

            let tdActive = $("<td></td>", {css: {'text-align': 'center', 'vertical-align': 'middle'}});

            let buttonIcon = $("<button></button>", {
                attr: {
                    type: "button",
                    "data-page": data.pageId
                },
                class: "btn btn-success btn-mini btn-page"
            });

            let iconSuccess = $("<i></i>", {class: "fas fa-check"});
            let iconError = $("<i></i>", {class: "fas fa-times"});

            $(buttonIcon).on({
                mouseenter: function () {
                    $(buttonIcon).addClass("btn-danger");
                    $(buttonIcon).removeClass("btn-success");
                    $(buttonIcon).html(iconError);
                },
                mouseleave: function () {
                    $(buttonIcon).addClass("btn-success");
                    $(buttonIcon).removeClass("btn-danger");
                    $(buttonIcon).html(iconSuccess);
                },
                click: function () {
                    if (confirm('Este processo impedirá a abertura de novos chamados relacionados a esta conta. Deseja realizar esta operação?')) {
                        removeLinkedAccount(data.pageId, function (response) {
                            $(buttonIcon).parents("tr").slideUp('slow', function () {
                                $(this).remove();

                                if ($(tbody).find("tr").length === 1)
                                    $(tbody).find(".noaccfound").slideDown('fast');
                            })
                        });
                    }
                }
            });

            $(buttonIcon).append(iconSuccess);

            $(tdActive).append(buttonIcon);
            $(tr).append(tdPage, tdIg, tdActive);
            $(tbody).append(tr);
        }

        function removeFacebookAccount(callback) {
            $.ajax({
                url: window.location,
                data: {
                    ajax: 'removeFacebookAccount',
                },
                method: 'POST',
                async: true
            }).fail(function () {
                alert('Falha ao remover conta do Facebook e contas do Instagram vinculadas.');
            }).done(function (response) {
                callback(response);
            });
        }

        function removeLinkedAccount(page, callback) {
            $.ajax({
                url: window.location,
                data: {
                    ajax: 'removeLinkedAccount',
                    pageId: page
                },
                method: 'POST',
                async: true
            }).fail(function () {
                alert('Falha ao remover conta vinculada.');
            }).done(function (response) {
                callback(response);
            });
        }

        function getLinkedAccounts(callback) {
            $.ajax({
                url: window.location,
                data: {
                    ajax: 'getLinkedAccounts'
                },
                type: 'POST',
                async: true
            }).fail(function () {
                alert('Falha ao buscar páginas vinculadas.')
            }).done(function (response) {
                callback(response);
            })
        }

        function linkBusinessAccount(igData, callback) {
            $.ajax({
                url: window.location,
                data: {
                    ajax: 'linkBusinessAccount',
                    igData: igData
                },
                type: 'POST',
                async: true
            }).fail(function () {
                alert('Falha ao vincular contas. Tente novamente.');
            }).done(function (response) {
                callback(response);
            });
        }

        function listAllPages(accessToken, callback) {
            $.ajax({
                url: window.location,
                type: 'POST',
                data: {
                    ajax: 'listAllPages',
                    accessToken: accessToken
                },
                async: true
            }).fail(function () {
                alert('Falha ao buscar páginas vinculadas ao usuário. Tente novamente.');
            }).done(function (response) {
                callback(response);
            });
        }

        function getOAuth(callback) {
            $.ajax({
                url: window.location,
                type: 'POST',
                data: {
                    ajax: 'getOAuth'
                },
                async: true
            }).fail(function () {
                alert('Falha ao buscar dados de autorização do aplicativo. Tente novamente');
            }).done(function (response) {
                callback(response);
            })
        }

        function checkPermissions(auth) {
            FB.api('/me/permissions', function (r) {
                let permissions = r.data;
                let missingPerms = false;

                $.each(permissions, function (i, e) {
                    if (e.status !== "granted")
                        missingPerms = true;
                });

                if (missingPerms)
                    return alert('Não foram concedidas todas as permissões necessárias.');

                successAuth(auth);
            })
        }

        function successAuth(auth) {
            FB.api('/oauth/access_token', 'post', {
                fb_exchange_token: auth.authResponse.accessToken,
                grant_type: 'fb_exchange_token',
                client_id: '<?= $appId ?>',
                client_secret: '<?= $appSecret ?>'
            }, function (response) {
                if ((!response.error) && (response.access_token.length > 0 && response.token_type === "bearer")) {
                    auth.authResponse.accessToken = response.access_token;

                    $.ajax({
                        url: window.location,
                        type: 'POST',
                        data: {
                            ajax: 'persistOAuth',
                            auth: {
                                accessToken: response.access_token,
                                userID: auth.authResponse.userID,
                                signedRequest: auth.authResponse.signedRequest
                            }
                        },
                        async: true
                    }).fail(function () {
                        alert('Ocorreu uma falha ao vincular as contas. Tente novamente em instantes.');
                    }).done(function (res) {
                        if (res.exception)
                            return alert(res.exception);

                        var select = $(".form-pages select[name=ig-pages]");
                        $(select).prop({disabled: true});

                        listAllPages(auth.authResponse.accessToken, function (r) {
                            r = JSON.parse(r);
                            if (r.exception && r.code == 190) {
                                return $(".row-link").slideDown('slow');
                            } else if (r.exception && r.code != 190) {
                                return alert('Falha ao buscar páginas vinculadas ao usuário: ' + r.exception);
                            }

                            $(select).val("");
                            $(select).find("option").remove();
                            $(select).append("<option value=''>Selecione</option>");
                            $(".form-pages select").select2({width: 'resolve'});

                            $(select).prop({disabled: false});

                            $.each(r, function (i, e) {
                                let value = {
                                    pageId: e.id,
                                    pageName: e.name,
                                    instagramBusinessAccount: e.instagram_business_account,
                                    pageAccessToken: e.access_token
                                };

                                let option = $("<option></option>", {
                                    value: JSON.stringify(value),
                                    text: e.name,
                                });

                                $(select).append(option);
                            });

                            $(".row-link").slideUp('slow', function () {
                                $(".row-remove-facebook").slideDown('slow');
                                $(".form-pages").slideDown('slow');
                            });
                        });
                    });
                }
            });
        }
    })
</script>