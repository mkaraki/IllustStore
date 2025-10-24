<script setup lang="ts">
import TagList from "@/components/tag-list.vue";
import NegativeTagList from "@/components/negative-tag-list.vue";
import {Deferred, Head, Link} from "@inertiajs/vue3";
import ImageHashes from "@/components/image-hashes.vue";

defineProps({
  imageId: Number,
  imageData: Object,
  metadata: Object,
  tags: Array<any>,
  negativeTags: Array<any>,
});
</script>

<template>
  <Head :title="`Image #${imageId}`"></Head>
  <header class="p-strip is-shallow">
    <div class="row">
      <div class="col">
        <h1 class="query query-h1">Image #{{ imageId }}</h1>
      </div>
    </div>
  </header>
  <div class="p-strip is-shallow is-dark">
    <div class="row">
      <div class="col">
        <a :href="`${ $page.props.imgServerBase }/image/${imageId}/raw`">
          <img :src="`${ $page.props.imgServerBase }/image/${imageId}/large`" alt="Image" loading="lazy" class="viewer-img" />
        </a>
      </div>
    </div>
  </div>
  <div class="p-strip is-shallow">
    <div class="row">
      <div class="col">
        <dl>
          <dt>Tools</dt>
          <dd>
            <Link :href="`/image/${imageId}/duplicate`">dup check</Link> |
            <Link :href="`/image/${imageId}/neighbor`">similar images</Link>
          </dd>
          <dt>Tags</dt>
          <dd><tag-list :tags="tags" :allowEdit="true" :imageId="imageId" :pendingPaginationNow="0" /></dd>
          <dt>Negative Tags</dt>
          <dd><negative-tag-list :tags="negativeTags" :allowEdit="true" :imageId="imageId" :pendingPaginationNow="0" /></dd>
          <ImageHashes :image-data="imageData"></ImageHashes>
          <Deferred data="metadata">
            <template #fallback>
              <dt>Metadata Provider</dt>
              <dd><i class="p-icon--spinner u-animation--spin"></i> Loading</dd>
            </template>

            <dt>Metadata Provider</dt>
            <dd>{{ metadata?.metadataProviderName ?? 'No Provider' }}</dd>
            <template v-if="metadata?.metadataProviderUrl != null">
              <dt>Provider's content page</dt>
              <dd>
                <a :href="metadata.metadataProviderUrl" target="_blank" rel="noreferrer noopener">{{metadata.metadataProviderUrl}}</a>
              </dd>
            </template>
            <template v-if="metadata?.apiMetadata != null">
              <template v-for="(v, k) in metadata.apiMetadata" :key="k">
                <dt>API Metadata: {{ k }}</dt>
                <dd>{{ v }}</dd>
              </template>
            </template>
            <template v-if="metadata?.metadataSourceUrl != null">
              <dt>Source URL</dt>
              <dd>
                <a :href="metadata.metadataSourceUrl" target="_blank" rel="noreferrer noopener">{{metadata.metadataSourceUrl}}</a>
              </dd>
            </template>
          </Deferred>
          <dt>Server Path</dt>
          <dd>{{ imageData?.path ?? 'No path info' }}</dd>
        </dl>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>