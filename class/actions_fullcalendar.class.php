<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    class/actions_fullcalendar.class.php
 * \ingroup fullcalendar
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class Actionsfullcalendar
 */
class Actionsfullcalendar
{
	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
	}

	
	function addCalendarChoice($parameters,&$object,&$action, $hookmanager)
	{
		global $conf;
		if (in_array('agenda', explode(':', $parameters['context'])))
		{
			//if(!empty($conf->global->MAIN_NOT_INC_FULLCALENDAR_HEAD))
			//{
				echo '<script type="text/javascript" src="'.dol_buildpath('/fullcalendar/js/fullcalendar.js.php?force_use_js=1', 1).'"></script>';
			//}
			return 1;
		}
		return 0;
	}
}
