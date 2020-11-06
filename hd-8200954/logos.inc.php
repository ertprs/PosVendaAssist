<?php
/**
 * Devolve o logo da fábrica, se tiver mais de um configurado, escolhe
 * um qualquer.
 */
if (!function_exists('getFabricaLogo')) {
    function getFabricaLogo($fabrica, $imagensLogo) {
        $fabrica = intval($fabrica) ? : (int)$GLOBALS['login_fabrica'];
        $imagem  = '';

        if (array_key_exists($fabrica, $imagensLogo)) {
            $links   = $imagensLogo[$fabrica];
            // Escolhe uma das imagens, se tiver mais de uma
            $idx     = count($links) > 1 ? rand(0, count($links)-1) : 0;
            $imagem  = $links[$idx];
        }
        return $imagem;
    }
}

// Esta função já existe no mlg_funciones.php
if(!function_exists('array_merge_keys')){
    function array_merge_keys($a, $b) {
        foreach ($b as $k => $v)
            $a[$k] = $v;
        return $a;
    }
}

/**
 * Logos para as área de posto e admin, se o admin quer diferenciar,
 * deixar neste array o arquivo ou arquivos do posto, e colocar o
 * arquivo ou arquivos no array do final do script.
 */
$LOGOS = [
      1 => ['logo_black_2017.png'],
      3 => ['britania_admin1.jpg'],
      6 => ['tectoy_admin1.jpg'],
     10 => ['telecontrol_new_admin1.jpg'],
     11 => ['logo_lenox_new.jpg'],
     15 => ['latina_admin1.jpg'],
     19 => ['lorenzetti_admin1.jpg'],
     20 => ['bosch_admin1.jpg'],
     24 => ['suggar_admin1.jpg'],
     30 => ['esmaltec_admin1.jpg'],
     35 => ['logo_cadence_new.png'],
     40 => ['masterfrio_admin1.jpg'],
     42 => ['makita_admin1.jpg'],
     45 => ['nks_admin1.jpg'],
     50 => ['colormarq_admin1.jpg'],
     52 => ['fricon_admin1.jpg'],
     59 => ['sight_admin1.jpg'],
     63 => ['telecontrol_admin1.jpg'],
     72 => ['mallory_admin1.jpg'],
     74 => ['atlas_admin.jpg'], // HD 384115: atlas_saa_anim.gif
     75 => ['telecontrol_new_admin1.jpg'],
     78 => ['telecontrol_new_admin1.jpg'],
     80 => ['amvox_admin1.jpg'],
     81 =>[
        'bestway_admin1.jpg', 'bestway_admin2.jpg', 'bestway_admin3.jpg',
        'bestway_admin4.jpg', 'bestway_admin5.jpg', 'bestway_admin6.jpg',
        'bestway_admin7.jpg', 'bestway_admin8.jpg', 'bestway_admin9.jpg'
    ],
     85 => ['gelopar_admin1.jpg'],
     86 => ['famastil_admin1.jpg'],
     87 => ['jacto_admin1.jpg'],
     88 => ['orbis_admin1.jpg'],
     90 => ['ibbl_admin1.jpg'],
     91 => ['wanke_admin1.jpg'],
     94 => ['everest_admin1.jpg'],
     95 => [
        'leadership_admin1.jpg', 'leadership_admin2.jpg', 'leadership_admin3.jpg',
        'leadership_admin4.jpg', 'leadership_admin5.jpg'
    ],
     96 => ['bosch-security.jpg'],
     98 => ['dellar_admin1.jpg'],
     99 => ['eterny_admin1.jpg'],
    101 => ['delonghi_admin1.jpg'],
    102 => ['logo_remington.jpg'],
    103 => ['bestway_admin4.jpg'],
    104 => [
        'vonder_admin2.jpg', 'vonder_admin3.jpg', 'vonder_admin4.jpg',
        'vonder_admin5.jpg', 'vonder_admin6.jpg', 'vonder_admin7.jpg'
    ],
    106 => ['houston_admin1.jpg'],
    107 => ['orbis_admin1.jpg'],
    109 => ['sac_social_admin1.jpg'],
    110 => ['telecontrol_new_admin1.jpg'],
    112 => ['telecontrol_new_admin1.jpg'],
    114 => ['cobimex_admin1.jpg'],
    115 => ['nordtech_admin1.jpg'],
    116 => ['toyama_admin1.jpg'],
    117 => ['elgin_admin1.jpg'],
    120 => ['logo_newmaq.jpg'],
    121 => ['milwaukee_admin1.jpg'],
    122 => ['wurth_admin1.jpg'],
    123 => [
        'positec_admin1.jpg',
        'positec_admin2.jpg',
        'positec_admin3.jpg'
    ],
    124 => ['gama_admin1.jpg'],
    125 => ['saintgobain_admin1.jpg', 'saintgobain_admin2.jpg'],
    126 => ['master_admin1.jpg'],
    127 => ['dl_admin1.jpg'],
    128 => ['unilever_admin1.jpg', 'unilever_admin2.jpg'],
    129 => ['rinai_admin1.jpg'],
    130 => ['ford_admin1.jpg'],
    131 => ['pressure_admin1.jpg'],
    132 => ['logo_loyal.png'],
    134 => ['logo_hydra.gif'],
    136 => ['logo_ello_alta.jpg'],
    137 => ['logo_arge.jpg'],
    138 => ['logo_fujitsu.jpg'],
    139 => ['logo_ventisol.jpg'],
    140 => ['logo_lavor.jpg'],
    141 => ['logo_unicoba.jpg'],
    142 => ['logo_v8brasil.jpg'],
    143 => ['logo_wacker_neuson.jpg'],
    144 => ['logo_hikari.jpg'],
    145 => ['logo_fabrimar.jpg'],
    146 => ['logo_ferragens_negrao.jpg', 'worker.jpg', 'kala.jpg'],
    147 => ['logo_hitachi.jpg'],
    148 => ['logo_yanmar.jpg'],
    149 => ['logo_cortag.png'],
    150 => ['logo_inbrasil.jpg'],
    151 => ['logo_mondial.jpg'],
    152 => ['logo_esab.jpg'],
    153 => ['positron.jpg'],
    154 => ['logo_rheem.jpg'],
    155 => ['logo_duracell.png'],
    156 => ['elgin_admin1.jpg'],
    157 => ['logo_wap.jpg'],
    158 => ['logo_imbera.jpg'],
    160 => ['logo_einhell.jpg'],
    161 => ['logo_cristofoli.jpg'],
    162 => ['logo_qbex.jpg'],
    163 => ['logo_rowa.jpg'],
    164 => ['logo_gama_italy.jpg'],
    165 => ['logo_tecvoz.jpg'],
    166 => ['telecontrol_modelo.jpg'],
    167 => ['logo_brother.jpg'],
    168 => ['logo_acacia.png'],
    169 => ['logo_midea.png'],
    170 => ['logo_midea.png'],
    171 => ['logo_grohe.jpg'],
    //172 => ['lenox_admin1.jpg', 'lenox_admin2.jpg'],
    172 => ['logo_lenox_new.jpg'],
    173 => ['logo_jfa.jpg'],
    174 => ['logo_aquarius.png'],
    175 => ['logo_ibramed.png'],
    176 => ['logo_lofra.jpg'],
    177 => ['logo_anauger.png'],
    178 => ['roca_nova_logo.jpg'],
    180 => ['esab_argentina.jpg'],
    181 => ['esab_colombia.jpg'],
    182 => ['esab_peru.jpg'],
    183 => ['itatiaia_logo.jpg'],
    184 => ['lepono_logo.jpg'],
    185 => ['casa_do_construtor_logo.png'],
    186 => ['mq_professional_logo.png'],
    187 => ['logo_cuisinart.jpg'],
    188 => ['logo_ingco.jpg'],
    189 => ['viapol_logo.png'],
    190 => ['nilfisk_logo.png'],
    196 => ['elgin_refrigeracao.png']
];

if(!array_key_exists($GLOBALS['login_fabrica'],$LOGOS)) {
	$LOGOS[$GLOBALS['login_fabrica']] = array($GLOBALS['login_fabrica_logo']);
}

if (strpos($_SERVER['SCRIPT_FILENAME'], 'admin') === false)
    return $LOGOS;

return array_merge_keys(
    $LOGOS, [
        // valores que devem ser substituÃ­dos
         74 => ['atlas_saa_anim.gif'],
         81 => ['bestway_anim_admin.gif'],
        104 => ['logo_vonder_adm.jpg'],
    ]
);

