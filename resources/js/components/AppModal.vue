<script setup lang="ts">
type Tone = 'default' | 'approve' | 'disapprove';

const props = withDefaults(defineProps<{
    modelValue: boolean;
    title: string;
    tone?: Tone;
    closeOnBackdrop?: boolean;
    persistent?: boolean;
}>(), {
    tone: 'default',
    closeOnBackdrop: true,
    persistent: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
    close: [];
}>();

function requestClose() {
    if (props.persistent) return;
    emit('update:modelValue', false);
    emit('close');
}

function onBackdropClick() {
    if (!props.closeOnBackdrop) return;
    requestClose();
}
</script>

<template>
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="modelValue" class="app-modal-overlay" @click.self="onBackdropClick">
                <div class="app-modal-card">
                    <div class="app-modal-header" :class="`tone-${tone}`">
                        <h4>{{ title }}</h4>
                    </div>
                    <div class="app-modal-body">
                        <slot />
                        <div class="app-modal-actions">
                            <slot name="actions" />
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.app-modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 200;
    display: flex;
    align-items: center;
    justify-content: center;
    background: hsl(0 0% 0% / 0.55);
    backdrop-filter: blur(4px);
}

.app-modal-card {
    width: 100%;
    max-width: 480px;
    border-radius: 1rem;
    border: 1px solid hsl(var(--border));
    background: #fff;
    overflow: hidden;
    box-shadow: 0 8px 30px hsl(0 0% 0% / 0.2);
}

.dark .app-modal-card {
    background: hsl(223 24% 14%);
}

.app-modal-header {
    padding: 1.1rem 1.5rem;
    border-bottom: 1px solid hsl(var(--border));
}

.app-modal-header h4 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 800;
}

.app-modal-header.tone-approve {
    background: hsl(160 60% 45% / 0.08);
    border-bottom-color: hsl(160 60% 45% / 0.15);
}

.app-modal-header.tone-approve h4 {
    color: hsl(160 60% 30%);
}

.app-modal-header.tone-disapprove {
    background: hsl(0 72% 51% / 0.08);
    border-bottom-color: hsl(0 72% 51% / 0.15);
}

.app-modal-header.tone-disapprove h4 {
    color: hsl(0 72% 51%);
}

.app-modal-body {
    padding: 1.25rem 1.5rem 1.5rem;
    background: #fff;
}

.dark .app-modal-body {
    background: hsl(223 24% 14%);
}

.app-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.6rem;
    margin-top: 1.25rem;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>

