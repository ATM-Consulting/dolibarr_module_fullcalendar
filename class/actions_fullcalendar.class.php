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


	function printFieldListWhere($parameters, &$object, &$action, $hookmanager)
	{
		global $conf;

		$TContexts = explode(':', $parameters['context']);

		if(in_array('agendalist', $TContexts) && ! empty($conf->global->FULLCALENDAR_ENABLE_EVENT_LIST_MULTIDATE_FILTER))
		{
			global $db;

			$datestart1=dol_mktime(0, 0, 0, GETPOST('datestart1month','int'), GETPOST('datestart1day','int'), GETPOST('datestart1year','int'));
			$datestart2=dol_mktime(0, 0, 0, GETPOST('datestart2month','int'), GETPOST('datestart2day','int'), GETPOST('datestart2year','int'));
			$dateend1=dol_mktime(0, 0, 0, GETPOST('dateend1month','int'), GETPOST('dateend1day','int'), GETPOST('dateend1year','int'));
			$dateend2=dol_mktime(0, 0, 0, GETPOST('dateend2month','int'), GETPOST('dateend2day','int'), GETPOST('dateend2year','int'));

			$moreSQL = '';

			if($datestart1 > 0)
			{
				$moreSQL.= ' AND a.datep >= "' . $db->idate(intval($datestart1)) . '"';
			}

			if($datestart2 > 0)
			{
				$moreSQL.= ' AND a.datep < "' . $db->idate(intval($datestart2 + 24 * 3600)) . '"';
			}

			if($dateend1 > 0)
			{
				$moreSQL.= ' AND a.datep2 >= "' . $db->idate(intval($dateend1)) . '"';
			}

			if($dateend2 > 0)
			{
				$moreSQL.= ' AND a.datep2 < "' . $db->idate(intval($dateend2 + 24 * 3600)) . '"';
			}

			$this->resprints = $moreSQL;
		}

		return 0;
	}


	function printFieldListOption($parameters, &$object, &$action, $hookmanager)
	{
		global $conf;

		$TContexts = explode(':', $parameters['context']);

		if(in_array('agendalist', $TContexts) && ! empty($conf->global->FULLCALENDAR_ENABLE_EVENT_LIST_MULTIDATE_FILTER))
		{
			global $form;

			$datestart1=dol_mktime(0, 0, 0, GETPOST('datestart1month','int'), GETPOST('datestart1day','int'), GETPOST('datestart1year','int'));
			$datestart2=dol_mktime(0, 0, 0, GETPOST('datestart2month','int'), GETPOST('datestart2day','int'), GETPOST('datestart2year','int'));
			$dateend1=dol_mktime(0, 0, 0, GETPOST('dateend1month','int'), GETPOST('dateend1day','int'), GETPOST('dateend1year','int'));
			$dateend2=dol_mktime(0, 0, 0, GETPOST('dateend2month','int'), GETPOST('dateend2day','int'), GETPOST('dateend2year','int'));

			$dateStart1Input = $form->select_date($datestart1, 'datestart1', 0, 0, 1, '', 1, 0, 1);
			$dateStart2Input = $form->select_date($datestart2, 'datestart2', 0, 0, 1, '', 1, 0, 1);
			$dateEnd1Input = $form->select_date($dateend1, 'dateend1', 0, 0, 1, '', 1, 0, 1);
			$dateEnd2Input = $form->select_date($dateend2, 'dateend2', 0, 0, 1, '', 1, 0, 1);

?>
			<script type="text/javascript">
			$(document).ready(function() {
				$('#datestart').parent().html("<?php echo str_replace(array('\n', '<script', '</script'), array('', '<scr"+"ipt', '</scr"+"ipt'), dol_escape_js($dateStart1Input . '<br />' . $dateStart2Input, 2)); ?>");
				$('#dateend').parent().html("<?php echo str_replace(array('\n', '<script', '</script'), array('', '<scr"+"ipt', '</scr"+"ipt'), dol_escape_js($dateEnd1Input . '<br />' . $dateEnd2Input, 2)); ?>");
			});
			</script>
<?php

			return 0;
		}
	}
}
