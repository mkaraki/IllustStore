<script setup lang="ts">
import {Ref, ref, useTemplateRef, watch} from "vue";
import * as Sentry from "@sentry/vue";

const props = defineProps({
  input: String,
  inputRef: HTMLInputElement,
});

const searchTagAutoComplete = useTemplateRef('searchTagAutoComplete');
const abortSignal: Ref<AbortController|null> = ref(null);

watch(() => props.input, (newInput) => {
  console.trace('[TagComplete][watch][props.input] Called. New: ', newInput)

  if (newInput === undefined || props.inputRef === undefined) {
    console.trace('[TagComplete][watch][props.input] newInput', newInput, 'props.inputRef', props.inputRef);
    return;
  }

  const sQ = props.inputRef;
  const sQWords = sQ.value.split(' ');

  const sendWord = sQWords[sQWords.length - 1];
  const sendBody = JSON.stringify({
    'w': sendWord
  });
  if (sendWord.length < 3)
    return;
  let sentryTraceHeader = undefined;
  let sentryBaggageHeader = undefined;
  if (typeof Sentry !== 'undefined') {
    const traceData = Sentry.getTraceData();
    console.trace("[TagComplete][watch][props.input] Preparing to sent sentry trace data: ", traceData);
    sentryTraceHeader = traceData['sentry-trace'] ?? undefined;
    sentryBaggageHeader = traceData['baggage'] ?? undefined;
  }

  if (abortSignal.value !== null) {
    abortSignal.value.abort('New input received.');
  }
  const abortController = new AbortController();
  abortSignal.value = abortController;

  fetch('/tag/complete', {
    'method': 'POST',
    body: sendBody,
    signal: abortController.signal,
    headers: {
      "Content-Type": "application/json",
      "baggage": sentryBaggageHeader ?? '',
      "sentry-trace": sentryTraceHeader ?? '',
    },
  })
      .then(d => d.json())
      .then(d => {
        if (searchTagAutoComplete.value === undefined || searchTagAutoComplete.value === null) return;

        searchTagAutoComplete.value.innerHTML = '';
        d['sw'].forEach((v: any) => {
          const acObj = document.createElement('a');
          acObj.href = 'javascript:void(0)';
          acObj.innerText = v;
          acObj.onclick = () => {
            const sQWords = sQ.value.split(' ');
            sQWords.pop();
            sQWords.push(v);
            sQ.value = sQWords.join(' ') + ' ';
            sQ.focus();
          }
          searchTagAutoComplete.value?.appendChild(acObj);
        })
      })
      .catch(e => {
        if (typeof e.name === 'undefined' || e.name === 'AbortError') {
          // This is not an error.
          return;
        }
        Sentry.captureException(e);
        console.error(e);
        if (searchTagAutoComplete.value === undefined || searchTagAutoComplete.value === null) return;
        searchTagAutoComplete.value.innerHTML = 'Failed to retrieve completion';
      })

});
</script>

<template>
  <div id="search-tag-auto-complete" ref="searchTagAutoComplete"></div>
</template>

<style scoped>

</style>