<?php


$observers = array(
    array(
        'eventname'   => '\core\event\user_created',
        'callback'    => 'enrol_select_event_handler::user_created',
        'includefile' => '/enrol/select/eventlib.php'
    ),
);
