<?php 
/**
 * Tela de Cadastro de Email de devolução para Colormaq
 * HD 107532
 *
 * @author Augusto Pascutti <augusto.pascutti@telecontrol.com.br>
 */
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="gerencia,cadastros";
include 'autentica_admin.php';

/**
 * Arquivo contento os dados do e-mail
 */
define('COLORMAQ_ARQUIVO_EMAIL_DEVOLUCAO','/var/www/assist/www/admin/documentos/colormaq_email_devolucao.txt');

/**
 * Grava o email de devolucao no local correto 
 *
 * @param string $email_de
 * @param string $assunto
 * @param string $mensagem
 * @return boolean Se 'true' a gravacao foi efetuada com sucesso
 */
function colormaq_grava_email_devolucao($email_de,$assunto,$mensagem) {
    global $login_fabrica;
    
    if ( $login_fabrica != 50 ) { return false; }
    if ( empty($email_de) || empty($assunto) || empty($mensagem) ) { return false; }
    
    $array              = array('email_de'=>$email_de,
                                'assunto'=>$assunto,
                                'mensagem'=>$mensagem);
    $conteudo           = serialize($array);
    $handler            = fopen(COLORMAQ_ARQUIVO_EMAIL_DEVOLUCAO,'w');
    if ( ! is_resource($handler) ) { return false; }
    $sucess             = fwrite($handler,$conteudo);
    fclose($handler);
    return (boolean) ( $sucess !== false );
}

/**
 * Retorna o email de nf de decolucao da colormaq. 
 *
 * @return array('email_de'=>'string',
 *               'assunto'=>'string',
 *               'mensagem'=>'string')
 */
function colormaq_retorna_email_devolucao() {
    global $login_fabrica;
    
    if ( $login_fabrica != 50 || ! file_exists(COLORMAQ_ARQUIVO_EMAIL_DEVOLUCAO) ) { return array(); }
    $handler  = fopen(COLORMAQ_ARQUIVO_EMAIL_DEVOLUCAO,'r');
    if ( ! is_resource($handler) ) { return array(); }
    $conteudo = fread($handler, filesize(COLORMAQ_ARQUIVO_EMAIL_DEVOLUCAO));
    fclose($handler);
    if ( empty($conteudo) ) { return array(); }
    $conteudo = unserialize($conteudo);
    if ( is_array($conteudo) ) {
        return $conteudo;
    }
    return array();
}

$layout_menu = "cadastro";
$title       = "Cadastro de E-mail de Devolução";
include "cabecalho.php";

if ( count($_POST) > 0 ) {
    $email_de = trim($_POST['email_de']);
    $assunto  = trim($_POST['assunto']);
    $mensagem = trim($_POST['mensagem']);
    
    if ( empty($email_de) || empty($assunto) || empty($mensagem) ) {
        $msg_erro = "Por-favor, preencha todos os campos informados !";
    } else {
        if ( colormaq_grava_email_devolucao($email_de,$assunto,$mensagem) ) {
            $msg = "E-mail de devolução cadastrado com sucesso !";
        } else {
            $msg_erro = "Ocorreu um erro durante a gravação do e-mail !";
        }
    }
} else {
    $array = colormaq_retorna_email_devolucao();
    if ( count($array) > 0 ) {
        foreach ($array as $key=>$value) {
            $$key = $value;
        }
    } else {
        $email_de = "suporte@telecontrol.com.br";
        $assunto  = "Por favor, conferir NF de devolução n. __NF__";
    }
}

?>
<style type="text/css" rel="stylesheet" media="all">
.center {
    margin: 0px auto;
}
#email-holder {
    width: 600px;
    margin-top: 20px;
    margin-bottom: 20px;
    border: 1px solid #596D9B;
    background-color: #E6EEF7;
    padding: 2px;
}

#email-holder > h1 {
    font-size: 10pt;
    font-weight: bold;
    color: #FFFFFF;
    background-color: #596D9B;
    width: 100%;
    padding: 5px 0;
    margin: 0 0;
    padding-bottom: 5px;
    text-transform: none; 
}

#email-holder > #email-instrucoes {
    width: 80%;
    margin-top: 10px;
    margin-bottom: 10px;
    text-align: left;
    border: 1px solid #596D9B;
    background-color: #FFFDB6;
    padding: 2px 10px;
}

.email-form dt {
    font-size: 8pt;
    font-weight: bold;
    width: 150px;
    float: left;
    text-align: right;
}

.email-reservadas dt {
    font-size: 7pt;
    font-weight: bold;
    float: left;
    text-align: center;
    width: 130px;
}

.mensagem {
    width: 600px;
    margin-top: 20px;
    margin-bottom: 20px;
    text-align: center;
    padding: 10px 5px;
    font-size: 10pt;
}

.msg-erro {
    border: 1px solid #FF0000;
    background-color: #FF8F8F;
}

.msg-info {
    border: 1px solid #596D9B;
    background-color: #E6EEF7;
}
</style>

<?php if ( isset($msg) || isset($msg_erro) ): ?>
    <div class="center mensagem <?php echo ( isset($msg_erro) ) ? 'msg-erro' : 'msg-info' ; ?>">
        <?php echo ( isset($msg_erro) ) ? $msg_erro : $msg ; ?>
    </div>
<?php endif; ?>

<form action="" method="POST">
    <div id="email-holder" class="center">
        <h1> Informações do E-mail enviado para as Notas de Devolução </h1>
        
        <div id="email-instrucoes" class="center">
            <p>
                Todos os campos abaixo são de preenchimento obrigatório.
            </p>
            <p>
                Os campos <em>Assunto</em> e <em>Mensagem</em> podem conter palavras que serão substiuídas 
                pelas informações da nota informada no ato da ação de enviar e-mail, estas palavras são:
                <dl class="email-reservadas">
                    <dt>__NF__</dt>
                    <dd>Número da Nota Fiscal</dd>
                    
                    <dt>__EXTRATO__</dt>
                    <dd>Número do extrato</dd>
                    
                    <dt>__DATA_EMISSAO__<dt>
                    <dd>Data da Emissão da Nota Fiscal</dd>
                    
                    <dt>__POSTO__</dt>
                    <dd>Código e nome do posto<dd>
            </p>
        </div>
        
        <dl class="email-form">
            <dt> E-mail de: </dt>
            <dd> <input type="text" name="email_de" id="email_de" size="32" value="<?php echo $email_de; ?>" /> </dd>
            
            <dt> Assunto: </dt>
            <dd> <input type="text" name="assunto" id="assunto" size="32" value="<?php echo $assunto; ?>" /> </dd>
            
            <dt> Mensagem: </dt>
            <dd>
                <textarea name="mensagem" id="mensagem" cols="32" rows="5"><?php echo $mensagem; ?></textarea>
            </dd>
        </dl>
    
        <input type="submit" value="Salvar" />
    </div>
</form>

<?php include 'rodape.php'; ?>