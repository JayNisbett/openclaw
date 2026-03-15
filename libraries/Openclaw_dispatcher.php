<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Openclaw_dispatcher
{
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('openclaw/Openclaw_model');
        $this->CI->load->model('openclaw/Openclaw_gateways_model');
        $this->CI->load->model('openclaw/Openclaw_events_model');
        $this->CI->load->model('openclaw/Openclaw_chat_model');
    }

    public function dispatch_event($eventType, $payload, $relType = null, $relId = null, $staffId = null)
    {
        $gatewayIds = $this->resolve_gateways_for_context($relType, $relId, $staffId);

        if (empty($gatewayIds)) {
            $this->CI->Openclaw_events_model->log_event([
                'event_type' => $eventType,
                'source' => 'system',
                'staff_id' => $staffId,
                'rel_type' => $relType,
                'rel_id' => $relId,
                'payload' => ['note' => 'No gateway mapped', 'payload' => $payload],
            ]);
            return;
        }

        foreach ($gatewayIds as $gatewayId) {
            $response = $this->CI->Openclaw_gateways_model->dispatch_webhook($gatewayId, $eventType, [
                'rel_type' => $relType,
                'rel_id' => $relId,
                'staff_id' => $staffId,
                'event_payload' => $payload,
            ]);
            $this->CI->Openclaw_events_model->log_event([
                'event_type' => $eventType,
                'source' => 'perfex',
                'staff_id' => $staffId,
                'rel_type' => $relType,
                'rel_id' => $relId,
                'gateway_id' => $gatewayId,
                'payload' => ['request' => $payload, 'response' => $response],
                'response_code' => isset($response['status_code']) ? $response['status_code'] : null,
            ]);
        }
    }

    public function dispatch_chat_message($threadId, $messageId)
    {
        $thread = $this->CI->Openclaw_chat_model->get_thread($threadId);
        if (!$thread) {
            return;
        }

        $messages = $this->CI->Openclaw_chat_model->get_messages($threadId, $messageId - 1);
        $message = !empty($messages) ? end($messages) : null;
        if (!$message) {
            return;
        }

        if (!empty($thread['gateway_id'])) {
            $this->CI->Openclaw_gateways_model->dispatch_webhook($thread['gateway_id'], 'chat_message', [
                'thread' => $thread,
                'message' => $message,
            ]);
        }
    }

    protected function resolve_gateways_for_context($relType, $relId, $staffId)
    {
        $gatewayIds = [];

        if ($staffId) {
            $gateway = openclaw_get_custom_field_value_by_name($staffId, 'staff', 'openclaw_gateway_id');
            if (!empty($gateway)) {
                $gatewayIds[] = (int) $gateway;
            }
        }

        if ($relType === 'task' && $relId) {
            $this->CI->db->select('staffid');
            $this->CI->db->where('taskid', $relId);
            $assignees = $this->CI->db->get(db_prefix() . 'task_assigned')->result_array();
            foreach ($assignees as $assignee) {
                $gateway = openclaw_get_custom_field_value_by_name($assignee['staffid'], 'staff', 'openclaw_gateway_id');
                if (!empty($gateway)) {
                    $gatewayIds[] = (int) $gateway;
                }
            }
        }

        if ($relType === 'project' && $relId) {
            $this->CI->db->select('staff_id');
            $this->CI->db->where('project_id', $relId);
            $members = $this->CI->db->get(db_prefix() . 'project_members')->result_array();
            foreach ($members as $member) {
                $gateway = openclaw_get_custom_field_value_by_name($member['staff_id'], 'staff', 'openclaw_gateway_id');
                if (!empty($gateway)) {
                    $gatewayIds[] = (int) $gateway;
                }
            }
        }

        return array_values(array_unique(array_filter($gatewayIds)));
    }
}
