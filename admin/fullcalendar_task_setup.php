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
 * 	\file		admin/about.php
 * 	\ingroup	fullcalendar
 * 	\brief		This file is an example about page
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

$action = GETPOST('action', 'alpha');


/*
 * Actions
 */

if (preg_match('/set_(.*)/',$action,$reg))
{
	$code=$reg[1];

	$value = GETPOST($code, 'none');

	if(preg_match('/^FULLCALENDAR_PREFILL_DATETIME_/', $code))
	{
		$value = dol_mktime(GETPOST($code . 'hour', 'none'), GETPOST($code . 'min', 'none'), 0, 1, 1, 1970);
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
$page_name = "fullcalendarSetupTask";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = fullcalendarAdminPrepareHead();
dol_fiche_head(
    $head,
    'tasks',
    $langs->trans("Module104851Name"),
    0,
    'fullcalendar@fullcalendar'
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
print '<td>'.$langs->trans("FULLCALENDAR_ENABLE_TASKS").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_FULLCALENDAR_ENABLE_TASKS">';
echo ajax_constantonoff('FULLCALENDAR_ENABLE_TASKS');
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FULLCALENDAR_TASK_SHOW_THIS_HOURS").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_FULLCALENDAR_TASK_SHOW_THIS_HOURS">';
print '<input type="text" name="FULLCALENDAR_TASK_SHOW_THIS_HOURS" value="'.$conf->global->FULLCALENDAR_TASK_SHOW_THIS_HOURS.'" />';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FULLCALENDAR_TASK_DURATION_SLOT").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_FULLCALENDAR_TASK_DURATION_SLOT">';

$TOption=array(
	'00:30:00'=>'30 '.$langs->trans('minutes')
	,'00:15:00'=>'15 '.$langs->trans('minutes')
	,'00:05:00'=>'5 '.$langs->trans('minutes')
	,'01:00:00'=>'1 '.$langs->trans('hour')
);

echo $form->selectarray('FULLCALENDAR_TASK_DURATION_SLOT', $TOption, $conf->global->FULLCALENDAR_TASK_DURATION_SLOT);

print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';

print '</form>';
print '</td></tr>';

print '</table>';
dol_fiche_end();



llxFooter();

$db->close();