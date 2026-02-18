<template>
  <q-page class="q-pa-md">
    <div class="row items-center q-mb-md">
      <div class="text-h5 text-weight-medium">Customers</div>
      <q-space />
      <q-btn
        color="primary"
        label="Add Customer"
        icon="add"
        unelevated
        @click="openCreateModal"
      />
    </div>

    <!-- Table -->
    <q-table
      :rows="customers"
      :columns="columns"
      row-key="uuid"
      flat
      bordered
      :loading="loading"
      class="shadow-1"
    >
      <!-- Status Toggle -->
      <template #body-cell-status="props">
        <q-td :props="props">
          <q-toggle
            :model-value="props.row.status"
            color="positive"
            :disable="props.row.statusLoading"
            @update:model-value="changeStatus(props.row, $event)"
          />
        </q-td>
      </template>

      <!-- Actions -->
      <template #body-cell-actions="props">
        <q-td :props="props" class="q-gutter-x-sm">
          <q-btn
            flat
            dense
            round
            icon="edit"
            color="primary"
            size="sm"
            @click="openEditModal(props.row)"
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
            @click="deleteCustomer(props.row.uuid)"
          >
            <q-tooltip>Delete</q-tooltip>
          </q-btn>
        </q-td>
      </template>
    </q-table>

    <!-- Create/Edit Modal -->
    <q-dialog v-model="showModal" persistent>
      <q-card style="min-width: 450px">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">{{ isEdit ? 'Edit Customer' : 'Add Customer' }}</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section class="q-pt-md">
          <q-form @submit="submitForm" class="q-gutter-md">
            <q-input
              filled
              v-model="form.name"
              label="Name *"
              hint="Customer's full name"
              lazy-rules
              :rules="[ val => val && val.length > 0 || 'Name is required']"
            />

            <q-input
              filled
              v-model="form.email"
              label="Email *"
              type="email"
              hint="Customer's email address"
              lazy-rules
              :rules="[ 
                val => val && val.length > 0 || 'Email is required',
                val => /.+@.+\..+/.test(val) || 'Please enter a valid email'
              ]"
            />

            <q-input
              filled
              v-model="form.password"
              :label="isEdit ? 'Password (Leave blank to keep current)' : 'Password *'"
              :type="showPassword ? 'text' : 'password'"
              hint="Minimum 8 characters"
              lazy-rules
              :rules="[ 
                val => (isEdit || (val && val.length >= 8)) || 'Password must be at least 8 characters'
              ]"
            >
              <template v-slot:append>
                <q-icon
                  :name="showPassword ? 'visibility' : 'visibility_off'"
                  class="cursor-pointer"
                  @click="showPassword = !showPassword"
                />
              </template>
            </q-input>

            <q-select
              filled
              v-model="form.customer_group_id"
              :options="groupOptions"
              label="Customer Group *"
              emit-value
              map-options
              option-value="id"
              option-label="name"
              :rules="[ val => !!val || 'Please select a customer group']"
            >
              <template #no-option>
                <q-item>
                  <q-item-section class="text-grey">
                    No results
                  </q-item-section>
                </q-item>
              </template>
            </q-select>

            <q-toggle
              v-model="form.status"
              label="Active"
              color="positive"
            />

            <div class="row justify-end q-gutter-sm">
              <q-btn flat label="Cancel" color="primary" v-close-popup />
              <q-btn :loading="submitting" label="Save" type="submit" color="primary" unelevated />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import axios from 'axios'

const $q = useQuasar()

const customers = ref([])
const groupOptions = ref([])
const loading = ref(false)
const showModal = ref(false)
const isEdit = ref(false)
const submitting = ref(false)
const editUuid = ref(null)
const showPassword = ref(false)

const form = ref({
  name: '',
  email: '',
  password: '',
  customer_group_id: null,
  status: true
})

const columns = [
  { name: 'id', label: 'ID', field: 'id', align: 'left', sortable: true },
  { name: 'name', label: 'Name', field: 'name', align: 'left', sortable: true },
  { name: 'email', label: 'Email', field: 'email', align: 'left', sortable: true },
  { name: 'customer_group', label: 'Group', field: 'customer_group', align: 'left', sortable: true },
  { name: 'status', label: 'Status', field: 'status', align: 'center', sortable: true },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'center' }
]

const fetchCustomers = async () => {
  loading.value = true
  try {
    const res = await axios.get('/admin/customers')
    if (Array.isArray(res.data)) {
      customers.value = res.data
    } else {
      console.error('API returned non-array:', res.data)
      customers.value = []
    }
  } catch (error) {
    console.error(error)
    $q.notify({
      type: 'negative',
      message: 'Failed to fetch customers'
    })
  } finally {
    loading.value = false
  }
}

const fetchGroups = async () => {
  try {
    const res = await axios.get('/admin/customer-groups')
    groupOptions.value = Array.isArray(res.data) ? res.data : []
  } catch (error) {
    console.error('Failed to fetch groups:', error)
  }
}

const openCreateModal = () => {
  isEdit.value = false
  editUuid.value = null
  form.value = {
    name: '',
    email: '',
    password: '',
    customer_group_id: null,
    status: true
  }
  showPassword.value = false
  showModal.value = true
}

const openEditModal = (customer) => {
  isEdit.value = true
  editUuid.value = customer.uuid
  form.value = {
    name: customer.name,
    email: customer.email,
    password: '',
    customer_group_id: customer.customer_group_id,
    status: customer.status
  }
  showPassword.value = false
  showModal.value = true
}

const submitForm = async () => {
  submitting.value = true
  try {
    if (isEdit.value) {
      await axios.put(`/admin/customers/${editUuid.value}`, form.value)
      $q.notify({ type: 'positive', message: 'Customer updated successfully' })
    } else {
      await axios.post('/admin/customers', form.value)
      $q.notify({ type: 'positive', message: 'Customer created successfully' })
    }
    showModal.value = false
    fetchCustomers()
  } catch (error) {
    console.error(error)
    const message = error.response?.data?.message || 'Operation failed'
    $q.notify({ type: 'negative', message })
  } finally {
    submitting.value = false
  }
}

const changeStatus = async (customer, newStatus) => {
  const oldStatus = customer.status
  customer.status = newStatus
  customer.statusLoading = true

  try {
    const res = await axios.patch(`/admin/customers/${customer.uuid}/status`)
    $q.notify({ type: 'positive', message: 'Status updated successfully' })
  } catch (error) {
    console.error(error)
    customer.status = oldStatus
    $q.notify({ type: 'negative', message: 'Failed to update status' })
  } finally {
    customer.statusLoading = false
  }
}

const deleteCustomer = (uuid) => {
  $q.dialog({
    title: 'Confirm Delete',
    message: 'Are you sure you want to delete this customer?',
    cancel: true,
    persistent: true
  }).onOk(async () => {
    try {
      await axios.delete(`/admin/customers/${uuid}`)
      $q.notify({ type: 'positive', message: 'Customer deleted successfully' })
      fetchCustomers()
    } catch (error) {
      console.error(error)
      $q.notify({ type: 'negative', message: 'Failed to delete customer' })
    }
  })
}

onMounted(() => {
  fetchCustomers()
  fetchGroups()
})
</script>
