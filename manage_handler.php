<?php

use UniversiteRennes2\Apsolu as apsolu;

require(__DIR__.'/../../config.php');

$actions = required_param('actions', PARAM_ALPHA);

if ($actions === 'notify') {
    require(__DIR__.'/manage_notify.php');
}else if ($actions === 'changecourse') {
    require(__DIR__.'/manage_change_course.php');
} else if ($actions === 'editenroltype') {
    require(__DIR__.'/manage_editenroltype.php');
} else {
    require(__DIR__.'/manage_move.php');
}
