<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Openclaw_events_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function log_event($data)
    {
        $insert = [
            'event_type' => isset($data['event_type']) ? $data['event_type'] : 'unknown',
            'source' => isset($data['source']) ? $data['source'] : 'system',
            'staff_id' => isset($data['staff_id']) ? $data['staff_id'] : null,
            'rel_type' => isset($data['rel_type']) ? $data['rel_type'] : null,
            'rel_id' => isset($data['rel_id']) ? $data['rel_id'] : null,
            'gateway_id' => isset($data['gateway_id']) ? $data['gateway_id'] : null,
            'payload' => isset($data['payload']) ? json_encode($data['payload']) : null,
            'response_code' => isset($data['response_code']) ? $data['response_code'] : null,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->insert(db_prefix() . 'openclaw_event_log', $insert);
        return $this->db->insert_id();
    }

    public function get_events($filters = [])
    {
        $this->db->from(db_prefix() . 'openclaw_event_log');
        if (isset($filters['event_type'])) {
            $this->db->where('event_type', $filters['event_type']);
        }
        if (isset($filters['source'])) {
            $this->db->where('source', $filters['source']);
        }
        if (isset($filters['gateway_id'])) {
            $this->db->where('gateway_id', $filters['gateway_id']);
        }
        $this->db->order_by('id', 'DESC');
        if (isset($filters['limit'])) {
            $this->db->limit((int) $filters['limit']);
        }
        return $this->db->get()->result_array();
    }
}
