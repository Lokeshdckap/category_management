<template>
  <q-page class="q-pa-md">
    <div class="q-pa-md" style="max-width: 1400px; margin: 0 auto">
      <!-- Header -->
      <div class="row items-center justify-between q-mb-lg">
        <div>
          <div class="text-h4 text-weight-bold text-grey-9">Products</div>
          <div class="text-subtitle2 text-grey-6 q-mt-xs">
            Manage your product catalog
          </div>
        </div>
        <q-btn
          unelevated
          color="primary"
          label="Add New Product"
          icon="add"
          @click="navigateToCreate"

          
        />
      </div>

      <!-- Filters Card -->
      <q-card flat bordered class="q-mb-md">
        <q-card-section>
          <div class="row q-col-gutter-md">
            <!-- Search -->
            <div class="col-12 col-md-4">
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
                @update:model-value="fetchProducts"
              >
                <template v-slot:prepend>
                  <q-icon name="category" />
                </template>
              </q-select>
            </div>

            <!-- Status Filter -->
            <div class="col-12 col-md-3">
              <q-select
                v-model="filters.status"
                :options="statusOptions"
                outlined
                dense
                label="Filter by Status"
                clearable
                emit-value
                map-options
                @update:model-value="fetchProducts"
              >
                <template v-slot:prepend>
                  <q-icon name="flag" />
                </template>
              </q-select>
            </div>


            <div class="col-12 col-md-2">
              <q-btn
                outline
                color="grey-7"
                label="Reset"
                icon="refresh"
                @click="resetFilters"
                class="full-width"
              />
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
          class="products-table"
        >
          <!-- Loading -->
          <template v-slot:loading>
            <q-inner-loading showing color="primary">
              <q-spinner-gears size="50px" />
            </q-inner-loading>
          </template>

          <!-- Header -->
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
        
              <q-td key="image" :props="props">
                <q-avatar size="60px" rounded>
                  <img
                    v-if="props.row.first_image"
                    :src="props.row.first_image"
                    :alt="props.row.name"
                  />
                  <q-icon v-else name="image" size="30px" color="grey-5" />
                </q-avatar>
              </q-td>

         
              <q-td key="id" :props="props">
                <div class="text-grey-8 text-weight-medium">
                  #{{ props.row.id }}
                </div>
              </q-td>

  
              <q-td key="sku" :props="props">
                <div class="text-primary text-weight-medium">
                  {{ props.row.sku }}
                </div>
              </q-td>

              
              <q-td key="name" :props="props">
                <div class="text-grey-9 text-weight-bold">
                  {{ props.row.name }}
                </div>
                <div class="text-grey-6 text-caption" v-if="props.row.default_category">
                  <q-icon name="label" size="14px" />
                  {{ props.row.default_category.name }}
                </div>
              </q-td>

              <q-td key="short_description" :props="props">
                <div class="text-grey-7 ellipsis-2-lines" style="max-width: 300px">
                  {{ props.row.short_description || 'No description' }}
                </div>
              </q-td>

              <q-td key="status" :props="props">
                <q-toggle
                  v-model="props.row.status"
                  :true-value="'active'"
                  :false-value="'inactive'"
                  color="positive"
                  @update:model-value="toggleStatus(props.row)"
                />
              </q-td>

    
              <q-td key="created_at" :props="props">
                <div class="text-grey-7">
                  {{ formatDate(props.row.created_at) }}
                </div>
              </q-td>

              <q-td key="actions" :props="props">
                <div class="row q-gutter-xs no-wrap">
                  <q-btn
                    flat
                    dense
                    round
                    icon="edit"
                    color="primary"
                    size="sm"
                    @click="navigateToEdit(props.row)"
                  >
                    <q-tooltip>Edit</q-tooltip>
                  </q-btn>

                  <!-- <q-btn
                    flat
                    dense
                    round
                    icon="visibility"
                    color="info"
                    size="sm"
                    @click="viewProduct(props.row)"
                  >
                    <q-tooltip>View</q-tooltip>
                  </q-btn> -->

                  <q-btn
                    flat
                    dense
                    round
                    icon="delete"
                    color="negative"
                    size="sm"
                    @click="confirmDelete(props.row)"
                  >
                    <q-tooltip>Delete</q-tooltip>
                  </q-btn>
                </div>
              </q-td>
            </q-tr>
          </template>

          <template v-slot:no-data>
            <div class="full-width row flex-center q-gutter-sm q-pa-lg">
              <q-icon name="inventory_2" size="50px" color="grey-5" />
              <div class="text-center">
                <div class="text-h6 text-grey-7">No products found</div>
                <div class="text-grey-6 q-mt-sm">
                  {{ filters.search ? 'Try adjusting your search or filters' : 'Click "Add New Product" to create your first product' }}
                </div>
              </div>
            </div>
          </template>
        </q-table>
      </q-card>
    </div>

    <q-dialog v-model="viewDialog" position="right" maximized>
      <q-card style="width: 500px; max-width: 80vw">
        <!-- ... existing content ... -->
      </q-card>
    </q-dialog>


    <q-dialog v-model="typeDialog">
  <q-card style="min-width: 320px">
    
    <q-card-section class="text-h6">
      Select Product Type
    </q-card-section>

    <q-card-section>
      <q-select
        v-model="selectedType"
        :options="productTypes"
        label="Product Type"
        outlined
        dense
        emit-value
        map-options
      />
    </q-card-section>

    <q-card-actions align="right">
      <q-btn flat label="Cancel" color="grey" v-close-popup />
      <q-btn
        label="Submit"
        color="primary"
        :disable="!selectedType"
        @click="submitType"
      />
    </q-card-actions>

  </q-card>
</q-dialog>

  </q-page>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useQuasar, date } from 'quasar'
import axios from 'axios'

import { useRouter } from 'vue-router'

export default {
  name: 'ProductList',

  setup() {
    // ... existing setup ...
    const $q = useQuasar()
    const router = useRouter()

    // Reactive data
    const loading = ref(false)
    const products = ref([])
    const selectedProduct = ref(null)
    const viewDialog = ref(false)
    const imageSlide = ref(0)
    
     const filters = ref({
      search: '',
      category_id: null,
      status: null
    })

    const pagination = ref({
      sortBy: 'created_at',
      descending: true,
      page: 1,
      rowsPerPage: 10,
      rowsNumber: 0
    })

    const typeDialog = ref(false)
    const selectedType = ref(null)

    const productTypes = [
      { label: 'Standard', value: 'standard' },
      { label: 'Bundle', value: 'bundle' }
    ]

    const categoryOptions = ref([])
    const statusOptions = ref([
      { label: 'Active', value: 'active' },
      { label: 'Inactive', value: 'inactive' },
      { label: 'Draft', value: 'draft' }
    ])

    // Table columns
    const columns = [
      {
        name: 'image',
        label: 'Image',
        align: 'left',
        sortable: false
      },
      {
        name: 'id',
        label: 'ID',
        field: 'id',
        align: 'left',
        sortable: true
      },
      {
        name: 'sku',
        label: 'SKU',
        field: 'sku',
        align: 'left',
        sortable: true
      },
      {
        name: 'name',
        label: 'Product Name',
        field: 'name',
        align: 'left',
        sortable: true
      },
      {
        name: 'short_description',
        label: 'Description',
        field: 'short_description',
        align: 'left',
        sortable: false
      },
      {
        name: 'status',
        label: 'Status',
        field: 'status',
        align: 'center',
        sortable: true
      },
      {
        name: 'created_at',
        label: 'Created',
        field: 'created_at',
        align: 'left',
        sortable: true
      },
      {
        name: 'actions',
        label: 'Actions',
        align: 'center',
        sortable: false
      }
    ]

    const fetchProducts = async () => {
      try {
        loading.value = true

        const params = {
          page: pagination.value.page,
          per_page: pagination.value.rowsPerPage,
          sort_by: pagination.value.sortBy,
          sort_order: pagination.value.descending ? 'desc' : 'asc',
          ...filters.value
        }

        Object.keys(params).forEach(key => {
          if (params[key] === null || params[key] === '') {
            delete params[key]
          }
        })

        const response = await axios.get('/admin/products', { params })

        if (response.data.data.data) {
          products.value = response.data.data.data
          pagination.value.rowsNumber = response.data.data.total
        } else {
          products.value = response.data.data
        }

      } catch (error) {
        console.error('Error fetching products:', error)
        $q.notify({
          type: 'negative',
          message: 'Failed to load products',
          caption: error.response?.data?.message || error.message,
          position: 'top'
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

    const onRequest = (props) => {
      const { page, rowsPerPage, sortBy, descending } = props.pagination

      pagination.value.page = page
      pagination.value.rowsPerPage = rowsPerPage
      pagination.value.sortBy = sortBy
      pagination.value.descending = descending

      fetchProducts()
    }

    const handleSearch = () => {
      pagination.value.page = 1
      fetchProducts()
    }

    const resetFilters = () => {
      filters.value = {
        search: '',
        category_id: null,
        status: null
      }
      pagination.value.page = 1
      fetchProducts()
    }

    const viewProduct = async (product) => {
        // ... existing implementation ...
    }

    const navigateToCreate = () => {
      typeDialog.value = true
    }

    const submitType = () => {
      if(selectedType.value){
       router.push(`/products/create?type=${selectedType.value}`)
       typeDialog.value = false
      }
    }

    const navigateToEdit = (product) => {
      router.push(`/products/${product.uuid}/edit`)
    }

    const confirmDelete = (product) => {
      $q.dialog({
        title: 'Confirm Delete',
        message: `Are you sure you want to delete "${product.name}"? This action cannot be undone.`,
        cancel: true,
        persistent: true,
         ok: {
          label: 'Delete',
          color: 'negative',
          flat: true
        },
        cancel: {
          label: 'Cancel',
          color: 'grey-7',
          flat: true
        }
      }).onOk(async () => {
        await deleteProduct(product)
      })
    }

    const deleteProduct = async (product) => {
      try {
        loading.value = true
        await axios.delete(`/admin/products/${product.uuid}`)

        $q.notify({
          type: 'positive',
          message: 'Product deleted successfully',
          icon: 'check_circle',
          position: 'top'
        })

        await fetchProducts()
      } catch (error) {
        console.error('Error deleting product:', error)
        $q.notify({
          type: 'negative',
          message: 'Failed to delete product',
          caption: error.response?.data?.message || error.message,
          position: 'top'
        })
      } finally {
        loading.value = false
      }
    }

    const toggleStatus = async (product) => {
      try {
        await axios.patch(`/admin/products/${product.uuid}/status`, {
          status: product.status
        })

        $q.notify({
          type: 'positive',
          message: `Product status updated to ${product.status}`,
          position: 'top'
        })
      } catch (error) {
        console.error('Error updating status:', error)
        // Revert status on error
        product.status = product.status === 'active' ? 'inactive' : 'active'
        $q.notify({
          type: 'negative',
          message: 'Failed to update status',
          caption: error.response?.data?.message || error.message,
          position: 'top'
        })
      }
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

    const formatDate = (dateString) => {
      if (!dateString) return 'N/A'
      return date.formatDate(dateString, 'MMM DD, YYYY HH:mm')
    }

    onMounted(async () => {
      await Promise.all([
        fetchProducts(),
        fetchCategories()
      ])
    })

    return {
      loading,
      products,
      selectedProduct,
      viewDialog,
      imageSlide,
      filters,
      pagination,
      categoryOptions,
      statusOptions,
      columns,
      fetchProducts,
      onRequest,
      handleSearch,
      resetFilters,
      viewProduct,
      navigateToCreate,
      navigateToEdit,
      confirmDelete,
      toggleStatus,
      getStatusColor,
      getStatusLabel,
      formatDate,
      selectedType,
      productTypes,
      typeDialog,
      submitType
    }
  }
}
</script>

<style scoped>
.products-table {
}

.ellipsis-2-lines {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.4em;
  max-height: 2.8em;
}
</style>