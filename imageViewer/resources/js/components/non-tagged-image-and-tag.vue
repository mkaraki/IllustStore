<script setup lang="ts">
import {Form, Link} from "@inertiajs/vue3";
import ImageThumb from "@/components/image-thumb.vue";
const props = defineProps({
  nonTaggedImageAndTag: Object,
})

const imageData = {
  id: props?.nonTaggedImageAndTag?.imageId,
  width: props?.nonTaggedImageAndTag?.width,
  height: props?.nonTaggedImageAndTag?.height,
};
</script>

<template>
  <div class="tag-suggest-container" v-if="nonTaggedImageAndTag != null">
    <span class="tag-suggest-image-container">
      <ImageThumb :image="imageData" imgClass="p-image--shadowed"></ImageThumb>
    </span>
    <div class="tag-suggest-info-section">
      <span class="tag-suggest-info-text">
        Is this image contains <i><Link :href="`/tag/${nonTaggedImageAndTag.tagId}`" class="monospace">{{nonTaggedImageAndTag.tagName}}</Link></i>?
      </span>
      <ul class="forever-ul">
        <li>
          <Form class="p-form" :action="`/image/${nonTaggedImageAndTag.imageId}/tag/${nonTaggedImageAndTag.tagId}`" method="put">
            <button type="submit" class="p-button--positive has-icon"><i class="p-icon--thumbs-up"></i><span>Approve</span></button>
          </Form>
        </li>
        <li>
          <Form class="p-form" :action="`/image/${nonTaggedImageAndTag.imageId}/tag/${nonTaggedImageAndTag.tagId}`" method="delete">
            <button type="submit" class="p-button--negative has-icon"><i class="p-icon--close"></i><span>Reject</span></button>
          </Form>
        </li>
      </ul>
    </div>
  </div>
</template>

<style scoped>
.tag-suggest-image-container {
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.tag-suggest-container {
  display: flex;
  width: 100%;
  align-items: center;
  justify-content: center;
}
.tag-suggest-info-section {
  display: inline-block;
  padding-left: 1.5rem;
  text-align: left;
}
.tag-suggest-info-text {
  display: inline-block;
  margin-bottom: 1rem;
}
</style>