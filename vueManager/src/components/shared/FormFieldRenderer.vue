<template>
  <div
    class="form-field"
    :class="{ 'form-field--full': isFullWidth }"
  >
    <label v-if="field.caption" :for="inputId" class="form-field__label">
      {{ field.caption }}
      <span v-if="field.required" class="form-field__required">*</span>
    </label>

    <InputText
      v-if="field.type === 'text'"
      :id="inputId"
      v-model="modelValue"
      :required="field.required"
      class="form-field__input"
    />

    <Textarea
      v-else-if="isTextareaType"
      :id="inputId"
      v-model="modelValue"
      :required="field.required"
      :rows="textareaRows"
      class="form-field__input"
    />

    <Select
      v-else-if="field.type === 'select'"
      :id="inputId"
      v-model="modelValue"
      :options="selectOptions"
      :option-label="selectOptionLabel"
      :option-value="selectOptionValue"
      :placeholder="field.caption"
      :disabled="selectDisabled"
      :empty-message="emptyMessage"
      class="form-field__input"
    />

    <InputNumber
      v-else-if="field.type === 'number'"
      :input-id="inputId"
      v-model="modelValue"
      class="form-field__input"
    />

    <div v-else class="form-field__unsupported">
      Unsupported field type: {{ field.type }}
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { InputText, Textarea, Select, InputNumber } from 'primevue'

const props = defineProps({
  field: {
    type: Object,
    required: true,
  },
  modelValue: {
    type: [String, Number, null],
    default: null,
  },
  options: {
    type: Array,
    default: () => [],
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  emptyMessage: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['update:modelValue'])

const inputId = computed(() => `field-${props.field.field}`)

const isFullWidth = computed(() => {
  const f = props.field
  return f.field === 'content' || f.type === 'textarea' || f.type === 'richtext'
})

const isTextareaType = computed(() => {
  return props.field.type === 'textarea' || props.field.type === 'richtext' || props.field.type === 'tv'
})

const textareaRows = computed(() => {
  return props.field.field === 'content' ? 8 : 4
})

const selectOptions = computed(() => props.options)
const selectOptionLabel = computed(() => 'name')
const selectOptionValue = computed(() => 'key')
const selectDisabled = computed(() => props.disabled)

// v-model computed for different types
const modelValue = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val),
})
</script>

<style scoped>
.form-field {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.form-field--full {
  grid-column: 1 / -1;
}

.form-field__label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--p-text-color, #374151);
}

.form-field__required {
  color: #ef4444;
  margin-left: 2px;
}

.form-field__input {
  width: 100%;
}

.form-field__unsupported {
  color: var(--p-text-muted-color, #6b7280);
  font-size: 0.75rem;
}
</style>
