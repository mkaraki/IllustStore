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
        <Form :action="`/image/${ imageId }/tag/${tag.tagId}`" method="put" :options="{ only: ['tags', 'negativeTags'] }">
          <input type="hidden" name="pending" :value="pendingPaginationNow" v-if="pendingPaginationNow != 0">
          <button type="submit" class="has-icon is-small is-inline p-button--base tag-action-btn"><i class="p-icon--plus">Add</i></button>
        </Form>
      </template>
    </li>
    <li>*</li>
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
