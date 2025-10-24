<script setup lang="ts">
import ImageThumb from "@/components/image-thumb.vue";
import TagList from "@/components/tag-list.vue";
import NegativeTagList from "@/components/negative-tag-list.vue";
import Paginate from "@/components/paginate.vue";
defineProps({
  images: Object,
});
</script>

<template>
  <div class="is-shallow p-strip">
    <header class="row">
      <div class="col">
        <h1 class="query-ind query-h1">Pending Tags</h1>
      </div>
    </header>
  </div>
  <div class="is-shallow p-strip" v-if="images?.total > 0">
    <div class="row">
      <div class="col">
        <table>
          <thead>
            <tr>
              <th class="image-side-td">Image</th>
              <th>Tags</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="image in images?.data" :key="image.id">
              <td class="u-align--center image-side-td">
                <div class="image-thumb-container">
                  <ImageThumb :image="image"></ImageThumb>
                </div>
              </td>
              <td>
                <dl>
                  <dt>Tags</dt>
                  <dd><tag-list :tags="image.tags" :allowEdit="true" :imageId="image.id" :pendingPaginationNow="0"></tag-list></dd>
                  <dt>Negative Tags</dt>
                  <dd><negative-tag-list :tags="image.negative_tags" :allowEdit="true" :imageId="image.id" :pendingPaginationNow="0"></negative-tag-list></dd>
                </dl>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="p-strip" v-else>
    <div class="row--25-75">
      <div class="u-align--left col">
        <p class="p-heading--4 u-no-margin--bottom">Tag pending illustration not available</p>
        <p>No suggested tags by AI. Try manually tagging if you aren't satisfied.</p>
      </div>
    </div>
  </div>
  <div class="is-shallow p-strip">
    <div class="row">
      <div class="col">
        <paginate :paginate="images"></paginate>
      </div>
    </div>
  </div>
</template>

<style scoped>
@media (min-width: 500px) {
  .image-side-td {
    width: calc(200px + 1rem);
  }
}
.image-side-td {
  height: 100%;
  max-width: calc(200px + 1rem);
  padding: 0.5rem;
  margin: 0;
  position: relative;
}
.image-thumb-container {
  height: calc(100% - 1rem);
  width: calc(100% - 1rem);
  display: flex;
  justify-content: center;
  align-items: center;
  position: absolute;
}
</style>