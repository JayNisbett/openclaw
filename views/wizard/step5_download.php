<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="panel_s">
            <div class="panel-body">
                <h4>Step 5: <?php echo _l('openclaw_wizard_download'); ?></h4>
                <p>Download generated agent spec files and MCP schema references.</p>
                <ul>
                    <li>MCP schema URL: <code><?php echo site_url('api/openclaw/schema'); ?></code></li>
                    <li>Agents can authenticate with their own <code>authtoken</code> generated in onboarding.</li>
                </ul>
                <h5>Agent file downloads</h5>
                <?php
                $this->db->where('fieldto', 'staff');
                $this->db->where('name', 'openclaw_is_agent');
                $cf = $this->db->get(db_prefix() . 'customfields')->row();
                if ($cf) {
                    $this->db->select('s.staffid,s.firstname,s.lastname');
                    $this->db->from(db_prefix() . 'staff s');
                    $this->db->join(db_prefix() . 'customfieldsvalues v', 'v.relid=s.staffid AND v.fieldto="staff" AND v.fieldid=' . (int) $cf->id . ' AND v.value="1"', 'inner');
                    $agents = $this->db->get()->result_array();
                } else {
                    $agents = [];
                }
                ?>
                <?php if (!empty($agents)) { ?>
                    <ul>
                        <?php foreach ($agents as $agent) { ?>
                            <li>
                                <?php echo html_escape(trim($agent['firstname'] . ' ' . $agent['lastname'])); ?>
                                - <a href="<?php echo admin_url('openclaw/download_agent_spec/' . (int) $agent['staffid']); ?>">AGENTS.md</a>
                                | <a href="<?php echo admin_url('openclaw/download_perfex_skill/' . (int) $agent['staffid']); ?>">Perfex Skill</a>
                                | <a href="<?php echo admin_url('openclaw/download_mcp_config/' . (int) $agent['staffid']); ?>">MCP Config</a>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } else { ?>
                    <p class="text-muted">No onboarded agent staff found yet.</p>
                <?php } ?>
                <a href="<?php echo admin_url('openclaw/agents'); ?>" class="btn btn-info">Open Agents</a>
                <a href="<?php echo admin_url('openclaw/chat'); ?>" class="btn btn-default">Open Chat Inbox</a>
                <a href="<?php echo admin_url('openclaw/wizard/4'); ?>" class="btn btn-default pull-right">Back</a>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
