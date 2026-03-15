<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php foreach ($messages as $message) { ?>
    <div class="media" data-message-id="<?php echo (int) $message['id']; ?>">
        <div class="media-body">
            <p class="mtop5 mbot5"><?php echo nl2br(html_escape($message['message'])); ?></p>
            <small class="text-muted">
                <?php echo html_escape($message['sender_type']); ?>
                <?php if (!empty($message['sender_id'])) { ?>
                    #<?php echo (int) $message['sender_id']; ?>
                <?php } ?>
                <?php if (!empty($message['instance_id'])) { ?>
                    (instance: <?php echo html_escape($message['instance_id']); ?>)
                <?php } ?>
                - <?php echo _dt($message['created_at']); ?>
            </small>
        </div>
    </div>
    <hr />
<?php } ?>
