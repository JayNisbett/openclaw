<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-6">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4><?php echo html_escape($agent['full_name']); ?></h4>
                        <p><strong>Staff ID:</strong> <?php echo (int) $agent['staffid']; ?></p>
                        <p><strong>Email:</strong> <?php echo html_escape($agent['email']); ?></p>
                        <p><strong>Agent Type:</strong> <?php echo html_escape((string) $agent['agent_type']); ?></p>
                        <p><strong>Autonomy Tier:</strong> <?php echo html_escape((string) $agent['autonomy_tier']); ?></p>
                        <p><strong>Gateway:</strong> <?php echo html_escape((string) $agent['gateway_id']); ?></p>
                        <p><strong>API User ID:</strong> <?php echo html_escape((string) $agent['api_user_id']); ?></p>
                        <a class="btn btn-info" href="<?php echo admin_url('openclaw/create_agent_api_key/' . (int) $agent['staffid']); ?>"><?php echo _l('openclaw_create_api_key'); ?></a>
                        <a class="btn btn-warning" href="<?php echo admin_url('openclaw/revoke_agent_api_key/' . (int) $agent['staffid']); ?>"><?php echo _l('openclaw_revoke_api_key'); ?></a>
                        <a class="btn btn-default" href="<?php echo admin_url('openclaw/download_agent_spec/' . (int) $agent['staffid']); ?>">Download AGENTS.md</a>
                        <a class="btn btn-default" href="<?php echo admin_url('openclaw/download_perfex_skill/' . (int) $agent['staffid']); ?>">Download Perfex Skill</a>
                        <a class="btn btn-default" href="<?php echo admin_url('openclaw/download_mcp_config/' . (int) $agent['staffid']); ?>">Download MCP Config</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4>Assigned Tasks</h4>
                        <ul class="list-unstyled">
                            <?php foreach ($tasks as $task) { ?>
                                <li><a href="<?php echo admin_url('tasks/view/' . (int) $task['id']); ?>">#<?php echo (int) $task['id']; ?> <?php echo html_escape($task['name']); ?></a></li>
                            <?php } ?>
                            <?php if (empty($tasks)) { ?><li>No tasks assigned.</li><?php } ?>
                        </ul>
                        <hr />
                        <h4>Project Memberships</h4>
                        <ul class="list-unstyled">
                            <?php foreach ($projects as $project) { ?>
                                <li><a href="<?php echo admin_url('projects/view/' . (int) $project['id']); ?>">#<?php echo (int) $project['id']; ?> <?php echo html_escape($project['name']); ?></a></li>
                            <?php } ?>
                            <?php if (empty($projects)) { ?><li>No projects assigned.</li><?php } ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
