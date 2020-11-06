<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

$title       = traduz("POSTOS EM CREDENCIAMENTO");
$layout_menu = "gerencia";

include 'cabecalho_new.php';

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "ajaxform",
    "dataTable"
);

include("plugin_loader.php");

include 'jquery-ui.html';

?>

<TITLE> POSTOS EM CREDENCIAMENTO </TITLE>

<script type="text/javascript">
    function MostraEsconde(dados)
    {
        if (document.getElementById)
        {
            var style2 = document.getElementById(dados);
            if (style2==false) return;
            if (style2.style.display=="block"){
                style2.style.display = "none";
                }
            else{
                style2.style.display = "block";
            }
        }
    }

    function informacoes(posto) {
        var url = "";
            url = "../credenciamento/suggar/informacoes.php?posto=" + posto;
            janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=650,height=600,top=18,left=0");
            janela.focus();
    }

    function janela(a , b , c , d) {
        var arquivo = a;
        var janela = b;
        var largura = c;
        var altura = d;
        posx = (screen.width/2)-(largura/2);
        posy = (screen.height/2)-(altura/2);
        features="width=" + largura + " height=" + altura + " status=yes scrollbars=yes";
        newin = window.open(arquivo,janela,features);
        newin.focus();
    }
    $(function(){
        $.datepickerLoad(Array("data_final", "data_inicial"));
    });

    function gravar_obs(posto){
        var data ={
            obs: document.getElementById("observacao-"+posto+"").value,
            posto: posto
        } 
        
        var estado  = document.getElementById("estado_obs").value;
        var cnpj    = document.getElementById("cnpj_obs").value;
        var razao   = document.getElementById("razao_obs").value;
        var dataobs = document.getElementById("data_obs").value;

        $.ajax({
            type: "POST",
            url: "credenciamento_suggar.php?gravar",
            data: data,
            success: function(data) {
                $("#mensagem-"+posto+"").show();
                $("#mensagem-"+posto+"").css('display','block');
                $("#mensagem-"+posto+"").html('Observação gravada com sucesso');
                if(estado != '' || cnpj != '' || razao != '' || dataobs != ''){
                    document.getElementById("btn_pesquisa_consulta").click()
                }else{
                    document.getElementById("btn_pesquisa_todos").click()
                }
            }
        });
    }
</script>


<!-- <TABLE align='center' width='500' style='fon-size: 14px' border='0' class='tabela2' bgcolor='' cellspacing='0' cellpadding='0'>
<TR>
	<TD style='font-size: 25px; font-family: verdana' align='center'>POSTOS EM CREDENCIAMENTO</TD>
</TR>
<TR>
	<TD align='center'>Telecontrol Networking</TD>
</TR>
</TABLE>

<br><br> -->

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name="frm_credenciado" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela '>Consulta de Postos em Credenciamento</div>
    <br/>
    <div class="row-fluid">
        <div class='span2'></div>
        <div class="span4">
            <div class="control-group" >
                <label class="control-label" for="cnpj">CNPJ</label>
                <div class="controls controls-row">
                    <div class="span12">
                    <input type="text" name="cnpj" id="cnpj" size="13" maxlength="20" value="<? echo (($_POST['cnpj'] != '') and ($_POST['btn_pesquisa'] == 'Consultar')) ? $_POST['cnpj']: ''?>" class="frm span12">
                    </div>
                </div>
            </div>
             
        </div>

         <div class="span4">
            <div class="control-group" >
                <label class="control-label" for="cnpj">Razão Social</label>
                <div class="controls controls-row">
                    <div class="span12">
                    <input type="text" name="razao" id="razao" size="13" value="<? echo (($_POST['razao'] != '') and ($_POST['btn_pesquisa'] == 'Consultar')) ? $_POST['razao']: ''?>" class="frm span12">
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <br>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span2">
            <div class="control-group" >
                <label class="control-label" for="data_credenciamento">Data inicial</label>
                <div class="controls controls-row">
                    <div class="span12" valign='top'>
                        <input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<? echo (($_POST['data_inicial'] != '') and ($_POST['btn_pesquisa'] == 'Consultar')) ? $_POST['data_inicial'] : ""; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm span12">
                    </div>
                </div>
            </div>
        </div>
        <div class="span2">
            <div class="control-group" >
                <label class="control-label" for="data_credenciamento">Data final</label>
                <div class="controls controls-row">
                    <div class="span12" valign='top'>
                        <input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<? echo (($_POST['data_final'] != '') and ($_POST['btn_pesquisa'] == 'Consultar')) ? $_POST['data_final'] : ""; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm span12">
                    </div>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for="tabela">Estado</label>
                <div class="controls controls-row">
                    <div class='span12'>
                        <select id="estado" name="estado" class="span12">
                        <option></option>
                        <option <?= (($_POST['estado'] == 'AC') and ($_POST['btn_pesquisa'] == 'Consultar')) ? 'selected' : '' ?> value="AC">AC</option>
                        <option <?= (($_POST['estado'] == 'AL') and ($_POST['btn_pesquisa'] == 'Consultar')) ? 'selected' : '' ?>  value="AL">AL</option>
                        <option <?= (($_POST['estado'] == 'AP') and ($_POST['btn_pesquisa'] == 'Consultar')) ? 'selected' : '' ?>  value="AP">AP</option>
                        <option <?= (($_POST['estado'] == 'AM') and ($_POST['btn_pesquisa'] == 'Consultar')) ? 'selected' : '' ?>  value="AM">AM</option>
                        <option <?= (($_POST['estado'] == 'BA') and ($_POST['btn_pesquisa'] == 'Consultar')) ? 'selected' : '' ?>  value="BA">BA</option>
                        <option <?= (($_POST['estado'] == 'CE') and ($_POST['btn_pesquisa'] == 'Consultar')) ? 'selected' : '' ?>  value="CE">CE</option>
                        <option <?= (($_POST['estado'] == 'DF') and ($_POST['btn_pesquisa'] == 'Consultar')) ? 'selected' : '' ?>  value="DF">DF</option>
                        <option <?= (($_POST['estado'] == 'ES') and ($_POST['btn_pesquisa'] == 'Consultar')) ? 'selected' : '' ?>  value="ES">ES</option>
                        <option <?= (($_POST['estado'] == 'GO') and ($_POST['btn_pesquisa'] == 'Consultar')) ? 'selected' : '' ?>  value="GO">GO</option>
                        <option <?= (($_POST['estado'] == 'MA') and ($_POST['btn_pesquisa'] == 'Consultar')) ? 'selected' : '' ?>  value="MA">MA</option>
                        <option <?= (($_POST['estado'] == 'MG') and ($_POST['btn_pesquisa'] == 'Consultar')) ? 'selected' : '' ?>  value="MG">MG</option>
                        <option <?= (($_POST['estado'] == 'MT') and ($_POST['btn_pesquisa'] == 'Consultar')) ? 'selected' : '' ?>  value="MT">MT</option>
                        <option <?= (($_POST['estado'] == 'MS') and ($_POST['btn_pesquisa'] == 'Consultar')) ? 'selected' : '' ?>  value="MS">MS</option>
                        <option <?= (($_POST['estado'] == 'PA') and ($_POST['btn_pesquisa'] == 'Consultar')) ? 'selected' : '' ?>  value="PA">PA</option>
                        <option <?= (($_POST['estado'] == 'PB') and ($_POST['btn_pesquisa'] == 'Consultar')) ? 'selected' : '' ?>  value="PB">PB</option>
                        <option <?= (($_POST['estado'] == 'PR') and ($_POST['btn_pesquisa'] == 'Consultar')) ? 'selected' : '' ?>  value="PR">PR</option>
                        <option <?= (($_POST['estado'] == 'PE') and ($_POST['btn_pesquisa'] == 'Consultar')) ? 'selected' : '' ?>  value="PE">PE</option>
                        <option <?= (($_POST['estado'] == 'PI') and ($_POST['btn_pesquisa'] == 'Consultar')) ? 'selected' : '' ?>  value="PI">PI</option>
                        <option <?= (($_POST['estado'] == 'RJ') and ($_POST['btn_pesquisa'] == 'Consultar')) ? 'selected' : '' ?>  value="RJ">RJ</option>
                        <option <?= (($_POST['estado'] == 'RN') and ($_POST['btn_pesquisa'] == 'Consultar')) ? 'selected' : '' ?>  value="RN">RN</option>
                        <option <?= (($_POST['estado'] == 'RS') and ($_POST['btn_pesquisa'] == 'Consultar')) ? 'selected' : '' ?>  value="RS">RS</option>
                        <option <?= (($_POST['estado'] == 'RO') and ($_POST['btn_pesquisa'] == 'Consultar')) ? 'selected' : '' ?>  value="RO">RO</option>
                        <option <?= (($_POST['estado'] == 'RR') and ($_POST['btn_pesquisa'] == 'Consultar')) ? 'selected' : '' ?>  value="RR">RR</option>
                        <option <?= (($_POST['estado'] == 'SC') and ($_POST['btn_pesquisa'] == 'Consultar')) ? 'selected' : '' ?>  value="SC">SC</option>
                        <option <?= (($_POST['estado'] == 'SE') and ($_POST['btn_pesquisa'] == 'Consultar')) ? 'selected' : '' ?>  value="SE">SE</option>
                        <option <?= (($_POST['estado'] == 'SP') and ($_POST['btn_pesquisa'] == 'Consultar')) ? 'selected' : '' ?>  value="SP">SP</option>
                        <option <?= (($_POST['estado'] == 'TO') and ($_POST['btn_pesquisa'] == 'Consultar')) ? 'selected' : '' ?>  value="TO">TO</option>
                        </select>
                    </div>
                </div>
            </div> 
          
            </div>
        <div class="span2"></div>
    </div>   
    <br />
    <p><br/>
        <input type="submit" id="btn_pesquisa_consulta" class="btn" name="btn_pesquisa" value="Consultar" />
        <input type="submit" id="btn_pesquisa_todos" class="btn" name="btn_pesquisa" value="Listar Todos" />
    </p><br/>
</FORM>

<?


$diretorio = '../credenciamento/fotos';
$ponteiro  = opendir($diretorio);

while ($nome_itens = readdir($ponteiro)) {
	if($nome_itens <> "index.php" AND $nome_itens <> "posto_cadastro_hbtec.php" AND $nome_itens <> "index2.php")
		$itens[] = $nome_itens;
}

sort($itens);

foreach ($itens as $listar) {
	if ($listar!="." && $listar!=".."){
		if (is_dir($listar)) {
			$pastas[]=$listar;
		} else{
			$arquivos[]=$listar;
		}
	}
}
if(isset($_GET['gravar'])){
    $posto = $_POST['posto'];
    $observacao = $_POST['obs'];

    $sql = "INSERT INTO tbl_credenciamento (
            posto,
            fabrica,
            data,
            texto,
            status,
            confirmacao_admin
        ) VALUES (
            $posto,
            24,
            current_timestamp,
            '$observacao',
            'DESCREDENCIADO',
            $login_admin
        )";
        
        $res = pg_query($con, $sql);
}

if($_POST['btn_credenciar'] == 'credenciar'){
    $posto = $_POST['posto_credencia'];
    $observacao = $_POST['observacao'];
    $cnpj = $_POST['cnpj'];

    $sql_credencia = "INSERT INTO tbl_credenciamento (
        posto,
        fabrica,
        data,
        texto,
        status,
        confirmacao_admin
    ) VALUES (
        $posto,
        24,
        current_timestamp,
        '$observacao',
        'CREDENCIADO',
        $login_admin
    )";
    
    $res_credencia = pg_query($con, $sql_credencia);

    $sql = "INSERT INTO tbl_posto_fabrica ( posto ,
        fabrica ,
        codigo_posto ,
        senha ,
        tipo_posto ,
        login_provisorio,
        data_alteracao,
        observacao_credenciamento
        ) VALUES (
        $posto ,
        24 ,
        '$cnpj' ,
        '*' ,
        '119' ,
        't' ,
        current_timestamp,
        '$observacao'
        ); ";
        $res = pg_query($con, $sql);

        $sql_update = "UPDATE tbl_posto_fabrica_autocredenciamento SET
                credenciado = 't'
                WHERE posto = $posto";

        $res_update = pg_query($con, $sql_update);

        ?>
        <script>
            document.getElementById("btn_pesquisa_consulta").click();
        </script>  
    <?
}

if($_GET['excluir'] == true){
    $psoto = $_GET['posto'];
    $sql_delete = "DELETE FROM tbl_posto_fabrica_autocredenciamento WHERE fabrica = 24 AND posto = $posto";
    // echo $sql_delete;
    $res_delete = pg_query($con, $sql_delete);    

    ?>
        <script>
            document.getElementById("btn_pesquisa_consulta").click();
        </script>  
    <?
}
if($_POST['btn_pesquisa'] == 'Consultar' || $_POST['btn_pesquisa'] == 'Listar Todos'){
    if($_POST['btn_pesquisa'] == 'Listar Todos'){
        $estado = null;
        $data_inicial = null;
        $data_final = null;
        $cnpj = null;
        $razao = null;
        $_POST['estado'] = '';
        $_POST['data_inicial'] = '';
        $_POST['data_final'] = '';
        $_POST['cnpj'] = '';
        $_POST['razao'] = '';
    }
    if($_POST['btn_pesquisa'] == 'Consultar'){
        if(strlen($_POST['estado']) > 0)  $estado = strtoupper(trim($_POST['estado']));
        if(strlen($_POST['data_inicial']) > 0)  $data_inicial = ($_POST['data_inicial']);
        if(strlen($_POST['data_final']) > 0)  $data_final = ($_POST['data_final']);
        if(strlen($_POST['cnpj']) > 0)  $cnpj = ($_POST['cnpj']);
        if(strlen($_POST['razao']) > 0)  $razao = ($_POST['razao']);
    }

	$xarquivos = $arquivos;

	$sql = "SELECT distinct tbl_posto.posto ,
					tbl_posto.nome          ,
					tbl_posto.cnpj          ,
                    tbl_posto.fone          ,
					cidade                  ,
					estado                  ,
					fabricantes             ,
					descricao               ,
					email                   ,
					linhas                  ,
					funcionario_qtde        ,
					os_qtde                 ,
					atende_cidade_proxima   ,
					marca_nao_autorizada    ,
					marca_ser_autorizada    ,
					melhor_sistema          ,
					to_char(tbl_posto_fabrica_autocredenciamento.data_autocredenciamento,'dd/mm/yyyy') as data_credenciamento,
					to_char(data_modificado,'dd/mm/yyyy') as data_modificado
					FROM tbl_posto_extra
					JOIN tbl_posto using(posto)
					JOIN tbl_posto_fabrica_autocredenciamento on (tbl_posto_fabrica_autocredenciamento.posto = tbl_posto_extra.posto AND tbl_posto_fabrica_autocredenciamento.fabrica = 24)
				WHERE (tbl_posto_extra.fabricantes IS NOT NULL OR tbl_posto_extra.descricao IS NOT NULL)
				AND tbl_posto.posto not in (select posto from tbl_posto_fabrica where fabrica = 24)";
    
    if(strlen($razao) > 0){
        $sql .=" AND tbl_posto.nome = '$razao' ";
    }
    
	if(strlen($estado) > 0 AND $estado <> 'TODOS'){
		$sql .=" AND UPPER(tbl_posto.estado) = '$estado' ";
	}
    
	if((strlen($data_inicial) > 0) and (strlen($data_final) > 0)){
        $data_inicial = explode('/',$data_inicial);
        $data_final =  explode('/',$data_final);
        $data_final[0]= $data_final[0]+1;
        $sql .= " AND tbl_posto_fabrica_autocredenciamento.data_autocredenciamento BETWEEN '$data_inicial[2]-$data_inicial[1]-$data_inicial[0] ' and '$data_final[2]-$data_final[1]-$data_final[0]'";
    }
    
    if(strlen($cnpj) > 0){
		$sql .= " AND tbl_posto.cnpj = '$cnpj'";
	}
	

	$sql .=" AND tbl_posto.posto in (SELECT posto from tbl_posto_fabrica_autocredenciamento where credenciado is false)";

    $sql .= " ORDER BY tbl_posto.nome; ";

    $res = pg_exec($con, $sql);
	if(pg_numrows($res) > 0){

    /* cabeçalho csv*/
    $arquivo_nome     = "posto-credenciamento.csv";
    $path             = "../xls/";
    $path_tmp         = "/tmp/";

    $arquivo_completo     = $path.$arquivo_nome;
    $arquivo_completo_tmp = $path_tmp.$arquivo_nome;

    $fp = fopen ($arquivo_completo_tmp,"w");


    $conteudo .= "Razão Social;Estado;Cidade;CNPJ;Telefone;Email;Fabricantes;Descrição;Linhas que atende;Qtde de funcionários;Qtde de OS;As cidades que atende;Marcas que não quer trabalhar;Marcas que quer trabalhar;Melhor sistema que o posto acha;Última data que foi alterada\n";

    /* fim do cabeçalho csv */
	//	echo 123;
		for($i = 0; $i < pg_numrows($res); $i++){
			$nome                   = pg_result($res,$i,nome);
			$cidade                 = pg_result($res,$i,cidade);
			$estado                 = pg_result($res,$i,estado);
			$posto                  = pg_result($res,$i,posto);
			$fabricantes            = pg_result($res,$i,fabricantes);
			$descricao              = pg_result($res,$i,descricao);
			$codigo_posto           = @pg_result($res,$i,codigo_posto);
			$credenciamento         = @pg_result($res,$i,credenciamento);
			$linhas                 = strtoupper(pg_result($res,$i,linhas));
			$funcionario_qtde       = pg_result($res,$i,funcionario_qtde);
			$os_qtde                = pg_result($res,$i,os_qtde);
			$atende_cidade_proxima  = pg_result($res,$i,atende_cidade_proxima);
			$marca_nao_autorizada   = pg_result($res,$i,marca_nao_autorizada);
			$marca_ser_autorizada   = pg_result($res,$i,marca_ser_autorizada);
			$melhor_sistema         = pg_result($res,$i,melhor_sistema);
            $data_modificado		= pg_result($res,$i,data_modificado);
            $cnpj           		= pg_result($res,$i,cnpj);
            $email                  = pg_result($res,$i,email);
            $fone                   = pg_result($res,$i,fone);

            $cnpj = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4)."-".substr($cnpj,12,2);
            /*Busca observações gravadas*/
            $sql_obs = "SELECT to_char(tbl_credenciamento.data,'dd/mm/yyyy') as data, tbl_credenciamento.texto, tbl_credenciamento.confirmacao_admin, tbl_admin.nome_completo 
                        FROM tbl_credenciamento 
                        JOIN tbl_admin on tbl_admin.admin = tbl_credenciamento.confirmacao_admin 
                        WHERE posto = $posto
                        AND tbl_credenciamento.fabrica = 24";
                        
                        $res_obs = pg_query($con, $sql_obs); 
            
            $observacoes = null;

            if(pg_num_rows($res_obs) > 0){
                $observacoes .= '<table class="table table-striped table-bordered table-hover table-fixed">
                <thead> <tr><td colspan="3" style="text-align: center;"><b>Observação</b></td></tr>';
                $observacoes .= "
                   
                   <tr>
                    <td><b>Nome</b></td>
                    <td><b>Observação</b></td>
                    <td style='width: 125px'><b>Data da Observação</b></td></tr>
                    </thead><tbody>";
                for($j = 0; $j < pg_num_rows($res_obs); $j++){
                    $data_observacao = pg_result($res_obs, $j, 'data');
                    $obs             = pg_result($res_obs, $j, 'texto');
                    $nome_admin      = pg_result($res_obs, $j, 'nome_completo');
                    
                    $observacoes .= "<tr>
                            <td>".$nome_admin."</td>
                            <td>".$obs."</td>
                            <td>".$data_observacao."</td>
                          </tr>";
                }
                $observacoes .= "</tbody></table>";
            }
            /*  fim da busca da observações */
            
            /* Corpo CSV*/
            $conteudo .= "".str_replace(',',' ',$nome).";";
            $conteudo .= str_replace(',','',$cidade).";";
            $conteudo .= str_replace(',','',$estado).";";
            $conteudo .= str_replace(',',' ',$cnpj).";";
            $conteudo .= str_replace(',',' ',$fone).";";
            $conteudo .= str_replace(',',' ',$email).";";
            $conteudo .= str_replace(',',' ',$fabricantes).";";
            $conteudo .= str_replace(',',' ',$descricao).";";
            $conteudo .= str_replace(',',' ',$linhas).";";
            $conteudo .= str_replace(',',' ',$funcionario_qtde).";";
            $conteudo .= str_replace(',',' ',$os_qtde).";";
            $conteudo .= str_replace(',',' ',$atende_cidade_proxima).";";
            $conteudo .= str_replace(',',' ',$marca_nao_autorizada).";";
            $conteudo .= str_replace(',',' ',$marca_ser_autorizada).";";
            $conteudo .= str_replace(',',' ',$melhor_sistema).";";
            $conteudo .= str_replace(',',' ',$data_modificado).";\n";
            /*fim do csv*/

            echo '<br><br>
                <table class="table table-striped table-bordered table-hover table-fixed">
                <thead>';
                    echo "<tr class='titulo_coluna'>
                            <td>Razão Social</td>
                            <td align='right' colspan='2'>Cidade</td>
                            <td>Estado</td>
                            <td>Informações</td>
                        </tr>
                        <tr border='0'>
                        <td style='font-size: 12px; width:500px' nowrap><b onClick=\"MostraEsconde('conteudo$i')\" style='cursor:pointer; cursor:hand;'>". ($nome) ."</b></td>
                        <td align='right' colspan='2'>". ($cidade) . "</td><td> " . ($estado) ."</td>
                        <td align='left'>";
                        $foto = '0';
                        foreach($xarquivos as $xlistar){
                                //validações para não imprimir fotos de outros postos com nome final =
                                $testa_1 = "$posto" . "_1.jpg";
                                $testa_2 = "$posto" . "_2.jpg";
                                $testa_3 = "$posto" . "_3.jpg";

                                if(substr_count($xlistar, $posto) > 0 AND $foto == '0' AND ($testa_1 == $xlistar OR $testa_2 == $xlistar OR $testa_3 == $xlistar)) {
                                    echo "<a href= \"javascript: informacoes($posto)\"><img border='0' src='../credenciamento/suggar/camera_foto.gif' ALT='Tem fotos'></a>&nbsp;&nbsp;";
                                    $z++;
                                    $foto = '1';
                                }
                        }
                    echo "<a href= \"javascript: informacoes($posto)\"><img border='0' src='../credenciamento/suggar/papel.jpg' ALT='Mais informações' width='16' height='16'></a></td>";
                   
                    echo "</tr>
                </thead>
                </table>
                <div id='conteudo$i' style='display: none;'>
                    <table class='table table-striped table-bordered table-hover table-fixed'>
                    <tbody>";
                    echo '
                    <tr><td align="left" style="width: 515px"><b>CNPJ</b></td><td colspan="2">'. $cnpj.'</td></tr>
                    <tr><td align="left" style="width: 515px"><b>Fabricantes</b></td><td colspan="2">'. $fabricantes.'</td></tr>
                    <tr><td align="left" style="width: 515px"><b>Telefone</b></td><td colspan="2">'. $fone.'</td></tr>
                    <tr><td align="left" style="width: 515px"><b>Email</b></td><td colspan="2">'. $email.'</td></tr>
                    <tr><td style="width: 515px"><b>Descrição:</b></td><td  colspan="2">'. $descricao.'</td></tr>
                    <tr><td style="width: 515px"><b>Linhas que atende:</b></td><td colspan="2">'. $linhas.'</td></tr>
                    <tr><td style="width: 515px"><b>Qtde de funcionários:</b></td><td colspan="2">'. $funcionario_qtde.'</td></tr>
                    <tr><td style="width: 515px"><b>Qtde de OS:</b></td><td colspan="2">'. $os_qtde.'</td></tr>
                    <tr><td style="width: 515px"><b>As cidades que atende:</b></td><td colspan="2">'. $atende_cidade_proxima.'</td></tr>
                    <tr><td style="width: 515px"><b>Marcas que não quer trabalhar:</b></td><td colspan="2">'. $marca_nao_autorizada.'</td></tr>
                    <tr><td style="width: 515px"><b>Marcas que quer trabalhar:</b></td><td colspan="2">'. $marca_ser_autorizada.'</td></tr>
                    <tr><td style="width: 515px"><b>Melhor sistema que o posto acha:</b></td><td colspan="2">'. $melhor_sistema.'</td></tr>
                    <tr><td style="width: 515px"><b>Última data que foi alterada:</b></td><td colspan="2">'. $data_modificado.'</td></tr></tbody></table>';
                    if($observacoes != null){
                        echo $observacoes;
                    }
                    echo '<div class="alerts">
                        <div class="alert success" style="display: none" id="mensagem-'.$posto.'"><i class="fa fa-check-circle"></i></div>
                    </div>';
                    echo '<table class="table table-striped table-bordered table-hover table-fixed">
                    <tbody><tr><form method="post" action="'.$PHP_SELF.'"><td><label for="exampleFormControlTextarea1"><b>Observação</b></label>
                    <textarea id="observacao-'.$posto.'" class="form-control" name="observacao" rows="3" style="width: 97%;"></textarea>
                    <INPUT TYPE="hidden" id="cnpj_obs"  NAME="cnpj" value="'.$_POST['cnpj'].'">
                    <INPUT TYPE="hidden" id="razao_obs" NAME="razao" value="'.$_POST['razao'].'">
                    <INPUT TYPE="hidden" id="estado_obs" NAME="estado" value="'.$_POST['estado'].'">
                    <INPUT TYPE="hidden" id="data_obs" NAME="data_credenciamento" value="'.$_POST['data_credenciamento'].'">
                    <INPUT id="posto" TYPE="hidden" NAME="posto_credencia" value="'.$posto.'">
                    </td><td colspan="2">';
                    echo "<a href='javascript: gravar_obs(".$posto.")' value='gravar' name='btn_gravar' class='btn btn-primary' style='margin-top: 25px;'>Gravar</a>
                    <a href= 'http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]?excluir=true&posto=".$posto."' name='btn_excluir' class='btn btn-danger' style='margin-top: 25px;'>Excluir</a>
                    <button type='submit' value='credenciar' name='btn_credenciar' class='btn btn-success' style='margin-top: 25px;'>Credenciar</button>
                    </td></form></tr>";
                    echo '</tbody></table>
                </div>
            </table>';
		}
	}else{
		echo "<br><br>";
		echo "<p style='font-size: 12px; font-family: verdana;' align='center'>Nenhum resultado encontrado!</p>";
    }
    fputs ($fp,utf8_encode($conteudo));

    fclose($fp);

    echo ` cp $arquivo_completo_tmp $path `;
  
	echo "<p style='font-size: 12px; font-family: verdana;' align='center'>Total: " . pg_numrows($res) . "</p>";
	$z = 1;
    $xposto = $posto;
    if(pg_num_rows($res) > 0){
        echo "<a href='../xls/$arquivo_nome' target='_blank' align='center'>
            <div id='gerar_excel' class='btn btn-success' text-align: center;' style='margin-top: 25px;margin-bottom: 20px;margin-left: 45%;'>
                <!-- <input type='hidden' id='jsonPOST' value='<?=$jsonPOST?>' /> -->
                <span class='txt' style='width: 180px; text-align: center;'>Gerar CSV</span>
            </div>
        </a>";
    }

}
?>
