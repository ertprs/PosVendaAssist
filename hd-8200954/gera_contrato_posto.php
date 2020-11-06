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
            $mes = " de Março de ";
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
                        <strong> Página {PAGENO} de 3 </strong>
                    </div>
                ";
            break;
        case "Autorizada":
            $rod = "
                    <div style='text-align: center; font-family: arial, verdana; font-size: 8px !important;'>
                        INSTRUMENTO PARTICULAR DE ACORDO DE PRESTAÇÃO DE SERVIÇOS CELEBRADO ENTRE BLACK & DECKER DO BRASIL LTDA., E <strong>{$posto_nome}</strong>.
                        <br />
                        <strong> Página {PAGENO} de 3 </strong>
                    </div>
                ";
            break;
        case "Compra Peca":
            $rod = "
                    <div style='text-align: center; font-family: arial, verdana; font-size: 8px !important;'>
                        INSTRUMENTO PARTICULAR DE ACORDO DE COMPRA DE PEÇAS CELEBRADO ENTRE BLACK & DECKER DO BRASIL LTDA., E <strong>{$posto_nome}</strong>.
                        <br />
                        <strong> Página {PAGENO} de 3 </strong>
                    </div>
                ";
            break;
        case "mega projeto":
            $rod = "
                    <div style='text-align: center; font-family: arial, verdana; font-size: 8px !important;'>
                        INSTRUMENTO PARTICULAR DE ACORDO DE INDUSTRIA/MEGA PROJETO CELEBRADO ENTRE BLACK & DECKER DO BRASIL LTDA., E <strong>{$posto_nome}</strong>.
                        <br />
                        <strong> Página {PAGENO} de 3 </strong>
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
                                Pelo presente instrumento particular de um lado <strong>BLACK & DECKER DO BRASIL LTDA.</strong>, pessoa jurídica de direito privado, com sede à Rodovia BR 050, s/nº, KM 167, Lote 05 Parte, Quadra 01, Distrito Industrial II, Uberaba - MG, inscrita no CNPJ/MF sob o nº. 53.296.273/0001-91, representada por seus diretores, ao final assinados, e doravante denominada simplesmente <strong>BLACK & DECKER</strong>, e de outro lado <strong>{$posto_nome}</strong> com sede {$posto_endereco_completo}, inscrita no CNPJ/MF sob o nº {$posto_cnpj}, neste ato representada na forma de seu Contrato Social, denominada simplesmente denominada simplesmente <strong>LOCADORA</strong>, firmam o presente acordo <em>\"DeWALT Rental\"</em>, regulado pelas cláusulas seguintes:
                            </p>

                            <p>
                                <strong>CLÁUSULA PRIMEIRA - DO OBJETO</strong>
                            </p>

                            <p>
                                1.1 - As locadoras que adquirirem Ferramentas DEWALT direto da <strong>BLACK & DECKER</strong>, e ao realizar o cadastro junto à mesma, terão acesso ao sistema Telecontrol, com endereço eletrônico <u class='corLink'>www.telecontrol.com.br</u>, para que efetuem compras de peças.
                            </p>

                            <p>
                                <strong>CLÁUSULA SEGUNDA - DO ACESSO</strong>
                            </p>

                            <p>
                                2.1 - A <strong>BLACK & DECKER</strong> criará para a <strong>LOCADORA</strong>, um código de cadastro.
                            </p>

                            <p class='escpaco-clausula'>
                                2.1.1 - O <em>\"login\"</em> de acesso ao sistema da <strong>BLACK & DECKER</strong> será criado assim que a <strong>LOCADORA</strong> enviar o presente instrumento devidamente assinado.
                            </p>

                            <p class='escpaco-clausula'>
                                2.1.2 - Para concluir o acesso a <strong>LOCADORA</strong> deverá entrar no site <u class='corLink'>www.telecontrol.com.br</u>, onde encontrará as vistas explodidas dos produtos, tela para pedidos de peças, acesso à tabela de preços, informações técnicas e relatórios gerenciais.
                            </p>

                            <p>
                                <strong>CLÁUSULA TERCEIRA - DAS OBRIGAÇÕES E RESPONSABILIDADES DA LOCADORA</strong>
                            </p>

                            <p>
                                3.1 Para o bom desempenho de vossas atividades, a <strong>LOCADORA</strong> deverá:
                            </p>

                            <p class='escpaco-clausula'>
                                3.1.1 - Manter estoque de peças de reposição de maior giro da <strong>BLACK & DECKER</strong>, que serão utilizadas para aplicação nos consertos de seus produtos;
                            </p>

                            <p class='escpaco-clausula'>
                                3.1.2 - Possuir o equipamento mínimo necessário para conserto, bem como as ferramentas básicas para aplicação nos consertos de seus produtos;
                            </p>

                            <p class='escpaco-clausula'>
                                3.1.3 - Possuir o equipamento mínimo necessário para conserto, bem como as ferramentas básicas para reparo, objetivando garantir serviço rápido e eficiente, além de garantir assim o bom reparo das ferramentas;
                            </p>

                            <p class='escpaco-clausula'>
                                3.1.4 - Participar, sempre que convocado, de cursos de treinamento organizados ou ministrados pela <strong>BLACK & DECKER</strong>;
                            </p>
                            <p class='escpaco-clausula'>
                                3.1.5 - Manter contato com o atendimento fábrica da <strong>BLACK & DECKER</strong> em caso de dúvidas, para que esta possa prestar o devido auxilio;
                            </p>
                            <p class='escpaco-clausula'>
                                3.1.6 - Saldar em dia seus compromissos financeiros com a <strong>BLACK & DECKER</strong>;
                            </p>
                            <p class='escpaco-clausula'>
                                3.1.7 - Autorizar inspeções e auditoria, efetuadas por pessoas indicadas pela <strong>BLACK & DECKER</strong>;
                            </p>
                            <p class='escpaco-clausula'>
                                3.1.8 - Possuir organização Administrativa e fiscal condizente com o volume do negócio a ser desenvolvido;
                            </p>
                            <p class='escpaco-clausula'>
                                3.1.9 - Comunicar imediatamente ao setor de cadastro da Black e Decker as alterações tais como:
                            </p>

                            <p class='escpaco-clausula-2'>
                                a. Mudança de Razão Social ou quaisquer alterações societárias, incluindo mas não se limitando ao Contrato Social;
                            </p>
                            <p class='escpaco-clausula-2'>
                                b. Alteração de Inscrição Estadual e/ou CNPJ;
                            </p>
                            <p class='escpaco-clausula-2'>
                                c. Mudanças de endereço / telefone / contato / e-mail;
                            </p>
                            <p class='escpaco-clausula-2'>
                                d. Abertura de filiais;
                            </p>
                            <p class='escpaco-clausula-2'>
                                e. Alteração de Capital Social;
                            </p>
                            <p class='escpaco-clausula-2'>
                                f. Alteração de Dados Bancários;
                            </p>

                            <p class='escpaco-clausula'>
                                3.1.10 - A <strong>LOCADORA</strong> não poderá incluir nos materiais promocionais fornecidos pela <strong>BLACK & DECKER</strong> quaisquer outras inscrições, mesmo que não signifiquem concorrência, direta ou indireta, aos produtos da <strong>BLACK & DECKER</strong>;
                            </p>
                            <p class='escpaco-clausula'>
                                3.1.11 - A <strong>LOCADORA</strong> responsabiliza-se por todos os encargos fiscais e tributários federais, estaduais e municipais, decorrentes da sua atividade, inclusive, mas não se limitando a encargos trabalhistas, emolumentos e taxas relativas ao município incidentes sobre placas e propagandas.
                            </p>
                            <p class='escpaco-clausula'>
                                3.1.12 - A <strong>BLACK & DECKER</strong> não se responsabilizará por problemas ocasionados pelo não cumprimento pela <strong>LOCADORA</strong>, de obrigações, tais como:
                            </p>
                            <p class='escpaco-clausula-2'>
                                a. Produtos parados em sua locadora, ocasionados pela morosidade na colocação de pedidos de peças ou pedidos inseridos erroneamente junto ao sistema disponibilizado pela <strong>BLACK & DECKER</strong>;
                            </p>
                            <p class='escpaco-clausula-2'>
                                b. Reparos executados sem qualidade e/ou com negligência;
                            </p>
                            <p>
                                <strong>CLÁUSULA QUARTA - DA GARANTIA E SUAS CONDIÇÕES</strong>
                            </p>

                            <p>
                                4.1 - A <strong>LOCADORA</strong> tem como diferencial o preço <em>\"VIP DeWALT\"</em> para compras de peças com desconto diferenciado de sobre a tabela de preço sugerido pela <strong>BLACK & DECKER</strong>. O site <u class='corLink'>www.telecontrol.com.br</u>, apresentará sempre os preços atualizados quando da inserção do pedido de peças por parte da <strong>LOCADORA</strong>. Recomenda-se que a <strong>LOCADORA</strong> tenha um estoque de segurança de itens de maior giro.
                            </p>

                            <p>
                                4.2 - A <strong>BLACK & DECKER</strong> reserva-se o direito de alterar, suspender ou cancelar quaisquer itens disponíveis, a qualquer tempo e ao seu exclusivo critério, sem que disto resulte em qualquer reclamação e/ou indenização à <strong>LOCADORA</strong>.
                            </p>

                            <p>
                                4.3 - A <strong>BLACK & DECKER</strong> poderá a seu exclusivo critério e com prévio agendamento, disponibilizar à <strong>LOCADORA</strong> material de divulgação (catálogos e banners), apoio da equipe de promoção técnica para treinamento dos seus funcionários de modo a estarem aptos a prestar esclarecimentos sobre utilização dos produtos voltados para locação, e treinamento técnico para manutenção dos equipamentos adquiridos.
                            </p>

                            <p>
                                4.4 - O prazo de garantia para todos os produtos voltados para locação, é de 06 (seis) meses, a partir da emissão de Nota Fiscal. Sendo aplicada para todas as marcas voltadas para esse segmento (DEWALT, BLACK & DECKER, PORTER CABLE e STANLEY HIDRAULIC e PNEUMATICA) sendo de responsabilidade da <strong>BLACK & DECKER</strong> o envio das peças neste período, mediante pedido inserido no sistema Telecontrol, onde a <strong>LOCADORA</strong> deverá informar o número de serie de sua ferramenta para finalização do pedido em garantia e posterior envio da(s) peça(s).
                            </p>

                            <p>
                                4.5 - Fica sob responsabilidade da <strong>LOCADORA</strong> o reparo de suas ferramentas em sua própria oficina, sem que disto resulte qualquer ressarcimento de mão de obra por parte da <strong>BLACK & DECKER</strong>.
                            </p>

                            <p>
                                4.6 - A <strong>LOCADORA</strong> é totalmente responsável pela inserção de pedidos no sistema Telecontrol para envio de peças, tanto para ferramentas que estejam no período de garantia como ferramentas fora do período de garantia, acompanhando assim o envio das peças até a sede de sua empresa, onde em caso de atraso de seus pedidos caberá à <strong>LOCADORA</strong> fazer contato com a <strong>BLACK & DECKER</strong> para que as ações necessárias sejam tomadas.
                            </p>

                            <p>
                                4.7 - A <strong>BLACK & DECKER</strong> tem por responsabilidade atender seus clientes no envio de peças, dentro do melhor prazo possível, garantindo assim satisfação dos mesmos, onde em caso de falta de peças, a <strong>LOCADORA</strong> deverá acionar a <strong>BLACK & DECKER</strong>, informando o atraso para que as devidas análises e envio dos itens faltantes sejam providenciados, não sendo de responsabilidade da BLACK & DEKCER a troca ou ressarcimento de valores referentes à locação que fuja das condições acima citadas.
                            </p>

                            <p>
                                4.8 - Caso a <strong>LOCADORA</strong> não possua um técnico em seu estabelecimento, caberá a este, informar a <strong>BLACK & DECKER</strong> de tal situação, para que esta priorize o treinamento para o técnico responsável afim de que a própria <strong>LOCADORA</strong> faça o reparo de seus produtos, conforme política do vigente em nosso Projeto Rental.
                            </p>

                            <p>
                                4.9 - Caso a locadorA não possua estrutura técnica / manutenção, o mesmo poderá contar com o atendimento da rede autorizada indicada pela <strong>BLACK & DECKER</strong>, sendo de inteira responsabilidade da <strong>LOCADORA</strong>, realizar o pedido de peças tanto para produtos em garantia quanto orçamentos, bem como, encaminhar as peças à referida assistência técnica assim que as mesmas forem entregues em sua locadora.
                            </p>

                            <p>
                                4.10 - Nos casos de atendimento realizado por uma de nossas assistências técnicas, o prazo para reparo e, entrega das ferramentas não será de responsabilidade da <strong>BLACK & DECKER</strong>, permanecendo esta à disposição, para auxílio nas dificuldades e/ou dúvidas junto aos prestadores de serviços assistenciais.
                            </p>

                            <p>
                                4.11 - Para a <strong>LOCADORA</strong> que optar em proceder com reparo de seus produtos por meio das assistências técnicas autorizadas <strong>BLACK & DECKER</strong>, será este responsável pelo pagamento referente aos serviços prestados, bem como, por orçamentos e valores de mão-de-obra, conforme valor previamente estabelecido pelas supracitadas assistências técnicas, não cabendo a <strong>BLACK & DECKER</strong> nenhuma responsabilidade por esses serviços.
                            </p>

                            <p>
                                4.12 - As peças trocadas em Garantia (DEWALT Rental), deverão ser mantidas à disposição da <strong>BLACK & DECKER</strong>, pelo período de 03 meses (três) após emissão de nota fiscal de compra, para que a <strong>BLACK & DECKER</strong>, possa realizar a inspeção e/ou remoção quando possível, sendo que, somente após o prazo acima citado as peças poderão ser sucateadas pela <strong>LOCADORA</strong>.
                            </p>

                            <p>
                                4.13 - Caso a <strong>BLACK & DECKER</strong> solicite as peças, e a <strong>LOCADORA</strong> por ventura não as possua, a <strong>BLACK & DECKER</strong> terá o pleno direito de adverti-lo formalmente e em caso de reincidência rescindir o presente acordo.
                            </p>

                            <p>
                                4.14 - A <strong>LOCADORA</strong> que possua equipamentos da linha <em>\"Stanley Hidraulic\"</em>, tem por responsabilidade proceder com o reparo das ferramentas que o mesmo possui , cabendo à <strong>LOCADORA</strong> proceder com a implantação e acompanhamento de pedidos das peças necessárias para reparo de suas ferramentas, sendo que, em caso de atraso em seus pedidos caberá à <strong>LOCADORA</strong> fazer contato com a <strong>BLACK & DECKER</strong> para que as ações necessárias sejam tomadas.
                            </p>

                            <p>
                                4.15 - A <strong>BLACK & DECKER</strong> não se responsabiliza pelo reparo de produtos da linha Stanley Hidraulic feitos por terceiros tanto quando pelo prazo gasto com o conserto, respaldando-se assim de qualquer ressarcimento de valores referentes à locação ou troca dos produtos que fuja das condições acima citadas.
                            </p>

                            <p>
                                4.16 - A <strong>BLACK & DECKER</strong> não será responsável pela substituição ou restituição de valores referentes à troca de óleo, fluidos hidráulicos e recarga de nitrogênio dos equipamentos da linha hidráulica, mesmo para equipamentos que estejam em período de garantia, por se tratar de itens com consumo ou desgaste natural.
                            </p>

                            <p>
                                4.17 - A <strong>BLACK & DECKER</strong> não receberá para reparo produtos da locadorA de qualquer dos segmentos (DEWALT, BLACK & DECKER, PORTER CABLE e STANLEY HIDRAULIC e PNEUMATICA), pois o mesmo oferece todas as condições para que a <strong>LOCADORA</strong> proceda com os reparos de suas ferramentas como treinamentos na fábr
                            </p>

                            <p>
                                <strong>CLÁUSULA QUINTA - DA VIGÊNCIA E RESCISÃO</strong>
                            </p>

                            <p>
                                5.1 - O prazo de vigência do presente contrato é indeterminado, a contar da data de assinatura.
                            </p>
                            <p>
                                5.2 - Fica facultada às partes, a rescisão antecipada deste instrumento, a qualquer momento, mediante notificação por escrito, com antecedência de 30 (trinta) dias, sem direito a qualquer indenização.
                            </p>
                            <p>
                                5.3 - Não obstante o prazo estipulado na clausula 5.1, o presente contrato se rescindirá de pleno direito, independentemente de notificação ou interpelação judicial ou extrajudicial nas hipóteses abaixo, a saber:
                            </p>

                            <p class='escpaco-clausula-2'>
                                a) Falência, dissolução ou recuperação judicial da <strong>LOCADORA</strong> ou da <strong>BLACK & DECKER</strong>; e,
                            </p>
                            <p class='escpaco-clausula-2'>
                                b) Descumprimento de quaisquer cláusulas do presente acordo, desde que a parte infratora, devidamente notificada para sanar a falha, não o faça no prazo de 05 (cinco) dias após o comunicado por escrito.
                            </p>
                            <p>
                                5.4 - Findo o presente acordo, a <strong>LOCADORA</strong> se comprometerá liquidar suas pendências financeiras junto a <strong>BLACK & DECKER</strong> e, obrigando-se a não fazer uso do nome ou logomarca da <strong>BLACK & DECKER</strong>, ou seja, monogramas em seus documentos, letreiros, cartazes ou quaisquer outros meios de comunicação.
                            </p>

                            <p>
                                <strong>CLÁUSULA SEXTA - DAS DISPOSIÇÕES GERAIS</strong>
                            </p>

                            <p>
                                6.1 - A <strong>BLACK & DECKER</strong> reserva-se o direito de suspender, sem prévio aviso e sem qualquer indenização, o fornecimento de quaisquer de seus produtos à <strong>LOCADORA</strong>, bem como, rescindir o presente acordo, caso este mantenha atitudes não condizentes com a ética comercial, profissional e moral, sem prejuízo do atendimento ao descrito nos demais parágrafos da presente.
                            </p>

                            <p>
                                6.2 - Nos casos de pedidos incorretos feitos pela <strong>LOCADORA</strong>, seja por quantidade, código ou encerramentos de atividades por parte da <strong>LOCADORA</strong>, a <strong>BLACK & DECKER</strong> não se obriga a receber peças de seu estoque, em devolução, sob qualquer pretexto.
                            </p>

                            <p>
                                6.3 - A <strong>LOCADORA</strong> é o único responsável pelo pessoal que utilizar na prestação dos Serviços de sua sede, devendo satisfazer rigorosamente todas as suas obrigações trabalhistas, previdenciárias e sociais em geral em relação ao mesmo, mantendo a <strong>BLACK & DECKER</strong> isenta de qualquer reclamação, dúvida, pendência ou atrito entre ela, <strong>LOCADORA</strong>, e seus empregados ou qualquer outro preposto que utilizar na execução deste acordo.
                            </p>
                            <p>
                                6.4 - Fica vedado as partes transferir ou ceder, a qualquer título, os direitos e obrigações assumidos nesse acordo.
                            </p>
                            <p>
                                6.5 - Qualquer alteração, modificação, complementação, ou ajuste, somente será reconhecido e produzirá efeitos legais, se incorporado ao presente acordo mediante termo aditivo, devidamente assinado pela <strong>BLACK & DECKER</strong> e <strong>LOCADORA</strong>.
                            </p>
                            <p>
                                6.6 - Toda e qualquer tolerância quanto ao cumprimento por qualquer das partes, das condições estabelecidas neste contrato, será considerada exceção, não possuindo condão de novação ou alteração das disposições ora pactuadas, mas tão somente liberalidade entre <strong>BLACK & DECKER</strong> e <strong>LOCADORA</strong>.
                            </p>
                            <p>
                                6.7 - Para fins de notificação, as informações deverão ser formalizadas por escrito, via e-mail, ou quando couber, de outra forma, desde que ratificadas posteriormente por e-mail.
                            </p>
                            <p>
                                6.8 - Este acordo cancela e/ou substitui quaisquer outros acordos, escritos ou verbais, por ventura, existentes, passando a partir dessa data, a ser o único instrumento que rege as relações entre <strong>LOCADORA</strong> e <strong>BLACK & DECKER</strong>.
                            </p>

                            <p>
                                <strong>CLÁUSULA SÉTIMA - DO FORO</strong>
                            </p>

                            <p>
                                7.1 - As partes elegem o foro da Comarca de Uberaba/MG para dirimir quaisquer dúvidas decorrentes do presente contrato, com exclusão de qualquer outro, por mais privilegiado que seja.
                            </p>
                            <p>
                                E, por estarem, assim certas e contratadas, assinam as partes o presente acordo, em 02 (duas) vias de igual teor e valor, na presença das testemunhas ao final nomeadas para que reproduza os efeitos de direito.
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
                                SILVANIA SILVA - GERENTE DE PRODUTOS E SERVIÇOS <br /> <br /> <br /> <br />
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
                                    INSTRUMENTO PARTICULAR DE ACORDO DE PRESTAÇÃO DE SERVIÇOS CELEBRADO ENTRE BLACK & DECKER DO BRASIL LTDA., E {$posto_nome}
                                </h3>

                                <p>
                                    Pelo presente instrumento particular de um lado <strong>BLACK & DECKER DO BRASIL LTDA.</strong>, pessoa jurídica de direito privado, com sede à Rodovia BR 050, s/nº, KM 167, Lote 05 Parte, Quadra 01, Distrito Industrial II, Uberaba - MG, inscrita no CNPJ/MF sob o nº. 53.296.273/0001-91, representada por seus diretores, ao final assinados, e doravante denominada simplesmente <strong>BLACK & DECKER</strong>, e de outro lado <strong>{$posto_nome}</strong> com sede {$posto_endereco_completo}, inscrita no CNPJ/MF sob o nº {$posto_cnpj}, neste ato representada na forma de seu Contrato Social, denominada simplesmente <strong>POSTO AUTORIZADO</strong>, firmam o presente acordo regulado pelas cláusulas seguintes:
                                </p>

                                <p>
                                    <strong>CLÁUSULA PRIMEIRA - DO OBJETO</strong>
                                </p>

                                <p>
                                    A <strong>BLACK & DECKER</strong> nomeia como posto autorizado Black & Decker do Brasil Ltda. $posto_nome, cujo objeto do presente acordo, será a prestação de serviços de assistência técnica a consumidores e revendedores, para as linhas de produtos, fabricados pela BLACK & DECKER, quais sejam: $posto_linhas
                                </p>

                                <p>
                                    <strong>CLÁUSULA SEGUNDA - DO ACESSO</strong>
                                </p>

                                <p>
                                    2.1 - A <strong>BLACK & DECKER</strong> criará  um código de cadastro para identificação do <strong>POSTO AUTORIZADO</strong>.
                                </p>

                                <p class='escpaco-clausula'>
                                    2.1.1 - O <em>\"login\"</em> de acesso ao sistema da <strong>BLACK & DECKER</strong> será criado assim que o <strong>POSTO AUTORIZADO</strong> enviar o presente instrumento devidamente assinado.
                                </p>

                                <p class='escpaco-clausula'>
                                    2.1.2 - Para concluir o acesso, o <strong>POSTO AUTORIZADO</strong> deverá entrar no site <u class='corLink'>www.telecontrol.com.br</u>, onde encontrará as vistas explodidas dos produtos, tela para pedidos de peças, acesso à tabela de preços, informações técnicas e relatórios gerenciais.
                                </p>

                                <p>
                                    <strong>CLÁUSULA TERCEIRA - DO ATENDIMENTO A REVENDAS</strong>
                                </p>

                                <p>
                                    3.1 Para o bom desempenho de vossas atividades, o <strong>POSTO AUTORIZADO</strong> deverá acatar as seguintes instruções:
                                </p>

                                <p class='escpaco-clausula'>
                                    3.1.1 - O produto que retornar sem conserto ou que ultrapasse o prazo de reparo firmado com consumidor/revendedor, e que eventualmente ocasionar devolução ou troca, <u>terá seu valor debitado</u> do <strong>POSTO AUTORIZADO</strong>, quando detectada <u>falha do atendimento</u>, tais como:  falta de comunicação do problema à <strong>BLACK & DECKER</strong> após 05 (cinco) dias do recebimento do produto, atraso superior a 5 (cinco) dias na efetivação do pedido, falta de comunicação ao consumidor POR ESCRITO (através de e-mail, SMS, WhatsApp, carta registrada ou telegrama) quando o produto estiver reparado, etc.
                                </p>

                                <p class='escpaco-clausula'>
                                    3.1.2 - O produto deverá passar por triagem no prazo máximo de 05 (cinco) dias, assim como o pedido das peças para reparo e o envio do orçamento às revendas, deverá ser informado no mesmo prazo.
                                </p>

                                <p class='escpaco-clausula'>
                                    3.1.3 - O <strong>POSTO AUTORIZADO</strong> deverá se responsabilizar por documentar <strong>POR ESCRITO</strong> o recebimento do produto, assim como a entrega dos produtos de modo organizado e disponibilizará essa informação quando solicitado pela <strong>BLACK & DECKER</strong>, guardando os comprovantes pelo período de 60 (sessenta) meses. Caso a revenda retire o produto sem conserto, é necessária a emissão de uma Nota Fiscal de Retorno sem conserto e esta informação deverá ser passada para a <strong>BLACK & DECKER</strong>.
                                </p>
                                <p>
                                    <strong>CLÁUSULA QUARTA - DAS RESPONSABILIDADES DO POSTO AUTORIZADO</strong>
                                </p>

                                <p>
                                    4.1 - Para o bom desempenho de vossas atividades, o <strong>POSTO AUTORIZADO</strong> deverá cumprir as seguintes obrigações:
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.1 - Manter instalações adequadas ao desempenho da prestação dos serviços;
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.2 - Sugerimos manter no estoque de peças de reposição de maior giro da marca <strong>BLACK & DECKER</strong>, que serão utilizadas para revenda e aplicação nos consertos dos produtos;
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.3 - Possuir o equipamento mínimo necessário para conserto, bem como as ferramentas, objetivando garantir serviço rápido e eficiente para os consumidores / revendedores;
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.4 - Executar serviços e atender reclamações de produtos, a fim de oferecer aos consumidores/revendedores atendimento de alto padrão;
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.5 - Executar inserção de pedidos de peças para atendimentos em garantia com a maior agilidade e precisão possível.
                                </p>

                                <p class='escpaco-clausula-2'>
                                    4.1.5.1 - Em caso de demora na colocação de pedidos de peças, superior a 5 (cinco) dias, pedidos inseridos incorretos ou falhas comprovadas (por erro de atendimento do posto / descumprimento de procedimento) que acarretarem em produtos parados no posto por mais de 30 (trinta) dias, a B&D reserva-se o direito de debitar do <strong>POSTO AUTORIZADO</strong> gastos oriundos dessas divergências, tais como, devolução de valor ao cliente, troca de produto, fretes adicionais, além de despesas com pagamentos de condenações judiciais, condenações de PROCON, custas e honorários advocatícios
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.6 - Participar, sempre que convocado, de cursos de treinamento organizados ou ministrados pela <strong>BLACK & DECKER</strong>;
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.7 - Obedecer à tabela de preço sugerido de peças de reposição fornecida pela <strong>BLACK & DECKER</strong>;
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.8 - Possuir organização administrativa e fiscal condizente com o volume do negócio a ser desenvolvido;
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.9 - Conservar manuais e cartas de serviços fornecidas pela <strong>BLACK & DECKER</strong> e mantê-los disponíveis a todos os funcionários;
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.10 - Conservar os aparelhos de clientes, em reparo ou já reparados, limpos e em prateleiras e não jogados pelo chão;
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.11 - Informar ao setor de assistência técnica <strong>BLACK & DECKER</strong> sobre problemas não comuns de ordem técnica e/ou administrativa;
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.12 - Saldar em dia seus compromissos financeiros com a <strong>BLACK & DECKER</strong> e/ou Distribuidores autorizados;
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.13 - Autorizar inspeções e auditorias, efetuadas por pessoas indicadas pela <strong>BLACK & DECKER</strong>;
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.14 - Evitar declarações exageradas com relação aos produtos da <strong>BLACK & DECKER</strong> observando os preceitos legais de proteção ao consumidor.
                                </p>

                                <p class='escpaco-clausula'>
                                    4.1.15 - Informar ao consumidor POR ESCRITO (através de e-mail, SMS, WhatsApp, carta registrada ou telegrama) que o produto foi reparado;
                                </p>
                                <p class='escpaco-clausula'>
                                    4.1.16 - Informar à BLACK & DECKER caso exista algum produto parado no <strong>POSTO AUTORIZADO</strong>, por falta de peças ou outro motivo, quando completado <strong>15 dias</strong> da abertura da OS, <strong>SOB PENA DE SER RESPONSABILIZADO</strong> a reembolsar as despesas com pagamentos de condenações judiciais, condenações de PROCON, custas e honorários advocatícios pelo não cumprimento do prazo previsto no artigo 18 do Código de Defesa do Consumidor
                                </p>
                                <p class='escpaco-clausula'>
                                    4.1.17 - O <strong>POSTO AUTORIZADO</strong> tem conhecimento e concorda que a BLACK & DECKER não o obriga a manter peças e produtos em estoque, e por esse motivo, não será responsável pelo reembolso de eventuais peças e produtos que o <strong>POSTO AUTORIZADO</strong> mantenha em seu estoque, mesmo após eventual descredenciamento ou rescisão do contrato.
                                </p>

                                <p>
                                    4.2 - A BLACK & DECKER não se responsabilizará por problemas ocasionados pelo não cumprimento pelo <strong>POSTO AUTORIZADO</strong>, de obrigações, tais como:
                                </p>

                                <p class='escpaco-clausula'>
                                    4.2.1 - Demora na análise e colocação de pedidos de peças superior a 5 (cinco) dias a contar do recebimento do produto;
                                </p>
                                <p class='escpaco-clausula'>
                                    4.2.2 - Inserir pedidos incorretos;
                                </p>
                                <p class='escpaco-clausula'>
                                    4.2.3 - Não comunicar ao consumidor POR ESCRITO (através de e-mail, SMS, WhatsApp, carta registrada ou telegrama) que o produto foi reparado;
                                </p>
                                <p class='escpaco-clausula'>
                                    4.2.4 - Reparos executados sem qualidade e/ou negligência.
                                </p>
                                <p class='escpaco-clausula'>
                                    4.2.5 - Mau atendimento junto ao consumidor/revendedor final.
                                </p>

                                <p>
                                    4.3 - O não cumprimento de qualquer uma das cláusulas acima é passível de penalidades ao <strong>POSTO AUTORIZADO</strong>, podendo ser em forma de advertência verbal, advertência por escrito, registro de ocorrência, e em casos mais graves e/ou reincidentes, débito de valores no extrato do posto e descredenciamento.
                                </p>

                                </p>
                                <p class='escpaco-clausula'>
                                    <strong>
                                    4.3.1 - Caso a BLACK & DECKER venha a ser condenada judicialmente ou pelo PROCON por culpa comprovada do <strong>POSTO AUTORIZADO</strong>, pelo não cumprimento do disposto no item 4.2, a BLACK & DECKER poderá descontar dos valores a serem pagos ao <strong>POSTO AUTORIZADO</strong>, as despesas com pagamento de condenações e custas judiciais, com as quais o <strong>POSTO AUTORIZADO</strong> concorda plenamente.</strong>
                                </p>

                                <p>
                                    <strong>CLÁUSULA QUINTA - A RESPONSABILIDADE DO POSTO AUTORIZADO QUANTO ÀS INFORMAÇÕES CADASTRAIS</strong>
                                </p>

                                <p>
                                    5.1 - Comunicar imediatamente ao setor de cadastro da <strong>BLACK & DECKER</strong> as alterações tais como:
                                </p>
                                <p class='escpaco-clausula letraCurta'>
                                    a. Mudança de Razão Social ou quaisquer alterações societárias, incluindo mas não se limitando ao Contrato Social;
                                </p>
                                <p class='escpaco-clausula letraCurta'>
                                    b. Alteração de Inscrição Estadual e/ou CNPJ;
                                </p>

                                <p class='escpaco-clausula letraCurta'>
                                    c. Mudanças de endereço / telefone / contato / e-mail;
                                </p>
                                <p class='escpaco-clausula letraCurta'>
                                    d. Abertura de filiais;
                                </p>
                                <p class='escpaco-clausula letraCurta'>
                                    e. Alteração de Capital Social;
                                </p>
                                <p class='escpaco-clausula letraCurta'>
                                    f. Alteração de Dados Bancários.
                                </p>

                                <p>
                                    <strong>CLÁUSULA SEXTA - DA ORDEM DE SERVIÇO (O.S.)</strong>
                                </p>

                                <p>
                                    6.1 - A ordem de serviço representa o registro de todo e qualquer atendimento realizado, sendo este obrigatório ao <strong>POSTO AUTORIZADO</strong>.
                                </p>

                                <p>
                                    6.2 - Ordens de serviço com itens irregulares, ou seja, com peças que não pertencem a garantia e/ou não autorizado pela <strong>BLACK & DECKER</strong>, ou ainda estiverem com alguma irregularidade terão seus valores descontado em extrato.
                                </p>

                                <p>
                                    6.3 - Caso sejam constatados problemas descritos acima poderão ocasionar em: advertência e posteriormente o descredenciamento do posto autorizado.
                                </p>
                                <p class='escpaco-clausula'>
                                    <u>Parágrafo Único</u>: O consumidor e/ou revendedor deverá ser avisado que seu produto já foi reparado, estando pronto para a retirada <strong>POR ESCRITO</strong> (através de e-mail, SMS, WhatsApp, carta registrada ou telegrama), formalizando para o consumidor e/ou revendedor que seu produto já foi consertado e está pronto para retirada. O custo de envio desta comunicação, será <u>reembolsado</u> ao <strong>POSTO AUTORIZADO</strong> pela <strong>BLACK & DECKER</strong>, para que, desta forma esta última possa evitar notificações junto ao Procon e processos judiciais.
                                </p>


                                <p>
                                    <strong>CLÁUSULA SÉTIMA - DAS OBRIGAÇÕES DAS PARTES</strong>
                                </p>

                                <p>
                                    7.1 - Todo o material fornecido pela <strong>BLACK & DECKER</strong> deverá ser manuseado, exclusiva e privativamente, pelo <strong>POSTO AUTORIZADO</strong> com o devido cuidado e para seu uso interno.
                                </p>
                                <p>
                                    7.2 - O presente acordo para o funcionamento do Posto Autorizado não poderá ser cedido ou transferido sem anuência expressa da <strong>BLACK & DECKER</strong>.
                                </p>
                                <p>
                                    7.3 - O <strong>POSTO AUTORIZADO</strong> não exercerá quaisquer direitos sobre a clientela originada em decorrência da atividade exercida pelo <strong>POSTO AUTORIZADO</strong>, podendo qualquer das partes utilizá-la após a eventual rescisão deste acordo.
                                </p>
                                <p>
                                    7.4 - O <strong>POSTO AUTORIZADO</strong> se responsabiliza, de forma exclusiva, por todos os encargos fiscais, trabalhistas, acidentados e previdenciários de seus prepostos;
                                </p>
                                <p>
                                    7.5 - Todos os produtos e todas as peças trocadas pelo <strong>POSTO AUTORIZADO</strong> para produtos com garantia deverão ser mantidas à disposição da <strong>BLACK & DECKER</strong> pelo prazo mínimo de 03 (três) meses. A <strong>BLACK & DECKER</strong> tem direito à coleta das peças e produtos no prazo de 90 (noventa) dias. Para extratos não pagos, este direito à coleta é independente da data. Para que não haja necessidade de armazenamento de peças e produtos por longos períodos, os lançamentos devem estar sempre atualizados.  Somente após 03 (três) meses, estas peças e produtos poderão ser sucateadas.  O prazo de 03 (três) meses ora mencionado, começa a contar a partir do momento que as ordens de serviço estão finalizadas em extrato de serviço com documentação já enviada para a <strong>BLACK & DECKER</strong>. As peças/produtos trocados fora de garantia deverão permanecer em poder do consumidor;
                                </p>
                                <p class='escpaco-clausula'>
                                    7.5.1 - As peças e produtos que tiveram sua coleta solicitada e não forem recebidos na <strong>BLACK & DECKER</strong>, sendo esta causada pelo posto (por recusa em enviar as peças/produtos ou não possuir estas), terão seus valores descontado em extrato.
                                </p>
                                <p>
                                    7.6 - O <strong>POSTO AUTORIZADO</strong> não poderá incluir nos materiais promocionais fornecidos pela <strong>BLACK & DECKER</strong> quaisquer outras inscrições, mesmo que não signifiquem concorrência, direta ou indireta, aos produtos da <strong>BLACK & DECKER</strong>;
                                </p>
                                <p>
                                    7.7 - O <strong>POSTO AUTORIZADO</strong> responsabiliza-se por todos os encargos fiscais e tributários federais, estaduais e municipais, decorrentes da sua atividade, inclusive encargos trabalhistas, emolumentos e taxas relativas ao município incidentes sobre placas e propaganda.
                                </p>

                                <p>
                                    <strong>CLÁUSULA OITAVA - DA VIGÊNCIA E RESCISÃO</strong>
                                </p>
                                <p>
                                    8.1 - O prazo de vigência do presente contrato é indeterminado, a contar da data de assinatura.
                                </p>
                                <p>
                                    8.2 - Fica facultada às partes, a rescisão antecipada deste instrumento, a qualquer momento, mediante notificação por escrito, com antecedência de 30 (trinta) dias, sem direito a qualquer indenização.
                                </p>
                                <p>
                                    8.3 - Não obstante o prazo estipulado na cláusula 8.1, o presente contrato se rescindirá de pleno direito, independentemente de notificação ou interpelação judicial ou extrajudicial nas hipóteses abaixo, a saber:
                                </p>
                                <p class='escpaco-clausula letraCurta'>
                                    a. Falência, dissolução ou recuperação judicial do <strong>POSTO AUTORIZADO</strong> ou da <strong>BLACK & DECKER</strong>; e,
                                </p>
                                <p class='escpaco-clausula letraCurta'>
                                    b. Descumprimento de quaisquer cláusulas do presente acordo, desde que a parte infratora, devidamente notificada para sanar a falha, não o faça no prazo de 05 (cinco) dias após o comunicado por escrito.
                                </p>


                                <!-- PARAGRAFO FINAL -->
                                <p>
                                    <strong>CLÁUSULA NONA - DAS DISPOSIÇÕES GERAIS</strong>
                                </p>
                                <p>
                                    9.1 - Este acordo cancela e/ou substitui quaisquer outros acordos, escritos ou verbais, por ventura, existentes, passando a partir dessa data, a ser o único instrumento que rege as relações entre <strong>POSTO AUTORIZADO</strong> e <strong>BLACK & DECKER</strong>.
                                </p>
                                <p>
                                    9.2 - Por meio deste acordo, fica concedida a autorização para prestação de serviços de assistência técnica dos produtos de fabricação da <strong>BLACK & DECKER</strong>, podendo ser rescindido a qualquer tempo, por qualquer das partes, mediante aviso prévio de 30 (trinta) dias por escrito conforme descrito na cláusula 8.2 do presente, findo os quais o <strong>POSTO AUTORIZADO</strong> se comprometerá liquidar suas pendências de consumidores e revendedores da <strong>BLACK & DECKER</strong> assim como, extratos de pagamentos de garantia em aberto e, não mais usar o nome da BLACK & DECKER DO BRASIL LTDA e/ou marcas desta companhia, ou seja, monogramas em seus documentos, letreiros, cartazes ou em quaisquer outros meios de comunicação.
                                </p>
                                <p>
                                    9.3 - A <strong>BLACK & DECKER</strong> reserva-se o direito de suspender, sem prévio aviso e sem qualquer indenização, o fornecimento de quaisquer de seus produtos ao <strong>POSTO AUTORIZADO</strong>, caso este mantenha atitudes não condizentes com a ética comercial, profissional e moral, sem prejuízo do atendimento ao descrito nos demais parágrafos do presente.
                                </p>
                                <p>
                                    9.4 - Em caso de cancelamento, a <strong>BLACK & DECKER</strong> não se obriga a receber peças de seu estoque, em devolução, sob qualquer pretexto.
                                </p>
                                <p>
                                    9.5 - O presente acordo não possui caráter de exclusividade, reservando-se a <strong>BLACK & DECKER</strong> o direito de, a seu exclusivo critério, nomear outras oficinas na mesma área, ou região para a prestação de serviços de mesmo objeto deste acordo.
                                </p>
                                <p>
                                    9.6 - O <strong>POSTO AUTORIZADO</strong> é o único responsável pelo pessoal que utilizar na prestação dos Serviços de sua sede, devendo satisfazer rigorosamente todas as suas obrigações trabalhistas, previdenciárias e sociais em geral em relação ao mesmo, mantendo a <strong>BLACK & DECKER</strong> isenta de qualquer reclamação, dúvida, pendência ou atrito entre ela, <strong>POSTO AUTORIZADO</strong>, e seus empregados ou qualquer outro preposto que utilizar na execução deste acordo.
                                </p>
                                <p>
                                    9.7 - Qualquer alteração, modificação, complementação, ou ajuste, somente será reconhecido e produzirá efeitos legais, se incorporado ao presente acordo mediante termo aditivo, devidamente assinado pela <strong>BLACK & DECKER</strong> e <strong>POSTO AUTORIZADO</strong>.
                                </p>
                                <p>
                                    9.8 - Toda e qualquer tolerância quanto ao cumprimento por qualquer das partes, das condições estabelecidas neste contrato, será considerada exceção, não possuindo condão de novação ou alteração das disposições ora pactuadas, mas tão somente liberalidade entre <strong>BLACK & DECKER</strong> e <strong>POSTO AUTORIZADO</strong>.
                                </p>
                                <p>
                                    9.9 - Para fins de notificação, as informações deverão ser formalizadas por escrito, via e-mail, ou quando couber, de outra forma, desde que ratificadas posteriormente por e-mail.
                                </p>
                                <p>
                                    <strong>CLÁUSULA DÉCIMA - DO FORO</strong>
                                </p>
                                <p>
                                    10.1 - As partes elegem o foro da Comarca de Uberaba/MG para dirimir quaisquer dúvidas decorrentes do presente contrato, com exclusão de qualquer outro, por mais privilegiado que seja.
                                </p>
                                <p>
                                    E, por estarem, assim certas e contratadas, assinam as partes o presente acordo, em 02 (duas) vias de igual teor e valor, na presença das testemunhas ao final nomeadas para que reproduza os efeitos de direito.
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
                                    SILVANIA SILVA - GERENTE DE PRODUTOS E SERVIÇOS <br /> <br /> <br /> <br />
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
                                INSTRUMENTO PARTICULAR DE ACORDO DE COMPRA DE PEÇAS CELEBRADO ENTRE BLACK & DECKER DO BRASIL LTDA., E {$posto_nome}
                            </h3>

                            <p>
                                Pelo presente instrumento particular de um lado <strong>BLACK & DECKER DO BRASIL LTDA.</strong>, pessoa jurídica de direito privado, com sede à Rodovia BR 050, s/nº, KM 167, Lote 05 Parte, Quadra 01, Distrito Industrial II, Uberaba - MG, inscrita no CNPJ/MF sob o nº. 53.296.273/0001-91, representada por seus diretores, ao final assinados, e doravante denominada simplesmente <strong>BLACK & DECKER</strong>, e de outro lado <strong>{$posto_nome}</strong> com sede {$posto_endereco_completo}, inscrita no CNPJ/MF sob o nº {$posto_cnpj}, neste ato representada na forma de seu Contrato Social, denominada simplesmente denominada simplesmente <strong>COMPRA DE PEÇAS</strong>, firmam o presente acordo regulado pelas cláusulas seguintes:
                            </p>

                            <p>
                                <strong>CLÁUSULA PRIMEIRA - DO OBJETO</strong>
                            </p>

                            <p>
                                1.1 - O <strong>COMPRA DE PEÇAS</strong> possuirá acesso ao sistema Telecontrol, com endereço eletrônico <u class='corLink'>www.telecontrol.com.br</u>, para que efetue compras de peças, esse procedimento é denominado \"COMPRA DE PEÇAS\".
                            </p>

                            <p>
                                <strong>CLÁUSULA SEGUNDA - DO ACESSO</strong>
                            </p>

                            <p>
                                2.1 - A <strong>BLACK & DECKER</strong> criará  um código de cadastro para identificação do <strong>COMPRA DE PEÇAS</strong>.
                            </p>

                            <p class='escpaco-clausula'>
                                2.1.1 - O <em>\"login\"</em> de acesso ao sistema da <strong>BLACK & DECKER</strong> será criado assim que o <strong>COMPRA DE PEÇAS</strong> enviar o presente instrumento devidamente assinado.
                            </p>

                            <p class='escpaco-clausula'>
                                2.1.2 - Para concluir o acesso, o <strong>COMPRA DE PEÇAS</strong> deverá entrar no site <u class='corLink'>www.telecontrol.com.br</u>, onde encontrará as vistas explodidas dos produtos, tela para pedidos de peças, acesso à tabela de preços, informações técnicas e relatórios gerenciais.
                            </p>

                            <p>
                                <strong>CLÁUSULA TERCEIRA - DA RESPONSABILIDADE DO COMPRA DE PEÇAS</strong>
                            </p>

                            <p>
                                3.1 - É de responsabilidade do <strong>COMPRA DE PEÇAS</strong>:
                            </p>

                            <p class='escpaco-clausula'>
                                3.1.1 - Manter contato com a <strong>BLACK & DECKER</strong> em caso de dúvidas para que esta possa lhes prestar o devido auxilio;
                            </p>

                            <p class='escpaco-clausula'>
                                3.1.2 - Saldar em dia seus compromissos financeiros com a <strong>BLACK & DECKER</strong>;
                            </p>

                            <p class='escpaco-clausula'>
                                3.1.3 - Autorizar inspeções, bem como, auditorias efetuadas por pessoas indicadas pela <strong>BLACK & DECKER</strong>;
                            </p>
                            <p class='escpaco-clausula'>
                                3.1.4 - Comunicar imediatamente ao setor de cadastro da <strong>BLACK & DECKER</strong> as alterações tais como:
                            </p>

                            <p class='escpaco-clausula-2'>
                                a. Mudança de Razão Social ou quaisquer alterações societárias, incluindo mas não se limitando ao Contrato Social;
                            </p>
                            <p class='escpaco-clausula-2'>
                                b. Alteração de Inscrição Estadual e/ou CNPJ;
                            </p>
                            <p class='escpaco-clausula-2'>
                                c. Mudanças de endereço / telefone / contato / e-mail;
                            </p>
                            <p class='escpaco-clausula-2'>
                                d. Abertura de filiais;
                            </p>
                            <p class='escpaco-clausula-2'>
                                e. Alteração de Capital Social;
                            </p>
                            <p class='escpaco-clausula-2'>
                                f. Alteração de Dados Bancários;
                            </p>

                            <p>
                                <strong>CLÁUSULA QUARTA - DAS RESPONSABILIDADES DA BLACK & DECKER</strong>
                            </p>

                            <p>
                                4.1 - O <strong>COMPRA DE PEÇAS</strong> responsabiliza-se por todos os encargos fiscais e tributários federais, estaduais e municipais, decorrentes da sua atividade, inclusive encargos trabalhistas, emolumentos e taxas relativas ao município incidentes sobre placas e propaganda.
                            </p>

                            <p>
                                4.2 - A <strong>BLACK & DECKER</strong> tem por responsabilidade atender seus clientes no envio de peças, dentro do melhor prazo possível, garantindo assim satisfação dos mesmos, onde em caso de falta de peças o <strong>COMPRA DE PEÇAS</strong> deverá acionar a <strong>BLACK & DECKER</strong> informando o atraso para que as devidas análises e envio dos itens faltantes sejam providenciados, não sendo de responsabilidade da BLACK & Decker a troca ou ressarcimento de valores referentes que fuja das condições acima citadas.
                            </p>

                            <p>
                                <strong>CLÁUSULA QUINTA - DA VIGÊNCIA e RESCISÃO</strong>
                            </p>

                            <p>
                                5.1 - O prazo de vigência do presente contrato é indeterminado, a contar da data de assinatura.
                            </p>
                            <p>
                                5.2 - Fica facultada às partes, a rescisão antecipada deste instrumento, a qualquer momento, mediante notificação por escrito, com antecedência de 30 (trinta) dias, sem direito a qualquer indenização.
                            </p>
                            <p>
                                5.3 - Não obstante o prazo estipulado na clausula 5.1, o presente contrato se rescindirá de pleno direito, independentemente de notificação ou interpelação judicial ou extrajudicial nas hipóteses abaixo, a saber:
                            </p>
                            <p class='escpaco-clausula'>
                                a. Falência, dissolução ou recuperação judicial do <strong>COMPRA DE PEÇAS</strong> ou da <strong>BLACK & DECKER</strong>; e,
                            </p>

                            <p class='escpaco-clausula'>
                                b. Descumprimento de quaisquer cláusulas do presente acordo, desde que a parte infratora, devidamente notificada para sanar a falha, não o faça no prazo de 05 (cinco) dias após o comunicado por escrito.
                            </p>

                            <!-- PARAGRAFO FINAL -->
                            <p>
                                <strong>CLÁUSULA SEXTA - DAS DISPOSIÇÕES GERAIS</strong>
                            </p>
                            <p>
                                6.1 - A <strong>BLACK & DECKER</strong> reserva-se o direito de suspender, sem prévio aviso e sem qualquer indenização, o fornecimento de quaisquer de seus produtos ao <strong>COMPRA DE PEÇAS</strong>, bem como, rescindir o presente acordo, caso este mantenha atitudes não condizentes com a ética comercial, profissional e moral, sem prejuízo do atendimento ao descrito nos demais parágrafos da presente.
                            </p>
                            <p>
                                6.2 - Nos casos de pedidos incorretos feitos pelo <strong>COMPRA DE PEÇAS</strong>, seja por quantidade, código ou encerramentos de atividades por parte do <strong>COMPRA DE PEÇAS</strong>, a <strong>BLACK & DECKER</strong> não se obriga a receber peças de seu estoque, em devolução, sob qualquer pretexto.
                            </p>
                            <p>
                                6.3 - O <strong>COMPRA DE PEÇAS</strong> é o único responsável pelo pessoal que utilizar na prestação dos Serviços de sua sede, devendo satisfazer rigorosamente todas as suas obrigações trabalhistas, previdenciárias e sociais em geral em relação ao mesmo, mantendo a <strong>BLACK & DECKER</strong> isenta de qualquer reclamação, dúvida, pendência ou atrito entre ela, <strong>COMPRA DE PEÇAS</strong>, e seus empregados ou qualquer outro preposto que utilizar na execução deste acordo.
                            </p>
                            <p>
                                6.4 - Fica vedado as partes transferir ou ceder, a qualquer título, os direitos e obrigações assumidos nesse acordo.
                            </p>
                            <p>
                                6.5 - Qualquer alteração, modificação, complementação, ou ajuste, somente será reconhecido e produzirá efeitos legais, se incorporado ao presente acordo mediante termo aditivo, devidamente assinado pela <strong>BLACK & DECKER</strong> e <strong>COMPRA DE PEÇAS</strong>.
                            </p>
                            <p>
                                6.6 - Toda e qualquer tolerância quanto ao cumprimento por qualquer das partes, das condições estabelecidas neste contrato, será considerada exceção, não possuindo condão de novação ou alteração das disposições ora pactuadas, mas tão somente liberalidade entre <strong>BLACK & DECKER</strong> e <strong>COMPRA DE PEÇAS</strong>.
                            </p>
                            <p>
                                6.7 - Para fins de notificação, as informações deverão ser formalizadas por escrito, via e-mail, ou quando couber, de outra forma, desde que ratificadas posteriormente por e-mail.
                            </p>
                            <p>
                                6.8 - Este acordo cancela e/ou substitui quaisquer outros acordos, escritos ou verbais, por ventura, existentes, passando a partir dessa data, a ser o único instrumento que rege as relações entre <strong>COMPRA DE PEÇAS</strong> e <strong>BLACK & DECKER</strong>.
                            </p>
                            <p>
                                <strong>CLÁUSULA SÉTIMA - DO FORO</strong>
                            </p>
                            <p>
                                7.1 - As partes elegem o foro da Comarca de Uberaba/MG para dirimir quaisquer dúvidas decorrentes do presente contrato, com exclusão de qualquer outro, por mais privilegiado que seja.
                            </p>
                            <p>
                                E, por estarem, assim certas e contratadas, assinam as partes o presente acordo, em 02 (duas) vias de igual teor e valor, na presença das testemunhas ao final nomeadas para que reproduza os efeitos de direito.
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
                                SILVANIA SILVA - GERENTE DE PRODUTOS E SERVIÇOS <br /> <br /> <br /> <br />
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
                                Pelo presente instrumento particular de um lado <strong>BLACK & DECKER DO BRASIL LTDA.</strong>, pessoa jurídica de direito privado, com $sede_filial à Rodovia BR 050, s/nº, KM 167, Lote 05 Parte, Quadra 01, Distrito Industrial II, Uberaba - MG, inscrita no CNPJ/MF sob o nº. 53.296.273/0001-91, representada por seus diretores, ao final assinados, e doravante denominada simplesmente <strong>BLACK & DECKER</strong>, e de outro lado <strong>{$posto_nome} </strong> com $sede_filial {$posto_endereco_completo}, inscrita no CNPJ/MF sob o nº {$posto_cnpj}, neste ato representada na forma de seu Contrato Social, denominada simplesmente denominada simplesmente <strong>INDUSTRIA/MEGA PROJETO</strong>, firmam o presente acordo regulado pelas cláusulas seguintes:
                            </p>

                            <p>
                                <strong>CLÁUSULA PRIMEIRA - DO OBJETO</strong>
                            </p>

                            <p>
                                1.1 - O <strong>INDUSTRIA/MEGA PROJETO</strong> possuirá acesso ao sistema Telecontrol, com endereço eletrônico <u class='corLink'>www.telecontrol.com.br</u>, para que efetue compras de peças, esse procedimento é denominado \"INDUSTRIA/MEGA PROJETO\".
                            </p>

                            <p>
                                <strong>CLÁUSULA SEGUNDA - DO ACESSO</strong>
                            </p>

                            <p>
                                2.1 - A <strong>BLACK & DECKER</strong> criará  um código de cadastro para identificação do <strong>INDUSTRIA/MEGA PROJETO</strong>.
                            </p>

                            <p class='escpaco-clausula'>
                                2.1.1 - O <em>\"login\"</em> de acesso ao sistema da <strong>BLACK & DECKER</strong> será criado assim que o <strong>INDUSTRIA/MEGA PROJETO</strong> enviar o presente instrumento devidamente assinado.
                            </p>

                            <p class='escpaco-clausula'>
                                2.1.2 - Para concluir o acesso, o <strong>INDUSTRIA/MEGA PROJETO</strong> deverá entrar no site <u class='corLink'>www.telecontrol.com.br</u>, onde encontrará as vistas explodidas dos produtos, tela para pedidos de peças, acesso à tabela de preços, informações técnicas e relatórios gerenciais.
                            </p>

                            <p>
                                <strong>CLÁUSULA TERCEIRA - DA RESPONSABILIDADE DO INDUSTRIA/MEGA PROJETO</strong>
                            </p>

                            <p>
                                3.1 - É de responsabilidade do <strong>INDUSTRIA/MEGA PROJETO</strong>:
                            </p>

                            <p class='escpaco-clausula'>
                                3.1.1 - Manter contato com a <strong>BLACK & DECKER</strong> em caso de dúvidas para que esta possa lhes prestar o devido auxilio;
                            </p>

                            <p class='escpaco-clausula'>
                                3.1.2 - Saldar em dia seus compromissos financeiros com a <strong>BLACK & DECKER</strong>;
                            </p>

                            <p class='escpaco-clausula'>
                                3.1.3 - Autorizar inspeções, bem como, auditorias efetuadas por pessoas indicadas pela <strong>BLACK & DECKER</strong>;
                            </p>
                            <p class='escpaco-clausula'>
                                3.1.4 - Comunicar imediatamente ao setor de cadastro da <strong>BLACK & DECKER</strong> as alterações tais como:
                            </p>

                            <p class='escpaco-clausula-2'>
                                a. Mudança de Razão Social ou quaisquer alterações societárias, incluindo mas não se limitando ao Contrato Social;
                            </p>
                            <p class='escpaco-clausula-2'>
                                b. Alteração de Inscrição Estadual e/ou CNPJ;
                            </p>
                            <p class='escpaco-clausula-2'>
                                c. Mudanças de endereço / telefone / contato / e-mail;
                            </p>
                            <p class='escpaco-clausula-2'>
                                d. Abertura de filiais;
                            </p>
                            <p class='escpaco-clausula-2'>
                                e. Alteração de Capital Social;
                            </p>
                            <p class='escpaco-clausula-2'>
                                f. Alteração de Dados Bancários;
                            </p>

                            <p>
                                <strong>CLÁUSULA QUARTA - DAS RESPONSABILIDADES DA BLACK & DECKER</strong>
                            </p>

                            <p>
                                4.1 - O <strong>INDUSTRIA/MEGA PROJETO</strong> responsabiliza-se por todos os encargos fiscais e tributários federais, estaduais e municipais, decorrentes da sua atividade, inclusive encargos trabalhistas, emolumentos e taxas relativas ao município incidentes sobre placas e propaganda.
                            </p>

                            <p>
                                4.2 - A <strong>BLACK & DECKER</strong> tem por responsabilidade atender seus clientes no envio de peças, dentro do melhor prazo possível, garantindo assim satisfação dos mesmos, onde em caso de falta de peças o <strong>INDUSTRIA/MEGA PROJETO</strong> deverá acionar a <strong>BLACK & DECKER</strong> informando o atraso para que as devidas análises e envio dos itens faltantes sejam providenciados, não sendo de responsabilidade da BLACK & Decker a troca ou ressarcimento de valores referentes que fuja das condições acima citadas.
                            </p>

                            <p>
                                <strong>CLÁUSULA QUINTA - DA VIGÊNCIA e RESCISÃO</strong>
                            </p>

                            <p>
                                5.1 - O prazo de vigência do presente contrato é indeterminado, a contar da data de assinatura.
                            </p>
                            <p>
                                5.2 - Fica facultada às partes, a rescisão antecipada deste instrumento, a qualquer momento, mediante notificação por escrito, com antecedência de 30 (trinta) dias, sem direito a qualquer indenização.
                            </p>
                            <p>
                                5.3 - Não obstante o prazo estipulado na clausula 5.1, o presente contrato se rescindirá de pleno direito, independentemente de notificação ou interpelação judicial ou extrajudicial nas hipóteses abaixo, a saber:
                            </p>
			    <p class='escpaco-clausula'>";
				
				$artigo53a = ($login_posto == 139472) ? ' ou dissolução' : ', dissolução ou recuperação judicial';
			    $conteudo .= "
                                a. Falência $artigo53a do <strong>INDUSTRIA/MEGA PROJETO</strong> ou da <strong>BLACK & DECKER</strong>; e,
                            </p>

                            <p class='escpaco-clausula'>
                                b. Descumprimento de quaisquer cláusulas do presente acordo, desde que a parte infratora, devidamente notificada para sanar a falha, não o faça no prazo de 05 (cinco) dias após o comunicado por escrito.
                            </p>

                            <!-- PARAGRAFO FINAL -->
                            <p>
                                <strong>CLÁUSULA SEXTA - DAS DISPOSIÇÕES GERAIS</strong>
                            </p>
                            <p>
                                6.1 - A <strong>BLACK & DECKER</strong> reserva-se o direito de suspender, sem prévio aviso e sem qualquer indenização, o fornecimento de quaisquer de seus produtos ao <strong>INDUSTRIA/MEGA PROJETO</strong>, bem como, rescindir o presente acordo, caso este mantenha atitudes não condizentes com a ética comercial, profissional e moral, sem prejuízo do atendimento ao descrito nos demais parágrafos da presente.
                            </p>
                            <p>
                                6.2 - Nos casos de pedidos incorretos feitos pelo <strong>INDUSTRIA/MEGA PROJETO</strong>, seja por quantidade, código ou encerramentos de atividades por parte do <strong>INDUSTRIA/MEGA PROJETO</strong>, a <strong>BLACK & DECKER</strong> não se obriga a receber peças de seu estoque, em devolução, sob qualquer pretexto.
                            </p>
                            <p>
                                6.3 - O <strong>INDUSTRIA/MEGA PROJETO</strong> é o único responsável pelo pessoal que utilizar na prestação dos Serviços de sua sede, devendo satisfazer rigorosamente todas as suas obrigações trabalhistas, previdenciárias e sociais em geral em relação ao mesmo, mantendo a <strong>BLACK & DECKER</strong> isenta de qualquer reclamação, dúvida, pendência ou atrito entre ela, <strong>INDUSTRIA/MEGA PROJETO</strong>, e seus empregados ou qualquer outro preposto que utilizar na execução deste acordo.
                            </p>
                            <p>
                                6.4 - Fica vedado as partes transferir ou ceder, a qualquer título, os direitos e obrigações assumidos nesse acordo.
                            </p>
                            <p>
                                6.5 - Qualquer alteração, modificação, complementação, ou ajuste, somente será reconhecido e produzirá efeitos legais, se incorporado ao presente acordo mediante termo aditivo, devidamente assinado pela <strong>BLACK & DECKER</strong> e <strong>INDUSTRIA/MEGA PROJETO</strong>.
                            </p>
                            <p>
                                6.6 - Toda e qualquer tolerância quanto ao cumprimento por qualquer das partes, das condições estabelecidas neste contrato, será considerada exceção, não possuindo condão de novação ou alteração das disposições ora pactuadas, mas tão somente liberalidade entre <strong>BLACK & DECKER</strong> e <strong>INDUSTRIA/MEGA PROJETO</strong>.
                            </p>
                            <p>
                                6.7 - Para fins de notificação, as informações deverão ser formalizadas por escrito, via e-mail, ou quando couber, de outra forma, desde que ratificadas posteriormente por e-mail.
                            </p>
                            <p>
                                6.8 - Este acordo cancela e/ou substitui quaisquer outros acordos, escritos ou verbais, por ventura, existentes, passando a partir dessa data, a ser o único instrumento que rege as relações entre <strong>INDUSTRIA/MEGA PROJETO</strong> e <strong>BLACK & DECKER</strong>.
                            </p>
                            <p>
                                <strong>CLÁUSULA SÉTIMA - DO FORO</strong>
                            </p>
			    <p>";
				$comarca = ($login_posto == 139472) ? 'de São João da Boa Vista/SP' : 'da Comarca de Uberaba/MG';
			    $conteudo .= "
                                7.1 - As partes elegem o foro $comarca para dirimir quaisquer dúvidas decorrentes do presente contrato, com exclusão de qualquer outro, por mais privilegiado que seja.
                            </p>
                            <p>
                                E, por estarem, assim certas e contratadas, assinam as partes o presente acordo, em 02 (duas) vias de igual teor e valor, na presença das testemunhas ao final nomeadas para que reproduza os efeitos de direito.
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
                                SILVANIA SILVA - GERENTE DE PRODUTOS E SERVIÇOS <br /> <br /> <br /> <br />
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

    $assunto = "Aviso de download de contrato de prestação de serviço - {$posto_nome} - Telecontrol";
    $mensagem = "
        Prezados, <br />
        informamos que o posto <strong>{$posto_codigo} - {$posto_nome}</strong> (CNPJ: {$posto_cnpj}) recebeu o contrato de prestação de serviços
        através do sistema da Telecontrol. <br />
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
