<script setup lang="ts">
import TagList from "@/components/tag-list.vue";
import NegativeTagList from "@/components/negative-tag-list.vue";
import {Form, Head, Link} from "@inertiajs/vue3";
import ImageThumb from "@/components/image-thumb.vue";
import TagComplete from "@/components/tag-complete.vue";
import {ref, useTemplateRef} from "vue";

defineProps({
  imageId: Number,
  imageData: Object,
  metadata: Object,
  tags: Array<any>,
  negativeTags: Array<any>,
});

const searchInput = useTemplateRef('searchInput')
const searchModel = ref('')
</script>

<template>
  <Head :title="`Tag Assign - image:${imageId}`"></Head>
  <header class="p-strip is-shallow">
    <div class="row">
      <div class="col">
        <h1 class="query query-h1">Tag Assign: image:{{ imageId }}</h1>
        <Link :href="`/image/${imageId}`">Back to image page</Link>
      </div>
    </div>
  </header>
  <div class="p-strip is-shallow is-dark">
    <div class="row">
      <div class="col u-align-text--center">
        <ImageThumb :image="imageData"></ImageThumb>
      </div>
    </div>
  </div>
  <div class="p-strip is-shallow">
    <div class="row">
      <div class="col">
        <dl>
          <dt>Tags</dt>
          <dd>
            <tag-list :tags="tags" :allowEdit="false" :imageId="imageId" :pendingPaginationNow="0" />
          </dd>
          <dt>Negative Tags</dt>
          <dd><negative-tag-list :tags="negativeTags" :allowEdit="false" :imageId="imageId" :pendingPaginationNow="0" /></dd>
        </dl>
      </div>
    </div>
    <div class="row">
      <div class="col">
        <hr />
        <Form :action="`/image/${imageId}/tag/new`" method="post">
          <label for="assignNewTags">New Tags</label>
          <input type="text" id="assignNewTags" name="newTags" aria-describedby="assignNewTagsRule" required v-model="searchModel" ref="searchInput">
          <p class="p-form-help-text" id="assignNewTagsRule">
            Space separated.
          </p>
          <button type="submit" class="p-button has-icon"><i class="p-icon--plus"></i><span>Assign</span></button>
        </Form>
        <TagComplete :input="searchModel" :inputRef="searchInput"></TagComplete>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>