<script setup lang="ts">
import {Link} from "@inertiajs/vue3";
import {ref} from "vue";
import Paginate from "@/components/paginate.vue";
const props = defineProps({
  tags: Array<any>,
  maxAssignCount: Number,
});

const maxSize = ref(props.maxAssignCount ?? 0);
const initSize = ref(12.0);
const usableSize = ref(20.0);

if (maxSize.value == 0) {
  maxSize.value = 1;
}
</script>

<template>
  <div class="is-shallow p-strip">
    <header class="row">
      <div class="col">
        <h1 class="query-ind query-h1">Tags</h1>
        <Link href="/tag/new">New tag</Link> | <Link href="/tag/pending">Tagging queue</Link>
      </div>
    </header>
  </div>
  <div class="is-shallow p-strip">
    <div class="row">
      <div class="col">
        <ul class="forever-ul tag-cloud">
          <li v-for="v in tags?.data" :key="v['id']">
            <Link :href="`/tag/${ v.id }`" :style="`font-size: ${ initSize + ((-Math.pow((v.count / maxSize) - 1, 2) + 1) * usableSize) }pt;`">
              {{ v.tagName }}
            </Link> ({{ v.count ?? 0 }})
          </li>
        </ul>
      </div>
    </div>
  </div>
  <div class="is-shallow p-strip">
    <div class="row">
      <div class="col">
        <paginate :paginate="tags"></paginate>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>