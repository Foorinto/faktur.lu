<script setup>
defineProps({
    code: { type: String, required: true },
    class: { type: String, default: '' },
});
</script>

<template>
    <span :class="['inline-block align-middle leading-none overflow-hidden rounded-sm', $props.class || 'w-5 h-3.5']" :aria-label="code">
        <svg v-if="code === 'fr'" viewBox="0 0 6 4" class="block w-full h-full" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
            <rect width="6" height="4" fill="#fff"/>
            <rect width="2" height="4" fill="#002654"/>
            <rect x="4" width="2" height="4" fill="#CE1126"/>
        </svg>
        <svg v-else-if="code === 'de'" viewBox="0 0 6 4" class="block w-full h-full" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
            <rect width="6" height="1.333" fill="#000"/>
            <rect y="1.333" width="6" height="1.333" fill="#DD0000"/>
            <rect y="2.667" width="6" height="1.333" fill="#FFCE00"/>
        </svg>
        <svg v-else-if="code === 'en' || code === 'gb'" viewBox="0 0 60 30" class="block w-full h-full" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
            <clipPath id="flag-gb-clip"><path d="M0,0 v30 h60 v-30 z"/></clipPath>
            <clipPath id="flag-gb-clip2"><path d="M30,15 h30 v15 z v15 h-30 z h-30 v-15 z v-15 h30 z"/></clipPath>
            <g clip-path="url(#flag-gb-clip)">
                <rect width="60" height="30" fill="#012169"/>
                <path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6"/>
                <path d="M0,0 L60,30 M60,0 L0,30" clip-path="url(#flag-gb-clip2)" stroke="#C8102E" stroke-width="4"/>
                <path d="M30,0 V30 M0,15 H60" stroke="#fff" stroke-width="10"/>
                <path d="M30,0 V30 M0,15 H60" stroke="#C8102E" stroke-width="6"/>
            </g>
        </svg>
        <svg v-else-if="code === 'lb'" viewBox="0 0 6 4" class="block w-full h-full" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
            <rect width="6" height="4" fill="#fff"/>
            <rect width="6" height="1.333" fill="#ED2939"/>
            <rect y="2.667" width="6" height="1.333" fill="#00A1DE"/>
        </svg>
        <svg v-else-if="code === 'pt'" viewBox="0 0 6 4" class="block w-full h-full" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
            <rect width="6" height="4" fill="#DA291C"/>
            <rect width="2.4" height="4" fill="#006A4E"/>
            <circle cx="2.4" cy="2" r="0.55" fill="#FFE000" stroke="#fff" stroke-width="0.08"/>
        </svg>
        <svg v-else-if="code === 'be'" viewBox="0 0 6 4" class="block w-full h-full" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
            <rect width="2" height="4" fill="#000"/>
            <rect x="2" width="2" height="4" fill="#FAE042"/>
            <rect x="4" width="2" height="4" fill="#ED2939"/>
        </svg>
        <span v-else class="inline-flex items-center justify-center w-full h-full bg-slate-200 text-[10px] font-semibold text-slate-600 uppercase">
            {{ code }}
        </span>
    </span>
</template>
