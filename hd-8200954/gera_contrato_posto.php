<?php

function linhasPosto($con,$login_posto,$login_fabrica)
{
    $sql_linhas_postos = "
        SELECT  tbl_linha.nome
        FROM    tbl_posto_linha
        JOIN    tbl_linha   ON  tbl_linha.linha     = tbl_posto_linha.linha
                            AND tbl_linha.fabrica   = {$login_fabrica}
        WHERE   tbl_posto_linha.posto = {$login_posto}
        AND     tbl_linha.ativo         IS TRUE
        AND     tbl_posto_linha.ativo   IS TRUE";
    $res_linhas_postos = pg_query($con, $sql_linhas_postos);

    if(pg_num_rows($res_linhas_postos) > 0){

        $linhas_postos_arr = array();

        for ($i = 0; $i < pg_num_rows($res_linhas_postos); $i++) {

            $linhas_postos_arr[] = pg_fetch_result($res_linhas_postos, $i, "nome");

        }

        $qtde_linhas = count($linhas_postos_arr);

        if ($qtde_linhas == 1) {

            $linhas_posto = $linhas_postos_arr[0];

        } else {

            $ultima_linha = $linhas_postos_arr[$qtde_linhas - 1];
            unset($linhas_postos_arr[$qtde_linhas - 1]);

            $linhas_posto = implode(", ", $linhas_postos_arr);
            $linhas_posto .= " e ".$ultima_linha;

        }
    }

    return $linhas_posto;
}

function formataDataHoje()
{
    $hoje = new DateTime();
    $mes = "";

    switch ($hoje->format('m')) {
        case '01':
            $mes = " de Janeiro de ";
            break;
        case '02':
            $mes = " de Fevereiro de ";
            break;
        case '03':
            $mes = " de Mar�o de ";
            break;
        case '04':
            $mes = " de Abril de ";
            break;
        case '05':
            $mes = " de Maio de ";
            break;
        case '06':
            $mes = " de Junho de ";
            break;
        case '07':
            $mes = " de Julho de ";
            break;
        case '08':
            $mes = " de Agosto de ";
            break;
        case '09':
            $mes = " de Setembro de ";
            break;
        case '10':
            $mes = " de Outubro de ";
            break;
        case '11':
            $mes = " de Novembro de ";
            break;
        case '12':
            $mes = " de Dezembro de ";
            break;
    }

    return $hoje->format("d").$mes.$hoje->format("Y");
}

function gerarCabecalho()
{

    $cab = "
        <div class='cabecalho-contrato'>
            <div class='logo-contrato'>
                <img src='logos/logo_black_2016.png' style='width: 65%; padding-top: 4px;' />
            </div>
            <div class='info-contrato' style='font-family: arial, verdana; font-size: 8x !important;'>
                Black & Decker do Brasil Ltda. <br />
                Rod. BR 050 - Km 167 - Lote 05 Parte - Quadra 01 <br />
                Distrito Industrial II <br />
                38.064-750 - Uberaba - MG
            </div>
        </div>
    ";

    return $cab;
}

function gerarRodape($posto_nome,$posto_categoria)
{
    switch ($posto_categoria) {
        
        case "Locadora":
            $rod = "
                    <div style='text-align: center; font-family: arial, verdana; font-size: 8px !important;'>
                        INSTRUMENTO PARTICULAR DE ACORDO RENTAL CELEBRADO ENTRE BLACK & DECKER DO BRASIL LTDA., E <strong>{$posto_nome}</strong>.
                        <br />
                        <strong> P�gina {PAGENO} de 3 </strong>
                    </div>
                ";
            break;
        case "Autorizada":
            $rod = "
                    <div style='text-align: center; font-family: arial, verdana; font-size: 8px !important;'>
                        INSTRUMENTO PARTICULAR DE ACORDO DE PRESTA��O DE SERVI�OS CELEBRADO ENTRE BLACK & DECKER DO BRASIL LTDA., E <strong>{$posto_nome}</strong>.
                        <br />
                        <strong> P�gina {PAGENO} de 3 </strong>
                    </div>
                ";
            break;
        case "Compra Peca":
            $rod = "
                    <div style='text-align: center; font-family: arial, verdana; font-size: 8px !important;'>
                        INSTRUMENTO PARTICULAR DE ACORDO DE COMPRA DE PE�AS CELEBRADO ENTRE BLACK & DECKER DO BRASIL LTDA., E <strong>{$posto_nome}</strong>.
                        <br />
                        <strong> P�gina {PAGENO} de 3 </strong>
                    </div>
                ";
            break;
        case "mega projeto":
            $rod = "
                    <div style='text-align: center; font-family: arial, verdana; font-size: 8px !important;'>
                        INSTRUMENTO PARTICULAR DE ACORDO DE INDUSTRIA/MEGA PROJETO CELEBRADO ENTRE BLACK & DECKER DO BRASIL LTDA., E <strong>{$posto_nome}</strong>.
                        <br />
                        <strong> P�gina {PAGENO} de 3 </strong>
                    </div>
                ";
            break;        
    }

    return $rod;
}

function gerarCorpo($posto_codigo,$posto_nome,$posto_categoria,$posto_endereco_completo,$posto_cnpj,$con,$login_posto,$login_fabrica)
{
    $dataHoje       = formataDataHoje();
    $posto_linhas  = linhasPosto($con,$login_posto,$login_fabrica);

// echo "->".$posto_linhas;
    switch ($posto_categoria) {
        case "Locadora":
            $conteudo = "
                <!DOCTYPE html>
                <head>
                <style>
                    .corpo-contrato{
                        font-family: arial, verdana;
                        font-size: 9px !important;
                        color: #444;
                        width: 100%;
                        background-color: #fff;
                        text-align: justify;
                    }
                    .cabecalho-contrato, .rodape-contrato{
                        width: 100%;
                    }
                    .logo-contrato, .info-contrato, .assinaturas, .testemunhas{
                        width: 45%;
                        float: left;
                        padding-top: 10px;
                        padding-bottom: 10px;
                    }
                    .logo-contrato, assinaturas{
                        padding-right: 15px;
                    }
                    .info-contrato, .testemunhas{
                        padding-left: 30px;
                    }
                    .logo-contrato{
                        text-align: center;
                    }
                    .logo-contrato img{
                        width: 350px;
                    }
                    .info-contrato{
                        border-left: 1px solid #e74c3c;
                        padding-left: 20px;
                    }
                    .conteudo-contrato h3{
                        text-align: center;
                        font-size: 12px !important;
                    }
                    .conteudo-contrato strong{
                        text-transform: uppercase;
                    }
                    .escpaco-clausula{
                        padding-left: 30px;
                    }
                    .escpaco-clausula-2{
                        padding-left: 60px;
                    }
                    .text-upper{
                        text-transform: uppercase;
                    }
                    .rodape-contrato hr{
                        margin-left: 0;
                        width: 80%;
                        margin-top: 20px;
                    }
                    .corLink {
                        color:#00F;
                    }
                </style>

                </head>

                <body>

                    <div class='corpo-contrato'>

                        <div class='conteudo-contrato'>

                            <h3>
                                INSTRUMENTO PARTICULAR DE ACORDO RENTAL CELEBRADO ENTRE BLACK & DECKER DO BRASIL LTDA., E {$posto_nome}
                            </h3>

                            <p>
                                Pelo presente instrumento particular de um lado <strong>BLACK & DECKER DO BRASIL LTDA.</strong>, pessoa jur�dica de direito privado, com sede � Rodovia BR 050, s/n�, KM 167, Lote 05 Parte, Quadra 01, Distrito Industrial II, Uberaba - MG, inscrita no CNPJ/MF sob o n�. 53.296.273/0001-91, representada por seus diretores, ao final assinados, e doravante denominada simplesmente <strong>BLACK & DECKER</strong>, e de outro lado <strong>{$posto_nome}</strong> com sede {$posto_endereco_completo}, inscrita no CNPJ/MF sob o n� {$posto_cnpj}, neste ato representada na forma de seu Contrato Social, denominada simplesmente denominada simplesmente <strong>LOCADORA</strong>, firmam o presente acordo <em>\"DeWALT Rental\"</em>, regulado pelas cl�usulas seguintes:
                            </p>

                            <p>
                                <strong>CL�USULA PRIMEIRA - DO OBJETO</strong>
                            </p>

                            <p>
                                1.1 - As locadoras que adquirirem Ferramentas DEWALT direto da <strong>BLACK & DECKER</strong>, e ao realizar o cadastro junto � mesma, ter�o acesso ao sistema Telecontrol, com endere�o eletr�nico <u class='corLink'>www.telecontrol.com.br</u>, para que efetuem compras de pe�as.
                            </p>

                            <p>
                                <strong>CL�USULA SEGUNDA - DO ACESSO</strong>
                            </p>

                            <p>
                                2.1 - A <strong>BLACK & DECKER</strong> criar� para a <strong>LOCADORA</strong>, um c�digo de cadastro.
                            </p>

                            <p class='escpaco-clausula'>
                                2.1.1 - O <em>\"login\"</em> de acesso ao sistema da <strong>BLACK & DECKER</strong> ser� criado assim que a <strong>LOCADORA</strong> enviar o presente instrumento devidamente assinado.
                            </p>

                            <p class='escpaco-clausula'>
                                2.1.2 - Para concluir o acesso a <strong>LOCADORA</strong> dever� entrar no site <u class='corLink'>www.telecontrol.com.br</u>, onde encontrar� as vistas explodidas dos produtos, tela para pedidos de pe�as, acesso � tabela de pre�os, informa��es t�cnicas e relat�rios gerenciais.
                            </p>

                            <p>
                                <strong>CL�USULA TERCEIRA - DAS OBRIGA��ES E RESPONSABILIDADES DA LOCADORA</strong>
                            </p>

                            <p>
                                3.1 Para o bom desempenho de vossas atividades, a <strong>LOCADORA</strong> dever�:
                            </p>

                            <p class='escpaco-clausula'>
                                3.1.1 - Manter estoque de pe�as de reposi��o de maior giro da <strong>BLACK & DECKER</strong>, que ser�o utilizadas para aplica��o nos consertos de seus produtos;
                            </p>

                            <p class='escpaco-clausula'>
                                3.1.2 - Possuir o equipamento m�nimo necess�rio para conserto, bem como as ferramentas b�sicas para aplica��o nos consertos de seus produtos;
                            </p>

                            <p class='escpaco-clausula'>
                                3.1.3 - Possuir o equipamento m�nimo necess�rio para conserto, bem como as ferramentas b�sicas para reparo, objetivando garantir servi�o r�pido e eficiente, al�m de garantir assim o bom reparo das ferramentas;
                            </p>

                            <p class='escpaco-clausula'>
                                3.1.4 - Participar, sempre que convocado, de cursos de treinamento organizados ou ministrados pela <strong>BLACK & DECKER</strong>;
                            </p>
                            <p class='escpaco-clausula'>
                                3.1.5 - Manter contato com o atendimento f�brica da <strong>BLACK & DECKER</strong> em caso de d�vidas, para que esta possa prestar o devido auxilio;
                            </p>
                            <p class='escpaco-clausula'>
                                3.1.6 - Saldar em dia seus compromissos financeiros com a <strong>BLACK & DECKER</strong>;
                            </p>
                            <p class='escpaco-clausula'>
                                3.1.7 - Autorizar inspe��es e auditoria, efetuadas por pessoas indicadas pela <strong>BLACK & DECKER</strong>;
                            </p>
                            <p class='escpaco-clausula'>
                                3.1.8 - Possuir organiza��o Administrativa e fiscal condizente com o volume do neg�cio a ser desenvolvido;
                            </p>
                            <p class='escpaco-clausula'>
                                3.1.9 - Comunicar imediatamente ao setor de cadastro da Black e Decker as altera��es tais como:
                            </p>

                            <p class='escpaco-clausula-2'>
                                a. Mudan�a de Raz�o Social ou quaisquer altera��es societ�rias, incluindo mas n�o se limitando ao Contrato Social;
                            </p>
                            <p class='escpaco-clausula-2'>
                                b. Altera��o de Inscri��o Estadual e/ou CNPJ;
                            </p>
                            <p class='escpaco-clausula-2'>
                                c. Mudan�as de endere�o / telefone / contato / e-mail;
                            </p>
                            <p class='escpaco-clausula-2'>
                                d. Abertura de filiais;
                            </p>
                            <p class='escpaco-clausula-2'>
                                e. Altera��o de Capital Social;
                            </p>
                            <p class='escpaco-clausula-2'>
                                f. Altera��o de Dados Banc�rios;
                            </p>

                            <p class='escpaco-clausula'>
                                3.1.10 - A <strong>LOCADORA</strong> n�o poder� incluir nos materiais promocionais fornecidos pela <strong>BLACK & DECKER</strong> quaisquer outras inscri��es, mesmo que n�o signifiquem concorr�ncia, direta ou indireta, aos produtos da <strong>BLACK & DECKER</strong>;
                            </p>
                            <p class='escpaco-clausula'>
                                3.1.11 - A <strong>LOCADORA</strong> responsabiliza-se por todos os encargos fiscais e tribut�rios federais, estaduais e municipais, decorrentes da sua atividade, inclusive, mas n�o se limitando a encargos trabalhistas, emolumentos e taxas relativas ao munic�pio incidentes sobre placas e propagandas.
                            </p>
                            <p class='escpaco-clausula'>
                                3.1.12 - A <strong>BLACK & DECKER</strong> n�o se responsabilizar� por problemas ocasionados pelo n�o cumprimento pela <strong>LOCADORA</strong>, de obriga��es, tais como:
                            </p>
                            <p class='escpaco-clausula-2'>
                                a. Produtos parados em sua locadora, ocasionados pela morosidade na coloca��o de pedidos de pe�as ou pedidos inseridos erroneamente junto ao sistema disponibilizado pela <strong>BLACK & DECKER</strong>;
                            </p>
                            <p class='escpaco-clausula-2'>
                                b. Reparos executados sem qualidade e/ou com neglig�ncia;
                            </p>
                            <p>
                                <strong>CL�USULA QUARTA - DA GARANTIA E SUAS CONDI��ES</strong>
                            </p>

                            <p>
                                4.1 - A <strong>LOCADORA</strong> tem como diferencial o pre�o <em>\"VIP DeWALT\"</em> para compras de pe�as com desconto diferenciado de sobre a tabela de pre�o sugerido pela <strong>BLACK & DECKER</strong>. O site <u class='corLink'>www.telecontrol.com.br</u>, apresentar� sempre os pre�os atualizados quando da inser��o do pedido de pe�as por parte da <strong>LOCADORA</strong>. Recomenda-se que a <strong>LOCADORA</strong> tenha um estoque de seguran�a de itens de maior giro.
                            </p>

                            <p>
                                4.2 - A <strong>BLACK & DECKER</strong> reserva-se o direito de alterar, suspender ou cancelar quaisquer itens dispon�veis, a qualquer tempo e ao seu exclusivo crit�rio, sem que disto resulte em qualquer reclama��o e/ou indeniza��o � <strong>LOCADORA</strong>.
                            </p>

                            <p>
                                4.3 - A <strong>BLACK & DECKER</strong> poder� a seu exclusivo crit�rio e com pr�vio agendamento, disponibilizar � <strong>LOCADORA</strong> material de divulga��o (cat�logos e banners), apoio da equipe de promo��o t�cnica para treinamento dos seus funcion�rios de modo a estarem aptos a prestar esclarecimentos sobre utiliza��o dos produtos voltados para loca��o, e treinamento t�cnico para manuten��o dos equipamentos adquiridos.
                            </p>

                            <p>
                                4.4 - O prazo de garantia para todos os produtos voltados para loca��o, � de 06 (seis) meses, a partir da emiss�o de Nota Fiscal. Sendo aplicada para todas as marcas voltadas para esse segmento (DEWALT, BLACK & DECKER, PORTER CABLE e STANLEY HIDRAULIC e PNEUMATICA) sendo de responsabilidade da <strong>BLACK & DECKER</strong> o envio das pe�as neste per�odo, mediante pedido inserido no sistema Telecontrol, onde a <strong>LOCADORA</strong> dever� informar o n�mero de serie de sua ferramenta para finaliza��o do pedido em garantia e posterior envio da(s) pe�a(s).
                            </p>

                            <p>
                                4.5 - Fica sob responsabilidade da <strong>LOCADORA</strong> o reparo de suas ferramentas em sua pr�pria oficina, sem que disto resulte qualquer ressarcimento de m�o de obra por parte da <strong>BLACK & DECKER</strong>.
                            </p>

                            <p>
                                4.6 - A <strong>LOCADORA</strong> � totalmente respons�vel pela inser��o de pedidos no sistema Telecontrol para envio de pe�as, tanto para ferramentas que estejam no per�odo de garantia como ferramentas fora do per�odo de garantia, acompanhando assim o envio das pe�as at� a sede de sua empresa, onde em caso de atraso de seus pedidos caber� � <strong>LOCADORA</strong> fazer contato com a <strong>BLACK & DECKER</strong> para que as a��es necess�rias sejam tomadas.
                            </p>

                            <p>
                                4.7 - A <strong>BLACK & DECKER</strong> tem por responsabilidade atender seus clientes no envio de pe�as, dentro do melhor prazo poss�vel, garantindo assim satisfa��o dos mesmos, onde em caso de falta de pe�as, a <strong>LOCADORA</strong> dever� acionar a <strong>BLACK & DECKER</strong>, informando o atraso para que as devidas an�lises e envio dos itens faltantes sejam providenciados, n�o sendo de responsabilidade da BLACK & DEKCER a troca ou ressarcimento de valores referentes � loca��o que fuja das condi��es acima citadas.
                            </p>

                            <p>
                                4.8 - Caso a <strong>LOCADORA</strong> n�o possua um t�cnico em seu estabelecimento, caber� a este, informar a <strong>BLACK & DECKER</strong> de tal situa��o, para que esta priorize o treinamento para o t�cnico respons�vel afim de que a pr�pria <strong>LOCADORA</strong> fa�a o reparo de seus produtos, conforme pol�tica do vigente em nosso Projeto Rental.
                            </p>

                            <p>
                                4.9 - Caso a locadorA n�o possua estrutura t�cnica / manuten��o, o mesmo poder� contar com o atendimento da rede autorizada indicada pela <strong>BLACK & DECKER</strong>, sendo de inteira responsabilidade da <strong>LOCADORA</strong>, realizar o pedido de pe�as tanto para produtos em garantia quanto or�amentos, bem como, encaminhar as pe�as � referida assist�ncia t�cnica assim que as mesmas forem entregues em sua locadora.
                            </p>

                            <p>
                                4.10 - Nos casos de atendimento realizado por uma de nossas assist�ncias t�cnicas, o prazo para reparo e, entrega das ferramentas n�o ser� de responsabilidade da <strong>BLACK & DECKER</strong>, permanecendo esta � disposi��o, para aux�lio nas dificuldades e/ou d�vidas junto aos prestadores de servi�os assistenciais.
                            </p>

                            <p>
                                4.11 - Para a <strong>LOCADORA</strong> que optar em proceder com reparo de seus produtos por meio das assist�ncias t�cnicas autorizadas <strong>BLACK & DECKER</strong>, ser� este respons�vel pelo pagamento referente aos servi�os prestados, bem como, por or�amentos e valores de m�o-de-obra, conforme valor previamente estabelecido pelas supracitadas assist�ncias t�cnicas, n�o cabendo a <strong>BLACK & DECKER</strong> nenhuma responsabilidade por esses servi�os.
                            </p>

                            <p>
                                4.12 - As pe�as trocadas em Garantia (DEWALT Rental), dever�o ser mantidas � disposi��o da <strong>BLACK & DECKER</strong>, pelo per�odo de 03 meses (tr�s) ap�s emiss�o de nota fiscal de compra, para que a <strong>BLACK & DECKER</strong>, possa realizar a inspe��o e/ou remo��o quando poss�vel, sendo que, somente ap�s o prazo acima citado as pe�as poder�o ser sucateadas pela <strong>LOCADORA</strong>.
                            </p>

                            <p>
                                4.13 - Caso a <strong>BLACK & DECKER</strong> solicite as pe�as, e a <strong>LOCADORA</strong> por ventura n�o as possua, a <strong>BLACK & DECKER</strong> ter� o pleno direito de adverti-lo formalmente e em caso de reincid�ncia rescindir o presente acordo.
                            </p>

                            <p>
                                4.14 - A <strong>LOCADORA</strong> que possua equipamentos da linha <em>\"Stanley Hidraulic\"</em>, tem por responsabilidade proceder com o reparo das ferramentas que o mesmo possui , cabendo � <strong>LOCADORA</strong> proceder com a implanta��o e acompanhamento de pedidos das pe�as necess�rias para reparo de suas ferramentas, sendo que, em caso de atraso em seus pedidos caber� � <strong>LOCADORA</strong> fazer contato com a <strong>BLACK & DECKER</strong> para que as a��es necess�rias sejam tomadas.
                            </p>

                            <p>
                                4.15 - A <strong>BLACK & DECKER</strong> n�o se responsabiliza pelo reparo de produtos da linha Stanley Hidraulic feitos por terceiros tanto quando pelo prazo gasto com o conserto, respaldando-se assim de qualquer ressarcimento de valores referentes � loca��o ou troca dos produtos que fuja das condi��es acima citadas.
                            </p>

                            <p>
                                4.16 - A <strong>BLACK & DECKER</strong> n�o ser� respons�vel pela substitui��o ou restitui��o de valores referentes � troca de �leo, fluidos hidr�ulicos e recarga de nitrog�nio dos equipamentos da linha hidr�ulica, mesmo para equipamentos que estejam em per�odo de garantia, por se tratar de itens com consumo ou desgaste natural.
                            </p>

                            <p>
                                4.17 - A <strong>BLACK & DECKER</strong> n�o receber� para reparo produtos da locadorA de qualquer dos segmentos (DEWALT, BLACK & DECKER, PORTER CABLE e STANLEY HIDRAULIC e PNEUMATICA), pois o mesmo oferece todas as condi��es para que a <strong>LOCADORA</strong> proceda com os reparos de suas ferramentas como treinamentos na f�br
                            </p>

                            <p>
                                <strong>CL�USULA QUINTA - DA VIG�NCIA E RESCIS�O</strong>
                            </p>

                            <p>
                                5.1 - O prazo de vig�ncia do presente contrato � indeterminado, a contar da data de assinatura.
                            </p>
                            <p>
                                5.2 - Fica facultada �s partes, a rescis�o antecipada deste instrumento, a qualquer momento, mediante notifica��o por escrito, com anteced�ncia de 30 (trinta) dias, sem direito a qualquer indeniza��o.
                            </p>
                            <p>
                                5.3 - N�o obstante o prazo estipulado na clausula 5.1, o presente contrato se rescindir� de pleno direito, independentemente de notifica��o ou interpela��o judicial ou extrajudicial nas hip�teses abaixo, a saber:
                            </p>

                            <p class='escpaco-clausula-2'>
                                a) Fal�ncia, dissolu��o ou recupera��o judicial da <strong>LOCADORA</strong> ou da <strong>BLACK & DECKER</strong>; e,
                            </p>
                            <p class='escpaco-clausula-2'>
                                b) Descumprimento de quaisquer cl�usulas do presente acordo, desde que a parte infratora, devidamente notificada para sanar a falha, n�o o fa�a no prazo de 05 (cinco) dias ap�s o comunicado por escrito.
                            </p>
                            <p>
                                5.4 - Findo o presente acordo, a <strong>LOCADORA</strong> se comprometer� liquidar suas pend�ncias financeiras junto a <strong>BLACK & DECKER</strong> e, obrigando-se a n�o fazer uso do nome ou logomarca da <strong>BLACK & DECKER</strong>, ou seja, monogramas em seus documentos, letreiros, cartazes ou quaisquer outros meios de comunica��o.
                            </p>

                            <p>
                                <strong>CL�USULA SEXTA - DAS DISPOSI��ES GERAIS</strong>
                            </p>

                            <p>
                                6.1 - A <strong>BLACK & DECKER</strong> reserva-se o direito de suspender, sem pr�vio aviso e sem qualquer indeniza��o, o fornecimento de quaisquer de seus produtos � <strong>LOCADORA</strong>, bem como, rescindir o presente acordo, caso este mantenha atitudes n�o condizentes com a �tica comercial, profissional e moral, sem preju�zo do atendimento ao descrito nos demais par�grafos da presente.
                            </p>

                            <p>
                                6.2 - Nos casos de pedidos incorretos feitos pela <strong>LOCADORA</strong>, seja por quantidade, c�digo ou encerramentos de atividades por parte da <strong>LOCADORA</strong>, a <strong>BLACK & DECKER</strong> n�o se obriga a receber pe�as de seu estoque, em devolu��o, sob qualquer pretexto.
                            </p>

                            <p>
                                6.3 - A <strong>LOCADORA</strong> � o �nico respons�vel pelo pessoal que utilizar na presta��o dos Servi�os de sua sede, devendo satisfazer rigorosamente todas as suas obriga��es trabalhistas, previdenci�rias e sociais em geral em rela��o ao mesmo, mantendo a <strong>BLACK & DECKER</strong> isenta de qualquer reclama��o, d�vida, pend�ncia ou atrito entre ela, <strong>LOCADORA</strong>, e seus empregados ou qualquer outro preposto que utilizar na execu��o deste acordo.
                            </p>
                            <p>
                                6.4 - Fica vedado as partes transferir ou ceder, a qualquer t�tulo, os direitos e obriga��es assumidos nesse acordo.
                            </p>
                            <p>
                                6.5 - Qualquer altera��o, modifica��o, complementa��o, ou ajuste, somente ser� reconhecido e produzir� efeitos legais, se incorporado ao presente acordo mediante termo aditivo, devidamente assinado pela <strong>BLACK & DECKER</strong> e <strong>LOCADORA</strong>.
                            </p>
                            <p>
                                6.6 - Toda e qualquer toler�ncia quanto ao cumprimento por qualquer das partes, das condi��es estabelecidas neste contrato, ser� considerada exce��o, n�o possuindo cond�o de nova��o ou altera��o das disposi��es ora pactuadas, mas t�o somente liberalidade entre <strong>BLACK & DECKER</strong> e <strong>LOCADORA</strong>.
                            </p>
                            <p>
                                6.7 - Para fins de notifica��o, as informa��es dever�o ser formalizadas por escrito, via e-mail, ou quando couber, de outra forma, desde que ratificadas posteriormente por e-mail.
                            </p>
                            <p>
                                6.8 - Este acordo cancela e/ou substitui quaisquer outros acordos, escritos ou verbais, por ventura, existentes, passando a partir dessa data, a ser o �nico instrumento que rege as rela��es entre <strong>LOCADORA</strong> e <strong>BLACK & DECKER</strong>.
                            </p>

                            <p>
                                <strong>CL�USULA S�TIMA - DO FORO</strong>
                            </p>

                            <p>
                                7.1 - As partes elegem o foro da Comarca de Uberaba/MG para dirimir quaisquer d�vidas decorrentes do presente contrato, com exclus�o de qualquer outro, por mais privilegiado que seja.
                            </p>
                            <p>
                                E, por estarem, assim certas e contratadas, assinam as partes o presente acordo, em 02 (duas) vias de igual teor e valor, na presen�a das testemunhas ao final nomeadas para que reproduza os efeitos de direito.
                            </p>

                            <p>
                                Uberaba, ".$dataHoje."
                            </p>

                        </div>

                        <div class='rodape-contrato'>
                            <div class='assinaturas'>
                                <br /> <br />
                                <img src='../image/contratos/ass_black.jpg' width='180px' alt='Ass'>
                                <hr />
                                <strong>BLACK & DECKER DO BRASIL LTDA.</strong> <br />
                                SILVANIA SILVA - GERENTE DE PRODUTOS E SERVI�OS <br /> <br /> <br /> <br />
                                <hr />
                                <strong class='text-upper'>{$posto_nome}</strong>
                            </div>
                            <div class='testemunhas'>
                                <strong>Testemunhas</strong>
                                <br /> <br /> <br /> <br />
                                1)
                                <hr />
                                NOME: <br />
                                RG / CPF:
                                <br /> <br /> <br />
                                2)
                                <hr />
                                NOME: <br />
                                RG / CPF:
                            </div>
                        </div>

                        <div style='clear: both;'></div>

                    </div>

                </body>

                </html>
            ";
            break;
        case "Autorizada":
            $conteudo = "
                <!DOCTYPE html>
                    <head>

                        <style>
                            .corpo-contrato{
                                font-family: arial, verdana;
                                font-size: 9px !important;
                                color: #444;
                                width: 100%;
                                background-color: #fff;
                                text-align: justify;
                            }
                            .cabecalho-contrato, .rodape-contrato{
                                width: 100%;
                            }
                            .logo-contrato, .info-contrato, .assinaturas, .testemunhas{
                                width: 45%;
                                float: left;
                                padding-top: 10px;
                                padding-bottom: 10px;
                            }
                            .logo-contrato, assinaturas{
                                padding-right: 15px;
                            }
                            .info-contrato, .testemunhas{
                                padding-left: 30px;
                            }
                            .logo-contrato{
                                text-align: center;
                            }
                            .logo-contrato img{
                                width: 350px;
                            }
                            .info-contrato{
                                border-left: 1px solid #e74c3c;
                                padding-left: 20px;
                            }
                            .conteudo-contrato h3{
                                text-align: center;
                                font-size: 12px !important;
                            }
                            .conteudo-contrato strong{
                                text-transform: uppercase;
                            }
                            .escpaco-clausula{
                                padding-left: 30px;
                            }
                            .escpaco-clausula-2{
                                padding-left: 60px;
                            }
                            .text-upper{
                                text-transform: uppercase;
                            }
                            .rodape-contrato hr{
                                margin-left: 0;
                                width: 80%;
                                margin-top: 20px;
                            }
                            .corLink {
                                color:#00F;
                            }
                            .letraCurta {
                                line-height:5px;
                            }
                        </style>

                    </head>

                    <body>

                        <div class='corpo-contrato'>

                            <div class='conteudo-contrato'>

                                <h3>
                                    INSTRUMENTO PARTICULAR DE ACORDO DE PRESTA��O DE SERVI�OS CELEBRADO ENTRE BLACK & DECKER DO BRASIL LTDA., E {$posto_nome}
                                </h3>

                                <p>
                                    Pelo presente instrumento particular de um lado <strong>BLACK & DECKER DO BRASIL LTDA.</strong>, pessoa jur�dica de direito privado, com sede � Rodovia BR 050, s/n�, KM 167, Lote 05 Parte, Quadra 01, Distrito Industrial II, Uberaba - MG, inscrita no CNPJ/MF sob o n�. 53.296.273/0001-91, representada por seus diretores, ao final assinados, e doravante denominada simplesmente <strong>BLACK & DECKER</strong>, e de outro lado <strong>{$posto_nome}</strong> com sede {$posto_endereco_completo}, inscrita no CNPJ/MF sob o n� {$posto_cnpj}, neste ato representada na forma de seu Contrato Social, denominada simplesmente <strong>POSTO AUTORIZADO</strong>, firmam o presente acordo regulado pelas cl�usulas seguintes:
                                </p>

                                <p>
                                    <strong>CL�USULA PRIMEIRA - DO OBJETO</strong>
                                </p>

                                <p>
                                    A <strong>BLACK & DECKER</strong> nomeia como posto autorizado Black & Decker do Brasil Ltda. $posto_nome, cujo objeto do presente acordo, ser� a presta��o de servi�os de assist�ncia t�cnica a consumidores e revendedores, para as linhas de produtos, fabricados pela BLACK & DECKER, quais sejam: $posto_linhas
                                </p>

                                <p>
                                    <strong>CL�USULA SEGUNDA - DO ACESSO</strong>
                                </p>

                                <p>
                                    2.1 - A <strong>BLACK & DECKER</strong> criar�  um c�digo de cadastro para identifica��o do <strong>POSTO AUTORIZADO</strong>.
                                </p>

                                <p class='escpaco-clausula'>
                                    2.1.1 - O <em>\"login\"</em> de acesso ao sistema da <strong>BLACK & DECKER</strong> ser� criado assim que o <strong>POSTO AUTORIZADO</strong> enviar o presente instrumento devidamente assinado.
                                </p>

                                <p class='escpaco-clausula'>
                                    2.1.2 - Para concluir o acesso, o <strong>POSTO AUTORIZADO</strong> dever� entrar no site <u class='corLink'>www.telecontrol.com.br</u>, onde encontrar� as vistas explodidas dos produtos, tela para pedidos de pe�as, acesso � tabela de pre�os, informa��es t�cnicas e relat�rios gerenciais.
                                </p>

                                <p>
                                    <strong>CL�USULA TERCEIRA - DO ATENDIMENTO A REVENDAS</strong>
                                </p>

                                <p>
                                    3.1 Para o bom desempenho de vossas atividades, o <strong>POSTO AUTORIZADO</strong> dever� acatar as seguintes instru��es:
                                </p>

                                <p class='escpaco-clausula'>
                                    3.1.1 - O produto que retornar sem conserto ou que ultrapasse o prazo de reparo firmado com consumidor/revendedor, e que eventualmente ocasionar devolu��o ou troca, <u>ter� seu valor debitado</u> do <strong>POSTO AUTORIZADO</strong>, quando detectada <u>falha do atendimento</u>, tais como:  falta de comunica��o do problema � <strong>BLACK & DECKER</strong> ap�s 05 (cinco) dias do recebimento do produto, atraso superior a 5 (cinco) dias na efetiva��o do pedido, falta de comunica��o ao consumidor POR ESCRITO (atrav�s de e-mail, SMS, WhatsApp, carta registrada ou telegrama) quando o produto estiver reparado, etc.
                                </p>

                                <p class='escpaco-clausula'>
                                    3.1.2 - O produto dever� passar por triagem no prazo m�ximo de 05 (cinco) dias, assim como o pedido das pe�as para reparo e o envio do or�amento �s revendas, dever� ser informado no mesmo prazo.
                                </p>

                                <p class='escpaco-clausula'>
                                    3.1.3 - O <strong>POSTO AUTORIZADO</strong> dever� se responsabilizar por documentar <strong>POR ESCRITO</strong> o recebimento do produto, assim como a entrega dos produtos de modo organizado e disponibilizar� essa informa��o quando solicitado pela <strong>BLACK & DECKER</strong>, guardando os comprovantes pelo per�odo de 60 (sessenta) meses. Caso a revenda retire o produto sem conserto, � necess�ria a emiss�o de uma Nota Fiscal de Retorno sem conserto e esta informa��o dever� ser passada para a <strong>BLACK & DECKER</strong>.
                                </p>
                                <p>
                                    <strong>CL�USULA QUARTA - DAS RESPONSABILIDADES DO POSTO AUTORIZADO</strong>
                                </p>

                                <p>
                                    4.1 - Para o bom desempenho de vossas atividades, o <strong>POSTO AUTORIZADO</strong> dever� cumprir as seguintes obriga��es:
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.1 - Manter instala��es adequadas ao desempenho da presta��o dos servi�os;
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.2 - Sugerimos manter no estoque de pe�as de reposi��o de maior giro da marca <strong>BLACK & DECKER</strong>, que ser�o utilizadas para revenda e aplica��o nos consertos dos produtos;
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.3 - Possuir o equipamento m�nimo necess�rio para conserto, bem como as ferramentas, objetivando garantir servi�o r�pido e eficiente para os consumidores / revendedores;
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.4 - Executar servi�os e atender reclama��es de produtos, a fim de oferecer aos consumidores/revendedores atendimento de alto padr�o;
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.5 - Executar inser��o de pedidos de pe�as para atendimentos em garantia com a maior agilidade e precis�o poss�vel.
                                </p>

                                <p class='escpaco-clausula-2'>
                                    4.1.5.1 - Em caso de demora na coloca��o de pedidos de pe�as, superior a 5 (cinco) dias, pedidos inseridos incorretos ou falhas comprovadas (por erro de atendimento do posto / descumprimento de procedimento) que acarretarem em produtos parados no posto por mais de 30 (trinta) dias, a B&D reserva-se o direito de debitar do <strong>POSTO AUTORIZADO</strong> gastos oriundos dessas diverg�ncias, tais como, devolu��o de valor ao cliente, troca de produto, fretes adicionais, al�m de despesas com pagamentos de condena��es judiciais, condena��es de PROCON, custas e honor�rios advocat�cios
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.6 - Participar, sempre que convocado, de cursos de treinamento organizados ou ministrados pela <strong>BLACK & DECKER</strong>;
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.7 - Obedecer � tabela de pre�o sugerido de pe�as de reposi��o fornecida pela <strong>BLACK & DECKER</strong>;
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.8 - Possuir organiza��o administrativa e fiscal condizente com o volume do neg�cio a ser desenvolvido;
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.9 - Conservar manuais e cartas de servi�os fornecidas pela <strong>BLACK & DECKER</strong> e mant�-los dispon�veis a todos os funcion�rios;
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.10 - Conservar os aparelhos de clientes, em reparo ou j� reparados, limpos e em prateleiras e n�o jogados pelo ch�o;
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.11 - Informar ao setor de assist�ncia t�cnica <strong>BLACK & DECKER</strong> sobre problemas n�o comuns de ordem t�cnica e/ou administrativa;
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.12 - Saldar em dia seus compromissos financeiros com a <strong>BLACK & DECKER</strong> e/ou Distribuidores autorizados;
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.13 - Autorizar inspe��es e auditorias, efetuadas por pessoas indicadas pela <strong>BLACK & DECKER</strong>;
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.14 - Evitar declara��es exageradas com rela��o aos produtos da <strong>BLACK & DECKER</strong> observando os preceitos legais de prote��o ao consumidor.
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.15 - Informar ao consumidor POR ESCRITO (atrav�s de e-mail, SMS, WhatsApp, carta registrada ou telegrama) que o produto foi reparado;
                                </p>
                                <p class='escpaco-clausula'>
                                    4.1.16 - Informar � BLACK & DECKER caso exista algum produto parado no <strong>POSTO AUTORIZADO</strong>, por falta de pe�as ou outro motivo, quando completado <strong>15 dias</strong> da abertura da OS, <strong>SOB PENA DE SER RESPONSABILIZADO</strong> a reembolsar as despesas com pagamentos de condena��es judiciais, condena��es de PROCON, custas e honor�rios advocat�cios pelo n�o cumprimento do prazo previsto no artigo 18 do C�digo de Defesa do Consumidor
                                </p>
                                <p class='escpaco-clausula'>
                                    4.1.17 - O <strong>POSTO AUTORIZADO</strong> tem conhecimento e concorda que a BLACK & DECKER n�o o obriga a manter pe�as e produtos em estoque, e por esse motivo, n�o ser� respons�vel pelo reembolso de eventuais pe�as e produtos que o <strong>POSTO AUTORIZADO</strong> mantenha em seu estoque, mesmo ap�s eventual descredenciamento ou rescis�o do contrato.
                                </p>

                                <p>
                                    4.2 - A BLACK & DECKER n�o se responsabilizar� por problemas ocasionados pelo n�o cumprimento pelo <strong>POSTO AUTORIZADO</strong>, de obriga��es, tais como:
                                </p>

                                <p class='escpaco-clausula'>
                                    4.2.1 - Demora na an�lise e coloca��o de pedidos de pe�as superior a 5 (cinco) dias a contar do recebimento do produto;
                                </p>
                                <p class='escpaco-clausula'>
                                    4.2.2 - Inserir pedidos incorretos;
                                </p>
                                <p class='escpaco-clausula'>
                                    4.2.3 - N�o comunicar ao consumidor POR ESCRITO (atrav�s de e-mail, SMS, WhatsApp, carta registrada ou telegrama) que o produto foi reparado;
                                </p>
                                <p class='escpaco-clausula'>
                                    4.2.4 - Reparos executados sem qualidade e/ou neglig�ncia.
                                </p>
                                <p class='escpaco-clausula'>
                                    4.2.5 - Mau atendimento junto ao consumidor/revendedor final.
                                </p>

                                <p>
                                    4.3 - O n�o cumprimento de qualquer uma das cl�usulas acima � pass�vel de penalidades ao <strong>POSTO AUTORIZADO</strong>, podendo ser em forma de advert�ncia verbal, advert�ncia por escrito, registro de ocorr�ncia, e em casos mais graves e/ou reincidentes, d�bito de valores no extrato do posto e descredenciamento.
                                </p>

                                </p>
                                <p class='escpaco-clausula'>
                                    <strong>
                                    4.3.1 - Caso a BLACK & DECKER venha a ser condenada judicialmente ou pelo PROCON por culpa comprovada do <strong>POSTO AUTORIZADO</strong>, pelo n�o cumprimento do disposto no item 4.2, a BLACK & DECKER poder� descontar dos valores a serem pagos ao <strong>POSTO AUTORIZADO</strong>, as despesas com pagamento de condena��es e custas judiciais, com as quais o <strong>POSTO AUTORIZADO</strong> concorda plenamente.</strong>
                                </p>

                                <p>
                                    <strong>CL�USULA QUINTA - A RESPONSABILIDADE DO POSTO AUTORIZADO QUANTO �S INFORMA��ES CADASTRAIS</strong>
                                </p>

                                <p>
                                    5.1 - Comunicar imediatamente ao setor de cadastro da <strong>BLACK & DECKER</strong> as altera��es tais como:
                                </p>
                                <p class='escpaco-clausula letraCurta'>
                                    a. Mudan�a de Raz�o Social ou quaisquer altera��es societ�rias, incluindo mas n�o se limitando ao Contrato Social;
                                </p>
                                <p class='escpaco-clausula letraCurta'>
                                    b. Altera��o de Inscri��o Estadual e/ou CNPJ;
                                </p>

                                <p class='escpaco-clausula letraCurta'>
                                    c. Mudan�as de endere�o / telefone / contato / e-mail;
                                </p>
                                <p class='escpaco-clausula letraCurta'>
                                    d. Abertura de filiais;
                                </p>
                                <p class='escpaco-clausula letraCurta'>
                                    e. Altera��o de Capital Social;
                                </p>
                                <p class='escpaco-clausula letraCurta'>
                                    f. Altera��o de Dados Banc�rios.
                                </p>

                                <p>
                                    <strong>CL�USULA SEXTA - DA ORDEM DE SERVI�O (O.S.)</strong>
                                </p>

                                <p>
                                    6.1 - A ordem de servi�o representa o registro de todo e qualquer atendimento realizado, sendo este obrigat�rio ao <strong>POSTO AUTORIZADO</strong>.
                                </p>

                                <p>
                                    6.2 - Ordens de servi�o com itens irregulares, ou seja, com pe�as que n�o pertencem a garantia e/ou n�o autorizado pela <strong>BLACK & DECKER</strong>, ou ainda estiverem com alguma irregularidade ter�o seus valores descontado em extrato.
                                </p>

                                <p>
                                    6.3 - Caso sejam constatados problemas descritos acima poder�o ocasionar em: advert�ncia e posteriormente o descredenciamento do posto autorizado.
                                </p>
                                <p class='escpaco-clausula'>
                                    <u>Par�grafo �nico</u>: O consumidor e/ou revendedor dever� ser avisado que seu produto j� foi reparado, estando pronto para a retirada <strong>POR ESCRITO</strong> (atrav�s de e-mail, SMS, WhatsApp, carta registrada ou telegrama), formalizando para o consumidor e/ou revendedor que seu produto j� foi consertado e est� pronto para retirada. O custo de envio desta comunica��o, ser� <u>reembolsado</u> ao <strong>POSTO AUTORIZADO</strong> pela <strong>BLACK & DECKER</strong>, para que, desta forma esta �ltima possa evitar notifica��es junto ao Procon e processos judiciais.
                                </p>


                                <p>
                                    <strong>CL�USULA S�TIMA - DAS OBRIGA��ES DAS PARTES</strong>
                                </p>

                                <p>
                                    7.1 - Todo o material fornecido pela <strong>BLACK & DECKER</strong> dever� ser manuseado, exclusiva e privativamente, pelo <strong>POSTO AUTORIZADO</strong> com o devido cuidado e para seu uso interno.
                                </p>
                                <p>
                                    7.2 - O presente acordo para o funcionamento do Posto Autorizado n�o poder� ser cedido ou transferido sem anu�ncia expressa da <strong>BLACK & DECKER</strong>.
                                </p>
                                <p>
                                    7.3 - O <strong>POSTO AUTORIZADO</strong> n�o exercer� quaisquer direitos sobre a clientela originada em decorr�ncia da atividade exercida pelo <strong>POSTO AUTORIZADO</strong>, podendo qualquer das partes utiliz�-la ap�s a eventual rescis�o deste acordo.
                                </p>
                                <p>
                                    7.4 - O <strong>POSTO AUTORIZADO</strong> se responsabiliza, de forma exclusiva, por todos os encargos fiscais, trabalhistas, acidentados e previdenci�rios de seus prepostos;
                                </p>
                                <p>
                                    7.5 - Todos os produtos e todas as pe�as trocadas pelo <strong>POSTO AUTORIZADO</strong> para produtos com garantia dever�o ser mantidas � disposi��o da <strong>BLACK & DECKER</strong> pelo prazo m�nimo de 03 (tr�s) meses. A <strong>BLACK & DECKER</strong> tem direito � coleta das pe�as e produtos no prazo de 90 (noventa) dias. Para extratos n�o pagos, este direito � coleta � independente da data. Para que n�o haja necessidade de armazenamento de pe�as e produtos por longos per�odos, os lan�amentos devem estar sempre atualizados.  Somente ap�s 03 (tr�s) meses, estas pe�as e produtos poder�o ser sucateadas.  O prazo de 03 (tr�s) meses ora mencionado, come�a a contar a partir do momento que as ordens de servi�o est�o finalizadas em extrato de servi�o com documenta��o j� enviada para a <strong>BLACK & DECKER</strong>. As pe�as/produtos trocados fora de garantia dever�o permanecer em poder do consumidor;
                                </p>
                                <p class='escpaco-clausula'>
                                    7.5.1 - As pe�as e produtos que tiveram sua coleta solicitada e n�o forem recebidos na <strong>BLACK & DECKER</strong>, sendo esta causada pelo posto (por recusa em enviar as pe�as/produtos ou n�o possuir estas), ter�o seus valores descontado em extrato.
                                </p>
                                <p>
                                    7.6 - O <strong>POSTO AUTORIZADO</strong> n�o poder� incluir nos materiais promocionais fornecidos pela <strong>BLACK & DECKER</strong> quaisquer outras inscri��es, mesmo que n�o signifiquem concorr�ncia, direta ou indireta, aos produtos da <strong>BLACK & DECKER</strong>;
                                </p>
                                <p>
                                    7.7 - O <strong>POSTO AUTORIZADO</strong> responsabiliza-se por todos os encargos fiscais e tribut�rios federais, estaduais e municipais, decorrentes da sua atividade, inclusive encargos trabalhistas, emolumentos e taxas relativas ao munic�pio incidentes sobre placas e propaganda.
                                </p>

                                <p>
                                    <strong>CL�USULA OITAVA - DA VIG�NCIA E RESCIS�O</strong>
                                </p>
                                <p>
                                    8.1 - O prazo de vig�ncia do presente contrato � indeterminado, a contar da data de assinatura.
                                </p>
                                <p>
                                    8.2 - Fica facultada �s partes, a rescis�o antecipada deste instrumento, a qualquer momento, mediante notifica��o por escrito, com anteced�ncia de 30 (trinta) dias, sem direito a qualquer indeniza��o.
                                </p>
                                <p>
                                    8.3 - N�o obstante o prazo estipulado na cl�usula 8.1, o presente contrato se rescindir� de pleno direito, independentemente de notifica��o ou interpela��o judicial ou extrajudicial nas hip�teses abaixo, a saber:
                                </p>
                                <p class='escpaco-clausula letraCurta'>
                                    a. Fal�ncia, dissolu��o ou recupera��o judicial do <strong>POSTO AUTORIZADO</strong> ou da <strong>BLACK & DECKER</strong>; e,
                                </p>
                                <p class='escpaco-clausula letraCurta'>
                                    b. Descumprimento de quaisquer cl�usulas do presente acordo, desde que a parte infratora, devidamente notificada para sanar a falha, n�o o fa�a no prazo de 05 (cinco) dias ap�s o comunicado por escrito.
                                </p>


                                <!-- PARAGRAFO FINAL -->
                                <p>
                                    <strong>CL�USULA NONA - DAS DISPOSI��ES GERAIS</strong>
                                </p>
                                <p>
                                    9.1 - Este acordo cancela e/ou substitui quaisquer outros acordos, escritos ou verbais, por ventura, existentes, passando a partir dessa data, a ser o �nico instrumento que rege as rela��es entre <strong>POSTO AUTORIZADO</strong> e <strong>BLACK & DECKER</strong>.
                                </p>
                                <p>
                                    9.2 - Por meio deste acordo, fica concedida a autoriza��o para presta��o de servi�os de assist�ncia t�cnica dos produtos de fabrica��o da <strong>BLACK & DECKER</strong>, podendo ser rescindido a qualquer tempo, por qualquer das partes, mediante aviso pr�vio de 30 (trinta) dias por escrito conforme descrito na cl�usula 8.2 do presente, findo os quais o <strong>POSTO AUTORIZADO</strong> se comprometer� liquidar suas pend�ncias de consumidores e revendedores da <strong>BLACK & DECKER</strong> assim como, extratos de pagamentos de garantia em aberto e, n�o mais usar o nome da BLACK & DECKER DO BRASIL LTDA e/ou marcas desta companhia, ou seja, monogramas em seus documentos, letreiros, cartazes ou em quaisquer outros meios de comunica��o.
                                </p>
                                <p>
                                    9.3 - A <strong>BLACK & DECKER</strong> reserva-se o direito de suspender, sem pr�vio aviso e sem qualquer indeniza��o, o fornecimento de quaisquer de seus produtos ao <strong>POSTO AUTORIZADO</strong>, caso este mantenha atitudes n�o condizentes com a �tica comercial, profissional e moral, sem preju�zo do atendimento ao descrito nos demais par�grafos do presente.
                                </p>
                                <p>
                                    9.4 - Em caso de cancelamento, a <strong>BLACK & DECKER</strong> n�o se obriga a receber pe�as de seu estoque, em devolu��o, sob qualquer pretexto.
                                </p>
                                <p>
                                    9.5 - O presente acordo n�o possui car�ter de exclusividade, reservando-se a <strong>BLACK & DECKER</strong> o direito de, a seu exclusivo crit�rio, nomear outras oficinas na mesma �rea, ou regi�o para a presta��o de servi�os de mesmo objeto deste acordo.
                                </p>
                                <p>
                                    9.6 - O <strong>POSTO AUTORIZADO</strong> � o �nico respons�vel pelo pessoal que utilizar na presta��o dos Servi�os de sua sede, devendo satisfazer rigorosamente todas as suas obriga��es trabalhistas, previdenci�rias e sociais em geral em rela��o ao mesmo, mantendo a <strong>BLACK & DECKER</strong> isenta de qualquer reclama��o, d�vida, pend�ncia ou atrito entre ela, <strong>POSTO AUTORIZADO</strong>, e seus empregados ou qualquer outro preposto que utilizar na execu��o deste acordo.
                                </p>
                                <p>
                                    9.7 - Qualquer altera��o, modifica��o, complementa��o, ou ajuste, somente ser� reconhecido e produzir� efeitos legais, se incorporado ao presente acordo mediante termo aditivo, devidamente assinado pela <strong>BLACK & DECKER</strong> e <strong>POSTO AUTORIZADO</strong>.
                                </p>
                                <p>
                                    9.8 - Toda e qualquer toler�ncia quanto ao cumprimento por qualquer das partes, das condi��es estabelecidas neste contrato, ser� considerada exce��o, n�o possuindo cond�o de nova��o ou altera��o das disposi��es ora pactuadas, mas t�o somente liberalidade entre <strong>BLACK & DECKER</strong> e <strong>POSTO AUTORIZADO</strong>.
                                </p>
                                <p>
                                    9.9 - Para fins de notifica��o, as informa��es dever�o ser formalizadas por escrito, via e-mail, ou quando couber, de outra forma, desde que ratificadas posteriormente por e-mail.
                                </p>
                                <p>
                                    <strong>CL�USULA D�CIMA - DO FORO</strong>
                                </p>
                                <p>
                                    10.1 - As partes elegem o foro da Comarca de Uberaba/MG para dirimir quaisquer d�vidas decorrentes do presente contrato, com exclus�o de qualquer outro, por mais privilegiado que seja.
                                </p>
                                <p>
                                    E, por estarem, assim certas e contratadas, assinam as partes o presente acordo, em 02 (duas) vias de igual teor e valor, na presen�a das testemunhas ao final nomeadas para que reproduza os efeitos de direito.
                                </p>

                                <p>
                                    Uberaba, $dataHoje
                                </p>

                            </div>

                            <div class='rodape-contrato'>
                                <div class='assinaturas'>
                                    <br /> <br />
                                    <img src='../image/contratos/ass_black.jpg' width='180px' alt='Ass'>
                                    <hr />
                                    <strong>BLACK & DECKER DO BRASIL LTDA.</strong> <br />
                                    SILVANIA SILVA - GERENTE DE PRODUTOS E SERVI�OS <br /> <br /> <br /> <br />
                                    <hr />
                                    <strong class='text-upper'>{$posto_nome}</strong>
                                </div>
                                <div class='testemunhas'>
                                    <strong>Testemunhas</strong>
                                    <br /> <br /> <br /> <br />
                                    1)
                                    <hr />
                                    NOME: <br />
                                    RG / CPF:
                                    <br /> <br /> <br />
                                    2)
                                    <hr />
                                    NOME: <br />
                                    RG / CPF:
                                </div>
                            </div>

                            <div style='clear: both;'></div>

                        </div>

                    </body>

                </html>

            ";
            break;
        case "Compra Peca":
            $conteudo = "
                <!DOCTYPE html>
                <head>

                    <style>
                        .corpo-contrato{
                            font-family: arial, verdana;
                            font-size: 9px !important;
                            color: #444;
                            width: 100%;
                            background-color: #fff;
                            text-align: justify;
                        }
                        .cabecalho-contrato, .rodape-contrato{
                            width: 100%;
                        }
                        .logo-contrato, .info-contrato, .assinaturas, .testemunhas{
                            width: 45%;
                            float: left;
                            padding-top: 10px;
                            padding-bottom: 10px;
                        }
                        .logo-contrato, assinaturas{
                            padding-right: 15px;
                        }
                        .info-contrato, .testemunhas{
                            padding-left: 30px;
                        }
                        .logo-contrato{
                            text-align: center;
                        }
                        .logo-contrato img{
                            width: 350px;
                        }
                        .info-contrato{
                            border-left: 1px solid #e74c3c;
                            padding-left: 20px;
                        }
                        .conteudo-contrato h3{
                            text-align: center;
                            font-size: 12px !important;
                        }
                        .conteudo-contrato strong{
                            text-transform: uppercase;
                        }
                        .escpaco-clausula{
                            padding-left: 30px;
                        }
                        .escpaco-clausula-2{
                            padding-left: 60px;
                        }
                        .text-upper{
                            text-transform: uppercase;
                        }
                        .rodape-contrato hr{
                            margin-left: 0;
                            width: 80%;
                            margin-top: 20px;
                        }
                        .corLink {
                            color:#00F;
                        }
                    </style>

                </head>

                <body>

                    <div class='corpo-contrato'>

                        <div class='conteudo-contrato'>

                            <h3>
                                INSTRUMENTO PARTICULAR DE ACORDO DE COMPRA DE PE�AS CELEBRADO ENTRE BLACK & DECKER DO BRASIL LTDA., E {$posto_nome}
                            </h3>

                            <p>
                                Pelo presente instrumento particular de um lado <strong>BLACK & DECKER DO BRASIL LTDA.</strong>, pessoa jur�dica de direito privado, com sede � Rodovia BR 050, s/n�, KM 167, Lote 05 Parte, Quadra 01, Distrito Industrial II, Uberaba - MG, inscrita no CNPJ/MF sob o n�. 53.296.273/0001-91, representada por seus diretores, ao final assinados, e doravante denominada simplesmente <strong>BLACK & DECKER</strong>, e de outro lado <strong>{$posto_nome}</strong> com sede {$posto_endereco_completo}, inscrita no CNPJ/MF sob o n� {$posto_cnpj}, neste ato representada na forma de seu Contrato Social, denominada simplesmente denominada simplesmente <strong>COMPRA DE PE�AS</strong>, firmam o presente acordo regulado pelas cl�usulas seguintes:
                            </p>

                            <p>
                                <strong>CL�USULA PRIMEIRA - DO OBJETO</strong>
                            </p>

                            <p>
                                1.1 - O <strong>COMPRA DE PE�AS</strong> possuir� acesso ao sistema Telecontrol, com endere�o eletr�nico <u class='corLink'>www.telecontrol.com.br</u>, para que efetue compras de pe�as, esse procedimento � denominado \"COMPRA DE PE�AS\".
                            </p>

                            <p>
                                <strong>CL�USULA SEGUNDA - DO ACESSO</strong>
                            </p>

                            <p>
                                2.1 - A <strong>BLACK & DECKER</strong> criar�  um c�digo de cadastro para identifica��o do <strong>COMPRA DE PE�AS</strong>.
                            </p>

                            <p class='escpaco-clausula'>
                                2.1.1 - O <em>\"login\"</em> de acesso ao sistema da <strong>BLACK & DECKER</strong> ser� criado assim que o <strong>COMPRA DE PE�AS</strong> enviar o presente instrumento devidamente assinado.
                            </p>

                            <p class='escpaco-clausula'>
                                2.1.2 - Para concluir o acesso, o <strong>COMPRA DE PE�AS</strong> dever� entrar no site <u class='corLink'>www.telecontrol.com.br</u>, onde encontrar� as vistas explodidas dos produtos, tela para pedidos de pe�as, acesso � tabela de pre�os, informa��es t�cnicas e relat�rios gerenciais.
                            </p>

                            <p>
                                <strong>CL�USULA TERCEIRA - DA RESPONSABILIDADE DO COMPRA DE PE�AS</strong>
                            </p>

                            <p>
                                3.1 - � de responsabilidade do <strong>COMPRA DE PE�AS</strong>:
                            </p>

                            <p class='escpaco-clausula'>
                                3.1.1 - Manter contato com a <strong>BLACK & DECKER</strong> em caso de d�vidas para que esta possa lhes prestar o devido auxilio;
                            </p>

                            <p class='escpaco-clausula'>
                                3.1.2 - Saldar em dia seus compromissos financeiros com a <strong>BLACK & DECKER</strong>;
                            </p>

                            <p class='escpaco-clausula'>
                                3.1.3 - Autorizar inspe��es, bem como, auditorias efetuadas por pessoas indicadas pela <strong>BLACK & DECKER</strong>;
                            </p>
                            <p class='escpaco-clausula'>
                                3.1.4 - Comunicar imediatamente ao setor de cadastro da <strong>BLACK & DECKER</strong> as altera��es tais como:
                            </p>

                            <p class='escpaco-clausula-2'>
                                a. Mudan�a de Raz�o Social ou quaisquer altera��es societ�rias, incluindo mas n�o se limitando ao Contrato Social;
                            </p>
                            <p class='escpaco-clausula-2'>
                                b. Altera��o de Inscri��o Estadual e/ou CNPJ;
                            </p>
                            <p class='escpaco-clausula-2'>
                                c. Mudan�as de endere�o / telefone / contato / e-mail;
                            </p>
                            <p class='escpaco-clausula-2'>
                                d. Abertura de filiais;
                            </p>
                            <p class='escpaco-clausula-2'>
                                e. Altera��o de Capital Social;
                            </p>
                            <p class='escpaco-clausula-2'>
                                f. Altera��o de Dados Banc�rios;
                            </p>

                            <p>
                                <strong>CL�USULA QUARTA - DAS RESPONSABILIDADES DA BLACK & DECKER</strong>
                            </p>

                            <p>
                                4.1 - O <strong>COMPRA DE PE�AS</strong> responsabiliza-se por todos os encargos fiscais e tribut�rios federais, estaduais e municipais, decorrentes da sua atividade, inclusive encargos trabalhistas, emolumentos e taxas relativas ao munic�pio incidentes sobre placas e propaganda.
                            </p>

                            <p>
                                4.2 - A <strong>BLACK & DECKER</strong> tem por responsabilidade atender seus clientes no envio de pe�as, dentro do melhor prazo poss�vel, garantindo assim satisfa��o dos mesmos, onde em caso de falta de pe�as o <strong>COMPRA DE PE�AS</strong> dever� acionar a <strong>BLACK & DECKER</strong> informando o atraso para que as devidas an�lises e envio dos itens faltantes sejam providenciados, n�o sendo de responsabilidade da BLACK & Decker a troca ou ressarcimento de valores referentes que fuja das condi��es acima citadas.
                            </p>

                            <p>
                                <strong>CL�USULA QUINTA - DA VIG�NCIA e RESCIS�O</strong>
                            </p>

                            <p>
                                5.1 - O prazo de vig�ncia do presente contrato � indeterminado, a contar da data de assinatura.
                            </p>
                            <p>
                                5.2 - Fica facultada �s partes, a rescis�o antecipada deste instrumento, a qualquer momento, mediante notifica��o por escrito, com anteced�ncia de 30 (trinta) dias, sem direito a qualquer indeniza��o.
                            </p>
                            <p>
                                5.3 - N�o obstante o prazo estipulado na clausula 5.1, o presente contrato se rescindir� de pleno direito, independentemente de notifica��o ou interpela��o judicial ou extrajudicial nas hip�teses abaixo, a saber:
                            </p>
                            <p class='escpaco-clausula'>
                                a. Fal�ncia, dissolu��o ou recupera��o judicial do <strong>COMPRA DE PE�AS</strong> ou da <strong>BLACK & DECKER</strong>; e,
                            </p>

                            <p class='escpaco-clausula'>
                                b. Descumprimento de quaisquer cl�usulas do presente acordo, desde que a parte infratora, devidamente notificada para sanar a falha, n�o o fa�a no prazo de 05 (cinco) dias ap�s o comunicado por escrito.
                            </p>

                            <!-- PARAGRAFO FINAL -->
                            <p>
                                <strong>CL�USULA SEXTA - DAS DISPOSI��ES GERAIS</strong>
                            </p>
                            <p>
                                6.1 - A <strong>BLACK & DECKER</strong> reserva-se o direito de suspender, sem pr�vio aviso e sem qualquer indeniza��o, o fornecimento de quaisquer de seus produtos ao <strong>COMPRA DE PE�AS</strong>, bem como, rescindir o presente acordo, caso este mantenha atitudes n�o condizentes com a �tica comercial, profissional e moral, sem preju�zo do atendimento ao descrito nos demais par�grafos da presente.
                            </p>
                            <p>
                                6.2 - Nos casos de pedidos incorretos feitos pelo <strong>COMPRA DE PE�AS</strong>, seja por quantidade, c�digo ou encerramentos de atividades por parte do <strong>COMPRA DE PE�AS</strong>, a <strong>BLACK & DECKER</strong> n�o se obriga a receber pe�as de seu estoque, em devolu��o, sob qualquer pretexto.
                            </p>
                            <p>
                                6.3 - O <strong>COMPRA DE PE�AS</strong> � o �nico respons�vel pelo pessoal que utilizar na presta��o dos Servi�os de sua sede, devendo satisfazer rigorosamente todas as suas obriga��es trabalhistas, previdenci�rias e sociais em geral em rela��o ao mesmo, mantendo a <strong>BLACK & DECKER</strong> isenta de qualquer reclama��o, d�vida, pend�ncia ou atrito entre ela, <strong>COMPRA DE PE�AS</strong>, e seus empregados ou qualquer outro preposto que utilizar na execu��o deste acordo.
                            </p>
                            <p>
                                6.4 - Fica vedado as partes transferir ou ceder, a qualquer t�tulo, os direitos e obriga��es assumidos nesse acordo.
                            </p>
                            <p>
                                6.5 - Qualquer altera��o, modifica��o, complementa��o, ou ajuste, somente ser� reconhecido e produzir� efeitos legais, se incorporado ao presente acordo mediante termo aditivo, devidamente assinado pela <strong>BLACK & DECKER</strong> e <strong>COMPRA DE PE�AS</strong>.
                            </p>
                            <p>
                                6.6 - Toda e qualquer toler�ncia quanto ao cumprimento por qualquer das partes, das condi��es estabelecidas neste contrato, ser� considerada exce��o, n�o possuindo cond�o de nova��o ou altera��o das disposi��es ora pactuadas, mas t�o somente liberalidade entre <strong>BLACK & DECKER</strong> e <strong>COMPRA DE PE�AS</strong>.
                            </p>
                            <p>
                                6.7 - Para fins de notifica��o, as informa��es dever�o ser formalizadas por escrito, via e-mail, ou quando couber, de outra forma, desde que ratificadas posteriormente por e-mail.
                            </p>
                            <p>
                                6.8 - Este acordo cancela e/ou substitui quaisquer outros acordos, escritos ou verbais, por ventura, existentes, passando a partir dessa data, a ser o �nico instrumento que rege as rela��es entre <strong>COMPRA DE PE�AS</strong> e <strong>BLACK & DECKER</strong>.
                            </p>
                            <p>
                                <strong>CL�USULA S�TIMA - DO FORO</strong>
                            </p>
                            <p>
                                7.1 - As partes elegem o foro da Comarca de Uberaba/MG para dirimir quaisquer d�vidas decorrentes do presente contrato, com exclus�o de qualquer outro, por mais privilegiado que seja.
                            </p>
                            <p>
                                E, por estarem, assim certas e contratadas, assinam as partes o presente acordo, em 02 (duas) vias de igual teor e valor, na presen�a das testemunhas ao final nomeadas para que reproduza os efeitos de direito.
                            </p>

                            <p>
                                Uberaba, $dataHoje
                            </p>

                        </div>

                        <div class='rodape-contrato'>
                            <div class='assinaturas'>
                                <br /> <br />
                                <img src='../image/contratos/ass_black.jpg' width='180px' alt='Ass'>
                                <hr />
                                <strong>BLACK & DECKER DO BRASIL LTDA.</strong> <br />
                                SILVANIA SILVA - GERENTE DE PRODUTOS E SERVI�OS <br /> <br /> <br /> <br />
                                <hr />
                                <strong class='text-upper'>{$posto_nome}</strong>
                            </div>
                            <div class='testemunhas'>
                                <strong>Testemunhas</strong>
                                <br /> <br /> <br /> <br />
                                1)
                                <hr />
                                NOME: <br />
                                RG / CPF:
                                <br /> <br /> <br />
                                2)
                                <hr />
                                NOME: <br />
                                RG / CPF:
                            </div>
                        </div>

                        <div style='clear: both;'></div>

                    </div>

                </body>

            </html>

            ";
            break;
        case "mega projeto":
            $conteudo = "
                <!DOCTYPE html>
                <head>

                    <style>
                        .corpo-contrato{
                            font-family: arial, verdana;
                            font-size: 9px !important;
                            color: #444;
                            width: 100%;
                            background-color: #fff;
                            text-align: justify;
                        }
                        .cabecalho-contrato, .rodape-contrato{
                            width: 100%;
                        }
                        .logo-contrato, .info-contrato, .assinaturas, .testemunhas{
                            width: 45%;
                            float: left;
                            padding-top: 10px;
                            padding-bottom: 10px;
                        }
                        .logo-contrato, assinaturas{
                            padding-right: 15px;
                        }
                        .info-contrato, .testemunhas{
                            padding-left: 30px;
                        }
                        .logo-contrato{
                            text-align: center;
                        }
                        .logo-contrato img{
                            width: 350px;
                        }
                        .info-contrato{
                            border-left: 1px solid #e74c3c;
                            padding-left: 20px;
                        }
                        .conteudo-contrato h3{
                            text-align: center;
                            font-size: 12px !important;
                        }
                        .conteudo-contrato strong{
                            text-transform: uppercase;
                        }
                        .escpaco-clausula{
                            padding-left: 30px;
                        }
                        .escpaco-clausula-2{
                            padding-left: 60px;
                        }
                        .text-upper{
                            text-transform: uppercase;
                        }
                        .rodape-contrato hr{
                            margin-left: 0;
                            width: 80%;
                            margin-top: 20px;
                        }
                        .corLink {
                            color:#00F;
                        }
                    </style>

                </head>

                <body>

                    <div class='corpo-contrato'>

                        <div class='conteudo-contrato'>

                            <h3>
                                INSTRUMENTO PARTICULAR DE ACORDO DE INDUSTRIA/MEGA PROJETO CELEBRADO ENTRE BLACK & DECKER DO BRASIL LTDA., E {$posto_nome}
			    </h3>";
		$sede_filial = ($login_posto = 139472) ? "filial" : "sede";

		$conteudo .= "

                            <p>
                                Pelo presente instrumento particular de um lado <strong>BLACK & DECKER DO BRASIL LTDA.</strong>, pessoa jur�dica de direito privado, com $sede_filial � Rodovia BR 050, s/n�, KM 167, Lote 05 Parte, Quadra 01, Distrito Industrial II, Uberaba - MG, inscrita no CNPJ/MF sob o n�. 53.296.273/0001-91, representada por seus diretores, ao final assinados, e doravante denominada simplesmente <strong>BLACK & DECKER</strong>, e de outro lado <strong>{$posto_nome} </strong> com $sede_filial {$posto_endereco_completo}, inscrita no CNPJ/MF sob o n� {$posto_cnpj}, neste ato representada na forma de seu Contrato Social, denominada simplesmente denominada simplesmente <strong>INDUSTRIA/MEGA PROJETO</strong>, firmam o presente acordo regulado pelas cl�usulas seguintes:
                            </p>

                            <p>
                                <strong>CL�USULA PRIMEIRA - DO OBJETO</strong>
                            </p>

                            <p>
                                1.1 - O <strong>INDUSTRIA/MEGA PROJETO</strong> possuir� acesso ao sistema Telecontrol, com endere�o eletr�nico <u class='corLink'>www.telecontrol.com.br</u>, para que efetue compras de pe�as, esse procedimento � denominado \"INDUSTRIA/MEGA PROJETO\".
                            </p>

                            <p>
                                <strong>CL�USULA SEGUNDA - DO ACESSO</strong>
                            </p>

                            <p>
                                2.1 - A <strong>BLACK & DECKER</strong> criar�  um c�digo de cadastro para identifica��o do <strong>INDUSTRIA/MEGA PROJETO</strong>.
                            </p>

                            <p class='escpaco-clausula'>
                                2.1.1 - O <em>\"login\"</em> de acesso ao sistema da <strong>BLACK & DECKER</strong> ser� criado assim que o <strong>INDUSTRIA/MEGA PROJETO</strong> enviar o presente instrumento devidamente assinado.
                            </p>

                            <p class='escpaco-clausula'>
                                2.1.2 - Para concluir o acesso, o <strong>INDUSTRIA/MEGA PROJETO</strong> dever� entrar no site <u class='corLink'>www.telecontrol.com.br</u>, onde encontrar� as vistas explodidas dos produtos, tela para pedidos de pe�as, acesso � tabela de pre�os, informa��es t�cnicas e relat�rios gerenciais.
                            </p>

                            <p>
                                <strong>CL�USULA TERCEIRA - DA RESPONSABILIDADE DO INDUSTRIA/MEGA PROJETO</strong>
                            </p>

                            <p>
                                3.1 - � de responsabilidade do <strong>INDUSTRIA/MEGA PROJETO</strong>:
                            </p>

                            <p class='escpaco-clausula'>
                                3.1.1 - Manter contato com a <strong>BLACK & DECKER</strong> em caso de d�vidas para que esta possa lhes prestar o devido auxilio;
                            </p>

                            <p class='escpaco-clausula'>
                                3.1.2 - Saldar em dia seus compromissos financeiros com a <strong>BLACK & DECKER</strong>;
                            </p>

                            <p class='escpaco-clausula'>
                                3.1.3 - Autorizar inspe��es, bem como, auditorias efetuadas por pessoas indicadas pela <strong>BLACK & DECKER</strong>;
                            </p>
                            <p class='escpaco-clausula'>
                                3.1.4 - Comunicar imediatamente ao setor de cadastro da <strong>BLACK & DECKER</strong> as altera��es tais como:
                            </p>

                            <p class='escpaco-clausula-2'>
                                a. Mudan�a de Raz�o Social ou quaisquer altera��es societ�rias, incluindo mas n�o se limitando ao Contrato Social;
                            </p>
                            <p class='escpaco-clausula-2'>
                                b. Altera��o de Inscri��o Estadual e/ou CNPJ;
                            </p>
                            <p class='escpaco-clausula-2'>
                                c. Mudan�as de endere�o / telefone / contato / e-mail;
                            </p>
                            <p class='escpaco-clausula-2'>
                                d. Abertura de filiais;
                            </p>
                            <p class='escpaco-clausula-2'>
                                e. Altera��o de Capital Social;
                            </p>
                            <p class='escpaco-clausula-2'>
                                f. Altera��o de Dados Banc�rios;
                            </p>

                            <p>
                                <strong>CL�USULA QUARTA - DAS RESPONSABILIDADES DA BLACK & DECKER</strong>
                            </p>

                            <p>
                                4.1 - O <strong>INDUSTRIA/MEGA PROJETO</strong> responsabiliza-se por todos os encargos fiscais e tribut�rios federais, estaduais e municipais, decorrentes da sua atividade, inclusive encargos trabalhistas, emolumentos e taxas relativas ao munic�pio incidentes sobre placas e propaganda.
                            </p>

                            <p>
                                4.2 - A <strong>BLACK & DECKER</strong> tem por responsabilidade atender seus clientes no envio de pe�as, dentro do melhor prazo poss�vel, garantindo assim satisfa��o dos mesmos, onde em caso de falta de pe�as o <strong>INDUSTRIA/MEGA PROJETO</strong> dever� acionar a <strong>BLACK & DECKER</strong> informando o atraso para que as devidas an�lises e envio dos itens faltantes sejam providenciados, n�o sendo de responsabilidade da BLACK & Decker a troca ou ressarcimento de valores referentes que fuja das condi��es acima citadas.
                            </p>

                            <p>
                                <strong>CL�USULA QUINTA - DA VIG�NCIA e RESCIS�O</strong>
                            </p>

                            <p>
                                5.1 - O prazo de vig�ncia do presente contrato � indeterminado, a contar da data de assinatura.
                            </p>
                            <p>
                                5.2 - Fica facultada �s partes, a rescis�o antecipada deste instrumento, a qualquer momento, mediante notifica��o por escrito, com anteced�ncia de 30 (trinta) dias, sem direito a qualquer indeniza��o.
                            </p>
                            <p>
                                5.3 - N�o obstante o prazo estipulado na clausula 5.1, o presente contrato se rescindir� de pleno direito, independentemente de notifica��o ou interpela��o judicial ou extrajudicial nas hip�teses abaixo, a saber:
                            </p>
			    <p class='escpaco-clausula'>";
				
				$artigo53a = ($login_posto == 139472) ? ' ou dissolu��o' : ', dissolu��o ou recupera��o judicial';
			    $conteudo .= "
                                a. Fal�ncia $artigo53a do <strong>INDUSTRIA/MEGA PROJETO</strong> ou da <strong>BLACK & DECKER</strong>; e,
                            </p>

                            <p class='escpaco-clausula'>
                                b. Descumprimento de quaisquer cl�usulas do presente acordo, desde que a parte infratora, devidamente notificada para sanar a falha, n�o o fa�a no prazo de 05 (cinco) dias ap�s o comunicado por escrito.
                            </p>

                            <!-- PARAGRAFO FINAL -->
                            <p>
                                <strong>CL�USULA SEXTA - DAS DISPOSI��ES GERAIS</strong>
                            </p>
                            <p>
                                6.1 - A <strong>BLACK & DECKER</strong> reserva-se o direito de suspender, sem pr�vio aviso e sem qualquer indeniza��o, o fornecimento de quaisquer de seus produtos ao <strong>INDUSTRIA/MEGA PROJETO</strong>, bem como, rescindir o presente acordo, caso este mantenha atitudes n�o condizentes com a �tica comercial, profissional e moral, sem preju�zo do atendimento ao descrito nos demais par�grafos da presente.
                            </p>
                            <p>
                                6.2 - Nos casos de pedidos incorretos feitos pelo <strong>INDUSTRIA/MEGA PROJETO</strong>, seja por quantidade, c�digo ou encerramentos de atividades por parte do <strong>INDUSTRIA/MEGA PROJETO</strong>, a <strong>BLACK & DECKER</strong> n�o se obriga a receber pe�as de seu estoque, em devolu��o, sob qualquer pretexto.
                            </p>
                            <p>
                                6.3 - O <strong>INDUSTRIA/MEGA PROJETO</strong> � o �nico respons�vel pelo pessoal que utilizar na presta��o dos Servi�os de sua sede, devendo satisfazer rigorosamente todas as suas obriga��es trabalhistas, previdenci�rias e sociais em geral em rela��o ao mesmo, mantendo a <strong>BLACK & DECKER</strong> isenta de qualquer reclama��o, d�vida, pend�ncia ou atrito entre ela, <strong>INDUSTRIA/MEGA PROJETO</strong>, e seus empregados ou qualquer outro preposto que utilizar na execu��o deste acordo.
                            </p>
                            <p>
                                6.4 - Fica vedado as partes transferir ou ceder, a qualquer t�tulo, os direitos e obriga��es assumidos nesse acordo.
                            </p>
                            <p>
                                6.5 - Qualquer altera��o, modifica��o, complementa��o, ou ajuste, somente ser� reconhecido e produzir� efeitos legais, se incorporado ao presente acordo mediante termo aditivo, devidamente assinado pela <strong>BLACK & DECKER</strong> e <strong>INDUSTRIA/MEGA PROJETO</strong>.
                            </p>
                            <p>
                                6.6 - Toda e qualquer toler�ncia quanto ao cumprimento por qualquer das partes, das condi��es estabelecidas neste contrato, ser� considerada exce��o, n�o possuindo cond�o de nova��o ou altera��o das disposi��es ora pactuadas, mas t�o somente liberalidade entre <strong>BLACK & DECKER</strong> e <strong>INDUSTRIA/MEGA PROJETO</strong>.
                            </p>
                            <p>
                                6.7 - Para fins de notifica��o, as informa��es dever�o ser formalizadas por escrito, via e-mail, ou quando couber, de outra forma, desde que ratificadas posteriormente por e-mail.
                            </p>
                            <p>
                                6.8 - Este acordo cancela e/ou substitui quaisquer outros acordos, escritos ou verbais, por ventura, existentes, passando a partir dessa data, a ser o �nico instrumento que rege as rela��es entre <strong>INDUSTRIA/MEGA PROJETO</strong> e <strong>BLACK & DECKER</strong>.
                            </p>
                            <p>
                                <strong>CL�USULA S�TIMA - DO FORO</strong>
                            </p>
			    <p>";
				$comarca = ($login_posto == 139472) ? 'de S�o Jo�o da Boa Vista/SP' : 'da Comarca de Uberaba/MG';
			    $conteudo .= "
                                7.1 - As partes elegem o foro $comarca para dirimir quaisquer d�vidas decorrentes do presente contrato, com exclus�o de qualquer outro, por mais privilegiado que seja.
                            </p>
                            <p>
                                E, por estarem, assim certas e contratadas, assinam as partes o presente acordo, em 02 (duas) vias de igual teor e valor, na presen�a das testemunhas ao final nomeadas para que reproduza os efeitos de direito.
                            </p>

                            <p>
                                Uberaba, $dataHoje
                            </p>

                        </div>

                        <div class='rodape-contrato'>
                            <div class='assinaturas'>
                                <br /> <br />
                                <img src='../image/contratos/ass_black.jpg' width='180px' alt='Ass'>
                                <hr />
                                <strong>BLACK & DECKER DO BRASIL LTDA.</strong> <br />
                                SILVANIA SILVA - GERENTE DE PRODUTOS E SERVI�OS <br /> <br /> <br /> <br />
                                <hr />
                                <strong class='text-upper'>{$posto_nome}</strong>
                            </div>
                            <div class='testemunhas'>
                                <strong>Testemunhas</strong>
                                <br /> <br /> <br /> <br />
                                1)
                                <hr />
                                NOME: <br />
                                RG / CPF:
                                <br /> <br /> <br />
                                2)
                                <hr />
                                NOME: <br />
                                RG / CPF:
                            </div>
                        </div>

                        <div style='clear: both;'></div>

                    </div>

                </body>

                </html>

            ";
            break;
    }

    return $conteudo;
}

function imprimir($con,$login_fabrica,$login_posto,$posto_codigo,$posto_nome,$posto_categoria,$posto_endereco_completo,$posto_cnpj,$posto_email)
{
    include_once "classes/mpdf61/mpdf.php";
    include_once 'class/communicator.class.php';
    require_once 'class/tdocs.class.php';

    $arquivo = "/tmp/contrato_servico_".str_replace(" ","_",$posto_categoria)."_{$login_posto}.pdf";

    $conteudo_cabecalho = gerarCabecalho();
    $conteudo_rodape = gerarRodape($posto_nome,$posto_categoria);
    $conteudo = gerarCorpo($posto_codigo,$posto_nome,$posto_categoria,$posto_endereco_completo,$posto_cnpj,$con,$login_posto,$login_fabrica);

    $mpdf = new mPDF("", "A4", "", "", "15", "15", "32", "22");
    $mpdf->SetDisplayMode('fullpage');
    $mpdf->forcePortraitHeaders = true;
    $mpdf->charset_in = 'windows-1252';
    $mpdf->SetHTMLHeader($conteudo_cabecalho);
    $mpdf->SetHTMLFooter(utf8_encode($conteudo_rodape));
    $mpdf->WriteHTML($conteudo);
    $mpdf->Output($arquivo, "F");

    date_default_timezone_set("America/Sao_Paulo");

    $assunto = "Aviso de download de contrato de presta��o de servi�o - {$posto_nome} - Telecontrol";
    $mensagem = "
        Prezados, <br />
        informamos que o posto <strong>{$posto_codigo} - {$posto_nome}</strong> (CNPJ: {$posto_cnpj}) recebeu o contrato de presta��o de servi�os
        atrav�s do sistema da Telecontrol. <br />
        Data: ".date("d/m/Y H:i:s")."
    ";

    $sql_admin = "SELECT email from tbl_admin where fabrica = $login_fabrica and responsavel_postos is true and ativo is true ";
    $res_admin = pg_query($con, $sql_admin);

    for($a = 0; $a < pg_num_rows($res_admin); $a++){
        $email = pg_fetch_result($res_admin, $a, 'email');
        $mailTc = new TcComm('smtp@posvenda');
        $res = $mailTc->sendMail(
            $email,
            $assunto,
            $mensagem,
            'no-reply@telecontrol.com.br'
        );
    }

    $headers = '';

	if ($login_fabrica == 1) {
		$headers .= "Bcc: cadastro@sbdbrasil.com.br\r\n";
	}

    $headers .= "From: no-reply@telecontrol.com.br";

    $semi_rand = md5(time());
    $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";

    $headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\"";

    $message = "This is a multi-part message in MIME format.\n\n" . "--{$mime_boundary}\n" . "Content-Type: text/html; charset=\"iso-8859-1\"\n" . "Content-Transfer-Encoding: 7bit\n\n" . $message . "\n\n";
    $message .= "--{$mime_boundary}\n";
    $file1 = $arquivo;
    $files = array($file1);

    for ($x=0;$x<count($files);$x++) {

        $file = fopen($files[$x],"rb");
        $data = fread($file,filesize($files[$x]));

        fclose($file);

        $data = chunk_split(base64_encode($data));

        $message .= "Content-Type: {\"application/octet-stream\"};\n" . " name=\"contrato.pdf\"\n" .
        "Content-Disposition: attachment;\n" . " filename=\"contrato.pdf\"\n" .
        "Content-Transfer-Encoding: base64\n\n" . $data . "\n\n";
        $message .= "--{$mime_boundary}\n";
    }

    $to = $posto_email;
    mail($to, $assunto , $message, $headers);

    return true;
}

?>
