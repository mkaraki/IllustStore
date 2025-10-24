<script setup lang="ts">
import {Link} from "@inertiajs/vue3";

defineProps({
  imageData: Object
});
</script>

<template>
  <template v-if="imageData?.aHash != null">
    <dt>aHash</dt>
    <dd>
      <Link :href="`/search/aHash/${imageData.aHash}`"><code>{{ imageData.aHash }}</code></Link>
      &thinsp;(<Link :href="`/search/aHash/${imageData.aHash}?exact=1`">exact</Link>)
    </dd>
  </template>
  <template v-if="imageData?.pHash != null">
    <dt>pHash</dt>
    <dd>
      <Link :href="`/search/pHash/${imageData.pHash}`"><code>{{ imageData.pHash }}</code></Link>
      &thinsp;(<Link :href="`/search/pHash/${imageData.pHash}?exact=1`">exact</Link>)
    </dd>
  </template>
  <template v-if="imageData?.dHash != null">
    <dt>dHash</dt>
    <dd>
      <Link :href="`/search/dHash/${imageData.dHash}`"><code>{{ imageData.dHash }}</code></Link>
    &thinsp;(<Link :href="`/search/dHash/${imageData.dHash}?exact=1`">exact</Link>)
    </dd>
  </template>
  <template v-if="imageData?.colorHash != null">
    <dt>colorHash</dt>
    <dd>
      <Link :href="`/search/colorHash/${imageData.colorHash}`"><code>{{ imageData.colorHash }}</code></Link>
      &thinsp;(<Link :href="`/search/colorHash/${imageData.colorHash}?exact=1`">exact</Link>)
    </dd>
  </template>
</template>

<style scoped>

</style>