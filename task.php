<?php

require 'config.php';

$langs->loadLangs(array('fullcalendar@fullcalendar'));

$hookmanager->initHooks(array('fullcalendartasks'));

$title = $langs->trans("TaskOrdo");
//if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name." - ".$title;
$help_url = '';

list($langjs, $dummy) = explode('_', $langs->defaultlang);

if($langjs == 'en') $langjs = 'en-gb';
if(empty($conf->global->FULLCALENDAR_ENABLE_TASKS) || empty($user->rights->fullcalendar->task->read)) accessforbidden();

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

$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $tmpEvent, $action);    // Note that $action and $object may have been modified by hook

?>

    <script>

        document.addEventListener('DOMContentLoaded', function () {
            //Définition de la boite de dialog
            var taskediteventmodal = $('#dialog-edit-event');

			taskediteventmodal.dialog({
                autoOpen: false,
				autoResize:true,
                close: function( event, ui ) {
                   $('#calendar').fullCalendar('refetchEvents');
                }
            });
            //fullcalendar
            var currentsource = '<?php echo dol_buildpath('/fullcalendar/script/interface.php', 1) ?>'+'?get=tasks';
            $('#calendar').fullCalendar({
                header: {
                    left: 'title',
                    center: 'agendaDay,agendaWeek,month',
                    right: 'prev,next today'
                }
                <?php
                if(!empty($conf->global->FULLCALENDAR_TASK_SHOW_THIS_HOURS)) {
						list($hourShowStart, $hourShowEnd) = explode('-', $conf->global->FULLCALENDAR_TASK_SHOW_THIS_HOURS);
						if(!empty($hourShowStart) && !empty($hourShowEnd)) {
		        			?>,minTime:'<?php echo $hourShowStart.':00:00'; ?>'
		        			,maxTime:'<?php echo $hourShowEnd.':00:00'; ?>'<?php
						}
				}
                if(!empty($conf->global->FULLCALENDAR_TASK_DURATION_SLOT)) echo ',slotDuration:"'.$conf->global->FULLCALENDAR_TASK_DURATION_SLOT.'"';
                ?>
                , defaultDate: getDate()
                , height: getFullCalendarHeight()
                , eventSources: [currentsource]
                , weekNumbers: true
                , displayEventTime: true
                , defaultView: 'agendaWeek'
                , nowIndicator: true
                , eventLimit: true
                , nextDayThreshold: '00:00:00'
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
					element.find('.fc-content').prepend(event.headTask);

                    if ($().tipTip) // ou $.fn.tipTip, mais $.tipTip ne fonctionne pas
                    {
                        element.tipTip({
                            maxWidth: '600px', edgeOffset: 10, delay: 50, fadeIn: 50, fadeOut: 50
                            , content: event.headTask+'<strong>'+event.title+'</strong><br />'+event.description
                        });

                        element.find('.classfortooltip').tipTip({maxWidth: '600px', edgeOffset: 10, delay: 50, fadeIn: 50, fadeOut: 50});
                        element.find('.classforcustomtooltip').tipTip({maxWidth: '600px', edgeOffset: 10, delay: 50, fadeIn: 50, fadeOut: 5000});
                    } else {
                        element.tooltip({
                            items: 'a.fc-event' // La boîte entière de l'événement montre un tooltip, par défaut, ce sont tous les éléments contenus dans le sélecteur avec une balise title
                            , show: {collision: 'flipfit', effect: 'toggle', delay: 50}
                            , hide: {delay: 50}
                            , position: {my: 'left+10 center', at: 'right center'}
                            , content: event.headTask+'<strong>'+event.title+'</strong><br />'+event.description
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
                , eventDataTransform: function(event) {
                  if(event.allDay && moment(event.end).isAfter(event.start, 'day')) {
                    event.end = moment(event.end).add(1, 'days');
                  }
                  return event;
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
                ,
				eventClick: function(info) {
					$.ajax({
                        url: '<?php echo dol_buildpath('/fullcalendar/script/interface.php', 1) ?>'
                        , data: {
                            get: 'task-popin'
                            , fk_task: info.id
                        }
                    }).done(function (data) {
                        $('#dialog-edit-event').html(data);
                        taskediteventmodal.dialog('open');
                        taskediteventmodal.dialog({
                            height: 'auto', width: 'auto'
                            , buttons: {
                                '<?php echo $langs->trans('Update'); ?>': function () {
                                    updateTask();
                                    $(this).dialog('close');
                                },
                               '<?php echo $langs->trans('Cancel'); ?>': function () {
                                    $(this).dialog('close');
                                }
                            }
                        }); // resize to content
                        taskediteventmodal.parent().css({'top': '20%'});
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

        // refresh event on modal close
        $("#dialog-edit-event").on("hide.bs.modal", function (e) {
             $('#calendar').fullCalendar('refetchEvents');
        });

        function updateTask() {
            let data = $('#editableViewForm').serializeArray();
            $.ajax({
                url: '<?php echo dol_buildpath('/fullcalendar/script/interface.php', 1) ?>'
                , data: {
                    put: 'task-edit'
                    , data: data
                }
            }).done(function () {
                $('#calendar').fullCalendar('refetchEvents');
            });
        }

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
print '<div id="dialog-edit-event" title="'.$langs->trans('EditTask').'"></div>';


llxFooter();

