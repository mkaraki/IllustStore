<?php
require_once __DIR__ . '/../../_config.php';
if (!empty(SENTRY_DSN)) : ?>
<script
  src="https://browser.sentry-cdn.com/10.19.0/bundle.tracing.replay.min.js"
  integrity="sha384-9jBRZL7VtE2wKJdi3L8yhjcqeXGUASfuWtw7t6fTPhWBrF8Gcom99en+s9OiqBUA"
  crossorigin="anonymous"
></script>
<script>
Sentry.init({
  dsn: "<?= trim(SENTRY_DSN) ?>",
  sendDefaultPii: true,
  integrations: [
    Sentry.replayIntegration({
      maskAllText: false,
      blockAllMedia: false,
    })
  ],
  replaysSessionSampleRate: 0.1,
  replaysOnErrorSampleRate: 1.0
});
</script>
<?php endif; ?>