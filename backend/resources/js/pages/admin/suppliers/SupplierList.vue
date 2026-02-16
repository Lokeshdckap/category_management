<template>
  <q-page class="q-pa-md">
    <div class="q-pa-md" style="max-width: 1400px; margin: 0 auto">
      <!-- Header -->
      <div class="row items-center justify-between q-mb-lg">
        <div>
          <div class="text-h4 text-weight-bold text-grey-9">Suppliers</div>
        </div>
        <q-btn
          unelevated
          color="primary"
          label="Add New Supplier"
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
                placeholder="Search by name"
                @update:model-value="handleSearch"
                debounce="500"
                clearable
              >
                <template v-slot:prepend>
                  <q-icon name="search" />
                </template>
              </q-input>
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
          :rows="suppliers"
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
        

         
              <q-td key="id" :props="props">
                <div class="text-grey-8 text-weight-medium">
                  #{{ props.row.id }}
                </div>
              </q-td>



              
              <q-td key="name" :props="props">
                <div class="row items-center q-gutter-x-sm">
                  <div class="text-grey-9 text-weight-bold">
                    {{ props.row.name }}
                  </div>
                  <q-badge v-if="props.row.is_default" color="orange" label="Default" />
                </div>
              </q-td>

              <q-td key="short_description" :props="props">
                <div class="text-grey-7 ellipsis-2-lines" style="max-width: 300px">
                  {{ props.row.description || 'No description' }}
                </div>
              </q-td>

              <q-td key="status" :props="props">
                <q-toggle
                  v-model="props.row.status"
                  true-value="active"
                  false-value="inactive"
                  color="positive"
                  @update:model-value="toggleStatus(props.row)"
                  :loading="props.row.toggling"
                >
                  <q-tooltip>{{ props.row.status === 'active' ? 'Active' : 'Inactive' }}</q-tooltip>
                </q-toggle>
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
    const $q = useQuasar()
    const router = useRouter()

    // Reactive data
    const loading = ref(false)
    const suppliers = ref([])
    const selectedProduct = ref(null)

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


    // Table columns
    const columns = [
      {
        name: 'id',
        label: 'ID',
        field: 'id',
        align: 'left',
        sortable: true
      },
      {
        name: 'name',
        label: 'Supplier Name',
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
        name: 'actions',
        label: 'Actions',
        align: 'center',
        sortable: false
      }
    ]

    const fetchSuppliers = async () => {
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

        const response = await axios.get('/admin/suppliers',{params})

        if (response.data.suppliers.data) {
          suppliers.value = response.data.suppliers.data
          pagination.value.rowsNumber = response.data.suppliers.total
        } else {
          products.value = response.data.suppliers
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
    const onRequest = (props) => {
      const { page, rowsPerPage, sortBy, descending } = props.pagination

      pagination.value.page = page
      pagination.value.rowsPerPage = rowsPerPage
      pagination.value.sortBy = sortBy
      pagination.value.descending = descending

      fetchSuppliers()
    }

    const handleSearch = () => {
      pagination.value.page = 1
      fetchSuppliers()
    }

    const resetFilters = () => {
      filters.value = {
        search: '',
        category_id: null,
        status: null
      }
      pagination.value.page = 1
      fetchSuppliers()
    }

    const navigateToCreate = () => {
      router.push(`/supplier/create`)
      
    }

    const navigateToEdit = (supplier) => {
      router.push(`/suppliers/${supplier.id}/edit`)
    }

    const toggleStatus = async (supplier) => {
      try {
        supplier.toggling = true
        const response = await axios.post(`/admin/suppliers/${supplier.id}/toggle-status`)
        $q.notify({
          type: 'positive',
          message: response.data.message,
          position: 'top'
        })
      } catch (error) {
        // Rollback on error
        supplier.status = supplier.status === 'active' ? 'inactive' : 'active'
        $q.notify({
          type: 'negative',
          message: error.response?.data?.message || 'Failed to update status',
          position: 'top'
        })
      } finally {
        supplier.toggling = false
      }
    }

    const confirmDelete = (supplier) => {
      $q.dialog({
        title: 'Confirm Delete',
        message: `Are you sure you want to delete supplier "${supplier.name}"?`,
        cancel: true,
        persistent: true,
        ok: {
          color: 'negative',
          label: 'Delete',
          unelevated: true
        }
      }).onOk(() => {
        deleteSupplier(supplier)
      })
    }

    const deleteSupplier = async (supplier) => {
      try {
        await axios.delete(`/admin/suppliers/${supplier.id}`)
        $q.notify({
          type: 'positive',
          message: 'Supplier deleted successfully',
          position: 'top'
        })
        fetchSuppliers()
      } catch (error) {
        $q.notify({
          type: 'negative',
          message: error.response?.data?.message || 'Failed to delete supplier',
          position: 'top',
          timeout: 5000 // Give more time for the specific message
        })
      }
    }


    const formatDate = (dateString) => {
      if (!dateString) return 'N/A'
      return date.formatDate(dateString, 'MMM DD, YYYY HH:mm')
    }

    onMounted(async () => {
      await Promise.all([
        fetchSuppliers(),
      ])
    })

    return {
      loading,
      suppliers,
      filters,
      pagination,
      columns,
      fetchSuppliers,
      onRequest,
      handleSearch,
      resetFilters,
      navigateToCreate,
      navigateToEdit,
      toggleStatus,
      confirmDelete,
      formatDate,
    }
  }
}
</script>

<style scoped>
.ellipsis-2-lines {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.4em;
  max-height: 2.8em;
}
</style>