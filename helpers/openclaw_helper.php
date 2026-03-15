<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('openclaw_get_custom_field_value_by_name')) {
    function openclaw_get_custom_field_value_by_name($relId, $fieldTo, $name)
    {
        $CI = &get_instance();
        $CI->db->where('fieldto', $fieldTo);
        $CI->db->where('name', $name);
        $field = $CI->db->get(db_prefix() . 'customfields')->row();
        if (!$field) {
            return null;
        }
        return get_custom_field_value($relId, $field->id, $fieldTo);
    }
}

if (!function_exists('openclaw_is_staff_agent')) {
    function openclaw_is_staff_agent($staffId)
    {
        $value = openclaw_get_custom_field_value_by_name($staffId, 'staff', 'openclaw_is_agent');
        return !empty($value) && $value !== '0';
    }
}

if (!function_exists('openclaw_staff_display_name')) {
    function openclaw_staff_display_name($staffId)
    {
        $CI = &get_instance();
        $CI->db->select('firstname,lastname');
        $CI->db->where('staffid', $staffId);
        $staff = $CI->db->get(db_prefix() . 'staff')->row();
        if (!$staff) {
            return '';
        }
        return trim($staff->firstname . ' ' . $staff->lastname);
    }
}

if (!function_exists('openclaw_thread_title')) {
    function openclaw_thread_title($relType, $relId)
    {
        $prefix = ucfirst($relType) . ' ' . (int) $relId;
        $CI = &get_instance();

        if ($relType === 'project') {
            $CI->db->select('name');
            $CI->db->where('id', $relId);
            $row = $CI->db->get(db_prefix() . 'projects')->row();
            if ($row && !empty($row->name)) {
                return 'Project ' . (int) $relId . ': ' . $row->name;
            }
        }

        if ($relType === 'task') {
            $CI->db->select('name');
            $CI->db->where('id', $relId);
            $row = $CI->db->get(db_prefix() . 'tasks')->row();
            if ($row && !empty($row->name)) {
                return 'Task ' . (int) $relId . ': ' . $row->name;
            }
        }

        if ($relType === 'client') {
            $CI->db->select('company');
            $CI->db->where('userid', $relId);
            $row = $CI->db->get(db_prefix() . 'clients')->row();
            if ($row && !empty($row->company)) {
                return 'Client ' . (int) $relId . ': ' . $row->company;
            }
        }

        return $prefix . ' Thread';
    }
}

if (!function_exists('openclaw_get_ai_agent_role_id')) {
    function openclaw_get_ai_agent_role_id()
    {
        $CI = &get_instance();
        $CI->db->where('name', 'AI Agent');
        $row = $CI->db->get(db_prefix() . 'roles')->row();
        return $row ? (int) $row->roleid : 0;
    }
}

if (!function_exists('openclaw_staff_custom_field_post_data')) {
    function openclaw_staff_custom_field_post_data($byName)
    {
        $CI = &get_instance();
        $result = [];
        foreach ($byName as $name => $value) {
            $CI->db->where('fieldto', 'staff');
            $CI->db->where('name', $name);
            $field = $CI->db->get(db_prefix() . 'customfields')->row();
            if ($field) {
                $result[$field->id] = $value;
            }
        }
        return $result;
    }
}
