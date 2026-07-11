<?php
/* Copyright (C) 2004-2014	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2008		Raphael Bertrand			<raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2014	Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2012		Christophe Battarel			<christophe.battarel@altairis.fr>
 * Copyright (C) 2012		Cédric Salvador				<csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014	Raphaël Doursenaud			<rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015		Marcos García				<marcosgdf@gmail.com>
 * Copyright (C) 2017		Ferran Marcet				<fmarcet@2byte.es>
 * Copyright (C) 2018-2025  Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2024-2025	MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2026		Fernando Anaya Alba			<consultor.sistemas@ajigsa.com>
 * Ver. 1.0.0
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *  \file       tpl/tramul_ticket.tpl.php
 *  \ingroup    traspasomultiempresa
 *  \brief      File of class to generate document type ticket from standard template
 */

// Incluir el core de Dolibarr
$res = @include('../../../main.inc.php');
if (!$res) {
    $res = @include('../../../../main.inc.php');
}
if (!$res) {
    $res = @include('../../../../../main.inc.php');
}

require_once DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/company.lib.php";
require_once DOL_DOCUMENT_ROOT."/societe/class/societe.class.php";
require_once DOL_DOCUMENT_ROOT."/user/class/user.class.php";

global $langs, $db, $mysoc, $conf;

$langs->load('main');
$langs->load('propal');
$langs->load('bills');
$langs->load('companies');

$id  = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');

// -------------------------
// Textos personalizables
// -------------------------
$leyenda_texto = "Esta cotización tiene una vigencia de 15 días a partir de la fecha de emisión.";
$leyenda_final = "Precios sujetos a cambio sin previo aviso.";

// -------------------------
// Función: número a letras
// -------------------------
function numeroALetras($numero, $mayusculas = false) {
    $numero   = number_format($numero, 2, '.', '');
    list($entero, $decimal) = explode('.', $numero);
    $resultado = convertirEntero($entero);
    $resultado .= ($decimal > 0) ? ' ' . $decimal . '/100' : ' 00/100';
    $resultado .= ' M.N.';
    return strtoupper($resultado);
}

function convertirEntero($numero) {
    $numero = intval($numero);
    if ($numero == 0) return 'CERO PESOS';
    $resultado = '';
    if ($numero >= 1000000) {
        $millones = intval($numero / 1000000);
        $resultado .= ($millones == 1) ? 'UN MILLON ' : convertirGrupo($millones) . ' MILLONES ';
        $numero = $numero % 1000000;
    }
    if ($numero >= 1000) {
        $miles = intval($numero / 1000);
        $resultado .= ($miles == 1) ? 'MIL ' : convertirGrupo($miles) . ' MIL ';
        $numero = $numero % 1000;
    }
    if ($numero > 0) $resultado .= convertirGrupo($numero);
    return trim($resultado) . ' PESOS';
}

function convertirGrupo($numero) {
    $unidades = ['','UN','DOS','TRES','CUATRO','CINCO','SEIS','SIETE','OCHO','NUEVE'];
    $decenas  = [
        10=>'DIEZ',11=>'ONCE',12=>'DOCE',13=>'TRECE',14=>'CATORCE',
        15=>'QUINCE',16=>'DIECISEIS',17=>'DIECISIETE',18=>'DIECIOCHO',19=>'DIECINUEVE',
        20=>'VEINTE',30=>'TREINTA',40=>'CUARENTA',50=>'CINCUENTA',
        60=>'SESENTA',70=>'SETENTA',80=>'OCHENTA',90=>'NOVENTA'
    ];
    $centenas = [
        100=>'CIENTO',200=>'DOSCIENTOS',300=>'TRESCIENTOS',400=>'CUATROCIENTOS',
        500=>'QUINIENTOS',600=>'SEISCIENTOS',700=>'SETECIENTOS',800=>'OCHOCIENTOS',900=>'NOVECIENTOS'
    ];
    $resultado = '';
    $centena   = intval($numero / 100) * 100;
    $decena    = $numero % 100;
    $unidad    = $numero % 10;
    if ($centena > 0) {
        $resultado .= ($numero == 100) ? 'CIEN' : $centenas[$centena] . ' ';
        if ($numero == 100) return $resultado;
    }
    if ($decena >= 10 && $decena <= 20) {
        $resultado .= $decenas[$decena];
    } elseif ($decena > 20 && $decena < 30) {
        $resultado .= 'VEINTI' . $unidades[$unidad];
    } elseif ($decena >= 30) {
        $decenaBase = intval($decena / 10) * 10;
        $resultado .= $decenas[$decenaBase];
        if ($unidad > 0) $resultado .= ' Y ' . $unidades[$unidad];
    } elseif ($unidad > 0) {
        $resultado .= $unidades[$unidad];
    }
    return trim($resultado);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización Ticket</title>
    <style type="text/css">
        * { box-sizing: border-box; }

        body {
            font-size: 12px;
            font-family: monospace, courier, arial, helvetica, system;
            margin: 10px;
            width: 72mm; /* ancho útil en papel 80mm */
        }

        .logo {
            width: 100%;
            text-align: center;
            margin-bottom: 4px;
        }

        .encabezado {
            width: 100%;
            text-align: center;
            font-size: 11px;
            margin-bottom: 4px;
        }

        .separador {
            border: none;
            border-top: 1px dashed #000;
            margin: 4px 0;
        }

        .titulo-doc {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin: 4px 0;
        }

        .datos-cliente {
            font-size: 11px;
            margin: 4px 0;
            text-align: left;
        }

        table.articulos {
            width: 100%;
            font-size: 11px;
            border-collapse: collapse;
        }

        table.articulos thead th {
            border-bottom: 1px dashed #000;
            border-top: 1px dashed #000;
            text-align: left;
            padding: 2px 0;
        }

        table.articulos td {
            padding: 2px 0;
            vertical-align: top;
        }

        table.articulos td.precio {
            text-align: right;
            white-space: nowrap;
        }

        table.totales {
            width: 100%;
            font-size: 12px;
            margin-top: 4px;
            border-collapse: collapse;
        }

        table.totales td {
            padding: 1px 0;
        }

        table.totales td.label {
            text-align: left;
        }

        table.totales td.monto {
            text-align: right;
            white-space: nowrap;
        }

        table.totales tr.total-final td {
            font-weight: bold;
            font-size: 13px;
            border-top: 1px dashed #000;
            padding-top: 3px;
        }

        .letras {
            font-size: 10px;
            text-align: center;
            margin: 4px 0;
            font-style: italic;
        }

        .fecha-info {
            text-align: center;
            font-size: 11px;
            margin: 4px 0;
        }

        .leyenda {
            text-align: center;
            font-size: 10px;
            margin-top: 6px;
        }

        .vigencia {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            margin: 4px 0;
        }

        @media print {
            @page {
                margin: 0;
                size: 80mm auto;
            }
            body {
                margin: 5px;
            }
        }
    </style>
</head>
<body onload="window.print()">

<?php
// -------------------------
// Cargar objeto Propal
// -------------------------
$object = new Propal($db);
$result = $object->fetch($id, $ref);

if ($result <= 0) {
    print "<p>Error: No se encontró la cotización.</p>";
    exit;
}

// Cargar datos relacionados
$client = new Societe($db);
$client->fetch($object->socid);

$userstatic = new User($db);
if (!empty($object->fk_user_author)) {
    $userstatic->fetch($object->fk_user_author);
}
// Consultar login directo a la BD como respaldo
$user_login = !empty($userstatic->login) ? $userstatic->login : '';
if (empty($user_login)) {
    $sql_login = "SELECT u.login FROM ".MAIN_DB_PREFIX."user u
                  INNER JOIN ".MAIN_DB_PREFIX."propal p ON p.fk_user_author = u.rowid
                  WHERE p.rowid = ".((int) $object->id);
    $res_login = $db->query($sql_login);
    if ($res_login) {
        $obj_login = $db->fetch_object($res_login);
        if ($obj_login) $user_login = $obj_login->login;
    }
}

// -------------------------
// Datos de la entidad/sucursal
// Si entity > 1 leer de llx_entity y llx_const, si no usar $mysoc
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
?>

<!-- LOGO -->
<?php
$logourl = '';
// Buscar logo de la empresa en la ruta estándar de Dolibarr
if (!empty($mysoc->logo)) {
    $logofile = $conf->mycompany->dir_output . '/logos/' . $mysoc->logo;
    if (file_exists($logofile)) {
        $logourl = DOL_URL_ROOT . '/viewimage.php?modulepart=mycompany&file=logos/' . urlencode($mysoc->logo);
    }
}
if (!empty($logourl)) {
?>
<div class="logo">
    <img src="<?php echo $logourl; ?>" style="max-width:120px; max-height:80px;" />
</div>
<?php } ?>

<!-- ENCABEZADO EMPRESA -->
<div class="encabezado">
    <strong><?php echo $mysoc->name; ?></strong><br/>
    <?php if (!empty($mysoc->idprof1)) echo "RFC: " . $mysoc->idprof1 . "<br/>"; ?>
    <?php echo $mysoc->address . "<br/>"; ?>
    <?php echo $mysoc->town . " CP: " . $mysoc->zip . "<br/>"; ?>
    <?php if (!empty($mysoc->phone)) echo "Tel: " . $mysoc->phone . "<br/>"; ?>
    <?php if ($conf->entity > 1 && !empty($ent_label)) { ?>
    <br/>
    <strong><?php echo $ent_label; ?></strong><br/>
    <?php if (!empty($ent_address)) echo $ent_address . "<br/>"; ?>
    <?php if (!empty($ent_town))    echo $ent_town . " CP: " . $ent_zip . "<br/>"; ?>
    <?php } ?>
</div>

<hr class="separador"/>

<!-- TÍTULO Y NÚMERO -->
<div class="titulo-doc">REMISIÓN-COTIZACIÓN</div>
<div class="encabezado">
    <strong><?php echo $object->ref; ?></strong><br/>
    <?php
    echo (!empty($user_login) ? "Atendió: " . $user_login . "&nbsp;&nbsp;" : "");
    echo "Fecha: " . dol_print_date($object->date, 'day');
    ?><br/>
</div>

<hr class="separador"/>

<!-- DATOS DEL CLIENTE -->
<div class="datos-cliente">
    <strong>Cliente:</strong> <?php echo $client->name; ?><br/>
    <?php if (!empty($client->idprof1)) echo "RFC: " . $client->idprof1 . "<br/>"; ?>
    <?php if (!empty($client->address)) echo $client->address . "<br/>"; ?>
    <?php if (!empty($client->town)) echo $client->town . " CP: " . $client->zip . "<br/>"; ?>
    <?php if (!empty($client->phone)) echo "Tel: " . $client->phone . "<br/>"; ?>
    <?php if (!empty($object->note_public)) echo "<br/>" . $object->note_public . "<br/>"; ?>
</div>

<hr class="separador"/>

<!-- LÍNEAS DE PRODUCTOS -->
<table class="articulos">
    <thead>
        <tr>
            <th style="width:55%;">Descripción</th>
            <th style="text-align:center; width:10%;">Qty</th>
            <th style="text-align:right; width:17%;">P.Unit</th>
            <th style="text-align:right; width:18%;">Total</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $subtotal    = [];
    $subtotaltva = [];

    if (!empty($object->lines)) {
        foreach ($object->lines as $line) {
            // Etiqueta del producto
            $label = (!empty($line->product_label)) ? $line->product_label : $line->desc;
            $label = strip_tags($label);

            // Precio unitario con IVA, redondeado a 2 decimales
            $precio_unit = round($line->subprice * (1 + $line->tva_tx / 100), 2);
            $total_linea = price2num($line->total_ttc);

            echo "<tr>\n";
            // Ref + descripción
            echo "<td>";
            if (!empty($line->ref)) echo "<small>" . $line->ref . "</small><br/>";
            echo $label . "</td>\n";
            // Cantidad
            echo "<td style=\"text-align:center;\">" . $line->qty . "</td>\n";
            // Precio unitario
            echo "<td class=\"precio\">" . price($precio_unit) . "</td>\n";
            // Total línea
            echo "<td class=\"precio\">" . price($total_linea) . "</td>\n";
            echo "</tr>\n";

            // Acumulados por tasa de IVA — inicializar antes de sumar para evitar warnings
            if (!isset($subtotal[$line->tva_tx]))    $subtotal[$line->tva_tx]    = 0;
            if (!isset($subtotaltva[$line->tva_tx])) $subtotaltva[$line->tva_tx] = 0;
            $subtotal[$line->tva_tx]    += $line->total_ht;
            $subtotaltva[$line->tva_tx] += $line->total_tva;
        }
    } else {
        echo "<tr><td colspan=\"4\">Sin líneas.</td></tr>\n";
    }
    ?>
    </tbody>
</table>

<!-- TOTALES -->
<table class="totales">
    <?php
    // Subtotal por tasa de IVA
    if (!empty($subtotal)) {
        foreach ($subtotal as $tva => $ht) {
            $tva_label = ($tva > 0) ? '(' . (int)$tva . '%)' : '';
            echo "<tr>";
            echo "<td class=\"label\">Subtotal " . $tva_label . "</td>";
            echo "<td class=\"monto\">" . price(price2num($ht)) . "</td>";
            echo "</tr>\n";

            if (!empty($subtotaltva[$tva]) && $subtotaltva[$tva] != 0) {
                echo "<tr>";
                echo "<td class=\"label\">IVA " . (int)$tva . "%</td>";
                echo "<td class=\"monto\">" . price(price2num($subtotaltva[$tva])) . "</td>";
                echo "</tr>\n";
            }
        }
    }

    // Descuento global si aplica
    if (!empty($object->remise_percent) && $object->remise_percent > 0) {
        echo "<tr>";
        echo "<td class=\"label\">Descuento " . $object->remise_percent . "%</td>";
        echo "<td class=\"monto\">-" . price(price2num($object->total_ht * $object->remise_percent / 100)) . "</td>";
        echo "</tr>\n";
    }
    ?>
    <tr class="total-final">
        <td class="label">TOTAL</td>
        <td class="monto"><?php echo price(price2num($object->total_ttc)); ?></td>
    </tr>
</table>

<!-- TOTAL EN LETRAS -->
<div class="letras">
    <?php echo numeroALetras($object->total_ttc, true); ?>
</div>

<hr class="separador"/>

<!-- VIGENCIA -->
<?php if (!empty($object->date_fin_validite)) { ?>
<div class="vigencia">
    Vigencia: <?php echo dol_print_date($object->date_fin_validite, 'day'); ?>
</div>
<?php } ?>

<!-- FECHA Y HORA DE IMPRESIÓN -->
<div class="fecha-info">
    Impreso: <?php echo dol_print_date(dol_now(), 'dayhour'); ?>
</div>

<hr class="separador"/>

<!-- LEYENDA -->
<div class="leyenda">
    <p><?php echo $leyenda_texto; ?></p>
    <p><strong><?php echo $leyenda_final; ?></strong></p>
</div>

</body>
</html>
