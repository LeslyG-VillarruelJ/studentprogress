<?php
// File: local/studentprogress/index.php

require_once('../../config.php');
require_once($CFG->dirroot.'/local/studentprogress/classes/form/monitoring_form.php');
require_once($CFG->dirroot.'/local/studentprogress/classes/form/configuration_form.php');

$courseid = required_param('id', PARAM_INT);
$active_tab = optional_param('tab', 'monitoring', PARAM_ALPHA);


require_login($courseid);
$context = context_course::instance($courseid);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/studentprogress/index.php', ['id' => $courseid]));
$PAGE->set_title(get_string('pluginname', 'local_studentprogress'));
$PAGE->set_heading(format_string(get_course($courseid)->fullname));

$tabs = [
    new tabobject('monitoring', new moodle_url('/local/studentprogress/index.php', ['id' => $courseid, 'tab' => 'monitoring']), get_string('monitoring', 'local_studentprogress')),
    new tabobject('configuration', new moodle_url('/local/studentprogress/index.php', ['id' => $courseid, 'tab' => 'configuration']), get_string('configuration', 'local_studentprogress')),
];

echo $OUTPUT->header();
echo $OUTPUT->tabtree($tabs, $active_tab);

switch ($active_tab) {
    case 'configuration':
    $mform = new \local_studentprogress\form\configuration_form(null, ['courseid' => $courseid]);

    if ($mform->is_cancelled()) {
        redirect(new moodle_url('/local/studentprogress/index.php', ['id' => $courseid, 'tab' => 'monitoring']));
    } else if ($data = $mform->get_data()) {
        if ($mform->process_data($data)) {
            \core\notification::success(get_string('configsaved', 'local_studentprogress'));
            redirect(new moodle_url('/local/studentprogress/index.php', ['id' => $courseid, 'tab' => 'monitoring']));
        } else {
            \core\notification::error(get_string('confignotsaved', 'local_studentprogress'));
        }
    }

    $mform->display();
    break;

    default:
        $mform = new \local_studentprogress\form\monitoring_form(null, ['courseid' => $courseid]);

        if ($mform->is_cancelled()) {
            redirect(new moodle_url('/local/studentprogress/index.php', ['id' => $courseid, 'tab' => 'monitoring']));
        }

        $mform->display();
        break;
}

echo $OUTPUT->footer();
