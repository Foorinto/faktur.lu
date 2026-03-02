const avatarColors = [
    { bg: 'bg-pink-500',    darkBg: 'dark:bg-pink-600' },
    { bg: 'bg-violet-500',  darkBg: 'dark:bg-violet-600' },
    { bg: 'bg-cyan-500',    darkBg: 'dark:bg-cyan-600' },
    { bg: 'bg-amber-500',   darkBg: 'dark:bg-amber-600' },
    { bg: 'bg-emerald-500', darkBg: 'dark:bg-emerald-600' },
    { bg: 'bg-rose-500',    darkBg: 'dark:bg-rose-600' },
    { bg: 'bg-blue-500',    darkBg: 'dark:bg-blue-600' },
    { bg: 'bg-orange-500',  darkBg: 'dark:bg-orange-600' },
    { bg: 'bg-teal-500',    darkBg: 'dark:bg-teal-600' },
    { bg: 'bg-indigo-500',  darkBg: 'dark:bg-indigo-600' },
    { bg: 'bg-fuchsia-500', darkBg: 'dark:bg-fuchsia-600' },
    { bg: 'bg-lime-500',    darkBg: 'dark:bg-lime-600' },
];

function hashName(name) {
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    return Math.abs(hash);
}

export function useAvatarColor() {
    function getAvatarClasses(name) {
        const index = hashName(name || '') % avatarColors.length;
        const color = avatarColors[index];
        return `${color.bg} ${color.darkBg} text-white`;
    }

    return { getAvatarClasses };
}
