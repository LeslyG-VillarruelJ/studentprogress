<?php
// File: local/studentprogress/classes/service/progress_service.php

namespace local_studentprogress\service;

defined('MOODLE_INTERNAL') || die();

class progress_service {
    private $courseid;
    private $context;

    public function __construct(int $courseid) {
        global $DB;
        $this->courseid = $courseid;
        $this->context = \context_course::instance($courseid);
    }

    /**
     * Get visible course sections with names.
     *
     * @return array
     */
    public function get_visible_sections(): array {
        global $DB;

        $sections = $DB->get_records('course_sections', ['course' => $this->courseid]);
        return array_filter($sections, function($s) {
            return $s->section > 0 && !empty($s->name);
        });
    }

    /**
     * Get enrolled students (excluding teachers/admins).
     *
     * @return array
     */
    public function get_students(): array {
        $users = get_enrolled_users($this->context, '', 0, 'u.id, u.firstname, u.lastname');

        // Filter only students (no manage capability)
        return array_filter($users, function($user) {
            return !has_capability('moodle/course:update', $this->context, $user);
        });
    }

    /**
     * Get student progress data organized by [userid][sectionid] = status
     *
     * @return array
     */
    public function get_progress(): array {
        global $DB;

        $sql = "
            SELECT 
                s.id AS section_id,
                u.id AS userid,
                us.status AS status
            FROM {course} c
            JOIN {course_sections} s ON c.id = s.course
            JOIN {user_sections_plg} us ON s.id = us.id_section
            JOIN {user_learning_plg} ul ON us.id_user_learning = ul.id_user_learning
            JOIN {user} u ON ul.id_user = u.id
            WHERE c.id = :courseid
        ";

        $records = $DB->get_records_sql($sql, ['courseid' => $this->courseid]);

        $progress = [];
        foreach ($records as $r) {
            $progress[$r->userid][$r->section_id] = $r->status;
        }

        return $progress;
    }

    /**
     * Maps status string to color class.
     *
     * @param string $status
     * @return string
     */
    public static function map_status_to_color(string $status): string {
        switch (strtolower($status)) {
            case 'resuelto': return 'verde';
            case 'en progreso': return 'amarillo';
            case 'por resolver': return 'rojo';
            default: return 'azul';
        }
    }

    public function get_course_progress(array $students): float {
        global $DB;

        $studentcount = count($students);
        if ($studentcount === 0) {
            return 0.0;
        }

        $progress = [];

        foreach ($students as $s) {
            $userid = $s->id;

            $sql = "SELECT 
                        (SELECT COUNT(*) 
                        FROM {learning_course_module_plg} lcm
                        JOIN {course_modules} cm ON cm.id = lcm.id_course_module
                        JOIN {user_learning_module_plg} ulcm ON lcm.id_lcm_learning = ulcm.id_learning_course_module
                        JOIN {user_learning_plg} ul ON ulcm.id_user_learning = ul.id_user_learning
                        JOIN {user} u ON ul.id_user = u.id
                        WHERE ulcm.id_user_learning = (SELECT ul.id_user_learning FROM {user_learning_plg} ul JOIN {user} u ON ul.id_user = u.id WHERE u.id = :userid1) 
                        AND cm.course = :courseid1) AS total_asignados,

                        (SELECT COUNT(*) 
                        FROM {course_modules_completion} cmc
                        JOIN {learning_course_module_plg} lcm ON lcm.id_course_module = cmc.coursemoduleid
                        JOIN {course_modules} cm ON cm.id = lcm.id_course_module
                        JOIN {user_learning_module_plg} ulcm ON lcm.id_lcm_learning = ulcm.id_learning_course_module
                        JOIN {user_learning_plg} ul ON ulcm.id_user_learning = ul.id_user_learning
                        JOIN {user} u ON ul.id_user = u.id
                        WHERE cmc.userid = :userid2 AND cm.course = :courseid2 AND cmc.completionstate = 1) AS total_completados";

            $params = [
                'userid1' => $userid,
                'courseid1' => $this->courseid,
                'userid2' => $userid,
                'courseid2' => $this->courseid
            ];

            $studentprogress = $DB->get_record_sql($sql, $params);

            $finishresources = isset($studentprogress->total_completados) ? (int)$studentprogress->total_completados : 0;
            $totalresources = isset($studentprogress->total_asignados) ? (int)$studentprogress->total_asignados : 0;

            $pro = ($totalresources > 0) ? round(($finishresources * 100) / $totalresources) : 0;

            $progress[] = $pro;
        }

        $totalprogress = array_sum($progress) / $studentcount;

        return $totalprogress;
    }

    public function get_student_progress_resources(array $students): array {
        global $DB;

        $progressresources = [];

        foreach ($students as $s) {
            $userid = $s->id;

            $sql = "SELECT 
                        (SELECT COUNT(*) 
                        FROM {learning_course_module_plg} lcm
                        JOIN {course_modules} cm ON cm.id = lcm.id_course_module
                        JOIN {user_learning_module_plg} ulcm ON lcm.id_lcm_learning = ulcm.id_learning_course_module
                        JOIN {user_learning_plg} ul ON ulcm.id_user_learning = ul.id_user_learning
                        JOIN {user} u ON ul.id_user = u.id
                        WHERE ulcm.id_user_learning = (SELECT ul.id_user_learning FROM {user_learning_plg} ul JOIN {user} u ON ul.id_user = u.id WHERE u.id = :userid1) 
                        AND cm.course = :courseid1) AS total_asignados,

                        (SELECT COUNT(*) 
                        FROM {course_modules_completion} cmc
                        JOIN {learning_course_module_plg} lcm ON lcm.id_course_module = cmc.coursemoduleid
                        JOIN {course_modules} cm ON cm.id = lcm.id_course_module
                        JOIN {user_learning_module_plg} ulcm ON lcm.id_lcm_learning = ulcm.id_learning_course_module
                        JOIN {user_learning_plg} ul ON ulcm.id_user_learning = ul.id_user_learning
                        JOIN {user} u ON ul.id_user = u.id
                        WHERE cmc.userid = :userid2 AND cm.course = :courseid2 AND cmc.completionstate = 1) AS total_completados";

            $params = [
                'userid1' => $userid,
                'courseid1' => $this->courseid,
                'userid2' => $userid,
                'courseid2' => $this->courseid
            ];

            $studentprogress = $DB->get_record_sql($sql, $params);

            $finishresources = isset($studentprogress->total_completados) ? (int)$studentprogress->total_completados : 0;
            $totalresources = isset($studentprogress->total_asignados) ? (int)$studentprogress->total_asignados : 0;

            $pro = ($totalresources > 0) ? round(($finishresources * 100) / $totalresources) : 0;

            $progressresources[$userid] = $pro;
        }

        return $progressresources;
    }
}
