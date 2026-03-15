<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Openclaw_gateways_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get_gateways($id = null)
    {
        if ($id !== null) {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'openclaw_gateways')->row_array();
        }

        $this->db->order_by('id', 'DESC');
        return $this->db->get(db_prefix() . 'openclaw_gateways')->result_array();
    }

    public function add_gateway($data)
    {
        $insert = [
            'name' => $data['name'],
            'endpoint_url' => rtrim($data['endpoint_url'], '/'),
            'api_key' => $data['api_key'],
            'inbound_secret' => $data['inbound_secret'],
            'status' => isset($data['status']) ? $data['status'] : 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->insert(db_prefix() . 'openclaw_gateways', $insert);
        return $this->db->insert_id();
    }

    public function update_gateway($id, $data)
    {
        $update = [
            'name' => $data['name'],
            'endpoint_url' => rtrim($data['endpoint_url'], '/'),
            'status' => isset($data['status']) ? $data['status'] : 'active',
        ];
        if (!empty($data['api_key'])) {
            $update['api_key'] = $data['api_key'];
        }
        if (!empty($data['inbound_secret'])) {
            $update['inbound_secret'] = $data['inbound_secret'];
        }
        $this->db->where('id', $id);
        return $this->db->update(db_prefix() . 'openclaw_gateways', $update);
    }

    public function delete_gateway($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete(db_prefix() . 'openclaw_gateways');
    }

    public function get_gateway_agents($gatewayId)
    {
        $this->db->where('gateway_id', $gatewayId);
        $this->db->order_by('id', 'DESC');
        return $this->db->get(db_prefix() . 'openclaw_gateway_agents')->result_array();
    }

    public function upsert_gateway_agent($gatewayId, $externalAgentId, $externalAgentName)
    {
        $this->db->where('gateway_id', $gatewayId);
        $this->db->where('external_agent_id', $externalAgentId);
        $existing = $this->db->get(db_prefix() . 'openclaw_gateway_agents')->row_array();

        if ($existing) {
            $this->db->where('id', $existing['id']);
            $this->db->update(db_prefix() . 'openclaw_gateway_agents', [
                'external_agent_name' => $externalAgentName,
            ]);
            return $existing['id'];
        }

        $this->db->insert(db_prefix() . 'openclaw_gateway_agents', [
            'gateway_id' => $gatewayId,
            'external_agent_id' => $externalAgentId,
            'external_agent_name' => $externalAgentName,
            'status' => 'discovered',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->db->insert_id();
    }

    public function link_gateway_agent_staff($gatewayAgentId, $staffId, $apiUserId = null)
    {
        $update = [
            'staff_id' => $staffId,
            'status' => 'onboarded',
        ];
        if ($apiUserId !== null) {
            $update['api_user_id'] = $apiUserId;
        }
        $this->db->where('id', $gatewayAgentId);
        return $this->db->update(db_prefix() . 'openclaw_gateway_agents', $update);
    }

    public function discover_agents($gatewayId)
    {
        $gateway = $this->get_gateways($gatewayId);
        if (!$gateway || $gateway['status'] !== 'active') {
            return ['success' => false, 'message' => 'Gateway not found or inactive', 'agents' => []];
        }

        $url = $gateway['endpoint_url'] . '/agents';
        $response = $this->request($url, 'GET', null, $gateway['api_key']);
        if (!$response['success']) {
            return ['success' => false, 'message' => $response['message'], 'agents' => []];
        }

        $body = json_decode($response['body'], true);
        $agents = isset($body['agents']) && is_array($body['agents']) ? $body['agents'] : [];

        foreach ($agents as $agent) {
            $externalId = isset($agent['id']) ? (string) $agent['id'] : '';
            $externalName = isset($agent['name']) ? $agent['name'] : 'Unknown Agent';
            if ($externalId !== '') {
                $this->upsert_gateway_agent($gatewayId, $externalId, $externalName);
            }
        }

        return ['success' => true, 'agents' => $this->get_gateway_agents($gatewayId)];
    }

    public function dispatch_webhook($gatewayId, $eventType, $payload)
    {
        $gateway = $this->get_gateways($gatewayId);
        if (!$gateway || $gateway['status'] !== 'active') {
            return ['success' => false, 'message' => 'Gateway unavailable'];
        }

        $body = json_encode([
            'event' => $eventType,
            'payload' => $payload,
            'sent_at' => date('c'),
        ]);

        $url = $gateway['endpoint_url'] . '/webhooks/perfex';
        $response = $this->request($url, 'POST', $body, $gateway['api_key']);

        return $response;
    }

    public function run_heartbeat()
    {
        $gateways = $this->get_gateways();
        foreach ($gateways as $gateway) {
            if ($gateway['status'] !== 'active') {
                continue;
            }
            $response = $this->request($gateway['endpoint_url'] . '/health', 'GET', null, $gateway['api_key']);
            if ($response['success']) {
                $this->db->where('id', $gateway['id']);
                $this->db->update(db_prefix() . 'openclaw_gateways', [
                    'last_heartbeat' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    private function request($url, $method = 'GET', $body = null, $apiKey = null)
    {
        $ch = curl_init();
        $headers = ['Content-Type: application/json'];
        if (!empty($apiKey)) {
            $headers[] = 'X-OpenClaw-Key: ' . $apiKey;
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $result = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'message' => $error, 'body' => null, 'status_code' => $status];
        }

        return ['success' => $status >= 200 && $status < 300, 'message' => 'ok', 'body' => $result, 'status_code' => $status];
    }
}
