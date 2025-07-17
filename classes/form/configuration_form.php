<?php
namespace local_studentprogress\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

use moodleform;

class configuration_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];
        $num_alerts = 5;

        $mform->addElement('hidden', 'id', $courseid);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'tab', 'configuration');
        $mform->setType('tab', PARAM_ALPHA);

        $mform->addElement('html', '<style>
            #page-local-studentprogress-index .config-container {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 auto;
                padding: 20px;
                box-sizing: border-box;
            }
            .config-container h2 {
                font-size: 1.8em;
                font-weight: bold;
                border-bottom: 2px solid black;
                padding-bottom: 5px;
            }
            .config-description {
                background-color: #f0f0f0;
                padding: 15px;
                border-radius: 10px;
                margin: 20px 0;
                font-size: 1em;
            }
            .section-title {
                font-weight: bold;
                font-size: 1.1em;
                margin-top: 20px;
            }
            .radio-group label {
                margin-right: 50px;
                font-weight: normal;
            }
            .alerts-container input[type="text"] {
                display: block;
                width: 100%;
                padding: 12px;
                margin-bottom: 10px;
                background-color: #e0e0e0;
                border: none;
                border-radius: 5px;
                font-size: 1em;
            }
            select {
                padding: 8px;
                font-size: 1em;
                border-radius: 5px;
                background-color: #fff6cc;
                border: 1px solid #ccc;
            }
            .submit-button {
                background-color: #d9d9d9;
                border: none;
                padding: 12px 25px;
                border-radius: 15px;
                font-weight: bold;
                font-size: 1em;
                cursor: pointer;
                margin-top: 20px;
            }
            .alert-row {
                display: flex;
                align-items: center;
                margin-bottom: 10px;
            }
            .alert-input {
                flex: 1;
                margin-right: 10px;
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box;
                resize: vertical;
            }
            .alert-range {
                padding: 8px 12px;
                border: 2px solid #3399ff;
                border-radius: 8px;
                background-color: #e6f2ff;
                color: #000;
                white-space: nowrap;
                font-size: 14px;
                min-width: 120px;
                text-align: center;
            }
        </style>');

        // Contenedor principal
        $mform->addElement('html', '<div class="config-container">');
        $mform->addElement('html', '<h2>' . get_string('studentnotificationconfig', 'local_studentprogress') . '</h2>');
        $mform->addElement('html', '<div class="config-description">' . get_string('studentnotificationinfotext', 'local_studentprogress') . '</div>');

        // ID curso
        $mform->addElement('hidden', 'id', $courseid);
        $mform->setType('id', PARAM_INT);


        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->setType('sesskey', PARAM_RAW);

        // Duración
        $mform->addElement('html', '<div class="section-title">' . get_string('duration', 'local_studentprogress') . '</div>');
        $durationradios = [];
        foreach ([5, 10, 15, 20] as $sec) {
            $durationradios[] = $mform->createElement('radio', 'duration', '', $sec . ' ' . get_string('seconds', 'local_studentprogress'), $sec);
        }
        $mform->addGroup($durationradios, 'durationgroup', '', [' '], false);
        $mform->addRule('durationgroup', null, 'required', null, 'client');
        $mform->setType('duration', PARAM_INT);

        $mform->addElement('html', '<div class="section-title">' . get_string('alerts', 'local_studentprogress') . '</div>');

        if ($num_alerts > 1) {
            $step = 100 / ($num_alerts - 1);
        } else {
            $step = 100;
        }

        for ($i = 0; $i < $num_alerts; $i++) {
            $start = ($i == 0) ? 0 : round($step * $i, 2);
            $end = ($i == $num_alerts - 1) ? 100 : round($step * ($i + 1) - 0.01, 2);
            $range = ($i == $num_alerts - 1) ? "100%" : sprintf("%.2f%% - %.2f%%", $start, $end);

            // Start wrapper
            $mform->addElement('html', '<div class="alert-row">');

            // Add the actual input (with Moodle's internal layout)
            $mform->addElement('text', "alertmessage_$i", "Mensaje " . ($i + 1), [
                'rows' => 'auto',
                'style' => 'width: 100%; resize: vertical;',
                'class' => 'alert-input'
            ]);

            $mform->setType("alertmessage_$i", PARAM_TEXT);
            $mform->addRule("alertmessage_$i", get_string('required'), 'required', null, 'client');

            // Add the percentage range box
            $mform->addElement('html', '<div class="alert-range">' . $range . '</div>');

            // Close wrapper
            $mform->addElement('html', '</div>');
        }

        $this->add_action_buttons();
        $mform->addElement('html', '</div>'); // Cierra config-container
    }

    public function process_data($data) {
        global $DB;

        $courseid = $data->id;
        $duration = $data->duration;

        // Guardar duración
        $DB->delete_records('course_duration_plg', ['id_course' => $courseid]);
        $DB->insert_record('course_duration_plg', [
            'id_course' => $courseid,
            'duration' => $duration
        ]);

        // Guardamos los mensajes en la tabla personalizada.
        $DB->delete_records('course_alerts_plg', ['id_course' => $courseid]);

        foreach ($data as $key => $value) {
            if (strpos($key, 'alertmessage_') === 0 && !empty($value)) {
                $DB->insert_record('course_alerts_plg', [
                    'id_course' => $courseid,
                    'alert' => $value
                ]);
            }
        }

        return true;
    }
}

