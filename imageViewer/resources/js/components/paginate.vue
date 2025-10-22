<script setup lang="ts">
import {Link} from "@inertiajs/vue3";

defineProps({
  paginate: Object
});
</script>

<template>
  <nav class="p-pagination" aria-label="Pagination" v-if="paginate?.last_page > 1">
    <ol class="p-pagination__items">
      <li class="p-pagination__item">
        <Link class="p-pagination__link--previous" :href="paginate?.prev_page_url" title="Previous page" v-if="paginate?.prev_page_url != null"><i class="p-icon--chevron-down">Previous page</i></Link>
        <span class="p-pagination__link--previous is-disabled" aria-disabled="true" v-else><i class="p-icon--chevron-down">Previous page</i></span>
      </li>
      <li class="p-pagination__item" v-for="idx in paginate?.last_page" :key="idx">
        <Link class="p-pagination__link is-active" :href="paginate?.links[idx].url" aria-current="page" :aria-label="`Page ${idx}`"
              v-if="idx == paginate?.current_page">{{ idx }}</Link>
        <Link class="p-pagination__link" :href="paginate?.links[idx].url" :aria-label="`Page ${idx}`" v-else>{{ idx }}</Link>
      </li>
      <li class="p-pagination__item">
        <Link class="p-pagination__link--next" :href="paginate?.next_page_url" title="Next page" v-if="paginate?.next_page_url != null"><i class="p-icon--chevron-down">Next page</i></Link>
        <span class="p-pagination__link--next is-disabled" aria-disabled="true" v-else><i class="p-icon--chevron-down">Next page</i></span>
      </li>
    </ol>
  </nav>
</template>

<style scoped>
.p-pagination__items {
  width: 100%;
  flex-wrap: wrap;
}
</style>