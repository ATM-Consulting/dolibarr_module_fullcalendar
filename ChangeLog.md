# Change Log
All notable changes to this project will be documented in this file.

## Not Released
- NEW : Ajout de la class TechATM pour l'affichage de la page "A propos" *11/05/2022* 2.5.0

## Version 2.4

- FIX : php8.1 warnings *02/08/2022* - 2.4.8
- FIX : fullcalendar reacting to standard viewmode *02/08/2022* - 2.4.7
- FIX : position du premier menu pour eviter l'erreur bloquante menu déjà existant *02/08/2022* - 2.4.6
- FIX : Ajout de token là où il en manquait *21/07/2022* - 2.4.5
- FIX : change family name - *02/06/2022* - 2.4.4
- FIX : Compatibility V16 : newToken - *02/06/2022* - 2.4.3
- FIX : Bug affichage de la couleur du texte (horaires, description, liens) en fonction de la couleur de fond des évènements (couleur claire ou foncée) *27/06/2022* - 2.4.2
- FIX : Bug affichage fullcalendar après activation conf reminders module agenda *11/01/2022* - 2.4.1
- NEW : Upgrade de la lib fullcalendar de la 3.9 vers la 3.10 *16/12/2021* - 2.4.0  
   *Changement nécessaire pour des problèmes de compatibilité avec la lib Jquery de Dolibarr*  
   **Attention** : Ce changement de lib peut avoir un impact important sur les modules qui surchage fullcalendar  
   par conséquent le module passe en V2.4

## Version 2.3

- FIX : Compatibility V13 - $user->societe_id became $user->socid *17/05/2021* - 2.3.1
- FIX : Compatibility V13 : add token renowal *17/05/2021* - 2.3.1
- FIX - Compatibility V14 : Edit the descriptor: editor_url and family - *2021-06-10* - 2.3.1

- NEW : Filter slot duration and min max hour on task view T2700 *08/04/2021* - 2.3.0

## Version 2.2

- FIX : forgotten </strong> + need to fetch task on each iteration *07/04/2021* 2.2.2
- NEW : add thirdparty in tooltip *31/03/2021*- 2.2.1
- NEW : Editable field on task view T2700 *24/03/2021* - 2.2.0
- NEW : "headTask" param to add something in fullcalendar project task view T2699 *24/03/2021* - 2.1.0

## Version 1.5

- FIX : External calendars display *27-04-2021* - 1.5.5
- V13 compatibility after renaming contactid properties into contact_id *2021-03-04*
- remove unused box folder
