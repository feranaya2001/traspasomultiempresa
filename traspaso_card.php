<?php
// ... Abajo de esto ya continúan tus requires nativos como main.inc.php ...
ini_set('display_errors', 1);
error_reporting(E_ALL);
/* Copyright (C) 2017       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024-2025  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2026		Fernando Anaya Alba			<consultor.sistemas@ajigsa.com>
 * Version: 1.0.10
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
 *    \file       traspaso_card.php
 *    \ingroup    traspasomultiempresa
 *    \brief      Page to create/edit/view traspaso
 */


// General defined Options
//if (! defined('CSRFCHECK_WITH_TOKEN'))     define('CSRFCHECK_WITH_TOKEN', '1');					// Force use of CSRF protection with tokens even for GET
//if (! defined('MAIN_AUTHENTICATION_MODE')) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined('MAIN_LANG_DEFAULT'))        define('MAIN_LANG_DEFAULT', 'auto');					// Force LANG (language) to a particular value
//if (! defined('MAIN_SECURITY_FORCECSP'))   define('MAIN_SECURITY_FORCECSP', 'none');				// Disable all Content Security Policies
//if (! defined('NOBROWSERNOTIF'))     		 define('NOBROWSERNOTIF', '1');					// Disable browser notification
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');						// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined('NOLOGIN'))                  define('NOLOGIN', '1');						// Do not use login - if this page is public (can be called outside logged session). This includes the NOIPCHECK too.
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       	  		// Do not load ajax.lib.php library
//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');					// Do not create database handler $db
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');					// Do not load html.form.class.php
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');					// Do not load and show top and left menu
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');					// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');					// Do not load object $langs
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');					// Do not load object $user
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');			// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');			// Do not check injection attack on POST parameters
//if (! defined('NOSESSION'))                define('NOSESSION', '1');						// On CLI mode, no need to use web sessions
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');					// Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');					// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)


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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
dol_include_once('/traspasomultiempresa/class/traspaso.class.php');
dol_include_once('/traspasomultiempresa/lib/traspasomultiempresa_traspaso.lib.php');

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("traspasomultiempresa@traspasomultiempresa", "other"));

// Get parameters
$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$lineid   = GETPOSTINT('lineid');

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)).basename(__FILE__, '.php')); // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');					// if not set, a default page will be used
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');	// if not set, $backtopage will be used
$optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')
$dol_openinpopup = GETPOST('dol_openinpopup', 'aZ09');

// Initialize a technical objects
$object = new Traspaso($db);
$object->table_element_line = 'traspasomultiempresa_traspasoline'; //NEW FAA
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->traspasomultiempresa->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array($object->element.'card', 'globalcard')); // Note that conf->hooks_modules contains array
$soc = null;

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);


$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criteria
$search_all = trim(GETPOST("search_all", 'alpha'));
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha')) {
		$search[$key] = GETPOST('search_'.$key, 'alpha');
	}
}

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'.

// There is several ways to check permission.
// Set $enablepermissioncheck to 1 to enable a minimum low level of checks
$enablepermissioncheck = getDolGlobalInt('TRASPASOMULTIEMPRESA_ENABLE_PERMISSION_CHECK');
if ($enablepermissioncheck) {
	$permissiontoread = $user->hasRight('traspasomultiempresa', 'traspaso', 'read');
	$permissiontoadd = $user->hasRight('traspasomultiempresa', 'traspaso', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	$permissiontodelete = $user->hasRight('traspasomultiempresa', 'traspaso', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
	$permissionnote = $user->hasRight('traspasomultiempresa', 'traspaso', 'write'); // Used by the include of actions_setnotes.inc.php
	$permissiondellink = $user->hasRight('traspasomultiempresa', 'traspaso', 'write'); // Used by the include of actions_dellink.inc.php
} else {
	$permissiontoread = 1;
	$permissiontoadd = 1; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	$permissiontodelete = 1;
	$permissionnote = 1;
	$permissiondellink = 1;
}

$upload_dir = $conf->traspasomultiempresa->multidir_output[isset($object->entity) ? $object->entity : 1].'/traspaso';

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (isset($object->status) && ($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->module, $object, $object->table_element, $object->element, 'fk_soc', 'rowid', $isdraft);
if (!isModEnabled($object->module)) {
	accessforbidden("Module ".$object->module." not enabled");
}
if (!$permissiontoread) {
	accessforbidden();
}

$error = 0;


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$backurlforlist = dol_buildpath('/traspasomultiempresa/traspaso_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/traspasomultiempresa/traspaso_card.php', 1).'?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	$triggermodname = 'TRASPASOMULTIEMPRESA_MYOBJECT_MODIFY'; // Name of trigger action code to execute when we modify record

	// Seguridad: bloquear altas/bajas/cambios de línea si el traspaso ya no está en borrador
	if (in_array($action, array('addline', 'updateline', 'deleteline', 'confirm_deleteline')) && $object->status != $object::STATUS_DRAFT) {
		setEventMessages('No se permiten cambios en un traspaso ya validado.', null, 'errors');
		$action = '';
	}

	// Seguridad: una vez validado, no se permite regresar a borrador
	if ($action == 'confirm_setdraft' && $object->status == $object::STATUS_VALIDATED) {
		setEventMessages('No se permite regresar a borrador un traspaso ya validado.', null, 'errors');
		$action = '';
	}

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';
	// =========================================================================
	// >>> ACCIÓN: VALIDAR TRASPASO MULTIEMPRESA (ORIGEN -> DESTINO) <<<
	// =========================================================================
	if ($action == 'validate') {
		if (empty($permissiontoadd)) { 
			accessforbidden('NotEnoughPermissions', 0, 1);
		}

		$error = 0;
		$id_traspaso = (int) $object->id;
		$warehouse_origen = (int) $object->fk_warehouse_origen;
		$entidad_destino  = (int) $object->entidadDestino; // ID de la otra empresa
		$warehouse_destino = (int) $object->fk_warehouse_destino; // Almacén en la otra empresa

		// Guardamos la entidad actual del entorno para poder regresar al final
		$entidad_origen = (int) $conf->entity;

		// 1. Cargar las líneas guardadas en el borrador para validar existencias
		$lineas_validacion = array();
		$sql_check = "SELECT rowid, fk_product, qty, pmp FROM ".MAIN_DB_PREFIX."traspasomultiempresa_traspasoline WHERE fk_traspaso = ".$id_traspaso;
		$res_check = $db->query($sql_check);

		if ($res_check) {
			while ($line_obj = $db->fetch_object($res_check)) {
				$lineas_validacion[] = $line_obj;
			}
		}

		if (empty($lineas_validacion)) {
			$error++;
			setEventMessages("No puedes validar un traspaso sin partidas añadidas.", null, 'errors');
		}

		// 2. Cargar las clases nativas de Dolibarr para el inventario
		require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
		require_once DOL_DOCUMENT_ROOT . '/product/stock/class/mouvementstock.class.php';

		// 3. Bucle de verificación de stock EN EL ORIGEN (Filtro estricto)
		foreach ($lineas_validacion as $linea) {
			$productStatic = new Product($db);
			$productStatic->fetch($linea->fk_product);

			// Obtener el stock físico real exacto en este instante en el almacén origen
			$productStatic->load_stock();
			$stock_disponible = (double) $productStatic->stock_warehouse[$warehouse_origen]->real;

			if ($linea->qty > $stock_disponible) {
				$error++;
				$msg_error = "No se puede validar: El producto <strong>[".$productStatic->ref."] ".$productStatic->label."</strong> ";
				$msg_error.= "no tiene suficiente stock en el almacén origen (Requerido: ".$linea->qty.", Disponible: ".$stock_disponible.").";
				setEventMessages($msg_error, null, 'errors');
			}
		}

		// 4. Procesar la validación y los movimientos cruzados si no hay errores
		if (!$error) {
			$db->begin(); // Transacción SQL de seguridad

			// A) Actualizar el estatus del documento padre a 1 (Validado) y asignar folio definitivo
			$newref = $object->getNextNumRef();
			$sql_update_status = "UPDATE ".MAIN_DB_PREFIX."traspasomultiempresa_traspaso SET status = 1, ref = '".$db->escape($newref)."' WHERE rowid = ".$id_traspaso;
			$res_status = $db->query($sql_update_status);

			if ($res_status) {				
				$object->ref = $newref; // Actualizamos el objeto en memoria para que el resto del bloque use el folio correcto
				// Descripción clara para las tarjetas de stock nativas de Dolibarr
				$desc_movimiento = "Traspaso Multiempresa Ref: ".$object->ref;
				$fk_origin       = (int) $object->id;
				$origintype      = 'traspasomultiempresa';													  

			// B) RECORRER LAS PARTIDAS PARA APLICAR ENTRADAS Y SALIDAS
            foreach ($lineas_validacion as $linea) {
                
                $desc_movimiento = "Traspaso Multiempresa Ref: ".$object->ref;
                $fk_origin       = (int) $object->id;
                $origintype      = 'traspasomultiempresa';

                // === MOVIMIENTO 1: SALIDA DE INVENTARIO (Empresa Origen) ===
                $conf->entity = $entidad_origen; // Aseguramos contexto de origen
                
                $mouvementStockOrigen = new MouvementStock($db);
                
                // ASIGNACIÓN DIRECTA DE TRAZABILIDAD (Evita el desplazamiento de columnas en MySQL)
                $mouvementStockOrigen->fk_origin  = $fk_origin;
                $mouvementStockOrigen->origintype = $origintype;
                
                // Llamada limpia con los 6 parámetros estándar de Dolibarr:
                // livraison($user, $id_producto, $id_almacen, $cantidad, $precio_pmp, $comentario)
                $res_mov_orig = $mouvementStockOrigen->livraison(
                    $user, 
                    (int) $linea->fk_product, 
                    (int) $warehouse_origen, 
                    (double) $linea->qty, 
                    (double) $linea->pmp, 
                    $desc_movimiento
                );
                
                if ($res_mov_orig < 0) {
                    $error++;
                    setEventMessages("Error al descontar stock origen (Prod ID ".$linea->fk_product."): ".$mouvementStockOrigen->error, null, 'errors');
                    break;
                }

                // === MOVIMIENTO 2: ENTRADA DE INVENTARIO (Empresa Destino) ===
                $conf->entity = $entidad_destino; // Cambiamos el contexto a la empresa destino
                
                $mouvementStockDestino = new MouvementStock($db);
                
                // ASIGNACIÓN DIRECTA DE TRAZABILIDAD en el destino
                $mouvementStockDestino->fk_origin  = $fk_origin;
                $mouvementStockDestino->origintype = $origintype;
                
                // Llamada limpia para la recepción:
                // reception($user, $id_producto, $id_almacen, $cantidad, $precio_pmp, $comentario)
                $res_mov_dest = $mouvementStockDestino->reception(
                    $user, 
                    (int) $linea->fk_product, 
                    (int) $warehouse_destino, 
                    (double) $linea->qty, 
                    (double) $linea->pmp, 
                    $desc_movimiento
                );
                
                if ($res_mov_dest < 0) {
                    $error++;
                    setEventMessages("Error al ingresar stock destino (Prod ID ".$linea->fk_product." en Entidad ".$entidad_destino."): ".$mouvementStockDestino->error, null, 'errors');
                    break;
                }
            }

				// REVERSIÓN DE CONTEXTO OBLIGATORIA: Regresamos siempre el ERP a la entidad actual
				$conf->entity = $entidad_origen;

				// C) CIERRE DE TRANSACCIÓN BASE DE DATOS
				if (!$error) {
					$db->commit(); // Todo fue un éxito, guardamos de verdad en ambas tablas
					setEventMessages("El traspaso ha sido validado correctamente. Se descontó del almacén de origen e ingresó al almacén de la empresa destino a costo PMP.", null, 'mesgs');
					
					header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id_traspaso);
					exit;
				} else {
					$db->rollback(); // Si falló la entrada o la salida, deshacemos TODO para evitar descuadres
				}

			} else {
				$db->rollback();
				setEventMessages("Error al actualizar el estado del traspaso: ".$db->lasterror(), null, 'errors');
			}
		}
	}

		// Actions when linking object each other
		include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

		// Actions when printing a doc from card
		include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

		// Action to move up and down lines of object
		//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';

		// Action to build doc
		include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

		if ($action == 'set_thirdparty' && $permissiontoadd) {
			$object->setValueFrom('fk_soc', GETPOSTINT('fk_soc'), '', null, 'date', '', $user, $triggermodname);
		}
		if ($action == 'classin' && $permissiontoadd) {
			$object->setProject(GETPOSTINT('projectid'));
		}

		// Actions to send emails
		$triggersendname = 'TRASPASOMULTIEMPRESA_MYOBJECT_SENTBYMAIL';
		$autocopy = 'MAIN_MAIL_AUTOCOPY_MYOBJECT_TO';
		$trackid = 'traspaso'.$object->id;
		include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
	}

		// ACTION ADDLINE (FAA)
		//if ($action == 'addline' && $user->rights->traspasomultiempresa->crear) { // Ajusta el permiso a tu módulo
		if ($action == 'addline') {
		
		// >>> CORRECCIÓN CRÍTICA: Recuperar los valores enviados por el formulario <<<
		// Dolibarr limpia y extrae los datos usando GETPOST('nombre_input', 'tipo')
		$idprod = GETPOST('idprod', 'int');
		$qty    = GETPOST('qty', 'int');

		// Validación básica antes de continuar
		if (empty($idprod) || $idprod <= 0) {
			$error++;
			setEventMessages("Por favor, selecciona un producto válido de la lista.", null, 'errors');
		}
		if (empty($qty) || $qty <= 0) {
			$error++;
			setEventMessages("Por favor, ingresa una cantidad mayor a cero.", null, 'errors');
		}

		if (!$error) {
			$db->begin();
		
			// 1. Calculamos la referencia y los datos obligatorios
			$partida_ref   = $object->ref . '-' . (count($object->lines) + 1);
			$fecha_actual  = dol_print_date(dol_now(), '%Y-%m-%d %H:%M:%S');
			$usuario_crea  = (int) $user->id;
			$id_traspaso   = (int) $object->id;
			$id_producto   = (int) $idprod;       // <-- Ahora sí valdrá el ID del producto
			$cantidad_prod = (double) $qty;       // <-- Ahora sí valdrá la cantidad escrita
			$estatus_ini   = 0; // Borrador

			// 1.5. Cargar el producto nativo de Dolibarr para extraer su Costo Promedio (PMP)
			require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
			$productStatic = new Product($db);
			$productStatic->fetch($id_producto);

			$pmp_costo = (double) $productStatic->pmp;
			// Si el producto no tiene compras previas, usamos el cost_price como respaldo
			if (empty($pmp_costo) || $pmp_costo <= 0) {
				$pmp_costo = (double) $productStatic->cost_price;
			}
			if (empty($pmp_costo)) {
				$pmp_costo = 0.0;
			}

			// Calcular el Importe total de la partida
			$importe_total = (double) ($cantidad_prod * $pmp_costo);
		
			// 2. Armamos el Query manual directo a tu tabla real
			$sql_insert = "INSERT INTO ".MAIN_DB_PREFIX."traspasomultiempresa_traspasoline ";
			// Asegúrate de que las columnas coincidan al 100% con tu tabla de phpMyAdmin
			$sql_insert.= "(ref, date_creation, fk_user_creat, status, fk_traspaso, fk_product, qty, pmp, amount) ";
			$sql_insert.= "VALUES (";
			$sql_insert.= "'".$db->escape($partida_ref)."', ";
			$sql_insert.= "'".$db->escape($fecha_actual)."', ";
			$sql_insert.= "".$usuario_crea.", ";
			$sql_insert.= "".$estatus_ini.", ";
			$sql_insert.= "".$id_traspaso.", ";
			$sql_insert.= "".$id_producto.", ";
			$sql_insert.= "".$cantidad_prod.", ";
			$sql_insert.= "".$pmp_costo.", ";
			$sql_insert.= "".$importe_total."";
			$sql_insert.= ")";
		
			// 3. Ejecutamos la consulta en la Base de Datos para la línea hija
			$res_query = $db->query($sql_insert);
		
			if ($res_query) {
				
				// 3.5. Actualizar la tabla PADRE
				$sql_update_padre = "UPDATE ".MAIN_DB_PREFIX."traspasomultiempresa_traspaso 
									SET 
										amount = (SELECT COALESCE(SUM(amount), 0) FROM ".MAIN_DB_PREFIX."traspasomultiempresa_traspasoline WHERE fk_traspaso = ".$id_traspaso."),
										qty = (SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."traspasomultiempresa_traspasoline WHERE fk_traspaso = ".$id_traspaso.")
									WHERE rowid = ".$id_traspaso;

				$res_update_padre = $db->query($sql_update_padre);

				if ($res_update_padre) {
					$db->commit();
					
					// Limpiamos los campos del formulario POST
					$_POST['idprod'] = '';
					$_POST['qty'] = '1';
					
					// Redireccionamos limpiamente para recargar la pantalla
					header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
					exit;
				} else {
					$db->rollback();
					print "Error al actualizar totales en tabla Padre: " . $db->lasterror() . "<br>";
					print "Query ejecutado: " . $sql_update_padre;
					exit;
				}

			} else {
				$db->rollback();
				print "Error directo de MySQL (Línea): " . $db->lasterror() . "<br>";
				print "Query ejecutado: " . $sql_insert;
				exit;
			}
		}
	} // FIN ACTION ADDLINE

	    // --- BÚSQUEDA AJAX DE PRODUCTOS (alimenta el Select2, evita cargar todo el catálogo) ---
    if ($action == 'search_products_ajax') {
        top_httphead('application/json');

        $term = trim(GETPOST('term', 'alpha'));
        $idwarehouse = (int) $object->fk_warehouse_origen;

        $results = array();

        if (dol_strlen($term) >= 2) {
            $term_escaped = $db->escape($term);

            // Traemos a la vez el producto y su stock en el almacén ORIGEN del traspaso,
            // así el usuario ve de una vez si tiene existencia antes de seleccionarlo.
            $sql_search  = "SELECT p.rowid, p.ref, p.label, COALESCE(s.reel, 0) as stock";
            $sql_search .= " FROM ".MAIN_DB_PREFIX."product as p";
            $sql_search .= " LEFT JOIN ".MAIN_DB_PREFIX."product_stock as s ON s.fk_product = p.rowid AND s.fk_entrepot = ".$idwarehouse;
            $sql_search .= " WHERE (p.tosell = 1 OR p.tobuy = 1)";
            $sql_search .= " AND p.entity IN (".getEntity('product').")";            
			$sql_search .= " AND (p.ref LIKE '".$term_escaped."%' OR p.label LIKE '%".$term_escaped."%')";
            $sql_search .= " ORDER BY p.ref ASC";
            $sql_search .= $db->plimit(30);

            $res_search = $db->query($sql_search);
            if ($res_search) {
                while ($robj = $db->fetch_object($res_search)) {
                    $results[] = array(
                        'id'    => (int) $robj->rowid,
                        'ref'   => $robj->ref,
                        'label' => $robj->label,
                        'stock' => (float) $robj->stock,
                    );
                }
            }
        }

        echo json_encode(array('results' => $results));
        exit;
    }

    // --- RECEPTOR DE BORRADO ASÍNCRONO ---
    // Nota: usamos $action (ya leído arriba con filtro 'aZ09') en vez de releer con 'aZ',
    // porque el filtro 'aZ' elimina el guion bajo y nunca matchea "deleteline_ajax".
    if ($action == 'deleteline_ajax') {
        // Nota: $dolibarr_nocsrfcheck aquí no tiene efecto, el chequeo CSRF
        // ya se resolvió dentro de main.inc.php (incluido al inicio del archivo).
        // El fix real es enviar el token válido en el POST del AJAX (ver script al final del archivo).

        $lineid = GETPOST('lineid', 'int');
    
        if ($lineid > 0) {
            $db->begin();
            
            $sql_delete = "DELETE FROM ".MAIN_DB_PREFIX."traspasomultiempresa_traspasoline WHERE rowid = ".$lineid;
            $res_delete = $db->query($sql_delete);
    
            if ($res_delete) {
                $db->commit();
                echo "SUCCESS";
                exit;
            } else {
                $db->rollback();
                echo "ERROR_BD: " . $db->lasterror();
                exit;
            }
        }
        echo "ERROR_ID_VACIO";
        exit;
    }

/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("Traspaso")." - ".$langs->trans('Card');
//$title = $object->ref." - ".$langs->trans('Card');
if ($action == 'create') {
	$title = $langs->trans("NewObject", $langs->transnoentitiesnoconv("Traspaso"));
}
$help_url = '';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-traspasomultiempresa page-card');

// Example : Adding jquery code
// print '<script type="text/javascript">
// jQuery(document).ready(function() {
// 	function init_myfunc()
// 	{
// 		jQuery("#myid").removeAttr(\'disabled\');
// 		jQuery("#myid").attr(\'disabled\',\'disabled\');
// 	}
// 	init_myfunc();
// 	jQuery("#mybutton").click(function() {
// 		init_myfunc();
// 	});
// });
// </script>';


// Part to create // NEW FAA
if ($action == 'create') {
	//ini_set('display_errors', 1); error_reporting(E_ALL); //reporte todos los errores
    ini_set('display_errors', 1); error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED); //Omite los errores DEPRECATED
	if (empty($permissiontoadd)) {
		accessforbidden('NotEnoughPermissions', 0, 1);
	}

	print load_fiche_titre($title, '', $object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}
	if ($dol_openinpopup) {
		print '<input type="hidden" name="dol_openinpopup" value="'.$dol_openinpopup.'">';
	}

	print dol_get_fiche_head(array(), '');

	print '<table class="border centpercent tableforfieldcreate">'."\n";

	if (!isset($form) || !is_object($form)) {
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
		$form = new Form($db);
	}

	// 1. Campo de Referencia
	print '<tr class="field_ref"><td class="titlefieldcreate tdtop required">Referencia</td><td>';
	print '<input type="text" name="ref" value="'.(GETPOST('ref', 'alpha') ? GETPOST('ref', 'alpha') : '(PROV)').'" class="maxwidth200" disabled>';
	print ' <span class="opacitymedium">(Generada automáticamente al validar)</span>';
	print '</td></tr>';

	// 2. Campo Almacén Origen (Consulta directa a la base de datos)
	print '<tr class="field_fk_warehouse_origen"><td class="titlefieldcreate tdtop required">Almacén Origen</td><td>';
	$almacenes_origen = array();
	$sql_origen = "SELECT rowid, ref FROM ".MAIN_DB_PREFIX."entrepot WHERE statut=1 AND entity=".((int) $conf->entity);
	$res_origen = $db->query($sql_origen);
	if ($res_origen) {
		while ($obj = $db->fetch_object($res_origen)) {
			$almacenes_origen[$obj->rowid] = $obj->ref;
		}
	}
	print $form->selectarray('fk_warehouse_origen', $almacenes_origen, GETPOST('fk_warehouse_origen', 'int'), 1);
	print '</td></tr>';

	// 3. Campo Entidad Destino (Consulta directa y filtrada a llx_entity - Excluyendo la entidad actual)
	print '<tr class="field_entidadDestino"><td class="titlefieldcreate tdtop required">Entidad Destino</td><td>';
	$entidades_destino = array();
	$sql_entidades = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."entity WHERE active = 1 AND visible = 1 AND rowid > 0";
	$res_entidades = $db->query($sql_entidades);
	if ($res_entidades) {
		while ($obj = $db->fetch_object($res_entidades)) {
			
			// >>> FILTRO DE SEGURIDAD INDUSTRIAL <<<
			// Si el ID de la entidad coincide con la entidad actual activa, la ignoramos de la lista
			if ((int)$obj->rowid == (int)$conf->entity) {
				continue;
			}
			
			$entidades_destino[$obj->rowid] = $obj->label;
		}
	}
	print $form->selectarray('entidadDestino', $entidades_destino, GETPOST('entidadDestino', 'int'), 1);
	print '</td></tr>';

	// 4. Campo Almacén Destino (Estructura base)
	print '<tr class="field_fk_warehouse_destino"><td class="titlefieldcreate tdtop required">Almacén Destino</td><td>';
	print '<select id="fk_warehouse_destino" name="fk_warehouse_destino" class="flat minwidth200">';
	print '<option value="">-- Seleccione una Entidad Destino --</option>';
	print '</select>';
	print '</td></tr>';

	// 5. Campo Descripción
	print '<tr class="field_description"><td class="titlefieldcreate tdtop">Descripción</td><td>';
	print '<textarea name="description" class="centpercent" rows="3">'.dol_escape_htmltag(GETPOST('description', 'alpha')).'</textarea>';
	print '</td></tr>';

	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	// --- PASO CLAVE: GENERAR EL MAPA DE ALMACENES GLOBAL EN PHP PARA JAVASCRIPT ---
	$mapa_almacenes = array();
	$sql_mapa = "SELECT rowid, ref, entity FROM ".MAIN_DB_PREFIX."entrepot WHERE statut=1";
	$res_mapa = $db->query($sql_mapa);
	if ($res_mapa) {
		while ($obj = $db->fetch_object($res_mapa)) {
			$mapa_almacenes[] = array(
				'id' => $obj->rowid,
				'ref' => $obj->ref,
				'entity' => $obj->entity
			);
		}
	}
	
	// Inyectamos el mapa en formato JSON dentro del script
	print '<script type="text/javascript">
	jQuery(document).ready(function() {
		var todosLosAlmacenes = ' . json_encode($mapa_almacenes) . ';

		function filtrarAlmacenes() {
			var entidadSeleccionada = jQuery("select[name=\'entidadDestino\']").val();
			var selectorDestino = jQuery("#fk_warehouse_destino");
			
			// Limpiar selector
			selectorDestino.empty();
			
			if (!entidadSeleccionada || entidadSeleccionada == "") {
				selectorDestino.append("<option value=\'\'>-- Seleccione una Entidad Destino --</option>");
				return;
			}
			
			var filtrados = todosLosAlmacenes.filter(function(item) {
				return item.entity == entidadSeleccionada;
			});
			
			if (filtrados.length > 0) {
				selectorDestino.append("<option value=\'\'>-- Seleccione Almacén Destino --</option>");
				jQuery.each(filtrados, function(index, item) {
					selectorDestino.append("<option value=\'" + item.id + "\'>" + item.ref + "</option>");
				});
			} else {
				selectorDestino.append("<option value=\'\'>Sin almacenes en esta entidad</option>");
			}
		}

		// Escuchar cambios en el selector de tiendas
		jQuery("select[name=\'entidadDestino\']").change(function() {
			filtrarAlmacenes();
		});

		// Ejecución inicial al cargar
		filtrarAlmacenes();
	});
	</script>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("Create");

	print '</form>';

	//dol_set_focus('input[name="ref"]');
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("Traspaso"), '', $object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$head = traspasoPrepareHead($object);

	print dol_get_fiche_head($head, 'card', $langs->trans("Traspaso"), -1, $object->picto, 0, '', '', 0, '', 1);

	$formconfirm = '';

	// Confirmation to delete (using preloaded confirm popup)
	if ($action == 'delete' || ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile))) {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteTraspaso'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 'action-delete');
	}
	// Confirmation to delete line
	if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}

	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Confirmation of action xxxx (You can use it for xxx = 'close', xxx = 'reopen', ...)
	// if ($action == 'xxx') {
	// 	$text = $langs->trans('ConfirmActionXxx', $object->ref);
	// 	if (isModEnabled('notification')) {
	// 		require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
	// 		$notify = new Notify($db);
	// 		$text .= '<br>';
	// 		$text .= $notify->confirmMessage('MYOBJECT_CLOSE', $object->socid, $object);
	// 	}

	// 	$formquestion = array();

	// 	$forcecombo=0;
	// 	if ($conf->browser->name == 'ie') $forcecombo = 1;	// There is a bug in IE10 that make combo inside popup crazy
	// 	$formquestion = array(
	// 		// 'text' => $langs->trans("ConfirmClone"),
	// 		// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
	// 		// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
	// 		// array('type' => 'other',    'name' => 'idwarehouse',   'label' => $langs->trans("SelectWarehouseForStockDecrease"), 'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse')?GETPOST('idwarehouse'):'ifone', 'idwarehouse', '', 1, 0, 0, '', 0, $forcecombo))
	// 	);
	// 	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('XXX'), $text, 'confirm_xxx', $formquestion, 0, 1, 220);
	// }

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;


	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/traspasomultiempresa/traspaso_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	/*
		// Ref customer
		$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, $usercancreate, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, $usercancreate, 'string'.(getDolGlobalInt('THIRDPARTY_REF_INPUT_SIZE') ? ':'.getDolGlobalInt('THIRDPARTY_REF_INPUT_SIZE') : ''), '', null, null, '', 1);
		// Thirdparty
		$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1, 'customer');
		if (!getDolGlobalInt('MAIN_DISABLE_OTHER_LINK') && $object->thirdparty->id > 0) {
			$morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/commande/list.php?socid='.$object->thirdparty->id.'&search_societe='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherOrders").'</a>)';
		}
		// Project
		if (isModEnabled('project')) {
			$langs->load("projects");
			$morehtmlref .= '<br>';
			if ($permissiontoadd) {
				$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
				if ($action != 'classify') {
					$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
				}
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
			} else {
				if (!empty($object->fk_project)) {
					$proj = new Project($db);
					$proj->fetch($object->fk_project);
					$morehtmlref .= $proj->getNomUrl(1);
					if ($proj->title) {
						$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
					}
				}
			}
		}
	*/
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	//$keyforbreak='fieldkeytoswitchonsecondcolumn';	// We change column just before this field
	//unset($object->fields['fk_project']);				// Hide field already shown in banner
	//unset($object->fields['fk_soc']);					// Hide field already shown in banner
	//include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';
	// --- INICIO DE CAMPOS MANUALES EN MODO LECTURA ---
    require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
    
    // 1. Mostrar Almacén Origen con su nombre real
    print '<tr><td class="titlefield">Almacén Origen</td><td>';
    $wh_orig = new Entrepot($db);
    if (!empty($object->fk_warehouse_origen) && $wh_orig->fetch($object->fk_warehouse_origen) > 0) {
        print $wh_orig->getNomUrl(1);
    } else {
        print $object->fk_warehouse_origen;
    }
    print '</td></tr>';
    
    // 2. Mostrar la Entidad Destino buscando su nombre en llx_entity
    print '<tr><td>Entidad Destino</td><td>';
    if (!empty($object->entidadDestino)) {
        $sql_ent = "SELECT label FROM ".MAIN_DB_PREFIX."entity WHERE rowid = ".((int) $object->entidadDestino);
        $res_ent = $db->query($sql_ent);
        if ($res_ent && ($obj_ent = $db->fetch_object($res_ent))) {
            print dol_escape_htmltag($obj_ent->label);
        } else {
            print $object->entidadDestino;
        }
    }
    print '</td></tr>';
    
    // 3. Mostrar Almacén Destino con su nombre real
    print '<tr><td>Almacén Destino</td><td>';
    $wh_dest = new Entrepot($db);
    if (!empty($object->fk_warehouse_destino) && $wh_dest->fetch($object->fk_warehouse_destino) > 0) {
        print $wh_dest->getNomUrl(1);
    } else {
        print $object->fk_warehouse_destino;
    }
    print '</td></tr>';
    // --- FIN DE CAMPOS MANUALES ---
    
    $object->table_element_line = 'traspasomultiempresa_traspasoline';
    $object->fk_element = 'fk_traspaso'; // ID técnico del campo que une al padre con el hijo
    if (!isset($object->lines) || !is_array($object->lines)) {
        $object->lines = array(); // Inicializamos el array de partidas
    }
    
	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();


	/*
	 * Lines
	 */

    // --- FORZADO MANUAL DE PARTIDAS CON SELECTOR DIRECTO SQL (INFALIBLE) ---
    print '<br><br>';
    print '<!-- Inicio Bloque Partidas -->';
    print '<div class="div-table-responsive-no-min">';
        
	if ($object->status == $object::STATUS_DRAFT) {
		print '<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="POST">';
		print '<input type="hidden" name="token" value="' . newToken().'">';
		print '<input type="hidden" name="action" value="addline">';
		print '<input type="hidden" name="id" value="' . $object->id.'">';
	}	

        print '<table class="noborder noshadow centpercent">';
    
    // 1. Cabeceras principales de la tabla
    print '<tr class="liste_titre">';
    print '<td>Producto a Traspasar</td>';
    print '<td class="right" width="120">Cantidad</td>';
    print '<td class="right" width="140">Costo Promedio (PMP)</td>';
    print '<td class="right" width="140">Importe</td>';
    print '<td class="center" width="120">Acción</td>';
    print '</tr>';

    // 2. FILA DE CAPTURA EN BLANCO (Colocada arriba para mejor UX)    
	if ($object->status == $object::STATUS_DRAFT) {
        print '<tr class="nodrag nodrop" style="background: #fdfdfd; border-bottom: 2px solid #ddd;">';
        
        // Selector de productos con buscador AJAX
        print '<td>';
        if (!is_object($form)) {
            require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
            $form = new Form($db);
        }
        print '<select id="idprod" name="idprod" class="minwidth300" style="width:300px"><option value=""></option></select>';
        print '</td>';
        
        // Input de cantidad
        print '<td class="right">';
        print '<input type="number" size="4" name="qty" id="qty" value="1" class="right maxwidth75" min="1">';
        print '</td>';
        
        // Columnas automáticas de costo e importe
        print '<td class="right" style="color: #999; font-style: italic; font-size: 0.9em;">(Automático)</td>';
        print '<td class="right" style="color: #999; font-style: italic; font-size: 0.9em;">(Automático)</td>';
        
        // Botón Añadir
        print '<td class="center">';
        print '<input type="submit" class="button" value="Añadir Partida" id="btn_add_line">';
        print '</td>';
        
        print '</tr>';
    }

    // 3. Renderizar las líneas existentes que ya están en la Base de Datos
    $lineas_guardadas = array();
    $sql_lines = "SELECT rowid, ref, fk_product, qty, pmp, amount FROM ".MAIN_DB_PREFIX."traspasomultiempresa_traspasoline WHERE fk_traspaso = ".((int) $object->id);
    $res_lines = $db->query($sql_lines);
    
    if ($res_lines) {
        while ($linea_obj = $db->fetch_object($res_lines)) {
            // Evitamos pintar en la tabla registros basura que tengan producto 0 por errores previos
            if ((int)$linea_obj->fk_product > 0) {
                $lineas_guardadas[] = $linea_obj;
            }
        }
    }
    
    foreach ($lineas_guardadas as $linea_guardada) {
        print '<tr class="oddeven">';
        
        $prod_ref = $linea_guardada->fk_product;
        $sql_pname = "SELECT ref, label FROM ".MAIN_DB_PREFIX."product WHERE rowid = ".((int) $linea_guardada->fk_product);
        $res_pname = $db->query($sql_pname);
        if ($res_pname && ($obj_p = $db->fetch_object($res_pname))) {
            $prod_ref = '['.$obj_p->ref.'] '.$obj_p->label;
        }
        
        print '<td>' . $prod_ref . '</td>'; 
        print '<td class="right">' . $linea_guardada->qty . '</td>';
        print '<td class="right">' . price($linea_guardada->pmp, 0, '', 1, -1, -1, $conf->currency) . '</td>';
        print '<td class="right">' . price($linea_guardada->amount, 0, '', 1, -1, -1, $conf->currency) . '</td>';
        
        // Botón Eliminar
		print '<td class="center">';
		if ($object->status == $object::STATUS_DRAFT) {
    		print '<button type="button" class="button button-delete-line" data-id="'.$linea_guardada->rowid.'" style="padding: 2px 5px; background: none; border: none; color: #a40000; cursor: pointer;">';
    		print img_delete().' Eliminar';
    		print '</button>';
		}
		print '</td>';
    }

    // 4. FILA ABSOLUTA DE TOTALES (Siempre al final)
    print '<tr class="liste_total" style="border-top: 2px solid #ccc;">';
    print '<td colspan="3" class="right"><strong>Total a Costo:</strong></td>';
    print '<td class="right"><strong>' . price($object->amount, 0, '', 1, -1, -1, $conf->currency) . '</strong></td>';
    print '<td></td>'; 
    print '</tr>';

    print '</table>'; // Cierre de la tabla

    print '</form>';
    print '<script type="text/javascript">
jQuery(document).ready(function() {
    jQuery("#idprod").select2({
        width: "300px",
        minimumInputLength: 2,
        placeholder: "Escribe referencia o nombre del producto...",
        language: {
            inputTooShort: function() { return "Escribe al menos 2 caracteres"; },
            searching: function() { return "Buscando..."; },
            noResults: function() { return "Sin resultados"; }
        },
        ajax: {
            url: "'.$_SERVER["PHP_SELF"].'?id='.$object->id.'",
            type: "POST",
            dataType: "json",
            delay: 300,
            data: function(params) {
                return {
                    action: "search_products_ajax",
                    term: params.term,
                    token: "'.newToken().'"
                };
            },
            processResults: function(data) {
                return { results: data.results };
            },
            cache: true
        },
        templateResult: function(item) {
            if (!item.id) { return item.text; }
            var $wrap = jQuery("<div></div>");
            jQuery("<span></span>").text("[" + item.ref + "] " + item.label).appendTo($wrap);
            var stockColor = (item.stock > 0) ? "#168821" : "#a40000";
            jQuery("<span></span>")
                .text(" — Stock: " + item.stock)
                .css({color: stockColor, fontWeight: "bold", marginLeft: "6px"})
                .appendTo($wrap);
            return $wrap;
        },
        templateSelection: function(item) {
            if (!item.id) { return item.text; }
            return "[" + item.ref + "] " + item.label + " (Stock: " + item.stock + ")";
        }
    });
});
</script>';

    /* 
    if ($object->status == 0) {
        print '</form>';
    } */
    
    print '</div>';
    print '<!-- Fin Bloque Partidas -->';

    /*
	if (true) {
		// Show object lines
		require_once DOL_DOCUMENT_ROOT.'/custom/traspasomultiempresa/class/traspasoline.class.php';
		$result = $object->getLinesArray();

		print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '' : '#line_'.GETPOSTINT('lineid')).'" method="POST">
		<input type="hidden" name="token" value="' . newToken().'">
		<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="page_y" value="">
		<input type="hidden" name="id" value="' . $object->id.'">
		';

		if (!empty($conf->use_javascript_ajax) && $object->status == 0) {
			include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
		}

		print '<div class="div-table-responsive-no-min">';
		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '<table id="tablelines" class="noborder noshadow" width="100%">';
		}

		if (!empty($object->lines)) {
			$object->printObjectLines($action, $mysoc, null, GETPOSTINT('lineid'), 1);
		}

		// Form to add new line
		if ($object->status == 0 && $permissiontoadd && $action != 'selectlines') {
			if ($action != 'editline') {
				// Add products/services form

				$parameters = array();
				$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
				if ($reshook < 0) {
					setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
				}
				if (empty($reshook)) {
					$object->formAddObjectLine(1, $mysoc, $soc);
				}
			}
		}

		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '</table>';
		}
		print '</div>';

		print "</form>\n";
	} */
    

	// Buttons for actions
	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			// Send
			if (empty($user->socid)) {
				print dolGetButtonAction('', $langs->trans('SendMail'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&token='.newToken().'&mode=init#formmailbeforetitle');
			}			

			// Back to draft
			if ($object->status == $object::STATUS_VALIDATED) {
				// Deshabilitado a propósito: una vez validado, no se permite regresar a borrador.
				// print dolGetButtonAction('', $langs->trans('SetToDraft'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_setdraft&confirm=yes&token='.newToken(), '', $permissiontoadd);
			}

			// Modify
			if ($object->status == $object::STATUS_DRAFT) {
				print dolGetButtonAction('', $langs->trans('Modify'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken(), '', $permissiontoadd);
			}						

			// Validate Ori
			/*
			if ($object->status == $object::STATUS_DRAFT) {
				if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
					print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate&confirm=yes&token='.newToken(), '', $permissiontoadd);
				} else {
					$langs->load("errors");
					print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
				}
			} */

			// Validate: Validamos si tu documento padre indica que tiene renglones registrados (qty > 0 en la tabla padre) FAA
			if ($object->status == $object::STATUS_DRAFT) {
				if ((int)$object->qty > 0) { // <-- Evaluamos tu contador real de partidas
					print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=validate&token='.newToken(), '', $permissiontoadd);
				} else {
					$langs->load("errors");
					print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
				}
			}

			// Clone
			if ($permissiontoadd) {
				print dolGetButtonAction('', $langs->trans('ToClone'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.(!empty($object->socid) ? '&socid='.$object->socid : '').'&action=clone&token='.newToken(), '', $permissiontoadd);
			}

			/*
			// Disable / Enable
			if ($permissiontoadd) {
				if ($object->status == $object::STATUS_ENABLED) {
					print dolGetButtonAction('', $langs->trans('Disable'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=disable&token='.newToken(), '', $permissiontoadd);
				} else {
					print dolGetButtonAction('', $langs->trans('Enable'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=enable&token='.newToken(), '', $permissiontoadd);
				}
			}
			if ($permissiontoadd) {
				if ($object->status == $object::STATUS_VALIDATED) {
					print dolGetButtonAction('', $langs->trans('Cancel'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=close&token='.newToken(), '', $permissiontoadd);
				} else {
					print dolGetButtonAction('', $langs->trans('Re-Open'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=reopen&token='.newToken(), '', $permissiontoadd);
				}
			}
			*/

			// Delete (with preloaded confirm popup)
			$deleteUrl = $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken();
			$buttonId = 'action-delete-no-ajax';
			if ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile)) {	// We can use preloaded confirm if not jmobile
				$deleteUrl = '';
				$buttonId = 'action-delete';
			}			
			if ($object->status == $object::STATUS_DRAFT) {
				$params = array();
				print dolGetButtonAction('', $langs->trans("Delete"), 'delete', $deleteUrl, $buttonId, $permissiontodelete, $params);
			}		
		}
		print '</div>'."\n";
	}


	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	if ($action != 'presend') {
		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre

		$includedocgeneration = 1;

		// Documents
		if ($includedocgeneration) {
			$objref = dol_sanitizeFileName($object->ref);
			$relativepath = $objref.'/'.$objref.'.pdf';
			$filedir = $conf->traspasomultiempresa->dir_output.'/'.$object->element.'/'.$objref;
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
			$genallowed = $permissiontoread; // If you can read, you can build the PDF to read content
			$delallowed = $permissiontoadd; // If you can create/edit, you can remove a file on card
			print $formfile->showdocuments('traspasomultiempresa:Traspaso', $object->element.'/'.$objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
		}

		// Show links to link elements
		$tmparray = $form->showLinkToObjectBlock($object, array(), array('traspaso'), 1);
		if (is_array($tmparray)) {
			$linktoelem = $tmparray['linktoelem'];
			$htmltoenteralink = $tmparray['htmltoenteralink'];
			print $htmltoenteralink;
			$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);
		} else {
			// backward compatibility
			$somethingshown = $form->showLinkedObjectBlock($object, $tmparray);
		}

		print '</div><div class="fichehalfright">';

		$MAXEVENT = 10;

		$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/traspasomultiempresa/traspaso_agenda.php', 1).'?id='.$object->id);

		$includeeventlist = 0;

		// List of actions on element
		if ($includeeventlist) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
			$formactions = new FormActions($db);
			$somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);
		}

		print '</div></div>';
	}

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Presend form
	$modelmail = 'traspaso';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->traspasomultiempresa->dir_output;
	$trackid = 'traspaso'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}
print '<script type="text/javascript">
jQuery(document).ready(function() {
    jQuery(".button-delete-line").click(function(e) {
        e.preventDefault();
        var btn = jQuery(this);
        var lineid = btn.data("id");
        
        if (confirm("¿Estás seguro de eliminar esta línea?")) {
            jQuery.ajax({
                url: "'.$_SERVER["PHP_SELF"].'?id='.$object->id.'",
                type: "POST",
                data: {
                    action: "deleteline_ajax",
                    lineid: lineid,
                    token: "'.newToken().'"
                },
                success: function(response) {
                    if (response.trim() === "SUCCESS") {
                        btn.closest("tr").fadeOut("normal", function() {
                            jQuery(this).remove();
                        });
                    } else {
                        alert("El servidor no pudo borrar el registro: " + response);
                    }
                },
                error: function(xhr, status, error) {
                    alert("Error del servidor: " + xhr.status + " - " + error);
                }
            });
        }
    });
});
</script>';
// End of page
llxFooter();
$db->close();
