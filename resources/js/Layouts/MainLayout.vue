<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import axios from 'axios';

const isDarkMode = ref(false);
const isSidebarMobileOpen = ref(false);
const isNotificationsOpen = ref(false);
const notifications = ref([]);
const unreadCount = ref(0);
let pollingInterval = null;

const logout = () => {
    router.post('/logout');
};

const toggleDarkMode = () => {
    isDarkMode.value = !isDarkMode.value;
    if (isDarkMode.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

const toggleSidebar = () => {
    isSidebarMobileOpen.value = !isSidebarMobileOpen.value;
};

const closeSidebar = () => {
    isSidebarMobileOpen.value = false;
};

const fetchNotifications = async () => {
    try {
        const response = await axios.get('/api/notifications/unread');
        unreadCount.value = response.data.unread_count;
        notifications.value = response.data.recent;
    } catch (error) {
        console.error('Failed to fetch notifications', error);
    }
};

const markAllRead = () => {
    router.post('/notifications/read-all', {}, {
        onSuccess: () => {
            unreadCount.value = 0;
            notifications.value = [];
            isNotificationsOpen.value = false;
        }
    });
};

const markAsRead = (id) => {
    router.post(`/notifications/${id}/read`, {}, {
        onSuccess: () => fetchNotifications()
    });
};

// Close sidebar on route change (for mobile)
router.on('navigate', () => {
    closeSidebar();
    isNotificationsOpen.value = false;
    fetchNotifications();
});

onMounted(() => {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        isDarkMode.value = true;
        document.documentElement.classList.add('dark');
    } else {
        isDarkMode.value = false;
        document.documentElement.classList.remove('dark');
    }

    fetchNotifications();
    pollingInterval = setInterval(fetchNotifications, 60000); // Poll every 60s
});

onUnmounted(() => {
    if (pollingInterval) clearInterval(pollingInterval);
});

const navItems = [
    { name: 'Dashboard', icon: 'dashboard', href: '/dashboard' },
    { name: 'Sales', icon: 'payments', href: '/sales' },
    { name: 'EXP INV', icon: 'export_notes', href: '/sales?type=export' },
    { name: 'Expenses', icon: 'receipt_long', href: '/expenses' },
    { name: 'Employees', icon: 'badge', href: '/employees' },
    { name: 'Inventory', icon: 'inventory_2', href: '/inventory' },
    { name: 'Reminders', icon: 'notifications_active', href: '/reminders' },
    { name: 'Notifications', icon: 'notifications', href: '/notifications' },
    { name: 'Shipping Tool', icon: 'local_shipping', href: '/shipping' },
    { name: 'Reports', icon: 'analytics', href: '/reports' },
    { name: 'Banks', icon: 'account_balance', href: '/banks' },
];

const page = usePage();
</script>

<template>
    <div class="bg-background text-on-surface font-body antialiased flex min-h-screen">
        <!-- Sidebar Backdrop (Mobile Only) -->
        <Transition
            enter-active-class="transition-opacity duration-300 ease-linear"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-300 ease-linear"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isSidebarMobileOpen" @click="closeSidebar" class="fixed inset-0 bg-on-background/50 z-40 lg:hidden backdrop-blur-sm"></div>
        </Transition>

        <!-- SideNavBar -->
        <nav 
            class="h-screen w-64 fixed left-0 top-0 border-r border-outline-variant/30 bg-surface-container-lowest flex flex-col py-6 z-50 transition-transform duration-300 lg:transition-colors"
            :class="[isSidebarMobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0']"
        >
            <!-- Header -->
            <div class="px-6 mb-8 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-md bg-primary/10 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-primary">gallery_thumbnail</span>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold font-headline text-on-surface tracking-tight leading-tight">Precision</h1>
                        <p class="text-[10px] font-bold text-outline font-label uppercase tracking-widest">Admin Console</p>
                    </div>
                </div>
                <!-- Close Button (Mobile Only) -->
                <button @click="closeSidebar" class="lg:hidden p-2 text-outline hover:text-on-surface">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- Navigation Links -->
            <div class="flex-1 overflow-y-auto px-2 space-y-1">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="flex items-center px-4 py-3 rounded-md transition-all duration-200 group relative"
                    :class="[
                        $page.url === item.href || ($page.url === '/' && item.href === '/dashboard')
                            ? 'text-primary font-bold bg-primary/5 border-r-4 border-primary'
                            : 'text-outline hover:text-on-surface hover:bg-surface-container-high'
                    ]"
                >
                    <span class="material-symbols-outlined mr-3 text-[20px]" :class="{ 'fill-1': $page.url === item.href }">
                        {{ item.icon }}
                    </span>
                    <span class="text-sm font-label font-medium">{{ item.name }}</span>
                    <span v-if="item.name === 'Notifications' && unreadCount > 0" class="ml-auto bg-error text-on-error text-[10px] font-black px-1.5 py-0.5 rounded-full">
                        {{ unreadCount }}
                    </span>
                </Link>
            </div>

            <!-- Logout & Theme Toggle in Footer of Sidebar -->
            <div class="px-4 mt-auto space-y-1">
                <button 
                    @click="logout"
                    class="flex items-center w-full px-4 py-3 rounded-md text-error hover:bg-error/10 transition-colors"
                >
                    <span class="material-symbols-outlined mr-3 text-[20px]">
                        logout
                    </span>
                    <span class="text-sm font-label font-bold uppercase tracking-widest">Logout</span>
                </button>

                <button 
                    @click="toggleDarkMode"
                    class="flex items-center w-full px-4 py-3 rounded-md text-outline hover:text-on-surface hover:bg-surface-container-high transition-colors"
                >
                    <span class="material-symbols-outlined mr-3 text-[20px]">
                        {{ isDarkMode ? 'light_mode' : 'dark_mode' }}
                    </span>
                    <span class="text-sm font-label font-medium">{{ isDarkMode ? 'Light Mode' : 'Dark Mode' }}</span>
                </button>
            </div>
        </nav>

        <!-- Main Wrapper -->
        <div class="flex-1 flex flex-col min-h-screen transition-all duration-300" :class="['lg:ml-64']">
            <!-- TopNavBar -->
            <header class="sticky top-0 z-40 w-full bg-surface-container/80 backdrop-blur-md shadow-sm border-b border-outline-variant/20 flex justify-between items-center px-4 sm:px-8 h-16 sm:h-20 transition-all">
                <div class="flex items-center">
                    <!-- Hamburger (Mobile Only) -->
                    <button @click="toggleSidebar" class="lg:hidden mr-4 p-2 text-outline hover:bg-surface-container-high rounded-md transition-colors">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    
                    <h2 class="font-headline text-xl sm:text-[28px] font-bold text-on-surface leading-tight tracking-tight truncate">
                        {{ navItems.find(i => $page.url === i.href)?.name || 'Dashboard' }}
                    </h2>
                </div>
                
                <div class="flex items-center space-x-3 sm:space-x-6 relative">
                    <!-- Notification Bell Dropdown -->
                    <div class="relative">
                        <button 
                            @click="isNotificationsOpen = !isNotificationsOpen" 
                            class="text-outline hover:bg-surface-container-high rounded-full p-2 transition-colors relative"
                        >
                            <span class="material-symbols-outlined text-[20px] sm:text-[24px]">notifications</span>
                            <span v-if="unreadCount > 0" class="absolute top-2 right-2 w-2 h-2 bg-error rounded-full border border-surface-container-lowest"></span>
                        </button>

                        <!-- Notification Dropdown Menu -->
                        <div v-if="isNotificationsOpen" class="absolute right-0 mt-4 w-80 sm:w-96 bg-surface-container-lowest rounded-2xl shadow-xl border border-outline-variant/30 flex flex-col overflow-hidden animate-in fade-in slide-in-from-top-4 duration-200 z-[60]">
                            <div class="px-6 py-4 border-b border-outline-variant/30 flex justify-between items-center bg-surface-container-low/50">
                                <h3 class="font-headline font-bold text-on-surface">Intelligence Feed</h3>
                                <button v-if="unreadCount > 0" @click="markAllRead" class="text-xs font-bold text-primary hover:underline uppercase tracking-widest">Mark All Read</button>
                            </div>

                            <div class="max-h-[32rem] overflow-y-auto">
                                <div v-for="n in notifications" :key="n.id" class="p-4 border-b border-outline-variant/20 hover:bg-surface-container-low transition-colors group">
                                    <div class="flex gap-4">
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" 
                                            :class="[
                                                n.data.type === 'cheque' ? 'bg-error/10 text-error' : 
                                                n.data.type === 'reminder' ? 'bg-secondary/10 text-secondary' : 'bg-primary/10 text-primary'
                                            ]"
                                        >
                                            <span class="material-symbols-outlined">{{ n.data.icon || 'info' }}</span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-sm font-bold text-on-surface mb-1 truncate">{{ n.data.title }}</h4>
                                            <p class="text-xs text-on-surface-variant line-clamp-2 leading-relaxed mb-2">{{ n.data.message }}</p>
                                            <div class="flex justify-between items-center">
                                                <span class="text-[10px] font-bold text-outline uppercase tracking-tighter">{{ new Date(n.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</span>
                                                <button @click="markAsRead(n.id)" class="opacity-0 group-hover:opacity-100 text-[10px] font-black text-primary uppercase tracking-widest transition-opacity">Done</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="notifications.length === 0" class="py-12 text-center text-outline italic text-sm">
                                    All clear! No new intelligence.
                                </div>
                            </div>

                            <Link href="/notifications" class="px-6 py-4 text-center bg-primary text-on-primary text-xs font-black uppercase tracking-widest hover:bg-primary-container transition-colors">
                                View All Inbox
                            </Link>
                        </div>
                    </div>

                    <div class="h-8 w-8 sm:h-10 sm:w-10 rounded-full overflow-hidden bg-surface-container-high border border-outline-variant/20 cursor-pointer">
                        <img src="https://ui-avatars.com/api/?name=Admin&background=004ced&color=fff" alt="User Avatar" class="w-full h-full object-cover" />
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 p-4 sm:p-8 transition-all">
                <slot />
            </main>

            <!-- Footer -->
            <footer class="w-full border-t border-outline-variant/20 bg-surface-container-lowest flex flex-col sm:flex-row justify-between items-center px-4 sm:px-8 py-4 sm:py-4 gap-4 sm:gap-0 transition-colors">
                <div class="text-outline font-label text-[10px] font-bold uppercase tracking-widest text-center sm:text-left">
                    © 2024 Precision Admin • v1.0.0
                </div>
                <div class="flex space-x-4 sm:space-x-6">
                    <a href="#" class="text-outline hover:text-primary transition-colors font-label text-[10px] font-bold uppercase tracking-widest">Status</a>
                    <a href="#" class="text-outline hover:text-primary transition-colors font-label text-[10px] font-bold uppercase tracking-widest">Privacy</a>
                    <a href="#" class="text-outline hover:text-primary transition-colors font-label text-[10px] font-bold uppercase tracking-widest">Docs</a>
                </div>
            </footer>
        </div>
    </div>
</template>

<style>
.font-headline { font-family: 'Manrope', sans-serif; }
.font-label { font-family: 'Inter', sans-serif; }
.fill-1 {
    font-variation-settings: 'FILL' 1;
}

/* Hide scrollbar for layout */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-track {
    background: transparent;
}
::-webkit-scrollbar-thumb {
    background: #c3c5d9;
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: #737688;
}
</style>

