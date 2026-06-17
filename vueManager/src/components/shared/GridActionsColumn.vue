<template>
  <div class="grid-actions">
    <Button
      icon="pi pi-pencil"
      severity="secondary"
      text
      rounded
      size="small"
      :title="editLabel"
      @click="$emit('edit', data)"
    />
    <Button
      v-if="!isActive"
      icon="pi pi-power-off"
      severity="success"
      text
      rounded
      size="small"
      :title="enableLabel"
      @click="$emit('enable', data)"
    />
    <Button
      v-else
      icon="pi pi-power-off"
      severity="secondary"
      text
      rounded
      size="small"
      :title="disableLabel"
      @click="$emit('disable', data)"
    />
    <Button
      icon="pi pi-trash"
      severity="danger"
      text
      rounded
      size="small"
      :title="removeLabel"
      @click="$emit('remove', data)"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Button } from 'primevue'

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
  activeField: {
    type: String,
    default: 'active',
  },
  editLabel: {
    type: String,
    default: 'Edit',
  },
  enableLabel: {
    type: String,
    default: 'Enable',
  },
  disableLabel: {
    type: String,
    default: 'Disable',
  },
  removeLabel: {
    type: String,
    default: 'Delete',
  },
})

defineEmits(['edit', 'enable', 'disable', 'remove'])

const isActive = computed(() => props.data[props.activeField])
</script>

<style scoped>
.grid-actions {
  display: flex;
  gap: 0.25rem;
}
</style>
