<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: OpenClaw
Description: AI control center for Perfex CRM with agent staff onboarding, context chat, gateways, and MCP schema.
Version: 1.0.0
Requires at least: 2.3.*
Author: OpenClaw
*/

define('OPENCLAW_MODULE_NAME', 'openclaw');

$CI = &get_instance();
$CI->load->helper(OPENCLAW_MODULE_NAME . '/openclaw');

register_language_files(OPENCLAW_MODULE_NAME, [OPENCLAW_MODULE_NAME]);
register_activation_hook(OPENCLAW_MODULE_NAME, 'openclaw_activation_hook');
register_deactivation_hook(OPENCLAW_MODULE_NAME, 'openclaw_deactivation_hook');
register_uninstall_hook(OPENCLAW_MODULE_NAME, 'openclaw_uninstall_hook');
register_cron_task('openclaw_cron_heartbeat');

hooks()->add_action('admin_init', 'openclaw_admin_init');
hooks()->add_filter('api_permissions', 'openclaw_register_api_permissions');

// Native lifecycle hooks only.
hooks()->add_action('after_add_task', 'openclaw_on_task_created');
hooks()->add_action('after_update_task', 'openclaw_on_task_updated');
hooks()->add_action('task_status_changed', 'openclaw_on_task_status_changed');
hooks()->add_action('task_assignee_added', 'openclaw_on_task_assignee_added');
hooks()->add_action('task_comment_added', 'openclaw_on_task_comment_added');
hooks()->add_action('task_timer_started', 'openclaw_on_task_timer_started');

hooks()->add_action('after_add_project', 'openclaw_on_project_created');
hooks()->add_action('after_update_project', 'openclaw_on_project_updated');
hooks()->add_action('project_status_changed', 'openclaw_on_project_status_changed');
hooks()->add_action('after_project_staff_added_as_member', 'openclaw_on_project_member_added');

hooks()->add_action('staff_member_created', 'openclaw_on_staff_created');
hooks()->add_action('staff_member_updated', 'openclaw_on_staff_updated');

hooks()->add_action('before_task_description_section', 'openclaw_render_task_chat_panel');
hooks()->add_action('admin_project_overview_end_of_project_overview_right', 'openclaw_render_project_chat_summary');

function openclaw_activation_hook()
{
    $CI = &get_instance();
    $CI->load->database();

    openclaw_create_tables();
    openclaw_create_staff_role();
    openclaw_ensure_custom_fields();
    openclaw_ensure_module_options();
}

function openclaw_deactivation_hook()
{
    log_activity('OpenClaw module deactivated');
}

function openclaw_uninstall_hook()
{
    // Keep data by default to avoid accidental data loss.
    delete_option('openclaw_schema_version');
    delete_option('openclaw_cpanel_host');
    delete_option('openclaw_cpanel_username');
    delete_option('openclaw_cpanel_token');
    delete_option('openclaw_cpanel_domain');
    delete_option('openclaw_chat_poll_interval');
}

function openclaw_ensure_module_options()
{
    add_option('openclaw_schema_version', '1.0.0', 1);
    add_option('openclaw_chat_poll_interval', '5000', 1);
    add_option('openclaw_cpanel_host', '', 0);
    add_option('openclaw_cpanel_username', '', 0);
    add_option('openclaw_cpanel_token', '', 0);
    add_option('openclaw_cpanel_domain', '', 0);
}

function openclaw_create_tables()
{
    $CI = &get_instance();
    $charset = $CI->db->char_set;

    if (!$CI->db->table_exists(db_prefix() . 'openclaw_gateways')) {
        $CI->db->query('CREATE TABLE `' . db_prefix() . 'openclaw_gateways` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL,
            `endpoint_url` VARCHAR(500) NOT NULL,
            `api_key` VARCHAR(255) NOT NULL,
            `inbound_secret` VARCHAR(255) NOT NULL,
            `status` ENUM("active","inactive") NOT NULL DEFAULT "active",
            `last_heartbeat` DATETIME NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';');
    }

    if (!$CI->db->table_exists(db_prefix() . 'openclaw_gateway_agents')) {
        $CI->db->query('CREATE TABLE `' . db_prefix() . 'openclaw_gateway_agents` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `gateway_id` INT(11) NOT NULL,
            `external_agent_id` VARCHAR(255) NOT NULL,
            `external_agent_name` VARCHAR(255) NOT NULL,
            `staff_id` INT(11) NULL,
            `api_user_id` INT(11) NULL,
            `autonomy_tier` TINYINT(2) NOT NULL DEFAULT 0,
            `status` ENUM("discovered","onboarded","paused","archived") NOT NULL DEFAULT "discovered",
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            INDEX `idx_openclaw_gateway_id` (`gateway_id`),
            INDEX `idx_openclaw_staff_id` (`staff_id`),
            INDEX `idx_openclaw_external_agent_id` (`external_agent_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';');
    }

    if (!$CI->db->table_exists(db_prefix() . 'openclaw_chat_threads')) {
        $CI->db->query('CREATE TABLE `' . db_prefix() . 'openclaw_chat_threads` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `rel_type` VARCHAR(50) NOT NULL,
            `rel_id` INT(11) NULL,
            `gateway_id` INT(11) NULL,
            `title` VARCHAR(255) NOT NULL,
            `external_thread_id` VARCHAR(255) NULL,
            `status` ENUM("active","archived") NOT NULL DEFAULT "active",
            `created_by` INT(11) NOT NULL,
            `created_at` DATETIME NOT NULL,
            `last_message_at` DATETIME NULL,
            PRIMARY KEY (`id`),
            INDEX `idx_openclaw_thread_rel` (`rel_type`,`rel_id`),
            INDEX `idx_openclaw_thread_gateway` (`gateway_id`),
            INDEX `idx_openclaw_thread_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';');
    }

    if (!$CI->db->table_exists(db_prefix() . 'openclaw_chat_messages')) {
        $CI->db->query('CREATE TABLE `' . db_prefix() . 'openclaw_chat_messages` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `thread_id` INT(11) NOT NULL,
            `sender_type` ENUM("staff","agent","system") NOT NULL,
            `sender_id` INT(11) NULL,
            `instance_id` VARCHAR(255) NULL,
            `message` TEXT NOT NULL,
            `metadata` TEXT NULL,
            `external_message_id` VARCHAR(255) NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            INDEX `idx_openclaw_message_thread` (`thread_id`),
            INDEX `idx_openclaw_message_sender` (`sender_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';');
    }

    if (!$CI->db->table_exists(db_prefix() . 'openclaw_event_log')) {
        $CI->db->query('CREATE TABLE `' . db_prefix() . 'openclaw_event_log` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `event_type` VARCHAR(100) NOT NULL,
            `source` ENUM("perfex","agent","system") NOT NULL DEFAULT "system",
            `staff_id` INT(11) NULL,
            `rel_type` VARCHAR(50) NULL,
            `rel_id` INT(11) NULL,
            `gateway_id` INT(11) NULL,
            `payload` TEXT NULL,
            `response_code` INT(11) NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            INDEX `idx_openclaw_event_type` (`event_type`),
            INDEX `idx_openclaw_event_rel` (`rel_type`,`rel_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';');
    }
}

function openclaw_create_staff_role()
{
    $CI = &get_instance();
    $CI->db->where('name', 'AI Agent');
    $existing = $CI->db->get(db_prefix() . 'roles')->row();
    if ($existing) {
        return;
    }

    $permissions = serialize([
        'tasks' => ['view', 'view_own', 'edit', 'create'],
        'projects' => ['view', 'view_own'],
        'my_timesheets' => ['view_own', 'create'],
    ]);

    $CI->db->insert(db_prefix() . 'roles', [
        'name'        => 'AI Agent',
        'permissions' => $permissions,
    ]);
}

function openclaw_ensure_custom_fields()
{
    // Staff fields
    openclaw_ensure_custom_field('staff', 'openclaw_is_agent', 'checkbox', '');
    openclaw_ensure_custom_field('staff', 'openclaw_agent_type', 'select', 'domain_coordinator,project_specialist,worker');
    openclaw_ensure_custom_field('staff', 'openclaw_autonomy_tier', 'select', '0,1,2,3,4');
    openclaw_ensure_custom_field('staff', 'openclaw_gateway_id', 'input', '');
    openclaw_ensure_custom_field('staff', 'openclaw_external_id', 'input', '');

    // Project fields
    openclaw_ensure_custom_field('projects', 'openclaw_specialist_agent', 'input', '');
    openclaw_ensure_custom_field('projects', 'openclaw_approval_model', 'select', 'none,before_start,before_completion,both');
    openclaw_ensure_custom_field('projects', 'openclaw_strategic_priority', 'select', 'low,medium,high,critical');

    // Task fields
    openclaw_ensure_custom_field('tasks', 'openclaw_execution_type', 'select', 'human,agent,hybrid');
    openclaw_ensure_custom_field('tasks', 'openclaw_risk_tier', 'select', 'low,medium,high,critical');
    openclaw_ensure_custom_field('tasks', 'openclaw_approval_gate', 'select', 'none,before_start,before_completion,both');
}

function openclaw_ensure_custom_field($fieldto, $name, $type, $options = '')
{
    $CI = &get_instance();
    $CI->db->where('fieldto', $fieldto);
    $CI->db->where('name', $name);
    $exists = $CI->db->get(db_prefix() . 'customfields')->row();
    if ($exists) {
        return;
    }

    $CI->db->insert(db_prefix() . 'customfields', [
        'fieldto'            => $fieldto,
        'name'               => $name,
        'slug'               => $fieldto . '_' . $name,
        'required'           => 0,
        'type'               => $type,
        'options'            => $options,
        'field_order'        => 0,
        'active'             => 1,
        'show_on_table'      => 0,
        'show_on_client_portal' => 0,
        'show_on_pdf'        => 0,
        'bs_column'          => 12,
    ]);
}

function openclaw_register_api_permissions($apiPermissions)
{
    $capabilities = [
        'get' => _l('permission_view'),
        'post' => _l('permission_create'),
        'put' => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];

    $apiPermissions['openclaw'] = [
        'name' => 'OpenClaw',
        'capabilities' => $capabilities,
    ];
    $apiPermissions['api_openclaw'] = [
        'name' => 'OpenClaw API',
        'capabilities' => $capabilities,
    ];
    return $apiPermissions;
}

function openclaw_admin_init()
{
    $CI = &get_instance();

    if (has_permission('settings', '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item('openclaw', [
            'name'     => _l('openclaw'),
            'icon'     => 'fa fa-robot',
            'position' => 33,
            'href'     => admin_url('openclaw'),
        ]);

        $CI->app_menu->add_sidebar_children_item('openclaw', [
            'slug'     => 'openclaw_dashboard',
            'name'     => _l('openclaw_dashboard'),
            'href'     => admin_url('openclaw'),
            'position' => 1,
        ]);
        $CI->app_menu->add_sidebar_children_item('openclaw', [
            'slug'     => 'openclaw_chat',
            'name'     => _l('openclaw_chat'),
            'href'     => admin_url('openclaw/chat'),
            'position' => 2,
        ]);
        $CI->app_menu->add_sidebar_children_item('openclaw', [
            'slug'     => 'openclaw_agents',
            'name'     => _l('openclaw_agents'),
            'href'     => admin_url('openclaw/agents'),
            'position' => 3,
        ]);
        $CI->app_menu->add_sidebar_children_item('openclaw', [
            'slug'     => 'openclaw_gateways',
            'name'     => _l('openclaw_gateways'),
            'href'     => admin_url('openclaw/gateways'),
            'position' => 4,
        ]);
        $CI->app_menu->add_sidebar_children_item('openclaw', [
            'slug'     => 'openclaw_events',
            'name'     => _l('openclaw_event_log'),
            'href'     => admin_url('openclaw/events'),
            'position' => 5,
        ]);
        $CI->app_menu->add_sidebar_children_item('openclaw', [
            'slug'     => 'openclaw_wizard',
            'name'     => _l('openclaw_onboarding_wizard'),
            'href'     => admin_url('openclaw/wizard'),
            'position' => 6,
        ]);
    }

    // Inject native project tab.
    if (isset($CI->app_tabs)) {
        $CI->app_tabs->add_project_tab('openclaw_chat', [
            'name'     => _l('openclaw_ai_chat'),
            'icon'     => 'fa fa-comments',
            'view'     => 'openclaw/chat/panel',
            'position' => 70,
        ]);
        $CI->app_tabs->add_customer_profile_tab('openclaw_chat', [
            'name'     => _l('openclaw_ai_chat'),
            'icon'     => 'fa fa-comments',
            'view'     => 'openclaw/chat/panel',
            'position' => 70,
        ]);
    }
}

function openclaw_cron_heartbeat()
{
    $CI = &get_instance();
    $CI->load->model('openclaw/Openclaw_gateways_model');
    $CI->Openclaw_gateways_model->run_heartbeat();
}

function openclaw_dispatch_event($eventType, $payload = [], $relType = null, $relId = null, $staffId = null)
{
    $CI = &get_instance();
    $CI->load->library('openclaw/Openclaw_dispatcher');
    $CI->openclaw_dispatcher->dispatch_event($eventType, $payload, $relType, $relId, $staffId);
}

function openclaw_on_task_created($taskId)
{
    openclaw_dispatch_event('task_created', ['task_id' => $taskId], 'task', $taskId);
}

function openclaw_on_task_updated($taskId)
{
    openclaw_dispatch_event('task_updated', ['task_id' => $taskId], 'task', $taskId);
}

function openclaw_on_task_status_changed($data)
{
    $taskId = isset($data['task_id']) ? $data['task_id'] : null;
    openclaw_dispatch_event('task_status_changed', $data, 'task', $taskId);
}

function openclaw_on_task_assignee_added($data)
{
    $taskId = isset($data['task_id']) ? $data['task_id'] : null;
    $staffId = isset($data['staff_id']) ? $data['staff_id'] : null;
    openclaw_dispatch_event('task_assignee_added', $data, 'task', $taskId, $staffId);
}

function openclaw_on_task_comment_added($data)
{
    $taskId = isset($data['task_id']) ? $data['task_id'] : null;
    openclaw_dispatch_event('task_comment_added', $data, 'task', $taskId);
}

function openclaw_on_task_timer_started($data)
{
    $taskId = isset($data['task_id']) ? $data['task_id'] : null;
    openclaw_dispatch_event('task_timer_started', $data, 'task', $taskId);
}

function openclaw_on_project_created($projectId)
{
    openclaw_dispatch_event('project_created', ['project_id' => $projectId], 'project', $projectId);
}

function openclaw_on_project_updated($projectId)
{
    openclaw_dispatch_event('project_updated', ['project_id' => $projectId], 'project', $projectId);
}

function openclaw_on_project_status_changed($data)
{
    $projectId = isset($data['project_id']) ? $data['project_id'] : null;
    openclaw_dispatch_event('project_status_changed', $data, 'project', $projectId);
}

function openclaw_on_project_member_added($data)
{
    $projectId = isset($data['project_id']) ? $data['project_id'] : null;
    openclaw_dispatch_event('project_member_added', $data, 'project', $projectId);
}

function openclaw_on_staff_created($staffId)
{
    openclaw_dispatch_event('staff_created', ['staff_id' => $staffId], 'staff', $staffId, $staffId);
}

function openclaw_on_staff_updated($staffId)
{
    openclaw_dispatch_event('staff_updated', ['staff_id' => $staffId], 'staff', $staffId, $staffId);
}

function openclaw_render_task_chat_panel($task)
{
    $CI = &get_instance();
    $CI->load->model('openclaw/Openclaw_chat_model');
    $threads = $CI->Openclaw_chat_model->get_threads(['rel_type' => 'task', 'rel_id' => $task->id]);
    echo $CI->load->view('openclaw/chat/panel', [
        'rel_type' => 'task',
        'rel_id'   => $task->id,
        'threads'  => $threads,
        'compact'  => true,
    ], true);
}

function openclaw_render_project_chat_summary($project)
{
    $CI = &get_instance();
    $CI->load->model('openclaw/Openclaw_chat_model');
    $threads = $CI->Openclaw_chat_model->get_threads(['rel_type' => 'project', 'rel_id' => $project->id, 'limit' => 3]);
    echo $CI->load->view('openclaw/chat/panel', [
        'rel_type' => 'project',
        'rel_id'   => $project->id,
        'threads'  => $threads,
        'compact'  => true,
        'summary'  => true,
    ], true);
}
