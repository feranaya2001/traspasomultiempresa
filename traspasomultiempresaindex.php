<?php
/* Copyright (C) 2001-2005  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2026		Fernando Anaya Alba			<consultor.sistemas@ajigsa.com>
 * Ver. 1.0.1
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
 */

/**
 *	\file       traspasomultiempresa/traspasomultiempresaindex.php
 *	\ingroup    traspasomultiempresa
 *	\brief      Home page of traspasomultiempresa top menu
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("traspasomultiempresa@traspasomultiempresa"));

$action = GETPOST('action', 'aZ09');

$now = dol_now();
$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5);

// Security check - Protection if external user
$socid = GETPOSTINT('socid');
if (!empty($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
//$hookmanager->initHooks(array($object->element.'index'));

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//if (!isModEnabled('traspasomultiempresa')) {
//	accessforbidden('Module not enabled');
//}
//if (! $user->hasRight('traspasomultiempresa', 'myobject', 'read')) {
//	accessforbidden();
//}
//restrictedArea($user, 'traspasomultiempresa', 0, 'traspasomultiempresa_myobject', 'myobject', '', 'rowid');
//if (empty($user->admin)) {
//	accessforbidden('Must be admin');
//}


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("TraspasoMultiempresaArea"), '', '', 0, 0, '', '', '', 'mod-traspasomultiempresa page-index');

print load_fiche_titre($langs->trans("TraspasoMultiempresaArea"), '', 'traspasomultiempresa.png@traspasomultiempresa');

print '<div class="fichecenter"><div class="fichethirdleft">';

// Grafica: Traspasos validados este mes por Entidad Destino
if (isModEnabled('traspasomultiempresa') && $user->hasRight('traspasomultiempresa', 'traspaso', 'read')) {
    $sql = "SELECT e.label, COUNT(*) as nb";
    $sql .= " FROM ".MAIN_DB_PREFIX."traspasomultiempresa_traspaso as t";
    $sql .= " INNER JOIN ".MAIN_DB_PREFIX."entity as e ON e.rowid = t.entidadDestino";
    $sql .= " WHERE t.status = ".Traspaso::STATUS_VALIDATED;
    $sql .= " AND MONTH(t.tms) = MONTH(NOW()) AND YEAR(t.tms) = YEAR(NOW())";
    $sql .= " AND EXISTS (SELECT 1 FROM ".MAIN_DB_PREFIX."entrepot as we WHERE we.rowid = t.fk_warehouse_origen AND we.entity IN (".getEntity('entrepot')."))";
    $sql .= " GROUP BY e.label ORDER BY nb DESC";

    $resql = $db->query($sql);
    print load_fiche_titre($langs->trans("TraspasosValidadosMesPorEntidad"), '', '');
    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Entidad").' / '.$langs->trans("Cantidad").'</th></tr>';

    if ($resql) {
        $dataseries = array();
        $total = 0;
        while ($obj = $db->fetch_object($resql)) {
            $dataseries[] = array($obj->label, (int) $obj->nb);
            $total += (int) $obj->nb;
        }
        $db->free($resql);

        if ($total > 0) {
            include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
            $dolgraph = new DolGraph();
            $dolgraph->SetData($dataseries);
            $dolgraph->setShowLegend(2);
            $dolgraph->setShowPercent(1);
            $dolgraph->SetType(array('pie'));
            $dolgraph->setHeight('220');
            $dolgraph->draw('idgraphtraspasosmes');
            print '<tr><td colspan="2">'.$dolgraph->show(0).'</td></tr>';
        } else {
            print '<tr><td colspan="2" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
        }
    } else {
        dol_print_error($db);
    }
    print '</table>';
    print '</div>';
}

print '</div><div class="fichetwothirdright">';

// Ultimos 3 traspasos
if (isModEnabled('traspasomultiempresa') && $user->hasRight('traspasomultiempresa', 'traspaso', 'read')) {
    $sql = "SELECT t.rowid, t.ref, t.status, t.tms, e.label as entidad_label";
    $sql .= " FROM ".MAIN_DB_PREFIX."traspasomultiempresa_traspaso as t";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."entity as e ON e.rowid = t.entidadDestino";
    $sql .= " WHERE EXISTS (SELECT 1 FROM ".MAIN_DB_PREFIX."entrepot as we WHERE we.rowid = t.fk_warehouse_origen AND we.entity IN (".getEntity('entrepot')."))";
    $sql .= " ORDER BY t.tms DESC";
    $sql .= $db->plimit(3, 0);

    $resql = $db->query($sql);
    print load_fiche_titre($langs->trans("UltimosTraspasos"), '', '');
    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<th>'.$langs->trans("Ref").'</th>';
    print '<th>'.$langs->trans("EntidadDestino").'</th>';
    print '<th class="right">'.$langs->trans("Fecha").'</th>';
    print '<th class="right">'.$langs->trans("Estado").'</th>';
    print '</tr>';

    if ($resql) {
        $num = $db->num_rows($resql);
        if ($num) {
            $objtraspaso = new Traspaso($db);
            while ($objp = $db->fetch_object($resql)) {
                $objtraspaso->id = $objp->rowid;
                $objtraspaso->ref = $objp->ref;
                $objtraspaso->status = $objp->status;
                print '<tr class="oddeven">';
                print '<td class="nowrap">'.$objtraspaso->getNomUrl(1).'</td>';
                print '<td>'.dol_escape_htmltag($objp->entidad_label).'</td>';
                print '<td class="right nowrap">'.dol_print_date($db->jdate($objp->tms), 'dayhour').'</td>';
                print '<td class="right">'.$objtraspaso->LibStatut($objp->status, 5).'</td>';
                print '</tr>';
            }
        } else {
            print '<tr><td colspan="4" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
        }
        $db->free($resql);
    } else {
        dol_print_error($db);
    }
    print '</table>';
    print '</div>';
}

print '</div></div>';

/* BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject
if (isModEnabled('traspasomultiempresa') && $user->hasRight('traspasomultiempresa', 'read')) {
	$langs->load("orders");

	$sql = "SELECT c.rowid, c.ref, c.ref_client, c.total_ht, c.tva as total_tva, c.total_ttc, s.rowid as socid, s.nom as name, s.client, s.canvas";
	$sql.= ", s.code_client";
	$sql.= " FROM ".$db->prefix()."commande as c";
	$sql.= ", ".$db->prefix()."societe as s";
	$sql.= " WHERE c.fk_soc = s.rowid";
	$sql.= " AND c.fk_statut = 0";
	$sql.= " AND c.entity IN (".getEntity('commande').")";
	if ($socid)	$sql.= " AND c.fk_soc = ".((int) $socid);

	$resql = $db->query($sql);
	if ($resql)
	{
		$total = 0;
		$num = $db->num_rows($resql);

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">'.$langs->trans("DraftMyObjects").($num?'<span class="badge marginleftonlyshort">'.$num.'</span>':'').'</th></tr>';

		$var = true;
		if ($num > 0)
		{
			$i = 0;
			while ($i < $num)
			{

				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven"><td class="nowrap">';

				$myobjectstatic->id=$obj->rowid;
				$myobjectstatic->ref=$obj->ref;
				$myobjectstatic->ref_client=$obj->ref_client;
				$myobjectstatic->total_ht = $obj->total_ht;
				$myobjectstatic->total_tva = $obj->total_tva;
				$myobjectstatic->total_ttc = $obj->total_ttc;

				print $myobjectstatic->getNomUrl(1);
				print '</td>';
				print '<td class="nowrap">';
				print '</td>';
				print '<td class="right" class="nowrap">'.price($obj->total_ttc).'</td></tr>';
				$i++;
				$total += $obj->total_ttc;
			}
			if ($total>0)
			{

				print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td colspan="2" class="right">'.price($total)."</td></tr>";
			}
		}
		else
		{

			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoOrder").'</td></tr>';
		}
		print "</table><br>";

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}
END MODULEBUILDER DRAFT MYOBJECT */


//print '</div><div class="fichetwothirdright">';


/* BEGIN MODULEBUILDER LASTMODIFIED MYOBJECT
// Last modified myobject
if (isModEnabled('traspasomultiempresa') && $user->hasRight('traspasomultiempresa', 'read')) {
	$sql = "SELECT s.rowid, s.ref, s.label, s.date_creation, s.tms";
	$sql.= " FROM ".$db->prefix()."traspasomultiempresa_myobject as s";
	$sql.= " WHERE s.entity IN (".getEntity($myobjectstatic->element).")";
	//if ($socid)	$sql.= " AND s.rowid = $socid";
	$sql .= " ORDER BY s.tms DESC";
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="2">';
		print $langs->trans("BoxTitleLatestModifiedMyObjects", $max);
		print '</th>';
		print '<th class="right">'.$langs->trans("DateModificationShort").'</th>';
		print '</tr>';
		if ($num)
		{
			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);

				$myobjectstatic->id=$objp->rowid;
				$myobjectstatic->ref=$objp->ref;
				$myobjectstatic->label=$objp->label;
				$myobjectstatic->status = $objp->status;

				print '<tr class="oddeven">';
				print '<td class="nowrap">'.$myobjectstatic->getNomUrl(1).'</td>';
				print '<td class="right nowrap">';
				print "</td>";
				print '<td class="right nowrap">'.dol_print_date($db->jdate($objp->tms), 'day')."</td>";
				print '</tr>';
				$i++;
			}

			$db->free($resql);
		} else {
			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
		}
		print "</table><br>";
	}
}
*/

print '</div></div>';

// End of page
llxFooter();
$db->close();
