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

    public function get_progress(array $students, array $sections): array
    {
        global $DB;

        $progress = [];

        foreach ($students as $s) {
            foreach ($sections as $sec) {
                try {
                    $userid = $s->id;
                    $sectionid = $sec->id;
                    $sql = "SELECT 
                                (SELECT COUNT(*) 
                                FROM {learning_course_module_plg} lcm
                                JOIN {course_modules} cm ON cm.id = lcm.id_course_module
                                JOIN {user_learning_module_plg} ulcm ON lcm.id_learning = ulcm.id_learning_course_module
                                JOIN {user_learning_plg} ul ON ulcm.id_user_learning = ul.id
                                JOIN {user u ON ul.id_user} = u.id
                                WHERE ulcm.id_user_learning = (
                                    SELECT ul.id 
                                    FROM {user_learning_plg} ul 
                                    JOIN {user} u ON ul.id_user = u.id 
                                    WHERE u.id = :userid1
                                )
                                AND cm.course = :courseid1 AND cm.section = :sectionid) AS total_asignados,

                                (SELECT COUNT(*) 
                                FROM {course_modules_completion} cmc
                                JOIN {learning_course_module_plg} lcm ON lcm.id_course_module = cmc.coursemoduleid
                                JOIN {course_modules} cm ON cm.id = lcm.id_course_module
                                JOIN {user_learning_module_plg} ulcm ON lcm.id_learning = ulcm.id_learning_course_module
                                JOIN {user_learning_plg} ul ON ulcm.id_user_learning = ul.id
                                JOIN {user} u ON ul.id_user = u.id
                                WHERE cmc.userid = :userid2 AND cm.course = :courseid2 AND cm.section = :sectionid AND cmc.completionstate = 1) AS total_completados
                            ";

                    $params = [
                        'userid1' => $userid,
                        'courseid1' => $this->courseid,
                        'userid2' => $userid,
                        'courseid2' => $this->courseid,
                        'sectionid' => $sectionid
                    ];

                    $studentprogress = $DB->get_record_sql($sql, $params);

                    $finishresources = (int)($studentprogress->total_completados ?? 0);
                    $totalresources = (int)($studentprogress->total_asignados ?? 0);

                    if ($finishresources === 0) {
                        $progress[$userid][$sectionid] = 'por resolver';
                    } else if ($finishresources < $totalresources) {
                        $progress[$userid][$sectionid] = 'en progreso';
                    } else {
                        $progress[$userid][$sectionid] = 'resuelto';
                    }
                } catch (\dml_exception $e) {
                    debugging("Error en get_progress(): " . $e->getMessage(), DEBUG_DEVELOPER);
                    return [];
                }
            }
        }

        return $progress;
    }

    public static function map_status_to_color(string $status): string
    {
        switch (strtolower($status)) {
            case 'resuelto':
                return 'verde';
            case 'en progreso':
                return 'amarillo';
            case 'por resolver':
                return 'rojo';
            default:
                return 'azul';
        }
    }

    public function get_course_progress(array $students): float
    {
        global $DB;

        $studentcount = count($students);
        if ($studentcount === 0) {
            return 0.0;
        }

        $progress = [];

        foreach ($students as $s) {
            try {
                $userid = $s->id;
                $sql = "SELECT 
                            (SELECT COUNT(*) 
                             FROM {learning_course_module_plg} lcm
                             JOIN {course_modules} cm ON cm.id = lcm.id_course_module
                             JOIN {user_learning_module_plg} ulcm ON lcm.id_learning = ulcm.id_learning_course_module
                             JOIN {user_learning_plg} ul ON ulcm.id_user_learning = ul.id
                             JOIN {user u ON ul.id_user} = u.id
                             WHERE ulcm.id_user_learning = (
                                 SELECT ul.id 
                                 FROM {user_learning_plg} ul 
                                 JOIN {user} u ON ul.id_user = u.id 
                                 WHERE u.id = :userid1
                             )
                             AND cm.course = :courseid1) AS total_asignados,

                            (SELECT COUNT(*) 
                             FROM {course_modules_completion} cmc
                             JOIN {learning_course_module_plg} lcm ON lcm.id_course_module = cmc.coursemoduleid
                             JOIN {course_modules} cm ON cm.id = lcm.id_course_module
                             JOIN {user_learning_module_plg} ulcm ON lcm.id_learning = ulcm.id_learning_course_module
                             JOIN {user_learning_plg} ul ON ulcm.id_user_learning = ul.id
                             JOIN {user} u ON ul.id_user = u.id
                             WHERE cmc.userid = :userid2 AND cm.course = :courseid2 AND cmc.completionstate = 1) AS total_completados
                        ";

                $params = [
                    'userid1' => $userid,
                    'courseid1' => $this->courseid,
                    'userid2' => $userid,
                    'courseid2' => $this->courseid
                ];

                $studentprogress = $DB->get_record_sql($sql, $params);

                $finishresources = (int)($studentprogress->total_completados ?? 0);
                $totalresources = (int)($studentprogress->total_asignados ?? 0);

                $pro = ($totalresources > 0) ? round(($finishresources * 100) / $totalresources) : 0;

                $progress[] = $pro;
            } catch (\dml_exception $e) {
                debugging("Error en get_course_progress(): " . $e->getMessage(), DEBUG_DEVELOPER);
                $progress[] = 0;
            }
        }

        return array_sum($progress) / $studentcount;
    }

    public function get_student_progress_resources(array $students): array
    {
        global $DB;

        $progressresources = [];

        foreach ($students as $s) {
            try {
                $userid = $s->id;

                $sql = "SELECT 
                            (SELECT COUNT(*) 
                             FROM {learning_course_module_plg} lcm
                             JOIN {course_modules} cm ON cm.id = lcm.id_course_module
                             JOIN {user_learning_module_plg} ulcm ON lcm.id_learning = ulcm.id_learning_course_module
                             JOIN {user_learning_plg} ul ON ulcm.id_user_learning = ul.id
                             JOIN {user u ON ul.id_user} = u.id
                             WHERE ulcm.id_user_learning = (
                                 SELECT ul.id 
                                 FROM {user_learning_plg} ul 
                                 JOIN {user} u ON ul.id_user = u.id 
                                 WHERE u.id = :userid1
                             )
                             AND cm.course = :courseid1) AS total_asignados,

                            (SELECT COUNT(*) 
                             FROM {course_modules_completion} cmc
                             JOIN {learning_course_module_plg} lcm ON lcm.id_course_module = cmc.coursemoduleid
                             JOIN {course_modules} cm ON cm.id = lcm.id_course_module
                             JOIN {user_learning_module_plg} ulcm ON lcm.id_learning = ulcm.id_learning_course_module
                             JOIN {user_learning_plg} ul ON ulcm.id_user_learning = ul.id
                             JOIN {user} u ON ul.id_user = u.id
                             WHERE cmc.userid = :userid2 AND cm.course = :courseid2 AND cmc.completionstate = 1) AS total_completados
                        ";

                $params = [
                    'userid1' => $userid,
                    'courseid1' => $this->courseid,
                    'userid2' => $userid,
                    'courseid2' => $this->courseid
                ];

                $studentprogress = $DB->get_record_sql($sql, $params);

                $finishresources = (int)($studentprogress->total_completados ?? 0);
                $totalresources = (int)($studentprogress->total_asignados ?? 0);

                $pro = ($totalresources > 0) ? round(($finishresources * 100) / $totalresources) : 0;

                $progressresources[$userid] = $pro;
            } catch (\dml_exception $e) {
                debugging("Error en get_student_progress_resources(): " . $e->getMessage(), DEBUG_DEVELOPER);
                $progressresources[$s->id] = 0;
            }
        }

        return $progressresources;
    }
}
