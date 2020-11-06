<?php
$isFabrica = function($arr) use ($login_fabrica) {
    if (!is_array($arr))
        $arr = explode(',', $arr);
    return in_array($login_fabrica, $arr);
};

$comunicado_options = '';

if ($isFabrica('42')) {
    $comunicado_options = include('admin/menus/comunicado_option_array.php');
    $comunicado_options['Alteraчѕes Tщcnicas'] = traduz('Alteraчѕes.Tщcnicas',$con,$cook_idioma);
    $comunicado_options['Esquema Elщtrico'] = traduz('Esquema.Elщtrico',$con,$cook_idioma);
    $comunicado_options['Manual Tщcnico'] = traduz('Manual.Tщcnico',$con,$cook_idioma);
    $comunicado_options['Vista Explodida'] = traduz('Vista.Explodida',$con,$cook_idioma);
}

if($isFabrica('117')){
	$comunicado_options = include('admin/menus/comunicado_option_array.php');
	unset($comunicado_options['Apresentaчуo do Produto']);
	unset($comunicado_options['Com. Unico Posto']);
	unset($comunicado_options['Estrutura do Produto']);
	unset($comunicado_options['Foto']);
	unset($comunicado_options['Manual']);
	unset($comunicado_options['Orientaчуo de Serviчo']);
	unset($comunicado_options['Procedimentos']);
	unset($comunicado_options['Promocao']);
	unset($comunicado_options['Comunicado']);
}

ksort($comunicado_options);
return $comunicado_options;
