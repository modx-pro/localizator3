<template>
  <input
    v-if="field.tvType === 'hidden'"
    type="hidden"
    :value="stringValue"
  />

  <InputNumber
    v-else-if="field.tvType === 'number'"
    :model-value="numberValue"
    :placeholder="field.caption"
    fluid
    @update:model-value="updateValue"
  />

  <Select
    v-else-if="field.tvType === 'listbox' || field.tvType === 'radio'"
    :model-value="stringValue"
    :options="options"
    option-label="label"
    option-value="value"
    :placeholder="field.caption"
    show-clear
    fluid
    @update:model-value="updateValue"
  />

  <MultiSelect
    v-else-if="field.tvType === 'listbox-multiple'"
    :model-value="arrayValue"
    :options="options"
    option-label="label"
    option-value="value"
    :placeholder="field.caption"
    display="chip"
    fluid
    @update:model-value="updateValue"
  />

  <div v-else-if="field.tvType === 'checkbox'" class="loc-tv-checkbox-group">
    <label
      v-for="option in options"
      :key="option.value"
      class="loc-tv-checkbox"
    >
      <Checkbox
        :value="option.value"
        :model-value="arrayValue"
        @update:model-value="updateValue"
      />
      <span>{{ option.label }}</span>
    </label>
  </div>

  <TvRenderedInput
    v-else-if="field.renderMode === 'html' && field.formElement"
    :html="field.formElement"
    :render-id="field.renderTvId"
    :target-name="'tvlocalizator_' + field.inputTV"
  />

  <Textarea
    v-else-if="isTextarea"
    :model-value="stringValue"
    :placeholder="field.caption"
    :rows="textareaRows"
    fluid
    @update:model-value="updateValue"
  />

  <InputText
    v-else
    :model-value="stringValue"
    :placeholder="field.caption"
    fluid
    @update:model-value="updateValue"
  />
</template>

<script setup>
import { computed } from 'vue'
import { Checkbox, InputNumber, InputText, MultiSelect, Select, Textarea } from 'primevue'
import TvRenderedInput from './TvRenderedInput.vue'

const props = defineProps({
  field: {
    type: Object,
    required: true,
  },
  modelValue: {
    type: [String, Number, Array, null],
    default: null,
  },
})

const emit = defineEmits(['update:modelValue'])

const options = computed(() => props.field.options || [])

const isTextarea = computed(() => {
  return ['textarea', 'richtext'].includes(props.field.tvType)
})

const textareaRows = computed(() => {
  return ['textarea', 'richtext'].includes(props.field.tvType) ? 6 : 4
})

const stringValue = computed(() => {
  if (props.modelValue === null || props.modelValue === undefined) {
    return ''
  }
  return Array.isArray(props.modelValue) ? props.modelValue.join('||') : String(props.modelValue)
})

const numberValue = computed(() => {
  if (props.modelValue === '' || props.modelValue === null || props.modelValue === undefined) {
    return null
  }
  const value = Number(props.modelValue)
  return Number.isNaN(value) ? null : value
})

const arrayValue = computed(() => {
  if (Array.isArray(props.modelValue)) {
    return props.modelValue
  }
  if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') {
    return []
  }
  return String(props.modelValue).split('||')
})

function updateValue(value) {
  emit('update:modelValue', value)
}
</script>

<style scoped>
.loc-tv-checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  padding-top: 0.25rem;
}

.loc-tv-checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}
</style>
