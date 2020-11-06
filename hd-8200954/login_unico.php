<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
// include_once 'autentica_usuario.php';
include_once 'login_unico_autentica_usuario.php';
include_once 'regras/menu_posto/menu.helper.php';
include_once 'classes/Posvenda/Seguranca.php';

$objSeguranca = new \Posvenda\Seguranca(null,$con);

if ($_POST["ajax_altera_depois"] == true) {
    $retorno = $objSeguranca->gravaAlterarDepois(null, null, $login_unico);
    if ($retorno) {
        setcookie("senha_skip", true, time() + (86400 * 30), "/");
        exit(json_encode(["erro" => false]));
    } else {
        exit(json_encode(["erro" => true]));
    }
}

if (filter_input(INPUT_GET,"id")) {
    $fabrica = filter_input(INPUT_GET,"id");

    $sql = "
        SELECT  tbl_posto_fabrica.oid as posto_fabrica,
                tbl_posto_fabrica.posto,
                tbl_posto_fabrica.fabrica
        FROM    tbl_posto_fabrica
        WHERE   fabrica         = $fabrica
        AND     credenciamento  <> 'DESCREDENCIADO'
        AND     posto           = $login_posto";
    // echo $sql;
    $res = pg_query($con, $sql);

    if (pg_numrows($res) > 0) {
        remove_cookie($cookie_login, "cook_posto_fabrica");
        remove_cookie($cookie_login, "cook_posto");
        remove_cookie($cookie_login, "cook_fabrica");
        remove_cookie($cookie_login, "cook_login_posto");
        remove_cookie($cookie_login, "cook_login_nome");
        remove_cookie($cookie_login, "cook_login_cnpj");
        remove_cookie($cookie_login, "cook_login_fabrica");
        remove_cookie($cookie_login, "cook_login_fabrica_nome");
        remove_cookie($cookie_login, "cook_login_pede_peca_garantia");
        remove_cookie($cookie_login, "cook_login_tipo_posto");
        remove_cookie($cookie_login, "cook_login_e_distribuidor");
        remove_cookie($cookie_login, "cook_login_distribuidor");
        remove_cookie($cookie_login, "cook_pedido_via_distribuidor");

        add_cookie($cookie_login, "cook_posto_fabrica", pg_result($res, 0, 'posto_fabrica'));
        add_cookie($cookie_login, "cook_posto",         pg_result($res, 0, 'posto'));
        add_cookie($cookie_login, "cook_fabrica",       pg_result($res, 0, 'fabrica'));

        set_cookie_login($token_cookie, $cookie_login);

        if ($_GET['loginAcacia'] == 1) {
            header("Location: cadastro_pedido.php");
            exit;
        }

        if (strlen($os)>0) {
            header("Location: os_item.php?os=$os");
        } else {
            header('Location: login.php');
        }
        exit;
    }
}



if($login_fabrica <> 87 AND !in_array($login_posto,[6359,4311])){
	$sql = "SELECT pesquisa FROM tbl_pesquisa WHERE pesquisa = 677 AND ativo";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0){ 
		$sql = "SELECT resposta FROM tbl_resposta WHERE pesquisa = 677 AND posto = $login_posto AND data_input::date = CURRENT_DATE";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) == 0){
?>  
			<script src="js/jquery-1.8.3.min.js"></script>                                                                                            
<?
$plugins = array(
	    "shadowbox"
    );
include("plugin_loader.php");
?>
			<script type="text/javascript">

			window.onload = function(){
				Shadowbox.init();

				Shadowbox.open({
					content : "pesquisa_situacao_atendimento_posto.php?pesquisa=677&posto=<?=$login_posto?>",
					player: 'iframe',
					title : "Pesquisa",
					width   :   900,
					height  :   400,
					options: {
						modal: true,                                                         
						enableKeys: false,
						displayNav: false
					}
				});
			}
			</script>
			<?php
			exit;
		}
}
}

$sql = "SELECT tbl_fabrica.fabrica, tbl_fabrica.nome AS fabrica_nome
          FROM tbl_fabrica
          JOIN tbl_posto_fabrica USING (fabrica)
         WHERE tbl_posto_fabrica.credenciamento IN ('CREDENCIADO','EM DESCREDENCIAMENTO')
           AND tbl_posto_fabrica.senha NOT IN ('','*')
           AND fabrica NOT IN(0, 168)
           AND ativo_fabrica IS TRUE
           AND posto = $cook_posto
      ORDER BY nome";

$fabricas    = pg_fetch_all(pg_query($con, $sql));
$fabricantes = array();

foreach ($fabricas as $f) {
    $fabricantes[$f['fabrica']] = array(
        'links' => "?id=".$f['fabrica'],
        'title' => $f['fabrica_nome'],
        'icon'  => MenuPosto::getLogoFabrica($f['fabrica'])['src'],
    );
}

// if (array_key_exists(168, $fabricantes) and $_GET['loginAcacia'] == '1') {
//     header("Location: login_unico.php?id=168&loginAcacia=1");
//     exit;
// }

/**
 * HD 1060482 - Banner publicidade sobre o BANNER Telecontrol para postos autorizados
 *              Postos exclusivos Makita ou Bosch, não mostrar
 *              17673 é posto interno Bosch e Bosch Security, dá tot_fabricas == 2.
 **/
$show_banner_mondial = array_key_exists(151, $fabricantes);

$title = traduz('login.unico');
include_once 'cabecalho.php';
$xxvalidaSenhaAntiga = $objSeguranca->getAlteracaoSenha(null,$login_unico);
?>

<script>

<?php  if (isset($xxvalidaSenhaAntiga) && $xxvalidaSenhaAntiga && !isset($_COOKIE["senha_skip"])) { ?> 
    
    window.onload = function() {   

        Shadowbox.init({
            skipSetup: true
        });

       // carregaBoxAlterarSenha();
    }
    
<?php } else { ?>

    window.onload = function() {
        usuariosLoginUnico(<?=$login_posto?>);
    }
    
<?php } ?>

    function retornaLink(sucesso = false) {
        if (!sucesso) {
            gravaAlterarDepois();
        }

        window.location = 'login_unico.php?skip=true';

    }
    function gravaAlterarDepois() {
        $.ajax("login_unico.php", {
            type: 'POST',
            async: false,
            data: {
                ajax_altera_depois: true
            }
        }).done(function (response) {

            response = JSON.parse(response);
            if (response.erro == true) {
                return false;
            }
            return true;

        });


        return false;
    }
    function carregaBoxAlterarSenha() {

        Shadowbox.init({
            skipSetup: true
        });

        Shadowbox.open({
            content : "modal_altera_senha.php?tipo=login_unico",
            player: 'iframe',
            title : "Alterar senha",
            width   :   600,
            height  :   400,
            options: {
                modal: true,
                enableKeys: false,
                displayNav: false,
                onClose: function(){
                    gravaAlterarDepois();
                    usuariosLoginUnico(<?=$login_posto?>); 
                }
            }
        });
    }

</script>

<?
/*
if ($show_banner_mondial and !($tot_fabricas == 1 and in_array($fabrica, array(20,42,96))) or $login_posto == 17673)
    echo $cabecalho->alert(
        '<img src="imagens/banner_acacia_login_unico.jpg" style="height:300; border: 1px solid #999;">',
        'default'
    );
 */

if (count($fabricantes)) {
    echo $cabecalho->alert(traduz('fabricas.atendidas'), 'info', 'list');
    echo $cabecalho->cardsMenu($fabricantes, 160, 90);
}

// Banner de autocredenciamento
if (!($tot_fabricas == 1 and in_array($fabrica, array(20,42,96))) or $login_posto == 17673) { ?>
    <div style="text-align: center;">
        <strong>N&atilde;o perca tempo, cadastre-se já!</strong> <br />
        <a href="externos/autocredenciamento_new.php" style="">
            <img src="imagens/autocredenciamento.jpg" alt="Telecontrol Autocredenciamento" style="cursor:pointer; width: 600px; border: 1px solid #999;">
        </a>
    </div>
<?php
}
?>

<?php include 'rodape.php';

