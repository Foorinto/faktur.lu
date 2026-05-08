<script setup>
import { onMounted, ref } from 'vue';

/**
 * Champs anti-bot a inclure dans tous les formulaires publics.
 *
 * Doit etre utilise avec v-model:honeypot et v-model:loadedAt sur le parent
 * (les valeurs vont dans le payload du form). Le middleware 'honeypot' cote
 * backend verifie ces champs.
 */
const props = defineProps({
    honeypot: { type: String, default: '' },
    loadedAt: { type: [String, Number], default: '' },
});

const emit = defineEmits(['update:honeypot', 'update:loadedAt']);

const honeypotValue = ref(props.honeypot);
const loadedAtValue = ref(props.loadedAt);

onMounted(() => {
    // Timestamp en secondes (compatible avec microtime(true) cote PHP)
    loadedAtValue.value = (Date.now() / 1000).toString();
    emit('update:loadedAt', loadedAtValue.value);
});

const onHoneypotInput = (e) => {
    honeypotValue.value = e.target.value;
    emit('update:honeypot', e.target.value);
};
</script>

<template>
    <!-- Honeypot : champ visiblement cache, mais avec un nom plausible que les bots remplissent -->
    <div aria-hidden="true" style="position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden;">
        <label>
            Site web (laisser vide)
            <input
                type="text"
                name="homepage_url"
                tabindex="-1"
                autocomplete="off"
                :value="honeypotValue"
                @input="onHoneypotInput"
            />
        </label>
    </div>

    <!-- Timestamp : invisible, rempli au mount -->
    <input type="hidden" name="form_loaded_at" :value="loadedAtValue" />
</template>
