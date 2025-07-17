<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Instala las tablas necesarias para el plugin studentprogress.
 */
function xmldb_local_studentprogress_install() {
    global $DB;

    $dbman = $DB->get_manager();

    // Tabla course_duration_plg
    if (!$dbman->table_exists('course_duration_plg')) {
        $table = new xmldb_table('course_duration_plg');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('id_course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('duration', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('id_course_ix', XMLDB_INDEX_NOTUNIQUE, ['id_course']);

        $dbman->create_table($table);
    }

    // Tabla course_alerts_plg
    if (!$dbman->table_exists('course_alerts_plg')) {
        $table = new xmldb_table('course_alerts_plg');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('id_course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('alert', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('id_course_ix', XMLDB_INDEX_NOTUNIQUE, ['id_course']);

        $dbman->create_table($table);
    }

    // Tabla learning_type_plg
    if (!$dbman->table_exists('learning_type_plg')) {
        $table = new xmldb_table('learning_type_plg');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, '');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        $dbman->create_table($table);
    }

    // Tabla learning_course_module_plg
    if (!$dbman->table_exists('learning_course_module_plg')) {
        $table = new xmldb_table('learning_course_module_plg');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('id_course_module', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('id_learning', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('idx_course_module', XMLDB_INDEX_NOTUNIQUE, ['id_course_module']);
        $table->add_index('idx_learning', XMLDB_INDEX_NOTUNIQUE, ['id_learning']);

        $table->add_foreign_key('fk_course_module', 'id_course_module', 'course_modules', 'id', XMLDB_KEY_FOREIGN, 'CASCADE', 'RESTRICT');
        $table->add_foreign_key('fk_learning_type', 'id_learning', 'learning_type_plg', 'id', XMLDB_KEY_FOREIGN, 'CASCADE', 'RESTRICT');

        $dbman->create_table($table);
    }

    // Tabla user_learning_plg
    if (!$dbman->table_exists('user_learning_plg')) {
        $table = new xmldb_table('user_learning_plg');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('id_user', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('id_learning', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('idx_user', XMLDB_INDEX_NOTUNIQUE, ['id_user']);
        $table->add_index('idx_learning', XMLDB_INDEX_NOTUNIQUE, ['id_learning']);

        $table->add_foreign_key('fk_user', 'id_user', 'user', 'id', XMLDB_KEY_FOREIGN, 'CASCADE', 'RESTRICT');
        $table->add_foreign_key('fk_learning', 'id_learning', 'learning_type_plg', 'id', XMLDB_KEY_FOREIGN, 'CASCADE', 'RESTRICT');

        $dbman->create_table($table);
    }

    // Tabla user_learning_module_plg
    if (!$dbman->table_exists('user_learning_module_plg')) {
        $table = new xmldb_table('user_learning_module_plg');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('id_user_learning', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('id_learning_course_module', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('idx_user_learning', XMLDB_INDEX_NOTUNIQUE, ['id']);
        $table->add_index('idx_learning_module', XMLDB_INDEX_NOTUNIQUE, ['id_learning_course_module']);

        $table->add_foreign_key('fk_user_learning', 'id_user_learning', 'user_learning_plg', 'id', XMLDB_KEY_FOREIGN, 'CASCADE', 'RESTRICT');
        $table->add_foreign_key('fk_learning_module', 'id_learning_course_module', 'learning_course_module_plg', 'id', XMLDB_KEY_FOREIGN, 'CASCADE', 'RESTRICT');

        $dbman->create_table($table);
    }

    // Tabla user_section_grades_plg
    if (!$dbman->table_exists('user_sections_grades_plg')) {
        $table = new xmldb_table('user_section_grades_plg');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('id_section', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('id_user_learning', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('grade', XMLDB_TYPE_NUMBER, '5,2', null, XMLDB_NOTNULL, null, 0);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('idx_section', XMLDB_INDEX_NOTUNIQUE, ['id_section']);
        $table->add_index('idx_user_learning', XMLDB_INDEX_NOTUNIQUE, ['id_user_learning']);

        $table->add_foreign_key('fk_section', 'id_section', 'course_sections', 'id', XMLDB_KEY_FOREIGN, 'RESTRICT', 'RESTRICT');
        $table->add_foreign_key('fk_user_learning_gr', 'id_user_learning', 'user_learning_plg', 'id', XMLDB_KEY_FOREIGN, 'CASCADE', 'RESTRICT');

        $dbman->create_table($table);
    }

    // Tabla user_section_status_plg
    if (!$dbman->table_exists('user_sections_plg')) {
        $table = new xmldb_table('user_section_status_plg');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('id_section', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('id_user_learning', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, '');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('idx_section', XMLDB_INDEX_NOTUNIQUE, ['id_section']);
        $table->add_index('idx_user_learning', XMLDB_INDEX_NOTUNIQUE, ['id_user_learning']);

        $table->add_foreign_key('fk_section_status', 'id_section', 'course_sections', 'id', XMLDB_KEY_FOREIGN, 'RESTRICT', 'RESTRICT');
        $table->add_foreign_key('fk_user_learning_status', 'id_user_learning', 'user_learning_plg', 'id', XMLDB_KEY_FOREIGN, 'CASCADE', 'RESTRICT');

        $dbman->create_table($table);
    }
}
