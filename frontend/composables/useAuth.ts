import { ref, onMounted } from 'vue';

export interface User {
    id: number;
    name: string;
    email: string;
    customer_group_id?: number | null;
}

export const useAuth = () => {
    const user = useState<User | null>('auth_user', () => null);
    const token = useState<string | null>('auth_token', () => null);
    const apiUrl = 'http://localhost:8000/api';

    const fetchUser = async () => {
        if (!token.value) return;

        try {
            const response = await $fetch<{ success: boolean, data: User }>(`${apiUrl}/get-customer`, {
                headers: {
                    Authorization: `Bearer ${token.value}`
                }
            });

            if (response.success) {
                user.value = response.data;
                if (process.client) {
                    localStorage.setItem('customer_user', JSON.stringify(response.data));
                }
            }
        } catch (e) {
            console.error('Failed to fetch user profile', e);
            if ((e as any).status === 401) {
                logout();
            }
        }
    };

    const initAuth = async () => {
        if (process.client) {
            const savedToken = localStorage.getItem('customer_token');
            const savedUser = localStorage.getItem('customer_user');

            if (savedToken) {
                token.value = savedToken;
            }
            if (savedUser) {
                try {
                    user.value = JSON.parse(savedUser);
                } catch (e) {
                    console.error('Failed to parse user from localStorage', e);
                }
            }

            if (token.value) {
                await fetchUser();
            }
        }
    };

    const setAuth = (newUser: User, newToken: string) => {
        user.value = newUser;
        token.value = newToken;

        if (process.client) {
            localStorage.setItem('customer_token', newToken);
            localStorage.setItem('customer_user', JSON.stringify(newUser));
        }
    };

    const logout = () => {
        user.value = null;
        token.value = null;

        if (process.client) {
            localStorage.removeItem('customer_token');
            localStorage.removeItem('customer_user');
        }

        navigateTo('/customer/login');
    };

    const isLoggedIn = computed(() => !!token.value);

    return {
        user,
        token,
        isLoggedIn,
        initAuth,
        setAuth,
        logout
    };
};
