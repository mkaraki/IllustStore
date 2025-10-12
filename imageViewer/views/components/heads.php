<?php
require_once __DIR__ . '/../../_config.php';
if (!empty(SENTRY_DSN)) : ?>
<script
  src="https://browser.sentry-cdn.com/10.19.0/bundle.tracing.replay.feedback.min.js"
  integrity="sha384-92N5u8Kf3n6PjH0F76yRkyEvEF5R0At4k2Ptj7eLJQqdHD3nhhFAezd3HbpbSNo8"
  crossorigin="anonymous"
></script>
<script>
console.debug('Sentry enabled');
Sentry.init({
  dsn: "<?= trim(SENTRY_DSN) ?>",
  sendDefaultPii: true,
  integrations: [
    Sentry.replayIntegration({
      maskAllText: true,
      blockAllMedia: true,
    }),
    Sentry.browserTracingIntegration(),
    // Sentry.browserProfilingIntegration(),
    // Sentry.consoleLoggingIntegration(),
  ],
  tracesSampleRate: 0.5,
  tracePropagationTargets: <?= json_encode([$_SERVER['SERVER_NAME'], IMG_SERVER_BASE]); ?>,
  replaysSessionSampleRate: 1.0,
  replaysOnErrorSampleRate: 1.0,
  // profilesSampleRate: 0.3,
  // enableLogs: true,
});
</script>
<?php endif; ?>
