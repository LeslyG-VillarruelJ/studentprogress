<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_studentprogress', get_string('pluginname', 'local_studentprogress'));
    $ADMIN->add('localplugins', $settings);
}
