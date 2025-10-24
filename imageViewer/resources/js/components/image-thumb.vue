<script setup lang="ts">
import {Link} from "@inertiajs/vue3";
import {ref} from "vue";

const props = defineProps({
  image: Object,
  imgMaxSize: Number,
  imgClass: String,
});

const afterWidth = ref(props.imgMaxSize ?? 200);
const afterHeight = ref(props.imgMaxSize ?? 200);

if (props.image?.width && props.image?.height) {
  const targetSize = props.imgMaxSize ?? 200;

  const width = props.image?.width;
  const height = props.image?.height;

  if (width > height) {
    afterWidth.value = targetSize;
    afterHeight.value = Math.ceil((height / width) * targetSize);
  } else {
    afterWidth.value = Math.ceil((width / height) * targetSize);
    afterHeight.value = targetSize;
  }
}
</script>

<template>
  <Link :href="`/image/${image?.id}`">
    <img :src="`${ $page.props.imgServerBase }/image/${image?.id}/thumb`" :alt="`Image: ${image?.id}`" loading="lazy" :width="afterWidth" :height="afterHeight" :class="imgClass" />
  </Link>
</template>

<style scoped>

</style>