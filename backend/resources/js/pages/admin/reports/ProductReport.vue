<template>
  <q-page class="q-pa-md">
    <div class="q-pa-md" style="max-width: 1400px; margin: 0 auto">
      <!-- Header -->
      <div class="row items-center justify-between q-mb-lg">
        <div>
          <div class="text-h4 text-weight-bold text-grey-9">Product Reports</div>
          <div class="text-subtitle2 text-grey-6 q-mt-xs">
            View detailed product information including categories and suppliers
          </div>
        </div>
      </div>

      <!-- Filters Card -->
      <q-card flat bordered class="q-mb-md">
        <q-card-section>
          <div class="row q-col-gutter-md">
            <!-- Search -->
            <div class="col-12 col-md-3">
              <q-input
                v-model="filters.search"
                outlined
                dense
                placeholder="Search by name, SKU..."
                @update:model-value="handleSearch"
                debounce="500"
                clearable
              >
                <template v-slot:prepend>
                  <q-icon name="search" />
                </template>
              </q-input>
            </div>

            <!-- Category Filter -->
            <div class="col-12 col-md-3">
              <q-select
                v-model="filters.category_id"
                :options="categoryOptions"
                outlined
                dense
                label="Filter by Category"
                clearable
                emit-value
                map-options
                @update:model-value="fetchReports"
              >
                <template v-slot:prepend>
                  <q-icon name="category" />
                </template>
              </q-select>
            </div>

             <!-- Supplier Filter -->
            <div class="col-12 col-md-3">
              <q-select
                v-model="filters.supplier_id"
                :options="supplierOptions"
                outlined
                dense
                label="Filter by Supplier"
                clearable
                emit-value
                map-options
                @update:model-value="fetchReports"
              >
                <template v-slot:prepend>
                  <q-icon name="local_shipping" />
                </template>
              </q-select>
            </div>

            <!-- Status Filter -->
            <div class="col-12 col-md-2">
              <q-select
                v-model="filters.status"
                :options="statusOptions"
                outlined
                dense
                label="Filter by Status"
                clearable
                emit-value
                map-options
                @update:model-value="fetchReports"
              >
                <template v-slot:prepend>
                  <q-icon name="flag" />
                </template>
              </q-select>
            </div>


            <div class="col-12 col-md-1">
              <q-btn
                outline
                color="grey-7"
                icon="refresh"
                @click="resetFilters"
                class="full-width"
              >
                <q-tooltip>Reset Filters</q-tooltip>
              </q-btn>
            </div>
          </div>
        </q-card-section>
      </q-card>

      <q-card flat bordered>
        <q-table
          :rows="products"
          :columns="columns"
          row-key="id"
          :loading="loading"
          :pagination="pagination"
          @request="onRequest"
          flat
        >
          <!-- Loading -->
          <template v-slot:loading>
            <q-inner-loading showing color="primary">
              <q-spinner-gears size="50px" />
            </q-inner-loading>
          </template>

          <template v-slot:header="props">
            <q-tr :props="props">
              <q-th
                v-for="col in props.cols"
                :key="col.name"
                :props="props"
              >
                {{ col.label }}
              </q-th>
            </q-tr>
          </template>

          <template v-slot:body="props">
            <q-tr :props="props">
              <q-td auto-width>
                <q-btn
                  size="sm"
                  color="primary"
                  round
                  dense
                  @click="toggleExpand(props)"
                  :icon="props.expand ? 'remove' : 'add'"
                />
              </q-td>

              <q-td key="image" :props="props">
                <q-avatar size="40px" rounded>
                  <img
                    v-if="props.row.image"
                    :src="props.row.image"
                    :alt="props.row.name"
                  />
                  <q-icon v-else name="image" size="24px" color="grey-5" />
                </q-avatar>
              </q-td>

              <q-td key="sku" :props="props">
                <div class="text-primary text-weight-medium">
                  {{ props.row.sku }}
                </div>
              </q-td>

              <q-td key="name" :props="props">
                <div class="text-weight-bold">
                  {{ props.row.name }}
                </div>
              </q-td>

              <q-td key="categories" :props="props">
                <div class="ellipsis" style="max-width: 200px">
                  {{ props.row.categories }}
                </div>
              </q-td>

              <q-td key="suppliers" :props="props">
                <div v-if="props.row.suppliers && props.row.suppliers.length > 0">
                    <div v-for="(sup, index) in props.row.suppliers.slice(0, 1)" :key="index" class="text-caption">
                         <span class="text-weight-medium">{{ sup.name }}</span>: ${{ sup.price }}
                    </div>
                    <div v-if="props.row.suppliers.length > 1" class="text-grey-6 text-caption">
                        + {{ props.row.suppliers.length - 1 }} more
                    </div>
                </div>
                <div v-else class="text-grey-6 text-italic">No suppliers</div>
              </q-td>

              <q-td key="base_price" :props="props">
                ${{ props.row.base_price }}
              </q-td>

              <q-td key="status" :props="props">
                <q-badge
                  :color="getStatusColor(props.row.status)"
                  :label="getStatusLabel(props.row.status)"
                />
              </q-td>
            </q-tr>

            <!-- Expanded Section -->
            <q-tr v-show="props.expand" :props="props" class="bg-grey-1">
              <q-td colspan="100%">
                <div class="q-pa-md">
                   <q-card flat bordered class="bg-white">
                    <q-tabs
                      v-model="detailTabs[props.row.id]"
                      dense
                      class="text-grey"
                      active-color="primary"
                      indicator-color="primary"
                      align="left"
                      narrow-indicator
                      @update:model-value="val => detailTabs[props.row.id] = val"
                    >
                      <q-tab :name="'basic_' + props.row.id" label="Basic Info" icon="info" />
                      <q-tab :name="'categories_' + props.row.id" label="Categories" icon="category" />
                      <q-tab :name="'compatible_' + props.row.id" label="Compatible" icon="widgets" />
                      <q-tab :name="'price_' + props.row.id" :label="props.row.type === 'standard' ? 'Price' : 'Price & Bundles'" icon="attach_money" />
                      <q-tab :name="'images_' + props.row.id" label="Images" icon="image" />
                      <q-tab :name="'seo_' + props.row.id" label="SEO" icon="search" />
                      <q-tab :name="'suppliers_' + props.row.id" label="Suppliers" icon="local_shipping" />
                    </q-tabs>

                    <q-separator />

                    <q-tab-panels v-model="detailTabs[props.row.id]" animated>
                      <!-- Basic Info -->
                      <q-tab-panel :name="'basic_' + props.row.id">
                        <div class="row q-col-gutter-md">
                          <div class="col-12 col-md-6">
                            <div class="text-weight-bold">Name:</div>
                            <div class="q-mb-md">{{ props.row.name }}</div>
                            <div class="text-weight-bold">SKU:</div>
                            <div class="q-mb-md">{{ props.row.sku }}</div>
                            <div class="text-weight-bold">Slug:</div>
                            <div class="q-mb-md">{{ props.row.slug }}</div>
                          </div>
                          <div class="col-12 col-md-6">
                             <div class="text-weight-bold">Type:</div>
                            <div class="q-mb-md text-capitalize">{{ props.row.type }}</div>
                             <div class="text-weight-bold">Status:</div>
                            <div class="q-mb-md">
                                <q-badge :color="getStatusColor(props.row.status)" :label="getStatusLabel(props.row.status)" />
                            </div>
                          </div>
                          <div class="col-12">
                            <div class="text-weight-bold">Short Description:</div>
                            <div class="q-mb-md text-grey-8">{{ props.row.short_description || 'None' }}</div>
                            <div class="text-weight-bold">Description:</div>
                            <div class="description-preview q-pa-sm border-grey-3 rounded-borders bg-grey-1" v-html="props.row.description || 'None'"></div>
                          </div>
                        </div>
                      </q-tab-panel>

                      <!-- Categories -->
                      <q-tab-panel :name="'categories_' + props.row.id">
                        <div class="row items-center q-gutter-sm">
                           <div class="text-weight-bold col-12 q-mb-xs">Default Category:</div>
                           <q-badge v-if="props.row.default_category" color="primary" class="q-pa-sm">
                             {{ props.row.default_category.name }}
                           </q-badge>
                           <span v-else class="text-grey-6 italic">No default category</span>

                           <div class="text-weight-bold col-12 q-mt-md q-mb-xs">All Categories:</div>
                           <div class="row q-gutter-xs">
                             <q-badge v-for="cat in props.row.all_categories" :key="cat.id" outline color="blue-grey" class="q-pa-xs">
                               {{ cat.name }}
                             </q-badge>
                           </div>
                        </div>
                      </q-tab-panel>

                      <!-- Compatible Products -->
                      <q-tab-panel :name="'compatible_' + props.row.id">
                        <q-list dense bordered separator class="rounded-borders" v-if="props.row.compatible_products.length > 0">
                           <q-item v-for="(p, i) in props.row.compatible_products" :key="i">
                             <q-item-section avatar>
                               <q-icon name="widgets" color="primary" />
                             </q-item-section>
                             <q-item-section>
                               <q-item-label>{{ p.name }}</q-item-label>
                               <q-item-label caption>SKU: {{ p.sku }}</q-item-label>
                             </q-item-section>
                           </q-item>
                        </q-list>
                        <div v-else class="text-grey-6 italic text-center q-pa-md">
                           No compatible products assigned
                        </div>
                      </q-tab-panel>

                      <!-- Price & Bundles -->
                      <q-tab-panel :name="'price_' + props.row.id">
                        <div class="row q-col-gutter-lg">
                          <div class="col-12 col-md-6">
                            <div class="text-h6 q-mb-sm">Pricing Info</div>
                            <q-list dense>
                              <q-item v-if="props.row.type === 'bundle'">
                                <q-item-section>Components Subtotal</q-item-section>
                                <q-item-section side class="text-weight-bold text-black">${{ formatPrice(parseFloat(props.row.bundle_subtotal) - parseFloat(props.row.price)) }}</q-item-section>
                              </q-item>
                              <q-item>
                                <q-item-section>{{ props.row.type === 'bundle' ? 'Bundle Base Price' : 'Base Price' }}</q-item-section>
                                <q-item-section side class="text-weight-bold text-black">${{ formatPrice(props.row.price) }}</q-item-section>
                              </q-item>
                              <q-item>
                                <q-item-section>GP Percentage</q-item-section>
                                <q-item-section side class="text-weight-bold text-black">{{ formatPrice(props.row.gp_percentage) }}%</q-item-section>
                              </q-item>
                              <q-item class="bg-primary text-white rounded-borders">
                                <q-item-section>Total Price</q-item-section>
                                <q-item-section side class="text-weight-bold text-white">${{ formatPrice(props.row.total_price) }}</q-item-section>
                              </q-item>
                            </q-list>
                          </div>
                          
                          <div class="col-12 col-md-6" v-if="props.row.type === 'bundle'">
                             <div class="text-h6 q-mb-sm">Bundle Details</div>
                             <q-list dense bordered separator>
                               <q-item v-for="(item, i) in props.row.bundle_items" :key="i">
                                 <q-item-section>
                                   {{ item.name }} ({{ item.qty }}x)
                                 </q-item-section>
                                 <q-item-section side>
                                   ${{ item.price }} / unit
                                 </q-item-section>
                               </q-item>
                             </q-list>
                             <div class="q-mt-sm text-right">
                               <div class="text-caption text-grey-7">Bundle Subtotal: ${{ formatPrice(props.row.bundle_subtotal) }}</div>
                               <div class="text-subtitle1 text-weight-bold text-primary">Final Bundle Price: ${{ formatPrice(props.row.bundle_final_price) }}</div>
                             </div>
                          </div>
                        </div>
                      </q-tab-panel>

                      <!-- Images -->
                      <q-tab-panel :name="'images_' + props.row.id">
                        <div class="row q-col-gutter-md">
                          <div v-for="(img, i) in props.row.images" :key="i" class="col-12 col-sm-6 col-md-3">
                            <q-card flat bordered class="overflow-hidden">
                              <q-img :src="img.url" :ratio="1" fit="cover">
                                 <q-badge v-if="img.is_primary" floating color="warning">Primary</q-badge>
                              </q-img>
                              <div class="q-pa-xs text-caption text-center ellipsis" :title="img.alt">
                                {{ img.alt || 'No alt text' }}
                              </div>
                            </q-card>
                          </div>
                          <div v-if="props.row.images.length === 0" class="col-12 text-center q-pa-md text-grey-6 italic">
                             No images found for this product
                          </div>
                        </div>
                      </q-tab-panel>

                      <!-- SEO -->
                      <q-tab-panel :name="'seo_' + props.row.id">
                         <div class="text-weight-bold">Meta Title:</div>
                         <div class="q-mb-md text-grey-9">{{ props.row.meta_title || 'N/A' }}</div>
                         <div class="text-weight-bold">Meta Description:</div>
                         <div class="q-mb-md text-grey-9 bg-grey-2 q-pa-sm rounded-borders">{{ props.row.meta_description || 'N/A' }}</div>
                      </q-tab-panel>

                      <!-- Suppliers -->
                      <q-tab-panel :name="'suppliers_' + props.row.id">
                        <q-table
                          dense
                          flat
                          bordered
                          :rows="props.row.suppliers"
                          :columns="[
                            { name: 'name', label: 'Supplier Name', field: 'name', align: 'left' },
                            { name: 'price', label: 'Price', field: 'price', align: 'right', format: val => `$${val}` }
                          ]"
                          row-key="name"
                          hide-pagination
                        />
                        <div v-if="props.row.suppliers.length === 0" class="text-center q-pa-md text-grey-6 italic">
                           No suppliers assigned
                        </div>
                      </q-tab-panel>
                    </q-tab-panels>
                  </q-card>
                </div>
              </q-td>
            </q-tr>
          </template>

          <template v-slot:no-data>
            <div class="full-width row flex-center q-gutter-sm q-pa-lg">
              <q-icon name="assessment" size="50px" color="grey-5" />
              <div class="text-center">
                <div class="text-h6 text-grey-7">No data found</div>
                <div class="text-grey-6 q-mt-sm">
                  Try adjusting your search or filters
                </div>
              </div>
            </div>
          </template>
        </q-table>
      </q-card>
    </div>
  </q-page>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import axios from 'axios'

export default {
  name: 'ProductReport',

  setup() {
    const $q = useQuasar()

    // Reactive data
    const loading = ref(false)
    const products = ref([])
    const filters = ref({
      search: '',
      category_id: null,
      supplier_id: null,
      status: null
    })

    const pagination = ref({
      sortBy: 'created_at',
      descending: true,
      page: 1,
      rowsPerPage: 10,
      rowsNumber: 0
    })

    const categoryOptions = ref([])
    const supplierOptions = ref([]) // To be populated
    const statusOptions = ref([
      { label: 'Active', value: 'active' },
      { label: 'Inactive', value: 'inactive' },
      { label: 'Draft', value: 'draft' }
    ])

    const columns = [
      { name: 'expand', label: '', align: 'left', sortable: false },
      { name: 'image', label: 'Image', align: 'left', sortable: false },
      { name: 'sku', label: 'SKU', field: 'sku', align: 'left', sortable: false },
      { name: 'name', label: 'Product Name', field: 'name', align: 'left', sortable: false },
      { name: 'categories', label: 'Categories', field: 'categories', align: 'left', sortable: false },
      { name: 'suppliers', label: 'Suppliers & Price', align: 'left', sortable: false },
      { name: 'base_price', label: 'Base Price', field: 'base_price', align: 'left', sortable: false },
      { name: 'status', label: 'Status', field: 'status', align: 'center', sortable: false },
    ]

    const detailTabs = ref({}) // To track active tab per expanded row

    const fetchReports = async () => {
      try {
        loading.value = true

        const params = {
          page: pagination.value.page,
          per_page: pagination.value.rowsPerPage,
           ...filters.value
        }

        // Clean null/empty params
         Object.keys(params).forEach(key => {
          if (params[key] === null || params[key] === '') {
            delete params[key]
          }
        })


        const response = await axios.get('/admin/reports/products', { params })

        products.value = response.data.data
        pagination.value.rowsNumber = response.data.total
        pagination.value.page = response.data.current_page
        pagination.value.rowsPerPage = response.data.per_page


      } catch (error) {
        console.error('Error fetching reports:', error)
        $q.notify({
          type: 'negative',
          message: 'Failed to load reports',
          caption: error.response?.data?.message || error.message
        })
      } finally {
        loading.value = false
      }
    }

    const fetchCategories = async () => {
      try {
        const response = await axios.get('/admin/categories')
        categoryOptions.value = response.data.map(cat => ({
          label: cat.name,
          value: cat.id
        }))
      } catch (error) {
        console.error('Error fetching categories:', error)
      }
    }

    const fetchSuppliers = async () => {
        try {
            const response = await axios.get('/admin/suppliers')
             supplierOptions.value = response.data.suppliers.data.map(s => ({
                  label: s.name,
                  value: s.id
             }))

        } catch (error) {
            console.error('Error fetching suppliers:', error)
        }
    }

    const onRequest = (props) => {
      const { page, rowsPerPage } = props.pagination
      pagination.value.page = page
      pagination.value.rowsPerPage = rowsPerPage
      fetchReports()
    }

    const handleSearch = () => {
      pagination.value.page = 1
      fetchReports()
    }

    const resetFilters = () => {
      filters.value = {
        search: '',
        category_id: null,
        supplier_id: null,
        status: null
      }
      pagination.value.page = 1
      fetchReports()
    }

    const getStatusColor = (status) => {
      const colors = {
        active: 'positive',
        inactive: 'grey',
        draft: 'warning'
      }
      return colors[status] || 'grey'
    }

    const getStatusLabel = (status) => {
      if (!status) return 'N/A'
      return status.charAt(0).toUpperCase() + status.slice(1)
    }

    onMounted(() => {
        fetchReports()
        fetchCategories()
        fetchSuppliers()
    })

    const toggleExpand = (props) => {
      props.expand = !props.expand
      if (props.expand && !detailTabs.value[props.row.id]) {
        detailTabs.value[props.row.id] = 'basic_' + props.row.id
      }
    }

    const formatPrice = (val) => {
      const num = parseFloat(val)
      return isNaN(num) ? '0.00' : num.toFixed(2)
    }

    return {
      loading,
      products,
      filters,
      pagination,
      categoryOptions,
      supplierOptions,
      statusOptions,
      columns,
      fetchReports,
      onRequest,
      handleSearch,
      resetFilters,
      getStatusColor,
      getStatusLabel,
      detailTabs,
      toggleExpand,
      formatPrice
    }
  }
}
</script>
