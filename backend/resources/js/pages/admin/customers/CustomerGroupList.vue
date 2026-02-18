<template>
  <q-page class="q-pa-md">
    <div class="row items-center q-mb-md">
      <div class="text-h5 text-weight-medium">Customer Groups</div>
      <q-space />
      <q-btn
        color="primary"
        label="Add Group"
        icon="add"
        unelevated
        @click="openCreateModal"
      />
    </div>

    <!-- Table -->
    <q-table
      :rows="groups"
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
            @click="deleteGroup(props.row.uuid)"
          >
            <q-tooltip>Delete</q-tooltip>
          </q-btn>
        </q-td>
      </template>
    </q-table>

    <!-- Create/Edit Modal -->
    <q-dialog v-model="showModal">
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">{{ isEdit ? 'Edit Customer Group' : 'Add Customer Group' }}</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-form @submit="submitForm" class="q-gutter-md">
            <q-input
              filled
              v-model="form.name"
              label="Group Name *"
              lazy-rules
              :rules="[ val => val && val.length > 0 || 'Please type something']"
            />

            <q-toggle
              v-model="form.status"
              label="Active"
              color="positive"
            />

            <div align="right">
              <q-btn flat label="Cancel" color="primary" v-close-popup />
              <q-btn :loading="submitting" label="Save" type="submit" color="primary" />
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

const groups = ref([])
const loading = ref(false)
const showModal = ref(false)
const isEdit = ref(false)
const submitting = ref(false)
const editUuid = ref(null)

const form = ref({
  name: '',
  status: true
})

const columns = [
  { name: 'id', label: 'ID', field: 'id', align: 'left', sortable: true },
  // { name: 'uuid', label: 'UUID', field: 'uuid', align: 'left', sortable: true },
  { name: 'name', label: 'Name', field: 'name', align: 'left', sortable: true },
  { name: 'status', label: 'Status', field: 'status', align: 'center', sortable: true },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'center' }
]

const fetchGroups = async () => {
  loading.value = true
  try {
    const res = await axios.get('/admin/customer-groups')
    if (Array.isArray(res.data)) {
      groups.value = res.data
    } else {
      console.error('API returned non-array:', res.data)
      groups.value = []
      $q.notify({ type: 'warning', message: 'Received invalid data format' })
    }
  } catch (error) {
    console.error(error)
    $q.notify({
      type: 'negative',
      message: 'Failed to fetch customer groups'
    })
  } finally {
    loading.value = false
  }
}

const openCreateModal = () => {
  isEdit.value = false
  editUuid.value = null
  form.value = {
    name: '',
    status: true
  }
  showModal.value = true
}

const openEditModal = (group) => {
  isEdit.value = true
  editUuid.value = group.uuid
  form.value = {
    name: group.name,
    status: group.status
  }
  showModal.value = true
}

const submitForm = async () => {
  submitting.value = true
  try {
    if (isEdit.value) {
      await axios.put(`/admin/customer-groups/${editUuid.value}`, form.value)
      $q.notify({ type: 'positive', message: 'Group updated successfully' })
    } else {
      await axios.post('/admin/customer-groups', form.value)
      $q.notify({ type: 'positive', message: 'Group created successfully' })
    }
    showModal.value = false
    fetchGroups()
  } catch (error) {
    console.error(error)
    $q.notify({ type: 'negative', message: error.response.data.message || 'Operation failed' })
  } finally {
    submitting.value = false
  }
}

const changeStatus = async (group, newStatus) => {
  const oldStatus = group.status
  group.status = newStatus
  group.statusLoading = true

  try {
    await axios.patch(`/admin/customer-groups/${group.uuid}/status`)
    $q.notify({ type: 'positive', message: 'Status updated successfully' })
  } catch (error) {
    console.error(error)
    group.status = oldStatus
    $q.notify({ type: 'negative', message: 'Failed to update status' })
  } finally {
    group.statusLoading = false
  }
}

const deleteGroup = (uuid) => {
  $q.dialog({
    title: 'Confirm Delete',
    message: 'Are you sure you want to delete this group?',
    cancel: true,
    persistent: true
  }).onOk(async () => {
    try {
      await axios.delete(`/admin/customer-groups/${uuid}`)
      $q.notify({ type: 'positive', message: 'Group deleted successfully' })
      fetchGroups()
    } catch (error) {
      console.error(error)
      $q.notify({ type: 'negative', message: 'Failed to delete group' })
    }
  })
}

onMounted(() => {
  fetchGroups()
})
</script>
