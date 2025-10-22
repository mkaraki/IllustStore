<script setup lang="ts">
import Paginate from "@/components/paginate.vue";
import TagInfo from "@/components/tag-info.vue";
import ImageThumb from "@/components/image-thumb.vue";
const props = defineProps({
  searchParam: String,
  images: Object,
  tagData: Object,
});
console.log(props.images);
</script>

<template>
  <header class="is-shallow p-strip">
    <div class="row">
      <div class="col">
        <h1 class="query-ind query-h1">Query: {{ searchParam }}</h1>
      </div>
    </div>
    <div class="row" v-if="tagData != null">
      <div class="col">
        <tag-info :tagData="tagData"></tag-info>
      </div>
    </div>
  </header>
  <main class="is-shallow p-strip is-dark" v-if="images?.total > 0">
    <div class="row">
      <div class="col u-align-text--center">
        <span v-for="image in images?.data" :key="image.id" class="image-for">
          <ImageThumb :image="image"></ImageThumb>
        </span>
      </div>
    </div>
  </main>
  <div class="p-strip" v-else>
    <div class="row--25-75">
      <div class="u-align--left col">
        <p class="p-heading--4 u-no-margin--bottom">Illustration not available</p>
        <p>Illustration with specified query not found. Try another query or add another illustration.</p>
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
.image-for {
  margin: 5px;
  vertical-align: middle;
}
</style>