<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Openclaw_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('openclaw/openclaw');
    }

    public function get_staff_agents()
    {
        $this->db->select('s.staffid,s.firstname,s.lastname,s.email,s.active,s.role');
        $this->db->from(db_prefix() . 'staff s');
        $this->db->order_by('s.firstname', 'ASC');
        $staff = $this->db->get()->result_array();

        $agents = [];
        foreach ($staff as $row) {
            if ($this->is_agent($row['staffid'])) {
                $row['full_name'] = trim($row['firstname'] . ' ' . $row['lastname']);
                $row['gateway_id'] = openclaw_get_custom_field_value_by_name($row['staffid'], 'staff', 'openclaw_gateway_id');
                $row['external_id'] = openclaw_get_custom_field_value_by_name($row['staffid'], 'staff', 'openclaw_external_id');
                $row['agent_type'] = openclaw_get_custom_field_value_by_name($row['staffid'], 'staff', 'openclaw_agent_type');
                $row['autonomy_tier'] = openclaw_get_custom_field_value_by_name($row['staffid'], 'staff', 'openclaw_autonomy_tier');
                $row['api_user_id'] = $this->get_staff_api_user_id($row['email']);
                $agents[] = $row;
            }
        }
        return $agents;
    }

    public function get_agent($staffId)
    {
        $this->db->where('staffid', $staffId);
        $staff = $this->db->get(db_prefix() . 'staff')->row_array();
        if (!$staff) {
            return null;
        }

        $staff['is_agent'] = $this->is_agent($staffId);
        $staff['full_name'] = trim($staff['firstname'] . ' ' . $staff['lastname']);
        $staff['gateway_id'] = openclaw_get_custom_field_value_by_name($staffId, 'staff', 'openclaw_gateway_id');
        $staff['external_id'] = openclaw_get_custom_field_value_by_name($staffId, 'staff', 'openclaw_external_id');
        $staff['agent_type'] = openclaw_get_custom_field_value_by_name($staffId, 'staff', 'openclaw_agent_type');
        $staff['autonomy_tier'] = openclaw_get_custom_field_value_by_name($staffId, 'staff', 'openclaw_autonomy_tier');
        $staff['api_user_id'] = $this->get_staff_api_user_id($staff['email']);

        return $staff;
    }

    public function is_agent($staffId)
    {
        return openclaw_is_staff_agent($staffId);
    }

    public function get_staff_api_user_id($email)
    {
        $this->db->select('id');
        $this->db->where('user', $email);
        $row = $this->db->get(db_prefix() . 'user_api')->row();
        return $row ? (int) $row->id : null;
    }

    public function create_agent_api_key($staffId)
    {
        $this->load->model('staff_model');
        $staff = $this->staff_model->get($staffId);
        if (!$staff || empty($staff->email)) {
            return ['success' => false, 'message' => 'Staff not found or email missing'];
        }

        $this->revoke_agent_api_key($staffId);

        $this->load->model('api/Api_model', 'openclaw_api_model');

        $apiData = [
            'user' => $staff->email,
            'name' => trim($staff->firstname . ' ' . $staff->lastname) . ' (OpenClaw Agent)',
            'expiration_date' => date('Y-m-d H:i:s', strtotime('+5 years')),
            'permissions' => [
                ['feature' => 'tasks', 'capability' => 'get'],
                ['feature' => 'tasks', 'capability' => 'search_get'],
                ['feature' => 'tasks', 'capability' => 'post'],
                ['feature' => 'tasks', 'capability' => 'put'],
                ['feature' => 'projects', 'capability' => 'get'],
                ['feature' => 'projects', 'capability' => 'search_get'],
                ['feature' => 'timesheets', 'capability' => 'get'],
                ['feature' => 'timesheets', 'capability' => 'post'],
                ['feature' => 'openclaw', 'capability' => 'get'],
                ['feature' => 'openclaw', 'capability' => 'post'],
                ['feature' => 'api_openclaw', 'capability' => 'get'],
                ['feature' => 'api_openclaw', 'capability' => 'post'],
            ],
        ];

        $apiId = $this->openclaw_api_model->add_user($apiData);
        if (!$apiId) {
            return ['success' => false, 'message' => 'Failed to create API key'];
        }

        $this->db->where('id', $apiId);
        $apiUser = $this->db->get(db_prefix() . 'user_api')->row_array();
        if (!$apiUser) {
            return ['success' => false, 'message' => 'API user created but token lookup failed'];
        }

        $this->db->where('staff_id', $staffId);
        $this->db->update(db_prefix() . 'openclaw_gateway_agents', ['api_user_id' => $apiId]);

        return [
            'success' => true,
            'api_user_id' => $apiId,
            'token' => $apiUser['token'],
        ];
    }

    public function revoke_agent_api_key($staffId)
    {
        $this->load->model('staff_model');
        $staff = $this->staff_model->get($staffId);
        if (!$staff || empty($staff->email)) {
            return false;
        }

        $this->db->where('user', $staff->email);
        $existing = $this->db->get(db_prefix() . 'user_api')->result_array();
        if (!$existing) {
            return true;
        }

        foreach ($existing as $apiUser) {
            $this->db->where('api_id', $apiUser['id']);
            $this->db->delete(db_prefix() . 'user_api_permissions');
            $this->db->where('id', $apiUser['id']);
            $this->db->delete(db_prefix() . 'user_api');
        }

        $this->db->where('staff_id', $staffId);
        $this->db->update(db_prefix() . 'openclaw_gateway_agents', ['api_user_id' => null]);
        return true;
    }

    public function get_agent_tasks($staffId)
    {
        $this->db->select('t.*');
        $this->db->from(db_prefix() . 'task_assigned ta');
        $this->db->join(db_prefix() . 'tasks t', 't.id = ta.taskid', 'inner');
        $this->db->where('ta.staffid', $staffId);
        $this->db->order_by('t.id', 'DESC');
        return $this->db->get()->result_array();
    }

    public function get_agent_projects($staffId)
    {
        $this->db->select('p.*');
        $this->db->from(db_prefix() . 'project_members pm');
        $this->db->join(db_prefix() . 'projects p', 'p.id = pm.project_id', 'inner');
        $this->db->where('pm.staff_id', $staffId);
        $this->db->order_by('p.id', 'DESC');
        return $this->db->get()->result_array();
    }
}
