<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'funcoes.php';
include 'ajax_cabecalho.php';


$makita = 42; // codigo fabrica makita para campos especiais.

function validatemail($email=""){
    if (preg_match("/^[a-z]+([\._\-]?[a-z0-9\._-]+)+@+[a-z0-9\._-]+\.+[a-z]{2,3}$/", $email)) {
//validacao anterior [a-z0-9\._-]
        $valida = "1";
    }
    else {
        $valida = "0";
    }
    return $valida;
}

//--==== Cadastrar um técnico no treinamento =================================
include 'autentica_usuario.php';

$termo_compromisso = traduz('este.e.um.termo.de.compromisso.no.qual.esta.sendo.agendando.para.que.o.tecnico.aqui.cadastrado.pelo.posto.autorizado.possa.participar.do.treinamento.aqui.escolhido.o.treinamento.e.permitido.somente.para.pessoas.com.idade.igual.ou.maior.que.18.anos.caso.voce.nao.tenha.certeza.fica.obrigado.a.clicar.em.nao.aceito.clicando.em.aceito.voce.declara.expressamente.que.o.tecnico.cadastrado.esta.assumindo.um.compromisso.para.representar.o.posto.autorizado.para.participar.do.treinamento.aqui.agendado.declara.por.fim.conhecer.e.aceitar.o.aviso.legal.de.uso.do.sistema.assist.telecontrol');

if (in_array($login_fabrica, [1])) {
    $termo_compromisso .= traduz("em.conformidade.com.as.nossas.normas.de.seguranca.e.proibida.a.entrada.na.empresa.trajando.camisa.regata.bermuda.bone.chinelo.sandalia.e.sapato.aberto.o.tecnico.devera.utilizar.bota.de.seguranca.vale.lembrar.que.e.proibida.a.captacao.de.imagens.nas.dependencias.da.fabrica");
}

if (in_array($login_fabrica, [148])) {
    $termo_compromisso = traduz("Este é um termo de compromisso no qual, está sendo agendando para que o técnico aqui cadastrado pelo posto autorizado possa participar do treinamento aqui escolhido. O treinamento é permitido somente para pessoas com idade igual ou maior que 18 anos. Caso você não tenha certeza fica obrigado a clicar em NÃO ACEITO. Clicando em ACEITO, você declara expressamente que o técnico cadastrado, está assumindo um compromisso para representar o posto autorizado, para participar do treinamento aqui agendado. Declara, por fim, conhecer e aceitar o Aviso Legal de Uso do sistema Assist Telecontrol.

Clicando em ACEITO, você autoriza (de acordo com o Art. 20 do Código Civil) o uso de sua imagem em todo e qualquer material entre fotos e vídeo, para serem utilizadas em campanhas promocionais e institucionais da empresa YANMAR SOUTH AMERICA INDÚSTRIA DE MÁQUINAS LTDA., representada pelo nome fantasia YANMAR, inscrita no CNPJ 08.263.434/0001-96, assim como por todo o Grupo YANMAR, sejam estas destinadas à divulgação ao público em geral e/ou apenas para clientes da empresa, sem que nada haja a ser reclamado a título de direitos conexos a sua imagem ou a qualquer outro.");
}

if($_GET['ajax']=='sim' AND $_GET['acao']=='cadastrar') {
    $enviar_confirmacao = "true";

    if($login_fabrica != $makita){

        if ($login_fabrica != 20 && !in_array($login_fabrica, array(169,170,193))) {
            $tecnico_nome = trim($_GET['tecnico_nome']);
            $tecnico_cpf  = str_replace(array('-','.'), array('',''), trim($_GET['tecnico_cpf']));
            $tecnico_rg   = str_replace(array('-','.'), array('',''), trim($_GET['tecnico_rg']));
            $tecnico_fone = trim($_GET['tecnico_fone']);
            $posto_email  = trim($_GET['posto_email']);
            $treinamento  = trim($_GET['treinamento']);
            $promotor     = trim($_GET['promotor']);
            $hotel        = trim($_GET['hotel']);
            $observacao   = trim($_GET['observacao']);
            $promotor_treinamento = trim($_GET['promotor_treinamento']);

        }else{
            $tecnico_nome = trim($_GET['tecnico_nome']);
            $tecnico      = trim($_GET['tecnico']);
        }

        if (in_array($login_fabrica, [1])) {
            $tecnico_celular        = trim($_GET['tecnico_celular']);
        }

    } else {
        $tecnico_nome           = trim($_GET['tecnico_nome']);
        $tecnico_cpf            = trim($_GET['tecnico_cpf']);
        $tecnico_rg             = trim($_GET['tecnico_rg']);
        $tecnico_fone           = trim($_GET['tecnico_fone']);
        $tecnico_celular        = trim($_GET['tecnico_celular']);
        $tecnico_email          = trim($_GET['tecnico_email']);
        $tecnico_calcado        = trim($_GET['tecnico_calcado']);
        $tecnico_passaporte     = trim($_GET['tecnico_passaporte']);
        $tecnico_doenca         = trim($_GET['tecnico_doenca']);
        $tecnico_medicamento    = trim($_GET['tecnico_medicamento']);
        $tecnico_necessidade    = trim($_GET['tecnico_necessidade']);
        $tecnico_tipo_sanguineo = trim($_GET['tecnico_tipo_sanguineo']);
        $posto_email            = trim($_GET['posto_email']);
        $treinamento            = trim($_GET['treinamento']);
        $promotor               = trim($_GET['promotor']);
        $hotel                  = trim($_GET['hotel']);
        $promotor_treinamento   = trim($_GET['promotor_treinamento']);
        $observacao             = trim($_GET['observacao']);
    }

    $letra = $tecnico_tipo_sanguineo[0];
    $sinal = $tecnico_tipo_sanguineo[1];
    $sinal = ($sinal == 1) ? "+" : "-";
    $tecnico_tipo_sanguineo = $letra.$sinal;

    if(in_array($login_fabrica, array(1,20))){
        if (in_array($login_fabrica, [1])) {
            $not_posto = " AND tbl_treinamento_posto.tecnico is not null ";
        }

        $sql2="SELECT COUNT(treinamento_posto) AS qtd_incritos_posto
                        FROM tbl_treinamento_posto
                        WHERE posto=$login_posto
                        AND treinamento=$treinamento
                        $not_posto
                        AND ativo IS TRUE";
        $res2 = pg_exec ($con,$sql2);
        $qtd_incritos_posto = trim(pg_result($res2,0,qtd_incritos_posto));
        $qtd_disponivel_vagas=5-$qtd_incritos_posto;

        if($qtd_disponivel_vagas<1){
            $msg_erro .='<center>'.traduz("nao.existe.vaga.disponivel").'</center>';
        }
    }

    if (in_array($login_fabrica, array(169,170,193))){

        $cadastra_tecnico_admin = trim($_GET["cadastra_tecnico_admin"]);

        if ($cadastra_tecnico_admin == "sim"){
            $login_posto = trim($_GET["posto"]);
            $enviar_confirmacao = "false";
        }

        $sql2="SELECT COUNT(tbl_treinamento_posto.treinamento_posto) AS qtd_incritos_posto
                FROM tbl_treinamento_posto
                WHERE tbl_treinamento_posto.posto=$login_posto
                AND tbl_treinamento_posto.treinamento=$treinamento
                AND tbl_treinamento_posto.tecnico IS NOT NULL
                AND tbl_treinamento_posto.ativo IS TRUE ";
        $res2 = pg_exec ($con,$sql2);

        $sql3 = "SELECT tbl_treinamento.qtde_participante
                FROM tbl_treinamento
                WHERE tbl_treinamento.fabrica = {$login_fabrica}
                AND tbl_treinamento.treinamento=$treinamento ";
        $res3 = pg_query($con, $sql3);

        $qtd_incritos_posto = trim(pg_result($res2,0,qtd_incritos_posto));
        $qtde_participante = pg_fetch_result($res3, 0, qtde_participante);

        $qtd_disponivel_vagas= $qtde_participante-$qtd_incritos_posto;

        if($qtd_disponivel_vagas<1){
            $msg_erro .= '<center>'.traduz("nao.existe.vaga.disponivel").'</center>';
        }
    }

    if (!in_array($login_fabrica, array(20,169,170,193))) {
        if (strlen($tecnico_nome) == 0)             $msg_erro .= traduz("favor.informar.o.nome.do.tecnico")."<br>";

        if ($login_fabrica != 117) {
            if (strlen($tecnico_rg) == 0)               $msg_erro .= traduz("favor.informar.o.rg.do.tecnico")."<br>";
        }else{
            if (strlen($tecnico_rg) == 0) $tecnico_rg = 'null';
        }

        if (in_array($login_fabrica, [1])) {
            if (strlen($tecnico_celular)     == 0) $msg_erro .= traduz("favor.informar.o.celular.do.tecnico")."<br>";
            $validando_celular = valida_celular($tecnico_celular);
            if ( strlen($validando_celular) > 0 ) $msg_erro .= "$validando_celular<br>";
        } else {
            if (strlen($tecnico_fone) == 0)             $msg_erro .= traduz("favor.informar.o.telefone.de.contato")."<br>";
        }
        if (strlen($posto_email) == 0)              $msg_erro .= traduz("favor.informar.o.email.do.posto")."<br>";

        if($login_fabrica <> 138){
            if (strlen($tecnico_data_nascimento) == 0)  $msg_erro .= traduz("favor.informar.a.data.de.nascimento.do.tecnico")."<br>";
        }

        if (strlen($treinamento) == 0)              $msg_erro .= traduz("favor.informar.o.treinamento.escolhido")."<br>";
        elseif (strlen($observacao) == 0)
        {
            $sql = "SELECT adicional FROM tbl_treinamento WHERE treinamento=$treinamento";
            @$res = pg_query($con, $sql);

            if($res)
            {
                $adicional = pg_result($res, 0, 0);
                if ($adicional && $login_fabrica != 148)
                {
                    $msg_erro .= traduz("favor.informar")." $adicional<br>";
                }
            }
            else
            {
                $msg_erro .= traduz("favor.informar.o.treinamento.escolhido")."<br>";
            }
        }

        if ($login_fabrica != 117) {
            if (strlen($tecnico_cpf) > 0){
                if(!validaCPF($tecnico_cpf)){
                    $msg_erro .= traduz("o.cpf.do.tecnico.nao.e.valido")."<br />";
                }
            }
        }else{
            if (strlen($tecnico_cpf) == 0){
                $tecnico_cpf = 'null';
            }
        }
    }else{
        if (strlen($treinamento) == 0){
            $msg_erro .= traduz("favor.informar.o.treinamento.escolhido")."<br>";
        }else if (strlen($observacao) == 0){
                $sql = "SELECT adicional FROM tbl_treinamento WHERE treinamento=$treinamento";
                @$res = pg_query($con, $sql);

                if($res)
                {
                    $adicional = pg_result($res, 0, 0);
                    if ($adicional)
                    {
                        $msg_erro .= traduz("favor.informar")." $adicional<br>";
                    }
                }
                else
                {
                    $msg_erro .= traduz("favor.informar.o.treinamento.escolhido")."<br>";
                }
        }

        if (strlen($tecnico_nome) == 0){
            $msg_erro .= traduz("favor.informar.o.nome.do.tecnico")."<br>";
        }else{

            if(!in_array($login_fabrica, array(20,169,170,193))){
                $funcao_T = "AND funcao = 'T'";
            }

            $sql = "SELECT  tbl_tecnico.nome,
                            tbl_tecnico.tecnico,
                            tbl_tecnico.rg,
                            tbl_tecnico.cpf,
                            tbl_tecnico.telefone,
                            to_char(data_nascimento, 'DD/MM/YYYY') AS data_nascimento
                            FROM tbl_tecnico
                            WHERE posto = $login_posto
                            AND fabrica = $login_fabrica
                            $funcao_T
                            AND tecnico = $tecnico_nome";
            $res = @pg_exec ($con,$sql);

            if (@pg_numrows($res) > 0) {
                for($i=0;$i < @pg_numrows($res);$i++){
                    $tecnico_nome = trim(@pg_result($res,$i,'nome'));
                    $tecnico      = trim(@pg_result($res,$i,'tecnico'));
                    $tecnico_rg   = trim(@pg_result($res,$i,'rg'));
                    $tecnico_cpf  = trim(@pg_result($res,$i,'cpf'));
                    $tecnico_fone = trim(@pg_result($res,$i,'telefone'));
                    $tecnico_data_nascimento = trim(@pg_result($res,$i,'data_nascimento'));
                }
            }
        }
    }


    if($login_fabrica == $makita)
    {

        if (strlen($tecnico_celular)     == 0) $msg_erro .= traduz("favor.informar.o.celular.do.tecnico")." <br>";
        //if (strlen($tecnico_email)     == 0) $msg_erro .= "Favor informar o e-mail do técnico <br>";
        if (strlen($tecnico_passaporte)  == 0) $msg_erro .= traduz("atencao.e.obrigatorio.o.participante.possuir.seu.passaporte.de.treinamento.makita")." <br>";
        if (strlen($tecnico_doenca)      == 0) $msg_erro .= traduz("favor.informar.o.historico.de.doencas.do.tecnico")."<br>";
        if (strlen($tecnico_medicamento) == 0) $msg_erro .= traduz("favor.informar.os.medicamentos.que.tecnico.esta.tomando")."<br>";
        if (strlen($tecnico_necessidade) == 0) $msg_erro .= traduz("favor.informar.se.o.tecnico.possui.necessidades.especiais")."<br>";
        if ($tecnico_calcado             == 'Selecione') $msg_erro .= traduz("favor.informar.o.numero.do.calcado.do.tecnico")."<br>";
        if ($tecnico_tipo_sanguineo      == 'Selecione') $msg_erro .= traduz("favor.informar.o.tipo.sanguineo.de.contato")."<br>";
    }

    if ($enviar_confirmacao == "true"){
        if (!filter_var($posto_email,FILTER_VALIDATE_EMAIL))  $msg_erro .= traduz("email.do.posto.invalido")." $posto_email<br>";

        if($login_fabrica == $makita){
            if (!filter_var($tecnico_email,FILTER_VALIDATE_EMAIL))  $msg_erro .= traduz("e.mail.do.tecnico.invalido")." $tecnico_email<br>";
        }
    }

    $tecnico_cpf = str_replace("-","",$tecnico_cpf);
    $tecnico_cpf = str_replace(".","",$tecnico_cpf);
    $tecnico_cpf = str_replace("/","",$tecnico_cpf);
    $tecnico_cpf = str_replace(" ","",$tecnico_cpf);
    $tecnico_cpf = trim(substr($tecnico_cpf,0,14));

    $tecnico_rg = str_replace("-","",$tecnico_rg);
    $tecnico_rg = str_replace(".","",$tecnico_rg);
    $tecnico_rg = str_replace("/","",$tecnico_rg);
    $tecnico_rg = str_replace(" ","",$tecnico_rg);


    $aux_tecnico_nome = pg_escape_literal($con,$tecnico_nome);
    if ($login_fabrica == 117) {
        if ($tecnico_cpf == 'null') {
            $aux_tecnico_cpf  = "null" ;
        }else{
            $aux_tecnico_cpf  = "'".$tecnico_cpf."'" ;
        }
    }else{
        if(strlen($tecnico_cpf) > 0){
            $aux_tecnico_cpf  = "'".$tecnico_cpf."'" ;
        }
    }


	if ($tecnico_rg == 'null') {
		$aux_tecnico_rg  = "null" ;
	}else{
		$aux_tecnico_rg  = "'".$tecnico_rg."'" ;
	}

    $aux_tecnico_fone = "'".$tecnico_fone."'";

    $aux_promotor_treinamento = $promotor_treinamento;

    $aux_tecnico_celular    = "'".$tecnico_celular."'";
    $tecnico_tipo_sanguineo = "'".$tecnico_tipo_sanguineo."'";
    $tecnico_doenca         = "'".$tecnico_doenca."'";
    $tecnico_medicamento    = "'".$tecnico_medicamento."'";
    $tecnico_necessidade    = "'".$tecnico_necessidade."'";

    if(strlen($promotor)==0) $aux_promotor = "null";
    else                     $aux_promotor = "'".$promotor."'";

    if(strlen($hotel)==0){
        $hotel = "'f'";
    }else{
        $hotel = "'t'";
    }

    if(strlen($tecnico_data_nascimento) > 0){
        $tecnico_data_nascimento = str_replace (" " , "" , $tecnico_data_nascimento);
        $tecnico_data_nascimento = str_replace ("-" , "" , $tecnico_data_nascimento);
        $tecnico_data_nascimento = str_replace ("/" , "" , $tecnico_data_nascimento);
        $tecnico_data_nascimento = str_replace ("." , "" , $tecnico_data_nascimento);

        if (strlen ($tecnico_data_nascimento) == 6) $tecnico_data_nascimento = substr ($tecnico_data_nascimento,0,4) . "20" . substr ($tecnico_data_nascimento,4,2);
        if (strlen ($tecnico_data_nascimento)   > 0) $tecnico_data_nascimento   = substr ($tecnico_data_nascimento,0,2)   . "/" . substr ($tecnico_data_nascimento,2,2)   . "/" . substr ($tecnico_data_nascimento,4,4);
        if (strlen ($tecnico_data_nascimento) < 10) $tecnico_data_nascimento = date ("d/m/Y");

        $x_tecnico_data_nascimento = substr ($tecnico_data_nascimento,6,4) . "-" . substr ($tecnico_data_nascimento,3,2) . "-" . substr ($tecnico_data_nascimento,0,2);
    }

    if($login_fabrica <> 138){
        if(strlen($x_tecnico_data_nascimento)>0){
            $sql ="SELECT date'$x_tecnico_data_nascimento' > (current_date-interval'18 year')";
            $res = pg_exec ($con,$sql);
            if(pg_result($res,0,0)=='t'){
                $sql= "SELECT nome FROM tbl_fabrica WHERE fabrica={$login_fabrica}";
                $res = pg_query($con,$sql);
                $fabrica = pg_result($res,0,0);
                $msg_erro.= traduz("nao.e.permitido.a.participacao.de.menores.de.18.anos.no.treinamento").' '.strtoupper($fabrica);
            }
			$x_tecnico_data_nascimento = "'".$x_tecnico_data_nascimento."'";
        }
    }else{
        if(strlen($x_tecnico_data_nascimento) == 0){
            $x_tecnico_data_nascimento = 'null';
		}else{
			$x_tecnico_data_nascimento = "'".$x_tecnico_data_nascimento."'";
		}
    }

    // if(($login_fabrica == $makita OR $login_fabrica == 117) AND strlen($aux_tecnico_cpf)>0){

    if(strlen($aux_tecnico_cpf)>0 OR strlen($tecnico_nome) >0){

        if(strlen($aux_tecnico_cpf) > 0){
            $cond_tecnico = "AND tbl_tecnico.cpf = $aux_tecnico_cpf ";
        }else{
            $cond_tecnico = " AND tbl_tecnico.nome ILIKE '%$tecnico_nome%'";
        }

        if (in_array($login_fabrica, array(169,170,193))){
            $cond_tecnico_ativo = " AND tbl_treinamento_posto.ativo IS TRUE ";
        }else{
            $cond_tecnico_ativo = "";
        }
        $sql = "SELECT tbl_tecnico.nome
                    FROM tbl_treinamento
                    JOIN tbl_treinamento_posto USING(treinamento)
                    JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_treinamento_posto.tecnico
                    WHERE tbl_treinamento.treinamento = $treinamento
                    AND tbl_treinamento.fabrica = $login_fabrica
                    $cond_tecnico
                    $cond_tecnico_ativo";
        $res = pg_query($con,$sql);
        if(pg_num_rows($res) > 0){
            $msg_erro .= traduz("ja.existe.um.tecnico.cadastrado.para.este.treinamento.com.o.cpf.informado")."<br>";
        }
    }

    if (in_array($login_fabrica, [1])) {
        $sqlVP="SELECT   tbl_treinamento.treinamento,
                        tbl_treinamento.vaga_posto - (
                            SELECT COUNT(treinamento_posto) AS qtd_inscritos_posto
                                FROM tbl_treinamento_posto
                                WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                                    AND tbl_treinamento_posto.tecnico IS NOT NULL
                                    AND tbl_treinamento_posto.ativo is true
                                    AND tbl_treinamento_posto.posto = {$login_posto}
                        ) as vaga_por_posto
                    FROM tbl_treinamento
                        JOIN tbl_admin USING(admin)
                        JOIN tbl_linha USING(linha)
                        JOIN tbl_treinamento_posto USING(treinamento)
                    WHERE tbl_treinamento.fabrica = {$login_fabrica}
                        AND tbl_treinamento.data_fim >= CURRENT_DATE
                        AND tbl_treinamento.treinamento = {$treinamento}
                    GROUP BY tbl_treinamento.treinamento,tbl_treinamento.vaga_posto;";
        $resVP = pg_query($con,$sqlVP);

        if (pg_num_rows($resVP) > 0) {
            $vaga_por_postoVP = pg_fetch_result($resVP, 0, vaga_por_posto);
            if ($vaga_por_postoVP <= 0) {
                $msg_erro .= traduz("ja.foram.preenchidas.todas.as.vagas.disponiveis.para.o.posto")."<br>";
            }
        }
    }

    if (strlen($msg_erro) > 0) {
        $msg  = "<b>".traduz("foi.foram.detectado.s.o.s.seguinte.s.erro.s").": </b><br>";
        $msg .= $msg_erro;
    }else {
        $listar = "ok";
    }

    if ($listar == "ok") {

        $res = @pg_exec($con,"BEGIN TRANSACTION");

        //--==== Controle de Quantidade de vagas existentes no treinamento ======================================
        $sql = "SELECT  count(treinamento_posto) AS total_inscritos,
                tbl_treinamento.vagas
            FROM tbl_treinamento
            JOIN tbl_treinamento_posto USING(treinamento)
            WHERE tbl_treinamento.treinamento = $treinamento
            AND   tbl_treinamento_posto.ativo IS TRUE
            AND tbl_treinamento_posto.tecnico IS NOT NULL
            GROUP BY tbl_treinamento.vagas;";
        $res = pg_exec ($con,$sql);

        if (pg_numrows($res) > 0) {
            $total_inscritos = trim(pg_result($res,0,total_inscritos))   ;
            $vagas           = trim(pg_result($res,0,vagas));
            if($total_inscritos >= $vagas) $msg_erro .= traduz("todas.as.vagas.estao.preenchidas.procure.uma.nova.data");
        }

        if ($aux_tecnico_cpf == 'null' AND $login_fabrica == 117) {
            $sql = "INSERT INTO tbl_tecnico(
                                                    fabrica,
                                                    posto,
                                                    nome,
                                                    cpf,
                                                    data_nascimento,
                                                    telefone,
                                                    rg
                ";
                $sql .= "                       ) VALUES (
                                                    $login_fabrica,
                                                    $login_posto,
                                                    $aux_tecnico_nome,
                                                    $aux_tecnico_cpf,
                                                    $x_tecnico_data_nascimento,
                                                    $aux_tecnico_fone,
                                                    $aux_tecnico_rg
                ";

                $sql .= "                        ) RETURNING tecnico";

                $resTecnico = pg_exec($con,$sql);
                $tecnico = pg_result($resTecnico,0,tecnico);
        }else{

            if(strlen($aux_tecnico_cpf) > 0){
                $cond_cpf = " AND cpf = $aux_tecnico_cpf ";
            }else{
                $cond_cpf = " AND nome ILIKE '%$tecnico_nome%' ";
            }

            $sql = "select tecnico from tbl_tecnico where fabrica  = $login_fabrica and posto = $login_posto $cond_cpf;";
            $resTecnico = pg_exec($con,$sql);

            if(pg_num_rows($resTecnico) > 0){
                $tecnico = pg_result($resTecnico,0,tecnico);
            }else{

                if(strlen($aux_tecnico_cpf) == 0){
                    $aux_tecnico_cpf = 'null';
                }

                $sql = "INSERT INTO tbl_tecnico(
                            fabrica,
                            posto,
                            nome,
                            cpf,
                            data_nascimento,";
                if (in_array($login_fabrica, [1])) {
                    $sql .= "
                            celular,
                            email,
                    ";
                } else {
                    $sql .= "                       telefone, ";
                }
                    $sql .= "
                                                    rg
                    ";
                if($login_fabrica == $makita){
                    $sql .= "    ,
                            celular      ,
                            email        ,
                            calcado      ,
                            passaporte   ,
                            tipo_sanguineo,
                            doencas       ,
                            medicamento  ,
                            necessidade_especial
                    ";
                }
                $sql .= "
                        ) VALUES (
                            $login_fabrica,
                            $login_posto,
                            $aux_tecnico_nome,
                            $aux_tecnico_cpf,
                            $x_tecnico_data_nascimento,";
                if (in_array($login_fabrica, [1])) {
                    $sql .= "
                            $aux_tecnico_celular       ,
                            '$posto_email'              ,
                    ";
                } else {
                    $sql .= "
                            $aux_tecnico_fone,
                    ";
                }
                    $sql .= "
                            $aux_tecnico_rg
                    ";
                if($login_fabrica == $makita){
                    $sql .= " ,
                            $aux_tecnico_celular       ,
                            '$tecnico_email'         ,
                            $tecnico_calcado       ,
                            '$tecnico_passaporte'     ,
                            $tecnico_tipo_sanguineo,
                            $tecnico_doenca        ,
                            $tecnico_medicamento    ,
                            $tecnico_necessidade
                    ";
                }
                $sql .= " ) RETURNING tecnico";
                $resTecnico = pg_exec($con,$sql);
				$tecnico = pg_result($resTecnico,0,tecnico);
            }
        }

        if (in_array($login_fabrica, array(169,170,193))){
            $sql_inscrito = "
                SELECT posto, treinamento, treinamento_posto
                FROM tbl_treinamento_posto
                WHERE posto = {$login_posto}
                AND treinamento = {$treinamento}
                AND tecnico IS NULL ";
            $res_inscrito = pg_query($con, $sql_inscrito);

            if (pg_num_rows($res_inscrito) > 0){
                $treinamento_posto = pg_fetch_result($res_inscrito, 0, 'treinamento_posto');
                $sql_delete = "DELETE FROM tbl_treinamento_posto WHERE treinamento_posto = {$treinamento_posto} AND treinamento = {$treinamento}";
                $res_delete = pg_query($con, $sql_delete);
            }
        }

        $sql = "INSERT INTO tbl_treinamento_posto (
                tecnico ,
                promotor     ,
                posto        ,
                hotel        ,
                treinamento  ,";
                if ($aux_promotor_treinamento) $sql .= " promotor_treinamento, ";
                $sql .= "
                observacao
            )VALUES(
                $tecnico        ,
                $aux_promotor    ,
                $login_posto     ,
                $hotel         ,
                '$treinamento'     ,";
                if ($aux_promotor_treinamento) $sql .= " $aux_promotor_treinamento, ";
                $sql .= "
                ".pg_escape_literal($con, $observacao)."
            )";
        $res = @pg_exec($con, $sql);
        $msg_erro .= pg_errormessage($con);

        $sql = "SELECT CURRVAL ('seq_treinamento_posto')";
        $res = @pg_exec($con,$sql);
        $treinamento_posto =@ pg_result($res,0,0);

        $email = $posto_email;

        if($msg_erro==0){
            if ($enviar_confirmacao == "true"){
                $chave1 = md5($login_posto);
                $chave2 = md5($treinamento_posto);

                $sql=  "SELECT nome FROM tbl_posto WHERE posto = $login_posto";
                $res = pg_exec ($con,$sql);
                $nome = pg_result($res,0,nome);

                $sql=  "SELECT  titulo                            ,
                        TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY') AS data_inicio,
                        TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY')    AS data_fim
                        FROM tbl_treinamento WHERE treinamento = $treinamento";
                $res = pg_query($con,$sql);

                if (pg_num_rows($res) > 0) {
                    $titulo      = pg_fetch_result($res,0,titulo)     ;
                    if (mb_check_encoding($titulo, 'UTF-8')) {
                        $titulo = utf8_decode($titulo);
                    }
                    $data_inicio = pg_fetch_result($res,0,data_inicio);
                    $data_fim    = pg_fetch_result($res,0,data_fim)   ;
                }
                //ENVIA EMAIL PARA POSTO PRA CONFIRMAÇÃO

                $email_origem  = "verificacao@telecontrol.com.br";
                $email_destino = "$email";
                $assunto       = "Confirmação de Presença no Treinamento";

                $corpo.= "Titulo: $titulo <br>\n";
                $corpo.= "Data Inicío: $data_inicio<br> \n";
                $corpo.= "Data Termino: $data_fim <p>\n";

                $corpo.="<br>Você recebeu esse email para confirmar a inscrição do técnico.\n\n";
                $corpo.="<br>Nome: $tecnico_nome \n";
                $corpo.="<br>RG:$tecnico_rg \n";
                $corpo.="<br>CPF: $tecnico_cpf \n";
                $corpo.="<br>Telefone de Contato: ".(($login_fabrica == 1) ? "$tecnico_celular\n" : "$tecnico_fone \n");

                if($login_fabrica == $makita){
                     $corpo.="<br>Telefone celular: {$tecnico_celular} \n";
                     $corpo.="<br>E-mail do técnico: {$tecnico_email}\n";
                     $corpo.="<br>Tamanho calçado: {$tecnico_calcado}\n";
                     $corpo.="<br>Passaporte: {$tecnico_passaporte}\n";
                     $corpo.="<br>Histórico de doenças: {$tecnico_doenca}\n";
                     $corpo.="<br>Medicamento: {$tecnico_medicamento}\n";
                     $corpo.="<br>Necessidades especiais: {$tecnico_necessidade}\n";
                     $corpo.="<br>Tipo Sanguíneo: {$tecnico_tipo_sanguineo}\n\n";
                }

                if($adicional && $login_fabrica != 148) $corpo.="<br>$adicional: $observacao \n\n";
                $corpo.="<br>Email: $email\n\n";

                $host = $_SERVER['HTTP_HOST'];
                if(strstr($host, "devel.telecontrol") OR strstr($host, "homologacao.telecontrol")){
                    $corpo.="<br><br><a href='http://novodevel.telecontrol.com.br/~bicalleto/PosVenda/treinamento_confirmacao.php?key1=$chave1&key2=$login_posto&key3=$chave2&key4=$treinamento_posto'>CLIQUE AQUI PARA CONFIRMAR PRESENÇA</a>.\n\n";
                }elseif($login_fabrica <> 1){
                    $corpo.="<br><br><a href='http://posvenda.telecontrol.com.br/assist/treinamento_confirmacao.php?key1=$chave1&key2=$login_posto&key3=$chave2&key4=$treinamento_posto'>CLIQUE AQUI PARA CONFIRMAR PRESENÇA</a>.\n\n";
                }

                $corpo.="<br><br><br>Telecontrol\n";
                $corpo.="<br>www.telecontrol.com.br\n";
                $corpo.="<br>_______________________________________________\n";
                $corpo.="<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";


                $body_top = "MIME-Version: 1.0\r\n";
                $body_top .= "Content-type: text/html; charset=iso-8859-1\r\n";
                $body_top .= "From: $email_origem\r\n";

                if ( @mail($email_destino, stripslashes(($assunto)), $corpo, $body_top ) ){
                    $msg = "$email";
                }else{
                    $msg_erro = traduz("nao.foi.possivel.enviar.o.email.por.favor.entre.em.contato.com.a.telecontrol");
                }

                if ($aux_promotor_treinamento == '') $aux_promotor_treinamento = 0;
                $sql = "select nome, email
                        from tbl_promotor_treinamento
                        where promotor_treinamento = $aux_promotor_treinamento";
                $res = pg_exec($con,$sql);
                if(pg_numrows($res)>0){
                    $nome_promotor      = pg_result($res,0,nome)     ;
                    $email_promotor      = pg_result($res,0,email)     ;
                    if(strlen($email_promotor)>0){
                        $sql = "select nome, codigo_posto
                                from tbl_posto
                                join tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto
                                and tbl_posto_fabrica.fabrica = $login_fabrica
                                where tbl_posto.posto = $login_posto";
                        $res = pg_exec($con,$sql);
                        if(pg_numrows($res)>0){
                            $nome_posto      = pg_result($res,0,nome)        ;
                            $xcodigo_posto   = pg_result($res,0,codigo_posto);

                            $corpo = "";

                            $email_origem  = "verificacao@telecontrol.com.br";
                            $email_destino = "$email_promotor";
                            $assunto       = "Confirmação de Presença no Treinamento";
                            $corpo.="<br>Caro Promotor,";
                            $corpo.="<BR>Segue abaixo informações do posto e o treinamento solicitado\n<BR>";

                            $corpo.= "Titulo: $titulo <br>\n";
                            $corpo.= "Data Inicío: $data_inicio<br> \n";
                            $corpo.= "Data Termino: $data_fim <p>\n";
                            $corpo.="<BR>Posto: $xcodigo_posto - $nome_posto\n";
                            $corpo.="<br>Nome: $tecnico_nome \n";
                            $corpo.="<br>RG:$tecnico_rg \n";
                            $corpo.="<br>CPF: $tecnico_cpf \n";
                            $corpo.="<br>Telefone de Contato: $tecnico_fone \n\n";
                            if($adicional && $login_fabrica != 148) $corpo.="<br>$adicional: $observacao \n\n";
                            $corpo.="<br>Email: $email\n\n";
                            $corpo.="<br><br><br>Telecontrol\n";
                            $corpo.="<br>www.telecontrol.com.br\n";
                            $corpo.="<br>_______________________________________________\n";
                            $corpo.="<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";


                            $body_top = "MIME-Version: 1.0\r\n";
                            $body_top .= "Content-type: text/html; charset=iso-8859-1\r\n";
                            $body_top .= "From: $email_origem\r\n";

                            if ( @mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), $body_top ) ){
                                $msg = "$email";
                            }else{
                                $msg_erro = traduz("nao.foi.possivel.enviar.o.email.por.favor.entre.em.contato.com.a.telecontrol");

                            }
                        }
                    }
                }
            }else{
                if (in_array($login_fabrica, array(169,170,193)) AND $enviar_confirmacao == "false"){
                    $sql = "
                        UPDATE tbl_treinamento_posto SET confirma_inscricao = 't'
                        WHERE posto             = $login_posto
                        AND   treinamento_posto = $treinamento_posto ";
                    $res = @pg_exec ($con,$sql);
                }
            }
        }

        if (strlen($msg_erro) == 0 ) {
            $res = @pg_exec ($con,"COMMIT TRANSACTION");
            
            if ($login_fabrica == 148) {
                include_once 'class/communicator.class.php';
                $sql = "select linha from tbl_treinamento where treinamento = {$_GET['treinamento']} and fabrica = {$login_fabrica}";
                $res = @pg_exec ($con,$sql);
                $linha  = trim(pg_result($res,0,linha));
                $email = [];

                if (!empty($linha)) {
                    $sql_email = "SELECT email, parametros_adicionais FROM tbl_admin WHERE parametros_adicionais ~'email_treinamento' AND fabrica = $login_fabrica";
                    $res_email = pg_query($con, $sql_email);
                    if (pg_num_rows($res_email) > 0) {
                        for ($e = 0; $e < pg_num_rows($res_email); $e++) {
                            $parametros_adicionais = [];
                            $parametros_adicionais = json_decode(pg_fetch_result($res_email, $e, 'parametros_adicionais'), true);
                            if (in_array($linha, $parametros_adicionais['email_treinamento'])) {
                                $email[] = pg_fetch_result($res_email, $e, 'email'); 
                            }
                        }
                    }    
                    $assunto = "Confirmação de Presença do Posto em Treinamento";

                    $corpoFabrica .= "Titulo: $titulo <br>\n";
                    $corpoFabrica.= "Data Inicío: $data_inicio<br> \n";
                    $corpoFabrica.= "Data Termino: $data_fim <p>\n";

                    $corpoFabrica.="<br>Você recebeu esse email para confirmar a inscrição do técnico.\n\n";
                    $corpoFabrica.="<br>Nome: $tecnico_nome \n";
                    $corpoFabrica.="<br>RG:$tecnico_rg \n";
                    $corpoFabrica.="<br>CPF: $tecnico_cpf \n";
                    $corpoFabrica.="<br>Telefone de Contato: $tecnico_fone \n";
            		$corpoFabrica.="<br>E-mail: $posto_email \n";

                    $mailTc = new TcComm($externalId);
                    $res = $mailTc->sendMail(
                        $email,
                        $assunto,
                        $corpoFabrica,
                        $externalEmail
                    );
                }
            }

            if (in_array($login_fabrica, [1])) {

                $sql2="SELECT   tbl_treinamento.treinamento,
                                tbl_treinamento.vagas - (
                                    SELECT COUNT(treinamento_posto) AS qtd_inscritos_posto
                                        FROM tbl_treinamento_posto
                                        WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                                            AND tbl_treinamento_posto.tecnico IS NOT NULL
                                            AND tbl_treinamento_posto.ativo is true
                                ) as vagas_geral,
                                tbl_treinamento.vaga_posto,
                                tbl_treinamento.vaga_posto - (
                                    SELECT COUNT(treinamento_posto) AS qtd_inscritos_posto
                                        FROM tbl_treinamento_posto
                                        WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                                            AND tbl_treinamento_posto.tecnico IS NOT NULL
                                            AND tbl_treinamento_posto.ativo is true
                                            AND tbl_treinamento_posto.posto = {$login_posto}
                                ) as vaga_por_posto
                            FROM tbl_treinamento
                                JOIN tbl_admin USING(admin)
                                JOIN tbl_produto on (tbl_produto.linha = tbl_treinamento.linha OR tbl_produto.marca = tbl_treinamento.marca) AND tbl_produto.fabrica_i = {$login_fabrica}
                                JOIN tbl_marca ON tbl_produto.marca = tbl_marca.marca
                                JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_produto.linha
                                                  AND tbl_posto_linha.posto = $login_posto
                                JOIN tbl_treinamento_posto USING(treinamento)
                            WHERE tbl_treinamento.fabrica = {$login_fabrica}
                                AND tbl_treinamento.treinamento = {$treinamento}
                                AND tbl_treinamento.data_fim >= CURRENT_DATE
                                GROUP BY tbl_treinamento.treinamento,tbl_treinamento.vaga_posto;";
                $res2 = pg_query($con,$sql2);

                if (pg_num_rows($res2) > 0) {
                    for ($l=0; $l < pg_num_rows($res2); $l++) {
                        $vagas_geral = pg_fetch_result($res2, $l, vagas_geral);
                        $vaga_por_posto = pg_fetch_result($res2, $l, vaga_por_posto);
                        $valida_vaga_posto = pg_fetch_result($res2, $l, vaga_posto);

                        $tem_vaga = false;
                        if (empty($valida_vaga_posto)) {
                            if ($vagas_geral > 0) {
                                $tem_vaga = true;
                            }
                        } else {
                            if ($vagas_geral > 0 AND $vaga_por_posto > 0) {
                                $tem_vaga = true;
                            }
                        }
                    }
                }
                
                if ($tem_vaga) {
                    echo "ok|<center><font size='4'color='#009900'><b>".traduz("treinamento.agendado.com.sucesso")."</b></font><br><a href='javascript:mostrar_treinamento(\"dados\");'>".traduz("ver.treinamentos")."</a> <br><button class='btn btn-inscreva-tecnico' type='button' onClick ='javascript:treinamento_formulario($treinamento)'>".traduz("inscreva.outro.tecnico")."</button></center>|$treinamento_posto";
                } else {
                    echo "ok|<center><font size='4'color='#009900'><b>".traduz("treinamento.agendado.com.sucesso")."</b></font><br><a href='javascript:mostrar_treinamento(\"dados\");'>".traduz("ver.treinamentos")."</a> </center>|$treinamento_posto";
                }
            } else {
                if ($enviar_confirmacao == "true"){
                    echo "ok|<center><font size='4'color='#009900'><b>".traduz("treinamento.agendado.com.sucesso")."</b></font><br><a href='javascript:mostrar_treinamento(\"dados\");'>".traduz("ver.treinamentos")."</a></center>";
                }else{
                    exit("ok|".traduz("tecnico.cadastrado.com.sucesso").".");
                }
            }            

            exit;
        }else{
            $res = @pg_exec ($con,"ROLLBACK TRANSACTION");
            echo  "2|<b>".traduz("foi.foram.detectado.s.o.s.seguinte.s.erro.s").":</b><br> $msg_erro";
            exit;
        }

    }

    if (strlen($msg_erro) > 0) {
        echo "1|".$msg;
    }
    exit;

}

//--==== Ver treinamentos cadastrados ========================================
if($_GET['ajax']=='sim' AND $_GET['acao']=='ver') {

    if(in_array($login_fabrica, array(169,170,193))){
        $cond_tecnico = " AND tbl_treinamento_posto.tecnico IS NOT NULL ";
        $sql = "SELECT pf.cod_ibge, pf.contato_estado as estado FROM tbl_posto_fabrica pf WHERE pf.posto = $login_posto AND pf.fabrica = $login_fabrica";
        $res_posto = pg_query($con,$sql);
        $codi_ibge = (!empty(pg_fetch_result($res_posto, 0, 'cod_ibge'))) ? pg_fetch_result($res_posto, 0, 'cod_ibge') : 0;
        $post_uf   = (!empty(pg_fetch_result($res_posto, 0, 'estado'))) ? pg_fetch_result($res_posto, 0, 'estado') : null;

        $sql_posto_treinamento = "
            SELECT tbl_treinamento_posto.posto
            FROM tbl_treinamento_posto
            JOIN tbl_treinamento ON tbl_treinamento.treinamento = tbl_treinamento_posto.treinamento
                AND tbl_treinamento.fabrica = {$login_fabrica}
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_treinamento_posto.posto
                AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            WHERE tbl_treinamento_posto.posto = {$login_posto}
            AND tbl_treinamento_posto.confirma_inscricao = 'f' ";
        $res_posto_treinamento = pg_query($con, $sql_posto_treinamento);

        if (pg_num_rows($res_posto_treinamento) > 0){
            $treinamento_posto = pg_fetch_result($res_posto_treinamento, 0, 'posto');
            $cond =  " OR tbl_treinamento_posto.posto = {$treinamento_posto} ";
        }

        $col_qtd_participante  = '';

        if (in_array($login_fabrica, array(169,170,193))){
            $select_linha      = " ARRAY_TO_STRING(array_agg(DISTINCT(tbl_linha.nome)), ', ', null) AS linha_nome, ";
            $join_linha        = "  JOIN tbl_treinamento_produto ON tbl_treinamento_produto.treinamento = tbl_treinamento.treinamento
                                        AND tbl_treinamento_produto.fabrica = $login_fabrica
                                    ";
            $join_linha       .= "JOIN tbl_linha ON tbl_linha.linha = tbl_treinamento_produto.linha AND tbl_linha.fabrica = $login_fabrica";
            $join_posto_linha  = "JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_treinamento_produto.linha AND tbl_posto_linha.posto = $login_posto";

            if (in_array($login_fabrica, [193])) {
                $col_qtd_participante = 'tbl_treinamento.qtde_participante,';
            }
        }else{
            $group_linha       = " linha_nome, ";
            $select_linha      = "tbl_linha.nome                                    AS linha_nome,";
            $join_linha        = "JOIN tbl_linha USING(linha)";
            $join_posto_linha  = "JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_treinamento.linha AND tbl_posto_linha.posto = $login_posto";
        }

        $sql = "SELECT tbl_treinamento.treinamento,
                tbl_treinamento.titulo,
                tbl_treinamento.descricao,
                tbl_treinamento.ativo,
                tbl_treinamento.vagas,
                tbl_treinamento.local,
                tbl_treinamento.cidade,
                $col_qtd_participante
                TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY') AS data_inicio,
                TO_CHAR(tbl_treinamento.data_fim,   'DD/MM/YYYY') AS data_fim,
                TO_CHAR(prazo_inscricao,            'DD/MM/YYYY') AS prazo_inscricao,
                {$select_linha}
                tbl_familia.descricao                             AS familia_descricao,
                (
                    SELECT COUNT(*)
                    FROM tbl_treinamento_posto
                    WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                    AND   tbl_treinamento_posto.ativo IS TRUE
                    $cond_tecnico
                )   AS qtde_postos,
                (
                    SELECT COUNT(*)
                    FROM tbl_treinamento_posto
                    WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                    AND tbl_treinamento_posto.posto = $login_posto
                    AND tbl_treinamento_posto.ativo IS TRUE
                )   AS qtde_inscritos
                FROM tbl_treinamento
                JOIN tbl_admin USING(admin)
                {$join_linha}
                {$join_posto_linha}
                LEFT JOIN tbl_familia ON tbl_familia.familia   = tbl_treinamento.familia
                LEFT JOIN tbl_treinamento_posto ON tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                JOIN tbl_treinamento_tipo ON tbl_treinamento.treinamento_tipo = tbl_treinamento_tipo.treinamento_tipo
                WHERE tbl_treinamento.fabrica     = $login_fabrica
                AND tbl_treinamento.ativo       IS TRUE
                AND tbl_treinamento.data_inicio >= CURRENT_DATE
                AND tbl_treinamento.prazo_inscricao >= CURRENT_DATE
                AND lower(tbl_treinamento_tipo.nome) != lower('Palestra')
                AND tbl_treinamento.data_finalizado IS NULL
		AND (
                        (
                        SELECT count(tbl_cidade.cidade)
                            FROM tbl_treinamento_cidade
                            JOIN tbl_cidade ON tbl_treinamento_cidade.cidade = tbl_cidade.cidade
                            WHERE tbl_treinamento_cidade.treinamento = tbl_treinamento.treinamento
                            AND tbl_cidade.cod_ibge = {$codi_ibge}
                        ) > 0
                    OR (
                        SELECT count(tbl_treinamento_cidade.estado)
                            FROM tbl_treinamento_cidade
                            WHERE tbl_treinamento_cidade.treinamento = tbl_treinamento.treinamento
                            AND tbl_treinamento_cidade.estado = '{$post_uf}'
                        ) > 0
                        {$cond}
                    )
                GROUP BY tbl_treinamento.treinamento, tbl_treinamento.titulo, tbl_treinamento.descricao,
                tbl_treinamento.ativo, tbl_treinamento.vagas, tbl_treinamento.local, tbl_treinamento.cidade,
                data_inicio, data_fim, prazo_inscricao, {$group_linha} familia_descricao, qtde_postos, qtde_inscritos
                ORDER BY tbl_treinamento.data_inicio, tbl_treinamento.titulo";
                //echo $sql; exit;
                $res = pg_exec ($con,$sql);
        if (pg_numrows($res) > 0) {
            if ($treinamento_prazo_inscricao) {
                $prazoTD =  "<th>".traduz("prazo.inscricao")."</th>";
            }

            $labelVagas    = "<th>".traduz("total.vagas")."</th>";
            if (in_array($login_fabrica, [193])) {
                $labelVagas = "<th>".traduz("vagas.disponiveis")."</th>";
            }

            $resposta  =  "
                <table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='700'>
                  <thead>
                  <tr class='Titulo' height='25'>
                    <th></th>
                    <th>".traduz("titulo")."</th>
                    <th>".traduz("data.inicio")."</th>
                    <th>".traduz("data.fim")."</th>
                    $prazoTD
                    <th>".traduz("linha")."</th>
                    $labelVagas
                    <th>".traduz("local")."</th>
                    <th>".traduz("situacao")."</th>
                  </tr>
                  </thead>
                  <tbody>
                ";

            for ($i=0; $i<pg_numrows($res); $i++) {
                $treinamento       = trim(pg_result($res,$i,'treinamento'));
                $titulo            = trim(pg_result($res,$i,'titulo'));
                if (mb_check_encoding($titulo, 'UTF-8')) {
                    $titulo = utf8_decode($titulo);
                }
                $descricao         = trim(pg_result($res,$i,'descricao'));
                $ativo             = trim(pg_result($res,$i,'ativo'));
                $data_inicio       = trim(pg_result($res,$i,'data_inicio'));
                $data_fim          = trim(pg_result($res,$i,'data_fim'));
                $prazo_inscricao   = trim(pg_result($res,$i,'prazo_inscricao'));
                $linha_nome        = trim(pg_result($res,$i,'linha_nome'));
                $familia_descricao = trim(pg_result($res,$i,'familia_descricao'));
                $vagas             = trim(pg_result($res,$i,'vagas'));
                $vagas_postos      = trim(pg_result($res,$i,'qtde_postos'));
                $vagas_ocupadas    = trim(pg_result($res,$i,'qtde_inscritos'));
                $local             = trim(pg_result($res,$i,'local'));
                if (mb_check_encoding($local, 'UTF-8')) {
                    $local = utf8_decode($local);
                }
                $cidade            = trim(pg_result($res,$i,'cidade'));

                $localizacao = '';
                if ($cidade != '') {
                    $sql = "SELECT cidade,nome,estado FROM tbl_cidade WHERE cidade = $cidade;";
                    $resCidade = pg_exec($con,$sql);
                    if(pg_num_rows($res) > 0){
                        $cidade        = pg_result($resCidade,0,'cidade');
                        $nome_cidade   = pg_result($resCidade,0,'nome');
                        $estado_cidade = pg_result($resCidade,0,'estado');
                        $localizacao   = $nome_cidade.", ".$estado_cidade;
                    }
                }

                $cor = ($cor == '#F1F4FA') ? '#F7F5F0' : '#F1F4FA';
                
                if($vagas_postos >= $vagas) $situacao = "<img src='admin/imagens_admin/status_vermelho.gif'> ".traduz("sem.vagas");
                else                        $situacao = "<img src='admin/imagens_admin/status_verde.gif'> ".traduz("ha.vagas");

                if($vagas_ocupadas>0)$tem = "<img src='imagens/img_ok.gif'>";
                else                 $tem = "";

                $sobra = $vagas - $vagas_postos;

                if (in_array($login_fabrica, [193])) { 
                    $vagaPorPosto =  (!empty(pg_result($res,$i,'qtde_participante'))) ? trim(pg_result($res,$i,'qtde_participante')) : 0;
                    $sobra        = ($vagaPorPosto > 0) ? $vagaPorPosto - $vagas_ocupadas : $vagas - $vagas_ocupadas;
                    $situacao     = ($sobra > 0) ? "<img src='admin/imagens_admin/status_verde.gif'> ".traduz("ha.vagas") : "<img src='admin/imagens_admin/status_vermelho.gif'> ".traduz("sem.vagas");
                    $tem       = ($sobra > 0) ? "<img src='imagens/img_ok.gif'>" : "";
                }

                $resposta  .=  "<TR bgcolor='$cor'class='Conteudo'>";
                $resposta  .=  "<TD align='center'>$tem </TD>";

                $resposta  .=  "<TD align='left'nowrap><a href='javascript:treinamento_formulario($treinamento)'>$titulo</a></TD>";
                $resposta  .=  "<TD align='left'>$data_inicio</a></TD>";
                $resposta  .=  "<TD align='left'>$data_fim</TD>";
                if ($treinamento_prazo_inscricao) {
                    $resposta  .=  "<TD align='left'>$prazo_inscricao</TD>";
                }
                $resposta  .=  "<TD align='left'>$linha_nome</TD>";
                $resposta  .=  "<TD align='center'>";
                $resposta  .=  $sobra;

                $resposta  .=  "</TD>";
                if($local != ""){
                    $local .= ", ";
                }
                $resposta  .=  "<TD align='left'>$local $localizacao </TD>";
                $resposta  .=  "<TD align='left'>$situacao</TD>";

                $resposta  .=  "</TR>";

                $total = $total_os + $total;

            }
            $resposta .= " </TABLE>";
        }

        //--==== Ver técnicos cadastrados em treinamentos ============================


        if (in_array(($login_fabrica), array(169,170,193)))
        {
            $ativo_where = 'AND tbl_treinamento_posto.ativo IS TRUE';
        }

        $sql = "
	SELECT DISTINCT tbl_tecnico.nome as tecnico_nome                        ,
            tbl_tecnico.cpf as tecnico_cpf                                              ,
            tbl_treinamento.ativo                                                 ,
            tbl_treinamento.titulo                                                      ,
            tbl_treinamento.treinamento                                                      ,
            tbl_treinamento.local                                                      ,
            tbl_treinamento.cidade                                                      ,
            tbl_treinamento_posto.ativo AS treinamento_posto_ativo,
            TO_CHAR(tbl_treinamento_posto.data_inscricao,'DD/MM/YYYY') AS data_inscricao,
            TO_CHAR(tbl_treinamento_posto.data_inscricao,'HH24:MI:SS') AS hora_inscricao
        FROM tbl_treinamento_posto
        JOIN tbl_treinamento using(treinamento)
        JOIN tbl_tecnico on tbl_tecnico.tecnico = tbl_treinamento_posto.tecnico
        JOIN tbl_posto   on tbl_treinamento_posto.posto = tbl_posto.posto
        JOIN      tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
        WHERE tbl_treinamento_posto.posto = $login_posto
        AND  tbl_treinamento.fabrica = $login_fabrica
        {$ativo_where}
        ORDER BY tbl_treinamento.titulo" ;

        $res = pg_exec ($con,$sql);

        if (pg_numrows($res) > 0) {

            $resposta  .=  "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='700'>";
            $resposta  .=  "<TR class='Conteudo2' height='20' bgcolor='#ffffff'>";
            $resposta  .=  "<TD.colspan='10' align='center'><br><img src='imagens/img_ok.gif'> <b>".traduz("treinamento.s.ja.agendado.s.pelo.posto")."</b></td>";
            $resposta  .=  "</TR>";
            $resposta  .=  "</table>";
            $resposta  .=  "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='700'>";
            $resposta  .=  "<TR class='Titulo2' height='20' bgcolor='$cor'>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>".traduz("titulo")."</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>".traduz("nome.do.tecnico")."</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>".traduz("cpf.do.tecnico")."</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>".traduz("data.de.inscricao")."</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>".traduz("hora.de.inscricao")."</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>".traduz("local")."</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>".traduz("treinamento.ativo")."</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>".traduz("acao")."</b></TD>";
            $resposta  .=  "</TR>";

            for ($i=0; $i<pg_numrows($res); $i++){

                $tecnico_nome      = trim(pg_result($res,$i,tecnico_nome))  ;
                $tecnico_cpf       = trim(pg_result($res,$i,tecnico_cpf))   ;
                $data_inscricao    = trim(pg_result($res,$i,data_inscricao));
                $hora_inscricao    = trim(pg_result($res,$i,hora_inscricao));
                $titulo            = trim(pg_result($res,$i,titulo))        ;
                $ativo             = trim(pg_result($res,$i,ativo))         ;
                $local             = trim(pg_result($res,$i,local))         ;
                $cidade            = trim(pg_result($res,$i,cidade))         ;
                $treinamento_posto_ativo = trim(pg_result($res,$i,treinamento_posto_ativo))         ;

                if (in_array($login_fabrica, [169,170,193])) {
                    $x_treinamento   = trim(pg_result($res,$i,treinamento));
                }
            if(strlen(pg_result($res,$i,cidade)) > 0){

               $cidade = pg_result($res,$i,cidade);
               $sql = "SELECT cidade,nome,estado from tbl_cidade where cidade = $cidade;";
               $resCidade = pg_exec($con,$sql);
               if(pg_num_rows($res) > 0){
                    $cidade = pg_result($resCidade,0,cidade);
                        $nome_cidade = pg_result($resCidade,0,nome);
                    $estado_cidade = pg_result($resCidade,0,estado);
                    $localizacao = $nome_cidade.", ".$estado_cidade;
               }else{
                    $localizacao = "";
               }

            }else{
                   $localizacao = "";
            }

                if($ativo == 't')  $ativo = "<img src='admin/imagens_admin/status_verde.gif'>";
                else               $ativo = "<img src='admin/imagens_admin/status_vermelho.gif'>";

                if($cor=="#F6F6F6")$cor = '#FAFAFA';
                else               $cor = '#F6F6F6';

                $resposta  .=  "<TR bgcolor='$cor'class='Conteudo'>";
                $resposta  .=  "<TD align='left'>$titulo </TD>";
                $resposta  .=  "<TD align='left'nowrap>$tecnico_nome</TD>";
                $resposta  .=  "<TD align='left'>$tecnico_cpf</TD>";
                $resposta  .=  "<TD align='center'>$data_inscricao</TD>";
                $resposta  .=  "<TD align='center'>$hora_inscricao</TD>";
                $resposta  .=  "<TD align='center'>$local $localizacao</TD>";
                $resposta  .=  "<TD align='center'>$ativo</TD>";
                $resposta  .=  "<TD align='center'><a class='btn-ver-infos' style='cursor: pointer; color: #0000FF;' data-treinamento='".$x_treinamento."'>ver <br /> informações</a></TD>";
                $resposta  .=  "</TR>";
            }
            $resposta .= " </TABLE>";
        }else{
            $resposta .= "<b>".traduz("nenhum.treinamento.confirmado")."</b>";
        }


        echo "ok|".$resposta;
        exit;
        // --------------------
    }else{

        $join = (!in_array($login_fabrica, [129,148])) ? 'LEFT JOIN' : 'JOIN';
        if(in_array($login_fabrica, [1,117,138])){ //HD-3261932
            $cond_tecnico = " AND tbl_treinamento_posto.tecnico IS NOT NULL ";
        }

        $relacaoLinhaTreinamento = "    JOIN      tbl_linha   on tbl_linha.linha = tbl_treinamento.linha
                                        $join tbl_posto_linha ON tbl_posto_linha.linha = tbl_treinamento.linha
                                            AND tbl_posto_linha.posto = $login_posto
                                        LEFT JOIN tbl_familia on tbl_familia.familia = tbl_treinamento.familia ";
        $linhaMarcaSql = " tbl_linha.nome AS linha_nome, tbl_familia.descricao  AS familia_descricao, ";

        $linhaMarca ="<th>Linha</th>";
        $groupTipoPosto = "linha_nome, familia_descricao ,";

        if (in_array($login_fabrica, [1])) {
            unset($posto_atende_marca);
            unset($posto_atende_familia);
            unset($posto_atende_linha);

            $sqlInfoMarca = "   SELECT  DISTINCT tbl_marca.marca AS atende_marca
                                FROM tbl_posto_linha 
                                JOIN tbl_produto ON tbl_produto.linha = tbl_posto_linha.linha
                                JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
                                JOIN tbl_posto_fabrica ON tbl_posto_linha.posto = tbl_posto_fabrica.posto
                                WHERE tbl_posto_linha.posto = {$login_posto}
                                AND tbl_posto_linha.ativo IS TRUE
                                AND tbl_produto.fabrica_i = {$login_fabrica}";
            $res_sqlInfoMarca = pg_query($con, $sqlInfoMarca);
            if (pg_num_rows($res_sqlInfoMarca) > 0) {                
                for ($s=0; $s < pg_num_rows($res_sqlInfoMarca); $s++) { 
                    $posto_atende_marca[] = pg_fetch_result($res_sqlInfoMarca, $s, 'atende_marca');
                }
            }
            $sqlInfoLinha = "   SELECT  DISTINCT tbl_posto_linha.linha AS atende_linha
                                FROM tbl_posto_linha 
                                JOIN tbl_produto ON tbl_produto.linha = tbl_posto_linha.linha
                                JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
                                JOIN tbl_posto_fabrica ON tbl_posto_linha.posto = tbl_posto_fabrica.posto
                                WHERE tbl_posto_linha.posto = {$login_posto}
                                AND tbl_posto_linha.ativo IS TRUE
                                AND tbl_produto.fabrica_i = {$login_fabrica} "; 
            $res_sqlInfoLinha = pg_query($con, $sqlInfoLinha);
            if (pg_num_rows($res_sqlInfoLinha) > 0) {
                for ($s=0; $s < pg_num_rows($res_sqlInfoLinha); $s++) { 
                    $posto_atende_linha[] = pg_fetch_result($res_sqlInfoLinha, $s, 'atende_linha');
                }
            }

            $sqlInfoFamilia = " SELECT  DISTINCT tbl_produto.familia AS atende_familia
                                FROM tbl_posto_linha 
                                JOIN tbl_produto ON tbl_produto.linha = tbl_posto_linha.linha
                                JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
                                JOIN tbl_posto_fabrica ON tbl_posto_linha.posto = tbl_posto_fabrica.posto
                                WHERE tbl_posto_linha.posto = {$login_posto}
                                AND tbl_posto_linha.ativo IS TRUE
                                AND tbl_produto.fabrica_i = {$login_fabrica} "; 
            $res_sqlInfoFamilia = pg_query($con, $sqlInfoFamilia);
            if (pg_num_rows($res_sqlInfoFamilia) > 0) {
                for ($s=0; $s < pg_num_rows($res_sqlInfoFamilia); $s++) { 
                    $posto_atende_familia[] = pg_fetch_result($res_sqlInfoFamilia, $s, 'atende_familia');
                }
            }
            
            if (isset($posto_atende_marca)) {
                $consulta_marca_arr = [];

                foreach ($posto_atende_marca as $pam) {
                    $consulta_marca_arr[] = "(tbl_treinamento.parametros_adicionais->'marca' ? '{$pam}')";

                }

                $consulta_marca = ' AND ((tbl_treinamento.parametros_adicionais->\'marca\' IS NULL) OR (' . implode(' OR ', $consulta_marca_arr) . '))';
            } else {
                $consulta_marca = 'AND tbl_treinamento.parametros_adicionais->\'marca\' IS NULL ';
            }

            if (isset($posto_atende_linha)) {
                $consulta_linha_arr = [];

                foreach ($posto_atende_linha as $pal) {
                    $consulta_linha_arr[] = "(tbl_treinamento.parametros_adicionais->'linha' ? '{$pal}')";

                }

                $consulta_linha = ' AND ((tbl_treinamento.parametros_adicionais->\'linha\' IS NULL) OR (' . implode(' OR ', $consulta_linha_arr) . '))';
            } else {
                $consulta_linha = 'AND tbl_treinamento.parametros_adicionais->\'linha\' IS NULL ';
            }

            if (isset($posto_atende_familia)) {
                $consulta_familia_arr = [];

                foreach ($posto_atende_familia as $paf) {
                    $consulta_familia_arr[] = "(tbl_treinamento.parametros_adicionais->'familia' ? '{$paf}')";

                }

                $consulta_familia = ' AND ((tbl_treinamento.parametros_adicionais->\'familia\' IS NULL) OR  (' . implode(' OR ', $consulta_familia_arr) . '))';

            } else {
                $consulta_familia = 'AND tbl_treinamento.parametros_adicionais->\'familia\' IS NULL ';
            }
            
            $sqlTipoPostoCategoria = "SELECT tipo_posto, categoria FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
            $resTipoPostoCategoria = pg_query($con, $sqlTipoPostoCategoria);

            $posto_tipo_posto = pg_fetch_result($resTipoPostoCategoria, 0, 'tipo_posto');
            $posto_categoria_posto = strtolower(pg_fetch_result($resTipoPostoCategoria, 0, 'categoria')); 

            if (!empty($posto_tipo_posto)) {
               
                $consulta_tipo_posto = " AND ((tbl_treinamento.parametros_adicionais->'tipo_posto' IS NULL) OR (tbl_treinamento.parametros_adicionais->'tipo_posto' ? '{$posto_tipo_posto}' ))";
            } else {
                $consulta_tipo_posto = 'AND tbl_treinamento.parametros_adicionais->\'tipo_posto\' IS NULL ';
            }

            if (!empty($posto_categoria_posto)) {
               
                $consulta_categoria_posto = " AND ((tbl_treinamento.parametros_adicionais->'categoria_posto' IS NULL) OR  (tbl_treinamento.parametros_adicionais->'categoria_posto' ? '{$posto_categoria_posto}' ))";
            } else {
                $consulta_categoria_posto = 'AND tbl_treinamento.parametros_adicionais->\'categoria_posto\' IS NULL ';
            }

            $linhaMarca = "<th>Marca</th>";
            $linhaMarcaSql = "  array_to_string(array_agg( DISTINCT tbl_marca.nome),', ') AS marca_nome,
                                array_to_string(array_agg( DISTINCT tbl_linha.nome),', ') AS linha_nome,
                                array_to_string(array_agg( DISTINCT tbl_familia.descricao),', ') AS familia_descricao,";

            $groupTipoPosto = "  ";

            $relacaoLinhaTreinamento = " LEFT JOIN tbl_produto on (tbl_produto.linha = tbl_treinamento.linha OR tbl_produto.marca = tbl_treinamento.marca) AND tbl_produto.fabrica_i = {$login_fabrica}
                LEFT JOIN tbl_marca ON tbl_produto.marca = tbl_marca.marca
                LEFT JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha
                LEFT JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia
                ";

            $consulta_query = $consulta_marca . $consulta_linha . $consulta_familia . $consulta_tipo_posto . $consulta_categoria_posto;
        }

        if (in_array($login_fabrica, [193])) {
            $relTreinamento   = "LEFT JOIN tbl_treinamento_posto ON tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                                JOIN tbl_treinamento_tipo ON tbl_treinamento.treinamento_tipo = tbl_treinamento_tipo.treinamento_tipo";
            $whereTreinamento = "AND lower(tbl_treinamento_tipo.nome) != lower('Online')";
        }

        $sql = "SELECT tbl_treinamento.treinamento,
                        tbl_treinamento.titulo,
                        tbl_treinamento.descricao,
                        tbl_treinamento.ativo,
                        tbl_treinamento.vagas,
                        tbl_treinamento.vaga_posto,
                        tbl_treinamento.local,
                        tbl_treinamento.cidade,
                        TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY') AS data_inicio,
                        TO_CHAR(tbl_treinamento.data_fim,   'DD/MM/YYYY') AS data_fim,
                        TO_CHAR(prazo_inscricao,            'DD/MM/YYYY') AS prazo_inscricao,
                        $linhaMarcaSql
                        (
                            SELECT COUNT(*)
                                FROM tbl_treinamento_posto
                                WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                                AND   tbl_treinamento_posto.ativo IS TRUE
                                $cond_tecnico
                        )                                                 AS qtde_postos,
                        (
                            SELECT COUNT(*)
                                FROM tbl_treinamento_posto
                                WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                                    AND tbl_treinamento_posto.posto = $login_posto
                                    AND tbl_treinamento_posto.ativo IS TRUE
                                    $cond_tecnico
                        )                                                 AS qtde_inscritos
                    FROM tbl_treinamento
                        JOIN tbl_admin       USING(admin)
                        $relacaoLinhaTreinamento
                        $relTreinamento
                    WHERE tbl_treinamento.fabrica     = $login_fabrica
                        AND tbl_treinamento.ativo       IS TRUE
                        AND tbl_treinamento.data_inicio >= CURRENT_DATE
                        AND tbl_treinamento.data_finalizado IS NULL
                        $consulta_query
                        $whereTreinamento
                    GROUP BY
                        tbl_treinamento.treinamento,
                        tbl_treinamento.titulo,
                        tbl_treinamento.descricao,
                        tbl_treinamento.ativo,
                        tbl_treinamento.vagas,
                        tbl_treinamento.vaga_posto,
                        tbl_treinamento.local,
                        tbl_treinamento.cidade,
                        data_inicio,
                        data_fim,
                        prazo_inscricao,
                        $groupTipoPosto
                        qtde_postos, qtde_inscritos
                    ORDER BY tbl_treinamento.data_inicio, tbl_treinamento.titulo ";
        $res = pg_exec ($con,$sql);

        if (pg_numrows($res) > 0) {
            if ($treinamento_prazo_inscricao) {
                $prazoTD =  "<th>".traduz("prazo.inscricao")."</th>";
            }

            $resposta  .=  "
                <table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='700'>
                  <thead>
                  <tr class='Titulo' height='25'>
                    <th></th>
                    <th>".traduz("titulo")."</th>
                    <th>".traduz("data.inicio")."</th>
                    <th>".traduz("data.fim")."</th>
                    $prazoTD
                    $linhaMarca
                    <th>".traduz("total.vagas")."</th>
                    <th>".traduz("local")."</th>
                    <th>".traduz("situacao")."</th>";

            if (in_array($login_fabrica, [20])) {

                $resposta  .=  "
                    <th>".traduz("qtd.tecnicos.cadastrados")."</th>
                    <th>".traduz("qtd.vagas.disponiveis.por.posto")."</th>";
            }

            if (in_array($login_fabrica, [1])) {
                $resposta  .=  "
                    <th>".traduz('Qtde Técnicos Cadastrados')."</th>
                    <th>".traduz('Qtde. Vagas Disponíveis')."</th>
                    <th colspan ='2' >".traduz("acao")."</th>";
            }

            $resposta  .=  "
                  </tr>
                  </thead>
                  <tbody>
                ";

            for ($i=0; $i<pg_numrows($res); $i++) {
                $treinamento       = trim(pg_result($res,$i,'treinamento'));
                $titulo            = trim(pg_result($res,$i,'titulo'));
                if (mb_check_encoding($titulo, 'UTF-8')) {
                    $titulo = utf8_decode($titulo);
                }
                $descricao         = trim(pg_result($res,$i,'descricao'));
                $ativo             = trim(pg_result($res,$i,'ativo'));
                $data_inicio       = trim(pg_result($res,$i,'data_inicio'));
                $data_fim          = trim(pg_result($res,$i,'data_fim'));
                $prazo_inscricao   = trim(pg_result($res,$i,'prazo_inscricao'));
                $linha_nome        = trim(pg_result($res,$i,'linha_nome'));
                $familia_descricao = trim(pg_result($res,$i,'familia_descricao'));
                $vagas             = trim(pg_result($res,$i,'vagas'));
                $vagas_postos      = trim(pg_result($res,$i,'qtde_postos'));
                $vagas_ocupadas    = trim(pg_result($res,$i,'qtde_inscritos'));
                $local             = trim(pg_result($res,$i,'local'));
                if (mb_check_encoding($local, 'UTF-8')) {
                    $local = utf8_decode($local);
                }
                $cidade            = trim(pg_result($res,$i,'cidade'));
                $qtd_vaga_posto = pg_fetch_result($res, $i, vaga_posto);

                if(in_array($login_fabrica, [1,117,138])){ //HD-3261932
                    $mostrar = "";
                    $sql2 = "SELECT posto
                            FROM tbl_treinamento_posto
                            WHERE tbl_treinamento_posto.treinamento = $treinamento
                            AND tbl_treinamento_posto.ativo IS TRUE
                            AND tecnico isnull";
                    $res2 = pg_query($con, $sql2);
                    if(pg_num_rows($res2) > 0){
                        $posto_especifico = pg_fetch_all_columns($res2);
                        if(in_array($login_posto, $posto_especifico)){
                            $mostrar = 't';
                        }else{
                            $mostrar = 'f';
                        }
                    }else{
                        $mostrar = 't';
                    }

                    if($mostrar == 'f'){
                        continue;
                    }
                    // FIM HD-3261932
                }

                $localizacao = '';
                if ($cidade != '') {
                    $sql = "SELECT cidade,nome,estado FROM tbl_cidade WHERE cidade = $cidade;";
                    $resCidade = pg_exec($con,$sql);
                    if(pg_num_rows($res) > 0){
                        $cidade        = pg_result($resCidade,0,'cidade');
                        $nome_cidade   = pg_result($resCidade,0,'nome');
                        $estado_cidade = pg_result($resCidade,0,'estado');
                        $localizacao   = $nome_cidade.", ".$estado_cidade;
                    }
                }

                //vagas_ocupadas -> Quantidade de inscritos do Posto
                //vagas_postos -> Quantidade de inscritos geral
                if (in_array($login_fabrica, [1,20])) {
                    if($vagas_postos >= $vagas){
                        $qtd_disponivel=0;
                    }else{
                        if (in_array($login_fabrica, [1])) {
                            if (empty($qtd_vaga_posto)) {
                                $qtd_disponivel = $vagas - $vagas_postos;
                            } else {
                                $qtd_disponivel=$qtd_vaga_posto-$vagas_ocupadas;
                            }
                        } else {
                            $qtd_disponivel=5-$vagas_ocupadas;
                        }
                    }
                }

                $cor = ($cor == '#F1F4FA') ? '#F7F5F0' : '#F1F4FA';

                if (in_array($login_fabrica, [1])) {
                    $linha_nome         = trim(pg_result($res,$i,'marca_nome'));
                    if (empty($linha_nome)) {
                    unset($array_linha_nome);
                    unset($linha_nome);
                    $sql_linha = "SELECT (parametros_adicionais -> 'marca') AS marca FROM tbl_treinamento WHERE fabrica = $login_fabrica AND treinamento = $treinamento";
                    $res_linha = pg_query($con, $sql_linha);
                    if (pg_num_rows($res_linha) > 0) {
                        $linha_sql = pg_fetch_result($res_linha, 0, 'marca');
                        $linha_sql  = json_decode($linha_sql);    
                        $sql_linha_nome = "SELECT nome FROM tbl_marca WHERE fabrica = $login_fabrica AND marca in (".implode(',', $linha_sql).")";
                        $res_linha_nome = pg_query($con, $sql_linha_nome);
                        if (pg_num_rows($res_linha_nome) > 0) {
                            for ($m=0; $m < pg_num_rows($res_linha_nome); $m++) { 
                                $array_linha_nome[] = pg_fetch_result($res_linha_nome, $m, 'nome'); 
                            }
                        }
                            $linha_nome = implode(',', $array_linha_nome);    
                        }
                    }

                    if($qtd_disponivel > 0){
                        $situacao = "<img src='admin/imagens_admin/status_verde.gif'> ".traduz("ha.vagas");
                    } else {
                        $situacao = "<img src='admin/imagens_admin/status_vermelho.gif'> ".traduz("sem.vagas");
                    }
                } else {
                    if($vagas_postos >= $vagas) $situacao = "<img src='admin/imagens_admin/status_vermelho.gif'> ".traduz("sem.vagas");
                    else                        $situacao = "<img src='admin/imagens_admin/status_verde.gif'> ".traduz("ha.vagas");
                }

                if($vagas_ocupadas>0)$tem = "<img src='imagens/img_ok.gif'>";
                else                 $tem = "";

                $sobra = $vagas - $vagas_postos;

                $resposta  .=  "<TR bgcolor='$cor'class='Conteudo'>";
                $resposta  .=  "<TD align='center'>$tem </TD>";

                if($login_fabrica == 20){
                    if ($qtd_disponivel>0){
                        $resposta  .=  "<TD align='left'nowrap><a href='javascript:treinamento_formulario($treinamento)'>$titulo</a></TD>";
                    }else{
                            $resposta  .=  "<TD align='left'nowrap>$titulo</TD>";
                    }
                }elseif (in_array($login_fabrica, [1])) {
                    $resposta  .=  "<TD align='left'nowrap>$titulo</TD>";
                } else{
                    $resposta  .=  "<TD align='left'nowrap><a href='javascript:treinamento_formulario($treinamento)'>$titulo</a></TD>";
                }
                $resposta  .=  "<TD align='left'>$data_inicio</a></TD>";
                $resposta  .=  "<TD align='left'>$data_fim</TD>";
                if ($treinamento_prazo_inscricao) {
                    $resposta  .=  "<TD align='left'>$prazo_inscricao</TD>";
                }
                // Para Black é Marca e não Linha
                $resposta  .=  "<TD align='left'>$linha_nome</TD>";
                $resposta  .=  "<TD align='center'>";
                if($login_fabrica == 20 || isset($novaTelaOs)) $resposta  .=  $sobra;
                else                     $resposta  .=  $vagas;
                $resposta  .=  "</TD>";
                if($local != ""){
                    $local .= ", ";
                }
                $resposta  .=  "<TD align='left'>$local $localizacao </TD>";
                $resposta  .=  "<TD align='left'>$situacao</TD>";

                if (in_array($login_fabrica, [1,20])) {
                    $resposta  .=  "<TD align='left'>$vagas_ocupadas</TD>";
                    $resposta  .=  "<TD align='left'>$qtd_disponivel</TD>";
                }

                if (in_array($login_fabrica, [1])) {

                    $resposta  .=  "<td align='center'><button type='button' class='btn envia-notificao' data-url='admin/treinamento_notificacao.php?treinamento=$treinamento&area=posto'>".traduz("notificacao")."</button></td>";
                
                    if($qtd_disponivel > 0 ){
                        $resposta  .=  "<TD align='center' width='14%'> <button class='btn btn-inscreva-tecnico' type='button' onClick ='javascript:treinamento_formulario($treinamento)'>".traduz("inscreva.se")."</button></TD>";
                    } else {
                        $resposta  .=  "<TD align='left'></TD>";
                    }
                }
                $resposta  .=  "</TR>";

                $total = $total_os + $total;

            }
            $resposta .= " </TABLE>";
        }


        //--==== Ver técnicos cadastrados em treinamentos ============================


            $sql = "SELECT DISTINCT tbl_tecnico.nome as tecnico_nome                        ,
                tbl_tecnico.cpf as tecnico_cpf                                              ,
                tbl_treinamento.ativo                                                 ,
                tbl_treinamento.titulo                                                      ,
                tbl_treinamento.local                                                      ,
                tbl_treinamento.cidade                                                      ,
                tbl_treinamento_posto.ativo AS treinamento_posto_ativo,
                TO_CHAR(tbl_treinamento_posto.data_inscricao,'DD/MM/YYYY') AS data_inscricao,
                TO_CHAR(tbl_treinamento_posto.data_inscricao,'HH24:MI:SS') AS hora_inscricao

            FROM tbl_treinamento_posto
            JOIN tbl_treinamento using(treinamento)
            JOIN tbl_tecnico on tbl_tecnico.tecnico = tbl_treinamento_posto.tecnico
            JOIN tbl_posto   on tbl_treinamento_posto.posto = tbl_posto.posto
            JOIN      tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            WHERE tbl_treinamento_posto.posto = $login_posto
            AND  tbl_treinamento.fabrica = $login_fabrica
            ORDER BY tbl_treinamento.titulo" ;
            #echo $sql;exit;
        $res = pg_exec ($con,$sql);

        if (pg_numrows($res) > 0) {

            $resposta  .=  "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='700'>";
            $resposta  .=  "<TR class='Conteudo2' height='20' bgcolor='#ffffff'>";
            $resposta  .=  "<TD.colspan='10' align='center'><br><img src='imagens/img_ok.gif'> <b>".traduz("treinamento.s.ja.agendado.s.pelo.posto")."</b></td>";
            $resposta  .=  "</TR>";
            $resposta  .=  "</table>";
            $resposta  .=  "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='700'>";
            $resposta  .=  "<TR class='Titulo2' height='20' bgcolor='$cor'>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>".traduz("titulo")."</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>".traduz("nome.do.tecnico")."</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>".traduz("cpf.do.tecnico")."</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>".traduz("data.da.inscricao")."</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>".traduz("hora.da.inscricao")."</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>".traduz("local")."</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>".traduz("treinamento.ativo")." </b></TD>";
            if($login_fabrica == 138){ //HD-2930346
                $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>".traduz("inscricao.ativo")."</b></TD>";
            }
            $resposta  .=  "</TR>";

            for ($i=0; $i<pg_numrows($res); $i++){

                $tecnico_nome      = trim(pg_result($res,$i,tecnico_nome))  ;
                $tecnico_cpf       = trim(pg_result($res,$i,tecnico_cpf))   ;
                $data_inscricao    = trim(pg_result($res,$i,data_inscricao));
                $hora_inscricao    = trim(pg_result($res,$i,hora_inscricao));
                $titulo            = trim(pg_result($res,$i,titulo))        ;
                if (mb_check_encoding($titulo, 'UTF-8')) {
                    $titulo = utf8_decode($titulo);
                }
                $ativo             = trim(pg_result($res,$i,ativo))         ;
                $local             = trim(pg_result($res,$i,local))         ;
                if (mb_check_encoding($local, 'UTF-8')) {
                    $local = utf8_decode($local);
                }
                $cidade            = trim(pg_result($res,$i,cidade))         ;
                $treinamento_posto_ativo = trim(pg_result($res,$i,treinamento_posto_ativo))         ;

                if(strlen(pg_result($res,$i,cidade)) > 0){

                   $cidade = pg_result($res,$i,cidade);
                   $sql = "SELECT cidade,nome,estado from tbl_cidade where cidade = $cidade;";
                   $resCidade = pg_exec($con,$sql);
                   if(pg_num_rows($res) > 0){
                        $cidade = pg_result($resCidade,0,cidade);
                            $nome_cidade = pg_result($resCidade,0,nome);
                        $estado_cidade = pg_result($resCidade,0,estado);
                        $localizacao = $nome_cidade.", ".$estado_cidade;
                   }else{
                        $localizacao = "";
                   }

                }else{
                       $localizacao = "";
                }

                if($ativo == 't')  $ativo = "<img src='admin/imagens_admin/status_verde.gif'>";
                else               $ativo = "<img src='admin/imagens_admin/status_vermelho.gif'>";

                if($login_fabrica == 138){ //HD-2930346
                    if($treinamento_posto_ativo == 't'){
                        $treinamento_posto_ativo = "<img src='admin/imagens_admin/status_verde.gif'>";
                    }else{
                        $treinamento_posto_ativo = "<img src='admin/imagens_admin/status_vermelho.gif'>";
                    }
                }


                if($cor=="#F6F6F6")$cor = '#FAFAFA';
                else               $cor = '#F6F6F6';

                $resposta  .=  "<TR bgcolor='$cor'class='Conteudo'>";
                $resposta  .=  "<TD align='left'>$titulo </TD>";
                $resposta  .=  "<TD align='left'nowrap>$tecnico_nome</TD>";
                $resposta  .=  "<TD align='left'>$tecnico_cpf</TD>";
                $resposta  .=  "<TD align='center'>$data_inscricao</TD>";
                $resposta  .=  "<TD align='center'>$hora_inscricao</TD>";
                $resposta  .=  "<TD align='center'>$local $localizacao</TD>";
                $resposta  .=  "<TD align='center'>$ativo</TD>";
                if($login_fabrica == 138){ //HD-2930346
                    $resposta  .=  "<TD align='center'>$treinamento_posto_ativo</TD>";
                }

                $resposta  .=  "</TR>";

            }
            $resposta .= " </TABLE>";
        }else{
            $resposta .= "<b>".traduz("nenhum.treinamento.cadastrado")."</b>";
        }

    }


    echo "ok|".$resposta;
    exit;
}

if($_GET['ajax']=='sim' AND $_GET['acao']=='tecnico') {

    $treinamento  = trim($_GET["treinamento"]) ;

    if (in_array($login_fabrica, array(169,170,193))){
        $cond_treinamento_ativo = " AND tbl_treinamento_posto.ativo IS TRUE ";
    }else{
        $cond_treinamento_ativo = "";
    }

    $sql2 = "SELECT tbl_tecnico.nome as tecnico_nome      ,
                    tbl_tecnico.cpf as tecnico_cpf       ,
                    tbl_tecnico.rg as tecnico_rg       ,";
    $sql2 .= (in_array($login_fabrica,array(1,42))) ? "\ntbl_tecnico.celular " : "\ntbl_tecnico.telefone ";
    $sql2 .= "      as tecnico_fone      ,
                    confirma_inscricao,
                    hotel             ,
                    tbl_treinamento_posto.ativo,
                    tbl_treinamento_posto.treinamento_posto
            FROM    tbl_treinamento_posto
            JOIN    tbl_tecnico ON tbl_treinamento_posto.tecnico = tbl_tecnico.tecnico
            WHERE   treinamento = $treinamento
            AND     tbl_treinamento_posto.posto = $login_posto
            $cond_treinamento_ativo
    ";
    $res2 = pg_exec ($con,$sql2);
    $tecnicos_inscritos = pg_num_rows($res2);

    if ($tecnicos_inscritos > 0) {

        $resposta  .= "<br><table width='695' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center'>";
        $resposta  .= "<tr bgcolor='$cor'class='Caixa'>";

        if ($tecnicos_inscritos == 1)
            $resposta  .= "<td colspan='7'><b>".traduz("tem.um.tecnico.inscrito.no.treinamento").": </b></td>";
        else
            $resposta  .= "<td colspan='7'><b>".traduz("os.seguintes.tecnicos.estao.inscritos.no.treinamento").": </b></td>";
        $resposta  .= "</tr>";

        $resposta  .= "<tr bgcolor='$cor'class='Caixa'>";
        $resposta  .= "<td width='120'><b>".traduz("nome.do.tecnico")."</b></td>";
        if($login_fabrica != 117){
            $resposta  .= "<td width='40'><b>".traduz("rg")."</b></td>";
        }
        $resposta  .= "<td width='40'><b>".traduz("cpf")."</b></td>";
        (in_array($login_fabrica, [1]))? $resposta  .= "<td width='40'><b>".traduz("celular")."</b></td>" : $resposta  .= "<td width='40'><b>".traduz("telefone")."</b></td>";
        //$resposta  .= "<td width='40'><b>Telefone</b></td>";

        $resposta  .= "<td width='80'><b>".traduz("inscrito")."</b></td>";
        $resposta  .= "<td width='80'><b>".traduz("confirmado")."</b></td>";
        if(!in_array($login_fabrica, array(1,117,138))) {
            $resposta  .= "<td width='80'><b>".traduz("hotel")."</b></td>";

        }


        for ($i=0; $i<pg_numrows($res2); $i++){

            $tecnico_nome       = trim(pg_result($res2,$i,tecnico_nome));
            $tecnico_rg         = trim(pg_result($res2,$i,tecnico_rg));
            $tecnico_cpf        = trim(pg_result($res2,$i,tecnico_cpf));
            $tecnico_fone       = trim(pg_result($res2,$i,tecnico_fone));
            $confirma           = trim(pg_result($res2,$i,confirma_inscricao));
            $ativo              = trim(pg_result($res2,$i,ativo));
            $hotel              = trim(pg_result($res2,$i,hotel));
            $treinamento_posto  = trim(pg_result($res2,$i,treinamento_posto));

            if($ativo =='f')    $ativo    = "<img src='admin/imagens_admin/status_vermelho.gif'> ".traduz("cancelado");
            else                $ativo    = "<img src='admin/imagens_admin/status_verde.gif'><div id='tec_ativo_$i'><a href='javascript:if (confirm(\"".traduz("deseja.cancelar.esta.inscricao")."\") == true) {ativa_desativa_tecnico(\"$treinamento_posto\",\"$i\")}'> ".traduz("sim")."</a></div>"         ;
            if($confirma =='f') $confirma = "<img src='admin/imagens_admin/status_vermelho.gif'> ".traduz("nao")      ;
            else                $confirma = "<img src='admin/imagens_admin/status_verde.gif'> ".traduz("sim")        ;
            if($hotel =='f')    $hotel    = "<img src='admin/imagens_admin/status_vermelho.gif'> ".traduz("nao")      ;
            else                $hotel    = "<img src='admin/imagens_admin/status_verde.gif'> ".traduz("sim")         ;

            $resposta  .= "<tr bgcolor='$cor'class='Caixa'>";
            $resposta  .= "<td>$tecnico_nome</td>";
            if($login_fabrica != 117){
                $resposta  .= "<td>$tecnico_rg</td>";
            }
            $resposta  .= "<td>$tecnico_cpf</td>";
            $resposta  .= "<td>$tecnico_fone</td>";
            $resposta  .= "<td>$ativo</td>";
            $resposta  .= "<td>$confirma</td>";
            if(!in_array($login_fabrica, array(1,117,138,148))) {
                $resposta  .= "<td>$hotel</td>";
            }

            $resposta  .= "</tr>";
        }
        $resposta  .= "</table>";
    }else{
        $resposta  .= traduz("nao.ha.tecnicos.do.seu.posto.cadastrado.neste.treinamento");
    }
    echo "ok|".$resposta;
    exit;
}

//--==== Formulário de cadastro de treinamento ===============================
if($_GET['ajax']=='sim' AND $_GET['acao']=='formulario') {

    $treinamento  = trim($_GET["treinamento"]) ;


    $sql = "SELECT contato_email as email FROM tbl_posto_fabrica WHERE posto = $login_posto and fabrica = $login_fabrica";
    $res = pg_exec ($con,$sql);
    $posto_email = trim(pg_result($res,0,email));

    if(in_array($login_fabrica, [1,117,138,169,170,193])){ //HD-3261932 //HD-3261932
        $cond_tecnico = " AND tbl_treinamento_posto.tecnico IS NOT NULL ";
    }

    if (in_array($login_fabrica, array(169,170,193))){
        $select_linha = " ARRAY_TO_STRING(array_agg(DISTINCT(tbl_linha.nome)), ', ', null) AS linha_nome, ";
        $joinTblTreinamento  = " JOIN    tbl_admin   USING(admin)
                                 JOIN tbl_treinamento_produto ON tbl_treinamento_produto.treinamento = tbl_treinamento.treinamento
                                    AND tbl_treinamento_produto.fabrica = $login_fabrica
                                ";
        $joinTblTreinamento .= " JOIN tbl_linha ON tbl_linha.linha = tbl_treinamento_produto.linha AND tbl_linha.fabrica = $login_fabrica";
        #$joinTblTreinamento .= " LEFT JOIN    tbl_familia USING(familia)";
        $joinTblTreinamento .= " LEFT JOIN    tbl_familia ON tbl_familia.familia = tbl_treinamento.familia";

        $group_by            = "GROUP BY tbl_treinamento.treinamento ,
                                tbl_treinamento.titulo ,
                                tbl_treinamento.descricao ,
                                tbl_treinamento.vagas ,
                                tbl_treinamento.vaga_posto ,
                                tbl_treinamento.local ,
                                tbl_treinamento.cidade ,
                                tbl_treinamento.qtde_participante ,
                                data_inicio,
                                data_fim,
                                prazo_inscricao,
                                tbl_admin.nome_completo,
                                familia_descricao,
                                qtde_postos,
                                qtde_inscritos,
                                tbl_treinamento.adicional,
                                dentro_do_prazo";

    }elseif (in_array($login_fabrica, [1])){
        $select_linha = " tbl_linha.nome                                      AS linha_nome       , ";
        $joinTblTreinamento = " JOIN    tbl_admin   USING(admin)
            LEFT JOIN    tbl_linha   USING(linha)
            LEFT JOIN    tbl_familia USING(familia)
            LEFT JOIN tbl_marca on tbl_treinamento.marca = tbl_marca.marca ";

    }else{
        $select_linha = " tbl_linha.linha                                     AS linha,
                               tbl_linha.nome                                      AS linha_nome, ";
        $joinTblTreinamento = " JOIN    tbl_admin   USING(admin)
            JOIN    tbl_linha   USING(linha)
            LEFT JOIN    tbl_familia USING(familia)";
    }


    if (in_array($login_fabrica, array(148,169,170,193))){
        $campo_time = "HH24:MI:SS"; 
    }

    $sql = "SELECT  tbl_treinamento.treinamento                                             ,
                    tbl_treinamento.titulo                                                  ,
                    tbl_treinamento.descricao                                               ,
                    tbl_treinamento.vagas                                                   ,
                    tbl_treinamento.vaga_posto                                              ,
                    tbl_treinamento.local                                                   ,
                    tbl_treinamento.cidade                                                  ,
                    tbl_treinamento.qtde_participante                                       ,
                    TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY $campo_time')   AS data_inicio      ,
                    TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY $campo_time')      AS data_fim         ,
                    TO_CHAR(prazo_inscricao,'DD/MM/YYYY')               AS prazo_inscricao  ,
                    tbl_admin.nome_completo                                                 ,
                    {$select_linha}
                    tbl_familia.descricao                               AS familia_descricao,
                    (
                        SELECT  count(*)
                        FROM    tbl_treinamento_posto
                        WHERE   tbl_treinamento_posto.treinamento   = tbl_treinamento.treinamento
                        AND     tbl_treinamento_posto.ativo         IS TRUE
                        $cond_tecnico
                    )                                                   AS qtde_postos      ,
                    (
                        SELECT COUNT(*)
                            FROM tbl_treinamento_posto
                            WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                                AND tbl_treinamento_posto.posto = $login_posto
                                AND tbl_treinamento_posto.ativo IS TRUE
                                $cond_tecnico
                    )                                                 AS qtde_inscritos     ,
                    tbl_treinamento.adicional,
                    prazo_inscricao IS NULL OR prazo_inscricao >= CURRENT_DATE AS dentro_do_prazo
            FROM    tbl_treinamento
            $joinTblTreinamento
            WHERE   tbl_treinamento.fabrica     = $login_fabrica
            AND     tbl_treinamento.treinamento = $treinamento
            AND     tbl_treinamento.ativo IS TRUE
        {$group_by}
      ORDER BY      tbl_treinamento.data_inicio,tbl_treinamento.titulo";      
    $res = pg_exec ($con,$sql);
    #$resposta  .= "$sql";
    if (pg_numrows($res) > 0) {

        $treinamento       = trim(pg_result($res,0,'treinamento'));
        $titulo            = trim(pg_result($res,0,'titulo'));
        if (mb_check_encoding($titulo, 'UTF-8')) {
            $titulo = utf8_decode($titulo);
        }
        $descricao         = trim(pg_result($res,0,'descricao'));
        if (mb_check_encoding($descricao, 'UTF-8')) {
            $descricao = utf8_decode($descricao);
        }
        $vagas             = trim(pg_result($res,0,'vagas'));
        $vaga_posto        = trim(pg_result($res,0,'vaga_posto'));
        $data_inicio       = trim(pg_result($res,0,'data_inicio'));
        $data_fim          = trim(pg_result($res,0,'data_fim'));
        $prazo_inscricao   = trim(pg_result($res,0,'prazo_inscricao'));
        $nome_completo     = trim(pg_result($res,0,'nome_completo'));
        $linha             = trim(pg_result($res,0,'linha'));
        if ($login_fabrica == 1) {
            $linha_nome        = trim(pg_result($res,0,'linha_nome'));
            $familia_descricao = trim(pg_result($res,0,'familia_descricao'));
            if (empty($linha_nome)) {
                unset($array_linha_nome);
                unset($linha_nome);
                $sql_linha = "SELECT (parametros_adicionais -> 'linha') AS linha FROM tbl_treinamento WHERE fabrica = $login_fabrica AND treinamento = $treinamento";
                $res_linha = pg_query($con, $sql_linha);
                if (pg_num_rows($res_linha) > 0) {
                    $linha_sql = pg_fetch_result($res_linha, 0, 'linha');
                    $linha_sql  = json_decode($linha_sql);    
                    $sql_linha_nome = "SELECT nome FROM tbl_linha WHERE fabrica = $login_fabrica AND linha in (".implode(',', $linha_sql).")";
                    $res_linha_nome = pg_query($con, $sql_linha_nome);
                    if (pg_num_rows($res_linha_nome) > 0) {
                        for ($m=0; $m < pg_num_rows($res_linha_nome); $m++) { 
                            $array_linha_nome[] = pg_fetch_result($res_linha_nome, $m, 'nome'); 
                        }
                    }
                    $linha_nome = implode(',', $array_linha_nome);    
                }
            }
            if (empty($familia_descricao)) {
                unset($array_familia_nome);
                unset($familia_descricao);
                $sql_familia = "SELECT (parametros_adicionais -> 'familia') AS familia FROM tbl_treinamento WHERE fabrica = $login_fabrica AND treinamento = $treinamento";
                $res_familia = pg_query($con, $sql_familia);
                if (pg_num_rows($res_familia) > 0) {
                    $familia_sql = pg_fetch_result($res_familia, 0, 'familia');
                    $familia_sql  = json_decode($familia_sql);    
                    $sql_familia_nome = "SELECT descricao FROM tbl_familia WHERE fabrica = $login_fabrica AND familia in (".implode(',', $familia_sql).")";
                    $res_familia_nome = pg_query($con, $sql_familia_nome);
                    if (pg_num_rows($res_familia_nome) > 0) {
                        for ($m=0; $m < pg_num_rows($res_familia_nome); $m++) { 
                            $array_familia_nome[] = pg_fetch_result($res_familia_nome, $m, 'descricao'); 
                        }
                    }
                    $familia_descricao = implode(',', $array_familia_nome);    
                }
            }
        } else {
            $linha_nome        = trim(pg_result($res,0,'linha_nome'));
            $familia_descricao = trim(pg_result($res,0,'familia_descricao'));
        }
        $vagas_postos      = trim(pg_result($res,0,'qtde_postos'));
        $vagas_ocupadas    = trim(pg_result($res,0,'qtde_inscritos'));
        $adicional         = trim(pg_result($res,0,'adicional'));
        $local             = trim(pg_result($res,0,'local'));
        $qtde_participante = trim(pg_result($res,0,'qtde_participante'));
        $dentro_do_prazo   = (pg_result($res,0,'dentro_do_prazo') == 't');

        if (strlen(pg_result($res,0,cidade)) > 0) {

            $cidade = pg_result($res,0,cidade);
            $sql = "SELECT cidade,nome,estado from tbl_cidade where cidade = $cidade;";
            $resCidade = pg_exec($con,$sql);
            if (pg_num_rows($res) > 0) {
                $cidade        = pg_result($resCidade,0,cidade);
                $nome_cidade   = pg_result($resCidade,0,nome);
                $estado_cidade = pg_result($resCidade,0,estado);
                $localizacao = ", ".$nome_cidade.", ".$estado_cidade;
            }else{
                $localizacao = "";
            }

        }else{
            $localizacao = "";
        }

		if(!empty($linha)) {
			$sqlVer = " SELECT  count(tbl_posto_linha.linha) AS atende_linha_treinamento
						FROM    tbl_posto_linha
						JOIN    tbl_linha   ON  tbl_linha.linha     = tbl_posto_linha.linha
											AND tbl_linha.fabrica   = $login_fabrica
						WHERE   tbl_posto_linha.posto = $login_posto
						AND     tbl_posto_linha.linha = $linha
			";
			$resVer = pg_query($con,$sqlVer);
			$atendeLinhaTreinamento = pg_fetch_result($resVer,0,atende_linha_treinamento);
		}
        //vagas_ocupadas -> Quantidade de inscritos do Posto
        //vagas_postos -> Quantidade de inscritos geral
        if (in_array($login_fabrica, [1,20,169,170,193])) {
            if($vagas_postos >= $vagas){
                $qtd_disponivel=0;
            }else{
                if (in_array($login_fabrica, [1])) {
                    if (empty($qtd_vaga_posto)) {
                        $qtd_disponivel = $vagas - $vagas_postos;
                        $qtd_disponivel_vagas = $qtd_disponivel;
                    } else {
                        $qtd_disponivel=$qtd_vaga_posto-$vagas_ocupadas;
                        $qtd_disponivel_vagas = $qtd_disponivel;
                    }
                } elseif(in_array($login_fabrica, array(169,170,193)) && is_numeric($qtde_participante) && $qtde_participante > 0){
                    $vagas_disponiveis = $qtde_participante;

                    if($vagas_disponiveis >= $vagas_postos){
                        $qtd_disponivel = 0;
                        $qtd_disponivel_vagas = $qtd_disponivel;
                    }else{
                        $qtd_disponivel_vagas= $vagas_disponiveis - $vagas_ocupadas;
                    }

                    if (in_array($login_fabrica, [193])) {
                        $vagaPorPosto = $qtde_participante;

                        if ($vagaPorPosto > 0) {
                            $qtd_disponivel_vagas = $vagaPorPosto - $vagas_ocupadas;
                        } else {
                            $qtd_disponivel_vagas = $vagas - $vagas_ocupadas;
                        }
                    }
                } else {
                    $qtd_disponivel=5-$vagas_ocupadas;
                    $qtd_disponivel_vagas = $qtd_disponivel;
                }
            }
        }

        if (in_array($login_fabrica, [1])) {
            if($vagas_postos > $vagas) $situacao = "<img src='admin/imagens_admin/status_vermelho.gif'> ".traduz("sem.vagas");
            else                       $situacao = "<img src='admin/imagens_admin/status_verde.gif'> ".traduz("ha.vagas");
        } else {
            if($vagas_postos >= $vagas) $situacao = "<img src='admin/imagens_admin/status_vermelho.gif'>  ".traduz("sem.vagas");
            else                       $situacao = "<img src='admin/imagens_admin/status_verde.gif'>  ".traduz("ha.vagas");

            if (in_array($login_fabrica, [193])) {
                $vagaPorPosto =  (!empty(pg_result($res,0,'qtde_participante'))) ? trim(pg_result($res,0,'qtde_participante')) : 0;
                $xsobra       = ($vagaPorPosto > 0) ? $vagaPorPosto - $vagas_ocupadas : $vagas - $vagas_ocupadas;
                $situacao     = ($xsobra > 0) ? "<img src='admin/imagens_admin/status_verde.gif'> ".traduz("ha.vagas") : "<img src='admin/imagens_admin/status_vermelho.gif'> ".traduz("sem.vagas");
            }
        }


        $resposta  .= "<FORM name='frm_relatorio' METHOD='POST' ACTION='$PHP_SELF '>";
        $resposta  .= "<input type='hidden' name='treinamento' id='treinamento' value='$treinamento'>";
        $resposta  .= "<div id='carregando' style='position: absolute;visibility:hidden;opacity:.90;' class='Carregando' width='150'></div>";

        $resposta  .= "<table width='700' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>";
        $resposta  .= "<tr>";
        $resposta  .= "<td class='Titulo' background='admin/imagens_admin/azul.gif'><b>".traduz("tema.do.treinamento").": $titulo</b></td>";
        $resposta  .= "</tr>";
        $resposta  .= "<tr>";
        $resposta  .= "<td bgcolor='#DBE5F5' valign='bottom'>";


        $resposta  .= "<table width='695' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center'>";

        $resposta  .= "<tr bgcolor='$cor'class='Caixa'>";
        $resposta  .= "<td><b>".traduz("linha").": </b></td>";
        $resposta  .= "<td>$linha_nome</td>";
        $resposta  .= "<td><b>".traduz("familia").": </b></td>";
        $resposta  .= "<td>$familia_descricao</td>";
        $sobra = $vagas - $vagas_postos;
        if(in_array($login_fabrica, array(20,169,170,193)) || isset($novaTelaOs)) $tot_vagas  .= $sobra;
        else                     $tot_vagas  .= $vagas;
        $resposta  .= "<td><b>".traduz("vagas").": $tot_vagas</b></td>";

        if(in_array($login_fabrica, array(1,20,169,170,193))) $resposta  .= "<td><b>".traduz("vagas.disponivel").": $qtd_disponivel_vagas</b></td>";

        $resposta  .= "</tr>";

        $resposta  .= "<tr bgcolor='$cor'class='Caixa' >";
        $resposta  .= "<td><b>".traduz("data.inicio").": </b></td>";
        $resposta  .= "<td width='140'>$data_inicio</td>";
        $resposta  .= "<td><b>".traduz("data.termino").": </b></td>";
        $resposta  .= "<td width='140'>$data_fim</td>";

        $resposta  .= "<td colspan='2'><b>$situacao</b></td>";
        $resposta  .= "</tr>";

        $resposta  .= "<tr bgcolor='$cor'class='Conteudo'>";
        $resposta  .= "<td colspan='7' class='Caixa'><b>".traduz("descricao").": </b><br>".nl2br($descricao)."</td>";
        $resposta  .= "</tr>";
        if (!$login_fabrica == 148) {
        $resposta  .= "<tr bgcolor='$cor'class='Conteudo'>";
        $resposta  .= "<td colspan='7' class='Caixa'><b>".traduz("local").": </b><br>".nl2br($local." ".$localizacao)."</td>";
        $resposta  .= "</tr>";
        }
        $resposta  .= "</table>";

        $resposta  .= "<center><a href='javascript:mostrar_treinamento(\"dados\");'>".traduz("ver.outros.treinamentos")."</a></center>";

        //--====== Exibe todos os técnicos que estão cadastrados neste treinamento ====================
        $resposta  .= "<div id='tecnico'></div>";


        //--====== Ver erros que ocorreram no cadastro ==============================================
        $resposta  .= "<div id='erro' style='visibility:hidden;opacity:.85;' class='Erro'></div>";

        //--====== Libera o formulário se houver vagas ===============================================
        if ( ($vagas_postos < $vagas and $dentro_do_prazo AND !in_array($login_fabrica, [1]) ) OR (in_array($login_fabrica, [1]) AND $dentro_do_prazo AND $vagas_postos <= $vagas  ) ) {
            if ($login_fabrica == $makita) $colspan = 0; else $colspan = 3;

            if (in_array($login_fabrica, [169,170,193])) {
                if (isset($_GET['isInfo']) && $_GET['isInfo'] == 't') {
                    $resposta  .= "<div id='cadastro' style='display: none;'>";
                } else {
                    $resposta  .= "<div id='cadastro'>";
                }
            } else {
                $resposta  .= "<div id='cadastro'>";
            }

            if (!in_array($login_fabrica, [1])) {
                $resposta  .= "<table style=' border: #D3BE96 1px solid; background-color: #FCF0D8 ' align='center' width='600'><tr><td class='Conteudo' align='center'><b>".traduz("a.confirmacao.da.inscricao.sera.feita.atraves.do.link.enviado.no.email.do.posto").".</b></td></tr></table>";
            }
            $resposta  .= "<table width='100%' border='0' cellspacing='1' cellpadding='2' >";

        if(!in_array($login_fabrica, array(20,169,170,193))){
            $resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
                $resposta  .= "<td width='10'>&nbsp;</td>";
                $resposta  .= "<td align='right'nowrap >".traduz("nome.do.tecnico")."</td>";
                $resposta  .= "<td align='left' colspan='3'>";
                $resposta  .= "<input type='text' name='tecnico_nome' id='tecnico_nome' size='65' maxlength='100' class='Caixa' value=''>";
                $resposta  .= "</td>";
                $resposta  .= "<td width='10'>&nbsp;</td>";
            $resposta  .= "</tr>";

            $resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
                $resposta  .= "<td width='10'>&nbsp;</td>";
                $resposta  .= "<td align='right' nowrap valign='top'> ".traduz("rg.do.tecnico")."</td>";
                $resposta  .= "<td align='left' colspan='{$colspan}'>";
                $resposta  .= "<input type='text' name='tecnico_rg' id='tecnico_rg' size='23' maxlength='14' class='Caixa' value=''>";
                $resposta  .= "</td>";

            if($login_fabrica == $makita){
                $resposta  .= "<td align='right' nowrap valign='top'>".traduz("celular.do.tecnico")."</td>";
                $resposta  .= "<td align='left'>";
                $resposta  .= "<input type='text' name='tecnico_celular' id='tecnico_celular' size='23' maxlength='13' class='Caixa' value=''>";
                $resposta  .= "</td>";
            }
                $resposta  .= "<td width='10'>&nbsp;</td>";
            $resposta  .= "</tr>";

            $resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
                $resposta  .= "<td width='10'>&nbsp;</td>";
                $resposta  .= "<td align='right' nowrap valign='top'> ".traduz("cpf.do.tecnico")."</td>";
                $resposta  .= "<td align='left'colspan='{$colspan}'>";
                $resposta  .= "<input type='text' name='tecnico_cpf' id='tecnico_cpf' size='23' maxlength='14' class='Caixa' value=''>";
                $resposta  .= "</td>";

            if($login_fabrica == $makita){
                $resposta  .= "<td align='right' nowrap valign='top'> ".traduz("e.mail.do.tecnico")." </td>";
                $resposta  .= "<td align='left'>";
                $resposta  .= "<input type='text' name='tecnico_email' id='tecnico_email' size='23' maxlength='60' class='Caixa' value=''>";
                $resposta  .= "</td>";
            }
                $resposta  .= "<td width='10'>&nbsp;</td>";
            $resposta  .= "</tr>";

            $resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
                $resposta  .= "<td width='10'>&nbsp;</td>";
                $resposta  .= "<td align='right' nowrap valign='top'> ".traduz("dt.nascimento.do.tecnico")."</td>";
                $resposta  .= "<td align='left' colspan='{$colspan}'>";
                $resposta  .= "<input type='text' name='tecnico_data_nascimento' id='tecnico_data_nascimento' size='10' maxlength='10' class='Caixa' value=''>";
            if($login_fabrica == $makita){
                $resposta  .= "<td align='right' nowrap valign='top'>  ".traduz("n.calcado")."  </td>";
                $resposta  .= "<td align='left'>";
                $resposta  .= "<select name='tecnico_calcado' id='tecnico_calcado'><option value='".traduz("selecione")."'>Selecione</option><option value='44'>44</option><option value='43'>43</option><option value='42'>42</option><option value='41'>41</option><option value='40'>40</option><option value='39'>39</option><option value='38'>38</option><option value='37'>37</option><option value='36'>36</option><option value='35'>35</option><option value='34'>34</option></select>";
                $resposta  .= "</td>";
            }
                $resposta  .= "<td width='10'>&nbsp;</td>";
            $resposta  .= "</tr>";

            $resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
                $resposta  .= "<td width='10'>&nbsp;</td>";
                if (in_array($login_fabrica, [1])) {
                    $resposta  .= "<td align='right' nowrap valign='top'>".traduz("celular.do.tecnico")." </td>";
                    $resposta  .= "<td align='left'>";
                    $resposta  .= "<input type='text' name='tecnico_celular' id='tecnico_celular' size='23' maxlength='13' class='Caixa' value=''>";
                    $resposta  .= "</td>";
                } else {
                    $resposta  .= "<td align='right' nowrap valign='top'>".traduz("telefone.contato")."</td>";
                    $resposta  .= "<td align='left' colspan='{$colspan}'>";
                    $resposta  .= "<input type='text' name='tecnico_fone' id='tecnico_fone' class='Caixa'>";
                    $resposta  .= "</td>";
                }

            if($login_fabrica == $makita){
                $resposta  .= "<td align='right' nowrap valign='top'> ".traduz("tipo.sanguineo")." </td>";
                $resposta  .= "<td align='left'>";
                $resposta  .= "<select name='tecnico_tipo_sanguineo' id='tecnico_tipo_sanguineo'>
                                <option value='Selecione'>".traduz("selecione")."</option>
                                <option value='A1'>A+</option>
                                <option value='A2'>A-</option>
                                <option value='B1'>B+</option>
                                <option value='B2'>B-</option>
                                <option value='AB1'>AB+</option>
                                <option value='AB2'>AB-</option>
                                <option value='O1'>O+</option>
                                <option value='O2'>O-</option>
                               </select>";
                $resposta  .= "</td>";
            }
                $resposta  .= "<td width='10'>&nbsp;</td>";
            $resposta  .= "</tr>";

        //Alteração chamado 3301309 - Label Nome Funcionário.
        }else if($login_fabrica == 20){

            $resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
                $resposta  .= "<td width='10'>&nbsp;</td>";
                $resposta  .= "<td align='right'nowrap >".traduz("nome.do.funcionario")."</td>";
                $resposta  .= "<td align='left' colspan='3'>";
                $resposta  .= "<select name='tecnico_nome' id='tecnico_nome' class='Caixa'>";
                $resposta  .= "<option value=''></option>";

                $sql = "SELECT tbl_tecnico.nome, tbl_tecnico.tecnico FROM tbl_tecnico WHERE posto = $login_posto AND fabrica = $login_fabrica";
                $res = @pg_exec ($con,$sql);

                if (@pg_numrows($res) > 0) {
                    for($i=0;$i < @pg_numrows($res);$i++){
                        $tecnico_nome = trim(@pg_result($res,$i,nome));
                        $tecnico = trim(@pg_result($res,$i,tecnico));

                        $resposta  .= "<option value='$tecnico'>$tecnico_nome</option>";
                    }
                }
                $resposta  .= "";
                $resposta  .= "</select>";
                $resposta  .= "</td>";
                $resposta  .= "<td width='10'>&nbsp;</td>";
            $resposta  .= "</tr>";

            $resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
                $resposta  .= "<td width='10'>&nbsp;</td>";
                $resposta  .= "<td align='right' nowrap valign='top'>".traduz("promotor")."</td>";
                $resposta  .= "<td align='left'colspan='3'>";
                $resposta  .= "<select name='promotor_treinamento' id='promotor_treinamento' class='Caixa'>";
                $resposta  .= "<option value=''></option>";
                $sql = "SELECT escritorio_regional FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
                $res = @pg_exec ($con,$sql);
                $escritorio_regional = trim(@pg_result($res,$i,escritorio_regional));

                $sql = "SELECT * FROM tbl_promotor_treinamento WHERE fabrica = $login_fabrica AND escritorio_regional = '$escritorio_regional' AND ativo IS TRUE order by nome";

                $res = @pg_exec ($con,$sql);
                if (@pg_numrows($res) > 0) {
                    for($i=0;$i < @pg_numrows($res);$i++){
                        $promotor_treinamento = trim(pg_result($res,$i,promotor_treinamento));
                        $nome                 = trim(pg_result($res,$i,nome));
                        $email                = trim(pg_result($res,$i,email));
                        $regiao               = trim(pg_result($res,$i,regiao));

                        $resposta  .= "<option value='$promotor_treinamento'>$nome</option>";
                    }
                }

                $resposta  .= "";
                $resposta  .= "</select>";
                $resposta  .= "</td>";
                $resposta  .= "<td width='10'>&nbsp;</td>";
            $resposta  .= "</tr>";

        }else{
            $resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
                $resposta  .= "<td width='10'>&nbsp;</td>";
                $resposta  .= "<td align='right'nowrap >".traduz("nome.do.tecnico")."</td>";
                $resposta  .= "<td align='left' colspan='3'>";
                $resposta  .= "<select name='tecnico_nome' id='tecnico_nome' class='Caixa'>";
                $resposta  .= "<option value=''></option>";

                $sql = "SELECT tbl_tecnico.nome, tbl_tecnico.tecnico FROM tbl_tecnico WHERE posto = $login_posto AND fabrica = $login_fabrica $funcao_T";

                $res = @pg_exec ($con,$sql);

                if (@pg_numrows($res) > 0) {
                    for($i=0;$i < @pg_numrows($res);$i++){
                        $tecnico_nome = trim(@pg_result($res,$i,nome));
                        $tecnico = trim(@pg_result($res,$i,tecnico));

                        $resposta  .= "<option value='$tecnico'>$tecnico_nome</option>";
                    }
                }
                $resposta  .= "";
                $resposta  .= "</select>";
                $resposta  .= "</td>";
                $resposta  .= "<td width='10'>&nbsp;</td>";
            $resposta  .= "</tr>";
        }

        if($login_fabrica == $makita){

            $resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
                $resposta  .= "<td width='10'>&nbsp;</td>";
                $resposta  .= "<td align='right' nowrap valign='top' > ".traduz("possui.passaporte")." <br/>".traduz("de.treinamento.makita")." </td>";
                $resposta  .= "<td align='left' colspan='3'>";
                $resposta  .= "<input type='checkbox' name='tecnico_passaporte' id='tecnico_passaporte'  class='Caixa' value='t'><font size='1'><b> ".traduz("atencao.e.obrigatorio.o.participante.trazer.seu.passaporte.de.treinamento.makita.caso.ele.ainda.nao.tenha.ele.deve.trazer.uma.foto.3x4.para.cadastramento")."</b></font>";
                $resposta  .= "</td>";
                $resposta  .= "<td width='10'>&nbsp;</td>";
            $resposta  .= "</tr>";

            $resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
                $resposta  .= "<td width='10'>&nbsp;</td>";
                $resposta  .= "<td align='right' nowrap valign='top'width='156'>".traduz("o.participante.sofreu.ou.sofre.de.alguma.doenca.qual")." <img src='admin/imagens/help.png' name='img_help' id='img_help' class='img_help' title=' (".traduz('cardiaca.hipoertensiva.traumatismo.infecto.contagiosa.etc').")' onClick='javascript:img_info();'/></td>";
                $resposta  .= "<td align='left'colspan='3'>";
                $resposta  .= "<input type='text' name='tecnico_doenca' id='tecnico_doenca' size='65' maxlength='90' class='Caixa' value=''>";
                $resposta  .= "</td>";
                $resposta  .= "<td width='10'>&nbsp;</td>";
            $resposta  .= "</tr>";

            $resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
                $resposta  .= "<td width='10'>&nbsp;</td>";
                $resposta  .= "<td align='right' nowrap valign='top'width='156'> ".traduz("toma.algum.medicamento.controlado.qual")." </td>";
                $resposta  .= "<td align='left' colspan='3'>";
                $resposta  .= "<input type='text' name='tecnico_medicamento' id='tecnico_medicamento' size='65' maxlength='90' class='Caixa' value=''>";
                $resposta  .= "</td>";
                $resposta  .= "<td width='10'>&nbsp;</td>";
            $resposta  .= "</tr>";

            $resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
                $resposta  .= "<td width='10'>&nbsp;</td>";
                $resposta  .= "<td align='right' nowrap valign='top'width='156'> ".traduz("e.portador.de.alguma.necessidade.especial.qual")." </td>";
                $resposta  .= "<td align='left' colspan='3'>";
                $resposta  .= "<input type='text' name='tecnico_necessidade' id='tecnico_necessidade' size='65' maxlength='90' class='Caixa' value=''>";
                $resposta  .= "</td>";
                $resposta  .= "<td width='10'>&nbsp;</td>";
            $resposta  .= "</tr>";

        }

        if ($adicional && $login_fabrica != 148)
        {
            $resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
            $resposta  .= "<td width='10'>&nbsp;</td>";
            $resposta  .= "<td align='right' nowrap valign='top'>$adicional </td>";
            $resposta  .= "<td align='left'colspan='3'>";
            $resposta  .= "<input type='text' name='observacao' id='observacao' size='65' maxlength='200' class='Caixa' value=''>";
            $resposta  .= "</td>";
            $resposta  .= "<td width='10'>&nbsp;</td>";
            $resposta  .= "</tr>";
        }

        if ($login_fabrica == 20){
            $resposta  .="<input type='hidden' name='hotel' id='hotel' value='f'>";
        } elseif (!in_array($login_fabrica, array(1,117,138,148,169,170,193))) {
            $resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
            $resposta  .= "<td width='10'>&nbsp;</td>";
            $resposta  .= "<td align='right' nowrap valign='top'>".traduz("agendar.hotel")."?</td>";
            $resposta  .= "<td align='left' colspan='3'>";
            $resposta  .= "<input type='checkbox' name='hotel' id='hotel'  class='Caixa' value='t'>";
            $resposta  .= "</td>";
            $resposta  .= "<td width='10'>&nbsp;</td>";
            $resposta  .= "</tr>";
        }
            $resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
                $resposta  .= "<td width='10'>&nbsp;</td>";
                $resposta  .= "<td align='right' nowrap valign='top'>".traduz("email.do.posto")."</td>";
                $resposta  .= "<td align='left' colspan='3'>";
                $resposta  .= "<input type='text' name='posto_email' id='posto_email' size='30' maxlength='50' class='Caixa' value='$posto_email'> * <font size='1'><b>".traduz("este.email.e.o.email.do.posto.autorizado")."</b></font>";
                $resposta  .= "</td>";
                $resposta  .= "<td width='10'>&nbsp;</td>";
            $resposta  .= "</tr>";

            $resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
                $resposta  .= "<td width='10'>&nbsp;</td>";
                $resposta  .= "<td align='right' nowrap valign='top'>".traduz("politica.de.treinamento")."</td>";
                $resposta  .= "<td align='left' colspan='3'>";

                $rowsTermo = (in_array($login_fabrica, [148])) ? "12" : "7";

                $resposta  .= "<TEXTAREA name='compromisso' id='compromisso' ROWS='{$rowsTermo}' COLS='90' class='Caixa2' READONLY>$termo_compromisso</TEXTAREA>";
                $resposta  .= "</td>";
                $resposta  .= "<td width='10'>&nbsp;</td>";
            $resposta  .= "</tr>";
            if (in_array($login_fabrica, array(138))) {

                $resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
                    $resposta  .= "<td width='10'>&nbsp;</td>";
                    $resposta  .= "<td align='center' colspan='4'>";
                    $resposta  .= "<button class='btn btn-info btn-upload-tecnico' type='button'>".traduz("cadastro.de.tecnico.em.lote")."</button>";
                    $resposta  .= "</td>";
                    $resposta  .= "<td width='10'>&nbsp;</td>";
                $resposta  .= "</tr>";
            }
            $resposta  .= "</table><br>";


            if($login_fabrica <> 129){
                $resposta  .= "<center>";
                if($login_fabrica == $makita){
                    $resposta  .= "<INPUT TYPE='button' name='bt_cad_forn' id='bt_cad_forn' value='ACEITO' onClick=\"if (this.value=='Gravando...'){ alert('Aguarde');}else {this.value='Gravando...'; gravar_treinamento_makita(this.form);}\" class='Botao1'> ";
                }else{
                    $resposta  .= "<INPUT TYPE='button' name='bt_cad_forn' id='bt_cad_forn' value='ACEITO' onClick=\"if (this.value=='Gravando...'){ alert('Aguarde');}else {this.value='Gravando...'; gravar_treinamento(this.form);}\" class='Botao1'> ";
                }
                $resposta  .= "<INPUT TYPE='button' name='bt_cad_forn2' id='bt_cad_forn2' value='NÃO ACEITO' onClick='javascript:mostrar_treinamento(\"dados\");' class='Botao2'>";
                $resposta  .= "</center>";
            }elseif($atendeLinhaTreinamento == 0 ){
                $resposta .= "<div style='font-size:13px;font-weight:bold;text-align:center; border: #D3BE96 1px solid; background-color: #FCF0D8;'>";
                $resposta .= traduz("este.posto.nao.atende.a.linha.desse.treinamento");
                $resposta .= "</div>";
            }else{
                $resposta  .= "<center>";
                $resposta  .= "<INPUT TYPE='button' name='bt_cad_forn' id='bt_cad_forn' value='ACEITO' onClick=\"if (this.value=='Gravando...'){ alert('Aguarde');}else {this.value='Gravando...'; gravar_treinamento(this.form);}\" class='Botao1'> ";
                $resposta  .= "<INPUT TYPE='button' name='bt_cad_forn2' id='bt_cad_forn2' value='NÃO ACEITO' onClick='javascript:mostrar_treinamento(\"dados\");' class='Botao2'>";
                $resposta  .= "</center>";
            }

            $resposta  .= "</div>";

        }else{
            if ($dentro_do_prazo)
                $resposta  .= "<center><font size='4' color='#990000'>".traduz("todas.as.vagas.deste.treinamento.estao.preenchidas")."!</font><br><a href='javascript:mostrar_treinamento(\"dados\");'>".traduz("ver.agenda")."</a></center>";
            else
                if (in_array($login_fabrica, [169,170,193])) {
                    if ($_GET['isInfo'] == 't') {
                        $resposta  .= "<center><br><a href='javascript:mostrar_treinamento(\"dados\");'>".traduz("ver.agenda")."</a></center>";
                    } else {
                        $resposta  .= "<center><br /><br /><font size='4' color='#990000'>".traduz("fora.do.prazo.de.inscricao.ate")." $prazo_inscricao). ".traduz("contate.com.o.fabricante.para.esclarecimentos").".</font><br><a href='javascript:mostrar_treinamento(\"dados\");'>".traduz("ver.agenda")."</a></center>";
                    }
                } else {
                    $resposta  .= "<center><br /><br /><font size='4' color='#990000'>".traduz("fora.do.prazo.de.inscricao.ate")." $prazo_inscricao). ".traduz("contate.com.o.fabricante.para.esclarecimentos").".</font><br><a href='javascript:mostrar_treinamento(\"dados\");'>".traduz("ver.agenda")."</a></center>";
                }
        }
        $resposta  .= "</td>";
        $resposta  .= "</tr>";
        $resposta  .= "</table>";

        $resposta  .= "<script language='javascript'>mostrar_tecnico('$treinamento');</script>";

        $resposta  .= "</FORM>";

    }
    echo "ok|$resposta";
    exit;
}

if($_GET['ajax']=='sim' AND $_GET['acao']=='ativa_desativa_tecnico') {
	$treinamento_posto = filter_input(INPUT_GET,'treinamento_posto',FILTER_VALIDATE_INT);
	$id                = filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
	$motivo            = utf8_decode(filter_input(INPUT_GET,'motivo',FILTER_SANITIZE_MAGIC_QUOTES));
    // echo $motivo;exit;
	$sql = "SELECT  tbl_treinamento_posto.ativo       ,
                    tbl_tecnico.nome AS tecnico_nome,
                    tbl_posto.email
            FROM tbl_treinamento_posto
            JOIN tbl_tecnico USING (tecnico)
            JOIN tbl_posto ON tbl_posto.posto = tbl_treinamento_posto.posto
		WHERE treinamento_posto = $treinamento_posto";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$ativo        = trim(pg_result($res,0,ativo))       ;
		$email        = trim(pg_result($res,0,email))       ;
		$tecnico_nome = trim(pg_result($res,0,tecnico_nome));

		if($ativo == 't'){
			$x_ativo = 'f';
			$resposta = "<a href=\"javascript:ativa_desativa_tecnico('$treinamento_posto','$id')\">".traduz("cancelado")."</a>|vermelho";

			//--== Envio de email =========================================================================
			$sql=  "SELECT  titulo                            ,
					TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY') AS data_inicio,
					TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY')    AS data_fim
					FROM tbl_treinamento
					JOIN tbl_treinamento_posto USING(treinamento)
					 WHERE treinamento_posto = $treinamento_posto";
			$res = pg_exec ($con,$sql);

			if (pg_numrows($res) > 0) {
				$titulo      = pg_result($res,0,titulo)     ;
				$data_inicio = pg_result($res,0,data_inicio);
				$data_fim    = pg_result($res,0,data_fim)   ;
			}

			$email_origem  = "verificacao@telecontrol.com.br";
			$email_destino = "$email";
			$assunto       = "Incrição Cancelada em Treinamento";

			$corpo.= "Titulo: $titulo <br>\n";
			$corpo.= "Data Inicío: $data_inicio<br> \n";
			$corpo.= "Data Término: $data_fim <p>\n";

			$corpo.="<br>Desculpe-nos pelo transtorno, mas estamos cancelando o treinamento conforme agendado!\nPor favor fazer a inscrição do mesmo em uma outra data, conforme disponível no sistema Telecontrol..\n\n";
			$corpo.="<br>Nome: $tecnico_nome \n";
			$corpo.="<br>Email: $email\n\n";
			$corpo.="<br>Motivo do cancelamento: $motivo\n\n";
			$corpo.="<br><br><br>Telecontrol\n";
			$corpo.="<br>www.telecontrol.com.br\n";
			$corpo.="<br>_______________________________________________\n";
			$corpo.="<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";


			$body_top = "MIME-Version: 1.0\r\n";
			$body_top .= "Content-type: text/html; charset=iso-8859-1\r\n";
			$body_top .= "From: $email_origem\r\n";

			if ( @mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), $body_top ) ){
				$msg = "$email";
			}else{
				$msg_erro = traduz("nao.foi.possivel.enviar.o.email.por.favor.entre.em.contato.com.a.telecontrol");

			}

		}else{
			$x_ativo = 't';
			$motivo = '';
			$resposta = "<a href=\"javascript:ativa_desativa_tecnico('$treinamento_posto','$id')\">".traduz("confirmado")."</a>|verde";
		}
		$sql = "UPDATE tbl_treinamento_posto SET ativo = '$x_ativo', motivo_cancelamento = '$motivo' WHERE treinamento_posto = $treinamento_posto";
		$res = pg_exec ($con,$sql);
	}
	echo "ok|".$resposta;
	exit;
}

// --==== Confirmar a presença do tecnico ============
if($_GET['ajax']=='sim' AND $_GET['acao']=='confirma_presenca_tecnico') {
    $treinamento_posto_id = filter_input(INPUT_GET,'treinamento_posto_id',FILTER_VALIDATE_INT);

    //Verificar se o treinamento_posto é valido
    $sql_tp = "SELECT treinamento_posto FROM tbl_treinamento_posto WHERE treinamento_posto = {$treinamento_posto_id};";
    $res_tp = pg_query($con,$sql_tp);

    if (pg_num_rows($res_tp) == 1) {
        pg_query($con, "BEGIN TRANSACTION");

        $sql_tp = "UPDATE tbl_treinamento_posto set confirma_inscricao = 't'  where tbl_treinamento_posto.treinamento_posto in ({$treinamento_posto_id});";
        $res_tp = pg_query($con,$sql_tp);

        if (pg_last_error($con)) {
            pg_query($con, "ROLLBACK TRANSACTION");
            $resposta = traduz("o.correu.um.erro.na.confirmacao.da.presenca.do.tecnico");
        } else {
            pg_query($con, "COMMIT TRANSACTION");
            $resposta = traduz("tecnico.confirmado");
        }
    }

    echo "ok|$resposta";
    exit;

}

$layout_menu = "tecnica";
$title = traduz("treinamento");

include "cabecalho.php";

?>
<style type="text/css">
.Titulo {
    text-align: center;
    font-family: Verdana;
    font-size: 14px;
    font-weight: bold;
    color: #FFFFFF;
    background-color: #485989;
}
.Titulo2 {
    text-align: center;
    font-family: Verdana;
    font-size: 12px;
    font-weight: bold;
    color: #FFFFFF;
    background-color: #485989;
}
.Conteudo {
    font-family: Arial;
    font-size: 8pt;
    font-weight: normal;
}
.Conteudo2 {
    font-family: Arial;
    font-size: 10pt;
}

.Caixa{
    BORDER-RIGHT: #6699CC 1px solid;
    BORDER-TOP: #6699CC 1px solid;
    FONT: 8pt Arial ;
    BORDER-LEFT: #6699CC 1px solid;
    BORDER-BOTTOM: #6699CC 1px solid;
    BACKGROUND-COLOR: #FFFFFF;
}
.Caixa2{
    BORDER-RIGHT: #6699CC 1px solid;
    BORDER-TOP: #6699CC 1px solid;
    FONT: 7pt Arial ;
    BORDER-LEFT: #6699CC 1px solid;
    BORDER-BOTTOM: #6699CC 1px solid;
    BACKGROUND-COLOR: #FFFFFF;
}
.Botao1{
    BORDER-RIGHT:  #6699CC 1px solid;
    BORDER-TOP:    #6699CC 1px solid;
    BORDER-LEFT:   #6699CC 1px solid;
    BORDER-BOTTOM: #6699CC 1px solid;
    FONT:             10pt Arial ;
    FONT-WEIGHT:      bold;
    COLOR:            #009900;
    BACKGROUND-COLOR: #EEEEEE;
}
.Botao2{
    BORDER-RIGHT:  #6699CC 1px solid;
    BORDER-TOP:    #6699CC 1px solid;
    BORDER-LEFT:   #6699CC 1px solid;
    BORDER-BOTTOM: #6699CC 1px solid;
    FONT:             10pt Arial;
    FONT-WEIGHT:      bold;
    COLOR:            #990000;
    BACKGROUND-COLOR: #EEEEEE;
}
.Erro{
    BORDER-RIGHT: #990000 1px solid;
    BORDER-TOP: #990000 1px solid;
    FONT: 10pt Arial ;
    COLOR: #ffffff;
    BORDER-LEFT: #990000 1px solid;
    BORDER-BOTTOM: #990000 1px solid;
    BACKGROUND-COLOR: #FF0000;
}

.banner_makita{
    width: 200px;
    height: auto;
    position: absolute;
    top: 130px;
    right: 10px;
}

.btn-inscreva-tecnico {
    BORDER: #bd362f 1px solid;
    FONT: 10pt Arial;
    FONT-WEIGHT: bold;
    COLOR: #ffffff;
    BACKGROUND-COLOR: #da4f49;
    cursor: pointer;
    padding: 10px;
}
.btn-inscreva-tecnico:hover {
    BORDER: #da4f49 1px solid;
    FONT: 10pt Arial;
    FONT-WEIGHT: bold;
    COLOR: #ffffff;
    BACKGROUND-COLOR: #bd362f;
    cursor: pointer;
}
.btn-upload-tecnico {
    BORDER: #2f96b4 1px solid;
    FONT: 10pt Arial;
    FONT-WEIGHT: bold;
    COLOR: #ffffff;
    BACKGROUND-COLOR: #49afcd;
    cursor: pointer;
    padding: 10px;
}
.btn-upload-tecnico:hover {
    BORDER: #49afcd 1px solid;
    FONT: 10pt Arial;
    FONT-WEIGHT: bold;
    COLOR: #ffffff;
    BACKGROUND-COLOR: #2f96b4;
    cursor: pointer;
}
</style>

<script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src='plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js'></script>
<script src='plugins/shadowbox_lupa/shadowbox.js'></script>
<link rel='stylesheet' type='text/css' href='plugins/shadowbox_lupa/shadowbox.css' />
<?
include "javascript_calendario_new.php";
include "js/js_css.php";
?>
<script type="text/javascript">

$(function() {

    Shadowbox.init();
    $(document).on('click', '.btn-upload-tecnico', function(){
        var  login_fabrica = '<?php echo $login_fabrica;?>';
        Shadowbox.open({
            content: "upload_tecnico.php?treinamento="+$("#treinamento").val()+"&login_fabrica="+login_fabrica,
            player: "iframe",
            width:  1024,
            height: 300
        });

    });
    <?php if ((in_array($login_fabrica, array(160)) or $replica_einhell)) { ?>
        $('#tecnico_fone').mask('(99) 99999-9999');
        $(document).on('blur', '#tecnico_fone', function(){
            if($(this).val().length > 14){
              $('#tecnico_fone').mask('(99) 99999-9999');
            } else {
              $('#tecnico_fone').mask('(99) 9999-99999');
            }
        });

        $(document).on('blur', '#tecnico_rg', function(){
            $('#tecnico_rg').mask('99.999.999-9');
        });

        $(document).on('blur', '#tecnico_cpf', function(){
            $('#tecnico_cpf').mask('999.999.999-99');
        });
    <?php } ?>

    <?php if (in_array($login_fabrica, array(169,170,193))) { ?>
        $(document).on('click', '.btn-ver-infos', function(){
            var login_fabrica = '<?php echo $login_fabrica;?>';
            var treinamento   = $(this).data('treinamento');
            treinamento_formulario(treinamento,'t');
        });
    <?php } ?>
});

function img_info(){
    alert("<?php echo traduz("cardiaca.hipertensiva.traumatismo.infecto.contagiosa.etc");?>");
}

function createRequestObject(){
    var request_;
    var browser = navigator.appName;
    if(browser == "Microsoft Internet Explorer"){
         request_ = new ActiveXObject("Microsoft.XMLHTTP");
    }else{
         request_ = new XMLHttpRequest();
    }
    return request_;
}

var http_forn = new Array();

//FUNÇÃO USADA PARA ATUALISAR, INSERIR E ALTERAR
function gravar_treinamento(formulario) {
    var acao='cadastrar';/* INICIO HD 3331 RAPHAEL/IGOR- SEGUNDO RAPHAEL, ERA SOH COPIAR O QUE ESTAVA NO ARQUIVO DO POSTO E COLAR AQUI */

    url = "treinamento_agenda.php?ajax=sim&acao="+acao;
    //console.log(formulario);
    // var e = document.getElementById("tecnico_nome");
    // var itemSelecionado = e.options[e.selectedIndex].value;
    // alert(itemSelecionado);

    for( var i = 0 ; i < formulario.length; i++ ){
        /* INICIO HD 3331 RAPHAEL/IGOR- SEGUNDO RAPHAEL, ERA SOH COPIAR O QUE ESTAVA NO ARQUIVO DO POSTO E COLAR AQUI */
        if (formulario.elements[i].type !='button'){
            if(formulario.elements[i].type=='radio' || formulario.elements[i].type=='checkbox'){
                if(formulario.elements[i].checked == true){
                    url = url+"&"+formulario.elements[i].name+"="+escape(formulario.elements[i].value);
                }
            }else{
                url = url+"&"+formulario.elements[i].name+"="+escape(formulario.elements[i].value);
            }
        }
        /* INICIO HD 3331 RAPHAEL/IGOR- SEGUNDO RAPHAEL, ERA SOH COPIAR O QUE ESTAVA NO ARQUIVO DO POSTO E COLAR AQUI */

    }

    var com = document.getElementById('erro');
    var com2 = document.getElementById('cadastro');

    var curDateTime = new Date();
    http_forn[curDateTime] = createRequestObject();
    http_forn[curDateTime].open('GET',url,true);
    http_forn[curDateTime].onreadystatechange = function(){
        if (http_forn[curDateTime].readyState == 4)
        {
            if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
            {
                var response = http_forn[curDateTime].responseText.split("|");
                if (response[0]=="ok"){

                    formulario.bt_cad_forn.value='ACEITO';
                    com2.innerHTML = response[1];

                    for( var i = 0 ; i < formulario.length; i++ ){
                        if (formulario.elements[i].type=='text'){
                            formulario.elements[i].value = "";
                        }
                        if (formulario.elements[i].type=='hidden'){
                            //chamar aki se for black o confirma presenca do tecnico
                            <?php
                            if (in_array($login_fabrica, [1])) { ?>
                                confirma_presenca_tecnico(response[2],formulario.elements[i].value);
                            <?php
                            } else { ?>
                                mostrar_tecnico(formulario.elements[i].value);
                            <?php
                            } ?>

                        }
                    }

                    com.style.visibility = "hidden";


                }
                if (response[0]=="0"){
                    // posto ja cadastrado
                    com.innerHTML = response[1];
                    com.style.visibility = "visible";
                    formulario.bt_cad_forn.value='ACEITO';
                }
                if (response[0]=="1"){
                    // dados incompletos
                    com.innerHTML = response[1];
                    com.style.visibility = "visible";
                    formulario.bt_cad_forn.value='ACEITO';
                }
                if (response[0]=="2"){
                    // erro inesperado
                    com.innerHTML = response[1];
                    com.style.visibility = "visible";
                    formulario.bt_cad_forn.value='ACEITO';
                }
            }
        }
    }
    http_forn[curDateTime].send(null);
}

//FUNÇÃO PARA GRAVAR DADOS MAKITA
function gravar_treinamento_makita(formulario) {
    //  ref = trim(ref);
    var acao='cadastrar';

    url = "treinamento_agenda.php?ajax=sim&acao="+acao;
    for( var i = 0 ; i < formulario.length; i++ ){
        if (formulario.elements[i].type !='button'){
            if(formulario.elements[i].type=='radio' || formulario.elements[i].type=='checkbox'){
                if(formulario.elements[i].checked == true){
                    url = url+"&"+formulario.elements[i].name+"="+escape(formulario.elements[i].value);
                }
            }

            if(formulario.elements[i].type=='select-one'){
                url = url+"&"+formulario.elements[i].name+"="+escape(formulario.elements[i].value);
            }
            else{
                url = url+"&"+formulario.elements[i].name+"="+escape(formulario.elements[i].value);
            }
        }
    }

    var com = document.getElementById('erro');
    var com2 = document.getElementById('cadastro');

    var curDateTime = new Date();
    http_forn[curDateTime] = createRequestObject();
    http_forn[curDateTime].open('GET',url,true);
    http_forn[curDateTime].onreadystatechange = function(){
        if (http_forn[curDateTime].readyState == 4)
        {
            if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
            {
                var response = http_forn[curDateTime].responseText.split("|");
                if (response[0]=="ok"){

                    formulario.bt_cad_forn.value='ACEITO';
                    com2.innerHTML = response[1];

                    for( var i = 0 ; i < formulario.length; i++ ){
                        if (formulario.elements[i].type=='text'){
                            formulario.elements[i].value = "";
                        }
                        if (formulario.elements[i].type=='hidden'){
                            mostrar_tecnico(formulario.elements[i].value);
                        }
                    }

                    com.style.visibility = "hidden";


                }
                if (response[0]=="0"){
                    // posto ja cadastrado
                    com.innerHTML = response[1];
                    com.style.visibility = "visible";
                    formulario.bt_cad_forn.value='ACEITO';
                }
                if (response[0]=="1"){
                    // dados incompletos
                    com.innerHTML = response[1];
                    com.style.visibility = "visible";
                    formulario.bt_cad_forn.value='ACEITO';
                }
                if (response[0]=="2"){
                    // erro inesperado
                    com.innerHTML = response[1];
                    com.style.visibility = "visible";
                    formulario.bt_cad_forn.value='ACEITO';
                }
            }
        }
    }
    http_forn[curDateTime].send(null);
}


function mostrar_treinamento(componente) {

    var com = document.getElementById(componente);
    var acao='ver';

    url = "treinamento_agenda.php?ajax=sim&acao="+acao;

    com.innerHTML   ="Carregando<br><img src='imagens/carregar2.gif'>";

    var curDateTime = new Date();
    http_forn[curDateTime] = createRequestObject();
    http_forn[curDateTime].open('GET',url,true);

    http_forn[curDateTime].onreadystatechange = function(){
        if (http_forn[curDateTime].readyState == 4)
        {
            if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
            {
                var response = http_forn[curDateTime].responseText.split("|");
                if (response[0]=="ok"){
                    com.innerHTML   = response[1];
                }
                if (response[0]=="0"){
                    // posto ja cadastrado
                    alert(response[1]);
                }
                if (response[0]=="1"){
                    // dados incompletos
                    alert("<?php echo traduz("campos.incompletos");?>:\n\n"+response[1]);
                }
            }
        }
    }
    http_forn[curDateTime].send(null);
}

<?php
if (in_array($login_fabrica, [1])) { ?>
    function confirma_presenca_tecnico (treinamento_posto_id,formulario_element) {

        var confirma = confirm('<?php echo traduz("confirmar.a.presenca.do.tecnico.para.o.treinamento");?>?');

        if (confirma == true) {

            var acao='confirma_presenca_tecnico';
            url = "<?=$PHP_SELF?>?ajax=sim&acao="+acao+"&treinamento_posto_id="+treinamento_posto_id;

            var curDateTime = new Date();
            http_forn[curDateTime] = createRequestObject();
            http_forn[curDateTime].open('GET',url,true);

            http_forn[curDateTime].onreadystatechange = function(){
                if (http_forn[curDateTime].readyState == 4)
                {
                    if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
                    {
                        var response = http_forn[curDateTime].responseText.split("|");
                        if (response[0]=="ok"){
                            mostrar_tecnico(formulario_element);
                        }
                    }
                }
            }
            http_forn[curDateTime].send(null);
        } else {
            mostrar_tecnico(formulario_element);
        }
    }

    //Notificação
    $(document).on('click','button.envia-notificao', function(){
        var btn = $(this);
        var text = $(this).text();
        var treinamento = $(btn).data('url');

        var url = $(this).data('url');
        Shadowbox.open({
            content: url,
            player: 'iframe',
            width: 1024,
            height: 600
        });
    });
<?php
} ?>

function ativa_desativa_tecnico(treinamento,id) {

	var com = document.getElementById("tec_ativo_"+id);
	var img = document.getElementById("tec_img_ativo_"+id);

	com.innerHTML   ="Espere...";

	var acao='ativa_desativa_tecnico';
    var motivo = prompt("Diga o motivo do cancelamento");
	url = "<?=$PHP_SELF?>?ajax=sim&acao="+acao+"&treinamento_posto="+treinamento+"&id="+id+"&motivo="+motivo;

	var curDateTime = new Date();
	http_forn[curDateTime] = createRequestObject();
	http_forn[curDateTime].open('GET',url,true);

	http_forn[curDateTime].onreadystatechange = function(){
		if (http_forn[curDateTime].readyState == 4)
		{
			if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
			{
				var response = http_forn[curDateTime].responseText.split("|");
				if (response[0]=="ok"){
					com.innerHTML   = response[1];
					img.src = "admin/imagens_admin/status_"+response[2]+".gif";

				}
			}
		}
	}
	http_forn[curDateTime].send(null);
}

function mostrar_tecnico(treinamento) {

    var acao='tecnico';

    url = "treinamento_agenda.php?ajax=sim&acao="+acao+"&treinamento="+treinamento;

    var com = document.getElementById('tecnico');
    com.innerHTML   ="Carregando<br><img src='imagens/carregar2.gif'>";

    var curDateTime = new Date();
    http_forn[curDateTime] = createRequestObject();
    http_forn[curDateTime].open('GET',url,true);

    http_forn[curDateTime].onreadystatechange = function(){
        if (http_forn[curDateTime].readyState == 4)
        {
            if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
            {
                var response = http_forn[curDateTime].responseText.split("|");
                if (response[0]=="ok"){
                    com.innerHTML   = response[1];

                    <?php
                    if(in_array($login_fabrica, array(1,117))){
                        ?>
                        $("#tecnico_cpf").mask("999-999-999-99");
                        $("#tecnico_rg").mask("99.999.999-x");
                        <?php
                    }
                    ?>
                }
            }
        }
    }
    http_forn[curDateTime].send(null);
}

function treinamento_formulario(treinamento,isInfo='f') {
    var acao='formulario';

    <?php if (in_array($login_fabrica, [169,170,193])) { ?>
            url = "treinamento_agenda.php?ajax=sim&acao="+acao+"&treinamento="+treinamento+"&isInfo="+isInfo;
    <?php } else { ?>
            url = "treinamento_agenda.php?ajax=sim&acao="+acao+"&treinamento="+treinamento;
    <?php } ?> 

    var com = document.getElementById('dados');
    com.innerHTML   ="Carregando<br><img src='imagens/carregar2.gif'>";

    var curDateTime = new Date();
    http_forn[curDateTime] = createRequestObject();
    http_forn[curDateTime].open('GET',url,true);

    http_forn[curDateTime].onreadystatechange = function(){
        if (http_forn[curDateTime].readyState == 4)
        {
            if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
            {
                var response = http_forn[curDateTime].responseText.split("|");
                if (response[0]=="ok"){
                    com.innerHTML   = response[1];
                    <?php
                    if($login_fabrica != 117){
                    ?>
                    $("#tecnico_data_nascimento").datepick({startdate:'01/01/2000'});
                    <?php
                    }
                    ?>

                    $("#tecnico_data_nascimento").mask("99/99/9999");
                    $("#tecnico_fone").mask("(99)99999-9999");
                    $("#tecnico_celular").mask("(99)99999-9999");
                    mostrar_tecnico(treinamento);
                }
            }
        }
    }
    http_forn[curDateTime].send(null);
}

</script>
<? include "javascript_pesquisas.php" ?>

<?php
    if($login_fabrica == 42){?>
<!-- <iframe width="150" height="450" src="http://www.makita.com.br/restrito/autorizadas/iadm/banner_telecontrol/banner.swf " frameborder="no" style='position:absolute;left:82%'>
</iframe> -->
    <div>
        <a align="center" href="https://plus.google.com/111700869584353735711/posts" target="_blank"><img class='banner_makita' src="imagens/makita_treinamento_banner.jpg"></a>
    </div>

<? } ?>

<?
echo "<div id='dados'></div>";
echo "<script language='javascript'>mostrar_treinamento('dados');</script>";

    if($login_fabrica == 42){
        $banner_rodape = true;
    }
    if(in_array($login_fabrica, array(169,170,193))){
        ?>
        <br>
        <br>
        <br>
        <a class='btn' href='treinamentos_finalizados.php'><?php echo traduz("visualizar.treinamentos.finalizados");?></a>&nbsp;&nbsp;
        <a class='btn' href='treinamentos_cancelados.php'><?= traduz('Visualizar Treinamentos Cancelados') ?></a> &nbsp;&nbsp;

        <?php
    }
?>
<?
include "rodape.php"

?>
