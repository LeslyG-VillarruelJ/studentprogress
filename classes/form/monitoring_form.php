<?php

namespace local_studentprogress\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

use local_studentprogress\service\progress_service;

class monitoring_form extends \moodleform
{

    public function definition()
    {
        global $DB;

        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];

        $mform->addElement('hidden', 'id', $courseid);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'tab', 'monitoring');
        $mform->setType('tab', PARAM_ALPHA);

        // Instanciar servicio
        $service = new progress_service($courseid);

        // Obtener datos
        $students = $service->get_students();
        $sections = $service->get_visible_sections();
        $statuses = $service->get_progress_by_section($students, $sections);
        $progressresources = $service->get_student_progress_resources($students, $sections);
        $overallprogress = min(100, max(0, round($service->get_course_progress_resources($students, $sections))));

        // Script para filtrar
        $html = '<script>
        function filtrar() {
            const selectedSection = document.getElementById("tema-section").value;
            const selectedStudent = document.getElementById("tema-student").value;

            const rows = document.querySelectorAll(".tabla-progreso tbody tr");
            rows.forEach(row => {
                const studentId = row.getAttribute("data-student-id");
                const studentMatch = (selectedStudent === "all" || selectedStudent === studentId);
                row.style.display = studentMatch ? "" : "none";
            });

            const headerCells = document.querySelectorAll(".tabla-progreso thead th[data-section-id]");
            headerCells.forEach(th => {
                const sectionId = th.getAttribute("data-section-id");
                th.style.display = (selectedSection === "all" || selectedSection === sectionId) ? "" : "none";
            });

            rows.forEach(row => {
                const cells = row.querySelectorAll("td[data-section-id]");
                cells.forEach(td => {
                    const sectionId = td.getAttribute("data-section-id");
                    td.style.display = (selectedSection === "all" || selectedSection === sectionId) ? "" : "none";
                });
            });
        }
        </script>';

        // HTML de la interfaz
        $html .= '
        <div class="monitoreo-container">
            <h2 class="titulo">' . get_string('monitoring', 'local_studentprogress') . '</h2>

            <div class="progreso-general">
                <strong style="font-size: 16px;">' . get_string('overallprogress', 'local_studentprogress') . '</strong>
                <div class="barra-progreso">
                    <div class="progreso" style="width: ' . $overallprogress . '%;"></div>
                </div>
                <span class="porcentaje">' . $overallprogress . '%</span>
            </div>

            <div class="leyenda-estados">
                <strong style="font-size: 16px;">' . get_string('states', 'local_studentprogress') . '</strong><br/><br/>
                <div class="estados">
                    <span><span class="circulo azul"></span> ' . get_string('noregistered', 'local_studentprogress') . '</span>
                    <span><span class="circulo amarillo"></span> ' . get_string('inprogress', 'local_studentprogress') . '</span>
                    <span><span class="circulo verde"></span> ' . get_string('resolved', 'local_studentprogress') . '</span>
                    <span><span class="circulo rojo"></span> ' . get_string('unresolved', 'local_studentprogress') . '</span>
                </div>
            </div>

            <div class="filtro">
                <label for="tema-section">' . get_string('topic', 'local_studentprogress') . '</label>
                <select id="tema-section">
                    <option value="all">' . get_string('all', 'local_studentprogress') . '</option>';
        foreach ($sections as $sec) {
            $html .= '<option value="' . $sec->id . '">' . format_string($sec->name) . '</option>';
        }
        $html .= '</select>

                <label for="tema-student">' . get_string('student', 'local_studentprogress') . '</label>
                <select id="tema-student">
                    <option value="all">' . get_string('all', 'local_studentprogress') . '</option>';
        foreach ($students as $stu) {
            $html .= '<option value="' . $stu->id . '">' . fullname($stu) . '</option>';
        }
        $html .= '</select>

                <button type="button" onclick="filtrar()">' . get_string('filter', 'local_studentprogress') . '</button>
            </div>';

        $html .= '<div class="tabla-scroll">
                    <table class="tabla-progreso">
                        <thead><tr>
                            <th>' . get_string('student', 'local_studentprogress') . '</th>
                            <th>' . get_string('resourceprogress', 'local_studentprogress') . '</th>';
        foreach ($sections as $sec) {
            $html .= '<th data-section-id="' . $sec->id . '">' . format_string($sec->name) . '</th>';
        }
        $html .=     '</tr></thead>
                        <tbody>';
        foreach ($students as $student) {
            $html .= '<tr data-student-id="' . $student->id . '">
                                <td style="width: 500px;">' . fullname($student) . '</td>';

            $progress = $progressresources[$student->id] ?? 0;

            $html .= '<td style="width: 300px;">
                        <div class="progreso-mini">
                            <div class="barra-mini">
                                <div class="progreso" style="width:' . $progress . '%;"></div>
                            </div>
                            <span class="porcentaje">' . $progress . '%</span>
                        </div>
                    </td>';

            foreach ($sections as $sec) {
                $color = $statuses[$student->id][$sec->id];
                $html .= '<td data-section-id="' . $sec->id . '" style="width: 300px;">
                                    <span class="circulo ' . $color . '"></span>
                                </td>';
            }

            $html .= '</tr>';
        }
        $html .=     '</tbody>
                    </table>
                </div>
            </div>';

        // Mostrar el HTML en el formulario
        $mform->addElement('html', $html);
    }
}
