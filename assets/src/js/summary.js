jQuery(document).ready(function($){
    const btn = $('#wpai-generate-summary');
    if (!btn.length) return;

    btn.on('click', function(e){
        e.preventDefault();
        const postId = btn.data('post-id');
        if (!postId) return;
        btn.prop('disabled', true).text(wpAIGenerateSummary.i18n.generating);
        $.post(wpAIGenerateSummary.ajaxurl, {
            action: 'wp_ai_assistant_generate_summary',
            post_id: postId,
            _ajax_nonce: wpAIGenerateSummary.nonce
        }, function(res){
            btn.prop('disabled', false);
            if (res.success && res.data && res.data.summary) {
                $('#wpai-summary-text').text(res.data.summary);
                btn.text(wpAIGenerateSummary.i18n.regenerate);
            } else {
                alert(wpAIGenerateSummary.i18n.error);
                btn.text(wpAIGenerateSummary.i18n.generate);
            }
        }).fail(function(){
            btn.prop('disabled', false);
            alert(wpAIGenerateSummary.i18n.error);
            btn.text(wpAIGenerateSummary.i18n.generate);
        });
    });
});

