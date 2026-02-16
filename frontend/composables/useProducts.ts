import { ref } from 'vue';

export interface Product {
    id: number;
    name: string;
    price: number;
    image: string;
    categoryId: number;
    category_slug_url?: string;
    description: string;
    slug: string;
}

export interface Category {
    id: number;
    name: string;
    slug?: string;
    slug_url?: string;
    parentId?: number;
    image?: string;
    children?: Category[];
}

export const useProducts = () => {
    const products = ref<Product[]>([]);
    const categories = ref<Category[]>([]);
    const featuredCategories = ref<Category[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);

    const featuredCategory = ref<Category>({
        id: 0,
        name: 'Featured Collection',
        image: 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?q=80&w=1000'
    });

    const config = useRuntimeConfig();
    const apiUrl = 'http://localhost:8000/api/shop'; // In a real app, this would be in nuxt.config

    const fetchCategories = async () => {
        try {
            const data = await $fetch<Category[]>(`${apiUrl}/categories`);
            categories.value = data;
            if (data.length > 0 && data[0]) {
                const firstCat = data[0];
                featuredCategory.value = {
                    id: firstCat.id || 0,
                    name: `Explore ${firstCat.name}`,
                    image: firstCat.image || 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?q=80&w=1000'
                };
            }
        } catch (err) {
            console.error('Error fetching categories:', err);
            error.value = 'Failed to load categories';
        }
    };

    const fetchFeaturedCategories = async () => {
        try {
            const data = await $fetch<Category[]>(`${apiUrl}/featured-categories`);
            featuredCategories.value = data;
        } catch (err) {
            console.error('Error fetching featured categories:', err);
        }
    };

    const fetchProducts = async (params: any = {}) => {
        loading.value = true;
        try {
            const response = await $fetch<{ data: Product[] }>(`${apiUrl}/products`, {
                params: {
                    ...params,
                    per_page: 50 // Fetch enough for client-side filtering demo
                }
            });
            products.value = response.data;
        } catch (err) {
            console.error('Error fetching products:', err);
            error.value = 'Failed to load products';
        } finally {
            loading.value = false;
        }
    };

    const fetchProductBySlug = async (slug: string) => {
        loading.value = true;
        try {
            const data = await $fetch<any>(`${apiUrl}/products/${slug}`);
            return data;
        } catch (err) {
            console.error('Error fetching product:', err);
            error.value = 'Failed to load product details';
            return null;
        } finally {
            loading.value = false;
        }
    };

    const fetchCategoryBySlug = async (slug: string) => {
        loading.value = true;
        try {
            const data = await $fetch<any>(`${apiUrl}/category/${slug}`);
            return data;
        } catch (err) {
            console.error('Error fetching category:', err);
            return null;
        } finally {
            loading.value = false;
        }
    };

    const resolveSlug = async (slug: string) => {
        loading.value = true;
        try {
            const data = await $fetch<any>(`${apiUrl}/resolve/${slug}`);
            return data;
        } catch (err) {
            console.error('Error resolving slug:', err);
            return null;
        } finally {
            loading.value = false;
        }
    };

    // Initial fetch if needed, or let component handle it
    const init = async () => {
        await Promise.all([
            fetchCategories(),
            fetchFeaturedCategories(),
            fetchProducts()
        ]);
    };

    return {
        products,
        categories,
        featuredCategories,
        featuredCategory,
        loading,
        error,
        fetchProducts,
        fetchCategories,
        fetchFeaturedCategories,
        fetchProductBySlug,
        fetchCategoryBySlug,
        resolveSlug,
        init
    };
};
