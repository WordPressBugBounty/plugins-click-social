<?php

namespace Smashballoon\ClickSocial\App\Services;

if (!defined('ABSPATH')) {
	exit;
}

class TimeslotsScheduleService
{
	/**
	 * Generate schedule from the current time in the week, which avoids scheduling
	 * posts in the past.
	 *
	 * @param array $timeslots Timeslots.
	 * @param int $noEntries Number of entries.
	 *
	 * @return array
	 */
	public static function generate($timeslots, $noEntries)
	{
		if (! $noEntries) {
			return [];
		}

		$index = 0;

		$week = [
			'monday'    => [],
			'tuesday'   => [],
			'wednesday' => [],
			'thursday'  => [],
			'friday'    => [],
			'saturday'  => [],
			'sunday'    => [],
		];

		foreach ($timeslots as $timeslot) {
			$week[ $timeslot['week_day'] ][] = $timeslot;
		}

		foreach ($week as $day => $timeslots) {
			usort($timeslots, function ($a, $b) {
				return strtotime($a['publish_at']) - strtotime($b['publish_at']);
			});
			$week[ $day ] = $timeslots;
		}

		// Generate week schedule starting from current day time
		$start_week_day = strtolower(gmdate('l'));

		$earlierTimeslots = [];
		$laterTimeslots   = [];

		$local_time = date_i18n('H:i:s', strtotime('now'));

		$isEarlier = true;

		// Reorganize week based on current day in the week.
		$reorganizedWeek =
			array_slice($week, array_search($start_week_day, array_keys($week)), null, true)
			+ array_slice($week, 0, array_search($start_week_day, array_keys($week)), true);

		// Define timeslots for the week taking into account the current time in the week.
		foreach ($reorganizedWeek as $day => $timeslots) {
			foreach ($timeslots as $index => $timeslot) {
				if ((strtotime($timeslot['publish_at']) > strtotime($local_time))) {
					$isEarlier = false;
				}

				$timeslotData = [
					'day_offset' => array_search($day, array_keys($reorganizedWeek)),
					'day'        => $day,
					'time'       => $timeslot['publish_at'],
				];

				if ($isEarlier) {
					$earlierTimeslots[] = $timeslotData;
				} else {
					$laterTimeslots[] = $timeslotData;
				}
			}
		}

		// Put back timeslots for the week from earlier and later timeslots
		$organisedTimeslots = array_merge($laterTimeslots, $earlierTimeslots);

		if (count($organisedTimeslots) === 0) {
			return [];
		}

		$scheduleEntries = [];
		while ($index < $noEntries) {
			$currentTimeslot   = $organisedTimeslots[ $index % count($organisedTimeslots) ];
			$week_offset       = floor($index / count($organisedTimeslots)) * 7;
			$scheduleEntries[] = gmdate(
				'Y-m-d h:i:s',
				strtotime(
					sprintf('+%d days', $week_offset + $currentTimeslot['day_offset']),
					strtotime($currentTimeslot['time'])
				)
			);

			$index++;
		}

		return $scheduleEntries;
	}
}
