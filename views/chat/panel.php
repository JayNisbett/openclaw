<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$compact = isset($compact) ? (bool) $compact : false;
$summary = isset($summary) ? (bool) $summary : false;
if (!isset($rel_type)) {
    if (isset($project) && isset($project->id)) {
        $rel_type = 'project';
        $rel_id = $project->id;
    } elseif (isset($client) && isset($client->userid)) {
        $rel_type = 'client';
        $rel_id = $client->userid;
    } elseif (isset($task) && isset($task->id)) {
        $rel_type = 'task';
        $rel_id = $task->id;
    } else {
        $rel_type = 'general';
        $rel_id = null;
    }
}
if (!isset($threads)) {
    $CI = &get_instance();
    $CI->load->model('openclaw/Openclaw_chat_model');
    $threads = $CI->Openclaw_chat_model->get_threads([
        'rel_type' => $rel_type,
        'rel_id' => $rel_id,
        'status' => 'active',
        'limit' => 20,
    ]);
}
?>
<div class="panel panel-default mtop15">
    <div class="panel-heading">
        <strong><?php echo _l('openclaw_ai_chat'); ?></strong>
        <?php if (!empty($rel_type)) { ?>
            <small class="text-muted">[<?php echo html_escape($rel_type); ?> #<?php echo html_escape((string) $rel_id); ?>]</small>
        <?php } ?>
        <?php if (!$summary && !$compact) { ?>
            <a class="pull-right" href="<?php echo admin_url('openclaw/chat'); ?>"><?php echo _l('openclaw_chat'); ?></a>
        <?php } ?>
    </div>
    <div class="panel-body">
        <?php if (!empty($threads)) { ?>
            <ul class="list-unstyled">
                <?php foreach ($threads as $thread) { ?>
                    <li>
                        <a href="<?php echo admin_url('openclaw/chat/' . (int) $thread['id']); ?>">
                            <?php echo html_escape($thread['title']); ?>
                        </a>
                        <small class="text-muted pull-right"><?php echo _dt($thread['last_message_at']); ?></small>
                    </li>
                <?php } ?>
            </ul>
        <?php } else { ?>
            <p class="text-muted mbot0"><?php echo _l('openclaw_no_threads_found') ?: 'No threads yet.'; ?></p>
        <?php } ?>
        <?php if (!$summary) { ?>
            <hr />
            <form method="post" action="<?php echo admin_url('openclaw/chat_create_thread'); ?>">
                <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                <input type="hidden" name="rel_type" value="<?php echo html_escape((string) $rel_type); ?>">
                <input type="hidden" name="rel_id" value="<?php echo html_escape((string) $rel_id); ?>">
                <button class="btn btn-default btn-sm"><?php echo _l('openclaw_new_thread'); ?></button>
            </form>
        <?php } ?>
    </div>
</div>
