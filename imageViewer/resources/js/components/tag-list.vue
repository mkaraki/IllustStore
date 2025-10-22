<script setup lang="ts">
import {Form, Link, router} from "@inertiajs/vue3";

defineProps({
  tags: Array<any>,
  allowEdit: Boolean,
  imageId: Number,
  pendingPaginationNow: Number,
});
</script>

<template>
  <ul class="forever-ul">
    <li v-for="tag in tags" :key="tag.tagId">
      <Link :href="`/tag/${tag.tagId}`">{{ tag.tagName }}</Link>
      <template v-if="allowEdit">
        <template v-if="tag.autoAssigned == 1">
          <Form :action="`/image/${ imageId }/tag/${tag.tagId}`" method="put" :options="{ only: ['tags'] }">
            <input type="hidden" name="pending" :value="pendingPaginationNow" v-if="pendingPaginationNow != 0">
            <button type="submit" class="has-icon is-small is-inline p-button--base tag-action-btn"><i class="p-icon--thumbs-up">Approve</i></button>
          </Form>
        </template>
        <template v-else>
          <span class="tag-action-btn"><i class="p-icon--conflict-resolution-grey">Verified</i></span>
        </template>
        <Form :action="`/image/${ imageId }/tag/${tag.tagId}`" method="delete" :options="{ only: ['tags', 'negativeTags'] }">
          <input type="hidden" name="pending" :value="pendingPaginationNow" v-if="pendingPaginationNow != 0">
          <button type="submit" class="has-icon is-small is-inline p-button--base tag-action-btn"><i class="p-icon--close">Remove</i></button>
        </Form>
      </template>
    </li>
    <li v-if="allowEdit">
      <Link :href="`/image/${imageId}/tag/new`" class="tag-action-btn"><i class="p-icon--plus">Assign new tag</i></Link>
    </li>
  </ul>
</template>

<style scoped>
.tag-action-btn {
  margin-left: 5px;
}
button.tag-action-btn {
  padding: 0 5px;
}
</style>