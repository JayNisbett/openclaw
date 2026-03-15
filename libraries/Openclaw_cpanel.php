<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Openclaw_cpanel
{
    protected $host;
    protected $username;
    protected $token;
    protected $domain;

    public function __construct()
    {
        $this->host = get_option('openclaw_cpanel_host');
        $this->username = get_option('openclaw_cpanel_username');
        $this->token = get_option('openclaw_cpanel_token');
        $this->domain = get_option('openclaw_cpanel_domain');
    }

    public function is_configured()
    {
        return !empty($this->host) && !empty($this->username) && !empty($this->token) && !empty($this->domain);
    }

    public function create_email($localPart, $password, $quota = 1024)
    {
        if (!$this->is_configured()) {
            return ['success' => false, 'message' => 'cPanel credentials are not configured'];
        }

        $query = http_build_query([
            'email' => $localPart,
            'password' => $password,
            'domain' => $this->domain,
            'quota' => (int) $quota,
        ]);

        $url = 'https://' . $this->host . ':2083/execute/Email/add_pop?' . $query;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: cpanel ' . $this->username . ':' . $this->token,
        ]);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'message' => $error];
        }

        return [
            'success' => $status >= 200 && $status < 300,
            'status_code' => $status,
            'body' => $body,
            'message' => $status >= 200 && $status < 300 ? 'ok' : 'cpanel request failed',
            'email' => $localPart . '@' . $this->domain,
        ];
    }
}
