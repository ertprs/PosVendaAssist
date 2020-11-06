<?php
    header('Content-Type: text/html; charset=ISO-8859-1');
    include '../../dbconfig.php';
    include '../../includes/dbconnect-inc.php';
    include '../../funcoes.php';
    include '../../helpdesk/mlg_funciones.php';

    if($_POST["buscaCidade"] == true){   
        $estado = strtoupper($_POST["estado"]);

        if(strlen($estado) > 0){
            $sql = "SELECT DISTINCT * FROM (
                        SELECT UPPER(TO_ASCII(nome, 'LATIN9')) AS cidade FROM tbl_cidade WHERE UPPER(TO_ASCII(nome, 'LATIN9')) ~ UPPER(TO_ASCII('{$cidade}', 'LATIN9')) AND UPPER(estado) = UPPER('{$estado}')
                        UNION (
                            SELECT UPPER(TO_ASCII(cidade, 'LATIN9')) AS cidade FROM tbl_ibge WHERE UPPER(TO_ASCII(cidade, 'LATIN9')) ~ UPPER(TO_ASCII('{$cidade}', 'LATIN9')) AND UPPER(estado) = UPPER('{$estado}')
                        )
                    ) AS cidade ORDER BY cidade ASC";
            $res  = pg_query($con, $sql);
            $rows = pg_num_rows($res);

            if ($rows > 0) {
                $cidades = array();
                for ($i = 0; $i < $rows; $i++) {
                    $cidades[$i] = array(
                        "cidade"          => utf8_encode(pg_fetch_result($res, $i, "cidade")),
                        "cidade_pesquisa" => utf8_encode(strtoupper(pg_fetch_result($res, $i, "cidade"))),
                    );
                }
                $retorno = array("cidades" => $cidades);
            } else {
                $retorno = array("erro" => "Nenhuma cidade encontrada para o estado {$estado}");
            }
        } else {
            $retorno = array("erro" => "Nenhum estado selecionado");
        }
        exit(json_encode($retorno));
    }

    $array_estado = array(
        'AC'=>'AC - Acre',          'AL'=>'AL - Alagoas',   'AM'=>'AM - Amazonas',          'AP'=>'AP - Amapá',
        'BA'=>'BA - Bahia',         'CE'=>'CE - Ceará',     'DF'=>'DF - Distrito Federal',  'ES'=>'ES - Espírito Santo',
        'GO'=>'GO - Goiás',         'MA'=>'MA - Maranhão',  'MG'=>'MG - Minas Gerais',      'MS'=>'MS - Mato Grosso do Sul',
        'MT'=>'MT - Mato Grosso',   'PA'=>'PA - Pará',      'PB'=>'PB - Paraíba',           'PE'=>'PE - Pernambuco',
        'PI'=>'PI - Piauí',         'PR'=>'PR - Paraná',    'RJ'=>'RJ - Rio de Janeiro',    'RN'=>'RN - Rio Grande do Norte',
        'RO'=>'RO - Rondônia',      'RR'=>'RR - Roraima',   'RS'=>'RS - Rio Grande do Sul', 'SC'=>'SC - Santa Catarina',
        'SE'=>'SE - Sergipe',       'SP'=>'SP - São Paulo', 'TO'=>'TO - Tocantins'
    );

    $login_fabrica = 147;

    if (isset($_GET["q"])){
        $busca      = getPost('busca');
        $tipo_busca = getPost('tipo_busca');
        $q          = preg_replace("/\W/", ".?", getPost('q'));

        if (strlen($q)>2){
            if ($tipo_busca=="produto"){
                $sql = "SELECT
                            tbl_produto.produto, tbl_produto.descricao
                        FROM tbl_produto
                        JOIN tbl_linha USING (linha)
                        WHERE tbl_linha.fabrica = $login_fabrica
                            AND ( tbl_produto.descricao  ~* '$q' OR tbl_produto.referencia ~* '$q' )";
                            
                $res = pg_query($con,$sql);

                if ((pg_num_rows ($res)) > 0) {
                    for ($i = 0; $i < pg_num_rows ($res); $i++) {
                        $produto    = trim(pg_fetch_result($res,$i,'produto'));
                        $descricao  = trim(pg_fetch_result($res,$i,'descricao'));
                        echo "$produto|$descricao\n";
                    }
                }
            }
        }
        die;
    }

    if( isset($_GET["reclamado"]) ){
        $tipo_busca = $_GET['tipo_busca'];
        $produto    = preg_replace("/\W/", ".?", $_GET['produto']);

        if( strlen($produto)>2 ){
            if( $tipo_busca=="defeito_reclamado" ){
                $sql = "SELECT DISTINCT tbl_defeito_reclamado.descricao,
                            tbl_defeito_reclamado.defeito_reclamado
                        FROM tbl_diagnostico
                            JOIN tbl_defeito_reclamado
                                ON tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
                            JOIN tbl_produto
                                ON tbl_diagnostico.familia = tbl_produto.familia
                        WHERE tbl_diagnostico.fabrica = $login_fabrica
                            AND tbl_diagnostico.ativo IS TRUE
                            AND tbl_produto.produto = $produto
                        UNION
                        SELECT DISTINCT tbl_defeito_reclamado.descricao,
                            tbl_familia_defeito_reclamado.defeito_reclamado
                        FROM tbl_familia_defeito_reclamado
                            JOIN tbl_defeito_reclamado
                                ON tbl_defeito_reclamado.defeito_reclamado = tbl_familia_defeito_reclamado.defeito_reclamado
                            AND tbl_defeito_reclamado.fabrica = $login_fabrica
                        ORDER BY 1";

                $res = pg_query($con, $sql);

                echo "<select id='defeito_reclamado' name='defeito_reclamado' title='Defeito Reclamado' style='color: #333; width:280px; height:28px'>
                          <option value=''> - Selecione o Defeito Reclamado</option>";
                if( pg_num_rows($res) > 0 ){
                    for( $i=0; $i<pg_num_rows($res); $i++ ){
                        $defeito_reclamado = pg_result($res, $i, defeito_reclamado);
                        $descricao         = pg_result($res, $i, descricao);
                        echo "<option value='$defeito_reclamado'>$descricao</option>";
                    }
                }
                echo "</select>";
            }
        }
        die;
    }

    if( $_POST['nome_completo'] ){
        //var_export($_POST); echo getPost('endereco');
        $nome             = getPost('nome_completo');
        $email            = getPost('email');
        $telefone         = getPost('telefone');
        $celular_sp       = getPost('celular_sp');
        $cep              = pg_quote(preg_replace("/\D/","",getPost('cep')));
        $endereco         = pg_quote(change_case(getPost('endereco'), 'u'));
        $numero           = getPost('numero');
        $complemento      = pg_quote(change_case(getPost('complemento'), 'u'));
        $bairro           = pg_quote(change_case(getPost('bairro'), 'u'));
        $estado           = pg_quote(change_case(getPost('estado'), 'u'));
        $cidade           = getPost('cidade');
        $produto          = pg_quote(getPost('produto'), true);
        if ($produto == "''") $produto = 'NULL';
        $defeito_reclamado = getPost('defeito_reclamado');
        $mensagem          = getPost('mensagem');

        if (empty($nome)) {
            $msg_erro[] = 'Preencha o campo Nome.';
        } else if (empty($email)) {
            $msg_erro[] = 'Preencha o campo E-mail.';
        } else if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $msg_erro[] = 'Preencha um e-mail válido.';
        } else if (empty($telefone) && empty($celular_sp)) {
            $msg_erro[] = 'Preencha o campo Tel Fixo ou Celular.';
        } else if(empty($cep)) {
            $msg_erro[] = 'Preencha o campo CEP.';
        } else if (empty($endereco)) {
            $msg_erro[] = 'Preencha o campo Endereço.';
        } else if (empty($numero)) {
            $msg_erro[] = 'Preencha o campo Número.';
        } else if (empty($bairro)) {
            $msg_erro[] = 'Preencha o campo Bairro.';
        } else if (empty($cidade)) {
            $msg_erro[] = 'Preencha o campo Cidade.';
        } else if (empty($produto)) {
            $msg_erro[] = 'Preencha o campo Produto.';
        } else if (empty($defeito_reclamado)) {
            $msg_erro[] = 'Preencha o campo Defeito Reclamado.';
        } else if (strlen($mensagem)==0) {
            $msg_erro[] = 'Preencha o campo Mensagem.';
        } else if( !is_null($estado) and !is_null($cidade) and count($msg_erro)==0 ){

            $sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER({$estado})";
            $res = pg_query($con, $sql);

            if(pg_num_rows($res) == 0){
                
                $sql = "SELECT cidade, estado FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER({$estado})";
                $res = pg_query($con, $sql);

                if(pg_num_rows($res) > 0){
                    $cidade = pg_fetch_result($res, 0, 'cidade');
                    $estado = pg_fetch_result($res, 0, 'estado');
                    $sql = "INSERT INTO tbl_cidade (nome, estado) VALUES ('$cidade', '$estado')";
                    $res = pg_query($con, $sql);
                }else{
                    $cidade = 'null';
                }
            }else{
                $cidade = pg_fetch_result($res, 0, 'cidade');
            }
        }

        if(count($msg_erro) == 0){
            $hd_chamado_origem = 175; //valor de producao

            $sqlAdmin = "SELECT tbl_hd_origem_admin.admin,
                        tbl_admin.email, 
                        (select count(1) from tbl_hd_chamado where tbl_hd_chamado.admin = tbl_hd_origem_admin.admin and tbl_hd_chamado.status = 'Aberto') as qtde_hd_chamado 
                        FROM tbl_hd_origem_admin 
                        JOIN tbl_admin USING(admin)
                        WHERE hd_chamado_origem = $hd_chamado_origem 
                        ORDER BY qtde_hd_chamado asc "; 
            $resAdmin = pg_query($con, $sqlAdmin);

            if(pg_num_rows($resAdmin)>0){
                $admin          = pg_fetch_result($resAdmin, 0, 'admin');
                $email_admin    = pg_fetch_result($resAdmin, 0, 'email');
            }


            $titulo         = 'Atendimento Site';
            //$admin          = 9155; // Atendimento Site.
            //$email_admin    = 'claudio.silva@telecontrol.com.br';
        
            $sql = "INSERT INTO tbl_hd_chamado
                    (
                        admin              ,
                        data               ,
                        status             ,
                        atendente          ,
                        fabrica_responsavel,
                        titulo             ,
                        categoria          ,
                        fabrica
                    )
                    VALUES
                    (
                        $admin                       ,
                        CURRENT_TIMESTAMP            ,
                        'Aberto'                     ,
                        $admin                       ,
                        $login_fabrica               ,
                        '$titulo'                    ,
                        'reclamacao_produto'         ,
                        $login_fabrica
                    )";
            
            $res = pg_query($con,$sql);

            if( is_resource($res) ){
                
                $res        = pg_query($con,"SELECT CURRVAL ('seq_hd_chamado')");
                $hd_chamado = pg_fetch_result($res,0,0);

                $i_nome       = pg_quote($nome);
                $i_email      = pg_quote($email);
                $i_telefone   = pg_quote($telefone);
                $i_celular_sp = pg_quote($celular_sp);

                /* HD 941072 - Gravando o Celular opcionalmente */
                if( empty($i_celular_sp) ){
                    $campo_celular_sp = ' ';
                    $valor_celular_sp = ' ' ;
                } else{
                    $campo_celular_sp = ' celular, ';
                    $valor_celular_sp = $i_celular_sp.' ,';
                }

                $sql = "INSERT INTO tbl_hd_chamado_extra
                        (
                            hd_chamado        ,
                            produto           ,
                            reclamado         ,
                            defeito_reclamado ,
                            nome              ,
                            endereco          ,
                            numero            ,
                            complemento       ,
                            bairro            ,
                            cep               ,
                            fone              ,
                            $campo_celular_sp
                            email             ,
                            cidade
                        )
                        VALUES
                        (
                            $hd_chamado       ,
                            $produto          ,
                            '$mensagem'       ,
                            $defeito_reclamado,
                            $i_nome           ,
                            $endereco         ,
                            $numero           ,
                            $complemento      ,
                            $bairro           ,
                            $cep              ,
                            $i_telefone       ,
                            $valor_celular_sp
                            $i_email          ,
                            $cidade
                        )";

                $res = pg_query($con, $sql);
                //echo nl2br($sql); exit;
                if( $dbErro = pg_last_error($con) ){
                    $msg_erro[] = $dbErro;
                }

                if( strlen($dbErro) == 0 ){

                    $marca_nome = 'HITACHI';
                    
                    $res = pg_query($con, "COMMIT TRANSACTION");
                    $subject  = "Contato via site " . $marca_nome . " - Protocolo de Atendimento Nº ". $hd_chamado;
                    $message  = "<b>Foi aberto um novo Help Desk</b> <br /><br />";
                    $message .= "<b>Nome </b>: $nome <br />";
                    $message .= "<b>E-mail </b>: $email <br />";
                    $message .= "<b>Telefone </b>: $telefone <br />";
                    $message .= "<b>Help Desk </b>: $hd_chamado <br />";
                    $message .= "<b>Mensagem </b>: $mensagem <br />";
                    $message .= "<p>Segue abaixo o link para acesso ao chamado:</p>
                                 <p>
                                     <a href='http://posvenda.telecontrol.com.br/assist/admin/callcenter_interativo_new.php?callcenter=$hd_chamado'>
                                        http://posvenda.telecontrol.com.br/assist/admin/callcenter_interativo_new.php?callcenter=$hd_chamado
                                     </a>
                                 </p>";
                    $para = $email_admin;
                    $headers  = "MIME-Version: 1.0 \r\n";
                    $headers .= "Content-type: text/html \r\n";
                    $headers .= "From: Telecontrol Networking <helpdesk@telecontrol.com.br> \r\n";
                    $headers .= "Reply-to: $email \r\n";

                    if(mail($para, utf8_encode($subject), utf8_decode($message), $headers)){    
                        $msg['sucess'] = true;
                        $msg['msg'] = "Mensagem enviada com Sucesso! <br> <b>Nº Protocolo <b> - $hd_chamado";
                    }else{
                        $msg['error'] = true;
                        $msg['msg'] = 'Houve um erro ao enviar o E-mail, tente novamente!';
                    }
                } else{
                    $res = pg_query($con, "ROLLBACK TRANSACTION");
                }
            }
        }
    }
    $marcaID = 'HITACHI';
?>

<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script type='text/javascript' src='../../js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='../css/jquery.autocomplete.min.js'></script>
<script type="text/javascript" src="../../admin/js/jquery.mask.js"></script>

<style type="text/css">
    .ac_results { padding: 0px; border: 1px solid black; background-color: white; overflow: hidden; z-index: 99999; }
    .ac_results ul { width: 100%; list-style-position: outside; list-style: none; padding: 0; margin: 0; }
    .ac_results li { margin: 0px; padding: 2px 5px; cursor: default; display: block;

        font: menu; font-size: 12px;
        line-height: 16px; overflow: hidden;
    }

    .ac_loading { background: white url('../css/indicator.gif') right center no-repeat; }
    .ac_odd { background-color: #eee; }
    .ac_over { background-color: #0A246A; color: white; }

    .bgerror{
        background: #ff0000;
        color: #FFFFFF;
        padding: 20px;
        text-align: center;
    }
    .bgsucess{
        background: #008000;
        color: #FFFFFF;
        padding: 20px;
        text-align: center;

    }
       
</style>

<script type="text/javascript">
    var php_self = window.location.pathname;

    $(function(){
        var mask_field = $('#celular_sp'),
            options = { onKeyPress:function(phone){
                if( /^\([1-9][0-9]\) *[0-8]/i.test(phone) ){
                    mask_field.mask('(00) 0000-0000', options); // 9º Dígito de São Paulo com DDD 11 + 9 para celulares
                } else {
                    mask_field.mask('(00) 00000-0000', options);  // Máscara default para telefones
                }
            }};

        mask_field.mask('(00) 0000-0000', options);
        // fim - HD 896924 - Validação para o nono dígito de celulares que iniciam com 11

        $("#data_nascimento").mask('00-00-0000');
        $("#cep").mask('00000-000');
        $("#telefone").mask('(00) 0000-0000');

        $("#estado").change(function () {
            if ($(this).val().length > 0) {
                buscaCidade($(this).val());
            } else {
                $("#cidade > option[rel!=default]").remove();
            }
        });

        /* # HD 941072 - Busca produto pela descrição */
         $("#produto_descricao").autocomplete(php_self + "?tipo_busca=produto",{
             minChars      : 3,
             delay         : 150,
             width         : 350,
             matchContains : true,
             formatItem    : function(row){ return row[1]  },
             formatResult  : function(row){ return row[1]; }
         });

        $("#produto_descricao").result(function(event, data, formatted){
            $("#produto").val(data[0]);
            $("#produto_descricao").val(formatItemDescricao(data));
            defeitoReclamado(data[0]);
        });

        function defeitoReclamado(produto){
            $.get(php_self,
            { 'reclamado': '', 'tipo_busca': 'defeito_reclamado', 'produto': produto },
                function (data){
                    if( data ){
                        $("#div_defeitos").html(data);
                        return true;
                    }
                }
            );
        }

        function formatItemDescricao(row){
            return row[1];
        }

        $('#cep').change(function(){
            if( $(this).val() == '' ) return true; // Não faz nada se o usuário não teclou nada.
            
            var end      = new Object;
            end.endereco = $('#endereco');
            end.bairro   = $('#bairro');
            end.cidade   = $('#cidade');
            end.estado   = $('#estado');
            end.numero   = $('#numero');

            var cep = $(this).val().replace(/\D/, '');

            if(cep.length == 8){
                $.get('../../admin/ajax_cep.php',
                    { 'ajax': 'cep', 'cep': cep },
                    function (data){
                        if( data=='ko' ){
                            end.endereco.focus();
                            return true;
                        }
                        if( data.indexOf(';') >= 0 ){
                            r = data.split(';');
                            var text = r[3];
                            text = text.replace(new RegExp('[ÁÀÂÃ]','gi'), 'A');
                            text = text.replace(new RegExp('[ÉÈÊ]','gi'), 'E');
                            text = text.replace(new RegExp('[ÍÌÎ]','gi'), 'I');
                            text = text.replace(new RegExp('[ÓÒÔÕ]','gi'), 'O');
                            text = text.replace(new RegExp('[ÚÙÛ]','gi'), 'U');
                            text = text.replace(new RegExp('[Ç]','gi'), 'C');
                            text = text.toUpperCase();
                            r[3] = text;
                            end.endereco.val(r[1]);
                            end.bairro.val(r[2]);
                            end.estado.val(r[4]);
                            //end.numero.val(r[5]).focus();
                            buscaCidade(r[4], r[3]);
                        }
                    }
                );
            }
        });
    });

    function buscaCidade (estado, cidade){
        $.ajax({
            async: false,
            url: "callcenter_cadastra_hitachi.php",
            type: "POST",
            data: { buscaCidade: true, estado: estado },
            cache: false,
            complete: function (data) {
                data = $.parseJSON(data.responseText);

                if (data.cidades) {
                    $("#cidade > option[rel!=default]").remove();
                    var cidades = data.cidades;

                    $.each(cidades, function (key, value) {
                        var option = $("<option></option>");
                        $(option).attr({ value: value.cidade_pesquisa });
                        $(option).text(value.cidade);

                        if (cidade != undefined && value.cidade.toUpperCase() == cidade.toUpperCase()) {
                            $(option).attr({ selected: "selected" });
                        }
                        $("#cidade").append(option);
                    });
                } else {
                    $("#cidade > option[rel!=default]").remove();
                }
            }
        });
    }

    function frmValidaFormContato(){
        
        nome          = document.getElementById('nome_completo');
        email         = document.getElementById('email');
        telefone      = document.getElementById('telefone');
        celular_sp    = document.getElementById('celular_sp');
        cep           = document.getElementById('cep');
        endereco      = document.getElementById('endereco');
        numero        = document.getElementById('numero');
        complemento   = document.getElementById('complemento');
        bairro        = document.getElementById('bairro');
        estado        = document.getElementById('estado');
        cidade        = document.getElementById('cidade');
        produto       = document.getElementById('produto_descricao');
        defeito       = document.getElementById('defeito_reclamado');
        mensagem      = document.getElementById('mensagem');

        if( (nome.value == '' || nome.value == ' ')  ){
            alert("Preencha o campo: " + nome.title);
            nome.focus();
            return false;
        } else if( (email.value == '' || email.value == ' ')  ){
            alert("Preencha o campo: " + email.title);
            email.focus();
            return false;
        } else if( (telefone.value == '' || telefone.value == ' ') && (celular_sp.value == '' || celular_sp.value == ' ')   ){
            alert("Preencha o campo: " + telefone.title + " ou o campo: " + celular_sp.title);
            telefone.focus();
            return false;
        } else if( (telefone.value.length != 14) && (celular_sp.value == '' || celular_sp.value == ' ')  ){
            alert("Preencha completamente o campo: " + telefone.title);
            telefone.focus();
            return false;
        } else if( celular_sp.value.length > 0  ){
            /* Testando a digitação do 9º dígito pra São Paulo no DDD 11 */
            if( /^\(11\) 9/i.test(celular_sp.value) && celular_sp.value.length < 15 ){
                alert("Preencha o 9º dígito corretamente no campo: " + celular_sp.title);
                celular_sp.focus();
                return false;
            } else if( !/^\(11\) 9/i.test(celular_sp.value) && celular_sp.value.length < 14 ){
                alert("Preencha completamente o campo: " + celular_sp.title);
                celular_sp.focus();
                return false;
            }
        }

        if( (cep.value == '' || cep.value == ' ')  ){
            alert("Para carregar o endereço, preencha o campo: " + cep.title);
            cep.focus();
            return false;
        } else if( (endereco.value == '' || endereco.value == ' ')  ){
            alert("Preencha o campo: " + endereco.title);
            endereco.focus();
            return false;
        } else if( (numero.value == '' || numero.value == ' ')  ){
            alert("Preencha o campo: " + numero.title);
            numero.focus();
            return false;
        } else if( (complemento.value == '' || complemento.value == ' ')  ){
            alert("Preencha o campo: " + complemento.title);
            complemento.focus();
            return false;
        } else if( (bairro.value == '' || bairro.value == ' ')  ){
            alert("Preencha o campo: " + bairro.title);
            bairro.focus();
            return false;
        } else if( (cidade.value == '' || cidade.value == ' ')  ){
            alert("Preencha o campo: " + cidade.title);
            cidade.focus();
            return false;
        } else if( (estado.value == '' || estado.value == ' ')  ){
            alert("Preencha o campo: " + estado.title);
            return false;
        } else if( (produto.value == '' || produto.value == ' ')  ){
            alert("Preencha o campo: " + produto.title);
            produto.focus();            
            return false;
        } else if( defeito.value == ''){
            alert("Preencha o campo: " + defeito.title);
            defeito.focus();           
            return false;
        } else if( (mensagem.value == '' || mensagem.value == ' ')  ){
            alert("Preencha o campo: " + mensagem.title);
            mensagem.focus();
            return false;
        } else return true;
    }
</script>
<?php include $marcaID . '_form.php'; ?>
