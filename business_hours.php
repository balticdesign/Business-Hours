<?php
function mbhi_hours() {
    // Initial setup
   
    $datestamp = strtotime("today");
    $thirty_two_days_ahead = strtotime("+32 days");
    

    // Prepare initial HTML with weekday hours
    $wk_open = date("H:i", strtotime(get_field('weekdays_wd_store_is_open')));
    $wk_closed = date("H:i", strtotime(get_field('weekdays_wd_store_is_closed')));
    $html = '<table style="border:0px !important;"><tbody>'; //class="mabel-bhi-businesshours"
    $html .= "<tr><td>Monday - Friday</td><td>{$wk_open} - {$wk_closed}</td></tr>"; // class='mbhi-is-current'  class='mabel-bhi-day'

   	$allDates = []; // Initialize as empty array to hold both holidays and special dates.

    // Process Holidays
    $allDates += processHolidays();

    // Process Special Dates
    $allDates += processSpecialDates($datestamp, $thirty_two_days_ahead);
	$allDates += handleWeekendDates();
    
    ksort($allDates);
    
    foreach ($allDates as $date => $info) {
       
        $label = $info['name']; // Default label
        $html .= sprintf('<tr><td class="%s">%s</td><td>%s</td></tr>', $info['class'], $label, $info['hours']); //mabel-bhi-day 
    }

    // Finalize and return HTML
    $html .= '</tbody></table>';
    return $html;
}

function handleWeekendDates() {
    $weekend = [];
    $saturdayStamp = strtotime("next Saturday");
    $sundayStamp = strtotime("next Sunday");

    // Assuming get_field returns '1' for true (closed all day)
    $sat_allday = get_field('saturday_sat_closed_all_day');
    $sun_allday = get_field('sunday_sun_closed_all_day_copy');

    $sat_open = !$sat_allday ? date("H:i", strtotime(get_field('saturday_sat_store_is_open'))) : 'Closed';
    $sat_closed = !$sat_allday ? date("H:i", strtotime(get_field('saturday_sat_store_is_closed'))) : '';
    $sun_open = !$sun_allday ? date("H:i", strtotime(get_field('sunday_sun_store_is_open'))) : 'Closed';
    $sun_closed = !$sun_allday ? date("H:i", strtotime(get_field('sunday_sun_store_is_closed'))) : '';

    // Add Saturday and Sunday with proper formatting
    $weekend[$saturdayStamp] = [
        'name' => 'Saturday',
        'hours' => $sat_open === 'Closed' ? 'Closed' : "$sat_open - $sat_closed",
        'class' => 'weekend'
    ];
    $weekend[$sundayStamp] = [
        'name' => 'Sunday',
        'hours' => $sun_open === 'Closed' ? 'Closed' : "$sun_open - $sun_closed",
        'class' => 'weekend'
    ];

    return $weekend;
}

function processHolidays() {
    $holidays = [];
    $nextMonday = strtotime("next monday");
    $thirty_two_days_ahead = strtotime("+32 days");
    $currentDate = strtotime("today"); // Get the current date to filter out past holidays
    
    if (have_rows('holidays')) {
        while (have_rows('holidays')) : the_row();
            $holName = get_sub_field('holiday_name');
            $holDateFrom = get_sub_field('ho_date_from');
            if ($holDateFrom) {
                $hol_stamp = strtotime($holDateFrom);
                // Filter out past holidays
                if ($hol_stamp < $currentDate) {
                    continue;
                }
                
                if ($hol_stamp < $nextMonday) {
                    $Hday = date('D', strtotime($holDateFrom));
                } elseif ($hol_stamp <= $thirty_two_days_ahead) {
                    $Hday = date('jS M', strtotime($holDateFrom));
                } else {
                    continue;
                }
                
                $holDateTo = get_sub_field('ho_date_to');
                // Assuming the dates are inclusive, you might need to iterate through each day
                // For simplicity, we're just marking the start date
                $holidays[$hol_stamp] = [
                    'name' => $holName . " (" . $Hday . ")",
                    'hours' => 'Closed', // Simplified representation
                    'class' => 'holiday'
                ];
            }
        endwhile;
    }
    return $holidays;
}

function processSpecialDates($currentDate, $endOfFollowingMonth) {
    $specialDates = [];
    $nextMonday = strtotime("next monday");
    $thirty_two_days_ahead = strtotime("+32 days");
    if (have_rows('special_dates')) {
        while (have_rows('special_dates')) : the_row();
            $spName = get_sub_field('special_hours_name');
            $spDate = get_sub_field('special_hours_date');
		 	$SpStamp = strtotime($spDate);
             if ($spDate) {
                if ($SpStamp < $currentDate) {
                    continue;
                }
            if ($SpStamp < $nextMonday) {
                $spDay = date('D', strtotime($spDate));
            } elseif ($SpStamp <= $thirty_two_days_ahead) {
                $spDay = date('jS M', strtotime($spDate));
            } else {
                continue;
            }

            $spOpen = get_sub_field('sp_store_is_open') ?: 'Closed';
            $spClose = get_sub_field('sp_store_is_closed') ?: '';
            $hours = $spOpen === 'Closed' ? 'Closed' : $spOpen . ' - ' . $spClose;
                $specialDates[$SpStamp] = [
                    'name' => $spName . " (" . $spDay . ")",
                    'hours' => $hours,
                    'class' => 'special-date'
                ];
            }
        endwhile;
    }
    return $specialDates;
}
?>