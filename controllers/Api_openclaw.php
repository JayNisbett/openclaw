<?php

defined('BASEPATH') or exit('No direct script access allowed');

require FCPATH . 'modules/api/controllers/REST_Controller.php';

class Api_openclaw extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('openclaw/openclaw');
        $this->load->model('openclaw/Openclaw_model');
        $this->load->model('openclaw/Openclaw_gateways_model');
        $this->load->model('openclaw/Openclaw_chat_model');
        $this->load->model('openclaw/Openclaw_events_model');
    }

    public function data_get($segment1 = null, $segment2 = null, $segment3 = null, $segment4 = null)
    {
        if ($segment1 === 'schema') {
            $this->response($this->build_schema(), REST_Controller::HTTP_OK);
            return;
        }

        if ($segment1 === 'agents') {
            if (is_numeric($segment2)) {
                $staffId = (int) $segment2;
                if ($segment3 === 'tasks') {
                    $this->response($this->Openclaw_model->get_agent_tasks($staffId), REST_Controller::HTTP_OK);
                    return;
                }
                if ($segment3 === 'projects') {
                    $this->response($this->Openclaw_model->get_agent_projects($staffId), REST_Controller::HTTP_OK);
                    return;
                }
                $agent = $this->Openclaw_model->get_agent($staffId);
                if (!$agent) {
                    $this->response(['status' => false, 'message' => 'Agent not found'], REST_Controller::HTTP_NOT_FOUND);
                    return;
                }
                $this->response($agent, REST_Controller::HTTP_OK);
                return;
            }

            $this->response($this->Openclaw_model->get_staff_agents(), REST_Controller::HTTP_OK);
            return;
        }

        if ($segment1 === 'chat' && $segment2 === 'threads') {
            if (is_numeric($segment3)) {
                $thread = $this->Openclaw_chat_model->get_thread((int) $segment3);
                if (!$thread) {
                    $this->response(['status' => false, 'message' => 'Thread not found'], REST_Controller::HTTP_NOT_FOUND);
                    return;
                }
                $messages = $this->Openclaw_chat_model->get_messages((int) $segment3);
                $this->response(['thread' => $thread, 'messages' => $messages], REST_Controller::HTTP_OK);
                return;
            }

            $threads = $this->Openclaw_chat_model->get_threads([
                'rel_type' => $this->get('rel_type') ?: null,
                'status' => $this->get('status') ?: null,
                'gateway_id' => $this->get('gateway_id') ?: null,
            ]);
            $this->response($threads, REST_Controller::HTTP_OK);
            return;
        }

        $this->response(['status' => false, 'message' => 'Endpoint not found'], REST_Controller::HTTP_NOT_FOUND);
    }

    public function data_post($segment1 = null, $segment2 = null, $segment3 = null, $segment4 = null)
    {
        if ($segment1 === 'events') {
            $payload = $this->post();
            $id = $this->Openclaw_events_model->log_event([
                'event_type' => isset($payload['event_type']) ? $payload['event_type'] : 'agent_event',
                'source' => 'agent',
                'staff_id' => isset($payload['staff_id']) ? $payload['staff_id'] : null,
                'rel_type' => isset($payload['rel_type']) ? $payload['rel_type'] : null,
                'rel_id' => isset($payload['rel_id']) ? $payload['rel_id'] : null,
                'gateway_id' => isset($payload['gateway_id']) ? $payload['gateway_id'] : null,
                'payload' => $payload,
            ]);
            $this->response(['status' => true, 'event_id' => $id], REST_Controller::HTTP_OK);
            return;
        }

        if ($segment1 === 'chat' && $segment2 === 'threads' && is_numeric($segment3) && ($segment4 === null || $segment4 === 'messages')) {
            $threadId = (int) $segment3;
            $thread = $this->Openclaw_chat_model->get_thread($threadId);
            if (!$thread) {
                $this->response(['status' => false, 'message' => 'Thread not found'], REST_Controller::HTTP_NOT_FOUND);
                return;
            }

            $message = $this->post('message');
            if (trim((string) $message) === '') {
                $this->response(['status' => false, 'message' => 'Message required'], REST_Controller::HTTP_BAD_REQUEST);
                return;
            }

            $id = $this->Openclaw_chat_model->add_message([
                'thread_id' => $threadId,
                'sender_type' => $this->post('sender_type') ?: 'agent',
                'sender_id' => $this->post('sender_id') ?: null,
                'instance_id' => $this->post('instance_id') ?: null,
                'external_message_id' => $this->post('external_message_id') ?: null,
                'metadata' => $this->post('metadata') ?: null,
                'message' => $message,
            ]);
            $this->response(['status' => true, 'message_id' => $id], REST_Controller::HTTP_OK);
            return;
        }

        if ($segment1 === 'chat' && $segment2 === 'threads') {
            $threadId = $this->Openclaw_chat_model->create_thread([
                'rel_type' => $this->post('rel_type') ?: 'general',
                'rel_id' => $this->post('rel_id') ?: null,
                'gateway_id' => $this->post('gateway_id') ?: null,
                'title' => $this->post('title') ?: null,
                'external_thread_id' => $this->post('external_thread_id') ?: null,
                'created_by' => get_staff_user_id(),
            ]);
            $this->response(['status' => true, 'thread_id' => $threadId], REST_Controller::HTTP_OK);
            return;
        }

        if ($segment1 === 'webhook' && is_numeric($segment2)) {
            $gatewayId = (int) $segment2;
            $gateway = $this->Openclaw_gateways_model->get_gateways($gatewayId);
            if (!$gateway) {
                $this->response(['status' => false, 'message' => 'Gateway not found'], REST_Controller::HTTP_NOT_FOUND);
                return;
            }

            $provided = $this->input->get_request_header('X-OpenClaw-Secret');
            if (!$provided || !hash_equals($gateway['inbound_secret'], $provided)) {
                $this->response(['status' => false, 'message' => 'Invalid secret'], REST_Controller::HTTP_UNAUTHORIZED);
                return;
            }

            $payload = $this->post();
            if (isset($payload['thread']) && isset($payload['message'])) {
                $threadPayload = $payload['thread'];
                $messagePayload = $payload['message'];
                $result = $this->Openclaw_chat_model->create_or_append_message([
                    'thread_id' => isset($threadPayload['id']) ? $threadPayload['id'] : null,
                    'rel_type' => isset($threadPayload['rel_type']) ? $threadPayload['rel_type'] : 'general',
                    'rel_id' => isset($threadPayload['rel_id']) ? $threadPayload['rel_id'] : null,
                    'gateway_id' => $gatewayId,
                    'title' => isset($threadPayload['title']) ? $threadPayload['title'] : null,
                    'external_thread_id' => isset($threadPayload['external_thread_id']) ? $threadPayload['external_thread_id'] : null,
                    'created_by' => get_staff_user_id() ?: 0,
                ], [
                    'sender_type' => isset($messagePayload['sender_type']) ? $messagePayload['sender_type'] : 'agent',
                    'sender_id' => isset($messagePayload['sender_id']) ? $messagePayload['sender_id'] : null,
                    'instance_id' => isset($messagePayload['instance_id']) ? $messagePayload['instance_id'] : null,
                    'external_message_id' => isset($messagePayload['id']) ? $messagePayload['id'] : null,
                    'metadata' => isset($messagePayload['metadata']) ? $messagePayload['metadata'] : null,
                    'message' => isset($messagePayload['content']) ? $messagePayload['content'] : '',
                ]);
                $this->response(['status' => true, 'result' => $result], REST_Controller::HTTP_OK);
                return;
            }

            $eventId = $this->Openclaw_events_model->log_event([
                'event_type' => isset($payload['event']) ? $payload['event'] : 'gateway_event',
                'source' => 'agent',
                'gateway_id' => $gatewayId,
                'payload' => $payload,
            ]);
            $this->response(['status' => true, 'event_id' => $eventId], REST_Controller::HTTP_OK);
            return;
        }

        $this->response(['status' => false, 'message' => 'Endpoint not found'], REST_Controller::HTTP_NOT_FOUND);
    }

    private function build_schema()
    {
        return [
            'name' => 'perfex-openclaw',
            'version' => '1.0.0',
            'base_url' => site_url(),
            'auth' => [
                'type' => 'jwt',
                'header' => 'authtoken',
                'description' => 'Per-agent JWT token from tbluser_api',
            ],
            'tools' => [
                [
                    'name' => 'list_agents',
                    'description' => 'List all AI agents (staff marked as openclaw_is_agent)',
                    'method' => 'GET',
                    'path' => '/api/openclaw/agents',
                ],
                [
                    'name' => 'get_agent_tasks',
                    'description' => 'Get tasks assigned to an agent',
                    'method' => 'GET',
                    'path' => '/api/openclaw/agents/{id}/tasks',
                ],
                [
                    'name' => 'list_threads',
                    'description' => 'List chat threads',
                    'method' => 'GET',
                    'path' => '/api/openclaw/chat/threads',
                ],
                [
                    'name' => 'create_thread',
                    'description' => 'Create a chat thread for project/task/client context',
                    'method' => 'POST',
                    'path' => '/api/openclaw/chat/threads',
                ],
                [
                    'name' => 'post_thread_message',
                    'description' => 'Post chat message to a thread',
                    'method' => 'POST',
                    'path' => '/api/openclaw/chat/threads/{id}',
                ],
                [
                    'name' => 'report_event',
                    'description' => 'Report escalation, blocker, or system event',
                    'method' => 'POST',
                    'path' => '/api/openclaw/events',
                ],
            ],
            'native_perfex_api' => [
                '/api/tasks',
                '/api/tasks/{id}',
                '/api/projects',
                '/api/projects/{id}',
                '/api/timesheets',
            ],
        ];
    }
}
