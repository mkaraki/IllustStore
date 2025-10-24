<script setup lang="ts">
import {Form, Head} from "@inertiajs/vue3";
import {ref} from "vue";

const props = defineProps({
  isNew: Boolean,
  tagData: Object,
  errors: Object,
})
const title = props.isNew ? 'New Tag' : `Edit Tag: ${ props.tagData?.tagName ?? '' }`;

const descriptionModel = ref(props.isNew ? '' : (props.tagData?.description ?? ''));
const taggingNoteModel = ref(props.isNew ? '' : (props.tagData?.taggingNote ?? ''));
</script>

<template>
  <Head :title="title"></Head>
  <div class="p-strip is-shallow">
    <div class="row">
      <div class="col">
        <h1>{{ title }}</h1>
      </div>
    </div>
    <div class="row">
      <div class="col">
        <Form :action="isNew ? '/tag/new' : `/tag/${tagData?.id}/edit`" method="post" :options="{ preserveState: 'errors', }">
          <div :class="`p-form-validation__field ${( errors?.tag ? 'is-error' : '' )}`">
            <label for="tag">Tag name</label>
            <input type="text" class="p-form-validation__input" :aria-invalid="!!errors?.tag" id="tag" name="tag" required :value="isNew ? '' : (tagData?.tagName ?? '')">
            <p class="p-form-validation__message" v-if="errors?.tag" >{{ errors?.tag }}</p>
          </div>

          <div :class="`p-form-validation__field ${( errors?.tagName ? 'is-error' : '' )}`">
            <label for="tagPixivJpn">Pixiv Japanese/Original Tag</label>
            <input type="text" class="p-form-validation__input" id="tagPixivJpn" :aria-invalid="!!errors?.tagPixivJpn" name="tagPixivJpn" :value="isNew ? '' : (tagData?.tagPixivJpn ?? '')">
            <p class="p-form-validation__message" v-if="errors?.tagPixivJpn" >{{ errors?.tagPixivJpn }}</p>
          </div>

          <div :class="`p-form-validation__field ${( errors?.tagName ? 'is-error' : '' )}`">
            <label for="tagPixivEng">Pixiv English translated Tag</label>
            <input type="text" class="p-form-validation__input" id="tagPixivEng" :aria-invalid="!!errors?.tagPixivEng" name="tagPixivEng" aria-describedby="tagPixivEng_alert" :value="isNew ? '' : tagData?.tagPixivEng">
            <p class="p-form-validation__message" id="tagPixivEng_alert" v-if="errors?.tagPixivEng" >{{ errors?.tagPixivEng }}</p>
            <p class="p-form-help-text" id="tagPixivEng_alert" v-else>
              Note: You have to put translated tag. Not another tag which translated to English.
              (e.g. <code>風景</code> become <code>scenery</code>)
            </p>
          </div>

          <div :class="`p-form-validation__field ${( errors?.tagName ? 'is-error' : '' )}`">
            <label for="tagDanbooru">Danbooru Tag</label>
            <input type="text" class="p-form-validation__input" id="tagDanbooru" :aria-invalid="!!errors?.tagDanbooru" name="tagDanbooru" :value="isNew ? '' : tagData?.tagDanbooru">
            <p class="p-form-validation__message" v-if="errors?.tagDanbooru" >{{ errors?.tagDanbooru }}</p>
          </div>

          <label for="description">Description</label>
          <textarea id="description" name="description" rows="3" v-model="descriptionModel"></textarea>

          <label for="taggingNote">Tagging Note</label>
          <textarea id="taggingNote" name="taggingNote" rows="3" v-model="taggingNoteModel"></textarea>

          <button type="submit" class="p-button has-icon"><i class="p-icon--save"></i><span>Save</span></button>
        </Form>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>