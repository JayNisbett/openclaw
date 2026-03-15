<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Openclaw extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        if (!has_permission('settings', '', 'view')) {
            access_denied('settings');
        }

        $this->load->helper('openclaw/openclaw');
        $this->load->model('openclaw/Openclaw_model');
        $this->load->model('openclaw/Openclaw_gateways_model');
        $this->load->model('openclaw/Openclaw_chat_model');
        $this->load->model('openclaw/Openclaw_events_model');
        $this->load->library('openclaw/Openclaw_dispatcher');
        $this->lang->load('openclaw/openclaw');
    }

    public function index()
    {
        $data['title'] = _l('openclaw_dashboard');
        $data['gateways'] = $this->Openclaw_gateways_model->get_gateways();
        $data['agents'] = $this->Openclaw_model->get_staff_agents();
        $data['threads'] = $this->Openclaw_chat_model->get_threads(['limit' => 10]);
        $data['events'] = $this->Openclaw_events_model->get_events(['limit' => 20]);
        $this->load->view('openclaw/dashboard', $data);
    }

    public function gateways($id = null)
    {
        if ($this->input->post()) {
            $post = $this->input->post();
            if (!empty($post['id'])) {
                $ok = $this->Openclaw_gateways_model->update_gateway((int) $post['id'], $post);
                set_alert($ok ? 'success' : 'warning', $ok ? _l('updated_successfully') : _l('openclaw_operation_failed'));
            } else {
                $newId = $this->Openclaw_gateways_model->add_gateway($post);
                set_alert($newId ? 'success' : 'warning', $newId ? _l('added_successfully') : _l('openclaw_operation_failed'));
            }
            redirect(admin_url('openclaw/gateways'));
        }

        $data['title'] = _l('openclaw_gateways');
        $data['editing'] = $id ? $this->Openclaw_gateways_model->get_gateways((int) $id) : null;
        $data['gateways'] = $this->Openclaw_gateways_model->get_gateways();
        $this->load->view('openclaw/gateways/manage', $data);
    }

    public function delete_gateway($id)
    {
        $ok = $this->Openclaw_gateways_model->delete_gateway((int) $id);
        set_alert($ok ? 'success' : 'warning', $ok ? _l('deleted', _l('openclaw_gateways')) : _l('openclaw_operation_failed'));
        redirect(admin_url('openclaw/gateways'));
    }

    public function discover_gateway_agents($gatewayId)
    {
        $result = $this->Openclaw_gateways_model->discover_agents((int) $gatewayId);
        if ($this->input->is_ajax_request()) {
            echo json_encode($result);
            return;
        }
        set_alert($result['success'] ? 'success' : 'warning', $result['success'] ? _l('openclaw_save_success') : $result['message']);
        redirect(admin_url('openclaw/wizard/2?gateway=' . (int) $gatewayId));
    }

    public function agents($id = null)
    {
        if ($id !== null) {
            $data['title'] = _l('openclaw_agent_staff');
            $data['agent'] = $this->Openclaw_model->get_agent((int) $id);
            if (!$data['agent']) {
                show_404();
            }
            $data['tasks'] = $this->Openclaw_model->get_agent_tasks((int) $id);
            $data['projects'] = $this->Openclaw_model->get_agent_projects((int) $id);
            $this->load->view('openclaw/agents/detail', $data);
            return;
        }

        $data['title'] = _l('openclaw_agents');
        $data['agents'] = $this->Openclaw_model->get_staff_agents();
        $this->load->view('openclaw/agents/manage', $data);
    }

    public function create_agent_api_key($staffId)
    {
        $result = $this->Openclaw_model->create_agent_api_key((int) $staffId);
        if ($this->input->is_ajax_request()) {
            echo json_encode($result);
            return;
        }
        set_alert($result['success'] ? 'success' : 'warning', $result['success'] ? _l('openclaw_save_success') : $result['message']);
        if ($result['success']) {
            set_alert('info', 'JWT: ' . $result['token']);
        }
        redirect(admin_url('openclaw/agents/' . (int) $staffId));
    }

    public function revoke_agent_api_key($staffId)
    {
        $ok = $this->Openclaw_model->revoke_agent_api_key((int) $staffId);
        set_alert($ok ? 'success' : 'warning', $ok ? _l('openclaw_save_success') : _l('openclaw_operation_failed'));
        redirect(admin_url('openclaw/agents/' . (int) $staffId));
    }

    public function chat($threadId = null)
    {
        $data['title'] = _l('openclaw_chat');
        $data['threads'] = $this->Openclaw_chat_model->get_threads();
        $data['active_thread'] = null;
        $data['messages'] = [];

        if ($threadId !== null) {
            $data['active_thread'] = $this->Openclaw_chat_model->get_thread((int) $threadId);
            if ($data['active_thread']) {
                $data['messages'] = $this->Openclaw_chat_model->get_messages((int) $threadId);
            }
        }

        $this->load->view('openclaw/chat/inbox', $data);
    }

    public function chat_create_thread()
    {
        if (!$this->input->post()) {
            show_404();
        }
        $threadId = $this->Openclaw_chat_model->create_thread([
            'rel_type' => $this->input->post('rel_type'),
            'rel_id' => $this->input->post('rel_id'),
            'gateway_id' => $this->input->post('gateway_id'),
            'title' => $this->input->post('title'),
            'created_by' => get_staff_user_id(),
        ]);

        redirect(admin_url('openclaw/chat/' . $threadId));
    }

    public function chat_send()
    {
        if (!$this->input->post()) {
            show_404();
        }
        $threadId = (int) $this->input->post('thread_id');
        $message = $this->input->post('message');
        if ($threadId <= 0 || trim((string) $message) === '') {
            echo json_encode(['success' => false, 'message' => 'Missing thread or message']);
            return;
        }

        $messageId = $this->Openclaw_chat_model->add_message([
            'thread_id' => $threadId,
            'sender_type' => 'staff',
            'sender_id' => get_staff_user_id(),
            'message' => $message,
        ]);

        $this->openclaw_dispatcher->dispatch_chat_message($threadId, $messageId);
        echo json_encode(['success' => true, 'message_id' => $messageId]);
    }

    public function chat_messages($threadId)
    {
        $afterId = $this->input->get('after_id');
        $messages = $this->Openclaw_chat_model->get_messages((int) $threadId, $afterId ? (int) $afterId : null);
        echo $this->load->view('openclaw/chat/_messages', ['messages' => $messages], true);
    }

    public function events()
    {
        $data['title'] = _l('openclaw_event_log');
        $data['events'] = $this->Openclaw_events_model->get_events(['limit' => 500]);
        $this->load->view('openclaw/events/log', $data);
    }

    public function settings()
    {
        if ($this->input->post()) {
            update_option('openclaw_cpanel_host', $this->input->post('openclaw_cpanel_host'));
            update_option('openclaw_cpanel_username', $this->input->post('openclaw_cpanel_username'));
            update_option('openclaw_cpanel_token', $this->input->post('openclaw_cpanel_token'));
            update_option('openclaw_cpanel_domain', $this->input->post('openclaw_cpanel_domain'));
            update_option('openclaw_chat_poll_interval', $this->input->post('openclaw_chat_poll_interval'));
            set_alert('success', _l('openclaw_save_success'));
            redirect(admin_url('openclaw/settings'));
        }

        $data['title'] = _l('openclaw_settings');
        $this->load->view('openclaw/settings/manage', $data);
    }

    public function wizard($step = 1)
    {
        $step = (int) $step;
        if ($step < 1 || $step > 5) {
            $step = 1;
        }

        $data['title'] = _l('openclaw_onboarding_wizard');
        $data['step'] = $step;
        $data['gateways'] = $this->Openclaw_gateways_model->get_gateways();
        $data['discovered_agents'] = [];
        if (!empty($data['gateways'])) {
            $data['discovered_agents'] = $this->Openclaw_gateways_model->get_gateway_agents((int) $data['gateways'][0]['id']);
        }

        $view = 'openclaw/wizard/step' . $step . ($step === 1 ? '_connect' : ($step === 2 ? '_agents' : ($step === 3 ? '_email' : ($step === 4 ? '_staff' : '_download'))));
        $this->load->view($view, $data);
    }

    public function wizard_create_email()
    {
        if (!$this->input->post()) {
            show_404();
        }

        $this->load->library('openclaw/Openclaw_cpanel');
        $local = trim((string) $this->input->post('local_part'));
        $password = trim((string) $this->input->post('password'));
        if ($local === '' || $password === '') {
            set_alert('warning', 'Local part and password are required.');
            redirect(admin_url('openclaw/wizard/3'));
        }

        $result = $this->openclaw_cpanel->create_email($local, $password);
        if ($result['success']) {
            set_alert('success', 'Email created: ' . $result['email']);
        } else {
            set_alert('warning', $result['message']);
        }
        redirect(admin_url('openclaw/wizard/3'));
    }

    public function wizard_create_staff()
    {
        if (!$this->input->post()) {
            show_404();
        }
        $gatewayAgentId = (int) $this->input->post('gateway_agent_id');
        $email = $this->input->post('email');
        $firstName = $this->input->post('firstname');
        $lastName = $this->input->post('lastname');

        $this->load->model('staff_model');
        $roleId = openclaw_get_ai_agent_role_id();
        $password = app_generate_hash();

        $customFields = [
            'staff' => openclaw_staff_custom_field_post_data([
                'openclaw_is_agent' => '1',
                'openclaw_agent_type' => $this->input->post('agent_type') ?: 'worker',
                'openclaw_autonomy_tier' => $this->input->post('autonomy_tier') ?: '2',
                'openclaw_gateway_id' => $this->input->post('gateway_id'),
                'openclaw_external_id' => $this->input->post('external_agent_id'),
            ]),
        ];

        $staffData = [
            'firstname' => $firstName,
            'lastname' => $lastName,
            'email' => $email,
            'password' => $password,
            'role' => $roleId,
            'permissions' => [],
            'custom_fields' => $customFields,
        ];

        $staffId = $this->staff_model->add($staffData);
        if (!$staffId) {
            set_alert('warning', _l('openclaw_operation_failed'));
            redirect(admin_url('openclaw/wizard/4'));
        }

        $api = $this->Openclaw_model->create_agent_api_key($staffId);
        $this->Openclaw_gateways_model->link_gateway_agent_staff($gatewayAgentId, $staffId, $api['success'] ? $api['api_user_id'] : null);
        set_alert('success', _l('openclaw_save_success'));
        redirect(admin_url('openclaw/agents/' . $staffId));
    }

    public function download_agent_spec($staffId)
    {
        $agent = $this->Openclaw_model->get_agent((int) $staffId);
        if (!$agent) {
            show_404();
        }

        $this->db->where('id', $agent['api_user_id']);
        $apiUser = $this->db->get(db_prefix() . 'user_api')->row_array();
        $token = $apiUser ? $apiUser['token'] : '';

        $content = "# Specialist agent: " . $agent['full_name'] . "\n\n";
        $content .= "## Identity\n\n";
        $content .= "You are the specialist agent for Perfex staff ID `" . $agent['staffid'] . "`.\n\n";
        $content .= "## API\n\n";
        $content .= "- Base URL: `" . site_url() . "`\n";
        $content .= "- Header: `authtoken: " . $token . "`\n";
        $content .= "- Schema: `" . site_url('api/openclaw/schema') . "`\n";

        $filename = 'openclaw-agent-' . (int) $staffId . '-AGENTS.md';
        header('Content-Type: text/markdown');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $content;
        exit;
    }

    public function download_perfex_skill($staffId)
    {
        $agent = $this->Openclaw_model->get_agent((int) $staffId);
        if (!$agent) {
            show_404();
        }
        $content = "# Perfex Skill for " . $agent['full_name'] . "\n\n";
        $content .= "- Use `authtoken` header for every request.\n";
        $content .= "- Query assigned tasks via `/api/openclaw/agents/" . (int) $agent['staffid'] . "/tasks`.\n";
        $content .= "- Use `/api/tasks` and `/api/timesheets` for day-to-day updates.\n";
        $content .= "- Use `/api/openclaw/chat/threads` to engage in context threads.\n";
        $filename = 'openclaw-agent-' . (int) $staffId . '-PERFEX_SKILL.md';
        header('Content-Type: text/markdown');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $content;
        exit;
    }

    public function download_mcp_config($staffId)
    {
        $agent = $this->Openclaw_model->get_agent((int) $staffId);
        if (!$agent) {
            show_404();
        }
        $this->db->where('id', $agent['api_user_id']);
        $apiUser = $this->db->get(db_prefix() . 'user_api')->row_array();
        $token = $apiUser ? $apiUser['token'] : '';
        $config = [
            'name' => 'perfex-openclaw-' . (int) $staffId,
            'baseUrl' => site_url(),
            'schemaUrl' => site_url('api/openclaw/schema'),
            'authHeader' => 'authtoken',
            'authToken' => $token,
        ];
        $filename = 'openclaw-agent-' . (int) $staffId . '-mcp.json';
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo json_encode($config, JSON_PRETTY_PRINT);
        exit;
    }
}
