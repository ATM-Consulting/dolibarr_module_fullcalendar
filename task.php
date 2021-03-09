<?php

require 'config.php';

$langs->loadLangs(array('fullcalendar@fullcalendar'));

$hookmanager->initHooks(array('fullcalendartasks'));

$title = $langs->trans("TaskOrdo");
//if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name." - ".$title;
$help_url = '';

list($langjs, $dummy) = explode('_', $langs->defaultlang);

if($langjs == 'en') $langjs = 'en-gb';
if(empty($conf->global->FULLCALENDAR_ENABLE_TASKS) || empty($user->rights->projet->lire)) accessforbidden();

if(! is_file(dol_buildpath('/fullcalendar/lib/fullcalendar/dist/lang/'.$langjs.'.js'))) $langjs = 'en-gb';

$TIncludeCSS = array(
    '/fullcalendar/lib/fullcalendar/dist/fullcalendar.min.css',
    '/fullcalendar/css/fullcalendar.css'
);

$TIncludeJS = array(
    '/fullcalendar/lib/moment/min/moment.min.js',
    '/fullcalendar/lib/fullcalendar/dist/fullcalendar.min.js',
    '/fullcalendar/lib/fullcalendar/dist/lang/'.$langjs.'.js',
);

$hookmanager->initHooks(array('fullcalendartask'));

$title = $langs->trans("TaskOrdo");
//if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name." - ".$title;
$help_url = '';
llxHeader('', $title, $help_url, '', 0, 0, $TIncludeJS, $TIncludeCSS);

?>

    <script>

        document.addEventListener('DOMContentLoaded', function () {
            var currentsource = '<?php echo dol_buildpath('/fullcalendar/script/interface.php', 1) ?>'+'?get=tasks';
            $('#calendar').fullCalendar({
                header: {
                    left: 'title',
                    center: 'agendaDay,agendaWeek,month',
                    right: 'prev,next today'
                }
                // , slotDuration : '00:01:00'
                , defaultDate: getDate()
                , height: getFullCalendarHeight()
                , eventSources: [currentsource]
                , weekNumbers: true
                , displayEventTime: true
                , defaultView: 'agendaWeek'
                , nowIndicator: true
                , eventLimit: true
                , eventAfterRender: function (event, element, view) {

                    if (event.color != '') {
                        element.css({
                            'background-color': ''
                            , 'background': event.color

                        });
                    }


                    if (event.isDarkColor == 1) {
                        element.css({color: '#fff'});

                        element.find('a').css({
                            color: '#fff'
                        });
                    }

                }
                , eventRender: function (event, element, view) {
                    var title = element.find('.fc-title').html();
                    element.find('.fc-title').html('<a class="url_title" href="'+event.url_title+'" onclick="event.stopPropagation();">'+title+'</a>');

                    if ($().tipTip) // ou $.fn.tipTip, mais $.tipTip ne fonctionne pas
                    {
                        element.tipTip({
                            maxWidth: '600px', edgeOffset: 10, delay: 50, fadeIn: 50, fadeOut: 50
                            , content: '<strong>'+event.title+'</strong><br />'+event.description
                        });

                        element.find('.classfortooltip').tipTip({maxWidth: '600px', edgeOffset: 10, delay: 50, fadeIn: 50, fadeOut: 50});
                        element.find('.classforcustomtooltip').tipTip({maxWidth: '600px', edgeOffset: 10, delay: 50, fadeIn: 50, fadeOut: 5000});
                    } else {
                        element.tooltip({
                            items: 'a.fc-event' // La boîte entière de l'événement montre un tooltip, par défaut, ce sont tous les éléments contenus dans le sélecteur avec une balise title
                            , show: {collision: 'flipfit', effect: 'toggle', delay: 50}
                            , hide: {delay: 50}
                            , position: {my: 'left+10 center', at: 'right center'}
                            , content: '<strong>'+event.title+'</strong><br />'+event.description
                        });

                        // On remet les tooltips des liens désactivés par l'appel ci-dessus
                        element.find('.classfortooltip, .classforcustomtooltip').tooltip({
                            show: {collision: 'flipfit', effect: 'toggle', delay: 50}
                            , hide: {delay: 50}
                            , tooltipClass: 'mytooltip'
                            , content: function () {
                                return $(this).prop('title');
                            }
                        });
                    }
                }
                , eventResize: function (event, delta, revertFunc, jsEvent, ui, view) {

                    $.ajax({
                        url: '<?php echo dol_buildpath('/fullcalendar/script/interface.php', 1) ?>'
                        , data: {
                            put: 'task-resize'
                            , id: event.id
                            , data: delta._data
                        }
                    }).done(function () {
                        $('#calendar').fullCalendar('refetchEvents');
                    });
                }
                , eventDrop: function (event, delta, revertFunc, jsEvent, ui, view) {

                    $.ajax({
                        url: '<?php echo dol_buildpath('/fullcalendar/script/interface.php', 1) ?>'
                        , data: {
                            put: 'task-move'
                            , id: event.id
                            , data: delta._data
                        }
                    }).done(function () {
                        $('#calendar').fullCalendar('refetchEvents');
                    });
                }
            });
        });

        function getFullCalendarHeight() {
            return $(window).height()-$('#id-right').offset().top-30;
        }

        function getDate() {
            var d = new Date(),
                month = ''+(d.getMonth()+1),
                day = ''+d.getDate(),
                year = d.getFullYear();

            if (month.length < 2)
                month = '0'+month;
            if (day.length < 2)
                day = '0'+day;

            return [year, month, day].join('-');
        }
    </script>
<?php
print '<div id="calendar"></div>';
print '<div id="dialog-add-event" title="'.$langs->trans('CreateNewORAction').'"></div>';

llxFooter();

