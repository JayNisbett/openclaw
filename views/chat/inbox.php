<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-4">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo _l('openclaw_threads'); ?></h4>
                        <hr class="hr-panel-heading" />
                        <form method="post" action="<?php echo admin_url('openclaw/chat_create_thread'); ?>" class="mbot15">
                            <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                            <div class="row">
                                <div class="col-md-4"><?php echo render_input('rel_type', _l('openclaw_rel_type'), 'general'); ?></div>
                                <div class="col-md-4"><?php echo render_input('rel_id', _l('openclaw_rel_id')); ?></div>
                                <div class="col-md-4"><?php echo render_input('title', _l('openclaw_thread')); ?></div>
                            </div>
                            <button class="btn btn-default"><?php echo _l('openclaw_new_thread'); ?></button>
                        </form>
                        <ul class="list-group">
                            <?php foreach ($threads as $thread) { ?>
                                <a href="<?php echo admin_url('openclaw/chat/' . (int) $thread['id']); ?>" class="list-group-item<?php echo !empty($active_thread) && (int) $active_thread['id'] === (int) $thread['id'] ? ' active' : ''; ?>">
                                    <strong><?php echo html_escape($thread['title']); ?></strong>
                                    <br />
                                    <small><?php echo html_escape($thread['rel_type']); ?> #<?php echo html_escape((string) $thread['rel_id']); ?></small>
                                </a>
                            <?php } ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <?php $this->load->view('openclaw/chat/thread', ['active_thread' => $active_thread, 'messages' => $messages]); ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
(function () {
    var form = document.getElementById('openclaw-chat-send-form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var data = new FormData(form);
        $.ajax({
            url: '<?php echo admin_url('openclaw/chat_send'); ?>',
            type: 'POST',
            data: data,
            processData: false,
            contentType: false,
            success: function () {
                form.reset();
                refreshMessages();
            }
        });
    });

    function refreshMessages() {
        var threadId = $('input[name="thread_id"]').val();
        if (!threadId) return;
        $('#openclaw-thread-messages').load('<?php echo admin_url('openclaw/chat_messages/'); ?>' + threadId);
    }

    var pollInterval = parseInt('<?php echo get_option('openclaw_chat_poll_interval'); ?>', 10) || 5000;
    setInterval(refreshMessages, pollInterval);
})();
</script>
