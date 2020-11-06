<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
    include 'autentica_admin.php';
    define('APPBACK', '../');
    $areaAdmin = true;    
} else {
    define('APPBACK', '');
    include 'autentica_usuario.php';
}

$posto = $_GET['posto'];

header('Content-Type: text/html; charset=iso-8859-1');

$title = "CONSULTA DADOS DO POSTO NO RECEITA";
define('BI_BACK', (strpos($_SERVER['PHP_SELF'],'/bi/') == true)?'../':'');


if(isset($_POST['btnacao'])){

    $cnpj   = $_POST['cnpj'];
    $posto  = $_POST['posto'];
    $cidade_alteracao = false;


    $sqlPosto = "SELECT tbl_posto.nome, 
                        tbl_posto.cnpj,
                        tbl_posto_fabrica.contato_endereco, 
                        tbl_posto_fabrica.contato_numero, 
                        tbl_posto_fabrica.contato_complemento,
                        tbl_posto_fabrica.contato_bairro,
                        tbl_posto_fabrica.contato_cidade,
                        tbl_posto_fabrica.contato_estado, 
                        tbl_posto_fabrica.nome_fantasia,
                        tbl_posto_fabrica.contato_email,
                        tbl_posto_fabrica.contato_cep
                    FROM tbl_posto 
                    INNER JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
                    WHERE tbl_posto.posto = $posto and tbl_posto_fabrica.fabrica = $login_fabrica ";
    $resPosto = pg_query($con, $sqlPosto);
    if(strlen(pg_last_error($con))){
        $msg_erro .= pg_last_error($con);
    }
    if(pg_num_rows($resPosto)>0){
        $nome_banco = mb_strtoupper(pg_fetch_result($resPosto, 0, nome), 'iso-8859-1');
        $endereco_banco = utf8_decode(pg_fetch_result($resPosto, 0, contato_endereco));
        $numero_banco = pg_fetch_result($resPosto, 0, contato_numero);
        $complemento_banco = pg_fetch_result($resPosto, 0, contato_complemento);
        $bairro_banco = pg_fetch_result($resPosto, 0, contato_bairro);
        $cidade_banco = mb_strtoupper(pg_fetch_result($resPosto, 0, contato_cidade), 'iso-8859-1');
        $estado_banco = pg_fetch_result($resPosto, 0, contato_estado);
        $email_banco = pg_fetch_result($resPosto, 0, contato_email);
        $fantasia_banco = pg_fetch_result($resPosto, 0, nome_fantasia);
        $cep_banco = pg_fetch_result($resPosto, 0, contato_cep);
    }


    $endereco       = trim($_POST['endereco']);
    $numero         = trim($_POST['numero']);
    $complemento    = trim($_POST['complemento']);
    $municipio      = trim($_POST['municipio']);
    $uf             = trim($_POST['uf']);
    $bairro         = trim($_POST['bairro']);
    $cep            = trim($_POST['cep']);
    $nome           = trim($_POST['nome']);
    $fantasia       = trim($_POST['fantasia']);
    

    if(strlen($fantasia)> 0 and ($fantasia != $fantasia_banco)){
        $campo_fantasia = "nome_fantasia = UPPER('$fantasia'), ";
    }
    if(strlen($bairro)>0 and ($bairro != $bairro_banco)){
        $campo_bairro = "contato_bairro = UPPER('$bairro'), ";
    }
    if(strlen($endereco)>0 and ($endereco != $endereco_banco)){
        $campo_endereco = "contato_endereco = UPPER('$endereco'), ";
    }
    if(strlen($numero)>0 and ($numero != $numero_banco)){
        $campo_numero = "contato_numero = UPPER('$numero'), ";
    }
    if(strlen($complemento)>0 and ($complemento != $complemento_banco)){
        $campo_complemento = "contato_complemento = UPPER('$complemento'), ";
    }
    if(strlen($cep)>0 and ($cep != $cep_banco)){
        $campo_cep = " contato_cep = '$cep', ";
    }
    if(strlen($municipio)>0 and ($municipio != $cidade_banco)){
        $campo_municipio = " contato_cidade = UPPER('$municipio'), ";
        $cidade_alteracao = true;
    }
    if(strlen($uf)>0 and ($uf != $estado_banco )){
        $campo_estado = " contato_estado = UPPER('$uf'), ";
        $cidade_alteracao = true;
    }  

    if($cidade_alteracao == true){
        $sqlLimpaIBGE = "UPDATE tbl_posto_fabrica 
                            SET  cod_ibge = null            
                        WHERE posto = $posto and fabrica = $login_fabrica";
        $resLimpaIBGE = pg_query($con, $sqlLimpaIBGE);

        if(strlen(pg_last_error($con))>0){
            $msg_erro .= pg_last_error($con);
        }

        $sql_ibge = "SELECT cod_ibge FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais('{$municipio}')) AND UPPER(estado) = UPPER('{$uf}')";
        $res_ibge = pg_query($con, $sql_ibge);
        if (pg_num_rows($res_ibge) > 0) {
            $addressIbge = pg_fetch_result($res_ibge, 0, cod_ibge);
            $campo_cod_ibge = " cod_ibge = $addressIbge , ";
        }else{
            $msg_erro .= "Cidade não encontrada. <br>";
        }
    }

    if(strlen(trim($msg_erro))==0){

        $res = pg_query ($con,"BEGIN TRANSACTION");

        if(strlen($nome)>0 and ($nome != $nome_banco)){
            $sqlPosto = "UPDATE tbl_posto SET nome = UPPER('$nome') WHERE posto = $posto";
            $resPosto = pg_query($con, $sqlPosto);
            $msg_erro = pg_last_error($con);
        }

        $sqlPostoFabrica = "UPDATE tbl_posto_fabrica 
            SET  
            $campo_fantasia 
            $campo_bairro 
            $campo_endereco 
            $campo_numero
            $campo_complemento 
            $campo_cep 
            $campo_municipio  
            $campo_cod_ibge
            $campo_estado 
            obs = obs
        WHERE posto = $posto and fabrica = $login_fabrica";
        $resPostoFabrica = pg_query($con, $sqlPostoFabrica); 
        $msg_erro = pg_last_error($con);
        
        if(strlen($msg_erro)>0){
            $res = pg_query ($con,"rollback TRANSACTION");
            $msg_erro = "Falha ao atualizar dados";
        }else{
            $res = pg_query ($con,"commit TRANSACTION");
            $ok = "Dados atualizado com sucesso.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title><?=$title?></title>
		<link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>bootstrap/css/bootstrap.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>bootstrap/css/extra.css" />
		<!-- <link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>css/tc_css.css" /> -->
		<!-- <link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>css/tooltips.css" /> -->
		<link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>bootstrap/css/ajuste.css" />

		<script src="<?=BI_BACK?>plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="<?=BI_BACK?>bootstrap/js/bootstrap.js"></script>

        <script type="text/javascript">
            
            $(function(){
                $("#btn_atualizar").click(function(){
                    $("#btn_atualizar").hide();
                    $("#loading_pre_cadastro").show();
                });
            });

        </script>

		<style>
            .diferente{
                color:red;
            }
			
		</style>
		
	</head>
<body>

	
<?php 

//if (count($dataTable)):
	$tableAttrs = array(
		'tableAttrs'   => ' class="table table-striped table-bordered table-hover table-fixed"',
		'captionAttrs' => ' class="titulo_tabela"',
		'headerAttrs'  => ' class="titulo_coluna"',
	);
?>

<?php 
            $sqlPosto = "SELECT tbl_posto.nome, 
                                tbl_posto.cnpj,
                                tbl_posto_fabrica.contato_endereco, 
                                tbl_posto_fabrica.contato_numero, 
                                tbl_posto_fabrica.contato_complemento,
                                tbl_posto_fabrica.contato_bairro,
                                tbl_posto_fabrica.contato_cidade,
                                tbl_posto_fabrica.contato_estado, 
                                tbl_posto_fabrica.nome_fantasia,
                                tbl_posto_fabrica.contato_email,
                                tbl_posto_fabrica.contato_cep
                            FROM tbl_posto 
                            INNER JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
                            WHERE tbl_posto.posto = $posto and tbl_posto_fabrica.fabrica = $login_fabrica ";
            $resPosto = pg_query($con, $sqlPosto);
            if(pg_num_rows($resPosto)>0){
                $cnpj = pg_fetch_result($resPosto, 0, cnpj);
                $nome = mb_strtoupper(pg_fetch_result($resPosto, 0, nome), 'iso-8859-1');
                $endereco = mb_strtoupper(pg_fetch_result($resPosto, 0, contato_endereco), 'iso-8859-1');
                $numero = pg_fetch_result($resPosto, 0, contato_numero);
                $complemento = mb_strtoupper(pg_fetch_result($resPosto, 0, contato_complemento), 'iso-8859-1');
                $bairro = mb_strtoupper(pg_fetch_result($resPosto, 0, contato_bairro), 'iso-8859-1');
                $cidade = mb_strtoupper(pg_fetch_result($resPosto, 0, contato_cidade), 'iso-8859-1');
                $estado = mb_strtoupper(pg_fetch_result($resPosto, 0, contato_estado));
                $fantasia = mb_strtoupper(pg_fetch_result($resPosto, 0, nome_fantasia), 'iso-8859-1');
                $cep = pg_fetch_result($resPosto, 0, contato_cep);

                $endereco_completo = $endereco. " ". $numero. " ". $complemento;

                $cidade_completa = $cidade. " ". $estado;
            }

            if(strlen(trim($ok))==0){
                $curl = curl_init();
                curl_setopt_array($curl, array(
                CURLOPT_URL => "https://www.receitaws.com.br/v1/cnpj/".$cnpj,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                ));

                $response = curl_exec($curl);
                $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                $err = curl_error($curl);

                curl_close($curl);           

                if($status_code == '200'){
                    $dados = json_decode($response, true);

                    $endereco_completo_receita = utf8_decode($dados['logradouro']). " ". $dados['numero']. " ". $dados['complemento'];
                    $cidade_completa_receita = $dados['municipio']. " ". $dados['uf'];

                    $dados['cep'] = str_replace(array(".", "-"), "", $dados['cep']);
                  
                }else{
                    $msg_erro = "Falha ao consultar dados da receita";
                }
            }
        ?>  
	<div class="container">
        <?php if(strlen($msg_erro)>0){ ?>
        <div class="row-fluid">
            <div class="alert alert-danger"><?=$msg_erro?></div>
        </div>
        <?php } if(strlen($ok)>0){ ?>
        <div class="row-fluid">
            <div class="alert alert-success"><?=$ok?></div>
        </div>
        <?php } ?>
        <?php  if(strlen($msg_erro)==0 and strlen($ok)==0){ ?>
		<div class="row-fluid">
        <span class='col-md-12 diferente' style='text-align: right;'>Diferenças em Vermelho</span>
		<table class="table table-striped table-bordered table-hover table-fixed">
            <thead  class="titulo_coluna">
                <tr>
                    <th colspan="2">Consulta Dados do Posto na Receita</th>
                </tr>
                <tr>
                    <th>Dados da Base</th>
                    <th>Dados da Receita</th>
                </tr>
            </thead>
            <body>                      
                <tr>
                    <td><b>Razão Social:</b> <?=$nome?></td>
                    <td <?php if($nome != $dados['nome'] ){ echo "class='diferente' "; } ?> ><b>Razão Social:</b> <?php echo $dados['nome']; ?> </td>
                </tr>
                <tr>
                    <td><b>Nome Fantasia:</b> <?=$fantasia?></td>
                    <td <?php if($fantasia != $dados['fantasia'] ){ echo "class='diferente' "; } ?> ><b>Nome Fantasia:</b> <?php echo $dados['fantasia']; ?></td>
                </tr>
                <tr>
                    <td><b>Endereço:</b> <?=$endereco_completo ?></td>
                    <td <?php if($endereco_completo != $endereco_completo_receita ){ echo "class='diferente' "; } ?> ><b>Endereço:</b> <?php echo $endereco_completo_receita; ?></td>
                </tr>
                <tr>
                    <td><b>Bairro:</b> <?=$bairro?></td>
                    <td <?php if($bairro != $dados['bairro'] ){ echo "class='diferente' "; } ?> ><b>Bairro:</b> <?php echo $dados['bairro']; ?></td>
                </tr>
                <tr>
                    <td><b>Cidade/UF:</b> <?=$cidade_completa?></td>
                    <td <?php if($cidade_completa != $cidade_completa_receita ){ echo "class='diferente' "; } ?> ><b>Cidade/UF:</b> <?php echo $cidade_completa_receita; ?></td>
                </tr>
                <tr>
                    <td><b>CEP:</b> <?=$cep?></td>
                    <td <?php if($cep != $dados['cep'] ){ echo "class='diferente' "; } ?> ><b>CEP:</b> <?php echo $dados['cep']; ?></td>
                </tr>
            </body>
        </table>
		</div>
        <div class="row-fluid">
            <div class="col-md-12">
                <center>
                    <form method="POST" action=''>
                        <!-- Dados que vão alterar -->
                        <input type="hidden" name="nome" value="<?=$dados[nome]?>">
                        <input type="hidden" name="fantasia" value="<?=$dados[fantasia]?>">
                        <input type="hidden" name="endereco" value="<?=utf8_decode($dados['logradouro']) ?>">
                        <input type="hidden" name="numero" value="<?=$dados[numero]?>">
                        <input type="hidden" name="complemento" value="<?=$dados[complemento]?>">
                        <input type="hidden" name="bairro" value="<?=$dados[bairro]?>">
                        <input type="hidden" name="municipio" value="<?=$dados[municipio]?>">
                        <input type="hidden" name="uf" value="<?=$dados[uf]?>">
                        <input type="hidden" name="cep" value="<?=$dados[cep]?>">

                        <input type="hidden" name="posto" value="<?=$posto?>">
                        <input type="hidden" name="cnpj" value="<?=$cnpj?>">
                        <input type="submit" name="btnacao" id="btn_atualizar" value="Atualizar Dados">
                        <img src="imagens/loading_img.gif" style="display: none; height: 20px; width: 20px;" id="loading_pre_cadastro" />
                    </form>
                </center>
            </div>
        </div>
        <?php } ?>
	</div>
<?php //endif; ?>
</body>
</html>
