<script setup lang="ts">
import {Deferred, Form, Link} from "@inertiajs/vue3";
import ImageThumb from "@/components/image-thumb.vue";
import TagComplete from "@/components/tag-complete.vue";
import {ref, useTemplateRef} from "vue";
import NonTaggedImageAndTag from "@/components/non-tagged-image-and-tag.vue";

const props = defineProps({
  randomTags: Array<Object>,
  nonTaggedImageAndTag: Object,
  images: Array<Object>,
  imageCount: Number,
  tagCount: Number,
  tagAssignCount: Number,
})

const randomImages = props.images?.slice(0, Math.min(18, props.images.length));
const topImage: any = ((props.images?.length ?? 0) >= 18) ? (props.images?.[18]) : null;

const searchInput = useTemplateRef('searchInput')
const searchModel = ref('')
</script>

<template>
  <div class="p-strip--highlighted">
    <div class="row--25-75 u-vertically-center">
      <div class="col u-hide--small u-align-text--center">
        <ImageThumb :image="topImage" v-if="topImage !== null" imgClass="p-image--shadowed"></ImageThumb>
      </div>
      <div class="col">
        <h1>Illust Store</h1>
        <p>AI powered illustration search engine.</p>
        <section>
          <h2 class="p-muted-heading">Start tag searching</h2>
          <Form action="/search" method="get" class="p-search-box">
            <label class="u-off-screen" for="search">Search</label>
            <input type="search" id="search" class="p-search-box__input" name="q" placeholder="Search" required autocomplete="on" v-model="searchModel" ref="searchInput">
            <button type="reset" class="p-search-box__reset"><i class="p-icon--close">Close</i></button>
            <button type="submit" class="p-search-box__button"><i class="p-icon--search">Search</i></button>
          </Form>
          <TagComplete :input="searchModel" :inputRef="searchInput" ></TagComplete>
        </section>
        <!-- ToDo: impl Image Search again...
        <hr />
        <section>
          <h2 class="p-muted-heading">Or image search</h2>
          <form action="/search/image" enctype="multipart/form-data" method="post" id="im-search-form" class="p-form p-form--inline">
            <div class="p-form__group">
              <div class="p-form__control">
                <input type="file" name="img" accept="image/*" id="im-search-file" class="p-form__control" />
              </div>
            </div>
            <button type="submit" class="p-button">Search Image</button>
          </form>
        </section>-->
      </div>
    </div>
  </div>
  <div class="p-strip">
    <div class="row">
      <div class="col">
        <section class="p-section p-data-spotlight--3-blocks">
          <div class="p-equal-height-row--wrap">
            <div class="p-equal-height-row__col u-no-margin--bottom p-data-spotlight__title-col">
              <div class="p-equal-height-row__item p-data-spotlight__title">
                <h2 class="p-muted-heading">Illust Store<br class="u-hide--medium u-hide--small"> in numbers</h2>
              </div>
            </div>
            <div class="p-equal-height-row__col u-no-margin--bottom p-data-spotlight__block">
              <div class="p-equal-height-row__item">
                <hr class="p-rule--highlight">
                <Deferred data="imageCount">
                  <template #fallback>
                    <p class="p-heading--1 u-no-margin u-no-padding">
                      <i class="p-icon--spinner u-animation--spin">Loading</i>
                    </p>
                  </template>
                  <p class="p-heading--1 u-no-margin u-no-padding">{{ imageCount?.toLocaleString() }}</p>
                </Deferred>
              </div>
              <p class="p-equal-height-row__item p-heading--3 u-no-margin u-no-padding">Searchable illustrations</p>
              <div class="p-equal-height-row__item">
                <Link href="/image/">Check all illusts ›</Link>
              </div>
            </div>
            <div class="p-equal-height-row__col u-no-margin--bottom p-data-spotlight__block">
              <div class="p-equal-height-row__item">
                <hr class="p-rule--highlight">
                <Deferred data="tagCount">
                  <template #fallback>
                    <p class="p-heading--1 u-no-margin u-no-padding">
                      <i class="p-icon--spinner u-animation--spin">Loading</i>
                    </p>
                  </template>
                  <p class="p-heading--1 u-no-margin u-no-padding">{{ tagCount?.toLocaleString() }}</p>
                </Deferred>
              </div>
              <p class="p-equal-height-row__item p-heading--3 u-no-margin u-no-padding">Tags</p>
              <div class="p-equal-height-row__item">
                <Link href="/tag/">Check registered tags ›</Link>
              </div>
            </div>
            <div class="p-equal-height-row__col u-no-margin--bottom p-data-spotlight__block">
              <div class="p-equal-height-row__item">
                <hr class="p-rule--highlight">
                <Deferred data="tagAssignCount">
                  <template #fallback>
                    <p class="p-heading--1 u-no-margin u-no-padding">
                      <i class="p-icon--spinner u-animation--spin">Loading</i>
                    </p>
                  </template>
                  <p class="p-heading--1 u-no-margin u-no-padding">{{ tagAssignCount?.toLocaleString() }}</p>
                </Deferred>
              </div>
              <p class="p-equal-height-row__item p-heading--3 u-no-margin u-no-padding">Tags assigned</p>
              <div class="p-equal-height-row__item">
                <Link href="/tag/pending">Check tagging suggestions ›</Link>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
    <div class="row row-custom-margin-bottom">
      <div class="col u-align-text--center">
        <h2 class="p-muted-heading centered-section-muted-heading">Random Tags</h2>
        <Deferred data="randomTags">
          <template #fallback>
            <div>
              <i class="p-icon--spinner u-animation--spin">Loading</i>
            </div>
          </template>
          <ul class="forever-ul">
            <li v-for="v in randomTags" :key="v.id"><Link :href="`/tag/${v.id}`">{{v.tagName}}</Link></li>
          </ul>
        </Deferred>
      </div>
    </div>
    <template v-if="nonTaggedImageAndTag != null">
      <div class="row">
        <div class="col u-align-text--center">
          <h2 class="p-muted-heading centered-section-muted-heading">Tagging suggestion</h2>
        </div>
      </div>
      <div class="row row-custom-margin-bottom">
        <div class="col">
          <Deferred data="nonTaggedImageAndTag">
            <template #fallback>
              <div>
                <i class="p-icon--spinner u-animation--spin">Loading</i>
              </div>
            </template>
            <non-tagged-image-and-tag :nonTaggedImageAndTag="nonTaggedImageAndTag"></non-tagged-image-and-tag>
          </Deferred>
        </div>
      </div>
    </template>
    <div class="row">
      <div class="col u-align-text--center">
        <h2 class="p-muted-heading centered-section-muted-heading">Random illusts</h2>
        <div>
          <ul class="p-matrix">
            <li class="p-matrix__item" v-for="image in randomImages" :key="image.id">
              <div class="p-matrix__content text-align--center random-image-gallery-item">
                <ImageThumb :image="image"></ImageThumb>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.random-image-gallery-item {
  display: flex;
  align-items: center;
  justify-content: center;
}

.row-custom-margin-bottom {
  margin-bottom: 4rem;
}

.pad-both-up-down {
  padding-top: 1rem;
  padding-bottom: 1rem;
}
</style>
