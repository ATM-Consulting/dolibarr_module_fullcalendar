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
 * 	\file		admin/fullcalendar.php
 * 	\ingroup	fullcalendar
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */
// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
    $res = @include("../../../main.inc.php"); // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/fullcalendar.lib.php';

// Translations
$langs->load("fullcalendar@fullcalendar");

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');

/*
 * Actions
 */
if (preg_match('/set_(.*)/',$action,$reg))
{
	$code=$reg[1];

	$value = GETPOST($code);

	if(preg_match('/^FULLCALENDAR_PREFILL_DATETIME_/', $code))
	{
		$value = dol_mktime(GETPOST($code . 'hour'), GETPOST($code . 'min'), 0, 1, 1, 1970);
	}

	if (dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}
	
if (preg_match('/del_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * View
 */
$page_name = "fullcalendarSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = fullcalendarAdminPrepareHead();
dol_fiche_head(
    $head,
    'settings',
    $langs->trans("Module104851Name"),
    0,
    "fullcalendar@fullcalendar"
);

// Setup page goes here
$form=new Form($db);
$var=false;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";


// Example with a yes / no select
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FULLCALENDAR_AUTO_FILL_TITLE").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_FULLCALENDAR_AUTO_FILL_TITLE">';
echo ajax_constantonoff('FULLCALENDAR_AUTO_FILL_TITLE');
print '</form>';
print '</td></tr>';

// Example with a yes / no select
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FULLCALENDAR_SHOW_AFFECTED_USER").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_FULLCALENDAR_SHOW_AFFECTED_USER">';
echo ajax_constantonoff('FULLCALENDAR_SHOW_AFFECTED_USER');

print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("DONT_SHOW_AUTO_EVENT").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_DONT_SHOW_AUTO_EVENT">';
echo ajax_constantonoff('DONT_SHOW_AUTO_EVENT');

print '</form>';
print '</td></tr>';

$var=!$var;

print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FULLCALENDAR_SHOW_PROJECT").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_FULLCALENDAR_SHOW_PROJECT">';
echo ajax_constantonoff('FULLCALENDAR_SHOW_PROJECT');

print '</form>';
print '</td></tr>';


$var=!$var;

print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FULLCALENDAR_SHOW_ORDER").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_FULLCALENDAR_SHOW_ORDER">';
echo ajax_constantonoff('FULLCALENDAR_SHOW_ORDER');

print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FULLCALENDAR_HIDE_DAYS").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_FULLCALENDAR_HIDE_DAYS">';
print '<input type="text" name="FULLCALENDAR_HIDE_DAYS" value="'.$conf->global->FULLCALENDAR_HIDE_DAYS.'" />';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FULLCALENDAR_SHOW_THIS_HOURS").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_FULLCALENDAR_SHOW_THIS_HOURS">';
print '<input type="text" name="FULLCALENDAR_SHOW_THIS_HOURS" value="'.$conf->global->FULLCALENDAR_SHOW_THIS_HOURS.'" />';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';


$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FULLCALENDAR_DURATION_SLOT").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_FULLCALENDAR_DURATION_SLOT">';

$TOption=array(
	'00:30:00'=>'30 '.$langs->trans('minutes')
	,'00:15:00'=>'15 '.$langs->trans('minutes')
	,'00:05:00'=>'5 '.$langs->trans('minutes')
	,'01:00:00'=>'1 '.$langs->trans('hour')
);

echo $form->selectarray('FULLCALENDAR_DURATION_SLOT', $TOption, $conf->global->FULLCALENDAR_DURATION_SLOT);

print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';

print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FULLCALENDAR_USE_ASSIGNED_COLOR").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_FULLCALENDAR_USE_ASSIGNED_COLOR">';
echo ajax_constantonoff('FULLCALENDAR_USE_ASSIGNED_COLOR');

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FULLCALENDAR_LIGTHNESS_SWAP").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_FULLCALENDAR_LIGTHNESS_SWAP">';
print '<input type="text" name="FULLCALENDAR_LIGTHNESS_SWAP" value="'.( empty($conf->global->FULLCALENDAR_LIGTHNESS_SWAP) ? 150 : $conf->global->FULLCALENDAR_LIGTHNESS_SWAP).'" />';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FULLCALENDAR_SHOW_ALL_ASSIGNED_COLOR").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_FULLCALENDAR_SHOW_ALL_ASSIGNED_COLOR">';
echo ajax_constantonoff('FULLCALENDAR_SHOW_ALL_ASSIGNED_COLOR');

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FULLCALENDAR_USE_HUGE_WHITE_BORDER").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_FULLCALENDAR_USE_HUGE_WHITE_BORDER">';
echo ajax_constantonoff('FULLCALENDAR_USE_HUGE_WHITE_BORDER');

print '</form>';
print '</td></tr>';



$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FULLCALENDAR_FILTER_ON_STATE").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
echo ajax_constantonoff('FULLCALENDAR_FILTER_ON_STATE');
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FULLCALENDAR_CAN_UPDATE_PERCENT").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
echo ajax_constantonoff('FULLCALENDAR_CAN_UPDATE_PERCENT');
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FULLCALENDAR_ENABLE_EVENT_LIST_MULTIDATE_FILTER").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
echo ajax_constantonoff('FULLCALENDAR_ENABLE_EVENT_LIST_MULTIDATE_FILTER');
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FULLCALENDAR_SHOW_EVENT_DESCRIPTION").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
echo ajax_constantonoff('FULLCALENDAR_SHOW_EVENT_DESCRIPTION');
print '</td></tr>';

$TElementsToDisable = array(
	  '#FULLCALENDAR_PREFILL_DATETIME_MORNING_STARThour'
	, '#FULLCALENDAR_PREFILL_DATETIME_MORNING_STARTmin'
	, '#FULLCALENDAR_PREFILL_DATETIME_MORNING_ENDhour'
	, '#FULLCALENDAR_PREFILL_DATETIME_MORNING_ENDmin'
	, '#FULLCALENDAR_PREFILL_DATETIME_AFTERNOON_STARThour'
	, '#FULLCALENDAR_PREFILL_DATETIME_AFTERNOON_STARTmin'
	, '#FULLCALENDAR_PREFILL_DATETIME_AFTERNOON_ENDhour'
	, '#FULLCALENDAR_PREFILL_DATETIME_AFTERNOON_ENDmin'
);

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FULLCALENDAR_PREFILL_DATETIMES").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print ajax_constantonoff('FULLCALENDAR_PREFILL_DATETIMES', array('disabled' => $TElementsToDisable));
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$langs->trans("FULLCALENDAR_PREFILL_DATETIME_MORNING_START").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" name="formFULLCALENDAR_PREFILL_DATETIME_MORNING_START">';
print '<input type="hidden" name="action" value="set_FULLCALENDAR_PREFILL_DATETIME_MORNING_START">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table id="BLBLBLBL" class="nobordernopadding" cellpadding="0" cellspacing="0">';
print '<tr><td>';
print $form->select_date($conf->global->FULLCALENDAR_PREFILL_DATETIME_MORNING_START, 'FULLCALENDAR_PREFILL_DATETIME_MORNING_START', 1, 1, 0,'formFULLCALENDAR_PREFILL_DATETIME_MORNING_START',0, 0, 0, empty($conf->global->FULLCALENDAR_PREFILL_DATETIMES));
print '</td>';
print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print '</tr></table></form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$langs->trans("FULLCALENDAR_PREFILL_DATETIME_MORNING_END").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" name="formFULLCALENDAR_PREFILL_DATETIME_MORNING_END">';
print '<input type="hidden" name="action" value="set_FULLCALENDAR_PREFILL_DATETIME_MORNING_END">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table id="BLBLBLBL" class="nobordernopadding" cellpadding="0" cellspacing="0">';
print '<tr><td>';
print $form->select_date($conf->global->FULLCALENDAR_PREFILL_DATETIME_MORNING_END, 'FULLCALENDAR_PREFILL_DATETIME_MORNING_END', 1, 1, 0,'formFULLCALENDAR_PREFILL_DATETIME_MORNING_END',0, 0, 0, empty($conf->global->FULLCALENDAR_PREFILL_DATETIMES));
print '</td>';
print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print '</tr></table></form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$langs->trans("FULLCALENDAR_PREFILL_DATETIME_AFTERNOON_START").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" name="formFULLCALENDAR_PREFILL_DATETIME_AFTERNOON_START">';
print '<input type="hidden" name="action" value="set_FULLCALENDAR_PREFILL_DATETIME_AFTERNOON_START">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table id="BLBLBLBL" class="nobordernopadding" cellpadding="0" cellspacing="0">';
print '<tr><td>';
print $form->select_date($conf->global->FULLCALENDAR_PREFILL_DATETIME_AFTERNOON_START, 'FULLCALENDAR_PREFILL_DATETIME_AFTERNOON_START', 1, 1, 0,'formFULLCALENDAR_PREFILL_DATETIME_AFTERNOON_START',0, 0, 0, empty($conf->global->FULLCALENDAR_PREFILL_DATETIMES));
print '</td>';
print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print '</tr></table></form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$langs->trans("FULLCALENDAR_PREFILL_DATETIME_AFTERNOON_END").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" name="formFULLCALENDAR_PREFILL_DATETIME_AFTERNOON_END">';
print '<input type="hidden" name="action" value="set_FULLCALENDAR_PREFILL_DATETIME_AFTERNOON_END">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table id="BLBLBLBL" class="nobordernopadding" cellpadding="0" cellspacing="0">';
print '<tr><td>';
print $form->select_date($conf->global->FULLCALENDAR_PREFILL_DATETIME_AFTERNOON_END, 'FULLCALENDAR_PREFILL_DATETIME_AFTERNOON_END', 1, 1, 0,'formFULLCALENDAR_PREFILL_DATETIME_AFTERNOON_END',0, 0, 0, empty($conf->global->FULLCALENDAR_PREFILL_DATETIMES));
print '</td>';
print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print '</tr></table></form>';
print '</td></tr>';



$var=!$var;

print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FULLCALENDAR_SPLIT_DAYS").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
if(!empty($conf->global->FULLCALENDAR_PREFILL_DATETIMES)){
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_FULLCALENDAR_SPIT_DAYS">';
echo ajax_constantonoff('FULLCALENDAR_SPLIT_DAYS');
}
print '</form>';
print '</td></tr>';

print '</table>';

llxFooter();

$db->close();
