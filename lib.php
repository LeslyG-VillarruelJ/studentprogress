<?php
// File: local/studentprogress/lib.php

defined('MOODLE_INTERNAL') || die();

/**
 * Agrega el enlace al plugin en la navegación del curso.
 *
 * @param navigation_node $navigation
 * @param stdClass $course
 * @param context_course $context
 */
function local_studentprogress_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('local/studentprogress:view', $context)) {
        $url = new moodle_url('/local/studentprogress/index.php', ['id' => $course->id]);
        $navigation->add(
            get_string('pluginname', 'local_studentprogress'),
            $url,
            navigation_node::TYPE_CUSTOM,
            null,
            null,
            new pix_icon('i/report', '')
        );
    }
}
