# Change Log
All notable changes to this project will be documented in this file.

## Not Released

## Release 2.7
- FIX: DA027282 - Change in how to fix the problem of not desired data in the object event - *31/10/2025* - 2.7.20
- FIX: DA027282 - Data not desired in the object - *30/10/2025* - 2.7.19
- FIX: DA027064 - Contact id wasn't kept - *19/09/2025* - 2.7.18
- FIX: DA026711 - Adding mode = 3 to a dol_escape_js call to avoid js syntax error due to wrong string delimiters - *01/08/2025* - 2.7.17
- FIX: COMPAT V22 - *31/07/2025* - 2.7.16 
- FIX: DA026711 - removed a str_replace that was causing an error in the console because line breaks were being removed for no reason and a comment in the std lib was causing a problem - *30/07/2025* - 2.7.16
- FIX: DA026711 infinite loop call stack in cloning event - *25/06/2025* - 2.7.15
- FIX: DA026440 incorrect end date for all-day event - *22/05/2025* - 2.7.14
- FIX: Update leave handling with half-day precision - *20/05/2025* - 2.7.13
- FIX: Ticket DA024563 removal of mixed colors for leaves - *05/05/2025* - 2.7.12
- FIX: Ticket DA024563 causing the bug + addition of user colors and leave request status - *05/05/2025* - 2.7.11
- FIX: DA026124 - Bug on notification selection - **27/02/2025** - 2.7.10
- FIX: DA025956 - Company input field being cleared despite successful saving - **17/02/2025** - 2.7.9
- FIX: DA025956 - Removal of part of the query returning an empty object - **13/01/2025** - 2.7.8
- FIX: Compatibility 21 - *12/12/2024* - 2.7.7
- FIX: DA025444 - When editing an event, some fields were empty & some events were not editable - *17/10/2024* - 2.7.6
- FIX: Change the timezone output to display correct hours - *27/09/2024* - 2.7.5
- FIX: Filter on calendar - *11/09/2024* - 2.7.4
- FIX: *23/08/2024* - 2.7.3
    - Display of the event in the correct timezone
    - Display of event completion
    - Display of event colors based on user color
    - Assignment of the correct owner during event creation
- FIX: Removal of individual event fetch after the selection query - *19/08/2024* - 2.7.2
- FIX: Refactor of part of the event selection query on the fullcalendar side - *05/08/2024* - 2.7.1
- FIX: Compatibility v20 - *22/07/2024* - 2.7.0
  Changed Dolibarr compatibility range to 16 min - 20 max

## Release 2.6

- FIX: Addition of leave management on full calendar - *05/06/2024* - 2.6.9
- FIX: Add LIMIT on query to fetch events, to prevent memory limit access - DA024924 - *30/05/2024* - 2.6.8
- FIX: UTC timezone PHP add event return ticket - *17/05/2024* - 2.6.7
- FIX: No fullcalendar on calendar view (because of http_referer) - *29/04/2024* - 2.6.6
- FIX: Warnings blocking module activation - *10/04/2024* - 2.6.5
- FIX: UTC timezone PHP add event - *22/03/2024* - 2.6.4
- FIX: Search button on other views (month, week, day) - *18/03/2024* - 2.6.3
- FIX: Checkbox external calendar - *18/03/2024* - 2.6.2
- FIX: State configuration wasn't working - *22/01/2024* - 2.6.1
- NEW: COMPATV19 - *24/11/2023* - 2.6.0  
  Changed Dolibarr compatibility range to 15 min - 19 max  
  Change PHP compatibility range to 7.0 min - 8.2 max

## Release 2.5

- FIX: DA024868 textlabel on conf activated - *22/05/2024* - 2.5.5
- FIX: DA024569 - Removal of duplicate standard buttons with full calendar + relocation of the search button next to filters - *05/03/2024* - 2.5.4
- FIX: DA023055 - Management of search on "Not applicable" status - *07/03/2023* - 2.5.3
- FIX: Remove deprecated function call - *03/08/2022* - 2.5.2
- FIX: Icon for v16 compatibility - *03/08/2022* - 2.5.1
- NEW: Addition of TechATM class for displaying the "About" page - *11/05/2022* - 2.5.0

## Release 2.4

- FIX: PHP8.1 warnings - *02/08/2022* - 2.4.8
- FIX: Fullcalendar reacting to standard viewmode - *02/08/2022* - 2.4.7
- FIX: Position of the first menu to avoid blocking error of already existing menu - *02/08/2022* - 2.4.6
- FIX: Addition of token where it was missing - *21/07/2022* - 2.4.5
- FIX: Change family name - *02/06/2022* - 2.4.4
- FIX: Compatibility V16: newToken - *02/06/2022* - 2.4.3
- FIX: Bug in displaying text color (times, description, links) based on the event background color (light or dark) - *27/06/2022* - 2.4.2
- FIX: Bug in fullcalendar display after activating conf reminders module agenda - *11/01/2022* - 2.4.1
- NEW: Upgrade of the fullcalendar library from 3.9 to 3.10 - *16/12/2021* - 2.4.0  
  *Change necessary due to compatibility issues with Dolibarr's jQuery library*  
  **Warning**: This library change may significantly impact modules overriding fullcalendar  
  As a result, the module moves to V2.4

## Release 2.3

- FIX: Compatibility V13 - $user->societe_id became $user->socid - *17/05/2021* - 2.3.1
- FIX: Compatibility V13: add token renewal - *17/05/2021* - 2.3.1
- FIX: Compatibility V14: Edit the descriptor: editor_url and family - *2021-06-10* - 2.3.1

- NEW: Filter slot duration and min max hour on task view T2700 - *08/04/2021* - 2.3.0

## Release 2.2

- FIX: Forgotten </strong> + need to fetch task on each iteration - *07/04/2021* - 2.2.2
- NEW: Add thirdparty in tooltip - *31/03/2021* - 2.2.1
- NEW: Editable field on task view T2700 - *24/03/2021* - 2.2.0
- NEW: "headTask" param to add something in fullcalendar project task view T2699 - *24/03/2021* - 2.1.0

## Release 1.5

- FIX: External calendars display - *27-04-2021* - 1.5.5
- V13 compatibility after renaming contactid properties into contact_id - *2021-03-04*
- Remove unused box folder
