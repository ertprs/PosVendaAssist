<?php
$isFabrica = function($arr) use ($login_fabrica) {
    if (!is_array($arr))
        $arr = explode(',', $arr);
    return in_array($login_fabrica, $arr);
};


if ($isFabrica('1'))
    $tipos_comunicado['Comunidado Automatico'] = 'Comunicado Automatico';

if($isFabrica('52,152,35,1,24,140,177'))
    $tipos_comunicado['Contrato'] = 'Contrato';


if($isFabrica('153'))
    $tipos_comunicado['Atualização de Software'] = 'Atualização de Software';


if (!$isFabrica('42,74'))
    $tipos_comunicado['Com. Unico Posto'] = 'Com. Unico Posto';

if (!$isFabrica('3,11,14,42,45,129'))// HD 16961 54608
    $tipos_comunicado['Boletim'] = ($isFabrica('169,170')) ? 'Boletim Técnico' : 'Boletim';

if($isFabrica('138')){
	$tipos_comunicado['Código de Erro'] = 'Código de Erro';
}

if ($isFabrica('1')){
    $tipos_comunicado['Extrato'] = 'Extrato';
    $tipos_comunicado['video_explicativos'] = 'Vídeos Explicativos das Telas';
}

if (!$isFabrica('14,42,129')) { //A PEDIDO DE HONORATO FOI RETIRADO Informativo - CHAMADO 1192
    $tipos_comunicado['Comunicado'] =  'Comunicado';

    if (!$isFabrica('11,30')) // HD 54608
        $tipos_comunicado['Informativo'] = 'Informativo';

} else {
    if (!$isFabrica('30,42,74,169,170'))
        $tipos_comunicado['Comunicado administrativo'] = 'Comunicado administrativo';
}

if($isFabrica('104')){
    $tipos_comunicado['pedido_faturado_parcial'] = 'Pedido Faturado Parcialmente';
}

if (!$isFabrica('1,11,30,42,74'))
    $tipos_comunicado['Foto'] = 'Foto';

if (!$isFabrica('1,3,11,14,30,42,45,74'))
    $tipos_comunicado['Apresentação do Produto'] = 'Apresentação do Produto';

if (!$isFabrica('1,3,11,14,42,45,74'))
    $tipos_comunicado['Descritivo técnico'] = 'Descritivo técnico';

if (!$isFabrica('3,11,14,42')) // HD 16961 17700 54608
    $tipos_comunicado['Informativo tecnico'] = 'Informativo tecnico';

if ($isFabrica('1')) // HD 16961 17700 54608
    $tipos_comunicado['Recall'] = 'Recall';

if (!$isFabrica('3,11,14,30,42')) // HD 16961 17700 54608
    $tipos_comunicado['Manual'] = ($isFabrica('169,170')) ? 'Manual de Serviços' : 'Manual';

if ($isFabrica('3'))
    $tipos_comunicado['Manual de Serviço'] = 'Manual de Serviço';

if (!$isFabrica('3,11,42,45,75')) // HD 16961
    $tipos_comunicado['Orientação de Serviço'] = 'Orientação de Serviço';

if (!$isFabrica('1,11,30')) // HD 54608
    $tipos_comunicado['Lançamentos'] = 'Lançamentos';

if($isFabrica('11,171'))
    $tipos_comunicado['Video'] = 'Video';

if (!$isFabrica('42,74'))
    $tipos_comunicado['Procedimentos'] = 'Procedimentos';

if (!$isFabrica('30,42,45,19'))
    $tipos_comunicado['Promocao'] = 'Promoção';

if ($isFabrica('19'))
    $tipos_comunicado['Promocao'] = 'Formulários';

if (!$isFabrica('1,3,11,14,30,42,45,74'))
    $tipos_comunicado['Estrutura do Produto'] = 'Estrutura do Produto';

if ($isFabrica('24'))
    $tipos_comunicado['treinamento de Produto'] = 'Treinamento de Produto';

if ($isFabrica('42')) {
    $tipos_comunicado['Informativo tecnico']        = 'Informativo Técnico';
    $tipos_comunicado['Informativo administrativo'] = 'Informativo Administrativo';
    $tipos_comunicado['Procedimento de manutenção'] = 'Boletim Técnico';
    $tipos_comunicado['Análise Garantia']           = 'Análise Garantia';
    $tipos_comunicado['Informativo Promocional']    = 'Informativo Promocional';
    $tipos_comunicado['Video'] = 'Vídeo';
    $tipos_comunicado['FAQ Makita'] = 'FAQ Makita';
    $tipos_comunicado['Treinamento Telecontrol'] = 'Treinamento Telecontrol';
}

if($isFabrica('117')){
    $tipos_comunicado['Boletim'] =  'Comunicado Administrativo';
    $tipos_comunicado['Descritivo técnico'] =  'Material Técnico';
    $tipos_comunicado['Informativo'] =  'Boletim Técnico';
    $tipos_comunicado['Informativo tecnico'] =  'Manuais';
    $tipos_comunicado['Lançamentos'] =  'Venda de Peças';
}

if ($isFabrica('43'))
    $tipos_comunicado['Comunicado de não conformidade'] = 'Comunicado de não conformidade';

if ($isFabrica('1')) {
    $tipos_comunicado['Acessório'] = 'Acessório';
    $tipos_comunicado['Comunicado por tela'] = 'Comunicado por tela';
}

if ($isFabrica('15'))
    $tipos_comunicado['Tabela de preços'] = 'Tabela de preços';


if ($isFabrica('160') or $replica_einhell){
    $tipos_comunicado['Laudo'] = 'Laudo';
    $tipos_comunicado['Manual da Rede autorizada'] = 'Manual da Rede autorizada';
}

if ($isFabrica('140,151,163'))
    $tipos_comunicado['Laudo Tecnico'] = 'Laudo Técnico';

if($isFabrica('152')){
	$tipos_comunicado['Documentação Padrão / Procedimentos'] = 'Documentação Padrão / Procedimentos';
    $tipos_comunicado['Contrato'] = 'Contrato';
}

if ($isFabrica('169,170')) {
    $tipos_comunicado['Boletim de Lançamento']  = 'Boletim de Lançamento';
    $tipos_comunicado['Boletim Administrativo'] = 'Boletim Administrativo';
    $tipos_comunicado['Manual de Usuário']      = 'Manual de Usuário';
    $tipos_comunicado['IOM']                    = 'IOM';
}

if ($isFabrica('20,90,176,178')) {
	$tipos_comunicado['Capacitação Vídeo'] = 'Capacitação Vídeo';
	$tipos_comunicado['Capacitação Manual'] = 'Capacitação Manual';
}

if (isFabrica('42')) {

    #OBS: Lembrar de apagar os comunicados dos tipos excluídos.

    unset($tipos_comunicado['Capacitação Manual']);
    unset($tipos_comunicado['Capacitação Vídeo']);
    unset($tipos_comunicado['Informativo Promocional']);
    unset($tipos_comunicado['Lançamentos']); 
    unset($tipos_comunicado['Contrato']);

    $tipos_comunicado['Conteudos'] = 'Conteúdos';
}

if (in_array($login_fabrica, [169])) {

    unset($tipos_comunicado['Apresentação do Produto']);
    unset($tipos_comunicado['Boletim Administrativo']);
    unset($tipos_comunicado['Capacitação Manual']);
    unset($tipos_comunicado['Capacitação Vídeo']);
    unset($tipos_comunicado['Descritivo']);
    unset($tipos_comunicado['Técnico']);
    unset($tipos_comunicado['Estrutura do Produto']);
    unset($tipos_comunicado['Foto']);
    unset($tipos_comunicado['Informativo tecnico']); 
    unset($tipos_comunicado['Lançamentos']);
    unset($tipos_comunicado['Orientação de Usuário']);
    unset($tipos_comunicado['Procedimentos']);
    unset($tipos_comunicado['Promocao']);
    unset($tipos_comunicado['Contrato']);
    unset($tipos_comunicado['Comunicado']);
    unset($tipos_comunicado['Descritivo técnico']);
    unset($tipos_comunicado['Orientação de Serviço']);
    unset($tipos_comunicado['Informativo']);
    
    $tipos_comunicado['Com. Unico Posto']    = 'CA único Posto';
    $tipos_comunicado['Comunicado OS']       = 'Interação de OS';
    $tipos_comunicado['Informativo alterar'] = 'Comunicados Administrativos';
}

return $tipos_comunicado;

