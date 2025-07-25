<?php

namespace local_studentprogress\service;

defined('MOODLE_INTERNAL') || die();

class progress_service
{
    private $courseid;
    private $context;

    public function __construct(int $courseid)
    {
        $this->courseid = $courseid;
        $this->context = \context_course::instance($courseid);
    }

    public function get_visible_sections(): array
    {
        global $DB;

        try {
            $sections = $DB->get_records('course_sections', ['course' => $this->courseid]);
            return array_filter($sections, fn($s) => $s->section > 0 && !empty($s->name));
        } catch (\dml_exception $e) {
            debugging("Error en get_visible_sections(): " . $e->getMessage(), DEBUG_DEVELOPER);
            return [];
        }
    }

    public function get_students(): array
    {
        try {
            $users = get_enrolled_users($this->context, '', 0, 'u.id, u.firstname, u.lastname');
            return array_filter($users, fn($user) => !has_capability('moodle/course:update', $this->context, $user));
        } catch (\Exception $e) {
            debugging("Error en get_students(): " . $e->getMessage(), DEBUG_DEVELOPER);
            return [];
        }
    }

    private function get_section_status($userid, $sectionid)
    {
        global $DB;

        try {
            $sql1 = "SELECT 
                        (SELECT COUNT(*)
                             FROM {course_modules} cm
                             JOIN {user_learning_module_plg} ulcm ON cm.id = ulcm.id_learning_course_module
                             JOIN {user} u ON ulcm.id_user = u.id
                             WHERE ulcm.id_user = :userid1 AND cm.course = :courseid1 AND cm.section = :sectionid1
                        ) AS total_asignados,

                        (SELECT COUNT(*)
                             FROM {course_modules_completion} cmc
                             JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                             JOIN {user_learning_module_plg} ulcm ON cmc.coursemoduleid = ulcm.id_learning_course_module
                             JOIN {user} u ON ulcm.id_user = u.id
                             WHERE u.id = :userid2 AND cmc.userid = :userid3 AND cm.course = 3 AND cmc.completionstate = 1 AND cm.section = :sectionid2
                        ) AS total_completados;;
                    ";

            $params1 = [
                'userid1' => $userid,
                'courseid1' => $this->courseid,
                'userid2' => $userid,
                'courseid2' => $this->courseid,
                'sectionid1' => $sectionid,
                'userid3' => $userid,
                'sectionid2' => $sectionid
            ];

            debugging('Antes de la consulta', DEBUG_DEVELOPER);

            $sectionprogress = $DB->get_record_sql($sql1, $params1);

            $finishresources = isset($sectionprogress->total_completados) ? (int)$sectionprogress->total_completados : 0;
            $totalresources = isset($sectionprogress->total_asignados) ? (int)$sectionprogress->total_asignados : 0;

            return [$finishresources, $totalresources];
        } catch (Exception $e) {
            debugging('Error en get_user_sections(): ' . $e->getMessage(), DEBUG_DEVELOPER);
            return null;
        }
    }

    public function get_progress_by_section(array $students, array $sections): array
    {
        global $DB;

        $progress = [];
        $color = 'gris';

        foreach ($students as $s) {
            foreach ($sections as $sec) {
                try {
                    $userid = $s->id;
                    $sectionid = $sec->id;
                    list($finishresources, $totalresources) = $this->get_section_status($userid, $sectionid);

                    if ($totalresources === 0) {
                        $color  = 'azul';
                    } else {
                        if ($totalresources === $finishresources) {
                            $color  = 'verde';
                        } else if ($totalresources > $finishresources && $finishresources >= 1) {
                            $color  = 'amarillo';
                        } else {
                            $color  = 'rojo';
                        }
                    }

                    $progress[$userid][$sectionid] = $color;
                } catch (\dml_exception $e) {
                    debugging("Error en get_progress(): " . $e->getMessage(), DEBUG_DEVELOPER);
                    return [];
                }
            }
        }

        return $progress;
    }

    public function get_student_progress_resources(array $students, array $sections): array
    {
        global $DB;

        $progressresources = [];

        foreach ($students as $s) {

            try {
                $addfinishresources = 0;
                $addtotalresources = 0;

                foreach ($sections as $sec) {
                    $userid = $s->id;
                    $sectionid = $sec->id;
                    list($finishresources, $totalresources) = $this->get_section_status($userid, $sectionid);

                    $addfinishresources += $finishresources;
                    $addtotalresources += $totalresources;
                }

                $progressresources[$userid] = ($addtotalresources > 0) ? round(($addfinishresources * 100) / $addtotalresources) : 0;
            } catch (\dml_exception $e) {
                debugging("Error en get_student_progress_resources(): " . $e->getMessage(), DEBUG_DEVELOPER);
                $progressresources[$s->id] = 0;
            }
        }
        return $progressresources;
    }

    public function get_course_progress_resources(array $students, array $sections): float
    {
        global $DB;

        $studentcount = count($students);
        if ($studentcount === 0) {
            return 0.0;
        }

        $sumprogress = 0;


        try {
            $studentprogress = $this->get_student_progress_resources($students, $sections);

            foreach ($students as $s) {
                $userid = $s->id;
                $sumprogress += $studentprogress[$s->id] ?? 0;
            }
        } catch (\dml_exception $e) {
            debugging("Error en get_course_progress(): " . $e->getMessage(), DEBUG_DEVELOPER);
        }

        return $sumprogress / $studentcount;
    }
}
