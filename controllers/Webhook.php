<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Webhook extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('openclaw/Openclaw_gateways_model');
        $this->load->model('openclaw/Openclaw_chat_model');
        $this->load->model('openclaw/Openclaw_events_model');
    }

    public function inbound($gatewayId)
    {
        $gateway = $this->Openclaw_gateways_model->get_gateways((int) $gatewayId);
        if (!$gateway) {
            return $this->json(['status' => false, 'message' => 'Gateway not found'], 404);
        }

        $provided = $this->input->get_request_header('X-OpenClaw-Secret');
        if (!$provided || !hash_equals($gateway['inbound_secret'], $provided)) {
            return $this->json(['status' => false, 'message' => 'Invalid secret'], 401);
        }

        $raw = $this->input->raw_input_stream;
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            $payload = [];
        }

        if (isset($payload['thread']) && isset($payload['message'])) {
            $threadPayload = $payload['thread'];
            $messagePayload = $payload['message'];

            $result = $this->Openclaw_chat_model->create_or_append_message([
                'thread_id' => isset($threadPayload['id']) ? $threadPayload['id'] : null,
                'rel_type' => isset($threadPayload['rel_type']) ? $threadPayload['rel_type'] : 'general',
                'rel_id' => isset($threadPayload['rel_id']) ? $threadPayload['rel_id'] : null,
                'gateway_id' => (int) $gatewayId,
                'title' => isset($threadPayload['title']) ? $threadPayload['title'] : null,
                'external_thread_id' => isset($threadPayload['external_thread_id']) ? $threadPayload['external_thread_id'] : null,
                'created_by' => 0,
            ], [
                'sender_type' => isset($messagePayload['sender_type']) ? $messagePayload['sender_type'] : 'agent',
                'sender_id' => isset($messagePayload['sender_id']) ? $messagePayload['sender_id'] : null,
                'instance_id' => isset($messagePayload['instance_id']) ? $messagePayload['instance_id'] : null,
                'external_message_id' => isset($messagePayload['id']) ? $messagePayload['id'] : null,
                'metadata' => isset($messagePayload['metadata']) ? $messagePayload['metadata'] : null,
                'message' => isset($messagePayload['content']) ? $messagePayload['content'] : '',
            ]);

            $this->Openclaw_events_model->log_event([
                'event_type' => 'chat_message_received',
                'source' => 'agent',
                'gateway_id' => (int) $gatewayId,
                'rel_type' => isset($threadPayload['rel_type']) ? $threadPayload['rel_type'] : null,
                'rel_id' => isset($threadPayload['rel_id']) ? $threadPayload['rel_id'] : null,
                'payload' => $payload,
            ]);

            return $this->json(['status' => true, 'result' => $result], 200);
        }

        $eventId = $this->Openclaw_events_model->log_event([
            'event_type' => isset($payload['event']) ? $payload['event'] : 'gateway_event',
            'source' => 'agent',
            'gateway_id' => (int) $gatewayId,
            'payload' => $payload,
        ]);

        return $this->json(['status' => true, 'event_id' => $eventId], 200);
    }

    public function schema()
    {
        $schema = [
            'name' => 'perfex-openclaw',
            'version' => '1.0.0',
            'base_url' => site_url(),
            'auth' => [
                'type' => 'jwt',
                'header' => 'authtoken',
                'description' => 'Per-agent token generated in Perfex API management',
            ],
            'tools' => [
                ['name' => 'list_agents', 'method' => 'GET', 'path' => '/api/openclaw/agents'],
                ['name' => 'get_agent_tasks', 'method' => 'GET', 'path' => '/api/openclaw/agents/{id}/tasks'],
                ['name' => 'get_agent_projects', 'method' => 'GET', 'path' => '/api/openclaw/agents/{id}/projects'],
                ['name' => 'list_threads', 'method' => 'GET', 'path' => '/api/openclaw/chat/threads'],
                ['name' => 'create_thread', 'method' => 'POST', 'path' => '/api/openclaw/chat/threads'],
                ['name' => 'post_thread_message', 'method' => 'POST', 'path' => '/api/openclaw/chat/threads/{id}/messages'],
                ['name' => 'report_event', 'method' => 'POST', 'path' => '/api/openclaw/events'],
                ['name' => 'inbound_webhook', 'method' => 'POST', 'path' => '/api/openclaw/webhook/{gatewayId}'],
            ],
            'native_perfex_api' => [
                '/api/tasks',
                '/api/projects',
                '/api/timesheets',
            ],
        ];

        return $this->json($schema, 200);
    }

    private function json($data, $status)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        return;
    }
}
