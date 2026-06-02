import { ref } from 'vue';

export const onlineUsers = ref(new Set());
export const offlineTimes = ref(new Map());
let isInitialized = false;

export function initPresence() {
    if (isInitialized || !window.Echo) return;
    
    isInitialized = true;
    
    window.Echo.join('system.users')
        .here((users) => {
            onlineUsers.value = new Set(users.map(u => u.id));
            users.forEach(u => offlineTimes.value.delete(u.id));
        })
        .joining((user) => {
            onlineUsers.value.add(user.id);
            offlineTimes.value.delete(user.id);
        })
        .leaving((user) => {
            onlineUsers.value.delete(user.id);
            offlineTimes.value.set(user.id, new Date());
        });
}

export function disconnectPresence() {
    if (window.Echo) {
        window.Echo.leave('system.users');
        window.Echo.disconnect();
    }
    isInitialized = false;
    onlineUsers.value.clear();
}
