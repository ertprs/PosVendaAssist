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

$appId = '1743792952341073';
$appSecret = '3f3a4ae3eef8bc8c9bb607df3ce2ded0';

$fb = new Facebook\Facebook([
	'app_id'=> $appId,
	'app_secret' => $appSecret,
	'default_graph_version' => 'v3.1'
]);

if (count($_POST) > 0) {
	switch($_POST['ajax']) {
		case 'checkFB':
			$query_select_params = "SELECT parametros_adicionais FROM tbl_fabrica WHERE fabrica = {$login_fabrica};";
			$result = pg_query($con, $query_select_params);

			$params_adicionais = pg_fetch_assoc($result);
			$params_adicionais = $params_adicionais['parametros_adicionais'];

			$json_params = json_decode($params_adicionais, true);

			if (!$json_params['oAuthFacebook'] OR empty($json_params['oAuthFacebook'])) {
				$response = ['error' => 'oauth'];
			} elseif ($json_params['oAuthFacebook']['userId'] != $_POST['userId']) {
				$response = ['error' => 'id'];
			} else {
				$response = ['banco' => $json_params['oAuthFacebook']['userId'], 'sent' => $_POST['userId']];
			}

			echo json_encode($response);			
			break;
		case 'insertOAuth':
			//// insert origem de chamado
			try {
				$query = "SELECT descricao FROM tbl_hd_chamado_origem WHERE fabrica = $login_fabrica AND descricao = 'Facebook';";
				$result = pg_query($con, $query);

				if (pg_num_rows($result) == 0) {
					$query = "INSERT INTO tbl_hd_chamado_origem (fabrica, descricao, ativo, valida_obrigatorio) VALUES ($1, $2, $3, $4);";
					$params = [$login_fabrica, 'Facebook', 't', 'f'];
					$result = pg_query_params($con, $query, $params);
				}
			} catch(\Exception $e) {
				$e = utf8_encode($e->getMessage());
				$response = ['error' => $e];
				echo json_encode($response);
				exit;
			}

			//// insert oauth no banco

			$shortToken = $_POST['userAccessToken'];
			$fb->setDefaultAccessToken($shortToken);

			$query = "SELECT parametros_adicionais FROM tbl_fabrica WHERE fabrica = $login_fabrica;";
			$result = pg_query($con, $query);

			$params_adicionais = pg_fetch_assoc($result);
			$params_adicionais = $params_adicionais['parametros_adicionais'];

			$json_params = json_decode($params_adicionais, true);

			try {
				$token = $fb->post('/oauth/access_token', [
					'fb_exchange_token' => $shortToken,
					'grant_type' => 'fb_exchange_token',
					'client_id' => $appId,
					'client_secret' => $appSecret
				]);

				$token = $token->getDecodedBody();
				$longLivedAccessToken = $token['access_token'];
				$expires = $token['expires_in'];

				$json_params['oAuthFacebook']['userId'] = $_POST['userId'];
				$json_params['oAuthFacebook']['userLongLivedAccessToken'] = $longLivedAccessToken;
			} catch (\Exception $e) {
				$e = utf8_encode($e->getMessage());
				$response = ['error' => $e];
				echo json_encode($response); 
				exit;
			}

			$new_params = json_encode($json_params);

			$query = "UPDATE tbl_fabrica SET parametros_adicionais = '{$new_params}' WHERE fabrica = $login_fabrica;";
			$result = pg_query($con, $query);

			if (strlen(pg_last_error()) == 0) {
				$response = $new_params;
			} else {
				$e = utf8_encode(pg_last_error());
				$response = ['error' => $e];
				$response = json_encode($response);
			}

			echo $response;
			break;
		case 'listAllPages':
			$query = "SELECT parametros_adicionais FROM tbl_fabrica WHERE fabrica = $login_fabrica;";
			$result = pg_query($con, $query);

			$params_adicionais = pg_fetch_assoc($result);
			$params_adicionais = $params_adicionais['parametros_adicionais'];

			$json_params = json_decode($params_adicionais, true);

			$userLongAccessToken = $json_params['oAuthFacebook']['userLongLivedAccessToken'];

			try {
				$response = $fb->get('/me/accounts', $userLongAccessToken);
				$response = $response->getGraphEdge();
				$response = json_decode($response, true);

				$pages = array_column($json_params['facebookPages'], 'pageId');

				$auxPages = [];
				foreach ($response as $page) {
					if (!in_array($page['id'], $pages) or !in_array('ADMINISTER', $page['perms'])) {
						$auxPages[] = $page;
					}
				}

				$response = $auxPages;				
			} catch (\Exception $e) {
				$e = utf8_encode($e->getMessage());
				$response = ['error' => $e];
			}

			echo json_encode($response);
			break;
		case 'insertPage':
			$errors = 0;

			$query = "SELECT parametros_adicionais FROM tbl_fabrica WHERE fabrica = $login_fabrica";
			$result = pg_query($con, $query);

			$params_adicionais = pg_fetch_assoc($result);
			$params_adicionais = $params_adicionais['parametros_adicionais'];

			$json_params = json_decode($params_adicionais, true);

			if ($errors == 0) {
				$facebookPages = [
					'pageName' => $_POST['pageName'],
					'pageAccessToken' => $_POST['pageAccessToken'],
					'pageId' => $_POST['pageId']
				];
				$json_params['facebookPages'][] = $facebookPages;
				$new_params = json_encode($json_params);

				pg_query($con, "BEGIN");

				$query = "UPDATE tbl_fabrica 
						  SET parametros_adicionais = '{$new_params}'
						  WHERE fabrica = {$login_fabrica}";
				$result = pg_query($con, $query);

				if (strlen(pg_last_error()) > 0) {
					$errors++;
				}

				if ($errors == 0) {
					$responseSubs = $fb->post(
						'/' . $_POST['pageId'] . '/subscribed_apps',
						['access_token' => $_POST['pageAccessToken']]
					);
					$responseSubs = $responseSubs->getGraphNode();
					$responseSubs = json_decode($responseSubs, true);

					if ($responseSubs['success'] == true) {
						$response = "ok";
						pg_query($con, "COMMIT");
					} else {
						$response = "error";
						pg_query($con, "ROLLBACK");
					}
				}
			}

			echo $response;
			break;
		case 'getAddedPages':
			$query = "SELECT parametros_adicionais FROM tbl_fabrica WHERE fabrica = $login_fabrica;";
			$result = pg_query($con, $query);

			$params_adicionais = pg_fetch_assoc($result);
			$params_adicionais = $params_adicionais['parametros_adicionais'];

			$json_params = json_decode($params_adicionais, true);

			$addedPages = [];
			foreach($json_params['facebookPages'] as $page) {
				$addedPages[] = ['pageName' => $page['pageName'], 'pageId' => $page['pageId']];
			}

			echo json_encode($addedPages);
			break;
		case 'removePage':
			$errors = 0;
			$query = "SELECT parametros_adicionais
					  FROM tbl_fabrica
					  WHERE fabrica = {$login_fabrica}";
			$result = pg_query($con, $query);

			$params_adicionais = pg_fetch_assoc($result);
			$params_adicionais = $params_adicionais['parametros_adicionais'];

			$json_params = json_decode($params_adicionais, true);

			foreach ($json_params['facebookPages'] as $key => $added) {
				if ($added['pageId'] == $_POST['pageId']) {
					$accessToken = $added['pageAccessToken'];
					unset($json_params['facebookPages'][$key]);

					$json_params['oAuthFacebook'] = [];
				}
			}

			$new_params = json_encode($json_params);

			pg_query($con, "BEGIN");
			$query = "UPDATE tbl_fabrica
					  SET parametros_adicionais = '{$new_params}'
					  WHERE fabrica = {$login_fabrica}";
			$result = pg_query($query);

			if (strlen(pg_last_error()) > 0) {
				$errors++;
			}

			if ($errors == 0) {
				$responseSubs = $fb->delete('/' . $_POST['pageId'] . '/subscribed_apps', ['access_token' => $accessToken]);
				$responseSubs = $responseSubs->getGraphNode();
				$responseSubs = json_decode($responseSubs, true);

				if ($responseSubs['success'] == true) {
					$response = "ok";
					pg_query($con, "COMMIT");
				} else {
					$response = "error";
					pg_query($con, "ROLLBACK");
				}
			}

			echo $response;
			break;
	}
	exit;
}

include 'funcoes.php';

$layout_menu = "cadastro";
$title = "AUTENTICAÇÃO COM O FACEBOOK";

include "cabecalho_new.php";
include ("plugin_loader.php");

?>

<style>

#login img {
	width:200px;
	cursor:pointer;
}

</style>

<script>
	var appId = <?= $appId ?>;
	var requestType = "request";

    $(function () {
	    $.ajaxSetup({ cache: true });
	    $.getScript('https://connect.facebook.net/pt_BR/sdk.js', function () {
		    FB.init({
		    	appId: appId,
			    version: 'v3.1'
			});
			listAddedPages();
			checkLoginStatus();
		});

		$("#login").on("click", function () {
			loginFacebook();
		});

		$("#btn-add-page").on("click", function () {
			insertPage();
		});
	});

	
	function checkLoginStatus() {
		try {
			FB.getLoginStatus(function (response) {
				if (response.status) {
					FB.api('/me/permissions', function (resp) {
						$(resp.data).each(function (index, element) {
							if (element.status == "declined") {
								requestType = "rerequest";
							}
						});
						if (response.authResponse && requestType == "request") {
							var userId = response.authResponse.userID;
							var userAccessToken = response.authResponse.accessToken;
							$.ajax({
								url: 'autentica_facebook.php',
								type: 'POST',
								data: {
									ajax: 'checkFB',
									userId: userId,
									userAccessToken: userAccessToken
								}
							}).done(function (response) {
								var response = JSON.parse(response);
								if (response.error == 'oauth') {
									$(".login-wrapper").fadeIn(1000);
								} else if (response.error == 'id') {
									$(".warning-wrapper").html("<strong style='text-align:center'>Você está autenticado com uma conta diferente da cadastrada. Por favor, realize o login com a mesma e tente novamente.</strong>");
									$(".warning-wrapper").fadeIn(1000);
								} else {
									listAllPages();
									$(".full-wrapper").fadeIn(1000);
								}
							});
						} else {
							$(".login-wrapper").fadeIn(1000);
						}
					});

				}
				
			}, {scope: ['email', 'manage_pages', 'read_page_mailboxes']});
		} catch (e) {
			alert("Falha ao verificar login");
		}
	}

	function loginFacebook() {
		FB.login(function (resp) {
			if (resp.status == "connected") {
				var error = "false";
				FB.api('/me/permissions', function (response) {
					$(response.data).each(function (index, element) {
						if (element.status == 'declined') {
							error = "true";
						}
					});

					if (error == "false") {
						var userId = resp.authResponse.userID;
						var userAccessToken = resp.authResponse.accessToken;
						$.ajax({
							url: 'autentica_facebook.php',
							type: 'POST',
							data: {
								ajax: 'insertOAuth',
								userId: userId,
								userAccessToken: userAccessToken
							}
						}).done(function (response) {
							response = JSON.parse(response);
							if (response.error) {
								alert("Falha ao realizar autentição. Tente novamente.");
							} else {
								$(".login-wrapper").fadeOut(1000, function () {
									listAllPages();
									$(".full-wrapper").fadeIn(1000);
								});
							}
						});
					} else {
						alert('Não foram aceitas todas as permissões, por favor, realize novamente a autenticação');
					}
				});
			}
		}, {scope: ['email', 'manage_pages', 'read_page_mailboxes']});
	}

	function createPageDOM(element) {
		var tr = $("<tr>");
		$(tr).data("pageid", element.pageId);
		$(tr).addClass("page-element");
		$(tr).attr("style", "display:none");

		var tdOne = $("<td>");
		$(tdOne).html("<b>" + element.pageName + "</br>");
		$(tdOne).attr("style", "text-align:center;font-size:14px;");

		var tdTwo = $("<td>");
		$(tdTwo).html(element.pageId);
		$(tdTwo).attr("style", "text-align:center;font-size:14px;");
		$(tdTwo).addClass("page-id");

		var tdThree = $("<td>");
		$(tdThree).attr("style", "text-align:center;font-size:14px;");

		var btnRemove = $("<button>");
		$(btnRemove).addClass("btn btn-mini btn-danger btn-remove");
		$(btnRemove).data("pageid", element.pageId);
		$(btnRemove).html("&times;");
		$(btnRemove).attr("type", "button");

		$(btnRemove).on("click", function () {
			var pageId = $(this).parents(".page-element").data("pageid");
			if (confirm("Deseja remover esta página?")) {
				removePage(pageId, function (response) {
					if (response == "ok") {
						$(tr).data("pageid", pageId).fadeOut(1000, function () {
							$(this).remove();
							if ($("#pages-wrapper").html() == "") {
								var tr = $("<tr>");
								var td = $("<td>");
								$(td).attr("colspan", "3");
								$(td).attr("style", "text-align:center;font-size:14px;")
								$(td).html("Nenhuma página cadastrada.");

								$(tr).append(td);

								$("#pages-wrapper").append(tr);
							}
							listAllPages();
						});
					}
				});
			}			
		});

		$(tdThree).append(btnRemove);

		$(tr).append(tdOne);
		$(tr).append(tdTwo);
		$(tr).append(tdThree);

		$("#pages-wrapper").append(tr);
		$(tr).fadeIn(500);
	}

	function listAddedPages() {
		$.ajax({
			url: 'autentica_facebook.php',
			type: 'POST',
			data: {
				ajax: 'getAddedPages'
			}
		}).done(function (response) {
			var response = JSON.parse(response);
			if (response.length == 0) {
				var tr = $("<tr>");
				var td = $("<td>");
				$(td).attr("colspan", "3");
				$(td).attr("style", "text-align:center;font-size:14px;")
				$(td).html("Nenhuma página cadastrada.");

				$(tr).append(td);

				$("#pages-wrapper").append(tr);
			} else {
				$("#pages-wrapper").html("");
				$(response).each(function (index, element) {
					createPageDOM(element);
				});
			}			
		});
	}

	function listAllPages() {
		$.ajax({
			url: 'autentica_facebook.php',
			type: 'POST',
			data: {
				ajax: 'listAllPages'
			}
		}).done(function (response) {
			$(".select-page").find("option").remove();
			var response = JSON.parse(response);
			if (response.length == 0) {
				$(".full-wrapper").fadeOut(100, function() {
					$(".warning-wrapper").fadeIn(500);
				});
			} else {
				let options = [];
				$(response).each(function (index, element) {
					$(element).each(function (idx, el) {
						var option = $("<option>");
						$(option).data("token", el.access_token);
						$(option).html(el.name);
						$(option).val(el.id);

						options.push(option)
					});
				});

				let pages = [];
				$.each($(".page-id"), function (key, value) {
					pages.push(String($(value).text()));
				});

				$.each(options, function (key, value) {
					if ($.inArray(String(value.val()), pages) < 0) {
						$(".select-page").append(value); 
						$(".success-wrapper").fadeOut(100);
					}
				});

				$(".full-wrapper").fadeIn(500);
			}
		});
	}

	function insertPage() {
		var pageAccessToken = $(".select-page").find('option:selected').data("token");
		var pageName = $(".select-page").find('option:selected').html();
		var pageId = $(".select-page").val();

		if (pageAccessToken == "" || pageName == "" || pageId == "") {
			alert("Falha ao adicionar página. Tente novamente em instantes.");
			return false;
		}

		$.ajax({
			url: 'autentica_facebook.php',
			type: 'POST',
			data: {
				ajax: 'insertPage',
				pageName: pageName,
				pageAccessToken: pageAccessToken,
				pageId: pageId
			}
		}).done(function (response) {
			$(".select-page").find("option:selected").remove();
			listAddedPages();
		});	
	}

	function removePage(pageId, callback) {
		$.ajax({
			url: 'autentica_facebook.php',
			type: 'POST',
			data: {
				ajax: 'removePage',
				pageId: pageId
			}
		}).done(function (response) {
			callback(response);
		});
	}
</script>

<div class="tabbable">
    <ul class="nav nav-tabs">
        <li id="linkone" class="active"><a href="#tab1" data-toggle="tab">Página Adicionada</a></li>
    	<li id="linktwo"><a href="#tab2" data-toggle="tab">Adicionar</a></li>
    </ul>
    <div class="tab-content">
    	<div class="tab-pane active" id="tab1">
    		<form>
	            <table class="pages-list table table-striped table-bordered table-hover">
					<thead style="font-size:15px;background-color:#596D9B;color:#FFF;">
						<tr>
							<th>Título</th>
							<th>ID</th>
							<th>Ativa</th>
						</tr>
					</thead>
					<tbody id="pages-wrapper">

					</tbody>
				</table>
			</form>
        </div>
    	<div class="tab-pane" id="tab2">
    		<div class="login-wrapper" style="display:none;">
	      		<div class="row-fluid">
	      			<div class="span12" style="font-size:14px;text-align:center">
	      				<h5>Para continuar, siga as instruções abaixo:</h5>
	      			</div>
	      		</div>
	      		<div class="row-fluid">
	      			<div class="span1"></div>
		      		<div class="span10" style="text-align:center;font-size:14px">
		      			Para adicionar uma nova página para realizarmos o monitoramento, é necessária que uma autenticação seja feita através do <b>Facebook</b>.
		      		</div>
		      		<div class="span1"></div>
	      		</div>
	      		<div class="row-fluid" style="text-align:center;font-size:14px;">
	      			- Uma <b>única conta</b> deverá ser utilizada pela fábrica;<br>
	      			- Esta conta deverá ser a <b>administradora</b> da página;<br>
	      			- Clique no botão abaixo e <b>realize o login</b>;<br>
	      			- <b>Leia e confirme</b> as requisições feitas.<br>
	      		</div>
	      		<div class="row-fluid" style="margin-top:20px;">
	      			<div class="span12" style="text-align:center;">
	      				<center id="login"><img src="imagens/botoes/fb-login.png"></center>
	      			</div>
	      		</div>
      		</div>
      		<div class="list-wrapper">
      			<div class="full-wrapper" style="display:none;">
	      			<hr style="width:85%;display:block;margin:0 auto;">
	      			<form>
		  				<div class="row-fluid" style="text-align:center;">
		  					<label class="control-label" for="inputEmail"><h5>Selecione a página que deseja monitorar:</h5></label>
							<div class="controls">
		  						<select class="control-form select-page span6" style="display:block;margin:0 auto;">
		  							
		  						</select>
		  					</div><br>
		  					<small>Acima se situam páginas que ainda <b>não</b> foram adicionas e que possuem <b>permissões administrativas</b>.</small><br>
	      					<small>Verifique se está logado no <b>Facebook</b> com a conta administradora da página que deseja adicionar.</small>
						</div>
						<div class="row-fluid" style="text-align:center;margin-top:50px;">
							<button id="btn-add-page" type="button" class="btn btn-primary">Adicionar</button>
						</div>
		  			</form>
	  			</div>
	  			<div class="warning-wrapper" style="display:none;">
					<div class="row-fluid" style="text-align:center;padding:30px 0;">
						<h5>Atualmente, todas as suas páginas estão sendo monitoradas.</h5>
					</div>
	  			</div>
      		</div>
      		<div class="success-wrapper" style="display:none;">
      			<div class="alert alert-success">Sucesso! Agora as mensagens de sua página estão sendo monitoradas.</div>
      		</div>
        </div>
    </div>
</div>
<? include "rodape.php"; ?>