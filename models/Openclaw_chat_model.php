<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Openclaw_chat_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('openclaw/openclaw');
    }

    public function get_threads($filters = [])
    {
        $this->db->from(db_prefix() . 'openclaw_chat_threads');

        if (isset($filters['rel_type'])) {
            $this->db->where('rel_type', $filters['rel_type']);
        }
        if (array_key_exists('rel_id', $filters)) {
            $this->db->where('rel_id', $filters['rel_id']);
        }
        if (isset($filters['gateway_id'])) {
            $this->db->where('gateway_id', $filters['gateway_id']);
        }
        if (isset($filters['status'])) {
            $this->db->where('status', $filters['status']);
        }

        $this->db->order_by('last_message_at', 'DESC');
        if (isset($filters['limit'])) {
            $this->db->limit((int) $filters['limit']);
        }

        return $this->db->get()->result_array();
    }

    public function get_thread($threadId)
    {
        $this->db->where('id', $threadId);
        return $this->db->get(db_prefix() . 'openclaw_chat_threads')->row_array();
    }

    public function create_thread($data)
    {
        $relType = isset($data['rel_type']) ? $data['rel_type'] : 'general';
        $relId = isset($data['rel_id']) ? (int) $data['rel_id'] : null;
        $title = !empty($data['title']) ? $data['title'] : openclaw_thread_title($relType, $relId);

        $insert = [
            'rel_type' => $relType,
            'rel_id' => $relId,
            'gateway_id' => isset($data['gateway_id']) ? (int) $data['gateway_id'] : null,
            'title' => $title,
            'external_thread_id' => isset($data['external_thread_id']) ? $data['external_thread_id'] : null,
            'status' => isset($data['status']) ? $data['status'] : 'active',
            'created_by' => isset($data['created_by']) ? (int) $data['created_by'] : get_staff_user_id(),
            'created_at' => date('Y-m-d H:i:s'),
            'last_message_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->insert(db_prefix() . 'openclaw_chat_threads', $insert);
        return $this->db->insert_id();
    }

    public function get_messages($threadId, $afterId = null)
    {
        $this->db->from(db_prefix() . 'openclaw_chat_messages');
        $this->db->where('thread_id', $threadId);
        if ($afterId !== null) {
            $this->db->where('id >', (int) $afterId);
        }
        $this->db->order_by('id', 'ASC');
        return $this->db->get()->result_array();
    }

    public function add_message($data)
    {
        $insert = [
            'thread_id' => (int) $data['thread_id'],
            'sender_type' => $data['sender_type'],
            'sender_id' => isset($data['sender_id']) ? (int) $data['sender_id'] : null,
            'instance_id' => isset($data['instance_id']) ? $data['instance_id'] : null,
            'message' => $data['message'],
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
            'external_message_id' => isset($data['external_message_id']) ? $data['external_message_id'] : null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->insert(db_prefix() . 'openclaw_chat_messages', $insert);
        $id = $this->db->insert_id();

        $this->db->where('id', (int) $data['thread_id']);
        $this->db->update(db_prefix() . 'openclaw_chat_threads', [
            'last_message_at' => date('Y-m-d H:i:s'),
        ]);

        return $id;
    }

    public function create_or_append_message($threadData, $messageData)
    {
        $threadId = null;
        if (!empty($threadData['thread_id'])) {
            $threadId = (int) $threadData['thread_id'];
        } else {
            $threads = $this->get_threads([
                'rel_type' => $threadData['rel_type'],
                'rel_id' => $threadData['rel_id'],
                'status' => 'active',
                'limit' => 1,
            ]);
            if (!empty($threads)) {
                $threadId = (int) $threads[0]['id'];
            }
        }

        if (!$threadId) {
            $threadId = $this->create_thread($threadData);
        }

        $messageData['thread_id'] = $threadId;
        $messageId = $this->add_message($messageData);
        return ['thread_id' => $threadId, 'message_id' => $messageId];
    }
}
