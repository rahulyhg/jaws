<?php
/**
 * EventsCalendar Gadget
 *
 * @category    Gadget
 * @package     EventsCalendar
 * @author      Mohsen Khahani <mkhahani@gmail.com>
 * @copyright   2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
$GLOBALS['app']->Layout->AddHeadLink('gadgets/EventsCalendar/Resources/site_style.css');
class EventsCalendar_Actions_ViewWeek extends Jaws_Gadget_Action
{
    /**
     * Builds week view UI
     *
     * @access  public
     * @return  string  XHTML UI
     */
    function ViewWeek()
    {
        $data = jaws()->request->fetch(array('year', 'month', 'day'), 'get');
        $year = (int)$data['year'];
        $month = (int)$data['month'];
        $day = (int)$data['day'];

        $this->AjaxMe('site_script.js');
        $tpl = $this->gadget->loadTemplate('ViewWeek.html');
        $tpl->SetBlock('week');

        $tpl->SetVariable('lbl_day', _t('EVENTSCALENDAR_DAY'));
        $tpl->SetVariable('lbl_events', _t('EVENTSCALENDAR_EVENTS'));

        // Menubar
        $action = $this->gadget->loadAction('Menubar');
        $tpl->SetVariable('menubar', $action->Menubar('Events'));

        $jdate = $GLOBALS['app']->loadDate();

        // Previous week
        $info = $jdate->GetDateInfo($year, $month, $day - 7);
        $prev_url = $this->gadget->urlMap('ViewWeek', array(
            'year' => $info['year'],
            'month' => $info['mon'],
            'day' => $info['mday']
        ));
        $tpl->SetVariable('prev_url', $prev_url);
        $tpl->SetVariable('prev', _t('EVENTSCALENDAR_PREV_WEEK'));

        // Next week
        $info = $jdate->GetDateInfo($year, $month, $day + 7);
        $next_url = $this->gadget->urlMap('ViewWeek', array(
            'year' => $info['year'],
            'month' => $info['mon'],
            'day' => $info['mday']
        ));
        $tpl->SetVariable('next_url', $next_url);
        $tpl->SetVariable('next', _t('EVENTSCALENDAR_NEXT_WEEK'));

        $todayInfo = $jdate->GetDateInfo($year, $month, $day);
        $startDay = $day - $todayInfo['wday'];
        $stopDay = $startDay + 6;
        $startDayInfo = $jdate->GetDateInfo($year, $month, $startDay);
        $stopDayInfo = $jdate->GetDateInfo($year, $month, $stopDay);

        // This week
        $start = $jdate->ToBaseDate($year, $month, $startDay);
        $start = $start['timestamp'];
        $stop = $jdate->ToBaseDate($year, $month, $stopDay, 23, 59, 59);
        $stop = $stop['timestamp'];
        $from = $jdate->Format($start, 'Y MN d');
        $to = $jdate->Format($stop, 'Y MN d');
        $current = $from . ' - ' . $to;
        $this->SetTitle($current . ' - ' . _t('EVENTSCALENDAR_EVENTS'));
        $tpl->SetVariable('title', $current);

        // Fetch events
        $model = $this->gadget->loadModel('Report');
        $user = (int)$GLOBALS['app']->Session->GetAttribute('user');
        $events = $model->GetEvents($user, null, null, $start, $stop, array('month' => $month));
        if (Jaws_Error::IsError($events)){
            $events = array();
        }

        // Prepare events
        $eventsById = array();
        $eventsByDay = array_fill(1, 7, array());
        foreach ($events as $e) {
            $eventsById[$e['id']] = $e;
            $startIdx = ($e['start_date'] <= $start)? 1:
                floor(($e['start_date'] - $start) / 86400) + 1;
            $stopIdx = ($e['stop_date'] >= $stop)? 7:
                ceil(($e['stop_date'] - $start) / 86400);
            for ($i = $startIdx; $i <= $stopIdx; $i++) {
                $eventsByDay[$i][] = $e['id'];
            }
        }

        // Display events
        for ($i = 1; $i <= 7; $i++) {
            $info = $jdate->GetDateInfo($year, $month, $startDay + $i - 1);
            $tpl->SetBlock('week/day');
            $day_url = $this->gadget->urlMap('ViewWeek', array(
                'year' => $year,
                'month' => $month,
                'day' => $info['mday']
            ));
            $tpl->SetVariable('day_url', $day_url);
            $tpl->SetVariable('day', $info['mday'] . ' ' . $info['weekday']);
            foreach ($eventsByDay[$i] as $event_id) {
                $e = $eventsById[$event_id];
                if (($e['day'] == 0 || $e['day'] == $info['mday']) &&
                    ($e['wday'] == 0 || $e['wday'] == $info['wday'] + 1))
                {
                    $tpl->SetBlock('week/day/event');
                    $tpl->SetVariable('event', $e['subject']);
                    $url = $this->gadget->urlMap('ViewEvent', array('id' => $event_id));
                    $tpl->SetVariable('event_url', $url);
                    $tpl->ParseBlock('week/day/event');
                }
            }
            $tpl->ParseBlock('week/day');
        }

        $tpl->ParseBlock('week');
        return $tpl->Get();
    }
}