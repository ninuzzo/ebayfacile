<?php

namespace StopWatch;

# See: http://www.phpjabbers.com/measuring-php-page-load-time-php17.html

function start() {
  global $stopwatch_time, $stopwatch_start;

  $stopwatch_time = microtime();
  $stopwatch_time = explode(' ', $stopwatch_time);
  $stopwatch_time = $stopwatch_time[1] + $stopwatch_time[0];
  $stopwatch_start = $stopwatch_time;
}

function stop() {
  global $stopwatch_time, $stopwatch_start;

  $stopwatch_time = microtime();
  $stopwatch_time = explode(' ', $stopwatch_time);
  $stopwatch_time = $stopwatch_time[1] + $stopwatch_time[0];
  $finish = $stopwatch_time;
  $total_time = round(($finish - $stopwatch_start), 4);
  echo 'Page generated in '.$total_time.' seconds.<br>';
}

?>
