<template>
  <q-page padding>
    <div class="row q-col-gutter-md">
      <div class="col-12">
        <q-card flat bordered class="q-pa-md">
          <q-card-section class="row items-center q-pb-none">
            <div class="text-h6">Global Attributes</div>
            <q-space />
            <q-btn color="primary" icon="add" label="New Attribute" @click="openCreateDialog" />
          </q-card-section>

          <q-separator q-my-md />

          <q-card-section>
            <q-list bordered separator>
               <draggable
                v-if="Array.isArray(attributes)"
                v-model="attributes"
                item-key="id"
                handle=".handle"
                @end="onDragEnd"
              >
                <template #item="{ element }">
                  <q-item>
                    <q-item-section avatar>
                      <q-icon name="drag_indicator" class="handle cursor-pointer" color="grey-7" />
                    </q-item-section>

                    <q-item-section>
                      <div class="row items-center no-wrap">
                        <q-item-label class="text-weight-bold">{{ element.name }}</q-item-label>
                        <q-chip 
                          :color="element.status === 'active' ? 'positive' : 'grey-7'" 
                          text-color="white" 
                          size="xs" 
                          dense 
                          class="q-ml-sm"
                        >
                          {{ element.status === 'active' ? 'Active' : 'Inactive' }}
                        </q-chip>
                      </div>
                      <q-item-label caption>
                        Values: {{ element.values.map(v => v.value).join(', ') }}
                      </q-item-label>
                    </q-item-section>

                    <q-item-section side>
                      <div class="row q-gutter-xs">
                        <q-btn flat round color="primary" icon="edit" @click="openEditDialog(element)" />
                        <q-btn flat round color="negative" icon="delete" @click="confirmDelete(element)" />
                      </div>
                    </q-item-section>
                  </q-item>
                </template>
              </draggable>
              <q-item v-if="attributes.length === 0">
                <q-item-section class="text-center text-grey-6 italic">
                  No attributes found. Click "New Attribute" to create one.
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Create/Edit Dialog -->
    <q-dialog v-model="dialog.open" persistent full-width>
      <q-card>
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">{{ dialog.editMode ? 'Edit Attribute' : 'New Attribute' }}</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section class="q-pa-md">
          <q-form @submit="saveAttribute" class="q-gutter-md">
            <div class="row q-col-gutter-md">
              <div class="col-12 col-md-6">
                <q-input
                  filled
                  v-model="form.name"
                  label="Attribute Name *"
                  hint="Example: Size, Colour, Material"
                  lazy-rules
                  :rules="[ val => val && val.length > 0 || 'Name is required']"
                />
              </div>
              <div class="col-12 col-md-6">
                <q-select
                  filled
                  v-model="form.status"
                  :options="['active', 'inactive']"
                  label="Status"
                  emit-value
                  map-options
                >
                  <template v-slot:option="scope">
                    <q-item v-bind="scope.itemProps">
                      <q-item-section avatar>
                        <q-icon :name="scope.opt === 'active' ? 'check_circle' : 'cancel'" :color="scope.opt === 'active' ? 'positive' : 'grey-7'" />
                      </q-item-section>
                      <q-item-section>
                        <q-item-label>{{ scope.opt.charAt(0).toUpperCase() + scope.opt.slice(1) }}</q-item-label>
                      </q-item-section>
                    </q-item>
                  </template>
                </q-select>
              </div>
            </div>

            <div class="text-subtitle1 q-mt-lg">Attribute Values</div>
            <q-separator q-mb-md />

            <div class="row q-col-gutter-sm items-center">
              <div class="col-grow">
                <q-input
                  filled
                  v-model="newValue"
                  label="New Value"
                  hint="Press Enter or click Add"
                  @keyup.enter="addValue"
                />
              </div>
              <div class="col-auto">
                <q-btn color="secondary" icon="add" label="Add" @click="addValue" :disable="!newValue" />
              </div>
            </div>

            <q-list bordered separator class="q-mt-md">
               <draggable
                v-if="Array.isArray(form.values)"
                v-model="form.values"
                item-key="tempId"
                handle=".value-handle"
              >
                <template #item="{ element, index }">
                  <q-item>
                    <q-item-section avatar>
                      <q-icon name="drag_handle" class="value-handle cursor-pointer" color="grey-6" />
                    </q-item-section>
                    <q-item-section>
                      <q-input v-model="element.value" dense borderless hide-bottom-space />
                    </q-item-section>
                    <q-item-section side>
                      <div class="row items-center q-gutter-x-sm">
                        <q-toggle
                          v-model="element.status"
                          color="primary"
                          true-value="active"
                          false-value="inactive"
                          dense
                          size="sm"
                        >
                          <q-tooltip>Status: {{ element.status }}</q-tooltip>
                        </q-toggle>
                        <q-btn flat round dense color="negative" icon="remove_circle" @click="removeValue(index)" />
                      </div>
                    </q-item-section>
                  </q-item>
                </template>
              </draggable>
              <q-item v-if="form.values.length === 0">
                <q-item-section class="text-center text-grey-6 italic">
                  At least one value is required.
                </q-item-section>
              </q-item>
            </q-list>

            <div class="row justify-end q-mt-xl">
              <q-btn label="Cancel" flat color="grey" v-close-popup />
              <q-btn :label="dialog.editMode ? 'Update' : 'Create'" type="submit" color="primary" class="q-ml-sm" :loading="saving" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Delete Confirmation -->
    <q-dialog v-model="deleteDialog.open">
      <q-card>
        <q-card-section class="row items-center">
          <q-avatar icon="warning" color="negative" text-white />
          <span class="q-ml-sm">Are you sure you want to delete this attribute? This action cannot be undone and may affect existing products.</span>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="No" color="primary" v-close-popup />
          <q-btn flat label="Yes, Delete" color="negative" @click="deleteAttribute" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { useQuasar } from 'quasar';
import draggable from 'vuedraggable';

const $q = useQuasar();
const attributes = ref([]);
const loading = ref(false);
const saving = ref(false);
const newValue = ref('');

const dialog = reactive({
  open: false,
  editMode: false,
  itemId: null
});

const form = reactive({
  name: '',
  status: 'active',
  values: []
});

const deleteDialog = reactive({
  open: false,
  item: null
});

const fetchAttributes = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/admin/attributes');
    attributes.value = response.data;
  } catch (error) {
    $q.notify({
      color: 'negative',
      message: 'Failed to fetch attributes',
      icon: 'report_problem'
    });
  } finally {
    loading.value = false;
  }
};

const openCreateDialog = () => {
  dialog.editMode = false;
  dialog.itemId = null;
  form.name = '';
  form.status = 'active';
  form.values = [];
  dialog.open = true;
};

const openEditDialog = (item) => {
  dialog.editMode = true;
  dialog.itemId = item.id;
  form.name = item.name;
  form.status = item.status || 'active';
  form.values = item.values.map(v => ({ 
    ...v, 
    tempId: v.id,
    status: v.status || 'active' 
  }));
  dialog.open = true;
};

const addValue = () => {
  if (newValue.value.trim()) {
    form.values.push({
      value: newValue.value.trim(),
      status: 'active',
      tempId: Date.now() + Math.random()
    });
    newValue.value = '';
  }
};

const removeValue = (index) => {
  form.values.splice(index, 1);
};

const saveAttribute = async () => {
  if (form.values.length === 0) {
    $q.notify({
      color: 'warning',
      message: 'Please add at least one value',
      icon: 'warning'
    });
    return;
  }

  saving.value = true;
  try {
    const payload = {
      name: form.name,
      status: form.status,
      values: form.values.map((v, index) => ({
        id: v.id || null,
        value: v.value,
        status: v.status || 'active',
        sort_order: index
      }))
    };

    if (dialog.editMode) {
      await axios.put(`/admin/attributes/${dialog.itemId}`, payload);
      $q.notify({ color: 'positive', message: 'Attribute updated successfully' });
    } else {
      await axios.post('/admin/attributes', payload);
      $q.notify({ color: 'positive', message: 'Attribute created successfully' });
    }
    dialog.open = false;
    fetchAttributes();
  } catch (error) {
    $q.notify({
      color: 'negative',
      message: error.response?.data?.message || 'Failed to save attribute',
      icon: 'report_problem'
    });
  } finally {
    saving.value = false;
  }
};

const confirmDelete = (item) => {
  deleteDialog.item = item;
  deleteDialog.open = true;
};

const deleteAttribute = async () => {
  try {
    await axios.delete(`/admin/attributes/${deleteDialog.item.id}`);
    $q.notify({ color: 'positive', message: 'Attribute deleted successfully' });
    deleteDialog.open = false;
    fetchAttributes();
  } catch (error) {
    $q.notify({
      color: 'negative',
      message: 'Failed to delete attribute',
      icon: 'report_problem'
    });
  }
};

const onDragEnd = async () => {
  const payload = {
    attributes: attributes.value.map((attr, index) => ({
      id: attr.id,
      sort_order: index
    }))
  };

  try {
    await axios.post('/admin/attributes/reorder', payload);
    $q.notify({ color: 'info', message: 'Order updated', timeout: 1000 });
  } catch (error) {
    $q.notify({ color: 'negative', message: 'Failed to update order' });
    fetchAttributes(); // Revert order
  }
};

onMounted(() => {
  fetchAttributes();
});
</script>

<style scoped>
.handle, .value-handle {
  cursor: grab;
}
.handle:active, .value-handle:active {
  cursor: grabbing;
}
</style>
