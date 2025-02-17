# Change Log
All notable changes to this project will be documented in this file.

## Not Released



## Release 2.7
- FIX : DA025956 - Remplissage de l'input société qui était vidé alors que l'enregistrement fonctionnait. - **17/02/2025** - 2.7.9
- FIX : DA025956 - Suppression d'une partie de la requête qui renvoyait un objet vide - **13/01/2025** - 2.7.8 
- FIX : Compat 21 - *12/12/2024* - 2.7.7
- FIX : DA025444 - Lors de la modification d'un événement, certains des champs étaient vides & certains événements n'étaient même pas éditables - *17/10/2024* - 2.7.6
- FIX : change the timezone output to display right hours *27/09/2024* - 2.7.5
- FIX : filtre sur calendar *11/09/2024* - 2.7.4
- FIX : *23/08/2024* - 2.7.3
  - Affichage de l'évènement sur le bon fuseau horaire 
  - Affichage de la complétion des événements
  - Affichage des couleurs des évènements en fonction de la couleur utilisateur
  - Assignation du bon propriétaire lors de la création d'un évenement
- FIX : Suppression du fetch de chaque évenement apres la requête de sélection - *19/08/2024* - 2.7.2
- FIX : Refactor d'une partie de la requête de sélection des événements côté fullcalendar - *05/08/2024* - 2.7.1
- FIX : Compat v20 - *22/07/2024* - 2.7.0
  Changed Dolibarr compatibility range to 16 min - 20 max

## Release 2.6

- FIX : Ajout de la gestion des congés sur full calendar - *05/06/2024* - 2.6.9
- FIX : Add LIMIT on query to fetch events, to prevent memory limit access - DA024924 - *30/05/2024* - 2.6.8
- FIX : Fuseau UTC php add event retour ticket- *17/05/2024* - 2.6.7
- FIX : No fullcalendar on calendar view (because of http_referer) - *29/04/2024* - 2.6.6 
- FIX : Warnings bloquant l'activation du module - *10/04/2024* - 2.6.5
- FIX : Fuseau UTC php add event - *22/03/2024* - 2.6.4
- FIX : Search button on other views (month, week, day) - *18/03/2024* - 2.6.3
- FIX : Checkbox external calendar - *18/03/2024* - 2.6.2
- FIX : State configuration wasn't working - *22/01/2024* - 2.6.1
- NEW : COMPATV19 - *24/11/2023* - 2.6.0  
  Changed Dolibarr compatibility range to 15 min - 19 max  
  Change PHP compatibility range to 7.0 min - 8.2 max

## Release 2.5

- FIX :  DA024868 textlabel on conf activated *22/05/2024* - 2.5.5  
- FIX : DA024569 - Suppression des boutons standard en double avec full calendar + déplacement du bouton de recherche a coté des filtres *05/03/2024* 2.5.4
- FIX : DA023055 - Gestion de la recherche sur le statut "Non applicable" *07/03/2023* 2.5.3
- FIX : Remove deprecated function call *03/08/2022* 2.5.2
- FIX : Icon for v16 compatibility *03/08/2022* 2.5.1
- NEW : Ajout de la class TechATM pour l'affichage de la page "A propos" *11/05/2022* 2.5.0

## Release 2.4

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

## Release 2.3

- FIX : Compatibility V13 - $user->societe_id became $user->socid *17/05/2021* - 2.3.1
- FIX : Compatibility V13 : add token renowal *17/05/2021* - 2.3.1
- FIX - Compatibility V14 : Edit the descriptor: editor_url and family - *2021-06-10* - 2.3.1

- NEW : Filter slot duration and min max hour on task view T2700 *08/04/2021* - 2.3.0

## Release 2.2

- FIX : forgotten </strong> + need to fetch task on each iteration *07/04/2021* 2.2.2
- NEW : add thirdparty in tooltip *31/03/2021*- 2.2.1
- NEW : Editable field on task view T2700 *24/03/2021* - 2.2.0
- NEW : "headTask" param to add something in fullcalendar project task view T2699 *24/03/2021* - 2.1.0

## Release 1.5

- FIX : External calendars display *27-04-2021* - 1.5.5
- V13 compatibility after renaming contactid properties into contact_id *2021-03-04*
- remove unused box folder
