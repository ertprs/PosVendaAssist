<?php

//include '/var/www/assist/www/dbconfig.php';
//include '/var/www/includes/dbconnect-inc.php';
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($login_fabrica <> 10){
  header('LOCATION: menu_gerencia.php');
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
    <META NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW">
    <title></title>
<?// Segue a declarção de CSS?>
    <style type="text/css">
    a img {border: 0}
    #clientes_lst {
        border-bottom:1.5em solid transparent;
        width: 790px;
    }
    #clientes_lst span.logo {
        display: inline-block;
        height: 120px;
        width: 172px;
        margin: auto auto 2em 10px;
        border: 5px solid #EEE;
        border-bottom-width: 20px;
        text-align: center;
        box-shadow: 3px 2px 4px #666;
        border-radius: 6px;
    }
    .logo a {
        display: block;
        margin: -1em auto;
        line-height: 120px;
    }
    #clientes_lst .logo img {
        min-width: 120px;
        max-width: 155px;
        vertical-align: middle;
        margin: auto;
    }
    #clientes_lst .logo img.img_1 {
        position: relative;
        top: -5px;
        margin-top: 2px;
        height: 110px;
        width: auto;
        min-width: initial;
        max-width: initial;
    }
    </style>
</head>
<body><center>
<div id='clientes_lst'>
<?
$sql = "SELECT  CASE WHEN nome = 'Precision'
                     THEN 'Amvox'
                     ELSE nome
                END                             AS nome ,
                logo                                    ,
                site
        FROM    tbl_fabrica
        WHERE   fabrica NOT IN( 0,1,2,3,4,6,9,10,11,12,13,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,
                              36,37,38,39,40,41,42,44,45,46,47,48,49,50,52,55,56,57,58,59,60,61,62,63,64,65,67,68,
                              69,70,71,72,73,74,75,76,77,78,80,81,85,86,87,88,90,91,92,93,94,95,96,98,99,100,101,102,103,104,106,107,109,114,
                              115,116,117,119,120,121,122,123,124,125,126,127,128,129,166)
        AND     logo IS NOT NULL
        AND     nome !~* 'pedido'
        AND     ativo_fabrica \n".
            //"UNION SELECT 'BOSCH Security Systems'     ,'bosch.png'               , 'http://www.boschsecurity.com.br' ".

       "UNION SELECT 'Amvox'                        , 'amvox_clientes.jpg'                , 'http://www.amvox.com.br' ".
       "UNION SELECT 'BestWay'                      , 'bestway_clientes.jpg'              , 'http://www.georgeforeman.com.br/novo/' ".
       "UNION SELECT 'Black & Decker'               , 'black_clientes.jpg'                , 'http://www.blackedecker.com.br/' ".
       "UNION SELECT 'Bosch'                        , 'bosh_clientes.jpg'                 , 'http://www.brasil.bosch.com.br/' ".
       "UNION SELECT 'Britania'                     , 'britania_clientes.jpg'             , 'http://www.britania.com.br/' ".
       "UNION SELECT 'Cadence'                      , 'cadence_clientes.jpg'              , 'http://www.cadence.com.br/' ".
       "UNION SELECT 'Canon'                        , 'cannon_clientes.jpg'               , 'http://www.elgin.com.br' ".
       "UNION SELECT 'Cobimex'                      , 'cobimex_clientes.jpg'              , 'http://www.cobimex.com.br' ".
       "UNION SELECT 'Delonghi'                     , 'delonghi_clientes.jpg'             , 'http://www.delonghi.com/pt-BR/' ".
       "UNION SELECT 'DeWalt by Black & Decker'     , 'dewalt_clientes.jpg'               , 'http://www.dewalt.com.br' ".
       "UNION SELECT 'Disma'                        , 'disma_clientes.jpg'                , 'http://www.ovd.com.br' ".
       "UNION SELECT 'DWT'                          , 'dwt_clientes.jpg'                  , 'http://www.ovd.com.br' ".
       "UNION SELECT 'Eccofer'                      , 'ecoffer_clientes.jpg'              , 'http://www.ovd.com.br' ".
       "UNION SELECT 'Elgin'                        , 'elgin_clientes.jpg'                , 'https://www.elgin.com.br/' ".
       "UNION SELECT 'Esmaltec'                     , 'esmaltec_clientes.jpg'             , 'http://www.esmaltec.com.br/' ".
       "UNION SELECT 'Everest'                      , 'everest_clientes.jpg'              , 'http://www.everest.ind.br/' ".
       "UNION SELECT 'Famastil'                     , 'famastil_clientes.jpg'             , 'http://www.famastiltaurus.com.br/' ".
       "UNION SELECT 'Fricon'                       , 'fricon_clientes.jpg'               , 'http://www.fricon.com.br/' ".
       "UNION SELECT 'Gamma'                        , 'gamma_clientes.jpg'                , 'http://gammaferramentas.com.br/' ".
       "UNION SELECT 'Gelopar'                      , 'gelopar_clientes.jpg'              , 'http://www.gelopar.com.br/index.htm' ".
       "UNION SELECT 'geoMetais by Black & Decker'  , 'geo_clientes.jpg'                  , 'http://www.geometais.com.br' ".
       "UNION SELECT 'George Foreman'               , 'georgeforeman_clientes.jpg'        , 'http://www.georgeforeman.com.br/novo/' ".
       "UNION SELECT 'Goodyear'                     , 'goodyear_clientes.jpg'             , 'http://www.goodyear.com.br/' ".
       "UNION SELECT 'Grupo OVD'                    , 'ovd_clientes.jpg'                  , 'http://www.ovd.com.br/' ".
       //"UNION SELECT 'Hitachi'                      , 'hitachi_clientes.jpg'              , 'http://www.hitachi.com.br/' ".
       "UNION SELECT 'Hymair'                       , 'hymair_clientes.jpg'               , 'http://www.ovd.com.br' ".
       "UNION SELECT 'IBBL'                         , 'ibbl_clientes.jpg'                 , 'http://ibbl.com.br/' ".
       "UNION SELECT 'Jacto'                        , 'jacto_clientes.jpg'                , 'http://www.jacto.com.br/' ".
       "UNION SELECT 'Latina'                       , 'latina_clientes.jpg'               , 'http://www.latina.com.br/' ".
       //"UNION SELECT 'Leadership'                   , 'leadership_clientes.jpg'           , 'http://www.leadership.com.br' ".
       //"UNION SELECT 'Leadership Feminina'          , 'leadership_feminina_clientes.jpg'  , 'http://www.leadership.com.br' ".
       //"UNION SELECT 'LeaderShip Gamer'             , 'gammer_clientes.jpg'               , 'http://www.leadership.com.br' ".
       "UNION SELECT 'Lenoxx'                       , 'lenox_clientes.jpg'                , 'http://www.lenoxxsound.com.br/' ".
       "UNION SELECT 'Lorenzetti'                   , 'lorenzetti_clientes.jpg'           , 'http://www.lorenzetti.com.br/' ".
       "UNION SELECT 'Makita'                       , 'makita_clientes.jpg'               , 'http://www.makita.com.br/' ".
       "UNION SELECT 'Mallory'                      , 'mallory_clientes.jpg'              , 'http://www.mallory.com.br/' ".
       "UNION SELECT 'Master Frio'                  , 'masterfrio_clientes.jpg'           , 'http://www.masterfrio.com.br/' ".
       "UNION SELECT 'Melitta'                      , 'melita_clientes.jpg'               , 'http://www.georgeforeman.com.br/novo/' ".
       "UNION SELECT 'Michelin'                     , 'michelin_clientes.jpg'             , 'http://www.cobimex.com.br' ".
       "UNION SELECT 'Milwaukee'                    , 'milwaukee_clientes.jpg'            , 'http://www.milwaukeetool.com/' ".
       "UNION SELECT 'Newup'                        , 'newup_clientes.jpg'                , 'http://www.newup.com.br/' ".
       "UNION SELECT 'NKS'                          , 'nks_clientes.jpg'                  , 'http://www.nksonline.com.br/' ".
       "UNION SELECT 'Nordtech'                     , 'nordtech_clientes.jpg'             , 'http://www.nordtech-brasil.com.br/' ".
       "UNION SELECT 'Norton Clipper'               , 'norton_clientes.jpg'               , 'http://www.norton-abrasivos.com.br/linha-clipper.aspx' ".
       "UNION SELECT 'nove54'                       , 'nove54_clientes.jpg'               , 'http://www.ovd.com.br' ".
       "UNION SELECT 'Orbis'                        , 'orbis_clientes.jpg'                , 'http://www.orbisdobrasil.com.br/' ".
       "UNION SELECT 'Oster'                        , 'oster_clientes.jpg'                , 'http://http://www.osterbrasil.com/' ".
       "UNION SELECT 'Philco'                       , 'philco_clientes.jpg'               , 'http://www.philco.com.br' ".
       "UNION SELECT 'Positec'                      , 'positec_clientes.jpg'              , 'http://www.positecgroup.com' ".
       "UNION SELECT 'Rayovac'                      , 'rayovac_clientes.jpg'              , 'http://la.rayovac.com/?pais_id=4' ".
       "UNION SELECT 'Remington'                    , 'remington_clientes.jpg'            , 'http://www.productosremington.com/landing.html' ".
       "UNION SELECT 'Rinnai'                       , 'rinai_clientes.jpg'                , 'http://www.rinnai.com.br/' ".
       "UNION SELECT 'Russell Hobbs'                , 'russelhobs_clientes.jpg'           , 'http://www.russellhobbs.com.br/' ".
       "UNION SELECT 'Saint-Gobain'                 , 'saintgobain_clientes.jpg'          , 'http://www.saint-gobain.com.br/19' ".
       "UNION SELECT 'Sight'                        , 'sight_clientes.jpg'                , 'http://www.sightgps.com.br/' ".
       "UNION SELECT 'Skill'                        , 'skill_clientes.jpg'                , 'http://www.herramientasskil.com.ar/index.aspx' ".
       "UNION SELECT 'Spectrum'                     , 'spectrum_brands_clientes.jpeg'     , 'http://www.spectrumbrands.com/' ".
       "UNION SELECT 'Stanley Black & Decker'       , 'stanley_clientes.jpg'              , 'http://www.stanleyblackanddecker.com/' ".
       "UNION SELECT 'Tectoy'                       , 'tectoy_clientes.jpg'               , 'http://www.tectoy.com.br/' ".
       "UNION SELECT 'Tekna'                        , 'tekna_clientes.jpg'                , 'http://www.nordtech.com.br' ".
       "UNION SELECT 'Thermo King'                  , 'termoking_clientes.jpg'            , 'http://www.thermoking.com.br' ".
       "UNION SELECT 'ToastMaster'                  , 'toastmaster_clientes.jpg'          , 'http://www.georgeforeman.com.br/novo/' ".
       "UNION SELECT 'Toyama'                       , 'toyama_clientes.jpg'               , 'http://www.toyama.com.br/' ".
       "UNION SELECT 'Vonder'                       , 'vonder_clientes.jpg'               , 'http://www.ovd.com.br' ".
       "UNION SELECT 'Wanke'                        , 'wanke_clientes.jpg'                , 'http://www.wanke.com.br/' ".
       "UNION SELECT 'Wesco'                        , 'wesco_clientes.jpg'                , 'http://www.ferramentaswesco.com.br' ".
       "UNION SELECT 'White Westinghouse'           , 'westinghouse_clientes.jpg'         , 'http://www.georgeforeman.com.br/novo/' ".
       "UNION SELECT 'Worx'                         , 'worx_clientes.jpg'                 , 'http://www.positecgroup.com' ".
       "UNION SELECT 'Wurth'                        , 'wurth_clientes.jpg'                , 'https://www.wurth.com.br/PT/Default.aspx' ".


       "ORDER BY nome";
$res = pg_query($con,$sql);
$tot = pg_num_rows($res);
for ($i = 0 ; $i < $tot ; $i++) {
    $nome   = utf8_encode(pg_fetch_result ($res,$i,nome));
    $logo   = trim(pg_fetch_result ($res,$i,logo));
    $site   = trim(pg_fetch_result ($res,$i,site));
    //$urlLogo= (strpos($logo, "tp://")) ? $logo : "/assist/logos/$logo" ;
    //$pathImg= '/var/www/assist/www/logos/' . basename($logo);
    $urlLogo= (strpos($logo, "tp://")) ? $logo : "logos/$logo" ;
    $pathImg= 'logos/' . basename($logo);
    if (!file_exists($pathImg)) {continue;}

    list($width, $height) = getimagesize($pathImg);
    $aspect = $width/$height;
    switch ($aspect) {

      case $aspect < 1.5:
          $imgClass = 'img_1';
          break;
    // case $aspect < 2:
    //     $imgClass = 'img_2';
    //     break;
    //     break;
    // case $aspect < 3:
    //     $imgClass = 'img_3';
    //     break;
    //     break;
    // case $aspect < 4:
    //     $imgClass = 'img_4';
    //     break;
    //     break;
    // case $aspect < 5:
    //     $imgClass = 'img_5';
    //     break;
    //     break;
    // case $aspect >= 5:
    //     $imgClass = 'img_6';
    //     break;
    }
    if ($imgClass) $imgClass=" class='$imgClass'";
?>
    <span class='logo' rel='<?=$aspect?>'>&nbsp;
        <a href='<?=$site?>' target='_blank' title='<?="$nome - $site"?>'>
            <img alt='<?=$nome?>'<?=$imgClass?> src='<?=$urlLogo?>' />
        </a>
    </span>
<?}
?>
</div>
</body>
</html>
