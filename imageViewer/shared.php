<?php
require_once __DIR__ . '/vendor/autoload.php';

function createAndStartWebTransaction($name) {
    $transactionContext = \Sentry\Tracing\TransactionContext::make()
        ->setName($name)
        ->setOp('http.server');
    $transaction = \Sentry\startTransaction($transactionContext);
    \Sentry\SentrySdk::getCurrentHub()->setSpan($transaction);
    return $transaction;
}

function createAndStartDbSpan($transaction, $sql) {
    # See: https://docs.sentry.io/product/insights/backend/queries/
    $spanContext = \Sentry\Tracing\SpanContext::make()
        ->setOp('db.query')
        ->setDescription($sql)
        ->setData([
            'db.system' => 'mariadb'
        ]);
    $transaction->startChild($spanContext);
    \Sentry\SentrySdk::getCurrentHub()->setSpan($spanContext);
}

function createAndStartRenderSpan($transaction) {
    # See: https://develop.sentry.dev/sdk/telemetry/traces/span-operations/
    $spanContext = \Sentry\Tracing\SpanContext::make()
        ->setOp('view.render');
    $transaction->startChild($spanContext);
    \Sentry\SentrySdk::getCurrentHub()->setSpan($spanContext);
}

function finishSpanAndReturn($transaction, $span) {
    $span->finish();
    \Sentry\SentrySdk::getCurrentHub()->setSpan($transaction);
}

