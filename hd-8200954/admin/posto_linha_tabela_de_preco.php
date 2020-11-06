<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios='cadastros';
require_once 'autentica_admin.php';
include_once 'helper.php';

include_once 'funcoes.php';

$title = traduz("Posto X linha X Tabela de preço");
$layout_menu = "cadastro";
include 'cabecalho_new.php';
?>
<script>
    function fnc_pesquisa_posto (campo, campo2, tipo) {
        if (tipo == "nome" ) {
            var xcampo = campo;
        }

        if (tipo == "codigo" ) {
            var xcampo = campo2;
        }

        if (xcampo.value != "") {
            var url = "";
            url = "posto_pesquisa.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo ;
            janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=650, height=400, top=0, left=0");
            janela.retorno = "<? echo $_SERVER['PHP_SELF']; ?>";
            janela.nome = campo;
            janela.cnpj = campo2;
            janela.focus();
        }
        else{
            alert('<?=traduz("Informar toda ou parte da informação para realizar a pesquisa")?>');
        }
    }
    function upload(){
        $('#upload').css('display', 'block')
        $('#principal').css('display', 'none')
        $('.titulo_tabela').html('Upload de Tabela de Preço')
    }
    function cancelar_upload(){
        $('#upload').css('display', 'none')
        $('#principal').css('display', 'block')
        $('.titulo_tabela').html('Consulta Tabela de Preço')
    }

    function alterar(posto, descricao, linha, tabela){
        console.log('chego aqui');
        document.getElementById('codigo_posto').value = posto;
        document.getElementById('nome').value = descricao;
        document.getElementById('linha').value = linha;
        document.getElementById('tabela').value = tabela;
        document.getElementById('btn_submit').value = 'Atualizar'; 
        $('.titulo_tabela').html('Atualizar Tabela de Preço')
    }
</script>


<?php
    if ($_GET['posto']) {
        $posto = $_GET['posto'];
        $sql_posto = "SELECT codigo_posto, nome
                        FROM tbl_posto
                        JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                        where tbl_posto.posto = $posto and tbl_posto_fabrica.fabrica = $login_fabrica";
    
        $res_posto = pg_query($con, $sql_posto);
        
        if(pg_num_rows($res_posto) > 0){
            $codigo_posto = pg_fetch_result($res_posto, 'codigo_posto');
            $nome = pg_fetch_result($res_posto, 'nome');
        }
    }

if($_POST['upload'] == 'Upload'){

    $registros   = array();
    $extensao    = strtolower(preg_replace("/.+\./", "", $_FILES["tabela_preco"]["name"]));
    $msg_erro    = array();

    if (!in_array($extensao, array("csv"))) {
        $msg_erro[] = "Formado de arquivo inválido.";
    }

    $arquivo = fopen($_FILES['tabela_preco']['tmp_name'], 'r+');

    if ($arquivo && (count($msg_erro) == 0)) {

        while(!feof($arquivo)){

            $linha = fgets($arquivo,4096);

            if (strlen(trim($linha)) > 0) {
                $registros[] = explode(";", $linha);
            }

        }

        fclose($f);
    }

    if ((count($registros) > 0) && (count($msg_erro) == 0)) {
        foreach ($registros as $key => $registro) {

            $xcodigo_posto             = trim($registro[0]);
            $xcodigo_linha             = trim($registro[1]);
            $xsigla_tabela             = trim($registro[2]);

	    if (strtoupper($xcodigo_posto) == "POSTO") {
		continue;
	    }
            
	    if (empty($xcodigo_posto) || empty($xcodigo_linha) || empty($xsigla_tabela)) {
		$msg_erro[] = "O arquivo enviado não segue o layout estabelecido, algumas informações não estão presentes no arquivo. <br> Favor verificar se os dados apresentados estão corretos.";
	    }
			$posto = null;
            $sql_posto = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND codigo_posto = '{$xcodigo_posto}';";
            $res_posto = pg_query($con, $sql_posto);
            if(pg_num_rows($res_posto) > 0){
                $posto = pg_fetch_result($res_posto, 'posto');
            }

            $sql_linha = "SELECT linha FROM tbl_linha WHERE codigo_linha = '{$xcodigo_linha}' AND fabrica = {$login_fabrica};";
            $res_linha = pg_query($con, $sql_linha);
            if(pg_num_rows($res_linha) > 0){
                $linha = pg_fetch_result($res_linha, 'linha');
            }

            $sql_tabela = "SELECT tabela FROM tbl_tabela WHERE sigla_tabela = '{$xsigla_tabela}' AND fabrica = {$login_fabrica};";
            $res_tabela = pg_query($con, $sql_tabela);
            if(pg_num_rows($res_tabela) > 0){
                $tabela = pg_fetch_result($res_tabela, 'tabela');
            }

	    if (!empty($posto) && !empty($linha) && !empty($tabela)) {
		$verifica = "SELECT * FROM tbl_posto_linha WHERE posto = {$posto} AND linha = {$linha};";
                $resX = pg_query ($con, $verifica);

		if (pg_num_rows($resX) == 0) {
	    	    $sql = "INSERT INTO tbl_posto_linha(posto, linha, tabela_posto) VALUES ($posto, $linha, $tabela);";
		} else {
		    $sql = "UPDATE tbl_posto_linha SET tabela_posto = {$tabela} WHERE posto = {$posto} AND linha = {$linha};";
		}

		pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
		    $msg_erro[] = "Ocorreu um erro gravando -> Posto: {$xcodigo_posto} x Linha: {$xcodigo_linha} x Tabela: {$xsigla_tabela}";
		}
	    } else {
		$msg_erro[] = "Dados não encontrados -> Posto: {$xcodigo_posto} x Linha: {$xcodigo_linha} x Tabela: {$xsigla_tabela}";
	    }
        }
    }
    if (count($msg_erro) == 0) {
	$msg_sucesso = "Registros inseridos/atualizados com sucesso";
    }
}

if($_POST['gravar'] == 'Atualizar'){
    $codigo_posto = $_POST['codigo_posto'];
    $linha = $_POST['linha'];
    $tabela = $_POST['tabela'];

    $sql_posto = "SELECT posto FROM tbl_posto_linha WHERE codigo_posto = '{$codigo_posto}' AND linha = {$linha} AND tabela = {$tabela};";
    $res_posto = pg_query($con, $sql_posto);
    if(pg_num_rows($res_posto) > 0){
        $posto = pg_fetch_result($res_posto, 'posto');
        $sql_update = "UPDATE tbl_posto_linha set tabela_posto = $tabela WHERE posto = $posto";
        $res_update = pg_query($con, $sql_update);

        if($res_update == true){?>
            <div class="alert alert-sucess">
                <h4>Alterado com sucesso</h4>
            </div>
        <?php $codigo_posto = '';
            $nome = '';
            $_POST['linha'] = '';
            $_POST['tabela'] ='';
        }else{ ?>
             <div class="alert alert-error">
                <h4>Registro já cadastrado</h4>
                <br>
                <?php busca($_POST['codigo_posto'], $_POST['linha'], $_POST['tabela'], $con);?>
            </div>
        <?php }
    }
}

if($_POST['gravar'] == 'Gravar'){
    $posto = $_POST['posto'];
    $linha = $_POST['linha'];
    $tabela = $_POST['tabela'];
    
    $insert = "INSERT INTO tbl_posto_linha(posto,linha,tabela_posto) VALUES($posto,$linha,$tabela)";
    $res_insert = pg_query($con, $insert);

    if($res_insert == true){?>
        <div class="alert alert-sucess">
            <h4>Gravado com sucesso</h4>
        </div>
    <?php $codigo_posto = '';
        $nome = '';
        $_POST['linha'] = '';
        $_POST['tabela'] ='';
    }else{ ?>
         <div class="alert alert-error">
            <h4>Registro já cadastrado</h4>
            <br>
            <?php busca($_POST['codigo_posto'], $_POST['linha'], $_POST['tabela'], $con);?>
        </div>
    <?php }
}

if (count($msg_erro) > 0) { ?>
        <div class="alert alert-error">
                <h4><?= implode("<br />", $msg_erro);?></h4>
        </div>
<?php }
if (!empty($msg_sucesso)) { ?>
        <div class="alert alert-success">
                <h4><?= $msg_sucesso;?></h4>
        </div>
<?php } ?>

<div id="principal">
    <form name="frm_posto" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
        <div class='titulo_tabela '>Consulta Tabela de Preço</div>
        <br/>
        <div class="row-fluid">
            <div class='span2'></div>
            <div class="span4">
                <div class='control-group'>
                    <label class='control-label' for='codigo_posto'>Código Posto</label>
                    <div class='controls controls-row'>
                        <div class='span10 input-append'>
                            <input type="text" id="codigo_posto" name="codigo_posto" class='span12' maxlength="20" value="<? echo $codigo_posto ?>" style="width: 245px;">
                            <a href="#"><span class='add-on' rel="lupa" >
                                <i class='icon-search' onclick="javascript: fnc_pesquisa_posto (document.frm_posto.nome,document.frm_posto.codigo_posto,'codigo')">
                            </i></span></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class='control-group'>
                    <label class='control-label' for='nome'>Descricao Posto</label>
                    <div class='controls controls-row'>
                        <div class='span10 input-append'>
                            <input type="text" id="nome" name="nome" class='span12' maxlength="20" value="<?php echo $nome ?>" style="width: 245px;">
                            <<a href="#"><span class='add-on' rel="lupa" >
                                <i class='icon-search' onclick="javascript: fnc_pesquisa_posto (document.frm_posto.nome,document.frm_posto.codigo_posto,'nome')">
                            </i></span></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class='span2'></div>
                <div class="span4">
                <div class="control-group">
                    <label class="control-label" for="linha">Linha</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <select id="linha" name="linha" class="span12">
                            <option value=''></option>
                            <?php
                                $sql_linha = "SELECT linha, nome
                                                FROM tbl_linha
                                                WHERE fabrica = {$login_fabrica}
                                                AND ativo = 't'; ";
                                $res_linha = pg_query($con,$sql_linha);

                                if (pg_num_rows($res_linha) > 0) {
                                    $linhas = pg_fetch_all($res_linha);
                                    foreach ($linhas as $resultado) {
                                        if ($_POST['linha'] == $resultado['linha']) {
                                            $selected = "SELECTED";
                                        }else{
                                            $selected = "";
                                        }
                                        
                                        echo "<option value='".$resultado['linha']."'".$selected.">".$resultado['nome']."</option>";
                                        
                                    }
                                }
                            ?>                       
                            
                            </select>
                        </div>
                    </div>
                </div>  
            </div>
            <div class="span4">
                <div class="control-group">
                    <label class="control-label" for="linha">Tabela de Preço</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <select id="tabela" name="tabela" class="span12">
                            <option value=''></option>
                            <?php
                                $sql_tabela = "SELECT tabela, sigla_tabela, descricao
                                                FROM tbl_tabela
                                                WHERE fabrica = {$login_fabrica}
                                                AND ativa = 't'; ";
                                $res_tabela = pg_query($con,$sql_tabela );

                                if (pg_num_rows($res_tabela) > 0) {
                                    $linhas = pg_fetch_all($res_tabela);
                                    foreach ($linhas as $resultado) {
                                        if ($_POST['tabela'] == $resultado['tabela']) {
                                            $selected = "SELECTED";
                                        }else{
                                            $selected = "";
                                        }
                                        
                                        echo "<option value='".$resultado['tabela']."'".$selected.">".$resultado['sigla_tabela'] .' - '. $resultado['descricao']."</option>";
                                        
                                    }
                                }
                            ?>                       
                            
                            </select>
                        </div>
                    </div>
                </div>  
            </div>
            <div class='span2'></div>
        </div>
        <br>
        <br />
        <p><br/>
            <input type="hidden" name="posto" value="<?echo $posto ?>">
            <input type="submit" class="btn" name="consultar" value="Consultar" />
            <input type="submit" class="btn" id="btn_submit" name="gravar" value="Gravar" />
            <a href="javascript: upload();" class="btn" name="btn_upload" id="btn_upload">Upload</a>
        </p><br/>
    </form>
</div>


<div id="upload" style="display: none">
    <form name="frm_posto_upload" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario' enctype='multipart/form-data'>
        <div class='titulo_tabela '>Upload de Tabela de Preço</div>
        <br/>
        <br>
        <br />
        <div class="alert alert-sucess">
            <h5>Faça o UPLOAD de um arquivo no formado CSV separado por Ponto e Vírgula (;) seguindo o layout abaixo.</h5>
            <h5><b>Código Posto;Código da Linha;Sigla da Tabela</b></h5>
        </div>
        <input type="file" name="tabela_preco" id="tabela_preco" />
        <p><br/>
            <input type="submit" class="btn" name="upload" value="Upload" />
            <a href="javascript: cancelar_upload();" class="btn" name="cancela" id="btn_cancela">Cancelar</a>
        </p><br/>
    </form>
</div>

<?php if($_POST['consultar'] == 'Consultar'){
    busca($_POST['codigo_posto'], $_POST['linha'], $_POST['tabela'], $con);
}

function busca($codigo, $linha, $tabela, $con){
    $sql_consulta .= "SELECT 
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome,
                    tbl_tabela.tabela,
                    tbl_tabela.sigla_tabela,
                    tbl_tabela.descricao,
                    tbl_linha.linha AS idlinha,
                    tbl_linha.nome AS linha
                    FROM tbl_posto_fabrica
                    JOIN tbl_posto USING(posto)
                    JOIN tbl_posto_linha ON tbl_posto.posto = tbl_posto_linha.posto
                    JOIN tbl_linha ON tbl_posto_linha.linha = tbl_linha.linha AND tbl_linha.fabrica = 143
                    JOIN tbl_tabela ON tbl_tabela.tabela = tbl_posto_linha.tabela_posto AND tbl_tabela.fabrica = 143
                    WHERE tbl_posto_fabrica.fabrica = 143";
                            
        if(strlen($codigo) > 0 ){
            $sql_consulta .= " AND tbl_posto_fabrica.codigo_posto = '$codigo'";
        }

        if(strlen($linha) > 0 ){
            $sql_consulta .= " AND tbl_posto_linha.linha = $linha";
        }

        if(strlen($tabela) > 0 ){
            $sql_consulta .= " AND tbl_tabela.tabela = $tabela";
        }

        $res_consulta = pg_query($con, $sql_consulta);
?>
    <table class='table table-striped table-bordered table-hover table-fixed'>
        <thead>
            <tr class='titulo_coluna'>
                <td>Código</td>
                <td>Nome</td>
                <td>Linha</td>
                <td>Tabela</td>
                <td>Ações</td>
            </tr>
        </thead>
        <tbody>
        <?php
            if(pg_num_rows($res_consulta) > 0){
                for($c = 0; $c < pg_num_rows($res_consulta); $c++){
                    $codigo_posto = pg_fetch_result($res_consulta, $c, 'codigo_posto'); 
                    $nome_posto   = pg_fetch_result($res_consulta, $c, 'nome');
                    $sigla_tabela = pg_fetch_result($res_consulta, $c, 'sigla_tabela');
                    $tabela       = pg_fetch_result($res_consulta, $c, 'descricao');
                    $linha        = pg_fetch_result($res_consulta, $c, 'linha');
                    $tabelaid     = pg_fetch_result($res_consulta, $c, 'tabela');
                    $linhaid     = pg_fetch_result($res_consulta, $c, 'idlinha');
                    ?>
                <tr>
                    <td><?= $codigo_posto ?></td>
                    <td><?= $nome_posto ?></td>
                    <td><?= $linha ?></td>
                    <td><?= $sigla_tabela .' - '. $tabela ?></td>
                    <td>
                        <a href="javascript: alterar('<?=$codigo_posto?>','<?=$nome_posto?>','<?=$linhaid?>','<?=$tabelaid?>')">Alterar</a>
                    </td>
                </tr>
                <?php }?>
            <?php } ?>
        </tbody>
    </table>
	<?php }

include_once "rodape.php"; ?>
