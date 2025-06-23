<?php
namespace local_studentprogress\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

use moodleform;

class configuration_form extends moodleform {

    public function definition() {
        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];

        // Estilos personalizados (idéntico a tu diseño original)
        $style = '
        <style>
        .config-container {
            max-width: 700px;
            margin: 0 auto;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
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
            margin-right: 20px;
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
        </style>';
        $mform->addElement('html', $style);

        // Contenedor principal
        $mform->addElement('html', '<div class="config-container">');
        $mform->addElement('html', '<h2>' . get_string('studentnotificationconfig', 'local_studentprogress') . '</h2>');
        $mform->addElement('html', '<div class="config-description">' . get_string('studentnotificationinfotext', 'local_studentprogress') . '</div>');

        // ID curso
        $mform->addElement('hidden', 'id', $courseid);
        $mform->setType('id', PARAM_INT);;,,zzzzzzzz


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

        // Número de alertas
        $mform->addElement('html', '<div class="section-title">' . get_string('alerts', 'local_studentprogress') . '</div>');
        $options = ['' => get_string('select', 'local_studentprogress')];
        for ($i = 3; $i <= 7; $i++) {
            $options[$i] = (string)$i;
        }
        $mform->addElement('select', 'alertcount', '', $options);
        $mform->addRule('alertcount', null, 'required', null, 'client');
        $mform->setType('alertcount', PARAM_INT);

        // Contenedor de inputs dinámicos (generado por JS)
        $mform->addElement('html', '<div class="alerts-container" id="alerts-container"></div>');

        // Botón de enviar (estilizado)
        $this->add_action_buttons();
        $mform->addElement('html', '</div>'); // Cierra config-container

        // Script JS para inputs dinámicos
        $mform->addElement('html', '
        <script>
        function renderInputs() {
            const container = document.getElementById("alerts-container");
            container.innerHTML = "";

            const count = parseInt(document.querySelector("select[name=\'alertcount\']").value);
            if (!isNaN(count)) {
                for (let i = 1; i <= count; i++) {
                    const wrapper = document.createElement("div");
                    wrapper.style.display = "flex";
                    wrapper.style.alignItems = "center";
                    wrapper.style.marginBottom = "10px";
                    wrapper.style.gap = "10px";

                    const input = document.createElement("input");
                    input.type = "text";
                    input.name = "alert_" + i;
                    input.placeholder = "Mensaje " + i;
                    input.required = true;
                    input.style.flex = "1";

                    const range = document.createElement("div");
                    range.style.padding = "8px 12px";
                    range.style.backgroundColor = "#cce5ff";
                    range.style.border = "1px solid #007bff";
                    range.style.borderRadius = "8px";
                    range.style.fontSize = "0.9em";
                    range.style.whiteSpace = "nowrap";
                    range.style.minWidth = "120px"; // 🔹 tamaño fijo
                    range.style.textAlign = "center"; // 🔹 centra texto

                    // Calcular rangos
                    let min = 0;
                    let max = 100;
                    if (count === 1) {
                        range.textContent = "0% - 100%";
                    } else if (i === count) {
                        range.textContent = "100%";
                    } else {
                        min = ((i - 1) * (100 / count)).toFixed(2);
                        max = (i * (100 / count) - 0.01).toFixed(2);
                        range.textContent = `${min}% - ${max}%`;
                    }

                    wrapper.appendChild(input);
                    wrapper.appendChild(range);
                    container.appendChild(wrapper);
                }
            }
        }

        document.addEventListener("DOMContentLoaded", () => {
            const select = document.querySelector("select[name=\'alertcount\']");
            select.addEventListener("change", renderInputs);

            // Renderiza al cargar si hay valor
            if (select.value) {
                renderInputs();
            }
        });
        </script>');
    }

    public function process_data($data) {
        global $DB;

        $courseid = $data->id;
        $duration = $data->duration;
        $alertcount = $data->alertcount;

        try {
            // Guardar duración
            $DB->delete_records('course_duration_plg', ['id_course' => $courseid]);
            $DB->insert_record('course_duration_plg', [
                'id_course' => $courseid,
                'duration' => $duration
            ]);

            // Guardar alertas
            $DB->delete_records('course_alerts_plg', ['id_course' => $courseid]);
            debugging("Contenido de \$_POST:");
            debugging(print_r($_POST, true));
            for ($i = 1; $i <= $alertcount; $i++) {
                $alertfield = "alert_$i";
                $alerttext = optional_param($alertfield, '', PARAM_TEXT);
                if (!empty($alerttext)) {
                    $DB->insert_record('course_alerts_plg', [
                        'id_course' => $courseid,
                        'alert' => $alerttext
                    ]);
                }
            }
            return true;
            die('Guardado completado');
        } catch (\Exception $e) {
            debugging("Error saving config: " . $e->getMessage());
            return false;
        }
    }
}
