<?php
/* Copyright (C) 2026  Fernando Anaya Alba  <consultor.sistemas@ajigsa.com>
 * Ver. 1.0.1
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 *  \file       tpl/tramul_ticket.tpl.php
 *  \ingroup    traspasomultiempresa
 *  \brief      Ticket de traspaso entre tiendas (formato impresora térmica 80mm)
 */

// Incluir el core de Dolibarr
$res = @include('../../../main.inc.php');
if (!$res) {
    $res = @include('../../../../main.inc.php');
}
if (!$res) {
    $res = @include('../../../../../main.inc.php');
}

require_once DOL_DOCUMENT_ROOT."/core/lib/company.lib.php";
require_once DOL_DOCUMENT_ROOT."/user/class/user.class.php";
require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
dol_include_once('/traspasomultiempresa/class/traspaso.class.php');
dol_include_once('/traspasomultiempresa/class/traspasoline.class.php');

global $langs, $db, $mysoc, $conf;

$langs->load('main');
$langs->load('companies');

$id  = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');

// -------------------------
// Cargar objeto Traspaso
// -------------------------
$object = new Traspaso($db);
$result = $object->fetch($id, $ref);

if ($result <= 0) {
    print "<p>Error: No se encontró el traspaso.</p>";
    exit;
}
$object->fetchLines();
// Depuracion temporal
var_dump($object->id, count($object->lines), $object->error, $object->errors);
die();
//

// -------------------------
// Datos de la entidad/sucursal emisora (matriz o entidad actual)
// -------------------------
$ent_label   = '';
$ent_address = '';
$ent_town    = '';
$ent_zip     = '';

if ($conf->entity > 1) {
    $sql_ent = "SELECT label FROM ".MAIN_DB_PREFIX."entity WHERE rowid = ".((int) $conf->entity);
    $res_ent = $db->query($sql_ent);
    if ($res_ent) {
        $obj_ent = $db->fetch_object($res_ent);
        if ($obj_ent) $ent_label = $obj_ent->label;
    }
    $sql_const = "SELECT name, value FROM ".MAIN_DB_PREFIX."const
                  WHERE entity = ".((int) $conf->entity)."
                  AND name IN ('MAIN_INFO_SOCIETE_ADDRESS','MAIN_INFO_SOCIETE_TOWN','MAIN_INFO_SOCIETE_ZIP')";
    $res_const = $db->query($sql_const);
    if ($res_const) {
        while ($obj_c = $db->fetch_object($res_const)) {
            if ($obj_c->name == 'MAIN_INFO_SOCIETE_ADDRESS') $ent_address = $obj_c->value;
            if ($obj_c->name == 'MAIN_INFO_SOCIETE_TOWN')    $ent_town    = $obj_c->value;
            if ($obj_c->name == 'MAIN_INFO_SOCIETE_ZIP')     $ent_zip     = $obj_c->value;
        }
    }
} else {
    $ent_label   = $mysoc->name;
    $ent_address = $mysoc->address;
    $ent_town    = $mysoc->town;
    $ent_zip     = $mysoc->zip;
}

// -------------------------
// Logo
// -------------------------
$logourl = '';
if (!empty($mysoc->logo)) {
    $logofile = $conf->mycompany->dir_output . '/logos/' . $mysoc->logo;
    if (file_exists($logofile)) {
        $logourl = DOL_URL_ROOT . '/viewimage.php?modulepart=mycompany&file=logos/' . urlencode($mysoc->logo);
    }
}

// -------------------------
// Tienda Origen (almacén de origen)
// -------------------------
$almacen_origen = '';
if (!empty($object->fk_warehouse_origen)) {
    $sql_wo = "SELECT ref, description FROM ".MAIN_DB_PREFIX."entrepot WHERE rowid = ".((int) $object->fk_warehouse_origen);
    $res_wo = $db->query($sql_wo);
    if ($res_wo && $db->num_rows($res_wo) > 0) {
        $obj_wo = $db->fetch_object($res_wo);
        $almacen_origen = $obj_wo->ref." ".$obj_wo->description;
    }
}

// -------------------------
// Tienda Destino (almacén de destino)
// -------------------------
$almacen_destino = '';
if (!empty($object->fk_warehouse_destino)) {
    $sql_wd = "SELECT ref, description FROM ".MAIN_DB_PREFIX."entrepot WHERE rowid = ".((int) $object->fk_warehouse_destino);
    $res_wd = $db->query($sql_wd);
    if ($res_wd && $db->num_rows($res_wd) > 0) {
        $obj_wd = $db->fetch_object($res_wd);
        $almacen_destino = $obj_wd->ref." ".$obj_wd->description;
    }
}

// -------------------------
// Usuario que crea y usuario que valida
// -------------------------
$login_creat  = '';
$fecha_creat  = !empty($object->date_creation) ? dol_print_date($object->date_creation, '%d/%m/%y %H:%M') : '';
if (!empty($object->fk_user_creat)) {
    $userTmp = new User($db);
    if ($userTmp->fetch($object->fk_user_creat) > 0) {
        $login_creat = $userTmp->login;
    }
}

$login_valid  = '';
$fecha_valid  = '';
if (!empty($object->fk_user_modif) && $object->status == $object::STATUS_VALIDATED) {
    $userTmp2 = new User($db);
    if ($userTmp2->fetch($object->fk_user_modif) > 0) {
        $login_valid = $userTmp2->login;
    }
    $fecha_valid = !empty($object->tms) ? dol_print_date($object->tms, '%d/%m/%y %H:%M') : '';
}

// -------------------------
// Funciones de formato para el ticket (monoespaciado, 42 columnas ~80mm)
// -------------------------
$ANCHO = 42;

function tk_centrar($texto, $ancho) {
    $texto = trim((string) $texto);
    $len = strlen($texto);
    if ($len >= $ancho) return substr($texto, 0, $ancho);
    $espacios = $ancho - $len;
    $izq = intdiv($espacios, 2);
    $der = $espacios - $izq;
    return str_repeat(' ', $izq) . $texto . str_repeat(' ', $der);
}

function tk_linea($ancho, $char = '=') {
    return str_repeat($char, $ancho);
}

function tk_login_corto($login) {
    if (empty($login)) return '---';
    return strtoupper(substr($login, 0, 4)).'*';
}

function tk_cols($izq, $der, $ancho) {
    $izq = (string) $izq;
    $der = (string) $der;
    $espacio = $ancho - strlen($izq) - strlen($der);
    if ($espacio < 1) $espacio = 1;
    return $izq.str_repeat(' ', $espacio).$der;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket Traspaso <?php echo dol_escape_htmltag($object->ref); ?></title>
    <style type="text/css">
        * { box-sizing: border-box; }

        body {
            font-size: 12px;
            font-family: monospace, courier, "Courier New";
            margin: 8px;
            width: 76mm;
        }

        .logo {
            width: 100%;
            text-align: center;
            margin-bottom: 4px;
        }

        pre.ticket {
            font-family: monospace, courier, "Courier New";
            font-size: 12px;
            white-space: pre;
            margin: 0;
            padding: 0;
            line-height: 1.35;
        }

        .espacio-final {
            height: 25mm;
        }

        @media print {
            @page {
                margin: 0;
                size: 80mm auto;
            }
            body {
                margin: 4px;
            }
        }
    </style>
</head>
<body onload="window.print()">

<?php if (!empty($logourl)) { ?>
<div class="logo">
    <img src="<?php echo $logourl; ?>" style="max-width:140px; max-height:90px;" />
</div>
<?php } ?>

<pre class="ticket"><?php
echo tk_centrar($ent_label, $ANCHO)."\n";
if (!empty($ent_address)) echo tk_centrar($ent_address, $ANCHO)."\n";
$linea_ciudad = trim($ent_town.(!empty($ent_zip) ? ' CP '.$ent_zip : ''));
if (!empty($linea_ciudad)) echo tk_centrar($linea_ciudad, $ANCHO)."\n";
echo "\n";
echo tk_centrar('*** SALIDA POR TRASPASO ***', $ANCHO)."\n";
echo tk_centrar('ENTRE TIENDAS', $ANCHO)."\n";
echo "\n";
echo "Num: ".dol_escape_htmltag($object->ref)."\n";
echo "Fecha: ".(!empty($object->date_creation) ? dol_print_date($object->date_creation, '%d/%m/%y') : '')."\n";
echo "Realizo: ".tk_login_corto($login_creat)." (".$fecha_creat.")\n";
echo "Valido:  ".($login_valid !== '' ? tk_login_corto($login_valid)." (".$fecha_valid.")" : "PENDIENTE")."\n";
echo "\n";
echo "Tienda Origen:\n".dol_escape_htmltag($almacen_origen)."\n";
echo "Tienda Destino:\n".dol_escape_htmltag($almacen_destino)."\n";
if (!empty($object->description)) {
    echo "Descripcion: ".dol_escape_htmltag($object->description)."\n";
}
echo tk_linea($ANCHO)."\n";

$total_prods = 0;
$total_pzas  = 0;
if (!empty($object->lines)) {
    require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
    foreach ($object->lines as $line) {
        $total_prods++;
        $total_pzas += (float) $line->qty;

        $ref_prod = '';
        $lab_prod = '';
        if (!empty($line->fk_product)) {
            $prod = new Product($db);
            if ($prod->fetch($line->fk_product) > 0) {
                $ref_prod = $prod->ref;
                $lab_prod = $prod->label;
            }
        }
        echo dol_escape_htmltag($ref_prod)."  ".dol_escape_htmltag($lab_prod)."\n";
        echo "Cantidad: ".$line->qty."\n";
    }
} else {
    echo "Sin lineas.\n";
}

echo tk_linea($ANCHO)."\n";
echo tk_cols('Total Prods: '.$total_prods, 'Total Pzas: '.$total_pzas, $ANCHO)."\n";
echo "\n";
echo "Impresion: ".dol_print_date(dol_now(), 'dayhour')."\n";
echo "\n";

// Bloque de firmas
$mitad = intdiv($ANCHO, 2);
$col_ancho = $mitad - 1;
echo str_repeat('_', $col_ancho - 2)."  ".str_repeat('_', $col_ancho - 2)."\n";
echo tk_centrar('S U R T I O', $col_ancho)."  ".tk_centrar('R E V I S O', $col_ancho)."\n";
echo "\n";
echo str_repeat('_', $col_ancho - 2)."  ".str_repeat('_', $col_ancho - 2)."\n";
echo tk_centrar('TRASLADO', $col_ancho)."  ".tk_centrar('RECIBIO', $col_ancho)."\n";
?></pre>

<div class="espacio-final"></div>

</body>
</html>