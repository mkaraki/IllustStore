<script setup lang="ts">
import {Link} from "@inertiajs/vue3";

defineProps({
  tagData: Object,
})
</script>

<template>
  <div>
    <span class="query-info-item">TagId: {{ tagData?.id }}</span>
    <span class="query-info-item"><Link :href="`/tag/${ tagData?.id }/edit`">Edit</Link></span>
    <span class="query-info-item" v-if="tagData?.tagDanbooru !== null">
      Danbooru: <a :href="`https://danbooru.donmai.us/wiki_pages/${ encodeURIComponent(tagData?.tagDanbooru) }`">{{ tagData?.tagDanbooru }}</a>
    </span>
    <span class="query-info-item" v-if="tagData?.tagPixivJpn !== null">
      Pixiv: <a :href="`https://www.pixiv.net/tags/${ encodeURIComponent(tagData?.tagPixivJpn) }`">{{ tagData?.tagPixivJpn }}</a>
      <span v-if="tagData?.tagPixivEng != null">[{{ tagData?.tagPixivEng }}]</span>
    </span>
  </div>
  <dl>
    <template v-if="tagData?.description != null">
      <dt>Tag Description</dt>
      <dd><pre>{{tagData?.description}}</pre></dd>
    </template>
    <template v-if="tagData?.taggingNote != null">
      <dt>Tagging Note</dt>
      <dd><pre>{{tagData?.taggingNote}}</pre></dd>
    </template>
  </dl>
</template>

<style scoped>

</style>