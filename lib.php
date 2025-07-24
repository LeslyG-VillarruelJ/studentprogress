<?php
// File: local/studentprogress/lib.php

defined('MOODLE_INTERNAL') || die();

// Verifica y crea las tablas necesarias para el plugin

function local_studentprogress_check_tables()
{
    global $DB;

    $dbman = $DB->get_manager();

    // 1. Tabla learning_type_plg
    if (!$dbman->table_exists('learning_type_plg')) {
        $DB->execute("CREATE TABLE {learning_type_plg} (
            id INT(10) NOT NULL AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Insertar datos iniciales
        $DB->insert_records('learning_type_plg', [
            (object)['name' => 'Visual'],
            (object)['name' => 'Kinestésico'],
            (object)['name' => 'Auditivo'],
            (object)['name' => 'Lectura']
        ]);
    }

    // 2. Tabla user_learning_plg
    if (!$dbman->table_exists('user_learning_plg')) {
        $DB->execute("CREATE TABLE {user_learning_plg} (
            id BIGINT(10) NOT NULL AUTO_INCREMENT,
            id_user BIGINT(10) NOT NULL COMMENT 'ID del usuario', 
            id_learning INT(10) NOT NULL COMMENT 'ID del tipo de aprendizaje',  
            timecreated BIGINT(10) NOT NULL COMMENT 'Fecha de creación en timestamp',
            PRIMARY KEY (id),
            FOREIGN KEY (id_user) REFERENCES {user}(id) ON DELETE CASCADE,  
            FOREIGN KEY (id_learning) REFERENCES {learning_type_plg}(id) ON DELETE CASCADE  
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    // 4. Tabla learning_course_module_plg
    if (!$dbman->table_exists('learning_course_module_plg')) {
    $DB->execute("CREATE TABLE {learning_course_module_plg} (
        id BIGINT(10) NOT NULL AUTO_INCREMENT,
        id_course_modules BIGINT(10) NOT NULL COMMENT 'ID del módulo del curso',
        id_learning INT(10) NOT NULL COMMENT 'ID del tipo de aprendizaje',       
        status TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Estado (0=inactivo, 1=activo)',
        timemodified TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha de modificación',
        PRIMARY KEY (id),
        UNIQUE KEY uq_resource_learning_type (id_course_modules, id_learning),  
        FOREIGN KEY (id_course_modules)                                         
            REFERENCES {course_modules}(id) ON DELETE CASCADE,  
        FOREIGN KEY (id_learning)                                               
            REFERENCES {learning_type_plg}(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Relación entre módulos del curso y tipos de aprendizaje'");
}

    // 4. Tabla user_learning_module_plg
    if (!$dbman->table_exists('user_learning_module_plg')) {
    $DB->execute("CREATE TABLE {user_learning_module_plg} (
        id BIGINT(10) NOT NULL AUTO_INCREMENT,
        id_user BIGINT(10) NOT NULL,
        id_learning_course_module BIGINT(10) NOT NULL,  
        timecreated BIGINT(10) NOT NULL,
        PRIMARY KEY (id),
        INDEX (id_user),
        INDEX (id_learning_course_module),
        FOREIGN KEY (id_user) REFERENCES {user}(id) ON UPDATE CASCADE ON DELETE RESTRICT,
        FOREIGN KEY (id_learning_course_module) REFERENCES {course_modules}(id) ON UPDATE CASCADE ON DELETE RESTRICT  
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 5. Tabla course_duration_plg
    if (!$dbman->table_exists('course_duration_plg')) {
        $DB->execute("CREATE TABLE {course_duration_plg} (
            id INT(10) NOT NULL AUTO_INCREMENT,
            id_course INT(10) NOT NULL,
            duration INT(10) NOT NULL,
            PRIMARY KEY (id),
            INDEX idx_id_course (id_course)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    // 6. Tabla course_alerts_plg
    if (!$dbman->table_exists('course_alerts_plg')) {
        $DB->execute("CREATE TABLE {course_alerts_plg} (
            id INT(10) NOT NULL AUTO_INCREMENT,
            id_course INT(10) NOT NULL,
            alert VARCHAR(255) NOT NULL DEFAULT '',
            PRIMARY KEY (id),
            INDEX idx_id_course (id_course)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}

/**
 * Agrega el enlace al plugin en la navegación del curso.
 *
 * @param navigation_node $navigation
 * @param stdClass $course
 * @param context_course $context
 */
function local_studentprogress_extend_navigation_course($navigation, $course, $context)
{
    global $PAGE, $USER;

    // Verificar tablas plugin
    local_studentprogress_check_tables();

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
