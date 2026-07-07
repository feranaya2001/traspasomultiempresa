<?php
/* Copyright (C) 2005-2010	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2026		Fernando Anaya Alba			<consultor.sistemas@ajigsa.com>
 * Ver 1.0.1
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
 *  \file       htdocs/core/modules/traspasomultiempresa/mod_traspaso_standard.php
 *  \ingroup    traspasomultiempresa
 *  \brief      File of class to manage Traspaso numbering rules standard
 */
dol_include_once('/traspasomultiempresa/core/modules/traspasomultiempresa/modules_traspaso.php');


/**
 *	Class to manage the Standard numbering rule for Traspaso
 */
class mod_traspaso_standard extends ModeleNumRefTraspaso
{
	/**
	 * Dolibarr version of the loaded document
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'''|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	/**
	 * @var string
	 */
	public $prefix = 'TRMU';

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string name
	 */
	public $name = 'standard';


	/**
	 *  Return description of numbering module
	 *
	 *  @param      Translate   $langs Translate Object
	 *  @return     string             Text with description
	 */
	public function info($langs)
	{
		return $langs->trans("SimpleNumRefModelDesc", $this->prefix);
	}


	/**
	 *  Return an example of numbering
	 *
	 *  @return     string      Example
	 */
	public function getExample()
	{
		return $this->prefix."2607-000001";
	}

	/**
	 *  Checks if the numbers already in the database do not
	 *  cause conflicts that would prevent this numbering working.
	 *
	 *  @param  CommonObject	$object		Object we need next value for
	 *  @return bool						false if conflict, true if ok
	 */
	public function canBeActivated($object)
	{
		global $conf, $langs, $db;

		$coyymm = '';
		$max = '';

		$posindice = strlen($this->prefix) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql .= " FROM ".$db->prefix()."traspasomultiempresa_traspaso";
		$sql .= " WHERE ref LIKE '".$db->escape($this->prefix)."____-%'";
		if ($object->ismultientitymanaged == 1) {
			$sql .= " AND entity = ".$conf->entity;
		} elseif (!is_numeric($object->ismultientitymanaged)) { // @phan-suppress-current-line PhanPluginEmptyStatementIf
			// TODO
		}

		$resql = $db->query($sql);
		if ($resql) {
			$row = $db->fetch_row($resql);
			if ($row) {
				$coyymm = substr($row[0], 0, 6);
				$max = $row[0];
			}
		}
		if ($coyymm && !preg_match('/'.$this->prefix.'[0-9][0-9][0-9][0-9]/i', $coyymm)) {
			$langs->load("errors");
			$this->error = $langs->trans('ErrorNumRefModel', $max);
			return false;
		}

		return true;
	}

	/**
	 * 	Return next free value
	 *
	 *  @param  Traspaso		$object		Object we need next value for
	 *  @return string|int<-1,0>			Next value if OK, <=0 if KO
	 */
	public function getNextValue($object)
	{
		global $db, $conf;

		// first we get the max value
		$posindice = strlen($this->prefix) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql .= " FROM ".$db->prefix()."traspasomultiempresa_traspaso";
		$sql .= " WHERE ref LIKE '".$db->escape($this->prefix)."____-%'";
		if ($object->ismultientitymanaged == 1) {
			$sql .= " AND entity = ".$conf->entity;
		} elseif (!is_numeric($object->ismultientitymanaged)) {
			// TODO
		}

		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$max = intval($obj->max);
			} else {
				$max = 0;
			}
		} else {
			dol_syslog("mod_traspaso_standard::getNextValue", LOG_DEBUG);
			return -1;
		}

		//$date=time();
		$date = $object->date_creation;
		$yymm = dol_print_date($date, "%y%m");

		if ($max >= (pow(10, 6) - 1)) {
 		   $num = $max + 1; // If counter > 999999, we do not format on 6 chars, we take number as it is
		} else {
    		$num = sprintf("%06u", $max + 1);
		}

		dol_syslog("mod_traspaso_standard::getNextValue return ".$this->prefix.$yymm."-".$num);
		return $this->prefix.$yymm."-".$num;
	}
}
