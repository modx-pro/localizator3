<template>
  <div class="content-grid vueApp">
    <Toast />
    <ConfirmDialog />

    <p class="loc-section-bar">
      {{ sectionDesc }}
    </p>

    <div class="loc-toolbar content-grid__locale-bar">
      <div class="content-grid__locale-group">
        <div class="loc-toolbar__field content-grid__locale-select">
          <label for="content-locale" class="loc-toolbar__label">
            {{ lexicon.localizator_language || 'Language' }}
          </label>
          <Select
            id="content-locale"
            v-model="selectedLocaleKey"
            :options="localeOptions"
            option-label="label"
            option-value="value"
            :placeholder="lexicon.localizator_language || 'Language'"
            :loading="loading"
            :disabled="loading || formConfigLoading || localeOptions.length === 0"
            class="content-grid__locale-dropdown"
          />
        </div>
        <label
          v-if="!isEdit && formReady"
          class="content-grid__publish"
          :title="lexicon.localizator_content_active_desc || undefined"
        >
          <Checkbox
            :model-value="isActiveFlag(form.active)"
            :binary="true"
            input-id="content-active"
            @update:model-value="(checked) => { form.active = checked ? 1 : 0 }"
          />
          <span class="content-grid__publish-label">
            {{ lexicon.localizator_content_active || 'Publish translation' }}
          </span>
        </label>
      </div>
      <div class="loc-toolbar__actions content-grid__actions">
        <Button
          :label="lexicon.localizator_save || 'Save'"
          icon="pi pi-check"
          :severity="saveSeverity"
          :loading="saving"
          :disabled="!formReady || saving || (!isEdit && !selectedLocaleKey)"
          @click="submitForm"
        />
        <Button
          :label="lexicon.localizator_translate || 'Translate'"
          icon="pi pi-language"
          severity="secondary"
          outlined
          :disabled="!isEdit || !form.key || saving"
          @click="translateCurrent"
        />
        <template v-if="isEdit && form.id">
          <Button
            v-if="!form.active"
            icon="pi pi-power-off"
            severity="success"
            text
            rounded
            :title="lexicon.localizator_item_enable"
            @click="toggleActive(true)"
          />
          <Button
            v-else
            icon="pi pi-power-off"
            severity="secondary"
            text
            rounded
            :title="lexicon.localizator_item_disable"
            @click="toggleActive(false)"
          />
          <Button
            icon="pi pi-trash"
            severity="danger"
            text
            rounded
            :title="lexicon.localizator_item_remove"
            @click="confirmRemoveCurrent"
          />
        </template>
      </div>
    </div>

    <div v-if="formConfigLoading || loading" class="content-grid__loading">
      {{ lexicon.localizator_loading || 'Loading...' }}
    </div>
    <div v-else-if="formConfigError" class="content-grid__empty">
      {{ formConfigError }}
    </div>
    <div v-else-if="!formReady && activeLanguages.length === 0" class="content-grid__empty">
      {{ emptyLanguagesMessage }}
    </div>
    <form v-else-if="formReady" ref="formEl" class="loc-panel loc-form content-grid__form" @submit.prevent="submitForm">
      <Tabs v-model:value="activeTab" class="content-form-tabs">
        <TabList>
          <Tab v-for="tab in visibleTabs" :key="tab.id" :value="tab.id">
            {{ tab.caption }}
          </Tab>
        </TabList>
        <TabPanels>
          <TabPanel v-for="tab in visibleTabs" :key="tab.id" :value="tab.id">
            <div class="loc-form-grid">
              <div
                v-for="field in visibleFields(tab)"
                :key="field.field"
                class="loc-form-field"
                :class="fieldClasses(field)"
              >
                <label
                  v-if="field.caption && field.tvType !== 'hidden'"
                  :for="'field-' + field.field"
                  class="loc-form-label"
                >
                  {{ field.caption }}
                  <span v-if="field.required" class="loc-form-label__required">*</span>
                  <span
                    v-if="field.type === 'tv' && field.description"
                    class="loc-form-label__desc"
                  >{{ field.description }}</span>
                </label>
                <TvFieldControl
                  v-if="field.type === 'tv'"
                  :field="field"
                  v-model="form[tvFieldName(field)]"
                />
                <InputText
                  v-else-if="field.type === 'text'"
                  :id="'field-' + field.field"
                  v-model="form[field.field]"
                  :required="field.required"
                />
                <Textarea
                  v-else-if="field.type === 'textarea' || field.type === 'richtext'"
                  :id="'field-' + field.field"
                  v-model="form[field.field]"
                  :required="field.required"
                  :rows="field.field === 'content' ? 12 : 4"
                />
                <InputNumber
                  v-else-if="field.type === 'number'"
                  :input-id="'field-' + field.field"
                  v-model="form[field.field]"
                  fluid
                />
              </div>
            </div>
          </TabPanel>
        </TabPanels>
      </Tabs>
    </form>
  </div>
</template>

<script setup>
import { toRef, onMounted, watch } from 'vue'
import { getPrimarySaveSeverity } from '../app/primevueTheme.js'
import { useContentForm } from '../composables/useContentForm.js'
import TvFieldControl from './shared/TvFieldControl.vue'

const props = defineProps({
  resourceId: { type: Number, required: true },
  connectorUrl: { type: String, required: true },
  modAuth: { type: String, default: '' },
  lexicon: { type: Object, default: () => ({}) },
})

const saveSeverity = getPrimarySaveSeverity()

const {
  lexicon,
  loading,
  saving,
  selectedLocaleKey,
  activeLanguages,
  formConfigLoading,
  formConfigError,
  form,
  formEl,
  isEdit,
  activeTab,
  localeOptions,
  formReady,
  sectionDesc,
  emptyLanguagesMessage,
  visibleTabs,
  isActiveFlag,
  visibleFields,
  tvFieldName,
  fieldClasses,
  submitForm,
  translateCurrent,
  toggleActive,
  confirmRemoveCurrent,
  bootstrap,
} = useContentForm({
  resourceId: toRef(props, 'resourceId'),
  connectorUrl: toRef(props, 'connectorUrl'),
  modAuth: toRef(props, 'modAuth'),
  lexicon: toRef(props, 'lexicon'),
})

onMounted(() => {
  bootstrap()
})

watch(() => props.resourceId, () => {
  selectedLocaleKey.value = ''
  bootstrap()
})
</script>

<style scoped>
.content-grid {
  width: 100%;
  max-width: 100%;
  min-width: 0;
  box-sizing: border-box;
}

.content-grid__locale-bar {
  align-items: flex-end;
  gap: 16px;
  max-width: 100%;
}

.content-grid__locale-group {
  display: flex;
  flex: 1 1 auto;
  flex-wrap: wrap;
    align-items: flex-end;
    gap: 8px 16px;
  min-width: 0;
}

.content-grid__locale-select {
  flex: 0 1 22rem;
  min-width: 12rem;
  max-width: 28rem;
}

.content-grid__locale-dropdown {
  width: 100%;
}

.content-grid__publish {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  height: var(--loc-control-h, 32px);
  margin: 0;
  padding: 0 2px;
  color: var(--loc-text, #53595f);
  cursor: pointer;
}

.content-grid__publish-label {
  font-size: 13px;
  font-weight: 500;
  line-height: 1.2;
  white-space: nowrap;
}

.content-grid__publish :deep(.p-checkbox) {
  flex-shrink: 0;
}

.content-grid__actions {
  flex: 0 0 auto;
  margin-left: auto;
}

.content-grid__form {
  padding: 0;
}

.loc-form-field--hidden {
  display: none;
}

.content-grid__loading,
.content-grid__empty {
  padding: 2rem 1rem;
  text-align: center;
  color: var(--loc-text-muted, #64748b);
}

.content-form-tabs {
  margin-bottom: 0.25rem;
}
@media (max-width: 720px) {
  .content-grid__locale-select {
    flex: 1 1 100%;
    max-width: none;
  }

  .content-grid__actions {
    margin-left: 0;
    width: 100%;
  }
}
</style>
